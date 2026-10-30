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
</style>
<!-- Header dengan z-index lebih tinggi -->
<header class="bg-indigo-950  fixed w-full top-0 z-50">
    <div class="flex justify-between items-center px-6 py-2">
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

<!-- Sidebar dengan z-index di bawah header -->
<aside id="sidebar"
    class="fixed left-0 top-0 h-screen w-48 bg-white dark:bg-slate-900 border-r border-slate-200 dark:border-slate-800 transform -translate-x-full lg:translate-x-0 transition-transform duration-200 ease-in-out z-40">
    <!-- Tambahan padding top agar tidak tertutup header -->
    <div class="pt-16">
        <nav class="mt-4 h-[calc(100vh-4rem)] overflow-y-auto pb-10">
            <div class="px-4 space-y-2">
                <!-- Dashboard Menu -->
                <a href="<?= $base ?>dashboard" class="flex items-center px-4 py-2 text-indigo-900 dark:text-indigo-400 rounded-lg hover:bg-slate-100 dark:hover:bg-indigo-900/50">
                    <i class="fa fa-home w-4 h-4 mr-4"></i>
                    <span class="text-xs font-medium">Dashboard</span>
                </a>

                <!-- Components Menu -->
                <div class="space-y-1">
                    <div>
                        <p class="px-4 text-[10px] font-semibold text-slate-400 dark:text-slate-500 uppercase tracking-wider mb-2">Akademik</p>
                        <div class="space-y-1">
                            <button class="submenu-button flex items-center justify-between w-full px-4 py-1 text-slate-800 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800 rounded-lg transition-colors">
                                <div class="flex items-center">
                                    <i class="fa fa-university w-5 h-5 mr-3 text-indigo-900 dark:text-indigo-400"></i>
                                    <span class="text-xs font-medium">Master</span>
                                </div>
                                <i class="fa fa-chevron-down submenu-arrow w-3 h-3 transition-transform duration-200"></i>
                            </button>
                            <div class="submenu pl-1 space-y-1 hidden overflow-y-auto max-h-52 border-l-2 border-slate-100 dark:border-slate-800 ml-6">
                                <a href="<?= $base ?>database/db_mahasiswa" class="block px-4 py-1 text-xs text-slate-600 dark:text-slate-400 hover:text-indigo-600 dark:hover:text-indigo-400 hover:bg-slate-50 dark:hover:bg-indigo-950/30 rounded-lg">Mahasiswa</a>
                                <a href="<?= $base ?>database/db_dosen" class="block px-4 py-1 text-xs text-slate-600 dark:text-slate-400 hover:text-indigo-600 dark:hover:text-indigo-400 hover:bg-slate-50 dark:hover:bg-indigo-950/30 rounded-lg">Dosen</a>
                                <a href="<?= $base ?>database/db_matkul" class="block px-4 py-1 text-xs text-slate-600 dark:text-slate-400 hover:text-indigo-600 dark:hover:text-indigo-400 hover:bg-slate-50 dark:hover:bg-indigo-950/30 rounded-lg">Mata Kuliah</a>
                                <a href="<?= $base ?>database/db_jadwal" class="block px-4 py-1 text-xs text-slate-600 dark:text-slate-400 hover:text-indigo-600 dark:hover:text-indigo-400 hover:bg-slate-50 dark:hover:bg-indigo-950/30 rounded-lg">Jadwal Kuliah</a>
                                <a href="<?= $base ?>database/db_berita" class="block px-4 py-1 text-xs text-slate-600 dark:text-slate-400 hover:text-indigo-600 dark:hover:text-indigo-400 hover:bg-slate-50 dark:hover:bg-indigo-950/30 rounded-lg">Berita & Info</a>
                                <a href="<?= $base ?>database/db_kalender" class="block px-4 py-1 text-xs text-slate-600 dark:text-slate-400 hover:text-indigo-600 dark:hover:text-indigo-400 hover:bg-slate-50 dark:hover:bg-indigo-950/30 rounded-lg">Kalender Akademik</a>
                            </div>

                            <button class="submenu-button flex items-center justify-between w-full px-4 py-1 text-slate-800 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800 rounded-lg transition-colors">
                                <div class="flex items-center">
                                    <i class="fa fa-tasks w-5 h-5 mr-3 text-indigo-900 dark:text-indigo-400"></i>
                                    <span class="text-xs font-medium">Tugas</span>
                                </div>
                                <i class="fa fa-chevron-down submenu-arrow w-3 h-3 transition-transform duration-200"></i>
                            </button>
                            <div class="submenu pl-1 space-y-1 hidden overflow-y-auto max-h-52 border-l-2 border-slate-100 dark:border-slate-800 ml-6">
                                <a href="<?= $base ?>database/db_tugas" class="block px-4 py-1 text-xs text-slate-600 dark:text-slate-400 hover:text-indigo-600 dark:hover:text-indigo-400 hover:bg-slate-50 dark:hover:bg-indigo-950/30 rounded-lg">Tugas Kuliah</a>
                                <a href="<?= $base ?>views/tugas_saya" class="block px-4 py-1 text-xs text-slate-600 dark:text-slate-400 hover:text-indigo-600 dark:hover:text-indigo-400 hover:bg-slate-50 dark:hover:bg-indigo-950/30 rounded-lg">Tugas Saya</a>
                            </div>

                            <a href="<?= $base ?>views/kelompok" class="flex items-center px-4 py-1 text-slate-800 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800 rounded-lg transition-colors">
                                <i class="fa fa-users w-5 h-5 mr-3 text-indigo-900 dark:text-indigo-400"></i>
                                <span class="text-xs font-medium">Kelompok</span>
                            </a>

                            <a href="<?= $base ?>views/piket_proyektor" class="flex items-center px-4 py-1 text-slate-800 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800 rounded-lg transition-colors">
                                <i class="fa fa-plug w-5 h-5 mr-3 text-indigo-900 dark:text-indigo-400"></i>
                                <span class="text-xs font-medium">Piket Proyektor</span>
                            </a>

                            <a href="<?= $base ?>views/kalender_view" class="flex items-center px-4 py-1 text-slate-800 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800 rounded-lg transition-colors">
                                <i class="fa fa-calendar w-5 h-5 mr-3 text-indigo-900 dark:text-indigo-400"></i>
                                <span class="text-xs font-medium">Kalender</span>
                            </a>
                        </div>
                    </div>

                    <div>
                        <p class="px-4 text-[10px] font-semibold text-slate-400 dark:text-slate-500 uppercase tracking-wider mb-2">Administrasi</p>
                        <div class="space-y-1">
                            <button class="submenu-button flex items-center justify-between w-full px-4 py-1 text-slate-800 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800 rounded-lg transition-colors">
                                <div class="flex items-center">
                                    <i class="fa fa-clipboard-list w-5 h-5 mr-3 text-indigo-900 dark:text-indigo-400"></i>
                                    <span class="text-xs font-medium">Absensi</span>
                                </div>
                                <i class="fa fa-chevron-down submenu-arrow w-3 h-3 transition-transform duration-200"></i>
                            </button>
                            <div class="submenu pl-1 space-y-1 hidden overflow-y-auto max-h-52 border-l-2 border-slate-100 dark:border-slate-800 ml-6">

                                <a href="<?= $base ?>views/rekap_absen" class="block px-4 py-1 text-xs text-slate-600 dark:text-slate-400 hover:text-indigo-600 dark:hover:text-indigo-400 hover:bg-slate-50 dark:hover:bg-indigo-950/30 rounded-lg">Rekap Absen</a>
                                <?php if ($role === 'Administrator Utama' || $role === 'Sekretaris'): ?>
                                    <a href="<?= $base ?>views/manajemen_absen" class="block px-4 py-1 text-xs text-slate-600 dark:text-slate-400 hover:text-indigo-600 dark:hover:text-indigo-400 hover:bg-slate-50 dark:hover:bg-indigo-950/30 rounded-lg">Data Absen</a>
                                    <a href="<?= $base ?>actions/tambah_absensi" class="block px-4 py-1 text-xs text-slate-600 dark:text-slate-400 hover:text-indigo-600 dark:hover:text-indigo-400 hover:bg-slate-50 dark:hover:bg-indigo-950/30 rounded-lg">Input Absen</a>
                                <?php endif; ?>
                            </div>

                            <button class="submenu-button flex items-center justify-between w-full px-4 py-1 text-slate-800 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800 rounded-lg transition-colors">
                                <div class="flex items-center">
                                    <i class="fa fa-wallet w-5 h-5 mr-3 text-indigo-900 dark:text-indigo-400"></i>
                                    <span class="text-xs font-medium">Keuangan</span>
                                </div>
                                <i class="fa fa-chevron-down submenu-arrow w-3 h-3 transition-transform duration-200"></i>
                            </button>
                            <div class="submenu pl-1 space-y-1 hidden overflow-y-auto max-h-52 border-l-2 border-slate-100 dark:border-slate-800 ml-6">

                                <a href="<?= $base ?>views/rekap_kas" class="block px-4 py-1 text-xs text-slate-600 dark:text-slate-400 hover:text-indigo-600 dark:hover:text-indigo-400 hover:bg-slate-50 dark:hover:bg-indigo-950/30 rounded-lg">Rekap Kas</a>
                                <?php if ($role === 'Administrator Utama' || $role === 'Bendahara'): ?>
                                    <a href="<?= $base ?>views/manajemen_kas" class="block px-4 py-1 text-xs text-slate-600 dark:text-slate-400 hover:text-indigo-600 dark:hover:text-indigo-400 hover:bg-slate-50 dark:hover:bg-indigo-950/30 rounded-lg">Data Kas</a>
                                    <a href="<?= $base ?>actions/tambah_kas_minggu" class="block px-4 py-1 text-xs text-slate-600 dark:text-slate-400 hover:text-indigo-600 dark:hover:text-indigo-400 hover:bg-slate-50 dark:hover:bg-indigo-950/30 rounded-lg">Input Per Minggu</a>
                                    <a href="<?= $base ?>actions/tambah_kas_individu" class="block px-4 py-1 text-xs text-slate-600 dark:text-slate-400 hover:text-indigo-600 dark:hover:text-indigo-400 hover:bg-slate-50 dark:hover:bg-indigo-950/30 rounded-lg">Input Per Individu</a>
                                <?php endif; ?>
                            </div>


                        </div>
                    </div>

                    

                    <div>
                        <p class="px-4 text-[10px] font-semibold text-slate-400 dark:text-slate-500 uppercase tracking-wider mb-2">Pengaturan</p>
                        <div class="space-y-1">
                            <button class="submenu-button flex items-center justify-between w-full px-4 py-1 text-slate-800 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800 rounded-lg transition-colors">
                                <div class="flex items-center">
                                    <i class="fa fa-user w-5 h-5 mr-3 text-indigo-900 dark:text-indigo-400"></i>
                                    <span class="text-xs font-medium">Akun</span>
                                </div>
                                <i class="fa fa-chevron-down submenu-arrow w-3 h-3 transition-transform duration-200"></i>
                            </button>
                            <div class="submenu pl-1 space-y-1 hidden overflow-y-auto max-h-52 border-l-2 border-slate-100 dark:border-slate-800 ml-6">
                                <a href="<?= $base ?>views/profile" class="block px-4 py-1 text-xs text-slate-600 dark:text-slate-400 hover:text-indigo-600 dark:hover:text-indigo-400 hover:bg-slate-50 dark:hover:bg-indigo-950/30 rounded-lg">Profile Saya</a>
                                <a href="<?= $base ?>actions/edit_krs" class="block px-4 py-1 text-xs text-slate-600 dark:text-slate-400 hover:text-indigo-600 dark:hover:text-indigo-400 hover:bg-slate-50 dark:hover:bg-indigo-950/30 rounded-lg">KRS Mahasiswa</a>
                                <a href="<?= $base ?>views/ksm" class="block px-4 py-1 text-xs text-slate-600 dark:text-slate-400 hover:text-indigo-600 dark:hover:text-indigo-400 hover:bg-slate-50 dark:hover:bg-indigo-950/30 rounded-lg">KSM Mahasiswa</a>
                            </div>
                            <?php if ($role === 'Administrator Utama'): ?>
                                <button class="submenu-button flex items-center justify-between w-full px-4 py-1 text-slate-800 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800 rounded-lg transition-colors">
                                    <div class="flex items-center">
                                        <i class="fa-solid fa-earth-americas w-5 h-5 mr-3 text-indigo-900 dark:text-indigo-400"></i>
                                        <span class="text-xs font-medium">Website</span>
                                    </div>
                                    <i class="fa fa-chevron-down submenu-arrow w-3 h-3 transition-transform duration-200"></i>
                                </button>
                                <div class="submenu pl-1 space-y-1 hidden overflow-y-auto max-h-52 border-l-2 border-slate-100 dark:border-slate-800 ml-6">
                                    <a href="<?= $base ?>database/db_settings" class="block px-4 py-1 text-xs font-semibold text-amber-600 dark:text-amber-400 hover:text-indigo-600 dark:hover:text-indigo-400 hover:bg-slate-50 dark:hover:bg-indigo-950/30 rounded-lg flex items-center justify-between">
                                        <span>Pengaturan Sistem</span>
                                        <i class="fas fa-gear text-[10px]"></i>
                                    </a>
                                    <a href="<?= $base ?>database/db_users" class="block px-4 py-1 text-xs text-slate-600 dark:text-slate-400 hover:text-indigo-600 dark:hover:text-indigo-400 hover:bg-slate-50 dark:hover:bg-indigo-950/30 rounded-lg">Users</a>

                                    <a href="<?= $base ?>database/db_periode" class="block px-4 py-1 text-xs text-slate-600 dark:text-slate-400 hover:text-indigo-600 dark:hover:text-indigo-400 hover:bg-slate-50 dark:hover:bg-indigo-950/30 rounded-lg">Periode</a>
                                    <a href="<?= $base ?>database/db_ruangan" class="block px-4 py-1 text-xs text-slate-600 dark:text-slate-400 hover:text-indigo-600 dark:hover:text-indigo-400 hover:bg-slate-50 dark:hover:bg-indigo-950/30 rounded-lg">Ruangan</a>
                                    <a href="<?= $base ?>database/db_jam" class="block px-4 py-1 text-xs text-slate-600 dark:text-slate-400 hover:text-indigo-600 dark:hover:text-indigo-400 hover:bg-slate-50 dark:hover:bg-indigo-950/30 rounded-lg">Jam Kuliah</a>
                                    <a href="<?= $base ?>database/db_kelompok" class="block px-4 py-1 text-xs text-slate-600 dark:text-slate-400 hover:text-indigo-600 dark:hover:text-indigo-400 hover:bg-slate-50 dark:hover:bg-indigo-950/30 rounded-lg">Data Kelompok</a>
                                    <a href="<?= $base ?>actions/validasi_krs" class="block px-4 py-1 text-xs text-slate-600 dark:text-slate-400 hover:text-indigo-600 dark:hover:text-indigo-400 hover:bg-slate-50 dark:hover:bg-indigo-950/30 rounded-lg">Validasi KRS</a>
                                    <a href="<?= $base ?>actions/bulk_krs" class="block px-4 py-1 text-xs font-semibold text-indigo-600 dark:text-indigo-400 hover:text-indigo-800 dark:hover:text-indigo-300 hover:bg-indigo-50 dark:hover:bg-indigo-950/30 rounded-lg flex items-center justify-between">
                                        <span>KRS Massal</span>
                                        <i class="fas fa-layer-group text-[9px]"></i>
                                    </a>
                                </div>
                            <?php endif; ?>
                            <?php if ($role === 'Administrator Utama' || $role === 'Ketua Tingkat'): ?>
                                <a href="<?= $base ?>views/reminder" class="flex items-center px-4 py-1 text-slate-800 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800 rounded-lg transition-colors">
                                    <i class="fa fa-bell w-5 h-5 mr-3 text-indigo-900 dark:text-indigo-400"></i>
                                    <span class="text-xs font-medium">Notifikasi</span>
                                </a>
                            <?php endif; ?>

                            <a href="<?= $base ?>index" class="flex items-center px-4 py-1 text-slate-800 dark:text-slate-300 hover:bg-red-50 dark:hover:bg-red-900/30 hover:text-red-700 dark:hover:text-red-400 rounded-lg transition-colors">
                                <i class="fa fa-sign-out-alt w-5 h-5 mr-3 text-indigo-900 dark:text-indigo-400"></i>
                                <span class="text-xs font-medium">Logout</span>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </nav>
    </div>
</aside>