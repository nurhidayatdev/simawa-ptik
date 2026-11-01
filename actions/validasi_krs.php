<?php
session_start();
include '../koneksi.php';

// Cek Login & Role
if (!isset($_SESSION['user_id'])) {
    header("Location: ../../index.php");
    exit;
}

$role = $_SESSION['role'];
if ($role !== 'Ketua Tingkat') {
    $_SESSION['error'] = 'Akses ditolak! Validasi KRS hanya wewenang Ketua Tingkat.';
    header('Location: ' . ($role === 'Administrator Utama' ? '../dashboard_admin.php' : '../dashboard.php'));
    exit();
}

$user_id = $_SESSION['user_id'];
$nama_lengkap = $_SESSION['nama_lengkap'];
$foto = $_SESSION['foto'] ?? '';

// ==========================================
// 1. PASTIKAN TABLE krs_validasi ADA
// ==========================================
$check_table = mysqli_query($koneksi, "SHOW TABLES LIKE 'krs_validasi'");
if (mysqli_num_rows($check_table) == 0) {
    mysqli_query($koneksi, "CREATE TABLE krs_validasi (
        id INT AUTO_INCREMENT PRIMARY KEY,
        mahasiswa_id INT NOT NULL,
        periode_id INT NOT NULL,
        status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
        tanggal_validasi DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        UNIQUE KEY unique_validasi (mahasiswa_id, periode_id)
    )");
}

// Check krs_temp separately
$check_temp = mysqli_query($koneksi, "SHOW TABLES LIKE 'krs_temp'");
if (mysqli_num_rows($check_temp) == 0) {
    mysqli_query($koneksi, "CREATE TABLE krs_temp (
        id INT AUTO_INCREMENT PRIMARY KEY,
        mahasiswa_id INT NOT NULL,
        matkul_id INT NOT NULL,
        periode_id INT NOT NULL,
        timestamp DATETIME DEFAULT CURRENT_TIMESTAMP,
        KEY idx_mhs_per (mahasiswa_id, periode_id)
    )");
}

// ==========================================
// 2. AMBIL PERIODE AKTIF
// ==========================================
$q_periode = mysqli_query($koneksi, "SELECT id, semester_ke, tahun_periode, semester_nama FROM periode WHERE status = 'aktif' LIMIT 1");
$periode = mysqli_fetch_assoc($q_periode);
if (!$periode) {
    echo "Tidak ada periode aktif.";
    exit;
}
$periode_id = $periode['id'];
$periode_info = $periode['tahun_periode'] . ' ' . $periode['semester_nama'];

