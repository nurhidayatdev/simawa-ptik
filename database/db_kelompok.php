<?php
session_start();
include '../koneksi.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../../index.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$nama_lengkap = $_SESSION['nama_lengkap'];
$foto = $_SESSION['foto'] ?? '';
$role = $_SESSION['role'];

if ($role === 'Administrator Utama') {
    $_SESSION['error'] = 'Akses ditolak! Administrator Utama tidak mengelola kelompok kelas.';
    header('Location: ../dashboard_admin.php');
    exit;
}

$search = $_GET['search'] ?? '';

// Ambil periode aktif untuk filter data terbaru saja (opsional, tapi bagus untuk performa)
$q_periode = mysqli_query($koneksi, "SELECT id FROM periode WHERE status = 'aktif' LIMIT 1");
$periode = mysqli_fetch_assoc($q_periode);
$periode_id = $periode['id'] ?? 0;

$query_base = "SELECT k.matkul_id, k.dosen, k.keterangan, mk.nama_matkul, 
               COUNT(*) as jml_kelompok, 
               MIN(k.created_at) as tgl_dibuat
               FROM kelompok k 
               JOIN mata_kuliah mk ON k.matkul_id = mk.id 
               WHERE k.periode_id = $periode_id ";

if (!empty($search)) {
    $stmt = mysqli_prepare(
        $koneksi,
        $query_base . " AND (mk.nama_matkul LIKE ? OR k.dosen LIKE ? OR k.keterangan LIKE ?) GROUP BY k.matkul_id, k.dosen, k.keterangan ORDER BY mk.nama_matkul ASC"
    );
    $like = "%$search%";
    mysqli_stmt_bind_param($stmt, "sss", $like, $like, $like);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
} else {
    $result = mysqli_query($koneksi, $query_base . " GROUP BY k.matkul_id, k.dosen, k.keterangan ORDER BY mk.nama_matkul ASC");
}

