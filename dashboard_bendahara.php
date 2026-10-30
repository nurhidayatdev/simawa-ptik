<?php
session_start();
include 'koneksi.php';
require_once 'functions/helpers.php';
require_once 'functions/foto_helpers.php';

// Proteksi Akses: Hanya Bendahara dan Administrator Utama
if (!isset($_SESSION['user_id'])) {
    header("Location: index");
    exit();
}

$user_id      = $_SESSION['user_id'];
$nama_lengkap = $_SESSION['nama_lengkap'] ?? 'Bendahara Kelas';
$foto         = $_SESSION['foto'] ?? '';
$role         = $_SESSION['role'];

if ($role !== 'Bendahara' && $role !== 'Administrator Utama') {
    include 'includes/alert_role.php';
    exit();
}

$kelas_id = getKelasScope();
if (!$kelas_id && $role !== 'Administrator Utama') {
    $_SESSION['error'] = "Akun Bendahara Anda belum terikat ke kelas manapun.";
    header("Location: index");
    exit();
}

// Ambil info metadata kelas aktif
$nama_kelas_aktif = getNamaKelasAktif();
$q_kelas_info = mysqli_query($koneksi, "SELECT * FROM kelas WHERE id = '$kelas_id' LIMIT 1");
$kelas_info = mysqli_fetch_assoc($q_kelas_info);
$prodi    = $kelas_info['prodi'] ?? 'Pendidikan Teknik Informatika dan Komputer';
$angkatan = $kelas_info['angkatan'] ?? '2024';

// Ambil periode aktif
$q_periode = mysqli_query($koneksi, "SELECT id, tahun_periode, semester_nama FROM periode WHERE status = 'aktif' LIMIT 1");
$periode = mysqli_fetch_assoc($q_periode);
$periode_id = $periode['id'] ?? 1;
$periode_nama = ($periode['tahun_periode'] ?? '2024/2025') . ' - ' . ($periode['semester_nama'] ?? 'Ganjil');

// Flash Messages
$success_msg = $_SESSION['success'] ?? '';
$error_msg   = $_SESSION['error'] ?? '';
unset($_SESSION['success'], $_SESSION['error']);

/* =========================================================
   1. STATISTIK RINGKASAN KEUANGAN KAS KELAS
   ========================================================= */

