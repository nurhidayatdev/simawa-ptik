<?php
session_start();
include '../../koneksi.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../../index.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$nama_lengkap = $_SESSION['nama_lengkap'] ?? '';
$foto = $_SESSION['foto'] ?? '';
$role = $_SESSION['role'];

if ($_SESSION['role'] === 'Mahasiswa' || $_SESSION['role'] === 'Ketua Tingkat' || $_SESSION['role'] === 'Sekretaris') {
    // Kita tetap butuh HTML dikit untuk menjalankan JS
    echo "<!DOCTYPE html>
    <html>
    <head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <meta name='theme-color' content='#1e1b4b' />
    <link rel='icon' href='../img/main/icon.png' />
    <title>Arus Kas - PTIK C 2024</title>
    <!-- Google tag (gtag.js) -->
    <script async src=\"https://www.googletagmanager.com/gtag/js?id=G-Q0TXETXV6Y\"></script>
    <script>
        window.dataLayer = window.dataLayer || [];

        function gtag() {
            dataLayer.push(arguments);
        }
        gtag('js', new Date());

        gtag('config', 'G-Q0TXETXV6Y');
    </script>
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
                icon: 'error',
                title: 'Akses Ditolak!',
                text: 'Hanya Admin yang dapat mengakses halaman ini.',
                confirmButtonColor: '#3085d6',
                confirmButtonText: 'Kembali'
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = '../../index.php';
                } else {
                    window.location.href = '../../index.php';
                }
            });
        </script>
    </body>
    </html>";
    exit();
}

// handle add entry
// handle update entry
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_arus'])) {
    $id = (int)($_POST['id'] ?? 0);
    $tipe = $_POST['tipe'] === 'keluar' ? 'keluar' : 'masuk';
    $deskripsi = mysqli_real_escape_string($koneksi, $_POST['deskripsi'] ?? '');
    $jumlah = str_replace(',', '.', $_POST['jumlah'] ?? '0');
    $jumlah = (float)$jumlah;
    $tanggal = $_POST['tanggal'] ?? date('Y-m-d');

    $stmt = mysqli_prepare($koneksi, "UPDATE arus_kas SET tipe=?, deskripsi=?, jumlah=?, tanggal=? WHERE id=?");
    mysqli_stmt_bind_param($stmt, 'ssdsi', $tipe, $deskripsi, $jumlah, $tanggal, $id);
    mysqli_stmt_execute($stmt);
    header("Location: arus_kas.php");
    exit;
}

// handle add entry
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['tambah_arus'])) {
    $tipe = $_POST['tipe'] === 'keluar' ? 'keluar' : 'masuk';
    $deskripsi = mysqli_real_escape_string($koneksi, $_POST['deskripsi'] ?? '');
    $jumlah = str_replace(',', '.', $_POST['jumlah'] ?? '0');
    $jumlah = (float)$jumlah;
    $tanggal = $_POST['tanggal'] ?? date('Y-m-d');

    $stmt = mysqli_prepare($koneksi, "INSERT INTO arus_kas (tipe, deskripsi, jumlah, tanggal) VALUES (?, ?, ?, ?)");
    mysqli_stmt_bind_param($stmt, 'ssds', $tipe, $deskripsi, $jumlah, $tanggal);
    mysqli_stmt_execute($stmt);
    header("Location: arus_kas.php");
    exit;
}

// handle delete
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    mysqli_query($koneksi, "DELETE FROM arus_kas WHERE id=$id");
    header("Location: arus_kas.php");
    exit;
}

// totals
// pemasukan dari arus_kas (other)
$res_other_in = mysqli_query($koneksi, "SELECT COALESCE(SUM(jumlah),0) AS total_other_masuk FROM arus_kas WHERE tipe='masuk'");
$total_other_masuk = ($res_other_in && $r = mysqli_fetch_assoc($res_other_in)) ? $r['total_other_masuk'] : 0;

