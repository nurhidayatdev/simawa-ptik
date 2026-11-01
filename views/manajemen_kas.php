<?php
session_start();
include '../koneksi.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../../index");
    exit;
}

$user_id = $_SESSION['user_id'];
$nama_lengkap = $_SESSION['nama_lengkap'] ?? '';
$foto = $_SESSION['foto'] ?? '';
$role = $_SESSION['role'];

// Only Treasurer can manage cash flow
if ($role !== 'Bendahara') {
    $_SESSION['error'] = 'Akses ditolak! Pengelolaan keuangan kas hanya wewenang Bendahara.';
    header('Location: ' . ($role === 'Administrator Utama' ? '../dashboard_admin.php' : '../dashboard.php'));
    exit();
}

/* =====================
   AMBIL PERIODE AKTIF
   ===================== */
$q_periode = mysqli_query($koneksi, "SELECT id, semester_ke, tahun_periode, semester_nama FROM periode WHERE status = 'aktif' LIMIT 1");
$periode = mysqli_fetch_assoc($q_periode);
$periode_id = (int)($periode['id'] ?? 0);
$periode_info = $periode ? ($periode['tahun_periode'] . ' ' . $periode['semester_nama']) : '-';

/**
 * ==========================================
 * HANDLING EXPORT EXCEL (Arus Kas & Mingguan)
 * ==========================================
 */

// 1. Export Arus Kas (Transaction Log)
if (isset($_GET['action']) && $_GET['action'] === 'export_excel_arus') {
    // Need totals for the report
    $where_ak_masuk = getKelasWhereClause('', true);
    $res_other_in = mysqli_query($koneksi, "SELECT COALESCE(SUM(jumlah),0) AS total_other_masuk FROM arus_kas WHERE tipe='masuk' $where_ak_masuk");
    $total_other_masuk = mysqli_fetch_assoc($res_other_in)['total_other_masuk'] ?? 0;

    $where_kas = getKelasWhereClause('', true);
    $res_kas_total = mysqli_query($koneksi, "SELECT COALESCE(SUM(nominal),0) AS total_kas FROM kas WHERE status='Lunas' AND periode_id = '$periode_id' $where_kas");
    $total_kas = mysqli_fetch_assoc($res_kas_total)['total_kas'] ?? 0;
    $total_masuk = $total_kas + $total_other_masuk;

    $where_ak_keluar = getKelasWhereClause('', true);
    $res_out = mysqli_query($koneksi, "SELECT COALESCE(SUM(jumlah),0) AS total_keluar FROM arus_kas WHERE tipe='keluar' $where_ak_keluar");
    $total_keluar = mysqli_fetch_assoc($res_out)['total_keluar'] ?? 0;
    $saldo = $total_masuk - $total_keluar;

    $filename = 'laporan_arus_kas_' . date('Ymd_His') . '.xls';
    header("Content-Type: application/vnd.ms-excel");
    header("Content-Disposition: attachment; filename=\"$filename\"");

    echo '<table border="1">';
    echo '<tr><th colspan="5" style="background-color: #1e1b4b; color: white;">LAPORAN ARUS KAS SIMAWA PTIK 2024 (' . htmlspecialchars($periode_info) . ')</th></tr>';
    echo '<tr style="background-color: #f1f5f9;"><th>ID</th><th>Tipe</th><th>Deskripsi</th><th>Jumlah</th><th>Tanggal</th></tr>';
    $where_ak_all = getKelasWhereClause('', false);
    $where_ak_all_sql = $where_ak_all ? "WHERE $where_ak_all" : "";
    $q = mysqli_query($koneksi, "SELECT * FROM arus_kas $where_ak_all_sql ORDER BY tanggal DESC, created_at DESC");
    while ($r = mysqli_fetch_assoc($q)) {
        $tgl = !empty($r['tanggal']) ? date('d/m/Y', strtotime($r['tanggal'])) : '-';
        echo "<tr><td>{$r['id']}</td><td>" . ucfirst($r['tipe']) . "</td><td>" . htmlspecialchars($r['deskripsi']) . "</td><td>Rp " . number_format($r['jumlah'], 0, ',', '.') . "</td><td>$tgl</td></tr>";
    }
    echo '<tr><td colspan="3" align="right"><b>TOTAL SALDO:</b></td><td colspan="2"><b>Rp ' . number_format($saldo, 0, ',', '.') . '</b></td></tr>';
    echo '</table>';
    exit;
}

