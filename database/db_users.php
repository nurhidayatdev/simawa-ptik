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
        "SELECT 
    u.id AS user_id,
    m.nama_lengkap,
    u.id,
    u.email,
    u.password,
    u.role
FROM users u
JOIN mahasiswa m ON m.user_id = u.id 
         WHERE m.nama_lengkap LIKE ? 
         OR u.email LIKE ? 
         OR u.role LIKE ?
         ORDER BY u.email"
    );

    $like = "%$search%";
    mysqli_stmt_bind_param($stmt, "sss", $like, $like, $like);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
} else {
    $result = mysqli_query($koneksi, "SELECT 
    u.id AS user_id,
    m.nama_lengkap,
    u.id,
    u.email,
    u.password,
    u.role
FROM users u
JOIN mahasiswa m ON m.user_id = u.id
ORDER BY u.email");
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
        echo '<td class="px-3 py-2 text-xs text-slate-800 dark:text-slate-100 font-bold">' . htmlspecialchars($row['nama_lengkap']) . '</td>';
        echo '<td class="px-3 py-2 whitespace-nowrap text-xs text-slate-800 dark:text-slate-200">' . htmlspecialchars($row['email']) . '</td>';
        echo '<td class="px-3 py-2 whitespace-nowrap text-xs text-slate-800 dark:text-slate-400 font-mono">**********</td>';
        
        // Role badge
        $roleColor = ($row['role'] === 'Administrator Utama') 
            ? 'bg-purple-100 dark:bg-purple-900/40 text-purple-700 dark:text-purple-400 border-purple-200 dark:border-purple-800/50' 
            : 'bg-indigo-100 dark:bg-indigo-900/40 text-indigo-700 dark:text-indigo-400 border-indigo-200 dark:border-indigo-800/50';
            
        echo '<td class="px-3 py-2 text-xs">';
        echo '<span class="px-2 py-0.5 rounded-full border text-[10px] font-black uppercase tracking-tighter ' . $roleColor . '">' . htmlspecialchars($row['role']) . '</span>';
        echo '</td>';
        
        echo '<td class="px-3 py-2 whitespace-nowrap text-sm text-center">';
        echo '<div class="flex items-center justify-center gap-3">';
        echo '<a href="../actions/edit.php?tabel=users&id=' . $row['id'] . '" title="Edit" class="text-blue-600 dark:text-blue-400 hover:text-blue-900 dark:hover:text-blue-200 transition-colors"><i class="fa fa-edit"></i></a>';
        echo '<a href="javascript:void(0)" onclick="confirmReset(' . $row['id'] . ')" title="Reset Password" class="text-yellow-600 dark:text-yellow-400 hover:text-yellow-900 dark:hover:text-yellow-200 transition-colors"><i class="fa fa-key"></i></a>';
        echo '<a href="javascript:void(0)" onclick="confirmDelete(' . $row['id'] . ')" title="Hapus" class="text-red-600 dark:text-red-400 hover:text-red-900 dark:hover:text-red-200 transition-colors"><i class="fa fa-trash"></i></a>';
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

    <title>Data Users - PTIK C 2024</title>
    <?php include '../includes/head.php'; ?>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
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
                <h3 class="text-xl font-bold text-slate-800 dark:text-slate-100">Data Users</h3>
                <div class="flex-col sm:flex-row gap-2 w-full sm:w-auto">
                    <form method="GET" class="w-full">
                        <div class="flex flex-row gap-2 items-center">
                            <div class="relative flex-1 min-w-0">
                                <input id="searchInput" type="text" name="search" value="<?= htmlspecialchars($_GET['search'] ?? '') ?>" placeholder="Cari Nama / Email..." class="w-full pl-9 pr-3 py-2 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 text-xs text-slate-700 dark:text-slate-200 transition-all">
                                <i class="fa fa-search absolute left-3 top-2.5 text-slate-400 dark:text-slate-500 text-xs"></i>
                            </div>
                            <a href="../actions/tambah.php?tabel=users" class="bg-indigo-600 hover:bg-indigo-700 text-white p-2.5 rounded-lg transition-all shadow-sm flex items-center justify-center shrink-0" title="Tambah">
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
                            <th class="px-3 py-3 text-left text-[10px] font-bold text-white uppercase tracking-widest">Nama Lengkap</th>
                            <th class="px-3 py-3 text-left text-[10px] font-bold text-white uppercase tracking-widest">Email</th>
                            <th class="px-3 py-3 text-left text-[10px] font-bold text-white uppercase tracking-widest">Password</th>
                            <th class="px-3 py-3 text-left text-[10px] font-bold text-white uppercase tracking-widest">Role</th>
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
                                <td class="px-3 py-2 text-xs text-slate-800 dark:text-slate-100 font-bold"><?= htmlspecialchars($row['nama_lengkap']); ?></td>
                                <td class="px-3 py-2 whitespace-nowrap text-xs text-slate-800 dark:text-slate-200"><?= htmlspecialchars($row['email']); ?></td>
                                <td class="px-3 py-2 whitespace-nowrap text-xs text-slate-800 dark:text-slate-400 font-mono">**********</td>
                                <td class="px-3 py-2 text-xs">
                                    <?php
                                    $roleColor = ($row['role'] === 'Administrator Utama') 
                                        ? 'bg-purple-100 dark:bg-purple-900/40 text-purple-700 dark:text-purple-400 border-purple-200 dark:border-purple-800/50' 
                                        : 'bg-indigo-100 dark:bg-indigo-900/40 text-indigo-700 dark:text-indigo-400 border-indigo-200 dark:border-indigo-800/50';
                                    ?>
                                    <span class="px-2 py-0.5 rounded-full border text-[10px] font-black uppercase tracking-tighter <?= $roleColor ?>">
                                        <?= htmlspecialchars($row['role']); ?>
                                    </span>
                                </td>
                                <td class="px-3 py-2 whitespace-nowrap text-sm text-center">
                                    <div class="flex items-center justify-center gap-3">
                                        <a href="../actions/edit.php?tabel=users&id=<?= $row['id']; ?>" title="Edit" class="text-blue-600 dark:text-blue-400 hover:text-blue-900 dark:hover:text-blue-200 transition-colors"><i class="fa fa-edit"></i></a>
                                        <a href="javascript:void(0)" onclick="confirmReset(<?= $row['id']; ?>)" title="Reset Password" class="text-yellow-600 dark:text-yellow-400 hover:text-yellow-900 dark:hover:text-yellow-200 transition-colors"><i class="fa fa-key"></i></a>
                                        <a href="javascript:void(0)" onclick="confirmDelete(<?= $row['id']; ?>)" title="Hapus" class="text-red-600 dark:text-red-400 hover:text-red-900 dark:hover:text-red-200 transition-colors"><i class="fa fa-trash"></i></a>
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
        <?php if (isset($_GET['status']) && $_GET['status'] == 'success_reset'): ?>
            Swal.fire({
                title: 'Berhasil!',
                text: "Password berhasil direset ke 'ptikClass24'",
                icon: 'success',
                confirmButtonText: 'OK'
            }).then(() => {
                // Bersihkan URL dari parameter status
                window.history.replaceState({}, document.title, window.location.pathname);
            });
        <?php endif; ?>

        function confirmReset(id) {
            Swal.fire({
                title: 'Reset Password?',
                text: "Password akan diubah kembali ke 'ptikClass24'",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#eab308',
                cancelButtonColor: '#64748b',
                confirmButtonText: 'Ya, Reset!',
                cancelButtonText: 'Batal'
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = '../actions/reset_password.php?id=' + id;
                }
            })
        }

        function confirmDelete(id) {
            Swal.fire({
                title: 'Hapus User?',
                text: "Data yang dihapus tidak dapat dikembalikan!",
                icon: 'error',
                showCancelButton: true,
                confirmButtonColor: '#dc2626',
                cancelButtonColor: '#64748b',
                confirmButtonText: 'Ya, Hapus!',
                cancelButtonText: 'Batal'
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = '../actions/hapus.php?tabel=users&id=' + id;
                }
            })
        }
        // Live search via AJAX (debounced) - keep focus in input
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
                            if (tbody) tbody.innerHTML = html || '<tr><td colspan="6" class="px-3 py-2 text-xs text-slate-500">No results</td></tr>';
                            input.focus();
                        }).catch(err => console.error(err));
                }, 250);
            });
        })();
    </script>
</body>

</html>
```