<?php
session_start();
$today = date('Y-m-d');
$today_formatted = date('d F Y');
include 'config.php';

// Cek session pengguna
if (!isset($_SESSION['pengguna'])) {
    header("Location: login.php");
    exit;
}

// Handle filter by single date
$selected_date = isset($_GET['selected_date']) ? $_GET['selected_date'] : $today;

// Validasi koneksi database
if (!$pdo) {
    die("Koneksi database gagal");
}

try {
    // Query untuk mendapatkan data alternatif
    $stmt = $pdo->prepare("SELECT DISTINCT a.* FROM alternatif a 
                         JOIN penilaian p ON a.id = p.alternatif_id 
                         WHERE p.tanggal_penilaian = ?");
    if (!$stmt->execute([$selected_date])) {
        throw new Exception("Gagal mengambil data alternatif");
    }
    $alternatif = $stmt->fetchAll();

    // Query kriteria
    $stmt = $pdo->query("SELECT * FROM kriteria");
    if (!$stmt) {
        throw new Exception("Gagal mengambil data kriteria");
    }
    $kriteria = $stmt->fetchAll();

    // Query penilaian dengan GROUP BY untuk hindari duplikat
    $stmt = $pdo->prepare("SELECT p.alternatif_id, p.kriteria_id, p.nilai 
                          FROM penilaian p
                          WHERE p.tanggal_penilaian = ?
                          GROUP BY p.alternatif_id, p.kriteria_id");
    if (!$stmt->execute([$selected_date])) {
        throw new Exception("Gagal mengambil data penilaian");
    }
    $penilaianData = $stmt->fetchAll();

    // Inisialisasi array penilaian
    $penilaian = [];
    foreach ($penilaianData as $data) {
        $penilaian[$data['alternatif_id']][$data['kriteria_id']] = $data['nilai'];
    }

    // Hitung nilai MFEP
    $hasil = [];
    $total = [];
    foreach ($alternatif as $a) {
        $nilai_total = 0;
        foreach ($kriteria as $k) {
            $nilai_alt_kriteria = $penilaian[$a['id']][$k['id']] ?? 0;
            $nilai = $k['bobot_kriteria'] * $nilai_alt_kriteria;
            $hasil[$a['id']][$k['id']] = $nilai;
            $nilai_total += $nilai;
        }
        $total[$a['id']] = $nilai_total;

        // Simpan/update hasil dengan ON DUPLICATE KEY
        $stmt = $pdo->prepare("INSERT INTO hasil (alternatif_id, nilai_mfep, tanggal_hasil) 
                              VALUES (?, ?, ?)
                              ON DUPLICATE KEY UPDATE nilai_mfep = VALUES(nilai_mfep)");
        if (!$stmt->execute([$a['id'], $nilai_total, $selected_date])) {
            throw new Exception("Gagal menyimpan hasil perhitungan");
        }
    }

    // Query ranking dengan GROUP BY untuk hindari duplikat
    $stmt = $pdo->prepare("SELECT h.nilai_mfep, a.kode_alternatif, a.nama_alternatif 
                          FROM hasil h
                          JOIN alternatif a ON a.id = h.alternatif_id
                          WHERE h.tanggal_hasil = ?
                          GROUP BY h.alternatif_id
                          ORDER BY h.nilai_mfep DESC");
    if (!$stmt->execute([$selected_date])) {
        throw new Exception("Gagal mengambil data ranking");
    }
    $ranking = $stmt->fetchAll();

} catch (Exception $e) {
    die("Error: " . $e->getMessage());
}

// Handle Excel export request
if (isset($_GET['export']) && $_GET['export'] == 'xls') {
    // Cek jika data kosong
    if (empty($alternatif)) {
        die("<script>alert('Tidak ada data untuk diexport'); window.history.back();</script>");
    }
    
    header("Content-Type: application/vnd.ms-excel");
    header("Content-Disposition: attachment; filename=perhitungan_mfep_".$selected_date.".xls");
    header("Pragma: no-cache");
    header("Expires: 0");
    
    echo "<html>";
    echo "<body>";
    echo "<table border='1'>";
    
    // Header
    echo "<tr><th colspan='".(count($kriteria)+3)."'>Laporan Perhitungan MFEP</th></tr>";
    echo "<tr><th colspan='".(count($kriteria)+3)."'>Tanggal: ".date('d F Y', strtotime($selected_date))."</th></tr>";
    echo "<tr><td colspan='".(count($kriteria)+3)."'></td></tr>";
    
    // [Isi tabel Excel sama persis dengan tampilan web]
    // Tabel Nilai Alternatif
    echo "<tr><th colspan='".(count($kriteria)+2)."'>Nilai Alternatif</th></tr>";
    echo "<tr><th>No</th><th>Alternatif</th>";
    foreach ($kriteria as $k) {
        echo "<th>".$k['kode_kriteria']."</th>";
    }
    echo "</tr>";
    
    foreach ($alternatif as $i => $a) {
        echo "<tr>";
        echo "<td>".($i+1)."</td>";
        echo "<td>".$a['nama_alternatif']."</td>";
        foreach ($kriteria as $k) {
            echo "<td>".($penilaian[$a['id']][$k['id']] ?? 0)."</td>";
        }
        echo "</tr>";
    }
    
    // Tabel Hasil Perhitungan
    echo "<tr><td colspan='".(count($kriteria)+3)."'></td></tr>";
    echo "<tr><th colspan='".(count($kriteria)+3)."'>Hasil Perhitungan MFEP</th></tr>";
    echo "<tr><th>No</th><th>Alternatif</th>";
    foreach ($kriteria as $k) {
        echo "<th>".$k['kode_kriteria']."</th>";
    }
    echo "<th>Total</th></tr>";
    
    foreach ($alternatif as $i => $a) {
        echo "<tr>";
        echo "<td>".($i+1)."</td>";
        echo "<td>".$a['nama_alternatif']."</td>";
        foreach ($kriteria as $k) {
            echo "<td>".number_format($hasil[$a['id']][$k['id']] ?? 0, 2)."</td>";
        }
        echo "<td>".number_format($total[$a['id']] ?? 0, 2)."</td>";
        echo "</tr>";
    }
    
    // Tabel Ranking
    echo "<tr><td colspan='".(count($kriteria)+3)."'></td></tr>";
    echo "<tr><th colspan='4'>Hasil Ranking</th></tr>";
    echo "<tr><th>Ranking</th><th>Alternatif</th><th>Kode</th><th>Nilai MFEP</th></tr>";
    
    $rank = 1;
    $previous_score = null;
    foreach ($ranking as $i => $row) {
        if ($i > 0 && $row['nilai_mfep'] != $previous_score) {
            $rank = $i + 1;
        }
        echo "<tr>";
        echo "<td>".$rank."</td>";
        echo "<td>".$row['nama_alternatif']."</td>";
        echo "<td>".$row['kode_alternatif']."</td>";
        echo "<td>".number_format($row['nilai_mfep'], 2)."</td>";
        echo "</tr>";
        $previous_score = $row['nilai_mfep'];
    }
    
    echo "</table>";
    echo "</body>";
    echo "</html>";
    exit;
}
include 'includes/header.php';
?>
<style>
@media print {
    body {
        padding: 20px;
        font-size: 12px;
    }
    .card {
        border: none;
        box-shadow: none;
    }
    .table {
        page-break-inside: avoid;
    }
}
</style>

<div class="container">
    <div class="card text-dark bg-light">
        <div class="card-header">
            <h4>Perhitungan Metode MFEP</h4>
            <div class="float-end">
                <span class="badge bg-info me-2">
                    <?php echo $today_formatted; ?>
                </span>
                <button id="exportExcel" class="btn btn-success me-2 <?php echo empty($alternatif) ? 'disabled' : ''; ?>">
                    Export Excel
                </button>
                <button id="exportPdf" class="btn btn-danger <?php echo empty($alternatif) ? 'disabled' : ''; ?>">
                    Export PDF
                </button>
            </div>
        </div>
        
        <div class="card-body">
            <form method="get" class="mb-4">
                <div class="row g-3 align-items-end">
                    <div class="col-md-8">
                        <label for="selected_date" class="form-label">Tanggal Penilaian</label>
                        <input type="date" class="form-control" id="selected_date" name="selected_date" 
                               value="<?php echo $selected_date; ?>" max="<?php echo $today; ?>">
                    </div>
                    <div class="col-md-4">
                        <button type="submit" class="btn btn-primary me-2">Filter</button>
                        <a href="?" class="btn btn-secondary">Reset</a>
                    </div>
                </div>
            </form>

            <?php if (empty($alternatif)): ?>
    <div class="alert alert-warning">
        Tidak ditemukan data penilaian untuk tanggal <?php echo date('d F Y', strtotime($selected_date)); ?>
    </div>
<?php else: ?>
    <!-- Tabel Nilai Alternatif -->
    <div class="table-responsive mb-4">
        <table class="table table-bordered table-striped">
            <thead class="table-primary">
                <tr>
                    <th>No</th>
                    <th>Alternatif</th>
                    <?php foreach ($kriteria as $k): ?>
                        <th class="text-center"><?php echo $k['kode_kriteria']; ?></th>
                    <?php endforeach; ?>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($alternatif as $i => $a): ?>
                    <tr>
                        <td><?php echo $i+1; ?></td>
                        <td><?php echo $a['nama_alternatif']; ?></td>
                        <?php foreach ($kriteria as $k): ?>
                            <td class="text-center"><?php echo $penilaian[$a['id']][$k['id']] ?? '0'; ?></td>
                        <?php endforeach; ?>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <!-- Tabel Hasil Perhitungan -->
    <div class="table-responsive mb-4">
        <table class="table table-bordered table-striped">
            <thead class="table-success">
                <tr>
                    <th>No</th>
                    <th>Alternatif</th>
                    <?php foreach ($kriteria as $k): ?>
                        <th class="text-center"><?php echo $k['kode_kriteria']; ?> (<?php echo $k['bobot_kriteria']; ?>)</th>
                    <?php endforeach; ?>
                    <th class="text-center">Total</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($alternatif as $i => $a): ?>
                    <tr>
                        <td><?php echo $i+1; ?></td>
                        <td><?php echo $a['nama_alternatif']; ?></td>
                        <?php foreach ($kriteria as $k): ?>
                            <td class="text-center"><?php echo number_format($hasil[$a['id']][$k['id']] ?? 0, 2); ?></td>
                        <?php endforeach; ?>
                        <td class="text-center fw-bold"><?php echo number_format($total[$a['id']] ?? 0, 2); ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <!-- Tabel Ranking -->
    <div class="table-responsive">
        <table class="table table-bordered table-striped">
            <thead class="table-info">
                <tr>
                    <th>Ranking</th>
                    <th>Kode</th>
                    <th>Alternatif</th>
                    <th class="text-center">Nilai MFEP</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                $rank = 1;
                $previous_score = null;
                foreach ($ranking as $i => $row): 
                    if ($i > 0 && $row['nilai_mfep'] != $previous_score) {
                        $rank = $i + 1;
                    }
                ?>
                    <tr>
                        <td><?php echo $rank; ?></td>
                        <td><?php echo $row['kode_alternatif']; ?></td>
                        <td><?php echo $row['nama_alternatif']; ?></td>
                        <td class="text-center fw-bold"><?php echo number_format($row['nilai_mfep'], 2); ?></td>
                    </tr>
                <?php 
                    $previous_score = $row['nilai_mfep'];
                endforeach; 
                ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>
        </div>
        
        <div class="card-footer text-muted small">
            Dicetak pada: <?php echo $today_formatted; ?>
        </div>
    </div>
</div>
<script>
// Fungsi untuk memuat library PDF dengan fallback
function loadPDFLibrary(callback) {
    const jsPDFUrl = 'https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js';
    const html2canvasUrl = 'https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js';
    
    // Cek jika library sudah dimuat
    if (typeof jsPDF !== 'undefined' && typeof html2canvas !== 'undefined') {
        callback();
        return;
    }

    // Fungsi untuk memuat script
    function loadScript(url, onSuccess) {
        return new Promise((resolve, reject) => {
            const script = document.createElement('script');
            script.src = url;
            script.onload = () => {
                onSuccess();
                resolve();
            };
            script.onerror = () => {
                console.error('Gagal memuat script: ' + url);
                reject();
            };
            document.head.appendChild(script);
        });
    }

    // Memuat library dengan fallback
    Promise.all([
        loadScript(jsPDFUrl, () => console.log('jsPDF loaded')),
        loadScript(html2canvasUrl, () => console.log('html2canvas loaded'))
    ]).then(() => {
        callback();
    }).catch(() => {
        // Coba CDN fallback jika gagal
        const fallbackPromises = [];
        
        if (typeof jsPDF === 'undefined') {
            fallbackPromises.push(loadScript('https://cdn.jsdelivr.net/npm/jspdf@2.5.1/dist/jspdf.umd.min.js', 
                () => console.log('jsPDF fallback loaded')));
        }
        
        if (typeof html2canvas === 'undefined') {
            fallbackPromises.push(loadScript('https://cdn.jsdelivr.net/npm/html2canvas@1.4.1/dist/html2canvas.min.js', 
                () => console.log('html2canvas fallback loaded')));
        }
        
        Promise.all(fallbackPromises).then(callback).catch(() => {
            alert('Gagal memuat library PDF. Silakan coba lagi atau gunakan export Excel.');
        });
    });
}

// Fungsi untuk export PDF
function exportToPDF() {
    // Cek jika data kosong
    if (document.querySelector('.alert-warning')) {
        alert('Tidak ada data untuk diexport ke PDF');
        return;
    }

    loadPDFLibrary(() => {
        try {
            const { jsPDF } = window.jspdf;
            const element = document.querySelector('.card');
            const options = {
                scale: 2,
                useCORS: true,
                allowTaint: true,
                logging: true,
                scrollX: 0,
                scrollY: 0,
                windowWidth: element.scrollWidth,
                windowHeight: element.scrollHeight
            };
            
            // Tampilkan loading
            const loading = document.createElement('div');
            loading.style.position = 'fixed';
            loading.style.top = '0';
            loading.style.left = '0';
            loading.style.width = '100%';
            loading.style.height = '100%';
            loading.style.backgroundColor = 'rgba(0,0,0,0.5)';
            loading.style.color = 'white';
            loading.style.display = 'flex';
            loading.style.justifyContent = 'center';
            loading.style.alignItems = 'center';
            loading.style.zIndex = '9999';
            loading.innerHTML = '<div>Membuat PDF, harap tunggu...</div>';
            document.body.appendChild(loading);
            
            html2canvas(element, options).then(canvas => {
                document.body.removeChild(loading);
                
                const imgData = canvas.toDataURL('image/png');
                const pdf = new jsPDF('p', 'mm', 'a4');
                const imgWidth = 190;
                const pageHeight = 277;
                const imgHeight = canvas.height * imgWidth / canvas.width;
                let heightLeft = imgHeight;
                let position = 10;
                
                // Add cover page
                pdf.setFontSize(16);
                pdf.text('Laporan Perhitungan MFEP', 105, 15, {align: 'center'});
                pdf.setFontSize(12);
                pdf.text('Tanggal: <?php echo date('d F Y', strtotime($selected_date)); ?>', 105, 22, {align: 'center'});
                pdf.text('Dicetak pada: <?php echo $today_formatted; ?>', 105, 28, {align: 'center'});
                
                // Add content
                pdf.addImage(imgData, 'PNG', 10, 35, imgWidth, imgHeight);
                heightLeft -= pageHeight;
                
                // Add new pages if content is too long
                while (heightLeft >= 0) {
                    position = heightLeft - imgHeight + 35;
                    pdf.addPage();
                    pdf.addImage(imgData, 'PNG', 10, position, imgWidth, imgHeight);
                    heightLeft -= pageHeight;
                }
                
                pdf.save('Laporan_MFEP_<?php echo $selected_date; ?>.pdf');
            }).catch(err => {
                document.body.removeChild(loading);
                console.error('Error generating PDF:', err);
                alert('Gagal membuat PDF: ' + err.message);
            });
        } catch (err) {
            console.error('Error in PDF generation:', err);
            alert('Terjadi kesalahan saat membuat PDF. Silakan coba lagi.');
        }
    });
}

// Fungsi untuk export Excel
function exportToExcel() {
    // Cek jika data kosong
    if (document.querySelector('.alert-warning')) {
        alert('Tidak ada data untuk diexport ke Excel');
        return;
    }
    
    window.location.href = '?export=xls&selected_date=<?php echo $selected_date; ?>';
}

// Event listener
document.getElementById('exportPdf').addEventListener('click', exportToPDF);
document.getElementById('exportExcel').addEventListener('click', exportToExcel);

// Nonaktifkan tombol jika data kosong
if (document.querySelector('.alert-warning')) {
    document.getElementById('exportExcel').disabled = true;
    document.getElementById('exportPdf').disabled = true;
}
</script>
<?php include 'includes/footer.php'; ?>