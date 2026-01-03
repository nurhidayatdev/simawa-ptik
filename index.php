<?php
include 'koneksi.php';

// Handle Anonymous Message Form Submission
if (isset($_POST['kirim_pesan'])) {
    $pesan_raw = $_POST['pesan_anonim'];
    $pesan_db = mysqli_real_escape_string($koneksi, $pesan_raw);
    if (!empty($pesan_raw)) {
        $query_pesan = "INSERT INTO pesan (isi_pesan) VALUES ('$pesan_db')";
        if (mysqli_query($koneksi, $query_pesan)) {
            // Kirim notifikasi ke Telegram
            include_once 'backend/crud/kirim_pesan_telegram.php';
            sendTelegramPesan($pesan_raw);

            // Redirect to prevent form resubmission
            header("Location: index.php?status=success#footer_form");
            exit;
        }
    }
}

$mhs = mysqli_query($koneksi, "SELECT COUNT(*) FROM mahasiswa");
$jml = mysqli_fetch_assoc($mhs);
$sms_aktif = mysqli_query($koneksi, "SELECT semester_ke FROM periode WHERE status = 'aktif'");
$sms = mysqli_fetch_assoc($sms_aktif);
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#1e1b4b" />
    <title>PTIK C 2024 - Teknik Informatika dan Komputer</title>
    <link rel="icon" href="backend/img/main/icon.webp" />
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="frontend/tailwind/src/output.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&display=swap" rel="stylesheet">
    <style>
        * {
            font-family: "Poppins", sans-serif;
        }
    </style>
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
</head>

