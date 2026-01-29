<?php
session_start();
include '../koneksi.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../../index.php");
    exit;
}

$nama_lengkap = $_SESSION['nama_lengkap'];
$user_id = $_SESSION['user_id'];
$foto = $_SESSION['foto'];
$role = $_SESSION['role'];

// Include foto helpers for cache busting
include_once '../functions/foto_helpers.php';

$query = "SELECT id FROM mahasiswa WHERE user_id = ?";
$stmt  = mysqli_prepare($koneksi, $query);
mysqli_stmt_bind_param($stmt, "i", $_SESSION['user_id']);
mysqli_stmt_execute($stmt);
$mahasiswa = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

$mahasiswa_id = $mahasiswa['id'];


// ambil data user
$query = "
SELECT 
    u.id AS user_id,
    u.role,
    u.password,
    m.nim,
    m.nama_lengkap,
    m.nama_panggilan,
    u.email,
    m.foto,
    m.instagram,
    m.bio
FROM users u
JOIN mahasiswa m ON m.user_id = u.id
WHERE u.id = ?
LIMIT 1
";

$stmt = mysqli_prepare($koneksi, $query);
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$data = mysqli_fetch_assoc($result);



$query_krs = "
SELECT 
    mk.kode_matkul,
    mk.nama_matkul,
    mk.sks,
    mk.semester
FROM krs k
JOIN mahasiswa m ON k.mahasiswa_id = m.id
JOIN mata_kuliah mk ON k.matkul_id = mk.id
WHERE m.user_id = ?
";


$stmt_krs = mysqli_prepare($koneksi, $query_krs);
mysqli_stmt_bind_param($stmt_krs, "i", $user_id);
mysqli_stmt_execute($stmt_krs);
$result_krs = mysqli_stmt_get_result($stmt_krs);

/* =====================
   AMBIL PERIODE AKTIF
   ===================== */
$q_periode = mysqli_query($koneksi, "SELECT id FROM periode WHERE status = 'aktif' LIMIT 1");
$periode = mysqli_fetch_assoc($q_periode);
$periode_id = $periode['id'] ?? 0;

/* =====================
   HITUNG PROGRES & MISI
   ===================== */

// 1. Kas
$res_kas = mysqli_query($koneksi, "SELECT COUNT(*) as total FROM kas WHERE mahasiswa_id = '$mahasiswa_id' AND status = 'Lunas' AND periode_id = '$periode_id'");
$count_kas = mysqli_fetch_assoc($res_kas)['total'] ?? 0;

// 2. Kuis
$res_kuis = mysqli_query($koneksi, "SELECT current_streak, total_games FROM kuis_harian_stats WHERE user_id = '$user_id'");
$kuis_stats = mysqli_fetch_assoc($res_kuis);
$kuis_streak = $kuis_stats['current_streak'] ?? 0;
$kuis_total = $kuis_stats['total_games'] ?? 0;

// 3. Absensi
$res_absensi_total = mysqli_query($koneksi, "SELECT COUNT(*) as total FROM absensi WHERE mahasiswa_id = '$mahasiswa_id' AND status = 'Hadir' AND periode_id = '$periode_id'");
$count_absensi = mysqli_fetch_assoc($res_absensi_total)['total'] ?? 0;

// 4. Profil & Lainnya
$profil_lengkap = (!empty($data['foto']) && $data['foto'] !== 'Yaya.png' && !empty($data['instagram']) && !empty($data['bio'])) ? 1 : 0;
$res_krs_count = mysqli_query($koneksi, "SELECT COUNT(*) as total FROM krs WHERE mahasiswa_id = '$mahasiswa_id' AND periode_id = '$periode_id'");
$count_krs = mysqli_fetch_assoc($res_krs_count)['total'] ?? 0;

$q_nim = mysqli_query($koneksi, "SELECT nim FROM mahasiswa WHERE id = '$mahasiswa_id'");
$nim_mhs = mysqli_fetch_assoc($q_nim)['nim'] ?? '';
$res_kelompok = mysqli_query($koneksi, "SELECT COUNT(*) as total FROM kelompok WHERE FIND_IN_SET('$nim_mhs', REPLACE(anggota, ' ', '')) OR anggota LIKE '%$nim_mhs%'");
$count_kelompok = mysqli_fetch_assoc($res_kelompok)['total'] ?? 0;

