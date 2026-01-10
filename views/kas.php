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

// CSV export handler (top of file) - responds to ?export=csv&minggu_ke=...&filter=...
if (isset($_GET['export']) && $_GET['export'] === 'csv' && isset($_GET['minggu_ke'])) {
    $minggu = mysqli_real_escape_string($koneksi, $_GET['minggu_ke']);
    $filter = $_GET['filter'] ?? 'all';
    $filter = in_array($filter, ['all', 'Lunas', 'Belum']) ? $filter : 'all';

    $where_clause = '';
    if ($filter === 'Lunas') {
        $where_clause = "AND k.status = 'Lunas'";
    } elseif ($filter === 'Belum') {
        $where_clause = "AND (k.status IS NULL OR k.status != 'Lunas')";
    }

    $q = "SELECT m.nim, m.nama_lengkap, k.minggu_ke, k.status, k.tanggal_bayar
          FROM kas k
          JOIN mahasiswa m ON k.mahasiswa_id = m.id
          WHERE k.minggu_ke = '$minggu' $where_clause
          ORDER BY m.nim ASC";

    $res = mysqli_query($koneksi, $q);

    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=kas_minggu_' . $minggu . '.csv');
    $out = fopen('php://output', 'w');
    fputcsv($out, ['NIM', 'Nama Lengkap', 'Minggu', 'Status', 'Tanggal Bayar']);
    while ($r = mysqli_fetch_assoc($res)) {
        $tanggal = !empty($r['tanggal_bayar']) ? date('d/m/Y', strtotime($r['tanggal_bayar'])) : '';
        fputcsv($out, [$r['nim'], $r['nama_lengkap'], $r['minggu_ke'], $r['status'], $tanggal]);
    }
    fclose($out);
    exit;
}

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Dashboard Resmi Kelas PTIK C 2024">
    <meta name="theme-color" content="#1e1b4b" />
    <title>Data Kas - PTIK C 2024</title>
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
                <h3 class="text-xl font-bold text-slate-800">Data Kas Mingguan</h3>
                <?php if ($role === 'Administrator Utama' || $role === 'Bendahara'): ?>
                    <div class="flex-col sm:flex-row gap-2 w-full sm:w-auto">
                        <form method="GET" class="flex flex-col sm:flex-row gap-3 w-full sm:w-auto">
                            <div class="flex flex-row gap-2 items-center">




                                <a href="../actions/tambah_kas_minggu?minggu_ke=<?= $_GET['minggu_ke'] ?? '' ?>" class="whitespace-nowrap bg-blue-600 text-white px-3 py-2 rounded-lg hover:bg-blue-800 text-center text-xs flex items-center justify-center w-full">Input Minggu</a>

                                <a href="../actions/tambah_kas_individu" class="whitespace-nowrap bg-indigo-600 text-white px-3 py-2 rounded-lg hover:bg-indigo-800 text-center text-xs flex items-center justify-center w-full">Input Individu</a>

                                <?php if (isset($_GET['minggu_ke']) && !empty($_GET['minggu_ke'])): ?>
                                    <button type="button" onclick="confirmReminderKas('<?= $_GET['minggu_ke'] ?>')" class="bg-blue-600 text-white px-3 py-2 rounded-lg hover:bg-blue-800 text-center text-xs flex items-center justify-center" title="Kirim Reminder Telegram">
                                        <i class="fa fa-paper-plane"></i>
                                    </button>
                                <?php endif; ?>

                                <a href="?export=csv&minggu_ke=<?= $_GET['minggu_ke'] ?? '' ?>&filter=<?= urlencode($_GET['filter'] ?? 'all') ?>" class="whitespace-nowrap bg-green-600 text-white px-3 py-2 rounded-lg hover:bg-green-800 text-center text-xs flex items-center justify-center"><i class="fas fa-file-csv"></i></a>


                        </form>
                    </div>
            </div>
        <?php endif; ?>
        </div>

        <form method="GET" class="grid grid-cols-2 gap-4 mb-6">

            <select name="minggu_ke" required onchange="this.form.submit()" class="border rounded px-4 py-2 text-xs">
                <option value="">-- Pilih Minggu --</option>
                <?php for ($i = 1; $i <= 16; $i++): ?>
                    <option value="<?= $i ?>" <?= ($_GET['minggu_ke'] ?? '') == $i ? 'selected' : '' ?>>Minggu <?= $i ?></option>
                <?php endfor; ?>
            </select>

            <label class="sr-only">Filter</label>
            <select name="filter" onchange="this.form.submit()" class="border rounded px-4 py-2 text-xs">
                <option value="all" <?= ($_GET['filter'] ?? 'all') == 'all' ? 'selected' : '' ?>>Semua</option>
                <option value="Lunas" <?= ($_GET['filter'] ?? '') == 'Lunas' ? 'selected' : '' ?>>Lunas</option>
                <option value="Belum" <?= ($_GET['filter'] ?? '') == 'Belum' ? 'selected' : '' ?>>Belum</option>
            </select>


        </form>

        <?php
        if (isset($_GET['minggu_ke'])):

            $minggu = $_GET['minggu_ke'];

            $filter = $_GET['filter'] ?? 'all';
            $filter = in_array($filter, ['all', 'Lunas', 'Belum']) ? $filter : 'all';

            $where_clause = '';
            if ($filter === 'Lunas') {
                $where_clause = " AND k.status = 'Lunas'";
            } elseif ($filter === 'Belum') {
                $where_clause = " AND (k.status IS NULL OR k.status != 'Lunas')";
            }

            $mhs = mysqli_query($koneksi, "

    SELECT
        m.id AS mahasiswa_id,
        m.nim,
        m.nama_lengkap,
        k.minggu_ke,
        k.status,
        k.tanggal_bayar
    FROM kas k
    JOIN mahasiswa m ON k.mahasiswa_id = m.id
    WHERE k.minggu_ke = '$minggu' $where_clause
    ORDER BY m.nim ASC

");
        ?>

            <!-- Rest of the content remains the same -->
            <div class="overflow-x-auto">
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
                            <th class="px-3 py-3 text-left text-xs font-bold text-white uppercase tracking-wider">
                                Tanggal Bayar</th>
                            <th class="px-3 py-3 text-center text-xs font-bold text-white uppercase tracking-wider">
                                Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-slate-200">
                        <?php
                        $no = 1;
                        $id = 1;
                        while ($row = mysqli_fetch_assoc($mhs)) : ?>
                            <tr class="<?= ($id++ % 2 == 0) ? 'bg-slate-100' : 'bg-white' ?> hover:bg-indigo-100">

                                <td class="px-3 py-2 whitespace-nowrap text-xs text-slate-800"><?= $no++; ?></td>
                                <td class="hidden md:table-cell px-3 py-2 whitespace-nowrap text-xs text-slate-800"><?= $row['nim']; ?></td>
                                <td class="px-3 py-2 text-xs text-slate-800"><?= $row['nama_lengkap']; ?></td>
                                <td class="px-3 py-2 whitespace-nowrap text-xs text-slate-800">
                                    <?php
                                    $statusColor = ($row['status'] === 'Lunas') ? 'bg-green-100 text-green-800 border-green-200' : 'bg-yellow-100 text-yellow-800 border-yellow-200';
                                    ?>
                                    <span class="px-1.5 py-0.5 rounded-full border text-[10px] font-semibold <?= $statusColor ?>"><?= ucfirst($row['status']) ?></span>
                                </td>
                                <?php
                                $tanggal = '-';
                                if (!empty($row['tanggal_bayar']) && $row['tanggal_bayar'] !== '0000-00-00') {
                                    $d = date_create($row['tanggal_bayar']);
                                    if ($d) $tanggal = date_format($d, 'd/m/Y');
                                }
                                ?>
                                <td class="px-3 py-2 whitespace-nowrap text-xs text-slate-800"><?= $tanggal; ?></td>
                                <td class="px-3 py-2 whitespace-nowrap text-center text-xs">
                                    <a href="../actions/tambah_kas_individu?mahasiswa_id=<?= $row['mahasiswa_id'] ?>" class="text-indigo-600 hover:text-indigo-900" title="Input Individu">
                                        <i class="fa fa-user-edit"></i>
                                    </a>
                                </td>


                            </tr>
                        <?php endwhile; ?>
                    </tbody>

                </table>

            </div>
        <?php endif; ?>
        </div>
        </div>
        <?php include '../includes/footer_db.php'; ?>
    </main>
    <?php include '../includes/script_db.php'; ?>
    <script>
        // SweetAlert2 Session Messages
        <?php if (isset($_SESSION['success'])): ?>
            Swal.fire({
                icon: 'success',
                title: 'Berhasil!',
                text: '<?= $_SESSION['success'] ?>',
                timer: 3000,
                showConfirmButton: false
            });
            <?php unset($_SESSION['success']); ?>
        <?php endif; ?>

        <?php if (isset($_SESSION['error'])): ?>
            Swal.fire({
                icon: 'error',
                title: 'Gagal!',
                text: '<?= $_SESSION['error'] ?>'
            });
            <?php unset($_SESSION['error']); ?>
        <?php endif; ?>

        <?php if (isset($_SESSION['info'])): ?>
            Swal.fire({
                icon: 'info',
                title: 'Informasi',
                text: '<?= $_SESSION['info'] ?>'
            });
            <?php unset($_SESSION['info']); ?>
        <?php endif; ?>

        // SweetAlert for Success GET Parameter
        <?php if (isset($_GET['success'])): ?>
            Swal.fire({
                icon: 'success',
                title: 'Berhasil!',
                text: 'Data kas mingguan telah berhasil diperbarui.',
                confirmButtonColor: '#3730a3'
            }).then(() => {
                // Remove parameter from URL
                const url = new URL(window.location);
                url.searchParams.delete('success');
                window.history.replaceState({}, document.title, url);
            });
        <?php endif; ?>

        // SweetAlert Confirmation for Telegram Reminder
        function confirmReminderKas(minggu) {
            Swal.fire({
                title: 'Kirim Reminder Kas?',
                text: "Daftar mahasiswa yang belum lunas minggu ini akan dikirim ke grup.",
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Ya, Kirim!',
                cancelButtonText: 'Batal'
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = '../functions/kirim_kas?minggu_ke=' + minggu;
                }
            });
        }
    </script>
</body>

</html>