// 2. Export Kas Mingguan (Student Status)
if (isset($_GET['export']) && $_GET['export'] === 'excel_mingguan' && isset($_GET['minggu_ke'])) {
    $minggu = mysqli_real_escape_string($koneksi, $_GET['minggu_ke']);
    $filter_exp = $_GET['filter'] ?? 'all';
    $where_exp = '';
    if ($filter_exp === 'Lunas') $where_exp = "AND k.status = 'Lunas'";
    elseif ($filter_exp === 'Belum') $where_exp = "AND (k.status IS NULL OR k.status != 'Lunas')";

    $where_mhs_exp = getKelasWhereClause('m', true);
    $where_k_exp = getKelasWhereClause('k', true);
    $res = mysqli_query($koneksi, "
        SELECT m.nim, m.nama_lengkap, '$minggu' AS minggu_ke, COALESCE(k.status, 'Belum') AS status, k.tanggal_bayar 
        FROM mahasiswa m 
        LEFT JOIN users u ON m.user_id = u.id
        LEFT JOIN kas k ON k.mahasiswa_id = m.id AND k.minggu_ke = '$minggu' AND k.periode_id = '$periode_id' $where_k_exp
        WHERE " . getMahasiswaPureWhereClause('m', 'u', false) . " $where_mhs_exp $where_exp 
        ORDER BY m.nim ASC
    ");

    $filename = 'kas_minggu_' . $minggu . '_' . str_replace(' ', '_', $periode_info) . '.xls';
    header("Content-Type: application/vnd.ms-excel");
    header("Content-Disposition: attachment; filename=\"$filename\"");

    echo '<table border="1">';
    echo '<tr><th colspan="5">DATA KAS MINGGU ' . $minggu . ' (' . htmlspecialchars($periode_info) . ')</th></tr>';
    echo '<tr style="background-color: #1e1b4b; color: white;"><th>NIM</th><th>Nama Lengkap</th><th>Minggu</th><th>Status</th><th>Tanggal Bayar</th></tr>';
    while ($r = mysqli_fetch_assoc($res)) {
        $tgl = !empty($r['tanggal_bayar']) ? date('d/m/Y', strtotime($r['tanggal_bayar'])) : '-';
        echo "<tr><td style=\"mso-number-format:'@'\">{$r['nim']}</td><td>{$r['nama_lengkap']}</td><td>{$r['minggu_ke']}</td><td>{$r['status']}</td><td>$tgl</td></tr>";
    }
    echo '</table>';
    exit;
}

/**
 * ==========================================
 * DATABASE ACTIONS (CRUD Arus Kas)
 * ==========================================
 */

// Handle update entry
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_arus'])) {
    $id = (int)($_POST['id'] ?? 0);
    $tipe = $_POST['tipe'] === 'keluar' ? 'keluar' : 'masuk';
    $deskripsi = mysqli_real_escape_string($koneksi, $_POST['deskripsi'] ?? '');
    $jumlah = str_replace(',', '.', $_POST['jumlah'] ?? '0');
    $jumlah = (float)$jumlah;
    $tanggal = $_POST['tanggal'] ?? date('Y-m-d');

    // Cek hak akses ke baris arus_kas
    $q_check = mysqli_query($koneksi, "SELECT kelas_id FROM arus_kas WHERE id = $id LIMIT 1");
    if ($r_check = mysqli_fetch_assoc($q_check)) {
        if (!empty($r_check['kelas_id'])) {
            requireKelasAccess($r_check['kelas_id']);
        }
    }

    $stmt = mysqli_prepare($koneksi, "UPDATE arus_kas SET tipe=?, deskripsi=?, jumlah=?, tanggal=? WHERE id=?");
    mysqli_stmt_bind_param($stmt, 'ssdsi', $tipe, $deskripsi, $jumlah, $tanggal, $id);
    mysqli_stmt_execute($stmt);
    header("Location: manajemen_kas.php");
    exit;
}

// Handle add entry
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['tambah_arus'])) {
    $tipe = $_POST['tipe'] === 'keluar' ? 'keluar' : 'masuk';
    $deskripsi = mysqli_real_escape_string($koneksi, $_POST['deskripsi'] ?? '');
    $jumlah = str_replace(',', '.', $_POST['jumlah'] ?? '0');
    $jumlah = (float)$jumlah;
    $tanggal = $_POST['tanggal'] ?? date('Y-m-d');
    $target_kelas_id = getKelasScope() ?? 1;

    $stmt = mysqli_prepare($koneksi, "INSERT INTO arus_kas (tipe, deskripsi, jumlah, tanggal, kelas_id) VALUES (?, ?, ?, ?, ?)");
    mysqli_stmt_bind_param($stmt, 'ssdsi', $tipe, $deskripsi, $jumlah, $tanggal, $target_kelas_id);
    mysqli_stmt_execute($stmt);
    header("Location: manajemen_kas.php");
    exit;
}

// Handle delete
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    $q_check = mysqli_query($koneksi, "SELECT kelas_id FROM arus_kas WHERE id = $id LIMIT 1");
    if ($r_check = mysqli_fetch_assoc($q_check)) {
        if (!empty($r_check['kelas_id'])) {
            requireKelasAccess($r_check['kelas_id']);
        }
    }
    mysqli_query($koneksi, "DELETE FROM arus_kas WHERE id=$id");
    header("Location: manajemen_kas.php");
    exit;
}

/**
 * ==========================================
 * STATISTICS CALCULATION
 * ==========================================
 */

// Pemasukan dari arus_kas (other)
$where_ak_masuk = getKelasWhereClause('', true);
$res_other_in = mysqli_query($koneksi, "SELECT COALESCE(SUM(jumlah),0) AS total_other_masuk FROM arus_kas WHERE tipe='masuk' $where_ak_masuk");
$total_other_masuk = mysqli_fetch_assoc($res_other_in)['total_other_masuk'] ?? 0;

