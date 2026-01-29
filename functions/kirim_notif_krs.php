<?php
// functions/kirim_notif_krs.php
include_once __DIR__ . '/helpers.php';

if (!function_exists('notifMintaValidasiKrs')) {
    function notifMintaValidasiKrs($nama_mahasiswa, $nim)
    {
        $message = "📑 *PERMINTAAN VALIDASI KRS*\n\n";
        $message .= "Mahasiswa berikut telah mengisi/memperbarui KRS dan meminta validasi:\n\n";
        $message .= "👤 *Nama:* " . $nama_mahasiswa . "\n";
        $message .= "🆔 *NIM:* " . $nim . "\n\n";
        $message .= "_Silakan admin segera melakukan pengecekan di sistem._ 🖥️";

        // We use the default target (Group) or we could specify a personal number here if we had one.
        return sendFonnteMessage($message);
    }
}
