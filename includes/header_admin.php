<?php
// Deteksi folder dasar untuk mendukung file di berbagai subfolder
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
$nama_lengkap = $_SESSION['nama_lengkap'] ?? 'Administrator Utama';
$foto = $_SESSION['foto'] ?? '';
?>
<!-- Header Khusus Administrator Utama (Executive Full-Width Navigation) -->
<header class="bg-indigo-950 fixed w-full top-0 z-50 border-b border-indigo-900/60 shadow-lg">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between items-center h-16">
            
            <!-- Brand / Logo -->
            <div class="flex items-center gap-3">
                <a href="<?= $base ?>dashboard_admin.php" class="flex items-center gap-3 group">
                    <img src="<?= $base ?>assets/image/logo.webp" alt="Logo Universitas Negeri Makassar" class="h-9 w-9 group-hover:scale-105 transition-transform">
                    <div class="flex flex-col">
                        <span class="text-base sm:text-lg font-black text-white tracking-tight leading-none">SIMAWA PTIK</span>
                        <span class="text-[10px] text-indigo-300 font-bold tracking-wider uppercase mt-0.5">Admin Console</span>
                    </div>
                </a>

                <?php if (function_exists('is_maintenance_mode') && is_maintenance_mode($koneksi)): ?>
                    <span class="hidden sm:inline-flex items-center gap-1.5 ml-2 px-2.5 py-0.5 rounded-full bg-rose-500/20 text-rose-300 border border-rose-500/40 text-[10px] font-black uppercase tracking-wider animate-pulse">
                        <i class="fas fa-screwdriver-wrench text-rose-400 text-[10px]"></i>
                        <span>Maintenance Aktif</span>
                    </span>
                <?php endif; ?>
            </div>

            <!-- Navigasi Tengah (Quick Links Eksekutif) -->
            <nav class="hidden md:flex items-center gap-1.5">
                <a href="<?= $base ?>dashboard_admin.php" class="px-3.5 py-1.5 text-xs font-bold text-white bg-indigo-800/80 hover:bg-indigo-700/80 rounded-xl transition-all flex items-center gap-1.5 shadow-sm">
                    <i class="fas fa-chart-pie text-[11px] text-indigo-300"></i>
                    <span>Dashboard</span>
                </a>

                <!-- Dropdown Master Global -->
                <div class="relative group" id="adminMasterDropdownContainer">
                    <button type="button" id="adminMasterDropdownBtn" class="px-3.5 py-1.5 text-xs font-semibold text-slate-200 hover:text-white hover:bg-white/10 rounded-xl transition-all flex items-center gap-1.5 cursor-pointer">
                        <i class="fas fa-database text-[11px] text-indigo-400"></i>
                        <span>Master Global</span>
                        <i class="fas fa-chevron-down text-[9px] text-slate-400 transition-transform group-hover:rotate-180"></i>
                    </button>
                    <div class="absolute left-0 mt-1.5 w-48 bg-white dark:bg-slate-800 rounded-xl shadow-xl border border-slate-200 dark:border-slate-700 py-1.5 hidden group-hover:block z-50">
                        <a href="<?= $base ?>database/db_dosen" class="block px-4 py-2 text-xs font-medium text-slate-700 dark:text-slate-200 hover:bg-indigo-50 dark:hover:bg-slate-700 hover:text-indigo-600 transition-colors">
                            <i class="fas fa-user-tie w-4 text-indigo-500 mr-2"></i> Master Dosen
                        </a>
                        <a href="<?= $base ?>database/db_matkul" class="block px-4 py-2 text-xs font-medium text-slate-700 dark:text-slate-200 hover:bg-indigo-50 dark:hover:bg-slate-700 hover:text-indigo-600 transition-colors">
                            <i class="fas fa-book-bookmark w-4 text-blue-500 mr-2"></i> Mata Kuliah
                        </a>
                        <a href="<?= $base ?>database/db_ruangan" class="block px-4 py-2 text-xs font-medium text-slate-700 dark:text-slate-200 hover:bg-indigo-50 dark:hover:bg-slate-700 hover:text-indigo-600 transition-colors">
                            <i class="fas fa-door-open w-4 text-emerald-500 mr-2"></i> Ruangan
                        </a>
                        <a href="<?= $base ?>database/db_jam" class="block px-4 py-2 text-xs font-medium text-slate-700 dark:text-slate-200 hover:bg-indigo-50 dark:hover:bg-slate-700 hover:text-indigo-600 transition-colors">
                            <i class="fas fa-clock w-4 text-amber-500 mr-2"></i> Jam Kuliah
                        </a>
                        <a href="<?= $base ?>database/db_periode" class="block px-4 py-2 text-xs font-medium text-slate-700 dark:text-slate-200 hover:bg-indigo-50 dark:hover:bg-slate-700 hover:text-indigo-600 transition-colors">
                            <i class="fas fa-calendar-days w-4 text-purple-500 mr-2"></i> Periode Akademik
                        </a>
                    </div>
                </div>

                <a href="<?= $base ?>database/db_users" class="px-3.5 py-1.5 text-xs font-semibold text-slate-200 hover:text-white hover:bg-white/10 rounded-xl transition-all flex items-center gap-1.5">
                    <i class="fas fa-users-gear text-[11px] text-indigo-400"></i>
                    <span>Kelola Akun</span>
                </a>

                <a href="<?= $base ?>database/db_settings" class="px-3.5 py-1.5 text-xs font-semibold text-slate-200 hover:text-white hover:bg-white/10 rounded-xl transition-all flex items-center gap-1.5">
                    <i class="fas fa-sliders-h text-[11px] text-indigo-400"></i>
                    <span>Pengaturan</span>
                </a>
            </nav>

            <!-- Aksi Kanan: Theme Toggle & Profile Dropdown -->
            <div class="flex items-center gap-3">
                
                <!-- Theme Toggle -->
                <button id="theme-toggle" class="relative inline-flex items-center h-7 w-12 flex-shrink-0 cursor-pointer rounded-full border border-white/20 bg-white/10 transition-all duration-300 ease-in-out focus:outline-none">
                    <span id="theme-toggle-thumb" class="pointer-events-none absolute left-0.5 h-5 w-5 transform rounded-full bg-white shadow-md transition duration-300 ease-in-out flex items-center justify-center dark:translate-x-5">
                        <i id="theme-toggle-light-icon" class="fa-solid fa-sun text-[10px] text-orange-500 transition-all duration-300 absolute"></i>
                        <i id="theme-toggle-dark-icon" class="fa-solid fa-moon text-[10px] text-indigo-900 transition-all duration-300 absolute opacity-0 scale-0"></i>
                    </span>
                </button>

                <!-- Profile Menu -->
                <div class="relative">
                    <button id="profile-button" class="flex items-center gap-2.5 p-1 rounded-xl hover:bg-white/10 transition-colors cursor-pointer">
                        <span class="hidden sm:inline text-xs font-bold text-white"><?= htmlspecialchars($nama_lengkap); ?></span>
                        <div class="w-8 h-8 rounded-full overflow-hidden border-2 border-indigo-400/60 bg-indigo-900 flex items-center justify-center text-white text-xs">
                            <?php if (!empty($foto)): ?>
                                <img src="<?= profilePhotoUrl($foto, $base) ?>" alt="Profile" class="w-full h-full object-cover">
                            <?php else: ?>
                                <i class="fas fa-user-shield"></i>
                            <?php endif; ?>
                        </div>
                        <i class="fas fa-chevron-down text-[10px] text-indigo-300 hidden sm:inline"></i>
                    </button>

                    <div id="profile-dropdown" class="absolute right-0 mt-2 w-48 bg-white dark:bg-slate-800 rounded-xl shadow-2xl hidden overflow-hidden border border-slate-200 dark:border-slate-700 py-1.5 z-50">
                        <div class="px-4 py-2 border-b border-slate-100 dark:border-slate-700">
                            <div class="text-xs font-bold text-slate-800 dark:text-white truncate"><?= htmlspecialchars($nama_lengkap); ?></div>
                            <div class="text-[10px] text-indigo-600 dark:text-indigo-400 font-extrabold uppercase mt-0.5">Administrator Utama</div>
                        </div>
                        <a href="<?= $base ?>views/profile" class="block px-4 py-2 text-xs hover:bg-slate-50 dark:hover:bg-slate-700 font-semibold text-slate-700 dark:text-slate-200 transition-colors">
                            <i class="fa fa-user mr-2 text-indigo-600 dark:text-indigo-400"></i> Profile Saya
                        </a>
                        <a href="<?= $base ?>database/db_settings" class="block px-4 py-2 text-xs hover:bg-slate-50 dark:hover:bg-slate-700 font-semibold text-slate-700 dark:text-slate-200 transition-colors">
                            <i class="fa fa-sliders-h mr-2 text-amber-500"></i> Pengaturan Global
                        </a>
                        <div class="border-t border-slate-100 dark:border-slate-700 my-1"></div>
                        <a href="<?= $base ?>index" class="block px-4 py-2 text-xs hover:bg-rose-50 dark:hover:bg-rose-950/40 font-bold text-rose-600 dark:text-rose-400 transition-colors">
                            <i class="fa fa-sign-out mr-2"></i> Logout
                        </a>
                    </div>
                </div>

            </div>

        </div>
    </div>
</header>
