<?php
include 'koneksi.php';
include_once 'functions/foto_helpers.php';

// Ambil periode aktif untuk konteks jika perlu
$q_periode = mysqli_query($koneksi, "SELECT * FROM periode WHERE status = 'aktif' LIMIT 1");
$periode = mysqli_fetch_assoc($q_periode);
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <title>Informasi Akademik - SIMAWA PTIK 2024</title>
    <?php include 'includes/head.php'; ?>
    <style>
        .glass-card {
            background: rgba(255, 255, 255, 0.8);
            backdrop-filter: blur(12px);
            border: 1px solid rgba(255, 255, 255, 0.3);
            transition: background 0.3s, border 0.3s;
        }

        .dark .glass-card {
            background: rgba(15, 23, 42, 0.5);
            border: 1px solid rgba(255, 255, 255, 0.1);
        }

        .text-gradient {
            background: linear-gradient(135deg, #4f46e5 0%, #06b6d4 100%);
            -webkit-background-clip: text;
            background-clip: text;
            -webkit-text-fill-color: transparent;
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
    </style>
</head>

<body class="bg-mesh min-h-screen text-slate-800 transition-colors duration-300">

    <?php include 'includes/header_main.php'; ?>

    <!-- Spacer -->
    <div class="h-10"></div>

    <main class="container mx-auto px-6 py-12 flex-grow">
        <!-- Hero Section -->
        <div class="max-w-4xl mx-auto text-center mb-16 scroll-reveal">
            <h1 class="text-3xl md:text-5xl font-black mb-4 text-slate-900 dark:text-white transition-colors">
                Informasi & Identitas <span class="text-gradient">SIMAWA PTIK 2024</span>
            </h1>
            <p class="text-slate-500 dark:text-slate-400 text-sm md:text-base max-w-xl mx-auto transition-colors">
                Mengenal lebih dekat identitas akademik, visi, dan filosofi platform mahasiswa PTIK Angkatan 2024 FT UNM.
            </p>
        </div>

        <!-- Section: Identitas -->
        <section class="glass-card p-6 md:p-12 rounded-[2.5rem] mb-16 border border-slate-200 dark:border-slate-800 shadow-sm transition-colors">
            <div class="flex flex-col md:flex-row gap-8 items-center">
                <div class="w-full md:w-7/12 flex flex-col justify-center py-2 order-1 md:order-1">
                    <div class="inline-block px-3 py-1 bg-indigo-100 dark:bg-indigo-900/30 text-indigo-700 dark:text-indigo-300 rounded-full text-[10px] font-bold uppercase tracking-widest mb-4 w-max transition-colors">
                        Identitas Akademik
                    </div>
                    <h2 class="text-2xl font-bold text-slate-800 dark:text-white mb-2 transition-colors">Program Studi <span class="text-indigo-600 dark:text-indigo-400">PTIK 2024</span></h2>
                    <p class="text-slate-400 dark:text-slate-500 text-xs mb-8 transition-colors">Pendidikan Teknik Informatika dan Komputer - Angkatan 2024.</p>

                    <div class="space-y-4">
                        <div class="flex gap-5 p-4 rounded-2xl bg-white dark:bg-slate-900 border border-slate-100 dark:border-slate-800 shadow-sm transition-all hover:border-indigo-200 dark:hover:border-indigo-500">
                            <div class="h-10 w-10 shrink-0 bg-indigo-50 dark:bg-indigo-950/50 text-indigo-600 dark:text-indigo-400 rounded-xl flex items-center justify-center font-black text-sm transition-colors">P</div>
                            <div>
                                <p class="text-xs font-bold text-slate-700 dark:text-slate-200 leading-none transition-colors">Pendidikan</p>
                                <p class="text-[11px] text-slate-400 dark:text-slate-500 mt-1.5 transition-colors">Membentuk pendidik dan praktisi teknologi yang berkompeten dan berkarakter.</p>
                            </div>
                        </div>
                        <div class="flex gap-5 p-4 rounded-2xl bg-white dark:bg-slate-900 border border-slate-100 dark:border-slate-800 shadow-sm transition-all hover:border-cyan-200 dark:hover:border-cyan-500">
                            <div class="h-10 w-10 shrink-0 bg-cyan-50 dark:bg-cyan-950/50 text-cyan-600 dark:text-cyan-400 rounded-xl flex items-center justify-center font-black text-sm transition-colors">TIK</div>
                            <div>
                                <p class="text-xs font-bold text-slate-700 dark:text-slate-200 leading-none transition-colors">Teknik Informatika & Komputer</p>
                                <p class="text-[11px] text-slate-400 dark:text-slate-500 mt-1.5 transition-colors">Fakultas Teknik, Universitas Negeri Makassar.</p>
                            </div>
                        </div>
                        <div class="flex gap-5 p-4 rounded-2xl bg-white dark:bg-slate-900 border border-slate-100 dark:border-slate-800 shadow-sm transition-all hover:border-slate-200 dark:hover:border-slate-600">
                            <div class="h-10 w-10 shrink-0 bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400 rounded-xl flex items-center justify-center font-black text-sm transition-colors">24</div>
                            <div>
                                <p class="text-xs font-bold text-slate-700 dark:text-slate-200 leading-none transition-colors">Angkatan 2024</p>
                                <p class="text-[11px] text-slate-400 dark:text-slate-500 mt-1.5 transition-colors">Tahun penerimaan mahasiswa baru angkatan 2024.</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Branding Card -->
                <div class="w-full md:w-5/12 flex order-2 md:order-2">
                    <div class="p-8 rounded-[2rem] bg-indigo-950 text-white shadow-xl relative overflow-hidden w-full flex flex-col justify-center">
                        <div class="absolute top-0 right-0 w-32 h-32 bg-white/5 rounded-full -mr-16 -mt-16"></div>
                        <div class="absolute bottom-0 left-0 w-20 h-20 bg-white/5 rounded-full -ml-10 -mb-10"></div>

                        <h3 class="text-[10px] font-bold mb-6 flex items-center gap-2 uppercase tracking-widest text-indigo-300">
                            <i class="fa fa-graduation-cap"></i> Nilai Akademik
                        </h3>
                        <div class="space-y-4 border-l-2 border-indigo-500/30 pl-6">
                            <div class="flex items-center gap-4">
                                <span class="w-16 font-black text-indigo-200 text-xs">Inovatif</span>
                                <span class="text-[11px] opacity-70">Mampu beradaptasi dengan perkembangan teknologi</span>
                            </div>
                            <div class="flex items-center gap-4">
                                <span class="w-16 font-black text-indigo-200 text-xs">Kolaboratif</span>
                                <span class="text-[11px] opacity-70">Saling mendukung antar kelas dan mahasiswa</span>
                            </div>
                            <div class="flex items-center gap-4">
                                <span class="w-16 font-black text-indigo-200 text-xs">Akuntabel</span>
                                <span class="text-[11px] opacity-70">Transparansi data akademik dan administrasi</span>
                            </div>
                        </div>
                        <div class="mt-8 pt-6 border-t border-white/10">
                            <p class="text-sm font-bold text-indigo-300">SIMAWA PTIK 2024</p>
                            <p class="text-[9px] opacity-40 uppercase tracking-widest">Portal Akademik Mahasiswa</p>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </main>

    <?php include 'includes/footer_main.php'; ?>
    <?php include 'includes/script_main.php'; ?>
</body>

</html>
