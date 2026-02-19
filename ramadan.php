<?php
include 'koneksi.php';

// Fetch Bukber Memories from Gallery
$q_memories = mysqli_query($koneksi, "
    SELECT gf.foto_path, g.judul, g.tanggal_kegiatan
    FROM galeri g
    JOIN galeri_foto gf ON g.id = gf.galeri_id 
    WHERE LOWER(g.judul) LIKE '%buka%' 
       OR LOWER(g.judul) LIKE '%bukber%' 
       OR LOWER(g.judul) LIKE '%ramadhan%' 
       OR LOWER(g.judul) LIKE '%ramadan%'
    ORDER BY g.tanggal_kegiatan DESC 
    LIMIT 12
");
$memories = [];
if ($q_memories) {
    while ($m = mysqli_fetch_assoc($q_memories)) {
        $memories[] = $m;
    }
}
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <title>Ramadan 1447H - PTIK C 2024</title>
    <?php include 'includes/head.php'; ?>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.css" />
    <script src="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.js"></script>
    <style>
        :root {
            --primary: #4f46e5;
            --primary-light: #818cf8;
            --accent: #e0e7ff;
            --bg-dark: #020617;
        }

        @keyframes gold-flow {
            0% {
                background-position: 0% 50%;
            }

            50% {
                background-position: 100% 50%;
            }

            100% {
                background-position: 0% 50%;
            }
        }

        @keyframes slow-spin {
            from {
                transform: rotate(0deg);
            }

            to {
                transform: rotate(360deg);
            }
        }

        @keyframes float-soft {

            0%,
            100% {
                transform: translateY(0px);
            }

            50% {
                transform: translateY(-15px);
            }
        }

        .gold-text {
            background: linear-gradient(to right, #818cf8, #ffffff, #818cf8);
            background-size: 200% auto;
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
            animation: gold-flow 5s linear infinite;
        }

        .glow-indigo {
            filter: drop-shadow(0 0 15px rgba(79, 70, 229, 0.6));
        }

        .ornament-pattern {
            background-image: url('https://www.transparenttextures.com/patterns/islamic-art.png');
            opacity: 0.05;
        }

        .star {
            position: absolute;
            background: white;
            border-radius: 50%;
            animation: twinkle var(--duration) ease-in-out infinite;
        }

        @keyframes twinkle {

            0%,
            100% {
                opacity: 0.2;
                transform: scale(0.8);
            }

            50% {
                opacity: 0.8;
                transform: scale(1.1);
            }
        }

        /* Fully Responsive Poster Container */
        .poster-container {
            width: 100%;
            height: 100%;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            position: relative;
            z-index: 10;
            padding: 2.5rem;
            margin: auto;
            max-width: 500px;
            /* Limits width on large screens */
        }

        /* Adjustments for Desktop View */
        @media (min-width: 640px) and (min-height: 700px) {
            .poster-container {
                height: 90vh;
                /* Takes up 90% of screen height */
                max-width: 450px;
                /* Classic poster width */
                background: rgba(2, 6, 23, 0.7);
                backdrop-filter: blur(25px);
                border: 1px solid rgba(79, 70, 229, 0.3);
                border-radius: 40px;
                box-shadow: 0 50px 100px -20px rgba(0, 0, 0, 1), 0 0 50px rgba(79, 70, 229, 0.1);
                margin: auto;
            }
        }

        /* Adjustments for Very Short Screens or Mobile Landscape */
        @media (max-height: 680px) {
            .poster-container {
                padding: 1.5rem;
            }

            .content-wrapper {
                transform: scale(0.85);
                /* Scales down content to fit */
            }

            h1 {
                font-size: 3.5rem !important;
            }

            .quotes-p {
                font-size: 1rem !important;
            }
        }

        @keyframes scale-up {
            from {
                opacity: 0;
                transform: scale(0.9) translateY(20px);
            }

            to {
                opacity: 1;
                transform: scale(1) translateY(0);
            }
        }

        .animate-scale-up {
            animation: scale-up 0.4s cubic-bezier(0.16, 1, 0.3, 1) forwards;
        }

        .custom-scrollbar::-webkit-scrollbar {
            width: 4px;
        }

        .custom-scrollbar::-webkit-scrollbar-track {
            background: #f1f5f9;
        }

        .custom-scrollbar::-webkit-scrollbar-thumb {
            background: #6366f1;
            border-radius: 10px;
        }

        .custom-scrollbar::-webkit-scrollbar-thumb:hover {
            background: #4f46e5;
        }

        .swiper-button-next,
        .swiper-button-prev {
            color: #ffffff !important;
            background: rgba(79, 70, 229, 0.4);
            width: 46px !important;
            height: 46px !important;
            border-radius: 50%;
            backdrop-filter: blur(8px);
            -webkit-backdrop-filter: blur(8px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            transition: all 0.3s ease;
        }

        .swiper-button-next:hover,
        .swiper-button-prev:hover {
            background: rgba(79, 70, 229, 0.8);
            transform: scale(1.1);
        }

        .swiper-button-next:after,
        .swiper-button-prev:after {
            font-size: 16px !important;
            font-weight: 900;
        }

        .swiper-pagination-bullet {
            background: #cbd5e1 !important;
            opacity: 1;
        }

        .swiper-pagination-bullet-active {
            background: #6366f1 !important;
            width: 24px !important;
            border-radius: 5px !important;
        }
    </style>
</head>

<body class="bg-[#020617] h-screen w-full overflow-hidden flex flex-col items-center justify-center font-['Poppins']">

    <!-- Background Elements -->
    <div class="fixed inset-0 z-0">
        <div class="absolute inset-0 ornament-pattern"></div>
        <div id="star-container" class="absolute inset-0"></div>
        <div class="absolute top-0 left-0 w-full h-full bg-gradient-to-b from-transparent via-[#020617]/50 to-[#020617]"></div>
    </div>

    <!-- The Poster -->
    <div class="poster-container animate-fade-in">

        <!-- Top Ornament -->
        <div class="w-full flex justify-between items-start opacity-30 shrink-0">
            <div class="w-10 h-10 border-t border-l border-indigo-500/50 rounded-tl-lg"></div>
            <div class="text-indigo-400 text-xl animate-float-soft"><i class="fas fa-moon"></i></div>
            <div class="w-10 h-10 border-t border-r border-indigo-500/50 rounded-tr-lg"></div>
        </div>

        <!-- Content Area -->
        <div class="content-wrapper flex flex-col items-center justify-center space-y-6 md:space-y-8 flex-grow shrink">
            <!-- Logo Section -->
            <div class="relative shrink-0">
                <div class="absolute inset-0 bg-indigo-500/20 rounded-full blur-2xl animate-pulse"></div>
                <div class="relative w-24 h-24 md:w-28 md:h-28 p-2 rounded-full border border-indigo-500/30 flex items-center justify-center">
                    <img src="assets/image/logo.webp" alt="Logo" class="w-3/4 h-3/4 object-contain glow-indigo brightness-110 contrast-125">
                    <div class="absolute -inset-1 border-t border-indigo-400/50 rounded-full animate-slow-spin"></div>
                </div>
            </div>

            <div class="text-center space-y-1 md:space-y-2 shrink-0">
                <span class="text-indigo-400 font-medium tracking-[0.3em] uppercase text-[10px] md:text-xs block opacity-80">Marhaban Ya</span>
                <h1 class="text-5xl md:text-6xl lg:text-7xl font-black gold-text leading-none py-1">RAMADAN</h1>
                <div class="flex items-center justify-center gap-3">
                    <div class="h-[1px] w-6 bg-indigo-500/50"></div>
                    <span class="text-2xl md:text-3xl font-bold text-indigo-200 tracking-tighter">1447 H</span>
                    <div class="h-[1px] w-6 bg-indigo-500/50"></div>
                </div>
            </div>

            <!-- Quote Area -->
            <div class="relative px-2 shrink">
                <p class="quotes-p text-indigo-100/90 text-base md:text-xl font-light leading-relaxed italic text-center">
                    "Semoga setiap langkah ibadah kita di bulan yang suci ini membawa keberkahan dan kedamaian yang abadi."
                </p>
            </div>
        </div>

        <!-- Bottom Section -->
        <div class="w-full space-y-4 md:space-y-6 pt-4 shrink-0">
            <div class="text-center">
                <span class="text-indigo-400 font-bold tracking-[0.2em] text-[10px] md:text-xs uppercase block mb-2">Keluarga Besar</span>
                <button onclick="openMemories()" class="inline-block bg-indigo-600/20 px-5 py-2 rounded-xl border border-indigo-500/30 backdrop-blur-sm hover:bg-indigo-600/40 hover:scale-105 active:scale-95 transition-all cursor-pointer group shadow-lg shadow-indigo-500/10">
                    <span class="text-white text-base md:text-lg font-black tracking-widest uppercase group-hover:text-indigo-300 transition-colors">PTIK C 2024</span>
                </button>
            </div>

            <!-- Bottom Frame -->
            <div class="flex justify-between items-end opacity-30">
                <div class="w-10 h-10 border-b border-l border-indigo-500/50 rounded-bl-lg"></div>
                <div class="text-indigo-500/30 text-[8px] space-x-2 pb-1">
                    <i class="fas fa-star"></i>
                    <i class="fas fa-star"></i>
                    <i class="fas fa-star"></i>
                </div>
                <div class="w-10 h-10 border-b border-r border-indigo-500/50 rounded-br-lg"></div>
            </div>
        </div>
    </div>

    <!-- Ultra-subtle background mosque -->
    <div class="fixed bottom-0 w-full flex justify-center opacity-[0.02] pointer-events-none -mb-10 lg:-mb-20">
        <i class="fas fa-mosque text-[50vh] text-indigo-500"></i>
    </div>

    <!-- Memories Modal (Bukber) -->
    <div id="memoriesModal" class="fixed inset-0 z-[100] hidden items-center justify-center p-4">
        <div class="absolute inset-0 bg-slate-950/95 backdrop-blur-2xl" onclick="closeMemories()"></div>
        <div class="relative bg-slate-900 w-full max-w-5xl rounded-[2.5rem] overflow-hidden shadow-[0_0_100px_rgba(79,70,229,0.2)] animate-scale-up border border-indigo-500/20 flex flex-col max-h-[95vh]">
            <!-- Header -->
            <div class="px-6 py-4 md:px-10 md:py-6 bg-slate-900 text-white flex justify-between items-center shrink-0 border-b border-indigo-500/10">
                <div>
                    <h2 class="text-xl md:text-2xl font-black tracking-tighter mb-1 uppercase gold-text">Momen Kebersamaan</h2>
                    <p class="text-[10px] uppercase tracking-widest font-bold text-indigo-400 opacity-80">Flashback Buka Puasa Bersama PTIK C 2024</p>
                </div>
                <button onclick="closeMemories()" class="w-10 h-10 rounded-2xl bg-indigo-500/10 hover:bg-indigo-500/20 flex items-center justify-center transition-all border border-indigo-500/20">
                    <i class="fas fa-times text-indigo-400"></i>
                </button>
            </div>

            <!-- Content (Swiper Slider - Theater Mode) -->
            <div class="p-0 overflow-hidden flex-grow bg-slate-950 flex items-center justify-center relative">
                <?php if (!empty($memories)): ?>
                    <div class="swiper memorySwiper w-full h-full">
                        <div class="swiper-wrapper">
                            <?php foreach ($memories as $m): ?>
                                <div class="swiper-slide flex flex-col items-center justify-center p-0 relative group">
                                    <!-- Blurred Background Fallback for different aspect ratios -->
                                    <div class="absolute inset-0 opacity-20 blur-3xl scale-125 pointer-events-none">
                                        <img src="storage/gallery/<?= htmlspecialchars($m['foto_path']) ?>"
                                            class="w-full h-full object-cover">
                                    </div>

                                    <!-- Main Image Container -->
                                    <div class="relative z-10 w-full h-full flex flex-col items-center justify-center p-6 md:p-12 lg:p-20">
                                        <div class="relative max-w-full max-h-[60vh] md:max-h-[65vh] flex items-center justify-center rounded-2xl overflow-hidden shadow-[0_0_80px_rgba(0,0,0,0.8)] bg-slate-900 border border-indigo-500/20 group">
                                            <img src="storage/gallery/<?= htmlspecialchars($m['foto_path']) ?>"
                                                class="max-w-full max-h-full object-contain block mx-auto transition-transform duration-1000 group-hover:scale-105"
                                                alt="<?= htmlspecialchars($m['judul']) ?>"
                                                onerror="this.src='assets/image/logo.webp'">

                                            <!-- Floating Minimal Caption (inside image) -->
                                            <div class="absolute bottom-0 inset-x-0 p-4 md:p-6 bg-gradient-to-t from-black/95 via-black/40 to-transparent">
                                                <div class="flex flex-col items-center text-center">
                                                    <h3 class="text-white text-[9px] md:text-[11px] font-black uppercase tracking-widest leading-tight drop-shadow-xl gold-text">
                                                        <?= htmlspecialchars($m['judul']) ?>
                                                    </h3>
                                                    <div class="flex items-center justify-center gap-2 mt-1 md:mt-1.5">
                                                        <span class="w-1 h-1 rounded-full bg-indigo-500"></span>
                                                        <p class="text-indigo-200 text-[7px] md:text-[8px] font-bold uppercase tracking-[0.2em] opacity-80 italic">
                                                            <?= date('d F Y', strtotime($m['tanggal_kegiatan'])) ?>
                                                        </p>
                                                        <span class="w-1 h-1 rounded-full bg-indigo-500"></span>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <!-- Navigation -->
                        <div class="swiper-button-next !hidden md:!flex"></div>
                        <div class="swiper-button-prev !hidden md:!flex"></div>
                        <div class="swiper-pagination !bottom-4 md:!bottom-8"></div>
                    </div>
                <?php else: ?>
                    <div class="flex flex-col items-center justify-center py-20 text-slate-400 italic">
                        <i class="fas fa-camera-retro text-5xl mb-4 opacity-20"></i>
                        <p class="text-sm">Belum ada foto momen ramadan yang tersimpan.</p>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Footer Action -->
            <div class="p-4 md:px-10 md:py-6 bg-slate-900 border-t border-indigo-500/10 shrink-0 flex flex-col md:flex-row items-center justify-between gap-4">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-indigo-500/10 text-indigo-400 flex items-center justify-center border border-indigo-500/20">
                        <i class="fas fa-history"></i>
                    </div>
                    <div>
                        <p class="text-xs font-black text-white uppercase tracking-tight">Tradisi Kebersamaan</p>
                        <p class="text-[10px] font-medium text-slate-400">Terus mengukir cerita di setiap Ramadan.</p>
                    </div>
                </div>
                <div class="flex flex-wrap md:flex-nowrap items-center justify-center md:justify-end gap-3 w-full">
                    <button onclick="openImsakiyah()" class="bg-indigo-600 hover:bg-indigo-700 text-white px-8 py-3 rounded-2xl text-[12px] font-black uppercase tracking-widest transition-all shadow-xl shadow-indigo-600/20 active:scale-95 flex items-center justify-center gap-3 w-full md:w-auto border border-indigo-400/20 group">
                        Buka Jadwal Imsakiyah <i class="fas fa-chevron-right text-[10px] group-hover:translate-x-1 transition-transform"></i>
                    </button>
                </div>
            </div>
        </div>
    </div>
    <div id="imsakiyahModal" class="fixed inset-0 z-[100] hidden items-center justify-center p-4">
        <div class="absolute inset-0 bg-slate-950/90 backdrop-blur-xl" onclick="closeImsakiyah()"></div>
        <div class="relative bg-slate-900 w-full max-w-4xl max-h-[85vh] rounded-[2.5rem] overflow-hidden shadow-[0_0_100px_rgba(79,70,229,0.2)] animate-scale-up border border-indigo-500/20 flex flex-col">
            <!-- Modal Header -->
            <div class="px-8 py-6 bg-slate-900 text-white flex justify-between items-center shrink-0 border-b border-indigo-500/10 relative overflow-hidden">
                <div class="absolute top-0 right-0 p-4 opacity-10 rotate-12 scale-150">
                    <i class="fas fa-mosque text-6xl text-indigo-500"></i>
                </div>
                <div class="relative z-10">
                    <h2 class="text-xl md:text-2xl font-black tracking-tighter leading-none mb-1 gold-text">JADWAL IMSAKIYAH 1447 H</h2>
                    <p class="text-[9px] md:text-[10px] uppercase tracking-[0.2em] text-indigo-400 font-bold flex items-center gap-2">
                        <i class="fas fa-map-marker-alt"></i> Wilayah Asia/Makassar (PTIK C 2024)
                    </p>
                </div>
                <button onclick="closeImsakiyah()" class="relative z-10 w-10 h-10 rounded-2xl bg-indigo-500/10 hover:bg-indigo-500/20 border border-indigo-500/20 flex items-center justify-center transition-all active:scale-90">
                    <i class="fas fa-times text-indigo-400"></i>
                </button>
            </div>

            <!-- Table Body -->
            <div class="overflow-x-auto overflow-y-auto custom-scrollbar flex-grow bg-slate-950 italic-table-container">
                <table class="w-full text-left border-collapse min-w-[800px]">
                    <thead class="sticky top-0 z-20">
                        <tr class="bg-indigo-950/90 backdrop-blur-md text-white border-b border-indigo-500/20">
                            <th class="px-4 py-4 text-[10px] uppercase tracking-widest font-black text-center">Tgl</th>
                            <th class="px-4 py-4 text-[10px] uppercase tracking-widest font-black">Hari</th>
                            <th class="px-4 py-4 text-[10px] uppercase tracking-widest font-black">Masehi</th>
                            <th class="px-4 py-4 text-[10px] uppercase tracking-widest font-black text-center bg-indigo-500/10">Imsak</th>
                            <th class="px-4 py-4 text-[10px] uppercase tracking-widest font-black text-center">Shubuh</th>
                            <th class="px-4 py-4 text-[10px] uppercase tracking-widest font-black text-center">Terbit</th>
                            <th class="px-4 py-4 text-[10px] uppercase tracking-widest font-black text-center">Dhuha</th>
                            <th class="px-4 py-4 text-[10px] uppercase tracking-widest font-black text-center">Zhuhur</th>
                            <th class="px-4 py-4 text-[10px] uppercase tracking-widest font-black text-center">Ashr</th>
                            <th class="px-4 py-4 text-[10px] uppercase tracking-widest font-black text-center bg-orange-500/10">Maghrib</th>
                            <th class="px-4 py-4 text-[10px] uppercase tracking-widest font-black text-center">Isha</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-indigo-500/10">
                        <?php
                        date_default_timezone_set('Asia/Makassar');
                        $today_masehi = date('j M'); // Format: "19 Feb"

                        $imsakData = [
                            ["1", "Kamis", "19 Feb", "04:41", "04:51", "06:08", "06:32", "12:20", "15:30", "18:26", "19:36"],
                            ["2", "Jumat", "20 Feb", "04:41", "04:51", "06:08", "06:32", "12:20", "15:29", "18:25", "19:35"],
                            ["3", "Sabtu", "21 Feb", "04:41", "04:51", "06:08", "06:32", "12:19", "15:29", "18:25", "19:35"],
                            ["4", "Ahad", "22 Feb", "04:42", "04:52", "06:08", "06:32", "12:19", "15:28", "18:25", "19:35"],
                            ["5", "Senin", "23 Feb", "04:42", "04:52", "06:08", "06:32", "12:19", "15:27", "18:25", "19:34"],
                            ["6", "Selasa", "24 Feb", "04:42", "04:52", "06:08", "06:32", "12:19", "15:26", "18:24", "19:34"],
                            ["7", "Rabu", "25 Feb", "04:42", "04:52", "06:08", "06:32", "12:19", "15:26", "18:24", "19:34"],
                            ["8", "Kamis", "26 Feb", "04:42", "04:52", "06:08", "06:32", "12:19", "15:25", "18:24", "19:33"],
                            ["9", "Jumat", "27 Feb", "04:42", "04:52", "06:08", "06:32", "12:19", "15:24", "18:23", "19:33"],
                            ["10", "Sabtu", "28 Feb", "04:42", "04:52", "06:08", "06:32", "12:18", "15:23", "18:23", "19:32"],
                            ["11", "Ahad", "1 Mar", "04:42", "04:52", "06:08", "06:32", "12:18", "15:22", "18:23", "19:32"],
                            ["12", "Senin", "2 Mar", "04:42", "04:52", "06:08", "06:32", "12:18", "15:21", "18:22", "19:32"],
                            ["13", "Selasa", "3 Mar", "04:42", "04:52", "06:07", "06:31", "12:18", "15:20", "18:22", "19:31"],
                            ["14", "Rabu", "4 Mar", "04:42", "04:52", "06:07", "06:31", "12:18", "15:19", "18:22", "19:31"],
                            ["15", "Kamis", "5 Mar", "04:42", "04:52", "06:07", "06:31", "12:17", "15:18", "18:21", "19:30"],
                            ["16", "Jumat", "6 Mar", "04:42", "04:52", "06:07", "06:31", "12:17", "15:17", "18:21", "19:30"],
                            ["17", "Sabtu", "7 Mar", "04:42", "04:52", "06:07", "06:31", "12:17", "15:16", "18:21", "19:30"],
                            ["18", "Ahad", "8 Mar", "04:42", "04:52", "06:07", "06:31", "12:17", "15:16", "18:20", "19:29"],
                            ["19", "Senin", "9 Mar", "04:42", "04:52", "06:07", "06:31", "12:16", "15:16", "18:20", "19:29"],
                            ["20", "Selasa", "10 Mar", "04:42", "04:52", "06:07", "06:31", "12:16", "15:17", "18:19", "19:28"],
                            ["21", "Rabu", "11 Mar", "04:42", "04:52", "06:07", "06:31", "12:16", "15:17", "18:19", "19:28"],
                            ["22", "Kamis", "12 Mar", "04:42", "04:52", "06:07", "06:31", "12:16", "15:17", "18:19", "19:27"],
                            ["23", "Jumat", "13 Mar", "04:42", "04:52", "06:06", "06:30", "12:15", "15:18", "18:18", "19:27"],
                            ["24", "Sabtu", "14 Mar", "04:42", "04:52", "06:06", "06:30", "12:15", "15:18", "18:18", "19:26"],
                            ["25", "Ahad", "15 Mar", "04:42", "04:52", "06:06", "06:30", "12:15", "15:19", "18:17", "19:26"],
                            ["26", "Senin", "16 Mar", "04:41", "04:51", "06:06", "06:30", "12:15", "15:19", "18:17", "19:26"],
                            ["27", "Selasa", "17 Mar", "04:41", "04:51", "06:06", "06:30", "12:14", "15:19", "18:17", "19:25"],
                            ["28", "Rabu", "18 Mar", "04:41", "04:51", "06:06", "06:30", "12:14", "15:20", "18:16", "19:25"],
                            ["29", "Kamis", "19 Mar", "04:41", "04:51", "06:06", "06:30", "12:14", "15:20", "18:16", "19:24"]
                        ];

                        foreach ($imsakData as $row) :
                            $isJumat = $row[1] == 'Jumat';
                            $isToday = $row[2] == $today_masehi;
                        ?>
                            <tr id="<?= $isToday ? 'todayRow' : '' ?>" class="<?= $isToday ? 'bg-indigo-500/10' : 'hover:bg-indigo-500/5' ?> transition-colors group">
                                <td class="px-4 py-3 text-[11px] font-black <?= $isToday ? 'text-indigo-400' : 'text-slate-600' ?> text-center">
                                    <?php if ($isToday): ?>
                                        <div class="flex flex-col items-center">
                                            <span><?= $row[0] ?></span>
                                            <span class="text-[7px] text-indigo-400 animate-pulse">LIVE</span>
                                        </div>
                                    <?php else: ?>
                                        <?= $row[0] ?>
                                    <?php endif; ?>
                                </td>
                                <td class="px-4 py-3 text-[11px] font-bold <?= $isToday ? 'text-indigo-300' : ($isJumat ? 'text-rose-400' : 'text-slate-400') ?>"><?= $row[1] ?></td>
                                <td class="px-4 py-3 text-[11px] font-semibold <?= $isToday ? 'text-indigo-200' : 'text-slate-500' ?> whitespace-nowrap">
                                    <?= $row[2] ?>
                                    <?php if ($isToday): ?>
                                        <span class="ml-1 text-[8px] bg-indigo-600 text-white px-1.5 py-0.5 rounded-full font-black">HARI INI</span>
                                    <?php endif; ?>
                                </td>
                                <td class="px-4 py-3 text-[11px] font-black <?= $isToday ? 'text-indigo-300 bg-indigo-500/20' : 'text-indigo-400 bg-indigo-500/5 group-hover:bg-indigo-500/10' ?> transition-all font-mono tracking-tight text-center"><?= $row[3] ?></td>
                                <td class="px-4 py-3 text-[11px] font-bold <?= $isToday ? 'text-white font-black' : 'text-slate-300' ?> text-center font-mono"><?= $row[4] ?></td>
                                <td class="px-4 py-3 text-[11px] font-bold text-slate-600 text-center font-mono opacity-60"><?= $row[5] ?></td>
                                <td class="px-4 py-3 text-[11px] font-bold text-slate-600 text-center font-mono opacity-60"><?= $row[6] ?></td>
                                <td class="px-4 py-3 text-[11px] font-bold <?= $isToday ? 'text-white font-black' : 'text-slate-300' ?> text-center font-mono"><?= $row[7] ?></td>
                                <td class="px-4 py-3 text-[11px] font-bold <?= $isToday ? 'text-white font-black' : 'text-slate-300' ?> text-center font-mono"><?= $row[8] ?></td>
                                <td class="px-4 py-3 text-[11px] font-black <?= $isToday ? 'text-orange-300 bg-orange-500/20' : 'text-orange-400 bg-orange-500/5 group-hover:bg-orange-500/10' ?> transition-all font-mono tracking-tight text-center font-bold"><?= $row[9] ?></td>
                                <td class="px-4 py-3 text-[11px] font-bold <?= $isToday ? 'text-white font-black' : 'text-slate-300' ?> text-center font-mono"><?= $row[10] ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <!-- Modal Footer -->
            <div class="px-8 py-5 bg-slate-900 border-t border-indigo-500/10 flex flex-col md:flex-row justify-between items-center gap-4 shrink-0">
                <p class="text-[9px] font-bold text-slate-500 uppercase tracking-widest italic flex items-center gap-2">
                    <i class="fas fa-info-circle text-indigo-500"></i> Sumber Data: al-habib.info | Zona Asia/Makassar
                </p>
                <button onclick="closeImsakiyah()" class="bg-indigo-600/10 hover:bg-indigo-600/20 text-indigo-400 border border-indigo-500/30 px-6 py-2 rounded-xl text-[10px] font-black uppercase tracking-widest transition-all active:scale-95">
                    Tutup Jadwal
                </button>
            </div>
        </div>
    </div>

    <script>
        // Modal logic
        function openImsakiyah() {
            closeMemories(); // Ensure memories is closed
            const modal = document.getElementById('imsakiyahModal');
            modal.classList.remove('hidden');
            modal.classList.add('flex');
            document.body.style.overflow = 'hidden';

            // Auto-scroll to today
            setTimeout(() => {
                const todayRow = document.getElementById('todayRow');
                if (todayRow) {
                    todayRow.scrollIntoView({
                        behavior: 'smooth',
                        block: 'center'
                    });
                }
            }, 100);
        }

        function openMemories() {
            const modal = document.getElementById('memoriesModal');
            modal.classList.remove('hidden');
            modal.classList.add('flex');
            document.body.style.overflow = 'hidden';

            // Initialize Swiper after modal is visible
            new Swiper(".memorySwiper", {
                effect: "slide",
                grabCursor: true,
                centeredSlides: true,
                slidesPerView: 1,
                spaceBetween: 30,
                loop: true,
                autoplay: {
                    delay: 4000,
                    disableOnInteraction: false,
                },
                pagination: {
                    el: ".swiper-pagination",
                    clickable: true,
                    dynamicBullets: true,
                },
                navigation: {
                    nextEl: ".swiper-button-next",
                    prevEl: ".swiper-button-prev",
                },
            });
        }

        function closeMemories() {
            const modal = document.getElementById('memoriesModal');
            modal.classList.add('hidden');
            modal.classList.remove('flex');
            document.body.style.overflow = 'auto';
        }

        function closeImsakiyah() {
            const modal = document.getElementById('imsakiyahModal');
            modal.classList.add('hidden');
            modal.classList.remove('flex');
            document.body.style.overflow = 'auto';
        }

        const container = document.getElementById('star-container');

        // Auto-open modal once per session
        window.addEventListener('load', () => {
            if (!sessionStorage.getItem('ramadan_notif_shown')) {
                setTimeout(() => {
                    openMemories();
                    sessionStorage.setItem('ramadan_notif_shown', 'true');
                }, 1500); // Small delay for better UX
            }
        });

        const count = 80;
        for (let i = 0; i < count; i++) {
            const star = document.createElement('div');
            star.className = 'star';
            const size = Math.random() * 2 + 'px';
            star.style.width = size;
            star.style.height = size;
            star.style.left = Math.random() * 100 + '%';
            star.style.top = Math.random() * 100 + '%';
            const duration = Math.random() * 3 + 2 + 's';
            star.style.setProperty('--duration', duration);
            star.style.animationDelay = Math.random() * 5 + 's';
            container.appendChild(star);
        }
    </script>
</body>

</html>