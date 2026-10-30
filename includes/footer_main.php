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
<footer class="bg-indigo-950 text-white py-6 mt-auto">
    <div class="container mx-auto px-4">
        <div class="grid md:grid-cols-3 gap-8">
            <div>
                <h3 class="text-sm font-bold uppercase tracking-wider text-indigo-300 mb-3">Informasi & Bantuan</h3>
                <ul class="space-y-1.5">
                    <li><a href="<?= $base ?>info" class="hover:text-indigo-200 transition text-xs">Informasi Akademik</a></li>
                    <li><a href="<?= $base ?>panduan" class="hover:text-indigo-200 transition text-xs">Panduan Sistem</a></li>
                    <li><a href="<?= $base ?>tentang" class="hover:text-indigo-200 transition text-xs">Tentang Platform</a></li>
                </ul>
            </div>

            <div>
                <h3 class="text-sm font-bold uppercase tracking-wider text-indigo-300 mb-3">Akademik</h3>
                <ul class="space-y-1.5">
                    <li><a href="<?= $base ?>kalender" class="hover:text-indigo-200 transition text-xs">Kalender Akademik</a></li>
                    <li><a href="<?= $base ?>matakuliah" class="hover:text-indigo-200 transition text-xs">Kurikulum PTIK</a></li>
                    <li><a href="<?= $base ?>jadwal" class="hover:text-indigo-200 transition text-xs">Jadwal Perkuliahan</a></li>
                    <li><a href="<?= $base ?>tugas" class="hover:text-indigo-200 transition text-xs">Tugas Kuliah</a></li>
                </ul>
            </div>

            <div>
                <h3 class="text-sm font-bold uppercase tracking-wider text-indigo-300 mb-3">Kontak & Lokasi</h3>
                <p class="mb-2 text-xs"><i class="fas fa-map-marker-alt mr-2 text-sm text-indigo-400"></i> Jurusan PTIK, Fakultas Teknik UNM</p>
                <p class="mb-2 text-xs"><i class="fas fa-envelope mr-2 text-sm text-indigo-400"></i> simawa.ptik2024@gmail.com</p>
                <p class="mb-2 text-xs"><i class="fas fa-university mr-2 text-sm text-indigo-400"></i> Kampus UNM Parangtambung, Makassar</p>
            </div>
        </div>
    </div>

    <div class="border-t border-indigo-900 mt-6 pt-4 text-center text-xs mx-5 text-indigo-300/70">
        <p>© <?php echo date("Y"); ?> SIMAWA PTIK 2024 - Pendidikan Teknik Informatika dan Komputer FT UNM. All rights reserved.</p>
    </div>
</footer>
