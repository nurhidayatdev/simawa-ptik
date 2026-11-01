<?php
// functions/kirim_tugas.php - Multi-Class WhatsApp Routing
include_once __DIR__ . '/helpers.php';

function kirimTugasReminder($koneksi, $token = null, $chat_id = null, $kelas_id = null)
{
    date_default_timezone_set('Asia/Makassar');
    $now_date = date('Y-m-d');
    $now_time = date('H:i:s');

    $target_kelas_id = !empty($kelas_id) ? (int)$kelas_id : (function_exists('getKelasScope') ? getKelasScope() : 1);
    $where_kelas = !empty($target_kelas_id) ? " AND t.kelas_id = $target_kelas_id " : "";

    $nama_kelas = 'PTIK 2024';
    if (!empty($target_kelas_id)) {
        $qk = mysqli_query($koneksi, "SELECT nama_kelas FROM kelas WHERE id = $target_kelas_id LIMIT 1");
        if ($rk = mysqli_fetch_assoc($qk)) {
            $nama_kelas = $rk['nama_kelas'];
        }
    }

    $query = "SELECT t.deadline_tanggal, t.deadline_pukul, t.deskripsi, t.link_kirim, mk.nama_matkul
              FROM tugas t JOIN mata_kuliah mk ON t.matkul_id = mk.id
              WHERE t.status = 'belum' AND (t.deadline_tanggal > '$now_date' OR (t.deadline_tanggal = '$now_date' AND t.deadline_pukul > '$now_time'))
              $where_kelas
              ORDER BY t.deadline_tanggal ASC, t.deadline_pukul ASC";
    $result = mysqli_query($koneksi, $query);

    $days_map = ['Sunday' => 'Minggu', 'Monday' => 'Senin', 'Tuesday' => 'Selasa', 'Wednesday' => 'Rabu', 'Thursday' => 'Kamis', 'Friday' => 'Jumat', 'Saturday' => 'Sabtu'];

    if ($result && mysqli_num_rows($result) > 0) {
        $message = "🔔 *REKAP TUGAS AKTIF - {$nama_kelas}* 🔔\n\nDaftar tugas *belum dikumpulkan*:\n\n";
        $no = 1;
        while ($row = mysqli_fetch_assoc($result)) {
            $day_ind = $days_map[date('l', strtotime($row['deadline_tanggal']))];
            $status_time = $row['deadline_tanggal'] == $now_date ? "⚠️ *HARI INI ($day_ind)*" : ($row['deadline_tanggal'] == date('Y-m-d', strtotime('+1 day')) ? "⏰ *BESOK ($day_ind)*" : "📅 " . $day_ind . ", " . date('d M Y', strtotime($row['deadline_tanggal'])));
            $message .= "*{$no}. {$row['nama_matkul']}*\n   {$status_time} | " . date('H:i', strtotime($row['deadline_pukul'])) . " WITA\n";
            if (!empty($row['deskripsi'])) $message .= "   📝 *Deskripsi:*\n {$row['deskripsi']}\n";
            if (!empty($row['link_kirim'])) $message .= "   🔗 Link: {$row['link_kirim']}\n";
            $message .= "\n";
            $no++;
        }
        $message .= "_Segera kerjakan!_ 🚀";

        $res = sendFonnteMessage($message, null, null, null, $target_kelas_id);
        if ($res['success']) {
            return ['status' => 'success', 'message' => "Reminder tugas berhasil dikirim ke WhatsApp {$nama_kelas}!"];
        } else {
            return ['status' => 'error', 'message' => "Gagal: " . ($res['error'] ?? '')];
        }
    } else {
        return ['status' => 'info', 'message' => "Tidak ada tugas aktif untuk {$nama_kelas}."];
    }
}

