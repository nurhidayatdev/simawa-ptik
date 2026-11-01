<?php
session_start();
include '../koneksi.php';

// Cek Login & Role
if (!isset($_SESSION['user_id'])) {
    header("Location: ../index.php");
    exit;
}

$role = $_SESSION['role'];
if ($role !== 'Ketua Tingkat') {
    $_SESSION['error'] = 'Akses ditolak! KRS Massal hanya wewenang Ketua Tingkat.';
    header('Location: ' . ($role === 'Administrator Utama' ? '../dashboard_admin.php' : '../dashboard.php'));
    exit();
}

$user_id    = $_SESSION['user_id'];
$nama_lengkap = $_SESSION['nama_lengkap'];
$foto = $_SESSION['foto'] ?? '';

include_once '../functions/foto_helpers.php';

// Pastikan tabel ada
mysqli_query($koneksi, "CREATE TABLE IF NOT EXISTS krs_temp (
    id INT AUTO_INCREMENT PRIMARY KEY,
    mahasiswa_id INT NOT NULL,
    matkul_id INT NOT NULL,
    periode_id INT NOT NULL,
    timestamp DATETIME DEFAULT CURRENT_TIMESTAMP,
    KEY idx_mhs_per (mahasiswa_id, periode_id)
)");
mysqli_query($koneksi, "CREATE TABLE IF NOT EXISTS krs_validasi (
    id INT AUTO_INCREMENT PRIMARY KEY,
    mahasiswa_id INT NOT NULL,
    periode_id INT NOT NULL,
    status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
    tanggal_validasi DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_validasi (mahasiswa_id, periode_id)
)");

// Ambil Periode Aktif
$q_periode = mysqli_query($koneksi, "SELECT id, semester_ke, tahun_periode, semester_nama FROM periode WHERE status = 'aktif' LIMIT 1");
$periode   = mysqli_fetch_assoc($q_periode);
if (!$periode) {
    die("Tidak ada periode aktif.");
}
$periode_id   = (int)$periode['id'];
$periode_info = $periode['tahun_periode'] . ' ' . $periode['semester_nama'];
$semester_aktif = (int)$periode['semester_ke'];

// ============================================================
// HANDLE POST ACTIONS DIRECTLY IN THIS FILE
// ============================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];

    // 1. BULK SET KRS (Massal & Auto-Approve)
    if ($action === 'bulk_set_krs') {
        $ids_raw    = $_POST['mahasiswa_ids'] ?? [];
        $matkul_raw = $_POST['matkul_ids'] ?? [];

        if (empty($ids_raw)) {
            header("Location: bulk_krs?msg=no_selection");
            exit;
        }
        if (empty($matkul_raw)) {
            header("Location: bulk_krs?msg=no_matkul");
            exit;
        }

        $ids     = array_map('intval', (array)$ids_raw);
        $matkuls = array_map('intval', (array)$matkul_raw);

        // Validasi total SKS
        $matkuls_str = implode(',', $matkuls);
        $res_sks     = mysqli_query($koneksi, "SELECT SUM(sks) as total FROM mata_kuliah WHERE id IN ($matkuls_str)");
        $data_sks    = mysqli_fetch_assoc($res_sks);
        $total_sks   = (int)($data_sks['total'] ?? 0);

        if ($total_sks > 24) {
            header("Location: bulk_krs?msg=sks_limit&sks=$total_sks");
            exit;
        }

        $stmt_temp = mysqli_prepare($koneksi, "INSERT INTO krs_temp (mahasiswa_id, matkul_id, periode_id) VALUES (?, ?, ?)");
        $stmt_krs  = mysqli_prepare($koneksi, "INSERT INTO krs (mahasiswa_id, matkul_id, periode_id) VALUES (?, ?, ?)");
        $stmt_val  = mysqli_prepare($koneksi, "INSERT INTO krs_validasi (mahasiswa_id, periode_id, status) VALUES (?, ?, 'approved') ON DUPLICATE KEY UPDATE status = 'approved', tanggal_validasi = NOW()");

        $count = 0;
        foreach ($ids as $mhs_id) {
            if ($mhs_id <= 0) continue;

            // Hapus KRS lama & temp lama untuk periode ini
            mysqli_query($koneksi, "DELETE FROM krs WHERE mahasiswa_id = $mhs_id AND periode_id = $periode_id");
            mysqli_query($koneksi, "DELETE FROM krs_temp WHERE mahasiswa_id = $mhs_id AND periode_id = $periode_id");

            // Insert ke krs_temp & krs
            foreach ($matkuls as $matkul_id) {
                mysqli_stmt_bind_param($stmt_temp, "iii", $mhs_id, $matkul_id, $periode_id);
                mysqli_stmt_execute($stmt_temp);

                mysqli_stmt_bind_param($stmt_krs, "iii", $mhs_id, $matkul_id, $periode_id);
                mysqli_stmt_execute($stmt_krs);
            }

            // Set status krs_validasi -> approved
            mysqli_stmt_bind_param($stmt_val, "ii", $mhs_id, $periode_id);
            mysqli_stmt_execute($stmt_val);

            $count++;
        }

        header("Location: bulk_krs?msg=bulk_set&count=$count&sks=$total_sks");
        exit;
    }

    // 2. BULK APPROVE
    if ($action === 'bulk_approve') {
        $ids_raw = $_POST['mahasiswa_ids'] ?? [];
        if (empty($ids_raw)) {
            header("Location: bulk_krs?msg=no_selection");
            exit;
        }

        $ids      = array_map('intval', (array)$ids_raw);
        $stmt_val = mysqli_prepare($koneksi, "INSERT INTO krs_validasi (mahasiswa_id, periode_id, status) VALUES (?, ?, 'approved') ON DUPLICATE KEY UPDATE status = 'approved', tanggal_validasi = NOW()");

        $count = 0;
        foreach ($ids as $mhs_id) {
            if ($mhs_id <= 0) continue;

            mysqli_query($koneksi, "DELETE FROM krs WHERE mahasiswa_id = $mhs_id AND periode_id = $periode_id");
            mysqli_query($koneksi, "
                INSERT INTO krs (mahasiswa_id, matkul_id, periode_id)
                SELECT mahasiswa_id, matkul_id, periode_id
                FROM krs_temp
                WHERE mahasiswa_id = $mhs_id AND periode_id = $periode_id
            ");

            mysqli_stmt_bind_param($stmt_val, "ii", $mhs_id, $periode_id);
            mysqli_stmt_execute($stmt_val);
            $count++;
        }

        header("Location: bulk_krs?msg=bulk_approved&count=$count");
        exit;
    }

    // 3. BULK REJECT
    if ($action === 'bulk_reject') {
        $ids_raw = $_POST['mahasiswa_ids'] ?? [];
        if (empty($ids_raw)) {
            header("Location: bulk_krs?msg=no_selection");
            exit;
        }

        $ids      = array_map('intval', (array)$ids_raw);
        $stmt_val = mysqli_prepare($koneksi, "INSERT INTO krs_validasi (mahasiswa_id, periode_id, status) VALUES (?, ?, 'rejected') ON DUPLICATE KEY UPDATE status = 'rejected', tanggal_validasi = NOW()");

        $count = 0;
        foreach ($ids as $mhs_id) {
            if ($mhs_id <= 0) continue;
            mysqli_stmt_bind_param($stmt_val, "ii", $mhs_id, $periode_id);
            mysqli_stmt_execute($stmt_val);
            $count++;
        }

        header("Location: bulk_krs?msg=bulk_rejected&count=$count");
        exit;
    }

    // 4. BULK RESET
    if ($action === 'bulk_reset') {
        $ids_raw = $_POST['mahasiswa_ids'] ?? [];
        if (empty($ids_raw)) {
            header("Location: bulk_krs?msg=no_selection");
            exit;
        }

        $ids   = array_map('intval', (array)$ids_raw);
        $count = 0;
        foreach ($ids as $mhs_id) {
            if ($mhs_id <= 0) continue;
            mysqli_query($koneksi, "DELETE FROM krs WHERE mahasiswa_id = $mhs_id AND periode_id = $periode_id");
            mysqli_query($koneksi, "DELETE FROM krs_temp WHERE mahasiswa_id = $mhs_id AND periode_id = $periode_id");
            mysqli_query($koneksi, "DELETE FROM krs_validasi WHERE mahasiswa_id = $mhs_id AND periode_id = $periode_id");
            $count++;
        }

        header("Location: bulk_krs?msg=bulk_reset&count=$count");
        exit;
    }
}

