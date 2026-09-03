<?php

if (!function_exists('init_settings_table')) {
    /**
     * Inisialisasi tabel settings jika belum ada
     */
    function init_settings_table($koneksi)
    {
        if (!$koneksi) return;
        $sql = "CREATE TABLE IF NOT EXISTS settings (
            setting_key VARCHAR(50) PRIMARY KEY,
            setting_value TEXT,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
        @mysqli_query($koneksi, $sql);
    }
}

if (!function_exists('get_setting')) {
    /**
     * Mengambil nilai setting berdasarkan key
     */
    function get_setting($koneksi, $key, $default = '')
    {
        if (!$koneksi) return $default;
        $stmt = mysqli_prepare($koneksi, "SELECT setting_value FROM settings WHERE setting_key = ? LIMIT 1");
        if (!$stmt) return $default;
        mysqli_stmt_bind_param($stmt, "s", $key);
        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);
        if ($row = mysqli_fetch_assoc($res)) {
            mysqli_stmt_close($stmt);
            return $row['setting_value'];
        }
        mysqli_stmt_close($stmt);
        return $default;
    }
}

if (!function_exists('set_setting')) {
    /**
     * Menyimpan/memperbarui nilai setting berdasarkan key
     */
    function set_setting($koneksi, $key, $value)
    {
        if (!$koneksi) return false;
        $stmt = mysqli_prepare($koneksi, "INSERT INTO settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)");
        if (!$stmt) return false;
        mysqli_stmt_bind_param($stmt, "ss", $key, $value);
        $success = mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
        return $success;
    }
}

if (!function_exists('is_maintenance_mode')) {
    /**
     * Mengecek apakah mode maintenance sedang aktif
     */
    function is_maintenance_mode($koneksi)
    {
        $val = get_setting($koneksi, 'maintenance_mode', '0');
        return ($val === '1' || strtolower($val) === 'true' || strtolower($val) === 'on');
    }
}

if (!function_exists('get_maintenance_message')) {
    /**
     * Mengambil pesan maintenance kustom
     */
    function get_maintenance_message($koneksi)
    {
        $default_msg = 'Sistem sedang dalam Mode Maintenance (Pemeliharaan). Hanya Administrator Utama yang diizinkan untuk login.';
        $msg = get_setting($koneksi, 'maintenance_message', $default_msg);
        return !empty(trim($msg)) ? $msg : $default_msg;
    }
}

if (!function_exists('enforce_maintenance_mode')) {
    /**
     * Memeriksa sesi pengguna aktif dan meng-logout jika mode maintenance aktif & pengguna bukan Administrator Utama
     */
    function enforce_maintenance_mode($koneksi)
    {
        if (session_status() === PHP_SESSION_NONE) {
            @session_start();
        }

        if (is_maintenance_mode($koneksi)) {
            if (isset($_SESSION['user_id']) && ($_SESSION['role'] ?? '') !== 'Administrator Utama') {
                // Logout user
                unset($_SESSION['user_id']);
                unset($_SESSION['role']);
                unset($_SESSION['email']);
                unset($_SESSION['nama_lengkap']);
                unset($_SESSION['foto']);
                
                $_SESSION['maintenance_blocked'] = get_maintenance_message($koneksi);
                
                $script = $_SERVER['SCRIPT_NAME'] ?? '';
                if (strpos($script, 'login.php') === false && strpos($script, 'login') === false) {
                    $base_url = function_exists('env') ? env('APP_URL', '/') : '/ptikc24/';
                    // Adjust base path if needed
                    header("Location: " . rtrim($base_url, '/') . "/login?maintenance=1");
                    exit();
                }
            }
        }
    }
}
