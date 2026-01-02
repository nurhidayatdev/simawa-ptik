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

if ($role === 'Mahasiswa' || $role === 'Ketua Tingkat' || $role === 'Sekretaris') {
    echo "<!DOCTYPE html>
    <html>
    <head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>Kas Individu - PTIK C 2024</title>
    <!-- Google tag (gtag.js) -->
    <script async src=\"https://www.googletagmanager.com/gtag/js?id=G-Q0TXETXV6Y\"></script>
    <script>
        window.dataLayer = window.dataLayer || [];

        function gtag() {
            dataLayer.push(arguments);
        }
        gtag('js', new Date());

        gtag('config', 'G-Q0TXETXV6Y');
    </script>
    <script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script>
    </head>
    <body>
        <script>
            Swal.fire({
                icon: 'error',
                title: 'Akses Ditolak!',
                text: 'Hanya Admin atau Bendahara yang dapat mengakses halaman ini.',
                confirmButtonColor: '#3085d6',
                confirmButtonText: 'Kembali'
            }).then((result) => {
                window.location.href = 'db_kas.php';
            });
        </script>
    </body>
    </html>";
    exit();
}

/* =====================
   AMBIL PERIODE AKTIF
   ===================== */
$q_periode = mysqli_query($koneksi, "SELECT id FROM periode WHERE status = 'aktif' LIMIT 1");
$periode = mysqli_fetch_assoc($q_periode);
$periode_id = $periode['id'];

$mahasiswa_id_selected = $_GET['mahasiswa_id'] ?? '';

// Handle Update
if (isset($_POST['update_kas_individu'])) {
    $m_id = mysqli_real_escape_string($koneksi, $_POST['mahasiswa_id']);

    if (!empty($_POST['status']) && is_array($_POST['status'])) {
        foreach ($_POST['status'] as $minggu => $status) {
            $status_safe = mysqli_real_escape_string($koneksi, $status);
            $minggu_safe = mysqli_real_escape_string($koneksi, $minggu);

            $posted_tanggal = $_POST['tanggal'][$minggu] ?? '';

            // Check existence
            $res = mysqli_query($koneksi, "SELECT status, tanggal_bayar FROM kas WHERE mahasiswa_id='$m_id' AND minggu_ke='$minggu_safe' AND periode_id='$periode_id'");
            $exists = mysqli_num_rows($res) > 0;
            $existing = $exists ? mysqli_fetch_assoc($res) : null;

            $tanggal_to_save = null;
            if ($status_safe === 'Belum') {
                $tanggal_to_save = null;
            } elseif ($posted_tanggal !== '') {
                $tanggal_to_save = mysqli_real_escape_string($koneksi, $posted_tanggal);
            } elseif ($exists && !empty($existing['tanggal_bayar'])) {
                $tanggal_to_save = $existing['tanggal_bayar'];
            }

            if ($exists) {
                if ($tanggal_to_save !== null) {
                    mysqli_query($koneksi, "UPDATE kas SET status='$status_safe', tanggal_bayar='$tanggal_to_save' WHERE mahasiswa_id='$m_id' AND minggu_ke='$minggu_safe' AND periode_id='$periode_id'");
                } else {
                    mysqli_query($koneksi, "UPDATE kas SET status='$status_safe', tanggal_bayar=NULL WHERE mahasiswa_id='$m_id' AND minggu_ke='$minggu_safe' AND periode_id='$periode_id'");
                }
            } else {
                if ($tanggal_to_save !== null) {
                    mysqli_query($koneksi, "INSERT INTO kas (mahasiswa_id, minggu_ke, status, tanggal_bayar, periode_id) VALUES ('$m_id', '$minggu_safe', '$status_safe', '$tanggal_to_save', '$periode_id')");
                } else {
                    mysqli_query($koneksi, "INSERT INTO kas (mahasiswa_id, minggu_ke, status, tanggal_bayar, periode_id) VALUES ('$m_id', '$minggu_safe', '$status_safe', NULL, '$periode_id')");
                }
            }
        }
    }
    $_SESSION['success'] = "Data kas individu berhasil diperbarui.";
    header("Location: kas_individu.php?mahasiswa_id=$m_id");
    exit;
}

