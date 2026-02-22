<?php
include 'koneksi.php';

// Ambil periode aktif untuk konteks jika perlu
$q_periode = mysqli_query($koneksi, "SELECT * FROM periode WHERE status = 'aktif' LIMIT 1");
$periode = mysqli_fetch_assoc($q_periode);
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <title>Informasi Kelas - PTIK C 2024</title>
    <?php include 'includes/head.php'; ?>
    <style>
        .glass-card {
            background: rgba(255, 255, 255, 0.8);
            backdrop-filter: blur(12px);
            border: 1px solid rgba(255, 255, 255, 0.3);
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
        }

        .hover-scale {
            transition: transform 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);
        }

        .hover-scale:hover {
            transform: scale(1.05);
        }
    </style>
</head>

<body class="bg-mesh min-h-screen text-slate-800">
    <?php include 'includes/header_main.php'; ?>

    <!-- Spacer -->
    <div class="h-16"></div>

    <main class="container mx-auto px-4 py-12 max-w-5xl">
        <!-- Hero Section -->
        <div class="text-center mb-12">
            <h1 class="text-2xl md:text-4xl font-bold text-indigo-950 mb-3 tracking-tight">
                Identitas <span class="text-indigo-600 uppercase">Kelas Kita</span>
            </h1>
            <p class="text-slate-500 max-w-2xl mx-auto text-xs md:text-sm leading-relaxed">
                Mengenal lebih dalam simbolisme, filosofi, dan sejarah di balik identitas digital PTIK C Angkatan 2024.
            </p>
        </div>

        <!-- Section: Penjelasan Nama -->
        <section class="mb-16">
            <div class="glass-card p-6 md:p-10 rounded-[2.5rem] shadow-sm border border-slate-200 flex flex-col md:flex-row gap-10 items-stretch">
                <!-- Explanation List -->
                <div class="w-full md:w-7/12 flex flex-col justify-center py-2 order-1 md:order-1">
                    <div class="inline-block px-3 py-1 bg-indigo-100 text-indigo-700 rounded-full text-[10px] font-bold uppercase tracking-widest mb-4 w-max">
                        Etimologi
                    </div>
                    <h2 class="text-2xl font-bold text-slate-800 mb-2">Arti <span class="text-indigo-600">PTIK C 24</span></h2>
                    <p class="text-slate-400 text-xs mb-8">Simbol perjuangan dan identitas kolektif kita.</p>

                    <div class="space-y-4">
                        <div class="flex gap-5 p-4 rounded-2xl bg-white border border-slate-100 shadow-sm transition-all hover:border-indigo-200">
                            <div class="h-10 w-10 shrink-0 bg-indigo-50 text-indigo-600 rounded-xl flex items-center justify-center font-black text-sm">P</div>
                            <div>
                                <p class="text-xs font-bold text-slate-700 leading-none">Pendidikan Teknik Informatika & Komputer</p>
                                <p class="text-[11px] text-slate-400 mt-1.5">Program Studi kita di Universitas Negeri Makassar.</p>
                            </div>
                        </div>
                        <div class="flex gap-5 p-4 rounded-2xl bg-white border border-slate-100 shadow-sm transition-all hover:border-cyan-200">
                            <div class="h-10 w-10 shrink-0 bg-cyan-50 text-cyan-600 rounded-xl flex items-center justify-center font-black text-sm">C</div>
                            <div>
                                <p class="text-xs font-bold text-slate-700 leading-none">Kelas C</p>
                                <p class="text-[11px] text-slate-400 mt-1.5">Identitas rombongan belajar angkatan kita.</p>
                            </div>
                        </div>
                        <div class="flex gap-5 p-4 rounded-2xl bg-white border border-slate-100 shadow-sm transition-all hover:border-slate-200">
                            <div class="h-10 w-10 shrink-0 bg-slate-100 text-slate-600 rounded-xl flex items-center justify-center font-black text-sm">24</div>
                            <div>
                                <p class="text-xs font-bold text-slate-700 leading-none">Angkatan 2024</p>
                                <p class="text-[11px] text-slate-400 mt-1.5">Tahun dimulainya perjalanan akademik kita.</p>
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
                            <i class="fa fa-shuttle-space"></i> Filosofi Nama
                        </h3>
                        <div class="space-y-4 border-l-2 border-indigo-500/30 pl-6">
                            <div class="flex items-center gap-4">
                                <span class="w-10 font-black text-indigo-200 text-sm">Pro</span>
                                <span class="text-[11px] opacity-70 whitespace-nowrap uppercase tracking-wider">= Programmer</span>
                            </div>
                            <div class="flex items-center gap-4">
                                <span class="w-10 font-black text-indigo-200 text-sm">Tech</span>
                                <span class="text-[11px] opacity-70 whitespace-nowrap uppercase tracking-wider">= Technology</span>
                            </div>
                            <div class="flex items-center gap-4">
                                <span class="w-10 font-black text-indigo-200 text-sm">No</span>
                                <span class="text-[11px] opacity-70 whitespace-nowrap uppercase tracking-wider">= INovation</span>
                            </div>
                            <div class="flex items-center gap-4">
                                <span class="w-10 font-black text-indigo-200 text-sm">Ty</span>
                                <span class="text-[11px] opacity-70 whitespace-nowrap uppercase tracking-wider">= CreativiTy</span>
                            </div>
                        </div>
                        <div class="mt-8 pt-6 border-t border-white/10">
                            <p class="text-sm font-bold text-indigo-300">ProTechNoTy Class 24</p>
                            <p class="text-[9px] opacity-40 uppercase tracking-widest">Official Class Identity</p>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Section: Penjelasan Logo -->
        <section class="mb-16 px-8 md:px-12 py-12 rounded-[2.5rem] bg-slate-900 text-white relative overflow-hidden">
            <div class="absolute top-0 right-0 w-64 h-64 bg-indigo-500/10 rounded-full blur-[80px]"></div>
            <div class="absolute bottom-0 left-0 w-64 h-64 bg-cyan-500/5 rounded-full blur-[80px]"></div>

            <div class="relative z-10 grid grid-cols-1 lg:grid-cols-2 gap-16 items-center">
                <div class="flex flex-col items-center">
                    <div class="p-8 bg-white/5 backdrop-blur-md border border-white/10 rounded-[2rem] mb-6 group hover:bg-white/10 transition-colors shadow-2xl">
                        <img src="assets/image/logo.webp" alt="Logo PTIK C 24" class="w-48 h-48 md:w-64 md:h-64 object-contain drop-shadow-[0_0_20px_rgba(79,70,229,0.3)] group-hover:scale-105 transition-transform duration-500">
                    </div>
                    <div class="text-center">
                        <div class="inline-block px-3 py-1 bg-indigo-500/20 text-indigo-300 rounded-full text-[10px] font-bold uppercase tracking-widest mb-3">
                            Identitas Visual
                        </div>
                        <h2 class="text-2xl font-bold">Filosofi <span class="text-indigo-400">Logo Kelas</span></h2>
                    </div>
                </div>

                <div class="space-y-6">
                    <div class="flex gap-5">
                        <div class="w-1 h-12 bg-indigo-500 rounded-full shrink-0"></div>
                        <div>
                            <h4 class="text-xs font-bold text-indigo-300 uppercase tracking-wide">1. Huruf 'C' Dominan</h4>
                            <p class="text-[11px] text-slate-400 leading-relaxed mt-1.5">Representasi <strong>Kelas C</strong> sebagai wadah kesatuan. Terbuka ke arah kanan menunjukkan keterbukaan terhadap inovasi sekaligus melindungi anggota di dalamnya.</p>
                        </div>
                    </div>

                    <div class="flex gap-5">
                        <div class="w-1 h-12 bg-cyan-400 rounded-full shrink-0"></div>
                        <div>
                            <h4 class="text-xs font-bold text-cyan-300 uppercase tracking-wide">2. Garis Kecepatan (Speed Lines)</h4>
                            <p class="text-[11px] text-slate-400 leading-relaxed mt-1.5">Merepresentasikan <strong>akselerasi dan efisiensi</strong>. Mencerminkan adaptivitas mahasiswa PTIK 24 yang bergerak cepat mengikuti perkembangan teknologi.</p>
                        </div>
                    </div>

                    <div class="flex gap-5">
                        <div class="w-1 h-12 bg-white/40 rounded-full shrink-0"></div>
                        <div>
                            <h4 class="text-xs font-bold text-slate-100 uppercase tracking-wide">3. Tipografi PTIK Solid</h4>
                            <p class="text-[11px] text-slate-400 leading-relaxed mt-1.5">Font tebal melambangkan <strong>fondasi pengetahuan</strong> yang kokoh dan profesionalisme dalam menuntut ilmu di Fakultas Teknik.</p>
                        </div>
                    </div>

                    <div class="flex gap-5">
                        <div class="w-1 h-12 bg-indigo-400 rounded-full shrink-0"></div>
                        <div>
                            <h4 class="text-xs font-bold text-indigo-200 uppercase tracking-wide">4. Angka "24" dalam Lingkaran</h4>
                            <p class="text-[11px] text-slate-400 leading-relaxed mt-1.5">Menandakan identitas angkatan yang menyatu dengan teks utama, menciptakan rasa <strong>kepemilikan (Sense of Belonging)</strong> yang tinggi.</p>
                        </div>
                    </div>

                    <div class="p-4 bg-white/5 border border-white/10 rounded-2xl mt-4">
                        <h4 class="text-[11px] font-bold text-slate-100 mb-3 uppercase tracking-wide">5. Makna Warna</h4>
                        <div class="grid grid-cols-2 gap-6">
                            <div>
                                <p class="text-xs font-bold text-indigo-300">Navy Blue</p>
                                <p class="text-[10px] text-slate-400 mt-1">Simbol kedewasaan, kecerdasan, dan teknologi tinggi.</p>
                            </div>
                            <div>
                                <p class="text-xs font-bold text-slate-200">Putih</p>
                                <p class="text-[10px] text-slate-400 mt-1">Ketulusan dalam belajar dan fokus pada tujuan bersama.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Section: Download Assets Link -->
        <section class="max-w-2xl mx-auto">
            <a href="download.php" class="block group">
                <div class="glass-card p-8 rounded-[2.5rem] border border-slate-200 shadow-sm hover:shadow-xl hover:border-indigo-200 transition-all flex items-center justify-between gap-8">
                    <div class="flex items-center gap-6">
                        <div class="w-16 h-16 bg-indigo-600 rounded-[1.5rem] flex items-center justify-center text-white shadow-2xl shadow-indigo-200 group-hover:scale-110 group-hover:rotate-3 transition-transform duration-500">
                            <i class="fa fa-cloud-arrow-down text-2xl"></i>
                        </div>
                        <div>
                            <h3 class="text-lg font-bold text-slate-900">Download Official Assets</h3>
                            <p class="text-xs text-slate-500 mt-1">Dapatkan Logo, Virtual Background, dan Resources kelas lainnya dalam kualitas HD.</p>
                        </div>
                    </div>
                    <div class="h-10 w-10 rounded-full bg-slate-50 flex items-center justify-center text-slate-400 group-hover:bg-indigo-600 group-hover:text-white transition-all duration-300">
                        <i class="fa fa-chevron-right text-sm"></i>
                    </div>
                </div>
            </a>
        </section>
    </main>

    <?php include 'includes/footer_main.php'; ?>
    <?php include 'includes/script_main.php'; ?>
</body>

</html>