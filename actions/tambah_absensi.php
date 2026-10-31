<?php
session_start();
include '../koneksi.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../../index.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$nama_lengkap = $_SESSION['nama_lengkap'];
$foto = $_SESSION['foto'];
$role = $_SESSION['role'];

if ($_SESSION['role'] === 'Mahasiswa' || $_SESSION['role'] === 'Bendahara' || $_SESSION['role'] === 'Ketua Tingkat') {
    include '../includes/alert_role.php';
    exit();
}

// Handle WhatsApp Sending Confirmation
if (isset($_GET['send_wa']) && isset($_SESSION['pending_wa_absensi'])) {
    include_once '../functions/helpers.php';
    $res_wa = sendFonnteMessage($_SESSION['pending_wa_absensi']);
    unset($_SESSION['pending_wa_absensi']);
    
    $matkul_id = $_GET['matkul_id'] ?? '';
    $pertemuan = $_GET['pertemuan'] ?? '';

    if ($res_wa['success']) {
        $_SESSION['success_msg'] = "Rekap berhasil terkirim ke WhatsApp!";
    } else {
        $_SESSION['success_msg'] = "Gagal kirim WA: " . $res_wa['error'];
    }
    header("Location: tambah_absensi.php?matkul_id=$matkul_id&pertemuan=$pertemuan&wa_sent=1");
    exit;
}



/* =====================
   AMBIL PERIODE AKTIF
   ===================== */
$q_periode = mysqli_query($koneksi, "SELECT id FROM periode WHERE status = 'aktif' LIMIT 1");
$periode = mysqli_fetch_assoc($q_periode);
$periode_id = $periode['id'];

$where_mhs_matkul = getKelasWhereClause('m', true);
$query_matkul = "
SELECT DISTINCT
    mk.id AS matkul_id,
    mk.nama_matkul
FROM krs k
JOIN mahasiswa m ON k.mahasiswa_id = m.id
LEFT JOIN users u ON m.user_id = u.id
JOIN mata_kuliah mk ON k.matkul_id = mk.id
WHERE k.periode_id = $periode_id
" . getMahasiswaPureWhereClause('m', 'u', true) . "
$where_mhs_matkul
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
    $target_kelas_id = getKelasScope() ?? 1;

    // Ensure period is active
    if (!$periode_id) {
        die("Error: Tidak ada periode aktif. Harap setel periode aktif terlebih dahulu di menu Data Periode.");
    }

    foreach ($status as $mahasiswa_id => $st) {

        $query = "
        INSERT INTO absensi (mahasiswa_id, matkul_id, pertemuan, status, periode_id, kelas_id)
        VALUES (?, ?, ?, ?, ?, ?)
        ON DUPLICATE KEY UPDATE status = VALUES(status), kelas_id = VALUES(kelas_id)
        ";

        $stmt = mysqli_prepare($koneksi, $query);
        mysqli_stmt_bind_param(
            $stmt,
            "iiisii",
            $mahasiswa_id,
            $matkul_id,
            $pertemuan,
            $st,
            $periode_id,
            $target_kelas_id
        );
        mysqli_stmt_execute($stmt);
    }

    // --- FITUR: WHATSAPP NOTIFICATION VIA FONNTE ---
    include_once '../functions/helpers.php';

    // Fetch matkul name
    $q_matkul_info = mysqli_query($koneksi, "SELECT nama_matkul FROM mata_kuliah WHERE id = '$matkul_id'");
    $matkul_name = mysqli_fetch_assoc($q_matkul_info)['nama_matkul'];

    // Fetch all students in this course with their attendance for this meeting
    $where_mhs_rep = getKelasWhereClause('m', true);
    $where_a_rep = getKelasWhereClause('a', true);
    $q_report = mysqli_query($koneksi, "
        SELECT m.nama_lengkap, IFNULL(a.status, 'Alpha') as status_absen
        FROM krs k
        JOIN mahasiswa m ON k.mahasiswa_id = m.id
        LEFT JOIN absensi a ON a.mahasiswa_id = m.id AND a.matkul_id = '$matkul_id' AND a.pertemuan = '$pertemuan' AND a.periode_id = '$periode_id' $where_a_rep
        WHERE k.matkul_id = '$matkul_id' AND k.periode_id = '$periode_id' $where_mhs_rep
        ORDER BY m.nama_lengkap ASC
    ");

    $hadir = [];
    $izin = [];
    $sakit = [];
    $alpha = [];

    while ($row = mysqli_fetch_assoc($q_report)) {
        $st = $row['status_absen'];
        if ($st === 'Hadir') $hadir[] = $row['nama_lengkap'];
        elseif ($st === 'Izin') $izin[] = $row['nama_lengkap'];
        elseif ($st === 'Sakit') $sakit[] = $row['nama_lengkap'];
        else $alpha[] = $row['nama_lengkap'];
    }

    $wa_msg = "📝 *LAPORAN ABSENSI SIMAWA PTIK 2024*\n";
    $wa_msg .= "📘 *{$matkul_name}*\n";
    $wa_msg .= "🔢 Pertemuan Ke-{$pertemuan}\n\n";

    if (count($alpha) > 0) {
        $count_alpha = count($alpha);
        $wa_msg .= "❌ *ALPHA ({$count_alpha} Orang):*\n";
        foreach ($alpha as $idx => $nama) {
            $wa_msg .= ($idx + 1) . ". " . $nama . "\n";
        }
        $wa_msg .= "\n";
    }

    if (count($izin) > 0 || count($sakit) > 0) {
        $wa_msg .= "⚠️ *KETERANGAN:*\n";
        foreach ($sakit as $nama) {
            $wa_msg .= "• " . $nama . " (Sakit)\n";
        }
        foreach ($izin as $nama) {
            $wa_msg .= "• " . $nama . " (Izin)\n";
        }
        $wa_msg .= "\n";
    }

    if (count($hadir) > 0) {
        $count_hadir = count($hadir);
        $wa_msg .= "✅ *HADIR ({$count_hadir} Orang):*\n";
        foreach ($hadir as $idx => $nama) {
            $wa_msg .= ($idx + 1) . ". " . $nama . "\n";
        }
    } else {
        $wa_msg .= "✅ *HADIR (0 Orang)*\n";
    }

    $wa_msg .= "\n_Terima kasih bagi yang telah hadir tepat waktu!_";

    // --- FITUR: WHATSAPP NOTIFICATION PREPARATION ---
    $_SESSION['pending_wa_absensi'] = $wa_msg;
    $_SESSION['success_msg'] = "Absensi berhasil disimpan!";

    // redirect agar tidak double submit, tambah param tanya_wa
    header("Location: tambah_absensi.php?matkul_id=$matkul_id&pertemuan=$pertemuan&sukses=1&tanya_wa=1");
    exit;
}

