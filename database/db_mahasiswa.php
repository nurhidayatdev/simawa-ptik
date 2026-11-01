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

// Include foto helpers and helpers for multi-class scoping
include_once '../functions/foto_helpers.php';
require_once '../functions/helpers.php';

$where_mhs_kelas = getKelasWhereClause('m', false);
$search = $_GET['search'] ?? '';

if (!empty($search)) {
    $like = "%$search%";
    $where_extra = !empty($where_mhs_kelas) ? "AND ($where_mhs_kelas)" : "";
    $stmt = mysqli_prepare(
        $koneksi,
        "SELECT m.* FROM mahasiswa m
         LEFT JOIN users u ON m.user_id = u.id
         WHERE (u.role = 'Mahasiswa' OR (m.user_id IS NULL AND m.nama_lengkap NOT LIKE '%Administrator%'))
           AND (u.role NOT IN ('Administrator Utama', 'Ketua Tingkat', 'Sekretaris', 'Bendahara') OR u.role IS NULL)
         $where_extra
         AND (m.nim LIKE ? OR m.nama_lengkap LIKE ? OR m.nama_panggilan LIKE ?)
         ORDER BY m.nim ASC"
    );
    mysqli_stmt_bind_param($stmt, "sss", $like, $like, $like);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
} else {
    $where_extra = !empty($where_mhs_kelas) ? "AND ($where_mhs_kelas)" : "";
    $result = mysqli_query($koneksi, "
        SELECT m.* FROM mahasiswa m
        LEFT JOIN users u ON m.user_id = u.id
        WHERE (u.role = 'Mahasiswa' OR (m.user_id IS NULL AND m.nama_lengkap NOT LIKE '%Administrator%'))
          AND (u.role NOT IN ('Administrator Utama', 'Ketua Tingkat', 'Sekretaris', 'Bendahara') OR u.role IS NULL)
        $where_extra
        ORDER BY m.nim ASC
    ");
}

// If this is an AJAX live-search request, return only the table rows
if (isset($_GET['ajax']) && $_GET['ajax'] == '1') {
    $no = 1;
    $id = 1;
    while ($row = mysqli_fetch_assoc($result)) {
        $isEven = ($id++ % 2 == 0);
        $rowClass = ($isEven ? 'bg-slate-50 dark:bg-slate-800/30' : 'bg-white dark:bg-slate-900') . ' hover:bg-indigo-50 dark:hover:bg-indigo-900/20 transition-colors';
        echo '<tr class="' . $rowClass . '">';
        echo '<td class="px-3 py-2 whitespace-nowrap text-xs text-slate-800 dark:text-slate-200">' . ($no++) . '</td>';
        echo '<td class="px-3 py-2 whitespace-nowrap text-xs">';
        echo '<div class="h-6 w-6 rounded-full overflow-hidden shadow-sm inline-block"><img src="' . profilePhotoUrl($row['nama_lengkap'], '../') . '" class="w-full h-full object-cover" /></div>';
        echo '</td>';
        echo '<td class="px-3 py-2 whitespace-nowrap text-xs font-bold text-slate-800 dark:text-slate-100">' . htmlspecialchars($row['nim']) . '</td>';
        echo '<td class="px-3 py-2 text-xs font-semibold text-slate-800 dark:text-slate-200">' . htmlspecialchars($row['nama_lengkap']) . '</td>';
        echo '<td class="px-3 py-2 whitespace-nowrap text-xs text-slate-800 dark:text-slate-300">' . htmlspecialchars($row['nama_panggilan']) . '</td>';
        $jkColor = ($row['jenis_kelamin'] === 'Laki-laki') ? 'bg-sky-100 dark:bg-sky-900/30 text-sky-800 dark:text-sky-300 border-sky-200 dark:border-sky-800' : 'bg-pink-100 dark:bg-pink-900/30 text-pink-800 dark:text-pink-300 border-pink-200 dark:border-pink-800';
        echo '<td class="px-3 py-2 whitespace-nowrap text-xs">';
        echo '<span class="px-1.5 py-0.5 rounded-full border text-[9px] font-bold ' . $jkColor . '">' . ($row['jenis_kelamin'] ?? '-') . '</span>';
        echo '</td>';
        echo '<td class="px-3 py-2 whitespace-nowrap text-xs">';
        if (!empty($row['instagram'])) {
            echo '<a href="https://instagram.com/' . ltrim(htmlspecialchars($row['instagram']), '@') . '" target="_blank" class="text-pink-600 dark:text-pink-400 hover:text-pink-800 dark:hover:text-pink-300"><i class="fab fa-instagram mr-1"></i>' . htmlspecialchars($row['instagram']) . '</a>';
        } else {
            echo '<span class="text-slate-400 dark:text-slate-600">-</span>';
        }
        echo '</td>';
        echo '<td class="px-3 py-2 text-xs text-slate-600 dark:text-slate-400">' . htmlspecialchars($row['bio']) . '</td>';
        if ($role === 'Administrator Utama' || $role === 'Ketua Tingkat'):
            echo '<td class="px-3 py-2 whitespace-nowrap text-sm text-slate-500 dark:text-slate-400">';
            $mhsJson = htmlspecialchars(json_encode([
                "id" => (int)$row["id"],
                "nama_lengkap" => $row["nama_lengkap"],
                "nim" => $row["nim"],
                "nama_panggilan" => $row["nama_panggilan"] ?? "",
                "jenis_kelamin" => $row["jenis_kelamin"] ?? "Laki-laki",
                "instagram" => $row["instagram"] ?? "",
                "bio" => $row["bio"] ?? ""
            ]), ENT_QUOTES, "UTF-8");
            echo '<button type="button" onclick="openEditMhsModal(' . $mhsJson . ')" title="Edit" class="cursor-pointer mr-3"><i class="fa fa-edit text-blue-600 dark:text-blue-400 hover:text-blue-900 dark:hover:text-blue-300 transition-colors"></i></button>';
            echo '<a href="../actions/hapus.php?tabel=mahasiswa&id=' . $row['id'] . '" title="Hapus"><i class="fa fa-trash text-red-600 dark:text-red-400 hover:text-red-900 dark:hover:text-red-300 transition-colors"></i></a>';
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

    <title>Data Mahasiswa - SIMAWA PTIK 2024</title>
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
                <div>
                    <h1 class="text-xl font-bold text-slate-800 dark:text-slate-100">Data Mahasiswa</h1>
                </div>
                <div class="flex flex-col sm:flex-row gap-2 w-full sm:w-auto">
                    <form method="GET" class="w-full sm:w-64">
                        <div class="flex flex-row gap-2 items-center">
                            <div class="relative w-full">
                                <input id="searchInput" type="text" name="search" value="<?= htmlspecialchars($_GET['search'] ?? '') ?>" placeholder="Cari NIM / Nama..."
                                    class="w-full pl-9 pr-3 py-2 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 text-xs text-slate-700 dark:text-slate-200 transition-all">
                                <i class="fa fa-search absolute left-3 top-2.5 text-slate-400 dark:text-slate-500 text-xs"></i>
                            </div>
                            <?php if ($role === 'Administrator Utama' || $role === 'Ketua Tingkat'): ?>
                                <button type="button" onclick="openTambahMhsModal()" class="bg-indigo-600 hover:bg-indigo-700 text-white p-2.5 rounded-lg transition-all shadow-sm flex items-center justify-center shrink-0 cursor-pointer" title="Tambah Mahasiswa">
                                    <i class="fa fa-plus text-xs"></i>
                                </button>
                            <?php endif; ?>
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
                            <th class="px-3 py-3 text-left text-[10px] font-bold text-white uppercase tracking-widest">Foto</th>
                            <th class="px-3 py-3 text-left text-[10px] font-bold text-white uppercase tracking-widest">NIM</th>
                            <th class="px-3 py-3 text-left text-[10px] font-bold text-white uppercase tracking-widest">Nama Lengkap</th>
                            <th class="px-3 py-3 text-left text-[10px] font-bold text-white uppercase tracking-widest">Panggilan</th>
                            <th class="px-3 py-3 text-left text-[10px] font-bold text-white uppercase tracking-widest">Gender</th>
                            <th class="px-3 py-3 text-left text-[10px] font-bold text-white uppercase tracking-widest">Instagram</th>
                            <th class="px-3 py-3 text-left text-[10px] font-bold text-white uppercase tracking-widest">Bio</th>
                            <?php if ($role === 'Administrator Utama' || $role === 'Ketua Tingkat'): ?>
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
                                <td class="px-3 py-2 whitespace-nowrap">
                                    <div class="h-6 w-6 rounded-full overflow-hidden shadow-sm">
                                        <img src="<?= profilePhotoUrl($row['nama_lengkap'], '../') ?>" class="w-full h-full object-cover" />
                                    </div>
                                </td>
                                <td class="px-3 py-2 whitespace-nowrap text-xs font-bold text-slate-800 dark:text-slate-100"><?= $row['nim']; ?></td>
                                <td class="px-3 py-2 text-xs font-semibold text-slate-800 dark:text-slate-200"><?= $row['nama_lengkap']; ?></td>
                                <td class="px-3 py-2 whitespace-nowrap text-xs text-slate-800 dark:text-slate-300"><?= $row['nama_panggilan']; ?></td>
                                <td class="px-3 py-2 whitespace-nowrap text-xs">
                                    <?php
                                    $jkColor = ($row['jenis_kelamin'] === 'Laki-laki') ? 'bg-sky-50 dark:bg-sky-900/30 text-sky-700 dark:text-sky-300 border-sky-100 dark:border-sky-800' : 'bg-pink-50 dark:bg-pink-900/30 text-pink-700 dark:text-pink-300 border-pink-100 dark:border-pink-800';
                                    ?>
                                    <span class="px-1.5 py-0.5 rounded-full border text-[9px] font-bold <?= $jkColor ?>"><?= ucfirst($row['jenis_kelamin']) ?></span>
                                </td>
                                <td class="px-3 py-2 whitespace-nowrap text-xs">
                                    <?php if (!empty($row['instagram'])): ?>
                                        <a href="https://instagram.com/<?= ltrim(htmlspecialchars($row['instagram']), '@') ?>" target="_blank" class="text-pink-600 dark:text-pink-400 hover:text-pink-800 dark:hover:text-pink-300 font-medium transition-colors">
                                            <i class="fab fa-instagram mr-1"></i><?= htmlspecialchars($row['instagram']); ?>
                                        </a>
                                    <?php else: ?>
                                        <span class="text-slate-400 dark:text-slate-600">-</span>
                                    <?php endif; ?>
                                </td>
                                <td class="px-3 py-2 text-xs text-slate-600 dark:text-slate-400 leading-relaxed"><?= $row['bio']; ?></td>
                                <?php if ($role === 'Administrator Utama' || $role === 'Ketua Tingkat'): ?>
                                    <td class="px-3 py-2 whitespace-nowrap text-sm text-center">
                                        <div class="flex items-center justify-center gap-3">
                                            <button type="button" onclick='openEditMhsModal(<?= htmlspecialchars(json_encode([
                                                "id" => (int)$row["id"],
                                                "nama_lengkap" => $row["nama_lengkap"],
                                                "nim" => $row["nim"],
                                                "nama_panggilan" => $row["nama_panggilan"] ?? "",
                                                "jenis_kelamin" => $row["jenis_kelamin"] ?? "Laki-laki",
                                                "instagram" => $row["instagram"] ?? "",
                                                "bio" => $row["bio"] ?? ""
                                            ]), ENT_QUOTES, "UTF-8") ?>)' title="Edit" class="text-blue-600 dark:text-blue-400 hover:text-blue-900 dark:hover:text-blue-300 transition-colors cursor-pointer">
                                                <i class="fa fa-edit"></i>
                                            </button>
                                            <a href="../actions/hapus.php?tabel=mahasiswa&id=<?= $row['id']; ?>" title="Hapus" class="text-red-600 dark:text-red-400 hover:text-red-900 dark:hover:text-red-300 transition-colors">
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
        
    <!-- ==========================================
         MODAL TAMBAH MAHASISWA KELAS (SERAGAM DENGAN DASHBOARD)
         ========================================== -->
    
    <!-- ==========================================
         MODAL EDIT MAHASISWA KELAS (SERAGAM DENGAN DASHBOARD)
         ========================================== -->
    <div id="modalEditMhs" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs hidden transition-opacity">
        <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-2xl max-w-lg w-full max-h-[90vh] overflow-y-auto custom-scrollbar p-6">
            <div class="flex items-center justify-between pb-4 mb-4 border-b border-slate-100 dark:border-slate-800">
                <div>
                    <h3 class="text-base font-extrabold text-slate-900 dark:text-white">Edit Data Mahasiswa</h3>
                    <p class="text-xs text-slate-400 dark:text-slate-500">Perbarui profil dan identitas mahasiswa.</p>
                </div>
                <button type="button" onclick="closeEditMhsModal()" class="w-8 h-8 rounded-lg bg-slate-100 dark:bg-slate-800 text-slate-400 hover:text-slate-700 dark:hover:text-white flex items-center justify-center transition-colors cursor-pointer">
                    <i class="fas fa-times text-xs"></i>
                </button>
            </div>

            <form action="../actions/kelola_pengurus.php" method="POST" class="space-y-4">
                <input type="hidden" name="action" value="edit_mahasiswa">
                <input type="hidden" name="redirect_to" value="../database/db_mahasiswa.php">
                <input type="hidden" name="id" id="dbm_edit_mhs_id">

                <div>
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1">Nama Lengkap *</label>
                    <input type="text" name="nama_lengkap" id="dbm_edit_nama_lengkap" required placeholder="Contoh: Muhammad Ihsan" class="w-full bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-900 dark:text-white px-3.5 py-2.5 rounded-xl text-xs focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 outline-none transition-all">
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1">NIM Mahasiswa *</label>
                        <input type="text" name="nim" id="dbm_edit_nim" required placeholder="24020950..." class="w-full bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-900 dark:text-white px-3.5 py-2.5 rounded-xl text-xs focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 outline-none transition-all">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1">Nama Panggilan</label>
                        <input type="text" name="nama_panggilan" id="dbm_edit_nama_panggilan" placeholder="Contoh: Ihsan" class="w-full bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-900 dark:text-white px-3.5 py-2.5 rounded-xl text-xs focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 outline-none transition-all">
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1">Jenis Kelamin</label>
                        <select name="jenis_kelamin" id="dbm_edit_jenis_kelamin" class="w-full bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-900 dark:text-white px-3.5 py-2.5 rounded-xl text-xs focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 outline-none transition-all cursor-pointer">
                            <option value="Laki-laki">Laki-laki</option>
                            <option value="Perempuan">Perempuan</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1">Instagram (Opsional)</label>
                        <input type="text" name="instagram" id="dbm_edit_instagram" placeholder="@username" class="w-full bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-900 dark:text-white px-3.5 py-2.5 rounded-xl text-xs focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 outline-none transition-all">
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1">Biodata / Minat (Opsional)</label>
                    <input type="text" name="bio" id="dbm_edit_bio" placeholder="Contoh: Minat Cyber Security & Programming" class="w-full bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-900 dark:text-white px-3.5 py-2.5 rounded-xl text-xs focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 outline-none transition-all">
                </div>

                <div class="flex items-center justify-end gap-2 pt-4 border-t border-slate-100 dark:border-slate-800">
                    <button type="button" onclick="closeEditMhsModal()" class="px-4 py-2.5 rounded-xl text-xs font-bold text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors cursor-pointer">Batal</button>
                    <button type="submit" class="px-5 py-2.5 rounded-xl text-xs font-bold bg-indigo-600 hover:bg-indigo-700 text-white shadow-lg shadow-indigo-600/20 active:scale-95 transition-all cursor-pointer">Simpan Perubahan</button>
                </div>
            </form>
        </div>
    </div>

