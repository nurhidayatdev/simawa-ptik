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

if ($_SESSION['role'] === 'Mahasiswa' || $_SESSION['role'] === 'Ketua Tingkat' || $_SESSION['role'] === 'Sekretaris') {
    include '../includes/alert_role.php';
    exit();
}

// Handle WhatsApp Sending Confirmation
if (isset($_GET['send_wa']) && isset($_SESSION['pending_wa_kas'])) {
    include_once '../functions/helpers.php';
    $res_wa = sendFonnteMessage($_SESSION['pending_wa_kas']);
    unset($_SESSION['pending_wa_kas']);
    
    if ($res_wa['success']) {
        $_SESSION['success'] = "Laporan berhasil terkirim ke WhatsApp!";
    } else {
        $_SESSION['success'] = "Gagal kirim WA: " . $res_wa['error'];
    }
    $minggu = $_GET['minggu_ke'] ?? '';
    header("Location: ../views/manajemen_kas.php?minggu_ke=$minggu&wa_sent=1");
    exit;
}


/* =====================
   AMBIL PERIODE AKTIF
   ===================== */
$q_periode = mysqli_query($koneksi, "SELECT id FROM periode WHERE status = 'aktif' LIMIT 1");
$periode = mysqli_fetch_assoc($q_periode);
$periode_id = $periode['id'];

