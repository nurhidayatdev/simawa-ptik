<?php
session_start();
include 'koneksi.php';

// Logika Akses Publik via Token
$is_public = false;
if (isset($_GET['token'])) {
    $token = mysqli_real_escape_string($koneksi, $_GET['token']);
    $q_token = mysqli_query($koneksi, "SELECT users.id as user_id, mahasiswa.id as mhs_id, mahasiswa.nim, mahasiswa.nama_lengkap, mahasiswa.nama_panggilan, mahasiswa.foto
FROM users
JOIN mahasiswa ON users.id = mahasiswa.user_id
WHERE md5(CONCAT(users.id, 'wrapped_salt_2024')) = '$token' LIMIT 1");
    if (mysqli_num_rows($q_token) > 0) {
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

if (!$is_public) {
    if (!isset($_SESSION['user_id'])) {
        header("Location: index");
        exit;
    }
    $user_id = $_SESSION['user_id'];
    $nama_lengkap = $_SESSION['nama_lengkap'];
    $nama_panggilan = $_SESSION['nama_panggilan'];
    $foto_user = $_SESSION['foto'];

    // Ambil data mahasiswa
    $q_mhs = mysqli_query($koneksi, "SELECT * FROM mahasiswa WHERE user_id = '$user_id'");
    $mhs = mysqli_fetch_assoc($q_mhs);
    $mhs_id = $mhs['id'] ?? 0;
    $nim_user = $mhs['nim'] ?? '';
}

// Generate token untuk dishare nanti
$share_token = md5($user_id . 'wrapped_salt_2024');
$share_url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[PHP_SELF]?token=$share_token";

// Ambil periode aktif
$q_periode = mysqli_query($koneksi, "SELECT * FROM periode WHERE status = 'aktif' LIMIT 1");
$periode = mysqli_fetch_assoc($q_periode);
if (!$periode) {
    die("Error: Tidak ada periode aktif.");
}
$periode_id = $periode['id'];
$tahun_periode = $periode['tahun_periode'];

// --- 1. KEHADIRAN ---
$q_total_pertemuan = mysqli_query($koneksi, "SELECT COUNT(*) as total FROM absensi WHERE periode_id = $periode_id");
$total_pertemuan_global = mysqli_fetch_assoc($q_total_pertemuan)['total'] ?? 0;

$q_hadir = mysqli_query($koneksi, "SELECT COUNT(*) as total FROM absensi WHERE mahasiswa_id = $mhs_id AND status = 'Hadir' AND periode_id = $periode_id");
$total_hadir = mysqli_fetch_assoc($q_hadir)['total'] ?? 0;

// Estimasi total pertemuan yang diikuti (berdasarkan data di tabel absensi untuk user tersebut)
$q_absensi_total = mysqli_query($koneksi, "SELECT COUNT(*) as total FROM absensi WHERE mahasiswa_id = $mhs_id AND periode_id = $periode_id");
$expected_pertemuan = mysqli_fetch_assoc($q_absensi_total)['total'] ?? 0;
$attendance_rate = ($expected_pertemuan > 0) ? round(($total_hadir / $expected_pertemuan) * 100) : 0;

// --- 2. KAS ---
$q_kas_lunas = mysqli_query($koneksi, "SELECT COUNT(*) as total, SUM(nominal) as total_uang FROM kas WHERE mahasiswa_id = $mhs_id AND status = 'Lunas' AND periode_id = $periode_id");
$kas_data = mysqli_fetch_assoc($q_kas_lunas);
$total_minggu_kas = $kas_data['total'] ?? 0;
$total_uang_kas = $kas_data['total_uang'] ?? 0;
$kas_rate = round(($total_minggu_kas / 16) * 100);

// --- 3. TUGAS ---
$q_tugas_global = mysqli_query($koneksi, "SELECT COUNT(*) as total FROM tugas_global_done WHERE user_id = $user_id AND periode_id = $periode_id");
$total_global_done = mysqli_fetch_assoc($q_tugas_global)['total'] ?? 0;

$q_tugas_personal = mysqli_query($koneksi, "SELECT COUNT(*) as total FROM tugas_personal WHERE user_id = $user_id AND status = 'selesai' AND periode_id = $periode_id");
$total_personal_done = mysqli_fetch_assoc($q_tugas_personal)['total'] ?? 0;

$total_tugas_selesai = $total_global_done + $total_personal_done;

// --- 4. KUIS HARIAN ---
$q_kuis = mysqli_query($koneksi, "SELECT * FROM kuis_harian_stats WHERE user_id = $user_id");
$kuis = mysqli_fetch_assoc($q_kuis);
$kuis_streak = $kuis['max_streak'] ?? 0;
$kuis_total = $kuis['total_games'] ?? 0;

// --- 4.5 VIBE CHECK ---
$q_vibe_most = mysqli_query($koneksi, "SELECT mood, COUNT(*) as cnt FROM vibe_check WHERE user_id = $user_id GROUP BY mood ORDER BY cnt DESC LIMIT 1");
$vibe_most_data = mysqli_fetch_assoc($q_vibe_most);
$my_top_vibe = $vibe_most_data['mood'] ?? 'productive';

$q_vibe_total = mysqli_query($koneksi, "SELECT COUNT(*) as total FROM vibe_check WHERE user_id = $user_id");
$my_vibe_count = mysqli_fetch_assoc($q_vibe_total)['total'] ?? 0;

function getVibeDetails($mood)
{
    $details = [
        'productive' => ['icon' => 'fa-rocket', 'label' => 'Focus', 'desc' => 'Kamu sangat produktif semester ini!', 'color' => 'indigo-400'],
        'stress' => ['icon' => 'fa-head-side-virus', 'label' => 'Error', 'desc' => 'Banyak tantangan yang kamu lalui.', 'color' => 'red-400'],
        'relaxed' => ['icon' => 'fa-coffee', 'label' => 'Chill', 'desc' => 'Kamu tahu cara menikmati prosesnya.', 'color' => 'emerald-400'],
        'tired' => ['icon' => 'fa-battery-quarter', 'label' => 'Low', 'desc' => 'Kadang istirahat itu perlu, lho.', 'color' => 'slate-400'],
        'hyper' => ['icon' => 'fa-fire', 'label' => 'Fire', 'desc' => 'Semangatmu membara sepanjang waktu!', 'color' => 'orange-400']
    ];
    return $details[$mood] ?? $details['productive'];
}
$v_details = getVibeDetails($my_top_vibe);

// --- 5. BEST FRIEND ---
$partners = [];

// Dari Kelompok
$q_groups = mysqli_query($koneksi, "SELECT anggota FROM kelompok WHERE (FIND_IN_SET('$nim_user', REPLACE(anggota, ' ', '')) OR anggota LIKE '%$nim_user%') AND periode_id = $periode_id");
while ($row = mysqli_fetch_assoc($q_groups)) {
    $members = explode(',', $row['anggota']);
    foreach ($members as $m) {
        $m = trim($m);
        if ($m !== $nim_user && !empty($m)) {
            $partners[$m] = ($partners[$m] ?? 0) + 1;
        }
    }
}

// Dari Piket
$q_piket_partners = mysqli_query($koneksi, "
SELECT p2.mahasiswa_id, COUNT(*) as freq
FROM piket_proyektor p1
JOIN piket_proyektor p2 ON p1.jadwal_matkul_id = p2.jadwal_matkul_id AND p1.periode_id = p2.periode_id AND p1.minggu_ke = p2.minggu_ke
WHERE p1.mahasiswa_id = $mhs_id AND p2.mahasiswa_id != $mhs_id AND p1.periode_id = $periode_id
GROUP BY p2.mahasiswa_id
");
while ($row = mysqli_fetch_assoc($q_piket_partners)) {
    // We need NIM for consistency or we map everything to ID later.
    // Let's use ID as key for partners to avoid mixing NIM and ID.
    // Wait, the groups use NIM. Let's convert NIM to ID or vice versa.
    // Get NIM for this piket partner
    $pid = $row['mahasiswa_id'];
    $q_nim_p = mysqli_query($koneksi, "SELECT nim FROM mahasiswa WHERE id = $pid");
    $p_nim_data = mysqli_fetch_assoc($q_nim_p);
    if ($p_nim_data) {
        $p_nim = $p_nim_data['nim'];
        $partners[$p_nim] = ($partners[$p_nim] ?? 0) + ($row['freq'] * 2); // Piket usually feels closer
    }
}

arsort($partners);
$top_3_partners = array_slice($partners, 0, 3, true);
$friends_data = [];
foreach ($top_3_partners as $nim => $score) {
    if ($nim) {
        $q_f = mysqli_query($koneksi, "SELECT * FROM mahasiswa WHERE nim = '$nim'");
        $f_data = mysqli_fetch_assoc($q_f);
        if ($f_data) {
            $f_data['score'] = $score;
            $friends_data[] = $f_data;
        }
    }
}
$best_friend_data = $friends_data[0] ?? null;
$best_friend_nim = $best_friend_data['nim'] ?? null;

// Stats for comparison (Class averages or totals)
$res_avg_attendance = mysqli_query($koneksi, "SELECT COUNT(*) as total FROM absensi WHERE periode_id = $periode_id");
$total_absensi_class = mysqli_fetch_assoc($res_avg_attendance)['total'] ?? 1;
$q_mhs_count = mysqli_query($koneksi, "SELECT COUNT(*) as total FROM mahasiswa");
$num_mhs = mysqli_fetch_assoc($q_mhs_count)['total'] ?? 1;
$avg_attendance = round($total_absensi_class / ($num_mhs * 14)); // Roughly

include_once 'functions/foto_helpers.php';
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PTIK C Wrapped 2024 - <?= htmlspecialchars($nama_panggilan) ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background-color: #020617;
            color: #f8fafc;
            overflow: hidden;
        }

        .slide {
            height: 100vh;
            width: 100vw;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            position: absolute;
            top: 0;
            left: 0;
            transition: transform 0.8s cubic-bezier(0.85, 0, 0.15, 1);
            padding: 2rem;
            text-align: center;
        }

        .slide-hidden-right {
            transform: translateX(100%);
        }

        .slide-hidden-left {
            transform: translateX(-100%);
        }

        .slide-active {
            transform: translateX(0);
        }

        .glass {
            background: rgba(255, 255, 255, 0.03);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 2rem;
        }

        .vibrant-blue {
            background: linear-gradient(135deg, #1e1b4b, #312e81, #1e3a8a);
        }

        .vibrant-indigo {
            background: linear-gradient(135deg, #312e81, #4338ca, #3730a3);
        }

        .vibrant-purple {
            background: linear-gradient(135deg, #1e1b4b, #581c87, #4c1d95);
        }

        .vibrant-emerald {
            background: linear-gradient(135deg, #064e3b, #065f46, #047857);
        }

        .vibrant-amber {
            background: linear-gradient(135deg, #78350f, #92400e, #b45309);
        }

        .vibrant-rose {
            background: linear-gradient(135deg, #881337, #9f1239, #be123c);
        }

        .vibrant-dark {
            background: linear-gradient(180deg, #020617 0%, #0f172a 100%);
        }

        @keyframes float {

            0%,
            100% {
                transform: translateY(0);
            }

            50% {
                transform: translateY(-20px);
            }
        }

        .floating {
            animation: float 6s ease-in-out infinite;
        }

        @keyframes scaleIn {
            from {
                transform: scale(0.8);
                opacity: 0;
            }

            to {
                transform: scale(1);
                opacity: 1;
            }
        }

        .scale-in {
            animation: scaleIn 0.8s cubic-bezier(0.34, 1.56, 0.64, 1) forwards;
        }

        .progress-container {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            display: flex;
            gap: 4px;
            padding: 10px;
            z-index: 100;
        }

        .progress-bar {
            height: 3px;
            flex: 1;
            background: rgba(255, 255, 255, 0.2);
            border-radius: 2px;
            overflow: hidden;
        }

        .progress-inner {
            height: 100%;
            background: white;
            width: 0%;
            transition: width 0.1s linear;
        }

        .cursor-pointer {
            cursor: pointer;
        }

        .data-card {
            min-height: 250px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }

        @media (min-width: 768px) {
            .data-card {
                min-height: 280px;
            }
        }
    </style>
</head>

<body>

    <div class="progress-container" id="progress-container">
        <!-- Bars will be generated by JS -->
    </div>

    <!-- Slide 1: Intro -->
    <div class="slide slide-active vibrant-dark" id="slide-0">
        <div class="z-10 scale-in px-4">
            <div class="absolute top-8 left-8 flex items-center gap-3 opacity-60">
                <img src="assets/image/logo.webp" class="h-8 w-8">
                <span class="text-[9px] md:text-[10px] font-extrabold uppercase tracking-[0.3em]">PTIK C 2024</span>
            </div>
            <div class="relative inline-block mb-4">
                <div class="absolute -inset-4 bg-indigo-500/20 rounded-full blur-xl animate-pulse"></div>
                <img src="<?= profilePhotoUrl($foto_user, '') ?>" class="w-20 h-20 md:w-32 md:h-32 rounded-full mx-auto border-4 border-indigo-500/30 shadow-2xl relative z-10 object-cover">
            </div>
            <h1 class="text-2xl md:text-5xl font-black mb-2 uppercase tracking-tighter text-white">Halo, <?= htmlspecialchars($nama_panggilan) ?>!</h1>
            <p class="text-sm md:text-lg font-medium opacity-70 mb-6 max-w-sm mx-auto text-white">Kami telah merangkum perjalanan hebatmu selama satu semester ini.</p>
            <div class="glass p-3 inline-flex items-center gap-2 animate-bounce shadow-xl text-white">
                <span class="text-[9px] md:text-[11px] font-bold uppercase tracking-widest">Gulir untuk mulai</span>
                <i class="fas fa-arrow-down text-sm"></i>
            </div>
        </div>
        <div class="absolute inset-0 opacity-10">
            <div class="absolute top-1/4 left-1/4 w-64 h-64 bg-indigo-500 rounded-full blur-[100px] floating"></div>
            <div class="absolute bottom-1/4 right-1/4 w-96 h-96 bg-blue-600 rounded-full blur-[120px] floating" style="animation-delay: -2s"></div>
        </div>
    </div>

    <!-- Slide 2: Kehadiran -->
    <div class="slide slide-hidden-right vibrant-blue" id="slide-1">
        <div class="z-10 w-full max-w-xs md:max-w-md px-4 text-white">
            <span class="text-[9px] md:text-[10px] font-extrabold uppercase tracking-[0.3em] text-blue-300 mb-2 block">AKADEMIK & KEDISIPLINAN</span>
            <h2 class="text-3xl md:text-5xl font-black mb-4 leading-tight text-white">Kehadiran Kamu<br><span class="text-blue-400">Konsisten!</span></h2>
            <div class="glass p-4 md:p-6 mb-4 text-left relative overflow-hidden bg-white/5 border-white/10 data-card text-white">
                <div class="absolute -top-4 -right-4 p-4 opacity-10 text-7xl md:text-8xl rotate-12 text-white">
                    <i class="fas fa-university"></i>
                </div>
                <div class="text-4xl md:text-6xl font-black mb-1 text-white"><?= $total_hadir ?></div>
                <div class="text-sm md:text-xl font-bold opacity-60 uppercase tracking-tight">Pertemuan Tercatat</div>
                <div class="mt-8">
                    <div class="flex justify-between items-end mb-2">
                        <span class="text-xs font-bold uppercase tracking-widest opacity-60">Pencapaian</span>
                        <span class="text-2xl font-black"><?= $attendance_rate ?>%</span>
                    </div>
                    <div class="h-3 bg-white/10 rounded-full overflow-hidden">
                        <div class="h-full bg-blue-400 rounded-full transition-all duration-1000" style="width: <?= $attendance_rate ?>%"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Slide 3: Kas -->
    <div class="slide slide-hidden-right vibrant-emerald" id="slide-2">
        <div class="z-10 w-full max-w-xs md:max-w-md px-4 text-white">
            <span class="text-[9px] md:text-[10px] font-extrabold uppercase tracking-[0.3em] text-emerald-300 mb-2 block">KEUANGAN KELAS</span>
            <h2 class="text-3xl md:text-5xl font-black mb-4 leading-tight">Urusan Kas?<br><span class="text-emerald-400">Sangat Aman!</span></h2>
            <div class="glass p-4 md:p-6 mb-4 text-left relative overflow-hidden bg-white/5 border-white/10 data-card">
                <div class="absolute -top-4 -right-4 p-4 opacity-10 text-7xl md:text-8xl rotate-12">
                    <i class="fas fa-wallet"></i>
                </div>
                <div class="text-[9px] md:text-[10px] font-bold opacity-60 mb-1 uppercase tracking-widest">Total Kontribusi</div>
                <div class="text-3xl md:text-4xl font-black mb-1">Rp <?= number_format($total_uang_kas, 0, ',', '.') ?></div>
                <p class="text-xs md:text-base font-medium opacity-80 mb-6"><?= $total_minggu_kas ?> Minggu Terbayar Lunas</p>

                <div class="flex justify-between items-end border-t border-white/10 pt-6">
                    <div>
                        <div class="text-[9px] md:text-[10px] font-bold opacity-60 uppercase mb-1">Status Keuangan</div>
                        <div class="text-lg md:text-xl font-black uppercase tracking-wider"><?= $kas_rate >= 100 ? 'Sultan Kelas' : ($kas_rate >= 80 ? 'Warga Teladan' : 'Masih Terkendali') ?></div>
                    </div>
                    <div class="text-3xl md:text-4xl">
                        <i class="fas <?= $kas_rate >= 80 ? 'fa-crown text-amber-400' : 'fa-check-circle text-emerald-400' ?>"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Slide 4: Tugas -->
    <div class="slide slide-hidden-right vibrant-purple" id="slide-3">
        <div class="z-10 w-full max-w-xs md:max-w-md px-4 text-white">
            <span class="text-[9px] md:text-[10px] font-extrabold uppercase tracking-[0.3em] text-purple-300 mb-2 block">PRODUKTIVITAS</span>
            <h2 class="text-3xl md:text-5xl font-black mb-4 leading-tight">Pejuang<br><span class="text-purple-400">Ambisius!</span></h2>
            <div class="data-card">
                <div class="grid grid-cols-2 gap-3 mb-3">
                    <div class="glass p-3 md:p-4 text-center bg-white/5">
                        <div class="text-xl md:text-2xl font-black mb-1"><?= $total_global_done ?></div>
                        <div class="text-[8px] md:text-[9px] font-bold opacity-60 uppercase tracking-widest">Tugas Kelas</div>
                    </div>
                    <div class="glass p-3 md:p-4 text-center bg-white/5">
                        <div class="text-xl md:text-2xl font-black mb-1"><?= $total_personal_done ?></div>
                        <div class="text-[8px] md:text-[9px] font-bold opacity-60 uppercase tracking-widest">Tugas Pribadi</div>
                    </div>
                </div>
                <div class="glass p-4 md:p-6 relative overflow-hidden text-left bg-white/5 flex-1 flex flex-col justify-center">
                    <div class="text-4xl md:text-6xl font-black leading-none mb-1"><?= $total_tugas_selesai ?></div>
                    <div class="text-base md:text-xl font-bold opacity-80 uppercase tracking-tighter">Misi Terselesaikan</div>
                    <div class="absolute -bottom-6 -right-6 opacity-10 text-[6rem] md:text-[8rem] rotate-12">
                        <i class="fas fa-check-double"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Slide 5: Kuis Harian -->
    <div class="slide slide-hidden-right vibrant-amber" id="slide-4">
        <div class="z-10 w-full max-w-xs md:max-w-md px-4 text-white">
            <span class="text-[9px] md:text-[10px] font-extrabold uppercase tracking-[0.3em] text-amber-300 mb-2 block">KUIS HARIAN</span>
            <h2 class="text-3xl md:text-5xl font-black mb-4 leading-tight">Kecerdasan<br><span class="text-amber-400">Teruji!</span></h2>
            <div class="data-card">
                <div class="flex items-center justify-center gap-4 mb-3 flex-1">
                    <div class="text-5xl md:text-7xl text-orange-400 floating"><i class="fas fa-fire"></i></div>
                    <div class="text-left">
                        <div class="text-4xl md:text-6xl font-black leading-none"><?= $kuis_streak ?></div>
                        <div class="text-xs md:text-base font-bold opacity-60 uppercase tracking-widest">Max Streak</div>
                    </div>
                </div>
                <div class="glass p-4 md:p-6 flex justify-between items-center bg-white/5">
                    <div class="text-left border-r border-white/10 pr-6 md:pr-10">
                        <div class="text-[8px] md:text-[9px] font-bold opacity-60 uppercase mb-1">Total Game</div>
                        <div class="text-xl md:text-3xl font-black"><?= $kuis_total ?></div>
                    </div>
                    <div class="text-right">
                        <div class="text-[8px] md:text-[9px] font-bold opacity-60 uppercase mb-1">Status Kuis</div>
                        <div class="text-xl md:text-3xl font-black"><?= $kuis_streak >= 7 ? 'Sifu' : ($kuis_streak >= 3 ? 'Expert' : 'Rookie') ?></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Slide 6: Vibe Reveal -->
    <div class="slide slide-hidden-right vibrant-indigo" id="slide-5">
        <div class="z-10 w-full max-w-xs md:max-w-md px-4 text-white">
            <span class="text-[9px] md:text-[10px] font-extrabold uppercase tracking-[0.3em] text-indigo-300 mb-2 block">MOOD TRACKER</span>
            <h2 class="text-3xl md:text-5xl font-black mb-4 leading-tight text-white">Vibe Kamu<br><span class="text-indigo-400">Dominan!</span></h2>
            <div class="glass p-4 md:p-6 mb-4 text-left relative overflow-hidden bg-white/5 border-white/10 data-card text-white">
                <div class="absolute -top-4 -right-4 p-4 opacity-10 text-7xl md:text-8xl rotate-12">
                    <i class="fas <?= $v_details['icon'] ?>"></i>
                </div>
                <div class="text-4xl md:text-6xl font-black mb-1 text-white"><?= $v_details['label'] ?></div>
                <div class="text-[10px] md:text-sm font-bold opacity-60 uppercase tracking-tight text-white">Vibe Paling Sering</div>

                <div class="mt-4">
                    <p class="text-xs md:text-base font-medium opacity-80 mb-6 text-white">"<?= $v_details['desc'] ?>"</p>

                    <div class="flex justify-between items-end border-t border-white/10 pt-6">
                        <div>
                            <div class="text-[9px] md:text-[10px] font-bold opacity-60 uppercase mb-1 text-white">Total Check-In</div>
                            <div class="text-lg md:text-xl font-black uppercase tracking-wider text-white"><?= $my_vibe_count ?> Hari</div>
                        </div>
                        <div class="text-3xl md:text-4xl text-white">
                            <i class="fas <?= $v_details['icon'] ?> floating"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Slide 7: Top 3 Partners -->
    <div class="slide slide-hidden-right vibrant-rose/80" id="slide-6">
        <div class="z-10 w-full max-w-xs md:max-w-md px-4 text-white">
            <span class="text-[9px] md:text-[10px] font-extrabold uppercase tracking-[0.3em] text-rose-300 mb-2 block">THE INNER CIRCLE</span>
            <h2 class="text-3xl md:text-5xl font-black mb-6 leading-tight">Partner Kerja<br><span class="text-rose-400">Terdekat.</span></h2>

            <div class="space-y-3">
                <?php
                $display_friends = $friends_data;
                shuffle($display_friends);
                foreach ($display_friends as $index => $f): ?>
                    <div class="glass p-4 flex items-center gap-4 text-left scale-in bg-white/5" style="animation-delay: <?= $index * 0.2 ?>s">
                        <img src="<?= profilePhotoUrl($f['foto'], '') ?>" class="w-14 h-14 md:w-16 md:h-16 rounded-xl border-2 border-white/10 object-cover">
                        <div class="flex-1">
                            <div class="text-lg md:text-xl font-black leading-tight"><?= htmlspecialchars($f['nama_panggilan']) ?></div>
                            <div class="text-[9px] md:text-[10px] font-bold opacity-40 uppercase tracking-widest">Kolaborasi Aktif</div>
                        </div>
                        <div class="text-rose-400 h-10 w-10 flex items-center justify-center rounded-full bg-rose-500/10">
                            <i class="fas fa-heart"></i>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <!-- Slide 8: Partner Transition -->
    <div class="slide slide-hidden-right vibrant-rose/90" id="slide-7">
        <div class="z-10 w-full px-4">
            <h2 class="text-5xl md:text-7xl font-black mb-4 uppercase tracking-tighter scale-in">Siapakah</h2>
            <h2 class="text-6xl md:text-8xl font-black mb-8 italic scale-in text-rose-300" style="animation-delay: 0.5s">Partner Kerja <span class="text-white">Terbaik?</span></h2>
        </div>
    </div>

    <!-- Slide 9: Best Partner -->
    <div class="slide slide-hidden-right vibrant-rose" id="slide-8">
        <div class="z-10 w-full max-w-sm md:max-w-lg px-4 text-white">
            <span class="text-[9px] md:text-[10px] font-extrabold uppercase tracking-[0.3em] text-rose-300 mb-3 block">THE REVEAL</span>

            <?php if ($best_friend_data): ?>
                <div class="relative mb-12 inline-block">
                    <div class="absolute -inset-8 bg-rose-500/30 rounded-full blur-3xl animate-pulse"></div>
                    <div class="relative">
                        <img src="<?= profilePhotoUrl($best_friend_data['foto'], '') ?>" class="w-48 h-48 md:w-64 md:h-64 rounded-full mx-auto border-8 border-white shadow-2xl z-10 object-cover relative">
                        <div class="absolute -bottom-4 left-1/2 -translate-x-1/2 bg-white text-rose-900 px-8 py-3 rounded-2xl font-black shadow-2xl z-20 whitespace-nowrap text-xl">
                            <?= htmlspecialchars($best_friend_data['nama_panggilan']) ?>
                        </div>
                    </div>
                </div>
                <h2 class="text-3xl md:text-4xl font-black mb-4 px-6 leading-tight uppercase">Partner Kerja Paling Klop & Luar Biasa!</h2>
                <div class="glass p-4 inline-flex items-center gap-3 bg-white/10 uppercase tracking-widest text-[10px] font-bold">
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

    <!-- Final Summary Card -->
    <div class="slide slide-hidden-right vibrant-dark" id="slide-9">
        <div class="z-10 w-full max-w-sm px-4">
            <div class="bg-indigo-950/80 border border-white/10 p-5 md:p-8 rounded-[2rem] shadow-2xl relative overflow-hidden backdrop-blur-xl scale-95 md:scale-100">
                <div class="absolute -top-6 -right-6 opacity-5 pointer-events-none">
                    <img src="assets/image/logo.webp" class="w-36">
                </div>

                <div class="flex items-center gap-4 mb-8 relative">
                    <div class="relative">
                        <img src="<?= profilePhotoUrl($foto_user, '') ?>" class="w-12 h-12 md:w-16 md:h-16 rounded-xl border-2 border-indigo-500/30 object-cover shadow-lg">
                    </div>
                    <div class="text-left">
                        <h4 class="text-lg md:text-xl font-black uppercase tracking-tight leading-tight text-white"><?= htmlspecialchars($nama_lengkap) ?></h4>
                        <div class="flex items-center gap-2 opacity-50">
                            <span class="text-[9px] md:text-[10px] font-bold tracking-widest uppercase text-white"><?= $nim_user ?></span>
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-3 mb-8">
                    <div class="bg-white/5 p-3 rounded-xl border border-white/5 group transition-colors">
                        <div class="text-[8px] font-bold uppercase opacity-30 mb-1 tracking-widest text-white">Absensi</div>
                        <div class="text-xl font-black text-blue-400 group-hover:scale-105 transition-transform origin-left"><?= $attendance_rate ?>%</div>
                    </div>
                    <div class="bg-white/5 p-3 rounded-xl border border-white/5 group transition-colors">
                        <div class="text-[8px] font-bold uppercase opacity-30 mb-1 tracking-widest text-white">Kas</div>
                        <div class="text-xl font-black text-emerald-400 group-hover:scale-105 transition-transform origin-left"><?= $total_minggu_kas ?>/16</div>
                    </div>
                    <div class="bg-white/5 p-3 rounded-xl border border-white/5 group transition-colors">
                        <div class="text-[8px] font-bold uppercase opacity-30 mb-1 tracking-widest text-white">Tugas</div>
                        <div class="text-xl font-black text-purple-400 group-hover:scale-105 transition-transform origin-left"><?= $total_tugas_selesai ?></div>
                    </div>
                    <div class="bg-white/5 p-3 rounded-xl border border-white/5 group transition-colors">
                        <div class="text-[8px] font-bold uppercase opacity-30 mb-1 tracking-widest text-white">Streak</div>
                        <div class="text-xl font-black text-amber-400 group-hover:scale-105 transition-transform origin-left"><?= $kuis_streak ?></div>
                    </div>
                </div>

                <div class="text-center pt-2 border-t border-white/5">
                    <div class="text-[8px] font-bold uppercase tracking-[0.2em] mb-4 opacity-30 text-white">PTIK C 2024 WRAPPED</div>
                    <a href="dashboard" class="inline-block mt-2 text-[9px] font-bold uppercase opacity-40 hover:opacity-100 transition-opacity tracking-widest text-white">
                        Kembali ke Dashboard
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Controls -->
    <div class="fixed bottom-0 left-0 w-full p-4 flex justify-between items-center z-[200]">
        <div class="flex gap-2">
            <button onclick="prevSlide()" class="w-12 h-12 rounded-full border border-white/20 flex items-center justify-center hover:bg-white/10 transition backdrop-blur-sm">
                <i class="fas fa-chevron-left"></i>
            </button>
            <button onclick="nextSlide()" class="w-12 h-12 rounded-full border border-white/20 flex items-center justify-center hover:bg-white/10 transition backdrop-blur-sm">
                <i class="fas fa-chevron-right"></i>
            </button>
        </div>
        <div class="text-[10px] font-black tracking-widest opacity-40 uppercase">
            Sentuh atau gunakan panah untuk berpindah
        </div>
    </div>

    <script>
        const totalSlides = 10;
        let currentSlide = 0;
        let slideInterval;
        const duration = 8000; // 8 seconds per slide

        // Initialize progress bars
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
                const bar = document.getElementById(`bar-${i}`);

                if (i < index) {
                    slide.className = 'slide slide-hidden-left';
                    bar.style.width = '100%';
                } else if (i === index) {
                    slide.className = 'slide slide-active';
                    bar.style.width = '0%';
                } else {
                    slide.className = 'slide slide-hidden-right';
                    bar.style.width = '0%';
                }
            }

            resetTimer();
        }

        function nextSlide() {
            if (currentSlide < totalSlides - 1) {
                currentSlide++;
                showSlide(currentSlide);
            } else {
                // Stay on last slide
                clearInterval(slideInterval);
                document.getElementById(`bar-${totalSlides - 1}`).style.width = '100%';
            }
        }

        function prevSlide() {
            if (currentSlide > 0) {
                currentSlide--;
                showSlide(currentSlide);
            }
        }

        function resetTimer() {
            clearInterval(slideInterval);
            let startTime = Date.now();
            const bar = document.getElementById(`bar-${currentSlide}`);

            slideInterval = setInterval(() => {
                let elapsed = Date.now() - startTime;
                let progress = (elapsed / duration) * 100;

                if (progress >= 100) {
                    bar.style.width = '100%';
                    nextSlide();
                } else {
                    bar.style.width = progress + '%';
                }
            }, 10);
        }

        // Click detection for mobile (left side prev, right side next)
        document.addEventListener('mousedown', (e) => {
            if (e.target.closest('button') || e.target.closest('a')) return;
            const w = window.innerWidth;
            if (e.clientX < w / 3) {
                prevSlide();
            } else {
                nextSlide();
            }
        });

        // Key support
        document.addEventListener('keydown', (e) => {
            if (e.key === 'ArrowRight' || e.key === ' ') nextSlide();
            if (e.key === 'ArrowLeft') prevSlide();
        });


        // Start
        window.onload = () => {
            showSlide(0);
        };
    </script>
</body>

</html>