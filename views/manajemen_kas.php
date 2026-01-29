<?php
session_start();
include '../koneksi.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../../index.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$nama_lengkap = $_SESSION['nama_lengkap'] ?? '';
$foto = $_SESSION['foto'] ?? '';
$role = $_SESSION['role'];

if ($_SESSION['role'] === 'Mahasiswa' || $_SESSION['role'] === 'Ketua Tingkat' || $_SESSION['role'] === 'Sekretaris') {
    include '../includes/alert_role.php';
    exit();
}

// handle add entry
// handle update entry
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_arus'])) {
    $id = (int)($_POST['id'] ?? 0);
    $tipe = $_POST['tipe'] === 'keluar' ? 'keluar' : 'masuk';
    $deskripsi = mysqli_real_escape_string($koneksi, $_POST['deskripsi'] ?? '');
    $jumlah = str_replace(',', '.', $_POST['jumlah'] ?? '0');
    $jumlah = (float)$jumlah;
    $tanggal = $_POST['tanggal'] ?? date('Y-m-d');

    $stmt = mysqli_prepare($koneksi, "UPDATE arus_kas SET tipe=?, deskripsi=?, jumlah=?, tanggal=? WHERE id=?");
    mysqli_stmt_bind_param($stmt, 'ssdsi', $tipe, $deskripsi, $jumlah, $tanggal, $id);
    mysqli_stmt_execute($stmt);
    header("Location: manajemen_kas.php");
    exit;
}

// handle add entry
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['tambah_arus'])) {
    $tipe = $_POST['tipe'] === 'keluar' ? 'keluar' : 'masuk';
    $deskripsi = mysqli_real_escape_string($koneksi, $_POST['deskripsi'] ?? '');
    $jumlah = str_replace(',', '.', $_POST['jumlah'] ?? '0');
    $jumlah = (float)$jumlah;
    $tanggal = $_POST['tanggal'] ?? date('Y-m-d');

    $stmt = mysqli_prepare($koneksi, "INSERT INTO arus_kas (tipe, deskripsi, jumlah, tanggal) VALUES (?, ?, ?, ?)");
    mysqli_stmt_bind_param($stmt, 'ssds', $tipe, $deskripsi, $jumlah, $tanggal);
    mysqli_stmt_execute($stmt);
    header("Location: manajemen_kas.php");
    exit;
}

// handle delete
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    mysqli_query($koneksi, "DELETE FROM arus_kas WHERE id=$id");
    header("Location: manajemen_kas.php");
    exit;
}

// totals
// pemasukan dari arus_kas (other)
$res_other_in = mysqli_query($koneksi, "SELECT COALESCE(SUM(jumlah),0) AS total_other_masuk FROM arus_kas WHERE tipe='masuk'");
$total_other_masuk = ($res_other_in && $r = mysqli_fetch_assoc($res_other_in)) ? $r['total_other_masuk'] : 0;

// pemasukan dari tabel kas (nominal per pembayaran) - hanya yang sudah Lunas
$res_kas = mysqli_query($koneksi, "SELECT COALESCE(SUM(nominal),0) AS total_kas FROM kas WHERE status='Lunas'");
$total_kas = ($res_kas && $r2 = mysqli_fetch_assoc($res_kas)) ? $r2['total_kas'] : 0;

$total_masuk = $total_kas + $total_other_masuk;

$res_out = mysqli_query($koneksi, "SELECT COALESCE(SUM(jumlah),0) AS total_keluar FROM arus_kas WHERE tipe='keluar'");
$total_keluar = ($res_out && $r = mysqli_fetch_assoc($res_out)) ? $r['total_keluar'] : 0;
$saldo = $total_masuk - $total_keluar;

// --- Progress Kas Calculation ---
// Get total active students
$q_mhs_count = mysqli_query($koneksi, "SELECT COUNT(*) as total FROM mahasiswa");
$total_students = mysqli_fetch_assoc($q_mhs_count)['total'] ?? 0;

// Get latest week from kas table
$q_latest_week = mysqli_query($koneksi, "SELECT MAX(minggu_ke) as max_week FROM kas");
$current_week = mysqli_fetch_assoc($q_latest_week)['max_week'] ?? 1;
if ($current_week < 1) $current_week = 1;

