<?php
include '../koneksi.php';

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
        <meta name='description' content='Dashboard Resmi Kelas PTIK C 2024'>
        <meta name='theme-color' content='#1e1b4b' />
        <title>Hapus Data - Kelas PTIK C 2024</title>
        <link rel='icon' href='../assets/image/icon.webp' />
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
                    window.location.href = 'hapus?tabel={$tabel}&id={$id}&confirm=yes';
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

    $tabel_diizinkan = ['mahasiswa', 'mata_kuliah', 'jadwal_matkul', 'users', 'dosen', 'tugas', 'galeri', 'pesan', 'akademik', 'kalender_akademik'];

    if (!in_array($tabel, $tabel_diizinkan)) {
        echo "Tabel tidak diizinkan!";
        exit();
    }

    $hapus = mysqli_query($koneksi, "DELETE FROM $tabel WHERE id='$id'");


    switch ($tabel) {
        case 'mahasiswa':
            $redirect = '../database/db_mahasiswa';
            break;

        case 'mata_kuliah':
            $redirect = '../database/db_matkul';
            break;
        case 'jadwal_matkul':
            $redirect = '../database/db_jadwal';
            break;

        case 'users':
            $redirect = '../database/db_users';
            break;
        case 'dosen':
            $redirect = '../database/db_dosen';
            break;
        case 'tugas':
            $redirect = '../database/db_tugas';
            break;
        case 'galeri':
            $redirect = '../database/db_galeri';
            break;
        case 'pesan':
            $redirect = '../database/db_pesan';
            break;
        case 'akademik':
            $redirect = '../database/db_berita';
            break;
        case 'kalender_akademik':
            $redirect = '../database/db_kalender';
            break;
    }

    if ($hapus) {
        echo <<<HTML
            <!DOCTYPE html>
            <html>
            <head>
                <meta charset='UTF-8'>
                <meta name='viewport' content='width=device-width, initial-scale=1.0'>
                <meta name='theme-color' content='#1e1b4b' />
                <title>Hapus Data - Kelas PTIK C 2024</title>
                <link rel='icon' href='../assets/image/icon.webp' />
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
