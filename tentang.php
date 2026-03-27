<?php
include 'koneksi.php';
include_once 'functions/foto_helpers.php';

$q_periode = mysqli_query($koneksi, "SELECT * FROM periode WHERE status = 'aktif' LIMIT 1");
$periode = mysqli_fetch_assoc($q_periode);
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <title>Tentang Project - PTIK C 2024</title>
    <?php include 'includes/head.php'; ?>
    <style>
        .glass-card {
            background: rgba(255, 255, 255, 0.8);
            backdrop-filter: blur(12px);
            border: 1px solid rgba(255, 255, 255, 0.3);
            transition: background 0.3s, border 0.3s;
        }
        .dark .glass-card {
            background: rgba(15, 23, 42, 0.6);
            border: 1px solid rgba(255, 255, 255, 0.1);
        }

        .bg-mesh {
            background-color: #f8fafc;
            background-image:
                radial-gradient(at 0% 0%, rgba(79, 70, 229, 0.05) 0px, transparent 50%),
                radial-gradient(at 100% 0%, rgba(6, 182, 212, 0.05) 0px, transparent 50%),
                radial-gradient(at 100% 100%, rgba(79, 70, 229, 0.05) 0px, transparent 50%),
                radial-gradient(at 0% 100%, rgba(79, 70, 229, 0.05) 0px, transparent 50%);
            transition: background-color 0.3s;
        }
        .dark .bg-mesh {
            background-color: #020617;
            background-image:
                radial-gradient(at 0% 0%, rgba(79, 70, 229, 0.1) 0px, transparent 50%),
                radial-gradient(at 100% 0%, rgba(6, 182, 212, 0.1) 0px, transparent 50%),
                radial-gradient(at 100% 100%, rgba(79, 70, 229, 0.1) 0px, transparent 50%),
                radial-gradient(at 0% 100%, rgba(79, 70, 229, 0.1) 0px, transparent 50%);
        }

        .tech-card:hover {
            transform: translateY(-5px);
            transition: all 0.3s ease;
        }
    </style>
</head>

