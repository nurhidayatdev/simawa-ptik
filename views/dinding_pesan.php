<?php
session_start();
include '../koneksi.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../../index");
    exit;
}
$role = $_SESSION['role'];

// Pre-encode logo to Base64 to prevent CORS/Taint issues
$logo_path = '../assets/image/logo.webp';
$logo_base64 = '';
if (file_exists($logo_path)) {
    $logo_data = file_get_contents($logo_path);
    $logo_base64 = 'data:image/webp;base64,' . base64_encode($logo_data);
} else {
    $logo_base64 = '../assets/image/logo.webp';
}

?>
<!DOCTYPE html>
<html lang="id">

<head>

    <title>Dashboard Pesan Anonim - PTIK C 2024</title>
    <?php include '../includes/head.php'; ?>
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

        .rotate-180 {
            transform: rotate(180deg);
        }

        @keyframes shine {
            100% {
                left: 125%;
            }
        }

        .animate-shine {
            animation: shine 1s;
        }

        @keyframes blob {
            0% {
                transform: translate(0px, 0px) scale(1);
            }

            33% {
                transform: translate(30px, -50px) scale(1.1);
            }

            66% {
                transform: translate(-20px, 20px) scale(0.9);
            }

            100% {
                transform: translate(0px, 0px) scale(1);
            }
        }

        .animate-blob {
            animation: blob 7s infinite;
        }

        .animation-delay-2000 {
            animation-delay: 2s;
        }

        .animation-delay-4000 {
            animation-delay: 4s;
        }
    </style>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>


</head>

