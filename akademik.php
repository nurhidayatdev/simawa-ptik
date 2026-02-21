<?php
include 'koneksi.php';
include 'includes/pesan.php';

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
    <title>Akademik - Kelas PTIK C 2024</title>
    <?php include 'includes/head.php'; ?>

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
            background: #314158;
        }

        .event-tugas-done {
            background: #314158;
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

        /* Scroll Reveal Animation Styles */
        .scroll-reveal {
            opacity: 0;
            transform: translateY(30px);
            transition: all 0.8s cubic-bezier(0.4, 0, 0.2, 1);
            will-change: opacity, transform;
        }

        .scroll-reveal.active {
            opacity: 1;
            transform: translateY(0);
        }
    </style>

</head>

<body class="bg-gray-50 min-h-screen flex flex-col">
    <?php include 'includes/header_main.php'; ?>

    <!-- spacer to avoid content hidden under fixed header -->
    <div class="h-10"></div>

    <main class="flex-grow ">
        <section class="py-12 p-6">
            <div class="container mx-auto">
                <h2 class="text-2xl font-bold mb-6 text-gray-800 scroll-reveal">Berita & Informasi Akademik</h2>

                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
                    <!-- sample card -->
                    <?php
                    if (mysqli_num_rows($query) > 0) {
                        while ($data = mysqli_fetch_assoc($query)) {
                    ?>
                            <article class="bg-white rounded-xl shadow-md overflow-hidden hover:shadow-lg transition scroll-reveal">

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
            <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-4 md:p-6 scroll-reveal">
                <!-- Header with Navigation -->
                <div class="flex flex-col md:flex-row justify-between items-center mb-6 gap-4">
                    <div>
                        <h1 class="text-2xl font-bold text-slate-800">Kalender Kelas</h1>
                        <p class="text-slate-500 text-sm mt-1">Informasi akademik terintegrasi</p>
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
                                    $event_counts = ['kalender' => 0, 'tugas' => 0];
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
                <div class="mt-6 grid grid-cols-1 lg:grid-cols-2 gap-4">
                    <!-- Kalender Akademik -->
                    <div class="p-4 bg-indigo-50 border border-indigo-200 rounded-xl scroll-reveal">
                        <h3 class="font-bold text-indigo-900 mb-3 flex items-center gap-2 text-sm">
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
                                        <div class="font-bold text-indigo-700 min-w-[70px]">
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






                    <!-- Deadline Tugas -->
                    <div class="p-4 bg-slate-50 border border-slate-200 rounded-xl scroll-reveal">
                        <h3 class="font-bold text-slate-900 mb-3 flex items-center gap-2 text-sm">
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
                                        <span class="font-bold text-slate-700 w-16"><?= date('d M', strtotime($t['date'])) ?></span>
                                        <span class="flex-1 text-slate-700 <?= $is_done ? 'line-through' : '' ?>"><?= htmlspecialchars($t['title']) ?></span>
                                        <?php if ($t['time']): ?>
                                            <span class="text-slate-600 font-medium"><?= $t['time'] ?></span>
                                        <?php endif; ?>
                                        <?php if ($is_done): ?>
                                            <i class="fa fa-check-circle text-slate-500"></i>
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
                <div class="bg-gradient-to-r from-indigo-900 to-purple-950 p-4 text-white">
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

    <?php include 'includes/footer_main.php'; ?>
    <?php include 'includes/script_main.php'; ?>

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
                        'kalender': 'Kalender Akademik',
                        'tugas': 'Deadline Tugas'
                    } [evt.type] || '';
                    let bgClass = {
                        'kalender': 'bg-indigo-50 border-indigo-200',
                        'tugas': 'bg-slate-50 border-slate-200'
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
                                        `<div class="text-[10px] text-indigo-600 font-bold mt-1"><i class="fa fa-calendar-range mr-1"></i>${formatDateIndo(evt.start)} - ${formatDateIndo(evt.end)}</div>` : ''
                                    }
                                    ${evt.time ? `<div class="text-xs text-slate-600 mt-1"><i class="fa fa-clock mr-1"></i>${evt.time}</div>` : ''}
                                    ${evt.status === 'selesai' ? '<div class="text-xs text-indigo-600 mt-1"><i class="fa fa-check-circle mr-1"></i>Selesai</div>' : ''}
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
    <script>
        // Scroll Reveal Implementation
        document.addEventListener('DOMContentLoaded', function() {
            const observerOptions = {
                threshold: 0.1,
                rootMargin: '0px 0px -50px 0px'
            };

            const observer = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        entry.target.classList.add('active');
                    } else {
                        entry.target.classList.remove('active');
                    }
                });
            }, observerOptions);

            const revealElements = document.querySelectorAll('.scroll-reveal');
            revealElements.forEach(el => observer.observe(el));
        });
    </script>
</body>

</html>