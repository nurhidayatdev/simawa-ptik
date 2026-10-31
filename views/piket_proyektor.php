<?php
session_start();
include '../koneksi.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../index");
    exit;
}

$nama_lengkap = $_SESSION['nama_lengkap'];
$user_id = $_SESSION['user_id'];
$foto = $_SESSION['foto'];
$role = $_SESSION['role'];

// Allowed roles for admin actions
$admin_roles = ['Administrator Utama', 'Ketua Tingkat', 'Sekretaris'];
$is_admin = in_array($role, $admin_roles);

// Include foto helpers for cache busting
include_once '../functions/foto_helpers.php';

/* =====================
   AMBIL PERIODE AKTIF
   ===================== */
$q_periode = mysqli_query($koneksi, "SELECT * FROM periode WHERE status = 'aktif' LIMIT 1");
$periode = mysqli_fetch_assoc($q_periode);
$periode_id = $periode['id'] ?? 0;
$periode_text = $periode ? $periode['tahun_periode'] . ' - ' . $periode['semester_nama'] : 'Tidak ada periode aktif';

// Ambil Tanggal Mulai Kuliah dari Kalender Akademik (Otomatis)
$q_start = mysqli_query($koneksi, "SELECT tanggal_mulai FROM kalender_akademik WHERE periode_id = $periode_id AND (kegiatan LIKE '%Kuliah Bagian Pertama%') ORDER BY tanggal_mulai ASC LIMIT 1");
$start_res = mysqli_fetch_assoc($q_start);
$start_date = $start_res['tanggal_mulai'] ?? "2026-08-18"; // Fallback jika tidak ditemukan

// Hitung hari Senin sebagai awal pekan perkuliahan (siklus Senin s/d Jumat)
$day_of_week = (int)date('N', strtotime($start_date)); // 1 = Senin, ..., 7 = Minggu
$start_monday = date('Y-m-d', strtotime("$start_date - " . ($day_of_week - 1) . " days"));

// Hitung Minggu Default berdasarkan tanggal hari ini (siklus Senin - Minggu)
$today = date('Y-m-d');
$diff = strtotime($today) - strtotime($start_monday);
$default_minggu = floor($diff / (7 * 24 * 60 * 60)) + 1;
if ($default_minggu < 1) $default_minggu = 1;
if ($default_minggu > 18) $default_minggu = 18;

// Filter Minggu
$filter_minggu = isset($_GET['minggu']) ? intval($_GET['minggu']) : $default_minggu;
if ($filter_minggu < 1) $filter_minggu = 1;
if ($filter_minggu > 18) $filter_minggu = 18;

