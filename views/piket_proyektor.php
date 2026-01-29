<?php
session_start();
include '../koneksi.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../index");
    exit;
}

$nama_lengkap = $_SESSION['nama_lengkap'];
$user_id = $_SESSION['user_id'];
$foto = $_SESSION['foto'];
$role = $_SESSION['role'];

// Allowed roles for admin actions
$admin_roles = ['Administrator Utama', 'Ketua Tingkat', 'Sekretaris'];
$is_admin = in_array($role, $admin_roles);

// Include foto helpers for cache busting
include_once '../functions/foto_helpers.php';

/* =====================
   AMBIL PERIODE AKTIF
   ===================== */
$q_periode = mysqli_query($koneksi, "SELECT * FROM periode WHERE status = 'aktif' LIMIT 1");
$periode = mysqli_fetch_assoc($q_periode);
$periode_id = $periode['id'] ?? 0;
$periode_text = $periode ? $periode['tahun_periode'] . ' - ' . $periode['semester_nama'] : 'Tidak ada periode aktif';

// Filter Minggu
$filter_minggu = isset($_GET['minggu']) ? intval($_GET['minggu']) : 1;
if ($filter_minggu < 1) $filter_minggu = 1;
if ($filter_minggu > 16) $filter_minggu = 16;

// Data Piket
$query = "SELECT p.*, m.nama_lengkap, m.foto, jm.hari, mk.nama_matkul, r.nama_ruangan, jk_start.jam_mulai, jk_end.jam_selesai
          FROM piket_proyektor p
          JOIN mahasiswa m ON p.mahasiswa_id = m.id
          JOIN jadwal_matkul jm ON p.jadwal_matkul_id = jm.id
          JOIN mata_kuliah mk ON jm.matkul_id = mk.id
          LEFT JOIN ruangan r ON jm.ruangan_id = r.id
          LEFT JOIN jam_kuliah jk_start ON jm.jam_mulai_ke = jk_start.jam_ke
          LEFT JOIN jam_kuliah jk_end ON jm.jam_selesai_ke = jk_end.jam_ke
          WHERE p.periode_id = $periode_id AND p.minggu_ke = $filter_minggu
          ORDER BY FIELD(jm.hari, 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu', 'Minggu'), jk_start.jam_mulai, p.jadwal_matkul_id, m.nama_lengkap";

$res_piket = mysqli_query($koneksi, $query);

$piket_data = [];
while ($row = mysqli_fetch_assoc($res_piket)) {
    $jid = $row['jadwal_matkul_id'];
    if (!isset($piket_data[$jid])) {
        $piket_data[$jid] = [
            'matkul' => $row['nama_matkul'],
            'hari' => $row['hari'],
            'jam' => date('H:i', strtotime($row['jam_mulai'])) . ' - ' . date('H:i', strtotime($row['jam_selesai'])),
            'ruangan' => $row['nama_ruangan'] ?? '-',
            'petugas' => []
        ];
    }
    $piket_data[$jid]['petugas'][] = [
        'nama' => $row['nama_lengkap'],
        'foto' => $row['foto']
    ];
}

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <title>Piket Proyektor - PTIK C 2024</title>
    <?php include '../includes/head.php'; ?>
    <style>
        .piket-card {
            transition: all 0.3s ease;
        }

        .piket-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
        }
    </style>
</head>

