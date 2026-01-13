<?php
$host = "localhost";
$user = "root";
$pass = "";
$db   = "ptikc_class";

$koneksi = mysqli_connect($host, $user, $pass, $db);

if (!$koneksi) {
    die("Koneksi database gagal: " . mysqli_connect_error());
}

// Set Timezone
date_default_timezone_set('Asia/Makassar');
@mysqli_query($koneksi, "SET time_zone = '+08:00'");
