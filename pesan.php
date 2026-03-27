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

    $pesan_raw = trim($_POST['pesan_anonim']);
    // Sterilize input: remove tags and handle potential break-tags
    $pesan_clean = strip_tags($pesan_raw);

    if (!empty($pesan_clean)) {
        // Use Prepared Statements for maximum security against SQL Injection
        // This naturally handles symbols like quotes without breaking the SQL query
        $stmt = $koneksi->prepare("INSERT INTO pesan (isi_pesan) VALUES (?)");
        $stmt->bind_param("s", $pesan_clean);
        
        if ($stmt->execute()) {
            // Kirim notifikasi WhatsApp
            include 'functions/kirim_pesan.php';
            sendWhatsAppPesan($pesan_clean);
            $_SESSION['pesan_status'] = 'success';
            $_SESSION['last_send'] = time();
        } else {
            $_SESSION['pesan_status'] = 'error';
        }
        $stmt->close();
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
    <style>
        body {
            font-family: 'Space Grotesk', sans-serif;
            background-color: #f1f5f9;
            color: #0f172a;
            font-size: 14px;
            transition: background-color 0.3s, color 0.3s;
        }

        .dark body {
            background-color: #0b0f1a;
            color: #f8fafc;
        }

        .navbar-responsive {
            background-color: rgba(255, 255, 255, 0.82);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            border-color: #e2e8f0;
        }

        .dark .navbar-responsive {
            background-color: rgba(11, 15, 26, 0.92);
            border-color: #1e293b;
        }

        .card-responsive {
            background-color: #ffffff;
            border-color: #e2e8f0;
            transition: all 0.3s ease;
        }

        .dark .card-responsive {
            background-color: rgba(30, 41, 59, 0.5);
            border-color: #1e293b;
        }
    </style>
</head>

<body class="min-h-screen flex flex-col relative overflow-x-hidden">

    <div class="fixed inset-0 -z-10 overflow-hidden">
        <div class="absolute top-10 left-10 w-24 h-24 bg-indigo-500/10 dark:bg-indigo-600/20 rounded-full blur-3xl"></div>
        <div class="absolute bottom-20 right-20 w-32 h-32 bg-purple-500/10 dark:bg-purple-600/20 rounded-full blur-3xl"></div>
    </div>

    <div class="w-full p-3 flex justify-between items-center z-50 border-b navbar-responsive transition-colors duration-300">
        <div class="flex items-center gap-3">
            <img src="assets/image/logo.webp" alt="Logo" class="w-8 h-8 object-contain dark:brightness-100 brightness-0 transition-all">
            <h1 class="text-base font-bold tracking-tight text-slate-900 dark:text-white">PesanAnonim</h1>
        </div>
        <div class="flex items-center gap-3">
            <button id="theme-toggle" class="relative inline-flex items-center h-7 w-12 flex-shrink-0 cursor-pointer rounded-full border border-slate-200 dark:border-white/20 bg-slate-100 dark:bg-white/10 transition-all duration-300 ease-in-out focus:outline-none">
                <span id="theme-toggle-thumb" class="pointer-events-none absolute left-0.5 h-5 w-5 transform rounded-full bg-white shadow-md transition duration-300 ease-in-out flex items-center justify-center dark:translate-x-5">
                    <i id="theme-toggle-light-icon" class="fa-solid fa-sun text-[10px] text-orange-500 transition-all duration-300 absolute"></i>
                    <i id="theme-toggle-dark-icon" class="fa-solid fa-moon text-[10px] text-indigo-900 transition-all duration-300 absolute opacity-0 scale-0"></i>
                </span>
            </button>
            <a href="views/dinding_pesan.php" class="px-3 py-1.5 bg-indigo-600 hover:bg-indigo-500 rounded-lg text-xs font-medium text-white transition-colors">
                <i class="fa fa-arrow-left mr-1"></i> Kembali
            </a>
        </div>
    </div>

    <div class="flex-grow flex items-center justify-center p-4">
        <div class="w-full max-w-md">
            <div class="mb-6 text-center animate-fade-in-down">
                <h1 class="text-3xl font-bold text-slate-900 dark:text-white mb-2">Kotak Saran</h1>
                <p class="text-slate-500 dark:text-gray-400 text-sm">Sampaikan aspirasi atau pesan Anda secara anonim.</p>
            </div>

            <div class="relative group">
                <div class="absolute -inset-1 bg-gradient-to-r from-indigo-500 to-purple-600 rounded-xl blur opacity-25 group-hover:opacity-40 transition duration-1000"></div>
                <div class="relative card-responsive p-8 rounded-xl overflow-hidden shadow-2xl border">

                    <!-- Decorative Glow -->
                    <div class="absolute top-0 right-0 -mr-4 -mt-4 w-24 h-24 rounded-full bg-indigo-500/20 blur-2xl"></div>

                    <div class="flex items-center space-x-3 mb-6 relative z-10">
                        <div class="w-12 h-12 rounded-full bg-gradient-to-br from-indigo-500 to-purple-600 flex items-center justify-center shadow-lg shadow-indigo-500/30 p-2">
                            <img src="assets/image/logo.webp" alt="Logo Kelas" class="w-full h-full object-contain filter drop-shadow-md">
                        </div>
                        <div>
                            <h3 class="text-lg font-bold text-slate-900 dark:text-white leading-tight">Private Message</h3>
                            <p class="text-xs text-indigo-600 dark:text-indigo-200">Identitas pengirim dirahasiakan</p>
                        </div>
                    </div>

                    <form action="" method="POST" class="space-y-4 relative z-10">
                        <div class="relative group/input">
                            <label for="pesan_anonim" class="block text-xs font-medium text-slate-600 dark:text-indigo-300 mb-1 ml-1">Isi Pesan</label>
                            <textarea
                                id="pesan_anonim"
                                name="pesan_anonim"
                                rows="4"
                                maxlength="500"
                                class="w-full p-4 rounded-lg bg-slate-50 dark:bg-indigo-950/50 border border-slate-200 dark:border-indigo-500/30 text-slate-900 dark:text-indigo-50 text-sm focus:outline-none focus:border-indigo-400 focus:ring-1 focus:ring-indigo-400 placeholder-slate-400 dark:placeholder-indigo-400/50 transition-all resize-none shadow-inner"
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
                    <p class="text-[10px] text-slate-400 dark:text-gray-500 duration-300 transition-colors">
                        &copy; <?php echo date("Y"); ?> Kelas PTIK C - Teknik Informatika dan Komputer FT UNM. <br>All rights reserved.
                    </p>
                </div>
            </div>
        </div>
    </div>

    <script>
        // --- THEME TOGGLE LOGIC ---
        const themeToggleBtn = document.getElementById('theme-toggle');
        const darkIcon = document.getElementById('theme-toggle-dark-icon');
        const lightIcon = document.getElementById('theme-toggle-light-icon');

        if (themeToggleBtn) {
            const setToggleState = (isDark) => {
                if (isDark) {
                    lightIcon.classList.add('opacity-0', 'scale-0');
                    darkIcon.classList.remove('opacity-0', 'scale-0');
                } else {
                    lightIcon.classList.remove('opacity-0', 'scale-0');
                    darkIcon.classList.add('opacity-0', 'scale-0');
                }
            };

            setToggleState(document.documentElement.classList.contains('dark'));

            themeToggleBtn.addEventListener('click', () => {
                const isDark = !document.documentElement.classList.contains('dark');
                if (isDark) {
                    document.documentElement.classList.add('dark');
                    localStorage.setItem('theme', 'dark');
                } else {
                    document.documentElement.classList.remove('dark');
                    localStorage.setItem('theme', 'light');
                }
                setToggleState(isDark);
                // Reload page to correctly re-initialize components
                setTimeout(() => {
                    location.reload();
                }, 150);
            });
        }

        // --- PREVENT QUOTES LOGIC ---
        const pesanInput = document.getElementById('pesan_anonim');
        if (pesanInput) {
            // Prevent typing
            pesanInput.addEventListener('keydown', function(e) {
                if (e.key === "'" || e.key === '"') {
                    e.preventDefault();
                }
            });
            // Also handle pasting/other input methods
            pesanInput.addEventListener('input', function() {
                this.value = this.value.replace(/['"]/g, '');
            });
        }
    </script>
</body>

</html>