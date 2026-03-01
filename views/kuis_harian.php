<?php
session_start();
include '../koneksi.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../index.php");
    exit;
}

$user_id = $_SESSION['user_id'];
date_default_timezone_set('Asia/Makassar');
$today = date('Y-m-d');
$yesterday = date('Y-m-d', strtotime('-1 day'));

$user_info = $koneksi->query("SELECT nama_panggilan, foto FROM mahasiswa WHERE user_id = $user_id")->fetch_assoc();
$nama_panggilan = $user_info['nama_panggilan'] ?? 'User';
$foto = $user_info['foto'] ?? 'default.png';

include_once '../functions/foto_helpers.php';
include_once '../functions/helpers.php';

$stmt = $koneksi->prepare("SELECT * FROM kuis_harian_stats WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$stats = $stmt->get_result()->fetch_assoc();

if (!$stats) {
    $koneksi->query("INSERT INTO kuis_harian_stats (user_id) VALUES ($user_id)");
    $stats = $koneksi->query("SELECT * FROM kuis_harian_stats WHERE user_id = $user_id")->fetch_assoc();
}

// Ensure $stats is an array to avoid further errors
if (!$stats) {
    $stats = [
        'current_streak' => 0,
        'max_streak' => 0,
        'total_games' => 0,
        'last_played_date' => null,
        'last_completed_at' => null,
        'recovery_stock' => 0,
        'last_milestone_claimed' => 0,
        'hint_stock' => 0,
        'last_hint_milestone_claimed' => 0,
        'last_recovery_used_at' => null
    ];
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
    s.max_streak, s.total_games, s.last_completed_at 
    FROM kuis_harian_stats s 
    JOIN mahasiswa m ON s.user_id = m.user_id 
    ORDER BY current_streak DESC, s.total_games DESC, s.last_completed_at ASC 
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
            200 => ['recovery' => 30, 'hint' => 40],
            100 => ['recovery' => 20, 'hint' => 25],
            50  => ['recovery' => 15, 'hint' => 20],
            30  => ['recovery' => 10, 'hint' => 15],
            10  => ['recovery' => 5, 'hint' => 10],
            3   => ['recovery' => 1, 'hint' => 3]
        ];

        $bonus_stock = 0;
        $bonus_hint_stock = 0;
        $new_hint_stock = $stats['hint_stock'];
        $last_hint_milestone_claimed = $stats['last_hint_milestone_claimed'];
        $last_milestone_claimed = $stats['last_milestone_claimed'];

        $new_milestone_reached = null;
        foreach ($milestone_config as $m_val => $rewards) {
            if ($current_streak >= $m_val && $last_milestone_claimed < $m_val) {
                $bonus_stock += $rewards['recovery'];
                $bonus_hint_stock += $rewards['hint'];
                $last_milestone_claimed = max($last_milestone_claimed, $m_val);
                $last_hint_milestone_claimed = max($last_hint_milestone_claimed, $m_val);
                $new_milestone_reached = $m_val;
            }
        }
        $new_recovery_stock += $bonus_stock;
        $new_hint_stock += $bonus_hint_stock;

        // Push WhatsApp Notification for Milestones
        if ($new_milestone_reached) {
            $milestone_titles = [
                200 => 'NOVA 🌌',
                100 => 'PLASMA 💠',
                50  => 'INFERNO 🌋',
                30  => 'BLAZE 🔥',
                10  => 'EMBER 🎇',
                3   => 'SPARK ✨'
            ];
            $title = $milestone_titles[$new_milestone_reached] ?? 'Legendary';
            $wa_message = "🏆 *MILESTONE KUIS HARIAN* 🏆\n\nSelamat kepada *@$nama_panggilan* yang telah mencapai milestone *$title* dengan streak sebanyak *{$current_streak} Hari* berturut-turut! 🎉\n\n🎁 *Rewards:* \n- +{$bonus_stock} Recovery Stock\n- +{$bonus_hint_stock} Hint Stock\n\n_Terus asah ilmumu dan jaga api streak-mu!_ 🔥";
            sendFonnteMessage($wa_message);
        }

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
        200 => ['color' => '#A020F0', 'glow' => '#C460FF', 'label' => 'NOVA'],
        100 => ['color' => '#0055FF', 'glow' => '#4D8AFF', 'label' => 'PLASMA'],
        50 => ['color' => '#00A2FF', 'glow' => '#4DC4FF', 'label' => 'INFERNO'],
        30 => ['color' => '#FF3300', 'glow' => '#FF6640', 'label' => 'BLAZE'],
        10 => ['color' => '#FF8800', 'glow' => '#FFAD40', 'label' => 'EMBER'],
        3 => ['color' => '#FFC300', 'glow' => '#FFE066', 'label' => 'SPARK']
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

    <title>Kuis Harian - PTIK C 2024</title>
    <?php include '../includes/head.php'; ?>
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
            <img src="../assets/image/logo.webp" alt="Logo" class="w-8 h-8 object-contain">
            <h1 class="text-base font-bold tracking-tight">KuisHarian</h1>
            <button onclick="showRules()" class="ml-2 w-6 h-6 rounded-full bg-slate-800 text-slate-400 hover:text-white flex items-center justify-center transition-colors border border-slate-700">
                <i class="fa fa-question text-[10px]"></i>
            </button>
        </div>
        <a href="../dashboard.php" class="px-3 py-1.5 bg-slate-800/50 hover:bg-slate-700/50 rounded-lg text-xs font-medium border border-slate-700">
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
                        <i class="fa fa-shield"></i> <?= $stats['recovery_stock'] ?>
                    </span>
                    <span class="text-[10px] bg-amber-500/20 text-amber-300 px-2 py-0.5 rounded-full flex items-center gap-1 border border-amber-500/30" title="Hint Stock">
                        <i class="fa fa-lightbulb"></i> <span id="hint-stock-display"><?= $stats['hint_stock'] ?></span>
                    </span>
                </div>
            </div>

            <?php if ($can_recover): ?>
                <div class="w-full max-w-sm bg-orange-900/30 border border-orange-500/30 rounded-xl p-3 mb-4 text-center">
                    <div class="flex items-center justify-center gap-2 mb-1">
                        <i class="fa fa-shield text-orange-500 text-sm"></i>
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
                        <div class="inline-flex items-center gap-1.5 bg-indigo-500/10 text-indigo-400 px-3 py-1 rounded-full border border-indigo-500/20 text-[10px] font-extrabold uppercase tracking-tighter mb-4">

                            <span>Kuis#<?= $soal['id'] ?></span>
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
                    $milestones = [3, 10, 30, 50, 100, 200];
                    $colors = ['#FFC300', '#FF8800', '#FF3300', '#00A2FF', '#0055FF', '#A020F0'];
                    foreach ($milestones as $i => $m):
                        $achieved = $display_streak >= $m;
                    ?>
                        <div class="flex items-center gap-0.5 px-1.5 py-0.5 rounded <?= $achieved ? 'bg-slate-700' : 'bg-slate-800/50' ?> border <?= $achieved ? 'border-slate-600' : 'border-slate-700/50' ?>">
                            <i class="fa fa-fire text-xs" style="color: <?= $achieved ? $colors[$i] : '#4b5563' ?>;"></i>
                            <span class="text-[10px] font-bold <?= $achieved ? 'text-white' : 'text-slate-600' ?>"><?= $m ?></span>
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
                        <div class="flex items-center gap-3 p-2 rounded-lg <?= $isMe ? 'bg-indigo-500/10 border border-indigo-500/20' : 'hover:bg-slate-700/30' ?> transition-colors">
                            <span class="text-xs font-black w-4 text-center <?= $rank == 1 ? 'text-yellow-500' : ($rank == 2 ? 'text-slate-300' : ($rank == 3 ? 'text-orange-400' : 'text-slate-600')) ?>"><?= $rank++ ?></span>
                            <div class="w-8 h-8 rounded-full bg-slate-700 overflow-hidden border border-slate-600 shrink-0">
                                <img src="<?= profilePhotoUrl($lb['foto'], '../') ?>" class="w-full h-full object-cover" onerror="this.src='../assets/image/logo.webp'">
                            </div>
                            <div class="flex-grow min-w-0 flex flex-col justify-center">
                                <div class="text-xs font-bold text-slate-200 truncate leading-tight">
                                    <?= htmlspecialchars($lb['nama_panggilan']) ?>
                                </div>
                                <div class="text-[10px] text-slate-500 font-medium">
                                    <?= $lb['total_games'] ?> games
                                </div>
                            </div>
                            <?php if ($lbFire['show']): ?>
                                <div class="flex items-center gap-2 shrink-0 w-16" style="color: <?= $lbFire['color'] ?>;">
                                    <i class="fa fa-fire text-sm w-4 text-center"></i>
                                    <span class="text-sm font-black"><?= $lb['current_streak'] ?></span>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>

            </div>
        </div>

    </main>

    <footer class="w-full py-6 px-3 border-t border-slate-800 mt-auto">
        <div class="max-w-5xl mx-auto text-center">
            <p class="text-[10px] text-slate-500 font-bold uppercase tracking-widest">© <?php echo date("Y"); ?> Kelas PTIK C - Teknik Informatika dan Komputer FT UNM. All rights reserved.</p>
        </div>
    </footer>

    <script>
        const USER_NAME = "<?= htmlspecialchars($nama_panggilan) ?>";
        const USER_PHOTO = "<?= profilePhotoUrl($foto, '../') ?>";
        const ANS_LENGTH = <?= $has_question ? strlen($soal['jawaban']) : 0 ?>;
        let hintStock = <?= (int)$stats['hint_stock'] ?>;
        let hintsUsed = 0;
        const MILESTONES = {
            200: {
                label: 'NOVA',
                color: '#A020F0'
            },
            100: {
                label: 'PLASMA',
                color: '#0055FF'
            },
            50: {
                label: 'INFERNO',
                color: '#00A2FF'
            },
            30: {
                label: 'BLAZE',
                color: '#FF3300'
            },
            10: {
                label: 'EMBER',
                color: '#FF8800'
            },
            3: {
                label: 'SPARK',
                color: '#FFC300'
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
            return [3, 10, 30, 50, 100, 200].includes(s);
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
                    colors: ['#FFC300', '#FF8800', '#FF3300', '#00A2FF', '#0055FF', '#A020F0']
                });
                confetti({
                    ...f,
                    particleCount: p,
                    origin: {
                        x: Math.random() * 0.2 + 0.7,
                        y: Math.random() - 0.2
                    },
                    colors: ['#FFC300', '#FF8800', '#FF3300', '#00A2FF', '#0055FF', '#A020F0']
                });
            }, 250);
        }

        function showMilestonePopup(streak, bonus = 0, bonusHint = 0) {
            const m = getMilestone(streak);
            const isM = isNewMilestone(streak);
            if (isM) triggerConfetti();
            Swal.fire({
                html: `<style>@keyframes ff{0%,100%{transform:scale(1)translateY(0)}25%{transform:scale(1.1)translateY(-6px)}50%{transform:scale(1.15)translateY(-10px)}75%{transform:scale(1.1)translateY(-6px)}}@keyframes gw{0%,100%{filter:drop-shadow(0 0 15px ${m?.color||'#ff9500'})}50%{filter:drop-shadow(0 0 30px ${m?.color||'#ff9500'})}}.fc{animation:ff 1s ease-in-out infinite,gw 1s ease-in-out infinite}@keyframes si{0%{transform:scale(0);opacity:0}50%{transform:scale(1.1)}100%{transform:scale(1);opacity:1}}@keyframes bi{0%{transform:scale(0)}50%{transform:scale(1.1)}100%{transform:scale(1)}}.pb{animation:bi .5s ease-out}.mb{animation:si .5s ease-out forwards}</style><div class="text-center py-3"><div class="pb mb-2"><img src="${USER_PHOTO}" class="w-12 h-12 rounded-full mx-auto border-3 shadow-lg" style="border-color:${m?.color||'#ff9500'}" onerror="this.src='../assets/image/logo.webp'"></div><div class="text-sm font-bold text-white mb-2">${USER_NAME}</div><div class="fc mb-3" style="color:${m?.color||'#ff9500'};font-size:3rem"><i class="fa fa-fire"></i></div>${isM?`<div class="mb inline-block bg-gradient-to-r from-amber-500 to-orange-600 text-white px-3 py-1 rounded-full text-xs font-bold mb-2 shadow-lg">MILESTONE UNLOCKED!</div><div class="text-lg font-black mb-1" style="color:${m?.color}">${m?.label}</div>`:''}<div class="text-2xl font-black text-white mb-1">Streak ${streak}!</div><div class="flex flex-col gap-1 items-center mt-2">${bonus > 0 ? `<div class="text-indigo-400 text-[10px] font-bold"><i class="fa fa-shield mr-1"></i> +${bonus} Recovery Stock</div>` : ''}${bonusHint > 0 ? `<div class="text-amber-400 text-[10px] font-bold"><i class="fa fa-lightbulb mr-1"></i> +${bonusHint} Hint Stock</div>` : ''}</div><div class="text-slate-400 text-xs mt-2">Luar biasa!</div></div>`,
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
            fetch('kuis_harian', {
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
                    fetch('kuis_harian', {
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
                    fetch('kuis_harian', {
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
                            <h4 class="text-orange-400 font-bold text-sm mb-1"><i class="fa fa-shield mr-2"></i>Sistem Recovery & Hint</h4>
                            <p class="text-[11px] leading-relaxed">Gunakan <b>Recovery Stock</b> untuk memulihkan streak. Gunakan <b>Hint Stock</b> (tombol lampu) untuk membuka satu huruf jawaban sebagai bantuan.</p>
                        </div>

                        <div>
                            <h4 class="text-white font-bold text-sm mb-2"><i class="fa fa-fire mr-2"></i>Milestone & Rewards</h4>
                            <div class="grid grid-cols-2 gap-2">
                                <div class="bg-slate-800/50 p-3 rounded border border-slate-700 flex items-center gap-3">
                                    <div class="text-[#FFC300] text-2xl shrink-0"><i class="fa fa-fire"></i></div>
                                    <div class="flex-grow">
                                        <div class="text-[#FFC300] font-bold uppercase text-[10px]">SPARK (3)</div>
                                        <div class="text-indigo-400 text-[9px]">+1 Recovery Stock</div>
                                        <div class="text-amber-400 text-[9px]">+3 Hint Stock</div>
                                    </div>
                                </div>
                                <div class="bg-slate-800/50 p-3 rounded border border-slate-700 flex items-center gap-3">
                                    <div class="text-[#FF8800] text-2xl shrink-0"><i class="fa fa-fire"></i></div>
                                    <div class="flex-grow">
                                        <div class="text-[#FF8800] font-bold uppercase text-[10px]">EMBER (10)</div>
                                        <div class="text-indigo-400 text-[9px]">+5 Recovery Stock</div>
                                        <div class="text-amber-400 text-[9px]">+10 Hint Stock</div>
                                    </div>
                                </div>
                                <div class="bg-slate-800/50 p-3 rounded border border-slate-700 flex items-center gap-3">
                                    <div class="text-[#FF3300] text-2xl shrink-0"><i class="fa fa-fire"></i></div>
                                    <div class="flex-grow">
                                        <div class="text-[#FF3300] font-bold uppercase text-[10px]">BLAZE (30)</div>
                                        <div class="text-indigo-400 text-[9px]">+10 Recovery Stock</div>
                                        <div class="text-amber-400 text-[9px]">+15 Hint Stock</div>
                                    </div>
                                </div>
                                <div class="bg-slate-800/50 p-3 rounded border border-slate-700 flex items-center gap-3">
                                    <div class="text-[#00A2FF] text-2xl shrink-0"><i class="fa fa-fire"></i></div>
                                    <div class="flex-grow">
                                        <div class="text-[#00A2FF] font-bold uppercase text-[10px]">INFERNO (50)</div>
                                        <div class="text-indigo-400 text-[9px]">+15 Recovery Stock</div>
                                        <div class="text-amber-400 text-[9px]">+20 Hint Stock</div>
                                    </div>
                                </div>
                                <div class="bg-slate-800/50 p-3 rounded border border-slate-700 flex items-center gap-3">
                                    <div class="text-[#0055FF] text-2xl shrink-0"><i class="fa fa-fire"></i></div>
                                    <div class="flex-grow">
                                        <div class="text-[#0055FF] font-bold uppercase text-[10px]">PLASMA (100)</div>
                                        <div class="text-indigo-400 text-[9px]">+20 Recovery Stock</div>
                                        <div class="text-amber-400 text-[9px]">+25 Hint Stock</div>
                                    </div>
                                </div>
                                <div class="bg-slate-800/50 p-3 rounded border border-slate-700 flex items-center gap-3">
                                    <div class="text-[#A020F0] text-2xl shrink-0"><i class="fa fa-fire"></i></div>
                                    <div class="flex-grow">
                                        <div class="text-[#A020F0] font-bold uppercase text-[10px]">NOVA (200)</div>
                                        <div class="text-indigo-400 text-[9px]">+30 Recovery Stock</div>
                                        <div class="text-amber-400 text-[9px]">+40 Hint Stock</div>
                                    </div>
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