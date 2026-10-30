<?php
session_start();
include 'koneksi.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: index");
    exit;
}

$user_id = $_SESSION['user_id'];
$nama_lengkap = $_SESSION['nama_lengkap'] ?? '';
$foto = $_SESSION['foto'] ?? '';
$role = $_SESSION['role'];

// Routing dashboard per peran spesifik
if ($role === 'Administrator Utama') {
    header("Location: dashboard_admin.php");
    exit;
} elseif ($role === 'Ketua Tingkat') {
    header("Location: dashboard_keti.php");
    exit;
} elseif ($role === 'Sekretaris') {
    header("Location: dashboard_sekretaris.php");
    exit;
}

// Include foto helpers for cache busting
include_once 'functions/foto_helpers.php';

/* =====================
   AMBIL PERIODE AKTIF
   ===================== */
$q_periode = mysqli_query($koneksi, "SELECT * FROM periode WHERE status = 'aktif' LIMIT 1");
$periode = mysqli_fetch_assoc($q_periode);
$periode_text = $periode ? $periode['tahun_periode'] . ' - ' . $periode['semester_nama'] : 'Tidak ada periode aktif';

// counts
$counts = [];
$tables = [
    'mahasiswa' => 'database/db_mahasiswa',
    'mata_kuliah' => 'database/db_matkul',
    'dosen' => 'database/db_dosen',
    'jadwal_matkul' => 'database/db_jadwal',
    'tugas' => 'database/db_tugas',
    'akademik' => 'database/db_berita',
    'kelompok' => 'views/kelompok'
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

/* =====================
   TENTUKAN RENTANG TANGGAL SEMESTER (OTOMATIS)
   ===================== */
$q_start = mysqli_query($koneksi, "SELECT tanggal_mulai FROM kalender_akademik WHERE periode_id = '$periode_id' AND kegiatan LIKE '%Kuliah Bagian Pertama%' ORDER BY tanggal_mulai ASC LIMIT 1");
$start_res = mysqli_fetch_assoc($q_start);
$semester_start = $start_res['tanggal_mulai'] ?? date('Y-m-01'); // Default awal bulan ini jika tidak ada data

$q_end = mysqli_query($koneksi, "SELECT tanggal_selesai FROM kalender_akademik WHERE periode_id = '$periode_id' AND kegiatan LIKE '%Kuliah Bagian Kedua%' ORDER BY tanggal_selesai DESC LIMIT 1");
$end_res = mysqli_fetch_assoc($q_end);
$semester_end = $end_res['tanggal_selesai'] ?? date('Y-m-t'); // Default akhir bulan ini jika tidak ada data

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
// Schedule Today
$q_sch_today = mysqli_query($koneksi, "SELECT mk.nama_matkul, jk_start.jam_mulai, jk_end.jam_selesai, r.nama_ruangan AS ruangan FROM jadwal_matkul jm JOIN mata_kuliah mk ON jm.matkul_id = mk.id LEFT JOIN ruangan r ON jm.ruangan_id = r.id LEFT JOIN jam_kuliah jk_start ON jm.jam_mulai_ke = jk_start.jam_ke LEFT JOIN jam_kuliah jk_end ON jm.jam_selesai_ke = jk_end.jam_ke WHERE jm.hari = '$today_indo' AND jm.periode_id = '$periode_id' ORDER BY jk_start.jam_mulai ASC");
$sch_today = [];
while ($r = mysqli_fetch_assoc($q_sch_today)) $sch_today[] = $r;

// Schedule Tomorrow
$q_sch_tomorrow = mysqli_query($koneksi, "SELECT mk.nama_matkul, jk_start.jam_mulai, jk_end.jam_selesai, r.nama_ruangan AS ruangan FROM jadwal_matkul jm JOIN mata_kuliah mk ON jm.matkul_id = mk.id LEFT JOIN ruangan r ON jm.ruangan_id = r.id LEFT JOIN jam_kuliah jk_start ON jm.jam_mulai_ke = jk_start.jam_ke LEFT JOIN jam_kuliah jk_end ON jm.jam_selesai_ke = jk_end.jam_ke WHERE jm.hari = '$tomorrow_indo' AND jm.periode_id = '$periode_id' ORDER BY jk_start.jam_mulai ASC");
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
$absensi_stats_raw = ['Hadir' => 0, 'Izin' => 0, 'Sakit' => 0, 'Alpha' => 0];
$total_absensi = 0;
while ($row = mysqli_fetch_assoc($q_absensi)) {
    if (isset($absensi_stats_raw[$row['status']])) {
        $absensi_stats_raw[$row['status']] = (int)$row['cnt'];
        $total_absensi += (int)$row['cnt'];
    }
}

// Hitung Persentase
$absensi_stats = ['Hadir' => 0, 'Izin' => 0, 'Sakit' => 0, 'Alpha' => 0];
if ($total_absensi > 0) {
    foreach ($absensi_stats_raw as $status => $count) {
        $absensi_stats[$status] = round(($count / $total_absensi) * 100, 1);
    }
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



// 7. Data Kas Per Minggu (Stacked Bar - Lunas vs Belum)
$total_mhs = (int)($counts['mahasiswa'] ?? 0);
$q_nom = mysqli_query($koneksi, "SELECT nominal FROM kas WHERE nominal > 0 AND periode_id = '$periode_id' LIMIT 1");
$fixed_nom = mysqli_fetch_assoc($q_nom)['nominal'] ?? 5000;

$kas_per_minggu_history = [];
for ($i = 1; $i <= 16; $i++) {
    $kas_per_minggu_history[$i] = [
        'minggu' => "M-" . $i,
        'lunas_nominal' => 0,
        'belum_nominal' => $total_mhs * $fixed_nom,
        'lunas_cnt' => 0,
        'persen' => 0
    ];
}

$q_kas_per_minggu = mysqli_query($koneksi, "SELECT minggu_ke, SUM(nominal) as total, COUNT(*) as lunas_count FROM kas WHERE status = 'Lunas' AND periode_id = '$periode_id' GROUP BY minggu_ke ORDER BY minggu_ke ASC");
while ($row = mysqli_fetch_assoc($q_kas_per_minggu)) {
    $m_ke = (int)$row['minggu_ke'];
    if (isset($kas_per_minggu_history[$m_ke])) {
        $l_cnt = (int)$row['lunas_count'];
        $l_nom = (int)$row['total'];
        $kas_per_minggu_history[$m_ke]['lunas_nominal'] = $l_nom;
        $kas_per_minggu_history[$m_ke]['lunas_cnt'] = $l_cnt;
        $kas_per_minggu_history[$m_ke]['belum_nominal'] = max(0, ($total_mhs - $l_cnt) * $fixed_nom);
        $kas_per_minggu_history[$m_ke]['persen'] = $total_mhs > 0 ? round(($l_cnt / $total_mhs) * 100) : 0;
    }
}
// Reindex to 0-based for JS compatibility
$kas_per_minggu_history = array_values($kas_per_minggu_history);

function getFireColor($streak)
{
    if ($streak >= 200) return '#A020F0';
    if ($streak >= 100) return '#0055FF';
    if ($streak >= 50) return '#00A2FF';
    if ($streak >= 30) return '#FF3300';
    if ($streak >= 10) return '#FF8800';
    if ($streak >= 3) return '#FFC300';
    return null;
}


?>
<!DOCTYPE html>
<html lang="en">

<head>
    <title>Dashboard - SIMAWA PTIK 2024</title>
    <?php include 'includes/head.php'; ?>
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

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

</head>

<body class="bg-slate-100 dark:bg-slate-950 transition-colors duration-300">
    <?php include 'includes/menu_db.php'; ?>

    <main class="ml-0 lg:ml-48 pt-20 p-6 flex flex-col min-h-screen">
        <div class="max-w-6xl mx-auto flex-grow w-full">


            <!-- Welcome & Realtime Section -->
            <div class="mb-6 bg-white dark:bg-slate-900 p-6 rounded-lg shadow-sm border border-slate-200 dark:border-slate-800 flex flex-col md:flex-row justify-between items-center gap-4 transition-all">
                <div class="flex items-center gap-4">
                    <div class="h-14 w-14 flex-shrink-0 aspect-square rounded-full overflow-hidden border-2 border-indigo-200 dark:border-indigo-900/50">
                        <img src="<?= profilePhotoUrl($foto) ?>" alt="Profile" class="w-full h-full object-cover">
                    </div>
                    <div>
                        <h2 class="font-bold text-lg text-slate-800 dark:text-slate-100">Selamat Datang, <span class="text-indigo-600 dark:text-indigo-400"><?= htmlspecialchars($nama_lengkap) ?></span>!</h2>
                        <p class="text-sm text-slate-600 dark:text-slate-400 flex items-center gap-2">
                            <i class="fa fa-calendar-check text-green-600 dark:text-green-400"></i>
                            Periode: <span class="font-semibold bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-400 px-2 py-0.5 rounded text-xs"><?= htmlspecialchars($periode_text) ?></span>
                        </p>
                    </div>
                </div>

                <div class="flex items-center gap-4 mt-2 md:mt-0">
                    <div class="flex items-center gap-1.5 bg-slate-100 dark:bg-slate-800 px-3 py-1.5 rounded-lg border border-slate-200 dark:border-slate-700">
                        <i class="fa fa-calendar-alt text-indigo-500 dark:text-indigo-400 text-xs"></i>
                        <div id="realtime-date" class="text-xs font-medium text-slate-600 dark:text-slate-400 uppercase tracking-wide">...</div>
                    </div>
                    <div class="flex items-center gap-1.5 bg-slate-100 dark:bg-slate-800 px-3 py-1.5 rounded-lg border border-slate-200 dark:border-slate-700">
                        <i class="fa fa-clock text-indigo-500 dark:text-indigo-400 text-xs"></i>
                        <div id="realtime-clock" class="text-xs font-mono font-bold text-slate-700 dark:text-slate-200 tracking-wider">--:--:--</div>
                    </div>
                </div>

            </div>

            <div class="grid grid-cols-2 lg:grid-cols-4 gap-6">
                <a href="database/db_mahasiswa" class="bg-white dark:bg-slate-900 p-4 rounded-xl shadow-sm border border-slate-200 dark:border-slate-800 card-hover flex items-center gap-4 transition-all">
                    <div class="h-12 w-12 bg-indigo-100 dark:bg-indigo-950/40 rounded-lg flex items-center justify-center text-indigo-700 dark:text-indigo-400">
                        <i class="fa fa-user-graduate text-xl"></i>
                    </div>
                    <div>
                        <div class="text-[10px] font-bold text-slate-400 dark:text-slate-500 uppercase tracking-wider">Mahasiswa</div>
                        <div class="text-xl font-bold text-slate-800 dark:text-slate-100"><?= number_format($counts['mahasiswa']) ?></div>
                    </div>
                </a>

                <a href="database/db_tugas" class="bg-white dark:bg-slate-900 p-4 rounded-xl shadow-sm border border-slate-200 dark:border-slate-800 card-hover flex items-center gap-4 transition-all">
                    <div class="h-12 w-12 bg-indigo-100 dark:bg-indigo-950/40 rounded-lg flex items-center justify-center text-indigo-700 dark:text-indigo-400">
                        <i class="fa fa-tasks text-xl"></i>
                    </div>
                    <div>
                        <div class="text-[10px] font-bold text-slate-400 dark:text-slate-500 uppercase tracking-wider">Tugas Aktif</div>
                        <div class="text-xl font-bold text-slate-800 dark:text-slate-100"><?= number_format($counts['tugas']) ?></div>
                    </div>
                </a>

                <a href="views/kelompok" class="bg-white dark:bg-slate-900 p-4 rounded-xl shadow-sm border border-slate-200 dark:border-slate-800 card-hover flex items-center gap-4 transition-all">
                    <div class="h-12 w-12 bg-indigo-100 dark:bg-indigo-950/40 rounded-lg flex items-center justify-center text-indigo-700 dark:text-indigo-400">
                        <i class="fa fa-users text-xl"></i>
                    </div>
                    <div>
                        <div class="text-[10px] font-bold text-slate-400 dark:text-slate-500 uppercase tracking-wider">Data Kelompok</div>
                        <div class="text-xl font-bold text-slate-800 dark:text-slate-100"><?= number_format($counts['kelompok']) ?></div>
                    </div>
                </a>

                <a href="database/db_jadwal" class="bg-white dark:bg-slate-900 p-4 rounded-xl shadow-sm border border-slate-200 dark:border-slate-800 card-hover flex items-center gap-4 transition-all">
                    <div class="h-12 w-12 bg-indigo-100 dark:bg-indigo-950/40 rounded-lg flex items-center justify-center text-indigo-700 dark:text-indigo-400">
                        <i class="fa fa-list-alt text-xl"></i>
                    </div>
                    <div>
                        <div class="text-[10px] font-bold text-slate-400 dark:text-slate-500 uppercase tracking-wider">Jadwal Kuliah</div>
                        <div class="text-xl font-bold text-slate-800 dark:text-slate-100"><?= number_format($counts['jadwal_matkul']) ?></div>
                    </div>
                </a>
            </div>

            <!-- Info Slider Section -->
            <div class="mt-6 relative overflow-hidden bg-white dark:bg-slate-900 rounded-xl shadow-sm border border-slate-200 dark:border-slate-800 h-28 transition-all">
                <div id="infoSlider" class="flex transition-transform duration-700 ease-in-out h-full">

                    <!-- Slide 1: Jadwal -->
                    <div class="min-w-full p-4 flex items-center gap-6">
                        <div class="h-16 w-16 bg-blue-100 dark:bg-blue-950/40 rounded-2xl flex items-center justify-center text-blue-600 dark:text-blue-400 shrink-0">
                            <i class="fa fa-calendar-alt text-2xl"></i>
                        </div>
                        <div class="flex-grow grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <div class="text-[10px] font-bold text-slate-400 dark:text-slate-500 uppercase tracking-wider mb-1">Kuliah Hari Ini (<?= $today_indo ?>)</div>
                                <div class="text-sm font-bold text-slate-700 dark:text-slate-200">
                                    <?php if (empty($sch_today)): ?>
                                        <span class="text-slate-400 dark:text-slate-500 italic font-normal lowercase">Tidak ada jadwal kuliah hari ini.</span>
                                    <?php else: ?>
                                        <div class="flex flex-col <?= count($sch_today) > 1 ? 'gap-0' : '' ?>">
                                            <?php foreach ($sch_today as $s): ?>
                                                <div class="<?= count($sch_today) > 1 ? 'text-[11px] leading-tight' : 'text-sm' ?> font-bold text-slate-700 dark:text-slate-200 truncate">
                                                    • <?= $s['nama_matkul'] ?>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div class="hidden md:block border-l border-slate-100 dark:border-slate-800 pl-4">
                                <div class="text-[10px] font-bold text-slate-400 dark:text-slate-500 uppercase tracking-wider mb-1">Besok (<?= $tomorrow_indo ?>)</div>
                                <div class="text-sm font-bold text-slate-700 dark:text-slate-200">
                                    <?php if (empty($sch_tomorrow)): ?>
                                        <span class="text-slate-400 dark:text-slate-500 italic font-normal lowercase">Tidak ada jadwal.</span>
                                    <?php else: ?>
                                        <div class="flex flex-col <?= count($sch_tomorrow) > 1 ? 'gap-0' : '' ?>">
                                            <?php foreach ($sch_tomorrow as $s): ?>
                                                <div class="<?= count($sch_tomorrow) > 1 ? 'text-[11px] leading-tight' : 'text-sm' ?> font-bold text-slate-700 dark:text-slate-200 truncate">
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
                        <div class="h-16 w-16 bg-orange-100 dark:bg-orange-950/40 rounded-2xl flex items-center justify-center text-orange-600 dark:text-orange-400 shrink-0">
                            <i class="fa fa-hourglass-half text-2xl"></i>
                        </div>
                        <div class="flex-grow grid grid-cols-1 <?= count($closest_tasks) > 1 ? 'md:grid-cols-2' : '' ?> gap-4">
                            <?php if (!empty($closest_tasks)): ?>
                                <?php foreach ($closest_tasks as $index => $task): ?>
                                    <div class="<?= $index === 1 ? 'hidden md:block border-l border-slate-100 dark:border-slate-800 pl-4' : '' ?>">
                                        <div class="text-[10px] font-bold text-slate-400 dark:text-slate-500 uppercase tracking-wider mb-1">
                                            <?= $index === 0 ? 'Deadline Terdekat' : 'Deadline Berikutnya' ?>
                                        </div>
                                        <div class="text-sm font-bold text-slate-700 dark:text-slate-200 mb-1 truncate"><?= $task['nama_matkul'] ?></div>
                                        <div class="flex items-center gap-2">
                                            <span class="px-2 py-0.5 bg-orange-100 dark:bg-orange-900/30 text-orange-700 dark:text-orange-400 rounded text-[10px] font-bold uppercase whitespace-nowrap">
                                                <?= date('d M Y', strtotime($task['deadline_tanggal'])) ?> • <?= date('H:i', strtotime($task['deadline_pukul'])) ?>
                                            </span>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <div>
                                    <div class="text-[10px] font-bold text-slate-400 dark:text-slate-500 uppercase tracking-wider mb-1">Deadline Terdekat</div>
                                    <div class="text-sm text-slate-400 dark:text-slate-500 italic">Semua tugas telah selesai dikerjakan! ✨</div>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Slide 3: Distribusi Gender -->
                    <div class="min-w-full p-4 flex items-center gap-6">
                        <div class="h-16 w-16 bg-pink-100 dark:bg-pink-950/40 rounded-2xl flex items-center justify-center text-pink-600 dark:text-pink-400 shrink-0">
                            <i class="fa fa-venus-mars text-2xl"></i>
                        </div>
                        <div class="flex-grow grid grid-cols-2 gap-4">
                            <div>
                                <div class="text-[10px] font-bold text-slate-400 dark:text-slate-500 uppercase tracking-wider mb-1">Mahasiswa Laki-laki</div>
                                <div class="text-lg font-bold text-indigo-600 dark:text-indigo-400"><?= number_format($gender_data['Laki-laki']) ?> <span class="text-xs font-normal text-slate-400 dark:text-slate-500">Orang</span></div>
                            </div>
                            <div class="border-l border-slate-100 dark:border-slate-800 pl-4">
                                <div class="text-[10px] font-bold text-slate-400 dark:text-slate-500 uppercase tracking-wider mb-1">Mahasiswa Perempuan</div>
                                <div class="text-lg font-bold text-pink-500 dark:text-pink-400"><?= number_format($gender_data['Perempuan']) ?> <span class="text-xs font-normal text-slate-400 dark:text-slate-500">Orang</span></div>
                            </div>
                        </div>
                    </div>

                    <!-- Slide 4: Kalender Akademik -->
                    <div class="min-w-full p-4 flex items-center gap-6">
                        <div class="h-16 w-16 bg-blue-100 dark:bg-blue-950/40 rounded-2xl flex items-center justify-center text-blue-600 dark:text-blue-400 shrink-0">
                            <i class="fa fa-calendar-alt text-2xl"></i>
                        </div>
                        <div class="flex-grow grid grid-cols-1 <?= count($academic_events) > 1 ? 'md:grid-cols-2' : '' ?> gap-4">
                            <?php if (!empty($academic_events)): ?>
                                <?php foreach ($academic_events as $index => $event): ?>
                                    <div class="<?= $index === 1 ? 'hidden md:block border-l border-slate-100 dark:border-slate-800 pl-4' : '' ?>">
                                        <div class="text-[10px] font-bold text-blue-500 dark:text-blue-400 uppercase tracking-wider mb-1">
                                            <?= $event['status_event'] ?>
                                        </div>
                                        <?php
                                        // Perkecil font jika teks terlalu panjang (> 30 karakter)
                                        $fontClass = (strlen($event['kegiatan']) > 30) ? 'text-[11px]' : 'text-sm';
                                        ?>
                                        <div class="<?= $fontClass ?> font-bold text-slate-700 dark:text-slate-200 mb-1" title="<?= htmlspecialchars($event['kegiatan']) ?>">
                                            <?= htmlspecialchars($event['kegiatan']) ?>
                                        </div>
                                        <div class="text-[10px] text-slate-500 dark:text-slate-400 font-medium">
                                            <?= date('d M', strtotime($event['tanggal_mulai'])) ?> - <?= date('d M Y', strtotime($event['tanggal_selesai'])) ?>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <div>
                                    <div class="text-[10px] font-bold text-slate-400 dark:text-slate-500 uppercase tracking-wider mb-1">Kalender Akademik</div>
                                    <div class="text-sm text-slate-400 dark:text-slate-500 italic">Tidak ada agenda akademik terdekat.</div>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                </div>

                <!-- Slider Indicators -->
                <div class="absolute bottom-2 right-4 flex gap-1.5">
                    <div class="h-1.5 w-4 rounded-full bg-indigo-900 dark:bg-indigo-500 indicator" data-index="0"></div>
                    <div class="h-1.5 w-1.5 rounded-full bg-slate-200 dark:bg-slate-700 indicator" data-index="1"></div>
                    <div class="h-1.5 w-1.5 rounded-full bg-slate-200 dark:bg-slate-700 indicator" data-index="2"></div>
                    <div class="h-1.5 w-1.5 rounded-full bg-slate-200 dark:bg-slate-700 indicator" data-index="3"></div>
                </div>
            </div>

            <!-- --- SECTION 1: AKADEMIK --- -->
            <div class="mt-12 border-b-2 border-blue-100 dark:border-blue-900/30 pb-2 mb-6 transition-all">
                <h2 class="text-xs font-black text-blue-600 dark:text-blue-400 uppercase tracking-[0.2em] flex items-center gap-3">
                    <span class="h-8 w-8 rounded-lg bg-blue-50 dark:bg-blue-900/20 flex items-center justify-center">
                        <i class="fa fa-graduation-cap"></i>
                    </span>
                    Manajemen Akademik
                </h2>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <!-- Task Distribution per Subject -->
                <div class="bg-white dark:bg-slate-900 p-6 rounded-xl shadow-sm border border-slate-200 dark:border-slate-800 transition-all">
                    <h3 class="text-sm font-bold text-slate-800 dark:text-slate-100 mb-4 flex items-center gap-2">
                        <i class="fa fa-tasks text-indigo-500"></i>
                        Distribusi Tugas per Matkul
                    </h3>
                    <div class="relative h-64">
                        <canvas id="taskStatusChart"></canvas>
                    </div>
                </div>

                <!-- Attendance Statistics -->
                <div class="bg-white dark:bg-slate-900 p-6 rounded-xl shadow-sm border border-slate-200 dark:border-slate-800 transition-all">
                    <div class="flex flex-col md:flex-row md:justify-between md:items-center gap-3 mb-4">
                        <h3 class="text-sm font-bold text-slate-800 dark:text-slate-100 flex items-center gap-2">
                            <i class="fa fa-user-check text-green-500"></i>
                            Statistik Absensi
                        </h3>
                        <div class="flex items-center gap-2">
                            <select name="abs_matkul" form="vizFilters" onchange="this.form.submit()" class="text-[10px] bg-slate-50 dark:bg-slate-800 border-slate-200 dark:border-slate-700 text-slate-700 dark:text-slate-200 rounded p-1 focus:ring-indigo-500">
                                <option value="">Semua Matkul</option>
                                <?php foreach ($abs_matkuls as $m): ?>
                                    <option value="<?= $m['matkul_id'] ?>" <?= $abs_matkul == $m['matkul_id'] ? 'selected' : '' ?>><?= htmlspecialchars($m['nama_matkul']) ?></option>
                                <?php endforeach; ?>
                            </select>
                            <select name="abs_pertemuan" form="vizFilters" onchange="this.form.submit()" class="text-[10px] bg-slate-50 dark:bg-slate-800 border-slate-200 dark:border-slate-700 text-slate-700 dark:text-slate-200 rounded p-1 focus:ring-indigo-500">
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

            <!-- --- SECTION: TREN & ANALITIK --- -->
            <form id="vizFilters" method="GET" class="hidden">
                <?php if (isset($_GET['periode_id'])): ?><input type="hidden" name="periode_id" value="<?= $_GET['periode_id'] ?>"><?php endif; ?>
            </form>

            <!-- 1. Tren Arus Kas dan Pemasukan Kas per Minggu -->
            <div class="mt-12 border-b-2 border-emerald-100 dark:border-emerald-900/30 pb-2 mb-6 transition-all">
                <h2 class="text-xs font-black text-emerald-600 dark:text-emerald-400 uppercase tracking-[0.2em] flex items-center gap-3">
                    <span class="h-8 w-8 rounded-lg bg-emerald-50 dark:bg-emerald-900/20 flex items-center justify-center">
                        <i class="fa fa-piggy-bank"></i>
                    </span>
                    Analitik Keuangan
                </h2>
            </div>
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-12">
                <!-- Financial Trend Line Chart -->
                <div class="bg-white dark:bg-slate-900 p-6 rounded-xl shadow-sm border border-slate-200 dark:border-slate-800 transition-all">
                    <div class="flex flex-col md:flex-row md:justify-between md:items-center gap-3 mb-4">
                        <div class="min-w-0">
                            <h3 class="text-sm font-bold text-slate-800 dark:text-slate-100 flex items-center gap-2">
                                <i class="fa fa-line-chart text-green-500"></i>
                                Tren Arus Kas
                            </h3>
                        </div>
                        <div class="flex items-center justify-between sm:justify-start gap-1 bg-slate-50 dark:bg-slate-800 p-1 rounded border border-slate-200 dark:border-slate-700 w-full sm:w-auto">
                            <input type="date" name="kas_start" value="<?= $kas_start ?>" form="vizFilters" onchange="this.form.submit()" class="text-[10px] font-bold text-slate-600 dark:text-slate-300 bg-transparent border-none focus:ring-0 p-0 flex-grow sm:flex-none w-0 sm:w-24 text-center">
                            <span class="text-slate-300 dark:text-slate-600 text-[10px]">-</span>
                            <input type="date" name="kas_end" value="<?= $kas_end ?>" form="vizFilters" onchange="this.form.submit()" class="text-[10px] font-bold text-slate-600 dark:text-slate-300 bg-transparent border-none focus:ring-0 p-0 flex-grow sm:flex-none w-0 sm:w-24 text-center">
                        </div>
                    </div>
                    <div class="relative h-72">
                        <canvas id="financeChart"></canvas>
                    </div>
                </div>

                <!-- Weekly Cash Bar Chart -->
                <div class="bg-white dark:bg-slate-900 p-6 rounded-xl shadow-sm border border-slate-200 dark:border-slate-800 transition-all">
                    <div class="flex flex-col md:flex-row md:justify-between md:items-center gap-3 mb-4">
                        <h3 class="text-sm font-bold text-slate-800 dark:text-slate-100 flex items-center gap-2">
                            <i class="fa fa-chart-bar text-green-500"></i>
                            Pemasukan Kas per Minggu
                        </h3>
                        <div class="flex items-center gap-1 bg-slate-50 dark:bg-slate-800 p-1 rounded border border-slate-200 dark:border-slate-700">
                           <span class="text-[10px] font-bold text-slate-500 dark:text-slate-400 px-2 uppercase tracking-tight">Data Per Minggu</span>
                        </div>
                    </div>
                    <div class="relative h-72">
                        <canvas id="kasMingguanChart"></canvas>
                    </div>
                </div>
            </div>

            <?php include 'includes/footer_db.php'; ?>
    </main>
    <?php include 'includes/script_db.php'; ?>
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
                    ind.classList.add('bg-indigo-500', 'dark:bg-indigo-500', 'w-4');
                    ind.classList.remove('bg-slate-200', 'dark:bg-slate-700', 'w-1.5');
                } else {
                    ind.classList.remove('bg-indigo-500', 'dark:bg-indigo-500', 'w-4');
                    ind.classList.add('bg-slate-200', 'dark:bg-slate-700', 'w-1.5');
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

        // Chart.js Global Dark Mode Config
        const isDarkMode = document.documentElement.classList.contains('dark');
        Chart.defaults.color = isDarkMode ? '#94a3b8' : '#64748b';
        Chart.defaults.borderColor = isDarkMode ? 'rgba(148, 163, 184, 0.1)' : '#f1f5f9';
        Chart.defaults.font.family = "'Inter', sans-serif";

        // Task Status per Matkul Chart
        const taskCtx = document.getElementById('taskStatusChart').getContext('2d');
        new Chart(taskCtx, {
            type: 'bar',
            data: {
                labels: <?= json_encode($task_matkul_labels) ?>,
                datasets: [{
                        label: 'Selesai',
                        data: <?= json_encode($task_matkul_selesai) ?>,
                        backgroundColor: '#615fff',
                        borderRadius: 4
                    },
                    {
                        label: 'Belum Selesai',
                        data: <?= json_encode($task_matkul_belum) ?>,
                        backgroundColor: isDarkMode ? '#1e293b' : '#f1f5f9',
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
                            font: {
                                size: 9
                            }
                        }
                    }
                },
                scales: {
                    x: {
                        stacked: true,
                        grid: {
                            display: false
                        },
                        ticks: {
                            font: {
                                size: 9
                            },
                            precision: 0
                        }
                    },
                    y: {
                        stacked: true,
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


        // Attendance Chart
        const attendCtx = document.getElementById('attendanceChart').getContext('2d');
        new Chart(attendCtx, {
            type: 'bar',
            data: {
                labels: ['Hadir', 'Izin', 'Sakit', 'Alpha'],
                datasets: [{
                    label: 'Persentase Absensi',
                    data: [<?= $absensi_stats['Hadir'] ?>, <?= $absensi_stats['Izin'] ?>, <?= $absensi_stats['Sakit'] ?>, <?= $absensi_stats['Alpha'] ?>],
                    rawCounts: [<?= $absensi_stats_raw['Hadir'] ?>, <?= $absensi_stats_raw['Izin'] ?>, <?= $absensi_stats_raw['Sakit'] ?>, <?= $absensi_stats_raw['Alpha'] ?>],
                    backgroundColor: ['#00bc7d', '#51a2ff', '#ffb900', '#ff2056'],
                    borderRadius: 4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                const percentage = context.parsed.y;
                                const count = context.dataset.rawCounts[context.dataIndex];
                                return context.label + ': ' + percentage + '% (' + count + ' Mhs)';
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        max: 100,
                        grid: {
                            display: false
                        },
                        ticks: {
                            font: {
                                size: 9
                            },
                            callback: function(value) {
                                return value + "%";
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
                            color: isDarkMode ? 'rgba(148, 163, 184, 0.1)' : '#f1f5f9'
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

        // Weekly Cash Chart
        const kasCtx = document.getElementById('kasMingguanChart').getContext('2d');
        const kasMingguanHistory = <?= json_encode($kas_per_minggu_history) ?>;
        new Chart(kasCtx, {
            type: 'bar',
            data: {
                labels: kasMingguanHistory.map(h => h.minggu),
                datasets: [
                    {
                        label: 'Lunas',
                        data: kasMingguanHistory.map(h => h.lunas_nominal),
                        backgroundColor: '#10b981',
                        borderRadius: 4
                    },
                    {
                        label: 'Belum Lunas',
                        data: kasMingguanHistory.map(h => h.belum_nominal),
                        backgroundColor: isDarkMode ? '#1e293b' : '#f1f5f9',
                        borderRadius: 4
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: true,
                        position: 'bottom',
                        labels: {
                            usePointStyle: true,
                            font: { size: 9 }
                        }
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                let item = kasMingguanHistory[context.dataIndex];
                                let label = context.dataset.label;
                                let val = context.parsed.y;
                                let res = label + ': Rp ' + val.toLocaleString('id-ID');
                                if (label === 'Lunas') res += ' (' + item.persen + '%)';
                                return res;
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        stacked: true,
                        beginAtZero: true,
                        ticks: {
                            font: { size: 9 },
                            callback: function(value) {
                                if (value >= 1000000) return 'Rp ' + (value / 1000000) + 'jt';
                                if (value >= 1000) return 'Rp ' + (value / 1000) + 'k';
                                return 'Rp ' + value;
                            }
                        }
                    },
                    x: {
                        stacked: true,
                        grid: { display: false },
                        ticks: { font: { size: 9 } }
                    }
                }
            }
        });

        </script>
    </body>

</html>