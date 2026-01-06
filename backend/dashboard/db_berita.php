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
            if ($role === 'Administrator Utama'):
                echo '<td class="px-3 py-2 whitespace-nowrap text-center text-sm text-slate-500">';
                echo '<a href="../crud/edit.php?tabel=akademik&id=' . $row['id'] . '" title="Edit"><i class="fa fa-edit text-blue-600 hover:text-blue-900 mr-3"></i></a>';
                echo '<a href="../crud/hapus.php?tabel=akademik&id=' . $row['id'] . '" title="Hapus" onclick="return confirm(\'Hapus berita ini?\');"><i class="fa fa-trash text-red-600 hover:text-red-900"></i></a>';
                echo '</td>';
            endif;
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
    <meta name="theme-color" content="#1e1b4b" />
    <title>Data Berita - PTIK C 2024</title>
    <link rel="icon" href="../img/main/icon.webp" />
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="../../frontend/tailwind/src/output.css" rel="stylesheet">
    <link href="../../frontend/tailwind/src/input.css" rel="stylesheet">
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

<body class="bg-slate-100">
    <!-- Header dengan z-index lebih tinggi -->
    <header class="bg-indigo-950  fixed w-full top-0 z-50">
        <div class="flex justify-between items-center px-6 py-2">
            <div class="flex items-center">
                <img src="../img/main/logo.webp" alt="Logo Universitas Negeri Makassar" class="h-10 w-10 mr-3">
                <span class="text-xl font-bold text-white">Dashboard PTIK C</span>
            </div>
            <!-- Mobile Menu Button dengan z-index yang sesuai -->
            <button id="mobile-menu-button" class="text-slate-100 hover:text-indigo-950 lg:hidden p-2 rounded-lg hover:bg-gray-100">
                <i class="fa fa-bars w-6 h-6"></i>
            </button>
            <div class="hidden lg:flex items-center space-x-4">
                <div class="relative">
                    <button id="profile-button" class="flex items-center space-x-2">
                        <span class="text-white font-semibold"><?php echo htmlspecialchars($nama_lengkap); ?></span>
                        <img src="../img/profile/<?php echo htmlspecialchars($foto); ?>" alt="Profile" class="w-8 h-8 rounded-full">
                    </button>
                    <div id="profile-dropdown" class="absolute right-0 mt-2 w-40 bg-white rounded-md shadow-lg hidden">
                        <a href="profile.php" class="block px-4 py-2 text-xs hover:bg-gray-100 font-semibold text-indigo-900"><i class="fa fa-user mr-2 text-indigo-900"></i>Profile</a>
                        <a href="../../index.php" class="block px-4 py-2 text-xs hover:bg-gray-100 font-semibold text-indigo-900"><i class="fa fa-sign-out mr-2 text-indigo-900"></i>Logout</a>
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
            <nav class="mt-4 h-[calc(100vh-4rem)] overflow-y-auto pb-10">
                <div class="px-4 space-y-2">
                    <!-- Dashboard Menu -->
                    <a href="dashboard.php" class="flex items-center px-4 py-2 text-indigo-900  rounded-lg hover:bg-slate-100">
                        <i class="fa fa-home w-4 h-4 mr-4"></i>
                        <span class="text-xs font-medium">Dashboard</span>
                    </a>



                    <!-- Components Menu -->
                    <div class="space-y-1">
                        <div>
                            <p class="px-4 text-[10px] font-semibold text-slate-400 uppercase tracking-wider mb-2">Akademik</p>
                            <div class="space-y-1">
                                <button class="submenu-button flex items-center justify-between w-full px-4 py-1 text-slate-800 hover:bg-slate-100 rounded-lg transition-colors">
                                    <div class="flex items-center">
                                        <i class="fa fa-university w-5 h-5 mr-3 text-indigo-900"></i>
                                        <span class="text-xs font-medium">Master</span>
                                    </div>
                                    <i class="fa fa-chevron-down submenu-arrow w-3 h-3 transition-transform duration-200"></i>
                                </button>
                                <div class="submenu pl-1 space-y-1 hidden overflow-y-auto max-h-52 border-l-2 border-slate-100 ml-6">
                                    <a href="db_mahasiswa.php" class="block px-4 py-1 text-xs text-slate-600 hover:text-indigo-600 hover:bg-slate-50 rounded-lg">Mahasiswa</a>
                                    <a href="db_dosen.php" class="block px-4 py-1 text-xs text-slate-600 hover:text-indigo-600 hover:bg-slate-50 rounded-lg">Dosen</a>
                                    <a href="db_matkul.php" class="block px-4 py-1 text-xs text-slate-600 hover:text-indigo-600 hover:bg-slate-50 rounded-lg">Mata Kuliah</a>
                                    <a href="db_berita.php" class="block px-4 py-1 text-xs text-slate-600 hover:text-indigo-600 hover:bg-slate-50 rounded-lg">Berita & Info</a>
                                    <a href="db_kalender.php" class="block px-4 py-1 text-xs text-slate-600 hover:text-indigo-600 hover:bg-slate-50 rounded-lg">Kalender Akademik</a>
                                    <a href="db_galeri.php" class="block px-4 py-1 text-xs text-slate-600 hover:text-indigo-600 hover:bg-slate-50 rounded-lg">Galeri Kelas</a>
                                </div>

                                <a href="db_jadwal.php" class="flex items-center px-4 py-1 text-slate-800 hover:bg-slate-100 rounded-lg transition-colors">
                                    <i class="fa fa-list-alt w-5 h-5 mr-3 text-indigo-900"></i>
                                    <span class="text-xs font-medium">Jadwal Kuliah</span>
                                </a>

                                <a href="db_tugas.php" class="flex items-center px-4 py-1 text-slate-800 hover:bg-slate-100 rounded-lg transition-colors">
                                    <i class="fa fa-tasks w-5 h-5 mr-3 text-indigo-900"></i>
                                    <span class="text-xs font-medium">Tugas Kuliah</span>
                                </a>
                                <a href="db_kelompok.php" class="flex items-center px-4 py-1 text-slate-800 hover:bg-slate-100 rounded-lg transition-colors">
                                    <i class="fa fa-users w-5 h-5 mr-3 text-indigo-900"></i>
                                    <span class="text-xs font-medium">Kelompok</span>
                                </a>
                                <a href="kalender_view.php" class="flex items-center px-4 py-1 text-slate-800 hover:bg-slate-100 rounded-lg transition-colors">
                                    <i class="fa fa-calendar w-5 h-5 mr-3 text-indigo-900"></i>
                                    <span class="text-xs font-medium">Kalender</span>
                                </a>

                            </div>
                        </div>

                        <div>
                            <p class="px-4 text-[10px] font-semibold text-slate-400 uppercase tracking-wider mb-2">Administrasi</p>
                            <div class="space-y-1">
                                <button class="submenu-button flex items-center justify-between w-full px-4 py-1 text-slate-800 hover:bg-slate-100 rounded-lg transition-colors">
                                    <div class="flex items-center">
                                        <i class="fa fa-clipboard-list w-5 h-5 mr-3 text-indigo-900"></i>
                                        <span class="text-xs font-medium">Absensi</span>
                                    </div>
                                    <i class="fa fa-chevron-down submenu-arrow w-3 h-3 transition-transform duration-200"></i>
                                </button>
                                <div class="submenu pl-1 space-y-1 hidden overflow-y-auto max-h-52 border-l-2 border-slate-100 ml-6">
                                    <a href="db_absensi.php" class="block px-4 py-1 text-xs text-slate-600 hover:text-indigo-600 hover:bg-slate-50 rounded-lg">Data Absen</a>
                                    <?php if ($role === 'Administrator Utama' || $role === 'Sekretaris'): ?>
                                        <a href="absensi.php" class="block px-4 py-1 text-xs text-slate-600 hover:text-indigo-600 hover:bg-slate-50 rounded-lg">Input Absen</a>
                                    <?php endif; ?>
                                </div>

                                <button class="submenu-button flex items-center justify-between w-full px-4 py-1 text-slate-800 hover:bg-slate-100 rounded-lg transition-colors">
                                    <div class="flex items-center">
                                        <i class="fa fa-wallet w-5 h-5 mr-3 text-indigo-900"></i>
                                        <span class="text-xs font-medium">Keuangan</span>
                                    </div>
                                    <i class="fa fa-chevron-down submenu-arrow w-3 h-3 transition-transform duration-200"></i>
                                </button>
                                <div class="submenu pl-1 space-y-1 hidden overflow-y-auto max-h-52 border-l-2 border-slate-100 ml-6">
                                    <a href="db_kas.php" class="block px-4 py-1 text-xs text-slate-600 hover:text-indigo-600 hover:bg-slate-50 rounded-lg">Data Kas</a>
                                    <?php if ($role === 'Administrator Utama' || $role === 'Bendahara'): ?>
                                        <a href="arus_kas.php" class="block px-4 py-1 text-xs text-slate-600 hover:text-indigo-600 hover:bg-slate-50 rounded-lg">Arus Kas</a>
                                        <a href="kas.php" class="block px-4 py-1 text-xs text-slate-600 hover:text-indigo-600 hover:bg-slate-50 rounded-lg">Input Per Minggu</a>
                                        <a href="kas_individu.php" class="block px-4 py-1 text-xs text-slate-600 hover:text-indigo-600 hover:bg-slate-50 rounded-lg">Input Per Individu</a>
                                    <?php endif; ?>
                                </div>


                            </div>
                        </div>

                        <div>
                            <p class="px-4 text-[10px] font-semibold text-slate-400 uppercase tracking-wider mb-2">Aktivitas</p>
                            <div class="space-y-1">




                                <a href="misi.php" class="flex items-center px-4 py-1 text-slate-800 hover:bg-slate-100 rounded-lg transition-colors">
                                    <i class="fa fa-trophy w-5 h-5 mr-3 text-indigo-900"></i>
                                    <span class="text-xs font-medium">Pusat Misi</span>
                                </a>
                                <a href="kuis_harian.php" class="flex items-center px-4 py-1 text-slate-800 hover:bg-slate-100 rounded-lg transition-colors">
                                    <i class="fa fa-gamepad w-5 h-5 mr-3 text-indigo-900"></i>
                                    <span class="text-xs font-medium">Kuis Harian</span>
                                </a>
                                <a href="db_pesan.php" class="flex items-center px-4 py-1 text-slate-800 hover:bg-slate-100 rounded-lg transition-colors">
                                    <i class="fa fa-comments w-5 h-5 mr-3 text-indigo-900"></i>
                                    <span class="text-xs font-medium">Pesan Anonim</span>
                                </a>

                            </div>
                        </div>


                        <div>
                            <p class="px-4 text-[10px] font-semibold text-slate-400 uppercase tracking-wider mb-2">Pengaturan</p>
                            <div class="space-y-1">
                                <button class="submenu-button flex items-center justify-between w-full px-4 py-1 text-slate-800 hover:bg-slate-100 rounded-lg transition-colors">
                                    <div class="flex items-center">
                                        <i class="fa fa-user w-5 h-5 mr-3 text-indigo-900"></i>
                                        <span class="text-xs font-medium">Akun</span>
                                    </div>
                                    <i class="fa fa-chevron-down submenu-arrow w-3 h-3 transition-transform duration-200"></i>
                                </button>
                                <div class="submenu pl-1 space-y-1 hidden overflow-y-auto max-h-52 border-l-2 border-slate-100 ml-6">
                                    <a href="profile.php" class="block px-4 py-1 text-xs text-slate-600 hover:text-indigo-600 hover:bg-slate-50 rounded-lg">Profile Saya</a>
                                    <a href="krs.php" class="block px-4 py-1 text-xs text-slate-600 hover:text-indigo-600 hover:bg-slate-50 rounded-lg">KRS</a>

                                </div>
                                <?php if ($role === 'Administrator Utama'): ?>
                                    <button class="submenu-button flex items-center justify-between w-full px-4 py-1 text-slate-800 hover:bg-slate-100 rounded-lg transition-colors">
                                        <div class="flex items-center">
                                            <i class="fa-solid fa-earth-americas w-5 h-5 mr-3 text-indigo-900"></i>
                                            <span class="text-xs font-medium">Website</span>
                                        </div>
                                        <i class="fa fa-chevron-down submenu-arrow w-3 h-3 transition-transform duration-200"></i>
                                    </button>
                                    <div class="submenu pl-1 space-y-1 hidden overflow-y-auto max-h-52 border-l-2 border-slate-100 ml-6">
                                        <a href="db_users.php" class="block px-4 py-1 text-xs text-slate-600 hover:text-indigo-600 hover:bg-slate-50 rounded-lg">Users</a>
                                        <a href="db_periode.php" class="block px-4 py-1 text-xs text-slate-600 hover:text-indigo-600 hover:bg-slate-50 rounded-lg">Periode</a>
                                        <a href="reminder.php" class="block px-4 py-1 text-xs text-slate-600 hover:text-indigo-600 hover:bg-slate-50 rounded-lg">Reminder</a>
                                    </div>
                                <?php endif; ?>


                                <a href="tentang.php" class="flex items-center px-4 py-1 text-slate-800 hover:bg-slate-100 rounded-lg transition-colors">
                                    <i class="fa fa-info-circle w-5 h-5 mr-3 text-indigo-900"></i>
                                    <span class="text-xs font-medium">Tentang Aplikasi</span>
                                </a>

                                <a href="../../index.php" class="flex items-center px-4 py-1 text-slate-800 hover:bg-red-50 hover:text-red-700 rounded-lg transition-colors">
                                    <i class="fa fa-sign-out-alt w-5 h-5 mr-3 text-indigo-900"></i>
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
            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-6">
                <div>
                    <h1 class="text-xl font-bold text-slate-800">Data Berita & Informasi</h1>
                </div>
                <div class="flex-col sm:flex-row gap-2 w-full sm:w-auto">
                    <form method="GET" class="w-full">
                        <div class="flex flex-row gap-2 items-center">
                            <input id="searchInput" type="text" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="Cari berita..."
                                class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-1 focus:ring-slate-400 text-xs">
                            <?php if ($role === 'Administrator Utama'): ?>
                                <a href="../crud/tambah.php?tabel=akademik" class=" bg-green-600 text-white px-3 py-2 rounded-lg hover:bg-green-800 text-center text-xs flex items-center justify-center" title="Tambah">
                                    <i class="fa fa-plus"></i>
                                </a>
                            <?php endif; ?>
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
                            <?php if ($role === 'Administrator Utama'): ?>
                                <th class="px-3 py-3 text-xs font-bold text-white uppercase tracking-wider text-center">Actions</th>
                            <?php endif; ?>
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
                                    <td class="px-3 py-2 text-xs text-slate-800"><?= htmlspecialchars($row['judul']) ?></td>
                                    <td class="px-3 py-2 text-xs text-slate-800">
                                        <?= strlen($row['deskripsi']) > 120 ? htmlspecialchars(substr($row['deskripsi'], 0, 120)) . '...' : htmlspecialchars($row['deskripsi']) ?>
                                    </td>
                                    <td class="px-3 py-2 text-xs text-blue-600">
                                        <?php if (!empty($row['link'])): ?>
                                            <a href="../../pages/<?= htmlspecialchars($row['link']) ?>" target="_blank" class="hover:underline">Link →</a>
                                        <?php else: ?>
                                            <span class="text-gray-400">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <?php if ($role === 'Administrator Utama'): ?>
                                        <td class="px-3 py-2 whitespace-nowrap text-center text-sm text-slate-500">
                                            <a href="../crud/edit.php?tabel=akademik&id=<?= $row['id'] ?>" title="Edit" class="text-blue-600 hover:text-blue-900 mr-3">
                                                <i class="fa fa-edit"></i>
                                            </a>
                                            <a href="../crud/hapus.php?tabel=akademik&id=<?= $row['id'] ?>" title="Hapus" onclick="return confirm('Hapus berita ini?');" class="text-red-600 hover:text-red-900">
                                                <i class="fa fa-trash"></i>
                                            </a>
                                        </td>
                                    <?php endif; ?>
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
        (function() {
            const input = document.getElementById('searchInput');
            if (!input) return;
            let timer = null;
            input.addEventListener('input', function() {
                clearTimeout(timer);
                timer = setTimeout(() => {
                    const q = encodeURIComponent(input.value || '');
                    const url = window.location.pathname + '?search=' + q + '&ajax=1';
                    fetch(url, {
                            credentials: 'same-origin'
                        })
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