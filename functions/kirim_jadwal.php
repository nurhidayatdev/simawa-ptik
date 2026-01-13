<?php
// functions/kirim_jadwal.php
include_once __DIR__ . '/helpers.php';

function kirimJadwalReminder($koneksi, $token, $chat_id, $custom_data = null)
{
    // Set timezone to WITA
    date_default_timezone_set('Asia/Makassar');

    $days_map = [
        'Sunday' => 'Minggu',
        'Monday' => 'Senin',
        'Tuesday' => 'Selasa',
        'Wednesday' => 'Rabu',
        'Thursday' => 'Kamis',
        'Friday' => 'Jumat',
        'Saturday' => 'Sabtu'
    ];

    $tomorrow_eng = date('l', strtotime('+1 day'));
    $tomorrow_ind = $days_map[$tomorrow_eng];

    $message = "📅 *JADWAL KULIAH BESOK* 📅\nHari: *{$tomorrow_ind}*, " . date('d M Y', strtotime('+1 day')) . "\n\n";

    if ($custom_data) {
        // Use custom data
        // Expecting $custom_data to be an array of schedules
        // e.g., [['matkul' => '...', 'jam' => '...', 'ruangan' => '...']]

        // Single custom schedule for now based on user request "matkul, jam, dan ruangan"
        // Or handle multiple. Given user said "pengeditan... matkul, jam, dan ruangan", implying singular or a text block.
        // Let's assume user inputs standard fields for one or more items.
        // For simplicity based on prompt "edit popup", let's handle single item or iterating if array.

        $no = 1;
        foreach ($custom_data as $item) {
            $message .= "{$no}. *{$item['matkul']}*\n   ⏰ {$item['jam']} WITA\n   🏛️ {$item['ruangan']}\n   Note: _Jadwal Disesuaikan_\n\n";
            $no++;
        }
        $message .= "_Jangan lupa datang tepat waktu!_ 📝";

        $res = sendTelegramMessage($token, $chat_id, $message);
        if ($res['success']) {
            return ['status' => 'success', 'message' => "Jadwal Custom besok berhasil dikirim!"];
        } else {
            return ['status' => 'error', 'message' => "Gagal: " . ($res['error'] ?? '')];
        }
    } else {
        // Use Database
        $q_periode = mysqli_query($koneksi, "SELECT id FROM periode WHERE status = 'aktif' LIMIT 1");
        $periode = mysqli_fetch_assoc($q_periode);
        $periode_id = $periode['id'] ?? 0;

        $query = "SELECT jk_start.jam_mulai, jk_end.jam_selesai, r.nama_ruangan AS ruangan, mk.nama_matkul, dp.nama_dosen AS dosen_pengampu
                  FROM jadwal_matkul jm
                  JOIN mata_kuliah mk ON jm.matkul_id = mk.id
                  LEFT JOIN ruangan r ON jm.ruangan_id = r.id
                  LEFT JOIN jam_kuliah jk_start ON jm.jam_mulai_ke = jk_start.jam_ke
                  LEFT JOIN jam_kuliah jk_end ON jm.jam_selesai_ke = jk_end.jam_ke
                  LEFT JOIN dosen dp ON jm.dosen_pengampu_id = dp.id
                  WHERE jm.periode_id = $periode_id AND jm.hari = '$tomorrow_ind'
                  ORDER BY jk_start.jam_mulai ASC";

        $result = mysqli_query($koneksi, $query);

        if ($result && mysqli_num_rows($result) > 0) {
            $no = 1;
            while ($row = mysqli_fetch_assoc($result)) {
                $message .= "{$no}. *{$row['nama_matkul']}*\n   ⏰ " . date('H:i', strtotime($row['jam_mulai'])) . " - " . date('H:i', strtotime($row['jam_selesai'])) . " WITA\n   🏛️ {$row['ruangan']}\n   👨‍🏫 {$row['dosen_pengampu']}\n\n";
                $no++;
            }
            $message .= "_Jangan lupa datang tepat waktu!_ 📝";

            $res = sendTelegramMessage($token, $chat_id, $message);
            if ($res['success']) {
                return ['status' => 'success', 'message' => "Jadwal $tomorrow_ind berhasil dikirim!"];
            } else {
                return ['status' => 'error', 'message' => "Gagal: " . ($res['error'] ?? '')];
            }
        } else {
            return ['status' => 'info', 'message' => "Tidak ada jadwal untuk hari $tomorrow_ind besok."];
        }
    }
}

// Standalone execution
if (basename($_SERVER['PHP_SELF']) == 'kirim_jadwal.php') {
    session_start();
    include '../koneksi.php';

    if (!isset($_SESSION['user_id']) || ($_SESSION['role'] !== 'Administrator Utama' && $_SESSION['role'] !== 'Sekretaris')) {
        header("Location: ../../index");
        exit;
    }

    $token = "8212322367:AAGCGc4TG__EnsEd-20wFTI_s2adp-dp1OY";
    $chat_id = "-1003577478223";

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'kirim_custom') {
        // Handle custom data via JSON payload
        if (isset($_POST['custom_payload'])) {
            $custom_data = json_decode($_POST['custom_payload'], true);
        } else {
            // Fallback for single item (jaga-jaga)
            $custom_data = [
                [
                    'matkul' => $_POST['matkul'] ?? '',
                    'jam' => $_POST['jam'] ?? '',
                    'ruangan' => $_POST['ruangan'] ?? ''
                ]
            ];
        }
        $res = kirimJadwalReminder($koneksi, $token, $chat_id, $custom_data);
    } else {
        // Default / 'kirim_langsung'
        $res = kirimJadwalReminder($koneksi, $token, $chat_id);
    }

    $_SESSION[$res['status']] = $res['message'];

    header("Location: ../database/db_jadwal");
    exit;
}
