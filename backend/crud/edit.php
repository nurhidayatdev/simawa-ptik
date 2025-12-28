<?php
session_start();
include '../../koneksi.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../../index.php");
    exit;
}

$nama_lengkap = $_SESSION['nama_lengkap'];
$nama_panggilan = $_SESSION['nama_panggilan'];
$foto = $_SESSION['foto'];
$role = $_SESSION['role'];
$tabel = $_GET['tabel'] ?? '';
$id = $_GET['id'] ?? '';
$query = mysqli_query($koneksi, "SELECT * FROM $tabel WHERE id='$id'");
$data = mysqli_fetch_assoc($query);
if (!$data) {
    die("Data tidak ditemukan!");
}

if (isset($_POST['update'])) {

    $redirect = "";

    switch ($tabel) {

        case 'mahasiswa':
            $foto_baru = $data['foto']; // default foto lama
            $nama_panggilan = $_POST['nama_panggilan'] ?? $data['nama_panggilan'];

            // prefer client-side processed image in foto_data (base64)
            if (!empty($_POST['foto_data'])) {
                $foto_data = $_POST['foto_data'];
                // data URIs look like: data:image/jpeg;base64,....
                if (preg_match('/^data:(image\/[a-zA-Z]+);base64,/', $foto_data, $m)) {
                    $mime = $m[1];
                    $base64 = substr($foto_data, strpos($foto_data, ',') + 1);
                    $decoded = base64_decode($base64);
                    if ($decoded === false) {
                        die('Gagal decode gambar.');
                    }

                    // If GD functions available, use them to resize/convert
                    if (function_exists('imagecreatefromstring')) {
                        $src = @imagecreatefromstring($decoded);
                        if ($src === false) {
                            die('Format gambar tidak dikenali.');
                        }

                        // resize to 512x512
                        $size = 512;
                        $dst = imagecreatetruecolor($size, $size);
                        imagesavealpha($dst, true);
                        $trans_col = imagecolorallocatealpha($dst, 0, 0, 0, 127);
                        imagefill($dst, 0, 0, $trans_col);
                        $w = imagesx($src);
                        $h = imagesy($src);
                        imagecopyresampled($dst, $src, 0, 0, 0, 0, $size, $size, $w, $h);

                        // prefer webp if available
                        if (function_exists('imagewebp')) {
                            $ext = 'webp';
                            $foto_baru = $nama_panggilan . '.' . $ext;
                            imagewebp($dst, __DIR__ . "/../img/profile/" . $foto_baru, 80);
                        } else {
                            $ext = 'jpg';
                            $foto_baru = $nama_panggilan . '.' . $ext;
                            imagejpeg($dst, __DIR__ . "/../img/profile/" . $foto_baru, 85);
                        }

                        if (function_exists('imagedestroy')) { imagedestroy($src); imagedestroy($dst); }
                    } else {
                        // GD not available: save raw bytes
                        $ext = 'jpg';
                        if ($mime === 'image/webp') $ext = 'webp';
                        elseif ($mime === 'image/png') $ext = 'png';
                        $foto_baru = $nama_panggilan . '.' . $ext;
                        file_put_contents(__DIR__ . "/../img/profile/" . $foto_baru, $decoded);
                    }
                }

            } elseif (!empty($_FILES['foto']['name'])) {
                $nama_file = $_FILES['foto']['name'];
                $tmp_file  = $_FILES['foto']['tmp_name'];
                $ext       = strtolower(pathinfo($nama_file, PATHINFO_EXTENSION));

                $allowed = ['jpg', 'jpeg', 'png', 'webp'];

                if (in_array($ext, $allowed)) {
                    $foto_baru = $nama_panggilan . '.' . $ext;
                    $dest_path = __DIR__ . "/../img/profile/" . $foto_baru;
                    move_uploaded_file($tmp_file, $dest_path);
                    
                    // attempt to compress/resize if GD available
                    if (function_exists('imagecreatefromstring')) {
                        $src = @imagecreatefromstring(file_get_contents($dest_path));
                        if ($src !== false) {
                            $size = 512;
                            $dst = imagecreatetruecolor($size, $size);
                            imagesavealpha($dst, true);
                            $trans_col = imagecolorallocatealpha($dst, 0, 0, 0, 127);
                            imagefill($dst, 0, 0, $trans_col);
                            $w = imagesx($src);
                            $h = imagesy($src);
                            imagecopyresampled($dst, $src, 0, 0, 0, 0, $size, $size, $w, $h);
                            if (function_exists('imagewebp')) {
                                $foto_baru = $nama_panggilan . '.webp';
                                imagewebp($dst, __DIR__ . "/../img/profile/" . $foto_baru, 80);
                            } else {
                                imagejpeg($dst, $dest_path, 85);
                            }
                            if (function_exists('imagedestroy')) { imagedestroy($src); imagedestroy($dst); }
                        }
                    }
                } else {
                    die("Format foto tidak valid!");
                }
            }

            mysqli_query($koneksi, "UPDATE mahasiswa SET 
                    nama_lengkap='{$_POST['nama_lengkap']}',
                    nama_panggilan='{$_POST['nama_panggilan']}',
                    nim='{$_POST['nim']}',
                    bio='{$_POST['bio']}',
                    foto='$foto_baru'
                    WHERE id='$id'");
            $redirect = "../dashboard/db_mahasiswa.php";
            break;

        case 'mata_kuliah':
            mysqli_query($koneksi, "UPDATE mata_kuliah SET 
                    kode_matkul='{$_POST['kode_matkul']}',
                    nama_matkul='{$_POST['nama_matkul']}',
                    semester='{$_POST['semester']}',
                    sks='{$_POST['sks']}'
                    WHERE id='$id'");
            $redirect = "../dashboard/db_matkul.php";
            break;

        case 'users':
            mysqli_query($koneksi, "UPDATE users SET 
                    email='{$_POST['email']}',
                    password='{$_POST['password']}',
                    role='{$_POST['role']}'
                    WHERE id='$id'");
            $redirect = "../dashboard/db_users.php";
            break;

        case 'dosen':
            mysqli_query($koneksi, "UPDATE dosen SET 
                    nama_dosen='{$_POST['nama_dosen']}',
                    no_hp='{$_POST['no_hp']}'
                    WHERE id='$id'");
            $redirect = "../dashboard/db_dosen.php";
            break;

        case 'tugas':
            // Handle file upload - keep old file if no new file uploaded
            $file_tugas = $data['file_tugas']; // default to existing file
            if (!empty($_FILES['file_tugas']['name'])) {
                $target_dir = "../files/tugas/";
                // Create directory if not exists
                if (!is_dir($target_dir)) {
                    mkdir($target_dir, 0777, true);
                }
                $file_tugas = time() . "_" . basename($_FILES["file_tugas"]["name"]);
                $target_file = $target_dir . $file_tugas;
                move_uploaded_file($_FILES["file_tugas"]["tmp_name"], $target_file);
            }
            
            // Get nama mata kuliah untuk notifikasi
            $q_matkul = mysqli_query($koneksi, "SELECT nama_matkul FROM mata_kuliah WHERE id = '{$_POST['matkul_id']}'");
            $matkul_data = mysqli_fetch_assoc($q_matkul);
            $nama_matkul = $matkul_data['nama_matkul'] ?? 'Unknown';
            
            mysqli_query($koneksi, "UPDATE tugas SET 
                    matkul_id='{$_POST['matkul_id']}',
                    deadline_tanggal='{$_POST['deadline_tanggal']}',
                    deadline_pukul='{$_POST['deadline_pukul']}',
                    deskripsi='{$_POST['deskripsi']}',
                    file_tugas='$file_tugas',
                    link_kirim='{$_POST['link_kirim']}'
                    WHERE id='$id'");
            
            // =====================
            // TELEGRAM NOTIFICATION
            // =====================
            $telegram_token = "8212322367:AAGCGc4TG__EnsEd-20wFTI_s2adp-dp1OY";
            $telegram_chat_id = "-1003577478223";
            
            // Format pesan
            $message = "✏️ *TUGAS TELAH DIPERBARUI!*\n\n";
            $message .= "📖 *Mata Kuliah:* " . $nama_matkul . "\n";
            $message .= "📅 *Deadline:* " . $_POST['deadline_tanggal'] . " pukul " . $_POST['deadline_pukul'] . "\n";
            $message .= "📝 *Deskripsi:* " . ($_POST['deskripsi'] ?: '-') . "\n";
            if (!empty($_POST['link_kirim'])) {
                $message .= "🔗 *Link Kirim:* " . $_POST['link_kirim'] . "\n";
            }
            $message .= "\n_Perhatikan perubahan tugas ini!_ ⚠️";
            
            // Kirim ke Telegram (Resilient DNS Bypass Method)
            $tg_ip = "149.154.167.220";
            $telegram_url = "https://{$tg_ip}/bot{$telegram_token}/sendMessage";
            $telegram_data = [
                'chat_id' => $telegram_chat_id,
                'text' => $message,
                'parse_mode' => 'Markdown'
            ];
            
            // Try Method 1: CURL (DNS Bypass)
            $sent = false;
            if (function_exists('curl_init')) {
                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, $telegram_url);
                curl_setopt($ch, CURLOPT_POST, true);
                curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($telegram_data));
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
                curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
                curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
                curl_setopt($ch, CURLOPT_TIMEOUT, 20);
                curl_setopt($ch, CURLOPT_USERAGENT, 'PTIKC24_Bot/1.0');
                curl_setopt($ch, CURLOPT_HTTPHEADER, ['Host: api.telegram.org']); // DNS Bypass
                $res = curl_exec($ch);
                $info = curl_getinfo($ch);
                curl_close($ch);
                if ($info['http_code'] == 200) $sent = true;
            }
            
            // Try Method 2: FGC (Fallback DNS Bypass)
            if (!$sent && ini_get('allow_url_fopen')) {
                $opts = [
                    'http' => [
                        'method' => 'POST',
                        'header' => "Content-Type: application/x-www-form-urlencoded\r\n" .
                                   "Host: api.telegram.org\r\n" . 
                                   "User-Agent: PTIKC24_Bot/1.0\r\n",
                        'content' => http_build_query($telegram_data),
                        'timeout' => 15
                    ],
                    'ssl' => ['verify_peer' => false, 'verify_peer_name' => false]
                ];
                $ctx = stream_context_create($opts);
                $res_fgc = @file_get_contents($telegram_url, false, $ctx);
                if ($res_fgc !== FALSE) $sent = true;
            }
            
            $redirect = "../dashboard/db_tugas.php";
            break;

            case 'jadwal_matkul':
            mysqli_query($koneksi, "UPDATE jadwal_matkul SET 
                    matkul_id='{$_POST['matkul_id']}',
                    hari='{$_POST['hari']}',
                    jam_ke='{$_POST['jam_ke']}',
                    jam_mulai='{$_POST['jam_mulai']}',
                    jam_selesai='{$_POST['jam_selesai']}',
                    ruangan='{$_POST['ruangan']}',
                    dosen_pengampu_id='{$_POST['dosen_pengampu_id']}',
                    dosen_mitra_id='{$_POST['dosen_mitra_id']}'
                    WHERE id='$id'");
            $redirect = "../dashboard/db_jadwal.php";
            break;
    }

    echo "
    <!DOCTYPE html>
    <html>
    <head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>Dashboard - SD Inpres Maccini Sombala 1</title>
    <link rel='icon' href='../img/main/icon.png' />
  <link rel='preconnect' href='https://fonts.googleapis.com'>
    <link rel='preconnect' href='https://fonts.gstatic.com' crossorigin>
    <link href='https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap' rel='stylesheet'>
    <style>
        body { font-family: 'Poppins', sans-serif !important; }
    </style>
        <script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script>
    </head>
    <body>
        <script>
   Swal.fire({
    title: 'Berhasil!',
    text: 'Data berhasil diperbarui!',
    icon: 'success',
    allowOutsideClick: false,
    allowEscapeKey: false,
    confirmButtonText: 'OK'
}).then(() => {
    window.location.href = '$redirect';
});
</script>

    </body>
    </html>";
    exit;
}

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#6c27dc" />
    <link rel="icon" href="img/icon.png" />
    <title>Kelas PTIK C 2024 - Teknik Informatika dan Komputer</title>
    <link href="../../frontend/tailwind/src/output.css" rel="stylesheet">
    <link href="../../frontend/tailwind/src/input.css" rel="stylesheet">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&display=swap" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.5.13/cropper.min.css" rel="stylesheet">
    <style>
        .rotate-180 {
            transform: rotate(180deg);
        }

        @media (max-width: 768px) {
            .mobile-menu-open {
                transform: translateX(0) !important;
            }
        }
    </style>
</head>

<body class="bg-gray-100">
    <!-- Header dengan z-index lebih tinggi -->
    <header class="bg-indigo-950  fixed w-full top-0 z-50">
        <div class="flex justify-between items-center px-6 py-2">
            <div class="flex items-center">
                <img src="../../img/logo.png" alt="Logo Universitas Negeri Makassar" class="h-10 w-10 mr-3">
                <span class="text-xl font-bold text-white">Dashboard PTIK C</span>
            </div>
            <!-- Mobile Menu Button dengan z-index yang sesuai -->
            <button id="mobile-menu-button" class="text-slate-100 hover:text-indigo-950 lg:hidden p-2 rounded-lg hover:bg-gray-100">
                <i class="fa fa-bars w-6 h-5"></i>
            </button>
            <div class="hidden lg:flex items-center space-x-4">
                <div class="relative">
                    <button id="profile-button" class="flex items-center space-x-2">
                        <span class="text-white"><?= htmlspecialchars($nama_lengkap) ?></span>
                        <img src="../img/profile/<?= htmlspecialchars($foto) ?>" alt="Profile" class="w-8 h-8 rounded-full">
                    </button>
                    <div id="profile-dropdown" class="absolute right-0 mt-2 w-48 bg-white rounded-md shadow-lg hidden">
                        <a href="profile.php" class="block px-4 py-2 text-xs hover:bg-gray-100">Profile</a>
                        <a href="../../index.php" class="block px-4 py-2 text-xs hover:bg-gray-100">Logout</a>
                    </div>
                </div>
            </div>
        </div>
    </header>

    <aside id="sidebar"
        class="fixed left-0 top-0 h-screen w-48 bg-white transform -translate-x-full lg:translate-x-0 transition-transform duration-200 ease-in-out z-40">
        <!-- Tambahan padding top agar tidak tertutup header -->
        <div class="pt-16">
            <nav class="mt-6">
                <div class="px-4 space-y-2">
                    <!-- Dashboard Menu -->
                    <a href="dashboard.php" class="flex items-center px-4 py-2 text-slate-700  rounded-lg hover:bg-slate-100">
                        <i class="fa fa-home w-4 h-4 mr-4"></i>
                        <span class="text-xs">Beranda</span>
                    </a>

                    <!-- Components Menu -->
                    <div class="space-y-2">

                        <button
                            class="submenu-button flex items-center justify-between w-full px-4 py-2 text-slate-800 hover:bg-slate-100 rounded-lg">
                            <div class="flex items-center">
                                <i class="fa fa-list w-5 h-5 mr-4"></i>
                                <span class="text-xs">Akademik</span>
                            </div>
                            <i class="fa fa-chevron-down submenu-arrow w-4 h-4 transition-transform duration-200"></i>
                        </button>

                        <div class="submenu pl-8 space-y-1 hidden overflow-y-auto max-h-52">
                            <a href="db_mahasiswa.php"
                                class="block px-4 py-2 text-xs text-slate-800 hover:bg-slate-100 rounded-lg">Mahasiswa</a>

                            <a href="db_dosen.php"
                                class="block px-4 py-2 text-xs text-slate-800 hover:bg-slate-100 rounded-lg">Dosen</a>

                            <a href="db_matkul.php"
                                class="block px-4 py-2 text-xs text-slate-800 hover:bg-slate-100 rounded-lg">Mata Kuliah</a>

                            <a href="db_jadwal.php"
                                class="block px-4 py-2 text-xs text-slate-800 hover:bg-slate-100 rounded-lg">Jadwal</a>

                            <a href="db_tugas.php"
                                class="block px-4 py-2 text-xs text-slate-800 hover:bg-slate-100 rounded-lg">Tugas</a>


                        </div>

                        <button
                            class="flex items-center justify-between w-full px-4 py-2 text-slate-800 hover:bg-gray-100 rounded-lg">
                            <div class="flex items-center">
                                <i class="fa fa-calendar w-5 h-5 mr-4"></i>
                                <a href="db_absensi.php?matkul_id=33&pertemuan=1">
                                    <span class="text-xs">Absensi</span>
                                </a>
                            </div>
                        </button>

                        <button
                            class="flex items-center justify-between w-full px-4 py-2 text-slate-800 hover:bg-gray-100 rounded-lg">
                            <div class="flex items-center">
                                <i class="fa fa-wallet w-5 h-5 mr-4"></i>
                                <a href="db_kas.php?minggu_ke=1">
                                    <span class="text-xs">Kas Mingguan</span>
                                </a>
                            </div>
                        </button>



                        <button
                            class="flex items-center justify-between w-full px-4 py-2 text-slate-800 hover:bg-gray-100 rounded-lg">
                            <div class="flex items-center">
                                <i class="fa fa-users w-5 h-5 mr-4"></i>
                                <a href="db_kelompok.php">
                                    <span class="text-xs">Kelompok</span>
                                </a>
                            </div>
                        </button>
<?php if ($role === 'Administrator Utama'): ?>
                        <button
                            class="flex items-center justify-between w-full px-4 py-2 text-slate-800 hover:bg-gray-100 rounded-lg">
                            <div class="flex items-center">
                                <i class="fa fa-user w-5 h-5 mr-4"></i>
                                <a href="db_users.php">
                                    <span class="text-xs">Users</span>
                                </a>
                            </div>
                        </button>
<?php endif; ?>
                    </div>
                </div>
            </nav>
        </div>
    </aside>

    <main class="ml-0 lg:ml-48 pt-20 p-6 flex flex-col min-h-screen">
        <div class="bg-white rounded-lg border border-gray-200 p-6 flex-grow w-full">

            <form class="space-y-6" method="POST" enctype="multipart/form-data">
                <div class="border-b border-gray-200 pb-6">
                    <?php if ($tabel === 'mahasiswa'): ?>
                        <h3 class="text-xl font-bold mb-6">Edit Mahasiswa</h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label class="block text-xs font-medium text-gray-700 mb-1">Nama Lengkap</label>
                                <input type="text" name="nama_lengkap" value="<?= $data['nama_lengkap']; ?>"
                                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-1 focus:ring-gray-400 text-xs">
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-700 mb-1">Nama Panggilan</label>
                                <input type="text" name="nama_panggilan" value="<?= $data['nama_panggilan']; ?>"
                                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-1 focus:ring-gray-400 text-xs">
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-700 mb-1">NIM</label>
                                <input type="number" name="nim" value="<?= $data['nim']; ?>"
                                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-1 focus:ring-gray-400 text-xs">
                            </div>
                            <div class="space-y-4">
                                <label class="block text-xs font-medium text-gray-700 mb-1">Bio</label>
                                <input name="bio" value="<?= $data['bio']; ?>"
                                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-1 focus:ring-gray-400 text-xs"></input>
                            </div>
                            <div class="space-y-4">
                            <label class="block text-xs font-medium text-gray-700 mb-1">Foto</label>
                            <input type="file" id="foto-input" name="foto_file" accept="image/*" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-1 focus:ring-gray-400 text-sm">
                            <input type="hidden" name="foto_data" id="foto_data">
                            <input type="hidden" name="current_foto" value="<?= htmlspecialchars($data['foto']); ?>">
                            <br>
                            <div class="flex items-center space-x-4">
                                <img id="current-preview" src="../img/profile/<?php echo htmlspecialchars($data['foto']); ?>" class="img-fluid rounded" style="width:120px; height:120px; object-fit:cover;" />
                                <div>
                                    <button type="button" id="open-cropper" class="px-3 py-1 bg-indigo-200 rounded text-xs hidden">Buka Cropper</button>
                                </div>
                            </div>

                            <!-- Cropper modal-ish area -->
                            <div id="cropper-area" class="mt-4 hidden">
                                <div class="grid sm:grid-cols-2 gap-4 space-x-4">
                                    <div class="w-full flex align-center justify-center bg-slate-200">
                                        <img id="crop-image" style="max-width:100%; display:block;" />
                                    </div>
                                    <div class="space-y-2">
                                        <div class="border p-2 rounded" style="width:160px; height:160px; overflow:hidden;">
                                            <div class="preview-wrapper" style="width:100%; height:100%;">
                                                <img id="preview" style="width:100%; height:100%; object-fit:cover; display:block;" />
                                            </div>
                                        </div>
                                        <div class="flex space-x-2">
                                            <button type="button" id="zoom-in" class="px-3 py-1 bg-indigo-200 rounded">+</button>
                                            <button type="button" id="zoom-out" class="px-3 py-1 bg-indigo-200 rounded">-</button>
                                            <button type="button" id="rotate-left" class="px-3 py-1 bg-indigo-200 rounded">⟲</button>
                                            <button type="button" id="rotate-right" class="px-3 py-1 bg-indigo-200 rounded">⟳</button>
                                        </div>
                                        <div>
                                            <button type="button" id="apply-crop" class="mt-2 px-3 py-1 bg-indigo-700 text-white rounded text-sm">Gunakan Foto</button>
                                            <button type="button" id="cancel-crop" class="mt-2 ml-2 px-3 py-1 bg-indigo-200 rounded text-sm">Batal</button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        </div>

                    <?php elseif ($tabel === 'mata_kuliah'): ?>
                        <h3 class="text-xl font-bold mb-6">Edit Mata Kuliah</h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label class="block text-xs font-medium text-gray-700 mb-1">Kode Mata Kuliah</label>
                                <input type="text" name="kode_matkul" value="<?= $data['kode_matkul']; ?>"
                                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-1 focus:ring-gray-400 text-xs">
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-700 mb-1">Nama Mata Kuliah</label>
                                <input type="text" name="nama_matkul" value="<?= $data['nama_matkul']; ?>"
                                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-1 focus:ring-gray-400 text-xs">
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-700 mb-1">Semester</label>
                                <input type="number" name="semester" value="<?= $data['semester']; ?>"
                                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-1 focus:ring-gray-400 text-xs">
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-700 mb-1">SKS</label>
                                <input type="number" name="sks" value="<?= $data['sks']; ?>"
                                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-1 focus:ring-gray-400 text-xs">
                            </div>
                        </div>

                    <?php elseif ($tabel === 'users'): ?>
                        <h3 class="text-xl font-bold mb-6">Edit Users</h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label class="block text-xs font-medium text-gray-700 mb-1">Email</label>
                                <input type="text" name="email" value="<?= $data['email']; ?>"
                                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-1 focus:ring-gray-400 text-xs">
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-700 mb-1">Password</label>
                                <input type="text" name="password" value="<?= $data['password']; ?>"
                                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-1 focus:ring-gray-400 text-xs">
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-700 mb-1">Role</label>
                                <select name="role" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-xs" required>
                                    <option value="<?= $data['role']; ?>">-- Pilih Role/Peran --</option>
                                    <option value="administrator utama">Administrator Utama</option>
                                    <option value="ketua tingkat">Ketua Tingkat</option>
                                    <option value="sekretaris">Sekretaris</option>
                                    <option value="Bendahara">Bendahara</option>
                                </select>

                            </div>
                            <?php elseif ($tabel === 'dosen'): ?>
                        <h3 class="text-xl font-bold mb-6">Edit Dosen</h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label class="block text-xs font-medium text-gray-700 mb-1">Nama Dosen</label>
                                <input type="text" name="nama_dosen" value="<?= $data['nama_dosen']; ?>"
                                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-1 focus:ring-gray-400 text-xs">
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-700 mb-1">No HP</label>
                                <input type="text" name="no_hp" value="<?= $data['no_hp']; ?>"
                                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-1 focus:ring-gray-400 text-xs">
                            </div>
                        </div>
                        <?php elseif ($tabel === 'tugas'): ?>
                        <h3 class="text-xl font-bold mb-6">Edit Tugas</h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label class="block text-xs font-medium text-gray-700 mb-1">Mata Kuliah</label>
                                <select name="matkul_id" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-1 focus:ring-gray-400" required>
                                    <option value="">-- Pilih Mata Kuliah --</option>
                                    <?php
                                    $query_mk = mysqli_query($koneksi, "SELECT DISTINCT
                                        mk.id AS matkul_id,
                                        mk.nama_matkul
                                        FROM krs k
                                        JOIN mata_kuliah mk ON k.matkul_id = mk.id
                                        ORDER BY mk.nama_matkul ASC");
                                    while ($mk = mysqli_fetch_assoc($query_mk)) {
                                        echo "<option value='{$mk['matkul_id']}'>{$mk['nama_matkul']}</option>";
                                    }
                                    ?>
                                </select>
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-700 mb-1">Deadline Tanggal</label>
                                <input type="date" name="deadline_tanggal" value="<?= $data['deadline_tanggal']; ?>"
                                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-1 focus:ring-gray-400 text-xs">
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-700 mb-1">Deadline Pukul</label>
                                <input type="time" name="deadline_pukul" value="<?= $data['deadline_pukul']; ?>"
                                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-1 focus:ring-gray-400 text-xs">
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-700 mb-1">Deskripsi</label>
                                <input type="text" name="deskripsi" value="<?= $data['deskripsi']; ?>"
                                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-1 focus:ring-gray-400 text-xs">
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-700 mb-1">File Tugas</label>
                                <input type="file" name="file_tugas" value="<?= $data['file_tugas']; ?>"
                                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-1 focus:ring-gray-400 text-xs">
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-700 mb-1">Link Kirim</label>
                                <input type="text" name="link_kirim" value="<?= $data['link_kirim']; ?>"
                                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-1 focus:ring-gray-400 text-xs">
                            </div>
                        </div>
                        <?php elseif ($tabel === 'jadwal_matkul'): ?>
                        <h3 class="text-xl font-bold mb-6">Jadwal Mata Kuliah</h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label class="block text-xs font-medium text-gray-700 mb-1">Mata Kuliah</label>
                                <select name="matkul_id" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-1 focus:ring-gray-400 text-xs" required>
                                    <option value="<?= $data['matkul_id']; ?>">-- Pilih Mata Kuliah --</option>
                                    <?php
                                    $query_mk = mysqli_query($koneksi, "SELECT DISTINCT
                                        mk.id AS matkul_id,
                                        mk.nama_matkul
                                        FROM krs k
                                        JOIN mata_kuliah mk ON k.matkul_id = mk.id
                                        ORDER BY mk.nama_matkul ASC");
                                    while ($mk = mysqli_fetch_assoc($query_mk)) {
                                        echo "<option value='{$mk['matkul_id']}'>{$mk['nama_matkul']}</option>";
                                    }
                                    ?>
                                </select>
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-700 mb-1">Hari</label>
                                <select name="hari" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-1 focus:ring-gray-400 text-xs" required>
                                    <option value="<?= $data['hari']; ?>">-- Pilih Hari --</option>
                                    <option value="Senin">Senin</option>
                                    <option value="Selasa">Selasa</option>
                                    <option value="Rabu">Rabu</option>
                                    <option value="Kamis">Kamis</option>
                                    <option value="Jumat">Jumat</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-700 mb-1">Ruangan</label>
                                <input type="text" name="ruangan" value="<?= $data['ruangan']; ?>"
                                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-1 focus:ring-gray-400 text-xs">
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-700 mb-1">Jam Ke</label>
                                <input type="text" name="jam_ke" value="<?= $data['jam_ke']; ?>"
                                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-1 focus:ring-gray-400 text-xs">
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-700 mb-1">Jam Mulai</label>
                                <input type="time" name="jam_mulai" value="<?= $data['jam_mulai']; ?>"
                                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-1 focus:ring-gray-400 text-xs">
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-700 mb-1">Jam Selesai</label>
                                <input type="time" name="jam_selesai" value="<?= $data['jam_selesai']; ?>"
                                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-1 focus:ring-gray-400 text-xs">
                            </div>

                            <div>
                                <label class="block text-xs font-medium text-gray-700 mb-1">Dosen Pengampu</label>
                                <select name="dosen_pengampu_id" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-1 focus:ring-gray-400 text-xs" required>
                                    <option value="<?= $data['dosen_pengampu_id']; ?>">-- Pilih Dosen Pengampu --</option>
                                    <?php
                                    $query_dp = mysqli_query($koneksi, "SELECT * FROM dosen ORDER BY nama_dosen ASC");
                                    while ($dp = mysqli_fetch_assoc($query_dp)) {
                                        echo "<option value='{$dp['id']}'>{$dp['nama_dosen']}</option>";
                                    }
                                    ?>
                                </select>
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-700 mb-1">Dosen Mitra</label>
                                <select name="dosen_mitra_id" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-1 focus:ring-gray-400 text-xs" required>
                                    <option value="<?= $data['dosen_mitra_id']; ?>">-- Pilih Dosen Mitra --</option>
                                    <?php
                                    $query_dm = mysqli_query($koneksi, "SELECT * FROM dosen ORDER BY nama_dosen ASC");
                                    while ($dm = mysqli_fetch_assoc($query_dm)) {
                                        echo "<option value='{$dm['id']}'>{$dm['nama_dosen']}</option>";
                                    }
                                    ?>
                                    </select>
                            </div>
                        </div>
                        <?php endif; ?>
                        </div>

                        <div class="flex justify-end space-x-4">
                            <button type="button"
                                class="px-6 py-2 border border-gray-300 rounded-lg hover:bg-gray-50 text-xs">Kembali</button>
                            <button type="submit" name="update"
                                class="px-6 py-2 bg-gray-800 text-white rounded-lg hover:bg-gray-700 text-xs">Submit</button>
                        </div>
            </form>
        </div>
        <footer class="text-xs text-indigo-900 text-center mb-0 pb-0 mt-6">
            © 2025 Kelas PTIK C - Teknik Informatika dan Komputer FT UNM. All rights reserved.
        </footer>
    </main>

    <script>
        // Mobile menu toggle dengan perbaikan
        const mobileMenuButton = document.getElementById('mobile-menu-button');
        const sidebar = document.getElementById('sidebar');

        mobileMenuButton.addEventListener('click', (e) => {
            e.stopPropagation(); // Prevent event bubbling
            sidebar.classList.toggle('-translate-x-full');
            sidebar.classList.toggle('mobile-menu-open');
        });

        // Close sidebar when clicking outside
        document.addEventListener('click', (e) => {
            if (window.innerWidth < 768) { // Only on mobile
                if (!sidebar.contains(e.target) && !mobileMenuButton.contains(e.target)) {
                    sidebar.classList.add('-translate-x-full');
                    sidebar.classList.remove('mobile-menu-open');
                }
            }
        });

        // Submenu toggles
        const submenuButtons = document.querySelectorAll('.submenu-button');

        submenuButtons.forEach(button => {
            button.addEventListener('click', () => {
                const submenu = button.nextElementSibling;
                const arrow = button.querySelector('svg:last-child');

                submenu.classList.toggle('hidden');
                arrow.classList.toggle('rotate-180');
            });
        });

        // Profile dropdown
        const profileButton = document.getElementById('profile-button');
        const profileDropdown = document.getElementById('profile-dropdown');

        if (profileButton && profileDropdown) {
            profileButton.addEventListener('click', (e) => {
                e.stopPropagation();
                profileDropdown.classList.toggle('hidden');
            });

            document.addEventListener('click', (e) => {
                if (!profileButton.contains(e.target)) {
                    profileDropdown.classList.add('hidden');
                }
            });
        }
    </script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.5.13/cropper.min.js"></script>
    <script>
        // Image cropper for profile foto
        (function(){
            const fotoInput = document.getElementById('foto-input');
            const cropperArea = document.getElementById('cropper-area');
            const cropImage = document.getElementById('crop-image');
            const currentPreview = document.getElementById('current-preview');
            const fotoDataInput = document.getElementById('foto_data');
            const applyBtn = document.getElementById('apply-crop');
            const cancelBtn = document.getElementById('cancel-crop');
            const zoomIn = document.getElementById('zoom-in');
            const zoomOut = document.getElementById('zoom-out');
            const rotL = document.getElementById('rotate-left');
            const rotR = document.getElementById('rotate-right');
            let cropper = null;
            const form = document.querySelector('form.space-y-6');

            function initCropper() {
                if (cropper) {
                    cropper.destroy();
                }
                cropper = new Cropper(cropImage, {
                    aspectRatio: 1,
                    viewMode: 1,
                    background: false,
                    autoCropArea: 1,
                    responsive: true,
                    preview: '.preview-wrapper'
                });
            }

            fotoInput.addEventListener('change', (e) => {
                const file = e.target.files && e.target.files[0];
                if (!file) return;
                const url = URL.createObjectURL(file);
                cropImage.src = url;
                cropperArea.classList.remove('hidden');
                cropImage.onload = () => {
                    initCropper();
                };
            });

            zoomIn.addEventListener('click', () => { if (cropper) cropper.zoom(0.1); });
            zoomOut.addEventListener('click', () => { if (cropper) cropper.zoom(-0.1); });
            rotL.addEventListener('click', () => { if (cropper) cropper.rotate(-90); });
            rotR.addEventListener('click', () => { if (cropper) cropper.rotate(90); });

            cancelBtn.addEventListener('click', () => {
                if (cropper) { cropper.destroy(); cropper = null; }
                cropperArea.classList.add('hidden');
                fotoInput.value = '';
            });

            applyBtn.addEventListener('click', () => {
                if (!cropper) return;
                const canvas = cropper.getCroppedCanvas({ width: 512, height: 512, imageSmoothingQuality: 'high' });
                canvas.toBlob((blob) => {
                    const reader = new FileReader();
                    reader.onloadend = () => {
                        fotoDataInput.value = reader.result; // base64 data URL
                        currentPreview.src = reader.result;
                        if (cropper) { cropper.destroy(); cropper = null; }
                        cropperArea.classList.add('hidden');
                    };
                    reader.readAsDataURL(blob);
                }, 'image/webp', 0.8);
            });

            form.addEventListener('submit', function(e){
                if (cropper && !fotoDataInput.value) {
                    e.preventDefault();
                    const canvas = cropper.getCroppedCanvas({ width: 512, height: 512, imageSmoothingQuality: 'high' });
                    canvas.toBlob((blob) => {
                        const reader = new FileReader();
                        reader.onloadend = () => {
                            fotoDataInput.value = reader.result;
                            if (cropper) { cropper.destroy(); cropper = null; }
                            cropperArea.classList.add('hidden');
                            form.submit();
                        };
                        reader.readAsDataURL(blob);
                    }, 'image/webp', 0.8);
                }
            });
        })();
    </script>
</body>

</html>