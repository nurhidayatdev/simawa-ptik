<?php
include 'koneksi.php';
include_once 'functions/foto_helpers.php';

// Ambil periode aktif
$q_periode = mysqli_query($koneksi, "SELECT * FROM periode WHERE status = 'aktif' LIMIT 1");
$periode = mysqli_fetch_assoc($q_periode);
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <title>Panduan Penggunaan - SIMAWA PTIK 2024</title>
    <?php include 'includes/head.php'; ?>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Poppins', sans-serif;
        }

        .plus-jakarta {
            font-family: 'Plus Jakarta Sans', sans-serif;
        }

        .glass-card {
            background: rgba(255, 255, 255, 0.7);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            border: 1px solid rgba(255, 255, 255, 0.4);
            transition: background 0.3s, border 0.3s;
        }

        .dark .glass-card {
            background: rgba(15, 23, 42, 0.6);
            border: 1px solid rgba(255, 255, 255, 0.1);
        }

        .bg-mesh {
            background-color: #ffffff;
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
                radial-gradient(at 0% 0%, rgba(79, 70, 229, 0.15) 0px, transparent 50%),
                radial-gradient(at 100% 0%, rgba(6, 182, 212, 0.15) 0px, transparent 50%),
                radial-gradient(at 100% 100%, rgba(79, 70, 229, 0.15) 0px, transparent 50%),
                radial-gradient(at 0% 100%, rgba(79, 70, 229, 0.15) 0px, transparent 50%);
        }

        .role-btn {
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .role-btn.active {
            background: #4f46e5;
            color: white !important;
            transform: scale(1.05);
            box-shadow: 0 10px 25px -5px rgba(79, 70, 229, 0.4);
            border-color: #4f46e5 !important;
        }

        .dark .role-btn:not(.active) {
            color: #94a3b8;
        }

        .dark .role-btn:not(.active):hover {
            background: rgba(255, 255, 255, 0.05);
            color: white;
        }



        .guide-section {
            display: none;
            animation: fadeIn 0.5s ease-out forwards;
        }

        .guide-section.active {
            display: block;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(15px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .step-card {
            padding: 1.5rem;
            border-radius: 1.5rem;
            background: white;
            border: 1px solid #f1f5f9;
            transition: all 0.3s ease;
            position: relative;
        }

        @media (max-width: 640px) {
            .step-card {
                padding: 1.25rem;
            }
        }

        .dark .step-card {
            background: #0f172a;
            border-color: #1e293b;
        }

        .step-card:hover {
            box-shadow: 0 20px 40px -15px rgba(0,0,0,0.05);
            transform: translateY(-3px);
            border-color: #e2e8f0;
        }

        .dark .step-card:hover { border-color: #334155; }

        .step-card:last-child {
            margin-bottom: 0;
        }

        .tip-box {
            background: linear-gradient(135deg, #eff6ff 0%, #dbeafe 100%);
            border-left: 4px solid #3b82f6;
            padding: 1.25rem;
            border-radius: 1rem;
            margin-top: 1.5rem;
            transition: all 0.3s;
        }
        .dark .tip-box {
            background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%);
            border-color: #4f46e5;
        }

        /* Hide scrollbar for Chrome, Safari and Opera */
        .no-scrollbar::-webkit-scrollbar {
            display: none;
        }

        /* Hide scrollbar for IE, Edge and Firefox */
        .no-scrollbar {
            -ms-overflow-style: none;  /* IE and Edge */
            scrollbar-width: none;  /* Firefox */
        }

        @media (max-width: 640px) {
            .role-btn {
                white-space: nowrap;
                flex-shrink: 0;
            }
        }
    </style>
</head>

<body class="bg-mesh min-h-screen transition-colors duration-300">

    <?php include 'includes/header_main.php'; ?>

    <!-- Spacer -->
    <div class="h-10"></div>

    <main class="flex-grow pt-12 pb-0">
        <div class="container mx-auto px-6 pb-8">
            <div class="flex flex-col md:flex-row gap-8 items-start">
                <!-- Sidebar Navigation -->
                <aside class="w-full md:w-64 md:sticky md:top-24 flex-shrink-0 z-20">
                    <div class="mb-6 md:mb-6">
                        <h1 class="text-2xl md:text-3xl font-bold mb-2 text-gray-800 dark:text-white transition-colors text-center md:text-left">Panduan <span class="text-indigo-600">Penggunaan</span></h1>
                        <p class="text-gray-500 dark:text-slate-400 transition-colors text-xs text-center md:text-left max-w-2xl mx-auto md:mx-0 leading-relaxed font-medium">Pelajari pengelolaan tugas, absensi, hingga pengaturan profil secara maksimal.</p>
                    </div>
                    <div class="flex flex-nowrap md:flex-col justify-start items-center md:items-stretch gap-2 md:gap-3 overflow-x-auto md:overflow-visible no-scrollbar -mx-6 px-6 md:mx-0 md:px-0 scroll-smooth">
                <button onclick="showGuide('mhs')" class="role-btn active glass-card px-3.5 md:px-4 py-2 md:py-2.5 rounded-xl flex items-center gap-2 text-[10px] md:text-[11px] font-bold text-slate-600 dark:text-slate-400 hover:bg-white dark:hover:bg-slate-800 transition-all shadow-sm">
                    <i class="fa fa-user-graduate text-sm"></i> Mahasiswa
                </button>
                <button onclick="showGuide('keti')" class="role-btn glass-card px-3.5 md:px-4 py-2 md:py-2.5 rounded-xl flex items-center gap-2 text-[10px] md:text-[11px] font-bold text-slate-600 dark:text-slate-400 hover:bg-white dark:hover:bg-slate-800 transition-all shadow-sm">
                    <i class="fa fa-crown text-sm"></i> Ketua Tingkat
                </button>
                <button onclick="showGuide('sekretaris')" class="role-btn glass-card px-3.5 md:px-4 py-2 md:py-2.5 rounded-xl flex items-center gap-2 text-[10px] md:text-[11px] font-bold text-slate-600 dark:text-slate-400 hover:bg-white dark:hover:bg-slate-800 transition-all shadow-sm">
                    <i class="fa fa-file-signature text-sm"></i> Sekretaris
                </button>
                <button onclick="showGuide('bendahara')" class="role-btn glass-card px-3.5 md:px-4 py-2 md:py-2.5 rounded-xl flex items-center gap-2 text-[10px] md:text-[11px] font-bold text-slate-600 dark:text-slate-400 hover:bg-white dark:hover:bg-slate-800 transition-all shadow-sm">
                    <i class="fa fa-wallet text-sm"></i> Bendahara
                </button>
                <button onclick="showGuide('manager')" class="role-btn glass-card px-3.5 md:px-4 py-2 md:py-2.5 rounded-xl flex items-center gap-2 text-[10px] md:text-[11px] font-bold text-slate-600 dark:text-slate-400 hover:bg-white dark:hover:bg-slate-800 transition-all shadow-sm">
                    <i class="fa fa-tasks text-sm"></i> Manager Tugas
                </button>
                <button onclick="showGuide('admin')" class="role-btn glass-card px-3.5 md:px-4 py-2 md:py-2.5 rounded-xl flex items-center gap-2 text-[10px] md:text-[11px] font-bold text-slate-600 dark:text-slate-400 hover:bg-white dark:hover:bg-slate-800 transition-all shadow-sm">
                    <i class="fa fa-user-shield text-sm"></i> Administrator
                </button>
                    </div>
                </aside>

                <!-- Right Side Content -->
                <div class="flex-1 w-full">

            <!-- SEARCH BAR -->
            <div class="max-w-full mx-auto mb-8 relative group">
                <div class="absolute inset-y-0 left-4 flex items-center pointer-events-none z-10">
                    <i class="fa fa-search text-xs text-indigo-600 group-focus-within:text-indigo-500 transition-colors"></i>
                </div>
                <input type="text" id="guide-search" onkeyup="searchGuide(this.value)" placeholder="Cari judul panduan..." 
                    class="w-full pl-10 pr-6 py-2.5 bg-white/70 dark:bg-slate-900/50 backdrop-blur-xl border border-slate-200 dark:border-slate-800 rounded-2xl shadow-xl shadow-slate-200/20 dark:shadow-black/20 focus:ring-4 focus:ring-indigo-500/10 focus:border-indigo-500/50 dark:text-white transition-all outline-none text-xs font-semibold plus-jakarta">
                <div id="search-status" class="hidden absolute top-full left-0 right-0 mt-4 text-center">
                    <p class="text-xs text-slate-400 italic">Tidak menemukan panduan yang cocok...</p>
                </div>
            </div>
        
        <!-- PANDUAN MAHASISWA -->
        <div id="guide-mhs" class="guide-section active">
            <div class="glass-card p-6 md:p-8 rounded-[3rem] border border-slate-100 dark:border-slate-800 shadow-2xl shadow-slate-200/50 dark:shadow-black/20">
                <div class="flex items-center gap-4 mb-6 md:mb-8">
                    <div class="h-14 w-14 bg-indigo-600 text-white rounded-2xl flex items-center justify-center text-xl shadow-xl shadow-indigo-200 dark:shadow-indigo-900/40 transition-colors">
                        <i class="fa fa-user-graduate"></i>
                    </div>
                    <div>
                        <h2 class="text-xl md:text-2xl font-black text-slate-800 dark:text-white plus-jakarta transition-colors">Mahasiswa</h2>
                        <p class="text-[11px] text-indigo-500 dark:text-indigo-400 font-extrabold uppercase tracking-[0.2em] mt-1 transition-colors">Starting Your Digital Journey</p>
                    </div>
                </div>

                <div class="space-y-8 relative">

                    <!-- Setup Profil -->
                    <div class="step-card group">
                        <div class="flex gap-6 relative z-10">

                            <div class="flex-1">
                                <h3 class="text-lg font-black text-slate-800 dark:text-white mb-3 plus-jakarta transition-colors">Persiapan & Setup Profil Awal</h3>
                                <p class="text-xs text-slate-500 dark:text-slate-400 leading-relaxed mb-4 transition-colors">Langkah pertama yang wajib dilakukan oleh setiap mahasiswa baru sistem:</p>
                                <ul class="space-y-3">
                                    <li class="flex items-start gap-2 text-xs text-slate-600 dark:text-slate-300 transition-colors">
                                        <i class="fa fa-right-to-bracket text-indigo-500 mt-0.5"></i>
                                        <span><strong>Login Pertama:</strong> <strong>Masuk</strong> menggunakan <strong>NIM</strong> sebagai <strong>Username</strong> dan <strong>Password</strong> default (Hubungi Administrator Utama jika tidak tahu).</span>
                                    </li>
                                    <li class="flex items-start gap-2 text-xs text-slate-600 dark:text-slate-300 transition-colors">
                                        <i class="fa fa-user-shield text-indigo-500 mt-0.5"></i>
                                        <span><strong>Ubah Password:</strong> Masuk ke menu <strong>Profil</strong> > <strong>Edit Profil</strong>. Di halaman inilah Anda dapat mengupdate <strong>password baru</strong> demi keamanan akun.</span>
                                    </li>
                                    <li class="flex items-start gap-2 text-xs text-slate-600 dark:text-slate-300 transition-colors">
                                        <i class="fa fa-key text-indigo-500 mt-0.5"></i>
                                        <div class="space-y-1">
                                            <span><strong>Simpan Akun Manual:</strong> Jika pop-up penyimpanan otomatis tidak muncul, ikuti langkah manual ini:</span>
                                            <ol class="list-decimal list-inside pl-2 space-y-1 opacity-80 decoration-indigo-200">
                                                <li>Buka <strong>Settings</strong> di Chrome/Browser Anda.</li>
                                                <li>Pilih menu <strong>Google Password Manager</strong>.</li>
                                                <li>Klik tombol <strong>+ Add</strong> atau <strong>Tambah</strong>.</li>
                                                <li>Masukkan URL: <code>simawa-ptik.local</code></li>
                                                <li>Masukkan Username: <code>NIM Anda</code></li>
                                                <li>Masukkan Password yang baru saja Anda buat.</li>
                                            </ol>
                                        </div>
                                    </li>
                                    <li class="flex items-start gap-2 text-xs text-slate-600 dark:text-slate-300 transition-colors">
                                        <i class="fa fa-id-badge text-indigo-500 mt-0.5"></i>
                                        <span><strong>Avatar Otomatis:</strong> Sistem secara otomatis menghasilkan foto profil avatar inisial modern berbasis nama lengkap Anda tanpa perlu unggah file manual.</span>
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div>

                    <!-- Data Master -->
                    <div class="step-card group">
                        <div class="flex gap-6 relative z-10">

                            <div class="flex-1">
                                <h3 class="text-lg font-black text-slate-800 dark:text-white mb-3 plus-jakarta transition-colors">Data Master</h3>
                                <p class="text-xs text-slate-500 dark:text-slate-400 leading-relaxed mb-4 transition-colors">Menu Master merupakan pusat data utama yang berisi informasi identitas mahasiswa, pengajar, dan detail perkuliahan.</p>
                                <ul class="space-y-3">
                                    <li class="flex items-start gap-2 text-xs text-slate-600 dark:text-slate-300 transition-colors">
                                        <i class="fa fa-sitemap text-indigo-500 mt-0.5"></i>
                                        <span><strong>Buka Menu Utama:</strong> Klik menu <strong>Master</strong> (dengan ikon gedung/bank) pada <strong>sidebar</strong> untuk memunculkan daftar sub-menu yang tersedia.</span>
                                    </li>
                                    <li class="flex items-start gap-2 text-xs text-slate-600 dark:text-slate-300 transition-colors">
                                        <i class="fa fa-layer-group text-indigo-500 mt-0.5"></i>
                                        <div class="space-y-1">
                                            <span><strong>Pilih Kategori Data:</strong> Anda dapat memilih salah satu dari daftar berikut sesuai kebutuhan:</span>
                                            <ol class="list-decimal list-inside pl-2 space-y-1 opacity-80 decoration-indigo-200">
                                                <li><strong>Mahasiswa:</strong> Untuk melihat daftar nama dan profil teman sekelas.</li>
                                                <li><strong>Dosen:</strong> Untuk melihat daftar dosen pengampu mata kuliah.</li>
                                                <li><strong>Mata Kuliah:</strong> Untuk melihat list mata kuliah pada kurikulum.</li>
                                                <li><strong>Jadwal Kuliah:</strong> Untuk melihat jadwal perkuliahan pada kelas.</li>
                                                <li><strong>Berita & Info:</strong> Untuk update informasi terbaru seputar kelas.</li>
                                                <li><strong>Kalender Akademik:</strong> Untuk memantau jadwal penting semester ini.</li>
                                                
                                            </ol>
                                        </div>
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div>

                    <!-- KRS & KSM -->
                    <div class="step-card group">
                        <div class="flex gap-6 relative z-10">

                            <div class="flex-1">
                                <h3 class="text-lg font-black text-slate-800 dark:text-white mb-3 plus-jakarta transition-colors">Manajemen Dokumen (KRS & KSM)</h3>
                                <p class="text-xs text-slate-500 dark:text-slate-400 leading-relaxed mb-4 transition-colors">Arsip mandiri untuk dokumen penting universitas:</p>
                                <ul class="space-y-3">
                                    <li class="flex items-start gap-2 text-xs text-slate-600 dark:text-slate-400 transition-colors">
                                        <i class="fa fa-file-signature text-indigo-500 mt-0.5"></i>
                                        <span><strong>Pengisian KRS:</strong> Akses <strong>Sidebar</strong> > <strong>Akun</strong> > <strong>KRS Mahasiswa</strong>. Pilih <strong>mata kuliah</strong> yang diprogramkan pada <strong>semester</strong> berjalan (tersedia tombol <strong>Pilih Semua</strong> untuk mempercepat). Klik <strong>Simpan KRS</strong> dan tunggu persetujuan dari Administrator Utama.</span>
                                    </li>
                                    <li class="flex items-start gap-2 text-xs text-slate-600 dark:text-slate-400 transition-colors">
                                        <i class="fa fa-user-check text-indigo-500 mt-0.5"></i>
                                        <span><strong>Validasi & KSM:</strong> Cek status di menu <strong>Akun</strong> > <strong>KSM Mahasiswa</strong>. Jika belum disetujui, klik <strong>Minta Validasi</strong>.</span>
                                    </li>
                                    <li class="flex items-start gap-2 text-xs text-slate-600 dark:text-slate-400 transition-colors">
                                        <i class="fa fa-file-pdf text-indigo-500 mt-0.5"></i>
                                        <span><strong>Unduh Dokumen:</strong> Setelah status berubah menjadi <strong>Approved</strong>, Anda dapat melihat <strong>Kartu Studi Mahasiswa (KSM)</strong> dan mengunduhnya dalam format <strong>PDF</strong> melalui tombol <strong>Cetak</strong>.</span>
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div>

                    <!-- Melacak Tugas -->
                    <div class="step-card group">
                        <div class="flex gap-6 relative z-10">

                            <div class="flex-1">
                                <h3 class="text-lg font-black text-slate-800 dark:text-white mb-3 plus-jakarta transition-colors">Melacak Daftar Tugas</h3>
                                <p class="text-xs text-slate-500 dark:text-slate-400 leading-relaxed mb-4 transition-colors">Pusat informasi akademik harian Anda:</p>
                                <ul class="space-y-3">
                                    <li class="flex items-start gap-2 text-xs text-slate-600 dark:text-slate-300 transition-colors">
                                        <i class="fa fa-chart-line text-indigo-500 mt-0.5"></i>
                                        <span>Lihat <strong>Dashboard Utama</strong> untuk melihat jumlah <strong>tugas aktif</strong> atau <strong>deadline tugas</strong> terdekat.</span>
                                    </li>
                                    
                                    <li class="flex items-start gap-2 text-xs text-slate-600 dark:text-slate-300 transition-colors">
                                        <i class="fa fa-circle-question text-indigo-500 mt-0.5"></i>
                                        <span>Ada dua metode untuk mengelola tugas Anda:</span>
                                    </li>
                                    <li class="flex items-start gap-2 text-xs text-slate-600 dark:text-slate-300 transition-colors">
                                        <i class="fa fa-list-ul text-indigo-500 mt-0.5"></i>
                                        <div class="space-y-1">
                                            <span><strong>Metode A:</strong> <strong>Tugas Kuliah</strong> (Pantau Status):</span>
                                            <ol class="list-decimal list-inside pl-2 space-y-1 opacity-80 decoration-indigo-200">
                                                <li>Akses melalui <strong>Sidebar</strong> > <strong>Tugas</strong> > <strong>Tugas Kuliah</strong>.</li>
                                                <li>Daftar tugas aktif akan langsung muncul. Gunakan fitur <strong>Pencarian</strong> untuk akses cepat.</li>
                                                <li>Klik ikon <strong>Info</strong> untuk melihat instruksi lengkap.</li>
                                                <li>Ingin melihat riwayat? Ubah filter pada <strong>dropdown</strong> dari <strong>Belum Selesai</strong> menjadi <strong>Selesai</strong>.</li>
                                            </ol>
                                        </div>
                                    </li>
                                    <li class="flex items-start gap-2 text-xs text-slate-600 dark:text-slate-300 transition-colors">
                                        <i class="fa fa-list-check text-indigo-500 mt-0.5"></i>
                                        <div class="space-y-1">
                                            <span><strong>Metode B:</strong> <strong>Tugas Saya</strong> (Kelola Progres):</span>
                                            <ol class="list-decimal list-inside pl-2 space-y-1 opacity-80 decoration-indigo-200">
                                                <li>Akses melalui <strong>Sidebar</strong> > <strong>Tugas</strong> > <strong>Tugas Saya</strong>.</li>
                                                <li>Di sini tersedia dua kategori: <strong>Tugas Kuliah</strong> dan <strong>Tugas Pribadi</strong>.</li>
                                                <li>Fitur Spesial: Anda bisa menambah <strong>tugas mandiri</strong> dengan klik ikon <strong>(+)</strong>.</li>
                                                <li>Centang tugas yang sudah selesai dikerjakan.</li>
                                                <li>Gunakan fitur <strong>Selesaikan Semua</strong> untuk membersihkan daftar sekaligus.</li>
                                            </ol>
                                        </div>
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div>

                    <!-- Kelompok -->
                    <div class="step-card group">
                        <div class="flex gap-6 relative z-10">

                            <div class="flex-1">
                                <h3 class="text-lg font-black text-slate-800 dark:text-white mb-3 plus-jakarta transition-colors">Cek Kelompok & Rekan Tim</h3>
                                <p class="text-xs text-slate-500 dark:text-slate-400 leading-relaxed mb-4 transition-colors">Kelola koordinasi tim Anda dengan langkah mudah berikut:</p>
                                <ul class="space-y-3">
                                    <li class="flex items-start gap-2 text-xs text-slate-600 dark:text-slate-300 transition-colors">
                                        <i class="fa fa-folder-tree text-indigo-500 mt-0.5"></i>
                                        <span><strong>Akses Menu:</strong> Buka <strong>Sidebar</strong> > <strong>Kelompok</strong>.</span>
                                    </li>
                                    <li class="flex items-start gap-2 text-xs text-slate-600 dark:text-slate-300 transition-colors">
                                        <i class="fa fa-filter text-indigo-500 mt-0.5"></i>
                                        <span><strong>Filter Mata Kuliah:</strong> Pilih <strong>mata kuliah</strong> yang diinginkan pada menu <strong>dropdown</strong> yang tersedia.</span>
                                    </li>
                                    <li class="flex items-start gap-2 text-xs text-slate-600 dark:text-slate-300 transition-colors">
                                        <i class="fa fa-people-arrows text-indigo-500 mt-0.5"></i>
                                        <span><strong>Variasi Kelompok:</strong> Jika ada perbedaan pembagian tim antar dosen, Anda bisa mengganti <strong>Set Kelompok</strong> melalui <strong>dropdown</strong> tambahan.</span>
                                    </li>
                                    <li class="flex items-start gap-2 text-xs text-slate-600 dark:text-slate-300 transition-colors">
                                        <i class="fa fa-user-group text-indigo-500 mt-0.5"></i>
                                        <span><strong>Cek Riwayat:</strong> Tekan tombol <strong>Kelompok Saya</strong> untuk melihat daftar semua kelompok di mana Anda terdaftar di semester ini.</span>
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div>

                    <!-- Piket Proyektor -->
                    <div class="step-card group">
                        <div class="flex gap-6 relative z-10">

                            <div class="flex-1">
                                <h3 class="text-lg font-black text-slate-800 dark:text-white mb-3 plus-jakarta transition-colors">Jadwal Piket Proyektor</h3>
                                <p class="text-xs text-slate-500 dark:text-slate-400 leading-relaxed mb-4 transition-colors">Kontribusi rutin untuk kelancaran perkuliahan:</p>
                                <ul class="space-y-3">
                                    <li class="flex items-start gap-2 text-xs text-slate-600 dark:text-slate-300 transition-colors">
                                        <i class="fa fa-tv text-indigo-500 mt-0.5"></i>
                                        <span>Periksa jadwal piket secara berkala melalui menu <strong>Sidebar</strong> > <strong>Piket Proyektor</strong>.</span>
                                    </li>
                                    <li class="flex items-start gap-2 text-xs text-slate-600 dark:text-slate-300 transition-colors">
                                        <i class="fa fa-calendar-week text-indigo-500 mt-0.5"></i>
                                        <span>Gunakan <strong>dropdown</strong> Minggu Perkuliahan untuk melihat siapa yang bertugas pada minggu tertentu.</span>
                                    </li>
                                    <li class="flex items-start gap-2 text-xs text-slate-600 dark:text-slate-300 transition-colors">
                                        <i class="fa fa-user-clock text-indigo-500 mt-0.5"></i>
                                        <span>Klik tombol <strong>Piket Saya</strong> untuk melihat seluruh daftar jadwal tugas Anda selama satu semester.</span>
                                    </li>
                                    <li class="flex items-start gap-2 text-xs text-slate-600 dark:text-slate-300 transition-colors">
                                        <i class="fa fa-handshake-angle text-indigo-500 mt-0.5"></i>
                                        <span>Jika jadwal Anda tiba, Anda bertanggung jawab mengambil proyektor di Lab dan mengembalikannya setelah kuliah selesai.</span>
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div>

                    <!-- Kalender Akademik -->
                    <div class="step-card group">
                        <div class="flex gap-6 relative z-10">

                            <div class="flex-1">
                                <h3 class="text-lg font-black text-slate-800 dark:text-white mb-3 plus-jakarta transition-colors">Kalender Akademik Terintegrasi</h3>
                                <p class="text-xs text-slate-500 dark:text-slate-400 leading-relaxed mb-4 transition-colors">Jangan lewatkan tanggal-tanggal penting:</p>
                                <ul class="space-y-3">
                                    <li class="flex items-start gap-2 text-xs text-slate-600 dark:text-slate-400 transition-colors">
                                        <i class="fa fa-calendar-days text-indigo-500 mt-0.5"></i>
                                        <span><strong>Akses Menu:</strong> Pilih menu <strong>Kalender</strong> pada <strong>sidebar</strong>.</span>
                                    </li>
                                    <li class="flex items-start gap-2 text-xs text-slate-600 dark:text-slate-400 transition-colors">
                                        <i class="fa fa-network-wired text-indigo-500 mt-0.5"></i>
                                        <span><strong>Satu Untuk Semua:</strong> Kalender ini otomatis menggabungkan data <strong>Jadwal Akademik</strong>, <strong>Tugas Kuliah</strong>, <strong>Tugas Pribadi</strong>, hingga <strong>Jadwal Piket Proyektor</strong>.</span>
                                    </li>
                                    <li class="flex items-start gap-2 text-xs text-slate-600 dark:text-slate-400 transition-colors">
                                        <i class="fa fa-layer-group text-indigo-500 mt-0.5"></i>
                                        <span><strong>Ringkasan Bulanan:</strong> Cek bagian bawah kalender untuk melihat daftar seluruh <strong>event</strong> yang terjadi pada bulan tersebut.</span>
                                    </li>
                                    <li class="flex items-start gap-2 text-xs text-slate-600 dark:text-slate-400 transition-colors">
                                        <i class="fa fa-calendar-check text-indigo-500 mt-0.5"></i>
                                        <span><strong>Interaktif:</strong> Tekan tanggal yang memiliki tanda <strong>event</strong> untuk melihat rincian lengkap, atau ganti bulan untuk memantau agenda mendatang.</span>
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div>

                    <!-- Memantau Absensi -->
                    <div class="step-card group">
                        <div class="flex gap-6 relative z-10">

                            <div class="flex-1">
                                <h3 class="text-lg font-black text-slate-800 dark:text-white mb-3 plus-jakarta transition-colors">Monitoring Absensi & Kehadiran</h3>
                                <p class="text-xs text-slate-500 dark:text-slate-400 leading-relaxed mb-4 transition-colors">Pastikan record kehadiran Anda selalu akurat:</p>
                                <ul class="space-y-3">
                                    <li class="flex items-start gap-2 text-xs text-slate-600 dark:text-slate-300 transition-colors">
                                        <i class="fa fa-user-check text-indigo-500 mt-0.5"></i>
                                        <span>Buka menu <strong>Absensi</strong> > <strong>Rekap Absen</strong> pada <strong>sidebar</strong>.</span>
                                    </li>
                                    <li class="flex items-start gap-2 text-xs text-slate-600 dark:text-slate-300 transition-colors">
                                        <i class="fa fa-table-cells text-indigo-500 mt-0.5"></i>
                                        <span>Lihat tab <strong>Matrix Kehadiran Mahasiswa</strong> untuk meninjau riwayat <strong>minggu 1–16</strong></span>
                                    </li>
                                    <li class="flex items-start gap-2 text-xs text-slate-600 dark:text-slate-300 transition-colors">
                                        <i class="fa fa-magnifying-glass text-indigo-500 mt-0.5"></i>
                                        <span>Gunakan fitur <strong>Pencarian Nama</strong> untuk pengecekan yang lebih instan.</span>
                                    </li>
                                    <li class="flex items-start gap-2 text-xs text-slate-600 dark:text-slate-300 transition-colors">
                                        <i class="fa fa-percent text-indigo-500 mt-0.5"></i>
                                        <span>Pantau <strong>persentase</strong> kehadiran Anda (Pastikan tetap di atas <strong>80%</strong> untuk syarat ujian).</span>
                                    </li>
                                    <li class="flex items-start gap-2 text-xs text-slate-600 dark:text-slate-300 transition-colors">
                                        <i class="fa fa-handshake-angle text-indigo-500 mt-0.5"></i>
                                        <span>Lakukan klaim ke <strong>Sekretaris</strong> jika ada ketidaksesuaian data absen.</span>
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div>

                    <!-- Monitoring Kas -->
                    <div class="step-card group">
                        <div class="flex gap-6 relative z-10">

                            <div class="flex-1">
                                <h3 class="text-lg font-black text-slate-800 dark:text-white mb-3 plus-jakarta transition-colors">Memantau Kewajiban Keuangan (Kas)</h3>
                                <p class="text-xs text-slate-500 dark:text-slate-400 leading-relaxed mb-4 transition-colors">Agar tidak ada tunggakan yang menumpuk di akhir semester:</p>
                                <ul class="space-y-3">
                                    <li class="flex items-start gap-2 text-xs text-slate-600 dark:text-slate-300 transition-colors">
                                        <i class="fa fa-vault text-indigo-500 mt-0.5"></i>
                                        <span>Buka menu <strong>Keuangan > Rekap Kas</strong> dari <strong>Sidebar</strong>.</span>
                                    </li>
                                    <li class="flex items-start gap-2 text-xs text-slate-600 dark:text-slate-300 transition-colors">
                                        <i class="fa fa-file-invoice-dollar text-indigo-500 mt-0.5"></i>
                                        <span>Lihat tab <strong>Matrix Pembayaran Kas</strong> untuk meninjau riwayat <strong>minggu 1–16</strong></span>
                                    </li>
                                    <li class="flex items-start gap-2 text-xs text-slate-600 dark:text-slate-300 transition-colors">
                                        <i class="fa fa-magnifying-glass-dollar text-indigo-500 mt-0.5"></i>
                                        <span>Gunakan fitur <strong>Pencarian Nama</strong> untuk pengecekan yang lebih instan.</span>
                                    </li>
                                    <li class="flex items-start gap-2 text-xs text-slate-600 dark:text-slate-300 transition-colors">
                                        <i class="fa fa-comments-dollar text-indigo-500 mt-0.5"></i>
                                        <span>Segera lakukan pembayaran ke <strong>Bendahara</strong> dan pastikan data Anda diupdate di sistem.</span>
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div>

                    <!-- Bot WhatsApp -->
                    <div class="step-card group">
                        <div class="flex gap-6 relative z-10">

                            <div class="flex-1">
                                <h3 class="text-lg font-black text-slate-800 dark:text-white mb-3 plus-jakarta transition-colors">Integrasi Bot WhatsApp Kelas</h3>
                                <p class="text-xs text-slate-500 dark:text-slate-400 leading-relaxed mb-4 transition-colors">Nikmati kemudahan akses informasi akademik langsung melalui aplikasi WhatsApp Anda.</p>
                                <ul class="space-y-5">
                                    <li class="flex items-start gap-3 text-xs text-slate-600 dark:text-slate-300">
                                        <i class="fa fa-check-circle text-indigo-500 mt-1"></i>
                                        <span><strong>Cara Akses:</strong> Anda bisa mengirim pesan ke grup kelas atau langsung melalui <a href="https://wa.me/6287816244448" target="_blank" class="text-indigo-600 dark:text-indigo-400 font-bold hover:underline">Chat Pribadi Bot Di Sini</a>.</span>
                                    </li>
                                    <li class="flex items-start gap-3 text-xs text-slate-600 dark:text-slate-300">
                                        <i class="fa fa-check-circle text-indigo-500 mt-1"></i>
                                        <span><strong>Perintah Utama:</strong> Cukup ketik <code class="text-indigo-600 dark:text-indigo-400 font-bold">.menu</code> untuk menampilkan seluruh daftar perintah yang tersedia.</span>
                                    </li>
                                    <li class="flex items-start gap-3 text-xs text-slate-600 dark:text-slate-300">
                                        <i class="fa fa-list-ul text-indigo-500 mt-1"></i>
                                        <div class="space-y-4 w-full">
                                            <span><strong>Daftar Perintah:</strong> Silakan tinjau daftar command lengkap di bawah ini untuk bantuan lebih spesifik.</span>
                                            
                                            
                                        </div>
                                    </li>
                                    <li class="items-start gap-3 text-xs text-slate-600 dark:text-slate-300">
                                        <div class="space-y-5 bg-slate-50 dark:bg-slate-900/50 p-5 mt-2 rounded-2xl border border-slate-100 dark:border-slate-800">
                                                <!-- Akademik -->
                                                <div class="space-y-1.5">
                                                    <p class="text-[12px] font-bold text-slate-800 dark:text-white flex items-center gap-2"><i class="fa fa-book-open text-indigo-500"></i> AKADEMIK & KULIAH</p>
                                                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-x-4 gap-y-1.5 pl-2">
                                                        <p class="text-[12px] text-slate-500 dark:text-slate-400">• <code class="text-indigo-500 font-bold">.akademik</code> : Portal info akademik</p>
                                                        <p class="text-[12px] text-slate-500 dark:text-slate-400">• <code class="text-indigo-500 font-bold">.tugas</code> : Cek deadline & list tugas</p>
                                                        <p class="text-[12px] text-slate-500 dark:text-slate-400">• <code class="text-indigo-500 font-bold">.jadwal</code> : Jadwal kuliah & ruangan</p>
                                                        <p class="text-[12px] text-slate-500 dark:text-slate-400">• <code class="text-indigo-500 font-bold">.kalender</code> : Kalender akademik kampus</p>
                                                        <p class="text-[12px] text-slate-500 dark:text-slate-400">• <code class="text-indigo-500 font-bold">.kurikulum</code> : Struktur matkul & SKS</p>
                                                    </div>
                                                </div>

                                                <!-- Identitas -->
                                                <div class="space-y-1.5">
                                                    <p class="text-[12px] font-bold text-slate-800 dark:text-white flex items-center gap-2"><i class="fa fa-image text-indigo-500"></i> IDENTITAS & INFORMASI</p>
                                                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-x-4 gap-y-1.5 pl-2">
                                                        <p class="text-[12px] text-slate-500 dark:text-slate-400">• <code class="text-indigo-500 font-bold">.info</code> : Identitas kelas & makna logo</p>
                                                        
                                                        <p class="text-[12px] text-slate-500 dark:text-slate-400">• <code class="text-indigo-500 font-bold">.download</code> : Unduh logo & bg zoom</p>
                                                        
                                                    </div>
                                                </div>

                                                <!-- Tools -->
                                                <div class="space-y-1.5">
                                                    <p class="text-[12px] font-bold text-slate-800 dark:text-white flex items-center gap-2"><i class="fa fa-screwdriver-wrench text-indigo-500"></i> TOOLS & PORTAL</p>
                                                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-x-4 gap-y-1.5 pl-2">
                                                        <p class="text-[12px] text-slate-500 dark:text-slate-400">• <code class="text-indigo-500 font-bold">.tentang</code> : Tech stack & versi website</p>
                                                        <p class="text-[12px] text-slate-500 dark:text-slate-400">• <code class="text-indigo-500 font-bold">.panduan</code> : Panduan penggunaan web</p>
                                                        <p class="text-[12px] text-slate-500 dark:text-slate-400">• <code class="text-indigo-500 font-bold">.login</code> : Akses portal member</p>
                                                    </div>
                                                </div>

                                                <!-- Mahasiswa -->
                                                <div class="space-y-1.5">
                                                    <p class="text-[12px] font-bold text-slate-800 dark:text-white flex items-center gap-2"><i class="fa fa-users text-indigo-500"></i> DAFTAR MAHASISWA</p>
                                                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-x-4 gap-y-1.5 pl-2">
                                                        <p class="text-[12px] text-slate-500 dark:text-slate-400">• <code class="text-indigo-500 font-bold">.mhs</code> : Daftar seluruh mahasiswa</p>
                                                        <p class="text-[12px] text-slate-500 dark:text-slate-400">• <code class="text-indigo-500 font-bold">.mhs-co</code> : Command tambahan mhs</p>
                                                        <p class="text-[12px] text-slate-500 dark:text-slate-400">• <code class="text-indigo-500 font-bold">.mhs-nim</code> : Nama & NIM seluruh mhs</p>
                                                        <p class="text-[12px] text-slate-500 dark:text-slate-400">• <code class="text-indigo-500 font-bold">.mhs-l</code> : Nama mahasiswa</p>
                                                        <p class="text-[12px] text-slate-500 dark:text-slate-400">• <code class="text-indigo-500 font-bold">.mhs-l-nim</code> : Nama & NIM mahasiswa</p>
                                                        <p class="text-[12px] text-slate-500 dark:text-slate-400">• <code class="text-indigo-500 font-bold">.mhs-p</code> : Nama mahasiswi</p>
                                                        <p class="text-[12px] text-slate-500 dark:text-slate-400">• <code class="text-indigo-500 font-bold">.mhs-p-nim</code> : Nama & NIM mahasiswi</p>
                                                    </div>
                                                </div>

                                                <!-- Bantuan -->
                                                <div class="space-y-1.5">
                                                    <p class="text-[12px] font-bold text-slate-800 dark:text-white flex items-center gap-2"><i class="fa fa-comments text-indigo-500"></i> INTERAKSI & BANTUAN</p>
                                                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-x-4 gap-y-1.5 pl-2">
                                                        <p class="text-[12px] text-slate-500 dark:text-slate-400">• <code class="text-indigo-500 font-bold">.menu</code> : Menampilkan menu utama ini</p>
                                                        <p class="text-[12px] text-slate-500 dark:text-slate-400">• <code class="text-indigo-500 font-bold">.p / .hai / .halo</code> : Menyapa ProtyBot</p>
                                                    </div>
                                                </div>
                                            </div>
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- PANDUAN KETUA TINGKAT -->
        <div id="guide-keti" class="guide-section">
            <div class="glass-card p-6 md:p-8 rounded-[3rem] border border-slate-100 dark:border-slate-800 shadow-2xl shadow-slate-200/50 dark:shadow-black/20">
                <div class="flex items-center gap-4 mb-6 md:mb-8">
                    <div class="h-14 w-14 bg-indigo-600 text-white rounded-2xl flex items-center justify-center text-xl shadow-xl shadow-indigo-200 dark:shadow-indigo-900/40 transition-colors">
                        <i class="fa fa-crown"></i>
                    </div>
                    <div>
                        <h2 class="text-xl md:text-2xl font-black text-slate-800 dark:text-white plus-jakarta transition-colors">Ketua Tingkat</h2>
                        <p class="text-[11px] text-indigo-600 dark:text-indigo-400 font-extrabold uppercase tracking-[0.2em] mt-1 transition-colors">Decision Making & Monitoring</p>
                    </div>
                </div>

                <div class="space-y-8">
                    <!-- Pembagian Kelompok -->
                    <div class="step-card group">
                        <div class="flex gap-6 relative z-10">

                            <div class="flex-1">
                                <h3 class="text-lg font-black text-slate-800 dark:text-white mb-3 plus-jakarta transition-colors">Manajemen Pembagian Kelompok</h3>
                                <p class="text-xs text-slate-500 dark:text-slate-400 leading-relaxed mb-4 transition-colors">Kelola pembentukan kelompok belajar atau tugas mahasiswa secara sistematis:</p>
                                <ul class="space-y-3">
                                    <li class="flex items-start gap-2 text-xs text-slate-600 dark:text-slate-300 transition-colors">
                                        <i class="fa fa-users-gear text-indigo-600 mt-0.5"></i>
                                        <span><strong>Akses Menu:</strong> Buka menu <strong>Kelompok</strong> pada <strong>sidebar</strong> dan pilih <strong>Atur Kelompok Baru</strong>.</span>
                                    </li>
                                    <li class="flex items-start gap-2 text-xs text-slate-600 dark:text-slate-300 transition-colors">
                                        <i class="fa fa-shuffle text-indigo-600 mt-0.5"></i>
                                        <span><strong>Logika Pengacakan:</strong> Pilih metode yang diinginkan: <strong>Acak Murni</strong> (acak total), <strong>Acak Berimbang</strong> (distribusi gender), atau <strong>Acak Sejenis</strong>.</span>
                                    </li>
                                    <li class="flex items-start gap-2 text-xs text-slate-600 dark:text-slate-300 transition-colors">
                                        <i class="fa fa-sliders text-indigo-600 mt-0.5"></i>
                                        <span><strong>Konfigurasi:</strong> Tentukan <strong>Mata Kuliah</strong>, <strong>Dosen Pengampu</strong>, <strong>Keterangan Tugas</strong>, serta jumlah <strong>Anggota</strong> per kelompok.</span>
                                    </li>
                                    <li class="flex items-start gap-2 text-xs text-slate-600 dark:text-slate-300 transition-colors">
                                        <i class="fa fa-wand-magic-sparkles text-indigo-600 mt-0.5"></i>
                                        <span><strong>Eksekusi:</strong> Tekan tombol <strong>Acak Murni</strong> untuk memulai proses pengacakan sistem.</span>
                                    </li>
                                    <li class="flex items-start gap-2 text-xs text-slate-600 dark:text-slate-300 transition-colors">
                                        <i class="fa fa-floppy-disk text-indigo-600 mt-0.5"></i>
                                        <span><strong>Finalisasi:</strong> Jika sudah sesuai, klik <strong>Simpan Kelompok</strong> agar mahasiswa bisa melihat tim mereka.</span>
                                    </li>
                                    <li class="flex items-start gap-2 text-xs text-slate-600 dark:text-slate-300 transition-colors">
                                        <i class="fa fa-user-pen text-indigo-600 mt-0.5"></i>
                                        <span>Gunakan fitur <strong>Atur Manual</strong> jika perlu melakukan penyesuaian posisi anggota.</span>
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div>

                    <!-- Edit Kelompok -->
                    <div class="step-card group">
                        <div class="flex gap-6 relative z-10">

                            <div class="flex-1">
                                <h3 class="text-lg font-black text-slate-800 dark:text-white mb-3 plus-jakarta transition-colors">Modifikasi Pembagian Kelompok</h3>
                                <p class="text-xs text-slate-500 dark:text-slate-400 leading-relaxed mb-4 transition-colors">Gunakan fitur ini jika perlu mengubah anggota atau data pada set kelompok yang sudah ada:</p>
                                <ul class="space-y-3">
                                    <li class="flex items-start gap-2 text-xs text-slate-600 dark:text-slate-300 transition-colors">
                                        <i class="fa fa-folder-open text-indigo-600 mt-0.5"></i>
                                        <span><strong>Akses Menu:</strong> Buka menu <strong>Kelompok</strong> pada <strong>sidebar</strong>.</span>
                                    </li>
                                    <li class="flex items-start gap-2 text-xs text-slate-600 dark:text-slate-300 transition-colors">
                                        <i class="fa fa-list-check text-indigo-600 mt-0.5"></i>
                                        <span><strong>Pilih Data:</strong> Tentukan <strong>Mata Kuliah</strong> dan <strong>Set Kelompok</strong> yang ingin diubah melalui menu dropdown.</span>
                                    </li>
                                    <li class="flex items-start gap-2 text-xs text-slate-600 dark:text-slate-300 transition-colors">
                                        <i class="fa fa-pen-to-square text-indigo-600 mt-0.5"></i>
                                        <span><strong>Aksi:</strong> Klik tombol <strong>Edit</strong> untuk masuk ke mode pengaturan.</span>
                                    </li>
                                    <li class="flex items-start gap-2 text-xs text-slate-600 dark:text-slate-300 transition-colors">
                                        <i class="fa fa-arrows-rotate text-indigo-600 mt-0.5"></i>
                                        <span><strong>Proses Perubahan:</strong> Lakukan langkah penyesuaian yang sama seperti saat pembuatan kelompok baru (mengatur ulang anggota atau deskripsi tugas).</span>
                                    </li>
                                    <li class="flex items-start gap-2 text-xs text-slate-600 dark:text-slate-300 transition-colors">
                                        <i class="fa fa-cloud-arrow-up text-indigo-600 mt-0.5"></i>
                                        <span><strong>Simpan:</strong> Pastikan mengklik tombol <strong>simpan kembali</strong> agar perubahan terupdate di sistem mahasiswa.</span>
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div>

                    <!-- Reminder WhatsApp -->
                    <div class="step-card group">
                        <div class="flex gap-6 relative z-10">

                            <div class="flex-1">
                                <h3 class="text-lg font-black text-slate-800 dark:text-white mb-3 plus-jakarta transition-colors">Mengirim Pengingat ke WhatsApp</h3>
                                <p class="text-xs text-slate-500 dark:text-slate-400 leading-relaxed mb-4 transition-colors">Memastikan tidak ada mahasiswa yang ketinggalan informasi krusial:</p>
                                <ul class="space-y-3">
                                    <li class="flex items-start gap-2 text-xs text-slate-600 dark:text-slate-300 transition-colors">
                                        <i class="fa fa-bell text-indigo-600 mt-0.5"></i>
                                        <span><strong>Akses Menu:</strong> Buka <strong>Sidebar > Notifikasi</strong> untuk masuk ke dashboard <strong>Kirim Reminder ke WhatsApp</strong>.</span>
                                    </li>
                                    <li class="flex items-start gap-2 text-xs text-slate-600 dark:text-slate-300 transition-colors">
                                        <i class="fa fa-layer-group text-indigo-600 mt-0.5"></i>
                                        <div class="space-y-1">
                                            <span><strong>Pilih Jenis Pengingat:</strong> Tersedia 6 kategori pengingat utama yang bisa dikirimkan:</span>
                                            <ol class="list-decimal list-inside pl-2 space-y-1 opacity-80 decoration-indigo-200">
                                                <li><strong>Reminder KRS:</strong> Mengingatkan mahasiswa yang belum melakukan pengisian atau validasi <strong>KRS</strong>.</li>
                                                <li><strong>Tugas Aktif:</strong> Mengirimkan rekap daftar <strong>tugas aktif</strong> yang mendekati deadline.</li>
                                                <li><strong>Jadwal Kuliah:</strong> Mengirimkan info <strong>jadwal kuliah</strong> untuk hari ini atau esok hari.</li>
                                                <li><strong>Kas Mingguan:</strong> Memberikan teguran halus bagi mahasiswa yang memiliki tunggakan <strong>uang kas</strong>.</li>
                                                <li><strong>Piket Proyektor:</strong> Mengingatkan petugas <strong>piket proyektor</strong> yang bertanggung jawab hari ini, besok, atau selama sepekan ke depan.</li>
                                            </ol>
                                        </div>
                                    </li>
                                    <li class="flex items-start gap-2 text-xs text-slate-600 dark:text-slate-300 transition-colors">
                                        <i class="fa fa-comment-sms text-indigo-600 mt-0.5"></i>
                                        <span><strong>Kirim Notifikasi:</strong> Tekan tombol Kirim (<strong>ikon WhatsApp</strong>) pada kartu kategori yang diinginkan.</span>
                                    </li>
                                    <li class="flex items-start gap-2 text-xs text-slate-600 dark:text-slate-300 transition-colors">
                                        <i class="fa fa-paper-plane text-indigo-600 mt-0.5"></i>
                                        <span><strong>Verifikasi:</strong> Sistem akan memproses data dan mengirimkan pesan secara otomatis ke grup tujuan.</span>
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- PANDUAN SEKRETARIS -->
        <div id="guide-sekretaris" class="guide-section">
            <div class="glass-card p-6 md:p-8 rounded-[3rem] border border-slate-100 dark:border-slate-800 shadow-2xl shadow-slate-200/50 dark:shadow-black/20 transition-all">
                <div class="flex items-center gap-4 mb-6 md:mb-8">
                    <div class="h-14 w-14 bg-indigo-600 text-white rounded-2xl flex items-center justify-center text-xl shadow-xl shadow-indigo-200 dark:shadow-indigo-900/40 transition-colors">
                        <i class="fa fa-file-signature"></i>
                    </div>
                    <div>
                        <h2 class="text-xl md:text-2xl font-black text-slate-800 dark:text-white plus-jakarta transition-colors">Sekretaris</h2>
                        <p class="text-[11px] text-indigo-600 dark:text-indigo-400 font-extrabold uppercase tracking-[0.2em] mt-1 transition-colors">Daily Administrative Ops</p>
                    </div>
                </div>

                <div class="space-y-8">
                    <!-- Kelola Absensi -->
                    <div class="step-card group">
                        <div class="flex gap-6 relative z-10">

                            <div class="flex-1">
                                <h3 class="text-lg font-black text-slate-800 dark:text-white mb-3 plus-jakarta transition-colors">Langkah Membuka Absensi Digital</h3>
                                <p class="text-xs text-slate-500 dark:text-slate-400 leading-relaxed mb-4 transition-colors">Lakukan ini setiap kali mata kuliah dimulai:</p>
                                <ul class="space-y-3">
                                    <li class="flex items-start gap-2 text-xs text-slate-600 dark:text-slate-300 transition-colors">
                                        <i class="fa fa-fingerprint text-indigo-600 mt-0.5"></i>
                                        <span><strong>Navigasi:</strong> Pilih menu <strong>Input Absen</strong> di bawah kategori Absensi pada <strong>sidebar</strong>.</span>
                                    </li>
                                    <li class="flex items-start gap-2 text-xs text-slate-600 dark:text-slate-300 transition-colors">
                                        <i class="fa fa-clipboard-list text-indigo-600 mt-0.5"></i>
                                        <span><strong>Identifikasi:</strong> Pilih <strong>Mata Kuliah</strong> dan sesi pertemuan hari ini.</span>
                                    </li>
                                    <li class="flex items-start gap-2 text-xs text-slate-600 dark:text-slate-300 transition-colors">
                                        <i class="fa fa-user-check text-indigo-600 mt-0.5"></i>
                                        <span><strong>Update Status:</strong> Sistem otomatis menandai semua <strong>"Hadir"</strong>. Ubah hanya mahasiswa yang tidak hadir sesuai keterangan masing-masing.</span>
                                    </li>
                                    <li class="flex items-start gap-2 text-xs text-slate-600 dark:text-slate-300 transition-colors">
                                        <i class="fa fa-save text-indigo-600 mt-0.5"></i>
                                        <span><strong>Validasi:</strong> Klik <strong>Simpan</strong> dan setujui konfirmasi keamanan yang muncul.</span>
                                    </li>
                                    <li class="flex items-start gap-2 text-xs text-slate-600 dark:text-slate-300 transition-colors">
                                        <i class="fa fa-paper-plane text-indigo-600 mt-0.5"></i>
                                        <span><strong>Notifikasi:</strong> Pilih "Ya" pada popup jika ingin membagikan <strong>laporan kehadiran</strong> ke Grub.</span>
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div>

                    <!-- Download Absensi -->
                    <div class="step-card group">
                        <div class="flex gap-6 relative z-10">

                            <div class="flex-1">
                                <h3 class="text-lg font-black text-slate-800 dark:text-white mb-3 plus-jakarta transition-colors">Manajemen Unduh Dokumen Absensi</h3>
                                <p class="text-xs text-slate-500 dark:text-slate-400 leading-relaxed mb-4 transition-colors">Gunakan fitur ini untuk keperluan pengarsipan atau pelaporan data kehadiran dalam format Excel.</p>
                                <ul class="space-y-3">
                                    <li class="flex items-start gap-2 text-xs text-slate-600 dark:text-slate-300 transition-colors">
                                        <i class="fa fa-file-excel text-indigo-600 mt-0.5"></i>
                                        <div class="space-y-1">
                                            <span><strong>Rekap Absensi Semester (Minggu 1–16):</strong></span>
                                            <ol class="list-decimal list-inside pl-2 space-y-1 opacity-80 decoration-indigo-200">
                                                <li>Akses <strong>Sidebar > Absensi > Rekap Absen</strong>.</li>
                                                <li>Pilih Mata Kuliah yang diinginkan melalui menu dropdown.</li>
                                                <li>Klik tombol <strong>Export Rekap</strong> di bagian atas, konfirmasi validasi, dan dokumen akan otomatis terunduh dalam format <strong>Excel (.xlsx)</strong>.</li>
                                            </ol>
                                        </div>
                                    </li>
                                    <li class="flex items-start gap-2 text-xs text-slate-600 dark:text-slate-300 transition-colors">
                                        <i class="fa fa-file-lines text-indigo-600 mt-0.5"></i>
                                        <div class="space-y-1">
                                            <span><strong>Data Absensi Per Pertemuan:</strong></span>
                                            <ol class="list-decimal list-inside pl-2 space-y-1 opacity-80 decoration-indigo-200">
                                                <li>Akses <strong>Sidebar > Absensi > Data Absen</strong>.</li>
                                                <li>Gunakan filter untuk memilih Mata Kuliah, nomor Pertemuan, and Status kehadiran tertentu.</li>
                                                <li>Klik tombol <strong>Export Rekap</strong> dan selujui validasi untuk mendapatkan file <strong>Excel</strong>-nya.</li>
                                            </ol>
                                        </div>
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div>

                    <!-- Membuat Tugas -->
                    <div class="step-card group">
                        <div class="flex gap-6 relative z-10">

                            <div class="flex-1">
                                <h3 class="text-lg font-black text-slate-800 dark:text-white mb-3 plus-jakarta transition-colors">Membuat & Mengelola Tugas Kuliah</h3>
                                <p class="text-xs text-slate-500 dark:text-slate-400 leading-relaxed mb-4 transition-colors">Mendata tugas dari dosen agar terdokumentasi dengan baik:</p>
                                <ul class="space-y-3">
                                    <li class="flex items-start gap-2 text-xs text-slate-600 dark:text-slate-300 transition-colors">
                                        <i class="fa fa-plus-circle text-indigo-600 mt-0.5"></i>
                                        <span><strong>Input:</strong> Buka menu <strong>Tugas Kuliah</strong>, klik <strong>(+)</strong>, lengkapi form tugas, dan simpan. Notifikasi otomatis akan langsung masuk ke <strong>grup WA</strong>.</span>
                                    </li>
                                    <li class="flex items-start gap-2 text-xs text-slate-600 dark:text-slate-300 transition-colors">
                                        <i class="fa fa-pen-to-square text-indigo-600 mt-0.5"></i>
                                        <span><strong>Edit:</strong> Klik <strong>ikon Pensil</strong> pada list tugas, sesuaikan isinya, dan simpan kembali untuk mengirim notifikasi update.</span>
                                    </li>
                                    <li class="flex items-start gap-2 text-xs text-slate-600 dark:text-slate-300 transition-colors">
                                        <i class="fa fa-trash-can text-indigo-600 mt-0.5"></i>
                                        <span><strong>Hapus:</strong> Klik <strong>ikon Sampah</strong> dan konfirmasi jika tugas tersebut ingin dibatalkan atau dihapus.</span>
                                    </li>
                                    <li class="flex items-start gap-2 text-xs text-slate-600 dark:text-slate-300 transition-colors">
                                        <i class="fa fa-paper-plane text-indigo-600 mt-0.5"></i>
                                        <span><strong>Reminder:</strong> Klik <strong>ikon Pesawat</strong> (di bagian atas) untuk mengirimkan ringkasan seluruh tugas aktif ke grup WhatsApp kelas secara instan.</span>
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- PANDUAN BENDAHARA -->
        <div id="guide-bendahara" class="guide-section">
            <div class="glass-card p-6 md:p-8 rounded-[3rem] border border-slate-100 dark:border-slate-800 shadow-2xl shadow-slate-200/50 dark:shadow-black/20 transition-colors">
                <div class="flex items-center gap-4 mb-6 md:mb-8">
                    <div class="h-14 w-14 bg-indigo-600 text-white rounded-2xl flex items-center justify-center text-xl shadow-xl shadow-indigo-200 dark:shadow-indigo-900/40 transition-colors">
                        <i class="fa fa-wallet"></i>
                    </div>
                    <div>
                        <h2 class="text-xl md:text-2xl font-black text-slate-800 dark:text-white plus-jakarta transition-colors">Bendahara</h2>
                        <p class="text-[11px] text-indigo-600 dark:text-indigo-400 font-extrabold uppercase tracking-[0.2em] mt-1 transition-colors">Financial Data Entry</p>
                    </div>
                </div>

                <div class="space-y-8">
                    <!-- Tambah Kas Perminggu -->
                    <div class="step-card group">
                        <div class="flex gap-6 relative z-10">

                            <div class="flex-1">
                                <h3 class="text-lg font-black text-slate-800 dark:text-white mb-3 plus-jakarta transition-colors">Tambah Kas Per Minggu</h3>
                                <p class="text-xs text-slate-500 dark:text-slate-400 leading-relaxed mb-4 transition-colors">Fitur cepat untuk menginput pembayaran seluruh kelas dalam satu minggu:</p>
                                <ul class="space-y-3">
                                    <li class="flex items-start gap-2 text-xs text-slate-600 dark:text-slate-300 transition-colors">
                                        <i class="fa fa-calendar-check text-indigo-600 mt-0.5"></i>
                                        <span><strong>Navigasi:</strong> Masuk ke menu <strong>Input Per Minggu</strong> di bawah kategori Keuangan pada <strong>sidebar</strong>.</span>
                                    </li>
                                    <li class="flex items-start gap-2 text-xs text-slate-600 dark:text-slate-300 transition-colors">
                                        <i class="fa fa-calendar-week text-indigo-600 mt-0.5"></i>
                                        <span><strong>Pilih Periode:</strong> Tentukan <strong>minggu 1–16</strong> yang ingin diproses.</span>
                                    </li>
                                    <li class="flex items-start gap-2 text-xs text-slate-600 dark:text-slate-300 transition-colors">
                                        <i class="fa fa-circle-check text-indigo-600 mt-0.5"></i>
                                        <span><strong>Update Pembayaran:</strong> Klik status Belum hingga berubah menjadi <strong>Lunas</strong>. Kamu tidak perlu mengisi tanggal secara manual karena sistem akan otomatis mengambil data hari ini.</span>
                                    </li>
                                    <li class="flex items-start gap-2 text-xs text-slate-600 dark:text-slate-300 transition-colors">
                                        <i class="fa fa-save text-indigo-600 mt-0.5"></i>
                                        <span><strong>Update Saldo:</strong> Tekan tombol <strong>Simpan Kas</strong> untuk memperbarui saldo kas kelas di <strong>dashboard utama</strong> secara otomatis.</span>
                                    </li>
                                    <li class="flex items-start gap-2 text-xs text-slate-600 dark:text-slate-300 transition-colors">
                                        <i class="fa fa-paper-plane text-indigo-600 mt-0.5"></i>
                                        <span><strong>Notifikasi:</strong> Pilih "Ya" pada popup jika ingin membagikan laporan kehadiran ke <strong>Grub</strong>.</span>
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div>

                    <!-- Tambah Kas Per Individu -->
                    <div class="step-card group">
                        <div class="flex gap-6 relative z-10">

                            <div class="flex-1">
                                <h3 class="text-lg font-black text-slate-800 dark:text-white mb-3 plus-jakarta transition-colors">Tambah Kas Per Individu</h3>
                                <p class="text-xs text-slate-500 dark:text-slate-400 leading-relaxed mb-4 transition-colors">Mencatat pembayaran mahasiswa secara spesifik (misal rapel atau cicilan):</p>
                                <ul class="space-y-3">
                                    <li class="flex items-start gap-2 text-xs text-slate-600 dark:text-slate-300 transition-colors">
                                        <i class="fa fa-user-tag text-indigo-600 mt-0.5"></i>
                                        <span><strong>Navigasi:</strong> Masuk ke menu <strong>Input Per Individu</strong> di bawah kategori Keuangan pada <strong>sidebar</strong>.</span>
                                    </li>
                                    <li class="flex items-start gap-2 text-xs text-slate-600 dark:text-slate-300 transition-colors">
                                        <i class="fa fa-magnifying-glass text-indigo-600 mt-0.5"></i>
                                        <span><strong>Cari Nama:</strong> Pilih nama mahasiswa yang ingin membayar pada kolom <strong>dropdown</strong> yang tersedia.</span>
                                    </li>
                                    <li class="flex items-start gap-2 text-xs text-slate-600 dark:text-slate-300 transition-colors">
                                        <i class="fa fa-circle-check text-indigo-600 mt-0.5"></i>
                                        <span><strong>Konfirmasi Lunas:</strong> Klik status Belum hingga berubah menjadi <strong>Lunas</strong> pada kolom minggu yang diinginkan. Tanggal bayar akan terisi otomatis oleh sistem.</span>
                                    </li>
                                    <li class="flex items-start gap-2 text-xs text-slate-600 dark:text-slate-300 transition-colors">
                                        <i class="fa fa-save text-indigo-600 mt-0.5"></i>
                                        <span><strong>Update Saldo:</strong> Tekan tombol <strong>Simpan Kas</strong> untuk memvalidasi penambahan saldo kelas.</span>
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div>

                    <!-- Catat Pemasukan & Pengeluaran -->
                    <div class="step-card group">
                        <div class="flex gap-6 relative z-10">

                            <div class="flex-1">
                                <h3 class="text-lg font-black text-slate-800 dark:text-white mb-3 plus-jakarta transition-colors">Pencatatan Pemasukan & Pengeluaran Lainnya</h3>
                                <p class="text-xs text-slate-500 dark:text-slate-400 leading-relaxed mb-4 transition-colors">Mengelola sirkulasi uang selain iuran kas rutin:</p>
                                <ul class="space-y-3">
                                    <li class="flex items-start gap-2 text-xs text-slate-600 dark:text-slate-300 transition-colors">
                                        <i class="fa fa-user-tag text-indigo-600 mt-0.5"></i>
                                        <span><strong>Navigasi:</strong> Masuk ke menu <strong>Data Kas</strong> di bawah kategori Keuangan pada <strong>sidebar</strong>.</span>
                                    </li>
                                    <li class="flex items-start gap-2 text-xs text-slate-600 dark:text-slate-300 transition-colors">
                                        <i class="fa fa-list-ol text-indigo-600 mt-0.5"></i>
                                        <div class="space-y-1">
                                            <span><strong>Input Transaksi:</strong> Pada bagian Catat Transaksi Baru, pilih jenis:</span>
                                            <ol class="list-decimal list-inside pl-2 space-y-1 opacity-80 decoration-indigo-200">
                                                <li><strong>Pemasukan:</strong> Jika ada dana masuk selain kas.</li>
                                                <li><strong>Pengeluaran:</strong> Jika ada dana yang terpakai.</li>
                                            </ol>
                                        </div>
                                    </li>
                                    <li class="flex items-start gap-2 text-xs text-slate-600 dark:text-slate-300 transition-colors">
                                        <i class="fa fa-calendar-plus text-indigo-600 mt-0.5"></i>
                                        <span><strong>Lengkapi Data:</strong> Isi keterangan, nominal uang, dan tanggal, lalu klik tombol <strong>(+)</strong>.</span>
                                    </li>
                                    <li class="flex items-start gap-2 text-xs text-slate-600 dark:text-slate-300 transition-colors">
                                        <i class="fa fa-arrows-rotate text-indigo-600 mt-0.5"></i>
                                        <span><strong>Update Otomatis:</strong> <strong>Saldo</strong> akhir di dashboard akan otomatis menyesuaikan dengan transaksi yang baru saja kamu buat.</span>
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div>

                    <!-- Reminder Kas WA -->
                    <div class="step-card group">
                        <div class="flex gap-6 relative z-10">

                            <div class="flex-1">
                                <h3 class="text-lg font-black text-slate-800 dark:text-white mb-3 plus-jakarta transition-colors">Kirim Pengingat Kas ke WhatsApp</h3>
                                <p class="text-xs text-slate-500 dark:text-slate-400 leading-relaxed mb-4 transition-colors">Menagih tunggakan secara otomatis melalui WhatsApp:</p>
                                <ul class="space-y-3">
                                    <li class="flex items-start gap-2 text-xs text-slate-600 dark:text-slate-300 transition-colors">
                                        <i class="fa fa-magnifying-glass-dollar text-indigo-600 mt-0.5"></i>
                                        <span><strong>Navigasi:</strong> Masuk ke menu <strong>Data Kas</strong> di sidebar, lalu scroll ke bagian bawah pada tabel Status Pembayaran.</span>
                                    </li>
                                    <li class="flex items-start gap-2 text-xs text-slate-600 dark:text-slate-300 transition-colors">
                                        <i class="fa fa-calendar-days text-indigo-600 mt-0.5"></i>
                                        <span><strong>Pilih Periode:</strong> Tentukan minggu yang ingin dicek.</span>
                                    </li>
                                    <li class="flex items-start gap-2 text-xs text-slate-600 dark:text-slate-300 transition-colors">
                                        <i class="fa fa-paper-plane text-indigo-600 mt-0.5"></i>
                                        <span><strong>Klik Ikon Pesawat:</strong> Tekan <strong>ikon pesawat</strong> untuk menyiapkan draf pengingat otomatis.</span>
                                    </li>
                                    <li class="flex items-start gap-2 text-xs text-slate-600 dark:text-slate-300 transition-colors">
                                        <i class="fa fa-comment-sms text-indigo-600 mt-0.5"></i>
                                        <span><strong>Konfirmasi:</strong> Klik Kirim Pesan ke <strong>Grup WA</strong>.</span>
                                    </li>
                                    <li class="flex items-start gap-2 text-xs text-slate-600 dark:text-slate-300 transition-colors">
                                        <i class="fa fa-whatsapp text-indigo-600 mt-0.5"></i>
                                        <span><strong>Hasil:</strong> <strong>Grup WhatsApp</strong> akan menerima pesan rapi berisi siapa saja yang belum bayar dan berapa nominal yang harus mereka lunasi.</span>
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div>

                    <!-- Download Keuangan -->
                    <div class="step-card group">
                        <div class="flex gap-6 relative z-10">

                            <div class="flex-1">
                                <h3 class="text-lg font-black text-slate-800 dark:text-white mb-3 plus-jakarta transition-colors">Manajemen Unduh Dokumen Keuangan</h3>
                                <p class="text-xs text-slate-500 dark:text-slate-400 leading-relaxed mb-4 transition-colors">Gunakan fitur ini untuk keperluan pengarsipan atau pelaporan data keuangan dalam format Excel.</p>
                                <ul class="space-y-3">
                                    <li class="flex items-start gap-2 text-xs text-slate-600 dark:text-slate-300 transition-colors">
                                        <i class="fa fa-file-excel text-indigo-600 mt-0.5"></i>
                                        <div class="space-y-1">
                                            <span><strong>Rekap Kas (Minggu 1–16):</strong></span>
                                            <ol class="list-decimal list-inside pl-2 space-y-1 opacity-80 decoration-indigo-200">
                                                <li>Akses <strong>Sidebar > Keuangan > Rekap Kas</strong>.</li>
                                                <li>Klik tombol <strong>Export Rekap</strong> di bagian atas, konfirmasi validasi, and dokumen akan otomatis terunduh dalam format <strong>Excel (.xlsx)</strong>.</li>
                                            </ol>
                                        </div>
                                    </li>
                                    <li class="flex items-start gap-2 text-xs text-slate-600 dark:text-slate-300 transition-colors">
                                        <i class="fa fa-file-invoice-dollar text-indigo-600 mt-0.5"></i>
                                        <div class="space-y-1">
                                            <span><strong>Data Kas Per Minggu:</strong></span>
                                            <ol class="list-decimal list-inside pl-2 space-y-1 opacity-80 decoration-indigo-200">
                                                <li>Akses <strong>Sidebar > Keuangan > Data Kas</strong>.</li>
                                                <li>Scroll ke bagian bawah pada tabel Status Pembayaran.</li>
                                                <li>Gunakan filter untuk memilih Minggu and Status keuangan tertentu.</li>
                                                <li>Klik tombol dengan <strong>icon Excel</strong> and selujui validasi untuk mendapatkan file Excel-nya.</li>
                                            </ol>
                                        </div>
                                    </li>
                                    <li class="flex items-start gap-2 text-xs text-slate-600 dark:text-slate-300 transition-colors">
                                        <i class="fa fa-file-import text-indigo-600 mt-0.5"></i>
                                        <div class="space-y-1">
                                            <span><strong>Data Arus Kas (Pemasukan dan Pengeluaran):</strong></span>
                                            <ol class="list-decimal list-inside pl-2 space-y-1 opacity-80 decoration-indigo-200">
                                                <li>Akses <strong>Sidebar > Keuangan > Data Kas</strong>.</li>
                                                <li>Klik tombol <strong>Export Arus</strong> and selujui validasi untuk mendapatkan file <strong>Excel</strong>-nya.</li>
                                            </ol>
                                        </div>
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- PANDUAN MANAGER TUGAS -->
        <div id="guide-manager" class="guide-section">
            <div class="glass-card p-6 md:p-8 rounded-[3rem] border border-slate-100 dark:border-slate-800 shadow-2xl shadow-slate-200/50 dark:shadow-black/20 text-slate-800 dark:text-white transition-colors">
                <div class="flex items-center gap-4 mb-6 md:mb-8">
                    <div class="h-14 w-14 bg-indigo-600 text-white rounded-2xl flex items-center justify-center text-xl shadow-xl shadow-indigo-200 dark:shadow-indigo-900/40 transition-colors">
                        <i class="fa fa-tasks"></i>
                    </div>
                    <div>
                        <h2 class="text-xl md:text-2xl font-black text-slate-800 dark:text-white plus-jakarta transition-colors">Manager Tugas</h2>
                        <p class="text-[11px] text-indigo-600 dark:text-indigo-400 font-extrabold uppercase tracking-[0.2em] mt-1 transition-colors">Assignment & Deadline Management</p>
                    </div>
                </div>

                <div class="space-y-8">
                    <!-- Membuat Tugas -->
                    <div class="step-card group">
                        <div class="flex gap-6 relative z-10">

                            <div class="flex-1">
                                <h3 class="text-lg font-black text-slate-800 dark:text-white mb-3 plus-jakarta transition-colors">Membuat & Mengelola Tugas Kuliah</h3>
                                <p class="text-xs text-slate-500 dark:text-slate-400 leading-relaxed mb-4 transition-colors">Mendata tugas dari dosen agar terdokumentasi dengan baik:</p>
                                <ul class="space-y-3">
                                    <li class="flex items-start gap-2 text-xs text-slate-600 dark:text-slate-300 transition-colors">
                                        <i class="fa fa-folder-plus text-indigo-600 mt-0.5"></i>
                                        <span><strong>Input:</strong> Buka menu <strong>Tugas Kuliah</strong>, klik <strong>(+)</strong>, lengkapi form tugas, dan simpan. Notifikasi otomatis akan langsung masuk ke <strong>grup WA</strong>.</span>
                                    </li>
                                    <li class="flex items-start gap-2 text-xs text-slate-600 dark:text-slate-300 transition-colors">
                                        <i class="fa fa-pen-to-square text-indigo-600 mt-0.5"></i>
                                        <span><strong>Edit:</strong> Klik <strong>ikon Pensil</strong> pada list tugas, sesuaikan isinya, dan simpan kembali untuk mengirim notifikasi update.</span>
                                    </li>
                                    <li class="flex items-start gap-2 text-xs text-slate-600 dark:text-slate-300 transition-colors">
                                        <i class="fa fa-trash-can text-indigo-600 mt-0.5"></i>
                                        <span><strong>Hapus:</strong> Klik <strong>ikon Sampah</strong> dan konfirmasi jika tugas tersebut ingin dibatalkan atau dihapus.</span>
                                    </li>
                                    <li class="flex items-start gap-2 text-xs text-slate-600 dark:text-slate-300 transition-colors">
                                        <i class="fa fa-paper-plane text-indigo-600 mt-0.5"></i>
                                        <span><strong>Reminder:</strong> Klik <strong>ikon Pesawat</strong> (di bagian atas) untuk mengirimkan ringkasan seluruh tugas aktif ke grup WhatsApp kelas secara instan.</span>
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- PANDUAN ADMINISTRATOR -->
        <div id="guide-admin" class="guide-section">
            <div class="glass-card p-6 md:p-8 rounded-[3rem] border border-slate-100 dark:border-slate-800 shadow-2xl shadow-slate-200/50 dark:shadow-black/20 transition-colors">
                <div class="flex items-center gap-4 mb-6 md:mb-8">
                    <div class="h-14 w-14 bg-indigo-600 text-white rounded-2xl flex items-center justify-center text-xl shadow-xl shadow-indigo-200 dark:shadow-indigo-900/40 transition-colors">
                        <i class="fa fa-user-shield"></i>
                    </div>
                    <div>
                        <h2 class="text-xl md:text-2xl font-black text-slate-800 dark:text-white plus-jakarta transition-colors">Administrator Utama</h2>
                        <p class="text-[11px] text-indigo-600 dark:text-indigo-400 font-extrabold uppercase tracking-[0.2em] mt-1 transition-colors">Core System Maintenance</p>
                    </div>
                </div>

                <div class="space-y-8">
                    <!-- Full Access & Database Control -->
                    <div class="step-card group">
                        <div class="flex gap-6 relative z-10">

                            <div class="flex-1">
                                <h3 class="text-lg font-black text-slate-800 dark:text-white mb-3 plus-jakarta transition-colors">Full Control & Database Management</h3>
                                <p class="text-xs text-slate-500 dark:text-slate-400 leading-relaxed mb-4 transition-colors">Administrator menyatukan seluruh fungsi role sebelumnya dan memiliki wewenang penuh untuk:</p>
                                <ul class="space-y-3">
                                    <li class="flex items-start gap-2 text-xs text-slate-600 dark:text-slate-300 transition-colors">
                                        <i class="fa fa-database text-indigo-600 mt-0.5"></i>
                                        <span><strong>Edit Database:</strong> Mengubah, menghapus, atau memperbaiki data (Nama, NIM, Saldo Kas, Kehadiran) melalui kategori <strong>Database</strong> di <strong>sidebar</strong>.</span>
                                    </li>
                                    <li class="flex items-start gap-2 text-xs text-slate-600 dark:text-slate-300 transition-colors">
                                        <i class="fa fa-user-gear text-indigo-600 mt-0.5"></i>
                                        <span><strong>User Role Management:</strong> Menunjuk atau mencopot jabatan pengurus (<strong>Role</strong>) langsung melalui tabel <strong>Users</strong>.</span>
                                    </li>
                                    <li class="flex items-start gap-2 text-xs text-slate-600 dark:text-slate-300 transition-colors">
                                        <i class="fa fa-eye text-indigo-600 mt-0.5"></i>
                                        <span><strong>Log Aktivitas:</strong> Pantau seluruh rekam jejak aksi pengguna secara <strong>real-time</strong> untuk menjaga keamanan sistem.</span>
                                    </li>
                                    <li class="flex items-start gap-2 text-xs text-slate-600 dark:text-slate-300 transition-colors">
                                        <i class="fa fa-triangle-exclamation text-indigo-600 mt-0.5"></i>
                                        <span><strong>Peringatan Keamanan:</strong> Gunakan fitur ini dengan bijak karena setiap perubahan data pada database bersifat <strong>permanen</strong>.</span>
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div>

                    <!-- Validasi KRS & KSM -->
                    <div class="step-card group">
                        <div class="flex gap-6 relative z-10">

                            <div class="flex-1">
                                <h3 class="text-lg font-black text-slate-800 dark:text-white mb-3 plus-jakarta transition-colors">Validasi Berkas (KRS & KSM)</h3>
                                <p class="text-xs text-slate-500 dark:text-slate-400 leading-relaxed mb-4 transition-colors">Memetakan dan memverifikasi dokumen akademik mahasiswa:</p>
                                <ul class="space-y-3">
                                    <li class="flex items-start gap-2 text-xs text-slate-600 dark:text-slate-300 transition-colors">
                                        <i class="fa fa-user-check text-indigo-600 mt-0.5"></i>
                                        <span><strong>Akses Menu:</strong> Buka <strong>Website > Validasi KRS</strong> melalui sidebar.</span>
                                    </li>
                                    <li class="flex items-start gap-2 text-xs text-slate-600 dark:text-slate-300 transition-colors">
                                        <i class="fa fa-file-invoice text-indigo-600 mt-0.5"></i>
                                        <span><strong>Peninjauan Data:</strong> Periksa daftar mata kuliah yang diinput mahasiswa dengan menekan tombol <strong>Detail</strong>.</span>
                                    </li>
                                     <li class="flex items-start gap-2 text-xs text-slate-600 dark:text-slate-300 transition-colors">
                                        <i class="fa fa-signature text-indigo-600 mt-0.5"></i>
                                        <div class="space-y-1">
                                            <span><strong>Tindakan Verifikasi:</strong></span>
                                            <ol class="list-decimal list-inside pl-2 space-y-1 opacity-80 decoration-indigo-200">
                                                <li><strong>Setujui (Verify):</strong> Klik Ikon Centang jika data sudah sesuai dengan KRS resmi.</li>
                                                <li><strong>Tolak (Reject):</strong> Klik Ikon Silang jika terdapat kesalahan agar mahasiswa dapat memperbaikinya.</li>
                                            </ol>
                                        </div>
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div>

                    <!-- System Maintenance -->
                    <div class="step-card group">
                        <div class="flex gap-6 relative z-10">

                            <div class="flex-1">
                                <h3 class="text-lg font-black text-slate-800 dark:text-white mb-3 plus-jakarta transition-colors">System Settings & Maintenance</h3>
                                <p class="text-xs text-slate-500 dark:text-slate-400 leading-relaxed mb-4 transition-colors">Menjaga stabilitas dan keberlangsungan portal:</p>
                                <ul class="space-y-3">
                                    <li class="flex items-start gap-2 text-xs text-slate-600 dark:text-slate-300 transition-colors">
                                        <i class="fa fa-gears text-indigo-600 mt-0.5"></i>
                                        <span><strong>Konfigurasi Global:</strong> Atur Info Web, No WA Bot, dan Link Grup melalui menu <strong>Pengaturan</strong>.</span>
                                    </li>
                                    <li class="flex items-start gap-2 text-xs text-slate-600 dark:text-slate-300 transition-colors">
                                        <i class="fa fa-screwdriver-wrench text-indigo-600 mt-0.5"></i>
                                        <span><strong>Maintenance Mode:</strong> Aktifkan mode perbaikan untuk mencegah bentrok data saat melakukan update besar pada sistem.</span>
                                    </li>
                                    <li class="flex items-start gap-2 text-xs text-slate-600 dark:text-slate-300 transition-colors">
                                        <i class="fa fa-broom text-indigo-600 mt-0.5"></i>
                                        <span><strong>Reset Data:</strong> Gunakan fitur <strong>Reset Semester</strong> (Absensi, Kas, Log) untuk membersihkan database saat memasuki semester baru.</span>
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
</div>
</main>

    <?php include 'includes/footer_main.php'; ?>
    <?php include 'includes/script_main.php'; ?>

    <script>
        function showGuide(role) {
            // Reset search input
            document.getElementById('guide-search').value = "";
            searchGuide("");

            // Hide all guides
            document.querySelectorAll('.guide-section').forEach(el => {
                el.classList.remove('active');
            });
            // Show selected guide
            document.getElementById('guide-' + role).classList.add('active');

            // Update buttons
            document.querySelectorAll('.role-btn').forEach(btn => {
                btn.classList.remove('active');
            });
            event.currentTarget.classList.add('active');

            // Scroll to top of content
            window.scrollTo({
                top: document.querySelector('main').offsetTop - 120,
                behavior: 'smooth'
            });
        }

        function searchGuide(query) {
            const q = query.toLowerCase();
            const sections = document.querySelectorAll('.guide-section');
            const status = document.getElementById('search-status');
            let totalVisibleCount = 0;

            if (q === "") {
                // Jika input kosong, kembalikan ke kategori yang aktif
                const activeBtn = document.querySelector('.role-btn.active');
                // Mengambil parameter role dari atribut onclick (e.g. showGuide('mhs'))
                const match = activeBtn ? activeBtn.getAttribute('onclick').match(/'([^']+)'/) : null;
                const activeRole = match ? match[1] : 'mhs';

                sections.forEach(section => {
                    if (section.id === 'guide-' + activeRole) {
                        section.classList.add('active');
                    } else {
                        section.classList.remove('active');
                    }
                    // Reset visibilitas seluruh card di dalam section
                    section.querySelectorAll('.step-card').forEach(card => card.style.display = "block");
                });
                status.classList.add('hidden');
                return;
            }

            // Pencarian global di seluruh kategori
            sections.forEach(section => {
                const cards = section.querySelectorAll('.step-card');
                const h2 = section.querySelector('h2').innerText.toLowerCase();
                let sectionVisibleCount = 0;

                cards.forEach(card => {
                    const h3 = card.querySelector('h3').innerText.toLowerCase();
                    const text = card.innerText.toLowerCase();
                    
                    // Cek apakan query ada di judul section, judul langkah, atau isi konten
                    if (h2.includes(q) || h3.includes(q) || text.includes(q)) {
                        card.style.display = "block";
                        card.style.animation = "fadeIn 0.3s ease-out forwards";
                        sectionVisibleCount++;
                    } else {
                        card.style.display = "none";
                    }
                });

                // Tampilkan section jika ada card yang cocok di dalamnya
                if (sectionVisibleCount > 0) {
                    section.classList.add('active');
                    totalVisibleCount += sectionVisibleCount;
                } else {
                    section.classList.remove('active');
                }
            });

            // Tampilkan pesan jika tidak ada hasil sama sekali
            if (totalVisibleCount === 0) {
                status.classList.remove('hidden');
            } else {
                status.classList.add('hidden');
            }
        }
    </script>
</body>

</html>
