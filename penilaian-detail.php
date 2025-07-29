<?php
session_start();
include 'config.php';

// Cek login
if (!isset($_SESSION['pengguna'])) {
    header("Location: login.php");
    exit;
}

// Validasi parameter
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    $_SESSION['error'] = "Parameter tidak valid!";
    header("Location: penilaian-list.php");
    exit;
}

$alternatif_id = $_GET['id'];
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

// Ambil data penilaian dalam periode
$stmt = $pdo->prepare("SELECT p.*, k.kode_kriteria, k.nama_kriteria, k.bobot_kriteria 
                      FROM penilaian p
                      JOIN kriteria k ON p.kriteria_id = k.id
                      WHERE p.alternatif_id = ? 
                      AND p.tanggal_penilaian BETWEEN ? AND ?
                      ORDER BY p.tanggal_penilaian DESC, k.kode_kriteria ASC");
$stmt->execute([$alternatif_id, $start_date, $end_date]);
$penilaianData = $stmt->fetchAll();

// Kelompokkan data penilaian berdasarkan tanggal
$penilaianByDate = [];
foreach ($penilaianData as $p) {
    $penilaianByDate[$p['tanggal_penilaian']][] = $p;
}

include 'includes/header.php';
?>

<div class="container">
    <div class="card text-dark bg-light mb-4">
        <div class="card-header">
            <h4 class="mb-0">Detail Penilaian</h4>
            <span class="badge bg-primary"><?php echo $alternatif['kode_alternatif'] . ' - ' . $alternatif['nama_alternatif']; ?></span>
        </div>
        <div class="card-body">
            <!-- Filter Periode Tanggal -->
            <form method="get" class="mb-4">
                <input type="hidden" name="id" value="<?php echo $alternatif_id; ?>">
                <div class="row">
                    <div class="col-md-4">
                        <label for="start_date" class="form-label">Tanggal Mulai</label>
                        <input type="date" class="form-control" name="start_date" id="start_date" 
                               value="<?php echo $start_date; ?>">
                    </div>
                    <div class="col-md-4">
                        <label for="end_date" class="form-label">Tanggal Akhir</label>
                        <input type="date" class="form-control" name="end_date" id="end_date" 
                               value="<?php echo $end_date; ?>">
                    </div>
                    <div class="col-md-4 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary me-2">Filter</button>
                        <a href="penilaian-detail.php?id=<?php echo $alternatif_id; ?>" class="btn btn-secondary">Reset</a>
                    </div>
                </div>
            </form>
            
            <div class="alert alert-info mb-3">
                Menampilkan data penilaian dari <strong><?php echo date('d/m/Y', strtotime($start_date)); ?></strong> 
                sampai <strong><?php echo date('d/m/Y', strtotime($end_date)); ?></strong>
            </div>

            <?php if (empty($penilaianByDate)): ?>
                <div class="alert alert-warning">
                    Tidak ada data penilaian untuk periode ini.
                </div>
            <?php else: ?>
                <?php foreach ($penilaianByDate as $tanggal => $penilaian): ?>
                    <div class="card mb-4">
                        <div class="card-header bg-light">
                            <h5 class="mb-0">Tanggal Penilaian: <?php echo date('d/m/Y', strtotime($tanggal)); ?></h5>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-bordered">
                                    <thead class="table-dark">
                                        <tr>
                                            <th>Kode Kriteria</th>
                                            <th>Nama Kriteria</th>
                                            <th>Nilai</th>
                                            <th>Bobot</th>
                                            <th>Skor Tertimbang</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php 
                                        $totalSkor = 0;
                                        foreach ($penilaian as $p): 
                                            $bobot = $p['bobot_kriteria'];
                                            $skorTertimbang = $p['nilai'] * $bobot;
                                            $totalSkor += $skorTertimbang;
                                        ?>
                                            <tr>
                                                <td><?php echo $p['kode_kriteria']; ?></td>
                                                <td><?php echo $p['nama_kriteria']; ?></td>
                                                <td class="text-center"><?php echo $p['nilai']; ?></td>
                                                <td class="text-center"><?php echo $bobot; ?></td>
                                                <td class="text-center"><?php echo number_format($skorTertimbang, 2); ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                        <tr class="table-active">
                                            <td colspan="4" class="text-end"><strong>Total Skor MFEP</strong></td>
                                            <td class="text-center"><strong><?php echo number_format($totalSkor, 2); ?></strong></td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                            
                            <div class="d-flex justify-content-end mt-3">
                                <a href="penilaian-edit.php?alternatif_id=<?php echo $alternatif_id; ?>&tanggal=<?php echo urlencode($tanggal); ?>" 
                                    class="btn btn-sm btn-warning me-2">
                                        <i class="fas fa-edit"></i> Edit
                                </a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
            
            <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                <a href="penilaian-list.php" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Kembali ke Daftar
                </a>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/js.php'; ?>
<?php include 'includes/footer.php'; ?>