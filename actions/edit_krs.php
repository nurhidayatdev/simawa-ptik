<?php
session_start();
include '../koneksi.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../../index.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$nama_lengkap = $_SESSION['nama_lengkap'];
$foto = $_SESSION['foto'] ?? '';
$role = $_SESSION['role'];

/* =====================
   AMBIL PERIODE (AKTIF ATAU PILIHAN USER)
   ===================== */
$all_periodes_q = mysqli_query($koneksi, "SELECT id, semester_ke, tahun_periode, semester_nama, status FROM periode ORDER BY semester_ke ASC, id ASC");
$all_periodes = [];
while ($pr = mysqli_fetch_assoc($all_periodes_q)) {
    $all_periodes[] = $pr;
}

$req_periode_id = isset($_GET['periode_id']) ? (int)$_GET['periode_id'] : 0;
$periode = null;

if ($req_periode_id > 0) {
    $q_p_req = mysqli_query($koneksi, "SELECT id, semester_ke, tahun_periode, semester_nama, status FROM periode WHERE id = $req_periode_id LIMIT 1");
    $periode = mysqli_fetch_assoc($q_p_req);
}

if (!$periode) {
    // Default: periode aktif
    $q_p_act = mysqli_query($koneksi, "SELECT id, semester_ke, tahun_periode, semester_nama, status FROM periode WHERE status = 'aktif' LIMIT 1");
    $periode = mysqli_fetch_assoc($q_p_act);
    if (!$periode && !empty($all_periodes)) {
        $periode = $all_periodes[0];
    }
}

$periode_id        = (int)$periode['id'];
$semester_aktif    = (int)$periode['semester_ke'];
$periode_info      = $periode['tahun_periode'] . ' ' . $periode['semester_nama'];
$is_periode_aktif  = ($periode['status'] === 'aktif');
/* =====================
   AMBIL MAHASISWA_ID
   ===================== */
$stmt = mysqli_prepare(
    $koneksi,
    "SELECT id FROM mahasiswa WHERE user_id = ? LIMIT 1"
);
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$mahasiswa = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
$mahasiswa_id = $mahasiswa['id'] ?? null;

// Fallback jika akun pengurus kelas linked via linked_student_nim
if (empty($mahasiswa_id)) {
    $q_u_nim = mysqli_query($koneksi, "SELECT linked_student_nim FROM users WHERE id = $user_id LIMIT 1");
    $u_nim_data = mysqli_fetch_assoc($q_u_nim);
    if (!empty($u_nim_data['linked_student_nim'])) {
        $q_m_fb = mysqli_query($koneksi, "SELECT id FROM mahasiswa WHERE nim = '" . mysqli_real_escape_string($koneksi, $u_nim_data['linked_student_nim']) . "' LIMIT 1");
        $m_fb = mysqli_fetch_assoc($q_m_fb);
        $mahasiswa_id = $m_fb['id'] ?? 0;
    }
}

/* =====================
   AMBIL STATUS VALIDASI
   ===================== */
// Ensure table exists (safeguard)
mysqli_query($koneksi, "CREATE TABLE IF NOT EXISTS krs_temp (
    id INT AUTO_INCREMENT PRIMARY KEY,
    mahasiswa_id INT NOT NULL,
    matkul_id INT NOT NULL,
    periode_id INT NOT NULL,
    timestamp DATETIME DEFAULT CURRENT_TIMESTAMP,
    KEY idx_mhs_per (mahasiswa_id, periode_id)
)");
mysqli_query($koneksi, "CREATE TABLE IF NOT EXISTS krs_validasi (
    id INT AUTO_INCREMENT PRIMARY KEY,
    mahasiswa_id INT NOT NULL,
    periode_id INT NOT NULL,
    status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
    tanggal_validasi DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_validasi (mahasiswa_id, periode_id)
)");

$q_val = mysqli_query($koneksi, "SELECT status FROM krs_validasi WHERE mahasiswa_id = $mahasiswa_id AND periode_id = $periode_id");
$val_data = mysqli_fetch_assoc($q_val);
$status_validasi = $val_data['status'] ?? null; // null means not submitted yet

