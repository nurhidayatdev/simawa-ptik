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
    header("Location: arus_kas.php");
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
    header("Location: arus_kas.php");
    exit;
}

// handle delete
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    mysqli_query($koneksi, "DELETE FROM arus_kas WHERE id=$id");
    header("Location: arus_kas.php");
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

// handle export to CSV
if (isset($_GET['action']) && $_GET['action'] === 'export_csv') {
    // send headers to force download
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=arus_kas_' . date('Ymd_His') . '.csv');

    $output = fopen('php://output', 'w');
    // header row (omit created_at)
    fputcsv($output, ['ID', 'Tipe', 'Deskripsi', 'Jumlah', 'Tanggal']);

    $q = mysqli_query($koneksi, "SELECT * FROM arus_kas ORDER BY tanggal DESC, created_at DESC");
    while ($r = mysqli_fetch_assoc($q)) {
        // format tanggal as dd/mm/YYYY to be more friendly for Excel
        $tgl = '';
        if (!empty($r['tanggal']) && strtotime($r['tanggal']) !== false) {
            $tgl = date('d/m/Y', strtotime($r['tanggal']));
        }

        fputcsv($output, [
            $r['id'],
            $r['tipe'],
            $r['deskripsi'],
            $r['jumlah'],
            $tgl
        ]);
    }

    fclose($output);
    exit;
}

?>
<!DOCTYPE html>
<html lang="en">

<head>
    
    <title>Arus Kas - PTIK C 2024</title>
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

        /* live-search script removed from style block; see script at document end */
    </style>
  
</head>

<body class="bg-slate-100">
    <?php include '../includes/menu_db.php'; ?>

    <main class="ml-0 lg:ml-48 pt-20 p-6 flex flex-col min-h-screen">
        <div class="max-w-6xl mx-auto flex-grow w-full">
            <div class="grid grid-cols-1 sm:grid-cols-4 gap-4 mb-6">
                <div class="bg-white p-4 rounded shadow">
                    <div class="text-xs text-slate-500">Pemasukan dari Kas (Lunas)</div>
                    <div class="text-2xl font-bold text-indigo-900">Rp <?= number_format($total_kas, 0, ',', '.') ?></div>
                </div>
                <div class="bg-white p-4 rounded shadow">
                    <div class="text-xs text-slate-500">Pemasukan Lainnya</div>
                    <div class="text-2xl font-bold text-green-700">Rp <?= number_format($total_other_masuk, 0, ',', '.') ?></div>
                </div>
                <div class="bg-white p-4 rounded shadow">
                    <div class="text-xs text-slate-500">Total Pengeluaran</div>
                    <div class="text-2xl font-bold text-red-700">Rp <?= number_format($total_keluar, 0, ',', '.') ?></div>
                </div>
                <div class="bg-white p-4 rounded shadow">
                    <div class="text-xs text-slate-500">Saldo</div>
                    <div class="text-2xl font-bold text-indigo-900">Rp <?= number_format($saldo, 0, ',', '.') ?></div>
                </div>
            </div>


            <div class="bg-white p-4 rounded shadow mb-6">
                <h2 class="text-lg font-semibold mb-2">Tambah Pemasukan / Pengeluaran</h2>
                <form method="POST" class="grid grid-cols-1 sm:grid-cols-4 gap-2">
                    <select name="tipe" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-1 focus:ring-gray-400 text-xs" required>
                        <option value="masuk" <?= $form_tipe === 'masuk' ? 'selected' : '' ?>>Pemasukan</option>
                        <option value="keluar" <?= $form_tipe === 'keluar' ? 'selected' : '' ?>>Pengeluaran</option>
                    </select>
                    <input type="text" name="deskripsi" placeholder="Deskripsi" value="<?= htmlspecialchars($form_deskripsi) ?>" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-1 focus:ring-gray-400 text-xs">
                    <input type="number" step="0.01" name="jumlah" placeholder="Jumlah (Rp)" value="<?= htmlspecialchars($form_jumlah) ?>" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-1 focus:ring-gray-400 text-xs" required>
                    <input type="date" name="tanggal" value="<?= htmlspecialchars($form_tanggal) ?>" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-1 focus:ring-gray-400 text-xs">
                    <div class="sm:col-span-4">
                        <?php if ($editing): ?>
                            <input type="hidden" name="id" value="<?= (int)$eid ?>">
                            <button type="submit" name="edit_arus" class="mt-2 bg-yellow-600 text-white px-4 py-2 rounded text-xs">Simpan Perubahan</button>
                            <a href="arus_kas.php" class="ml-2 mt-2 inline-block text-xs text-slate-600">Batal</a>
                        <?php else: ?>
                            <button type="submit" name="tambah_arus" class="mt-2 bg-indigo-900 text-white px-4 py-2 rounded text-xs">Tambah</button>
                        <?php endif; ?>
                    </div>
                </form>
            </div>

            <div class="bg-white p-4 rounded shadow">
                <div class="flex justify-between items-center mb-2">
                    <h2 class="text-lg font-semibold">Riwayat Arus Kas</h2>
                    <a href="arus_kas.php?action=export_csv" class="bg-green-600 text-white px-4 py-2 rounded text-xs">Export CSV</a>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200 text-xs">
                        <thead class="bg-indigo-900 text-white">
                            <tr>
                                <th class="px-3 py-2 text-left">No</th>
                                <th class="px-3 py-2 text-left">Tipe</th>
                                <th class="px-3 py-2 text-left">Deskripsi</th>
                                <th class="px-3 py-2 text-right">Jumlah</th>
                                <th class="px-3 py-2 text-left">Tanggal</th>
                                <th class="px-3 py-2 text-left">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white">
                            <?php $no = 1;
                            while ($row = mysqli_fetch_assoc($entries)): ?>
                                <tr class="hover:bg-indigo-50">
                                    <td class="px-3 py-2"><?= $no++ ?></td>
                                    <td class="px-3 py-2"><?= $row['tipe'] ?></td>
                                    <td class="px-3 py-2"><?= htmlspecialchars($row['deskripsi']) ?></td>
                                    <td class="px-3 py-2 text-right">Rp <?= number_format($row['jumlah'], 0, ',', '.') ?></td>
                                    <td class="px-3 py-2"><?= !empty($row['tanggal']) ? date('d/m/Y', strtotime($row['tanggal'])) : '-' ?></td>
                                    <td class="px-3 py-2">
                                        <a href="arus_kas.php?action=edit&id=<?= $row['id'] ?>">
                                            <i class="fa fa-edit text-blue-600 hover:text-blue-900 mr-3"></i>
                                        </a>
                                        <a href="arus_kas.php?action=delete&id=<?= $row['id'] ?>">
                                            <i class="fa fa-trash text-red-600 hover:text-red-900 mr-3"></i>
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
</body>

</html>