<body class="min-h-screen flex flex-col relative overflow-x-hidden">

    <div class="fixed inset-0 -z-10 overflow-hidden">
        <div class="absolute top-10 left-10 w-24 h-24 bg-indigo-500/10 dark:bg-indigo-600/20 rounded-full blur-3xl"></div>
        <div class="absolute bottom-20 right-20 w-32 h-32 bg-purple-500/10 dark:bg-purple-600/20 rounded-full blur-3xl"></div>
    </div>

    <div class="w-full p-3 flex justify-between items-center z-50 border-b navbar-responsive transition-colors duration-300">
        <div class="flex items-center gap-3">
            <img src="../assets/image/logo.webp" alt="Logo" class="w-8 h-8 object-contain dark:brightness-100 brightness-0 transition-all">
            <h1 class="text-base font-bold tracking-tight text-slate-900 dark:text-white">DindingPesan</h1>
        </div>
        <div class="flex items-center gap-3">
            <button id="theme-toggle" class="relative inline-flex items-center h-7 w-12 flex-shrink-0 cursor-pointer rounded-full border border-slate-200 dark:border-white/20 bg-slate-100 dark:bg-white/10 transition-all duration-300 ease-in-out focus:outline-none">
                <span id="theme-toggle-thumb" class="pointer-events-none absolute left-0.5 h-5 w-5 transform rounded-full bg-white shadow-md transition duration-300 ease-in-out flex items-center justify-center dark:translate-x-5">
                    <i id="theme-toggle-light-icon" class="fa-solid fa-sun text-[10px] text-orange-500 transition-all duration-300 absolute"></i>
                    <i id="theme-toggle-dark-icon" class="fa-solid fa-moon text-[10px] text-indigo-900 transition-all duration-300 absolute opacity-0 scale-0"></i>
                </span>
            </button>
            <a href="../pesan" class="px-3 py-1.5 bg-indigo-600 hover:bg-indigo-500 rounded-lg text-xs font-medium text-white transition-colors">
                <i class="fa fa-paper-plane mr-1"></i> Kirim
            </a>
            <a href="../dashboard" class="px-3 py-1.5 bg-slate-100 dark:bg-slate-800/50 hover:bg-slate-200 dark:hover:bg-slate-700/50 rounded-lg text-xs font-medium border border-slate-200 dark:border-slate-700 text-slate-700 dark:text-slate-200 transition-colors">
                <i class="fa fa-arrow-left mr-1"></i> Kembali
            </a>
        </div>
    </div>

    <div class="container mx-auto px-4 py-8">

        <!-- Header -->
        <div class="mb-8 animate-fade-in-down">
            <h1 class="text-2xl font-bold mb-2 flex items-center text-slate-900 dark:text-white">
                <span class="bg-clip-text text-transparent bg-gradient-to-r from-indigo-500 to-purple-500">
                    Dinding Pesan Anonim
                </span>
                <i class="fas fa-layer-group ml-3 text-indigo-500 text-xl"></i>
            </h1>
            <p class="text-sm text-slate-500 dark:text-gray-400 border-l-2 border-indigo-500 pl-3">
                Aspirasi dan pesan rahasia dari mahasiswa kelas PTIK C 2024.
            </p>
        </div>

        <!-- Grid Pesan -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
            <?php
            $show_all = isset($_GET['expand']);
            $limit_count = 100;

            // Get total count for logic
            $total_res = mysqli_query($koneksi, "SELECT COUNT(*) as total FROM pesan");
            $total_data = mysqli_fetch_assoc($total_res)['total'];

            $sql = "SELECT * FROM pesan ORDER BY tanggal DESC";
            if (!$show_all) {
                $sql .= " LIMIT $limit_count";
            }

            $query = mysqli_query($koneksi, $sql);
            if (mysqli_num_rows($query) > 0) {
                while ($row = mysqli_fetch_assoc($query)) {
                    $pesan_content = htmlspecialchars($row['isi_pesan']);
                    $pesan_date = date('d M Y', strtotime($row['tanggal']));
                    $pesan_time = date('H:i', strtotime($row['tanggal']));
                    $pesan_id = $row['id'];
            ?>
                    <!-- Card Item -->
                    <div class="relative group cursor-pointer" onclick='openModal(<?php echo json_encode($pesan_content); ?>, "<?php echo $pesan_date; ?>", "<?php echo $pesan_time; ?>")'>
                        <div class="absolute -inset-0.5 bg-gradient-to-r from-indigo-500 to-purple-600 rounded-2xl blur opacity-10 group-hover:opacity-30 transition duration-500"></div>
                        <div class="relative card-responsive p-6 rounded-2xl border group-hover:border-indigo-500/30 transition-all shadow-xl">

                            <!-- Header Card -->
                            <div class="flex justify-between items-start mb-4">
                                <div class="flex items-center space-x-3">
                                    <div class="w-10 h-10 rounded-full bg-gradient-to-br from-indigo-600 to-purple-700 flex items-center justify-center shadow-lg shadow-indigo-500/20 p-1.5 shrink-0">
                                        <img src="../assets/image/logo.webp" alt="Anonim" class="w-full h-full object-contain filter drop-shadow-sm opacity-90">
                                    </div>
                                    <div class="min-w-0">
                                        <h4 class="text-sm font-bold text-slate-900 dark:text-white truncate">ProTechNoTy</h4>
                                        <span class="text-[10px] text-indigo-600 dark:text-indigo-400 bg-indigo-50 dark:bg-indigo-500/10 px-2 py-0.5 rounded-full border border-indigo-200 dark:border-indigo-500/10">Anonim</span>
                                    </div>
                                </div>


                            </div>

                            <!-- Content -->
                            <div class="relative">
                                <i class="fas fa-quote-left absolute -top-1 -left-1 text-2xl text-indigo-500/10"></i>
                                <p class="text-slate-600 dark:text-gray-300 text-sm leading-relaxed relative z-10 pl-4 py-2 italic font-light line-clamp-4">
                                    "<?php echo nl2br($pesan_content); ?>"
                                </p>
                            </div>

                            <!-- Footer Card -->
                            <div class="mt-4 pt-4 border-t border-slate-100 dark:border-gray-700/50 flex items-center justify-between text-[10px] text-slate-400 dark:text-gray-500">
                                <span class="flex items-center gap-1.5">
                                    <i class="far fa-clock"></i>
                                    <?php echo $pesan_date; ?>
                                </span>
                                <span class="flex items-center gap-1.5 text-indigo-600 dark:text-indigo-400/80">
                                    <i class="fas fa-history"></i>
                                    <?php echo $pesan_time; ?> WITA
                                </span>
                            </div>
                        </div>
                    </div>
                <?php
                }
            } else {
                ?>
                <!-- Empty State -->
                <div class="col-span-full flex flex-col items-center justify-center py-20 text-center border-2 border-dashed border-slate-200 dark:border-gray-700 rounded-3xl bg-white/50 dark:bg-gray-800/30">
                    <div class="w-20 h-20 bg-slate-100 dark:bg-gray-800 rounded-full flex items-center justify-center mb-4 shadow-inner">
                        <i class="far fa-paper-plane text-3xl text-slate-400 dark:text-gray-600"></i>
                    </div>
                    <h3 class="text-xl font-bold text-slate-700 dark:text-gray-400 mb-2">Belum ada pesan</h3>
                    <p class="text-sm text-slate-500 dark:text-gray-500 max-w-xs mx-auto">
                        Jadilah yang pertama mengirimkan pesan rahasia di kotak saran kelas.
                    </p>
                    <a href="../pesan" class="mt-6 px-6 py-2 bg-indigo-600 hover:bg-indigo-500 text-white text-xs font-bold rounded-full transition shadow-lg shadow-indigo-500/20">
                        Kirim Pesan Sekarang
                    </a>
                </div>
            <?php } ?>
        </div>

        <?php if (!$show_all && $total_data > $limit_count): ?>
            <!-- Expand Button -->
            <div class="mt-12 flex justify-center animate-fade-in-up">
                <a href="?expand=true" class="group relative px-8 py-4 rounded-2xl bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-700 dark:text-slate-200 font-bold text-sm transition-all hover:shadow-2xl hover:shadow-indigo-500/10 hover:-translate-y-1 flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-indigo-50 dark:bg-indigo-500/10 flex items-center justify-center text-indigo-600">
                        <i class="fas fa-chevron-down transition-transform group-hover:translate-y-0.5"></i>
                    </div>
                    <span>Tampilkan Semua Pesan (<?php echo $total_data - $limit_count; ?> lainnya)</span>
                </a>
            </div>
        <?php endif; ?>
    </div>


    <!-- Modal Popup -->
    <div id="shareModal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/80 backdrop-blur-sm p-4 opacity-0 transition-opacity duration-300">
        <div class="bg-white dark:bg-gray-900 rounded-2xl w-full max-w-md border border-slate-200 dark:border-gray-700 shadow-2xl transform scale-95 transition-transform duration-300" id="modalContent">

            <div class="p-6 relative">
                <button onclick="closeModal()" class="absolute top-4 right-4 text-slate-400 hover:text-slate-600 dark:hover:text-white transition">
                    <i class="fas fa-times text-xl"></i>
                </button>

                <h3 class="text-xl font-bold text-slate-900 dark:text-white mb-6 text-center">Bagikan Pesan</h3>

                <!-- Preview Area for Image Generation -->
                <div id="captureArea"
                    style="width: 100%; max-width: 320px; margin-left: auto; margin-right: auto; background-color: #1f2937; color: #ffffff; border: 1px solid #374151; border-radius: 1rem; padding: 2rem; margin-bottom: 1.5rem; position: relative; overflow: hidden; font-family: 'Poppins', Arial, sans-serif; opacity: 1 !important; visibility: visible !important; box-sizing: border-box;">

                    <div style="position: relative; z-index: 10;">
                        <div style="display: flex; align-items: center; margin-bottom: 1.5rem;">
                            <div style="width: 2.8rem; height: 2.8rem; border-radius: 9999px; display: flex; align-items: center; justify-content: center; padding: 0.5rem; background: linear-gradient(135deg, #4f46e5 0%, #7e22ce 100%); box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.3);">
                                <img src="<?php echo $logo_base64; ?>" alt="Logo" style="width: 100%; height: 100%; object-fit: contain;">
                            </div>
                            <div style="margin-left: 0.75rem;">
                                <h4 style="font-size: 1rem; font-weight: 700; color: #ffffff; margin: 0;">ProTechNoTy</h4>
                                <p style="font-size: 0.8rem; color: #9ca3af; margin: 0;">Pesan Anonim</p>
                            </div>
                        </div>

                        <div style="margin-bottom: 1rem;">
                            <p id="modalPesan" style="font-size: 1rem; line-height: 1.5; font-style: italic; font-weight: 300; color: #e5e7eb; margin: 0;"></p>
                        </div>

                        <div style="display: flex; justify-content: space-between; align-items: center; font-size: 0.625rem; padding-top: 0.75rem; border-top: 1px solid #374151; color: #6b7280;">
                            <span id="modalDate"></span>
                            <span style="color: #818cf8;"><i class="fab fa-instagram"></i> ptikc_24</span>
                        </div>
                    </div>
                </div>

                <!-- Action Buttons -->
                <div class="grid grid-cols-1">
                    <button onclick="shareInstagram(this)" class="flex items-center justify-center gap-2 bg-gradient-to-r from-purple-500 to-pink-500 hover:from-purple-600 hover:to-pink-600 text-white py-3 rounded-xl font-semibold text-sm transition shadow-lg shadow-pink-900/20">
                        <i class="fas fa-download text-lg"></i>
                        Simpan Gambar
                    </button>
                </div>
                <p class="text-[10px] text-slate-500 dark:text-gray-500 text-center mt-3">
                    *Unduh gambar untuk dibagikan ke Instagram Story
                </p>
            </div>
        </div>
    </div>
    <!-- Footer -->
    <footer class="w-full py-6 px-3 border-t border-slate-200 dark:border-slate-800 transition-colors mt-auto">
        <div class="max-w-5xl mx-auto text-center">
            <p class="text-[10px] text-slate-500 font-bold uppercase tracking-widest">© <?php echo date("Y"); ?> Kelas PTIK C - Teknik Informatika dan Komputer FT UNM. All rights reserved.</p>
        </div>
    </footer>
    <script>
        const modal = document.getElementById('shareModal');
        const modalContent = document.getElementById('modalContent');
        let currentPesan = '';

        function openModal(pesan, date, time) {
            currentPesan = pesan;
            document.getElementById('modalPesan').innerHTML = '"' + pesan.replace(/\n/g, '<br>') + '"';
            document.getElementById('modalDate').innerText = date + ' • ' + time;

            // Reset button state whenever modal is opened
            const downloadBtn = modal.querySelector('button[onclick="shareInstagram(this)"]');
            if (downloadBtn) {
                downloadBtn.innerHTML = '<i class="fas fa-download text-lg"></i> Simpan Gambar';
                downloadBtn.disabled = false;
            }

            modal.classList.remove('hidden');
            modal.classList.add('flex');
            setTimeout(() => {
                modal.classList.remove('opacity-0');
                modalContent.classList.remove('scale-95');
                modalContent.classList.add('scale-100');
            }, 10);
        }

        function closeModal() {
            modal.classList.add('opacity-0');
            modalContent.classList.remove('scale-100');
            modalContent.classList.add('scale-95');
            setTimeout(() => {
                modal.classList.add('hidden');
                modal.classList.remove('flex');
            }, 300);
        }


        async function shareInstagram(btn) {
            const captureArea = document.getElementById('captureArea');
            if (!captureArea) return;

            const originalText = '<i class="fas fa-download text-lg"></i> Simpan Gambar';
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Loading...';
            btn.disabled = true;

            const styleSheets = Array.from(document.querySelectorAll('link[rel="stylesheet"]'));
            const tailwindSheet = styleSheets.find(s => s.href.includes('output.css'));
            let originalWidth = captureArea.style.width;

            try {
                // Stabilize dimensions
                captureArea.style.width = captureArea.offsetWidth + 'px';

                // Temporarily disable tailwind to prevent some capture issues
                if (tailwindSheet) tailwindSheet.disabled = true;

                // Small delay to let browser reflow if needed
                await new Promise(resolve => setTimeout(resolve, 200));

                const canvas = await html2canvas(captureArea, {
                    backgroundColor: '#1f2937',
                    scale: 2,
                    useCORS: true,
                    allowTaint: false,
                    logging: false
                });

                const dataUrl = canvas.toDataURL('image/png', 1.0);

                if (dataUrl === "data:,") {
                    throw new Error("Canvas is empty");
                }

                const link = document.createElement('a');
                link.href = dataUrl;
                link.download = 'pesan_ptikc_' + Date.now() + '.png';
                document.body.appendChild(link);
                link.click();
                document.body.removeChild(link);

                btn.innerHTML = '<i class="fas fa-check"></i> Berhasil';
            } catch (err) {
                console.error('Capture failed:', err);
                alert('Gagal mengunduh gambar. Silakan coba lagi.');
                btn.innerHTML = originalText;
                btn.disabled = false;
            } finally {
                // ALWAYS restore these states
                if (tailwindSheet) tailwindSheet.disabled = false;
                captureArea.style.width = originalWidth;

                // Allow button to be clickable again after a delay if success
                if (btn.innerHTML.includes('Berhasil')) {
                    setTimeout(() => {
                        if (modal.classList.contains('hidden')) {
                            btn.innerHTML = originalText;
                            btn.disabled = false;
                        }
                    }, 2000);
                }
            }
        }

        modal.addEventListener('click', (e) => {
            if (e.target === modal) {
                closeModal();
            }
        });

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
    </script>
</body>

</html>