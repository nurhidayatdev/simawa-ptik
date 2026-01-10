<?php
// functions/helpers.php

if (!function_exists('sendTelegramMessage')) {
    function sendTelegramMessage($token, $chat_id, $message)
    {
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

        $sent = false;
        $response = ['success' => false, 'error' => 'All connection methods failed'];

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
                $response = ['success' => true, 'method' => 'CURL_IP'];
                $sent = true;
            } else {
                // Fallback to Domain
                curl_setopt($ch, CURLOPT_URL, $url_domain);
                curl_setopt($ch, CURLOPT_HTTPHEADER, []);
                $result = curl_exec($ch);
                $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                curl_close($ch);

                if ($http_code == 200) {
                    $response = ['success' => true, 'method' => 'CURL_DOMAIN'];
                    $sent = true;
                }
            }
        }

        // Method 2: file_get_contents fallback
        if (!$sent && ini_get('allow_url_fopen')) {
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
                $response = ['success' => true, 'method' => 'FGC_IP'];
                $sent = true;
            }
        }

        return $sent ? $response : ['success' => false, 'error' => 'Failed to send message'];
    }
}
