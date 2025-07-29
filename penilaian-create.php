<?php
session_start();
include 'config.php';

// Cek login
if (!isset($_SESSION['pengguna'])) {
    header("Location: login.php");
    exit;
}

// Cek role
$currentUserRole = $_SESSION['pengguna']['role'] ?? 'user';
$isAdmin = ($currentUserRole === 'admin');

if (!$isAdmin) {
    header("Location: unauthorized.php");
    exit;
}

$stmt = $pdo->query("SELECT * FROM alternatif");
$alternatif = $stmt->fetchAll();

$stmt = $pdo->query("SELECT * FROM kriteria");
$kriteria = $stmt->fetchAll();

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $alternatif_id = $_POST['alternatif_id'];
    $tanggal_penilaian = $_POST['tanggal_penilaian'];
    $hasZeroValue = false;

    // Validasi nilai kriteria
    foreach ($kriteria as $row) {
        if (isset($_POST['kriteria_' . $row['id']]) && $_POST['kriteria_' . $row['id']] == 0) {
            $hasZeroValue = true;
            break;
        }
    }

    if ($hasZeroValue) {
        $error = "Nilai tidak boleh 0 untuk semua kriteria";
    } else {
        // Cek duplikasi penilaian
        $checkStmt = $pdo->prepare("SELECT COUNT(*) FROM penilaian WHERE alternatif_id = ? AND tanggal_penilaian = ?");
        $checkStmt->execute([$alternatif_id, $tanggal_penilaian]);
        $count = $checkStmt->fetchColumn();

        if ($count > 0) {
            $error = "Penilaian untuk alternatif tersebut pada tanggal ".date('d/m/Y', strtotime($tanggal_penilaian))." sudah ada";
        } else {
            // Mulai transaksi
            $pdo->beginTransaction();
            
            try {
                foreach ($kriteria as $row) {
                    $nilai = $_POST['kriteria_' . $row['id']];
                    
                    $stmt = $pdo->prepare("INSERT INTO penilaian (alternatif_id, kriteria_id, nilai, tanggal_penilaian) VALUES (?, ?, ?, ?)");
                    $stmt->execute([$alternatif_id, $row['id'], $nilai, $tanggal_penilaian]);
                }
                
                $pdo->commit();
                
                $_SESSION['success'] = "Penilaian berhasil disimpan untuk tanggal ".date('d/m/Y', strtotime($tanggal_penilaian));
                echo '<script>
                    alert("Data penilaian berhasil disimpan!");
                    window.location.href = "penilaian-list.php";
                </script>';
                exit;
            } catch (PDOException $e) {
                $pdo->rollBack();
                $error = "Terjadi kesalahan saat menyimpan data: " . $e->getMessage();
                echo '<script>alert("'.addslashes($error).'");</script>';
            }
        }
    }
}

include 'includes/header.php';
?>

<div class="container">
    <div class="card text-dark bg-light">
        <div class="card-header">
            <h4 class="mb-0">Tambah Penilaian</h4>
            <span class="badge bg-primary">Role: <?php echo strtoupper($currentUserRole); ?></span>
        </div>
        <div class="card-body">
            <?php if (isset($_SESSION['success'])): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <?php if (isset($error)): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <?php echo $error; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <form method="post" action="penilaian-create.php" onsubmit="return confirmSubmit()" id="formPenilaian">
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label for="alternatif_id" class="form-label">Alternatif <span class="text-danger">*</span></label>
                        <select name="alternatif_id" id="alternatif_id" class="form-select" required>
                            <option value="">- Pilih Alternatif -</option>
                            <?php foreach ($alternatif as $row): ?>
                                <option value="<?php echo $row['id'] ?>" <?php echo (isset($_POST['alternatif_id']) && $_POST['alternatif_id'] == $row['id'] ? 'selected' : ''); ?>>
                                    <?php echo $row['kode_alternatif'] . ' - ' . $row['nama_alternatif']; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label for="tanggal_penilaian" class="form-label">Tanggal Penilaian <span class="text-danger">*</span></label>
                        <input type="date" class="form-control" name="tanggal_penilaian" id="tanggal_penilaian" 
                               value="<?php echo isset($_POST['tanggal_penilaian']) ? $_POST['tanggal_penilaian'] : date('Y-m-d'); ?>" required>
                    </div>
                </div>

                <div class="card mb-4">
                    <div class="card-header bg-light">
                        <h5 class="mb-0">Nilai Kriteria</h5>
                    </div>
                    <div class="card-body">
                        <?php foreach ($kriteria as $row): ?>
                            <div class="mb-3">
                                <label for="kriteria_<?php echo $row['id'] ?>" class="form-label">
                                    <?php echo $row['kode_kriteria'] . ' - ' . $row['nama_kriteria']; ?>
                                    <?php if ($row['bobot_kriteria'] > 0): ?>
                                        <span class="badge bg-info float-end">Bobot: <?php echo $row['bobot_kriteria']; ?></span>
                                    <?php endif; ?>
                                </label>
                                <input type="number" min="1" max="5" step="1" class="form-control" 
                                    name="kriteria_<?php echo $row['id'] ?>" 
                                    id="kriteria_<?php echo $row['id'] ?>" 
                                    value="<?php echo isset($_POST['kriteria_'.$row['id']]) ? $_POST['kriteria_'.$row['id']] : ''; ?>"
                                    required>
                                <?php if (!empty($row['keterangan'])): ?>
                                    <small class="text-muted"><?php echo $row['keterangan']; ?></small>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                    <button type="submit" class="btn btn-primary me-md-2" id="btnSimpan">
                        <i class="fas fa-save"></i> Simpan
                    </button>
                    <a href="penilaian-list.php" class="btn btn-secondary">
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
    // Ambil nilai form
    const alternatif = document.getElementById('alternatif_id');
    const alternatifText = alternatif.options[alternatif.selectedIndex].text;
    const tanggal = document.getElementById('tanggal_penilaian').value;
    
    // Validasi semua input
    let allValid = true;
    document.querySelectorAll('input[type="number"]').forEach(input => {
        if (!input.value || input.value < 1 || input.value > 5) {
            allValid = false;
        }
    });
    
    if (!allValid) {
        alert('Harap isi semua nilai kriteria dengan angka 1-5!');
        return false;
    }
    
    // Tampilkan konfirmasi
    const confirmation = confirm(`Apakah data penilaian sudah benar?\n\nAlternatif: ${alternatifText}\nTanggal: ${tanggal}\n\nKlik OK untuk menyimpan.`);
    
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