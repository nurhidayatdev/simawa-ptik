<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include 'koneksi.php';

// Proteksi Autentikasi Pengguna
if (!isset($_SESSION['user_id'])) {
    header("Location: index");
    exit();
}

$role = $_SESSION['role'] ?? 'Mahasiswa';

// Central Router: Arahkan setiap role ke dashboard khusus masing-masing
if ($role === 'Administrator Utama') {
    header("Location: dashboard_admin.php");
    exit();
} elseif ($role === 'Ketua Tingkat') {
    header("Location: dashboard_keti.php");
    exit();
} elseif ($role === 'Sekretaris') {
    header("Location: dashboard_sekretaris.php");
    exit();
} elseif ($role === 'Bendahara') {
    header("Location: dashboard_bendahara.php");
    exit();
} else {
    // Role Mahasiswa dan role reguler lainnya
    header("Location: dashboard_mahasiswa.php");
    exit();
}