<div id="modalTambahMhs" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs hidden transition-opacity">
        <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-2xl max-w-lg w-full max-h-[90vh] overflow-y-auto custom-scrollbar p-6">
            <div class="flex items-center justify-between pb-4 mb-4 border-b border-slate-100 dark:border-slate-800">
                <div>
                    <h3 class="text-base font-extrabold text-slate-900 dark:text-white">Tambah Mahasiswa Baru</h3>
                    <p class="text-xs text-slate-400 dark:text-slate-500">Otomatis terdaftar ke kelas <strong><?= htmlspecialchars(getNamaKelasAktif()) ?></strong>.</p>
                </div>
                <button type="button" onclick="closeTambahMhsModal()" class="w-8 h-8 rounded-lg bg-slate-100 dark:bg-slate-800 text-slate-400 hover:text-slate-700 dark:hover:text-white flex items-center justify-center transition-colors cursor-pointer">
                    <i class="fas fa-times text-xs"></i>
                </button>
            </div>

            <form action="../actions/kelola_pengurus.php" method="POST" class="space-y-4">
                <input type="hidden" name="action" value="tambah_mahasiswa">
                <input type="hidden" name="redirect_to" value="../database/db_mahasiswa.php">

                <div>
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1">Nama Lengkap *</label>
                    <input type="text" name="nama_lengkap" required placeholder="Contoh: Muhammad Ihsan" class="w-full bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-900 dark:text-white px-3.5 py-2.5 rounded-xl text-xs focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 outline-none transition-all">
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1">NIM Mahasiswa *</label>
                        <input type="text" name="nim" required placeholder="24020950..." class="w-full bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-900 dark:text-white px-3.5 py-2.5 rounded-xl text-xs focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 outline-none transition-all">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1">Nama Panggilan</label>
                        <input type="text" name="nama_panggilan" placeholder="Contoh: Ihsan" class="w-full bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-900 dark:text-white px-3.5 py-2.5 rounded-xl text-xs focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 outline-none transition-all">
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1">Jenis Kelamin</label>
                        <select name="jenis_kelamin" class="w-full bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-900 dark:text-white px-3.5 py-2.5 rounded-xl text-xs focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 outline-none transition-all cursor-pointer">
                            <option value="Laki-laki">Laki-laki</option>
                            <option value="Perempuan">Perempuan</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1">Instagram (Opsional)</label>
                        <input type="text" name="instagram" placeholder="@username" class="w-full bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-900 dark:text-white px-3.5 py-2.5 rounded-xl text-xs focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 outline-none transition-all">
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1">Biodata / Minat (Opsional)</label>
                    <input type="text" name="bio" placeholder="Contoh: Minat Cyber Security & Programming" class="w-full bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-900 dark:text-white px-3.5 py-2.5 rounded-xl text-xs focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 outline-none transition-all">
                </div>

                <!-- Pembuatan Akun Login Role Mahasiswa (Opsional) -->
                <div class="pt-3 border-t border-slate-100 dark:border-slate-800 space-y-3">
                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider">Akun Login Mahasiswa (Opsional)</label>
                        <p class="text-[10px] text-slate-400 mt-0.5">Isi jika mahasiswa ini (atau Anda sendiri) ingin memiliki akun login terpisah sebagai Mahasiswa.</p>
                    </div>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div>
                            <label class="block text-[11px] font-bold text-slate-700 dark:text-slate-300 mb-1">Email Login</label>
                            <input type="email" name="email" placeholder="contoh: mhs@simawa.id" class="w-full bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-900 dark:text-white px-3.5 py-2 rounded-xl text-xs focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 outline-none transition-all">
                        </div>
                        <div>
                            <label class="block text-[11px] font-bold text-slate-700 dark:text-slate-300 mb-1">Password Awal</label>
                            <input type="password" name="password" placeholder="Default: simawa2024" class="w-full bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-900 dark:text-white px-3.5 py-2 rounded-xl text-xs focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 outline-none transition-all">
                        </div>
                    </div>
                </div>

                <div class="flex items-center justify-end gap-2 pt-4 border-t border-slate-100 dark:border-slate-800">
                    <button type="button" onclick="closeTambahMhsModal()" class="px-4 py-2.5 rounded-xl text-xs font-bold text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors cursor-pointer">Batal</button>
                    <button type="submit" class="px-5 py-2.5 rounded-xl text-xs font-bold bg-indigo-600 hover:bg-indigo-700 text-white shadow-lg shadow-indigo-600/20 active:scale-95 transition-all cursor-pointer">Simpan Mahasiswa</button>
                </div>
            </form>
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

    <script>
        
        function openEditMhsModal(mhs) {
            document.getElementById("dbm_edit_mhs_id").value = mhs.id || "";
            document.getElementById("dbm_edit_nama_lengkap").value = mhs.nama_lengkap || "";
            document.getElementById("dbm_edit_nim").value = mhs.nim || "";
            document.getElementById("dbm_edit_nama_panggilan").value = mhs.nama_panggilan || "";
            document.getElementById("dbm_edit_jenis_kelamin").value = mhs.jenis_kelamin || "Laki-laki";
            document.getElementById("dbm_edit_instagram").value = mhs.instagram || "";
            document.getElementById("dbm_edit_bio").value = mhs.bio || "";
            document.getElementById("modalEditMhs").classList.remove("hidden");
        }
        function closeEditMhsModal() {
            document.getElementById("modalEditMhs").classList.add("hidden");
        }

        function openTambahMhsModal() {
            document.getElementById("modalTambahMhs").classList.remove("hidden");
        }
        function closeTambahMhsModal() {
            document.getElementById("modalTambahMhs").classList.add("hidden");
        }
    </script>


<?php if (!empty($_SESSION['success'])): ?>
    <script>
        Swal.fire({
            icon: 'success',
            title: 'Berhasil!',
            html: <?= json_encode($_SESSION['success']) ?>,
            confirmButtonColor: '#4f46e5'
        });
    </script>
    <?php unset($_SESSION['success']); ?>
<?php endif; ?>
<?php if (!empty($_SESSION['error'])): ?>
    <script>
        Swal.fire({
            icon: 'error',
            title: 'Gagal!',
            html: <?= json_encode($_SESSION['error']) ?>,
            confirmButtonColor: '#ef4444'
        });
    </script>
    <?php unset($_SESSION['error']); ?>
<?php endif; ?>

</body>

</html>