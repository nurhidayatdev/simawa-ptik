<?php
session_start();
include '../koneksi.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../../index");
    exit;
}

$nama_lengkap = $_SESSION['nama_lengkap'];
$nama_panggilan = $_SESSION['nama_panggilan'];
$foto = $_SESSION['foto'] ?? '';
$role = $_SESSION['role'];
$tabel = $_GET['tabel'] ?? '';
$id = $_GET['id'] ?? '';

if ($tabel === 'jadwal_matkul' && $role !== 'Ketua Tingkat') {
    $_SESSION['error'] = "Akses ditolak! Pengelolaan jadwal kuliah hanya wewenang Ketua Tingkat.";
    header("Location: ../database/db_jadwal");
    exit;
}

if ($tabel === 'tugas' && !in_array($role, ['Sekretaris', 'Ketua Tingkat'])) {
    $_SESSION['error'] = "Akses ditolak! Pengelolaan tugas hanya wewenang Sekretaris dan Ketua Tingkat.";
    header("Location: ../dashboard.php");
    exit;
}

if ($tabel === 'mahasiswa' && !in_array($role, ['Administrator Utama', 'Ketua Tingkat'])) {
    $_SESSION['error'] = "Akses ditolak! Pengelolaan data mahasiswa hanya wewenang Administrator Utama dan Ketua Tingkat.";
    header("Location: ../database/db_mahasiswa");
    exit;
}

