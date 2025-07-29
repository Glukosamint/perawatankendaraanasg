<?php
session_start();
include 'includes/header.php';
?>

<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card border-danger">
                <div class="card-header bg-danger text-white">
                    <h4><i class="fas fa-exclamation-triangle"></i> Akses Ditolak</h4>
                </div>
                <div class="card-body text-center py-5">
                    <div class="mb-4">
                        <i class="fas fa-ban fa-5x text-danger"></i>
                    </div>
                    <h3 class="text-danger mb-4">Anda Tidak Memiliki Izin Akses</h3>
                    
                    <div class="alert alert-warning">
                        <p class="mb-0">Halaman ini hanya dapat diakses oleh Administrator.</p>
                    </div>
                    
                    <?php if (isset($_SESSION['pengguna'])): ?>
                        <div class="mb-3">
                            <p>Anda login sebagai: <strong><?php echo htmlspecialchars($_SESSION['pengguna']['nama_lengkap'] ?? 'Unknown'); ?></strong></p>
                            <p>Role: <span class="badge bg-secondary"><?php echo htmlspecialchars($_SESSION['pengguna']['role'] ?? 'guest'); ?></span></p>
                        </div>
                    <?php endif; ?>
                    
                    <div class="d-flex justify-content-center gap-2">
                        <a href="javascript:history.back()" class="btn btn-secondary">
                            <i class="fas fa-arrow-left"></i> Kembali
                        </a>
                        <a href="index.php" class="btn btn-primary">
                            <i class="fas fa-home"></i> Beranda
                        </a>
                        <?php if (!isset($_SESSION['pengguna'])): ?>
                            <a href="login.php" class="btn btn-success">
                                <i class="fas fa-sign-in-alt"></i> Login
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>