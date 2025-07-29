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

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nama_lengkap = htmlspecialchars($_POST['nama_lengkap']);
    $username = htmlspecialchars($_POST['username']);
    $password = $_POST['password'];
    $role = in_array($_POST['role'], $allowedRoles) ? $_POST['role'] : 'user';
    
    // Validasi panjang karakter
    if (strlen($username) > 50) {
        echo '<script>alert("Username maksimal 50 karakter!");</script>';
    } elseif (strlen($password) > 30) {
        echo '<script>alert("Password maksimal 30 karakter!");</script>';
    } else {
        try {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO pengguna (nama_lengkap, username, password, role) VALUES (?, ?, ?, ?)");
            $stmt->execute([$nama_lengkap, $username, $hashed_password, $role]);
            
            echo '<script>
                alert("Pengguna berhasil ditambahkan!");
                window.location.href = "pengguna-list.php";
            </script>';
            exit;
        } catch (PDOException $e) {
            $error_message = "Gagal menambahkan pengguna: " . $e->getMessage();
            echo '<script>alert("'.addslashes($error_message).'");</script>';
        }
    }
}

include 'includes/header.php';
?>

<div class="container">
    <div class="card text-dark bg-light">
        <div class="card-header">
            <h4>Tambah Pengguna</h4>
        </div>
        <div class="card-body">
            <form method="post" action="pengguna-create.php" id="userForm" novalidate>
                <div class="mb-3">
                    <label for="nama_lengkap" class="form-label">Nama Lengkap <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" name="nama_lengkap" id="nama_lengkap" required>
                    <div class="invalid-feedback">Nama lengkap harus diisi</div>
                </div>
                
                <div class="mb-3">
                    <label for="username" class="form-label">Username <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" name="username" id="username" maxlength="50" required>
                    <div class="invalid-feedback">Username harus diisi</div>
                    <div class="form-text">Maksimal 50 karakter</div>
                </div>
                
                <div class="mb-3">
                    <label for="password" class="form-label">Password <span class="text-danger">*</span></label>
                    <input type="password" class="form-control" name="password" id="password" maxlength="30" required>
                    <div class="invalid-feedback">Password harus diisi</div>
                    <div class="form-text">Minimal 8 karakter dan maksimal 30 karakter</div>
                </div>
                
                <div class="mb-3">
                    <label for="role" class="form-label">Role <span class="text-danger">*</span></label>
                    <select class="form-select" name="role" id="role" required>
                        <option value="">Pilih Role</option>
                        <option value="admin">Admin</option>
                        <option value="user" selected>User</option>
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
    
    // Validasi password
    if (password.value.trim() === '') {
        password.classList.add('is-invalid');
        isValid = false;
    } else if (password.value.length > 30) {
        alert('Password maksimal 30 karakter!');
        return false;
    } else if (password.value.length < 8) {
        alert('Password minimal 8 karakter!');
        return false;
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
    if (this.value.trim() !== '') {
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