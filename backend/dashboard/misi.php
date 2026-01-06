<?php
session_start();
include '../../koneksi.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../../index.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$nama_lengkap = $_SESSION['nama_lengkap'];
$foto = $_SESSION['foto'];
$role = $_SESSION['role'];

// Ambil data mahasiswa berdasarkan user_id
$q_mhs = mysqli_query($koneksi, "SELECT * FROM mahasiswa WHERE user_id = '$user_id'");
$mhs = mysqli_fetch_assoc($q_mhs);
$mhs_id = $mhs['id'] ?? 0;

/* =====================
   AMBIL PERIODE AKTIF
   ===================== */
$q_periode = mysqli_query($koneksi, "SELECT id FROM periode WHERE status = 'aktif' LIMIT 1");
$periode = mysqli_fetch_assoc($q_periode);
$periode_id = $periode['id'] ?? 0;

// --- HITUNG PROGRES MISI ---

// 1. Misi Kas
$res_kas = mysqli_query($koneksi, "SELECT COUNT(*) as total FROM kas WHERE mahasiswa_id = '$mhs_id' AND status = 'Lunas' AND periode_id = '$periode_id'");
$count_kas = mysqli_fetch_assoc($res_kas)['total'] ?? 0;

// 2. Misi Kuis
$res_kuis = mysqli_query($koneksi, "SELECT current_streak, total_games FROM kuis_harian_stats WHERE user_id = '$user_id'");
$kuis_stats = mysqli_fetch_assoc($res_kuis);
$kuis_streak = $kuis_stats['current_streak'] ?? 0;
$kuis_total = $kuis_stats['total_games'] ?? 0;

// 3. Misi Absensi
$res_absensi = mysqli_query($koneksi, "SELECT COUNT(*) as total FROM absensi WHERE mahasiswa_id = '$mhs_id' AND status = 'Hadir' AND periode_id = '$periode_id'");
$count_absensi = mysqli_fetch_assoc($res_absensi)['total'] ?? 0;

// 4. Misi Profil
$profil_lengkap = (!empty($mhs['foto']) && $mhs['foto'] !== 'Yaya.png' && !empty($mhs['instagram']) && !empty($mhs['bio'])) ? 1 : 0;

// 5. Misi KRS (Jumlah mata kuliah yang diambil di semester ini)
$res_krs = mysqli_query($koneksi, "SELECT COUNT(*) as total FROM krs WHERE mahasiswa_id = '$mhs_id' AND periode_id = '$periode_id'");
$count_krs = mysqli_fetch_assoc($res_krs)['total'] ?? 0;

// 6. Misi Kelompok
$q_nim = mysqli_query($koneksi, "SELECT nim FROM mahasiswa WHERE id = '$mhs_id'");
$nim_mhs = mysqli_fetch_assoc($q_nim)['nim'] ?? '';
$res_kelompok = mysqli_query($koneksi, "SELECT COUNT(*) as total FROM kelompok WHERE FIND_IN_SET('$nim_mhs', REPLACE(anggota, ' ', '')) OR anggota LIKE '%$nim_mhs%'");
$count_kelompok = mysqli_fetch_assoc($res_kelompok)['total'] ?? 0;

