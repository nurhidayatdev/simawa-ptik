<?php
session_start();
include '../../koneksi.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../../index.php");
    exit;
}

$user_id = $_SESSION['user_id'];
date_default_timezone_set('Asia/Makassar');
$today = date('Y-m-d');
$yesterday = date('Y-m-d', strtotime('-1 day'));

$user_info = $koneksi->query("SELECT nama_panggilan, foto FROM mahasiswa WHERE user_id = $user_id")->fetch_assoc();
$nama_panggilan = $user_info['nama_panggilan'] ?? 'User';
$foto = $user_info['foto'] ?? 'default.png';

$stmt = $koneksi->prepare("SELECT * FROM kuis_harian_stats WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$stats = $stmt->get_result()->fetch_assoc();

if (!$stats) {
    $koneksi->query("INSERT INTO kuis_harian_stats (user_id) VALUES ($user_id)");
    $stats = $koneksi->query("SELECT * FROM kuis_harian_stats WHERE user_id = $user_id")->fetch_assoc();
}

// Inisialisasi kolom hint jika belum ada di array (untuk BC jika migrate gagal)
if (!isset($stats['hint_stock'])) $stats['hint_stock'] = 0;
if (!isset($stats['last_hint_milestone_claimed'])) $stats['last_hint_milestone_claimed'] = 0;

$streak_broken = false;
if ($stats['last_played_date'] !== null && $stats['last_played_date'] !== $today && $stats['last_played_date'] !== $yesterday) {
    $streak_broken = true;
}

$can_recover = ($stats['recovery_stock'] > 0) && $streak_broken;

$q_soal = $koneksi->prepare("SELECT * FROM kuis_harian_soal WHERE tanggal = ? LIMIT 1");
$q_soal->bind_param("s", $today);
$q_soal->execute();
$soal = $q_soal->get_result()->fetch_assoc();

$has_question = ($soal !== null);
$already_played = ($stats['last_played_date'] === $today && $stats['last_completed_at'] !== null);

// Fetch ALL leaderboard for expand
$q_leaderboard = $koneksi->query("SELECT m.nama_panggilan, m.foto, s.user_id, 
    IF(s.last_played_date >= '$yesterday', s.current_streak, 0) AS current_streak, 
    s.max_streak, s.total_games 
    FROM kuis_harian_stats s 
    JOIN mahasiswa m ON s.user_id = m.user_id 
    ORDER BY current_streak DESC, s.total_games DESC 
    LIMIT 50");
$leaderboard_data = [];
while ($row = mysqli_fetch_assoc($q_leaderboard)) {
    $leaderboard_data[] = $row;
}

if (isset($_POST['action']) && $_POST['action'] === 'use_hint') {
    header('Content-Type: application/json');
    if ($stats['hint_stock'] > 0 && $has_question) {
        $jawaban = $soal['jawaban'];
        $koneksi->query("UPDATE kuis_harian_stats SET hint_stock = hint_stock - 1 WHERE user_id = $user_id");
        echo json_encode(['status' => 'success', 'jawaban' => $jawaban]);
    } else {
        echo json_encode(['status' => 'failed']);
    }
    exit;
}

if (isset($_POST['action']) && $_POST['action'] === 'submit_answer') {
    header('Content-Type: application/json');
    if (!$has_question) {
        echo json_encode(['status' => 'error']);
        exit;
    }
    if ($already_played) {
        echo json_encode(['status' => 'already_played']);
        exit;
    }

    $user_answer = trim(strtoupper($_POST['answer'] ?? ''));
    $correct_answer = strtoupper($soal['jawaban']);

    if ($user_answer === $correct_answer) {
        $current_streak = $stats['current_streak'];
        $max_streak = $stats['max_streak'];
        $total_games = $stats['total_games'] + 1;

        $new_recovery_stock = $stats['recovery_stock'];
        $last_milestone_claimed = $stats['last_milestone_claimed'];

        if ($stats['last_played_date'] === $yesterday || $stats['last_played_date'] === null) {
            $current_streak++;
        } elseif ($stats['last_played_date'] !== $today) {
            $current_streak = 1;
            $new_recovery_stock = 0; // Reset stock when starting new streak after death
            $last_milestone_claimed = 0;
        }
        if ($stats['last_played_date'] === $today && !$already_played) {
            $current_streak++;
        }

        // Milestone Rewards (Recovery & Hint)
        $milestone_config = [
            500 => ['recovery' => 15, 'hint' => 20],
            200 => ['recovery' => 10, 'hint' => 15],
            100 => ['recovery' => 7, 'hint' => 10],
            50  => ['recovery' => 5, 'hint' => 7],
            30  => ['recovery' => 3, 'hint' => 5],
            10  => ['recovery' => 1, 'hint' => 3],
            3   => ['recovery' => 0, 'hint' => 1]
        ];

        $bonus_stock = 0;
        $bonus_hint_stock = 0;
        $new_hint_stock = $stats['hint_stock'];
        $last_hint_milestone_claimed = $stats['last_hint_milestone_claimed'];
        $last_milestone_claimed = $stats['last_milestone_claimed'];

        foreach ($milestone_config as $m_val => $rewards) {
            if ($current_streak >= $m_val && $last_milestone_claimed < $m_val) {
                $bonus_stock += $rewards['recovery'];
                $bonus_hint_stock += $rewards['hint'];
                $last_milestone_claimed = max($last_milestone_claimed, $m_val);
                $last_hint_milestone_claimed = max($last_hint_milestone_claimed, $m_val);
            }
        }
        $new_recovery_stock += $bonus_stock;
        $new_hint_stock += $bonus_hint_stock;

        if ($current_streak > $max_streak) $max_streak = $current_streak;
        $now = date('Y-m-d H:i:s');

        $upd = $koneksi->prepare("UPDATE kuis_harian_stats SET current_streak=?, max_streak=?, total_games=?, last_completed_at=?, last_played_date=?, recovery_stock=?, last_milestone_claimed=?, hint_stock=?, last_hint_milestone_claimed=? WHERE user_id=?");
        $upd->bind_param("iiissiiiii", $current_streak, $max_streak, $total_games, $now, $today, $new_recovery_stock, $last_milestone_claimed, $new_hint_stock, $last_hint_milestone_claimed, $user_id);
        $upd->execute();

        echo json_encode([
            'status' => 'success',
            'new_streak' => $current_streak,
            'bonus_stock' => $bonus_stock,
            'bonus_hint' => $bonus_hint_stock
        ]);
    } else {
        echo json_encode(['status' => 'wrong']);
    }
    exit;
}

if (isset($_POST['action']) && $_POST['action'] === 'use_recovery') {
    header('Content-Type: application/json');
    if ($can_recover) {
        $now = date('Y-m-d H:i:s');
        $koneksi->query("UPDATE kuis_harian_stats SET last_recovery_used_at = '$now', last_played_date = '$yesterday' WHERE user_id = $user_id");
        echo json_encode(['status' => 'success']);
    } else {
        echo json_encode(['status' => 'failed']);
    }
    exit;
}

function getFireIcon($streak, $isActive = true)
{
    $milestones = [
        500 => ['color' => '#ff0000', 'glow' => '#ff6b6b', 'label' => 'LEGENDARY'],
        200 => ['color' => '#ff00ff', 'glow' => '#ff66ff', 'label' => 'EPIC'],
        100 => ['color' => '#00ffff', 'glow' => '#66ffff', 'label' => 'DIAMOND'],
        50 => ['color' => '#ffd700', 'glow' => '#ffeb3b', 'label' => 'GOLD'],
        30 => ['color' => '#ff6600', 'glow' => '#ff9933', 'label' => 'PLATINUM'],
        10 => ['color' => '#e5e7eb', 'glow' => '#f3f4f6', 'label' => 'SILVER'],
        3 => ['color' => '#ff9500', 'glow' => '#ffb347', 'label' => 'FIRE']
    ];
    foreach ($milestones as $threshold => $data) {
        if ($streak >= $threshold) return ['show' => true, 'color' => $isActive ? $data['color'] : '#6b7280', 'glow' => $isActive ? $data['glow'] : '#9ca3af', 'label' => $data['label'], 'active' => $isActive];
    }
    return ['show' => false];
}

$display_streak = ($streak_broken && !$can_recover) ? 0 : $stats['current_streak'];
$fire_is_active = $already_played || ($stats['last_recovery_used_at'] && date('Y-m-d', strtotime($stats['last_recovery_used_at'])) === $today);
$fireInfo = getFireIcon($display_streak, $fire_is_active);
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#1e1b4b" />
    <title>Kuis Harian - PTIK C 2024</title>
    <link rel="icon" href="../img/main/icon.png" />
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="../../frontend/tailwind/src/output.css" rel="stylesheet">
    <link href="../../frontend/tailwind/src/input.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        body {
            font-family: 'Space Grotesk', sans-serif;
            background-color: #0f172a;
            color: #f8fafc;
            font-size: 14px;
        }

        @keyframes fireGlow {

            0%,
            100% {
                filter: drop-shadow(0 0 6px var(--glow-color));
                transform: scale(1);
            }

            50% {
                filter: drop-shadow(0 0 14px var(--glow-color));
                transform: scale(1.08);
            }
        }

        .fire-active {
            animation: fireGlow 1.5s ease-in-out infinite;
        }

        .fire-inactive {
            opacity: 0.5;
        }

        .no-scrollbar::-webkit-scrollbar {
            display: none;
        }

        .no-scrollbar {
            -ms-overflow-style: none;
            scrollbar-width: none;
        }
    </style>
    <!-- Google tag (gtag.js) -->
    <script async src="https://www.googletagmanager.com/gtag/js?id=G-Q0TXETXV6Y"></script>
    <script>
        window.dataLayer = window.dataLayer || [];

        function gtag() {
            dataLayer.push(arguments);
        }
        gtag('js', new Date());

        gtag('config', 'G-Q0TXETXV6Y');
    </script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://cdn.jsdelivr.net/npm/canvas-confetti@1.9.2/dist/confetti.browser.min.js"></script>
</head>

<body class="min-h-screen flex flex-col relative overflow-x-hidden">

    <div class="fixed inset-0 -z-10 overflow-hidden">
        <div class="absolute top-10 left-10 w-24 h-24 bg-indigo-600/20 rounded-full blur-3xl"></div>
        <div class="absolute bottom-20 right-20 w-32 h-32 bg-purple-600/20 rounded-full blur-3xl"></div>
    </div>

    <div class="w-full p-3 flex justify-between items-center z-50 border-b border-slate-800">
        <div class="flex items-center gap-3">
            <img src="../img/main/logo.png" alt="Logo" class="w-8 h-8 object-contain">
            <h1 class="text-base font-bold tracking-tight">KuisHarian</h1>
            <button onclick="showRules()" class="ml-2 w-6 h-6 rounded-full bg-slate-800 text-slate-400 hover:text-white flex items-center justify-center transition-colors border border-slate-700">
                <i class="fa fa-question text-[10px]"></i>
            </button>
        </div>
        <a href="dashboard.php" class="px-3 py-1.5 bg-slate-800/50 hover:bg-slate-700/50 rounded-lg text-xs font-medium border border-slate-700">
            <i class="fa fa-arrow-left mr-1"></i> Dashboard
        </a>
    </div>

    <main class="flex-grow w-full max-w-5xl mx-auto px-3 py-4 grid grid-cols-1 lg:grid-cols-3 gap-4 lg:content-center">

        <div class="lg:col-span-2 flex flex-col items-center">

            <div class="mb-4 flex flex-col items-center">
                <?php if ($fireInfo['show']): ?>
                    <div class="relative mb-1">
                        <div class="text-5xl <?= $fireInfo['active'] ? 'fire-active' : 'fire-inactive' ?>" style="--glow-color: <?= $fireInfo['glow'] ?>; color: <?= $fireInfo['color'] ?>;">
                            <i class="fa fa-fire"></i>
                        </div>
                        <?php if (!$fireInfo['active'] && !$already_played && $has_question): ?>
                            <div class="absolute -bottom-1 -right-1 w-3 h-3 bg-gray-500 rounded-full flex items-center justify-center">
                                <i class="fa fa-hourglass-half text-[6px] text-white"></i>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="text-[12px] font-bold uppercase tracking-widest" style="color: <?= $fireInfo['color'] ?>;"><?= $fireInfo['label'] ?></div>
                <?php endif; ?>

                <div class="mt-2 inline-flex items-center gap-2 bg-slate-800/80 px-4 py-1.5 rounded-full border border-slate-700 shadow-lg">
                    <span class="text-xs font-bold text-slate-200">Streak: <span class="text-indigo-400"><?= $display_streak ?></span></span>
                    <span class="text-[10px] bg-indigo-500/20 text-indigo-300 px-2 py-0.5 rounded-full flex items-center gap-1 border border-indigo-500/30">
                        <i class="fa fa-shield-halved"></i> <?= $stats['recovery_stock'] ?>
                    </span>
                    <span class="text-[10px] bg-amber-500/20 text-amber-300 px-2 py-0.5 rounded-full flex items-center gap-1 border border-amber-500/30" title="Hint Stock">
                        <i class="fa fa-lightbulb"></i> <span id="hint-stock-display"><?= $stats['hint_stock'] ?></span>
                    </span>
                </div>
            </div>

            <?php if ($can_recover): ?>
                <div class="w-full max-w-sm bg-orange-900/30 border border-orange-500/30 rounded-xl p-3 mb-4 text-center">
                    <div class="flex items-center justify-center gap-2 mb-1">
                        <i class="fa fa-shield-halved text-orange-500 text-sm"></i>
                        <span class="font-bold text-orange-400 text-sm">Streak Terputus!</span>
                    </div>
                    <button onclick="useRecovery()" class="bg-orange-600 hover:bg-orange-500 text-white px-4 py-1.5 rounded-lg font-bold text-xs transition">
                        <i class="fa fa-heart-pulse mr-1"></i> Pulihkan (Stock: <?= $stats['recovery_stock'] ?>)
                    </button>
                </div>
            <?php endif; ?>

            <?php if ($already_played): ?>
                <div class="bg-slate-800/60 border border-green-500/30 p-5 rounded-2xl w-full max-w-sm text-center shadow-xl relative overflow-hidden">
                    <div class="absolute top-0 left-0 w-full h-0.5 bg-gradient-to-r from-green-500 to-emerald-400"></div>
                    <div class="w-14 h-14 bg-green-500/20 text-green-400 rounded-full flex items-center justify-center mx-auto mb-4 text-2xl">
                        <i class="fa fa-check"></i>
                    </div>
                    <h2 class="text-xl font-bold text-white mb-1">Selesai!</h2>
                    <p class="text-slate-400 text-xs mb-4">Kembali besok untuk lanjut!</p>
                    <div class="grid grid-cols-2 gap-3 border-t border-slate-700 pt-4">
                        <div class="bg-slate-900/50 p-3 rounded-lg">
                            <div class="text-[10px] text-slate-500 uppercase font-bold">Total</div>
                            <div class="text-lg font-black text-white"><?= $stats['total_games'] ?></div>
                        </div>
                        <div class="bg-slate-900/50 p-3 rounded-lg">
                            <div class="text-[10px] text-slate-500 uppercase font-bold">Max</div>
                            <div class="text-lg font-black text-indigo-400"><?= $stats['max_streak'] ?></div>
                        </div>
                    </div>
                </div>

            <?php elseif ($has_question && !$already_played && !$can_recover): ?>
                <div class="w-full max-w-sm bg-slate-800/60 border border-slate-700 p-5 rounded-2xl shadow-xl">
                    <div class="text-center mb-4">
                        <div class="inline-flex items-center gap-1.5 bg-indigo-500/10 text-indigo-400 px-3 py-1 rounded-full border border-indigo-500/20 text-[10px] font-black uppercase tracking-tighter mb-4">
                            <i class="fa fa-hashtag"></i>
                            <span>Kuis #<?= $soal['id'] ?></span>
                        </div>
                        <h3 class="text-[10px] font-bold text-slate-500 uppercase tracking-widest mb-2">Pertanyaan</h3>
                        <p class="text-base font-bold text-white leading-snug">"<?= htmlspecialchars($soal['pertanyaan']) ?>"</p>
                    </div>
                    <form id="quizForm" class="space-y-3" onsubmit="submitAnswer(event)">
                        <div class="relative">
                            <input type="text" id="answer" class="w-full bg-slate-900/50 border-2 border-slate-600 text-white text-center text-base font-bold py-3 rounded-lg focus:border-indigo-500 transition placeholder-slate-600 uppercase tracking-widest" placeholder="JAWABAN..." autocomplete="off" required>
                            <button type="button" onclick="useHint()" class="absolute right-3 top-1/2 -translate-y-1/2 text-amber-400 hover:text-amber-300 transition" title="Gunakan Hint (1 Stock)">
                                <i class="fa fa-lightbulb text-lg"></i>
                            </button>
                        </div>
                        <button type="submit" class="w-full bg-gradient-to-r from-indigo-600 to-violet-600 hover:from-indigo-500 hover:to-violet-500 text-white font-bold py-3 rounded-lg shadow-lg transition active:scale-95 flex items-center justify-center gap-2 text-sm">
                            <span>Jawab</span><i class="fa fa-paper-plane"></i>
                        </button>
                    </form>
                </div>

            <?php elseif (!$has_question): ?>
                <div class="text-center p-6 bg-slate-800/50 rounded-2xl border border-slate-700 max-w-sm w-full">
                    <i class="fa fa-mug-hot text-3xl text-slate-500 mb-3"></i>
                    <h3 class="text-base font-bold text-white">Istirahat</h3>
                    <p class="text-slate-400 text-xs mt-1">Belum ada pertanyaan hari ini.</p>
                </div>
            <?php endif; ?>

            <div class="mt-5 w-full max-w-sm">
                <div class="text-[10px] text-slate-500 text-center mb-2 font-bold uppercase tracking-wider">Milestone</div>
                <div class="flex flex-wrap justify-center gap-1.5">
                    <?php
                    $milestones = [3, 10, 30, 50, 100, 200, 500];
                    $colors = ['#ff9500', '#e5e7eb', '#ff6600', '#ffd700', '#00ffff', '#ff00ff', '#ff0000'];
                    foreach ($milestones as $i => $m):
                        $achieved = $display_streak >= $m;
                    ?>
                        <div class="flex items-center gap-0.5 px-1.5 py-0.5 rounded <?= $achieved ? 'bg-slate-700' : 'bg-slate-800/50' ?> border <?= $achieved ? 'border-slate-600' : 'border-slate-700/50' ?>">
                            <i class="fa fa-fire text-[10px]" style="color: <?= $achieved ? $colors[$i] : '#4b5563' ?>;"></i>
                            <span class="text-[9px] font-bold <?= $achieved ? 'text-white' : 'text-slate-600' ?>"><?= $m ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <!-- Leaderboard -->
        <div class="lg:col-span-1">
            <div class="bg-slate-800/50 rounded-xl border border-slate-700 overflow-hidden h-fit sticky top-4">
                <div class="p-3 border-b border-slate-700 bg-slate-800 flex justify-between items-center">
                    <h3 class="font-bold text-white text-sm flex items-center gap-1.5">
                        <i class="fa fa-trophy text-yellow-500 text-xs"></i> Leaderboard
                    </h3>
                    <span class="text-[10px] text-slate-500">Top <?= count($leaderboard_data) ?></span>
                </div>
                <div id="leaderboard-list" class="p-2 space-y-1 max-h-[400px] overflow-y-auto no-scrollbar transition-all">
                    <?php
                    $rank = 1;
                    foreach ($leaderboard_data as $index => $lb):
                        $lbFire = getFireIcon($lb['current_streak'], true);
                        $isMe = ($lb['user_id'] == $user_id);

                    ?>
                        <div class="flex items-center gap-2 p-2 rounded-lg <?= $isMe ? 'bg-indigo-500/10 border border-indigo-500/20' : 'hover:bg-slate-700/30' ?> transition-colors">
                            <span class="text-xs font-black w-5 text-center <?= $rank == 1 ? 'text-yellow-500' : ($rank == 2 ? 'text-slate-300' : ($rank == 3 ? 'text-orange-400' : 'text-slate-600')) ?>"><?= $rank++ ?></span>
                            <div class="w-7 h-7 rounded-full bg-slate-700 overflow-hidden border border-slate-600">
                                <img src="../img/profile/<?= htmlspecialchars($lb['foto']) ?>" class="w-full h-full object-cover" onerror="this.src='../../img/profile/default.png'">
                            </div>
                            <div class="flex-grow min-w-0">
                                <div class="text-xs font-bold text-slate-200 truncate flex items-center gap-1">
                                    <?= htmlspecialchars($lb['nama_panggilan']) ?>
                                    <?php if ($lbFire['show']): ?><i class="fa fa-fire text-[10px]" style="color: <?= $lbFire['color'] ?>;"></i><?php endif; ?>
                                </div>
                                <div class="text-[9px] text-slate-500"><?= $lb['current_streak'] ?> streak • <?= $lb['total_games'] ?> games</div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

            </div>
        </div>

    </main>

    <footer class="w-full py-6 px-3 border-t border-slate-800 mt-auto">
        <div class="max-w-5xl mx-auto text-center">
            <p class="text-[10px] text-slate-500 font-bold uppercase tracking-widest">© 2025 Kelas PTIK C - Teknik Informatika dan Komputer FT UNM. All rights reserved.</p>
        </div>
    </footer>

    <script>
        const USER_NAME = "<?= htmlspecialchars($nama_panggilan) ?>";
        const USER_PHOTO = "../img/profile/<?= htmlspecialchars($foto) ?>";
        const ANS_LENGTH = <?= $has_question ? strlen($soal['jawaban']) : 0 ?>;
        let hintStock = <?= (int)$stats['hint_stock'] ?>;
        let hintsUsed = 0;
        const MILESTONES = {
            500: {
                label: 'LEGENDARY',
                color: '#ff0000'
            },
            200: {
                label: 'EPIC',
                color: '#ff00ff'
            },
            100: {
                label: 'DIAMOND',
                color: '#00ffff'
            },
            50: {
                label: 'GOLD',
                color: '#ffd700'
            },
            30: {
                label: 'PLATINUM',
                color: '#ff6600'
            },
            10: {
                label: 'SILVER',
                color: '#e5e7eb'
            },
            3: {
                label: 'FIRE',
                color: '#ff9500'
            }
        };

        function getMilestone(s) {
            for (const [t, d] of Object.entries(MILESTONES).sort((a, b) => b[0] - a[0]))
                if (s >= parseInt(t)) return {
                    threshold: parseInt(t),
                    ...d
                };
            return null;
        }

        function isNewMilestone(s) {
            return [3, 10, 30, 50, 100, 200, 500].includes(s);
        }



        function triggerConfetti() {
            const d = 3000,
                e = Date.now() + d,
                f = {
                    startVelocity: 30,
                    spread: 360,
                    ticks: 60,
                    zIndex: 10000
                };
            const i = setInterval(() => {
                const t = e - Date.now();
                if (t <= 0) return clearInterval(i);
                const p = 50 * (t / d);
                confetti({
                    ...f,
                    particleCount: p,
                    origin: {
                        x: Math.random() * 0.2 + 0.1,
                        y: Math.random() - 0.2
                    },
                    colors: ['#ff9500', '#ffd700', '#ff6600', '#ff00ff', '#00ffff']
                });
                confetti({
                    ...f,
                    particleCount: p,
                    origin: {
                        x: Math.random() * 0.2 + 0.7,
                        y: Math.random() - 0.2
                    },
                    colors: ['#ff9500', '#ffd700', '#ff6600', '#ff00ff', '#00ffff']
                });
            }, 250);
        }

        function showMilestonePopup(streak, bonus = 0, bonusHint = 0) {
            const m = getMilestone(streak);
            const isM = isNewMilestone(streak);
            if (isM) triggerConfetti();
            Swal.fire({
                html: `<style>@keyframes ff{0%,100%{transform:scale(1)translateY(0)}25%{transform:scale(1.1)translateY(-6px)}50%{transform:scale(1.15)translateY(-10px)}75%{transform:scale(1.1)translateY(-6px)}}@keyframes gw{0%,100%{filter:drop-shadow(0 0 15px ${m?.color||'#ff9500'})}50%{filter:drop-shadow(0 0 30px ${m?.color||'#ff9500'})}}.fc{animation:ff 1s ease-in-out infinite,gw 1s ease-in-out infinite}@keyframes si{0%{transform:scale(0);opacity:0}50%{transform:scale(1.1)}100%{transform:scale(1);opacity:1}}@keyframes bi{0%{transform:scale(0)}50%{transform:scale(1.1)}100%{transform:scale(1)}}.pb{animation:bi .5s ease-out}.mb{animation:si .5s ease-out forwards}</style><div class="text-center py-3"><div class="pb mb-2"><img src="${USER_PHOTO}" class="w-12 h-12 rounded-full mx-auto border-3 shadow-lg" style="border-color:${m?.color||'#ff9500'}" onerror="this.src='../../img/profile/default.png'"></div><div class="text-sm font-bold text-white mb-2">${USER_NAME}</div><div class="fc mb-3" style="color:${m?.color||'#ff9500'};font-size:3rem"><i class="fa fa-fire"></i></div>${isM?`<div class="mb inline-block bg-gradient-to-r from-amber-500 to-orange-600 text-white px-3 py-1 rounded-full text-xs font-bold mb-2 shadow-lg">MILESTONE UNLOCKED!</div><div class="text-lg font-black mb-1" style="color:${m?.color}">${m?.label}</div>`:''}<div class="text-2xl font-black text-white mb-1">Streak ${streak}!</div><div class="flex flex-col gap-1 items-center mt-2">${bonus > 0 ? `<div class="text-indigo-400 text-[10px] font-bold"><i class="fa fa-shield-halved mr-1"></i> +${bonus} Recovery Stock</div>` : ''}${bonusHint > 0 ? `<div class="text-amber-400 text-[10px] font-bold"><i class="fa fa-lightbulb mr-1"></i> +${bonusHint} Hint Stock</div>` : ''}</div><div class="text-slate-400 text-xs mt-2">Luar biasa!</div></div>`,
                showConfirmButton: true,
                confirmButtonText: 'OK',
                confirmButtonColor: '#6366f1',
                background: '#1e293b',
                color: '#fff',
                allowOutsideClick: false
            }).then(() => location.reload());
        }

        function submitAnswer(e) {
            e.preventDefault();
            const i = document.getElementById('answer'),
                b = e.target.querySelector('button'),
                o = b.innerHTML;
            b.disabled = true;
            b.innerHTML = '<i class="fa fa-spinner fa-spin"></i>';
            const f = new FormData();
            f.append('action', 'submit_answer');
            f.append('answer', i.value);
            fetch('kuis_harian.php', {
                method: 'POST',
                body: f
            }).then(r => r.json()).then(d => {
                b.disabled = false;
                b.innerHTML = o;
                if (d.status === 'success') showMilestonePopup(d.new_streak, d.bonus_stock, d.bonus_hint);
                else if (d.status === 'already_played') location.reload();
                else {
                    Swal.fire({
                        title: 'Salah',
                        text: 'Coba lagi!',
                        icon: 'error',
                        background: '#1e293b',
                        color: '#fff',
                        confirmButtonColor: '#ef4444'
                    });
                    i.value = '';
                    i.focus();
                }
            }).catch(() => {
                b.disabled = false;
                b.innerHTML = o;
                Swal.fire('Error', 'Koneksi bermasalah', 'error');
            });
        }

        function useRecovery() {
            Swal.fire({
                title: 'Pulihkan Streak?',
                html: `<p class="text-xs">Menggunakan 1 Recovery Stock<br>Current Stock: <?= $stats['recovery_stock'] ?></p>`,
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: 'Ya',
                confirmButtonColor: '#ea580c',
                background: '#1e293b',
                color: '#fff'
            }).then(r => {
                if (r.isConfirmed) {
                    const f = new FormData();
                    f.append('action', 'use_recovery');
                    fetch('kuis_harian.php', {
                        method: 'POST',
                        body: f
                    }).then(r => r.json()).then(d => {
                        if (d.status === 'success') Swal.fire({
                            title: 'Berhasil!',
                            icon: 'success',
                            background: '#1e293b',
                            color: '#fff'
                        }).then(() => location.reload());
                        else Swal.fire('Gagal', '', 'error');
                    });
                }
            });
        }

        function useHint() {
            if (hintStock <= 0) {
                return Swal.fire({
                    title: 'Stok Habis',
                    text: 'Capai milestone berikutnya untuk mendapat stok hint.',
                    icon: 'warning',
                    background: '#1e293b',
                    color: '#fff',
                    confirmButtonColor: '#fbbf24'
                });
            }

            if (hintsUsed >= ANS_LENGTH) {
                return Swal.fire({
                    title: 'Hint Maksimal',
                    text: 'Semua huruf sudah terbuka!',
                    icon: 'info',
                    background: '#1e293b',
                    color: '#fff',
                    confirmButtonColor: '#fbbf24'
                });
            }

            Swal.fire({
                title: 'Gunakan Hint?',
                html: `<p class="text-xs">Menggunakan 1 Hint Stock untuk membuka huruf berikutnya.<br>Stok Saat Ini: ${hintStock}</p>`,
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: 'Gunakan',
                confirmButtonColor: '#fbbf24',
                background: '#1e293b',
                color: '#fff'
            }).then(r => {
                if (r.isConfirmed) {
                    const f = new FormData();
                    f.append('action', 'use_hint');
                    fetch('kuis_harian.php', {
                        method: 'POST',
                        body: f
                    }).then(r => r.json()).then(d => {
                        if (d.status === 'success') {
                            hintsUsed++;
                            hintStock--;

                            // Update display
                            const display = document.getElementById('hint-stock-display');
                            if (display) display.innerText = hintStock;

                            const jaw = d.jawaban.toUpperCase();
                            let revealStr = "";
                            for (let i = 0; i < jaw.length; i++) {
                                if (i < hintsUsed) revealStr += jaw[i] + " ";
                                else revealStr += "_ ";
                            }

                            Swal.fire({
                                title: revealStr.trim(),
                                html: `<p class="text-slate-400 text-xs mt-4">Simpan baik-baik bantuan ini!</p>`,
                                background: '#1e293b',
                                color: '#fff',
                                confirmButtonColor: '#fbbf24'
                            });
                        } else {
                            Swal.fire('Gagal', 'Stok tidak cukup', 'error');
                        }
                    });
                }
            });
        }

        function showRules() {
            Swal.fire({
                title: 'Aturan Bermain',
                html: `
                    <div class="text-left space-y-4 text-slate-300">
                        <div class="bg-indigo-500/10 p-3 rounded-lg border border-indigo-500/20">
                            <h4 class="text-indigo-400 font-bold text-sm mb-1"><i class="fa fa-info-circle mr-2"></i>Mekanisme Dasar</h4>
                            <p class="text-[11px] leading-relaxed">Jawab 1 pertanyaan setiap hari untuk menjaga streak. Pertanyaan diperbarui setiap jam 00:00 WITA. Peringkat <b>Leaderboard</b> ditentukan berdasarkan <b>Streak Tertinggi saat ini</b>, kemudian <b>Total Games</b> sebagai penentu jika skor seri.</p>
                        </div>
                        
                        <div class="bg-orange-500/10 p-3 rounded-lg border border-orange-500/20">
                            <h4 class="text-orange-400 font-bold text-sm mb-1"><i class="fa fa-shield-halved mr-2"></i>Sistem Recovery & Hint</h4>
                            <p class="text-[11px] leading-relaxed">Gunakan <b>Recovery Stock</b> untuk memulihkan streak. Gunakan <b>Hint Stock</b> (tombol lampu) untuk membuka satu huruf jawaban sebagai bantuan.</p>
                        </div>

                        <div>
                            <h4 class="text-white font-bold text-sm mb-2"><i class="fa fa-fire mr-2"></i>Milestone & Rewards</h4>
                            <div class="grid grid-cols-2 gap-2">
                                <div class="bg-slate-800/50 p-2 rounded border border-slate-700 text-[10px]">
                                    <div class="text-[#ff9500] font-bold uppercase">FIRE (3)</div>
                                    <div class="text-amber-400">+1 Hint Stock</div>
                                </div>
                                <div class="bg-slate-800/50 p-2 rounded border border-slate-700 text-[10px]">
                                    <div class="text-[#e5e7eb] font-bold uppercase">SILVER (10)</div>
                                    <div class="text-indigo-400">+1 Recovery Stock</div>
                                    <div class="text-amber-400">+3 Hint Stock</div>
                                </div>
                                <div class="bg-slate-800/50 p-2 rounded border border-slate-700 text-[10px]">
                                    <div class="text-[#ff6600] font-bold uppercase">PLATINUM (30)</div>
                                    <div class="text-indigo-400">+3 Recovery Stock</div>
                                    <div class="text-amber-400">+5 Hint Stock</div>
                                </div>
                                <div class="bg-slate-800/50 p-2 rounded border border-slate-700 text-[10px]">
                                    <div class="text-[#ffd700] font-bold uppercase">GOLD (50)</div>
                                    <div class="text-indigo-400">+5 Recovery Stock</div>
                                    <div class="text-amber-400">+7 Hint Stock</div>
                                </div>
                                <div class="bg-slate-800/50 p-2 rounded border border-slate-700 text-[10px]">
                                    <div class="text-[#00ffff] font-bold uppercase">DIAMOND (100)</div>
                                    <div class="text-indigo-400">+7 Recovery Stock</div>
                                    <div class="text-amber-400">+10 Hint Stock</div>
                                </div>
                                <div class="bg-slate-800/50 p-2 rounded border border-slate-700 text-[10px]">
                                    <div class="text-[#ff00ff] font-bold uppercase">EPIC (200)</div>
                                    <div class="text-indigo-400">+10 Recovery Stock</div>
                                    <div class="text-amber-400">+15 Hint Stock</div>
                                </div>
                                <div class="bg-slate-800/50 p-2 rounded border border-slate-700 text-[10px] col-span-2">
                                    <div class="text-[#ff0000] font-bold uppercase">LEGENDARY (500)</div>
                                    <div class="text-indigo-400">+15 Recovery Stock</div>
                                    <div class="text-amber-400">+20 Hint Stock</div>
                                </div>
                            </div>
                        </div>

                        <div class="bg-red-500/10 p-3 rounded-lg border border-red-500/20">
                            <h4 class="text-red-400 font-bold text-sm mb-1"><i class="fa fa-skull mr-2"></i>Peringatan Mati Total</h4>
                            <p class="text-[11px] leading-relaxed italic">Jika streak putus dan anda bermain tanpa memulihkannya, maka <b>semua stok recovery akan hangus (0)</b>.</p>
                        </div>
                    </div>
                `,
                confirmButtonText: 'Siap, Mengerti!',
                confirmButtonColor: '#6366f1',
                background: '#1e293b',
                color: '#fff',
                width: '32rem'
            });
        }

        window.onload = function() {
            const isRecoveryMode = <?= $can_recover ? 'true' : 'false' ?>;
            if (!sessionStorage.getItem('rulesShown') || isRecoveryMode) {
                showRules();
                if (!isRecoveryMode) sessionStorage.setItem('rulesShown', 'true');
            }
        };
    </script>
</body>

</html>