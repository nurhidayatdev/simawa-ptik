<?php
session_start();
include '../koneksi.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../index.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$nama_lengkap = $_SESSION['nama_lengkap'];
$foto = $_SESSION['foto'];
$role = $_SESSION['role'];

// Include foto helpers
include_once '../functions/foto_helpers.php';

// Ambil Mahasiswa ID
$query_mhs = "SELECT id, nim FROM mahasiswa WHERE user_id = ?";
$stmt = mysqli_prepare($koneksi, $query_mhs);
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$res_mhs = mysqli_stmt_get_result($stmt);
$mahasiswa = mysqli_fetch_assoc($res_mhs);
if (!$mahasiswa) {
    // Should not happen if data consistent
    echo "Data mahasiswa tidak ditemukan.";
    exit;
}
$mahasiswa_id = $mahasiswa['id'];
$nim = $mahasiswa['nim'];

// Handle Minta Validasi Action
if (isset($_GET['act']) && $_GET['act'] === 'minta_validasi') {
    include_once '../functions/kirim_notif_krs.php';
    $res = notifMintaValidasiKrs($nama_lengkap, $nim);
    $status_msg = $res['success'] ? 'notif_sent' : 'notif_error';
    header("Location: ksm?msg=" . $status_msg);
    exit;
}

// Ambil Periode Aktif
$q_periode = mysqli_query($koneksi, "SELECT id, semester_ke, tahun_periode, semester_nama FROM periode WHERE status = 'aktif' LIMIT 1");
$periode = mysqli_fetch_assoc($q_periode);
if (!$periode) {
    echo "Tidak ada periode aktif.";
    exit;
}
$periode_id = $periode['id'];
$periode_label = $periode['tahun_periode'] . " " . $periode['semester_nama'];

// Cek Status Validasi (Optional, but good for UI)
$q_val = mysqli_query($koneksi, "SELECT status FROM krs_validasi WHERE mahasiswa_id = $mahasiswa_id AND periode_id = $periode_id");
$val_data = mysqli_fetch_assoc($q_val);
$status_validasi = $val_data['status'] ?? 'pending';

// Fetch Approved KRS (actual KSM) from 'krs' table
// Since approved/synced data is in 'krs'
$q_ksm = "
    SELECT 
        mk.kode_matkul,
        mk.nama_matkul,
        mk.sks,
        mk.semester,
        d1.nama_dosen as dosen_pengampu,
        d2.nama_dosen as dosen_mitra,
        jm.hari,
        jm.jam_mulai_ke,
        jm.jam_selesai_ke,
        r.nama_ruangan,
        jk_mulai.jam_mulai as waktu_mulai,
        jk_selesai.jam_selesai as waktu_selesai
    FROM krs k
    JOIN mata_kuliah mk ON k.matkul_id = mk.id
    LEFT JOIN jadwal_matkul jm ON (mk.id = jm.matkul_id AND jm.periode_id = k.periode_id)
    LEFT JOIN dosen d1 ON jm.dosen_pengampu_id = d1.id
    LEFT JOIN dosen d2 ON jm.dosen_mitra_id = d2.id
    LEFT JOIN ruangan r ON jm.ruangan_id = r.id
    LEFT JOIN jam_kuliah jk_mulai ON jm.jam_mulai_ke = jk_mulai.jam_ke
    LEFT JOIN jam_kuliah jk_selesai ON jm.jam_selesai_ke = jk_selesai.jam_ke
    WHERE k.mahasiswa_id = $mahasiswa_id
    AND k.periode_id = $periode_id
    ORDER BY mk.semester ASC, mk.nama_matkul ASC
";
$res_ksm = mysqli_query($koneksi, $q_ksm);