?>
<!DOCTYPE html>
<html lang="en">

<head>

    <title>Absensi - SIMAWA PTIK 2024</title>
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

<body class="bg-slate-100 dark:bg-slate-950 transition-colors">
    <?php include '../includes/menu_db.php'; ?>

    <main class="ml-0 lg:ml-48 pt-20 p-6 flex flex-col min-h-screen">
        <div class="bg-white dark:bg-slate-900/50 rounded-xl border border-slate-200 dark:border-slate-800 p-6 flex-grow w-full shadow-sm">
            <!-- Modified this section for better mobile responsiveness -->
            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-6">
                <h3 class="text-xl font-bold text-slate-800 dark:text-slate-100">Data Absensi</h3>
                <div class="flex flex-col sm:flex-row gap-2 w-full sm:w-auto">
                    <form method="GET" class="flex flex-col sm:flex-row gap-2 w-full sm:w-auto">



                        <a href="../views/rekap_absen.php?matkul_id=<?= $_GET['matkul_id'] ?? ''; ?>&pertemuan=<?= $_GET['pertemuan'] ?? ''; ?>" class="bg-indigo-600 text-white px-4 py-2 rounded-lg hover:bg-indigo-800 text-center text-xs">
                            <button type="button">
                                Kembali
                            </button>
                        </a>
                    </form>






                </div>
            </div>
            <form method="GET" class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
                <select name="matkul_id" class="bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-900 dark:text-slate-200 p-2 rounded-lg text-xs focus:ring-1 focus:ring-indigo-500 transition-colors" required onchange="this.form.submit()">
                    <option value="">-- Pilih Mata Kuliah --</option>
                    <?php while ($m = mysqli_fetch_assoc($matkul)): ?>
                        <option value="<?= $m['matkul_id'] ?>"
                            <?= (isset($_GET['matkul_id']) && $_GET['matkul_id'] == $m['matkul_id']) ? 'selected' : '' ?>>
                            <?= $m['nama_matkul'] ?>
                        </option>
                    <?php endwhile; ?>
                </select>

                <select name="pertemuan" class="bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-900 dark:text-slate-200 p-2 rounded-lg text-xs focus:ring-1 focus:ring-indigo-500 transition-colors" required onchange="this.form.submit()">
                    <option value="">-- Pertemuan --</option>
                    <?php for ($i = 1; $i <= 16; $i++): ?>
                        <option value="<?= $i ?>"
                            <?= ($_GET['pertemuan'] ?? '') == $i ? 'selected' : '' ?>>
                            Pertemuan <?= $i ?>
                        </option>
                    <?php endfor; ?>
                </select>

                <label class="sr-only">Filter</label>
                <select name="filter" onchange="this.form.submit()" class="bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-900 dark:text-slate-200 rounded px-4 py-2 text-xs focus:ring-1 focus:ring-indigo-500 transition-colors">
                    <option value="all" <?= ($_GET['filter'] ?? 'all') == 'all' ? 'selected' : '' ?>>Semua</option>
                    <option value="Hadir" <?= ($_GET['filter'] ?? '') == 'Hadir' ? 'selected' : '' ?>>Hadir</option>
                    <option value="Izin" <?= ($_GET['filter'] ?? '') == 'Izin' ? 'selected' : '' ?>>Izin</option>
                    <option value="Sakit" <?= ($_GET['filter'] ?? '') == 'Sakit' ? 'selected' : '' ?>>Sakit</option>
                    <option value="Alpha" <?= ($_GET['filter'] ?? '') == 'Alpha' ? 'selected' : '' ?>>Alpha</option>
                </select>
            </form>

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

                $where_mhs_tbl = getKelasWhereClause('m', true);
                $where_a_tbl = getKelasWhereClause('a', true);
                $query = "
        SELECT DISTINCT
            m.id AS mahasiswa_id,
            m.nim,
            m.nama_lengkap,
            a.status
        FROM krs k
        JOIN mahasiswa m ON k.mahasiswa_id = m.id
        LEFT JOIN users u ON m.user_id = u.id
        LEFT JOIN absensi a 
            ON a.mahasiswa_id = m.id
            AND a.matkul_id = k.matkul_id
            AND a.pertemuan = ?
            $where_a_tbl
        WHERE k.matkul_id = ?
        AND k.periode_id = ?
        " . getMahasiswaPureWhereClause('m', 'u', true) . "
        $where_mhs_tbl
        $where_clause
        ORDER BY m.nim ASC
    ";

                $stmt = mysqli_prepare($koneksi, $query);
                mysqli_stmt_bind_param($stmt, "iii", $pertemuan, $matkul_id, $periode_id);
                mysqli_stmt_execute($stmt);
                $result = mysqli_stmt_get_result($stmt);
            ?>

                <!-- Rest of the content remains the same -->
                <div class="overflow-x-auto rounded-xl border border-slate-200 dark:border-slate-800 shadow-sm">
                    <form method="POST">
                        <input type="hidden" name="matkul_id" value="<?= $matkul_id ?>">
                        <input type="hidden" name="pertemuan" value="<?= $pertemuan ?>">

                        <table class="min-w-full divide-y divide-slate-200 dark:divide-slate-800">
                            <thead class="bg-indigo-900 dark:bg-indigo-950">
                                <tr>
                                    <th class="px-3 py-3 text-left text-xs font-bold text-white uppercase tracking-wider">No</th>
                                    <th class="hidden md:table-cell px-3 py-3 text-left text-xs font-bold text-white uppercase tracking-wider">NIM</th>
                                    <th class="px-3 py-3 text-left text-xs font-bold text-white uppercase tracking-wider">Nama Lengkap</th>
                                    <th class="px-3 py-3 text-left text-xs font-bold text-white uppercase tracking-wider">Status</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white dark:bg-slate-900/50 divide-y divide-slate-200 dark:divide-slate-800">
                                <?php
                                $no = 1;
                                $id = 1;
                                while ($row = mysqli_fetch_assoc($result)) : ?>
                                    <tr class="<?= ($id++ % 2 == 0) ? 'bg-slate-50 dark:bg-slate-800/30' : 'bg-white dark:bg-slate-900/20' ?> hover:bg-indigo-50 dark:hover:bg-indigo-900/40 transition-colors group">
                                        <td class="px-3 py-3 whitespace-nowrap text-xs text-slate-700 dark:text-slate-300"><?= $no++; ?></td>
                                        <td class="hidden md:table-cell px-3 py-3 whitespace-nowrap text-xs text-slate-700 dark:text-slate-300"><?= $row['nim']; ?></td>
                                        <td class="px-3 py-3 text-xs font-medium text-slate-700 dark:text-slate-300 group-hover:text-indigo-700 dark:group-hover:text-indigo-400 transition-colors"><?= $row['nama_lengkap']; ?></td>
                                        <td class="px-3 py-3 whitespace-nowrap text-xs">
                                            <select name="status[<?= $row['mahasiswa_id'] ?>]"
                                                class="bg-white dark:bg-slate-800 border-slate-300 dark:border-slate-700 text-slate-900 dark:text-slate-200 p-1 rounded text-xs focus:ring-1 focus:ring-indigo-500 transition-colors">
                                                <option value="Hadir" <?= $row['status'] == 'Hadir' ? 'selected' : '' ?>>Hadir</option>
                                                <option value="Izin" <?= $row['status'] == 'Izin' ? 'selected' : '' ?>>Izin</option>
                                                <option value="Sakit" <?= $row['status'] == 'Sakit' ? 'selected' : '' ?>>Sakit</option>
                                                <option value="Alpha" <?= $row['status'] == 'Alpha' ? 'selected' : '' ?>>Alpha</option>
                                            </select>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>

                        </table>
                        <div class="mt-8 pt-8 border-t border-slate-200 dark:border-slate-800 flex flex-col items-center">
                            <p class="text-[10px] text-slate-500 dark:text-slate-500 mb-6 italic">Silakan periksa kembali data kehadiran sebelum menekan tombol simpan.</p>
                            <button type="button" onclick="confirmSaveAbsensi()"
                                class="bg-indigo-900 dark:bg-indigo-800 hover:bg-indigo-800 dark:hover:bg-indigo-700 text-white rounded-xl px-10 mb-5 py-3 text-xs font-bold shadow-lg shadow-indigo-900/20 dark:shadow-indigo-950/40 transition-all active:scale-95 flex items-center gap-2">
                                <i class="fa fa-save"></i> Simpan Absensi
                            </button>
                        </div>
                    </form>
                </div>
            <?php endif; ?>
        </div>
        <?php include '../includes/footer_db.php'; ?>
    </main>
    <?php include '../includes/script_db.php'; ?>
    <script>
        // SweetAlert for Saving Absensi
        function confirmSaveAbsensi() {
            Swal.fire({
                title: 'Simpan Absensi?',
                text: "Pastikan data kehadiran sudah sesuai.",
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#3730a3',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Ya, Simpan!',
                cancelButtonText: 'Batal'
            }).then((result) => {
                if (result.isConfirmed) {
                    document.querySelector('form[method="POST"]').submit();
                }
            });
        }

        // Show Success Alert
        <?php if (isset($_GET['sukses'])): ?>
            Swal.fire({
                icon: 'success',
                title: 'Berhasil!',
                text: '<?= $_SESSION['success_msg'] ?? "Data absensi telah berhasil disimpan." ?>',
                confirmButtonColor: '#3730a3',
                showCancelButton: <?= isset($_GET['tanya_wa']) ? 'true' : 'false' ?>,
                confirmButtonText: '<?= isset($_GET['tanya_wa']) ? "Kirim WA" : "OK" ?>',
                cancelButtonText: 'Selesai'
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = 'tambah_absensi.php?send_wa=1&matkul_id=<?= $_GET['matkul_id'] ?? '' ?>&pertemuan=<?= $_GET['pertemuan'] ?? '' ?>';
                } else {
                    // Remove parameter from URL
                    const url = new URL(window.location);
                    url.searchParams.delete('sukses');
                    url.searchParams.delete('tanya_wa');
                    window.history.replaceState({}, document.title, url);
                }
            });
            <?php unset($_SESSION['success_msg']); ?>
        <?php endif; ?>

        // Show WA Sent Alert
        <?php if (isset($_GET['wa_sent'])): ?>
            Swal.fire({
                icon: 'info',
                title: 'WhatsApp',
                text: '<?= $_SESSION['success_msg'] ?? "Proses selesai." ?>',
                confirmButtonColor: '#3730a3'
            }).then(() => {
                const url = new URL(window.location);
                url.searchParams.delete('wa_sent');
                window.history.replaceState({}, document.title, url);
            });
            <?php unset($_SESSION['success_msg']); ?>
        <?php endif; ?>
    </script>
</body>

</html>