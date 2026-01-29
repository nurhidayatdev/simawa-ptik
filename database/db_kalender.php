<?php
session_start();
include '../koneksi.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../../index.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$nama_lengkap = $_SESSION['nama_lengkap'];
$foto = $_SESSION['foto'];
$role = $_SESSION['role'];

// Get active period
$q_active = mysqli_query($koneksi, "SELECT * FROM periode WHERE status = 'aktif' LIMIT 1");
$active_period = mysqli_fetch_assoc($q_active);
$active_id = $active_period['id'] ?? 0;

// Filter by period if selected
$periode_id = $_GET['periode_id'] ?? $active_id;

// Fetch periods for dropdown
$periods_res = mysqli_query($koneksi, "SELECT * FROM periode ORDER BY semester_ke DESC");

// Fetch calendar events filtered by period
$stmt = mysqli_prepare($koneksi, "
    SELECT k.*, p.tahun_periode, p.semester_nama 
    FROM kalender_akademik k
    JOIN periode p ON k.periode_id = p.id
    WHERE k.periode_id = ?
    ORDER BY k.tanggal_mulai ASC
");
mysqli_stmt_bind_param($stmt, "i", $periode_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

// If this is an AJAX request (optional, keeping structure if needed for future)
if (isset($_GET['ajax']) && $_GET['ajax'] == '1') {
    $no = 1;
    $id = 1;
    if (mysqli_num_rows($result) > 0) {
        while ($row = mysqli_fetch_assoc($result)) {
            $rowClass = (($id++ % 2 == 0) ? 'bg-slate-100' : 'bg-white') . ' hover:bg-indigo-100';
            echo '<tr class="' . $rowClass . '">';
            echo '<td class="px-3 py-2 whitespace-nowrap text-xs text-slate-800">' . ($no++) . '</td>';
            echo '<td class="px-3 py-2 text-xs font-semibold text-slate-800">' . htmlspecialchars($row['kegiatan']) . '</td>';
            echo '<td class="px-3 py-2 text-xs text-slate-600">' . date('d/m/Y', strtotime($row['tanggal_mulai'])) . '</td>';
            echo '<td class="px-3 py-2 text-xs text-slate-600">' . date('d/m/Y', strtotime($row['tanggal_selesai'])) . '</td>';
            echo '<td class="px-3 py-2 text-xs text-slate-600">' . htmlspecialchars($row['tahun_periode']) . ' - ' . htmlspecialchars($row['semester_nama']) . '</td>';
            if ($role === 'Administrator Utama'):
                echo '<td class="px-3 py-2 whitespace-nowrap text-center text-sm text-slate-500">';
                echo '<a href="../actions/edit.php?tabel=kalender_akademik&id=' . $row['id'] . '" title="Edit" class="text-blue-600 hover:text-blue-900 mr-3"><i class="fa fa-edit"></i></a>';
                echo '<a href="../actions/hapus.php?tabel=kalender_akademik&id=' . $row['id'] . '" title="Hapus" onclick="return confirm(\'Hapus agenda ini?\');" class="text-red-600 hover:text-red-900"><i class="fa fa-trash"></i></a>';
                echo '</td>';
            endif;
            echo '</tr>';
        }
    } else {
        echo '<tr><td colspan="6" class="px-6 py-10 text-center text-xs text-gray-500 italic">Data kalender tidak ditemukan.</td></tr>';
    }
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">

<head>

    <title>Data Kalender Akademik - PTIK C 2024</title>
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
    </style>

</head>

<body class="bg-slate-100">
    <?php include '../includes/menu_db.php'; ?>



    <main class="ml-0 lg:ml-48 pt-20 p-6 flex flex-col min-h-screen">
        <div class="bg-white rounded-lg border border-slate-200 p-6 flex-grow w-full">
            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-6">
                <div>
                    <h1 class="text-xl font-bold text-slate-800">Kalender Akademik</h1>
                    <p class="text-xs text-gray-500">Kelola agenda dan jadwal perkuliahan.</p>
                </div>
                <div class="flex flex-row gap-2 items-center w-full sm:w-auto">
                    <form action="" method="GET" class="flex items-center gap-2 bg-white px-3 py-1.5 rounded-lg border border-slate-200">
                        <label for="periode" class="text-[10px] font-medium text-slate-500">Periode:</label>
                        <select name="periode_id" id="periode" onchange="this.form.submit()"
                            class="bg-transparent border-none text-xs focus:outline-none focus:ring-0 p-0 pr-6">
                            <?php while ($p = mysqli_fetch_assoc($periods_res)): ?>
                                <option value="<?= $p['id'] ?>" <?= ($periode_id == $p['id']) ? 'selected' : '' ?>>
                                    <?= $p['tahun_periode'] ?> - <?= $p['semester_nama'] ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </form>
                    <?php if ($role === 'Administrator Utama'): ?>
                        <a href="../actions/tambah.php?tabel=kalender_akademik" class="bg-green-600 text-white px-3 py-2 rounded-lg hover:bg-green-800 text-center text-xs flex items-center justify-center transition-colors" title="Tambah">
                            <i class="fa fa-plus"></i>
                        </a>
                    <?php endif; ?>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200">
                    <thead class="bg-indigo-900">
                        <tr>
                            <th class="px-3 py-3 text-left text-xs font-bold text-white uppercase tracking-wider">No</th>
                            <th class="px-3 py-3 text-left text-xs font-bold text-white uppercase tracking-wider">Kegiatan</th>
                            <th class="px-3 py-3 text-left text-xs font-bold text-white uppercase tracking-wider">Mulai</th>
                            <th class="px-3 py-3 text-left text-xs font-bold text-white uppercase tracking-wider">Selesai</th>
                            <th class="px-3 py-3 text-left text-xs font-bold text-white uppercase tracking-wider">Periode</th>
                            <?php if ($role === 'Administrator Utama'): ?>
                                <th class="px-3 py-3 text-xs font-bold text-white uppercase tracking-wider text-center">Actions</th>
                            <?php endif; ?>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-slate-200">
                        <?php
                        $no = 1;
                        $id = 1;
                        if (mysqli_num_rows($result) > 0) {
                            while ($row = mysqli_fetch_assoc($result)) {
                                $rowClass = (($id++ % 2 == 0) ? 'bg-slate-100' : 'bg-white') . ' hover:bg-indigo-100';
                        ?>
                                <tr class="<?= $rowClass ?>">
                                    <td class="px-3 py-2 whitespace-nowrap text-xs text-slate-800"><?= $no++ ?></td>
                                    <td class="px-3 py-2 text-xs text-slate-800"><?= htmlspecialchars($row['kegiatan']) ?></td>
                                    <td class="px-3 py-2 text-xs text-slate-800"><?= date('d/m/Y', strtotime($row['tanggal_mulai'])) ?></td>
                                    <td class="px-3 py-2 text-xs text-slate-800"><?= date('d/m/Y', strtotime($row['tanggal_selesai'])) ?></td>
                                    <td class="px-3 py-2 text-xs text-slate-800"><?= htmlspecialchars($row['tahun_periode']) ?> - <?= htmlspecialchars($row['semester_nama']) ?></td>
                                    <?php if ($role === 'Administrator Utama'): ?>
                                        <td class="px-3 py-2 whitespace-nowrap text-center text-sm text-slate-500">
                                            <a href="../actions/edit.php?tabel=kalender_akademik&id=<?= $row['id'] ?>" title="Edit" class="text-blue-600 hover:text-blue-900 mr-3">
                                                <i class="fa fa-edit"></i>
                                            </a>
                                            <a href="../actions/hapus.php?tabel=kalender_akademik&id=<?= $row['id'] ?>" title="Hapus" onclick="return confirm('Hapus agenda ini?');" class="text-red-600 hover:text-red-900">
                                                <i class="fa fa-trash"></i>
                                            </a>
                                        </td>
                                    <?php endif; ?>
                                </tr>
                        <?php
                            }
                        } else {
                            echo '<tr><td colspan="6" class="px-6 py-10 text-center text-xs text-gray-500 italic">Data kalender tidak ditemukan.</td></tr>';
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php include '../includes/footer_db.php'; ?>
    </main>
    <?php include '../includes/script_db.php'; ?>
</body>

</html>