<?php
session_start();
include '../../koneksi.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../../index.php");
    exit;
}

$nama_lengkap = $_SESSION['nama_lengkap'];
$user_id = $_SESSION['user_id'];
$foto = $_SESSION['foto'];
$role = $_SESSION['role'];

$query = "SELECT id FROM mahasiswa WHERE user_id = ?";
$stmt  = mysqli_prepare($koneksi, $query);
mysqli_stmt_bind_param($stmt, "i", $_SESSION['user_id']);
mysqli_stmt_execute($stmt);
$mahasiswa = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

$mahasiswa_id = $mahasiswa['id'];


// ambil data user
$query = "
SELECT 
    u.id AS user_id,
    u.role,
    u.password,
    m.nim,
    m.nama_lengkap,
    m.nama_panggilan,
    u.email,
    m.foto,
    m.bio
FROM users u
JOIN mahasiswa m ON m.user_id = u.id
WHERE u.id = ?
LIMIT 1
";

$stmt = mysqli_prepare($koneksi, $query);
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$data = mysqli_fetch_assoc($result);



$query_krs = "
SELECT 
    mk.kode_matkul,
    mk.nama_matkul,
    mk.sks,
    mk.semester
FROM krs k
JOIN mahasiswa m ON k.mahasiswa_id = m.id
JOIN mata_kuliah mk ON k.matkul_id = mk.id
WHERE m.user_id = ?
";


$stmt_krs = mysqli_prepare($koneksi, $query_krs);
mysqli_stmt_bind_param($stmt_krs, "i", $user_id);
mysqli_stmt_execute($stmt_krs);
$result_krs = mysqli_stmt_get_result($stmt_krs);


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

