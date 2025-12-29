<?php
session_start();
include '../../koneksi.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../../index.php");
    exit;
}

$nama_lengkap = $_SESSION['nama_lengkap'];
$foto = $_SESSION['foto'];
$tabel = $_GET['tabel'] ?? '';
$file = $_GET['file'] ?? '';
$role = $_SESSION['role'];

if (isset($_POST['tambah'])) {
    $redirect = "";
    switch ($tabel) {
        case 'mahasiswa':
            $nama_lengkap = $_POST['nama_lengkap'];
            $nama_panggilan = $_POST['nama_panggilan'];
            $email = $_POST['email'];
            $nim = $_POST['nim'];
            $bio = $_POST['bio'];
            $jenis_kelamin = $_POST['jenis_kelamin'];
            $instagram = $_POST['instagram'];
            $foto = '';
            if (!empty($_FILES['foto']['name'])) {
                $target_dir = "../img/profile/";
                $foto = time() . "_" . basename($_FILES["foto"]["name"]);
                $target_file = $target_dir . $foto;
                move_uploaded_file($_FILES["foto"]["tmp_name"], $target_file);
            }
            mysqli_query($koneksi, "INSERT INTO mahasiswa (nama_lengkap, nama_panggilan, email, nim, bio, jenis_kelamin, instagram, foto)
                VALUES ('$nama_lengkap','$nama_panggilan','$email','$nim','$bio','$jenis_kelamin','$instagram','$foto')");
            $redirect = "../dashboard/db_mahasiswa.php";

            break;

        case 'mata_kuliah':
            $kode_matkul = $_POST['kode_matkul'];
            $nama_matkul = $_POST['nama_matkul'];
            $semester = $_POST['semester'];
            $sks = $_POST['sks'];
            mysqli_query($koneksi, "INSERT INTO mata_kuliah (kode_matkul, nama_matkul, semester, sks)
                VALUES ('$kode_matkul','$nama_matkul','$semester','$sks')");
            $redirect = "../dashboard/db_matkul.php";

            break;

        case 'jadwal_matkul':
            $matkul_id = $_POST['matkul_id'];
            $hari = $_POST['hari'];
            $jam_ke = $_POST['jam_ke'];
            $jam_mulai = $_POST['jam_mulai'];
            $jam_selesai = $_POST['jam_selesai'];
            $ruangan = $_POST['ruangan'];
            $dosen_pengampu_id = $_POST['dosen_pengampu_id'];
            $dosen_mitra_id = $_POST['dosen_mitra_id'];
            
            // Get active periode_id
            $q_periode = mysqli_query($koneksi, "SELECT id FROM periode WHERE status = 'aktif' LIMIT 1");
            $periode = mysqli_fetch_assoc($q_periode);
            $periode_id = $periode['id'] ?? 0;
            
            mysqli_query($koneksi, "INSERT INTO jadwal_matkul (matkul_id, hari, jam_ke, jam_mulai, jam_selesai, ruangan, dosen_pengampu_id, dosen_mitra_id, periode_id)
                VALUES ('$matkul_id', '$hari', '$jam_ke', '$jam_mulai', '$jam_selesai', '$ruangan', '$dosen_pengampu_id', '$dosen_mitra_id', '$periode_id')");
            $redirect = "../dashboard/db_jadwal.php";

            break;

        case 'users':
            $id = $_POST['id'];
            $password = $_POST['password'];
            $role = $_POST['role'];
            mysqli_query($koneksi, "INSERT INTO users (id, password, role)
                VALUES ('$id','$password','$role')");
            $redirect = "../dashboard/db_users.php";

            break;

            case 'dosen':
            $nama_dosen = $_POST['nama_dosen'];
            $no_hp = $_POST['no_hp'];
            mysqli_query($koneksi, "INSERT INTO dosen (nama_dosen, no_hp)
                VALUES ('$nama_dosen','$no_hp')");
            $redirect = "../dashboard/db_dosen.php";

            break;

            case 'tugas':
            $matkul_id = $_POST['matkul_id'];
            $deadline_tanggal = $_POST['deadline_tanggal'];
            $deadline_pukul = $_POST['deadline_pukul'];
            $deskripsi = $_POST['deskripsi'];
            $link_kirim = $_POST['link_kirim'];
            
            // Handle file upload
            $file_tugas = '';
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
            
            // Get active periode_id
            $q_periode = mysqli_query($koneksi, "SELECT id FROM periode WHERE status = 'aktif' LIMIT 1");
            $periode = mysqli_fetch_assoc($q_periode);
            $periode_id = $periode['id'] ?? 0;
            
            // Get nama mata kuliah untuk notifikasi
            $q_matkul = mysqli_query($koneksi, "SELECT nama_matkul FROM mata_kuliah WHERE id = '$matkul_id'");
            $matkul_data = mysqli_fetch_assoc($q_matkul);
            $nama_matkul = $matkul_data['nama_matkul'] ?? 'Unknown';
            
            mysqli_query($koneksi, "INSERT INTO tugas (matkul_id, deadline_tanggal, deadline_pukul, deskripsi, file_tugas, link_kirim, periode_id)
                VALUES ('$matkul_id', '$deadline_tanggal', '$deadline_pukul', '$deskripsi', '$file_tugas', '$link_kirim', '$periode_id')");
            
            // =====================
            // TELEGRAM NOTIFICATION
            // =====================
            $telegram_token = "8212322367:AAGCGc4TG__EnsEd-20wFTI_s2adp-dp1OY";
            $telegram_chat_id = "-1003577478223";
            
            // Format pesan
            $message = "📚 *TUGAS BARU DITAMBAHKAN!*\n\n";
            $message .= "📖 *Mata Kuliah:* " . $nama_matkul . "\n";
            $message .= "📅 *Deadline:* " . $deadline_tanggal . " pukul " . $deadline_pukul . "\n";
            $message .= "📝 *Deskripsi:* " . ($deskripsi ?: '-') . "\n";
            if (!empty($link_kirim)) {
                $message .= "🔗 *Link Kirim:* " . $link_kirim . "\n";
            }
            $message .= "\n_Segera kerjakan tugasmu!_ 💪";
            
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
            case 'tugas':
            $matkul_id = $_POST['matkul_id'];
            $deadline_tanggal = $_POST['deadline_tanggal'];
            $deadline_pukul = $_POST['deadline_pukul'];
            $deskripsi = $_POST['deskripsi'];
            $link_kirim = $_POST['link_kirim'];
            
            // Handle file upload
            $file_tugas = '';
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
            
            // Get active periode_id
            $q_periode = mysqli_query($koneksi, "SELECT id FROM periode WHERE status = 'aktif' LIMIT 1");
            $periode = mysqli_fetch_assoc($q_periode);
            $periode_id = $periode['id'] ?? 0;
            
            mysqli_query($koneksi, "INSERT INTO tugas (matkul_id, deadline_tanggal, deadline_pukul, deskripsi, file_tugas, link_kirim, periode_id)
                VALUES ('$matkul_id', '$deadline_tanggal', '$deadline_pukul', '$deskripsi', '$file_tugas', '$link_kirim', '$periode_id')");
            $redirect = "../dashboard/db_tugas.php";

            break;

        case 'galeri':
            $judul = mysqli_real_escape_string($koneksi, $_POST['judul']);
            $tanggal_kegiatan = $_POST['tanggal_kegiatan'];
            
            mysqli_query($koneksi, "INSERT INTO galeri (judul, tanggal_kegiatan) VALUES ('$judul', '$tanggal_kegiatan')");
            $galeri_id = mysqli_insert_id($koneksi);
            
            if (!empty($_FILES['fotos']['name'][0])) {
                $target_dir = __DIR__ . "/../img/gallery/"; // Absolute path for safety
                if (!is_dir($target_dir)) {
                    mkdir($target_dir, 0777, true);
                }
                
                foreach ($_FILES['fotos']['tmp_name'] as $key => $tmp_name) {
                    if (empty($tmp_name)) continue;

                    $file_name = $_FILES['fotos']['name'][$key];
                    $ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
                    $basename = preg_replace('/[^a-zA-Z0-9_]/', '_', pathinfo($file_name, PATHINFO_FILENAME));
                    $new_base = time() . "_" . uniqid() . "_" . $basename;
                    
                    // Always save as jpg for consistent compression
                    $final_name = $new_base . ".jpg";
                    $final_path = $target_dir . $final_name;
                    $db_path = "img/gallery/" . $final_name;

                    // Try to compress/resize
                    $processed = false;
                    if (function_exists('gd_info')) {
                        $src = null;
                        if ($ext == 'jpg' || $ext == 'jpeg') $src = @imagecreatefromjpeg($tmp_name);
                        elseif ($ext == 'png') $src = @imagecreatefrompng($tmp_name);
                        elseif ($ext == 'webp') $src = @imagecreatefromwebp($tmp_name);
                        
                        if ($src) {
                            $width = imagesx($src);
                            $height = imagesy($src);
                            
                            // Max width 1024 for size reduction
                            $max_width = 1024;
                            if ($width > $max_width) {
                                $new_width = $max_width;
                                $new_height = floor($height * ($max_width / $width));
                            } else {
                                $new_width = $width;
                                $new_height = $height;
                            }

                            $dst = imagecreatetruecolor($new_width, $new_height);
                            
                            // Fill white background for transparency (since we output JPG)
                            $white = imagecolorallocate($dst, 255, 255, 255);
                            imagefill($dst, 0, 0, $white);
                            
                            imagecopyresampled($dst, $src, 0, 0, 0, 0, $new_width, $new_height, $width, $height);
                            
                            // Save as JPG with 75% quality
                            if (imagejpeg($dst, $final_path, 75)) {
                                $processed = true;
                            }
                            
                            imagedestroy($src);
                            imagedestroy($dst);
                        }
                    }

                    // Fallback: just move the file if processing failed (and keep original name/ext if needed, or fail)
                    if (!$processed) {
                        // If we couldn't process, just move uploaded file as is
                        $final_name_fallback = $new_base . "." . $ext;
                        $final_path_fallback = $target_dir . $final_name_fallback;
                        if (move_uploaded_file($tmp_name, $final_path_fallback)) {
                             $db_path = "img/gallery/" . $final_name_fallback;
                        } else {
                            continue; // Skip failed uploads
                        }
                    }

                    mysqli_query($koneksi, "INSERT INTO galeri_foto (galeri_id, foto_path) VALUES ('$galeri_id', '$db_path')");
                }
            }
            $redirect = "../dashboard/db_galeri.php";
            break;

        case 'akademik':
            $judul = mysqli_real_escape_string($koneksi, $_POST['judul']);
            $deskripsi = mysqli_real_escape_string($koneksi, $_POST['deskripsi']);
            $link = mysqli_real_escape_string($koneksi, $_POST['link']);
            
            mysqli_query($koneksi, "INSERT INTO akademik (judul, deskripsi, link) VALUES ('$judul', '$deskripsi', '$link')");
            $redirect = "../dashboard/db_berita.php";
            break;

        case 'kalender_akademik':
            $periode_id = intval($_POST['periode_id']);
            $kegiatan = mysqli_real_escape_string($koneksi, $_POST['kegiatan']);
            $tanggal_mulai = $_POST['tanggal_mulai'];
            $tanggal_selesai = $_POST['tanggal_selesai'];

            mysqli_query($koneksi, "INSERT INTO kalender_akademik (periode_id, kegiatan, tanggal_mulai, tanggal_selesai) VALUES ('$periode_id', '$kegiatan', '$tanggal_mulai', '$tanggal_selesai')");
            $redirect = "../dashboard/db_kalender.php";
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
    text: 'Data berhasil ditambahkan!',
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
    <style>
        .rotate-180 {
            transform: rotate(180deg);
        }

        /* Tambahan CSS untuk mobile menu */
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
                        <h3 class="text-xl font-bold mb-6">Mahasiswa</h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label class="block text-xs font-medium text-gray-700 mb-1">Nama Lengkap</label>
                                <input type="text" name="nama_lengkap"
                                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-1 focus:ring-gray-400">
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-700 mb-1">Nama Panggilan</label>
                                <input type="text" name="nama_panggilan"
                                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-1 focus:ring-gray-400">
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-700 mb-1">Email</label>
                                <input type="email" name="email"
                                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-1 focus:ring-gray-400">
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-700 mb-1">NIM</label>
                                <input type="number" name="nim"
                                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-1 focus:ring-gray-400">
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-700 mb-1">Jenis Kelamin</label>
                                <select name="jenis_kelamin"
                                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-1 focus:ring-gray-400">
                                    <option value="">-- Pilih Jenis Kelamin --</option>
                                    <option value="Laki-laki">Laki-laki</option>
                                    <option value="Perempuan">Perempuan</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-700 mb-1">Instagram</label>
                                <input type="text" name="instagram" placeholder="@username"
                                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-1 focus:ring-gray-400">
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-700 mb-1">Biodata</label>
                                <input type="text" name="bio"
                                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-1 focus:ring-gray-400">
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-700 mb-1">Foto</label>
                                <input type="file" name="foto" accept="image/*"
                                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-1 focus:ring-gray-400">
                            </div>
                        </div>

                    <?php elseif ($tabel === 'mata_kuliah'): ?>
                        <h3 class="text-xl font-bold mb-6">Mata Kuliah</h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label class="block text-xs font-medium text-gray-700 mb-1">Kode Mata Kuliah</label>
                                <input type="text" name="kode_matkul"
                                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-1 focus:ring-gray-400">
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-700 mb-1">Nama Mata Kuliah</label>
                                <input type="text" name="nama_matkul"
                                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-1 focus:ring-gray-400">
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-700 mb-1">Semester</label>
                                <input type="number" name="semester"
                                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-1 focus:ring-gray-400">
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-700 mb-1">SKS</label>
                                <input type="number" name="sks"
                                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-1 focus:ring-gray-400">
                            </div>
                        </div>

                    <?php elseif ($tabel === 'jadwal_matkul'): ?>
                        <h3 class="text-xl font-bold mb-6">Jadwal Mata Kuliah</h3>
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
                                <label class="block text-xs font-medium text-gray-700 mb-1">Hari</label>
                                <select name="hari" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-1 focus:ring-gray-400" required>
                                    <option value="">-- Pilih Hari --</option>
                                    <option value="Senin">Senin</option>
                                    <option value="Selasa">Selasa</option>
                                    <option value="Rabu">Rabu</option>
                                    <option value="Kamis">Kamis</option>
                                    <option value="Jumat">Jumat</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-700 mb-1">Ruangan</label>
                                <input type="text" name="ruangan"
                                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-1 focus:ring-gray-400">
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-700 mb-1">Jam Ke</label>
                                <input type="text" name="jam_ke"
                                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-1 focus:ring-gray-400">
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-700 mb-1">Jam Mulai</label>
                                <input type="time" name="jam_mulai"
                                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-1 focus:ring-gray-400">
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-700 mb-1">Jam Selesai</label>
                                <input type="time" name="jam_selesai"
                                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-1 focus:ring-gray-400">
                            </div>

                            <div>
                                <label class="block text-xs font-medium text-gray-700 mb-1">Dosen Pengampu</label>
                                <select name="dosen_pengampu_id" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-1 focus:ring-gray-400" required>
                                    <option value="">-- Pilih Dosen Pengampu --</option>
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
                                <select name="dosen_mitra_id" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-1 focus:ring-gray-400" required>
                                    <option value="">-- Pilih Dosen Mitra --</option>
                                    <?php
                                    $query_dm = mysqli_query($koneksi, "SELECT * FROM dosen ORDER BY nama_dosen ASC");
                                    while ($dm = mysqli_fetch_assoc($query_dm)) {
                                        echo "<option value='{$dm['id']}'>{$dm['nama_dosen']}</option>";
                                    }
                                    ?>
                                    </select>
                            </div>
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
                        <h3 class="text-xl font-bold mb-6">Tambah Tugas</h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label class="block text-xs font-medium text-gray-700 mb-1">Mata Kuliah</label>
                                <select name="matkul_id" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-1 focus:ring-gray-400 text-xs" required>
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
                                <input type="date" name="deadline_tanggal" 
                                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-1 focus:ring-gray-400 text-xs">
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-700 mb-1">Deadline Pukul</label>
                                <input type="time" name="deadline_pukul" 
                                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-1 focus:ring-gray-400 text-xs">
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-700 mb-1">Deskripsi</label>
                                <input type="text" name="deskripsi" 
                                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-1 focus:ring-gray-400 text-xs">
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-700 mb-1">File Tugas</label>
                                <input type="file" name="file_tugas" accept="file/*"
                                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-1 focus:ring-gray-400 text-xs">
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-700 mb-1">Link Kirim</label>
                                <input type="text" name="link_kirim" 
                                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-1 focus:ring-gray-400 text-xs">
                            </div>
                        </div>

                    <?php elseif ($tabel === 'users'): ?>
                        <h3 class="text-xl font-bold mb-6">Users</h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label class="block text-xs font-medium text-gray-700 mb-1">Kode Mata Kuliah</label>
                                <select name="id" class="form-select" required>
                                    <option value="">-- Pilih Mahasiswa --</option>
                                    <?php
                                    $query_mhs = mysqli_query($koneksi, "SELECT * FROM mahasiswa ORDER BY nim ASC");
                                    while ($mhs = mysqli_fetch_assoc($query_mhs)) {
                                        echo "<option value='{$mhs['user_id']}'>{$mhs['nama_lengkap']}</option>";
                                    }
                                    ?>
                                </select>
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-700 mb-1">Password</label>
                                <input type="password" name="password"
                                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-1 focus:ring-gray-400">
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-700 mb-1">Role</label>
                                <select name="role" class="form-control" required>
                                    <option value="">-- Pilih Role/Peran --</option>
                                    <option value="Administrator Utama">Administrator Utama</option>
                                    <option value="Ketua Tingkat">Ketua Tingkat</option>
                                    <option value="Sekretaris">Sekretaris</option>
                                    <option value="Bendahara">Bendahara</option>
                                    <option value="Mahasiswa">Mahasiswa</option>
                                </select>
                            </div>
                        </div>

                    <?php elseif ($tabel === 'galeri'): ?>
                        <h3 class="text-xl font-bold mb-6">Galeri Kegiatan</h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label class="block text-xs font-medium text-gray-700 mb-1">Judul Kegiatan</label>
                                <input type="text" name="judul" required
                                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-1 focus:ring-gray-400 text-xs">
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-700 mb-1">Tanggal Kegiatan</label>
                                <input type="date" name="tanggal_kegiatan" required
                                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-1 focus:ring-gray-400 text-xs">
                            </div>
                            <div class="md:col-span-2">
                                <label class="block text-xs font-medium text-gray-700 mb-1">Foto-foto (Bisa pilih banyak)</label>
                                <input type="file" name="fotos[]" multiple accept="image/*" required
                                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-1 focus:ring-gray-400 text-xs">
                            </div>
                        </div>
                    <?php elseif ($tabel === 'akademik'): ?>
                        <h3 class="text-xl font-bold mb-6">Berita & Informasi Akademik</h3>
                        <div class="grid grid-cols-1 gap-6">
                            <div>
                                <label class="block text-xs font-medium text-gray-700 mb-1">Judul Berita</label>
                                <input type="text" name="judul" required
                                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-1 focus:ring-gray-400 text-xs">
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-700 mb-1">Deskripsi</label>
                                <textarea name="deskripsi" rows="3" required
                                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-1 focus:ring-gray-400 text-xs"></textarea>
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-700 mb-1">Link (Opsional)</label>
                                <input type="text" name="link"
                                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-1 focus:ring-gray-400 text-xs">
                            </div>
                        </div>
                    <?php elseif ($tabel === 'kalender_akademik'): ?>
                        <h3 class="text-xl font-bold mb-6">Tambah Agenda Kalender Akademik</h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label class="block text-xs font-medium text-gray-700 mb-1">Periode</label>
                                <select name="periode_id" required
                                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-1 focus:ring-gray-400 text-xs">
                                    <option value="">-- Pilih Periode --</option>
                                    <?php
                                    $q_per = mysqli_query($koneksi, "SELECT * FROM periode ORDER BY id DESC");
                                    while ($per = mysqli_fetch_assoc($q_per)) {
                                        echo "<option value='{$per['id']}'>{$per['tahun_periode']} - {$per['semester_nama']}</option>";
                                    }
                                    ?>
                                </select>
                            </div>
                            <div class="md:col-span-2">
                                <label class="block text-xs font-medium text-gray-700 mb-1">Nama Kegiatan</label>
                                <textarea name="kegiatan" rows="2" required
                                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-1 focus:ring-gray-400 text-xs"></textarea>
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-700 mb-1">Tanggal Mulai</label>
                                <input type="date" name="tanggal_mulai" required
                                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-1 focus:ring-gray-400 text-xs">
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-700 mb-1">Tanggal Selesai</label>
                                <input type="date" name="tanggal_selesai" required
                                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-1 focus:ring-gray-400 text-xs">
                            </div>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Form Actions -->
                <div class="flex justify-end space-x-4">
                    <button type="button"
                        class="px-6 py-2 border border-gray-300 rounded-lg hover:bg-gray-50">Cancel</button>
                    <button type="submit" name="tambah"
                        class="px-6 py-2 bg-gray-800 text-white rounded-lg hover:bg-gray-700">Submit</button>
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
</body>

</html>