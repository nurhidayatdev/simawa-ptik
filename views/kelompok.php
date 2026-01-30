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

// Include foto helpers for cache busting
include_once '../functions/foto_helpers.php';

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
            $res2 = mysqli_query($koneksi, "SELECT nim, nama_lengkap, foto FROM mahasiswa WHERE nim IN ($inlist)");
            while ($r = mysqli_fetch_assoc($res2)) {
                $student_map[$r['nim']] = [
                    'nama' => $r['nama_lengkap'],
                    'foto' => $r['foto']
                ];
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
                <div>
                    <h3 class="text-xl font-semibold text-slate-800">Data Kelompok</h3>
                    <p class="text-xs text-slate-500 mt-1">Daftar pembagian kelompok mahasiswa per mata kuliah.</p>
                </div>
                <?php if ($role === 'Administrator Utama' || $role === 'Ketua Tingkat'): ?>
                    <div class="flex flex-col sm:flex-row gap-2 w-full sm:w-auto">
                        <form method="GET" class="flex flex-col sm:flex-row gap-4 w-full sm:w-auto">




                            <?php if ($selected_matkul): ?>
                                <?php if (count($sets) > 0): ?>
                                    <button type="button" onclick="confirmBagikan()" class="bg-green-600 text-white px-4 py-2 rounded-lg hover:bg-green-700 text-center text-xs shadow-sm transition-all">
                                        <i class="fab fa-whatsapp mr-2 text-sm"></i> Bagikan
                                    </button>
                                    <a href="../actions/edit_kelompok?matkul_id=<?= $selected_matkul ?>&dosen=<?= urlencode($current_set['dosen']) ?>&keterangan=<?= urlencode($current_set['keterangan']) ?>" class="bg-blue-500 text-white px-4 py-2 rounded-lg hover:bg-blue-600 text-center text-xs shadow-sm transition-all">
                                        <i class="fa fa-edit mr-2"></i> Edit
                                    </a>
                                <?php endif; ?>
                                <a href="../actions/tambah_kelompok?matkul_id=<?= $selected_matkul ?>" class="bg-indigo-600 text-white px-4 py-2 rounded-lg hover:bg-indigo-700 text-center text-xs shadow-md transition-all">
                                    <i class="fa fa-plus mr-2"></i> Baru
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


            <form id="selectForm" method="GET" class="my-6 grid grid-cols-1 md:grid-cols-2 gap-4 max-w-2xl">
                <div>
                    <label class="text-[10px] font-semibold text-slate-400 uppercase tracking-widest block mb-1.5">Mata Kuliah</label>
                    <div class="relative">
                        <select id="mata_kuliah" name="matkul_id" class="w-full bg-slate-50 border border-slate-200 text-xs font-semibold text-indigo-700 rounded-lg px-3 py-2.5 appearance-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 cursor-pointer shadow-sm" onchange="this.form.submit()">
                            <option value="">-- Pilih Mata Kuliah --</option>
                            <?php while ($mk = mysqli_fetch_assoc($matkuls_res)) : ?>
                                <option value="<?= $mk['id'] ?>" <?= ($mk['id'] == $selected_matkul) ? 'selected' : '' ?>><?= htmlspecialchars($mk['nama_matkul']) ?></option>
                            <?php endwhile; ?>
                        </select>
                        <div class="absolute inset-y-0 right-0 flex items-center px-2 pointer-events-none text-slate-400">
                            <i class="fa fa-chevron-down text-[10px]"></i>
                        </div>
                    </div>
                </div>
                <?php if (count($sets) > 1): ?>
                    <div>
                        <label class="text-[10px] font-semibold text-slate-400 uppercase tracking-widest block mb-1.5">Set Kelompok</label>
                        <div class="relative">
                            <select name="set_idx" class="w-full bg-slate-50 border border-slate-200 text-xs font-semibold text-indigo-700 rounded-lg px-3 py-2.5 appearance-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 cursor-pointer shadow-sm" onchange="this.form.submit()">
                                <?php foreach ($sets as $idx => $s): ?>
                                    <option value="<?= $idx ?>" <?= $idx == $selected_set_idx ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($s['dosen']) ?> <?= !empty($s['keterangan']) ? '(' . htmlspecialchars($s['keterangan']) . ')' : '' ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <div class="absolute inset-y-0 right-0 flex items-center px-2 pointer-events-none text-slate-400">
                                <i class="fa fa-chevron-down text-[10px]"></i>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            </form>

            <?php if ($selected_matkul): ?>
                <?php if (count($sets) === 0): ?>
                    <div class="p-4 bg-yellow-100 text-yellow-800 text-xs">Tidak ada kelompok tersimpan untuk mata kuliah ini.</div>
                <?php else: ?>
                    <div class="mb-4 p-3 bg-indigo-50 border border-indigo-100 rounded-lg">
                        <div class="text-[10px] font-semibold text-indigo-400 uppercase tracking-wider mb-1">Set Aktif</div>
                        <div class="text-sm font-semibold text-indigo-900">
                            👨‍🏫 <?= htmlspecialchars($current_set['dosen']) ?>
                            <?php if (!empty($current_set['keterangan'])): ?>
                                <span class="ml-2 px-2 py-0.5 bg-indigo-200 text-indigo-800 rounded text-[10px] uppercase font-semibold"><?= htmlspecialchars($current_set['keterangan']) ?></span>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div id="groupsContainer" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
                        <?php foreach ($groups as $idx => $g): ?>
                            <div class="bg-white rounded-xl border border-slate-200 overflow-hidden hover:shadow-md transition-all group-card">
                                <div class="bg-slate-50 px-4 py-3 border-b border-slate-100">
                                    <h4 class="font-semibold text-indigo-900 text-sm">Kelompok <?= htmlspecialchars($g['kelompok_no']) ?></h4>
                                </div>
                                <div class="p-4">
                                    <ul class="space-y-3">
                                        <?php
                                        $parts = array_filter(array_map('trim', explode(',', $g['anggota'])));
                                        foreach ($parts as $nim):
                                            $s_data = $student_map[$nim] ?? ['nama' => $nim, 'foto' => ''];
                                        ?>
                                            <li class="flex items-center gap-3">
                                                <div class="h-8 w-8 rounded-full overflow-hidden border border-slate-200 shrink-0 shadow-sm">
                                                    <img src="<?= profilePhotoUrl($s_data['foto'], '../') ?>" class="w-full h-full object-cover" alt="<?= htmlspecialchars($s_data['nama']) ?>">
                                                </div>
                                                <span class="text-[11px] font-semibold text-slate-700 truncate"><?= htmlspecialchars($s_data['nama']) ?></span>
                                            </li>
                                        <?php endforeach; ?>
                                    </ul>
                                </div>
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

        function confirmBagikan() {
            Swal.fire({
                title: 'Bagikan Kelompok?',
                text: "Daftar kelompok ini akan dikirimkan ke WhatsApp Grup.",
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#22c55e',
                cancelButtonColor: '#64748b',
                confirmButtonText: 'Ya, Kirim Sekarang!',
                cancelButtonText: 'Batal'
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = "../actions/bagikan_kelompok?matkul_id=<?= $selected_matkul ?>&dosen=<?= urlencode($current_set['dosen'] ?? '') ?>&keterangan=<?= urlencode($current_set['keterangan'] ?? '') ?>";
                }
            })
        }
    </script>

</body>

</html>