<?php
session_start();
include '../koneksi.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../index.php");
    exit;
}

// Restriction: Only Administrator Utama can access this page
if ($_SESSION['role'] !== 'Administrator Utama') {
    include '../includes/alert_role.php';
    exit();
}

$user_id = $_SESSION['user_id'];
$nama_lengkap = $_SESSION['nama_lengkap'];
$foto = $_SESSION['foto'];
$role = $_SESSION['role'];

// Handle POST actions for adding hint or recovery
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    ob_clean(); // Ensure no output before JSON
    header('Content-Type: application/json');
    $action = $_POST['action'] ?? '';
    $target_user_id = isset($_POST['target_user_id']) ? (int)$_POST['target_user_id'] : 0;
    $amount = isset($_POST['amount']) ? (int)$_POST['amount'] : 0;

    if ($action === 'add_hint') {
        $stmt = $koneksi->prepare("UPDATE kuis_harian_stats SET hint_stock = hint_stock + ? WHERE user_id = ?");
        $stmt->bind_param("ii", $amount, $target_user_id);
        if ($stmt->execute()) {
            echo json_encode(['status' => 'success', 'message' => "Berhasil menambah $amount hint."]);
        } else {
            echo json_encode(['status' => 'error', 'message' => "Gagal menambah hint."]);
        }
    } elseif ($action === 'add_recovery') {
        $stmt = $koneksi->prepare("UPDATE kuis_harian_stats SET recovery_stock = recovery_stock + ? WHERE user_id = ?");
        $stmt->bind_param("ii", $amount, $target_user_id);
        if ($stmt->execute()) {
            echo json_encode(['status' => 'success', 'message' => "Berhasil menambah $amount recovery stock."]);
        } else {
            echo json_encode(['status' => 'error', 'message' => "Gagal menambah recovery stock."]);
        }
    } elseif ($action === 'set_streak') {
        $stmt = $koneksi->prepare("UPDATE kuis_harian_stats SET current_streak = ? WHERE user_id = ?");
        $stmt->bind_param("ii", $amount, $target_user_id);
        if ($stmt->execute()) {
            echo json_encode(['status' => 'success', 'message' => "Berhasil menetapkan streak ke $amount."]);
        } else {
            echo json_encode(['status' => 'error', 'message' => "Gagal menetapkan streak."]);
        }
    } elseif ($action === 'restore_streak') {
        date_default_timezone_set('Asia/Makassar');
        $yesterday = date('Y-m-d', strtotime('-1 day'));
        $stmt = $koneksi->prepare("UPDATE kuis_harian_stats SET last_played_date = ? WHERE user_id = ?");
        $stmt->bind_param("si", $yesterday, $target_user_id);
        if ($stmt->execute()) {
            echo json_encode(['status' => 'success', 'message' => "Berhasil memulihkan masa aktif streak."]);
        } else {
            echo json_encode(['status' => 'error', 'message' => "Gagal memulihkan streak."]);
        }
    } elseif ($action === 'global_hint') {
        $stmt = mysqli_prepare($koneksi, "UPDATE kuis_harian_stats SET hint_stock = hint_stock + ?");
        mysqli_stmt_bind_param($stmt, "i", $amount);
        if (mysqli_stmt_execute($stmt)) {
            echo json_encode(['status' => 'success', 'message' => "Berhasil menambah $amount hint ke semua pemain!"]);
        } else {
            echo json_encode(['status' => 'error', 'message' => "Gagal menambah hint global."]);
        }
    } elseif ($action === 'global_recovery') {
        $stmt = mysqli_prepare($koneksi, "UPDATE kuis_harian_stats SET recovery_stock = recovery_stock + ?");
        mysqli_stmt_bind_param($stmt, "i", $amount);
        if (mysqli_stmt_execute($stmt)) {
            echo json_encode(['status' => 'success', 'message' => "Berhasil menambah $amount recovery ke semua pemain!"]);
        } else {
            echo json_encode(['status' => 'error', 'message' => "Gagal menambah recovery global."]);
        }
    } elseif ($action === 'send_reminder') {
        include_once '../functions/kirim_streak.php';
        $res = kirimStreakReminder($koneksi);
        echo json_encode($res);
    }
    exit;
}

// Fetch stats for all players
$query = "
    SELECT 
        s.*, 
        m.nama_lengkap, 
        m.nim, 
        m.foto,
        m.nama_panggilan
    FROM kuis_harian_stats s
    JOIN mahasiswa m ON s.user_id = m.user_id
    ORDER BY s.current_streak DESC, s.total_games DESC
";
$result_stats = mysqli_query($koneksi, $query);
$all_stats = [];
while ($row = mysqli_fetch_assoc($result_stats)) {
    $all_stats[] = $row;
}

