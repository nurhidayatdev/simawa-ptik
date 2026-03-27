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

// Ambil data mahasiswa berdasarkan user_id
$q_mhs = mysqli_query($koneksi, "SELECT * FROM mahasiswa WHERE user_id = '$user_id'");
$mhs = mysqli_fetch_assoc($q_mhs);
$mhs_id = $mhs['id'] ?? 0;

/* =====================
   AMBIL PERIODE AKTIF
   ===================== */
$q_periode = mysqli_query($koneksi, "SELECT id FROM periode WHERE status = 'aktif' LIMIT 1");
$periode = mysqli_fetch_assoc($q_periode);
$periode_id = $periode['id'] ?? 0;

/* =====================
   TENTUKAN RENTANG TANGGAL SEMESTER (OTOMATIS)
   ===================== */
// Ambil tanggal mulai kuliah (Kuliah Bagian Pertama)
$q_start = mysqli_query($koneksi, "SELECT tanggal_mulai FROM kalender_akademik WHERE periode_id = '$periode_id' AND kegiatan LIKE '%Kuliah Bagian Pertama%' ORDER BY tanggal_mulai ASC LIMIT 1");
$start_res = mysqli_fetch_assoc($q_start);
$semester_start = $start_res['tanggal_mulai'] ?? "2026-01-19";

// Ambil tanggal selesai kuliah (Kuliah Bagian Kedua)
$q_end = mysqli_query($koneksi, "SELECT tanggal_selesai FROM kalender_akademik WHERE periode_id = '$periode_id' AND kegiatan LIKE '%Kuliah Bagian Kedua%' ORDER BY tanggal_selesai DESC LIMIT 1");
$end_res = mysqli_fetch_assoc($q_end);
$semester_end = $end_res['tanggal_selesai'] ?? date('Y-12-31');

// --- HITUNG PROGRES MISI ---

// 1. Misi Kas
$res_kas = mysqli_query($koneksi, "SELECT COUNT(*) as total FROM kas WHERE mahasiswa_id = '$mhs_id' AND status = 'Lunas' AND periode_id = '$periode_id'");
$count_kas = mysqli_fetch_assoc($res_kas)['total'] ?? 0;

// 2. Misi Kuis
$res_kuis = mysqli_query($koneksi, "SELECT current_streak, total_games FROM kuis_harian_stats WHERE user_id = '$user_id'");
$kuis_stats = mysqli_fetch_assoc($res_kuis);
$kuis_streak = $kuis_stats['current_streak'] ?? 0;
$kuis_total = $kuis_stats['total_games'] ?? 0;

// 3. Misi Absensi
$res_absensi = mysqli_query($koneksi, "SELECT COUNT(*) as total FROM absensi WHERE mahasiswa_id = '$mhs_id' AND status = 'Hadir' AND periode_id = '$periode_id'");
$count_absensi = mysqli_fetch_assoc($res_absensi)['total'] ?? 0;

// 4. Misi Profil
$profil_lengkap = (!empty($mhs['foto']) && $mhs['foto'] !== 'Yaya.png' && !empty($mhs['instagram']) && !empty($mhs['bio'])) ? 1 : 0;

// 5. Misi KRS (Jumlah mata kuliah yang diambil di semester ini)
$res_krs = mysqli_query($koneksi, "SELECT COUNT(*) as total FROM krs WHERE mahasiswa_id = '$mhs_id' AND periode_id = '$periode_id'");
$count_krs = mysqli_fetch_assoc($res_krs)['total'] ?? 0;

// 6. Misi Kelompok
$q_nim = mysqli_query($koneksi, "SELECT nim FROM mahasiswa WHERE id = '$mhs_id'");
$nim_mhs = mysqli_fetch_assoc($q_nim)['nim'] ?? '';
$res_kelompok = mysqli_query($koneksi, "SELECT COUNT(*) as total FROM kelompok WHERE FIND_IN_SET('$nim_mhs', REPLACE(anggota, ' ', '')) OR anggota LIKE '%$nim_mhs%'");
$count_kelompok = mysqli_fetch_assoc($res_kelompok)['total'] ?? 0;

