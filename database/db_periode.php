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

if ($_SESSION['role'] !== 'Administrator Utama') {
    // Kita tetap butuh HTML dikit untuk menjalankan JS
    include '../includes/alert_role.php';
    exit();
}

$search = $_GET['search'] ?? '';

if (!empty($search)) {
    $stmt = mysqli_prepare(
        $koneksi,
        "SELECT * FROM periode 
         WHERE tahun_periode LIKE ? 
         OR semester_nama LIKE ?
         ORDER BY semester_ke DESC"
    );

    $like = "%$search%";
    mysqli_stmt_bind_param($stmt, "ss", $like, $like);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
} else {
    $result = mysqli_query($koneksi, "SELECT * FROM periode ORDER BY semester_ke DESC");
}

// If this is an AJAX live-search request, return only the table rows
if (isset($_GET['ajax']) && $_GET['ajax'] == '1') {
    $no = 1;
    $id = 1;
    while ($row = mysqli_fetch_assoc($result)) {
        $isEven = ($id++ % 2 == 0);
        $rowClass = ($isEven ? 'bg-slate-50 dark:bg-slate-800/30' : 'bg-white dark:bg-slate-900') . ' hover:bg-indigo-50 dark:hover:bg-indigo-900/10 transition-colors';
        echo '<tr class="' . $rowClass . '">';
        echo '<td class="px-3 py-2 whitespace-nowrap text-xs text-slate-800 dark:text-slate-200 font-medium">' . ($no++) . '</td>';
        echo '<td class="px-3 py-2 whitespace-nowrap text-xs text-slate-800 dark:text-slate-100 font-bold">' . htmlspecialchars($row['tahun_periode']) . '</td>';
        echo '<td class="px-3 py-2 whitespace-nowrap text-xs text-slate-800 dark:text-slate-200">' . htmlspecialchars($row['semester_nama']) . '</td>';
        echo '<td class="px-3 py-2 whitespace-nowrap text-xs text-slate-800 dark:text-slate-200 font-mono">' . htmlspecialchars($row['semester_ke']) . '</td>';

        // Status badge
        $isAktif = ($row['status'] == 'aktif');
        $statusClass = $isAktif
            ? 'bg-green-100 dark:bg-green-900/40 text-green-700 dark:text-green-400 border-green-200 dark:border-green-800/50'
            : 'bg-red-100 dark:bg-red-900/40 text-red-700 dark:text-red-400 border-red-200 dark:border-red-800/50';

        echo '<td class="px-3 py-2 whitespace-nowrap text-xs">';
        echo '<span class="px-2 py-0.5 rounded-full border text-[10px] font-black uppercase tracking-tighter ' . $statusClass . '">' . htmlspecialchars(ucfirst($row['status'])) . '</span>';
        echo '</td>';

        echo '<td class="px-3 py-2 whitespace-nowrap text-sm text-center">';
        echo '<div class="flex items-center justify-center gap-3">';
        echo '<a href="../actions/edit.php?tabel=periode&id=' . $row['id'] . '" title="Edit" class="text-blue-600 dark:text-blue-400 hover:text-blue-900 dark:hover:text-blue-200 transition-colors"><i class="fa fa-edit"></i></a>';
        echo '<a href="../actions/hapus.php?tabel=periode&id=' . $row['id'] . '" title="Hapus" class="text-red-600 dark:text-red-400 hover:text-red-900 dark:hover:text-red-200 transition-colors"><i class="fa fa-trash"></i></a>';
        echo '</div>';
        echo '</td>';
        echo '</tr>';
    }
    exit;
}

?>
<!DOCTYPE html>
<html lang="en">

<head>

    <title>Data Periode - SIMAWA PTIK 2024</title>
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

        /* live-search script removed from style block; see script at document end */
    </style>

</head>

