<?php
session_start();
include '../koneksi.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../../index.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$nama_lengkap = $_SESSION['nama_lengkap'] ?? '';
$foto = $_SESSION['foto'] ?? '';
$role = $_SESSION['role'];

if ($role === 'Administrator Utama') {
    $_SESSION['error'] = 'Akses ditolak! Administrator Utama tidak mengelola notifikasi kelas.';
    header('Location: ../dashboard_admin.php');
    exit;
}

// AJAX Handler for "Sesuaikan Jadwal" Data Fetching
if (isset($_GET['fetch_adjust_data'])) {
    header('Content-Type: application/json');
    date_default_timezone_set('Asia/Makassar');

    $day_type = $_GET['day'] ?? 'besok';
    $days_map = ['Sunday' => 'Minggu', 'Monday' => 'Senin', 'Tuesday' => 'Selasa', 'Wednesday' => 'Rabu', 'Thursday' => 'Kamis', 'Friday' => 'Jumat', 'Saturday' => 'Sabtu'];
    $offset = ($day_type === 'hari_ini') ? 'now' : '+1 day';
    $target_eng = date('l', strtotime($offset));
    $target_ind = $days_map[$target_eng];

    // 1. Get Schedule
    $q_p = mysqli_query($koneksi, "SELECT id FROM periode WHERE status = 'aktif' LIMIT 1");
    $p_id = mysqli_fetch_assoc($q_p)['id'] ?? 0;

    $where_jm_scope = getKelasWhereClause('jm', true);
    $query_jadwal = "SELECT jm.id, mk.nama_matkul, 
                     jk_start.jam_mulai, jk_end.jam_selesai,
                     r.nama_ruangan, r.id as ruangan_id
                     FROM jadwal_matkul jm
                     JOIN mata_kuliah mk ON jm.matkul_id = mk.id
                     LEFT JOIN ruangan r ON jm.ruangan_id = r.id
                     LEFT JOIN jam_kuliah jk_start ON jm.jam_mulai_ke = jk_start.jam_ke
                     LEFT JOIN jam_kuliah jk_end ON jm.jam_selesai_ke = jk_end.jam_ke
                     WHERE jm.periode_id = $p_id AND jm.hari = '$target_ind' $where_jm_scope
                     ORDER BY jk_start.jam_mulai ASC";

    $res_jadwal = mysqli_query($koneksi, $query_jadwal);
    $jadwal = [];
    while ($row = mysqli_fetch_assoc($res_jadwal)) {
        $jam_display = date('H.i', strtotime($row['jam_mulai'])) . ' - ' . date('H.i', strtotime($row['jam_selesai']));
        $jadwal[] = [
            'id' => $row['id'],
            'matkul' => $row['nama_matkul'],
            'jam_text' => $jam_display,
            'ruangan_text' => $row['nama_ruangan'] ?? '—',
            'ruangan_id' => $row['ruangan_id'] ?? 0,
            'checked' => true,
            'status' => 'masuk'
        ];
    }

    // 2. Get Matkul that exist in Schedule for active period
    $query_mk = "SELECT DISTINCT mk.id, mk.nama_matkul 
                 FROM jadwal_matkul jm 
                 JOIN mata_kuliah mk ON jm.matkul_id = mk.id 
                 WHERE jm.periode_id = $p_id $where_jm_scope
                 ORDER BY mk.nama_matkul ASC";
    $res_mk = mysqli_query($koneksi, $query_mk);
    $opts_mk = [];
    while ($row = mysqli_fetch_assoc($res_mk)) {
        $opts_mk[] = ['id' => $row['id'], 'nama' => $row['nama_matkul']];
    }

    // 2. Get All Jam Kuliah Options
    $res_jam = mysqli_query($koneksi, "SELECT jam_ke, jam_mulai, jam_selesai FROM jam_kuliah ORDER BY jam_ke ASC");
    $opts_jam = [];
    while ($row = mysqli_fetch_assoc($res_jam)) {
        $opts_jam[] = [
            'val' => date('H.i', strtotime($row['jam_mulai'])) . ' - ' . date('H.i', strtotime($row['jam_selesai'])),
            'label' => "Jam ke-{$row['jam_ke']} (" . date('H:i', strtotime($row['jam_mulai'])) . " - " . date('H:i', strtotime($row['jam_selesai'])) . ")"
        ];
    }

    // 3. Get All Ruangan Options
    $res_ruang = mysqli_query($koneksi, "SELECT id, nama_ruangan FROM ruangan ORDER BY nama_ruangan ASC");
    $opts_ruang = [];
    while ($row = mysqli_fetch_assoc($res_ruang)) {
        $opts_ruang[] = [
            'val' => $row['nama_ruangan'],
            'label' => $row['nama_ruangan']
        ];
    }

    echo json_encode([
        'tomorrow' => $target_ind,
        'date' => date('d M Y', strtotime($offset)),
        'jadwal' => $jadwal,
        'opt_jam' => $opts_jam,
        'opt_ruang' => $opts_ruang,
        'opt_mk' => $opts_mk
    ]);
    exit;
}

if ($_SESSION['role'] === 'Mahasiswa') {
    include '../includes/alert_role.php';
    exit();
}

date_default_timezone_set('Asia/Makassar');

// Fonnte Config (Moved to helpers.php)
require_once __DIR__ . '/../functions/helpers.php';


