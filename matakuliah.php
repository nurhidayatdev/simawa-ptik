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

<body class="bg-gray-50 min-h-screen flex flex-col">
    <?php include 'includes/header_main.php'; ?>

    <!-- spacer to avoid content hidden under fixed header -->
    <div class="h-10"></div>


    <div class="container mx-auto py-6 mt-4 p-6">
        <h1 class="text-2xl font-bold mb-6 text-center text-indigo-950">Matakuliah PTIK</h1>
        <!-- Rest of the content remains the same -->
        <?php for ($s = 1; $s <= 8; $s++): ?>
            <h4 class="text-lg font-semibold mt-4 mb-2">Semester <?= $s ?></h4>
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200">
                        <thead class="bg-indigo-900">
                            <tr>
                                <th class="px-3 py-3 text-center text-xs font-bold text-white uppercase tracking-wider">No</th>
                                <th class="px-3 py-3 text-left text-xs font-bold text-white uppercase tracking-wider">Kode</th>
                                <th class="px-3 py-3 text-left text-xs font-bold text-white uppercase tracking-wider">Nama Mata Kuliah</th>
                                <th class="px-3 py-3 text-center text-xs font-bold text-white uppercase tracking-wider">SKS</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-slate-200">
                            <?php
                            $no = 1;
                            $id = 1;
                            $found = false;
                            foreach ($matkul_data as $m) {
                                if ((int)$m['semester'] !== $s) continue;
                                $found = true;
                            ?>
                                <tr class="<?= ($id++ % 2 == 0) ? 'bg-slate-100' : 'bg-white' ?> hover:bg-indigo-100">
                                    <td class="px-3 py-2 whitespace-nowrap text-sm text-slate-800 text-center"><?= $no++; ?></td>
                                    <td class="px-3 py-2 whitespace-nowrap text-sm text-slate-800"><?= htmlspecialchars($m['kode_matkul']) ?></td>
                                    <td class="px-3 py-2 text-sm text-slate-800"><?= htmlspecialchars($m['nama_matkul']) ?></td>
                                    <td class="px-3 py-2 whitespace-nowrap text-sm text-slate-800 text-center"><?= htmlspecialchars($m['sks']) ?></td>
                                </tr>
                            <?php } ?>
                            <tr class="bg-indigo-50 font-bold">
                                <td colspan="3" class="px-3 py-3 text-sm text-indigo-950 text-right">Jumlah Beban Studi Semester <?= $s ?></td>
                                <td class="px-3 py-3 whitespace-nowrap text-sm text-indigo-950 text-center"><?= $beban_studi[$s] ?></td>
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