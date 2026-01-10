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
        "SELECT * FROM mahasiswa 
         WHERE nim LIKE ? 
         OR nama_lengkap LIKE ? 
         OR nama_panggilan LIKE ?
         ORDER BY nim ASC"
    );

    $like = "%$search%";
    mysqli_stmt_bind_param($stmt, "sss", $like, $like, $like);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
} else {
    $result = mysqli_query($koneksi, "SELECT * FROM mahasiswa ORDER BY nim ASC");
}

// If this is an AJAX live-search request, return only the table rows
if (isset($_GET['ajax']) && $_GET['ajax'] == '1') {
    $no = 1;
    $id = 1;
    while ($row = mysqli_fetch_assoc($result)) {
        $rowClass = (($id++ % 2 == 0) ? 'bg-slate-100' : 'bg-white') . ' hover:bg-indigo-100';
        echo '<tr class="' . $rowClass . '">';
        echo '<td class="px-3 py-2 whitespace-nowrap text-xs text-slate-800">' . ($no++) . '</td>';
        echo '<td class="px-3 py-2 whitespace-nowrap text-xs">';
        if (!empty($row['foto'])) {
            echo '<img src="../storage/profile/' . htmlspecialchars($row['foto']) . '" class="img-fluid rounded" style="max-width:25px; height:auto;" />';
        } else {
            echo '<span class="text-muted">Belum ada gambar</span>';
        }
        echo '</td>';
        echo '<td class="px-3 py-2 whitespace-nowrap text-xs text-slate-800">' . htmlspecialchars($row['nim']) . '</td>';
        echo '<td class="px-3 py-2 text-xs text-slate-800">' . htmlspecialchars($row['nama_lengkap']) . '</td>';
        echo '<td class="px-3 py-2 whitespace-nowrap text-xs text-slate-800">' . htmlspecialchars($row['nama_panggilan']) . '</td>';
        echo '<td class="px-3 py-2 whitespace-nowrap text-xs text-slate-800">' . htmlspecialchars($row['jenis_kelamin'] ?? '-') . '</td>';
        echo '<td class="px-3 py-2 whitespace-nowrap text-xs text-slate-800">';
        if (!empty($row['instagram'])) {
            echo '<a href="https://instagram.com/' . ltrim(htmlspecialchars($row['instagram']), '@') . '" target="_blank" class="text-pink-600 hover:text-pink-800"><i class="fab fa-instagram mr-1"></i>' . htmlspecialchars($row['instagram']) . '</a>';
        } else {
            echo '-';
        }
        echo '</td>';
        echo '<td class="px-3 py-2 text-xs text-slate-800">' . htmlspecialchars($row['bio']) . '</td>';
        if ($role === 'Administrator Utama'):
            echo '<td class="px-3 py-2 whitespace-nowrap text-sm text-slate-500">';

            echo '<a href="../actions/edit.php?tabel=mahasiswa&id=' . $row['id'] . '" title="Edit"><i class="fa fa-edit text-blue-600 hover:text-blue-900 mr-3"></i></a>';
            echo '<a href="../actions/hapus.php?tabel=mahasiswa&id=' . $row['id'] . '" title="Hapus" onclick="return confirm(\'Hapus mahasiswa ini?\');"><i class="fa fa-trash text-red-600 hover:text-red-900"></i></a>';
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
   
    <title>Data Mahasiswa - PTIK C 2024</title>
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

<body class="bg-slate-100">
    <?php include '../includes/menu_db.php'; ?>

    <main class="ml-0 lg:ml-48 pt-20 p-6 flex flex-col min-h-screen">
        <div class="bg-white rounded-lg border border-slate-200 p-6 flex-grow w-full">
            <!-- Modified this section for better mobile responsiveness -->
            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-6">
                <h3 class="text-xl font-bold text-slate-800">Data Mahasiswa</h3>
                <div class="flex-col sm:flex-row gap-2 w-full sm:w-auto">
                    <form method="GET" class="w-full">
                        <div class="flex flex-row gap-2 items-center">
                            <input
                                id="searchInput"
                                type="text"
                                name="search"
                                value="<?= htmlspecialchars($_GET['search'] ?? '') ?>"
                                placeholder="Cari NIM / Nama..."
                                class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-1 focus:ring-slate-400 text-xs">
                            <?php if ($role === 'Administrator Utama'): ?>
                                <a href="../actions/tambah.php?tabel=mahasiswa" class="bg-green-600 text-white px-3 py-2 rounded-lg hover:bg-green-800 text-center text-xs flex items-center justify-center" title="Tambah">
                                    <i class="fa fa-plus"></i>
                                </a>
                            <?php endif; ?>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Rest of the content remains the same -->
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200">
                    <thead class="bg-indigo-900">
                        <tr>
                            <th class="px-3 py-3 text-left text-xs font-bold text-white uppercase tracking-wider">
                                No</th>
                            <th class="px-3 py-3 text-left text-xs font-bold text-white uppercase tracking-wider">
                                Foto</th>
                            <th class="px-3 py-3 text-left text-xs font-bold text-white uppercase tracking-wider">
                                NIM</th>
                            <th class="px-3 py-3 text-left text-xs font-bold text-white uppercase tracking-wider">
                                Nama Lengkap</th>
                            <th class="px-3 py-3 text-left text-xs font-bold text-white uppercase tracking-wider">
                                Nama Panggilan</th>
                            <th class="px-3 py-3 text-left text-xs font-bold text-white uppercase tracking-wider">
                                Jenis Kelamin</th>
                            <th class="px-3 py-3 text-left text-xs font-bold text-white uppercase tracking-wider">
                                Instagram</th>
                            <th class="px-3 py-3 text-left text-xs font-bold text-white uppercase tracking-wider">
                                Bio</th>
                            <?php if ($role === 'Administrator Utama'): ?>
                                <th class="px-3 py-3 text-left text-xs font-bold text-white uppercase tracking-wider">
                                    Actions</th>
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
                                <td class="px-3 py-2 whitespace-nowrap text-xs">
                                    <?php if (!empty($row['foto'])): ?>
                                        <img src="../storage/profile/<?php echo htmlspecialchars($row['foto']); ?>"
                                            class="img-fluid rounded"
                                            style="max-width:25px; height:auto;" />
                                    <?php else: ?>
                                        <span class="text-muted">Belum ada gambar</span>
                                    <?php endif; ?>
                                </td>
                                <td class="px-3 py-2 whitespace-nowrap text-xs text-slate-800"><?= $row['nim']; ?></td>
                                <td class="px-3 py-2 text-xs text-slate-800"><?= $row['nama_lengkap']; ?></td>
                                <td class="px-3 py-2 whitespace-nowrap text-xs text-slate-800"><?= $row['nama_panggilan']; ?></td>
                                <td class="px-3 py-2 whitespace-nowrap text-xs text-slate-800">
                                    <?php
                                    $jkColor = ($row['jenis_kelamin'] === 'Laki-laki') ? 'bg-sky-100 text-sky-800 border-sky-200' : 'bg-pink-100 text-pink-800 border-pink-200';
                                    ?>
                                    <span class="px-1.5 py-0.5 rounded-full border text-[10px] font-semibold <?= $jkColor ?>"><?= ucfirst($row['jenis_kelamin']) ?></span>
                                </td>
                                <td class="px-3 py-2 whitespace-nowrap text-xs text-slate-800">
                                    <?php if (!empty($row['instagram'])): ?>
                                        <a href="https://instagram.com/<?= ltrim(htmlspecialchars($row['instagram']), '@') ?>" target="_blank" class="text-purple-400 hover:text-purple-800">
                                            <i class="fab fa-instagram mr-1"></i><?= htmlspecialchars($row['instagram']); ?>
                                        </a>
                                    <?php else: ?>
                                        -
                                    <?php endif; ?>
                                </td>
                                <td class="px-3 py-2 text-xs text-slate-800"><?= $row['bio']; ?></td>
                                <?php if ($role === 'Administrator Utama'): ?>
                                    <td class="px-3 py-2 whitespace-nowrap text-sm text-slate-500">
                                        <a href="../actions/edit.php?tabel=mahasiswa&id=<?= $row['id']; ?>" title="Edit">
                                            <i class="fa fa-edit text-blue-600 hover:text-blue-900 mr-3"></i>
                                        </a>

                                        <a href="../actions/hapus.php?tabel=mahasiswa&id=<?= $row['id']; ?>" title="Hapus">
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