// 7. Misi Login (Vibe Check) - Filtered by Semester Range
$res_vibe = mysqli_query($koneksi, "SELECT COUNT(*) as total FROM vibe_check WHERE user_id = '$user_id' AND DATE(created_at) BETWEEN '$semester_start' AND '$semester_end'");
$count_vibe = mysqli_fetch_assoc($res_vibe)['total'] ?? 0;

// 8. Misi Koleksi Vibe (Pilih Semua Vibe) - Filtered by Semester Range
$res_vibe_sets = mysqli_query($koneksi, "SELECT mood, COUNT(*) as cnt FROM vibe_check WHERE user_id = '$user_id' AND DATE(created_at) BETWEEN '$semester_start' AND '$semester_end' GROUP BY mood");
$mood_counts = ['productive' => 0, 'stress' => 0, 'relaxed' => 0, 'tired' => 0, 'hyper' => 0];
while ($vs = mysqli_fetch_assoc($res_vibe_sets)) {
    if (isset($mood_counts[$vs['mood']])) {
        $mood_counts[$vs['mood']] = (int)$vs['cnt'];
    }
}
$count_vibe_sets = min($mood_counts);

// Definisi Misi
$missions = [
    [
        'category' => 'Keuangan',
        'title' => 'Bendahara Tersenyum',
        'icon' => 'fa-wallet',
        'color' => 'emerald',
        'levels' => [
            ['name' => 'Bronze', 'target' => 5, 'current' => $count_kas, 'desc' => 'Bayar kas selama 5 minggu'],
            ['name' => 'Silver', 'target' => 10, 'current' => $count_kas, 'desc' => 'Bayar kas selama 10 minggu'],
            ['name' => 'Gold', 'target' => 16, 'current' => $count_kas, 'desc' => 'Bayar kas penuh satu semester (16 minggu)']
        ]
    ],
    [
        'category' => 'Akademik',
        'title' => 'Mahasiswa Teladan',
        'icon' => 'fa-user-check',
        'color' => 'blue',
        'levels' => [
            ['name' => 'Hadir 5x', 'target' => 5, 'current' => $count_absensi, 'desc' => 'Kumpulkan 5 poin kehadiran'],
            ['name' => 'Hadir 15x', 'target' => 15, 'current' => $count_absensi, 'desc' => 'Kumpulkan 15 poin kehadiran'],
            ['name' => 'Hadir 30x', 'target' => 30, 'current' => $count_absensi, 'desc' => 'Kumpulkan 30 poin kehadiran']
        ]
    ],
    [
        'category' => 'Kuis Harian',
        'title' => 'Raja Kuis',
        'icon' => 'fa-fire',
        'color' => 'orange',
        'levels' => [
            ['name' => 'Starter Streak', 'target' => 3, 'current' => $kuis_streak, 'desc' => 'Capai 3 hari streak kuis'],
            ['name' => 'Veteran Streak', 'target' => 14, 'current' => $kuis_streak, 'desc' => 'Capai 14 hari streak kuis'],
            ['name' => 'Total Gamer 50', 'target' => 50, 'current' => $kuis_total, 'desc' => 'Total bermain kuis sebanyak 50 kali']
        ]
    ],
    [
        'category' => 'Konsistensi',
        'title' => 'Si Paling Login',
        'icon' => 'fa-door-open',
        'color' => 'cyan',
        'levels' => [
            ['name' => '10 Login', 'target' => 10, 'current' => $count_vibe, 'desc' => 'Lakukan login sebanyak 10 kali'],
            ['name' => '30 Login', 'target' => 30, 'current' => $count_vibe, 'desc' => 'Lakukan login sebanyak 30 kali'],
            ['name' => '60 Login', 'target' => 60, 'current' => $count_vibe, 'desc' => 'Lakukan login sebanyak 60 kali']
        ]
    ],
    [
        'category' => 'Konsistensi',
        'title' => 'Kolektor Mood',
        'icon' => 'fa-palette',
        'color' => 'yellow',
        'levels' => [
            ['name' => 'Satu Putaran', 'target' => 1, 'current' => $count_vibe_sets, 'desc' => 'Pilih semua jenis vibe setidaknya masing-masing 1 kali'],
            ['name' => 'Tiga Putaran', 'target' => 3, 'current' => $count_vibe_sets, 'desc' => 'Pilih semua jenis vibe setidaknya masing-masing 3 kali'],
            ['name' => 'Tujuh Putaran', 'target' => 7, 'current' => $count_vibe_sets, 'desc' => 'Pilih semua jenis vibe setidaknya masing-masing 7 kali']
        ]
    ],
    [
        'category' => 'Sosial',
        'title' => 'Warga Kelas Aktif',
        'icon' => 'fa-user-check',
        'color' => 'purple',
        'levels' => [
            ['name' => 'KRS Terisi', 'target' => 1, 'current' => ($count_krs > 0 ? 1 : 0), 'desc' => 'Selesaikan pengisian KRS semester ini'],
            ['name' => 'Profil Lengkap', 'target' => 1, 'current' => $profil_lengkap, 'desc' => 'Lengkapi foto (bukan default), instagram & bio'],
            ['name' => 'Si Paling Kelompok', 'target' => 5, 'current' => $count_kelompok, 'desc' => 'Terjalin dalam 5 kelompok mata kuliah']
        ]
    ]
];