// Assume nominal 5000 if not found, or get average nominal
$q_nominal = mysqli_query($koneksi, "SELECT nominal FROM kas WHERE nominal > 0 LIMIT 1");
$fixed_nominal = mysqli_fetch_assoc($q_nominal)['nominal'] ?? 5000;

$target_kas = $total_students * $current_week * $fixed_nominal;
$progress_percent = ($target_kas > 0) ? min(100, round(($total_kas / $target_kas) * 100, 1)) : 0;

// --- Weekly Progress Details ---
$weekly_data = [];
$q_weekly = mysqli_query($koneksi, "SELECT minggu_ke, COUNT(*) as paid_count FROM kas WHERE status='Lunas' GROUP BY minggu_ke");
while ($rw = mysqli_fetch_assoc($q_weekly)) {
    $weekly_data[$rw['minggu_ke']] = $rw['paid_count'];
}

// --- Individual Student Progress ---
$q_mhs_progress = mysqli_query($koneksi, "
    SELECT m.nim, m.nama_lengkap, m.foto, COUNT(k.id) as weeks_paid
    FROM mahasiswa m
    LEFT JOIN kas k ON m.id = k.mahasiswa_id AND k.status = 'Lunas'
    GROUP BY m.id
    ORDER BY m.nim ASC
");
$student_progress = [];
while ($rp = mysqli_fetch_assoc($q_mhs_progress)) {
    $rp['percent'] = ($current_week > 0) ? min(100, round(($rp['weeks_paid'] / $current_week) * 100, 1)) : 0;
    $student_progress[] = $rp;
}
// --------------------------------


// optional edit fetch
$editing = false;
$form_tipe = 'masuk';
$form_deskripsi = '';
$form_jumlah = '';
$form_tanggal = date('Y-m-d');
if (isset($_GET['action']) && $_GET['action'] === 'edit' && isset($_GET['id'])) {
    $eid = (int)$_GET['id'];
    $res_edit = mysqli_query($koneksi, "SELECT * FROM arus_kas WHERE id={$eid} LIMIT 1");
    if ($res_edit && $row_edit = mysqli_fetch_assoc($res_edit)) {
        $editing = true;
        $form_tipe = $row_edit['tipe'];
        $form_deskripsi = $row_edit['deskripsi'];
        $form_jumlah = $row_edit['jumlah'];
        $form_tanggal = !empty($row_edit['tanggal']) ? $row_edit['tanggal'] : $form_tanggal;
    }
}

// list entries
$entries = mysqli_query($koneksi, "SELECT * FROM arus_kas ORDER BY tanggal DESC, created_at DESC");

// handle export to Excel
if (isset($_GET['action']) && $_GET['action'] === 'export_excel') {
    $filename = 'manajemen_kas_' . date('Ymd_His') . '.xls';
    header("Content-Type: application/vnd.ms-excel");
    header("Content-Disposition: attachment; filename=\"$filename\"");
    header("Pragma: no-cache");
    header("Expires: 0");

    echo '<table border="1">';
    echo '<tr><th colspan="5" style="background-color: #1e1b4b; color: white; padding: 10px;">LAPORAN ARUS KAS PTIK C 2024</th></tr>';
    echo '<tr>
            <th style="background-color: #f8fafc;">ID</th>
            <th style="background-color: #f8fafc;">Tipe</th>
            <th style="background-color: #f8fafc;">Deskripsi</th>
            <th style="background-color: #f8fafc;">Jumlah</th>
            <th style="background-color: #f8fafc;">Tanggal</th>
          </tr>';

    $q = mysqli_query($koneksi, "SELECT * FROM arus_kas ORDER BY tanggal DESC, created_at DESC");
    while ($r = mysqli_fetch_assoc($q)) {
        $tgl = !empty($r['tanggal']) ? date('d/m/Y', strtotime($r['tanggal'])) : '-';
        $amount = number_format($r['jumlah'], 0, ',', '.');
        echo '<tr>';
        echo '<td>' . $r['id'] . '</td>';
        echo '<td>' . ucfirst($r['tipe']) . '</td>';
        echo '<td>' . htmlspecialchars($r['deskripsi']) . '</td>';
        echo '<td>Rp ' . $amount . '</td>';
        echo '<td>' . $tgl . '</td>';
        echo '</tr>';
    }
    echo '<tr><td colspan="3" align="right"><b>TOTAL SALDO:</b></td><td colspan="2"><b>Rp ' . number_format($saldo, 0, ',', '.') . '</b></td></tr>';
    echo '</table>';
    exit;
}

?>
<!DOCTYPE html>
<html lang="en">

<head>

    <title>Manajemen Kas - PTIK C 2024</title>
    <?php include '../includes/head.php'; ?>
    <style>
        .rotate-180 {
            transform: rotate(180deg);
        }

        /* Tambahan CSS untuk mobile menu */
        @media (max-width: 768px) {
            .mobile-menu-open {
                transform: translateX(0) !important;
            }
        }

        @keyframes shimmer {
            0% {
                background-position: -200% 0;
            }

            100% {
                background-position: 200% 0;
            }
        }

        .animate-shimmer {
            background: linear-gradient(90deg, rgba(255, 255, 255, 0) 0%, rgba(255, 255, 255, 0.2) 50%, rgba(255, 255, 255, 0) 100%);
            background-size: 200% 100%;
            animation: shimmer 2s infinite;
        }
    </style>

</head>

<body class="bg-slate-100">
    <?php include '../includes/menu_db.php'; ?>

    <main class="ml-0 lg:ml-48 pt-20 p-6 flex flex-col min-h-screen">
        <div class="max-w-6xl mx-auto flex-grow w-full">
            <!-- Header section -->
            <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4 mb-8">
                <div>
                    <h3 class="text-2xl font-black text-slate-800 tracking-tight">Manajemen Kas</h3>
                    <p class="text-xs font-bold text-slate-400 uppercase tracking-widest mt-1">Cash Flow & Savings Tracking</p>
                </div>
                <div class="flex gap-3">
                    <a href="manajemen_kas.php?action=export_excel" class="bg-emerald-500 hover:bg-emerald-600 text-white px-5 py-2.5 rounded-xl text-xs font-black flex items-center gap-2 transition-all shadow-lg shadow-emerald-500/20 active:scale-95">
                        <i class="fas fa-file-excel"></i> EXPORT DATA
                    </a>
                </div>
            </div>

            <!-- Stats Grid -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 mb-10">
                <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-sm hover:shadow-md transition-all group">
                    <div class="flex items-center gap-4">
                        <div class="w-12 h-12 rounded-xl bg-indigo-50 flex items-center justify-center text-indigo-600 border border-indigo-100 group-hover:bg-indigo-600 group-hover:text-white transition-all">
                            <i class="fas fa-wallet text-xl"></i>
                        </div>
                        <div>
                            <div class="text-[10px] font-black text-slate-400 uppercase tracking-widest leading-none mb-1">Kas Mahasiswa</div>
                            <div class="text-xl font-black text-slate-800 leading-none">Rp <?= number_format($total_kas, 0, ',', '.') ?></div>
                        </div>
                    </div>
                </div>

                <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-sm hover:shadow-md transition-all group">
                    <div class="flex items-center gap-4">
                        <div class="w-12 h-12 rounded-xl bg-emerald-50 flex items-center justify-center text-emerald-600 border border-emerald-100 group-hover:bg-emerald-600 group-hover:text-white transition-all">
                            <i class="fas fa-arrow-down text-xl"></i>
                        </div>
                        <div>
                            <div class="text-[10px] font-black text-slate-400 uppercase tracking-widest leading-none mb-1">Pemasukan lain</div>
                            <div class="text-xl font-black text-emerald-600 leading-none">Rp <?= number_format($total_other_masuk, 0, ',', '.') ?></div>
                        </div>
                    </div>
                </div>

                <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-sm hover:shadow-md transition-all group">
                    <div class="flex items-center gap-4">
                        <div class="w-12 h-12 rounded-xl bg-rose-50 flex items-center justify-center text-rose-600 border border-rose-100 group-hover:bg-rose-600 group-hover:text-white transition-all">
                            <i class="fas fa-arrow-up text-xl"></i>
                        </div>
                        <div>
                            <div class="text-[10px] font-black text-slate-400 uppercase tracking-widest leading-none mb-1">Dana Keluar</div>
                            <div class="text-xl font-black text-rose-600 leading-none">Rp <?= number_format($total_keluar, 0, ',', '.') ?></div>
                        </div>
                    </div>
                </div>

                <div class="bg-gradient-to-br from-indigo-900 to-slate-900 p-5 rounded-2xl shadow-xl shadow-indigo-900/20 border-0 group relative overflow-hidden">
                    <div class="relative z-10 flex items-center gap-4">
                        <div class="w-12 h-12 rounded-xl bg-white/10 flex items-center justify-center text-white border border-white/10">
                            <i class="fas fa-balance-scale text-xl"></i>
                        </div>
                        <div>
                            <div class="text-[10px] font-black text-indigo-300/60 uppercase tracking-widest leading-none mb-1">Saldo Tersedia</div>
                            <div class="text-xl font-black text-white leading-none">Rp <?= number_format($saldo, 0, ',', '.') ?></div>
                        </div>
                    </div>
                    <div class="absolute top-0 right-0 w-24 h-24 bg-white/5 rounded-full -mr-12 -mt-12 transition-transform group-hover:scale-150"></div>
                </div>
            </div>

            <!-- Transaction Form Row -->
            <div class="mb-8">
                <div class="bg-white p-4 rounded-2xl border border-slate-200 shadow-sm relative overflow-hidden">
                    <div class="absolute top-0 left-0 w-1 h-full bg-indigo-600"></div>
                    <h3 class="text-xs font-black text-slate-800 mb-4 flex items-center gap-2 uppercase tracking-widest">
                        <i class="fas fa-edit text-indigo-600"></i>
                        <?= $editing ? 'Perbarui Transaksi' : 'Catat Transaksi Baru' ?>
                    </h3>

                    <form method="POST" class="grid grid-cols-1 md:grid-cols-4 gap-4 items-end">
                        <div class="md:col-span-1">
                            <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest block mb-1.5 px-1">Jenis</label>
                            <select name="tipe" class="w-full bg-slate-50 border border-slate-200 text-xs font-bold text-slate-700 rounded-xl px-4 py-2.5 focus:ring-4 focus:ring-indigo-500/10 focus:border-indigo-500 transition-all cursor-pointer shadow-sm" required>
                                <option value="masuk" <?= $form_tipe === 'masuk' ? 'selected' : '' ?>>Pemasukan</option>
                                <option value="keluar" <?= $form_tipe === 'keluar' ? 'selected' : '' ?>>Pengeluaran</option>
                            </select>
                        </div>

                        <div class="md:col-span-1">
                            <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest block mb-1.5 px-1">Keterangan</label>
                            <input type="text" name="deskripsi" value="<?= htmlspecialchars($form_deskripsi) ?>" placeholder="Contoh: Iuran Mingguan..." class="w-full bg-slate-50 border border-slate-200 text-xs font-bold text-slate-700 rounded-xl px-4 py-2.5 focus:ring-4 focus:ring-indigo-500/10 focus:border-indigo-500 transition-all shadow-sm" required>
                        </div>

                        <div class="md:col-span-1">
                            <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest block mb-1.5 px-1">Nominal (Rp)</label>
                            <input type="number" step="1" name="jumlah" value="<?= htmlspecialchars($form_jumlah) ?>" placeholder="0" class="w-full bg-slate-50 border border-slate-200 text-xs font-black text-indigo-700 rounded-xl px-4 py-2.5 focus:ring-4 focus:ring-indigo-500/10 focus:border-indigo-500 transition-all shadow-sm" required>
                        </div>

                        <div class="md:col-span-1 flex gap-2 items-end">
                            <div class="flex-grow">
                                <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest block mb-1.5 px-1">Tanggal</label>
                                <input type="date" name="tanggal" value="<?= htmlspecialchars($form_tanggal) ?>" class="w-full bg-slate-50 border border-slate-200 text-xs font-bold text-slate-700 rounded-xl px-4 py-2.5 focus:ring-4 focus:ring-indigo-500/10 focus:border-indigo-500 transition-all shadow-sm">
                            </div>
                            <?php if ($editing): ?>
                                <input type="hidden" name="id" value="<?= (int)$eid ?>">
                                <button type="submit" name="edit_arus" class="bg-amber-500 hover:bg-amber-600 text-white w-11 h-[38px] flex items-center justify-center rounded-xl transition-all shadow-lg shadow-amber-500/20 active:scale-95" title="Simpan Perubahan">
                                    <i class="fas fa-check text-xs"></i>
                                </button>
                                <a href="manajemen_kas.php" class="bg-slate-100 hover:bg-slate-200 text-slate-500 w-10 h-[38px] flex items-center justify-center rounded-xl transition-all active:scale-95" title="Batal">
                                    <i class="fas fa-times text-xs"></i>
                                </a>
                            <?php else: ?>
                                <button type="submit" name="tambah_arus" class="bg-indigo-900 hover:bg-slate-800 text-white w-11 h-[38px] flex items-center justify-center rounded-xl transition-all shadow-lg shadow-indigo-900/30 active:scale-95">
                                    <i class="fas fa-plus text-xs"></i>
                                </button>
                            <?php endif; ?>
                        </div>
                    </form>
                </div>
            </div>

            <!-- History Section -->
            <div class="mb-10">
                <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
                    <div class="p-4 border-b border-slate-100 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 bg-slate-50/50">
                        <h4 class="text-xs font-black text-slate-800 uppercase tracking-widest flex items-center gap-2">
                            <i class="fas fa-history text-slate-400"></i>
                            Riwayat Kas
                        </h4>
                        <div class="relative w-full sm:w-64">
                            <i class="fas fa-search absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-[10px]"></i>
                            <input type="text" id="searchInput" placeholder="Cari transaksi..." class="w-full bg-white border border-slate-200 rounded-lg pl-8 pr-4 py-2 text-[10px] focus:ring-4 focus:ring-indigo-500/10 focus:border-indigo-500 transition-all shadow-sm">
                        </div>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full" id="historyTable">
                            <thead>
                                <tr class="bg-slate-50 border-b border-slate-100">
                                    <th class="px-4 py-3 text-left text-[10px] font-black text-slate-400 uppercase tracking-widest">Tgl / Tipe</th>
                                    <th class="px-4 py-3 text-left text-[10px] font-black text-slate-400 uppercase tracking-widest">Deskripsi</th>
                                    <th class="px-4 py-3 text-right text-[10px] font-black text-slate-400 uppercase tracking-widest">Jumlah</th>
                                    <th class="px-4 py-3 text-right text-[10px] font-black text-slate-400 uppercase tracking-widest">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-50">
                                <?php
                                $count = 0;
                                while ($row = mysqli_fetch_assoc($entries)):
                                    $count++;
                                    $hideClass = ($count > 3) ? 'hidden history-extra' : '';
                                ?>
                                    <tr class="hover:bg-slate-50/50 transition-colors group <?= $hideClass ?>">
                                        <td class="px-4 py-3 whitespace-nowrap">
                                            <div class="text-[10px] font-black text-slate-700 mb-0.5 tracking-tight">
                                                <?= !empty($row['tanggal']) ? date('d M Y', strtotime($row['tanggal'])) : '-' ?>
                                            </div>
                                            <?php if ($row['tipe'] === 'masuk'): ?>
                                                <span class="inline-flex items-center gap-1 text-emerald-600 text-[7px] font-black uppercase tracking-tighter bg-emerald-50 px-1.5 py-0.5 rounded-full border border-emerald-100/50">
                                                    MASUK
                                                </span>
                                            <?php else: ?>
                                                <span class="inline-flex items-center gap-1 text-rose-600 text-[7px] font-black uppercase tracking-tighter bg-rose-50 px-1.5 py-0.5 rounded-full border border-rose-100/50">
                                                    KELUAR
                                                </span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="px-4 py-3">
                                            <p class="text-[11px] font-bold text-slate-600 leading-normal max-w-xs md:max-w-md"><?= htmlspecialchars($row['deskripsi']) ?></p>
                                        </td>
                                        <td class="px-4 py-3 text-right">
                                            <span class="text-xs font-black <?= $row['tipe'] === 'masuk' ? 'text-emerald-600' : 'text-rose-600' ?>">
                                                <?= $row['tipe'] === 'masuk' ? '+' : '-' ?> Rp <?= number_format($row['jumlah'], 0, ',', '.') ?>
                                            </span>
                                        </td>
                                        <td class="px-4 py-3 text-right">
                                            <div class="flex items-center justify-end gap-1 opacity-0 group-hover:opacity-100 transition-all translate-x-2 group-hover:translate-x-0">
                                                <a href="manajemen_kas.php?action=edit&id=<?= $row['id'] ?>" class="w-7 h-7 rounded-lg bg-indigo-50 text-indigo-600 flex items-center justify-center hover:bg-indigo-600 hover:text-white transition-all shadow-sm">
                                                    <i class="fas fa-pen text-[9px]"></i>
                                                </a>
                                                <button onclick="confirmDelete(<?= $row['id'] ?>)" class="w-7 h-7 rounded-lg bg-rose-50 text-rose-600 flex items-center justify-center hover:bg-rose-600 hover:text-white transition-all shadow-sm">
                                                    <i class="fas fa-trash text-[9px]"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php if ($count > 3): ?>
                        <div class="p-3 bg-slate-50/50 border-t border-slate-100 text-center">
                            <button id="toggleHistoryBtn" onclick="toggleHistory()" class="text-[10px] font-black text-indigo-600 uppercase tracking-widest hover:text-indigo-800 transition-colors flex items-center gap-2 mx-auto">
                                <span>Lihat Semua Transaksi</span>
                                <i class="fas fa-chevron-down text-[8px] transition-transform duration-300"></i>
                            </button>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Summary Progress Section -->
            <div class="mb-10 bg-white p-6 rounded-2xl border border-slate-200 shadow-sm relative overflow-hidden group">
                <div class="absolute top-0 right-0 w-32 h-32 bg-indigo-50 rounded-full -mr-16 -mt-16 transition-transform group-hover:scale-110"></div>
                <div class="relative z-10">
                    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-5">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-xl bg-indigo-600 flex items-center justify-center text-white shadow-lg shadow-indigo-600/20">
                                <i class="fas fa-chart-line"></i>
                            </div>
                            <div>
                                <h4 class="text-xs font-black text-slate-800 uppercase tracking-widest">Pencapaian Mingguan</h4>
                                <p class="text-[10px] font-bold text-slate-400 mt-0.5 uppercase">MINGGU KE-<?= $current_week ?> PELAKSANAAN</p>
                            </div>
                        </div>
                        <div class="bg-indigo-50 px-4 py-2 rounded-xl border border-indigo-100 text-center min-w-[100px]">
                            <span class="text-xl font-black text-indigo-600"><?= $progress_percent ?>%</span>
                        </div>
                    </div>
                    <!-- Progress Bar -->
                    <div class="w-full bg-slate-100 h-3.5 rounded-full overflow-hidden flex shadow-inner border border-slate-200/50">
                        <div class="h-full bg-gradient-to-r from-indigo-600 via-indigo-500 to-indigo-400 transition-all duration-1000 ease-out relative group" style="width: <?= $progress_percent ?>%">
                            <div class="absolute inset-0 animate-shimmer opacity-30"></div>
                        </div>
                    </div>
                    <div class="flex flex-wrap justify-between mt-4 gap-4">
                        <div class="flex items-center gap-2 bg-slate-50 px-3 py-1.5 rounded-lg border border-slate-100">
                            <div class="w-2 h-2 rounded-full bg-indigo-600 animate-pulse"></div>
                            <span class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Terkumpul: </span>
                            <span class="text-[11px] font-black text-slate-700">Rp <?= number_format($total_kas, 0, ',', '.') ?></span>
                        </div>
                        <div class="flex items-center gap-2 bg-slate-50 px-3 py-1.5 rounded-lg border border-slate-100">
                            <i class="fas fa-bullseye text-slate-400 text-[10px]"></i>
                            <span class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Target: </span>
                            <span class="text-[11px] font-black text-slate-700">Rp <?= number_format($target_kas, 0, ',', '.') ?></span>
                        </div>
                    </div>

                    <!-- Weekly Detail List -->
                    <div class="mt-8 pt-8 border-t border-slate-100">
                        <div class="flex items-center justify-between mb-5">
                            <div>
                                <h5 class="text-[10px] font-black text-slate-800 uppercase tracking-widest">Detail Per Pertemuan</h5>
                                <p class="text-[9px] font-bold text-slate-400 uppercase mt-0.5">Monitoring Ketaatan Mingguan</p>
                            </div>
                            <div class="w-8 h-8 rounded-lg bg-slate-50 flex items-center justify-center border border-slate-100">
                                <i class="fas fa-list text-[10px] text-slate-400"></i>
                            </div>
                        </div>
                        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
                            <?php for ($i = 1; $i <= $current_week; $i++):
                                $paid = $weekly_data[$i] ?? 0;
                                $pct = ($total_students > 0) ? min(100, round(($paid / $total_students) * 100, 1)) : 0;
                                $barColor = $pct >= 100 ? 'bg-emerald-500' : ($pct >= 50 ? 'bg-indigo-500' : 'bg-amber-500');
                            ?>
                                <div class="bg-slate-50/50 rounded-xl p-3 border border-slate-100 flex items-center gap-3 hover:bg-white hover:shadow-md hover:border-indigo-100 transition-all group/week">
                                    <div class="w-9 h-9 rounded-lg bg-white border border-slate-100 flex flex-col items-center justify-center shrink-0 group-hover/week:border-indigo-200 shadow-sm transition-all">
                                        <span class="text-[7px] font-black text-slate-400 leading-none uppercase">Wk</span>
                                        <span class="text-xs font-black text-slate-700 leading-none mt-0.5"><?= $i ?></span>
                                    </div>
                                    <div class="flex-grow">
                                        <div class="flex justify-between items-center mb-1.5">
                                            <span class="text-[9px] font-black text-slate-500 truncate"><?= $paid ?> / <?= $total_students ?> Mhs</span>
                                            <span class="text-[9px] font-black text-slate-700"><?= $pct ?>%</span>
                                        </div>
                                        <div class="w-full bg-slate-200/50 h-1 rounded-full overflow-hidden">
                                            <div class="h-full <?= $barColor ?> rounded-full transition-all duration-500" style="width: <?= $pct ?>%"></div>
                                        </div>
                                    </div>
                                    <a href="../functions/kirim_kas.php?minggu_ke=<?= $i ?>&redirect=manajemen_kas" class="w-8 h-8 rounded-lg bg-emerald-50 text-emerald-600 flex items-center justify-center hover:bg-emerald-600 hover:text-white transition-all shadow-sm shrink-0 border border-emerald-100/50" title="Kirim Pengingat WA">
                                        <i class="fab fa-whatsapp text-xs"></i>
                                    </a>
                                </div>
                            <?php endfor; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Individual Progress List (Bottom) -->
            <div class="mb-12 bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
                <div class="p-6 border-b border-slate-100 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 bg-slate-50/50">
                    <div>
                        <h4 class="text-xs font-black text-slate-800 uppercase tracking-widest">Progres Mahasiswa</h4>
                        <p class="text-[10px] font-bold text-slate-400 mt-1 uppercase">Ketaatan Pembayaran Individu</p>
                    </div>
                    <div class="relative w-full sm:w-64">
                        <i class="fas fa-search absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-[10px]"></i>
                        <input type="text" id="mhsSearchInput" placeholder="Cari nama mahasiswa..." class="w-full bg-white border border-slate-200 rounded-lg pl-8 pr-4 py-2 text-[10px] focus:ring-4 focus:ring-indigo-500/10 focus:border-indigo-500 transition-all shadow-sm">
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-0 divide-y md:divide-y-0 md:divide-x divide-slate-100" id="mhsProgressList">
                    <?php foreach ($student_progress as $sp): ?>
                        <div class="p-5 hover:bg-slate-50/80 transition-all mhs-item group border-b border-slate-100 md:border-b-0">
                            <div class="flex items-center gap-3 mb-4">
                                <div class="w-10 h-10 rounded-xl overflow-hidden border border-slate-200 shrink-0 bg-white flex items-center justify-center group-hover:border-indigo-200 transition-all shadow-sm">
                                    <?php if (!empty($sp['foto'])): ?>
                                        <img src="<?= profilePhotoUrl($sp['foto'], '../') ?>" class="w-full h-full object-cover">
                                    <?php else: ?>
                                        <i class="fas fa-user-circle text-slate-200 text-xl"></i>
                                    <?php endif; ?>
                                </div>
                                <div class="truncate">
                                    <div class="text-[11px] font-black text-slate-700 truncate mhs-name leading-tight uppercase"><?= htmlspecialchars($sp['nama_lengkap']) ?></div>
                                    <div class="text-[9px] text-slate-400 font-bold tracking-tighter mt-0.5"><?= $sp['nim'] ?></div>
                                </div>
                            </div>
                            <div class="flex justify-between items-end mb-2">
                                <span class="text-[9px] font-black bg-slate-100 text-slate-500 px-2.5 py-1 rounded-lg uppercase tracking-tighter"><?= $sp['weeks_paid'] ?>/<?= $current_week ?> MG</span>
                                <span class="text-[11px] font-black tracking-tight <?= $sp['percent'] >= 100 ? 'text-indigo-600' : ($sp['percent'] >= 50 ? 'text-amber-600' : 'text-rose-600') ?>"><?= $sp['percent'] ?>%</span>
                            </div>
                            <div class="w-full bg-slate-100 h-2 rounded-full overflow-hidden shadow-inner border border-slate-200/20">
                                <div class="h-full <?= $sp['percent'] >= 100 ? 'bg-indigo-600' : ($sp['percent'] >= 50 ? 'bg-amber-500' : 'bg-rose-500') ?> transition-all duration-1000 shadow-sm" style="width: <?= $sp['percent'] ?>%"></div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        <?php include '../includes/footer_db.php'; ?>
    </main>
    <?php include '../includes/script_db.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        // Simple client-side search for transactions
        document.getElementById('searchInput').addEventListener('keyup', function() {
            let filter = this.value.toLowerCase();
            let rows = document.querySelectorAll('#historyTable tbody tr');
            const btnContainer = document.getElementById('toggleHistoryBtn')?.parentElement;

            rows.forEach(row => {
                let text = row.innerText.toLowerCase();
                if (filter === "") {
                    // Restore toggle state logic is tricky, so let's just show default or current state
                    // For simplicity, if filter is empty, we reload or re-apply classes
                    // But actually, style.display = '' will honor the 'hidden' class if we don't interfere
                    row.style.display = '';
                } else {
                    row.style.display = text.includes(filter) ? '' : 'none';
                }
            });

            if (btnContainer) {
                btnContainer.style.display = filter === "" ? "" : "none";
            }
        });

        function toggleHistory() {
            const extras = document.querySelectorAll('.history-extra');
            const btn = document.getElementById('toggleHistoryBtn');
            const icon = btn.querySelector('i');
            const text = btn.querySelector('span');

            const isCurrentlyHidden = extras[0].classList.contains('hidden');

            extras.forEach(el => el.classList.toggle('hidden'));

            if (isCurrentlyHidden) {
                text.innerText = 'Sembunyikan';
                icon.classList.add('rotate-180');
            } else {
                text.innerText = 'Lihat Semua Transaksi';
                icon.classList.remove('rotate-180');
            }
        }

        // Search for student progress
        document.getElementById('mhsSearchInput').addEventListener('keyup', function() {
            let filter = this.value.toLowerCase();
            let items = document.querySelectorAll('.mhs-item');

            items.forEach(item => {
                let name = item.querySelector('.mhs-name').innerText.toLowerCase();
                item.style.display = name.includes(filter) ? '' : 'none';
            });
        });

        function confirmDelete(id) {
            Swal.fire({
                title: 'Hapus Transaksi?',
                text: "Data yang dihapus tidak dapat dikembalikan!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#e11d48',
                cancelButtonColor: '#64748b',
                confirmButtonText: 'Ya, Hapus!',
                cancelButtonText: 'Batal'
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = `manajemen_kas.php?action=delete&id=${id}`;
                }
            });
        }

        <?php if (isset($_SESSION['success'])): ?>
            Swal.fire({
                icon: 'success',
                title: 'Berhasil!',
                text: '<?= $_SESSION['success'] ?>',
                timer: 3000,
                showConfirmButton: false
            });
            <?php unset($_SESSION['success']); ?>
        <?php endif; ?>

        <?php if (isset($_SESSION['error'])): ?>
            Swal.fire({
                icon: 'error',
                title: 'Gagal!',
                text: '<?= $_SESSION['error'] ?>',
            });
            <?php unset($_SESSION['error']); ?>
        <?php endif; ?>
    </script>
</body>

</html>