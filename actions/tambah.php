<?php
session_start();
include '../koneksi.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../../index");
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
                $target_dir = "../storage/profile/";
                $foto = time() . "_" . basename($_FILES["foto"]["name"]);
                $target_file = $target_dir . $foto;
                move_uploaded_file($_FILES["foto"]["tmp_name"], $target_file);
            }
            mysqli_query($koneksi, "INSERT INTO mahasiswa (nama_lengkap, nama_panggilan, email, nim, bio, jenis_kelamin, instagram, foto)
                VALUES ('$nama_lengkap','$nama_panggilan','$email','$nim','$bio','$jenis_kelamin','$instagram','$foto')");
            $redirect = "../database/db_mahasiswa";

            break;

        case 'periode':
            $tahun_periode = $_POST['tahun_periode'];
            $semester_nama = $_POST['semester_nama'];
            $semester_ke = $_POST['semester_ke'];
            $status = $_POST['status'];
            mysqli_query($koneksi, "INSERT INTO periode (tahun_periode, semester_nama, semester_ke, status)
                VALUES ('$tahun_periode','$semester_nama','$semester_ke','$status')");
            $redirect = "../database/db_periode";

            break;

        case 'mata_kuliah':
            $kode_matkul = $_POST['kode_matkul'];
            $nama_matkul = $_POST['nama_matkul'];
            $semester = $_POST['semester'];
            $sks = $_POST['sks'];
            mysqli_query($koneksi, "INSERT INTO mata_kuliah (kode_matkul, nama_matkul, semester, sks)
                VALUES ('$kode_matkul','$nama_matkul','$semester','$sks')");
            $redirect = "../database/db_matkul";

            break;

        case 'jadwal_matkul':
            $matkul_id = $_POST['matkul_id'];
            $hari = $_POST['hari'];
            $jam_mulai_ke = $_POST['jam_mulai_ke'];
            $jam_selesai_ke = $_POST['jam_selesai_ke'];
            $ruangan_id = $_POST['ruangan_id'];
            $dosen_pengampu_id = $_POST['dosen_pengampu_id'];
            $dosen_mitra_id = $_POST['dosen_mitra_id'];

            // Get active periode_id
            $q_periode = mysqli_query($koneksi, "SELECT id FROM periode WHERE status = 'aktif' LIMIT 1");
            $periode = mysqli_fetch_assoc($q_periode);
            $periode_id = $periode['id'] ?? 0;

            mysqli_query($koneksi, "INSERT INTO jadwal_matkul (matkul_id, hari, jam_mulai_ke, jam_selesai_ke, ruangan_id, dosen_pengampu_id, dosen_mitra_id, periode_id)
                VALUES ('$matkul_id', '$hari', '$jam_mulai_ke', '$jam_selesai_ke', '$ruangan_id', '$dosen_pengampu_id', '$dosen_mitra_id', '$periode_id')");
            $redirect = "../database/db_jadwal";

            break;

        case 'users':
            $id = $_POST['id'];
            $password = $_POST['password'];
            $role = $_POST['role'];
            mysqli_query($koneksi, "INSERT INTO users (id, password, role)
                VALUES ('$id','$password','$role')");
            $redirect = "../database/db_users";

            break;

        case 'dosen':
            $nama_dosen = $_POST['nama_dosen'];
            $no_hp = $_POST['no_hp'];
            mysqli_query($koneksi, "INSERT INTO dosen (nama_dosen, no_hp)
                VALUES ('$nama_dosen','$no_hp')");
            $redirect = "../database/db_dosen";

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
                $target_dir = "../storage/tugas/";
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
            include_once '../functions/kirim_tugas.php';
            $telegram_token = "8212322367:AAGCGc4TG__EnsEd-20wFTI_s2adp-dp1OY";
            $telegram_chat_id = "-1003577478223";

            notifyNewTask($telegram_token, $telegram_chat_id, $nama_matkul, $deadline_tanggal, $deadline_pukul, $deskripsi, $link_kirim);

            $redirect = "../database/db_tugas";
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
                $target_dir = "../storage/tugas/";
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
            $redirect = "../database/db_tugas";

            break;

        case 'galeri':
            $judul = mysqli_real_escape_string($koneksi, $_POST['judul']);
            $tanggal_kegiatan = $_POST['tanggal_kegiatan'];

            mysqli_query($koneksi, "INSERT INTO galeri (judul, tanggal_kegiatan) VALUES ('$judul', '$tanggal_kegiatan')");
            $galeri_id = mysqli_insert_id($koneksi);

            if (!empty($_FILES['fotos']['name'][0])) {
                $target_dir = __DIR__ . "../../storage/gallery/"; // Absolute path for safety
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
                        mysqli_query($koneksi, "INSERT INTO galeri_foto (galeri_id, foto_path) VALUES ('$galeri_id', '$db_path')");
                    }
                }
            }
            $redirect = "../database/db_galeri";
            break;

        case 'akademik':
            $judul = mysqli_real_escape_string($koneksi, $_POST['judul']);
            $deskripsi = mysqli_real_escape_string($koneksi, $_POST['deskripsi']);
            $link = mysqli_real_escape_string($koneksi, $_POST['link']);

            mysqli_query($koneksi, "INSERT INTO akademik (judul, deskripsi, link) VALUES ('$judul', '$deskripsi', '$link')");
            $redirect = "../database/db_berita";
            break;

        case 'kalender_akademik':
            $periode_id = intval($_POST['periode_id']);
            $kegiatan = mysqli_real_escape_string($koneksi, $_POST['kegiatan']);
            $tanggal_mulai = $_POST['tanggal_mulai'];
            $tanggal_selesai = $_POST['tanggal_selesai'];

            mysqli_query($koneksi, "INSERT INTO kalender_akademik (periode_id, kegiatan, tanggal_mulai, tanggal_selesai) VALUES ('$periode_id', '$kegiatan', '$tanggal_mulai', '$tanggal_selesai')");
            $redirect = "../database/db_kalender";
            break;

        case 'ruangan':
            $nama_ruangan = mysqli_real_escape_string($koneksi, $_POST['nama_ruangan']);
            mysqli_query($koneksi, "INSERT INTO ruangan (nama_ruangan) VALUES ('$nama_ruangan')");
            $redirect = "../database/db_ruangan";
            break;

        case 'jam_kuliah':
            $jam_ke = intval($_POST['jam_ke']);
            $jam_mulai = $_POST['jam_mulai'];
            $jam_selesai = $_POST['jam_selesai'];
            mysqli_query($koneksi, "INSERT INTO jam_kuliah (jam_ke, jam_mulai, jam_selesai) VALUES ('$jam_ke', '$jam_mulai', '$jam_selesai')");
            $redirect = "../database/db_jam";
            break;

        case 'kuis_harian_soal':
            $tanggal = $_POST['tanggal'];
            $pertanyaan = mysqli_real_escape_string($koneksi, $_POST['pertanyaan']);
            $jawaban = mysqli_real_escape_string($koneksi, $_POST['jawaban']);
            mysqli_query($koneksi, "INSERT INTO kuis_harian_soal (tanggal, pertanyaan, jawaban) VALUES ('$tanggal', '$pertanyaan', '$jawaban')");
            $redirect = "../database/db_kuis";
            break;
    }
    include '../includes/alert_actions.php';

    exit;
}
?>
<!DOCTYPE html>
<html lang="en">

<head>

    <title>Tambah - PTIK C 2024</title>
    <?php include '../includes/head.php'; ?>
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

<body class="bg-slate-100">
    <?php include '../includes/menu_db.php'; ?>

    <main class="ml-0 lg:ml-48 pt-20 p-6 flex flex-col min-h-screen">
        <div class="bg-white rounded-lg border border-slate-200 p-6 flex-grow w-full">


            <form class="space-y-6" method="POST" enctype="multipart/form-data">
                <div class="border-b border-slate-200 pb-6">
                    <?php if ($tabel === 'mahasiswa'): ?>
                        <h3 class="text-xl font-bold mb-6">Mahasiswa</h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label class="block text-xs font-medium text-slate-700 mb-1">Nama Lengkap</label>
                                <input type="text" name="nama_lengkap"
                                    class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-1 focus:ring-slate-400 text-xs">
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-slate-700 mb-1">Nama Panggilan</label>
                                <input type="text" name="nama_panggilan"
                                    class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-1 focus:ring-slate-400 text-xs">
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-slate-700 mb-1">Email</label>
                                <input type="email" name="email"
                                    class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-1 focus:ring-slate-400 text-xs">
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-slate-700 mb-1">NIM</label>
                                <input type="number" name="nim"
                                    class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-1 focus:ring-slate-400 text-xs">
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-slate-700 mb-1">Jenis Kelamin</label>
                                <select name="jenis_kelamin"
                                    class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-1 focus:ring-slate-400 text-xs">
                                    <option value="">-- Pilih Jenis Kelamin --</option>
                                    <option value="Laki-laki">Laki-laki</option>
                                    <option value="Perempuan">Perempuan</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-slate-700 mb-1">Instagram</label>
                                <input type="text" name="instagram" placeholder="@username"
                                    class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-1 focus:ring-slate-400 text-xs">
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-slate-700 mb-1">Biodata</label>
                                <input type="text" name="bio"
                                    class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-1 focus:ring-slate-400 text-xs">
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-slate-700 mb-1">Foto</label>
                                <input type="file" name="foto" accept="image/*"
                                    class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-1 focus:ring-slate-400 text-xs">
                            </div>
                        </div>

                    <?php elseif ($tabel === 'periode'): ?>
                        <h3 class="text-xl font-bold mb-6">Tambah Periode</h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label class="block text-xs font-medium text-slate-700 mb-1">Tahun Periode</label>
                                <input type="text" name="tahun_periode"
                                    class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-1 focus:ring-slate-400 text-xs">
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-slate-700 mb-1">Semester Nama</label>
                                <select name="semester_nama" class="w-full px-3 py-2 border border-slate-300 rounded-lg text-xs" required>
                                    <option value="">-- Pilih Semester Nama --</option>
                                    <option value="Ganjil">Ganjil</option>
                                    <option value="Genap">Genap</option>
                                    <option value="Antara">Antara</option>
                                </select>
                            </div>

                            <div>
                                <label class="block text-xs font-medium text-slate-700 mb-1">Semester Ke</label>
                                <select name="semester_ke" class="w-full px-3 py-2 border border-slate-300 rounded-lg text-xs" required">
                                    <option value="">-- Semester Ke --</option>
                                    <?php for ($i = 1; $i <= 8; $i++): ?>
                                        <option value="<?= $i ?>"><?= $i ?>
                                        </option>
                                    <?php endfor; ?>
                                </select>
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-slate-700 mb-1">Status</label>
                                <select name="status" class="w-full px-3 py-2 border border-slate-300 rounded-lg text-xs" required>
                                    <option value="">-- Pilih Status --</option>
                                    <option value="aktif">aktif</option>
                                    <option value="non-aktif">non-aktif</option>
                                </select>
                            </div>
                        </div>

                    <?php elseif ($tabel === 'mata_kuliah'): ?>
                        <h3 class="text-xl font-bold mb-6">Mata Kuliah</h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label class="block text-xs font-medium text-slate-700 mb-1">Kode Mata Kuliah</label>
                                <input type="text" name="kode_matkul"
                                    class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-1 focus:ring-slate-400 text-xs">
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-slate-700 mb-1">Nama Mata Kuliah</label>
                                <input type="text" name="nama_matkul"
                                    class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-1 focus:ring-slate-400 text-xs">
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-slate-700 mb-1">Semester</label>
                                <select name="semester" class="w-full px-3 py-2 border border-slate-300 rounded-lg text-xs" required">
                                    <option value="">-- Semester --</option>
                                    <?php for ($i = 1; $i <= 8; $i++): ?>
                                        <option value="<?= $i ?>"><?= $i ?>
                                        </option>
                                    <?php endfor; ?>
                                </select>
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-slate-700 mb-1">SKS</label>
                                <select name="sks" class="w-full px-3 py-2 border border-slate-300 rounded-lg text-xs" required>
                                    <option value="">-- Pilih SKS --</option>
                                    <option value="2">2</option>
                                    <option value="3">3</option>
                                    <option value="4">4</option>
                                    <option value="6">6</option>
                                </select>
                            </div>
                        </div>

                    <?php elseif ($tabel === 'jadwal_matkul'): ?>
                        <h3 class="text-xl font-bold mb-6">Jadwal Mata Kuliah</h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div class="col-span-2">
                                <label class="block text-xs font-medium text-slate-700 mb-1">Mata Kuliah</label>
                                <select name="matkul_id" class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-1 focus:ring-slate-400 text-xs" required>
                                    <option value="">-- Pilih Mata Kuliah --</option>
                                    <?php
                                    // Get active periode_id
                                    $q_per_add = mysqli_query($koneksi, "SELECT id FROM periode WHERE status = 'aktif' LIMIT 1");
                                    $per_add = mysqli_fetch_assoc($q_per_add);
                                    $pid_add = $per_add['id'] ?? 0;

                                    $query_mk = mysqli_query($koneksi, "SELECT DISTINCT
                                        mk.id AS matkul_id,
                                        mk.nama_matkul
                                        FROM krs k
                                        JOIN mata_kuliah mk ON k.matkul_id = mk.id
                                        WHERE mk.id NOT IN (SELECT matkul_id FROM jadwal_matkul WHERE periode_id = '$pid_add')
                                        ORDER BY mk.nama_matkul ASC");
                                    while ($mk = mysqli_fetch_assoc($query_mk)) {
                                        echo "<option value='{$mk['matkul_id']}'>{$mk['nama_matkul']}</option>";
                                    }
                                    ?>
                                </select>
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-slate-700 mb-1">Hari</label>
                                <select name="hari" class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-1 focus:ring-slate-400 text-xs" required>
                                    <option value="">-- Pilih Hari --</option>
                                    <option value="Senin">Senin</option>
                                    <option value="Selasa">Selasa</option>
                                    <option value="Rabu">Rabu</option>
                                    <option value="Kamis">Kamis</option>
                                    <option value="Jumat">Jumat</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-slate-700 mb-1">Ruangan</label>
                                <select name="ruangan_id" class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-1 focus:ring-slate-400 text-xs" required>
                                    <option value="">-- Pilih Ruangan --</option>
                                    <?php
                                    $query_ruang = mysqli_query($koneksi, "SELECT * FROM ruangan ORDER BY nama_ruangan ASC");
                                    while ($r = mysqli_fetch_assoc($query_ruang)) {
                                        echo "<option value='{$r['id']}'>{$r['nama_ruangan']}</option>";
                                    }
                                    ?>
                                </select>
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-slate-700 mb-1">Jam Mulai Ke</label>
                                <select name="jam_mulai_ke" class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-1 focus:ring-slate-400 text-xs" required>
                                    <option value="">-- Pilih Jam Mulai --</option>
                                    <?php
                                    $query_jk = mysqli_query($koneksi, "SELECT * FROM jam_kuliah ORDER BY jam_mulai ASC");
                                    // Store for second dropdown to avoid re-querying if possible, or just re-query/reset pointer
                                    $jam_kuliah_data = [];
                                    while ($jk = mysqli_fetch_assoc($query_jk)) {
                                        $jam_kuliah_data[] = $jk;
                                        $display = "Ke-{$jk['jam_ke']} (" . date('H:i', strtotime($jk['jam_mulai'])) . " - " . date('H:i', strtotime($jk['jam_selesai'])) . ")";
                                        echo "<option value='{$jk['jam_ke']}'>$display</option>";
                                    }
                                    ?>
                                </select>
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-slate-700 mb-1">Jam Selesai Ke</label>
                                <select name="jam_selesai_ke" class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-1 focus:ring-slate-400 text-xs" required>
                                    <option value="">-- Pilih Jam Selesai --</option>
                                    <?php
                                    foreach ($jam_kuliah_data as $jk) {
                                        $display = "Ke-{$jk['jam_ke']} (" . date('H:i', strtotime($jk['jam_mulai'])) . " - " . date('H:i', strtotime($jk['jam_selesai'])) . ")";
                                        echo "<option value='{$jk['jam_ke']}'>$display</option>";
                                    }
                                    ?>
                                </select>
                            </div>

                            <div>
                                <label class="block text-xs font-medium text-slate-700 mb-1">Dosen Pengampu</label>
                                <select name="dosen_pengampu_id" class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-1 focus:ring-slate-400 text-xs" required>
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
                                <label class="block text-xs font-medium text-slate-700 mb-1">Dosen Mitra</label>
                                <select name="dosen_mitra_id" class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-1 focus:ring-slate-400 text-xs" required>
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
                        <h3 class="text-xl font-bold mb-6">Tambah Dosen</h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label class="block text-xs font-medium text-slate-700 mb-1">Nama Dosen</label>
                                <input type="text" name="nama_dosen"
                                    class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-1 focus:ring-slate-400 text-xs">
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-slate-700 mb-1">No HP</label>
                                <input type="text" name="no_hp"
                                    class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-1 focus:ring-slate-400 text-xs">
                            </div>
                        </div>
                    <?php elseif ($tabel === 'tugas'): ?>
                        <h3 class="text-xl font-bold mb-6">Tambah Tugas</h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label class="block text-xs font-medium text-slate-700 mb-1">Mata Kuliah</label>
                                <select name="matkul_id" class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-1 focus:ring-slate-400 text-xs" required>
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
                                <label class="block text-xs font-medium text-slate-700 mb-1">Deadline Tanggal</label>
                                <input type="date" name="deadline_tanggal"
                                    class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-1 focus:ring-slate-400 text-xs">
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-slate-700 mb-1">Deadline Pukul</label>
                                <input type="time" name="deadline_pukul"
                                    class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-1 focus:ring-slate-400 text-xs">
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-slate-700 mb-1">Deskripsi</label>
                                <input type="text" name="deskripsi"
                                    class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-1 focus:ring-slate-400 text-xs">
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-slate-700 mb-1">File Tugas</label>
                                <input type="file" name="file_tugas" accept="file/*"
                                    class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-1 focus:ring-slate-400 text-xs">
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-slate-700 mb-1">Link Kirim</label>
                                <input type="text" name="link_kirim"
                                    class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-1 focus:ring-slate-400 text-xs">
                            </div>
                        </div>

                    <?php elseif ($tabel === 'users'): ?>
                        <h3 class="text-xl font-bold mb-6">Users</h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label class="block text-xs font-medium text-slate-700 mb-1">Kode Mata Kuliah</label>
                                <select name="id" class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-1 focus:ring-slate-400 text-xs" required>
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
                                <label class="block text-xs font-medium text-slate-700 mb-1">Password</label>
                                <input type="password" name="password"
                                    class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-1 focus:ring-slate-400 text-xs">
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-slate-700 mb-1">Role</label>
                                <select name="role" class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-1 focus:ring-slate-400 text-xs" required>
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
                                <label class="block text-xs font-medium text-slate-700 mb-1">Judul Kegiatan</label>
                                <input type="text" name="judul" required
                                    class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-1 focus:ring-slate-400 text-xs">
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-slate-700 mb-1">Tanggal Kegiatan</label>
                                <input type="date" name="tanggal_kegiatan" required
                                    class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-1 focus:ring-slate-400 text-xs">
                            </div>
                            <div class="md:col-span-2">
                                <label class="block text-xs font-medium text-slate-700 mb-1">Foto-foto (Bisa pilih banyak)</label>
                                <input type="file" name="fotos[]" multiple accept="image/*" required
                                    class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-1 focus:ring-slate-400 text-xs">
                            </div>
                        </div>
                    <?php elseif ($tabel === 'akademik'): ?>
                        <h3 class="text-xl font-bold mb-6">Berita & Informasi Akademik</h3>
                        <div class="grid grid-cols-1 gap-6">
                            <div>
                                <label class="block text-xs font-medium text-slate-700 mb-1">Judul Berita</label>
                                <input type="text" name="judul" required
                                    class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-1 focus:ring-slate-400 text-xs">
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-slate-700 mb-1">Deskripsi</label>
                                <textarea name="deskripsi" rows="3" required
                                    class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-1 focus:ring-slate-400 text-xs"></textarea>
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-slate-700 mb-1">Link (Opsional)</label>
                                <input type="text" name="link"
                                    class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-1 focus:ring-slate-400 text-xs">
                            </div>
                        </div>
                    <?php elseif ($tabel === 'kalender_akademik'): ?>
                        <h3 class="text-xl font-bold mb-6">Tambah Agenda Kalender Akademik</h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label class="block text-xs font-medium text-slate-700 mb-1">Periode</label>
                                <select name="periode_id" required
                                    class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-1 focus:ring-slate-400 text-xs">
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
                                <label class="block text-xs font-medium text-slate-700 mb-1">Nama Kegiatan</label>
                                <textarea name="kegiatan" rows="2" required
                                    class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-1 focus:ring-slate-400 text-xs"></textarea>
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-slate-700 mb-1">Tanggal Mulai</label>
                                <input type="date" name="tanggal_mulai" required
                                    class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-1 focus:ring-slate-400 text-xs">
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-slate-700 mb-1">Tanggal Selesai</label>
                                <input type="date" name="tanggal_selesai" required
                                    class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-1 focus:ring-slate-400 text-xs">
                            </div>
                        </div>
                    <?php elseif ($tabel === 'ruangan'): ?>
                        <h3 class="text-xl font-bold mb-6">Tambah Ruangan</h3>
                        <div class="grid grid-cols-1 gap-6">
                            <div>
                                <label class="block text-xs font-medium text-slate-700 mb-1">Nama Ruangan</label>
                                <input type="text" name="nama_ruangan" required
                                    class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-1 focus:ring-slate-400 text-xs">
                            </div>
                        </div>
                    <?php elseif ($tabel === 'jam_kuliah'): ?>
                        <h3 class="text-xl font-bold mb-6">Tambah Jam Kuliah</h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label class="block text-xs font-medium text-slate-700 mb-1">Jam Ke</label>
                                <input type="number" name="jam_ke" required min="1"
                                    class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-1 focus:ring-slate-400 text-xs">
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-slate-700 mb-1">Jam Mulai</label>
                                <input type="time" name="jam_mulai" required
                                    class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-1 focus:ring-slate-400 text-xs">
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-slate-700 mb-1">Jam Selesai</label>
                                <input type="time" name="jam_selesai" required
                                    class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-1 focus:ring-slate-400 text-xs">
                            </div>
                        </div>
                    <?php elseif ($tabel === 'kuis_harian_soal'): ?>
                        <h3 class="text-xl font-bold mb-6">Tambah Soal Kuis Harian</h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label class="block text-xs font-medium text-slate-700 mb-1">Tanggal</label>
                                <input type="date" name="tanggal" required
                                    class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-1 focus:ring-slate-400 text-xs">
                            </div>
                            <div class="md:col-span-2">
                                <label class="block text-xs font-medium text-slate-700 mb-1">Pertanyaan</label>
                                <textarea name="pertanyaan" rows="3" required
                                    class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-1 focus:ring-slate-400 text-xs"></textarea>
                            </div>
                            <div class="md:col-span-2">
                                <label class="block text-xs font-medium text-slate-700 mb-1">Jawaban</label>
                                <input type="text" name="jawaban" required
                                    class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-1 focus:ring-slate-400 text-xs">
                            </div>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Form Actions -->
                <div class="flex justify-end space-x-4">
                    <button type="button"
                        class="px-6 py-2 border border-slate-300 rounded-lg hover:bg-slate-50 text-xs">Cancel</button>
                    <button type="submit" name="tambah"
                        class="px-6 py-2 bg-indigo-800 text-white rounded-lg hover:bg-indigo-700 text-xs">Submit</button>
                </div>
            </form>
        </div>
        <?php include '../includes/footer_db.php'; ?>
    </main>
    <?php include '../includes/script_db.php'; ?>
</body>

</html>