// Definisi Misi (untuk Badge)
$missions = [
    ['icon' => 'fa-wallet', 'color' => 'emerald', 'levels' => [['name' => 'Bronze', 'target' => 5, 'current' => $count_kas], ['name' => 'Silver', 'target' => 10, 'current' => $count_kas], ['name' => 'Gold', 'target' => 16, 'current' => $count_kas]]],
    ['icon' => 'fa-user-check', 'color' => 'blue', 'levels' => [['name' => 'Hadir 5x', 'target' => 5, 'current' => $count_absensi], ['name' => 'Hadir 15x', 'target' => 15, 'current' => $count_absensi], ['name' => 'Hadir 30x', 'target' => 30, 'current' => $count_absensi]]],
    ['icon' => 'fa-fire', 'color' => 'orange', 'levels' => [['name' => 'Starter', 'target' => 3, 'current' => $kuis_streak], ['name' => 'Veteran', 'target' => 14, 'current' => $kuis_streak], ['name' => 'Gamer', 'target' => 50, 'current' => $kuis_total]]],
    ['icon' => 'fa-users', 'color' => 'purple', 'levels' => [['name' => 'KRS', 'target' => 1, 'current' => ($count_krs > 0 ? 1 : 0)], ['name' => 'Profil', 'target' => 1, 'current' => $profil_lengkap], ['name' => 'Kelompok', 'target' => 9, 'current' => $count_kelompok]]]
];

$color_filters = [
    'emerald' => 'brightness(0) saturate(100%) invert(65%) sepia(16%) saturate(3065%) hue-rotate(116deg) brightness(101%) contrast(86%)',
    'blue'    => 'brightness(0) saturate(100%) invert(51%) sepia(85%) saturate(2394%) hue-rotate(201deg) brightness(99%) contrast(94%)',
    'orange'  => 'brightness(0) saturate(100%) invert(62%) sepia(77%) saturate(3143%) hue-rotate(352deg) brightness(101%) contrast(96%)',
    'purple'  => 'brightness(0) saturate(100%) invert(43%) sepia(72%) saturate(3085%) hue-rotate(244deg) brightness(102%) contrast(95%)'
];

$badge_gradients = [
    'emerald' => 'from-emerald-400 to-emerald-600',
    'blue'    => 'from-blue-400 to-blue-600',
    'orange'  => 'from-orange-400 to-orange-600',
    'purple'  => 'from-purple-400 to-purple-600'
];

// --- Attendance per Matkul Progress ---
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


?>
<!DOCTYPE html>
<html lang="en">

