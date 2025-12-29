<?php
session_start();
include '../../koneksi.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../../index.php");
    exit;
}

$user_id = $_SESSION['user_id'];
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

$streak_broken = false;
if ($stats['last_played_date'] !== null && $stats['last_played_date'] !== $today && $stats['last_played_date'] !== $yesterday) {
    $streak_broken = true;
    if ($stats['current_streak'] > 0) {
        $koneksi->query("UPDATE kuis_harian_stats SET current_streak = 0 WHERE user_id = $user_id");
        $stats['current_streak'] = 0;
    }
}

$current_week = date('W-Y');
$last_recovery_week = $stats['last_recovery_used_at'] ? date('W-Y', strtotime($stats['last_recovery_used_at'])) : null;
$can_recover = ($last_recovery_week !== $current_week) && $streak_broken && $stats['max_streak'] > 0;

$q_soal = $koneksi->prepare("SELECT * FROM kuis_harian_soal WHERE tanggal = ? LIMIT 1");
$q_soal->bind_param("s", $today);
$q_soal->execute();
$soal = $q_soal->get_result()->fetch_assoc();

$has_question = ($soal !== null);
$already_played = ($stats['last_played_date'] === $today && $stats['last_completed_at'] !== null);

// Fetch ALL leaderboard for expand
$q_leaderboard = $koneksi->query("SELECT m.nama_panggilan, m.foto, s.user_id, s.current_streak, s.max_streak, s.total_games 
    FROM kuis_harian_stats s 
    JOIN mahasiswa m ON s.user_id = m.user_id 
    ORDER BY s.current_streak DESC, s.total_games DESC 
    LIMIT 50");
$leaderboard_data = [];
while ($row = mysqli_fetch_assoc($q_leaderboard)) {
    $leaderboard_data[] = $row;
}

if (isset($_POST['action']) && $_POST['action'] === 'submit_answer') {
    header('Content-Type: application/json');
    if (!$has_question) { echo json_encode(['status' => 'error']); exit; }
    if ($already_played) { echo json_encode(['status' => 'already_played']); exit; }

    $user_answer = trim(strtoupper($_POST['answer'] ?? ''));
    $correct_answer = strtoupper($soal['jawaban']);

    if ($user_answer === $correct_answer) {
        $current_streak = $stats['current_streak'];
        $max_streak = $stats['max_streak'];
        $total_games = $stats['total_games'] + 1;
        
        if ($stats['last_played_date'] === $yesterday || $stats['last_played_date'] === null) {
            $current_streak++;
        } elseif ($stats['last_played_date'] !== $today) {
            $current_streak = 1;
        }
        if ($stats['last_played_date'] === $today && !$already_played) {
            $current_streak++;
        }

        if ($current_streak > $max_streak) $max_streak = $current_streak;
        $now = date('Y-m-d H:i:s');

        $upd = $koneksi->prepare("UPDATE kuis_harian_stats SET current_streak=?, max_streak=?, total_games=?, last_completed_at=?, last_played_date=? WHERE user_id=?");
        $upd->bind_param("iiissi", $current_streak, $max_streak, $total_games, $now, $today, $user_id);
        $upd->execute();

        echo json_encode(['status' => 'success', 'new_streak' => $current_streak]);
    } else {
        echo json_encode(['status' => 'wrong']);
    }
    exit;
}

if (isset($_POST['action']) && $_POST['action'] === 'use_recovery') {
    header('Content-Type: application/json');
    if ($can_recover) {
        $now = date('Y-m-d H:i:s');
        $koneksi->query("UPDATE kuis_harian_stats SET current_streak = 1, last_recovery_used_at = '$now', last_played_date = '$yesterday' WHERE user_id = $user_id");
        echo json_encode(['status' => 'success']);
    } else {
        echo json_encode(['status' => 'failed']);
    }
    exit;
}

function getFireIcon($streak, $isActive = true) {
    $milestones = [
        1000 => ['color' => '#ff0000', 'glow' => '#ff6b6b', 'label' => 'LEGENDARY'],
        500 => ['color' => '#ff00ff', 'glow' => '#ff66ff', 'label' => 'EPIC'],
        200 => ['color' => '#00ffff', 'glow' => '#66ffff', 'label' => 'DIAMOND'],
        100 => ['color' => '#ffd700', 'glow' => '#ffeb3b', 'label' => 'GOLD'],
        50 => ['color' => '#ff6600', 'glow' => '#ff9933', 'label' => 'PLATINUM'],
        30 => ['color' => '#9966ff', 'glow' => '#b399ff', 'label' => 'PURPLE'],
        10 => ['color' => '#3399ff', 'glow' => '#66b3ff', 'label' => 'BLUE'],
        3 => ['color' => '#ff9500', 'glow' => '#ffb347', 'label' => 'FIRE']
    ];
    foreach ($milestones as $threshold => $data) {
        if ($streak >= $threshold) return ['show' => true, 'color' => $isActive ? $data['color'] : '#6b7280', 'glow' => $isActive ? $data['glow'] : '#9ca3af', 'label' => $data['label'], 'active' => $isActive];
    }
    return ['show' => false];
}

$fireInfo = getFireIcon($stats['current_streak'], $already_played);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kuis Harian - PTIK C</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://cdn.jsdelivr.net/npm/canvas-confetti@1.9.2/dist/confetti.browser.min.js"></script>
    <style>
        body { font-family: 'Space Grotesk', sans-serif; background-color: #0f172a; color: #f8fafc; font-size: 14px; }
        @keyframes fireGlow { 0%, 100% { filter: drop-shadow(0 0 6px var(--glow-color)); transform: scale(1); } 50% { filter: drop-shadow(0 0 14px var(--glow-color)); transform: scale(1.08); } }
        .fire-active { animation: fireGlow 1.5s ease-in-out infinite; }
        .fire-inactive { opacity: 0.5; }
        .no-scrollbar::-webkit-scrollbar { display: none; }
        .no-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }
    </style>
</head>
<body class="min-h-screen flex flex-col relative overflow-x-hidden">
    
    <div class="fixed inset-0 -z-10 overflow-hidden">
        <div class="absolute top-10 left-10 w-24 h-24 bg-indigo-600/20 rounded-full blur-3xl"></div>
        <div class="absolute bottom-20 right-20 w-32 h-32 bg-purple-600/20 rounded-full blur-3xl"></div>
    </div>

    <div class="w-full p-3 flex justify-between items-center z-50 border-b border-slate-800">
        <div class="flex items-center gap-2">
            <div class="w-8 h-8 bg-indigo-600 rounded-lg flex items-center justify-center shadow-lg shadow-indigo-500/30">
                <i class="fa fa-quote-right text-white text-sm"></i>
            </div>
            <h1 class="text-base font-bold tracking-tight">KuisHarian</h1>
        </div>
        <a href="dashboard.php" class="px-3 py-1.5 bg-slate-800/50 hover:bg-slate-700/50 rounded-lg text-xs font-medium border border-slate-700">
            <i class="fa fa-arrow-left mr-1"></i> Dashboard
        </a>
    </div>

    <main class="flex-grow w-full max-w-5xl mx-auto px-3 py-4 grid grid-cols-1 lg:grid-cols-3 gap-4">
        
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
                    <span class="text-xs font-bold text-slate-200">Streak: <span class="text-indigo-400"><?= $stats['current_streak'] ?></span></span>
                </div>
            </div>

            <?php if ($can_recover): ?>
            <div class="w-full max-w-sm bg-orange-900/30 border border-orange-500/30 rounded-xl p-3 mb-4 text-center">
                <div class="flex items-center justify-center gap-2 mb-1">
                    <i class="fa fa-shield-halved text-orange-500 text-sm"></i>
                    <span class="font-bold text-orange-400 text-sm">Streak Terputus!</span>
                </div>
                <button onclick="useRecovery()" class="bg-orange-600 hover:bg-orange-500 text-white px-4 py-1.5 rounded-lg font-bold text-xs transition">
                    <i class="fa fa-heart-pulse mr-1"></i> Pulihkan (1x/minggu)
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

            <?php elseif ($has_question): ?>
                <div class="w-full max-w-sm bg-slate-800/60 border border-slate-700 p-5 rounded-2xl shadow-xl">
                    <div class="text-center mb-4">
                        <h3 class="text-[10px] font-bold text-indigo-400 uppercase tracking-widest mb-2">Pertanyaan</h3>
                        <p class="text-base font-bold text-white leading-snug">"<?= htmlspecialchars($soal['pertanyaan']) ?>"</p>
                    </div>
                    <form id="quizForm" class="space-y-3" onsubmit="submitAnswer(event)">
                        <input type="text" id="answer" class="w-full bg-slate-900/50 border-2 border-slate-600 text-white text-center text-base font-bold py-3 rounded-lg focus:border-indigo-500 transition placeholder-slate-600 uppercase tracking-wider" placeholder="JAWABAN..." autocomplete="off" required>
                        <button type="submit" class="w-full bg-gradient-to-r from-indigo-600 to-violet-600 hover:from-indigo-500 hover:to-violet-500 text-white font-bold py-3 rounded-lg shadow-lg transition active:scale-95 flex items-center justify-center gap-2 text-sm">
                            <span>Jawab</span><i class="fa fa-paper-plane"></i>
                        </button>
                    </form>
                </div>

            <?php else: ?>
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
                    $milestones = [3, 10, 30, 50, 100, 200, 500, 1000];
                    $colors = ['#ff9500', '#3399ff', '#9966ff', '#ff6600', '#ffd700', '#00ffff', '#ff00ff', '#ff0000'];
                    foreach ($milestones as $i => $m):
                        $achieved = $stats['current_streak'] >= $m;
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

    <script>
        const USER_NAME = "<?= htmlspecialchars($nama_panggilan) ?>";
        const USER_PHOTO = "../img/profile/<?= htmlspecialchars($foto) ?>";
        const MILESTONES = {1000:{label:'LEGENDARY',color:'#ff0000'},500:{label:'EPIC',color:'#ff00ff'},200:{label:'DIAMOND',color:'#00ffff'},100:{label:'GOLD',color:'#ffd700'},50:{label:'PLATINUM',color:'#ff6600'},30:{label:'PURPLE',color:'#9966ff'},10:{label:'BLUE',color:'#3399ff'},3:{label:'FIRE',color:'#ff9500'}};

        function getMilestone(s){for(const[t,d]of Object.entries(MILESTONES).sort((a,b)=>b[0]-a[0]))if(s>=parseInt(t))return{threshold:parseInt(t),...d};return null;}
        function isNewMilestone(s){return[3,10,30,50,100,200,500,1000].includes(s);}
        
        

        function triggerConfetti(){const d=3000,e=Date.now()+d,f={startVelocity:30,spread:360,ticks:60,zIndex:10000};const i=setInterval(()=>{const t=e-Date.now();if(t<=0)return clearInterval(i);const p=50*(t/d);confetti({...f,particleCount:p,origin:{x:Math.random()*0.2+0.1,y:Math.random()-0.2},colors:['#ff9500','#ffd700','#ff6600','#ff00ff','#00ffff']});confetti({...f,particleCount:p,origin:{x:Math.random()*0.2+0.7,y:Math.random()-0.2},colors:['#ff9500','#ffd700','#ff6600','#ff00ff','#00ffff']});},250);}

        function showMilestonePopup(streak){const m=getMilestone(streak);const isM=isNewMilestone(streak);if(isM)triggerConfetti();
            Swal.fire({html:`<style>@keyframes ff{0%,100%{transform:scale(1)translateY(0)}25%{transform:scale(1.1)translateY(-6px)}50%{transform:scale(1.15)translateY(-10px)}75%{transform:scale(1.1)translateY(-6px)}}@keyframes gw{0%,100%{filter:drop-shadow(0 0 15px ${m?.color||'#ff9500'})}50%{filter:drop-shadow(0 0 30px ${m?.color||'#ff9500'})}}.fc{animation:ff 1s ease-in-out infinite,gw 1s ease-in-out infinite}@keyframes si{0%{transform:scale(0);opacity:0}50%{transform:scale(1.1)}100%{transform:scale(1);opacity:1}}@keyframes bi{0%{transform:scale(0)}50%{transform:scale(1.1)}100%{transform:scale(1)}}.pb{animation:bi .5s ease-out}.mb{animation:si .5s ease-out forwards}</style><div class="text-center py-3"><div class="pb mb-2"><img src="${USER_PHOTO}" class="w-12 h-12 rounded-full mx-auto border-3 shadow-lg" style="border-color:${m?.color||'#ff9500'}" onerror="this.src='../../img/profile/default.png'"></div><div class="text-sm font-bold text-white mb-2">${USER_NAME}</div><div class="fc mb-3" style="color:${m?.color||'#ff9500'};font-size:3rem"><i class="fa fa-fire"></i></div>${isM?`<div class="mb inline-block bg-gradient-to-r from-amber-500 to-orange-600 text-white px-3 py-1 rounded-full text-xs font-bold mb-2 shadow-lg">MILESTONE UNLOCKED!</div><div class="text-lg font-black mb-1" style="color:${m?.color}">${m?.label}</div>`:''}<div class="text-2xl font-black text-white mb-1">Streak ${streak}!</div><div class="text-slate-400 text-xs">Luar biasa!</div></div>`,showConfirmButton:true,confirmButtonText:'OK',confirmButtonColor:'#6366f1',background:'#1e293b',color:'#fff',allowOutsideClick:false}).then(()=>location.reload());}

        function submitAnswer(e){e.preventDefault();const i=document.getElementById('answer'),b=e.target.querySelector('button'),o=b.innerHTML;b.disabled=true;b.innerHTML='<i class="fa fa-spinner fa-spin"></i>';const f=new FormData();f.append('action','submit_answer');f.append('answer',i.value);fetch('kuis_harian.php',{method:'POST',body:f}).then(r=>r.json()).then(d=>{b.disabled=false;b.innerHTML=o;if(d.status==='success')showMilestonePopup(d.new_streak);else if(d.status==='already_played')location.reload();else{Swal.fire({title:'Salah',text:'Coba lagi!',icon:'error',background:'#1e293b',color:'#fff',confirmButtonColor:'#ef4444'});i.value='';i.focus();}}).catch(()=>{b.disabled=false;b.innerHTML=o;Swal.fire('Error','Koneksi bermasalah','error');});}
        
        function useRecovery(){Swal.fire({title:'Pulihkan?',html:'<p class="text-xs">1x/minggu</p>',icon:'question',showCancelButton:true,confirmButtonText:'Ya',confirmButtonColor:'#ea580c',background:'#1e293b',color:'#fff'}).then(r=>{if(r.isConfirmed){const f=new FormData();f.append('action','use_recovery');fetch('kuis_harian.php',{method:'POST',body:f}).then(r=>r.json()).then(d=>{if(d.status==='success')Swal.fire({title:'Berhasil!',icon:'success',background:'#1e293b',color:'#fff'}).then(()=>location.reload());else Swal.fire('Gagal','','error');});}});}
    </script>
</body>
</html>
