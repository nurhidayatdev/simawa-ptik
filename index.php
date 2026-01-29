<?php
include 'koneksi.php';

// Handle Anonymous Message Form Submission

include 'includes/pesan.php';


$mhs = mysqli_query($koneksi, "SELECT COUNT(*) FROM mahasiswa");
$jml = mysqli_fetch_assoc($mhs);
$sms_aktif = mysqli_query($koneksi, "SELECT semester_ke FROM periode WHERE status = 'aktif'");
$sms = mysqli_fetch_assoc($sms_aktif);
?>
<!DOCTYPE html>
<html lang="id">

<head>

    <title>PTIK C 2024 - Teknik Informatika dan Komputer</title>
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
    </style>

</head>

<body class="bg-gray-50 min-h-screen flex flex-col">
    <?php include 'includes/header_main.php'; ?>


    <!-- spacer to avoid content hidden under fixed header -->
    <div class="h-14"></div>

    <!-- Hero Section: Premium Redesign -->
    <section class="relative min-h-[100vh] flex items-center justify-center overflow-hidden bg-slate-900">
        <!-- Background with Parallax effect hint -->
        <div class="absolute inset-0 z-0">
            <div class="absolute inset-0 bg-gradient-to-t from-slate-950 via-slate-900/60 to-transparent z-10"></div>
            <img src="assets/image/img-1.webp" alt="Background" class="w-full h-full object-cover scale-105 animate-slow-zoom brightness-[0.4]">
        </div>

        <!-- Decorative Elements -->
        <div class="absolute top-1/4 -left-20 w-80 h-80 bg-indigo-600/20 rounded-full blur-[100px] z-0 animate-pulse"></div>
        <div class="absolute bottom-1/4 -right-20 w-80 h-80 bg-indigo-500/20 rounded-full blur-[100px] z-0"></div>

        <div class="relative z-20 container mx-auto px-4 text-center">
            <!-- Glassmorphism Badge -->
            <div class="inline-flex items-center gap-2 px-4 py-2 rounded-full bg-white/5 backdrop-blur-md border border-white/10 mb-8 animate-fade-in-down">
                <span class="flex h-2 w-2 rounded-full bg-indigo-500 animate-ping"></span>
                <span class="text-indigo-200 text-xs font-bold uppercase tracking-widest">Sistem Informasi</span>
            </div>

            <!-- Main Heading -->
            <h2 class="text-4xl md:text-7xl font-black text-white mb-6 tracking-tight leading-tight animate-fade-in-up">
                PTIK C <span class="text-transparent bg-clip-text bg-gradient-to-r from-indigo-400 to-indigo-200 uppercase">2024</span>
            </h2>

            <!-- Subheading -->
            <p class="text-lg md:text-xl text-indigo-100/80 mb-10 max-w-2xl mx-auto leading-relaxed font-light animate-fade-in-up delay-100">
                Wadah kolaborasi, kreativitas, dan inovasi bagi mahasiswa Teknik Informatika dan Komputer - Universitas Negeri Makassar.
            </p>

            <!-- CTA Buttons -->
            <div class="flex flex-col sm:flex-row items-center justify-center gap-4 animate-fade-in-up delay-200">
                <a href="#team" class="w-full sm:w-auto px-6 py-3 sm:px-8 sm:py-4 bg-indigo-600 hover:bg-indigo-500 text-white rounded-xl sm:rounded-2xl text-sm sm:text-base font-bold transition-all hover:shadow-[0_0_20px_rgba(79,70,229,0.4)] flex items-center justify-center gap-2 group">
                    <span>Jelajahi Sekarang</span>
                    <i class="fas fa-arrow-right text-xs sm:text-sm group-hover:translate-x-1 transition-transform"></i>
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



    <!-- Profil Kelas -->
    <section class="py-12 bg-white overflow-hidden">
        <div class="container mx-auto px-4">
            <div class="flex flex-col lg:flex-row items-center gap-12 lg:gap-20">
                <!-- Content Left -->
                <div class="lg:w-1/2">

                    <h2 class="text-3xl md:text-4xl font-bold text-slate-900 mb-6 leading-tight">
                        Membangun Masa Depan <br>
                        <span class="text-indigo-600 italic">Teknologi Bersama</span>
                    </h2>
                    <div class="space-y-4 text-slate-600 leading-relaxed max-w-xl">
                        <p>
                            Kelas PTIK C Angkatan 2024 adalah bagian dari Program Studi Teknik Informatika dan Komputer
                            di Fakultas Teknik, Universitas Negeri Makassar. Kami adalah kelompok mahasiswa yang
                            berdedikasi untuk menguasai teknologi dan inovasi di era transformasi digital.
                        </p>
                        <p>
                            Dengan semangat kolaborasi yang kuat, kami tidak hanya belajar secara akademis,
                            tetapi juga membangun ekosistem pendukung yang mempermudah interaksi dan pengelolaan
                            informasi kelas secara pintar dan transparan.
                        </p>
                    </div>

                    <!-- Modern Stats -->
                    <div class="grid grid-cols-2 gap-6 mt-10">
                        <div class="p-5 rounded-2xl bg-slate-50 border border-slate-100 relative group overflow-hidden hover:border-indigo-200 transition-colors">
                            <div class="absolute top-0 right-0 p-3 opacity-5 text-4xl group-hover:scale-110 transition-transform">
                                <i class="fa fa-users"></i>
                            </div>
                            <div class="text-3xl font-black text-indigo-600 mb-1"><?= ($jml['COUNT(*)']) ?></div>
                            <div class="text-[11px] font-bold text-slate-500 uppercase tracking-widest">Mahasiswa Aktif</div>
                        </div>
                        <div class="p-5 rounded-2xl bg-slate-50 border border-slate-100 relative group overflow-hidden hover:border-indigo-200 transition-colors">
                            <div class="absolute top-0 right-0 p-3 opacity-5 text-4xl group-hover:scale-110 transition-transform">
                                <i class="fa fa-graduation-cap"></i>
                            </div>
                            <div class="text-3xl font-black text-indigo-600 mb-1"><?= ($sms['semester_ke']) ?></div>
                            <div class="text-[11px] font-bold text-slate-500 uppercase tracking-widest">Semester Berjalan</div>
                        </div>
                    </div>
                </div>

                <!-- Image Right -->
                <div class="lg:w-1/2 relative">
                    <div class="absolute -top-6 -left-6 w-24 h-24 bg-indigo-600/5 rounded-full blur-2xl"></div>
                    <div class="absolute -bottom-10 -right-10 w-40 h-40 bg-indigo-600/10 rounded-full blur-3xl"></div>

                    <div class="relative z-10 p-2 bg-white rounded-[32px] shadow-2xl border border-slate-100">
                        <img src="assets/image/img-2.webp" alt="Foto bersama kelas PTIK C Angkatan 2024" class="rounded-[24px] w-full object-cover">


                    </div>

                    <!-- Decorative Dots -->
                    <div class="absolute top-1/2 -right-4 transform -translate-y-1/2 grid grid-cols-2 gap-2 opacity-20">
                        <?php for ($i = 0; $i < 6; $i++): ?>
                            <div class="w-1.5 h-1.5 bg-indigo-600 rounded-full"></div>
                        <?php endfor; ?>
                    </div>
                </div>
            </div>
        </div>
    </section>





    <!-- Struktur Pengurus Kelas (dinamis dari tabel `users` + `mahasiswa`) -->
    <section id="team" class="py-14 bg-indigo-950 relative overflow-hidden">
        <!-- Decorative Background Elements -->
        <div class="absolute top-0 left-0 w-full h-full overflow-hidden pointer-events-none">
            <div class="absolute -top-24 -left-24 w-96 h-96 bg-indigo-500/10 rounded-full blur-3xl"></div>
            <div class="absolute -bottom-24 -right-24 w-96 h-96 bg-indigo-500/10 rounded-full blur-3xl"></div>
        </div>

        <div class="container mx-auto px-4 relative z-10">
            <div class="max-w-5xl mx-auto">
                <div class="text-center mb-16">
                    <h3 class="text-3xl font-bold text-white">Pengurus Kelas <span class="text-indigo-400">PTIK C 24</span></h3>
                    <p class="text-indigo-200/50 text-sm mt-2 font-light tracking-wide">Struktur organisasi kelas periode aktif</p>
                </div>

                <?php
                // helper: path foto
                function foto_path($file)
                {
                    if (!$file) return 'assets/image/logo.webp';
                    $p = 'storage/profile/' . $file;
                    return file_exists($p) ? $p : 'assets/image/logo.webp';
                }

                // Ambil Ketua (role = 'Ketua')
                $qKetua = mysqli_query($koneksi, "SELECT u.id, u.email, u.role, m.nama_lengkap, m.foto FROM users u LEFT JOIN mahasiswa m ON m.user_id = u.id WHERE u.role = 'Ketua Tingkat' LIMIT 1");
                $ketua = mysqli_fetch_assoc($qKetua);
                ?>

                <!-- Ketua -->
                <div class="mb-12 max-w-xs mx-auto">
                    <div class="bg-white backdrop-blur-md rounded-2xl shadow-2xl overflow-hidden transition-all group">
                        <div class="bg-gradient-to-br from-indigo-800 to-indigo-950 h-16 relative">
                            <div class="absolute -bottom-10 left-1/2 -translate-x-1/2">
                                <div class="w-20 h-20 rounded-2xl flex items-center justify-center text-white text-xl font-bold shadow-lg border-4 border-indigo-950 overflow-hidden bg-indigo-900 group-hover:scale-105 transition-transform">
                                    <?php if ($ketua && !empty($ketua['foto'])): ?>
                                        <img src="<?php echo htmlspecialchars(foto_path($ketua['foto'])); ?>" alt="<?php echo htmlspecialchars($ketua['nama_lengkap'] ?? ''); ?>" class="w-full h-full object-cover">
                                    <?php else: ?>
                                        <span><?php echo isset($ketua['nama_lengkap']) ? strtoupper(substr($ketua['nama_lengkap'], 0, 2)) : 'KT'; ?></span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        <div class="pt-12 pb-6 px-4 text-center">
                            <div class="inline-block px-3 pb-0.5 bg-indigo-900/20 rounded-full mb-2">
                                <span class="text-[10px] font-bold text-indigo-900 uppercase tracking-wider"><?php echo htmlspecialchars($ketua['role'] ?? ''); ?></span>
                            </div>
                            <h4 class="text-sm font-bold text-indigo-950 mb-0.5"><?php echo htmlspecialchars($ketua['nama_lengkap'] ?? ''); ?></h4>
                        </div>
                    </div>
                </div>

                <?php
                // Ambil Sekretaris dan Bendahara dari users (join mahasiswa untuk nama & foto)
                $qSek = mysqli_query($koneksi, "SELECT u.id, u.email, u.role, m.nama_lengkap, m.foto FROM users u LEFT JOIN mahasiswa m ON m.user_id = u.id WHERE u.role = 'Sekretaris' ORDER BY m.nama_lengkap");
                $qBen = mysqli_query($koneksi, "SELECT u.id, u.email, u.role, m.nama_lengkap, m.foto FROM users u LEFT JOIN mahasiswa m ON m.user_id = u.id WHERE u.role = 'Bendahara' ORDER BY m.nama_lengkap");
                ?>

                <!-- Sekretaris & Bendahara -->
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4 md:gap-6">
                    <?php
                    // render sekretaris
                    while ($row = mysqli_fetch_assoc($qSek)) {
                    ?>
                        <div class="bg-white backdrop-blur-md rounded-2xl shadow-xl overflow-hidden transition-all group">
                            <div class="bg-gradient-to-br from-slate-700 to-slate-900 h-14 relative">
                                <div class="absolute -bottom-8 left-1/2 -translate-x-1/2">
                                    <div class="w-16 h-16 rounded-2xl flex items-center justify-center text-slate-900 text-lg font-bold shadow-lg border-4 border-indigo-950 overflow-hidden bg-slate-800 group-hover:scale-105 transition-transform">
                                        <?php if (!empty($row['foto'])): ?>
                                            <img src="<?php echo htmlspecialchars(foto_path($row['foto'])); ?>" alt="<?php echo htmlspecialchars($row['nama_lengkap']); ?>" class="w-full h-full object-cover">
                                        <?php else: ?>
                                            <span><?php echo strtoupper(substr($row['nama_lengkap'], 0, 2)); ?></span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                            <div class="pt-10 pb-4 px-3 text-center">
                                <div class="inline-block px-2 py-0.5 bg-slate-500/20 rounded-full mb-1">
                                    <span class="text-[10px] font-bold text-slate-600 uppercase tracking-wider">Sekretaris</span>
                                </div>
                                <h4 class="text-xs font-bold text-indigo-950 truncate"><?php echo htmlspecialchars($row['nama_lengkap']); ?></h4>
                            </div>
                        </div>
                    <?php
                    }

                    // render bendahara
                    while ($row = mysqli_fetch_assoc($qBen)) {
                    ?>
                        <div class="bg-white backdrop-blur-md rounded-2xl shadow-xl overflow-hidden transition-all group">
                            <div class="bg-gradient-to-br from-indigo-600 to-indigo-900 h-14 relative">
                                <div class="absolute -bottom-8 left-1/2 -translate-x-1/2">
                                    <div class="w-16 h-16 rounded-2xl flex items-center justify-center text-white text-lg font-bold shadow-lg border-4 border-indigo-950 overflow-hidden bg-indigo-800 group-hover:scale-105 transition-transform">
                                        <?php if (!empty($row['foto'])): ?>
                                            <img src="<?php echo htmlspecialchars(foto_path($row['foto'])); ?>" alt="<?php echo htmlspecialchars($row['nama_lengkap']); ?>" class="w-full h-full object-cover">
                                        <?php else: ?>
                                            <span><?php echo strtoupper(substr($row['nama_lengkap'], 0, 2)); ?></span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                            <div class="pt-10 pb-4 px-3 text-center">
                                <div class="inline-block px-2 py-0.5 bg-indigo-500/20 rounded-full mb-1">
                                    <span class="text-[10px] font-bold text-indigo-600 uppercase tracking-wider">Bendahara</span>
                                </div>
                                <h4 class="text-xs font-bold text-indigo-950 truncate"><?php echo htmlspecialchars($row['nama_lengkap']); ?></h4>
                            </div>
                        </div>
                    <?php
                    }
                    ?>
                </div>
            </div>
        </div>
    </section>



    <!-- Fitur & Fasilitas Sistem -->
    <section class="py-12 bg-slate-50 border-t border-slate-100">
        <div class="container mx-auto px-4">
            <div class="text-center mb-10">
                <h2 class="text-2xl md:text-3xl font-bold text-gray-800 mb-4">Fitur & Fasilitas Sistem</h2>
                <p class="text-gray-600 max-w-2xl mx-auto hidden md:block">Platform digital ini dirancang untuk mempermudah segala kebutuhan akademik dalam satu ekosistem yang terintegrasi</p>
            </div>

            <div class="grid grid-cols-2 md:grid-cols-2 lg:grid-cols-3 gap-4 md:gap-8">
                <div class="bg-white p-5 md:p-8 rounded-2xl shadow-sm border border-slate-100 hover:shadow-md transition-shadow flex flex-col items-center md:items-start text-center md:text-left">
                    <div class="w-10 h-10 md:w-12 md:h-12 bg-indigo-100 rounded-xl flex items-center justify-center text-indigo-600 text-lg md:text-xl mb-4 md:mb-6">
                        <i class="fa fa-desktop"></i>
                    </div>
                    <h3 class="text-sm md:text-xl font-bold mb-0 md:mb-3 text-slate-800">Dashboard Pintar</h3>
                    <p class="text-slate-600 text-sm leading-relaxed hidden md:block">
                        Pusat kendali utama yang menyajikan statistik absensi, deadline tugas terdekat, laporan kas terbaru, dan berita akademik dalam satu tampilan bersih.
                    </p>
                </div>

                <!-- Feature 2 -->
                <div class="bg-white p-5 md:p-8 rounded-2xl shadow-sm border border-slate-100 hover:shadow-md transition-shadow flex flex-col items-center md:items-start text-center md:text-left">
                    <div class="w-10 h-10 md:w-12 md:h-12 bg-indigo-100 rounded-xl flex items-center justify-center text-indigo-600 text-lg md:text-xl mb-4 md:mb-6">
                        <i class="fa fa-tasks"></i>
                    </div>
                    <h3 class="text-sm md:text-xl font-bold mb-0 md:mb-3 text-slate-800">Manajemen Tugas</h3>
                    <p class="text-slate-600 text-sm leading-relaxed hidden md:block">
                        Tak ada lagi tugas yang terlewat. Dilengkapi pemantau deadline, filter mata kuliah, dan pengiriman notifikasi otomatis via Bot Telegram ke grup kelas.
                    </p>
                </div>

                <!-- Feature 3 -->
                <div class="bg-white p-5 md:p-8 rounded-2xl shadow-sm border border-slate-100 hover:shadow-md transition-shadow flex flex-col items-center md:items-start text-center md:text-left">
                    <div class="w-10 h-10 md:w-12 md:h-12 bg-indigo-100 rounded-xl flex items-center justify-center text-indigo-600 text-lg md:text-xl mb-4 md:mb-6">
                        <i class="fa fa-wallet"></i>
                    </div>
                    <h3 class="text-sm md:text-xl font-bold mb-0 md:mb-3 text-slate-800">Transparansi Kas</h3>
                    <p class="text-slate-600 text-sm leading-relaxed hidden md:block">
                        Laporan keuangan kelas yang akuntabel. Mahasiswa dapat memantau saldo, tagihan pribadi, riwayat pemasukan, dan detail pengeluaran secara real-time.
                    </p>
                </div>

                <!-- Feature 4 -->
                <div class="bg-white p-5 md:p-8 rounded-2xl shadow-sm border border-slate-100 hover:shadow-md transition-shadow flex flex-col items-center md:items-start text-center md:text-left">
                    <div class="w-10 h-10 md:w-12 md:h-12 bg-indigo-100 rounded-xl flex items-center justify-center text-indigo-600 text-lg md:text-xl mb-4 md:mb-6">
                        <i class="fa fa-calendar-check"></i>
                    </div>
                    <h3 class="text-sm md:text-xl font-bold mb-0 md:mb-3 text-slate-800">Absensi Digital</h3>
                    <p class="text-slate-600 text-sm leading-relaxed hidden md:block">
                        Pencatatan kehadiran harian yang mempermudah admin dalam rekapitulasi. Menampilkan persentase kehadiran masing-masing mahasiswa untuk evaluasi.
                    </p>
                </div>

                <!-- Feature 5 -->
                <div class="bg-white p-5 md:p-8 rounded-2xl shadow-sm border border-slate-100 hover:shadow-md transition-shadow flex flex-col items-center md:items-start text-center md:text-left">
                    <div class="w-10 h-10 md:w-12 md:h-12 bg-indigo-100 rounded-xl flex items-center justify-center text-indigo-600 text-lg md:text-xl mb-4 md:mb-6">
                        <i class="fab fa-whatsapp"></i>
                    </div>
                    <h3 class="text-sm md:text-xl font-bold mb-0 md:mb-3 text-slate-800">Bot WhatsApp</h3>
                    <p class="text-slate-600 text-sm leading-relaxed hidden md:block">
                        Terhubung langsung ke grup kelas untuk pengingat tugas harian setiap pagi, notifikasi berita/jadwal, dan penerusan pesan anonim secara instan.
                    </p>
                </div>

                <!-- Feature 9 -->
                <div class="bg-white p-5 md:p-8 rounded-2xl shadow-sm border border-slate-100 hover:shadow-md transition-shadow flex flex-col items-center md:items-start text-center md:text-left">
                    <div class="w-10 h-10 md:w-12 md:h-12 bg-indigo-100 rounded-xl flex items-center justify-center text-indigo-600 text-lg md:text-xl mb-4 md:mb-6">
                        <i class="fa fa-calendar-alt"></i>
                    </div>
                    <h3 class="text-sm md:text-xl font-bold mb-0 md:mb-3 text-slate-800">Kalender Pintar</h3>
                    <p class="text-slate-600 text-sm leading-relaxed hidden md:block">
                        Sinkronisasi antara jadwal kuliah rutin dengan kalender akademik UNM. Memberikan gambaran agenda kelas selama satu semester penuh.
                    </p>
                </div>
            </div>
        </div>
    </section>



    <?php include 'includes/footer_main.php'; ?>
    <?php include 'includes/script_main.php'; ?>
</body>

</html>