// Data Piket
$where_p_kelas = getKelasWhereClause('p', true);
$query = "SELECT p.*, m.nama_lengkap, m.foto, jm.hari, mk.nama_matkul, r.nama_ruangan, jk_start.jam_mulai, jk_end.jam_selesai
          FROM piket_proyektor p
          JOIN mahasiswa m ON p.mahasiswa_id = m.id
          JOIN jadwal_matkul jm ON p.jadwal_matkul_id = jm.id
          JOIN mata_kuliah mk ON jm.matkul_id = mk.id
          LEFT JOIN ruangan r ON jm.ruangan_id = r.id
          LEFT JOIN jam_kuliah jk_start ON jm.jam_mulai_ke = jk_start.jam_ke
          LEFT JOIN jam_kuliah jk_end ON jm.jam_selesai_ke = jk_end.jam_ke
          WHERE p.periode_id = $periode_id AND p.minggu_ke = $filter_minggu $where_p_kelas
          ORDER BY FIELD(jm.hari, 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu', 'Minggu'), jk_start.jam_mulai, p.jadwal_matkul_id, m.nama_lengkap";

$res_piket = mysqli_query($koneksi, $query);

$piket_data = [];
while ($row = mysqli_fetch_assoc($res_piket)) {
    $jid = $row['jadwal_matkul_id'];
    if (!isset($piket_data[$jid])) {
        $piket_data[$jid] = [
            'matkul' => $row['nama_matkul'],
            'hari' => $row['hari'],
            'jam' => date('H:i', strtotime($row['jam_mulai'])) . ' - ' . date('H:i', strtotime($row['jam_selesai'])),
            'ruangan' => $row['nama_ruangan'] ?? '-',
            'petugas' => []
        ];
    }
    $piket_data[$jid]['petugas'][] = [
        'nama' => $row['nama_lengkap'],
        'foto' => $row['foto']
    ];
}

$week_options = [];
for ($i = 1; $i <= 18; $i++) {
    $week_options[$i] = "Minggu $i";
}

$block_options = [];
for ($i = 1; $i <= 6; $i++) {
    $w1 = ($i - 1) * 3 + 1;
    $w2 = $i * 3;
    $b_start_monday = date('d M', strtotime("$start_monday + " . (($w1 - 1) * 7) . " days"));
    $b_end_friday = date('d M Y', strtotime("$start_monday + " . (($w2 - 1) * 7 + 4) . " days"));
    $block_options[$i] = "Blok $i (Minggu $w1 - $w2: $b_start_monday - $b_end_friday)";
}

$week_reminder_options = [];
for ($i = 1; $i <= 18; $i++) {
    $w_monday = date('Y-m-d', strtotime("$start_monday + " . (($i - 1) * 7) . " days"));
    $w_friday = date('Y-m-d', strtotime("$w_monday + 4 days"));
    $week_start = date('d M', strtotime($w_monday));
    $week_end = date('d M Y', strtotime($w_friday));
    $is_cur = ($i == $default_minggu) ? ' (Minggu Ini)' : '';
    $week_reminder_options[$i] = "Minggu $i ($week_start - $week_end)$is_cur";
}

// Data Piket Saya (Summary)
$res_mhs_piket = mysqli_query($koneksi, "SELECT id FROM mahasiswa WHERE user_id = '$user_id'");
$mhs_piket_row = mysqli_fetch_assoc($res_mhs_piket);
$my_mhs_id = $mhs_piket_row['id'] ?? 0;
$my_piket_list = [];
if ($my_mhs_id) {
    $q_mp = mysqli_query($koneksi, "SELECT p.*, jm.hari, mk.nama_matkul, jk_start.jam_mulai, r.nama_ruangan
        FROM piket_proyektor p
        JOIN jadwal_matkul jm ON p.jadwal_matkul_id = jm.id
        JOIN mata_kuliah mk ON jm.matkul_id = mk.id
        LEFT JOIN jam_kuliah jk_start ON jm.jam_mulai_ke = jk_start.jam_ke
        LEFT JOIN ruangan r ON jm.ruangan_id = r.id
        WHERE p.mahasiswa_id = $my_mhs_id AND p.periode_id = $periode_id
        ORDER BY p.minggu_ke ASC, FIELD(jm.hari, 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu', 'Minggu'), jk_start.jam_mulai ASC");
    $day_map_mhs = [
        'Senin'  => 0,
        'Selasa' => 1,
        'Rabu'   => 2,
        'Kamis'  => 3,
        'Jumat'  => 4,
        'Sabtu'  => 5,
        'Minggu' => 6
    ];
    while ($mp = mysqli_fetch_assoc($q_mp)) {
        $my_monday = date('Y-m-d', strtotime("$start_monday + " . (($mp['minggu_ke'] - 1) * 7) . " days"));
        $d_offset = $day_map_mhs[$mp['hari']] ?? 0;
        $mp['tgl_formatted'] = date('d M Y', strtotime("$my_monday + $d_offset days"));
        
        // Ambil rekan piket
        $jid = $mp['jadwal_matkul_id'];
        $m_ke = $mp['minggu_ke'];
        $where_p_tm = getKelasWhereClause('p', true);
        $q_teammates = mysqli_query($koneksi, "SELECT m.nama_lengkap FROM piket_proyektor p JOIN mahasiswa m ON p.mahasiswa_id = m.id WHERE p.jadwal_matkul_id = $jid AND p.minggu_ke = $m_ke AND p.periode_id = $periode_id $where_p_tm");
        $tm_names = [];
        while($tm = mysqli_fetch_assoc($q_teammates)) {
            $tm_names[] = "<li class='flex items-center gap-1.5'><i class='fa fa-circle text-[5px] text-slate-300 dark:text-slate-600'></i> " . htmlspecialchars($tm['nama_lengkap']) . "</li>";
        }
        $mp['teammates_html'] = "<ul class='mt-1 space-y-0.5 text-[9px] text-slate-600 dark:text-slate-400'>" . implode('', $tm_names) . "</ul>";

        $my_piket_list[] = $mp;
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <title>Piket Proyektor - SIMAWA PTIK 2024</title>
    <?php include '../includes/head.php'; ?>
    <style>
        .piket-card {
            transition: all 0.3s ease;
        }

        .piket-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
        }

        /* SweetAlert Dark Mode Fix */
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
        .dark .swal2-select {
            background-color: #1e293b !important;
            color: #f8fafc !important;
            border-color: #334155 !important;
        }

        .dark .swal2-confirm {
            background-color: #312e81 !important;
        }
    </style>
</head>

<body class="bg-slate-100 dark:bg-slate-950 transition-colors duration-300">
    <?php include '../includes/menu_db.php'; ?>

    <main class="ml-0 lg:ml-48 pt-20 p-6 flex flex-col min-h-screen bg-indigo-950/5 dark:bg-slate-950 transition-colors duration-300">
        <div class="bg-white dark:bg-slate-900/50 rounded-lg border border-slate-200 dark:border-slate-800 p-6 flex-grow w-full">
            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-2">
                <div>
                    <h3 class="text-xl font-semibold text-slate-800 dark:text-white">Piket Proyektor</h3>
                    <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">Daftar mahasiswa petugas pengambil proyektor mata kuliah.</p>
                </div>

                <div class="flex flex-col sm:flex-row gap-2 w-full sm:w-auto mt-4 sm:mt-0">
                    <button type="button" onclick="showMyPiket()" class="bg-indigo-50 dark:bg-indigo-900/30 text-indigo-700 dark:text-indigo-400 border border-indigo-100 dark:border-indigo-800/50 px-4 py-2 rounded-lg hover:bg-indigo-100 dark:hover:bg-indigo-900/50 text-center text-[11px] font-bold transition-all flex items-center justify-center gap-2 shadow-sm">
                        <i class="fa fa-calendar-alt"></i> Piket Saya
                    </button>
                    <?php if ($is_admin): ?>
                        <div class="flex flex-col sm:flex-row gap-2">
                            <form action="reminder" method="POST" id="reminderForm" class="w-full sm:w-auto">
                                <input type="hidden" name="action" value="piket">
                                <input type="hidden" name="day" id="piket_day" value="besok">
                                <input type="hidden" name="minggu_ke" id="piket_minggu_ke" value="">
                                <input type="hidden" name="redirect" value="piket_proyektor">
                                <button type="button" onclick="confirmPiket()" class="w-full bg-indigo-600 text-white px-3 py-2 rounded-lg hover:bg-indigo-700 text-[10px] font-bold flex items-center justify-center gap-1.5 shadow-sm transition-all uppercase tracking-wide">
                                    <i class="fab fa-whatsapp text-xs"></i> Notifikasi
                                </button>
                            </form>
                            <?php if ($role === 'Administrator Utama' || $role === 'Ketua Tingkat'): ?>
                                <button onclick="confirmReset()" class="bg-slate-800 text-white px-3 py-2 rounded-lg hover:bg-black text-[10px] font-bold flex items-center justify-center gap-1.5 shadow-sm transition-all uppercase tracking-wide">
                                    <i class="fa fa-random text-xs"></i> Acak
                                </button>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <form method="GET" class="my-6 max-w-sm">
                <label class="text-[10px] font-semibold text-slate-400 dark:text-slate-500 uppercase tracking-widest block mb-1.5">Pilih Minggu Perkuliahan</label>
                <div class="relative">
                    <select name="minggu" onchange="this.form.submit()" class="w-full bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-xs font-semibold text-indigo-700 dark:text-indigo-400 rounded-lg px-3 py-2.5 appearance-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 cursor-pointer shadow-sm">
                        <?php
                        for ($i = 1; $i <= 18; $i++):
                            $w_monday = date('Y-m-d', strtotime("$start_monday + " . (($i - 1) * 7) . " days"));
                            $w_friday = date('Y-m-d', strtotime("$w_monday + 4 days"));
                            $week_start = date('d M', strtotime($w_monday));
                            $week_end = date('d M Y', strtotime($w_friday));
                        ?>
                            <option value="<?= $i ?>" <?= $filter_minggu == $i ? 'selected' : '' ?>>
                                Minggu <?= $i ?> (<?= $week_start ?> - <?= $week_end ?>)
                            </option>
                        <?php endfor; ?>
                    </select>
                    <div class="absolute inset-y-0 right-0 flex items-center px-2 pointer-events-none text-slate-400">
                        <i class="fa fa-chevron-down text-[10px]"></i>
                    </div>
                </div>
            </form>

            <?php if (empty($piket_data)): ?>
                <div class="bg-slate-50 dark:bg-slate-800/50 rounded-2xl p-12 text-center border border-dashed border-slate-300 dark:border-slate-700">
                    <div class="w-16 h-16 bg-white dark:bg-slate-800 rounded-full flex items-center justify-center mx-auto mb-4 shadow-sm">
                        <i class="fa fa-clipboard-list text-2xl text-slate-300 dark:text-slate-600"></i>
                    </div>
                    <h3 class="text-sm font-semibold text-slate-700 dark:text-slate-200">Belum Ada Data Piket</h3>
                    <p class="text-[11px] text-slate-500 dark:text-slate-400 max-w-xs mx-auto">Administrator belum mengacak daftar piket proyektor untuk periode ini.</p>
                </div>
            <?php else: ?>
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
                    <?php
                    $day_map = [
                        'Senin'  => 0,
                        'Selasa' => 1,
                        'Rabu'   => 2,
                        'Kamis'  => 3,
                        'Jumat'  => 4,
                        'Sabtu'  => 5,
                        'Minggu' => 6
                    ];
                    $filter_monday = date('Y-m-d', strtotime("$start_monday + " . (($filter_minggu - 1) * 7) . " days"));
                    foreach ($piket_data as $jid => $data):
                        $day_offset = $day_map[$data['hari']] ?? 0;
                        $duty_date = date('d M Y', strtotime("$filter_monday + $day_offset days"));
                    ?>
                        <div class="bg-white dark:bg-slate-900 rounded-xl border border-slate-200 dark:border-slate-800 overflow-hidden hover:shadow-md transition-all group">
                            <div class="bg-slate-50 dark:bg-slate-950 px-4 py-3 border-b border-slate-100 dark:border-slate-800 group-hover:bg-indigo-50 dark:group-hover:bg-indigo-950/30 transition-colors">
                                <div class="flex justify-between items-center mb-1.5">
                                    <span class="text-[9px] font-bold text-slate-400 dark:text-slate-500 uppercase tracking-widest group-hover:text-indigo-500"><?= $data['hari'] ?></span>
                                    <span class="text-[9px] font-semibold text-slate-500 dark:text-slate-500"><?= $duty_date ?></span>
                                </div>
                                <h3 class="text-xs font-semibold text-slate-800 dark:text-slate-200 line-clamp-1 mb-1" title="<?= htmlspecialchars($data['matkul']) ?>"><?= htmlspecialchars($data['matkul']) ?></h3>
                                <div class="flex items-center gap-2">
                                    <span class="text-[9px] bg-white dark:bg-slate-800 px-2 py-0.5 rounded border border-slate-200 dark:border-slate-700 text-slate-600 dark:text-slate-400 font-semibold"><?= $data['jam'] ?></span>
                                    <div class="flex items-center gap-1">
                                        <i class="fa fa-map-marker-alt text-slate-300 dark:text-slate-600 text-[9px]"></i>
                                        <span class="text-[9px] font-medium text-slate-500 dark:text-slate-400"><?= htmlspecialchars($data['ruangan']) ?></span>
                                    </div>
                                </div>
                            </div>
                            <div class="p-3">
                                <div class="text-[8px] font-semibold text-slate-400 dark:text-slate-500 uppercase tracking-wider mb-2">Petugas Piket</div>
                                <div class="space-y-2">
                                    <?php foreach ($data['petugas'] as $petugas): ?>
                                        <div class="flex items-center gap-2">
                                            <div class="h-6 w-6 rounded-full overflow-hidden border border-slate-200 dark:border-slate-800 shrink-0 shadow-sm">
                                                <img src="<?= profilePhotoUrl($petugas['foto'], '../') ?>" class="w-full h-full object-cover">
                                            </div>
                                            <span class="text-[10px] font-semibold text-slate-700 dark:text-slate-300 truncate"><?= htmlspecialchars($petugas['nama']) ?></span>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <div class="mt-8 bg-slate-900/90 dark:bg-slate-900/50 rounded-xl p-6 text-white relative overflow-hidden shadow-lg border border-white/5 dark:border-slate-800">
                <div class="relative z-10">
                    <h3 class="text-sm font-semibold mb-2 flex items-center gap-2">
                        <i class="fa fa-info-circle text-indigo-400"></i>
                        Informasi Penting
                    </h3>
                    <p class="text-slate-400 dark:text-slate-300 text-[11px] leading-relaxed max-w-2xl">Petugas piket diharapkan mengambil proyektor di Ruangan Admin 15 menit sebelum perkuliahan dimulai dan mengembalikannya segera setelah mata kuliah selesai.</p>
                </div>
                <div class="absolute -right-10 -bottom-10 w-32 h-32 bg-slate-800 dark:bg-slate-800/50 rounded-full opacity-50"></div>
            </div>

            <?php include '../includes/footer_db.php'; ?>
        </div>
    </main>

    <?php include '../includes/script_db.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        const weekReminderOptions = <?= json_encode($week_reminder_options) ?>;
        const currentFilterMinggu = <?= json_encode($filter_minggu) ?>;
        const currentDefaultMinggu = <?= json_encode($default_minggu) ?>;

        function confirmPiket() {
            Swal.fire({
                title: 'Kirim Notifikasi WhatsApp',
                text: "Pilih cakupan jadwal piket yang ingin dikirim ke grup:",
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
                customClass: {
                    popup: 'rounded-2xl',
                    title: 'text-lg font-bold',
                    htmlContainer: 'text-sm',
                    input: 'text-xs font-semibold rounded-lg'
                },
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
                            text: "Pilih minggu jadwal piket yang ingin dikirim:",
                            input: 'select',
                            inputOptions: weekReminderOptions,
                            inputValue: currentFilterMinggu || currentDefaultMinggu || 1,
                            showCancelButton: true,
                            confirmButtonColor: '#312e81',
                            confirmButtonText: '<i class="fab fa-whatsapp"></i> Kirim Notifikasi',
                            cancelButtonText: 'Kembali',
                            customClass: {
                                popup: 'rounded-2xl',
                                title: 'text-lg font-bold',
                                htmlContainer: 'text-sm',
                                input: 'text-xs font-semibold rounded-lg'
                            },
                            inputValidator: (val) => {
                                if (!val) {
                                    return 'Silakan pilih minggu terlebih dahulu!'
                                }
                            }
                        }).then((weekResult) => {
                            if (weekResult.isConfirmed) {
                                document.getElementById('piket_day').value = 'seluruh_minggu';
                                document.getElementById('piket_minggu_ke').value = weekResult.value;
                                document.getElementById('reminderForm').submit();
                            } else if (weekResult.dismiss === Swal.DismissReason.cancel) {
                                confirmPiket();
                            }
                        });
                    } else {
                        document.getElementById('piket_day').value = result.value;
                        document.getElementById('piket_minggu_ke').value = '';
                        document.getElementById('reminderForm').submit();
                    }
                }
            });
        }

        function confirmReset() {
            Swal.fire({
                title: 'Opsi Pengacakan Piket',
                text: "Silakan pilih metode pengacakan yang Anda inginkan.",
                icon: 'question',
                showCancelButton: true,
                showDenyButton: true,
                confirmButtonColor: '#312e81',
                denyButtonColor: '#10b981',
                cancelButtonColor: '#6b7280',
                confirmButtonText: 'Acak Semua (18 Minggu)',
                denyButtonText: 'Acak 3 Minggu Saja',
                cancelButtonText: 'Batal',
                customClass: {
                    popup: 'rounded-2xl',
                    title: 'text-lg font-bold',
                    htmlContainer: 'text-sm'
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    // Acak Semua
                    Swal.fire({
                        title: 'Yakin acak semua?',
                        text: "Daftar piket lama akan dihapus dan diganti dengan yang baru untuk 18 minggu.",
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonColor: '#312e81',
                        confirmButtonText: 'Ya, Acak Semua!',
                        cancelButtonText: 'Batal'
                    }).then((c) => {
                        if(c.isConfirmed) window.location.href = '../actions/tambah_piket_proyektor.php';
                    });
                } else if (result.isDenied) {
                    // Acak Blok (3 Minggu)
                    Swal.fire({
                        title: 'Acak 3 Minggu Tertentu',
                        text: "Pilih blok yang ingin diacak ulang (berisi 3 minggu):",
                        input: 'select',
                        inputOptions: <?= json_encode($block_options) ?>,
                        showCancelButton: true,
                        confirmButtonColor: '#10b981',
                        confirmButtonText: 'Acak Blok Ini',
                        cancelButtonText: 'Batal'
                    }).then((blockResult) => {
                        if (blockResult.isConfirmed) {
                            window.location.href = '../actions/tambah_piket_proyektor.php?blok=' + blockResult.value;
                        }
                    });
                }
            });
        }

        <?php if (isset($_SESSION['success'])): ?>
            Swal.fire({
                icon: 'success',
                title: 'Berhasil!',
                text: '<?= $_SESSION['success'] ?>',
                confirmButtonColor: '#312e81'
            });
            <?php unset($_SESSION['success']); ?>
        <?php endif; ?>

        <?php if (isset($_SESSION['error'])): ?>
            Swal.fire({
                icon: 'error',
                title: 'Gagal!',
                text: '<?= $_SESSION['error'] ?>',
                confirmButtonColor: '#d33'
            });
            <?php unset($_SESSION['error']); ?>
        <?php endif; ?>

        <?php if (isset($_SESSION['reminder_msg'])): ?>
            Swal.fire({
                icon: '<?= $_SESSION['reminder_type'] === "success" ? "success" : ($_SESSION['reminder_type'] === "error" ? "error" : "info") ?>',
                title: '<?= $_SESSION['reminder_type'] === "success" ? "Berhasil!" : ($_SESSION['reminder_type'] === "error" ? "Gagal!" : "Informasi") ?>',
                text: '<?= addslashes($_SESSION['reminder_msg']) ?>',
                confirmButtonColor: '#312e81'
            });
            <?php
            unset($_SESSION['reminder_msg']);
            unset($_SESSION['reminder_type']);
            ?>
        <?php endif; ?>

        function showMyPiket() {
            const template = document.getElementById('myPiketTemplate');
            if (!template) return;

            Swal.fire({
                title: 'Jadwal Piket Saya',
                html: template.innerHTML,
                width: '550px',
                confirmButtonText: 'Tutup',
                confirmButtonColor: '#1e1b4b',
                padding: '1.5rem',
                customClass: {
                    title: 'text-xl font-bold mb-1',
                    popup: 'rounded-2xl border border-slate-200 dark:border-slate-800',
                    confirmButton: 'px-6 py-2 rounded-xl font-bold uppercase tracking-wider text-xs'
                }
            });
        }
    </script>

    <template id="myPiketTemplate">
        <div class="text-left space-y-3 py-2 max-h-[60vh] overflow-y-auto pr-2 custom-scrollbar">
            <?php if (count($my_piket_list) === 0): ?>
                <div class="p-8 text-center text-slate-400 dark:text-slate-500">
                    <i class="fa fa-calendar-times text-4xl mb-3 block"></i>
                    <p class="text-sm">Kamu tidak memiliki jadwal piket semester ini.</p>
                </div>
            <?php else: ?>
                <div class="bg-blue-50 dark:bg-blue-900/30 border border-blue-100 dark:border-blue-800/50 p-3 rounded-lg mb-4 text-[10px] text-blue-700 dark:text-blue-300 flex gap-2">
                    <i class="fa fa-info-circle text-xs"></i>
                    <p>Ditemukan <b><?= count($my_piket_list) ?> tugas piket</b> untukmu selama semester ini.</p>
                </div>
                <?php foreach ($my_piket_list as $mp): ?>
                    <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 p-3 rounded-xl shadow-sm relative overflow-hidden group">
                        <div class="absolute left-0 top-0 bottom-0 w-1 bg-indigo-500"></div>
                        <div class="flex justify-between items-start mb-1.5">
                            <div>
                                <span class="bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400 text-[9px] font-bold px-1.5 py-0.5 rounded uppercase tracking-tighter">Minggu <?= $mp['minggu_ke'] ?></span>
                                <h5 class="font-bold text-slate-800 dark:text-white text-xs mt-1 leading-tight"><?= htmlspecialchars($mp['nama_matkul']) ?></h5>
                            </div>
                            <div class="text-right">
                                <span class="text-[10px] font-bold text-indigo-600 dark:text-indigo-400 block"><?= $mp['hari'] ?></span>
                                <span class="text-[9px] text-slate-400 dark:text-slate-500 block"><?= $mp['tgl_formatted'] ?></span>
                            </div>
                        </div>
                        <div class="flex items-center gap-3 mt-2 pt-2 border-t border-slate-50 dark:border-slate-800">
                            <div class="flex items-center gap-1">
                                <i class="fa fa-clock text-slate-300 dark:text-slate-600 text-[9px]"></i>
                                <span class="text-[9px] font-medium text-slate-600 dark:text-slate-400"><?= date('H:i', strtotime($mp['jam_mulai'])) ?></span>
                            </div>
                            <div class="flex items-center gap-1">
                                <i class="fa fa-map-marker-alt text-slate-300 dark:text-slate-600 text-[9px]"></i>
                                <span class="text-[9px] font-medium text-slate-600 dark:text-slate-400"><?= htmlspecialchars($mp['nama_ruangan']) ?></span>
                            </div>
                        </div>
                        <div class="mt-2 pt-2 border-t border-slate-50 dark:border-slate-800">
                            <span class="text-[8px] font-bold text-indigo-400 uppercase tracking-widest block mb-1">Rekan Petugas:</span>
                            <?= $mp['teammates_html'] ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </template>
</body>

</html>