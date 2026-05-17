<?php
session_start();
include 'koneksi.php';

// Logika Akses Publik via Token
$is_public = false;
$user_id = 0;
$mhs_id = 0;
$nim_user = '';
$nama_lengkap = '';
$nama_panggilan = '';
$foto_user = '';

if (isset($_GET['token'])) {
    $token = mysqli_real_escape_string($koneksi, $_GET['token']);
    $q_token = mysqli_query($koneksi, "SELECT users.id as user_id, mahasiswa.id as mhs_id, mahasiswa.nim, mahasiswa.nama_lengkap, mahasiswa.nama_panggilan, mahasiswa.foto
FROM users
JOIN mahasiswa ON users.id = mahasiswa.user_id
WHERE md5(CONCAT(users.id, 'wrapped_salt_2024')) = '$token' LIMIT 1");
    if ($q_token && mysqli_num_rows($q_token) > 0) {
        $public_data = mysqli_fetch_assoc($q_token);
        $user_id = $public_data['user_id'];
        $mhs_id = $public_data['mhs_id'];
        $nim_user = $public_data['nim'];
        $nama_lengkap = $public_data['nama_lengkap'];
        $nama_panggilan = $public_data['nama_panggilan'];
        $foto_user = $public_data['foto'];
        $is_public = true;
    }
}

$login_status = '';
$login_message = '';

if (!$is_public && !isset($_SESSION['user_id'])) {
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login_wrapped'])) {
        $email = trim($_POST['email']);
        $password = trim($_POST['password']);

        if (empty($email) || empty($password)) {
            $login_status = 'error';
            $login_message = 'Email dan Password tidak boleh kosong!';
        } else {
            $query = "SELECT u.id AS user_id, m.nim, m.nama_lengkap, m.nama_panggilan, u.email, m.foto, u.password, u.role FROM users u JOIN mahasiswa m ON m.user_id = u.id WHERE email = ? AND password = ?";
            $stmt = mysqli_prepare($koneksi, $query);
            mysqli_stmt_bind_param($stmt, "ss", $email, $password);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);

            if ($data = mysqli_fetch_assoc($result)) {
                $_SESSION['user_id']       = $data['user_id'];
                $_SESSION['email']         = $data['email'];
                $_SESSION['nama_lengkap']  = $data['nama_lengkap'];
                $_SESSION['nama_panggilan']= $data['nama_panggilan'];
                $_SESSION['role']          = $data['role'];
                $_SESSION['foto']          = $data['foto'];
                header("Location: wrapped.php");
                exit;
            } else {
                $login_status = 'error';
                $login_message = 'Email atau Password salah!';
            }
        }
    }

    ?>
    <!DOCTYPE html>
    <html lang="id">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
        <title>Login - PTIK C Wrapped</title>
        <script src="https://cdn.tailwindcss.com"></script>
        <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
        <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;800&display=swap" rel="stylesheet">
        <style>
            body { font-family: 'Plus Jakarta Sans', sans-serif; background-color: #020617; color: #f8fafc; overflow: hidden; }
            .vibrant-dark { background: linear-gradient(180deg, #020617 0%, #0f172a 100%); }
            .glass { background: rgba(255, 255, 255, 0.05); backdrop-filter: blur(10px); border: 1px solid rgba(255, 255, 255, 0.1); }
        </style>
    </head>
    <body class="vibrant-dark min-h-screen flex flex-col items-center justify-center p-4">
        
        <div class="absolute inset-0 z-0 opacity-20 pointer-events-none">
            <div class="absolute top-[-10%] left-[-10%] w-[40%] h-[40%] bg-indigo-500 rounded-full blur-[120px]"></div>
            <div class="absolute bottom-[-10%] right-[-10%] w-[40%] h-[40%] bg-purple-500 rounded-full blur-[120px]"></div>
        </div>

        <div class="w-full max-w-sm glass p-8 rounded-[2rem] shadow-2xl relative z-10">
            <div class="text-center mb-8">
                <h1 class="text-3xl font-black uppercase tracking-tight text-white mb-2">Wrapped <span class="text-indigo-400">2024</span></h1>
                <p class="text-[10px] font-bold opacity-60 uppercase tracking-widest text-white">Login untuk melihat kilas balikmu</p>
            </div>
            
            <?php if ($login_status === 'error'): ?>
            <div class="bg-red-500/10 border border-red-500/30 text-red-400 p-3 rounded-xl text-xs font-bold mb-6 text-center">
                <?= $login_message ?>
            </div>
            <?php endif; ?>

            <form method="POST" action="">
                <input type="hidden" name="login_wrapped" value="1">
                <div class="space-y-4 mb-8">
                    <div class="relative">
                        <span class="absolute inset-y-0 left-0 pl-4 flex items-center text-white/40"><i class="fas fa-envelope"></i></span>
                        <input type="email" name="email" required placeholder="Email Address" class="w-full bg-black/20 border border-white/10 rounded-xl py-3.5 pl-11 pr-4 text-white text-sm focus:outline-none focus:border-indigo-500 transition-colors placeholder:text-white/30">
                    </div>
                    <div class="relative">
                        <span class="absolute inset-y-0 left-0 pl-4 flex items-center text-white/40"><i class="fas fa-lock"></i></span>
                        <input type="password" name="password" required placeholder="Password" class="w-full bg-black/20 border border-white/10 rounded-xl py-3.5 pl-11 pr-4 text-white text-sm focus:outline-none focus:border-indigo-500 transition-colors placeholder:text-white/30">
                    </div>
                </div>
                <button type="submit" class="w-full bg-indigo-600 hover:bg-indigo-500 text-white rounded-xl py-3.5 font-bold text-sm uppercase tracking-widest transition-colors shadow-lg shadow-indigo-500/30">
                    Masuk Sekarang
                </button>
            </form>
            
            <div class="text-center mt-6 pt-6 border-t border-white/10">
                <a href="index.php" class="text-[10px] font-bold uppercase opacity-40 hover:opacity-100 transition-opacity tracking-widest text-white">
                    <i class="fas fa-arrow-left mr-1"></i> Kembali ke Beranda
                </a>
            </div>
        </div>
    </body>
    </html>
    <?php
    exit;
}

if (!$is_public) {
    $user_id = $_SESSION['user_id'];
    $nama_lengkap = $_SESSION['nama_lengkap'] ?? 'Guest';
    $nama_panggilan = $_SESSION['nama_panggilan'] ?? 'Guest';
    $foto_user = $_SESSION['foto'] ?? 'default.jpg';

    // Ambil data mahasiswa
    $q_mhs = mysqli_query($koneksi, "SELECT * FROM mahasiswa WHERE user_id = '$user_id'");
    if ($q_mhs && mysqli_num_rows($q_mhs) > 0) {
        $mhs = mysqli_fetch_assoc($q_mhs);
        $mhs_id = $mhs['id'] ?? 0;
        $nim_user = $mhs['nim'] ?? '';
    }
}

// Generate token untuk dishare nanti
$share_token = md5($user_id . 'wrapped_salt_2024');
$protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http";
$share_url = $protocol . "://$_SERVER[HTTP_HOST]$_SERVER[PHP_SELF]?token=$share_token";

// Ambil periode aktif
$q_periode = mysqli_query($koneksi, "SELECT * FROM periode WHERE status = 'aktif' LIMIT 1");
if (!$q_periode || mysqli_num_rows($q_periode) == 0) {
    die("Error: Tidak ada periode aktif.");
}
$periode = mysqli_fetch_assoc($q_periode);
$periode_id = $periode['id'];
$tahun_periode = $periode['tahun_periode'];

// --- 1. KEHADIRAN ---
$q_total_pertemuan = mysqli_query($koneksi, "SELECT COUNT(*) as total FROM absensi WHERE periode_id = '$periode_id'");
$total_pertemuan_global = $q_total_pertemuan ? (mysqli_fetch_assoc($q_total_pertemuan)['total'] ?? 0) : 0;

$q_hadir = mysqli_query($koneksi, "SELECT COUNT(*) as total FROM absensi WHERE mahasiswa_id = '$mhs_id' AND status = 'Hadir' AND periode_id = '$periode_id'");
$total_hadir = $q_hadir ? (mysqli_fetch_assoc($q_hadir)['total'] ?? 0) : 0;

// Estimasi total pertemuan yang diikuti
$q_absensi_total = mysqli_query($koneksi, "SELECT COUNT(*) as total FROM absensi WHERE mahasiswa_id = '$mhs_id' AND periode_id = '$periode_id'");
$expected_pertemuan = $q_absensi_total ? (mysqli_fetch_assoc($q_absensi_total)['total'] ?? 0) : 0;
$attendance_rate = ($expected_pertemuan > 0) ? round(($total_hadir / $expected_pertemuan) * 100) : 0;

// --- 2. KAS ---
$q_kas_lunas = mysqli_query($koneksi, "SELECT COUNT(*) as total, SUM(nominal) as total_uang FROM kas WHERE mahasiswa_id = '$mhs_id' AND status = 'Lunas' AND periode_id = '$periode_id'");
$kas_data = $q_kas_lunas ? mysqli_fetch_assoc($q_kas_lunas) : null;
$total_minggu_kas = $kas_data['total'] ?? 0;
$total_uang_kas = $kas_data['total_uang'] ?? 0;
$kas_rate = round(($total_minggu_kas / 16) * 100);

// --- 3. TUGAS ---
$q_tugas_global = mysqli_query($koneksi, "SELECT COUNT(*) as total FROM tugas_global_done WHERE user_id = '$user_id' AND periode_id = '$periode_id'");
$total_global_done = $q_tugas_global ? (mysqli_fetch_assoc($q_tugas_global)['total'] ?? 0) : 0;

$q_tugas_personal = mysqli_query($koneksi, "SELECT COUNT(*) as total FROM tugas_personal WHERE user_id = '$user_id' AND status = 'selesai' AND periode_id = '$periode_id'");
$total_personal_done = $q_tugas_personal ? (mysqli_fetch_assoc($q_tugas_personal)['total'] ?? 0) : 0;

$total_tugas_selesai = $total_global_done + $total_personal_done;

// --- 4. KUIS HARIAN ---
$q_kuis = mysqli_query($koneksi, "SELECT * FROM kuis_harian_stats WHERE user_id = '$user_id'");
$kuis = $q_kuis ? mysqli_fetch_assoc($q_kuis) : null;
$kuis_streak = $kuis['max_streak'] ?? 0;
$kuis_total = $kuis['total_games'] ?? 0;

// --- 4.5 VIBE CHECK ---
$q_vibe_most = mysqli_query($koneksi, "SELECT mood, COUNT(*) as cnt FROM vibe_check WHERE user_id = '$user_id' GROUP BY mood ORDER BY cnt DESC LIMIT 1");
$vibe_most_data = $q_vibe_most ? mysqli_fetch_assoc($q_vibe_most) : null;
$my_top_vibe = $vibe_most_data['mood'] ?? 'productive';

$q_vibe_total = mysqli_query($koneksi, "SELECT COUNT(*) as total FROM vibe_check WHERE user_id = '$user_id'");
$my_vibe_count = $q_vibe_total ? (mysqli_fetch_assoc($q_vibe_total)['total'] ?? 0) : 0;

function getVibeDetails(string $mood)
{
    $details = [
        'productive' => ['icon' => 'fa-rocket', 'label' => 'Focus', 'desc' => 'Kamu sangat produktif semester ini!', 'color' => 'text-indigo-400'],
        'stress' => ['icon' => 'fa-head-side-virus', 'label' => 'Error', 'desc' => 'Banyak tantangan yang kamu lalui.', 'color' => 'text-red-400'],
        'relaxed' => ['icon' => 'fa-coffee', 'label' => 'Chill', 'desc' => 'Kamu tahu cara menikmati prosesnya.', 'color' => 'text-emerald-400'],
        'tired' => ['icon' => 'fa-battery-quarter', 'label' => 'Low', 'desc' => 'Kadang istirahat itu perlu, lho.', 'color' => 'text-slate-400'],
        'hyper' => ['icon' => 'fa-fire', 'label' => 'Fire', 'desc' => 'Semangatmu membara sepanjang waktu!', 'color' => 'text-orange-400']
    ];
    return $details[$mood] ?? $details['productive'];
}
$v_details = getVibeDetails($my_top_vibe);

// --- 5. BEST FRIEND ---
$partners = [];

// Dari Kelompok
$q_groups = mysqli_query($koneksi, "SELECT anggota FROM kelompok WHERE (FIND_IN_SET('$nim_user', REPLACE(anggota, ' ', '')) OR anggota LIKE '%$nim_user%') AND periode_id = '$periode_id'");
if ($q_groups) {
    while ($row = mysqli_fetch_assoc($q_groups)) {
        $members = explode(',', $row['anggota']);
        foreach ($members as $m) {
            $m = trim($m);
            if ($m !== $nim_user && !empty($m)) {
                $partners[$m] = ($partners[$m] ?? 0) + 1;
            }
        }
    }
}

// Dari Piket
$q_piket_partners = mysqli_query($koneksi, "
SELECT p2.mahasiswa_id, COUNT(*) as freq
FROM piket_proyektor p1
JOIN piket_proyektor p2 ON p1.jadwal_matkul_id = p2.jadwal_matkul_id AND p1.periode_id = p2.periode_id AND p1.minggu_ke = p2.minggu_ke
WHERE p1.mahasiswa_id = '$mhs_id' AND p2.mahasiswa_id != '$mhs_id' AND p1.periode_id = '$periode_id'
GROUP BY p2.mahasiswa_id
");
if ($q_piket_partners) {
    while ($row = mysqli_fetch_assoc($q_piket_partners)) {
        $pid = $row['mahasiswa_id'];
        $q_nim_p = mysqli_query($koneksi, "SELECT nim FROM mahasiswa WHERE id = '$pid'");
        if ($q_nim_p && mysqli_num_rows($q_nim_p) > 0) {
            $p_nim_data = mysqli_fetch_assoc($q_nim_p);
            $p_nim = $p_nim_data['nim'];
            $partners[$p_nim] = ($partners[$p_nim] ?? 0) + ($row['freq'] * 2); 
        }
    }
}

arsort($partners);
$top_3_partners = array_slice($partners, 0, 3, true);
$friends_data = [];
foreach ($top_3_partners as $nim => $score) {
    if ($nim) {
        $q_f = mysqli_query($koneksi, "SELECT * FROM mahasiswa WHERE nim = '$nim'");
        if ($q_f && mysqli_num_rows($q_f) > 0) {
            $f_data = mysqli_fetch_assoc($q_f);
            $f_data['score'] = $score;
            $friends_data[] = $f_data;
        }
    }
}
$best_friend_data = $friends_data[0] ?? null;
$best_friend_nim = $best_friend_data['nim'] ?? null;

// Stats for comparison
$res_avg_attendance = mysqli_query($koneksi, "SELECT COUNT(*) as total FROM absensi WHERE periode_id = '$periode_id'");
$total_absensi_class = $res_avg_attendance ? (mysqli_fetch_assoc($res_avg_attendance)['total'] ?? 1) : 1;
$q_mhs_count = mysqli_query($koneksi, "SELECT COUNT(*) as total FROM mahasiswa");
$num_mhs = $q_mhs_count ? (mysqli_fetch_assoc($q_mhs_count)['total'] ?? 1) : 1;
$avg_attendance = round($total_absensi_class / (max($num_mhs, 1) * 14)); 

include_once 'functions/foto_helpers.php';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>PTIK C Wrapped 2024 - <?= htmlspecialchars($nama_panggilan) ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;800&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background-color: #020617;
            color: #f8fafc;
            overflow: hidden;
            touch-action: none;
        }
        .slide {
            position: absolute;
            inset: 0;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 1.5rem;
            text-align: center;
            opacity: 0;
            transform: scale(0.95);
            pointer-events: none;
            transition: all 0.5s cubic-bezier(0.4, 0, 0.2, 1);
            will-change: opacity, transform;
            z-index: 10;
        }
        .slide-active {
            opacity: 1;
            transform: scale(1);
            pointer-events: auto;
            z-index: 20;
        }
        .glass {
            background: rgba(255, 255, 255, 0.05);
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 1.5rem;
        }
        .vibrant-dark { background: linear-gradient(180deg, #020617 0%, #0f172a 100%); }
        .vibrant-blue { background: linear-gradient(135deg, #1e1b4b, #1e3a8a); }
        .vibrant-emerald { background: linear-gradient(135deg, #064e3b, #047857); }
        .vibrant-purple { background: linear-gradient(135deg, #1e1b4b, #4c1d95); }
        .vibrant-amber { background: linear-gradient(135deg, #78350f, #b45309); }
        .vibrant-indigo { background: linear-gradient(135deg, #312e81, #3730a3); }
        .vibrant-rose { background: linear-gradient(135deg, #881337, #be123c); }
        
        .progress-container {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            display: flex;
            gap: 4px;
            padding: 12px;
            z-index: 50;
        }
        .progress-bar {
            height: 4px;
            flex: 1;
            background: rgba(255, 255, 255, 0.2);
            border-radius: 2px;
            overflow: hidden;
        }
        .progress-inner {
            height: 100%;
            background: #fff;
            width: 0%;
            transform-origin: left;
        }
        .tap-left, .tap-right {
            position: absolute;
            top: 50px;
            bottom: 80px;
            width: 50%;
            z-index: 40;
        }
        .tap-left { left: 0; }
        .tap-right { right: 0; }
    </style>
</head>
<body>

    <div class="progress-container" id="progress-container"></div>
    <div class="tap-left" onclick="prevSlide()"></div>
    <div class="tap-right" onclick="nextSlide()"></div>

    <!-- Slide 1: Intro -->
    <div class="slide slide-active vibrant-dark" id="slide-0">
        <div class="w-full max-w-sm">
            <div class="mb-6">
                <img src="<?= profilePhotoUrl($foto_user, '') ?>" class="w-24 h-24 md:w-32 md:h-32 rounded-full mx-auto border-4 border-indigo-500/30 shadow-lg object-cover">
            </div>
            <h1 class="text-3xl md:text-5xl font-black mb-2 uppercase tracking-tight text-white">Halo, <?= htmlspecialchars($nama_panggilan) ?>!</h1>
            <p class="text-sm md:text-lg font-medium opacity-70 mb-8 text-white">Kami telah merangkum perjalanan hebatmu selama satu semester ini.</p>
            <div class="glass px-4 py-2 inline-flex items-center gap-2 text-white">
                <span class="text-xs font-bold uppercase tracking-widest">Ketuk untuk mulai</span>
                <i class="fas fa-arrow-right"></i>
            </div>
        </div>
    </div>

    <!-- Slide 2: Kehadiran -->
    <div class="slide vibrant-blue" id="slide-1">
        <div class="w-full max-w-sm text-white">
            <span class="text-xs font-extrabold uppercase tracking-[0.3em] text-blue-300 mb-2 block">KEDISIPLINAN</span>
            <h2 class="text-4xl font-black mb-6 leading-tight">Kehadiran Kamu<br><span class="text-blue-400">Konsisten!</span></h2>
            <div class="glass p-6 text-left relative overflow-hidden bg-white/5 border-white/10">
                <div class="absolute -top-4 -right-4 p-4 opacity-10 text-7xl rotate-12">
                    <i class="fas fa-university"></i>
                </div>
                <div class="text-6xl font-black mb-1"><?= $total_hadir ?></div>
                <div class="text-lg font-bold opacity-60 uppercase tracking-tight">Pertemuan Tercatat</div>
                <div class="mt-8">
                    <div class="flex justify-between items-end mb-2">
                        <span class="text-xs font-bold uppercase tracking-widest opacity-60">Pencapaian</span>
                        <span class="text-2xl font-black"><?= $attendance_rate ?>%</span>
                    </div>
                    <div class="h-3 bg-white/10 rounded-full overflow-hidden">
                        <div class="h-full bg-blue-400 rounded-full" style="width: <?= $attendance_rate ?>%"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Slide 3: Kas -->
    <div class="slide vibrant-emerald" id="slide-2">
        <div class="w-full max-w-sm text-white">
            <span class="text-xs font-extrabold uppercase tracking-[0.3em] text-emerald-300 mb-2 block">KEUANGAN</span>
            <h2 class="text-4xl font-black mb-6 leading-tight">Urusan Kas?<br><span class="text-emerald-400">Aman Terkendali!</span></h2>
            <div class="glass p-6 text-left relative overflow-hidden bg-white/5">
                <div class="absolute -top-4 -right-4 p-4 opacity-10 text-7xl rotate-12">
                    <i class="fas fa-wallet"></i>
                </div>
                <div class="text-xs font-bold opacity-60 mb-1 uppercase tracking-widest">Total Kontribusi</div>
                <div class="text-3xl font-black mb-1">Rp <?= number_format($total_uang_kas, 0, ',', '.') ?></div>
                <p class="text-sm font-medium opacity-80 mb-6"><?= $total_minggu_kas ?> Minggu Terbayar Lunas</p>
                <div class="flex justify-between items-center border-t border-white/10 pt-4">
                    <div>
                        <div class="text-xs font-bold opacity-60 uppercase mb-1">Status Keuangan</div>
                        <div class="text-lg font-black uppercase"><?= $kas_rate >= 100 ? 'Sultan Kelas' : ($kas_rate >= 80 ? 'Warga Teladan' : 'Terkendali') ?></div>
                    </div>
                    <div class="text-4xl">
                        <i class="fas <?= $kas_rate >= 80 ? 'fa-crown text-amber-400' : 'fa-check-circle text-emerald-400' ?>"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Slide 4: Tugas -->
    <div class="slide vibrant-purple" id="slide-3">
        <div class="w-full max-w-sm text-white">
            <span class="text-xs font-extrabold uppercase tracking-[0.3em] text-purple-300 mb-2 block">PRODUKTIVITAS</span>
            <h2 class="text-4xl font-black mb-6 leading-tight">Pejuang<br><span class="text-purple-400">Ambisius!</span></h2>
            <div class="grid grid-cols-2 gap-3 mb-3">
                <div class="glass p-4 text-center bg-white/5">
                    <div class="text-3xl font-black mb-1"><?= $total_global_done ?></div>
                    <div class="text-xs font-bold opacity-60 uppercase tracking-widest">Tugas Kelas</div>
                </div>
                <div class="glass p-4 text-center bg-white/5">
                    <div class="text-3xl font-black mb-1"><?= $total_personal_done ?></div>
                    <div class="text-xs font-bold opacity-60 uppercase tracking-widest">Tugas Pribadi</div>
                </div>
            </div>
            <div class="glass p-6 relative overflow-hidden text-left bg-white/5">
                <div class="text-6xl font-black leading-none mb-1"><?= $total_tugas_selesai ?></div>
                <div class="text-lg font-bold opacity-80 uppercase tracking-tighter">Misi Terselesaikan</div>
                <div class="absolute -bottom-4 -right-4 opacity-10 text-8xl rotate-12">
                    <i class="fas fa-check-double"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Slide 5: Kuis Harian -->
    <div class="slide vibrant-amber" id="slide-4">
        <div class="w-full max-w-sm text-white">
            <span class="text-xs font-extrabold uppercase tracking-[0.3em] text-amber-300 mb-2 block">KUIS HARIAN</span>
            <h2 class="text-4xl font-black mb-6 leading-tight">Kecerdasan<br><span class="text-amber-400">Teruji!</span></h2>
            <div class="flex items-center justify-center gap-6 mb-6">
                <div class="text-7xl text-orange-400"><i class="fas fa-fire"></i></div>
                <div class="text-left">
                    <div class="text-6xl font-black leading-none"><?= $kuis_streak ?></div>
                    <div class="text-sm font-bold opacity-60 uppercase tracking-widest">Max Streak</div>
                </div>
            </div>
            <div class="glass p-6 flex justify-between items-center bg-white/5">
                <div class="text-left border-r border-white/10 pr-6">
                    <div class="text-xs font-bold opacity-60 uppercase mb-1">Total Game</div>
                    <div class="text-3xl font-black"><?= $kuis_total ?></div>
                </div>
                <div class="text-right pl-6">
                    <div class="text-xs font-bold opacity-60 uppercase mb-1">Status Kuis</div>
                    <div class="text-2xl font-black"><?= $kuis_streak >= 7 ? 'Sifu' : ($kuis_streak >= 3 ? 'Expert' : 'Rookie') ?></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Slide 6: Vibe Reveal -->
    <div class="slide vibrant-indigo" id="slide-5">
        <div class="w-full max-w-sm text-white">
            <span class="text-xs font-extrabold uppercase tracking-[0.3em] text-indigo-300 mb-2 block">MOOD TRACKER</span>
            <h2 class="text-4xl font-black mb-6 leading-tight">Vibe Kamu<br><span class="text-indigo-400">Dominan!</span></h2>
            <div class="glass p-6 text-left relative overflow-hidden bg-white/5">
                <div class="absolute -top-4 -right-4 p-4 opacity-10 text-8xl rotate-12">
                    <i class="fas <?= $v_details['icon'] ?>"></i>
                </div>
                <div class="text-5xl font-black mb-1"><?= $v_details['label'] ?></div>
                <div class="text-sm font-bold opacity-60 uppercase tracking-tight">Vibe Paling Sering</div>
                <div class="mt-4">
                    <p class="text-sm font-medium opacity-80 mb-6">"<?= $v_details['desc'] ?>"</p>
                    <div class="flex justify-between items-center border-t border-white/10 pt-4">
                        <div>
                            <div class="text-xs font-bold opacity-60 uppercase mb-1">Total Check-In</div>
                            <div class="text-xl font-black uppercase"><?= $my_vibe_count ?> Hari</div>
                        </div>
                        <div class="text-4xl">
                            <i class="fas <?= $v_details['icon'] ?> <?= $v_details['color'] ?>"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Slide 7: Top 3 Partners -->
    <div class="slide vibrant-rose" id="slide-6">
        <div class="w-full max-w-sm text-white">
            <span class="text-xs font-extrabold uppercase tracking-[0.3em] text-rose-300 mb-2 block">THE INNER CIRCLE</span>
            <h2 class="text-4xl font-black mb-6 leading-tight">Partner Kerja<br><span class="text-rose-400">Terdekat.</span></h2>
            <div class="space-y-3">
                <?php
                $display_friends = $friends_data;
                shuffle($display_friends);
                foreach ($display_friends as $f): ?>
                    <div class="glass p-4 flex items-center gap-4 text-left bg-white/5">
                        <img src="<?= profilePhotoUrl($f['foto'], '') ?>" class="w-14 h-14 rounded-xl border-2 border-white/10 object-cover">
                        <div class="flex-1">
                            <div class="text-xl font-black leading-tight"><?= htmlspecialchars($f['nama_panggilan']) ?></div>
                            <div class="text-[10px] font-bold opacity-60 uppercase tracking-widest">Kolaborasi Aktif</div>
                        </div>
                        <div class="text-rose-400 text-2xl">
                            <i class="fas fa-heart"></i>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <!-- Slide 8: Best Partner -->
    <div class="slide vibrant-rose" id="slide-7">
        <div class="w-full max-w-sm text-white">
            <span class="text-xs font-extrabold uppercase tracking-[0.3em] text-rose-300 mb-3 block">THE BEST PARTNER</span>
            <?php if ($best_friend_data): ?>
                <div class="mb-8">
                    <img src="<?= profilePhotoUrl($best_friend_data['foto'], '') ?>" class="w-48 h-48 rounded-full mx-auto border-8 border-white shadow-xl object-cover relative z-10">
                    <div class="-mt-6 bg-white text-rose-900 px-6 py-2 rounded-2xl font-black shadow-lg inline-block relative z-20 text-xl">
                        <?= htmlspecialchars($best_friend_data['nama_panggilan']) ?>
                    </div>
                </div>
                <h2 class="text-2xl font-black mb-4 px-2 leading-tight uppercase">Partner Paling Klop & Luar Biasa!</h2>
                <div class="glass p-3 inline-flex items-center gap-3 bg-white/10 uppercase tracking-widest text-xs font-bold">
                    <i class="fas fa-star text-amber-400"></i>
                    <span><?= $friends_data[0]['score'] ?? 0 ?>x Sinergi Kolaborasi</span>
                </div>
            <?php else: ?>
                <div class="glass p-12 text-center bg-white/5">
                    <i class="fas fa-user-friends text-6xl opacity-20 mb-4"></i>
                    <p class="text-xl font-bold opacity-80">Anda adalah sosok yang mandiri!</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Slide 9: Final Summary -->
    <div class="slide vibrant-dark" id="slide-8">
        <div class="w-full max-w-sm">
            <div class="bg-indigo-950/80 border border-white/10 p-6 rounded-[2rem] shadow-2xl relative z-50">
                <div class="flex items-center gap-4 mb-8">
                    <img src="<?= profilePhotoUrl($foto_user, '') ?>" class="w-16 h-16 rounded-xl border-2 border-indigo-500/30 object-cover shadow-lg">
                    <div class="text-left">
                        <h4 class="text-xl font-black uppercase tracking-tight leading-tight text-white"><?= htmlspecialchars($nama_lengkap) ?></h4>
                        <span class="text-xs font-bold tracking-widest uppercase text-white/50"><?= $nim_user ?></span>
                    </div>
                </div>
                <div class="grid grid-cols-2 gap-3 mb-8">
                    <div class="bg-white/5 p-3 rounded-xl border border-white/5 text-left">
                        <div class="text-[10px] font-bold uppercase opacity-50 mb-1 tracking-widest text-white">Absensi</div>
                        <div class="text-2xl font-black text-blue-400"><?= $attendance_rate ?>%</div>
                    </div>
                    <div class="bg-white/5 p-3 rounded-xl border border-white/5 text-left">
                        <div class="text-[10px] font-bold uppercase opacity-50 mb-1 tracking-widest text-white">Kas</div>
                        <div class="text-2xl font-black text-emerald-400"><?= $total_minggu_kas ?>/16</div>
                    </div>
                    <div class="bg-white/5 p-3 rounded-xl border border-white/5 text-left">
                        <div class="text-[10px] font-bold uppercase opacity-50 mb-1 tracking-widest text-white">Tugas</div>
                        <div class="text-2xl font-black text-purple-400"><?= $total_tugas_selesai ?></div>
                    </div>
                    <div class="bg-white/5 p-3 rounded-xl border border-white/5 text-left">
                        <div class="text-[10px] font-bold uppercase opacity-50 mb-1 tracking-widest text-white">Streak</div>
                        <div class="text-2xl font-black text-amber-400"><?= $kuis_streak ?></div>
                    </div>
                </div>
                <div class="text-center pt-4 border-t border-white/10">
                    <div class="text-xs font-bold uppercase tracking-[0.2em] mb-4 opacity-50 text-white">PTIK C 2024 WRAPPED</div>
                    <a href="dashboard.php" class="inline-block px-6 py-3 bg-white/10 rounded-full text-xs font-bold uppercase hover:bg-white/20 transition-colors tracking-widest text-white relative z-[100]">
                        Kembali ke Dashboard
                    </a>
                </div>
            </div>
        </div>
    </div>

    <script>
        const totalSlides = 9;
        let currentSlide = 0;
        let slideTimer;
        const duration = 6000; // 6 seconds per slide

        const progressContainer = document.getElementById('progress-container');
        for (let i = 0; i < totalSlides; i++) {
            const bar = document.createElement('div');
            bar.className = 'progress-bar';
            const inner = document.createElement('div');
            inner.className = 'progress-inner';
            inner.id = `bar-${i}`;
            bar.appendChild(inner);
            progressContainer.appendChild(bar);
        }

        function showSlide(index) {
            for (let i = 0; i < totalSlides; i++) {
                const slide = document.getElementById(`slide-${i}`);
                if (i === index) {
                    slide.classList.add('slide-active');
                } else {
                    slide.classList.remove('slide-active');
                }
            }
            resetTimer();
        }

        function nextSlide() {
            if (currentSlide < totalSlides - 1) {
                currentSlide++;
                showSlide(currentSlide);
            } else {
                clearTimeout(slideTimer);
                document.getElementById(`bar-${currentSlide}`).style.width = '100%';
            }
        }

        function prevSlide() {
            if (currentSlide > 0) {
                currentSlide--;
                showSlide(currentSlide);
            }
        }

        function resetTimer() {
            clearTimeout(slideTimer);
            
            for(let i = 0; i < totalSlides; i++) {
                const bar = document.getElementById(`bar-${i}`);
                bar.style.transition = 'none';
                if (i < currentSlide) {
                    bar.style.width = '100%';
                } else {
                    bar.style.width = '0%';
                }
            }
            
            const currentBar = document.getElementById(`bar-${currentSlide}`);
            // Force reflow
            void currentBar.offsetWidth;
            
            currentBar.style.transition = `width ${duration}ms linear`;
            currentBar.style.width = '100%';

            if (currentSlide < totalSlides - 1) {
                slideTimer = setTimeout(() => {
                    nextSlide();
                }, duration);
            }
        }

        document.addEventListener('keydown', (e) => {
            if (e.key === 'ArrowRight' || e.key === ' ') nextSlide();
            if (e.key === 'ArrowLeft') prevSlide();
        });

        window.onload = () => {
            showSlide(0);
        };
    </script>
</body>
</html>