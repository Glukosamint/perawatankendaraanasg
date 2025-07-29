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

$stmt = $pdo->query("SELECT * FROM pengguna");
$pengguna = $stmt->fetchAll();

include 'includes/header.php';
?>

<div class="container">
    <div class="card text-dark bg-light">
        <div class="card-header">
            Data Pengguna
            <span class="badge bg-primary float-end">Role: <?php echo strtoupper($currentUserRole); ?></span>
        </div>
        <div class="card-body">
            <!-- Hanya admin yang bisa tambah pengguna -->
            <?php if ($isAdmin): ?>
                <a href="pengguna-create.php" class="btn btn-primary mb-2">Tambah Pengguna</a>
            <?php endif; ?>
            
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>No</th>
                        <th>Nama Lengkap</th>
                        <th>Username</th>
                        <th>Role</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php $no = 1; ?>
                    <?php foreach ($pengguna as $row): ?>
                        <tr>
                            <td><?php echo $no++; ?></td>
                            <td><?php echo htmlspecialchars($row['nama_lengkap']); ?></td>
                            <td><?php echo htmlspecialchars($row['username']); ?></td>
                            <td><?php echo htmlspecialchars($row['role']); ?></td>
                            <td>
                                <!-- Admin bisa edit semua, user hanya edit profil sendiri -->
                                <?php if ($isAdmin || $row['id'] == $_SESSION['pengguna']['id']): ?>
                                    <a href="pengguna-edit.php?id=<?php echo $row['id']; ?>" class="btn btn-sm btn-warning">Ubah</a>
                                <?php endif; ?>

                                <!-- Hanya admin yang bisa hapus dan tidak bisa hapus diri sendiri -->
                                <?php if ($isAdmin && $row['username'] != $_SESSION['pengguna']['username']): ?>
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
        confirmDeleteBtn.href = 'pengguna-delete.php?id=' + id;
    });
</script>
<?php endif; ?>

<?php include 'includes/footer.php'; ?>