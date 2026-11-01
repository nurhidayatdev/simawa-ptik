<?php
session_start();
include '../koneksi.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../../index.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$nama_lengkap = $_SESSION['nama_lengkap'];
$foto = $_SESSION['foto'] ?? '';
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

    <title>Data Mata Kuliah - SIMAWA PTIK 2024</title>
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

<body class="bg-slate-100 dark:bg-slate-950 transition-colors duration-300">
    <?php include '../includes/menu_db.php'; ?>

    <main class="ml-0 lg:ml-48 pt-20 p-6 flex flex-col min-h-screen">
        <div class="bg-white dark:bg-slate-900 rounded-lg border border-slate-200 dark:border-slate-800 p-6 flex-grow w-full shadow-sm">
            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-6">
                <div>
                    <h1 class="text-xl font-bold text-slate-800 dark:text-slate-100">Data Mata Kuliah</h1>
                </div>
                <div class="flex flex-col sm:flex-row gap-2 w-full sm:w-auto">
                    <form method="GET" class="w-full sm:w-48">
                        <div class="flex flex-row gap-2 items-center">
                            <select name="semester" onchange="this.form.submit()"
                                class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-xs text-slate-700 dark:text-slate-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 transition-all appearance-none cursor-pointer">
                                <option value="">Semua Semester</option>
                                <?php for ($i = 1; $i <= 8; $i++): ?>
                                    <option value="<?= $i ?>" <?= $semester_filter == $i ? 'selected' : '' ?>>Semester <?= $i ?></option>
                                <?php endfor; ?>
                            </select>
                            <?php if ($role === 'Administrator Utama'): ?>
                                <a href="../actions/tambah.php?tabel=mata_kuliah"
                                    class="bg-indigo-600 hover:bg-indigo-700 text-white p-2.5 rounded-lg transition-all shadow-sm flex items-center justify-center shrink-0"
                                    title="Tambah">
                                    <i class="fa fa-plus text-xs"></i>
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
                if (empty($semester_rows) && empty($semester_filter)) continue;
            ?>
                <div class="mt-8 mb-4 flex items-center gap-3">
                    <div class="h-8 w-1 bg-indigo-600 dark:bg-indigo-500 rounded-full"></div>
                    <h4 class="text-lg font-bold text-slate-800 dark:text-slate-100 italic uppercase tracking-wider">Semester <?= $s ?></h4>
                </div>
                <div class="overflow-x-auto rounded-xl border border-slate-200 dark:border-slate-800 mb-8">
                    <table class="min-w-full divide-y divide-slate-200 dark:divide-slate-800">
                        <thead class="bg-indigo-900 dark:bg-slate-800">
                            <tr>
                                <th class="px-3 py-3 text-center text-[10px] font-bold text-white uppercase tracking-widest w-16">No</th>
                                <th class="px-3 py-3 text-left text-[10px] font-bold text-white uppercase tracking-widest w-24">Kode</th>
                                <th class="px-3 py-3 text-left text-[10px] font-bold text-white uppercase tracking-widest">Nama Mata Kuliah</th>
                                <th class="px-3 py-3 text-center text-[10px] font-bold text-white uppercase tracking-widest w-20">SKS</th>
                                <?php if ($role === 'Administrator Utama'): ?>
                                    <th class="px-3 py-3 text-center text-[10px] font-bold text-white uppercase tracking-widest w-24">Actions</th>
                                <?php endif; ?>
                            </tr>
                        </thead>
                        <tbody class="bg-white dark:bg-slate-900 divide-y divide-slate-200 dark:divide-slate-800">
                            <?php
                            $no = 1;
                            $id = 1;
                            if (empty($semester_rows)) {
                                echo '<tr><td colspan="5" class="px-3 py-6 text-center text-xs text-slate-500 dark:text-slate-400 italic">Tidak ada data untuk semester ini</td></tr>';
                            } else {
                                foreach ($semester_rows as $m) {
                                    $isEven = ($id++ % 2 == 0);
                                    $rowClass = ($isEven ? 'bg-slate-50/50 dark:bg-slate-800/20' : 'bg-white dark:bg-slate-900') . ' hover:bg-indigo-50 dark:hover:bg-indigo-900/20 transition-colors';
                            ?>
                                    <tr class="<?= $rowClass ?>">
                                        <td class="px-3 py-2 whitespace-nowrap text-xs text-slate-800 dark:text-slate-200 text-center font-medium"><?= $no++; ?></td>
                                        <td class="px-3 py-2 whitespace-nowrap text-xs text-slate-800 dark:text-slate-100 font-bold"><?= htmlspecialchars($m['kode_matkul']) ?></td>
                                        <td class="px-3 py-2 text-xs text-slate-800 dark:text-slate-200 font-semibold"><?= htmlspecialchars($m['nama_matkul']) ?></td>
                                        <td class="px-3 py-2 whitespace-nowrap text-xs text-center">
                                            <span class="bg-indigo-50 dark:bg-indigo-900/40 text-indigo-700 dark:text-indigo-300 px-2 py-0.5 rounded text-[10px] font-bold border border-indigo-100 dark:border-indigo-800/50"><?= htmlspecialchars($m['sks']) ?></span>
                                        </td>
                                        <?php if ($role === 'Administrator Utama'): ?>
                                            <td class="px-3 py-2 whitespace-nowrap text-sm text-center">
                                                <div class="flex items-center justify-center gap-3">
                                                    <a href="../actions/edit.php?tabel=mata_kuliah&id=<?= $m['id']; ?>" title="Edit" class="text-blue-600 dark:text-blue-400 hover:text-blue-900 dark:hover:text-blue-300 transition-colors">
                                                        <i class="fa fa-edit"></i>
                                                    </a>
                                                    <a href="../actions/hapus.php?tabel=mata_kuliah&id=<?= $m['id']; ?>" title="Hapus" class="text-red-600 dark:text-red-400 hover:text-red-900 dark:hover:text-red-300 transition-colors">
                                                        <i class="fa fa-trash"></i>
                                                    </a>
                                                </div>
                                            </td>
                                        <?php endif; ?>
                                    </tr>
                            <?php }
                            } ?>
                            <tr class="bg-slate-50 dark:bg-slate-800/50 font-bold">
                                <td colspan="3" class="px-4 py-3 text-[10px] text-slate-500 dark:text-slate-400 text-right uppercase tracking-wider">Total SKS Semester <?= $s ?></td>
                                <td class="px-3 py-3 whitespace-nowrap text-xs text-indigo-600 dark:text-indigo-400 text-center font-black"><?= $beban_studi[$s] ?></td>
                                <?php if ($role === 'Administrator Utama'): ?>
                                    <td class="px-3 py-2"></td>
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