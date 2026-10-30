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

if (!function_exists('sendFonnteMessage')) {
    function sendFonnteMessage($message, $target = FONNTE_GROUP_ID)
    {
        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => 'https://api.fonnte.com/send',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => array(
                'target' => $target,
                'message' => $message,
                'countryCode' => '62', // Optional
            ),
            CURLOPT_HTTPHEADER => array(
                'Authorization: ' . FONNTE_TOKEN
            ),
        ));

        $response_raw = curl_exec($curl);
        $http_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        curl_close($curl);

        if ($http_code == 200) {
            return ['success' => true, 'response' => json_decode($response_raw, true)];
        } else {
            return ['success' => false, 'error' => 'Fonnte API Error: ' . $response_raw, 'code' => $http_code];
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

        $column = $tableAlias ? "`$tableAlias`.`kelas_id`" : "`kelas_id`";
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
