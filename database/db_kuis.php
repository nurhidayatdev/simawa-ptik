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

$tanggal_filter = $_GET['tanggal'] ?? '';

if (!empty($tanggal_filter)) {
    $stmt = mysqli_prepare(
        $koneksi,
        "SELECT * FROM kuis_harian_soal WHERE tanggal = ? ORDER BY tanggal ASC"
    );
    mysqli_stmt_bind_param($stmt, "s", $tanggal_filter);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
} else {
    $result = mysqli_query($koneksi, "SELECT * FROM kuis_harian_soal ORDER BY tanggal ASC");
}

// If AJAX live-search request, return only the table rows
if (isset($_GET['ajax']) && $_GET['ajax'] == '1') {
    $no = 1;
    $id = 1;
    while ($row = mysqli_fetch_assoc($result)) {
        $rowClass = (($id++ % 2 == 0) ? 'bg-slate-100' : 'bg-white') . ' hover:bg-indigo-100';
        echo '<tr class="' . $rowClass . '">';
        echo '<td class="px-3 py-2 whitespace-nowrap text-xs text-slate-800">' . ($no++) . '</td>';
        echo '<td class="px-3 py-2 whitespace-nowrap text-xs text-slate-800">' . htmlspecialchars($row['tanggal']) . '</td>';
        echo '<td class="px-3 py-2 text-xs text-slate-800">' . htmlspecialchars($row['pertanyaan']) . '</td>';
        echo '<td class="px-3 py-2 text-xs text-slate-800">' . htmlspecialchars($row['jawaban']) . '</td>';
        if ($role === 'Administrator Utama'):
            echo '<td class="px-3 py-2 whitespace-nowrap text-sm text-slate-500">';
            echo '<a href="../actions/edit.php?tabel=kuis_harian_soal&id=' . $row['id'] . '" title="Edit"><i class="fa fa-edit text-blue-600 hover:text-blue-900 mr-3"></i></a>';
            echo '<a href="../actions/hapus.php?tabel=kuis_harian_soal&id=' . $row['id'] . '" title="Hapus" onclick="return confirm(\'Hapus soal kuis ini?\');"><i class="fa fa-trash text-red-600 hover:text-red-900"></i></a>';
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

    <title>Data Kuis Harian - PTIK C 2024</title>
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
                <h3 class="text-xl font-bold text-slate-800">Data Kuis Harian</h3>
                <div class="flex-col sm:flex-row gap-2 w-full sm:w-auto">
                    <form method="GET" class="w-full">
                        <div class="flex flex-row gap-2 items-center">
                            <input type="date" id="tanggalFilter" name="tanggal" value="<?php echo htmlspecialchars($tanggal_filter); ?>" class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-1 focus:ring-slate-400 text-xs">
                            <button type="button" id="resetFilter" class="bg-slate-500 text-white px-3 py-2 rounded-lg hover:bg-slate-600 text-center text-xs flex items-center justify-center" title="Reset">
                                <i class="fa fa-times"></i>
                            </button>
                            <?php if ($role === 'Administrator Utama'): ?>
                                <a href="../actions/tambah.php?tabel=kuis_harian_soal" class="bg-green-600 text-white px-3 py-2 rounded-lg hover:bg-green-800 text-center text-xs flex items-center justify-center" title="Tambah">
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
                            <th class="px-3 py-3 text-left text-xs font-bold text-white uppercase tracking-wider">Tanggal</th>
                            <th class="px-3 py-3 text-left text-xs font-bold text-white uppercase tracking-wider">Pertanyaan</th>
                            <th class="px-3 py-3 text-left text-xs font-bold text-white uppercase tracking-wider">Jawaban</th>
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
                                <td class="px-3 py-2 whitespace-nowrap text-xs text-slate-800"><?php echo $no++; ?></td>
                                <td class="px-3 py-2 whitespace-nowrap text-xs text-slate-800"><?php echo htmlspecialchars($row['tanggal']); ?></td>
                                <td class="px-3 py-2 text-xs text-slate-800"><?php echo htmlspecialchars($row['pertanyaan']); ?></td>
                                <td class="px-3 py-2 text-xs text-slate-800"><?php echo htmlspecialchars($row['jawaban']); ?></td>
                                <?php if ($role === 'Administrator Utama'): ?>
                                    <td class="px-3 py-2 whitespace-nowrap text-sm text-slate-500">
                                        <a href="../actions/edit.php?tabel=kuis_harian_soal&id=<?php echo $row['id']; ?>" title="Edit"><i class="fa fa-edit text-blue-600 hover:text-blue-900 mr-3"></i></a>
                                        <a href="../actions/hapus.php?tabel=kuis_harian_soal&id=<?php echo $row['id']; ?>" title="Hapus" onclick="return confirm('Hapus soal kuis ini?');"><i class="fa fa-trash text-red-600 hover:text-red-900"></i></a>
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
        // Filter tanggal via AJAX
        (function() {
            const dateInput = document.getElementById('tanggalFilter');
            const resetBtn = document.getElementById('resetFilter');
            if (!dateInput) return;

            dateInput.addEventListener('change', function() {
                const tanggal = encodeURIComponent(dateInput.value || '');
                const url = window.location.pathname + '?tanggal=' + tanggal + '&ajax=1';
                fetch(url, {
                        credentials: 'same-origin'
                    })
                    .then(r => r.text())
                    .then(html => {
                        const tbody = document.querySelector('table tbody');
                        if (tbody) tbody.innerHTML = html || '<tr><td colspan="5" class="px-3 py-2 text-xs text-slate-500">No results</td></tr>';
                    }).catch(err => console.error(err));
            });

            // Reset filter
            if (resetBtn) {
                resetBtn.addEventListener('click', function() {
                    dateInput.value = '';
                    const url = window.location.pathname + '?ajax=1';
                    fetch(url, {
                            credentials: 'same-origin'
                        })
                        .then(r => r.text())
                        .then(html => {
                            const tbody = document.querySelector('table tbody');
                            if (tbody) tbody.innerHTML = html || '<tr><td colspan="5" class="px-3 py-2 text-xs text-slate-500">No results</td></tr>';
                        }).catch(err => console.error(err));
                });
            }
        })();
    </script>
</body>

</html>