<?php
session_start();
include 'config.php';

if (!isset($_SESSION['pengguna'])) {
    header("Location: login.php");
    exit;
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $kode_kriteria = $_POST['kode_kriteria'];
    $nama_kriteria = $_POST['nama_kriteria'];
    $bobot_kriteria = $_POST['bobot_kriteria'];

    $stmt = $pdo->prepare("INSERT INTO kriteria (kode_kriteria, nama_kriteria, bobot_kriteria) VALUES (?, ?, ?)");
    $stmt->execute([$kode_kriteria, $nama_kriteria, $bobot_kriteria]);

    // Set variabel untuk notifikasi
    $success_message = "Data kriteria berhasil ditambahkan!";
}

include 'includes/header.php';
?>

<div class="container">
    <div class="card text-dark bg-light">
        <div class="card-header">
            Tambah Kriteria
        </div>
        <div class="card-body">
            <!-- Tempat untuk notifikasi -->
            <div id="notification" class="alert alert-success alert-dismissible fade show" role="alert" style="display: none;">
                <span id="notification-message"></span>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
            
            <form method="post" action="kriteria-create.php" id="form-kriteria">
                <div class="mb-3">
                    <label for="kode_kriteria" class="form-label">Kode Kriteria</label>
                    <input type="text" class="form-control" name="kode_kriteria" id="kode_kriteria" required>
                </div>
                <div class="mb-3">
                    <label for="nama_kriteria" class="form-label">Nama Kriteria</label>
                    <input type="text" class="form-control" name="nama_kriteria" id="nama_kriteria" required>
                </div>
                <div class="mb-3">
                    <label for="bobot_kriteria" class="form-label">Bobot Kriteria</label>
                    <input type="number" min="0.01" max="1" step="0.01" class="form-control" 
                    name="bobot_kriteria" id="bobot_kriteria" required>
                    <small class="text-muted">Nilai harus antara 0.01 sampai 1</small>
                </div>
                <button type="submit" class="btn btn-primary">Simpan</button>
                <a href="kriteria-list.php" class="btn btn-secondary">Batal</a>
            </form>
        </div>
    </div>
</div>

<script>
// Menangani submit form
document.getElementById('form-kriteria').addEventListener('submit', function(e) {
    e.preventDefault();
    
    // Kirim data form via AJAX
    fetch(this.action, {
        method: this.method,
        body: new FormData(this)
    })
    .then(response => response.text())
    .then(() => {
        // Tampilkan notifikasi
        const notification = document.getElementById('notification');
        const message = document.getElementById('notification-message');
        
        message.textContent = "Data kriteria berhasil ditambahkan!";
        notification.style.display = 'block';
        
        // Reset form
        this.reset();
        
        // Sembunyikan notifikasi setelah 2 detik
        setTimeout(() => {
            notification.style.display = 'none';
        }, 2000);
        setTimeout(() => {
            window.location.href = 'kriteria-list.php';
        }, 3000); // Redirect setelah 3 detik
    })
    .catch(error => {
        console.error('Error:', error);
    });
});
</script>

<?php include 'includes/js.php'; ?>
<?php include 'includes/footer.php'; ?>