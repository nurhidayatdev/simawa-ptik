<?php
require_once __DIR__ . '/functions/env_loader.php';

$host = env('DB_HOST');
$user = env('DB_USERNAME');
$pass = env('DB_PASSWORD');
$db   = env('DB_DATABASE');

$koneksi = mysqli_connect($host, $user, $pass, $db);


// Set Timezone
date_default_timezone_set('Asia/Makassar');
@mysqli_query($koneksi, "SET time_zone = '+08:00'");

// Maintenance Mode Helper & Auto Enforcement
require_once __DIR__ . '/functions/maintenance.php';
if ($koneksi) {
    init_settings_table($koneksi);
    enforce_maintenance_mode($koneksi);
}