// Filter status
$filter = $_GET['filter'] ?? 'all';

// Ambil semua mahasiswa beserta status KRS-nya
$filter_where = "";
if ($filter === 'pending')  $filter_where = "AND kv.status = 'pending'";
elseif ($filter === 'approved') $filter_where = "AND kv.status = 'approved'";
elseif ($filter === 'rejected') $filter_where = "AND kv.status = 'rejected'";
elseif ($filter === 'empty')    $filter_where = "AND kv.status IS NULL";

$q_mhs = "
    SELECT
        m.id,
        m.nim,
        m.nama_lengkap,
        COALESCE(kv.status, 'belum_isi') AS status_krs,
        kv.tanggal_validasi,
        COALESCE((
            SELECT COUNT(*) FROM krs_temp kt WHERE kt.mahasiswa_id = m.id AND kt.periode_id = $periode_id
        ), 0) AS total_matkul_temp,
        COALESCE((
            SELECT SUM(mk.sks) FROM krs_temp kt
            JOIN mata_kuliah mk ON kt.matkul_id = mk.id
            WHERE kt.mahasiswa_id = m.id AND kt.periode_id = $periode_id
        ), 0) AS total_sks_temp,
        COALESCE((
            SELECT COUNT(*) FROM krs k WHERE k.mahasiswa_id = m.id AND k.periode_id = $periode_id
        ), 0) AS total_matkul_approved,
        COALESCE((
            SELECT SUM(mk.sks) FROM krs k
            JOIN mata_kuliah mk ON k.matkul_id = mk.id
            WHERE k.mahasiswa_id = m.id AND k.periode_id = $periode_id
        ), 0) AS total_sks_approved
    FROM mahasiswa m
    LEFT JOIN users u ON m.user_id = u.id
    LEFT JOIN krs_validasi kv ON (m.id = kv.mahasiswa_id AND kv.periode_id = $periode_id)
    WHERE " . getMahasiswaPureWhereClause('m', 'u', false) . " " . getKelasWhereClause('m', true) . " $filter_where
    ORDER BY m.nim ASC
";
$res_mhs = mysqli_query($koneksi, $q_mhs);
if (!$res_mhs) {
    die('Query error: ' . mysqli_error($koneksi));
}

$list_mhs = [];
$mhs_ids  = [];
while ($row = mysqli_fetch_assoc($res_mhs)) {
    $list_mhs[] = $row;
    $mhs_ids[]  = $row['id'];
}

// Hitung summary
$where_sum_raw = getKelasWhereClause('m', false);
$where_sum_sql = $where_sum_raw ? "AND $where_sum_raw" : "";
$q_summary = "
    SELECT
        COUNT(m.id) AS total_mhs,
        SUM(CASE WHEN kv.status = 'approved' THEN 1 ELSE 0 END) AS approved,
        SUM(CASE WHEN kv.status = 'pending' THEN 1 ELSE 0 END) AS pending,
        SUM(CASE WHEN kv.status = 'rejected' THEN 1 ELSE 0 END) AS rejected,
        SUM(CASE WHEN kv.status IS NULL THEN 1 ELSE 0 END) AS belum_isi
    FROM mahasiswa m
    LEFT JOIN users u ON m.user_id = u.id
    LEFT JOIN krs_validasi kv ON (m.id = kv.mahasiswa_id AND kv.periode_id = $periode_id)
    WHERE " . getMahasiswaPureWhereClause('m', 'u', false) . " $where_sum_sql
";
$res_summary = mysqli_query($koneksi, $q_summary);
$summary     = mysqli_fetch_assoc($res_summary);

// Ambil mata kuliah sesuai semester aktif saja
$res_matkul = mysqli_query($koneksi, "SELECT * FROM mata_kuliah WHERE semester = $semester_aktif ORDER BY kode_matkul ASC");
$all_matkul = [];
while ($m = mysqli_fetch_assoc($res_matkul)) {
    $all_matkul[] = $m;
}

// Ambil detail matkul per mahasiswa untuk modal preview
$krs_details_temp     = [];
$krs_details_approved = [];
if (!empty($mhs_ids)) {
    $ids_str = implode(',', $mhs_ids);

    // Detail dari krs_temp
    $q_dt = "
        SELECT kt.mahasiswa_id, mk.id as matkul_id, mk.kode_matkul, mk.nama_matkul, mk.sks, mk.semester
        FROM krs_temp kt
        JOIN mata_kuliah mk ON kt.matkul_id = mk.id
        WHERE kt.periode_id = $periode_id AND kt.mahasiswa_id IN ($ids_str)
        ORDER BY mk.semester ASC, mk.nama_matkul ASC
    ";
    $res_dt = mysqli_query($koneksi, $q_dt);
    while ($d = mysqli_fetch_assoc($res_dt)) {
        $krs_details_temp[$d['mahasiswa_id']][] = $d;
    }

    // Detail dari krs (approved)
    $q_da = "
        SELECT k.mahasiswa_id, mk.id as matkul_id, mk.kode_matkul, mk.nama_matkul, mk.sks, mk.semester
        FROM krs k
        JOIN mata_kuliah mk ON k.matkul_id = mk.id
        WHERE k.periode_id = $periode_id AND k.mahasiswa_id IN ($ids_str)
        ORDER BY mk.semester ASC, mk.nama_matkul ASC
    ";
    $res_da = mysqli_query($koneksi, $q_da);
    while ($d = mysqli_fetch_assoc($res_da)) {
        $krs_details_approved[$d['mahasiswa_id']][] = $d;
    }
}

$json_temp     = json_encode($krs_details_temp);
$json_approved = json_encode($krs_details_approved);
$json_matkul   = json_encode($all_matkul);
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <title>Manajemen KRS Massal - SIMAWA PTIK 2024</title>
    <?php include '../includes/head.php'; ?>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        /* SweetAlert Dark Mode Styling */
        .dark .swal2-popup {
            background-color: #0f172a !important;
            color: #f8fafc !important;
            border: 1px solid #1e293b !important;
            border-radius: 1.25rem !important;
        }

        .dark .swal2-title,
        .dark .swal2-html-container {
            color: #f8fafc !important;
        }

        .dark .swal2-input,
        .dark .swal2-select {
            background-color: #1e293b !important;
            color: #f8fafc !important;
            border-color: #334155 !important;
        }

        /* Checkbox row highlight */
        tr:has(.row-check:checked) {
            background-color: rgba(99, 102, 241, 0.08) !important;
        }
        .dark tr:has(.row-check:checked) {
            background-color: rgba(99, 102, 241, 0.15) !important;
        }

        /* Course card selection styling in modal */
        .matkul-card {
            transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
            cursor: pointer;
        }
        .matkul-card:hover {
            border-color: #818cf8;
            transform: translateY(-1px);
        }
        .matkul-card:has(input:checked) {
            background-color: #eef2ff;
            border-color: #6366f1;
            box-shadow: 0 4px 6px -1px rgba(99, 102, 241, 0.1);
        }
        .dark .matkul-card:has(input:checked) {
            background-color: rgba(99, 102, 241, 0.16);
            border-color: #818cf8;
        }

        /* Floating bulk action dock animation */
        #bulkDock {
            transition: all 0.3s cubic-bezier(0.16, 1, 0.3, 1);
            transform: translateY(120%) translateX(-50%);
            opacity: 0;
            pointer-events: none;
        }
        #bulkDock.visible {
            transform: translateY(0) translateX(-50%);
            opacity: 1;
            pointer-events: auto;
        }

        /* Custom scrollbar */
        .custom-scrollbar::-webkit-scrollbar {
            width: 6px;
            height: 6px;
        }
        .custom-scrollbar::-webkit-scrollbar-track {
            background: transparent;
        }
        .custom-scrollbar::-webkit-scrollbar-thumb {
            background: #cbd5e1;
            border-radius: 9999px;
        }
        .dark .custom-scrollbar::-webkit-scrollbar-thumb {
            background: #334155;
        }
    </style>
</head>

