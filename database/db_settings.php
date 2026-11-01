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

if ($_SESSION['role'] !== 'Administrator Utama') {
    include '../includes/alert_role.php';
    exit();
}

$is_maintenance = is_maintenance_mode($koneksi);
$maintenance_msg = get_maintenance_message($koneksi);

$success_msg = $_SESSION['success'] ?? '';
$error_msg   = $_SESSION['error'] ?? '';
unset($_SESSION['success'], $_SESSION['error']);
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <title>Pengaturan Sistem & Maintenance - SIMAWA PTIK 2024</title>
    <?php include '../includes/head.php'; ?>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        .rotate-180 {
            transform: rotate(180deg);
        }

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
        <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 p-6 flex-grow w-full shadow-sm">
            
            <!-- Title Header -->
            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 pb-6 mb-6 border-b border-slate-100 dark:border-slate-800">
                <div>
                    <div class="flex items-center gap-2">
                        <h3 class="text-xl font-black text-slate-800 dark:text-slate-100">Pengaturan Sistem</h3>
                        <span class="px-2.5 py-0.5 rounded-full text-[10px] font-extrabold uppercase tracking-wider <?= $is_maintenance ? 'bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-300 border border-amber-300 dark:border-amber-700' : 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-300 border border-emerald-300 dark:border-emerald-700' ?>">
                            <?= $is_maintenance ? 'Maintenance Mode' : 'System Operational' ?>
                        </span>
                    </div>
                    <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">Kelola mode pemeliharaan (maintenance) dan akses login pengguna aplikasi.</p>
                </div>
            </div>

            <!-- Maintenance Status Box & Control Form -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                
                <!-- Form Configuration (Left - 2 Cols) -->
                <div class="lg:col-span-2">
                    <form action="../actions/toggle_maintenance" method="POST" class="space-y-6">
                        
                        <!-- Card Switch Toggle -->
                        <div class="p-5 rounded-2xl border transition-all duration-300 <?= $is_maintenance ? 'bg-amber-500/5 border-amber-500/30' : 'bg-slate-50 dark:bg-slate-800/40 border-slate-200 dark:border-slate-800' ?>">
                            <div class="flex items-center justify-between gap-4">
                                <div class="flex items-center gap-3.5">
                                    <div class="w-12 h-12 rounded-xl flex items-center justify-center <?= $is_maintenance ? 'bg-amber-500 text-white shadow-lg shadow-amber-500/20' : 'bg-indigo-600 text-white shadow-lg shadow-indigo-600/20' ?>">
                                        <i class="fas <?= $is_maintenance ? 'fa-screwdriver-wrench' : 'fa-server' ?> text-lg"></i>
                                    </div>
                                    <div>
                                        <label for="maintenance_toggle" class="text-sm font-bold text-slate-800 dark:text-white cursor-pointer select-none">
                                            Status Mode Maintenance
                                        </label>
                                        <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">
                                            Aktifkan untuk membatasi akses login pengguna non-Administrator Utama.
                                        </p>
                                    </div>
                                </div>

                                <!-- Custom Switch Input -->
                                <label class="relative inline-flex items-center cursor-pointer shrink-0">
                                    <input type="checkbox" name="maintenance_mode" id="maintenance_toggle" value="1" <?= $is_maintenance ? 'checked' : '' ?> class="sr-only peer">
                                    <div class="w-14 h-7 bg-slate-300 peer-focus:outline-none dark:bg-slate-700 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-0.5 after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-6 after:w-6 after:transition-all peer-checked:bg-amber-500"></div>
                                </label>
                            </div>
                        </div>

                        <!-- Textarea Custom Message -->
                        <div>
                            <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-2">
                                Pesan Peringatan Maintenance
                            </label>
                            <div class="relative">
                                <textarea name="maintenance_message" rows="4" required
                                    class="w-full rounded-xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-950 text-slate-900 dark:text-white p-3.5 focus:outline-none focus:ring-2 focus:ring-amber-500/30 focus:border-amber-500 transition-all text-xs leading-relaxed placeholder:text-slate-400 dark:placeholder:text-slate-600"
                                    placeholder="Tuliskan pesan yang akan ditampilkan saat pengguna lain mencoba login..."><?= htmlspecialchars($maintenance_msg) ?></textarea>
                            </div>
                            <p class="text-[11px] text-slate-400 dark:text-slate-500 mt-1.5">
                                Pesan ini akan dimunculkan pada notifikasi SweetAlert saat pengguna non-admin mencoba login saat mode maintenance aktif.
                            </p>
                        </div>

                        <!-- Submit Button -->
                        <div class="pt-2">
                            <button type="submit"
                                class="px-6 py-3 bg-indigo-600 hover:bg-indigo-700 active:scale-95 text-white text-xs font-bold rounded-xl shadow-lg shadow-indigo-600/20 transition-all flex items-center gap-2">
                                <i class="fas fa-save"></i>
                                <span>Simpan Pengaturan</span>
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Info Box (Right - 1 Col) -->
                <div class="space-y-4">
                    <div class="p-5 rounded-2xl bg-indigo-50/50 dark:bg-indigo-950/20 border border-indigo-100 dark:border-indigo-900/30">
                        <div class="flex items-center gap-2 text-indigo-900 dark:text-indigo-300 font-bold text-xs uppercase tracking-wider mb-3">
                            <i class="fas fa-circle-info text-sm"></i>
                            <span>Aturan Akses Maintenance</span>
                        </div>
                        <ul class="space-y-3 text-xs text-slate-600 dark:text-slate-300 leading-relaxed">
                            <li class="flex items-start gap-2.5">
                                <i class="fas fa-check-circle text-emerald-500 text-sm mt-0.5 shrink-0"></i>
                                <span><strong>Administrator Utama:</strong> Memiliki akses penuh, bisa login dan mengakses seluruh halaman tanpa hambatan.</span>
                            </li>
                            <li class="flex items-start gap-2.5">
                                <i class="fas fa-times-circle text-rose-500 text-sm mt-0.5 shrink-0"></i>
                                <span><strong>Mahasiswa & Role Lain:</strong> Tidak dapat login. Jika sesi sudah aktif, pengguna akan otomatis ter-logout saat mengakses halaman.</span>
                            </li>
                            <li class="flex items-start gap-2.5">
                                <i class="fas fa-eye text-indigo-500 text-sm mt-0.5 shrink-0"></i>
                                <span><strong>Indikator Visual:</strong> Banner mode maintenance akan otomatis terlihat di halaman login dan menu sidebar.</span>
                            </li>
                        </ul>
                    </div>

                    <div class="p-5 rounded-2xl bg-slate-50 dark:bg-slate-800/30 border border-slate-200 dark:border-slate-800">
                        <div class="text-xs text-slate-500 dark:text-slate-400 font-medium">
                            <p class="font-bold text-slate-700 dark:text-slate-200 mb-1">Catatan Keamanan</p>
                            <p>Fitur ini menggunakan basis data MySQL untuk menyimpan preferensi secara persisten, sehingga konfigurasi tetap tersimpan dengan aman meskipun server restart.</p>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </main>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const isDarkMode = document.documentElement.classList.contains('dark');
            <?php if (!empty($success_msg)): ?>
                Swal.fire({
                    icon: 'success',
                    title: 'Berhasil!',
                    text: '<?= addslashes($success_msg); ?>',
                    confirmButtonColor: '#4f46e5',
                    background: isDarkMode ? '#0f172a' : '#fff',
                    color: isDarkMode ? '#fff' : '#64748b'
                });
            <?php elseif (!empty($error_msg)): ?>
                Swal.fire({
                    icon: 'error',
                    title: 'Gagal!',
                    text: '<?= addslashes($error_msg); ?>',
                    confirmButtonColor: '#4f46e5',
                    background: isDarkMode ? '#0f172a' : '#fff',
                    color: isDarkMode ? '#fff' : '#64748b'
                });
            <?php endif; ?>
        });
    </script>
</body>

</html>