<body class="bg-slate-100 dark:bg-slate-950 transition-colors duration-300">
    <?php include '../includes/menu_db.php'; ?>

    <main class="ml-0 lg:ml-48 pt-20 p-6 flex flex-col min-h-screen">
        <div class="bg-white dark:bg-slate-900 rounded-lg border border-slate-200 dark:border-slate-800 p-6 flex-grow w-full shadow-sm">
            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-6">
                <h3 class="text-xl font-bold text-slate-800 dark:text-slate-100">Data Periode</h3>
                <div class="flex-col sm:flex-row gap-2 w-full sm:w-auto">
                    <form method="GET" class="w-full">
                        <div class="flex flex-row gap-2 items-center">
                            <div class="relative flex-1 min-w-0">
                                <input id="searchInput" type="text" name="search" value="<?= htmlspecialchars($_GET['search'] ?? '') ?>" placeholder="Cari Tahun / Semester..." class="w-full pl-9 pr-3 py-2 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 text-xs text-slate-700 dark:text-slate-200 transition-all">
                                <i class="fa fa-search absolute left-3 top-2.5 text-slate-400 dark:text-slate-500 text-xs"></i>
                            </div>
                            <a href="../actions/tambah.php?tabel=periode" class="bg-indigo-600 hover:bg-indigo-700 text-white p-2.5 rounded-lg transition-all shadow-sm flex items-center justify-center shrink-0" title="Tambah">
                                <i class="fa fa-plus text-xs"></i>
                            </a>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Rest of the content remains the same -->
            <div class="overflow-x-auto rounded-xl border border-slate-200 dark:border-slate-800">
                <table class="min-w-full divide-y divide-slate-200 dark:divide-slate-800">
                    <thead class="bg-indigo-900 dark:bg-slate-800">
                        <tr>
                            <th class="px-3 py-3 text-left text-[10px] font-bold text-white uppercase tracking-widest">No</th>
                            <th class="px-3 py-3 text-left text-[10px] font-bold text-white uppercase tracking-widest">Tahun Periode</th>
                            <th class="px-3 py-3 text-left text-[10px] font-bold text-white uppercase tracking-widest">Semester Nama</th>
                            <th class="px-3 py-3 text-left text-[10px] font-bold text-white uppercase tracking-widest">Sem Ke</th>
                            <th class="px-3 py-3 text-left text-[10px] font-bold text-white uppercase tracking-widest">Status</th>
                            <th class="px-3 py-3 text-[10px] font-bold text-white uppercase tracking-widest text-center">Actions</th>
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
                                <td class="px-3 py-2 whitespace-nowrap text-xs text-slate-800 dark:text-slate-100 font-bold"><?= htmlspecialchars($row['tahun_periode']); ?></td>
                                <td class="px-3 py-2 whitespace-nowrap text-xs text-slate-800 dark:text-slate-200"><?= htmlspecialchars($row['semester_nama']); ?></td>
                                <td class="px-3 py-2 whitespace-nowrap text-xs text-slate-800 dark:text-slate-200 font-mono"><?= htmlspecialchars($row['semester_ke']); ?></td>
                                <td class="px-3 py-2 whitespace-nowrap text-xs">
                                    <?php
                                    $isAktif = ($row['status'] == 'aktif');
                                    $statusClass = $isAktif
                                        ? 'bg-green-100 dark:bg-green-900/40 text-green-700 dark:text-green-400 border-green-200 dark:border-green-800/50'
                                        : 'bg-red-100 dark:bg-red-900/40 text-red-700 dark:text-red-400 border-red-200 dark:border-red-800/50';
                                    ?>
                                    <span class="px-2 py-0.5 rounded-full border text-[10px] font-black uppercase tracking-tighter <?= $statusClass ?>">
                                        <?= htmlspecialchars(ucfirst($row['status'])); ?>
                                    </span>
                                </td>
                                <td class="px-3 py-2 whitespace-nowrap text-sm text-center">
                                    <div class="flex items-center justify-center gap-3">
                                        <a href="../actions/edit.php?tabel=periode&id=<?= $row['id']; ?>" title="Edit" class="text-blue-600 dark:text-blue-400 hover:text-blue-900 dark:hover:text-blue-200 transition-colors"><i class="fa fa-edit"></i></a>
                                        <a href="../actions/hapus.php?tabel=periode&id=<?= $row['id']; ?>" title="Hapus" class="text-red-600 dark:text-red-400 hover:text-red-900 dark:hover:text-red-200 transition-colors"><i class="fa fa-trash"></i></a>
                                    </div>
                                </td>
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
        // Live search via AJAX: update table rows without leaving input focus
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
                            if (tbody) tbody.innerHTML = html;
                            input.focus();
                        }).catch(err => console.error(err));
                }, 250);
            });
        })();
    </script>
</body>

</html>