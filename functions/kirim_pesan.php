<?php
// backend/crud/kirim_pesan_telegram.php
// File ini digunakan untuk mengirim notifikasi Telegram ketika ada pesan anonim baru

function sendTelegramPesan($pesan_isi) {
    // Configuration
    // Menggunakan token dan chat_id yang sama dengan fitur reminder lainnya
    $telegram_token = "8212322367:AAGCGc4TG__EnsEd-20wFTI_s2adp-dp1OY";
    $telegram_chat_id = "-1003563899034";

    // Set timezone ke WITA
    date_default_timezone_set('Asia/Makassar');

    // Format pesan
    $message = "✉️ *PESAN ANONIM BARU* ✉️\n\n";
    $message .= "\"" . $pesan_isi . "\"\n\n";
    $message .= "📅 _" . date('d M Y, H:i') . " WITA_\n";

    // Kirim ke Telegram (Resilient DNS Bypass Method)
    $tg_ip = "149.154.167.220";
    $telegram_url = "https://{$tg_ip}/bot{$telegram_token}/sendMessage";
    $telegram_data = [
        'chat_id' => $telegram_chat_id,
        'text' => $message,
        'parse_mode' => 'Markdown'
    ];

    // Try Method 1: CURL (DNS Bypass)
    $sent = false;
    if (function_exists('curl_init')) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $telegram_url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($telegram_data));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
        curl_setopt($ch, CURLOPT_TIMEOUT, 20);
        curl_setopt($ch, CURLOPT_USERAGENT, 'PTIKC24_Bot/1.0');
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Host: api.telegram.org']); // DNS Bypass
        $res = curl_exec($ch);
        $info = curl_getinfo($ch);
        curl_close($ch);
        if ($info['http_code'] == 200) $sent = true;
    }

    // Try Method 2: FGC (Fallback DNS Bypass)
    if (!$sent && ini_get('allow_url_fopen')) {
        $opts = [
            'http' => [
                'method' => 'POST',
                'header' => "Content-Type: application/x-www-form-urlencoded\r\n" .
                    "Host: api.telegram.org\r\n" .
                    "User-Agent: PTIKC24_Bot/1.0\r\n",
                'content' => http_build_query($telegram_data),
                'timeout' => 15
            ],
            'ssl' => ['verify_peer' => false, 'verify_peer_name' => false]
        ];
        $ctx = stream_context_create($opts);
        $res_fgc = @file_get_contents($telegram_url, false, $ctx);
        if ($res_fgc !== FALSE) $sent = true;
    }
    
    return $sent;
}
?>
