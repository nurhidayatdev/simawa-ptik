<?php
// functions/kirim_tugas.php
include_once __DIR__ . '/helpers.php';

function kirimTugasReminder($koneksi, $token, $chat_id)
{
    date_default_timezone_set('Asia/Makassar');
    $now_date = date('Y-m-d');
    $now_time = date('H:i:s');

    $query = "SELECT t.deadline_tanggal, t.deadline_pukul, t.deskripsi, t.link_kirim, mk.nama_matkul
              FROM tugas t JOIN mata_kuliah mk ON t.matkul_id = mk.id
              WHERE t.status = 'belum' AND (t.deadline_tanggal > '$now_date' OR (t.deadline_tanggal = '$now_date' AND t.deadline_pukul > '$now_time'))
              ORDER BY t.deadline_tanggal ASC, t.deadline_pukul ASC";
    $result = mysqli_query($koneksi, $query);

    if ($result && mysqli_num_rows($result) > 0) {
        $message = "🔔 *REKAP TUGAS AKTIF PTIK C* 🔔\n\nDaftar tugas *belum dikumpulkan*:\n\n";
        $no = 1;
        while ($row = mysqli_fetch_assoc($result)) {
            $status_time = $row['deadline_tanggal'] == $now_date ? "⚠️ *HARI INI*" : ($row['deadline_tanggal'] == date('Y-m-d', strtotime('+1 day')) ? "⏰ *BESOK*" : "📅 " . date('d M Y', strtotime($row['deadline_tanggal'])));
            $message .= "*{$no}. {$row['nama_matkul']}*\n   {$status_time} | " . date('H:i', strtotime($row['deadline_pukul'])) . " WITA\n";
            if (!empty($row['deskripsi'])) $message .= "   📝 {$row['deskripsi']}\n";
            if (!empty($row['link_kirim'])) $message .= "   🔗 [Link]({$row['link_kirim']})\n";
            $message .= "\n";
            $no++;
        }
        $message .= "_Segera kerjakan!_ 🚀";

        $res = sendTelegramMessage($token, $chat_id, $message);
        if ($res['success']) {
            return ['status' => 'success', 'message' => "Reminder tugas berhasil dikirim!"];
        } else {
            return ['status' => 'error', 'message' => "Gagal: " . ($res['error'] ?? '')];
        }
    } else {
        return ['status' => 'info', 'message' => "Tidak ada tugas aktif."];
    }
}

function notifyNewTask($token, $chat_id, $nama_matkul, $deadline_tanggal, $deadline_pukul, $deskripsi, $link_kirim)
{
    $message = "📚 *TUGAS BARU DITAMBAHKAN!*\n\n";
    $message .= "📖 *Mata Kuliah:* " . $nama_matkul . "\n";
    $message .= "📅 *Deadline:* " . $deadline_tanggal . " pukul " . $deadline_pukul . "\n";
    $message .= "📝 *Deskripsi:* " . ($deskripsi ?: '-') . "\n";
    if (!empty($link_kirim)) {
        $message .= "🔗 *Link Kirim:* " . $link_kirim . "\n";
    }
    $message .= "\n_Segera kerjakan tugasmu!_ 💪";

    return sendTelegramMessage($token, $chat_id, $message);
}

function notifyUpdateTask($token, $chat_id, $nama_matkul, $deadline_tanggal, $deadline_pukul, $deskripsi, $link_kirim)
{
    $message = "✏️ *TUGAS TELAH DIPERBARUI!*\n\n";
    $message .= "📖 *Mata Kuliah:* " . $nama_matkul . "\n";
    $message .= "📅 *Deadline:* " . $deadline_tanggal . " pukul " . $deadline_pukul . "\n";
    $message .= "📝 *Deskripsi:* " . ($deskripsi ?: '-') . "\n";
    if (!empty($link_kirim)) {
        $message .= "🔗 *Link Kirim:* " . $link_kirim . "\n";
    }
    $message .= "\n_Perhatikan perubahan tugas ini!_ ⚠️";

    return sendTelegramMessage($token, $chat_id, $message);
}

// Standalone execution
if (basename($_SERVER['PHP_SELF']) == 'kirim_tugas.php') {
    session_start();
    include '../koneksi.php';

    if (!isset($_SESSION['user_id']) || ($_SESSION['role'] !== 'Administrator Utama' && $_SESSION['role'] !== 'Sekretaris')) {
        header("Location: ../../index");
        exit;
    }

    $token = "8212322367:AAGCGc4TG__EnsEd-20wFTI_s2adp-dp1OY";
    $chat_id = "-1003577478223";

    $res = kirimTugasReminder($koneksi, $token, $chat_id);
    $_SESSION[$res['status']] = $res['message'];

    header("Location: ../database/db_tugas");
    exit;
}
