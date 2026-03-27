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

// 3. Fetch Global Tugas (with completion status for this user)
$q_tugas = mysqli_query($koneksi, "
    SELECT t.id, t.deadline_tanggal, t.deadline_pukul, t.deskripsi, mk.nama_matkul, gd.id as is_done
    FROM tugas t
    LEFT JOIN mata_kuliah mk ON t.matkul_id = mk.id
    LEFT JOIN tugas_global_done gd ON t.id = gd.tugas_id AND gd.user_id = '$user_id' AND gd.periode_id = '$periode_id'
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
        'status' => $row['is_done'] ? 'selesai' : 'belum'
    ];
}

// 4. Fetch Personal Tugas
$q_personal = mysqli_query($koneksi, "
    SELECT judul, deadline, status 
    FROM tugas_personal 
    WHERE user_id = '$user_id' AND periode_id = '$periode_id'
    AND DATE(deadline) >= '$month_start' 
    AND DATE(deadline) <= '$month_end'
");
while ($row = mysqli_fetch_assoc($q_personal)) {
    $date_key = date('Y-m-d', strtotime($row['deadline']));
    $events[$date_key][] = [
        'type' => 'personal',
        'title' => $row['judul'],
        'time' => date('H:i', strtotime($row['deadline'])),
        'status' => $row['status']
    ];
}

// 5. Fetch Piket Proyektor (based on Minggu Ke)
$lecture_start = "2026-01-19"; // Same as kirim_piket_reminder.php
$q_piket = mysqli_query($koneksi, "
    SELECT p.minggu_ke, jm.hari, mk.nama_matkul, m.nama_lengkap
    FROM piket_proyektor p
    JOIN mahasiswa m ON p.mahasiswa_id = m.id
    JOIN jadwal_matkul jm ON p.jadwal_matkul_id = jm.id
    JOIN mata_kuliah mk ON jm.matkul_id = mk.id
    WHERE p.periode_id = '$periode_id' AND p.mahasiswa_id = '$user_id'
");
$piket_by_day = [];
while ($row = mysqli_fetch_assoc($q_piket)) {
    $piket_by_day[$row['minggu_ke']][$row['hari']][] = [
        'matkul' => $row['nama_matkul'],
        'nama' => $row['nama_lengkap']
    ];
}

// Map Indonesian days to numeric for calculation
$days_eng_to_ind = [
    'Monday' => 'Senin',
    'Tuesday' => 'Selasa',
    'Wednesday' => 'Rabu',
    'Thursday' => 'Kamis',
    'Friday' => 'Jumat',
    'Saturday' => 'Sabtu',
    'Sunday' => 'Minggu'
];

// Combine all into $events
for ($d = 1; $d <= $days_in_month; $d++) {
    $date_key = sprintf('%04d-%02d-%02d', $year, $month, $d);
    $ts = strtotime($date_key);
    $day_name_ind = $days_eng_to_ind[date('l', $ts)];

    // Calculate Minggu Ke
    $diff = $ts - strtotime($lecture_start);
    $days_diff = floor($diff / (60 * 60 * 24));
    $current_minggu = floor($days_diff / 7) + 1;

    if ($current_minggu >= 1 && $current_minggu <= 16) {
        if (isset($piket_by_day[$current_minggu][$day_name_ind])) {
            foreach ($piket_by_day[$current_minggu][$day_name_ind] as $p) {
                $events[$date_key][] = [
                    'type' => 'piket',
                    'title' => 'Piket: ' . $p['matkul'],
                    'petugas' => $p['nama'],
                    'time' => ''
                ];
            }
        }
    }
}
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

    <title>Kalender Kelas - PTIK C 2024</title>
    <?php include '../includes/head.php'; ?>
    <style>
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


        .event-kalender {
            background: #6366f1;
        }

        .event-tugas {
            background: #6366f1;
        }

        .event-personal {
            background: #f43f5e;
        }

        .event-piket {
            background: #1e1b4b;
        }
        
        .dark .event-piket {
            background: #818cf8;
        }

        .event-done {
            background: #94a3b8 !important;
        }

        .dark .event-done {
            background: #475569 !important;
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

<body class="bg-slate-100 dark:bg-slate-950 transition-colors duration-300">
    <?php include '../includes/menu_db.php'; ?>

    <main class="ml-0 lg:ml-48 pt-20 p-6 flex flex-col min-h-screen">
        <div class="bg-white dark:bg-slate-900 rounded-xl border border-slate-200 dark:border-slate-800 shadow-sm p-4 md:p-6 transition-colors duration-300">
            <!-- Header with Navigation -->
            <div class="flex flex-col md:flex-row justify-between items-center mb-6 gap-4">
                <div>
                    <h1 class="text-2xl font-bold text-slate-800 dark:text-white">Kalender Kelas</h1>
                    <p class="text-slate-500 dark:text-slate-400 text-sm mt-1">Informasi akademik terintegrasi</p>
                </div>

                <!-- Month Navigation -->
                <div class="flex items-center gap-2 bg-slate-100 dark:bg-slate-800 p-1 rounded-lg">
                    <a href="?month=<?= $prev_month ?>&year=<?= $prev_year ?>" class="p-2 hover:bg-white dark:hover:bg-slate-700 rounded-md transition-colors">
                        <i class="fa fa-chevron-left text-slate-600 dark:text-slate-400"></i>
                    </a>
                    <span class="px-4 font-bold text-indigo-900 dark:text-indigo-400 text-sm min-w-[140px] text-center">
                        <?= $month_names[$month] ?> <?= $year ?>
                    </span>
                    <a href="?month=<?= $next_month ?>&year=<?= $next_year ?>" class="p-2 hover:bg-white dark:hover:bg-slate-700 rounded-md transition-colors">
                        <i class="fa fa-chevron-right text-slate-600 dark:text-slate-400"></i>
                    </a>
                </div>
            </div>

            <!-- Legend -->
            <div class="flex flex-wrap gap-4 mb-4 text-xs">
                <div class="flex items-center gap-2">
                    <span class="event-dot event-kalender"></span>
                    <span class="text-slate-600 dark:text-slate-400">Kalender Akademik</span>
                </div>


                <div class="flex items-center gap-2">
                    <span class="event-dot event-tugas"></span>
                    <span class="text-slate-600 dark:text-slate-400">Tugas Kuliah</span>
                </div>

                <div class="flex items-center gap-2">
                    <span class="event-dot event-personal"></span>
                    <span class="text-slate-600 dark:text-slate-400">Tugas Pribadi</span>
                </div>

                <div class="flex items-center gap-2">
                    <span class="event-dot event-piket"></span>
                    <span class="text-slate-600 dark:text-slate-400">Piket Proyektor</span>
                </div>

                <div class="flex items-center gap-2">
                    <span class="event-dot event-done"></span>
                    <span class="text-slate-600 dark:text-slate-400">Sudah Selesai</span>
                </div>
            </div>

            <!-- Calendar Grid -->
            <div class="border border-slate-200 dark:border-slate-800 rounded-lg overflow-hidden">
                <!-- Day Headers -->
                <div class="calendar-grid bg-indigo-900 dark:bg-indigo-950 text-white">
                    <?php foreach ($day_names as $dn): ?>
                        <div class="p-2 md:p-3 text-center text-xs font-bold uppercase"><?= $dn ?></div>
                    <?php endforeach; ?>
                </div>

                <!-- Calendar Days -->
                <div class="calendar-grid bg-white dark:bg-slate-900">
                    <?php
                    // Empty cells before first day
                    for ($i = 0; $i < $start_day; $i++): ?>
                        <div class="calendar-cell p-1 sm:p-2 md:p-3 border-t border-l border-slate-100 dark:border-slate-800 bg-slate-50 dark:bg-slate-800/50 min-h-[60px] sm:min-h-[80px] md:min-h-[100px]"></div>
                    <?php endfor; ?>

                    <?php for ($d = 1; $d <= $days_in_month; $d++):
                        $date_key = sprintf('%04d-%02d-%02d', $year, $month, $d);
                        $is_today = ($date_key === $today);
                        $day_events = $events[$date_key] ?? [];
                        $has_events = !empty($day_events);
                    ?>
                        <div class="calendar-cell p-1 sm:p-2 md:p-3 border-t border-l border-slate-100 dark:border-slate-800 min-h-[60px] sm:min-h-[80px] md:min-h-[100px] <?= $is_today ? 'bg-indigo-50 dark:bg-indigo-900/30 ring-2 ring-indigo-400 ring-inset' : '' ?> <?= $has_events ? 'cursor-pointer hover:bg-slate-50 dark:hover:bg-slate-800/50 transition-colors' : '' ?>"
                            <?php if ($has_events): ?>
                            onclick="showDatePopup('<?= $date_key ?>', <?= $d ?>)"
                            <?php endif; ?>>
                            <div class="flex justify-between items-start mb-1">
                                <span class="text-xs sm:text-sm font-bold <?= $is_today ? 'text-indigo-700 dark:text-indigo-400' : 'text-slate-700 dark:text-slate-300' ?>"><?= $d ?></span>
                                <?php if ($is_today): ?>
                                    <span class="hidden sm:inline text-[8px] bg-indigo-600 text-white px-1 rounded">HARI INI</span>
                                <?php endif; ?>
                            </div>

                            <!-- Events - Mobile: dots only, Desktop: dots + text -->
                            <div class="event-row flex sm:flex-col sm:space-y-1 overflow-hidden max-h-[40px] sm:max-h-[60px] md:max-h-[80px]">
                                <?php
                                $event_counts = ['kalender' => 0, 'tugas' => 0, 'personal' => 0, 'piket' => 0];
                                $shown = 0;
                                foreach ($day_events as $evt):
                                    $event_counts[$evt['type']]++;
                                    if ($shown >= 4) continue;
                                    $shown++;

                                    $dot_class = 'event-' . $evt['type'];
                                    if (isset($evt['status']) && $evt['status'] === 'selesai') {
                                        $dot_class = 'event-done';
                                    }
                                ?>
                                    <div class="flex items-center gap-1 text-[10px] text-slate-600 dark:text-slate-400" title="<?= htmlspecialchars($evt['title']) ?>">
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
                        <div class="calendar-cell p-1 sm:p-2 md:p-3 border-t border-l border-slate-100 dark:border-slate-800 bg-slate-50 dark:bg-slate-800/50 min-h-[60px] sm:min-h-[80px] md:min-h-[100px]"></div>
                    <?php endfor; ?>
                </div>
            </div>

            <!-- Rekap Agenda Sections -->
            <div class="mt-6 space-y-4">
                <!-- 1. Tugas Kuliah -->
                <div class="p-4 bg-indigo-50/50 dark:bg-indigo-950/20 border border-indigo-100 dark:border-indigo-900/50 rounded-xl">
                    <h3 class="font-bold text-indigo-900 dark:text-indigo-400 mb-3 flex items-center gap-2 text-sm">
                        <span class="event-dot event-tugas" style="width:10px;height:10px;"></span>
                        Tugas Kuliah
                    </h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3 max-h-[400px] overflow-y-auto pr-2">
                        <?php
                        $tugas_kuliah = [];
                        foreach ($events as $date => $day_events) {
                            foreach ($day_events as $evt) {
                                if ($evt['type'] === 'tugas') {
                                    $tugas_kuliah[] = ['date' => $date, ...$evt];
                                }
                            }
                        }
                        if (!empty($tugas_kuliah)):
                            foreach ($tugas_kuliah as $t):
                                $is_done = ($t['status'] === 'selesai');
                        ?>
                                <div class="bg-white/80 dark:bg-slate-800/80 p-3 rounded-lg text-xs border border-indigo-100 dark:border-indigo-900/40 flex items-center gap-3 <?= $is_done ? 'opacity-60' : '' ?>">
                                    <div class="font-bold text-indigo-700 dark:text-indigo-400 w-12 text-center leading-tight">
                                        <?= date('d', strtotime($t['date'])) ?><br><span class="text-[9px] uppercase"><?= date('M', strtotime($t['date'])) ?></span>
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <div class="font-bold truncate mb-0.5 <?= $is_done ? 'line-through text-slate-400 dark:text-slate-500' : 'text-slate-800 dark:text-slate-200' ?>"><?= htmlspecialchars($t['title']) ?></div>
                                        <div class="text-[10px] text-slate-500 dark:text-slate-400 font-medium"><?= $t['time'] ?: 'No Time' ?></div>
                                    </div>
                                    <?php if ($is_done): ?>
                                        <i class="fa fa-check-circle text-indigo-500"></i>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach;
                        else: ?>
                            <p class="text-xs text-slate-500 italic py-2">Tidak ada tugas kuliah.</p>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- 2. Tugas Pribadi -->
                <div class="p-4 bg-rose-50/50 dark:bg-rose-950/20 border border-rose-100 dark:border-rose-900/40 rounded-xl">
                    <h3 class="font-bold text-rose-900 dark:text-rose-400 mb-3 flex items-center gap-2 text-sm">
                        <span class="event-dot event-personal" style="width:10px;height:10px;"></span>
                        Tugas Pribadi
                    </h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3 max-h-[400px] overflow-y-auto pr-2">
                        <?php
                        $tugas_pribadi = [];
                        foreach ($events as $date => $day_events) {
                            foreach ($day_events as $evt) {
                                if ($evt['type'] === 'personal') {
                                    $tugas_pribadi[] = ['date' => $date, ...$evt];
                                }
                            }
                        }
                        if (!empty($tugas_pribadi)):
                            foreach ($tugas_pribadi as $t):
                                $is_done = ($t['status'] === 'selesai');
                        ?>
                                <div class="bg-white/80 dark:bg-slate-800/80 p-3 rounded-lg text-xs border border-rose-100 dark:border-rose-900/40 flex items-center gap-3 <?= $is_done ? 'opacity-60' : '' ?>">
                                    <div class="font-bold text-rose-600 dark:text-rose-400 w-12 text-center leading-tight">
                                        <?= date('d', strtotime($t['date'])) ?><br><span class="text-[9px] uppercase"><?= date('M', strtotime($t['date'])) ?></span>
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <div class="font-bold truncate mb-0.5 <?= $is_done ? 'line-through text-slate-400 dark:text-slate-500' : 'text-slate-800 dark:text-slate-200' ?>"><?= htmlspecialchars($t['title']) ?></div>
                                        <div class="text-[10px] text-slate-500 dark:text-slate-400 font-medium"><?= $t['time'] ?: 'No Time' ?></div>
                                    </div>
                                    <?php if ($is_done): ?>
                                        <i class="fa fa-check-circle text-rose-500"></i>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach;
                        else: ?>
                            <p class="text-xs text-slate-500 italic py-2">Tidak ada tugas pribadi.</p>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- 3. Piket Proyektor -->
                <div class="p-4 bg-slate-100/50 dark:bg-slate-800/50 border border-slate-200 dark:border-slate-700 rounded-xl">
                    <h3 class="font-bold text-slate-900 dark:text-slate-100 mb-3 flex items-center gap-2 text-sm">
                        <span class="event-dot event-piket" style="width:10px;height:10px;"></span>
                        Jadwal Piket Saya
                    </h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3 max-h-[400px] overflow-y-auto pr-2">
                        <?php
                        $piket_saya = [];
                        foreach ($events as $date => $day_events) {
                            foreach ($day_events as $evt) {
                                if ($evt['type'] === 'piket') {
                                    $piket_saya[] = ['date' => $date, ...$evt];
                                }
                            }
                        }
                        if (!empty($piket_saya)):
                            foreach ($piket_saya as $p):
                        ?>
                                <div class="bg-white/80 dark:bg-slate-800/80 p-3 rounded-lg text-xs border border-slate-200 dark:border-slate-700">
                                    <div class="flex items-center gap-3">
                                        <div class="font-bold text-slate-700 dark:text-slate-400 w-12 text-center leading-tight">
                                            <?= date('d', strtotime($p['date'])) ?><br><span class="text-[9px] uppercase"><?= date('M', strtotime($p['date'])) ?></span>
                                        </div>
                                        <div class="flex-1 min-w-0">
                                            <div class="text-slate-800 dark:text-slate-200 font-bold truncate mb-0.5"><?= htmlspecialchars($p['title']) ?></div>
                                            <div class="text-[10px] text-indigo-700 dark:text-indigo-400 font-bold flex items-center gap-1 uppercase italic"><i class="fa fa-user text-[8px]"></i> Piket Kamu</div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach;
                        else: ?>
                            <p class="text-xs text-slate-500 italic py-2">Tidak ada jadwal piket untukmu bulan ini.</p>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- 4. Kalender Akademik -->
                <div class="p-4 bg-indigo-900/10 dark:bg-indigo-900/20 border border-indigo-200 dark:border-indigo-900/40 rounded-xl">
                    <h3 class="font-bold text-indigo-900 dark:text-indigo-400 mb-3 flex items-center gap-2 text-sm">
                        <span class="event-dot event-kalender" style="width:10px;height:10px;"></span>
                        Kalender Akademik
                    </h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3 max-h-[400px] overflow-y-auto pr-2">
                        <?php
                        $kalender_list = [];
                        foreach ($events as $date => $day_events) {
                            foreach ($day_events as $evt) {
                                if ($evt['type'] === 'kalender') {
                                    $kalender_list[] = ['date' => $date, ...$evt];
                                }
                            }
                        }
                        if (!empty($kalender_list)):
                            foreach ($kalender_list as $k):
                                $is_range = ($k['start'] !== $k['end']);
                        ?>
                                <div class="bg-white/80 dark:bg-slate-800/80 p-3 rounded-lg text-xs border border-indigo-100 dark:border-indigo-900/40 flex items-center gap-3">
                                    <div class="font-bold text-indigo-700 dark:text-indigo-400 w-14 text-center leading-tight whitespace-nowrap">
                                        <?php if ($is_range): ?>
                                            <div class="text-[10px]"><?= date('d M', strtotime($k['start'])) ?></div>
                                            <div class="text-[8px] text-slate-400 dark:text-slate-500">s/d</div>
                                            <div class="text-[10px]"><?= date('d M', strtotime($k['end'])) ?></div>
                                        <?php else: ?>
                                            <?= date('d', strtotime($k['start'])) ?><br><span class="text-[9px] uppercase"><?= date('M', strtotime($k['start'])) ?></span>
                                        <?php endif; ?>
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <div class="text-slate-800 dark:text-slate-200 font-bold leading-tight"><?= htmlspecialchars($k['title']) ?></div>
                                    </div>
                                </div>
                            <?php endforeach;
                        else: ?>
                            <p class="text-xs text-slate-500 italic py-2">Tidak ada kegiatan akademik.</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
        </div>

        <?php include '../includes/footer_db.php'; ?>

    </main>

    <!-- Date Popup Modal -->
    <div id="dateModal" class="fixed inset-0 bg-black/60 backdrop-blur-sm z-[100] hidden items-center justify-center p-4 transition-all duration-300">
        <div class="bg-white dark:bg-slate-900 rounded-xl shadow-2xl max-w-md w-full max-h-[80vh] overflow-hidden border border-slate-200 dark:border-slate-800">
            <div class="bg-gradient-to-r from-indigo-900 to-indigo-950 p-4 text-white">
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

    <?php include '../includes/script_db.php'; ?>
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
                content.innerHTML = '<p class="text-slate-500 dark:text-slate-400 text-sm text-center py-4">Tidak ada agenda.</p>';
            } else {
                let html = '<div class="space-y-3">';
                dayEvents.forEach(evt => {
                    let dotClass = 'event-' + evt.type;
                    let typeLabel = {
                        'kalender': 'Kalender Akademik',
                        'tugas': 'Tugas Kuliah',
                        'personal': 'Tugas Pribadi',
                        'piket': 'Piket Proyektor'
                    } [evt.type] || '';
                    let bgClass = {
                        'kalender': 'bg-indigo-50 dark:bg-indigo-950/30 border-indigo-200 dark:border-indigo-900/50',
                        'tugas': 'bg-slate-50 dark:bg-slate-800/50 border-slate-200 dark:border-slate-700',
                        'personal': 'bg-rose-50 dark:bg-rose-950/30 border-rose-100 dark:border-rose-900/50',
                        'piket': 'bg-indigo-900/5 dark:bg-indigo-900/10 border-indigo-900/20 dark:border-indigo-900/30'
                    } [evt.type] || 'bg-slate-50 dark:bg-slate-800';

                    if (evt.status === 'selesai') {
                        dotClass = 'event-done';
                    }

                    html += `
                        <div class="p-3 rounded-lg border ${bgClass}">
                            <div class="flex items-start gap-3">
                                <span class="event-dot ${dotClass} mt-1" style="width:10px;height:10px;flex-shrink:0;"></span>
                                <div class="flex-1">
                                    <div class="text-[10px] font-bold text-slate-500 dark:text-slate-400 mb-1 uppercase tracking-tighter">${typeLabel}</div>
                                    <div class="font-semibold text-slate-800 dark:text-slate-100 text-xs">${evt.title}</div>
                                    ${evt.petugas ? `<div class="text-[10px] text-indigo-700 dark:text-indigo-400 font-bold mt-1 uppercase"><i class="fa fa-user mr-1"></i>Petugas: ${evt.petugas}</div>` : ''}
                                    ${evt.type === 'kalender' && evt.start && evt.end && evt.start !== evt.end ? 
                                        `<div class="text-[10px] text-indigo-600 dark:text-indigo-400 font-bold mt-1"><i class="fa fa-calendar-range mr-1"></i>${formatDateIndo(evt.start)} - ${formatDateIndo(evt.end)}</div>` : ''
                                    }
                                    ${evt.time ? `<div class="text-xs text-slate-600 dark:text-slate-400 mt-1"><i class="fa fa-clock mr-1"></i>${evt.time}</div>` : ''}
                                    ${evt.status === 'selesai' ? '<div class="text-xs text-indigo-600 dark:text-indigo-400 mt-1"><i class="fa fa-check-circle mr-1"></i>Selesai</div>' : ''}
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
    </script>
</body>

</html>