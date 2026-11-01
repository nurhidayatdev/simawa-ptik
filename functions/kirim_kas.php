<?php
// functions/kirim_kas.php - Multi-Class WhatsApp Routing
include_once __DIR__ . '/helpers.php';

function kirimKasReminder($koneksi, $minggu_ke, $token = null, $chat_id = null, $kelas_id = null)
{
    date_default_timezone_set('Asia/Makassar');

    $target_kelas_id = !empty($kelas_id) ? (int)$kelas_id : (function_exists('getKelasScope') ? getKelasScope() : 1);
    $nama_kelas = 'PTIK 2024';
    if (!empty($target_kelas_id)) {
        $qk = mysqli_query($koneksi, "SELECT nama_kelas FROM kelas WHERE id = $target_kelas_id LIMIT 1");
        if ($rk = mysqli_fetch_assoc($qk)) {
            $nama_kelas = $rk['nama_kelas'];
        }
    }

    $q_p = mysqli_query($koneksi, "SELECT id FROM periode WHERE status = 'aktif' LIMIT 1");
    $p = mysqli_fetch_assoc($q_p);
    $periode_id = (int)($p['id'] ?? 0);

    $where_m_scope = !empty($target_kelas_id) ? " AND m.kelas_id = $target_kelas_id " : "";

    $q_belum = "SELECT m.nama_lengkap 
                FROM mahasiswa m 
                LEFT JOIN kas k ON k.mahasiswa_id = m.id AND k.minggu_ke = '$minggu_ke' AND k.periode_id = '$periode_id' 
                WHERE (k.status IS NULL OR k.status != 'Lunas') $where_m_scope 
                ORDER BY m.nim ASC";
    $res_belum = mysqli_query($koneksi, $q_belum);

    if ($res_belum && mysqli_num_rows($res_belum) > 0) {
        $message = "💰 *PENGINGAT PEMBAYARAN KAS - {$nama_kelas}*\n📅 *Minggu ke-{$minggu_ke}*\n\nBelum bayar:\n\n";
        $no = 1;
        while ($row = mysqli_fetch_assoc($res_belum)) {
            $message .= $no . ". " . $row['nama_lengkap'] . "\n";
            $no++;
        }
        $message .= "\n_Segera lakukan pembayaran kas ke Bendahara!_ 🙏";

        $res = sendFonnteMessage($message, null, null, null, $target_kelas_id);
        if ($res['success']) {
            return ['status' => 'success', 'message' => "Reminder kas minggu $minggu_ke berhasil dikirim ke WhatsApp {$nama_kelas}!"];
        } else {
            return ['status' => 'error', 'message' => "Gagal: " . ($res['error'] ?? '')];
        }
    } else {
        return ['status' => 'info', 'message' => "Semua mahasiswa {$nama_kelas} sudah lunas untuk minggu $minggu_ke."];
    }
}

// Standalone execution
if (basename($_SERVER['PHP_SELF']) == 'kirim_kas.php') {
    session_start();
    include '../koneksi.php';

    if (!isset($_SESSION['user_id']) || (!in_array($_SESSION['role'], ['Administrator Utama', 'Bendahara', 'Ketua Tingkat']))) {
        header("Location: ../../index");
        exit;
    }

    $minggu_ke = isset($_GET['minggu_ke']) ? (int)$_GET['minggu_ke'] : 1;

    $res = kirimKasReminder($koneksi, $minggu_ke);
    $_SESSION[$res['status']] = $res['message'];

    $redirect = isset($_GET['redirect']) ? $_GET['redirect'] : 'kas';
    header("Location: ../views/$redirect?minggu_ke=$minggu_ke");
    exit;
}
