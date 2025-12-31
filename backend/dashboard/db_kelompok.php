<?php
session_start();
include '../../koneksi.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../../index.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$nama_lengkap = $_SESSION['nama_lengkap'] ?? '';
$foto = $_SESSION['foto'] ?? '';
$role = $_SESSION['role'];


/* =====================
   AMBIL PERIODE AKTIF
   ===================== */
$q_periode = mysqli_query($koneksi, "SELECT id FROM periode WHERE status = 'aktif' LIMIT 1");
$periode = mysqli_fetch_assoc($q_periode);
$periode_id = $periode['id'];

// Ambil daftar mata kuliah yang punya kelompok tersimpan
$matkuls_res = mysqli_query($koneksi, "SELECT DISTINCT k.matkul_id AS id, mk.nama_matkul
	FROM kelompok k
	JOIN mata_kuliah mk ON k.matkul_id = mk.id
	WHERE k.periode_id = $periode_id
	ORDER BY mk.semester, mk.nama_matkul");

$selected_matkul = intval($_GET['matkul_id'] ?? 0);
$sets = [];
$selected_set_idx = intval($_GET['set_idx'] ?? 0);
$groups = [];
$student_map = [];

if ($selected_matkul) {
    // Get all unique sets for this matkul
    $q_sets = mysqli_query($koneksi, "SELECT DISTINCT dosen, keterangan FROM kelompok WHERE matkul_id = $selected_matkul AND periode_id = $periode_id");
    while ($rs = mysqli_fetch_assoc($q_sets)) {
        $sets[] = $rs;
    }

    if (count($sets) > 0) {
        $current_set = $sets[$selected_set_idx] ?? $sets[0];
        $dosen_filter = mysqli_real_escape_string($koneksi, $current_set['dosen']);
        $keterangan_filter = mysqli_real_escape_string($koneksi, $current_set['keterangan']);

        $stmt = mysqli_prepare($koneksi, "SELECT kelompok_no, anggota FROM kelompok WHERE matkul_id = ? AND periode_id = ? AND dosen = ? AND keterangan = ? ORDER BY kelompok_no");
        mysqli_stmt_bind_param($stmt, 'iiss', $selected_matkul, $periode_id, $dosen_filter, $keterangan_filter);
        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);
        while ($r = mysqli_fetch_assoc($res)) {
            $groups[] = $r;
        }

        // Kumpulkan semua NIM dari semua kelompok, lalu ambil nama mahasiswa
        $all_nims = [];
        foreach ($groups as $g) {
            $parts = array_filter(array_map('trim', explode(',', $g['anggota'])));
            foreach ($parts as $nim) {
                if ($nim !== '') $all_nims[$nim] = true;
            }
        }

        if (count($all_nims) > 0) {
            $escaped = array_map(function ($s) use ($koneksi) {
                return "'" . mysqli_real_escape_string($koneksi, $s) . "'";
            }, array_keys($all_nims));
            $inlist = implode(',', $escaped);
            $res2 = mysqli_query($koneksi, "SELECT nim, nama_lengkap FROM mahasiswa WHERE nim IN ($inlist)");
            while ($r = mysqli_fetch_assoc($res2)) {
                $student_map[$r['nim']] = $r['nama_lengkap'];
            }
        }
    }
}

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#1e1b4b" />
    <link rel="icon" href="../img/main/icon.png" />
    <title>Data Kelompok - PTIK C 2024</title>
    <link href="../../frontend/tailwind/src/output.css" rel="stylesheet">
    <link href="../../frontend/tailwind/src/input.css" rel="stylesheet">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&display=swap" rel="stylesheet">
    <!-- Font Awesome -->
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

        /* live-search script removed from style block; see script at document end */
    </style>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>

