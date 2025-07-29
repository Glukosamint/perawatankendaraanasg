<?php
session_start();
include 'config.php';

if (!isset($_SESSION['pengguna'])) {
    header("Location: login.php");
    exit;
}

// Validasi parameter
if (!isset($_GET['alternatif_id']) || !isset($_GET['tanggal'])) {
    $_SESSION['error'] = "Parameter tidak valid!";
    header("Location: penilaian-list.php");
    exit;
}

$alternatif_id = $_GET['alternatif_id'];
$tanggal = $_GET['tanggal'];
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-01');
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-t');

// Ambil data alternatif
$stmt = $pdo->prepare("SELECT * FROM alternatif WHERE id = ?");
$stmt->execute([$alternatif_id]);
$alternatif = $stmt->fetch();

if (!$alternatif) {
    $_SESSION['error'] = "Alternatif tidak ditemukan!";
    header("Location: penilaian-list.php");
    exit;
}

// Ambil data kriteria
$stmt = $pdo->query("SELECT * FROM kriteria ORDER BY kode_kriteria");
$kriteria = $stmt->fetchAll();

// Ambil data penilaian yang akan diedit
$stmt = $pdo->prepare("SELECT * FROM penilaian WHERE alternatif_id = ? AND tanggal_penilaian = ?");
$stmt->execute([$alternatif_id, $tanggal]);
$penilaianData = $stmt->fetchAll();

// Proses form edit
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $new_tanggal = $_POST['tanggal_penilaian'];
    
    // Validasi nilai kriteria
    $allValid = true;
    foreach ($kriteria as $k) {
        if (!isset($_POST['kriteria_'.$k['id']]) || $_POST['kriteria_'.$k['id']] < 0) {
            $allValid = false;
            break;
        }
    }
    
    if (!$allValid) {
        $_SESSION['error'] = "Semua nilai kriteria harus diisi dengan angka positif!";
        header("Location: penilaian-edit.php?alternatif_id=".$alternatif_id."&tanggal=".$tanggal."&start_date=".$start_date."&end_date=".$end_date);
        exit;
    }
    
    // Mulai transaksi
    $pdo->beginTransaction();
    
    try {
        // Hapus data lama
        $pdo->prepare("DELETE FROM penilaian WHERE alternatif_id = ? AND tanggal_penilaian = ?")
            ->execute([$alternatif_id, $tanggal]);
        
        // Simpan data baru
        foreach ($kriteria as $k) {
            if (isset($_POST['kriteria_'.$k['id']])) {
                $nilai = $_POST['kriteria_'.$k['id']];
                $pdo->prepare("INSERT INTO penilaian (alternatif_id, kriteria_id, nilai, tanggal_penilaian) 
                              VALUES (?, ?, ?, ?)")
                    ->execute([$alternatif_id, $k['id'], $nilai, $new_tanggal]);
            }
        }
        
        // Commit transaksi
        $pdo->commit();
        
        echo '<script>
            alert("Penilaian berhasil diperbarui!");
            window.location.href = "penilaian-detail.php?id='.$alternatif_id.'&start_date='.$start_date.'&end_date='.$end_date.'";
        </script>';
        exit;
    } catch (PDOException $e) {
        // Rollback jika error
        $pdo->rollBack();
        echo '<script>
            alert("Gagal memperbarui penilaian: '.addslashes($e->getMessage()).'");
            window.location.href = "penilaian-edit.php?alternatif_id='.$alternatif_id.'&tanggal='.$tanggal.'&start_date='.$start_date.'&end_date='.$end_date.'";
        </script>';
        exit;
    }
}

include 'includes/header.php';
?>