// Pemasukan dari tabel kas (Mahasiswa)
$where_kas = getKelasWhereClause('', true);
$res_kas = mysqli_query($koneksi, "SELECT COALESCE(SUM(nominal),0) AS total_kas FROM kas WHERE status='Lunas' AND periode_id = '$periode_id' $where_kas");
$total_kas = mysqli_fetch_assoc($res_kas)['total_kas'] ?? 0;

$total_masuk = $total_kas + $total_other_masuk;

$where_ak_keluar = getKelasWhereClause('', true);
$res_out = mysqli_query($koneksi, "SELECT COALESCE(SUM(jumlah),0) AS total_keluar FROM arus_kas WHERE tipe='keluar' $where_ak_keluar");
$total_keluar = mysqli_fetch_assoc($res_out)['total_keluar'] ?? 0;
$saldo = $total_masuk - $total_keluar;

// Progress Calculation
$where_mhs_only = getKelasWhereClause('m', false);
$where_mhs_sql = $where_mhs_only ? "AND $where_mhs_only" : "";
$mhs_pure = getMahasiswaPureWhereClause('m', 'u', false);
$q_mhs_count = mysqli_query($koneksi, "SELECT COUNT(*) as total FROM mahasiswa m LEFT JOIN users u ON m.user_id = u.id WHERE $mhs_pure $where_mhs_sql");
$total_students = mysqli_fetch_assoc($q_mhs_count)['total'] ?? 0;

$q_latest_week = mysqli_query($koneksi, "SELECT MAX(minggu_ke) as max_week FROM kas WHERE periode_id = '$periode_id' $where_kas");
$current_week = (int)(mysqli_fetch_assoc($q_latest_week)['max_week'] ?? 1);
if ($current_week < 1) $current_week = 1;

$q_nominal = mysqli_query($koneksi, "SELECT nominal FROM kas WHERE nominal > 0 AND periode_id = '$periode_id' $where_kas LIMIT 1");
$fixed_nominal = (int)(mysqli_fetch_assoc($q_nominal)['nominal'] ?? 5000);

$target_kas = $total_students * $current_week * $fixed_nominal;
$progress_percent = ($target_kas > 0) ? min(100, round(($total_kas / $target_kas) * 100, 1)) : 0;

// Weekly Details
$weekly_data = [];
$q_weekly = mysqli_query($koneksi, "SELECT minggu_ke, COUNT(*) as paid_count FROM kas WHERE status='Lunas' AND periode_id = '$periode_id' $where_kas GROUP BY minggu_ke");
while ($rw = mysqli_fetch_assoc($q_weekly)) {
    $weekly_data[$rw['minggu_ke']] = $rw['paid_count'];
}

/**
 * ==========================================
 * DATA FOR LIST DISPLAY
 * ==========================================
 */

// For Edit Arus Kas
$editing = false;
$form_tipe = 'masuk';
$form_deskripsi = '';
$form_jumlah = '';
$form_tanggal = date('Y-m-d');
if (isset($_GET['action']) && $_GET['action'] === 'edit' && isset($_GET['id'])) {
    $eid = (int)$_GET['id'];
    $res_edit = mysqli_query($koneksi, "SELECT * FROM arus_kas WHERE id={$eid} LIMIT 1");
    if ($res_edit && $row_edit = mysqli_fetch_assoc($res_edit)) {
        if (!empty($row_edit['kelas_id'])) {
            requireKelasAccess($row_edit['kelas_id']);
        }
        $editing = true;
        $form_tipe = $row_edit['tipe'];
        $form_deskripsi = $row_edit['deskripsi'];
        $form_jumlah = $row_edit['jumlah'];
        $form_tanggal = !empty($row_edit['tanggal']) ? $row_edit['tanggal'] : $form_tanggal;
    }
}

// Transaction List
$where_ak_all = getKelasWhereClause('', false);
$where_ak_all_sql = $where_ak_all ? "WHERE $where_ak_all" : "";
$entries = mysqli_query($koneksi, "SELECT * FROM arus_kas $where_ak_all_sql ORDER BY tanggal DESC, created_at DESC");

// Weekly Table Status (from kas.php)
$f_minggu = $_GET['minggu_ke'] ?? 1;
$f_filter_mhs = $_GET['filter_mhs'] ?? 'all';
$where_mhs = ($f_filter_mhs === 'Lunas') ? " AND k.status = 'Lunas'" : (($f_filter_mhs === 'Belum') ? " AND (k.status IS NULL OR k.status != 'Lunas')" : "");

