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
    m.nama_panggilan,
    m.instagram,
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
   PROGRES KAS & KEHADIRAN (KHUSUS ROLE MAHASISWA)
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

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Instagram</label>
                                <input type="text" name="instagram" value="<?= htmlspecialchars($data['instagram'] ?? '') ?>"
                                    class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-800/50 border border-slate-200 dark:border-slate-700 text-slate-800 dark:text-white rounded-lg focus:outline-none focus:ring-2 focus:ring-slate-400 text-xs"
                                    disabled>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Bio</label>
                                <input type="text" name="bio" value="<?= htmlspecialchars($data['bio'] ?? '') ?>"
                                    class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-800/50 border border-slate-200 dark:border-slate-700 text-slate-800 dark:text-white rounded-lg focus:outline-none focus:ring-2 focus:ring-slate-400 text-xs"
                                    disabled>
                            </div>
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

                    <?php if ($role === 'Mahasiswa'): ?>
                    <!-- Achievements & Progress Section (Khusus Mahasiswa) -->
                    <div class="mt-12 space-y-8">
                        
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