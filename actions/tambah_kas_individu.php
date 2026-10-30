<?php
session_start();
include '../koneksi.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../../index.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$nama_lengkap = $_SESSION['nama_lengkap'];
$foto = $_SESSION['foto'];
$role = $_SESSION['role'];

if ($role === 'Mahasiswa' || $role === 'Ketua Tingkat' || $role === 'Sekretaris') {
    include '../includes/alert_role.php';
    exit();
}

/* =====================
   AMBIL PERIODE AKTIF
   ===================== */
$q_periode = mysqli_query($koneksi, "SELECT id FROM periode WHERE status = 'aktif' LIMIT 1");
$periode = mysqli_fetch_assoc($q_periode);
$periode_id = $periode['id'];

$mahasiswa_id_selected = $_GET['mahasiswa_id'] ?? '';

// Handle Update
if (isset($_POST['update_kas_individu'])) {
    $m_id = mysqli_real_escape_string($koneksi, $_POST['mahasiswa_id']);

    // Cek kelas mahasiswa & validasi akses
    $q_mhs_chk = mysqli_query($koneksi, "SELECT kelas_id FROM mahasiswa WHERE id = '$m_id' LIMIT 1");
    $r_mhs_chk = mysqli_fetch_assoc($q_mhs_chk);
    $target_kelas_id = $r_mhs_chk['kelas_id'] ?? (getKelasScope() ?? 1);
    requireKelasAccess($target_kelas_id);

    if (!empty($_POST['status']) && is_array($_POST['status'])) {
        foreach ($_POST['status'] as $minggu => $status) {
            $status_safe = mysqli_real_escape_string($koneksi, $status);
            $minggu_safe = mysqli_real_escape_string($koneksi, $minggu);

            $posted_tanggal = $_POST['tanggal'][$minggu] ?? '';

            // Check existence
            $res = mysqli_query($koneksi, "SELECT status, tanggal_bayar FROM kas WHERE mahasiswa_id='$m_id' AND minggu_ke='$minggu_safe' AND periode_id='$periode_id'");
            $exists = mysqli_num_rows($res) > 0;
            $existing = $exists ? mysqli_fetch_assoc($res) : null;

            $tanggal_to_save = null;
            if ($status_safe === 'Belum') {
                $tanggal_to_save = null;
            } elseif ($posted_tanggal !== '') {
                $tanggal_to_save = mysqli_real_escape_string($koneksi, $posted_tanggal);
            } elseif ($exists && !empty($existing['tanggal_bayar'])) {
                $tanggal_to_save = $existing['tanggal_bayar'];
            }

            if ($exists) {
                if ($tanggal_to_save !== null) {
                    mysqli_query($koneksi, "UPDATE kas SET status='$status_safe', tanggal_bayar='$tanggal_to_save', kelas_id='$target_kelas_id' WHERE mahasiswa_id='$m_id' AND minggu_ke='$minggu_safe' AND periode_id='$periode_id'");
                } else {
                    mysqli_query($koneksi, "UPDATE kas SET status='$status_safe', tanggal_bayar=NULL, kelas_id='$target_kelas_id' WHERE mahasiswa_id='$m_id' AND minggu_ke='$minggu_safe' AND periode_id='$periode_id'");
                }
            } else {
                if ($tanggal_to_save !== null) {
                    mysqli_query($koneksi, "INSERT INTO kas (mahasiswa_id, minggu_ke, status, tanggal_bayar, periode_id, kelas_id) VALUES ('$m_id', '$minggu_safe', '$status_safe', '$tanggal_to_save', '$periode_id', '$target_kelas_id')");
                } else {
                    mysqli_query($koneksi, "INSERT INTO kas (mahasiswa_id, minggu_ke, status, tanggal_bayar, periode_id, kelas_id) VALUES ('$m_id', '$minggu_safe', '$status_safe', NULL, '$periode_id', '$target_kelas_id')");
                }
            }
        }
    }
    $_SESSION['success'] = "Data kas individu berhasil diperbarui.";
    header("Location: tambah_kas_individu.php?mahasiswa_id=$m_id");
    exit;
}

// Get Mahasiswa List for dropdown
$where_mhs_drop = getKelasWhereClause('', false);
$where_mhs_drop_sql = $where_mhs_drop ? "WHERE $where_mhs_drop" : "";
$all_mhs = mysqli_query($koneksi, "SELECT id, nim, nama_lengkap FROM mahasiswa $where_mhs_drop_sql ORDER BY nim ASC");

