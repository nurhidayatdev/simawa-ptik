<?php
session_start();
include 'koneksi.php';

// Proteksi Akses: Hanya Administrator Utama
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

if (($_SESSION['role'] ?? '') !== 'Administrator Utama') {
    include 'includes/alert_role.php';
    exit();
}

$user_id      = $_SESSION['user_id'];
$nama_lengkap = $_SESSION['nama_lengkap'] ?? 'Administrator Utama';
$foto         = $_SESSION['foto'] ?? '';
$role         = $_SESSION['role'];

require_once 'functions/maintenance.php';
require_once 'functions/helpers.php';
require_once 'functions/foto_helpers.php';

// Flash Messages
$success_msg = $_SESSION['success'] ?? '';
$error_msg   = $_SESSION['error'] ?? '';
unset($_SESSION['success'], $_SESSION['error']);

// 1. Data Statistik Lintas-Kelas
$q_kelas_stats = mysqli_query($koneksi, "
    SELECT 
        COUNT(*) as total_kelas,
        SUM(CASE WHEN status_aktif = 'aktif' THEN 1 ELSE 0 END) as kelas_aktif
    FROM kelas
");
$kelas_stats = mysqli_fetch_assoc($q_kelas_stats);
$total_kelas = (int)($kelas_stats['total_kelas'] ?? 0);
$kelas_aktif = (int)($kelas_stats['kelas_aktif'] ?? 0);

$q_total_mhs = mysqli_query($koneksi, "SELECT COUNT(*) as total FROM mahasiswa");
$total_mhs   = (int)(mysqli_fetch_assoc($q_total_mhs)['total'] ?? 0);

$q_total_keti = mysqli_query($koneksi, "SELECT COUNT(*) as total FROM users WHERE role = 'Ketua Tingkat'");
$total_keti   = (int)(mysqli_fetch_assoc($q_total_keti)['total'] ?? 0);

$is_maintenance  = is_maintenance_mode($koneksi);
$maintenance_msg = get_maintenance_message($koneksi);

// 2. Daftar Seluruh Kelas Terdaftar + Info Ketua & Anggota
$q_kelas_list = mysqli_query($koneksi, "
    SELECT 
        k.id,
        k.nama_kelas,
        k.angkatan,
        k.prodi,
        k.status_aktif,
        k.dibuat_pada,
        k.ketua_tingkat_user_id,
        u.email as ketua_email,
        m.nama_lengkap as ketua_nama,
        m.foto as ketua_foto,
        (SELECT COUNT(*) FROM mahasiswa m2 WHERE m2.kelas_id = k.id) as total_mahasiswa
    FROM kelas k
    LEFT JOIN users u ON k.ketua_tingkat_user_id = u.id
    LEFT JOIN mahasiswa m ON m.user_id = u.id
    ORDER BY k.status_aktif ASC, k.id ASC
");
$kelas_list = [];
while ($row = mysqli_fetch_assoc($q_kelas_list)) {
    $kelas_list[] = $row;
}

// 3. User untuk Dropdown Ketua Tingkat (Non-Admin)
$q_users_keti = mysqli_query($koneksi, "
    SELECT u.id, u.email, u.role, m.nama_lengkap, m.nim, k.nama_kelas
    FROM users u
    LEFT JOIN mahasiswa m ON m.user_id = u.id
    LEFT JOIN kelas k ON u.kelas_id = k.id
    WHERE u.role != 'Administrator Utama'
    ORDER BY m.nama_lengkap ASC, u.email ASC
");
$users_keti = [];
while ($row = mysqli_fetch_assoc($q_users_keti)) {
    $users_keti[] = $row;
}

$title = "Dashboard Administrator - SIMAWA PTIK 2024";
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <title><?= $title ?></title>
    <?php include 'includes/head.php'; ?>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        .custom-scrollbar::-webkit-scrollbar {
            width: 4px;
        }

        .custom-scrollbar::-webkit-scrollbar-thumb {
            background: #cbd5e1;
            border-radius: 10px;
        }

        .dark .custom-scrollbar::-webkit-scrollbar-thumb {
            background: #334155;
        }
    </style>
</head>

<body class="bg-slate-100 dark:bg-slate-950 text-slate-800 dark:text-slate-200 font-sans antialiased min-h-screen flex flex-col transition-colors duration-300">

    <!-- Navigasi Shell (Top Bar + Fixed Sidebar) -->
    <?php include 'includes/menu_db.php'; ?>

    <!-- Main Shell (Offset Margin Left ml-0 lg:ml-48, pt-20 untuk topbar) -->
    <main class="ml-0 lg:ml-48 pt-20 p-4 md:p-6 flex flex-col min-h-screen">
        <div class="max-w-7xl mx-auto w-full space-y-6 flex-grow">

            <!-- 1. Header & Action Bar -->
            <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 p-6 shadow-sm flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                <div>
                    <div class="flex items-center gap-2">
                        <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-[10px] font-extrabold uppercase tracking-widest bg-indigo-50 dark:bg-indigo-950/40 text-indigo-700 dark:text-indigo-400 border border-indigo-200/60 dark:border-indigo-900/50">
                            <i class="fas fa-crown text-[9px]"></i> Administrator Utama
                        </span>
                        <span class="text-xs font-semibold text-slate-400 dark:text-slate-500">• Pusat Kendali Multi-Kelas</span>
                    </div>
                    <h1 class="text-2xl md:text-3xl font-extrabold text-slate-900 dark:text-white mt-1.5 tracking-tight">Dashboard Administrator</h1>
                    <p class="text-xs md:text-sm text-slate-500 dark:text-slate-400 mt-1">Kelola pembagian kelas, penunjukan Ketua Tingkat, dan pengawasan operasional lintas angkatan.</p>
                </div>
                <div class="flex flex-wrap items-center gap-2.5">
                    <button type="button" onclick="openTambahKelasModal()" class="inline-flex items-center gap-2 px-4 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-bold rounded-xl shadow-lg shadow-indigo-600/20 active:scale-95 transition-all cursor-pointer">
                        <i class="fas fa-plus-circle"></i> Tambah Kelas Baru
                    </button>
                    <a href="database/db_settings" class="inline-flex items-center gap-2 px-4 py-2.5 bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 text-xs font-bold rounded-xl border border-slate-200 dark:border-slate-700 transition-all">
                        <i class="fas fa-sliders-h"></i> Pengaturan Global
                    </a>
                </div>
            </div>

            <!-- 2. Metrics & Ringkasan Lintas-Kelas (Grid 4 Kolom) -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 md:gap-5">
                <!-- Card 1: Total Kelas -->
                <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 p-5 shadow-sm hover:shadow-md transition-all group">
                    <div class="flex items-center justify-between">
                        <div>
                            <span class="text-[10px] font-bold uppercase tracking-widest text-slate-400 dark:text-slate-500">Kelas Terdaftar</span>
                            <div class="text-2xl md:text-3xl font-black text-slate-800 dark:text-white mt-1.5"><?= $total_kelas ?></div>
                            <div class="text-[11px] font-semibold text-emerald-600 dark:text-emerald-400 mt-1 flex items-center gap-1">
                                <i class="fas fa-check-circle text-[10px]"></i> <?= $kelas_aktif ?> Kelas Aktif
                            </div>
                        </div>
                        <div class="w-12 h-12 rounded-2xl bg-indigo-50 dark:bg-indigo-950/40 text-indigo-600 dark:text-indigo-400 border border-indigo-100 dark:border-indigo-900/50 flex items-center justify-center text-xl group-hover:scale-110 transition-transform">
                            <i class="fas fa-school"></i>
                        </div>
                    </div>
                </div>

                <!-- Card 2: Total Mahasiswa -->
                <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 p-5 shadow-sm hover:shadow-md transition-all group">
                    <div class="flex items-center justify-between">
                        <div>
                            <span class="text-[10px] font-bold uppercase tracking-widest text-slate-400 dark:text-slate-500">Total Mahasiswa</span>
                            <div class="text-2xl md:text-3xl font-black text-slate-800 dark:text-white mt-1.5"><?= $total_mhs ?></div>
                            <div class="text-[11px] font-semibold text-slate-500 dark:text-slate-400 mt-1">
                                Akumulasi Seluruh Kelas
                            </div>
                        </div>
                        <div class="w-12 h-12 rounded-2xl bg-blue-50 dark:bg-blue-950/40 text-blue-600 dark:text-blue-400 border border-blue-100 dark:border-blue-900/50 flex items-center justify-center text-xl group-hover:scale-110 transition-transform">
                            <i class="fas fa-user-graduate"></i>
                        </div>
                    </div>
                </div>

                <!-- Card 3: Ketua Tingkat -->
                <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 p-5 shadow-sm hover:shadow-md transition-all group">
                    <div class="flex items-center justify-between">
                        <div>
                            <span class="text-[10px] font-bold uppercase tracking-widest text-slate-400 dark:text-slate-500">Ketua Tingkat</span>
                            <div class="text-2xl md:text-3xl font-black text-slate-800 dark:text-white mt-1.5"><?= $total_keti ?></div>
                            <div class="text-[11px] font-semibold text-indigo-600 dark:text-indigo-400 mt-1">
                                Akun Penanggung Jawab
                            </div>
                        </div>
                        <div class="w-12 h-12 rounded-2xl bg-amber-50 dark:bg-amber-950/40 text-amber-600 dark:text-amber-400 border border-amber-100 dark:border-amber-900/50 flex items-center justify-center text-xl group-hover:scale-110 transition-transform">
                            <i class="fas fa-user-shield"></i>
                        </div>
                    </div>
                </div>

                <!-- Card 4: Status Sistem Global -->
                <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 p-5 shadow-sm hover:shadow-md transition-all group">
                    <div class="flex items-center justify-between">
                        <div>
                            <span class="text-[10px] font-bold uppercase tracking-widest text-slate-400 dark:text-slate-500">Status Sistem</span>
                            <div class="mt-2">
                                <?php if ($is_maintenance): ?>
                                    <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-black uppercase tracking-wider bg-rose-100 dark:bg-rose-950/60 text-rose-700 dark:text-rose-400 border border-rose-300 dark:border-rose-800">
                                        <span class="w-2 h-2 rounded-full bg-rose-500 animate-pulse"></span> Maintenance
                                    </span>
                                <?php else: ?>
                                    <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-black uppercase tracking-wider bg-emerald-100 dark:bg-emerald-950/60 text-emerald-700 dark:text-emerald-400 border border-emerald-300 dark:border-emerald-800">
                                        <span class="w-2 h-2 rounded-full bg-emerald-500"></span> Operasional
                                    </span>
                                <?php endif; ?>
                            </div>
                            <div class="text-[11px] text-slate-400 dark:text-slate-500 mt-1.5">
                                <?= $is_maintenance ? 'User non-admin diblokir' : 'Semua akses normal' ?>
                            </div>
                        </div>
                        <div class="w-12 h-12 rounded-2xl <?= $is_maintenance ? 'bg-rose-50 dark:bg-rose-950/40 text-rose-600 dark:text-rose-400 border-rose-100 dark:border-rose-900/50' : 'bg-emerald-50 dark:bg-emerald-950/40 text-emerald-600 dark:text-emerald-400 border-emerald-100 dark:border-emerald-900/50' ?> border flex items-center justify-center text-xl group-hover:scale-110 transition-transform">
                            <i class="fas fa-server"></i>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 3. Section Utama 1: Manajemen Multi-Kelas & Ketua Tingkat -->
            <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-sm overflow-hidden">
                <div class="p-5 border-b border-slate-100 dark:border-slate-800 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 bg-slate-50/50 dark:bg-slate-900/50">
                    <div>
                        <h3 class="text-base font-extrabold text-slate-800 dark:text-white flex items-center gap-2">
                            <i class="fas fa-chalkboard-user text-indigo-600 dark:text-indigo-400"></i>
                            Daftar Kelas & Ketua Tingkat Terdaftar
                        </h3>
                        <p class="text-xs text-slate-400 dark:text-slate-500 mt-0.5">Kelola penetapan kelas, data ketua tingkat, serta alokasi mahasiswa per kelas.</p>
                    </div>
                    <span class="px-3 py-1 rounded-full text-xs font-bold bg-indigo-50 dark:bg-indigo-950/50 text-indigo-700 dark:text-indigo-400 border border-indigo-200 dark:border-indigo-900/50 w-fit">
                        Total <?= count($kelas_list) ?> Kelas
                    </span>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-indigo-900 dark:bg-slate-800 text-white border-b border-indigo-950 dark:border-slate-700 text-[10px] md:text-xs font-bold uppercase tracking-wider">
                                <th class="px-5 py-3.5 w-12 text-center">No</th>
                                <th class="px-5 py-3.5">Nama Kelas & Program Studi</th>
                                <th class="px-5 py-3.5">Ketua Tingkat</th>
                                <th class="px-5 py-3.5 text-center">Anggota</th>
                                <th class="px-5 py-3.5 text-center">Status</th>
                                <th class="px-5 py-3.5 text-center w-52">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-800/80 text-xs">
                            <?php if (empty($kelas_list)): ?>
                                <tr>
                                    <td colspan="6" class="px-6 py-12 text-center text-slate-400 dark:text-slate-500">
                                        <i class="fas fa-folder-open text-3xl mb-2 block opacity-40"></i>
                                        Belum ada kelas terdaftar di sistem.
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php $no = 1;
                                foreach ($kelas_list as $k): 
                                    $is_active = ($k['status_aktif'] === 'aktif');
                                ?>
                                    <tr class="hover:bg-slate-50/80 dark:hover:bg-slate-800/40 transition-colors">
                                        <td class="px-5 py-4 text-center font-bold text-slate-400"><?= $no++ ?></td>
                                        
                                        <!-- Nama Kelas & Prodi -->
                                        <td class="px-5 py-4">
                                            <div class="flex items-center gap-3">
                                                <div class="w-10 h-10 rounded-xl bg-indigo-100/70 dark:bg-indigo-950/60 text-indigo-600 dark:text-indigo-400 border border-indigo-200 dark:border-indigo-900/50 flex items-center justify-center font-black text-sm shrink-0">
                                                    <?= htmlspecialchars(substr($k['nama_kelas'], 0, 2)) ?>
                                                </div>
                                                <div>
                                                    <div class="font-extrabold text-slate-900 dark:text-white text-sm">
                                                        <?= htmlspecialchars($k['nama_kelas']) ?>
                                                    </div>
                                                    <div class="text-[11px] text-slate-500 dark:text-slate-400 mt-0.5">
                                                        Angkatan <?= htmlspecialchars($k['angkatan']) ?> • <?= htmlspecialchars($k['prodi']) ?>
                                                    </div>
                                                </div>
                                            </div>
                                        </td>

                                        <!-- Ketua Tingkat -->
                                        <td class="px-5 py-4">
                                            <?php if (!empty($k['ketua_nama'])): ?>
                                                <div class="flex items-center gap-2.5">
                                                    <div class="w-8 h-8 rounded-full overflow-hidden border border-slate-200 dark:border-slate-700 bg-slate-100 dark:bg-slate-800 shrink-0">
                                                        <?php if (!empty($k['ketua_foto'])): ?>
                                                            <img src="<?= profilePhotoUrl($k['ketua_foto'], '') ?>" class="w-full h-full object-cover">
                                                        <?php else: ?>
                                                            <div class="w-full h-full flex items-center justify-center text-slate-400 text-xs">
                                                                <i class="fas fa-user"></i>
                                                            </div>
                                                        <?php endif; ?>
                                                    </div>
                                                    <div>
                                                        <div class="font-bold text-slate-800 dark:text-slate-200 leading-tight">
                                                            <?= htmlspecialchars($k['ketua_nama']) ?>
                                                        </div>
                                                        <div class="text-[10px] text-slate-400 dark:text-slate-500">
                                                            <?= htmlspecialchars($k['ketua_email'] ?? '-') ?>
                                                        </div>
                                                    </div>
                                                </div>
                                            <?php else: ?>
                                                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-[10px] font-bold bg-slate-100 dark:bg-slate-800 text-slate-500 dark:text-slate-400 border border-slate-200 dark:border-slate-700">
                                                    <i class="fas fa-exclamation-circle text-amber-500"></i> Belum Ditunjuk
                                                </span>
                                            <?php endif; ?>
                                        </td>

                                        <!-- Jumlah Mahasiswa -->
                                        <td class="px-5 py-4 text-center">
                                            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 border border-slate-200 dark:border-slate-700">
                                                <i class="fas fa-users text-[10px] text-slate-400"></i>
                                                <?= (int)$k['total_mahasiswa'] ?> Mahasiswa
                                            </span>
                                        </td>

                                        <!-- Status Kelas -->
                                        <td class="px-5 py-4 text-center">
                                            <?php if ($is_active): ?>
                                                <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[10px] font-extrabold uppercase tracking-wider bg-emerald-100 dark:bg-emerald-950/50 text-emerald-700 dark:text-emerald-400 border border-emerald-200 dark:border-emerald-800">
                                                    <i class="fas fa-circle text-[6px]"></i> Aktif
                                                </span>
                                            <?php else: ?>
                                                <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[10px] font-extrabold uppercase tracking-wider bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400 border border-slate-300 dark:border-slate-700">
                                                    <i class="fas fa-circle text-[6px]"></i> Non-Aktif
                                                </span>
                                            <?php endif; ?>
                                        </td>

                                        <!-- Aksi -->
                                        <td class="px-5 py-4 text-center">
                                            <div class="flex items-center justify-center gap-1.5">
                                                <!-- Scope View: Meninjau Operasional Kelas Ini -->
                                                <a href="views/manajemen_absen.php?filter_kelas=<?= $k['id'] ?>" title="Tinjau Data Kelas Ini" class="inline-flex items-center justify-center w-8 h-8 rounded-lg bg-indigo-50 hover:bg-indigo-600 dark:bg-indigo-950/40 dark:hover:bg-indigo-600 text-indigo-600 hover:text-white dark:text-indigo-400 dark:hover:text-white border border-indigo-200 dark:border-indigo-900/50 transition-colors">
                                                    <i class="fas fa-eye text-xs"></i>
                                                </a>

                                                <!-- Edit Kelas Modal Trigger -->
                                                <button type="button" onclick='openEditKelasModal(<?= json_encode($k) ?>)' title="Edit Kelas & Ketua" class="inline-flex items-center justify-center w-8 h-8 rounded-lg bg-amber-50 hover:bg-amber-600 dark:bg-amber-950/40 dark:hover:bg-amber-600 text-amber-600 hover:text-white dark:text-amber-400 dark:hover:text-white border border-amber-200 dark:border-amber-900/50 transition-colors cursor-pointer">
                                                    <i class="fas fa-pencil text-xs"></i>
                                                </button>

                                                <!-- Toggle Aktif/Non-aktif -->
                                                <form action="actions/tambah_kelas.php" method="POST" class="inline" onsubmit="return confirm('Apakah Anda yakin ingin mengubah status kelas ini?')">
                                                    <input type="hidden" name="action" value="toggle_status_kelas">
                                                    <input type="hidden" name="kelas_id" value="<?= $k['id'] ?>">
                                                    <button type="submit" title="<?= $is_active ? 'Nonaktifkan Kelas' : 'Aktifkan Kelas' ?>" class="inline-flex items-center justify-center w-8 h-8 rounded-lg <?= $is_active ? 'bg-rose-50 hover:bg-rose-600 dark:bg-rose-950/40 text-rose-600 hover:text-white border-rose-200 dark:border-rose-900/50' : 'bg-emerald-50 hover:bg-emerald-600 dark:bg-emerald-950/40 text-emerald-600 hover:text-white border-emerald-200 dark:border-emerald-900/50' ?> border transition-colors cursor-pointer">
                                                        <i class="fas <?= $is_active ? 'fa-ban' : 'fa-check' ?> text-xs"></i>
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- 4. Section Utama 2: Kontrol Sistem & Master Lintas-Kelas (Grid 2 Kolom) -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

                <!-- Kolom Kiri: Quick Maintenance Toggle -->
                <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 p-6 shadow-sm flex flex-col justify-between">
                    <div>
                        <div class="flex items-center justify-between mb-4">
                            <h3 class="text-base font-extrabold text-slate-800 dark:text-white flex items-center gap-2">
                                <i class="fas fa-tools text-amber-500"></i> Kontrol Maintenance Mode
                            </h3>
                            <span class="text-[10px] font-extrabold uppercase px-2.5 py-1 rounded-full <?= $is_maintenance ? 'bg-rose-100 text-rose-800 dark:bg-rose-950/50 dark:text-rose-400 border border-rose-200 dark:border-rose-800' : 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950/50 dark:text-emerald-400 border border-emerald-200 dark:border-emerald-800' ?>">
                                <?= $is_maintenance ? 'Mode Aktif' : 'Normal Operational' ?>
                            </span>
                        </div>
                        <p class="text-xs text-slate-500 dark:text-slate-400 mb-4 leading-relaxed">
                            Bila diaktifkan, sesi pengguna selain Administrator Utama akan otomatis diblokir/dilogout dengan pesan pemeliharaan.
                        </p>

                        <div class="p-3.5 bg-slate-50 dark:bg-slate-800/60 rounded-xl border border-slate-200 dark:border-slate-700/60 mb-4">
                            <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400 dark:text-slate-500 block mb-1">Pesan Pemeliharaan Terpasang:</span>
                            <div class="text-xs text-slate-700 dark:text-slate-300 italic">
                                "<?= htmlspecialchars($maintenance_msg) ?>"
                            </div>
                        </div>
                    </div>

                    <form action="actions/tambah_kelas.php" method="POST" class="pt-4 border-t border-slate-100 dark:border-slate-800">
                        <input type="hidden" name="action" value="quick_maintenance_toggle">
                        <div class="flex flex-col sm:flex-row items-center gap-3 justify-between">
                            <span class="text-xs font-semibold text-slate-600 dark:text-slate-400">
                                <?= $is_maintenance ? 'Klik untuk mematikan mode maintenance' : 'Klik untuk mengaktifkan maintenance darurat' ?>
                            </span>
                            <button type="submit" class="w-full sm:w-auto px-5 py-2.5 rounded-xl text-xs font-bold text-white transition-all shadow-sm active:scale-95 cursor-pointer <?= $is_maintenance ? 'bg-emerald-600 hover:bg-emerald-700' : 'bg-rose-600 hover:bg-rose-700' ?>">
                                <i class="fas <?= $is_maintenance ? 'fa-play' : 'fa-pause' ?> mr-1.5"></i>
                                <?= $is_maintenance ? 'Normalkan Sistem' : 'Aktifkan Maintenance' ?>
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Kolom Kanan: Akses Master Data Global Lintas-Kelas -->
                <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 p-6 shadow-sm">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-base font-extrabold text-slate-800 dark:text-white flex items-center gap-2">
                            <i class="fas fa-database text-indigo-600 dark:text-indigo-400"></i> Master Data Global (Lintas-Kelas)
                        </h3>
                        <span class="text-[10px] text-slate-400 font-semibold uppercase tracking-wider">Universal Data</span>
                    </div>
                    <p class="text-xs text-slate-500 dark:text-slate-400 mb-4">
                        Tabel-tabel master berikut berlaku bersama untuk seluruh kelas di program studi PTIK:
                    </p>

                    <div class="grid grid-cols-2 sm:grid-cols-3 gap-3">
                        <a href="database/db_dosen" class="p-3.5 rounded-xl bg-slate-50 hover:bg-indigo-50/70 dark:bg-slate-800/50 dark:hover:bg-slate-800 border border-slate-200 dark:border-slate-700/60 transition-all group">
                            <i class="fas fa-user-tie text-indigo-600 dark:text-indigo-400 text-lg mb-1.5 block group-hover:scale-110 transition-transform"></i>
                            <div class="font-bold text-xs text-slate-800 dark:text-slate-200 leading-tight">Master Dosen</div>
                            <div class="text-[10px] text-slate-400 mt-0.5">Dosen Pengampu</div>
                        </a>

                        <a href="database/db_matkul" class="p-3.5 rounded-xl bg-slate-50 hover:bg-indigo-50/70 dark:bg-slate-800/50 dark:hover:bg-slate-800 border border-slate-200 dark:border-slate-700/60 transition-all group">
                            <i class="fas fa-book-bookmark text-blue-600 dark:text-blue-400 text-lg mb-1.5 block group-hover:scale-110 transition-transform"></i>
                            <div class="font-bold text-xs text-slate-800 dark:text-slate-200 leading-tight">Mata Kuliah</div>
                            <div class="text-[10px] text-slate-400 mt-0.5">Kurikulum & SKS</div>
                        </a>

                        <a href="database/db_ruangan" class="p-3.5 rounded-xl bg-slate-50 hover:bg-indigo-50/70 dark:bg-slate-800/50 dark:hover:bg-slate-800 border border-slate-200 dark:border-slate-700/60 transition-all group">
                            <i class="fas fa-door-open text-emerald-600 dark:text-emerald-400 text-lg mb-1.5 block group-hover:scale-110 transition-transform"></i>
                            <div class="font-bold text-xs text-slate-800 dark:text-slate-200 leading-tight">Ruangan</div>
                            <div class="text-[10px] text-slate-400 mt-0.5">Gedung & Lab</div>
                        </a>

                        <a href="database/db_jam" class="p-3.5 rounded-xl bg-slate-50 hover:bg-indigo-50/70 dark:bg-slate-800/50 dark:hover:bg-slate-800 border border-slate-200 dark:border-slate-700/60 transition-all group">
                            <i class="fas fa-clock text-amber-600 dark:text-amber-400 text-lg mb-1.5 block group-hover:scale-110 transition-transform"></i>
                            <div class="font-bold text-xs text-slate-800 dark:text-slate-200 leading-tight">Jam Kuliah</div>
                            <div class="text-[10px] text-slate-400 mt-0.5">Sesi Jam ke-1 s/d 14</div>
                        </a>

                        <a href="database/db_periode" class="p-3.5 rounded-xl bg-slate-50 hover:bg-indigo-50/70 dark:bg-slate-800/50 dark:hover:bg-slate-800 border border-slate-200 dark:border-slate-700/60 transition-all group">
                            <i class="fas fa-calendar-days text-purple-600 dark:text-purple-400 text-lg mb-1.5 block group-hover:scale-110 transition-transform"></i>
                            <div class="font-bold text-xs text-slate-800 dark:text-slate-200 leading-tight">Periode Aktif</div>
                            <div class="text-[10px] text-slate-400 mt-0.5">Semester & Tahun</div>
                        </a>

                        <a href="database/db_users" class="p-3.5 rounded-xl bg-slate-50 hover:bg-indigo-50/70 dark:bg-slate-800/50 dark:hover:bg-slate-800 border border-slate-200 dark:border-slate-700/60 transition-all group">
                            <i class="fas fa-users-gear text-rose-600 dark:text-rose-400 text-lg mb-1.5 block group-hover:scale-110 transition-transform"></i>
                            <div class="font-bold text-xs text-slate-800 dark:text-slate-200 leading-tight">Kelola Akun</div>
                            <div class="text-[10px] text-slate-400 mt-0.5">RBAC & Password</div>
                        </a>
                    </div>
                </div>

            </div>

        </div>

        <!-- Footer Back-office -->
        <?php include 'includes/footer_db.php'; ?>
    </main>

    <!-- ==========================================
         MODAL 1: TAMBAH KELAS BARU & KETUA TINGKAT
         ========================================== -->
    <div id="modalTambahKelas" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs hidden transition-opacity">
        <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-2xl max-w-lg w-full max-h-[90vh] overflow-y-auto custom-scrollbar p-6">
            <div class="flex items-center justify-between pb-4 mb-4 border-b border-slate-100 dark:border-slate-800">
                <div>
                    <h3 class="text-base font-extrabold text-slate-900 dark:text-white">Tambah Kelas Baru</h3>
                    <p class="text-xs text-slate-400 dark:text-slate-500">Daftarkan kelas baru di angkatan 2024 prodi PTIK.</p>
                </div>
                <button type="button" onclick="closeTambahKelasModal()" class="w-8 h-8 rounded-lg bg-slate-100 dark:bg-slate-800 text-slate-400 hover:text-slate-700 dark:hover:text-white flex items-center justify-center transition-colors cursor-pointer">
                    <i class="fas fa-times text-xs"></i>
                </button>
            </div>

            <form action="actions/tambah_kelas.php" method="POST" class="space-y-4">
                <input type="hidden" name="action" value="tambah_kelas">

                <div>
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1">Nama Kelas *</label>
                    <input type="text" name="nama_kelas" required placeholder="Contoh: PTIK A 2024, PTIK B 2024" class="w-full bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-900 dark:text-white px-3.5 py-2.5 rounded-xl text-xs focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 outline-none transition-all">
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1">Angkatan *</label>
                        <input type="text" name="angkatan" value="2024" required class="w-full bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-900 dark:text-white px-3.5 py-2.5 rounded-xl text-xs focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 outline-none transition-all">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1">Status Awal</label>
                        <select name="status_aktif" class="w-full bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-900 dark:text-white px-3.5 py-2.5 rounded-xl text-xs focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 outline-none transition-all cursor-pointer">
                            <option value="aktif">Aktif</option>
                            <option value="non-aktif">Non-Aktif</option>
                        </select>
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1">Program Studi *</label>
                    <input type="text" name="prodi" value="Pendidikan Teknik Informatika dan Komputer" required class="w-full bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-900 dark:text-white px-3.5 py-2.5 rounded-xl text-xs focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 outline-none transition-all">
                </div>

                <div class="pt-3 border-t border-slate-100 dark:border-slate-800">
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-2">Penunjukan Ketua Tingkat</label>
                    <div class="grid grid-cols-3 gap-2 mb-3">
                        <label class="flex items-center gap-1.5 p-2.5 rounded-xl border border-slate-200 dark:border-slate-700 cursor-pointer text-xs font-semibold hover:bg-slate-50 dark:hover:bg-slate-800">
                            <input type="radio" name="ketua_mode" value="none" checked onchange="toggleKetuaInputs('none')">
                            <span>Nanti Saja</span>
                        </label>
                        <label class="flex items-center gap-1.5 p-2.5 rounded-xl border border-slate-200 dark:border-slate-700 cursor-pointer text-xs font-semibold hover:bg-slate-50 dark:hover:bg-slate-800">
                            <input type="radio" name="ketua_mode" value="new" onchange="toggleKetuaInputs('new')">
                            <span>Akun Baru</span>
                        </label>
                        <label class="flex items-center gap-1.5 p-2.5 rounded-xl border border-slate-200 dark:border-slate-700 cursor-pointer text-xs font-semibold hover:bg-slate-50 dark:hover:bg-slate-800">
                            <input type="radio" name="ketua_mode" value="existing" onchange="toggleKetuaInputs('existing')">
                            <span>Pilih User</span>
                        </label>
                    </div>

                    <!-- Input Form Akun Baru -->
                    <div id="ketuaNewSection" class="hidden space-y-3 p-3.5 bg-indigo-50/50 dark:bg-slate-800/40 rounded-xl border border-indigo-100 dark:border-slate-700/60">
                        <div>
                            <label class="block text-[11px] font-bold text-slate-700 dark:text-slate-300 mb-1">Nama Lengkap Ketua Tingkat *</label>
                            <input type="text" name="ketua_nama" placeholder="Contoh: Ahmad Fauzan" class="w-full bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-900 dark:text-white px-3 py-2 rounded-lg text-xs outline-none">
                        </div>
                        <div class="grid grid-cols-2 gap-2">
                            <div>
                                <label class="block text-[11px] font-bold text-slate-700 dark:text-slate-300 mb-1">NIM Mahasiswa *</label>
                                <input type="text" name="ketua_nim" placeholder="24020950..." class="w-full bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-900 dark:text-white px-3 py-2 rounded-lg text-xs outline-none">
                            </div>
                            <div>
                                <label class="block text-[11px] font-bold text-slate-700 dark:text-slate-300 mb-1">Password Awal *</label>
                                <input type="password" name="ketua_password" placeholder="Minimal 8 karakter" class="w-full bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-900 dark:text-white px-3 py-2 rounded-lg text-xs outline-none">
                            </div>
                        </div>
                        <div>
                            <label class="block text-[11px] font-bold text-slate-700 dark:text-slate-300 mb-1">Email Login *</label>
                            <input type="email" name="ketua_email" placeholder="ketua.a@simawa.id" class="w-full bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-900 dark:text-white px-3 py-2 rounded-lg text-xs outline-none">
                        </div>
                    </div>

                    <!-- Input Pilih User yang Ada -->
                    <div id="ketuaExistingSection" class="hidden p-3.5 bg-indigo-50/50 dark:bg-slate-800/40 rounded-xl border border-indigo-100 dark:border-slate-700/60">
                        <label class="block text-[11px] font-bold text-slate-700 dark:text-slate-300 mb-1">Pilih User / Mahasiswa *</label>
                        <select name="existing_user_id" class="w-full bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-900 dark:text-white px-3 py-2 rounded-lg text-xs outline-none cursor-pointer">
                            <option value="">-- Pilih Mahasiswa / User --</option>
                            <?php foreach ($users_keti as $uk): ?>
                                <option value="<?= $uk['id'] ?>">
                                    <?= htmlspecialchars($uk['nama_lengkap'] ?: $uk['email']) ?> 
                                    <?= !empty($uk['nim']) ? '(' . htmlspecialchars($uk['nim']) . ')' : '' ?>
                                    <?= !empty($uk['nama_kelas']) ? ' - saat ini di ' . htmlspecialchars($uk['nama_kelas']) : '' ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="flex items-center justify-end gap-2 pt-4 border-t border-slate-100 dark:border-slate-800">
                    <button type="button" onclick="closeTambahKelasModal()" class="px-4 py-2.5 rounded-xl text-xs font-bold text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors cursor-pointer">Batal</button>
                    <button type="submit" class="px-5 py-2.5 rounded-xl text-xs font-bold bg-indigo-600 hover:bg-indigo-700 text-white shadow-lg shadow-indigo-600/20 active:scale-95 transition-all cursor-pointer">Simpan Kelas</button>
                </div>
            </form>
        </div>
    </div>

    <!-- ==========================================
         MODAL 2: EDIT KELAS & KETUA TINGKAT
         ========================================== -->
    <div id="modalEditKelas" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs hidden transition-opacity">
        <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-2xl max-w-lg w-full max-h-[90vh] overflow-y-auto custom-scrollbar p-6">
            <div class="flex items-center justify-between pb-4 mb-4 border-b border-slate-100 dark:border-slate-800">
                <div>
                    <h3 class="text-base font-extrabold text-slate-900 dark:text-white">Edit Data Kelas</h3>
                    <p class="text-xs text-slate-400 dark:text-slate-500">Perbarui identitas kelas atau ganti Ketua Tingkat.</p>
                </div>
                <button type="button" onclick="closeEditKelasModal()" class="w-8 h-8 rounded-lg bg-slate-100 dark:bg-slate-800 text-slate-400 hover:text-slate-700 dark:hover:text-white flex items-center justify-center transition-colors cursor-pointer">
                    <i class="fas fa-times text-xs"></i>
                </button>
            </div>

            <form action="actions/tambah_kelas.php" method="POST" class="space-y-4">
                <input type="hidden" name="action" value="edit_kelas">
                <input type="hidden" name="kelas_id" id="edit_kelas_id">

                <div>
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1">Nama Kelas *</label>
                    <input type="text" name="nama_kelas" id="edit_nama_kelas" required class="w-full bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-900 dark:text-white px-3.5 py-2.5 rounded-xl text-xs focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 outline-none transition-all">
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1">Angkatan *</label>
                        <input type="text" name="angkatan" id="edit_angkatan" required class="w-full bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-900 dark:text-white px-3.5 py-2.5 rounded-xl text-xs focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 outline-none transition-all">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1">Status Kelas</label>
                        <select name="status_aktif" id="edit_status_aktif" class="w-full bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-900 dark:text-white px-3.5 py-2.5 rounded-xl text-xs focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 outline-none transition-all cursor-pointer">
                            <option value="aktif">Aktif</option>
                            <option value="non-aktif">Non-Aktif</option>
                        </select>
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1">Program Studi *</label>
                    <input type="text" name="prodi" id="edit_prodi" required class="w-full bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-900 dark:text-white px-3.5 py-2.5 rounded-xl text-xs focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 outline-none transition-all">
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1">Ketua Tingkat Penanggung Jawab</label>
                    <select name="ketua_user_id" id="edit_ketua_user_id" class="w-full bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-900 dark:text-white px-3.5 py-2.5 rounded-xl text-xs focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 outline-none transition-all cursor-pointer">
                        <option value="">-- Kosongkan (Belum Ditunjuk) --</option>
                        <?php foreach ($users_keti as $uk): ?>
                            <option value="<?= $uk['id'] ?>">
                                <?= htmlspecialchars($uk['nama_lengkap'] ?: $uk['email']) ?>
                                <?= !empty($uk['nim']) ? '(' . htmlspecialchars($uk['nim']) . ')' : '' ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="flex items-center justify-end gap-2 pt-4 border-t border-slate-100 dark:border-slate-800">
                    <button type="button" onclick="closeEditKelasModal()" class="px-4 py-2.5 rounded-xl text-xs font-bold text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors cursor-pointer">Batal</button>
                    <button type="submit" class="px-5 py-2.5 rounded-xl text-xs font-bold bg-amber-600 hover:bg-amber-700 text-white shadow-lg shadow-amber-600/20 active:scale-95 transition-all cursor-pointer">Perbarui Kelas</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Script Back-office & Modal Management -->
    <?php include 'includes/script_db.php'; ?>
    <script>
        function openTambahKelasModal() {
            document.getElementById('modalTambahKelas').classList.remove('hidden');
        }

        function closeTambahKelasModal() {
            document.getElementById('modalTambahKelas').classList.add('hidden');
        }

        function toggleKetuaInputs(mode) {
            const newSec = document.getElementById('ketuaNewSection');
            const existSec = document.getElementById('ketuaExistingSection');
            newSec.classList.add('hidden');
            existSec.classList.add('hidden');

            if (mode === 'new') {
                newSec.classList.remove('hidden');
            } else if (mode === 'existing') {
                existSec.classList.remove('hidden');
            }
        }

        function openEditKelasModal(data) {
            document.getElementById('edit_kelas_id').value = data.id;
            document.getElementById('edit_nama_kelas').value = data.nama_kelas;
            document.getElementById('edit_angkatan').value = data.angkatan;
            document.getElementById('edit_prodi').value = data.prodi;
            document.getElementById('edit_status_aktif').value = data.status_aktif;
            document.getElementById('edit_ketua_user_id').value = data.ketua_tingkat_user_id || '';
            document.getElementById('modalEditKelas').classList.remove('hidden');
        }

        function closeEditKelasModal() {
            document.getElementById('modalEditKelas').classList.add('hidden');
        }

        // SweetAlert Flash Message Handling
        <?php if (!empty($success_msg)): ?>
            Swal.fire({
                icon: 'success',
                title: 'Berhasil!',
                html: <?= json_encode($success_msg) ?>,
                confirmButtonColor: '#4f46e5',
                customClass: { popup: 'rounded-2xl' }
            });
        <?php endif; ?>

        <?php if (!empty($error_msg)): ?>
            Swal.fire({
                icon: 'error',
                title: 'Perhatian!',
                html: <?= json_encode($error_msg) ?>,
                confirmButtonColor: '#ef4444',
                customClass: { popup: 'rounded-2xl' }
            });
        <?php endif; ?>
    </script>
</body>

</html>
