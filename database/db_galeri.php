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
        "SELECT * FROM galeri 
         WHERE judul LIKE ? 
         ORDER BY tanggal_kegiatan DESC"
    );

    $like = "%$search%";
    mysqli_stmt_bind_param($stmt, "s", $like);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
} else {
    $result = mysqli_query($koneksi, "SELECT * FROM galeri ORDER BY tanggal_kegiatan DESC");
}

// AJAX handler
if (isset($_GET['ajax']) && $_GET['ajax'] == '1') {
    $no = 1;
    $id = 1;
    while ($row = mysqli_fetch_assoc($result)) {
        $isEven = ($id++ % 2 == 0);
        $rowClass = ($isEven ? 'bg-slate-50 dark:bg-slate-800/30' : 'bg-white dark:bg-slate-900') . ' hover:bg-indigo-50 dark:hover:bg-indigo-900/20 transition-colors';
        echo '<tr class="' . $rowClass . '">';
        echo '<td class="px-3 py-2 whitespace-nowrap text-xs text-slate-800 dark:text-slate-200 font-medium">' . ($no++) . '</td>';
        echo '<td class="px-3 py-2 text-xs font-semibold text-slate-800 dark:text-slate-100">' . htmlspecialchars($row['judul']) . '</td>';
        echo '<td class="px-3 py-2 whitespace-nowrap text-xs text-slate-600 dark:text-slate-400">' . date('d M Y', strtotime($row['tanggal_kegiatan'])) . '</td>';
        echo '<td class="px-3 py-2 whitespace-nowrap text-xs text-slate-800">';
        $gal_id = $row['id'];
        $q_foto = mysqli_query($koneksi, "SELECT foto_path FROM galeri_foto WHERE galeri_id = $gal_id LIMIT 1");
        $f_row = mysqli_fetch_assoc($q_foto);
        if ($f_row) {
            echo '<div class="h-8 w-8 rounded border border-slate-200 dark:border-slate-700 overflow-hidden shadow-sm">';
            echo '<img src="../storage/gallery/' . htmlspecialchars($f_row['foto_path']) . '" class="w-full h-full object-cover" />';
            echo '</div>';
        } else {
            echo '<span class="text-[10px] text-slate-400 dark:text-slate-600 italic">No image</span>';
        }
        echo '</td>';
        echo '<td class="px-3 py-2 whitespace-nowrap text-xs">';
        echo '<a href="../actions/download_galeri.php?id=' . $row['id'] . '" class="text-indigo-600 dark:text-indigo-400 hover:text-indigo-900 dark:hover:text-indigo-300 font-medium transition-colors" title="Download Semua Foto">';
        echo '<i class="fa fa-download mr-1"></i> Download';
        echo '</a>';
        echo '</td>';
        if ($role === 'Administrator Utama'):
            echo '<td class="px-3 py-2 whitespace-nowrap text-sm text-center">';
            echo '<div class="flex items-center justify-center gap-3">';
            echo '<a href="../actions/edit.php?tabel=galeri&id=' . $row['id'] . '" title="Edit" class="text-blue-600 dark:text-blue-400 hover:text-blue-900 dark:hover:text-blue-300 transition-colors"><i class="fa fa-edit"></i></a>';
            echo '<a href="../actions/hapus.php?tabel=galeri&id=' . $row['id'] . '" title="Hapus" class="text-red-600 dark:text-red-400 hover:text-red-900 dark:hover:text-red-300 transition-colors"><i class="fa fa-trash"></i></a>';
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

    <title>Data Galeri - PTIK C 2024</title>
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
                    <h1 class="text-xl font-bold text-slate-800 dark:text-slate-100">Data Galeri Kegiatan</h1>
                </div>
                <div class="flex flex-col sm:flex-row gap-2 w-full sm:w-auto">
                    <form method="GET" class="w-full sm:w-64">
                        <div class="flex flex-row gap-2 items-center">
                            <div class="relative w-full">
                                <input id="searchInput" type="text" name="search" value="<?= htmlspecialchars($_GET['search'] ?? '') ?>" placeholder="Cari Kegiatan..."
                                    class="w-full pl-9 pr-3 py-2 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 text-xs text-slate-700 dark:text-slate-200 transition-all">
                                <i class="fa fa-search absolute left-3 top-2.5 text-slate-400 dark:text-slate-500 text-xs"></i>
                            </div>
                            <?php if ($role === 'Administrator Utama'): ?>
                                <a href="../actions/tambah.php?tabel=galeri" class="bg-indigo-600 hover:bg-indigo-700 text-white p-2.5 rounded-lg transition-all shadow-sm flex items-center justify-center shrink-0" title="Tambah">
                                    <i class="fa fa-plus text-xs"></i>
                                </a>
                            <?php endif; ?>
                        </div>
                    </form>
                </div>
            </div>

            <div class="overflow-x-auto rounded-xl border border-slate-200 dark:border-slate-800">
                <table class="min-w-full divide-y divide-slate-200 dark:divide-slate-800">
                    <thead class="bg-indigo-900 dark:bg-slate-800">
                        <tr>
                            <th class="px-3 py-3 text-left text-[10px] font-bold text-white uppercase tracking-widest">No</th>
                            <th class="px-3 py-3 text-left text-[10px] font-bold text-white uppercase tracking-widest">Judul</th>
                            <th class="px-3 py-3 text-left text-[10px] font-bold text-white uppercase tracking-widest">Tanggal</th>
                            <th class="px-3 py-3 text-left text-[10px] font-bold text-white uppercase tracking-widest">Foto</th>
                            <th class="px-3 py-3 text-left text-[10px] font-bold text-white uppercase tracking-widest">Download</th>
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
                                <td class="px-3 py-2 whitespace-nowrap text-xs text-slate-800 dark:text-slate-200 font-medium"><?= $no++; ?></td>
                                <td class="px-3 py-2 text-xs font-semibold text-slate-800 dark:text-slate-100"><?= htmlspecialchars($row['judul']); ?></td>
                                <td class="px-3 py-2 whitespace-nowrap text-xs text-slate-600 dark:text-slate-400"><?= date('d M Y', strtotime($row['tanggal_kegiatan'])); ?></td>
                                <td class="px-3 py-2 whitespace-nowrap text-xs text-slate-800">
                                    <?php
                                    $gal_id = $row['id'];
                                    $q_foto = mysqli_query($koneksi, "SELECT foto_path FROM galeri_foto WHERE galeri_id = $gal_id LIMIT 1");
                                    $f_row = mysqli_fetch_assoc($q_foto);
                                    if ($f_row) {
                                        echo '<div class="h-8 w-8 rounded border border-slate-200 dark:border-slate-700 overflow-hidden shadow-sm">';
                                        echo '<img src="../storage/gallery/' . htmlspecialchars($f_row['foto_path']) . '" class="w-full h-full object-cover" />';
                                        echo '</div>';
                                    } else {
                                        echo '<span class="text-[10px] text-slate-400 dark:text-slate-600 italic">No image</span>';
                                    }
                                    ?>
                                </td>
                                <td class="px-3 py-2 whitespace-nowrap text-xs">
                                    <a href="../actions/download_galeri.php?id=<?= $row['id']; ?>" class="text-indigo-600 dark:text-indigo-400 hover:text-indigo-900 dark:hover:text-indigo-300 font-medium transition-colors" title="Download Semua Foto">
                                        <i class="fa fa-download mr-1"></i> Download
                                    </a>
                                </td>
                                <?php if ($role === 'Administrator Utama'): ?>
                                    <td class="px-3 py-2 whitespace-nowrap text-sm text-center">
                                        <div class="flex items-center justify-center gap-3">
                                            <a href="../actions/edit.php?tabel=galeri&id=<?= $row['id']; ?>" title="Edit" class="text-blue-600 dark:text-blue-400 hover:text-blue-900 dark:hover:text-blue-300 transition-colors">
                                                <i class="fa fa-edit"></i>
                                            </a>
                                            <a href="../actions/hapus.php?tabel=galeri&id=<?= $row['id']; ?>" title="Hapus" class="text-red-600 dark:text-red-400 hover:text-red-900 dark:hover:text-red-300 transition-colors">
                                                <i class="fa fa-trash"></i>
                                            </a>
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
        const input = document.getElementById('searchInput');
        let timer = null;
        input.addEventListener('input', () => {
            clearTimeout(timer);
            timer = setTimeout(() => {
                const q = encodeURIComponent(input.value || '');
                fetch(window.location.pathname + '?search=' + q + '&ajax=1')
                    .then(r => r.text())
                    .then(html => {
                        document.querySelector('table tbody').innerHTML = html;
                        input.focus();
                    });
            }, 250);
        });
    </script>
</body>

</html>