// pemasukan dari tabel kas (nominal per pembayaran) - hanya yang sudah Lunas
$res_kas = mysqli_query($koneksi, "SELECT COALESCE(SUM(nominal),0) AS total_kas FROM kas WHERE status='Lunas'");
$total_kas = ($res_kas && $r2 = mysqli_fetch_assoc($res_kas)) ? $r2['total_kas'] : 0;

$total_masuk = $total_kas + $total_other_masuk;

$res_out = mysqli_query($koneksi, "SELECT COALESCE(SUM(jumlah),0) AS total_keluar FROM arus_kas WHERE tipe='keluar'");
$total_keluar = ($res_out && $r = mysqli_fetch_assoc($res_out)) ? $r['total_keluar'] : 0;
$saldo = $total_masuk - $total_keluar;


// optional edit fetch
$editing = false;
$form_tipe = 'masuk';
$form_deskripsi = '';
$form_jumlah = '';
$form_tanggal = date('Y-m-d');
if (isset($_GET['action']) && $_GET['action'] === 'edit' && isset($_GET['id'])) {
    $eid = (int)$_GET['id'];
    $res_edit = mysqli_query($koneksi, "SELECT * FROM arus_kas WHERE id={$eid} LIMIT 1");
    if ($res_edit && $row_edit = mysqli_fetch_assoc($res_edit)) {
        $editing = true;
        $form_tipe = $row_edit['tipe'];
        $form_deskripsi = $row_edit['deskripsi'];
        $form_jumlah = $row_edit['jumlah'];
        $form_tanggal = !empty($row_edit['tanggal']) ? $row_edit['tanggal'] : $form_tanggal;
    }
}

// list entries
$entries = mysqli_query($koneksi, "SELECT * FROM arus_kas ORDER BY tanggal DESC, created_at DESC");

// handle export to CSV
if (isset($_GET['action']) && $_GET['action'] === 'export_csv') {
    // send headers to force download
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=arus_kas_' . date('Ymd_His') . '.csv');

    $output = fopen('php://output', 'w');
    // header row (omit created_at)
    fputcsv($output, ['ID', 'Tipe', 'Deskripsi', 'Jumlah', 'Tanggal']);

    $q = mysqli_query($koneksi, "SELECT * FROM arus_kas ORDER BY tanggal DESC, created_at DESC");
    while ($r = mysqli_fetch_assoc($q)) {
        // format tanggal as dd/mm/YYYY to be more friendly for Excel
        $tgl = '';
        if (!empty($r['tanggal']) && strtotime($r['tanggal']) !== false) {
            $tgl = date('d/m/Y', strtotime($r['tanggal']));
        }

        fputcsv($output, [
            $r['id'],
            $r['tipe'],
            $r['deskripsi'],
            $r['jumlah'],
            $tgl
        ]);
    }

    fclose($output);
    exit;
}

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#1e1b4b" />
    <link rel="icon" href="../img/main/icon.png" />
    <title>Arus Kas - PTIK C 2024</title>
    <!-- Google tag (gtag.js) -->
    <script async src="https://www.googletagmanager.com/gtag/js?id=G-Q0TXETXV6Y"></script>
    <script>
        window.dataLayer = window.dataLayer || [];

        function gtag() {
            dataLayer.push(arguments);
        }
        gtag('js', new Date());

        gtag('config', 'G-Q0TXETXV6Y');
    </script>
    <link href="../../frontend/tailwind/src/output.css" rel="stylesheet">
    <link href="../../frontend/tailwind/src/input.css" rel="stylesheet">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&display=swap" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        .rotate-180 {
            transform: rotate(180deg);
        }

        /* Tambahan CSS untuk mobile menu */
        @media (max-width: 768px) {
            .mobile-menu-open {
                transform: translateX(0) !important;
            }
        }

        /* live-search script removed from style block; see script at document end */
    </style>
</head>

