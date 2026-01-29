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

// AJAX Handler for "Sesuaikan Jadwal" Data Fetching
if (isset($_GET['fetch_adjust_data'])) {
    header('Content-Type: application/json');
    date_default_timezone_set('Asia/Makassar');

    $days_map = ['Sunday' => 'Minggu', 'Monday' => 'Senin', 'Tuesday' => 'Selasa', 'Wednesday' => 'Rabu', 'Thursday' => 'Kamis', 'Friday' => 'Jumat', 'Saturday' => 'Sabtu'];
    $tomorrow_eng = date('l', strtotime('+1 day'));
    $tomorrow_ind = $days_map[$tomorrow_eng];

    // 1. Get Tomorrow's Schedule
    $q_p = mysqli_query($koneksi, "SELECT id FROM periode WHERE status = 'aktif' LIMIT 1");
    $p_id = mysqli_fetch_assoc($q_p)['id'] ?? 0;

    $query_jadwal = "SELECT jm.id, mk.nama_matkul, 
                     jk_start.jam_mulai, jk_end.jam_selesai,
                     r.nama_ruangan, r.id as ruangan_id
                     FROM jadwal_matkul jm
                     JOIN mata_kuliah mk ON jm.matkul_id = mk.id
                     LEFT JOIN ruangan r ON jm.ruangan_id = r.id
                     LEFT JOIN jam_kuliah jk_start ON jm.jam_mulai_ke = jk_start.jam_ke
                     LEFT JOIN jam_kuliah jk_end ON jm.jam_selesai_ke = jk_end.jam_ke
                     WHERE jm.periode_id = $p_id AND jm.hari = '$tomorrow_ind'
                     ORDER BY jk_start.jam_mulai ASC";

    $res_jadwal = mysqli_query($koneksi, $query_jadwal);
    $jadwal = [];
    while ($row = mysqli_fetch_assoc($res_jadwal)) {
        // Format jam display (e.g. 07.30 - 09.10)
        $jam_display = date('H.i', strtotime($row['jam_mulai'])) . ' - ' . date('H.i', strtotime($row['jam_selesai']));
        $jadwal[] = [
            'id' => $row['id'],
            'matkul' => $row['nama_matkul'],
            'jam_text' => $jam_display,
            'ruangan_text' => $row['nama_ruangan'],
            'ruangan_id' => $row['ruangan_id'],
            'checked' => true // default checked
        ];
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
            'val' => $row['nama_ruangan'], // We send text to telegram, so value is content
            'label' => $row['nama_ruangan']
        ];
    }

    echo json_encode([
        'tomorrow' => $tomorrow_ind,
        'date' => date('d M Y', strtotime('+1 day')),
        'jadwal' => $jadwal,
        'opt_jam' => $opts_jam,
        'opt_ruang' => $opts_ruang
    ]);
    exit;
}

if ($_SESSION['role'] === 'Mahasiswa') {
    include '../includes/alert_role.php';
    exit();
}

date_default_timezone_set('Asia/Makassar');

// Fonnte Config (Moved to helpers.php)
include_once '../functions/helpers.php';


// Include reminder functions
include_once '../functions/helpers.php';
include_once '../functions/kirim_jadwal.php';
include_once '../functions/kirim_tugas.php';
include_once '../functions/kirim_kas.php';
include_once '../functions/kirim_streak.php';
include_once '../functions/kirim_krs.php';
include_once '../functions/kirim_kelompok.php';
include_once '../functions/kirim_piket_reminder.php';

