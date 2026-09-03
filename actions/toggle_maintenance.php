<?php
session_start();
include '../koneksi.php';

// Verification: Must be logged in & role must be Administrator Utama
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'Administrator Utama') {
    $_SESSION['error'] = 'Aksi ditolak. Hanya Administrator Utama yang dapat mengelola Mode Maintenance.';
    header('Location: ../database/db_settings');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $maintenance_mode = isset($_POST['maintenance_mode']) && ($_POST['maintenance_mode'] === '1' || $_POST['maintenance_mode'] === 'on') ? '1' : '0';
    $maintenance_message = trim($_POST['maintenance_message'] ?? '');

    if (empty($maintenance_message)) {
        $maintenance_message = 'Sistem sedang dalam Mode Maintenance (Pemeliharaan). Hanya Administrator Utama yang diizinkan untuk login.';
    }

    $set_mode = set_setting($koneksi, 'maintenance_mode', $maintenance_mode);
    $set_msg  = set_setting($koneksi, 'maintenance_message', $maintenance_message);

    if ($set_mode && $set_msg) {
        if ($maintenance_mode === '1') {
            $_SESSION['success'] = 'Mode Maintenance BERHASIL DIAKTIFKAN. Hanya Administrator Utama yang dapat login.';
        } else {
            $_SESSION['success'] = 'Mode Maintenance BERHASIL DINONAKTIFKAN. Seluruh pengguna dapat login kembali secara normal.';
        }
    } else {
        $_SESSION['error'] = 'Gagal memperbarui pengaturan Mode Maintenance.';
    }
}

header('Location: ../database/db_settings');
exit();
