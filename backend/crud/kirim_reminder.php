<?php
session_start();
include '../../koneksi.php';

// Check access
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] !== 'Administrator Utama' && $_SESSION['role'] !== 'Sekretaris')) {
    header("Location: ../../index.php");
    exit;
}

// Set timezone to WITA (Waktu Indonesia Tengah - UTC+8)
date_default_timezone_set('Asia/Makassar');

// Configuration
$telegram_token = "8212322367:AAGCGc4TG__EnsEd-20wFTI_s2adp-dp1OY";
$telegram_chat_id = "-1003577478223";

function sendTelegramMessage($token, $chat_id, $message) {
    $tg_ip = "149.154.167.220"; // api.telegram.org direct IP
    $url_ip = "https://{$tg_ip}/bot{$token}/sendMessage";
    $url_domain = "https://api.telegram.org/bot{$token}/sendMessage";
    
    $data = [
        'chat_id' => $chat_id,
        'text' => $message,
        'parse_mode' => 'Markdown',
        'disable_web_page_preview' => true
    ];
    $post_data = http_build_query($data);

    // Method 1: CURL with IP Bypass
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
        
        if ($http_code == 200) {
            curl_close($ch);
            return ['success' => true, 'method' => 'CURL_IP'];
        }

        // Fallback to Domain
        curl_setopt($ch, CURLOPT_URL, $url_domain);
        curl_setopt($ch, CURLOPT_HTTPHEADER, []);
        $result = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($http_code == 200) {
            return ['success' => true, 'method' => 'CURL_DOMAIN'];
        }
    }
    
    // Method 2: file_get_contents fallback
    if (ini_get('allow_url_fopen')) {
        $options = [
            'http' => [
                'method'  => 'POST',
                'header'  => "Content-Type: application/x-www-form-urlencoded\r\n" .
                             "Host: api.telegram.org\r\n",
                'content' => $post_data,
                'timeout' => 15
            ],
            'ssl' => [
                'verify_peer' => false,
                'verify_peer_name' => false,
            ]
        ];
        $context = stream_context_create($options);
        $result = @file_get_contents($url_ip, false, $context);
        
        if ($result !== FALSE) {
            return ['success' => true, 'method' => 'FGC_IP'];
        }
    }
    
    return ['success' => false, 'error' => 'All connection methods failed'];
}

// Fetch Active Tasks
$now_date = date('Y-m-d');
$now_time = date('H:i:s');
$query = "SELECT 
            t.id,
            t.deadline_tanggal,
            t.deadline_pukul,
            t.deskripsi,
            t.link_kirim,
            mk.nama_matkul
          FROM tugas t
          JOIN mata_kuliah mk ON t.matkul_id = mk.id
          WHERE t.status = 'belum' 
          AND (t.deadline_tanggal > '$now_date' OR (t.deadline_tanggal = '$now_date' AND t.deadline_pukul > '$now_time'))
          ORDER BY t.deadline_tanggal ASC, t.deadline_pukul ASC";

$result = mysqli_query($koneksi, $query);
$task_count = mysqli_num_rows($result);

if ($task_count > 0) {
    $message = "🔔 *REKAP TUGAS AKTIF PTIK C* 🔔\n\n";
    $message .= "Berikut adalah daftar tugas yang *belum dikumpulkan*:\n\n";
    
    $no = 1;
    while ($row = mysqli_fetch_assoc($result)) {
        $status_time = "";
        if ($row['deadline_tanggal'] == $now_date) {
            $status_time = "⚠️ *HARI INI*";
        } elseif ($row['deadline_tanggal'] == date('Y-m-d', strtotime('+1 day'))) {
            $status_time = "⏰ *BESOK*";
        } else {
            $status_time = "📅 " . date('d M Y', strtotime($row['deadline_tanggal']));
        }

        $message .= "*{$no}. {$row['nama_matkul']}*\n";
        $message .= "   {$status_time} | " . date('H:i', strtotime($row['deadline_pukul'])) . " WITA\n";
        
        if (!empty($row['deskripsi'])) {
            $message .= "   📝 " . $row['deskripsi'] . "\n";
        }
        
        if (!empty($row['link_kirim'])) {
            $message .= "   🔗 Link: [Klik di Sini](" . $row['link_kirim'] . ")\n";
        }
        
        $message .= "\n";
        $no++;
    }
    
    $message .= "_Segera kerjakan dan kumpulkan sebelum deadline!_ 🚀\n";
    $message .= "_Update Manual via Dashboard_";
    
    $response = sendTelegramMessage($telegram_token, $telegram_chat_id, $message);
    
    if ($response['success']) {
        $_SESSION['success'] = "Reminder Telegram berhasil dikirim via " . $response['method'];
    } else {
        $_SESSION['error'] = "Gagal mengirim reminder: " . $response['error'];
    }
} else {
    $_SESSION['info'] = "Tidak ada tugas aktif yang perlu diingatkan.";
}

header("Location: ../dashboard/db_tugas.php");
exit;