<div class="container">
    <div class="card text-dark bg-light">
        <div class="card-header">
            <h4 class="mb-0">Edit Penilaian</h4>
            <div class="d-flex justify-content-between align-items-center">
                <span class="badge bg-primary">
                    <?php echo $alternatif['kode_alternatif'].' - '.$alternatif['nama_alternatif']; ?>
                </span>
                <small class="text-muted">
                    Periode: <?php echo date('d/m/Y', strtotime($start_date))." - ".date('d/m/Y', strtotime($end_date)); ?>
                </small>
            </div>
        </div>
        <div class="card-body">
            <form method="post" onsubmit="return confirmSubmit()" id="formEditPenilaian">
                <div class="row mb-4">
                    <div class="col-md-6">
                        <label for="tanggal_penilaian" class="form-label">Tanggal Penilaian <span class="text-danger">*</span></label>
                        <input type="date" class="form-control" name="tanggal_penilaian" id="tanggal_penilaian" 
                               value="<?php echo $tanggal; ?>" required>
                    </div>
                </div>
                
                <div class="card mb-4">
                    <div class="card-header bg-light">
                        <h5 class="mb-0">Nilai Kriteria</h5>
                    </div>
                    <div class="card-body">
                        <?php foreach ($kriteria as $k): ?>
                            <?php 
                            $nilai = '';
                            foreach ($penilaianData as $p) {
                                if ($p['kriteria_id'] == $k['id']) {
                                    $nilai = $p['nilai'];
                                    break;
                                }
                            }
                            ?>
                            <div class="mb-3">
                                <label for="kriteria_<?php echo $k['id']; ?>" class="form-label">
                                    <?php echo $k['kode_kriteria'].' - '.$k['nama_kriteria']; ?>
                                    <span class="badge bg-info float-end">Bobot: <?php echo $k['bobot_kriteria']; ?></span>
                                </label>
                                <input type="number" step="0.01" min="0" max="100" class="form-control" 
                                       name="kriteria_<?php echo $k['id']; ?>" 
                                       id="kriteria_<?php echo $k['id']; ?>" 
                                       value="<?php echo $nilai; ?>" required>
                                <?php if (!empty($k['keterangan'])): ?>
                                    <small class="text-muted"><?php echo $k['keterangan']; ?></small>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                
                <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                    <input type="hidden" name="start_date" value="<?php echo $start_date; ?>">
                    <input type="hidden" name="end_date" value="<?php echo $end_date; ?>">
                    <button type="submit" class="btn btn-primary me-md-2" id="btnSimpan">
                        <i class="fas fa-save"></i> Simpan Perubahan
                    </button>
                    <a href="penilaian-detail.php?id=<?php echo $alternatif_id; ?>&start_date=<?php echo $start_date; ?>&end_date=<?php echo $end_date; ?>" 
                       class="btn btn-secondary">
                        <i class="fas fa-times"></i> Batal
                    </a>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// Fungsi untuk konfirmasi sebelum submit
function confirmSubmit() {
    // Validasi semua input
    let allValid = true;
    document.querySelectorAll('input[type="number"]').forEach(input => {
        if (!input.value || input.value < 0) {
            allValid = false;
        }
    });
    
    if (!allValid) {
        alert('Harap isi semua nilai kriteria dengan angka positif!');
        return false;
    }
    
    // Ambil data untuk konfirmasi
    const alternatif = "<?php echo $alternatif['kode_alternatif'].' - '.$alternatif['nama_alternatif']; ?>";
    const tanggal = document.getElementById('tanggal_penilaian').value;
    
    // Tampilkan konfirmasi
    const confirmation = confirm(`Apakah Anda yakin ingin menyimpan perubahan penilaian?\n\nAlternatif: ${alternatif}\nTanggal: ${tanggal}\n\nKlik OK untuk melanjutkan.`);
    
    if (confirmation) {
        // Tampilkan loading saat data dikirim
        const btn = document.getElementById('btnSimpan');
        btn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Menyimpan...';
        btn.disabled = true;
        return true;
    }
    return false;
}
</script>

<?php include 'includes/js.php'; ?>
<?php include 'includes/footer.php'; ?>