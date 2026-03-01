<?php
// functions/kirim_streak.php
include_once __DIR__ . '/helpers.php';

function kirimStreakReminder($koneksi, $token = null, $chat_id = null)
{
    date_default_timezone_set('Asia/Makassar');
    $today = date('Y-m-d');
    $yesterday = date('Y-m-d', strtotime('-1 day'));

    // Get users who have streak > 0, haven't completed today, and streak is still protectable (played today or yesterday)
    $query = "SELECT m.nama_panggilan, s.current_streak
              FROM kuis_harian_stats s 
              JOIN mahasiswa m ON s.user_id = m.user_id 
              WHERE s.current_streak > 0 
              AND (s.last_played_date = '$today' OR s.last_played_date = '$yesterday')
              AND (s.last_completed_at IS NULL OR DATE(s.last_completed_at) != '$today')
              ORDER BY s.current_streak DESC";
    $result = mysqli_query($koneksi, $query);

    if ($result && mysqli_num_rows($result) > 0) {
        $message = "🔥 *REMINDER KUIS HARIAN* 🔥\n\nStreakmu akan hilang jika tidak main hari ini!\n\n";
        $no = 1;
        while ($row = mysqli_fetch_assoc($result)) {
            $message .= $no . ". " . $row['nama_panggilan'] . " (🔥" . $row['current_streak'] . ")\n";
            $no++;
        }

        // Fetch Today's Question
        $q_soal = "SELECT pertanyaan FROM kuis_harian_soal WHERE tanggal = '$today' LIMIT 1";
        $r_soal = mysqli_query($koneksi, $q_soal);
        if ($r_soal && mysqli_num_rows($r_soal) > 0) {
            $soal_row = mysqli_fetch_assoc($r_soal);
            $pertanyaan = $soal_row['pertanyaan'];
            $message .= "\n📝 *SOAL HARI INI:*\n_" . $pertanyaan . "_\n";
        }

        $message .= "\n_Main sekarang untuk jaga streak!_ 🎮";

        $res = sendFonnteMessage($message);
        if ($res['success']) {
            return ['status' => 'success', 'message' => "Reminder streak berhasil dikirim ke WhatsApp untuk " . ($no - 1) . " pemain!"];
        } else {
            return ['status' => 'error', 'message' => "Gagal: " . ($res['error'] ?? '')];
        }
    } else {
        if (!$result) {
            return ['status' => 'error', 'message' => "Error: " . mysqli_error($koneksi)];
        } else {
            return ['status' => 'info', 'message' => "Semua pemain sudah main hari ini atau tidak ada streak aktif yang perlu dijaga."];
        }
    }
}
