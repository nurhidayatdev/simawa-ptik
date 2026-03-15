<?php
include 'koneksi.php';

include 'includes/pesan.php';

$result = mysqli_query($koneksi, "SELECT 
    jm.id, jm.matkul_id, jm.hari, jm.jam_mulai_ke, jm.jam_selesai_ke, 
    jk_start.jam_mulai, jk_end.jam_selesai,
    r.nama_ruangan AS ruangan, mk.nama_matkul, mk.sks,
    dp.nama_dosen AS dosen_pengampu,
    dm.nama_dosen AS dosen_mitra
FROM jadwal_matkul jm
JOIN mata_kuliah mk ON jm.matkul_id = mk.id
LEFT JOIN ruangan r ON jm.ruangan_id = r.id
LEFT JOIN jam_kuliah jk_start ON jm.jam_mulai_ke = jk_start.jam_ke
LEFT JOIN jam_kuliah jk_end ON jm.jam_selesai_ke = jk_end.jam_ke
LEFT JOIN dosen dp ON jm.dosen_pengampu_id = dp.id
LEFT JOIN dosen dm ON jm.dosen_mitra_id = dm.id
ORDER BY jm.hari, jk_start.jam_mulai");
?>
<!DOCTYPE html>
<html lang="id">

<head>
   
    <title>Jadwal Kuliah - PTIK C 2024</title>
   <?php include 'includes/head.php'; ?>
</head>

<body class="bg-gray-50 min-h-screen flex flex-col">
    <?php include 'includes/header_main.php'; ?>

    <!-- spacer to avoid content hidden under fixed header -->
    <div class="h-10"></div>


    <div class="container mx-auto px-6 py-12 flex-grow">
        <h1 class="text-3xl font-bold mb-2 text-gray-800 scroll-reveal text-center">Jadwal Mata Kuliah</h1>
        <p class="text-gray-500 mb-8 scroll-reveal text-sm text-center">Pantau waktu perkuliahan, ruangan, dan dosen pengampu untuk membantu perencanaan studi harian Anda.</p>
        <!-- Rest of the content remains the same -->
        <div class="mb-4 md:hidden flex justify-end">
            <button id="toggleViewBtn" class="bg-indigo-600 text-white px-4 py-2 rounded-lg text-xs font-semibold shadow-md hover:bg-indigo-700 transition">
                <i class="fas fa-eye mr-2"></i>Lihat Semua
            </button>
        </div>

        <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200">
                    <thead class="bg-indigo-900">
                        <tr>
                            <th class="toggle-col hidden md:table-cell px-3 py-3 text-center text-xs font-bold text-white uppercase tracking-wider">
                                No</th>
                            <th class="px-3 py-3 text-center text-xs font-bold text-white uppercase tracking-wider">
                                Hari</th>
                            <th class="toggle-col hidden md:table-cell px-3 py-3 text-center text-xs font-bold text-white uppercase tracking-wider">
                                Jam</th>
                            <th class="px-3 py-3 text-center text-xs font-bold text-white uppercase tracking-wider">
                                Pukul</th>
                            <th class="px-3 py-3 text-center text-xs font-bold text-white uppercase tracking-wider">
                                Ruangan</th>
                            <th class="px-3 py-3 text-center text-xs font-bold text-white uppercase tracking-wider">
                                Mata Kuliah</th>
                            <th class="toggle-col hidden md:table-cell px-3 py-3 text-center text-xs font-bold text-white uppercase tracking-wider">
                                SKS</th>
                            <th class="toggle-col hidden md:table-cell px-3 py-3 text-center text-xs font-bold text-white uppercase tracking-wider">
                                Dosen Pengampu</th>
                            <th class="toggle-col hidden md:table-cell px-3 py-3 text-center text-xs font-bold text-white uppercase tracking-wider">
                                Dosen Mitra</th>
                        </tr>

                    </thead>
                    <tbody class="bg-white divide-y divide-slate-200">
                        <?php
                        $no = 1;
                        $id = 1;
                        while ($row = mysqli_fetch_assoc($result)) : ?>
                            <tr class="<?= ($id++ % 2 == 0) ? 'bg-slate-100' : 'bg-white' ?> hover:bg-indigo-100">

                                <td class="toggle-col hidden md:table-cell px-3 py-2 whitespace-nowrap text-xs text-slate-800"><?= $no++; ?></td>

                                <td class="px-3 py-2 whitespace-nowrap text-xs text-slate-800"><?= $row['hari']; ?></td>
                                <td class="toggle-col hidden md:table-cell px-3 py-2 whitespace-nowrap text-xs text-slate-800"><?= $row['jam_mulai_ke']; ?>-<?= $row['jam_selesai_ke']; ?></td>
                                <td class="px-3 py-2 whitespace-nowrap text-xs text-slate-800"><?= date('H.i', strtotime($row['jam_mulai'])); ?> - <?= date('H.i', strtotime($row['jam_selesai'])); ?></td>

                                <td class="px-3 py-2 text-xs text-slate-800"><?= $row['ruangan']; ?></td>
                                <td class="px-3 py-2 text-xs text-slate-800"><?= $row['nama_matkul']; ?></td>
                                <td class="toggle-col hidden md:table-cell px-3 py-2 whitespace-nowrap text-xs text-slate-800"><?= $row['sks']; ?></td>
                                <td class="toggle-col hidden md:table-cell px-3 py-2 text-xs text-slate-800"><?= $row['dosen_pengampu']; ?></td>
                                <td class="toggle-col hidden md:table-cell px-3 py-2 text-xs text-slate-800"><?= $row['dosen_mitra']; ?></td>

                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <?php include 'includes/footer_main.php'; ?>
<?php include 'includes/script_main.php'; ?>
    <script>
        

        // Toggle View for Mobile Table
        const toggleViewBtn = document.getElementById('toggleViewBtn');
        if (toggleViewBtn) {
            toggleViewBtn.addEventListener('click', () => {
                const hiddenCols = document.querySelectorAll('.toggle-col');
                const isHidden = hiddenCols[0].classList.contains('hidden');

                hiddenCols.forEach(col => {
                    col.classList.toggle('hidden');
                });

                // Update button text and icon
                if (isHidden) {
                    toggleViewBtn.innerHTML = '<i class="fas fa-eye-slash mr-2"></i>Lihat Sedikit';
                } else {
                    toggleViewBtn.innerHTML = '<i class="fas fa-eye mr-2"></i>Lihat Semua';
                }
            });
        }
    </script>
</body>

</html>