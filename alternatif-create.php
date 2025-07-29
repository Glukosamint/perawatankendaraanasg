<?php
session_start();
include 'config.php';

if (!isset($_SESSION['pengguna'])) {
    header("Location: login.php");
    exit;
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $kode_alternatif = htmlspecialchars($_POST['kode_alternatif']);
    $nama_alternatif = htmlspecialchars($_POST['nama_alternatif']);

    try {
        $stmt = $pdo->prepare("INSERT INTO alternatif (kode_alternatif, nama_alternatif) VALUES (?, ?)");
        $stmt->execute([$kode_alternatif, $nama_alternatif]);
        
        echo '<script>
            alert("Data alternatif berhasil disimpan!");
            window.location.href = "alternatif-list.php";
        </script>';
        exit;
    } catch (PDOException $e) {
        $error_message = "Gagal menyimpan data: " . $e->getMessage();
        echo '<script>alert("'.addslashes($error_message).'");</script>';
    }
}

include 'includes/header.php';
?>

<div class="container">
    <div class="card text-dark bg-light">
        <div class="card-header">
            Tambah Alternatif
        </div>
        <div class="card-body">
            <form method="post" action="alternatif-create.php" onsubmit="return confirmSubmit()" id="formAlternatif">
                <div class="mb-3">
                    <label for="kode_alternatif" class="form-label">Kode Alternatif <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" name="kode_alternatif" id="kode_alternatif" required>
                </div>
                <div class="mb-3">
                    <label for="nama_alternatif" class="form-label">Nama Alternatif <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" name="nama_alternatif" id="nama_alternatif" required>
                </div>
                <div class="d-flex justify-content-between">
                    <button type="submit" class="btn btn-primary" id="btnSimpan">
                        <i class="fas fa-save"></i> Simpan
                    </button>
                    <a href="alternatif-list.php" class="btn btn-secondary">
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
    const kode = document.getElementById('kode_alternatif').value;
    const nama = document.getElementById('nama_alternatif').value;
    
    // Validasi input tidak kosong
    if (!kode || !nama) {
        alert('Harap lengkapi semua field yang wajib diisi!');
        return false;
    }
    
    // Tampilkan konfirmasi
    const confirmation = confirm(`Apakah data sudah benar?\n\nKode Alternatif: ${kode}\nNama Alternatif: ${nama}`);
    
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