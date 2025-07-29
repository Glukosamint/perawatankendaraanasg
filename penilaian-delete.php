<?php
session_start();
include 'config.php';

// Cek login dan role admin
if (!isset($_SESSION['pengguna'])) {
    header("Location: login.php");
    exit;
}

if ($_SESSION['pengguna']['role'] !== 'admin') {
    header("Location: unauthorized.php");
    exit;
}

// Validasi parameter
if (!isset($_GET['alternatif_id']) || !isset($_GET['start_date']) || !isset($_GET['end_date'])) {
    $_SESSION['error'] = "Parameter tidak valid!";
    header("Location: penilaian-list.php");
    exit;
}

$alternatif_id = $_GET['alternatif_id'];
$start_date = $_GET['start_date'];
$end_date = $_GET['end_date'];

try {
    // Mulai transaksi
    $pdo->beginTransaction();
    
    // 1. Hapus data penilaian dalam periode
    $stmt = $pdo->prepare("DELETE FROM penilaian WHERE alternatif_id = ? AND tanggal_penilaian BETWEEN ? AND ?");
    $stmt->execute([$alternatif_id, $start_date, $end_date]);
    
    // 2. Hapus data hasil terkait jika ada
    $stmt = $pdo->prepare("DELETE FROM hasil WHERE alternatif_id = ? AND tanggal_hasil BETWEEN ? AND ?");
    $stmt->execute([$alternatif_id, $start_date, $end_date]);
    
    // 3. Update perhitungan MFEP jika perlu
    // ... (tambahkan kode update perhitungan jika diperlukan)
    
    // Commit transaksi
    $pdo->commit();
    
    $_SESSION['success'] = "Penilaian berhasil dihapus untuk periode " . 
                          date('d/m/Y', strtotime($start_date)) . " - " . 
                          date('d/m/Y', strtotime($end_date));
} catch (PDOException $e) {
    // Rollback jika error
    $pdo->rollBack();
    $_SESSION['error'] = "Gagal menghapus penilaian: " . $e->getMessage();
}

// Redirect kembali ke halaman list
header("Location: penilaian-list.php?start_date=".urlencode($start_date)."&end_date=".urlencode($end_date));
exit;
?>