<?php
session_start();

if (!isset($_SESSION['pengguna'])) {
    header("Location: login.php");
    exit;
}

include 'includes/header.php';
?>

<div class="container">
    <div class="card text-dark bg-light">
        <div class="card-header">
            Selamat Datang, <?php echo $_SESSION['pengguna']['nama_lengkap']; ?>
        </div>
        <div class="card-body">
            <div class="text-center my-4">
                <h3>
                    Aplikasi SPK Menggunakan
                    <br>Metode MFEP
                </h3>

                <small>Oleh: <strong>Doddy Setiawan</strong></small>

                <img src="assets/img/logo.jpg" alt="Logo" width="150" class="d-block mx-auto mt-4">
            </div>
        </div>
    </div>
</div>

<?php include 'includes/js.php'; ?>
<?php include 'includes/footer.php'; ?>