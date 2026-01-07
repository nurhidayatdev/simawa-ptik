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

// Ambil Periode Aktif
$q_periode = mysqli_query($koneksi, "SELECT id FROM periode WHERE status = 'aktif' LIMIT 1");
$periode = mysqli_fetch_assoc($q_periode);
$periode_id = $periode['id'] ?? 0;

// Set Minggu (Default 1)
$minggu_ke = isset($_GET['minggu']) ? max(1, intval($_GET['minggu'])) : 1;

// Cek apakah sudah ada data untuk minggu ini
$q_existing = mysqli_query($koneksi, "SELECT COUNT(*) as cnt FROM piket_proyektor WHERE minggu_ke = '$minggu_ke' AND periode_id = '$periode_id'");
$existing = mysqli_fetch_assoc($q_existing);
$has_data = $existing['cnt'] > 0;

// Handle Generate / Acak Jadwal
if (isset($_POST['acak_jadwal'])) {
    // 1. Hapus jadwal lama untuk minggu ini
    mysqli_query($koneksi, "DELETE FROM piket_proyektor WHERE minggu_ke = '$minggu_ke' AND periode_id = '$periode_id'");

    // 2. Ambil Data Mahasiswa (Urut Random untuk variasi saat generate)
    $students = [];
    $q_mhs = mysqli_query($koneksi, "SELECT id, nim FROM mahasiswa ORDER BY RAND()");
    while ($row = mysqli_fetch_assoc($q_mhs)) {
        $students[] = $row;
    }
    $total_students = count($students);

    // 3. Ambil Mapping KRS
    $krs_map = [];
    $q_krs = mysqli_query($koneksi, "SELECT mahasiswa_id, matkul_id FROM krs WHERE periode_id = '$periode_id'");
    while ($row = mysqli_fetch_assoc($q_krs)) {
        $krs_map[$row['mahasiswa_id']][] = $row['matkul_id'];
    }

    // 4. Ambil Jadwal Kuliah (Urut Waktu)
    $schedule_data = [];
    $q_jadwal = mysqli_query($koneksi, "
        SELECT jm.id, jm.matkul_id, jm.hari, jk_start.jam_mulai 
        FROM jadwal_matkul jm 
        LEFT JOIN jam_kuliah jk_start ON jm.jam_mulai_ke = jk_start.jam_ke
        WHERE jm.periode_id = '$periode_id' 
        ORDER BY FIELD(jm.hari, 'Senin','Selasa','Rabu','Kamis','Jumat','Sabtu','Minggu'), jk_start.jam_mulai ASC
    ");
    while ($row = mysqli_fetch_assoc($q_jadwal)) {
        $schedule_data[] = $row;
    }
    $total_classes = count($schedule_data);

    // 5. Algoritma Distribusi (Siklus 2 Minggu)
    if ($total_students > 0 && $total_classes > 0) {
        // Tentukan Tim Aktif (Ganjil/Genap)
        $is_odd_week = ($minggu_ke % 2) != 0;
        $split_point = ceil($total_students / 2);

        $team_active = $is_odd_week
            ? array_slice($students, 0, $split_point)
            : array_slice($students, $split_point);

        $total_active = count($team_active);
        $base_alloc = floor($total_active / $total_classes);
        $extra_alloc = $total_active % $total_classes;

        // Tracking penugasan minggu ini
        $assigned_ids = [];

        foreach ($schedule_data as $idx => $class) {
            $target = $base_alloc + ($idx < $extra_alloc ? 1 : 0);
            if ($target < 1 && $total_active > 0) $target = 1;

            $assigned_count = 0;
            $matkul_id = $class['matkul_id'];
            $jadwal_id = $class['id'];

            foreach ($team_active as $student) {
                if ($assigned_count >= $target) break;

                $m_id = $student['id'];
                if (in_array($m_id, $assigned_ids)) continue;

                // Cek KRS
                $takes_course = isset($krs_map[$m_id]) && in_array($matkul_id, $krs_map[$m_id]);

                if ($takes_course) {
                    // Simpan ke DB
                    $insert = "INSERT INTO piket_proyektor (mahasiswa_id, jadwal_matkul_id, minggu_ke, periode_id) VALUES ('$m_id', '$jadwal_id', '$minggu_ke', '$periode_id')";
                    if (mysqli_query($koneksi, $insert)) {
                        $assigned_ids[] = $m_id;
                        $assigned_count++;
                    }
                }
            }
        }
        $_SESSION['success'] = "Jadwal piket proyektor minggu ke-$minggu_ke berhasil diacak!";
    }
    // Redirect agar tidak resubmit
    echo "<script>window.location.href='acak_piket_proyektor.php?minggu=$minggu_ke';</script>";
    exit;
}

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Dashboard Resmi Kelas PTIK C 2024">
    <meta name="theme-color" content="#1e1b4b" />
    <title>Acak Piket Proyektor - PTIK C 2024</title>
    <link rel="icon" href="../img/main/icon.webp" />
    <link href="../../frontend/tailwind/src/output.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Poppins', sans-serif;
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
    <!-- Header -->
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
                                    </div>
                                <?php endif; ?>
                                <?php if ($role === 'Administrator Utama' || $role === 'Ketua Tingkat'): ?>
                                    <a href="reminder.php" class="flex items-center px-4 py-1 text-slate-800 hover:bg-slate-100 rounded-lg transition-colors">
                                        <i class="fa fa-bell w-5 h-5 mr-3 text-indigo-900"></i>
                                        <span class="text-xs font-medium">Notifikasi</span>
                                    </a>
                                <?php endif; ?>

                                <a href="tentang.php" class="flex items-center px-4 py-1 text-slate-800 hover:bg-slate-100 rounded-lg transition-colors">
                                    <i class="fa fa-info-circle w-5 h-5 mr-3 text-indigo-900"></i>
                                    <span class="text-xs font-medium">Tentang</span>
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
        <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-6">
            <div class="flex flex-col md:flex-row justify-between items-center mb-6 gap-4">
                <div>
                    <h1 class="text-2xl font-bold text-slate-800">Acak Jadwal Piket Proyektor</h1>
                    <p class="text-slate-500 text-sm mt-1">Generate/Randomisasi jadwal penanggung jawab proyektor</p>
                </div>

                <!-- Week Selector -->
                <div class="flex items-center gap-2 bg-slate-100 p-1 rounded-lg">
                    <a href="?minggu=<?= max(1, $minggu_ke - 1) ?>" class="p-2 hover:bg-white rounded-md transition-colors <?= $minggu_ke <= 1 ? 'opacity-50 pointer-events-none' : '' ?>">
                        <i class="fa fa-chevron-left text-slate-600"></i>
                    </a>
                    <span class="px-4 font-bold text-indigo-900 text-sm">Minggu Ke-<?= $minggu_ke ?></span>
                    <a href="?minggu=<?= $minggu_ke + 1 ?>" class="p-2 hover:bg-white rounded-md transition-colors">
                        <i class="fa fa-chevron-right text-slate-600"></i>
                    </a>
                </div>
            </div>

            <!-- Success Message -->
            <?php if (isset($_SESSION['success'])): ?>
                <div class="mb-6 p-4 bg-green-50 border border-green-200 rounded-lg flex items-center gap-3">
                    <i class="fa fa-check-circle text-green-600"></i>
                    <span class="text-sm text-green-800"><?= $_SESSION['success'] ?></span>
                </div>
                <?php unset($_SESSION['success']); ?>
            <?php endif; ?>

            <!-- Status Card -->
            <div class="mb-6 p-6 rounded-xl border <?= $has_data ? 'bg-green-50 border-green-200' : 'bg-yellow-50 border-yellow-200' ?>">
                <div class="flex items-center gap-4">
                    <div class="w-16 h-16 rounded-full flex items-center justify-center <?= $has_data ? 'bg-green-100' : 'bg-yellow-100' ?>">
                        <i class="fa <?= $has_data ? 'fa-check text-green-600' : 'fa-exclamation text-yellow-600' ?> text-2xl"></i>
                    </div>
                    <div>
                        <h3 class="font-bold text-lg <?= $has_data ? 'text-green-800' : 'text-yellow-800' ?>">
                            <?= $has_data ? 'Jadwal Sudah Di-generate' : 'Belum Ada Jadwal' ?>
                        </h3>
                        <p class="text-sm <?= $has_data ? 'text-green-700' : 'text-yellow-700' ?>">
                            <?= $has_data ? 'Jadwal piket proyektor untuk minggu ini sudah tersedia.' : 'Silakan klik tombol di bawah untuk membuat jadwal piket.' ?>
                        </p>
                    </div>
                </div>
            </div>

            <!-- Action Button -->
            <form method="POST" onsubmit="return confirm('<?= $has_data ? 'Acak ulang akan menghapus jadwal lama. Lanjutkan?' : 'Generate jadwal piket untuk Minggu ke-' . $minggu_ke . '?' ?>');">
                <button type="submit" name="acak_jadwal" class="w-full md:w-auto px-6 py-4 bg-gradient-to-r from-indigo-600 to-purple-600 hover:from-indigo-700 hover:to-purple-700 text-white rounded-xl text-sm font-bold transition-all shadow-lg hover:shadow-xl flex items-center justify-center gap-3">
                    <i class="fa fa-shuffle text-lg"></i>
                    <span><?= $has_data ? 'Acak Ulang Jadwal' : 'Generate Jadwal Piket' ?></span>
                </button>
            </form>

            <!-- Link to View -->
            <?php if ($has_data): ?>
                <a href="db_piket_proyektor.php?minggu=<?= $minggu_ke ?>" class="mt-4 inline-flex items-center gap-2 text-indigo-600 hover:text-indigo-800 text-sm font-medium">
                    <i class="fa fa-eye"></i>
                    Lihat Jadwal Piket Minggu Ke-<?= $minggu_ke ?>
                </a>
            <?php endif; ?>

            <!-- Info Box -->
            <div class="mt-6 p-4 bg-blue-50 border border-blue-200 rounded-lg flex items-start gap-3">
                <i class="fa fa-info-circle text-blue-600 mt-0.5"></i>
                <div class="text-xs text-blue-800">
                    <p class="font-bold mb-1">Informasi Rotasi Piket</p>
                    <p>Sistem akan membagi mahasiswa menjadi 2 tim secara acak. Tim ganjil bertugas di minggu ganjil, tim genap di minggu genap. Penugasan mempertimbangkan KRS mahasiswa.</p>
                </div>
            </div>
        </div>

        <footer class="text-[10px] text-indigo-950/50 text-center py-6 mt-auto">
            © 2025 Kelas PTIK C - FT UNM.
        </footer>
    </main>

    <script>
        const mobileMenuButton = document.getElementById('mobile-menu-button');
        const sidebar = document.getElementById('sidebar');
        mobileMenuButton.addEventListener('click', (e) => {
            e.stopPropagation();
            sidebar.classList.toggle('-translate-x-full');
        });
        document.addEventListener('click', (e) => {
            if (window.innerWidth < 1024 && !sidebar.contains(e.target) && !mobileMenuButton.contains(e.target)) {
                sidebar.classList.add('-translate-x-full');
            }
        });
        const profileButton = document.getElementById('profile-button');
        const profileDropdown = document.getElementById('profile-dropdown');
        if (profileButton && profileDropdown) {
            profileButton.addEventListener('click', (e) => {
                e.stopPropagation();
                profileDropdown.classList.toggle('hidden');
            });
            document.addEventListener('click', () => profileDropdown.classList.add('hidden'));
        }
    </script>
</body>

</html>