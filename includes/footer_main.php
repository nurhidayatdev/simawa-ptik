<?php
// Deteksi folder dasar untuk mendukung file di berbagai subfolder
if (!isset($base)) {
    $base = "";
    if (file_exists('koneksi.php')) {
        $base = "";
    } elseif (file_exists('../koneksi.php')) {
        $base = "../";
    } elseif (file_exists('../../koneksi.php')) {
        $base = "../../";
    }
}
?>
<!-- Footer -->
<footer class="bg-indigo-950 text-white pt-12 pb-8 mt-auto border-t border-indigo-900/40">
    <div class="container mx-auto px-4 md:px-6">
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-8 lg:gap-10">
            <!-- Kolom 1: Brand & Identitas SIMAWA PTIK 2024 -->
            <div class="space-y-4">
                <div class="flex items-center space-x-3">
                    <img src="<?= $base ?>assets/image/logo.webp" alt="Logo SIMAWA PTIK 2024" class="h-10 w-10 object-contain drop-shadow">
                    <div>
                        <h2 class="text-base font-extrabold tracking-tight text-white">SIMAWA PTIK 2024</h2>
                        <span class="inline-block text-[10px] font-bold uppercase tracking-widest text-indigo-300 bg-indigo-900/60 px-2 py-0.5 rounded-full border border-indigo-800/60">ProTechNoTy Class</span>
                    </div>
                </div>
                <p class="text-xs text-indigo-200/80 leading-relaxed">
                    Sistem Informasi & Manajemen Akademik Mahasiswa Pendidikan Teknik Informatika dan Komputer, Fakultas Teknik, Universitas Negeri Makassar.
                </p>
                <div class="flex items-center gap-3 pt-1">
                    <a href="https://www.instagram.com/ptikc_24" target="_blank" rel="noopener noreferrer" class="w-8 h-8 rounded-lg bg-indigo-900/80 hover:bg-indigo-800 text-indigo-300 hover:text-white flex items-center justify-center text-sm transition-all shadow-sm" title="Instagram Resmi">
                        <i class="fab fa-instagram"></i>
                    </a>
                    <a href="mailto:simawa.ptik2024@gmail.com" class="w-8 h-8 rounded-lg bg-indigo-900/80 hover:bg-indigo-800 text-indigo-300 hover:text-white flex items-center justify-center text-sm transition-all shadow-sm" title="Email Kami">
                        <i class="fas fa-envelope"></i>
                    </a>
                    <a href="<?= $base ?>info" class="w-8 h-8 rounded-lg bg-indigo-900/80 hover:bg-indigo-800 text-indigo-300 hover:text-white flex items-center justify-center text-sm transition-all shadow-sm" title="Informasi Terkini">
                        <i class="fas fa-bullhorn"></i>
                    </a>
                </div>
            </div>

            <!-- Kolom 2: Layanan Akademik -->
            <div>
                <h3 class="text-xs font-bold uppercase tracking-widest text-indigo-300 mb-4 pb-1 border-b border-indigo-900/60 flex items-center gap-2">
                    <i class="fas fa-graduation-cap text-indigo-400"></i> Akademik
                </h3>
                <ul class="space-y-2.5">
                    <li>
                        <a href="<?= $base ?>kalender" class="text-xs text-indigo-200/80 hover:text-white flex items-center gap-2 hover:translate-x-1 transition-all">
                            <i class="fas fa-chevron-right text-[9px] text-indigo-400"></i>
                            <span>Kalender Akademik</span>
                        </a>
                    </li>
                    <li>
                        <a href="<?= $base ?>matakuliah" class="text-xs text-indigo-200/80 hover:text-white flex items-center gap-2 hover:translate-x-1 transition-all">
                            <i class="fas fa-chevron-right text-[9px] text-indigo-400"></i>
                            <span>Kurikulum & Mata Kuliah</span>
                        </a>
                    </li>
                    <li>
                        <a href="<?= $base ?>jadwal" class="text-xs text-indigo-200/80 hover:text-white flex items-center gap-2 hover:translate-x-1 transition-all">
                            <i class="fas fa-chevron-right text-[9px] text-indigo-400"></i>
                            <span>Jadwal Perkuliahan</span>
                        </a>
                    </li>
                    <li>
                        <a href="<?= $base ?>tugas" class="text-xs text-indigo-200/80 hover:text-white flex items-center gap-2 hover:translate-x-1 transition-all">
                            <i class="fas fa-chevron-right text-[9px] text-indigo-400"></i>
                            <span>Daftar Tugas Kuliah</span>
                        </a>
                    </li>
                </ul>
            </div>

            <!-- Kolom 3: Informasi & Bantuan -->
            <div>
                <h3 class="text-xs font-bold uppercase tracking-widest text-indigo-300 mb-4 pb-1 border-b border-indigo-900/60 flex items-center gap-2">
                    <i class="fas fa-circle-info text-indigo-400"></i> Informasi & Bantuan
                </h3>
                <ul class="space-y-2.5">
                    <li>
                        <a href="<?= $base ?>info" class="text-xs text-indigo-200/80 hover:text-white flex items-center gap-2 hover:translate-x-1 transition-all">
                            <i class="fas fa-chevron-right text-[9px] text-indigo-400"></i>
                            <span>Informasi & Pengumuman</span>
                        </a>
                    </li>
                    <li>
                        <a href="<?= $base ?>panduan" class="text-xs text-indigo-200/80 hover:text-white flex items-center gap-2 hover:translate-x-1 transition-all">
                            <i class="fas fa-chevron-right text-[9px] text-indigo-400"></i>
                            <span>Panduan Sistem & Bot</span>
                        </a>
                    </li>
                    <li>
                        <a href="<?= $base ?>download" class="text-xs text-indigo-200/80 hover:text-white flex items-center gap-2 hover:translate-x-1 transition-all">
                            <i class="fas fa-chevron-right text-[9px] text-indigo-400"></i>
                            <span>Pusat Unduhan Aset</span>
                        </a>
                    </li>
                    <li>
                        <a href="<?= $base ?>tentang" class="text-xs text-indigo-200/80 hover:text-white flex items-center gap-2 hover:translate-x-1 transition-all">
                            <i class="fas fa-chevron-right text-[9px] text-indigo-400"></i>
                            <span>Tentang Platform</span>
                        </a>
                    </li>
                    <li>
                        <a href="<?= $base ?>login" class="text-xs text-indigo-200/80 hover:text-white flex items-center gap-2 hover:translate-x-1 transition-all">
                            <i class="fas fa-chevron-right text-[9px] text-indigo-400"></i>
                            <span>Portal Masuk (Login)</span>
                        </a>
                    </li>
                </ul>
            </div>

            <!-- Kolom 4: Kontak & Kampus -->
            <div>
                <h3 class="text-xs font-bold uppercase tracking-widest text-indigo-300 mb-4 pb-1 border-b border-indigo-900/60 flex items-center gap-2">
                    <i class="fas fa-location-dot text-indigo-400"></i> Kontak & Lokasi
                </h3>
                <ul class="space-y-3 text-xs text-indigo-200/80">
                    <li class="flex items-start gap-2.5">
                        <i class="fas fa-map-marker-alt text-sm text-indigo-400 mt-0.5 flex-shrink-0"></i>
                        <span>Kampus UNM Parangtambung, Jl. Mallengkeri Raya, Makassar, Sulawesi Selatan</span>
                    </li>
                    <li class="flex items-center gap-2.5">
                        <i class="fas fa-university text-sm text-indigo-400 flex-shrink-0"></i>
                        <span>Jurusan PTIK, Fakultas Teknik UNM</span>
                    </li>
                    <li class="flex items-center gap-2.5">
                        <i class="fas fa-envelope text-sm text-indigo-400 flex-shrink-0"></i>
                        <a href="mailto:simawa.ptik2024@gmail.com" class="hover:text-white transition-colors">simawa.ptik2024@gmail.com</a>
                    </li>
                    <li class="flex items-center gap-2.5">
                        <i class="fab fa-instagram text-sm text-indigo-400 flex-shrink-0"></i>
                        <a href="https://www.instagram.com/ptikc_24" target="_blank" rel="noopener noreferrer" class="hover:text-white transition-colors">@ptikc_24</a>
                    </li>
                </ul>
            </div>
        </div>
    </div>

    <!-- Copyright & Bottom Bar -->
    <div class="border-t border-indigo-900/60 mt-10 pt-6">
        <div class="container mx-auto px-4 md:px-6 flex flex-col md:flex-row items-center justify-between gap-3 text-xs text-indigo-300/70">
            <p>© <?= date("Y"); ?> SIMAWA PTIK 2024 — Pendidikan Teknik Informatika dan Komputer FT UNM. All rights reserved.</p>
            <p class="text-[11px] text-indigo-400/80">ProTechNoTy Class • Angkatan 2024</p>
        </div>
    </div>
</footer>
