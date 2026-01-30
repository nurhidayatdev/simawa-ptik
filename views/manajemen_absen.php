<?php
session_start();
include '../koneksi.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../../index");
    exit;
}

$user_id = $_SESSION['user_id'];
$nama_lengkap = $_SESSION['nama_lengkap'];
$foto = $_SESSION['foto'];
$role = $_SESSION['role'];

// Only Admin and Secretary can manage attendance
if ($_SESSION['role'] === 'Mahasiswa' || $_SESSION['role'] === 'Bendahara') {
    include '../includes/alert_role.php';
    exit();
}

/* =====================
   AMBIL PERIODE AKTIF
   ===================== */
$q_periode = mysqli_query($koneksi, "SELECT id FROM periode WHERE status = 'aktif' LIMIT 1");
$periode = mysqli_fetch_assoc($q_periode);
$periode_id = $periode['id'];

// Get all subjects for filter
$query_matkul = "
SELECT DISTINCT
    mk.id AS matkul_id,
    mk.nama_matkul
FROM krs k
JOIN mata_kuliah mk ON k.matkul_id = mk.id
WHERE k.periode_id = $periode_id
ORDER BY mk.nama_matkul ASC
";
$res_matkul = mysqli_query($koneksi, $query_matkul);
$matkul_list = [];
while ($m = mysqli_fetch_assoc($res_matkul)) {
    $matkul_list[] = $m;
}

// Stats Calculation
$q_total_mhs = mysqli_query($koneksi, "SELECT COUNT(*) as total FROM mahasiswa");
$total_students = mysqli_fetch_assoc($q_total_mhs)['total'] ?? 0;

$q_total_absen = mysqli_query($koneksi, "SELECT COUNT(*) as total FROM absensi WHERE periode_id = $periode_id");
$total_entries = mysqli_fetch_assoc($q_total_absen)['total'] ?? 0;

$q_hadir = mysqli_query($koneksi, "SELECT COUNT(*) as total FROM absensi WHERE status = 'Hadir' AND periode_id = $periode_id");
$total_hadir = mysqli_fetch_assoc($q_hadir)['total'] ?? 0;

$attendance_rate = ($total_entries > 0) ? round(($total_hadir / $total_entries) * 100, 1) : 0;

// Filter handling
$f_matkul = $_GET['matkul_id'] ?? ($matkul_list[0]['matkul_id'] ?? '');
$f_pertemuan = $_GET['pertemuan'] ?? '';
$f_status = $_GET['filter'] ?? 'all';

// Fetch Attendance Data for the table
$attendance_data = [];
if ($f_matkul && $f_pertemuan) {
    $where_status = ($f_status !== 'all') ? " AND a.status = '$f_status'" : "";
    $query_abs = "
        SELECT DISTINCT
            m.id AS mahasiswa_id,
            m.nim,
            m.nama_lengkap,
            m.foto,
            a.status
        FROM krs k
        JOIN mahasiswa m ON k.mahasiswa_id = m.id
        LEFT JOIN absensi a 
            ON a.mahasiswa_id = m.id
            AND a.matkul_id = k.matkul_id
            AND a.pertemuan = ?
        WHERE k.matkul_id = ?
        AND k.periode_id = ?
        $where_status
        ORDER BY m.nim ASC
    ";
    $stmt = mysqli_prepare($koneksi, $query_abs);
    mysqli_stmt_bind_param($stmt, "iii", $f_pertemuan, $f_matkul, $periode_id);
    mysqli_stmt_execute($stmt);
    $res_abs = mysqli_stmt_get_result($stmt);
    while ($row = mysqli_fetch_assoc($res_abs)) {
        $attendance_data[] = $row;
    }
}