// If AJAX live-search request, return only the table rows
if (isset($_GET['ajax']) && $_GET['ajax'] == '1') {
    $no = 1;
    $id = 1;
    while ($row = mysqli_fetch_assoc($result)) {
        $isEven = ($id++ % 2 == 0);
        $rowClass = ($isEven ? 'bg-slate-50 dark:bg-slate-800/30' : 'bg-white dark:bg-slate-900') . ' hover:bg-indigo-50 dark:hover:bg-indigo-900/10 transition-colors';
        
        // Formatting time

        echo '<tr class="' . $rowClass . '">';
        echo '<td class="px-3 py-2 whitespace-nowrap text-xs text-slate-800 dark:text-slate-200 font-medium">' . ($no++) . '</td>';
        echo '<td class="px-3 py-2 whitespace-nowrap text-xs text-slate-800 dark:text-slate-200 font-bold">' . htmlspecialchars($row['nama_matkul']) . '</td>';
        echo '<td class="px-3 py-2 whitespace-nowrap text-xs text-indigo-600 dark:text-indigo-400 font-bold">' . htmlspecialchars($row['jml_kelompok']) . ' Kelompok</td>';
        echo '<td class="px-3 py-2 whitespace-nowrap text-xs text-slate-800 dark:text-slate-200">' . htmlspecialchars($row['dosen']) . '</td>';
        echo '<td class="px-3 py-2 whitespace-nowrap text-xs text-slate-800 dark:text-slate-200 text-center font-medium">' . date('d M Y, H:i', strtotime($row['tgl_dibuat'])) . '</td>';
        echo '<td class="px-3 py-2 whitespace-nowrap text-xs text-slate-500 dark:text-slate-400">' . htmlspecialchars($row['keterangan']) . '</td>';
        if ($role === 'Ketua Tingkat'):
            echo '<td class="px-3 py-2 whitespace-nowrap text-sm text-center">';
            echo '<div class="flex items-center justify-center gap-3">';
            echo '<a href="../actions/hapus.php?tabel=set_kelompok&matkul_id=' . $row['matkul_id'] . '&dosen=' . urlencode($row['dosen']) . '&keterangan=' . urlencode($row['keterangan']) . '" title="Hapus Seluruh Set Kelompok" class="text-red-600 dark:text-red-400 hover:text-red-900 dark:hover:text-red-200"><i class="fa fa-trash"></i></a>';
            echo '</div>';
            echo '</td>';
        endif;
        echo '</tr>';
    }
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">

<head>

    <title>Data Kelompok - SIMAWA PTIK 2024</title>
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

</head>

<body class="bg-slate-100 dark:bg-slate-950 transition-colors duration-300">
    <?php include '../includes/menu_db.php'; ?>

    <main class="ml-0 lg:ml-48 pt-20 p-6 flex flex-col min-h-screen">
        <div class="bg-white dark:bg-slate-900 rounded-lg border border-slate-200 dark:border-slate-800 p-6 flex-grow w-full shadow-sm">
            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-6">
                <div>
                    <h3 class="text-xl font-bold text-slate-800 dark:text-slate-100">Data Kelompok</h3>
                    <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">Kelola data pembagian kelompok mahasiswa yang tersimpan.</p>
                </div>
                <div class="flex-col sm:flex-row gap-2 w-full sm:w-auto">
                    <form method="GET" class="w-full">
                        <div class="flex flex-row gap-2 items-center">
                            <div class="relative flex-1 min-w-0">
                                <input id="searchInput" type="text" name="search" value="<?php echo htmlspecialchars($_GET['search'] ?? ''); ?>" placeholder="Cari Kelompok..." class="w-full pl-9 pr-3 py-2 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 text-xs text-slate-700 dark:text-slate-200 transition-all">
                                <i class="fa fa-search absolute left-3 top-2.5 text-slate-400 dark:text-slate-500 text-xs"></i>
                            </div>

                        </div>
                    </form>
                </div>
            </div>

            <div class="overflow-x-auto rounded-xl border border-slate-200 dark:border-slate-800">
                <table class="min-w-full divide-y divide-slate-200 dark:divide-slate-800">
                    <thead class="bg-indigo-900 dark:bg-slate-800">
                        <tr>
                            <th class="px-3 py-3 text-left text-[10px] font-bold text-white uppercase tracking-widest">No</th>
                            <th class="px-3 py-3 text-left text-[10px] font-bold text-white uppercase tracking-widest">Mata Kuliah</th>
                            <th class="px-3 py-3 text-left text-[10px] font-bold text-white uppercase tracking-widest">Kelompok</th>
                            <th class="px-3 py-3 text-left text-[10px] font-bold text-white uppercase tracking-widest">Dosen</th>
                            <th class="px-3 py-3 text-[10px] font-bold text-white uppercase tracking-widest text-center">Waktu Dibuat</th>
                            <th class="px-3 py-3 text-left text-[10px] font-bold text-white uppercase tracking-widest">Keterangan</th>
                            <?php if ($role === 'Ketua Tingkat'): ?>
                                <th class="px-3 py-3 text-[10px] font-bold text-white uppercase tracking-widest text-center">Actions</th>
                            <?php endif; ?>
                        </tr>
                    </thead>
                    <tbody class="bg-white dark:bg-slate-900 divide-y divide-slate-200 dark:divide-slate-800">
                        <?php
                        $no = 1;
                        $id = 1;
                        while ($row = mysqli_fetch_assoc($result)) : 
                            $isEven = ($id++ % 2 == 0);
                            $rowClass = ($isEven ? 'bg-slate-50/50 dark:bg-slate-800/20' : 'bg-white dark:bg-slate-900') . ' hover:bg-indigo-50 dark:hover:bg-indigo-900/10 transition-colors';
                        ?>
                            <tr class="<?= $rowClass ?>">
                                <td class="px-3 py-2 whitespace-nowrap text-xs text-slate-800 dark:text-slate-200 font-medium"><?= $no++; ?></td>
                                <td class="px-3 py-2 whitespace-nowrap text-xs text-slate-800 dark:text-slate-200 font-bold"><?= htmlspecialchars($row['nama_matkul']); ?></td>
                                <td class="px-3 py-2 whitespace-nowrap text-xs font-bold text-indigo-600 dark:text-indigo-400"><?= htmlspecialchars($row['jml_kelompok']); ?> Kelompok</td>
                                <td class="px-3 py-2 whitespace-nowrap text-xs text-slate-800 dark:text-slate-200"><?= htmlspecialchars($row['dosen']); ?></td>
                                <td class="px-3 py-2 whitespace-nowrap text-xs text-slate-800 dark:text-slate-200 text-center font-medium"><?= date('d M Y, H:i', strtotime($row['tgl_dibuat'])); ?></td>
                                <td class="px-3 py-2 whitespace-nowrap text-xs text-slate-500 dark:text-slate-400 capitalize"><?= htmlspecialchars($row['keterangan'] ?: '-'); ?></td>
                                <?php if ($role === 'Ketua Tingkat'): ?>
                                    <td class="px-3 py-2 whitespace-nowrap text-sm text-center">
                                        <div class="flex items-center justify-center gap-3">
                                            <a href="../actions/hapus.php?tabel=set_kelompok&matkul_id=<?= $row['matkul_id']; ?>&dosen=<?= urlencode($row['dosen']); ?>&keterangan=<?= urlencode($row['keterangan']); ?>" title="Hapus Seluruh Set Kelompok" class="text-red-600 dark:text-red-400 hover:text-red-900 dark:hover:text-red-200 transition-colors"><i class="fa fa-trash"></i></a>
                                        </div>
                                    </td>
                                <?php endif; ?>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php include '../includes/footer_db.php'; ?>
    </main>
    <?php include '../includes/script_db.php'; ?>

    <script>
        // Live search via AJAX
        (function() {
            const input = document.getElementById('searchInput');
            if (!input) return;
            let timer = null;
            input.addEventListener('input', function() {
                clearTimeout(timer);
                timer = setTimeout(() => {
                    const q = encodeURIComponent(input.value || '');
                    const url = window.location.pathname + '?search=' + q + '&ajax=1';
                    fetch(url, {
                            credentials: 'same-origin'
                        })
                        .then(r => r.text())
                        .then(html => {
                            const tbody = document.querySelector('table tbody');
                            if (tbody) tbody.innerHTML = html || '<tr><td colspan="7" class="px-3 py-5 text-center text-xs text-slate-500">No results found</td></tr>';
                            input.focus();
                        }).catch(err => console.error(err));
                }, 250);
            });
        })();
    </script>
</body>

</html>