if (isset($_POST['simpan_kas'])) {
    $minggu = $_POST['minggu_ke'];

    $target_kelas_id = getKelasScope() ?? 1;
    if (!empty($_POST['status']) && is_array($_POST['status'])) {
        foreach ($_POST['status'] as $mahasiswa_id => $status) {
            $mahasiswa_id = mysqli_real_escape_string($koneksi, $mahasiswa_id);
            $status_safe = mysqli_real_escape_string($koneksi, $status);
            $minggu_safe = mysqli_real_escape_string($koneksi, $minggu);

            // get posted tanggal for this mahasiswa (may be empty)
            $posted_tanggal = '';
            if (!empty($_POST['tanggal']) && isset($_POST['tanggal'][$mahasiswa_id])) {
                $posted_tanggal = $_POST['tanggal'][$mahasiswa_id];
            }

            // fetch existing kas row if any
            $res = mysqli_query($koneksi, "SELECT status, tanggal_bayar FROM kas WHERE mahasiswa_id='$mahasiswa_id' AND minggu_ke='$minggu_safe' AND periode_id='$periode_id'");
            $exists = mysqli_num_rows($res) > 0;
            $existing = $exists ? mysqli_fetch_assoc($res) : null;

            // Determine tanggal to save
            $tanggal_to_save = null;
            if ($status_safe === 'Belum') {
                $tanggal_to_save = null;
            } elseif ($posted_tanggal !== '') {
                $tanggal_to_save = mysqli_real_escape_string($koneksi, $posted_tanggal);
            } elseif ($exists && !empty($existing['tanggal_bayar'])) {
                $tanggal_to_save = $existing['tanggal_bayar'];
            }

            if ($exists) {
                // Update: only set tanggal_bayar if not null, otherwise set NULL
                if ($tanggal_to_save !== null) {
                    mysqli_query($koneksi, "UPDATE kas SET status='$status_safe', tanggal_bayar='$tanggal_to_save', kelas_id='$target_kelas_id' WHERE mahasiswa_id='$mahasiswa_id' AND minggu_ke='$minggu_safe' AND periode_id='$periode_id'");
                } else {
                    mysqli_query($koneksi, "UPDATE kas SET status='$status_safe', tanggal_bayar=NULL, kelas_id='$target_kelas_id' WHERE mahasiswa_id='$mahasiswa_id' AND minggu_ke='$minggu_safe' AND periode_id='$periode_id'");
                }
            } else {
                // Insert new row
                if ($tanggal_to_save !== null) {
                    mysqli_query($koneksi, "INSERT INTO kas (mahasiswa_id, minggu_ke, status, tanggal_bayar, periode_id, kelas_id) VALUES ('$mahasiswa_id', '$minggu_safe', '$status_safe', '$tanggal_to_save', '$periode_id', '$target_kelas_id')");
                } else {
                    mysqli_query($koneksi, "INSERT INTO kas (mahasiswa_id, minggu_ke, status, tanggal_bayar, periode_id, kelas_id) VALUES ('$mahasiswa_id', '$minggu_safe', '$status_safe', NULL, '$periode_id', '$target_kelas_id')");
                }
            }
        }
    }

    // --- FITUR: WHATSAPP NOTIFICATION VIA FONNTE ---
    include_once '../functions/helpers.php';

    // Fetch all students and their status for this week to create a complete report
    $where_mhs_rep = getKelasWhereClause('m', true);
    $where_k_rep = getKelasWhereClause('k', true);
    $q_report = mysqli_query($koneksi, "
        SELECT m.nama_panggilan, IFNULL(k.status, 'Belum') as status_bayar
        FROM mahasiswa m
        LEFT JOIN kas k ON k.mahasiswa_id = m.id AND k.minggu_ke = '$minggu' AND k.periode_id = '$periode_id' $where_k_rep
        WHERE 1=1 $where_mhs_rep
        ORDER BY m.nama_panggilan ASC
    ");

    $lunas = [];
    $belum = [];

    while ($row = mysqli_fetch_assoc($q_report)) {
        if ($row['status_bayar'] === 'Lunas') {
            $lunas[] = $row['nama_panggilan'];
        } else {
            $belum[] = $row['nama_panggilan'];
        }
    }

    $wa_msg = "💰 *LAPORAN KAS SIMAWA PTIK 2024*\n";
    $wa_msg .= "📅 *Minggu Ke-{$minggu}*\n\n";

    $count_belum = count($belum);
    $wa_msg .= "❌ *BELUM BAYAR ({$count_belum} Orang):*\n";
    if ($count_belum > 0) {
        foreach ($belum as $idx => $nama) {
            $wa_msg .= ($idx + 1) . ". " . $nama . "\n";
        }
    } else {
        $wa_msg .= "_Alhamdulillah, Lunas Semua!_ ✨\n";
    }

    $count_lunas = count($lunas);
    $wa_msg .= "\n✅ *SUDAH BAYAR ({$count_lunas} Orang):*\n";
    if ($count_lunas > 0) {
        foreach ($lunas as $idx => $nama) {
            $wa_msg .= ($idx + 1) . ". " . $nama . "\n";
        }
    } else {
        $wa_msg .= "_Belum ada data pembayaran._\n";
    }

    // --- FITUR: WHATSAPP NOTIFICATION PREPARATION ---
    $_SESSION['pending_wa_kas'] = $wa_msg;
    $_SESSION['success'] = "Data kas minggu ke-{$minggu} telah berhasil disimpan!";

    header("Location: ../views/manajemen_kas.php?minggu_ke=$minggu&success=1&tanya_wa=1");
    exit;
}

?>
<!DOCTYPE html>
<html lang="en">

<head>

    <title>Kas Mingguan - SIMAWA PTIK 2024</title>
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

    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>

<body class="bg-slate-100 dark:bg-slate-950 transition-colors">
    <?php include '../includes/menu_db.php'; ?>

    <main class="ml-0 lg:ml-48 pt-20 p-6 flex flex-col min-h-screen">
        <div class="bg-white dark:bg-slate-900/50 rounded-xl border border-slate-200 dark:border-slate-800 p-6 flex-grow w-full shadow-sm">
            <!-- Modified this section for better mobile responsiveness -->
            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-6">
                <h3 class="text-xl font-bold text-slate-800 dark:text-slate-100">Bayar Kas</h3>
                <div class="flex flex-col sm:flex-row gap-4 w-full sm:w-auto">
                    <form method="GET" class="flex flex-col sm:flex-row gap-4 w-full sm:w-auto">
                        <a href="tambah_kas_individu.php" class="bg-indigo-600 dark:bg-indigo-700 text-white px-4 py-2 rounded-lg text-xs hover:bg-indigo-700 dark:hover:bg-indigo-600 transition-colors shadow-sm text-center font-semibold">Input Individu</a>
                        <a href="../views/manajemen_kas.php?minggu_ke=<?= $_GET['minggu_ke'] ?? '' ?>" class="bg-slate-600 dark:bg-slate-700 text-white px-4 py-2 rounded-lg text-xs hover:bg-slate-700 dark:hover:bg-slate-600 transition-colors shadow-sm text-center font-semibold">Kembali</a>
                    </form>
                </div>

            </div>
            <form method="GET" class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
                <select name="minggu_ke" required onchange="this.form.submit()" class="bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-900 dark:text-slate-200 p-2 rounded-lg text-xs focus:ring-1 focus:ring-indigo-500 transition-colors">
                    <option value="">-- Pilih Minggu --</option>
                    <?php for ($i = 1; $i <= 16; $i++): ?>
                        <option value="<?= $i ?>" <?= ($_GET['minggu_ke'] ?? '') == $i ? 'selected' : '' ?>>Minggu <?= $i ?></option>
                    <?php endfor; ?>
                </select>

                <label class="sr-only">Filter</label>
                <select name="filter" onchange="this.form.submit()" class="bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-900 dark:text-slate-200 p-2 rounded-lg text-xs focus:ring-1 focus:ring-indigo-500 transition-colors">
                    <option value="all" <?= ($_GET['filter'] ?? 'all') == 'all' ? 'selected' : '' ?>>Semua</option>
                    <option value="Lunas" <?= ($_GET['filter'] ?? '') == 'Lunas' ? 'selected' : '' ?>>Lunas</option>
                    <option value="Belum" <?= ($_GET['filter'] ?? '') == 'Belum' ? 'selected' : '' ?>>Belum</option>
                </select>
            </form>

            <!-- filter links removed; filter dropdown will be next to save button -->

            <?php if (isset($_GET['minggu_ke'])): ?>
                <?php $minggu = $_GET['minggu_ke']; ?>




                <!-- Rest of the content remains the same -->
                <form method="POST">
                    <div class="overflow-x-auto rounded-xl border border-slate-200 dark:border-slate-800 shadow-sm transition-all">
                        <input type="hidden" name="minggu_ke" value="<?= $minggu ?>">
                        <table class="min-w-full divide-y divide-slate-200 dark:divide-slate-800">
                            <thead class="bg-indigo-900 dark:bg-indigo-950">
                                <tr>
                                    <th class="px-3 py-3 text-left text-xs font-bold text-white uppercase tracking-wider">No</th>
                                    <th class="hidden md:table-cell px-3 py-3 text-left text-xs font-bold text-white uppercase tracking-wider">NIM</th>
                                    <th class="px-3 py-3 text-left text-xs font-bold text-white uppercase tracking-wider">Nama Lengkap</th>
                                    <th class="px-3 py-3 text-left text-xs font-bold text-white uppercase tracking-wider">Status</th>
                                    <th class="px-3 py-3 text-left text-xs font-bold text-white uppercase tracking-wider">Tanggal Bayar</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white dark:bg-slate-900/50 divide-y divide-slate-200 dark:divide-slate-800">
                                <?php
                                $no = 1;
                                $id = 1;
                                $minggu = $_GET['minggu_ke'];

                                $filter = $_GET['filter'] ?? 'all';
                                $filter = in_array($filter, ['all', 'Lunas', 'Belum']) ? $filter : 'all';

                                $where_mhs_tbl = getKelasWhereClause('m', true);
                                $where_k_tbl = getKelasWhereClause('k', true);
                                $where_combined = "WHERE 1=1 $where_mhs_tbl";
                                if ($filter === 'Lunas') {
                                    $where_combined .= " AND k.status = 'Lunas'";
                                } elseif ($filter === 'Belum') {
                                    $where_combined .= " AND (k.status IS NULL OR k.status != 'Lunas')";
                                }

                                $mhs = mysqli_query($koneksi, "
    SELECT
        m.id AS mahasiswa_id,
        m.nim,
        m.nama_lengkap,
        k.minggu_ke,
        k.status,
        k.tanggal_bayar
    FROM mahasiswa m
    LEFT JOIN kas k ON k.mahasiswa_id = m.id AND k.minggu_ke = '$minggu' AND k.periode_id = '$periode_id' $where_k_tbl
    $where_combined
    ORDER BY m.nim ASC
    ");
                                while ($row = mysqli_fetch_assoc($mhs)) : ?>
                                    <tr class="<?= ($id++ % 2 == 0) ? 'bg-slate-50 dark:bg-slate-800/30' : 'bg-white dark:bg-slate-900/20' ?> hover:bg-indigo-50 dark:hover:bg-indigo-900/40 transition-colors group">
                                        <td class="px-3 py-3 whitespace-nowrap text-xs text-slate-700 dark:text-slate-300"><?= $no++; ?></td>
                                        <td class="hidden md:table-cell px-3 py-3 whitespace-nowrap text-xs text-slate-700 dark:text-slate-300"><?= $row['nim']; ?></td>
                                        <td class="px-3 py-3 text-xs font-medium text-slate-700 dark:text-slate-300 group-hover:text-indigo-700 dark:group-hover:text-indigo-400 transition-colors"><?= $row['nama_lengkap']; ?></td>
                                        <td class="px-3 py-3 whitespace-nowrap text-xs">
                                            <?php $status_current = $row['status'] ?? 'Belum'; ?>
                                            <select name="status[<?= $row['mahasiswa_id'] ?>]"
                                                class="bg-white dark:bg-slate-800 border-slate-300 dark:border-slate-700 text-slate-900 dark:text-slate-200 p-1 rounded text-xs focus:ring-1 focus:ring-indigo-500 transition-colors status-select" data-mhs-id="<?= $row['mahasiswa_id'] ?>">
                                                <option value="Lunas" <?= $status_current == 'Lunas' ? 'selected' : '' ?>>Lunas</option>
                                                <option value="Belum" <?= $status_current == 'Belum' ? 'selected' : '' ?>>Belum</option>
                                            </select>
                                        </td>
                                        <td class="px-3 py-3 whitespace-nowrap text-xs">
                                            <?php $tanggal_current = $row['tanggal_bayar'] ?? ''; ?>
                                            <input type="date" name="tanggal[<?= $row['mahasiswa_id'] ?>]"
                                                id="tanggal_<?= $row['mahasiswa_id'] ?>"
                                                value="<?= htmlspecialchars($tanggal_current) ?>"
                                                class="bg-white dark:bg-slate-800 border-slate-300 dark:border-slate-700 text-slate-900 dark:text-slate-200 p-1 rounded text-xs date-input focus:ring-1 focus:ring-indigo-500 transition-colors disabled:opacity-50 dark:disabled:bg-slate-900/50"
                                                <?= ($status_current == 'Belum') ? 'disabled' : '' ?>>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>

                        </table>
                        <div class="mt-8 pt-8 border-t border-slate-200 dark:border-slate-800 flex flex-col items-center">
                            <p class="text-[10px] text-slate-500 dark:text-slate-500 mb-6 italic font-medium leading-relaxed">Pastikan status pembayaran dan tanggal bayar sudah benar.</p>
                            <button type="button" onclick="confirmSaveKas()" class="bg-indigo-900 dark:bg-indigo-800 hover:bg-indigo-800 dark:hover:bg-indigo-700 text-white rounded-xl px-10 mb-5 py-3 text-xs font-bold shadow-lg shadow-indigo-900/20 dark:shadow-indigo-950/40 transition-all active:scale-95 flex items-center gap-2">
                                <i class="fa fa-save"></i> Simpan Kas
                            </button>
                        </div>
                </form>
        </div>
    <?php endif; ?>
    </div>
    <?php include '../includes/footer_db.php'; ?>
    </main>
    <?php include '../includes/script_db.php'; ?>
    <script>
        // no-op: filter handled by GET form onchange submit

        // Handle Status vs Date Automation
        document.querySelectorAll('.status-select').forEach(select => {
            select.addEventListener('change', function() {
                const mhsId = this.getAttribute('data-mhs-id');
                const dateInput = document.getElementById('tanggal_' + mhsId);

                if (this.value === 'Lunas') {
                    dateInput.disabled = false;
                    // Hanya set tanggal hari ini jika input masih kosong
                    if (!dateInput.value) {
                        const today = ScriptGetToday();
                        dateInput.value = today;
                    }
                } else {
                    dateInput.value = '';
                    dateInput.disabled = true;
                }
            });
        });

        function ScriptGetToday() {
            const now = new Date();
            const year = now.getFullYear();
            const month = String(now.getMonth() + 1).padStart(2, '0');
            const day = String(now.getDate()).padStart(2, '0');
            return `${year}-${month}-${day}`;
        }

        // SweetAlert for Saving Kas
        function confirmSaveKas() {
            Swal.fire({
                title: 'Simpan Data Kas?',
                text: "Pastikan status pembayaran dan tanggal sudah benar.",
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#3730a3',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Ya, Simpan!',
                cancelButtonText: 'Batal'
            }).then((result) => {
                if (result.isConfirmed) {
                    // Create hidden input to satisfy the PHP isset($_POST['simpan_kas'])
                    const form = document.querySelector('form[method="POST"]');
                    const hiddenInput = document.createElement('input');
                    hiddenInput.type = 'hidden';
                    hiddenInput.name = 'simpan_kas';
                    hiddenInput.value = '1';
                    form.appendChild(hiddenInput);
                    form.submit();
                }
            });
        }
    </script>
</body>

</html>