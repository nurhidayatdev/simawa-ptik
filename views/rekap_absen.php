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

/* =====================
   AMBIL PERIODE AKTIF
   ===================== */
$q_periode = mysqli_query($koneksi, "SELECT * FROM periode WHERE status = 'aktif' LIMIT 1");
$periode = mysqli_fetch_assoc($q_periode);
if (!$periode) {
    die("Error: Tidak ada periode aktif. Harap setel periode aktif terlebih dahulu.");
}
$periode_id = $periode['id'];

// Get all subjects
$query_matkul = "
SELECT DISTINCT
    mk.id AS matkul_id,
    mk.nama_matkul,
    mk.kode_matkul
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

// Fetch Matrix Data
$student_list = [];
$attendance_matrix = [];

if ($f_matkul) {
    // 1. Get students in this subject
    $q_students = "
        SELECT m.id, m.nim, m.nama_lengkap, m.foto 
        FROM krs k 
        JOIN mahasiswa m ON k.mahasiswa_id = m.id 
        WHERE k.matkul_id = ? AND k.periode_id = ? 
        ORDER BY m.nim ASC
    ";
    $stmt_st = mysqli_prepare($koneksi, $q_students);
    mysqli_stmt_bind_param($stmt_st, "ii", $f_matkul, $periode_id);
    mysqli_stmt_execute($stmt_st);
    $res_st = mysqli_stmt_get_result($stmt_st);
    while ($row = mysqli_fetch_assoc($res_st)) {
        $student_list[] = $row;
    }

    // 2. Get attendance records for this subject
    $q_abs = "
        SELECT mahasiswa_id, pertemuan, status 
        FROM absensi 
        WHERE matkul_id = ? AND periode_id = ?
    ";
    $stmt_ab = mysqli_prepare($koneksi, $q_abs);
    mysqli_stmt_bind_param($stmt_ab, "ii", $f_matkul, $periode_id);
    mysqli_stmt_execute($stmt_ab);
    $res_ab = mysqli_stmt_get_result($stmt_ab);
    while ($row = mysqli_fetch_assoc($res_ab)) {
        $attendance_matrix[$row['mahasiswa_id']][$row['pertemuan']] = $row['status'];
    }
}

