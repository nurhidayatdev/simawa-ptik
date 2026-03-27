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

/* =====================
   AMBIL PERIODE AKTIF
   ===================== */
$q_periode = mysqli_query($koneksi, "SELECT id, semester_ke, tahun_periode, semester_nama FROM periode WHERE status = 'aktif' LIMIT 1");
$periode = mysqli_fetch_assoc($q_periode);
$periode_id = $periode['id'];
$semester_aktif = (int)$periode['semester_ke'];
$periode_info = $periode['tahun_periode'] . ' ' . $periode['semester_nama'];
/* =====================
   AMBIL MAHASISWA_ID
   ===================== */
$stmt = mysqli_prepare(
    $koneksi,
    "SELECT id FROM mahasiswa WHERE user_id = ? LIMIT 1"
);
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$mahasiswa = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
$mahasiswa_id = $mahasiswa['id'];

/* =====================
   AMBIL STATUS VALIDASI
   ===================== */
// Ensure table exists (safeguard)
mysqli_query($koneksi, "CREATE TABLE IF NOT EXISTS krs_temp (
    id INT AUTO_INCREMENT PRIMARY KEY,
    mahasiswa_id INT NOT NULL,
    matkul_id INT NOT NULL,
    periode_id INT NOT NULL,
    timestamp DATETIME DEFAULT CURRENT_TIMESTAMP,
    KEY idx_mhs_per (mahasiswa_id, periode_id)
)");
mysqli_query($koneksi, "CREATE TABLE IF NOT EXISTS krs_validasi (
    id INT AUTO_INCREMENT PRIMARY KEY,
    mahasiswa_id INT NOT NULL,
    periode_id INT NOT NULL,
    status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
    tanggal_validasi DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_validasi (mahasiswa_id, periode_id)
)");

$q_val = mysqli_query($koneksi, "SELECT status FROM krs_validasi WHERE mahasiswa_id = $mahasiswa_id AND periode_id = $periode_id");
$val_data = mysqli_fetch_assoc($q_val);
$status_validasi = $val_data['status'] ?? null; // null means not submitted yet

/* =====================
   SIMPAN KRS
   ===================== */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $matkul = $_POST['matkul'] ?? [];

    // Validasi SKS Server-side
    $total_sks_post = 0;
    if (!empty($matkul)) {
        $ids = implode(',', array_map('intval', $matkul));
        $res_sks = mysqli_query($koneksi, "SELECT SUM(sks) as total FROM mata_kuliah WHERE id IN ($ids)");
        $data_sks = mysqli_fetch_assoc($res_sks);
        $total_sks_post = (int)$data_sks['total'];
    }

    if ($total_sks_post > 24) {
        header("Location: edit_krs.php?error=sks_limit");
        exit;
    }

    // hapus KRS TEMP lama (Draft)
    mysqli_query(
        $koneksi,
        "DELETE FROM krs_temp WHERE mahasiswa_id = $mahasiswa_id AND periode_id = $periode_id"
    );

    // insert KRS TEMP baru
    $stmt = mysqli_prepare(
        $koneksi,
        "INSERT INTO krs_temp (mahasiswa_id, matkul_id, periode_id) VALUES (?, ?, ?)"
    );

    foreach ($matkul as $matkul_id) {
        mysqli_stmt_bind_param($stmt, "iii", $mahasiswa_id, $matkul_id, $periode_id);
        mysqli_stmt_execute($stmt);
    }

    // Update Status Validasi -> Pending
    $stmt_val = mysqli_prepare($koneksi, "INSERT INTO krs_validasi (mahasiswa_id, periode_id, status) VALUES (?, ?, 'pending') ON DUPLICATE KEY UPDATE status = 'pending'");
    mysqli_stmt_bind_param($stmt_val, "ii", $mahasiswa_id, $periode_id);
    mysqli_stmt_execute($stmt_val);

    header("Location: edit_krs.php?sukses=1");
    exit;
}

/* =====================
   AMBIL MATKUL PTIK
   ===================== */
$res_matkul = mysqli_query($koneksi, "SELECT * FROM mata_kuliah ORDER BY semester ASC, kode_matkul ASC");

$matkul_rows = [];
while ($r = mysqli_fetch_assoc($res_matkul)) {
    $matkul_rows[] = $r;
}

/* =====================
   MATKUL YANG SUDAH DIAMBIL
   ===================== */
