<?php
session_start();
include '../../koneksi.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../../index.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$nama_lengkap = $_SESSION['nama_lengkap'];
$foto = $_SESSION['foto'];
$role = $_SESSION['role'];

// CSV export handler (top of file) - responds to ?export=csv&minggu_ke=...&filter=...
if (isset($_GET['export']) && $_GET['export'] === 'csv' && isset($_GET['minggu_ke'])) {
    $minggu = mysqli_real_escape_string($koneksi, $_GET['minggu_ke']);
    $filter = $_GET['filter'] ?? 'all';
    $filter = in_array($filter, ['all', 'Lunas', 'Belum']) ? $filter : 'all';

    $where_clause = '';
    if ($filter === 'Lunas') {
        $where_clause = "AND k.status = 'Lunas'";
    } elseif ($filter === 'Belum') {
        $where_clause = "AND (k.status IS NULL OR k.status != 'Lunas')";
    }

    $q = "SELECT m.nim, m.nama_lengkap, k.minggu_ke, k.status, k.tanggal_bayar
          FROM kas k
          JOIN mahasiswa m ON k.mahasiswa_id = m.id
          WHERE k.minggu_ke = '$minggu' $where_clause
          ORDER BY m.nim ASC";

    $res = mysqli_query($koneksi, $q);

    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=kas_minggu_' . $minggu . '.csv');
    $out = fopen('php://output', 'w');
    fputcsv($out, ['NIM', 'Nama Lengkap', 'Minggu', 'Status', 'Tanggal Bayar']);
    while ($r = mysqli_fetch_assoc($res)) {
        $tanggal = !empty($r['tanggal_bayar']) ? date('d/m/Y', strtotime($r['tanggal_bayar'])) : '';
        fputcsv($out, [$r['nim'], $r['nama_lengkap'], $r['minggu_ke'], $r['status'], $tanggal]);
    }
    fclose($out);
    exit;
}

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#1e1b4b" />
    <link rel="icon" href="../img/main/icon.png" />
    <title>Data Kas - PTIK C 2024</title>
    <link href="../../frontend/tailwind/src/output.css" rel="stylesheet">
    <link href="../../frontend/tailwind/src/input.css" rel="stylesheet">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&display=swap" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        .rotate-180 {
            transform: rotate(180deg);
        }

        /* Tambahan CSS untuk mobile menu */
        @media (max-width: 768px) {
            .mobile-menu-open {
                transform: translateX(0) !important;
            }
        }
    </style>
</head>

