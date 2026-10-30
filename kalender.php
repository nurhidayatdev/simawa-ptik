<?php
include 'koneksi.php';

// Get active period
$q_active = mysqli_query($koneksi, "SELECT * FROM periode WHERE status = 'aktif' LIMIT 1");
$active_period = mysqli_fetch_assoc($q_active);
$active_id = $active_period['id'] ?? 0;

// Filter by period if selected
$periode_id = $_GET['periode_id'] ?? $active_id;

// Fetch periods for dropdown
$periods = mysqli_query($koneksi, "SELECT * FROM periode ORDER BY semester_ke DESC");

// Fetch calendar events
$events = mysqli_query($koneksi, "SELECT * FROM kalender_akademik WHERE periode_id = '$periode_id' ORDER BY tanggal_mulai ASC");
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <title>Kalender Akademik - SIMAWA PTIK 2024</title>
    <?php include 'includes/head.php'; ?>
</head>

<body class="bg-gray-50 dark:bg-slate-950 min-h-screen flex flex-col transition-colors duration-300">

    <?php include 'includes/header_main.php'; ?>
 
    <!-- spacer to avoid content hidden under fixed header -->
    <div class="h-10"></div>
 
    <main class="container mx-auto px-6 py-12 flex-grow">
            <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4 mb-8">
                <div>
                    <h1 class="text-3xl font-bold mb-2 text-gray-800 dark:text-white transition-colors">Kalender Akademik</h1>
                    <p class="text-gray-500 dark:text-slate-400 transition-colors text-sm">Informasi lengkap agenda akademik semester mulai dari masa perkuliahan hingga jadwal ujian penting.</p>
                </div>

                <!-- Filter -->
                <form action="" method="GET" class="w-full md:w-auto flex items-center gap-3 bg-white dark:bg-slate-900 p-2 rounded-lg shadow-sm border border-gray-200 dark:border-slate-800 transition-colors">
                    <label for="periode" class="text-xs font-medium text-gray-500 dark:text-slate-400 ml-2 transition-colors">Periode:</label>
                    <select name="periode_id" id="periode" onchange="this.form.submit()"
                        class="bg-gray-50 dark:bg-slate-800 border border-gray-200 dark:border-slate-700 text-gray-700 dark:text-slate-200 rounded-md px-3 py-1.5 text-xs focus:outline-none focus:ring-1 focus:ring-indigo-500 transition-colors">
                        <?php while ($p = mysqli_fetch_assoc($periods)): ?>
                            <option value="<?= $p['id'] ?>" <?= ($periode_id == $p['id']) ? 'selected' : '' ?>>
                                <?= $p['tahun_periode'] ?> - <?= $p['semester_nama'] ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </form>
            </div>

                <!-- Table Layout (Matching pages/dosen.php) -->
                <div class="bg-white dark:bg-slate-900 rounded-xl shadow-sm border border-gray-200 dark:border-slate-800 overflow-hidden transition-colors">
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-slate-200 dark:divide-slate-800">
                            <thead class="bg-indigo-900">
                                <tr>
                                    <th class="px-2 py-4 text-left text-xs font-bold text-white uppercase tracking-wider w-16">No</th>
                                    <th class="px-2 py-4 text-left text-xs font-bold text-white uppercase tracking-wider">Kegiatan</th>
                                    <th class="px-2 py-4 text-left text-xs font-bold text-white uppercase tracking-wider">Tanggal</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white dark:bg-slate-900 divide-y divide-slate-200 dark:divide-slate-800 transition-colors">
                                <?php if (mysqli_num_rows($events) > 0): ?>
                                    <?php
                                    $no = 1;
                                    $id = 1;
                                    while ($event = mysqli_fetch_assoc($events)):
                                        $rowClass = ($id++ % 2 == 0) ? 'bg-slate-50 dark:bg-slate-800/40' : 'bg-white dark:bg-slate-900';
                                    ?>
                                        <tr class="<?= $rowClass ?> hover:bg-indigo-50 dark:hover:bg-indigo-900/40 transition-colors">
                                            <td class="px-2 py-2 whitespace-nowrap text-sm text-gray-600 dark:text-slate-400 text-center transition-colors"><?= $no++ ?></td>
                                            <td class="px-2 py-2 text-sm font-semibold text-gray-800 dark:text-slate-200 transition-colors"><?= htmlspecialchars($event['kegiatan']) ?></td>
                                            <td class="px-2 py-2 text-sm text-gray-600 dark:text-slate-400 italic transition-colors">
                                                <div class="flex items-center gap-2">
                                                    <i class="far fa-calendar-alt text-indigo-600 dark:text-indigo-400 text-xs transition-colors"></i>
                                                    <span><?= date('d M Y', strtotime($event['tanggal_mulai'])) ?></span>
                                                    <?php if ($event['tanggal_mulai'] != $event['tanggal_selesai']): ?>
                                                        <span>-</span>
                                                        <span><?= date('d M Y', strtotime($event['tanggal_selesai'])) ?></span>
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="3" class="px-6 py-20 text-center text-gray-400 dark:text-slate-500 italic transition-colors">
                                            <i class="fas fa-calendar-times text-4xl block mb-2 opacity-20 dark:opacity-10 transition-colors"></i>
                                            Belum ada agenda terdaftar untuk periode ini.
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </section>
    </main>

    <?php include 'includes/footer_main.php'; ?>
<?php include 'includes/script_main.php'; ?>
</body>

</html>