<head>

    <title>Profile - PTIK C 2024</title>
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

    <main class="ml-0 md:ml-48 pt-20 p-6 flex flex-col min-h-screen">
        <div class="bg-white rounded-lg border border-slate-200 p-6 flex-grow w-full">
            <div class="flex justify-between items-center mb-6">
                <h1 class="text-2xl font-bold text-slate-800">User Profile</h1>
                <a href="../actions/edit_profile.php?<?= $data['user_id']; ?>" id="edit-profile" class="px-4 py-2 bg-indigo-900 text-white rounded-lg hover:bg-indigo-800 text-xs">
                    Edit Profile
                </a>

            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <!-- Profile Picture Section -->
                <div class="md:col-span-1 flex flex-col items-center">
                    <img src="<?= profilePhotoUrl($data['foto'], '../') ?>" alt="Profile Picture"
                        class="w-32 h-32 rounded-full mb-4">
                    <h2 class="text-xl font-semibold mb-2 text-slate-800"><?= htmlspecialchars($data['nama_lengkap']) ?></h2>
                    <p class="text-slate-600"><?= htmlspecialchars($data['role']) ?></p>
                </div>

                <!-- Profile Details Section -->
                <div class="md:col-span-2">
                    <!-- Replace the content with form wrapper -->
                    <form method="POST" class="space-y-4">
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-slate-700 mb-1">Email</label>
                                <input type="email" name="email" value="<?= htmlspecialchars($data['email']) ?>"
                                    class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-slate-400 text-xs"
                                    disabled>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-slate-700 mb-1">Password</label>
                                <input type="password" name="password" value="<?= htmlspecialchars($data['password']) ?>"
                                    class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-slate-400 text-xs"
                                    disabled>
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-slate-700 mb-1">Instagram</label>
                                <input type="text" name="instagram" value="<?= htmlspecialchars($data['instagram']) ?>"
                                    class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-slate-400 text-xs"
                                    disabled>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-slate-700 mb-1">Bio</label>
                                <input type="text" name="password" value="<?= htmlspecialchars($data['bio']) ?>"
                                    class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-slate-400 text-xs"
                                    disabled>
                            </div>
                        </div>

                        <!-- Bio Section -->




                        <!-- Submit Button -->

                    </form>

                    <!-- Achievements & Progress Section -->
                    <div class="mt-12 space-y-8">
                        

                       
                            <!-- KAS Progress -->
                            <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-sm">
                                <div class="flex justify-between items-center mb-4">
                                    <h3 class="text-sm font-black text-slate-800 uppercase tracking-widest flex items-center gap-2">
                                        <i class="fa fa-wallet text-emerald-500"></i>
                                        Progres Kas
                                    </h3>
                                    <span class="text-xs font-bold text-emerald-600 bg-emerald-50 px-2 py-0.5 rounded"><?= $count_kas ?> / 16 Minggu</span>
                                </div>
                                <div class="w-full bg-slate-100 h-3 rounded-full overflow-hidden shadow-inner flex">
                                    <div class="h-full bg-gradient-to-r from-emerald-400 to-emerald-600 transition-all duration-1000" style="width: <?= min(100, ($count_kas / 16) * 100) ?>%"></div>
                                </div>
                                <p class="text-[10px] text-slate-400 mt-2 italic">*Target lunas 16 minggu pembayaran kas dalam satu semester.</p>
                            </div>

                            <!-- Attendance Progress -->
                            <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-sm">
                                <h3 class="text-sm font-black text-slate-800 uppercase tracking-widest mb-4 flex items-center gap-2">
                                    <i class="fa fa-user-check text-blue-500"></i>
                                    Kehadiran Kuliah
                                </h3>
                                <div class="space-y-4 max-h-60 overflow-y-auto pr-2 custom-scrollbar">
                                    <?php while ($row = mysqli_fetch_assoc($res_att_matkul)):
                                        $att_percent = min(100, ($row['hadir_count'] / 16) * 100);
                                    ?>
                                        <div>
                                            <div class="flex justify-between text-[10px] font-bold mb-1">
                                                <span class="text-slate-600 truncate"><?= htmlspecialchars($row['nama_matkul']) ?></span>
                                                <span class="text-blue-600"><?= $row['hadir_count'] ?>/16</span>
                                            </div>
                                            <div class="w-full bg-slate-100 h-1.5 rounded-full overflow-hidden">
                                                <div class="h-full bg-blue-500 transition-all duration-1000" style="width: <?= $att_percent ?>%"></div>
                                            </div>
                                        </div>
                                    <?php endwhile; ?>
                                </div>
                            </div>

                            
                        
                        <!-- Badges Section -->
                        <div class="bg-indigo-950 p-6 rounded-2xl text-white shadow-xl relative overflow-hidden">
                            <div class="relative z-10">
                                <h3 class="text-lg font-black mb-4 flex items-center gap-2">
                                    <i class="fa fa-medal text-yellow-400"></i>
                                    Koleksi Lencana
                                </h3>
                                <div class="flex flex-wrap gap-4">
                                    <?php
                                    $any_badge = false;
                                    foreach ($missions as $cat):
                                        $bg_grad = $badge_gradients[$cat['color']] ?? 'from-slate-400 to-slate-600';
                                        foreach ($cat['levels'] as $l_idx => $level):
                                            if ($level['current'] >= $level['target']):
                                                $any_badge = true;
                                    ?>
                                                <div class="group relative flex flex-col items-center">
                                                    <div class="relative w-12 h-12 rounded-full bg-gradient-to-br <?= $bg_grad ?> p-[1px] shadow-lg transform group-hover:-translate-y-1 transition-transform">
                                                        <div class="w-full h-full rounded-full bg-slate-900 flex flex-col items-center justify-center relative overflow-hidden">
                                                            <i class="fa <?= $cat['icon'] ?> text-xs text-white"></i>
                                                            <div class="flex gap-0.5 mt-0.5">
                                                                <?php for ($s = 0; $s <= $l_idx; $s++): ?>
                                                                    <img src="../assets/image/logo.webp" class="w-2 h-2 object-contain" style="filter: <?= $color_filters[$cat['color']] ?>;">
                                                                <?php endfor; ?>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <span class="text-[9px] font-bold uppercase mt-1 text-white/70"><?= $level['name'] ?></span>
                                                </div>
                                        <?php
                                            endif;
                                        endforeach;
                                    endforeach;

                                    if (!$any_badge):
                                        ?>
                                        <div class="w-full text-center py-4 bg-white/5 rounded-xl border border-dashed border-white/20">
                                            <p class="text-[10px] text-indigo-300">Belum ada lencana yang terbuka.</p>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div class="absolute -right-10 -bottom-10 w-32 h-32 bg-indigo-500/20 rounded-full blur-2xl"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php include '../includes/footer_db.php'; ?>
    </main>
    <?php include '../includes/script_db.php'; ?>

</body>

</html>