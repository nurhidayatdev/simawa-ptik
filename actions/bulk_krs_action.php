<?php
session_start();
include '../koneksi.php';

// Cek Login & Role
if (!isset($_SESSION['user_id'])) {
    header("Location: ../../index.php");
    exit;
}

$role = $_SESSION['role'];
if ($role !== 'Administrator Utama') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Akses ditolak.']);
    exit;
}

// Pastikan request POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: bulk_krs.php");
    exit;
}

// Pastikan tabel ada
mysqli_query($koneksi, "CREATE TABLE IF NOT EXISTS krs_temp (
    id INT AUTO_INCREMENT PRIMARY KEY,
    mahasiswa_id INT NOT NULL,
    matkul_id INT NOT NULL,
    periode_id INT NOT NULL,
    timestamp DATETIME DEFAULT CURRENT_TIMESTAMP,
    KEY idx_mhs_per (mahasiswa_id, periode_id)
)");
mysqli_query($koneksi, "CREATE TABLE IF NOT EXISTS krs_validasi (
    id INT AUTO_INCREMENT PRIMARY KEY,
    mahasiswa_id INT NOT NULL,
    periode_id INT NOT NULL,
    status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
    tanggal_validasi DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_validasi (mahasiswa_id, periode_id)
)");

// Ambil Periode Aktif
$q_periode = mysqli_query($koneksi, "SELECT id FROM periode WHERE status = 'aktif' LIMIT 1");
$periode = mysqli_fetch_assoc($q_periode);
if (!$periode) {
    header("Location: bulk_krs?msg=no_periode");
    exit;
}
$periode_id = (int)$periode['id'];

$action = $_POST['action'] ?? '';

