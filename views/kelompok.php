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

if ($role === 'Administrator Utama') {
    $_SESSION['error'] = 'Akses ditolak! Administrator Utama tidak mengelola kelompok kelas.';
    header('Location: ../dashboard_admin.php');
    exit;
}

// Include foto helpers for cache busting
include_once '../functions/foto_helpers.php';

/* =====================
   AMBIL PERIODE AKTIF
   ===================== */
$q_periode = mysqli_query($koneksi, "SELECT id FROM periode WHERE status = 'aktif' LIMIT 1");
$periode = mysqli_fetch_assoc($q_periode);
$periode_id = $periode['id'];

// Ambil daftar mata kuliah yang punya kelompok tersimpan
$where_k_kelas = getKelasWhereClause('k', true);
$matkuls_res = mysqli_query($koneksi, "SELECT DISTINCT k.matkul_id AS id, mk.nama_matkul
	FROM kelompok k
	JOIN mata_kuliah mk ON k.matkul_id = mk.id
	WHERE k.periode_id = $periode_id $where_k_kelas
	ORDER BY mk.semester, mk.nama_matkul");

$selected_matkul = intval($_GET['matkul_id'] ?? 0);
$sets = [];
$selected_set_idx = intval($_GET['set_idx'] ?? 0);
$groups = [];
$student_map = [];

if ($selected_matkul) {
    // Get all unique sets for this matkul
    $where_k_set = getKelasWhereClause('', true);
    $q_sets = mysqli_query($koneksi, "SELECT DISTINCT dosen, keterangan FROM kelompok WHERE matkul_id = $selected_matkul AND periode_id = $periode_id $where_k_set");
    while ($rs = mysqli_fetch_assoc($q_sets)) {
        $sets[] = $rs;
    }

    if (count($sets) > 0) {
        $current_set = $sets[$selected_set_idx] ?? $sets[0];
        $dosen_filter = mysqli_real_escape_string($koneksi, $current_set['dosen']);
        $keterangan_filter = mysqli_real_escape_string($koneksi, $current_set['keterangan']);

        $where_k_stmt = getKelasWhereClause('', true);
        $stmt = mysqli_prepare($koneksi, "SELECT kelompok_no, anggota FROM kelompok WHERE matkul_id = ? AND periode_id = ? AND dosen = ? AND keterangan = ? $where_k_stmt ORDER BY kelompok_no");
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
                $student_map[$r['nim']] = [
                    'nama' => $r['nama_lengkap'],
                    
                ];
            }
        }
    }
}

