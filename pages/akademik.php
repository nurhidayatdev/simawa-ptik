<?php
include '../koneksi.php';

$query = mysqli_query($koneksi, "SELECT * FROM akademik ORDER BY id DESC");

// Get current month/year from request or default to current
$month = isset($_GET['month']) ? intval($_GET['month']) : intval(date('m'));
$year = isset($_GET['year']) ? intval($_GET['year']) : intval(date('Y'));

// Validate month/year
if ($month < 1) {
    $month = 12;
    $year--;
}
if ($month > 12) {
    $month = 1;
    $year++;
}

// Get active period
$q_periode = mysqli_query($koneksi, "SELECT id FROM periode WHERE status = 'aktif' LIMIT 1");
$periode = mysqli_fetch_assoc($q_periode);
$periode_id = $periode['id'] ?? 0;

// Days in Indonesian
$day_names = ['Min', 'Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab'];
$month_names = ['', 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];

// Calculate calendar grid
$first_day = mktime(0, 0, 0, $month, 1, $year);
$days_in_month = date('t', $first_day);
$start_day = date('w', $first_day); // 0 = Sunday

// Prepare events array (keyed by date)
$events = [];

// 1. Fetch Lecture Period Ranges from Kalender Akademik
$lecture_ranges = [];
$q_lecture = mysqli_query($koneksi, "
    SELECT tanggal_mulai, tanggal_selesai 
    FROM kalender_akademik 
    WHERE periode_id = '$periode_id' 
    AND (kegiatan LIKE '%Kuliah Bagian Pertama%' OR kegiatan LIKE '%Kuliah Bagian Kedua%')
");
while ($row = mysqli_fetch_assoc($q_lecture)) {
    $lecture_ranges[] = [
        'start' => strtotime($row['tanggal_mulai']),
        'end' => strtotime($row['tanggal_selesai'])
    ];
}

// Helper function to check if a date is within lecture periods
function isLectureDay($timestamp, $ranges)
{
    foreach ($ranges as $range) {
        if ($timestamp >= $range['start'] && $timestamp <= $range['end']) {
            return true;
        }
    }
    return false;
}

// 2. Fetch Jadwal Matkul (weekly recurring)
$day_map = ['Minggu' => 0, 'Senin' => 1, 'Selasa' => 2, 'Rabu' => 3, 'Kamis' => 4, 'Jumat' => 5, 'Sabtu' => 6];
$q_jadwal = mysqli_query($koneksi, "
    SELECT jm.hari, jk.jam_mulai, mk.nama_matkul 
    FROM jadwal_matkul jm
    JOIN mata_kuliah mk ON jm.matkul_id = mk.id
    LEFT JOIN jam_kuliah jk ON jm.jam_mulai_ke = jk.jam_ke
    WHERE jm.periode_id = '$periode_id'
    ORDER BY jk.jam_mulai ASC
");
$jadwal_by_day = [];
while ($row = mysqli_fetch_assoc($q_jadwal)) {
    $day_num = $day_map[$row['hari']] ?? -1;
    if ($day_num >= 0) {
        $jadwal_by_day[$day_num][] = [
            'type' => 'jadwal',
            'title' => $row['nama_matkul'],
            'time' => $row['jam_mulai'] ? date('H:i', strtotime($row['jam_mulai'])) : ''
        ];
    }
}

// Map jadwal to each day of the month (only on lecture days)
for ($d = 1; $d <= $days_in_month; $d++) {
    $date_ts = mktime(0, 0, 0, $month, $d, $year);
    $day_of_week = date('w', $date_ts);
    $date_key = sprintf('%04d-%02d-%02d', $year, $month, $d);

    // Only add jadwal if date is within lecture periods
    if (isset($jadwal_by_day[$day_of_week]) && isLectureDay($date_ts, $lecture_ranges)) {
        foreach ($jadwal_by_day[$day_of_week] as $j) {
            $events[$date_key][] = $j;
        }
    }
}

// 2. Fetch Kalender Akademik (only show on start date)
$month_start = sprintf('%04d-%02d-01', $year, $month);
$month_end = sprintf('%04d-%02d-%02d', $year, $month, $days_in_month);

$q_kalender = mysqli_query($koneksi, "
    SELECT kegiatan, tanggal_mulai, tanggal_selesai 
    FROM kalender_akademik 
    WHERE periode_id = '$periode_id'
    AND tanggal_mulai >= '$month_start' 
    AND tanggal_mulai <= '$month_end'
");
while ($row = mysqli_fetch_assoc($q_kalender)) {
    $date_key = $row['tanggal_mulai'];
    $events[$date_key][] = [
        'type' => 'kalender',
        'title' => $row['kegiatan'],
        'time' => '',
        'start' => $row['tanggal_mulai'],
        'end' => $row['tanggal_selesai']
    ];
}

// 3. Fetch Tugas (deadline dates)
$q_tugas = mysqli_query($koneksi, "
    SELECT t.deadline_tanggal, t.deadline_pukul, t.deskripsi, mk.nama_matkul, t.status
    FROM tugas t
    LEFT JOIN mata_kuliah mk ON t.matkul_id = mk.id
    WHERE t.periode_id = '$periode_id'
    AND t.deadline_tanggal >= '$month_start' 
    AND t.deadline_tanggal <= '$month_end'
");
while ($row = mysqli_fetch_assoc($q_tugas)) {
    $date_key = $row['deadline_tanggal'];
    $events[$date_key][] = [
        'type' => 'tugas',
        'title' => ($row['nama_matkul'] ?? 'Tugas'),
        'time' => $row['deadline_pukul'] ? date('H:i', strtotime($row['deadline_pukul'])) : '',
        'status' => $row['status']
    ];
}

// Navigation URLs
$prev_month = $month - 1;
$prev_year = $year;
if ($prev_month < 1) {
    $prev_month = 12;
    $prev_year--;
}

$next_month = $month + 1;
$next_year = $year;
if ($next_month > 12) {
    $next_month = 1;
    $next_year++;
}

$today = date('Y-m-d');
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#1e1b4b" />
    <link rel="icon" href="../backend/img/main/icon.png" />
    <title>Akademik - Kelas PTIK C 2024</title>
    <link href="../frontend/tailwind/src/output.css" rel="stylesheet">
    <link href="../frontend/tailwind/src/input.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link rel="prekoneksiect" href="https://fonts.googleapis.com">
    <link rel="prekoneksiect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Poppins', sans-serif;
        }

        .calendar-grid {
            display: grid;
            grid-template-columns: repeat(7, 1fr);
        }

        .event-dot {
            width: 6px;
            height: 6px;
            border-radius: 50%;
            display: inline-block;
            flex-shrink: 0;
        }

        .event-jadwal {
            background: #6366f1;
        }

        .event-kalender {
            background: #10b981;
        }

        .event-tugas {
            background: #f59e0b;
        }

        .event-tugas-done {
            background: #22c55e;
        }

        /* Mobile-only: show dots in a row */
        @media (max-width: 640px) {
            .calendar-cell {
                min-height: 60px !important;
                padding: 4px !important;
            }

            .event-text {
                display: none;
            }

            .event-row {
                flex-wrap: wrap;
                gap: 3px;
            }

            .event-dot {
                width: 8px;
                height: 8px;
            }
        }
    </style>
</head>

<body class="bg-gray-50 min-h-screen flex flex-col">
    <header class="bg-indigo-950 text-white shadow-lg fixed top-0 left-0 right-0 z-50">
        <div class="container mx-auto px-4 py-2 flex justify-between items-center">
            <!-- Logo -->
            <div class="flex items-center">
                <img src="../backend/img/main/logo.png" alt="Logo Universitas Negeri Makassar" class="h-10 w-10 mr-3">
                <div>
                    <h1 class="text-lg font-bold">PTIK C 2024</h1>
                    <p class="text-xs">Teknik Informatika dan Komputer - FT UNM</p>
                </div>
            </div>

            <!-- Hamburger Button -->
            <button id="menuToggle" class="md:hidden text-2xl focus:outline-none">
                <i class="fas fa-bars"></i>
            </button>

            <nav class="hidden md:block">
                <ul class="flex space-x-6">
                    <li>
                        <a href="../index.php" class="flex items-center hover:text-indigo-200 transition text-sm">
                            <i class="fas fa-home mr-2"></i>Beranda
                        </a>
                    </li>
                    <li>
                        <a href="mahasiswa.php" class="flex items-center hover:text-indigo-200 transition text-sm">
                            <i class="fas fa-user-graduate mr-2"></i>Mahasiswa
                        </a>
                    </li>
                    <li>
                        <a href="akademik.php" class="flex items-center hover:text-indigo-200 transition text-sm">
                            <i class="fas fa-graduation-cap mr-2"></i>Akademik
                        </a>
                    </li>
                    <li>
                        <a href="galeri.php" class="flex items-center hover:text-indigo-200 transition text-sm">
                            <i class="fas fa-images mr-2"></i>Galeri
                        </a>
                    </li>
                    <li>
                        <a href="../backend/login/login.php" class="flex items-center hover:text-indigo-200 transition text-sm">
                            <i class="fas fa-sign-in-alt mr-2"></i>Login
                        </a>
                    </li>
                </ul>
            </nav>
        </div>

        <nav id="mobileMenu" class="hidden md:hidden absolute top-full left-0 w-full bg-indigo-950 shadow-md z-50">
            <ul class="flex flex-col space-y-2 p-4">
                <li>
                    <a href="#" class="flex items-center space-x-3 hover:text-indigo-200 transition text-sm py-2">
                        <i class="fas fa-home w-5"></i><span>Beranda</span>
                    </a>
                </li>
                <li>
                    <a href="mahasiswa.php" class="flex items-center space-x-3 hover:text-indigo-200 transition text-sm py-2">
                        <i class="fas fa-user-graduate w-5"></i><span>Mahasiswa</span>
                    </a>
                </li>
                <li>
                    <a href="akademik.php" class="flex items-center space-x-3 hover:text-indigo-200 transition text-sm py-2">
                        <i class="fas fa-graduation-cap w-5"></i><span>Akademik</span>
                    </a>
                </li>
                <li>
                    <a href="galeri.php" class="flex items-center space-x-3 hover:text-indigo-200 transition text-sm py-2">
                        <i class="fas fa-images w-5"></i><span>Galeri</span>
                    </a>
                </li>
                <li>
                    <a href="../backend/login/login.php" class="flex items-center space-x-3 hover:text-indigo-200 transition text-sm py-2">
                        <i class="fas fa-sign-in-alt w-5"></i><span>Login</span>
                    </a>
                </li>
            </ul>
        </nav>
    </header>

    <!-- spacer to avoid content hidden under fixed header -->
    <div class="h-10"></div>

    <main class="flex-grow ">
        <section class="py-12 p-6">
            <div class="container mx-auto">
                <h2 class="text-2xl font-bold mb-6 text-gray-800">Berita & Informasi Akademik</h2>

                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
                    <!-- sample card -->
                    <?php
                    if (mysqli_num_rows($query) > 0) {
                        while ($data = mysqli_fetch_assoc($query)) {
                    ?>
                            <article class="bg-white rounded-xl shadow-md overflow-hidden hover:shadow-lg transition">

                                <div class="p-4">
                                    <h3 class="font-semibold text-lg mb-2"><?php echo $data['judul']; ?></h3>
                                    <p class="text-xs text-gray-600 mb-3"><?php echo $data['deskripsi']; ?></p>
                                    <a href="<?php echo $data['link']; ?>" class="text-indigo-700 font-medium text-sm">Baca lebih lanjut →</a>
                                </div>
                            </article>
                    <?php
                        }
                    } else {
                        echo '<div class="col-span-full text-center py-10 text-gray-500 italic">Belum ada data akademik yang tersedia.</div>';
                    }
                    ?>
                </div>
            </div>
        </section>
        <section class="ml-0 p-6 flex flex-col">
            <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-4 md:p-6">
                <!-- Header with Navigation -->
                <div class="flex flex-col md:flex-row justify-between items-center mb-6 gap-4">
                    <div>
                        <h1 class="text-2xl font-bold text-slate-800">Kalender Kelas</h1>
                        <p class="text-slate-500 text-sm mt-1">Jadwal kuliah, kalender akademik, dan deadline tugas</p>
                    </div>

                    <!-- Month Navigation -->
                    <div class="flex items-center gap-2 bg-slate-100 p-1 rounded-lg">
                        <a href="?month=<?= $prev_month ?>&year=<?= $prev_year ?>" class="p-2 hover:bg-white rounded-md transition-colors">
                            <i class="fa fa-chevron-left text-slate-600"></i>
                        </a>
                        <span class="px-4 font-bold text-indigo-900 text-sm min-w-[140px] text-center">
                            <?= $month_names[$month] ?> <?= $year ?>
                        </span>
                        <a href="?month=<?= $next_month ?>&year=<?= $next_year ?>" class="p-2 hover:bg-white rounded-md transition-colors">
                            <i class="fa fa-chevron-right text-slate-600"></i>
                        </a>
                    </div>
                </div>

                <!-- Legend -->
                <div class="flex flex-wrap gap-4 mb-4 text-xs">
                    <div class="flex items-center gap-2">
                        <span class="event-dot event-kalender"></span>
                        <span class="text-slate-600">Kalender Akademik</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="event-dot event-jadwal"></span>
                        <span class="text-slate-600">Jadwal Kuliah</span>
                    </div>

                    <div class="flex items-center gap-2">
                        <span class="event-dot event-tugas"></span>
                        <span class="text-slate-600">Deadline Tugas</span>
                    </div>
                </div>

                <!-- Calendar Grid -->
                <div class="border border-slate-200 rounded-lg overflow-hidden">
                    <!-- Day Headers -->
                    <div class="calendar-grid bg-indigo-900 text-white">
                        <?php foreach ($day_names as $dn): ?>
                            <div class="p-2 md:p-3 text-center text-xs font-bold uppercase"><?= $dn ?></div>
                        <?php endforeach; ?>
                    </div>

                    <!-- Calendar Days -->
                    <div class="calendar-grid bg-white">
                        <?php
                        // Empty cells before first day
                        for ($i = 0; $i < $start_day; $i++): ?>
                            <div class="calendar-cell p-1 sm:p-2 md:p-3 border-t border-l border-slate-100 bg-slate-50 min-h-[60px] sm:min-h-[80px] md:min-h-[100px]"></div>
                        <?php endfor; ?>

                        <?php for ($d = 1; $d <= $days_in_month; $d++):
                            $date_key = sprintf('%04d-%02d-%02d', $year, $month, $d);
                            $is_today = ($date_key === $today);
                            $day_events = $events[$date_key] ?? [];
                            $has_events = !empty($day_events);
                        ?>
                            <div class="calendar-cell p-1 sm:p-2 md:p-3 border-t border-l border-slate-100 min-h-[60px] sm:min-h-[80px] md:min-h-[100px] <?= $is_today ? 'bg-indigo-50 ring-2 ring-indigo-400 ring-inset' : '' ?> <?= $has_events ? 'cursor-pointer hover:bg-slate-50 transition-colors' : '' ?>"
                                <?php if ($has_events): ?>
                                onclick="showDatePopup('<?= $date_key ?>', <?= $d ?>)"
                                <?php endif; ?>>
                                <div class="flex justify-between items-start mb-1">
                                    <span class="text-xs sm:text-sm font-bold <?= $is_today ? 'text-indigo-700' : 'text-slate-700' ?>"><?= $d ?></span>
                                    <?php if ($is_today): ?>
                                        <span class="hidden sm:inline text-[8px] bg-indigo-600 text-white px-1 rounded">HARI INI</span>
                                    <?php endif; ?>
                                </div>

                                <!-- Events - Mobile: dots only, Desktop: dots + text -->
                                <div class="event-row flex sm:flex-col sm:space-y-1 overflow-hidden max-h-[40px] sm:max-h-[60px] md:max-h-[80px]">
                                    <?php
                                    $event_counts = ['jadwal' => 0, 'kalender' => 0, 'tugas' => 0, 'ultah' => 0];
                                    $shown = 0;
                                    foreach ($day_events as $evt):
                                        $event_counts[$evt['type']]++;
                                        if ($shown >= 4) continue;
                                        $shown++;

                                        $dot_class = 'event-' . $evt['type'];
                                        if ($evt['type'] === 'tugas' && isset($evt['status']) && $evt['status'] === 'selesai') {
                                            $dot_class = 'event-tugas-done';
                                        }
                                    ?>
                                        <div class="flex items-center gap-1 text-[10px] text-slate-600" title="<?= htmlspecialchars($evt['title']) ?>">
                                            <span class="event-dot <?= $dot_class ?>"></span>
                                            <span class="event-text truncate hidden sm:inline"><?= htmlspecialchars(mb_strimwidth($evt['title'], 0, 12, '..')) ?></span>
                                        </div>
                                    <?php endforeach; ?>

                                    <?php if (count($day_events) > 3): ?>
                                        <div class="event-text text-[9px] text-slate-400 italic hidden sm:block">+<?= count($day_events) - 3 ?> lainnya</div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endfor; ?>

                        <?php
                        // Fill remaining cells
                        $total_cells = $start_day + $days_in_month;
                        $remaining = (7 - ($total_cells % 7)) % 7;
                        for ($i = 0; $i < $remaining; $i++): ?>
                            <div class="calendar-cell p-1 sm:p-2 md:p-3 border-t border-l border-slate-100 bg-slate-50 min-h-[60px] sm:min-h-[80px] md:min-h-[100px]"></div>
                        <?php endfor; ?>
                    </div>
                </div>

                <!-- All Events This Month -->
                <div class="mt-6 grid grid-cols-1 lg:grid-cols-3 gap-4">
                    <!-- Kalender Akademik -->
                    <div class="p-4 bg-emerald-50 border border-emerald-200 rounded-xl">
                        <h3 class="font-bold text-emerald-900 mb-3 flex items-center gap-2 text-sm">
                            <span class="event-dot event-kalender" style="width:10px;height:10px;"></span>
                            Kalender Akademik
                        </h3>
                        <div class="space-y-2 max-h-[300px] overflow-y-auto">
                            <?php
                            // Collect kalender events
                            $kalender_list = [];
                            foreach ($events as $date => $day_events) {
                                foreach ($day_events as $evt) {
                                    if ($evt['type'] === 'kalender') {
                                        $kalender_list[] = [
                                            'date' => $date,
                                            'title' => $evt['title'],
                                            'start' => $evt['start'],
                                            'end' => $evt['end']
                                        ];
                                    }
                                }
                            }
                            if (!empty($kalender_list)):
                                foreach ($kalender_list as $k):
                                    $is_range = ($k['start'] !== $k['end']);
                            ?>
                                    <div class="flex items-center gap-2 bg-white/60 p-2 rounded-lg text-xs">
                                        <div class="font-bold text-emerald-700 min-w-[70px]">
                                            <?php if ($is_range): ?>
                                                <?= date('d M', strtotime($k['start'])) ?> - <?= date('d M', strtotime($k['end'])) ?>
                                            <?php else: ?>
                                                <?= date('d M', strtotime($k['start'])) ?>
                                            <?php endif; ?>
                                        </div>
                                        <span class="flex-1 text-slate-700"><?= htmlspecialchars($k['title']) ?></span>
                                    </div>
                                <?php endforeach;
                            else: ?>
                                <p class="text-xs text-slate-500 italic">Tidak ada kegiatan akademik.</p>
                            <?php endif; ?>
                        </div>
                    </div>


                    <!-- Jadwal Kuliah -->
                    <div class="p-4 bg-indigo-50 border border-indigo-200 rounded-xl">
                        <h3 class="font-bold text-indigo-900 mb-3 flex items-center gap-2 text-sm">
                            <span class="event-dot event-jadwal" style="width:10px;height:10px;"></span>
                            Jadwal Kuliah
                        </h3>
                        <div class="space-y-2 max-h-[300px] overflow-y-auto">
                            <?php
                            // Get jadwal from events (already filtered by lecture days)
                            $jadwal_list = [];
                            foreach ($events as $date => $day_events) {
                                foreach ($day_events as $evt) {
                                    if ($evt['type'] === 'jadwal') {
                                        // Use title as key to avoid duplicates
                                        $key = $evt['title'] . '_' . $evt['time'];
                                        if (!isset($jadwal_list[$key])) {
                                            // Get day name from date
                                            $day_of_week = date('w', strtotime($date));
                                            $day_name = array_search($day_of_week, $day_map);
                                            $jadwal_list[$key] = [
                                                'hari' => $day_name,
                                                'title' => $evt['title'],
                                                'time' => $evt['time']
                                            ];
                                        }
                                    }
                                }
                            }
                            if (!empty($jadwal_list)):
                                foreach ($jadwal_list as $j): ?>
                                    <div class="flex items-center gap-2 bg-white/60 p-2 rounded-lg text-xs">
                                        <span class="font-bold text-indigo-700 w-16"><?= $j['hari'] ?></span>
                                        <span class="flex-1 text-slate-700"><?= htmlspecialchars($j['title']) ?></span>
                                        <span class="text-indigo-600 font-medium"><?= $j['time'] ?></span>
                                    </div>
                                <?php endforeach;
                            else: ?>
                                <p class="text-xs text-slate-500 italic">Tidak ada jadwal kuliah bulan ini.</p>
                            <?php endif; ?>
                        </div>
                    </div>



                    <!-- Deadline Tugas -->
                    <div class="p-4 bg-amber-50 border border-amber-200 rounded-xl">
                        <h3 class="font-bold text-amber-900 mb-3 flex items-center gap-2 text-sm">
                            <span class="event-dot event-tugas" style="width:10px;height:10px;"></span>
                            Deadline Tugas
                        </h3>
                        <div class="space-y-2 max-h-[300px] overflow-y-auto">
                            <?php
                            // Collect tugas events
                            $tugas_list = [];
                            foreach ($events as $date => $day_events) {
                                foreach ($day_events as $evt) {
                                    if ($evt['type'] === 'tugas') {
                                        $tugas_list[] = [
                                            'date' => $date,
                                            'title' => $evt['title'],
                                            'time' => $evt['time'],
                                            'status' => $evt['status'] ?? 'belum'
                                        ];
                                    }
                                }
                            }
                            if (!empty($tugas_list)):
                                foreach ($tugas_list as $t):
                                    $is_done = ($t['status'] === 'selesai');
                            ?>
                                    <div class="flex items-center gap-2 bg-white/60 p-2 rounded-lg text-xs <?= $is_done ? 'opacity-60' : '' ?>">
                                        <span class="font-bold text-amber-700 w-16"><?= date('d M', strtotime($t['date'])) ?></span>
                                        <span class="flex-1 text-slate-700 <?= $is_done ? 'line-through' : '' ?>"><?= htmlspecialchars($t['title']) ?></span>
                                        <?php if ($t['time']): ?>
                                            <span class="text-amber-600 font-medium"><?= $t['time'] ?></span>
                                        <?php endif; ?>
                                        <?php if ($is_done): ?>
                                            <i class="fa fa-check-circle text-green-500"></i>
                                        <?php endif; ?>
                                    </div>
                                <?php endforeach;
                            else: ?>
                                <p class="text-xs text-slate-500 italic">Tidak ada tugas.</p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Date Popup Modal -->
        <div id="dateModal" class="fixed inset-0 bg-black/50 z-[100] hidden items-center justify-center p-4">
            <div class="bg-white rounded-xl shadow-2xl max-w-md w-full max-h-[80vh] overflow-hidden">
                <div class="bg-gradient-to-r from-indigo-600 to-purple-600 p-4 text-white">
                    <div class="flex justify-between items-center">
                        <h3 id="modalTitle" class="font-bold text-lg">Agenda Tanggal</h3>
                        <button onclick="closeDatePopup()" class="text-white/80 hover:text-white text-xl">
                            <i class="fa fa-times"></i>
                        </button>
                    </div>
                </div>
                <div id="modalContent" class="p-4 overflow-y-auto max-h-[60vh]">
                    <!-- Events will be inserted here -->
                </div>
            </div>
        </div>
    </main>

    <!-- Footer (sticky to bottom via flex layout) -->
    <footer class="bg-indigo-950 text-white py-4 mt-auto">
        <div class="container mx-auto px-4">
            <div class="grid md:grid-cols-4 gap-8">
                <div>
                    <h3 class="text-lg font-bold mb-4">PTIK C 2024</h3>
                    <p class="mb-4 text-xs">Jurusan Teknik Informatika dan Komputer</p>
                    <p class="text-xs">Fakultas Teknik</p>
                    <p class="text-xs">Universitas Negeri Makassar</p>
                </div>

                <div>
                    <h3 class="text-lg font-bold mb-4">Tautan Cepat</h3>
                    <ul class="space-y-1">
                        <li><a href="kalender.php" class="hover:text-indigo-200 transition text-xs">Kalender Akademik</a></li>
                        <li><a href="matakuliah.php" class="hover:text-indigo-200 transition text-xs">Kurikulum PTIK</a></li>
                        <li><a href="jadwal.php" class="hover:text-indigo-200 transition text-xs">Jadwal Mata Kuliah</a></li>
                        <li><a href="tugas.php" class="hover:text-indigo-200 transition text-xs">Tugas Mata Kuliah</a></li>
                    </ul>
                </div>

                <div>
                    <h3 class="text-lg font-bold mb-4">Kontak</h3>
                    <p class="mb-2 text-xs"><i class="fas fa-map-marker-alt mr-2 text-sm"></i> Jl. A.P. Pettarani, Makassar</p>
                    <p class="mb-2 text-xs"><i class="fas fa-envelope mr-2 text-sm"></i> ptikc2024@unm.ac.id</p>
                    <a href="https://www.instagram.com/ptikc_24">
                        <p class="mb-2 text-xs"><i class="fab fa-instagram mr-2 text-sm"></i></i> @ptikc_24</p>
                    </a>
                </div>
                <div>
                    <h3 class="text-lg font-bold mb-4">Kirim Pesan Anonim</h3>

                    <form action="" method="POST" class="space-y-2">
                        <textarea
                            name="pesan_anonim"
                            rows="3"
                            maxlength="300"
                            class="w-full p-3 rounded-lg bg-indigo-900/50 border border-indigo-800 text-white text-xs focus:outline-none focus:ring-2 focus:ring-indigo-500 placeholder-indigo-300/50"
                            placeholder="Tulis pesan rahasia atau saran di sini..."
                            required></textarea>
                        <button
                            type="submit"
                            name="kirim_pesan"
                            class="w-full bg-indigo-600 hover:bg-indigo-500 text-white py-2 rounded-lg text-xs font-semibold transition flex items-center justify-center space-x-2">
                            <i class="fas fa-paper-plane text-xs"></i>
                            <span>Kirim Sekarang</span>
                        </button>
                    </form>

                    <?php
                    // Logika PHP untuk simpan pesan ke database
                    if (isset($_POST['kirim_pesan'])) {
                        $pesan = mysqli_real_escape_string($koneksi, $_POST['pesan_anonim']);
                        if (!empty($pesan)) {
                            $query_pesan = "INSERT INTO pesan (isi_pesan) VALUES ('$pesan')";
                            if (mysqli_query($koneksi, $query_pesan)) {
                                // Kirim notifikasi ke Telegram
                                include_once '../backend/crud/kirim_pesan_telegram.php';
                                sendTelegramPesan($pesan);

                                echo "<p class='text-[10px] text-green-400 mt-2'><i class='fas fa-check-circle mr-1'></i> Pesan berhasil dikirim secara anonim!</p>";
                            }
                        }
                    }
                    ?>
                </div>
            </div>
        </div>
        </div>

        <div class="border-t border-indigo-800 mt-4 pt-4 text-center text-xs">
            <p>© 2025 Kelas PTIK C - Teknik Informatika dan Komputer FT UNM. All rights reserved.</p>
        </div>
        </div>
    </footer>

    <script>
        // Events data from PHP
        const eventsData = <?= json_encode($events) ?>;
        const monthNames = ['', 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];

        function formatDateIndo(dateStr) {
            if (!dateStr) return '';
            const d = new Date(dateStr);
            return `${d.getDate()} ${monthNames[d.getMonth() + 1]}`;
        }

        function showDatePopup(dateKey, day) {
            const modal = document.getElementById('dateModal');
            const title = document.getElementById('modalTitle');
            const content = document.getElementById('modalContent');

            const dateParts = dateKey.split('-');
            const monthName = monthNames[parseInt(dateParts[1])];
            title.textContent = `Agenda ${day} ${monthName} ${dateParts[0]}`;

            const dayEvents = eventsData[dateKey] || [];

            if (dayEvents.length === 0) {
                content.innerHTML = '<p class="text-slate-500 text-sm text-center py-4">Tidak ada agenda.</p>';
            } else {
                let html = '<div class="space-y-3">';
                dayEvents.forEach(evt => {
                    let dotClass = 'event-' + evt.type;
                    let typeLabel = {
                        'jadwal': 'Jadwal Kuliah',
                        'kalender': 'Kalender Akademik',
                        'tugas': 'Deadline Tugas'
                    } [evt.type] || '';
                    let bgClass = {
                        'jadwal': 'bg-indigo-50 border-indigo-200',
                        'kalender': 'bg-emerald-50 border-emerald-200',
                        'tugas': 'bg-amber-50 border-amber-200'
                    } [evt.type] || 'bg-slate-50';

                    if (evt.type === 'tugas' && evt.status === 'selesai') {
                        dotClass = 'event-tugas-done';
                    }

                    html += `
                        <div class="p-3 rounded-lg border ${bgClass}">
                            <div class="flex items-start gap-3">
                                <span class="event-dot ${dotClass} mt-1" style="width:10px;height:10px;flex-shrink:0;"></span>
                                <div class="flex-1">
                                    <div class="text-xs text-slate-500 mb-1">${typeLabel}</div>
                                    <div class="font-semibold text-slate-800">${evt.title}</div>
                                    ${evt.type === 'kalender' && evt.start && evt.end && evt.start !== evt.end ? 
                                        `<div class="text-[10px] text-emerald-600 font-bold mt-1"><i class="fa fa-calendar-range mr-1"></i>${formatDateIndo(evt.start)} - ${formatDateIndo(evt.end)}</div>` : ''
                                    }
                                    ${evt.time ? `<div class="text-sm text-slate-600 mt-1"><i class="fa fa-clock mr-1"></i>${evt.time}</div>` : ''}
                                    ${evt.status === 'selesai' ? '<div class="text-xs text-green-600 mt-1"><i class="fa fa-check-circle mr-1"></i>Selesai</div>' : ''}
                                </div>
                            </div>
                        </div>
                    `;
                });
                html += '</div>';
                content.innerHTML = html;
            }

            modal.classList.remove('hidden');
            modal.classList.add('flex');
        }

        function closeDatePopup() {
            const modal = document.getElementById('dateModal');
            modal.classList.add('hidden');
            modal.classList.remove('flex');
        }

        // Close modal on backdrop click
        document.getElementById('dateModal').addEventListener('click', function(e) {
            if (e.target === this) closeDatePopup();
        });

        // Close modal on Escape key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') closeDatePopup();
        });
        const menuToggle = document.getElementById('menuToggle');
        const mobileMenu = document.getElementById('mobileMenu');
        const menuIcon = menuToggle.querySelector('i');

        menuToggle.addEventListener('click', () => {
            mobileMenu.classList.toggle('hidden');
            if (mobileMenu.classList.contains('hidden')) {
                menuIcon.classList.remove('fa-xmark');
                menuIcon.classList.add('fa-bars');
            } else {
                menuIcon.classList.remove('fa-bars');
                menuIcon.classList.add('fa-xmark');
            }
        });
    </script>
</body>

</html>