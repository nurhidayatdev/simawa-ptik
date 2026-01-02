<?php
session_start();
include '../../koneksi.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../../index.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$nama_lengkap = $_SESSION['nama_lengkap'] ?? '';
$foto = $_SESSION['foto'] ?? '';
$role = $_SESSION['role'];

if ($_SESSION['role'] !== 'Administrator Utama') {
    echo "<!DOCTYPE html>
    <html>
    <head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <meta name='theme-color' content='#1e1b4b' />
    <link rel='icon' href='../img/main/icon.png' />
    <title>Pesan - PTIK C 2024</title>
    <link rel='icon' href='../img/main/icon.png' />
  <link rel='preconnect' href='https://fonts.googleapis.com'>
    <link rel='preconnect' href='https://fonts.gstatic.com' crossorigin>
    <link href='https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap' rel='stylesheet'>
    <style>
        body { font-family: 'Poppins', sans-serif !important; }
    </style>
        <script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script>
    </head>
    <body>
        <script>
            Swal.fire({
                icon: 'error',
                title: 'Akses Ditolak!',
                text: 'Hanya Admin yang dapat mengakses halaman ini.',
                confirmButtonColor: '#3085d6',
                confirmButtonText: 'Kembali'
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = '../../index.php';
                } else {
                    window.location.href = '../../index.php';
                }
            });
        </script>
    </body>
    </html>";
    exit();
}

date_default_timezone_set('Asia/Makassar');

// Telegram Config
$telegram_token = "8212322367:AAGCGc4TG__EnsEd-20wFTI_s2adp-dp1OY";
$telegram_chat_id = "-1003577478223";

function sendTelegramMessage($token, $chat_id, $message) {
    $tg_ip = "149.154.167.220";
    $url_ip = "https://{$tg_ip}/bot{$token}/sendMessage";
    $url_domain = "https://api.telegram.org/bot{$token}/sendMessage";
    
    $data = ['chat_id' => $chat_id, 'text' => $message, 'parse_mode' => 'Markdown', 'disable_web_page_preview' => true];
    $post_data = http_build_query($data);

    if (function_exists('curl_init')) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url_ip);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
        curl_setopt($ch, CURLOPT_TIMEOUT, 20);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Host: api.telegram.org']);
        
        $result = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        
        if ($http_code == 200) { curl_close($ch); return ['success' => true, 'method' => 'CURL_IP']; }

        curl_setopt($ch, CURLOPT_URL, $url_domain);
        curl_setopt($ch, CURLOPT_HTTPHEADER, []);
        $result = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($http_code == 200) return ['success' => true, 'method' => 'CURL_DOMAIN'];
    }
    
    if (ini_get('allow_url_fopen')) {
        $options = ['http' => ['method' => 'POST', 'header' => "Content-Type: application/x-www-form-urlencoded\r\nHost: api.telegram.org\r\n", 'content' => $post_data, 'timeout' => 15], 'ssl' => ['verify_peer' => false, 'verify_peer_name' => false]];
        $context = stream_context_create($options);
        $result = @file_get_contents($url_ip, false, $context);
        if ($result !== FALSE) return ['success' => true, 'method' => 'FGC_IP'];
    }
    
    return ['success' => false, 'error' => 'All methods failed'];
}

