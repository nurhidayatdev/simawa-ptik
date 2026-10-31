<?php
// Deteksi folder dasar untuk mendukung file di berbagai subfolder (root, actions, database, functions)
$base = "";
if (file_exists('koneksi.php')) {
    $base = "";
} elseif (file_exists('../koneksi.php')) {
    $base = "../";
} elseif (file_exists('../../koneksi.php')) {
    $base = "../../";
}

// Include foto helpers for cache busting
include_once __DIR__ . '/../functions/foto_helpers.php';

// Pastikan variabel user sesi selalu tersedia
$role         = $_SESSION['role'] ?? 'Mahasiswa';
$nama_lengkap = $_SESSION['nama_lengkap'] ?? 'Pengguna';
$foto         = $_SESSION['foto'] ?? '';
?>
<style>
    /* Custom Scrollbar Styling for Sidebar & Submenus */
    #sidebar nav::-webkit-scrollbar,
    .submenu::-webkit-scrollbar {
        width: 4px;
    }
    #sidebar nav::-webkit-scrollbar-track,
    .submenu::-webkit-scrollbar-track {
        background: transparent;
    }
    #sidebar nav::-webkit-scrollbar-thumb,
    .submenu::-webkit-scrollbar-thumb {
        background: #cbd5e1;
        border-radius: 10px;
    }
    .dark #sidebar nav::-webkit-scrollbar-thumb,
    .dark .submenu::-webkit-scrollbar-thumb {
        background: #334155;
    }
    #sidebar nav::-webkit-scrollbar-thumb:hover,
    .submenu::-webkit-scrollbar-thumb:hover {
        background: #94a3b8;
    }
    .dark #sidebar nav::-webkit-scrollbar-thumb:hover,
    .dark .submenu::-webkit-scrollbar-thumb:hover {
        background: #475569;
    }
    /* =========================================================
       GLOBAL DASHBOARD LAYOUT: HEADER HEIGHT & MAIN OFFSET
       Mencegah header fixed menimpa/overlapping konten dashboard di semua role
       ========================================================= */
    #navbar-top,
    header.fixed {
        height: 4rem !important; /* 64px fixed navbar */
    }
    #navbar-top > div,
    header.fixed > div {
        height: 100% !important;
    }

    /* Target elemen main di seluruh dashboard dan back-office */
    body > main,
    main.flex-col {
        padding-top: 5.5rem !important; /* Mobile: 88px = 64px navbar + 24px safe clearance */
    }
    @media (min-width: 768px) {
        body > main,
        main.flex-col {
            padding-top: 6rem !important; /* Desktop: 96px = 64px navbar + 32px generous breathing room */
        }
    }
</style>

<!-- Header dengan z-index lebih tinggi -->
<header id="navbar-top" class="bg-indigo-950 fixed w-full top-0 h-16 z-50 shadow-md">
    <div class="flex justify-between items-center px-4 md:px-6 h-full w-full">
        <div class="flex items-center">
            <img src="<?= $base ?>assets/image/logo.webp" alt="Logo Universitas Negeri Makassar" class="h-10 w-10 mr-3">
            <span class="text-xl font-bold text-white">SIMAWA PTIK 2024</span>
            <?php if (function_exists('is_maintenance_mode') && is_maintenance_mode($koneksi)): ?>
                <span class="ml-3 px-2.5 py-0.5 rounded-full bg-amber-500/20 text-amber-300 border border-amber-500/40 text-[10px] font-black uppercase tracking-wider flex items-center gap-1.5 shadow-sm">
                    <i class="fas fa-screwdriver-wrench text-amber-400 text-[10px]"></i>
                    <span class="hidden sm:inline">Maintenance Mode</span>
                </span>
            <?php endif; ?>
        </div>
        <div class="flex items-center gap-2">
            <!-- Theme Toggle -->
            <button id="theme-toggle" class="relative inline-flex items-center h-7 w-12 flex-shrink-0 cursor-pointer rounded-full border border-white/20 bg-white/10 transition-all duration-300 ease-in-out focus:outline-none">
                <span id="theme-toggle-thumb" class="pointer-events-none absolute left-0.5 h-5 w-5 transform rounded-full bg-white shadow-md transition duration-300 ease-in-out flex items-center justify-center dark:translate-x-5">
                    <i id="theme-toggle-light-icon" class="fa-solid fa-sun text-[10px] text-orange-500 transition-all duration-300 absolute"></i>
                    <i id="theme-toggle-dark-icon" class="fa-solid fa-moon text-[10px] text-indigo-900 transition-all duration-300 absolute opacity-0 scale-0"></i>
                </span>
            </button>

            <!-- Mobile Menu Button dengan z-index yang sesuai -->
            <button id="mobile-menu-button" class="grid place-items-center h-10 w-10 text-slate-100 hover:text-indigo-950 lg:hidden rounded-lg hover:bg-gray-100 dark:hover:bg-slate-800 dark:hover:text-indigo-400 transition-colors">
                <i class="fa fa-bars text-xl leading-none"></i>
            </button>

            <div class="hidden lg:flex items-center space-x-4">
                <div class="relative">
                    <button id="profile-button" class="flex items-center space-x-2">
                        <span class="text-white font-semibold"><?php echo htmlspecialchars($nama_lengkap); ?></span>
                        <img src="<?= profilePhotoUrl($foto, $base) ?>" alt="Profile" class="w-8 h-8 rounded-full">
                    </button>
                    <div id="profile-dropdown" class="absolute right-0 mt-2 w-40 bg-white dark:bg-slate-800 rounded-md shadow-lg hidden overflow-hidden border dark:border-slate-700">
                        <a href="<?= $base ?>views/profile" class="block px-4 py-2 text-xs hover:bg-gray-100 dark:hover:bg-slate-700 font-semibold text-indigo-900 dark:text-slate-200"><i class="fa fa-user mr-2 text-indigo-900 dark:text-indigo-400"></i>Profile</a>
                        <a href="<?= $base ?>index" class="block px-4 py-2 text-xs hover:bg-gray-100 dark:hover:bg-slate-700 font-semibold text-indigo-900 dark:text-slate-200"><i class="fa fa-sign-out mr-2 text-indigo-900 dark:text-indigo-400"></i>Logout</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</header>