// Excel Export for Matrix
if (isset($_GET['export']) && $_GET['export'] === 'excel') {
    $exp_matkul = (int)$_GET['matkul_id'];
    $q_mn = mysqli_query($koneksi, "SELECT nama_matkul FROM mata_kuliah WHERE id = $exp_matkul");
    $matkul_name = mysqli_fetch_assoc($q_mn)['nama_matkul'] ?? 'Rekap_Absensi';

    $filename = 'rekap_absensi_' . str_replace(' ', '_', $matkul_name) . '.xls';
    header("Content-Type: application/vnd.ms-excel");
    header("Content-Disposition: attachment; filename=\"$filename\"");

    echo '<table border="1">';
    echo '<tr><th colspan="19" style="background-color: #1e1b4b; color: white;">REKAP ABSENSI: ' . strtoupper($matkul_name) . '</th></tr>';
    echo '<tr style="background-color: #f1f5f9;"><th>NIM</th><th>NAMA</th>';
    for ($i = 1; $i <= 16; $i++) echo "<th>P$i</th>";
    echo '</tr>';

    // Fetch data again for export
    mysqli_stmt_execute($stmt_st);
    $res_st_exp = mysqli_stmt_get_result($stmt_st);
    while ($s = mysqli_fetch_assoc($res_st_exp)) {
        echo "<tr><td>{$s['nim']}</td><td>{$s['nama_lengkap']}</td>";
        for ($i = 1; $i <= 16; $i++) {
            $st = $attendance_matrix[$s['id']][$i] ?? '-';
            echo "<td align='center'>$st</td>";
        }
        echo "</tr>";
    }
    echo '</table>';
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <title>Rekap Absensi - PTIK C 2024</title>
    <?php include '../includes/head.php'; ?>
    <style>
        .abs-cell {
            width: 32px;
            min-width: 32px;
            height: 32px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 10px;
            font-weight: 900;
            border-radius: 6px;
            transition: all 0.2s;
        }

        .abs-H {
            background-color: #f0fdf4;
            color: #166534;
            border: 1px solid #bbf7d0;
        }

        .abs-I {
            background-color: #fffbeb;
            color: #92400e;
            border: 1px solid #fde68a;
        }

        .abs-S {
            background-color: #f0f9ff;
            color: #075985;
            border: 1px solid #bae6fd;
        }

        .abs-A {
            background-color: #fef2f2;
            color: #991b1b;
            border: 1px solid #fecaca;
        }

        .abs-null {
            background-color: #f8fafc;
            color: #cbd5e1;
            border: 1px solid #f1f5f9;
        }

        /* Sticky Column for Name */
        .sticky-col {
            position: sticky;
            left: 0;
            background: white;
            z-index: 10;
            border-right: 1px solid #e2e8f0;
        }

        tr:hover .sticky-col {
            background: #f8fafc;
        }
    </style>
</head>

<body class="bg-slate-100">
    <?php include '../includes/menu_db.php'; ?>

    <main class="ml-0 lg:ml-48 pt-20 p-6 flex flex-col min-h-screen">
        <div class="max-w-full mx-auto w-full">
            <!-- Header Section -->
            <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4 mb-8">
                <div>
                    <h3 class="text-2xl font-bold text-slate-800 tracking-tight">Rekap Absensi</h3>
                    <p class="text-xs font-semibold text-slate-400 uppercase tracking-widest mt-1">Monitoring Attendance Matrix (P1-P16)</p>
                </div>
                <div class="flex gap-2">
                    <?php if ($role === 'Administrator Utama' || $role === 'Sekretaris'): ?>
                    <button onclick="confirmExport()" class="bg-emerald-500 hover:bg-emerald-600 text-white px-4 py-2.5 rounded-xl text-[10px] font-bold flex items-center gap-2 transition-all shadow-lg shadow-emerald-500/20 active:scale-95">
                        <i class="fas fa-file-excel"></i> EXPORT REKAP
                    </button>
                    
                        <a href="../actions/tambah_absensi.php" class="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2.5 rounded-xl text-[10px] font-bold flex items-center gap-2 transition-all shadow-lg shadow-indigo-600/20 active:scale-95">
                            <i class="fas fa-plus-circle"></i> INPUT ABSEN
                        </a>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Stats Mini Grid -->
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-8">
                <div class="bg-white p-4 rounded-xl border border-slate-200 shadow-sm">
                    <div class="text-[9px] font-bold text-slate-400 uppercase tracking-widest mb-1">Kehadiran Rata-rata</div>
                    <div class="text-lg font-bold text-indigo-600"><?= $attendance_rate ?>%</div>
                </div>
                <div class="bg-white p-4 rounded-xl border border-slate-200 shadow-sm">
                    <div class="text-[9px] font-bold text-slate-400 uppercase tracking-widest mb-1">Total Input</div>
                    <div class="text-lg font-bold text-slate-800"><?= $total_entries ?> <span class="text-[10px] font-semibold text-slate-400 ml-1">Entri</span></div>
                </div>
                <div class="bg-white p-4 rounded-xl border border-slate-200 shadow-sm">
                    <div class="text-[9px] font-bold text-slate-400 uppercase tracking-widest mb-1">Legenda Status</div>
                    <div class="flex gap-1 mt-1">
                        <span class="w-4 h-4 rounded text-[8px] font-bold flex items-center justify-center abs-H">H</span>
                        <span class="w-4 h-4 rounded text-[8px] font-bold flex items-center justify-center abs-I">I</span>
                        <span class="w-4 h-4 rounded text-[8px] font-bold flex items-center justify-center abs-S">S</span>
                        <span class="w-4 h-4 rounded text-[8px] font-bold flex items-center justify-center abs-A">A</span>
                    </div>
                </div>
                <div class="bg-white p-4 rounded-xl border border-slate-200 shadow-sm">
                    <div class="text-[9px] font-bold text-slate-400 uppercase tracking-widest mb-1">Mata Kuliah Aktif</div>
                    <div class="text-lg font-bold text-slate-800"><?= count($matkul_list) ?></div>
                </div>
            </div>

            <!-- Filter Card -->
            <div class="bg-white p-4 rounded-2xl border border-slate-200 shadow-sm mb-6 relative overflow-hidden">
                <div class="absolute top-0 left-0 w-1 h-full bg-indigo-600"></div>
                <form method="GET" class="flex flex-col md:flex-row gap-4 items-end">
                    <div class="flex-grow">
                        <label class="text-[10px] font-bold text-slate-400 uppercase tracking-widest block mb-1.5 px-1">Pilih Mata Kuliah untuk Melihat Rekap</label>
                        <select name="matkul_id" onchange="this.form.submit()" class="w-full bg-slate-50 border border-slate-200 text-xs font-semibold text-slate-700 rounded-xl px-4 py-2.5 focus:ring-4 focus:ring-indigo-500/10 focus:border-indigo-500 transition-all cursor-pointer shadow-sm">
                            <?php foreach ($matkul_list as $ml): ?>
                                <option value="<?= $ml['matkul_id'] ?>" <?= $f_matkul == $ml['matkul_id'] ? 'selected' : '' ?>><?= $ml['nama_matkul'] ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="w-full md:w-64">
                        <div class="relative w-full">
                            <i class="fas fa-search absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-[10px]"></i>
                            <input type="text" id="mhsSearchInput" placeholder="Cari nama mahasiswa..." class="w-full bg-slate-50 border border-slate-200 rounded-xl pl-8 pr-4 py-2.5 text-[11px] focus:ring-4 focus:ring-indigo-500/10 focus:border-indigo-500 transition-all shadow-sm font-semibold">
                        </div>
                    </div>
                </form>
            </div>

            <!-- Attendance Matrix Card -->
            <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden mb-12">
                <div class="p-4 border-b border-slate-100 flex justify-between items-center bg-slate-50/50">
                    <h4 class="text-xs font-bold text-slate-800 uppercase tracking-widest flex items-center gap-2">
                        <i class="fas fa-columns text-slate-400"></i>
                        Matrix Kehadiran Mahasiswa
                    </h4>
                    <span class="text-[10px] font-bold text-slate-400 uppercase">Periode: <?= $periode['tahun_periode'] ?></span>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse" id="matrixTable">
                        <thead>
                            <tr class="bg-slate-50 border-b border-slate-200">
                                <th class="px-4 py-3 text-[10px] font-bold text-slate-400 uppercase tracking-widest w-12 text-center">No</th>
                                <th class="px-4 py-3 text-[10px] font-bold text-slate-400 uppercase tracking-widest min-w-[250px] sticky-col">Mahasiswa</th>
                                <?php for ($i = 1; $i <= 16; $i++): ?>
                                    <th class="px-1 py-3 text-[9px] font-bold text-slate-400 uppercase text-center w-10">P<?= $i ?></th>
                                <?php endfor; ?>
                                <th class="px-4 py-3 text-[10px] font-bold text-slate-400 uppercase text-center w-20">%</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <?php if (empty($student_list)): ?>
                                <tr>
                                    <td colspan="19" class="px-5 py-20 text-center">
                                        <div class="flex flex-col items-center gap-3">
                                            <div class="w-16 h-16 rounded-full bg-slate-50 flex items-center justify-center">
                                                <i class="fas fa-users-slash text-2xl text-slate-200"></i>
                                            </div>
                                            <p class="text-sm font-semibold text-slate-400 capitalize">Belum ada mahasiswa terdaftar di mata kuliah ini.</p>
                                        </div>
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php $no = 1;
                                foreach ($student_list as $s):
                                    $hadir_count = 0;
                                    $total_p = 0;
                                ?>
                                    <tr class="hover:bg-slate-50/80 transition-colors mhs-row">
                                        <td class="px-4 py-3 text-[11px] font-bold text-slate-400 text-center"><?= $no++ ?></td>
                                        <td class="px-4 py-3 sticky-col">
                                            <div class="flex items-center gap-3">
                                                <div class="w-8 h-8 rounded-lg overflow-hidden border border-slate-200 bg-slate-50 shrink-0">
                                                    <?php if (!empty($s['foto'])): ?>
                                                        <img src="<?= profilePhotoUrl($s['foto'], '../') ?>" class="w-full h-full object-cover">
                                                    <?php else: ?>
                                                        <div class="w-full h-full flex items-center justify-center text-slate-200">
                                                            <i class="fas fa-user text-[10px]"></i>
                                                        </div>
                                                    <?php endif; ?>
                                                </div>
                                                <div class="truncate">
                                                    <div class="text-[10px] font-bold text-slate-700 truncate leading-tight uppercase mhs-name"><?= htmlspecialchars($s['nama_lengkap']) ?></div>
                                                    <div class="text-[8px] text-slate-400 font-semibold tracking-tighter mt-0.5"><?= $s['nim'] ?></div>
                                                </div>
                                            </div>
                                        </td>
                                        <?php for ($i = 1; $i <= 16; $i++):
                                            $st = $attendance_matrix[$s['id']][$i] ?? null;
                                            if ($st) $total_p++;
                                            if ($st === 'Hadir') $hadir_count++;

                                            $short = match ($st) {
                                                'Hadir' => 'H',
                                                'Izin' => 'I',
                                                'Sakit' => 'S',
                                                'Alpha' => 'A',
                                                default => ''
                                            };
                                            $class = $st ? "abs-$short" : "abs-null";
                                        ?>
                                            <td class="px-1 py-2 text-center">
                                                <div class="abs-cell mx-auto <?= $class ?>" title="Pertemuan <?= $i ?>: <?= $st ?: 'Belum Absen' ?>">
                                                    <?= $short ?>
                                                </div>
                                            </td>
                                        <?php endfor; ?>
                                        <?php
                                        $pct = ($total_p > 0) ? round(($hadir_count / $total_p) * 100) : 0;
                                        $pctColor = $pct >= 80 ? 'text-emerald-600' : ($pct >= 50 ? 'text-amber-500' : 'text-rose-500');
                                        ?>
                                        <td class="px-4 py-3 text-center">
                                            <span class="text-[11px] font-bold <?= $pctColor ?>"><?= $pct ?>%</span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <div class="p-4 bg-slate-50 border-t border-slate-100">
                    <div class="flex flex-wrap gap-4 justify-center md:justify-start">
                        <div class="flex items-center gap-1.5">
                            <span class="w-3 h-3 rounded-sm abs-H"></span>
                            <span class="text-[9px] font-bold text-slate-500 uppercase">Hadir</span>
                        </div>
                        <div class="flex items-center gap-1.5">
                            <span class="w-3 h-3 rounded-sm abs-I"></span>
                            <span class="text-[9px] font-bold text-slate-500 uppercase">Izin</span>
                        </div>
                        <div class="flex items-center gap-1.5">
                            <span class="w-3 h-3 rounded-sm abs-S"></span>
                            <span class="text-[9px] font-bold text-slate-500 uppercase">Sakit</span>
                        </div>
                        <div class="flex items-center gap-1.5">
                            <span class="w-3 h-3 rounded-sm abs-A"></span>
                            <span class="text-[9px] font-bold text-slate-500 uppercase">Alpha</span>
                        </div>
                    </div>
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
            if (!matkulId) return;

            Swal.fire({
                title: 'Export Rekap?',
                text: "Data seluruh pertemuan akan diunduh.",
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#10b981',
                confirmButtonText: 'Ya, Unduh!',
                cancelButtonText: 'Batal'
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = `?export=excel&matkul_id=${matkulId}`;
                }
            });
        }
    </script>
</body>

</html>