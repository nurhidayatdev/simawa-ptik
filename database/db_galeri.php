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
        $rowClass = (($id++ % 2 == 0) ? 'bg-slate-100' : 'bg-white') . ' hover:bg-indigo-100';
        echo '<tr class="' . $rowClass . '">';
        echo '<td class="px-3 py-2 whitespace-nowrap text-xs text-slate-800">' . ($no++) . '</td>';
        echo '<td class="px-3 py-2 text-xs text-slate-800">' . htmlspecialchars($row['judul']) . '</td>';
        echo '<td class="px-3 py-2 whitespace-nowrap text-xs text-slate-800">' . date('d M Y', strtotime($row['tanggal_kegiatan'])) . '</td>';
        echo '<td class="px-3 py-2 whitespace-nowrap text-xs">';
        $gal_id = $row['id'];
        $q_foto = mysqli_query($koneksi, "SELECT foto_path FROM galeri_foto WHERE galeri_id = $gal_id LIMIT 1");
        $f_row = mysqli_fetch_assoc($q_foto);
        if ($f_row) {
            echo '<img src="../storage/gallery/' . htmlspecialchars($f_row['foto_path']) . '" class="h-8 w-8 object-cover rounded" />';
        } else {
            echo '<span class="text-xs text-gray-400">No image</span>';
        }
        echo '</td>';
        echo '<td class="px-3 py-2 whitespace-nowrap text-xs">';
        echo '<a href="../actions/download_galeri.php?id=' . $row['id'] . '" class="text-indigo-600 hover:text-indigo-900" title="Download Semua Foto">';
        echo '<i class="fa fa-download"></i> Download';
        echo '</a>';
        echo '</td>';
        if ($role === 'Administrator Utama'):
            echo '<td class="px-3 py-2 whitespace-nowrap text-sm text-slate-500">';
            echo '<a href="../actions/edit.php?tabel=galeri&id=' . $row['id'] . '" title="Edit"><i class="fa fa-edit text-blue-600 hover:text-blue-900 mr-3"></i></a>';
            echo '<a href="../actions/hapus.php?tabel=galeri&id=' . $row['id'] . '" title="Hapus" onclick="return confirm(\'Hapus kegiatan ini?\');"><i class="fa fa-trash text-red-600 hover:text-red-900"></i></a>';
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

<body class="bg-slate-100">
    <?php include '../includes/menu_db.php'; ?>

    <main class="ml-0 lg:ml-48 pt-20 p-6 flex flex-col min-h-screen">
        <div class="bg-white rounded-lg border border-slate-200 p-6 flex-grow w-full">
            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-6">
                <h3 class="text-xl font-bold text-slate-800">Data Galeri Kegiatan</h3>
                <div class="flex-col sm:flex-row gap-2 w-full sm:w-auto">
                    <form method="GET" class="w-full">
                        <div class="flex flex-row gap-2 items-center">
                            <input id="searchInput" type="text" name="search" value="<?= htmlspecialchars($_GET['search'] ?? '') ?>" placeholder="Cari Kegiatan..." class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-1 focus:ring-slate-400 text-xs">
                            <?php if ($role === 'Administrator Utama'): ?>
                                <a href="../actions/tambah.php?tabel=galeri" class="bg-green-600 text-white px-3 py-2 rounded-lg hover:bg-green-800 text-center text-xs flex items-center justify-center" title="Tambah">
                                    <i class="fa fa-plus"></i>
                                </a>
                            <?php endif; ?>
                        </div>
                    </form>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200">
                    <thead class="bg-indigo-900">
                        <tr>
                            <th class="px-3 py-3 text-left text-xs font-bold text-white uppercase tracking-wider">No</th>
                            <th class="px-3 py-3 text-left text-xs font-bold text-white uppercase tracking-wider">Judul</th>
                            <th class="px-3 py-3 text-left text-xs font-bold text-white uppercase tracking-wider">Tanggal</th>
                            <th class="px-3 py-3 text-left text-xs font-bold text-white uppercase tracking-wider">Foto</th>
                            <th class="px-3 py-3 text-left text-xs font-bold text-white uppercase tracking-wider">Download</th>
                            <?php if ($role === 'Administrator Utama'): ?>
                                <th class="px-3 py-3 text-left text-xs font-bold text-white uppercase tracking-wider">Actions</th>
                            <?php endif; ?>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-slate-200">
                        <?php
                        $no = 1;
                        $id = 1;
                        while ($row = mysqli_fetch_assoc($result)) : ?>
                            <tr class="<?= ($id++ % 2 == 0) ? 'bg-slate-100' : 'bg-white' ?> hover:bg-indigo-100">
                                <td class="px-3 py-2 whitespace-nowrap text-xs text-slate-800"><?= $no++; ?></td>
                                <td class="px-3 py-2 text-xs text-slate-800"><?= htmlspecialchars($row['judul']); ?></td>
                                <td class="px-3 py-2 whitespace-nowrap text-xs text-slate-800"><?= date('d M Y', strtotime($row['tanggal_kegiatan'])); ?></td>
                                <td class="px-3 py-2 whitespace-nowrap text-xs text-slate-800">
                                    <?php
                                    $gal_id = $row['id'];
                                    $q_foto = mysqli_query($koneksi, "SELECT foto_path FROM galeri_foto WHERE galeri_id = $gal_id LIMIT 1");
                                    $f_row = mysqli_fetch_assoc($q_foto);
                                    if ($f_row) {
                                        echo '<img src="../storage/gallery/' . htmlspecialchars($f_row['foto_path']) . '" class="h-8 w-8 object-cover rounded" />';
                                    } else {
                                        echo '<span class="text-xs text-gray-400">No image</span>';
                                    }
                                    ?>
                                </td>
                                <td class="px-3 py-2 whitespace-nowrap text-xs text-slate-800">
                                    <a href="../actions/download_galeri.php?id=<?= $row['id']; ?>" class="text-indigo-600 hover:text-indigo-900" title="Download Semua Foto">
                                        <i class="fa fa-download"></i> Download
                                    </a>
                                </td>
                                <?php if ($role === 'Administrator Utama'): ?>
                                    <td class="px-3 py-2 whitespace-nowrap text-sm text-slate-500">
                                        <a href="../actions/edit.php?tabel=galeri&id=<?= $row['id']; ?>" title="Edit">
                                            <i class="fa fa-edit text-blue-600 hover:text-blue-900 mr-3"></i>
                                        </a>
                                        <a href="../actions/hapus.php?tabel=galeri&id=<?= $row['id']; ?>" title="Hapus" onclick="return confirm('Hapus kegiatan ini?');">
                                            <i class="fa fa-trash text-red-600 hover:text-red-900"></i>
                                        </a>
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