?>
<!DOCTYPE html>
<html lang="en">

<head>

    <title>Kas Individu - SIMAWA PTIK 2024</title>
    <?php include '../includes/head.php'; ?>
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

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>

<body class="bg-slate-100 dark:bg-slate-950 transition-colors">
    <?php include '../includes/menu_db.php'; ?>

    <main class="ml-0 lg:ml-48 pt-20 p-6 flex flex-col min-h-screen">
        <div class="bg-white dark:bg-slate-900/50 rounded-xl border border-slate-200 dark:border-slate-800 p-6 flex-grow w-full shadow-sm transition-all">
            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-6">
                <h3 class="text-xl font-bold text-slate-800 dark:text-slate-100">Input Kas Individu</h3>
                <a href="../views/manajemen_kas.php" class="bg-indigo-600 dark:bg-indigo-700 text-white px-4 py-2 rounded-lg text-xs hover:bg-indigo-700 dark:hover:bg-indigo-600 transition-colors shadow-sm">Kembali</a>
            </div>

            <form method="GET" class="mb-6">
                <div class="max-w-md">
                    <label class="block text-xs font-medium text-slate-700 dark:text-slate-400 mb-1">Pilih Mahasiswa</label>
                    <select name="mahasiswa_id" onchange="this.form.submit()" class="w-full bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-900 dark:text-slate-200 p-2 rounded-lg text-xs focus:ring-1 focus:ring-indigo-500 outline-none transition-colors">
                        <option value="">-- Pilih Mahasiswa --</option>
                        <?php while ($m = mysqli_fetch_assoc($all_mhs)): ?>
                            <option value="<?= $m['id'] ?>" <?= ($mahasiswa_id_selected == $m['id']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($m['nim']) ?> - <?= htmlspecialchars($m['nama_lengkap']) ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
            </form>

            <?php if ($mahasiswa_id_selected):
                $m_id_safe = mysqli_real_escape_string($koneksi, $mahasiswa_id_selected);
                $q_mhs_info = mysqli_query($koneksi, "SELECT * FROM mahasiswa WHERE id = '$m_id_safe'");
                $mhs_info = mysqli_fetch_assoc($q_mhs_info);
                if ($mhs_info && !empty($mhs_info['kelas_id'])) {
                    requireKelasAccess($mhs_info['kelas_id']);
                }
            ?>
                <div class="bg-indigo-50 dark:bg-indigo-950/30 border border-indigo-100 dark:border-indigo-900/50 rounded-xl p-4 mb-6 transition-all">
                    <div class="flex items-center gap-4">
                        <?php if (!empty($mhs_info['foto'])): ?>
                            <img src="../storage/profile/<?= htmlspecialchars($mhs_info['foto']) ?>" class="w-12 h-12 rounded-full border-2 border-white dark:border-slate-700 shadow-sm">
                        <?php else: ?>
                            <div class="w-12 h-12 rounded-full bg-indigo-200 dark:bg-indigo-900 flex items-center justify-center text-indigo-700 dark:text-indigo-400">
                                <i class="fa fa-user text-xl"></i>
                            </div>
                        <?php endif; ?>
                        <div>
                            <h4 class="font-bold text-slate-800 dark:text-slate-100"><?= htmlspecialchars($mhs_info['nama_lengkap']) ?></h4>
                            <p class="text-xs text-slate-600 dark:text-slate-400">NIM: <?= htmlspecialchars($mhs_info['nim']) ?></p>
                        </div>
                    </div>
                </div>

                <form method="POST">
                    <input type="hidden" name="mahasiswa_id" value="<?= htmlspecialchars($mahasiswa_id_selected) ?>">
                    <div class="overflow-x-auto rounded-xl border border-slate-200 dark:border-slate-800 shadow-sm">
                        <table class="min-w-full divide-y divide-slate-200 dark:divide-slate-800">
                            <thead class="bg-indigo-900 dark:bg-indigo-950">
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-bold text-white uppercase tracking-wider w-20">Minggu</th>
                                    <th class="px-4 py-3 text-left text-xs font-bold text-white uppercase tracking-wider">Status</th>
                                    <th class="px-4 py-3 text-left text-xs font-bold text-white uppercase tracking-wider">Tanggal Bayar</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white dark:bg-slate-900/50 divide-y divide-slate-200 dark:divide-slate-800">
                                <?php
                                for ($i = 1; $i <= 16; $i++) {
                                    $q_kas = mysqli_query($koneksi, "SELECT status, tanggal_bayar FROM kas WHERE mahasiswa_id = '$m_id_safe' AND minggu_ke = '$i' AND periode_id = '$periode_id'");
                                    $kas_data = mysqli_fetch_assoc($q_kas);
                                    $status_curr = $kas_data['status'] ?? 'Belum';
                                    $tgl_curr = $kas_data['tanggal_bayar'] ?? '';
                                ?>
                                    <tr class="<?= ($i % 2 == 0) ? 'bg-slate-50 dark:bg-slate-800/30' : 'bg-white dark:bg-slate-900/20' ?> hover:bg-indigo-50 dark:hover:bg-indigo-900/40 transition-colors group">
                                        <td class="px-4 py-3 whitespace-nowrap text-xs font-medium text-slate-800 dark:text-slate-200 text-center">
                                            Minggu <?= $i ?>
                                        </td>
                                        <td class="px-4 py-3 whitespace-nowrap text-xs">
                                            <select name="status[<?= $i ?>]" class="status-select w-full sm:w-32 bg-white dark:bg-slate-800 border-slate-300 dark:border-slate-700 text-slate-900 dark:text-slate-200 p-1.5 rounded-md text-xs focus:ring-1 focus:ring-indigo-500 transition-colors" data-week="<?= $i ?>">
                                                <option value="Lunas" <?= ($status_curr == 'Lunas') ? 'selected' : '' ?>>Lunas</option>
                                                <option value="Belum" <?= ($status_curr == 'Belum') ? 'selected' : '' ?>>Belum</option>
                                            </select>
                                        </td>
                                        <td class="px-4 py-3 whitespace-nowrap text-xs">
                                            <input type="date" name="tanggal[<?= $i ?>]" id="tanggal_<?= $i ?>" value="<?= htmlspecialchars($tgl_curr) ?>" class="border p-1.5 rounded-md text-xs w-full sm:w-48 bg-white dark:bg-slate-800 border-slate-300 dark:border-slate-700 text-slate-900 dark:text-slate-200 focus:ring-1 focus:ring-indigo-500 transition-colors disabled:opacity-50 dark:disabled:bg-slate-900/50" <?= ($status_curr == 'Belum') ? 'disabled' : '' ?>>
                                        </td>
                                    </tr>
                                <?php } ?>
                            </tbody>
                        </table>
                    </div>
                    <div class="flex justify-end mt-8 pt-5 border-t border-slate-200 dark:border-slate-800">
                        <button type="submit" name="update_kas_individu" class="bg-indigo-900 dark:bg-indigo-800 hover:bg-indigo-800 dark:hover:bg-indigo-700 text-white rounded-xl px-10 py-3 text-xs font-bold flex items-center gap-2 shadow-lg shadow-indigo-900/20 dark:shadow-indigo-950/40 transition-all active:scale-95">
                            <i class="fa fa-save"></i> Simpan Perubahan
                        </button>
                    </div>
                </form>
            <?php else: ?>
                <div class="flex flex-col items-center justify-center py-20 text-slate-400 dark:text-slate-600 transition-all">
                    <i class="fa fa-user-circle text-7xl mb-4 opacity-20"></i>
                    <p class="text-sm font-medium">Silakan pilih mahasiswa untuk melihat data kas</p>
                </div>
            <?php endif; ?>
        </div>
        <?php include '../includes/footer_db.php'; ?>
    </main>
    <?php include '../includes/script_db.php'; ?>

    <script>
        document.querySelectorAll('.status-select').forEach(select => {
            select.addEventListener('change', function() {
                const week = this.getAttribute('data-week');
                const dateInput = document.getElementById('tanggal_' + week);
                if (this.value === 'Lunas') {
                    dateInput.disabled = false;
                    if (!dateInput.value) {
                        const today = new Date().toISOString().split('T')[0];
                        dateInput.value = today;
                    }
                } else {
                    dateInput.value = '';
                    dateInput.disabled = true;
                }
            });
        });

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
    </script>
</body>

</html>