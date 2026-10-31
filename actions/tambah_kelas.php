<?php
session_start();
include '../koneksi.php';

// Proteksi Akses: Hanya Administrator Utama
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'Administrator Utama') {
    include '../includes/alert_role.php';
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // ==========================================
    // 1. TAMBAH KELAS BARU & KETUA TINGKAT LANGSUNG
    // ==========================================
    if ($action === 'tambah_kelas') {
        $nama_kelas   = trim($_POST['nama_kelas'] ?? '');
        $angkatan     = '2024'; // Tetap & Fiks Angkatan 2024
        $prodi        = 'Pendidikan Teknik Informatika dan Komputer'; // Tetap & Fiks Prodi PTIK
        $status_aktif = ($_POST['status_aktif'] ?? 'aktif') === 'non-aktif' ? 'non-aktif' : 'aktif';

        if (empty($nama_kelas)) {
            $_SESSION['error'] = 'Nama kelas wajib diisi!';
            header('Location: ../dashboard_admin');
            exit();
        }

        // Cek duplikasi nama kelas
        $stmt_check = mysqli_prepare($koneksi, "SELECT id FROM kelas WHERE nama_kelas = ? LIMIT 1");
        mysqli_stmt_bind_param($stmt_check, "s", $nama_kelas);
        mysqli_stmt_execute($stmt_check);
        $res_check = mysqli_stmt_get_result($stmt_check);
        if (mysqli_num_rows($res_check) > 0) {
            $_SESSION['error'] = "Kelas dengan nama '$nama_kelas' sudah terdaftar!";
            header('Location: ../dashboard_admin');
            exit();
        }

        // Data Akun Baru Ketua Tingkat (Langsung Dibuat)
        $ketua_nama     = trim($_POST['ketua_nama'] ?? '');
        $ketua_email    = trim($_POST['ketua_email'] ?? '');
        $ketua_password = trim($_POST['ketua_password'] ?? '');

        if (empty($ketua_nama) || empty($ketua_email) || empty($ketua_password)) {
            $_SESSION['error'] = 'Nama Lengkap, Email Login, dan Password untuk Ketua Tingkat baru wajib diisi!';
            header('Location: ../dashboard_admin');
            exit();
        }

        // Validasi format email
        if (!filter_var($ketua_email, FILTER_VALIDATE_EMAIL)) {
            $_SESSION['error'] = 'Format email Ketua Tingkat tidak valid!';
            header('Location: ../dashboard_admin');
            exit();
        }

        // Cek keunikan email di tabel users
        $stmt_u = mysqli_prepare($koneksi, "SELECT id FROM users WHERE email = ? LIMIT 1");
        mysqli_stmt_bind_param($stmt_u, "s", $ketua_email);
        mysqli_stmt_execute($stmt_u);
        if (mysqli_num_rows(mysqli_stmt_get_result($stmt_u)) > 0) {
            $_SESSION['error'] = "Email '$ketua_email' sudah digunakan oleh akun lain!";
            header('Location: ../dashboard_admin');
            exit();
        }

        // 1. Simpan data kelas baru
        $stmt_ins = mysqli_prepare($koneksi, "INSERT INTO kelas (nama_kelas, angkatan, prodi, status_aktif) VALUES (?, ?, ?, ?)");
        mysqli_stmt_bind_param($stmt_ins, "ssss", $nama_kelas, $angkatan, $prodi, $status_aktif);
        if (!mysqli_stmt_execute($stmt_ins)) {
            $_SESSION['error'] = 'Gagal menyimpan kelas baru: ' . mysqli_error($koneksi);
            header('Location: ../dashboard_admin');
            exit();
        }
        $kelas_id = mysqli_insert_id($koneksi);

        // 2. Buat akun login Ketua Tingkat di tabel users (Clean Architecture: Profil melekat di users)
        $linked_nim = trim($_POST['linked_student_nim'] ?? '');
        $linked_nim = !empty($linked_nim) ? $linked_nim : null;

        $stmt_user = mysqli_prepare($koneksi, "INSERT INTO users (email, password, role, nama_lengkap, linked_student_nim, kelas_id) VALUES (?, ?, 'Ketua Tingkat', ?, ?, ?)");
        mysqli_stmt_bind_param($stmt_user, "ssssi", $ketua_email, $ketua_password, $ketua_nama, $linked_nim, $kelas_id);
        mysqli_stmt_execute($stmt_user);
        $new_user_id = mysqli_insert_id($koneksi);

        // 3. Tautkan ketua_tingkat_user_id pada kelas
        $stmt_up_k = mysqli_prepare($koneksi, "UPDATE kelas SET ketua_tingkat_user_id = ? WHERE id = ?");
        mysqli_stmt_bind_param($stmt_up_k, "ii", $new_user_id, $kelas_id);
        mysqli_stmt_execute($stmt_up_k);

        // Catatan: Akun fungsional Ketua Tingkat TIDAK dimasukkan ke tabel mahasiswa.
        // Jika pemegang akun ini adalah mahasiswa aktif di kelasnya, dia akan mendaftarkan
        // akun Mahasiswa terpisah lewat menu 'Tambah Mahasiswa' dengan NIM aslinya.

        $_SESSION['success'] = "Kelas <strong>" . htmlspecialchars($nama_kelas) . "</strong> dan Akun Ketua Tingkat (<strong>" . htmlspecialchars($ketua_nama) . "</strong> - <code>" . htmlspecialchars($ketua_email) . "</code>) berhasil dibuat!";
        header('Location: ../dashboard_admin');
        exit();
    }

    // ==========================================
    // 2. TOGGLE STATUS AKTIF KELAS
    // ==========================================
    if ($action === 'toggle_status_kelas') {
        $kelas_id = (int)($_POST['kelas_id'] ?? 0);
        if ($kelas_id > 0) {
            $q_current = mysqli_query($koneksi, "SELECT status_aktif, nama_kelas FROM kelas WHERE id = $kelas_id LIMIT 1");
            if ($row = mysqli_fetch_assoc($q_current)) {
                $new_status = ($row['status_aktif'] === 'aktif') ? 'non-aktif' : 'aktif';
                mysqli_query($koneksi, "UPDATE kelas SET status_aktif = '$new_status' WHERE id = $kelas_id");
                $_SESSION['success'] = "Status kelas <strong>" . htmlspecialchars($row['nama_kelas']) . "</strong> diubah menjadi <strong>" . ucfirst($new_status) . "</strong>.";
            }
        }
        header('Location: ../dashboard_admin');
        exit();
    }

    // ==========================================
    // 3. EDIT KELAS & KETUA TINGKAT
    // ==========================================
    if ($action === 'edit_kelas') {
        $kelas_id     = (int)($_POST['kelas_id'] ?? 0);
        $nama_kelas   = trim($_POST['nama_kelas'] ?? '');
        $angkatan     = trim($_POST['angkatan'] ?? '2024');
        $prodi        = trim($_POST['prodi'] ?? 'Pendidikan Teknik Informatika dan Komputer');
        $status_aktif = ($_POST['status_aktif'] ?? 'aktif') === 'non-aktif' ? 'non-aktif' : 'aktif';
        $ketua_user_id = !empty($_POST['ketua_user_id']) ? (int)$_POST['ketua_user_id'] : null;

        if ($kelas_id <= 0 || empty($nama_kelas)) {
            $_SESSION['error'] = 'Data edit kelas tidak valid!';
            header('Location: ../dashboard_admin');
            exit();
        }

        // Cek nama kelas ganda selain dirinya
        $stmt_check = mysqli_prepare($koneksi, "SELECT id FROM kelas WHERE nama_kelas = ? AND id != ? LIMIT 1");
        mysqli_stmt_bind_param($stmt_check, "si", $nama_kelas, $kelas_id);
        mysqli_stmt_execute($stmt_check);
        if (mysqli_num_rows(mysqli_stmt_get_result($stmt_check)) > 0) {
            $_SESSION['error'] = "Nama kelas '$nama_kelas' sudah digunakan oleh kelas lain!";
            header('Location: ../dashboard_admin');
            exit();
        }

        if ($ketua_user_id !== null) {
            // Update kelas dengan ketua tingkat
            $stmt_up = mysqli_prepare($koneksi, "UPDATE kelas SET nama_kelas = ?, angkatan = ?, prodi = ?, status_aktif = ?, ketua_tingkat_user_id = ? WHERE id = ?");
            mysqli_stmt_bind_param($stmt_up, "ssssii", $nama_kelas, $angkatan, $prodi, $status_aktif, $ketua_user_id, $kelas_id);
            mysqli_stmt_execute($stmt_up);

            // Update user & mahasiswa yang ditunjuk
            mysqli_query($koneksi, "UPDATE users SET role = 'Ketua Tingkat', kelas_id = $kelas_id WHERE id = $ketua_user_id");
            // Single-account-single-role: non-student accounts are not in mahasiswa table
        } else {
            // Update tanpa ketua tingkat
            $stmt_up = mysqli_prepare($koneksi, "UPDATE kelas SET nama_kelas = ?, angkatan = ?, prodi = ?, status_aktif = ?, ketua_tingkat_user_id = NULL WHERE id = ?");
            mysqli_stmt_bind_param($stmt_up, "ssssi", $nama_kelas, $angkatan, $prodi, $status_aktif, $kelas_id);
            mysqli_stmt_execute($stmt_up);
        }

        $_SESSION['success'] = "Perubahan kelas <strong>" . htmlspecialchars($nama_kelas) . "</strong> berhasil disimpan!";
        header('Location: ../dashboard_admin');
        exit();
    }

    // ==========================================
    // 4. QUICK MAINTENANCE TOGGLE
    // ==========================================
    if ($action === 'quick_maintenance_toggle') {
        require_once __DIR__ . '/../functions/maintenance.php';
        $current = is_maintenance_mode($koneksi);
        $new_val = $current ? '0' : '1';
        set_setting($koneksi, 'maintenance_mode', $new_val);

        if (isset($_POST['maintenance_message']) && !empty(trim($_POST['maintenance_message']))) {
            set_setting($koneksi, 'maintenance_message', trim($_POST['maintenance_message']));
        }

        $_SESSION['success'] = $new_val === '1' 
            ? "Mode Maintenance berhasil <strong>diaktifkan</strong>. Akses user non-admin diblokir."
            : "Mode Maintenance berhasil <strong>dinonaktifkan</strong>. Sistem kembali beroperasi normal.";
        header('Location: ../dashboard_admin');
        exit();
    }

    // ==========================================
    // 5. TAMBAH AKUN KETUA TINGKAT BARU KE KELAS
    // ==========================================
    if ($action === 'tambah_ketua_tingkat') {
        $kelas_id       = (int)($_POST['kelas_id'] ?? 0);
        $ketua_nama     = trim($_POST['ketua_nama'] ?? '');
        $ketua_email    = trim($_POST['ketua_email'] ?? '');
        $ketua_password = trim($_POST['ketua_password'] ?? '');
        $linked_nim     = trim($_POST['linked_student_nim'] ?? '');
        $linked_nim     = !empty($linked_nim) ? $linked_nim : null;

        if ($kelas_id <= 0) {
            $_SESSION['error'] = 'Pilih kelas yang valid untuk penunjukan Ketua Tingkat!';
            header('Location: ../dashboard_admin');
            exit();
        }

        if (empty($ketua_nama) || empty($ketua_email) || empty($ketua_password)) {
            $_SESSION['error'] = 'Nama Lengkap, Email Login, dan Password untuk Ketua Tingkat baru wajib diisi!';
            header('Location: ../dashboard_admin');
            exit();
        }

        if (!filter_var($ketua_email, FILTER_VALIDATE_EMAIL)) {
            $_SESSION['error'] = 'Format email Ketua Tingkat tidak valid!';
            header('Location: ../dashboard_admin');
            exit();
        }

        // Cek keunikan email
        $stmt_u = mysqli_prepare($koneksi, "SELECT id FROM users WHERE email = ? LIMIT 1");
        mysqli_stmt_bind_param($stmt_u, "s", $ketua_email);
        mysqli_stmt_execute($stmt_u);
        if (mysqli_num_rows(mysqli_stmt_get_result($stmt_u)) > 0) {
            $_SESSION['error'] = "Email '$ketua_email' sudah digunakan oleh akun lain!";
            header('Location: ../dashboard_admin');
            exit();
        }

        // Insert Ketua Tingkat murni ke users
        $stmt_user = mysqli_prepare($koneksi, "INSERT INTO users (email, password, role, nama_lengkap, linked_student_nim, kelas_id) VALUES (?, ?, 'Ketua Tingkat', ?, ?, ?)");
        mysqli_stmt_bind_param($stmt_user, "ssssi", $ketua_email, $ketua_password, $ketua_nama, $linked_nim, $kelas_id);
        if (!mysqli_stmt_execute($stmt_user)) {
            $_SESSION['error'] = 'Gagal membuat akun Ketua Tingkat: ' . mysqli_error($koneksi);
            header('Location: ../dashboard_admin');
            exit();
        }
        $new_user_id = mysqli_insert_id($koneksi);

        // Update ketua_tingkat_user_id pada kelas
        mysqli_query($koneksi, "UPDATE kelas SET ketua_tingkat_user_id = $new_user_id WHERE id = $kelas_id");

        $_SESSION['success'] = "Akun Ketua Tingkat (<strong>" . htmlspecialchars($ketua_nama) . "</strong> - <code>" . htmlspecialchars($ketua_email) . "</code>) berhasil dibuat dan ditugaskan ke kelas!";
        header('Location: ../dashboard_admin');
        exit();
    }
}

// Fallback jika bukan POST
header('Location: ../dashboard_admin');
exit();

