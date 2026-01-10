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
$q_periode = mysqli_query($koneksi, "SELECT id FROM periode WHERE status = 'aktif' LIMIT 1");
$periode = mysqli_fetch_assoc($q_periode);
$periode_id = $periode['id'];
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

    // hapus KRS lama
    mysqli_query(
        $koneksi,
        "DELETE FROM krs WHERE mahasiswa_id = $mahasiswa_id AND periode_id = $periode_id"
    );

    // insert KRS baru
    $stmt = mysqli_prepare(
        $koneksi,
        "INSERT INTO krs (mahasiswa_id, matkul_id, periode_id) VALUES (?, ?, ?)"
    );

    foreach ($matkul as $matkul_id) {
        mysqli_stmt_bind_param($stmt, "iii", $mahasiswa_id, $matkul_id, $periode_id);
        mysqli_stmt_execute($stmt);
    }

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
$krs = mysqli_query($koneksi, "
    SELECT matkul_id FROM krs
    WHERE mahasiswa_id = $mahasiswa_id
    AND periode_id = $periode_id
");

$diambil = [];
while ($row = mysqli_fetch_assoc($krs)) {
    $diambil[] = $row['matkul_id'];
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

<body class="bg-slate-100">
    <?php include '../includes/menu_db.php'; ?>

    <main class="ml-0 lg:ml-48 pt-20 p-6 flex flex-col min-h-screen">
        <div class="bg-white rounded-lg border border-slate-200 p-6 flex-grow w-full">

            <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-6">
                <div class="flex items-center gap-4">
                    <h3 class="text-2xl font-bold">Kartu Rencana Studi (KRS)</h3>
                    <button type="button" onclick="showKrsRules()" class="w-8 h-8 rounded-full bg-slate-100 text-slate-500 hover:bg-indigo-100 hover:text-indigo-600 transition-colors flex items-center justify-center shadow-sm border border-slate-200" title="Informasi Aturan">
                        <i class="fas fa-info-circle"></i>
                    </button>
                </div>
                <div class="bg-indigo-50 border border-indigo-200 rounded-xl px-4 py-2 flex items-center gap-3">
                    <div class="w-10 h-10 rounded-full bg-indigo-600 flex items-center justify-center text-white shadow-lg">
                        <i class="fas fa-book-open"></i>
                    </div>
                    <div>
                        <p class="text-[10px] text-indigo-600 font-bold uppercase tracking-wider">Total SKS Terpilih</p>
                        <p class="text-lg font-black text-indigo-950"><span id="total-sks-display">0</span> <span class="text-xs font-normal text-slate-500">/ 24 SKS</span></p>
                    </div>
                </div>
            </div>

            <!-- Success logic handled by SweetAlert -->

            <form method="POST">

                <?php for ($s = 1; $s <= 8; $s++): ?>
                    <h4 class="text-lg font-semibold mt-4 mb-2">Semester <?= $s ?></h4>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-slate-200">
                            <thead class="bg-indigo-900">
                                <tr>
                                    <th class="px-3 py-3 text-center text-xs font-bold text-white uppercase tracking-wider">Ambil</th>
                                    <th class="px-3 py-3 text-left text-xs font-bold text-white uppercase tracking-wider">Kode</th>
                                    <th class="px-3 py-3 text-left text-xs font-bold text-white uppercase tracking-wider">Nama Mata Kuliah</th>
                                    <th class="px-3 py-3 text-center text-xs font-bold text-white uppercase tracking-wider">SKS</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-slate-200">
                                <?php
                                $found = false;
                                foreach ($matkul_rows as $m) {
                                    if ((int)$m['semester'] !== $s) continue;
                                    $found = true;
                                ?>
                                    <tr class="<?= ($id++ % 2 == 0) ? 'bg-slate-100' : 'bg-white' ?> hover:bg-indigo-100 transition-colors">
                                        <td class="px-3 py-2 whitespace-nowrap text-xs text-slate-800 text-center">
                                            <input type="checkbox" name="matkul[]" value="<?= $m['id'] ?>" data-sks="<?= $m['sks'] ?>" class="sks-checkbox rounded text-indigo-600 focus:ring-indigo-500" <?= in_array($m['id'], $diambil) ? 'checked' : '' ?>>
                                        </td>
                                        <td class="px-3 py-2 whitespace-nowrap text-xs text-slate-800"><?= htmlspecialchars($m['kode_matkul']) ?></td>
                                        <td class="px-3 py-2 text-xs text-slate-800"><?= htmlspecialchars($m['nama_matkul']) ?></td>
                                        <td class="px-3 py-2 whitespace-nowrap text-xs text-slate-800 text-center"><?= htmlspecialchars($m['sks']) ?></td>
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

                <div class="mt-8 pt-5 border-t border-slate-200 flex flex-col items-center">
                    <p class="text-[10px] text-slate-500 mb-4 italic">Pastikan mata kuliah yang dipilih sudah sesuai sebelum melakukan penyimpanan.</p>
                    <button type="button" onclick="confirmSaveKRS()"
                        class="px-6 py-2.5 bg-indigo-800 text-white rounded-lg hover:bg-indigo-950 text-xs font-semibold shadow-lg shadow-indigo-900/20 transition-all active:scale-95 flex items-center">
                        <i class="fa fa-save mr-2"></i> Simpan KRS
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
            Swal.fire({
                title: '<span class="text-indigo-950">Aturan Pengisian KRS</span>',
                html: `
                    <div class="text-left text-xs space-y-3 p-2">
                        <div class="flex gap-3">
                            <div class="mt-1"><i class="fas fa-check-circle text-indigo-600"></i></div>
                            <p class="text-slate-700">Maksimal beban studi yang dapat diambil adalah <strong>24 SKS</strong>.</p>
                        </div>
                        <div class="flex gap-3">
                            <div class="mt-1"><i class="fas fa-check-circle text-indigo-600"></i></div>
                            <p class="text-slate-700">Hanya mengambil mata kuliah yang <strong>diprogramkan bersama kelas PTIK C 24</strong>.</p>
                        </div>
                        <div class="flex gap-3">
                            <div class="mt-1"><i class="fas fa-exclamation-triangle text-amber-500"></i></div>
                            <p class="text-slate-700">Dilarang mengambil mata kuliah dari kelas lain.</p>
                        </div>
                        <div class="bg-indigo-50 p-3 rounded-lg border border-indigo-100 mt-4">
                            <p class="text-[10px] text-indigo-900 leading-relaxed italic">
                                *Sistem KRS ini digunakan secara otomatis sebagai filter pada sistem <strong>Absensi, Pembagian Kelompok</strong>, dan fitur akademik lainnya di dashboard ini.
                            </p>
                        </div>
                    </div>
                `,
                icon: 'info',
                confirmButtonText: 'Saya Mengerti',
                confirmButtonColor: '#3730a3',
                background: '#ffffff',
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
                Swal.fire({
                    icon: 'error',
                    title: 'Batas SKS Terlampaui',
                    text: 'Total SKS yang Anda pilih (' + currentTotal + ') melebihi batas maksimal 24 SKS.',
                    confirmButtonColor: '#3730a3'
                });
                return;
            }

            if (currentTotal === 0) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Belum Ada Matkul',
                    text: 'Silakan pilih minimal satu mata kuliah.',
                    confirmButtonColor: '#3730a3'
                });
                return;
            }

            Swal.fire({
                title: 'Simpan KRS?',
                text: "Total SKS terpilih: " + currentTotal + ". Pastikan pilihan Anda sudah benar.",
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#3730a3',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Ya, Simpan!',
                cancelButtonText: 'Batal'
            }).then((result) => {
                if (result.isConfirmed) {
                    document.querySelector('form').submit();
                }
            });
        }

        // Show Success Alert
        <?php if (isset($_GET['sukses'])): ?>
            Swal.fire({
                icon: 'success',
                title: 'Berhasil!',
                text: 'Kartu Rencana Studi Anda telah berhasil diperbarui.',
                confirmButtonColor: '#3730a3'
            }).then(() => {
                window.location.href = '../views/profile.php';
            });
        <?php endif; ?>

        // Show Error Alert for SKS Limit
        <?php if (isset($_GET['error']) && $_GET['error'] === 'sks_limit'): ?>
            Swal.fire({
                icon: 'error',
                title: 'Gagal Menyimpan!',
                text: 'Total SKS melebihi batas maksimal 24 SKS.',
                confirmButtonColor: '#3730a3'
            });
        <?php endif; ?>
    </script>
</body>

</html>