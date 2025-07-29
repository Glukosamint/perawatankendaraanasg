<?php
session_start();
include 'config.php';

// Set waktu timeout session (30 menit)
$inactive = 1800; // Dalam detik
if (isset($_SESSION['timeout'])) {
    $session_life = time() - $_SESSION['timeout'];
    if ($session_life > $inactive) {
        session_destroy();
        header("Location: login.php?timeout=1");
        exit;
    }
}
$_SESSION['timeout'] = time();

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

$stmt = $pdo->query("SELECT * FROM kriteria");
$kriteria = $stmt->fetchAll();

// Hitung total bobot
$totalBobot = 0;
foreach ($kriteria as $row) {
    $totalBobot += (float)$row['bobot_kriteria'];
}

include 'includes/header.php';
?>

<div class="container">
    <div class="card text-dark bg-light">
        <div class="card-header">
            Data Kriteria
            <span class="badge bg-primary float-end">Role: <?php echo strtoupper($currentUserRole); ?></span>
        </div>
        <div class="card-body">
    <!-- Hanya admin yang bisa tambah kriteria -->
    <?php if ($isAdmin): ?>
        <a href="kriteria-create.php" class="btn btn-primary mb-2">Tambah Kriteria</a>
    <?php endif; ?>
    <?php
    // Hitung total bobot
$totalBobot = 0;
foreach ($kriteria as $row) {
    $totalBobot += (float)$row['bobot_kriteria'];
}

// Bulatkan total bobot untuk 2 digit desimal untuk perbandingan
$roundedTotal = round($totalBobot, 2);
?>
    
    <!-- Tampilkan total bobot -->
    <div class="alert alert-info mb-3">
    <strong>Total Bobot Kriteria:</strong> 
    <span class="badge bg-primary"><?php echo number_format($totalBobot, 2); ?></span>
    
    <?php if ($roundedTotal > 1.0): ?>
        <span class="badge bg-danger ms-2">Total bobot melebihi 1.0</span>
    <?php elseif ($roundedTotal == 1.0): ?>
        <span class="badge bg-success ms-2">Total bobot valid</span>
    <?php else: ?>
        <span class="badge bg-warning ms-2">Total bobot kurang dari 1.0</span>
    <?php endif; ?>
</div>
    
    <table class="table table-hover">
                <thead>
                    <tr>
                        <th>No</th>
                        <th>Kode Kriteria</th>
                        <th>Nama Kriteria</th>
                        <th>Bobot Kriteria</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php $no = 1; ?>
                    <?php foreach ($kriteria as $row): ?>
                        <tr>
                            <td><?php echo $no++; ?></td>
                            <td><?php echo htmlspecialchars($row['kode_kriteria']); ?></td>
                            <td><?php echo htmlspecialchars($row['nama_kriteria']); ?></td>
                            <td><?php echo htmlspecialchars($row['bobot_kriteria']); ?></td>
                            <td>
                                <!-- Hanya admin yang bisa edit -->
                                <?php if ($isAdmin): ?>
                                    <a href="kriteria-edit.php?id=<?php echo $row['id']; ?>" class="btn btn-sm btn-warning">Ubah</a>
                                <?php endif; ?>

                                <!-- Hanya admin yang bisa hapus -->
                                <?php if ($isAdmin): ?>
                                    <button class="btn btn-sm btn-danger" data-bs-toggle="modal" data-bs-target="#deleteModal" data-id="<?php echo $row['id']; ?>">Hapus</button>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
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
                Apakah Anda yakin ingin menghapus data ini?
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                <a href="#" class="btn btn-danger" id="confirmDeleteBtn">Hapus</a>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<?php include 'includes/js.php'; ?>

<!-- Script Hapus - Hanya dimuat untuk admin -->
<?php if ($isAdmin): ?>
<script>
    // Script untuk mengubah href pada tombol konfirmasi hapus
    var deleteModal = document.getElementById('deleteModal');
    deleteModal.addEventListener('show.bs.modal', function(event) {
        var button = event.relatedTarget;
        var id = button.getAttribute('data-id');
        var confirmDeleteBtn = document.getElementById('confirmDeleteBtn');
        confirmDeleteBtn.href = 'kriteria-delete.php?id=' + id;
    });
</script>
<?php endif; ?>

<?php include 'includes/footer.php'; ?>