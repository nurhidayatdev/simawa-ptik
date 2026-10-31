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

if ($tabel === 'jadwal_matkul' && !in_array($role, ['Administrator Utama', 'Ketua Tingkat'])) {
    $_SESSION['error'] = "Akses ditolak! Pengelolaan jadwal kuliah hanya wewenang Ketua Tingkat.";
    header("Location: ../database/db_jadwal");
    exit;
}

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
            $target_kelas_id = getKelasScope() ?? 1;
            mysqli_query($koneksi, "INSERT INTO mahasiswa (nama_lengkap, nama_panggilan, email, nim, bio, jenis_kelamin, instagram, kelas_id)
                VALUES ('$nama_lengkap','$nama_panggilan','$email','$nim','$bio','$jenis_kelamin','$instagram', '$target_kelas_id')");
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
            if (!in_array($role, ['Administrator Utama', 'Ketua Tingkat'])) {
                $_SESSION['error'] = "Akses ditolak! Pengelolaan jadwal kuliah hanya wewenang Ketua Tingkat.";
                header("Location: ../database/db_jadwal");
                exit;
            }
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
            $target_kelas_id = getKelasScope() ?? 1;

            mysqli_query($koneksi, "INSERT INTO jadwal_matkul (matkul_id, hari, jam_mulai_ke, jam_selesai_ke, ruangan_id, dosen_pengampu_id, dosen_mitra_id, periode_id, kelas_id)
                VALUES ('$matkul_id', '$hari', '$jam_mulai_ke', '$jam_selesai_ke', '$ruangan_id', '$dosen_pengampu_id', '$dosen_mitra_id', '$periode_id', '$target_kelas_id')");
            $redirect = "../database/db_jadwal";

            break;

        case 'users':
            $email = mysqli_real_escape_string($koneksi, trim($_POST['email'] ?? ''));
            $nama_lengkap = mysqli_real_escape_string($koneksi, trim($_POST['nama_lengkap'] ?? ''));
            $password = $_POST['password'] ?? '';
            $role = mysqli_real_escape_string($koneksi, $_POST['role'] ?? 'Mahasiswa');
            $kelas_id = !empty($_POST['kelas_id']) ? (int)$_POST['kelas_id'] : null;
            $linked_nim = !empty($_POST['linked_student_nim']) ? mysqli_real_escape_string($koneksi, trim($_POST['linked_student_nim'])) : null;

            if (empty($email) || empty($password) || empty($nama_lengkap)) {
                $error_message = "Nama Lengkap, Email, dan Password wajib diisi.";
                include '../includes/alert_actions.php';
                exit;
            }

            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $error_message = "Format email tidak valid.";
                include '../includes/alert_actions.php';
                exit;
            }

            // Cek keunikan email
            $stmt_chk = mysqli_prepare($koneksi, "SELECT id FROM users WHERE email = ? LIMIT 1");
            mysqli_stmt_bind_param($stmt_chk, "s", $email);
            mysqli_stmt_execute($stmt_chk);
            if (mysqli_num_rows(mysqli_stmt_get_result($stmt_chk)) > 0) {
                $error_message = "Email '$email' sudah digunakan oleh akun lain.";
                include '../includes/alert_actions.php';
                exit;
            }

            // Buat akun baru di users
            $stmt_ins_u = mysqli_prepare($koneksi, "INSERT INTO users (email, password, role, nama_lengkap, linked_student_nim, kelas_id) VALUES (?, ?, ?, ?, ?, ?)");
            mysqli_stmt_bind_param($stmt_ins_u, "sssssi", $email, $password, $role, $nama_lengkap, $linked_nim, $kelas_id);
            if (!mysqli_stmt_execute($stmt_ins_u)) {
                $error_message = "Gagal membuat akun: " . mysqli_error($koneksi);
                include '../includes/alert_actions.php';
                exit;
            }
            $new_user_id = mysqli_insert_id($koneksi);

            // Jika role Ketua Tingkat dan kelas_id diisi, update penanggung jawab kelas
            if ($role === 'Ketua Tingkat' && !empty($kelas_id)) {
                mysqli_query($koneksi, "UPDATE kelas SET ketua_tingkat_user_id = $new_user_id WHERE id = $kelas_id");
            }

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

            $target_kelas_id = getKelasScope() ?? 1;
            mysqli_query($koneksi, "INSERT INTO tugas (matkul_id, deadline_tanggal, deadline_pukul, deskripsi, file_tugas, link_kirim, periode_id, kelas_id)
                VALUES ('$matkul_id', '$deadline_tanggal', '$deadline_pukul', '$deskripsi', '$file_tugas', '$link_kirim', '$periode_id', '$target_kelas_id')");

            // =====================
            // WHATSAPP NOTIFICATION
            // =====================
            include_once '../functions/kirim_tugas.php';
            notifyNewTask(null, null, $nama_matkul, $deadline_tanggal, $deadline_pukul, $deskripsi, $link_kirim);

            $redirect = "../database/db_tugas";
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
    }
    include '../includes/alert_actions.php';

    exit;
}
?>
<!DOCTYPE html>
<html lang="en">