// Data Kelompok Saya (Summary)
$q_my_nim = mysqli_query($koneksi, "SELECT nim FROM mahasiswa WHERE user_id = '$user_id'");
$my_nim = mysqli_fetch_assoc($q_my_nim)['nim'] ?? '';
$my_groups_data = [];
if ($my_nim) {
    $where_k_my = getKelasWhereClause('k', true);
    $q_mg = mysqli_query($koneksi, "SELECT k.*, mk.nama_matkul 
        FROM kelompok k 
        JOIN mata_kuliah mk ON k.matkul_id = mk.id 
        WHERE (FIND_IN_SET('$my_nim', REPLACE(k.anggota, ' ', '')) OR k.anggota LIKE '%$my_nim%')
        AND k.periode_id = $periode_id
        $where_k_my
        ORDER BY mk.nama_matkul ASC");
    while ($mg = mysqli_fetch_assoc($q_mg)) {
        // Ambil nama anggota
        $nims = array_filter(array_map('trim', explode(',', $mg['anggota'])));
        $teammates = [];
        if (count($nims) > 0) {
            $n_list = "'" . implode("','", array_map(fn($n) => mysqli_real_escape_string($koneksi, $n), $nims)) . "'";
            $q_names = mysqli_query($koneksi, "SELECT nama_lengkap, nim FROM mahasiswa WHERE nim IN ($n_list)");
            while ($qn = mysqli_fetch_assoc($q_names)) {
                $name = htmlspecialchars($qn['nama_lengkap']);
                $teammates[] = "<li class='flex items-center gap-2'><i class='fa fa-circle text-[6px] text-slate-300 dark:text-slate-600 ml-1'></i> $name</li>";
            }
        }
        $mg['teammates_html'] = "<ul class='mt-1 space-y-1'>" . implode('', $teammates) . "</ul>";
        $my_groups_data[] = $mg;
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>

    <title>Data Kelompok - SIMAWA PTIK 2024</title>
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

<body class="bg-slate-100 dark:bg-slate-950 transition-colors duration-300">
    <?php include '../includes/menu_db.php'; ?>
    <main class="ml-0 lg:ml-48 pt-20 p-6 flex flex-col min-h-screen">
        <div class="bg-white dark:bg-slate-900 rounded-lg border border-slate-200 dark:border-slate-800 p-6 flex-grow w-full transition-colors duration-300">
            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-2">
                <div>
                    <h3 class="text-xl font-semibold text-slate-800 dark:text-white">Data Kelompok</h3>
                    <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">Daftar pembagian kelompok mahasiswa per mata kuliah.</p>
                </div>
                <div class="flex flex-col sm:flex-row gap-2 w-full sm:w-auto mt-4 sm:mt-0">
                    <button type="button" onclick="showMyGroups()" class="bg-indigo-50 dark:bg-indigo-950/30 text-indigo-700 dark:text-indigo-400 border border-indigo-100 dark:border-indigo-900/50 px-4 py-2 rounded-lg hover:bg-indigo-100 dark:hover:bg-indigo-900/50 text-center text-[11px] font-bold transition-all flex items-center justify-center gap-2 shadow-sm">
                        <i class="fa fa-user-friends"></i> Kelompok Saya
                    </button>

                    <?php if ($role === 'Ketua Tingkat'): ?>
                        <div class="flex flex-col sm:flex-row gap-2">
                            <?php if ($selected_matkul): ?>
                                <?php if (count($sets) > 0): ?>
                                    <button type="button" onclick="confirmBagikan()" class="bg-green-600 text-white px-3 py-2 rounded-lg hover:bg-green-700 text-center text-[10px] font-bold shadow-sm transition-all">
                                        <i class="fab fa-whatsapp mr-1.5 text-xs"></i> Bagikan
                                    </button>
                                    <a href="../actions/edit_kelompok?matkul_id=<?= $selected_matkul ?>&dosen=<?= urlencode($current_set['dosen']) ?>&keterangan=<?= urlencode($current_set['keterangan']) ?>" class="bg-blue-500 text-white px-3 py-2 rounded-lg hover:bg-blue-600 text-center text-[10px] font-bold shadow-sm transition-all">
                                        <i class="fa fa-edit mr-1.5"></i> Edit
                                    </a>
                                <?php endif; ?>
                                <button type="button" onclick="showTambahMenu(<?= $selected_matkul ?>)" class="bg-indigo-600 text-white px-3 py-2 rounded-lg hover:bg-indigo-700 text-center text-[10px] font-bold shadow-md transition-all">
                                    <i class="fa fa-plus mr-1.5"></i> Atur Kelompok Baru
                                </button>
                            <?php else: ?>
                                <button type="button" onclick="showTambahMenu()" class="bg-indigo-600 text-white px-3 py-2 rounded-lg hover:bg-indigo-800 text-center text-[10px] font-bold shadow-md transition-all">
                                    <i class="fa fa-plus mr-1.5"></i> Atur Kelompok Baru
                                </button>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>


            <form id="selectForm" method="GET" class="my-6 grid grid-cols-1 md:grid-cols-2 gap-4 max-w-2xl">
                <div>
                    <label class="text-[10px] font-semibold text-slate-400 dark:text-slate-500 uppercase tracking-widest block mb-1.5">Mata Kuliah</label>
                    <div class="relative">
                        <select id="mata_kuliah" name="matkul_id" class="w-full bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-xs font-semibold text-indigo-700 dark:text-indigo-400 rounded-lg px-3 py-2.5 appearance-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 cursor-pointer shadow-sm transition-colors duration-200" onchange="this.form.submit()">
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
                        <label class="text-[10px] font-semibold text-slate-400 dark:text-slate-500 uppercase tracking-widest block mb-1.5">Set Kelompok</label>
                        <div class="relative">
                            <select name="set_idx" class="w-full bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-xs font-semibold text-indigo-700 dark:text-indigo-400 rounded-lg px-3 py-2.5 appearance-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 cursor-pointer shadow-sm transition-colors duration-200" onchange="this.form.submit()">
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
                    <div class="mb-4 p-3 bg-indigo-50 dark:bg-indigo-950/20 border border-indigo-100 dark:border-indigo-900/40 rounded-lg">
                        <div class="text-[10px] font-semibold text-indigo-400 uppercase tracking-wider mb-1">Set Aktif</div>
                        <div class="text-sm font-semibold text-indigo-900 dark:text-indigo-300">
                            👨‍🏫 <?= htmlspecialchars($current_set['dosen']) ?>
                            <?php if (!empty($current_set['keterangan'])): ?>
                                <span class="ml-2 px-2 py-0.5 bg-indigo-200 dark:bg-indigo-900 text-indigo-800 dark:text-indigo-300 rounded text-[10px] uppercase font-semibold"><?= htmlspecialchars($current_set['keterangan']) ?></span>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div id="groupsContainer" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
                        <?php foreach ($groups as $idx => $g): ?>
                            <div class="bg-white dark:bg-slate-800/50 rounded-xl border border-slate-200 dark:border-slate-700 overflow-hidden hover:shadow-md transition-all group-card">
                                <div class="bg-slate-50 dark:bg-slate-800 px-4 py-3 border-b border-slate-100 dark:border-slate-700">
                                    <h4 class="font-semibold text-indigo-900 dark:text-indigo-400 text-sm">Kelompok <?= htmlspecialchars($g['kelompok_no']) ?></h4>
                                </div>
                                <div class="p-4">
                                    <ul class="space-y-3">
                                        <?php
                                        $parts = array_filter(array_map('trim', explode(',', $g['anggota'])));
                                        foreach ($parts as $nim):
                                            $s_data = $student_map[$nim] ?? ['nama' => $nim, 'foto' => ''];
                                        ?>
                                            <li class="flex items-center gap-3">
                                                <div class="h-8 w-8 rounded-full overflow-hidden border border-slate-200 dark:border-slate-700 shrink-0 shadow-sm">
                                                    <img src="<?= profilePhotoUrl($s_data['nama'], '../') ?>" class="w-full h-full object-cover" alt="<?= htmlspecialchars($s_data['nama']) ?>">
                                                </div>
                                                <span class="text-[11px] font-semibold text-slate-700 dark:text-slate-300 truncate"><?= htmlspecialchars($s_data['nama']) ?></span>
                                            </li>
                                        <?php endforeach; ?>
                                    </ul>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            <?php else: ?>
                <div class="p-3 text-xs text-slate-600 dark:text-slate-400">Pilih mata kuliah untuk melihat kelompok yang tersimpan.</div>
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

        function showTambahMenu(matkulId = null) {
            const isDark = document.documentElement.classList.contains('dark');
            const query = matkulId ? `?matkul_id=${matkulId}` : '';
            Swal.fire({
                title: 'Pilih Logika Pengacakan',
                background: isDark ? '#0f172a' : '#ffffff',
                color: isDark ? '#f8fafc' : '#0f172a',
                html: `
                    <div class="grid grid-cols-1 gap-3 mt-4">
                        <a href="../actions/tambah_kelompok_murni${query}" class="flex items-center gap-4 p-4 rounded-xl border border-slate-100 dark:border-slate-800 hover:bg-slate-50 dark:hover:bg-slate-800 transition-all text-left group">
                            <div class="w-10 h-10 rounded-lg bg-amber-100 dark:bg-amber-900/30 flex items-center justify-center text-amber-600">
                                <i class="fa fa-random text-lg"></i>
                            </div>
                            <div>
                                <h4 class="text-sm font-bold text-slate-700 dark:text-slate-200">Acak Murni</h4>
                                <p class="text-[10px] text-slate-500 uppercase tracking-wide">Bebas & tanpa aturan gender</p>
                            </div>
                        </a>
                        <a href="../actions/tambah_kelompok_berimbang${query}" class="flex items-center gap-4 p-4 rounded-xl border border-slate-100 dark:border-slate-800 hover:bg-slate-50 dark:hover:bg-slate-800 transition-all text-left group">
                            <div class="w-10 h-10 rounded-lg bg-indigo-100 dark:bg-indigo-900/30 flex items-center justify-center text-indigo-600">
                                <i class="fa fa-balance-scale text-lg"></i>
                            </div>
                            <div>
                                <h4 class="text-sm font-bold text-slate-700 dark:text-slate-200">Berimbang</h4>
                                <p class="text-[10px] text-slate-500 uppercase tracking-wide">Jumlah laki-laki & perempuan merata</p>
                            </div>
                        </a>
                        <a href="../actions/tambah_kelompok_sejenis${query}" class="flex items-center gap-4 p-4 rounded-xl border border-slate-100 dark:border-slate-800 hover:bg-slate-50 dark:hover:bg-slate-800 transition-all text-left group">
                            <div class="w-10 h-10 rounded-lg bg-pink-100 dark:bg-pink-900/30 flex items-center justify-center text-pink-600">
                                <i class="fa fa-users text-lg"></i>
                            </div>
                            <div>
                                <h4 class="text-sm font-bold text-slate-700 dark:text-slate-200">Sejenis (Per Gender)</h4>
                                <p class="text-[10px] text-slate-500 uppercase tracking-wide">Kelompok tidak dicampur gender</p>
                            </div>
                        </a>
                    </div>
                `,
                showConfirmButton: false,
                showCloseButton: true,
                width: '450px',
                customClass: {
                    popup: 'rounded-[2rem] border dark:border-slate-800 p-8 shadow-2xl transition-all duration-300',
                    title: 'text-xl font-bold transition-colors'
                }
            });
        }

        function showMyGroups() {
            // Gunakan template hidden untuk menghindari masalah backtick/JS syntax
            const template = document.getElementById('myGroupsTemplate');
            if (!template) return;

            Swal.fire({
                title: 'Daftar Kelompok Saya',
                html: template.innerHTML,
                width: '550px',
                confirmButtonText: 'Tutup',
                confirmButtonColor: '#312e81',
                padding: '1.5rem',
                customClass: {
                    title: 'text-xl font-bold text-slate-800 dark:text-white mb-1',
                    popup: 'rounded-2xl dark:bg-slate-900 dark:border dark:border-slate-800'
                }
            });
        }
    </script>

    <template id="myGroupsTemplate">
        <div class="text-left space-y-4 py-2 max-h-[60vh] overflow-y-auto pr-2 custom-scrollbar">
            <?php if (count($my_groups_data) === 0): ?>
                <div class="p-6 text-center text-slate-400 dark:text-slate-500">
                    <i class="fa fa-folder-open text-4xl mb-3 block"></i>
                    <p class="text-sm">Kamu belum terdaftar di kelompok manapun semester ini.</p>
                </div>
            <?php else: ?>
                <?php foreach ($my_groups_data as $mg): ?>
                    <div class="bg-indigo-50/50 dark:bg-indigo-950/20 border border-indigo-100 dark:border-indigo-900/40 p-4 rounded-xl shadow-sm">
                        <div class="flex justify-between items-start mb-2">
                            <h5 class="font-bold text-indigo-900 dark:text-indigo-400 text-sm leading-tight"><?= htmlspecialchars($mg['nama_matkul']) ?></h5>
                            <span class="bg-indigo-600 text-white text-[9px] font-bold px-2 py-0.5 rounded-full uppercase">Kelompok <?= $mg['kelompok_no'] ?></span>
                        </div>
                        <div class="space-y-1.5">
                            <p class="text-[10px] text-slate-500 dark:text-slate-400 flex items-center gap-1.5 italic">
                                <i class="fa fa-chalkboard-teacher text-indigo-400"></i>
                                <?= htmlspecialchars($mg['dosen']) ?> <?= !empty($mg['keterangan']) ? '• ' . htmlspecialchars($mg['keterangan']) : '' ?>
                            </p>
                            <div class="text-[11px] text-slate-700 dark:text-slate-300 leading-relaxed pt-2 border-t border-indigo-100/50 dark:border-indigo-900/40 mt-2">
                                <span class="text-[10px] font-bold text-indigo-700 dark:text-indigo-400 uppercase tracking-widest mb-1 block">Rekan Tim:</span>
                                <?= $mg['teammates_html'] ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </template>
</body>

</html>