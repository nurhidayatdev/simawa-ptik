<?php
// functions/kirim_kas.php
include_once __DIR__ . '/helpers.php';

function kirimKasReminder($koneksi, $minggu_ke, $token = null, $chat_id = null)
{
    date_default_timezone_set('Asia/Makassar');

    $q_belum = "SELECT m.nama_panggilan FROM kas k JOIN mahasiswa m ON k.mahasiswa_id = m.id WHERE k.minggu_ke = '$minggu_ke' AND (k.status IS NULL OR k.status != 'Lunas') ORDER BY m.nim ASC";
    $res_belum = mysqli_query($koneksi, $q_belum);

    if ($res_belum && mysqli_num_rows($res_belum) > 0) {
        $message = "💰 *PENGINGAT PEMBAYARAN KAS MINGGUAN*\n📅 *Minggu ke-{$minggu_ke}*\n\nBelum bayar:\n\n";
        $no = 1;
        while ($row = mysqli_fetch_assoc($res_belum)) {
            $message .= $no . ". " . $row['nama_panggilan'] . "\n";
            $no++;
        }
        $message .= "\n_Segera lakukan pembayaran!_ 🙏";

        $res = sendFonnteMessage($message);
        if ($res['success']) {
            return ['status' => 'success', 'message' => "Reminder kas minggu $minggu_ke berhasil dikirim ke WhatsApp!"];
        } else {
            return ['status' => 'error', 'message' => "Gagal: " . ($res['error'] ?? '')];
        }
    } else {
        return ['status' => 'info', 'message' => "Semua sudah lunas untuk minggu $minggu_ke."];
    }
}

// Standalone execution
if (basename($_SERVER['PHP_SELF']) == 'kirim_kas.php') {
    session_start();
    include '../koneksi.php';

    if (!isset($_SESSION['user_id']) || ($_SESSION['role'] !== 'Administrator Utama' && $_SESSION['role'] !== 'Bendahara')) {
        header("Location: ../../index");
        exit;
    }

    $minggu_ke = isset($_GET['minggu_ke']) ? (int)$_GET['minggu_ke'] : 1;

    $res = kirimKasReminder($koneksi, $minggu_ke);
    $_SESSION[$res['status']] = $res['message'];

    header("Location: ../views/kas?minggu_ke=$minggu_ke");
    exit;
}
