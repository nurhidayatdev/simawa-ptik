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

$search = $_GET['search'] ?? '';

if (!empty($search)) {
    $stmt = mysqli_prepare(
        $koneksi,
        "SELECT * FROM dosen WHERE nama_dosen LIKE ? ORDER BY nama_dosen ASC"
    );
    $like = "%$search%";
    mysqli_stmt_bind_param($stmt, "s", $like);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
} else {
    $result = mysqli_query($koneksi, "SELECT * FROM dosen ORDER BY nama_dosen ASC");
}

// If AJAX live-search request, return only the table rows
if (isset($_GET['ajax']) && $_GET['ajax'] == '1') {
    $no = 1;
    $id = 1;
    while ($row = mysqli_fetch_assoc($result)) {
        $isEven = ($id++ % 2 == 0);
        $rowClass = ($isEven ? 'bg-slate-50 dark:bg-slate-800/30' : 'bg-white dark:bg-slate-900') . ' hover:bg-indigo-50 dark:hover:bg-indigo-900/20 transition-colors';
        echo '<tr class="' . $rowClass . '">';
        echo '<td class="px-3 py-2 whitespace-nowrap text-xs text-slate-800 dark:text-slate-200">' . ($no++) . '</td>';
        echo '<td class="px-3 py-2 text-xs font-semibold text-slate-800 dark:text-slate-100">' . htmlspecialchars($row['nama_dosen']) . '</td>';
        echo '<td class="px-3 py-2 whitespace-nowrap text-xs text-slate-600 dark:text-slate-400">' . htmlspecialchars($row['no_hp']) . '</td>';
        echo '<td class="px-3 py-2 text-xs">';
        if (!empty($row['no_hp'])):
            echo '<a href="https://wa.me/' . htmlspecialchars($row['no_hp']) . '" target="_blank" class="text-green-600 dark:text-green-400 hover:underline font-medium"><i class="fab fa-whatsapp mr-1"></i>WhatsApp →</a>';
        else:
            echo '<span class="text-slate-400 dark:text-slate-600">-</span>';
        endif;
        echo '</td>';
        if ($role === 'Administrator Utama'):
            echo '<td class="px-3 py-2 whitespace-nowrap text-sm text-slate-500 text-center">';
            echo '<a href="../actions/edit.php?tabel=dosen&id=' . $row['id'] . '" title="Edit"><i class="fa fa-edit text-blue-600 dark:text-blue-400 hover:text-blue-900 dark:hover:text-blue-300 mr-3 transition-colors"></i></a>';
            echo '<a href="../actions/hapus.php?tabel=dosen&id=' . $row['id'] . '" title="Hapus"><i class="fa fa-trash text-red-600 dark:text-red-400 hover:text-red-900 dark:hover:text-red-300 transition-colors"></i></a>';
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

    <title>Data Dosen - PTIK C 2024</title>
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

</head>

<body class="bg-slate-100 dark:bg-slate-950 transition-colors duration-300">
    <?php include '../includes/menu_db.php'; ?>

    <main class="ml-0 lg:ml-48 pt-20 p-6 flex flex-col min-h-screen">
        <div class="bg-white dark:bg-slate-900 rounded-lg border border-slate-200 dark:border-slate-800 p-6 flex-grow w-full shadow-sm">
            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-6">
                <div>
                    <h1 class="text-xl font-bold text-slate-800 dark:text-slate-100">Data Dosen</h1>
                </div>
                <div class="flex flex-col sm:flex-row gap-2 w-full sm:w-auto">
                    <form method="GET" class="w-full sm:w-64">
                        <div class="flex flex-row gap-2 items-center">
                            <div class="relative w-full">
                                <input id="searchInput" type="text" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="Cari Nama..."
                                    class="w-full pl-9 pr-3 py-2 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 text-xs text-slate-700 dark:text-slate-200 transition-all">
                                <i class="fa fa-search absolute left-3 top-2.5 text-slate-400 dark:text-slate-500 text-xs"></i>
                            </div>
                            <?php if ($role === 'Administrator Utama'): ?>
                                <a href="../actions/tambah.php?tabel=dosen" class="bg-indigo-600 hover:bg-indigo-700 text-white p-2.5 rounded-lg transition-all shadow-sm flex items-center justify-center shrink-0" title="Tambah">
                                    <i class="fa fa-plus text-xs"></i>
                                </a>
                            <?php endif; ?>
                        </div>
                    </form>
                </div>
            </div>

            <div class="overflow-x-auto">
            <div class="overflow-x-auto rounded-xl border border-slate-200 dark:border-slate-800">
                <table class="min-w-full divide-y divide-slate-200 dark:divide-slate-800">
                    <thead class="bg-indigo-900 dark:bg-slate-800">
                        <tr>
                            <th class="px-3 py-3 text-left text-[10px] font-bold text-white uppercase tracking-widest">No</th>
                            <th class="px-3 py-3 text-left text-[10px] font-bold text-white uppercase tracking-widest">Nama Dosen</th>
                            <th class="px-3 py-3 text-left text-[10px] font-bold text-white uppercase tracking-widest">No HP</th>
                            <th class="px-3 py-3 text-left text-[10px] font-bold text-white uppercase tracking-widest">Hubungi</th>
                            <?php if ($role === 'Administrator Utama'): ?>
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
                            $rowClass = ($isEven ? 'bg-slate-50/50 dark:bg-slate-800/20' : 'bg-white dark:bg-slate-900') . ' hover:bg-indigo-50 dark:hover:bg-indigo-900/20 transition-colors';
                        ?>
                            <tr class="<?= $rowClass ?>">
                                <td class="px-3 py-2 whitespace-nowrap text-xs text-slate-800 dark:text-slate-200 font-medium"><?php echo $no++; ?></td>
                                <td class="px-3 py-2 text-xs font-semibold text-slate-800 dark:text-slate-100"><?php echo htmlspecialchars($row['nama_dosen']); ?></td>
                                <td class="px-3 py-2 whitespace-nowrap text-xs text-slate-600 dark:text-slate-400"><?php echo htmlspecialchars($row['no_hp']); ?></td>
                                <td class="px-3 py-2 text-xs">
                                    <?php if (!empty($row['no_hp'])): ?>
                                        <a href="https://wa.me/<?php echo htmlspecialchars($row['no_hp']); ?>" target="_blank" class="text-green-600 dark:text-green-400 hover:underline font-medium transition-colors"><i class="fab fa-whatsapp mr-1"></i>WhatsApp</a>
                                    <?php else: ?>
                                        <span class="text-slate-400 dark:text-slate-600">-</span>
                                    <?php endif; ?>
                                </td>
                                <?php if ($role === 'Administrator Utama'): ?>
                                    <td class="px-3 py-2 whitespace-nowrap text-sm text-center text-slate-500">
                                        <div class="flex items-center justify-center gap-3">
                                            <a href="../actions/edit.php?tabel=dosen&id=<?php echo $row['id']; ?>" title="Edit" class="text-blue-600 dark:text-blue-400 hover:text-blue-900 dark:hover:text-blue-300 transition-colors"><i class="fa fa-edit"></i></a>
                                            <a href="../actions/hapus.php?tabel=dosen&id=<?php echo $row['id']; ?>" title="Hapus" class="text-red-600 dark:text-red-400 hover:text-red-900 dark:hover:text-red-300 transition-colors"><i class="fa fa-trash"></i></a>
                                        </div>
                                    </td>
                                <?php endif; ?>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
            </div>
        </div>
        <?php include '../includes/footer_db.php'; ?>
    </main>
    <?php include '../includes/script_db.php'; ?>

    <script>
        // Live search via AJAX (debounced) - keeps input focus
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
                            if (tbody) tbody.innerHTML = html || '<tr><td colspan="4" class="px-3 py-2 text-xs text-slate-500">No results</td></tr>';
                            input.focus();
                        }).catch(err => console.error(err));
                }, 250);
            });
        })();
    </script>
</body>

</html>