<?php
// functions/kirim_kelompok.php - Multi-Class WhatsApp Routing
include_once __DIR__ . '/helpers.php';

function kirimKelompokReminder($koneksi, $matkul_id, $dosen, $keterangan, $type = 'new', $token = null, $chat_id = null, $kelas_id = null)
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

    $matkul_id = mysqli_real_escape_string($koneksi, $matkul_id);
    $dosen = mysqli_real_escape_string($koneksi, $dosen);
    $keterangan = mysqli_real_escape_string($koneksi, $keterangan);

    $q_periode = mysqli_query($koneksi, "SELECT id FROM periode WHERE status = 'aktif' LIMIT 1");
    $periode = mysqli_fetch_assoc($q_periode);
    $periode_id = $periode['id'] ?? 0;

    // Get matkul name
    $q_mk = mysqli_query($koneksi, "SELECT nama_matkul FROM mata_kuliah WHERE id = $matkul_id");
    $mk_name = mysqli_fetch_assoc($q_mk)['nama_matkul'] ?? 'Mata Kuliah';

    // Get groups and members
    $where_k_scope = !empty($target_kelas_id) ? " AND kelas_id = $target_kelas_id " : "";
    $q_groups = "SELECT kelompok_no, anggota 
                 FROM kelompok 
                 WHERE matkul_id = '$matkul_id' 
                 AND dosen = '$dosen' 
                 AND keterangan = '$keterangan' 
                 AND periode_id = '$periode_id' 
                 $where_k_scope
                 ORDER BY kelompok_no ASC";
    $res_groups = mysqli_query($koneksi, $q_groups);

    if ($res_groups && mysqli_num_rows($res_groups) > 0) {
        $groups_data = [];
        $all_nims = [];
        while ($row = mysqli_fetch_assoc($res_groups)) {
            $nims = array_filter(array_map('trim', explode(',', $row['anggota'])));
            foreach ($nims as $nim) $all_nims[$nim] = true;
            $groups_data[] = [
                'no' => $row['kelompok_no'],
                'nims' => $nims
            ];
        }

        // Map NIM to Name
        $student_names = [];
        if (!empty($all_nims)) {
            $nim_list = "'" . implode("','", array_map(function ($n) use ($koneksi) {
                return mysqli_real_escape_string($koneksi, $n);
            }, array_keys($all_nims))) . "'";
            $res_names = mysqli_query($koneksi, "SELECT nim, nama_lengkap FROM mahasiswa WHERE nim IN ($nim_list)");
            while ($rn = mysqli_fetch_assoc($res_names)) {
                $student_names[$rn['nim']] = $rn['nama_lengkap'];
            }
        }

        $header = ($type === 'edit') ? "✏️ *PEMBARUAN DATA KELOMPOK - {$nama_kelas}*" : "👥 *DAFTAR KELOMPOK BARU - {$nama_kelas}*";
        $footer = ($type === 'edit') ? "_Data kelompok telah diperbarui oleh Admin._ ⚠️" : "_Silakan hubungi teman kelompok kalian untuk koordinasi!_ 🤝";

        $message = "{$header} 👥\n";
        $message .= "📘 *Matkul:* {$mk_name}\n";
        $message .= "👨‍🏫 *Dosen:* {$dosen}\n";
        if (!empty($keterangan)) $message .= "📝 *Ket:* {$keterangan}\n";
        $message .= "\n";

        foreach ($groups_data as $g) {
            $message .= "*Kelompok {$g['no']}:*\n";
            foreach ($g['nims'] as $nim) {
                $nama = $student_names[$nim] ?? "Mahasiswa ($nim)";
                $message .= "• {$nama}\n";
            }
            $message .= "\n";
        }
        $message .= $footer;

        $res = sendFonnteMessage($message, null, null, null, $target_kelas_id);
        if ($res['success']) {
            return ['status' => 'success', 'message' => "Daftar kelompok berhasil dikirim ke WhatsApp {$nama_kelas}!"];
        } else {
            return ['status' => 'error', 'message' => "Gagal: " . ($res['error'] ?? '')];
        }
    } else {
        return ['status' => 'error', 'message' => "Data kelompok tidak ditemukan untuk {$nama_kelas}."];
    }
}
