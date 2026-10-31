<?php
session_start();
include '../koneksi.php';
require_once '../functions/helpers.php';

// Proteksi Akses: Hanya Ketua Tingkat dan Administrator Utama
if (!isset($_SESSION['user_id'])) {
    header("Location: ../index");
    exit();
}

$user_role = $_SESSION['role'] ?? '';
if ($user_role !== 'Ketua Tingkat' && $user_role !== 'Administrator Utama') {
    include '../includes/alert_role.php';
    exit();
}

$kelas_id = getKelasScope();
if (!$kelas_id && $user_role !== 'Administrator Utama') {
    $_SESSION['error'] = "Akun Anda belum terikat dengan kelas manapun. Hubungi Administrator.";
    header("Location: ../dashboard");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // ==========================================
    // 1. TAMBAH MAHASISWA BARU KE KELAS
    // ==========================================
    if ($action === 'tambah_mahasiswa') {
        $nama_lengkap   = trim($_POST['nama_lengkap'] ?? '');
        $nama_panggilan = trim($_POST['nama_panggilan'] ?? '');
        $nim            = trim($_POST['nim'] ?? '');
        $jenis_kelamin  = $_POST['jenis_kelamin'] ?? 'Laki-laki';
        $bio            = trim($_POST['bio'] ?? '');
        $instagram      = trim($_POST['instagram'] ?? '');

        if (empty($nama_lengkap) || empty($nim)) {
            $_SESSION['error'] = "Nama lengkap dan NIM wajib diisi!";
            header("Location: ../dashboard_keti");
            exit();
        }

        // Cek duplikasi NIM di seluruh sistem
        $stmt_check = mysqli_prepare($koneksi, "SELECT id FROM mahasiswa WHERE nim = ? LIMIT 1");
        mysqli_stmt_bind_param($stmt_check, "s", $nim);
        mysqli_stmt_execute($stmt_check);
        if (mysqli_num_rows(mysqli_stmt_get_result($stmt_check)) > 0) {
            $_SESSION['error'] = "Mahasiswa dengan NIM <strong>$nim</strong> sudah terdaftar di sistem!";
            header("Location: ../dashboard_keti");
            exit();
        }

        // Handle upload foto jika ada
        $foto = '';
        if (!empty($_FILES['foto']['name']) && $_FILES['foto']['error'] === UPLOAD_ERR_OK) {
            $allowed_ext = ['jpg', 'jpeg', 'png', 'webp'];
            $file_ext = strtolower(pathinfo($_FILES['foto']['name'], PATHINFO_EXTENSION));
            if (in_array($file_ext, $allowed_ext)) {
                $target_dir = "../storage/profile/";
                if (!file_exists($target_dir)) {
                    mkdir($target_dir, 0777, true);
                }
                $foto = time() . "_" . preg_replace('/[^a-zA-Z0-9._-]/', '', basename($_FILES['foto']['name']));
                move_uploaded_file($_FILES['foto']['tmp_name'], $target_dir . $foto);
            }
        }

        // Opsi: Buat akun login role Mahasiswa jika email diisi
        $email_login = trim($_POST['email'] ?? '');
        $pass_login  = trim($_POST['password'] ?? '');

        if (!empty($email_login)) {
            if (!filter_var($email_login, FILTER_VALIDATE_EMAIL)) {
                $_SESSION['error'] = "Format email login mahasiswa tidak valid!";
                header("Location: ../dashboard_keti");
                exit();
            }

            // Cek keunikan email di users
            $stmt_em = mysqli_prepare($koneksi, "SELECT id FROM users WHERE email = ? LIMIT 1");
            mysqli_stmt_bind_param($stmt_em, "s", $email_login);
            mysqli_stmt_execute($stmt_em);
            if (mysqli_num_rows(mysqli_stmt_get_result($stmt_em)) > 0) {
                $_SESSION['error'] = "Email <strong>$email_login</strong> sudah terdaftar di sistem! Gunakan email lain.";
                header("Location: ../dashboard_keti");
                exit();
            }
        }

        // Simpan data mahasiswa otomatis dengan kelas_id sesi Ketua Tingkat
        $stmt_ins = mysqli_prepare($koneksi, "
            INSERT INTO mahasiswa (kelas_id, nim, nama_lengkap, nama_panggilan, jenis_kelamin, bio, instagram, foto)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ");
        mysqli_stmt_bind_param($stmt_ins, "isssssss", $kelas_id, $nim, $nama_lengkap, $nama_panggilan, $jenis_kelamin, $bio, $instagram, $foto);

        if (mysqli_stmt_execute($stmt_ins)) {
            $new_mhs_id = mysqli_insert_id($koneksi);

            // Jika email login diisi, buat akun di tabel users dengan role 'Mahasiswa'
            if (!empty($email_login)) {
                if (empty($pass_login)) {
                    $pass_login = "simawa2024";
                }
                $stmt_u = mysqli_prepare($koneksi, "INSERT INTO users (email, password, role, kelas_id) VALUES (?, ?, 'Mahasiswa', ?)");
                mysqli_stmt_bind_param($stmt_u, "ssi", $email_login, $pass_login, $kelas_id);
                if (mysqli_stmt_execute($stmt_u)) {
                    $new_uid = mysqli_insert_id($koneksi);
                    mysqli_query($koneksi, "UPDATE mahasiswa SET user_id = $new_uid WHERE id = $new_mhs_id");
                }
            }

            $success_text = "Mahasiswa <strong>" . htmlspecialchars($nama_lengkap) . "</strong> (" . htmlspecialchars($nim) . ") berhasil ditambahkan ke kelas Anda!";
            if (!empty($email_login)) {
                $success_text .= " Akun login Mahasiswa (<code>$email_login</code>) telah aktif.";
            }
            $_SESSION['success'] = $success_text;
        } else {
            $_SESSION['error'] = "Gagal menambahkan mahasiswa: " . mysqli_error($koneksi);
        }

        header("Location: ../dashboard_keti");
        exit();
    }

    // ==========================================
    // 2. TUNJUK / BUAT AKUN PENGURUS KELAS
    // ==========================================
    if ($action === 'tunjuk_pengurus') {
        $mahasiswa_id  = (int)($_POST['mahasiswa_id'] ?? 0);
        $role_pengurus = trim($_POST['role_pengurus'] ?? '');
        $email         = trim($_POST['email'] ?? '');
        $password      = trim($_POST['password'] ?? '');

        // Whitelist role: Ketua Tingkat DILARANG membuat Administrator Utama atau Ketua Tingkat lain
        $allowed_roles = ['Sekretaris', 'Bendahara'];
        if (!in_array($role_pengurus, $allowed_roles)) {
            $_SESSION['error'] = "Role kepengurusan tidak valid! Anda hanya dapat menunjuk Sekretaris atau Bendahara.";
            header("Location: ../dashboard_keti");
            exit();
        }

        if ($mahasiswa_id <= 0) {
            $_SESSION['error'] = "Pilih mahasiswa yang valid dari kelas Anda!";
            header("Location: ../dashboard_keti");
            exit();
        }

        // Verifikasi mahasiswa benar-benar milik kelas_id Ketua Tingkat
        $stmt_mhs = mysqli_prepare($koneksi, "SELECT id, user_id, nama_lengkap, nim FROM mahasiswa WHERE id = ? AND kelas_id = ? LIMIT 1");
        mysqli_stmt_bind_param($stmt_mhs, "ii", $mahasiswa_id, $kelas_id);
        mysqli_stmt_execute($stmt_mhs);
        $res_mhs = mysqli_stmt_get_result($stmt_mhs);
        $mhs = mysqli_fetch_assoc($res_mhs);

        if (!$mhs) {
            $_SESSION['error'] = "Mahasiswa tidak ditemukan di kelas Anda atau akses ditolak!";
            header("Location: ../dashboard_keti");
            exit();
        }

        // Default password jika dikosongkan
        if (empty($password)) {
            $password = "simawa2024";
        }

        // Prinsip Satu-Akun-Satu-Role: Akun pengurus adalah akun fungsional baru murni
        // tidak mengubah atau merangkap akun Mahasiswa yang sudah ada.
        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $_SESSION['error'] = "Format email login untuk akun pengurus wajib valid!";
            header("Location: ../dashboard_keti");
            exit();
        }

        // Cek keunikan email
        $stmt_chk_em = mysqli_prepare($koneksi, "SELECT id FROM users WHERE email = ? LIMIT 1");
        mysqli_stmt_bind_param($stmt_chk_em, "s", $email);
        mysqli_stmt_execute($stmt_chk_em);
        if (mysqli_num_rows(mysqli_stmt_get_result($stmt_chk_em)) > 0) {
            $_SESSION['error'] = "Email <strong>$email</strong> sudah terdaftar di sistem. Gunakan email lain!";
            header("Location: ../dashboard_keti");
            exit();
        }

        // Insert akun fungsional baru ke users (lengkap dengan nama_lengkap & linked_student_nim)
        $stmt_ins_u = mysqli_prepare($koneksi, "INSERT INTO users (email, password, role, nama_lengkap, linked_student_nim, kelas_id) VALUES (?, ?, ?, ?, ?, ?)");
        mysqli_stmt_bind_param($stmt_ins_u, "sssssi", $email, $password, $role_pengurus, $mhs['nama_lengkap'], $mhs['nim'], $kelas_id);
        if (mysqli_stmt_execute($stmt_ins_u)) {
            $_SESSION['success'] = "Akun fungsional pengurus <strong>$role_pengurus</strong> untuk <strong>" . htmlspecialchars($mhs['nama_lengkap']) . "</strong> (NIM: {$mhs['nim']}) berhasil dibuat! Email: <code>$email</code>";
        } else {
            $_SESSION['error'] = "Gagal membuat akun pengurus: " . mysqli_error($koneksi);
        }

        header("Location: ../dashboard_keti");
        exit();
    }

    // ==========================================
    // 3. RESET PASSWORD PENGURUS KELAS
    // ==========================================
    if ($action === 'reset_password_pengurus') {
        $target_user_id = (int)($_POST['user_id'] ?? 0);
        $new_password   = trim($_POST['new_password'] ?? '');

        if ($target_user_id <= 0) {
            $_SESSION['error'] = "User tidak valid!";
            header("Location: ../dashboard_keti");
            exit();
        }

        if (empty($new_password)) {
            $new_password = "simawa2024";
        }

        // Verifikasi target user berada di kelas yang sama dan rolenya BUKAN Administrator / Ketua Tingkat
        $stmt_target = mysqli_prepare($koneksi, "SELECT id, email, role FROM users WHERE id = ? AND kelas_id = ? LIMIT 1");
        mysqli_stmt_bind_param($stmt_target, "ii", $target_user_id, $kelas_id);
        mysqli_stmt_execute($stmt_target);
        $res_target = mysqli_stmt_get_result($stmt_target);
        $target = mysqli_fetch_assoc($res_target);

        if (!$target || in_array($target['role'], ['Administrator Utama', 'Ketua Tingkat'])) {
            $_SESSION['error'] = "Aksi ditolak: Anda tidak memiliki wewenang untuk mereset password akun ini.";
            header("Location: ../dashboard_keti");
            exit();
        }

        $stmt_up_pwd = mysqli_prepare($koneksi, "UPDATE users SET password = ? WHERE id = ? AND kelas_id = ?");
        mysqli_stmt_bind_param($stmt_up_pwd, "sii", $new_password, $target_user_id, $kelas_id);

        if (mysqli_stmt_execute($stmt_up_pwd)) {
            $_SESSION['success'] = "Password untuk akun <strong>" . htmlspecialchars($target['email']) . "</strong> berhasil direset menjadi: <code>" . htmlspecialchars($new_password) . "</code>";
        } else {
            $_SESSION['error'] = "Gagal mereset password: " . mysqli_error($koneksi);
        }

        header("Location: ../dashboard_keti");
        exit();
    }

    // ==========================================
    // 4. COPOT JABATAN PENGURUS KELAS
    // ==========================================
    if ($action === 'copot_jabatan') {
        $target_user_id = (int)($_POST['user_id'] ?? 0);

        if ($target_user_id <= 0) {
            $_SESSION['error'] = "User tidak valid!";
            header("Location: ../dashboard_keti");
            exit();
        }

        // Verifikasi target user di kelas yang sama dan rolenya adalah pengurus
        $stmt_target = mysqli_prepare($koneksi, "SELECT id, email, role FROM users WHERE id = ? AND kelas_id = ? LIMIT 1");
        mysqli_stmt_bind_param($stmt_target, "ii", $target_user_id, $kelas_id);
        mysqli_stmt_execute($stmt_target);
        $res_target = mysqli_stmt_get_result($stmt_target);
        $target = mysqli_fetch_assoc($res_target);

        if (!$target || !in_array($target['role'], ['Sekretaris', 'Bendahara'])) {
            $_SESSION['error'] = "Aksi ditolak: User ini bukan pengurus kelas Anda atau akses tidak diizinkan.";
            header("Location: ../dashboard_keti");
            exit();
        }

        // Hapus akun fungsional pengurus (akun Mahasiswa asli mahasiswa tetap aman)
        $stmt_del = mysqli_prepare($koneksi, "DELETE FROM users WHERE id = ? AND kelas_id = ? AND role IN ('Sekretaris', 'Bendahara')");
        mysqli_stmt_bind_param($stmt_del, "ii", $target_user_id, $kelas_id);

        if (mysqli_stmt_execute($stmt_del)) {
            $_SESSION['success'] = "Akun pengurus <strong>" . htmlspecialchars($target['email']) . "</strong> telah dicopot/dinonaktifkan.";
        } else {
            $_SESSION['error'] = "Gagal mencopot jabatan: " . mysqli_error($koneksi);
        }

        header("Location: ../dashboard_keti");
        exit();
    }
}

// Fallback jika bukan POST
header("Location: ../dashboard_keti");
exit();

