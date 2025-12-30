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
$q_periode = mysqli_query($koneksi, "SELECT * FROM periode WHERE status = 'aktif' LIMIT 1");
$periode = mysqli_fetch_assoc($q_periode);
$periode_text = $periode ? $periode['tahun_periode'] . ' - ' . $periode['semester_nama'] : 'Tidak ada periode aktif';

// counts
$counts = [];
$tables = [
    'mahasiswa' => 'db_mahasiswa.php',
    'mata_kuliah' => 'db_matkul.php',
    'dosen' => 'db_dosen.php',
    'jadwal_matkul' => 'db_jadwal.php',
    'tugas' => 'db_tugas.php',
    'galeri' => 'db_galeri.php',
    'pesan' => 'db_pesan.php',
    'akademik' => 'db_berita.php',
    'kelompok' => 'db_kelompok.php'
];

$periode_id = $periode['id'] ?? 0;

foreach ($tables as $table => $link) {
    if ($table === 'tugas') {
        $res = mysqli_query($koneksi, "SELECT COUNT(*) AS cnt FROM tugas WHERE status = 'belum' AND periode_id = '$periode_id'");
    } elseif ($table === 'jadwal_matkul') {
        $res = mysqli_query($koneksi, "SELECT COUNT(*) AS cnt FROM jadwal_matkul WHERE periode_id = '$periode_id'");
    } elseif ($table === 'kelompok') {
        $res = mysqli_query($koneksi, "SELECT COUNT(*) AS cnt FROM kelompok WHERE periode_id = '$periode_id'");
    } else {
        $res = mysqli_query($koneksi, "SELECT COUNT(*) AS cnt FROM $table");
    }

    if ($res) {
        $row = mysqli_fetch_assoc($res);
        $counts[$table] = $row['cnt'] ?? 0;
    } else {
        $counts[$table] = 0;
    }
}