<body class="bg-slate-100">
    <!-- Header dengan z-index lebih tinggi -->
    <header class="bg-indigo-950  fixed w-full top-0 z-50">
        <div class="flex justify-between items-center px-6 py-2">
            <div class="flex items-center">
                <img src="../img/main/logo.png" alt="Logo Universitas Negeri Makassar" class="h-10 w-10 mr-3">
                <span class="text-xl font-bold text-white">Dashboard PTIK C</span>
            </div>
            <!-- Mobile Menu Button dengan z-index yang sesuai -->
            <button id="mobile-menu-button" class="text-slate-100 hover:text-indigo-950 lg:hidden p-2 rounded-lg hover:bg-gray-100">
                <i class="fa fa-bars w-6 h-5"></i>
            </button>
            <div class="hidden lg:flex items-center space-x-4">
                <div class="relative">
                    <button id="profile-button" class="flex items-center space-x-2">
                        <span class="text-white"><?= htmlspecialchars($nama_lengkap) ?></span>
                        <img src="../img/profile/<?= htmlspecialchars($foto) ?>" alt="Profile" class="w-8 h-8 rounded-full">
                    </button>
                    <div id="profile-dropdown" class="absolute right-0 mt-2 w-48 bg-white rounded-md shadow-lg hidden">
                        <a href="profile.php" class="block px-4 py-2 text-sm hover:bg-gray-100">Profile</a>
                        <a href="../../index.php" class="block px-4 py-2 text-sm hover:bg-gray-100">Logout</a>
                    </div>
                </div>
            </div>
        </div>
    </header>

    <aside id="sidebar"
        class="fixed left-0 top-0 h-screen w-48 bg-white transform -translate-x-full lg:translate-x-0 transition-transform duration-200 ease-in-out z-40">
        <!-- Tambahan padding top agar tidak tertutup header -->
        <div class="pt-16">
            <nav class="mt-4 h-[calc(100vh-4rem)] overflow-y-auto pb-10">
                <div class="px-4 space-y-2">
                    <!-- Dashboard Menu -->
                    <a href="dashboard.php" class="flex items-center px-4 py-2 text-slate-700  rounded-lg hover:bg-slate-100">
                        <i class="fa fa-home w-4 h-4 mr-4"></i>
                        <span class="text-xs">Beranda</span>
                    </a>

                    <!-- Components Menu -->
                    <div class="space-y-1">
                        <div>
                            <p class="px-4 text-[10px] font-semibold text-slate-400 uppercase tracking-wider mb-2">Akademik</p>
                            <div class="space-y-1">
                                <button class="submenu-button flex items-center justify-between w-full px-4 py-1 text-slate-800 hover:bg-slate-100 rounded-lg transition-colors">
                                    <div class="flex items-center">
                                        <i class="fa fa-university w-5 h-5 mr-3 text-indigo-600"></i>
                                        <span class="text-xs font-medium">Master</span>
                                    </div>
                                    <i class="fa fa-chevron-down submenu-arrow w-3 h-3 transition-transform duration-200"></i>
                                </button>
                                <div class="submenu pl-1 space-y-1 hidden overflow-y-auto max-h-52 border-l-2 border-slate-100 ml-6">
                                    <a href="db_mahasiswa.php" class="block px-4 py-1 text-xs text-slate-600 hover:text-indigo-600 hover:bg-slate-50 rounded-lg">Mahasiswa</a>
                                    <a href="db_dosen.php" class="block px-4 py-1 text-xs text-slate-600 hover:text-indigo-600 hover:bg-slate-50 rounded-lg">Dosen</a>
                                    <a href="db_matkul.php" class="block px-4 py-1 text-xs text-slate-600 hover:text-indigo-600 hover:bg-slate-50 rounded-lg">Mata Kuliah</a>
                                    <a href="db_berita.php" class="block px-4 py-1 text-xs text-slate-600 hover:text-indigo-600 hover:bg-slate-50 rounded-lg">Berita & Info</a>
                                    <a href="db_kalender.php" class="block px-4 py-1 text-xs text-slate-600 hover:text-indigo-600 hover:bg-slate-50 rounded-lg">Kalender</a>
                                </div>

                                <a href="db_jadwal.php" class="flex items-center px-4 py-1 text-slate-800 hover:bg-slate-100 rounded-lg transition-colors">
                                    <i class="fa fa-list-alt w-5 h-5 mr-3 text-indigo-600"></i>
                                    <span class="text-xs font-medium">Jadwal Kuliah</span>
                                </a>
                                <a href="db_absensi.php?matkul_id=33&pertemuan=1" class="flex items-center px-4 py-1 text-slate-800 hover:bg-slate-100 rounded-lg transition-colors">
                                    <i class="fa fa-calendar-check w-5 h-5 mr-3 text-indigo-600"></i>
                                    <span class="text-xs font-medium">Absensi</span>
                                </a>
                                <a href="db_tugas.php" class="flex items-center px-4 py-1 text-slate-800 hover:bg-slate-100 rounded-lg transition-colors">
                                    <i class="fa fa-tasks w-5 h-5 mr-3 text-indigo-600"></i>
                                    <span class="text-xs font-medium">Tugas Kuliah</span>
                                </a>
                                <a href="db_kelompok.php" class="flex items-center px-4 py-1 text-slate-800 hover:bg-slate-100 rounded-lg transition-colors">
                                    <i class="fa fa-users w-5 h-5 mr-3 text-indigo-600"></i>
                                    <span class="text-xs font-medium">Kelompok</span>
                                </a>
                            </div>
                        </div>

                        <div>
                            <p class="px-4 text-[10px] font-semibold text-slate-400 uppercase tracking-wider mb-2">Keuangan & Galeri</p>
                            <div class="space-y-1">
                                <a href="db_kas.php?minggu_ke=1" class="flex items-center px-4 py-1 text-slate-800 hover:bg-slate-100 rounded-lg transition-colors">
                                    <i class="fa fa-wallet w-5 h-5 mr-3 text-emerald-600"></i>
                                    <span class="text-xs font-medium">Kas Mingguan</span>
                                </a>
                                <a href="db_galeri.php" class="flex items-center px-4 py-1 text-slate-800 hover:bg-slate-100 rounded-lg transition-colors">
                                    <i class="fa fa-images w-5 h-5 mr-3 text-emerald-600"></i>
                                    <span class="text-xs font-medium">Galeri Kelas</span>
                                </a>
                                <a href="db_pesan.php" class="flex items-center px-4 py-1 text-slate-800 hover:bg-slate-100 rounded-lg transition-colors">
                                    <i class="fa fa-comment-dots w-5 h-5 mr-3 text-emerald-600"></i>
                                    <span class="text-xs font-medium">Pesan Anonim</span>
                                </a>
                                <a href="kuis_harian.php" class="flex items-center px-4 py-1 text-slate-800 hover:bg-slate-100 rounded-lg transition-colors">
                                    <i class="fa fa-gamepad w-5 h-5 mr-3 text-emerald-600"></i>
                                    <span class="text-xs font-medium">Kuis Harian</span>
                                </a>
                            </div>
                        </div>

                        <?php if ($role === 'Administrator Utama'): ?>
                            <div>
                                <p class="px-4 text-[10px] font-semibold text-red-400 uppercase tracking-wider mb-2">Administrator</p>
                                <div class="space-y-1">
                                    <a href="db_users.php" class="flex items-center px-4 py-1 text-slate-800 hover:bg-red-50 hover:text-red-700 rounded-lg transition-colors">
                                        <i class="fa fa-user-shield w-5 h-5 mr-3"></i>
                                        <span class="text-xs font-medium">Manajemen User</span>
                                    </a>
                                    <a href="db_periode.php" class="flex items-center px-4 py-1 text-slate-800 hover:bg-red-50 hover:text-red-700 rounded-lg transition-colors">
                                        <i class="fa fa-clock w-5 h-5 mr-3"></i>
                                        <span class="text-xs font-medium">Periode Akademik</span>
                                    </a>
                                    <a href="reminder.php" class="flex items-center px-4 py-1 text-slate-800 hover:bg-red-50 hover:text-red-700 rounded-lg transition-colors">
                                        <i class="fa fa-paper-plane w-5 h-5 mr-3"></i>
                                        <span class="text-xs font-medium">Reminder Telegram</span>
                                    </a>
                                </div>
                            </div>
                        <?php endif; ?>

                        <div>
                            <p class="px-4 text-[10px] font-semibold text-slate-400 uppercase tracking-wider mb-2">Akun</p>
                            <div class="space-y-1">
                                <a href="profile.php" class="flex items-center px-4 py-1 text-slate-800 hover:bg-slate-100 rounded-lg transition-colors">
                                    <i class="fa fa-user-circle w-5 h-5 mr-3 text-slate-600"></i>
                                    <span class="text-xs font-medium">Profile Saya</span>
                                </a>
                                <a href="../../index.php" class="flex items-center px-4 py-1 text-slate-800 hover:bg-red-50 hover:text-red-700 rounded-lg transition-colors">
                                    <i class="fa fa-sign-out-alt w-5 h-5 mr-3 text-red-600"></i>
                                    <span class="text-xs font-medium">Logout</span>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </nav>
        </div>
    </aside>

    <main class="ml-0 lg:ml-48 pt-20 p-6 flex flex-col min-h-screen">
        <div class="bg-white rounded-lg border border-slate-200 p-6 flex-grow w-full">
            <!-- Modified this section for better mobile responsiveness -->
            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-6">
                <h3 class="text-xl font-bold text-slate-800">Data Absensi</h3>
                <?php if ($role === 'Administrator Utama' || $role === 'Bendahara'): ?>
                <div class="flex-col sm:flex-row gap-2 w-full sm:w-auto">
                    <form method="GET" class="flex flex-col sm:flex-row gap-3 w-full sm:w-auto">
                        

                        <a href="arus_kas.php" class=" bg-green-600 text-white px-3 py-2 rounded-lg hover:bg-green-800 text-center text-xs flex items-center justify-center w-full">Arus Kas</a>
                        
                            <a href="kas.php?minggu_ke=<?= $_GET['minggu_ke'] ?? '' ?>" class=" bg-blue-600 text-white px-3 py-2 rounded-lg hover:bg-blue-800 text-center text-xs flex items-center justify-center w-full">Bayar Kas</a>
                            
                            <?php if (isset($_GET['minggu_ke']) && !empty($_GET['minggu_ke'])): ?>
                            <a href="../crud/kirim_reminder_kas.php?minggu_ke=<?= $_GET['minggu_ke'] ?>" class=" bg-sky-600 text-white px-3 py-2 rounded-lg hover:bg-sky-800 text-center text-xs flex items-center justify-center w-full">Kirim Reminder</a>
                            <?php endif; ?>

                            <a href="?export=csv&minggu_ke=<?= $_GET['minggu_ke'] ?? '' ?>&filter=<?= urlencode($_GET['filter'] ?? 'all') ?>" class=" bg-green-600 text-white px-3 py-2 rounded-lg hover:bg-green-800 text-center text-xs flex items-center justify-center">Export CSV</a>
                            
                    </form>
                
            </div>
            <?php endif; ?>
        </div>

        <form method="GET" class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
            <select name="minggu_ke" required onchange="this.form.submit()" class="border rounded px-4 py-2 text-xs">
                <option value="">-- Pilih Minggu --</option>
                <?php for ($i = 1; $i <= 16; $i++): ?>
                    <option value="<?= $i ?>" <?= ($_GET['minggu_ke'] ?? '') == $i ? 'selected' : '' ?>>Minggu <?= $i ?></option>
                <?php endfor; ?>
            </select>

            <label class="sr-only">Filter</label>
            <select name="filter" onchange="this.form.submit()" class="border rounded px-4 py-2 text-xs">
                <option value="all" <?= ($_GET['filter'] ?? 'all') == 'all' ? 'selected' : '' ?>>Semua</option>
                <option value="Lunas" <?= ($_GET['filter'] ?? '') == 'Lunas' ? 'selected' : '' ?>>Lunas</option>
                <option value="Belum" <?= ($_GET['filter'] ?? '') == 'Belum' ? 'selected' : '' ?>>Belum</option>
            </select>


        </form>

        <?php
        if (isset($_GET['minggu_ke'])):

            $minggu = $_GET['minggu_ke'];

            $filter = $_GET['filter'] ?? 'all';
            $filter = in_array($filter, ['all', 'Lunas', 'Belum']) ? $filter : 'all';

            $where_clause = '';
            if ($filter === 'Lunas') {
                $where_clause = " AND k.status = 'Lunas'";
            } elseif ($filter === 'Belum') {
                $where_clause = " AND (k.status IS NULL OR k.status != 'Lunas')";
            }

            $mhs = mysqli_query($koneksi, "

    SELECT
        m.id AS mahasiswa_id,
        m.nim,
        m.nama_lengkap,
        k.minggu_ke,
        k.status,
        k.tanggal_bayar
    FROM kas k
    JOIN mahasiswa m ON k.mahasiswa_id = m.id
    WHERE k.minggu_ke = '$minggu' $where_clause
    ORDER BY m.nim ASC

");
        ?>

            <!-- Rest of the content remains the same -->
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200">
                    <thead class="bg-indigo-900">
                        <tr>
                            <th class="px-3 py-3 text-left text-xs font-bold text-white uppercase tracking-wider">
                                No</th>
                            <th class="px-3 py-3 text-left text-xs font-bold text-white uppercase tracking-wider">
                                NIM</th>
                            <th class="px-3 py-3 text-left text-xs font-bold text-white uppercase tracking-wider">
                                Nama Lengkap</th>
                            <th class="px-3 py-3 text-left text-xs font-bold text-white uppercase tracking-wider">
                                Status</th>
                            <th class="px-3 py-3 text-left text-xs font-bold text-white uppercase tracking-wider">
                                Tanggal Bayar</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-slate-200">
                        <?php
                        $no = 1;
                        $id = 1;
                        while ($row = mysqli_fetch_assoc($mhs)) : ?>
                            <tr class="<?= ($id++ % 2 == 0) ? 'bg-slate-100' : 'bg-white' ?> hover:bg-indigo-100">

                                <td class="px-3 py-2 whitespace-nowrap text-xs text-slate-800"><?= $no++; ?></td>
                                <td class="px-3 py-2 whitespace-nowrap text-xs text-slate-800"><?= $row['nim']; ?></td>
                                <td class="px-3 py-2 whitespace-nowrap text-xs text-slate-800"><?= $row['nama_lengkap']; ?></td>
                                <td class="px-3 py-2 whitespace-nowrap text-xs text-slate-800"><?= $row['status']; ?></td>
                                <?php
                                $tanggal = '-';
                                if (!empty($row['tanggal_bayar']) && $row['tanggal_bayar'] !== '0000-00-00') {
                                    $d = date_create($row['tanggal_bayar']);
                                    if ($d) $tanggal = date_format($d, 'd/m/Y');
                                }
                                ?>
                                <td class="px-3 py-2 whitespace-nowrap text-xs text-slate-800"><?= $tanggal; ?></td>


                            </tr>
                        <?php endwhile; ?>
                    </tbody>

                </table>

            </div>
        <?php endif; ?>
        </div>
        </div>
        <footer class="text-xs text-indigo-900 text-center mb-0 pb-0 mt-6">
            © 2025 Kelas PTIK C - Teknik Informatika dan Komputer FT UNM. All rights reserved.
        </footer>
    </main>
    <script>
        // Mobile menu toggle dengan perbaikan
        const mobileMenuButton = document.getElementById('mobile-menu-button');
        const sidebar = document.getElementById('sidebar');

        mobileMenuButton.addEventListener('click', (e) => {
            e.stopPropagation(); // Prevent event bubbling
            sidebar.classList.toggle('-translate-x-full');
            sidebar.classList.toggle('mobile-menu-open');
        });

        // Close sidebar when clicking outside
        document.addEventListener('click', (e) => {
            if (window.innerWidth < 768) { // Only on mobile
                if (!sidebar.contains(e.target) && !mobileMenuButton.contains(e.target)) {
                    sidebar.classList.add('-translate-x-full');
                    sidebar.classList.remove('mobile-menu-open');
                }
            }
        });

        // Submenu toggles
        const submenuButtons = document.querySelectorAll('.submenu-button');

        submenuButtons.forEach(button => {
            button.addEventListener('click', () => {
                const submenu = button.nextElementSibling;
                const arrow = button.querySelector('svg:last-child');

                submenu.classList.toggle('hidden');
                arrow.classList.toggle('rotate-180');
            });
        });

        // Profile dropdown
        const profileButton = document.getElementById('profile-button');
        const profileDropdown = document.getElementById('profile-dropdown');

        if (profileButton && profileDropdown) {
            profileButton.addEventListener('click', (e) => {
                e.stopPropagation();
                profileDropdown.classList.toggle('hidden');
            });

            document.addEventListener('click', (e) => {
                if (!profileButton.contains(e.target)) {
                    profileDropdown.classList.add('hidden');
                }
            });
        }

        // SweetAlert2 Session Messages
        <?php if (isset($_SESSION['success'])): ?>
            Swal.fire({
                icon: 'success',
                title: 'Berhasil!',
                text: '<?= $_SESSION['success'] ?>',
                timer: 3000,
                showConfirmButton: false
            });
            <?php unset($_SESSION['success']); ?>
        <?php endif; ?>

        <?php if (isset($_SESSION['error'])): ?>
            Swal.fire({
                icon: 'error',
                title: 'Gagal!',
                text: '<?= $_SESSION['error'] ?>'
            });
            <?php unset($_SESSION['error']); ?>
        <?php endif; ?>

        <?php if (isset($_SESSION['info'])): ?>
            Swal.fire({
                icon: 'info',
                title: 'Informasi',
                text: '<?= $_SESSION['info'] ?>'
            });
            <?php unset($_SESSION['info']); ?>
        <?php endif; ?>
    </script>
</body>

</html>
