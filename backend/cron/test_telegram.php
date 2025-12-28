<?php
/**
 * Telegram Debug Tool for InfinityFree
 * Run this script via browser to see detailed error info
 */
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Telegram Debug PTIK C</h1>";

$token = "8212322367:AAGCGc4TG__EnsEd-20wFTI_s2adp-dp1OY";
$chat_id = "-1003577478223";
$message = "Test Debug from InfinityFree at " . date('Y-m-d H:i:s');

echo "<h2>Method 1: CURL (DNS Bypass)</h2>";
$tg_ip = "149.154.167.220"; // api.telegram.org IP
$url_ip = "https://{$tg_ip}/bot{$token}/sendMessage";
$data = ['chat_id' => $chat_id, 'text' => $message, 'parse_mode' => 'Markdown'];

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url_ip);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 15);
curl_setopt($ch, CURLOPT_TIMEOUT, 30);
// Crucial: Pass the Host header so Telegram knows which domain we want
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Host: api.telegram.org'
]);

$result = curl_exec($ch);
$info = curl_getinfo($ch);
$error = curl_error($ch);
$errno = curl_errno($ch);
curl_close($ch);

echo "<pre>";
echo "HTTP Code: " . $info['http_code'] . "\n";
echo "CURL Error ($errno): " . $error . "\n";
echo "Result: " . $result . "\n";
echo "</pre>";

echo "<h2>Method 2: file_get_contents (DNS Bypass)</h2>";
$options = [
    'http' => [
        'method'  => 'POST',
        'header'  => "Content-Type: application/x-www-form-urlencoded\r\n" .
                     "Host: api.telegram.org\r\n", // Explicit Host Header
        'content' => http_build_query($data),
        'ignore_errors' => true,
        'timeout' => 15
    ],
    'ssl' => [
        'verify_peer' => false,
        'verify_peer_name' => false,
    ]
];
$context  = stream_context_create($options);
// Using IP in URL
$result_fgc = @file_get_contents($url_ip, false, $context);

echo "<pre>";
if ($result_fgc === FALSE) {
    $e = error_get_last();
    echo "FGC Error: " . ($e['message'] ?? 'Unknown error') . "\n";
} else {
    echo "FGC Result: " . $result_fgc . "\n";
}
echo "</pre>";

echo "<h2>PHP Info Snippet</h2>";
echo "CURL enabled: " . (function_exists('curl_version') ? 'Yes' : 'No') . "<br>";
echo "OpenSSL enabled: " . (extension_loaded('openssl') ? 'Yes' : 'No') . "<br>";
echo "Allow URL Fopen: " . (ini_get('allow_url_fopen') ? 'Yes' : 'No') . "<br>";
?>
