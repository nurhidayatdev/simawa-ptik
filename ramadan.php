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

<body class="bg-[#020617] min-h-screen w-full flex flex-col items-center font-['Poppins'] scroll-smooth">

    <!-- Background Elements -->
    <div class="fixed inset-0 z-0">
        <div class="absolute inset-0 ornament-pattern"></div>
        <div id="star-container" class="absolute inset-0"></div>
        <div class="absolute top-0 left-0 w-full h-full bg-gradient-to-b from-transparent via-[#020617]/50 to-[#020617]"></div>
    </div>

    <!-- Hero Section (Full Height) -->
    <div class="relative w-full h-screen flex flex-col items-center justify-center shrink-0">
        <div class="poster-container animate-fade-in relative z-10">

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
                    <a href="#momen-kebersamaan" class="inline-block bg-indigo-600/20 px-5 py-2 rounded-xl border border-indigo-500/30 backdrop-blur-sm hover:bg-indigo-600/40 hover:scale-105 active:scale-95 transition-all cursor-pointer group shadow-lg shadow-indigo-500/10">
                        <span class="text-white text-base md:text-lg font-black tracking-widest uppercase group-hover:text-indigo-300 transition-colors">PTIK C 2024</span>
                    </a>
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
        <div class="absolute bottom-0 w-full flex justify-center opacity-[0.02] pointer-events-none -mb-10 lg:-mb-20">
            <i class="fas fa-mosque text-[50vh] text-indigo-500"></i>
        </div>
    </div>

    <!-- Integrated Momen Kebersamaan Section -->
    <div id="momen-kebersamaan" class="relative z-10 w-full max-w-5xl px-4 mt-10 space-y-12">
        <!-- Section Header -->
        <div class="text-center transform transition-all duration-700" data-aos="fade-up">
            <h2 class="text-3xl md:text-4xl font-black tracking-tighter mb-3 uppercase gold-text">Momen Kebersamaan</h2>
            <div class="flex flex-col items-center space-y-2">
                <p class="text-xs uppercase tracking-[0.3em] font-bold text-indigo-400 opacity-80">Flashback Buka Puasa Bersama (Bukber)</p>
                <div class="h-1 w-20 bg-gradient-to-r from-transparent via-indigo-500 to-transparent rounded-full"></div>
            </div>
        </div>

        <!-- Integrated Slider -->
        <div class="relative bg-slate-900/50 rounded-[2.5rem] md:rounded-[3rem] overflow-hidden shadow-[0_40px_100px_rgba(0,0,0,0.5)] border border-indigo-500/10 backdrop-blur-lg">
            <!-- Content (Swiper Slider) -->
            <div class="p-0 overflow-hidden bg-slate-950 flex items-center justify-center relative min-h-[300px] md:min-h-[600px]">
                <?php if (!empty($memories)): ?>
                    <div class="swiper memorySwiper w-full h-full">
                        <div class="swiper-wrapper">
                            <?php foreach ($memories as $m): ?>
                                <div class="swiper-slide flex flex-col items-center justify-center p-0 relative group">
                                    <div class="absolute inset-0 opacity-10 blur-3xl scale-125 pointer-events-none">
                                        <img src="storage/gallery/<?= htmlspecialchars($m['foto_path']) ?>" class="w-full h-full object-cover">
                                    </div>
                                    <div class="relative z-10 w-full h-full flex flex-col items-center justify-center p-3 md:p-8 lg:p-12">
                                        <!-- Cinematic 16:9 Container -->
                                        <div class="relative w-full max-w-5xl aspect-video rounded-3xl md:rounded-[2rem] overflow-hidden shadow-[0_0_100px_rgba(0,0,0,0.8)] bg-slate-900 border border-indigo-500/20 group">
                                            <img src="storage/gallery/<?= htmlspecialchars($m['foto_path']) ?>"
                                                class="w-full h-full object-cover transition-transform duration-1000 group-hover:scale-110"
                                                alt="<?= htmlspecialchars($m['judul']) ?>"
                                                onerror="this.src='assets/image/logo.webp'">

                                            <!-- Elegant Bottom-Right Floating Caption -->
                                            <div class="absolute bottom-6 right-6 z-20">
                                                <div class="bg-black/60 backdrop-blur-md border border-white/10 px-4 py-2 rounded-2xl flex flex-col items-end">
                                                    <h3 class="text-white text-[10px] md:text-sm font-black uppercase tracking-widest leading-none gold-text mb-1">
                                                        <?= htmlspecialchars($m['judul']) ?>
                                                    </h3>
                                                    <p class="text-indigo-300 text-[8px] md:text-[10px] font-bold uppercase tracking-widest opacity-80 italic">
                                                        <?= date('d M Y', strtotime($m['tanggal_kegiatan'])) ?>
                                                    </p>
                                                </div>
                                            </div>

                                            <!-- Bottom Gradient Overlay -->
                                            <div class="absolute inset-0 bg-gradient-to-t from-black/60 via-transparent to-transparent pointer-events-none"></div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <div class="swiper-button-next !hidden md:!flex shadow-2xl"></div>
                        <div class="swiper-button-prev !hidden md:!flex shadow-2xl"></div>
                        <div class="swiper-pagination !bottom-6"></div>
                    </div>
                <?php else: ?>
                    <div class="flex flex-col items-center justify-center py-40 text-slate-500 italic">
                        <i class="fas fa-camera-retro text-6xl mb-6 opacity-20"></i>
                        <p class="text-base tracking-widest uppercase font-bold">Galeri Masih Kosong</p>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Footer Removed for Clean Look -->
        </div>
    </div>

    <!-- Integrated Jadwal Imsakiyah Section -->
    <div id="jadwal-imsakiyah" class="relative z-10 w-full max-w-5xl px-4 mt-16 md:mt-24 space-y-8 md:space-y-12">
        <!-- Section Header -->
        <div class="text-center transform transition-all duration-700" data-aos="fade-up">
            <h2 class="text-3xl md:text-4xl font-black tracking-tighter mb-3 uppercase gold-text">Jadwal Imsakiyah</h2>
            <div class="flex flex-col items-center space-y-2">
                <p class="text-xs uppercase tracking-[0.3em] font-bold text-indigo-400 opacity-80">Wilayah Asia/Makassar (PTIK C 2024)</p>
                <div class="h-1 w-20 bg-gradient-to-r from-transparent via-indigo-500 to-transparent rounded-full"></div>
            </div>
        </div>

        <div class="relative bg-slate-900/50 rounded-[2.5rem] md:rounded-[3rem] overflow-hidden shadow-[0_40px_100px_rgba(0,0,0,0.5)] border border-indigo-500/10 backdrop-blur-lg flex flex-col max-h-[85vh]">
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
                        $today_masehi = date('j M');

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
        </div>
    </div>
    </div>
    </div>

    <!-- Simple Footer -->
    <footer class="relative z-10 w-full mt-12 pt-8 pb-4 border-t border-indigo-500/10 bg-slate-950/30 backdrop-blur-md">
        <div class="max-w-6xl mx-auto px-6 text-center">
            <p class="text-[10px] md:text-xs text-slate-500 font-bold uppercase tracking-[0.3em]">
                &copy; 2026 PTIK C 2024. All Rights Reserved.
            </p>
        </div>
    </footer>

    <script>
        // Anchor logic for Imsakiyah scroll
        function openImsakiyah() {
            const section = document.getElementById('jadwal-imsakiyah');
            if (section) {
                section.scrollIntoView({
                    behavior: 'smooth'
                });

                // Auto-scroll to today row inside the table container
                setTimeout(() => {
                    const todayRow = document.getElementById('todayRow');
                    if (todayRow) {
                        todayRow.scrollIntoView({
                            behavior: 'smooth',
                            block: 'center'
                        });
                    }
                }, 800);
            }
        }


        // Initialize Swiper on load
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

        function closeImsakiyah() {
            const modal = document.getElementById('imsakiyahModal');
            modal.classList.add('hidden');
            modal.classList.remove('flex');
            document.body.style.overflow = 'auto';
        }

        const container = document.getElementById('star-container');


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