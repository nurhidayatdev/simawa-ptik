<?php
// backend/crud/kirim_pesan_telegram.php
// File ini digunakan untuk mengirim notifikasi Telegram ketika ada pesan anonim baru

function sendWhatsAppPesan($pesan_isi)
{
    // Configuration handled by helpers.php
    include_once 'helpers.php';

    // Set timezone ke WITA
    date_default_timezone_set('Asia/Makassar');

    // Format pesan
    $message = "✉️ *PESAN ANONIM BARU* ✉️\n\n";
    $message .= "\"" . $pesan_isi . "\"\n\n";
    $message .= "📅 _" . date('d M Y, H:i') . " WITA_";

    // Kirim Ke WhatsApp via Fonnte
    $status = sendFonnteMessage($message, FONNTE_ANON_GROUP_ID);

    return $status['success'];
}
