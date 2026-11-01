<?php
session_start();
include 'koneksi.php';
require_once 'functions/helpers.php';
require_once 'functions/foto_helpers.php';

// Proteksi Akses: Hanya Ketua Tingkat dan Administrator Utama
if (!isset($_SESSION['user_id'])) {
    header("Location: index");
    exit();
}

$user_id      = $_SESSION['user_id'];
$nama_lengkap = $_SESSION['nama_lengkap'] ?? 'Ketua Tingkat';
$foto         = $_SESSION['foto'] ?? '';
$role         = $_SESSION['role'];

if ($role !== 'Ketua Tingkat' && $role !== 'Administrator Utama') {
    include 'includes/alert_role.php';
    exit();
}

$kelas_id = getKelasScope();
if (!$kelas_id && $role !== 'Administrator Utama') {
    $_SESSION['error'] = "Akun Anda belum terikat ke kelas manapun. Harap hubungi Administrator Utama.";
    header("Location: index");
    exit();
}

// Ambil info metadata kelas aktif
$nama_kelas_aktif = getNamaKelasAktif();
$q_kelas_info = mysqli_query($koneksi, "SELECT * FROM kelas WHERE id = '$kelas_id' LIMIT 1");
$kelas_info = mysqli_fetch_assoc($q_kelas_info);
$prodi    = $kelas_info['prodi'] ?? 'Pendidikan Teknik Informatika dan Komputer';
$angkatan = $kelas_info['angkatan'] ?? '2024';

// Flash Messages
$success_msg = $_SESSION['success'] ?? '';
$error_msg   = $_SESSION['error'] ?? '';
unset($_SESSION['success'], $_SESSION['error']);

/* =========================================================
   1. STATISTIK RINGKASAN KELAS
   ========================================================= */

// Total Mahasiswa Kelas
$mhs_pure_clause = getMahasiswaPureWhereClause('m', 'u', true);
$q_mhs_count = mysqli_query($koneksi, "SELECT COUNT(*) as total FROM mahasiswa m LEFT JOIN users u ON m.user_id = u.id WHERE m.kelas_id = '$kelas_id' $mhs_pure_clause");
$total_mhs = (int)(mysqli_fetch_assoc($q_mhs_count)['total'] ?? 0);

