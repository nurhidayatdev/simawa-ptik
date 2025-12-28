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

if (isset($_POST['tambah'])) {
    $redirect = "";
    switch ($tabel) {
        case 'mahasiswa':
            $nama_lengkap = $_POST['nama_lengkap'];
            $nama_panggilan = $_POST['nama_panggilan'];
            $email = $_POST['email'];
            $nim = $_POST['nim'];
            $bio = $_POST['bio'];
            $foto = '';
            if (!empty($_FILES['foto']['name'])) {
                $target_dir = "../img/profile/";
                $foto = time() . "_" . basename($_FILES["foto"]["name"]);
                $target_file = $target_dir . $foto;
                move_uploaded_file($_FILES["foto"]["tmp_name"], $target_file);
            }
            mysqli_query($koneksi, "INSERT INTO mahasiswa (nama_lengkap, nama_panggilan, email, nim, bio, foto)
                VALUES ('$nama_lengkap','$nama_panggilan','$email','$nim','$bio','$foto')");
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
            
            // Kirim ke Telegram
            $telegram_url = "https://api.telegram.org/bot{$telegram_token}/sendMessage";
            $telegram_data = [
                'chat_id' => $telegram_chat_id,
                'text' => $message,
                'parse_mode' => 'Markdown'
            ];
            
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $telegram_url);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $telegram_data);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_exec($ch);
            curl_close($ch);
            
            $redirect = "../dashboard/db_tugas.php";

            break;
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

                        <button
                            class="flex items-center justify-between w-full px-4 py-2 text-slate-800 hover:bg-gray-100 rounded-lg">
                            <div class="flex items-center">
                                <i class="fa fa-user w-5 h-5 mr-4"></i>
                                <a href="db_users.php">
                                    <span class="text-xs">Users</span>
                                </a>
                            </div>
                        </button>

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