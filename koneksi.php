<?php
$host = "localhost";
$user = "root";
$pass = "";
$db   = "ptikc24";

$koneksi = mysqli_connect($host, $user, $pass, $db);


// Set Timezone
date_default_timezone_set('Asia/Makassar');
@mysqli_query($koneksi, "SET time_zone = '+08:00'");
