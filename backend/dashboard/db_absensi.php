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

$query_matkul = "
SELECT DISTINCT
    mk.id AS matkul_id,
    mk.nama_matkul
FROM krs k
JOIN mata_kuliah mk ON k.matkul_id = mk.id
WHERE k.periode_id = $periode_id
ORDER BY mk.nama_matkul ASC
";

$matkul = mysqli_query($koneksi, $query_matkul);

if (!$matkul) {
    die('Query matkul error: ' . mysqli_error($koneksi));
}
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $matkul_id = $_POST['matkul_id'];
    $pertemuan = $_POST['pertemuan'];
    $status    = $_POST['status'];

    foreach ($status as $mahasiswa_id => $st) {

        $query = "
        INSERT INTO absensi (mahasiswa_id, matkul_id, pertemuan, status)
        VALUES (?, ?, ?, ?)
        ON DUPLICATE KEY UPDATE status = ?
        ";

        $stmt = mysqli_prepare($koneksi, $query);
        mysqli_stmt_bind_param(
            $stmt,
            "iiiss",
            $mahasiswa_id,
            $matkul_id,
            $pertemuan,
            $st,
            $st
        );
        mysqli_stmt_execute($stmt);

        
    }
    // redirect agar tidak double submit
    header("Location: absensi.php?matkul_id=$matkul_id&pertemuan=$pertemuan");
    exit;
}
// CSV export handler (top of file) - responds to ?export=csv&pertemuan=...&matkul_id=...&filter=...
if (isset($_GET['export']) && $_GET['export'] === 'csv' && isset($_GET['pertemuan']) && isset($_GET['matkul_id'])) {
    $pertemuan = (int) $_GET['pertemuan'];
    $matkul_id = (int) $_GET['matkul_id'];
    $filter = $_GET['filter'] ?? 'all';
    $filter = in_array($filter, ['all', 'Hadir', 'Izin', 'Sakit', 'Alpha']) ? $filter : 'all';

    $where_clause = '';
    if ($filter !== 'all') {
        $where_clause = " AND a.status = '" . mysqli_real_escape_string($koneksi, $filter) . "'";
    }

    $q = "SELECT DISTINCT
            m.id AS mahasiswa_id,
            m.nim,
            m.nama_lengkap,
            COALESCE(a.status, '') AS status
        FROM krs k
        JOIN mahasiswa m ON k.mahasiswa_id = m.id
        LEFT JOIN absensi a 
            ON a.mahasiswa_id = m.id
        AND a.matkul_id = k.matkul_id
            AND a.pertemuan = ?
        WHERE k.matkul_id = ?
        AND k.periode_id = ?
        " . $where_clause . "
        ORDER BY m.nim ASC";

    $stmt = mysqli_prepare($koneksi, $q);
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, 'iii', $pertemuan, $matkul_id, $periode_id);
        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);

        // Resolve mata kuliah name for filename
        $matkul_name = '';
        $mq = mysqli_prepare($koneksi, "SELECT nama_matkul FROM mata_kuliah WHERE id = ?");
        if ($mq) {
            mysqli_stmt_bind_param($mq, 'i', $matkul_id);
            mysqli_stmt_execute($mq);
            $mres = mysqli_stmt_get_result($mq);
            if ($mrow = mysqli_fetch_assoc($mres)) {
                $matkul_name = $mrow['nama_matkul'];
            }
        }

        // sanitize name for filename
        $safe_name = $matkul_name ?: 'matkul_' . $matkul_id;
        $safe_name = preg_replace('/[^A-Za-z0-9 _-]/', '', $safe_name);
        $safe_name = trim($safe_name);
        $safe_name = str_replace(' ', '_', $safe_name);
        $safe_name = substr($safe_name, 0, 80);

        $filename = 'absensi_' . $safe_name . '_p' . $pertemuan . '.csv';

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        $out = fopen('php://output', 'w');
        fputcsv($out, ['NIM', 'Nama Lengkap', 'Status']);
        while ($r = mysqli_fetch_assoc($res)) {
            // Ensure NIM is numeric when possible
            $nim_value = is_numeric($r['nim']) ? ($r['nim'] + 0) : $r['nim'];
            fputcsv($out, [$nim_value, $r['nama_lengkap'], $r['status']]);
        }
        fclose($out);
        exit;
    } else {
        // fallback: query error
        die('Query error: ' . mysqli_error($koneksi));
    }
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
            <!-- Modified this section for better mobile responsiveness -->
            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-6">
                <h3 class="text-xl font-bold text-slate-800">Data Absensi</h3>
                <div class="flex flex-col sm:flex-row gap-2 w-full sm:w-auto">
                    <form method="GET" class="flex flex-col sm:flex-row gap-4 w-full sm:w-auto">
                        

                        <a href="?export=csv&matkul_id=<?= urlencode($_GET['matkul_id'] ?? '') ?>&pertemuan=<?= $_GET['pertemuan'] ?? '' ?>&filter=<?= urlencode($_GET['filter'] ?? 'all') ?>" class=" bg-green-600 text-white px-4 py-2 rounded-lg hover:bg-green-800 text-center text-xs flex items-center justify-center">Export CSV</a>
                        
                        <a href="absensi.php?matkul_id=<?= $_GET['matkul_id']; ?>&pertemuan=<?= $_GET['pertemuan']; ?>" class="bg-indigo-600 text-white px-4 py-2 rounded-lg hover:bg-indigo-800 text-center text-xs">
                           
                                Absensi
                          
                        </a>
                    </form>
                </div>
            </div>
            <form method="GET" class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">

                <select name="matkul_id" class="border p-2 rounded-lg text-xs" required onchange="this.form.submit()">
                    <option value="">-- Pilih Mata Kuliah --</option>
                    <?php while ($m = mysqli_fetch_assoc($matkul)): ?>
                        <option value="<?= $m['matkul_id'] ?>"
                            <?= (isset($_GET['matkul_id']) && $_GET['matkul_id'] == $m['matkul_id']) ? 'selected' : '' ?>>
                            <?= $m['nama_matkul'] ?>
                        </option>
                    <?php endwhile; ?>
                </select>


                <select name="pertemuan" class="border p-2 rounded-lg text-xs" required onchange="this.form.submit()">
                    <option value="">-- Pertemuan --</option>
                    <?php for ($i = 1; $i <= 16; $i++): ?>
                        <option value="<?= $i ?>"
                            <?= ($_GET['pertemuan'] ?? '') == $i ? 'selected' : '' ?>>
                            Pertemuan <?= $i ?>
                        </option>
                    <?php endfor; ?>
                </select>

                <label class="sr-only">Filter</label>
            <select name="filter" onchange="this.form.submit()" class="border rounded px-4 py-2 text-xs">
                <option value="all" <?= ($_GET['filter'] ?? 'all') == 'all' ? 'selected' : '' ?>>Semua</option>
                <option value="Hadir" <?= ($_GET['filter'] ?? '') == 'Hadir' ? 'selected' : '' ?>>Hadir</option>
                <option value="Izin" <?= ($_GET['filter'] ?? '') == 'Izin' ? 'selected' : '' ?>>Izin</option>
                <option value="Sakit" <?= ($_GET['filter'] ?? '') == 'Sakit' ? 'selected' : '' ?>>Sakit</option>
                <option value="Alpha" <?= ($_GET['filter'] ?? '') == 'Alpha' ? 'selected' : '' ?>>Alpha</option>
            </select>
                <!-- tombol Tampilkan dihapus; pilihan akan auto-submit -->
            </form>

           <?php
