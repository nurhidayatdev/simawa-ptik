<?php
session_start();
include '../koneksi.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../../index.php");
    exit;
}

$nama_lengkap = $_SESSION['nama_lengkap'];
$user_id = $_SESSION['user_id'];
$foto = $_SESSION['foto'] ?? '';
$role = $_SESSION['role'];

// Include foto helpers for cache busting
include_once '../functions/foto_helpers.php';

$query = "SELECT id FROM mahasiswa WHERE user_id = ?";
$stmt  = mysqli_prepare($koneksi, $query);
mysqli_stmt_bind_param($stmt, "i", $_SESSION['user_id']);
mysqli_stmt_execute($stmt);
$res_mhs = mysqli_stmt_get_result($stmt);
$mahasiswa = mysqli_fetch_assoc($res_mhs);

$mahasiswa_id = $mahasiswa['id'] ?? null;
// Fallback jika user terhubung lewat linked_student_nim (misal akun Ketua Tingkat/Pengurus)
if (empty($mahasiswa_id) && !empty($data['linked_student_nim'])) {
    $q_link = mysqli_query($koneksi, "SELECT id FROM mahasiswa WHERE nim = '" . mysqli_real_escape_string($koneksi, $data['linked_student_nim']) . "' LIMIT 1");
    if ($r_link = mysqli_fetch_assoc($q_link)) {
        $mahasiswa_id = $r_link['id'];
    }
}


// ambil data user (LEFT JOIN agar role fungsional non-mahasiswa tetap bisa lihat profil)
$query = "
SELECT 
    u.id AS user_id,
    u.role,
    u.password,
    u.email,
    COALESCE(NULLIF(u.nama_lengkap, ''), m.nama_lengkap, 'Pengguna') AS nama_lengkap,
    u.linked_student_nim,
    m.nim,
            m.bio
FROM users u
LEFT JOIN mahasiswa m ON m.user_id = u.id
WHERE u.id = ?
LIMIT 1
";

$stmt = mysqli_prepare($koneksi, $query);
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$data = mysqli_fetch_assoc($result);



/* =====================
   PROGRES KELULUSAN 146 SKS PTIK
   ===================== */
$target_sks_ptik = 146;
$total_sks_lulus = 0;
$sks_berjalan    = 0;
$sks_per_semester = [];