// Include reminder functions
require_once __DIR__ . '/../functions/kirim_jadwal.php';
require_once __DIR__ . '/../functions/kirim_tugas.php';
require_once __DIR__ . '/../functions/kirim_kas.php';
require_once __DIR__ . '/../functions/kirim_krs.php';
require_once __DIR__ . '/../functions/kirim_kelompok.php';
require_once __DIR__ . '/../functions/kirim_piket_reminder.php';

// Handle Actions (PRG Pattern)
if (isset($_POST['action'])) {
    $action = $_POST['action'];
    $response = ['status' => 'error', 'message' => 'Aksi tidak dikenal'];

    if ($action === 'jadwal') {
        $day = $_POST['day'] ?? 'besok';
        $response = kirimJadwalReminder($koneksi, null, null, null, $day);
    } elseif ($action === 'jadwal_custom') {
        // Handle custom data via JSON payload
        $day = $_POST['day'] ?? 'besok';
        if (isset($_POST['custom_payload'])) {
            $custom_data = json_decode($_POST['custom_payload'], true);
        } else {
            $custom_data = [[
                'matkul' => $_POST['matkul'] ?? '',
                'jam' => $_POST['jam'] ?? '',
                'ruangan' => $_POST['ruangan'] ?? '',
                'status' => $_POST['status'] ?? 'masuk'
            ]];
        }
        $response = kirimJadwalReminder($koneksi, null, null, $custom_data, $day);
        if ($response['status'] === 'success') {
            $_SESSION['last_custom_payload'] = $_POST['custom_payload'] ?? null;
            $_SESSION['last_day'] = $day;
            $_SESSION['show_piket_button'] = true;
        }
    } elseif ($action === 'tugas') {
        $response = kirimTugasReminder($koneksi);
    } elseif ($action === 'kas') {
        $minggu_ke = (int)($_POST['minggu_ke'] ?? 1);
        $response = kirimKasReminder($koneksi, $minggu_ke);
    } elseif ($action === 'krs') {
        $response = kirimKrsReminder($koneksi);
    } elseif ($action === 'kelompok') {
        $matkul_id = $_POST['matkul_id'];
        $dosen = $_POST['dosen'];
        $keterangan = $_POST['keterangan'];
        $response = kirimKelompokReminder($koneksi, $matkul_id, $dosen, $keterangan);
    } elseif ($action === 'piket') {
        $day = $_POST['day'] ?? 'besok';
        $custom_data = null;
        if (isset($_POST['custom_payload'])) {
            $custom_data = json_decode($_POST['custom_payload'], true);
        }
        $minggu_ke = isset($_POST['minggu_ke']) && !empty($_POST['minggu_ke']) ? (int)$_POST['minggu_ke'] : null;
        $response = kirimPiketReminder($koneksi, null, null, $day, $custom_data, $minggu_ke);
    }

    $_SESSION['reminder_msg'] = $response['message'];
    $_SESSION['reminder_type'] = $response['status'];

    // Redirect back
    $redirect_url = $_POST['redirect'] ?? 'reminder';
    header("Location: $redirect_url");
    exit;
}


// Counts for display
$now_date = date('Y-m-d');
$now_time = date('H:i:s');
$yesterday_date = date('Y-m-d', strtotime('-1 day'));
$where_t_scope = getKelasWhereClause('', true);
$tugas_count = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT COUNT(*) as cnt FROM tugas WHERE status='belum' AND (deadline_tanggal > '$now_date' OR (deadline_tanggal = '$now_date' AND deadline_pukul > '$now_time')) $where_t_scope"))['cnt'] ?? 0;

$q_p = mysqli_query($koneksi, "SELECT id FROM periode WHERE status = 'aktif' LIMIT 1");
$p_id = mysqli_fetch_assoc($q_p)['id'] ?? 0;
$where_m_scope = getKelasWhereClause('m', true);
$krs_count = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT COUNT(*) as cnt FROM mahasiswa m LEFT JOIN (SELECT DISTINCT mahasiswa_id FROM krs WHERE periode_id = $p_id) k ON m.id = k.mahasiswa_id WHERE k.mahasiswa_id IS NULL $where_m_scope"))['cnt'] ?? 0;

