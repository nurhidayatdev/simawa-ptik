<?php
session_start();
include '../koneksi.php';
include '../functions/kirim_kelompok.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../index");
    exit;
}

if ($_SESSION['role'] === 'Mahasiswa' || $_SESSION['role'] === 'Bendahara' || $_SESSION['role'] === 'Sekretaris') {
    $_SESSION['error'] = "Anda tidak memiliki akses untuk membagikan kelompok.";
    header("Location: ../views/kelompok");
    exit();
}

$matkul_id = intval($_GET['matkul_id'] ?? 0);
$dosen = $_GET['dosen'] ?? '';
$keterangan = $_GET['keterangan'] ?? '';

if ($matkul_id && !empty($dosen)) {
    // Call the helper function (it already handles the response formatting)
    $res = kirimKelompokReminder($koneksi, $matkul_id, $dosen, $keterangan, 'new');

    if ($res['status'] === 'success') {
        $_SESSION['success'] = $res['message'];
    } else {
        $_SESSION['error'] = $res['message'];
    }
} else {
    $_SESSION['error'] = "Data tidak lengkap untuk mengirim notifikasi.";
}

header("Location: ../views/kelompok?matkul_id=$matkul_id&dosen=" . urlencode($dosen) . "&keterangan=" . urlencode($keterangan));
exit;
