<?php
include '../koneksi.php';

error_reporting(E_ALL);
ini_set('display_errors', 1);

// Handle jam_kuliah dengan jam_ke sebagai identifier
if (isset($_GET['tabel']) && $_GET['tabel'] === 'jam_kuliah' && isset($_GET['jam_ke']) && !isset($_GET['confirm'])) {
    $tabel = 'jam_kuliah';
    $jam_ke = intval($_GET['jam_ke']);

    echo <<<HTML
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset='UTF-8'>
        <meta name='viewport' content='width=device-width, initial-scale=1.0'>
        <meta name='description' content='Dashboard Resmi SIMAWA PTIK 2024'>
        <meta name='theme-color' content='#1e1b4b' />
        <title>Hapus Data - SIMAWA PTIK 2024</title>
        <link rel='icon' href='../assets/image/icon.webp' />
        <link rel='preconnect' href='https://fonts.googleapis.com'>
        <link rel='preconnect' href='https://fonts.gstatic.com' crossorigin>
        <link href='https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap' rel='stylesheet'>
        <style>
            html, body {
                background-color: #f8fafc;
            }
            body { 
                font-family: 'Poppins', sans-serif !important; 
                transition: background-color 0.3s;
            }
            .dark html, .dark body {
                background-color: #020617;
                color: #f1f5f9;
            }
        </style>
        <script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script>
    <script>
        (function() {
            const theme = localStorage.getItem('theme') || 'light';
            const isDark = theme === 'dark' || (theme === 'system' && window.matchMedia('(prefers-color-scheme: dark)').matches);
            if (isDark) {
                document.documentElement.classList.add('dark');
            } else {
                document.documentElement.classList.remove('dark');
            }
        })();
    </script>
    </head>
    <body>
        <script>
            const isDarkMode = document.documentElement.classList.contains('dark');
            Swal.fire({
                title: 'Anda yakin?',
                text: 'Data yang dihapus tidak dapat dikembalikan!',
                icon: 'warning',
                showCancelButton: true,
                background: isDarkMode ? '#0f172a' : '#fff',
                color: isDarkMode ? '#f1f5f9' : '#1e293b',
                confirmButtonColor: '#4f46e5',
                cancelButtonColor: isDarkMode ? '#1e293b' : '#94a3b8',
                confirmButtonText: 'Ya, hapus',
                cancelButtonText: 'Batal'
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = 'hapus?tabel={$tabel}&jam_ke={$jam_ke}&confirm=yes';
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

// Handle jam_kuliah deletion dengan confirm
if (isset($_GET['tabel']) && $_GET['tabel'] === 'jam_kuliah' && isset($_GET['jam_ke']) && isset($_GET['confirm'])) {
    $jam_ke = intval($_GET['jam_ke']);
    $hapus = mysqli_query($koneksi, "DELETE FROM jam_kuliah WHERE jam_ke='$jam_ke'");
    $redirect = '../database/db_jam';

    if ($hapus) {
        echo <<<HTML
            <!DOCTYPE html>
            <html>
            <head>
                <meta charset='UTF-8'>
                <meta name='viewport' content='width=device-width, initial-scale=1.0'>
                <meta name='theme-color' content='#1e1b4b' />
                <title>Hapus Data - SIMAWA PTIK 2024</title>
                <link rel='icon' href='../assets/image/icon.webp' />
                <link rel='preconnect' href='https://fonts.googleapis.com'>
                <link rel='preconnect' href='https://fonts.gstatic.com' crossorigin>
                <link href='https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap' rel='stylesheet'>
                <style>
                    html, body {
                        background-color: #f8fafc;
                    }
                    body { 
                        font-family: 'Poppins', sans-serif !important; 
                        transition: background-color 0.3s;
                    }
                    .dark html, .dark body {
                        background-color: #020617;
                        color: #f1f5f9;
                    }
                </style>
                <script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script>
                <script>
                    (function() {
                        const theme = localStorage.getItem('theme') || 'light';
                        const isDark = theme === 'dark' || (theme === 'system' && window.matchMedia('(prefers-color-scheme: dark)').matches);
                        if (isDark) {
                            document.documentElement.classList.add('dark');
                        } else {
                            document.documentElement.classList.remove('dark');
                        }
                    })();
                </script>
            </head>
            <body>
                <script>
                    const isDarkMode = document.documentElement.classList.contains('dark');
                    Swal.fire({
                        title: 'Berhasil!',
                        text: 'Data berhasil dihapus!',
                        icon: 'success',
                        background: isDarkMode ? '#0f172a' : '#fff',
                        color: isDarkMode ? '#f1f5f9' : '#1e293b',
                        confirmButtonColor: '#4f46e5',
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

if (isset($_GET['tabel']) && isset($_GET['id']) && !isset($_GET['confirm'])) {
    $tabel = $_GET['tabel'];
    $id = intval($_GET['id']);

    echo <<<HTML
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset='UTF-8'>
        <meta name='viewport' content='width=device-width, initial-scale=1.0'>
        <meta name='description' content='Dashboard Resmi SIMAWA PTIK 2024'>
        <meta name='theme-color' content='#1e1b4b' />
        <title>Hapus Data - SIMAWA PTIK 2024</title>
        <link rel='icon' href='../assets/image/icon.webp' />
        <link rel='preconnect' href='https://fonts.googleapis.com'>
        <link rel='preconnect' href='https://fonts.gstatic.com' crossorigin>
        <link href='https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap' rel='stylesheet'>
        <style>
            html, body {
                background-color: #f8fafc;
            }
            body { 
                font-family: 'Poppins', sans-serif !important; 
                transition: background-color 0.3s;
            }
            .dark html, .dark body {
                background-color: #020617;
                color: #f1f5f9;
            }
        </style>
        <script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script>
    <script>
        (function() {
            const theme = localStorage.getItem('theme') || 'light';
            const isDark = theme === 'dark' || (theme === 'system' && window.matchMedia('(prefers-color-scheme: dark)').matches);
            if (isDark) {
                document.documentElement.classList.add('dark');
            } else {
                document.documentElement.classList.remove('dark');
            }
        })();
    </script>
    </head>
    <body>
        <script>
            const isDarkMode = document.documentElement.classList.contains('dark');
            Swal.fire({
                title: 'Anda yakin?',
                text: 'Data yang dihapus tidak dapat dikembalikan!',
                icon: 'warning',
                showCancelButton: true,
                background: isDarkMode ? '#0f172a' : '#fff',
                color: isDarkMode ? '#f1f5f9' : '#1e293b',
                confirmButtonColor: '#4f46e5',
                cancelButtonColor: isDarkMode ? '#1e293b' : '#94a3b8',
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

// Handle hapus satu set kelompok
if (isset($_GET['tabel']) && $_GET['tabel'] === 'set_kelompok' && !isset($_GET['confirm'])) {
    $tabel = 'set_kelompok';
    $matkul_id = intval($_GET['matkul_id']);
    $dosen = mysqli_real_escape_string($koneksi, $_GET['dosen']);
    $keterangan = mysqli_real_escape_string($koneksi, $_GET['keterangan']);

    echo <<<HTML
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset='UTF-8'>
        <meta name='viewport' content='width=device-width, initial-scale=1.0'>
        <title>Hapus Set Kelompok - SIMAWA PTIK 2024</title>
        <link rel='icon' href='../assets/image/icon.webp' />
        <link href='https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap' rel='stylesheet'>
        <style>
            html, body {
                background-color: #f8fafc;
            }
            body { 
                font-family: 'Poppins', sans-serif !important; 
            }
            .dark html, .dark body {
                background-color: #020617;
                color: #f1f5f9;
            }
        </style>
        <script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script>
        <script>
            (function() {
                const theme = localStorage.getItem('theme') || 'light';
                const isDark = theme === 'dark' || (theme === 'system' && window.matchMedia('(prefers-color-scheme: dark)').matches);
                if (isDark) document.documentElement.classList.add('dark');
            })();
        </script>
    </head>
    <body>
        <script>
            const isDarkMode = document.documentElement.classList.contains('dark');
            Swal.fire({
                title: 'Hapus Satu Set?',
                text: 'Seluruh kelompok dalam matkul, dosen, dan keterangan yang sama akan dihapus!',
                icon: 'warning',
                showCancelButton: true,
                background: isDarkMode ? '#0f172a' : '#fff',
                color: isDarkMode ? '#f1f5f9' : '#1e293b',
                confirmButtonColor: '#ef4444',
                cancelButtonColor: isDarkMode ? '#1e293b' : '#94a3b8',
                confirmButtonText: 'Ya, hapus semua',
                cancelButtonText: 'Batal'
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = 'hapus?tabel=set_kelompok&matkul_id={$matkul_id}&dosen={$dosen}&keterangan={$keterangan}&confirm=yes';
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

// Logic untuk hapus satu set kelompok (confirm)
if (isset($_GET['tabel']) && $_GET['tabel'] === 'set_kelompok' && isset($_GET['confirm'])) {
    $matkul_id = intval($_GET['matkul_id']);
    $dosen = mysqli_real_escape_string($koneksi, $_GET['dosen']);
    $keterangan = mysqli_real_escape_string($koneksi, $_GET['keterangan']);
    
    // Ambil periode aktif untuk memastikan kita hanya hapus di periode sekarang
    $q_p = mysqli_query($koneksi, "SELECT id FROM periode WHERE status = 'aktif' LIMIT 1");
    $p = mysqli_fetch_assoc($q_p);
    $p_id = $p['id'] ?? 0;

    $q_chk = mysqli_query($koneksi, "SELECT kelas_id FROM kelompok WHERE matkul_id='$matkul_id' AND dosen='$dosen' AND keterangan='$keterangan' AND periode_id='$p_id' LIMIT 1");
    if ($r_chk = mysqli_fetch_assoc($q_chk)) {
        if (!empty($r_chk['kelas_id'])) {
            requireKelasAccess($r_chk['kelas_id']);
        }
    }
    $where_k_del = getKelasWhereClause('', true);
    $hapus = mysqli_query($koneksi, "DELETE FROM kelompok WHERE matkul_id='$matkul_id' AND dosen='$dosen' AND keterangan='$keterangan' AND periode_id='$p_id' $where_k_del");
    $redirect = '../database/db_kelompok';

    if ($hapus) {
        echo <<<HTML
            <!DOCTYPE html>
            <html>
            <head>
                <meta charset='UTF-8'>
                <meta name='viewport' content='width=device-width, initial-scale=1.0'>
                <script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script>
                <script>
                    (function() {
                        const theme = localStorage.getItem('theme') || 'light';
                        if (theme === 'dark' || (theme === 'system' && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
                            document.documentElement.classList.add('dark');
                        }
                    })();
                </script>
                <style>
                    .dark body { background-color: #020617; color: #f1f5f9; }
                </style>
            </head>
            <body>
                <script>
                    const isDark = document.documentElement.classList.contains('dark');
                    Swal.fire({
                        title: 'Berhasil!',
                        text: 'Satu set kelompok berhasil dihapus!',
                        icon: 'success',
                        background: isDark ? '#0f172a' : '#fff',
                        color: isDark ? '#f1f5f9' : '#1e293b',
                        confirmButtonColor: '#4f46e5'
                    }).then(() => {
                        window.location.href = '$redirect';
                    });
                </script>
            </body>
            </html>
        HTML;
    } else {
        echo "Gagal menghapus data set.";
    }
    exit();
}


if (isset($_GET['tabel']) && isset($_GET['id']) && isset($_GET['confirm'])) {

    $tabel = $_GET['tabel'];
    $id = intval($_GET['id']);

    $tabel_diizinkan = ['mahasiswa', 'mata_kuliah', 'jadwal_matkul', 'users', 'dosen', 'tugas', 'akademik', 'kalender_akademik', 'ruangan', 'kelompok'];

    if (!in_array($tabel, $tabel_diizinkan)) {
        echo "Tabel tidak diizinkan!";
        exit();
    }

    if (in_array($tabel, ['mahasiswa', 'jadwal_matkul', 'users', 'tugas', 'kelompok'])) {
        $q_chk = mysqli_query($koneksi, "SELECT kelas_id FROM $tabel WHERE id = $id LIMIT 1");
        if ($r_chk = mysqli_fetch_assoc($q_chk)) {
            if (!empty($r_chk['kelas_id'])) {
                requireKelasAccess($r_chk['kelas_id']);
            }
        }
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
            break;
            break;
        case 'akademik':
            $redirect = '../database/db_berita';
            break;
        case 'kalender_akademik':
            $redirect = '../database/db_kalender';
            break;
        case 'ruangan':
            $redirect = '../database/db_ruangan';
            break;
            break;
        case 'kelompok':
            $redirect = '../database/db_kelompok';
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
                <title>Hapus Data - SIMAWA PTIK 2024</title>
                <link rel='icon' href='../assets/image/icon.webp' />
                <link rel='preconnect' href='https://fonts.googleapis.com'>
                <link rel='preconnect' href='https://fonts.gstatic.com' crossorigin>
                <link href='https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap' rel='stylesheet'>
                <style>
                    html, body {
                        background-color: #f8fafc;
                    }
                    body { 
                        font-family: 'Poppins', sans-serif !important; 
                        transition: background-color 0.3s;
                    }
                    .dark html, .dark body {
                        background-color: #020617;
                        color: #f1f5f9;
                    }
                </style>
                <script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script>
                <script>
                    (function() {
                        const theme = localStorage.getItem('theme') || 'light';
                        const isDark = theme === 'dark' || (theme === 'system' && window.matchMedia('(prefers-color-scheme: dark)').matches);
                        if (isDark) {
                            document.documentElement.classList.add('dark');
                        } else {
                            document.documentElement.classList.remove('dark');
                        }
                    })();
                </script>
            </head>
            <body>
                <script>
                    const isDarkMode = document.documentElement.classList.contains('dark');
                    Swal.fire({
                        title: 'Berhasil!',
                        text: 'Data berhasil dihapus!',
                        icon: 'success',
                        background: isDarkMode ? '#0f172a' : '#fff',
                        color: isDarkMode ? '#f1f5f9' : '#1e293b',
                        confirmButtonColor: '#4f46e5',
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
