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
<!-- Header dengan z-index lebih tinggi -->
<header class="bg-indigo-950  fixed w-full top-0 z-50">
    <div class="flex justify-between items-center px-6 py-2">
        <div class="flex items-center">
            <img src="<?= $base ?>assets/image/logo.webp" alt="Logo Universitas Negeri Makassar" class="h-10 w-10 mr-3">
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
                    <img src="<?= profilePhotoUrl($foto, $base) ?>" alt="Profile" class="w-8 h-8 rounded-full">
                </button>
                <div id="profile-dropdown" class="absolute right-0 mt-2 w-40 bg-white rounded-md shadow-lg hidden">
                    <a href="<?= $base ?>views/profile" class="block px-4 py-2 text-xs hover:bg-gray-100 font-semibold text-indigo-900"><i class="fa fa-user mr-2 text-indigo-900"></i>Profile</a>
                    <a href="<?= $base ?>index" class="block px-4 py-2 text-xs hover:bg-gray-100 font-semibold text-indigo-900"><i class="fa fa-sign-out mr-2 text-indigo-900"></i>Logout</a>
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
                <a href="<?= $base ?>dashboard" class="flex items-center px-4 py-2 text-indigo-900  rounded-lg hover:bg-slate-100">
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
                                <a href="<?= $base ?>database/db_mahasiswa" class="block px-4 py-1 text-xs text-slate-600 hover:text-indigo-600 hover:bg-slate-50 rounded-lg">Mahasiswa</a>
                                <a href="<?= $base ?>database/db_dosen" class="block px-4 py-1 text-xs text-slate-600 hover:text-indigo-600 hover:bg-slate-50 rounded-lg">Dosen</a>
                                <a href="<?= $base ?>database/db_matkul" class="block px-4 py-1 text-xs text-slate-600 hover:text-indigo-600 hover:bg-slate-50 rounded-lg">Mata Kuliah</a>
                                <a href="<?= $base ?>database/db_berita" class="block px-4 py-1 text-xs text-slate-600 hover:text-indigo-600 hover:bg-slate-50 rounded-lg">Berita & Info</a>
                                <a href="<?= $base ?>database/db_kalender" class="block px-4 py-1 text-xs text-slate-600 hover:text-indigo-600 hover:bg-slate-50 rounded-lg">Kalender Akademik</a>
                                <a href="<?= $base ?>database/db_galeri" class="block px-4 py-1 text-xs text-slate-600 hover:text-indigo-600 hover:bg-slate-50 rounded-lg">Galeri Kelas</a>
                            </div>

                            <a href="<?= $base ?>database/db_jadwal" class="flex items-center px-4 py-1 text-slate-800 hover:bg-slate-100 rounded-lg transition-colors">
                                <i class="fa fa-list-alt w-5 h-5 mr-3 text-indigo-900"></i>
                                <span class="text-xs font-medium">Jadwal Kuliah</span>
                            </a>

                            <a href="<?= $base ?>database/db_tugas" class="flex items-center px-4 py-1 text-slate-800 hover:bg-slate-100 rounded-lg transition-colors">
                                <i class="fa fa-tasks w-5 h-5 mr-3 text-indigo-900"></i>
                                <span class="text-xs font-medium">Tugas Kuliah</span>
                            </a>
                            <a href="<?= $base ?>views/tugas_saya" class="flex items-center px-4 py-1 text-slate-800 hover:bg-slate-100 rounded-lg transition-colors">
                                <i class="fa fa-check-square w-5 h-5 mr-3 text-indigo-900"></i>
                                <span class="text-xs font-medium">Tugas Saya</span>
                            </a>
                            <a href="<?= $base ?>views/kelompok" class="flex items-center px-4 py-1 text-slate-800 hover:bg-slate-100 rounded-lg transition-colors">
                                <i class="fa fa-users w-5 h-5 mr-3 text-indigo-900"></i>
                                <span class="text-xs font-medium">Kelompok</span>
                            </a>

                            <a href="<?= $base ?>views/piket_proyektor" class="flex items-center px-4 py-1 text-slate-800 hover:bg-slate-100 rounded-lg transition-colors">
                                <i class="fa fa-plug w-5 h-5 mr-3 text-indigo-900"></i>
                                <span class="text-xs font-medium">Piket Proyektor</span>
                            </a>

                            <a href="<?= $base ?>views/kalender_view" class="flex items-center px-4 py-1 text-slate-800 hover:bg-slate-100 rounded-lg transition-colors">
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
                                
                                <a href="<?= $base ?>views/rekap_absen" class="block px-4 py-1 text-xs text-slate-600 hover:text-indigo-600 hover:bg-slate-50 rounded-lg">Rekap Absen</a>
                                <?php if ($role === 'Administrator Utama' || $role === 'Sekretaris'): ?>
                                    <a href="<?= $base ?>views/manajemen_absen" class="block px-4 py-1 text-xs text-slate-600 hover:text-indigo-600 hover:bg-slate-50 rounded-lg">Data Absen</a>
                                    <a href="<?= $base ?>actions/tambah_absensi" class="block px-4 py-1 text-xs text-slate-600 hover:text-indigo-600 hover:bg-slate-50 rounded-lg">Input Absen</a>
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
                                
                                <a href="<?= $base ?>views/rekap_kas" class="block px-4 py-1 text-xs text-slate-600 hover:text-indigo-600 hover:bg-slate-50 rounded-lg">Rekap Kas</a>
                                <?php if ($role === 'Administrator Utama' || $role === 'Bendahara'): ?>
                                    <a href="<?= $base ?>views/manajemen_kas" class="block px-4 py-1 text-xs text-slate-600 hover:text-indigo-600 hover:bg-slate-50 rounded-lg">Data Kas</a>
                                    <a href="<?= $base ?>actions/tambah_kas_minggu" class="block px-4 py-1 text-xs text-slate-600 hover:text-indigo-600 hover:bg-slate-50 rounded-lg">Input Per Minggu</a>
                                    <a href="<?= $base ?>actions/tambah_kas_individu" class="block px-4 py-1 text-xs text-slate-600 hover:text-indigo-600 hover:bg-slate-50 rounded-lg">Input Per Individu</a>
                                <?php endif; ?>
                            </div>


                        </div>
                    </div>

                    <div>
                        <p class="px-4 text-[10px] font-semibold text-slate-400 uppercase tracking-wider mb-2">Aktivitas</p>
                        <div class="space-y-1">




                            <a href="<?= $base ?>views/misi" class="flex items-center px-4 py-1 text-slate-800 hover:bg-slate-100 rounded-lg transition-colors">
                                <i class="fa fa-trophy w-5 h-5 mr-3 text-indigo-900"></i>
                                <span class="text-xs font-medium">Pusat Misi</span>
                            </a>
                            <a href="<?= $base ?>views/kuis_harian" class="flex items-center px-4 py-1 text-slate-800 hover:bg-slate-100 rounded-lg transition-colors">
                                <i class="fa fa-gamepad w-5 h-5 mr-3 text-indigo-900"></i>
                                <span class="text-xs font-medium">Kuis Harian</span>
                            </a>
                            <a href="<?= $base ?>views/dinding_pesan" class="flex items-center px-4 py-1 text-slate-800 hover:bg-slate-100 rounded-lg transition-colors">
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
                                <a href="<?= $base ?>views/profile" class="block px-4 py-1 text-xs text-slate-600 hover:text-indigo-600 hover:bg-slate-50 rounded-lg">Profile Saya</a>
                                <a href="<?= $base ?>actions/edit_krs" class="block px-4 py-1 text-xs text-slate-600 hover:text-indigo-600 hover:bg-slate-50 rounded-lg">KRS Mahasiswa</a>
                                <a href="<?= $base ?>views/ksm" class="block px-4 py-1 text-xs text-slate-600 hover:text-indigo-600 hover:bg-slate-50 rounded-lg">KSM Mahasiswa</a>

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
                                    <a href="<?= $base ?>database/db_users" class="block px-4 py-1 text-xs text-slate-600 hover:text-indigo-600 hover:bg-slate-50 rounded-lg">Users</a>
                                    <a href="<?= $base ?>database/db_periode" class="block px-4 py-1 text-xs text-slate-600 hover:text-indigo-600 hover:bg-slate-50 rounded-lg">Periode</a>
                                    <a href="<?= $base ?>database/db_ruangan" class="block px-4 py-1 text-xs text-slate-600 hover:text-indigo-600 hover:bg-slate-50 rounded-lg">Ruangan</a>
                                    <a href="<?= $base ?>database/db_jam" class="block px-4 py-1 text-xs text-slate-600 hover:text-indigo-600 hover:bg-slate-50 rounded-lg">Jam Kuliah</a>
                                    <a href="<?= $base ?>database/db_kuis" class="block px-4 py-1 text-xs text-slate-600 hover:text-indigo-600 hover:bg-slate-50 rounded-lg">Kuis Harian</a>
                                    <a href="<?= $base ?>database/db_pesan" class="block px-4 py-1 text-xs text-slate-600 hover:text-indigo-600 hover:bg-slate-50 rounded-lg">Pesan Anonim</a>
                                    <a href="<?= $base ?>actions/validasi_krs" class="block px-4 py-1 text-xs text-slate-600 hover:text-indigo-600 hover:bg-slate-50 rounded-lg">Validasi KRS</a>
                                </div>
                            <?php endif; ?>
                            <?php if ($role === 'Administrator Utama' || $role === 'Ketua Tingkat'): ?>
                                <a href="<?= $base ?>views/reminder" class="flex items-center px-4 py-1 text-slate-800 hover:bg-slate-100 rounded-lg transition-colors">
                                    <i class="fa fa-bell w-5 h-5 mr-3 text-indigo-900"></i>
                                    <span class="text-xs font-medium">Notifikasi</span>
                                </a>
                            <?php endif; ?>

                            <a href="<?= $base ?>views/tentang" class="flex items-center px-4 py-1 text-slate-800 hover:bg-slate-100 rounded-lg transition-colors">
                                <i class="fa fa-info-circle w-5 h-5 mr-3 text-indigo-900"></i>
                                <span class="text-xs font-medium">Tentang</span>
                            </a>

                            <a href="<?= $base ?>index" class="flex items-center px-4 py-1 text-slate-800 hover:bg-red-50 hover:text-red-700 rounded-lg transition-colors">
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