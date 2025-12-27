<?php
session_start();
include '../../koneksi.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login/login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$nama_lengkap = $_SESSION['nama_lengkap'];
$foto = $_SESSION['foto'];

/* =====================
   AMBIL PERIODE AKTIF
   ===================== */
$q_periode = mysqli_query($koneksi, "SELECT id FROM periode WHERE status = 'aktif' LIMIT 1");
$periode = mysqli_fetch_assoc($q_periode);
$periode_id = $periode['id'];
/* =====================
   AMBIL MAHASISWA_ID
   ===================== */
$stmt = mysqli_prepare(
    $koneksi,
    "SELECT id FROM mahasiswa WHERE user_id = ? LIMIT 1"
);
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$mahasiswa = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
$mahasiswa_id = $mahasiswa['id'];

/* =====================
   SIMPAN KRS
   ===================== */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $matkul = $_POST['matkul'] ?? [];

    // hapus KRS lama
    mysqli_query(
        $koneksi,
        "DELETE FROM krs WHERE mahasiswa_id = $mahasiswa_id AND periode_id = $periode_id"
    );

    // insert KRS baru
    $stmt = mysqli_prepare(
        $koneksi,
        "INSERT INTO krs (mahasiswa_id, matkul_id, periode_id) VALUES (?, ?, ?)"
    );

    foreach ($matkul as $matkul_id) {
        mysqli_stmt_bind_param($stmt, "iii", $mahasiswa_id, $matkul_id, $periode_id);
        mysqli_stmt_execute($stmt);
    }

    header("Location: krs.php?sukses=1");
    exit;
}

/* =====================
   AMBIL MATKUL PTIK
   ===================== */
$res_matkul = mysqli_query($koneksi, "SELECT * FROM mata_kuliah ORDER BY semester, nama_matkul");

$matkul_rows = [];
while ($r = mysqli_fetch_assoc($res_matkul)) {
    $matkul_rows[] = $r;
}

/* =====================
   MATKUL YANG SUDAH DIAMBIL
   ===================== */
