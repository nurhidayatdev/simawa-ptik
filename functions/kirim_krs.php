<?php
// functions/kirim_krs.php
include_once __DIR__ . '/helpers.php';

function kirimKrsReminder($koneksi, $token, $chat_id)
{
    date_default_timezone_set('Asia/Makassar');

    $q_periode = mysqli_query($koneksi, "SELECT id FROM periode WHERE status = 'aktif' LIMIT 1");
    $periode = mysqli_fetch_assoc($q_periode);
    $periode_id = $periode['id'] ?? 0;

    $q_belum = "SELECT m.nama_lengkap 
                FROM mahasiswa m 
                LEFT JOIN (SELECT DISTINCT mahasiswa_id FROM krs WHERE periode_id = $periode_id) k ON m.id = k.mahasiswa_id 
                WHERE k.mahasiswa_id IS NULL 
                ORDER BY m.nim ASC";
    $res_belum = mysqli_query($koneksi, $q_belum);

    if ($res_belum && mysqli_num_rows($res_belum) > 0) {
        $message = "📝 *PENGINGAT PENGISIAN KRS*\n\nMahasiswa berikut *BELUM* mengisi KRS untuk periode aktif:\n\n";
        $no = 1;
        while ($row = mysqli_fetch_assoc($res_belum)) {
            $message .= $no . ". " . $row['nama_lengkap'] . "\n";
            $no++;
        }
        $message .= "\n_Harap segera mengisi KRS di sistem!_ 🚀";

        $res = sendTelegramMessage($token, $chat_id, $message);
        if ($res['success']) {
            return ['status' => 'success', 'message' => "Reminder KRS berhasil dikirim ke " . ($no - 1) . " mahasiswa!"];
        } else {
            return ['status' => 'error', 'message' => "Gagal: " . ($res['error'] ?? '')];
        }
    } else {
        return ['status' => 'info', 'message' => "Semua mahasiswa sudah mengisi KRS."];
    }
}
