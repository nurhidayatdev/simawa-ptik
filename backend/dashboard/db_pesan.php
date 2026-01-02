<?php
session_start();
include '../../koneksi.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../../index.php");
    exit;
}
$role = $_SESSION['role'];

?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#1e1b4b" />
    <link rel="icon" href="..//img/main/icon.png" />
    <title>Dashboard Pesan Anonim - PTIK C 2024</title>
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
    <link href="../../frontend/tailwind/src/output.css" rel="stylesheet">
    <link href="../../frontend/tailwind/src/input.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
    <style>
        body {
            font-family: 'Poppins', sans-serif;
        }
    </style>
</head>

<body class="bg-gray-900 min-h-screen text-gray-100">

    <div class="container mx-auto px-4 py-8">

        <!-- Header -->
        <div class="flex flex-col md:flex-row md:items-center justify-between mb-12 gap-6 animate-fade-in-down">
            <div>
                <h1 class="text-3xl font-bold text-white mb-2 flex items-center">
                    <span class="bg-clip-text text-transparent bg-gradient-to-r from-indigo-400 to-purple-500">
                        Dinding Pesan Anonim
                    </span>
                    <i class="fas fa-layer-group ml-3 text-indigo-500 text-xl"></i>
                </h1>
                <p class="text-sm text-gray-400 border-l-2 border-indigo-500 pl-3">
                    Aspirasi dan pesan rahasia dari mahasiswa kelas PTIK C 2024.
                </p>
            </div>
            <div class="flex flex-row flex-wrap items-center justify-end gap-3 md:gap-4 w-full">
                <a href="../../pages/pesan.php" class="group relative px-6 py-3 rounded-lg bg-indigo-600 border border-indigo-400/30 text-white text-sm font-medium hover:bg-indigo-500 transition-all overflow-hidden shadow-lg shadow-indigo-500/20">
                    <div class="absolute inset-0 w-full bg-gradient-to-r from-transparent via-white/20 to-transparent skew-x-12 -translate-x-full group-hover:animate-shine"></div>
                    <span class="relative flex items-center gap-2">
                        <i class="fas fa-paper-plane transition-transform group-hover:translate-x-1 group-hover:-translate-y-1"></i>
                        Kirim Pesan
                    </span>
                </a>

                <?php if ($role === 'Administrator Utama'): ?>
                    <a href="pesan.php" class="group relative px-6 py-3 rounded-lg bg-indigo-900/40 border border-indigo-500/30 text-indigo-300 text-sm font-medium hover:bg-indigo-800/50 transition-all overflow-hidden">
                        <div class="absolute inset-0 w-full bg-gradient-to-r from-transparent via-indigo-500/10 to-transparent skew-x-12 -translate-x-full group-hover:animate-shine"></div>
                        <span class="relative flex items-center gap-2">
                            <i class="fas fa-database transition-transform group-hover:scale-110"></i>
                            Database
                        </span>
                    </a>
                <?php endif; ?>

                <a href="dashboard.php" class="group relative px-6 py-3 rounded-lg bg-indigo-900/40 border border-indigo-500/30 text-indigo-300 text-sm font-medium hover:bg-indigo-800/50 transition-all overflow-hidden">
                    <div class="absolute inset-0 w-full bg-gradient-to-r from-transparent via-indigo-500/10 to-transparent skew-x-12 -translate-x-full group-hover:animate-shine"></div>
                    <span class="relative flex items-center gap-2">
                        <i class="fas fa-arrow-left transition-transform group-hover:-translate-x-1"></i>
                        Kembali
                    </span>
                </a>
            </div>

        </div>

        <!-- Grid Pesan -->
        <div class="columns-1 md:columns-2 lg:columns-3 xl:columns-4 gap-6 space-y-6">
            <?php
            $query = mysqli_query($koneksi, "SELECT * FROM pesan ORDER BY tanggal DESC");
            if (mysqli_num_rows($query) > 0) {
                while ($row = mysqli_fetch_assoc($query)) {
                    $pesan_content = htmlspecialchars($row['isi_pesan']);
                    $pesan_date = date('d M Y', strtotime($row['tanggal']));
                    $pesan_time = date('H:i', strtotime($row['tanggal']));
                    $pesan_id = $row['id'];
            ?>
                    <!-- Card Item -->
                    <div class="break-inside-avoid relative group cursor-pointer" onclick="openModal('<?php echo addslashes($pesan_content); ?>', '<?php echo $pesan_date; ?>', '<?php echo $pesan_time; ?>')">
                        <div class="absolute -inset-0.5 bg-gradient-to-r from-indigo-500 to-purple-600 rounded-2xl blur opacity-20 group-hover:opacity-40 transition duration-500"></div>
                        <div class="relative bg-gray-800/80 backdrop-blur-xl p-6 rounded-2xl border border-gray-700/50 group-hover:border-indigo-500/30 transition-all shadow-xl">

                            <!-- Header Card -->
                            <div class="flex justify-between items-start mb-4">
                                <div class="flex items-center space-x-3">
                                    <div class="w-10 h-10 rounded-full bg-gradient-to-br from-indigo-600 to-purple-700 flex items-center justify-center shadow-lg shadow-indigo-500/20 p-1.5">
                                        <img src="..//img/main/logo.png" alt="Anonim" class="w-full h-full object-contain filter drop-shadow-sm opacity-90">
                                    </div>
                                    <div>
                                        <h4 class="text-sm font-bold text-white">PTIK C 24</h4>
                                        <span class="text-[10px] text-indigo-300 bg-indigo-500/10 px-2 py-0.5 rounded-full border border-indigo-500/10">Anonim</span>
                                    </div>
                                </div>


                            </div>

                            <!-- Content -->
                            <div class="relative">
                                <i class="fas fa-quote-left absolute -top-1 -left-1 text-2xl text-indigo-500/10"></i>
                                <p class="text-gray-300 text-sm leading-relaxed relative z-10 pl-4 py-2 italic font-light line-clamp-4">
                                    "<?php echo nl2br($pesan_content); ?>"
                                </p>
                            </div>

                            <!-- Footer Card -->
                            <div class="mt-4 pt-4 border-t border-gray-700/50 flex items-center justify-between text-[10px] text-gray-500">
                                <span class="flex items-center gap-1.5">
                                    <i class="far fa-clock"></i>
                                    <?php echo $pesan_date; ?>
                                </span>
                                <span class="flex items-center gap-1.5 text-indigo-400/80">
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
                <div class="col-span-full flex flex-col items-center justify-center py-20 text-center border-2 border-dashed border-gray-700 rounded-3xl bg-gray-800/30">
                    <div class="w-20 h-20 bg-gray-800 rounded-full flex items-center justify-center mb-4 shadow-inner">
                        <i class="far fa-paper-plane text-3xl text-gray-600"></i>
                    </div>
                    <h3 class="text-xl font-bold text-gray-400 mb-2">Belum ada pesan</h3>
                    <p class="text-sm text-gray-500 max-w-xs mx-auto">
                        Jadilah yang pertama mengirimkan pesan rahasia di kotak saran kelas.
                    </p>
                    <a href="pesan.php" class="mt-6 px-6 py-2 bg-indigo-600 hover:bg-indigo-500 text-white text-xs font-bold rounded-full transition shadow-lg shadow-indigo-500/20">
                        Kirim Pesan Sekarang
                    </a>
                </div>
            <?php } ?>
        </div>
    </div>

    <!-- Background Decoration -->
    <div class="fixed top-0 left-0 w-full h-full overflow-hidden -z-10 pointer-events-none">
        <div class="absolute top-0 left-1/4 w-96 h-96 bg-indigo-600/10 rounded-full blur-3xl mix-blend-screen animate-blob"></div>
        <div class="absolute top-0 right-1/4 w-96 h-96 bg-purple-600/10 rounded-full blur-3xl mix-blend-screen animate-blob animation-delay-2000"></div>
        <div class="absolute -bottom-32 left-1/3 w-96 h-96 bg-pink-600/10 rounded-full blur-3xl mix-blend-screen animate-blob animation-delay-4000"></div>
    </div>

    <!-- Modal Popup -->
    <div id="shareModal" class="fixed inset-0 z-50 hidden flex items-center justify-center bg-black/80 backdrop-blur-sm p-4 opacity-0 transition-opacity duration-300">
        <div class="bg-gray-900 rounded-2xl w-full max-w-md border border-gray-700 shadow-2xl transform scale-95 transition-transform duration-300" id="modalContent">

            <div class="p-6 relative">
                <button onclick="closeModal()" class="absolute top-4 right-4 text-gray-400 hover:text-white transition">
                    <i class="fas fa-times text-xl"></i>
                </button>

                <h3 class="text-xl font-bold text-white mb-6 text-center">Bagikan Pesan</h3>

                <!-- Preview Area for Image Generation -->
                <div id="captureArea" class="bg-gray-800 rounded-xl p-6 border border-gray-700/50 mb-6 relative overflow-hidden">
                    <div class="absolute top-0 right-0 w-32 h-32 bg-indigo-500/10 rounded-full blur-3xl -mr-16 -mt-16"></div>
                    <div class="absolute bottom-0 left-0 w-32 h-32 bg-purple-500/10 rounded-full blur-3xl -ml-16 -mb-16"></div>

                    <div class="relative z-10">
                        <div class="flex items-center space-x-3 mb-4">
                            <div class="w-10 h-10 rounded-full bg-gradient-to-br from-indigo-600 to-purple-700 flex items-center justify-center p-1.5 shadow-lg">
                                <img src="../img/main/logo.png" alt="Logo" class="w-full h-full object-contain opacity-90">
                            </div>
                            <div>
                                <h4 class="text-sm font-bold text-white">PTIK C 2024</h4>
                                <p class="text-[10px] text-gray-400">Pesan Anonim</p>
                            </div>
                        </div>

                        <div class="mb-4">
                            <p id="modalPesan" class="text-gray-200 text-sm leading-relaxed italic font-light"></p>
                        </div>

                        <div class="flex justify-between items-center text-[10px] text-gray-500 border-t border-gray-700 pt-3">
                            <span id="modalDate"></span>
                            <span class="text-indigo-400">@ptikc_24</span>
                        </div>
                    </div>
                </div>

                <!-- Action Buttons -->
                <div class="grid grid-cols-1">
                    <button onclick="shareInstagram()" class="flex items-center justify-center gap-2 bg-gradient-to-r from-purple-500 to-pink-500 hover:from-purple-600 hover:to-pink-600 text-white py-3 rounded-xl font-semibold text-sm transition shadow-lg shadow-pink-900/20">
                        <i class="fas fa-download text-lg"></i>
                        Simpan Gambar
                    </button>
                </div>
                <p class="text-[10px] text-gray-500 text-center mt-3">
                    *Unduh gambar untuk dibagikan ke Instagram Story
                </p>
            </div>
        </div>
    </div>

    <script>
        const modal = document.getElementById('shareModal');
        const modalContent = document.getElementById('modalContent');
        let currentPesan = '';

        function openModal(pesan, date, time) {
            currentPesan = pesan; // Store for WA sharing
            document.getElementById('modalPesan').innerHTML = '"' + pesan.replace(/\n/g, '<br>') + '"';
            document.getElementById('modalDate').innerText = date + ' • ' + time;

            modal.classList.remove('hidden');
            // Small timeout to allow removing 'hidden' to render for transition
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
            }, 300);
        }


        function shareInstagram() {
            const captureArea = document.getElementById('captureArea');

            // Show loading state if wanted, but here direct capture
            html2canvas(captureArea, {
                backgroundColor: '#1f2937', // Match bg-gray-800
                scale: 2 // Higher quality
            }).then(canvas => {
                // Trigger download
                const link = document.createElement('a');
                link.download = 'pesan_ptikc_' + Date.now() + '.png';
                link.href = canvas.toDataURL('image/png');
                link.click();
            });
        }

        // Close on click outside
        modal.addEventListener('click', (e) => {
            if (e.target === modal) {
                closeModal();
            }
        });
    </script>

    <!-- Footer -->
    <div class="mt-12 mb-8 text-center relative z-10">
        <p class="text-[10px] text-gray-500">
            &copy; 2025 Kelas PTIK C - Teknik Informatika dan Komputer FT UNM. <br>All rights reserved.
        </p>
    </div>

    <style>
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

</body>

</html>