// Handle Actions (PRG Pattern)
if (isset($_POST['action'])) {
    $action = $_POST['action'];
    $response_msg = '';
    $response_type = '';
    
    // JADWAL REMINDER
    if ($action === 'jadwal') {
        $days_map = ['Sunday' => 'Minggu', 'Monday' => 'Senin', 'Tuesday' => 'Selasa', 'Wednesday' => 'Rabu', 'Thursday' => 'Kamis', 'Friday' => 'Jumat', 'Saturday' => 'Sabtu'];
        $tomorrow_eng = date('l', strtotime('+1 day'));
        $tomorrow_ind = $days_map[$tomorrow_eng];
        
        $q_periode = mysqli_query($koneksi, "SELECT id FROM periode WHERE status = 'aktif' LIMIT 1");
        $periode = mysqli_fetch_assoc($q_periode);
        $periode_id = $periode['id'] ?? 0;
        
        $query = "SELECT jk_start.jam_mulai, jk_end.jam_selesai, r.nama_ruangan AS ruangan, mk.nama_matkul, dp.nama_dosen AS dosen_pengampu
                  FROM jadwal_matkul jm
                  JOIN mata_kuliah mk ON jm.matkul_id = mk.id
                  LEFT JOIN ruangan r ON jm.ruangan_id = r.id
                  LEFT JOIN jam_kuliah jk_start ON jm.jam_mulai_ke = jk_start.jam_ke
                  LEFT JOIN jam_kuliah jk_end ON jm.jam_selesai_ke = jk_end.jam_ke
                  LEFT JOIN dosen dp ON jm.dosen_pengampu_id = dp.id
                  WHERE jm.periode_id = $periode_id AND jm.hari = '$tomorrow_ind'
                  ORDER BY jk_start.jam_mulai ASC";
        $result = mysqli_query($koneksi, $query);
        
        if ($result && mysqli_num_rows($result) > 0) {
            $message = "📅 *JADWAL KULIAH BESOK* 📅\nHari: *{$tomorrow_ind}*, " . date('d M Y', strtotime('+1 day')) . "\n\n";
            $no = 1;
            while ($row = mysqli_fetch_assoc($result)) {
                $message .= "{$no}. *{$row['nama_matkul']}*\n   ⏰ " . date('H:i', strtotime($row['jam_mulai'])) . " - " . date('H:i', strtotime($row['jam_selesai'])) . " WITA\n   🏛️ {$row['ruangan']}\n   👨‍🏫 {$row['dosen_pengampu']}\n\n";
                $no++;
            }
            $message .= "_Jangan lupa datang tepat waktu!_ 📝";
            $res = sendTelegramMessage($telegram_token, $telegram_chat_id, $message);
            $response_msg = $res['success'] ? "Jadwal $tomorrow_ind berhasil dikirim!" : "Gagal: " . ($res['error'] ?? '');
            $response_type = $res['success'] ? 'success' : 'error';
        } else {
            $response_msg = "Tidak ada jadwal untuk hari $tomorrow_ind besok.";
            $response_type = 'info';
        }
    }
    
    // TUGAS REMINDER
    elseif ($action === 'tugas') {
        $now_date = date('Y-m-d');
        $now_time = date('H:i:s');
        $query = "SELECT t.deadline_tanggal, t.deadline_pukul, t.deskripsi, t.link_kirim, mk.nama_matkul
                  FROM tugas t JOIN mata_kuliah mk ON t.matkul_id = mk.id
                  WHERE t.status = 'belum' AND (t.deadline_tanggal > '$now_date' OR (t.deadline_tanggal = '$now_date' AND t.deadline_pukul > '$now_time'))
                  ORDER BY t.deadline_tanggal ASC, t.deadline_pukul ASC";
        $result = mysqli_query($koneksi, $query);
        
        if ($result && mysqli_num_rows($result) > 0) {
            $message = "🔔 *REKAP TUGAS AKTIF PTIK C* 🔔\n\nDaftar tugas *belum dikumpulkan*:\n\n";
            $no = 1;
            while ($row = mysqli_fetch_assoc($result)) {
                $status_time = $row['deadline_tanggal'] == $now_date ? "⚠️ *HARI INI*" : ($row['deadline_tanggal'] == date('Y-m-d', strtotime('+1 day')) ? "⏰ *BESOK*" : "📅 " . date('d M Y', strtotime($row['deadline_tanggal'])));
                $message .= "*{$no}. {$row['nama_matkul']}*\n   {$status_time} | " . date('H:i', strtotime($row['deadline_pukul'])) . " WITA\n";
                if (!empty($row['deskripsi'])) $message .= "   📝 {$row['deskripsi']}\n";
                if (!empty($row['link_kirim'])) $message .= "   🔗 [Link]({$row['link_kirim']})\n";
                $message .= "\n";
                $no++;
            }
            $message .= "_Segera kerjakan!_ 🚀";
            $res = sendTelegramMessage($telegram_token, $telegram_chat_id, $message);
            $response_msg = $res['success'] ? "Reminder tugas berhasil dikirim!" : "Gagal: " . ($res['error'] ?? '');
            $response_type = $res['success'] ? 'success' : 'error';
        } else {
            $response_msg = "Tidak ada tugas aktif.";
            $response_type = 'info';
        }
    }
    
    // KAS REMINDER
    elseif ($action === 'kas') {
        $minggu_ke = (int)($_POST['minggu_ke'] ?? 1);
        $q_belum = "SELECT m.nama_panggilan FROM kas k JOIN mahasiswa m ON k.mahasiswa_id = m.id WHERE k.minggu_ke = '$minggu_ke' AND (k.status IS NULL OR k.status != 'Lunas') ORDER BY m.nim ASC";
        $res_belum = mysqli_query($koneksi, $q_belum);
        
        if ($res_belum && mysqli_num_rows($res_belum) > 0) {
            $message = "💰 *PENGINGAT KAS MINGGUAN*\n📅 *Minggu ke-{$minggu_ke}*\n\nBelum bayar:\n\n";
            $no = 1;
            while ($row = mysqli_fetch_assoc($res_belum)) {
                $message .= $no . ". " . $row['nama_panggilan'] . "\n";
                $no++;
            }
            $message .= "\n_Segera lakukan pembayaran!_ 🙏";
            $res = sendTelegramMessage($telegram_token, $telegram_chat_id, $message);
            $response_msg = $res['success'] ? "Reminder kas minggu $minggu_ke berhasil dikirim!" : "Gagal: " . ($res['error'] ?? '');
            $response_type = $res['success'] ? 'success' : 'error';
        } else {
            $response_msg = "Semua sudah lunas untuk minggu $minggu_ke.";
            $response_type = 'info';
        }
    }
    
    // KUIS HARIAN STREAK REMINDER
    elseif ($action === 'streak') {
        $today = date('Y-m-d');
        // Get users who have streak > 0 and haven't completed today
        $query = "SELECT m.nama_panggilan, s.current_streak
                  FROM kuis_harian_stats s 
                  JOIN mahasiswa m ON s.user_id = m.user_id 
                  WHERE s.current_streak > 0 
                  AND (s.last_played_date IS NULL OR s.last_played_date != '$today' OR DATE(s.last_completed_at) != '$today')
                  ORDER BY s.current_streak DESC";
        $result = mysqli_query($koneksi, $query);
        
        if ($result && mysqli_num_rows($result) > 0) {
            $message = "🔥 *REMINDER KUIS HARIAN* 🔥\n\nStreakmu akan hilang jika tidak main hari ini!\n\n";
            $no = 1;
            while ($row = mysqli_fetch_assoc($result)) {
                $message .= $no . ". " . $row['nama_panggilan'] . " (🔥" . $row['current_streak'] . ")\n";
                $no++;
            }
            $message .= "\n_Main sekarang untuk jaga streak!_ 🎮";
            $res = sendTelegramMessage($telegram_token, $telegram_chat_id, $message);
            $response_msg = $res['success'] ? "Reminder streak berhasil dikirim ke " . ($no-1) . " pemain!" : "Gagal: " . ($res['error'] ?? '');
            $response_type = $res['success'] ? 'success' : 'error';
        } else {
            if (!$result) {
                $response_msg = "Error: " . mysqli_error($koneksi);
                $response_type = 'error';
            } else {
                $response_msg = "Semua pemain sudah main hari ini atau tidak ada streak aktif.";
                $response_type = 'info';
            }
        }
    }
    
    // KRS REMINDER
    elseif ($action === 'krs') {
        $q_periode = mysqli_query($koneksi, "SELECT id FROM periode WHERE status = 'aktif' LIMIT 1");
        $periode = mysqli_fetch_assoc($q_periode);
        $periode_id = $periode['id'] ?? 0;

        $q_belum = "SELECT m.nama_lengkap 
                    FROM mahasiswa m 
                    LEFT JOIN (SELECT DISTINCT mahasiswa_id FROM krs WHERE periode_id = $periode_id) k ON m.id = k.mahasiswa_id 
                    WHERE k.mahasiswa_id IS NULL 
                    ORDER BY m.nim ASC";
        $res_belum = mysqli_query($koneksi, $q_belum);
        
        if ($res_belum && mysqli_num_rows($res_belum) > 0) {
            $message = "📝 *PENGINGAT PENGISIAN KRS*\n\nMahasiswa berikut *BELUM* mengisi KRS untuk periode aktif:\n\n";
            $no = 1;
            while ($row = mysqli_fetch_assoc($res_belum)) {
                $message .= $no . ". " . $row['nama_lengkap'] . "\n";
                $no++;
            }
            $message .= "\n_Harap segera mengisi KRS di sistem!_ 🚀";
            $res = sendTelegramMessage($telegram_token, $telegram_chat_id, $message);
            $response_msg = $res['success'] ? "Reminder KRS berhasil dikirim ke " . ($no-1) . " mahasiswa!" : "Gagal: " . ($res['error'] ?? '');
            $response_type = $res['success'] ? 'success' : 'error';
        } else {
            $response_msg = "Semua mahasiswa sudah mengisi KRS.";
            $response_type = 'info';
        }
    }
    
    // KELOMPOK REMINDER
    elseif ($action === 'kelompok') {
        $matkul_id = mysqli_real_escape_string($koneksi, $_POST['matkul_id']);
        $dosen = mysqli_real_escape_string($koneksi, $_POST['dosen']);
        $keterangan = mysqli_real_escape_string($koneksi, $_POST['keterangan']);
        
        $q_periode = mysqli_query($koneksi, "SELECT id FROM periode WHERE status = 'aktif' LIMIT 1");
        $periode = mysqli_fetch_assoc($q_periode);
        $periode_id = $periode['id'] ?? 0;

        // Get matkul name
        $q_mk = mysqli_query($koneksi, "SELECT nama_matkul FROM mata_kuliah WHERE id = $matkul_id");
        $mk_name = mysqli_fetch_assoc($q_mk)['nama_matkul'] ?? 'Mata Kuliah';

        // Get groups and members
        $q_groups = "SELECT kelompok_no, anggota 
                     FROM kelompok 
                     WHERE matkul_id = '$matkul_id' 
                     AND dosen = '$dosen' 
                     AND keterangan = '$keterangan' 
                     AND periode_id = '$periode_id' 
                     ORDER BY kelompok_no ASC";
        $res_groups = mysqli_query($koneksi, $q_groups);
        
        if ($res_groups && mysqli_num_rows($res_groups) > 0) {
            $groups_data = [];
            $all_nims = [];
            while ($row = mysqli_fetch_assoc($res_groups)) {
                $nims = array_filter(array_map('trim', explode(',', $row['anggota'])));
                foreach ($nims as $nim) $all_nims[$nim] = true;
                $groups_data[] = [
                    'no' => $row['kelompok_no'],
                    'nims' => $nims
                ];
            }

            // Map NIM to Name
            $student_names = [];
            if (!empty($all_nims)) {
                $nim_list = "'" . implode("','", array_map(function($n) use ($koneksi) { return mysqli_real_escape_string($koneksi, $n); }, array_keys($all_nims))) . "'";
                $res_names = mysqli_query($koneksi, "SELECT nim, nama_lengkap FROM mahasiswa WHERE nim IN ($nim_list)");
                while ($rn = mysqli_fetch_assoc($res_names)) {
                    $student_names[$rn['nim']] = $rn['nama_lengkap'];
                }
            }

            $message = "👥 *DAFTAR KELOMPOK BARU* 👥\n";
            $message .= "📘 *Matkul:* {$mk_name}\n";
            $message .= "👨‍🏫 *Dosen:* {$dosen}\n";
            if (!empty($keterangan)) $message .= "📝 *Ket:* {$keterangan}\n";
            $message .= "\n";
            
            foreach ($groups_data as $g) {
                $message .= "*Kelompok {$g['no']}:*\n";
                foreach ($g['nims'] as $nim) {
                    $nama = $student_names[$nim] ?? "Mahasiswa ($nim)";
                    $message .= "• {$nama}\n";
                }
                $message .= "\n";
            }
            
            $res = sendTelegramMessage($telegram_token, $telegram_chat_id, $message);
            $response_msg = $res['success'] ? "Daftar kelompok berhasil dikirim!" : "Gagal: " . ($res['error'] ?? '');
            $response_type = $res['success'] ? 'success' : 'error';
        } else {
            $response_msg = "Data kelompok tidak ditemukan.";
            $response_type = 'error';
        }
    }

    $_SESSION['reminder_msg'] = $response_msg;
    $_SESSION['reminder_type'] = $response_type;
    header("Location: reminder.php");
    exit;
}