<head>

    <title>Tambah - SIMAWA PTIK 2024</title>
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

<body class="bg-slate-100 dark:bg-slate-950 transition-colors">
    <?php include '../includes/menu_db.php'; ?>

    <main class="ml-0 lg:ml-48 pt-20 p-6 flex flex-col min-h-screen">
        <div class="bg-white dark:bg-slate-900 rounded-lg border border-slate-200 dark:border-slate-800 p-6 flex-grow w-full transition-all">


            <form class="space-y-6" method="POST" enctype="multipart/form-data">
                <div class="border-b border-slate-200 dark:border-slate-800 pb-6">
                    <?php if ($tabel === 'mahasiswa'): ?>
                        <h3 class="text-xl font-bold mb-6 text-slate-800 dark:text-slate-100">Mahasiswa</h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label class="block text-xs font-medium text-slate-700 dark:text-slate-300 mb-1">Nama Lengkap</label>
                                <input type="text" name="nama_lengkap"
                                    class="w-full px-4 py-2 border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-slate-200 rounded-lg focus:outline-none focus:ring-1 focus:ring-indigo-500 dark:focus:ring-indigo-600 text-xs transition-colors">
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-slate-700 dark:text-slate-300 mb-1">Nama Panggilan</label>
                                <input type="text" name="nama_panggilan"
                                    class="w-full px-4 py-2 border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-slate-200 rounded-lg focus:outline-none focus:ring-1 focus:ring-indigo-500 dark:focus:ring-indigo-600 text-xs transition-colors">
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-slate-700 dark:text-slate-300 mb-1">Email</label>
                                <input type="email" name="email"
                                    class="w-full px-4 py-2 border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-slate-200 rounded-lg focus:outline-none focus:ring-1 focus:ring-indigo-500 dark:focus:ring-indigo-600 text-xs transition-colors">
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-slate-700 dark:text-slate-300 mb-1">NIM</label>
                                <input type="number" name="nim"
                                    class="w-full px-4 py-2 border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-slate-200 rounded-lg focus:outline-none focus:ring-1 focus:ring-indigo-500 dark:focus:ring-indigo-600 text-xs transition-colors">
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-slate-700 dark:text-slate-300 mb-1">Jenis Kelamin</label>
                                <select name="jenis_kelamin"
                                    class="w-full px-4 py-2 border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-slate-200 rounded-lg focus:outline-none focus:ring-1 focus:ring-indigo-500 dark:focus:ring-indigo-600 text-xs transition-colors">
                                    <option value="">-- Pilih Jenis Kelamin --</option>
                                    <option value="Laki-laki">Laki-laki</option>
                                    <option value="Perempuan">Perempuan</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-slate-700 dark:text-slate-300 mb-1">Instagram</label>
                                <input type="text" name="instagram" placeholder="@username"
                                    class="w-full px-4 py-2 border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-slate-200 rounded-lg focus:outline-none focus:ring-1 focus:ring-indigo-500 dark:focus:ring-indigo-600 text-xs transition-colors">
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-slate-700 dark:text-slate-300 mb-1">Biodata</label>
                                <input type="text" name="bio"
                                    class="w-full px-4 py-2 border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-slate-200 rounded-lg focus:outline-none focus:ring-1 focus:ring-indigo-500 dark:focus:ring-indigo-600 text-xs transition-colors">
                            </div>
                            
                        </div>

                    <?php elseif ($tabel === 'periode'): ?>
                        <h3 class="text-xl font-bold mb-6 text-slate-800 dark:text-slate-100">Tambah Periode</h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label class="block text-xs font-medium text-slate-700 dark:text-slate-300 mb-1">Tahun Periode</label>
                                <input type="text" name="tahun_periode"
                                    class="w-full px-4 py-2 border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-slate-200 rounded-lg focus:outline-none focus:ring-1 focus:ring-indigo-500 dark:focus:ring-indigo-600 text-xs transition-colors">
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-slate-700 dark:text-slate-300 mb-1">Semester Nama</label>
                                <select name="semester_nama" class="w-full px-3 py-2 border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-slate-200 rounded-lg focus:outline-none focus:ring-1 focus:ring-indigo-500 dark:focus:ring-indigo-600 text-xs transition-colors" required>
                                    <option value="">-- Pilih Semester Nama --</option>
                                    <option value="Ganjil">Ganjil</option>
                                    <option value="Genap">Genap</option>
                                    <option value="Antara">Antara</option>
                                </select>
                            </div>

                            <div>
                                <label class="block text-xs font-medium text-slate-700 dark:text-slate-300 mb-1">Semester Ke</label>
                                <select name="semester_ke" class="w-full px-3 py-2 border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-slate-200 rounded-lg focus:outline-none focus:ring-1 focus:ring-indigo-500 dark:focus:ring-indigo-600 text-xs transition-colors" required">
                                    <option value="">-- Semester Ke --</option>
                                    <?php for ($i = 1; $i <= 8; $i++): ?>
                                        <option value="<?= $i ?>"><?= $i ?>
                                        </option>
                                    <?php endfor; ?>
                                </select>
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-slate-700 dark:text-slate-300 mb-1">Status</label>
                                <select name="status" class="w-full px-3 py-2 border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-slate-200 rounded-lg focus:outline-none focus:ring-1 focus:ring-indigo-500 dark:focus:ring-indigo-600 text-xs transition-colors" required>
                                    <option value="">-- Pilih Status --</option>
                                    <option value="aktif">aktif</option>
                                    <option value="non-aktif">non-aktif</option>
                                </select>
                            </div>
                        </div>

                    <?php elseif ($tabel === 'mata_kuliah'): ?>
                        <h3 class="text-xl font-bold mb-6 text-slate-800 dark:text-slate-100">Mata Kuliah</h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label class="block text-xs font-medium text-slate-700 dark:text-slate-300 mb-1">Kode Mata Kuliah</label>
                                <input type="text" name="kode_matkul"
                                    class="w-full px-4 py-2 border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-slate-200 rounded-lg focus:outline-none focus:ring-1 focus:ring-indigo-500 dark:focus:ring-indigo-600 text-xs transition-colors">
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-slate-700 dark:text-slate-300 mb-1">Nama Mata Kuliah</label>
                                <input type="text" name="nama_matkul"
                                    class="w-full px-4 py-2 border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-slate-200 rounded-lg focus:outline-none focus:ring-1 focus:ring-indigo-500 dark:focus:ring-indigo-600 text-xs transition-colors">
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-slate-700 dark:text-slate-300 mb-1">Semester</label>
                                <select name="semester" class="w-full px-3 py-2 border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-slate-200 rounded-lg focus:outline-none focus:ring-1 focus:ring-indigo-500 dark:focus:ring-indigo-600 text-xs transition-colors" required">
                                    <option value="">-- Semester --</option>
                                    <?php for ($i = 1; $i <= 8; $i++): ?>
                                        <option value="<?= $i ?>"><?= $i ?>
                                        </option>
                                    <?php endfor; ?>
                                </select>
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-slate-700 dark:text-slate-300 mb-1">SKS</label>
                                <select name="sks" class="w-full px-3 py-2 border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-slate-200 rounded-lg focus:outline-none focus:ring-1 focus:ring-indigo-500 dark:focus:ring-indigo-600 text-xs transition-colors" required>
                                    <option value="">-- Pilih SKS --</option>
                                    <option value="2">2</option>
                                    <option value="3">3</option>
                                    <option value="4">4</option>
                                    <option value="6">6</option>
                                </select>
                            </div>
                        </div>

                    <?php elseif ($tabel === 'jadwal_matkul'): ?>
                        <h3 class="text-xl font-bold mb-6 text-slate-800 dark:text-slate-100">Jadwal Mata Kuliah</h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div class="col-span-2">
                                <label class="block text-xs font-medium text-slate-700 dark:text-slate-300 mb-1">Mata Kuliah</label>
                                <select name="matkul_id" class="w-full px-4 py-2 border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-slate-200 rounded-lg focus:outline-none focus:ring-1 focus:ring-indigo-500 dark:focus:ring-indigo-600 text-xs transition-colors" required>
                                    <option value="">-- Pilih Mata Kuliah --</option>
                                    <?php
                                    // Get active periode_id
                                    $q_per_add = mysqli_query($koneksi, "SELECT id FROM periode WHERE status = 'aktif' LIMIT 1");
                                    $per_add = mysqli_fetch_assoc($q_per_add);
                                    $pid_add = (int)($per_add['id'] ?? 0);

                                    $target_kelas_scope = getKelasScope() ?? 1;
                                    $query_mk = mysqli_query($koneksi, "SELECT DISTINCT
                                        mk.id AS matkul_id,
                                        mk.nama_matkul
                                        FROM mata_kuliah mk
                                        WHERE mk.id NOT IN (SELECT matkul_id FROM jadwal_matkul WHERE periode_id = '$pid_add' AND kelas_id = '$target_kelas_scope')
                                        ORDER BY mk.nama_matkul ASC");
                                    while ($mk = mysqli_fetch_assoc($query_mk)) {
                                        echo "<option value='{$mk['matkul_id']}'>{$mk['nama_matkul']}</option>";
                                    }
                                    ?>
                                </select>
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-slate-700 dark:text-slate-300 mb-1">Hari</label>
                                <select name="hari" class="w-full px-4 py-2 border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-slate-200 rounded-lg focus:outline-none focus:ring-1 focus:ring-indigo-500 dark:focus:ring-indigo-600 text-xs transition-colors" required>
                                    <option value="">-- Pilih Hari --</option>
                                    <option value="Senin">Senin</option>
                                    <option value="Selasa">Selasa</option>
                                    <option value="Rabu">Rabu</option>
                                    <option value="Kamis">Kamis</option>
                                    <option value="Jumat">Jumat</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-slate-700 dark:text-slate-300 mb-1">Ruangan</label>
                                <select name="ruangan_id" class="w-full px-4 py-2 border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-slate-200 rounded-lg focus:outline-none focus:ring-1 focus:ring-indigo-500 dark:focus:ring-indigo-600 text-xs transition-colors" required>
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
                                <label class="block text-xs font-medium text-slate-700 dark:text-slate-300 mb-1">Jam Mulai Ke</label>
                                <select name="jam_mulai_ke" class="w-full px-4 py-2 border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-slate-200 rounded-lg focus:outline-none focus:ring-1 focus:ring-indigo-500 dark:focus:ring-indigo-600 text-xs transition-colors" required>
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
                                <label class="block text-xs font-medium text-slate-700 dark:text-slate-300 mb-1">Jam Selesai Ke</label>
                                <select name="jam_selesai_ke" class="w-full px-4 py-2 border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-slate-200 rounded-lg focus:outline-none focus:ring-1 focus:ring-indigo-500 dark:focus:ring-indigo-600 text-xs transition-colors" required>
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
                                <label class="block text-xs font-medium text-slate-700 dark:text-slate-300 mb-1">Dosen Pengampu</label>
                                <select name="dosen_pengampu_id" class="w-full px-4 py-2 border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-slate-200 rounded-lg focus:outline-none focus:ring-1 focus:ring-indigo-500 dark:focus:ring-indigo-600 text-xs transition-colors" required>
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
                                <label class="block text-xs font-medium text-slate-700 dark:text-slate-300 mb-1">Dosen Mitra</label>
                                <select name="dosen_mitra_id" class="w-full px-4 py-2 border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-slate-200 rounded-lg focus:outline-none focus:ring-1 focus:ring-indigo-500 dark:focus:ring-indigo-600 text-xs transition-colors" required>
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
                        <h3 class="text-xl font-bold mb-6 text-slate-800 dark:text-slate-100">Tambah Dosen</h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label class="block text-xs font-medium text-slate-700 dark:text-slate-300 mb-1">Nama Dosen</label>
                                <input type="text" name="nama_dosen"
                                    class="w-full px-4 py-2 border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-slate-200 rounded-lg focus:outline-none focus:ring-1 focus:ring-indigo-500 dark:focus:ring-indigo-600 text-xs transition-colors">
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-slate-700 dark:text-slate-300 mb-1">No HP</label>
                                <input type="text" name="no_hp"
                                    class="w-full px-4 py-2 border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-slate-200 rounded-lg focus:outline-none focus:ring-1 focus:ring-indigo-500 dark:focus:ring-indigo-600 text-xs transition-colors">
                            </div>
                        </div>
                    <?php elseif ($tabel === 'tugas'): ?>
                        <h3 class="text-xl font-bold mb-6 text-slate-800 dark:text-slate-100">Tambah Tugas</h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div class="md:col-span-2">
                                <label class="block text-xs font-medium text-slate-700 dark:text-slate-300 mb-1">Mata Kuliah</label>
                                <select name="matkul_id" class="w-full px-4 py-2 border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-slate-200 rounded-lg focus:outline-none focus:ring-1 focus:ring-indigo-500 dark:focus:ring-indigo-600 text-xs transition-colors" required>
                                    <option value="">-- Pilih Mata Kuliah --</option>
                                    <?php
                                    $q_per_tugas = mysqli_query($koneksi, "SELECT id FROM periode WHERE status = 'aktif' LIMIT 1");
                                    $per_tugas = mysqli_fetch_assoc($q_per_tugas);
                                    $pid_tugas = (int)($per_tugas['id'] ?? 0);

                                    $query_mk = mysqli_query($koneksi, "SELECT DISTINCT
                                        mk.id AS matkul_id,
                                        mk.nama_matkul
                                        FROM krs k
                                        JOIN mata_kuliah mk ON k.matkul_id = mk.id
                                        WHERE k.periode_id = '$pid_tugas'
                                        ORDER BY mk.nama_matkul ASC");
                                    while ($mk = mysqli_fetch_assoc($query_mk)) {
                                        echo "<option value='{$mk['matkul_id']}'>{$mk['nama_matkul']}</option>";
                                    }
                                    ?>
                                </select>
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-slate-700 dark:text-slate-300 mb-1">Deadline Tanggal</label>
                                <input type="date" name="deadline_tanggal"
                                    class="w-full px-4 py-2 border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-slate-200 rounded-lg focus:outline-none focus:ring-1 focus:ring-indigo-500 dark:focus:ring-indigo-600 text-xs transition-colors">
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-slate-700 dark:text-slate-300 mb-1">Deadline Pukul</label>
                                <input type="time" name="deadline_pukul"
                                    class="w-full px-4 py-2 border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-slate-200 rounded-lg focus:outline-none focus:ring-1 focus:ring-indigo-500 dark:focus:ring-indigo-600 text-xs transition-colors">
                            </div>
                            <div class="md:col-span-2">
                                <label class="block text-xs font-medium text-slate-700 dark:text-slate-300 mb-1">Deskripsi</label>
                                <textarea name="deskripsi" rows="5"
                                    class="w-full px-4 py-2 border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-slate-200 rounded-lg focus:outline-none focus:ring-1 focus:ring-indigo-500 dark:focus:ring-indigo-600 text-xs transition-colors"></textarea>
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-slate-700 dark:text-slate-300 mb-1">File Tugas</label>
                                <input type="file" name="file_tugas" accept="file/*"
                                    class="w-full px-4 py-2 border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-slate-200 rounded-lg focus:outline-none focus:ring-1 focus:ring-indigo-500 dark:focus:ring-indigo-600 text-xs transition-colors">
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-slate-700 dark:text-slate-300 mb-1">Link Kirim</label>
                                <input type="text" name="link_kirim"
                                    class="w-full px-4 py-2 border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-slate-200 rounded-lg focus:outline-none focus:ring-1 focus:ring-indigo-500 dark:focus:ring-indigo-600 text-xs transition-colors">
                            </div>
                        </div>

                    <?php elseif ($tabel === 'users'): ?>
                        <div class="mb-6">
                            <h3 class="text-xl font-bold text-slate-800 dark:text-slate-100">Tambah Akun Ketua Tingkat</h3>
                            <p class="text-xs text-slate-400 mt-1">Buat akun login kepemimpinan kelas khusus Ketua Tingkat.</p>
                        </div>
                        <input type="hidden" name="role" value="Ketua Tingkat">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label class="block text-xs font-medium text-slate-700 dark:text-slate-300 mb-1">Nama Lengkap Ketua Tingkat *</label>
                                <input type="text" name="nama_lengkap" required placeholder="Contoh: Ahmad Fauzan"
                                    class="w-full px-4 py-2 border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-slate-200 rounded-lg focus:outline-none focus:ring-1 focus:ring-indigo-500 dark:focus:ring-indigo-600 text-xs transition-colors">
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-slate-700 dark:text-slate-300 mb-1">Email Login *</label>
                                <input type="email" name="email" required placeholder="ketua@simawa.id"
                                    class="w-full px-4 py-2 border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-slate-200 rounded-lg focus:outline-none focus:ring-1 focus:ring-indigo-500 dark:focus:ring-indigo-600 text-xs transition-colors">
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-slate-700 dark:text-slate-300 mb-1">Password *</label>
                                <input type="password" name="password" required placeholder="Minimal 8 karakter"
                                    class="w-full px-4 py-2 border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-slate-200 rounded-lg focus:outline-none focus:ring-1 focus:ring-indigo-500 dark:focus:ring-indigo-600 text-xs transition-colors">
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-slate-700 dark:text-slate-300 mb-1">Kelas yang Dipimpin *</label>
                                <select name="kelas_id" required class="w-full px-4 py-2 border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-slate-200 rounded-lg focus:outline-none focus:ring-1 focus:ring-indigo-500 dark:focus:ring-indigo-600 text-xs transition-colors cursor-pointer">
                                    <option value="">-- Pilih Kelas --</option>
                                    <?php
                                    $q_kelas_all = mysqli_query($koneksi, "SELECT id, nama_kelas FROM kelas ORDER BY id ASC");
                                    while ($kls = mysqli_fetch_assoc($q_kelas_all)) {
                                        echo "<option value='{$kls['id']}'>" . htmlspecialchars($kls['nama_kelas']) . "</option>";
                                    }
                                    ?>
                                </select>
                            </div>
                            <div class="md:col-span-2">
                                <label class="block text-xs font-medium text-slate-700 dark:text-slate-300 mb-1">Catatan NIM Mahasiswa (Opsional)</label>
                                <input type="text" name="linked_student_nim" placeholder="Contoh: 240209501001"
                                    class="w-full px-4 py-2 border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-slate-200 rounded-lg focus:outline-none focus:ring-1 focus:ring-indigo-500 dark:focus:ring-indigo-600 text-xs transition-colors">
                                <p class="mt-1 text-[10px] text-slate-400">Informasi identitas mahasiswa di dunia nyata tanpa menggabungkan permission/data akademik.</p>
                            </div>
                        </div>

                    <?php elseif ($tabel === 'akademik'): ?>
                        <h3 class="text-xl font-bold mb-6 text-slate-800 dark:text-slate-100">Berita & Informasi Akademik</h3>
                        <div class="grid grid-cols-1 gap-6">
                            <div>
                                <label class="block text-xs font-medium text-slate-700 dark:text-slate-300 mb-1">Judul Berita</label>
                                <input type="text" name="judul" required
                                    class="w-full px-4 py-2 border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-slate-200 rounded-lg focus:outline-none focus:ring-1 focus:ring-indigo-500 dark:focus:ring-indigo-600 text-xs transition-colors">
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-slate-700 dark:text-slate-300 mb-1">Deskripsi</label>
                                <textarea name="deskripsi" rows="3" required
                                    class="w-full px-4 py-2 border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-slate-200 rounded-lg focus:outline-none focus:ring-1 focus:ring-indigo-500 dark:focus:ring-indigo-600 text-xs transition-colors"></textarea>
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-slate-700 dark:text-slate-300 mb-1">Link (Opsional)</label>
                                <input type="text" name="link"
                                    class="w-full px-4 py-2 border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-slate-200 rounded-lg focus:outline-none focus:ring-1 focus:ring-indigo-500 dark:focus:ring-indigo-600 text-xs transition-colors">
                            </div>
                        </div>
                    <?php elseif ($tabel === 'kalender_akademik'): ?>
                        <h3 class="text-xl font-bold mb-6 text-slate-800 dark:text-slate-100">Tambah Agenda Kalender Akademik</h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label class="block text-xs font-medium text-slate-700 dark:text-slate-300 mb-1">Periode</label>
                                <select name="periode_id" required
                                    class="w-full px-4 py-2 border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-slate-200 rounded-lg focus:outline-none focus:ring-1 focus:ring-indigo-500 dark:focus:ring-indigo-600 text-xs transition-colors">
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
                                <label class="block text-xs font-medium text-slate-700 dark:text-slate-300 mb-1">Nama Kegiatan</label>
                                <textarea name="kegiatan" rows="2" required
                                    class="w-full px-4 py-2 border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-slate-200 rounded-lg focus:outline-none focus:ring-1 focus:ring-indigo-500 dark:focus:ring-indigo-600 text-xs transition-colors"></textarea>
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-slate-700 dark:text-slate-300 mb-1">Tanggal Mulai</label>
                                <input type="date" name="tanggal_mulai" required
                                    class="w-full px-4 py-2 border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-slate-200 rounded-lg focus:outline-none focus:ring-1 focus:ring-indigo-500 dark:focus:ring-indigo-600 text-xs transition-colors">
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-slate-700 dark:text-slate-300 mb-1">Tanggal Selesai</label>
                                <input type="date" name="tanggal_selesai" required
                                    class="w-full px-4 py-2 border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-slate-200 rounded-lg focus:outline-none focus:ring-1 focus:ring-indigo-500 dark:focus:ring-indigo-600 text-xs transition-colors">
                            </div>
                        </div>
                    <?php elseif ($tabel === 'ruangan'): ?>
                        <h3 class="text-xl font-bold mb-6 text-slate-800 dark:text-slate-100">Tambah Ruangan</h3>
                        <div class="grid grid-cols-1 gap-6">
                            <div>
                                <label class="block text-xs font-medium text-slate-700 dark:text-slate-300 mb-1">Nama Ruangan</label>
                                <input type="text" name="nama_ruangan" required
                                    class="w-full px-4 py-2 border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-slate-200 rounded-lg focus:outline-none focus:ring-1 focus:ring-indigo-500 dark:focus:ring-indigo-600 text-xs transition-colors">
                            </div>
                        </div>
                    <?php elseif ($tabel === 'jam_kuliah'): ?>
                        <h3 class="text-xl font-bold mb-6 text-slate-800 dark:text-slate-100">Tambah Jam Kuliah</h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label class="block text-xs font-medium text-slate-700 dark:text-slate-300 mb-1">Jam Ke</label>
                                <input type="number" name="jam_ke" required min="1"
                                    class="w-full px-4 py-2 border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-slate-200 rounded-lg focus:outline-none focus:ring-1 focus:ring-indigo-500 dark:focus:ring-indigo-600 text-xs transition-colors">
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-slate-700 dark:text-slate-300 mb-1">Jam Mulai</label>
                                <input type="time" name="jam_mulai" required
                                    class="w-full px-4 py-2 border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-slate-200 rounded-lg focus:outline-none focus:ring-1 focus:ring-indigo-500 dark:focus:ring-indigo-600 text-xs transition-colors">
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-slate-700 dark:text-slate-300 mb-1">Jam Selesai</label>
                                <input type="time" name="jam_selesai" required
                                    class="w-full px-4 py-2 border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-slate-200 rounded-lg focus:outline-none focus:ring-1 focus:ring-indigo-500 dark:focus:ring-indigo-600 text-xs transition-colors">
                            </div>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Form Actions -->
                <div class="flex justify-end">
                    <button type="submit" name="tambah"
                        class="px-6 py-2 bg-indigo-800 dark:bg-indigo-700 text-white rounded-lg hover:bg-indigo-700 dark:hover:bg-indigo-600 text-xs transition-all shadow-lg shadow-indigo-100 dark:shadow-indigo-950/20">Submit</button>
                </div>
            </form>
        </div>
        <?php include '../includes/footer_db.php'; ?>
    </main>
    <?php include '../includes/script_db.php'; ?>
</body>

</html>