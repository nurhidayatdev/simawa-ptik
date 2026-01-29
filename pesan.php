<?php
session_start();
include 'koneksi.php';

if (isset($_POST['kirim_pesan'])) {
    // Batasi 5 menit sekali (300 detik)
    if (isset($_SESSION['last_send'])) {
        $selisih = time() - $_SESSION['last_send'];
        if ($selisih < 300) {
            $_SESSION['pesan_status'] = 'wait';
            $_SESSION['wait_time'] = ceil((300 - $selisih) / 60);
            header("Location: pesan");
            exit;
        }
    }

    $pesan_raw = $_POST['pesan_anonim'];
    $pesan_db = mysqli_real_escape_string($koneksi, $pesan_raw);

    if (!empty($pesan_raw)) {
        $query_pesan = "INSERT INTO pesan (isi_pesan) VALUES ('$pesan_db')";
        if (mysqli_query($koneksi, $query_pesan)) {
            // Kirim notifikasi WhatsApp (gunakan pesan raw agar \r\n tidak muncul)
            include 'functions/kirim_pesan.php';
            sendWhatsAppPesan($pesan_raw);
            $_SESSION['pesan_status'] = 'success';
            $_SESSION['last_send'] = time(); // Simpan waktu terakhir kirim
        } else {
            $_SESSION['pesan_status'] = 'error';
        }
    }
    header("Location: pesan");
    exit;
}

?>
<!DOCTYPE html>
<html lang="id">

<head>

    <title>Kirim Pesan Anonim - PTIK C 2024</title>
    <?php include 'includes/head.php'; ?>
</head>

