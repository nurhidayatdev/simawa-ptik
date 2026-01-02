<?php
session_start();
include '../../koneksi.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../../index.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$nama_lengkap = $_SESSION['nama_lengkap'] ?? '';
$foto = $_SESSION['foto'] ?? '';
$role = $_SESSION['role'];

$q_periode = mysqli_query($koneksi, "SELECT * FROM periode WHERE status = 'aktif' LIMIT 1");
$periode = mysqli_fetch_assoc($q_periode);
$periode_text = $periode ? $periode['tahun_periode'] . ' - ' . $periode['semester_nama'] : 'Tidak ada periode aktif';

?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#1e1b4b" />
    <link rel="icon" href="../img/main/icon.png" />
    <title>Tentang - PTIK C 2024</title>
    <!-- Google tag (gtag.js) -->
    <script async src="https://www.googletagmanager.com/gtag/js?id=G-Q0TXETXV6Y"></script>
    <script>
        window.dataLayer = window.dataLayer || [];

        function gtag() {
            dataLayer.push(arguments);
        }
        gtag('js', new Date());

        gtag('config', 'G-Q0TXETXV6Y');
    </script>
    <link href="../../frontend/tailwind/src/output.css" rel="stylesheet">
    <link href="../../frontend/tailwind/src/input.css" rel="stylesheet">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        body {
            font-family: 'Poppins', sans-serif;
        }

        .glass {
            background: rgba(255, 255, 255, 0.7);
            backdrop-filter: blur(10px);
        }

        .tech-card:hover {
            transform: translateY(-5px);
            transition: all 0.3s ease;
        }
    </style>
</head>

