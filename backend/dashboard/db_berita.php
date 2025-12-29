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

// Search functionality
$search = $_GET['search'] ?? '';
if (!empty($search)) {
    $stmt = mysqli_prepare($koneksi, "SELECT * FROM akademik WHERE judul LIKE ? OR deskripsi LIKE ? ORDER BY id DESC");
    $like = "%$search%";
    mysqli_stmt_bind_param($stmt, "ss", $like, $like);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
} else {
    $result = mysqli_query($koneksi, "SELECT * FROM akademik ORDER BY id DESC");
}

// If this is an AJAX live-search request, return only the table rows
if (isset($_GET['ajax']) && $_GET['ajax'] == '1') {
    $no = 1;
    $id = 1;
    if (mysqli_num_rows($result) > 0) {
        while ($row = mysqli_fetch_assoc($result)) {
            $rowClass = (($id++ % 2 == 0) ? 'bg-slate-100' : 'bg-white') . ' hover:bg-indigo-100';
            echo '<tr class="' . $rowClass . '">';
            echo '<td class="px-3 py-2 whitespace-nowrap text-xs text-slate-800">' . ($no++) . '</td>';
            echo '<td class="px-3 py-2 text-xs font-semibold text-slate-800">' . htmlspecialchars($row['judul']) . '</td>';
            echo '<td class="px-3 py-2 text-xs text-slate-600">' . (strlen($row['deskripsi']) > 100 ? htmlspecialchars(substr($row['deskripsi'], 0, 100)) . '...' : htmlspecialchars($row['deskripsi'])) . '</td>';
            echo '<td class="px-3 py-2 text-xs text-blue-600">';
            if (!empty($row['link'])) {
                echo '<a href="' . htmlspecialchars($row['link']) . '" target="_blank" class="hover:underline">Link →</a>';
            } else {
                echo '<span class="text-gray-400">-</span>';
            }
            echo '</td>';
            echo '<td class="px-3 py-2 whitespace-nowrap text-center text-sm text-slate-500">';
            echo '<a href="../crud/edit.php?tabel=akademik&id=' . $row['id'] . '" title="Edit"><i class="fa fa-edit text-blue-600 hover:text-blue-900 mr-3"></i></a>';
            echo '<a href="../crud/hapus.php?tabel=akademik&id=' . $row['id'] . '" title="Hapus" onclick="return confirm(\'Hapus berita ini?\');"><i class="fa fa-trash text-red-600 hover:text-red-900"></i></a>';
            echo '</td>';
            echo '</tr>';
        }
    } else {
        echo '<tr><td colspan="5" class="px-6 py-10 text-center text-xs text-gray-500 italic">Data berita tidak ditemukan.</td></tr>';
    }
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#6c27dc" />
    <link rel="icon" href="img/icon.png" />
    <title>Berita & Informasi - Dashboard PTIK C</title>
    <link href="../../frontend/tailwind/src/output.css" rel="stylesheet">
    <link href="../../frontend/tailwind/src/input.css" rel="stylesheet">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        .rotate-180 { transform: rotate(180deg); }
        @media (max-width: 768px) {
            .mobile-menu-open { transform: translateX(0) !important; }
        }
    </style>
</head>

<body class="bg-slate-100">
    <header class="bg-indigo-950 fixed w-full top-0 z-50">
        <div class="flex justify-between items-center px-6 py-2">
            <div class="flex items-center">
                <img src="../../img/logo.png" alt="Logo" class="h-10 w-10 mr-3">
                <span class="text-xl font-bold text-white">Dashboard PTIK C</span>
            </div>
            <button id="mobile-menu-button" class="text-slate-100 hover:text-indigo-950 lg:hidden p-2 rounded-lg hover:bg-gray-100">
                <i class="fa fa-bars w-6 h-5"></i>
            </button>
            <div class="hidden lg:flex items-center space-x-4">
                <div class="relative">
                    <button id="profile-button" class="flex items-center space-x-2">
                        <span class="text-white"><?= htmlspecialchars($nama_lengkap) ?></span>
                        <img src="../img/profile/<?= htmlspecialchars($foto) ?>" alt="Profile" class="w-8 h-8 rounded-full">
                    </button>
                    <div id="profile-dropdown" class="absolute right-0 mt-2 w-48 bg-white rounded-md shadow-lg hidden text-xs">
                        <a href="profile.php" class="block px-4 py-2 hover:bg-gray-100">Profile</a>
                        <a href="../../index.php" class="block px-4 py-2 hover:bg-gray-100">Logout</a>
                    </div>
                </div>
            </div>
        </div>
    </header>

    <aside id="sidebar" class="fixed left-0 top-0 h-screen w-48 bg-white transform -translate-x-full lg:translate-x-0 transition-transform duration-200 ease-in-out z-40">
        <div class="pt-16">
            <nav class="mt-6">
                <div class="px-4 space-y-2">
                    <a href="dashboard.php" class="flex items-center px-4 py-2 text-slate-700 rounded-lg hover:bg-slate-100">
                        <i class="fa fa-home w-4 h-4 mr-4"></i>
                        <span class="text-xs">Beranda</span>
                    </a>
                    <div class="space-y-2">
                        <button class="submenu-button flex items-center justify-between w-full px-4 py-2 text-slate-800 hover:bg-slate-100 rounded-lg">
                            <div class="flex items-center">
                                <i class="fa fa-list w-5 h-5 mr-4"></i>
                                <span class="text-xs">Akademik</span>
                            </div>
                            <i class="fa fa-chevron-down submenu-arrow w-4 h-4 transition-transform duration-200"></i>
                        </button>
                        <div class="submenu pl-8 space-y-1 hidden overflow-y-auto max-h-52">
                            <a href="db_mahasiswa.php" class="block px-4 py-2 text-xs text-slate-800 hover:bg-slate-100 rounded-lg">Mahasiswa</a>
                            <a href="db_dosen.php" class="block px-4 py-2 text-xs text-slate-800 hover:bg-slate-100 rounded-lg">Dosen</a>
                            <a href="db_matkul.php" class="block px-4 py-2 text-xs text-slate-800 hover:bg-slate-100 rounded-lg">Mata Kuliah</a>
                            <a href="db_jadwal.php" class="block px-4 py-2 text-xs text-slate-800 hover:bg-slate-100 rounded-lg">Jadwal</a>
                            <a href="db_tugas.php" class="block px-4 py-2 text-xs text-slate-800 hover:bg-slate-100 rounded-lg">Tugas</a>
                            <a href="db_berita.php" class="block px-4 py-2 text-xs text-white bg-indigo-600 rounded-lg">Berita</a>
                            <a href="db_kalender.php" class="block px-4 py-2 text-xs text-slate-800 hover:bg-slate-100 rounded-lg">Kalender</a>
                        </div>
                        <a href="db_absensi.php?matkul_id=33&pertemuan=1" class="flex items-center px-4 py-2 text-xs text-slate-800 hover:bg-gray-100 rounded-lg">
                            <i class="fa fa-calendar w-5 h-5 mr-4"></i>
                            <span>Absensi</span>
                        </a>
                        <a href="db_kas.php?minggu_ke=1" class="flex items-center px-4 py-2 text-xs text-slate-800 hover:bg-gray-100 rounded-lg">
                            <i class="fa fa-wallet w-5 h-5 mr-4"></i>
                            <span>Kas Mingguan</span>
                        </a>
                        <a href="db_kelompok.php" class="flex items-center px-4 py-2 text-xs text-slate-800 hover:bg-gray-100 rounded-lg">
                            <i class="fa fa-users w-5 h-5 mr-4"></i>
                            <span>Kelompok</span>
                        </a>
                        <a href="db_galeri.php" class="flex items-center px-4 py-2 text-xs text-slate-800 hover:bg-gray-100 rounded-lg">
                            <i class="fa fa-images w-5 h-5 mr-4"></i>
                            <span>Galeri</span>
                        </a>
                        <a href="db_pesan.php" class="flex items-center px-4 py-2 text-xs text-slate-800 hover:bg-gray-100 rounded-lg">
                            <i class="fa fa-envelope w-5 h-5 mr-4"></i>
                            <span>Pesan Anonim</span>
                        </a>
                        <a href="kuis_harian.php" class="flex items-center px-4 py-2 text-slate-800 hover:bg-gray-100 rounded-lg">
                            <i class="fa fa-gamepad w-5 h-5 mr-4"></i>
                            <span class="text-xs">Kuis Harian</span>
                        </a>
                        <?php if ($role === 'Administrator Utama'): ?>
                        <a href="db_users.php" class="flex items-center px-4 py-2 text-xs text-slate-800 hover:bg-gray-100 rounded-lg">
                            <i class="fa fa-user w-5 h-5 mr-4"></i>
                            <span>Users</span>
                        </a>
                        <?php endif; ?>
                    </div>
                </div>
            </nav>
        </div>
    </aside>

    <main class="ml-0 lg:ml-48 pt-20 p-6 flex flex-col min-h-screen">
        <div class="bg-white rounded-lg border border-slate-200 p-6 flex-grow w-full">
            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-6">
                <div>
                    <h1 class="text-xl font-bold text-slate-800">Data Berita & Informasi</h1>
                </div>
                <div class="flex-col sm:flex-row gap-2 w-full sm:w-auto">
                    <form method="GET" class="w-full">
                        <div class="flex flex-row gap-2 items-center">
                            <input id="searchInput" type="text" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="Cari berita..." 
                                class="basis-4/5 w-full px-3 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-1 focus:ring-slate-400 text-xs">
                            <a href="../crud/tambah.php?tabel=akademik" class="basis-1/5 bg-green-600 text-white px-3 py-2 rounded-lg hover:bg-green-800 text-center text-xs flex items-center justify-center" title="Tambah">
                                <i class="fa fa-plus"></i>
                            </a>
                        </div>
                    </form>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200">
                    <thead class="bg-indigo-900">
                        <tr>
                            <th class="px-3 py-3 text-left text-xs font-bold text-white uppercase tracking-wider">No</th>
                            <th class="px-3 py-3 text-left text-xs font-bold text-white uppercase tracking-wider">Judul</th>
                            <th class="px-3 py-3 text-left text-xs font-bold text-white uppercase tracking-wider">Deskripsi</th>
                            <th class="px-3 py-3 text-left text-xs font-bold text-white uppercase tracking-wider">Link</th>
                            <th class="px-3 py-3 text-left text-xs font-bold text-white uppercase tracking-wider text-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-slate-200">
                        <?php
                        $no = 1;
                        $id = 1;
                        if (mysqli_num_rows($result) > 0) {
                            while ($row = mysqli_fetch_assoc($result)) {
                                $rowClass = (($id++ % 2 == 0) ? 'bg-slate-100' : 'bg-white') . ' hover:bg-indigo-100';
                        ?>
                                <tr class="<?= $rowClass ?>">
                                    <td class="px-3 py-2 whitespace-nowrap text-xs text-slate-800"><?= $no++ ?></td>
                                    <td class="px-3 py-2 text-xs font-semibold text-slate-800"><?= htmlspecialchars($row['judul']) ?></td>
                                    <td class="px-3 py-2 text-xs text-slate-600">
                                        <?= strlen($row['deskripsi']) > 100 ? htmlspecialchars(substr($row['deskripsi'], 0, 100)) . '...' : htmlspecialchars($row['deskripsi']) ?>
                                    </td>
                                    <td class="px-3 py-2 text-xs text-blue-600">
                                        <?php if (!empty($row['link'])): ?>
                                            <a href="../../pages/<?= htmlspecialchars($row['link']) ?>" target="_blank" class="hover:underline">Link →</a>
                                        <?php else: ?>
                                            <span class="text-gray-400">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="px-3 py-2 whitespace-nowrap text-center text-sm text-slate-500">
                                        <a href="../crud/edit.php?tabel=akademik&id=<?= $row['id'] ?>" title="Edit" class="text-blue-600 hover:text-blue-900 mr-3">
                                            <i class="fa fa-edit"></i>
                                        </a>
                                        <a href="../crud/hapus.php?tabel=akademik&id=<?= $row['id'] ?>" title="Hapus" onclick="return confirm('Hapus berita ini?');" class="text-red-600 hover:text-red-900">
                                            <i class="fa fa-trash"></i>
                                        </a>
                                    </td>
                                </tr>
                        <?php
                            }
                        } else {
                            echo '<tr><td colspan="5" class="px-6 py-10 text-center text-xs text-gray-500 italic">Data berita tidak ditemukan.</td></tr>';
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>
        <footer class="text-xs text-indigo-900 text-center mt-6">
            © 2025 Kelas PTIK C - Teknik Informatika dan Komputer FT UNM. All rights reserved.
        </footer>
    </main>

    <script>
        const mobileMenuButton = document.getElementById('mobile-menu-button');
        const sidebar = document.getElementById('sidebar');

        mobileMenuButton.addEventListener('click', (e) => {
            e.stopPropagation();
            sidebar.classList.toggle('-translate-x-full');
            sidebar.classList.toggle('mobile-menu-open');
        });

        document.addEventListener('click', (e) => {
            if (window.innerWidth < 1024) {
                if (!sidebar.contains(e.target) && !mobileMenuButton.contains(e.target)) {
                    sidebar.classList.add('-translate-x-full');
                    sidebar.classList.remove('mobile-menu-open');
                }
            }
        });

        const submenuButtons = document.querySelectorAll('.submenu-button');
        submenuButtons.forEach(button => {
            button.addEventListener('click', () => {
                const submenu = button.nextElementSibling;
                const arrow = button.querySelector('.submenu-arrow');
                submenu.classList.toggle('hidden');
                if (arrow) arrow.classList.toggle('rotate-180');
            });
        });

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

        // Live search via AJAX: update table rows without leaving input focus
        (function(){
            const input = document.getElementById('searchInput');
            if (!input) return;
            let timer = null;
            input.addEventListener('input', function(){
                clearTimeout(timer);
                timer = setTimeout(() => {
                    const q = encodeURIComponent(input.value || '');
                    const url = window.location.pathname + '?search=' + q + '&ajax=1';
                    fetch(url, { credentials: 'same-origin' })
                        .then(r => r.text())
                        .then(html => {
                            const tbody = document.querySelector('table tbody');
                            if (tbody) tbody.innerHTML = html;
                            input.focus();
                        }).catch(err => console.error(err));
                }, 250);
            });
        })();
    </script>
</body>

</html>
