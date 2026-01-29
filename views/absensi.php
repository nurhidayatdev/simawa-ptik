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
$q_periode = mysqli_query($koneksi, "SELECT id FROM periode WHERE status = 'aktif' LIMIT 1");
$periode = mysqli_fetch_assoc($q_periode);
$periode_id = $periode['id'];

$query_matkul = "
SELECT DISTINCT
    mk.id AS matkul_id,
    mk.nama_matkul
FROM krs k
JOIN mata_kuliah mk ON k.matkul_id = mk.id
WHERE k.periode_id = $periode_id
ORDER BY mk.nama_matkul ASC
";

$matkul = mysqli_query($koneksi, $query_matkul);

if (!$matkul) {
    die('Query matkul error: ' . mysqli_error($koneksi));
}
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $matkul_id = $_POST['matkul_id'];
    $pertemuan = $_POST['pertemuan'];
    $status    = $_POST['status'];

    foreach ($status as $mahasiswa_id => $st) {

        $query = "
        INSERT INTO absensi (mahasiswa_id, matkul_id, pertemuan, status)
        VALUES (?, ?, ?, ?)
        ON DUPLICATE KEY UPDATE status = ?
        ";

        $stmt = mysqli_prepare($koneksi, $query);
        mysqli_stmt_bind_param(
            $stmt,
            "iiiss",
            $mahasiswa_id,
            $matkul_id,
            $pertemuan,
            $st,
            $st
        );
        mysqli_stmt_execute($stmt);
    }
    // redirect agar tidak double submit
    header("Location: ../actions/tambah_absensi?matkul_id=$matkul_id&pertemuan=$pertemuan");
    exit;
}
// Excel export handler (top of file) - responds to ?export=excel&pertemuan=...&matkul_id=...&filter=...
if (isset($_GET['export']) && $_GET['export'] === 'excel' && isset($_GET['pertemuan']) && isset($_GET['matkul_id'])) {
    $pertemuan = (int) $_GET['pertemuan'];
    $matkul_id = (int) $_GET['matkul_id'];
    $filter = $_GET['filter'] ?? 'all';
    $filter = in_array($filter, ['all', 'Hadir', 'Izin', 'Sakit', 'Alpha']) ? $filter : 'all';

    $where_clause = '';
    if ($filter !== 'all') {
        $where_clause = " AND a.status = '" . mysqli_real_escape_string($koneksi, $filter) . "'";
    }

    $q = "SELECT DISTINCT
            m.id AS mahasiswa_id,
            m.nim,
            m.nama_lengkap,
            COALESCE(a.status, '') AS status
        FROM krs k
        JOIN mahasiswa m ON k.mahasiswa_id = m.id
        LEFT JOIN absensi a 
            ON a.mahasiswa_id = m.id
        AND a.matkul_id = k.matkul_id
            AND a.pertemuan = ?
        WHERE k.matkul_id = ?
        AND k.periode_id = ?
        " . $where_clause . "
        ORDER BY m.nim ASC";

    $stmt = mysqli_prepare($koneksi, $q);
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, 'iii', $pertemuan, $matkul_id, $periode_id);
        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);

        // Resolve mata kuliah name for filename
        $matkul_name = '';
        $mq = mysqli_prepare($koneksi, "SELECT nama_matkul FROM mata_kuliah WHERE id = ?");
        if ($mq) {
            mysqli_stmt_bind_param($mq, 'i', $matkul_id);
            mysqli_stmt_execute($mq);
            $mres = mysqli_stmt_get_result($mq);
            if ($mrow = mysqli_fetch_assoc($mres)) {
                $matkul_name = $mrow['nama_matkul'];
            }
        }

        // sanitize name for filename
        $safe_name = $matkul_name ?: 'matkul_' . $matkul_id;
        $safe_name = preg_replace('/[^A-Za-z0-9 _-]/', '', $safe_name);
        $safe_name = trim($safe_name);
        $safe_name = str_replace(' ', '_', $safe_name);
        $safe_name = substr($safe_name, 0, 80);

        $filename = 'absensi_' . $safe_name . '_p' . $pertemuan . '.xls';

        header("Content-Type: application/vnd.ms-excel");
        header("Content-Disposition: attachment; filename=\"$filename\"");
        header("Pragma: no-cache");
        header("Expires: 0");

        echo '<table border="1">';
        echo '<tr><th colspan="3">DATA ABSENSI ' . strtoupper($matkul_name) . ' - PERTEMUAN ' . $pertemuan . '</th></tr>';
        echo '<tr>
                <th style="background-color: #1e1b4b; color: white;">NIM</th>
                <th style="background-color: #1e1b4b; color: white;">Nama Lengkap</th>
                <th style="background-color: #1e1b4b; color: white;">Status</th>
              </tr>';

        while ($r = mysqli_fetch_assoc($res)) {
            echo '<tr>';
            echo '<td style="mso-number-format:\'@\'">' . $r['nim'] . '</td>';
            echo '<td>' . $r['nama_lengkap'] . '</td>';
            echo '<td>' . $r['status'] . '</td>';
            echo '</tr>';
        }
        echo '</table>';
        exit;
    } else {
        die('Query error: ' . mysqli_error($koneksi));
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>

    <title>Dashboard Absensi - PTIK C 2024</title>
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

    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>

<body class="bg-slate-100">
    <?php include '../includes/menu_db.php'; ?>

    <main class="ml-0 lg:ml-48 pt-20 p-6 flex flex-col min-h-screen">
        <div class="bg-white rounded-lg border border-slate-200 p-6 flex-grow w-full">
            <!-- Modified this section for better mobile responsiveness -->
            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-6">
                <h3 class="text-xl font-bold text-slate-800">Data Absensi</h3>
                <?php if ($role === 'Administrator Utama' || $role === 'Sekretaris'): ?>
                    <div class="flex flex-col sm:flex-row gap-2 w-full sm:w-auto">
                        <form method="GET" class="flex flex-col sm:flex-row gap-4 w-full sm:w-auto">



                            <div class="flex flex-row gap-2 items-center">
                                <a href="../actions/tambah_absensi?matkul_id=<?= $_GET['matkul_id'] ?? ''; ?>&pertemuan=<?= $_GET['pertemuan'] ?? ''; ?>" class="w-full bg-indigo-600 text-white px-4 py-2 rounded-lg hover:bg-indigo-800 text-center text-xs">
                                    Absensi
                                </a>
                                <button type="button" onclick="confirmExport()" class=" bg-green-600 text-white px-3 py-2 rounded-lg hover:bg-green-800 text-center text-xs flex items-center justify-center" title="Export to Excel">
                                    <i class="fas fa-file-excel"></i>
                                </button>
                            </div>
                        </form>
                    </div>
                <?php endif; ?>
            </div>
            <form method="GET" class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">

                <select name="matkul_id" class="border p-2 rounded-lg text-xs" required onchange="this.form.submit()">
                    <option value="">-- Pilih Mata Kuliah --</option>
                    <?php while ($m = mysqli_fetch_assoc($matkul)): ?>
                        <option value="<?= $m['matkul_id'] ?>"
                            <?= (isset($_GET['matkul_id']) && $_GET['matkul_id'] == $m['matkul_id']) ? 'selected' : '' ?>>
                            <?= $m['nama_matkul'] ?>
                        </option>
                    <?php endwhile; ?>
                </select>


                <select name="pertemuan" class="border p-2 rounded-lg text-xs" required onchange="this.form.submit()">
                    <option value="">-- Pertemuan --</option>
                    <?php for ($i = 1; $i <= 16; $i++): ?>
                        <option value="<?= $i ?>"
                            <?= ($_GET['pertemuan'] ?? '') == $i ? 'selected' : '' ?>>
                            Pertemuan <?= $i ?>
                        </option>
                    <?php endfor; ?>
                </select>

                <label class="sr-only">Filter</label>
                <select name="filter" onchange="this.form.submit()" class="border rounded px-4 py-2 text-xs">
                    <option value="all" <?= ($_GET['filter'] ?? 'all') == 'all' ? 'selected' : '' ?>>Semua</option>
                    <option value="Hadir" <?= ($_GET['filter'] ?? '') == 'Hadir' ? 'selected' : '' ?>>Hadir</option>
                    <option value="Izin" <?= ($_GET['filter'] ?? '') == 'Izin' ? 'selected' : '' ?>>Izin</option>
                    <option value="Sakit" <?= ($_GET['filter'] ?? '') == 'Sakit' ? 'selected' : '' ?>>Sakit</option>
                    <option value="Alpha" <?= ($_GET['filter'] ?? '') == 'Alpha' ? 'selected' : '' ?>>Alpha</option>
                </select>
                <!-- tombol Tampilkan dihapus; pilihan akan auto-submit -->
            </form>

            <?php
            $matkul_id = null;
            $pertemuan = null;
            if (isset($_GET['matkul_id'], $_GET['pertemuan'])) :

                $matkul_id = (int) $_GET['matkul_id'];
                $pertemuan = (int) $_GET['pertemuan'];

                $filter = $_GET['filter'] ?? 'all';
                $filter = in_array($filter, ['all', 'Hadir', 'Izin', 'Sakit', 'Alpha']) ? $filter : 'all';

                $where_clause = '';
                if ($filter !== 'all') {
                    $where_clause = " AND a.status = '$filter'";
                }

                $query = "
        SELECT DISTINCT
            m.id AS mahasiswa_id,
            m.nim,
            m.nama_lengkap,
            a.status
        FROM krs k
        JOIN mahasiswa m ON k.mahasiswa_id = m.id
        LEFT JOIN absensi a 
            ON a.mahasiswa_id = m.id
            AND a.matkul_id = k.matkul_id
            AND a.pertemuan = ?
        WHERE k.matkul_id = ?
        AND k.periode_id = ?
        $where_clause
        ORDER BY m.nim ASC
    ";

                $stmt = mysqli_prepare($koneksi, $query);
                mysqli_stmt_bind_param($stmt, "iii", $pertemuan, $matkul_id, $periode_id);
                mysqli_stmt_execute($stmt);
                $result = mysqli_stmt_get_result($stmt);
            ?>


                <!-- Rest of the content remains the same -->
                <div class="overflow-x-auto">
                    <form method="POST">
                        <input type="hidden" name="matkul_id" value="<?= $matkul_id ?>">
                        <input type="hidden" name="pertemuan" value="<?= $pertemuan ?>">
                        <table class="min-w-full divide-y divide-slate-200">
                            <thead class="bg-indigo-900">
                                <tr>
                                    <th class="px-3 py-3 text-left text-xs font-bold text-white uppercase tracking-wider">
                                        No</th>
                                    <th class="hidden md:table-cell px-3 py-3 text-left text-xs font-bold text-white uppercase tracking-wider">
                                        NIM</th>
                                    <th class="px-3 py-3 text-left text-xs font-bold text-white uppercase tracking-wider">
                                        Nama Lengkap</th>
                                    <th class="px-3 py-3 text-left text-xs font-bold text-white uppercase tracking-wider">
                                        Status</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-slate-200">
                                <?php
                                $no = 1;
                                $id = 1;
                                while ($row = mysqli_fetch_assoc($result)) : ?>
                                    <tr class="<?= ($id++ % 2 == 0) ? 'bg-slate-100' : 'bg-white' ?> hover:bg-indigo-100">

                                        <td class="px-3 py-2 whitespace-nowrap text-xs text-slate-800"><?= $no++; ?></td>
                                        <td class="hidden md:table-cell px-3 py-2 whitespace-nowrap text-xs text-slate-800"><?= $row['nim']; ?></td>
                                        <td class="px-3 py-2 text-xs text-slate-800"><?= $row['nama_lengkap']; ?></td>
                                        <td class="px-3 py-2 whitespace-nowrap text-xs text-slate-800">

                                            <?php
                                            $jkStatus = match ($row['status']) {
                                                'Hadir' => 'bg-emerald-100 text-emerald-800 border-emerald-200', // Hijau
                                                'Izin'  => 'bg-amber-100 text-amber-800 border-amber-200',     // Kuning/Oranye
                                                'Sakit' => 'bg-sky-100 text-sky-800 border-sky-200',           // Biru
                                                'Alpha' => 'bg-rose-100 text-rose-800 border-rose-200',
                                                default => 'bg-slate-100 text-slate-800 border-slate-200'       // Default/Unknown/NULL
                                            };
                                            ?>

                                            <span class="px-1.5 py-0.5 rounded-full border text-[10px] font-semibold <?= $jkStatus ?>"><?= ucfirst($row['status']) ?></span>
                                        </td>


                                    </tr>
                                <?php endwhile; ?>
                            </tbody>

                        </table>
                    </form>
                </div>
            <?php endif; ?>
            <?php if (!$matkul_id) : ?>
                <div class="p-3 text-xs text-slate-600">Pilih mata kuliah untuk melihat absensi yang tersimpan</div>
            <?php endif; ?>
        </div>
        <?php include '../includes/footer_db.php'; ?>
    </main>
    <?php include '../includes/script_db.php'; ?>
    <script>
        // SweetAlert for Export Excel
        function confirmExport() {
            const matkulId = "<?= $_GET['matkul_id'] ?? '' ?>";
            const pertemuan = "<?= $_GET['pertemuan'] ?? '' ?>";
            const filter = "<?= $_GET['filter'] ?? 'all' ?>";

            if (!matkulId || !pertemuan) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Peringatan',
                    text: 'Silakan pilih mata kuliah and pertemuan terlebih dahulu.',
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
                cancelButtonColor: '#d33',
                confirmButtonText: 'Ya, Unduh!',
                cancelButtonText: 'Batal'
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = `?export=excel&matkul_id=${matkulId}&pertemuan=${pertemuan}&filter=${filter}`;
                }
            });
        }
    </script>
</body>

</html>