// Definisi Misi
$missions = [
    [
        'category' => 'Keuangan',
        'title' => 'Bendahara Tersenyum',
        'icon' => 'fa-wallet',
        'color' => 'emerald',
        'levels' => [
            ['name' => 'Bronze', 'target' => 5, 'current' => $count_kas, 'desc' => 'Bayar kas selama 5 minggu'],
            ['name' => 'Silver', 'target' => 10, 'current' => $count_kas, 'desc' => 'Bayar kas selama 10 minggu'],
            ['name' => 'Gold', 'target' => 16, 'current' => $count_kas, 'desc' => 'Bayar kas penuh satu semester (16 minggu)']
        ]
    ],
    [
        'category' => 'Akademik',
        'title' => 'Mahasiswa Teladan',
        'icon' => 'fa-user-check',
        'color' => 'blue',
        'levels' => [
            ['name' => 'Hadir 5x', 'target' => 5, 'current' => $count_absensi, 'desc' => 'Kumpulkan 5 poin kehadiran'],
            ['name' => 'Hadir 15x', 'target' => 15, 'current' => $count_absensi, 'desc' => 'Kumpulkan 15 poin kehadiran'],
            ['name' => 'Hadir 30x', 'target' => 30, 'current' => $count_absensi, 'desc' => 'Kumpulkan 30 poin kehadiran']
        ]
    ],
    [
        'category' => 'Kuis Harian',
        'title' => 'Raja Kuis',
        'icon' => 'fa-fire',
        'color' => 'orange',
        'levels' => [
            ['name' => 'Starter Streak', 'target' => 3, 'current' => $kuis_streak, 'desc' => 'Capai 3 hari streak kuis'],
            ['name' => 'Veteran Streak', 'target' => 14, 'current' => $kuis_streak, 'desc' => 'Capai 14 hari streak kuis'],
            ['name' => 'Total Gamer 50', 'target' => 50, 'current' => $kuis_total, 'desc' => 'Total bermain kuis sebanyak 50 kali']
        ]
    ],
    [
        'category' => 'Sosial',
        'title' => 'Warga Kelas Aktif',
        'icon' => 'fa-user-check',
        'color' => 'purple',
        'levels' => [
            ['name' => 'KRS Terisi', 'target' => 1, 'current' => ($count_krs > 0 ? 1 : 0), 'desc' => 'Selesaikan pengisian KRS semester ini'],
            ['name' => 'Profil Lengkap', 'target' => 1, 'current' => $profil_lengkap, 'desc' => 'Lengkapi foto (bukan default), instagram & bio'],
            ['name' => 'Si Paling Kelompok', 'target' => 9, 'current' => $count_kelompok, 'desc' => 'Terjalin dalam 9 kelompok mata kuliah']
        ]
    ]
];

// Mapping warna ke CSS filter untuk logo
$color_filters = [
    'emerald' => 'brightness(0) saturate(100%) invert(65%) sepia(16%) saturate(3065%) hue-rotate(116deg) brightness(101%) contrast(86%)',
    'blue'    => 'brightness(0) saturate(100%) invert(51%) sepia(85%) saturate(2394%) hue-rotate(201deg) brightness(99%) contrast(94%)',
    'orange'  => 'brightness(0) saturate(100%) invert(62%) sepia(77%) saturate(3143%) hue-rotate(352deg) brightness(101%) contrast(96%)',
    'purple'  => 'brightness(0) saturate(100%) invert(43%) sepia(72%) saturate(3085%) hue-rotate(244deg) brightness(102%) contrast(95%)',
    'yellow'  => 'brightness(0) saturate(100%) invert(91%) sepia(96%) saturate(1313%) hue-rotate(359deg) brightness(101%) contrast(101%)'
];
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#1e1b4b" />
    <title>Pusat Misi - PTIK C 2024</title>
    <link rel="icon" href="../img/main/icon.webp" />
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="../../frontend/tailwind/src/output.css" rel="stylesheet">
    <link href="../../frontend/tailwind/src/input.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        body {
            font-family: 'Poppins', sans-serif;
        }

        .glass {
            background: rgba(255, 255, 255, 0.7);
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.3);
        }

        .mission-card:hover {
            transform: translateY(-5px);
        }

        .status-badge {
            transition: all 0.3s ease;
        }

        .locked {
            opacity: 0.5;
            filter: grayscale(1);
        }

        .completed {
            filter: drop-shadow(0 0 8px rgba(255, 215, 0, 0.6));
        }
    </style>
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
</head>