// Excel export logic
if (isset($_GET['export']) && $_GET['export'] === 'excel' && $f_matkul && $f_pertemuan) {
    $matkul_name = '';
    foreach ($matkul_list as $ml) if ($ml['matkul_id'] == $f_matkul) $matkul_name = $ml['nama_matkul'];

    $filename = 'absensi_' . str_replace(' ', '_', $matkul_name) . '_p' . $f_pertemuan . '.xls';
    header("Content-Type: application/vnd.ms-excel");
    header("Content-Disposition: attachment; filename=\"$filename\"");

    echo '<table border="1">';
    echo '<tr><th colspan="3" style="background-color: #1e1b4b; color: white;">DATA ABSENSI ' . strtoupper($matkul_name) . ' - PERTEMUAN ' . $f_pertemuan . '</th></tr>';
    echo '<tr style="background-color: #f1f5f9;"><th>NIM</th><th>Nama Lengkap</th><th>Status</th></tr>';
    foreach ($attendance_data as $r) {
        echo "<tr><td style=\"mso-number-format:'@'\">{$r['nim']}</td><td>{$r['nama_lengkap']}</td><td>{$r['status']}</td></tr>";
    }
    echo '</table>';
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <title>Manajemen Absensi - PTIK C 2024</title>
    <?php include '../includes/head.php'; ?>
    <style>
        .animate-shimmer {
            background: linear-gradient(90deg, rgba(255, 255, 255, 0) 0%, rgba(255, 255, 255, 0.2) 50%, rgba(255, 255, 255, 0) 100%);
            background-size: 200% 100%;
            animation: shimmer 2s infinite;
        }

        @keyframes shimmer {
            0% {
                background-position: -200% 0;
            }

            100% {
                background-position: 200% 0;
            }
        }
    </style>
</head>

<body class="bg-slate-100">
    <?php include '../includes/menu_db.php'; ?>

    <main class="ml-0 lg:ml-48 pt-20 p-6 flex flex-col min-h-screen">
        <div class="max-w-6xl mx-auto flex-grow w-full">
            <!-- Header section -->
            <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4 mb-8">
                <div>
                    <h3 class="text-2xl font-bold text-slate-800 tracking-tight">Manajemen Absensi</h3>
                    <p class="text-xs font-semibold text-slate-400 uppercase tracking-widest mt-1">Attendance Tracking & Management</p>
                </div>
                <div class="flex gap-3">
                    <button onclick="confirmExport()" class="bg-emerald-500 hover:bg-emerald-600 text-white px-5 py-2.5 rounded-xl text-xs font-bold flex items-center gap-2 transition-all shadow-lg shadow-emerald-500/20 active:scale-95">
                        <i class="fas fa-file-excel"></i> EXPORT DATA
                    </button>
                    <a href="../actions/tambah_absensi.php?matkul_id=<?= $f_matkul ?>&pertemuan=<?= $f_pertemuan ?>" class="bg-indigo-600 hover:bg-indigo-700 text-white px-5 py-2.5 rounded-xl text-xs font-bold flex items-center gap-2 transition-all shadow-lg shadow-indigo-600/20 active:scale-95">
                        <i class="fas fa-plus-circle"></i> INPUT ABSEN
                    </a>
                </div>
            </div>

            <!-- Stats Grid -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 mb-10">
                <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-sm hover:shadow-md transition-all group">
                    <div class="flex items-center gap-4">
                        <div class="w-12 h-12 rounded-xl bg-indigo-50 flex items-center justify-center text-indigo-600 border border-indigo-100 group-hover:bg-indigo-600 group-hover:text-white transition-all">
                            <i class="fas fa-users text-xl"></i>
                        </div>
                        <div>
                            <div class="text-[10px] font-bold text-slate-400 uppercase tracking-widest leading-none mb-1">Total Mahasiswa</div>
                            <div class="text-xl font-bold text-slate-800 leading-none"><?= $total_students ?></div>
                        </div>
                    </div>
                </div>

                <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-sm hover:shadow-md transition-all group">
                    <div class="flex items-center gap-4">
                        <div class="w-12 h-12 rounded-xl bg-emerald-50 flex items-center justify-center text-emerald-600 border border-emerald-100 group-hover:bg-emerald-600 group-hover:text-white transition-all">
                            <i class="fas fa-check-double text-xl"></i>
                        </div>
                        <div>
                            <div class="text-[10px] font-bold text-slate-400 uppercase tracking-widest leading-none mb-1">Total Hadir</div>
                            <div class="text-xl font-bold text-emerald-600 leading-none"><?= $total_hadir ?></div>
                        </div>
                    </div>
                </div>

                <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-sm hover:shadow-md transition-all group">
                    <div class="flex items-center gap-4">
                        <div class="w-12 h-12 rounded-xl bg-amber-50 flex items-center justify-center text-amber-600 border border-amber-100 group-hover:bg-amber-600 group-hover:text-white transition-all">
                            <i class="fas fa-clipboard-list text-xl"></i>
                        </div>
                        <div>
                            <div class="text-[10px] font-bold text-slate-400 uppercase tracking-widest leading-none mb-1">Total Entri</div>
                            <div class="text-xl font-bold text-amber-600 leading-none"><?= $total_entries ?></div>
                        </div>
                    </div>
                </div>

                <div class="bg-gradient-to-br from-indigo-900 to-slate-900 p-5 rounded-2xl shadow-xl shadow-indigo-900/20 border-0 group relative overflow-hidden">
                    <div class="relative z-10 flex items-center gap-4">
                        <div class="w-12 h-12 rounded-xl bg-white/10 flex items-center justify-center text-white border border-white/10">
                            <i class="fas fa-chart-pie text-xl"></i>
                        </div>
                        <div>
                            <div class="text-[10px] font-bold text-indigo-300/60 uppercase tracking-widest leading-none mb-1">Rate Kehadiran</div>
                            <div class="text-xl font-bold text-white leading-none"><?= $attendance_rate ?>%</div>
                        </div>
                    </div>
                    <div class="absolute top-0 right-0 w-24 h-24 bg-white/5 rounded-full -mr-12 -mt-12 transition-transform group-hover:scale-150"></div>
                </div>
            </div>

            <!-- Filter Row -->
            <div class="mb-8">
                <div class="bg-white p-4 rounded-2xl border border-slate-200 shadow-sm relative overflow-hidden">
                    <div class="absolute top-0 left-0 w-1 h-full bg-indigo-600"></div>
                    <h3 class="text-xs font-bold text-slate-800 mb-4 flex items-center gap-2 uppercase tracking-widest">
                        <i class="fas fa-filter text-indigo-600"></i>
                        Filter Data Absensi
                    </h3>

                    <form method="GET" class="grid grid-cols-1 md:grid-cols-4 gap-4 items-end">
                        <div class="md:col-span-1">
                            <label class="text-[10px] font-bold text-slate-400 uppercase tracking-widest block mb-1.5 px-1">Mata Kuliah</label>
                            <select name="matkul_id" onchange="this.form.submit()" class="w-full bg-slate-50 border border-slate-200 text-xs font-semibold text-slate-700 rounded-xl px-4 py-2.5 focus:ring-4 focus:ring-indigo-500/10 focus:border-indigo-500 transition-all cursor-pointer shadow-sm" required>
                                <option value="">-- Pilih Matkul --</option>
                                <?php foreach ($matkul_list as $ml): ?>
                                    <option value="<?= $ml['matkul_id'] ?>" <?= $f_matkul == $ml['matkul_id'] ? 'selected' : '' ?>><?= $ml['nama_matkul'] ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="md:col-span-1">
                            <label class="text-[10px] font-bold text-slate-400 uppercase tracking-widest block mb-1.5 px-1">Pertemuan</label>
                            <select name="pertemuan" onchange="this.form.submit()" class="w-full bg-slate-50 border border-slate-200 text-xs font-semibold text-slate-700 rounded-xl px-4 py-2.5 focus:ring-4 focus:ring-indigo-500/10 focus:border-indigo-500 transition-all cursor-pointer shadow-sm" required>
                                <option value="">-- Pertemuan --</option>
                                <?php for ($i = 1; $i <= 16; $i++): ?>
                                    <option value="<?= $i ?>" <?= $f_pertemuan == $i ? 'selected' : '' ?>>Pertemuan <?= $i ?></option>
                                <?php endfor; ?>
                            </select>
                        </div>

                        <div class="md:col-span-1">
                            <label class="text-[10px] font-bold text-slate-400 uppercase tracking-widest block mb-1.5 px-1">Status Kehadiran</label>
                            <select name="filter" onchange="this.form.submit()" class="w-full bg-slate-50 border border-slate-200 text-xs font-semibold text-slate-700 rounded-xl px-4 py-2.5 focus:ring-4 focus:ring-indigo-500/10 focus:border-indigo-500 transition-all cursor-pointer shadow-sm">
                                <option value="all" <?= $f_status == 'all' ? 'selected' : '' ?>>Semua Status</option>
                                <option value="Hadir" <?= $f_status == 'Hadir' ? 'selected' : '' ?>>Hadir</option>
                                <option value="Izin" <?= $f_status == 'Izin' ? 'selected' : '' ?>>Izin</option>
                                <option value="Sakit" <?= $f_status == 'Sakit' ? 'selected' : '' ?>>Sakit</option>
                                <option value="Alpha" <?= $f_status == 'Alpha' ? 'selected' : '' ?>>Alpha</option>
                            </select>
                        </div>

                        <div class="md:col-span-1">
                            <div class="relative w-full">
                                <i class="fas fa-search absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-[10px]"></i>
                                <input type="text" id="mhsSearchInput" placeholder="Cari nama mahasiswa..." class="w-full bg-slate-50 border border-slate-200 rounded-xl pl-8 pr-4 py-2.5 text-[11px] focus:ring-4 focus:ring-indigo-500/10 focus:border-indigo-500 transition-all shadow-sm font-semibold">
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Attendance Table Card -->
            <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden mb-12">
                <div class="p-4 border-b border-slate-100 flex justify-between items-center bg-slate-50/50">
                    <h4 class="text-xs font-bold text-slate-800 uppercase tracking-widest flex items-center gap-2">
                        <i class="fas fa-table text-slate-400"></i>
                        Daftar Kehadiran Mahasiswa
                    </h4>
                    <?php if ($f_matkul && $f_pertemuan): ?>
                        <span class="text-[10px] font-bold text-indigo-600 uppercase bg-indigo-50 px-3 py-1 rounded-full border border-indigo-100">P<?= $f_pertemuan ?> Terpilih</span>
                    <?php endif; ?>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left" id="attendanceTable">
                        <thead>
                            <tr class="bg-slate-50 border-b border-slate-200">
                                <th class="px-6 py-4 text-[10px] font-bold text-slate-400 uppercase tracking-widest w-16 text-center">No</th>
                                <th class="px-6 py-4 text-[10px] font-bold text-slate-400 uppercase tracking-widest">Mahasiswa</th>
                                <th class="px-6 py-4 text-[10px] font-bold text-slate-400 uppercase tracking-widest text-center">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <?php if (empty($attendance_data)): ?>
                                <tr>
                                    <td colspan="3" class="px-6 py-20 text-center">
                                        <div class="flex flex-col items-center gap-3">
                                            <div class="w-16 h-16 rounded-full bg-slate-50 flex items-center justify-center">
                                                <i class="fas fa-filter text-2xl text-slate-200"></i>
                                            </div>
                                            <p class="text-sm font-semibold text-slate-400">Silakan pilih mata kuliah & pertemuan untuk menampilkan data.</p>
                                        </div>
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php $no = 1;
                                foreach ($attendance_data as $row):
                                    $statusClass = match ($row['status']) {
                                        'Hadir' => 'bg-emerald-100 text-emerald-800 border-emerald-200',
                                        'Izin'  => 'bg-amber-100 text-amber-800 border-amber-200',
                                        'Sakit' => 'bg-sky-100 text-sky-800 border-sky-200',
                                        'Alpha' => 'bg-rose-100 text-rose-800 border-rose-200',
                                        default => 'bg-slate-100 text-slate-400 border-slate-200 italic'
                                    };
                                ?>
                                    <tr class="hover:bg-slate-50/80 transition-all mhs-row">
                                        <td class="px-6 py-4 text-[11px] font-bold text-slate-400 text-center"><?= $no++ ?></td>
                                        <td class="px-6 py-4">
                                            <div class="flex items-center gap-4">
                                                <div class="w-10 h-10 rounded-xl overflow-hidden border border-slate-200 shrink-0 bg-white">
                                                    <?php if (!empty($row['foto'])): ?>
                                                        <img src="<?= profilePhotoUrl($row['foto'], '../') ?>" class="w-full h-full object-cover">
                                                    <?php else: ?>
                                                        <div class="w-full h-full flex items-center justify-center text-slate-200">
                                                            <i class="fas fa-user text-lg"></i>
                                                        </div>
                                                    <?php endif; ?>
                                                </div>
                                                <div>
                                                    <div class="text-[11px] font-bold text-slate-700 leading-tight uppercase mhs-name"><?= htmlspecialchars($row['nama_lengkap']) ?></div>
                                                    <div class="text-[9px] text-slate-400 font-semibold tracking-tighter mt-0.5"><?= $row['nim'] ?></div>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 text-center">
                                            <span class="inline-block px-3 py-1 rounded-full border text-[10px] font-bold uppercase tracking-wider <?= $statusClass ?>">
                                                <?= $row['status'] ?: 'Belum Absen' ?>
                                            </span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <?php include '../includes/footer_db.php'; ?>
    </main>

    <?php include '../includes/script_db.php'; ?>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        // Search functionality
        document.getElementById('mhsSearchInput').addEventListener('keyup', function() {
            let filter = this.value.toLowerCase();
            let rows = document.querySelectorAll('.mhs-row');
            rows.forEach(row => {
                let name = row.querySelector('.mhs-name').innerText.toLowerCase();
                row.style.display = name.includes(filter) ? '' : 'none';
            });
        });

        function confirmExport() {
            const matkulId = "<?= $f_matkul ?>";
            const pertemuan = "<?= $f_pertemuan ?>";

            if (!matkulId || !pertemuan) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Peringatan',
                    text: 'Silakan pilih mata kuliah dan pertemuan terlebih dahulu.',
                    confirmButtonColor: '#3730a3'
                });
                return;
            }

            Swal.fire({
                title: 'Export ke Excel?',
                text: "Data absensi akan diunduh dalam format Excel.",
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#10b981',
                confirmButtonText: 'Ya, Unduh!',
                cancelButtonText: 'Batal'
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = `?export=excel&matkul_id=${matkulId}&pertemuan=${pertemuan}&filter=<?= $f_status ?>`;
                }
            });
        }
    </script>
</body>

</html>