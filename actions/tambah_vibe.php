<?php
session_start();
include '../koneksi.php';

// Cek apakah user sudah login
if (!isset($_SESSION['user_id'])) {
    header("Location: ../index");
    exit;
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $user_id = $_SESSION['user_id'];

    // 2. Ambil data dari FORM
    if (!isset($_POST['mood'])) {
        header("Location: ../dashboard?error=no_mood");
        exit;
    }

    $mood = mysqli_real_escape_string($koneksi, $_POST['mood']);

    // 3. Cek apakah user sudah input hari ini
    $today = date('Y-m-d');
    $check = mysqli_query($koneksi, "SELECT id FROM vibe_check WHERE user_id = '$user_id' AND DATE(created_at) = '$today'");

    if (mysqli_num_rows($check) == 0) {
        $query = "INSERT INTO vibe_check (user_id, mood, created_at) VALUES ('$user_id', '$mood', NOW())";
        mysqli_query($koneksi, $query);
    }

    header("Location: ../dashboard?vibe_success=1");
    exit;
}