<body class="bg-slate-100 min-h-screen">
    <header class="bg-indigo-950  fixed w-full top-0 z-50">
        <div class="flex justify-between items-center px-6 py-2">
            <div class="flex items-center">
                <img src="../img/main/logo.webp" alt="Logo Universitas Negeri Makassar" class="h-10 w-10 mr-3">
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

    <main class="ml-0 lg:ml-48 pt-20 p-6 flex flex-col min-h-screen bg-indigo-950/5">
        <div class="max-w-6xl mx-auto w-full">
            <div class="mb-8">
                <h1 class="text-3xl font-extrabold text-slate-800 tracking-tight">Pusat Misi & Pencapaian</h1>
                <p class="text-slate-500 text-sm mt-1">Selesaikan misi untuk mendapatkan lencana prestisius!</p>
            </div>



            <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                <?php foreach ($missions as $cat): ?>
                    <div class="glass p-6 rounded-2xl shadow-xl transition-all mission-card">
                        <div class="flex items-center gap-4 mb-6">
                            <div class="w-14 h-14 rounded-2xl bg-<?= $cat['color'] ?>-100 flex items-center justify-center text-<?= $cat['color'] ?>-600 shadow-inner">
                                <i class="fa <?= $cat['icon'] ?> text-2xl"></i>
                            </div>
                            <div>
                                <span class="text-[10px] uppercase font-black tracking-widest text-<?= $cat['color'] ?>-600"><?= $cat['category'] ?></span>
                                <h3 class="text-xl font-bold text-slate-800"><?= $cat['title'] ?></h3>
                            </div>
                        </div>

                        <div class="space-y-6">
                            <?php foreach ($cat['levels'] as $level):
                                $progress = min(100, ($level['current'] / $level['target']) * 100);
                                $is_completed = ($level['current'] >= $level['target']);
                            ?>
                                <div class="bg-white/50 p-4 rounded-xl border border-white/80 shadow-sm">
                                    <div class="flex justify-between items-start mb-2">
                                        <div class="flex items-center gap-3">
                                            <div class="w-10 h-10 rounded-full flex items-center justify-center <?= $is_completed ? 'bg-' . $cat['color'] . '-100 completed' : 'bg-slate-100 text-slate-400 locked' ?>">
                                                <?php if ($is_completed): ?>
                                                    <img src="../img/main/logo.webp" class="w-6 h-6 object-contain" style="filter: <?= $color_filters[$cat['color']] ?? $color_filters['yellow'] ?>;">
                                                <?php else: ?>
                                                    <i class="fa fa-lock text-lg"></i>
                                                <?php endif; ?>
                                            </div>
                                            <div>
                                                <h4 class="font-bold text-sm text-slate-700"><?= $level['name'] ?></h4>
                                                <p class="text-[10px] text-slate-500"><?= $level['desc'] ?></p>
                                            </div>
                                        </div>
                                        <div class="text-right">
                                            <span class="text-xs font-black text-slate-700"><?= $level['current'] ?> / <?= $level['target'] ?></span>
                                        </div>
                                    </div>

                                    <div class="w-full bg-slate-200 h-2 rounded-full overflow-hidden mt-3 shadow-inner">
                                        <div class="h-full rounded-full transition-all duration-1000 bg-gradient-to-r from-<?= $cat['color'] ?>-400 to-<?= $cat['color'] ?>-600" style="width: <?= $progress ?>%"></div>
                                    </div>

                                    <?php if ($is_completed): ?>
                                        <div class="mt-3 flex items-center gap-1.5 text-[10px] font-bold text-green-600 uppercase">
                                            <i class="fa fa-check-circle"></i> Berhasil Dicapai!
                                        </div>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <div class="mt-12 p-8 rounded-3xl border border-indigo-900/50 bg-gradient-to-br from-slate-900 via-indigo-950 to-slate-900 text-white overflow-hidden relative shadow-2xl">
                <div class="relative z-10">
                    <h2 class="text-2xl font-black mb-2 flex items-center gap-3">
                        <i class="fa fa-star text-yellow-400"></i>
                        Koleksi Lencana Kamu
                    </h2>
                    <p class="text-indigo-200 text-sm mb-6">Berikut adalah lencana eksklusif yang telah kamu kumpulkan melalui kerja kerasmu!</p>

                    <div class="flex flex-wrap justify-center gap-6 md:gap-8">
                        <?php
                        $any_badge = false;
                        $badge_gradients = [
                            'emerald' => 'from-emerald-400 to-emerald-600',
                            'blue'    => 'from-blue-400 to-blue-600',
                            'cyan'    => 'from-cyan-400 to-cyan-600',
                            'orange'  => 'from-orange-400 to-orange-600',
                            'purple'  => 'from-purple-400 to-purple-600',
                            'yellow'  => 'from-yellow-400 to-yellow-600'
                        ];

                        foreach ($missions as $cat):
                            $bg_grad = $badge_gradients[$cat['color']] ?? 'from-slate-400 to-slate-600';

                            foreach ($cat['levels'] as $l_idx => $level):
                                if ($level['current'] >= $level['target']):
                                    $any_badge = true;
                        ?>
                                    <div class="group relative flex flex-col items-center">
                                        <!-- Badge Circle -->
                                        <div class="relative w-14 h-14 md:w-16 md:h-16 transition-transform duration-300 transform group-hover:-translate-y-2 group-hover:scale-105 cursor-pointer">
                                            <!-- Glow Effect behind -->
                                            <div class="absolute inset-0 bg-gradient-to-br <?= $bg_grad ?> rounded-full blur-md opacity-40 group-hover:opacity-75 transition-opacity"></div>

                                            <!-- Main Badge Body -->
                                            <div class="relative w-full h-full rounded-full bg-gradient-to-br <?= $bg_grad ?> p-[2px] shadow-xl">
                                                <div class="w-full h-full rounded-full bg-slate-900/90 backdrop-blur-sm flex flex-col items-center justify-center border border-white/10 relative overflow-hidden">

                                                    <!-- Shine Animation -->
                                                    <div class="absolute inset-0 bg-gradient-to-tr from-transparent via-white/20 to-transparent translate-x-[-150%] skew-x-12 group-hover:translate-x-[150%] transition-transform duration-1000 ease-in-out"></div>

                                                    <i class="fa <?= $cat['icon'] ?> text-lg md:text-2xl text-white mb-1 drop-shadow-md relative z-10"></i>

                                                    <!-- Tiny Logos for rank indicator -->
                                                    <div class="flex gap-0.5 mt-0.5 relative z-10">
                                                        <?php for ($s = 0; $s <= $l_idx; $s++): ?>
                                                            <img src="../img/main/logo.webp" class="w-2.5 h-2.5 object-contain" style="filter: <?= $color_filters[$cat['color']] ?? $color_filters['yellow'] ?>;">
                                                        <?php endfor; ?>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Label -->
                                        <div class="mt-3 text-center">
                                            <span class="text-[10px] md:text-xs font-bold uppercase tracking-widest text-white/90 bg-white/10 px-3 py-1 rounded-full border border-white/5 shadow-sm group-hover:bg-white/20 transition-colors">
                                                <?= $level['name'] ?>
                                            </span>
                                        </div>

                                        <!-- Detailed Tooltip -->
                                        <div class="absolute bottom-full left-1/2 -translate-x-1/2 mb-3 bg-slate-900 text-white text-xs py-2 px-3 rounded-xl shadow-2xl opacity-0 group-hover:opacity-100 transition-all duration-300 pointer-events-none transform translate-y-2 group-hover:translate-y-0 w-max max-w-[150px] text-center z-50 border border-white/10">
                                            <p class="font-bold text-<?= $cat['color'] ?>-400 mb-0.5"><?= $cat['category'] ?></p>
                                            <p class="text-[10px] text-slate-300 leading-tight"><?= $level['desc'] ?></p>
                                            <!-- Arrow -->
                                            <div class="absolute top-full left-1/2 -translate-x-1/2 -mt-1 border-4 border-transparent border-t-slate-900"></div>
                                        </div>
                                    </div>
                            <?php
                                endif;
                            endforeach;
                        endforeach;

                        if (!$any_badge):
                            ?>
                            <div class="w-full text-center py-8 bg-white/5 rounded-2xl border border-white/10 flex flex-col items-center justify-center dashed-border">
                                <div class="w-16 h-16 bg-white/10 rounded-full flex items-center justify-center mb-3">
                                    <i class="fa fa-lock text-white/30 text-2xl"></i>
                                </div>
                                <p class="text-indigo-200 text-sm font-medium">Belum ada lencana yang terbuka.</p>
                                <p class="text-indigo-300/60 text-xs mt-1">Selesaikan misi di atas untuk mulai mengoleksi!</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                <!-- Decor -->
                <div class="absolute -right-20 -bottom-20 w-64 h-64 bg-indigo-500/20 rounded-full blur-3xl"></div>
                <div class="absolute -left-20 -top-20 w-64 h-64 bg-purple-500/20 rounded-full blur-3xl"></div>
            </div>
        </div>

        <footer class="text-xs text-indigo-900 text-center mt-8 pb-2">© 2025 Kelas PTIK C - Teknik Informatika dan Komputer FT UNM. All rights reserved.</footer>
    </main>

    <script>
        // Mobile Sidebar Toggle
        const mobileMenuButton = document.getElementById('mobile-menu-button');
        const sidebar = document.getElementById('sidebar');

        mobileMenuButton.addEventListener('click', (e) => {
            e.stopPropagation();
            sidebar.classList.toggle('-translate-x-full');
        });

        document.addEventListener('click', (e) => {
            if (window.innerWidth < 1024 && !sidebar.contains(e.target) && !mobileMenuButton.contains(e.target)) {
                sidebar.classList.add('-translate-x-full');
            }
        });

        const profileButton = document.getElementById('profile-button');
        const profileDropdown = document.getElementById('profile-dropdown');
        if (profileButton && profileDropdown) {
            profileButton.addEventListener('click', (e) => {
                e.stopPropagation();
                profileDropdown.classList.toggle('hidden');
            });
            document.addEventListener('click', () => profileDropdown.classList.add('hidden'));
        }

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
    </script>
</body>

</html>