// Cek di krs_temp dulu
$krs = mysqli_query($koneksi, "
    SELECT matkul_id FROM krs_temp
    WHERE mahasiswa_id = $mahasiswa_id
    AND periode_id = $periode_id
");

// Kalau temp kosong, cek krs (active), KECUALI status rejected (rejected means draft needs fix, so might be in temp, but if user cleared it, fall back to active?)
// Actually, if krs_temp has rows, use it. If not, use krs.
$diambil = [];
if (mysqli_num_rows($krs) > 0) {
    while ($row = mysqli_fetch_assoc($krs)) {
        $diambil[] = $row['matkul_id'];
    }
} else {
    // Fallback to active KRS
    $krs_active = mysqli_query($koneksi, "
        SELECT matkul_id FROM krs
        WHERE mahasiswa_id = $mahasiswa_id
        AND periode_id = $periode_id
    ");
    while ($row = mysqli_fetch_assoc($krs_active)) {
        $diambil[] = $row['matkul_id'];
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>

    <title>Kartu Rencana Studi - PTIK C 2024</title>
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

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>

<body class="bg-slate-100 dark:bg-slate-950 transition-colors">
    <?php include '../includes/menu_db.php'; ?>

    <main class="ml-0 lg:ml-48 pt-20 p-6 flex flex-col min-h-screen">
        <div class="bg-white dark:bg-slate-900/50 rounded-xl border border-slate-200 dark:border-slate-800 p-6 flex-grow w-full shadow-sm transition-all">

            <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-6">
                <div class="flex items-center gap-4">
                    <div>
                        <h3 class="text-2xl font-bold text-slate-800 dark:text-slate-100">Kartu Rencana Studi (KRS)</h3>
                        <p class="text-xs text-slate-500 dark:text-slate-400 font-medium mt-1">Periode Aktif: <span class="text-indigo-600 dark:text-indigo-400"><?= htmlspecialchars($periode_info) ?></span></p>
                    </div>
                    <button type="button" onclick="showKrsRules()" class="w-8 h-8 rounded-full bg-slate-100 dark:bg-slate-800 text-slate-500 dark:text-slate-400 hover:bg-indigo-100 dark:hover:bg-indigo-900/50 hover:text-indigo-600 dark:hover:text-indigo-400 transition-colors flex items-center justify-center shadow-sm border border-slate-200 dark:border-slate-700" title="Informasi Aturan">
                        <i class="fas fa-info-circle"></i>
                    </button>
                </div>
                <div class="bg-indigo-50 dark:bg-indigo-950/30 border border-indigo-200 dark:border-indigo-900/50 rounded-xl px-4 py-2 flex items-center gap-3">
                    <div class="w-10 h-10 rounded-full bg-indigo-600 dark:bg-indigo-700 flex items-center justify-center text-white shadow-lg shadow-indigo-200 dark:shadow-indigo-950/20">
                        <i class="fas fa-book-open"></i>
                    </div>
                    <div>
                        <p class="text-[10px] text-indigo-600 dark:text-indigo-400 font-bold uppercase tracking-wider">Total SKS Terpilih</p>
                        <p class="text-lg font-black text-indigo-950 dark:text-indigo-100"><span id="total-sks-display">0</span> <span class="text-xs font-normal text-slate-500 dark:text-slate-400">/ 24 SKS</span></p>
                    </div>
                </div>
            </div>

            <!-- Success logic handled by SweetAlert -->

            <?php if ($status_validasi === 'approved'): ?>
                <div class="bg-green-50/50 dark:bg-green-900/20 border border-green-200 dark:border-green-900/50 text-green-800 dark:text-green-300 px-4 py-3 rounded-xl mb-6 flex items-start shadow-sm transition-all">
                    <i class="fas fa-check-circle mt-1 mr-3"></i>
                    <div>
                        <p class="font-bold text-sm">Approved</p>
                        <p class="text-xs leading-relaxed">KRS Anda telah <strong>disetujui</strong>. Jika Anda melakukan perubahan dan menyimpannya kembali, status akan berubah menjadi <strong>Menunggu Persetujuan</strong>.</p>
                    </div>
                </div>
            <?php elseif ($status_validasi === 'pending'): ?>
                <div class="bg-yellow-50/50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-900/50 text-yellow-800 dark:text-yellow-300 px-4 py-3 rounded-xl mb-6 flex items-start shadow-sm transition-all">
                    <i class="fas fa-clock mt-1 mr-3"></i>
                    <div>
                        <p class="font-bold text-sm">Menunggu Persetujuan</p>
                        <p class="text-xs leading-relaxed">Draft KRS Anda sedang menunggu validasi oleh Admin. Anda masih dapat mengubahnya selama belum disetujui.</p>
                    </div>
                </div>
            <?php elseif ($status_validasi === 'rejected'): ?>
                <div class="bg-red-50/50 dark:bg-red-900/20 border border-red-200 dark:border-red-900/50 text-red-800 dark:text-red-300 px-4 py-3 rounded-xl mb-6 flex items-start shadow-sm transition-all">
                    <i class="fas fa-times-circle mt-1 mr-3"></i>
                    <div>
                        <p class="font-bold text-sm">Ditolak</p>
                        <p class="text-xs leading-relaxed">KRS Anda ditolak. Silakan perbaiki pilihan mata kuliah Anda dan simpan kembali.</p>
                    </div>
                </div>
            <?php else: ?>
                <div class="bg-blue-50/50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-900/50 text-blue-800 dark:text-blue-300 px-4 py-3 rounded-xl mb-6 flex items-start shadow-sm transition-all">
                    <i class="fas fa-info-circle mt-1 mr-3"></i>
                    <div>
                        <p class="font-bold text-sm">Silakan Isi KRS</p>
                        <p class="text-xs leading-relaxed">Pilih mata kuliah dan klik Simpan. Data akan dikirim untuk divalidasi.</p>
                    </div>
                </div>
            <?php endif; ?>

            <form method="POST">

                <?php for ($s = $semester_aktif; $s <= $semester_aktif; $s++): ?>
                    <div class="flex justify-between items-end mt-4 mb-2">
                        <h4 class="text-lg font-semibold text-slate-800 dark:text-slate-100">Semester <?= $s ?></h4>
                        <button type="button" onclick="toggleSelectAll()" class="text-xs bg-indigo-50 dark:bg-indigo-900/30 text-indigo-700 dark:text-indigo-400 px-3 py-1.5 rounded-lg border border-indigo-200 dark:border-indigo-800 hover:bg-indigo-100 dark:hover:bg-indigo-900/50 transition-colors font-semibold flex items-center shadow-sm">
                            <i class="fas fa-check-double mr-2"></i> Pilih Semua
                        </button>
                    </div>
                    <div class="overflow-x-auto rounded-xl border border-slate-200 dark:border-slate-800 shadow-sm">
                        <table class="min-w-full divide-y divide-slate-200 dark:divide-slate-800">
                            <thead class="bg-indigo-900 dark:bg-indigo-950">
                                <tr>
                                    <th class="px-3 py-3 text-center text-xs font-bold text-white uppercase tracking-wider">Ambil</th>
                                    <th class="px-3 py-3 text-left text-xs font-bold text-white uppercase tracking-wider">Kode</th>
                                    <th class="px-3 py-3 text-left text-xs font-bold text-white uppercase tracking-wider">Nama Mata Kuliah</th>
                                    <th class="px-3 py-3 text-center text-xs font-bold text-white uppercase tracking-wider">SKS</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white dark:bg-slate-900/50 divide-y divide-slate-200 dark:divide-slate-800">
                                <?php
                                $found = false;
                                foreach ($matkul_rows as $m) {
                                    if ((int)$m['semester'] !== $s) continue;
                                    $found = true;
                                ?>
                                    <tr class="<?= ($id++ % 2 == 0) ? 'bg-slate-50 dark:bg-slate-800/30' : 'bg-white dark:bg-slate-900/20' ?> hover:bg-indigo-50 dark:hover:bg-indigo-900/40 transition-colors group">
                                        <td class="px-3 py-3 whitespace-nowrap text-xs text-slate-800 dark:text-slate-200 text-center">
                                            <input type="checkbox" name="matkul[]" value="<?= $m['id'] ?>" data-sks="<?= $m['sks'] ?>" class="sks-checkbox rounded border-slate-300 dark:border-slate-700 text-indigo-600 dark:text-indigo-500 focus:ring-indigo-500 dark:focus:ring-indigo-600 dark:bg-slate-800" <?= in_array($m['id'], $diambil) ? 'checked' : '' ?>>
                                        </td>
                                        <td class="px-3 py-3 whitespace-nowrap text-xs font-bold text-slate-700 dark:text-slate-300 group-hover:text-indigo-700 dark:group-hover:text-indigo-400 transition-colors"><?= htmlspecialchars($m['kode_matkul']) ?></td>
                                        <td class="px-3 py-3 text-xs text-slate-700 dark:text-slate-300 group-hover:text-indigo-700 dark:group-hover:text-indigo-400 transition-colors font-medium"><?= htmlspecialchars($m['nama_matkul']) ?></td>
                                        <td class="px-3 py-3 whitespace-nowrap text-xs text-slate-700 dark:text-slate-300 text-center"><?= htmlspecialchars($m['sks']) ?></td>
                                    </tr>
                                <?php } ?>
                                <?php if (!$found): ?>
                                    <tr>
                                        <td class="px-3 py-2 whitespace-nowrap text-xs text-slate-800" colspan="4">Tidak ada mata kuliah untuk semester ini.</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endfor; ?>

                <div class="mt-8 pt-8 border-t border-slate-200 dark:border-slate-800 flex flex-col items-center">
                    <p class="text-[10px] text-slate-500 dark:text-slate-500 mb-6 italic">Pastikan mata kuliah yang dipilih sudah sesuai sebelum melakukan penyimpanan.</p>
                    <button type="button" onclick="confirmSaveKRS()"
                        class="px-10 py-3 bg-indigo-800 dark:bg-indigo-700 text-white rounded-xl hover:bg-indigo-950 dark:hover:bg-indigo-600 text-xs font-bold shadow-lg shadow-indigo-900/20 dark:shadow-indigo-950/40 transition-all active:scale-95 flex items-center gap-2">
                        <i class="fa fa-save"></i> Simpan KRS
                    </button>
                </div>
            </form>

        </div>
        <?php include '../includes/footer_db.php'; ?>
    </main>
    <?php include '../includes/script_db.php'; ?>
    <script>
        // Reusable function for KRS rules
        function showKrsRules() {
            const isDark = document.documentElement.classList.contains('dark');
            Swal.fire({
                title: `<span class="${isDark ? 'text-indigo-300' : 'text-indigo-950'} font-bold">Aturan Pengisian KRS</span>`,
                html: `
                    <div class="text-left text-xs space-y-3 p-2">
                        <div class="flex gap-3">
                            <div class="mt-1"><i class="fas fa-check-circle text-indigo-500"></i></div>
                            <p class="${isDark ? 'text-slate-300' : 'text-slate-700'}">Maksimal beban studi yang dapat diambil adalah <strong>24 SKS</strong>.</p>
                        </div>
                        <div class="flex gap-3">
                            <div class="mt-1"><i class="fas fa-check-circle text-indigo-500"></i></div>
                            <p class="${isDark ? 'text-slate-300' : 'text-slate-700'}">Hanya mengambil mata kuliah yang <strong>diprogramkan bersama kelas PTIK C 24</strong>.</p>
                        </div>
                        <div class="flex gap-3">
                            <div class="mt-1"><i class="fas fa-exclamation-triangle text-amber-500"></i></div>
                            <p class="${isDark ? 'text-slate-300' : 'text-slate-700'}">Dilarang mengambil mata kuliah dari kelas lain.</p>
                        </div>
                        <div class="${isDark ? 'bg-indigo-950/40 border-indigo-800' : 'bg-indigo-50 border-indigo-100'} p-3 rounded-lg border mt-4">
                            <p class="text-[10px] ${isDark ? 'text-indigo-200' : 'text-indigo-900'} leading-relaxed italic">
                                *Sistem KRS ini digunakan secara otomatis sebagai filter pada sistem <strong>Absensi, Pembagian Kelompok</strong>, dan fitur akademik lainnya di dashboard ini.
                            </p>
                        </div>
                    </div>
                `,
                icon: 'info',
                confirmButtonText: 'Saya Mengerti',
                confirmButtonColor: '#3730a3',
                background: isDark ? '#0f172a' : '#ffffff',
                color: isDark ? '#f1f5f9' : '#0f172a',
                allowOutsideClick: false
            });
        }

        // Show Rules Popup Automatically on Entry (Once Only)
        document.addEventListener('DOMContentLoaded', () => {
            const rulesSeen = localStorage.getItem('krs_rules_seen');
            if (!rulesSeen) {
                showKrsRules();
                localStorage.setItem('krs_rules_seen', 'true');
            }
        });



        // SKS Counter Logic
        const sksCheckboxes = document.querySelectorAll('.sks-checkbox');
        const totalSksDisplay = document.getElementById('total-sks-display');

        function updateTotalSks() {
            let total = 0;
            sksCheckboxes.forEach(cb => {
                if (cb.checked) {
                    total += parseInt(cb.dataset.sks);
                }
            });
            totalSksDisplay.innerText = total;

            if (total > 24) {
                totalSksDisplay.classList.add('text-red-600');
                totalSksDisplay.parentElement.classList.add('animate-bounce');
            } else {
                totalSksDisplay.classList.remove('text-red-600');
                totalSksDisplay.parentElement.classList.remove('animate-bounce');
            }
        }

        sksCheckboxes.forEach(cb => {
            cb.addEventListener('change', () => {
                updateTotalSks();

                // Optional: Prevent checking if it exceeds 24
                const currentTotal = parseInt(totalSksDisplay.innerText);
                if (currentTotal > 24 && cb.checked) {
                    // We let them check but totalSksDisplay will show error
                }
            });
        });

        // Initial update
        updateTotalSks();

        // SweetAlert for Saving KRS
        function confirmSaveKRS() {
            const currentTotal = parseInt(totalSksDisplay.innerText);

            if (currentTotal > 24) {
                const isDark = document.documentElement.classList.contains('dark');
                Swal.fire({
                    icon: 'error',
                    title: 'Batas SKS Terlampaui',
                    text: 'Total SKS yang Anda pilih (' + currentTotal + ') melebihi batas maksimal 24 SKS.',
                    confirmButtonColor: '#3730a3',
                    background: isDark ? '#0f172a' : '#ffffff',
                    color: isDark ? '#f1f5f9' : '#0f172a'
                });
                return;
            }

            if (currentTotal === 0) {
                const isDark = document.documentElement.classList.contains('dark');
                Swal.fire({
                    icon: 'warning',
                    title: 'Belum Ada Matkul',
                    text: 'Silakan pilih minimal satu mata kuliah.',
                    confirmButtonColor: '#3730a3',
                    background: isDark ? '#0f172a' : '#ffffff',
                    color: isDark ? '#f1f5f9' : '#0f172a'
                });
                return;
            }

            const isDark = document.documentElement.classList.contains('dark');
            Swal.fire({
                title: 'Simpan KRS?',
                text: "Total SKS terpilih: " + currentTotal + ". Pastikan pilihan Anda sudah benar.",
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#3730a3',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Ya, Simpan!',
                cancelButtonText: 'Batal',
                background: isDark ? '#0f172a' : '#ffffff',
                color: isDark ? '#f1f5f9' : '#0f172a'
            }).then((result) => {
                if (result.isConfirmed) {
                    document.querySelector('form').submit();
                }
            });
        }

        // Toggle Select All
        function toggleSelectAll() {
            const allCbs = document.querySelectorAll('.sks-checkbox');
            const allChecked = Array.from(allCbs).every(cb => cb.checked);

            allCbs.forEach(cb => {
                cb.checked = !allChecked;
            });
            updateTotalSks();
        }

        // Show Success Alert
        <?php if (isset($_GET['sukses'])): ?>
            (() => {
                const isDark = document.documentElement.classList.contains('dark');
                Swal.fire({
                    icon: 'success',
                    title: 'Berhasil Disimpan!',
                    text: 'Draft KRS Anda berhasil disimpan dan menunggu persetujuan.',
                    confirmButtonColor: '#3730a3',
                    background: isDark ? '#0f172a' : '#ffffff',
                    color: isDark ? '#f1f5f9' : '#0f172a'
                }).then(() => {
                    window.location.href = '../views/ksm.php';
                });
            })();
        <?php endif; ?>

        // Show Error Alert for SKS Limit
        <?php if (isset($_GET['error']) && $_GET['error'] === 'sks_limit'): ?>
            (() => {
                const isDark = document.documentElement.classList.contains('dark');
                Swal.fire({
                    icon: 'error',
                    title: 'Gagal Menyimpan!',
                    text: 'Total SKS melebihi batas maksimal 24 SKS.',
                    confirmButtonColor: '#3730a3',
                    background: isDark ? '#0f172a' : '#ffffff',
                    color: isDark ? '#f1f5f9' : '#0f172a'
                });
            })();
        <?php endif; ?>
    </script>
</body>

</html>