/* =====================
   SIMPAN KRS
   ===================== */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $matkul = $_POST['matkul'] ?? [];

    // Validasi SKS Server-side
    $total_sks_post = 0;
    if (!empty($matkul)) {
        $ids = implode(',', array_map('intval', $matkul));
        $res_sks = mysqli_query($koneksi, "SELECT SUM(sks) as total FROM mata_kuliah WHERE id IN ($ids)");
        $data_sks = mysqli_fetch_assoc($res_sks);
        $total_sks_post = (int)$data_sks['total'];
    }

    if ($total_sks_post > 24) {
        header("Location: edit_krs.php?error=sks_limit");
        exit;
    }

    if ($is_periode_aktif) {
        // PERIODE AKTIF: Simpan ke krs_temp sebagai draft dan status menjadi pending
        mysqli_query($koneksi, "DELETE FROM krs_temp WHERE mahasiswa_id = $mahasiswa_id AND periode_id = $periode_id");

        if (!empty($matkul)) {
            $escaped_matkul = implode(',', array_map('intval', $matkul));
            // Hapus dari draft periode lain jika ada
            mysqli_query($koneksi, "DELETE FROM krs_temp WHERE mahasiswa_id = $mahasiswa_id AND matkul_id IN ($escaped_matkul) AND periode_id != $periode_id");

            $stmt = mysqli_prepare($koneksi, "INSERT INTO krs_temp (mahasiswa_id, matkul_id, periode_id) VALUES (?, ?, ?)");
            foreach ($matkul as $matkul_id) {
                $m_id = (int)$matkul_id;
                mysqli_stmt_bind_param($stmt, "iii", $mahasiswa_id, $m_id, $periode_id);
                mysqli_stmt_execute($stmt);
            }
        }

        $stmt_val = mysqli_prepare($koneksi, "INSERT INTO krs_validasi (mahasiswa_id, periode_id, status) VALUES (?, ?, 'pending') ON DUPLICATE KEY UPDATE status = 'pending'");
        mysqli_stmt_bind_param($stmt_val, "ii", $mahasiswa_id, $periode_id);
        mysqli_stmt_execute($stmt_val);

        header("Location: edit_krs.php?periode_id=$periode_id&sukses=1");
        exit;
    } else {
        // PERIODE LAMPAU: Langsung perbarui tabel krs resmi dan disahkan (approved)
        // 1. Hapus krs & krs_temp lama untuk mahasiswa ini di periode ini
        mysqli_query($koneksi, "DELETE FROM krs WHERE mahasiswa_id = $mahasiswa_id AND periode_id = $periode_id");
        mysqli_query($koneksi, "DELETE FROM krs_temp WHERE mahasiswa_id = $mahasiswa_id AND periode_id = $periode_id");

        if (!empty($matkul)) {
            $escaped_matkul = implode(',', array_map('intval', $matkul));

            // 2. PINDAHKAN (SWITCH): Jika ada matkul yang sebelumnya tercatat di periode LAIN,
            // hapus dari periode lain tersebut agar berpindah ke periode ini tanpa melanggar unique_krs!
            mysqli_query($koneksi, "DELETE FROM krs WHERE mahasiswa_id = $mahasiswa_id AND matkul_id IN ($escaped_matkul)");
            mysqli_query($koneksi, "DELETE FROM krs_temp WHERE mahasiswa_id = $mahasiswa_id AND matkul_id IN ($escaped_matkul)");

            $stmt_krs = mysqli_prepare($koneksi, "INSERT INTO krs (mahasiswa_id, matkul_id, periode_id) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE periode_id = VALUES(periode_id)");
            $stmt_temp = mysqli_prepare($koneksi, "INSERT INTO krs_temp (mahasiswa_id, matkul_id, periode_id) VALUES (?, ?, ?)");
            foreach ($matkul as $matkul_id) {
                $m_id = (int)$matkul_id;
                mysqli_stmt_bind_param($stmt_krs, "iii", $mahasiswa_id, $m_id, $periode_id);
                mysqli_stmt_execute($stmt_krs);

                mysqli_stmt_bind_param($stmt_temp, "iii", $mahasiswa_id, $m_id, $periode_id);
                mysqli_stmt_execute($stmt_temp);
            }
        }

        $stmt_val = mysqli_prepare($koneksi, "INSERT INTO krs_validasi (mahasiswa_id, periode_id, status, tanggal_validasi) VALUES (?, ?, 'approved', NOW()) ON DUPLICATE KEY UPDATE status = 'approved', tanggal_validasi = NOW()");
        mysqli_stmt_bind_param($stmt_val, "ii", $mahasiswa_id, $periode_id);
        mysqli_stmt_execute($stmt_val);

        header("Location: edit_krs.php?periode_id=$periode_id&sukses_lampau=1");
        exit;
    }
}

/* =====================
   AMBIL MATKUL PTIK
   ===================== */
$res_matkul = mysqli_query($koneksi, "SELECT * FROM mata_kuliah ORDER BY semester ASC, kode_matkul ASC");

$matkul_rows = [];
while ($r = mysqli_fetch_assoc($res_matkul)) {
    $matkul_rows[] = $r;
}

/* =====================
   MATKUL YANG SUDAH DIAMBIL DI PERIODE INI & PERIODE LAIN
   ===================== */
$diambil = [];
if ($is_periode_aktif) {
    // Di periode aktif, prioritaskan krs_temp jika ada draft
    $krs_temp_q = mysqli_query($koneksi, "SELECT matkul_id FROM krs_temp WHERE mahasiswa_id = $mahasiswa_id AND periode_id = $periode_id");
    if (mysqli_num_rows($krs_temp_q) > 0) {
        while ($row = mysqli_fetch_assoc($krs_temp_q)) {
            $diambil[] = (int)$row['matkul_id'];
        }
    } else {
        $krs_act_q = mysqli_query($koneksi, "SELECT matkul_id FROM krs WHERE mahasiswa_id = $mahasiswa_id AND periode_id = $periode_id");
        while ($row = mysqli_fetch_assoc($krs_act_q)) {
            $diambil[] = (int)$row['matkul_id'];
        }
    }
} else {
    // Di periode lampau, ambil data resmi dari krs
    $krs_lampau_q = mysqli_query($koneksi, "SELECT matkul_id FROM krs WHERE mahasiswa_id = $mahasiswa_id AND periode_id = $periode_id");
    while ($row = mysqli_fetch_assoc($krs_lampau_q)) {
        $diambil[] = (int)$row['matkul_id'];
    }
}