<body class="bg-slate-100">
    <header class="bg-indigo-950  fixed w-full top-0 z-50">
        <div class="flex justify-between items-center px-6 py-2">
            <div class="flex items-center">
                <img src="../img/main/logo.png" alt="Logo Universitas Negeri Makassar" class="h-10 w-10 mr-3">
                <span class="text-xl font-bold text-white">Dashboard PTIK C</span>
            </div>
            <!-- Mobile Menu Button dengan z-index yang sesuai -->
            <button id="mobile-menu-button" class="text-slate-100 hover:text-indigo-950 lg:hidden p-2 rounded-lg hover:bg-gray-100">
                <i class="fa fa-bars w-6 h-6"></i>
            </button>
            <div class="hidden lg:flex items-center space-x-4">
                <div class="relative">
                    <button id="profile-button" class="flex items-center space-x-2">
                        <span class="text-white font-semibold"><?php echo htmlspecialchars($nama_lengkap); ?></span>
                        <img src="../img/profile/<?php echo htmlspecialchars($foto); ?>" alt="Profile" class="w-8 h-8 rounded-full">
                    </button>
                    <div id="profile-dropdown" class="absolute right-0 mt-2 w-40 bg-white rounded-md shadow-lg hidden">
                        <a href="profile.php" class="block px-4 py-2 text-xs hover:bg-gray-100 font-semibold text-indigo-900"><i class="fa fa-user mr-2 text-indigo-900"></i>Profile</a>
                        <a href="../../index.php" class="block px-4 py-2 text-xs hover:bg-gray-100 font-semibold text-indigo-900"><i class="fa fa-sign-out mr-2 text-indigo-900"></i>Logout</a>
                    </div>
                </div>
            </div>
        </div>
    </header>

    <!-- Sidebar dengan z-index di bawah header -->
    <aside id="sidebar"
        class="fixed left-0 top-0 h-screen w-48 bg-white transform -translate-x-full lg:translate-x-0 transition-transform duration-200 ease-in-out z-40">
        <!-- Tambahan padding top agar tidak tertutup header -->
        <div class="pt-16">
            <nav class="mt-4 h-[calc(100vh-4rem)] overflow-y-auto pb-10">
                <div class="px-4 space-y-2">
                    <!-- Dashboard Menu -->
                    <a href="dashboard.php" class="flex items-center px-4 py-2 text-indigo-900  rounded-lg hover:bg-slate-100">
                        <i class="fa fa-home w-4 h-4 mr-4"></i>
                        <span class="text-xs font-medium">Dashboard</span>
                    </a>



                    <!-- Components Menu -->
                    <div class="space-y-1">
                        <div>
                            <p class="px-4 text-[10px] font-semibold text-slate-400 uppercase tracking-wider mb-2">Akademik</p>
                            <div class="space-y-1">
                                <button class="submenu-button flex items-center justify-between w-full px-4 py-1 text-slate-800 hover:bg-slate-100 rounded-lg transition-colors">
                                    <div class="flex items-center">
                                        <i class="fa fa-university w-5 h-5 mr-3 text-indigo-900"></i>
                                        <span class="text-xs font-medium">Master</span>
                                    </div>
                                    <i class="fa fa-chevron-down submenu-arrow w-3 h-3 transition-transform duration-200"></i>
                                </button>
                                <div class="submenu pl-1 space-y-1 hidden overflow-y-auto max-h-52 border-l-2 border-slate-100 ml-6">
                                    <a href="db_mahasiswa.php" class="block px-4 py-1 text-xs text-slate-600 hover:text-indigo-600 hover:bg-slate-50 rounded-lg">Mahasiswa</a>
                                    <a href="db_dosen.php" class="block px-4 py-1 text-xs text-slate-600 hover:text-indigo-600 hover:bg-slate-50 rounded-lg">Dosen</a>
                                    <a href="db_matkul.php" class="block px-4 py-1 text-xs text-slate-600 hover:text-indigo-600 hover:bg-slate-50 rounded-lg">Mata Kuliah</a>
                                    <a href="db_berita.php" class="block px-4 py-1 text-xs text-slate-600 hover:text-indigo-600 hover:bg-slate-50 rounded-lg">Berita & Info</a>
                                    <a href="db_kalender.php" class="block px-4 py-1 text-xs text-slate-600 hover:text-indigo-600 hover:bg-slate-50 rounded-lg">Kalender Akademik</a>
                                    <a href="db_galeri.php" class="block px-4 py-1 text-xs text-slate-600 hover:text-indigo-600 hover:bg-slate-50 rounded-lg">Galeri Kelas</a>
                                </div>

                                <a href="db_jadwal.php" class="flex items-center px-4 py-1 text-slate-800 hover:bg-slate-100 rounded-lg transition-colors">
                                    <i class="fa fa-list-alt w-5 h-5 mr-3 text-indigo-900"></i>
                                    <span class="text-xs font-medium">Jadwal Kuliah</span>
                                </a>

                                <a href="db_tugas.php" class="flex items-center px-4 py-1 text-slate-800 hover:bg-slate-100 rounded-lg transition-colors">
                                    <i class="fa fa-tasks w-5 h-5 mr-3 text-indigo-900"></i>
                                    <span class="text-xs font-medium">Tugas Kuliah</span>
                                </a>
                                <a href="db_kelompok.php" class="flex items-center px-4 py-1 text-slate-800 hover:bg-slate-100 rounded-lg transition-colors">
                                    <i class="fa fa-users w-5 h-5 mr-3 text-indigo-900"></i>
                                    <span class="text-xs font-medium">Kelompok</span>
                                </a>
                                <a href="kalender_view.php" class="flex items-center px-4 py-1 text-slate-800 hover:bg-slate-100 rounded-lg transition-colors">
                                    <i class="fa fa-calendar w-5 h-5 mr-3 text-indigo-900"></i>
                                    <span class="text-xs font-medium">Kalender</span>
                                </a>

                            </div>
                        </div>

                        <div>
                            <p class="px-4 text-[10px] font-semibold text-slate-400 uppercase tracking-wider mb-2">Administrasi</p>
                            <div class="space-y-1">
                                <button class="submenu-button flex items-center justify-between w-full px-4 py-1 text-slate-800 hover:bg-slate-100 rounded-lg transition-colors">
                                    <div class="flex items-center">
                                        <i class="fa fa-clipboard-list w-5 h-5 mr-3 text-indigo-900"></i>
                                        <span class="text-xs font-medium">Absensi</span>
                                    </div>
                                    <i class="fa fa-chevron-down submenu-arrow w-3 h-3 transition-transform duration-200"></i>
                                </button>
                                <div class="submenu pl-1 space-y-1 hidden overflow-y-auto max-h-52 border-l-2 border-slate-100 ml-6">
                                    <a href="db_absensi.php" class="block px-4 py-1 text-xs text-slate-600 hover:text-indigo-600 hover:bg-slate-50 rounded-lg">Data Absen</a>
                                    <?php if ($role === 'Administrator Utama' || $role === 'Sekretaris'): ?>
                                        <a href="absensi.php" class="block px-4 py-1 text-xs text-slate-600 hover:text-indigo-600 hover:bg-slate-50 rounded-lg">Input Absen</a>
                                    <?php endif; ?>
                                </div>

                                <button class="submenu-button flex items-center justify-between w-full px-4 py-1 text-slate-800 hover:bg-slate-100 rounded-lg transition-colors">
                                    <div class="flex items-center">
                                        <i class="fa fa-wallet w-5 h-5 mr-3 text-indigo-900"></i>
                                        <span class="text-xs font-medium">Keuangan</span>
                                    </div>
                                    <i class="fa fa-chevron-down submenu-arrow w-3 h-3 transition-transform duration-200"></i>
                                </button>
                                <div class="submenu pl-1 space-y-1 hidden overflow-y-auto max-h-52 border-l-2 border-slate-100 ml-6">
                                    <a href="db_kas.php" class="block px-4 py-1 text-xs text-slate-600 hover:text-indigo-600 hover:bg-slate-50 rounded-lg">Data Kas</a>
                                    <?php if ($role === 'Administrator Utama' || $role === 'Bendahara'): ?>
                                        <a href="arus_kas.php" class="block px-4 py-1 text-xs text-slate-600 hover:text-indigo-600 hover:bg-slate-50 rounded-lg">Arus Kas</a>
                                        <a href="kas.php" class="block px-4 py-1 text-xs text-slate-600 hover:text-indigo-600 hover:bg-slate-50 rounded-lg">Input Per Minggu</a>
                                        <a href="kas_individu.php" class="block px-4 py-1 text-xs text-slate-600 hover:text-indigo-600 hover:bg-slate-50 rounded-lg">Input Per Individu</a>
                                    <?php endif; ?>
                                </div>


                            </div>
                        </div>

                        <div>
                            <p class="px-4 text-[10px] font-semibold text-slate-400 uppercase tracking-wider mb-2">Aktivitas</p>
                            <div class="space-y-1">




                                <a href="misi.php" class="flex items-center px-4 py-1 text-slate-800 hover:bg-slate-100 rounded-lg transition-colors">
                                    <i class="fa fa-trophy w-5 h-5 mr-3 text-indigo-900"></i>
                                    <span class="text-xs font-medium">Pusat Misi</span>
                                </a>
                                <a href="kuis_harian.php" class="flex items-center px-4 py-1 text-slate-800 hover:bg-slate-100 rounded-lg transition-colors">
                                    <i class="fa fa-gamepad w-5 h-5 mr-3 text-indigo-900"></i>
                                    <span class="text-xs font-medium">Kuis Harian</span>
                                </a>
                                <a href="db_pesan.php" class="flex items-center px-4 py-1 text-slate-800 hover:bg-slate-100 rounded-lg transition-colors">
                                    <i class="fa fa-comments w-5 h-5 mr-3 text-indigo-900"></i>
                                    <span class="text-xs font-medium">Pesan Anonim</span>
                                </a>

                            </div>
                        </div>


                        <div>
                            <p class="px-4 text-[10px] font-semibold text-slate-400 uppercase tracking-wider mb-2">Pengaturan</p>
                            <div class="space-y-1">
                                <button class="submenu-button flex items-center justify-between w-full px-4 py-1 text-slate-800 hover:bg-slate-100 rounded-lg transition-colors">
                                    <div class="flex items-center">
                                        <i class="fa fa-user w-5 h-5 mr-3 text-indigo-900"></i>
                                        <span class="text-xs font-medium">Akun</span>
                                    </div>
                                    <i class="fa fa-chevron-down submenu-arrow w-3 h-3 transition-transform duration-200"></i>
                                </button>
                                <div class="submenu pl-1 space-y-1 hidden overflow-y-auto max-h-52 border-l-2 border-slate-100 ml-6">
                                    <a href="profile.php" class="block px-4 py-1 text-xs text-slate-600 hover:text-indigo-600 hover:bg-slate-50 rounded-lg">Profile Saya</a>
                                    <a href="krs.php" class="block px-4 py-1 text-xs text-slate-600 hover:text-indigo-600 hover:bg-slate-50 rounded-lg">KRS</a>

                                </div>
                                <?php if ($role === 'Administrator Utama'): ?>
                                    <button class="submenu-button flex items-center justify-between w-full px-4 py-1 text-slate-800 hover:bg-slate-100 rounded-lg transition-colors">
                                        <div class="flex items-center">
                                            <i class="fa-solid fa-earth-americas w-5 h-5 mr-3 text-indigo-900"></i>
                                            <span class="text-xs font-medium">Website</span>
                                        </div>
                                        <i class="fa fa-chevron-down submenu-arrow w-3 h-3 transition-transform duration-200"></i>
                                    </button>
                                    <div class="submenu pl-1 space-y-1 hidden overflow-y-auto max-h-52 border-l-2 border-slate-100 ml-6">
                                        <a href="db_users.php" class="block px-4 py-1 text-xs text-slate-600 hover:text-indigo-600 hover:bg-slate-50 rounded-lg">Users</a>
                                        <a href="db_periode.php" class="block px-4 py-1 text-xs text-slate-600 hover:text-indigo-600 hover:bg-slate-50 rounded-lg">Periode</a>
                                        <a href="reminder.php" class="block px-4 py-1 text-xs text-slate-600 hover:text-indigo-600 hover:bg-slate-50 rounded-lg">Reminder</a>
                                    </div>
                                <?php endif; ?>


                                <a href="tentang.php" class="flex items-center px-4 py-1 text-slate-800 hover:bg-slate-100 rounded-lg transition-colors">
                                    <i class="fa fa-info-circle w-5 h-5 mr-3 text-indigo-900"></i>
                                    <span class="text-xs font-medium">Tentang Aplikasi</span>
                                </a>

                                <a href="../../index.php" class="flex items-center px-4 py-1 text-slate-800 hover:bg-red-50 hover:text-red-700 rounded-lg transition-colors">
                                    <i class="fa fa-sign-out-alt w-5 h-5 mr-3 text-indigo-900"></i>
                                    <span class="text-xs font-medium">Logout</span>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </nav>
        </div>
    </aside>

    <main class="ml-0 lg:ml-48 pt-20 p-6 flex flex-col min-h-screen">
        <div class="max-w-6xl mx-auto flex-grow w-full">
            <div class="grid grid-cols-1 sm:grid-cols-4 gap-4 mb-6">
                <div class="bg-white p-4 rounded shadow">
                    <div class="text-xs text-slate-500">Pemasukan dari Kas (Lunas)</div>
                    <div class="text-2xl font-bold text-indigo-900">Rp <?= number_format($total_kas, 0, ',', '.') ?></div>
                </div>
                <div class="bg-white p-4 rounded shadow">
                    <div class="text-xs text-slate-500">Pemasukan Lainnya</div>
                    <div class="text-2xl font-bold text-green-700">Rp <?= number_format($total_other_masuk, 0, ',', '.') ?></div>
                </div>
                <div class="bg-white p-4 rounded shadow">
                    <div class="text-xs text-slate-500">Total Pengeluaran</div>
                    <div class="text-2xl font-bold text-red-700">Rp <?= number_format($total_keluar, 0, ',', '.') ?></div>
                </div>
                <div class="bg-white p-4 rounded shadow">
                    <div class="text-xs text-slate-500">Saldo</div>
                    <div class="text-2xl font-bold text-indigo-900">Rp <?= number_format($saldo, 0, ',', '.') ?></div>
                </div>
            </div>


            <div class="bg-white p-4 rounded shadow mb-6">
                <h2 class="text-lg font-semibold mb-2">Tambah Pemasukan / Pengeluaran</h2>
                <form method="POST" class="grid grid-cols-1 sm:grid-cols-4 gap-2">
                    <select name="tipe" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-1 focus:ring-gray-400 text-xs" required>
                        <option value="masuk" <?= $form_tipe === 'masuk' ? 'selected' : '' ?>>Pemasukan</option>
                        <option value="keluar" <?= $form_tipe === 'keluar' ? 'selected' : '' ?>>Pengeluaran</option>
                    </select>
                    <input type="text" name="deskripsi" placeholder="Deskripsi" value="<?= htmlspecialchars($form_deskripsi) ?>" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-1 focus:ring-gray-400 text-xs">
                    <input type="number" step="0.01" name="jumlah" placeholder="Jumlah (Rp)" value="<?= htmlspecialchars($form_jumlah) ?>" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-1 focus:ring-gray-400 text-xs" required>
                    <input type="date" name="tanggal" value="<?= htmlspecialchars($form_tanggal) ?>" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-1 focus:ring-gray-400 text-xs">
                    <div class="sm:col-span-4">
                        <?php if ($editing): ?>
                            <input type="hidden" name="id" value="<?= (int)$eid ?>">
                            <button type="submit" name="edit_arus" class="mt-2 bg-yellow-600 text-white px-4 py-2 rounded text-xs">Simpan Perubahan</button>
                            <a href="arus_kas.php" class="ml-2 mt-2 inline-block text-xs text-slate-600">Batal</a>
                        <?php else: ?>
                            <button type="submit" name="tambah_arus" class="mt-2 bg-indigo-900 text-white px-4 py-2 rounded text-xs">Tambah</button>
                        <?php endif; ?>
                    </div>
                </form>
            </div>

            <div class="bg-white p-4 rounded shadow">
                <div class="flex justify-between items-center mb-2">
                    <h2 class="text-lg font-semibold">Riwayat Arus Kas</h2>
                    <a href="arus_kas.php?action=export_csv" class="bg-green-600 text-white px-4 py-2 rounded text-xs">Export CSV</a>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200 text-xs">
                        <thead class="bg-indigo-900 text-white">
                            <tr>
                                <th class="px-3 py-2 text-left">No</th>
                                <th class="px-3 py-2 text-left">Tipe</th>
                                <th class="px-3 py-2 text-left">Deskripsi</th>
                                <th class="px-3 py-2 text-right">Jumlah</th>
                                <th class="px-3 py-2 text-left">Tanggal</th>
                                <th class="px-3 py-2 text-left">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white">
                            <?php $no = 1;
                            while ($row = mysqli_fetch_assoc($entries)): ?>
                                <tr class="hover:bg-indigo-50">
                                    <td class="px-3 py-2"><?= $no++ ?></td>
                                    <td class="px-3 py-2"><?= $row['tipe'] ?></td>
                                    <td class="px-3 py-2"><?= htmlspecialchars($row['deskripsi']) ?></td>
                                    <td class="px-3 py-2 text-right">Rp <?= number_format($row['jumlah'], 0, ',', '.') ?></td>
                                    <td class="px-3 py-2"><?= !empty($row['tanggal']) ? date('d/m/Y', strtotime($row['tanggal'])) : '-' ?></td>
                                    <td class="px-3 py-2">
                                        <a href="arus_kas.php?action=edit&id=<?= $row['id'] ?>">
                                            <i class="fa fa-edit text-blue-600 hover:text-blue-900 mr-3"></i>
                                        </a>
                                        <a href="arus_kas.php?action=delete&id=<?= $row['id'] ?>">
                                            <i class="fa fa-trash text-red-600 hover:text-red-900 mr-3"></i>
                                        </a>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <footer class="text-xs text-indigo-900 text-center mb-0 pb-0 mt-6">© 2025 Kelas PTIK C - Teknik Informatika dan Komputer FT UNM. All rights reserved.</footer>
    </main>
    <script>
        // Mobile menu toggle dengan perbaikan
        const mobileMenuButton = document.getElementById('mobile-menu-button');
        const sidebar = document.getElementById('sidebar');

        mobileMenuButton.addEventListener('click', (e) => {
            e.stopPropagation(); // Prevent event bubbling
            sidebar.classList.toggle('-translate-x-full');
            sidebar.classList.toggle('mobile-menu-open');
        });

        // Close sidebar when clicking outside
        document.addEventListener('click', (e) => {
            if (window.innerWidth < 768) { // Only on mobile
                if (!sidebar.contains(e.target) && !mobileMenuButton.contains(e.target)) {
                    sidebar.classList.add('-translate-x-full');
                    sidebar.classList.remove('mobile-menu-open');
                }
            }
        });

        // Submenu toggles
        const submenuButtons = document.querySelectorAll('.submenu-button');

        submenuButtons.forEach(button => {
            button.addEventListener('click', () => {
                const submenu = button.nextElementSibling;
                const arrow = button.querySelector('.submenu-arrow');

                submenu.classList.toggle('hidden');
                if (arrow) arrow.classList.toggle('rotate-180');
            });
        });

        // Profile dropdown
        const profileButton = document.getElementById('profile-button');
        const profileDropdown = document.getElementById('profile-dropdown');

        if (profileButton && profileDropdown) {
            profileButton.addEventListener('click', (e) => {
                e.stopPropagation();
                profileDropdown.classList.toggle('hidden');
            });

            document.addEventListener('click', (e) => {
                if (!profileButton.contains(e.target)) {
                    profileDropdown.classList.add('hidden');
                }
            });
        }
    </script>
</body>

</html>