$ksm_data = [];
$total_sks = 0;
while ($row = mysqli_fetch_assoc($res_ksm)) {
    $ksm_data[] = $row;
    $total_sks += (int)$row['sks'];
}

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <title>KSM - <?= $nim ?> - <?= $periode_label ?></title>
    <?php include '../includes/head.php'; ?>
    <style>
        @media (max-width: 768px) {
            .mobile-menu-open {
                transform: translateX(0) !important;
            }
        }

        @media print {
            @page {
                size: A4 portrait;
                margin: 10mm;
            }

            body {
                background: white !important;
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
            }

            /* Force Light Mode on Print even if .dark class is active */
            .dark {
                background-color: white !important;
                color-scheme: light !important;
            }

            .dark #printableArea {
                background-color: white !important;
                color: #1e293b !important;
                border: none !important;
                padding: 0 !important;
                margin: 0 !important;
                width: 100% !important;
                box-shadow: none !important;
            }

            .dark #printableArea * {
                border-color: #e2e8f0 !important;
                --tw-ring-color: transparent !important;
            }

            .no-print,
            aside,
            nav,
            header,
            footer,
            #mobile-menu-button,
            .swal2-container,
            button,
            .flex.gap-2.no-print,
            .absolute.top-0.right-0.bg-indigo-900\/20 {
                display: none !important;
            }

            main {
                margin: 0 !important;
                padding: 0 !important;
                width: 100% !important;
                display: block !important;
            }

            #printableArea {
                border: none !important;
                padding: 0 !important;
                margin: 0 !important;
                width: 100% !important;
                box-shadow: none !important;
            }

            .print-row {
                display: flex !important;
                flex-direction: row !important;
                align-items: center !important;
                justify-content: space-between !important;
                gap: 1rem !important;
            }

            .print-grid-2 {
                display: grid !important;
                grid-template-columns: repeat(2, minmax(0, 1fr)) !important;
                gap: 1rem !important;
            }

            .print-header {
                display: flex !important;
                flex-direction: row !important;
                align-items: center !important;
                text-align: left !important;
                border-bottom: 2px solid #e2e8f0 !important;
                margin-bottom: 1.5rem !important;
                padding-bottom: 1rem !important;
            }

            .print-header img {
                width: 60px !important;
                height: 60px !important;
                margin-right: 1.5rem !important;
                filter: brightness(0) saturate(100%) invert(8%) sepia(35%) saturate(5462%) hue-rotate(229deg) brightness(94%) contrast(100%) !important;
            }

            .print-student-info {
                display: flex !important;
                flex-direction: row !important;
                gap: 1.5rem !important;
                background-color: #f8fafc !important;
                padding: 1rem !important;
                border: 1px solid #e2e8f0 !important;
                border-radius: 0.75rem !important;
            }

            .dark .print-student-info {
                background-color: #f8fafc !important;
            }

            .dark .print-student-info span:not(.text-slate-400):not(.text-slate-500) {
                color: #1e293b !important;
            }

            .dark .print-student-info .text-slate-400,
            .dark .print-student-info .text-slate-500 {
                color: #64748b !important;
            }

            .print-total-sks {
                min-width: 100px !important;
                padding: 0.75rem !important;
                background-color: #312e81 !important;
                color: white !important;
            }

            .dark thead {
                background-color: #1e293b !important;
                color: white !important;
            }

            .dark tbody,
            .dark tbody tr,
            .dark tfoot,
            .dark tfoot tr {
                background-color: white !important;
            }

            .dark tbody td,
            .dark tfoot td {
                color: #334155 !important;
            }

            .dark .print-total-sks {
                background-color: #f8fafc !important;
                color: #312e81 !important;
                border: 1px solid #e2e8f0 !important;
            }

            .dark .print-total-sks span:first-child {
                color: #64748b !important;
            }

            .dark .print-total-sks span:last-child {
                color: #312e81 !important;
            }
        }
    </style>
</head>

