<?php
// functions/helpers.php

// functions/helpers.php

// Fonnte Configuration
if (!defined('FONNTE_TOKEN')) {
    define('FONNTE_TOKEN', 'u1EPmvTn73JuHRGeKyE6');
}
if (!defined('FONNTE_GROUP_ID')) {
    define('FONNTE_GROUP_ID', '120363425662585531@g.us');
}
if (!defined('FONNTE_ANON_GROUP_ID')) {
    define('FONNTE_ANON_GROUP_ID', '120363407814831709@g.us'); // Ganti dengan ID grup khusus pesan anonim
}

if (!function_exists('sendFonnteMessage')) {
    function sendFonnteMessage($message, $target = FONNTE_GROUP_ID)
    {
        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => 'https://api.fonnte.com/send',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => array(
                'target' => $target,
                'message' => $message,
                'countryCode' => '62', // Optional
            ),
            CURLOPT_HTTPHEADER => array(
                'Authorization: ' . FONNTE_TOKEN
            ),
        ));

        $response_raw = curl_exec($curl);
        $http_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        curl_close($curl);

        if ($http_code == 200) {
            return ['success' => true, 'response' => json_decode($response_raw, true)];
        } else {
            return ['success' => false, 'error' => 'Fonnte API Error: ' . $response_raw, 'code' => $http_code];
        }
    }
}

// Deprecated: Replaced by sendFonnteMessage
if (!function_exists('sendTelegramMessage')) {
    function sendTelegramMessage($token, $chat_id, $message)
    {
        // For compatibility, we redirect to WhatsApp via Fonnte
        // We ignore token and chat_id and use the global Fonnte config
        return sendFonnteMessage($message);
    }
}
