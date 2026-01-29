<?php
session_start();
include '../koneksi.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Administrator Utama') {
    header("Location: ../index.php");
    exit;
}

if (isset($_GET['id'])) {
    $id = $_GET['id'];
    $default_password = "ptikClass24";

    $stmt = mysqli_prepare($koneksi, "UPDATE users SET password = ? WHERE id = ?");
    mysqli_stmt_bind_param($stmt, "si", $default_password, $id);

    if (mysqli_stmt_execute($stmt)) {
        header("Location: ../database/db_users.php?status=success_reset");
        exit();
    } else {
        echo "Gagal mereset password.";
    }
} else {
    header("Location: ../database/db_users.php");
}
exit();