// Task Status per Mata Kuliah
$q_task_matkul = mysqli_query($koneksi, "SELECT mk.nama_matkul, 
    SUM(CASE WHEN t.status = 'selesai' THEN 1 ELSE 0 END) as selesai,
    SUM(CASE WHEN t.status = 'belum' THEN 1 ELSE 0 END) as belum
    FROM tugas t 
    JOIN mata_kuliah mk ON t.matkul_id = mk.id 
    WHERE t.periode_id = '$periode_id' 
    GROUP BY t.matkul_id
    ORDER BY (sum(CASE WHEN t.status = 'selesai' THEN 1 ELSE 0 END) + sum(CASE WHEN t.status = 'belum' THEN 1 ELSE 0 END)) DESC");
$task_matkul_labels = [];
$task_matkul_selesai = [];
$task_matkul_belum = [];
while ($row = mysqli_fetch_assoc($q_task_matkul)) {
    $task_matkul_labels[] = $row['nama_matkul'];
    $task_matkul_selesai[] = (int)$row['selesai'];
    $task_matkul_belum[] = (int)$row['belum'];
}

// Recent Messages (Anonymous)
$q_recent_pesan = mysqli_query($koneksi, "SELECT * FROM pesan ORDER BY tanggal DESC LIMIT 4");


// Kuis Harian Leaderboard
$q_kuis_harian = mysqli_query($koneksi, "SELECT m.nama_panggilan, m.foto, s.current_streak, s.total_games 
    FROM kuis_harian_stats s 
    JOIN mahasiswa m ON s.user_id = m.user_id 
    ORDER BY s.current_streak DESC, s.total_games DESC 
    LIMIT 5");

// --- DATA UNTUK SLIDER ---
$days_indo = [
    'Sunday' => 'Minggu',
    'Monday' => 'Senin',
    'Tuesday' => 'Selasa',
    'Wednesday' => 'Rabu',
    'Thursday' => 'Kamis',
    'Friday' => 'Jumat',
    'Saturday' => 'Sabtu'
];
$today_indo = $days_indo[date('l')];
$tomorrow_indo = $days_indo[date('l', strtotime('+1 day'))];

// Schedule Today
$q_sch_today = mysqli_query($koneksi, "SELECT mk.nama_matkul, jm.jam_mulai, jm.jam_selesai, jm.ruangan FROM jadwal_matkul jm JOIN mata_kuliah mk ON jm.matkul_id = mk.id WHERE jm.hari = '$today_indo' AND jm.periode_id = '$periode_id' ORDER BY jm.jam_mulai ASC");
$sch_today = [];
while ($r = mysqli_fetch_assoc($q_sch_today)) $sch_today[] = $r;

// Schedule Tomorrow
$q_sch_tomorrow = mysqli_query($koneksi, "SELECT mk.nama_matkul, jm.jam_mulai, jm.jam_selesai, jm.ruangan FROM jadwal_matkul jm JOIN mata_kuliah mk ON jm.matkul_id = mk.id WHERE jm.hari = '$tomorrow_indo' AND jm.periode_id = '$periode_id' ORDER BY jm.jam_mulai ASC");
$sch_tomorrow = [];
while ($r = mysqli_fetch_assoc($q_sch_tomorrow)) $sch_tomorrow[] = $r;

// Closest Task
$q_closest = mysqli_query($koneksi, "SELECT mk.nama_matkul, t.deadline_tanggal, t.deadline_pukul FROM tugas t JOIN mata_kuliah mk ON t.matkul_id = mk.id WHERE t.status = 'belum' AND t.periode_id = '$periode_id' ORDER BY t.deadline_tanggal ASC, t.deadline_pukul ASC LIMIT 2");
$closest_tasks = [];
while ($r = mysqli_fetch_assoc($q_closest)) $closest_tasks[] = $r;
$closest_task = $closest_tasks[0] ?? null; // Keep this for backward compatibility if needed, but we'll use $closest_tasks

// Finance
$q_kas_total = mysqli_query($koneksi, "SELECT SUM(nominal) as total FROM kas WHERE status = 'Lunas' AND periode_id = '$periode_id'");
$kas_total = mysqli_fetch_assoc($q_kas_total)['total'] ?? 0;
$q_arus_in = mysqli_query($koneksi, "SELECT SUM(jumlah) as total FROM arus_kas WHERE tipe = 'masuk'");
$arus_in = mysqli_fetch_assoc($q_arus_in)['total'] ?? 0;
$q_arus_out = mysqli_query($koneksi, "SELECT SUM(jumlah) as total FROM arus_kas WHERE tipe = 'keluar'");
$arus_out = mysqli_fetch_assoc($q_arus_out)['total'] ?? 0;
$saldo_sekarang = ($kas_total + $arus_in) - $arus_out;

// Statistik Pembayaran Kas
$q_kas_stats = mysqli_query($koneksi, "SELECT 
    COUNT(*) as total,
    SUM(CASE WHEN status = 'Lunas' THEN 1 ELSE 0 END) as lunas
    FROM kas 
    WHERE periode_id = '$periode_id'");
$kas_stats_data = mysqli_fetch_assoc($q_kas_stats);
$total_k = (int)($kas_stats_data['total'] ?? 0);
$lunas_k = (int)($kas_stats_data['lunas'] ?? 0);
$belum_k = $total_k - $lunas_k;
$persen_lunas = $total_k > 0 ? round(($lunas_k / $total_k) * 100) : 0;

// Kalender Akademik untuk Slider
$today_date = date('Y-m-d');
$academic_events = [];

// 1. Ambil 1 kegiatan sedang berlangsung (prioritas utama untuk slot pertama)
$q_ongoing = mysqli_query($koneksi, "SELECT * FROM kalender_akademik WHERE tanggal_mulai <= '$today_date' AND tanggal_selesai >= '$today_date' ORDER BY tanggal_selesai ASC LIMIT 1");
$ongoing_event = mysqli_fetch_assoc($q_ongoing);

if ($ongoing_event) {
    $ongoing_event['status_event'] = 'Sedang Berlangsung';
    $academic_events[] = $ongoing_event;
    
    // Slot kedua: ambil 1 kegiatan akan datang
    $q_upcoming = mysqli_query($koneksi, "SELECT * FROM kalender_akademik WHERE tanggal_mulai > '$today_date' ORDER BY tanggal_mulai ASC LIMIT 1");
    if ($r = mysqli_fetch_assoc($q_upcoming)) {
        $r['status_event'] = 'Akan Datang';
        $academic_events[] = $r;
    }
} else {
    // Jika tidak ada yang berlangsung, ambil 2 kegiatan akan datang terdekat
    $q_upcoming = mysqli_query($koneksi, "SELECT * FROM kalender_akademik WHERE tanggal_mulai > '$today_date' ORDER BY tanggal_mulai ASC LIMIT 2");
    while ($r = mysqli_fetch_assoc($q_upcoming)) {
        $r['status_event'] = 'Akan Datang';
        $academic_events[] = $r;
    }
}

// --- DATA VISUALISASI LANJUTAN ---

// 1. Distribusi Gender Mahasiswa
$q_gender = mysqli_query($koneksi, "SELECT jenis_kelamin, COUNT(*) as cnt FROM mahasiswa GROUP BY jenis_kelamin");
$gender_data = ['Laki-laki' => 0, 'Perempuan' => 0];
while ($row = mysqli_fetch_assoc($q_gender)) {
    if ($row['jenis_kelamin']) $gender_data[$row['jenis_kelamin']] = (int)$row['cnt'];
}

// 2. Statistik Absensi (Periode Aktif)
$abs_matkul = $_GET['abs_matkul'] ?? '';
$abs_pertemuan = $_GET['abs_pertemuan'] ?? '';

$abs_where = "WHERE periode_id = '$periode_id'";
if ($abs_matkul !== '') $abs_where .= " AND matkul_id = '$abs_matkul'";
if ($abs_pertemuan !== '') $abs_where .= " AND pertemuan = '$abs_pertemuan'";

$q_absensi = mysqli_query($koneksi, "SELECT status, COUNT(*) as cnt FROM absensi $abs_where GROUP BY status");
$absensi_stats = ['Hadir' => 0, 'Izin' => 0, 'Sakit' => 0, 'Alpha' => 0];
while ($row = mysqli_fetch_assoc($q_absensi)) {
    if (isset($absensi_stats[$row['status']])) $absensi_stats[$row['status']] = (int)$row['cnt'];
}

// Ambil daftar matkul & pertemuan untuk filter absensi
$q_abs_matkuls = mysqli_query($koneksi, "SELECT DISTINCT a.matkul_id, mk.nama_matkul FROM absensi a JOIN mata_kuliah mk ON a.matkul_id = mk.id WHERE a.periode_id = '$periode_id' ORDER BY mk.nama_matkul");
$abs_matkuls = [];
while ($r = mysqli_fetch_assoc($q_abs_matkuls)) $abs_matkuls[] = $r;

$q_abs_pertemuans = mysqli_query($koneksi, "SELECT DISTINCT pertemuan FROM absensi WHERE periode_id = '$periode_id' ORDER BY pertemuan ASC");
$abs_pertemuans = [];
while ($r = mysqli_fetch_assoc($q_abs_pertemuans)) $abs_pertemuans[] = (int)$r['pertemuan'];

// 3. Filter Rentang Tanggal (KAS)
$kas_start = $_GET['kas_start'] ?? date('Y-m-d', strtotime('-30 days'));
$kas_end = $_GET['kas_end'] ?? date('Y-m-d');

// 4. Data Arus Kas (Line Chart)
$kas_history = [];
$current = strtotime($kas_start);
$last = strtotime($kas_end);
$temp_saldo = 0;

// Hitung saldo awal sebelum kas_start
$q_saldo_awal_kas = mysqli_query($koneksi, "SELECT SUM(nominal) as total FROM kas WHERE status = 'Lunas' AND periode_id = '$periode_id' AND (tanggal_bayar < '$kas_start' OR tanggal_bayar IS NULL)");
$q_saldo_awal_in = mysqli_query($koneksi, "SELECT SUM(jumlah) as total FROM arus_kas WHERE tipe = 'masuk' AND tanggal < '$kas_start'");
$q_saldo_awal_out = mysqli_query($koneksi, "SELECT SUM(jumlah) as total FROM arus_kas WHERE tipe = 'keluar' AND tanggal < '$kas_start'");
$temp_saldo = (mysqli_fetch_assoc($q_saldo_awal_kas)['total'] ?? 0) + (mysqli_fetch_assoc($q_saldo_awal_in)['total'] ?? 0) - (mysqli_fetch_assoc($q_saldo_awal_out)['total'] ?? 0);

while ($current <= $last) {
    $date = date('Y-m-d', $current);

    // Pemasukan hari ini
    $q_in_today = mysqli_query($koneksi, "SELECT SUM(nominal) as total FROM kas WHERE status = 'Lunas' AND periode_id = '$periode_id' AND tanggal_bayar = '$date'");
    $q_arus_in_today = mysqli_query($koneksi, "SELECT SUM(jumlah) as total FROM arus_kas WHERE tipe = 'masuk' AND tanggal = '$date'");
    $daily_in = (mysqli_fetch_assoc($q_in_today)['total'] ?? 0) + (mysqli_fetch_assoc($q_arus_in_today)['total'] ?? 0);

    // Pengeluaran hari ini
    $q_out_today = mysqli_query($koneksi, "SELECT SUM(jumlah) as total FROM arus_kas WHERE tipe = 'keluar' AND tanggal = '$date'");
    $daily_out = mysqli_fetch_assoc($q_out_today)['total'] ?? 0;

    $temp_saldo += ($daily_in - $daily_out);

    $kas_history[] = [
        'tanggal' => date('d M', $current),
        'masuk' => $daily_in,
        'keluar' => $daily_out,
        'saldo' => $temp_saldo
    ];
    $current = strtotime("+1 day", $current);
}

// 5. Filter Rentang Tanggal (PESAN)
$pesan_start = $_GET['pesan_start'] ?? date('Y-m-d', strtotime('-30 days'));
$pesan_end = $_GET['pesan_end'] ?? date('Y-m-d');

// 6. Data Pesan Anonim (Bar Chart)
$pesan_history = [];
$current = strtotime($pesan_start);
$last = strtotime($pesan_end);
while ($current <= $last) {
    $date = date('Y-m-d', $current);
    $q_pesan_count = mysqli_query($koneksi, "SELECT COUNT(*) as cnt FROM pesan WHERE DATE(tanggal) = '$date'");
    $pesan_history[] = [
        'tanggal' => date('d M', $current),
        'jumlah' => (int)mysqli_fetch_assoc($q_pesan_count)['cnt']
    ];
    $current = strtotime("+1 day", $current);
}

function getFireColor($streak)
{
    if ($streak >= 1000) return '#ff0000';
    if ($streak >= 500) return '#ff00ff';
    if ($streak >= 200) return '#00ffff';
    if ($streak >= 100) return '#ffd700';
    if ($streak >= 50) return '#ff6600';
    if ($streak >= 30) return '#9966ff';
    if ($streak >= 10) return '#3399ff';
    if ($streak >= 3) return '#ff9500';
    return null;
}


?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#1e1b4b" />
    <link rel="icon" href="../img/main/icon.png" />
    <title>Dashboard - PTIK C 24</title>
    <link href="../../frontend/tailwind/src/output.css" rel="stylesheet">
    <link href="../../frontend/tailwind/src/input.css" rel="stylesheet">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&display=swap" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        .card-hover:hover {
            transform: translateY(-4px);
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
    <!-- Header dengan z-index lebih tinggi -->
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
                        <span class="text-white"><?php echo htmlspecialchars($nama_lengkap); ?></span>
                        <img src="../img/profile/<?php echo htmlspecialchars($foto); ?>" alt="Profile" class="w-8 h-8 rounded-full">
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
        <div class="max-w-6xl mx-auto flex-grow w-full">


            <!-- Welcome & Realtime Section -->
            <div class="mb-6 bg-white  p-6 rounded-lg shadow-sm flex flex-col md:flex-row justify-between items-center gap-4">
                <div class="flex items-center gap-4">
                    <div class="h-14 w-14 rounded-full overflow-hidden border-2 border-indigo-200">
                        <img src="../img/profile/<?= htmlspecialchars($foto) ?>" alt="Profile" class="w-full h-full object-cover">
                    </div>
                    <div>
                        <h2 class="font-bold text-lg text-slate-800">Selamat Datang, <span class="text-indigo-600"><?= htmlspecialchars($nama_lengkap) ?></span>!</h2>
                        <p class="text-sm text-slate-600 flex items-center gap-2">
                            <i class="fa fa-calendar-check text-green-600"></i>
                            Periode Aktif: <span class="font-semibold bg-green-100 text-green-700 px-2 py-0.5 rounded text-xs"><?= htmlspecialchars($periode_text) ?></span>
                        </p>
                    </div>
                </div>

                <div class="flex items-center gap-4 mt-2 md:mt-0">
                    <div class="flex items-center gap-1.5 bg-slate-100 px-3 py-1.5 rounded-lg border border-slate-200">
                        <i class="fa fa-calendar-alt text-indigo-500 text-xs"></i>
                        <div id="realtime-date" class="text-xs font-medium text-slate-600 uppercase tracking-wide">...</div>
                    </div>
                    <div class="flex items-center gap-1.5 bg-slate-100 px-3 py-1.5 rounded-lg border border-slate-200">
                        <i class="fa fa-clock text-indigo-500 text-xs"></i>
                        <div id="realtime-clock" class="text-xs font-mono font-bold text-slate-700 tracking-wider">--:--:--</div>
                    </div>
                </div>

            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">

                <a href="db_mahasiswa.php" class="bg-white p-4 rounded-xl shadow-sm border border-slate-200 card-hover flex items-center gap-4">
                    <div class="h-12 w-12 bg-indigo-100 rounded-lg flex items-center justify-center text-indigo-600">
                        <i class="fa fa-user-graduate text-xl"></i>
                    </div>
                    <div>
                        <div class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Mahasiswa</div>
                        <div class="text-xl font-bold text-slate-800"><?= number_format($counts['mahasiswa']) ?></div>
                    </div>
                </a>



                <a href="db_tugas.php" class="bg-white p-4 rounded-xl shadow-sm border border-slate-200 card-hover flex items-center gap-4">
                    <div class="h-12 w-12 bg-orange-100 rounded-lg flex items-center justify-center text-orange-600">
                        <i class="fa fa-tasks text-xl"></i>
                    </div>
                    <div>
                        <div class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Tugas Aktif</div>
                        <div class="text-xl font-bold text-slate-800"><?= number_format($counts['tugas']) ?></div>
                    </div>
                </a>

                <a href="db_kelompok.php" class="bg-white p-4 rounded-xl shadow-sm border border-slate-200 card-hover flex items-center gap-4">
                    <div class="h-12 w-12 bg-purple-100 rounded-lg flex items-center justify-center text-purple-600">
                        <i class="fa fa-users text-xl"></i>
                    </div>
                    <div>
                        <div class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Data Kelompok</div>
                        <div class="text-xl font-bold text-slate-800"><?= number_format($counts['kelompok']) ?></div>
                    </div>
                </a>

                <a href="db_jadwal.php" class="bg-white p-4 rounded-xl shadow-sm border border-slate-200 card-hover flex items-center gap-4">
                    <div class="h-12 w-12 bg-blue-100 rounded-lg flex items-center justify-center text-blue-600">
                        <i class="fa fa-list-alt text-xl"></i>
                    </div>
                    <div>
                        <div class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Jadwal Kuliah</div>
                        <div class="text-xl font-bold text-slate-800"><?= number_format($counts['jadwal_matkul']) ?></div>
                    </div>
                </a>
            </div>

            <!-- Info Slider Section -->
            <div class="mt-6 relative overflow-hidden bg-white rounded-xl shadow-sm border border-slate-200 h-28">
                <div id="infoSlider" class="flex transition-transform duration-700 ease-in-out h-full">

                    <!-- Slide 1: Jadwal -->
                    <div class="min-w-full p-4 flex items-center gap-6">
                        <div class="h-16 w-16 bg-blue-100 rounded-2xl flex items-center justify-center text-blue-600 shrink-0">
                            <i class="fa fa-calendar-alt text-2xl"></i>
                        </div>
                        <div class="flex-grow grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <div class="text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Kuliah Hari Ini (<?= $today_indo ?>)</div>
                                <div class="text-sm font-bold text-slate-700">
                                    <?php if (empty($sch_today)): ?>
                                        <span class="text-slate-400 italic font-normal lowercase">Tidak ada jadwal kuliah hari ini.</span>
                                    <?php else: ?>
                                        <div class="flex flex-col <?= count($sch_today) > 1 ? 'gap-0' : '' ?>">
                                            <?php foreach ($sch_today as $s): ?>
                                                <div class="<?= count($sch_today) > 1 ? 'text-[11px] leading-tight' : 'text-sm' ?> font-bold text-slate-700 truncate">
                                                    • <?= $s['nama_matkul'] ?>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div class="hidden md:block border-l border-slate-100 pl-4">
                                <div class="text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Besok (<?= $tomorrow_indo ?>)</div>
                                <div class="text-sm font-bold text-slate-700">
                                    <?php if (empty($sch_tomorrow)): ?>
                                        <span class="text-slate-400 italic font-normal lowercase">Tidak ada jadwal.</span>
                                    <?php else: ?>
                                        <div class="flex flex-col <?= count($sch_tomorrow) > 1 ? 'gap-0' : '' ?>">
                                            <?php foreach ($sch_tomorrow as $s): ?>
                                                <div class="<?= count($sch_tomorrow) > 1 ? 'text-[11px] leading-tight' : 'text-sm' ?> font-bold text-slate-700 truncate">
                                                    • <?= $s['nama_matkul'] ?>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Slide 2: Deadline Tugas -->
                    <div class="min-w-full p-4 flex items-center gap-6">
                        <div class="h-16 w-16 bg-orange-100 rounded-2xl flex items-center justify-center text-orange-600 shrink-0">
                            <i class="fa fa-hourglass-half text-2xl"></i>
                        </div>
                        <div class="flex-grow grid grid-cols-1 <?= count($closest_tasks) > 1 ? 'md:grid-cols-2' : '' ?> gap-4">
                            <?php if (!empty($closest_tasks)): ?>
                                <?php foreach ($closest_tasks as $index => $task): ?>
                                    <div class="<?= $index === 1 ? 'hidden md:block border-l border-slate-100 pl-4' : '' ?>">
                                        <div class="text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">
                                            <?= $index === 0 ? 'Deadline Terdekat' : 'Deadline Berikutnya' ?>
                                        </div>
                                        <div class="text-sm font-bold text-slate-700 mb-1 truncate"><?= $task['nama_matkul'] ?></div>
                                        <div class="flex items-center gap-2">
                                            <span class="px-2 py-0.5 bg-orange-100 text-orange-700 rounded text-[10px] font-bold uppercase whitespace-nowrap">
                                                <?= date('d M Y', strtotime($task['deadline_tanggal'])) ?> • <?= date('H:i', strtotime($task['deadline_pukul'])) ?>
                                            </span>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <div>
                                    <div class="text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Deadline Terdekat</div>
                                    <div class="text-sm text-slate-400 italic">Semua tugas telah selesai dikerjakan! ✨</div>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Slide 3: Distribusi Gender -->
                    <div class="min-w-full p-4 flex items-center gap-6">
                        <div class="h-16 w-16 bg-pink-100 rounded-2xl flex items-center justify-center text-pink-600 shrink-0">
                            <i class="fa fa-venus-mars text-2xl"></i>
                        </div>
                        <div class="flex-grow grid grid-cols-2 gap-4">
                            <div>
                                <div class="text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Mahasiswa Laki-laki</div>
                                <div class="text-lg font-bold text-indigo-600"><?= number_format($gender_data['Laki-laki']) ?> <span class="text-xs font-normal text-slate-400">Orang</span></div>
                            </div>
                            <div class="border-l border-slate-100 pl-4">
                                <div class="text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Mahasiswa Perempuan</div>
                                <div class="text-lg font-bold text-pink-500"><?= number_format($gender_data['Perempuan']) ?> <span class="text-xs font-normal text-slate-400">Orang</span></div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Slide 4: Kalender Akademik -->
                    <div class="min-w-full p-4 flex items-center gap-6">
                        <div class="h-16 w-16 bg-blue-100 rounded-2xl flex items-center justify-center text-blue-600 shrink-0">
                            <i class="fa fa-calendar-alt text-2xl"></i>
                        </div>
                        <div class="flex-grow grid grid-cols-1 <?= count($academic_events) > 1 ? 'md:grid-cols-2' : '' ?> gap-4">
                            <?php if (!empty($academic_events)): ?>
                                <?php foreach ($academic_events as $index => $event): ?>
                                    <div class="<?= $index === 1 ? 'hidden md:block border-l border-slate-100 pl-4' : '' ?>">
                                        <div class="text-[10px] font-bold text-blue-500 uppercase tracking-wider mb-1">
                                            <?= $event['status_event'] ?>
                                        </div>
                                        <?php 
                                            // Perkecil font jika teks terlalu panjang (> 30 karakter)
                                            $fontClass = (strlen($event['kegiatan']) > 30) ? 'text-[11px]' : 'text-sm';
                                        ?>
                                        <div class="<?= $fontClass ?> font-bold text-slate-700 mb-1" title="<?= htmlspecialchars($event['kegiatan']) ?>">
                                            <?= htmlspecialchars($event['kegiatan']) ?>
                                        </div>
                                        <div class="text-[10px] text-slate-500 font-medium">
                                            <?= date('d M', strtotime($event['tanggal_mulai'])) ?> - <?= date('d M Y', strtotime($event['tanggal_selesai'])) ?>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <div>
                                    <div class="text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Kalender Akademik</div>
                                    <div class="text-sm text-slate-400 italic">Tidak ada agenda akademik terdekat.</div>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                </div>

                <!-- Slider Indicators -->
                <div class="absolute bottom-2 right-4 flex gap-1.5">
                    <div class="h-1.5 w-4 rounded-full bg-indigo-500 indicator" data-index="0"></div>
                    <div class="h-1.5 w-1.5 rounded-full bg-slate-200 indicator" data-index="1"></div>
                    <div class="h-1.5 w-1.5 rounded-full bg-slate-200 indicator" data-index="2"></div>
                    <div class="h-1.5 w-1.5 rounded-full bg-slate-200 indicator" data-index="3"></div>
                </div>
            </div>

            <!-- --- SECTION 1: DEMOGRAFI & SOSIAL --- -->
            <div class="mt-8 border-b border-slate-200 pb-2 mb-6">
                <h2 class="text-sm font-black text-slate-400 uppercase tracking-widest flex items-center gap-2">
                    <i class="fa fa-user-friends text-indigo-500"></i>
                    Pusat Aktivitas
                </h2>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Finance Summary Card -->
                <div class="bg-white p-6 rounded-xl shadow-sm border border-slate-200 lg:col-span-1">
                    <h3 class="text-sm font-bold text-slate-800 mb-4 flex justify-between items-center">
                        <span><i class="fa fa-wallet text-green-500 mr-2"></i>Status Keuangan</span>
                        <span class="text-[10px] bg-green-100 px-2 py-0.5 rounded text-green-700 font-bold">Saldo Aktif</span>
                    </h3>
                    <div class="space-y-4">
                        <div class="bg-slate-50 p-4 rounded-xl border border-slate-100">
                            <div class="text-[10px] text-slate-500 uppercase font-black tracking-widest mb-1">Saldo Sekarang</div>
                            <div class="text-2xl font-black text-slate-800">Rp <?= number_format($saldo_sekarang, 0, ',', '.') ?></div>
                        </div>
                        <div class="grid grid-cols-2 gap-3">
                            <div class="p-3 bg-indigo-50 rounded-lg border border-indigo-100">
                                <div class="text-[9px] text-indigo-400 uppercase font-bold mb-1">Dari Setoran Kas</div>
                                <div class="text-xs font-black text-indigo-700">Rp <?= number_format($kas_total, 0, ',', '.') ?></div>
                            </div>
                            <div class="p-3 bg-blue-50 rounded-lg border border-blue-100">
                                <div class="text-[9px] text-blue-400 uppercase font-bold mb-1">Pemasukan Lain</div>
                                <div class="text-xs font-black text-blue-700">Rp <?= number_format($arus_in, 0, ',', '.') ?></div>
                            </div>
                        </div>
                        <div class="p-3 bg-red-50 rounded-lg border border-red-100">
                            <div class="flex justify-between items-center">
                                <div class="text-[9px] text-red-400 uppercase font-bold">Total Pengeluaran</div>
                                <div class="text-xs font-black text-red-700">Rp <?= number_format($arus_out, 0, ',', '.') ?></div>
                            </div>
                        </div>
                        <div class="pt-2 border-t border-slate-100">
                            <div class="flex justify-between items-end mb-1">
                                <div class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Persentase Bayar Kas</div>
                                <div class="text-xs font-black text-indigo-600"><?= $persen_lunas ?>%</div>
                            </div>
                            <div class="w-full bg-slate-100 rounded-full h-1.5 overflow-hidden">
                                <div class="bg-indigo-500 h-full rounded-full" style="width: <?= $persen_lunas ?>%"></div>
                            </div>
                            <div class="flex justify-between mt-1 text-[9px] font-medium">
                                <span class="text-green-600">Lunas: <?= $lunas_k ?></span>
                                <span class="text-slate-400">Belum: <?= $belum_k ?></span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Kuis Harian Leaderboard -->
                <div class="bg-white p-6 rounded-xl shadow-sm border border-slate-200 lg:col-span-1">
                    <h3 class="text-sm font-bold text-slate-800 mb-4 flex items-center gap-2">
                        <i class="fa fa-fire text-orange-500"></i>
                        Kuis Harian Leaderboard
                    </h3>
                    <div class="space-y-2">
                        <?php
                        $rank = 1;
                        mysqli_data_seek($q_kuis_harian, 0);
                        if (mysqli_num_rows($q_kuis_harian) > 0):
                            while ($tk = mysqli_fetch_assoc($q_kuis_harian)):
                                $fireColor = getFireColor($tk['current_streak']);
                        ?>
                                <div class="flex items-center gap-3 p-2 rounded-lg bg-slate-50 border border-slate-100 hover:bg-slate-100 transition">
                                    <span class="text-xs font-black w-5 text-center <?= $rank == 1 ? 'text-yellow-500' : ($rank == 2 ? 'text-slate-400' : ($rank == 3 ? 'text-orange-400' : 'text-slate-500')) ?>"><?= $rank++ ?></span>
                                    <div class="w-8 h-8 rounded-full bg-slate-200 overflow-hidden border border-slate-300">
                                        <img src="../img/profile/<?= htmlspecialchars($tk['foto']) ?>" class="w-full h-full object-cover" onerror="this.src='../../img/profile/default.png'">
                                    </div>
                                    <div class="flex-grow min-w-0">
                                        <div class="text-xs font-bold text-slate-700 truncate flex items-center gap-1">
                                            <?= htmlspecialchars($tk['nama_panggilan']) ?>
                                            <?php if ($fireColor): ?><i class="fa fa-fire" style="color: <?= $fireColor ?>;"></i><?php endif; ?>
                                        </div>
                                        <div class="text-[10px] text-slate-500"><?= $tk['current_streak'] ?> streak • <?= $tk['total_games'] ?> games</div>
                                    </div>
                                </div>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <p class="text-xs text-slate-400 text-center py-4">Belum ada data.</p>
                        <?php endif; ?>
                    </div>
                    
                </div>

                <!-- Recent Messages -->
                <div class="bg-white p-6 rounded-xl shadow-sm border border-slate-200 lg:col-span-1">
                    <h3 class="text-sm font-bold text-slate-800 mb-4 flex items-center gap-2">
                        <i class="fa fa-envelope-open-text text-purple-500"></i>
                        Pesan Anonim Terbaru
                    </h3>
                    <div class="space-y-3">
                        <?php
                        mysqli_data_seek($q_recent_pesan, 0);
                        if (mysqli_num_rows($q_recent_pesan) > 0):
                            while ($ps = mysqli_fetch_assoc($q_recent_pesan)):
                        ?>
                                <div class="bg-slate-50 p-3 rounded-lg border-l-4 border-purple-400">
                                    <div class="text-[10px] text-purple-600 font-bold uppercase mb-1"><?= date('d M, H:i', strtotime($ps['tanggal'])) ?></div>
                                    <div class="text-xs text-slate-600 italic line-clamp-2">"<?= htmlspecialchars($ps['isi_pesan']) ?>"</div>
                                </div>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <p class="text-xs text-slate-400 text-center py-4">Belum ada pesan.</p>
                        <?php endif; ?>
                    </div>
                   
                </div>
            </div>

            <!-- --- SECTION 2: AKADEMIK --- -->
            <div class="mt-12 border-b border-slate-200 pb-2 mb-6">
                <h2 class="text-sm font-black text-slate-400 uppercase tracking-widest flex items-center gap-2">
                    <i class="fa fa-graduation-cap text-orange-500"></i>
                    Akademik & Tugas
                </h2>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <!-- Task Distribution per Subject -->
                <div class="bg-white p-6 rounded-xl shadow-sm border border-slate-200">
                    <h3 class="text-sm font-bold text-slate-800 mb-4 flex items-center gap-2">
                        <i class="fa fa-tasks text-indigo-500"></i>
                        Distribusi Tugas per Matkul
                    </h3>
                    <div class="relative h-64">
                        <canvas id="taskStatusChart"></canvas>
                    </div>
                </div>

                <!-- Attendance Statistics -->
                <div class="bg-white p-6 rounded-xl shadow-sm border border-slate-200">
                    <div class="flex flex-col md:flex-row md:justify-between md:items-center gap-3 mb-4">
                        <h3 class="text-sm font-bold text-slate-800 flex items-center gap-2">
                            <i class="fa fa-user-check text-green-500"></i>
                            Statistik Absensi
                        </h3>
                        <div class="flex items-center gap-2">
                            <select name="abs_matkul" form="vizFilters" onchange="this.form.submit()" class="text-[10px] border-slate-200 rounded p-1 focus:ring-indigo-500">
                                <option value="">Semua Matkul</option>
                                <?php foreach ($abs_matkuls as $m): ?>
                                    <option value="<?= $m['matkul_id'] ?>" <?= $abs_matkul == $m['matkul_id'] ? 'selected' : '' ?>><?= htmlspecialchars($m['nama_matkul']) ?></option>
                                <?php endforeach; ?>
                            </select>
                            <select name="abs_pertemuan" form="vizFilters" onchange="this.form.submit()" class="text-[10px] border-slate-200 rounded p-1 focus:ring-indigo-500">
                                <option value="">Semua Pertemuan</option>
                                <?php foreach ($abs_pertemuans as $p): ?>
                                    <option value="<?= $p ?>" <?= $abs_pertemuan == $p ? 'selected' : '' ?>>Ke-<?= $p ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="relative h-64">
                        <canvas id="attendanceChart"></canvas>
                    </div>
                </div>
            </div>

            <!-- --- SECTION 3: TREN & ANALITIK --- -->
            <form id="vizFilters" method="GET" class="hidden">
                <?php if (isset($_GET['periode_id'])): ?><input type="hidden" name="periode_id" value="<?= $_GET['periode_id'] ?>"><?php endif; ?>
            </form>
            <div class="mt-12 border-b border-slate-200 pb-2 mb-6">
                <h2 class="text-sm font-black text-slate-400 uppercase tracking-widest flex items-center gap-2">
                    <i class="fa fa-chart-line text-green-600"></i>
                    Tren & Analitik
                </h2>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 pb-12">
                <!-- Financial Trend Line Chart -->
                <div class="bg-white p-6 rounded-xl shadow-sm border border-slate-200">
                    <div class="flex flex-col md:flex-row md:justify-between md:items-center gap-3 mb-4">
                        <h3 class="text-sm font-bold text-slate-800 flex items-center gap-2">
                            <i class="fa fa-wallet text-green-500"></i>
                            Tren Arus Kas
                        </h3>
                        <div class="flex items-center gap-1 bg-slate-50 p-1 rounded border border-slate-200">
                            <input type="date" name="kas_start" value="<?= $kas_start ?>" form="vizFilters" onchange="this.form.submit()" class="text-[10px] font-bold text-slate-600 bg-transparent border-none focus:ring-0 p-0 w-24">
                            <span class="text-slate-300 text-[10px]">-</span>
                            <input type="date" name="kas_end" value="<?= $kas_end ?>" form="vizFilters" onchange="this.form.submit()" class="text-[10px] font-bold text-slate-600 bg-transparent border-none focus:ring-0 p-0 w-24">
                        </div>
                    </div>
                    <div class="relative h-72">
                        <canvas id="financeChart"></canvas>
                    </div>
                </div>

                <!-- Message Volume Bar Chart -->
                <div class="bg-white p-6 rounded-xl shadow-sm border border-slate-200">
                    <div class="flex flex-col md:flex-row md:justify-between md:items-center gap-3 mb-4">
                        <h3 class="text-sm font-bold text-slate-800 flex items-center gap-2">
                            <i class="fa fa-chart-bar text-purple-500"></i>
                            Volume Pesan Anonim
                        </h3>
                        <div class="flex items-center gap-1 bg-slate-50 p-1 rounded border border-slate-200">
                            <input type="date" name="pesan_start" value="<?= $pesan_start ?>" form="vizFilters" onchange="this.form.submit()" class="text-[10px] font-bold text-slate-600 bg-transparent border-none focus:ring-0 p-0 w-24">
                            <span class="text-slate-300 text-[10px]">-</span>
                            <input type="date" name="pesan_end" value="<?= $pesan_end ?>" form="vizFilters" onchange="this.form.submit()" class="text-[10px] font-bold text-slate-600 bg-transparent border-none focus:ring-0 p-0 w-24">
                        </div>
                    </div>
                    <div class="relative h-72">
                        <canvas id="messageChart"></canvas>
                    </div>
                </div>
            </div>
            <footer class="text-xs text-indigo-900 text-center mb-0 pb-0">© 2025 Kelas PTIK C - Teknik Informatika dan Komputer FT UNM. All rights reserved.</footer>
    </main>

    <script>
        // Info Slider Logic
        let currentSlide = 0;
        const totalSlides = 4;
        const slider = document.getElementById('infoSlider');
        const indicators = document.querySelectorAll('.indicator');

        function nextSlide() {
            currentSlide = (currentSlide + 1) % totalSlides;
            updateSlider();
        }

        function updateSlider() {
            slider.style.transform = `translateX(-${currentSlide * 100}%)`;

            // Update indicators
            indicators.forEach((ind, idx) => {
                if (idx === currentSlide) {
                    ind.classList.add('bg-indigo-500', 'w-4');
                    ind.classList.remove('bg-slate-200', 'w-1.5');
                } else {
                    ind.classList.remove('bg-indigo-500', 'w-4');
                    ind.classList.add('bg-slate-200', 'w-1.5');
                }
            });
        }

        // Auto slide interval
        setInterval(nextSlide, 5000);

        // Click on indicators to change slide
        indicators.forEach((ind, idx) => {
            ind.style.cursor = 'pointer';
            ind.addEventListener('click', () => {
                currentSlide = idx;
                updateSlider();
            });
        });

        // Mobile menu toggle dengan perbaikan
        const mobileMenuButton = document.getElementById('mobile-menu-button');
        const sidebar = document.getElementById('sidebar');

        if (mobileMenuButton) {
            mobileMenuButton.addEventListener('click', (e) => {
                e.stopPropagation(); // Prevent event bubbling
                sidebar.classList.toggle('-translate-x-full');
                sidebar.classList.toggle('mobile-menu-open');
            });
        }

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
        // Realtime Clock
        function updateClock() {
            const now = new Date();
            const timeString = now.toLocaleTimeString('id-ID', {
                hour: '2-digit',
                minute: '2-digit',
                second: '2-digit'
            });
            const dateString = now.toLocaleDateString('id-ID', {
                weekday: 'long',
                year: 'numeric',
                month: 'long',
                day: 'numeric'
            });

            document.getElementById('realtime-clock').textContent = timeString.replaceAll('.', ':');
            document.getElementById('realtime-date').textContent = dateString;
        }
        setInterval(updateClock, 1000);
        updateClock(); // initial call

        // Task Status per Matkul Chart
        const taskCtx = document.getElementById('taskStatusChart').getContext('2d');
        new Chart(taskCtx, {
            type: 'bar',
            data: {
                labels: <?= json_encode($task_matkul_labels) ?>,
                datasets: [{
                        label: 'Selesai',
                        data: <?= json_encode($task_matkul_selesai) ?>,
                        backgroundColor: '#10b981',
                        borderRadius: 4
                    },
                    {
                        label: 'Belum Selesai',
                        data: <?= json_encode($task_matkul_belum) ?>,
                        backgroundColor: '#f59e0b',
                        borderRadius: 4
                    }
                ]
            },
            options: {
                indexAxis: 'y',
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            usePointStyle: true,
                            font: { size: 9 }
                        }
                    }
                },
                scales: {
                    x: {
                        stacked: true,
                        grid: { display: false },
                        ticks: { font: { size: 9 }, precision: 0 }
                    },
                    y: {
                        stacked: true,
                        grid: { display: false },
                        ticks: { font: { size: 9 } }
                    }
                }
            }
        });


        // Attendance Chart
        const attendCtx = document.getElementById('attendanceChart').getContext('2d');
        new Chart(attendCtx, {
            type: 'bar',
            data: {
                labels: ['Hadir', 'Izin', 'Sakit', 'Alpha'],
                datasets: [{
                    label: 'Jumlah Absensi',
                    data: [<?= $absensi_stats['Hadir'] ?>, <?= $absensi_stats['Izin'] ?>, <?= $absensi_stats['Sakit'] ?>, <?= $absensi_stats['Alpha'] ?>],
                    backgroundColor: ['#10b981', '#3b82f6', '#f59e0b', '#ef4444'],
                    borderRadius: 4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: {
                            display: false
                        },
                        ticks: {
                            font: {
                                size: 9
                            }
                        }
                    },
                    x: {
                        grid: {
                            display: false
                        },
                        ticks: {
                            font: {
                                size: 9
                            }
                        }
                    }
                }
            }
        });

        // Finance Chart
        const financeCtx = document.getElementById('financeChart').getContext('2d');
        const kasHistory = <?= json_encode($kas_history) ?>;
        new Chart(financeCtx, {
            type: 'line',
            data: {
                labels: kasHistory.map(h => h.tanggal),
                datasets: [{
                        label: 'Total Saldo',
                        data: kasHistory.map(h => h.saldo),
                        borderColor: '#10b981',
                        backgroundColor: 'rgba(16, 185, 129, 0.1)',
                        fill: true,
                        tension: 0.3
                    },
                    {
                        label: 'Pemasukan',
                        data: kasHistory.map(h => h.masuk),
                        borderColor: '#6366f1',
                        borderDash: [5, 5],
                        borderWidth: 1.5,
                        hidden: true
                    },
                    {
                        label: 'Pengeluaran',
                        data: kasHistory.map(h => h.keluar),
                        borderColor: '#ef4444',
                        borderDash: [5, 5],
                        borderWidth: 1.5,
                        hidden: true
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            usePointStyle: true,
                            font: {
                                size: 10
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        grid: {
                            color: '#f1f5f9'
                        },
                        ticks: {
                            font: {
                                size: 9
                            }
                        }
                    },
                    x: {
                        grid: {
                            display: false
                        },
                        ticks: {
                            font: {
                                size: 9
                            }
                        }
                    }
                }
            }
        });

        // Message Chart
        const msgCtx = document.getElementById('messageChart').getContext('2d');
        const msgHistory = <?= json_encode($pesan_history) ?>;
        new Chart(msgCtx, {
            type: 'bar',
            data: {
                labels: msgHistory.map(h => h.tanggal),
                datasets: [{
                    label: 'Jumlah Pesan',
                    data: msgHistory.map(h => h.jumlah),
                    backgroundColor: '#8b5cf6',
                    borderRadius: 4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            precision: 0,
                            font: {
                                size: 9
                            }
                        }
                    },
                    x: {
                        grid: {
                            display: false
                        },
                        ticks: {
                            font: {
                                size: 9
                            }
                        }
                    }
                }
            }
        });
    </script>
</body>

</html>