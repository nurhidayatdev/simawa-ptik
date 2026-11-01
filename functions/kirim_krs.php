<?php
// functions/kirim_krs.php - Multi-Class WhatsApp Routing
include_once __DIR__ . '/helpers.php';

function kirimKrsReminder($koneksi, $token = null, $chat_id = null, $kelas_id = null)
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

    $q_periode = mysqli_query($koneksi, "SELECT id FROM periode WHERE status = 'aktif' LIMIT 1");
    $periode = mysqli_fetch_assoc($q_periode);
    $periode_id = $periode['id'] ?? 0;

    $where_m_scope = !empty($target_kelas_id) ? " AND m.kelas_id = $target_kelas_id " : "";

    $q_belum = "SELECT m.nama_lengkap 
                FROM mahasiswa m 
                LEFT JOIN (SELECT DISTINCT mahasiswa_id FROM krs WHERE periode_id = $periode_id) k ON m.id = k.mahasiswa_id 
                WHERE k.mahasiswa_id IS NULL $where_m_scope
                ORDER BY m.nim ASC";
    $res_belum = mysqli_query($koneksi, $q_belum);

    if ($res_belum && mysqli_num_rows($res_belum) > 0) {
        $message = "📝 *PENGINGAT PENGISIAN KRS - {$nama_kelas}*\n\nMahasiswa berikut *BELUM* mengisi KRS untuk periode aktif:\n\n";
        $no = 1;
        while ($row = mysqli_fetch_assoc($res_belum)) {
            $message .= $no . ". " . $row['nama_lengkap'] . "\n";
            $no++;
        }
        $message .= "\n_Harap segera mengisi KRS di sistem SIMAWA!_ 🚀";

        $res = sendFonnteMessage($message, null, null, null, $target_kelas_id);
        if ($res['success']) {
            return ['status' => 'success', 'message' => "Reminder KRS berhasil dikirim ke WhatsApp {$nama_kelas} untuk " . ($no - 1) . " mahasiswa!"];
        } else {
            return ['status' => 'error', 'message' => "Gagal: " . ($res['error'] ?? '')];
        }
    } else {
        return ['status' => 'info', 'message' => "Semua mahasiswa {$nama_kelas} sudah mengisi KRS."];
    }
}