// Total Pengurus Kelas (Sekretaris, Bendahara)
$q_pengurus_count = mysqli_query($koneksi, "
    SELECT COUNT(*) as total 
    FROM users 
    WHERE kelas_id = '$kelas_id' AND role IN ('Sekretaris', 'Bendahara')
");
$total_pengurus = (int)(mysqli_fetch_assoc($q_pengurus_count)['total'] ?? 0);

// Saldo Kas Kelas
$res_other_in = mysqli_query($koneksi, "SELECT COALESCE(SUM(jumlah),0) AS total_other_masuk FROM arus_kas WHERE tipe='masuk' AND kelas_id = '$kelas_id'");
$total_other_masuk = mysqli_fetch_assoc($res_other_in)['total_other_masuk'] ?? 0;

$res_kas = mysqli_query($koneksi, "SELECT COALESCE(SUM(nominal),0) AS total_kas FROM kas WHERE status='Lunas' AND kelas_id = '$kelas_id'");
$total_kas = mysqli_fetch_assoc($res_kas)['total_kas'] ?? 0;
$total_masuk = $total_kas + $total_other_masuk;

$res_out = mysqli_query($koneksi, "SELECT COALESCE(SUM(jumlah),0) AS total_keluar FROM arus_kas WHERE tipe='keluar' AND kelas_id = '$kelas_id'");
$total_keluar = mysqli_fetch_assoc($res_out)['total_keluar'] ?? 0;
$saldo_kas = $total_masuk - $total_keluar;

// Persentase Kehadiran Kelas
$q_absen_tot = mysqli_query($koneksi, "SELECT COUNT(*) as total FROM absensi WHERE kelas_id = '$kelas_id'");
$tot_absen = (int)(mysqli_fetch_assoc($q_absen_tot)['total'] ?? 0);
$q_absen_hdr = mysqli_query($koneksi, "SELECT COUNT(*) as total FROM absensi WHERE status = 'Hadir' AND kelas_id = '$kelas_id'");
$tot_hadir = (int)(mysqli_fetch_assoc($q_absen_hdr)['total'] ?? 0);
$attendance_rate = ($tot_absen > 0) ? round(($tot_hadir / $tot_absen) * 100, 1) : 0;

// Total Tugas Aktif (Belum lewat deadline)
$q_tugas_count = mysqli_query($koneksi, "
    SELECT COUNT(*) as total 
    FROM tugas 
    WHERE (kelas_id = '$kelas_id' OR kelas_id IS NULL) 
    AND (deadline_tanggal > CURDATE() OR (deadline_tanggal = CURDATE() AND deadline_pukul >= CURTIME()))
    AND status = 'belum'
");
$total_tugas_aktif = (int)(mysqli_fetch_assoc($q_tugas_count)['total'] ?? 0);

/* =========================================================
   2. DAFTAR PENGURUS KELAS AKTIF
   ========================================================= */
$q_pengurus = mysqli_query($koneksi, "
    SELECT 
        u.id as user_id,
        u.email,
        u.role,
        m.id as mahasiswa_id,
        COALESCE(NULLIF(u.nama_lengkap, ''), m.nama_lengkap, 'Pengurus') as nama_lengkap,
        COALESCE(NULLIF(u.linked_student_nim, ''), m.nim, '-') as nim,
        m.jenis_kelamin
    FROM users u
    LEFT JOIN mahasiswa m ON m.user_id = u.id
    WHERE u.kelas_id = '$kelas_id' AND u.role IN ('Sekretaris', 'Bendahara')
    ORDER BY FIELD(u.role, 'Sekretaris', 'Bendahara'), u.nama_lengkap ASC
");
$pengurus_list = [];
while ($row = mysqli_fetch_assoc($q_pengurus)) {
    $pengurus_list[] = $row;
}

/* =========================================================
   3. DAFTAR ANGGOTA MAHASISWA KELAS
   ========================================================= */
$q_mhs = mysqli_query($koneksi, "
    SELECT 
        m.id,
        m.nim,
        m.nama_lengkap,
                m.jenis_kelamin,
        m.bio,
                m.user_id,
        u.email,
        u.role
    FROM mahasiswa m
    LEFT JOIN users u ON m.user_id = u.id
    WHERE m.kelas_id = '$kelas_id' $mhs_pure_clause
    ORDER BY m.nim ASC
");
$mhs_list = [];
$mhs_non_pengurus = [];
while ($row = mysqli_fetch_assoc($q_mhs)) {
    $mhs_list[] = $row;
    // Calon pengurus: belum punya role atau role masih Mahasiswa biasa
    if (empty($row['role']) || $row['role'] === 'Mahasiswa') {
        $mhs_non_pengurus[] = $row;
    }
}

/* =========================================================
   4. JADWAL HARI INI & DEADLINE TUGAS
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

$q_tugas = mysqli_query($koneksi, "
    SELECT 
        t.id,
        t.deskripsi,
        t.deadline_tanggal,
        t.deadline_pukul,
        mk.nama_matkul,
        DATEDIFF(t.deadline_tanggal, CURDATE()) as sisa_hari
    FROM tugas t
    JOIN mata_kuliah mk ON t.matkul_id = mk.id
    WHERE (t.kelas_id = '$kelas_id' OR t.kelas_id IS NULL) 
    AND (t.deadline_tanggal > CURDATE() OR (t.deadline_tanggal = CURDATE() AND t.deadline_pukul >= CURTIME()))
    ORDER BY t.deadline_tanggal ASC, t.deadline_pukul ASC
    LIMIT 4
");
$tugas_mendatang = [];
while ($row = mysqli_fetch_assoc($q_tugas)) {
    $tugas_mendatang[] = $row;
}

$title = "Dashboard Ketua Tingkat - " . $nama_kelas_aktif;
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

    <!-- Navigasi Shell (Top Bar + Fixed Sidebar Khusus Ketua Tingkat) -->
    <?php include 'includes/menu_db.php'; ?>

    <!-- Main Shell (Offset Margin Left ml-0 lg:ml-48, pt-20 untuk topbar) -->
    <main class="ml-0 lg:ml-48 pt-24 px-4 md:px-6 pb-6 flex flex-col min-h-screen">
        <div class="max-w-7xl mx-auto w-full space-y-6 flex-grow">

            <!-- 1. Header & Quick Actions -->
            <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 p-6 md:p-7 shadow-sm flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                <div>
                    <div class="flex items-center gap-2 mb-2">
                        <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-[10px] font-extrabold uppercase tracking-widest bg-amber-50 dark:bg-amber-950/40 text-amber-700 dark:text-amber-400 border border-amber-200/60 dark:border-amber-900/50">
                            <i class="fas fa-crown text-[9px]"></i> Ketua Tingkat
                        </span>
                        <span class="text-xs font-semibold text-slate-400 dark:text-slate-500">• <?= htmlspecialchars($nama_kelas_aktif) ?></span>
                    </div>
                    <h1 class="text-2xl md:text-3xl font-extrabold text-slate-900 dark:text-white tracking-tight">
                        Pusat Kendali <?= htmlspecialchars($nama_kelas_aktif) ?>
                    </h1>
                    <p class="text-xs md:text-sm text-slate-500 dark:text-slate-400 mt-1.5">
                        Kelola data mahasiswa kelas, penunjukan pengurus (Sekretaris & Bendahara), dan aktivitas perkuliahan kelas Anda.
                    </p>
                </div>
                <div class="flex flex-wrap items-center gap-2.5">
                    <button type="button" onclick="openTambahMhsModal()" class="inline-flex items-center gap-2 px-4 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-bold rounded-xl shadow-lg shadow-indigo-600/20 active:scale-95 transition-all cursor-pointer">
                        <i class="fas fa-user-plus"></i> Tambah Mahasiswa
                    </button>
                    <button type="button" onclick="openTunjukPengurusModal()" class="inline-flex items-center gap-2 px-4 py-2.5 bg-amber-500 hover:bg-amber-600 text-white text-xs font-bold rounded-xl shadow-lg shadow-amber-500/20 active:scale-95 transition-all cursor-pointer">
                        <i class="fas fa-user-shield"></i> Tunjuk Pengurus
                    </button>
                </div>
            </div>

            <!-- 2. Ringkasan Metrik Kelas (Grid 4 Kolom) -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 md:gap-5">
                
                <!-- Card 1: Total Mahasiswa -->
                <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 p-5 shadow-sm hover:shadow-md transition-all group">
                    <div class="flex items-center justify-between">
                        <div>
                            <span class="text-[10px] font-bold uppercase tracking-widest text-slate-400 dark:text-slate-500">Anggota Kelas</span>
                            <div class="text-2xl md:text-3xl font-black text-slate-800 dark:text-white mt-1.5"><?= $total_mhs ?></div>
                            <div class="text-[11px] font-semibold text-indigo-600 dark:text-indigo-400 mt-1 flex items-center gap-1">
                                <i class="fas fa-shield text-[10px]"></i> <?= $total_pengurus ?> Pengurus Terpilih
                            </div>
                        </div>
                        <div class="w-12 h-12 rounded-2xl bg-indigo-50 dark:bg-indigo-950/40 text-indigo-600 dark:text-indigo-400 border border-indigo-100 dark:border-indigo-900/50 flex items-center justify-center text-xl group-hover:scale-110 transition-transform">
                            <i class="fas fa-user-graduate"></i>
                        </div>
                    </div>
                </div>

                <!-- Card 2: Saldo Kas Kelas -->
                <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 p-5 shadow-sm hover:shadow-md transition-all group">
                    <div class="flex items-center justify-between">
                        <div>
                            <span class="text-[10px] font-bold uppercase tracking-widest text-slate-400 dark:text-slate-500">Saldo Kas Kelas</span>
                            <div class="text-xl md:text-2xl font-black text-slate-800 dark:text-white mt-1.5">
                                Rp <?= number_format($saldo_kas, 0, ',', '.') ?>
                            </div>
                            <div class="text-[11px] font-semibold text-emerald-600 dark:text-emerald-400 mt-1 flex items-center gap-1">
                                <i class="fas fa-arrow-trend-up text-[10px]"></i> Masuk: Rp <?= number_format($total_masuk, 0, ',', '.') ?>
                            </div>
                        </div>
                        <div class="w-12 h-12 rounded-2xl bg-emerald-50 dark:bg-emerald-950/40 text-emerald-600 dark:text-emerald-400 border border-emerald-100 dark:border-emerald-900/50 flex items-center justify-center text-xl group-hover:scale-110 transition-transform">
                            <i class="fas fa-wallet"></i>
                        </div>
                    </div>
                </div>

                <!-- Card 3: Kehadiran Kelas -->
                <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 p-5 shadow-sm hover:shadow-md transition-all group">
                    <div class="flex items-center justify-between">
                        <div>
                            <span class="text-[10px] font-bold uppercase tracking-widest text-slate-400 dark:text-slate-500">Kehadiran Kelas</span>
                            <div class="text-2xl md:text-3xl font-black text-slate-800 dark:text-white mt-1.5"><?= $attendance_rate ?>%</div>
                            <div class="text-[11px] font-semibold text-slate-500 dark:text-slate-400 mt-1.5">
                                <?= $tot_hadir ?> Hadir dari <?= $tot_absen ?> Sesi
                            </div>
                        </div>
                        <div class="w-12 h-12 rounded-2xl bg-blue-50 dark:bg-blue-950/40 text-blue-600 dark:text-blue-400 border border-blue-100 dark:border-blue-900/50 flex items-center justify-center text-xl group-hover:scale-110 transition-transform">
                            <i class="fas fa-calendar-check"></i>
                        </div>
                    </div>
                </div>

                <!-- Card 4: Tugas Aktif -->
                <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 p-5 shadow-sm hover:shadow-md transition-all group">
                    <div class="flex items-center justify-between">
                        <div>
                            <span class="text-[10px] font-bold uppercase tracking-widest text-slate-400 dark:text-slate-500">Tugas Berjalan</span>
                            <div class="text-2xl md:text-3xl font-black text-slate-800 dark:text-white mt-1.5"><?= $total_tugas_aktif ?></div>
                            <div class="text-[11px] font-semibold text-rose-600 dark:text-rose-400 mt-1 flex items-center gap-1">
                                <i class="fas fa-clock text-[10px]"></i> Deadline Terbuka
                            </div>
                        </div>
                        <div class="w-12 h-12 rounded-2xl bg-rose-50 dark:bg-rose-950/40 text-rose-600 dark:text-rose-400 border border-rose-100 dark:border-rose-900/50 flex items-center justify-center text-xl group-hover:scale-110 transition-transform">
                            <i class="fas fa-tasks"></i>
                        </div>
                    </div>
                </div>

            </div>

            <!-- 3. Pusat Akses Cepat Operasional (Quick Action Launcher - 5 Grid) -->
            <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 p-5 shadow-sm">
                <div class="flex items-center justify-between mb-3.5">
                    <h3 class="text-xs font-bold uppercase tracking-wider text-slate-400 dark:text-slate-500 flex items-center gap-2">
                        <i class="fas fa-rocket text-indigo-600 dark:text-indigo-400"></i> Pintasan Operasional Kelas
                    </h3>
                </div>
                <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3">
                    <button type="button" onclick="openTambahMhsModal()" class="p-3.5 rounded-xl bg-slate-50 hover:bg-indigo-50/70 dark:bg-slate-800/50 dark:hover:bg-slate-800 border border-slate-200 dark:border-slate-700/60 transition-all text-left group cursor-pointer">
                        <i class="fas fa-user-plus text-indigo-600 dark:text-indigo-400 text-lg mb-1.5 block group-hover:scale-110 transition-transform"></i>
                        <div class="font-bold text-xs text-slate-800 dark:text-slate-200">Tambah Anggota</div>
                        <div class="text-[10px] text-slate-400 mt-0.5">Input mahasiswa kelas</div>
                    </button>

                    <button type="button" onclick="openTunjukPengurusModal()" class="p-3.5 rounded-xl bg-slate-50 hover:bg-indigo-50/70 dark:bg-slate-800/50 dark:hover:bg-slate-800 border border-slate-200 dark:border-slate-700/60 transition-all text-left group cursor-pointer">
                        <i class="fas fa-user-shield text-amber-600 dark:text-amber-400 text-lg mb-1.5 block group-hover:scale-110 transition-transform"></i>
                        <div class="font-bold text-xs text-slate-800 dark:text-slate-200">Akun Pengurus</div>
                        <div class="text-[10px] text-slate-400 mt-0.5">Sekretaris/Bendahara</div>
                    </button>

                    <a href="database/db_jadwal" class="p-3.5 rounded-xl bg-slate-50 hover:bg-indigo-50/70 dark:bg-slate-800/50 dark:hover:bg-slate-800 border border-slate-200 dark:border-slate-700/60 transition-all group">
                        <i class="fas fa-calendar-week text-indigo-600 dark:text-indigo-400 text-lg mb-1.5 block group-hover:scale-110 transition-transform"></i>
                        <div class="font-bold text-xs text-slate-800 dark:text-slate-200">Jadwal Kuliah</div>
                        <div class="text-[10px] text-slate-400 mt-0.5">Atur jadwal kelas</div>
                    </a>

                    <a href="views/piket_proyektor" class="p-3.5 rounded-xl bg-slate-50 hover:bg-indigo-50/70 dark:bg-slate-800/50 dark:hover:bg-slate-800 border border-slate-200 dark:border-slate-700/60 transition-all group">
                        <i class="fas fa-plug text-emerald-600 dark:text-emerald-400 text-lg mb-1.5 block group-hover:scale-110 transition-transform"></i>
                        <div class="font-bold text-xs text-slate-800 dark:text-slate-200">Piket Proyektor</div>
                        <div class="text-[10px] text-slate-400 mt-0.5">Jadwal & acak piket</div>
                    </a>

                    <a href="views/kelompok" class="p-3.5 rounded-xl bg-slate-50 hover:bg-indigo-50/70 dark:bg-slate-800/50 dark:hover:bg-slate-800 border border-slate-200 dark:border-slate-700/60 transition-all group">
                        <i class="fas fa-users text-blue-600 dark:text-blue-400 text-lg mb-1.5 block group-hover:scale-110 transition-transform"></i>
                        <div class="font-bold text-xs text-slate-800 dark:text-slate-200">Kelompok Kelas</div>
                        <div class="text-[10px] text-slate-400 mt-0.5">Bagi & atur kelompok</div>
                    </a>

                    <a href="database/db_tugas" class="p-3.5 rounded-xl bg-slate-50 hover:bg-indigo-50/70 dark:bg-slate-800/50 dark:hover:bg-slate-800 border border-slate-200 dark:border-slate-700/60 transition-all group">
                        <i class="fas fa-tasks text-purple-600 dark:text-purple-400 text-lg mb-1.5 block group-hover:scale-110 transition-transform"></i>
                        <div class="font-bold text-xs text-slate-800 dark:text-slate-200">Tugas Kuliah</div>
                        <div class="text-[10px] text-slate-400 mt-0.5">Buat & kelola tugas</div>
                    </a>
                </div>
            </div>

            <!-- 4. Section Utama 1: Manajemen Pengurus Kelas (Sekretaris & Bendahara) -->
            <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-sm overflow-hidden">
                <div class="p-5 border-b border-slate-100 dark:border-slate-800 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 bg-slate-50/50 dark:bg-slate-900/50">
                    <div>
                        <h3 class="text-base font-extrabold text-slate-800 dark:text-white flex items-center gap-2">
                            <i class="fas fa-shield-halved text-amber-500"></i>
                            Struktur Pengurus <?= htmlspecialchars($nama_kelas_aktif) ?>
                        </h3>
                        <p class="text-xs text-slate-400 dark:text-slate-500 mt-0.5">Pengurus yang ditunjuk memiliki hak akses input absensi (Sekretaris) atau kas (Bendahara).</p>
                    </div>
                    <button type="button" onclick="openTunjukPengurusModal()" class="px-3.5 py-1.5 rounded-xl text-xs font-bold bg-amber-500 hover:bg-amber-600 text-white shadow-sm transition-all cursor-pointer w-fit">
                        <i class="fas fa-plus mr-1"></i> Tunjuk Pengurus Baru
                    </button>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-indigo-900 dark:bg-slate-800 text-white border-b border-indigo-950 dark:border-slate-700 text-[10px] md:text-xs font-bold uppercase tracking-wider">
                                <th class="px-5 py-3.5 w-12 text-center">No</th>
                                <th class="px-5 py-3.5">Nama Pengurus</th>
                                <th class="px-5 py-3.5">NIM</th>
                                <th class="px-5 py-3.5">Jabatan / Role</th>
                                <th class="px-5 py-3.5">Email Login</th>
                                <th class="px-5 py-3.5 text-center w-48">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-800/80 text-xs">
                            <?php if (empty($pengurus_list)): ?>
                                <tr>
                                    <td colspan="6" class="px-6 py-10 text-center text-slate-400 dark:text-slate-500">
                                        <i class="fas fa-user-shield text-3xl mb-2 block opacity-30"></i>
                                        Belum ada pengurus yang ditunjuk untuk kelas ini. Klik tombol di atas untuk menunjuk Sekretaris atau Bendahara.
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php $no = 1;
                                foreach ($pengurus_list as $p): 
                                    $badge_class = 'bg-indigo-100 text-indigo-700 border-indigo-200 dark:bg-indigo-950/60 dark:text-indigo-400 dark:border-indigo-800';
                                    if ($p['role'] === 'Sekretaris') {
                                        $badge_class = 'bg-blue-100 text-blue-700 border-blue-200 dark:bg-blue-950/60 dark:text-blue-400 dark:border-blue-800';
                                    } elseif ($p['role'] === 'Bendahara') {
                                        $badge_class = 'bg-emerald-100 text-emerald-700 border-emerald-200 dark:bg-emerald-950/60 dark:text-emerald-400 dark:border-emerald-800';
                                    }
                                ?>
                                    <tr class="hover:bg-slate-50/80 dark:hover:bg-slate-800/40 transition-colors">
                                        <td class="px-5 py-3.5 text-center font-bold text-slate-400"><?= $no++ ?></td>
                                        
                                        <!-- Nama & Foto -->
                                        <td class="px-5 py-3.5">
                                            <div class="flex items-center gap-3">
                                                <div class="w-9 h-9 rounded-full overflow-hidden border border-slate-200 dark:border-slate-700 bg-slate-100 dark:bg-slate-800 shrink-0">
                                                    <img src="<?= profilePhotoUrl($p['nama_lengkap']) ?>" class="w-full h-full object-cover">
                                                </div>
                                                <div class="font-extrabold text-slate-800 dark:text-white">
                                                    <?= htmlspecialchars($p['nama_lengkap'] ?: $p['email']) ?>
                                                </div>
                                            </div>
                                        </td>

                                        <td class="px-5 py-3.5 font-bold text-slate-600 dark:text-slate-300">
                                            <?= htmlspecialchars($p['nim'] ?? '-') ?>
                                        </td>

                                        <td class="px-5 py-3.5">
                                            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-[11px] font-extrabold uppercase tracking-wider border <?= $badge_class ?>">
                                                <i class="fas fa-shield text-[9px]"></i> <?= htmlspecialchars($p['role']) ?>
                                            </span>
                                        </td>

                                        <td class="px-5 py-3.5 text-slate-500 dark:text-slate-400 font-mono text-[11px]">
                                            <?= htmlspecialchars($p['email']) ?>
                                        </td>

                                        <!-- Aksi -->
                                        <td class="px-5 py-3.5 text-center">
                                            <div class="flex items-center justify-center gap-1.5">
                                                <!-- Tombol Reset Password -->
                                                <button type="button" onclick='openResetPasswordModal(<?= (int)$p['user_id'] ?>, <?= json_encode($p['email']) ?>)' title="Reset Password Pengurus" class="inline-flex items-center justify-center px-2.5 py-1.5 rounded-lg bg-amber-50 hover:bg-amber-600 dark:bg-amber-950/40 dark:hover:bg-amber-600 text-amber-600 hover:text-white dark:text-amber-400 dark:hover:text-white border border-amber-200 dark:border-amber-900/50 text-xs font-semibold transition-colors cursor-pointer">
                                                    <i class="fas fa-key mr-1"></i> Reset Pwd
                                                </button>

                                                <!-- Tombol Copot Jabatan -->
                                                <form action="actions/kelola_pengurus" method="POST" class="inline" onsubmit="return confirm('Apakah Anda yakin ingin mencopot jabatan pengurus ini dan mengembalikannya menjadi Mahasiswa biasa?')">
                                                    <input type="hidden" name="action" value="copot_jabatan">
                                                    <input type="hidden" name="user_id" value="<?= (int)$p['user_id'] ?>">
                                                    <button type="submit" title="Copot Jabatan" class="inline-flex items-center justify-center px-2.5 py-1.5 rounded-lg bg-rose-50 hover:bg-rose-600 dark:bg-rose-950/40 text-rose-600 hover:text-white border border-rose-200 dark:border-rose-900/50 text-xs font-semibold transition-colors cursor-pointer">
                                                        <i class="fas fa-user-xmark mr-1"></i> Copot
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- 5. Section Utama 2: Anggota Mahasiswa Kelas & Widget Perkuliahan (Grid 3:2) -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

                <!-- Kolom Kiri (2 Kolom): Tabel Daftar Mahasiswa Kelas -->
                <div class="lg:col-span-2 bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-sm overflow-hidden flex flex-col justify-between">
                    <div>
                        <div class="p-5 border-b border-slate-100 dark:border-slate-800 flex items-center justify-between bg-slate-50/50 dark:bg-slate-900/50">
                            <div>
                                <h3 class="text-base font-extrabold text-slate-800 dark:text-white flex items-center gap-2">
                                    <i class="fas fa-users text-indigo-600 dark:text-indigo-400"></i>
                                    Daftar Mahasiswa <?= htmlspecialchars($nama_kelas_aktif) ?>
                                </h3>
                                <p class="text-xs text-slate-400 dark:text-slate-500 mt-0.5">Total <?= count($mhs_list) ?> mahasiswa terdaftar di kelas Anda.</p>
                            </div>
                            <a href="database/db_mahasiswa" class="text-xs font-bold text-indigo-600 hover:text-indigo-700 dark:text-indigo-400 flex items-center gap-1">
                                Lihat Semua <i class="fas fa-arrow-right text-[10px]"></i>
                            </a>
                        </div>

                        <div class="overflow-x-auto max-h-[420px] custom-scrollbar">
                            <table class="w-full text-left border-collapse">
                                <thead>
                                    <tr class="bg-indigo-900 dark:bg-slate-800 text-white text-[10px] md:text-xs font-bold uppercase tracking-wider sticky top-0 z-10">
                                        <th class="px-4 py-3 w-10 text-center">No</th>
                                        <th class="px-4 py-3">Nama Mahasiswa</th>
                                        <th class="px-4 py-3">NIM</th>
                                        <th class="px-4 py-3 text-center">Status Akun</th>
                                        <th class="px-4 py-3 text-center w-36">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100 dark:divide-slate-800/80 text-xs">
                                    <?php if (empty($mhs_list)): ?>
                                        <tr>
                                            <td colspan="5" class="px-6 py-10 text-center text-slate-400 dark:text-slate-500">
                                                Belum ada mahasiswa di kelas ini. Klik "Tambah Mahasiswa" untuk mendaftarkan anggota.
                                            </td>
                                        </tr>
                                    <?php else: ?>
                                        <?php $no = 1;
                                        foreach ($mhs_list as $m): 
                                            $has_account = !empty($m['user_id']);
                                        ?>
                                            <tr class="hover:bg-slate-50/80 dark:hover:bg-slate-800/40 transition-colors">
                                                <td class="px-4 py-2.5 text-center font-bold text-slate-400"><?= $no++ ?></td>
                                                <td class="px-4 py-2.5">
                                                    <div class="font-bold text-slate-800 dark:text-white">
                                                        <?= htmlspecialchars($m['nama_lengkap']) ?>
                                                    </div>
                                                    <div class="text-[10px] text-slate-400">
                                                        <?= htmlspecialchars($m['jenis_kelamin'] ?? '-') ?>
                                                    </div>
                                                </td>
                                                <td class="px-4 py-2.5 font-bold font-mono text-slate-600 dark:text-slate-300">
                                                    <?= htmlspecialchars($m['nim']) ?>
                                                </td>
                                                <td class="px-4 py-2.5 text-center">
                                                    <?php if ($has_account): ?>
                                                        <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-emerald-50 dark:bg-emerald-950/50 text-emerald-700 dark:text-emerald-400 border border-emerald-200 dark:border-emerald-800">
                                                            <i class="fas fa-check-circle text-[8px]"></i> Terdaftar (<?= htmlspecialchars($m['role'] ?? 'Mhs') ?>)
                                                        </span>
                                                    <?php else: ?>
                                                        <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-slate-100 dark:bg-slate-800 text-slate-500 dark:text-slate-400 border border-slate-200 dark:border-slate-700">
                                                            <i class="fas fa-circle-minus text-[8px]"></i> Belum Ada Akun
                                                        </span>
                                                    <?php endif; ?>
                                                </td>
                                                <td class="px-4 py-2.5 text-center">
                                                    <div class="flex items-center justify-center gap-1.5">
                                                        <?php if (!$has_account): ?>
                                                            <button type="button" onclick="openBuatAkunMhsModal(<?= (int)$m['id'] ?>, <?= json_encode($m['nama_lengkap']) ?>, <?= json_encode($m['nim']) ?>)" class="px-2 py-1 bg-indigo-50 hover:bg-indigo-600 dark:bg-indigo-950/40 text-indigo-600 hover:text-white rounded-lg border border-indigo-200 dark:border-indigo-900/50 text-[10px] font-bold transition-colors cursor-pointer" title="Buat Akun Login">
                                                                <i class="fas fa-plus mr-0.5"></i> Akun
                                                            </button>
                                                        <?php endif; ?>
                                                        <button type="button" onclick='openEditMhsModal(<?= htmlspecialchars(json_encode([
                                                            "id" => (int)$m["id"],
                                                            "nama_lengkap" => $m["nama_lengkap"],
                                                            "nim" => $m["nim"],
                                                            "jenis_kelamin" => $m["jenis_kelamin"] ?? "Laki-laki",
                                                            "bio" => $m["bio"] ?? ""
                                                        ]), ENT_QUOTES, "UTF-8") ?>)' class="w-7 h-7 rounded-lg bg-blue-50 dark:bg-blue-950/40 text-blue-600 dark:text-blue-400 hover:bg-blue-600 hover:text-white border border-blue-200 dark:border-blue-900/50 flex items-center justify-center text-xs transition-colors cursor-pointer" title="Edit Data Mahasiswa">
                                                            <i class="fas fa-edit"></i>
                                                        </button>
                                                        <a href="actions/hapus.php?tabel=mahasiswa&id=<?= $m['id'] ?>" class="w-7 h-7 rounded-lg bg-red-50 dark:bg-red-950/40 text-red-600 dark:text-red-400 hover:bg-red-600 hover:text-white border border-red-200 dark:border-red-900/50 flex items-center justify-center text-xs transition-colors" title="Hapus Data Mahasiswa">
                                                            <i class="fas fa-trash"></i>
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
                </div>

                <!-- Kolom Kanan (1 Kolom): Jadwal Hari Ini & Deadline Tugas -->
                <div class="space-y-6">

                    <!-- Widget 1: Jadwal Kuliah Hari Ini -->
                    <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 p-5 shadow-sm">
                        <div class="flex items-center justify-between pb-3 mb-3 border-b border-slate-100 dark:border-slate-800">
                            <h3 class="text-sm font-extrabold text-slate-800 dark:text-white flex items-center gap-2">
                                <i class="fas fa-calendar-day text-indigo-600 dark:text-indigo-400"></i>
                                Jadwal Kuliah Hari Ini
                            </h3>
                            <div class="flex items-center gap-2">
                                <span class="text-[10px] font-bold uppercase px-2 py-0.5 rounded bg-indigo-50 dark:bg-indigo-950/50 text-indigo-700 dark:text-indigo-400">
                                    <?= $hari_ini ?>
                                </span>
                                <a href="database/db_jadwal" class="text-xs font-bold text-indigo-600 hover:text-indigo-700 dark:text-indigo-400 flex items-center gap-1">
                                    Kelola <i class="fas fa-arrow-right text-[10px]"></i>
                                </a>
                            </div>
                        </div>

                        <?php if (empty($jadwal_hari_ini)): ?>
                            <div class="py-6 text-center text-slate-400 dark:text-slate-500 text-xs">
                                <i class="fas fa-couch text-2xl mb-1.5 block opacity-40"></i>
                                Tidak ada jadwal kuliah hari ini.
                            </div>
                        <?php else: ?>
                            <div class="space-y-2.5">
                                <?php foreach ($jadwal_hari_ini as $j): ?>
                                    <div class="p-3 rounded-xl bg-slate-50 dark:bg-slate-800/60 border border-slate-100 dark:border-slate-700/60">
                                        <div class="flex items-center justify-between">
                                            <span class="font-extrabold text-xs text-slate-800 dark:text-white">
                                                <?= htmlspecialchars($j['nama_matkul']) ?>
                                            </span>
                                            <span class="text-[10px] font-bold px-1.5 py-0.5 rounded bg-indigo-100 dark:bg-indigo-950/60 text-indigo-700 dark:text-indigo-300">
                                                <?= htmlspecialchars($j['sks']) ?> SKS
                                            </span>
                                        </div>
                                        <div class="flex items-center gap-2 text-[11px] text-slate-500 dark:text-slate-400 mt-1.5">
                                            <span><i class="fas fa-clock mr-1 text-amber-500"></i> <?= htmlspecialchars(substr($j['jam_mulai'] ?? '', 0, 5)) ?> - <?= htmlspecialchars(substr($j['jam_selesai'] ?? '', 0, 5)) ?></span>
                                            <span>•</span>
                                            <span><i class="fas fa-door-open mr-1 text-emerald-500"></i> <?= htmlspecialchars($j['nama_ruangan'] ?? 'Ruang TBA') ?></span>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Widget 2: Deadline Tugas Mendatang -->
                    <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 p-5 shadow-sm">
                        <div class="flex items-center justify-between pb-3 mb-3 border-b border-slate-100 dark:border-slate-800">
                            <h3 class="text-sm font-extrabold text-slate-800 dark:text-white flex items-center gap-2">
                                <i class="fas fa-hourglass-half text-rose-500"></i>
                                Tugas Mendekati Deadline
                            </h3>
                            <a href="database/db_tugas" class="text-[10px] font-bold text-indigo-600 dark:text-indigo-400 hover:underline">
                                Semua Tugas
                            </a>
                        </div>

                        <?php if (empty($tugas_mendatang)): ?>
                            <div class="py-6 text-center text-slate-400 dark:text-slate-500 text-xs">
                                <i class="fas fa-check-double text-2xl mb-1.5 block opacity-40"></i>
                                Semua tugas kelas beres! Belum ada deadline baru.
                            </div>
                        <?php else: ?>
                            <div class="space-y-2.5">
                                <?php foreach ($tugas_mendatang as $t): 
                                    $sisa = (int)$t['sisa_hari'];
                                    $sisa_badge = $sisa <= 1 
                                        ? 'bg-rose-100 text-rose-800 dark:bg-rose-950/60 dark:text-rose-400 border-rose-300' 
                                        : 'bg-amber-100 text-amber-800 dark:bg-amber-950/60 dark:text-amber-400 border-amber-300';
                                ?>
                                    <div class="p-3 rounded-xl bg-slate-50 dark:bg-slate-800/60 border border-slate-100 dark:border-slate-700/60 flex items-center justify-between gap-2">
                                        <div class="overflow-hidden">
                                            <div class="font-extrabold text-xs text-slate-800 dark:text-white truncate">
                                                <?= htmlspecialchars($t['nama_matkul']) ?>
                                            </div>
                                            <div class="text-[10px] text-slate-400 truncate mt-0.5">
                                                <?= htmlspecialchars(mb_strimwidth($t['deskripsi'] ?: 'Tugas perkuliahan', 0, 38, '...')) ?>
                                            </div>
                                        </div>
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-black border uppercase shrink-0 <?= $sisa_badge ?>">
                                            <?= $sisa == 0 ? 'Hari Ini' : ($sisa == 1 ? 'Besok' : $sisa . ' Hari') ?>
                                        </span>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>

                </div>

            </div>

        </div>

        <!-- Footer Back-office -->
        <?php include 'includes/footer_db.php'; ?>
    </main>

    <!-- ==========================================
         MODAL 1: TAMBAH MAHASISWA KELAS
         ========================================== -->
    
    <!-- ==========================================
         MODAL 1B: EDIT DATA MAHASISWA KELAS
         ========================================== -->
    <div id="modalEditMhs" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs hidden transition-opacity">
        <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-2xl max-w-lg w-full max-h-[90vh] overflow-y-auto custom-scrollbar p-6">
            <div class="flex items-center justify-between pb-4 mb-4 border-b border-slate-100 dark:border-slate-800">
                <div>
                    <h3 class="text-base font-extrabold text-slate-900 dark:text-white">Edit Data Mahasiswa</h3>
                    <p class="text-xs text-slate-400 dark:text-slate-500">Perbarui profil dan identitas mahasiswa.</p>
                </div>
                <button type="button" onclick="closeEditMhsModal()" class="w-8 h-8 rounded-lg bg-slate-100 dark:bg-slate-800 text-slate-400 hover:text-slate-700 dark:hover:text-white flex items-center justify-center transition-colors cursor-pointer">
                    <i class="fas fa-times text-xs"></i>
                </button>
            </div>

            <form action="actions/kelola_pengurus.php" method="POST" class="space-y-4">
                <input type="hidden" name="action" value="edit_mahasiswa">
                <input type="hidden" name="redirect_to" value="../dashboard_keti">
                <input type="hidden" name="id" id="dash_edit_mhs_id">

                <div>
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1">Nama Lengkap *</label>
                    <input type="text" name="nama_lengkap" id="dash_edit_nama_lengkap" required placeholder="Contoh: Muhammad Ihsan" class="w-full bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-900 dark:text-white px-3.5 py-2.5 rounded-xl text-xs focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 outline-none transition-all">
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1">NIM Mahasiswa *</label>
                        <input type="text" name="nim" id="dash_edit_nim" required placeholder="24020950..." class="w-full bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-900 dark:text-white px-3.5 py-2.5 rounded-xl text-xs focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 outline-none transition-all">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1">Jenis Kelamin</label>
                        <select name="jenis_kelamin" id="dash_edit_jenis_kelamin" class="w-full bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-900 dark:text-white px-3.5 py-2.5 rounded-xl text-xs focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 outline-none transition-all cursor-pointer">
                            <option value="Laki-laki">Laki-laki</option>
                            <option value="Perempuan">Perempuan</option>
                        </select>
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1">Biodata / Minat (Opsional)</label>
                    <input type="text" name="bio" id="dash_edit_bio" placeholder="Contoh: Minat Cyber Security & Programming" class="w-full bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-900 dark:text-white px-3.5 py-2.5 rounded-xl text-xs focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 outline-none transition-all">
                </div>

                <div class="flex items-center justify-end gap-2 pt-4 border-t border-slate-100 dark:border-slate-800">
                    <button type="button" onclick="closeEditMhsModal()" class="px-4 py-2.5 rounded-xl text-xs font-bold text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors cursor-pointer">Batal</button>
                    <button type="submit" class="px-5 py-2.5 rounded-xl text-xs font-bold bg-indigo-600 hover:bg-indigo-700 text-white shadow-lg shadow-indigo-600/20 active:scale-95 transition-all cursor-pointer">Simpan Perubahan</button>
                </div>
            </form>
        </div>
    </div>

<div id="modalTambahMhs" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs hidden transition-opacity">
        <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-2xl max-w-lg w-full max-h-[90vh] overflow-y-auto custom-scrollbar p-6">
            <div class="flex items-center justify-between pb-4 mb-4 border-b border-slate-100 dark:border-slate-800">
                <div>
                    <h3 class="text-base font-extrabold text-slate-900 dark:text-white">Tambah Mahasiswa Baru</h3>
                    <p class="text-xs text-slate-400 dark:text-slate-500">Otomatis terdaftar ke kelas <strong><?= htmlspecialchars($nama_kelas_aktif) ?></strong>.</p>
                </div>
                <button type="button" onclick="closeTambahMhsModal()" class="w-8 h-8 rounded-lg bg-slate-100 dark:bg-slate-800 text-slate-400 hover:text-slate-700 dark:hover:text-white flex items-center justify-center transition-colors cursor-pointer">
                    <i class="fas fa-times text-xs"></i>
                </button>
            </div>

            <form action="actions/kelola_pengurus" method="POST" enctype="multipart/form-data" class="space-y-4">
                <input type="hidden" name="action" value="tambah_mahasiswa">

                <div>
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1">Nama Lengkap *</label>
                    <input type="text" name="nama_lengkap" required placeholder="Contoh: Muhammad Ihsan" class="w-full bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-900 dark:text-white px-3.5 py-2.5 rounded-xl text-xs focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 outline-none transition-all">
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1">NIM Mahasiswa *</label>
                        <input type="text" name="nim" required placeholder="24020950..." class="w-full bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-900 dark:text-white px-3.5 py-2.5 rounded-xl text-xs focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 outline-none transition-all">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1">Jenis Kelamin</label>
                        <select name="jenis_kelamin" class="w-full bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-900 dark:text-white px-3.5 py-2.5 rounded-xl text-xs focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 outline-none transition-all cursor-pointer">
                            <option value="Laki-laki">Laki-laki</option>
                            <option value="Perempuan">Perempuan</option>
                        </select>
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1">Biodata / Minat (Opsional)</label>
                    <input type="text" name="bio" placeholder="Contoh: Minat Cyber Security & Programming" class="w-full bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-900 dark:text-white px-3.5 py-2.5 rounded-xl text-xs focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 outline-none transition-all">
                </div>

                                <!-- Pembuatan Akun Login Role Mahasiswa (Opsional) -->
                <div class="pt-3 border-t border-slate-100 dark:border-slate-800 space-y-3">
                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider">Akun Login Mahasiswa (Opsional)</label>
                        <p class="text-[10px] text-slate-400 mt-0.5">Isi jika mahasiswa ini (atau Anda sendiri) ingin memiliki akun login terpisah sebagai Mahasiswa.</p>
                    </div>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div>
                            <label class="block text-[11px] font-bold text-slate-700 dark:text-slate-300 mb-1">Email Login</label>
                            <input type="email" name="email" placeholder="contoh: mhs@simawa.id" class="w-full bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-900 dark:text-white px-3.5 py-2 rounded-xl text-xs focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 outline-none transition-all">
                        </div>
                        <div>
                            <label class="block text-[11px] font-bold text-slate-700 dark:text-slate-300 mb-1">Password Awal</label>
                            <input type="password" name="password" placeholder="Default: simawa2024" class="w-full bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-900 dark:text-white px-3.5 py-2 rounded-xl text-xs focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 outline-none transition-all">
                        </div>
                    </div>
                </div>

                <div class="flex items-center justify-end gap-2 pt-4 border-t border-slate-100 dark:border-slate-800">
                    <button type="button" onclick="closeTambahMhsModal()" class="px-4 py-2.5 rounded-xl text-xs font-bold text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors cursor-pointer">Batal</button>
                    <button type="submit" class="px-5 py-2.5 rounded-xl text-xs font-bold bg-indigo-600 hover:bg-indigo-700 text-white shadow-lg shadow-indigo-600/20 active:scale-95 transition-all cursor-pointer">Simpan Mahasiswa</button>
                </div>
            </form>
        </div>
    </div>

    <!-- ==========================================
         MODAL 2: TUNJUK / BUAT AKUN PENGURUS
         ========================================== -->
    <div id="modalTunjukPengurus" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs hidden transition-opacity">
        <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-2xl max-w-lg w-full max-h-[90vh] overflow-y-auto custom-scrollbar p-6">
            <div class="flex items-center justify-between pb-4 mb-4 border-b border-slate-100 dark:border-slate-800">
                <div>
                    <h3 class="text-base font-extrabold text-slate-900 dark:text-white">Tunjuk Pengurus Kelas</h3>
                    <p class="text-xs text-slate-400 dark:text-slate-500">Tetapkan hak akses Sekretaris atau Bendahara.</p>
                </div>
                <button type="button" onclick="closeTunjukPengurusModal()" class="w-8 h-8 rounded-lg bg-slate-100 dark:bg-slate-800 text-slate-400 hover:text-slate-700 dark:hover:text-white flex items-center justify-center transition-colors cursor-pointer">
                    <i class="fas fa-times text-xs"></i>
                </button>
            </div>

            <form action="actions/kelola_pengurus" method="POST" class="space-y-4">
                <input type="hidden" name="action" value="tunjuk_pengurus">

                <div>
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1">Pilih Mahasiswa Kelas *</label>
                    <select name="mahasiswa_id" id="tunjuk_mhs_id" required class="w-full bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-900 dark:text-white px-3.5 py-2.5 rounded-xl text-xs focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 outline-none transition-all cursor-pointer">
                        <option value="">-- Pilih Mahasiswa Kelas Anda --</option>
                        <?php foreach ($mhs_list as $ml): ?>
                            <option value="<?= $ml['id'] ?>">
                                <?= htmlspecialchars($ml['nama_lengkap']) ?> (<?= htmlspecialchars($ml['nim']) ?>) 
                                <?= !empty($ml['role']) && $ml['role'] !== 'Mahasiswa' ? ' - Saat ini: ' . htmlspecialchars($ml['role']) : '' ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1">Jabatan Pengurus *</label>
                    <select name="role_pengurus" required class="w-full bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-900 dark:text-white px-3.5 py-2.5 rounded-xl text-xs focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 outline-none transition-all cursor-pointer">
                        <option value="Sekretaris">Sekretaris (Manajemen Absensi & Tugas Kuliah)</option>
                        <option value="Bendahara">Bendahara (Manajemen & Input Kas Kelas)</option>
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1">Email Login *</label>
                    <input type="email" name="email" id="tunjuk_email" required placeholder="sekretaris.ptik@simawa.id" class="w-full bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-900 dark:text-white px-3.5 py-2.5 rounded-xl text-xs focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 outline-none transition-all">
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1">Password Awal</label>
                    <input type="password" name="password" placeholder="Default: simawa2024 (kosongkan jika tetap)" class="w-full bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-900 dark:text-white px-3.5 py-2.5 rounded-xl text-xs focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 outline-none transition-all">
                    <p class="text-[10px] text-slate-400 mt-1">Kosongkan jika mahasiswa sudah memiliki password sebelumnya atau gunakan default.</p>
                </div>

                <div class="flex items-center justify-end gap-2 pt-4 border-t border-slate-100 dark:border-slate-800">
                    <button type="button" onclick="closeTunjukPengurusModal()" class="px-4 py-2.5 rounded-xl text-xs font-bold text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors cursor-pointer">Batal</button>
                    <button type="submit" class="px-5 py-2.5 rounded-xl text-xs font-bold bg-amber-500 hover:bg-amber-600 text-white shadow-lg shadow-amber-500/20 active:scale-95 transition-all cursor-pointer">Simpan Jabatan</button>
                </div>
            </form>
        </div>
    </div>

    <!-- ==========================================
         MODAL 3: RESET PASSWORD PENGURUS
         ========================================== -->
    <div id="modalResetPassword" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs hidden transition-opacity">
        <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-2xl max-w-sm w-full p-6">
            <div class="flex items-center justify-between pb-3 mb-3 border-b border-slate-100 dark:border-slate-800">
                <h3 class="text-sm font-extrabold text-slate-900 dark:text-white">Reset Password Pengurus</h3>
                <button type="button" onclick="closeResetPasswordModal()" class="w-7 h-7 rounded-lg bg-slate-100 dark:bg-slate-800 text-slate-400 hover:text-slate-700 dark:hover:text-white flex items-center justify-center transition-colors cursor-pointer">
                    <i class="fas fa-times text-xs"></i>
                </button>
            </div>

            <form action="actions/kelola_pengurus" method="POST" class="space-y-3">
                <input type="hidden" name="action" value="reset_password_pengurus">
                <input type="hidden" name="user_id" id="reset_user_id">

                <div class="p-3 bg-slate-50 dark:bg-slate-800/60 rounded-xl border border-slate-200 dark:border-slate-700/60 text-xs">
                    <span class="text-[10px] text-slate-400 font-semibold uppercase block">Akun Pengurus:</span>
                    <span id="reset_user_email" class="font-extrabold text-slate-800 dark:text-slate-200 font-mono"></span>
                </div>

                <div>
                    <label class="block text-[11px] font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1">Password Baru</label>
                    <input type="text" name="new_password" placeholder="Default: simawa2024" class="w-full bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-900 dark:text-white px-3 py-2 rounded-xl text-xs outline-none">
                    <p class="text-[10px] text-slate-400 mt-1">Kosongkan untuk menyetel password default <code>simawa2024</code>.</p>
                </div>

                <div class="flex items-center justify-end gap-2 pt-3 border-t border-slate-100 dark:border-slate-800">
                    <button type="button" onclick="closeResetPasswordModal()" class="px-3.5 py-2 rounded-xl text-xs font-bold text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors cursor-pointer">Batal</button>
                    <button type="submit" class="px-4 py-2 rounded-xl text-xs font-bold bg-amber-600 hover:bg-amber-700 text-white shadow-md active:scale-95 transition-all cursor-pointer">Terapkan Password</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Script Back-office & Modal Management -->
    <?php include 'includes/script_db.php'; ?>
    <script>
        function openTambahMhsModal() {
            document.getElementById('modalTambahMhs').classList.remove('hidden');
        }

        function closeTambahMhsModal() {
            document.getElementById('modalTambahMhs').classList.add('hidden');
        }

        function openTunjukPengurusModal() {
            document.getElementById('modalTunjukPengurus').classList.remove('hidden');
        }

        function closeTunjukPengurusModal() {
            document.getElementById('modalTunjukPengurus').classList.add('hidden');
        }

        function openBuatAkunMhsModal(mhsId, namaLengkap, nim) {
            document.getElementById('tunjuk_mhs_id').value = mhsId;
            // Generate saran email default berbasis nim
            document.getElementById('tunjuk_email').value = nim + '@student.unm.ac.id';
            openTunjukPengurusModal();
        }

        function openResetPasswordModal(userId, email) {
            document.getElementById('reset_user_id').value = userId;
            document.getElementById('reset_user_email').innerText = email;
            document.getElementById('modalResetPassword').classList.remove('hidden');
        }

        function closeResetPasswordModal() {
            document.getElementById('modalResetPassword').classList.add('hidden');
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

    <script>
        function openEditMhsModal(mhs) {
            document.getElementById("dash_edit_mhs_id").value = mhs.id || "";
            document.getElementById("dash_edit_nama_lengkap").value = mhs.nama_lengkap || "";
            document.getElementById("dash_edit_nim").value = mhs.nim || "";
                        document.getElementById("dash_edit_jenis_kelamin").value = mhs.jenis_kelamin || "Laki-laki";
                        document.getElementById("dash_edit_bio").value = mhs.bio || "";
            document.getElementById("modalEditMhs").classList.remove("hidden");
        }
        function closeEditMhsModal() {
            document.getElementById("modalEditMhs").classList.add("hidden");
        }
    </script>

</body>

</html>
