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


/* =====================
   AMBIL PERIODE AKTIF
   ===================== */
$q_periode = mysqli_query($koneksi, "SELECT id FROM periode WHERE status = 'aktif' LIMIT 1");
$periode = mysqli_fetch_assoc($q_periode);
$periode_id = $periode['id'];

$search = $_GET['search'] ?? '';

if (!empty($search)) {
    $stmt = mysqli_prepare(
        $koneksi,
        "SELECT 
    jm.id, jm.matkul_id, jm.hari, jm.jam_mulai_ke, jm.jam_selesai_ke, 
    jk_start.jam_mulai, jk_end.jam_selesai,
    r.nama_ruangan AS ruangan, mk.nama_matkul, mk.sks,
    dp.nama_dosen AS dosen_pengampu,
    dm.nama_dosen AS dosen_mitra
FROM jadwal_matkul jm
JOIN mata_kuliah mk ON jm.matkul_id = mk.id
LEFT JOIN ruangan r ON jm.ruangan_id = r.id
LEFT JOIN jam_kuliah jk_start ON jm.jam_mulai_ke = jk_start.jam_ke
LEFT JOIN jam_kuliah jk_end ON jm.jam_selesai_ke = jk_end.jam_ke
LEFT JOIN dosen dp ON jm.dosen_pengampu_id = dp.id
LEFT JOIN dosen dm ON jm.dosen_mitra_id = dm.id 
         WHERE (jm.hari LIKE ? 
         OR mk.nama_matkul LIKE ?)
         AND jm.periode_id = ?
         ORDER BY jm.hari, jk_start.jam_mulai"
    );

    $like = "%$search%";
    mysqli_stmt_bind_param($stmt, "ssi", $like, $like, $periode_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
} else {
    $result = mysqli_query($koneksi, "SELECT 
    jm.id, jm.matkul_id, jm.hari, jm.jam_mulai_ke, jm.jam_selesai_ke,
    jk_start.jam_mulai, jk_end.jam_selesai,
    r.nama_ruangan AS ruangan, mk.nama_matkul, mk.sks,
    dp.nama_dosen AS dosen_pengampu,
    dm.nama_dosen AS dosen_mitra
FROM jadwal_matkul jm
JOIN mata_kuliah mk ON jm.matkul_id = mk.id
LEFT JOIN ruangan r ON jm.ruangan_id = r.id
LEFT JOIN jam_kuliah jk_start ON jm.jam_mulai_ke = jk_start.jam_ke
LEFT JOIN jam_kuliah jk_end ON jm.jam_selesai_ke = jk_end.jam_ke
LEFT JOIN dosen dp ON jm.dosen_pengampu_id = dp.id
LEFT JOIN dosen dm ON jm.dosen_mitra_id = dm.id
WHERE jm.periode_id = $periode_id
ORDER BY jm.hari, jk_start.jam_mulai");
}

// If AJAX live-search request, return table rows only
if (isset($_GET['ajax']) && $_GET['ajax'] == '1') {
    $no = 1;
    $id = 1;
    while ($row = mysqli_fetch_assoc($result)) {
        $rowClass = (($id++ % 2 == 0) ? 'bg-slate-100' : 'bg-white') . ' hover:bg-indigo-100';
        echo '<tr class="' . $rowClass . '">';
        echo '<td class="px-3 py-2 whitespace-nowrap text-xs text-slate-800">' . ($no++) . '</td>';
        echo '<td class="px-3 py-2 whitespace-nowrap text-xs text-slate-800">' . htmlspecialchars($row['hari']) . '</td>';
        echo '<td class="px-3 py-2 whitespace-nowrap text-xs text-slate-800">' . htmlspecialchars($row['jam_mulai_ke']) . '-' . htmlspecialchars($row['jam_selesai_ke']) . '</td>';
        echo '<td class="px-3 py-2 whitespace-nowrap text-xs text-slate-800">' . date('H.i', strtotime($row['jam_mulai'])) . ' - ' . date('H.i', strtotime($row['jam_selesai'])) .'</td>';
        echo '<td class="px-3 py-2 whitespace-nowrap text-xs text-slate-800">' . htmlspecialchars($row['ruangan']) . '</td>';
        echo '<td class="px-3 py-2 text-xs text-slate-800">' . htmlspecialchars($row['nama_matkul']) . '</td>';
        echo '<td class="px-3 py-2 whitespace-nowrap text-xs text-slate-800">' . htmlspecialchars($row['sks']) . '</td>';
        echo '<td class="px-3 py-2 text-xs text-slate-800">' . htmlspecialchars($row['dosen_pengampu']) . '</td>';
        echo '<td class="px-3 py-2 text-xs text-slate-800">' . htmlspecialchars($row['dosen_mitra']) . '</td>';
        if ($role === 'Administrator Utama' || $role === 'Sekretaris'):
        echo '<td class="px-3 py-2 whitespace-nowrap text-sm text-slate-500">';
        echo '<a href="../crud/edit.php?tabel=jadwal_matkul&id=' . $row['id'] . '" title="Edit"><i class="fa fa-edit text-blue-600 hover:text-blue-900 mr-3"></i></a>';
        echo '<a href="../crud/hapus.php?tabel=jadwal_matkul&id=' . $row['id'] . '" title="Hapus" onclick="return confirm(\'Hapus jadwal ini?\');"><i class="fa fa-trash text-red-600 hover:text-red-900"></i></a>';
        echo '</td>';
        endif;
        echo '</tr>';
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
    <link rel="icon" href="../img/main/icon.png" />
    <title>Data Jadwal - PTIK C 2024</title>
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
            <!-- Session Alerts handled by SweetAlert -->

            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-6">
                <h3 class="text-xl font-bold text-slate-800">Jadwal Mata Kuliah</h3>
                <div class="flex-col sm:flex-row gap-2 w-full sm:w-auto">
                    <form method="GET" class="w-full">
                        <div class="flex flex-row gap-2 items-center">
                             <input
                                id="searchInput"
                                type="text"
                                name="search"
                                value="<?= htmlspecialchars($_GET['search'] ?? '') ?>"
                                placeholder="Cari Hari / Mata Kuliah..."
                                class="flex-grow px-3 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-1 focus:ring-slate-400 text-xs">

                            <?php if ($role === 'Administrator Utama' || $role === 'Sekretaris'): ?>
                            <button type="button" onclick="confirmTelegram()" class="bg-blue-600 text-white px-3 py-2 rounded-lg hover:bg-blue-800 text-center text-xs flex items-center justify-center" title="Kirim Telegram">
                                <i class="fa fa-paper-plane"></i>
                            </button>
                          

                            <a href="../crud/tambah.php?tabel=jadwal_matkul" class="bg-green-600 text-white px-3 py-2 rounded-lg hover:bg-green-800 text-center text-xs flex items-center justify-center" title="Tambah">
                                <i class="fa fa-plus"></i>
                            </a>
                            <?php endif; ?>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Rest of the content remains the same -->
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200">
                    <thead class="bg-indigo-900">
                        <tr>
                            <th class="px-3 py-3 text-left text-xs font-bold text-white uppercase tracking-wider">
                                No</th>
                            <th class="px-3 py-3 text-left text-xs font-bold text-white uppercase tracking-wider">
                                Hari</th>
                            <th class="px-3 py-3 text-left text-xs font-bold text-white uppercase tracking-wider">
                                Jam</th>
                            <th class="px-3 py-3 text-left text-xs font-bold text-white uppercase tracking-wider">
                                Pukul</th>
                            <th class="px-3 py-3 text-left text-xs font-bold text-white uppercase tracking-wider">
                                Ruangan</th>
                            <th class="px-3 py-3 text-left text-xs font-bold text-white uppercase tracking-wider">
                                Mata Kuliah</th>
                            <th class="px-3 py-3 text-left text-xs font-bold text-white uppercase tracking-wider">
                                SKS</th>
                            <th class="px-3 py-3 text-left text-xs font-bold text-white uppercase tracking-wider">
                                Dosen Pengampu</th>
                            <th class="px-3 py-3 text-left text-xs font-bold text-white uppercase tracking-wider">
                                Dosen Mitra</th>
<?php if ($role === 'Administrator Utama' || $role === 'Sekretaris'): ?>
                            <th class="px-3 py-3 text-left text-xs font-bold text-white uppercase tracking-wider">
                                Actions</th>
                                <?php endif; ?>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-slate-200">
                        <?php
                        $no = 1;
                        $id = 1;
                        while ($row = mysqli_fetch_assoc($result)) : ?>
                            <tr class="<?= ($id++ % 2 == 0) ? 'bg-slate-100' : 'bg-white' ?> hover:bg-indigo-100">

                                <td class="px-3 py-2 whitespace-nowrap text-xs text-slate-800"><?= $no++; ?></td>

                                <td class="px-3 py-2 whitespace-nowrap text-xs text-slate-800"><?= $row['hari']; ?></td>
                                <td class="px-3 py-2 whitespace-nowrap text-xs text-slate-800"><?= $row['jam_mulai_ke'] . '-' . $row['jam_selesai_ke']; ?></td>
                                <td class="px-3 py-2 whitespace-nowrap text-xs text-slate-800"><?= date('H.i', strtotime($row['jam_mulai'])); ?> - <?= date('H.i', strtotime($row['jam_selesai'])); ?></td>
                                
                                <td class="px-3 py-2 whitespace-nowrap text-xs text-slate-800"><?= $row['ruangan']; ?></td>
                                <td class="px-3 py-2 text-xs text-slate-800"><?= $row['nama_matkul']; ?></td>
                                <td class="px-3 py-2 whitespace-nowrap text-xs text-slate-800"><?= $row['sks']; ?></td>
                                <td class="px-3 py-2 text-xs text-slate-800"><?= $row['dosen_pengampu']; ?></td>
                                <td class="px-3 py-2 text-xs text-slate-800"><?= $row['dosen_mitra']; ?></td>
                                <?php if ($role === 'Administrator Utama' || $role === 'Sekretaris'): ?>
                                <td class="px-3 py-2 whitespace-nowrap text-sm text-slate-500">
                                    <a href="../crud/edit.php?tabel=jadwal_matkul&id=<?= $row['id']; ?>" title="Edit"><i class="fa fa-edit text-blue-600 hover:text-blue-900 mr-3"></i></a>

                                    <a href="../crud/hapus.php?tabel=jadwal_matkul&id=<?= $row['id']; ?>" title="Hapus" onclick="return confirm('Hapus jadwal ini?');"><i class="fa fa-trash text-red-600 hover:text-red-900"></i></a>

                                </td>
                                <?php endif; ?>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
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
                const arrow = button.querySelector('.submenu-arrow');

                submenu.classList.toggle('hidden');
                if (arrow) arrow.classList.toggle('rotate-180');
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

        // Live search via AJAX (debounced) - keep focus in input
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
                            if (tbody) tbody.innerHTML = html || '<tr><td colspan="11" class="px-3 py-2 text-xs text-slate-500">No results</td></tr>';
                            input.focus();
                        }).catch(err => console.error(err));
                }, 250);
            });
        })();

        // SweetAlert Confirmation for Telegram
        function confirmTelegram() {
            Swal.fire({
                title: 'Kirim Telegram?',
                text: "Jadwal kuliah besok akan dikirim ke grup.",
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Ya, Kirim!',
                cancelButtonText: 'Batal'
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = '../crud/kirim_jadwal.php';
                }
            });
        }

        // Show Session Alerts with SweetAlert
        <?php if (isset($_SESSION['success'])): ?>
            Swal.fire({
                icon: 'success',
                title: 'Berhasil!',
                text: '<?= addslashes($_SESSION['success']) ?>',
                confirmButtonColor: '#3085d6'
            });
            <?php unset($_SESSION['success']); ?>
        <?php endif; ?>

        <?php if (isset($_SESSION['error'])): ?>
            Swal.fire({
                icon: 'error',
                title: 'Gagal!',
                text: '<?= addslashes($_SESSION['error']) ?>',
                confirmButtonColor: '#d33'
            });
            <?php unset($_SESSION['error']); ?>
        <?php endif; ?>

        <?php if (isset($_SESSION['info'])): ?>
            Swal.fire({
                icon: 'info',
                title: 'Informasi',
                text: '<?= addslashes($_SESSION['info']) ?>',
                confirmButtonColor: '#3085d6'
            });
            <?php unset($_SESSION['info']); ?>
        <?php endif; ?>
    </script>
</body>

</html>
