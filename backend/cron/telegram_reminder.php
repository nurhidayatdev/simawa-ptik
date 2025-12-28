<?php
/**
 * Telegram Task Reminder - H-1 Deadline
 * 
 * Script ini mengirim reminder otomatis ke Telegram untuk tugas yang deadlinenya besok (H-1)
 * Jalankan script ini setiap hari menggunakan Windows Task Scheduler atau cron job
 * 
 * Cara menjalankan manual: php telegram_reminder.php
 * Atau akses via browser: http://localhost/ptikc24/backend/cron/telegram_reminder.php
 */

// Include koneksi database
require_once '../../koneksi.php';

// Set timezone to WITA (Waktu Indonesia Tengah - UTC+8)
date_default_timezone_set('Asia/Makassar');

// Configuration
$telegram_token = "8212322367:AAGCGc4TG__EnsEd-20wFTI_s2adp-dp1OY";
$telegram_chat_id = "-1003577478223";

// Get current date and tomorrow's date
$today = date('Y-m-d');
$tomorrow = date('Y-m-d', strtotime('+1 day'));

// Log file untuk tracking
$log_file = __DIR__ . '/telegram_reminder.log';
$last_run_file = __DIR__ . '/last_run.txt';

// Cek apakah sudah pernah kirim hari ini (Mencegah double kirim saat refresh)
// Dukung bypass via ?force=true (Web), --force (CLI), atau variabel $force_include
$force_web = isset($_GET['force']);
$force_cli = isset($argv) && in_array('--force', $argv);
$force_manual = isset($force_include) && $force_include === true;

if (file_exists($last_run_file) && !($force_web || $force_cli || $force_manual)) {
    $last_run = trim(file_get_contents($last_run_file));
    if ($last_run === $today) {
        $msg = "Info: Reminder sudah dikirim hari ini ({$today}).";
        if (basename($_SERVER['PHP_SELF']) == 'telegram_reminder.php') {
            die($msg);
        } else {
            return; // Exit silently if included
        }
    }
}

/**
 * Resilient Telegram Message Sender
 * Optimized for InfinityFree (DNS Bypass & Fallback)
 */
function sendTelegramMessage($token, $chat_id, $message) {
    $tg_ip = "149.154.167.220"; // api.telegram.org IP
    $url = "https://{$tg_ip}/bot{$token}/sendMessage";
    $data = [
        'chat_id' => $chat_id,
        'text' => $message,
        'parse_mode' => 'Markdown'
    ];
    
    // Method 1: CURL (Preferred with DNS Bypass)
    if (function_exists('curl_init')) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 15);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_USERAGENT, 'PTIKC24_Bot/1.0');
        // DNS Bypass Header
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Host: api.telegram.org']);
        
        $result = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($http_code == 200) {
            return ['success' => true, 'method' => 'CURL_IP'];
        }
    }
    
    // Method 2: file_get_contents (Fallback with DNS Bypass)
    if (ini_get('allow_url_fopen')) {
        $options = [
            'http' => [
                'method'  => 'POST',
                'header'  => "Content-Type: application/x-www-form-urlencoded\r\n" .
                             "Host: api.telegram.org\r\n" .
                             "User-Agent: PTIKC24_Bot/1.0\r\n",
                'content' => http_build_query($data),
                'timeout' => 15
            ],
            'ssl' => [
                'verify_peer' => false,
                'verify_peer_name' => false,
            ]
        ];
        $context = stream_context_create($options);
        $result = @file_get_contents($url, false, $context);
        
        if ($result !== FALSE) {
            return ['success' => true, 'method' => 'FGC_IP'];
        }
    }
    
    return ['success' => false, 'error' => 'All DNS Bypass methods failed'];
}

// Function to log messages
function logMessage($message) {
    global $log_file;
    $timestamp = date('Y-m-d H:i:s');
    @file_put_contents($log_file, "[{$timestamp}] {$message}\n", FILE_APPEND);
}

// Start logging
logMessage("=== Starting Telegram Reminder Check ===");

// Query untuk mendapatkan tugas yang deadline-nya besok
$query = "SELECT 
            t.id,
            t.deadline_tanggal,
            t.deadline_pukul,
            t.deskripsi,
            t.link_kirim,
            mk.nama_matkul
          FROM tugas t
          JOIN mata_kuliah mk ON t.matkul_id = mk.id
          WHERE t.deadline_tanggal = '$tomorrow'
          ORDER BY t.deadline_pukul ASC";

$result = mysqli_query($koneksi, $query);
$task_count = mysqli_num_rows($result);

if ($task_count > 0) {
    // Build message
    $message = "⏰ *REMINDER: DEADLINE TUGAS BESOK!* ⏰\n\n";
    $message .= "📅 *Tanggal: " . date('d/m/Y', strtotime($tomorrow)) . "*\n\n";
    $message .= "Berikut tugas yang harus dikumpulkan *BESOK*:\n\n";
    
    $no = 1;
    while ($row = mysqli_fetch_assoc($result)) {
        $message .= "*{$no}. {$row['nama_matkul']}*\n";
        $message .= "   ⏰ Deadline: " . date('H:i', strtotime($row['deadline_pukul'])) . " WITA\n";
        
        if (!empty($row['deskripsi'])) {
            $message .= "   📝 " . $row['deskripsi'] . "\n";
        }
        
        if (!empty($row['link_kirim'])) {
            $message .= "   🔗 Link: " . $row['link_kirim'] . "\n";
        }
        
        $message .= "\n";
        $no++;
    }
    
    $message .= "_Jangan lupa kerjakan dan kumpulkan tepat waktu!_ 🚀\n";
    $message .= "_H-1 sebelum deadline_ ⚠️";
    
    // Send to Telegram
    $response = sendTelegramMessage($telegram_token, $telegram_chat_id, $message);
    
    if ($response['success']) {
        logMessage("SUCCESS: Message sent via " . $response['method']);
        file_put_contents($last_run_file, $today);
        echo "✅ Reminder sent successfully!";
    } else {
        logMessage("ERROR: " . $response['error']);
        echo "❌ Failed to send reminder: " . $response['error'];
    }
} else {
    logMessage("INFO: No tasks found with deadline tomorrow");
    echo "ℹ️ No tasks with deadline tomorrow";
}

logMessage("=== Telegram Reminder Check Completed ===\n");

// Close database connection ONLY if not included
if (basename($_SERVER['PHP_SELF']) == 'telegram_reminder.php') {
    mysqli_close($koneksi);
}
?>