// Handle Actions (PRG Pattern)
if (isset($_POST['action'])) {
    $action = $_POST['action'];
    $response = ['status' => 'error', 'message' => 'Aksi tidak dikenal'];

    if ($action === 'jadwal') {
        $response = kirimJadwalReminder($koneksi);
    } elseif ($action === 'jadwal_custom') {
        // Handle custom data via JSON payload
        if (isset($_POST['custom_payload'])) {
            $custom_data = json_decode($_POST['custom_payload'], true);
        } else {
            $custom_data = [[
                'matkul' => $_POST['matkul'] ?? '',
                'jam' => $_POST['jam'] ?? '',
                'ruangan' => $_POST['ruangan'] ?? ''
            ]];
        }
        $response = kirimJadwalReminder($koneksi, null, null, $custom_data);
    } elseif ($action === 'tugas') {
        $response = kirimTugasReminder($koneksi);
    } elseif ($action === 'kas') {
        $minggu_ke = (int)($_POST['minggu_ke'] ?? 1);
        $response = kirimKasReminder($koneksi, $minggu_ke);
    } elseif ($action === 'streak') {
        $response = kirimStreakReminder($koneksi);
    } elseif ($action === 'krs') {
        $response = kirimKrsReminder($koneksi);
    } elseif ($action === 'kelompok') {
        $matkul_id = $_POST['matkul_id'];
        $dosen = $_POST['dosen'];
        $keterangan = $_POST['keterangan'];
        $response = kirimKelompokReminder($koneksi, $matkul_id, $dosen, $keterangan);
    } elseif ($action === 'piket') {
        $response = kirimPiketReminder($koneksi);
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
                <div class="w-12 h-12 bg-green-500 rounded-xl flex items-center justify-center">
                    <i class="fab fa-whatsapp text-white text-2xl"></i>
                </div>
                <div>
                    <h2 class="text-lg font-bold text-slate-800">Kirim Reminder ke WhatsApp</h2>
                    <p class="text-xs text-slate-500">Pilih jenis reminder yang ingin dikirim ke grup WhatsApp PTIK C</p>
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
                            <i class="fab fa-whatsapp mr-1"></i> Kirim
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
                            <i class="fab fa-whatsapp mr-1"></i> Kirim
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
                            <i class="fab fa-whatsapp mr-1"></i> Kirim
                        </button>
                    </div>
                </div>
            </form>

            <!-- Jadwal -->
            <form id="formJadwal" method="POST" class="bg-white rounded-xl shadow-sm border border-slate-200 p-5 hover:shadow-md transition">
                <input type="hidden" name="action" value="jadwal">
                <div class="flex items-start gap-4">
                    <div class="w-12 h-12 bg-indigo-100 text-indigo-600 rounded-lg flex items-center justify-center flex-shrink-0">
                        <i class="fa fa-calendar-alt text-xl"></i>
                    </div>
                    <div class="flex-grow">
                        <h3 class="font-bold text-slate-800 text-md">Jadwal Besok</h3>
                        <p class="text-xs text-slate-500 mt-1 mb-3">Ingatkan jadwal kuliah untuk hari besok</p>
                        <button type="button" onclick="confirmJadwal()" class="w-full bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-bold py-2 px-4 rounded-lg transition">
                            <i class="fab fa-whatsapp mr-1"></i> Kirim
                        </button>
                    </div>
                </div>
            </form>

            <!-- Kas -->
            <form method="POST" id="formKas" class="bg-white rounded-xl shadow-sm border border-slate-200 p-5 hover:shadow-md transition">
                <input type="hidden" name="action" value="kas">
                <input type="hidden" name="minggu_ke" id="kas_minggu_ke" value="1">
                <div class="flex items-start gap-4">
                    <div class="w-12 h-12 bg-green-100 text-green-600 rounded-lg flex items-center justify-center flex-shrink-0">
                        <i class="fa fa-wallet text-xl"></i>
                    </div>
                    <div class="flex-grow">
                        <h3 class="font-bold text-slate-800 text-md">Kas Mingguan</h3>
                        <p class="text-xs text-slate-500 mt-1 mb-3">Kirim pengingat uang kas yang belum lunas</p>
                        <button type="button" onclick="confirmKas()" class="w-full bg-green-600 hover:bg-green-700 text-white text-xs font-bold py-2 px-4 rounded-lg transition">
                            <i class="fab fa-whatsapp mr-1"></i> Kirim
                        </button>
                    </div>
                </div>
            </form>

            <!-- Piket proyektor -->
            <form method="POST" class="bg-white rounded-xl shadow-sm border border-slate-200 p-5 hover:shadow-md transition">
                <input type="hidden" name="action" value="piket">
                <div class="flex items-start gap-4">
                    <div class="w-12 h-12 bg-indigo-900 text-white rounded-lg flex items-center justify-center flex-shrink-0 relative">
                        <i class="fa fa-plug text-xl"></i>
                    </div>
                    <div class="flex-grow">
                        <h3 class="font-bold text-slate-800 text-md">Piket Proyektor</h3>
                        <p class="text-xs text-slate-500 mt-1 mb-3">Kirim pengingat petugas piket proyektor besok</p>
                        <button type="submit" class="w-full bg-indigo-900 hover:bg-black text-white text-xs font-bold py-2 px-4 rounded-lg transition shadow-sm">
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
                        <label class="block text-sm font-bold text-slate-700 mb-2">Minggu Ke-</label>
                        <select id="select_minggu" class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-green-500">
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

        function confirmJadwal() {
            Swal.fire({
                title: 'Kirim Jadwal?',
                text: "Pilih metode pengiriman jadwal ke WhatsApp.",

                icon: 'question',
                showCancelButton: true,
                showDenyButton: true,
                confirmButtonColor: '#3085d6',
                denyButtonColor: '#f59e0b',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Kirim Langsung',
                denyButtonText: 'Sesuaikan Jadwal',
                cancelButtonText: 'Batal'
            }).then((result) => {
                if (result.isConfirmed) {
                    // Kirim Langsung (Submit form default HTML)
                    document.getElementById('formJadwal').submit();
                } else if (result.isDenied) {
                    fetchDataAndShowList();
                }
            });
        }

        function fetchDataAndShowList() {
            Swal.showLoading();
            // Fetch ke diri sendiri (local endpoint)
            fetch('?fetch_adjust_data=1')
                .then(r => r.json())
                .then(data => {
                    currentJadwal = data.jadwal;
                    optJam = data.opt_jam;
                    optRuang = data.opt_ruang;
                    renderJadwalList(data.tomorrow, data.date);
                })
                .catch(err => {
                    console.error(err);
                    Swal.fire('Error', 'Gagal mengambil data jadwal', 'error');
                });
        }

        function renderJadwalList(dayName, dateStr) {
            // Check for saved state
            if (dayName) window.lastDayName = dayName;
            if (dateStr) window.lastDateStr = dateStr;
            dayName = dayName || window.lastDayName;
            dateStr = dateStr || window.lastDateStr;

            let html = `<div class="text-left text-sm mb-2">Hari: <b>${dayName}</b>, ${dateStr}</div>`;

            if (currentJadwal.length === 0) {
                html += '<p class="text-red-500 text-sm">Tidak ada jadwal untuk besok.</p>';
            } else {
                html += `<div class="overflow-x-auto max-h-64 border rounded-lg">
                    <table class="w-full text-xs text-left">
                        <thead class="bg-indigo-50 text-indigo-900 border-b">
                            <tr>
                                <th class="p-2 w-8 text-center">#</th>
                                <th class="p-2">Mata Kuliah</th>
                                <th class="p-2">Jam</th>
                                <th class="p-2">Ruangan</th>
                                <th class="p-2 w-16 text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y">`;

                currentJadwal.forEach((item, index) => {
                    const checked = item.checked ? 'checked' : '';
                    const statusBadge = item.status === 'tidak_masuk' ? '<span class="text-xs bg-red-100 text-red-600 px-2 py-0.5 rounded-full ml-1">Tidak Masuk</span>' : '';
                    html += `<tr>
                        <td class="p-2 text-center"><input type="checkbox" onchange="toggleCheck(${index})" ${checked}></td>
                        <td class="p-2 font-medium">${item.matkul}${statusBadge}</td>
                        <td class="p-2 text-slate-500">${item.jam_text}</td>
                        <td class="p-2 text-slate-500">${item.ruangan_text}</td>
                        <td class="p-2 text-center">
                            <button type="button" onclick="showEditForm(${index})" class="text-blue-600 hover:text-blue-800 bg-blue-50 hover:bg-blue-100 px-2 py-1 rounded">Edit</button>
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
                confirmButtonText: 'Kirim',
                cancelButtonText: 'Batal'
            }).then((result) => {
                if (result.isConfirmed) {
                    submitCustomJadwal();
                }
            });
        }

        function toggleCheck(index) {
            currentJadwal[index].checked = !currentJadwal[index].checked;
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
            let ruangHtml = `<select id="edit_ruang" class="w-full px-3 py-2 border rounded-md text-sm focus:outline-none focus:ring-1 focus:ring-indigo-500">`;
            optRuang.forEach(opt => {
                const selected = (opt.val === item.ruangan_text) ? 'selected' : '';
                ruangHtml += `<option value="${opt.val}" ${selected}>${opt.label}</option>`;
            });
            ruangHtml += `</select>`;

            Swal.fire({
                title: 'Edit Item Jadwal',
                html: `
                    <div class="text-left">
                        <label class="block text-xs font-bold text-slate-700 mb-1">Mata Kuliah</label>
                        <input type="text" value="${item.matkul}" disabled class="w-full px-3 py-2 border bg-slate-100 rounded-md text-sm mb-3">
                        
                        <label class="block text-xs font-bold text-slate-700 mb-1">Status Kehadiran</label>
                        <select id="edit_status" class="w-full px-3 py-2 border rounded-md text-sm mb-3 focus:outline-none focus:ring-1 focus:ring-indigo-500">
                            <option value="masuk" ${currentStatus === 'masuk' ? 'selected' : ''}>Masuk</option>
                            <option value="tidak_masuk" ${currentStatus === 'tidak_masuk' ? 'selected' : ''}>Tidak Masuk</option>
                        </select>
                        
                        <label class="block text-xs font-bold text-slate-700 mb-1">Jam Mulai</label>
                        <input type="time" id="edit_jam_mulai" value="${jamMulai}" class="w-full px-3 py-2 border rounded-md text-sm mb-3 focus:outline-none focus:ring-1 focus:ring-indigo-500">
                        
                        <label class="block text-xs font-bold text-slate-700 mb-1">Jam Selesai</label>
                        <input type="time" id="edit_jam_selesai" value="${jamSelesai}" class="w-full px-3 py-2 border rounded-md text-sm mb-3 focus:outline-none focus:ring-1 focus:ring-indigo-500">
                        
                        <label class="block text-xs font-bold text-slate-700 mb-1">Ruangan</label>
                        ${ruangHtml}
                    </div>
                `,
                showCancelButton: true,
                confirmButtonText: 'Simpan',
                cancelButtonText: 'Batal',
                preConfirm: () => {
                    const jamMulaiVal = document.getElementById('edit_jam_mulai').value;
                    const jamSelesaiVal = document.getElementById('edit_jam_selesai').value;
                    const newRuang = document.getElementById('edit_ruang').value;
                    const newStatus = document.getElementById('edit_status').value;

                    // Format time as HH.MM - HH.MM
                    const jamText = jamMulaiVal.replace(':', '.') + ' - ' + jamSelesaiVal.replace(':', '.');

                    return {
                        jam: jamText,
                        ruang: newRuang,
                        status: newStatus
                    };
                }
            }).then((res) => {
                if (res.isConfirmed) {
                    currentJadwal[index].jam_text = res.value.jam;
                    currentJadwal[index].ruangan_text = res.value.ruang;
                    currentJadwal[index].status = res.value.status;
                    renderJadwalList();
                } else {
                    renderJadwalList();
                }
            });
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

            // Create hidden form to submit array to THIS PAGE (reminder.php)
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = 'reminder'; // submit to self

            const inputAction = document.createElement('input');
            inputAction.type = 'hidden';
            inputAction.name = 'action';
            inputAction.value = 'jadwal_custom';
            form.appendChild(inputAction);

            const inputPayload = document.createElement('input');
            inputPayload.type = 'hidden';
            inputPayload.name = 'custom_payload';
            inputPayload.value = JSON.stringify(selectedItems);
            form.appendChild(inputPayload);

            document.body.appendChild(form);
            form.submit();
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