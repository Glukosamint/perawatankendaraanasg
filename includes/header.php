<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Aplikasi MFEP</title>

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <link rel="shortcut icon" href="assets/img/logo.jpg" type="image/x-icon">
    
    <style>
        .navbar-brand img {
            margin-right: 10px;
        }
        
        /* Perbaikan khusus untuk dropdown di mobile */
        @media (max-width: 991.98px) {
            .navbar-collapse {
                padding-top: 15px;
            }
            .navbar-nav {
                margin-top: 10px;
            }
            .nav-item {
                margin-bottom: 5px;
            }
            .dropdown-menu {
                position: static !important;
                float: none;
                width: 100%;
                margin-top: 0;
                background-color: rgba(0,0,0,0.1);
                border: none;
            }
            .dropdown-item {
                padding: 0.5rem 1.5rem;
                color: rgba(255,255,255,0.8) !important;
            }
            .dropdown-item:hover {
                color: white !important;
                background-color: transparent;
            }
            .dropdown-divider {
                border-color: rgba(255,255,255,0.1);
            }
        }
    </style>
</head>

<body>
    <?php
    // Redirect ke login jika tidak ada session
    if (!isset($_SESSION['pengguna'])) {
        header("Location: login.php");
        exit();
    }
    ?>
    
    <nav class="navbar navbar-expand-lg navbar-dark bg-danger mb-4">
        <div class="container">
            <a class="navbar-brand" href="index.php">
                <img src="assets/img/logo.jpg" alt="Logo" width="30" height="30" class="d-inline-block align-text-top">
                <span class="d-none d-sm-inline">Aplikasi Sistem Pendukung Keputusan MFEP</span>
                <span class="d-inline d-sm-none">SPK MFEP</span>
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" 
                    aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>

            <div class="collapse navbar-collapse" id="navbarNav">
                <?php $current_page = basename($_SERVER['PHP_SELF']); ?>

                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link <?php echo $current_page == 'index.php' ? 'active' : ''; ?>" href="index.php">
                            <i class="fas fa-home"></i> Home
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo $current_page == 'pengguna-list.php' ? 'active' : ''; ?>" href="pengguna-list.php">
                            <i class="fas fa-users"></i> Pengguna
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo $current_page == 'kriteria-list.php' ? 'active' : ''; ?>" href="kriteria-list.php">
                            <i class="fas fa-list-alt"></i> Kriteria
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo $current_page == 'alternatif-list.php' ? 'active' : ''; ?>" href="alternatif-list.php">
                            <i class="fas fa-th-list"></i> Alternatif
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo $current_page == 'penilaian-list.php' ? 'active' : ''; ?>" href="penilaian-list.php">
                            <i class="fas fa-check-square"></i> Penilaian
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo $current_page == 'perhitungan.php' ? 'active' : ''; ?>" href="perhitungan.php">
                            <i class="fas fa-calculator"></i> Perhitungan
                        </a>
                    </li>
                </ul>

                <ul class="navbar-nav">
                    <li class="nav-item">
                        <a class="nav-link d-flex align-items-center" href="logout.php" onclick="return confirm('Yakin ingin logout?')">
                            <i class="fas fa-sign-out-alt me-1"></i> 
                            <span>Logout</span>
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>