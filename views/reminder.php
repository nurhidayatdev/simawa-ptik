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

if ($_SESSION['role'] === 'Mahasiswa' || $_SESSION['role'] === 'Bendahara' || $_SESSION['role'] === 'Sekretaris') {
    include '../includes/alert_role.php';
    exit();
}

date_default_timezone_set('Asia/Makassar');

// Telegram Config
$telegram_token = "8212322367:AAGCGc4TG__EnsEd-20wFTI_s2adp-dp1OY";
$telegram_chat_id = "-1003577478223";

// Include reminder functions
include_once '../functions/helpers.php';
include_once '../functions/kirim_jadwal.php';
include_once '../functions/kirim_tugas.php';
include_once '../functions/kirim_kas.php';
include_once '../functions/kirim_streak.php';
include_once '../functions/kirim_krs.php';
include_once '../functions/kirim_kelompok.php';

// Handle Actions (PRG Pattern)
if (isset($_POST['action'])) {
    $action = $_POST['action'];
    $response = ['status' => 'error', 'message' => 'Aksi tidak dikenal'];

    if ($action === 'jadwal') {
        $response = kirimJadwalReminder($koneksi, $telegram_token, $telegram_chat_id);
    } elseif ($action === 'tugas') {
        $response = kirimTugasReminder($koneksi, $telegram_token, $telegram_chat_id);
    } elseif ($action === 'kas') {
        $minggu_ke = (int)($_POST['minggu_ke'] ?? 1);
        $response = kirimKasReminder($koneksi, $telegram_token, $telegram_chat_id, $minggu_ke);
    } elseif ($action === 'streak') {
        $response = kirimStreakReminder($koneksi, $telegram_token, $telegram_chat_id);
    } elseif ($action === 'krs') {
        $response = kirimKrsReminder($koneksi, $telegram_token, $telegram_chat_id);
    } elseif ($action === 'kelompok') {
        $matkul_id = $_POST['matkul_id'];
        $dosen = $_POST['dosen'];
        $keterangan = $_POST['keterangan'];
        $response = kirimKelompokReminder($koneksi, $telegram_token, $telegram_chat_id, $matkul_id, $dosen, $keterangan);
    }

    $_SESSION['reminder_msg'] = $response['message'];
    $_SESSION['reminder_type'] = $response['status'];
    header("Location: reminder");
    exit;
}


// Counts for display
$now_date = date('Y-m-d');
$now_time = date('H:i:s');
$yesterday_date = date('Y-m-d', strtotime('-1 day'));
$tugas_count = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT COUNT(*) as cnt FROM tugas WHERE status='belum' AND (deadline_tanggal > '$now_date' OR (deadline_tanggal = '$now_date' AND deadline_pukul > '$now_time'))"))['cnt'] ?? 0;
$streak_count = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT COUNT(*) as cnt FROM kuis_harian_stats WHERE current_streak > 0 AND (last_played_date = '$now_date' OR last_played_date = '$yesterday_date') AND (last_completed_at IS NULL OR DATE(last_completed_at) != '$now_date')"))['cnt'] ?? 0;

$q_p = mysqli_query($koneksi, "SELECT id FROM periode WHERE status = 'aktif' LIMIT 1");
$p_id = mysqli_fetch_assoc($q_p)['id'] ?? 0;
$krs_count = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT COUNT(*) as cnt FROM mahasiswa m LEFT JOIN (SELECT DISTINCT mahasiswa_id FROM krs WHERE periode_id = $p_id) k ON m.id = k.mahasiswa_id WHERE k.mahasiswa_id IS NULL"))['cnt'] ?? 0;