// ==========================================
// 3. HANDLE ACTIONS (APPROVE / REJECT)
// ==========================================
if (isset($_POST['action'])) {
    $mhs_id = (int)$_POST['mahasiswa_id'];
    $act = $_POST['action']; // 'approve' or 'reject'
    $status_set = ($act === 'approve') ? 'approved' : 'rejected';

    // Upsert status
    $stmt = mysqli_prepare($koneksi, "INSERT INTO krs_validasi (mahasiswa_id, periode_id, status) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE status = VALUES(status)");
    mysqli_stmt_bind_param($stmt, "iis", $mhs_id, $periode_id, $status_set);

    if (mysqli_stmt_execute($stmt)) {
        // If Approved, Move krs_temp -> krs
        if ($act === 'approve') {
            // Delete old active krs
            mysqli_query($koneksi, "DELETE FROM krs WHERE mahasiswa_id = $mhs_id AND periode_id = $periode_id");
            // Switch: Hapus matkul dari periode lain jika matkul tersebut diajukan di periode ini
            mysqli_query($koneksi, "
                DELETE FROM krs 
                WHERE mahasiswa_id = $mhs_id 
                AND matkul_id IN (
                    SELECT matkul_id FROM krs_temp WHERE mahasiswa_id = $mhs_id AND periode_id = $periode_id
                )
            ");
            // Copy from temp
            mysqli_query($koneksi, "
                INSERT INTO krs (mahasiswa_id, matkul_id, periode_id)
                SELECT mahasiswa_id, matkul_id, periode_id
                FROM krs_temp
                WHERE mahasiswa_id = $mhs_id AND periode_id = $periode_id
                ON DUPLICATE KEY UPDATE periode_id = VALUES(periode_id)
            ");
            // (Optional) Delete from temp or keep history? 
            // Let's keep distinct draft logic: once approved, temp is no longer needed? 
            // Actually, keep it until next edit so we know what was last submitted. 
            // Or delete it to signify 'Synced'. Let's delete it so 'Pending' means 'Has Temp'.
            // However, status is tracked in krs_validasi. So keeping temp is fine.
        }

        header("Location: validasi_krs.php?msg=success_" . $act);
        exit;
    } else {
        header("Location: validasi_krs.php?msg=error");
        exit;
    }
}

// ==========================================
// 4. AMBIL DATA MAHASISWA & KRS
// ==========================================
// Get all mahasiswa who have filled KRS in this period
$query_mhs = "
    SELECT 
        m.id, 
        m.nama_lengkap, 
        m.nim, 
        kv.status as status_validasi,
        kv.tanggal_validasi,
        COUNT(kt.id) as total_matkul,
        SUM(mk.sks) as total_sks
    FROM mahasiswa m
    LEFT JOIN users u ON m.user_id = u.id
    JOIN krs_temp kt ON m.id = kt.mahasiswa_id
    JOIN mata_kuliah mk ON kt.matkul_id = mk.id
    LEFT JOIN krs_validasi kv ON (m.id = kv.mahasiswa_id AND kv.periode_id = $periode_id)
    WHERE kt.periode_id = $periode_id " . getMahasiswaPureWhereClause('m', 'u', true) . " " . getKelasWhereClause('m', true) . "
    GROUP BY m.id
    ORDER BY m.nim ASC
";
$res_mhs = mysqli_query($koneksi, $query_mhs);

$list_mahasiswa = [];
$mhs_ids = [];
while ($row = mysqli_fetch_assoc($res_mhs)) {
    $list_mahasiswa[] = $row;
    $mhs_ids[] = $row['id'];
}

// ==========================================
// 5. AMBIL DETAIL MATKUL UNTUK MODAL
// ==========================================
// We fetch all courses for these students in one go
$krs_details = [];
if (!empty($mhs_ids)) {
    $ids_str = implode(',', $mhs_ids);
    $q_detail = "
        SELECT 
            kt.mahasiswa_id,
            mk.kode_matkul,
            mk.nama_matkul,
            mk.sks,
            mk.semester
        FROM krs_temp kt
        JOIN mata_kuliah mk ON kt.matkul_id = mk.id
        WHERE kt.periode_id = $periode_id
        AND kt.mahasiswa_id IN ($ids_str)
        ORDER BY mk.semester ASC, mk.nama_matkul ASC
    ";
    $res_detail = mysqli_query($koneksi, $q_detail);
    while ($d = mysqli_fetch_assoc($res_detail)) {
        $krs_details[$d['mahasiswa_id']][] = $d;
    }
}

// Encode for JS
$json_details = json_encode($krs_details);

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <title>Validasi KRS - SIMAWA PTIK 2024</title>
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
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>

<body class="bg-slate-100 dark:bg-slate-950 transition-colors">
    <?php include '../includes/menu_db.php'; ?>

    <main class="ml-0 lg:ml-48 pt-20 p-6 flex flex-col min-h-screen">
        <div class="bg-white dark:bg-slate-900/50 rounded-xl border border-slate-200 dark:border-slate-800 p-6 flex-grow w-full transition-all shadow-sm">

            <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-6">
                <div>
                    <h3 class="text-2xl font-bold text-slate-800 dark:text-slate-100">Validasi KRS</h3>
                    <p class="text-xs text-slate-500 dark:text-slate-400 font-medium mt-1">Periode: <span class="text-indigo-600 dark:text-indigo-400"><?= htmlspecialchars($periode_info) ?></span></p>
                </div>
            </div>

            <!-- Table -->
            <div class="overflow-x-auto rounded-lg border border-slate-200 dark:border-slate-800">
                <table class="min-w-full divide-y divide-slate-200 dark:divide-slate-800">
                    <thead class="bg-indigo-900 dark:bg-indigo-950">
                        <tr>
                            <th class="px-3 py-3 text-left text-xs font-bold text-white uppercase tracking-wider">No</th>
                            <th class="px-3 py-3 text-left text-xs font-bold text-white uppercase tracking-wider">Mahasiswa</th>
                            <th class="px-3 py-3 text-center text-xs font-bold text-white uppercase tracking-wider">Total SKS</th>
                            <th class="px-3 py-3 text-center text-xs font-bold text-white uppercase tracking-wider">Status</th>
                            <th class="px-3 py-3 text-center text-xs font-bold text-white uppercase tracking-wider">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white dark:bg-slate-900/50 divide-y divide-slate-200 dark:divide-slate-800">
                        <?php
                        $no = 1;
                        foreach ($list_mahasiswa as $m):
                            $status = $m['status_validasi'] ?? 'pending';
                            $status_color = match ($status) {
                                'approved' => 'bg-green-100 dark:bg-green-900/20 text-green-800 dark:text-green-400 border-green-200 dark:border-green-900/30',
                                'rejected' => 'bg-red-100 dark:bg-red-900/20 text-red-800 dark:text-red-400 border-red-200 dark:border-red-900/30',
                                default => 'bg-yellow-100 dark:bg-yellow-900/20 text-yellow-800 dark:text-yellow-400 border-yellow-200 dark:border-yellow-900/30'
                            };
                            $status_label = match ($status) {
                                'approved' => 'Disetujui',
                                'rejected' => 'Ditolak',
                                default => 'Menunggu'
                            };
                        ?>
                            <tr class="hover:bg-slate-50 dark:hover:bg-indigo-900/20 transition-colors">
                                <td class="px-3 py-3 whitespace-nowrap text-xs text-slate-700 dark:text-slate-300"><?= $no++ ?></td>
                                <td class="px-3 py-3 whitespace-nowrap">
                                    <div class="flex items-center">
                                        <img class="h-8 w-8 rounded-full object-cover mr-3 border border-slate-200 dark:border-slate-700" src="<?= profilePhotoUrl($m['nama_lengkap'], '../') ?>" alt="">
                                        <div>
                                            <div class="text-xs font-bold text-slate-800 dark:text-slate-200"><?= htmlspecialchars($m['nama_lengkap']) ?></div>
                                            <div class="text-[10px] text-slate-500 dark:text-slate-500 font-medium"><?= htmlspecialchars($m['nim']) ?></div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-3 py-3 whitespace-nowrap text-xs text-center font-semibold text-slate-700 dark:text-slate-300">
                                    <?= $m['total_sks'] ?>/24
                                </td>
                                <td class="px-3 py-3 whitespace-nowrap text-center">
                                    <span class="px-2 py-1 rounded-full border text-[10px] font-bold <?= $status_color ?>">
                                        <?= $status_label ?>
                                    </span>
                                </td>
                                <td class="px-3 py-3 whitespace-nowrap text-center">
                                    <button onclick="showDetails(<?= $m['id'] ?>, '<?= htmlspecialchars(addslashes($m['nama_lengkap'])) ?>')"
                                        class="bg-indigo-50 dark:bg-indigo-900/30 text-indigo-600 dark:text-indigo-400 hover:bg-indigo-100 dark:hover:bg-indigo-900/50 px-3 py-1 rounded text-xs font-bold transition-colors mr-1">
                                        <i class="fas fa-eye mr-1"></i> Detail
                                    </button>
                                    <?php if ($status !== 'approved'): ?>
                                        <button onclick="confirmAction(<?= $m['id'] ?>, 'approve')"
                                            class="bg-green-50 dark:bg-green-900/30 text-green-600 dark:text-green-400 hover:bg-green-100 dark:hover:bg-green-900/50 px-3 py-1 rounded text-xs font-bold transition-colors mr-1" title="Setujui">
                                            <i class="fas fa-check"></i>
                                        </button>
                                    <?php endif; ?>
                                    <?php if ($status !== 'rejected'): ?>
                                        <button onclick="confirmAction(<?= $m['id'] ?>, 'reject')"
                                            class="bg-red-50 dark:bg-red-900/30 text-red-600 dark:text-red-400 hover:bg-red-100 dark:hover:bg-red-900/50 px-3 py-1 rounded text-xs font-bold transition-colors" title="Tolak">
                                            <i class="fas fa-times"></i>
                                        </button>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>

                        <?php if (empty($list_mahasiswa)): ?>
                            <tr>
                                <td colspan="5" class="px-6 py-8 text-center text-slate-400 dark:text-slate-500 text-xs italic font-medium">
                                    Belum ada mahasiswa yang mengisi KRS pada periode ini.
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

        </div>
        <?php include '../includes/footer_db.php'; ?>
    </main>

    <!-- Hidden Form for Actions -->
    <form id="actionForm" method="POST" class="hidden">
        <input type="hidden" name="mahasiswa_id" id="form_mhs_id">
        <input type="hidden" name="action" id="form_action">
    </form>

    <?php include '../includes/script_db.php'; ?>
    <script>
        const krsData = <?= $json_details ?>;

        function showDetails(mhsId, nama) {
            const data = krsData[mhsId] || [];
            if (data.length === 0) {
                Swal.fire('Info', 'Tidak ada data mata kuliah.', 'info');
                return;
            }

            const isDark = document.documentElement.classList.contains('dark') || 
                          window.matchMedia('(prefers-color-scheme: dark)').matches;

            let htmlContent = `
                <div class="overflow-x-auto text-left mt-2">
                    <table class="min-w-full divide-y divide-slate-200 dark:divide-slate-700 border border-slate-200 dark:border-slate-700 rounded-lg overflow-hidden">
                        <thead class="bg-slate-50 dark:bg-slate-800/80">
                            <tr>
                                <th class="px-3 py-2 text-xs font-bold text-slate-700 dark:text-slate-200 uppercase tracking-wider">Kode</th>
                                <th class="px-3 py-2 text-xs font-bold text-slate-700 dark:text-slate-200 uppercase tracking-wider">Mata Kuliah</th>
                                <th class="px-3 py-2 text-xs font-bold text-slate-700 dark:text-slate-200 text-center uppercase tracking-wider">SKS</th>
                                <th class="px-3 py-2 text-xs font-bold text-slate-700 dark:text-slate-200 text-center uppercase tracking-wider">Sem</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-200 dark:divide-slate-700 bg-white dark:bg-slate-900">
            `;

            let totalSks = 0;
            data.forEach(d => {
                htmlContent += `
                    <tr class="hover:bg-slate-50 dark:hover:bg-slate-800/50 transition-colors">
                        <td class="px-3 py-2 text-xs text-slate-600 dark:text-slate-400 font-mono">${d.kode_matkul}</td>
                        <td class="px-3 py-2 text-xs text-slate-800 dark:text-slate-200 font-bold">${d.nama_matkul}</td>
                        <td class="px-3 py-2 text-xs text-slate-600 dark:text-slate-400 text-center font-bold">${d.sks}</td>
                        <td class="px-3 py-2 text-xs text-slate-600 dark:text-slate-400 text-center font-bold">${d.semester}</td>
                    </tr>
                `;
                totalSks += parseInt(d.sks);
            });

            htmlContent += `
                        </tbody>
                        <tfoot class="bg-indigo-50 dark:bg-indigo-950/50">
                            <tr>
                                <td colspan="2" class="px-3 py-2 text-xs font-bold text-indigo-900 dark:text-indigo-300 text-right uppercase tracking-wider">Total SKS</td>
                                <td class="px-3 py-2 text-xs font-bold text-indigo-900 dark:text-indigo-300 text-center">${totalSks}</td>
                                <td></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            `;

            Swal.fire({
                title: `<strong class="text-slate-800 dark:text-slate-100">${nama}</strong>`,
                html: htmlContent,
                width: '600px',
                confirmButtonText: 'Tutup',
                confirmButtonColor: '#3730a3',
                background: isDark ? '#0f172a' : '#ffffff',
                color: isDark ? '#f1f5f9' : '#1e293b'
            });
        }

        function confirmAction(mhsId, action) {
            const label = action === 'approve' ? 'Menyetujui' : 'Menolak';
            const color = action === 'approve' ? '#166534' : '#991b1b'; // Green or Red

            Swal.fire({
                title: 'Konfirmasi',
                text: `Apakah Anda yakin ingin ${label} KRS mahasiswa ini?`,
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: `Ya, ${action === 'approve' ? 'Setujui' : 'Tolak'}`,
                confirmButtonColor: color,
                cancelButtonText: 'Batal'
            }).then((result) => {
                if (result.isConfirmed) {
                    document.getElementById('form_mhs_id').value = mhsId;
                    document.getElementById('form_action').value = action;
                    document.getElementById('actionForm').submit();
                }
            });
        }

        <?php if (isset($_GET['msg'])): ?>
            const msg = "<?= $_GET['msg'] ?>";
            if (msg.includes('success')) {
                Swal.fire({
                    icon: 'success',
                    title: 'Berhasil',
                    text: 'Status KRS berhasil diperbarui.',
                    timer: 2000,
                    showConfirmButton: false
                });
            } else if (msg === 'error') {
                Swal.fire({
                    icon: 'error',
                    title: 'Gagal',
                    text: 'Terjadi kesalahan saat menyimpan data.'
                });
            }
        <?php endif; ?>
    </script>
</body>

</html>