<!-- Sidebar Shell (w-48, bg-white / dark:bg-slate-900) -->
<aside id="sidebar"
    class="fixed left-0 top-0 h-screen w-48 bg-white dark:bg-slate-900 border-r border-slate-200 dark:border-slate-800 transform -translate-x-full lg:translate-x-0 transition-transform duration-200 ease-in-out z-40">
    <div class="pt-16">
        <nav class="mt-4 h-[calc(100vh-4rem)] overflow-y-auto pb-10">
            <div class="px-4 space-y-3">

                <!-- =========================================================
                     1. SIDEBAR ROLE: ADMINISTRATOR UTAMA (EKSEKUTIF / GLOBAL)
                     ========================================================= -->
                <?php if ($role === 'Administrator Utama'): ?>
                    
                    <!-- Dashboard Admin -->
                    <a href="<?= $base ?>dashboard_admin.php" class="flex items-center px-4 py-2 text-indigo-900 dark:text-indigo-400 rounded-lg hover:bg-slate-100 dark:hover:bg-indigo-900/50">
                        <i class="fa fa-home w-4 h-4 mr-4"></i>
                        <span class="text-xs font-semibold">Dashboard</span>
                    </a>

                    <!-- Section: Master Data Global -->
                    <div class="space-y-1">
                        <p class="px-4 text-[10px] font-semibold text-slate-400 dark:text-slate-500 uppercase tracking-wider mb-2">Master Global</p>
                        
                        <button class="submenu-button flex items-center justify-between w-full px-4 py-2 text-slate-800 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800 rounded-lg transition-colors">
                            <div class="flex items-center">
                                <i class="fa fa-university w-4 h-4 mr-4 text-indigo-900 dark:text-indigo-400"></i>
                                <span class="text-xs font-medium">Data Akademik</span>
                            </div>
                            <i class="fa fa-chevron-down submenu-arrow w-3 h-3 transition-transform duration-200"></i>
                        </button>
                        <div class="submenu pl-1 space-y-1 hidden overflow-y-auto max-h-52 border-l-2 border-slate-100 dark:border-slate-800 ml-6">
                            <a href="<?= $base ?>database/db_dosen" class="block px-4 py-1.5 text-xs text-slate-600 dark:text-slate-400 hover:text-indigo-600 dark:hover:text-indigo-400 hover:bg-slate-50 dark:hover:bg-indigo-950/30 rounded-lg">Dosen</a>
                            <a href="<?= $base ?>database/db_matkul" class="block px-4 py-1.5 text-xs text-slate-600 dark:text-slate-400 hover:text-indigo-600 dark:hover:text-indigo-400 hover:bg-slate-50 dark:hover:bg-indigo-950/30 rounded-lg">Mata Kuliah</a>
                            <a href="<?= $base ?>database/db_ruangan" class="block px-4 py-1.5 text-xs text-slate-600 dark:text-slate-400 hover:text-indigo-600 dark:hover:text-indigo-400 hover:bg-slate-50 dark:hover:bg-indigo-950/30 rounded-lg">Ruangan</a>
                            <a href="<?= $base ?>database/db_jam" class="block px-4 py-1.5 text-xs text-slate-600 dark:text-slate-400 hover:text-indigo-600 dark:hover:text-indigo-400 hover:bg-slate-50 dark:hover:bg-indigo-950/30 rounded-lg">Jam Kuliah</a>
                            <a href="<?= $base ?>database/db_periode" class="block px-4 py-1.5 text-xs text-slate-600 dark:text-slate-400 hover:text-indigo-600 dark:hover:text-indigo-400 hover:bg-slate-50 dark:hover:bg-indigo-950/30 rounded-lg">Periode Semester</a>
                            <a href="<?= $base ?>database/db_kalender" class="block px-4 py-1.5 text-xs text-slate-600 dark:text-slate-400 hover:text-indigo-600 dark:hover:text-indigo-400 hover:bg-slate-50 dark:hover:bg-indigo-950/30 rounded-lg">Kalender Akademik</a>
                        </div>

                        <a href="<?= $base ?>database/db_berita" class="flex items-center px-4 py-2 text-slate-800 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800 rounded-lg transition-colors">
                            <i class="fa fa-newspaper w-4 h-4 mr-4 text-indigo-900 dark:text-indigo-400"></i>
                            <span class="text-xs font-medium">Berita & Info</span>
                        </a>
                    </div>

                    <!-- Section: Sistem & Akun -->
                    <div class="space-y-1">
                        <p class="px-4 text-[10px] font-semibold text-slate-400 dark:text-slate-500 uppercase tracking-wider mb-2">Sistem & Pengguna</p>
                        
                        <a href="<?= $base ?>database/db_settings" class="flex items-center px-4 py-2 text-slate-800 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800 rounded-lg transition-colors">
                            <i class="fa fa-sliders-h w-4 h-4 mr-4 text-indigo-900 dark:text-indigo-400"></i>
                            <span class="text-xs font-medium">Pengaturan Sistem</span>
                        </a>

                        <a href="<?= $base ?>database/db_users" class="flex items-center px-4 py-2 text-slate-800 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800 rounded-lg transition-colors">
                            <i class="fa fa-user-shield w-4 h-4 mr-4 text-amber-500 dark:text-amber-400"></i>
                            <span class="text-xs font-medium">Kelola Ketua Tingkat</span>
                        </a>

                    </div>

                    <!-- Section: Akun Pribadi -->
                    <div class="space-y-1">
                        <p class="px-4 text-[10px] font-semibold text-slate-400 dark:text-slate-500 uppercase tracking-wider mb-2">Akun</p>
                        
                        <a href="<?= $base ?>views/profile" class="flex items-center px-4 py-2 text-slate-800 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800 rounded-lg transition-colors">
                            <i class="fa fa-user w-4 h-4 mr-4 text-indigo-900 dark:text-indigo-400"></i>
                            <span class="text-xs font-medium">Profile Saya</span>
                        </a>

                        <a href="<?= $base ?>index" class="flex items-center px-4 py-2 text-slate-800 dark:text-slate-300 hover:bg-red-50 dark:hover:bg-red-900/30 hover:text-red-700 dark:hover:text-red-400 rounded-lg transition-colors">
                            <i class="fa fa-sign-out-alt w-4 h-4 mr-4 text-indigo-900 dark:text-indigo-400"></i>
                            <span class="text-xs font-medium">Logout</span>
                        </a>
                    </div>

                <!-- =========================================================
                     2. SIDEBAR ROLE: KETUA TINGKAT (OPERASIONAL KELAS)
                     ========================================================= -->
                <?php elseif ($role === 'Ketua Tingkat'): ?>

                    <!-- Dashboard Keti -->
                    <a href="<?= $base ?>dashboard_keti.php" class="flex items-center px-4 py-2 text-indigo-900 dark:text-indigo-400 rounded-lg hover:bg-slate-100 dark:hover:bg-indigo-900/50">
                        <i class="fa fa-home w-4 h-4 mr-4"></i>
                        <span class="text-xs font-semibold">Dashboard</span>
                    </a>

                    <!-- Section: Kelas Saya -->
                    <div class="space-y-1">
                        <p class="px-4 text-[10px] font-semibold text-slate-400 dark:text-slate-500 uppercase tracking-wider mb-2">Kelas Saya</p>
                        
                        <a href="<?= $base ?>database/db_mahasiswa" class="flex items-center px-4 py-2 text-slate-800 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800 rounded-lg transition-colors">
                            <i class="fa fa-user-graduate w-4 h-4 mr-4 text-indigo-900 dark:text-indigo-400"></i>
                            <span class="text-xs font-medium">Anggota Mahasiswa</span>
                        </a>

                        <a href="<?= $base ?>database/db_jadwal" class="flex items-center px-4 py-2 text-slate-800 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800 rounded-lg transition-colors">
                            <i class="fa fa-calendar-week w-4 h-4 mr-4 text-indigo-900 dark:text-indigo-400"></i>
                            <span class="text-xs font-medium">Jadwal Kuliah</span>
                        </a>

                        <a href="<?= $base ?>database/db_berita" class="flex items-center px-4 py-2 text-slate-800 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800 rounded-lg transition-colors">
                            <i class="fa fa-newspaper w-4 h-4 mr-4 text-indigo-900 dark:text-indigo-400"></i>
                            <span class="text-xs font-medium">Berita & Info</span>
                        </a>
                    </div>

                    <!-- Section: Aktivitas Kelas -->
                    <div class="space-y-1">
                        <p class="px-4 text-[10px] font-semibold text-slate-400 dark:text-slate-500 uppercase tracking-wider mb-2">Aktivitas Kelas</p>
                        
                        <a href="<?= $base ?>database/db_tugas" class="flex items-center px-4 py-2 text-slate-800 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800 rounded-lg transition-colors">
                            <i class="fa fa-tasks w-4 h-4 mr-4 text-indigo-900 dark:text-indigo-400"></i>
                            <span class="text-xs font-medium">Tugas Kuliah</span>
                        </a>

                        <a href="<?= $base ?>views/kelompok" class="flex items-center px-4 py-2 text-slate-800 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800 rounded-lg transition-colors">
                            <i class="fa fa-users w-4 h-4 mr-4 text-indigo-900 dark:text-indigo-400"></i>
                            <span class="text-xs font-medium">Kelompok</span>
                        </a>

                        <a href="<?= $base ?>views/piket_proyektor" class="flex items-center px-4 py-2 text-slate-800 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800 rounded-lg transition-colors">
                            <i class="fa fa-plug w-4 h-4 mr-4 text-indigo-900 dark:text-indigo-400"></i>
                            <span class="text-xs font-medium">Piket Proyektor</span>
                        </a>

                        <a href="<?= $base ?>views/kalender_view" class="flex items-center px-4 py-2 text-slate-800 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800 rounded-lg transition-colors">
                            <i class="fa fa-calendar w-4 h-4 mr-4 text-indigo-900 dark:text-indigo-400"></i>
                            <span class="text-xs font-medium">Kalender</span>
                        </a>
                    </div>

                    <!-- Section: Monitoring Kelas -->
                    <div class="space-y-1">
                        <p class="px-4 text-[10px] font-semibold text-slate-400 dark:text-slate-500 uppercase tracking-wider mb-2">Monitoring</p>
                        
                        <a href="<?= $base ?>views/rekap_absen" class="flex items-center px-4 py-2 text-slate-800 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800 rounded-lg transition-colors">
                            <i class="fa fa-clipboard-list w-4 h-4 mr-4 text-indigo-900 dark:text-indigo-400"></i>
                            <span class="text-xs font-medium">Rekap Absensi</span>
                        </a>

                        <a href="<?= $base ?>views/rekap_kas" class="flex items-center px-4 py-2 text-slate-800 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800 rounded-lg transition-colors">
                            <i class="fa fa-wallet w-4 h-4 mr-4 text-indigo-900 dark:text-indigo-400"></i>
                            <span class="text-xs font-medium">Rekap Kas</span>
                        </a>

                        <button class="submenu-button flex items-center justify-between w-full px-4 py-2 text-slate-800 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800 rounded-lg transition-colors">
                            <div class="flex items-center">
                                <i class="fa fa-id-card w-4 h-4 mr-4 text-indigo-900 dark:text-indigo-400"></i>
                                <span class="text-xs font-medium">KRS Mahasiswa</span>
                            </div>
                            <i class="fa fa-chevron-down submenu-arrow w-3 h-3 transition-transform duration-200"></i>
                        </button>
                        <div class="submenu pl-1 space-y-1 hidden overflow-y-auto max-h-52 border-l-2 border-slate-100 dark:border-slate-800 ml-6">
                            <a href="<?= $base ?>actions/validasi_krs" class="block px-4 py-1.5 text-xs text-slate-600 dark:text-slate-400 hover:text-indigo-600 dark:hover:text-indigo-400 hover:bg-slate-50 dark:hover:bg-indigo-950/30 rounded-lg">Validasi KRS</a>
                            <a href="<?= $base ?>actions/bulk_krs" class="block px-4 py-1.5 text-xs font-semibold text-indigo-600 dark:text-indigo-400 hover:text-indigo-800 dark:hover:text-indigo-300 hover:bg-indigo-50 dark:hover:bg-indigo-950/30 rounded-lg">KRS Massal</a>
                        </div>

                        <a href="<?= $base ?>views/reminder" class="flex items-center px-4 py-2 text-slate-800 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800 rounded-lg transition-colors">
                            <i class="fa fa-bell w-4 h-4 mr-4 text-indigo-900 dark:text-indigo-400"></i>
                            <span class="text-xs font-medium">Notifikasi</span>
                        </a>
                    </div>

                    <!-- Section: Akun Pengurus -->
                    <div class="space-y-1">
                        <p class="px-4 text-[10px] font-semibold text-slate-400 dark:text-slate-500 uppercase tracking-wider mb-2">Akun</p>
                        
                        <a href="<?= $base ?>views/profile" class="flex items-center px-4 py-2 text-slate-800 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800 rounded-lg transition-colors">
                            <i class="fa fa-user w-4 h-4 mr-4 text-indigo-900 dark:text-indigo-400"></i>
                            <span class="text-xs font-medium">Profile Saya</span>
                        </a>

                        <a href="<?= $base ?>index" class="flex items-center px-4 py-2 text-slate-800 dark:text-slate-300 hover:bg-red-50 dark:hover:bg-red-900/30 hover:text-red-700 dark:hover:text-red-400 rounded-lg transition-colors">
                            <i class="fa fa-sign-out-alt w-4 h-4 mr-4 text-indigo-900 dark:text-indigo-400"></i>
                            <span class="text-xs font-medium">Logout</span>
                        </a>
                    </div>

                <!-- =========================================================
                     3. SIDEBAR ROLE: SEKRETARIS (ADMINISTRASI ABSENSI & TUGAS)
                     ========================================================= -->
                <?php elseif ($role === 'Sekretaris'): ?>

                    <!-- Dashboard Sekretaris -->
                    <a href="<?= $base ?>dashboard_sekretaris.php" class="flex items-center px-4 py-2 text-indigo-900 dark:text-indigo-400 rounded-lg hover:bg-slate-100 dark:hover:bg-indigo-900/50">
                        <i class="fa fa-home w-4 h-4 mr-4"></i>
                        <span class="text-xs font-semibold">Dashboard</span>
                    </a>

                    <!-- Section: Administrasi Absensi -->
                    <div class="space-y-1">
                        <p class="px-4 text-[10px] font-semibold text-slate-400 dark:text-slate-500 uppercase tracking-wider mb-2">Administrasi Absensi</p>
                        
                        <a href="<?= $base ?>actions/tambah_absensi" class="flex items-center px-4 py-2 text-slate-800 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800 rounded-lg transition-colors">
                            <i class="fa fa-calendar-check w-4 h-4 mr-4 text-indigo-900 dark:text-indigo-400"></i>
                            <span class="text-xs font-medium">Input Absensi</span>
                        </a>

                        <a href="<?= $base ?>views/manajemen_absen" class="flex items-center px-4 py-2 text-slate-800 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800 rounded-lg transition-colors">
                            <i class="fa fa-table-list w-4 h-4 mr-4 text-indigo-900 dark:text-indigo-400"></i>
                            <span class="text-xs font-medium">Data Absen</span>
                        </a>

                        <a href="<?= $base ?>views/rekap_absen" class="flex items-center px-4 py-2 text-slate-800 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800 rounded-lg transition-colors">
                            <i class="fa fa-chart-line w-4 h-4 mr-4 text-indigo-900 dark:text-indigo-400"></i>
                            <span class="text-xs font-medium">Rekap Absensi</span>
                        </a>
                    </div>

                    <!-- Section: Manajemen Tugas -->
                    <div class="space-y-1">
                        <p class="px-4 text-[10px] font-semibold text-slate-400 dark:text-slate-500 uppercase tracking-wider mb-2">Manajemen Tugas</p>
                        
                        <a href="<?= $base ?>database/db_tugas" class="flex items-center px-4 py-2 text-slate-800 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800 rounded-lg transition-colors">
                            <i class="fa fa-tasks w-4 h-4 mr-4 text-indigo-900 dark:text-indigo-400"></i>
                            <span class="text-xs font-medium">Tugas Kuliah</span>
                        </a>
                    </div>

                    <!-- Section: Informasi Kelas -->
                    <div class="space-y-1">
                        <p class="px-4 text-[10px] font-semibold text-slate-400 dark:text-slate-500 uppercase tracking-wider mb-2">Informasi Kelas</p>
                        
                        <a href="<?= $base ?>database/db_mahasiswa" class="flex items-center px-4 py-2 text-slate-800 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800 rounded-lg transition-colors">
                            <i class="fa fa-user-graduate w-4 h-4 mr-4 text-indigo-900 dark:text-indigo-400"></i>
                            <span class="text-xs font-medium">Anggota Mahasiswa</span>
                        </a>

                        <a href="<?= $base ?>database/db_jadwal" class="flex items-center px-4 py-2 text-slate-800 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800 rounded-lg transition-colors">
                            <i class="fa fa-calendar-week w-4 h-4 mr-4 text-indigo-900 dark:text-indigo-400"></i>
                            <span class="text-xs font-medium">Jadwal Kuliah</span>
                        </a>

                        <a href="<?= $base ?>database/db_berita" class="flex items-center px-4 py-2 text-slate-800 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800 rounded-lg transition-colors">
                            <i class="fa fa-newspaper w-4 h-4 mr-4 text-indigo-900 dark:text-indigo-400"></i>
                            <span class="text-xs font-medium">Berita & Info</span>
                        </a>

                        <a href="<?= $base ?>views/kalender_view" class="flex items-center px-4 py-2 text-slate-800 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800 rounded-lg transition-colors">
                            <i class="fa fa-calendar w-4 h-4 mr-4 text-indigo-900 dark:text-indigo-400"></i>
                            <span class="text-xs font-medium">Kalender</span>
                        </a>
                    </div>

                    <!-- Section: Akun Pengurus -->
                    <div class="space-y-1">
                        <p class="px-4 text-[10px] font-semibold text-slate-400 dark:text-slate-500 uppercase tracking-wider mb-2">Akun</p>
                        
                        <a href="<?= $base ?>views/profile" class="flex items-center px-4 py-2 text-slate-800 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800 rounded-lg transition-colors">
                            <i class="fa fa-user w-4 h-4 mr-4 text-indigo-900 dark:text-indigo-400"></i>
                            <span class="text-xs font-medium">Profile Saya</span>
                        </a>

                        <a href="<?= $base ?>index" class="flex items-center px-4 py-2 text-slate-800 dark:text-slate-300 hover:bg-red-50 dark:hover:bg-red-900/30 hover:text-red-700 dark:hover:text-red-400 rounded-lg transition-colors">
                            <i class="fa fa-sign-out-alt w-4 h-4 mr-4 text-indigo-900 dark:text-indigo-400"></i>
                            <span class="text-xs font-medium">Logout</span>
                        </a>
                    </div>

                <!-- =========================================================
                     4. SIDEBAR ROLE: BENDAHARA (KEUANGAN & KAS KELAS)
                     ========================================================= -->
                <?php elseif ($role === 'Bendahara'): ?>

                    <!-- Dashboard Bendahara -->
                    <a href="<?= $base ?>dashboard_bendahara.php" class="flex items-center px-4 py-2 text-indigo-900 dark:text-indigo-400 rounded-lg hover:bg-slate-100 dark:hover:bg-indigo-900/50">
                        <i class="fa fa-home w-4 h-4 mr-4"></i>
                        <span class="text-xs font-semibold">Dashboard</span>
                    </a>

                    <!-- Section: Keuangan Kas Kelas -->
                    <div class="space-y-1">
                        <p class="px-4 text-[10px] font-semibold text-slate-400 dark:text-slate-500 uppercase tracking-wider mb-2">Kas & Keuangan</p>
                        
                        <button class="submenu-button flex items-center justify-between w-full px-4 py-2 text-slate-800 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800 rounded-lg transition-colors">
                            <div class="flex items-center">
                                <i class="fa fa-hand-holding-dollar w-4 h-4 mr-4 text-indigo-900 dark:text-indigo-400"></i>
                                <span class="text-xs font-medium">Input Kas</span>
                            </div>
                            <i class="fa fa-chevron-down submenu-arrow w-3 h-3 transition-transform duration-200"></i>
                        </button>
                        <div class="submenu pl-1 space-y-1 hidden overflow-y-auto max-h-52 border-l-2 border-slate-100 dark:border-slate-800 ml-6">
                            <a href="<?= $base ?>actions/tambah_kas_minggu" class="block px-4 py-1.5 text-xs text-slate-600 dark:text-slate-400 hover:text-indigo-600 dark:hover:text-indigo-400 hover:bg-slate-50 dark:hover:bg-indigo-950/30 rounded-lg">Input Per Minggu</a>
                            <a href="<?= $base ?>actions/tambah_kas_individu" class="block px-4 py-1.5 text-xs text-slate-600 dark:text-slate-400 hover:text-indigo-600 dark:hover:text-indigo-400 hover:bg-slate-50 dark:hover:bg-indigo-950/30 rounded-lg">Input Per Individu</a>
                        </div>

                        <a href="<?= $base ?>views/manajemen_kas" class="flex items-center px-4 py-2 text-slate-800 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800 rounded-lg transition-colors">
                            <i class="fa fa-wallet w-4 h-4 mr-4 text-indigo-900 dark:text-indigo-400"></i>
                            <span class="text-xs font-medium">Data & Arus Kas</span>
                        </a>

                        <a href="<?= $base ?>views/rekap_kas" class="flex items-center px-4 py-2 text-slate-800 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800 rounded-lg transition-colors">
                            <i class="fa fa-chart-pie w-4 h-4 mr-4 text-indigo-900 dark:text-indigo-400"></i>
                            <span class="text-xs font-medium">Rekap Kas</span>
                        </a>
                    </div>

                    <!-- Section: Informasi Kelas -->
                    <div class="space-y-1">
                        <p class="px-4 text-[10px] font-semibold text-slate-400 dark:text-slate-500 uppercase tracking-wider mb-2">Informasi Kelas</p>
                        
                        <a href="<?= $base ?>database/db_mahasiswa" class="flex items-center px-4 py-2 text-slate-800 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800 rounded-lg transition-colors">
                            <i class="fa fa-user-graduate w-4 h-4 mr-4 text-indigo-900 dark:text-indigo-400"></i>
                            <span class="text-xs font-medium">Anggota Mahasiswa</span>
                        </a>

                        <a href="<?= $base ?>database/db_jadwal" class="flex items-center px-4 py-2 text-slate-800 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800 rounded-lg transition-colors">
                            <i class="fa fa-calendar-week w-4 h-4 mr-4 text-indigo-900 dark:text-indigo-400"></i>
                            <span class="text-xs font-medium">Jadwal Kuliah</span>
                        </a>

                        <a href="<?= $base ?>views/kalender_view" class="flex items-center px-4 py-2 text-slate-800 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800 rounded-lg transition-colors">
                            <i class="fa fa-calendar w-4 h-4 mr-4 text-indigo-900 dark:text-indigo-400"></i>
                            <span class="text-xs font-medium">Kalender</span>
                        </a>
                    </div>

                    <!-- Section: Akun Pengurus -->
                    <div class="space-y-1">
                        <p class="px-4 text-[10px] font-semibold text-slate-400 dark:text-slate-500 uppercase tracking-wider mb-2">Akun</p>
                        
                        <a href="<?= $base ?>views/profile" class="flex items-center px-4 py-2 text-slate-800 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800 rounded-lg transition-colors">
                            <i class="fa fa-user w-4 h-4 mr-4 text-indigo-900 dark:text-indigo-400"></i>
                            <span class="text-xs font-medium">Profile Saya</span>
                        </a>

                        <a href="<?= $base ?>index" class="flex items-center px-4 py-2 text-slate-800 dark:text-slate-300 hover:bg-red-50 dark:hover:bg-red-900/30 hover:text-red-700 dark:hover:text-red-400 rounded-lg transition-colors">
                            <i class="fa fa-sign-out-alt w-4 h-4 mr-4 text-indigo-900 dark:text-indigo-400"></i>
                            <span class="text-xs font-medium">Logout</span>
                        </a>
                    </div>

                <!-- =========================================================
                     5. SIDEBAR ROLE: MAHASISWA & ROLE LAINNYA (STUDENT VIEW)
                     ========================================================= -->
                <?php else: ?>

                    <!-- Dashboard Mahasiswa -->
                    <a href="<?= $base ?>dashboard_mahasiswa.php" class="flex items-center px-4 py-2 text-indigo-900 dark:text-indigo-400 rounded-lg hover:bg-slate-100 dark:hover:bg-indigo-900/50">
                        <i class="fa fa-home w-4 h-4 mr-4"></i>
                        <span class="text-xs font-semibold">Dashboard</span>
                    </a>

                    <!-- Section: Perkuliahan Saya -->
                    <div class="space-y-1">
                        <p class="px-4 text-[10px] font-semibold text-slate-400 dark:text-slate-500 uppercase tracking-wider mb-2">Akademik Saya</p>
                        
                        <a href="<?= $base ?>database/db_jadwal" class="flex items-center px-4 py-2 text-slate-800 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800 rounded-lg transition-colors">
                            <i class="fa fa-calendar-week w-4 h-4 mr-4 text-indigo-900 dark:text-indigo-400"></i>
                            <span class="text-xs font-medium">Jadwal Kuliah</span>
                        </a>

                        <a href="<?= $base ?>views/tugas_saya" class="flex items-center px-4 py-2 text-slate-800 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800 rounded-lg transition-colors">
                            <i class="fa fa-tasks w-4 h-4 mr-4 text-indigo-900 dark:text-indigo-400"></i>
                            <span class="text-xs font-medium">Tugas Saya</span>
                        </a>

                        <a href="<?= $base ?>views/kelompok" class="flex items-center px-4 py-2 text-slate-800 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800 rounded-lg transition-colors">
                            <i class="fa fa-users w-4 h-4 mr-4 text-indigo-900 dark:text-indigo-400"></i>
                            <span class="text-xs font-medium">Kelompok</span>
                        </a>

                        <a href="<?= $base ?>views/piket_proyektor" class="flex items-center px-4 py-2 text-slate-800 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800 rounded-lg transition-colors">
                            <i class="fa fa-plug w-4 h-4 mr-4 text-indigo-900 dark:text-indigo-400"></i>
                            <span class="text-xs font-medium">Piket Proyektor</span>
                        </a>

                        <a href="<?= $base ?>views/kalender_view" class="flex items-center px-4 py-2 text-slate-800 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800 rounded-lg transition-colors">
                            <i class="fa fa-calendar w-4 h-4 mr-4 text-indigo-900 dark:text-indigo-400"></i>
                            <span class="text-xs font-medium">Kalender</span>
                        </a>
                    </div>

                    <!-- Section: Rekapitulasi Saya -->
                    <div class="space-y-1">
                        <p class="px-4 text-[10px] font-semibold text-slate-400 dark:text-slate-500 uppercase tracking-wider mb-2">Status Saya</p>
                        
                        <a href="<?= $base ?>views/rekap_absen" class="flex items-center px-4 py-2 text-slate-800 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800 rounded-lg transition-colors">
                            <i class="fa fa-calendar-check w-4 h-4 mr-4 text-indigo-900 dark:text-indigo-400"></i>
                            <span class="text-xs font-medium">Kehadiran Saya</span>
                        </a>

                        <a href="<?= $base ?>views/rekap_kas" class="flex items-center px-4 py-2 text-slate-800 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800 rounded-lg transition-colors">
                            <i class="fa fa-wallet w-4 h-4 mr-4 text-indigo-900 dark:text-indigo-400"></i>
                            <span class="text-xs font-medium">Kas Kelas</span>
                        </a>

                        <a href="<?= $base ?>database/db_berita" class="flex items-center px-4 py-2 text-slate-800 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800 rounded-lg transition-colors">
                            <i class="fa fa-newspaper w-4 h-4 mr-4 text-indigo-900 dark:text-indigo-400"></i>
                            <span class="text-xs font-medium">Berita & Info</span>
                        </a>
                    </div>

                    <!-- Section: Akun & KRS -->
                    <div class="space-y-1">
                        <p class="px-4 text-[10px] font-semibold text-slate-400 dark:text-slate-500 uppercase tracking-wider mb-2">Akun Mahasiswa</p>
                        
                        <button class="submenu-button flex items-center justify-between w-full px-4 py-2 text-slate-800 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800 rounded-lg transition-colors">
                            <div class="flex items-center">
                                <i class="fa fa-id-card w-4 h-4 mr-4 text-indigo-900 dark:text-indigo-400"></i>
                                <span class="text-xs font-medium">KRS & KSM</span>
                            </div>
                            <i class="fa fa-chevron-down submenu-arrow w-3 h-3 transition-transform duration-200"></i>
                        </button>
                        <div class="submenu pl-1 space-y-1 hidden overflow-y-auto max-h-52 border-l-2 border-slate-100 dark:border-slate-800 ml-6">
                            <a href="<?= $base ?>actions/edit_krs" class="block px-4 py-1.5 text-xs text-slate-600 dark:text-slate-400 hover:text-indigo-600 dark:hover:text-indigo-400 hover:bg-slate-50 dark:hover:bg-indigo-950/30 rounded-lg">KRS Mahasiswa</a>
                            <a href="<?= $base ?>views/ksm" class="block px-4 py-1.5 text-xs text-slate-600 dark:text-slate-400 hover:text-indigo-600 dark:hover:text-indigo-400 hover:bg-slate-50 dark:hover:bg-indigo-950/30 rounded-lg">KSM Mahasiswa</a>
                        </div>

                        <a href="<?= $base ?>views/profile" class="flex items-center px-4 py-2 text-slate-800 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800 rounded-lg transition-colors">
                            <i class="fa fa-user w-4 h-4 mr-4 text-indigo-900 dark:text-indigo-400"></i>
                            <span class="text-xs font-medium">Profile Saya</span>
                        </a>

                        <a href="<?= $base ?>index" class="flex items-center px-4 py-2 text-slate-800 dark:text-slate-300 hover:bg-red-50 dark:hover:bg-red-900/30 hover:text-red-700 dark:hover:text-red-400 rounded-lg transition-colors">
                            <i class="fa fa-sign-out-alt w-4 h-4 mr-4 text-indigo-900 dark:text-indigo-400"></i>
                            <span class="text-xs font-medium">Logout</span>
                        </a>
                    </div>

                <?php endif; ?>

            </div>
        </nav>
    </div>
</aside>
