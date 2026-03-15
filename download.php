<?php
include 'koneksi.php';

// Ambil periode aktif untuk konteks jika perlu
$q_periode = mysqli_query($koneksi, "SELECT * FROM periode WHERE status = 'aktif' LIMIT 1");
$periode = mysqli_fetch_assoc($q_periode);
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <title>Download Assets - PTIK C 2024</title>
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
            transform: scale(1.02);
        }

        .preview-img {
            transition: all 0.5s ease;
        }

        .preview-card:hover .preview-img {
            transform: scale(1.05);
        }
    </style>
</head>

<body class="bg-mesh min-h-screen text-slate-800">
    <?php include 'includes/header_main.php'; ?>

    <!-- Spacer -->
    <div class="h-10"></div>

    <main class="container mx-auto px-6 py-12 flex-grow">
        <!-- Hero Section -->
        <h1 class="text-3xl font-bold mb-2 text-gray-800 scroll-reveal">Download Assets</h1>
        <p class="text-gray-500 mb-8 scroll-reveal text-sm">Unduh berbagai aset digital kelas seperti logo resmi, wallpaper, serta dokumen penting lainnya di sini.</p>

        <section class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <!-- Logo Card -->
            <div class="preview-card glass-card rounded-[2rem] overflow-hidden shadow-sm border border-slate-200 flex flex-col h-full hover-scale">
                <div class="aspect-video bg-slate-100 relative overflow-hidden flex items-center justify-center p-6">
                    <div class="absolute inset-0 opacity-10" style="background-image: radial-gradient(#4f46e5 1px, transparent 1px); background-size: 20px 20px;"></div>
                    <img src="assets/image/logo-ptikc.png" alt="Preview Logo" class="preview-img w-full h-full object-contain drop-shadow-2xl relative z-10">
                </div>
                <div class="p-6 flex flex-col flex-grow">
                    <div class="flex items-center gap-3 mb-4">
                        <div class="w-10 h-10 bg-indigo-50 rounded-xl flex items-center justify-center text-indigo-600">
                            <i class="fa fa-image text-lg"></i>
                        </div>
                        <div>
                            <h2 class="text-base font-bold text-slate-900 leading-none">Logo Kelas (HD)</h2>
                            <p class="text-[9px] text-slate-400 mt-1 uppercase tracking-wider font-semibold">Branding Assets</p>
                        </div>
                    </div>
                    <p class="text-[11px] text-slate-500 leading-relaxed mb-6">
                        Logo resmi PTIK C 2024 dengan resolusi tinggi. Tersedia dalam format PNG transparan untuk kebutuhan desain, presentasi, dan dokumen resmi kelas.
                    </p>
                    <div class="mt-auto">
                        <a href="assets/image/logo-ptikc.png" download="Logo_PTIK_C_24.png" class="w-full py-3 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-[10px] font-bold transition-all flex items-center justify-center gap-2 shadow-lg shadow-indigo-100">
                            <i class="fa fa-download scale-90"></i> Download (.png)
                        </a>
                    </div>
                </div>
            </div>

            <!-- Zoom Background Card -->
            <div class="preview-card glass-card rounded-[2rem] overflow-hidden shadow-sm border border-slate-200 flex flex-col h-full hover-scale">
                <div class="aspect-video bg-slate-900 relative overflow-hidden">
                    <!-- Preview Image -->
                    <img src="assets/image/background-ptikc.jpg" alt="Preview Background" class="preview-img w-full h-full object-cover opacity-80">
                    <div class="absolute inset-0 bg-gradient-to-t from-slate-900/60 to-transparent"></div>
                    <div class="absolute inset-x-0 bottom-0 p-4 border-t border-white/5 backdrop-blur-sm bg-white/5">
                        <p class="text-[8px] text-white/60 text-center uppercase tracking-[0.2em] font-medium">Full HD Virtual Background</p>
                    </div>
                </div>
                <div class="p-6 flex flex-col flex-grow">
                    <div class="flex items-center gap-3 mb-4">
                        <div class="w-10 h-10 bg-cyan-50 rounded-xl flex items-center justify-center text-cyan-600">
                            <i class="fa fa-desktop text-lg"></i>
                        </div>
                        <div>
                            <h2 class="text-base font-bold text-slate-900 leading-none">Zoom Background</h2>
                            <p class="text-[9px] text-slate-400 mt-1 uppercase tracking-wider font-semibold">Virtual Presence</p>
                        </div>
                    </div>
                    <p class="text-[11px] text-slate-500 leading-relaxed mb-6">
                        Latar belakang virtual resmi untuk pertemuan online (Zoom/Google Meet). Memberikan kesan profesional dan kompak pada setiap sesi kuliah digital.
                    </p>
                    <div class="mt-auto flex flex-col gap-3">
                        <a href="assets/image/background-ptikc.jpg" download="Zoom_BG_PTIK_C_24.jpg" class="w-full py-3 bg-slate-900 hover:bg-slate-950 text-white rounded-xl text-[10px] font-bold transition-all flex items-center justify-center gap-2 shadow-lg shadow-slate-200">
                            <i class="fa fa-download scale-90"></i> Download (.jpg)
                        </a>
                    </div>
                </div>
            </div>
        </section>

        <!-- Back Link -->
        <div class="mt-16 text-center">
            <a href="info.php" class="inline-flex items-center gap-2 text-xs font-bold text-slate-400 hover:text-indigo-600 transition-colors">
                <i class="fa fa-arrow-left"></i> Kembali ke Informasi Kelas
            </a>
        </div>
    </main>

    <?php include 'includes/footer_main.php'; ?>
    <?php include 'includes/script_main.php'; ?>
</body>

</html>