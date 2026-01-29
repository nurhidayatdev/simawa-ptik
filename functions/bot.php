<?php
header('Content-Type: application/json');

// Mengambil data pesan masuk dari Fonnte
$json = file_get_contents('php://input');
$data = json_decode($json, true);

$pesan = $data['message']; // Isi pesan yang dikirim user
$sender = $data['sender']; // Nomor pengirim atau ID grup

// Logika Command
if ($pesan == "/halo") {
    $reply = [
        "reply" => "Halo juga! Ada yang bisa saya bantu di grup ini?"
    ];
} elseif ($pesan == "/jam") {
    $reply = [
        "reply" => "Sekarang pukul: " . date("H:i:s")
    ];
} else {
    // Jika tidak ada command, tidak perlu balas apa-apa
    exit;
}

echo json_encode($reply);