function notifyNewTask($token, $chat_id, $nama_matkul, $deadline_tanggal, $deadline_pukul, $deskripsi, $link_kirim, $kelas_id = null)
{
    global $koneksi;
    $target_kelas_id = !empty($kelas_id) ? (int)$kelas_id : (function_exists('getKelasScope') ? getKelasScope() : 1);
    
    $nama_kelas = '';
    if (!empty($target_kelas_id) && isset($koneksi)) {
        $qk = mysqli_query($koneksi, "SELECT nama_kelas FROM kelas WHERE id = $target_kelas_id LIMIT 1");
        if ($rk = mysqli_fetch_assoc($qk)) {
            $nama_kelas = $rk['nama_kelas'];
        }
    }

    $days_map = ['Sunday' => 'Minggu', 'Monday' => 'Senin', 'Tuesday' => 'Selasa', 'Wednesday' => 'Rabu', 'Thursday' => 'Kamis', 'Friday' => 'Jumat', 'Saturday' => 'Sabtu'];
    $day_ind = $days_map[date('l', strtotime($deadline_tanggal))];

    $message = "📚 *TUGAS BARU DITAMBAHKAN!*\n";
    if (!empty($nama_kelas)) {
        $message .= "🏷️ *Kelas:* {$nama_kelas}\n";
    }
    $message .= "📖 *Mata Kuliah:*\n" . $nama_matkul . "\n";
    $message .= "📅 *Deadline:*\n" . $day_ind . ", " . date('d M Y', strtotime($deadline_tanggal)) . " pukul " . date('H:i', strtotime($deadline_pukul)) . " WITA\n";
    $message .= "📝 *Deskripsi:*\n" . ($deskripsi ?: '-') . "\n";
    if (!empty($link_kirim)) {
        $message .= "🔗 *Link Kirim:*\n" . $link_kirim . "\n";
    }
    $message .= "\n_Segera kerjakan tugasmu!_ 💪";

    return sendFonnteMessage($message, null, null, null, $target_kelas_id);
}

function notifyUpdateTask($token, $chat_id, $nama_matkul, $deadline_tanggal, $deadline_pukul, $deskripsi, $link_kirim, $kelas_id = null)
{
    global $koneksi;
    $target_kelas_id = !empty($kelas_id) ? (int)$kelas_id : (function_exists('getKelasScope') ? getKelasScope() : 1);

    $nama_kelas = '';
    if (!empty($target_kelas_id) && isset($koneksi)) {
        $qk = mysqli_query($koneksi, "SELECT nama_kelas FROM kelas WHERE id = $target_kelas_id LIMIT 1");
        if ($rk = mysqli_fetch_assoc($qk)) {
            $nama_kelas = $rk['nama_kelas'];
        }
    }

    $days_map = ['Sunday' => 'Minggu', 'Monday' => 'Senin', 'Tuesday' => 'Selasa', 'Wednesday' => 'Rabu', 'Thursday' => 'Kamis', 'Friday' => 'Jumat', 'Saturday' => 'Sabtu'];
    $day_ind = $days_map[date('l', strtotime($deadline_tanggal))];

    $message = "✏️ *TUGAS TELAH DIPERBARUI!*\n";
    if (!empty($nama_kelas)) {
        $message .= "🏷️ *Kelas:* {$nama_kelas}\n";
    }
    $message .= "📖 *Mata Kuliah:*\n" . $nama_matkul . "\n";
    $message .= "📅 *Deadline:*\n" . $day_ind . ", " . date('d M Y', strtotime($deadline_tanggal)) . " pukul " . date('H:i', strtotime($deadline_pukul)) . " WITA\n";
    $message .= "📝 *Deskripsi:*\n" . ($deskripsi ?: '-') . "\n";
    if (!empty($link_kirim)) {
        $message .= "🔗 *Link Kirim:*\n" . $link_kirim . "\n";
    }
    $message .= "\n_Perhatikan perubahan tugas ini!_ ⚠️";

    return sendFonnteMessage($message, null, null, null, $target_kelas_id);
}

// Standalone execution
if (basename($_SERVER['PHP_SELF']) == 'kirim_tugas.php') {
    session_start();
    include '../koneksi.php';

    if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['Administrator Utama', 'Sekretaris', 'Ketua Tingkat'])) {
        header("Location: ../../index");
        exit;
    }

    $res = kirimTugasReminder($koneksi);
    $_SESSION[$res['status']] = $res['message'];

    header("Location: ../database/db_tugas");
    exit;
}
