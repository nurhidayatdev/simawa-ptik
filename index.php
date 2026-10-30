<?php
include 'koneksi.php';
include_once 'functions/foto_helpers.php';

$mhs = mysqli_query($koneksi, "SELECT COUNT(*) FROM mahasiswa");
$jml = mysqli_fetch_assoc($mhs);
$sms_aktif = mysqli_query($koneksi, "SELECT semester_ke FROM periode WHERE status = 'aktif'");
$sms = mysqli_fetch_assoc($sms_aktif);
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <title>SIMAWA PTIK 2024 - Sistem Informasi Mahasiswa PTIK UNM</title>
    <?php include 'includes/head.php'; ?>
    <style>
        @keyframes fadeInDown {
            from {
                opacity: 0;
                transform: translateY(-20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes slowZoom {
            from {
                transform: scale(1);
            }
            to {
                transform: scale(1.1);
            }
        }

        .animate-fade-in-down {
            animation: fadeInDown 1s ease-out forwards;
        }

        .animate-fade-in-up {
            animation: fadeInUp 1s ease-out forwards;
        }

        .animate-slow-zoom {
            animation: slowZoom 20s linear infinite alternate;
        }

        .delay-100 {
            animation-delay: 0.2s;
        }

        .delay-200 {
            animation-delay: 0.4s;
        }

        /* Scroll Reveal Animation Styles */
        .scroll-reveal {
            opacity: 0;
            transform: translateY(30px);
            transition: all 0.8s cubic-bezier(0.4, 0, 0.2, 1);
            will-change: opacity, transform;
        }

        .scroll-reveal.active {
            opacity: 1;
            transform: translateY(0);
        }

        .reveal-delay-1 {
            transition-delay: 0.1s;
        }

        .reveal-delay-2 {
            transition-delay: 0.2s;
        }

        .reveal-delay-3 {
            transition-delay: 0.3s;
        }

        .reveal-delay-4 {
            transition-delay: 0.4s;
        }
    </style>
</head>

<body class="bg-gray-50 dark:bg-slate-950 min-h-screen flex flex-col transition-colors duration-300">
    <?php include 'includes/header_main.php'; ?>

    <!-- spacer to avoid content hidden under fixed header -->
    <div class="h-14"></div>

    <!-- Hero Section -->
    <section class="relative min-h-[90vh] flex items-center justify-center overflow-hidden bg-slate-900">
        <!-- Background -->
        <div class="absolute inset-0 z-0">
            <div class="absolute inset-0 bg-gradient-to-t from-slate-950 via-slate-900/60 to-transparent z-10"></div>
            
        </div>

        <!-- Decorative Elements -->
        <div class="absolute top-1/4 -left-20 w-80 h-80 bg-indigo-600/20 rounded-full blur-[100px] z-0 animate-pulse"></div>
        <div class="absolute bottom-1/4 -right-20 w-80 h-80 bg-indigo-500/20 rounded-full blur-[100px] z-0"></div>

        <div class="relative z-20 container mx-auto px-4 text-center">
            <!-- Glassmorphism Badge -->
            <div class="inline-flex items-center gap-2 px-4 py-2 rounded-full bg-white/5 backdrop-blur-md border border-white/10 mb-8 animate-fade-in-down">
                <span class="flex h-2 w-2 rounded-full bg-indigo-500 animate-ping"></span>
                <span class="text-indigo-200 text-xs font-bold uppercase tracking-widest">Sistem Informasi Mahasiswa</span>
            </div>

            <!-- Main Heading -->
            <h2 class="text-4xl md:text-7xl font-black text-white mb-6 tracking-tight leading-tight animate-fade-in-up">
                SIMAWA PTIK <span class="text-transparent bg-clip-text bg-gradient-to-r from-indigo-400 to-indigo-200 uppercase">2024</span>
            </h2>

            <!-- Subheading -->
            <p class="text-lg md:text-xl text-indigo-100/80 mb-10 max-w-2xl mx-auto leading-relaxed font-light animate-fade-in-up delay-100">
                Pusat informasi dan manajemen akademik mahasiswa Program Studi Pendidikan Teknik Informatika dan Komputer Angkatan 2024 — Fakultas Teknik, Universitas Negeri Makassar.
            </p>

            <!-- CTA Buttons -->
            <div class="flex flex-col sm:flex-row items-center justify-center gap-4 animate-fade-in-up delay-200">
                <a href="#fitur" class="w-full sm:w-auto px-6 py-3 sm:px-8 sm:py-4 bg-indigo-600 hover:bg-indigo-500 text-white rounded-xl sm:rounded-2xl text-sm sm:text-base font-bold transition-all hover:shadow-[0_0_20px_rgba(79,70,229,0.4)] flex items-center justify-center gap-2 group">
                    <span>Jelajahi Fitur</span>
                    <i class="fas fa-arrow-down text-xs sm:text-sm group-hover:translate-y-1 transition-transform"></i>
                </a>
                <a href="akademik" class="w-full sm:w-auto px-6 py-3 sm:px-8 sm:py-4 bg-white/5 hover:bg-white/10 backdrop-blur-md text-white border border-white/10 rounded-xl sm:rounded-2xl text-sm sm:text-base font-bold transition-all">
                    Informasi Akademik
                </a>
            </div>
        </div>

        <!-- Scroll Indicator -->
        <div class="absolute bottom-10 left-1/2 -translate-x-1/2 z-20 animate-bounce hidden md:block">
            <div class="w-6 h-10 rounded-full border-2 border-white/30 flex justify-center p-1">
                <div class="w-1 h-2 bg-white/60 rounded-full"></div>
            </div>
        </div>
    </section>

    

    <!-- Fitur & Fasilitas Sistem -->
    <section id="fitur" class="py-16 bg-slate-50 dark:bg-slate-950 border-t border-slate-100 dark:border-slate-900 transition-colors">
        <div class="container mx-auto px-4 md:px-16 lg:px-24">
            <div class="text-center mb-12 scroll-reveal">
                <h2 class="text-2xl md:text-3xl font-bold text-gray-800 dark:text-white mb-3">Fitur & Fasilitas Sistem</h2>
                <p class="text-gray-600 dark:text-slate-400 text-sm max-w-2xl mx-auto hidden md:block">Platform terintegrasi untuk mendukung seluruh kebutuhan akademik mahasiswa dalam satu ekosistem.</p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 md:gap-8">
                <!-- Feature 1 -->
                <div class="bg-white dark:bg-slate-900 p-6 rounded-2xl shadow-sm border border-slate-100 dark:border-slate-800 hover:shadow-md transition-all scroll-reveal reveal-delay-1">
                    <div class="flex items-center gap-3 mb-4">
                        <div class="w-10 h-10 flex-shrink-0 bg-indigo-100 dark:bg-indigo-900/30 rounded-lg flex items-center justify-center text-indigo-600 dark:text-indigo-400 text-lg">
                            <i class="fa fa-desktop"></i>
                        </div>
                        <h3 class="text-base font-bold text-slate-800 dark:text-slate-200 leading-tight">Dashboard Akademik</h3>
                    </div>
                    <p class="text-slate-600 dark:text-slate-400 text-sm leading-relaxed">
                        Pusat kendali utama yang menyajikan statistik kehadiran, deadline tugas terdekat, laporan kas, dan berita akademik dalam satu antarmuka responsif.
                    </p>
                </div>

                <!-- Feature 2 -->
                <div class="bg-white dark:bg-slate-900 p-6 rounded-2xl shadow-sm border border-slate-100 dark:border-slate-800 hover:shadow-md transition-all scroll-reveal">
                    <div class="flex items-center gap-3 mb-4">
                        <div class="w-10 h-10 flex-shrink-0 bg-indigo-100 dark:bg-indigo-900/30 rounded-lg flex items-center justify-center text-indigo-600 dark:text-indigo-400 text-lg">
                            <i class="fa fa-tasks"></i>
                        </div>
                        <h3 class="text-base font-bold text-slate-800 dark:text-slate-200 leading-tight">Manajemen Tugas</h3>
                    </div>
                    <p class="text-slate-600 dark:text-slate-400 text-sm leading-relaxed">
                        Pengingat dan pelacak tugas perkuliahan dengan hitung mundur deadline, status penyelesaian pribadi, dan filter per mata kuliah.
                    </p>
                </div>

                <!-- Feature 3 -->
                <div class="bg-white dark:bg-slate-900 p-6 rounded-2xl shadow-sm border border-slate-100 dark:border-slate-800 hover:shadow-md transition-all scroll-reveal reveal-delay-3">
                    <div class="flex items-center gap-3 mb-4">
                        <div class="w-10 h-10 flex-shrink-0 bg-indigo-100 dark:bg-indigo-900/30 rounded-lg flex items-center justify-center text-indigo-600 dark:text-indigo-400 text-lg">
                            <i class="fa fa-wallet"></i>
                        </div>
                        <h3 class="text-base font-bold text-slate-800 dark:text-slate-200 leading-tight">Transparansi Keuangan</h3>
                    </div>
                    <p class="text-slate-600 dark:text-slate-400 text-sm leading-relaxed">
                        Sistem pelaporan keuangan dan kas yang transparan dan akuntabel, dapat dipantau oleh setiap mahasiswa secara real-time.
                    </p>
                </div>

                <!-- Feature 4 -->
                <div class="bg-white dark:bg-slate-900 p-6 rounded-2xl shadow-sm border border-slate-100 dark:border-slate-800 hover:shadow-md transition-all scroll-reveal reveal-delay-1">
                    <div class="flex items-center gap-3 mb-4">
                        <div class="w-10 h-10 flex-shrink-0 bg-indigo-100 dark:bg-indigo-900/30 rounded-lg flex items-center justify-center text-indigo-600 dark:text-indigo-400 text-lg">
                            <i class="fa fa-calendar-check"></i>
                        </div>
                        <h3 class="text-base font-bold text-slate-800 dark:text-slate-200 leading-tight">Presensi Perkuliahan</h3>
                    </div>
                    <p class="text-slate-600 dark:text-slate-400 text-sm leading-relaxed">
                        Pencatatan kehadiran digital per pertemuan perkuliahan lengkap dengan persentase dan rekapitulasi kehadiran per semester.
                    </p>
                </div>

                <!-- Feature 5 -->
                <div class="bg-white dark:bg-slate-900 p-6 rounded-2xl shadow-sm border border-slate-100 dark:border-slate-800 hover:shadow-md transition-all scroll-reveal reveal-delay-2">
                    <div class="flex items-center gap-3 mb-4">
                        <div class="w-10 h-10 flex-shrink-0 bg-indigo-100 dark:bg-indigo-900/30 rounded-lg flex items-center justify-center text-indigo-600 dark:text-indigo-400 text-lg">
                            <i class="fa fa-calendar-alt"></i>
                        </div>
                        <h3 class="text-base font-bold text-slate-800 dark:text-slate-200 leading-tight">Kalender & Jadwal</h3>
                    </div>
                    <p class="text-slate-600 dark:text-slate-400 text-sm leading-relaxed">
                        Jadwal perkuliahan mingguan interaktif yang tersinkronisasi dengan slot waktu, ruang kelas, dosen pengampu, dan kalender akademik.
                    </p>
                </div>

                <!-- Feature 6 -->
                <div class="bg-white dark:bg-slate-900 p-6 rounded-2xl shadow-sm border border-slate-100 dark:border-slate-800 hover:shadow-md transition-all scroll-reveal reveal-delay-3">
                    <div class="flex items-center gap-3 mb-4">
                        <div class="w-10 h-10 flex-shrink-0 bg-indigo-100 dark:bg-indigo-900/30 rounded-lg flex items-center justify-center text-indigo-600 dark:text-indigo-400 text-lg">
                            <i class="fa fa-users-cog"></i>
                        </div>
                        <h3 class="text-base font-bold text-slate-800 dark:text-slate-200 leading-tight">Manajemen Kelompok</h3>
                    </div>
                    <p class="text-slate-600 dark:text-slate-400 text-sm leading-relaxed">
                        Fitur pembagian kelompok kuliah secara otomatis dan berimbang untuk kemudahan pengerjaan proyek akademik bersama.
                    </p>
                </div>
            </div>
        </div>
    </section>

    <?php include 'includes/footer_main.php'; ?>
    <?php include 'includes/script_main.php'; ?>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const observerOptions = {
                threshold: 0.15,
                rootMargin: '0px 0px -50px 0px'
            };

            const observer = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        entry.target.classList.add('active');
                    } else {
                        entry.target.classList.remove('active');
                    }
                });
            }, observerOptions);

            const revealElements = document.querySelectorAll('.scroll-reveal');
            revealElements.forEach(el => observer.observe(el));
        });
    </script>
</body>

</html>
