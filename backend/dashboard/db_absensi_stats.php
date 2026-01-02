<?php
session_start();
include '../../koneksi.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../../index.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$nama_lengkap = $_SESSION['nama_lengkap'];
$foto = $_SESSION['foto'];
$role = $_SESSION['role'];

/* =====================
   AMBIL PERIODE AKTIF
   ===================== */
$q_periode = mysqli_query($koneksi, "SELECT id, tahun_periode, semester_nama FROM periode WHERE status = 'aktif' LIMIT 1");
$periode = mysqli_fetch_assoc($q_periode);
$periode_id = $periode['id'] ?? 0;
$periode_text = $periode ? $periode['tahun_periode'] . ' - ' . $periode['semester_nama'] : 'Tidak ada periode aktif';

// --- STATS OVERALL ---
$q_stats_overall = mysqli_query($koneksi, "SELECT status, COUNT(*) as cnt FROM absensi WHERE periode_id = '$periode_id' GROUP BY status");
$overall_stats = ['Hadir' => 0, 'Izin' => 0, 'Sakit' => 0, 'Alpha' => 0];
$total_absensi = 0;
while ($row = mysqli_fetch_assoc($q_stats_overall)) {
    if (isset($overall_stats[$row['status']])) {
        $overall_stats[$row['status']] = (int)$row['cnt'];
        $total_absensi += (int)$row['cnt'];
    }
}

// Hadir Rate
$hadir_rate = $total_absensi > 0 ? round(($overall_stats['Hadir'] / $total_absensi) * 100, 1) : 0;

