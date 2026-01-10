<?php
session_start();
include '../koneksi.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../../index");
    exit;
}

$user_id = $_SESSION['user_id'];
$nama_lengkap = $_SESSION['nama_lengkap'] ?? '';
$foto = $_SESSION['foto'] ?? '';
$role = $_SESSION['role'];


/* =====================
   AMBIL PERIODE AKTIF
   ===================== */
$q_periode = mysqli_query($koneksi, "SELECT id FROM periode WHERE status = 'aktif' LIMIT 1");
$periode = mysqli_fetch_assoc($q_periode);
$periode_id = $periode['id'];

// Ambil daftar mata kuliah yang punya kelompok tersimpan
$matkuls_res = mysqli_query($koneksi, "SELECT DISTINCT k.matkul_id AS id, mk.nama_matkul
	FROM kelompok k
	JOIN mata_kuliah mk ON k.matkul_id = mk.id
	WHERE k.periode_id = $periode_id
	ORDER BY mk.semester, mk.nama_matkul");

$selected_matkul = intval($_GET['matkul_id'] ?? 0);
$sets = [];
$selected_set_idx = intval($_GET['set_idx'] ?? 0);
$groups = [];
$student_map = [];

if ($selected_matkul) {
    // Get all unique sets for this matkul
    $q_sets = mysqli_query($koneksi, "SELECT DISTINCT dosen, keterangan FROM kelompok WHERE matkul_id = $selected_matkul AND periode_id = $periode_id");
    while ($rs = mysqli_fetch_assoc($q_sets)) {
        $sets[] = $rs;
    }

    if (count($sets) > 0) {
        $current_set = $sets[$selected_set_idx] ?? $sets[0];
        $dosen_filter = mysqli_real_escape_string($koneksi, $current_set['dosen']);
        $keterangan_filter = mysqli_real_escape_string($koneksi, $current_set['keterangan']);

        $stmt = mysqli_prepare($koneksi, "SELECT kelompok_no, anggota FROM kelompok WHERE matkul_id = ? AND periode_id = ? AND dosen = ? AND keterangan = ? ORDER BY kelompok_no");
        mysqli_stmt_bind_param($stmt, 'iiss', $selected_matkul, $periode_id, $dosen_filter, $keterangan_filter);
        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);
        while ($r = mysqli_fetch_assoc($res)) {
            $groups[] = $r;
        }

        // Kumpulkan semua NIM dari semua kelompok, lalu ambil nama mahasiswa
        $all_nims = [];
        foreach ($groups as $g) {
            $parts = array_filter(array_map('trim', explode(',', $g['anggota'])));
            foreach ($parts as $nim) {
                if ($nim !== '') $all_nims[$nim] = true;
            }
        }

        if (count($all_nims) > 0) {
            $escaped = array_map(function ($s) use ($koneksi) {
                return "'" . mysqli_real_escape_string($koneksi, $s) . "'";
            }, array_keys($all_nims));
            $inlist = implode(',', $escaped);
            $res2 = mysqli_query($koneksi, "SELECT nim, nama_lengkap FROM mahasiswa WHERE nim IN ($inlist)");
            while ($r = mysqli_fetch_assoc($res2)) {
                $student_map[$r['nim']] = $r['nama_lengkap'];
            }
        }
    }
}

?>
<!DOCTYPE html>
<html lang="en">

<head>

    <title>Data Kelompok - PTIK C 2024</title>
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

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>

