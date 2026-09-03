<?php
session_start();
include '../koneksi.php';

// Cek Login & Role
if (!isset($_SESSION['user_id'])) {
    header("Location: ../index.php");
    exit;
}

$role = $_SESSION['role'];
if ($role !== 'Administrator Utama') {
    include '../includes/alert_role.php';
    exit;
}

$user_id    = $_SESSION['user_id'];
$nama_lengkap = $_SESSION['nama_lengkap'];
$foto       = $_SESSION['foto'];

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
        m.foto,
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
    LEFT JOIN krs_validasi kv ON (m.id = kv.mahasiswa_id AND kv.periode_id = $periode_id)
    WHERE 1=1 $filter_where
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
$q_summary = "
    SELECT
        COUNT(m.id) AS total_mhs,
        SUM(CASE WHEN kv.status = 'approved' THEN 1 ELSE 0 END) AS approved,
        SUM(CASE WHEN kv.status = 'pending' THEN 1 ELSE 0 END) AS pending,
        SUM(CASE WHEN kv.status = 'rejected' THEN 1 ELSE 0 END) AS rejected,
        SUM(CASE WHEN kv.status IS NULL THEN 1 ELSE 0 END) AS belum_isi
    FROM mahasiswa m
    LEFT JOIN krs_validasi kv ON (m.id = kv.mahasiswa_id AND kv.periode_id = $periode_id)
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
    <title>Manajemen KRS Massal - PTIK C 2024</title>
    <?php include '../includes/head.php'; ?>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        @media (max-width: 768px) {
            .mobile-menu-open {
                transform: translateX(0) !important;
            }
        }

        .status-badge {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            padding: 2px 8px;
            border-radius: 9999px;
            font-size: 10px;
            font-weight: 700;
            border-width: 1px;
        }

        /* Animated gradient for bulk action bar */
        .bulk-bar {
            background: linear-gradient(135deg, #1e1b4b 0%, #312e81 50%, #1e1b4b 100%);
            background-size: 200% 200%;
            animation: gradientShift 4s ease infinite;
        }

        @keyframes gradientShift {
            0% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
            100% { background-position: 0% 50%; }
        }

        /* Checkbox styling */
        .row-check:checked {
            accent-color: #4f46e5;
        }

        /* Modal overlay */
        #setKrsModal {
            backdrop-filter: blur(8px);
        }

        /* Matkul checkbox grid */
        .matkul-item:has(input:checked) {
            background-color: #eef2ff;
            border-color: #6366f1;
        }
        .dark .matkul-item:has(input:checked) {
            background-color: #1e1b4b40;
            border-color: #6366f1;
        }

        .matkul-item {
            transition: all 0.15s ease;
            cursor: pointer;
        }
        .matkul-item:hover {
            border-color: #a5b4fc;
        }

        /* Sticky bulk bar */
        #bulkBar {
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            max-height: 0;
            overflow: hidden;
        }
        #bulkBar.visible {
            max-height: 80px;
        }

        /* Table row highlight on select */
        tr:has(.row-check:checked) {
            background-color: #eef2ff !important;
        }
        .dark tr:has(.row-check:checked) {
            background-color: #1e1b4b30 !important;
        }
    </style>
</head>