// Total Mahasiswa Kelas (Excluding Admin)
$q_total_mhs = mysqli_query($koneksi, "
    SELECT COUNT(*) as total 
    FROM mahasiswa m
    LEFT JOIN users u ON m.user_id = u.id
    WHERE m.kelas_id = '$kelas_id' AND (u.role IS NULL OR u.role != 'Administrator Utama')
");
$total_mhs = (int)(mysqli_fetch_assoc($q_total_mhs)['total'] ?? 0);

// Nominal Iuran per Minggu (Default: 5000)
$q_nom = mysqli_query($koneksi, "SELECT nominal FROM kas WHERE nominal > 0 AND periode_id = '$periode_id' AND kelas_id = '$kelas_id' LIMIT 1");
$fixed_nom = (int)(mysqli_fetch_assoc($q_nom)['nominal'] ?? 5000);
if ($fixed_nom <= 0) $fixed_nom = 5000;

// Kas Mahasiswa Lunas
$q_kas_lunas = mysqli_query($koneksi, "
    SELECT 
        COALESCE(SUM(nominal), 0) as total, 
        COUNT(*) as count_lunas 
    FROM kas 
    WHERE status = 'Lunas' AND periode_id = '$periode_id' AND kelas_id = '$kelas_id'
");
$d_kas_lunas = mysqli_fetch_assoc($q_kas_lunas);
$total_kas_lunas = (int)($d_kas_lunas['total'] ?? 0);
$count_lunas_entries = (int)($d_kas_lunas['count_lunas'] ?? 0);

// Arus Kas Masuk (Pemasukan lain-lain)
$q_arus_in = mysqli_query($koneksi, "
    SELECT COALESCE(SUM(jumlah), 0) as total 
    FROM arus_kas 
    WHERE tipe = 'masuk' AND kelas_id = '$kelas_id'
");
$total_arus_masuk = (int)(mysqli_fetch_assoc($q_arus_in)['total'] ?? 0);
$total_pemasukan = $total_kas_lunas + $total_arus_masuk;

// Arus Kas Keluar (Pengeluaran operasional)
$q_arus_out = mysqli_query($koneksi, "
    SELECT COALESCE(SUM(jumlah), 0) as total 
    FROM arus_kas 
    WHERE tipe = 'keluar' AND kelas_id = '$kelas_id'
");
$total_pengeluaran = (int)(mysqli_fetch_assoc($q_arus_out)['total'] ?? 0);

// Saldo Kas Saat Ini
$saldo_saat_ini = $total_pemasukan - $total_pengeluaran;

// Persentase Kelancaran Iuran Mahasiswa (16 Minggu)
$target_entries = $total_mhs * 16;
$persentase_kelancaran = ($target_entries > 0) ? round(($count_lunas_entries / $target_entries) * 100, 1) : 0;

/* =========================================================
   2. DATA CHART 1: TREN ARUS KAS & SALDO (30 HARI TERAKHIR)
   ========================================================= */
$kas_start = date('Y-m-d', strtotime('-29 days'));
$kas_end   = date('Y-m-d');

// Saldo Awal sebelum kas_start
$q_saldo_awal_kas = mysqli_query($koneksi, "
    SELECT COALESCE(SUM(nominal), 0) as total 
    FROM kas 
    WHERE status = 'Lunas' AND periode_id = '$periode_id' AND kelas_id = '$kelas_id' AND (tanggal_bayar < '$kas_start' OR tanggal_bayar IS NULL)
");
$q_saldo_awal_in = mysqli_query($koneksi, "
    SELECT COALESCE(SUM(jumlah), 0) as total 
    FROM arus_kas 
    WHERE tipe = 'masuk' AND kelas_id = '$kelas_id' AND tanggal < '$kas_start'
");
$q_saldo_awal_out = mysqli_query($koneksi, "
    SELECT COALESCE(SUM(jumlah), 0) as total 
    FROM arus_kas 
    WHERE tipe = 'keluar' AND kelas_id = '$kelas_id' AND tanggal < '$kas_start'
");
$temp_saldo = (int)(mysqli_fetch_assoc($q_saldo_awal_kas)['total'] ?? 0) 
            + (int)(mysqli_fetch_assoc($q_saldo_awal_in)['total'] ?? 0) 
            - (int)(mysqli_fetch_assoc($q_saldo_awal_out)['total'] ?? 0);

// Pre-fetch transaksi harian agar loop 30 hari cepat tanpa n+1 query
$daily_kas_map = [];
$q_daily_kas = mysqli_query($koneksi, "
    SELECT tanggal_bayar, SUM(nominal) as total 
    FROM kas 
    WHERE status = 'Lunas' AND periode_id = '$periode_id' AND kelas_id = '$kelas_id' AND tanggal_bayar BETWEEN '$kas_start' AND '$kas_end'
    GROUP BY tanggal_bayar
");
while ($r = mysqli_fetch_assoc($q_daily_kas)) {
    if ($r['tanggal_bayar']) {
        $daily_kas_map[$r['tanggal_bayar']] = (int)$r['total'];
    }
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
$cur = strtotime($kas_start);
$end_time = strtotime($kas_end);
while ($cur <= $end_time) {
    $d_str = date('Y-m-d', $cur);
    $in_day  = ($daily_kas_map[$d_str] ?? 0) + ($daily_arus_in_map[$d_str] ?? 0);
    $out_day = $daily_arus_out_map[$d_str] ?? 0;
    $temp_saldo += ($in_day - $out_day);

    $kas_history[] = [
        'tanggal' => date('d M', $cur),
        'masuk'   => $in_day,
        'keluar'  => $out_day,
        'saldo'   => $temp_saldo
    ];
    $cur = strtotime('+1 day', $cur);
}

/* =========================================================
   3. DATA CHART 2: PROGRES KAS PER MINGGU (M1 - M16)
   ========================================================= */
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

/* =========================================================
   4. DATA TABEL 1: 7 MUTASI ARUS KAS TERAKHIR
   ========================================================= */
$q_recent_arus = mysqli_query($koneksi, "
    SELECT id, tipe, deskripsi, jumlah, tanggal 
    FROM arus_kas 
    WHERE kelas_id = '$kelas_id' 
    ORDER BY tanggal DESC, id DESC 
    LIMIT 7
");
$recent_arus = [];
while ($row = mysqli_fetch_assoc($q_recent_arus)) {
    $recent_arus[] = $row;
}

/* =========================================================
   5. DATA TABEL 2: PRIORITAS PENAGIHAN (TOP MAHASISWA TUNGGAKAN)
   ========================================================= */
$q_tunggakan = mysqli_query($koneksi, "
    SELECT 
        m.id, 
        m.nim, 
        m.nama_lengkap, 
        m.foto,
        COALESCE(sub.lunas_cnt, 0) as lunas_cnt,
        (16 - COALESCE(sub.lunas_cnt, 0)) as tunggakan_minggu,
        ((16 - COALESCE(sub.lunas_cnt, 0)) * $fixed_nom) as estimasi_tunggakan
    FROM mahasiswa m
    LEFT JOIN users u ON m.user_id = u.id
    LEFT JOIN (
        SELECT mahasiswa_id, COUNT(*) as lunas_cnt 
        FROM kas 
        WHERE status = 'Lunas' AND periode_id = '$periode_id' AND kelas_id = '$kelas_id'
        GROUP BY mahasiswa_id
    ) sub ON m.id = sub.mahasiswa_id
    WHERE m.kelas_id = '$kelas_id' 
      AND (u.role IS NULL OR u.role != 'Administrator Utama')
      AND (COALESCE(sub.lunas_cnt, 0) < 16)
    ORDER BY tunggakan_minggu DESC, m.nama_lengkap ASC
    LIMIT 7
");
$top_tunggakan = [];
while ($row = mysqli_fetch_assoc($q_tunggakan)) {
    $top_tunggakan[] = $row;
}

$title = "Dashboard Bendahara - SIMAWA PTIK 2024";
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <title><?= $title ?></title>
    <?php include 'includes/head.php'; ?>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>

<body class="bg-slate-100 dark:bg-slate-950 text-slate-800 dark:text-slate-200 font-sans antialiased min-h-screen flex flex-col transition-colors duration-300">

    <!-- Navigasi Shell (Top Bar + Sidebar Bendahara) -->
    <?php include 'includes/menu_db.php'; ?>

    <!-- Main Shell (Offset Margin Left ml-0 lg:ml-48, pt-20 untuk topbar) -->
    <main class="ml-0 lg:ml-48 pt-20 p-4 md:p-6 flex flex-col min-h-screen">
        <div class="max-w-7xl mx-auto w-full space-y-6 flex-grow">

            <!-- 1. Header & Action Bar -->
            <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 p-6 shadow-sm flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                <div>
                    <div class="flex items-center gap-2">
                        <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-[10px] font-extrabold uppercase tracking-widest bg-emerald-50 dark:bg-emerald-950/40 text-emerald-700 dark:text-emerald-400 border border-emerald-200/60 dark:border-emerald-900/50">
                            <i class="fas fa-coins text-[9px]"></i> Bendahara Kelas
                        </span>
                        <span class="text-xs font-semibold text-slate-400 dark:text-slate-500">• <?= htmlspecialchars($nama_kelas_aktif) ?> (<?= htmlspecialchars($periode_nama) ?>)</span>
                    </div>
                    <h1 class="text-2xl md:text-3xl font-extrabold text-slate-900 dark:text-white mt-1.5 tracking-tight">Dashboard Bendahara</h1>
                    <p class="text-xs md:text-sm text-slate-500 dark:text-slate-400 mt-1">Pusat transparansi saldo kas, pencatatan arus masuk/keluar, dan penagihan iuran kelas.</p>
                </div>
                <div class="flex flex-wrap items-center gap-2.5">
                    <a href="actions/tambah_kas_minggu.php" class="inline-flex items-center gap-2 px-3.5 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold rounded-xl shadow-lg shadow-emerald-600/20 active:scale-95 transition-all">
                        <i class="fas fa-plus-circle"></i> Input Per Minggu
                    </a>
                    <a href="actions/tambah_kas_individu.php" class="inline-flex items-center gap-2 px-3.5 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-bold rounded-xl shadow-lg shadow-indigo-600/20 active:scale-95 transition-all">
                        <i class="fas fa-user-plus"></i> Input Per Individu
                    </a>
                    <a href="views/manajemen_kas.php" class="inline-flex items-center gap-2 px-3.5 py-2.5 bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 text-xs font-bold rounded-xl border border-slate-200 dark:border-slate-700 transition-all">
                        <i class="fas fa-wallet"></i> Arus Kas
                    </a>
                    <a href="views/rekap_kas.php" class="inline-flex items-center gap-2 px-3.5 py-2.5 bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 text-xs font-bold rounded-xl border border-slate-200 dark:border-slate-700 transition-all">
                        <i class="fas fa-chart-pie"></i> Rekap Matriks
                    </a>
                </div>
            </div>

            <!-- 2. Metric Cards Keuangan Kas (Grid 4 Kolom) -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 md:gap-5">
                <!-- Card 1: Saldo Kas Saat Ini -->
                <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 p-5 shadow-sm hover:shadow-md transition-all group">
                    <div class="flex items-center justify-between">
                        <div>
                            <span class="text-[10px] font-bold uppercase tracking-widest text-slate-400 dark:text-slate-500">Saldo Kas Saat Ini</span>
                            <div class="text-xl md:text-2xl font-black text-slate-900 dark:text-white mt-1.5 <?= $saldo_saat_ini >= 0 ? 'text-emerald-600 dark:text-emerald-400' : 'text-rose-600 dark:text-rose-400' ?>">
                                Rp <?= number_format($saldo_saat_ini, 0, ',', '.') ?>
                            </div>
                            <div class="text-[11px] font-semibold text-slate-500 dark:text-slate-400 mt-1 flex items-center gap-1">
                                <i class="fas fa-vault text-[10px] text-emerald-500"></i> Dana Siap Pakai
                            </div>
                        </div>
                        <div class="w-12 h-12 rounded-2xl bg-emerald-50 dark:bg-emerald-950/40 text-emerald-600 dark:text-emerald-400 border border-emerald-100 dark:border-emerald-900/50 flex items-center justify-center text-xl group-hover:scale-110 transition-transform">
                            <i class="fas fa-wallet"></i>
                        </div>
                    </div>
                </div>

                <!-- Card 2: Total Pemasukan -->
                <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 p-5 shadow-sm hover:shadow-md transition-all group">
                    <div class="flex items-center justify-between">
                        <div>
                            <span class="text-[10px] font-bold uppercase tracking-widest text-slate-400 dark:text-slate-500">Total Pemasukan</span>
                            <div class="text-xl md:text-2xl font-black text-emerald-600 dark:text-emerald-400 mt-1.5">
                                Rp <?= number_format($total_pemasukan, 0, ',', '.') ?>
                            </div>
                            <div class="text-[11px] font-semibold text-slate-500 dark:text-slate-400 mt-1 flex items-center gap-1">
                                <i class="fas fa-arrow-down text-[10px] text-emerald-500"></i> Kas: Rp <?= number_format($total_kas_lunas, 0, ',', '.') ?>
                            </div>
                        </div>
                        <div class="w-12 h-12 rounded-2xl bg-indigo-50 dark:bg-indigo-950/40 text-indigo-600 dark:text-indigo-400 border border-indigo-100 dark:border-indigo-900/50 flex items-center justify-center text-xl group-hover:scale-110 transition-transform">
                            <i class="fas fa-hand-holding-dollar"></i>
                        </div>
                    </div>
                </div>

                <!-- Card 3: Total Pengeluaran -->
                <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 p-5 shadow-sm hover:shadow-md transition-all group">
                    <div class="flex items-center justify-between">
                        <div>
                            <span class="text-[10px] font-bold uppercase tracking-widest text-slate-400 dark:text-slate-500">Total Pengeluaran</span>
                            <div class="text-xl md:text-2xl font-black text-rose-600 dark:text-rose-400 mt-1.5">
                                Rp <?= number_format($total_pengeluaran, 0, ',', '.') ?>
                            </div>
                            <div class="text-[11px] font-semibold text-slate-500 dark:text-slate-400 mt-1 flex items-center gap-1">
                                <i class="fas fa-arrow-up text-[10px] text-rose-500"></i> Belanja & Operasional
                            </div>
                        </div>
                        <div class="w-12 h-12 rounded-2xl bg-rose-50 dark:bg-rose-950/40 text-rose-600 dark:text-rose-400 border border-rose-100 dark:border-rose-900/50 flex items-center justify-center text-xl group-hover:scale-110 transition-transform">
                            <i class="fas fa-receipt"></i>
                        </div>
                    </div>
                </div>

                <!-- Card 4: Kelancaran Iuran Kelas -->
                <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 p-5 shadow-sm hover:shadow-md transition-all group">
                    <div class="flex items-center justify-between">
                        <div>
                            <span class="text-[10px] font-bold uppercase tracking-widest text-slate-400 dark:text-slate-500">Kelancaran Iuran</span>
                            <div class="text-xl md:text-2xl font-black text-indigo-600 dark:text-indigo-400 mt-1.5">
                                <?= $persentase_kelancaran ?>%
                            </div>
                            <div class="text-[11px] font-semibold text-slate-500 dark:text-slate-400 mt-1 flex items-center gap-1">
                                <i class="fas fa-check-double text-[10px] text-indigo-500"></i> <?= $count_lunas_entries ?> / <?= $target_entries ?> Setoran
                            </div>
                        </div>
                        <div class="w-12 h-12 rounded-2xl bg-amber-50 dark:bg-amber-950/40 text-amber-600 dark:text-amber-400 border border-amber-100 dark:border-amber-900/50 flex items-center justify-center text-xl group-hover:scale-110 transition-transform">
                            <i class="fas fa-piggy-bank"></i>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 3. Visualisasi Tren Kas Chart.js (Grid 2 Kolom) -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <!-- Chart 1: Tren Arus Kas & Saldo Kumulatif 30 Hari -->
                <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 p-6 shadow-sm flex flex-col justify-between">
                    <div>
                        <div class="flex items-center justify-between mb-4">
                            <div>
                                <h2 class="text-base font-extrabold text-slate-900 dark:text-white flex items-center gap-2">
                                    <i class="fas fa-chart-line text-indigo-600 dark:text-indigo-400"></i>
                                    Tren Arus Kas (30 Hari)
                                </h2>
                                <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">Pertumbuhan saldo kumulatif dan transaksi harian.</p>
                            </div>
                            <span class="px-2.5 py-1 text-[10px] font-bold uppercase rounded-lg bg-indigo-50 dark:bg-indigo-950/40 text-indigo-600 dark:text-indigo-400 border border-indigo-100 dark:border-indigo-900/50">
                                Harian
                            </span>
                        </div>
                        <div class="relative h-64 w-full">
                            <canvas id="arusKasChart"></canvas>
                        </div>
                    </div>
                    <div class="mt-4 pt-3 border-t border-slate-100 dark:border-slate-800 flex items-center justify-between text-[11px] text-slate-500 dark:text-slate-400">
                        <span><strong class="text-indigo-600 dark:text-indigo-400">●</strong> Saldo Kas</span>
                        <span><strong class="text-emerald-500">--</strong> Pemasukan</span>
                        <span><strong class="text-rose-500">--</strong> Pengeluaran</span>
                    </div>
                </div>

                <!-- Chart 2: Progres Setoran Kas Per Minggu M1-M16 -->
                <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 p-6 shadow-sm flex flex-col justify-between">
                    <div>
                        <div class="flex items-center justify-between mb-4">
                            <div>
                                <h2 class="text-base font-extrabold text-slate-900 dark:text-white flex items-center gap-2">
                                    <i class="fas fa-layer-group text-emerald-600 dark:text-emerald-400"></i>
                                    Progres Kas Per Minggu (M1 - M16)
                                </h2>
                                <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">Perbandingan nominal lunas vs sisa tunggakan per minggu.</p>
                            </div>
                            <span class="px-2.5 py-1 text-[10px] font-bold uppercase rounded-lg bg-emerald-50 dark:bg-emerald-950/40 text-emerald-600 dark:text-emerald-400 border border-emerald-100 dark:border-emerald-900/50">
                                16 Minggu
                            </span>
                        </div>
                        <div class="relative h-64 w-full">
                            <canvas id="kasMingguanChart"></canvas>
                        </div>
                    </div>
                    <div class="mt-4 pt-3 border-t border-slate-100 dark:border-slate-800 flex items-center justify-between text-[11px] text-slate-500 dark:text-slate-400">
                        <span><strong class="text-emerald-500">■</strong> Lunas</span>
                        <span><strong class="text-slate-400 dark:text-slate-600">■</strong> Belum Lunas</span>
                        <span>Tarif: Rp <?= number_format($fixed_nom, 0, ',', '.') ?>/mhs/mgg</span>
                    </div>
                </div>
            </div>

            <!-- 4. Tabel Monitoring Keuangan (Grid 2 Kolom) -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

                <!-- Widget Kiri: Mutasi Arus Kas Terakhir -->
                <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 p-6 shadow-sm flex flex-col justify-between">
                    <div>
                        <div class="flex items-center justify-between mb-4">
                            <div>
                                <h2 class="text-base font-extrabold text-slate-900 dark:text-white flex items-center gap-2">
                                    <i class="fas fa-history text-indigo-600 dark:text-indigo-400"></i>
                                    Mutasi Arus Kas Terakhir
                                </h2>
                                <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">Catatan belanja dan pemasukan lain kelas.</p>
                            </div>
                            <a href="views/manajemen_kas.php" class="text-xs font-bold text-indigo-600 dark:text-indigo-400 hover:underline">
                                Semua &rarr;
                            </a>
                        </div>

                        <?php if (empty($recent_arus)): ?>
                            <div class="py-12 text-center text-slate-400 dark:text-slate-500">
                                <i class="fas fa-receipt text-3xl mb-2 opacity-50"></i>
                                <p class="text-xs">Belum ada mutasi arus kas tercatat.</p>
                                <a href="views/manajemen_kas.php" class="inline-block mt-3 px-3 py-1.5 bg-indigo-50 dark:bg-indigo-950/40 text-indigo-600 dark:text-indigo-400 text-xs font-bold rounded-lg hover:underline">
                                    Catat Transaksi Pertama
                                </a>
                            </div>
                        <?php else: ?>
                            <div class="overflow-x-auto">
                                <table class="w-full text-left text-xs">
                                    <thead>
                                        <tr class="border-b border-slate-100 dark:border-slate-800 text-[10px] font-bold uppercase tracking-wider text-slate-400 dark:text-slate-500">
                                            <th class="pb-2.5">Tanggal</th>
                                            <th class="pb-2.5">Deskripsi</th>
                                            <th class="pb-2.5 text-center">Tipe</th>
                                            <th class="pb-2.5 text-right">Jumlah</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                                        <?php foreach ($recent_arus as $ra): ?>
                                            <tr class="hover:bg-slate-50 dark:hover:bg-slate-800/40 transition-colors">
                                                <td class="py-2.5 text-slate-600 dark:text-slate-400 font-medium">
                                                    <?= date('d/m/Y', strtotime($ra['tanggal'])) ?>
                                                </td>
                                                <td class="py-2.5 font-semibold text-slate-800 dark:text-slate-200">
                                                    <?= htmlspecialchars($ra['deskripsi']) ?>
                                                </td>
                                                <td class="py-2.5 text-center">
                                                    <?php if ($ra['tipe'] === 'masuk'): ?>
                                                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-50 dark:bg-emerald-950/40 text-emerald-600 dark:text-emerald-400 border border-emerald-200 dark:border-emerald-800">
                                                            <i class="fas fa-plus text-[8px]"></i> Masuk
                                                        </span>
                                                    <?php else: ?>
                                                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold bg-rose-50 dark:bg-rose-950/40 text-rose-600 dark:text-rose-400 border border-rose-200 dark:border-rose-800">
                                                            <i class="fas fa-minus text-[8px]"></i> Keluar
                                                        </span>
                                                    <?php endif; ?>
                                                </td>
                                                <td class="py-2.5 text-right font-bold <?= $ra['tipe'] === 'masuk' ? 'text-emerald-600 dark:text-emerald-400' : 'text-rose-600 dark:text-rose-400' ?>">
                                                    <?= $ra['tipe'] === 'masuk' ? '+' : '-' ?>Rp <?= number_format($ra['jumlah'], 0, ',', '.') ?>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="mt-4 pt-3 border-t border-slate-100 dark:border-slate-800 text-right">
                        <a href="views/manajemen_kas.php" class="text-xs font-semibold text-indigo-600 dark:text-indigo-400 hover:underline">
                            Kelola & Export Arus Kas (Excel) &rarr;
                        </a>
                    </div>
                </div>

                <!-- Widget Kanan: Prioritas Penagihan (Top Mahasiswa Belum Lunas) -->
                <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 p-6 shadow-sm flex flex-col justify-between">
                    <div>
                        <div class="flex items-center justify-between mb-4">
                            <div>
                                <h2 class="text-base font-extrabold text-slate-900 dark:text-white flex items-center gap-2">
                                    <i class="fas fa-bell text-amber-500"></i>
                                    Prioritas Penagihan Iuran
                                </h2>
                                <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">Mahasiswa dengan minggu belum lunas terbanyak.</p>
                            </div>
                            <a href="views/rekap_kas.php" class="text-xs font-bold text-indigo-600 dark:text-indigo-400 hover:underline">
                                Matriks Lengkap &rarr;
                            </a>
                        </div>

                        <?php if (empty($top_tunggakan)): ?>
                            <div class="py-12 text-center text-slate-400 dark:text-slate-500">
                                <i class="fas fa-check-circle text-3xl mb-2 text-emerald-500"></i>
                                <p class="text-xs font-semibold text-emerald-600 dark:text-emerald-400">Luar biasa! Seluruh mahasiswa telah melunasi kas semester ini.</p>
                            </div>
                        <?php else: ?>
                            <div class="overflow-x-auto">
                                <table class="w-full text-left text-xs">
                                    <thead>
                                        <tr class="border-b border-slate-100 dark:border-slate-800 text-[10px] font-bold uppercase tracking-wider text-slate-400 dark:text-slate-500">
                                            <th class="pb-2.5">Mahasiswa</th>
                                            <th class="pb-2.5 text-center">Status</th>
                                            <th class="pb-2.5 text-right">Tunggakan</th>
                                            <th class="pb-2.5 text-center">Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                                        <?php foreach ($top_tunggakan as $mhs): ?>
                                            <tr class="hover:bg-slate-50 dark:hover:bg-slate-800/40 transition-colors">
                                                <td class="py-2.5">
                                                    <div class="flex items-center gap-2.5">
                                                        <img src="<?= profilePhotoUrl($mhs['foto']) ?>" alt="Foto" class="w-7 h-7 rounded-full object-cover border border-slate-200 dark:border-slate-700">
                                                        <div>
                                                            <div class="font-bold text-slate-800 dark:text-slate-200"><?= htmlspecialchars($mhs['nama_lengkap']) ?></div>
                                                            <div class="text-[10px] text-slate-400 font-mono"><?= htmlspecialchars($mhs['nim']) ?></div>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td class="py-2.5 text-center">
                                                    <span class="inline-block px-2 py-0.5 rounded-full text-[10px] font-bold bg-amber-50 dark:bg-amber-950/40 text-amber-700 dark:text-amber-400 border border-amber-200 dark:border-amber-900/50">
                                                        <?= $mhs['lunas_cnt'] ?>/16 Lunas
                                                    </span>
                                                </td>
                                                <td class="py-2.5 text-right">
                                                    <div class="font-black text-rose-600 dark:text-rose-400">
                                                        Rp <?= number_format($mhs['estimasi_tunggakan'], 0, ',', '.') ?>
                                                    </div>
                                                    <div class="text-[10px] text-slate-400">
                                                        <?= $mhs['tunggakan_minggu'] ?> mgg tersisa
                                                    </div>
                                                </td>
                                                <td class="py-2.5 text-center">
                                                    <a href="actions/tambah_kas_individu.php?mahasiswa_id=<?= $mhs['id'] ?>" class="inline-flex items-center justify-center w-7 h-7 rounded-lg bg-emerald-50 hover:bg-emerald-100 dark:bg-emerald-950/40 dark:hover:bg-emerald-900/60 text-emerald-600 dark:text-emerald-400 transition-colors" title="Catat Setoran">
                                                        <i class="fas fa-hand-holding-dollar text-xs"></i>
                                                    </a>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="mt-4 pt-3 border-t border-slate-100 dark:border-slate-800 text-right">
                        <a href="views/rekap_kas.php" class="text-xs font-semibold text-indigo-600 dark:text-indigo-400 hover:underline">
                            Lihat Matriks 16 Minggu & Export Excel &rarr;
                        </a>
                    </div>
                </div>

            </div>

        </div>

        <!-- Footer -->
        <?php include 'includes/footer_db.php'; ?>
    </main>

    <!-- Scripts -->
    <?php include 'includes/script_db.php'; ?>

    <!-- Inisialisasi Chart.js Keuangan Kas -->
    <script>
        const isDarkMode = document.documentElement.classList.contains('dark');
        const gridColor = isDarkMode ? 'rgba(148, 163, 184, 0.1)' : 'rgba(226, 232, 240, 0.6)';
        const textColor = isDarkMode ? '#94a3b8' : '#64748b';

        // 1. Line Chart: Arus Kas & Saldo Kumulatif
        const arusCtx = document.getElementById('arusKasChart');
        if (arusCtx) {
            const kasHistory = <?= json_encode($kas_history) ?>;
            new Chart(arusCtx.getContext('2d'), {
                type: 'line',
                data: {
                    labels: kasHistory.map(h => h.tanggal),
                    datasets: [
                        {
                            label: 'Saldo Kas',
                            data: kasHistory.map(h => h.saldo),
                            borderColor: '#4f46e5',
                            backgroundColor: 'rgba(79, 70, 229, 0.08)',
                            fill: true,
                            tension: 0.35,
                            pointRadius: 2,
                            pointHoverRadius: 5,
                            borderWidth: 2.5
                        },
                        {
                            label: 'Pemasukan',
                            data: kasHistory.map(h => h.masuk),
                            borderColor: '#10b981',
                            borderDash: [4, 4],
                            borderWidth: 1.5,
                            pointRadius: 0,
                            hidden: false
                        },
                        {
                            label: 'Pengeluaran',
                            data: kasHistory.map(h => h.keluar),
                            borderColor: '#ef4444',
                            borderDash: [4, 4],
                            borderWidth: 1.5,
                            pointRadius: 0,
                            hidden: false
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    interaction: {
                        mode: 'index',
                        intersect: false
                    },
                    plugins: {
                        legend: {
                            display: false
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    return context.dataset.label + ': Rp ' + context.parsed.y.toLocaleString('id-ID');
                                }
                            }
                        }
                    },
                    scales: {
                        y: {
                            grid: { color: gridColor },
                            ticks: {
                                color: textColor,
                                font: { size: 10 },
                                callback: function(val) {
                                    if (val >= 1000000) return 'Rp ' + (val / 1000000).toFixed(1) + 'jt';
                                    if (val >= 1000) return 'Rp ' + (val / 1000).toFixed(0) + 'k';
                                    return 'Rp ' + val;
                                }
                            }
                        },
                        x: {
                            grid: { display: false },
                            ticks: {
                                color: textColor,
                                font: { size: 9 },
                                maxTicksLimit: 8
                            }
                        }
                    }
                }
            });
        }

        // 2. Stacked Bar Chart: Kas Per Minggu M1-M16
        const kasMingguanCtx = document.getElementById('kasMingguanChart');
        if (kasMingguanCtx) {
            const kasMingguanHistory = <?= json_encode($kas_per_minggu_history) ?>;
            new Chart(kasMingguanCtx.getContext('2d'), {
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
                            backgroundColor: isDarkMode ? '#334155' : '#e2e8f0',
                            borderRadius: 4
                        }
                    ]
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
                                    let item = kasMingguanHistory[context.dataIndex];
                                    let label = context.dataset.label;
                                    let val = context.parsed.y;
                                    let res = label + ': Rp ' + val.toLocaleString('id-ID');
                                    if (label === 'Lunas') res += ' (' + item.persen + '% lunas)';
                                    return res;
                                }
                            }
                        }
                    },
                    scales: {
                        y: {
                            stacked: true,
                            beginAtZero: true,
                            grid: { color: gridColor },
                            ticks: {
                                color: textColor,
                                font: { size: 10 },
                                callback: function(val) {
                                    if (val >= 1000000) return 'Rp ' + (val / 1000000).toFixed(1) + 'jt';
                                    if (val >= 1000) return 'Rp ' + (val / 1000).toFixed(0) + 'k';
                                    return 'Rp ' + val;
                                }
                            }
                        },
                        x: {
                            stacked: true,
                            grid: { display: false },
                            ticks: {
                                color: textColor,
                                font: { size: 9 }
                            }
                        }
                    }
                }
            });
        }
    </script>
</body>

</html>
