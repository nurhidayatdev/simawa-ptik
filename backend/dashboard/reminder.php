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

// Only admin/sekretaris/bendahara can access
if (!in_array($role, ['Administrator Utama', 'Sekretaris', 'Bendahara'])) {
    header("Location: dashboard.php");
    exit;
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

$response_msg = '';
$response_type = '';

// Handle Actions
if (isset($_POST['action'])) {
    $action = $_POST['action'];
    
    // JADWAL REMINDER
    if ($action === 'jadwal') {
        $days_map = ['Sunday' => 'Minggu', 'Monday' => 'Senin', 'Tuesday' => 'Selasa', 'Wednesday' => 'Rabu', 'Thursday' => 'Kamis', 'Friday' => 'Jumat', 'Saturday' => 'Sabtu'];
        $tomorrow_eng = date('l', strtotime('+1 day'));
        $tomorrow_ind = $days_map[$tomorrow_eng];
        
        $q_periode = mysqli_query($koneksi, "SELECT id FROM periode WHERE status = 'aktif' LIMIT 1");
        $periode = mysqli_fetch_assoc($q_periode);
        $periode_id = $periode['id'] ?? 0;
        
        $query = "SELECT jm.jam_mulai, jm.jam_selesai, jm.ruangan, mk.nama_matkul, dp.nama_dosen AS dosen_pengampu
                  FROM jadwal_matkul jm
                  JOIN mata_kuliah mk ON jm.matkul_id = mk.id
                  LEFT JOIN dosen dp ON jm.dosen_pengampu_id = dp.id
                  WHERE jm.periode_id = $periode_id AND jm.hari = '$tomorrow_ind'
                  ORDER BY jm.jam_mulai ASC";
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
}

// Counts for display
$now_date = date('Y-m-d');
$now_time = date('H:i:s');
$tugas_count = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT COUNT(*) as cnt FROM tugas WHERE status='belum' AND (deadline_tanggal > '$now_date' OR (deadline_tanggal = '$now_date' AND deadline_pukul > '$now_time'))"))['cnt'] ?? 0;
$streak_count = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT COUNT(*) as cnt FROM kuis_harian_stats WHERE current_streak > 0 AND (last_played_date IS NULL OR last_played_date != '$now_date' OR DATE(last_completed_at) != '$now_date')"))['cnt'] ?? 0;
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Telegram Reminder - PTIK C</title>
    <link href="../../frontend/tailwind/src/output.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body class="bg-slate-100 min-h-screen">
    
    <!-- Header -->
    <header class="bg-indigo-950 fixed w-full top-0 z-50">
        <div class="flex justify-between items-center px-6 py-2">
            <div class="flex items-center">
                <img src="../../img/logo.png" alt="Logo" class="h-10 w-10 mr-3">
                <span class="text-xl font-bold text-white">Telegram Reminder</span>
            </div>
            <a href="dashboard.php" class="text-white hover:text-slate-300 text-sm">
                <i class="fa fa-arrow-left mr-1"></i> Dashboard
            </a>
        </div>
    </header>

    <main class="pt-20 p-6 max-w-4xl mx-auto">
        
        <?php if ($response_msg): ?>
        <div class="mb-6 p-4 rounded-lg <?= $response_type === 'success' ? 'bg-green-100 border border-green-400 text-green-800' : ($response_type === 'error' ? 'bg-red-100 border border-red-400 text-red-800' : 'bg-blue-100 border border-blue-400 text-blue-800') ?>">
            <i class="fa <?= $response_type === 'success' ? 'fa-check-circle' : ($response_type === 'error' ? 'fa-exclamation-circle' : 'fa-info-circle') ?> mr-2"></i>
            <?= htmlspecialchars($response_msg) ?>
        </div>
        <?php endif; ?>
        
        <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6 mb-6">
            <div class="flex items-center gap-3 mb-4">
                <div class="w-12 h-12 bg-blue-500 rounded-xl flex items-center justify-center">
                    <i class="fab fa-telegram text-white text-2xl"></i>
                </div>
                <div>
                    <h2 class="text-lg font-bold text-slate-800">Kirim Reminder ke Telegram</h2>
                    <p class="text-xs text-slate-500">Pilih jenis reminder yang ingin dikirim ke grup PTIK C</p>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            
            <!-- Jadwal -->
            <form method="POST" class="bg-white rounded-xl shadow-sm border border-slate-200 p-5 hover:shadow-md transition">
                <input type="hidden" name="action" value="jadwal">
                <div class="flex items-start gap-4">
                    <div class="w-12 h-12 bg-indigo-100 text-indigo-600 rounded-lg flex items-center justify-center flex-shrink-0">
                        <i class="fa fa-calendar-alt text-xl"></i>
                    </div>
                    <div class="flex-grow">
                        <h3 class="font-bold text-slate-800">Jadwal Besok</h3>
                        <p class="text-xs text-slate-500 mt-1 mb-3">Kirim jadwal kuliah untuk hari besok</p>
                        <button type="submit" class="w-full bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-bold py-2 px-4 rounded-lg transition">
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
                        <h3 class="font-bold text-slate-800">Tugas Aktif</h3>
                        <p class="text-xs text-slate-500 mt-1 mb-3">Kirim rekap tugas yang belum dikumpulkan</p>
                        <button type="submit" class="w-full bg-orange-600 hover:bg-orange-700 text-white text-sm font-bold py-2 px-4 rounded-lg transition">
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
                        <h3 class="font-bold text-slate-800">Kas Mingguan</h3>
                        <p class="text-xs text-slate-500 mt-1 mb-2">Kirim pengingat kas belum lunas</p>
                        <div class="flex gap-2 mb-3">
                            <select name="minggu_ke" class="flex-grow text-sm border border-slate-300 rounded-lg px-3 py-1.5 focus:ring-2 focus:ring-green-500">
                                <?php for ($i = 1; $i <= 16; $i++): ?>
                                <option value="<?= $i ?>">Minggu <?= $i ?></option>
                                <?php endfor; ?>
                            </select>
                        </div>
                        <button type="submit" class="w-full bg-green-600 hover:bg-green-700 text-white text-sm font-bold py-2 px-4 rounded-lg transition">
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
                        <h3 class="font-bold text-slate-800">Streak Kuis Harian</h3>
                        <p class="text-xs text-slate-500 mt-1 mb-3">Ingatkan pemain yang belum main hari ini</p>
                        <button type="submit" class="w-full bg-red-500 hover:bg-red-600 text-white text-sm font-bold py-2 px-4 rounded-lg transition">
                            <i class="fab fa-telegram mr-1"></i> Kirim
                        </button>
                    </div>
                </div>
            </form>

        </div>

        <div class="mt-6 bg-slate-50 rounded-xl border border-slate-200 p-4">
            <div class="flex items-center gap-2 text-slate-600 text-sm">
                <i class="fa fa-info-circle text-slate-400"></i>
                <span>Reminder akan dikirim ke grup Telegram PTIK C secara langsung.</span>
            </div>
        </div>

    </main>

    <footer class="text-xs text-slate-500 text-center py-4 mt-8">
        © 2025 PTIK C - Teknik Informatika dan Komputer FT UNM
    </footer>

</body>
</html>
