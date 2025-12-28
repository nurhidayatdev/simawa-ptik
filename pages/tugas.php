<?php
include '../koneksi.php';

// Auto-update status based on deadline before fetching
mysqli_query($koneksi, "UPDATE tugas SET status = 'selesai' WHERE (deadline_tanggal < CURDATE() OR (deadline_tanggal = CURDATE() AND deadline_pukul < CURTIME())) AND status = 'belum'");

$view_history = isset($_GET['history']) && $_GET['history'] == '1';
$status_filter = $view_history ? 'selesai' : 'belum';

// Get active period
$q_active = mysqli_query($koneksi, "SELECT id, tahun_periode, semester_nama FROM periode WHERE status = 'aktif' LIMIT 1");
$active_period = mysqli_fetch_assoc($q_active);
$periode_id = $active_period['id'] ?? 0;

$query = mysqli_query($koneksi, "
    SELECT t.*, mk.nama_matkul 
    FROM tugas t 
    LEFT JOIN mata_kuliah mk ON t.matkul_id = mk.id 
    WHERE t.periode_id = '$periode_id' AND t.status = '$status_filter' 
    ORDER BY t.deadline_tanggal ASC
");
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#6c27dc" />
    <link rel="icon" href="../img/icon.png" />
    <title>Tugas Kuliah - Kelas PTIK C 2024</title>
    <link href="../frontend/tailwind/src/output.css" rel="stylesheet">
    <link href="../frontend/tailwind/src/input.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">
</head>

<body class="bg-gray-50 min-h-screen flex flex-col">
    <header class="bg-indigo-950 text-white shadow-lg fixed top-0 left-0 right-0 z-50">
        <div class="container mx-auto px-4 py-2 flex justify-between items-center">
            <!-- Logo -->
            <div class="flex items-center">
                <img src="../img/logo.png" alt="Logo Universitas Negeri Makassar" class="h-10 w-10 mr-3">
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
                        <a href="../index.php" class="flex items-center hover:text-indigo-200 transition text-sm">
                            <i class="fas fa-home mr-2"></i>Beranda
                        </a>
                    </li>
                    <li>
                        <a href="mahasiswa.php" class="flex items-center hover:text-indigo-200 transition text-sm">
                            <i class="fas fa-user-graduate mr-2"></i>Mahasiswa
                        </a>
                    </li>
                    <li>
                        <a href="akademik.php" class="flex items-center hover:text-indigo-200 transition text-sm">
                            <i class="fas fa-graduation-cap mr-2"></i>Akademik
                        </a>
                    </li>
                    <li>
                        <a href="tugas.php" class="flex items-center hover:text-indigo-200 transition text-sm text-indigo-200">
                            <i class="fas fa-tasks mr-2"></i>Tugas
                        </a>
                    </li>
                    <li>
                        <a href="galeri.php" class="flex items-center hover:text-indigo-200 transition text-sm">
                            <i class="fas fa-images mr-2"></i>Galeri
                        </a>
                    </li>
                    <li>
                        <a href="../backend/login/login.php" class="flex items-center hover:text-indigo-200 transition text-sm">
                            <i class="fas fa-sign-in-alt mr-2"></i>Login
                        </a>
                    </li>
                </ul>
            </nav>
        </div>

        <nav id="mobileMenu" class="hidden md:hidden absolute top-full left-0 w-full bg-indigo-950 shadow-md z-50">
            <ul class="flex flex-col space-y-2 p-4">
                <li>
                    <a href="../index.php" class="flex items-center space-x-3 hover:text-indigo-200 transition text-sm py-2">
                        <i class="fas fa-home w-5"></i><span>Beranda</span>
                    </a>
                </li>
                <li>
                    <a href="mahasiswa.php" class="flex items-center space-x-3 hover:text-indigo-200 transition text-sm py-2">
                        <i class="fas fa-user-graduate w-5"></i><span>Mahasiswa</span>
                    </a>
                </li>
                <li>
                    <a href="akademik.php" class="flex items-center space-x-3 hover:text-indigo-200 transition text-sm py-2">
                        <i class="fas fa-graduation-cap w-5"></i><span>Akademik</span>
                    </a>
                </li>
                 <li>
                    <a href="tugas.php" class="flex items-center space-x-3 hover:text-indigo-200 transition text-sm py-2 text-indigo-200">
                        <i class="fas fa-tasks w-5"></i><span>Tugas</span>
                    </a>
                </li>
                <li>
                    <a href="galeri.php" class="flex items-center space-x-3 hover:text-indigo-200 transition text-sm py-2">
                        <i class="fas fa-images w-5"></i><span>Galeri</span>
                    </a>
                </li>
                <li>
                    <a href="../backend/login/login.php" class="flex items-center space-x-3 hover:text-indigo-200 transition text-sm py-2">
                        <i class="fas fa-sign-in-alt w-5"></i><span>Login</span>
                    </a>
                </li>
            </ul>
        </nav>
    </header>

    <!-- spacer to avoid content hidden under fixed header -->
    <div class="h-20"></div>

    <main class="flex-grow">
        <section class="py-12">
            <div class="container mx-auto px-4">
                <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4 mb-8">
                    <div>
                        <h2 class="text-3xl font-bold text-gray-800">Tugas Kuliah</h2>
                        <p class="text-xs text-gray-500 mt-1"><?= $active_period['tahun_periode'] ?> - <?= $active_period['semester_nama'] ?></p>
                    </div>
                    
                    <div class="flex gap-2">
                        <?php if ($view_history): ?>
                            <a href="tugas.php" class="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-lg shadow-sm text-xs font-semibold flex items-center transition">
                                <i class="fas fa-arrow-left mr-2"></i> Kembali ke Tugas Aktif
                            </a>
                        <?php else: ?>
                            <a href="tugas.php?history=1" class="bg-white hover:bg-gray-50 text-gray-700 px-4 py-2 rounded-lg shadow-sm border border-gray-200 text-xs font-semibold flex items-center transition">
                                <i class="fas fa-history mr-2"></i> Lihat Tugas Selesai
                            </a>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    <?php if (mysqli_num_rows($query) > 0) : ?>
                        <?php while ($data = mysqli_fetch_assoc($query)) : ?>
                            <article class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden hover:shadow-md transition flex flex-col">
                                <div class="p-5 flex-grow">
                                    <div class="flex justify-between items-start mb-4">
                                        <span class="bg-indigo-50 text-indigo-700 text-[10px] font-bold px-2 py-1 rounded uppercase tracking-wider">
                                            <?= htmlspecialchars($data['nama_matkul']) ?>
                                        </span>
                                        <?php if ($data['status'] == 'selesai'): ?>
                                            <span class="bg-gray-100 text-gray-500 text-[10px] font-bold px-2 py-1 rounded uppercase tracking-wider">Selesai</span>
                                        <?php else: ?>
                                            <span class="bg-yellow-50 text-yellow-700 text-[10px] font-bold px-2 py-1 rounded uppercase tracking-wider">Aktif</span>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <h3 class="font-bold text-gray-800 mb-2"><?= htmlspecialchars($data['nama_matkul']) ?></h3>
                                    <div class="text-sm text-gray-600 mb-4 line-clamp-3"><?= nl2br(htmlspecialchars($data['deskripsi'])) ?></div>
                                    
                                    <div class="space-y-2 border-t border-gray-50 pt-4">
                                        <div class="flex items-center text-xs text-gray-500">
                                            <i class="far fa-clock w-5 text-indigo-500"></i>
                                            <span class="font-medium text-gray-700 mr-1">Deadline:</span>
                                            <span><?= date('d M Y', strtotime($data['deadline_tanggal'])) ?> - <?= date('H:i', strtotime($data['deadline_pukul'])) ?></span>
                                        </div>
                                        
                                        <?php if (!empty($data['file_tugas'])): ?>
                                            <div class="flex items-center text-xs text-gray-500">
                                                <i class="far fa-file-alt w-5 text-indigo-500"></i>
                                                <a href="../files/tugas/<?= htmlspecialchars($data['file_tugas']) ?>" target="_blank" class="text-indigo-600 hover:underline">Unduh Lampiran</a>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                
                                <div class="p-4 bg-gray-50 border-t border-gray-100">
                                    <?php if (!empty($data['link_kirim'])): ?>
                                        <a href="<?= htmlspecialchars($data['link_kirim']) ?>" target="_blank" class="w-full bg-indigo-600 hover:bg-indigo-700 text-white py-2 rounded-lg text-xs font-semibold transition flex items-center justify-center space-x-2">
                                            <i class="fas fa-paper-plane"></i>
                                            <span>Kirim Tugas</span>
                                        </a>
                                    <?php else: ?>
                                        <span class="w-full bg-gray-200 text-gray-500 py-2 rounded-lg text-xs font-semibold flex items-center justify-center cursor-not-allowed">
                                            <i class="fas fa-info-circle mr-2"></i> Tidak ada link pengumpulan
                                        </span>
                                    <?php endif; ?>
                                </div>
                            </article>
                        <?php endwhile; ?>
                    <?php else : ?>
                        <div class="col-span-full text-center py-20 bg-white rounded-xl border border-dashed border-gray-300">
                            <i class="fas fa-clipboard-list text-5xl text-gray-200 mb-4"></i>
                            <p class="text-gray-400 text-sm italic">Belum ada data tugas yang tersedia.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </section>
    </main>

    <!-- Footer -->
    <footer class="bg-indigo-950 text-white py-4 mt-auto">
        <div class="container mx-auto px-4 text-center">
            <p class="text-xs">© 2025 Kelas PTIK C - Teknik Informatika dan Komputer FT UNM. All rights reserved.</p>
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
