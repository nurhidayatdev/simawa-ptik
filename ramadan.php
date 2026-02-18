<?php
include 'koneksi.php';
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <title>Ramadan Kareem 1447H - Poster</title>
    <?php include 'includes/head.php'; ?>
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
                <span class="text-indigo-400 font-bold tracking-[0.2em] text-[9px] md:text-xs uppercase block mb-2">Keluarga Besar</span>
                <div class="inline-block bg-indigo-600/20 px-4 py-1.5 rounded-lg border border-indigo-500/30 backdrop-blur-sm">
                    <span class="text-white text-base md:text-lg font-black tracking-widest uppercase">PTIK C 2024</span>
                </div>
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

    <script>
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