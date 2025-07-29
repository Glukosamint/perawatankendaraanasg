<?php
session_start();
include 'config.php';

// Cek login dan role admin
if (!isset($_SESSION['pengguna']) || ($_SESSION['pengguna']['role'] ?? 'user') !== 'admin') {
    header("Location: unauthorized.php");
    exit;
}

// Daftar role yang diizinkan
$allowedRoles = ['admin', 'user'];

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: pengguna-list.php");
    exit;
}

$id = $_GET['id'];

// Ambil data pengguna
$stmt = $pdo->prepare("SELECT * FROM pengguna WHERE id = ?");
$stmt->execute([$id]);
$pengguna = $stmt->fetch();

if (!$pengguna) {
    header("Location: pengguna-list.php");
    exit;
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nama_lengkap = htmlspecialchars($_POST['nama_lengkap']);
    $username = htmlspecialchars($_POST['username']);
    $password = $_POST['password'];
    $role = in_array($_POST['role'], $allowedRoles) ? $_POST['role'] : 'user';
    
    // Validasi panjang karakter
    if (strlen($username) > 50) {
        echo '<script>alert("Username maksimal 50 karakter!");</script>';
    } elseif (!empty($password) && (strlen($password) < 8 || strlen($password) > 30)) {
        echo '<script>alert("Password harus 8-30 karakter!");</script>';
    } else {
        try {
            $password_update = !empty($password) ? password_hash($password, PASSWORD_DEFAULT) : $pengguna['password'];
            $stmt = $pdo->prepare("UPDATE pengguna SET nama_lengkap = ?, username = ?, password = ?, role = ? WHERE id = ?");
            $stmt->execute([$nama_lengkap, $username, $password_update, $role, $id]);
            
            echo '<script>
                alert("Data pengguna berhasil diperbarui!");
                window.location.href = "pengguna-list.php";
            </script>';
            exit;
        } catch (PDOException $e) {
            $error_message = "Gagal memperbarui pengguna: " . $e->getMessage();
            echo '<script>alert("'.addslashes($error_message).'");</script>';
        }
    }
}

include 'includes/header.php';
?>

<div class="container">
    <div class="card text-dark bg-light">
        <div class="card-header">
            <h4>Ubah Pengguna</h4>
        </div>
        <div class="card-body">
            <form method="post" action="pengguna-edit.php?id=<?php echo $id; ?>" id="userForm" novalidate>
                <div class="mb-3">
                    <label for="nama_lengkap" class="form-label">Nama Lengkap <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" name="nama_lengkap" id="nama_lengkap" 
                           value="<?php echo htmlspecialchars($pengguna['nama_lengkap']); ?>" required>
                    <div class="invalid-feedback">Nama lengkap harus diisi</div>
                </div>
                
                <div class="mb-3">
                    <label for="username" class="form-label">Username <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" name="username" id="username" 
                           value="<?php echo htmlspecialchars($pengguna['username']); ?>" maxlength="50" required>
                    <div class="invalid-feedback">Username harus diisi</div>
                    <div class="form-text">Maksimal 50 karakter</div>
                </div>
                
                <div class="mb-3">
                    <label for="password" class="form-label">Password</label>
                    <input type="password" class="form-control" name="password" id="password" maxlength="30">
                    <div class="invalid-feedback">Password harus 8-30 karakter jika diisi</div>
                    <div class="form-text">Kosongkan jika tidak ingin mengubah password (minimal 8 karakter, maksimal 30 karakter)</div>
                </div>
                
                <div class="mb-3">
                    <label for="role" class="form-label">Role <span class="text-danger">*</span></label>
                    <select class="form-select" name="role" id="role" required>
                        <option value="admin" <?php echo ($pengguna['role'] === 'admin') ? 'selected' : ''; ?>>Admin</option>
                        <option value="user" <?php echo ($pengguna['role'] === 'user') ? 'selected' : ''; ?>>User</option>
                    </select>
                    <div class="invalid-feedback">Role harus dipilih</div>
                </div>
                
                <div class="d-flex justify-content-between">
                    <button type="submit" class="btn btn-primary" id="saveButton">
                        <i class="fas fa-save"></i> Simpan
                    </button>
                    <a href="pengguna-list.php" class="btn btn-secondary">
                        <i class="fas fa-times"></i> Batal
                    </a>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
    .is-invalid {
        border-color: #dc3545;
    }
    .invalid-feedback {
        color: #dc3545;
        display: none;
        font-size: 0.875em;
    }
    .was-validated .form-control:invalid ~ .invalid-feedback,
    .was-validated .form-select:invalid ~ .invalid-feedback {
        display: block;
    }
</style>

<script>
// Fungsi untuk validasi form
function validateForm() {
    const form = document.getElementById('userForm');
    const nama = document.getElementById('nama_lengkap');
    const username = document.getElementById('username');
    const password = document.getElementById('password');
    const role = document.getElementById('role');
    
    // Reset validasi
    form.classList.remove('was-validated');
    [nama, username, password, role].forEach(field => {
        field.classList.remove('is-invalid');
    });
    
    let isValid = true;
    
    // Validasi nama lengkap
    if (nama.value.trim() === '') {
        nama.classList.add('is-invalid');
        isValid = false;
    }
    
    // Validasi username
    if (username.value.trim() === '') {
        username.classList.add('is-invalid');
        isValid = false;
    } else if (username.value.length > 50) {
        alert('Username maksimal 50 karakter!');
        return false;
    }
    
    // Validasi password (jika diisi)
    if (password.value.trim() !== '' && (password.value.length < 8 || password.value.length > 30)) {
        password.classList.add('is-invalid');
        isValid = false;
    }
    
    // Validasi role
    if (role.value === '') {
        role.classList.add('is-invalid');
        isValid = false;
    }
    
    if (!isValid) {
        form.classList.add('was-validated');
        return false;
    }
    
    showLoading();
    return true;
}

// Fungsi untuk menampilkan loading saat form disubmit
function showLoading() {
    const saveButton = document.getElementById('saveButton');
    saveButton.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Menyimpan...';
    saveButton.disabled = true;
}

// Event listener untuk form submission
document.getElementById('userForm').addEventListener('submit', function(event) {
    if (!validateForm()) {
        event.preventDefault();
        event.stopPropagation();
    }
});

// Real-time validation saat user mengisi field
document.getElementById('nama_lengkap').addEventListener('input', function() {
    if (this.value.trim() !== '') {
        this.classList.remove('is-invalid');
    }
});

document.getElementById('username').addEventListener('input', function() {
    if (this.value.trim() !== '') {
        this.classList.remove('is-invalid');
    }
});

document.getElementById('password').addEventListener('input', function() {
    if (this.value.trim() === '' || (this.value.length >= 8 && this.value.length <= 30)) {
        this.classList.remove('is-invalid');
    }
});

document.getElementById('role').addEventListener('change', function() {
    if (this.value !== '') {
        this.classList.remove('is-invalid');
    }
});
</script>

<?php include 'includes/js.php'; ?>
<?php include 'includes/footer.php'; ?>