// Mapping warna ke CSS filter untuk logo
$color_filters = [
    'emerald' => 'brightness(0) saturate(100%) invert(65%) sepia(16%) saturate(3065%) hue-rotate(116deg) brightness(101%) contrast(86%)',
    'blue'    => 'brightness(0) saturate(100%) invert(51%) sepia(85%) saturate(2394%) hue-rotate(201deg) brightness(99%) contrast(94%)',
    'orange'  => 'brightness(0) saturate(100%) invert(62%) sepia(77%) saturate(3143%) hue-rotate(352deg) brightness(101%) contrast(96%)',
    'purple'  => 'brightness(0) saturate(100%) invert(43%) sepia(72%) saturate(3085%) hue-rotate(244deg) brightness(102%) contrast(95%)',
    'yellow'  => 'brightness(0) saturate(100%) invert(91%) sepia(96%) saturate(1313%) hue-rotate(359deg) brightness(101%) contrast(101%)',
    'cyan'    => 'brightness(0) saturate(100%) invert(75%) sepia(50%) saturate(2000%) hue-rotate(150deg) brightness(100%) contrast(100%)'
];
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Dashboard Resmi Kelas PTIK C 2024">
    <meta name="theme-color" content="#1e1b4b" />
    <title>Pusat Misi - PTIK C 2024</title>
    <?php include '../includes/head.php'; ?>
    <style>
        .glass {
            background: rgba(255, 255, 255, 0.7);
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.3);
            transition: all 0.3s ease;
        }

        .dark .glass {
            background: rgba(15, 23, 42, 0.8);
            border: 1px solid rgba(255, 255, 255, 0.1);
        }

        .mission-card:hover {
            transform: translateY(-5px);
        }

        .status-badge {
            transition: all 0.3s ease;
        }

        .locked {
            opacity: 0.5;
            filter: grayscale(1);
        }

        .completed {
            filter: drop-shadow(0 0 8px rgba(255, 215, 0, 0.6));
        }
    </style>

</head>