<body class="bg-slate-100">
    <?php include '../includes/menu_db.php'; ?>
    <main class="ml-0 lg:ml-48 pt-20 p-6 flex flex-col min-h-screen">
        <div class="bg-white rounded-lg border border-slate-200 p-6 flex-grow w-full">
            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-2">
                <h3 class="text-xl font-bold text-slate-800 mb-4">Data Kelompok</h3>
                <?php if ($role === 'Administrator Utama' || $role === 'Ketua Tingkat'): ?>
                    <div class="flex flex-col sm:flex-row gap-2 w-full sm:w-auto">
                        <form method="GET" class="flex flex-col sm:flex-row gap-4 w-full sm:w-auto">




                            <?php if ($selected_matkul): ?>
                                <?php if (count($sets) > 0): ?>
                                    <a href="../actions/edit_kelompok?matkul_id=<?= $selected_matkul ?>&dosen=<?= urlencode($current_set['dosen']) ?>&keterangan=<?= urlencode($current_set['keterangan']) ?>" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-800 text-center text-xs">
                                        <i class="fa fa-edit mr-2"></i> Edit Kelompok
                                    </a>
                                <?php endif; ?>
                                <a href="../actions/tambah_kelompok?matkul_id=<?= $selected_matkul ?>" class="bg-indigo-600 text-white px-4 py-2 rounded-lg hover:bg-indigo-800 text-center text-xs">
                                    <i class="fa fa-plus mr-2"></i> Atur Kelompok Baru
                                </a>
                            <?php else: ?>
                                <a href="../actions/tambah_kelompok" class="bg-indigo-600 text-white px-4 py-2 rounded-lg hover:bg-indigo-800 text-center text-xs">
                                    <i class="fa fa-plus mr-2"></i> Atur Kelompok Baru
                                </a>
                            <?php endif; ?>
                        </form>
                    </div>
                <?php endif; ?>
            </div>


            <form id="selectForm" method="GET" class="mb-4 grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="text-xs block mb-1">Pilih Mata Kuliah:</label>
                    <select id="mata_kuliah" name="matkul_id" class="w-full px-3 py-2 border rounded text-xs" onchange="this.form.submit()">
                        <option value="">-- Pilih --</option>
                        <?php while ($mk = mysqli_fetch_assoc($matkuls_res)) : ?>
                            <option value="<?= $mk['id'] ?>" <?= ($mk['id'] == $selected_matkul) ? 'selected' : '' ?>><?= htmlspecialchars($mk['nama_matkul']) ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <?php if (count($sets) > 1): ?>
                    <div>
                        <label class="text-xs block mb-1">Pilih Set Kelompok (Dosen/Keterangan):</label>
                        <select name="set_idx" class="w-full px-3 py-2 border rounded text-xs" onchange="this.form.submit()">
                            <?php foreach ($sets as $idx => $s): ?>
                                <option value="<?= $idx ?>" <?= $idx == $selected_set_idx ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($s['dosen']) ?> <?= !empty($s['keterangan']) ? '(' . htmlspecialchars($s['keterangan']) . ')' : '' ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                <?php endif; ?>
            </form>

            <?php if ($selected_matkul): ?>
                <?php if (count($sets) === 0): ?>
                    <div class="p-4 bg-yellow-100 text-yellow-800 text-xs">Tidak ada kelompok tersimpan untuk mata kuliah ini.</div>
                <?php else: ?>
                    <div class="mb-4 p-3 bg-indigo-50 border border-indigo-100 rounded-lg">
                        <div class="text-[10px] font-bold text-indigo-400 uppercase tracking-wider mb-1">Set Aktif</div>
                        <div class="text-sm font-bold text-indigo-900">
                            👨‍🏫 <?= htmlspecialchars($current_set['dosen']) ?>
                            <?php if (!empty($current_set['keterangan'])): ?>
                                <span class="ml-2 px-2 py-0.5 bg-indigo-200 text-indigo-800 rounded text-[10px] uppercase font-bold"><?= htmlspecialchars($current_set['keterangan']) ?></span>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div id="groupsContainer" class="flex flex-wrap gap-4 justify-between">
                        <?php foreach ($groups as $idx => $g): ?>
                            <div class="p-4 border rounded bg-slate-50 w-56 shadow-sm hover:shadow-md transition-shadow">
                                <h4 class="font-bold mb-3 text-indigo-900 border-b border-indigo-100 pb-2 text-sm">Kelompok <?= htmlspecialchars($g['kelompok_no']) ?></h4>
                                <ul class="space-y-2 text-xs">
                                    <?php
                                    $parts = array_filter(array_map('trim', explode(',', $g['anggota'])));
                                    foreach ($parts as $nim):
                                        $name = $student_map[$nim] ?? $nim;
                                    ?>
                                        <li class="flex items-center gap-2">
                                            <div class="h-1.5 w-1.5 rounded-full bg-indigo-400"></div>
                                            <span class="text-slate-700"><?= htmlspecialchars($name) ?></span>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            <?php else: ?>
                <div class="p-3 text-xs text-slate-600">Pilih mata kuliah untuk melihat kelompok yang tersimpan.</div>
            <?php endif; ?>

        </div>
        <?php include '../includes/footer_db.php'; ?>
    </main>
    <?php include '../includes/script_db.php'; ?>

    <script>
        document.getElementById('mata_kuliah').addEventListener('change', function() {
            document.getElementById('selectForm').submit();
        });

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
                text: '<?= $_SESSION['error'] ?>',
                timer: 3000,
                showConfirmButton: false
            });
            <?php unset($_SESSION['error']); ?>
        <?php endif; ?>
    </script>

</body>

</html>