// Quiz Questions Logic
$tanggal_filter = $_GET['tanggal'] ?? '';
if (!empty($tanggal_filter)) {
    $stmt = mysqli_prepare($koneksi, "SELECT * FROM kuis_harian_soal WHERE tanggal = ? ORDER BY tanggal ASC");
    mysqli_stmt_bind_param($stmt, "s", $tanggal_filter);
    mysqli_stmt_execute($stmt);
    $result_soal = mysqli_stmt_get_result($stmt);
} else {
    $result_soal = mysqli_query($koneksi, "SELECT * FROM kuis_harian_soal ORDER BY tanggal ASC");
}

// Global Aggregates for Stats Cards
$q_global = mysqli_query($koneksi, "
    SELECT 
        COUNT(id) as total_soal,
        MIN(tanggal) as start_date
    FROM kuis_harian_soal
");
$global_soal_data = mysqli_fetch_assoc($q_global);
$total_soal_bank = $global_soal_data['total_soal'] ?? 0;

// Calculate "Hari Ke-" based on days since start_date
$hari_ke = 0;
if ($global_soal_data['start_date']) {
    $start_ts = strtotime($global_soal_data['start_date']);
    $today_ts = strtotime(date('Y-m-d'));
    $hari_ke = floor(($today_ts - $start_ts) / (60 * 60 * 24)) + 1;
}

$q_sum = mysqli_query($koneksi, "
    SELECT 
        SUM(hint_stock) as sum_hint,
        SUM(recovery_stock) as sum_recovery,
        SUM(total_games) as sum_games,
        SUM(CASE WHEN last_played_date >= CURDATE() - INTERVAL 1 DAY THEN current_streak ELSE 0 END) as active_streak_total
    FROM kuis_harian_stats
");
$sum_data = mysqli_fetch_assoc($q_sum);
$total_hint = $sum_data['sum_hint'] ?? 0;
$total_recovery = $sum_data['sum_recovery'] ?? 0;
$total_games_played = $sum_data['sum_games'] ?? 0;
$total_active_streak = $sum_data['active_streak_total'] ?? 0;

// If AJAX live-search request for questions
if (isset($_GET['ajax_soal']) && $_GET['ajax_soal'] == '1') {
    $no = 1;
    if (mysqli_num_rows($result_soal) > 0) {
        while ($row = mysqli_fetch_assoc($result_soal)) {
            echo '<tr class="hover:bg-slate-50 transition-all border-b border-slate-100">';
            echo '<td class="px-6 py-2 text-[11px] font-bold text-slate-400 text-center">' . ($no++) . '</td>';
            echo '<td class="px-6 py-2 text-[11px] font-bold text-slate-800">' . htmlspecialchars($row['tanggal']) . '</td>';
            echo '<td class="px-6 py-2 text-[11px] font-medium text-slate-600">' . htmlspecialchars($row['pertanyaan']) . '</td>';
            echo '<td class="px-6 py-2 text-[11px] font-bold text-indigo-600">' . htmlspecialchars($row['jawaban']) . '</td>';
            echo '<td class="px-6 py-2 text-right">';
            echo '<div class="flex justify-end gap-1.5">';
            echo '<a href="../actions/edit.php?tabel=kuis_harian_soal&id=' . $row['id'] . '" class="p-1.5 rounded-lg bg-blue-50 text-blue-600 border border-blue-100 hover:bg-blue-600 hover:text-white transition-all"><i class="fas fa-edit text-[10px]"></i></a>';
            echo '<a href="../actions/hapus.php?tabel=kuis_harian_soal&id=' . $row['id'] . '" onclick="return confirm(\'Hapus soal ini?\')" class="p-1.5 rounded-lg bg-rose-50 text-rose-600 border border-rose-100 hover:bg-rose-600 hover:text-white transition-all"><i class="fas fa-trash text-[10px]"></i></a>';
            echo '</div>';
            echo '</td>';
            echo '</tr>';
        }
    } else {
        echo '<tr><td colspan="5" class="px-6 py-10 text-center text-slate-400 text-xs font-bold italic uppercase">Belum ada data soal</td></tr>';
    }
    exit;
}

// Helper for streak fire icons (reusing logic from kuis_harian.php)
function getFireIconAdmin($streak, $isActive = true)
{
    $milestones = [
        200 => ['color' => '#A020F0', 'label' => 'NOVA'],
        100 => ['color' => '#0055FF', 'label' => 'PLASMA'],
        50 => ['color' => '#00A2FF', 'label' => 'INFERNO'],
        30 => ['color' => '#FF3300', 'label' => 'BLAZE'],
        10 => ['color' => '#FF8800', 'label' => 'EMBER'],
        3 => ['color' => '#FFC300', 'label' => 'SPARK']
    ];
    foreach ($milestones as $threshold => $data) {
        if ($streak >= $threshold) return [
            'show' => true,
            'color' => $isActive ? $data['color'] : '#cbd5e1',
            'label' => $data['label'],
            'active' => $isActive
        ];
    }
    return ['show' => false];
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <title>Manajemen Kuis - Admin Panel</title>
    <?php include '../includes/head.php'; ?>
    <style>
        .animate-pulse-slow {
            animation: pulse 3s cubic-bezier(0.4, 0, 0.6, 1) infinite;
        }

        @keyframes pulse {

            0%,
            100% {
                opacity: 1;
            }

            50% {
                opacity: .7;
            }
        }
    </style>
</head>

<body class="bg-slate-50">
    <?php include '../includes/menu_db.php'; ?>

    <main class="ml-0 lg:ml-48 pt-20 p-6 flex flex-col min-h-screen">
        <div class="max-w-7xl mx-auto w-full">

            <!-- Header section -->
            <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4 mb-8">
                <div>
                    <h3 class="text-2xl font-bold text-slate-800 tracking-tight">Kuis Harian Player Stats</h3>
                    <p class="text-xs font-semibold text-slate-400 uppercase tracking-widest mt-1">Global Leaderboard & Resource Management</p>
                </div>
                <div class="flex flex-wrap gap-2 md:gap-3">
                    <button onclick="sendReminder()" class="bg-rose-500 hover:bg-rose-600 text-white px-4 py-2 rounded-xl text-[11px] font-bold flex items-center gap-2 transition-all shadow-lg shadow-rose-500/20 active:scale-95">
                        <i class="fas fa-bell"></i> REMINDER
                    </button>
                    <button onclick="manageGlobal('hint')" class="bg-amber-500 hover:bg-amber-600 text-white px-4 py-2 rounded-xl text-[11px] font-bold flex items-center gap-2 transition-all shadow-lg shadow-amber-500/20 active:scale-95">
                        <i class="fas fa-lightbulb"></i> GLOBAL HINT
                    </button>
                    <button onclick="manageGlobal('recovery')" class="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-xl text-[11px] font-bold flex items-center gap-2 transition-all shadow-lg shadow-indigo-600/20 active:scale-95">
                        <i class="fas fa-shield-alt"></i> GLOBAL RECOVERY
                    </button>
                </div>
            </div>

            <!-- Quick Navigation Tabs -->
            <div class="flex gap-2 mb-8 bg-white/50 p-1.5 rounded-2xl border border-slate-200 w-fit">
                <button onclick="scrollToSection('sec-overview')" class="px-5 py-2 rounded-xl text-[10px] font-bold text-slate-500 hover:bg-white hover:text-indigo-600 hover:shadow-sm transition-all uppercase tracking-widest">Overview</button>
                <div class="w-px h-4 bg-slate-200 self-center"></div>
                <button onclick="scrollToSection('sec-players')" class="px-5 py-2 rounded-xl text-[10px] font-bold text-slate-500 hover:bg-white hover:text-indigo-600 hover:shadow-sm transition-all uppercase tracking-widest">Statistik Pemain</button>
                <div class="w-px h-4 bg-slate-200 self-center"></div>
                <button onclick="scrollToSection('sec-questions')" class="px-5 py-2 rounded-xl text-[10px] font-bold text-slate-500 hover:bg-white hover:text-indigo-600 hover:shadow-sm transition-all uppercase tracking-widest">Bank Soal Kuis</button>
            </div>

            <!-- Stats Overview - Mini Cards -->
            <div id="sec-overview" class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-4 mb-8 scroll-mt-24">
                <!-- Card 1: Kuis Hari Ke -->
                <div class="bg-indigo-600 p-4 rounded-3xl shadow-lg shadow-indigo-600/10 flex flex-col justify-center items-center text-center border border-indigo-500">
                    <div class="text-[9px] font-bold text-indigo-100 uppercase tracking-widest mb-1">KUIS HARI KE</div>
                    <div class="text-2xl font-black text-white"><?= $hari_ke ?></div>
                </div>

                <!-- Card 2: Jumlah Bank Soal -->
                <div class="bg-white p-4 rounded-3xl border border-slate-200 shadow-sm flex flex-col justify-center items-center text-center">
                    <div class="text-[9px] font-bold text-slate-400 uppercase tracking-widest mb-1">BANK SOAL</div>
                    <div class="text-xl font-bold text-slate-800"><?= $total_soal_bank ?> <span class="text-[10px] text-slate-400">Items</span></div>
                </div>

                <!-- Card 3: Total Games Played -->
                <div class="bg-white p-4 rounded-3xl border border-slate-200 shadow-sm flex flex-col justify-center items-center text-center">
                    <div class="text-[9px] font-bold text-slate-400 uppercase tracking-widest mb-1">TOTAL GAMES</div>
                    <div class="text-xl font-bold text-slate-800"><?= number_format($total_games_played) ?></div>
                </div>

                <!-- Card 4: Global Hint -->
                <div class="bg-white p-4 rounded-3xl border border-slate-200 shadow-sm flex flex-col justify-center items-center text-center">
                    <div class="text-[9px] font-bold text-amber-500 uppercase tracking-widest mb-1">GLOBAL HINTS</div>
                    <div class="text-xl font-bold text-slate-800"><?= number_format($total_hint) ?></div>
                </div>

                <!-- Card 5: Global Recovery -->
                <div class="bg-white p-4 rounded-3xl border border-slate-200 shadow-sm flex flex-col justify-center items-center text-center">
                    <div class="text-[9px] font-bold text-indigo-500 uppercase tracking-widest mb-1">GLOBAL RECOVERY</div>
                    <div class="text-xl font-bold text-slate-800"><?= number_format($total_recovery) ?></div>
                </div>

                <!-- Card 6: Total Streak Hidup -->
                <div class="bg-orange-500 p-4 rounded-3xl shadow-xl shadow-orange-500/10 flex flex-col justify-center items-center text-center border border-orange-400">
                    <div class="text-[9px] font-bold text-orange-100 uppercase tracking-widest mb-1">STREAK AKTIF</div>
                    <div class="text-xl font-bold text-white"><?= number_format($total_active_streak) ?>🔥</div>
                </div>
            </div>

            <!-- Player Table Card -->
            <div id="sec-players" class="bg-white rounded-3xl border border-slate-200 shadow-sm overflow-hidden mb-12 scroll-mt-24">
                <div class="p-6 border-b border-slate-100 flex justify-between items-center bg-slate-50/50">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-xl bg-slate-900 flex items-center justify-center text-white">
                            <i class="fas fa-list-ol text-sm"></i>
                        </div>
                        <h4 class="text-xs font-bold text-slate-800 uppercase tracking-widest">Daftar Progres Pemain</h4>
                    </div>
                    <div class="relative">
                        <i class="fas fa-search absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-[10px]"></i>
                        <input type="text" id="playerSearch" placeholder="Cari pemain..." class="bg-slate-100 border-0 rounded-2xl pl-9 pr-4 py-2 text-[11px] font-semibold focus:ring-2 focus:ring-indigo-500/20 w-48 md:w-64 transition-all shadow-sm">
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left" id="playerTable">
                        <thead>
                            <tr class="bg-slate-50 border-b border-slate-200">
                                <th class="px-6 py-3 text-[10px] font-bold text-slate-400 uppercase tracking-widest text-center w-16">Rank</th>
                                <th class="px-6 py-3 text-[10px] font-bold text-slate-400 uppercase tracking-widest">Pemain</th>
                                <th class="px-6 py-3 text-[10px] font-bold text-slate-400 uppercase tracking-widest text-center">Games</th>
                                <th class="px-6 py-3 text-[10px] font-bold text-slate-400 uppercase tracking-widest text-center">Resources</th>
                                <th class="px-6 py-3 text-[10px] font-bold text-slate-400 uppercase tracking-widest text-center">Last Played</th>
                                <th class="px-6 py-3 text-[10px] font-bold text-slate-400 uppercase tracking-widest text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <?php if (empty($all_stats)): ?>
                                <tr>
                                    <td colspan="6" class="px-6 py-20 text-center">
                                        <div class="flex flex-col items-center gap-3 opacity-20">
                                            <i class="fas fa-users-slash text-5xl"></i>
                                            <p class="text-sm font-bold uppercase tracking-widest">No players found</p>
                                        </div>
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php $rank = 1;
                                date_default_timezone_set('Asia/Makassar');
                                $today = date('Y-m-d');
                                $yesterday = date('Y-m-d', strtotime('-1 day'));

                                foreach ($all_stats as $row):
                                    $isBroken = ($row['last_played_date'] !== null && $row['last_played_date'] !== $today && $row['last_played_date'] !== $yesterday);
                                    $fireValue = $isBroken ? 0 : $row['current_streak'];
                                    $fire = getFireIconAdmin($row['current_streak'], !$isBroken);
                                    $isToday = $row['last_played_date'] === $today;
                                ?>
                                    <tr class="hover:bg-slate-50/80 transition-all player-row">
                                        <td class="px-6 py-2 text-center">
                                            <span class="inline-flex items-center justify-center w-7 h-7 rounded-lg font-bold text-xs <?= $rank <= 3 ? ($isBroken ? 'bg-slate-200 text-slate-400' : 'bg-indigo-600 text-white shadow-lg shadow-indigo-600/20') : 'text-slate-400' ?>">
                                                <?= $rank++ ?>
                                            </span>
                                        </td>
                                        <td class="px-6 py-2">
                                            <div class="flex items-center gap-3">
                                                <div class="w-10 h-10 rounded-xl overflow-hidden border-2 border-slate-100 shadow-sm shrink-0 bg-slate-50">
                                                    <img src="<?= profilePhotoUrl($row['foto'], '../') ?>"
                                                        class="w-full h-full object-cover"
                                                        onerror="this.src='../assets/image/logo.webp'">
                                                </div>
                                                <div>
                                                    <div class="text-[11px] font-bold text-slate-700 leading-tight player-name uppercase"><?= htmlspecialchars($row['nama_lengkap']) ?></div>
                                                    <div class="text-[10px] text-slate-400 font-semibold tracking-tight mt-0.5"><?= $row['nim'] ?> • @<?= htmlspecialchars($row['nama_panggilan']) ?></div>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="px-6 py-2 text-center">
                                            <div class="flex flex-col items-center justify-center gap-0.5">
                                                <div class="flex items-center gap-1.5">
                                                    <span class="text-base font-bold <?= $isBroken ? 'text-slate-300' : 'text-slate-800' ?>"><?= $fireValue ?></span>
                                                    <i class="fas fa-fire text-base" style="color: <?= $fire['show'] ? $fire['color'] : '#e2e8f0' ?>"></i>
                                                </div>
                                                <div class="text-[9px] font-bold text-slate-400 uppercase tracking-tighter">Games: <?= $row['total_games'] ?> | Max: <?= $row['max_streak'] ?></div>
                                            </div>
                                        </td>
                                        <td class="px-6 py-2 text-center">
                                            <div class="flex justify-center gap-4">
                                                <div class="text-center">
                                                    <div class="text-amber-500 text-sm font-bold"><?= $row['hint_stock'] ?></div>
                                                    <div class="text-[8px] font-bold text-slate-400 uppercase">Hints</div>
                                                </div>
                                                <div class="text-center">
                                                    <div class="text-indigo-600 text-sm font-bold"><?= $row['recovery_stock'] ?></div>
                                                    <div class="text-[8px] font-bold text-slate-400 uppercase">Recovery</div>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="px-6 py-2 text-center">
                                            <?php if ($row['last_played_date']): ?>
                                                <div class="inline-flex items-center gap-2 px-3 py-1.5 rounded-xl border <?= $isToday ? 'bg-emerald-50 text-emerald-600 border-emerald-100' : 'bg-slate-50 text-slate-400 border-slate-100' ?>">
                                                    <div class="w-1.5 h-1.5 rounded-full <?= $isToday ? 'bg-emerald-500 animate-pulse' : 'bg-slate-300' ?>"></div>
                                                    <span class="text-[10px] font-black uppercase"><?= $isToday ? 'TODAY' : $row['last_played_date'] ?></span>
                                                </div>
                                            <?php else: ?>
                                                <span class="text-[10px] font-bold text-slate-300 italic uppercase">Never Played</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="px-6 py-2 text-right">
                                            <div class="flex justify-end gap-1.5">
                                                <button onclick="manageResources(<?= $row['user_id'] ?>, '<?= htmlspecialchars($row['nama_panggilan']) ?>', 'hint')"
                                                    class="p-2 rounded-lg bg-amber-50 text-amber-600 border border-amber-100 hover:bg-amber-500 hover:text-white transition-all shadow-sm">
                                                    <i class="fas fa-lightbulb text-[10px]"></i>
                                                </button>
                                                <button onclick="manageResources(<?= $row['user_id'] ?>, '<?= htmlspecialchars($row['nama_panggilan']) ?>', 'recovery')"
                                                    class="p-2 rounded-lg bg-indigo-50 text-indigo-600 border border-indigo-100 hover:bg-indigo-600 hover:text-white transition-all shadow-sm">
                                                    <i class="fas fa-shield-alt text-[10px]"></i>
                                                </button>
                                                <button onclick="manageStreak(<?= $row['user_id'] ?>, '<?= htmlspecialchars($row['nama_panggilan']) ?>', <?= $row['current_streak'] ?>)"
                                                    class="p-2 rounded-lg bg-orange-50 text-orange-600 border border-orange-100 hover:bg-orange-500 hover:text-white transition-all shadow-sm">
                                                    <i class="fas fa-fire text-[10px]"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Bank Soal Kuis Section -->
            <div id="sec-questions" class="bg-white rounded-3xl border border-slate-200 shadow-sm overflow-hidden mb-12 scroll-mt-24">
                <div class="px-6 py-4 border-b border-slate-100 flex flex-col md:flex-row justify-between items-start md:items-center gap-4 bg-slate-50/50">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-xl bg-indigo-900 flex items-center justify-center text-white">
                            <i class="fas fa-database text-sm"></i>
                        </div>
                        <div>
                            <h4 class="text-xs font-bold text-slate-800 uppercase tracking-widest">Bank Soal Kuis</h4>
                            <p class="text-[9px] font-bold text-slate-400 uppercase tracking-tighter mt-0.5">Daftar pertanyaan kuis harian</p>
                        </div>
                    </div>
                    <div class="flex flex-wrap items-center gap-2 w-full md:w-auto">
                        <div class="relative w-full md:w-auto flex-grow">
                            <i class="fas fa-calendar absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-[10px]"></i>
                            <input type="date" id="soalFilter" class="bg-white border border-slate-200 rounded-xl pl-9 pr-4 py-2 text-[11px] font-semibold focus:ring-2 focus:ring-indigo-500/20 w-full md:w-48 transition-all shadow-sm">
                        </div>
                        <button id="resetSoal" class="p-2 rounded-xl bg-slate-200 text-slate-600 hover:bg-slate-300 transition-all shadow-sm" title="Reset Filter">
                            <i class="fas fa-sync-alt"></i>
                        </button>
                        <a href="../actions/tambah.php?tabel=kuis_harian_soal" class="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-xl text-[11px] font-bold flex items-center gap-2 transition-all shadow-lg shadow-indigo-600/20 active:scale-95">
                            <i class="fas fa-plus"></i> TAMBAH SOAL
                        </a>
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left">
                        <thead>
                            <tr class="bg-slate-50 border-b border-slate-200">
                                <th class="px-6 py-3 text-[10px] font-bold text-slate-400 uppercase tracking-widest text-center w-16">No</th>
                                <th class="px-6 py-3 text-[10px] font-bold text-slate-400 uppercase tracking-widest w-32">Tanggal</th>
                                <th class="px-6 py-3 text-[10px] font-bold text-slate-400 uppercase tracking-widest">Pertanyaan</th>
                                <th class="px-6 py-3 text-[10px] font-bold text-slate-400 uppercase tracking-widest w-40">Jawaban</th>
                                <th class="px-6 py-3 text-[10px] font-bold text-slate-400 uppercase tracking-widest text-right w-32">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100" id="soalTableBody">
                            <?php
                            $no_soal = 1;
                            if (mysqli_num_rows($result_soal) > 0):
                                while ($row = mysqli_fetch_assoc($result_soal)): ?>
                                    <tr class="hover:bg-slate-50 transition-all border-b border-slate-100">
                                        <td class="px-6 py-2 text-[11px] font-bold text-slate-400 text-center"><?= $no_soal++ ?></td>
                                        <td class="px-6 py-2 text-[11px] font-bold text-slate-800 tracking-tight"><?= htmlspecialchars($row['tanggal']) ?></td>
                                        <td class="px-6 py-2 text-[11px] font-medium text-slate-600 leading-relaxed"><?= htmlspecialchars($row['pertanyaan']) ?></td>
                                        <td class="px-6 py-2 text-[11px] font-bold text-indigo-600"><?= htmlspecialchars($row['jawaban']) ?></td>
                                        <td class="px-6 py-2 text-right">
                                            <div class="flex justify-end gap-1.5">
                                                <a href="../actions/edit.php?tabel=kuis_harian_soal&id=<?= $row['id'] ?>" class="px-1.5 py-0 rounded-lg bg-blue-50 text-blue-600 border border-blue-100 hover:bg-blue-600 hover:text-white transition-all">
                                                    <i class="fas fa-edit text-[10px]"></i>
                                                </a>
                                                <a href="../actions/hapus.php?tabel=kuis_harian_soal&id=<?= $row['id'] ?>" onclick="return confirm('Hapus soal ini?')" class="px-1.5 py-0 rounded-lg bg-rose-50 text-rose-600 border border-rose-100 hover:bg-rose-600 hover:text-white transition-all">
                                                    <i class="fas fa-trash text-[10px]"></i>
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endwhile;
                            else: ?>
                                <tr>
                                    <td colspan="5" class="px-6 py-10 text-center text-slate-400 text-xs font-bold italic uppercase">Belum ada data soal</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <?php include '../includes/footer_db.php'; ?>
    </main>

    <?php include '../includes/script_db.php'; ?>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        // Search functionality
        document.getElementById('playerSearch').addEventListener('keyup', function() {
            let filter = this.value.toLowerCase();
            let rows = document.querySelectorAll('.player-row');
            rows.forEach(row => {
                let name = row.querySelector('.player-name').innerText.toLowerCase();
                row.style.display = name.includes(filter) ? '' : 'none';
            });
        });

        async function manageResources(userId, name, type) {
            const title = type === 'hint' ? `Add Hints for @${name}` : `Add Recovery for @${name}`;
            const icon = type === 'hint' ? 'fa-lightbulb' : 'fa-shield-alt';
            const color = type === 'hint' ? '#f59e0b' : '#4f46e5';

            const {
                value: amount
            } = await Swal.fire({
                title: title,
                html: `
                    <div class="flex flex-col gap-4 py-4 px-2">
                        <div class="flex justify-center gap-3">
                            <button onclick="setAmount(1)" style="border-color: ${color}" class="px-4 py-2 rounded-xl border-2 font-bold text-xs hover:bg-slate-50">+1</button>
                            <button onclick="setAmount(5)" style="border-color: ${color}" class="px-4 py-2 rounded-xl border-2 font-bold text-xs hover:bg-slate-50">+5</button>
                            <button onclick="setAmount(10)" style="border-color: ${color}" class="px-4 py-2 rounded-xl border-2 font-bold text-xs hover:bg-slate-50">+10</button>
                        </div>
                        <input type="number" id="swal-input-amount" class="w-full bg-slate-100 border-0 rounded-2xl px-6 py-4 text-center text-xl font-bold focus:ring-4 focus:ring-indigo-500/10" value="1">
                    </div>
                `,
                showCancelButton: true,
                confirmButtonText: 'CONFIRM',
                confirmButtonColor: color,
                preConfirm: () => {
                    return document.getElementById('swal-input-amount').value;
                },
                didOpen: () => {
                    window.setAmount = (val) => {
                        document.getElementById('swal-input-amount').value = val;
                    }
                }
            });

            if (amount) {
                const formData = new FormData();
                formData.append('action', type === 'hint' ? 'add_hint' : 'add_recovery');
                formData.append('target_user_id', userId);
                formData.append('amount', amount);

                try {
                    const response = await fetch(window.location.href, {
                        method: 'POST',
                        body: formData
                    });
                    const text = await response.text();
                    try {
                        const result = JSON.parse(text);
                        if (result.status === 'success') {
                            Swal.fire({
                                icon: 'success',
                                title: 'Success!',
                                text: result.message,
                                timer: 2000,
                                showConfirmButton: false,
                                background: '#f8fafc'
                            }).then(() => location.reload());
                        } else {
                            Swal.fire('Error', result.message, 'error');
                        }
                    } catch (parseError) {
                        console.error('JSON Parse Error:', text);
                        Swal.fire('Error', 'Respon server tidak valid.', 'error');
                    }
                } catch (error) {
                    Swal.fire('Error', 'Something went wrong!', 'error');
                }
            }
        }

        async function manageStreak(userId, name, currentStreak) {
            const {
                value: result
            } = await Swal.fire({
                title: `Streak Admin: @${name}`,
                html: `
                    <div class="flex flex-col gap-4 py-4 px-2">
                        <div class="text-left">
                            <label class="text-[10px] font-bold text-slate-400 uppercase tracking-widest block mb-1.5 px-1">Set Jumlah Streak</label>
                            <input type="number" id="swal-input-streak" class="w-full bg-slate-100 border-0 rounded-2xl px-6 py-4 text-center text-xl font-bold focus:ring-4 focus:ring-orange-500/10" value="${currentStreak}">
                        </div>
                        <div class="border-t border-slate-100 pt-4">
                            <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest text-center mb-3">Opsi Khusus</p>
                            <button onclick="restoreStreak(${userId})" class="w-full flex items-center justify-center gap-2 bg-emerald-50 text-emerald-600 border border-emerald-100 py-3 rounded-2xl font-bold text-xs hover:bg-emerald-500 hover:text-white transition-all">
                                <i class="fas fa-heart-pulse"></i> PULIHKAN MASA AKTIF
                            </button>
                            <p class="text-[9px] text-slate-400 mt-2 text-center italic">*Membuat streak pemain kembali aktif jika sempat terputus.</p>
                        </div>
                    </div>
                `,
                showCancelButton: true,
                confirmButtonText: 'SAVE STREAK',
                confirmButtonColor: '#f97316',
                preConfirm: () => {
                    return {
                        streak: document.getElementById('swal-input-streak').value
                    }
                },
                didOpen: () => {
                    window.restoreStreak = async (id) => {
                        Swal.showLoading();
                        const formData = new FormData();
                        formData.append('action', 'restore_streak');
                        formData.append('target_user_id', id);

                        try {
                            const res = await fetch(window.location.href, {
                                method: 'POST',
                                body: formData
                            });
                            const text = await res.text();
                            try {
                                const d = JSON.parse(text);
                                if (d.status === 'success') {
                                    Swal.fire({
                                        icon: 'success',
                                        title: d.message,
                                        timer: 1500,
                                        showConfirmButton: false
                                    }).then(() => location.reload());
                                } else {
                                    Swal.fire('Error', d.message, 'error');
                                }
                            } catch (parseError) {
                                console.error('JSON Parse Error:', text);
                                Swal.fire('Error', 'Respon server tidak valid.', 'error');
                            }
                        } catch (e) {
                            Swal.fire('Error', 'Gagal memulihkan.', 'error');
                        }
                    }
                }
            });

            if (result && result.streak !== undefined) {
                const formData = new FormData();
                formData.append('action', 'set_streak');
                formData.append('target_user_id', userId);
                formData.append('amount', result.streak);

                try {
                    const response = await fetch(window.location.href, {
                        method: 'POST',
                        body: formData
                    });
                    const text = await response.text();
                    try {
                        const r = JSON.parse(text);
                        if (r.status === 'success') {
                            Swal.fire({
                                icon: 'success',
                                title: r.message,
                                timer: 1500,
                                showConfirmButton: false
                            }).then(() => location.reload());
                        } else {
                            Swal.fire('Error', r.message, 'error');
                        }
                    } catch (parseError) {
                        console.error('JSON Parse Error:', text);
                        Swal.fire('Error', 'Respon server tidak valid.', 'error');
                    }
                } catch (e) {
                    Swal.fire('Error', 'Gagal menyimpan.', 'error');
                }
            }
        }

        async function manageGlobal(type) {
            const title = type === 'hint' ? 'Tambah Hint Global' : 'Tambah Recovery Global';
            const color = type === 'hint' ? '#f59e0b' : '#4f46e5';

            const {
                value: amount
            } = await Swal.fire({
                title: title,
                text: `Semua pemain (${<?= count($all_stats) ?>}) akan menerima jumlah ini.`,
                input: 'number',
                inputAttributes: {
                    min: 1
                },
                inputValue: 1,
                showCancelButton: true,
                confirmButtonText: 'KONFIRMASI',
                confirmButtonColor: color,
                background: '#f8fafc'
            });

            if (amount) {
                Swal.fire({
                    title: 'Sedang memproses...',
                    allowOutsideClick: false,
                    didOpen: () => Swal.showLoading()
                });
                const formData = new FormData();
                formData.append('action', type === 'hint' ? 'global_hint' : 'global_recovery');
                formData.append('amount', amount);

                try {
                    const res = await fetch(window.location.href, {
                        method: 'POST',
                        body: formData
                    });
                    const text = await res.text();
                    try {
                        const d = JSON.parse(text);
                        if (d.status === 'success') {
                            Swal.fire({
                                icon: 'success',
                                title: 'Berhasil!',
                                text: d.message,
                                timer: 2000,
                                showConfirmButton: false
                            }).then(() => location.reload());
                        } else {
                            Swal.fire('Error', d.message, 'error');
                        }
                    } catch (parseError) {
                        console.error('JSON Parse Error:', text);
                        Swal.fire('Error', 'Respon server tidak valid.', 'error');
                    }
                } catch (e) {
                    Swal.fire('Error', 'Terjadi kesalahan sistem.', 'error');
                }
            }
        }

        async function sendReminder() {
            const confirm = await Swal.fire({
                title: 'Kirim Reminder?',
                text: 'Ini akan mengirim pesan WhatsApp kepada semua pemain yang belum bermain hari ini.',
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: 'Kirim Sekarang',
                confirmButtonColor: '#f43f5e',
                background: '#f8fafc'
            });

            if (confirm.isConfirmed) {
                Swal.fire({
                    title: 'Mengirim reminder...',
                    allowOutsideClick: false,
                    didOpen: () => Swal.showLoading()
                });
                const formData = new FormData();
                formData.append('action', 'send_reminder');

                try {
                    const res = await fetch(window.location.href, {
                        method: 'POST',
                        body: formData
                    });
                    const text = await res.text();
                    try {
                        const d = JSON.parse(text);
                        Swal.fire({
                            icon: d.status === 'success' ? 'success' : (d.status === 'info' ? 'info' : 'error'),
                            title: d.status === 'success' ? 'Berhasil!' : 'Info',
                            text: d.message,
                            confirmButtonColor: '#6366f1'
                        });
                    } catch (parseError) {
                        console.error('JSON Parse Error:', text);
                        Swal.fire('Error', 'Format respon server tidak valid.', 'error');
                    }
                } catch (e) {
                    Swal.fire('Error', 'Gagal mengirim reminder.', 'error');
                }
            }
        }

        function scrollToSection(id) {
            const element = document.getElementById(id);
            if (element) {
                element.scrollIntoView({
                    behavior: 'smooth'
                });
            }
        }

        // Questions Filter Logic
        const soalFilter = document.getElementById('soalFilter');
        const resetSoal = document.getElementById('resetSoal');
        const soalTableBody = document.getElementById('soalTableBody');

        if (soalFilter) {
            soalFilter.addEventListener('change', async function() {
                const tanggal = this.value;
                soalTableBody.innerHTML = '<tr><td colspan="5" class="px-6 py-10 text-center opacity-50"><i class="fas fa-spinner fa-spin mr-2"></i> Loading...</td></tr>';

                try {
                    const response = await fetch(`${window.location.pathname}?tanggal=${tanggal}&ajax_soal=1`);
                    const html = await response.text();
                    soalTableBody.innerHTML = html;
                } catch (e) {
                    soalTableBody.innerHTML = '<tr><td colspan="5" class="px-6 py-10 text-center text-rose-500 font-bold">Gagal memuat data</td></tr>';
                }
            });
        }

        if (resetSoal) {
            resetSoal.addEventListener('click', async function() {
                if (soalFilter) soalFilter.value = '';
                soalTableBody.innerHTML = '<tr><td colspan="5" class="px-6 py-10 text-center opacity-50"><i class="fas fa-spinner fa-spin mr-2"></i> Loading...</td></tr>';

                try {
                    const response = await fetch(`${window.location.pathname}?ajax_soal=1`);
                    const html = await response.text();
                    soalTableBody.innerHTML = html;
                } catch (e) {
                    soalTableBody.innerHTML = '<tr><td colspan="5" class="px-6 py-10 text-center text-rose-500 font-bold">Gagal memuat data</td></tr>';
                }
            });
        }
    </script>
</body>

</html>