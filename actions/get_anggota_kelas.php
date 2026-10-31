<?php
session_start();
include '../koneksi.php';
include_once '../functions/foto_helpers.php';

header('Content-Type: application/json; charset=utf-8');

// Proteksi Akses: Hanya Administrator Utama atau user login
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

$kelas_id = (int)($_GET['kelas_id'] ?? 0);
if ($kelas_id <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Parameter kelas_id tidak valid']);
    exit();
}

// Cek detail kelas
$stmt_k = mysqli_prepare($koneksi, "SELECT id, nama_kelas, angkatan, prodi FROM kelas WHERE id = ? LIMIT 1");
mysqli_stmt_bind_param($stmt_k, "i", $kelas_id);
mysqli_stmt_execute($stmt_k);
$res_k = mysqli_stmt_get_result($stmt_k);
$kelas = mysqli_fetch_assoc($res_k);

if (!$kelas) {
    http_response_code(404);
    echo json_encode(['success' => false, 'message' => 'Kelas tidak ditemukan']);
    exit();
}

// Ambil anggota kelas yang ber-role Mahasiswa (mengecualikan akun fungsional: Admin, Keti, Sekre, Bendahara)
$stmt = mysqli_prepare($koneksi, "
    SELECT 
        m.id,
        m.nama_lengkap,
        m.nama_panggilan,
        m.nim,
        m.foto,
        m.jenis_kelamin,
        u.email as user_email
    FROM mahasiswa m
    LEFT JOIN users u ON m.user_id = u.id
    WHERE m.kelas_id = ?
      AND (u.role = 'Mahasiswa' OR (m.user_id IS NULL AND m.nama_lengkap NOT LIKE '%Administrator%'))
      AND (u.role NOT IN ('Administrator Utama', 'Ketua Tingkat', 'Sekretaris', 'Bendahara') OR u.role IS NULL)
    ORDER BY m.nim ASC
");
mysqli_stmt_bind_param($stmt, "i", $kelas_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

$anggota = [];
while ($row = mysqli_fetch_assoc($result)) {
    $anggota[] = [
        'id'             => (int)$row['id'],
        'nim'            => $row['nim'],
        'nama_lengkap'   => $row['nama_lengkap'],
        'nama_panggilan' => $row['nama_panggilan'] ?? '',
        'foto_url'       => !empty($row['foto']) ? profilePhotoUrl($row['foto'], '') : '',
        'email'          => $row['user_email'] ?? '-',
        'jenis_kelamin'  => $row['jenis_kelamin'] ?? '-'
    ];
}

echo json_encode([
    'success' => true,
    'kelas'   => $kelas,
    'total'   => count($anggota),
    'anggota' => $anggota
]);
exit();

