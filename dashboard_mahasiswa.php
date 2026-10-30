<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include 'koneksi.php';
require_once 'functions/helpers.php';
require_once 'functions/foto_helpers.php';

// Proteksi Akses: Pengguna Terautentikasi
if (!isset($_SESSION['user_id'])) {
    header("Location: index");
    exit();
}

$user_id      = $_SESSION['user_id'];
$nama_lengkap = $_SESSION['nama_lengkap'] ?? 'Mahasiswa';
$foto         = $_SESSION['foto'] ?? '';
$role         = $_SESSION['role'] ?? 'Mahasiswa';

// Dapatkan kelas_id terikat untuk mahasiswa aktif
$kelas_id = getKelasScope();
if (!$kelas_id && $role !== 'Administrator Utama') {
    $_SESSION['error'] = "Akun Anda belum terikat ke kelas manapun.";
    header("Location: index");
    exit();
}

$nama_kelas_aktif = getNamaKelasAktif();

/* =========================================================
   1. PERIODE AKADEMIK AKTIF
   ========================================================= */
$q_periode = mysqli_query($koneksi, "SELECT * FROM periode WHERE status = 'aktif' LIMIT 1");
$periode = mysqli_fetch_assoc($q_periode);
$periode_id = (int)($periode['id'] ?? 0);
$periode_text = $periode ? $periode['tahun_periode'] . ' - ' . $periode['semester_nama'] : 'Tidak ada periode aktif';

/* =========================================================
   2. REKAPITULASI DATA STATISTIK KELAS (SCOPED)
   ========================================================= */
$counts = [];

// 1. Mahasiswa di kelas aktif
$q_cnt_mhs = mysqli_query($koneksi, "SELECT COUNT(*) AS cnt FROM mahasiswa WHERE kelas_id = '$kelas_id'");
$counts['mahasiswa'] = (int)(mysqli_fetch_assoc($q_cnt_mhs)['cnt'] ?? 0);

// 2. Tugas aktif kelas
$q_cnt_tugas = mysqli_query($koneksi, "SELECT COUNT(*) AS cnt FROM tugas WHERE status = 'belum' AND periode_id = '$periode_id' AND kelas_id = '$kelas_id'");
$counts['tugas'] = (int)(mysqli_fetch_assoc($q_cnt_tugas)['cnt'] ?? 0);

// 3. Kelompok kelas
$q_cnt_kelompok = mysqli_query($koneksi, "SELECT COUNT(*) AS cnt FROM kelompok WHERE periode_id = '$periode_id' AND kelas_id = '$kelas_id'");
$counts['kelompok'] = (int)(mysqli_fetch_assoc($q_cnt_kelompok)['cnt'] ?? 0);

// 4. Jadwal kuliah kelas
$q_cnt_jadwal = mysqli_query($koneksi, "SELECT COUNT(*) AS cnt FROM jadwal_matkul WHERE periode_id = '$periode_id' AND kelas_id = '$kelas_id'");
$counts['jadwal_matkul'] = (int)(mysqli_fetch_assoc($q_cnt_jadwal)['cnt'] ?? 0);

/* =========================================================
   3. DISTRIBUSI TUGAS PER MATA KULIAH KELAS
   ========================================================= */
