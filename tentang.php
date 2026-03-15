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
        }

        .bg-mesh {
            background-color: #f8fafc;
            background-image:
                radial-gradient(at 0% 0%, rgba(79, 70, 229, 0.05) 0px, transparent 50%),
                radial-gradient(at 100% 0%, rgba(6, 182, 212, 0.05) 0px, transparent 50%),
                radial-gradient(at 100% 100%, rgba(79, 70, 229, 0.05) 0px, transparent 50%),
                radial-gradient(at 0% 100%, rgba(79, 70, 229, 0.05) 0px, transparent 50%);
        }

        .tech-card:hover {
            transform: translateY(-5px);
            transition: all 0.3s ease;
        }
    </style>
</head>

<body class="bg-mesh min-h-screen text-slate-800">
    <?php include 'includes/header_main.php'; ?>

    <!-- Spacer -->
    <div class="h-10"></div>

    <main class="container mx-auto px-6 py-12 flex-grow">
        <!-- Hero Section -->
        <h1 class="text-3xl font-bold mb-2 text-gray-800 scroll-reveal">Tentang Project</h1>
        <p class="text-gray-500 mb-8 scroll-reveal text-sm">Platform digital yang dirancang khusus untuk mempermudah koordinasi dan manajemen data mahasiswa PTIK C 2024.</p>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <!-- Deskripsi Project -->
            <div class="md:col-span-2 space-y-6">
                <section class="glass-card p-6 md:p-8 rounded-3xl shadow-sm border border-slate-200">
                    <h2 class="text-xl font-bold text-slate-800 mb-4 flex items-center gap-2">
                        <i class="fa fa-bullseye text-indigo-600"></i> Visi & Misi
                    </h2>
                    <p class="text-[13px] text-slate-600 leading-relaxed mb-4">
                        Web ini dirancang khusus untuk memenuhi kebutuhan sirkulasi informasi di dalam kelas PTIK C Angkatan 2024. Proyek ini lahir dari keinginan untuk memiliki sistem yang terintegrasi, transparan, dan menyenangkan bagi seluruh rekan mahasiswa.
                    </p>
                    <ul class="space-y-3">
                        <li class="flex items-start gap-3 text-xs text-slate-600">
                            <i class="fa fa-check-circle text-indigo-500 mt-1"></i>
                            <span><b>Sentralisasi Data:</b> Semua info tugas, jadwal, dan krs berada dalam satu pintu.</span>
                        </li>
                        <li class="flex items-start gap-3 text-xs text-slate-600">
                            <i class="fa fa-check-circle text-indigo-500 mt-1"></i>
                            <span><b>Transparansi Keuangan:</b> Laporan kas yang bisa dipantau secara langsung oleh seluruh mahasiswa.</span>
                        </li>
                        <li class="flex items-start gap-3 text-xs text-slate-600">
                            <i class="fa fa-check-circle text-indigo-500 mt-1"></i>
                            <span><b>Engagement:</b> Fitur Kuis Harian dan Pesan Anonim untuk menjaga kelas tetap hidup.</span>
                        </li>
                    </ul>
                </section>

                <section class="glass-card p-6 md:p-8 rounded-3xl shadow-sm border border-slate-200">
                    <h2 class="text-xl font-bold text-slate-800 mb-6 flex items-center gap-2">
                        <i class="fa fa-code-branch text-indigo-600"></i> Teknologi yang Digunakan
                    </h2>
                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
                        <div class="tech-card bg-white/50 p-4 rounded-2xl text-center border border-slate-100 shadow-sm">
                            <i class="fab fa-php text-3xl text-indigo-500 mb-2"></i>
                            <div class="text-[10px] font-bold uppercase text-slate-400">Backend</div>
                            <div class="text-xs font-bold text-slate-700">PHP</div>
                        </div>
                        <div class="tech-card bg-white/50 p-4 rounded-2xl text-center border border-slate-100 shadow-sm">
                            <i class="fa fa-database text-3xl text-blue-500 mb-2"></i>
                            <div class="text-[10px] font-bold uppercase text-slate-400">Database</div>
                            <div class="text-xs font-bold text-slate-700">MySQL</div>
                        </div>
                        <div class="tech-card bg-white/50 p-4 rounded-2xl text-center border border-slate-100 shadow-sm">
                            <img src="https://upload.wikimedia.org/wikipedia/commons/d/d5/Tailwind_CSS_Logo.svg" class="h-8 mx-auto mb-2" alt="Tailwind">
                            <div class="text-[10px] font-bold uppercase text-slate-400">Styling</div>
                            <div class="text-xs font-bold text-slate-700">TailwindCSS</div>
                        </div>
                        <div class="tech-card bg-white/50 p-4 rounded-2xl text-center border border-slate-100 shadow-sm">
                            <i class="fab fa-js text-3xl text-yellow-500 mb-2"></i>
                            <div class="text-[10px] font-bold uppercase text-slate-400">Interactivity</div>
                            <div class="text-xs font-bold text-slate-700">JavaScript</div>
                        </div>
                    </div>
                    <div class="mt-6 pt-6 border-t border-slate-100">
                        <h3 class="text-[10px] font-bold text-slate-500 uppercase mb-3">Library & Tools Tambahan:</h3>
                        <div class="flex flex-wrap gap-2">
                            <span class="px-2 py-1 bg-indigo-50 text-indigo-600 rounded text-[9px] font-bold">SweetAlert2</span>
                            <span class="px-2 py-1 bg-indigo-50 text-indigo-600 rounded text-[9px] font-bold">Chart.js</span>
                            <span class="px-2 py-1 bg-indigo-50 text-indigo-600 rounded text-[9px] font-bold">FontAwesome 6</span>
                            <span class="px-2 py-1 bg-indigo-50 text-indigo-600 rounded text-[9px] font-bold">Canvas Confetti</span>
                            <span class="px-2 py-1 bg-indigo-50 text-indigo-600 rounded text-[9px] font-bold">Google Fonts</span>
                            <span class="px-2 py-1 bg-indigo-50 text-indigo-600 rounded text-[9px] font-bold">Fonnte API</span>
                        </div>
                    </div>
                </section>
            </div>

            <!-- Developer Card -->
            <div class="space-y-6">
                <section class="bg-indigo-600 p-6 rounded-3xl shadow-lg shadow-indigo-200 text-white relative overflow-hidden">
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

                <section class="glass-card p-6 rounded-3xl shadow-sm border border-slate-200">
                    <h3 class="text-xs font-bold text-slate-800 mb-3">Versi Aplikasi</h3>
                    <div class="flex items-center justify-between py-2 border-b border-slate-50">
                        <span class="text-[11px] text-slate-500">Current Version</span>
                        <span class="text-[10px] font-bold text-indigo-600 px-2 py-0.5 bg-indigo-50 rounded">v2.8.5</span>
                    </div>
                    <div class="flex items-center justify-between py-2 border-b border-slate-50">
                        <span class="text-[11px] text-slate-500">Last Build</span>
                        <span class="text-[11px] font-medium text-slate-700">15 Maret 2026</span>
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