// Get Mahasiswa List for dropdown
$all_mhs = mysqli_query($koneksi, "SELECT id, nim, nama_lengkap FROM mahasiswa ORDER BY nim ASC");

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#1e1b4b" />
    <link rel="icon" href="../img/main/icon.png" />
    <title>Kas Individu - PTIK C 2024</title>
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
    <link href="../../frontend/tailwind/src/output.css" rel="stylesheet">
    <link href="../../frontend/tailwind/src/input.css" rel="stylesheet">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        body {
            font-family: 'Poppins', sans-serif;
        }

        .rotate-180 {
            transform: rotate(180deg);
        }

        @media (max-width: 768px) {
            .mobile-menu-open {
                transform: translateX(0) !important;
            }
        }
    </style>
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
                <h3 class="text-xl font-bold text-slate-800">Input Kas Individu</h3>
                <a href="db_kas.php" class="bg-indigo-600 text-white px-4 py-2 rounded-lg text-xs">Kembali</a>
            </div>

            <form method="GET" class="mb-6">
                <div class="max-w-md">
                    <label class="block text-xs font-medium text-slate-700 mb-1">Pilih Mahasiswa</label>
                    <select name="mahasiswa_id" onchange="this.form.submit()" class="w-full border p-2 rounded-lg text-xs focus:ring-1 focus:ring-indigo-500 outline-none">
                        <option value="">-- Pilih Mahasiswa --</option>
                        <?php while ($m = mysqli_fetch_assoc($all_mhs)): ?>
                            <option value="<?= $m['id'] ?>" <?= ($mahasiswa_id_selected == $m['id']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($m['nim']) ?> - <?= htmlspecialchars($m['nama_lengkap']) ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
            </form>

            <?php if ($mahasiswa_id_selected):
                $m_id_safe = mysqli_real_escape_string($koneksi, $mahasiswa_id_selected);
                $q_mhs_info = mysqli_query($koneksi, "SELECT * FROM mahasiswa WHERE id = '$m_id_safe'");
                $mhs_info = mysqli_fetch_assoc($q_mhs_info);
            ?>
                <div class="bg-indigo-50 border border-indigo-100 rounded-lg p-4 mb-6">
                    <div class="flex items-center gap-4">
                        <?php if (!empty($mhs_info['foto'])): ?>
                            <img src="../img/profile/<?= htmlspecialchars($mhs_info['foto']) ?>" class="w-12 h-12 rounded-full border-2 border-white shadow-sm">
                        <?php else: ?>
                            <div class="w-12 h-12 rounded-full bg-indigo-200 flex items-center justify-center text-indigo-700">
                                <i class="fa fa-user text-xl"></i>
                            </div>
                        <?php endif; ?>
                        <div>
                            <h4 class="font-bold text-slate-800"><?= htmlspecialchars($mhs_info['nama_lengkap']) ?></h4>
                            <p class="text-xs text-slate-600">NIM: <?= htmlspecialchars($mhs_info['nim']) ?></p>
                        </div>
                    </div>
                </div>

                <form method="POST">
                    <input type="hidden" name="mahasiswa_id" value="<?= htmlspecialchars($mahasiswa_id_selected) ?>">
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-slate-200">
                            <thead class="bg-indigo-900">
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-bold text-white uppercase tracking-wider w-20">Minggu</th>
                                    <th class="px-4 py-3 text-left text-xs font-bold text-white uppercase tracking-wider">Status</th>
                                    <th class="px-4 py-3 text-left text-xs font-bold text-white uppercase tracking-wider">Tanggal Bayar</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-slate-200">
                                <?php
                                for ($i = 1; $i <= 16; $i++) {
                                    $q_kas = mysqli_query($koneksi, "SELECT status, tanggal_bayar FROM kas WHERE mahasiswa_id = '$m_id_safe' AND minggu_ke = '$i' AND periode_id = '$periode_id'");
                                    $kas_data = mysqli_fetch_assoc($q_kas);
                                    $status_curr = $kas_data['status'] ?? 'Belum';
                                    $tgl_curr = $kas_data['tanggal_bayar'] ?? '';
                                ?>
                                    <tr class="<?= ($i % 2 == 0) ? 'bg-slate-50' : 'bg-white' ?> hover:bg-indigo-50">
                                        <td class="px-4 py-3 whitespace-nowrap text-xs font-medium text-slate-800 text-center">
                                            Minggu <?= $i ?>
                                        </td>
                                        <td class="px-4 py-3 whitespace-nowrap text-xs text-slate-800">
                                            <select name="status[<?= $i ?>]" class="status-select border p-1.5 rounded-md text-xs w-full sm:w-32 focus:ring-1 focus:ring-indigo-500" data-week="<?= $i ?>">
                                                <option value="Lunas" <?= ($status_curr == 'Lunas') ? 'selected' : '' ?>>Lunas</option>
                                                <option value="Belum" <?= ($status_curr == 'Belum') ? 'selected' : '' ?>>Belum</option>
                                            </select>
                                        </td>
                                        <td class="px-4 py-3 whitespace-nowrap text-xs text-slate-800">
                                            <input type="date" name="tanggal[<?= $i ?>]" id="tanggal_<?= $i ?>" value="<?= htmlspecialchars($tgl_curr) ?>" class="border p-1.5 rounded-md text-xs w-full sm:w-48 focus:ring-1 focus:ring-indigo-500" <?= ($status_curr == 'Belum') ? 'disabled' : '' ?>>
                                        </td>
                                    </tr>
                                <?php } ?>
                            </tbody>
                        </table>
                    </div>
                    <div class="flex justify-end mt-6">
                        <button type="submit" name="update_kas_individu" class="bg-indigo-900 hover:bg-indigo-800 text-white rounded-lg px-6 py-2.5 text-xs font-semibold flex items-center gap-2 shadow-lg transition-all active:scale-95">
                            <i class="fa fa-save"></i> Simpan Perubahan
                        </button>
                    </div>
                </form>
            <?php else: ?>
                <div class="flex flex-col items-center justify-center py-20 text-slate-400">
                    <i class="fa fa-user-circle text-6xl mb-4 opacity-20"></i>
                    <p class="text-sm">Silakan pilih mahasiswa untuk melihat data kas</p>
                </div>
            <?php endif; ?>
        </div>
        <footer class="text-xs text-indigo-900 text-center py-6">
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
            if (window.innerWidth < 768) {
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

        document.querySelectorAll('.status-select').forEach(select => {
            select.addEventListener('change', function() {
                const week = this.getAttribute('data-week');
                const dateInput = document.getElementById('tanggal_' + week);
                if (this.value === 'Lunas') {
                    dateInput.disabled = false;
                    if (!dateInput.value) {
                        const today = new Date().toISOString().split('T')[0];
                        dateInput.value = today;
                    }
                } else {
                    dateInput.value = '';
                    dateInput.disabled = true;
                }
            });
        });

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
    </script>
</body>

</html>