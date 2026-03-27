<?php
if (isset($_POST['kirim_pesan'])) {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    // Batasi 5 menit sekali (300 detik)
    if (isset($_SESSION['last_send'])) {
        $selisih = time() - $_SESSION['last_send'];
        if ($selisih < 300) {
            $remaining = ceil((300 - $selisih) / 60);
            $redirect_url = $_SERVER['HTTP_REFERER'] ?? 'index.php';

            // Bersihkan parameter status dan wait sebelumnya
            $redirect_url = preg_replace('/([?&])status=[^&]*/', '', $redirect_url);
            $redirect_url = preg_replace('/([?&])wait=[^&]*/', '', $redirect_url);
            $redirect_url = rtrim($redirect_url, '?&');

            $separator = (strpos($redirect_url, '?') === false) ? '?' : '&';
            $final_url = strtok($redirect_url, '#') . $separator . "status=wait&wait=$remaining#footer_form";

            header("Location: $final_url");
            exit;
        }
    }

    $pesan_raw = trim($_POST['pesan_anonim']);
    $pesan_clean = strip_tags($pesan_raw);

    if (!empty($pesan_clean)) {
        // Use Prepared Statements for security and proper character handling
        $stmt = $koneksi->prepare("INSERT INTO pesan (isi_pesan) VALUES (?)");
        $stmt->bind_param("s", $pesan_clean);

        if ($stmt->execute()) {
            // Kirim notifikasi ke WhatsApp
            include_once 'functions/kirim_pesan.php';
            sendWhatsAppPesan($pesan_clean);

            // Redirect back with success
            $redirect_url = $_SERVER['HTTP_REFERER'] ?? 'index.php';

            // Bersihkan parameter sebelumnya
            $redirect_url = preg_replace('/([?&])status=[^&]*/', '', $redirect_url);
            $redirect_url = preg_replace('/([?&])wait=[^&]*/', '', $redirect_url);
            $redirect_url = rtrim($redirect_url, '?&');

            $separator = (strpos($redirect_url, '?') === false) ? '?' : '&';
            $final_url = strtok($redirect_url, '#') . $separator . "status=success#footer_form";

            $_SESSION['last_send'] = time(); // Simpan waktu terakhir kirim
            $stmt->close();
            header("Location: $final_url");
            exit;
        }
        $stmt->close();
    }
}
?>