$where_mhs_scope = getKelasWhereClause('m', true);
$where_k_scope = getKelasWhereClause('k', true);
$mhs_status = mysqli_query($koneksi, "
    SELECT m.id AS mahasiswa_id, m.nim, m.nama_lengkap, '$f_minggu' AS minggu_ke, COALESCE(k.status, 'Belum') AS status, k.tanggal_bayar 
    FROM mahasiswa m 
    LEFT JOIN users u ON m.user_id = u.id
    LEFT JOIN kas k ON k.mahasiswa_id = m.id AND k.minggu_ke = '$f_minggu' AND k.periode_id = '$periode_id' $where_k_scope
    WHERE " . getMahasiswaPureWhereClause('m', 'u', false) . " $where_mhs_scope $where_mhs 
    ORDER BY m.nim ASC
");

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <title>Manajemen Kas - SIMAWA PTIK 2024</title>
    <?php include '../includes/head.php'; ?>
    <style>
        .animate-shimmer {
            background: linear-gradient(90deg, rgba(255, 255, 255, 0) 0%, rgba(255, 255, 255, 0.2) 50%, rgba(255, 255, 255, 0) 100%);
            background-size: 200% 100%;
            animation: shimmer 2s infinite;
        }

        @keyframes shimmer {
            0% {
                background-position: -200% 0;
            }

            100% {
                background-position: 200% 0;
            }
        }
    </style>
</head>

<body class="bg-slate-100 dark:bg-slate-950 transition-colors duration-300">
    <?php include '../includes/menu_db.php'; ?>

    <main class="ml-0 lg:ml-48 pt-20 p-6 flex flex-col min-h-screen">
        <div class="max-w-6xl mx-auto flex-grow w-full">

            <!-- Header Section -->
            <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4 mb-8">
                <div>
                    <h3 class="text-2xl font-bold text-slate-800 dark:text-white tracking-tight text-center md:text-left">Manajemen Kas</h3>
                    <p class="text-xs font-semibold text-slate-400 dark:text-slate-500 mt-1 text-center md:text-left">
                        Periode Aktif: <span class="text-indigo-600 dark:text-indigo-400 font-bold"><?= htmlspecialchars($periode_info) ?></span> &bull; Cash Flow & Savings
                    </p>
                </div>
                <div class="flex flex-wrap gap-2">
                    <a href="?action=export_excel_arus" class="bg-emerald-500 hover:bg-emerald-600 text-white px-4 py-2.5 rounded-xl text-[10px] font-bold flex items-center gap-2 transition-all shadow-lg shadow-emerald-500/20 active:scale-95">
                        <i class="fas fa-file-excel"></i> EXPORT ARUS
                    </a>
                    <a href="../actions/tambah_kas_minggu.php?minggu_ke=<?= $f_minggu ?>" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2.5 rounded-xl text-[10px] font-bold flex items-center gap-2 transition-all shadow-lg shadow-blue-600/20 active:scale-95">
                        <i class="fas fa-layer-group"></i> INPUT MINGGUAN
                    </a>
                    <a href="../actions/tambah_kas_individu.php" class="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2.5 rounded-xl text-[10px] font-bold flex items-center gap-2 transition-all shadow-lg shadow-indigo-600/20 active:scale-95">
                        <i class="fas fa-user-plus"></i> INPUT INDIVIDU
                    </a>
                </div>
            </div>

            <!-- Stats Grid -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 mb-10">
                <div class="bg-white dark:bg-slate-900 p-5 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-sm transition-all group">
                    <div class="flex items-center gap-4">
                        <div class="w-12 h-12 rounded-xl bg-indigo-50 dark:bg-indigo-950/30 flex items-center justify-center text-indigo-600 dark:text-indigo-400 border border-indigo-100 dark:border-indigo-900/50 group-hover:bg-indigo-600 group-hover:text-white transition-all">
                            <i class="fas fa-wallet text-xl"></i>
                        </div>
                        <div>
                            <div class="text-[10px] font-bold text-slate-400 dark:text-slate-500 uppercase tracking-widest leading-none mb-1">Kas Mahasiswa</div>
                            <div class="text-lg font-bold text-slate-800 dark:text-white leading-none">Rp <?= number_format($total_kas, 0, ',', '.') ?></div>
                        </div>
                    </div>
                </div>

                <div class="bg-white dark:bg-slate-900 p-5 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-sm transition-all group">
                    <div class="flex items-center gap-4">
                        <div class="w-12 h-12 rounded-xl bg-emerald-50 dark:bg-emerald-950/30 flex items-center justify-center text-emerald-600 dark:text-emerald-400 border border-emerald-100 dark:border-emerald-900/50 group-hover:bg-emerald-600 group-hover:text-white transition-all">
                            <i class="fas fa-arrow-down text-xl"></i>
                        </div>
                        <div>
                            <div class="text-[10px] font-bold text-slate-400 dark:text-slate-500 uppercase tracking-widest leading-none mb-1">Pemasukan lain</div>
                            <div class="text-lg font-bold text-emerald-600 leading-none">Rp <?= number_format($total_other_masuk, 0, ',', '.') ?></div>
                        </div>
                    </div>
                </div>

                <div class="bg-white dark:bg-slate-900 p-5 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-sm transition-all group">
                    <div class="flex items-center gap-4">
                        <div class="w-12 h-12 rounded-xl bg-rose-50 dark:bg-rose-950/30 flex items-center justify-center text-rose-600 dark:text-rose-400 border border-rose-100 dark:border-rose-900/50 group-hover:bg-rose-600 group-hover:text-white transition-all">
                            <i class="fas fa-arrow-up text-xl"></i>
                        </div>
                        <div>
                            <div class="text-[10px] font-bold text-slate-400 dark:text-slate-500 uppercase tracking-widest leading-none mb-1">Dana Keluar</div>
                            <div class="text-lg font-bold text-rose-600 leading-none">Rp <?= number_format($total_keluar, 0, ',', '.') ?></div>
                        </div>
                    </div>
                </div>

                <div class="bg-gradient-to-br from-indigo-900 to-slate-900 p-5 rounded-2xl shadow-xl shadow-indigo-900/20 group relative overflow-hidden">
                    <div class="relative z-10 flex items-center gap-4">
                        <div class="w-12 h-12 rounded-xl bg-white/10 flex items-center justify-center text-white border border-white/10">
                            <i class="fas fa-balance-scale text-xl"></i>
                        </div>
                        <div>
                            <div class="text-[10px] font-bold text-indigo-300/60 uppercase tracking-widest leading-none mb-1">Saldo Tersedia</div>
                            <div class="text-lg font-bold text-white leading-none">Rp <?= number_format($saldo, 0, ',', '.') ?></div>
                        </div>
                    </div>
                    <div class="absolute top-0 right-0 w-24 h-24 bg-white/5 rounded-full -mr-12 -mt-12 transition-transform group-hover:scale-150"></div>
                </div>
            </div>

            <!-- Arus Kas Form -->
            <div class="mb-8">
                <div class="bg-white dark:bg-slate-900 p-4 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-sm relative overflow-hidden">
                    <div class="absolute top-0 left-0 w-1 h-full bg-indigo-600"></div>
                    <h3 class="text-xs font-bold text-slate-800 dark:text-white mb-4 flex items-center gap-2 uppercase tracking-widest">
                        <i class="fas fa-edit text-indigo-600"></i>
                        <?= $editing ? 'Perbarui Transaksi Arus Kas' : 'Catat Transaksi Baru' ?>
                    </h3>
                    <form method="POST" class="grid grid-cols-1 md:grid-cols-4 gap-4 items-end">
                        <div>
                            <label class="text-[10px] font-bold text-slate-400 dark:text-slate-500 uppercase block mb-1.5 px-1">Jenis</label>
                            <select name="tipe" class="w-full bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-xs font-semibold text-slate-800 dark:text-slate-200 rounded-xl px-4 py-2.5 focus:ring-4 focus:ring-indigo-500/10 transition-all">
                                <option value="masuk" <?= $form_tipe === 'masuk' ? 'selected' : '' ?>>Pemasukan</option>
                                <option value="keluar" <?= $form_tipe === 'keluar' ? 'selected' : '' ?>>Pengeluaran</option>
                            </select>
                        </div>
                        <div>
                            <label class="text-[10px] font-bold text-slate-400 dark:text-slate-500 uppercase block mb-1.5 px-1">Keterangan</label>
                            <input type="text" name="deskripsi" value="<?= htmlspecialchars($form_deskripsi) ?>" placeholder="Contoh: Beli Spidol..." class="w-full bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-xs font-semibold text-slate-800 dark:text-slate-200 rounded-xl px-4 py-2.5 focus:ring-4 focus:ring-indigo-500/10 transition-all" required>
                        </div>
                        <div>
                            <label class="text-[10px] font-bold text-slate-400 dark:text-slate-500 uppercase block mb-1.5 px-1">Nominal (Rp)</label>
                            <input type="number" name="jumlah" value="<?= htmlspecialchars($form_jumlah) ?>" placeholder="0" class="w-full bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-xs font-bold text-indigo-700 dark:text-indigo-400 rounded-xl px-4 py-2.5 focus:ring-4 focus:ring-indigo-500/10 transition-all" required>
                        </div>
                        <div class="flex gap-2">
                            <div class="flex-grow">
                                <label class="text-[10px] font-bold text-slate-400 dark:text-slate-500 uppercase block mb-1.5 px-1">Tanggal</label>
                                <input type="date" name="tanggal" value="<?= htmlspecialchars($form_tanggal) ?>" class="w-full bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-xs font-semibold text-slate-800 dark:text-slate-200 rounded-xl px-4 py-2.5 transition-all">
                            </div>
                            <?php if ($editing): ?>
                                <input type="hidden" name="id" value="<?= (int)$eid ?>">
                                <button type="submit" name="edit_arus" class="bg-amber-500 hover:bg-amber-600 text-white w-12 h-10 flex items-center justify-center rounded-xl transition-all shadow-lg shadow-amber-500/20"><i class="fas fa-check"></i></button>
                                <a href="manajemen_kas.php" class="bg-slate-100 dark:bg-slate-800 text-slate-500 dark:text-slate-400 w-12 h-10 flex items-center justify-center rounded-xl hover:bg-slate-200 dark:hover:bg-slate-700"><i class="fas fa-times"></i></a>
                            <?php else: ?>
                                <button type="submit" name="tambah_arus" class="bg-indigo-900 hover:bg-slate-800 text-white w-12 h-10 flex items-center justify-center rounded-xl shadow-lg shadow-indigo-900/30 transition-all"><i class="fas fa-plus"></i></button>
                            <?php endif; ?>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Pencapaian Mingguan & Detail Section -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8 mb-10">

                <!-- Progress Circular / Summary -->
                <div class="lg:col-span-1 bg-white dark:bg-slate-900 p-6 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-sm relative overflow-hidden flex flex-col justify-between">
                    <div class="relative z-10">
                        <div class="flex items-center gap-3 mb-6">
                            <div class="w-10 h-10 rounded-xl bg-indigo-600 flex items-center justify-center text-white shadow-lg shadow-indigo-600/20">
                                <i class="fas fa-chart-line"></i>
                            </div>
                            <div>
                                <h4 class="text-xs font-bold text-slate-800 dark:text-white uppercase tracking-widest leading-none">Pencapaian</h4>
                                <p class="text-[10px] font-semibold text-slate-400 dark:text-slate-500 mt-1 uppercase">MINGGU KE-<?= $current_week ?></p>
                            </div>
                        </div>
                        <div class="flex flex-col items-center justify-center py-4">
                            <div class="text-4xl font-bold text-indigo-600 dark:text-indigo-400 mb-1"><?= $progress_percent ?>%</div>
                            <div class="text-[10px] font-bold text-slate-400 dark:text-slate-500 uppercase tracking-widest text-center">Kelunasan Kas Mahasiswa</div>

                            <div class="w-full bg-slate-100 dark:bg-slate-800 h-2.5 rounded-full overflow-hidden mt-6 mb-2">
                                <div class="h-full bg-indigo-600" style="width: <?= $progress_percent ?>%"></div>
                            </div>
                            <div class="w-full flex justify-between text-[9px] font-bold text-slate-400 dark:text-slate-500">
                                <span>Rp <?= number_format($total_kas, 0, ',', '.') ?></span>
                                <span>TARGET: Rp <?= number_format($target_kas, 0, ',', '.') ?></span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Histori Arus Kas -->
                <div class="lg:col-span-2 bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-sm overflow-hidden flex flex-col">
                    <div class="p-4 border-b border-slate-100 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-900/50 flex justify-between items-center">
                        <h4 class="text-xs font-bold text-slate-800 dark:text-white uppercase tracking-widest flex items-center gap-2">
                            <i class="fas fa-history text-slate-400"></i> Riwayat Arus Kas
                        </h4>
                        <div class="relative">
                            <i class="fas fa-search absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-[10px]"></i>
                            <input type="text" id="searchInput" placeholder="Cari..." class="bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-lg pl-8 pr-4 py-1.5 text-[10px] text-slate-800 dark:text-slate-200 focus:ring-4 focus:ring-indigo-500/10 transition-all w-48 font-semibold">
                        </div>
                    </div>
                    <div class="overflow-y-auto max-h-[250px] flex-grow">
                        <table class="w-full text-left" id="historyTable">
                            <thead class="sticky top-0 bg-slate-50 dark:bg-slate-950 shadow-sm z-20">
                                <tr>
                                    <th class="px-4 py-2 text-[9px] font-bold text-slate-400 dark:text-slate-500 uppercase tracking-widest">Tgl / Tipe</th>
                                    <th class="px-4 py-2 text-[9px] font-bold text-slate-400 dark:text-slate-500 uppercase tracking-widest">Deskripsi</th>
                                    <th class="px-4 py-2 text-right text-[9px] font-bold text-slate-400 dark:text-slate-500 uppercase tracking-widest">Jumlah</th>
                                    <th class="px-4 py-2 text-center text-[9px] font-bold text-slate-400 dark:text-slate-500 uppercase tracking-widest">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-50 dark:divide-slate-800">
                                <?php while ($row = mysqli_fetch_assoc($entries)): ?>
                                    <tr class="hover:bg-slate-50/50 dark:hover:bg-slate-800/50 transition-colors group">
                                        <td class="px-4 py-2.5 whitespace-nowrap">
                                            <div class="text-[10px] font-bold text-slate-700 dark:text-slate-300"><?= !empty($row['tanggal']) ? date('d M Y', strtotime($row['tanggal'])) : '-' ?></div>
                                            <span class="text-[7px] font-bold px-1.5 py-0.5 rounded-full border <?= $row['tipe'] === 'masuk' ? 'text-emerald-600 bg-emerald-50 dark:bg-emerald-900/30 border-emerald-100 dark:border-emerald-800/50' : 'text-rose-600 bg-rose-50 dark:bg-rose-900/30 border-rose-100 dark:border-rose-800/50' ?>"><?= strtoupper($row['tipe']) ?></span>
                                        </td>
                                        <td class="px-4 py-2.5 text-[10px] font-semibold text-slate-600 dark:text-slate-400"><?= htmlspecialchars($row['deskripsi']) ?></td>
                                        <td class="px-4 py-2.5 text-right whitespace-nowrap">
                                            <span class="text-[10px] font-bold <?= $row['tipe'] === 'masuk' ? 'text-emerald-600' : 'text-rose-600' ?>">Rp <?= number_format($row['jumlah'], 0, ',', '.') ?></span>
                                        </td>
                                        <td class="px-4 py-2.5">
                                            <div class="flex justify-center gap-1 opacity-0 group-hover:opacity-100 transition-all">
                                                <a href="?action=edit&id=<?= $row['id'] ?>" class="w-6 h-6 rounded bg-indigo-50 dark:bg-indigo-900/40 text-indigo-600 dark:text-indigo-400 flex items-center justify-center hover:bg-indigo-600 hover:text-white transition-all"><i class="fas fa-pen text-[8px]"></i></a>
                                                <button onclick="confirmDelete(<?= $row['id'] ?>)" class="w-6 h-6 rounded bg-rose-50 dark:bg-rose-900/40 text-rose-600 dark:text-rose-400 flex items-center justify-center hover:bg-rose-600 hover:text-white transition-all"><i class="fas fa-trash text-[8px]"></i></button>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Status Weekly Table Section (NEW, consolidated from kas.php) -->
            <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-sm overflow-hidden mb-12">
                <div class="p-4 border-b border-slate-100 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-900/50 flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
                    <h4 class="text-xs font-bold text-slate-800 dark:text-white uppercase tracking-widest flex items-center gap-2">
                        <i class="fas fa-user-check text-slate-400"></i> Status Pembayaran Minggu Ini
                    </h4>
                    <form method="GET" class="flex flex-wrap gap-2 w-full md:w-auto">
                        <select name="minggu_ke" onchange="this.form.submit()" class="bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-lg px-3 py-1.5 text-[10px] font-semibold text-slate-700 dark:text-slate-300 focus:ring-4 focus:ring-indigo-500/10 transition-all">
                            <?php for ($i = 1; $i <= 16; $i++): ?>
                                <option value="<?= $i ?>" <?= $f_minggu == $i ? 'selected' : '' ?>>Minggu <?= $i ?></option>
                            <?php endfor; ?>
                        </select>
                        <select name="filter_mhs" onchange="this.form.submit()" class="bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-lg px-3 py-1.5 text-[10px] font-semibold text-slate-700 dark:text-slate-300 focus:ring-4 focus:ring-indigo-500/10 transition-all">
                            <option value="all" <?= $f_filter_mhs == 'all' ? 'selected' : '' ?>>Semua</option>
                            <option value="Lunas" <?= $f_filter_mhs == 'Lunas' ? 'selected' : '' ?>>Lunas</option>
                            <option value="Belum" <?= $f_filter_mhs == 'Belum' ? 'selected' : '' ?>>Belum</option>
                        </select>
                        <div class="relative flex-grow md:flex-grow-0">
                            <i class="fas fa-search absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-[10px]"></i>
                            <input type="text" id="mhsSearchInput" placeholder="Cari mahasiswa..." class="bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-lg pl-8 pr-4 py-1.5 text-[10px] font-semibold text-slate-800 dark:text-slate-200 focus:ring-4 focus:ring-indigo-500/10 transition-all w-full md:w-48">
                        </div>
                        <div class="flex gap-1">
                            <button type="button" onclick="confirmReminderKas('<?= $f_minggu ?>')" class="bg-sky-500 hover:bg-sky-600 text-white w-8 h-8 flex items-center justify-center rounded-lg shadow-sm transition-all active:scale-95" title="Kirim Pengingat">
                                <i class="fas fa-paper-plane text-[10px]"></i>
                            </button>
                            <a href="?export=excel_mingguan&minggu_ke=<?= $f_minggu ?>&filter=<?= $f_filter_mhs ?>" class="bg-emerald-500 hover:bg-emerald-600 text-white w-8 h-8 flex items-center justify-center rounded-lg shadow-sm transition-all active:scale-95" title="Export Excel">
                                <i class="fas fa-file-excel text-[10px]"></i>
                            </a>
                        </div>
                    </form>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-left" id="mhsTable">
                        <thead>
                            <tr class="bg-slate-50/80 dark:bg-slate-950 border-b border-slate-100 dark:border-slate-800">
                                <th class="px-6 py-4 text-[10px] font-bold text-slate-400 dark:text-slate-500 uppercase tracking-widest w-16 text-center">No</th>
                                <th class="px-6 py-4 text-[10px] font-bold text-slate-400 dark:text-slate-500 uppercase tracking-widest">Mahasiswa</th>
                                <th class="px-6 py-4 text-center text-[10px] font-bold text-slate-400 dark:text-slate-500 uppercase tracking-widest">Status</th>
                                <th class="px-6 py-4 text-[10px] font-bold text-slate-400 dark:text-slate-500 uppercase tracking-widest">Tgl Bayar</th>
                                <th class="px-6 py-4 text-center text-[10px] font-bold text-slate-400 dark:text-slate-500 uppercase tracking-widest">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                            <?php $no = 1;
                            while ($row = mysqli_fetch_assoc($mhs_status)):
                                $is_lunas = $row['status'] === 'Lunas';
                            ?>
                                <tr class="hover:bg-slate-50/50 dark:hover:bg-slate-800/50 transition-all mhs-row">
                                    <td class="px-6 py-3 text-[10px] font-bold text-slate-400 dark:text-slate-500 text-center"><?= $no++ ?></td>
                                    <td class="px-6 py-3">
                                        <div class="flex items-center gap-3">
                                            <div class="w-8 h-8 rounded-lg overflow-hidden border border-slate-200 dark:border-slate-700 shrink-0 bg-slate-50 dark:bg-slate-800 flex items-center justify-center"><img src="<?= profilePhotoUrl($row['nama_lengkap'], '../') ?>" class="w-full h-full object-cover"></div>
                                            <div class="truncate">
                                                <div class="text-[10px] font-bold text-slate-700 dark:text-white leading-tight uppercase mhs-name"><?= htmlspecialchars($row['nama_lengkap']) ?></div>
                                                <div class="text-[8px] text-slate-400 dark:text-slate-500 font-semibold mt-0.5"><?= $row['nim'] ?></div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-3 text-center">
                                        <span class="inline-block px-3 py-1 rounded-full border text-[9px] font-bold uppercase tracking-wider <?= $is_lunas ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/30 dark:text-emerald-400 border-emerald-200 dark:border-emerald-800/50' : 'bg-amber-100 text-amber-800 dark:bg-amber-900/30 dark:text-amber-400 border-amber-200 dark:border-amber-800/50' ?>">
                                            <?= $row['status'] ?: 'Belum' ?>
                                        </span>
                                    </td>
                                    <td class="px-6 py-3 text-[10px] font-semibold text-slate-600 dark:text-slate-400">
                                        <?= !empty($row['tanggal_bayar']) && $row['tanggal_bayar'] !== '0000-00-00' ? date('d/m/Y', strtotime($row['tanggal_bayar'])) : '-' ?>
                                    </td>
                                    <td class="px-6 py-3 text-center">
                                        <a href="../actions/tambah_kas_individu.php?mahasiswa_id=<?= $row['mahasiswa_id'] ?>" class="text-indigo-600 hover:text-indigo-900 transition-all" title="Edit Pembayaran">
                                            <i class="fas fa-user-edit text-[11px]"></i>
                                        </a>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
        <?php include '../includes/footer_db.php'; ?>
    </main>

    <?php include '../includes/script_db.php'; ?>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        // Transaction filtering
        document.getElementById('searchInput').addEventListener('keyup', function() {
            let filter = this.value.toLowerCase();
            let rows = document.querySelectorAll('#historyTable tbody tr');
            rows.forEach(row => {
                row.style.display = row.innerText.toLowerCase().includes(filter) ? '' : 'none';
            });
        });

        // Student status filtering
        document.getElementById('mhsSearchInput').addEventListener('keyup', function() {
            let filter = this.value.toLowerCase();
            let rows = document.querySelectorAll('.mhs-row');
            rows.forEach(row => {
                let name = row.querySelector('.mhs-name').innerText.toLowerCase();
                row.style.display = name.includes(filter) ? '' : 'none';
            });
        });

        function confirmDelete(id) {
            Swal.fire({
                title: 'Hapus Transaksi?',
                text: "Data tidak dapat dikembalikan!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#e11d48',
                confirmButtonText: 'Ya, Hapus!',
                cancelButtonText: 'Batal'
            }).then((result) => {
                if (result.isConfirmed) window.location.href = `?action=delete&id=${id}`;
            });
        }

        function confirmReminderKas(minggu) {
            Swal.fire({
                title: 'Kirim Reminder Kas?',
                text: "Daftar tagihan minggu " + minggu + " akan dikirim.",
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#0ea5e9',
                confirmButtonText: 'Kirim Sekarang!',
                cancelButtonText: 'Batal'
            }).then((result) => {
                if (result.isConfirmed) window.location.href = '../functions/kirim_kas.php?minggu_ke=' + minggu + '&redirect=manajemen_kas';
            });
        }

        // Show Success Alert
        <?php if (isset($_GET['success'])): ?>
            Swal.fire({
                icon: 'success',
                title: 'Berhasil!',
                text: '<?= $_SESSION['success'] ?? "Data telah berhasil disimpan." ?>',
                confirmButtonColor: '#3730a3',
                showCancelButton: <?= isset($_GET['tanya_wa']) ? 'true' : 'false' ?>,
                confirmButtonText: '<?= isset($_GET['tanya_wa']) ? "Kirim WA" : "OK" ?>',
                cancelButtonText: 'Selesai'
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = '../actions/tambah_kas_minggu.php?send_wa=1&minggu_ke=<?= $_GET['minggu_ke'] ?? '' ?>';
                } else {
                    const url = new URL(window.location);
                    url.searchParams.delete('success');
                    url.searchParams.delete('tanya_wa');
                    window.history.replaceState({}, document.title, url);
                }
            });
            <?php unset($_SESSION['success']); ?>
        <?php endif; ?>

        // Show WA Sent Alert
        <?php if (isset($_GET['wa_sent'])): ?>
            Swal.fire({
                icon: 'info',
                title: 'WhatsApp',
                text: '<?= $_SESSION['success'] ?? "Proses selesai." ?>',
                confirmButtonColor: '#3730a3'
            }).then(() => {
                const url = new URL(window.location);
                url.searchParams.delete('wa_sent');
                window.history.replaceState({}, document.title, url);
            });
            <?php unset($_SESSION['success']); ?>
        <?php endif; ?>
    </script>
</body>

</html>