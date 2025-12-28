<?php
session_start();

// Set timezone to WITA (Waktu Indonesia Tengah - UTC+8)
date_default_timezone_set('Asia/Makassar');

include '../../koneksi.php';

// Simple authentication check
if (!isset($_SESSION['user_id'])) {
    header("Location: ../../index.php");
    exit;
}

$nama_lengkap = $_SESSION['nama_lengkap'] ?? '';
$foto = $_SESSION['foto'] ?? '';
$role = $_SESSION['role'];

// Handle manual trigger
$manual_run_result = null;
if (isset($_POST['run_reminder'])) {
    // Run the reminder script via inclusion (safer for InfinityFree)
    $force_include = isset($_POST['force_send']);
    
    ob_start();
    include 'telegram_reminder.php';
    $output_text = ob_get_clean();
    
    $manual_run_result = [
        'success' => true, // We assume success if no fatal error occurred
        'output' => $output_text,
        'return_code' => 0
    ];
}

// Read log file
$log_file = __DIR__ . '/telegram_reminder.log';
$log_content = '';
if (file_exists($log_file)) {
    $log_lines = file($log_file, FILE_IGNORE_NEW_LINES);
    // Get last 50 lines
    $log_lines = array_slice($log_lines, -50);
    $log_content = implode("\n", $log_lines);
}

// Check tomorrow's tasks
$tomorrow = date('Y-m-d', strtotime('+1 day'));
$query_tomorrow = "SELECT 
                    t.id,
                    t.deadline_tanggal,
                    t.deadline_pukul,
                    t.deskripsi,
                    mk.nama_matkul
                  FROM tugas t
                  JOIN mata_kuliah mk ON t.matkul_id = mk.id
                  WHERE t.deadline_tanggal = '$tomorrow'
                  ORDER BY t.deadline_pukul ASC";
$result_tomorrow = mysqli_query($koneksi, $query_tomorrow);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Telegram Reminder Manager - PTIK C 2024</title>
    <link href="../../frontend/tailwind/src/output.css" rel="stylesheet">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body class="bg-gray-100 font-['Poppins']">
    <div class="container mx-auto px-4 py-8 max-w-6xl">
        <div class="mb-6">
            <a href="../dashboard/dashboard.php" class="text-indigo-600 hover:text-indigo-800 text-sm">
                <i class="fa fa-arrow-left"></i> Kembali ke Dashboard
            </a>
        </div>

        <div class="bg-white rounded-lg shadow-lg p-6 mb-6">
            <div class="flex items-center justify-between mb-4">
                <h1 class="text-2xl font-bold text-gray-800">
                    <i class="fab fa-telegram text-blue-500"></i> Telegram Reminder Manager
                </h1>
                <div class="text-sm text-gray-500">
                    <i class="fa fa-clock"></i> <?= date('d/m/Y H:i') ?>
                </div>
            </div>
            <p class="text-gray-600 text-sm">Kelola pengiriman otomatis reminder H-1 deadline tugas ke Telegram</p>
        </div>

        <!-- Manual Trigger Section -->
        <div class="bg-white rounded-lg shadow-lg p-6 mb-6">
            <h2 class="text-xl font-bold text-gray-800 mb-4">
                <i class="fa fa-play-circle text-green-500"></i> Jalankan Reminder Manual
            </h2>
            <p class="text-sm text-gray-600 mb-4">
                Klik tombol di bawah untuk menjalankan script reminder secara manual. Script akan mengecek tugas yang deadline-nya besok dan mengirim notifikasi ke Telegram.
            </p>
            <form method="POST" class="space-y-4">
                <div class="flex items-center">
                    <input type="checkbox" name="force_send" id="force_send" class="w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500">
                    <label for="force_send" class="ml-2 text-sm text-gray-700 font-medium">
                        Bypass limit 1x sehari (Kirim paksa)
                    </label>
                </div>
                <button type="submit" name="run_reminder" class="bg-blue-600 text-white px-6 py-3 rounded-lg hover:bg-blue-700 transition flex items-center gap-2">
                    <i class="fab fa-telegram"></i> Jalankan Sekarang
                </button>
            </form>
        </div>

        <!-- Tasks Tomorrow -->
        <div class="bg-white rounded-lg shadow-lg p-6 mb-6">
            <h2 class="text-xl font-bold text-gray-800 mb-4">
                <i class="fa fa-calendar-check text-orange-500"></i> Tugas Besok (<?= date('d/m/Y', strtotime($tomorrow)) ?>)
            </h2>
            <?php if ($result_tomorrow && mysqli_num_rows($result_tomorrow) > 0): ?>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 text-sm">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">No</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Mata Kuliah</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Deadline</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Deskripsi</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php 
                            $no = 1;
                            while ($task = mysqli_fetch_assoc($result_tomorrow)): 
                            ?>
                                <tr class="hover:bg-gray-50">
                                    <td class="px-4 py-3"><?= $no++ ?></td>
                                    <td class="px-4 py-3 font-semibold"><?= htmlspecialchars($task['nama_matkul']) ?></td>
                                    <td class="px-4 py-3">
                                        <span class="bg-red-100 text-red-800 px-2 py-1 rounded text-xs">
                                            <i class="fa fa-clock"></i> <?= date('H:i', strtotime($task['deadline_pukul'])) ?> WITA
                                        </span>
                                    </td>
                                    <td class="px-4 py-3"><?= htmlspecialchars($task['deskripsi']) ?: '-' ?></td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="text-center py-8 text-gray-500">
                    <i class="fa fa-inbox text-4xl mb-2"></i>
                    <p>Tidak ada tugas dengan deadline besok</p>
                </div>
            <?php endif; ?>
        </div>

        <!-- Log Viewer -->
        <div class="bg-white rounded-lg shadow-lg p-6">
            <h2 class="text-xl font-bold text-gray-800 mb-4">
                <i class="fa fa-file-alt text-purple-500"></i> Log Aktivitas (50 Baris Terakhir)
            </h2>
            <div class="bg-gray-900 text-green-400 p-4 rounded-lg font-mono text-xs overflow-x-auto" style="max-height: 400px; overflow-y: auto;">
                <pre><?= htmlspecialchars($log_content) ?: 'Log file kosong atau belum ada' ?></pre>
            </div>
            <div class="mt-4 text-sm text-gray-600">
                <i class="fa fa-info-circle"></i> Log file location: <code class="bg-gray-100 px-2 py-1 rounded"><?= $log_file ?></code>
            </div>
        </div>
    </div>

    <?php if ($manual_run_result): ?>
        <script>
            <?php if ($manual_run_result['success']): ?>
                Swal.fire({
                    title: 'Berhasil!',
                    html: '<pre class="text-left text-sm"><?= addslashes($manual_run_result['output']) ?></pre>',
                    icon: 'success',
                    confirmButtonText: 'OK'
                });
            <?php else: ?>
                Swal.fire({
                    title: 'Gagal!',
                    html: '<pre class="text-left text-sm"><?= addslashes($manual_run_result['output']) ?></pre>',
                    icon: 'error',
                    confirmButtonText: 'OK'
                });
            <?php endif; ?>
        </script>
    <?php endif; ?>
</body>
</html>