<body class="bg-gray-50 min-h-screen flex flex-col">
    <header class="bg-indigo-950 text-white shadow-lg fixed top-0 left-0 right-0 z-50">
        <div class="container mx-auto px-4 py-2 flex justify-between items-center">
            <!-- Logo -->
            <div class="flex items-center">
                <img src="backend/img/main/logo.webp" alt="Logo PTIK C 2024" class="h-10 w-10 mr-3">
                <div>
                    <h1 class="text-lg font-bold">PTIK C 2024</h1>
                    <p class="text-xs">Teknik Informatika dan Komputer - FT UNM</p>
                </div>
            </div>

            <!-- Hamburger Button -->
            <button id="menuToggle" class="md:hidden text-2xl focus:outline-none">
                <i class="fas fa-bars"></i>
            </button>

            <nav class="hidden md:block">
                <ul class="flex space-x-6">
                    <li>
                        <a href="#" class="flex items-center hover:text-indigo-200 transition text-sm">
                            <i class="fas fa-home mr-2"></i>Beranda
                        </a>
                    </li>
                    <li>
                        <a href="pages/mahasiswa.php" class="flex items-center hover:text-indigo-200 transition text-sm">
                            <i class="fas fa-user-graduate mr-2"></i>Mahasiswa
                        </a>
                    </li>
                    <li>
                        <a href="pages/akademik.php" class="flex items-center hover:text-indigo-200 transition text-sm">
                            <i class="fas fa-graduation-cap mr-2"></i>Akademik
                        </a>
                    </li>
                    <li>
                        <a href="pages/galeri.php" class="flex items-center hover:text-indigo-200 transition text-sm">
                            <i class="fas fa-images mr-2"></i>Galeri
                        </a>
                    </li>
                    <li>
                        <a href="backend/login/login.php" class="flex items-center hover:text-indigo-200 transition text-sm">
                            <i class="fas fa-sign-in-alt mr-2"></i>Login
                        </a>
                    </li>
                </ul>
            </nav>
        </div>

        <nav id="mobileMenu" class="hidden md:hidden absolute top-full left-0 w-full bg-indigo-950 shadow-md z-50">
            <ul class="flex flex-col space-y-2 p-4">
                <li>
                    <a href="#" class="flex items-center space-x-3 hover:text-indigo-200 transition text-sm py-2">
                        <i class="fas fa-home w-5"></i><span>Beranda</span>
                    </a>
                </li>
                <li>
                    <a href="pages/mahasiswa.php" class="flex items-center space-x-3 hover:text-indigo-200 transition text-sm py-2">
                        <i class="fas fa-user-graduate w-5"></i><span>Mahasiswa</span>
                    </a>
                </li>
                <li>
                    <a href="pages/akademik.php" class="flex items-center space-x-3 hover:text-indigo-200 transition text-sm py-2">
                        <i class="fas fa-graduation-cap w-5"></i><span>Akademik</span>
                    </a>
                </li>
                <li>
                    <a href="pages/galeri.php" class="flex items-center space-x-3 hover:text-indigo-200 transition text-sm py-2">
                        <i class="fas fa-images w-5"></i><span>Galeri</span>
                    </a>
                </li>
                <li>
                    <a href="backend/login/login.php" class="flex items-center space-x-3 hover:text-indigo-200 transition text-sm py-2">
                        <i class="fas fa-sign-in-alt w-5"></i><span>Login</span>
                    </a>
                </li>
            </ul>
        </nav>
    </header>

    <!-- spacer to avoid content hidden under fixed header -->
    <div class="h-14"></div>

    <!-- Hero Section (diperbaiki: tinggi responsif, konten tersentral, overlay) -->
    <section class="relative text-white overflow-hidden" style="min-height: 100vh;">
        <div class="absolute inset-0 bg-center bg-cover brightness-50"
            style="background-image: url('backend/img/main/img-1.webp'); z-index: 0;">
        </div>

        <div class="absolute inset-0 bg-black/30" style="z-index: 1;"></div>

        <div class="relative z-10 container mx-auto px-4 flex items-center justify-center" style="min-height: 100vh;">
            <div class="text-center">
                <h2 class="text-3xl md:text-5xl font-bold mb-4 leading-tight">Selamat Datang di Kelas PTIK C</h2>
                <p class="text-base md:text-xl mb-6 max-w-2xl mx-auto">Angkatan 2024 - Jurusan Teknik Informatika dan Komputer</p>
                <button class="bg-white text-indigo-800 px-6 py-3 rounded-lg font-semibold">
                    Jelajahi Lebih Lanjut
                </button>
            </div>
        </div>
    </section>

    <!-- Profil Kelas -->
    <section class="py-16 bg-white">
        <div class="container mx-auto px-4">
            <h2 class="text-3xl font-bold text-center mb-12 text-gray-800">Profil Kelas PTIK C 2024</h2>

            <div class="grid md:grid-cols-2 gap-8 items-center">
                <div>
                    <h3 class="text-2xl font-semibold mb-4 text-indigo-800">Tentang Kami</h3>
                    <p class="text-gray-700 mb-4">
                        Kelas PTIK C Angkatan 2024 adalah bagian dari Program Studi Teknik Informatika dan Komputer
                        di Fakultas Teknik, Universitas Negeri Makassar. Kami adalah generasi penerus dalam dunia
                        teknologi informasi yang siap menghadapi tantangan di era digital.
                    </p>
                    <p class="text-gray-700 mb-4">
                        Dengan semangat kolaborasi dan inovasi, kami terus belajar dan berkembang untuk menjadi
                        profesional TI yang kompeten dan berkarakter.
                    </p>
                    <div class="grid grid-cols-2 gap-4 mt-6">
                        <div class="bg-indigo-100 p-4 rounded-lg text-center">
                            <p class="text-2xl font-bold text-indigo-800"><?= ($jml['COUNT(*)']) ?></p>
                            <p class="text-gray-700">Mahasiswa</p>
                        </div>
                        <div class="bg-indigo-100 p-4 rounded-lg text-center">
                            <p class="text-2xl font-bold text-indigo-800"><?= ($sms['semester_ke']) ?></p>
                            <p class="text-gray-700">Semester</p>
                        </div>
                    </div>
                </div>

                <div class="flex justify-center">
                    <img src="backend/img/main/img-2.webp" alt="Foto bersama kelas PTIK C Angkatan 2024" class="rounded-lg shadow-lg">
                </div>
            </div>
        </div>
    </section>

    <!-- Struktur Pengurus Kelas (dinamis dari tabel `users` + `mahasiswa`) -->
    <section id="team" class="py-16 sm:py-12 bg-slate-50">
        <div class="container mx-auto px-4 sm:px-6">
            <div class="max-w-6xl mx-auto">
                <div class="text-center mb-12 sm:mb-8">
                    <h3 class="text-xl sm:text-3xl md:text-md font-bold text-slate-900">Pengurus Kelas PTIK C</h3>
                </div>

                <?php
                // helper: path foto
                function foto_path($file)
                {
                    if (!$file) return 'img/image.png';
                    $p = 'backend/img/profile/' . $file;
                    return file_exists($p) ? $p : 'img/image.png';
                }

                // Ambil Ketua (role = 'Ketua')
                $qKetua = mysqli_query($koneksi, "SELECT u.id, u.email, u.role, m.nama_lengkap, m.foto FROM users u LEFT JOIN mahasiswa m ON m.user_id = u.id WHERE u.role = 'Ketua Tingkat' LIMIT 1");
                $ketua = mysqli_fetch_assoc($qKetua);
                ?>

                <!-- Ketua -->
                <div class="mb-8 sm:mb-12 max-w-xl mx-auto">
                    <div class="bg-white rounded-3xl shadow-xl overflow-hidden border border-slate-200 card-3d hover:shadow-2xl transition-all">
                        <div class="bg-gradient-to-br from-indigo-800 to-indigo-950 h-24 sm:h-20 relative">
                            <div class="absolute -bottom-10 sm:-bottom-12 left-1/2 -translate-x-1/2">
                                <div class="w-24 h-24 rounded-xl flex items-center justify-center text-white text-2xl sm:text-3xl font-bold shadow-xl border-4 border-white overflow-hidden">
                                    <?php if ($ketua && !empty($ketua['foto'])): ?>
                                        <img src="<?php echo htmlspecialchars(foto_path($ketua['foto'])); ?>" alt="<?php echo htmlspecialchars($ketua['nama_lengkap'] ?? ''); ?>" class="w-full h-full object-cover">
                                    <?php else: ?>
                                        <span><?php echo isset($ketua['nama_lengkap']) ? strtoupper(substr($ketua['nama_lengkap'], 0, 2)) : 'KT'; ?></span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        <div class="pt-14 sm:pt-16 pb-6 px-4 sm:px-6 text-center">
                            <div class="inline-block px-3 py-1 bg-indigo-50 rounded-full mb-2">
                                <span class="text-xs font-bold text-indigo-700"><?php echo htmlspecialchars($ketua['role'] ?? ''); ?></span>
                            </div>
                            <h4 class="text-lg sm:text-sm font-bold text-slate-900 mb-1"><?php echo htmlspecialchars($ketua['nama_lengkap'] ?? ''); ?></h4>
                            <p class="text-xs sm:text-xs text-slate-600">Memimpin dan mengkoordinasi seluruh kegiatan kelas</p>
                        </div>
                    </div>
                </div>

                <?php
                // Ambil Sekretaris dan Bendahara dari users (join mahasiswa untuk nama & foto)
                $qSek = mysqli_query($koneksi, "SELECT u.id, u.email, u.role, m.nama_lengkap, m.foto FROM users u LEFT JOIN mahasiswa m ON m.user_id = u.id WHERE u.role = 'Sekretaris' ORDER BY m.nama_lengkap");
                $qBen = mysqli_query($koneksi, "SELECT u.id, u.email, u.role, m.nama_lengkap, m.foto FROM users u LEFT JOIN mahasiswa m ON m.user_id = u.id WHERE u.role = 'Bendahara' ORDER BY m.nama_lengkap");
                ?>

                <!-- Sekretaris & Bendahara -->
                <div class="grid sm:grid-cols-4 gap-6 sm:gap-8">
                    <?php
                    // render sekretaris (up to 2 card slots)
                    $sekCount = 0;
                    while ($row = mysqli_fetch_assoc($qSek)) {
                        $sekCount++;
                    ?>
                        <div class="bg-white rounded-3xl shadow-xl overflow-hidden border border-slate-200 card-3d hover:shadow-2xl transition-all">
                            <div class="bg-gradient-to-br from-slate-600 to-slate-800 h-24 sm:h-20 relative">
                                <div class="absolute -bottom-10 sm:-bottom-12 left-1/2 -translate-x-1/2">
                                    <div class="w-24 h-24 rounded-xl flex items-center justify-center text-white text-2xl sm:text-3xl font-bold shadow-xl border-4 border-white overflow-hidden">
                                        <?php if (!empty($row['foto'])): ?>
                                            <img src="<?php echo htmlspecialchars(foto_path($row['foto'])); ?>" alt="<?php echo htmlspecialchars($row['nama_lengkap']); ?>" class="w-full h-full object-cover">
                                        <?php else: ?>
                                            <span><?php echo strtoupper(substr($row['nama_lengkap'], 0, 2)); ?></span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                            <div class="pt-14 sm:pt-16 pb-6 px-4 sm:px-6 text-center">
                                <div class="inline-block px-3 py-1 bg-slate-50 rounded-full mb-2">
                                    <span class="text-xs font-bold text-slate-700"><?php echo htmlspecialchars($row['role'] ?? ''); ?></span>
                                </div>
                                <h4 class="text-lg sm:text-sm font-bold text-slate-900 mb-1"><?php echo htmlspecialchars($row['nama_lengkap']); ?></h4>
                                <p class="text-xs sm:text-xs text-slate-600">Dokumentasi & administrasi kelas</p>
                            </div>
                        </div>
                    <?php
                    }

                    // if less than 2 sekretaris, fill empty slots
                    for ($i = $sekCount; $i < 2; $i++) {
                    ?>
                        <div class="bg-white rounded-3xl shadow-xl overflow-hidden border border-dashed border-slate-200 flex items-center justify-center p-6 text-slate-400">Kosong</div>
                    <?php
                    }

                    // render bendahara (up to 2)
                    $benCount = 0;
                    while ($row = mysqli_fetch_assoc($qBen)) {
                        $benCount++;
                    ?>
                        <div class="bg-white rounded-3xl shadow-xl overflow-hidden border border-slate-200 card-3d hover:shadow-2xl transition-all">
                            <div class="bg-gradient-to-br from-indigo-600 to-indigo-800 h-24 sm:h-20 relative">
                                <div class="absolute -bottom-10 sm:-bottom-12 left-1/2 -translate-x-1/2">
                                    <div class="w-24 h-24 rounded-xl flex items-center justify-center text-white text-2xl sm:text-3xl font-bold shadow-xl border-4 border-white overflow-hidden">
                                        <?php if (!empty($row['foto'])): ?>
                                            <img src="<?php echo htmlspecialchars(foto_path($row['foto'])); ?>" alt="<?php echo htmlspecialchars($row['nama_lengkap']); ?>" class="w-full h-full object-cover">
                                        <?php else: ?>
                                            <span><?php echo strtoupper(substr($row['nama_lengkap'], 0, 2)); ?></span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                            <div class="pt-14 sm:pt-16 pb-6 px-4 sm:px-6 text-center">
                                <div class="inline-block px-3 py-1 bg-indigo-50 rounded-full mb-2">
                                    <span class="text-xs font-bold text-indigo-700"><?php echo htmlspecialchars($row['role'] ?? ''); ?></span>
                                </div>
                                <h4 class="text-lg sm:text-sm font-bold text-slate-900 mb-1"><?php echo htmlspecialchars($row['nama_lengkap']); ?></h4>
                                <p class="text-xs sm:text-xs text-slate-600">Pengelolaan keuangan kelas</p>
                            </div>
                        </div>
                    <?php
                    }

                    for ($i = $benCount; $i < 2; $i++) {
                    ?>
                        <div class="bg-white rounded-3xl shadow-xl overflow-hidden border border-dashed border-slate-200 flex items-center justify-center p-6 text-slate-400">Kosong</div>
                    <?php
                    }
                    ?>
                </div>
            </div>
        </div>
    </section>



    <!-- Footer -->
    <footer class="bg-indigo-950 text-white py-4 mt-auto">
        <div class="container mx-auto px-4">
            <div class="grid md:grid-cols-4 gap-8">
                <div>
                    <h3 class="text-lg font-bold mb-4">PTIK C 2024</h3>
                    <p class="mb-4 text-xs">Jurusan Teknik Informatika dan Komputer</p>
                    <p class="text-xs">Fakultas Teknik</p>
                    <p class="text-xs">Universitas Negeri Makassar</p>
                </div>

                <div>
                    <h3 class="text-lg font-bold mb-4">Tautan Cepat</h3>
                    <ul class="space-y-1">
                        <li><a href="pages/kalender.php" class="hover:text-indigo-200 transition text-xs">Kalender Akademik</a></li>
                        <li><a href="pages/matakuliah.php" class="hover:text-indigo-200 transition text-xs">Kurikulum PTIK</a></li>
                        <li><a href="pages/jadwal.php" class="hover:text-indigo-200 transition text-xs">Jadwal Mata Kuliah</a></li>
                        <li><a href="pages/tugas.php" class="hover:text-indigo-200 transition text-xs">Tugas Mata Kuliah</a></li>
                    </ul>
                </div>

                <div>
                    <h3 class="text-lg font-bold mb-4">Kontak</h3>
                    <p class="mb-2 text-xs"><i class="fas fa-map-marker-alt mr-2 text-sm"></i> Jl. A.P. Pettarani, Makassar</p>
                    <p class="mb-2 text-xs"><i class="fas fa-envelope mr-2 text-sm"></i> ptikc2024@unm.ac.id</p>
                    <a href="https://www.instagram.com/ptikc_24">
                        <p class="mb-2 text-xs"><i class="fab fa-instagram mr-2 text-sm"></i></i> @ptikc_24</p>
                    </a>

                </div>
                <div>
                    <h3 class="text-lg font-bold mb-4">Kirim Pesan Anonim</h3>

                    <form action="" method="POST" class="space-y-2" id="footer_form">
                        <textarea
                            name="pesan_anonim"
                            rows="3"
                            maxlength="300"
                            class="w-full p-3 rounded-lg bg-indigo-900/50 border border-indigo-800 text-white text-xs focus:outline-none focus:ring-2 focus:ring-indigo-500 placeholder-indigo-300/50"
                            placeholder="Tulis pesan rahasia atau saran di sini..."
                            required></textarea>
                        <button
                            type="submit"
                            name="kirim_pesan"
                            class="w-full bg-indigo-600 hover:bg-indigo-500 text-white py-2 rounded-lg text-xs font-semibold transition flex items-center justify-center space-x-2">
                            <i class="fas fa-paper-plane text-xs"></i>
                            <span>Kirim Sekarang</span>
                        </button>
                    </form>

                    <?php
                    if (isset($_GET['status']) && $_GET['status'] == 'success') {
                        echo "<p class='text-[10px] text-green-400 mt-2'><i class='fas fa-check-circle mr-1'></i> Pesan berhasil dikirim secara anonim!</p>";
                    }
                    ?>
                </div>
            </div>
        </div>
        </div>

        <div class="border-t border-indigo-800 mt-4 pt-4 text-center text-xs">
            <p>© 2025 Kelas PTIK C - Teknik Informatika dan Komputer FT UNM. All rights reserved.</p>
        </div>
        </div>
    </footer>

    <script>
        const menuToggle = document.getElementById('menuToggle');
        const mobileMenu = document.getElementById('mobileMenu');
        const menuIcon = menuToggle.querySelector('i');

        menuToggle.addEventListener('click', () => {
            mobileMenu.classList.toggle('hidden');
            if (mobileMenu.classList.contains('hidden')) {
                menuIcon.classList.remove('fa-xmark');
                menuIcon.classList.add('fa-bars');
            } else {
                menuIcon.classList.remove('fa-bars');
                menuIcon.classList.add('fa-xmark');
            }
        });
    </script>
</body>

</html>