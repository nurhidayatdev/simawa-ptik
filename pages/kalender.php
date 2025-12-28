<?php
include '../koneksi.php';

// Get active period
$q_active = mysqli_query($koneksi, "SELECT * FROM periode WHERE status = 'aktif' LIMIT 1");
$active_period = mysqli_fetch_assoc($q_active);
$active_id = $active_period['id'] ?? 0;

// Filter by period if selected
$periode_id = $_GET['periode_id'] ?? $active_id;

// Fetch periods for dropdown
$periods = mysqli_query($koneksi, "SELECT * FROM periode ORDER BY id DESC");

// Fetch calendar events
$events = mysqli_query($koneksi, "SELECT * FROM kalender_akademik WHERE periode_id = '$periode_id' ORDER BY tanggal_mulai ASC");
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#6c27dc" />
    <link rel="icon" href="../img/icon.png" />
    <title>Kalender Akademik - Kelas PTIK C 2024</title>
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
                        <a href="kalender.php" class="flex items-center hover:text-indigo-200 transition text-sm text-indigo-200">
                            <i class="fas fa-calendar-alt mr-2"></i>Kalender
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
                    <a href="kalender.php" class="flex items-center space-x-3 hover:text-indigo-200 transition text-sm py-2 text-indigo-200">
                        <i class="fas fa-calendar-alt w-5"></i><span>Kalender</span>
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
                    <h2 class="text-3xl font-bold text-gray-800">Kalender Akademik</h2>
                    
                    <!-- Filter -->
                    <form action="" method="GET" class="w-full md:w-auto flex items-center gap-3 bg-white p-2 rounded-lg shadow-sm border border-gray-200">
                        <label for="periode" class="text-xs font-medium text-gray-500 ml-2">Periode:</label>
                        <select name="periode_id" id="periode" onchange="this.form.submit()" 
                            class="bg-gray-50 border border-gray-200 rounded-md px-3 py-1.5 text-xs focus:outline-none focus:ring-1 focus:ring-indigo-500">
                            <?php while($p = mysqli_fetch_assoc($periods)): ?>
                                <option value="<?= $p['id'] ?>" <?= ($periode_id == $p['id']) ? 'selected' : '' ?>>
                                    <?= $p['tahun_periode'] ?> - <?= $p['semester_nama'] ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </form>
                </div>

                <!-- Table Layout (Matching pages/dosen.php) -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-slate-200">
                            <thead class="bg-indigo-900">
                                <tr>
                                    <th class="px-6 py-4 text-left text-xs font-bold text-white uppercase tracking-wider w-16">No</th>
                                    <th class="px-6 py-4 text-left text-xs font-bold text-white uppercase tracking-wider">Kegiatan</th>
                                    <th class="px-6 py-4 text-left text-xs font-bold text-white uppercase tracking-wider">Tanggal</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-slate-200">
                                <?php if(mysqli_num_rows($events) > 0): ?>
                                    <?php 
                                    $no = 1; 
                                    $id = 1;
                                    while($event = mysqli_fetch_assoc($events)): 
                                        $rowClass = ($id++ % 2 == 0) ? 'bg-slate-50' : 'bg-white';
                                    ?>
                                        <tr class="<?= $rowClass ?> hover:bg-indigo-50 transition-colors">
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600"><?= $no++ ?></td>
                                            <td class="px-6 py-4 text-sm font-semibold text-gray-800"><?= htmlspecialchars($event['kegiatan']) ?></td>
                                            <td class="px-6 py-4 text-sm text-gray-600 italic">
                                                <div class="flex items-center gap-2">
                                                    <i class="far fa-calendar-alt text-indigo-600 text-xs"></i>
                                                    <span><?= date('d M Y', strtotime($event['tanggal_mulai'])) ?></span>
                                                    <?php if($event['tanggal_mulai'] != $event['tanggal_selesai']): ?>
                                                        <span>-</span>
                                                        <span><?= date('d M Y', strtotime($event['tanggal_selesai'])) ?></span>
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="3" class="px-6 py-20 text-center text-gray-400 italic">
                                            <i class="fas fa-calendar-times text-4xl block mb-2 opacity-20"></i>
                                            Belum ada agenda terdaftar untuk periode ini.
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
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