<body class="bg-slate-50 text-slate-800">
    <!-- Header -->
    <header class="bg-indigo-950 fixed w-full top-0 z-50">
        <div class="flex justify-between items-center px-6 py-2">
            <div class="flex items-center">
                <img src="../img/main/logo.png" alt="Logo" class="h-10 w-10 mr-3">
                <span class="text-xl font-bold text-white">Dashboard PTIK C</span>
            </div>
            <div class="flex items-center space-x-4">
                <a href="dashboard.php" class="text-white hover:text-indigo-200 transition-colors text-sm font-medium">
                    <i class="fa fa-arrow-left mr-2"></i> Kembali
                </a>
            </div>
        </div>
    </header>

    <main class="pt-24 pb-12 px-6 max-w-5xl mx-auto">
        <!-- Hero Section -->
        <div class="text-center mb-12">
            <h1 class="text-4xl font-black text-slate-900 mb-4">Tentang <span class="text-indigo-600 italic">Project Ini</span></h1>
            <p class="text-slate-600 max-w-2xl mx-auto leading-relaxed">
                Platform ekosistem digital kelas PTIK C 2024 untuk mempermudah manajemen data, tugas, absensi, dan interaksi antar mahasiswa secara modern dan terpusat.
            </p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
            <!-- Deskripsi Project -->
            <div class="md:col-span-2 space-y-6">
                <section class="bg-white p-6 rounded-2xl shadow-sm border border-slate-200">
                    <h2 class="text-xl font-bold text-slate-800 mb-4 flex items-center gap-2">
                        <i class="fa fa-bullseye text-indigo-600"></i> Visi & Misi
                    </h2>
                    <p class="text-sm text-slate-600 leading-relaxed mb-4">
                        Web ini dirancang khusus untuk memenuhi kebutuhan sirkulasi informasi di dalam kelas PTIK C Angkatan 2024. Proyek ini lahir dari keinginan untuk memiliki sistem yang terintegrasi, transparan, dan menyenangkan bagi seluruh rekan mahasiswa.
                    </p>
                    <ul class="space-y-3">
                        <li class="flex items-start gap-3 text-xs text-slate-600">
                            <i class="fa fa-check-circle text-green-500 mt-1"></i>
                            <span><b>Sentralisasi Data:</b> Semua info tugas, jadwal, dan krs berada dalam satu pintu.</span>
                        </li>
                        <li class="flex items-start gap-3 text-xs text-slate-600">
                            <i class="fa fa-check-circle text-green-500 mt-1"></i>
                            <span><b>Transparansi Keuangan:</b> Laporan kas yang bisa dipantau secara langsung oleh seluruh mahasiswa.</span>
                        </li>
                        <li class="flex items-start gap-3 text-xs text-slate-600">
                            <i class="fa fa-check-circle text-green-500 mt-1"></i>
                            <span><b>Engagement:</b> Fitur Kuis Harian dan Pesan Anonim untuk menjaga kelas tetap hidup.</span>
                        </li>
                    </ul>
                </section>

                <section class="bg-white p-6 rounded-2xl shadow-sm border border-slate-200">
                    <h2 class="text-xl font-bold text-slate-800 mb-6 flex items-center gap-2">
                        <i class="fa fa-code-branch text-indigo-600"></i> Teknologi yang Digunakan
                    </h2>
                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
                        <div class="tech-card bg-slate-50 p-4 rounded-xl text-center border border-slate-100 shadow-sm">
                            <i class="fab fa-php text-3xl text-indigo-500 mb-2"></i>
                            <div class="text-[10px] font-bold uppercase text-slate-400">Backend</div>
                            <div class="text-xs font-bold text-slate-700">PHP 8.x</div>
                        </div>
                        <div class="tech-card bg-slate-50 p-4 rounded-xl text-center border border-slate-100 shadow-sm">
                            <i class="fa fa-database text-3xl text-blue-500 mb-2"></i>
                            <div class="text-[10px] font-bold uppercase text-slate-400">Database</div>
                            <div class="text-xs font-bold text-slate-700">MySQLi</div>
                        </div>
                        <div class="tech-card bg-slate-50 p-4 rounded-xl text-center border border-slate-100 shadow-sm">
                            <img src="https://upload.wikimedia.org/wikipedia/commons/d/d5/Tailwind_CSS_Logo.svg" class="h-8 mx-auto mb-2" alt="Tailwind">
                            <div class="text-[10px] font-bold uppercase text-slate-400">Styling</div>
                            <div class="text-xs font-bold text-slate-700">TailwindCSS</div>
                        </div>
                        <div class="tech-card bg-slate-50 p-4 rounded-xl text-center border border-slate-100 shadow-sm">
                            <i class="fab fa-js text-3xl text-yellow-500 mb-2"></i>
                            <div class="text-[10px] font-bold uppercase text-slate-400">Interactivity</div>
                            <div class="text-xs font-bold text-slate-700">JavaScript</div>
                        </div>
                    </div>
                    <div class="mt-6 pt-6 border-t border-slate-100">
                        <h3 class="text-xs font-bold text-slate-500 uppercase mb-3">Library & Tools Tambahan:</h3>
                        <div class="flex flex-wrap gap-2">
                            <span class="px-2 py-1 bg-indigo-50 text-indigo-600 rounded text-[9px] font-bold">SweetAlert2</span>
                            <span class="px-2 py-1 bg-indigo-50 text-indigo-600 rounded text-[9px] font-bold">Chart.js</span>
                            <span class="px-2 py-1 bg-indigo-50 text-indigo-600 rounded text-[9px] font-bold">FontAwesome 6</span>
                            <span class="px-2 py-1 bg-indigo-50 text-indigo-600 rounded text-[9px] font-bold">Canvas Confetti</span>
                            <span class="px-2 py-1 bg-indigo-50 text-indigo-600 rounded text-[9px] font-bold">Google Fonts</span>
                            <span class="px-2 py-1 bg-indigo-50 text-indigo-600 rounded text-[9px] font-bold">Telegram Bot API</span>
                        </div>
                    </div>
                </section>
            </div>

            <!-- Developer Card -->
            <div class="space-y-6">
                <section class="bg-indigo-600 p-6 rounded-2xl shadow-lg text-white relative overflow-hidden">
                    <div class="absolute -right-4 -bottom-4 opacity-10 text-8xl">
                        <i class="fa fa-code"></i>
                    </div>
                    <h3 class="text-sm font-bold uppercase tracking-widest opacity-80 mb-4">Pengembang</h3>
                    <div class="flex items-center gap-4 mb-4">
                        <div class="h-16 w-16 rounded-xl overflow-hidden border-2 border-white/20 p-2 bg-white/10">
                            <img src="../img/main/logo.png" alt="Dev" class="w-full h-full object-contain">
                        </div>
                        <div>
                            <div class="font-bold text-lg">Admin PTIK C</div>
                            <div class="text-xs opacity-80 italic">Bismillah Fullstack Developer</div>
                        </div>
                    </div>
                    <p class="text-xs leading-relaxed opacity-90">
                        Dikembangkan dengan penuh dedikasi untuk kemajuan kolektif kelas PTIK C 2024.
                    </p>
                    <div class="mt-6 flex gap-3">
                        <a href="https://www.instagram.com/yayaxnr/" class="h-8 w-8 bg-white/10 rounded-lg flex items-center justify-center hover:bg-white/20 transition-colors">
                            <i class="fab fa-instagram"></i>
                        </a>
                        <a href="https://github.com/nurhidayatdev" class="h-8 w-8 bg-white/10 rounded-lg flex items-center justify-center hover:bg-white/20 transition-colors">
                            <i class="fab fa-github"></i>
                        </a>
                        <a href="https://www.linkedin.com/in/nurh1dayat/" class="h-8 w-8 bg-white/10 rounded-lg flex items-center justify-center hover:bg-white/20 transition-colors">
                            <i class="fab fa-linkedin"></i>
                        </a>
                    </div>
                </section>

                <section class="bg-white p-6 rounded-2xl shadow-sm border border-slate-200">
                    <h3 class="text-sm font-bold text-slate-800 mb-3">Versi Aplikasi</h3>
                    <div class="flex items-center justify-between py-2 border-b border-slate-50">
                        <span class="text-xs text-slate-500">Current Version</span>
                        <span class="text-xs font-bold text-indigo-600 px-2 py-0.5 bg-indigo-50 rounded">v2.1.0-stable</span>
                    </div>
                    <div class="flex items-center justify-between py-2 border-b border-slate-50">
                        <span class="text-xs text-slate-500">Last Build</span>
                        <span class="text-xs font-medium text-slate-700"><?= date('d M Y') ?></span>
                    </div>
                    <div class="flex items-center justify-between py-2">
                        <span class="text-xs text-slate-500">Environment</span>
                        <span class="text-xs font-medium text-green-600">Production</span>
                    </div>
                </section>
            </div>
        </div>

        <!-- Footer inside content -->
        <div class="mt-12 text-center">
            <div class="h-px w-24 bg-slate-200 mx-auto mb-6"></div>
            <p class="text-[10px] text-slate-400 font-bold uppercase tracking-widest leading-loose">
                Kelas PTIK C 2024<br>
                Jurusan Teknik Informatika dan Komputer<br>
                Fakultas Teknik - Universitas Negeri Makassar
            </p>
        </div>
    </main>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.2/gsap.min.js"></script>
    <script>
        // Animasi masuk
        gsap.from("main", {
            opacity: 0,
            y: 30,
            duration: 1,
            ease: "power3.out"
        });
        gsap.from(".tech-card", {
            scale: 0.8,
            opacity: 0,
            duration: 0.5,
            stagger: 0.1,
            ease: "back.out(1.7)",
            delay: 0.5
        });
    </script>
</body>

</html>