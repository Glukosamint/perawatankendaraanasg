<?php
session_start();
include 'config.php';

if (!isset($_SESSION['pengguna'])) {
    header("Location: login.php");
    exit;
}

if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $id = $_GET['id'];

    $stmt = $pdo->prepare("DELETE FROM pengguna WHERE id = ?");
    $stmt->execute([$id]);

    header("Location: pengguna-list.php");
    exit;
} else {
    echo "ID pengguna tidak valid.";
}