if (isset($_GET['matkul_id'], $_GET['pertemuan'])) :

    $matkul_id = (int) $_GET['matkul_id'];
    $pertemuan = (int) $_GET['pertemuan'];

    $filter = $_GET['filter'] ?? 'all';
    $filter = in_array($filter, ['all', 'Hadir', 'Izin', 'Sakit', 'Alpha']) ? $filter : 'all';

    $where_clause = '';
    if ($filter !== 'all') {
        $where_clause = " AND a.status = '$filter'";
    }

    $query = "
        SELECT DISTINCT
            m.id AS mahasiswa_id,
            m.nim,
            m.nama_lengkap,
            a.status
        FROM krs k
        JOIN mahasiswa m ON k.mahasiswa_id = m.id
        LEFT JOIN absensi a 
            ON a.mahasiswa_id = m.id
            AND a.matkul_id = k.matkul_id
            AND a.pertemuan = ?
        WHERE k.matkul_id = ?
        AND k.periode_id = ?
        $where_clause
        ORDER BY m.nim ASC
    ";

    $stmt = mysqli_prepare($koneksi, $query);
    mysqli_stmt_bind_param($stmt, "iii", $pertemuan, $matkul_id, $periode_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
?>


            <!-- Rest of the content remains the same -->
            <div class="overflow-x-auto">
                <form method="POST">
                    <input type="hidden" name="matkul_id" value="<?= $matkul_id ?>">
                    <input type="hidden" name="pertemuan" value="<?= $pertemuan ?>">
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
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-slate-200">
                            <?php
                            $no = 1;
                            $id = 1;
                            while ($row = mysqli_fetch_assoc($result)) : ?>
                                <tr class="<?= ($id++ % 2 == 0) ? 'bg-slate-100' : 'bg-white' ?> hover:bg-indigo-100">

                                    <td class="px-3 py-2 whitespace-nowrap text-xs text-slate-800"><?= $no++; ?></td>
                                    <td class="px-3 py-2 whitespace-nowrap text-xs text-slate-800"><?= $row['nim']; ?></td>
                                    <td class="px-3 py-2 whitespace-nowrap text-xs text-slate-800"><?= $row['nama_lengkap']; ?></td>
                                    <td class="px-3 py-2 whitespace-nowrap text-xs text-slate-800"><?= $row['status']; ?></td>


                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                
                </table>
                </form>
            </div>
<?php endif; ?>
<?php if (!$matkul_id) : ?>
			<div class="p-3 text-xs text-slate-600">Pilih mata kuliah untuk melihat absensi yang tersimpan</div>
		<?php endif; ?>
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
    </script>
</body>

</html>