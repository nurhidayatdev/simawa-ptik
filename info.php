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

    <main class="container mx-auto px-4 py-8 max-w-4xl">
        <!-- Hero Section -->
        <div class="text-center mb-8">
            <h1 class="text-2xl font-black text-slate-900 mb-2 tracking-tight">
                Identitas <span class="text-gradient uppercase">Kelas Kita</span>
            </h1>
            <p class="text-slate-500 max-lg mx-auto text-[10px] leading-relaxed">
                Mengenal lebih dalam simbolisme, filosofi, dan sejarah di balik identitas digital PTIK C Angkatan 2024.
            </p>
        </div>

        <!-- Section: Penjelasan Nama -->
        <section class="mb-12">
            <div class="glass-card p-4 md:p-6 rounded-[2rem] shadow-sm border border-slate-200 flex flex-col md:flex-row gap-6 items-stretch">
                <!-- Explanation List -->
                <div class="w-full md:w-7/12 flex flex-col justify-center py-2 order-1 md:order-1">
                    <div class="inline-block px-2 py-0.5 bg-indigo-100 text-indigo-700 rounded-full text-[8px] font-bold uppercase tracking-widest mb-1.5 w-max">
                        Etimologi
                    </div>
                    <h2 class="text-lg font-bold text-slate-800 mb-1">Arti <span class="text-indigo-600">PTIK C 24</span></h2>
                    <p class="text-slate-400 text-[10px] mb-4">Simbol perjuangan dan identitas kolektif kita.</p>

                    <div class="space-y-2">
                        <div class="flex gap-3 p-2.5 rounded-xl bg-white border border-slate-100 shadow-sm">
                            <div class="h-6 w-6 shrink-0 bg-indigo-50 text-indigo-600 rounded flex items-center justify-center font-black text-[10px]">P</div>
                            <div>
                                <p class="text-[10px] font-bold text-slate-700 leading-none">Pendidikan Teknik Informatika & Komputer</p>
                                <p class="text-[9px] text-slate-400 mt-1">Program Studi kita di Universitas Negeri Makassar.</p>
                            </div>
                        </div>
                        <div class="flex gap-3 p-2.5 rounded-xl bg-white border border-slate-100 shadow-sm">
                            <div class="h-6 w-6 shrink-0 bg-cyan-50 text-cyan-600 rounded flex items-center justify-center font-black text-[10px]">C</div>
                            <div>
                                <p class="text-[10px] font-bold text-slate-700 leading-none">Kelas C</p>
                                <p class="text-[9px] text-slate-400 mt-1">Identitas rombongan belajar angkatan kita.</p>
                            </div>
                        </div>
                        <div class="flex gap-3 p-2.5 rounded-xl bg-white border border-slate-100 shadow-sm">
                            <div class="h-6 w-6 shrink-0 bg-slate-100 text-slate-600 rounded flex items-center justify-center font-black text-[10px]">24</div>
                            <div>
                                <p class="text-[10px] font-bold text-slate-700 leading-none">Angkatan 2024</p>
                                <p class="text-[9px] text-slate-400 mt-1">Tahun dimulainya perjalanan akademik kita.</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Branding Card -->
                <div class="w-full md:w-5/12 flex order-2 md:order-2">
                    <div class="p-5 rounded-[1.5rem] bg-indigo-900 text-white shadow-lg relative overflow-hidden w-full flex flex-col justify-center">
                        <div class="absolute top-0 right-0 w-20 h-20 bg-white/5 rounded-full -mr-10 -mt-10"></div>
                        <div class="absolute bottom-0 left-0 w-12 h-12 bg-white/5 rounded-full -ml-6 -mb-6"></div>

                        <h3 class="text-[9px] font-bold mb-4 flex items-center gap-2 uppercase tracking-widest text-indigo-300">
                            <i class="fa fa-shuttle-space"></i> Filosofi Nama
                        </h3>
                        <div class="space-y-2.5 border-l-2 border-indigo-500/30 pl-4">
                            <div class="flex items-center gap-3">
                                <span class="w-8 font-black text-indigo-200 text-xs">Pro</span>
                                <span class="text-[10px] opacity-70 whitespace-nowrap">= Programmer</span>
                            </div>
                            <div class="flex items-center gap-3">
                                <span class="w-8 font-black text-indigo-200 text-xs">Tech</span>
                                <span class="text-[10px] opacity-70 whitespace-nowrap">= Technology</span>
                            </div>
                            <div class="flex items-center gap-3">
                                <span class="w-8 font-black text-indigo-200 text-xs">No</span>
                                <span class="text-[10px] opacity-70 whitespace-nowrap">= INovation</span>
                            </div>
                            <div class="flex items-center gap-3">
                                <span class="w-8 font-black text-indigo-200 text-xs">Ty</span>
                                <span class="text-[10px] opacity-70 whitespace-nowrap">= CreativiTy</span>
                            </div>
                        </div>
                        <div class="mt-5 pt-3 border-t border-white/10">
                            <p class="text-[10px] font-bold text-indigo-300">ProTechNoTy Class 24</p>
                            <p class="text-[8px] opacity-40 uppercase">Official Class Identity</p>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Section: Penjelasan Logo -->
        <section class="mb-12 px-5 py-6 rounded-[2rem] bg-slate-900 text-white relative overflow-hidden">
            <div class="absolute top-0 right-0 w-40 h-40 bg-indigo-500/20 rounded-full blur-[60px]"></div>
            <div class="absolute bottom-0 left-0 w-40 h-40 bg-cyan-500/10 rounded-full blur-[60px]"></div>

            <div class="relative z-10 grid grid-cols-1 lg:grid-cols-2 gap-10 items-center">
                <div class="flex flex-col items-center">
                    <div class="p-4 bg-white/5 backdrop-blur-md border border-white/10 rounded-2xl mb-4 group hover:bg-white/10 transition-colors">
                        <img src="assets/image/logo.webp" alt="Logo PTIK C 24" class="w-40 h-40 md:w-56 md:h-56 object-contain drop-shadow-[0_0_15px_rgba(79,70,229,0.3)]">
                    </div>
                    <div class="text-center">
                        <div class="inline-block px-2.5 py-0.5 bg-indigo-500/20 text-indigo-300 rounded-full text-[8px] font-bold uppercase tracking-widest mb-2">
                            Identitas Visual
                        </div>
                        <h2 class="text-lg font-bold">Filosofi <span class="text-indigo-400">Logo Kelas</span></h2>
                    </div>
                </div>

                <div class="space-y-4">
                    <div class="flex gap-4">
                        <div class="w-1 h-10 bg-indigo-500 rounded-full shrink-0"></div>
                        <div>
                            <h4 class="text-[11px] font-bold text-indigo-300 uppercase tracking-wide">1. Huruf 'C' Dominan</h4>
                            <p class="text-[10px] text-slate-400 leading-relaxed mt-1">Representasi <strong>Kelas C</strong> sebagai wadah kesatuan. Terbuka ke arah kanan menunjukkan keterbukaan terhadap inovasi sekaligus melindungi anggota di dalamnya.</p>
                        </div>
                    </div>

                    <div class="flex gap-4">
                        <div class="w-1 h-10 bg-cyan-400 rounded-full shrink-0"></div>
                        <div>
                            <h4 class="text-[11px] font-bold text-cyan-300 uppercase tracking-wide">2. Garis Kecepatan (Speed Lines)</h4>
                            <p class="text-[10px] text-slate-400 leading-relaxed mt-1">Merepresentasikan <strong>akselerasi dan efisiensi</strong>. Mencerminkan adaptivitas mahasiswa PTIK 24 yang bergerak cepat mengikuti perkembangan teknologi.</p>
                        </div>
                    </div>

                    <div class="flex gap-4">
                        <div class="w-1 h-10 bg-white/40 rounded-full shrink-0"></div>
                        <div>
                            <h4 class="text-[11px] font-bold text-slate-100 uppercase tracking-wide">3. Tipografi PTIK Solid</h4>
                            <p class="text-[10px] text-slate-400 leading-relaxed mt-1">Font tebal melambangkan <strong>fondasi pengetahuan</strong> yang kokoh dan profesionalisme dalam menuntut ilmu di Fakultas Teknik.</p>
                        </div>
                    </div>

                    <div class="flex gap-4">
                        <div class="w-1 h-10 bg-indigo-400 rounded-full shrink-0"></div>
                        <div>
                            <h4 class="text-[11px] font-bold text-indigo-200 uppercase tracking-wide">4. Angka "24" dalam Lingkaran</h4>
                            <p class="text-[10px] text-slate-400 leading-relaxed mt-1">Menandakan identitas angkatan yang menyatu dengan teks utama, menciptakan rasa <strong>kepemilikan (Sense of Belonging)</strong> yang tinggi.</p>
                        </div>
                    </div>

                    <div class="flex gap-4">
                        <div class="w-1 h-10 bg-emerald-400 rounded-full shrink-0"></div>
                        <div>
                            <h4 class="text-[11px] font-bold text-emerald-300 uppercase tracking-wide">5. Konektivitas</h4>
                            <p class="text-[10px] text-slate-400 leading-relaxed mt-1">Mencerminkan <strong>konektivitas</strong> dan harmonisasi antara teori pendidikan dan praktik teknik komputer.</p>
                        </div>
                    </div>

                    <div class="p-3 bg-white/5 border border-white/10 rounded-xl">
                        <h4 class="text-[10px] font-bold text-slate-100 mb-2 uppercase tracking-wide">6. Makna Warna</h4>
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <p class="text-[10px] font-bold text-indigo-300">Navy Blue</p>
                                <p class="text-[9px] text-slate-400">Kecerdasan dan teknologi.</p>
                            </div>
                            <div>
                                <p class="text-[10px] font-bold text-slate-200">Putih</p>
                                <p class="text-[9px] text-slate-400">Kejujuran dan fokus.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Section: Download Assets -->
        <section class="max-w-2xl mx-auto">
            <div class="text-center mb-5">
                <h3 class="text-base font-bold text-slate-900 mb-1">Download Resources</h3>
                <p class="text-[9px] text-slate-500 text-center">Aset resmi untuk identitas digitalmu.</p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <!-- Logo Card -->
                <div class="bg-white p-5 rounded-2xl shadow-sm border border-slate-100 flex flex-col items-center text-center group hover:shadow-md transition-all">
                    <div class="w-10 h-10 bg-indigo-50 rounded-xl flex items-center justify-center text-indigo-600 mb-3 group-hover:scale-105 transition-transform">
                        <i class="fa fa-image text-lg"></i>
                    </div>
                    <h4 class="text-xs font-bold text-slate-800 mb-1">Logo Kelas (HD)</h4>
                    <p class="text-[9px] text-slate-500 mb-4">Logo format WebP/PNG transparan.</p>
                    <a href="assets/image/logo-ptikc.png" download="Logo_PTIK_C_24.png" class="w-full py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg text-[10px] font-bold transition-colors flex items-center justify-center gap-2">
                        <i class="fa fa-download"></i> Download
                    </a>
                </div>

                <!-- Zoom Background Card -->
                <div class="bg-white p-5 rounded-2xl shadow-sm border border-slate-100 flex flex-col items-center text-center group hover:shadow-md transition-all">
                    <div class="w-10 h-10 bg-cyan-50 rounded-xl flex items-center justify-center text-cyan-600 mb-3 group-hover:scale-105 transition-transform">
                        <i class="fa fa-desktop text-lg"></i>
                    </div>
                    <h4 class="text-xs font-bold text-slate-800 mb-1">Zoom Background</h4>
                    <p class="text-[9px] text-slate-500 mb-4">Latar belakang virtual resmi.</p>
                    <a href="assets/image/background-ptikc.jpg" download="Zoom_BG_PTIK_C_24.jpg" class="w-full py-2 bg-slate-800 hover:bg-slate-900 text-white rounded-lg text-[10px] font-bold transition-colors flex items-center justify-center gap-2">
                        <i class="fa fa-download"></i> Download
                    </a>
                </div>
            </div>
        </section>
    </main>

    <?php include 'includes/footer_main.php'; ?>
    <?php include 'includes/script_main.php'; ?>
</body>

</html>