// Counts for display
$now_date = date('Y-m-d');
$now_time = date('H:i:s');
$tugas_count = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT COUNT(*) as cnt FROM tugas WHERE status='belum' AND (deadline_tanggal > '$now_date' OR (deadline_tanggal = '$now_date' AND deadline_pukul > '$now_time'))"))['cnt'] ?? 0;
$streak_count = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT COUNT(*) as cnt FROM kuis_harian_stats WHERE current_streak > 0 AND (last_played_date IS NULL OR last_played_date != '$now_date' OR DATE(last_completed_at) != '$now_date')"))['cnt'] ?? 0;

$q_p = mysqli_query($koneksi, "SELECT id FROM periode WHERE status = 'aktif' LIMIT 1");
$p_id = mysqli_fetch_assoc($q_p)['id'] ?? 0;
$krs_count = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT COUNT(*) as cnt FROM mahasiswa m LEFT JOIN (SELECT DISTINCT mahasiswa_id FROM krs WHERE periode_id = $p_id) k ON m.id = k.mahasiswa_id WHERE k.mahasiswa_id IS NULL"))['cnt'] ?? 0;

// Fetch unique group sets
$q_sets = mysqli_query($koneksi, "
    SELECT DISTINCT 
        k.matkul_id, 
        mk.nama_matkul, 
        k.dosen, 
        k.keterangan 
    FROM kelompok k 
    JOIN mata_kuliah mk ON k.matkul_id = mk.id 
    WHERE k.periode_id = $p_id
    ORDER BY mk.nama_matkul ASC, k.dosen ASC
");
$group_sets = [];
while ($row = mysqli_fetch_assoc($q_sets)) {
    $group_sets[] = $row;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#1e1b4b" />
    <link rel="icon" href="../img/main/icon.png" />
    <title>Reminder - PTIK C 2024</title>
    <link href="../../frontend/tailwind/src/output.css" rel="stylesheet">
    <link href="../../frontend/tailwind/src/input.css" rel="stylesheet">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&display=swap" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
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
    </style>
</head>

<body class="bg-slate-100">
    <!-- Header dengan z-index lebih tinggi -->
    <header class="bg-indigo-950  fixed w-full top-0 z-50">
        <div class="flex justify-between items-center px-6 py-2">
            <div class="flex items-center">
                <img src="../img/main/logo.png" alt="Logo Universitas Negeri Makassar" class="h-10 w-10 mr-3">
                <span class="text-xl font-bold text-white">Dashboard PTIK C</span>
            </div>
            <!-- Mobile Menu Button dengan z-index yang sesuai -->
            <button id="mobile-menu-button" class="text-slate-100 hover:text-indigo-950 lg:hidden p-2 rounded-lg hover:bg-gray-100">
                <i class="fa fa-bars w-6 h-6"></i>
            </button>
            <div class="hidden lg:flex items-center space-x-4">
                <div class="relative">
                    <button id="profile-button" class="flex items-center space-x-2">
                        <span class="text-white font-semibold"><?php echo htmlspecialchars($nama_lengkap); ?></span>
                        <img src="../img/profile/<?php echo htmlspecialchars($foto); ?>" alt="Profile" class="w-8 h-8 rounded-full">
                    </button>
                    <div id="profile-dropdown" class="absolute right-0 mt-2 w-40 bg-white rounded-md shadow-lg hidden">
                        <a href="profile.php" class="block px-4 py-2 text-xs hover:bg-gray-100 font-semibold text-indigo-900"><i class="fa fa-user mr-2 text-indigo-900"></i>Profile</a>
                        <a href="../../index.php" class="block px-4 py-2 text-xs hover:bg-gray-100 font-semibold text-indigo-900"><i class="fa fa-sign-out mr-2 text-indigo-900"></i>Logout</a>
                    </div>
                </div>
            </div>
        </div>
    </header>

    <!-- Sidebar dengan z-index di bawah header -->
    <aside id="sidebar"
        class="fixed left-0 top-0 h-screen w-48 bg-white transform -translate-x-full lg:translate-x-0 transition-transform duration-200 ease-in-out z-40">
        <!-- Tambahan padding top agar tidak tertutup header -->
        <div class="pt-16">
            <nav class="mt-4 h-[calc(100vh-4rem)] overflow-y-auto pb-10">
                <div class="px-4 space-y-2">
                    <!-- Dashboard Menu -->
                    <a href="dashboard.php" class="flex items-center px-4 py-2 text-indigo-900  rounded-lg hover:bg-slate-100">
                        <i class="fa fa-home w-4 h-4 mr-4"></i>
                        <span class="text-xs font-medium">Dashboard</span>
                    </a>



                    <!-- Components Menu -->
                    <div class="space-y-1">
                        <div>
                            <p class="px-4 text-[10px] font-semibold text-slate-400 uppercase tracking-wider mb-2">Akademik</p>
                            <div class="space-y-1">
                                <button class="submenu-button flex items-center justify-between w-full px-4 py-1 text-slate-800 hover:bg-slate-100 rounded-lg transition-colors">
                                    <div class="flex items-center">
                                        <i class="fa fa-university w-5 h-5 mr-3 text-indigo-900"></i>
                                        <span class="text-xs font-medium">Master</span>
                                    </div>
                                    <i class="fa fa-chevron-down submenu-arrow w-3 h-3 transition-transform duration-200"></i>
                                </button>
                                <div class="submenu pl-1 space-y-1 hidden overflow-y-auto max-h-52 border-l-2 border-slate-100 ml-6">
                                    <a href="db_mahasiswa.php" class="block px-4 py-1 text-xs text-slate-600 hover:text-indigo-600 hover:bg-slate-50 rounded-lg">Mahasiswa</a>
                                    <a href="db_dosen.php" class="block px-4 py-1 text-xs text-slate-600 hover:text-indigo-600 hover:bg-slate-50 rounded-lg">Dosen</a>
                                    <a href="db_matkul.php" class="block px-4 py-1 text-xs text-slate-600 hover:text-indigo-600 hover:bg-slate-50 rounded-lg">Mata Kuliah</a>
                                    <a href="db_berita.php" class="block px-4 py-1 text-xs text-slate-600 hover:text-indigo-600 hover:bg-slate-50 rounded-lg">Berita & Info</a>
                                    <a href="db_kalender.php" class="block px-4 py-1 text-xs text-slate-600 hover:text-indigo-600 hover:bg-slate-50 rounded-lg">Kalender Akademik</a>
                                    <a href="db_galeri.php" class="block px-4 py-1 text-xs text-slate-600 hover:text-indigo-600 hover:bg-slate-50 rounded-lg">Galeri Kelas</a>
                                </div>

                                <a href="db_jadwal.php" class="flex items-center px-4 py-1 text-slate-800 hover:bg-slate-100 rounded-lg transition-colors">
                                    <i class="fa fa-list-alt w-5 h-5 mr-3 text-indigo-900"></i>
                                    <span class="text-xs font-medium">Jadwal Kuliah</span>
                                </a>

                                <a href="db_tugas.php" class="flex items-center px-4 py-1 text-slate-800 hover:bg-slate-100 rounded-lg transition-colors">
                                    <i class="fa fa-tasks w-5 h-5 mr-3 text-indigo-900"></i>
                                    <span class="text-xs font-medium">Tugas Kuliah</span>
                                </a>
                                <a href="db_kelompok.php" class="flex items-center px-4 py-1 text-slate-800 hover:bg-slate-100 rounded-lg transition-colors">
                                    <i class="fa fa-users w-5 h-5 mr-3 text-indigo-900"></i>
                                    <span class="text-xs font-medium">Kelompok</span>
                                </a>
                                <a href="kalender_view.php" class="flex items-center px-4 py-1 text-slate-800 hover:bg-slate-100 rounded-lg transition-colors">
                                    <i class="fa fa-calendar w-5 h-5 mr-3 text-indigo-900"></i>
                                    <span class="text-xs font-medium">Kalender</span>
                                </a>

                            </div>
                        </div>

                        <div>
                            <p class="px-4 text-[10px] font-semibold text-slate-400 uppercase tracking-wider mb-2">Administrasi</p>
                            <div class="space-y-1">
                                <button class="submenu-button flex items-center justify-between w-full px-4 py-1 text-slate-800 hover:bg-slate-100 rounded-lg transition-colors">
                                    <div class="flex items-center">
                                        <i class="fa fa-clipboard-list w-5 h-5 mr-3 text-indigo-900"></i>
                                        <span class="text-xs font-medium">Absensi</span>
                                    </div>
                                    <i class="fa fa-chevron-down submenu-arrow w-3 h-3 transition-transform duration-200"></i>
                                </button>
                                <div class="submenu pl-1 space-y-1 hidden overflow-y-auto max-h-52 border-l-2 border-slate-100 ml-6">
                                    <a href="db_absensi.php" class="block px-4 py-1 text-xs text-slate-600 hover:text-indigo-600 hover:bg-slate-50 rounded-lg">Data Absen</a>
                                    <?php if ($role === 'Administrator Utama' || $role === 'Sekretaris'): ?>
                                        <a href="absensi.php" class="block px-4 py-1 text-xs text-slate-600 hover:text-indigo-600 hover:bg-slate-50 rounded-lg">Input Absen</a>
                                    <?php endif; ?>
                                </div>

                                <button class="submenu-button flex items-center justify-between w-full px-4 py-1 text-slate-800 hover:bg-slate-100 rounded-lg transition-colors">
                                    <div class="flex items-center">
                                        <i class="fa fa-wallet w-5 h-5 mr-3 text-indigo-900"></i>
                                        <span class="text-xs font-medium">Keuangan</span>
                                    </div>
                                    <i class="fa fa-chevron-down submenu-arrow w-3 h-3 transition-transform duration-200"></i>
                                </button>
                                <div class="submenu pl-1 space-y-1 hidden overflow-y-auto max-h-52 border-l-2 border-slate-100 ml-6">
                                    <a href="db_kas.php" class="block px-4 py-1 text-xs text-slate-600 hover:text-indigo-600 hover:bg-slate-50 rounded-lg">Data Kas</a>
                                    <?php if ($role === 'Administrator Utama' || $role === 'Bendahara'): ?>
                                        <a href="arus_kas.php" class="block px-4 py-1 text-xs text-slate-600 hover:text-indigo-600 hover:bg-slate-50 rounded-lg">Arus Kas</a>
                                        <a href="kas.php" class="block px-4 py-1 text-xs text-slate-600 hover:text-indigo-600 hover:bg-slate-50 rounded-lg">Input Per Minggu</a>
                                        <a href="kas_individu.php" class="block px-4 py-1 text-xs text-slate-600 hover:text-indigo-600 hover:bg-slate-50 rounded-lg">Input Per Individu</a>
                                    <?php endif; ?>
                                </div>


                            </div>
                        </div>

                        <div>
                            <p class="px-4 text-[10px] font-semibold text-slate-400 uppercase tracking-wider mb-2">Aktivitas</p>
                            <div class="space-y-1">




                                <a href="misi.php" class="flex items-center px-4 py-1 text-slate-800 hover:bg-slate-100 rounded-lg transition-colors">
                                    <i class="fa fa-trophy w-5 h-5 mr-3 text-indigo-900"></i>
                                    <span class="text-xs font-medium">Pusat Misi</span>
                                </a>
                                <a href="kuis_harian.php" class="flex items-center px-4 py-1 text-slate-800 hover:bg-slate-100 rounded-lg transition-colors">
                                    <i class="fa fa-gamepad w-5 h-5 mr-3 text-indigo-900"></i>
                                    <span class="text-xs font-medium">Kuis Harian</span>
                                </a>
                                <a href="db_pesan.php" class="flex items-center px-4 py-1 text-slate-800 hover:bg-slate-100 rounded-lg transition-colors">
                                    <i class="fa fa-comments w-5 h-5 mr-3 text-indigo-900"></i>
                                    <span class="text-xs font-medium">Pesan Anonim</span>
                                </a>

                            </div>
                        </div>


                        <div>
                            <p class="px-4 text-[10px] font-semibold text-slate-400 uppercase tracking-wider mb-2">Pengaturan</p>
                            <div class="space-y-1">
                                <button class="submenu-button flex items-center justify-between w-full px-4 py-1 text-slate-800 hover:bg-slate-100 rounded-lg transition-colors">
                                    <div class="flex items-center">
                                        <i class="fa fa-user w-5 h-5 mr-3 text-indigo-900"></i>
                                        <span class="text-xs font-medium">Akun</span>
                                    </div>
                                    <i class="fa fa-chevron-down submenu-arrow w-3 h-3 transition-transform duration-200"></i>
                                </button>
                                <div class="submenu pl-1 space-y-1 hidden overflow-y-auto max-h-52 border-l-2 border-slate-100 ml-6">
                                    <a href="profile.php" class="block px-4 py-1 text-xs text-slate-600 hover:text-indigo-600 hover:bg-slate-50 rounded-lg">Profile Saya</a>
                                    <a href="krs.php" class="block px-4 py-1 text-xs text-slate-600 hover:text-indigo-600 hover:bg-slate-50 rounded-lg">KRS</a>

                                </div>
                                <?php if ($role === 'Administrator Utama'): ?>
                                    <button class="submenu-button flex items-center justify-between w-full px-4 py-1 text-slate-800 hover:bg-slate-100 rounded-lg transition-colors">
                                        <div class="flex items-center">
                                            <i class="fa-solid fa-earth-americas w-5 h-5 mr-3 text-indigo-900"></i>
                                            <span class="text-xs font-medium">Website</span>
                                        </div>
                                        <i class="fa fa-chevron-down submenu-arrow w-3 h-3 transition-transform duration-200"></i>
                                    </button>
                                    <div class="submenu pl-1 space-y-1 hidden overflow-y-auto max-h-52 border-l-2 border-slate-100 ml-6">
                                        <a href="db_users.php" class="block px-4 py-1 text-xs text-slate-600 hover:text-indigo-600 hover:bg-slate-50 rounded-lg">Users</a>
                                        <a href="db_periode.php" class="block px-4 py-1 text-xs text-slate-600 hover:text-indigo-600 hover:bg-slate-50 rounded-lg">Periode</a>
                                        <a href="reminder.php" class="block px-4 py-1 text-xs text-slate-600 hover:text-indigo-600 hover:bg-slate-50 rounded-lg">Reminder</a>
                                    </div>
                                <?php endif; ?>


                                <a href="tentang.php" class="flex items-center px-4 py-1 text-slate-800 hover:bg-slate-100 rounded-lg transition-colors">
                                    <i class="fa fa-info-circle w-5 h-5 mr-3 text-indigo-900"></i>
                                    <span class="text-xs font-medium">Tentang Aplikasi</span>
                                </a>

                                <a href="../../index.php" class="flex items-center px-4 py-1 text-slate-800 hover:bg-red-50 hover:text-red-700 rounded-lg transition-colors">
                                    <i class="fa fa-sign-out-alt w-5 h-5 mr-3 text-indigo-900"></i>
                                    <span class="text-xs font-medium">Logout</span>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </nav>
        </div>
    </aside>

    <main class="ml-0 lg:ml-48 pt-20 p-6 flex flex-col min-h-screen">
        
        
        <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6 mb-6">
            <div class="flex items-center gap-3">
                <div class="w-12 h-12 bg-blue-500 rounded-xl flex items-center justify-center">
                    <i class="fab fa-telegram text-white text-2xl"></i>
                </div>
                <div>
                    <h2 class="text-lg font-bold text-slate-800">Kirim Reminder ke Telegram</h2>
                    <p class="text-xs text-slate-500">Pilih jenis reminder yang ingin dikirim ke grup PTIK C</p>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            
            

            <!-- KRS Reminder -->
            <form method="POST" class="bg-white rounded-xl shadow-sm border border-slate-200 p-5 hover:shadow-md transition">
                <input type="hidden" name="action" value="krs">
                <div class="flex items-start gap-4">
                    <div class="w-12 h-12 bg-purple-100 text-purple-600 rounded-lg flex items-center justify-center flex-shrink-0 relative">
                        <i class="fa fa-university text-xl"></i>
                        <?php if ($krs_count > 0): ?>
                        <span class="absolute -top-1 -right-1 bg-purple-500 text-white text-[10px] font-bold w-5 h-5 rounded-full flex items-center justify-center"><?= $krs_count ?></span>
                        <?php endif; ?>
                    </div>
                    <div class="flex-grow">
                        <h3 class="font-bold text-slate-800 text-md">Reminder KRS</h3>
                        <p class="text-xs text-slate-500 mt-1 mb-3">Ingatkan mahasiswa yang belum mengisi KRS</p>
                        <button type="submit" class="w-full bg-purple-600 hover:bg-purple-700 text-white text-xs font-bold py-2 px-4 rounded-lg transition">
                            <i class="fab fa-telegram mr-1"></i> Kirim
                        </button>
                    </div>
                </div>
            </form>

            <!-- Tugas -->
            <form method="POST" class="bg-white rounded-xl shadow-sm border border-slate-200 p-5 hover:shadow-md transition">
                <input type="hidden" name="action" value="tugas">
                <div class="flex items-start gap-4">
                    <div class="w-12 h-12 bg-orange-100 text-orange-600 rounded-lg flex items-center justify-center flex-shrink-0 relative">
                        <i class="fa fa-tasks text-xl"></i>
                        <?php if ($tugas_count > 0): ?>
                        <span class="absolute -top-1 -right-1 bg-red-500 text-white text-[10px] font-bold w-5 h-5 rounded-full flex items-center justify-center"><?= $tugas_count ?></span>
                        <?php endif; ?>
                    </div>
                    <div class="flex-grow">
                        <h3 class="font-bold text-slate-800 text-md">Tugas Aktif</h3>
                        <p class="text-xs text-slate-500 mt-1 mb-3">Kirim rekap tugas yang belum dikumpulkan</p>
                        <button type="submit" class="w-full bg-orange-600 hover:bg-orange-700 text-white text-xs font-bold py-2 px-4 rounded-lg transition">
                            <i class="fab fa-telegram mr-1"></i> Kirim
                        </button>
                    </div>
                </div>
            </form>

            <!-- Streak Kuis Harian -->
            <form method="POST" class="bg-white rounded-xl shadow-sm border border-slate-200 p-5 hover:shadow-md transition">
                <input type="hidden" name="action" value="streak">
                <div class="flex items-start gap-4">
                    <div class="w-12 h-12 bg-red-100 text-red-500 rounded-lg flex items-center justify-center flex-shrink-0 relative">
                        <i class="fa fa-fire text-xl"></i>
                        <?php if ($streak_count > 0): ?>
                        <span class="absolute -top-1 -right-1 bg-orange-500 text-white text-[10px] font-bold w-5 h-5 rounded-full flex items-center justify-center"><?= $streak_count ?></span>
                        <?php endif; ?>
                    </div>
                    <div class="flex-grow">
                        <h3 class="font-bold text-slate-800 text-md">Streak Kuis Harian</h3>
                        <p class="text-xs text-slate-500 mt-1 mb-3">Ingatkan pemain yang belum main hari ini</p>
                        <button type="submit" class="w-full bg-red-500 hover:bg-red-600 text-white text-xs font-bold py-2 px-4 rounded-lg transition">
                            <i class="fab fa-telegram mr-1"></i> Kirim
                        </button>
                    </div>
                </div>
            </form>

            <!-- Jadwal -->
            <form method="POST" class="bg-white rounded-xl shadow-sm border border-slate-200 p-5 hover:shadow-md transition">
                <input type="hidden" name="action" value="jadwal">
                <div class="flex items-start gap-4">
                    <div class="w-12 h-12 bg-indigo-100 text-indigo-600 rounded-lg flex items-center justify-center flex-shrink-0">
                        <i class="fa fa-calendar-alt text-xl"></i>
                    </div>
                    <div class="flex-grow">
                        <h3 class="font-bold text-slate-800 text-md">Jadwal Besok</h3>
                        <p class="text-xs text-slate-500 mt-1 mb-3">Ingatkan jadwal kuliah untuk hari besok</p>
                        <button type="submit" class="w-full bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-bold py-2 px-4 rounded-lg transition">
                            <i class="fab fa-telegram mr-1"></i> Kirim
                        </button>
                    </div>
                </div>
            </form>

            <!-- Kas -->
            <form method="POST" class="bg-white rounded-xl shadow-sm border border-slate-200 p-5 hover:shadow-md transition">
                <input type="hidden" name="action" value="kas">
                <div class="flex items-start gap-4">
                    <div class="w-12 h-12 bg-green-100 text-green-600 rounded-lg flex items-center justify-center flex-shrink-0">
                        <i class="fa fa-wallet text-xl"></i>
                    </div>
                    <div class="flex-grow">
                        <h3 class="font-bold text-slate-800 text-md">Kas Mingguan</h3>
                        <p class="text-xs text-slate-500 mt-1 mb-2">Kirim pengingat kas belum lunas</p>
                        <div class="flex gap-2 mb-3">
                            <select name="minggu_ke" class="flex-grow text-xs border border-slate-300 rounded-lg px-3 py-1.5 focus:ring-2 focus:ring-green-500">
                                <?php for ($i = 1; $i <= 16; $i++): ?>
                                <option value="<?= $i ?>">Minggu <?= $i ?></option>
                                <?php endfor; ?>
                            </select>
                        </div>
                        <button type="submit" class="w-full bg-green-600 hover:bg-green-700 text-white text-xs font-bold py-2 px-4 rounded-lg transition">
                            <i class="fab fa-telegram mr-1"></i> Kirim
                        </button>
                    </div>
                </div>
            </form>

            

            

            <!-- Kelompok Reminder -->
            <form method="POST" class="bg-white rounded-xl shadow-sm border border-slate-200 p-5 hover:shadow-md transition">
                <input type="hidden" name="action" value="kelompok">
                <div class="flex items-start gap-4">
                    <div class="w-12 h-12 bg-teal-100 text-teal-600 rounded-lg flex items-center justify-center flex-shrink-0">
                        <i class="fa fa-users text-xl"></i>
                    </div>
                    <div class="flex-grow">
                        <h3 class="font-bold text-slate-800">Daftar Kelompok</h3>
                        <p class="text-xs text-slate-500 mt-1 mb-2">Kirim pembagian kelompok</p>
                        
                        <div class="mb-3">
                            <select name="group_set" required class="w-full text-xs border border-slate-300 rounded-lg px-2 py-1.5 focus:ring-2 focus:ring-teal-500" onchange="updateGroupFields(this)">
                                <option value="">-- Pilih Matkul & Dosen --</option>
                                <?php foreach ($group_sets as $s): ?>
                                    <option value="<?= $s['matkul_id'] ?>|<?= htmlspecialchars($s['dosen']) ?>|<?= htmlspecialchars($s['keterangan']) ?>">
                                        <?= htmlspecialchars($s['nama_matkul']) ?> - <?= htmlspecialchars($s['dosen']) ?> (<?= htmlspecialchars($s['keterangan']) ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <input type="hidden" name="matkul_id" id="field_matkul_id">
                            <input type="hidden" name="dosen" id="field_dosen">
                            <input type="hidden" name="keterangan" id="field_keterangan">
                        </div>

                        <button type="submit" class="w-full bg-teal-600 hover:bg-teal-700 text-white text-xs font-bold py-2 px-4 rounded-lg transition">
                            <i class="fab fa-telegram mr-1"></i> Kirim
                        </button>
                    </div>
                </div>
            </form>

        </div>
<footer class="text-xs text-indigo-900 text-center mt-6">© 2025 Kelas PTIK C - Teknik Informatika dan Komputer FT UNM. All rights reserved.</footer>
    </main>

    

    <script>
// Mobile menu toggle dengan perbaikan
        const mobileMenuButton = document.getElementById('mobile-menu-button');
        const sidebar = document.getElementById('sidebar');

        mobileMenuButton.addEventListener('click', (e) => {
            e.stopPropagation(); // Prevent event bubbling
            sidebar.classList.toggle('-translate-x-full');
            sidebar.classList.toggle('mobile-menu-open');
        });

        // Close sidebar when clicking outside
        document.addEventListener('click', (e) => {
            if (window.innerWidth < 768) { // Only on mobile
                if (!sidebar.contains(e.target) && !mobileMenuButton.contains(e.target)) {
                    sidebar.classList.add('-translate-x-full');
                    sidebar.classList.remove('mobile-menu-open');
                }
            }
        });

        // Submenu toggles
        const submenuButtons = document.querySelectorAll('.submenu-button');

        submenuButtons.forEach(button => {
            button.addEventListener('click', () => {
                const submenu = button.nextElementSibling;
                const arrow = button.querySelector('svg:last-child');

                submenu.classList.toggle('hidden');
                arrow.classList.toggle('rotate-180');
            });
        });

        // Profile dropdown
        const profileButton = document.getElementById('profile-button');
        const profileDropdown = document.getElementById('profile-dropdown');

        if (profileButton && profileDropdown) {
            profileButton.addEventListener('click', (e) => {
                e.stopPropagation();
                profileDropdown.classList.toggle('hidden');
            });

            document.addEventListener('click', (e) => {
                if (!profileButton.contains(e.target)) {
                    profileDropdown.classList.add('hidden');
                }
            });
        }

        function updateGroupFields(select) {
            const val = select.value;
            if (val) {
                const parts = val.split('|');
                document.getElementById('field_matkul_id').value = parts[0];
                document.getElementById('field_dosen').value = parts[1];
                document.getElementById('field_keterangan').value = parts[2];
            } else {
                document.getElementById('field_matkul_id').value = '';
                document.getElementById('field_dosen').value = '';
                document.getElementById('field_keterangan').value = '';
            }
        }

        // Response Message with SweetAlert
        <?php if (isset($_SESSION['reminder_msg'])): ?>
            Swal.fire({
                icon: '<?= $_SESSION['reminder_type'] === "success" ? "success" : ($_SESSION['reminder_type'] === "error" ? "error" : "info") ?>',
                title: '<?= $_SESSION['reminder_type'] === "success" ? "Berhasil!" : ($_SESSION['reminder_type'] === "error" ? "Gagal!" : "Informasi") ?>',
                text: '<?= addslashes($_SESSION['reminder_msg']) ?>',
                confirmButtonColor: '#3730a3'
            });
            <?php 
            unset($_SESSION['reminder_msg']);
            unset($_SESSION['reminder_type']);
            ?>
        <?php endif; ?>
    </script>
</body>
</html>
