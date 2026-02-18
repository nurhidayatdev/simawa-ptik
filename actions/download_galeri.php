<?php
session_start();
include '../koneksi.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../../index.php");
    exit;
}

if (!isset($_GET['id'])) {
    $_SESSION['error'] = "ID Galeri tidak ditemukan.";
    header("Location: ../database/db_galeri.php");
    exit;
}

$galeri_id = intval($_GET['id']);

// Ambil info galeri
$q_galeri = mysqli_query($koneksi, "SELECT judul FROM galeri WHERE id = $galeri_id");
$galeri = mysqli_fetch_assoc($q_galeri);

if (!$galeri) {
    $_SESSION['error'] = "Galeri tidak ditemukan.";
    header("Location: ../database/db_galeri.php");
    exit;
}

// Ambil semua foto dalam galeri ini
$q_foto = mysqli_query($koneksi, "SELECT foto_path FROM galeri_foto WHERE galeri_id = $galeri_id");

if (mysqli_num_rows($q_foto) == 0) {
    $_SESSION['error'] = "Tidak ada foto untuk didownload.";
    header("Location: ../database/db_galeri.php");
    exit;
}

// Gunakan ZipArchive untuk membungkus foto
$filename = "Galeri_" . str_replace(' ', '_', $galeri['judul']) . ".zip";
$filepath = sys_get_temp_dir() . '/' . $filename;

if (class_exists('ZipArchive')) {
    $zip = new ZipArchive();
    if ($zip->open($filepath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== TRUE) {
        exit("Gagal membuat file zip");
    }

    while ($f = mysqli_fetch_assoc($q_foto)) {
        $full_path = "../storage/gallery/" . $f['foto_path'];
        if (file_exists($full_path)) {
            $zip->addFile($full_path, $f['foto_path']);
        }
    }
    $zip->close();
} else {
    // Fallback: Gunakan command line 'tar' (Windows 10+ punya tar built-in)
    $files_to_zip = [];
    while ($f = mysqli_fetch_assoc($q_foto)) {
        $rel_path = "../storage/gallery/" . $f['foto_path'];
        if (file_exists($rel_path)) {
            $files_to_zip[] = escapeshellarg($f['foto_path']);
        }
    }

    if (empty($files_to_zip)) {
        $_SESSION['error'] = "File foto tidak ditemukan di server.";
        header("Location: ../database/db_galeri.php");
        exit;
    }

    $file_list = implode(' ', $files_to_zip);
    $gallery_dir = realpath("../storage/gallery");
    $target_zip = escapeshellarg($filepath);

    // Jalankan perintah tar untuk membuat zip
    // Windows tar (bsdtar) otomatis mendeteksi format .zip dari nama file
    $cmd = "cd /d " . escapeshellarg($gallery_dir) . " && tar -a -c -f $target_zip $file_list";
    shell_exec($cmd);
}

// Stream file zip ke browser
header('Content-Type: application/zip');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Content-Length: ' . filesize($filepath));
readfile($filepath);

// Hapus file temporary
unlink($filepath);
exit;
