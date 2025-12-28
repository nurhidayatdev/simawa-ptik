<?php
session_start();
include '../../koneksi.php';

// Check access
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] !== 'Administrator Utama' && $_SESSION['role'] !== 'Bendahara')) {
    header("Location: ../../index.php");
    exit;
}

$minggu_ke = isset($_GET['minggu_ke']) ? (int)$_GET['minggu_ke'] : 0;

if ($minggu_ke <= 0 || $minggu_ke > 16) {
    $_SESSION['error'] = "Minggu ke tidak valid.";
    header("Location: ../dashboard/db_kas.php");
    exit;
}

// Set timezone to WITA
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

// Get list of students who haven't paid
$q_belum = "SELECT m.nim, m.nama_panggilan
            FROM kas k
            JOIN mahasiswa m ON k.mahasiswa_id = m.id
            WHERE k.minggu_ke = '$minggu_ke' 
            AND (k.status IS NULL OR k.status != 'Lunas')
            ORDER BY m.nim ASC";

$res_belum = mysqli_query($koneksi, $q_belum);

if ($res_belum && mysqli_num_rows($res_belum) > 0) {
    // Build message
    $message = "💰 *PENGINGAT PEMBAYARAN KAS MINGGUAN*\n\n";
    $message .= "📅 *Minggu ke-" . $minggu_ke . "*\n\n";
    $message .= "Berikut daftar mahasiswa yang *BELUM* membayar kas:\n\n";
    
    $no = 1;
    while ($row = mysqli_fetch_assoc($res_belum)) {
        $message .= $no . ". " . $row['nama_panggilan'] . "\n";
        $no++;
    }
    
    $message .= "\n_Segera lakukan pembayaran!_ 🙏";
    $message .= "\n_Update Manual via Dashboard_";
    
    $response = sendTelegramMessage($telegram_token, $telegram_chat_id, $message);
    
    if ($response['success']) {
        $_SESSION['success'] = "Reminder Kas Minggu " . $minggu_ke . " berhasil dikirim via " . $response['method'];
    } else {
        $_SESSION['error'] = "Gagal mengirim reminder: " . $response['error'];
    }
} else {
    $_SESSION['info'] = "Semua mahasiswa sudah membayar lunas untuk Minggu " . $minggu_ke . ".";
}

header("Location: ../dashboard/db_kas.php?minggu_ke=" . $minggu_ke);
exit;