// Fetch unique group sets
$where_k_scope = getKelasWhereClause('k', true);
$q_sets = mysqli_query($koneksi, "
    SELECT DISTINCT 
        k.matkul_id, 
        mk.nama_matkul, 
        k.dosen, 
        k.keterangan 
    FROM kelompok k 
    JOIN mata_kuliah mk ON k.matkul_id = mk.id 
    WHERE k.periode_id = $p_id $where_k_scope
    ORDER BY mk.nama_matkul ASC, k.dosen ASC
");
$group_sets = [];
while ($row = mysqli_fetch_assoc($q_sets)) {
    $group_sets[] = $row;
}

// Kalender akademik untuk opsi piket mingguan
$q_start_piket = mysqli_query($koneksi, "SELECT tanggal_mulai FROM kalender_akademik WHERE periode_id = $p_id AND (kegiatan LIKE '%Kuliah Bagian Pertama%') ORDER BY tanggal_mulai ASC LIMIT 1");
$start_piket_res = mysqli_fetch_assoc($q_start_piket);
$start_piket_date = $start_piket_res['tanggal_mulai'] ?? "2026-08-18";
$dow_piket = (int)date('N', strtotime($start_piket_date));
$start_monday_piket = date('Y-m-d', strtotime("$start_piket_date - " . ($dow_piket - 1) . " days"));
$today_piket = date('Y-m-d');
$diff_piket = strtotime($today_piket) - strtotime($start_monday_piket);
$default_minggu_piket = floor($diff_piket / (7 * 24 * 60 * 60)) + 1;
if ($default_minggu_piket < 1) $default_minggu_piket = 1;
if ($default_minggu_piket > 18) $default_minggu_piket = 18;

$week_piket_options = [];
for ($i = 1; $i <= 18; $i++) {
    $w_monday = date('Y-m-d', strtotime("$start_monday_piket + " . (($i - 1) * 7) . " days"));
    $w_friday = date('Y-m-d', strtotime("$w_monday + 4 days"));
    $week_start = date('d M', strtotime($w_monday));
    $week_end = date('d M Y', strtotime($w_friday));
    $is_cur = ($i == $default_minggu_piket) ? ' (Minggu Ini)' : '';
    $week_piket_options[$i] = "Minggu $i ($week_start - $week_end)$is_cur";
}

// Prepare SweetAlert configuration for response messages
$swalConfig = null;
if (isset($_SESSION['reminder_msg'])) {
    $swalConfig = [
        'icon' => (($_SESSION['reminder_type'] ?? '') === "success" ? "success" : (($_SESSION['reminder_type'] ?? '') === "error" ? "error" : "info")),
        'title' => (($_SESSION['reminder_type'] ?? '') === "success" ? "Berhasil!" : (($_SESSION['reminder_type'] ?? '') === "error" ? "Gagal!" : "Informasi")),
        'text' => $_SESSION['reminder_msg'],
        'confirmButtonColor' => '#3730a3',
        'showDenyButton' => (bool)($_SESSION['show_piket_button'] ?? false),
        'denyButtonText' => 'Kirim Piket Proyektor',
        'denyButtonColor' => '#312e81'
    ];
}
?>
<!DOCTYPE html>
<html lang="id">

<head>

    <title>Reminder - SIMAWA PTIK 2024</title>
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

        .dark .swal2-popup {
            background-color: #0f172a !important;
            color: #f8fafc !important;
            border: 1px solid #1e293b !important;
        }

        .dark .swal2-title,
        .dark .swal2-html-container {
            color: #f8fafc !important;
        }

        .dark .swal2-input,
        .dark .swal2-select,
        .dark .swal2-textarea {
            background-color: #1e293b !important;
            color: #f8fafc !important;
            border-color: #334155 !important;
        }

        .dark .swal2-confirm {
            background-color: #4f46e5 !important;
        }

        .dark .swal2-deny {
            background-color: #1e293b !important;
            border: 1px solid #334155 !important;
        }

        .dark .swal2-cancel {
            background-color: #0f172a !important;
            color: #94a3b8 !important;
            border: 1px solid #1e293b !important;
        }

        .dark .swal2-validation-message {
            background-color: #7f1d1d !important;
            color: #fecaca !important;
        }
    </style>

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>

<body class="bg-slate-100 dark:bg-slate-950 transition-colors duration-300">
    <?php include '../includes/menu_db.php'; ?>

    <main class="ml-0 lg:ml-48 pt-20 p-6 flex flex-col min-h-screen bg-indigo-950/5 dark:bg-slate-950 transition-colors duration-300">


        <div class="bg-white dark:bg-slate-900/50 rounded-xl shadow-sm border border-slate-200 dark:border-slate-800 p-6 mb-6">
            <div class="flex items-center gap-3">
                <div class="w-12 h-12 bg-green-500 rounded-xl flex items-center justify-center">
                    <i class="fab fa-whatsapp text-white text-2xl"></i>
                </div>
                <div>
                    <h2 class="text-lg font-bold text-slate-800 dark:text-white">Kirim Reminder ke WhatsApp</h2>
                    <p class="text-xs text-slate-500 dark:text-slate-400">Pilih jenis reminder yang ingin dikirim ke grup WhatsApp PTIK 2024</p>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">



            <!-- KRS Reminder -->
            <form method="POST" class="bg-white dark:bg-slate-900/50 rounded-xl shadow-sm border border-slate-200 dark:border-slate-800 p-5 hover:shadow-md transition">
                <input type="hidden" name="action" value="krs">
                <div class="flex items-start gap-4">
                    <div class="w-12 h-12 bg-purple-100 dark:bg-purple-900/30 text-purple-600 dark:text-purple-400 rounded-lg flex items-center justify-center flex-shrink-0 relative">
                        <i class="fa fa-university text-xl"></i>
                        <?php if ($krs_count > 0): ?>
                            <span class="absolute -top-1 -right-1 bg-purple-500 text-white text-[10px] font-bold w-5 h-5 rounded-full flex items-center justify-center"><?= $krs_count ?></span>
                        <?php endif; ?>
                    </div>
                    <div class="flex-grow">
                        <h3 class="font-bold text-slate-800 dark:text-slate-200 text-md">Reminder KRS</h3>
                        <p class="text-xs text-slate-500 dark:text-slate-400 mt-1 mb-3">Ingatkan mahasiswa yang belum mengisi KRS</p>
                        <button type="submit" class="w-full bg-purple-600 hover:bg-purple-700 text-white text-xs font-bold py-2 px-4 rounded-lg transition shadow-sm">
                            <i class="fab fa-whatsapp mr-1"></i> Kirim
                        </button>
                    </div>
                </div>
            </form>

            <!-- Tugas -->
            <form method="POST" class="bg-white dark:bg-slate-900/50 rounded-xl shadow-sm border border-slate-200 dark:border-slate-800 p-5 hover:shadow-md transition">
                <input type="hidden" name="action" value="tugas">
                <div class="flex items-start gap-4">
                    <div class="w-12 h-12 bg-orange-100 dark:bg-orange-900/30 text-orange-600 dark:text-orange-400 rounded-lg flex items-center justify-center flex-shrink-0 relative">
                        <i class="fa fa-tasks text-xl"></i>
                        <?php if ($tugas_count > 0): ?>
                            <span class="absolute -top-1 -right-1 bg-red-500 text-white text-[10px] font-bold w-5 h-5 rounded-full flex items-center justify-center"><?= $tugas_count ?></span>
                        <?php endif; ?>
                    </div>
                    <div class="flex-grow">
                        <h3 class="font-bold text-slate-800 dark:text-slate-200 text-md">Tugas Aktif</h3>
                        <p class="text-xs text-slate-500 dark:text-slate-400 mt-1 mb-3">Kirim rekap tugas yang belum dikumpulkan</p>
                        <button type="submit" class="w-full bg-orange-600 hover:bg-orange-700 text-white text-xs font-bold py-2 px-4 rounded-lg transition shadow-sm">
                            <i class="fab fa-whatsapp mr-1"></i> Kirim
                        </button>
                    </div>
                </div>
            </form>

            

            <!-- Jadwal -->
            <form id="formJadwal" method="POST" class="bg-white dark:bg-slate-900/50 rounded-xl shadow-sm border border-slate-200 dark:border-slate-800 p-5 hover:shadow-md transition">
                <input type="hidden" name="action" value="jadwal">
                <input type="hidden" name="day" id="jadwal_day" value="besok">
                <div class="flex items-start gap-4">
                    <div class="w-12 h-12 bg-indigo-100 dark:bg-indigo-900/30 text-indigo-600 dark:text-indigo-400 rounded-lg flex items-center justify-center flex-shrink-0">
                        <i class="fa fa-calendar-alt text-xl"></i>
                    </div>
                    <div class="flex-grow">
                        <h3 class="font-bold text-slate-800 dark:text-slate-200 text-md">Jadwal Kuliah</h3>
                        <p class="text-xs text-slate-500 dark:text-slate-400 mt-1 mb-3">Ingatkan jadwal kuliah hari ini atau besok</p>
                        <button type="button" onclick="confirmJadwal()" class="w-full bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-bold py-2 px-4 rounded-lg transition shadow-sm">
                            <i class="fab fa-whatsapp mr-1"></i> Kirim
                        </button>
                    </div>
                </div>
            </form>

            <!-- Kas -->
            <form method="POST" id="formKas" class="bg-white dark:bg-slate-900/50 rounded-xl shadow-sm border border-slate-200 dark:border-slate-800 p-5 hover:shadow-md transition">
                <input type="hidden" name="action" value="kas">
                <input type="hidden" name="minggu_ke" id="kas_minggu_ke" value="1">
                <div class="flex items-start gap-4">
                    <div class="w-12 h-12 bg-green-100 dark:bg-green-900/30 text-green-600 dark:text-green-400 rounded-lg flex items-center justify-center flex-shrink-0">
                        <i class="fa fa-wallet text-xl"></i>
                    </div>
                    <div class="flex-grow">
                        <h3 class="font-bold text-slate-800 dark:text-slate-200 text-md">Kas Mingguan</h3>
                        <p class="text-xs text-slate-500 dark:text-slate-400 mt-1 mb-3">Kirim pengingat uang kas yang belum lunas</p>
                        <button type="button" onclick="confirmKas()" class="w-full bg-green-600 hover:bg-green-700 text-white text-xs font-bold py-2 px-4 rounded-lg transition shadow-sm">
                            <i class="fab fa-whatsapp mr-1"></i> Kirim
                        </button>
                    </div>
                </div>
            </form>

            <!-- Piket proyektor -->
            <form method="POST" id="formPiket" class="bg-white dark:bg-slate-900/50 rounded-xl shadow-sm border border-slate-200 dark:border-slate-800 p-5 hover:shadow-md transition">
                <input type="hidden" name="action" value="piket">
                <input type="hidden" name="day" id="piket_day" value="besok">
                <input type="hidden" name="minggu_ke" id="piket_minggu_ke" value="">
                <div class="flex items-start gap-4">
                    <div class="w-12 h-12 bg-indigo-900 text-white rounded-lg flex items-center justify-center flex-shrink-0 relative">
                        <i class="fa fa-plug text-xl"></i>
                    </div>
                    <div class="flex-grow">
                        <h3 class="font-bold text-slate-800 dark:text-slate-200 text-md">Piket Proyektor</h3>
                        <p class="text-xs text-slate-500 dark:text-slate-400 mt-1 mb-3">Kirim pengingat petugas piket hari ini, besok, atau seluruh minggu</p>
                        <button type="button" onclick="confirmPiket()" class="w-full bg-indigo-900 hover:bg-black text-white text-xs font-bold py-2 px-4 rounded-lg transition shadow-sm">
                            <i class="fab fa-whatsapp mr-1"></i> Kirim
                        </button>
                    </div>
                </div>
            </form>







        </div>
        <?php include '../includes/footer_db.php'; ?>
    </main>


    <?php include '../includes/script_db.php'; ?>

    <script>
        // Global variables for custom schedule
        let currentJadwal = [];
        let optJam = [];
        let optRuang = [];

        function confirmKas() {
            Swal.fire({
                title: 'Pilih Minggu Kas',
                html: `
                    <div class="text-left">
                        <label class="block text-sm font-bold text-slate-700 dark:text-slate-300 mb-2">Minggu Ke-</label>
                        <select id="select_minggu" class="w-full px-3 py-2 border dark:border-slate-800 rounded-lg dark:bg-slate-900 dark:text-slate-200 focus:ring-2 focus:ring-green-500">
                            <?php for ($i = 1; $i <= 16; $i++): ?>
                                <option value="<?= $i ?>">Minggu <?= $i ?></option>
                            <?php endfor; ?>
                        </select>
                    </div>
                `,
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#16a34a',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Kirim',
                cancelButtonText: 'Batal',
                preConfirm: () => {
                    return document.getElementById('select_minggu').value;
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    document.getElementById('kas_minggu_ke').value = result.value;
                    document.getElementById('formKas').submit();
                }
            });
        }

        const weekPiketOptions = <?= json_encode($week_piket_options) ?>;
        const defaultMingguPiket = <?= json_encode($default_minggu_piket) ?>;

        function confirmPiket() {
            Swal.fire({
                title: 'Kirim Notifikasi Piket',
                text: "Pilih cakupan jadwal piket yang ingin dikirim ke WhatsApp:",
                input: 'select',
                inputOptions: {
                    'hari_ini': 'Piket Hari Ini',
                    'besok': 'Piket Besok',
                    'seluruh_minggu': 'Piket Seluruh Minggu'
                },
                inputPlaceholder: 'Pilih tipe pengingat...',
                showCancelButton: true,
                confirmButtonColor: '#312e81',
                confirmButtonText: 'Lanjutkan <i class="fa fa-arrow-right ml-1"></i>',
                cancelButtonText: 'Batal',
                inputValidator: (value) => {
                    if (!value) {
                        return 'Anda harus memilih salah satu opsi!'
                    }
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    if (result.value === 'seluruh_minggu' || result.value === 'minggu_ini') {
                        Swal.fire({
                            title: 'Pilih Minggu Perkuliahan',
                            text: 'Pilih minggu jadwal piket yang ingin dikirim:',
                            input: 'select',
                            inputOptions: weekPiketOptions,
                            inputValue: defaultMingguPiket || 1,
                            showCancelButton: true,
                            confirmButtonColor: '#312e81',
                            confirmButtonText: '<i class="fab fa-whatsapp"></i> Kirim Notifikasi',
                            cancelButtonText: 'Kembali',
                            inputValidator: (val) => {
                                if (!val) {
                                    return 'Silakan pilih minggu terlebih dahulu!'
                                }
                            }
                        }).then((weekResult) => {
                            if (weekResult.isConfirmed) {
                                document.getElementById('piket_day').value = 'seluruh_minggu';
                                document.getElementById('piket_minggu_ke').value = weekResult.value;
                                document.getElementById('formPiket').submit();
                            } else if (weekResult.dismiss === Swal.DismissReason.cancel) {
                                confirmPiket();
                            }
                        });
                    } else {
                        document.getElementById('piket_day').value = result.value;
                        document.getElementById('piket_minggu_ke').value = '';
                        document.getElementById('formPiket').submit();
                    }
                }
            });
        }

        function confirmJadwal() {
            Swal.fire({
                title: 'Kirim Jadwal Kuliah',
                text: "Pilih jadwal yang ingin dikirim ke WhatsApp PTIK 2024.",
                icon: 'question',
                showCancelButton: true,
                showDenyButton: true,
                confirmButtonColor: '#3085d6',
                denyButtonColor: '#10b981',
                cancelButtonColor: '#6b7280',
                confirmButtonText: 'Jadwal Besok',
                denyButtonText: 'Jadwal Hari Ini',
                cancelButtonText: 'Batal'
            }).then((result) => {
                let day = 'besok';
                if (result.isDenied) day = 'hari_ini';

                if (result.isConfirmed || result.isDenied) {
                    Swal.fire({
                        title: 'Metode Pengiriman',
                        text: `Pilih metode pengiriman untuk jadwal ${day === 'hari_ini' ? 'hari ini' : 'besok'}.`,
                        icon: 'info',
                        showCancelButton: true,
                        showDenyButton: true,
                        confirmButtonColor: '#3085d6',
                        denyButtonColor: '#f59e0b',
                        confirmButtonText: 'Kirim Langsung',
                        denyButtonText: 'Sesuaikan Jadwal',
                        cancelButtonText: 'Kembali'
                    }).then((methodResult) => {
                        if (methodResult.isConfirmed) {
                            document.getElementById('jadwal_day').value = day;
                            document.getElementById('formJadwal').submit();
                        } else if (methodResult.isDenied) {
                            fetchDataAndShowList(day);
                        }
                    });
                }
            });
        }

        function fetchDataAndShowList(day) {
            Swal.showLoading();
            window.lastSelectedDay = day;
            fetch(`?fetch_adjust_data=1&day=${day}`)
                .then(r => r.json())
                .then(data => {
                    currentJadwal = data.jadwal;
                    optJam = data.opt_jam;
                    optRuang = data.opt_ruang;
                    optMk = data.opt_mk;
                    renderJadwalList(data.tomorrow, data.date);
                })
                .catch(err => {
                    console.error(err);
                    Swal.fire('Error', 'Gagal mengambil data jadwal', 'error');
                });
        }

        function renderJadwalList(dayName, dateStr) {
            if (dayName) window.lastDayName = dayName;
            if (dateStr) window.lastDateStr = dateStr;
            dayName = dayName || window.lastDayName;
            dateStr = dateStr || window.lastDateStr;

            let html = `<div class="text-left text-sm mb-4 flex justify-between items-center">
                <span>Hari: <b>${dayName}</b>, ${dateStr}</span>
                <button type="button" onclick="addNewMatkul()" class="bg-indigo-600 hover:bg-indigo-700 text-white text-[10px] font-bold px-3 py-1.5 rounded-lg transition flex items-center gap-1.5">
                    <i class="fas fa-plus"></i> Tambah Matkul
                </button>
            </div>`;
            if (currentJadwal.length === 0) {
                html += '<p class="text-slate-400 dark:text-slate-600 text-xs text-center py-8">Belum ada jadwal yang ditambahkan.</p>';
            } else {
                html += `<div class="overflow-x-auto max-h-64 border border-slate-200 dark:border-slate-800 rounded-lg bg-slate-50 dark:bg-slate-800/50">
                    <table class="w-full text-xs text-left">
                        <thead class="bg-indigo-900 text-white border-b dark:border-slate-800 sticky top-0">
                            <tr>
                                <th class="p-2 w-8 text-center"><i class="fas fa-check-circle"></i></th>
                                <th class="p-2">Mata Kuliah</th>
                                <th class="p-2">Jam & Ruangan</th>
                                <th class="p-2 w-16 text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y dark:divide-slate-800 bg-white dark:bg-slate-950">`;
 
                currentJadwal.forEach((item, index) => {
                    const checked = item.checked ? 'checked' : '';
                    const isAbsent = item.status === 'tidak_masuk';
                    const statusBadge = isAbsent ? '<span class="text-[8px] bg-red-100 dark:bg-red-900/30 text-red-600 dark:text-red-400 px-1.5 py-0.5 rounded-md ml-1.5 font-bold uppercase tracking-tighter">Tidak Masuk</span>' : '';
                    const detailText = isAbsent ? '-' : `${item.jam_text} (${item.ruangan_text})`;
                    html += `<tr class="${!item.checked ? 'opacity-50' : ''} dark:border-slate-800">
                        <td class="p-2 text-center"><input type="checkbox" onchange="toggleCheck(${index})" ${checked} class="rounded border-slate-300 dark:border-slate-700 dark:bg-slate-800 text-indigo-600 focus:ring-indigo-500"></td>
                        <td class="p-2">
                            <div class="font-bold text-slate-800 dark:text-slate-200">${item.matkul}</div>
                            ${statusBadge}
                        </td>
                        <td class="p-2 text-slate-500 dark:text-slate-400 font-medium">${detailText}</td>
                        <td class="p-2 text-center">
                            <div class="flex gap-1 justify-center">
                                <button type="button" onclick="showEditForm(${index})" class="p-1.5 text-blue-600 hover:bg-blue-50 dark:hover:bg-blue-900/30 rounded transition"><i class="fas fa-edit"></i></button>
                                <button type="button" onclick="removeItem(${index})" class="p-1.5 text-red-600 hover:bg-red-50 dark:hover:bg-red-900/30 rounded transition"><i class="fas fa-trash"></i></button>
                            </div>
                        </td>
                    </tr>`;
                });

                html += `</tbody></table></div>`;
            }

            Swal.fire({
                title: 'Sesuaikan Jadwal',
                html: html,
                width: '650px',
                showCancelButton: true,
                confirmButtonText: '<i class="fab fa-whatsapp mr-1.5"></i> Kirim WhatsApp',
                cancelButtonText: 'Kembali',
                confirmButtonColor: '#4f46e5',
                customClass: {
                    confirmButton: 'text-xs px-6 py-2.5 rounded-lg font-bold uppercase tracking-wider',
                    cancelButton: 'text-xs px-6 py-2.5 rounded-lg font-bold uppercase tracking-wider'
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    submitCustomJadwal();
                }
            });
        }

        function toggleCheck(index) {
            currentJadwal[index].checked = !currentJadwal[index].checked;
            renderJadwalList();
        }

        function removeItem(index) {
            currentJadwal.splice(index, 1);
            renderJadwalList();
        }

        function addNewMatkul() {
            let mkSelect = `<select id="new_matkul" class="w-full px-3 py-2 border border-slate-200 dark:border-slate-800 rounded-xl text-sm mb-4 bg-white dark:bg-slate-900 text-slate-800 dark:text-slate-200 focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-600 transition-all font-medium">`;
            mkSelect += `<option value="" disabled selected>-- Pilih Mata Kuliah --</option>`;
            optMk.forEach(opt => {
                mkSelect += `<option value="${opt.nama}">${opt.nama}</option>`;
            });
            mkSelect += `</select>`;

            let ruangSelect = `<select id="new_ruang" class="w-full px-3 py-2 border border-slate-200 dark:border-slate-800 rounded-xl text-sm bg-white dark:bg-slate-900 text-slate-800 dark:text-slate-200 focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-600 transition-all font-medium">`;
            ruangSelect += `<option value="" disabled selected>-- Pilih Ruangan --</option>`;
            optRuang.forEach(opt => {
                ruangSelect += `<option value="${opt.val}">${opt.label}</option>`;
            });
            ruangSelect += `</select>`;

            Swal.fire({
                title: 'Tambah Mata Kuliah',
                html: `
                    <div class="text-left space-y-4">
                        <div>
                            <label class="block text-[10px] font-bold text-slate-400 dark:text-slate-500 uppercase tracking-widest mb-1.5 ml-1">Pilih Mata Kuliah</label>
                            ${mkSelect}
                        </div>
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-[10px] font-bold text-slate-400 dark:text-slate-500 uppercase tracking-widest mb-1.5 ml-1">Jam Mulai</label>
                                <input type="time" id="new_jam_mulai" class="w-full px-3 py-2 border border-slate-200 dark:border-slate-800 rounded-xl text-sm dark:bg-slate-900 dark:text-slate-200 focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-600 transition-all font-medium">
                            </div>
                            <div>
                                <label class="block text-[10px] font-bold text-slate-400 dark:text-slate-500 uppercase tracking-widest mb-1.5 ml-1">Jam Selesai</label>
                                <input type="time" id="new_jam_selesai" class="w-full px-3 py-2 border border-slate-200 dark:border-slate-800 rounded-xl text-sm dark:bg-slate-900 dark:text-slate-200 focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-600 transition-all font-medium">
                            </div>
                        </div>
                        <div>
                            <label class="block text-[10px] font-bold text-slate-400 dark:text-slate-500 uppercase tracking-widest mb-1.5 ml-1">Pilih Ruangan</label>
                            ${ruangSelect}
                        </div>
                    </div>
                `,
                showCancelButton: true,
                confirmButtonText: 'Tambah',
                cancelButtonText: 'Batal',
                confirmButtonColor: '#4f46e5',
                preConfirm: () => {
                    const matkul = document.getElementById('new_matkul').value;
                    const jamMulai = document.getElementById('new_jam_mulai').value;
                    const jamSelesai = document.getElementById('new_jam_selesai').value;
                    const ruangan = document.getElementById('new_ruang').value;

                    if (!matkul || !jamMulai || !jamSelesai || !ruangan) {
                        Swal.showValidationMessage('Semua field harus diisi!');
                        return false;
                    }

                    const jamText = jamMulai.replace(':', '.') + ' - ' + jamSelesai.replace(':', '.');

                    return {
                        matkul,
                        jam_text: jamText,
                        ruangan_text: ruangan,
                        checked: true,
                        status: 'masuk'
                    };
                }
            }).then((res) => {
                if (res.isConfirmed) {
                    currentJadwal.push(res.value);
                    renderJadwalList();
                } else {
                    renderJadwalList();
                }
            });
        }

        function showEditForm(index) {
            const item = currentJadwal[index];

            // Parse existing time if available
            let jamMulai = '07:30';
            let jamSelesai = '09:10';
            if (item.jam_text && item.jam_text.includes(' - ')) {
                const parts = item.jam_text.split(' - ');
                jamMulai = parts[0].replace('.', ':');
                jamSelesai = parts[1].replace('.', ':');
            }

            const currentStatus = item.status || 'masuk';

            // Generate Ruangan Options
            let ruangHtml = `<select id="edit_ruang" class="w-full px-3 py-2.5 border border-slate-200 dark:border-slate-800 rounded-xl text-sm bg-white dark:bg-slate-900 text-slate-800 dark:text-slate-200 focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-600 transition-all font-medium">`;
            optRuang.forEach(opt => {
                const selected = (opt.val === item.ruangan_text) ? 'selected' : '';
                ruangHtml += `<option value="${opt.val}" ${selected}>${opt.label}</option>`;
            });
            ruangHtml += `</select>`;

            Swal.fire({
                title: 'Edit Item Jadwal',
                html: `
                    <div class="text-left space-y-4">
                        <div>
                            <label class="block text-[10px] font-bold text-slate-400 dark:text-slate-500 uppercase tracking-widest mb-1.5 ml-1">Status Kehadiran</label>
                            <select id="edit_status" class="w-full px-3 py-2.5 border border-slate-200 dark:border-slate-800 rounded-xl text-sm bg-white dark:bg-slate-900 text-slate-800 dark:text-slate-200 focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-600 transition-all font-medium" onchange="toggleEditFields(this.value)">
                                <option value="masuk" ${currentStatus === 'masuk' ? 'selected' : ''}>Masuk</option>
                                <option value="tidak_masuk" ${currentStatus === 'tidak_masuk' ? 'selected' : ''}>Tidak Masuk (Absen)</option>
                            </select>
                        </div>
                        
                        <div id="time_fields_container" class="${currentStatus === 'tidak_masuk' ? 'hidden' : 'space-y-4'}">
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-[10px] font-bold text-slate-400 dark:text-slate-500 uppercase tracking-widest mb-1.5 ml-1">Jam Mulai</label>
                                    <input type="time" id="edit_jam_mulai" value="${jamMulai}" class="w-full px-3 py-2.5 border border-slate-200 dark:border-slate-800 rounded-xl text-sm dark:bg-slate-900 dark:text-slate-200 focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-600 transition-all font-medium">
                                </div>
                                <div>
                                    <label class="block text-[10px] font-bold text-slate-400 dark:text-slate-500 uppercase tracking-widest mb-1.5 ml-1">Jam Selesai</label>
                                    <input type="time" id="edit_jam_selesai" value="${jamSelesai}" class="w-full px-3 py-2.5 border border-slate-200 dark:border-slate-800 rounded-xl text-sm dark:bg-slate-900 dark:text-slate-200 focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-600 transition-all font-medium">
                                </div>
                            </div>
                            
                            <div>
                                <label class="block text-[10px] font-bold text-slate-400 dark:text-slate-500 uppercase tracking-widest mb-1.5 ml-1">Pilih Ruangan Kuliah</label>
                                ${ruangHtml}
                            </div>
                        </div>
                    </div>
                `,
                showCancelButton: true,
                confirmButtonText: 'Simpan Perubahan',
                cancelButtonText: 'Batal',
                confirmButtonColor: '#4f46e5',
                preConfirm: () => {
                    const status = document.getElementById('edit_status').value;
                    let jamText = item.jam_text;
                    let ruangText = item.ruangan_text;

                    if (status === 'masuk') {
                        const jamMulaiVal = document.getElementById('edit_jam_mulai').value;
                        const jamSelesaiVal = document.getElementById('edit_jam_selesai').value;
                        jamText = jamMulaiVal.replace(':', '.') + ' - ' + jamSelesaiVal.replace(':', '.');
                        ruangText = document.getElementById('edit_ruang').value;
                    } else {
                        // If 'tidak_masuk', clear or set default for jam and ruang
                        jamText = '-'; // Or any other placeholder
                        ruangText = '-'; // Or any other placeholder
                    }

                    return {
                        jam: jamText,
                        ruang: ruangText,
                        status: status
                    };
                }
            }).then((res) => {
                if (res.isConfirmed) {
                    currentJadwal[index].jam_text = res.value.jam;
                    currentJadwal[index].ruangan_text = res.value.ruang;
                    currentJadwal[index].status = res.value.status;
                }
                renderJadwalList();
            });
        }

        function toggleEditFields(status) {
            const container = document.getElementById('time_fields_container');
            if (status === 'tidak_masuk') {
                container.classList.add('hidden');
                container.classList.remove('space-y-4');
            } else {
                container.classList.remove('hidden');
                container.classList.add('space-y-4');
            }
        }

        function submitCustomJadwal() {
            const selectedItems = currentJadwal.filter(i => i.checked).map(i => ({
                matkul: i.matkul,
                jam: i.jam_text,
                ruangan: i.ruangan_text,
                status: i.status || 'masuk'
            }));

            if (selectedItems.length === 0) {
                Swal.fire('Peringatan', 'Pilih minimal satu jadwal untuk dikirim.', 'warning')
                    .then(() => renderJadwalList());
                return;
            }

            const form = document.createElement('form');
            form.method = 'POST';
            form.action = 'reminder';

            const inputAction = document.createElement('input');
            inputAction.type = 'hidden';
            inputAction.name = 'action';
            inputAction.value = 'jadwal_custom';
            form.appendChild(inputAction);

            const inputDay = document.createElement('input');
            inputDay.type = 'hidden';
            inputDay.name = 'day';
            inputDay.value = window.lastSelectedDay;
            form.appendChild(inputDay);

            const inputPayload = document.createElement('input');
            inputPayload.type = 'hidden';
            inputPayload.name = 'custom_payload';
            inputPayload.value = JSON.stringify(selectedItems);
            form.appendChild(inputPayload);

            document.body.appendChild(form);
            form.submit();
        }

        function submitPiketCustom(payload, day) {
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = 'reminder';

            const inputAction = document.createElement('input');
            inputAction.type = 'hidden';
            inputAction.name = 'action';
            inputAction.value = 'piket';
            form.appendChild(inputAction);

            const inputDay = document.createElement('input');
            inputDay.type = 'hidden';
            inputDay.name = 'day';
            inputDay.value = day;
            form.appendChild(inputDay);

            const inputPayload = document.createElement('input');
            inputPayload.type = 'hidden';
            inputPayload.name = 'custom_payload';
            inputPayload.value = payload;
            form.appendChild(inputPayload);

            document.body.appendChild(form);
            form.submit();
        }

        // Response Message with SweetAlert
        <?php if ($swalConfig): ?>
            Swal.fire(<?= json_encode($swalConfig) ?>).then((result) => {
                <?php if (isset($_SESSION['show_piket_button']) && $_SESSION['show_piket_button']): ?>
                if (result.isDenied) {
                    submitPiketCustom('<?= addslashes($_SESSION['last_custom_payload'] ?? '') ?>', '<?= $_SESSION['last_day'] ?? '' ?>');
                }
                <?php endif; ?>
            });
            <?php
            unset($_SESSION['reminder_msg']);
            unset($_SESSION['reminder_type']);
            unset($_SESSION['show_piket_button']);
            unset($_SESSION['last_custom_payload']);
            unset($_SESSION['last_day']);
            ?>
        <?php endif; ?>
    </script>
</body>

</html>