$q_task_matkul = mysqli_query($koneksi, "
    SELECT mk.nama_matkul, 
        SUM(CASE WHEN t.status = 'selesai' THEN 1 ELSE 0 END) as selesai,
        SUM(CASE WHEN t.status = 'belum' THEN 1 ELSE 0 END) as belum
    FROM tugas t 
    JOIN mata_kuliah mk ON t.matkul_id = mk.id 
    WHERE t.periode_id = '$periode_id' AND t.kelas_id = '$kelas_id' 
    GROUP BY t.matkul_id
    ORDER BY (sum(CASE WHEN t.status = 'selesai' THEN 1 ELSE 0 END) + sum(CASE WHEN t.status = 'belum' THEN 1 ELSE 0 END)) DESC
");
$task_matkul_labels = [];
$task_matkul_selesai = [];
$task_matkul_belum = [];
while ($row = mysqli_fetch_assoc($q_task_matkul)) {
    $task_matkul_labels[] = $row['nama_matkul'];
    $task_matkul_selesai[] = (int)$row['selesai'];
    $task_matkul_belum[] = (int)$row['belum'];
}

/* =========================================================
   4. DATA UNTUK INFO SLIDER
   ========================================================= */
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

// Jadwal Hari Ini
$q_sch_today = mysqli_query($koneksi, "
    SELECT mk.nama_matkul, jk_start.jam_mulai, jk_end.jam_selesai, r.nama_ruangan AS ruangan 
    FROM jadwal_matkul jm 
    JOIN mata_kuliah mk ON jm.matkul_id = mk.id 
    LEFT JOIN ruangan r ON jm.ruangan_id = r.id 
    LEFT JOIN jam_kuliah jk_start ON jm.jam_mulai_ke = jk_start.jam_ke 
    LEFT JOIN jam_kuliah jk_end ON jm.jam_selesai_ke = jk_end.jam_ke 
    WHERE jm.hari = '$today_indo' AND jm.periode_id = '$periode_id' AND jm.kelas_id = '$kelas_id' 
    ORDER BY jk_start.jam_mulai ASC
");
$sch_today = [];
while ($r = mysqli_fetch_assoc($q_sch_today)) $sch_today[] = $r;

// Jadwal Besok
$q_sch_tomorrow = mysqli_query($koneksi, "
    SELECT mk.nama_matkul, jk_start.jam_mulai, jk_end.jam_selesai, r.nama_ruangan AS ruangan 
    FROM jadwal_matkul jm 
    JOIN mata_kuliah mk ON jm.matkul_id = mk.id 
    LEFT JOIN ruangan r ON jm.ruangan_id = r.id 
    LEFT JOIN jam_kuliah jk_start ON jm.jam_mulai_ke = jk_start.jam_ke 
    LEFT JOIN jam_kuliah jk_end ON jm.jam_selesai_ke = jk_end.jam_ke 
    WHERE jm.hari = '$tomorrow_indo' AND jm.periode_id = '$periode_id' AND jm.kelas_id = '$kelas_id' 
    ORDER BY jk_start.jam_mulai ASC
");
$sch_tomorrow = [];
while ($r = mysqli_fetch_assoc($q_sch_tomorrow)) $sch_tomorrow[] = $r;

// Deadline Tugas Terdekat
$q_closest = mysqli_query($koneksi, "
    SELECT mk.nama_matkul, t.deadline_tanggal, t.deadline_pukul 
    FROM tugas t 
    JOIN mata_kuliah mk ON t.matkul_id = mk.id 
    WHERE t.status = 'belum' AND t.periode_id = '$periode_id' AND t.kelas_id = '$kelas_id' 
    ORDER BY t.deadline_tanggal ASC, t.deadline_pukul ASC 
    LIMIT 2
");
$closest_tasks = [];
while ($r = mysqli_fetch_assoc($q_closest)) $closest_tasks[] = $r;

// Kalender Akademik untuk Slider (Global)
$today_date = date('Y-m-d');
$academic_events = [];
$q_ongoing = mysqli_query($koneksi, "SELECT * FROM kalender_akademik WHERE tanggal_mulai <= '$today_date' AND tanggal_selesai >= '$today_date' ORDER BY tanggal_selesai ASC LIMIT 1");
$ongoing_event = mysqli_fetch_assoc($q_ongoing);

if ($ongoing_event) {
    $ongoing_event['status_event'] = 'Sedang Berlangsung';
    $academic_events[] = $ongoing_event;

    $q_upcoming = mysqli_query($koneksi, "SELECT * FROM kalender_akademik WHERE tanggal_mulai > '$today_date' ORDER BY tanggal_mulai ASC LIMIT 1");
    if ($r = mysqli_fetch_assoc($q_upcoming)) {
        $r['status_event'] = 'Akan Datang';
        $academic_events[] = $r;
    }
} else {
    $q_upcoming = mysqli_query($koneksi, "SELECT * FROM kalender_akademik WHERE tanggal_mulai > '$today_date' ORDER BY tanggal_mulai ASC LIMIT 2");
    while ($r = mysqli_fetch_assoc($q_upcoming)) {
        $r['status_event'] = 'Akan Datang';
        $academic_events[] = $r;
    }
}

// Distribusi Gender Mahasiswa Kelas
$q_gender = mysqli_query($koneksi, "SELECT jenis_kelamin, COUNT(*) as cnt FROM mahasiswa WHERE kelas_id = '$kelas_id' GROUP BY jenis_kelamin");
$gender_data = ['Laki-laki' => 0, 'Perempuan' => 0];
while ($row = mysqli_fetch_assoc($q_gender)) {
    if ($row['jenis_kelamin']) $gender_data[$row['jenis_kelamin']] = (int)$row['cnt'];
}

/* =========================================================
   5. STATISTIK ABSENSI KELAS (FILTER MATKUL & PERTEMUAN)
   ========================================================= */
$abs_matkul = $_GET['abs_matkul'] ?? '';
$abs_pertemuan = $_GET['abs_pertemuan'] ?? '';

$abs_where = "WHERE periode_id = '$periode_id' AND kelas_id = '$kelas_id'";
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

$absensi_stats = ['Hadir' => 0, 'Izin' => 0, 'Sakit' => 0, 'Alpha' => 0];
if ($total_absensi > 0) {
    foreach ($absensi_stats_raw as $status => $count) {
        $absensi_stats[$status] = round(($count / $total_absensi) * 100, 1);
    }
}

// Dropdown Opsi Filter Matkul & Pertemuan
$q_abs_matkuls = mysqli_query($koneksi, "
    SELECT DISTINCT a.matkul_id, mk.nama_matkul 
    FROM absensi a 
    JOIN mata_kuliah mk ON a.matkul_id = mk.id 
    WHERE a.periode_id = '$periode_id' AND a.kelas_id = '$kelas_id' 
    ORDER BY mk.nama_matkul
");
$abs_matkuls = [];
while ($r = mysqli_fetch_assoc($q_abs_matkuls)) $abs_matkuls[] = $r;

$q_abs_pertemuans = mysqli_query($koneksi, "
    SELECT DISTINCT pertemuan 
    FROM absensi 
    WHERE periode_id = '$periode_id' AND kelas_id = '$kelas_id' 
    ORDER BY pertemuan ASC
");
$abs_pertemuans = [];
while ($r = mysqli_fetch_assoc($q_abs_pertemuans)) $abs_pertemuans[] = (int)$r['pertemuan'];

/* =========================================================
   6. TREN ARUS KAS & KAS PER MINGGU KELAS
   ========================================================= */
$kas_start = $_GET['kas_start'] ?? date('Y-m-d', strtotime('-30 days'));
$kas_end   = $_GET['kas_end'] ?? date('Y-m-d');

// Saldo Awal sebelum kas_start
$q_saldo_awal_kas = mysqli_query($koneksi, "SELECT COALESCE(SUM(nominal), 0) as total FROM kas WHERE status = 'Lunas' AND periode_id = '$periode_id' AND kelas_id = '$kelas_id' AND (tanggal_bayar < '$kas_start' OR tanggal_bayar IS NULL)");
$q_saldo_awal_in  = mysqli_query($koneksi, "SELECT COALESCE(SUM(jumlah), 0) as total FROM arus_kas WHERE tipe = 'masuk' AND kelas_id = '$kelas_id' AND tanggal < '$kas_start'");
$q_saldo_awal_out = mysqli_query($koneksi, "SELECT COALESCE(SUM(jumlah), 0) as total FROM arus_kas WHERE tipe = 'keluar' AND kelas_id = '$kelas_id' AND tanggal < '$kas_start'");
$temp_saldo = (int)(mysqli_fetch_assoc($q_saldo_awal_kas)['total'] ?? 0) 
            + (int)(mysqli_fetch_assoc($q_saldo_awal_in)['total'] ?? 0) 
            - (int)(mysqli_fetch_assoc($q_saldo_awal_out)['total'] ?? 0);

// Pre-fetch transaksi kas harian agar responsif
$daily_kas_map = [];
$q_daily_kas = mysqli_query($koneksi, "
    SELECT tanggal_bayar, SUM(nominal) as total 
    FROM kas 
    WHERE status = 'Lunas' AND periode_id = '$periode_id' AND kelas_id = '$kelas_id' AND tanggal_bayar BETWEEN '$kas_start' AND '$kas_end'
    GROUP BY tanggal_bayar
");
while ($r = mysqli_fetch_assoc($q_daily_kas)) {
    if ($r['tanggal_bayar']) $daily_kas_map[$r['tanggal_bayar']] = (int)$r['total'];
}

$daily_arus_in_map = [];
$daily_arus_out_map = [];
$q_daily_arus = mysqli_query($koneksi, "
    SELECT tanggal, tipe, SUM(jumlah) as total 
    FROM arus_kas 
    WHERE kelas_id = '$kelas_id' AND tanggal BETWEEN '$kas_start' AND '$kas_end'
    GROUP BY tanggal, tipe
");
while ($r = mysqli_fetch_assoc($q_daily_arus)) {
    if ($r['tipe'] === 'masuk') {
        $daily_arus_in_map[$r['tanggal']] = (int)$r['total'];
    } else {
        $daily_arus_out_map[$r['tanggal']] = (int)$r['total'];
    }
}

$kas_history = [];
$current = strtotime($kas_start);
$last = strtotime($kas_end);
while ($current <= $last) {
    $date = date('Y-m-d', $current);
    $daily_in = ($daily_kas_map[$date] ?? 0) + ($daily_arus_in_map[$date] ?? 0);
    $daily_out = $daily_arus_out_map[$date] ?? 0;
    $temp_saldo += ($daily_in - $daily_out);

    $kas_history[] = [
        'tanggal' => date('d M', $current),
        'masuk'   => $daily_in,
        'keluar'  => $daily_out,
        'saldo'   => $temp_saldo
    ];
    $current = strtotime("+1 day", $current);
}

// Data Kas Per Minggu (Stacked Bar - M1 s/d M16)
$total_mhs = (int)($counts['mahasiswa'] ?? 0);
$q_nom = mysqli_query($koneksi, "SELECT nominal FROM kas WHERE nominal > 0 AND periode_id = '$periode_id' AND kelas_id = '$kelas_id' LIMIT 1");
$fixed_nom = (int)(mysqli_fetch_assoc($q_nom)['nominal'] ?? 5000);
if ($fixed_nom <= 0) $fixed_nom = 5000;

$kas_per_minggu_history = [];
for ($i = 1; $i <= 16; $i++) {
    $kas_per_minggu_history[$i] = [
        'minggu'        => "M-" . $i,
        'lunas_nominal' => 0,
        'belum_nominal' => $total_mhs * $fixed_nom,
        'lunas_cnt'     => 0,
        'persen'        => 0
    ];
}

$q_kas_per_minggu = mysqli_query($koneksi, "
    SELECT minggu_ke, SUM(nominal) as total, COUNT(*) as lunas_count 
    FROM kas 
    WHERE status = 'Lunas' AND periode_id = '$periode_id' AND kelas_id = '$kelas_id' 
    GROUP BY minggu_ke 
    ORDER BY minggu_ke ASC
");
while ($row = mysqli_fetch_assoc($q_kas_per_minggu)) {
    $m_ke = (int)$row['minggu_ke'];
    if (isset($kas_per_minggu_history[$m_ke])) {
        $l_cnt = (int)$row['lunas_count'];
        $l_nom = (int)$row['total'];
        $kas_per_minggu_history[$m_ke]['lunas_nominal'] = $l_nom;
        $kas_per_minggu_history[$m_ke]['lunas_cnt']     = $l_cnt;
        $kas_per_minggu_history[$m_ke]['belum_nominal'] = max(0, ($total_mhs - $l_cnt) * $fixed_nom);
        $kas_per_minggu_history[$m_ke]['persen']        = $total_mhs > 0 ? round(($l_cnt / $total_mhs) * 100) : 0;
    }
}
$kas_per_minggu_history = array_values($kas_per_minggu_history);

$title = "Dashboard - SIMAWA PTIK 2024";
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <title><?= $title ?></title>
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

<body class="bg-slate-100 dark:bg-slate-950 text-slate-800 dark:text-slate-200 font-sans antialiased min-h-screen flex flex-col transition-colors duration-300">
    <!-- Navigasi Shell (Top Bar + Sidebar Mahasiswa) -->
    <?php include 'includes/menu_db.php'; ?>

    <!-- Main Shell (Offset Margin Left ml-0 lg:ml-48, pt-20 untuk topbar) -->
    <main class="ml-0 lg:ml-48 pt-20 p-4 md:p-6 flex flex-col min-h-screen">
        <div class="max-w-6xl mx-auto flex-grow w-full space-y-6">

            <!-- 1. Welcome & Realtime Section -->
            <div class="bg-white dark:bg-slate-900 p-6 rounded-2xl shadow-sm border border-slate-200 dark:border-slate-800 flex flex-col md:flex-row justify-between items-center gap-4 transition-all">
                <div class="flex items-center gap-4">
                    <div class="h-14 w-14 flex-shrink-0 aspect-square rounded-full overflow-hidden border-2 border-indigo-200 dark:border-indigo-900/50">
                        <img src="<?= profilePhotoUrl($foto) ?>" alt="Profile" class="w-full h-full object-cover">
                    </div>
                    <div>
                        <h2 class="font-bold text-lg text-slate-800 dark:text-slate-100">Selamat Datang, <span class="text-indigo-600 dark:text-indigo-400"><?= htmlspecialchars($nama_lengkap) ?></span>!</h2>
                        <p class="text-xs md:text-sm text-slate-600 dark:text-slate-400 flex items-center gap-2 mt-0.5">
                            <i class="fa fa-calendar-check text-green-600 dark:text-green-400"></i>
                            Periode: <span class="font-semibold bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-400 px-2 py-0.5 rounded text-xs"><?= htmlspecialchars($periode_text) ?></span>
                            <span class="text-slate-400">• <?= htmlspecialchars($nama_kelas_aktif) ?></span>
                        </p>
                    </div>
                </div>

                <div class="flex items-center gap-3 mt-2 md:mt-0">
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

            <!-- 2. 4 Quick Metric Cards (Scoped Kelas) -->
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 md:gap-6">
                <a href="database/db_mahasiswa" class="bg-white dark:bg-slate-900 p-4 md:p-5 rounded-2xl shadow-sm border border-slate-200 dark:border-slate-800 card-hover flex items-center gap-4 transition-all">
                    <div class="h-12 w-12 bg-indigo-100 dark:bg-indigo-950/40 rounded-xl flex items-center justify-center text-indigo-700 dark:text-indigo-400 shrink-0">
                        <i class="fa fa-user-graduate text-xl"></i>
                    </div>
                    <div>
                        <div class="text-[10px] font-bold text-slate-400 dark:text-slate-500 uppercase tracking-wider">Mahasiswa</div>
                        <div class="text-xl font-black text-slate-800 dark:text-slate-100"><?= number_format($counts['mahasiswa']) ?></div>
                    </div>
                </a>

                <a href="database/db_tugas" class="bg-white dark:bg-slate-900 p-4 md:p-5 rounded-2xl shadow-sm border border-slate-200 dark:border-slate-800 card-hover flex items-center gap-4 transition-all">
                    <div class="h-12 w-12 bg-indigo-100 dark:bg-indigo-950/40 rounded-xl flex items-center justify-center text-indigo-700 dark:text-indigo-400 shrink-0">
                        <i class="fa fa-tasks text-xl"></i>
                    </div>
                    <div>
                        <div class="text-[10px] font-bold text-slate-400 dark:text-slate-500 uppercase tracking-wider">Tugas Aktif</div>
                        <div class="text-xl font-black text-slate-800 dark:text-slate-100"><?= number_format($counts['tugas']) ?></div>
                    </div>
                </a>

                <a href="views/kelompok" class="bg-white dark:bg-slate-900 p-4 md:p-5 rounded-2xl shadow-sm border border-slate-200 dark:border-slate-800 card-hover flex items-center gap-4 transition-all">
                    <div class="h-12 w-12 bg-indigo-100 dark:bg-indigo-950/40 rounded-xl flex items-center justify-center text-indigo-700 dark:text-indigo-400 shrink-0">
                        <i class="fa fa-users text-xl"></i>
                    </div>
                    <div>
                        <div class="text-[10px] font-bold text-slate-400 dark:text-slate-500 uppercase tracking-wider">Kelompok</div>
                        <div class="text-xl font-black text-slate-800 dark:text-slate-100"><?= number_format($counts['kelompok']) ?></div>
                    </div>
                </a>

                <a href="database/db_jadwal" class="bg-white dark:bg-slate-900 p-4 md:p-5 rounded-2xl shadow-sm border border-slate-200 dark:border-slate-800 card-hover flex items-center gap-4 transition-all">
                    <div class="h-12 w-12 bg-indigo-100 dark:bg-indigo-950/40 rounded-xl flex items-center justify-center text-indigo-700 dark:text-indigo-400 shrink-0">
                        <i class="fa fa-list-alt text-xl"></i>
                    </div>
                    <div>
                        <div class="text-[10px] font-bold text-slate-400 dark:text-slate-500 uppercase tracking-wider">Jadwal Kuliah</div>
                        <div class="text-xl font-black text-slate-800 dark:text-slate-100"><?= number_format($counts['jadwal_matkul']) ?></div>
                    </div>
                </a>
            </div>

            <!-- 3. Info Slider Section (Auto-Rotating 4 Slides) -->
            <div class="relative overflow-hidden bg-white dark:bg-slate-900 rounded-2xl shadow-sm border border-slate-200 dark:border-slate-800 h-28 transition-all">
                <div id="infoSlider" class="flex transition-transform duration-700 ease-in-out h-full">

                    <!-- Slide 1: Jadwal Kuliah Hari Ini & Besok -->
                    <div class="min-w-full p-4 flex items-center gap-6">
                        <div class="h-16 w-16 bg-blue-100 dark:bg-blue-950/40 rounded-2xl flex items-center justify-center text-blue-600 dark:text-blue-400 shrink-0">
                            <i class="fa fa-calendar-alt text-2xl"></i>
                        </div>
                        <div class="flex-grow grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <div class="text-[10px] font-bold text-slate-400 dark:text-slate-500 uppercase tracking-wider mb-1">Kuliah Hari Ini (<?= $today_indo ?>)</div>
                                <div class="text-sm font-bold text-slate-700 dark:text-slate-200">
                                    <?php if (empty($sch_today)): ?>
                                        <span class="text-slate-400 dark:text-slate-500 italic font-normal text-xs">Tidak ada jadwal kuliah hari ini.</span>
                                    <?php else: ?>
                                        <div class="flex flex-col <?= count($sch_today) > 1 ? 'gap-0' : '' ?>">
                                            <?php foreach ($sch_today as $s): ?>
                                                <div class="<?= count($sch_today) > 1 ? 'text-[11px] leading-tight' : 'text-sm' ?> font-bold text-slate-700 dark:text-slate-200 truncate">
                                                    • <?= htmlspecialchars($s['nama_matkul']) ?>
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
                                        <span class="text-slate-400 dark:text-slate-500 italic font-normal text-xs">Tidak ada jadwal.</span>
                                    <?php else: ?>
                                        <div class="flex flex-col <?= count($sch_tomorrow) > 1 ? 'gap-0' : '' ?>">
                                            <?php foreach ($sch_tomorrow as $s): ?>
                                                <div class="<?= count($sch_tomorrow) > 1 ? 'text-[11px] leading-tight' : 'text-sm' ?> font-bold text-slate-700 dark:text-slate-200 truncate">
                                                    • <?= htmlspecialchars($s['nama_matkul']) ?>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Slide 2: Deadline Tugas Terdekat -->
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
                                        <div class="text-sm font-bold text-slate-700 dark:text-slate-200 mb-1 truncate"><?= htmlspecialchars($task['nama_matkul']) ?></div>
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

                    <!-- Slide 3: Distribusi Gender Mahasiswa -->
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
                                            <?= htmlspecialchars($event['status_event']) ?>
                                        </div>
                                        <?php
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

            <!-- Form Filter Global Visualisasi -->
            <form id="vizFilters" method="GET" class="hidden">
                <?php if (isset($_GET['periode_id'])): ?><input type="hidden" name="periode_id" value="<?= $_GET['periode_id'] ?>"><?php endif; ?>
            </form>

            <!-- 4. SECTION 1: MANAJEMEN AKADEMIK -->
            <div>
                <div class="border-b-2 border-blue-100 dark:border-blue-900/30 pb-2 mb-4 transition-all">
                    <h2 class="text-xs font-black text-blue-600 dark:text-blue-400 uppercase tracking-[0.2em] flex items-center gap-3">
                        <span class="h-8 w-8 rounded-xl bg-blue-50 dark:bg-blue-900/20 flex items-center justify-center">
                            <i class="fa fa-graduation-cap"></i>
                        </span>
                        Manajemen Akademik
                    </h2>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <!-- Task Distribution per Subject -->
                    <div class="bg-white dark:bg-slate-900 p-6 rounded-2xl shadow-sm border border-slate-200 dark:border-slate-800 transition-all">
                        <h3 class="text-sm font-bold text-slate-800 dark:text-slate-100 mb-4 flex items-center gap-2">
                            <i class="fa fa-tasks text-indigo-500"></i>
                            Distribusi Tugas per Matkul
                        </h3>
                        <div class="relative h-64">
                            <canvas id="taskStatusChart"></canvas>
                        </div>
                    </div>

                    <!-- Attendance Statistics -->
                    <div class="bg-white dark:bg-slate-900 p-6 rounded-2xl shadow-sm border border-slate-200 dark:border-slate-800 transition-all">
                        <div class="flex flex-col md:flex-row md:justify-between md:items-center gap-3 mb-4">
                            <h3 class="text-sm font-bold text-slate-800 dark:text-slate-100 flex items-center gap-2">
                                <i class="fa fa-user-check text-green-500"></i>
                                Statistik Absensi Kelas
                            </h3>
                            <div class="flex items-center gap-2">
                                <select name="abs_matkul" form="vizFilters" onchange="this.form.submit()" class="text-[10px] bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-700 dark:text-slate-200 rounded-lg p-1.5 focus:ring-indigo-500">
                                    <option value="">Semua Matkul</option>
                                    <?php foreach ($abs_matkuls as $m): ?>
                                        <option value="<?= $m['matkul_id'] ?>" <?= $abs_matkul == $m['matkul_id'] ? 'selected' : '' ?>><?= htmlspecialchars($m['nama_matkul']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <select name="abs_pertemuan" form="vizFilters" onchange="this.form.submit()" class="text-[10px] bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-700 dark:text-slate-200 rounded-lg p-1.5 focus:ring-indigo-500">
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
            </div>

            <!-- 5. SECTION 2: ANALITIK KEUANGAN KAS -->
            <div>
                <div class="border-b-2 border-emerald-100 dark:border-emerald-900/30 pb-2 mb-4 transition-all">
                    <h2 class="text-xs font-black text-emerald-600 dark:text-emerald-400 uppercase tracking-[0.2em] flex items-center gap-3">
                        <span class="h-8 w-8 rounded-xl bg-emerald-50 dark:bg-emerald-900/20 flex items-center justify-center">
                            <i class="fa fa-piggy-bank"></i>
                        </span>
                        Transparansi & Analitik Keuangan
                    </h2>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <!-- Financial Trend Line Chart -->
                    <div class="bg-white dark:bg-slate-900 p-6 rounded-2xl shadow-sm border border-slate-200 dark:border-slate-800 transition-all">
                        <div class="flex flex-col md:flex-row md:justify-between md:items-center gap-3 mb-4">
                            <div class="min-w-0">
                                <h3 class="text-sm font-bold text-slate-800 dark:text-slate-100 flex items-center gap-2">
                                    <i class="fa fa-line-chart text-green-500"></i>
                                    Tren Arus Kas Kelas
                                </h3>
                            </div>
                            <div class="flex items-center justify-between sm:justify-start gap-1 bg-slate-50 dark:bg-slate-800 p-1.5 rounded-lg border border-slate-200 dark:border-slate-700 w-full sm:w-auto">
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
                    <div class="bg-white dark:bg-slate-900 p-6 rounded-2xl shadow-sm border border-slate-200 dark:border-slate-800 transition-all">
                        <div class="flex flex-col md:flex-row md:justify-between md:items-center gap-3 mb-4">
                            <h3 class="text-sm font-bold text-slate-800 dark:text-slate-100 flex items-center gap-2">
                                <i class="fa fa-chart-bar text-green-500"></i>
                                Pemasukan Kas per Minggu
                            </h3>
                            <div class="flex items-center gap-1 bg-slate-50 dark:bg-slate-800 p-1 rounded-lg border border-slate-200 dark:border-slate-700">
                                <span class="text-[10px] font-bold text-slate-500 dark:text-slate-400 px-2 uppercase tracking-tight">Data 16 Minggu</span>
                            </div>
                        </div>
                        <div class="relative h-72">
                            <canvas id="kasMingguanChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>

        </div>

        <!-- Footer -->
        <?php include 'includes/footer_db.php'; ?>
    </main>

    <!-- Scripts -->
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
            if (!slider) return;
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

            const clockEl = document.getElementById('realtime-clock');
            const dateEl = document.getElementById('realtime-date');
            if (clockEl) clockEl.textContent = timeString.replaceAll('.', ':');
            if (dateEl) dateEl.textContent = dateString;
        }
        setInterval(updateClock, 1000);
        updateClock();

        // Chart.js Global Dark Mode Config
        const isDarkMode = document.documentElement.classList.contains('dark');
        Chart.defaults.color = isDarkMode ? '#94a3b8' : '#64748b';
        Chart.defaults.borderColor = isDarkMode ? 'rgba(148, 163, 184, 0.1)' : '#f1f5f9';
        Chart.defaults.font.family = "'Poppins', sans-serif";

        // Task Status per Matkul Chart
        const taskCtx = document.getElementById('taskStatusChart');
        if (taskCtx) {
            new Chart(taskCtx.getContext('2d'), {
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
        }

        // Attendance Chart
        const attendCtx = document.getElementById('attendanceChart');
        if (attendCtx) {
            new Chart(attendCtx.getContext('2d'), {
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
        }

        // Finance Chart
        const financeCtx = document.getElementById('financeChart');
        if (financeCtx) {
            const kasHistory = <?= json_encode($kas_history) ?>;
            new Chart(financeCtx.getContext('2d'), {
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
        }

        // Weekly Cash Chart
        const kasCtx = document.getElementById('kasMingguanChart');
        if (kasCtx) {
            const kasMingguanHistory = <?= json_encode($kas_per_minggu_history) ?>;
            new Chart(kasCtx.getContext('2d'), {
                type: 'bar',
                data: {
                    labels: kasMingguanHistory.map(h => h.minggu),
                    datasets: [{
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
                                font: {
                                    size: 9
                                }
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
                                font: {
                                    size: 9
                                },
                                callback: function(value) {
                                    if (value >= 1000000) return 'Rp ' + (value / 1000000) + 'jt';
                                    if (value >= 1000) return 'Rp ' + (value / 1000) + 'k';
                                    return 'Rp ' + value;
                                }
                            }
                        },
                        x: {
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
        }
    </script>
</body>

</html>
