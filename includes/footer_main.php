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
<footer class="bg-indigo-950 text-white py-4 mt-auto">
    <div class="container mx-auto px-4">
        <div class="grid md:grid-cols-4 gap-8">
            <div>
                <h3 class="text-lg font-bold mb-3">Informasi & Bantuan</h3>
                <ul class="space-y-1">
                    <li><a href="<?= $base ?>info" class="hover:text-indigo-200 transition text-xs">Info Kelas</a></li>
                    <li><a href="<?= $base ?>sejarah" class="hover:text-indigo-200 transition text-xs">Sejarah Kelas</a></li>
                    <li><a href="<?= $base ?>panduan" class="hover:text-indigo-200 transition text-xs">Panduan Sistem</a></li>
                    <li><a href="<?= $base ?>tentang" class="hover:text-indigo-200 transition text-xs">Tentang Platform</a></li>
                </ul>
            </div>

            <div>
                <h3 class="text-lg font-bold mb-3">Akademik</h3>
                <ul class="space-y-1">
                    <li><a href="<?= $base ?>kalender" class="hover:text-indigo-200 transition text-xs">Kalender Akademik</a></li>
                    <li><a href="<?= $base ?>matakuliah" class="hover:text-indigo-200 transition text-xs">Kurikulum PTIK</a></li>
                    <li><a href="<?= $base ?>jadwal" class="hover:text-indigo-200 transition text-xs">Jadwal Mata Kuliah</a></li>
                    <li><a href="<?= $base ?>tugas" class="hover:text-indigo-200 transition text-xs">Tugas Mata Kuliah</a></li>
                </ul>
            </div>

            <div>
                <h3 class="text-lg font-bold mb-3">Kontak</h3>
                <p class="mb-2 text-xs"><i class="fas fa-map-marker-alt mr-2 text-sm"></i> Jl. A.P. Pettarani, Makassar</p>
                <p class="mb-2 text-xs"><i class="fas fa-envelope mr-2 text-sm"></i> ptikc2024@gmail.com</p>
                <a href="https://www.instagram.com/ptikc_24">
                    <p class="mb-2 text-xs"><i class="fab fa-instagram mr-2 text-sm"></i></i> @ptikc_24</p>
                </a>

            </div>
            <div>
                <h3 class="text-lg font-bold mb-3">Kirim Pesan Anonim</h3>

                <form action="" method="POST" class="space-y-2" id="footer_form">
                    <textarea
                        name="pesan_anonim"
                        rows="3"
                        maxlength="300"
                        class="w-full p-3 rounded-lg bg-indigo-900/50 border border-indigo-800 text-white text-xs focus:outline-none focus:ring-2 focus:ring-indigo-500 placeholder-indigo-300/50"
                        placeholder="Tulis pesan rahasia atau saran di sini..."
                        required></textarea>
                    <button
                        type="submit"
                        name="kirim_pesan"
                        class="w-full bg-indigo-600 hover:bg-indigo-500 text-white py-2 rounded-lg text-xs font-semibold transition flex items-center justify-center space-x-2">
                        <i class="fas fa-paper-plane text-xs"></i>
                        <span>Kirim Sekarang</span>
                    </button>
                </form>

                <?php
                if (isset($_GET['status'])) {
                    if ($_GET['status'] == 'success') {
                        echo "<p class='text-[10px] text-green-400 mt-2'><i class='fas fa-check-circle mr-1'></i> Pesan berhasil dikirim secara anonim!</p>";
                    } elseif ($_GET['status'] == 'wait') {
                        $wait = $_GET['wait'] ?? 5;
                        echo "<p class='text-[10px] text-orange-400 mt-2'><i class='fas fa-clock mr-1'></i> Harap tunggu sekitar $wait menit lagi untuk mengirim pesan.</p>";
                    }
                }
                ?>
            </div>
        </div>
    </div>
    </div>

    <div class="border-t border-indigo-800 mt-4 pt-4 text-center text-xs mx-5">
        <p>© <?php echo date("Y"); ?> Kelas PTIK C - Teknik Informatika dan Komputer FT UNM. All rights reserved.</p>
    </div>
    </div>
</footer>

<script>
    // Prevent quotes in footer pesan_anonim
    const footerForm = document.getElementById('footer_form');
    if (footerForm) {
        const footerTextArea = footerForm.querySelector('textarea[name="pesan_anonim"]');
        if (footerTextArea) {
            // Prevent typing
            footerTextArea.addEventListener('keydown', function(e) {
                if (e.key === "'" || e.key === '"') {
                    e.preventDefault();
                }
            });
            // Also handle pasting/other input methods
            footerTextArea.addEventListener('input', function() {
                this.value = this.value.replace(/['"]/g, '');
            });
        }
    }
</script>