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
    <title>Panduan Penggunaan - PTIK C 2024</title>
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
        }

        .bg-mesh {
            background-color: #ffffff;
            background-image:
                radial-gradient(at 0% 0%, rgba(79, 70, 229, 0.05) 0px, transparent 50%),
                radial-gradient(at 100% 0%, rgba(6, 182, 212, 0.05) 0px, transparent 50%),
                radial-gradient(at 100% 100%, rgba(79, 70, 229, 0.05) 0px, transparent 50%),
                radial-gradient(at 0% 100%, rgba(79, 70, 229, 0.05) 0px, transparent 50%);
        }

        .role-btn {
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .role-btn.active {
            background: #4f46e5;
            color: white;
            transform: scale(1.05);
            box-shadow: 0 10px 25px -5px rgba(79, 70, 229, 0.4);
        }

        .step-badge {
            width: 28px;
            height: 28px;
            background: #4f46e5;
            color: white;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 13px;
            font-weight: 800;
            flex-shrink: 0;
            box-shadow: 0 4px 10px rgba(79, 70, 229, 0.2);
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

        .step-card:hover {
            box-shadow: 0 20px 40px -15px rgba(0,0,0,0.05);
            transform: translateY(-3px);
            border-color: #e2e8f0;
        }

        .step-card::before {
            content: '';
            position: absolute;
            left: 29px;
            top: 50px;
            bottom: -30px;
            width: 2px;
            background: #f1f5f9;
            z-index: 0;
        }

        .step-card:last-child::before {
            display: none;
        }

        .tip-box {
            background: linear-gradient(135deg, #eff6ff 0%, #dbeafe 100%);
            border-left: 4px solid #3b82f6;
            padding: 1.25rem;
            border-radius: 1rem;
            margin-top: 1.5rem;
        }
    </style>
</head>

<body class="bg-mesh min-h-screen">
    <?php include 'includes/header_main.php'; ?>

    <!-- Spacer -->
    <div class="h-10"></div>

    <main class="flex-grow py-12">
        <div class="container mx-auto px-6 pb-24">
            <h1 class="text-3xl font-bold mb-2 text-gray-800 scroll-reveal text-center">Panduan Penggunaan</h1>
            <p class="text-gray-500 mb-8 scroll-reveal text-sm text-center max-w-2xl mx-auto">Pelajari cara pengelolaan tugas, absensi, hingga pengaturan profil untuk memaksimalkan penggunaan sistem informasi.</p>
            <div class="flex flex-wrap justify-center gap-3 mb-12">
                <button onclick="showGuide('mhs')" class="role-btn active glass-card px-6 py-4 rounded-2xl flex items-center gap-3 text-xs font-bold text-slate-600 hover:bg-white transition-all shadow-sm">
                    <i class="fa fa-user-graduate text-lg"></i> Mahasiswa
                </button>
                <button onclick="showGuide('keti')" class="role-btn glass-card px-6 py-4 rounded-2xl flex items-center gap-3 text-xs font-bold text-slate-600 hover:bg-white transition-all shadow-sm">
                    <i class="fa fa-crown text-lg"></i> Ketua Tingkat
                </button>
                <button onclick="showGuide('sekretaris')" class="role-btn glass-card px-6 py-4 rounded-2xl flex items-center gap-3 text-xs font-bold text-slate-600 hover:bg-white transition-all shadow-sm">
                    <i class="fa fa-file-signature text-lg"></i> Sekretaris
                </button>
                <button onclick="showGuide('bendahara')" class="role-btn glass-card px-6 py-4 rounded-2xl flex items-center gap-3 text-xs font-bold text-slate-600 hover:bg-white transition-all shadow-sm">
                    <i class="fa fa-wallet text-lg"></i> Bendahara
                </button>
                <button onclick="showGuide('manager')" class="role-btn glass-card px-6 py-4 rounded-2xl flex items-center gap-3 text-xs font-bold text-slate-600 hover:bg-white transition-all shadow-sm">
                    <i class="fa fa-tasks text-lg"></i> Manager Tugas
                </button>
                <button onclick="showGuide('admin')" class="role-btn glass-card px-6 py-4 rounded-2xl flex items-center gap-3 text-xs font-bold text-slate-600 hover:bg-white transition-all shadow-sm">
                    <i class="fa fa-user-shield text-lg"></i> Administrator
                </button>
            </div>
        
        <!-- PANDUAN MAHASISWA -->
        <div id="guide-mhs" class="guide-section active">
            <div class="glass-card p-8 md:p-16 rounded-[3rem] border border-slate-100 shadow-2xl shadow-slate-200/50">
                <div class="flex items-center gap-5 mb-12">
                    <div class="h-16 w-16 bg-indigo-600 text-white rounded-[1.5rem] flex items-center justify-center text-2xl shadow-xl shadow-indigo-200">
                        <i class="fa fa-user-graduate"></i>
                    </div>
                    <div>
                        <h2 class="text-2xl md:text-3xl font-black text-slate-800 plus-jakarta">Panduan Mahasiswa</h2>
                        <p class="text-[11px] text-indigo-500 font-extrabold uppercase tracking-[0.2em] mt-1">Starting Your Digital Journey</p>
                    </div>
                </div>

                <div class="space-y-12 relative">

                    <!-- Setup Profil -->
                    <div class="step-card group">
                        <div class="flex gap-6 relative z-10">
                            <span class="step-badge">1</span>
                            <div class="flex-1">
                                <h3 class="text-lg font-black text-slate-800 mb-3 plus-jakarta">Persiapan & Setup Profil Awal</h3>
                                <p class="text-[13px] text-slate-500 leading-relaxed mb-4">Langkah pertama yang wajib dilakukan oleh setiap mahasiswa baru sistem:</p>
                                <ul class="space-y-3">
                                    <li class="flex items-start gap-2 text-[13px] text-slate-600">
                                        <i class="fa fa-check-circle text-indigo-500 mt-0.5"></i>
                                        <span><strong>Login Pertama:</strong> Masuk menggunakan NIM sebagai Username dan Password default (Hubungi Administrator Utama jika tidak tahu).</span>
                                    </li>
                                    <li class="flex items-start gap-2 text-[13px] text-slate-600">
                                        <i class="fa fa-check-circle text-indigo-500 mt-0.5"></i>
                                        <span><strong>Ubah Password:</strong> Masuk ke menu <strong>Profil</strong> > <strong>Edit Profil</strong>. Di halaman inilah Anda dapat mengupdate password baru demi keamanan akun.</span>
                                    </li>
                                    <li class="flex items-start gap-2 text-[13px] text-slate-600">
                                        <i class="fa fa-key text-indigo-500 mt-0.5"></i>
                                        <div class="space-y-1">
                                            <span><strong>Simpan Akun Manual:</strong> Karena pop-up penyimpanan otomatis tidak muncul, ikuti langkah manual ini:</span>
                                            <ol class="list-decimal list-inside pl-2 space-y-1 opacity-80 decoration-indigo-200">
                                                <li>Buka <strong>Settings</strong> di Chrome/Browser Anda.</li>
                                                <li>Pilih menu <strong>Google Password Manager</strong>.</li>
                                                <li>Klik tombol <strong>+ Add</strong> atau <strong>Tambah</strong>.</li>
                                                <li>Masukkan URL: <code>ptikc24.fwh.is</code></li>
                                                <li>Masukkan Username: <code>NIM Anda</code></li>
                                                <li>Masukkan Password yang baru saja Anda buat.</li>
                                            </ol>
                                        </div>
                                    </li>
                                    <li class="flex items-start gap-2 text-[13px] text-slate-600">
                                        <i class="fa fa-check-circle text-indigo-500 mt-0.5"></i>
                                        <span><strong>Unggah Foto:</strong> Pilih menu <strong>Update Profil</strong>, klik area foto, dan unggah foto terbaik Anda (Maks 2MB).</span>
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div>

                    <!-- Monitoring Kas -->
                    <div class="step-card group">
                        <div class="flex gap-6 relative z-10">
                            <span class="step-badge">2</span>
                            <div class="flex-1">
                                <h3 class="text-lg font-black text-slate-800 mb-3 plus-jakarta">Memantau Kewajiban Keuangan (Kas)</h3>
                                <p class="text-[13px] text-slate-500 leading-relaxed mb-4">Agar tidak ada tunggakan yang menumpuk di akhir semester:</p>
                                <ul class="space-y-3">
                                    <li class="flex items-start gap-2 text-[13px] text-slate-600">
                                        <i class="fa fa-check-circle text-indigo-500 mt-0.5"></i>
                                        <span>Buka menu <strong>Kas Saya</strong> dari Sidebar.</span>
                                    </li>
                                    <li class="flex items-start gap-2 text-[13px] text-slate-600">
                                        <i class="fa fa-check-circle text-indigo-500 mt-0.5"></i>
                                        <span>Cek tab <strong>Status Pembayaran</strong> untuk melihat baris berwarna merah (Tunggakan).</span>
                                    </li>
                                    <li class="flex items-start gap-2 text-[13px] text-slate-600">
                                        <i class="fa fa-check-circle text-indigo-500 mt-0.5"></i>
                                        <span>Segera lakukan pembayaran ke Bendahara dan pastikan data Anda diupdate di sistem.</span>
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div>

                    <!-- Memantau Absensi -->
                    <div class="step-card group">
                        <div class="flex gap-6 relative z-10">
                            <span class="step-badge">3</span>
                            <div class="flex-1">
                                <h3 class="text-lg font-black text-slate-800 mb-3 plus-jakarta">Monitoring Absensi & Kehadiran</h3>
                                <p class="text-[13px] text-slate-500 leading-relaxed mb-4">Pastikan record kehadiran Anda selalu akurat:</p>
                                <ul class="space-y-3">
                                    <li class="flex items-start gap-2 text-[13px] text-slate-600">
                                        <i class="fa fa-check-circle text-indigo-500 mt-0.5"></i>
                                        <span>Buka menu <strong>Absensi</strong> > <strong>Riwayat Saya</strong>.</span>
                                    </li>
                                    <li class="flex items-start gap-2 text-[13px] text-slate-600">
                                        <i class="fa fa-check-circle text-indigo-500 mt-0.5"></i>
                                        <span>Pantau persentase kehadiran Anda (Pastikan tetap di atas 80% untuk syarat ujian).</span>
                                    </li>
                                    <li class="flex items-start gap-2 text-[13px] text-slate-600">
                                        <i class="fa fa-check-circle text-indigo-500 mt-0.5"></i>
                                        <span>Lakukan klaim ke Sekretaris jika ada ketidaksesuaian data absen.</span>
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div>

                    <!-- Melacak Tugas -->
                    <div class="step-card group">
                        <div class="flex gap-6 relative z-10">
                            <span class="step-badge">4</span>
                            <div class="flex-1">
                                <h3 class="text-lg font-black text-slate-800 mb-3 plus-jakarta">Melacak Daftar Tugas</h3>
                                <p class="text-[13px] text-slate-500 leading-relaxed mb-4">Pusat informasi akademik harian Anda:</p>
                                <ul class="space-y-3">
                                    <li class="flex items-start gap-2 text-[13px] text-slate-600">
                                        <i class="fa fa-check-circle text-indigo-500 mt-0.5"></i>
                                        <span>Lihat <strong>Dashboard Utama</strong> untuk deadline tugas terdekat.</span>
                                    </li>
                                    <li class="flex items-start gap-2 text-[13px] text-slate-600">
                                        <i class="fa fa-check-circle text-indigo-500 mt-0.5"></i>
                                        <span>Klik <strong>Detail Tugas</strong> untuk melihat instruksi lengkap dan file referensi.</span>
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div>

                    <!-- Mengikuti Kuis -->
                    <div class="step-card group">
                        <div class="flex gap-6 relative z-10">
                            <span class="step-badge">5</span>
                            <div class="flex-1">
                                <h3 class="text-lg font-black text-slate-800 mb-3 plus-jakarta">Mengikuti Kuis Kelas</h3>
                                <p class="text-[13px] text-slate-500 leading-relaxed mb-4">Asah kemampuan Anda sebelum ujian resmi:</p>
                                <ul class="space-y-3">
                                    <li class="flex items-start gap-2 text-[13px] text-slate-600">
                                        <i class="fa fa-check-circle text-indigo-500 mt-0.5"></i>
                                        <span>Buka menu <strong>Kuis Kelas</strong> untuk melihat kuis yang aktif.</span>
                                    </li>
                                    <li class="flex items-start gap-2 text-[13px] text-slate-600">
                                        <i class="fa fa-check-circle text-indigo-500 mt-0.5"></i>
                                        <span>Ikuti kuis dan lihat skor Anda secara instan setelah selesai.</span>
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div>

                    <!-- Piket Proyektor -->
                    <div class="step-card group">
                        <div class="flex gap-6 relative z-10">
                            <span class="step-badge">6</span>
                            <div class="flex-1">
                                <h3 class="text-lg font-black text-slate-800 mb-3 plus-jakarta">Jadwal Piket Proyektor</h3>
                                <p class="text-[13px] text-slate-500 leading-relaxed mb-4">Kontribusi rutin untuk kelancaran perkuliahan:</p>
                                <ul class="space-y-3">
                                    <li class="flex items-start gap-2 text-[13px] text-slate-600">
                                        <i class="fa fa-check-circle text-indigo-500 mt-0.5"></i>
                                        <span>Cek menu <strong>Piket Proyektor</strong> untuk melihat jadwal mingguan.</span>
                                    </li>
                                    <li class="flex items-start gap-2 text-[13px] text-slate-600">
                                        <i class="fa fa-check-circle text-indigo-500 mt-0.5"></i>
                                        <span>Jika jadwal Anda tiba, Anda bertanggung jawab mengambil proyektor di Lab dan mengembalikannya setelah kuliah selesai.</span>
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div>

                    <!-- Vibe Kelas -->
                    <div class="step-card group">
                        <div class="flex gap-6 relative z-10">
                            <span class="step-badge">7</span>
                            <div class="flex-1">
                                <h3 class="text-lg font-black text-slate-800 mb-3 plus-jakarta">Mengatur 'Vibe' Kelas</h3>
                                <p class="text-[13px] text-slate-500 leading-relaxed mb-4">Ekspresikan perasaan Anda hari ini untuk dikumpulkan sebagai statistik kelas:</p>
                                <ul class="space-y-3">
                                    <li class="flex items-start gap-2 text-[13px] text-slate-600">
                                        <i class="fa fa-check-circle text-indigo-500 mt-0.5"></i>
                                        <span>Cari widget <strong>'How's the Vibe Today?'</strong> di Dashboard atau Sidebar.</span>
                                    </li>
                                    <li class="flex items-start gap-2 text-[13px] text-slate-600">
                                        <i class="fa fa-check-circle text-indigo-500 mt-0.5"></i>
                                        <span>Pilih salah satu emoji yang paling mewakili perasaan Anda (Senang, Capek, Semangat, dll).</span>
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div>

                    <!-- Liat Mahasiswa & Dosen -->
                    <div class="step-card group">
                        <div class="flex gap-6 relative z-10">
                            <span class="step-badge">8</span>
                            <div class="flex-1">
                                <h3 class="text-lg font-black text-slate-800 mb-3 plus-jakarta">Daftar Mahasiswa & Dosen</h3>
                                <p class="text-[13px] text-slate-500 leading-relaxed mb-4">Mengenal lebih dekat seluruh personil kelas dan pengajar:</p>
                                <ul class="space-y-3">
                                    <li class="flex items-start gap-2 text-[13px] text-slate-600">
                                        <i class="fa fa-check-circle text-indigo-500 mt-0.5"></i>
                                        <span>Buka menu <strong>Database</strong> > <strong>Mahasiswa</strong> untuk melihat daftar teman sekelas, sosial media, dan keahlian mereka.</span>
                                    </li>
                                    <li class="flex items-start gap-2 text-[13px] text-slate-600">
                                        <i class="fa fa-check-circle text-indigo-500 mt-0.5"></i>
                                        <span>Buka menu <strong>Dosen</strong> untuk melihat daftar dosen pengampu mata kuliah beserta informasi kontak yang tersedia.</span>
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div>

                    <!-- Mata Kuliah & Jadwal -->
                    <div class="step-card group">
                        <div class="flex gap-6 relative z-10">
                            <span class="step-badge">9</span>
                            <div class="flex-1">
                                <h3 class="text-lg font-black text-slate-800 mb-3 plus-jakarta">Mata Kuliah & Jadwal Perkuliahan</h3>
                                <p class="text-[13px] text-slate-500 leading-relaxed mb-4">Manajemen waktu kuliah yang lebih disiplin:</p>
                                <ul class="space-y-3">
                                    <li class="flex items-start gap-2 text-[13px] text-slate-600">
                                        <i class="fa fa-check-circle text-indigo-500 mt-0.5"></i>
                                        <span>Pilih menu <strong>Mata Kuliah</strong> untuk melihat daftar SKS dan deskripsi singkat setiap pembelajaran.</span>
                                    </li>
                                    <li class="flex items-start gap-2 text-[13px] text-slate-600">
                                        <i class="fa fa-check-circle text-indigo-500 mt-0.5"></i>
                                        <span>Buka <strong>Jadwal Kuliah</strong> untuk melihat jam masuk, ruang kelas, dan link grup WhatsApp mata kuliah (jika tersedia).</span>
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div>

                    <!-- Berita & Info -->
                    <div class="step-card group">
                        <div class="flex gap-6 relative z-10">
                            <span class="step-badge">10</span>
                            <div class="flex-1">
                                <h3 class="text-lg font-black text-slate-800 mb-3 plus-jakarta">Berita & Informasi Kelas</h3>
                                <p class="text-[13px] text-slate-500 leading-relaxed mb-4">Pusat kabar terbaru dari internal ProTechNoTy:</p>
                                <ul class="space-y-3">
                                    <li class="flex items-start gap-2 text-[13px] text-slate-600">
                                        <i class="fa fa-check-circle text-indigo-500 mt-0.5"></i>
                                        <span>Akses menu <strong>Berita</strong> untuk membaca artikel, ringkasan rapat kelas, atau berita luar kampus yang relevan.</span>
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div>

                    <!-- Kalender Akademik -->
                    <div class="step-card group">
                        <div class="flex gap-6 relative z-10">
                            <span class="step-badge">11</span>
                            <div class="flex-1">
                                <h3 class="text-lg font-black text-slate-800 mb-3 plus-jakarta">Kalender Akademik & Agenda</h3>
                                <p class="text-[13px] text-slate-500 leading-relaxed mb-4">Jangan lewatkan tanggal-tanggal penting:</p>
                                <ul class="space-y-3">
                                    <li class="flex items-start gap-2 text-[13px] text-slate-600">
                                        <i class="fa fa-check-circle text-indigo-500 mt-0.5"></i>
                                        <span>Buka menu <strong>Kalender</strong> untuk melihat jadwal UTS, UAS, libur semester, dan agenda penting kelas lainnya.</span>
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div>

                    <!-- Galeri Kelas -->
                    <div class="step-card group">
                        <div class="flex gap-6 relative z-10">
                            <span class="step-badge">12</span>
                            <div class="flex-1">
                                <h3 class="text-lg font-black text-slate-800 mb-3 plus-jakarta">Galeri Memori Kelas</h3>
                                <p class="text-[13px] text-slate-500 leading-relaxed mb-4">Menelusuri jejak dokumentasi kebersamaan:</p>
                                <ul class="space-y-3">
                                    <li class="flex items-start gap-2 text-[13px] text-slate-600">
                                        <i class="fa fa-check-circle text-indigo-500 mt-0.5"></i>
                                        <span>Masuk ke menu <strong>Galeri</strong> untuk melihat album foto dokumentasi kegiatan yang diunggah Sekretaris.</span>
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div>

                    <!-- Pembagian Kelompok -->
                    <div class="step-card group">
                        <div class="flex gap-6 relative z-10">
                            <span class="step-badge">13</span>
                            <div class="flex-1">
                                <h3 class="text-lg font-black text-slate-800 mb-3 plus-jakarta">Manajemen Kelompok Belajar</h3>
                                <p class="text-[13px] text-slate-500 leading-relaxed mb-4">Cek rekan tim Anda untuk setiap tugas besar:</p>
                                <ul class="space-y-3">
                                    <li class="flex items-start gap-2 text-[13px] text-slate-600">
                                        <i class="fa fa-check-circle text-indigo-500 mt-0.5"></i>
                                        <span>Pilih menu <strong>Kelompok</strong>, cari mata kuliahnya, dan lihat di kelompok mana nama Anda terdaftar.</span>
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div>

                    <!-- Pusat Misi -->
                    <div class="step-card group">
                        <div class="flex gap-6 relative z-10">
                            <span class="step-badge">14</span>
                            <div class="flex-1">
                                <h3 class="text-lg font-black text-slate-800 mb-3 plus-jakarta">Mission Center (Pusat Misi)</h3>
                                <p class="text-[13px] text-slate-500 leading-relaxed mb-4">Selesaikan tantangan untuk mendapatkan poin atau pencapaian:</p>
                                <ul class="space-y-3">
                                    <li class="flex items-start gap-2 text-[13px] text-slate-600">
                                        <i class="fa fa-check-circle text-indigo-500 mt-0.5"></i>
                                        <span>Buka <strong>Pusat Misi</strong> untuk melihat daftar misi harian atau mingguan yang diberikan sistem/pengurus.</span>
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div>

                    <!-- Pesan Anonim -->
                    <div class="step-card group">
                        <div class="flex gap-6 relative z-10">
                            <span class="step-badge">15</span>
                            <div class="flex-1">
                                <h3 class="text-lg font-black text-slate-800 mb-3 plus-jakarta">Anonymous Message (Pesan Anonim)</h3>
                                <p class="text-[13px] text-slate-500 leading-relaxed mb-4">Curhat atau kirim kritik/saran tanpa diketahui identitasnya:</p>
                                <ul class="space-y-3">
                                    <li class="flex items-start gap-2 text-[13px] text-slate-600">
                                        <i class="fa fa-check-circle text-indigo-500 mt-0.5"></i>
                                        <span>Gunakan fitur <strong>Pesan Anonim</strong> untuk mengirim pesan rahasia ke teman sekelas atau pengurus secara aman.</span>
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div>

                    <!-- Random Picker -->
                    <div class="step-card group">
                        <div class="flex gap-6 relative z-10">
                            <span class="step-badge">16</span>
                            <div class="flex-1">
                                <h3 class="text-lg font-black text-slate-800 mb-3 plus-jakarta">Random Student Picker</h3>
                                <p class="text-[13px] text-slate-500 leading-relaxed mb-4">Alat bantu pemilihan nama yang adil:</p>
                                <ul class="space-y-3">
                                    <li class="flex items-start gap-2 text-[13px] text-slate-600">
                                        <i class="fa fa-check-circle text-indigo-500 mt-0.5"></i>
                                        <span>Gunakan <strong>Random Picker</strong> di menu Alat (Tools) jika bosan dengan urutan absen saat presentasi atau piket.</span>
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div>

                    <!-- KRS & KSM -->
                    <div class="step-card group">
                        <div class="flex gap-6 relative z-10">
                            <span class="step-badge">17</span>
                            <div class="flex-1">
                                <h3 class="text-lg font-black text-slate-800 mb-3 plus-jakarta">Manajemen Dokumen (KRS & KSM)</h3>
                                <p class="text-[13px] text-slate-500 leading-relaxed mb-4">Arsip mandiri untuk dokumen penting universitas:</p>
                                <ul class="space-y-3">
                                    <li class="flex items-start gap-2 text-[13px] text-slate-600">
                                        <i class="fa fa-check-circle text-indigo-500 mt-0.5"></i>
                                        <span>Unggah PDF atau Screenshot <strong>KRS</strong> dan <strong>KSM</strong> Anda di menu Dokumen sebagai cadangan jika sistem kampus mengalami gangguan.</span>
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
            <div class="glass-card p-8 md:p-16 rounded-[3rem] border border-slate-100 shadow-2xl shadow-slate-200/50">
                <div class="flex items-center gap-5 mb-12">
                    <div class="h-16 w-16 bg-amber-500 text-white rounded-[1.5rem] flex items-center justify-center text-2xl shadow-xl shadow-amber-200">
                        <i class="fa fa-crown"></i>
                    </div>
                    <div>
                        <h2 class="text-2xl md:text-3xl font-black text-slate-800 plus-jakarta">Ketua Tingkat (Keti)</h2>
                        <p class="text-[11px] text-amber-600 font-extrabold uppercase tracking-[0.2em] mt-1">Decision Making & Monitoring</p>
                    </div>
                </div>

                <div class="space-y-12">
                    <!-- Pembagian Kelompok -->
                    <div class="step-card">
                        <div class="flex gap-6 relative z-10">
                            <span class="step-badge !bg-amber-500">1</span>
                            <div class="flex-1">
                                <h3 class="text-lg font-black text-slate-800 mb-3 plus-jakarta">Manajemen Pembagian Kelompok</h3>
                                <p class="text-[13px] text-slate-500 leading-relaxed mb-4">Mengatur distribusi anggota untuk tugas besar atau project:</p>
                                <ol class="list-decimal list-inside space-y-2 text-[13px] text-slate-500">
                                    <li>Buka menu <strong>Kelola Kelompok</strong>.</li>
                                    <li>Pilih Mata Kuliah yang memerlukan pembagian tim.</li>
                                    <li>Gunakan fitur <strong>Auto-Generate</strong> untuk pembagian acak, atau <strong>Custom dragging</strong> untuk menyusun anggota secara manual.</li>
                                    <li>Klik <strong>Simpan & Publikasikan</strong> agar mahasiswa dapat melihat kelompok mereka masing-masing.</li>
                                </ol>
                            </div>
                        </div>
                    </div>

                    <!-- Reminder WhatsApp -->
                    <div class="step-card">
                        <div class="flex gap-6 relative z-10">
                            <span class="step-badge !bg-amber-500">2</span>
                            <div class="flex-1">
                                <h3 class="text-lg font-black text-slate-800 mb-3 plus-jakarta">Mengirim Pengingat (Reminder) ke WhatsApp</h3>
                                <p class="text-[13px] text-slate-500 leading-relaxed mb-4">Memastikan tidak ada mahasiswa yang ketinggalan informasi krusial:</p>
                                <ul class="space-y-3">
                                    <li class="flex items-start gap-2 text-[13px] text-slate-600">
                                        <i class="fa fa-paper-plane text-amber-500 mt-0.5"></i>
                                        <span>Cari menu <strong>WhatsApp Bomber/Reminder</strong> di panel Keti.</span>
                                    </li>
                                    <li class="flex items-start gap-2 text-[13px] text-slate-600">
                                        <i class="fa fa-paper-plane text-amber-500 mt-0.5"></i>
                                        <span>Pilih kategori pengingat (Tugas Mendatang / Belum Bayar Kas / Absensi Rendah).</span>
                                    </li>
                                    <li class="flex items-start gap-2 text-[13px] text-slate-600">
                                        <i class="fa fa-paper-plane text-amber-500 mt-0.5"></i>
                                        <span>Klik tombol <strong>Kirim ke Grup/Personal</strong>. Sistem akan mengarahkan ke API WhatsApp untuk mengirimkan pesan pengingat otomatis.</span>
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
            <div class="glass-card p-8 md:p-16 rounded-[3rem] border border-slate-100 shadow-2xl shadow-slate-200/50">
                <div class="flex items-center gap-5 mb-12">
                    <div class="h-16 w-16 bg-cyan-500 text-white rounded-[1.5rem] flex items-center justify-center text-2xl shadow-xl shadow-cyan-200">
                        <i class="fa fa-file-signature"></i>
                    </div>
                    <div>
                        <h2 class="text-2xl md:text-3xl font-black text-slate-800 plus-jakarta">Peran Sekretaris</h2>
                        <p class="text-[11px] text-cyan-600 font-extrabold uppercase tracking-[0.2em] mt-1">Daily Administrative Ops</p>
                    </div>
                </div>

                <div class="space-y-12">
                    <!-- Kelola Absensi -->
                    <div class="step-card">
                        <div class="flex gap-6 relative z-10">
                            <span class="step-badge !bg-cyan-500">1</span>
                            <div class="flex-1">
                                <h3 class="text-lg font-black text-slate-800 mb-3 plus-jakarta">Langkah Membuka Absensi Digital</h3>
                                <p class="text-[13px] text-slate-600 leading-relaxed mb-4">Lakukan ini setiap kali mata kuliah dimulai:</p>
                                <ol class="list-decimal list-inside space-y-2 text-[13px] text-slate-500">
                                    <li>Buka menu <strong>Manajemen Absensi</strong>.</li>
                                    <li>Klik tombol <strong>Buka Sesi Baru</strong>.</li>
                                    <li>Pilih Mata Kuliah dan Dosen Pengajar.</li>
                                    <li>Atur durasi absensi (Misal: 15 Menit).</li>
                                    <li>Klik <strong>Simpan</strong>. Sistem akan membuka sesi absen dan <strong>secara otomatis mengirimkan pesan pengumuman ke Grup WhatsApp</strong> kelas.</li>
                                </ol>
                            </div>
                        </div>
                    </div>

                    <!-- Membuat Tugas -->
                    <div class="step-card">
                        <div class="flex gap-6 relative z-10">
                            <span class="step-badge !bg-cyan-500">2</span>
                            <div class="flex-1">
                                <h3 class="text-lg font-black text-slate-800 mb-3 plus-jakarta">Membuat & Mengelola Tugas Kuliah</h3>
                                <p class="text-[13px] text-slate-600 leading-relaxed mb-4">Mendata tugas dari dosen agar terdokumentasi dengan baik:</p>
                                <ol class="list-decimal list-inside space-y-2 text-[13px] text-slate-500">
                                    <li>Masuk ke menu <strong>Manajemen Tugas</strong>.</li>
                                    <li>Klik tombol <strong>Tambah Tugas</strong>.</li>
                                    <li>Pilih Mata Kuliah, isi Judul Tugas, dan Deskripsi instruksi dari dosen.</li>
                                    <li>Tentukan <strong>Deadline</strong> (Tanggal & Waktu pengumpulan).</li>
                                    <li>Klik <strong>Publish</strong> untuk memunculkan tugas di dashboard seluruh mahasiswa.</li>
                                </ol>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- PANDUAN BENDAHARA -->
        <div id="guide-bendahara" class="guide-section">
            <div class="glass-card p-8 md:p-16 rounded-[3rem] border border-slate-100 shadow-2xl shadow-slate-200/50">
                <div class="flex items-center gap-5 mb-12">
                    <div class="h-16 w-16 bg-emerald-500 text-white rounded-[1.5rem] flex items-center justify-center text-2xl shadow-xl shadow-emerald-200">
                        <i class="fa fa-wallet"></i>
                    </div>
                    <div>
                        <h2 class="text-2xl md:text-3xl font-black text-slate-800 plus-jakarta">Peran Bendahara</h2>
                        <p class="text-[11px] text-emerald-600 font-extrabold uppercase tracking-[0.2em] mt-1">Financial Data Entry</p>
                    </div>
                </div>

                <div class="space-y-12">
                    <!-- Tambah Kas Perminggu -->
                    <div class="step-card">
                        <div class="flex gap-6 relative z-10">
                            <span class="step-badge !bg-emerald-500">1</span>
                            <div class="flex-1">
                                <h3 class="text-lg font-black text-slate-800 mb-3 plus-jakarta">Tambah Kas Kolektif (Per Minggu)</h3>
                                <p class="text-[13px] text-slate-500 leading-relaxed mb-4">Fitur cepat untuk menginput pembayaran seluruh kelas dalam satu minggu:</p>
                                <ol class="list-decimal list-inside space-y-2 text-[13px] text-slate-500">
                                    <li>Buka menu <strong>Manajemen Kas</strong> > <strong>Tambah Kas Mingguan</strong>.</li>
                                    <li>Pilih Periode/Minggu yang ingin diisi.</li>
                                    <li>Centang nama-nama mahasiswa yang sudah membayar.</li>
                                    <li>Klik <strong>Simpan Kolektif</strong>. Saldo kelas akan bertambah sesuai jumlah mahasiswa yang membayar.</li>
                                </ol>
                            </div>
                        </div>
                    </div>

                    <!-- Tambah Kas Per Individu -->
                    <div class="step-card">
                        <div class="flex gap-6 relative z-10">
                            <span class="step-badge !bg-emerald-500">2</span>
                            <div class="flex-1">
                                <h3 class="text-lg font-black text-slate-800 mb-3 plus-jakarta">Tambah Kas Per Individu</h3>
                                <p class="text-[13px] text-slate-500 leading-relaxed mb-4">Mencatat pembayaran mahasiswa secara spesifik (misal rapel atau cicilan):</p>
                                <ol class="list-decimal list-inside space-y-2 text-[13px] text-slate-500">
                                    <li>Cari nama Mahasiswa di menu <strong>Database Kas</strong>.</li>
                                    <li>Klik tombol <strong>Detail/Bayar</strong> pada baris nama tersebut.</li>
                                    <li>Tentukan jumlah minggu yang dibayar dan nominalnya.</li>
                                    <li>Simpan. Status pembayaran individu tersebut akan otomatis berubah menjadi "Lunas" untuk periode terkait.</li>
                                </ol>
                            </div>
                        </div>
                    </div>

                    <!-- Catat Pemasukan & Pengeluaran -->
                    <div class="step-card">
                        <div class="flex gap-6 relative z-10">
                            <span class="step-badge !bg-emerald-500">3</span>
                            <div class="flex-1">
                                <h3 class="text-lg font-black text-slate-800 mb-3 plus-jakarta">Pencatatan Pemasukan & Pengeluaran Lainnya</h3>
                                <p class="text-[13px] text-slate-500 leading-relaxed mb-4">Mengelola sirkulasi uang selain iuran kas rutin:</p>
                                <ul class="space-y-3">
                                    <li class="flex items-start gap-2 text-[13px] text-slate-600">
                                        <i class="fa fa-plus-circle text-emerald-500 mt-0.5"></i>
                                        <span><strong>Pemasukan:</strong> Gunakan menu <strong>Transaksi Lain</strong> > <strong>Pemasukan</strong> untuk mencatat dana masuk seperti sumbangan atau sisa uang kegiatan.</span>
                                    </li>
                                    <li class="flex items-start gap-2 text-[13px] text-slate-600">
                                        <i class="fa fa-minus-circle text-rose-500 mt-0.5"></i>
                                        <span><strong>Pengeluaran:</strong> Masuk ke tab <strong>Catat Pengeluaran</strong> untuk isi nominal dan keperluan belanja kelas (Misal: Spidol, Konsumsi, dll).</span>
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div>

                    <!-- Reminder Kas WA -->
                    <div class="step-card">
                        <div class="flex gap-6 relative z-10">
                            <span class="step-badge !bg-emerald-500">4</span>
                            <div class="flex-1">
                                <h3 class="text-lg font-black text-slate-800 mb-3 plus-jakarta">Kirim Pengingat Kas ke WhatsApp</h3>
                                <p class="text-[13px] text-slate-500 leading-relaxed mb-4">Menagih tunggakan secara otomatis melalui WhatsApp:</p>
                                <ol class="list-decimal list-inside space-y-2 text-[13px] text-slate-500">
                                    <li>Buka menu <strong>Laporan Kas</strong> > <strong>Tunggakan Mahasiswa</strong>.</li>
                                    <li>Klik tombol <strong>Kirim Pesan ke Grup WA</strong>.</li>
                                    <li>Sistem akan men-generate daftar nama yang belum bayar beserta total tagihannya dan mengirimkannya ke Grup WhatsApp kelas.</li>
                                </ol>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- PANDUAN MANAGER TUGAS -->
        <div id="guide-manager" class="guide-section">
            <div class="glass-card p-8 md:p-16 rounded-[3rem] border border-slate-100 shadow-2xl shadow-slate-200/50">
                <div class="flex items-center gap-5 mb-12">
                    <div class="h-16 w-16 bg-rose-500 text-white rounded-[1.5rem] flex items-center justify-center text-2xl shadow-xl shadow-rose-200">
                        <i class="fa fa-tasks"></i>
                    </div>
                    <div>
                        <h2 class="text-2xl md:text-3xl font-black text-slate-800 plus-jakarta">Peran Manager Tugas</h2>
                        <p class="text-[11px] text-rose-600 font-extrabold uppercase tracking-[0.2em] mt-1">Assignment & Deadline Management</p>
                    </div>
                </div>

                <div class="space-y-12">
                    <div class="step-card">
                        <div class="flex gap-6 relative z-10">
                            <span class="step-badge !bg-rose-500">1</span>
                            <div class="flex-1">
                                <h3 class="text-lg font-black text-slate-800 mb-3 plus-jakarta">Langkah Menambahkan Tugas Baru</h3>
                                <ol class="list-decimal list-inside space-y-2 text-[13px] text-slate-500">
                                    <li>Buka menu <strong>Kelola Tugas</strong>.</li>
                                    <li>Pilih Mata Kuliah yang bersangkutan.</li>
                                    <li>Tulis Judul Tugas dan Deskripsi singkat instruksinya.</li>
                                    <li>Atur <strong>Deadline</strong> (Tanggal & Jam).</li>
                                    <li>Klik <strong>Publish</strong>. Notifikasi akan muncul di dashboard semua orang.</li>
                                </ol>
                            </div>
                        </div>
                    </div>

                    <div class="step-card">
                        <div class="flex gap-6 relative z-10">
                            <span class="step-badge !bg-rose-500">2</span>
                            <div class="flex-1">
                                <h3 class="text-lg font-black text-slate-800 mb-3 plus-jakarta">Melampirkan File Pendukung</h3>
                                <p class="text-[13px] text-slate-500 leading-relaxed italic mb-2">"Sangat membantu untuk teman-teman yang ketinggalan info"</p>
                                <ol class="list-decimal list-inside space-y-2 text-[13px] text-slate-500">
                                    <li>Setelah tugas dibuat, klik tombol <strong>Edit/Lampiran</strong>.</li>
                                    <li>Masukkan Link (Misal dari Google Drive atau Link Blog Dosen).</li>
                                    <li>Simpan. Tombol "Cek Materi" akan muncul di tugas tersebut.</li>
                                </ol>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- PANDUAN ADMINISTRATOR -->
        <div id="guide-admin" class="guide-section">
            <div class="glass-card p-8 md:p-16 rounded-[3rem] border border-slate-100 shadow-2xl shadow-slate-200/50">
                <div class="flex items-center gap-5 mb-12">
                    <div class="h-16 w-16 bg-slate-900 text-white rounded-[1.5rem] flex items-center justify-center text-2xl shadow-xl shadow-slate-300">
                        <i class="fa fa-user-shield"></i>
                    </div>
                    <div>
                        <h2 class="text-2xl md:text-3xl font-black text-slate-800 plus-jakarta">Super Administrator</h2>
                        <p class="text-[11px] text-slate-500 font-extrabold uppercase tracking-[0.2em] mt-1">Core System Maintenance</p>
                    </div>
                </div>

                <div class="space-y-12">
                    <!-- Full Access & Database Control -->
                    <div class="step-card">
                        <div class="flex gap-6 relative z-10">
                            <span class="step-badge !bg-slate-900">1</span>
                            <div class="flex-1">
                                <h3 class="text-lg font-black text-slate-800 mb-3 plus-jakarta">Full Access & Database Control</h3>
                                <p class="text-[13px] text-slate-500 leading-relaxed mb-4">Administrator menyatukan seluruh fungsi role sebelumnya (Mhs, Keti, Sekre, Bendahara) dan memiliki wewenang penuh untuk:</p>
                                <ul class="space-y-3">
                                    <li class="flex items-start gap-2 text-[13px] text-slate-600">
                                        <i class="fa fa-database text-slate-900 mt-0.5"></i>
                                        <span><strong>Edit Database:</strong> Mengubah, menghapus, atau memperbaiki data salah (Nama, NIM, Saldo Kas, Kehadiran) melalui menu <strong>Master Data</strong>.</span>
                                    </li>
                                    <li class="flex items-start gap-2 text-[13px] text-slate-600">
                                        <i class="fa fa-user-gear text-slate-900 mt-0.5"></i>
                                        <span><strong>User Role Management:</strong> Menunjuk atau mencopot jabatan pengurus (Role) di sistem.</span>
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div>

                    <!-- Manajemen Kuis & Gift -->
                    <div class="step-card">
                        <div class="flex gap-6 relative z-10">
                            <span class="step-badge !bg-slate-900">2</span>
                            <div class="flex-1">
                                <h3 class="text-lg font-black text-slate-800 mb-3 plus-jakarta">Manajemen Kuis & Reward System</h3>
                                <p class="text-[13px] text-slate-500 leading-relaxed mb-4">Mengelola konten edukasi dan apresiasi pengguna:</p>
                                <ol class="list-decimal list-inside space-y-2 text-[13px] text-slate-500">
                                    <li>Buka menu <strong>Quiz Management</strong> > <strong>Add Questions</strong> untuk menambah soal kuis baru (teks/gambar).</li>
                                    <li>Gunakan fitur <strong>Gift Coins/Points</strong> untuk memberikan hadiah manual kepada mahasiswa yang berprestasi atau aktif dalam kuis.</li>
                                    <li>Atur jadwal kuis agar aktif pada jam-jam tertentu saja.</li>
                                </ol>
                            </div>
                        </div>
                    </div>

                    <!-- Validasi KRS & KSM -->
                    <div class="step-card">
                        <div class="flex gap-6 relative z-10">
                            <span class="step-badge !bg-slate-900">3</span>
                            <div class="flex-1">
                                <h3 class="text-lg font-black text-slate-800 mb-3 plus-jakarta">Validasi Berkas (KRS & KSM)</h3>
                                <p class="text-[13px] text-slate-500 leading-relaxed mb-4">Memetakan dan memverifikasi dokumen akademik mahasiswa:</p>
                                <ul class="space-y-3">
                                    <li class="flex items-start gap-2 text-[13px] text-slate-600">
                                        <i class="fa fa-file-circle-check text-slate-900 mt-0.5"></i>
                                        <span>Masuk ke menu <strong>Pusat Dokumen</strong> > <strong>Validasi KRS</strong>.</span>
                                    </li>
                                    <li class="flex items-start gap-2 text-[13px] text-slate-600">
                                        <i class="fa fa-file-circle-check text-slate-900 mt-0.5"></i>
                                        <span>Periksa file yang diunggah mahasiswa, jika benar, klik <strong>Verifikasi</strong> agar muncul tanda centang hijau di profil mereka.</span>
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div>

                    <!-- Global WhatsApp Notification -->
                    <div class="step-card">
                        <div class="flex gap-6 relative z-10">
                            <span class="step-badge !bg-slate-900">4</span>
                            <div class="flex-1">
                                <h3 class="text-lg font-black text-slate-800 mb-3 plus-jakarta">Notifikasi Global ke Grup WhatsApp</h3>
                                <p class="text-[13px] text-slate-500 leading-relaxed mb-4">Mirip dengan Ketua Tingkat, Admin dapat melakukan siaran langsung:</p>
                                <ul class="space-y-3">
                                    <li class="flex items-start gap-2 text-[13px] text-slate-600">
                                        <i class="fa fa-comment-sms text-slate-900 mt-0.5"></i>
                                        <span>Gunakan menu <strong>System Broadcaster (WA)</strong> untuk mengirim agenda semester atau pengumuman maintenance sistem ke Grup WhatsApp utama kelas secara otomatis.</span>
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div>

                    <!-- System Maintenance -->
                    <div class="step-card">
                        <div class="flex gap-6 relative z-10">
                            <span class="step-badge !bg-slate-900">5</span>
                            <div class="flex-1">
                                <h3 class="text-lg font-black text-slate-800 mb-3 plus-jakarta">System Maintenance & Maintenance Mode</h3>
                                <p class="text-[13px] text-slate-500 leading-relaxed mb-4">Menjaga keberlangsungan sistem informasi:</p>
                                <ul class="space-y-3">
                                    <li class="flex items-start gap-2 text-[13px] text-slate-600">
                                        <i class="fa fa-screwdriver-wrench text-slate-900 mt-0.5"></i>
                                        <span>Kelola <strong>Periode Semester</strong> (Arsip data lama) dan aktifkan <strong>Maintenance Mode</strong> jika sedang ada pengembangan kode agar data tidak bentrok.</span>
                                    </li>
                                </ul>
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
    </script>
</body>

</html>