<body class="bg-slate-100">
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

                        <?php if ($role === 'Administrator Utama' || $role === 'Ketua Tingkat'): ?>
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
            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-2">
                <h3 class="text-xl font-bold text-slate-800 mb-4">Data Kelompok</h3>
                <?php if ($role === 'Administrator Utama' || $role === 'Ketua Tingkat'): ?>
                    <div class="flex flex-col sm:flex-row gap-2 w-full sm:w-auto">
                        <form method="GET" class="flex flex-col sm:flex-row gap-4 w-full sm:w-auto">




                            <?php if ($selected_matkul): ?>
                                <?php if (count($sets) > 0): ?>
                                    <a href="edit_kelompok.php?matkul_id=<?= $selected_matkul ?>&dosen=<?= urlencode($current_set['dosen']) ?>&keterangan=<?= urlencode($current_set['keterangan']) ?>" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-800 text-center text-xs">
                                        <i class="fa fa-edit mr-2"></i> Edit Kelompok
                                    </a>
                                <?php endif; ?>
                                <a href="kelompok.php?matkul_id=<?= $selected_matkul ?>" class="bg-indigo-600 text-white px-4 py-2 rounded-lg hover:bg-indigo-800 text-center text-xs">
                                    <i class="fa fa-plus mr-2"></i> Atur Kelompok Baru
                                </a>
                            <?php else: ?>
                                <a href="kelompok.php" class="bg-indigo-600 text-white px-4 py-2 rounded-lg hover:bg-indigo-800 text-center text-xs">
                                    <i class="fa fa-plus mr-2"></i> Atur Kelompok Baru
                                </a>
                            <?php endif; ?>
                        </form>
                    </div>
                <?php endif; ?>
            </div>


            <form id="selectForm" method="GET" class="mb-4 grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="text-xs block mb-1">Pilih Mata Kuliah:</label>
                    <select id="mata_kuliah" name="matkul_id" class="w-full px-3 py-2 border rounded text-xs" onchange="this.form.submit()">
                        <option value="">-- Pilih --</option>
                        <?php while ($mk = mysqli_fetch_assoc($matkuls_res)) : ?>
                            <option value="<?= $mk['id'] ?>" <?= ($mk['id'] == $selected_matkul) ? 'selected' : '' ?>><?= htmlspecialchars($mk['nama_matkul']) ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <?php if (count($sets) > 1): ?>
                    <div>
                        <label class="text-xs block mb-1">Pilih Set Kelompok (Dosen/Keterangan):</label>
                        <select name="set_idx" class="w-full px-3 py-2 border rounded text-xs" onchange="this.form.submit()">
                            <?php foreach ($sets as $idx => $s): ?>
                                <option value="<?= $idx ?>" <?= $idx == $selected_set_idx ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($s['dosen']) ?> <?= !empty($s['keterangan']) ? '(' . htmlspecialchars($s['keterangan']) . ')' : '' ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                <?php endif; ?>
            </form>

            <?php if ($selected_matkul): ?>
                <?php if (count($sets) === 0): ?>
                    <div class="p-4 bg-yellow-100 text-yellow-800 text-xs">Tidak ada kelompok tersimpan untuk mata kuliah ini.</div>
                <?php else: ?>
                    <div class="mb-4 p-3 bg-indigo-50 border border-indigo-100 rounded-lg">
                        <div class="text-[10px] font-bold text-indigo-400 uppercase tracking-wider mb-1">Set Aktif</div>
                        <div class="text-sm font-bold text-indigo-900">
                            👨‍🏫 <?= htmlspecialchars($current_set['dosen']) ?>
                            <?php if (!empty($current_set['keterangan'])): ?>
                                <span class="ml-2 px-2 py-0.5 bg-indigo-200 text-indigo-800 rounded text-[10px] uppercase font-bold"><?= htmlspecialchars($current_set['keterangan']) ?></span>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div id="groupsContainer" class="flex flex-wrap gap-4 justify-between">
                        <?php foreach ($groups as $idx => $g): ?>
                            <div class="p-4 border rounded bg-slate-50 w-56 shadow-sm hover:shadow-md transition-shadow">
                                <h4 class="font-bold mb-3 text-indigo-900 border-b border-indigo-100 pb-2 text-sm">Kelompok <?= htmlspecialchars($g['kelompok_no']) ?></h4>
                                <ul class="space-y-2 text-xs">
                                    <?php
                                    $parts = array_filter(array_map('trim', explode(',', $g['anggota'])));
                                    foreach ($parts as $nim):
                                        $name = $student_map[$nim] ?? $nim;
                                    ?>
                                        <li class="flex items-center gap-2">
                                            <div class="h-1.5 w-1.5 rounded-full bg-indigo-400"></div>
                                            <span class="text-slate-700"><?= htmlspecialchars($name) ?></span>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            <?php else: ?>
                <div class="p-3 text-xs text-slate-600">Pilih mata kuliah untuk melihat kelompok yang tersimpan.</div>
            <?php endif; ?>

        </div>
        <footer class="text-xs text-indigo-900 text-center mb-0 pb-0 mt-6">
            © 2025 Kelas PTIK C - Teknik Informatika dan Komputer FT UNM. All rights reserved.
        </footer>
    </main>

    <script>
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

        document.getElementById('mata_kuliah').addEventListener('change', function() {
            document.getElementById('selectForm').submit();
        });

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
                text: '<?= $_SESSION['error'] ?>',
                timer: 3000,
                showConfirmButton: false
            });
            <?php unset($_SESSION['error']); ?>
        <?php endif; ?>
    </script>

</body>

</html>