<body class="bg-slate-100 dark:bg-slate-950 transition-colors duration-300 min-h-screen">
    <?php include '../includes/menu_db.php'; ?>

    <main class="ml-0 lg:ml-48 pt-20 p-6 flex flex-col min-h-screen bg-indigo-950/5 dark:bg-slate-950 transition-colors duration-300">
        <div class="max-w-6xl mx-auto w-full">
            <div class="mb-8">
                <h1 class="text-3xl font-bold text-slate-800 dark:text-white tracking-tight">Pusat Misi & Pencapaian</h1>
                <p class="text-slate-500 dark:text-slate-400 text-sm mt-1">Selesaikan misi untuk mendapatkan lencana prestisius!</p>
            </div>



            <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                <?php foreach ($missions as $cat): ?>
                    <div class="glass dark:bg-slate-900/80 p-6 rounded-2xl shadow-xl dark:shadow-slate-950/20 border border-white/30 dark:border-slate-800 transition-all mission-card">
                        <div class="flex items-center gap-4 mb-6">
                            <div class="w-14 h-14 rounded-2xl bg-<?= $cat['color'] ?>-100 dark:bg-<?= $cat['color'] ?>-900/30 flex items-center justify-center text-<?= $cat['color'] ?>-600 dark:text-<?= $cat['color'] ?>-400 shadow-inner">
                                <i class="fa <?= $cat['icon'] ?> text-2xl"></i>
                            </div>
                            <div>
                                <span class="text-[10px] uppercase font-bold tracking-widest text-<?= $cat['color'] ?>-600 dark:text-<?= $cat['color'] ?>-400"><?= $cat['category'] ?></span>
                                <h3 class="text-xl font-semibold text-slate-800 dark:text-slate-200"><?= $cat['title'] ?></h3>
                            </div>
                        </div>

                        <div class="space-y-6">
                            <?php foreach ($cat['levels'] as $level):
                                $progress = min(100, ($level['current'] / $level['target']) * 100);
                                $is_completed = ($level['current'] >= $level['target']);
                            ?>
                                <div class="bg-white/50 dark:bg-slate-800/50 p-4 rounded-xl border border-white/80 dark:border-slate-700/50 shadow-sm">
                                    <div class="flex justify-between items-start mb-2">
                                        <div class="flex items-center gap-3">
                                            <div class="w-10 h-10 rounded-full flex items-center justify-center <?= $is_completed ? 'bg-' . $cat['color'] . '-100 dark:bg-' . $cat['color'] . '-900/20 completed' : 'bg-slate-100 dark:bg-slate-800 text-slate-400 dark:text-slate-600 locked' ?>">
                                                <?php if ($is_completed): ?>
                                                    <img src="../assets/image/logo.webp" class="w-6 h-6 object-contain" style="filter: <?= $color_filters[$cat['color']] ?? $color_filters['yellow'] ?>;">
                                                <?php else: ?>
                                                    <i class="fa fa-lock text-lg"></i>
                                                <?php endif; ?>
                                            </div>
                                            <div>
                                                <h4 class="font-semibold text-sm text-slate-700 dark:text-slate-200"><?= $level['name'] ?></h4>
                                                <p class="text-[10px] text-slate-500 dark:text-slate-400"><?= $level['desc'] ?></p>
                                            </div>
                                        </div>
                                        <div class="text-right">
                                            <span class="text-xs font-bold text-slate-700 dark:text-slate-200"><?= $level['current'] ?> / <?= $level['target'] ?></span>
                                        </div>
                                    </div>

                                    <div class="w-full bg-slate-200 dark:bg-slate-700 h-2 rounded-full overflow-hidden mt-3 shadow-inner">
                                        <div class="h-full rounded-full transition-all duration-1000 bg-gradient-to-r from-<?= $cat['color'] ?>-400 to-<?= $cat['color'] ?>-600" style="width: <?= $progress ?>%"></div>
                                    </div>

                                    <?php if ($is_completed): ?>
                                        <div class="mt-3 flex items-center gap-1.5 text-[10px] font-semibold text-green-600 dark:text-emerald-400 uppercase">
                                            <i class="fa fa-check-circle"></i> Berhasil Dicapai!
                                        </div>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <div class="mt-12 p-8 rounded-3xl border border-indigo-900/50 bg-gradient-to-br from-slate-900 via-indigo-950 to-slate-900 text-white overflow-hidden relative shadow-2xl">
                <div class="relative z-10">
                    <h2 class="text-2xl font-bold mb-2 flex items-center gap-3">
                        <i class="fa fa-star text-yellow-400"></i>
                        Koleksi Lencana Kamu
                    </h2>
                    <p class="text-indigo-200 text-sm mb-6">Berikut adalah lencana eksklusif yang telah kamu kumpulkan melalui kerja kerasmu!</p>

                    <div class="flex flex-wrap justify-center gap-6 md:gap-8">
                        <?php
                        $any_badge = false;
                        $badge_gradients = [
                            'emerald' => 'from-emerald-400 to-emerald-600',
                            'blue'    => 'from-blue-400 to-blue-600',
                            'cyan'    => 'from-cyan-400 to-cyan-600',
                            'orange'  => 'from-orange-400 to-orange-600',
                            'purple'  => 'from-purple-400 to-purple-600',
                            'yellow'  => 'from-yellow-400 to-yellow-600'
                        ];

                        foreach ($missions as $cat):
                            $bg_grad = $badge_gradients[$cat['color']] ?? 'from-slate-400 to-slate-600';

                            foreach ($cat['levels'] as $l_idx => $level):
                                if ($level['current'] >= $level['target']):
                                    $any_badge = true;
                        ?>
                                    <div class="group relative flex flex-col items-center">
                                        <!-- Badge Circle -->
                                        <div class="relative w-14 h-14 md:w-16 md:h-16 transition-transform duration-300 transform group-hover:-translate-y-2 group-hover:scale-105 cursor-pointer">
                                            <!-- Glow Effect behind -->
                                            <div class="absolute inset-0 bg-gradient-to-br <?= $bg_grad ?> rounded-full blur-md opacity-40 group-hover:opacity-75 transition-opacity"></div>

                                            <!-- Main Badge Body -->
                                            <div class="relative w-full h-full rounded-full bg-gradient-to-br <?= $bg_grad ?> p-[2px] shadow-xl">
                                                <div class="w-full h-full rounded-full bg-slate-900/90 backdrop-blur-sm flex flex-col items-center justify-center border border-white/10 relative overflow-hidden">

                                                    <!-- Shine Animation -->
                                                    <div class="absolute inset-0 bg-gradient-to-tr from-transparent via-white/20 to-transparent translate-x-[-150%] skew-x-12 group-hover:translate-x-[150%] transition-transform duration-1000 ease-in-out"></div>

                                                    <i class="fa <?= $cat['icon'] ?> text-lg md:text-2xl text-white mb-1 drop-shadow-md relative z-10"></i>

                                                    <!-- Tiny Logos for rank indicator -->
                                                    <div class="flex gap-0.5 mt-0.5 relative z-10">
                                                        <?php for ($s = 0; $s <= $l_idx; $s++): ?>
                                                            <img src="../assets/image/logo.webp" class="w-2.5 h-2.5 object-contain" style="filter: <?= $color_filters[$cat['color']] ?? $color_filters['yellow'] ?>;">
                                                        <?php endfor; ?>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Label -->
                                        <div class="mt-3 text-center">
                                            <span class="text-[10px] md:text-xs font-semibold uppercase tracking-widest text-white/90 bg-white/10 px-3 py-1 rounded-full border border-white/5 shadow-sm group-hover:bg-white/20 transition-colors">
                                                <?= $level['name'] ?>
                                            </span>
                                        </div>

                                        <!-- Detailed Tooltip -->
                                        <div class="absolute bottom-full left-1/2 -translate-x-1/2 mb-3 bg-slate-900 text-white text-xs py-2 px-3 rounded-xl shadow-2xl opacity-0 group-hover:opacity-100 transition-all duration-300 pointer-events-none transform translate-y-2 group-hover:translate-y-0 w-max max-w-[150px] text-center z-50 border border-white/10">
                                            <p class="font-semibold text-<?= $cat['color'] ?>-400 mb-0.5"><?= $cat['category'] ?></p>
                                            <p class="text-[10px] text-slate-300 leading-tight"><?= $level['desc'] ?></p>
                                            <!-- Arrow -->
                                            <div class="absolute top-full left-1/2 -translate-x-1/2 -mt-1 border-4 border-transparent border-t-slate-900"></div>
                                        </div>
                                    </div>
                            <?php
                                endif;
                            endforeach;
                        endforeach;

                        if (!$any_badge):
                            ?>
                            <div class="w-full text-center py-8 bg-white/5 rounded-2xl border border-white/10 flex flex-col items-center justify-center dashed-border">
                                <div class="w-16 h-16 bg-white/10 rounded-full flex items-center justify-center mb-3">
                                    <i class="fa fa-lock text-white/30 text-2xl"></i>
                                </div>
                                <p class="text-indigo-200 text-sm font-medium">Belum ada lencana yang terbuka.</p>
                                <p class="text-indigo-300/60 text-xs mt-1">Selesaikan misi di atas untuk mulai mengoleksi!</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                <!-- Decor -->
                <div class="absolute -right-20 -bottom-20 w-64 h-64 bg-indigo-500/20 rounded-full blur-3xl"></div>
                <div class="absolute -left-20 -top-20 w-64 h-64 bg-purple-500/20 rounded-full blur-3xl"></div>
            </div>
        </div>

        <?php include '../includes/footer_db.php'; ?>
    </main>
    <?php include '../includes/script_db.php'; ?>
</body>

</html>