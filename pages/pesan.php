<?php
session_start();
include '../koneksi.php';

if (isset($_POST['kirim_pesan'])) {
    $pesan_raw = $_POST['pesan_anonim'];
    $pesan_db = mysqli_real_escape_string($koneksi, $pesan_raw);

    if (!empty($pesan_raw)) {
        $query_pesan = "INSERT INTO pesan (isi_pesan) VALUES ('$pesan_db')";
        if (mysqli_query($koneksi, $query_pesan)) {
            // Kirim notifikasi Telegram (gunakan pesan raw agar \r\n tidak muncul)
            include '../backend/crud/kirim_pesan_telegram.php';
            sendTelegramPesan($pesan_raw);
            $_SESSION['pesan_status'] = 'success';
        } else {
            $_SESSION['pesan_status'] = 'error';
        }
    }
    header("Location: pesan.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#1e1b4b" />
    <title>Kirim Pesan Anonim - PTIK C 2024</title>
    <link rel="icon" href="../backend/img/main/icon.webp" />
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="../frontend/tailwind/src/output.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&display=swap" rel="stylesheet">
    <style>
        * {
            font-family: "Poppins", sans-serif;
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
<body class="bg-gray-900 min-h-screen flex items-center justify-center p-4">

    <div class="w-full max-w-md">
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
                        <img src="../backend/img/main/logo.png" alt="Logo Kelas" class="w-full h-full object-contain filter drop-shadow-md">
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
                    }
                    unset($_SESSION['pesan_status']);
                }
                ?>
            </div>

            <div class="mt-6 text-center">
                <p class="text-[10px] text-gray-500">
                    &copy; 2025 Kelas PTIK C - Teknik Informatika dan Komputer FT UNM. <br>All rights reserved.
                </p>
            </div>
        </div>
    </div>

</body>

</html>