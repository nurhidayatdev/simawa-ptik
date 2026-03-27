<?php
session_start();
include '../koneksi.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../../index");
    exit;
}

$nama_lengkap = $_SESSION['nama_lengkap'];
$nama_panggilan = $_SESSION['nama_panggilan'];
$foto = $_SESSION['foto'];
$role = $_SESSION['role'];
$tabel = $_GET['tabel'] ?? '';
$id = $_GET['id'] ?? '';

// Handle jam_kuliah yang menggunakan jam_ke sebagai primary key
if ($tabel === 'jam_kuliah') {
    $jam_ke = $_GET['jam_ke'] ?? '';
    $query = mysqli_query($koneksi, "SELECT * FROM jam_kuliah WHERE jam_ke='$jam_ke'");
} elseif ($tabel === 'users') {
    $query = mysqli_query($koneksi, "SELECT u.*, m.nama_lengkap FROM users u LEFT JOIN mahasiswa m ON m.user_id = u.id WHERE u.id='$id'");
} else {
    $query = mysqli_query($koneksi, "SELECT * FROM $tabel WHERE id='$id'");
}

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
                            imagewebp($dst, __DIR__ . "/../storage/profile/" . $foto_baru, 80);
                        } else {
                            $ext = 'jpg';
                            $foto_baru = $nama_panggilan . '.' . $ext;
                            imagejpeg($dst, __DIR__ . "/../storage/profile/" . $foto_baru, 85);
                        }

                        if (function_exists('imagedestroy')) {
                            imagedestroy($src);
                            imagedestroy($dst);
                        }
                    } else {
                        // GD not available: save raw bytes
                        $ext = 'jpg';
                        if ($mime === 'image/webp') $ext = 'webp';
                        elseif ($mime === 'image/png') $ext = 'png';
                        $foto_baru = $nama_panggilan . '.' . $ext;
                        file_put_contents(__DIR__ . "/../storage/profile/" . $foto_baru, $decoded);
                    }
                }
            } elseif (!empty($_FILES['foto']['name'])) {
                $nama_file = $_FILES['foto']['name'];
                $tmp_file  = $_FILES['foto']['tmp_name'];
                $ext       = strtolower(pathinfo($nama_file, PATHINFO_EXTENSION));

                $allowed = ['jpg', 'jpeg', 'png', 'webp'];

                if (in_array($ext, $allowed)) {
                    $foto_baru = $nama_panggilan . '.' . $ext;
                    $dest_path = __DIR__ . "/../storage/profile/" . $foto_baru;
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
                                imagewebp($dst, __DIR__ . "/../storage/profile/" . $foto_baru, 80);
                            } else {
                                imagejpeg($dst, $dest_path, 85);
                            }
                            if (function_exists('imagedestroy')) {
                                imagedestroy($src);
                                imagedestroy($dst);
                            }
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
                    jenis_kelamin='{$_POST['jenis_kelamin']}',
                    instagram='{$_POST['instagram']}',
                    foto='$foto_baru'
                    WHERE id='$id'");
            $redirect = "../database/db_mahasiswa";
            break;

        case 'periode':
            mysqli_query($koneksi, "UPDATE periode SET 
                    tahun_periode='{$_POST['tahun_periode']}',
                    semester_nama='{$_POST['semester_nama']}',
                    semester_ke='{$_POST['semester_ke']}',
                    status='{$_POST['status']}'
                    WHERE id='$id'");
            $redirect = "../database/db_periode";
            break;

        case 'mata_kuliah':
            mysqli_query($koneksi, "UPDATE mata_kuliah SET 
                    kode_matkul='{$_POST['kode_matkul']}',
                    nama_matkul='{$_POST['nama_matkul']}',
                    semester='{$_POST['semester']}',
                    sks='{$_POST['sks']}'
                    WHERE id='$id'");
            $redirect = "../database/db_matkul";
            break;

        case 'users':
            $email = mysqli_real_escape_string($koneksi, $_POST['email']);
            $role = mysqli_real_escape_string($koneksi, $_POST['role']);
            $password = $_POST['password'];

            if ($password === '**********') {
                // Jangan update password jika value-nya adalah mask
                mysqli_query($koneksi, "UPDATE users SET 
                        email='$email',
                        role='$role'
                        WHERE id='$id'");
            } else {
                mysqli_query($koneksi, "UPDATE users SET 
                        email='$email',
                        password='$password',
                        role='$role'
                        WHERE id='$id'");
            }
            $redirect = "../database/db_users";
            break;

        case 'dosen':
            mysqli_query($koneksi, "UPDATE dosen SET 
                    nama_dosen='{$_POST['nama_dosen']}',
                    no_hp='{$_POST['no_hp']}'
                    WHERE id='$id'");
            $redirect = "../database/db_dosen";
            break;

        case 'tugas':
            // Handle file upload - keep old file if no new file uploaded
            $file_tugas = $data['file_tugas']; // default to existing file
            if (!empty($_FILES['file_tugas']['name'])) {
                $target_dir = "../storage/tugas/";
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
            // WHATSAPP NOTIFICATION
            // =====================
            include_once '../functions/kirim_tugas.php';
            notifyUpdateTask(null, null, $nama_matkul, $_POST['deadline_tanggal'], $_POST['deadline_pukul'], $_POST['deskripsi'], $_POST['link_kirim']);

            $redirect = "../database/db_tugas";
            break;

        case 'jadwal_matkul':
            mysqli_query($koneksi, "UPDATE jadwal_matkul SET 
                    matkul_id='{$_POST['matkul_id']}',
                    hari='{$_POST['hari']}',
                    jam_mulai_ke='{$_POST['jam_mulai_ke']}',
                    jam_selesai_ke='{$_POST['jam_selesai_ke']}',
                    ruangan_id='{$_POST['ruangan_id']}',
                    dosen_pengampu_id='{$_POST['dosen_pengampu_id']}',
                    dosen_mitra_id='{$_POST['dosen_mitra_id']}'
                    WHERE id='$id'");
            $redirect = "../database/db_jadwal";
            break;

        case 'galeri':
            $judul = mysqli_real_escape_string($koneksi, $_POST['judul']);
            $tanggal_kegiatan = $_POST['tanggal_kegiatan'];

            mysqli_query($koneksi, "UPDATE galeri SET judul='$judul', tanggal_kegiatan='$tanggal_kegiatan' WHERE id='$id'");

            // Handle new photos
            if (!empty($_FILES['fotos']['name'][0])) {
                $target_dir = __DIR__ . "../../storage/gallery/";
                if (!is_dir($target_dir)) {
                    mkdir($target_dir, 0777, true);
                }

                $sanitized_judul = strtolower(preg_replace('/[^a-zA-Z0-9]/', '_', $judul));
                foreach ($_FILES['fotos']['tmp_name'] as $key => $tmp_name) {
                    if (empty($tmp_name)) continue;

                    $file_name = $_FILES['fotos']['name'][$key];
                    $ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
                    $final_name = $sanitized_judul . "_" . time() . "_" . $key . "." . $ext;
                    $target_file = $target_dir . $final_name;

                    if (move_uploaded_file($tmp_name, $target_file)) {
                        $db_path = $final_name;
                        mysqli_query($koneksi, "INSERT INTO galeri_foto (galeri_id, foto_path) VALUES ('$id', '$db_path')");
                    }
                }
            }

            // Handle photo deletion (if any IDs are passed)
            if (isset($_POST['delete_photos'])) {
                foreach ($_POST['delete_photos'] as $foto_id) {
                    $q_f = mysqli_query($koneksi, "SELECT foto_path FROM galeri_foto WHERE id = '$foto_id'");
                    $f_data = mysqli_fetch_assoc($q_f);
                    if ($f_data) {
                        @unlink("../" . $f_data['foto_path']);
                        mysqli_query($koneksi, "DELETE FROM galeri_foto WHERE id = '$foto_id'");
                    }
                }
            }

            $redirect = "../database/db_galeri";
            break;

        case 'akademik':
            $judul = mysqli_real_escape_string($koneksi, $_POST['judul']);
            $deskripsi = mysqli_real_escape_string($koneksi, $_POST['deskripsi']);
            $link = mysqli_real_escape_string($koneksi, $_POST['link']);

            mysqli_query($koneksi, "UPDATE akademik SET judul='$judul', deskripsi='$deskripsi', link='$link' WHERE id='$id'");
            $redirect = "../database/db_berita";
            break;

        case 'kalender_akademik':
            $periode_id = intval($_POST['periode_id']);
            $kegiatan = mysqli_real_escape_string($koneksi, $_POST['kegiatan']);
            $tanggal_mulai = $_POST['tanggal_mulai'];
            $tanggal_selesai = $_POST['tanggal_selesai'];

            mysqli_query($koneksi, "UPDATE kalender_akademik SET periode_id='$periode_id', kegiatan='$kegiatan', tanggal_mulai='$tanggal_mulai', tanggal_selesai='$tanggal_selesai' WHERE id='$id'");
            $redirect = "../database/db_kalender";
            break;

        case 'ruangan':
            $nama_ruangan = mysqli_real_escape_string($koneksi, $_POST['nama_ruangan']);
            mysqli_query($koneksi, "UPDATE ruangan SET nama_ruangan='$nama_ruangan' WHERE id='$id'");
            $redirect = "../database/db_ruangan";
            break;

        case 'jam_kuliah':
            $jam_ke_param = $_GET['jam_ke'] ?? '';
            $jam_mulai = $_POST['jam_mulai'];
            $jam_selesai = $_POST['jam_selesai'];
            mysqli_query($koneksi, "UPDATE jam_kuliah SET jam_mulai='$jam_mulai', jam_selesai='$jam_selesai' WHERE jam_ke='$jam_ke_param'");
            $redirect = "../database/db_jam";
            break;

        case 'kuis_harian_soal':
            $tanggal = $_POST['tanggal'];
            $pertanyaan = mysqli_real_escape_string($koneksi, $_POST['pertanyaan']);
            $jawaban = mysqli_real_escape_string($koneksi, $_POST['jawaban']);
            mysqli_query($koneksi, "UPDATE kuis_harian_soal SET tanggal='$tanggal', pertanyaan='$pertanyaan', jawaban='$jawaban' WHERE id='$id'");
            $redirect = "../database/db_soal";
            break;
    }

    include '../includes/alert_actions.php';
    exit;
}

?>
<!DOCTYPE html>
<html lang="en">

<head>

    <title>Edit - PTIK C 2024</title>
    <?php include '../includes/head.php'; ?>
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

<body class="bg-slate-100 dark:bg-slate-950 transition-colors">
    <?php include '../includes/menu_db.php'; ?>

    <main class="ml-0 lg:ml-48 pt-20 p-6 flex flex-col min-h-screen">
        <div class="bg-white dark:bg-slate-900/50 rounded-lg border border-slate-200 dark:border-slate-800 p-6 flex-grow w-full transition-all">

            <form class="space-y-6" method="POST" enctype="multipart/form-data">
                <div class="border-b border-slate-200 dark:border-slate-800 pb-6">
                    <?php if ($tabel === 'mahasiswa'): ?>
                        <h3 class="text-xl font-bold mb-6 text-slate-800 dark:text-slate-100">Edit Mahasiswa</h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label class="block text-xs font-medium text-slate-700 dark:text-slate-300 mb-1">Nama Lengkap</label>
                                <input type="text" name="nama_lengkap" value="<?= $data['nama_lengkap']; ?>"
                                    class="w-full px-4 py-2 border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-slate-200 rounded-lg focus:outline-none focus:ring-1 focus:ring-indigo-500 dark:focus:ring-indigo-600 text-xs transition-colors">
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-slate-700 dark:text-slate-300 mb-1">Nama Panggilan</label>
                                <input type="text" name="nama_panggilan" value="<?= $data['nama_panggilan']; ?>"
                                    class="w-full px-4 py-2 border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-slate-200 rounded-lg focus:outline-none focus:ring-1 focus:ring-indigo-500 dark:focus:ring-indigo-600 text-xs transition-colors">
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-slate-700 dark:text-slate-300 mb-1">NIM</label>
                                <input type="number" name="nim" value="<?= $data['nim']; ?>"
                                    class="w-full px-4 py-2 border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-slate-200 rounded-lg focus:outline-none focus:ring-1 focus:ring-indigo-500 dark:focus:ring-indigo-600 text-xs transition-colors">
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-slate-700 dark:text-slate-300 mb-1">Jenis Kelamin</label>
                                <select name="jenis_kelamin"
                                    class="w-full px-4 py-2 border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-slate-200 rounded-lg focus:outline-none focus:ring-1 focus:ring-indigo-500 dark:focus:ring-indigo-600 text-xs transition-colors">
                                    <option value="">-- Pilih Jenis Kelamin --</option>
                                    <option value="Laki-laki" <?= ($data['jenis_kelamin'] == 'Laki-laki') ? 'selected' : '' ?>>Laki-laki</option>
                                    <option value="Perempuan" <?= ($data['jenis_kelamin'] == 'Perempuan') ? 'selected' : '' ?>>Perempuan</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-slate-700 dark:text-slate-300 mb-1">Instagram</label>
                                <input type="text" name="instagram" value="<?= $data['instagram'] ?? ''; ?>" placeholder="@username"
                                    class="w-full px-4 py-2 border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-slate-200 rounded-lg focus:outline-none focus:ring-1 focus:ring-indigo-500 dark:focus:ring-indigo-600 text-xs transition-colors">
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-slate-700 dark:text-slate-300 mb-1">Bio</label>
                                <input name="bio" value="<?= $data['bio']; ?>"
                                    class="w-full px-4 py-2 border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-slate-200 rounded-lg focus:outline-none focus:ring-1 focus:ring-indigo-500 dark:focus:ring-indigo-600 text-xs transition-colors">
                            </div>
                            <div class="md:col-span-2 space-y-4">
                                <label class="block text-xs font-medium text-slate-700 dark:text-slate-300 mb-1">Foto</label>
                                <input type="file" id="foto-input" name="foto_file" accept="image/*" class="w-full px-4 py-2 border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-slate-200 rounded-lg focus:outline-none focus:ring-1 focus:ring-indigo-500 dark:focus:ring-indigo-600 text-xs transition-colors">
                                <input type="hidden" name="foto_data" id="foto_data">
                                <input type="hidden" name="current_foto" value="<?= htmlspecialchars($data['foto']); ?>">
                                <br>
                                <div class="flex items-center space-x-4">
                                    <img id="current-preview" src="../storage/profile/<?php echo htmlspecialchars($data['foto']); ?>" class="img-fluid rounded border dark:border-slate-700" style="width:120px; height:120px; object-fit:cover;" />
                                    <div>
                                        <button type="button" id="open-cropper" class="px-3 py-1 bg-indigo-100 dark:bg-indigo-900/40 text-indigo-700 dark:text-indigo-300 rounded text-xs hidden transition-colors">Buka Cropper</button>
                                    </div>
                                </div>

                                <!-- Cropper modal-ish area -->
                                <div id="cropper-area" class="mt-4 hidden">
                                    <div class="grid sm:grid-cols-2 gap-4 space-x-4">
                                        <div class="w-full flex align-center justify-center bg-slate-100 dark:bg-slate-800 rounded-lg overflow-hidden border dark:border-slate-700">
                                            <img id="crop-image" style="max-width:100%; display:block;" />
                                        </div>
                                        <div class="space-y-2">
                                            <div class="border dark:border-slate-700 p-2 rounded bg-white dark:bg-slate-800" style="width:160px; height:160px; overflow:hidden;">
                                                <div class="preview-wrapper" style="width:100%; height:100%;">
                                                    <img id="preview" style="width:100%; height:100%; object-fit:cover; display:block;" />
                                                </div>
                                            </div>
                                            <div class="flex space-x-2">
                                                <button type="button" id="zoom-in" class="px-3 py-1 bg-slate-200 dark:bg-slate-700 text-slate-700 dark:text-slate-200 rounded transition-colors">+</button>
                                                <button type="button" id="zoom-out" class="px-3 py-1 bg-slate-200 dark:bg-slate-700 text-slate-700 dark:text-slate-200 rounded transition-colors">-</button>
                                                <button type="button" id="rotate-left" class="px-3 py-1 bg-slate-200 dark:bg-slate-700 text-slate-700 dark:text-slate-200 rounded transition-colors">⟲</button>
                                                <button type="button" id="rotate-right" class="px-3 py-1 bg-slate-200 dark:bg-slate-700 text-slate-700 dark:text-slate-200 rounded transition-colors">⟳</button>
                                            </div>
                                            <div>
                                                <button type="button" id="apply-crop" class="mt-2 px-3 py-1 bg-indigo-700 text-white rounded text-sm transition-all shadow-lg shadow-indigo-200 dark:shadow-none hover:bg-indigo-600">Gunakan Foto</button>
                                                <button type="button" id="cancel-crop" class="mt-2 ml-2 px-3 py-1 bg-slate-200 dark:bg-slate-700 text-slate-700 dark:text-slate-200 rounded text-sm transition-colors">Batal</button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                    <?php elseif ($tabel === 'periode'): ?>
                        <h3 class="text-xl font-bold mb-6 text-slate-800 dark:text-slate-100">Edit Periode</h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label class="block text-xs font-medium text-slate-700 dark:text-slate-300 mb-1">Tahun Periode</label>
                                <input type="text" name="tahun_periode" value="<?= $data['tahun_periode']; ?>"
                                    class="w-full px-4 py-2 border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-slate-200 rounded-lg focus:outline-none focus:ring-1 focus:ring-indigo-500 dark:focus:ring-indigo-600 text-xs transition-colors">
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-slate-700 dark:text-slate-300 mb-1">Semester Nama</label>
                                <select name="semester_nama" class="w-full px-3 py-2 border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-slate-200 rounded-lg focus:outline-none focus:ring-1 focus:ring-indigo-500 dark:focus:ring-indigo-600 text-xs transition-colors" required>
                                    <option value="">-- Pilih Semester Nama --</option>
                                    <option value="Ganjil" <?= ($data['semester_nama'] == 'Ganjil') ? 'selected' : '' ?>>Ganjil</option>
                                    <option value="Genap" <?= ($data['semester_nama'] == 'Genap') ? 'selected' : '' ?>>Genap</option>
                                    <option value="Antara" <?= ($data['semester_nama'] == 'Antara') ? 'selected' : '' ?>>Antara</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-slate-700 dark:text-slate-300 mb-1">Semester Ke</label>
                                <select name="semester_ke" class="w-full px-3 py-2 border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-slate-200 rounded-lg focus:outline-none focus:ring-1 focus:ring-indigo-500 dark:focus:ring-indigo-600 text-xs transition-colors" required>
                                    <option value="">-- Semester Ke --</option>
                                    <?php for ($i = 1; $i <= 8; $i++): ?>
                                        <option value="<?= $i ?>" <?= ($data['semester_ke'] == $i) ? 'selected' : '' ?>><?= $i ?>
                                        </option>
                                    <?php endfor; ?>
                                </select>
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-slate-700 dark:text-slate-300 mb-1">Status</label>
                                <select name="status" class="w-full px-3 py-2 border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-slate-200 rounded-lg focus:outline-none focus:ring-1 focus:ring-indigo-500 dark:focus:ring-indigo-600 text-xs transition-colors" required>
                                    <option value="">-- Pilih Status --</option>
                                    <option value="aktif" <?= ($data['status'] == 'aktif') ? 'selected' : '' ?>>aktif</option>
                                    <option value="non-aktif" <?= ($data['status'] == 'non-aktif') ? 'selected' : '' ?>>non-aktif</option>
                                </select>
                            </div>
                        </div>

                    <?php elseif ($tabel === 'mata_kuliah'): ?>
                        <h3 class="text-xl font-bold mb-6 text-slate-800 dark:text-slate-100">Edit Mata Kuliah</h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <label class="block text-xs font-medium text-slate-700 dark:text-slate-300 mb-1">Kode Mata Kuliah</label>
                                    <input type="text" name="kode_matkul" value="<?= $data['kode_matkul']; ?>"
                                        class="w-full px-4 py-2 border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-slate-200 rounded-lg focus:outline-none focus:ring-1 focus:ring-indigo-500 dark:focus:ring-indigo-600 text-xs transition-colors">
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-slate-700 dark:text-slate-300 mb-1">Nama Mata Kuliah</label>
                                    <input type="text" name="nama_matkul" value="<?= $data['nama_matkul']; ?>"
                                        class="w-full px-4 py-2 border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-slate-200 rounded-lg focus:outline-none focus:ring-1 focus:ring-indigo-500 dark:focus:ring-indigo-600 text-xs transition-colors">
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-slate-700 dark:text-slate-300 mb-1">Semester</label>
                                    <select name="semester" class="w-full px-3 py-2 border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-slate-200 rounded-lg focus:outline-none focus:ring-1 focus:ring-indigo-500 dark:focus:ring-indigo-600 text-xs transition-colors" required">
                                        <option value="">-- Semester --</option>
                                        <?php for ($i = 1; $i <= 8; $i++): ?>
                                            <option value="<?= $i ?>" <?= ($data['semester'] == $i) ? 'selected' : '' ?>><?= $i ?>
                                            </option>
                                        <?php endfor; ?>
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-slate-700 dark:text-slate-300 mb-1">SKS</label>
                                    <select name="sks" class="w-full px-3 py-2 border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-slate-200 rounded-lg focus:outline-none focus:ring-1 focus:ring-indigo-500 dark:focus:ring-indigo-600 text-xs transition-colors" required>
                                        <option value="">-- Pilih SKS --</option>
                                        <option value="2" <?= ($data['sks'] == '2') ? 'selected' : '' ?>>2</option>
                                        <option value="3" <?= ($data['sks'] == '3') ? 'selected' : '' ?>>3</option>
                                        <option value="4" <?= ($data['sks'] == '4') ? 'selected' : '' ?>>4</option>
                                        <option value="6" <?= ($data['sks'] == '6') ? 'selected' : '' ?>>6</option>
                                    </select>
                                </div>
                            </div>

                        <?php elseif ($tabel === 'users'): ?>
                        <h3 class="text-xl font-bold mb-6 text-slate-800 dark:text-slate-100">Edit Users</h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <label class="block text-xs font-medium text-slate-700 dark:text-slate-300 mb-1">Nama Lengkap</label>
                                    <input type="text" value="<?= $data['nama_lengkap'] ?? 'Bukan Mahasiswa'; ?>" readonly
                                        class="w-full px-4 py-2 border border-slate-300 dark:border-slate-700 rounded-lg focus:outline-none bg-slate-100 dark:bg-slate-900/50 text-slate-500 dark:text-slate-400 text-xs cursor-not-allowed transition-colors">
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-slate-700 dark:text-slate-300 mb-1">Password</label>
                                    <input type="text" name="password" value="**********" readonly
                                        class="w-full px-4 py-2 border border-slate-300 dark:border-slate-700 rounded-lg focus:outline-none bg-slate-100 dark:bg-slate-900/50 text-slate-500 dark:text-slate-400 text-xs cursor-not-allowed transition-colors">
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-slate-700 dark:text-slate-300 mb-1">Email</label>
                                    <input type="text" name="email" value="<?= $data['email']; ?>"
                                        class="w-full px-4 py-2 border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-slate-200 rounded-lg focus:outline-none focus:ring-1 focus:ring-indigo-500 dark:focus:ring-indigo-600 text-xs transition-colors">
                                </div>
                                <div>
                                <label class="block text-xs font-medium text-slate-700 dark:text-slate-300 mb-1">Role</label>
                                <select name="role" class="w-full px-3 py-2 border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-slate-200 rounded-lg focus:outline-none focus:ring-1 focus:ring-indigo-500 dark:focus:ring-indigo-600 text-xs transition-colors" required>
                                    <option value="">-- Pilih Role/Peran --</option>
                                    <option value="Administrator Utama" <?= ($data['role'] == 'Administrator Utama') ? 'selected' : '' ?>>Administrator Utama</option>
                                    <option value="Ketua Tingkat" <?= ($data['role'] == 'Ketua Tingkat') ? 'selected' : '' ?>>Ketua Tingkat</option>
                                    <option value="Sekretaris" <?= ($data['role'] == 'Sekretaris') ? 'selected' : '' ?>>Sekretaris</option>
                                    <option value="Bendahara" <?= ($data['role'] == 'Bendahara') ? 'selected' : '' ?>>Bendahara</option>
                                    <option value="Koordinator Akademik" <?= ($data['role'] == 'Koordinator Akademik') ? 'selected' : '' ?>>Koordinator Akademik</option>
                                    <option value="Mahasiswa" <?= ($data['role'] == 'Mahasiswa') ? 'selected' : '' ?>>Mahasiswa</option>
                                </select>
                                </div>
                            <?php elseif ($tabel === 'dosen'): ?>
                                <h3 class="text-xl font-bold mb-6 text-slate-800 dark:text-slate-100">Edit Dosen</h3>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    <div>
                                        <label class="block text-xs font-medium text-slate-700 dark:text-slate-300 mb-1">Nama Dosen</label>
                                        <input type="text" name="nama_dosen" value="<?= $data['nama_dosen']; ?>"
                                            class="w-full px-4 py-2 border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-slate-200 rounded-lg focus:outline-none focus:ring-1 focus:ring-indigo-500 dark:focus:ring-indigo-600 text-xs transition-colors">
                                    </div>
                                    <div>
                                        <label class="block text-xs font-medium text-slate-700 dark:text-slate-300 mb-1">No HP</label>
                                        <input type="text" name="no_hp" value="<?= $data['no_hp']; ?>"
                                            class="w-full px-4 py-2 border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-slate-200 rounded-lg focus:outline-none focus:ring-1 focus:ring-indigo-500 dark:focus:ring-indigo-600 text-xs transition-colors">
                                    </div>
                                </div>
                            <?php elseif ($tabel === 'tugas'): ?>
                                <h3 class="text-xl font-bold mb-6 text-slate-800 dark:text-slate-100">Edit Tugas</h3>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    <div class="md:col-span-2">
                                        <label class="block text-xs font-medium text-slate-700 dark:text-slate-300 mb-1">Mata Kuliah</label>
                                        <select name="matkul_id" class="w-full px-4 py-2 border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-slate-200 rounded-lg focus:outline-none focus:ring-1 focus:ring-indigo-500 dark:focus:ring-indigo-600 text-xs transition-colors" required>
                                            <option value="">-- Pilih Mata Kuliah --</option>
                                            <?php
                                            $query_mk = mysqli_query($koneksi, "SELECT DISTINCT
                                        mk.id AS matkul_id,
                                        mk.nama_matkul
                                        FROM krs k
                                        JOIN mata_kuliah mk ON k.matkul_id = mk.id
                                        ORDER BY mk.nama_matkul ASC");
                                            while ($mk = mysqli_fetch_assoc($query_mk)) {
                                                $selected = ($data['matkul_id'] == $mk['matkul_id']) ? 'selected' : '';
                                                echo "<option value='{$mk['matkul_id']}' $selected>{$mk['nama_matkul']}</option>";
                                            }
                                            ?>
                                        </select>
                                    </div>
                                    <div>
                                        <label class="block text-xs font-medium text-slate-700 dark:text-slate-300 mb-1">Deadline Tanggal</label>
                                        <input type="date" name="deadline_tanggal" value="<?= $data['deadline_tanggal']; ?>"
                                            class="w-full px-4 py-2 border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-slate-200 rounded-lg focus:outline-none focus:ring-1 focus:ring-indigo-500 dark:focus:ring-indigo-600 text-xs transition-colors">
                                    </div>
                                    <div>
                                        <label class="block text-xs font-medium text-slate-700 dark:text-slate-300 mb-1">Deadline Pukul</label>
                                        <input type="time" name="deadline_pukul" value="<?= $data['deadline_pukul']; ?>"
                                            class="w-full px-4 py-2 border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-slate-200 rounded-lg focus:outline-none focus:ring-1 focus:ring-indigo-500 dark:focus:ring-indigo-600 text-xs transition-colors">
                                    </div>
                                    <div class="md:col-span-2">
                                        <label class="block text-xs font-medium text-slate-700 dark:text-slate-300 mb-1">Deskripsi</label>
                                        <textarea name="deskripsi" rows="5"
                                            class="w-full px-4 py-2 border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-slate-200 rounded-lg focus:outline-none focus:ring-1 focus:ring-indigo-500 dark:focus:ring-indigo-600 text-xs transition-colors"><?= $data['deskripsi']; ?></textarea>
                                    </div>
                                    <div>
                                        <label class="block text-xs font-medium text-slate-700 dark:text-slate-300 mb-1">File Tugas</label>
                                        <input type="file" name="file_tugas" value="<?= $data['file_tugas']; ?>"
                                            class="w-full px-4 py-2 border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-slate-200 rounded-lg focus:outline-none focus:ring-1 focus:ring-indigo-500 dark:focus:ring-indigo-600 text-xs transition-colors">
                                    </div>
                                    <div>
                                        <label class="block text-xs font-medium text-slate-700 dark:text-slate-300 mb-1">Link Kirim</label>
                                        <input type="text" name="link_kirim" value="<?= $data['link_kirim']; ?>"
                                            class="w-full px-4 py-2 border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-slate-200 rounded-lg focus:outline-none focus:ring-1 focus:ring-indigo-500 dark:focus:ring-indigo-600 text-xs transition-colors">
                                    </div>
                                </div>
                            <?php elseif ($tabel === 'jadwal_matkul'): ?>
                                <h3 class="text-xl font-bold mb-6 text-slate-800 dark:text-slate-100">Jadwal Mata Kuliah</h3>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    <div>
                                        <label class="block text-xs font-medium text-slate-700 dark:text-slate-300 mb-1">Mata Kuliah</label>
                                        <select name="matkul_id" class="w-full px-4 py-2 border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-slate-200 rounded-lg focus:outline-none focus:ring-1 focus:ring-indigo-500 dark:focus:ring-indigo-600 text-xs transition-colors" required>
                                            <option value="">-- Pilih Mata Kuliah --</option>
                                            <?php

                                            $query_mk = mysqli_query($koneksi, "SELECT DISTINCT
                                        mk.id AS matkul_id,
                                        mk.nama_matkul
                                        FROM krs k
                                        JOIN mata_kuliah mk ON k.matkul_id = mk.id
                                        ORDER BY mk.nama_matkul ASC");
                                            while ($mk = mysqli_fetch_assoc($query_mk)) {
                                                $selected = ($data['matkul_id'] == $mk['matkul_id']) ? 'selected' : '';
                                                echo "<option value='{$mk['matkul_id']}' $selected>{$mk['nama_matkul']}</option>";
                                            }
                                            ?>
                                        </select>
                                    </div>
                                    <div>
                                        <label class="block text-xs font-medium text-slate-700 dark:text-slate-300 mb-1">Hari</label>
                                        <select name="hari" class="w-full px-4 py-2 border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-slate-200 rounded-lg focus:outline-none focus:ring-1 focus:ring-indigo-500 dark:focus:ring-indigo-600 text-xs transition-colors" required>
                                            <option value="">-- Pilih Hari --</option>
                                            <option value="Senin" <?= ($data['hari'] == 'Senin') ? 'selected' : '' ?>>Senin</option>
                                            <option value="Selasa" <?= ($data['hari'] == 'Selasa') ? 'selected' : '' ?>>Selasa</option>
                                            <option value="Rabu" <?= ($data['hari'] == 'Rabu') ? 'selected' : '' ?>>Rabu</option>
                                            <option value="Kamis" <?= ($data['hari'] == 'Kamis') ? 'selected' : '' ?>>Kamis</option>
                                            <option value="Jumat" <?= ($data['hari'] == 'Jumat') ? 'selected' : '' ?>>Jumat</option>
                                        </select>
                                    </div>
                                    <div>
                                        <label class="block text-xs font-medium text-slate-700 dark:text-slate-300 mb-1">Ruangan</label>
                                        <select name="ruangan_id" class="w-full px-4 py-2 border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-slate-200 rounded-lg focus:outline-none focus:ring-1 focus:ring-indigo-500 dark:focus:ring-indigo-600 text-xs transition-colors" required>
                                            <option value="">-- Pilih Ruangan --</option>
                                            <?php
                                            $query_ruang = mysqli_query($koneksi, "SELECT * FROM ruangan ORDER BY nama_ruangan ASC");
                                            while ($r = mysqli_fetch_assoc($query_ruang)) {
                                                $selected = ($data['ruangan_id'] == $r['id']) ? 'selected' : '';
                                                echo "<option value='{$r['id']}' $selected>{$r['nama_ruangan']}</option>";
                                            }
                                            ?>
                                        </select>
                                    </div>
                                    <div>
                                        <label class="block text-xs font-medium text-slate-700 dark:text-slate-300 mb-1">Jam Mulai Ke</label>
                                        <select name="jam_mulai_ke" class="w-full px-4 py-2 border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-slate-200 rounded-lg focus:outline-none focus:ring-1 focus:ring-indigo-500 dark:focus:ring-indigo-600 text-xs transition-colors" required>
                                            <option value="">-- Pilih Jam Mulai --</option>
                                            <?php
                                            $query_jk = mysqli_query($koneksi, "SELECT * FROM jam_kuliah ORDER BY jam_mulai ASC");
                                            $jam_kuliah_data = [];
                                            while ($jk = mysqli_fetch_assoc($query_jk)) {
                                                $jam_kuliah_data[] = $jk;
                                                $selected = ($data['jam_mulai_ke'] == $jk['jam_ke']) ? 'selected' : '';
                                                $display = "Ke-{$jk['jam_ke']} (" . date('H:i', strtotime($jk['jam_mulai'])) . " - " . date('H:i', strtotime($jk['jam_selesai'])) . ")";
                                                echo "<option value='{$jk['jam_ke']}' $selected>$display</option>";
                                            }
                                            ?>
                                        </select>
                                    </div>
                                    <div>
                                        <label class="block text-xs font-medium text-slate-700 dark:text-slate-300 mb-1">Jam Selesai Ke</label>
                                        <select name="jam_selesai_ke" class="w-full px-4 py-2 border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-slate-200 rounded-lg focus:outline-none focus:ring-1 focus:ring-indigo-500 dark:focus:ring-indigo-600 text-xs transition-colors" required>
                                            <option value="">-- Pilih Jam Selesai --</option>
                                            <?php
                                            foreach ($jam_kuliah_data as $jk) {
                                                $selected = ($data['jam_selesai_ke'] == $jk['jam_ke']) ? 'selected' : '';
                                                $display = "Ke-{$jk['jam_ke']} (" . date('H:i', strtotime($jk['jam_mulai'])) . " - " . date('H:i', strtotime($jk['jam_selesai'])) . ")";
                                                echo "<option value='{$jk['jam_ke']}' $selected>$display</option>";
                                            }
                                            ?>
                                        </select>
                                    </div>

                                    <div>
                                        <label class="block text-xs font-medium text-slate-700 dark:text-slate-300 mb-1">Dosen Pengampu</label>
                                        <select name="dosen_pengampu_id" class="w-full px-4 py-2 border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-slate-200 rounded-lg focus:outline-none focus:ring-1 focus:ring-indigo-500 dark:focus:ring-indigo-600 text-xs transition-colors" required>
                                            <option value="">-- Pilih Dosen Pengampu --</option>
                                            <?php
                                            $query_dp = mysqli_query($koneksi, "SELECT * FROM dosen ORDER BY nama_dosen ASC");
                                            while ($dp = mysqli_fetch_assoc($query_dp)) {
                                                $selected = ($data['dosen_pengampu_id'] == $dp['id']) ? 'selected' : '';
                                                echo "<option value='{$dp['id']}' $selected>{$dp['nama_dosen']}</option>";
                                            }
                                            ?>
                                        </select>
                                    </div>
                                    <div>
                                        <label class="block text-xs font-medium text-slate-700 dark:text-slate-300 mb-1">Dosen Mitra</label>
                                        <select name="dosen_mitra_id" class="w-full px-4 py-2 border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-slate-200 rounded-lg focus:outline-none focus:ring-1 focus:ring-indigo-500 dark:focus:ring-indigo-600 text-xs transition-colors" required>
                                            <option value="">-- Pilih Dosen Mitra --</option>
                                            <?php
                                            $query_dm = mysqli_query($koneksi, "SELECT * FROM dosen ORDER BY nama_dosen ASC");
                                            while ($dm = mysqli_fetch_assoc($query_dm)) {
                                                $selected = ($data['dosen_mitra_id'] == $dm['id']) ? 'selected' : '';
                                                echo "<option value='{$dm['id']}' $selected>{$dm['nama_dosen']}</option>";
                                            }
                                            ?>
                                        </select>
                                    </div>
                                </div>
                            </div>
                        <?php elseif ($tabel === 'galeri'): ?>
                            <h3 class="text-xl font-bold mb-6 text-slate-800 dark:text-slate-100">Edit Galeri Kegiatan</h3>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <label class="block text-xs font-medium text-slate-700 dark:text-slate-300 mb-1">Judul Kegiatan</label>
                                    <input type="text" name="judul" value="<?= htmlspecialchars($data['judul']); ?>" required
                                        class="w-full px-4 py-2 border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-slate-200 rounded-lg focus:outline-none focus:ring-1 focus:ring-indigo-500 dark:focus:ring-indigo-600 text-xs transition-colors">
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-slate-700 dark:text-slate-300 mb-1">Tanggal Kegiatan</label>
                                    <input type="date" name="tanggal_kegiatan" value="<?= $data['tanggal_kegiatan']; ?>" required
                                        class="w-full px-4 py-2 border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-slate-200 rounded-lg focus:outline-none focus:ring-1 focus:ring-indigo-500 dark:focus:ring-indigo-600 text-xs transition-colors">
                                </div>
                                <div class="md:col-span-2">
                                    <label class="block text-xs font-medium text-slate-700 dark:text-slate-300 mb-1">Tambah Foto Baru</label>
                                    <input type="file" name="fotos[]" multiple accept="image/*"
                                        class="w-full px-4 py-2 border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-slate-200 rounded-lg focus:outline-none focus:ring-1 focus:ring-indigo-500 dark:focus:ring-indigo-600 text-xs transition-colors">
                                </div>
                                <div class="md:col-span-2">
                                    <label class="block text-xs font-medium text-slate-700 dark:text-slate-300 mb-1">Kelola Foto Saat Ini (Centang untuk hapus)</label>
                                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 mt-2">
                                        <?php
                                        $q_f = mysqli_query($koneksi, "SELECT * FROM galeri_foto WHERE galeri_id = '$id'");
                                        while ($f = mysqli_fetch_assoc($q_f)) {
                                        ?>
                                            <div class="relative group">
                                                <img src="../storage/gallery/<?= htmlspecialchars($f['foto_path']) ?>" class="w-full h-24 object-cover rounded-lg border dark:border-slate-700 transition-colors">
                                                <div class="absolute top-1 right-1">
                                                    <input type="checkbox" name="delete_photos[]" value="<?= $f['id'] ?>" class="w-4 h-4 text-red-600 bg-white dark:bg-slate-800 border-slate-300 dark:border-slate-700 rounded focus:ring-red-500 transition-colors">
                                                </div>
                                            </div>
                                        <?php
                                        }
                                        ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php elseif ($tabel === 'akademik'): ?>
                        <h3 class="text-xl font-bold mb-6 text-slate-800 dark:text-slate-100">Edit Berita & Informasi Akademik</h3>
                        <div class="grid grid-cols-1 gap-6">
                            <div>
                                <label class="block text-xs font-medium text-slate-700 dark:text-slate-300 mb-1">Judul Berita</label>
                                <input type="text" name="judul" value="<?= htmlspecialchars($data['judul']); ?>" required
                                    class="w-full px-4 py-2 border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-slate-200 rounded-lg focus:outline-none focus:ring-1 focus:ring-indigo-500 dark:focus:ring-indigo-600 text-xs transition-colors">
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-slate-700 dark:text-slate-300 mb-1">Deskripsi</label>
                                <textarea name="deskripsi" rows="3" required
                                    class="w-full px-4 py-2 border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-slate-200 rounded-lg focus:outline-none focus:ring-1 focus:ring-indigo-500 dark:focus:ring-indigo-600 text-xs transition-colors"><?= htmlspecialchars($data['deskripsi']); ?></textarea>
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-slate-700 dark:text-slate-300 mb-1">Link (Opsional)</label>
                                <input type="text" name="link" value="<?= htmlspecialchars($data['link']); ?>"
                                    class="w-full px-4 py-2 border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-slate-200 rounded-lg focus:outline-none focus:ring-1 focus:ring-indigo-500 dark:focus:ring-indigo-600 text-xs transition-colors">
                            </div>
                        </div>
                    <?php elseif ($tabel === 'kalender_akademik'): ?>
                        <h3 class="text-xl font-bold mb-6 text-slate-800 dark:text-slate-100">Edit Agenda Kalender Akademik</h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label class="block text-xs font-medium text-slate-700 dark:text-slate-300 mb-1">Periode</label>
                                <select name="periode_id" required
                                    class="w-full px-4 py-2 border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-slate-200 rounded-lg focus:outline-none focus:ring-1 focus:ring-indigo-500 dark:focus:ring-indigo-600 text-xs transition-colors">
                                    <option value="">-- Pilih Periode --</option>
                                    <?php
                                    $q_per = mysqli_query($koneksi, "SELECT * FROM periode ORDER BY id DESC");
                                    while ($per = mysqli_fetch_assoc($q_per)) {
                                        $selected = ($per['id'] == $data['periode_id']) ? 'selected' : '';
                                        echo "<option value='{$per['id']}' $selected>{$per['tahun_periode']} - {$per['semester_nama']}</option>";
                                    }
                                    ?>
                                </select>
                            </div>
                            <div class="md:col-span-2">
                                <label class="block text-xs font-medium text-slate-700 dark:text-slate-300 mb-1">Nama Kegiatan</label>
                                <input name="kegiatan" required type="text" value="<?= htmlspecialchars($data['kegiatan']); ?>"
                                    class="w-full px-4 py-2 border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-slate-200 rounded-lg focus:outline-none focus:ring-1 focus:ring-indigo-500 dark:focus:ring-indigo-600 text-xs transition-colors">
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-slate-700 dark:text-slate-300 mb-1">Tanggal Mulai</label>
                                <input type="date" name="tanggal_mulai" value="<?= $data['tanggal_mulai']; ?>" required
                                    class="w-full px-4 py-2 border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-slate-200 rounded-lg focus:outline-none focus:ring-1 focus:ring-indigo-500 dark:focus:ring-indigo-600 text-xs transition-colors">
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-slate-700 dark:text-slate-300 mb-1">Tanggal Selesai</label>
                                <input type="date" name="tanggal_selesai" value="<?= $data['tanggal_selesai']; ?>" required
                                    class="w-full px-4 py-2 border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-slate-200 rounded-lg focus:outline-none focus:ring-1 focus:ring-indigo-500 dark:focus:ring-indigo-600 text-xs transition-colors">
                            </div>
                        </div>
                    <?php elseif ($tabel === 'ruangan'): ?>
                        <h3 class="text-xl font-bold mb-6 text-slate-800 dark:text-slate-100">Edit Ruangan</h3>
                        <div class="grid grid-cols-1 gap-6">
                            <div>
                                <label class="block text-xs font-medium text-slate-700 dark:text-slate-300 mb-1">Nama Ruangan</label>
                                <input type="text" name="nama_ruangan" value="<?= htmlspecialchars($data['nama_ruangan']); ?>" required
                                    class="w-full px-4 py-2 border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-slate-200 rounded-lg focus:outline-none focus:ring-1 focus:ring-indigo-500 dark:focus:ring-indigo-600 text-xs transition-colors">
                            </div>
                        </div>
                    <?php elseif ($tabel === 'jam_kuliah'): ?>
                        <h3 class="text-xl font-bold mb-6 text-slate-800 dark:text-slate-100">Edit Jam Kuliah</h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label class="block text-xs font-medium text-slate-700 dark:text-slate-300 mb-1">Jam Ke</label>
                                <input type="number" value="<?= htmlspecialchars($data['jam_ke']); ?>" disabled
                                    class="w-full px-4 py-2 border border-slate-300 dark:border-slate-700 bg-slate-100 dark:bg-slate-900/50 text-slate-500 dark:text-slate-400 text-xs cursor-not-allowed transition-colors rounded-lg">
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-slate-700 dark:text-slate-300 mb-1">Jam Mulai</label>
                                <input type="time" name="jam_mulai" value="<?= htmlspecialchars($data['jam_mulai']); ?>" required
                                    class="w-full px-4 py-2 border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-slate-200 rounded-lg focus:outline-none focus:ring-1 focus:ring-indigo-500 dark:focus:ring-indigo-600 text-xs transition-colors">
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-slate-700 dark:text-slate-300 mb-1">Jam Selesai</label>
                                <input type="time" name="jam_selesai" value="<?= htmlspecialchars($data['jam_selesai']); ?>" required
                                    class="w-full px-4 py-2 border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-slate-200 rounded-lg focus:outline-none focus:ring-1 focus:ring-indigo-500 dark:focus:ring-indigo-600 text-xs transition-colors">
                            </div>
                        </div>
                    <?php elseif ($tabel === 'kuis_harian_soal'): ?>
                        <h3 class="text-xl font-bold mb-6 text-slate-800 dark:text-slate-100">Edit Soal Kuis Harian</h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label class="block text-xs font-medium text-slate-700 dark:text-slate-300 mb-1">Tanggal</label>
                                <input type="date" name="tanggal" value="<?= htmlspecialchars($data['tanggal']); ?>" required
                                    class="w-full px-4 py-2 border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-slate-200 rounded-lg focus:outline-none focus:ring-1 focus:ring-indigo-500 dark:focus:ring-indigo-600 text-xs transition-colors">
                            </div>
                            <div class="md:col-span-2">
                                <label class="block text-xs font-medium text-slate-700 dark:text-slate-300 mb-1">Pertanyaan</label>
                                <textarea name="pertanyaan" rows="3" required
                                    class="w-full px-4 py-2 border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-slate-200 rounded-lg focus:outline-none focus:ring-1 focus:ring-indigo-500 dark:focus:ring-indigo-600 text-xs transition-colors"><?= htmlspecialchars($data['pertanyaan']); ?></textarea>
                            </div>
                            <div class="md:col-span-2">
                                <label class="block text-xs font-medium text-slate-700 dark:text-slate-300 mb-1">Jawaban</label>
                                <input type="text" name="jawaban" value="<?= htmlspecialchars($data['jawaban']); ?>" required
                                    class="w-full px-4 py-2 border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-slate-200 rounded-lg focus:outline-none focus:ring-1 focus:ring-indigo-500 dark:focus:ring-indigo-600 text-xs transition-colors">
                            </div>
                        </div>
                    <?php endif; ?>



                </div>
                <div class="flex justify-end mt-4">
                    <button type="submit" name="update"
                        class="px-6 py-2 bg-indigo-800 dark:bg-indigo-700 text-white rounded-lg hover:bg-indigo-700 dark:hover:bg-indigo-600 text-xs transition-all shadow-lg shadow-indigo-100 dark:shadow-indigo-950/20">Submit</button>
                </div>
            </form>
        </div>
        <?php include '../includes/footer_db.php'; ?>
    </main>
    <?php include '../includes/script_db.php'; ?>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.5.13/cropper.min.js"></script>
    <script>
        // Image cropper for profile foto
        (function() {
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

            zoomIn.addEventListener('click', () => {
                if (cropper) cropper.zoom(0.1);
            });
            zoomOut.addEventListener('click', () => {
                if (cropper) cropper.zoom(-0.1);
            });
            rotL.addEventListener('click', () => {
                if (cropper) cropper.rotate(-90);
            });
            rotR.addEventListener('click', () => {
                if (cropper) cropper.rotate(90);
            });

            cancelBtn.addEventListener('click', () => {
                if (cropper) {
                    cropper.destroy();
                    cropper = null;
                }
                cropperArea.classList.add('hidden');
                fotoInput.value = '';
            });

            applyBtn.addEventListener('click', () => {
                if (!cropper) return;
                const canvas = cropper.getCroppedCanvas({
                    width: 512,
                    height: 512,
                    imageSmoothingQuality: 'high'
                });
                canvas.toBlob((blob) => {
                    const reader = new FileReader();
                    reader.onloadend = () => {
                        fotoDataInput.value = reader.result; // base64 data URL
                        currentPreview.src = reader.result;
                        if (cropper) {
                            cropper.destroy();
                            cropper = null;
                        }
                        cropperArea.classList.add('hidden');
                    };
                    reader.readAsDataURL(blob);
                }, 'image/webp', 0.8);
            });

            form.addEventListener('submit', function(e) {
                if (cropper && !fotoDataInput.value) {
                    e.preventDefault();
                    const canvas = cropper.getCroppedCanvas({
                        width: 512,
                        height: 512,
                        imageSmoothingQuality: 'high'
                    });
                    canvas.toBlob((blob) => {
                        const reader = new FileReader();
                        reader.onloadend = () => {
                            fotoDataInput.value = reader.result;
                            if (cropper) {
                                cropper.destroy();
                                cropper = null;
                            }
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