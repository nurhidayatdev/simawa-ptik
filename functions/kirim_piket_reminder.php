<?php
// functions/kirim_piket_reminder.php - Multi-Class WhatsApp Routing
require_once __DIR__ . '/helpers.php';

function kirimPiketReminder($koneksi, $token = null, $chat_id = null, $day = 'besok', $custom_data = null, $minggu_ke = null, $kelas_id = null)
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

    // 1. Ambil Periode Aktif
    $q_p = mysqli_query($koneksi, "SELECT id FROM periode WHERE status = 'aktif' LIMIT 1");
    $p_id = mysqli_fetch_assoc($q_p)['id'] ?? 0;

    // 2. Ambil Tanggal Mulai Kuliah dari Kalender Akademik (Otomatis)
    $q_start = mysqli_query($koneksi, "SELECT tanggal_mulai FROM kalender_akademik 
        WHERE periode_id = $p_id 
        AND (kegiatan LIKE '%Kuliah Bagian Pertama%') 
        ORDER BY tanggal_mulai ASC LIMIT 1");
    $start_res = mysqli_fetch_assoc($q_start);
    $start_date = $start_res['tanggal_mulai'] ?? "2026-08-18";

    // Hitung hari Senin sebagai awal pekan perkuliahan (siklus Senin s/d Jumat)
    $day_of_week = (int)date('N', strtotime($start_date)); // 1 = Senin, ..., 7 = Minggu
    $start_monday = date('Y-m-d', strtotime("$start_date - " . ($day_of_week - 1) . " days"));

    // 3. Tentukan Target (Hari & Tanggal)
    $is_weekly = in_array($day, ['minggu', 'minggu_ini', 'seluruh_minggu']);
    if ($is_weekly) {
        if ($minggu_ke !== null && $minggu_ke >= 1 && $minggu_ke <= 18) {
            $target_date_start = date('Y-m-d', strtotime("$start_monday + " . (($minggu_ke - 1) * 7) . " days"));
            $target_date_end = date('Y-m-d', strtotime("$target_date_start + 4 days")); // Jumat
        } else {
            // Cari Senin pekan ini
            $today_dow = (int)date('N'); // 1 = Senin, ..., 7 = Minggu
            $target_date_start = date('Y-m-d', strtotime("-" . ($today_dow - 1) . " days"));
            $target_date_end = date('Y-m-d', strtotime("$target_date_start + 4 days")); // Jumat
            
            $diff = strtotime($target_date_start) - strtotime($start_monday);
            $days_diff = floor($diff / (60 * 60 * 24));
            $minggu_ke = floor($days_diff / 7) + 1;
        }
    } else {
        $offset = ($day === 'hari_ini') ? 'now' : '+1 day';
        $target_eng = date('l', strtotime($offset));
        $target_date = date('Y-m-d', strtotime($offset));

        $days_map = [
            'Monday' => 'Senin', 'Tuesday' => 'Selasa', 'Wednesday' => 'Rabu', 
            'Thursday' => 'Kamis', 'Friday' => 'Jumat', 'Saturday' => 'Sabtu', 'Sunday' => 'Minggu'
        ];
        $target_ind = $days_map[$target_eng];

        $diff = strtotime($target_date) - strtotime($start_monday);
        $days_diff = floor($diff / (60 * 60 * 24));
        $minggu_ke = floor($days_diff / 7) + 1;
    }

    if ($minggu_ke < 1 || $minggu_ke > 18) {
        return ['status' => 'error', 'message' => "Periode piket (Minggu $minggu_ke) berada di luar jadwal perkuliahan."];
    }

    // 4. Query Data Piket Target
    $where_p_scope = !empty($target_kelas_id) ? " AND p.kelas_id = $target_kelas_id " : "";

    $query = "SELECT p.*, m.nama_lengkap, jm.hari, mk.nama_matkul, r.nama_ruangan, jk_start.jam_mulai, jk_end.jam_selesai
              FROM piket_proyektor p
              JOIN mahasiswa m ON p.mahasiswa_id = m.id
              JOIN jadwal_matkul jm ON p.jadwal_matkul_id = jm.id
              JOIN mata_kuliah mk ON jm.matkul_id = mk.id
              LEFT JOIN ruangan r ON jm.ruangan_id = r.id
              LEFT JOIN jam_kuliah jk_start ON jm.jam_mulai_ke = jk_start.jam_ke
              LEFT JOIN jam_kuliah jk_end ON jm.jam_selesai_ke = jk_end.jam_ke
              WHERE p.periode_id = $p_id 
              AND p.minggu_ke = $minggu_ke 
              $where_p_scope ";
    
    if (!$is_weekly) {
        $query .= " AND jm.hari = '$target_ind' ";
    }
    
    $query .= " ORDER BY FIELD(jm.hari, 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu', 'Minggu'), jk_start.jam_mulai ASC, p.jadwal_matkul_id, m.nama_lengkap";

    $res = mysqli_query($koneksi, $query);

    if (mysqli_num_rows($res) == 0) {
        return ['status' => 'info', 'message' => "Tidak ada jadwal piket proyektor untuk periode yang dipilih (Minggu $minggu_ke) di {$nama_kelas}."];
    }

    // 5. Format Pesan
    $msg = "📢 *REMINDER PIKET PROYEKTOR - {$nama_kelas}*\n";
    if ($is_weekly) {
        $start_week_date = date('d M', strtotime($target_date_start));
        $end_week_date = date('d M Y', strtotime($target_date_end));
        $msg .= "📅 *Minggu ke-{$minggu_ke}* ({$start_week_date} - {$end_week_date})\n\n";
    } else {
        $header_label = ($day === 'hari_ini') ? 'Hari Ini' : 'Besok';
        $msg .= "📅 {$header_label}: *{$target_ind}, " . date('d M Y', strtotime($target_date)) . "* | Minggu ke-{$minggu_ke}\n\n";
    }

    $grouped = [];
    while ($row = mysqli_fetch_assoc($res)) {
        $key = $row['jadwal_matkul_id'];
        if (!isset($grouped[$key])) {
            $grouped[$key] = [
                'hari' => $row['hari'],
                'matkul' => $row['nama_matkul'],
                'ruangan' => $row['nama_ruangan'] ?? 'Ruang Belum Diatur',
                'jam' => date('H.i', strtotime($row['jam_mulai'])) . ' - ' . date('H.i', strtotime($row['jam_selesai'])) . ' WITA',
                'petugas' => []
            ];
        }
        $grouped[$key]['petugas'][] = $row['nama_lengkap'];
    }

    $current_hari = '';
    foreach ($grouped as $data) {
        if ($is_weekly && $current_hari !== $data['hari']) {
            $current_hari = $data['hari'];
            $msg .= "🗓️ *HARI {$current_hari}:*\n";
        }
        $msg .= "📘 *{$data['matkul']}*\n";
        $msg .= "⏰ {$data['jam']}\n";
        $msg .= "📍 {$data['ruangan']}\n";
        $msg .= "👤 Petugas:\n- " . implode("\n- ", $data['petugas']) . "\n\n";
    }

    $msg .= "⚠️ *Catatan:*\n_Jadwal di atas bersifat situasional. Harap tetap memantau instruksi final dari Ketua Tingkat sebelum bertugas._";

    // 6. Kirim ke WhatsApp via Fonnte dengan scoping kelas
    $status = sendFonnteMessage($msg, null, null, null, $target_kelas_id);

    if ($status['success']) {
        return ['status' => 'success', 'message' => "Notifikasi piket proyektor Minggu ke-{$minggu_ke} berhasil dikirim ke WhatsApp {$nama_kelas}!"];
    } else {
        return ['status' => 'error', 'message' => 'Gagal mengirim pesan ke WhatsApp. Error: ' . ($status['error'] ?? 'Unknown')];
    }
}