if (!empty($mahasiswa_id)) {
    $q_sks_all = mysqli_query($koneksi, "
        SELECT 
            p.id as periode_id,
            p.semester_ke,
            p.tahun_periode,
            p.semester_nama,
            p.status as periode_status,
            mk.id as matkul_id,
            mk.kode_matkul,
            mk.nama_matkul,
            mk.sks,
            COALESCE(kv.status, 'approved') as status_validasi
        FROM krs k
        JOIN mata_kuliah mk ON k.matkul_id = mk.id
        JOIN periode p ON k.periode_id = p.id
        LEFT JOIN krs_validasi kv ON kv.mahasiswa_id = k.mahasiswa_id AND kv.periode_id = k.periode_id
        WHERE k.mahasiswa_id = $mahasiswa_id
        ORDER BY p.semester_ke ASC, mk.kode_matkul ASC
    ");

    if ($q_sks_all) {
        while ($r_krs = mysqli_fetch_assoc($q_sks_all)) {
            $pid = $r_krs['periode_id'];
            if (!isset($sks_per_semester[$pid])) {
                $sks_per_semester[$pid] = [
                    'periode_id'     => $pid,
                    'semester_ke'    => $r_krs['semester_ke'],
                    'tahun_periode'  => $r_krs['tahun_periode'],
                    'semester_nama'  => $r_krs['semester_nama'],
                    'periode_status' => $r_krs['periode_status'],
                    'status_validasi'=> $r_krs['status_validasi'],
                    'total_sks'      => 0,
                    'total_matkul'   => 0,
                    'matkul'         => []
                ];
            }
            $sks_per_semester[$pid]['total_sks'] += (int)$r_krs['sks'];
            $sks_per_semester[$pid]['total_matkul']++;
            $sks_per_semester[$pid]['matkul'][] = [
                'kode_matkul' => $r_krs['kode_matkul'],
                'nama_matkul' => $r_krs['nama_matkul'],
                'sks'         => (int)$r_krs['sks']
            ];

            if ($r_krs['status_validasi'] === 'approved') {
                $total_sks_lulus += (int)$r_krs['sks'];
                if ($r_krs['periode_status'] === 'aktif') {
                    $sks_berjalan += (int)$r_krs['sks'];
                }
            }
        }
    }
}

$sks_lampau_lulus = max(0, $total_sks_lulus - $sks_berjalan);
$sisa_sks_ptik    = max(0, $target_sks_ptik - $total_sks_lulus);
$persen_kelulusan = round(($total_sks_lulus / $target_sks_ptik) * 100, 1);

/* =====================
   PROGRES KAS & KEHADIRAN
   ===================== */
$count_kas = 0;
$res_att_matkul = null;

if ($role === 'Mahasiswa' && !empty($mahasiswa_id)) {
    // Ambil Periode Aktif
    $q_periode = mysqli_query($koneksi, "SELECT id FROM periode WHERE status = 'aktif' LIMIT 1");
    $periode = mysqli_fetch_assoc($q_periode);
    $periode_id = $periode['id'] ?? 0;

    // 1. Kas
    $res_kas = mysqli_query($koneksi, "SELECT COUNT(*) as total FROM kas WHERE mahasiswa_id = '$mahasiswa_id' AND status = 'Lunas' AND periode_id = '$periode_id'");
    $count_kas = mysqli_fetch_assoc($res_kas)['total'] ?? 0;

    // 2. Attendance per Matkul Progress
    $q_att_matkul = "
        SELECT 
            mk.nama_matkul,
            COUNT(a.id) as hadir_count
        FROM krs k
        JOIN mata_kuliah mk ON k.matkul_id = mk.id
        LEFT JOIN absensi a ON (a.mahasiswa_id = k.mahasiswa_id AND a.matkul_id = k.matkul_id AND a.status = 'Hadir' AND a.periode_id = k.periode_id)
        WHERE k.mahasiswa_id = '$mahasiswa_id' AND k.periode_id = '$periode_id'
        GROUP BY mk.id
        ORDER BY mk.nama_matkul ASC
    ";
    $res_att_matkul = mysqli_query($koneksi, $q_att_matkul);
}


?>
<!DOCTYPE html>
<html lang="en">

<head>

    <title>Profile - SIMAWA PTIK 2024</title>
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

    <main class="ml-0 lg:ml-48 pt-24 px-4 md:px-6 pb-6 flex flex-col min-h-screen">
        <div class="bg-white dark:bg-slate-900 rounded-lg border border-slate-200 dark:border-slate-800 p-6 flex-grow w-full">
            <div class="flex justify-between items-center mb-6">
                <h1 class="text-2xl font-bold text-slate-800 dark:text-slate-100">User Profile</h1>
                <a href="../actions/edit_profile.php" id="edit-profile" class="px-4 py-2 bg-indigo-900 dark:bg-indigo-600 text-white rounded-lg hover:bg-indigo-800 dark:hover:bg-indigo-500 text-xs shadow-md transition-all">
                    Edit Profile
                </a>

            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <!-- Profile Picture Section -->
                <div class="md:col-span-1 flex flex-col items-center">
                    <img src="<?= profilePhotoUrl($data['nama_lengkap'] ?? $nama_lengkap, '../') ?>" alt="Profile Picture"
                        class="w-32 h-32 rounded-full mb-4 shadow-lg border-2 border-white dark:border-slate-800 object-cover">
                    <h2 class="text-xl font-semibold mb-2 text-slate-800 dark:text-white text-center"><?= htmlspecialchars($data['nama_lengkap'] ?? 'Pengguna') ?></h2>
                    <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold uppercase tracking-wider bg-indigo-50 dark:bg-indigo-950/50 text-indigo-700 dark:text-indigo-400 border border-indigo-200 dark:border-indigo-800 mb-2">
                        <?= htmlspecialchars($data['role'] ?? '') ?>
                    </span>
                    <?php if (!empty($data['linked_student_nim'])): ?>
                        <span class="inline-flex items-center gap-1 text-[11px] font-medium text-slate-500 dark:text-slate-400 bg-slate-100 dark:bg-slate-800 px-3 py-1 rounded-full border border-slate-200 dark:border-slate-700 mt-1">
                            <i class="fas fa-id-badge text-indigo-500"></i> Terhubung NIM: <strong class="text-slate-700 dark:text-slate-200"><?= htmlspecialchars($data['linked_student_nim']) ?></strong>
                        </span>
                    <?php endif; ?>
                </div>

                <!-- Profile Details Section -->
                <div class="md:col-span-2">
                    <!-- Replace the content with form wrapper -->
                    <form method="POST" class="space-y-4">
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Email</label>
                                <input type="email" name="email" value="<?= htmlspecialchars($data['email']) ?>"
                                    class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-800/50 border border-slate-200 dark:border-slate-700 text-slate-800 dark:text-white rounded-lg focus:outline-none focus:ring-2 focus:ring-slate-400 text-xs"
                                    disabled>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Password</label>
                                <input type="password" name="password" value="<?= htmlspecialchars($data['password']) ?>"
                                    class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-800/50 border border-slate-200 dark:border-slate-700 text-slate-800 dark:text-white rounded-lg focus:outline-none focus:ring-2 focus:ring-slate-400 text-xs"
                                    disabled>
                            </div>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Bio</label>
                            <input type="text" name="bio" value="<?= htmlspecialchars($data['bio'] ?? '') ?>"
                                class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-800/50 border border-slate-200 dark:border-slate-700 text-slate-800 dark:text-white rounded-lg focus:outline-none focus:ring-2 focus:ring-slate-400 text-xs"
                                disabled>
                        </div>
                    </form>

                    <!-- Theme Settings Section -->
                    <div class="mt-8 bg-slate-50 dark:bg-slate-800/50 p-5 rounded-2xl border border-slate-200 dark:border-slate-700 shadow-sm">
                        <h3 class="text-sm font-black text-slate-800 dark:text-slate-100 uppercase tracking-widest mb-4 flex items-center gap-2">
                            <i class="fa fa-palette text-indigo-500"></i>
                            Tema Aplikasi
                        </h3>
                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                            <label class="relative cursor-pointer group">
                                <input type="radio" name="theme-choice" value="light" class="peer sr-only" onchange="setTheme('light')">
                                <div class="p-3 border-2 border-slate-100 dark:border-slate-800 rounded-xl peer-checked:border-indigo-500 peer-checked:bg-indigo-50 dark:peer-checked:bg-indigo-900/30 transition-all hover:border-slate-300 dark:hover:border-slate-600">
                                    <div class="flex items-center gap-2">
                                        <i class="fa fa-sun text-yellow-500"></i>
                                        <span class="text-xs font-bold text-slate-700 dark:text-slate-300">Terang</span>
                                    </div>
                                </div>
                            </label>
                            <label class="relative cursor-pointer group">
                                <input type="radio" name="theme-choice" value="dark" class="peer sr-only" onchange="setTheme('dark')">
                                <div class="p-3 border-2 border-slate-100 dark:border-slate-800 rounded-xl peer-checked:border-indigo-500 peer-checked:bg-indigo-50 dark:peer-checked:bg-indigo-900/30 transition-all hover:border-slate-300 dark:hover:border-slate-600">
                                    <div class="flex items-center gap-2">
                                        <i class="fa fa-moon text-indigo-400"></i>
                                        <span class="text-xs font-bold text-slate-700 dark:text-slate-300">Gelap</span>
                                    </div>
                                </div>
                            </label>
                            <label class="relative cursor-pointer group">
                                <input type="radio" name="theme-choice" value="system" class="peer sr-only" onchange="setTheme('system')">
                                <div class="p-3 border-2 border-slate-100 dark:border-slate-800 rounded-xl peer-checked:border-indigo-500 peer-checked:bg-indigo-50 dark:peer-checked:bg-indigo-900/30 transition-all hover:border-slate-300 dark:hover:border-slate-600">
                                    <div class="flex items-center gap-2">
                                        <i class="fa fa-desktop text-slate-400"></i>
                                        <span class="text-xs font-bold text-slate-700 dark:text-slate-300">Otomatis</span>
                                    </div>
                                </div>
                            </label>
                        </div>
                    </div>

                    <?php if ($role === 'Mahasiswa' || !empty($mahasiswa_id)): ?>
                    <!-- Achievements & Progress Section (Khusus Mahasiswa) -->
                    <div class="mt-12 space-y-8">

                            <!-- WIDGET PROGRES KELULUSAN 146 SKS PTIK -->
                            <div class="bg-white dark:bg-slate-900 p-6 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-sm transition-all">
                                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 mb-5">
                                    <div>
                                        <div class="flex items-center gap-2">
                                            <div class="w-8 h-8 rounded-lg bg-indigo-50 dark:bg-indigo-950/50 flex items-center justify-center text-indigo-600 dark:text-indigo-400">
                                                <i class="fas fa-graduation-cap text-sm"></i>
                                            </div>
                                            <h3 class="text-sm font-black text-slate-800 dark:text-slate-100 uppercase tracking-wider">
                                                Progres Kelulusan SKS PTIK
                                            </h3>
                                        </div>
                                        <p class="text-[11px] text-slate-500 dark:text-slate-400 mt-1">
                                            Beban SKS kumulatif menuju gelar Sarjana Pendidikan (S.Pd) Prodi PTIK.
                                        </p>
                                    </div>
                                    <div class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-indigo-50 dark:bg-indigo-950/40 text-indigo-700 dark:text-indigo-300 rounded-xl text-xs font-extrabold border border-indigo-200 dark:border-indigo-800/80 shadow-xs self-start sm:self-auto">
                                        <i class="fas fa-bullseye text-[10px]"></i>
                                        Target: 146 SKS
                                    </div>
                                </div>

                                <!-- Progress Bar Besar -->
                                <div class="space-y-2 mb-6">
                                    <div class="flex justify-between items-baseline text-xs">
                                        <span class="font-bold text-slate-700 dark:text-slate-300 flex items-center gap-1.5">
                                            <span>Pencapaian:</span>
                                            <strong class="text-base text-indigo-600 dark:text-indigo-400"><?= $total_sks_lulus ?></strong>
                                            <span class="text-slate-400 dark:text-slate-500">/ 146 SKS</span>
                                        </span>
                                        <span class="font-black text-sm text-indigo-600 dark:text-indigo-400"><?= $persen_kelulusan ?>%</span>
                                    </div>
                                    <div class="w-full bg-slate-100 dark:bg-slate-800 h-4 rounded-full overflow-hidden shadow-inner p-0.5">
                                        <div class="h-full bg-gradient-to-r from-indigo-500 via-purple-500 to-emerald-500 rounded-full transition-all duration-1000 shadow-sm" style="width: <?= min(100, $persen_kelulusan) ?>%"></div>
                                    </div>
                                </div>

                                <!-- 3 Stat Cards Mini -->
                                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 mb-6">
                                    <div class="p-3.5 bg-emerald-50/60 dark:bg-emerald-950/20 border border-emerald-100 dark:border-emerald-900/40 rounded-xl flex items-center gap-3">
                                        <div class="w-9 h-9 rounded-lg bg-emerald-100 dark:bg-emerald-900/50 flex items-center justify-center text-emerald-700 dark:text-emerald-400 shrink-0">
                                            <i class="fas fa-check-circle text-sm"></i>
                                        </div>
                                        <div>
                                            <div class="text-[10px] font-bold text-emerald-800 dark:text-emerald-300 uppercase tracking-wider">SKS Lampau (Lulus)</div>
                                            <div class="text-base font-extrabold text-slate-800 dark:text-white"><?= $sks_lampau_lulus ?> SKS</div>
                                        </div>
                                    </div>
                                    <div class="p-3.5 bg-blue-50/60 dark:bg-blue-950/20 border border-blue-100 dark:border-blue-900/40 rounded-xl flex items-center gap-3">
                                        <div class="w-9 h-9 rounded-lg bg-blue-100 dark:bg-blue-900/50 flex items-center justify-center text-blue-700 dark:text-blue-400 shrink-0">
                                            <i class="fas fa-spinner text-sm"></i>
                                        </div>
                                        <div>
                                            <div class="text-[10px] font-bold text-blue-800 dark:text-blue-300 uppercase tracking-wider">SKS Semester Aktif</div>
                                            <div class="text-base font-extrabold text-slate-800 dark:text-white"><?= $sks_berjalan ?> SKS</div>
                                        </div>
                                    </div>
                                    <div class="p-3.5 bg-amber-50/60 dark:bg-amber-950/20 border border-amber-100 dark:border-amber-900/40 rounded-xl flex items-center gap-3">
                                        <div class="w-9 h-9 rounded-lg bg-amber-100 dark:bg-amber-900/50 flex items-center justify-center text-amber-700 dark:text-amber-400 shrink-0">
                                            <i class="fas fa-hourglass-half text-sm"></i>
                                        </div>
                                        <div>
                                            <div class="text-[10px] font-bold text-amber-800 dark:text-amber-300 uppercase tracking-wider">Sisa Beban SKS</div>
                                            <div class="text-base font-extrabold text-slate-800 dark:text-white"><?= $sisa_sks_ptik ?> SKS</div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Riwayat SKS Per Semester (Accordion) -->
                                <div class="border-t border-slate-100 dark:border-slate-800 pt-4">
                                    <h4 class="text-xs font-bold text-slate-700 dark:text-slate-300 mb-3 flex items-center justify-between">
                                        <span>Rincian SKS Per Semester</span>
                                        <span class="text-[10px] font-normal text-slate-400"><?= count($sks_per_semester) ?> Semester Tercatat</span>
                                    </h4>

                                    <?php if (empty($sks_per_semester)): ?>
                                        <div class="py-6 text-center text-xs text-slate-400 dark:text-slate-500 italic bg-slate-50 dark:bg-slate-800/40 rounded-xl border border-dashed border-slate-200 dark:border-slate-800">
                                            Belum ada mata kuliah yang tercatat di KRS. Hubungi Ketua Tingkat untuk inisialisasi KRS semester lampau.
                                        </div>
                                    <?php else: ?>
                                        <div class="space-y-2.5">
                                            <?php foreach ($sks_per_semester as $sem_data): 
                                                $is_aktif = ($sem_data['periode_status'] === 'aktif');
                                            ?>
                                                <details class="group bg-slate-50 dark:bg-slate-800/40 rounded-xl border border-slate-200 dark:border-slate-800 overflow-hidden">
                                                    <summary class="flex items-center justify-between p-3.5 cursor-pointer list-none select-none hover:bg-slate-100 dark:hover:bg-slate-800/80 transition-colors">
                                                        <div class="flex items-center gap-3">
                                                            <div class="w-7 h-7 rounded-lg <?= $is_aktif ? 'bg-blue-100 text-blue-700 dark:bg-blue-900/50 dark:text-blue-300' : 'bg-slate-200 text-slate-700 dark:bg-slate-700 dark:text-slate-300' ?> flex items-center justify-center font-bold text-xs shrink-0">
                                                                <?= $sem_data['semester_ke'] ?>
                                                            </div>
                                                            <div>
                                                                <div class="text-xs font-bold text-slate-800 dark:text-slate-100">
                                                                    Semester <?= $sem_data['semester_ke'] ?> (<?= htmlspecialchars($sem_data['tahun_periode'] . ' ' . $sem_data['semester_nama']) ?>)
                                                                </div>
                                                                <div class="text-[10px] text-slate-400 dark:text-slate-500">
                                                                    <?= $sem_data['total_matkul'] ?> Mata Kuliah &bull; <?= $sem_data['total_sks'] ?> SKS
                                                                </div>
                                                            </div>
                                                        </div>
                                                        <div class="flex items-center gap-2">
                                                            <a href="../actions/edit_krs.php?periode_id=<?= $sem_data['periode_id'] ?>" onclick="event.stopPropagation()" class="px-2 py-0.5 rounded-lg text-[10px] font-bold bg-indigo-50 dark:bg-indigo-950/40 text-indigo-600 dark:text-indigo-400 border border-indigo-200 dark:border-indigo-900/50 hover:bg-indigo-600 hover:text-white transition-all flex items-center gap-1 shadow-2xs" title="Sesuaikan Mata Kuliah Semester Ini">
                                                                <i class="fas fa-edit text-[9px]"></i> Sesuaikan
                                                            </a>
                                                            <?php if ($is_aktif): ?>
                                                                <span class="px-2 py-0.5 rounded-full text-[9px] font-bold bg-blue-100 text-blue-700 dark:bg-blue-900/40 dark:text-blue-300 border border-blue-200 dark:border-blue-800">
                                                                    Semester Aktif
                                                                </span>
                                                            <?php else: ?>
                                                                <span class="px-2 py-0.5 rounded-full text-[9px] font-bold bg-emerald-100 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800">
                                                                    Tuntas / Lulus
                                                                </span>
                                                            <?php endif; ?>
                                                            <i class="fas fa-chevron-down text-slate-400 text-xs transition-transform group-open:rotate-180"></i>
                                                        </div>
                                                    </summary>
                                                    <div class="p-3.5 border-t border-slate-200 dark:border-slate-800/80 bg-white dark:bg-slate-900/60">
                                                        <table class="w-full text-left text-xs">
                                                            <thead>
                                                                <tr class="text-[10px] uppercase font-bold text-slate-400 dark:text-slate-500 border-b border-slate-100 dark:border-slate-800">
                                                                    <th class="pb-1.5">Kode</th>
                                                                    <th class="pb-1.5">Mata Kuliah</th>
                                                                    <th class="pb-1.5 text-right">SKS</th>
                                                                </tr>
                                                            </thead>
                                                            <tbody class="divide-y divide-slate-100 dark:divide-slate-800/50">
                                                                <?php foreach ($sem_data['matkul'] as $mk_item): ?>
                                                                    <tr class="text-slate-700 dark:text-slate-300">
                                                                        <td class="py-1.5 font-mono text-[11px] text-slate-400"><?= htmlspecialchars($mk_item['kode_matkul']) ?></td>
                                                                        <td class="py-1.5 font-medium"><?= htmlspecialchars($mk_item['nama_matkul']) ?></td>
                                                                        <td class="py-1.5 font-bold text-indigo-600 dark:text-indigo-400 text-right"><?= $mk_item['sks'] ?></td>
                                                                    </tr>
                                                                <?php endforeach; ?>
                                                            </tbody>
                                                        </table>
                                                    </div>
                                                </details>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <!-- KAS Progress -->
                            <div class="bg-white dark:bg-slate-900 p-5 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-sm">
                                <div class="flex justify-between items-center mb-4">
                                    <h3 class="text-sm font-black text-slate-800 dark:text-slate-100 uppercase tracking-widest flex items-center gap-2">
                                        <i class="fa fa-wallet text-emerald-500"></i>
                                        Progres Kas
                                    </h3>
                                    <span class="text-xs font-bold text-emerald-600 bg-emerald-50 dark:bg-emerald-900/20 px-2 py-0.5 rounded"><?= $count_kas ?> / 16 Minggu</span>
                                </div>
                                <div class="w-full bg-slate-100 dark:bg-slate-800 h-3 rounded-full overflow-hidden shadow-inner flex">
                                    <div class="h-full bg-gradient-to-r from-emerald-400 to-emerald-600 transition-all duration-1000" style="width: <?= min(100, ($count_kas / 16) * 100) ?>%"></div>
                                </div>
                                <p class="text-[10px] text-slate-400 dark:text-slate-500 mt-2 italic">*Target lunas 16 minggu pembayaran kas dalam satu semester.</p>
                            </div>

                            <!-- Attendance Progress -->
                            <div class="bg-white dark:bg-slate-900 p-5 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-sm">
                                <h3 class="text-sm font-black text-slate-800 dark:text-slate-100 uppercase tracking-widest mb-4 flex items-center gap-2">
                                    <i class="fa fa-user-check text-blue-500"></i>
                                    Kehadiran Kuliah
                                </h3>
                                <div class="space-y-4 max-h-60 overflow-y-auto pr-2 custom-scrollbar">
                                    <?php if ($res_att_matkul && mysqli_num_rows($res_att_matkul) > 0): ?>
                                        <?php while ($row = mysqli_fetch_assoc($res_att_matkul)):
                                            $att_percent = min(100, ($row['hadir_count'] / 16) * 100);
                                        ?>
                                            <div>
                                                <div class="flex justify-between text-[10px] font-bold mb-1">
                                                    <span class="text-slate-600 dark:text-slate-400 truncate"><?= htmlspecialchars($row['nama_matkul']) ?></span>
                                                    <span class="text-blue-600 dark:text-blue-400"><?= $row['hadir_count'] ?>/16</span>
                                                </div>
                                                <div class="w-full bg-slate-100 dark:bg-slate-800 h-1.5 rounded-full overflow-hidden">
                                                    <div class="h-full bg-blue-500 transition-all duration-1000" style="width: <?= $att_percent ?>%"></div>
                                                </div>
                                            </div>
                                        <?php endwhile; ?>
                                    <?php else: ?>
                                        <p class="text-xs text-slate-400 dark:text-slate-500 italic py-2">Belum ada data kehadiran per mata kuliah.</p>
                                    <?php endif; ?>
                                </div>
                            </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php include '../includes/footer_db.php'; ?>
    </main>
    <?php include '../includes/script_db.php'; ?>
    <script>
        function setTheme(theme) {
            localStorage.setItem('theme', theme);
            const isDark = theme === 'dark' || (theme === 'system' && window.matchMedia('(prefers-color-scheme: dark)').matches);
            
            if (isDark) {
                document.documentElement.classList.add('dark');
            } else {
                document.documentElement.classList.remove('dark');
            }
        }

        // Set radio button initial state
        document.addEventListener('DOMContentLoaded', () => {
            const currentTheme = localStorage.getItem('theme') || 'light';
            const radio = document.querySelector(`input[name="theme-choice"][value="${currentTheme}"]`);
            if (radio) radio.checked = true;
        });

        // Listen for system theme changes if set to system
        window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', e => {
            if (localStorage.getItem('theme') === 'system' || !localStorage.getItem('theme')) {
                if (e.matches) {
                    document.documentElement.classList.add('dark');
                } else {
                    document.documentElement.classList.remove('dark');
                }
            }
        });
    </script>
</body>

</html>