// Cek matkul yang saat ini tercatat di periode LAIN untuk mahasiswa ini
$taken_other_periods = [];
$q_other = mysqli_query($koneksi, "
    SELECT k.matkul_id, p.semester_ke, p.tahun_periode, p.semester_nama
    FROM krs k
    JOIN periode p ON k.periode_id = p.id
    WHERE k.mahasiswa_id = $mahasiswa_id AND k.periode_id != $periode_id
");
while ($ro = mysqli_fetch_assoc($q_other)) {
    $taken_other_periods[(int)$ro['matkul_id']] = $ro['semester_ke'];
}
?>
<!DOCTYPE html>
<html lang="en">

<head>

    <title>Kartu Rencana Studi - SIMAWA PTIK 2024</title>
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

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>

<body class="bg-slate-100 dark:bg-slate-950 transition-colors">
    <?php include '../includes/menu_db.php'; ?>

    <main class="ml-0 lg:ml-48 pt-20 p-6 flex flex-col min-h-screen">
        <div class="bg-white dark:bg-slate-900/50 rounded-xl border border-slate-200 dark:border-slate-800 p-6 flex-grow w-full shadow-sm transition-all">

            <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-6">
                <div class="flex items-center gap-4">
                    <div>
                        <h3 class="text-2xl font-bold text-slate-800 dark:text-slate-100">Kartu Rencana Studi (KRS)</h3>
                        <p class="text-xs text-slate-500 dark:text-slate-400 font-medium mt-1">
                            Semester <?= $semester_aktif ?> &bull; <?= htmlspecialchars($periode_info) ?> 
                            <?= $is_periode_aktif ? '<span class="ml-1.5 px-2 py-0.5 rounded-full text-[9px] font-bold bg-blue-100 text-blue-700 dark:bg-blue-900/40 dark:text-blue-300">Semester Aktif</span>' : '<span class="ml-1.5 px-2 py-0.5 rounded-full text-[9px] font-bold bg-amber-100 text-amber-700 dark:bg-amber-900/40 dark:text-amber-300">Semester Lampau</span>' ?>
                        </p>
                    </div>
                    <button type="button" onclick="showKrsRules()" class="w-8 h-8 rounded-full bg-slate-100 dark:bg-slate-800 text-slate-500 dark:text-slate-400 hover:bg-indigo-100 dark:hover:bg-indigo-900/50 hover:text-indigo-600 dark:hover:text-indigo-400 transition-colors flex items-center justify-center shadow-sm border border-slate-200 dark:border-slate-700" title="Informasi Aturan">
                        <i class="fas fa-info-circle"></i>
                    </button>
                </div>
                <div class="bg-indigo-50 dark:bg-indigo-950/30 border border-indigo-200 dark:border-indigo-900/50 rounded-xl px-4 py-2 flex items-center gap-3">
                    <div class="w-10 h-10 rounded-full bg-indigo-600 dark:bg-indigo-700 flex items-center justify-center text-white shadow-lg shadow-indigo-200 dark:shadow-indigo-950/20">
                        <i class="fas fa-book-open"></i>
                    </div>
                    <div>
                        <p class="text-[10px] text-indigo-600 dark:text-indigo-400 font-bold uppercase tracking-wider">Total SKS Terpilih</p>
                        <p class="text-lg font-black text-indigo-950 dark:text-indigo-100"><span id="total-sks-display">0</span> <span class="text-xs font-normal text-slate-500 dark:text-slate-400">/ 24 SKS</span></p>
                    </div>
                </div>
            </div>

            
            <!-- Dropdown Pemilihan Semester / Periode -->
            <div class="mb-6 flex flex-col sm:flex-row sm:items-center justify-between gap-3 bg-slate-50 dark:bg-slate-800/40 p-3.5 rounded-xl border border-slate-200 dark:border-slate-800">
                <div class="flex items-center gap-2">
                    <i class="fas fa-calendar-alt text-indigo-500 text-sm"></i>
                    <span class="text-xs font-bold text-slate-700 dark:text-slate-200">Pilih Periode / Semester:</span>
                </div>
                <div class="flex items-center gap-2">
                    <select onchange="location.href='edit_krs.php?periode_id=' + this.value" class="w-full sm:w-auto bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 text-slate-900 dark:text-white px-3.5 py-1.5 rounded-lg text-xs font-bold outline-none cursor-pointer focus:ring-2 focus:ring-indigo-500/20">
                        <?php foreach ($all_periodes as $p_item): 
                            $is_sel = ($p_item['id'] == $periode_id);
                            $tag = ($p_item['status'] === 'aktif') ? ' (Semester Aktif)' : ' (Semester Lampau)';
                        ?>
                            <option value="<?= $p_item['id'] ?>" <?= $is_sel ? 'selected' : '' ?>>
                                Semester <?= $p_item['semester_ke'] ?> &bull; <?= htmlspecialchars($p_item['tahun_periode'] . ' ' . $p_item['semester_nama']) ?><?= $tag ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <?php if (!$is_periode_aktif): ?>
                <div class="bg-amber-50/70 dark:bg-amber-950/30 border border-amber-200 dark:border-amber-900/50 text-amber-800 dark:text-amber-300 px-4 py-3 rounded-xl mb-6 flex items-start shadow-sm transition-all">
                    <i class="fas fa-history mt-1 mr-3 text-amber-600 dark:text-amber-400"></i>
                    <div>
                        <p class="font-bold text-sm">Mode Penyesuaian Riwayat SKS (Semester Lampau)</p>
                        <p class="text-xs leading-relaxed mt-0.5">
                            Anda sedang menyesuaikan mata kuliah untuk <strong>Semester <?= $semester_aktif ?> (<?= htmlspecialchars($periode_info) ?>)</strong>. Centang mata kuliah yang telah Anda lulusi pada semester ini. Perubahan yang Anda simpan akan langsung disahkan dan otomatis memperbarui <strong>Progres 146 SKS Kelulusan</strong> di profil Anda.
                        </p>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Success logic handled by SweetAlert -->

            <?php if ($status_validasi === 'approved'): ?>
                <div class="bg-green-50/50 dark:bg-green-900/20 border border-green-200 dark:border-green-900/50 text-green-800 dark:text-green-300 px-4 py-3 rounded-xl mb-6 flex items-start shadow-sm transition-all">
                    <i class="fas fa-check-circle mt-1 mr-3"></i>
                    <div>
                        <p class="font-bold text-sm">Approved</p>
                        <p class="text-xs leading-relaxed">KRS Anda telah <strong>disetujui</strong>. Jika Anda melakukan perubahan dan menyimpannya kembali, status akan berubah menjadi <strong>Menunggu Persetujuan</strong>.</p>
                    </div>
                </div>
            <?php elseif ($status_validasi === 'pending'): ?>
                <div class="bg-yellow-50/50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-900/50 text-yellow-800 dark:text-yellow-300 px-4 py-3 rounded-xl mb-6 flex items-start shadow-sm transition-all">
                    <i class="fas fa-clock mt-1 mr-3"></i>
                    <div>
                        <p class="font-bold text-sm">Menunggu Persetujuan</p>
                        <p class="text-xs leading-relaxed">Draft KRS Anda sedang menunggu validasi oleh Admin. Anda masih dapat mengubahnya selama belum disetujui.</p>
                    </div>
                </div>
            <?php elseif ($status_validasi === 'rejected'): ?>
                <div class="bg-red-50/50 dark:bg-red-900/20 border border-red-200 dark:border-red-900/50 text-red-800 dark:text-red-300 px-4 py-3 rounded-xl mb-6 flex items-start shadow-sm transition-all">
                    <i class="fas fa-times-circle mt-1 mr-3"></i>
                    <div>
                        <p class="font-bold text-sm">Ditolak</p>
                        <p class="text-xs leading-relaxed">KRS Anda ditolak. Silakan perbaiki pilihan mata kuliah Anda dan simpan kembali.</p>
                    </div>
                </div>
            <?php else: ?>
                <div class="bg-blue-50/50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-900/50 text-blue-800 dark:text-blue-300 px-4 py-3 rounded-xl mb-6 flex items-start shadow-sm transition-all">
                    <i class="fas fa-info-circle mt-1 mr-3"></i>
                    <div>
                        <p class="font-bold text-sm">Silakan Isi KRS</p>
                        <p class="text-xs leading-relaxed">Pilih mata kuliah dan klik Simpan. Data akan dikirim untuk divalidasi.</p>
                    </div>
                </div>
            <?php endif; ?>

            <form method="POST">

                <!-- Filter & Search Toolbar Lintas Semester -->
                <div class="mb-5 bg-slate-50 dark:bg-slate-800/40 p-4 rounded-2xl border border-slate-200 dark:border-slate-800 space-y-3">
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                        <div class="relative flex-1">
                            <i class="fas fa-search absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 text-xs"></i>
                            <input type="text" id="searchMatkulInput" placeholder="Cari nama atau kode mata kuliah lintas semester..." oninput="filterMatkulLive(this.value)" class="w-full pl-9 pr-4 py-2 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-xl text-xs font-medium text-slate-800 dark:text-slate-100 placeholder-slate-400 outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 transition-all">
                        </div>
                        <div class="text-[11px] font-semibold text-slate-500 dark:text-slate-400 flex items-center gap-1.5 shrink-0">
                            <i class="fas fa-layer-group text-indigo-500"></i>
                            <span>Tersedia: <strong>72 Mata Kuliah (Sem 1 - 8)</strong></span>
                        </div>
                    </div>

                    <!-- Tab Buttons Semester -->
                    <div class="flex items-center gap-1.5 overflow-x-auto pb-1 custom-scrollbar">
                        <button type="button" onclick="showMatkulSem('all')" class="sem-tab-btn px-3 py-1.5 rounded-xl text-xs font-bold transition-all bg-indigo-600 text-white shadow-sm shrink-0 cursor-pointer" data-sem="all">
                            Semua Semester (1-8)
                        </button>
                        <?php for ($s = 1; $s <= 8; $s++): 
                            $is_current_target = ($s === $semester_aktif);
                        ?>
                            <button type="button" onclick="showMatkulSem(<?= $s ?>)" class="sem-tab-btn px-3 py-1.5 rounded-xl text-xs font-bold transition-all bg-white dark:bg-slate-900 text-slate-600 dark:text-slate-400 border border-slate-200 dark:border-slate-700 hover:border-indigo-500 shrink-0 cursor-pointer flex items-center gap-1" data-sem="<?= $s ?>">
                                <span>Sem <?= $s ?></span>
                                <?php if ($is_current_target): ?>
                                    <span class="w-2 h-2 rounded-full bg-indigo-500"></span>
                                <?php endif; ?>
                            </button>
                        <?php endfor; ?>
                    </div>
                </div>

                <!-- Loop Seluruh Semester (1 s/d 8) -->
                <?php 
                $row_id = 1;
                for ($s = 1; $s <= 8; $s++): 
                    $is_current_target = ($s === $semester_aktif);
                    // Hitung jumlah matkul di semester ini
                    $sem_matkul_list = array_filter($matkul_rows, function($m) use ($s) {
                        return (int)$m['semester'] === $s;
                    });
                ?>
                    <div class="sem-container mb-6" id="sem-section-<?= $s ?>" data-semester="<?= $s ?>">
                        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 p-3 bg-indigo-50/70 dark:bg-indigo-950/30 rounded-t-xl border border-indigo-100 dark:border-indigo-900/50">
                            <div class="flex items-center gap-2.5">
                                <span class="w-7 h-7 rounded-lg <?= $is_current_target ? 'bg-indigo-600 text-white shadow-sm' : 'bg-slate-200 dark:bg-slate-800 text-slate-700 dark:text-slate-300' ?> flex items-center justify-center font-bold text-xs shrink-0">
                                    <?= $s ?>
                                </span>
                                <div>
                                    <h4 class="text-sm font-bold text-slate-800 dark:text-slate-100 flex items-center gap-2">
                                        <span>Semester <?= $s ?></span>
                                        <?php if ($is_current_target): ?>
                                            <span class="px-2 py-0.5 rounded-full text-[9px] font-bold bg-indigo-100 text-indigo-700 dark:bg-indigo-900/50 dark:text-indigo-300 border border-indigo-200 dark:border-indigo-800">
                                                Paket Semester Ini
                                            </span>
                                        <?php else: ?>
                                            <span class="px-2 py-0.5 rounded-full text-[9px] font-bold bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-400 border border-slate-200 dark:border-slate-700">
                                                Lintas Semester
                                            </span>
                                        <?php endif; ?>
                                    </h4>
                                    <p class="text-[10px] text-slate-400"><?= count($sem_matkul_list) ?> Mata Kuliah Terdaftar</p>
                                </div>
                            </div>
                            <div class="flex items-center gap-2">
                                <span id="sem-badge-count-<?= $s ?>" class="text-[11px] font-bold text-indigo-600 dark:text-indigo-400 bg-white dark:bg-slate-900 px-2.5 py-1 rounded-lg border border-indigo-100 dark:border-indigo-900/50">
                                    0 MK Terpilih
                                </span>
                                <button type="button" onclick="toggleSelectSemester(<?= $s ?>)" class="text-xs bg-white dark:bg-slate-900 hover:bg-indigo-50 dark:hover:bg-indigo-900/50 text-indigo-700 dark:text-indigo-300 px-3 py-1 rounded-lg border border-indigo-200 dark:border-indigo-800 font-semibold flex items-center shadow-2xs transition-colors cursor-pointer">
                                    <i class="fas fa-check-double mr-1.5 text-[10px]"></i> Pilih Semua Sem <?= $s ?>
                                </button>
                            </div>
                        </div>

                        <div class="overflow-x-auto rounded-b-xl border-x border-b border-slate-200 dark:border-slate-800 shadow-sm">
                            <table class="min-w-full divide-y divide-slate-200 dark:divide-slate-800">
                                <thead class="bg-slate-100 dark:bg-slate-900 text-slate-600 dark:text-slate-400">
                                    <tr>
                                        <th class="px-3 py-2.5 text-center text-[10px] font-bold uppercase tracking-wider w-16">Ambil</th>
                                        <th class="px-3 py-2.5 text-left text-[10px] font-bold uppercase tracking-wider w-28">Kode</th>
                                        <th class="px-3 py-2.5 text-left text-[10px] font-bold uppercase tracking-wider">Nama Mata Kuliah</th>
                                        <th class="px-3 py-2.5 text-center text-[10px] font-bold uppercase tracking-wider w-20">SKS</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white dark:bg-slate-900/50 divide-y divide-slate-200 dark:divide-slate-800">
                                    <?php if (empty($sem_matkul_list)): ?>
                                        <tr>
                                            <td class="px-3 py-4 text-center text-xs text-slate-400 italic" colspan="4">Tidak ada mata kuliah untuk semester <?= $s ?>.</td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($sem_matkul_list as $m): 
                                            $m_id = (int)$m['id'];
                                            $is_checked = in_array($m_id, $diambil);
                                            $other_sem = $taken_other_periods[$m_id] ?? null;
                                        ?>
                                            <tr class="matkul-row <?= ($row_id++ % 2 == 0) ? 'bg-slate-50/60 dark:bg-slate-800/30' : 'bg-white dark:bg-slate-900/20' ?> hover:bg-indigo-50/70 dark:hover:bg-indigo-900/40 transition-colors group cursor-pointer" data-sem="<?= $s ?>" data-search="<?= strtolower(htmlspecialchars($m['kode_matkul'] . ' ' . $m['nama_matkul'])) ?>">
                                                <td class="px-3 py-2.5 whitespace-nowrap text-xs text-center">
                                                    <input type="checkbox" name="matkul[]" value="<?= $m_id ?>" data-sks="<?= $m['sks'] ?>" data-sem="<?= $s ?>" <?= $other_sem ? 'data-other-sem="' . $other_sem . '"' : '' ?> class="sks-checkbox rounded border-slate-300 dark:border-slate-700 text-indigo-600 dark:text-indigo-500 focus:ring-indigo-500 dark:focus:ring-indigo-600 dark:bg-slate-800 cursor-pointer w-4 h-4" <?= $is_checked ? 'checked' : '' ?>>
                                                </td>
                                                <td class="px-3 py-2.5 whitespace-nowrap text-xs font-mono font-bold text-slate-700 dark:text-slate-300 group-hover:text-indigo-600 dark:group-hover:text-indigo-400 transition-colors"><?= htmlspecialchars($m['kode_matkul']) ?></td>
                                                <td class="px-3 py-2.5 text-xs text-slate-800 dark:text-slate-200 group-hover:text-indigo-600 dark:group-hover:text-indigo-400 transition-colors font-medium">
                                                    <div class="flex items-center gap-2 flex-wrap">
                                                        <span><?= htmlspecialchars($m['nama_matkul']) ?></span>
                                                        <?php if ($other_sem && !$is_checked): ?>
                                                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[9px] font-bold bg-amber-50 text-amber-700 dark:bg-amber-950/40 dark:text-amber-400 border border-amber-200 dark:border-amber-800/60" title="Matkul ini saat ini tercatat di Semester <?= $other_sem ?>. Centang untuk memindahkannya ke semester ini.">
                                                                <i class="fas fa-exchange-alt text-[8px]"></i> Tercatat di Sem <?= $other_sem ?> (Centang utk pindahkan)
                                                            </span>
                                                        <?php endif; ?>
                                                    </div>
                                                </td>
                                                <td class="px-3 py-2.5 whitespace-nowrap text-xs font-bold text-indigo-600 dark:text-indigo-400 text-center"><?= htmlspecialchars($m['sks']) ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                <?php endfor; ?>

                <div class="mt-8 pt-8 border-t border-slate-200 dark:border-slate-800 flex flex-col items-center">
                    <p class="text-[10px] text-slate-500 dark:text-slate-500 mb-6 italic">Pastikan mata kuliah yang dipilih sudah sesuai sebelum melakukan penyimpanan.</p>
                    <button type="button" onclick="confirmSaveKRS()"
                        class="px-10 py-3 bg-indigo-800 dark:bg-indigo-700 text-white rounded-xl hover:bg-indigo-950 dark:hover:bg-indigo-600 text-xs font-bold shadow-lg shadow-indigo-900/20 dark:shadow-indigo-950/40 transition-all active:scale-95 flex items-center gap-2">
                        <i class="fa fa-save"></i> Simpan KRS
                    </button>
                </div>
            </form>

        </div>
        <?php include '../includes/footer_db.php'; ?>
    </main>
    <?php include '../includes/script_db.php'; ?>
    <script>
        // Reusable function for KRS rules
        function showKrsRules() {
            const isDark = document.documentElement.classList.contains('dark');
            Swal.fire({
                title: `<span class="${isDark ? 'text-indigo-300' : 'text-indigo-950'} font-bold">Aturan Pengisian KRS</span>`,
                html: `
                    <div class="text-left text-xs space-y-3 p-2">
                        <div class="flex gap-3">
                            <div class="mt-1"><i class="fas fa-check-circle text-indigo-500"></i></div>
                            <p class="${isDark ? 'text-slate-300' : 'text-slate-700'}">Maksimal beban studi yang dapat diambil adalah <strong>24 SKS</strong>.</p>
                        </div>
                        <div class="flex gap-3">
                            <div class="mt-1"><i class="fas fa-check-circle text-indigo-500"></i></div>
                            <p class="${isDark ? 'text-slate-300' : 'text-slate-700'}">Hanya mengambil mata kuliah yang <strong>diprogramkan bersama SIMAWA PTIK 2024</strong>.</p>
                        </div>
                        <div class="flex gap-3">
                            <div class="mt-1"><i class="fas fa-exclamation-triangle text-amber-500"></i></div>
                            <p class="${isDark ? 'text-slate-300' : 'text-slate-700'}">Dilarang mengambil mata kuliah dari kelas lain.</p>
                        </div>
                        <div class="${isDark ? 'bg-indigo-950/40 border-indigo-800' : 'bg-indigo-50 border-indigo-100'} p-3 rounded-lg border mt-4">
                            <p class="text-[10px] ${isDark ? 'text-indigo-200' : 'text-indigo-900'} leading-relaxed italic">
                                *Sistem KRS ini digunakan secara otomatis sebagai filter pada sistem <strong>Absensi, Pembagian Kelompok</strong>, dan fitur akademik lainnya di dashboard ini.
                            </p>
                        </div>
                    </div>
                `,
                icon: 'info',
                confirmButtonText: 'Saya Mengerti',
                confirmButtonColor: '#3730a3',
                background: isDark ? '#0f172a' : '#ffffff',
                color: isDark ? '#f1f5f9' : '#0f172a',
                allowOutsideClick: false
            });
        }

        // Show Rules Popup Automatically on Entry (Once Only)
        document.addEventListener('DOMContentLoaded', () => {
            const rulesSeen = localStorage.getItem('krs_rules_seen');
            if (!rulesSeen) {
                showKrsRules();
                localStorage.setItem('krs_rules_seen', 'true');
            }
        });



        // SKS Counter Logic
        const sksCheckboxes = document.querySelectorAll('.sks-checkbox');
        const totalSksDisplay = document.getElementById('total-sks-display');

        
        // ============================================================
        // Lintas Semester Tab Filter & Live Search
        // ============================================================
        let activeSemFilter = "all";

        function showMatkulSem(sem) {
            activeSemFilter = sem;
            document.querySelectorAll(".sem-tab-btn").forEach(btn => {
                const bsem = btn.getAttribute("data-sem");
                if (String(bsem) === String(sem)) {
                    btn.classList.add("bg-indigo-600", "text-white", "shadow-sm");
                    btn.classList.remove("bg-white", "dark:bg-slate-900", "text-slate-600", "dark:text-slate-400", "bg-slate-200", "dark:bg-slate-700");
                } else {
                    btn.classList.remove("bg-indigo-600", "text-white", "shadow-sm");
                    btn.classList.add("bg-white", "dark:bg-slate-900", "text-slate-600", "dark:text-slate-400");
                }
            });

            document.querySelectorAll(".sem-container").forEach(el => {
                const esem = el.getAttribute("data-semester");
                if (sem === "all" || String(esem) === String(sem)) {
                    el.classList.remove("hidden");
                } else {
                    el.classList.add("hidden");
                }
            });
        }

        function filterMatkulLive(keyword) {
            const q = keyword.trim().toLowerCase();
            document.querySelectorAll(".matkul-row").forEach(row => {
                const s = row.getAttribute("data-search") || "";
                if (!q || s.includes(q)) {
                    row.classList.remove("hidden");
                } else {
                    row.classList.add("hidden");
                }
            });

            // Jika sedang mencari keyword, pastikan semua container semester terbuka
            if (q.length > 0) {
                document.querySelectorAll(".sem-container").forEach(el => el.classList.remove("hidden"));
            } else {
                showMatkulSem(activeSemFilter);
            }
        }

        function toggleSelectSemester(sem) {
            const cbs = document.querySelectorAll(`.sks-checkbox[data-sem="${sem}"]`);
            const allChecked = Array.from(cbs).every(cb => cb.checked);
            cbs.forEach(cb => cb.checked = !allChecked);
            updateTotalSks();
        }

        function updateSemesterBadges() {
            for (let s = 1; s <= 8; s++) {
                const count = document.querySelectorAll(`.sks-checkbox[data-sem="${s}"]:checked`).length;
                const badge = document.getElementById(`sem-badge-count-${s}`);
                if (badge) {
                    badge.textContent = `${count} MK Terpilih`;
                    if (count > 0) {
                        badge.classList.add("text-indigo-600", "dark:text-indigo-400", "font-black");
                    } else {
                        badge.classList.remove("font-black");
                    }
                }
            }
        }

        function updateTotalSks() {
            updateSemesterBadges();
            let total = 0;
            
        // Click row to toggle checkbox
        document.querySelectorAll(".matkul-row").forEach(row => {
            row.addEventListener("click", (e) => {
                if (e.target.tagName.toLowerCase() === "input") return;
                const cb = row.querySelector(".sks-checkbox");
                if (cb) {
                    cb.checked = !cb.checked;
                    updateTotalSks();
                }
            });
        });

        sksCheckboxes.forEach(cb => {
                if (cb.checked) {
                    total += parseInt(cb.dataset.sks);
                }
            });
            totalSksDisplay.innerText = total;

            if (total > 24) {
                totalSksDisplay.classList.add('text-red-600');
                totalSksDisplay.parentElement.classList.add('animate-bounce');
            } else {
                totalSksDisplay.classList.remove('text-red-600');
                totalSksDisplay.parentElement.classList.remove('animate-bounce');
            }
        }

        
        // Click row to toggle checkbox
        document.querySelectorAll(".matkul-row").forEach(row => {
            row.addEventListener("click", (e) => {
                if (e.target.tagName.toLowerCase() === "input") return;
                const cb = row.querySelector(".sks-checkbox");
                if (cb) {
                    cb.checked = !cb.checked;
                    updateTotalSks();
                }
            });
        });

        sksCheckboxes.forEach(cb => {
            cb.addEventListener('change', () => {
                updateTotalSks();

                // Optional: Prevent checking if it exceeds 24
                const currentTotal = parseInt(totalSksDisplay.innerText);
                if (currentTotal > 24 && cb.checked) {
                    // We let them check but totalSksDisplay will show error
                }
            });
        });

        // Initial update
        updateTotalSks();

        // SweetAlert for Saving KRS
        function confirmSaveKRS() {
            const currentTotal = parseInt(totalSksDisplay.innerText);

            if (currentTotal > 24) {
                const isDark = document.documentElement.classList.contains('dark');
                Swal.fire({
                    icon: 'error',
                    title: 'Batas SKS Terlampaui',
                    text: 'Total SKS yang Anda pilih (' + currentTotal + ') melebihi batas maksimal 24 SKS.',
                    confirmButtonColor: '#3730a3',
                    background: isDark ? '#0f172a' : '#ffffff',
                    color: isDark ? '#f1f5f9' : '#0f172a'
                });
                return;
            }

            if (currentTotal === 0) {
                const isDark = document.documentElement.classList.contains('dark');
                Swal.fire({
                    icon: 'warning',
                    title: 'Belum Ada Matkul',
                    text: 'Silakan pilih minimal satu mata kuliah.',
                    confirmButtonColor: '#3730a3',
                    background: isDark ? '#0f172a' : '#ffffff',
                    color: isDark ? '#f1f5f9' : '#0f172a'
                });
                return;
            }

            const isDark = document.documentElement.classList.contains('dark');
            const switchingCbs = Array.from(document.querySelectorAll('.sks-checkbox:checked[data-other-sem]'));
            let switchNotice = '';
            if (switchingCbs.length > 0) {
                switchNotice = '<div class="mt-3 p-2.5 rounded-xl bg-amber-50 dark:bg-amber-950/40 border border-amber-200 dark:border-amber-900/50 text-[11px] font-semibold text-amber-700 dark:text-amber-300 text-left flex items-start gap-2"><i class="fas fa-exchange-alt mt-0.5 text-amber-500"></i><span>Perhatian: Terdapat <b>' + switchingCbs.length + ' mata kuliah</b> yang sebelumnya tercatat di semester lain. Menyimpan akan memindahkannya ke semester ini secara otomatis.</span></div>';
            }

            Swal.fire({
                title: 'Simpan KRS?',
                html: '<p class="text-xs text-slate-600 dark:text-slate-300">Total SKS terpilih: <b class="text-indigo-600 dark:text-indigo-400">' + currentTotal + ' SKS</b>. Pastikan pilihan Anda sudah benar.</p>' + switchNotice,
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#3730a3',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Ya, Simpan!',
                cancelButtonText: 'Batal',
                background: isDark ? '#0f172a' : '#ffffff',
                color: isDark ? '#f1f5f9' : '#0f172a'
            }).then((result) => {
                if (result.isConfirmed) {
                    document.querySelector('form').submit();
                }
            });
        }

        // Toggle Select All
        function toggleSelectAll() {
            const allCbs = document.querySelectorAll('.sks-checkbox');
            const allChecked = Array.from(allCbs).every(cb => cb.checked);

            allCbs.forEach(cb => {
                cb.checked = !allChecked;
            });
            updateTotalSks();
        }

        // Show Success Alert
        <?php if (isset($_GET['sukses_lampau'])): ?>
            Swal.fire({
                icon: 'success',
                title: 'Riwayat SKS Diperbarui!',
                text: 'Mata kuliah semester lampau berhasil disesuaikan dan langsung disahkan pada progres kelulusan 146 SKS Anda.',
                confirmButtonColor: '#4f46e5',
                timer: 4000
            });
        <?php elseif (isset($_GET['sukses'])): ?>
            (() => {
                const isDark = document.documentElement.classList.contains('dark');
                Swal.fire({
                    icon: 'success',
                    title: 'Berhasil Disimpan!',
                    text: 'Draft KRS Anda berhasil disimpan dan menunggu persetujuan.',
                    confirmButtonColor: '#3730a3',
                    background: isDark ? '#0f172a' : '#ffffff',
                    color: isDark ? '#f1f5f9' : '#0f172a'
                }).then(() => {
                    window.location.href = '../views/ksm.php';
                });
            })();
        <?php endif; ?>

        // Show Error Alert for SKS Limit
        <?php if (isset($_GET['error']) && $_GET['error'] === 'sks_limit'): ?>
            (() => {
                const isDark = document.documentElement.classList.contains('dark');
                Swal.fire({
                    icon: 'error',
                    title: 'Gagal Menyimpan!',
                    text: 'Total SKS melebihi batas maksimal 24 SKS.',
                    confirmButtonColor: '#3730a3',
                    background: isDark ? '#0f172a' : '#ffffff',
                    color: isDark ? '#f1f5f9' : '#0f172a'
                });
            })();
        <?php endif; ?>
    </script>
</body>

</html>