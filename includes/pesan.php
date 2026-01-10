<?php
if (isset($_POST['kirim_pesan'])) {
    $pesan_raw = $_POST['pesan_anonim'];
    $pesan_db = mysqli_real_escape_string($koneksi, $pesan_raw);
    if (!empty($pesan_raw)) {
        $query_pesan = "INSERT INTO pesan (isi_pesan) VALUES ('$pesan_db')";
        if (mysqli_query($koneksi, $query_pesan)) {
            // Kirim notifikasi ke Telegram
            include_once 'functions/kirim_pesan.php';
            sendTelegramPesan($pesan_raw);
            // Redirect back to the source page to prevent form resubmission
            $redirect_url = $_SERVER['HTTP_REFERER'] ?? 'index.php';

            // Remove existing status parameter to avoid duplication
            $redirect_url = preg_replace('/([?&])status=[^&]*(&|$)/', '$1', $redirect_url);
            $redirect_url = rtrim($redirect_url, '?&');

            // Append success status and footer anchor
            $separator = (strpos($redirect_url, '?') === false) ? '?' : '&';
            $final_url = strtok($redirect_url, '#') . $separator . "status=success#footer_form";

            header("Location: $final_url");
            exit;
        }
    }
}
