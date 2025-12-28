<?php
include '../../koneksi.php';

error_reporting(E_ALL);
ini_set('display_errors', 1);

if (isset($_GET['tabel']) && isset($_GET['id']) && !isset($_GET['confirm'])) {
    $tabel = $_GET['tabel'];
    $id = intval($_GET['id']);

    echo <<<HTML
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset='UTF-8'>
        <meta name='viewport' content='width=device-width, initial-scale=1.0'>
        <title>Dashboard - SD Inpres Maccini Sombala 1</title>
        <link rel='icon' href='../img/main/icon.png' />
        <link rel='preconnect' href='https://fonts.googleapis.com'>
        <link rel='preconnect' href='https://fonts.gstatic.com' crossorigin>
        <link href='https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap' rel='stylesheet'>
        <style>
            body { font-family: 'Poppins', sans-serif !important; }
        </style>
        <script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script>
    </head>
    <body>
        <script>
            Swal.fire({
                title: 'Anda yakin?',
                text: 'Data yang dihapus tidak dapat dikembalikan!',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Ya, hapus',
                cancelButtonText: 'Batal'
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = 'hapus.php?tabel={$tabel}&id={$id}&confirm=yes';
                } else {
                    window.history.back();
                }
            });
        </script>
    </body>
    </html>
    HTML;
    exit();
}


if (isset($_GET['tabel']) && isset($_GET['id']) && isset($_GET['confirm'])) {

    $tabel = $_GET['tabel'];
    $id = intval($_GET['id']);

    $tabel_diizinkan = ['mahasiswa', 'mata_kuliah','jadwal_matkul', 'users', 'dosen', 'tugas', 'galeri', 'pesan', 'akademik', 'kalender_akademik'];

    if (!in_array($tabel, $tabel_diizinkan)) {
        echo "Tabel tidak diizinkan!";
        exit();
    }

    $hapus = mysqli_query($koneksi, "DELETE FROM $tabel WHERE id='$id'");


    switch ($tabel) {
        case 'mahasiswa':
            $redirect = '../dashboard/db_mahasiswa.php';
            break;

            case 'mata_kuliah':
            $redirect = '../dashboard/db_matkul.php';
            break;
            case 'jadwal_matkul':
            $redirect = '../dashboard/db_jadwal.php';
            break;

            case 'users':
            $redirect = '../dashboard/db_users.php';
            break;
            case 'dosen':
            $redirect = '../dashboard/db_dosen.php';
            break;
            case 'tugas':
            $redirect = '../dashboard/db_tugas.php';
            break;
            case 'galeri':
            $redirect = '../dashboard/db_galeri.php';
            break;
            case 'pesan':
            $redirect = '../dashboard/db_pesan.php';
            break;
            case 'akademik':
            $redirect = '../dashboard/db_berita.php';
            break;
            case 'kalender_akademik':
            $redirect = '../dashboard/db_kalender.php';
            break;
    }

    if ($hapus) {
        echo <<<HTML
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset='UTF-8'>
            <meta name='viewport' content='width=device-width, initial-scale=1.0'>
            <title>Dashboard - SD Inpres Maccini Sombala 1</title>
            <link rel='icon' href='../img/main/icon.png' />
            <link rel='preconnect' href='https://fonts.googleapis.com'>
            <link rel='preconnect' href='https://fonts.gstatic.com' crossorigin>
            <link href='https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap' rel='stylesheet'>
            <style>
                body { font-family: 'Poppins', sans-serif !important; }
            </style>
            <script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script>
        </head>
        <body>
            <script>
                Swal.fire({
                    title: 'Berhasil!',
                    text: 'Data berhasil dihapus!',
                    icon: 'success',
                    confirmButtonText: 'OK'
                }).then(() => {
                    window.location.href = '$redirect';
                });
            </script>
        </body>
        </html>
        HTML;
    } else {
        echo "Gagal menghapus data.";
    }

    exit();
}