<body class="bg-slate-100 dark:bg-slate-950 transition-colors duration-300">
    <?php include '../includes/menu_db.php'; ?>

    <main class="ml-0 lg:ml-48 pt-20 p-6 flex flex-col min-h-screen">
        <div id="printableArea" class="bg-white dark:bg-slate-900 rounded-lg border border-slate-200 dark:border-slate-800 p-6 flex-grow w-full transition-colors duration-300">

            <!-- KSM Header Logo -->
            <div class="hidden print:flex print-header items-center justify-center text-center md:text-left gap-4 mb-8 pb-6 border-b-2 border-slate-200 dark:border-slate-800">
                <img src="<?= $base ?>assets/image/logo.webp" alt="Logo UNM" class="w-20 h-20 object-contain dark:brightness-200" style="filter: brightness(0) saturate(100%) invert(8%) sepia(35%) saturate(5462%) hue-rotate(229deg) brightness(94%) contrast(100%);">
                <div class="flex-1">
                    <h2 class="text-xl font-black text-indigo-950 uppercase tracking-tight">Universitas Negeri Makassar</h2>
                    <h3 class="text-lg font-bold text-slate-700 uppercase">Fakultas Teknik</h3>
                    <h4 class="text-sm font-semibold text-slate-600 uppercase tracking-wide">Program Studi Pendidikan Teknik Informatika dan Komputer</h4>
                </div>
            </div>

            <div class="flex flex-col md:flex-row print-row justify-between items-start md:items-center mb-6 gap-4">
                <div>
                    <h1 class="text-2xl font-bold text-slate-800 dark:text-white tracking-tight">Kartu Studi Mahasiswa (KSM)</h1>
                </div>
                <div class="flex gap-2 no-print">
                    <?php if ($status_validasi !== 'approved'): ?>
                        <a href="?act=minta_validasi" class="bg-orange-500 text-white px-3 py-1.5 rounded-lg text-xs font-semibold hover:bg-orange-600 transition flex items-center shadow-md">
                            <i class="fas fa-paper-plane mr-2"></i> Minta Validasi
                        </a>
                    <?php endif; ?>
                    <a href="../actions/edit_krs.php" class="bg-indigo-600 text-white px-3 py-1.5 rounded-lg text-xs font-semibold hover:bg-indigo-700 transition flex items-center shadow-md">
                        <i class="fas fa-edit mr-2"></i> Edit KRS
                    </a>
                    <?php if ($status_validasi === 'approved'): ?>
                        <button onclick="window.print()" class="bg-slate-800 text-white px-3 py-1.5 rounded-lg text-xs font-semibold hover:bg-slate-900 transition flex items-center shadow-md">
                            <i class="fas fa-print mr-2"></i> Cetak PDF
                        </button>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Student Info Block -->
            <div class="bg-slate-50 dark:bg-slate-800/50 rounded-xl p-4 border border-slate-200 dark:border-slate-700 mb-6 flex flex-col md:flex-row print-student-info gap-4 items-center md:items-start relative overflow-hidden">
                <div class="absolute top-0 right-0 w-24 h-24 bg-indigo-50 dark:bg-indigo-900/20 rounded-bl-full -mr-12 -mt-12 opacity-50 no-print"></div>
                <img src="<?= profilePhotoUrl($foto, '../') ?>" alt="Foto Profil" class="w-20 h-20 rounded-lg object-cover border-2 border-white dark:border-slate-700 shadow-md z-10">
                <div class="flex-1 grid grid-cols-1 md:grid-cols-2 print-grid-2 gap-x-8 gap-y-3 text-sm w-full z-10">
                    <div class="flex flex-col">
                        <span class="text-slate-400 dark:text-slate-500 text-[9px] uppercase tracking-widest font-black mb-0.5">Nama Mahasiswa</span>
                        <span class="text-slate-800 dark:text-white font-black text-base"><?= htmlspecialchars($nama_lengkap) ?></span>
                    </div>
                    <div class="flex flex-col">
                        <span class="text-slate-400 dark:text-slate-500 text-[9px] uppercase tracking-widest font-black mb-0.5">NIM</span>
                        <span class="text-slate-800 dark:text-slate-200 font-bold text-base"><?= htmlspecialchars($nim) ?></span>
                    </div>
                    <div class="flex flex-col">
                        <span class="text-slate-400 dark:text-slate-500 text-[9px] uppercase tracking-widest font-black mb-0.5">Periode Akademik</span>
                        <span class="text-indigo-600 dark:text-indigo-400 font-bold"><?= htmlspecialchars($periode_label) ?></span>
                    </div>
                    <div class="flex flex-col">
                        <span class="text-slate-400 dark:text-slate-500 text-[9px] uppercase tracking-widest font-black mb-0.5">Kelas</span>
                        <span class="text-slate-800 dark:text-slate-200 font-bold text-base">SIMAWA PTIK 2024</span>
                    </div>
                </div>
                <div class="bg-indigo-900 dark:bg-indigo-950 text-white p-3 px-6 rounded-xl shadow-lg text-center min-w-[120px] z-10 print-total-sks">
                    <span class="block text-indigo-200 dark:text-indigo-400 text-[9px] uppercase font-black tracking-widest mb-0.5">Total SKS</span>
                    <span class="block text-3xl font-black"><?= $total_sks ?></span>
                </div>
            </div>

            <!-- Warning if Pending/Rejected -->
            <?php if ($status_validasi !== 'approved'): ?>
                <div class="bg-yellow-50 dark:bg-yellow-900/20 border-l-4 border-yellow-400 dark:border-yellow-600 p-4 mb-6">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <i class="fas fa-exclamation-triangle text-yellow-400"></i>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm text-yellow-700 dark:text-yellow-400">
                                KRS Anda saat ini berstatus <strong><?= ucfirst($status_validasi) ?></strong>.
                                <?php if ($status_validasi === 'pending' && $total_sks > 0): ?>
                                    Anda sedang mengajukan perubahan KRS. Data di bawah adalah KSM yang telah disetujui sebelumnya.
                                <?php elseif ($status_validasi === 'rejected'): ?>
                                    Pengajuan KRS Anda ditolak. Silakan lakukan perbaikan pada menu Edit KRS.
                                <?php else: ?>
                                    Data di bawah ini mungkin belum muncul atau belum final hingga disetujui oleh Administrator.
                                <?php endif; ?>
                            </p>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Table KSM -->
            <div class="overflow-x-auto rounded-lg border border-slate-200 dark:border-slate-800">
                <table class="min-w-full divide-y divide-slate-200 dark:divide-slate-800">
                    <thead class="bg-slate-800 dark:bg-slate-950 text-white">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-bold uppercase tracking-wider w-10">No</th>
                            <th class="px-4 py-3 text-left text-xs font-bold uppercase tracking-wider">Kode</th>
                            <th class="px-4 py-3 text-left text-xs font-bold uppercase tracking-wider">Mata Kuliah</th>
                            <th class="px-4 py-3 text-center text-xs font-bold uppercase tracking-wider">SKS</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white dark:bg-slate-900 divide-y divide-slate-200 dark:divide-slate-800">
                        <?php
                        $no = 1;
                        if (!empty($ksm_data)):
                            foreach ($ksm_data as $row):
                                // Format Jadwal
                                $jadwal = "-";
                                if ($row['hari']) {
                                    $waktu = "";
                                    if ($row['waktu_mulai'] && $row['waktu_selesai']) {
                                        $waktu = date('H:i', strtotime($row['waktu_mulai'])) . " - " . date('H:i', strtotime($row['waktu_selesai']));
                                    }
                                    $ruangan = $row['nama_ruangan'] ? " (" . $row['nama_ruangan'] . ")" : "";
                                    $jadwal = "{$row['hari']}, {$waktu}{$ruangan}";
                                }
                        ?>
                                <tr class="hover:bg-indigo-50 dark:hover:bg-indigo-950/30 transition-colors">
                                    <td class="px-4 py-3 text-xs text-slate-700 dark:text-slate-400 text-center"><?= $no++ ?></td>
                                    <td class="px-4 py-3 text-xs text-slate-600 dark:text-slate-500 font-mono"><?= htmlspecialchars($row['kode_matkul']) ?></td>
                                    <td class="px-4 py-3 text-xs text-slate-800 dark:text-slate-200 font-bold"><?= htmlspecialchars($row['nama_matkul']) ?></td>
                                    <td class="px-4 py-3 text-xs text-slate-700 dark:text-slate-400 text-center font-semibold"><?= htmlspecialchars($row['sks']) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="4" class="px-6 py-8 text-center text-slate-400 dark:text-slate-500 text-xs italic">
                                    Anda belum memprogramkan KRS atau KRS belum disetujui.
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                    <tfoot class="bg-slate-100 dark:bg-slate-900/50">
                        <tr>
                            <td colspan="3" class="px-4 py-3 text-xs font-bold text-slate-700 dark:text-slate-400 text-right uppercase">Total SKS</td>
                            <td class="px-4 py-3 text-xs font-black text-indigo-700 dark:text-indigo-400 text-center"><?= $total_sks ?></td>
                        </tr>
                    </tfoot>
                </table>
            </div>

            <div class="mt-8 hidden print:grid grid-cols-3 gap-8 text-center">
                <div class="col-start-3">
                    <?php
                    $bulan_indo = [
                        'January' => 'Januari',
                        'February' => 'Februari',
                        'March' => 'Maret',
                        'April' => 'April',
                        'May' => 'Mei',
                        'June' => 'Juni',
                        'July' => 'Juli',
                        'August' => 'Agustus',
                        'September' => 'September',
                        'October' => 'Oktober',
                        'November' => 'November',
                        'December' => 'Desember'
                    ];
                    $tgl = date('d') . ' ' . $bulan_indo[date('F')] . ' ' . date('Y');
                    ?>
                    <p class="text-xs text-slate-600 dark:text-slate-400 mb-16">Makassar, <?= $tgl ?></p>
                    <p class="text-xs font-bold text-slate-800 dark:text-slate-200 underline"><?= htmlspecialchars($nama_lengkap) ?></p>
                    <p class="text-[10px] text-slate-500 dark:text-slate-500">NIM. <?= htmlspecialchars($nim) ?></p>
                </div>
            </div>

        </div>
        <?php include '../includes/footer_db.php'; ?>
    </main>

    <?php include '../includes/script_db.php'; ?>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        <?php if (isset($_GET['msg'])): ?>
            <?php if ($_GET['msg'] === 'notif_sent'): ?>
                Swal.fire({
                    icon: 'success',
                    title: 'Berhasil!',
                    text: 'Permintaan validasi telah dikirim ke Admin melalui WhatsApp.',
                    confirmButtonColor: '#3730a3'
                });
            <?php elseif ($_GET['msg'] === 'notif_error'): ?>
                Swal.fire({
                    icon: 'error',
                    title: 'Gagal!',
                    text: 'Gagal mengirim notifikasi ke WhatsApp.',
                    confirmButtonColor: '#3730a3'
                });
            <?php endif; ?>
        <?php endif; ?>
    </script>
</body>

</html>