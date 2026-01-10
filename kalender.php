<?php
include 'koneksi.php';

include 'includes/pesan.php';

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
    <title>Kalender Akademik - Kelas PTIK C 2024</title>
    <?php include 'includes/head.php'; ?>
</head>

<body class="bg-gray-50 min-h-screen flex flex-col">
    <?php include 'includes/header_main.php'; ?>

    <!-- spacer to avoid content hidden under fixed header -->
    <div class="h-10"></div>

    <main class="flex-grow">
        <section class="py-12 p-6">
            <div class="container mx-auto">
                <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4 mb-8">
                    <h2 class="text-3xl font-bold text-gray-800">Kalender Akademik</h2>

                    <!-- Filter -->
                    <form action="" method="GET" class="w-full md:w-auto flex items-center gap-3 bg-white p-2 rounded-lg shadow-sm border border-gray-200">
                        <label for="periode" class="text-xs font-medium text-gray-500 ml-2">Periode:</label>
                        <select name="periode_id" id="periode" onchange="this.form.submit()"
                            class="bg-gray-50 border border-gray-200 rounded-md px-3 py-1.5 text-xs focus:outline-none focus:ring-1 focus:ring-indigo-500">
                            <?php while ($p = mysqli_fetch_assoc($periods)): ?>
                                <option value="<?= $p['id'] ?>" <?= ($periode_id == $p['id']) ? 'selected' : '' ?>>
                                    <?= $p['tahun_periode'] ?> - <?= $p['semester_nama'] ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </form>
                </div>

                <!-- Table Layout (Matching pages/dosen.php) -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-slate-200">
                            <thead class="bg-indigo-900">
                                <tr>
                                    <th class="px-2 py-4 text-left text-xs font-bold text-white uppercase tracking-wider w-16">No</th>
                                    <th class="px-2 py-4 text-left text-xs font-bold text-white uppercase tracking-wider">Kegiatan</th>
                                    <th class="px-2 py-4 text-left text-xs font-bold text-white uppercase tracking-wider">Tanggal</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-slate-200">
                                <?php if (mysqli_num_rows($events) > 0): ?>
                                    <?php
                                    $no = 1;
                                    $id = 1;
                                    while ($event = mysqli_fetch_assoc($events)):
                                        $rowClass = ($id++ % 2 == 0) ? 'bg-slate-50' : 'bg-white';
                                    ?>
                                        <tr class="<?= $rowClass ?> hover:bg-indigo-50 transition-colors">
                                            <td class="px-2 py-2 whitespace-nowrap text-sm text-gray-600 text-center"><?= $no++ ?></td>
                                            <td class="px-2 py-2 text-sm font-semibold text-gray-800"><?= htmlspecialchars($event['kegiatan']) ?></td>
                                            <td class="px-2 py-2 text-sm text-gray-600 italic">
                                                <div class="flex items-center gap-2">
                                                    <i class="far fa-calendar-alt text-indigo-600 text-xs"></i>
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
                                        <td colspan="3" class="px-6 py-20 text-center text-gray-400 italic">
                                            <i class="fas fa-calendar-times text-4xl block mb-2 opacity-20"></i>
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