// ============================================================
// HELPER: Approve satu mahasiswa (sync krs_temp -> krs)
// ============================================================
function approveOneMahasiswa($koneksi, $mhs_id, $periode_id)
{
    // Hapus krs lama
    mysqli_query($koneksi, "DELETE FROM krs WHERE mahasiswa_id = $mhs_id AND periode_id = $periode_id");
    // Switch: Hapus dari periode lain jika matkul tersebut ada di krs_temp periode ini
    mysqli_query($koneksi, "
        DELETE FROM krs 
        WHERE mahasiswa_id = $mhs_id 
        AND matkul_id IN (
            SELECT matkul_id FROM krs_temp WHERE mahasiswa_id = $mhs_id AND periode_id = $periode_id
        )
    ");
    // Copy dari temp ke krs
    mysqli_query($koneksi, "
        INSERT INTO krs (mahasiswa_id, matkul_id, periode_id)
        SELECT mahasiswa_id, matkul_id, periode_id
        FROM krs_temp
        WHERE mahasiswa_id = $mhs_id AND periode_id = $periode_id
        ON DUPLICATE KEY UPDATE periode_id = VALUES(periode_id)
    ");
    // Set status approved
    $stmt = mysqli_prepare($koneksi, "INSERT INTO krs_validasi (mahasiswa_id, periode_id, status)
        VALUES (?, ?, 'approved')
        ON DUPLICATE KEY UPDATE status = 'approved', tanggal_validasi = NOW()");
    mysqli_stmt_bind_param($stmt, "ii", $mhs_id, $periode_id);
    mysqli_stmt_execute($stmt);
}

// ============================================================
// HELPER: Reject satu mahasiswa
// ============================================================
function rejectOneMahasiswa($koneksi, $mhs_id, $periode_id)
{
    $stmt = mysqli_prepare($koneksi, "INSERT INTO krs_validasi (mahasiswa_id, periode_id, status)
        VALUES (?, ?, 'rejected')
        ON DUPLICATE KEY UPDATE status = 'rejected', tanggal_validasi = NOW()");
    mysqli_stmt_bind_param($stmt, "ii", $mhs_id, $periode_id);
    mysqli_stmt_execute($stmt);
}

// ============================================================
// ACTION: bulk_approve
// ============================================================
if ($action === 'bulk_approve') {
    $ids_raw = $_POST['mahasiswa_ids'] ?? [];
    if (empty($ids_raw)) {
        header("Location: bulk_krs?msg=no_selection");
        exit;
    }

    $ids = array_map('intval', (array)$ids_raw);
    $count = 0;
    foreach ($ids as $mhs_id) {
        if ($mhs_id <= 0) continue;
        approveOneMahasiswa($koneksi, $mhs_id, $periode_id);
        $count++;
    }
    header("Location: bulk_krs?msg=bulk_approved&count=$count");
    exit;
}

// ============================================================
// ACTION: bulk_reject
// ============================================================
if ($action === 'bulk_reject') {
    $ids_raw = $_POST['mahasiswa_ids'] ?? [];
    if (empty($ids_raw)) {
        header("Location: bulk_krs?msg=no_selection");
        exit;
    }

    $ids = array_map('intval', (array)$ids_raw);
    $count = 0;
    foreach ($ids as $mhs_id) {
        if ($mhs_id <= 0) continue;
        rejectOneMahasiswa($koneksi, $mhs_id, $periode_id);
        $count++;
    }
    header("Location: bulk_krs?msg=bulk_rejected&count=$count");
    exit;
}

// ============================================================
// ACTION: bulk_set_krs (Set + Auto Approve)
// ============================================================
if ($action === 'bulk_set_krs') {
    $ids_raw  = $_POST['mahasiswa_ids'] ?? [];
    $matkul_raw = $_POST['matkul_ids'] ?? [];

    if (empty($ids_raw)) {
        header("Location: bulk_krs?msg=no_selection");
        exit;
    }
    if (empty($matkul_raw)) {
        header("Location: bulk_krs?msg=no_matkul");
        exit;
    }

    $ids     = array_map('intval', (array)$ids_raw);
    $matkuls = array_map('intval', (array)$matkul_raw);

    // Validasi SKS server-side
    $matkuls_str = implode(',', $matkuls);
    $res_sks = mysqli_query($koneksi, "SELECT SUM(sks) as total FROM mata_kuliah WHERE id IN ($matkuls_str)");
    $data_sks = mysqli_fetch_assoc($res_sks);
    $total_sks = (int)$data_sks['total'];

    if ($total_sks > 24) {
        header("Location: bulk_krs?msg=sks_limit&sks=$total_sks");
        exit;
    }

    // Prepare insert statement untuk krs_temp & krs
    $stmt_temp = mysqli_prepare($koneksi,
        "INSERT INTO krs_temp (mahasiswa_id, matkul_id, periode_id) VALUES (?, ?, ?)"
    );
    $stmt_krs = mysqli_prepare($koneksi,
        "INSERT INTO krs (mahasiswa_id, matkul_id, periode_id) VALUES (?, ?, ?)"
    );
    $stmt_val = mysqli_prepare($koneksi,
        "INSERT INTO krs_validasi (mahasiswa_id, periode_id, status) VALUES (?, ?, 'approved') ON DUPLICATE KEY UPDATE status = 'approved', tanggal_validasi = NOW()"
    );

    $count = 0;
    foreach ($ids as $mhs_id) {
        if ($mhs_id <= 0) continue;

        // Hapus krs_temp & krs lama
        mysqli_query($koneksi, "DELETE FROM krs WHERE mahasiswa_id = $mhs_id AND periode_id = $periode_id");
        mysqli_query($koneksi, "DELETE FROM krs_temp WHERE mahasiswa_id = $mhs_id AND periode_id = $periode_id");

        // Insert matkul ke krs_temp & krs
        foreach ($matkuls as $matkul_id) {
            mysqli_stmt_bind_param($stmt_temp, "iii", $mhs_id, $matkul_id, $periode_id);
            mysqli_stmt_execute($stmt_temp);

            mysqli_stmt_bind_param($stmt_krs, "iii", $mhs_id, $matkul_id, $periode_id);
            mysqli_stmt_execute($stmt_krs);
        }

        // Set status approved
        mysqli_stmt_bind_param($stmt_val, "ii", $mhs_id, $periode_id);
        mysqli_stmt_execute($stmt_val);

        $count++;
    }

    header("Location: bulk_krs?msg=bulk_set&count=$count&sks=$total_sks");
    exit;
}

// ============================================================
// ACTION: reset_krs (hapus KRS + reset status)
// ============================================================
if ($action === 'bulk_reset') {
    $ids_raw = $_POST['mahasiswa_ids'] ?? [];
    if (empty($ids_raw)) {
        header("Location: bulk_krs?msg=no_selection");
        exit;
    }

    $ids = array_map('intval', (array)$ids_raw);
    $count = 0;
    foreach ($ids as $mhs_id) {
        if ($mhs_id <= 0) continue;
        mysqli_query($koneksi, "DELETE FROM krs WHERE mahasiswa_id = $mhs_id AND periode_id = $periode_id");
        mysqli_query($koneksi, "DELETE FROM krs_temp WHERE mahasiswa_id = $mhs_id AND periode_id = $periode_id");
        mysqli_query($koneksi, "DELETE FROM krs_validasi WHERE mahasiswa_id = $mhs_id AND periode_id = $periode_id");
        $count++;
    }
    header("Location: bulk_krs?msg=bulk_reset&count=$count");
    exit;
}

// Fallback
header("Location: bulk_krs?msg=unknown_action");
exit;