<body class="bg-slate-100">
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
                        <a href="../../index.php" class="block px-4 py-2 text-sm hover:bg-gray-100">Logout</a>
                    </div>
                </div>
            </div>
        </div>
    </header>

    <!-- Sidebar dengan z-index di bawah header -->
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
                            <a href="db_berita.php" class="block px-4 py-2 text-xs text-slate-800 hover:bg-slate-100 rounded-lg">Berita</a>
                            <a href="db_kalender.php" class="block px-4 py-2 text-xs text-slate-800 hover:bg-slate-100 rounded-lg">Kalender</a>
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

                        

                        <a href="db_kelompok.php" class="flex items-center px-4 py-2 text-slate-800 hover:bg-gray-100 rounded-lg">
                            <i class="fa fa-users w-5 h-5 mr-4"></i>
                            <span class="text-xs">Kelompok</span>
                        </a>

                        <a href="db_galeri.php" class="flex items-center px-4 py-2 text-slate-800 hover:bg-gray-100 rounded-lg">
                            <i class="fa fa-images w-5 h-5 mr-4"></i>
                            <span class="text-xs">Galeri</span>
                        </a>

                        <a href="db_pesan.php" class="flex items-center px-4 py-2 text-slate-800 hover:bg-gray-100 rounded-lg">
                            <i class="fa fa-envelope w-5 h-5 mr-4"></i>
                            <span class="text-xs">Pesan Anonim</span>
                        </a>

                        <a href="tebak_kata.php" class="flex items-center px-4 py-2 text-slate-800 hover:bg-gray-100 rounded-lg">
                            <i class="fa fa-gamepad w-5 h-5 mr-4"></i>
                            <span class="text-xs">Tebak Kata</span>
                        </a>

                        <a href="db_users.php" class="flex items-center px-4 py-2 text-slate-800 hover:bg-gray-100 rounded-lg">
                            <i class="fa fa-user w-5 h-5 mr-4"></i>
                            <span class="text-xs">Users</span>
                        </a>

                    </div>
                </div>
            </nav>
        </div>
    </aside>

    <main class="ml-0 md:ml-64 pt-20 p-6 flex flex-col min-h-screen">
        <div class="bg-white rounded-lg border border-slate-200 p-6 flex-grow w-full">
            <div class="flex justify-between items-center mb-6">
                <h1 class="text-2xl font- text-slate-800">User Profile</h1>
                <a href="../crud/edit_profile.php?<?= $data['user_id']; ?>" id="edit-profile" class="px-4 py-2 bg-indigo-900 text-white rounded-lg hover:bg-indigo-800 text-xs">
                    Edit Profile
                </a>

            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <!-- Profile Picture Section -->
                <div class="md:col-span-1 flex flex-col items-center">
                    <img src="../img/profile/<?= htmlspecialchars($data['foto']) ?>" alt="Profile Picture"
                        class="w-32 h-32 rounded-full mb-4">
                    <h2 class="text-xl font-semibold mb-2 text-slate-800"><?= htmlspecialchars($data['nama_lengkap']) ?></h2>
                    <p class="text-slate-600"><?= htmlspecialchars($data['role']) ?></p>
                </div>

                <!-- Profile Details Section -->
                <div class="md:col-span-2">
                    <!-- Replace the content with form wrapper -->
                    <form method="POST" class="space-y-4">
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-slate-700 mb-1">Email</label>
                                <input type="email" name="email" value="<?= htmlspecialchars($data['email']) ?>"
                                    class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-slate-400 text-xs"
                                    disabled>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-slate-700 mb-1">Password</label>
                                <input type="password" name="password" value="<?= htmlspecialchars($data['password']) ?>"
                                    class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-slate-400 text-xs"
                                    disabled>
                            </div>
                        </div>
                        <!-- Bio Section -->
                        <div class="mt-4">
                            <label class="block text-sm font-medium text-slate-700 mb-1">Bio</label>
                            <input type="text" name="password" value="<?= htmlspecialchars($data['bio']) ?>"
                                class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-slate-400 text-xs"
                                disabled>
                        </div>
                        <hr class="my-6">

                        <h2 class="text-xl font-bold mb-4 text-slate-800">Kartu Rencana Studi (KRS)</h2>
                        <a href="krs.php" id="edit-profile" class="px-4 py-2 bg-indigo-900 text-white rounded-lg hover:bg-indigo-800 text-xs">

                            Edit KRS

                        </a>
                        <br>
                        <br>
                        <?php if (mysqli_num_rows($result_krs) > 0): ?>
                            <div class="overflow-x-auto">
                                <table class="min-w-full divide-y divide-slate-200">
                                    <thead>
                                        <tr>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-indigo-900 uppercase tracking-wider">No</th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-indigo-900 uppercase tracking-wider">Kode</th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-indigo-900 uppercase tracking-wider">Mata Kuliah</th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-indigo-900 uppercase tracking-wider">SKS</th>
                                        </tr>
                                    </thead>
                                    <tbody class="bg-white divide-y divide-slate-200">
                                        <?php
                                        $no = 1;
                                        $id = 1;
                                        while ($krs = mysqli_fetch_assoc($result_krs)) : ?>
                                            <tr class="<?= ($id++ % 2 == 0) ? 'bg-slate-100' : 'bg-white' ?> hover:bg-indigo-100">
                                                <td class="px-6 py-2 whitespace-nowrap text-xs text-slate-800"><?= $no++ ?></td>
                                                <td class="px-6 py-2 whitespace-nowrap text-xs text-slate-800"><?= $krs['kode_matkul'] ?></td>
                                                <td class="px-6 py-2 whitespace-nowrap text-xs text-slate-800"><?= $krs['nama_matkul'] ?></td>
                                                <td class="px-6 py-2 whitespace-nowrap text-xs text-slate-800"><?= $krs['sks'] ?></td>
                                            </tr>
                                        <?php endwhile; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <p class="text-slate-500 italic">Belum ada mata kuliah yang diambil.</p>
                        <?php endif; ?>


                        <!-- Submit Button -->

                    </form>
                </div>
            </div>
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