// --- STATS PER MATA KULIAH ---
$q_matkul_stats = mysqli_query($koneksi, "
    SELECT 
        mk.nama_matkul,
        SUM(CASE WHEN a.status = 'Hadir' THEN 1 ELSE 0 END) as hadir,
        SUM(CASE WHEN a.status = 'Izin' THEN 1 ELSE 0 END) as izin,
        SUM(CASE WHEN a.status = 'Sakit' THEN 1 ELSE 0 END) as sakit,
        SUM(CASE WHEN a.status = 'Alpha' THEN 1 ELSE 0 END) as alpha,
        COUNT(a.id) as total
    FROM absensi a
    JOIN mata_kuliah mk ON a.matkul_id = mk.id
    WHERE a.periode_id = '$periode_id'
    GROUP BY a.matkul_id
    ORDER BY (sum(CASE WHEN a.status = 'Hadir' THEN 1 ELSE 0 END) / count(a.id)) DESC
");
$matkuls = [];
while ($row = mysqli_fetch_assoc($q_matkul_stats)) {
    $row['persen_hadir'] = $row['total'] > 0 ? round(($row['hadir'] / $row['total']) * 100, 1) : 0;
    $matkuls[] = $row;
}

// --- STATS PER PERTEMUAN (TREND) ---
$q_pertemuan_trend = mysqli_query($koneksi, "
    SELECT 
        pertemuan,
        SUM(CASE WHEN status = 'Hadir' THEN 1 ELSE 0 END) as hadir,
        COUNT(*) as total
    FROM absensi
    WHERE periode_id = '$periode_id'
    GROUP BY pertemuan
    ORDER BY pertemuan ASC
");
$trend_labels = [];
$trend_data = [];
while ($row = mysqli_fetch_assoc($q_pertemuan_trend)) {
    $trend_labels[] = "P" . $row['pertemuan'];
    $trend_data[] = $row['total'] > 0 ? round(($row['hadir'] / $row['total']) * 100, 1) : 0;
}

// --- TOP 10 MAHASISWA ALPHA ---
$q_top_alpha = mysqli_query($koneksi, "
    SELECT 
        m.nama_lengkap,
        m.nim,
        m.foto,
        COUNT(a.id) as alpha_count
    FROM absensi a
    JOIN mahasiswa m ON a.mahasiswa_id = m.id
    WHERE a.periode_id = '$periode_id' AND a.status = 'Alpha'
    GROUP BY a.mahasiswa_id
    HAVING alpha_count > 0
    ORDER BY alpha_count DESC, m.nama_lengkap ASC
    LIMIT 10
");
$top_alphas = [];
while ($row = mysqli_fetch_assoc($q_top_alpha)) {
    $top_alphas[] = $row;
}

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#1e1b4b" />
    <link rel="icon" href="../img/main/icon.png" />
    <title>Dashboard Absensi - PTIK C 2024</title>
    <link href="../../frontend/tailwind/src/output.css" rel="stylesheet">
    <link href="../../frontend/tailwind/src/input.css" rel="stylesheet">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        .rotate-180 {
            transform: rotate(180deg);
        }

        /* Tambahan CSS untuk mobile menu */
        @media (max-width: 768px) {
            .mobile-menu-open {
                transform: translateX(0) !important;
            }
        }
        
        .glass-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(226, 232, 240, 0.8);
        }
    </style>
</head>

<body class="bg-slate-50">
 

   

    <main class="ml-0 lg:ml-64 pt-24 p-6 min-h-screen">
        <div class="max-w-7xl mx-auto space-y-6">
            <!-- Header Section -->
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-black text-slate-800 tracking-tight">Dashboard Data Absensi</h1>
                    <p class="text-sm font-medium text-slate-500">Analisis kehadiran mahasiswa periode <?= htmlspecialchars($periode_text) ?></p>
                </div>
                <div class="flex items-center gap-3">
                    <a href="absensi.php" class="flex items-center px-4 py-2.5 bg-indigo-600 text-white text-xs font-bold rounded-xl shadow-lg shadow-indigo-200 hover:bg-indigo-700 transition-all active:scale-95">
                        <i class="fa fa-plus-circle mr-2"></i> INPUT ABSENSI
                    </a>
                    <a href="db_absensi.php" class="flex items-center px-4 py-2.5 bg-white text-slate-700 text-xs font-bold rounded-xl border border-slate-200 hover:bg-slate-50 transition-all">
                        <i class="fa fa-table mr-2 text-indigo-500"></i> LIHAT DETAIL
                    </a>
                </div>
            </div>

            <!-- Stats Grid -->
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
                <div class="glass-card p-5 rounded-2xl shadow-sm border border-slate-200 relative overflow-hidden group">
                    <div class="absolute -right-4 -bottom-4 text-indigo-50 opacity-[0.05] group-hover:scale-110 transition-transform">
                        <i class="fa fa-users text-8xl"></i>
                    </div>
                    <div class="relative z-10">
                        <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1">Rata-rata Hadir</p>
                        <div class="flex items-baseline gap-1">
                            <span class="text-3xl font-black text-indigo-600"><?= $hadir_rate ?></span>
                            <span class="text-xs font-bold text-slate-500">%</span>
                        </div>
                        <div class="mt-2 text-[10px] text-green-600 font-bold bg-green-50 px-2 py-0.5 rounded-full inline-block">
                            <i class="fa fa-check-circle mr-1"></i> Sangat Baik
                        </div>
                    </div>
                </div>

                <div class="glass-card p-5 rounded-2xl shadow-sm border border-slate-200 relative overflow-hidden group">
                    <div class="absolute -right-4 -bottom-4 text-amber-50 opacity-[0.05] group-hover:scale-110 transition-transform">
                        <i class="fa fa-user-clock text-8xl"></i>
                    </div>
                    <div class="relative z-10">
                        <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1">Total Hadir</p>
                        <div class="flex items-baseline gap-1">
                            <span class="text-3xl font-black text-slate-800"><?= number_format($overall_stats['Hadir']) ?></span>
                            <span class="text-xs font-bold text-slate-500">Record</span>
                        </div>
                        <p class="mt-2 text-[10px] text-slate-400 font-medium">Laporan kehadiran tersimpan</p>
                    </div>
                </div>

                <div class="glass-card p-5 rounded-2xl shadow-sm border border-slate-200 relative overflow-hidden group">
                    <div class="absolute -right-4 -bottom-4 text-rose-50 opacity-[0.05] group-hover:scale-110 transition-transform">
                        <i class="fa fa-user-times text-8xl"></i>
                    </div>
                    <div class="relative z-10">
                        <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1">Ketidakhadiran</p>
                        <div class="flex items-baseline gap-1">
                            <span class="text-3xl font-black text-rose-600"><?= number_format($overall_stats['Alpha']) ?></span>
                            <span class="text-xs font-bold text-slate-500">Alpha</span>
                        </div>
                        <div class="mt-2 text-[10px] text-rose-600 font-bold bg-rose-50 px-2 py-0.5 rounded-full inline-block">
                            <i class="fa fa-exclamation-triangle mr-1"></i> Perlu Atensi
                        </div>
                    </div>
                </div>

                <div class="glass-card p-5 rounded-2xl shadow-sm border border-slate-200 relative overflow-hidden group">
                    <div class="absolute -right-4 -bottom-4 text-sky-50 opacity-[0.05] group-hover:scale-110 transition-transform">
                        <i class="fa fa-medkit text-8xl"></i>
                    </div>
                    <div class="relative z-10">
                        <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1">Izin & Sakit</p>
                        <div class="flex items-baseline gap-1">
                            <span class="text-3xl font-black text-sky-600"><?= number_format($overall_stats['Izin'] + $overall_stats['Sakit']) ?></span>
                            <span class="text-xs font-bold text-slate-500">Record</span>
                        </div>
                        <p class="mt-2 text-[10px] text-slate-400 font-medium">Ketidakhadiran ber-alibi</p>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Main Chart (Trend) -->
                <div class="lg:col-span-2 bg-white p-6 rounded-2xl border border-slate-200 shadow-sm">
                    <div class="flex items-center justify-between mb-6">
                        <h3 class="text-sm font-black text-slate-800 uppercase tracking-widest">Tren Kehadiran</h3>
                        <div class="px-3 py-1 bg-indigo-50 text-indigo-600 text-[10px] font-bold rounded-full">Record per Pertemuan (%)</div>
                    </div>
                    <div class="h-[300px] w-full">
                        <canvas id="trendChart"></canvas>
                    </div>
                </div>

                <!-- Distribution Chart -->
                <div class="bg-white p-6 rounded-2xl border border-slate-200 shadow-sm">
                    <h3 class="text-sm font-black text-slate-800 uppercase tracking-widest mb-6">Komposisi Status</h3>
                    <div class="h-[250px] w-full flex items-center justify-center">
                        <canvas id="distChart"></canvas>
                    </div>
                    <div class="mt-6 grid grid-cols-2 gap-3">
                        <div class="flex items-center gap-2">
                            <div class="w-2 h-2 rounded-full bg-indigo-500"></div>
                            <span class="text-[10px] font-bold text-slate-600">Hadir</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <div class="w-2 h-2 rounded-full bg-rose-500"></div>
                            <span class="text-[10px] font-bold text-slate-600">Alpha</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <div class="w-2 h-2 rounded-full bg-amber-500"></div>
                            <span class="text-[10px] font-bold text-slate-600">Izin</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <div class="w-2 h-2 rounded-full bg-sky-500"></div>
                            <span class="text-[10px] font-bold text-slate-600">Sakit</span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Rankings by Course -->
                <div class="lg:col-span-2 bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
                    <div class="p-6 border-b border-slate-100 flex justify-between items-center">
                        <h3 class="text-sm font-black text-slate-800 uppercase tracking-widest">Peringkat Kehadiran Matkul</h3>
                        <span class="text-[10px] text-slate-400 font-bold uppercase tracking-widest">Terbaik ke Terburuk</span>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full text-left border-collapse">
                            <thead>
                                <tr class="bg-slate-50">
                                    <th class="px-6 py-3 text-[10px] font-black text-slate-400 uppercase tracking-widest">Mata Kuliah</th>
                                    <th class="px-6 py-3 text-[10px] font-black text-slate-400 uppercase tracking-widest text-center">Persentase</th>
                                    <th class="px-6 py-3 text-[10px] font-black text-slate-400 uppercase tracking-widest">Detail (H/I/S/A)</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                <?php foreach ($matkuls as $m): ?>
                                <tr class="hover:bg-slate-50/80 transition-colors">
                                    <td class="px-6 py-4">
                                        <div class="text-xs font-bold text-slate-700"><?= htmlspecialchars($m['nama_matkul']) ?></div>
                                        <div class="text-[10px] text-slate-400 font-medium"><?= $m['total'] ?> Record Terkumpul</div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="flex flex-col items-center gap-1.5 font-mono">
                                            <?php 
                                                $colorClass = $m['persen_hadir'] >= 90 ? 'text-green-600 bg-green-50' : ($m['persen_hadir'] >= 80 ? 'text-indigo-600 bg-indigo-50' : 'text-rose-600 bg-rose-50');
                                            ?>
                                            <span class="px-3 py-1 rounded-full text-sm font-black <?= $colorClass ?>"><?= $m['persen_hadir'] ?>%</span>
                                            <div class="w-24 h-1 bg-slate-100 rounded-full overflow-hidden">
                                                <div class="h-full <?= str_replace('text', 'bg', explode(' ', $colorClass)[0]) ?>" style="width: <?= $m['persen_hadir'] ?>%"></div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="flex items-center gap-2">
                                            <span class="px-1.5 py-0.5 bg-green-100 text-green-700 text-[9px] font-bold rounded" title="Hadir"><?= $m['hadir'] ?>H</span>
                                            <span class="px-1.5 py-0.5 bg-amber-100 text-amber-700 text-[9px] font-bold rounded" title="Izin"><?= $m['izin'] ?>I</span>
                                            <span class="px-1.5 py-0.5 bg-sky-100 text-sky-700 text-[9px] font-bold rounded" title="Sakit"><?= $m['sakit'] ?>S</span>
                                            <span class="px-1.5 py-0.5 bg-rose-100 text-rose-700 text-[9px] font-bold rounded" title="Alpha"><?= $m['alpha'] ?>A</span>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- At-Risk Students -->
                <div class="bg-indigo-950 rounded-2xl shadow-xl shadow-indigo-900/10 overflow-hidden border border-indigo-900">
                    <div class="p-6 bg-indigo-900 border-b border-indigo-800">
                        <h3 class="text-sm font-black text-white uppercase tracking-widest flex items-center gap-2">
                            <i class="fa fa-exclamation-triangle text-amber-500"></i>
                            Mahasiswa Ter-Alpha
                        </h3>
                    </div>
                    <div class="p-4 space-y-3 max-h-[500px] overflow-y-auto custom-scrollbar">
                        <?php if (empty($top_alphas)): ?>
                            <div class="flex flex-col items-center justify-center py-12 px-4 text-center">
                                <div class="w-16 h-16 bg-white/10 rounded-full flex items-center justify-center mb-4">
                                    <i class="fa fa-check-circle text-green-400 text-2xl"></i>
                                </div>
                                <p class="text-xs font-bold text-indigo-200 uppercase tracking-widest">Semua Bersih!</p>
                                <p class="text-[10px] text-indigo-400 mt-1 font-medium">Tidak ada mahasiswa dengan status alpha periode ini.</p>
                            </div>
                        <?php else: ?>
                            <?php foreach ($top_alphas as $a): ?>
                            <div class="bg-white/5 border border-white/5 rounded-xl p-3 flex items-center gap-3 hover:bg-white/10 transition-colors group">
                                <div class="w-10 h-10 rounded-full overflow-hidden border border-white/10 shrink-0">
                                    <img src="../img/profile/<?= htmlspecialchars($a['foto']) ?>" class="w-full h-full object-cover">
                                </div>
                                <div class="flex-grow min-w-0">
                                    <p class="text-[11px] font-black text-white truncate"><?= htmlspecialchars($a['nama_lengkap']) ?></p>
                                    <p class="text-[9px] font-bold text-indigo-300"><?= $a['nim'] ?></p>
                                </div>
                                <div class="flex flex-col items-end shrink-0">
                                    <span class="text-lg font-black text-rose-400 group-hover:scale-110 transition-transform"><?= $a['alpha_count'] ?></span>
                                    <span class="text-[8px] font-bold text-rose-300 uppercase tracking-tighter">Alpha</span>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                    <div class="p-4 bg-indigo-900/30 text-center border-t border-indigo-900/50">
                        <p class="text-[9px] text-indigo-400 font-medium italic">Data ini digunakan untuk monitoring & evaluasi kehadiran mahasiswa.</p>
                    </div>
                </div>
            </div>
        </div>

        <footer class="mt-12 text-[10px] text-slate-400 text-center uppercase font-bold tracking-[0.2em] pb-8">
            © 2025 PTIK C 2024 - Sistem Informasi Data Terpadu
        </footer>
    </main>

    <script>
        // Setup Charts
        const ctxTrend = document.getElementById('trendChart').getContext('2d');
        const ctxDist = document.getElementById('distChart').getContext('2d');

        // Trend Chart Gradient
        const gradientTrend = ctxTrend.createLinearGradient(0, 0, 0, 300);
        gradientTrend.addColorStop(0, 'rgba(79, 70, 229, 0.2)');
        gradientTrend.addColorStop(1, 'rgba(79, 70, 229, 0)');

        new Chart(ctxTrend, {
            type: 'line',
            data: {
                labels: <?= json_encode($trend_labels) ?>,
                datasets: [{
                    label: 'Persentase Hadir (%)',
                    data: <?= json_encode($trend_data) ?>,
                    borderColor: '#4f46e5',
                    borderWidth: 3,
                    pointBackgroundColor: '#fff',
                    pointBorderColor: '#4f46e5',
                    pointBorderWidth: 2,
                    pointRadius: 4,
                    pointHoverRadius: 6,
                    fill: true,
                    backgroundColor: gradientTrend,
                    tension: 0.4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        mode: 'index',
                        intersect: false,
                        padding: 12,
                        backgroundColor: '#1e1b4b',
                        titleFont: { size: 10, weight: 'bold' },
                        bodyFont: { size: 12, weight: 'black' },
                        bodySpacing: 4,
                        displayColors: false,
                        callbacks: {
                            label: function(context) { return context.parsed.y + '% Kehadiran'; }
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        max: 100,
                        grid: { borderDash: [5, 5], color: '#f1f5f9' },
                        ticks: { font: { size: 10, weight: 'bold' }, color: '#94a3b8', callback: v => v + '%' }
                    },
                    x: {
                        grid: { display: false },
                        ticks: { font: { size: 10, weight: 'bold' }, color: '#94a3b8' }
                    }
                }
            }
        });

        new Chart(ctxDist, {
            type: 'doughnut',
            data: {
                labels: ['Hadir', 'Izin', 'Sakit', 'Alpha'],
                datasets: [{
                    data: [
                        <?= $overall_stats['Hadir'] ?>,
                        <?= $overall_stats['Izin'] ?>,
                        <?= $overall_stats['Sakit'] ?>,
                        <?= $overall_stats['Alpha'] ?>
                    ],
                    backgroundColor: ['#6366f1', '#f59e0b', '#0ea5e9', '#f43f5e'],
                    borderWidth: 4,
                    borderColor: '#fff',
                    hoverOffset: 12
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '75%',
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        padding: 12,
                        backgroundColor: '#1e1b4b',
                        bodyFont: { size: 12, weight: 'black' },
                        displayColors: true,
                        boxPadding: 4
                    }
                }
            }
        });

        // Mobile Menu Toggle
        const mobileMenuButton = document.getElementById('mobile-menu-button');
        const sidebar = document.getElementById('sidebar');

        mobileMenuButton.addEventListener('click', () => {
            sidebar.classList.toggle('-translate-x-full');
            sidebar.classList.toggle('mobile-menu-open');
        });

        // Submenu Toggle
        document.querySelectorAll('.submenu-button').forEach(button => {
            button.addEventListener('click', () => {
                const submenu = button.nextElementSibling;
                const arrow = button.querySelector('.submenu-arrow');
                
                submenu.classList.toggle('hidden');
                arrow.classList.toggle('rotate-90');
            });
        });

        // Profile Dropdown
        const profileButton = document.getElementById('profile-button');
        const profileDropdown = document.getElementById('profile-dropdown');

        profileButton.addEventListener('click', (e) => {
            e.stopPropagation();
            profileDropdown.classList.toggle('hidden');
        });

        document.addEventListener('click', () => {
            profileDropdown.classList.add('hidden');
        });
    </script>
</body>

</html>
