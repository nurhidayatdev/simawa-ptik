<?php
session_start();
include '../../koneksi.php';

// Check access (Administrator Utama or Sekretaris)
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

// Map English day names to Indonesian
$days_map = [
    'Sunday' => 'Minggu',
    'Monday' => 'Senin',
    'Tuesday' => 'Selasa',
    'Wednesday' => 'Rabu',
    'Thursday' => 'Kamis',
    'Friday' => 'Jumat',
    'Saturday' => 'Sabtu'
];

$tomorrow_eng = date('l', strtotime('+1 day'));
$tomorrow_ind = $days_map[$tomorrow_eng];

/* =====================
   AMBIL PERIODE AKTIF
   ===================== */
$q_periode = mysqli_query($koneksi, "SELECT id FROM periode WHERE status = 'aktif' LIMIT 1");
$periode = mysqli_fetch_assoc($q_periode);
$periode_id = $periode['id'];

// Fetch Schedules for Tomorrow
$query = "SELECT 
            jm.jam_mulai, jm.jam_selesai, jm.ruangan, mk.nama_matkul,
            dp.nama_dosen AS dosen_pengampu
          FROM jadwal_matkul jm
          JOIN mata_kuliah mk ON jm.matkul_id = mk.id
          LEFT JOIN dosen dp ON jm.dosen_pengampu_id = dp.id
          WHERE jm.periode_id = $periode_id 
          AND jm.hari = '$tomorrow_ind'
          ORDER BY jm.jam_mulai ASC";

$result = mysqli_query($koneksi, $query);
$count = mysqli_num_rows($result);

if ($count > 0) {
    $message = "📅 *JADWAL KULIAH BESOK* 📅\n";
    $message .= "Hari: *{$tomorrow_ind}*, " . date('d M Y', strtotime('+1 day')) . "\n\n";
    
    $no = 1;
    while ($row = mysqli_fetch_assoc($result)) {
        $jam_mulai = date('H:i', strtotime($row['jam_mulai']));
        $jam_selesai = date('H:i', strtotime($row['jam_selesai']));
        
        $message .= "{$no}. *{$row['nama_matkul']}*\n";
        $message .= "   ⏰ {$jam_mulai} - {$jam_selesai} WITA\n";
        $message .= "   🏛️ Ruang: {$row['ruangan']}\n";
        $message .= "   👨‍🏫 Dosen: {$row['dosen_pengampu']}\n\n";
        $no++;
    }
    
    $message .= "_Jangan lupa datang tepat waktu!_ 📝\n";
    $message .= "_Update Manual via Dashboard_";
    
    $response = sendTelegramMessage($telegram_token, $telegram_chat_id, $message);
    
    if ($response['success']) {
        $_SESSION['success'] = "Jadwal " . $tomorrow_ind . " berhasil dikirim ke Telegram.";
    } else {
        $_SESSION['error'] = "Gagal mengirim jadwal: " . $response['error'];
    }
} else {
    $_SESSION['info'] = "Tidak ada jadwal kuliah untuk hari " . $tomorrow_ind . " besok.";
}

header("Location: ../dashboard/db_jadwal.php");
exit;
