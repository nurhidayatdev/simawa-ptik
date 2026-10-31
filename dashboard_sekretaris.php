<?php
session_start();
include 'koneksi.php';
require_once 'functions/helpers.php';
require_once 'functions/foto_helpers.php';

// Proteksi Akses: Hanya Sekretaris dan Administrator Utama
if (!isset($_SESSION['user_id'])) {
    header("Location: index");
    exit();
}

$user_id      = $_SESSION['user_id'];
$nama_lengkap = $_SESSION['nama_lengkap'] ?? 'Sekretaris Kelas';
$foto         = $_SESSION['foto'] ?? '';
$role         = $_SESSION['role'];

if ($role !== 'Sekretaris' && $role !== 'Administrator Utama') {
    include 'includes/alert_role.php';
    exit();
}

$kelas_id = getKelasScope();
if (!$kelas_id && $role !== 'Administrator Utama') {
    $_SESSION['error'] = "Akun Sekretaris Anda belum terikat ke kelas manapun.";
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
   1. STATISTIK PRESENSI KHUSUS KELAS
   ========================================================= */

// Total Entri Absensi & Tingkat Kehadiran
$q_total_abs = mysqli_query($koneksi, "
    SELECT 
        COUNT(*) as total_rekam,
        SUM(CASE WHEN status = 'Hadir' THEN 1 ELSE 0 END) as total_hadir,
        SUM(CASE WHEN status = 'Izin' THEN 1 ELSE 0 END) as total_izin,
        SUM(CASE WHEN status = 'Sakit' THEN 1 ELSE 0 END) as total_sakit,
        SUM(CASE WHEN status = 'Alpha' THEN 1 ELSE 0 END) as total_alpha
    FROM absensi 
    WHERE kelas_id = '$kelas_id' AND periode_id = '$periode_id'
");
$stats_abs = mysqli_fetch_assoc($q_total_abs);
$total_rekam = (int)($stats_abs['total_rekam'] ?? 0);
$total_hadir = (int)($stats_abs['total_hadir'] ?? 0);
$total_izin  = (int)($stats_abs['total_izin'] ?? 0);
$total_sakit = (int)($stats_abs['total_sakit'] ?? 0);
$total_alpha = (int)($stats_abs['total_alpha'] ?? 0);

$attendance_rate = ($total_rekam > 0) ? round(($total_hadir / $total_rekam) * 100, 1) : 0;

// Total Sesi Pertemuan Perkuliahan yang Telah Dicatat (Distinct matkul & pertemuan)
$q_total_sesi = mysqli_query($koneksi, "
    SELECT COUNT(DISTINCT matkul_id, pertemuan) as total_sesi 
    FROM absensi 
    WHERE kelas_id = '$kelas_id' AND periode_id = '$periode_id'
");
$total_sesi = (int)(mysqli_fetch_assoc($q_total_sesi)['total_sesi'] ?? 0);

// Data Pertemuan Terakhir yang Dicatat
$q_last_session = mysqli_query($koneksi, "
    SELECT 
        a.matkul_id,
        a.pertemuan,
        mk.nama_matkul,
        COUNT(*) as total_mhs,
        SUM(CASE WHEN a.status = 'Hadir' THEN 1 ELSE 0 END) as hadir,
        SUM(CASE WHEN a.status != 'Hadir' THEN 1 ELSE 0 END) as tidak_hadir
    FROM absensi a
    JOIN mata_kuliah mk ON a.matkul_id = mk.id
    WHERE a.kelas_id = '$kelas_id' AND a.periode_id = '$periode_id'
    GROUP BY a.matkul_id, a.pertemuan
    ORDER BY a.id DESC
    LIMIT 1
");
$last_session = mysqli_fetch_assoc($q_last_session);

/* =========================================================
   2. LOG 5 SESI PERTEMUAN TERAKHIR KELAS
   ========================================================= */
$q_recent_sessions = mysqli_query($koneksi, "
    SELECT 
        a.matkul_id,
        a.pertemuan,
        mk.nama_matkul,
        mk.kode_matkul,
        COUNT(*) as total_mhs,
        SUM(CASE WHEN a.status = 'Hadir' THEN 1 ELSE 0 END) as hadir,
        SUM(CASE WHEN a.status = 'Izin' THEN 1 ELSE 0 END) as izin,
        SUM(CASE WHEN a.status = 'Sakit' THEN 1 ELSE 0 END) as sakit,
        SUM(CASE WHEN a.status = 'Alpha' THEN 1 ELSE 0 END) as alpha
    FROM absensi a
    JOIN mata_kuliah mk ON a.matkul_id = mk.id
    WHERE a.kelas_id = '$kelas_id' AND a.periode_id = '$periode_id'
    GROUP BY a.matkul_id, a.pertemuan
    ORDER BY a.id DESC
    LIMIT 5
");
$recent_sessions = [];
while ($row = mysqli_fetch_assoc($q_recent_sessions)) {
    $recent_sessions[] = $row;
}

/* =========================================================
   3. JADWAL KULIAH HARI INI (PENGINGAT SESI PRESENSI)
   ========================================================= */
$hari_map = [
    'Sunday' => 'Minggu',
    'Monday' => 'Senin',
    'Tuesday' => 'Selasa',
    'Wednesday' => 'Rabu',
    'Thursday' => 'Kamis',
    'Friday' => 'Jumat',
    'Saturday' => 'Sabtu'
];
$hari_ini = $hari_map[date('l')] ?? 'Senin';

$q_jadwal = mysqli_query($koneksi, "
    SELECT 
        jm.id,
        jm.hari,
        jm.matkul_id,
        mk.nama_matkul,
        mk.kode_matkul,
        mk.sks,
        r.nama_ruangan,
        jk_start.jam_mulai,
        jk_end.jam_selesai,
        d.nama_dosen
    FROM jadwal_matkul jm
    JOIN mata_kuliah mk ON jm.matkul_id = mk.id
    LEFT JOIN ruangan r ON jm.ruangan_id = r.id
    LEFT JOIN jam_kuliah jk_start ON jm.jam_mulai_ke = jk_start.jam_ke
    LEFT JOIN jam_kuliah jk_end ON jm.jam_selesai_ke = jk_end.jam_ke
    LEFT JOIN dosen d ON jm.dosen_pengampu_id = d.id
    WHERE (jm.kelas_id = '$kelas_id' OR jm.kelas_id IS NULL) AND jm.hari = '$hari_ini'
    ORDER BY jk_start.jam_mulai ASC
");
$jadwal_hari_ini = [];
while ($row = mysqli_fetch_assoc($q_jadwal)) {
    $jadwal_hari_ini[] = $row;
}

/* =========================================================
   4. MAHASISWA PERLU PERHATIAN (ALPHA/TIDAK HADIR TERTINGGI)
   ========================================================= */
$q_top_absent = mysqli_query($koneksi, "
    SELECT * FROM (
        SELECT 
            m.id,
            m.nama_lengkap,
            m.nim,
            SUM(CASE WHEN a.status = 'Alpha' THEN 1 ELSE 0 END) as alpha,
            SUM(CASE WHEN a.status = 'Izin' THEN 1 ELSE 0 END) as izin,
            SUM(CASE WHEN a.status = 'Sakit' THEN 1 ELSE 0 END) as sakit,
            COUNT(a.id) as total_rekam,
            SUM(CASE WHEN a.status = 'Hadir' THEN 1 ELSE 0 END) as total_hadir
        FROM mahasiswa m
        LEFT JOIN users u ON m.user_id = u.id
        JOIN absensi a ON m.id = a.mahasiswa_id
        WHERE m.kelas_id = '$kelas_id' AND a.periode_id = '$periode_id' " . getMahasiswaPureWhereClause('m', 'u', true) . "
        GROUP BY m.id
    ) sub
    WHERE (sub.alpha > 0 OR (sub.izin + sub.sakit) >= 1)
    ORDER BY sub.alpha DESC, (sub.izin + sub.sakit) DESC
    LIMIT 5
");
$top_absent = [];
while ($row = mysqli_fetch_assoc($q_top_absent)) {
    $top_absent[] = $row;
}

/* =========================================================
   5. DATA TUGAS KULIAH KELAS (MANAJEMEN TUGAS OLEH SEKRETARIS)
   ========================================================= */
$q_tugas_stats = mysqli_query($koneksi, "
    SELECT 
        COUNT(*) as total_tugas,
        SUM(CASE WHEN status = 'belum' THEN 1 ELSE 0 END) as tugas_aktif,
        SUM(CASE WHEN status = 'selesai' THEN 1 ELSE 0 END) as tugas_selesai
    FROM tugas
    WHERE (kelas_id = '$kelas_id' OR kelas_id IS NULL)
");
$tugas_stats = mysqli_fetch_assoc($q_tugas_stats);
$total_tugas   = (int)($tugas_stats['total_tugas'] ?? 0);
$tugas_aktif   = (int)($tugas_stats['tugas_aktif'] ?? 0);
$tugas_selesai = (int)($tugas_stats['tugas_selesai'] ?? 0);

// 5 Tugas perkuliahan teratas (prioritas belum selesai & deadline terdekat)
$q_tugas_list = mysqli_query($koneksi, "
    SELECT t.*, mk.nama_matkul, mk.kode_matkul
    FROM tugas t
    LEFT JOIN mata_kuliah mk ON t.matkul_id = mk.id
    WHERE (t.kelas_id = '$kelas_id' OR t.kelas_id IS NULL)
    ORDER BY (CASE WHEN t.status = 'belum' THEN 0 ELSE 1 END) ASC, t.deadline_tanggal ASC, t.deadline_pukul ASC
    LIMIT 5
");
$tugas_list = [];
while ($row = mysqli_fetch_assoc($q_tugas_list)) {
    $tugas_list[] = $row;
}

$title = "Dashboard Sekretaris - " . $nama_kelas_aktif;
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <title><?= htmlspecialchars($title) ?></title>
    <?php include 'includes/head.php'; ?>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        .custom-scrollbar::-webkit-scrollbar {
            width: 4px;
        }

        .custom-scrollbar::-webkit-scrollbar-thumb {
            background: #cbd5e1;
            border-radius: 10px;
        }

        .dark .custom-scrollbar::-webkit-scrollbar-thumb {
            background: #334155;
        }
    </style>
</head>

<body class="bg-slate-100 dark:bg-slate-950 text-slate-800 dark:text-slate-200 font-sans antialiased min-h-screen flex flex-col transition-colors duration-300">

    <!-- Navigasi Shell (Top Bar + Fixed Sidebar Khusus Sekretaris) -->
    <?php include 'includes/menu_db.php'; ?>

    <!-- Main Shell (Offset Margin Left ml-0 lg:ml-48, pt-20 untuk topbar) -->
    <main class="ml-0 lg:ml-48 pt-24 px-4 md:px-6 pb-6 flex flex-col min-h-screen">
        <div class="max-w-7xl mx-auto w-full space-y-6 flex-grow">

            <!-- 1. Header & Context Bar -->
            <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 p-6 md:p-7 shadow-sm flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                <div>
                    <div class="flex items-center gap-2 mb-2">
                        <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-[10px] font-extrabold uppercase tracking-widest bg-blue-50 dark:bg-blue-950/40 text-blue-700 dark:text-blue-400 border border-blue-200/60 dark:border-blue-900/50">
                            <i class="fas fa-clipboard-check text-[9px]"></i> Sekretaris Kelas
                        </span>
                        <span class="text-xs font-semibold text-slate-400 dark:text-slate-500">• <?= htmlspecialchars($nama_kelas_aktif) ?></span>
                    </div>
                    <h1 class="text-2xl md:text-3xl font-extrabold text-slate-900 dark:text-white tracking-tight">
                        Pusat Presensi & Tugas Kuliah Kelas
                    </h1>
                    <p class="text-xs md:text-sm text-slate-500 dark:text-slate-400 mt-1.5">
                        Pencatatan presensi kehadiran dan manajemen tenggat tugas perkuliahan mahasiswa <?= htmlspecialchars($nama_kelas_aktif) ?>.
                    </p>
                </div>
                <div class="flex flex-wrap items-center gap-2.5">
                    <a href="actions/tambah_absensi.php" class="inline-flex items-center gap-2 px-4 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-bold rounded-xl shadow-lg shadow-indigo-600/20 active:scale-95 transition-all">
                        <i class="fas fa-calendar-plus"></i> Input Absen Baru
                    </a>
                    <a href="database/db_tugas" class="inline-flex items-center gap-2 px-4 py-2.5 bg-blue-600 hover:bg-blue-700 text-white text-xs font-bold rounded-xl shadow-lg shadow-blue-600/20 active:scale-95 transition-all">
                        <i class="fas fa-tasks"></i> Tugas Kuliah
                    </a>
                    <a href="views/rekap_absen.php" class="inline-flex items-center gap-2 px-4 py-2.5 bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 text-xs font-bold rounded-xl border border-slate-200 dark:border-slate-700 transition-all">
                        <i class="fas fa-chart-line"></i> Rekap Absen
                    </a>
                </div>
            </div>

            <!-- 2. Ringkasan Metrik (Grid 4 Kolom: Presensi + Tugas) -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 md:gap-5">
                
                <!-- Card 1: Tingkat Kehadiran Kelas -->
                <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 p-5 shadow-sm hover:shadow-md transition-all group">
                    <div class="flex items-center justify-between">
                        <div>
                            <span class="text-[10px] font-bold uppercase tracking-widest text-slate-400 dark:text-slate-500">Tingkat Kehadiran</span>
                            <div class="text-2xl md:text-3xl font-black text-slate-800 dark:text-white mt-1.5"><?= $attendance_rate ?>%</div>
                            <div class="text-[11px] font-semibold text-emerald-600 dark:text-emerald-400 mt-1 flex items-center gap-1">
                                <i class="fas fa-check-circle text-[10px]"></i> <?= $total_hadir ?> Hadir dari <?= $total_rekam ?>
                            </div>
                        </div>
                        <div class="w-12 h-12 rounded-2xl bg-emerald-50 dark:bg-emerald-950/40 text-emerald-600 dark:text-emerald-400 border border-emerald-100 dark:border-emerald-900/50 flex items-center justify-center text-xl group-hover:scale-110 transition-transform">
                            <i class="fas fa-chart-pie"></i>
                        </div>
                    </div>
                </div>

                <!-- Card 2: Sesi Pertemuan Dicatat -->
                <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 p-5 shadow-sm hover:shadow-md transition-all group">
                    <div class="flex items-center justify-between">
                        <div>
                            <span class="text-[10px] font-bold uppercase tracking-widest text-slate-400 dark:text-slate-500">Sesi Presensi</span>
                            <div class="text-2xl md:text-3xl font-black text-slate-800 dark:text-white mt-1.5"><?= $total_sesi ?></div>
                            <div class="text-[11px] font-semibold text-indigo-600 dark:text-indigo-400 mt-1">
                                Pertemuan Perkuliahan
                            </div>
                        </div>
                        <div class="w-12 h-12 rounded-2xl bg-indigo-50 dark:bg-indigo-950/40 text-indigo-600 dark:text-indigo-400 border border-indigo-100 dark:border-indigo-900/50 flex items-center justify-center text-xl group-hover:scale-110 transition-transform">
                            <i class="fas fa-book-bookmark"></i>
                        </div>
                    </div>
                </div>

                <!-- Card 3: Tugas Kuliah Aktif -->
                <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 p-5 shadow-sm hover:shadow-md transition-all group">
                    <div class="flex items-center justify-between">
                        <div>
                            <span class="text-[10px] font-bold uppercase tracking-widest text-slate-400 dark:text-slate-500">Tugas Aktif</span>
                            <div class="text-2xl md:text-3xl font-black text-blue-600 dark:text-blue-400 mt-1.5"><?= $tugas_aktif ?></div>
                            <div class="text-[11px] font-semibold text-slate-500 dark:text-slate-400 mt-1 flex items-center gap-1">
                                <i class="fas fa-tasks text-[10px]"></i> Dari <?= $total_tugas ?> Total Tugas
                            </div>
                        </div>
                        <div class="w-12 h-12 rounded-2xl bg-blue-50 dark:bg-blue-950/40 text-blue-600 dark:text-blue-400 border border-blue-100 dark:border-blue-900/50 flex items-center justify-center text-xl group-hover:scale-110 transition-transform">
                            <i class="fas fa-list-check"></i>
                        </div>
                    </div>
                </div>

                <!-- Card 4: Total Ketidakhadiran (Izin / Sakit / Alpha) -->
                <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 p-5 shadow-sm hover:shadow-md transition-all group">
                    <div class="flex items-center justify-between">
                        <div>
                            <span class="text-[10px] font-bold uppercase tracking-widest text-slate-400 dark:text-slate-500">Ketidakhadiran</span>
                            <div class="text-2xl md:text-3xl font-black text-rose-600 dark:text-rose-400 mt-1.5"><?= ($total_izin + $total_sakit + $total_alpha) ?></div>
                            <div class="text-[11px] font-semibold text-slate-500 dark:text-slate-400 mt-1.5">
                                <?= $total_alpha ?> Alpha • <?= $total_izin ?> Izin • <?= $total_sakit ?> Sakit
                            </div>
                        </div>
                        <div class="w-12 h-12 rounded-2xl bg-rose-50 dark:bg-rose-950/40 text-rose-600 dark:text-rose-400 border border-rose-100 dark:border-rose-900/50 flex items-center justify-center text-xl group-hover:scale-110 transition-transform">
                            <i class="fas fa-user-xmark"></i>
                        </div>
                    </div>
                </div>

            </div>

            <!-- 3. Pintasan Akses Presensi & Tugas Kelas (Quick Launcher - 6 Grid) -->
            <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 p-5 shadow-sm">
                <div class="flex items-center justify-between mb-3.5">
                    <h3 class="text-xs font-bold uppercase tracking-wider text-slate-400 dark:text-slate-500 flex items-center gap-2">
                        <i class="fas fa-bolt text-indigo-600 dark:text-indigo-400"></i> Pintasan Menu Administrasi & Tugas
                    </h3>
                </div>
                <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3">
                    <a href="actions/tambah_absensi.php" class="p-3.5 rounded-xl bg-slate-50 hover:bg-indigo-50/70 dark:bg-slate-800/50 dark:hover:bg-slate-800 border border-slate-200 dark:border-slate-700/60 transition-all group">
                        <i class="fas fa-calendar-check text-indigo-600 dark:text-indigo-400 text-lg mb-1.5 block group-hover:scale-110 transition-transform"></i>
                        <div class="font-bold text-xs text-slate-800 dark:text-slate-200">Input Absen Baru</div>
                        <div class="text-[10px] text-slate-400 mt-0.5">Catat kehadiran</div>
                    </a>

                    <a href="views/manajemen_absen.php" class="p-3.5 rounded-xl bg-slate-50 hover:bg-indigo-50/70 dark:bg-slate-800/50 dark:hover:bg-slate-800 border border-slate-200 dark:border-slate-700/60 transition-all group">
                        <i class="fas fa-table-list text-blue-600 dark:text-blue-400 text-lg mb-1.5 block group-hover:scale-110 transition-transform"></i>
                        <div class="font-bold text-xs text-slate-800 dark:text-slate-200">Data Presensi</div>
                        <div class="text-[10px] text-slate-400 mt-0.5">Edit kehadiran</div>
                    </a>

                    <a href="views/rekap_absen.php" class="p-3.5 rounded-xl bg-slate-50 hover:bg-indigo-50/70 dark:bg-slate-800/50 dark:hover:bg-slate-800 border border-slate-200 dark:border-slate-700/60 transition-all group">
                        <i class="fas fa-chart-line text-emerald-600 dark:text-emerald-400 text-lg mb-1.5 block group-hover:scale-110 transition-transform"></i>
                        <div class="font-bold text-xs text-slate-800 dark:text-slate-200">Rekap Presensi</div>
                        <div class="text-[10px] text-slate-400 mt-0.5">Persentase & total</div>
                    </a>

                    <a href="database/db_tugas" class="p-3.5 rounded-xl bg-slate-50 hover:bg-indigo-50/70 dark:bg-slate-800/50 dark:hover:bg-slate-800 border border-slate-200 dark:border-slate-700/60 transition-all group">
                        <i class="fas fa-tasks text-amber-600 dark:text-amber-400 text-lg mb-1.5 block group-hover:scale-110 transition-transform"></i>
                        <div class="font-bold text-xs text-slate-800 dark:text-slate-200">Data Tugas</div>
                        <div class="text-[10px] text-slate-400 mt-0.5">Kelola deadline</div>
                    </a>

                    <a href="actions/tambah.php?tabel=tugas" class="p-3.5 rounded-xl bg-slate-50 hover:bg-indigo-50/70 dark:bg-slate-800/50 dark:hover:bg-slate-800 border border-slate-200 dark:border-slate-700/60 transition-all group">
                        <i class="fas fa-plus-circle text-purple-600 dark:text-purple-400 text-lg mb-1.5 block group-hover:scale-110 transition-transform"></i>
                        <div class="font-bold text-xs text-slate-800 dark:text-slate-200">Tambah Tugas</div>
                        <div class="text-[10px] text-slate-400 mt-0.5">Buat tugas baru</div>
                    </a>

                    <a href="database/db_mahasiswa" class="p-3.5 rounded-xl bg-slate-50 hover:bg-indigo-50/70 dark:bg-slate-800/50 dark:hover:bg-slate-800 border border-slate-200 dark:border-slate-700/60 transition-all group">
                        <i class="fas fa-user-graduate text-cyan-600 dark:text-cyan-400 text-lg mb-1.5 block group-hover:scale-110 transition-transform"></i>
                        <div class="font-bold text-xs text-slate-800 dark:text-slate-200">Mahasiswa</div>
                        <div class="text-[10px] text-slate-400 mt-0.5">Anggota kelas</div>
                    </a>
                </div>
            </div>

            <!-- 4. Section Monitoring Tugas Perkuliahan & Deadline -->
            <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-sm overflow-hidden">
                <div class="p-5 border-b border-slate-100 dark:border-slate-800 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 bg-slate-50/50 dark:bg-slate-900/50">
                    <div>
                        <h3 class="text-base font-extrabold text-slate-800 dark:text-white flex items-center gap-2">
                            <i class="fas fa-tasks text-indigo-600 dark:text-indigo-400"></i>
                            Monitoring Tugas Kuliah & Deadline <?= htmlspecialchars($nama_kelas_aktif) ?>
                        </h3>
                        <p class="text-xs text-slate-400 dark:text-slate-500 mt-0.5">Kelola tenggat waktu, tautan pengumpulan LMS, dan pengingat WhatsApp.</p>
                    </div>
                    <div class="flex flex-wrap items-center gap-2">
                        <button type="button" onclick="confirmReminderWhatsApp()" class="px-3.5 py-1.5 rounded-xl text-xs font-bold bg-blue-600 hover:bg-blue-700 text-white transition-all shadow-sm flex items-center gap-1.5 cursor-pointer">
                            <i class="fas fa-paper-plane text-[10px]"></i> Reminder WA
                        </button>
                        <a href="actions/tambah.php?tabel=tugas" class="px-3.5 py-1.5 rounded-xl text-xs font-bold bg-indigo-600 hover:bg-indigo-700 text-white transition-all shadow-sm flex items-center gap-1.5">
                            <i class="fas fa-plus text-[10px]"></i> Tambah Tugas
                        </a>
                        <a href="database/db_tugas" class="px-3.5 py-1.5 rounded-xl text-xs font-bold bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 border border-slate-200 dark:border-slate-700 transition-all">
                            Kelola Semua <i class="fas fa-arrow-right ml-1 text-[10px]"></i>
                        </a>
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-indigo-900 dark:bg-slate-800 text-white border-b border-indigo-950 dark:border-slate-700 text-[10px] md:text-xs font-bold uppercase tracking-wider">
                                <th class="px-5 py-3.5 w-12 text-center">No</th>
                                <th class="px-5 py-3.5">Mata Kuliah & Instruksi</th>
                                <th class="px-5 py-3.5 text-center">Deadline</th>
                                <th class="px-5 py-3.5 text-center">Status</th>
                                <th class="px-5 py-3.5 text-center">Link / File</th>
                                <th class="px-5 py-3.5 text-center w-28">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-800/80 text-xs">
                            <?php if (empty($tugas_list)): ?>
                                <tr>
                                    <td colspan="6" class="px-6 py-10 text-center text-slate-400 dark:text-slate-500">
                                        <i class="fas fa-clipboard-check text-3xl mb-2 block opacity-30"></i>
                                        Belum ada tugas kuliah yang dicatat untuk kelas ini. Klik tombol <strong>Tambah Tugas</strong> untuk memulai.
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php $no_tugas = 1;
                                foreach ($tugas_list as $t): 
                                    $is_expired = false;
                                    $deadline_ts = strtotime($t['deadline_tanggal'] . ' ' . $t['deadline_pukul']);
                                    if ($deadline_ts && $deadline_ts < time()) {
                                        $is_expired = true;
                                    }
                                ?>
                                    <tr class="hover:bg-slate-50/80 dark:hover:bg-slate-800/40 transition-colors">
                                        <td class="px-5 py-3.5 text-center font-bold text-slate-400"><?= $no_tugas++ ?></td>
                                        <td class="px-5 py-3.5">
                                            <div class="font-extrabold text-slate-800 dark:text-white">
                                                <?= htmlspecialchars($t['nama_matkul'] ?? 'Mata Kuliah Umum') ?>
                                            </div>
                                            <div class="text-[11px] text-slate-500 dark:text-slate-400 line-clamp-1 mt-0.5">
                                                <?= htmlspecialchars($t['deskripsi'] ?: 'Tidak ada instruksi khusus.') ?>
                                            </div>
                                        </td>
                                        <td class="px-5 py-3.5 text-center whitespace-nowrap">
                                            <div class="font-bold text-xs <?= $is_expired && $t['status'] === 'belum' ? 'text-rose-600 dark:text-rose-400' : 'text-slate-700 dark:text-slate-300' ?>">
                                                <?= date('d M Y', strtotime($t['deadline_tanggal'])) ?>
                                            </div>
                                            <div class="text-[10px] text-slate-400">
                                                Pukul <?= htmlspecialchars(substr($t['deadline_pukul'], 0, 5)) ?> WITA
                                            </div>
                                        </td>
                                        <td class="px-5 py-3.5 text-center">
                                            <?php if ($t['status'] === 'selesai'): ?>
                                                <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-extrabold bg-emerald-50 text-emerald-700 border border-emerald-200 dark:bg-emerald-950/50 dark:text-emerald-400">
                                                    <i class="fas fa-check-circle text-[9px]"></i> Selesai
                                                </span>
                                            <?php elseif ($is_expired): ?>
                                                <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-extrabold bg-rose-50 text-rose-700 border border-rose-200 dark:bg-rose-950/50 dark:text-rose-400">
                                                    <i class="fas fa-clock text-[9px]"></i> Terlewat
                                                </span>
                                            <?php else: ?>
                                                <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-extrabold bg-amber-50 text-amber-700 border border-amber-200 dark:bg-amber-950/50 dark:text-amber-400">
                                                    <i class="fas fa-hourglass-half text-[9px]"></i> Aktif
                                                </span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="px-5 py-3.5 text-center">
                                            <?php if (!empty($t['link_kirim'])): ?>
                                                <a href="<?= htmlspecialchars($t['link_kirim']) ?>" target="_blank" rel="noopener noreferrer" class="inline-flex items-center gap-1 text-[11px] font-bold text-indigo-600 dark:text-indigo-400 hover:underline">
                                                    <i class="fas fa-arrow-up-right-from-square text-[9px]"></i> LMS Link
                                                </a>
                                            <?php elseif (!empty($t['file_tugas'])): ?>
                                                <a href="storage/uploads/<?= htmlspecialchars($t['file_tugas']) ?>" target="_blank" class="inline-flex items-center gap-1 text-[11px] font-bold text-blue-600 dark:text-blue-400 hover:underline">
                                                    <i class="fas fa-download text-[9px]"></i> Unduh File
                                                </a>
                                            <?php else: ?>
                                                <span class="text-slate-400 text-[10px]">-</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="px-5 py-3.5 text-center">
                                            <div class="flex items-center justify-center gap-2">
                                                <a href="actions/edit.php?tabel=tugas&id=<?= (int)$t['id'] ?>" class="w-7 h-7 rounded-lg bg-blue-50 hover:bg-blue-600 dark:bg-blue-950/50 dark:hover:bg-blue-600 text-blue-600 hover:text-white dark:text-blue-400 dark:hover:text-white flex items-center justify-center transition-colors" title="Edit Tugas">
                                                    <i class="fas fa-edit text-[11px]"></i>
                                                </a>
                                                <a href="actions/hapus.php?tabel=tugas&id=<?= (int)$t['id'] ?>" onclick="return confirm('Apakah Anda yakin ingin menghapus tugas ini?')" class="w-7 h-7 rounded-lg bg-rose-50 hover:bg-rose-600 dark:bg-rose-950/50 dark:hover:bg-rose-600 text-rose-600 hover:text-white dark:text-rose-400 dark:hover:text-white flex items-center justify-center transition-colors" title="Hapus Tugas">
                                                    <i class="fas fa-trash text-[11px]"></i>
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- 5. Section Utama: Log 5 Sesi Presensi Terakhir Kelas -->
            <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-sm overflow-hidden">
                <div class="p-5 border-b border-slate-100 dark:border-slate-800 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 bg-slate-50/50 dark:bg-slate-900/50">
                    <div>
                        <h3 class="text-base font-extrabold text-slate-800 dark:text-white flex items-center gap-2">
                            <i class="fas fa-clock-rotate-left text-indigo-600 dark:text-indigo-400"></i>
                            Riwayat Sesi Presensi Terakhir <?= htmlspecialchars($nama_kelas_aktif) ?>
                        </h3>
                        <p class="text-xs text-slate-400 dark:text-slate-500 mt-0.5">Sesi perkuliahan yang telah dicatat pada semester <?= htmlspecialchars($periode_nama) ?>.</p>
                    </div>
                    <a href="views/manajemen_absen.php" class="px-3.5 py-1.5 rounded-xl text-xs font-bold bg-indigo-50 hover:bg-indigo-600 dark:bg-indigo-950/50 dark:hover:bg-indigo-600 text-indigo-600 hover:text-white dark:text-indigo-400 dark:hover:text-white border border-indigo-200 dark:border-indigo-900/50 transition-all w-fit">
                        Kelola Semua Sesi <i class="fas fa-arrow-right ml-1 text-[10px]"></i>
                    </a>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-indigo-900 dark:bg-slate-800 text-white border-b border-indigo-950 dark:border-slate-700 text-[10px] md:text-xs font-bold uppercase tracking-wider">
                                <th class="px-5 py-3.5 w-12 text-center">No</th>
                                <th class="px-5 py-3.5">Mata Kuliah</th>
                                <th class="px-5 py-3.5 text-center">Pertemuan</th>
                                <th class="px-5 py-3.5 text-center">Hadir</th>
                                <th class="px-5 py-3.5 text-center">Izin</th>
                                <th class="px-5 py-3.5 text-center">Sakit</th>
                                <th class="px-5 py-3.5 text-center">Alpha</th>
                                <th class="px-5 py-3.5 text-center">Tingkat Hadir</th>
                                <th class="px-5 py-3.5 text-center w-28">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-800/80 text-xs">
                            <?php if (empty($recent_sessions)): ?>
                                <tr>
                                    <td colspan="9" class="px-6 py-10 text-center text-slate-400 dark:text-slate-500">
                                        <i class="fas fa-clipboard-question text-3xl mb-2 block opacity-30"></i>
                                        Belum ada riwayat sesi presensi yang dicatat di kelas ini. Klik tombol <strong>Input Absen Baru</strong> untuk memulai.
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php $no = 1;
                                foreach ($recent_sessions as $rs): 
                                    $rate_sesi = ($rs['total_mhs'] > 0) ? round(($rs['hadir'] / $rs['total_mhs']) * 100, 1) : 0;
                                ?>
                                    <tr class="hover:bg-slate-50/80 dark:hover:bg-slate-800/40 transition-colors">
                                        <td class="px-5 py-3.5 text-center font-bold text-slate-400"><?= $no++ ?></td>
                                        <td class="px-5 py-3.5">
                                            <div class="font-extrabold text-slate-800 dark:text-white">
                                                <?= htmlspecialchars($rs['nama_matkul']) ?>
                                            </div>
                                            <div class="text-[10px] text-slate-400">
                                                Kode: <?= htmlspecialchars($rs['kode_matkul'] ?? '-') ?> • <?= (int)$rs['total_mhs'] ?> Mahasiswa
                                            </div>
                                        </td>
                                        <td class="px-5 py-3.5 text-center">
                                            <span class="inline-flex items-center px-2.5 py-1 rounded-lg text-xs font-black bg-indigo-50 dark:bg-indigo-950/60 text-indigo-700 dark:text-indigo-400 border border-indigo-200 dark:border-indigo-900/50">
                                                P-<?= (int)$rs['pertemuan'] ?>
                                            </span>
                                        </td>
                                        <td class="px-5 py-3.5 text-center font-bold text-emerald-600 dark:text-emerald-400"><?= (int)$rs['hadir'] ?></td>
                                        <td class="px-5 py-3.5 text-center font-semibold text-blue-600 dark:text-blue-400"><?= (int)$rs['izin'] ?></td>
                                        <td class="px-5 py-3.5 text-center font-semibold text-amber-600 dark:text-amber-400"><?= (int)$rs['sakit'] ?></td>
                                        <td class="px-5 py-3.5 text-center font-bold text-rose-600 dark:text-rose-400"><?= (int)$rs['alpha'] ?></td>
                                        <td class="px-5 py-3.5 text-center">
                                            <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-extrabold <?= $rate_sesi >= 80 ? 'bg-emerald-50 text-emerald-700 border-emerald-200 dark:bg-emerald-950/50 dark:text-emerald-400' : 'bg-amber-50 text-amber-700 border-amber-200 dark:bg-amber-950/50 dark:text-amber-400' ?> border">
                                                <?= $rate_sesi ?>%
                                            </span>
                                        </td>
                                        <td class="px-5 py-3.5 text-center">
                                            <a href="views/manajemen_absen.php?matkul_id=<?= (int)$rs['matkul_id'] ?>&pertemuan=<?= (int)$rs['pertemuan'] ?>" class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg bg-indigo-50 hover:bg-indigo-600 dark:bg-indigo-950/50 dark:hover:bg-indigo-600 text-indigo-600 hover:text-white dark:text-indigo-400 dark:hover:text-white text-xs font-semibold transition-colors">
                                                <i class="fas fa-eye text-[10px]"></i> Periksa
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- 6. Section Bawah: Jadwal Hari Ini & Mahasiswa Perlu Perhatian (Grid 2 Kolom) -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

                <!-- Kolom Kiri: Agenda Presensi Hari Ini (Berdasarkan Jadwal) -->
                <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 p-5 shadow-sm">
                    <div class="flex items-center justify-between pb-3 mb-3 border-b border-slate-100 dark:border-slate-800">
                        <h3 class="text-sm font-extrabold text-slate-800 dark:text-white flex items-center gap-2">
                            <i class="fas fa-calendar-day text-indigo-600 dark:text-indigo-400"></i>
                            Agenda Kuliah Hari Ini (<?= $hari_ini ?>)
                        </h3>
                        <span class="text-[10px] font-bold text-slate-400">Sesi Wajib Presensi</span>
                    </div>

                    <?php if (empty($jadwal_hari_ini)): ?>
                        <div class="py-8 text-center text-slate-400 dark:text-slate-500 text-xs">
                            <i class="fas fa-mug-hot text-3xl mb-2 block opacity-40"></i>
                            Tidak ada jadwal perkuliahan kelas hari ini.
                        </div>
                    <?php else: ?>
                        <div class="space-y-3">
                            <?php foreach ($jadwal_hari_ini as $j): ?>
                                <div class="p-3.5 rounded-xl bg-slate-50 dark:bg-slate-800/60 border border-slate-100 dark:border-slate-700/60 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                                    <div>
                                        <div class="font-extrabold text-xs text-slate-800 dark:text-white">
                                            <?= htmlspecialchars($j['nama_matkul']) ?>
                                        </div>
                                        <div class="flex items-center gap-2 text-[11px] text-slate-500 dark:text-slate-400 mt-1.5">
                                            <span><i class="fas fa-clock mr-1 text-amber-500"></i> <?= htmlspecialchars(substr($j['jam_mulai'] ?? '', 0, 5)) ?> - <?= htmlspecialchars(substr($j['jam_selesai'] ?? '', 0, 5)) ?></span>
                                            <span>•</span>
                                            <span><i class="fas fa-door-open mr-1 text-emerald-500"></i> <?= htmlspecialchars($j['nama_ruangan'] ?? 'Ruang TBA') ?></span>
                                        </div>
                                        <div class="text-[10px] text-slate-400 mt-0.5">
                                            Dosen: <?= htmlspecialchars($j['nama_dosen'] ?? '-') ?>
                                        </div>
                                    </div>
                                    <a href="actions/tambah_absensi.php?matkul_id=<?= (int)$j['matkul_id'] ?>" class="px-3 py-1.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg text-xs font-bold shadow-sm transition-all text-center shrink-0">
                                        <i class="fas fa-check mr-1 text-[10px]"></i> Presensi Sekarang
                                    </a>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Kolom Kanan: Mahasiswa Perlu Perhatian (Ketidakhadiran Tertinggi) -->
                <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 p-5 shadow-sm">
                    <div class="flex items-center justify-between pb-3 mb-3 border-b border-slate-100 dark:border-slate-800">
                        <h3 class="text-sm font-extrabold text-slate-800 dark:text-white flex items-center gap-2">
                            <i class="fas fa-triangle-exclamation text-amber-500"></i>
                            Mahasiswa Perlu Perhatian
                        </h3>
                        <span class="text-[10px] font-bold text-slate-400">Akumulasi Absen</span>
                    </div>

                    <?php if (empty($top_absent)): ?>
                        <div class="py-8 text-center text-slate-400 dark:text-slate-500 text-xs">
                            <i class="fas fa-award text-3xl mb-2 block opacity-40 text-emerald-500"></i>
                            Luar biasa! Seluruh mahasiswa memiliki kehadiran prima (belum ada yang terkena Alpha).
                        </div>
                    <?php else: ?>
                        <div class="space-y-2.5">
                            <?php foreach ($top_absent as $ta): ?>
                                <div class="p-3 rounded-xl bg-slate-50 dark:bg-slate-800/60 border border-slate-100 dark:border-slate-700/60 flex items-center justify-between gap-3">
                                    <div class="flex items-center gap-2.5 overflow-hidden">
                                        <div class="w-8 h-8 rounded-full overflow-hidden border border-slate-200 dark:border-slate-700 bg-slate-100 dark:bg-slate-800 shrink-0">
                                            <img src="<?= profilePhotoUrl($ta['nama_lengkap']) ?>" class="w-full h-full object-cover">
                                        </div>
                                        <div class="overflow-hidden">
                                            <div class="font-extrabold text-xs text-slate-800 dark:text-white truncate">
                                                <?= htmlspecialchars($ta['nama_lengkap']) ?>
                                            </div>
                                            <div class="text-[10px] text-slate-400 font-mono">
                                                <?= htmlspecialchars($ta['nim']) ?>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="flex items-center gap-1.5 shrink-0 text-[10px] font-bold">
                                        <?php if ($ta['alpha'] > 0): ?>
                                            <span class="px-2 py-0.5 rounded bg-rose-100 dark:bg-rose-950/60 text-rose-700 dark:text-rose-400 border border-rose-200 dark:border-rose-800">
                                                <?= (int)$ta['alpha'] ?> Alpha
                                            </span>
                                        <?php endif; ?>
                                        <?php if ($ta['sakit'] > 0): ?>
                                            <span class="px-2 py-0.5 rounded bg-amber-100 dark:bg-amber-950/60 text-amber-700 dark:text-amber-400 border border-amber-200 dark:border-amber-800">
                                                <?= (int)$ta['sakit'] ?> Sakit
                                            </span>
                                        <?php endif; ?>
                                        <?php if ($ta['izin'] > 0): ?>
                                            <span class="px-2 py-0.5 rounded bg-blue-100 dark:bg-blue-950/60 text-blue-700 dark:text-blue-400 border border-blue-200 dark:border-blue-800">
                                                <?= (int)$ta['izin'] ?> Izin
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>

            </div>

        </div>

        <!-- Footer Back-office -->
        <?php include 'includes/footer_db.php'; ?>
    </main>

    <!-- Script Back-office -->
    <?php include 'includes/script_db.php'; ?>
    <script>
        // SweetAlert Confirmation for WhatsApp Reminder
        function confirmReminderWhatsApp() {
            Swal.fire({
                title: 'Kirim Reminder WhatsApp?',
                text: "Daftar tugas perkuliahan aktif akan dikirimkan ke grup WhatsApp kelas.",
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#2563eb',
                cancelButtonColor: '#ef4444',
                confirmButtonText: 'Ya, Kirim Sekarang!',
                cancelButtonText: 'Batal',
                customClass: { popup: 'rounded-2xl' }
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = 'functions/kirim_tugas.php';
                }
            });
        }

        // SweetAlert Flash Message Handling
        <?php if (!empty($success_msg)): ?>
            Swal.fire({
                icon: 'success',
                title: 'Berhasil!',
                html: <?= json_encode($success_msg) ?>,
                confirmButtonColor: '#4f46e5',
                customClass: { popup: 'rounded-2xl' }
            });
        <?php endif; ?>

        <?php if (!empty($error_msg)): ?>
            Swal.fire({
                icon: 'error',
                title: 'Perhatian!',
                html: <?= json_encode($error_msg) ?>,
                confirmButtonColor: '#ef4444',
                customClass: { popup: 'rounded-2xl' }
            });
        <?php endif; ?>
    </script>
</body>

</html>