<?php
function kirimPiketReminder($koneksi, $token = null, $chat_id = null, $day = 'besok')
{
    date_default_timezone_set('Asia/Makassar');

    // 1. Tentukan Target (Hari & Tanggal)
    $offset = ($day === 'hari_ini') ? 'now' : '+1 day';
    $target_eng = date('l', strtotime($offset));
    $target_date = date('Y-m-d', strtotime($offset));

    $days_map = [
        'Monday' => 'Senin',
        'Tuesday' => 'Selasa',
        'Wednesday' => 'Rabu',
        'Thursday' => 'Kamis',
        'Friday' => 'Jumat',
        'Saturday' => 'Sabtu',
        'Sunday' => 'Minggu'
    ];
    $target_ind = $days_map[$target_eng];

    // 2. Hitung Minggu Ke (Berdasarkan 19 Januari 2026 sebagai Minggu 1)
    $start_date = "2026-01-19";
    $diff = strtotime($target_date) - strtotime($start_date);
    $days_diff = floor($diff / (60 * 60 * 24));

    if ($days_diff < 0) {
        return ['status' => 'error', 'message' => 'Belum masuk periode perkuliahan (Januari 2026).'];
    }

    $minggu_ke = floor($days_diff / 7) + 1;

    if ($minggu_ke > 16) {
        return ['status' => 'error', 'message' => 'Periode piket untuk semester ini telah berakhir.'];
    }

    // 3. Ambil Periode Aktif
    $q_p = mysqli_query($koneksi, "SELECT id FROM periode WHERE status = 'aktif' LIMIT 1");
    $p_id = mysqli_fetch_assoc($q_p)['id'] ?? 0;

    // 4. Query Data Piket Target
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
              AND jm.hari = '$target_ind'
              ORDER BY jk_start.jam_mulai ASC, p.jadwal_matkul_id, m.nama_lengkap";

    $res = mysqli_query($koneksi, $query);

    if (mysqli_num_rows($res) == 0) {
        $day_label = ($day === 'hari_ini') ? 'hari ini' : 'hari besok';
        return ['status' => 'info', 'message' => "Tidak ada jadwal piket proyektor untuk $day_label ($target_ind)."];
    }

    // 5. Format Pesan
    $msg = "📢 *REMINDER PIKET PROYEKTOR*\n";
    $header_label = ($day === 'hari_ini') ? 'Hari Ini' : 'Besok';
    $msg .= "📅 {$header_label}: *{$target_ind}, " . date('d M Y', strtotime($target_date)) . "*\n\n";

    $piket_grouped = [];
    while ($row = mysqli_fetch_assoc($res)) {
        $jid = $row['jadwal_matkul_id'];
        if (!isset($piket_grouped[$jid])) {
            $piket_grouped[$jid] = [
                'matkul' => $row['nama_matkul'],
                'jam' => date('H:i', strtotime($row['jam_mulai'])) . ' - ' . date('H:i', strtotime($row['jam_selesai'])),
                'ruangan' => $row['nama_ruangan'] ?? '-',
                'petugas' => []
            ];
        }
        $piket_grouped[$jid]['petugas'][] = $row['nama_lengkap'];
    }

    foreach ($piket_grouped as $data) {
        $msg .= "📘 *{$data['matkul']}*\n";
        $msg .= "⏰ {$data['jam']} | 📍 {$data['ruangan']}\n";
        $msg .= "👤 Petugas:\n";
        foreach ($data['petugas'] as $nama) {
            $msg .= "  • {$nama}\n";
        }
        $msg .= "\n";
    }

    $msg .= "⚠️ _Mohon ambil proyektor 15 menit sebelum kuliah dimulai. Terima kasih!_";

    // 6. Kirim ke WhatsApp via Fonnte
    include_once 'helpers.php';
    $status = sendFonnteMessage($msg);

    if ($status['success']) {
        return ['status' => 'success', 'message' => 'Pengingat piket proyektor berhasil dikirim ke WhatsApp!'];
    } else {
        return ['status' => 'error', 'message' => 'Gagal mengirim pesan ke WhatsApp. Error: ' . ($status['error'] ?? 'Unknown')];
    }
}
