<?php
session_start();
include 'config.php';

// Cek login
if (!isset($_SESSION['pengguna'])) {
    header("Location: login.php");
    exit;
}

// Cek role (asumsi role disimpan di $_SESSION['pengguna']['role'])
$currentUserRole = $_SESSION['pengguna']['role'] ?? 'user';
$isAdmin = ($currentUserRole === 'admin');

// Jika bukan admin, redirect ke halaman unauthorized
if (!$isAdmin) {
    header("Location: unauthorized.php");
    exit;
}

// Handle filter by date period
$today = date('Y-m-d');
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : $today;
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : $today; // Sama dengan start_date

$stmt = $pdo->prepare("SELECT penilaian.alternatif_id, alternatif.kode_alternatif, alternatif.nama_alternatif,
                      MIN(penilaian.tanggal_penilaian) as tanggal_mulai, 
                      MAX(penilaian.tanggal_penilaian) as tanggal_akhir
                      FROM penilaian
                      JOIN alternatif ON alternatif.id = penilaian.alternatif_id
                      WHERE penilaian.tanggal_penilaian BETWEEN ? AND ?
                      GROUP BY alternatif_id
                      ORDER BY alternatif_id ASC");
$stmt->execute([$start_date, $end_date]);
$penilaian = $stmt->fetchAll();

include 'includes/header.php';
?>

<div class="container">
    <div class="card text-dark bg-light">
        <div class="card-header">
            Data Penilaian
            <span class="badge bg-primary float-end">Role: <?php echo strtoupper($currentUserRole); ?></span>
        </div>
        <div class="card-body">
            <!-- Filter Periode Tanggal -->
            <form method="get" class="mb-4">
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
                    <a href="?start_date=<?php echo date('Y-m-d'); ?>&end_date=<?php echo date('Y-m-d'); ?>" class="btn btn-secondary">Reset</a>
                    </div>
                </div>
            </form>
            
            <div class="alert alert-info mb-3">
                Menampilkan data penilaian dari <strong><?php echo date('d/m/Y', strtotime($start_date)); ?></strong> 
                sampai <strong><?php echo date('d/m/Y', strtotime($end_date)); ?></strong>
            </div>

            <!-- Hanya admin yang bisa tambah penilaian -->
            <?php if ($isAdmin): ?>
                <a href="penilaian-create.php" class="btn btn-primary mb-3">
                    <i class="fas fa-plus"></i> Tambah Penilaian
                </a>
            <?php endif; ?>
            
            <div class="table-responsive">
                <table class="table table-hover table-striped">
                    <thead class="table-dark">
                        <tr>
                            <th>No</th>
                            <th>Kode Alternatif</th>
                            <th>Nama Alternatif</th>
                            <th>Periode Penilaian</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $no = 1; ?>
                        <?php foreach ($penilaian as $row): ?>
                            <tr>
                                <td><?php echo $no++; ?></td>
                                <td><?php echo htmlspecialchars($row['kode_alternatif']); ?></td>
                                <td><?php echo htmlspecialchars($row['nama_alternatif']); ?></td>
                                <td>
                                    <?php echo date('d/m/Y', strtotime($row['tanggal_mulai'])); ?> - 
                                    <?php echo date('d/m/Y', strtotime($row['tanggal_akhir'])); ?>
                                </td>
                                <td>
                                    <div class="btn-group" role="group">
                                        <!-- Hanya admin yang bisa hapus -->
                                        <?php if ($isAdmin): ?>
                                        <button class="btn btn-sm btn-danger" data-bs-toggle="modal" 
                                            data-bs-target="#deleteModal" 
                                            data-id="<?php echo $row['alternatif_id']; ?>"
                                            data-start-date="<?php echo $start_date; ?>"
                                            data-end-date="<?php echo $end_date; ?>"
                                            title="Hapus">
                                            <i class="fas fa-trash"></i>
                                            </button>
                                        <?php endif; ?>
                                        
                                        <!-- Tombol Detail -->
                                        <a href="penilaian-detail.php?id=<?php echo $row['alternatif_id']; ?>&start_date=<?php echo $start_date; ?>&end_date=<?php echo $end_date; ?>" 
                                           class="btn btn-sm btn-info" title="Detail">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Modal Konfirmasi Hapus - Hanya muncul untuk admin -->
<?php if ($isAdmin): ?>
<div class="modal fade" id="deleteModal" tabindex="-1" aria-labelledby="deleteModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="deleteModalLabel">Konfirmasi Hapus</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>Apakah Anda yakin ingin menghapus semua penilaian untuk alternatif ini pada periode terpilih?</p>
                <p class="text-muted">Periode: <span id="deletePeriod"></span></p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                <a href="#" class="btn btn-danger" id="confirmDeleteBtn">Ya, Hapus</a>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<?php include 'includes/js.php'; ?>

<!-- Script Hapus - Hanya dimuat untuk admin -->
<?php if ($isAdmin): ?>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        var deleteModal = document.getElementById('deleteModal');
        if (deleteModal) {
            deleteModal.addEventListener('show.bs.modal', function(event) {
                var button = event.relatedTarget;
                var id = button.getAttribute('data-id');
                var startDate = button.getAttribute('data-start-date');
                var endDate = button.getAttribute('data-end-date');
                
                // Format tanggal untuk ditampilkan
                var startDateFormatted = new Date(startDate).toLocaleDateString('id-ID', {
                    day: '2-digit',
                    month: '2-digit',
                    year: 'numeric'
                });
                var endDateFormatted = new Date(endDate).toLocaleDateString('id-ID', {
                    day: '2-digit',
                    month: '2-digit',
                    year: 'numeric'
                });
                
                document.getElementById('deletePeriod').textContent = startDateFormatted + ' - ' + endDateFormatted;
                
                var confirmDeleteBtn = document.getElementById('confirmDeleteBtn');
                confirmDeleteBtn.href = 'penilaian-delete.php?alternatif_id=' + id + 
                                        '&start_date=' + encodeURIComponent(startDate) + 
                                        '&end_date=' + encodeURIComponent(endDate);
            });
        }
    });
</script>
<?php endif; ?>

<?php include 'includes/footer.php'; ?>