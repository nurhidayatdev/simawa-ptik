<?php
require_once __DIR__ . '/env_loader.php';

// Fonnte Configuration
if (!defined('FONNTE_TOKEN')) {
    define('FONNTE_TOKEN', env('FONNTE_TOKEN'));
}
if (!defined('FONNTE_GROUP_ID')) {
    define('FONNTE_GROUP_ID', env('FONNTE_GROUP_ID'));
}
if (!defined('FONNTE_ANON_GROUP_ID')) {
    define('FONNTE_ANON_GROUP_ID', env('FONNTE_ANON_GROUP_ID'));
}
if (!defined('FONNTE_TUGAS_GROUP_ID')) {
    define('FONNTE_TUGAS_GROUP_ID', env('FONNTE_TUGAS_GROUP_ID'));
}

if (!function_exists('getKelasWhatsAppConfig')) {
    /**
     * Ambil konfigurasi WhatsApp per kelas (Target Group, Link, Token, Endpoint).
     * Jika tidak dikonfigurasi khusus pada kelas, fallback ke konfigurasi default .env.
     */
    function getKelasWhatsAppConfig($db = null, $kelas_id = null)
    {
        if ($db === null && isset($GLOBALS['koneksi'])) {
            $db = $GLOBALS['koneksi'];
        }

        if (empty($kelas_id) && function_exists('getKelasScope')) {
            $kelas_id = getKelasScope();
        }

        $default_target   = defined('FONNTE_GROUP_ID') ? (FONNTE_GROUP_ID ?: '') : '';
        $default_token    = defined('FONNTE_TOKEN') ? (FONNTE_TOKEN ?: '') : '';
        $default_endpoint = 'https://api.fonnte.com/send';

        if (!empty($kelas_id) && $db) {
            $stmt = mysqli_prepare($db, "SELECT id, nama_kelas, wa_group_id, wa_link_group, wa_token, wa_endpoint FROM kelas WHERE id = ? LIMIT 1");
            if ($stmt) {
                mysqli_stmt_bind_param($stmt, "i", $kelas_id);
                mysqli_stmt_execute($stmt);
                $res = mysqli_stmt_get_result($stmt);
                if ($row = mysqli_fetch_assoc($res)) {
                    return [
                        'kelas_id'   => (int)$row['id'],
                        'nama_kelas' => $row['nama_kelas'],
                        'target'     => !empty($row['wa_group_id']) ? trim($row['wa_group_id']) : $default_target,
                        'token'      => !empty($row['wa_token']) ? trim($row['wa_token']) : $default_token,
                        'endpoint'   => !empty($row['wa_endpoint']) ? trim($row['wa_endpoint']) : $default_endpoint,
                        'link_group' => !empty($row['wa_link_group']) ? trim($row['wa_link_group']) : ''
                    ];
                }
            }
        }

        return [
            'kelas_id'   => null,
            'nama_kelas' => 'Default Sistem',
            'target'     => $default_target,
            'token'      => $default_token,
            'endpoint'   => $default_endpoint,
            'link_group' => ''
        ];
    }
}

if (!function_exists('sendFonnteMessage')) {
    function sendFonnteMessage($message, $target = null, $token = null, $endpoint = null, $kelas_id = null)
    {
        $db = $GLOBALS['koneksi'] ?? null;
        $config = getKelasWhatsAppConfig($db, $kelas_id);

        $final_target   = !empty($target) ? $target : $config['target'];
        $final_token    = !empty($token) ? $token : $config['token'];
        $final_endpoint = !empty($endpoint) ? $endpoint : $config['endpoint'];

        if (empty($final_target) || empty($final_token)) {
            return [
                'success' => false,
                'error'   => 'Target Grup WhatsApp atau Token belum dikonfigurasi untuk kelas ini/sistem.',
                'code'    => 400
            ];
        }

        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => $final_endpoint,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => array(
                'target' => $final_target,
                'message' => $message,
                'countryCode' => '62',
            ),
            CURLOPT_HTTPHEADER => array(
                'Authorization: ' . $final_token
            ),
        ));

        $response_raw = curl_exec($curl);
        $http_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        $curl_err = curl_error($curl);
        curl_close($curl);

        if ($curl_err) {
            return ['success' => false, 'error' => 'cURL Error: ' . $curl_err, 'code' => $http_code];
        }

        if ($http_code == 200) {
            return ['success' => true, 'response' => json_decode($response_raw, true)];
        } else {
            return ['success' => false, 'error' => 'WhatsApp Gateway Error: ' . $response_raw, 'code' => $http_code];
        }
    }
}

// Deprecated: Replaced by sendFonnteMessage
if (!function_exists('sendWhatsAppMessage')) {
    function sendWhatsAppMessage($token, $chat_id, $message)
    {
        return sendFonnteMessage($message);
    }
}