// Fetch unique group sets
$q_sets = mysqli_query($koneksi, "
    SELECT DISTINCT 
        k.matkul_id, 
        mk.nama_matkul, 
        k.dosen, 
        k.keterangan 
    FROM kelompok k 
    JOIN mata_kuliah mk ON k.matkul_id = mk.id 
    WHERE k.periode_id = $p_id
    ORDER BY mk.nama_matkul ASC, k.dosen ASC
");
$group_sets = [];
while ($row = mysqli_fetch_assoc($q_sets)) {
    $group_sets[] = $row;
}
?>
<!DOCTYPE html>
<html lang="id">

<head>

    <title>Reminder - PTIK C 2024</title>
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

<body class="bg-slate-100">
    <?php include '../includes/menu_db.php'; ?>

    <main class="ml-0 lg:ml-48 pt-20 p-6 flex flex-col min-h-screen">


        <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6 mb-6">
            <div class="flex items-center gap-3">
                <div class="w-12 h-12 bg-blue-500 rounded-xl flex items-center justify-center">
                    <i class="fab fa-telegram text-white text-2xl"></i>
                </div>
                <div>
                    <h2 class="text-lg font-bold text-slate-800">Kirim Reminder ke Telegram</h2>
                    <p class="text-xs text-slate-500">Pilih jenis reminder yang ingin dikirim ke grup PTIK C</p>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">



            <!-- KRS Reminder -->
            <form method="POST" class="bg-white rounded-xl shadow-sm border border-slate-200 p-5 hover:shadow-md transition">
                <input type="hidden" name="action" value="krs">
                <div class="flex items-start gap-4">
                    <div class="w-12 h-12 bg-purple-100 text-purple-600 rounded-lg flex items-center justify-center flex-shrink-0 relative">
                        <i class="fa fa-university text-xl"></i>
                        <?php if ($krs_count > 0): ?>
                            <span class="absolute -top-1 -right-1 bg-purple-500 text-white text-[10px] font-bold w-5 h-5 rounded-full flex items-center justify-center"><?= $krs_count ?></span>
                        <?php endif; ?>
                    </div>
                    <div class="flex-grow">
                        <h3 class="font-bold text-slate-800 text-md">Reminder KRS</h3>
                        <p class="text-xs text-slate-500 mt-1 mb-3">Ingatkan mahasiswa yang belum mengisi KRS</p>
                        <button type="submit" class="w-full bg-purple-600 hover:bg-purple-700 text-white text-xs font-bold py-2 px-4 rounded-lg transition">
                            <i class="fab fa-telegram mr-1"></i> Kirim
                        </button>
                    </div>
                </div>
            </form>

            <!-- Tugas -->
            <form method="POST" class="bg-white rounded-xl shadow-sm border border-slate-200 p-5 hover:shadow-md transition">
                <input type="hidden" name="action" value="tugas">
                <div class="flex items-start gap-4">
                    <div class="w-12 h-12 bg-orange-100 text-orange-600 rounded-lg flex items-center justify-center flex-shrink-0 relative">
                        <i class="fa fa-tasks text-xl"></i>
                        <?php if ($tugas_count > 0): ?>
                            <span class="absolute -top-1 -right-1 bg-red-500 text-white text-[10px] font-bold w-5 h-5 rounded-full flex items-center justify-center"><?= $tugas_count ?></span>
                        <?php endif; ?>
                    </div>
                    <div class="flex-grow">
                        <h3 class="font-bold text-slate-800 text-md">Tugas Aktif</h3>
                        <p class="text-xs text-slate-500 mt-1 mb-3">Kirim rekap tugas yang belum dikumpulkan</p>
                        <button type="submit" class="w-full bg-orange-600 hover:bg-orange-700 text-white text-xs font-bold py-2 px-4 rounded-lg transition">
                            <i class="fab fa-telegram mr-1"></i> Kirim
                        </button>
                    </div>
                </div>
            </form>

            <!-- Streak Kuis Harian -->
            <form method="POST" class="bg-white rounded-xl shadow-sm border border-slate-200 p-5 hover:shadow-md transition">
                <input type="hidden" name="action" value="streak">
                <div class="flex items-start gap-4">
                    <div class="w-12 h-12 bg-red-100 text-red-500 rounded-lg flex items-center justify-center flex-shrink-0 relative">
                        <i class="fa fa-fire text-xl"></i>
                        <?php if ($streak_count > 0): ?>
                            <span class="absolute -top-1 -right-1 bg-orange-500 text-white text-[10px] font-bold w-5 h-5 rounded-full flex items-center justify-center"><?= $streak_count ?></span>
                        <?php endif; ?>
                    </div>
                    <div class="flex-grow">
                        <h3 class="font-bold text-slate-800 text-md">Streak Kuis Harian</h3>
                        <p class="text-xs text-slate-500 mt-1 mb-3">Ingatkan pemain yang belum main hari ini</p>
                        <button type="submit" class="w-full bg-red-500 hover:bg-red-600 text-white text-xs font-bold py-2 px-4 rounded-lg transition">
                            <i class="fab fa-telegram mr-1"></i> Kirim
                        </button>
                    </div>
                </div>
            </form>

            <!-- Jadwal -->
            <form method="POST" class="bg-white rounded-xl shadow-sm border border-slate-200 p-5 hover:shadow-md transition">
                <input type="hidden" name="action" value="jadwal">
                <div class="flex items-start gap-4">
                    <div class="w-12 h-12 bg-indigo-100 text-indigo-600 rounded-lg flex items-center justify-center flex-shrink-0">
                        <i class="fa fa-calendar-alt text-xl"></i>
                    </div>
                    <div class="flex-grow">
                        <h3 class="font-bold text-slate-800 text-md">Jadwal Besok</h3>
                        <p class="text-xs text-slate-500 mt-1 mb-3">Ingatkan jadwal kuliah untuk hari besok</p>
                        <button type="submit" class="w-full bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-bold py-2 px-4 rounded-lg transition">
                            <i class="fab fa-telegram mr-1"></i> Kirim
                        </button>
                    </div>
                </div>
            </form>

            <!-- Kas -->
            <form method="POST" class="bg-white rounded-xl shadow-sm border border-slate-200 p-5 hover:shadow-md transition">
                <input type="hidden" name="action" value="kas">
                <div class="flex items-start gap-4">
                    <div class="w-12 h-12 bg-green-100 text-green-600 rounded-lg flex items-center justify-center flex-shrink-0">
                        <i class="fa fa-wallet text-xl"></i>
                    </div>
                    <div class="flex-grow">
                        <h3 class="font-bold text-slate-800 text-md">Kas Mingguan</h3>
                        <p class="text-xs text-slate-500 mt-1 mb-2">Kirim pengingat kas belum lunas</p>
                        <div class="flex gap-2 mb-3">
                            <select name="minggu_ke" class="flex-grow text-xs border border-slate-300 rounded-lg px-3 py-1.5 focus:ring-2 focus:ring-green-500">
                                <?php for ($i = 1; $i <= 16; $i++): ?>
                                    <option value="<?= $i ?>">Minggu <?= $i ?></option>
                                <?php endfor; ?>
                            </select>
                        </div>
                        <button type="submit" class="w-full bg-green-600 hover:bg-green-700 text-white text-xs font-bold py-2 px-4 rounded-lg transition">
                            <i class="fab fa-telegram mr-1"></i> Kirim
                        </button>
                    </div>
                </div>
            </form>





            <!-- Kelompok Reminder -->
            <form method="POST" class="bg-white rounded-xl shadow-sm border border-slate-200 p-5 hover:shadow-md transition">
                <input type="hidden" name="action" value="kelompok">
                <div class="flex items-start gap-4">
                    <div class="w-12 h-12 bg-teal-100 text-teal-600 rounded-lg flex items-center justify-center flex-shrink-0">
                        <i class="fa fa-users text-xl"></i>
                    </div>
                    <div class="flex-grow">
                        <h3 class="font-bold text-slate-800">Daftar Kelompok</h3>
                        <p class="text-xs text-slate-500 mt-1 mb-2">Kirim pembagian kelompok</p>

                        <div class="mb-3">
                            <select name="group_set" required class="w-full text-xs border border-slate-300 rounded-lg px-2 py-1.5 focus:ring-2 focus:ring-teal-500" onchange="updateGroupFields(this)">
                                <option value="">-- Pilih Matkul & Dosen --</option>
                                <?php foreach ($group_sets as $s): ?>
                                    <option value="<?= $s['matkul_id'] ?>|<?= htmlspecialchars($s['dosen']) ?>|<?= htmlspecialchars($s['keterangan']) ?>">
                                        <?= htmlspecialchars($s['nama_matkul']) ?> - <?= htmlspecialchars($s['dosen']) ?> (<?= htmlspecialchars($s['keterangan']) ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <input type="hidden" name="matkul_id" id="field_matkul_id">
                            <input type="hidden" name="dosen" id="field_dosen">
                            <input type="hidden" name="keterangan" id="field_keterangan">
                        </div>

                        <button type="submit" class="w-full bg-teal-600 hover:bg-teal-700 text-white text-xs font-bold py-2 px-4 rounded-lg transition">
                            <i class="fab fa-telegram mr-1"></i> Kirim
                        </button>
                    </div>
                </div>
            </form>

        </div>
        <?php include '../includes/footer_db.php'; ?>
    </main>


    <?php include '../includes/script_db.php'; ?>

    <script>
        function updateGroupFields(select) {
            const val = select.value;
            if (val) {
                const parts = val.split('|');
                document.getElementById('field_matkul_id').value = parts[0];
                document.getElementById('field_dosen').value = parts[1];
                document.getElementById('field_keterangan').value = parts[2];
            } else {
                document.getElementById('field_matkul_id').value = '';
                document.getElementById('field_dosen').value = '';
                document.getElementById('field_keterangan').value = '';
            }
        }

        // Response Message with SweetAlert
        <?php if (isset($_SESSION['reminder_msg'])): ?>
            Swal.fire({
                icon: '<?= $_SESSION['reminder_type'] === "success" ? "success" : ($_SESSION['reminder_type'] === "error" ? "error" : "info") ?>',
                title: '<?= $_SESSION['reminder_type'] === "success" ? "Berhasil!" : ($_SESSION['reminder_type'] === "error" ? "Gagal!" : "Informasi") ?>',
                text: '<?= addslashes($_SESSION['reminder_msg']) ?>',
                confirmButtonColor: '#3730a3'
            });
            <?php
            unset($_SESSION['reminder_msg']);
            unset($_SESSION['reminder_type']);
            ?>
        <?php endif; ?>
    </script>
</body>

</html>