<body class="bg-slate-100 dark:bg-slate-950 transition-colors duration-300">
    <?php include '../includes/menu_db.php'; ?>

    <main class="ml-0 lg:ml-48 pt-20 p-6 flex flex-col min-h-screen">
        <div class="max-w-6xl mx-auto flex-grow w-full">

            <!-- ================================================
                 HEADER SECTION
                 ================================================ -->
            <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4 mb-8">
                <div>
                    <h3 class="text-2xl font-bold text-slate-800 dark:text-white tracking-tight text-center md:text-left">Manajemen KRS Massal</h3>
                    <p class="text-xs font-semibold text-slate-400 dark:text-slate-500 mt-1 text-center md:text-left">
                        Periode Aktif: <span class="text-indigo-600 dark:text-indigo-400 font-bold"><?= htmlspecialchars($periode_info) ?></span> &bull; Semester <?= $semester_aktif ?> &bull; Validasi & Penetapan Serentak
                    </p>
                </div>
                <div class="flex flex-wrap gap-2.5">
                    <a href="validasi_krs.php" class="bg-indigo-50 dark:bg-indigo-900/30 text-indigo-700 dark:text-indigo-400 border border-indigo-100 dark:border-indigo-800/50 px-4 py-2.5 rounded-xl hover:bg-indigo-100 dark:hover:bg-indigo-900/50 text-xs font-bold transition-all flex items-center gap-2 shadow-sm">
                        <i class="fas fa-user-check text-xs"></i> VALIDASI INDIVIDU
                    </a>
                    <button type="button" onclick="openSetKrsModal()" class="bg-indigo-600 hover:bg-indigo-700 text-white px-5 py-2.5 rounded-xl text-xs font-bold flex items-center gap-2 transition-all shadow-lg shadow-indigo-600/20 active:scale-95">
                        <i class="fas fa-magic text-xs"></i> SET KRS MASSAL
                    </button>
                </div>
            </div>

            <!-- ================================================
                 STATS / SUMMARY CARDS
                 ================================================ -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                <!-- Total Mahasiswa -->
                <div class="bg-white dark:bg-slate-900 p-5 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-sm hover:shadow-md transition-all group">
                    <div class="flex items-center gap-4">
                        <div class="w-12 h-12 rounded-xl bg-indigo-50 dark:bg-indigo-950/30 flex items-center justify-center text-indigo-600 dark:text-indigo-400 border border-indigo-100 dark:border-indigo-900/50 group-hover:bg-indigo-600 group-hover:text-white transition-all">
                            <i class="fas fa-users text-xl"></i>
                        </div>
                        <div>
                            <div class="text-[10px] font-bold text-slate-400 dark:text-slate-500 uppercase tracking-widest leading-none mb-1">Total Mahasiswa</div>
                            <div class="text-xl font-bold text-slate-800 dark:text-white leading-none"><?= $summary['total_mhs'] ?? 0 ?></div>
                            <div class="text-[10px] text-slate-400 dark:text-slate-500 font-medium mt-1">SIMAWA PTIK 2024</div>
                        </div>
                    </div>
                </div>

                <!-- Disetujui (Approved) -->
                <div class="bg-white dark:bg-slate-900 p-5 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-sm hover:shadow-md transition-all group">
                    <div class="flex items-center gap-4">
                        <div class="w-12 h-12 rounded-xl bg-emerald-50 dark:bg-emerald-950/30 flex items-center justify-center text-emerald-600 dark:text-emerald-400 border border-emerald-100 dark:border-emerald-900/50 group-hover:bg-emerald-600 group-hover:text-white transition-all">
                            <i class="fas fa-check-circle text-xl"></i>
                        </div>
                        <div>
                            <div class="text-[10px] font-bold text-slate-400 dark:text-slate-500 uppercase tracking-widest leading-none mb-1">Disetujui (Approved)</div>
                            <div class="text-xl font-bold text-emerald-600 dark:text-emerald-400 leading-none"><?= $summary['approved'] ?? 0 ?></div>
                            <div class="text-[10px] text-slate-400 dark:text-slate-500 font-medium mt-1">KSM Aktif</div>
                        </div>
                    </div>
                </div>

                <!-- Menunggu Validasi -->
                <div class="bg-white dark:bg-slate-900 p-5 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-sm hover:shadow-md transition-all group">
                    <div class="flex items-center gap-4">
                        <div class="w-12 h-12 rounded-xl bg-amber-50 dark:bg-amber-950/30 flex items-center justify-center text-amber-600 dark:text-amber-400 border border-amber-100 dark:border-amber-900/50 group-hover:bg-amber-600 group-hover:text-white transition-all">
                            <i class="fas fa-clock text-xl"></i>
                        </div>
                        <div>
                            <div class="text-[10px] font-bold text-slate-400 dark:text-slate-500 uppercase tracking-widest leading-none mb-1">Menunggu Validasi</div>
                            <div class="text-xl font-bold text-amber-600 dark:text-amber-400 leading-none"><?= $summary['pending'] ?? 0 ?></div>
                            <div class="text-[10px] text-slate-400 dark:text-slate-500 font-medium mt-1">Draft Diajukan</div>
                        </div>
                    </div>
                </div>

                <!-- Signature SIMAWA PTIK Gradient Hero Card -->
                <?php 
                $total_mhs_count = max($summary['total_mhs'] ?? 1, 1);
                $persen_approved = round((($summary['approved'] ?? 0) / $total_mhs_count) * 100);
                ?>
                <div class="bg-gradient-to-br from-indigo-900 to-slate-900 p-5 rounded-2xl shadow-xl shadow-indigo-900/20 border-0 group relative overflow-hidden">
                    <div class="relative z-10 flex items-center gap-4">
                        <div class="w-12 h-12 rounded-xl bg-white/10 flex items-center justify-center text-white border border-white/10">
                            <i class="fas fa-graduation-cap text-xl"></i>
                        </div>
                        <div>
                            <div class="text-[10px] font-bold text-indigo-300/60 uppercase tracking-widest leading-none mb-1">Progres KRS Kelas</div>
                            <div class="text-xl font-bold text-white leading-none"><?= $persen_approved ?>% Tuntas</div>
                            <div class="text-[10px] text-indigo-200/60 font-medium mt-1"><?= ($summary['belum_isi'] ?? 0) + ($summary['rejected'] ?? 0) ?> belum tuntas</div>
                        </div>
                    </div>
                    <div class="absolute top-0 right-0 w-24 h-24 bg-white/5 rounded-full -mr-12 -mt-12 transition-transform group-hover:scale-150"></div>
                </div>
            </div>

            <!-- ================================================
                 FILTER & SEARCH TOOLBAR CARD
                 ================================================ -->
            <div class="bg-white dark:bg-slate-900 p-4 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-sm relative overflow-hidden mb-6">
                <div class="absolute top-0 left-0 w-1 h-full bg-indigo-600"></div>
                <h3 class="text-xs font-bold text-slate-800 dark:text-white mb-3 flex items-center gap-2 uppercase tracking-widest">
                    <i class="fas fa-filter text-indigo-600"></i>
                    Filter & Pencarian Mahasiswa
                </h3>

                <div class="flex flex-col lg:flex-row items-stretch lg:items-center justify-between gap-3">
                    <!-- Filter Tabs -->
                    <div class="flex items-center gap-1.5 flex-wrap">
                        <?php
                        $filters = [
                            'all'      => ['label' => 'Semua',     'count' => $summary['total_mhs'] ?? 0, 'icon' => 'fa-list'],
                            'approved' => ['label' => 'Approved',  'count' => $summary['approved'] ?? 0,  'icon' => 'fa-check-circle'],
                            'pending'  => ['label' => 'Pending',   'count' => $summary['pending'] ?? 0,   'icon' => 'fa-clock'],
                            'rejected' => ['label' => 'Ditolak',   'count' => $summary['rejected'] ?? 0,  'icon' => 'fa-times-circle'],
                            'empty'    => ['label' => 'Belum Isi', 'count' => $summary['belum_isi'] ?? 0, 'icon' => 'fa-minus-circle'],
                        ];
                        foreach ($filters as $key => $f):
                            $isActive = $filter === $key;
                            $cls = $isActive
                                ? 'bg-indigo-600 text-white shadow-sm shadow-indigo-600/20'
                                : 'bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400 hover:bg-slate-200 dark:hover:bg-slate-700/80';
                        ?>
                            <a href="?filter=<?= $key ?>" class="<?= $cls ?> px-3 py-1.5 rounded-xl text-xs font-bold transition-all flex items-center gap-1.5">
                                <i class="fas <?= $f['icon'] ?> text-[10px]"></i>
                                <span><?= $f['label'] ?></span>
                                <span class="text-[10px] px-1.5 py-0.2 rounded-md <?= $isActive ? 'bg-white/20 text-white' : 'bg-slate-200 dark:bg-slate-700 text-slate-500 dark:text-slate-400' ?>">
                                    <?= $f['count'] ?>
                                </span>
                            </a>
                        <?php endforeach; ?>
                    </div>

                    <!-- Search Box & Select All -->
                    <div class="flex items-center gap-2 shrink-0">
                        <div class="relative w-full sm:w-64">
                            <i class="fas fa-search absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 dark:text-slate-500 text-[10px]"></i>
                            <input type="text" id="searchInput" placeholder="Cari nama atau NIM..."
                                class="w-full bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl pl-8 pr-3 py-2 text-xs text-slate-800 dark:text-slate-200 focus:ring-4 focus:ring-indigo-500/10 focus:border-indigo-500 transition-all shadow-sm font-semibold">
                        </div>
                        <button id="btnSelectAll" type="button"
                            class="text-xs font-bold text-slate-700 dark:text-slate-300 hover:text-indigo-600 dark:hover:text-indigo-400 bg-slate-50 dark:bg-slate-800 hover:bg-slate-100 dark:hover:bg-slate-700 border border-slate-200 dark:border-slate-700 px-3.5 py-2 rounded-xl transition-colors flex items-center gap-1.5 shrink-0 shadow-sm">
                            <i class="fas fa-check-double text-[11px]"></i>
                            <span>Pilih Semua</span>
                        </button>
                    </div>
                </div>
            </div>

            <!-- ================================================
                 MAIN TABLE CARD
                 ================================================ -->
            <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-sm overflow-hidden mb-8">
                <div class="p-4 border-b border-slate-100 dark:border-slate-800 flex justify-between items-center bg-slate-50/50 dark:bg-slate-900/50">
                    <h4 class="text-xs font-bold text-slate-800 dark:text-white uppercase tracking-widest flex items-center gap-2">
                        <i class="fas fa-table text-slate-400"></i>
                        Daftar Mahasiswa & Status KRS
                    </h4>
                    <span class="text-[10px] font-bold text-indigo-600 dark:text-indigo-400 uppercase bg-indigo-50 dark:bg-indigo-950/30 px-3 py-1 rounded-full border border-indigo-100 dark:border-indigo-950/50">
                        <?= count($list_mhs) ?> Mahasiswa
                    </span>
                </div>

                <!-- Table -->
                <div class="overflow-x-auto">
                    <table class="w-full text-left">
                        <thead>
                            <tr class="bg-slate-50 dark:bg-slate-950 border-b border-slate-200 dark:border-slate-800">
                                <th class="px-4 py-3.5 text-center w-12">
                                    <input type="checkbox" id="checkAll" class="w-4 h-4 rounded text-indigo-600 border-slate-300 dark:border-slate-600 focus:ring-indigo-500 cursor-pointer" title="Pilih Semua">
                                </th>
                                <th class="px-3 py-3.5 text-center text-[10px] font-bold text-slate-400 dark:text-slate-500 uppercase tracking-widest w-12">No</th>
                                <th class="px-4 py-3.5 text-left text-[10px] font-bold text-slate-400 dark:text-slate-500 uppercase tracking-widest">Mahasiswa</th>
                                <th class="px-4 py-3.5 text-center text-[10px] font-bold text-slate-400 dark:text-slate-500 uppercase tracking-widest">Draft KRS</th>
                                <th class="px-4 py-3.5 text-center text-[10px] font-bold text-slate-400 dark:text-slate-500 uppercase tracking-widest">KSM Aktif</th>
                                <th class="px-4 py-3.5 text-center text-[10px] font-bold text-slate-400 dark:text-slate-500 uppercase tracking-widest">Status</th>
                                <th class="px-4 py-3.5 text-center text-[10px] font-bold text-slate-400 dark:text-slate-500 uppercase tracking-widest">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-800" id="mahasiswaTableBody">
                            <?php if (empty($list_mhs)): ?>
                                <tr id="noDataRow">
                                    <td colspan="7" class="px-6 py-16 text-center">
                                        <div class="flex flex-col items-center justify-center gap-2.5 text-slate-400 dark:text-slate-500">
                                            <div class="w-14 h-14 rounded-2xl bg-slate-100 dark:bg-slate-800 flex items-center justify-center text-slate-300 dark:text-slate-600 mb-1">
                                                <i class="fas fa-inbox text-2xl"></i>
                                            </div>
                                            <h4 class="text-sm font-bold text-slate-700 dark:text-slate-300">Tidak ada mahasiswa</h4>
                                            <p class="text-xs max-w-sm">Tidak ditemukan data mahasiswa yang sesuai dengan filter ini.</p>
                                        </div>
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php
                                $no = 1;
                                foreach ($list_mhs as $m):
                                    $status = $m['status_krs'];
                                    $statusMap = [
                                        'approved'  => [
                                            'label' => 'Approved',
                                            'cls'   => 'bg-emerald-100 dark:bg-emerald-900/30 text-emerald-800 dark:text-emerald-400 border border-emerald-200 dark:border-emerald-800/50',
                                            'icon'  => 'fa-check-circle'
                                        ],
                                        'pending'   => [
                                            'label' => 'Pending',
                                            'cls'   => 'bg-amber-100 dark:bg-amber-900/30 text-amber-800 dark:text-amber-400 border border-amber-200 dark:border-amber-800/50',
                                            'icon'  => 'fa-clock'
                                        ],
                                        'rejected'  => [
                                            'label' => 'Ditolak',
                                            'cls'   => 'bg-rose-100 dark:bg-rose-900/30 text-rose-800 dark:text-rose-400 border border-rose-200 dark:border-rose-800/50',
                                            'icon'  => 'fa-times-circle'
                                        ],
                                        'belum_isi' => [
                                            'label' => 'Belum Isi',
                                            'cls'   => 'bg-slate-100 dark:bg-slate-800 text-slate-500 dark:text-slate-400 border border-slate-200 dark:border-slate-700',
                                            'icon'  => 'fa-minus-circle'
                                        ],
                                    ];
                                    $s = $statusMap[$status] ?? $statusMap['belum_isi'];
                                ?>
                                    <tr class="hover:bg-slate-50/80 dark:hover:bg-slate-800/50 transition-all" data-id="<?= $m['id'] ?>">
                                        <td class="px-4 py-3 text-center">
                                            <input type="checkbox" class="row-check w-4 h-4 rounded text-indigo-600 border-slate-300 dark:border-slate-600 focus:ring-indigo-500 cursor-pointer" value="<?= $m['id'] ?>">
                                        </td>
                                        <td class="px-3 py-3 text-[11px] font-bold text-slate-400 dark:text-slate-500 text-center"><?= $no++ ?></td>
                                        <td class="px-4 py-3">
                                            <div class="flex items-center gap-3">
                                                <div class="w-10 h-10 rounded-xl overflow-hidden border border-slate-200 dark:border-slate-700 shrink-0 bg-white dark:bg-slate-800 shadow-xs">
                                                    <img src="<?= profilePhotoUrl($m['nama_lengkap'], '../') ?>" alt="" class="w-full h-full object-cover">
                                                </div>
                                                <div class="min-w-0">
                                                    <div class="text-[11px] font-bold text-slate-800 dark:text-slate-100 truncate uppercase leading-tight"><?= htmlspecialchars($m['nama_lengkap']) ?></div>
                                                    <div class="text-[9px] font-mono text-slate-400 dark:text-slate-500 mt-0.5"><?= htmlspecialchars($m['nim']) ?></div>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="px-4 py-3 text-center">
                                            <?php if ($m['total_matkul_temp'] > 0): ?>
                                                <button type="button" onclick="showKrsDetail(<?= $m['id'] ?>, '<?= htmlspecialchars(addslashes($m['nama_lengkap'])) ?>', 'temp')"
                                                    class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-xs font-semibold bg-indigo-50 dark:bg-indigo-950/40 text-indigo-700 dark:text-indigo-400 border border-indigo-100 dark:border-indigo-900/50 hover:bg-indigo-100 dark:hover:bg-indigo-900/50 transition-colors shadow-xs">
                                                    <i class="fas fa-book-open text-[10px]"></i>
                                                    <span><?= $m['total_matkul_temp'] ?> MK / <?= $m['total_sks_temp'] ?> SKS</span>
                                                </button>
                                            <?php else: ?>
                                                <span class="text-xs text-slate-300 dark:text-slate-600 italic">—</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="px-4 py-3 text-center">
                                            <?php if ($m['total_matkul_approved'] > 0): ?>
                                                <button type="button" onclick="showKrsDetail(<?= $m['id'] ?>, '<?= htmlspecialchars(addslashes($m['nama_lengkap'])) ?>', 'approved')"
                                                    class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-xs font-semibold bg-emerald-50 dark:bg-emerald-950/40 text-emerald-700 dark:text-emerald-400 border border-emerald-100 dark:border-emerald-900/50 hover:bg-emerald-100 dark:hover:bg-emerald-900/50 transition-colors shadow-xs">
                                                    <i class="fas fa-check-circle text-[10px]"></i>
                                                    <span><?= $m['total_matkul_approved'] ?> MK / <?= $m['total_sks_approved'] ?> SKS</span>
                                                </button>
                                            <?php else: ?>
                                                <span class="text-xs text-slate-300 dark:text-slate-600 italic">—</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="px-4 py-3 text-center">
                                            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[10px] font-bold <?= $s['cls'] ?>">
                                                <i class="fas <?= $s['icon'] ?> text-[9px]"></i>
                                                <?= $s['label'] ?>
                                            </span>
                                        </td>
                                        <td class="px-4 py-3 text-center">
                                            <div class="flex items-center justify-center gap-1.5">
                                                <?php if ($status === 'pending' || $status === 'belum_isi' || $status === 'rejected'): ?>
                                                    <button type="button" onclick="quickApprove(<?= $m['id'] ?>, '<?= htmlspecialchars(addslashes($m['nama_lengkap'])) ?>')"
                                                        class="w-8 h-8 rounded-xl bg-emerald-50 dark:bg-emerald-950/30 text-emerald-600 hover:bg-emerald-500 hover:text-white dark:text-emerald-400 border border-emerald-200 dark:border-emerald-800/50 transition-all flex items-center justify-center shadow-xs active:scale-95"
                                                        title="Approve KRS">
                                                        <i class="fas fa-check text-[11px]"></i>
                                                    </button>
                                                <?php endif; ?>
                                                <?php if ($status !== 'rejected'): ?>
                                                    <button type="button" onclick="quickReject(<?= $m['id'] ?>, '<?= htmlspecialchars(addslashes($m['nama_lengkap'])) ?>')"
                                                        class="w-8 h-8 rounded-xl bg-rose-50 dark:bg-rose-950/30 text-rose-600 hover:bg-rose-500 hover:text-white dark:text-rose-400 border border-rose-200 dark:border-rose-800/50 transition-all flex items-center justify-center shadow-xs active:scale-95"
                                                        title="Tolak KRS">
                                                        <i class="fas fa-times text-[11px]"></i>
                                                    </button>
                                                <?php endif; ?>
                                                <button type="button" onclick="openSetKrsOneModal(<?= $m['id'] ?>, '<?= htmlspecialchars(addslashes($m['nama_lengkap'])) ?>')"
                                                    class="w-8 h-8 rounded-xl bg-indigo-50 dark:bg-indigo-950/30 text-indigo-600 hover:bg-indigo-500 hover:text-white dark:text-indigo-400 border border-indigo-200 dark:border-indigo-800/50 transition-all flex items-center justify-center shadow-xs active:scale-95"
                                                    title="Set KRS Manual">
                                                    <i class="fas fa-edit text-[11px]"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Footer Info -->
                <div class="p-4 border-t border-slate-100 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-900/50 flex flex-col sm:flex-row items-center justify-between gap-2">
                    <p class="text-[11px] text-slate-500 dark:text-slate-400 font-medium">
                        Menampilkan <strong><?= count($list_mhs) ?></strong> mahasiswa
                        <?= $filter !== 'all' ? '(filter: ' . htmlspecialchars($filter) . ')' : '' ?>
                    </p>
                    <p class="text-[11px] text-slate-400 dark:text-slate-500 flex items-center gap-1.5">
                        <i class="fas fa-info-circle text-indigo-500 text-xs"></i>
                        <span>Set KRS Massal akan otomatis disetujui (auto-approve) ke KSM.</span>
                    </p>
                </div>
            </div>

            <?php include '../includes/footer_db.php'; ?>
        </div>
    </main>

    <!-- ================================================
         FLOATING BULK ACTION DOCK (BOTTOM DOCKED)
         ================================================ -->
    <div id="bulkDock" class="fixed bottom-6 left-1/2 z-50 bg-slate-900/95 dark:bg-slate-900/95 backdrop-blur-md border border-slate-700/70 shadow-2xl rounded-2xl px-5 py-3.5 flex flex-wrap items-center justify-between gap-3 text-white max-w-[95vw] sm:max-w-2xl">
        <div class="flex items-center gap-2.5">
            <span class="w-6 h-6 rounded-full bg-indigo-600 flex items-center justify-center text-[10px] text-white">
                <i class="fas fa-check"></i>
            </span>
            <span id="bulkDockCount" class="text-xs font-bold text-white tracking-wide">0 dipilih</span>
        </div>

        <div class="flex items-center gap-2 flex-wrap">
            <button type="button" onclick="submitBulkAction('bulk_approve')"
                class="bg-emerald-600 hover:bg-emerald-500 text-white text-xs font-bold px-3 py-1.5 rounded-xl flex items-center gap-1.5 transition-all shadow-sm active:scale-95">
                <i class="fas fa-check-circle text-xs"></i> Approve
            </button>
            <button type="button" onclick="submitBulkAction('bulk_reject')"
                class="bg-rose-600 hover:bg-rose-500 text-white text-xs font-bold px-3 py-1.5 rounded-xl flex items-center gap-1.5 transition-all shadow-sm active:scale-95">
                <i class="fas fa-times-circle text-xs"></i> Tolak
            </button>
            <button type="button" onclick="openSetKrsModal()"
                class="bg-indigo-600 hover:bg-indigo-500 text-white text-xs font-bold px-3 py-1.5 rounded-xl flex items-center gap-1.5 transition-all shadow-sm active:scale-95">
                <i class="fas fa-magic text-xs"></i> Set KRS
            </button>
            <button type="button" onclick="submitBulkAction('bulk_reset')"
                class="bg-slate-700 hover:bg-slate-600 text-slate-200 text-xs font-bold px-3 py-1.5 rounded-xl flex items-center gap-1.5 transition-all shadow-sm active:scale-95">
                <i class="fas fa-trash-alt text-xs"></i> Reset
            </button>
            <button type="button" onclick="clearSelection()"
                class="text-slate-400 hover:text-white px-2 py-1.5 text-xs font-bold transition-colors ml-1" title="Batalkan Pilihan">
                <i class="fas fa-times"></i>
            </button>
        </div>
    </div>

    <!-- ================================================
         MODAL: SET KRS MASSAL / MANUAL
         ================================================ -->
    <div id="setKrsModal" class="fixed inset-0 z-50 hidden items-center justify-center p-4 bg-slate-950/70 backdrop-blur-sm">
        <div class="bg-white dark:bg-slate-900 rounded-2xl shadow-2xl border border-slate-200 dark:border-slate-800 w-full max-w-2xl max-h-[90vh] flex flex-col overflow-hidden animate-fade-in">
            <!-- Modal Header -->
            <div class="flex items-center justify-between px-6 py-4 border-b border-slate-200 dark:border-slate-800">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-indigo-50 dark:bg-indigo-950/40 text-indigo-600 dark:text-indigo-400 border border-indigo-100 dark:border-indigo-900/50 flex items-center justify-center shrink-0">
                        <i class="fas fa-layer-group text-base"></i>
                    </div>
                    <div>
                        <h3 class="text-base font-bold text-slate-800 dark:text-slate-100" id="modalKrsTitle">Set KRS Massal</h3>
                        <p id="modalKrsSubtitle" class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">
                            Pilih mata kuliah yang akan diterapkan ke mahasiswa terpilih.
                        </p>
                    </div>
                </div>
                <button type="button" onclick="closeSetKrsModal()" class="w-8 h-8 rounded-xl bg-slate-100 dark:bg-slate-800 text-slate-400 hover:text-slate-700 dark:hover:text-slate-200 flex items-center justify-center transition-colors">
                    <i class="fas fa-times text-xs"></i>
                </button>
            </div>

            <!-- SKS Counter & Meter Bar -->
            <div class="px-6 py-3.5 bg-indigo-50/70 dark:bg-indigo-950/30 border-b border-indigo-100 dark:border-indigo-900/30">
                <div class="flex items-center justify-between">
                    <div>
                        <span class="text-xs font-bold text-indigo-900 dark:text-indigo-200">Total Beban SKS:</span>
                        <span id="modalSksCount" class="text-base font-black text-indigo-700 dark:text-indigo-400 ml-1">0</span>
                        <span class="text-xs text-indigo-500 dark:text-indigo-400">/ 24 SKS</span>
                    </div>
                    <button type="button" onclick="toggleSelectAllMatkul()" class="text-[11px] font-bold text-indigo-700 dark:text-indigo-300 bg-white dark:bg-slate-800 border border-indigo-200 dark:border-indigo-800/80 px-2.5 py-1 rounded-lg hover:bg-indigo-50 dark:hover:bg-indigo-900/50 transition-all shadow-xs">
                        Pilih Semua Mata Kuliah
                    </button>
                </div>
                <!-- SKS Progress Indicator -->
                <div class="w-full bg-slate-200 dark:bg-slate-800 h-2 rounded-full overflow-hidden mt-2">
                    <div id="sksProgressBar" class="h-full bg-indigo-600 rounded-full transition-all duration-300" style="width: 0%"></div>
                </div>
                <div id="sksWarningMsg" class="text-[10px] font-bold text-rose-600 dark:text-rose-400 mt-1.5 hidden flex items-center gap-1">
                    <i class="fas fa-exclamation-circle"></i> Melebihi batas maksimal 24 SKS!
                </div>
            </div>

            <!-- Matkul List Container -->
            <div class="flex-1 overflow-y-auto px-6 py-4 custom-scrollbar" id="matkulListContainer">
                <?php if (empty($all_matkul)): ?>
                    <div class="flex flex-col items-center justify-center py-12 text-slate-400 dark:text-slate-600 gap-2">
                        <i class="fas fa-book-open text-3xl"></i>
                        <p class="text-xs font-semibold">Tidak ada mata kuliah untuk Semester <?= $semester_aktif ?></p>
                    </div>
                <?php else: ?>
                    <div class="flex items-center justify-between mb-3">
                        <span class="text-[10px] font-bold text-slate-400 dark:text-slate-500 uppercase tracking-widest">
                            Mata Kuliah Semester <?= $semester_aktif ?> (<?= count($all_matkul) ?> MK)
                        </span>
                        <span class="text-[10px] text-slate-400 dark:text-slate-500">Klik kartu untuk memilih</span>
                    </div>
                    <div class="space-y-2">
                        <?php foreach ($all_matkul as $mk): ?>
                            <label class="matkul-card flex items-center justify-between p-3 rounded-xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900/60 select-none">
                                <div class="flex items-center gap-3 min-w-0">
                                    <input type="checkbox" class="modal-matkul-cb w-4 h-4 rounded text-indigo-600 border-slate-300 dark:border-slate-600 focus:ring-indigo-500 cursor-pointer"
                                        value="<?= $mk['id'] ?>"
                                        data-sks="<?= $mk['sks'] ?>">
                                    <div class="min-w-0">
                                        <div class="text-xs font-bold text-slate-800 dark:text-slate-100 truncate"><?= htmlspecialchars($mk['nama_matkul']) ?></div>
                                        <div class="text-[10px] font-mono text-slate-400 dark:text-slate-500 mt-0.5"><?= htmlspecialchars($mk['kode_matkul']) ?></div>
                                    </div>
                                </div>
                                <span class="text-[10px] font-bold text-indigo-600 dark:text-indigo-400 bg-indigo-50 dark:bg-indigo-950/40 border border-indigo-100 dark:border-indigo-900/50 px-2.5 py-1 rounded-lg shrink-0">
                                    <?= $mk['sks'] ?> SKS
                                </span>
                            </label>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Modal Footer -->
            <div class="px-6 py-4 border-t border-slate-200 dark:border-slate-800 flex flex-col sm:flex-row items-center justify-between gap-3 bg-slate-50 dark:bg-slate-900/40">
                <p class="text-[11px] text-slate-500 dark:text-slate-400 flex items-center gap-1.5">
                    <i class="fas fa-bolt text-amber-500 text-xs"></i>
                    <span>KRS akan langsung disetujui dan berstatus <strong>Approved</strong>.</span>
                </p>
                <div class="flex items-center gap-2 w-full sm:w-auto justify-end">
                    <button type="button" onclick="closeSetKrsModal()" class="px-4 py-2 text-xs font-bold text-slate-600 dark:text-slate-400 hover:bg-slate-200 dark:hover:bg-slate-800 rounded-xl transition-colors">
                        Batal
                    </button>
                    <button type="button" onclick="submitSetKrs()" class="px-5 py-2 text-xs font-bold text-white bg-indigo-600 hover:bg-indigo-700 rounded-xl transition-all shadow-md shadow-indigo-600/20 active:scale-95 flex items-center gap-2">
                        <i class="fas fa-check text-xs"></i> Simpan & Approve
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Hidden Form for Actions -->
    <form id="bulkForm" method="POST" class="hidden">
        <input type="hidden" name="action" id="bulkAction">
        <div id="bulkMhsInputs"></div>
        <div id="bulkMatkulInputs"></div>
    </form>

    <?php include '../includes/script_db.php'; ?>
    <script>
        const krsDataTemp     = <?= $json_temp ?>;
        const krsDataApproved = <?= $json_approved ?>;
        const allMatkul       = <?= $json_matkul ?>;
        const allMahasiswaIds = <?= json_encode($mhs_ids) ?>;

        // ============================================================
        // Checkbox: Select All / Individual & Floating Dock
        // ============================================================
        const checkAll      = document.getElementById('checkAll');
        const bulkDock      = document.getElementById('bulkDock');
        const bulkDockCount = document.getElementById('bulkDockCount');

        function getCheckedIds() {
            return [...document.querySelectorAll('.row-check:checked')].map(cb => parseInt(cb.value));
        }

        function updateBulkDock() {
            const checked = getCheckedIds();
            if (checked.length > 0) {
                bulkDock.classList.add('visible');
                bulkDockCount.textContent = checked.length + ' mahasiswa dipilih';
            } else {
                bulkDock.classList.remove('visible');
            }
        }

        function clearSelection() {
            document.querySelectorAll('.row-check').forEach(cb => cb.checked = false);
            if (checkAll) {
                checkAll.checked = false;
                checkAll.indeterminate = false;
            }
            updateBulkDock();
        }

        document.querySelectorAll('.row-check').forEach(cb => {
            cb.addEventListener('change', () => {
                const allCbs = document.querySelectorAll('.row-check');
                if (checkAll) {
                    checkAll.checked = [...allCbs].every(c => c.checked);
                    checkAll.indeterminate = [...allCbs].some(c => c.checked) && !checkAll.checked;
                }
                updateBulkDock();
            });
        });

        if (checkAll) {
            checkAll.addEventListener('change', () => {
                document.querySelectorAll('.row-check').forEach(cb => {
                    cb.checked = checkAll.checked;
                });
                updateBulkDock();
            });
        }

        document.getElementById('btnSelectAll').addEventListener('click', () => {
            const allCbs = document.querySelectorAll('.row-check');
            const allChecked = [...allCbs].every(c => c.checked);
            allCbs.forEach(cb => cb.checked = !allChecked);
            if (checkAll) {
                checkAll.checked = !allChecked;
                checkAll.indeterminate = false;
            }
            updateBulkDock();
        });

        // ============================================================
        // Instant Live Search Filter
        // ============================================================
        const searchInput = document.getElementById('searchInput');
        if (searchInput) {
            searchInput.addEventListener('input', function () {
                const query = this.value.toLowerCase().trim();
                const rows = document.querySelectorAll('#mahasiswaTableBody tr[data-id]');
                let visibleCount = 0;

                rows.forEach(row => {
                    const text = row.textContent.toLowerCase();
                    const match = text.includes(query);
                    row.style.display = match ? '' : 'none';
                    if (match) visibleCount++;
                });

                let noDataRow = document.getElementById('noSearchMatchRow');
                if (visibleCount === 0 && rows.length > 0) {
                    if (!noDataRow) {
                        noDataRow = document.createElement('tr');
                        noDataRow.id = 'noSearchMatchRow';
                        noDataRow.innerHTML = `
                            <td colspan="7" class="px-6 py-12 text-center text-slate-400 dark:text-slate-500 text-xs">
                                <i class="fas fa-search text-2xl mb-2 block"></i>
                                Tidak ada mahasiswa yang cocok dengan pencarian "<strong>${query}</strong>"
                            </td>
                        `;
                        document.getElementById('mahasiswaTableBody').appendChild(noDataRow);
                    }
                } else if (noDataRow) {
                    noDataRow.remove();
                }
            });
        }

        // ============================================================
        // Bulk Actions
        // ============================================================
        async function submitBulkAction(action) {
            const ids = getCheckedIds();
            if (ids.length === 0) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Belum Ada Pilihan',
                    text: 'Silakan pilih minimal satu mahasiswa terlebih dahulu.',
                    confirmButtonColor: '#4f46e5'
                });
                return;
            }

            const labels = { bulk_approve: 'menyetujui', bulk_reject: 'menolak', bulk_reset: 'mereset' };
            const icons  = { bulk_approve: 'question', bulk_reject: 'warning', bulk_reset: 'warning' };
            const colors = { bulk_approve: '#10b981', bulk_reject: '#f43f5e', bulk_reset: '#64748b' };

            const result = await Swal.fire({
                title: 'Konfirmasi Aksi Massal',
                text: `Apakah Anda yakin ingin ${labels[action]} KRS untuk ${ids.length} mahasiswa terpilih?`,
                icon: icons[action],
                showCancelButton: true,
                confirmButtonColor: colors[action],
                cancelButtonColor: '#64748b',
                confirmButtonText: 'Ya, Lanjutkan!',
                cancelButtonText: 'Batal'
            });

            if (!result.isConfirmed) return;

            document.getElementById('bulkAction').value = action;
            const container = document.getElementById('bulkMhsInputs');
            container.innerHTML = '';
            ids.forEach(id => {
                const inp = document.createElement('input');
                inp.type  = 'hidden';
                inp.name  = 'mahasiswa_ids[]';
                inp.value = id;
                container.appendChild(inp);
            });
            document.getElementById('bulkMatkulInputs').innerHTML = '';
            document.getElementById('bulkForm').submit();
        }

        // ============================================================
        // Quick Approve / Reject (Single)
        // ============================================================
        async function quickApprove(mhsId, nama) {
            const result = await Swal.fire({
                title: 'Approve KRS?',
                html: `KRS <strong>${nama}</strong> akan disetujui dan langsung aktif ke KSM.`,
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#10b981',
                cancelButtonColor: '#64748b',
                confirmButtonText: 'Ya, Approve!',
                cancelButtonText: 'Batal'
            });
            if (!result.isConfirmed) return;

            document.getElementById('bulkAction').value = 'bulk_approve';
            document.getElementById('bulkMhsInputs').innerHTML =
                `<input type="hidden" name="mahasiswa_ids[]" value="${mhsId}">`;
            document.getElementById('bulkMatkulInputs').innerHTML = '';
            document.getElementById('bulkForm').submit();
        }

        async function quickReject(mhsId, nama) {
            const result = await Swal.fire({
                title: 'Tolak KRS?',
                html: `KRS <strong>${nama}</strong> akan ditolak.`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#f43f5e',
                cancelButtonColor: '#64748b',
                confirmButtonText: 'Ya, Tolak!',
                cancelButtonText: 'Batal'
            });
            if (!result.isConfirmed) return;

            document.getElementById('bulkAction').value = 'bulk_reject';
            document.getElementById('bulkMhsInputs').innerHTML =
                `<input type="hidden" name="mahasiswa_ids[]" value="${mhsId}">`;
            document.getElementById('bulkMatkulInputs').innerHTML = '';
            document.getElementById('bulkForm').submit();
        }

        // ============================================================
        // Modal: Set KRS
        // ============================================================
        let setKrsTargetIds = [];

        async function openSetKrsModal() {
            setKrsTargetIds = getCheckedIds();
            if (setKrsTargetIds.length === 0) {
                const promptAll = await Swal.fire({
                    title: 'Set KRS Massal',
                    text: `Belum ada mahasiswa yang dicentang. Terapkan KRS ke SEMUA (${allMahasiswaIds.length} mahasiswa)?`,
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonColor: '#4f46e5',
                    cancelButtonColor: '#64748b',
                    confirmButtonText: `Ya, Semua (${allMahasiswaIds.length} Mahasiswa)`,
                    cancelButtonText: 'Pilih Manual'
                });
                if (promptAll.isConfirmed) {
                    setKrsTargetIds = [...allMahasiswaIds];
                    document.querySelectorAll('.row-check').forEach(cb => cb.checked = true);
                    if (checkAll) checkAll.checked = true;
                    updateBulkDock();
                } else {
                    return;
                }
            }
            document.getElementById('modalKrsTitle').textContent = `Set KRS Massal (${setKrsTargetIds.length} Mahasiswa)`;
            document.getElementById('modalKrsSubtitle').textContent = 'Pilih mata kuliah yang akan diterapkan ke mahasiswa terpilih (auto-approve).';
            document.querySelectorAll('.modal-matkul-cb').forEach(cb => cb.checked = false);
            updateModalSks();
            document.getElementById('setKrsModal').classList.remove('hidden');
            document.getElementById('setKrsModal').classList.add('flex');
        }

        function openSetKrsOneModal(mhsId, nama) {
            setKrsTargetIds = [mhsId];
            document.getElementById('modalKrsTitle').textContent = `Set KRS — ${nama}`;
            document.getElementById('modalKrsSubtitle').textContent = 'Pilih mata kuliah yang akan diterapkan dan disetujui untuk mahasiswa ini.';
            const approvedData = krsDataApproved[mhsId] || [];
            const approvedIds  = approvedData.map(d => d.matkul_id);
            document.querySelectorAll('.modal-matkul-cb').forEach(cb => {
                cb.checked = approvedIds.includes(parseInt(cb.value));
            });
            updateModalSks();
            document.getElementById('setKrsModal').classList.remove('hidden');
            document.getElementById('setKrsModal').classList.add('flex');
        }

        function closeSetKrsModal() {
            document.getElementById('setKrsModal').classList.add('hidden');
            document.getElementById('setKrsModal').classList.remove('flex');
        }

        document.getElementById('setKrsModal').addEventListener('click', function(e) {
            if (e.target === this) closeSetKrsModal();
        });

        function updateModalSks() {
            let total = 0;
            document.querySelectorAll('.modal-matkul-cb:checked').forEach(cb => {
                total += parseInt(cb.dataset.sks);
            });
            const el = document.getElementById('modalSksCount');
            const bar = document.getElementById('sksProgressBar');
            const warning = document.getElementById('sksWarningMsg');

            el.textContent = total;
            const pct = Math.min(100, Math.round((total / 24) * 100));
            bar.style.width = pct + '%';

            if (total > 24) {
                el.className = 'text-base font-black text-rose-600 dark:text-rose-400 ml-1';
                bar.className = 'h-full bg-rose-500 rounded-full transition-all duration-300';
                warning.classList.remove('hidden');
            } else {
                el.className = 'text-base font-black text-indigo-700 dark:text-indigo-400 ml-1';
                bar.className = 'h-full bg-indigo-600 rounded-full transition-all duration-300';
                warning.classList.add('hidden');
            }
        }

        document.querySelectorAll('.modal-matkul-cb').forEach(cb => {
            cb.addEventListener('change', updateModalSks);
        });

        function toggleSelectAllMatkul() {
            const cbs = document.querySelectorAll('.modal-matkul-cb');
            const allCheck = [...cbs].every(c => c.checked);
            cbs.forEach(c => c.checked = !allCheck);
            updateModalSks();
        }

        async function submitSetKrs() {
            const selectedMatkul = [...document.querySelectorAll('.modal-matkul-cb:checked')].map(cb => parseInt(cb.value));
            if (selectedMatkul.length === 0) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Pilih Mata Kuliah',
                    text: 'Silakan pilih minimal satu mata kuliah terlebih dahulu.',
                    confirmButtonColor: '#4f46e5'
                });
                return;
            }

            let totalSks = 0;
            document.querySelectorAll('.modal-matkul-cb:checked').forEach(cb => totalSks += parseInt(cb.dataset.sks));
            if (totalSks > 24) {
                Swal.fire({
                    icon: 'error',
                    title: 'Batas SKS Terlampaui',
                    text: `Total SKS (${totalSks}) melebihi batas maksimal 24 SKS. Kurangi mata kuliah yang dipilih.`,
                    confirmButtonColor: '#4f46e5'
                });
                return;
            }

            const result = await Swal.fire({
                title: 'Konfirmasi Set KRS Massal',
                html: `
                    <div class="text-left text-xs text-slate-600 dark:text-slate-300 space-y-2">
                        <p>Akan diterapkan ke <strong>${setKrsTargetIds.length} mahasiswa</strong>.</p>
                        <div class="p-3 bg-indigo-50 dark:bg-indigo-950/40 rounded-xl border border-indigo-100 dark:border-indigo-900/40">
                            <span class="font-bold text-indigo-700 dark:text-indigo-300">Total: ${selectedMatkul.length} Mata Kuliah (${totalSks} SKS)</span>
                        </div>
                        <div class="p-3 bg-amber-50 dark:bg-amber-950/30 rounded-xl border border-amber-100 dark:border-amber-900/40 text-amber-800 dark:text-amber-300">
                            <i class="fas fa-bolt mr-1"></i> KRS akan langsung <strong>otomatis disetujui (Approved)</strong> dan masuk ke KSM aktif mahasiswa.
                        </div>
                    </div>
                `,
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#4f46e5',
                cancelButtonColor: '#64748b',
                confirmButtonText: 'Ya, Simpan & Approve!',
                cancelButtonText: 'Batal'
            });

            if (!result.isConfirmed) return;

            document.getElementById('bulkAction').value = 'bulk_set_krs';

            const mhsContainer = document.getElementById('bulkMhsInputs');
            mhsContainer.innerHTML = '';
            setKrsTargetIds.forEach(id => {
                const inp = document.createElement('input');
                inp.type  = 'hidden';
                inp.name  = 'mahasiswa_ids[]';
                inp.value = id;
                mhsContainer.appendChild(inp);
            });

            const mkContainer = document.getElementById('bulkMatkulInputs');
            mkContainer.innerHTML = '';
            selectedMatkul.forEach(id => {
                const inp = document.createElement('input');
                inp.type  = 'hidden';
                inp.name  = 'matkul_ids[]';
                inp.value = id;
                mkContainer.appendChild(inp);
            });

            closeSetKrsModal();
            document.getElementById('bulkForm').submit();
        }

        // ============================================================
        // Show KRS Detail Modal
        // ============================================================
        function showKrsDetail(mhsId, nama, type) {
            const data = type === 'temp' ? (krsDataTemp[mhsId] || []) : (krsDataApproved[mhsId] || []);
            const isDark = document.documentElement.classList.contains('dark');

            if (data.length === 0) {
                Swal.fire({
                    icon: 'info',
                    title: 'Tidak Ada Data',
                    text: 'Tidak ada mata kuliah pada daftar ini.',
                    confirmButtonColor: '#4f46e5'
                });
                return;
            }

            let totalSks = 0;
            let rows = '';
            data.forEach((d, i) => {
                totalSks += parseInt(d.sks);
                rows += `
                    <tr class="hover:bg-slate-50 dark:hover:bg-slate-800/40 transition-colors">
                        <td class="px-3.5 py-2.5 text-xs text-slate-400 text-center">${i + 1}</td>
                        <td class="px-3.5 py-2.5 text-xs font-mono font-semibold text-indigo-600 dark:text-indigo-400">${d.kode_matkul}</td>
                        <td class="px-3.5 py-2.5 text-xs font-bold text-slate-800 dark:text-slate-200">${d.nama_matkul}</td>
                        <td class="px-3.5 py-2.5 text-xs text-center font-bold text-slate-700 dark:text-slate-300">${d.sks}</td>
                    </tr>`;
            });

            Swal.fire({
                title: `<span class="text-base font-bold">${nama}</span>`,
                html: `
                    <div class="text-left mt-2">
                        <div class="mb-3 flex items-center justify-between">
                            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-xs font-bold ${type === 'temp' ? 'bg-amber-50 text-amber-700 border border-amber-200' : 'bg-emerald-50 text-emerald-700 border border-emerald-200'}">
                                <i class="fas ${type === 'temp' ? 'fa-clock' : 'fa-check-circle'}"></i>
                                ${type === 'temp' ? 'Draft KRS (Belum Approved)' : 'KSM Aktif (Approved)'}
                            </span>
                            <span class="text-xs font-bold text-slate-500">${data.length} Mata Kuliah</span>
                        </div>
                        <div class="overflow-x-auto rounded-xl border border-slate-200 dark:border-slate-800">
                            <table class="min-w-full divide-y divide-slate-200 dark:divide-slate-800 text-left">
                                <thead class="bg-indigo-900 text-white">
                                    <tr>
                                        <th class="px-3.5 py-2.5 text-xs font-bold text-center w-10">No</th>
                                        <th class="px-3.5 py-2.5 text-xs font-bold">Kode</th>
                                        <th class="px-3.5 py-2.5 text-xs font-bold">Mata Kuliah</th>
                                        <th class="px-3.5 py-2.5 text-xs font-bold text-center w-14">SKS</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100 dark:divide-slate-800 bg-white dark:bg-slate-900">
                                    ${rows}
                                </tbody>
                                <tfoot class="bg-slate-50 dark:bg-slate-800/80 border-t border-slate-200 dark:border-slate-800">
                                    <tr>
                                        <td colspan="3" class="px-3.5 py-2.5 text-xs font-bold text-slate-700 dark:text-slate-300 text-right">Total Beban SKS:</td>
                                        <td class="px-3.5 py-2.5 text-xs font-black text-indigo-600 dark:text-indigo-400 text-center">${totalSks}</td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>`,
                width: '580px',
                confirmButtonText: 'Tutup',
                confirmButtonColor: '#4f46e5',
                customClass: {
                    popup: 'rounded-2xl',
                    confirmButton: 'px-5 py-2 rounded-xl text-xs font-bold'
                }
            });
        }

        // ============================================================
        // Toast Alerts for Action Feedback
        // ============================================================
        <?php if (isset($_GET['msg'])): ?>
            (() => {
                const msg   = "<?= htmlspecialchars($_GET['msg']) ?>";
                const count = "<?= htmlspecialchars($_GET['count'] ?? '0') ?>";
                const sks   = "<?= htmlspecialchars($_GET['sks'] ?? '0') ?>";

                const toastMap = {
                    bulk_approved: { icon: 'success', title: 'Berhasil Approve!',      text: `${count} mahasiswa KRS-nya telah disetujui dan aktif sebagai KSM.` },
                    bulk_rejected: { icon: 'warning', title: 'KRS Ditolak',            text: `${count} mahasiswa KRS-nya telah ditolak.` },
                    bulk_set:      { icon: 'success', title: 'Set KRS Berhasil!',      text: `KRS ${count} mahasiswa berhasil diset (${sks} SKS) dan langsung diapprove.` },
                    bulk_reset:    { icon: 'info',    title: 'KRS Direset',            text: `Data KRS ${count} mahasiswa telah dihapus/direset.` },
                    no_selection:  { icon: 'warning', title: 'Belum Ada Pilihan',      text: 'Silakan pilih minimal satu mahasiswa terlebih dahulu.' },
                    no_matkul:     { icon: 'warning', title: 'Belum Ada Mata Kuliah',  text: 'Silakan pilih minimal satu mata kuliah.' },
                    sks_limit:     { icon: 'error',   title: 'Batas SKS Terlampaui',   text: `Total SKS (${sks}) melebihi batas maksimal 24 SKS.` },
                    no_periode:    { icon: 'error',   title: 'Tidak Ada Periode Aktif',text: 'Aktifkan periode perkuliahan terlebih dahulu.' },
                };

                const toast = toastMap[msg];
                if (toast) {
                    Swal.fire({
                        icon: toast.icon,
                        title: toast.title,
                        text: toast.text,
                        timer: 3500,
                        timerProgressBar: true,
                        confirmButtonColor: '#4f46e5'
                    });
                }
            })();
        <?php endif; ?>
    </script>
</body>

</html>