/**
 * Mengembalikan ID kelas yang aktif untuk scoping kueri.
 * - Untuk role non-admin: mengembalikan kelas_id dari sesi pengguna.
 * - Untuk Administrator Utama: mengembalikan filter kelas jika dipilih via GET/SESSION, atau null (semua kelas).
 *
 * @return int|null
 */
if (!function_exists('getKelasScope')) {
    function getKelasScope()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $role = $_SESSION['role'] ?? '';

        if ($role === 'Administrator Utama') {
            if (isset($_GET['filter_kelas']) && !empty($_GET['filter_kelas'])) {
                return (int)$_GET['filter_kelas'];
            }
            if (isset($_SESSION['admin_selected_kelas_id']) && !empty($_SESSION['admin_selected_kelas_id'])) {
                return (int)$_SESSION['admin_selected_kelas_id'];
            }
            return null; // Null = global / seluruh kelas
        }

        if (isset($_SESSION['kelas_id']) && !empty($_SESSION['kelas_id'])) {
            return (int)$_SESSION['kelas_id'];
        }

        return 1; // Default fallback ke kelas ID 1
    }
}

/**
 * Menghasilkan potongan klausa WHERE SQL untuk membatasi data berdasarkan kelas_id.
 * Contoh output: " AND `m`.`kelas_id` = 1" atau "`m`.`kelas_id` = 1" atau "" (jika Admin melihat semua)
 *
 * @param string $tableAlias Alias tabel (misal: 'm', 'k', 'a')
 * @param bool $prefixWithAnd Apakah diawali dengan ' AND '
 * @return string
 */
if (!function_exists('getKelasWhereClause')) {
    function getKelasWhereClause($tableAlias = '', $prefixWithAnd = true)
    {
        $scope = getKelasScope();
        if ($scope === null) {
            return ""; // Tanpa batasan kelas untuk admin yang melihat semua kelas
        }

        // Defensive: Jika parameter memuat nama kolom (misal: 'm.kelas_id'), ambil alias tabel 'm' saja
        if (strpos($tableAlias, '.') !== false) {
            $parts = explode('.', $tableAlias);
            $tableAlias = $parts[0];
        }

        $column = !empty($tableAlias) ? "`$tableAlias`.`kelas_id`" : "`kelas_id`";
        $clause = "$column = $scope";

        return $prefixWithAnd ? " AND $clause" : $clause;
    }
}

/**
 * Guard otorisasi: memastikan user non-admin tidak mengakses atau memanipulasi data kelas lain.
 *
 * @param int $targetKelasId ID kelas yang ingin diakses
 * @return void
 */
if (!function_exists('requireKelasAccess')) {
    function requireKelasAccess($targetKelasId)
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $role = $_SESSION['role'] ?? '';
        if ($role === 'Administrator Utama') {
            return; // Admin diizinkan lintas kelas
        }

        $myKelasId = (int)($_SESSION['kelas_id'] ?? 1);
        if ((int)$targetKelasId !== $myKelasId) {
            http_response_code(403);
            if (file_exists(__DIR__ . '/../includes/alert_role.php')) {
                include __DIR__ . '/../includes/alert_role.php';
                exit;
            } else {
                die("Akses Ditolak: Anda tidak memiliki akses ke data kelas lain.");
            }
        }
    }
}

/**
 * Mengembalikan nama kelas aktif pengguna untuk keperluan tampilan header/badge.
 *
 * @return string
 */
if (!function_exists('getNamaKelasAktif')) {
    function getNamaKelasAktif()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (($_SESSION['role'] ?? '') === 'Administrator Utama') {
            $scope = getKelasScope();
            return $scope ? "Kelas #$scope" : "Semua Kelas";
        }

        return $_SESSION['nama_kelas'] ?? 'PTIK C 2024';
    }
}

/**
 * Klausa WHERE SQL untuk memfilter data mahasiswa murni
 * (mengecualikan role fungsional Admin, Keti, Sekre, Bendahara, dan legacy dummy admin).
 *
 * @param string $tableAlias Alias tabel mahasiswa (misal: 'm')
 * @param string $userAlias Alias tabel users (misal: 'u')
 * @param bool $prefixWithAnd Apakah diawali dengan ' AND '
 * @return string
 */
if (!function_exists('getMahasiswaPureWhereClause')) {
    function getMahasiswaPureWhereClause($tableAlias = 'm', $userAlias = 'u', $prefixWithAnd = true)
    {
        $m = !empty($tableAlias) ? "$tableAlias." : "";
        $u = !empty($userAlias) ? "$userAlias." : "";
        $clause = "(({$u}role = 'Mahasiswa' OR ({$m}user_id IS NULL AND {$m}nama_lengkap NOT LIKE '%Administrator%')) AND ({$u}role NOT IN ('Administrator Utama', 'Ketua Tingkat', 'Sekretaris', 'Bendahara') OR {$u}role IS NULL))";
        return $prefixWithAnd ? " AND $clause" : $clause;
    }
}