<body class="bg-slate-100">
    <?php include '../includes/menu_db.php'; ?>

    <main class="ml-0 lg:ml-48 pt-20 p-6 flex flex-col min-h-screen">
        <div class="max-w-6xl mx-auto w-full">

            <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4 mb-6">
                <div>
                    <h2 class="text-2xl font-black text-slate-800">Piket Proyektor</h2>
                    <p class="text-sm text-slate-500">Daftar mahasiswa petugas pengambil proyektor mata kuliah.</p>
                </div>

                <div class="flex items-center gap-2">
                    <form method="GET" class="flex items-center gap-2 bg-white rounded-lg p-1 shadow-sm border border-slate-200">
                        <label class="text-[10px] font-bold text-slate-400 uppercase ml-2">Minggu Ke</label>
                        <select name="minggu" onchange="this.form.submit()" class="bg-slate-50 border-none text-xs font-bold text-indigo-700 rounded focus:ring-0 cursor-pointer">
                            <?php
                            $start_date = "2026-01-19";
                            for ($i = 1; $i <= 16; $i++):
                                $week_start = date('d M', strtotime($start_date . " + " . (($i - 1) * 7) . " days"));
                                $week_end = date('d M Y', strtotime($start_date . " + " . (($i * 7) - 1) . " days"));
                            ?>
                                <option value="<?= $i ?>" <?= $filter_minggu == $i ? 'selected' : '' ?>>
                                    Minggu <?= $i ?> (<?= $week_start ?> - <?= $week_end ?>)
                                </option>
                            <?php endfor; ?>
                        </select>
                    </form>

                    <?php if ($is_admin): ?>
                        <div class="flex items-center gap-2">
                            <form action="reminder" method="POST" id="reminderForm">
                                <input type="hidden" name="action" value="piket">
                                <button type="submit" class="bg-indigo-600 text-white px-4 py-2 rounded-lg hover:bg-indigo-700 text-xs font-bold flex items-center gap-2 shadow-sm transition-all">
                                    <i class="fab fa-telegram-plane"></i>
                                </button>
                            </form>
                            <button onclick="confirmReset()" class="bg-slate-800 text-white px-4 py-2 rounded-lg hover:bg-black text-xs font-bold flex items-center gap-2 shadow-sm transition-all">
                                <i class="fa fa-random"></i>
                            </button>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <?php if (empty($piket_data)): ?>
                <div class="bg-white rounded-2xl p-12 text-center border border-dashed border-slate-300">
                    <div class="w-20 h-20 bg-slate-100 rounded-full flex items-center justify-center mx-auto mb-4">
                        <i class="fa fa-clipboard-list text-3xl text-slate-300"></i>
                    </div>
                    <h3 class="text-lg font-bold text-slate-700">Belum Ada Data Piket</h3>
                    <p class="text-sm text-slate-500 max-w-xs mx-auto mb-6">Administrator belum mengacak daftar piket proyektor untuk periode ini.</p>
                </div>
            <?php else: ?>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
                    <?php
                    $day_map = ['Senin' => 0, 'Selasa' => 1, 'Rabu' => 2, 'Kamis' => 3, 'Jumat' => 4, 'Sabtu' => 5, 'Minggu' => 6];
                    foreach ($piket_data as $jid => $data):
                        $day_idx = $day_map[$data['hari']] ?? 0;
                        $duty_date = date('d M Y', strtotime($start_date . " + " . (($filter_minggu - 1) * 7 + $day_idx) . " days"));
                    ?>
                        <div class="bg-white rounded-2xl border border-slate-200 overflow-hidden piket-card">
                            <div class="bg-indigo-50 px-4 py-3 border-b border-indigo-100">
                                <div class="flex justify-between items-start mb-1">
                                    <span class="text-[9px] font-black text-indigo-600 uppercase tracking-widest"><?= $data['hari'] ?>, <?= $duty_date ?></span>
                                    <span class="text-[9px] bg-white px-2 py-0.5 rounded-full border border-indigo-200 text-indigo-700 font-bold"><?= $data['jam'] ?></span>
                                </div>
                                <h3 class="text-sm font-bold text-slate-800 line-clamp-1" title="<?= htmlspecialchars($data['matkul']) ?>"><?= htmlspecialchars($data['matkul']) ?></h3>
                                <div class="flex items-center gap-1.5 mt-1">
                                    <i class="fa fa-map-marker-alt text-red-400 text-[10px]"></i>
                                    <span class="text-[10px] font-medium text-slate-500"><?= htmlspecialchars($data['ruangan']) ?></span>
                                </div>
                            </div>
                            <div class="p-4">
                                <div class="text-[9px] font-bold text-slate-400 uppercase tracking-wider mb-2">Petugas Piket</div>
                                <div class="space-y-2">
                                    <?php foreach ($data['petugas'] as $petugas): ?>
                                        <div class="flex items-center gap-2">
                                            <div class="h-7 w-7 rounded-full overflow-hidden border border-slate-200 shrink-0 shadow-sm">
                                                <img src="<?= profilePhotoUrl($petugas['foto'], '../') ?>" class="w-full h-full object-cover">
                                            </div>
                                            <span class="text-[11px] font-bold text-slate-700 truncate"><?= htmlspecialchars($petugas['nama']) ?></span>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <div class="mt-12 bg-indigo-900 rounded-2xl p-8 text-white relative overflow-hidden shadow-xl">
                <div class="relative z-10 flex flex-col md:flex-row items-center justify-between gap-6">
                    <div class="text-center md:text-left">
                        <h3 class="text-xl font-black mb-2 flex items-center gap-2 justify-center md:justify-start">
                            <i class="fa fa-info-circle text-indigo-400"></i>
                            Informasi Penting
                        </h3>
                        <p class="text-indigo-200 text-sm max-w-xl">Petugas piket diharapkan mengambil proyektor di Lab/Ruang Sarpras 15 menit sebelum perkuliahan dimulai dan mengembalikannya segera setelah mata kuliah selesai.</p>
                    </div>
                </div>
                <!-- Abstract Background Elements -->
                <div class="absolute -right-20 -bottom-20 w-64 h-64 bg-indigo-800 rounded-full opacity-50"></div>
                <div class="absolute -left-10 -top-10 w-32 h-32 bg-indigo-800 rounded-full opacity-30"></div>
            </div>

            <?php include '../includes/footer_db.php'; ?>
        </div>
    </main>

    <?php include '../includes/script_db.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        function confirmReset() {
            Swal.fire({
                title: 'Acak Ulang Piket?',
                text: "Daftar piket lama akan dihapus dan diganti dengan yang baru untuk 16 minggu ke depan.",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#312e81',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Ya, Acak Sekarang!',
                cancelButtonText: 'Batal'
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = '../actions/tambah_piket_proyektor.php';
                }
            })
        }

        <?php if (isset($_SESSION['success'])): ?>
            Swal.fire({
                icon: 'success',
                title: 'Berhasil!',
                text: '<?= $_SESSION['success'] ?>',
                confirmButtonColor: '#312e81'
            });
            <?php unset($_SESSION['success']); ?>
        <?php endif; ?>

        <?php if (isset($_SESSION['error'])): ?>
            Swal.fire({
                icon: 'error',
                title: 'Gagal!',
                text: '<?= $_SESSION['error'] ?>',
                confirmButtonColor: '#d33'
            });
            <?php unset($_SESSION['error']); ?>
        <?php endif; ?>
    </script>
</body>

</html>