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

$semester_filter = $_GET['semester'] ?? '';

$query = "SELECT * FROM mata_kuliah WHERE 1=1";
if (!empty($semester_filter)) {
    $query .= " AND semester = ?";
}
$query .= " ORDER BY semester ASC, kode_matkul ASC";

$stmt = mysqli_prepare($koneksi, $query);
if (!empty($semester_filter)) {
    mysqli_stmt_bind_param($stmt, "i", $semester_filter);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
} else {
    $res = mysqli_query($koneksi, $query);
}

$matkul_data = [];
while ($row = mysqli_fetch_assoc($res)) {
    $matkul_data[] = $row;
}

$beban_studi = [1 => 20, 2 => 20, 3 => 22, 4 => 22, 5 => 22, 6 => 22, 7 => 12, 8 => 6];



?>
<!DOCTYPE html>
<html lang="en">

<head>

    <title>Data Mata Kuliah - PTIK C 2024</title>
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
                <h3 class="text-xl font-bold text-slate-800">Data Mata Kuliah</h3>
                <div class="flex-col sm:flex-row gap-2 w-full sm:w-auto md:w-auto">
                    <form method="GET" class="w-full">
                        <div class="flex flex-row gap-2 w-full items-center">
                            <select name="semester" onchange="this.form.submit()"
                                class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-1 focus:ring-slate-400 text-xs  bg-white">
                                <option value="">Semua Semester</option>
                                <?php for ($i = 1; $i <= 8; $i++): ?>
                                    <option value="<?= $i ?>" <?= $semester_filter == $i ? 'selected' : '' ?>>Semester <?= $i ?></option>
                                <?php endfor; ?>
                            </select>
                            <?php if ($role === 'Administrator Utama'): ?>
                                <a href="../actions/tambah.php?tabel=mata_kuliah"
                                    class="bg-green-600 text-white px-3 py-2 rounded-lg hover:bg-green-800 text-center text-xs flex items-center justify-center "
                                    title="Tambah">
                                    <i class="fa fa-plus"></i>
                                </a>
                            <?php endif; ?>
                        </div>
                    </form>
                </div>
            </div>

            <?php
            $start_s = !empty($semester_filter) ? (int)$semester_filter : 1;
            $end_s = !empty($semester_filter) ? (int)$semester_filter : 8;
            for ($s = $start_s; $s <= $end_s; $s++):
                $semester_rows = array_filter($matkul_data, function ($m) use ($s) {
                    return (int)$m['semester'] === $s;
                });

                if (empty($semester_rows) && !empty($semester_filter)) continue;
            ?>
                <h4 class="text-lg font-semibold mt-4 mb-2">Semester <?= $s ?></h4>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200">
                        <thead class="bg-indigo-900">
                            <tr>
                                <th class="px-3 py-3 text-center text-xs font-bold text-white uppercase tracking-wider">No</th>
                                <th class="px-3 py-3 text-left text-xs font-bold text-white uppercase tracking-wider">Kode</th>
                                <th class="px-3 py-3 text-left text-xs font-bold text-white uppercase tracking-wider">Nama Mata Kuliah</th>
                                <th class="px-3 py-3 text-center text-xs font-bold text-white uppercase tracking-wider">SKS</th>
                                <?php if ($role === 'Administrator Utama'): ?>
                                    <th class="px-3 py-3 text-center text-xs font-bold text-white uppercase tracking-wider">
                                        Actions</th>
                                <?php endif; ?>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-slate-200">
                            <?php
                            $no = 1;
                            $id = 1;
                            if (empty($semester_rows)) {
                                echo '<tr><td colspan="5" class="px-3 py-4 text-center text-xs text-slate-500 italic">Tidak ada data untuk semester ini</td></tr>';
                            } else {
                                foreach ($semester_rows as $m) {
                            ?>
                                    <tr class="<?= ($id++ % 2 == 0) ? 'bg-slate-100' : 'bg-white' ?> hover:bg-indigo-100">
                                        <td class="px-3 py-2 whitespace-nowrap text-xs text-slate-800 text-center"><?= $no++; ?></td>
                                        <td class="px-3 py-2 whitespace-nowrap text-xs text-slate-800"><?= htmlspecialchars($m['kode_matkul']) ?></td>
                                        <td class="px-3 py-2 text-xs text-slate-800"><?= htmlspecialchars($m['nama_matkul']) ?></td>
                                        <td class="px-3 py-2 whitespace-nowrap text-xs text-slate-800 text-center"><?= htmlspecialchars($m['sks']) ?></td>
                                        <?php if ($role === 'Administrator Utama'): ?>
                                            <td class="px-3 py-2 whitespace-nowrap text-sm text-slate-500 text-center">
                                                <a href="../actions/edit.php?tabel=mata_kuliah&id=<?= $m['id']; ?>" title="Edit">
                                                    <i class="fa fa-edit text-blue-600 hover:text-blue-900 mr-3"></i>
                                                </a>

                                                <a href="../actions/hapus.php?tabel=mata_kuliah&id=<?= $m['id']; ?>" title="Hapus">
                                                    <i class="fa fa-trash text-red-600 hover:text-red-900"></i>
                                                </a>

                                            </td>
                                        <?php endif; ?>
                                    </tr>
                            <?php }
                            } ?>
                            <tr class="bg-indigo-50 font-bold">
                                <td colspan="3" class="px-3 py-3 text-xs text-indigo-950 text-right">Jumlah Beban Studi Semester <?= $s ?></td>
                                <td class="px-3 py-3 whitespace-nowrap text-xs text-indigo-950 text-center"><?= $beban_studi[$s] ?></td>
                                <?php if ($role === 'Administrator Utama'): ?>
                                    <td class="px-3 py-2 whitespace-nowrap text-sm text-slate-500 text-center"></td>
                                <?php endif; ?>
                            </tr>
                        </tbody>
                    </table>
                </div>
            <?php endfor; ?>
        </div>
        <?php include '../includes/footer_db.php'; ?>
    </main>
    <?php include '../includes/script_db.php'; ?>


</body>

</html>