<?php
session_start();
include 'config.php';

if (isset($_SESSION['pengguna'])) {
    header("Location: index.php");
    exit;
}

$error = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    
    // Validasi kosong
    if (empty($username) || empty($password)) {
        $error = "Username dan password harus diisi";
    } else {
        $stmt = $pdo->prepare("SELECT * FROM pengguna WHERE username = ?");
        $stmt->execute([$username]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['pengguna'] = $user;
            header("Location: index.php");
            exit;
        } else {
            $error = "Username atau password salah";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
    <link href="assets/css/bootstrap.min.css" rel="stylesheet">
    <link rel="shortcut icon" href="assets/img/logo.jpg" type="image/x-icon">
    <style>
        .is-invalid {
            border-color: #dc3545;
        }
        .invalid-feedback {
            color: #dc3545;
            display: none;
            font-size: 0.875em;
        }
    </style>
</head>

<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-danger mb-4">
        <div class="container">
            <a class="navbar-brand" href="login.php">
                <img src="assets/img/logo.jpg" alt="Logo" width="30" height="30" class="d-inline-block align-text-top">
                Aplikasi Sistem Pendukung Keputusan MFEP
            </a>
        </div>
    </nav>

    <div class="container">
        <div class="row">
            <div class="col-lg-5 m-auto">
                <div class="card text-dark bg-light">
                    <div class="card-header">
                        Login
                    </div>
                    <div class="card-body">

                        <?php if (!empty($error)): ?>
                            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                <?php echo $error; ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                            </div>
                        <?php endif; ?>

                        <form method="post" action="login.php" id="loginForm" novalidate>
                            <div class="mb-3">
                                <label for="username" class="form-label">Username</label>
                                <input type="text" class="form-control" name="username" id="username" required>
                                <div class="invalid-feedback" id="usernameFeedback">
                                    Username harus diisi
                                </div>
                            </div>
                            <div class="mb-3">
                                <label for="password" class="form-label">Password</label>
                                <input type="password" class="form-control" name="password" id="password" required>
                                <div class="invalid-feedback" id="passwordFeedback">
                                    Password harus diisi
                                </div>
                            </div>
                            <div class="d-grid mt-4">
                                <button type="submit" class="btn btn-primary">Login</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <footer class="bg-light text-dark text-center py-2 position-fixed bottom-0 w-100">
        <div class="container">
            <small>&copy; <?php echo date('Y') ?> PT ARTHAWENSAKTI GEMILANG</small>
        </div>
    </footer>

    <script src="assets/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.getElementById('loginForm');
            const usernameInput = document.getElementById('username');
            const passwordInput = document.getElementById('password');
            
            form.addEventListener('submit', function(event) {
                let isValid = true;
                
                // Validasi username
                if (usernameInput.value.trim() === '') {
                    usernameInput.classList.add('is-invalid');
                    document.getElementById('usernameFeedback').style.display = 'block';
                    isValid = false;
                } else {
                    usernameInput.classList.remove('is-invalid');
                    document.getElementById('usernameFeedback').style.display = 'none';
                }
                
                // Validasi password
                if (passwordInput.value.trim() === '') {
                    passwordInput.classList.add('is-invalid');
                    document.getElementById('passwordFeedback').style.display = 'block';
                    isValid = false;
                } else {
                    passwordInput.classList.remove('is-invalid');
                    document.getElementById('passwordFeedback').style.display = 'none';
                }
                
                if (!isValid) {
                    event.preventDefault();
                    event.stopPropagation();
                }
            });
            
            // Reset validasi saat user mulai mengetik
            usernameInput.addEventListener('input', function() {
                if (this.value.trim() !== '') {
                    this.classList.remove('is-invalid');
                    document.getElementById('usernameFeedback').style.display = 'none';
                }
            });
            
            passwordInput.addEventListener('input', function() {
                if (this.value.trim() !== '') {
                    this.classList.remove('is-invalid');
                    document.getElementById('passwordFeedback').style.display = 'none';
                }
            });
        });
    </script>
</body>

</html>