// Handle jam_kuliah yang menggunakan jam_ke sebagai primary key
if ($tabel === 'jam_kuliah') {
    $jam_ke = $_GET['jam_ke'] ?? '';
    $query = mysqli_query($koneksi, "SELECT * FROM jam_kuliah WHERE jam_ke='$jam_ke'");
} elseif ($tabel === 'users') {
    $query = mysqli_query($koneksi, "
        SELECT u.*, COALESCE(NULLIF(u.nama_lengkap, ''), m.nama_lengkap, u.email) AS display_nama, k.nama_kelas 
        FROM users u 
        LEFT JOIN mahasiswa m ON m.user_id = u.id 
        LEFT JOIN kelas k ON u.kelas_id = k.id
        WHERE u.id='$id'
    ");
} else {
    $query = mysqli_query($koneksi, "SELECT * FROM $tabel WHERE id='$id'");
}

$data = mysqli_fetch_assoc($query);
if (!$data) {
    die("Data tidak ditemukan!");
}
if (!empty($data['kelas_id'])) {
    requireKelasAccess($data['kelas_id']);
}

if (isset($_POST['update'])) {

    $redirect = "";

    switch ($tabel) {

        case 'mahasiswa':
            $nama_lengkap = mysqli_real_escape_string($koneksi, trim($_POST['nama_lengkap'] ?? ''));
            $nama_panggilan = mysqli_real_escape_string($koneksi, trim($_POST['nama_panggilan'] ?? ''));
            $nim = mysqli_real_escape_string($koneksi, trim($_POST['nim'] ?? ''));
            $bio = mysqli_real_escape_string($koneksi, trim($_POST['bio'] ?? ''));
            $jenis_kelamin = mysqli_real_escape_string($koneksi, trim($_POST['jenis_kelamin'] ?? ''));
            $instagram = mysqli_real_escape_string($koneksi, trim($_POST['instagram'] ?? ''));

            mysqli_query($koneksi, "UPDATE mahasiswa SET 
                    nama_lengkap='$nama_lengkap',
                    nama_panggilan='$nama_panggilan',
                    nim='$nim',
                    bio='$bio',
                    jenis_kelamin='$jenis_kelamin',
                    instagram='$instagram'
                    WHERE id='$id'");

            $chk_u = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT user_id FROM mahasiswa WHERE id='$id' LIMIT 1"));
            if (!empty($chk_u['user_id'])) {
                $uid = (int)$chk_u['user_id'];
                mysqli_query($koneksi, "UPDATE users SET nama_lengkap='$nama_lengkap', linked_student_nim='$nim' WHERE id='$uid'");
            }
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
            $email = mysqli_real_escape_string($koneksi, trim($_POST['email'] ?? ''));
            $nama_lengkap = mysqli_real_escape_string($koneksi, trim($_POST['nama_lengkap'] ?? ''));
            $role = mysqli_real_escape_string($koneksi, $_POST['role'] ?? 'Mahasiswa');
            $kelas_id = !empty($_POST['kelas_id']) ? (int)$_POST['kelas_id'] : null;
            $linked_nim = !empty($_POST['linked_student_nim']) ? mysqli_real_escape_string($koneksi, trim($_POST['linked_student_nim'])) : null;
            $password = $_POST['password'] ?? '';

            if (empty($email) || empty($nama_lengkap)) {
                $error_message = "Nama Lengkap dan Email tidak boleh kosong.";
                include '../includes/alert_actions.php';
                exit;
            }

            if ($password === '**********' || empty($password)) {
                $stmt_up_u = mysqli_prepare($koneksi, "UPDATE users SET nama_lengkap = ?, email = ?, role = ?, kelas_id = ?, linked_student_nim = ? WHERE id = ?");
                mysqli_stmt_bind_param($stmt_up_u, "sssisi", $nama_lengkap, $email, $role, $kelas_id, $linked_nim, $id);
                mysqli_stmt_execute($stmt_up_u);
            } else {
                $stmt_up_u = mysqli_prepare($koneksi, "UPDATE users SET nama_lengkap = ?, email = ?, password = ?, role = ?, kelas_id = ?, linked_student_nim = ? WHERE id = ?");
                mysqli_stmt_bind_param($stmt_up_u, "ssssisi", $nama_lengkap, $email, $password, $role, $kelas_id, $linked_nim, $id);
                mysqli_stmt_execute($stmt_up_u);
            }

            // Jika Ketua Tingkat dan kelas_id diisi, update kelas jika belum ada ketua tingkat
            if ($role === 'Ketua Tingkat' && !empty($kelas_id)) {
                mysqli_query($koneksi, "UPDATE kelas SET ketua_tingkat_user_id = $id WHERE id = $kelas_id");
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
            if (!in_array($role, ['Administrator Utama', 'Ketua Tingkat'])) {
                $_SESSION['error'] = "Akses ditolak! Pengelolaan jadwal kuliah hanya wewenang Ketua Tingkat.";
                header("Location: ../database/db_jadwal");
                exit;
            }
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
    }

    include '../includes/alert_actions.php';
    exit;
}

?>
<!DOCTYPE html>
<html lang="en">

<head>

    <title>Edit - SIMAWA PTIK 2024</title>
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
                            <div class="md:col-span-2 p-4 rounded-xl bg-slate-50 dark:bg-slate-800/50 border border-slate-200 dark:border-slate-700/60 flex items-center gap-4">
                                <div class="w-14 h-14 rounded-full overflow-hidden shadow-sm shrink-0">
                                    <img src="<?= profilePhotoUrl($data['nama_lengkap'], '../'); ?>" class="w-full h-full object-cover" alt="Avatar Inisial" />
                                </div>
                                <div>
                                    <h4 class="text-xs font-bold text-slate-800 dark:text-slate-100">Avatar Profil Otomatis</h4>
                                    <p class="text-[11px] text-slate-500 dark:text-slate-400 mt-0.5">Avatar dibuat otomatis berdasarkan inisial nama lengkap (SVG modern).</p>
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
                        <div class="mb-6">
                            <h3 class="text-xl font-bold text-slate-800 dark:text-slate-100">Edit Akun Ketua Tingkat</h3>
                            <p class="text-xs text-slate-400 mt-1">Perbarui data login Ketua Tingkat atau ubah kelas penugasan.</p>
                        </div>
                        <input type="hidden" name="role" value="<?= htmlspecialchars($data['role'] ?? 'Ketua Tingkat'); ?>">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label class="block text-xs font-medium text-slate-700 dark:text-slate-300 mb-1">Nama Lengkap Ketua Tingkat *</label>
                                <input type="text" name="nama_lengkap" value="<?= htmlspecialchars($data['display_nama'] ?? ''); ?>" required
                                    class="w-full px-4 py-2 border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-slate-200 rounded-lg focus:outline-none focus:ring-1 focus:ring-indigo-500 dark:focus:ring-indigo-600 text-xs transition-colors">
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-slate-700 dark:text-slate-300 mb-1">Email Login *</label>
                                <input type="email" name="email" value="<?= htmlspecialchars($data['email'] ?? ''); ?>" required
                                    class="w-full px-4 py-2 border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-slate-200 rounded-lg focus:outline-none focus:ring-1 focus:ring-indigo-500 dark:focus:ring-indigo-600 text-xs transition-colors">
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-slate-700 dark:text-slate-300 mb-1">Password</label>
                                <input type="password" name="password" value="**********"
                                    class="w-full px-4 py-2 border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-slate-200 rounded-lg focus:outline-none focus:ring-1 focus:ring-indigo-500 dark:focus:ring-indigo-600 text-xs transition-colors">
                                <p class="mt-1 text-[10px] text-slate-400">Biarkan <code>**********</code> jika tidak ingin mengganti password akun.</p>
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-slate-700 dark:text-slate-300 mb-1">Kelas yang Dipimpin *</label>
                                <select name="kelas_id" required class="w-full px-4 py-2 border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-slate-200 rounded-lg focus:outline-none focus:ring-1 focus:ring-indigo-500 dark:focus:ring-indigo-600 text-xs transition-colors cursor-pointer">
                                    <option value="">-- Pilih Kelas --</option>
                                    <?php
                                    $q_kelas_all = mysqli_query($koneksi, "SELECT id, nama_kelas FROM kelas ORDER BY id ASC");
                                    while ($kls = mysqli_fetch_assoc($q_kelas_all)) {
                                        $sel = ($data['kelas_id'] == $kls['id']) ? 'selected' : '';
                                        echo "<option value='{$kls['id']}' $sel>" . htmlspecialchars($kls['nama_kelas']) . "</option>";
                                    }
                                    ?>
                                </select>
                            </div>
                            <div class="md:col-span-2">
                                <label class="block text-xs font-medium text-slate-700 dark:text-slate-300 mb-1">Catatan NIM Mahasiswa (Opsional)</label>
                                <input type="text" name="linked_student_nim" value="<?= htmlspecialchars($data['linked_student_nim'] ?? ''); ?>" placeholder="Contoh: 240209501001"
                                    class="w-full px-4 py-2 border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-slate-200 rounded-lg focus:outline-none focus:ring-1 focus:ring-indigo-500 dark:focus:ring-indigo-600 text-xs transition-colors">
                                <p class="mt-1 text-[10px] text-slate-400">Informasi identitas mahasiswa di dunia nyata tanpa menggabungkan permission/data akademik.</p>
                            </div>
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
                                <?php
                                $q_per_edit = mysqli_query($koneksi, "SELECT id FROM periode WHERE status = 'aktif' LIMIT 1");
                                $per_edit = mysqli_fetch_assoc($q_per_edit);
                                $pid_edit = (int)($per_edit['id'] ?? 0);
                                ?>
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
                                                FROM mata_kuliah mk
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
                                <?php
                                $q_per_edit = mysqli_query($koneksi, "SELECT id FROM periode WHERE status = 'aktif' LIMIT 1");
                                $per_edit = mysqli_fetch_assoc($q_per_edit);
                                $pid_edit = (int)($per_edit['id'] ?? 0);
                                ?>
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
                                                FROM mata_kuliah mk
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
    
    
</body>

</html>