<body class="bg-slate-100 dark:bg-slate-950 transition-colors">
    <?php include '../includes/menu_db.php'; ?>

    <main class="ml-0 lg:ml-48 pt-20 p-4 md:p-6 flex flex-col min-h-screen">

        <!-- ================================================
             HEADER CARD
             ================================================ -->
        <div class="bg-white dark:bg-slate-900/50 rounded-xl border border-slate-200 dark:border-slate-800 p-5 md:p-6 mb-4 shadow-sm">
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-3">
                <div>
                    <h1 class="text-2xl font-black text-slate-800 dark:text-slate-100 flex items-center gap-2">
                        <span class="w-8 h-8 rounded-lg bg-indigo-600 flex items-center justify-center shadow-lg shadow-indigo-200 dark:shadow-indigo-950/40">
                            <i class="fas fa-layer-group text-white text-sm"></i>
                        </span>
                        Manajemen KRS Massal
                    </h1>
                    <p class="text-xs text-slate-500 dark:text-slate-400 mt-1 font-medium">
                        Periode Aktif: <span class="text-indigo-600 dark:text-indigo-400 font-bold"><?= htmlspecialchars($periode_info) ?></span>
                        &bull; Semester <?= $semester_aktif ?>
                    </p>
                </div>
                <a href="validasi_krs.php" class="text-xs font-semibold text-indigo-600 dark:text-indigo-400 hover:text-indigo-800 dark:hover:text-indigo-300 flex items-center gap-1.5 bg-indigo-50 dark:bg-indigo-950/30 border border-indigo-200 dark:border-indigo-900/50 px-3 py-1.5 rounded-lg transition-colors">
                    <i class="fas fa-check-double"></i> Validasi Individu
                </a>
            </div>
        </div>

        <!-- ================================================
             SUMMARY CARDS
             ================================================ -->
        <div class="grid grid-cols-2 md:grid-cols-4 gap-3 mb-4">
            <?php
            $cards = [
                ['label' => 'Total Mahasiswa', 'val' => $summary['total_mhs'] ?? 0,  'icon' => 'fa-users',              'color' => 'indigo'],
                ['label' => 'Approved',         'val' => $summary['approved'] ?? 0,   'icon' => 'fa-check-circle',       'color' => 'green'],
                ['label' => 'Pending',          'val' => $summary['pending'] ?? 0,    'icon' => 'fa-clock',              'color' => 'yellow'],
                ['label' => 'Belum Isi',        'val' => $summary['belum_isi'] ?? 0,  'icon' => 'fa-exclamation-circle', 'color' => 'slate'],
            ];
            $colorMap = [
                'indigo' => 'bg-indigo-50 dark:bg-indigo-950/30 border-indigo-200 dark:border-indigo-900/50 text-indigo-700 dark:text-indigo-300',
                'green'  => 'bg-green-50 dark:bg-green-950/30 border-green-200 dark:border-green-900/50 text-green-700 dark:text-green-300',
                'yellow' => 'bg-yellow-50 dark:bg-yellow-950/30 border-yellow-200 dark:border-yellow-900/50 text-yellow-700 dark:text-yellow-300',
                'slate'  => 'bg-slate-50 dark:bg-slate-800/30 border-slate-200 dark:border-slate-700/50 text-slate-600 dark:text-slate-400',
            ];
            foreach ($cards as $c):
            ?>
                <div class="<?= $colorMap[$c['color']] ?> border rounded-xl p-3 flex items-center gap-3">
                    <div class="w-9 h-9 rounded-lg bg-white/60 dark:bg-black/20 flex items-center justify-center shrink-0">
                        <i class="fas <?= $c['icon'] ?> text-sm"></i>
                    </div>
                    <div>
                        <p class="text-[10px] font-bold uppercase tracking-wider opacity-70"><?= $c['label'] ?></p>
                        <p class="text-2xl font-black leading-none mt-0.5"><?= $c['val'] ?></p>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- ================================================
             MAIN TABLE CARD
             ================================================ -->
        <div class="bg-white dark:bg-slate-900/50 rounded-xl border border-slate-200 dark:border-slate-800 shadow-sm flex flex-col flex-grow">

            <!-- Toolbar -->
            <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3 p-4 border-b border-slate-200 dark:border-slate-800">
                <!-- Filter Tabs -->
                <div class="flex items-center gap-1 flex-wrap">
                    <?php
                    $filters = [
                        'all'      => ['label' => 'Semua', 'icon' => 'fa-list'],
                        'approved' => ['label' => 'Approved', 'icon' => 'fa-check'],
                        'pending'  => ['label' => 'Pending', 'icon' => 'fa-clock'],
                        'rejected' => ['label' => 'Ditolak', 'icon' => 'fa-times'],
                        'empty'    => ['label' => 'Belum Isi', 'icon' => 'fa-minus-circle'],
                    ];
                    foreach ($filters as $key => $f):
                        $isActive = $filter === $key;
                        $cls = $isActive
                            ? 'bg-indigo-600 text-white shadow-sm'
                            : 'bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400 hover:bg-slate-200 dark:hover:bg-slate-700';
                    ?>
                        <a href="?filter=<?= $key ?>" class="<?= $cls ?> px-2.5 py-1 rounded-lg text-[10px] font-bold transition-colors flex items-center gap-1">
                            <i class="fas <?= $f['icon'] ?>"></i> <?= $f['label'] ?>
                        </a>
                    <?php endforeach; ?>
                </div>

                <!-- Bulk action buttons -->
                <div class="flex items-center gap-2">
                    <button id="btnSelectAll" type="button"
                        class="text-[10px] font-bold text-slate-600 dark:text-slate-400 hover:text-indigo-600 dark:hover:text-indigo-400 border border-slate-200 dark:border-slate-700 px-2.5 py-1 rounded-lg hover:border-indigo-300 dark:hover:border-indigo-700 transition-colors flex items-center gap-1">
                        <i class="fas fa-check-double"></i> Pilih Semua
                    </button>
                    <span class="text-slate-300 dark:text-slate-700">|</span>
                    <button id="btnSetKrs" type="button" onclick="openSetKrsModal()"
                        class="text-[10px] font-bold text-white bg-indigo-600 hover:bg-indigo-700 px-3 py-1.5 rounded-lg transition-colors flex items-center gap-1 shadow-sm shadow-indigo-200 dark:shadow-indigo-950/40">
                        <i class="fas fa-layer-group"></i> Set KRS Massal
                    </button>
                </div>
            </div>

            <!-- Bulk Action Bar (appears when rows selected) -->
            <div id="bulkBar" class="bulk-bar px-4 overflow-hidden">
                <div class="flex items-center justify-between h-14">
                    <span id="bulkBarCount" class="text-white text-xs font-bold flex items-center gap-2">
                        <i class="fas fa-check-square text-indigo-300"></i>
                        <span>0 mahasiswa dipilih</span>
                    </span>
                    <div class="flex items-center gap-2">
                        <button type="button" onclick="submitBulkAction('bulk_approve')"
                            class="bg-green-500 hover:bg-green-400 text-white text-[10px] font-black px-3 py-1.5 rounded-lg flex items-center gap-1.5 transition-colors shadow-sm">
                            <i class="fas fa-check"></i> Approve Terpilih
                        </button>
                        <button type="button" onclick="submitBulkAction('bulk_reject')"
                            class="bg-red-500 hover:bg-red-400 text-white text-[10px] font-black px-3 py-1.5 rounded-lg flex items-center gap-1.5 transition-colors shadow-sm">
                            <i class="fas fa-times"></i> Reject Terpilih
                        </button>
                        <button type="button" onclick="submitBulkAction('bulk_reset')"
                            class="bg-slate-500 hover:bg-slate-400 text-white text-[10px] font-black px-3 py-1.5 rounded-lg flex items-center gap-1.5 transition-colors shadow-sm">
                            <i class="fas fa-trash-alt"></i> Reset KRS
                        </button>
                    </div>
                </div>
            </div>

            <!-- Table -->
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 dark:divide-slate-800">
                    <thead class="bg-indigo-950 dark:bg-indigo-950/80">
                        <tr>
                            <th class="px-3 py-3 text-center">
                                <input type="checkbox" id="checkAll" class="rounded border-slate-400" title="Pilih Semua">
                            </th>
                            <th class="px-3 py-3 text-left text-[10px] font-bold text-white uppercase tracking-wider">No</th>
                            <th class="px-3 py-3 text-left text-[10px] font-bold text-white uppercase tracking-wider">Mahasiswa</th>
                            <th class="px-3 py-3 text-center text-[10px] font-bold text-white uppercase tracking-wider">Draft KRS</th>
                            <th class="px-3 py-3 text-center text-[10px] font-bold text-white uppercase tracking-wider">KSM Aktif</th>
                            <th class="px-3 py-3 text-center text-[10px] font-bold text-white uppercase tracking-wider">Status</th>
                            <th class="px-3 py-3 text-center text-[10px] font-bold text-white uppercase tracking-wider">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white dark:bg-slate-900/30 divide-y divide-slate-100 dark:divide-slate-800/50" id="mahasiswaTableBody">
                        <?php if (empty($list_mhs)): ?>
                            <tr>
                                <td colspan="7" class="px-6 py-12 text-center">
                                    <div class="flex flex-col items-center gap-2">
                                        <i class="fas fa-inbox text-3xl text-slate-300 dark:text-slate-700"></i>
                                        <p class="text-sm text-slate-400 dark:text-slate-500 font-medium">Tidak ada data untuk filter ini.</p>
                                    </div>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php
                            $no = 1;
                            foreach ($list_mhs as $m):
                                $status    = $m['status_krs'];
                                $statusMap = [
                                    'approved'  => ['label' => 'Approved',  'cls' => 'bg-green-100 dark:bg-green-900/30 text-green-800 dark:text-green-400 border-green-200 dark:border-green-800/50',  'icon' => 'fa-check-circle'],
                                    'pending'   => ['label' => 'Pending',   'cls' => 'bg-yellow-100 dark:bg-yellow-900/30 text-yellow-800 dark:text-yellow-400 border-yellow-200 dark:border-yellow-800/50', 'icon' => 'fa-clock'],
                                    'rejected'  => ['label' => 'Ditolak',   'cls' => 'bg-red-100 dark:bg-red-900/30 text-red-800 dark:text-red-400 border-red-200 dark:border-red-800/50',  'icon' => 'fa-times-circle'],
                                    'belum_isi' => ['label' => 'Belum Isi', 'cls' => 'bg-slate-100 dark:bg-slate-800/50 text-slate-600 dark:text-slate-400 border-slate-200 dark:border-slate-700', 'icon' => 'fa-minus-circle'],
                                ];
                                $s = $statusMap[$status] ?? $statusMap['belum_isi'];
                            ?>
                                <tr class="hover:bg-indigo-50/40 dark:hover:bg-indigo-900/10 transition-colors" data-id="<?= $m['id'] ?>">
                                    <td class="px-3 py-2.5 text-center">
                                        <input type="checkbox" class="row-check rounded border-slate-300 dark:border-slate-600" value="<?= $m['id'] ?>">
                                    </td>
                                    <td class="px-3 py-2.5 text-xs text-slate-500 dark:text-slate-500 font-medium"><?= $no++ ?></td>
                                    <td class="px-3 py-2.5">
                                        <div class="flex items-center gap-2.5">
                                            <img src="<?= profilePhotoUrl($m['foto'], '../') ?>" alt="" class="w-8 h-8 rounded-full object-cover border border-slate-200 dark:border-slate-700 shadow-sm shrink-0">
                                            <div>
                                                <div class="text-xs font-bold text-slate-800 dark:text-slate-200"><?= htmlspecialchars($m['nama_lengkap']) ?></div>
                                                <div class="text-[10px] font-mono text-slate-400 dark:text-slate-500"><?= htmlspecialchars($m['nim']) ?></div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-3 py-2.5 text-center">
                                        <?php if ($m['total_matkul_temp'] > 0): ?>
                                            <button onclick="showKrsDetail(<?= $m['id'] ?>, '<?= htmlspecialchars(addslashes($m['nama_lengkap'])) ?>', 'temp')"
                                                class="text-[10px] font-bold text-indigo-600 dark:text-indigo-400 hover:underline flex items-center gap-1 mx-auto">
                                                <i class="fas fa-book-open"></i>
                                                <?= $m['total_matkul_temp'] ?> MK / <?= $m['total_sks_temp'] ?> SKS
                                            </button>
                                        <?php else: ?>
                                            <span class="text-[10px] text-slate-400 dark:text-slate-600 italic">—</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="px-3 py-2.5 text-center">
                                        <?php if ($m['total_matkul_approved'] > 0): ?>
                                            <button onclick="showKrsDetail(<?= $m['id'] ?>, '<?= htmlspecialchars(addslashes($m['nama_lengkap'])) ?>', 'approved')"
                                                class="text-[10px] font-bold text-green-600 dark:text-green-400 hover:underline flex items-center gap-1 mx-auto">
                                                <i class="fas fa-check-circle"></i>
                                                <?= $m['total_matkul_approved'] ?> MK / <?= $m['total_sks_approved'] ?> SKS
                                            </button>
                                        <?php else: ?>
                                            <span class="text-[10px] text-slate-400 dark:text-slate-600 italic">—</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="px-3 py-2.5 text-center">
                                        <span class="status-badge <?= $s['cls'] ?> border">
                                            <i class="fas <?= $s['icon'] ?>"></i>
                                            <?= $s['label'] ?>
                                        </span>
                                    </td>
                                    <td class="px-3 py-2.5 text-center">
                                        <div class="flex items-center justify-center gap-1">
                                            <?php if ($status === 'pending' || $status === 'belum_isi' || $status === 'rejected'): ?>
                                                <button onclick="quickApprove(<?= $m['id'] ?>, '<?= htmlspecialchars(addslashes($m['nama_lengkap'])) ?>')"
                                                    class="w-7 h-7 rounded-lg bg-green-50 dark:bg-green-900/20 text-green-600 dark:text-green-400 hover:bg-green-100 dark:hover:bg-green-900/40 border border-green-200 dark:border-green-900/30 transition-colors flex items-center justify-center"
                                                    title="Approve">
                                                    <i class="fas fa-check text-[10px]"></i>
                                                </button>
                                            <?php endif; ?>
                                            <?php if ($status !== 'rejected'): ?>
                                                <button onclick="quickReject(<?= $m['id'] ?>, '<?= htmlspecialchars(addslashes($m['nama_lengkap'])) ?>')"
                                                    class="w-7 h-7 rounded-lg bg-red-50 dark:bg-red-900/20 text-red-600 dark:text-red-400 hover:bg-red-100 dark:hover:bg-red-900/40 border border-red-200 dark:border-red-900/30 transition-colors flex items-center justify-center"
                                                    title="Reject">
                                                    <i class="fas fa-times text-[10px]"></i>
                                                </button>
                                            <?php endif; ?>
                                            <button onclick="openSetKrsOneModal(<?= $m['id'] ?>, '<?= htmlspecialchars(addslashes($m['nama_lengkap'])) ?>')"
                                                class="w-7 h-7 rounded-lg bg-indigo-50 dark:bg-indigo-900/20 text-indigo-600 dark:text-indigo-400 hover:bg-indigo-100 dark:hover:bg-indigo-900/40 border border-indigo-200 dark:border-indigo-900/30 transition-colors flex items-center justify-center"
                                                title="Set KRS Manual">
                                                <i class="fas fa-edit text-[10px]"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- Footer info -->
            <div class="px-4 py-3 border-t border-slate-100 dark:border-slate-800 flex items-center justify-between">
                <p class="text-[10px] text-slate-400 dark:text-slate-600 font-medium">
                    Menampilkan <strong><?= count($list_mhs) ?></strong> mahasiswa
                    <?= $filter !== 'all' ? '(filter: ' . htmlspecialchars($filter) . ')' : '' ?>
                </p>
                <p class="text-[10px] text-slate-400 dark:text-slate-600">
                    <i class="fas fa-info-circle mr-1"></i> Set KRS Massal akan langsung auto-approve
                </p>
            </div>
        </div>

    </main>
    <?php include '../includes/footer_db.php'; ?>

    <!-- ================================================
         MODAL: SET KRS MASSAL
         ================================================ -->
    <div id="setKrsModal" class="fixed inset-0 z-50 hidden items-center justify-center p-4 bg-slate-900/70">
        <div class="bg-white dark:bg-slate-900 rounded-2xl shadow-2xl border border-slate-200 dark:border-slate-700 w-full max-w-2xl max-h-[90vh] flex flex-col">
            <!-- Modal Header -->
            <div class="flex items-center justify-between px-6 py-4 border-b border-slate-200 dark:border-slate-700">
                <div>
                    <h3 class="text-base font-black text-slate-800 dark:text-slate-100 flex items-center gap-2">
                        <i class="fas fa-layer-group text-indigo-500"></i>
                        <span id="modalKrsTitle">Set KRS Massal</span>
                    </h3>
                    <p id="modalKrsSubtitle" class="text-[10px] text-slate-500 dark:text-slate-400 mt-0.5 font-medium">
                        Pilih mata kuliah yang akan diterapkan ke mahasiswa terpilih
                    </p>
                </div>
                <button onclick="closeSetKrsModal()" class="w-8 h-8 rounded-lg bg-slate-100 dark:bg-slate-800 text-slate-500 hover:text-slate-800 dark:hover:text-slate-200 flex items-center justify-center transition-colors">
                    <i class="fas fa-times text-sm"></i>
                </button>
            </div>

            <!-- SKS Counter -->
            <div class="px-6 py-3 bg-indigo-50 dark:bg-indigo-950/30 border-b border-indigo-100 dark:border-indigo-900/30 flex items-center justify-between">
                <p class="text-[10px] text-indigo-700 dark:text-indigo-300 font-semibold">Total SKS Terpilih</p>
                <div class="flex items-center gap-2">
                    <span id="modalSksCount" class="text-lg font-black text-indigo-900 dark:text-indigo-100">0</span>
                    <span class="text-[10px] text-indigo-500 dark:text-indigo-400">/ 24 SKS</span>
                    <button type="button" onclick="toggleSelectAllMatkul()" class="text-[10px] font-bold text-indigo-600 dark:text-indigo-400 border border-indigo-200 dark:border-indigo-800 px-2 py-0.5 rounded-lg hover:bg-indigo-100 dark:hover:bg-indigo-900/50 transition-colors ml-2">
                        Pilih Semua
                    </button>
                </div>
            </div>

            <!-- Matkul List — Semester Aktif -->
            <div class="flex-1 overflow-y-auto px-6 py-4" id="matkulListContainer">
                <?php if (empty($all_matkul)): ?>
                    <div class="flex flex-col items-center justify-center py-10 text-slate-400 dark:text-slate-600 gap-2">
                        <i class="fas fa-book text-3xl"></i>
                        <p class="text-xs font-medium">Tidak ada mata kuliah untuk Semester <?= $semester_aktif ?></p>
                    </div>
                <?php else: ?>
                    <p class="text-[10px] font-black text-slate-400 dark:text-slate-600 uppercase tracking-widest mb-3">
                        Semester <?= $semester_aktif ?> &mdash; <?= count($all_matkul) ?> Mata Kuliah
                    </p>
                    <div class="space-y-1.5">
                        <?php foreach ($all_matkul as $mk): ?>
                            <label class="matkul-item flex items-center gap-3 p-2.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800/30">
                                <input type="checkbox" class="modal-matkul-cb rounded border-slate-300 dark:border-slate-600 text-indigo-600"
                                    value="<?= $mk['id'] ?>"
                                    data-sks="<?= $mk['sks'] ?>">
                                <div class="flex-1 min-w-0">
                                    <div class="text-xs font-bold text-slate-800 dark:text-slate-200 truncate"><?= htmlspecialchars($mk['nama_matkul']) ?></div>
                                    <div class="text-[10px] text-slate-400 dark:text-slate-500 font-mono"><?= htmlspecialchars($mk['kode_matkul']) ?></div>
                                </div>
                                <span class="text-[10px] font-black text-indigo-600 dark:text-indigo-400 shrink-0 bg-indigo-50 dark:bg-indigo-950/30 px-1.5 py-0.5 rounded-md border border-indigo-100 dark:border-indigo-900/50">
                                    <?= $mk['sks'] ?> SKS
                                </span>
                            </label>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>


            <!-- Modal Footer -->
            <div class="px-6 py-4 border-t border-slate-200 dark:border-slate-700 flex items-center justify-between gap-3">
                <p class="text-[10px] text-slate-400 dark:text-slate-500 italic flex items-center gap-1">
                    <i class="fas fa-bolt text-yellow-500"></i>
                    KRS akan langsung di-approve setelah disimpan
                </p>
                <div class="flex gap-2">
                    <button onclick="closeSetKrsModal()" class="px-4 py-2 text-xs font-bold text-slate-600 dark:text-slate-400 bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 rounded-xl transition-colors">
                        Batal
                    </button>
                    <button onclick="submitSetKrs()" class="px-5 py-2 text-xs font-black text-white bg-indigo-600 hover:bg-indigo-700 rounded-xl transition-colors flex items-center gap-2 shadow-sm shadow-indigo-200 dark:shadow-indigo-950/40">
                        <i class="fas fa-save"></i> Simpan & Approve
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Hidden Forms -->
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
        // Checkbox: Select All / Individual
        // ============================================================
        const checkAll    = document.getElementById('checkAll');
        const bulkBar     = document.getElementById('bulkBar');
        const bulkBarCount = document.getElementById('bulkBarCount').querySelector('span');

        function getCheckedIds() {
            return [...document.querySelectorAll('.row-check:checked')].map(cb => parseInt(cb.value));
        }

        function updateBulkBar() {
            const checked = getCheckedIds();
            if (checked.length > 0) {
                bulkBar.classList.add('visible');
                bulkBarCount.textContent = checked.length + ' mahasiswa dipilih';
            } else {
                bulkBar.classList.remove('visible');
            }
        }

        document.querySelectorAll('.row-check').forEach(cb => {
            cb.addEventListener('change', () => {
                const allCbs = document.querySelectorAll('.row-check');
                checkAll.checked = [...allCbs].every(c => c.checked);
                checkAll.indeterminate = [...allCbs].some(c => c.checked) && !checkAll.checked;
                updateBulkBar();
            });
        });

        checkAll.addEventListener('change', () => {
            document.querySelectorAll('.row-check').forEach(cb => {
                cb.checked = checkAll.checked;
            });
            updateBulkBar();
        });

        document.getElementById('btnSelectAll').addEventListener('click', () => {
            const allCbs    = document.querySelectorAll('.row-check');
            const allChecked = [...allCbs].every(c => c.checked);
            allCbs.forEach(cb => cb.checked = !allChecked);
            checkAll.checked = !allChecked;
            checkAll.indeterminate = false;
            updateBulkBar();
        });

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
                    confirmButtonColor: '#3730a3'
                });
                return;
            }

            const labels = { bulk_approve: 'menyetujui', bulk_reject: 'menolak', bulk_reset: 'mereset' };
            const icons  = { bulk_approve: 'question', bulk_reject: 'warning', bulk_reset: 'warning' };
            const colors = { bulk_approve: '#166534', bulk_reject: '#991b1b', bulk_reset: '#64748b' };

            const result = await Swal.fire({
                title: 'Konfirmasi Aksi Massal',
                text: `Apakah Anda yakin ingin ${labels[action]} KRS untuk ${ids.length} mahasiswa terpilih?`,
                icon: icons[action],
                showCancelButton: true,
                confirmButtonColor: colors[action],
                cancelButtonColor: '#4b5563',
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
                html: `KRS <strong>${nama}</strong> akan disetujui dan sinkronisasi ke KSM.`,
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#166534',
                cancelButtonColor: '#4b5563',
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
                title: 'Reject KRS?',
                html: `KRS <strong>${nama}</strong> akan ditolak.`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#991b1b',
                cancelButtonColor: '#4b5563',
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
        let setKrsTargetIds = []; // array of mhs IDs

        async function openSetKrsModal() {
            setKrsTargetIds = getCheckedIds();
            if (setKrsTargetIds.length === 0) {
                const promptAll = await Swal.fire({
                    title: 'Set KRS Massal',
                    text: `Belum ada mahasiswa yang dicentang. Apakah Anda ingin menerapkan KRS ke SEMUA ${allMahasiswaIds.length} mahasiswa?`,
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonColor: '#3730a3',
                    cancelButtonColor: '#64748b',
                    confirmButtonText: `Ya, Semua ${allMahasiswaIds.length} Mahasiswa`,
                    cancelButtonText: 'Saya Pilih Manual'
                });
                if (promptAll.isConfirmed) {
                    setKrsTargetIds = [...allMahasiswaIds];
                    document.querySelectorAll('.row-check').forEach(cb => cb.checked = true);
                    if (checkAll) checkAll.checked = true;
                    updateBulkBar();
                } else {
                    return;
                }
            }
            document.getElementById('modalKrsTitle').textContent = `Set KRS Massal — ${setKrsTargetIds.length} Mahasiswa`;
            document.getElementById('modalKrsSubtitle').textContent = 'Pilih mata kuliah yang akan diterapkan ke semua mahasiswa terpilih';
            // Reset checkboxes
            document.querySelectorAll('.modal-matkul-cb').forEach(cb => cb.checked = false);
            updateModalSks();
            document.getElementById('setKrsModal').classList.remove('hidden');
            document.getElementById('setKrsModal').classList.add('flex');
        }

        function openSetKrsOneModal(mhsId, nama) {
            setKrsTargetIds = [mhsId];
            document.getElementById('modalKrsTitle').textContent = `Set KRS — ${nama}`;
            document.getElementById('modalKrsSubtitle').textContent = 'Pilih mata kuliah yang akan diterapkan (auto-approve)';
            // Pre-fill dengan KRS aktif jika ada
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

        // Close on backdrop click
        document.getElementById('setKrsModal').addEventListener('click', function(e) {
            if (e.target === this) closeSetKrsModal();
        });

        // SKS Counter for modal
        function updateModalSks() {
            let total = 0;
            document.querySelectorAll('.modal-matkul-cb:checked').forEach(cb => {
                total += parseInt(cb.dataset.sks);
            });
            const el = document.getElementById('modalSksCount');
            el.textContent = total;
            el.className = total > 24
                ? 'text-lg font-black text-red-600 dark:text-red-400 animate-bounce'
                : 'text-lg font-black text-indigo-900 dark:text-indigo-100';
        }

        document.querySelectorAll('.modal-matkul-cb').forEach(cb => {
            cb.addEventListener('change', updateModalSks);
        });

        function toggleSelectAllMatkul() {
            const cbs      = document.querySelectorAll('.modal-matkul-cb');
            const allCheck = [...cbs].every(c => c.checked);
            cbs.forEach(c => c.checked = !allCheck);
            updateModalSks();
        }

        async function submitSetKrs() {
            const selectedMatkul = [...document.querySelectorAll('.modal-matkul-cb:checked')].map(cb => parseInt(cb.value));
            if (selectedMatkul.length === 0) {
                Swal.fire({ icon: 'warning', title: 'Pilih Mata Kuliah', text: 'Pilih minimal satu mata kuliah.', confirmButtonColor: '#3730a3' });
                return;
            }

            // Check SKS
            let totalSks = 0;
            document.querySelectorAll('.modal-matkul-cb:checked').forEach(cb => totalSks += parseInt(cb.dataset.sks));
            if (totalSks > 24) {
                Swal.fire({ icon: 'error', title: 'SKS Melebihi Batas', text: `Total SKS (${totalSks}) melebihi batas 24 SKS.`, confirmButtonColor: '#3730a3' });
                return;
            }

            const matkulNames = [...document.querySelectorAll('.modal-matkul-cb:checked')]
                .map(cb => cb.closest('label').querySelector('.font-bold').textContent.trim())
                .slice(0, 3)
                .join(', ') + (selectedMatkul.length > 3 ? ` ...+${selectedMatkul.length - 3} lagi` : '');

            const result = await Swal.fire({
                title: 'Konfirmasi Set KRS Massal',
                html: `
                    <div class="text-left text-sm">
                        <p class="mb-2">Akan diterapkan ke <strong>${setKrsTargetIds.length} mahasiswa</strong>:</p>
                        <p class="text-xs text-slate-500 mb-1">${matkulNames}</p>
                        <p class="mt-3 text-xs font-semibold text-indigo-700">Total: ${selectedMatkul.length} MK / ${totalSks} SKS</p>
                        <div class="mt-3 p-2.5 bg-yellow-50 rounded-lg border border-yellow-200 text-xs text-yellow-800">
                            <i class="fas fa-bolt mr-1"></i> KRS akan langsung <strong>auto-approve</strong> dan masuk ke KSM mahasiswa.
                        </div>
                    </div>
                `,
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#3730a3',
                cancelButtonColor: '#4b5563',
                confirmButtonText: 'Ya, Simpan & Approve!',
                cancelButtonText: 'Batal',
                width: '480px'
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
                Swal.fire('Info', 'Tidak ada data mata kuliah.', 'info');
                return;
            }

            let totalSks = 0;
            let rows = '';
            data.forEach((d, i) => {
                totalSks += parseInt(d.sks);
                rows += `
                    <tr>
                        <td class="px-3 py-2 text-xs text-slate-500">${i + 1}</td>
                        <td class="px-3 py-2 text-xs font-mono text-slate-600 dark:text-slate-400">${d.kode_matkul}</td>
                        <td class="px-3 py-2 text-xs font-bold text-slate-800 dark:text-slate-200">${d.nama_matkul}</td>
                        <td class="px-3 py-2 text-xs text-center font-bold text-indigo-700 dark:text-indigo-400">${d.sks}</td>
                    </tr>`;
            });

            Swal.fire({
                title: `<strong>${nama}</strong>`,
                html: `
                    <p class="text-xs text-slate-500 mb-3">${type === 'temp' ? '📋 Draft KRS (Pending)' : '✅ KSM Aktif (Approved)'}</p>
                    <div class="overflow-x-auto">
                        <table class="min-w-full border border-slate-200 dark:border-slate-700 rounded-lg overflow-hidden text-left">
                            <thead class="bg-indigo-900 text-white">
                                <tr>
                                    <th class="px-3 py-2 text-xs font-bold">No</th>
                                    <th class="px-3 py-2 text-xs font-bold">Kode</th>
                                    <th class="px-3 py-2 text-xs font-bold">Mata Kuliah</th>
                                    <th class="px-3 py-2 text-xs font-bold text-center">SKS</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 dark:divide-slate-700 bg-white dark:bg-slate-900">
                                ${rows}
                            </tbody>
                            <tfoot class="bg-indigo-50 dark:bg-indigo-950/50">
                                <tr>
                                    <td colspan="3" class="px-3 py-2 text-xs font-bold text-indigo-900 dark:text-indigo-300 text-right">Total SKS</td>
                                    <td class="px-3 py-2 text-xs font-black text-indigo-900 dark:text-indigo-300 text-center">${totalSks}</td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>`,
                width: '560px',
                confirmButtonText: 'Tutup',
                confirmButtonColor: '#3730a3',
                background: isDark ? '#0f172a' : '#ffffff',
                color: isDark ? '#f1f5f9' : '#1e293b'
            });
        }

        // ============================================================
        // Toast Messages
        // ============================================================
        <?php if (isset($_GET['msg'])): ?>
            (() => {
                const msg   = "<?= htmlspecialchars($_GET['msg']) ?>";
                const count = "<?= htmlspecialchars($_GET['count'] ?? '0') ?>";
                const sks   = "<?= htmlspecialchars($_GET['sks'] ?? '0') ?>";
                const isDark = document.documentElement.classList.contains('dark');

                const toastMap = {
                    bulk_approved: { icon: 'success', title: '✅ Berhasil Approve!',         text: `${count} mahasiswa telah disetujui dan KRS-nya aktif sebagai KSM.` },
                    bulk_rejected: { icon: 'warning', title: '❌ KRS Ditolak',               text: `${count} mahasiswa KRS-nya telah ditolak.` },
                    bulk_set:      { icon: 'success', title: '🎉 Set KRS Massal Berhasil!',  text: `KRS ${count} mahasiswa berhasil diset (${sks} SKS) dan langsung diapprove.` },
                    bulk_reset:    { icon: 'info',    title: '🗑️ KRS Direset',               text: `${count} mahasiswa KRS-nya telah dihapus.` },
                    no_selection:  { icon: 'warning', title: 'Belum Ada Pilihan',            text: 'Pilih minimal satu mahasiswa.' },
                    no_matkul:     { icon: 'warning', title: 'Belum Ada Matkul',             text: 'Pilih minimal satu mata kuliah.' },
                    sks_limit:     { icon: 'error',   title: 'Batas SKS Terlampaui',         text: `Total SKS (${sks}) melebihi batas 24 SKS.` },
                    no_periode:    { icon: 'error',   title: 'Tidak Ada Periode Aktif',      text: 'Aktifkan periode terlebih dahulu.' },
                };

                const toast = toastMap[msg];
                if (toast) {
                    Swal.fire({
                        icon: toast.icon,
                        title: toast.title,
                        text: toast.text,
                        timer: 4000,
                        timerProgressBar: true,
                        showConfirmButton: false,
                        background: isDark ? '#0f172a' : '#ffffff',
                        color: isDark ? '#f1f5f9' : '#1e293b',
                        toast: false,
                        position: 'center'
                    });
                }
            })();
        <?php endif; ?>
    </script>
</body>

</html>