<body class="bg-mesh min-h-screen text-slate-800 transition-colors duration-300">

    <?php include 'includes/header_main.php'; ?>

    <!-- Spacer -->
    <div class="h-10"></div>

    <main class="container mx-auto px-6 py-12 flex-grow">
        <!-- Hero Section -->
        <h1 class="text-3xl font-bold mb-2 text-gray-800 dark:text-white transition-colors">Tentang Project</h1>
        <p class="text-gray-500 dark:text-slate-400 mb-8 transition-colors text-sm">Platform digital yang dirancang khusus untuk mempermudah koordinasi dan manajemen data mahasiswa PTIK C 2024.</p>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <!-- Deskripsi Project -->
            <div class="md:col-span-2 space-y-6">
                <section class="glass-card p-6 md:p-8 rounded-3xl shadow-sm border border-slate-200 dark:border-slate-800 transition-colors">
                    <h2 class="text-xl font-bold text-slate-800 dark:text-white mb-4 flex items-center gap-2 transition-colors">
                        <i class="fa fa-bullseye text-indigo-600 dark:text-indigo-400"></i> Visi & Misi
                    </h2>
                    <p class="text-[13px] text-slate-600 dark:text-slate-400 leading-relaxed mb-4 transition-colors">
                        Web ini dirancang khusus untuk memenuhi kebutuhan sirkulasi informasi di dalam kelas PTIK C Angkatan 2024. Proyek ini lahir dari keinginan untuk memiliki sistem yang terintegrasi, transparan, dan menyenangkan bagi seluruh rekan mahasiswa.
                    </p>
                    <ul class="space-y-3">
                        <li class="flex items-start gap-3 text-xs text-slate-600 dark:text-slate-400 transition-colors">
                            <i class="fa fa-check-circle text-indigo-500 dark:text-indigo-400 mt-1 transition-colors"></i>
                            <span><b>Sentralisasi Data:</b> Semua info tugas, jadwal, and krs berada dalam satu pintu.</span>
                        </li>
                        <li class="flex items-start gap-3 text-xs text-slate-600 dark:text-slate-400 transition-colors">
                            <i class="fa fa-check-circle text-indigo-500 dark:text-indigo-400 mt-1 transition-colors"></i>
                            <span><b>Transparansi Keuangan:</b> Laporan kas yang bisa dipantau secara langsung oleh seluruh mahasiswa.</span>
                        </li>
                        <li class="flex items-start gap-3 text-xs text-slate-600 dark:text-slate-400 transition-colors">
                            <i class="fa fa-check-circle text-indigo-500 dark:text-indigo-400 mt-1 transition-colors"></i>
                            <span><b>Engagement:</b> Fitur Kuis Harian dan Pesan Anonim untuk menjaga kelas tetap hidup.</span>
                        </li>
                    </ul>
                </section>

                <section class="glass-card p-6 md:p-8 rounded-3xl shadow-sm border border-slate-200 dark:border-slate-800 transition-colors">
                    <h2 class="text-xl font-bold text-slate-800 dark:text-white mb-6 flex items-center gap-2 transition-colors">
                        <i class="fa fa-code-branch text-indigo-600 dark:text-indigo-400 transition-colors"></i> Teknologi yang Digunakan
                    </h2>
                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
                        <div class="tech-card bg-white/50 dark:bg-slate-800/50 p-4 rounded-2xl text-center border border-slate-100 dark:border-slate-700 shadow-sm transition-colors">
                            <i class="fab fa-php text-3xl text-indigo-500 mb-2"></i>
                            <div class="text-[10px] font-bold uppercase text-slate-400">Backend</div>
                            <div class="text-xs font-bold text-slate-700 dark:text-slate-200 transition-colors">PHP</div>
                        </div>
                        <div class="tech-card bg-white/50 dark:bg-slate-800/50 p-4 rounded-2xl text-center border border-slate-100 dark:border-slate-700 shadow-sm transition-colors">
                            <i class="fa fa-database text-3xl text-blue-500 mb-2"></i>
                            <div class="text-[10px] font-bold uppercase text-slate-400">Database</div>
                            <div class="text-xs font-bold text-slate-700 dark:text-slate-200 transition-colors">MySQL</div>
                        </div>
                        <div class="tech-card bg-white/50 dark:bg-slate-800/50 p-4 rounded-2xl text-center border border-slate-100 dark:border-slate-700 shadow-sm transition-colors">
                            <img src="https://upload.wikimedia.org/wikipedia/commons/d/d5/Tailwind_CSS_Logo.svg" class="h-8 mx-auto mb-2" alt="Tailwind">
                            <div class="text-[10px] font-bold uppercase text-slate-400">Styling</div>
                            <div class="text-xs font-bold text-slate-700 dark:text-slate-200 transition-colors">TailwindCSS</div>
                        </div>
                        <div class="tech-card bg-white/50 dark:bg-slate-800/50 p-4 rounded-2xl text-center border border-slate-100 dark:border-slate-700 shadow-sm transition-colors">
                            <i class="fab fa-js text-3xl text-yellow-500 mb-2"></i>
                            <div class="text-[10px] font-bold uppercase text-slate-400">Interactivity</div>
                            <div class="text-xs font-bold text-slate-700 dark:text-slate-200 transition-colors">JavaScript</div>
                        </div>
                    </div>
                    <div class="mt-6 pt-6 border-t border-slate-100 dark:border-slate-800 transition-colors">
                        <h3 class="text-[10px] font-bold text-slate-500 uppercase mb-3 transition-colors">Library & Tools Tambahan:</h3>
                        <div class="flex flex-wrap gap-2">
                            <span class="px-2 py-1 bg-indigo-50 dark:bg-indigo-900/30 text-indigo-600 dark:text-indigo-400 rounded text-[9px] font-bold transition-colors">SweetAlert2</span>
                            <span class="px-2 py-1 bg-indigo-50 dark:bg-indigo-900/30 text-indigo-600 dark:text-indigo-400 rounded text-[9px] font-bold transition-colors">Chart.js</span>
                            <span class="px-2 py-1 bg-indigo-50 dark:bg-indigo-900/30 text-indigo-600 dark:text-indigo-400 rounded text-[9px] font-bold transition-colors">FontAwesome 6</span>
                            <span class="px-2 py-1 bg-indigo-50 dark:bg-indigo-900/30 text-indigo-600 dark:text-indigo-400 rounded text-[9px] font-bold transition-colors">Canvas Confetti</span>
                            <span class="px-2 py-1 bg-indigo-50 dark:bg-indigo-900/30 text-indigo-600 dark:text-indigo-400 rounded text-[9px] font-bold transition-colors">Google Fonts</span>
                            <span class="px-2 py-1 bg-indigo-50 dark:bg-indigo-900/30 text-indigo-600 dark:text-indigo-400 rounded text-[9px] font-bold transition-colors">Fonnte API</span>
                        </div>
                    </div>
                </section>
            </div>

            <!-- Developer Card -->
            <div class="space-y-6">
                <section class="bg-indigo-600 dark:bg-indigo-900/60 p-6 rounded-3xl shadow-lg shadow-indigo-200 dark:shadow-black/20 text-white relative overflow-hidden transition-colors">
                    <div class="absolute -right-4 -bottom-4 opacity-10 text-8xl">
                        <i class="fa fa-code"></i>
                    </div>
                    <h3 class="text-[10px] font-bold uppercase tracking-widest opacity-80 mb-4">Pengembang</h3>
                    <div class="flex items-center gap-4 mb-4">
                        <div class="h-16 w-16 rounded-2xl overflow-hidden border-2 border-white/20 p-2 bg-white/10">
                            <img src="assets/image/logo.webp" alt="Dev" class="w-full h-full object-contain">
                        </div>
                        <div>
                            <div class="font-bold text-lg leading-tight">Admin PTIK C</div>
                            <div class="text-[10px] opacity-80 italic mt-1">Bismillah Fullstack Developer</div>
                        </div>
                    </div>
                    <p class="text-[11px] leading-relaxed opacity-90">
                        Dikembangkan dengan penuh dedikasi untuk kemajuan kolektif kelas PTIK C 2024.
                    </p>
                </section>

                <section class="glass-card p-6 rounded-3xl shadow-sm border border-slate-200 dark:border-slate-800 transition-colors">
                    <h3 class="text-xs font-bold text-slate-800 dark:text-white mb-3 transition-colors">Versi Aplikasi</h3>
                    <div class="flex items-center justify-between py-2 border-b border-slate-50 dark:border-slate-800 transition-colors">
                        <span class="text-[11px] text-slate-500">Current Version</span>
                        <span class="text-[10px] font-bold text-indigo-600 dark:text-indigo-400 px-2 py-0.5 bg-indigo-50 dark:bg-indigo-900/30 rounded transition-colors">v3.0.1</span>
                    </div>
                    <div class="flex items-center justify-between py-2 border-b border-slate-50 dark:border-slate-800 transition-colors">
                        <span class="text-[11px] text-slate-500">Last Build</span>
                        <span class="text-[11px] font-medium text-slate-700 dark:text-slate-300 transition-colors">27 Maret 2026</span>
                    </div>
                    <div class="flex items-center justify-between py-2">
                        <span class="text-[11px] text-slate-500">Environment</span>
                        <span class="text-[11px] font-bold text-emerald-600 uppercase">Production</span>
                    </div>
                </section>
            </div>
        </div>
    </main>

    <?php include 'includes/footer_main.php'; ?>
    <?php include 'includes/script_main.php'; ?>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.2/gsap.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            gsap.from("main > div > *", {
                opacity: 0,
                y: 30,
                duration: 0.8,
                stagger: 0.2,
                ease: "power3.out"
            });
            gsap.from(".tech-card", {
                scale: 0.8,
                opacity: 0,
                duration: 0.5,
                stagger: 0.1,
                ease: "back.out(1.7)",
                delay: 0.6
            });
        });
    </script>
</body>

</html>
