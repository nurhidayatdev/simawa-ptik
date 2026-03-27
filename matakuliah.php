<?php
include 'koneksi.php';

include 'includes/pesan.php';

$result = mysqli_query($koneksi, "SELECT * FROM mata_kuliah ORDER BY semester ASC, kode_matkul ASC");
// Fetch all rows into an array because mysqli_result cannot be iterated multiple times without seeking
$matkul_data = [];
while ($row = mysqli_fetch_assoc($result)) {
    $matkul_data[] = $row;
}
$beban_studi = [1 => 20, 2 => 20, 3 => 22, 4 => 22, 5 => 22, 6 => 22, 7 => 12, 8 => 6];
?>
<!DOCTYPE html>
<html lang="id">

<head>
    
    <title>Kurikulum - Kelas PTIK C 2024</title>
   <?php include 'includes/head.php'; ?>
</head>

<body class="bg-gray-50 dark:bg-slate-950 min-h-screen flex flex-col transition-colors duration-300">

    <?php include 'includes/header_main.php'; ?>

    <!-- spacer to avoid content hidden under fixed header -->
    <div class="h-10"></div>


    <div class="container mx-auto px-6 py-12 flex-grow">
        <h1 class="text-3xl font-bold mb-2 text-gray-800 dark:text-white transition-colors text-center">Kurikulum & Mata Kuliah</h1>
        <p class="text-gray-500 dark:text-slate-400 mb-8 transition-colors text-sm text-center">Daftar lengkap sebaran mata kuliah per semester beserta bobot SKS yang harus ditempuh selama masa studi.</p>
        <!-- Rest of the content remains the same -->
        <?php for ($s = 1; $s <= 8; $s++): ?>
            <h4 class="text-lg font-semibold mt-8 mb-4 text-gray-800 dark:text-white transition-colors">Semester <?= $s ?></h4>
            <div class="bg-white dark:bg-slate-900 rounded-xl shadow-sm border border-gray-200 dark:border-slate-800 overflow-hidden transition-colors">

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200 dark:divide-slate-800">
                        <thead class="bg-indigo-900 dark:bg-indigo-950">
                            <tr>
                                <th class="px-3 py-3 text-center text-xs font-bold text-white uppercase tracking-wider">No</th>
                                <th class="px-3 py-3 text-left text-xs font-bold text-white uppercase tracking-wider">Kode</th>
                                <th class="px-3 py-3 text-left text-xs font-bold text-white uppercase tracking-wider">Nama Mata Kuliah</th>
                                <th class="px-3 py-3 text-center text-xs font-bold text-white uppercase tracking-wider">SKS</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white dark:bg-slate-900 divide-y divide-slate-200 dark:divide-slate-800 transition-colors">
                            <?php
                            $no = 1;
                            $id = 1;
                            $found = false;
                            foreach ($matkul_data as $m) {
                                if ((int)$m['semester'] !== $s) continue;
                                $found = true;
                            ?>
                                <tr class="<?= ($id++ % 2 == 0) ? 'bg-slate-100 dark:bg-slate-800/40' : 'bg-white dark:bg-slate-900' ?> hover:bg-indigo-100 dark:hover:bg-indigo-900/40 transition-colors">
                                    <td class="px-3 py-2 whitespace-nowrap text-sm text-slate-800 dark:text-slate-300 text-center transition-colors"><?= $no++; ?></td>
                                    <td class="px-3 py-2 whitespace-nowrap text-sm text-slate-800 dark:text-slate-300 transition-colors"><?= htmlspecialchars($m['kode_matkul']) ?></td>
                                    <td class="px-3 py-2 text-sm text-slate-800 dark:text-slate-300 transition-colors"><?= htmlspecialchars($m['nama_matkul']) ?></td>
                                    <td class="px-3 py-2 whitespace-nowrap text-sm text-slate-800 dark:text-slate-300 text-center transition-colors"><?= htmlspecialchars($m['sks']) ?></td>
                                </tr>
                            <?php } ?>
                            <tr class="bg-indigo-50 dark:bg-indigo-950/40 font-bold transition-colors">
                                <td colspan="3" class="px-3 py-3 text-sm text-indigo-950 dark:text-indigo-300 text-right transition-colors">Jumlah Beban Studi Semester <?= $s ?></td>
                                <td class="px-3 py-3 whitespace-nowrap text-sm text-indigo-950 dark:text-indigo-300 text-center transition-colors"><?= $beban_studi[$s] ?></td>
                            </tr>
                        </tbody>
                    </table>
                </div>

            </div>
        <?php endfor; ?>
    </div>

    <?php include 'includes/footer_main.php'; ?>
<?php include 'includes/script_main.php'; ?>
</body>

</html>