<body class="bg-gray-900 min-h-screen flex items-center justify-center p-4">

    <div class="w-full max-w-md">
        <div class="mb-4">
            <a href="views/dinding_pesan.php" class="inline-flex items-center text-sm font-medium text-indigo-400 hover:text-indigo-300 transition-colors group">
                <i class="fas fa-arrow-left mr-2 transform group-hover:-translate-x-1 transition-transform"></i>
                Kembali
            </a>
        </div>

        <div class="mb-6 text-center">
            <h1 class="text-3xl font-bold text-white mb-2">Kotak Saran</h1>
            <p class="text-gray-400 text-sm">Sampaikan aspirasi atau pesan Anda secara anonim.</p>
        </div>

        <div class="relative group">
            <div class="absolute -inset-1 bg-gradient-to-r from-indigo-500 to-purple-600 rounded-xl blur opacity-25 group-hover:opacity-50 transition duration-1000"></div>
            <div class="relative bg-indigo-900/40 backdrop-blur-sm border border-indigo-500/30 p-8 rounded-xl overflow-hidden shadow-2xl">

                <!-- Decorative Glow -->
                <div class="absolute top-0 right-0 -mr-4 -mt-4 w-24 h-24 rounded-full bg-indigo-500/20 blur-2xl"></div>

                <div class="flex items-center space-x-3 mb-6 relative z-10">
                    <div class="w-12 h-12 rounded-full bg-gradient-to-br from-indigo-500 to-purple-600 flex items-center justify-center shadow-lg shadow-indigo-500/30 p-2">
                        <img src="assets/image/logo.webp" alt="Logo Kelas" class="w-full h-full object-contain filter drop-shadow-md">
                    </div>
                    <div>
                        <h3 class="text-lg font-bold text-white leading-tight">Private Message</h3>
                        <p class="text-xs text-indigo-200">Identitas pengirim dirahasiakan</p>
                    </div>
                </div>

                <form action="" method="POST" class="space-y-4 relative z-10">
                    <div class="relative group/input">
                        <label for="pesan_anonim" class="block text-xs font-medium text-indigo-300 mb-1 ml-1">Isi Pesan</label>
                        <textarea
                            id="pesan_anonim"
                            name="pesan_anonim"
                            rows="4"
                            maxlength="500"
                            class="w-full p-4 rounded-lg bg-indigo-950/50 border border-indigo-500/30 text-indigo-50 text-sm focus:outline-none focus:border-indigo-400 focus:ring-1 focus:ring-indigo-400 placeholder-indigo-400/50 transition-all resize-none shadow-inner"
                            placeholder="Tuliskan pesan, saran, atau masukan Anda di sini..."
                            required></textarea>
                        <div class="absolute bottom-3 right-3 text-[10px] text-indigo-400/50 opacity-0 group-hover/input:opacity-100 transition-opacity">
                            <i class="fas fa-lock mr-1"></i>Terenkripsi
                        </div>
                    </div>

                    <button
                        type="submit"
                        name="kirim_pesan"
                        class="w-full group/btn relative overflow-hidden rounded-lg bg-gradient-to-r from-indigo-600 to-indigo-500 p-[1px] focus:outline-none focus:ring-2 focus:ring-indigo-400 focus:ring-offset-2 focus:ring-offset-slate-900 mt-2">
                        <div class="relative bg-indigo-600 group-hover/btn:bg-opacity-0 transition-all duration-300 rounded-[calc(0.5rem-1px)] h-full w-full py-3">
                            <span class="relative flex items-center justify-center space-x-2 text-white text-sm font-semibold tracking-wide">
                                <span>Kirim Pesan Sekarang</span>
                                <i class="fas fa-paper-plane text-xs transform group-hover/btn:translate-x-1 transition-transform"></i>
                            </span>
                        </div>
                    </button>
                </form>

                <?php
                if (isset($_SESSION['pesan_status'])) {
                    if ($_SESSION['pesan_status'] == 'success') {
                        echo "
                        <div class='mt-4 p-4 bg-emerald-500/10 border border-emerald-500/20 rounded-lg flex items-start space-x-3 animate-fade-in-up transition-all'>
                            <div class='flex-shrink-0 w-6 h-6 rounded-full bg-emerald-500/20 flex items-center justify-center mt-0.5'>
                                <i class='fas fa-check text-xs text-emerald-400'></i>
                            </div>
                            <div>
                                <h4 class='text-sm font-semibold text-emerald-400'>Berhasil Terkirim!</h4>
                                <p class='text-xs text-emerald-200 mt-1'>Pesan Anda telah kami terima secara anonim. Terima kasih atas masukannya.</p>
                            </div>
                        </div>";
                    } elseif ($_SESSION['pesan_status'] == 'error') {
                        echo "
                        <div class='mt-4 p-4 bg-red-500/10 border border-red-500/20 rounded-lg flex items-start space-x-3 animate-fade-in-up transition-all'>
                            <div class='flex-shrink-0 w-6 h-6 rounded-full bg-red-500/20 flex items-center justify-center mt-0.5'>
                                <i class='fas fa-times text-xs text-red-400'></i>
                            </div>
                            <div>
                                <h4 class='text-sm font-semibold text-red-400'>Gagal Mengirim</h4>
                                <p class='text-xs text-red-200 mt-1'>Terjadi kesalahan saat menyimpan pesan. Silakan coba lagi.</p>
                            </div>
                        </div>";
                    } elseif ($_SESSION['pesan_status'] == 'wait') {
                        $remaining = $_SESSION['wait_time'] ?? 5;
                        echo "
                        <div class='mt-4 p-4 bg-orange-500/10 border border-orange-500/20 rounded-lg flex items-start space-x-3 animate-fade-in-up transition-all'>
                            <div class='flex-shrink-0 w-6 h-6 rounded-full bg-orange-500/20 flex items-center justify-center mt-0.5'>
                                <i class='fas fa-clock text-xs text-orange-400'></i>
                            </div>
                            <div>
                                <h4 class='text-sm font-semibold text-orange-400'>Harap Tunggu</h4>
                                <p class='text-xs text-orange-200 mt-1'>Anda hanya dapat mengirim pesan setiap 5 menit sekali. Silakan coba lagi dalam sekitar $remaining menit.</p>
                            </div>
                        </div>";
                    }
                    unset($_SESSION['pesan_status']);
                }
                ?>
            </div>

            <div class="mt-6 text-center">
                <p class="text-[10px] text-gray-500">
                    &copy; <?php echo date("Y"); ?> Kelas PTIK C - Teknik Informatika dan Komputer FT UNM. <br>All rights reserved.
                </p>
            </div>
        </div>
    </div>

</body>

</html>