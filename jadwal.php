<?php
include 'koneksi.php';

// Ambil periode aktif
$q_periode = mysqli_query($koneksi, "SELECT id, tahun_periode, semester_nama FROM periode WHERE status = 'aktif' LIMIT 1");
$periode_aktif = mysqli_fetch_assoc($q_periode);
$periode_id = (int)($periode_aktif['id'] ?? 0);

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
WHERE jm.periode_id = $periode_id
ORDER BY FIELD(jm.hari, 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu', 'Minggu'), jk_start.jam_mulai");
?>
<!DOCTYPE html>
<html lang="id">

<head>
   
    <title>Jadwal Kuliah - SIMAWA PTIK 2024</title>
   <?php include 'includes/head.php'; ?>
</head>

<body class="bg-gray-50 dark:bg-slate-950 min-h-screen flex flex-col transition-colors duration-300">

    <?php include 'includes/header_main.php'; ?>

    <!-- spacer to avoid content hidden under fixed header -->
    <div class="h-10"></div>


    <div class="container mx-auto px-6 py-12 flex-grow">
        <div class="text-center mb-8">
            <h1 class="text-3xl font-bold mb-2 text-gray-800 dark:text-white transition-colors">Jadwal Mata Kuliah</h1>
            <?php if ($periode_aktif): ?>
                <div class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold bg-indigo-50 dark:bg-indigo-900/40 text-indigo-700 dark:text-indigo-300 border border-indigo-100 dark:border-indigo-800/50 mb-3">
                    <i class="fas fa-calendar-alt text-[10px]"></i>
                    <span>Periode: <?= htmlspecialchars($periode_aktif['tahun_periode'] . ' - ' . $periode_aktif['semester_nama']) ?></span>
                </div>
            <?php endif; ?>
            <p class="text-gray-500 dark:text-slate-400 transition-colors text-sm">Pantau waktu perkuliahan, ruangan, dan dosen pengampu untuk membantu perencanaan studi harian Anda.</p>
        </div>
        

        <div class="bg-white dark:bg-slate-900 rounded-xl shadow-sm border border-gray-200 dark:border-slate-800 overflow-hidden transition-colors">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 dark:divide-slate-800">
                    <thead class="bg-indigo-900 dark:bg-indigo-950">
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
                    <tbody class="bg-white dark:bg-slate-900 divide-y divide-slate-200 dark:divide-slate-800 transition-colors">
                        <?php
                        $no = 1;
                        $id = 1;
                        if ($result && mysqli_num_rows($result) > 0) :
                            while ($row = mysqli_fetch_assoc($result)) : ?>
                                <tr class="<?= ($id++ % 2 == 0) ? 'bg-slate-100 dark:bg-slate-800/40' : 'bg-white dark:bg-slate-900' ?> hover:bg-indigo-100 dark:hover:bg-indigo-900/40 transition-colors">

                                    <td class="toggle-col hidden md:table-cell px-3 py-2 whitespace-nowrap text-xs text-slate-800 dark:text-slate-300 transition-colors"><?= $no++; ?></td>

                                    <td class="px-3 py-2 whitespace-nowrap text-xs text-slate-800 dark:text-slate-300 transition-colors"><?= $row['hari']; ?></td>
                                    <td class="toggle-col hidden md:table-cell px-3 py-2 whitespace-nowrap text-xs text-slate-800 dark:text-slate-300 transition-colors"><?= $row['jam_mulai_ke']; ?>-<?= $row['jam_selesai_ke']; ?></td>
                                    <td class="px-3 py-2 whitespace-nowrap text-xs text-slate-800 dark:text-slate-300 transition-colors"><?= date('H.i', strtotime($row['jam_mulai'])); ?> - <?= date('H.i', strtotime($row['jam_selesai'])); ?></td>

                                    <td class="px-3 py-2 text-xs text-slate-800 dark:text-slate-300 transition-colors"><?= $row['ruangan']; ?></td>
                                    <td class="px-3 py-2 text-xs text-slate-800 dark:text-slate-300 transition-colors"><?= $row['nama_matkul']; ?></td>
                                    <td class="toggle-col hidden md:table-cell px-3 py-2 whitespace-nowrap text-xs text-slate-800 dark:text-slate-300 transition-colors"><?= $row['sks']; ?></td>
                                    <td class="toggle-col hidden md:table-cell px-3 py-2 text-xs text-slate-800 dark:text-slate-300 transition-colors"><?= $row['dosen_pengampu']; ?></td>
                                    <td class="toggle-col hidden md:table-cell px-3 py-2 text-xs text-slate-800 dark:text-slate-300 transition-colors"><?= $row['dosen_mitra']; ?></td>

                                </tr>
                            <?php endwhile;
                        else: ?>
                            <tr>
                                <td colspan="9" class="px-4 py-8 text-center text-xs text-slate-500 dark:text-slate-400">
                                    <div class="flex flex-col items-center justify-center gap-1.5">
                                        <i class="fas fa-calendar-times text-2xl text-slate-300 dark:text-slate-600 mb-1"></i>
                                        <span>Tidak ada jadwal kuliah untuk periode aktif saat ini.</span>
                                    </div>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Rest of the content remains the same -->
        <div class="mt-4 md:hidden flex justify-end">
            <button id="toggleViewBtn" class="bg-indigo-600 dark:bg-indigo-700 text-white px-4 py-2 rounded-lg text-xs font-semibold shadow-md hover:bg-indigo-700 dark:hover:bg-indigo-800 transition">
                <i class="fas fa-eye mr-2"></i>Lihat Semua
            </button>
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