$krs = mysqli_query($koneksi, "
    SELECT matkul_id FROM krs
    WHERE mahasiswa_id = $mahasiswa_id
    AND periode_id = $periode_id
");

$diambil = [];
while ($row = mysqli_fetch_assoc($krs)) {
    $diambil[] = $row['matkul_id'];
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#6c27dc" />
    <link rel="icon" href="img/icon.png" />
    <title>Kelas PTIK C 2024 - Teknik Informatika dan Komputer</title>
    <link href="../../frontend/tailwind/src/output.css" rel="stylesheet">
    <link href="../../frontend/tailwind/src/input.css" rel="stylesheet">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
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

<body class="bg-gray-100">
    <!-- Header dengan z-index lebih tinggi -->
    <header class="bg-indigo-950  fixed w-full top-0 z-50">
        <div class="flex justify-between items-center px-6 py-2">
            <div class="flex items-center">
                <img src="../../img/logo.png" alt="Logo Universitas Negeri Makassar" class="h-10 w-10 mr-3">
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
                        <a href="../login/login.php" class="block px-4 py-2 text-sm hover:bg-gray-100">Logout</a>
                    </div>
                </div>
            </div>
        </div>
    </header>

    <aside id="sidebar"
        class="fixed left-0 top-0 h-screen w-48 bg-white transform -translate-x-full lg:translate-x-0 transition-transform duration-200 ease-in-out z-40">
        <!-- Tambahan padding top agar tidak tertutup header -->
        <div class="pt-16">
            <nav class="mt-6">
                <div class="px-4 space-y-2">
                    <!-- Dashboard Menu -->
                    <a href="dashboard.php" class="flex items-center px-4 py-2 text-slate-700  rounded-lg hover:bg-slate-100">
                                <i class="fa fa-home w-4 h-4 mr-4"></i>
                        <span class="text-xs">Beranda</span>
                    </a>

                    <!-- Components Menu -->
                    <div class="space-y-2">

                        <button
                            class="submenu-button flex items-center justify-between w-full px-4 py-2 text-slate-800 hover:bg-slate-100 rounded-lg">
                            <div class="flex items-center">
                                <i class="fa fa-list w-5 h-5 mr-4"></i>
                                <span class="text-xs">Akademik</span>
                            </div>
                            <i class="fa fa-chevron-down submenu-arrow w-4 h-4 transition-transform duration-200"></i>
                        </button>

                        <div class="submenu pl-8 space-y-1 hidden overflow-y-auto max-h-52">
                            <a href="db_mahasiswa.php"
                                class="block px-4 py-2 text-xs text-slate-800 hover:bg-slate-100 rounded-lg">Mahasiswa</a>

                                <a href="db_dosen.php"
                                class="block px-4 py-2 text-xs text-slate-800 hover:bg-slate-100 rounded-lg">Dosen</a>

                            <a href="db_matkul.php"
                                class="block px-4 py-2 text-xs text-slate-800 hover:bg-slate-100 rounded-lg">Mata Kuliah</a>

                            <a href="db_jadwal.php"
                                class="block px-4 py-2 text-xs text-slate-800 hover:bg-slate-100 rounded-lg">Jadwal</a>

                                <a href="db_tugas.php"
                                class="block px-4 py-2 text-xs text-slate-800 hover:bg-slate-100 rounded-lg">Tugas</a>

                            
                        </div>

                        <button
                            class="flex items-center justify-between w-full px-4 py-2 text-slate-800 hover:bg-gray-100 rounded-lg">
                            <div class="flex items-center">
                                <i class="fa fa-calendar w-5 h-5 mr-4"></i>
                                <a href="db_absensi.php?matkul_id=33&pertemuan=1">
                                    <span class="text-xs">Absensi</span>
                                </a>
                            </div>
                        </button>

                        <button
                            class="flex items-center justify-between w-full px-4 py-2 text-slate-800 hover:bg-gray-100 rounded-lg">
                            <div class="flex items-center">
                                <i class="fa fa-wallet w-5 h-5 mr-4"></i>
                                <a href="db_kas.php?minggu_ke=1">
                                    <span class="text-xs">Kas Mingguan</span>
                                </a>
                            </div>
                        </button>

                        

                        <button
                            class="flex items-center justify-between w-full px-4 py-2 text-slate-800 hover:bg-gray-100 rounded-lg">
                            <div class="flex items-center">
                                <i class="fa fa-users w-5 h-5 mr-4"></i>
                                <a href="db_kelompok.php">
                                    <span class="text-xs">Kelompok</span>
                                </a>
                            </div>
                        </button>

                        <button
                            class="flex items-center justify-between w-full px-4 py-2 text-slate-800 hover:bg-gray-100 rounded-lg">
                            <div class="flex items-center">
                                <i class="fa fa-user w-5 h-5 mr-4"></i>
                                <a href="db_users.php">
                                    <span class="text-xs">Users</span>
                                </a>
                            </div>
                        </button>

                    </div>
                </div>
            </nav>
        </div>
    </aside>

    <main class="ml-0 lg:ml-48 pt-20 p-6 flex flex-col min-h-screen">
        <div class="bg-white rounded-lg border border-slate-200 p-6 flex-grow w-full">

            <h3 class="text-2xl font-bold mb-4">Kartu Rencana Studi (KRS)</h3>

            <?php if (isset($_GET['sukses'])): ?>
                <div class="mb-4 p-3 bg-green-100 text-green-700 rounded">
                    KRS berhasil disimpan
                </div>
            <?php endif; ?>

            <form method="POST">

                <?php for ($s = 1; $s <= 8; $s++): ?>
                    <h4 class="text-lg font-semibold mt-4 mb-2">Semester <?= $s ?></h4>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-slate-200">
                            <thead class="bg-indigo-900">
                                <tr>
                                    <th class="px-3 py-3 text-center text-xs font-bold text-white uppercase tracking-wider">Ambil</th>
                                    <th class="px-3 py-3 text-left text-xs font-bold text-white uppercase tracking-wider">Kode</th>
                                    <th class="px-3 py-3 text-left text-xs font-bold text-white uppercase tracking-wider">Nama Mata Kuliah</th>
                                    <th class="px-3 py-3 text-center text-xs font-bold text-white uppercase tracking-wider">SKS</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-slate-200">
                                <?php
                                $found = false;
                                foreach ($matkul_rows as $m) {
                                    if ((int)$m['semester'] !== $s) continue;
                                    $found = true;
                                ?>
                                    <tr class="<?= ($id++ % 2 == 0) ? 'bg-slate-100' : 'bg-white' ?> hover:bg-indigo-100">
                                        <td class="px-3 py-2 whitespace-nowrap text-xs text-slate-800 text-center">
                                            <input type="checkbox" name="matkul[]" value="<?= $m['id'] ?>" <?= in_array($m['id'], $diambil) ? 'checked' : '' ?>>
                                        </td>
                                        <td class="px-3 py-2 whitespace-nowrap text-xs text-slate-800"><?= htmlspecialchars($m['kode_matkul']) ?></td>
                                        <td class="px-3 py-2 whitespace-nowrap text-xs text-slate-800"><?= htmlspecialchars($m['nama_matkul']) ?></td>
                                        <td class="px-3 py-2 whitespace-nowrap text-xs text-slate-800 text-center"><?= htmlspecialchars($m['sks']) ?></td>
                                    </tr>
                                <?php } ?>
                                <?php if (!$found): ?>
                                    <tr>
                                        <td class="px-3 py-2 whitespace-nowrap text-xs text-slate-800" colspan="4">Tidak ada mata kuliah untuk semester ini.</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endfor; ?>

                <button type="submit"
                    class="mt-4 px-4 py-2 bg-indigo-800 text-white rounded hover:bg-indigo-950 text-xs">
                    Simpan KRS
                </button>
            </form>

        </div>
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
    </script>
</body>

</html>