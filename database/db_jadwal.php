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

if ($role === 'Administrator Utama') {
    $_SESSION['error'] = 'Akses ditolak! Administrator Utama tidak mengelola jadwal kuliah kelas.';
    header('Location: ../dashboard_admin.php');
    exit;
}

// AJAX Handler for "Sesuaikan Jadwal" Data Fetching
if (isset($_GET['fetch_adjust_data'])) {
    header('Content-Type: application/json');
    date_default_timezone_set('Asia/Makassar');

    $day_type = $_GET['day'] ?? 'besok';
    $days_map = ['Sunday' => 'Minggu', 'Monday' => 'Senin', 'Tuesday' => 'Selasa', 'Wednesday' => 'Rabu', 'Thursday' => 'Kamis', 'Friday' => 'Jumat', 'Saturday' => 'Sabtu'];
    $offset = ($day_type === 'hari_ini') ? 'now' : '+1 day';
    $target_eng = date('l', strtotime($offset));
    $target_ind = $days_map[$target_eng];

    // 1. Get Schedule
    $q_p = mysqli_query($koneksi, "SELECT id FROM periode WHERE status = 'aktif' LIMIT 1");
    $p_id = mysqli_fetch_assoc($q_p)['id'] ?? 0;

    $query_jadwal = "SELECT jm.id, mk.nama_matkul, 
                     jk_start.jam_mulai, jk_end.jam_selesai,
                     r.nama_ruangan, r.id as ruangan_id
                     FROM jadwal_matkul jm
                     JOIN mata_kuliah mk ON jm.matkul_id = mk.id
                     LEFT JOIN ruangan r ON jm.ruangan_id = r.id
                     LEFT JOIN jam_kuliah jk_start ON jm.jam_mulai_ke = jk_start.jam_ke
                     LEFT JOIN jam_kuliah jk_end ON jm.jam_selesai_ke = jk_end.jam_ke
                     WHERE jm.periode_id = $p_id " . getKelasWhereClause('jm', true) . "AND jm.hari = '$target_ind' " . getKelasWhereClause('jm', true) . "
                     ORDER BY jk_start.jam_mulai ASC";

    $res_jadwal = mysqli_query($koneksi, $query_jadwal);
    $jadwal = [];
    while ($row = mysqli_fetch_assoc($res_jadwal)) {
        $jam_display = date('H.i', strtotime($row['jam_mulai'])) . ' - ' . date('H.i', strtotime($row['jam_selesai']));
        $jadwal[] = [
            'id' => $row['id'],
            'matkul' => $row['nama_matkul'],
            'jam_text' => $jam_display,
            'ruangan_text' => $row['nama_ruangan'] ?? '—',
            'ruangan_id' => $row['ruangan_id'] ?? 0,
            'checked' => true,
            'status' => 'masuk'
        ];
    }

    // 2. Get Matkul that exist in Schedule for active period
    $query_mk = "SELECT DISTINCT mk.id, mk.nama_matkul 
                 FROM jadwal_matkul jm 
                 JOIN mata_kuliah mk ON jm.matkul_id = mk.id 
                 WHERE jm.periode_id = $p_id " . getKelasWhereClause('jm', true) . "
                 ORDER BY mk.nama_matkul ASC";
    $res_mk = mysqli_query($koneksi, $query_mk);
    $opts_mk = [];
    while ($row = mysqli_fetch_assoc($res_mk)) {
        $opts_mk[] = ['id' => $row['id'], 'nama' => $row['nama_matkul']];
    }

    // 2. Get All Jam Kuliah Options
    $res_jam = mysqli_query($koneksi, "SELECT jam_ke, jam_mulai, jam_selesai FROM jam_kuliah ORDER BY jam_ke ASC");
    $opts_jam = [];
    while ($row = mysqli_fetch_assoc($res_jam)) {
        $opts_jam[] = [
            'val' => date('H.i', strtotime($row['jam_mulai'])) . ' - ' . date('H.i', strtotime($row['jam_selesai'])),
            'label' => "Jam ke-{$row['jam_ke']} (" . date('H:i', strtotime($row['jam_mulai'])) . " - " . date('H:i', strtotime($row['jam_selesai'])) . ")"
        ];
    }

    // 3. Get All Ruangan Options
    $res_ruang = mysqli_query($koneksi, "SELECT id, nama_ruangan FROM ruangan ORDER BY nama_ruangan ASC");
    $opts_ruang = [];
    while ($row = mysqli_fetch_assoc($res_ruang)) {
        $opts_ruang[] = [
            'val' => $row['nama_ruangan'],
            'label' => $row['nama_ruangan']
        ];
    }

    echo json_encode([
        'tomorrow' => $target_ind,
        'date' => date('d M Y', strtotime($offset)),
        'jadwal' => $jadwal,
        'opt_jam' => $opts_jam,
        'opt_ruang' => $opts_ruang,
        'opt_mk' => $opts_mk
    ]);
    exit;
}


/* =====================
   AMBIL PERIODE AKTIF
   ===================== */
$q_periode = mysqli_query($koneksi, "SELECT id FROM periode WHERE status = 'aktif' LIMIT 1");
$periode = mysqli_fetch_assoc($q_periode);
$periode_id = $periode['id'];

$search = $_GET['search'] ?? '';
$where_jm_kelas = getKelasWhereClause('jm', true);

if (!empty($search)) {
    $stmt = mysqli_prepare(
        $koneksi,
        "SELECT 
    jm.id, jm.matkul_id, jm.hari, jm.jam_mulai_ke, jm.jam_selesai_ke, 
    jk_start.jam_mulai, jk_end.jam_selesai,
    r.nama_ruangan AS ruangan, mk.nama_matkul, mk.sks,
    dp.nama_dosen AS dosen_pengampu,
    dm.nama_dosen AS dosen_mitra
FROM jadwal_matkul jm
JOIN mata_kuliah mk ON jm.matkul_id = mk.id
LEFT JOIN ruangan r ON jm.ruangan_id = r.id
LEFT JOIN jam_kuliah jk_start ON jm.jam_mulai_ke = jk_start.jam_ke
LEFT JOIN jam_kuliah jk_end ON jm.jam_selesai_ke = jk_end.jam_ke
LEFT JOIN dosen dp ON jm.dosen_pengampu_id = dp.id
LEFT JOIN dosen dm ON jm.dosen_mitra_id = dm.id 
         WHERE (jm.hari LIKE ? 
         OR mk.nama_matkul LIKE ?)
         AND jm.periode_id = ?
         $where_jm_kelas
         ORDER BY jm.hari, jk_start.jam_mulai"
    );

    $like = "%$search%";
    mysqli_stmt_bind_param($stmt, "ssi", $like, $like, $periode_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
} else {
    $result = mysqli_query($koneksi, "SELECT 
    jm.id, jm.matkul_id, jm.hari, jm.jam_mulai_ke, jm.jam_selesai_ke,
    jk_start.jam_mulai, jk_end.jam_selesai,
    r.nama_ruangan AS ruangan, mk.nama_matkul, mk.sks,
    dp.nama_dosen AS dosen_pengampu,
    dm.nama_dosen AS dosen_mitra
FROM jadwal_matkul jm
JOIN mata_kuliah mk ON jm.matkul_id = mk.id
LEFT JOIN ruangan r ON jm.ruangan_id = r.id
LEFT JOIN jam_kuliah jk_start ON jm.jam_mulai_ke = jk_start.jam_ke
LEFT JOIN jam_kuliah jk_end ON jm.jam_selesai_ke = jk_end.jam_ke
LEFT JOIN dosen dp ON jm.dosen_pengampu_id = dp.id
LEFT JOIN dosen dm ON jm.dosen_mitra_id = dm.id
WHERE jm.periode_id = $periode_id $where_jm_kelas
ORDER BY jm.hari, jk_start.jam_mulai");
}

// If AJAX live-search request, return table rows only
if (isset($_GET['ajax']) && $_GET['ajax'] == '1') {
    $no = 1;
    $id = 1;
    while ($row = mysqli_fetch_assoc($result)) {
        $isEven = ($id++ % 2 == 0);
        $rowClass = ($isEven ? 'bg-slate-50 dark:bg-slate-800/30' : 'bg-white dark:bg-slate-900') . ' hover:bg-indigo-50 dark:hover:bg-indigo-900/20 transition-colors';
        echo '<tr class="' . $rowClass . '">';
        echo '<td class="px-3 py-2 whitespace-nowrap text-xs text-slate-800 dark:text-slate-200 font-medium">' . ($no++) . '</td>';
        echo '<td class="px-3 py-2 whitespace-nowrap text-xs text-slate-800 dark:text-slate-200 font-semibold">' . htmlspecialchars($row['hari']) . '</td>';
        echo '<td class="px-3 py-2 whitespace-nowrap text-xs text-slate-600 dark:text-slate-400">' . htmlspecialchars($row['jam_mulai_ke']) . '-' . htmlspecialchars($row['jam_selesai_ke']) . '</td>';
        echo '<td class="px-3 py-2 whitespace-nowrap text-xs text-slate-600 dark:text-slate-400 font-mono">' . date('H.i', strtotime($row['jam_mulai'])) . ' - ' . date('H.i', strtotime($row['jam_selesai'])) . '</td>';
        echo '<td class="px-3 py-2 whitespace-nowrap text-xs text-slate-800 dark:text-slate-200 font-medium">' . htmlspecialchars($row['ruangan']) . '</td>';
        echo '<td class="px-3 py-2 text-xs text-slate-800 dark:text-slate-100 font-bold">' . htmlspecialchars($row['nama_matkul']) . '</td>';
        echo '<td class="px-3 py-2 whitespace-nowrap text-xs text-center"><span class="bg-indigo-50 dark:bg-indigo-900/40 text-indigo-700 dark:text-indigo-300 px-2 py-0.5 rounded text-[10px] font-bold border border-indigo-100 dark:border-indigo-800/50">' . htmlspecialchars($row['sks']) . '</span></td>';
        echo '<td class="px-3 py-2 text-xs text-slate-600 dark:text-slate-400">' . htmlspecialchars($row['dosen_pengampu']) . '</td>';
        echo '<td class="px-3 py-2 text-xs text-slate-600 dark:text-slate-400">' . htmlspecialchars($row['dosen_mitra']) . '</td>';
        if ($role === 'Ketua Tingkat'):
            echo '<td class="px-3 py-2 whitespace-nowrap text-sm text-center">';
            echo '<div class="flex items-center justify-center gap-3">';
            echo '<a href="../actions/edit.php?tabel=jadwal_matkul&id=' . $row['id'] . '" title="Edit" class="text-blue-600 dark:text-blue-400 hover:text-blue-900 dark:hover:text-blue-300 transition-colors"><i class="fa fa-edit"></i></a>';
            echo '<a href="../actions/hapus.php?tabel=jadwal_matkul&id=' . $row['id'] . '" title="Hapus" class="text-red-600 dark:text-red-400 hover:text-red-900 dark:hover:text-red-300 transition-colors"><i class="fa fa-trash"></i></a>';
            echo '</div>';
            echo '</td>';
        endif;
        echo '</tr>';
    }
    exit;
}

?>
<!DOCTYPE html>
<html lang="en">

<head>

    <title>Data Jadwal Kuliah - SIMAWA PTIK 2024</title>
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

<body class="bg-slate-100 dark:bg-slate-950 transition-colors duration-300">
    <?php include '../includes/menu_db.php'; ?>

    <main class="ml-0 lg:ml-48 pt-20 p-6 flex flex-col min-h-screen">
        <div class="bg-white dark:bg-slate-900 rounded-lg border border-slate-200 dark:border-slate-800 p-6 flex-grow w-full shadow-sm">
            <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4 mb-6">
                <div>
                    <h1 class="text-xl font-bold text-slate-800 dark:text-slate-100">Jadwal Mata Kuliah</h1>
                </div>
                <div class="w-full md:w-auto">
                    <form method="GET" class="w-full">
                        <div class="flex flex-row flex-nowrap gap-2 items-center">
                            <div class="relative flex-1 min-w-0">
                                <input
                                    id="searchInput"
                                    type="text"
                                    name="search"
                                    value="<?= htmlspecialchars($_GET['search'] ?? '') ?>"
                                    placeholder="Cari Hari / Mata Kuliah..."
                                    class="w-full pl-9 pr-3 py-2 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 text-xs text-slate-700 dark:text-slate-200 transition-all">
                                <i class="fa fa-search absolute left-3 top-2.5 text-slate-400 dark:text-slate-500 text-xs"></i>
                            </div>

                            <a href="../jadwal" class="bg-indigo-600 hover:bg-indigo-700 text-white p-2.5 rounded-lg transition-all shadow-sm flex items-center justify-center shrink-0" title="Lihat View">
                                    <i class="fa fa-eye text-xs"></i>
                                </a>

                            <?php if ($role === 'Ketua Tingkat'): ?>
                                <button type="button" onclick="confirmJadwal()" class="bg-blue-600 hover:bg-blue-700 text-white p-2.5 rounded-lg transition-all shadow-sm flex items-center justify-center shrink-0" title="Kirim WhatsApp">
                                    <i class="fa fa-paper-plane text-xs"></i>
                                </button>

                                <a href="../actions/tambah.php?tabel=jadwal_matkul" class="bg-indigo-600 hover:bg-indigo-700 text-white p-2.5 rounded-lg transition-all shadow-sm flex items-center justify-center shrink-0" title="Tambah">
                                    <i class="fa fa-plus text-xs"></i>
                                </a>
                            <?php endif; ?>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Rest of the content remains the same -->
            <div class="overflow-x-auto rounded-xl border border-slate-200 dark:border-slate-800">
                <table class="min-w-full divide-y divide-slate-200 dark:divide-slate-800">
                    <thead class="bg-indigo-900 dark:bg-slate-800">
                        <tr>
                            <th class="px-3 py-3 text-left text-[10px] font-bold text-white uppercase tracking-widest">No</th>
                            <th class="px-3 py-3 text-left text-[10px] font-bold text-white uppercase tracking-widest">Hari</th>
                            <th class="px-3 py-3 text-left text-[10px] font-bold text-white uppercase tracking-widest">Jam</th>
                            <th class="px-3 py-3 text-left text-[10px] font-bold text-white uppercase tracking-widest">Pukul</th>
                            <th class="px-3 py-3 text-left text-[10px] font-bold text-white uppercase tracking-widest">Ruangan</th>
                            <th class="px-3 py-3 text-left text-[10px] font-bold text-white uppercase tracking-widest">Mata Kuliah</th>
                            <th class="px-3 py-3 text-center text-[10px] font-bold text-white uppercase tracking-widest">SKS</th>
                            <th class="px-3 py-3 text-left text-[10px] font-bold text-white uppercase tracking-widest">Dosen Pengampu</th>
                            <th class="px-3 py-3 text-left text-[10px] font-bold text-white uppercase tracking-widest">Dosen Mitra</th>
                            <?php if ($role === 'Ketua Tingkat'): ?>
                                <th class="px-3 py-3 text-[10px] font-bold text-white uppercase tracking-widest text-center">Actions</th>
                            <?php endif; ?>
                        </tr>
                    </thead>
                    <tbody class="bg-white dark:bg-slate-900 divide-y divide-slate-200 dark:divide-slate-800">
                        <?php
                        $no = 1;
                        $id = 1;
                        while ($row = mysqli_fetch_assoc($result)) : 
                            $isEven = ($id++ % 2 == 0);
                            $rowClass = ($isEven ? 'bg-slate-50/50 dark:bg-slate-800/20' : 'bg-white dark:bg-slate-900') . ' hover:bg-indigo-50 dark:hover:bg-indigo-900/20 transition-colors';
                        ?>
                            <tr class="<?= $rowClass ?>">
                                <td class="px-3 py-2 whitespace-nowrap text-xs text-slate-800 dark:text-slate-200 font-medium"><?= $no++; ?></td>
                                <td class="px-3 py-2 whitespace-nowrap text-xs text-slate-800 dark:text-slate-200 font-semibold"><?= $row['hari']; ?></td>
                                <td class="px-3 py-2 whitespace-nowrap text-xs text-slate-600 dark:text-slate-400"><?= $row['jam_mulai_ke'] . '-' . $row['jam_selesai_ke']; ?></td>
                                <td class="px-3 py-2 whitespace-nowrap text-xs text-slate-600 dark:text-slate-400 font-mono"><?= date('H.i', strtotime($row['jam_mulai'])); ?> - <?= date('H.i', strtotime($row['jam_selesai'])); ?></td>
                                <td class="px-3 py-2 whitespace-nowrap text-xs text-slate-800 dark:text-slate-200 font-medium"><?= $row['ruangan']; ?></td>
                                <td class="px-3 py-2 text-xs text-slate-800 dark:text-slate-100 font-bold"><?= $row['nama_matkul']; ?></td>
                                <td class="px-3 py-2 whitespace-nowrap text-xs text-center"><span class="bg-indigo-50 dark:bg-indigo-900/40 text-indigo-700 dark:text-indigo-300 px-2 py-0.5 rounded text-[10px] font-bold border border-indigo-100 dark:border-indigo-800/50"><?= $row['sks']; ?></span></td>
                                <td class="px-3 py-2 text-xs text-slate-600 dark:text-slate-400"><?= $row['dosen_pengampu']; ?></td>
                                <td class="px-3 py-2 text-xs text-slate-600 dark:text-slate-400"><?= $row['dosen_mitra']; ?></td>
                                <?php if ($role === 'Ketua Tingkat'): ?>
                                    <td class="px-3 py-2 whitespace-nowrap text-sm text-center">
                                        <div class="flex items-center justify-center gap-3">
                                            <a href="../actions/edit.php?tabel=jadwal_matkul&id=<?= $row['id']; ?>" title="Edit" class="text-blue-600 dark:text-blue-400 hover:text-blue-900 dark:hover:text-blue-300 transition-colors"><i class="fa fa-edit"></i></a>
                                            <a href="../actions/hapus.php?tabel=jadwal_matkul&id=<?= $row['id']; ?>" title="Hapus" class="text-red-600 dark:text-red-400 hover:text-red-900 dark:hover:text-red-300 transition-colors"><i class="fa fa-trash"></i></a>
                                        </div>
                                    </td>
                                <?php endif; ?>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php include '../includes/footer_db.php'; ?>
    </main>
    <?php include '../includes/script_db.php'; ?>
    <script>
        // Live search via AJAX (debounced) - keep focus in input
        (function() {
            const input = document.getElementById('searchInput');
            if (!input) return;
            let timer = null;
            input.addEventListener('input', function() {
                clearTimeout(timer);
                timer = setTimeout(() => {
                    const q = encodeURIComponent(input.value || '');
                    const url = window.location.pathname + '?search=' + q + '&ajax=1';
                    fetch(url, {
                            credentials: 'same-origin'
                        })
                        .then(r => r.text())
                        .then(html => {
                            const tbody = document.querySelector('table tbody');
                            if (tbody) tbody.innerHTML = html || '<tr><td colspan="11" class="px-3 py-2 text-xs text-slate-500">No results</td></tr>';
                            input.focus();
                        }).catch(err => console.error(err));
                }, 250);
            });
        })();

        // Global variables for custom schedule
        let currentJadwal = [];
        let optJam = [];
        let optRuang = [];
        let optMk = [];

        function confirmJadwal() {
            Swal.fire({
                title: 'Kirim Jadwal Kuliah',
                text: "Pilih jadwal yang ingin dikirim ke WhatsApp PTIK 2024.",
                icon: 'question',
                showCancelButton: true,
                showDenyButton: true,
                confirmButtonColor: '#3085d6',
                denyButtonColor: '#10b981',
                cancelButtonColor: '#6b7280',
                confirmButtonText: 'Jadwal Besok',
                denyButtonText: 'Jadwal Hari Ini',
                cancelButtonText: 'Batal'
            }).then((result) => {
                let day = 'besok';
                if (result.isDenied) day = 'hari_ini';

                if (result.isConfirmed || result.isDenied) {
                    Swal.fire({
                        title: 'Metode Pengiriman',
                        text: `Pilih metode pengiriman untuk jadwal ${day === 'hari_ini' ? 'hari ini' : 'besok'}.`,
                        icon: 'info',
                        showCancelButton: true,
                        showDenyButton: true,
                        confirmButtonColor: '#3085d6',
                        denyButtonColor: '#f59e0b',
                        confirmButtonText: 'Kirim Langsung',
                        denyButtonText: 'Sesuaikan Jadwal',
                        cancelButtonText: 'Kembali'
                    }).then((methodResult) => {
                        if (methodResult.isConfirmed) {
                            // Kirim Langsung
                            const form = document.createElement('form');
                            form.method = 'POST';
                            form.action = '../views/reminder'; // target reminder as central handler

                            const inputAction = document.createElement('input');
                            inputAction.type = 'hidden';
                            inputAction.name = 'action';
                            inputAction.value = 'jadwal';
                            form.appendChild(inputAction);

                            const inputDay = document.createElement('input');
                            inputDay.type = 'hidden';
                            inputDay.name = 'day';
                            inputDay.value = day;
                            form.appendChild(inputDay);

                            document.body.appendChild(form);
                            form.submit();
                        } else if (methodResult.isDenied) {
                            fetchDataAndShowList(day);
                        }
                    });
                }
            });
        }

        function fetchDataAndShowList(day) {
            Swal.showLoading();
            window.lastSelectedDay = day;
            fetch(`?fetch_adjust_data=1&day=${day}`)
                .then(r => r.json())
                .then(data => {
                    currentJadwal = data.jadwal;
                    optJam = data.opt_jam;
                    optRuang = data.opt_ruang;
                    optMk = data.opt_mk;
                    renderJadwalList(data.tomorrow, data.date);
                })
                .catch(err => {
                    console.error(err);
                    Swal.fire('Error', 'Gagal mengambil data jadwal', 'error');
                });
        }

        function renderJadwalList(dayName, dateStr) {
            if (dayName) window.lastDayName = dayName;
            if (dateStr) window.lastDateStr = dateStr;
            dayName = dayName || window.lastDayName;
            dateStr = dateStr || window.lastDateStr;

            let html = `<div class="text-left text-sm mb-4 flex justify-between items-center">
                <span class="text-slate-700 dark:text-slate-300">Hari: <b class="text-slate-900 dark:text-white">${dayName}</b>, ${dateStr}</span>
                <button type="button" onclick="addNewMatkul()" class="bg-indigo-600 hover:bg-indigo-700 text-white text-[10px] font-bold px-3 py-1.5 rounded-lg transition-all shadow-sm flex items-center gap-1.5">
                    <i class="fas fa-plus"></i> Tambah Matkul
                </button>
            </div>`;

            if (currentJadwal.length === 0) {
                html += '<p class="text-slate-400 dark:text-slate-500 text-xs text-center py-8">Belum ada jadwal yang ditambahkan.</p>';
            } else {
                html += `<div class="overflow-x-auto max-h-64 border border-slate-200 dark:border-slate-700 rounded-xl bg-slate-50 dark:bg-slate-800/50">
                    <table class="w-full text-xs text-left border-collapse">
                        <thead class="bg-indigo-900 dark:bg-indigo-950 text-white border-b border-slate-200 dark:border-slate-700 sticky top-0">
                            <tr>
                                <th class="p-3 w-8 text-center"><i class="fas fa-check-circle"></i></th>
                                <th class="p-3">Mata Kuliah</th>
                                <th class="p-3">Jam & Ruangan</th>
                                <th class="p-3 w-16 text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-200 dark:divide-slate-700 bg-white dark:bg-slate-900">`;

                currentJadwal.forEach((item, index) => {
                    const checked = item.checked ? 'checked' : '';
                    const isAbsent = item.status === 'tidak_masuk';
                    const statusBadge = isAbsent ? '<span class="text-[8px] bg-red-100 dark:bg-red-900/40 text-red-600 dark:text-red-400 px-1.5 py-0.5 rounded-md ml-1.5 font-bold uppercase tracking-tighter border border-red-200 dark:border-red-800/50">Tidak Masuk</span>' : '';
                    const detailText = isAbsent ? '-' : `${item.jam_text} (${item.ruangan_text})`;
                    html += `<tr class="${!item.checked ? 'opacity-50 grayscale' : ''} hover:bg-slate-50 dark:hover:bg-slate-800/40 transition-colors">
                        <td class="p-3 text-center"><input type="checkbox" onchange="toggleCheck(${index})" ${checked} class="rounded border-slate-300 dark:border-slate-600 text-indigo-600 focus:ring-indigo-500 dark:bg-slate-800"></td>
                        <td class="p-3">
                            <div class="font-bold text-slate-800 dark:text-slate-100">${item.matkul}</div>
                            ${statusBadge}
                        </td>
                        <td class="p-3 text-slate-500 dark:text-slate-400 font-medium">${detailText}</td>
                        <td class="p-3 text-center">
                            <div class="flex gap-2 justify-center">
                                <button type="button" onclick="showEditForm(${index})" class="p-1.5 text-blue-600 dark:text-blue-400 hover:bg-blue-50 dark:hover:bg-blue-900/30 rounded transition-all"><i class="fas fa-edit text-xs"></i></button>
                                <button type="button" onclick="removeItem(${index})" class="p-1.5 text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/30 rounded transition-all"><i class="fas fa-trash text-xs"></i></button>
                            </div>
                        </td>
                    </tr>`;
                });

                html += `</tbody></table></div>`;
            }

            Swal.fire({
                title: '<span class="text-slate-800 dark:text-slate-100">Sesuaikan Jadwal</span>',
                html: html,
                width: '650px',
                background: document.documentElement.classList.contains('dark') ? '#0f172a' : '#ffffff',
                color: document.documentElement.classList.contains('dark') ? '#f1f5f9' : '#1e293b',
                showCancelButton: true,
                confirmButtonText: '<i class="fab fa-whatsapp mr-1.5"></i> Kirim WhatsApp',
                cancelButtonText: 'Kembali',
                confirmButtonColor: '#4f46e5',
                customClass: {
                    confirmButton: 'text-xs px-6 py-2.5 rounded-lg font-bold uppercase tracking-wider',
                    cancelButton: 'text-xs px-6 py-2.5 rounded-lg font-bold uppercase tracking-wider text-slate-600 dark:text-slate-400'
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    submitCustomJadwal();
                }
            });
        }

        function toggleCheck(index) {
            currentJadwal[index].checked = !currentJadwal[index].checked;
            renderJadwalList();
        }

        function removeItem(index) {
            currentJadwal.splice(index, 1);
            renderJadwalList();
        }

        function addNewMatkul() {
            let mkSelect = `<select id="new_matkul" class="w-full px-3 py-2.5 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl text-sm mb-4 focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-600 transition-all font-medium text-slate-700 dark:text-slate-200 appearance-none">`;
            mkSelect += `<option value="" disabled selected>-- Pilih Mata Kuliah --</option>`;
            optMk.forEach(opt => {
                mkSelect += `<option value="${opt.nama}">${opt.nama}</option>`;
            });
            mkSelect += `</select>`;

            let ruangSelect = `<select id="new_ruang" class="w-full px-3 py-2.5 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl text-sm focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-600 transition-all font-medium text-slate-700 dark:text-slate-200 appearance-none">`;
            ruangSelect += `<option value="" disabled selected>-- Pilih Ruangan --</option>`;
            optRuang.forEach(opt => {
                ruangSelect += `<option value="${opt.val}">${opt.label}</option>`;
            });
            ruangSelect += `</select>`;

            Swal.fire({
                title: '<span class="text-slate-800 dark:text-slate-100">Tambah Mata Kuliah</span>',
                background: document.documentElement.classList.contains('dark') ? '#0f172a' : '#ffffff',
                color: document.documentElement.classList.contains('dark') ? '#f1f5f9' : '#1e293b',
                html: `
                    <div class="text-left space-y-4">
                        <div>
                            <label class="block text-[10px] font-bold text-slate-400 dark:text-slate-500 uppercase tracking-widest mb-1.5 ml-1">Pilih Mata Kuliah</label>
                            ${mkSelect}
                        </div>
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-[10px] font-bold text-slate-400 dark:text-slate-500 uppercase tracking-widest mb-1.5 ml-1">Jam Mulai</label>
                                <input type="time" id="new_jam_mulai" class="w-full px-3 py-2.5 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl text-sm focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-600 transition-all font-medium text-slate-700 dark:text-slate-200">
                            </div>
                            <div>
                                <label class="block text-[10px] font-bold text-slate-400 dark:text-slate-500 uppercase tracking-widest mb-1.5 ml-1">Jam Selesai</label>
                                <input type="time" id="new_jam_selesai" class="w-full px-3 py-2.5 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl text-sm focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-600 transition-all font-medium text-slate-700 dark:text-slate-200">
                            </div>
                        </div>
                        <div>
                            <label class="block text-[10px] font-bold text-slate-400 dark:text-slate-500 uppercase tracking-widest mb-1.5 ml-1">Pilih Ruangan</label>
                            ${ruangSelect}
                        </div>
                    </div>
                `,
                showCancelButton: true,
                confirmButtonText: 'Tambah',
                cancelButtonText: 'Batal',
                confirmButtonColor: '#4f46e5',
                customClass: {
                    confirmButton: 'text-xs px-6 py-2.5 rounded-lg font-bold uppercase tracking-wider',
                    cancelButton: 'text-xs px-6 py-2.5 rounded-lg font-bold uppercase tracking-wider text-slate-600 dark:text-slate-400'
                },
                preConfirm: () => {
                    const matkul = document.getElementById('new_matkul').value;
                    const jamMulai = document.getElementById('new_jam_mulai').value;
                    const jamSelesai = document.getElementById('new_jam_selesai').value;
                    const ruangan = document.getElementById('new_ruang').value;

                    if (!matkul || !jamMulai || !jamSelesai || !ruangan) {
                        Swal.showValidationMessage('Semua field harus diisi!');
                        return false;
                    }

                    const jamText = jamMulai.replace(':', '.') + ' - ' + jamSelesai.replace(':', '.');

                    return {
                        matkul,
                        jam_text: jamText,
                        ruangan_text: ruangan,
                        checked: true,
                        status: 'masuk'
                    };
                }
            }).then((res) => {
                if (res.isConfirmed) {
                    currentJadwal.push(res.value);
                    renderJadwalList();
                } else {
                    renderJadwalList();
                }
            });
        }

        function showEditForm(index) {
            const item = currentJadwal[index];

            let jamMulai = '07:30';
            let jamSelesai = '09:10';
            if (item.jam_text && item.jam_text.includes(' - ')) {
                const parts = item.jam_text.split(' - ');
                jamMulai = parts[0].replace('.', ':');
                jamSelesai = parts[1].replace('.', ':');
            }

            const currentStatus = item.status || 'masuk';

            let ruangHtml = `<select id="edit_ruang" class="w-full px-3 py-2.5 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl text-sm focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-600 transition-all font-medium text-slate-700 dark:text-slate-200 appearance-none">`;
            optRuang.forEach(opt => {
                const selected = (opt.val === item.ruangan_text) ? 'selected' : '';
                ruangHtml += `<option value="${opt.val}" ${selected}>${opt.label}</option>`;
            });
            ruangHtml += `</select>`;

            Swal.fire({
                title: '<span class="text-slate-800 dark:text-slate-100">Edit Item Jadwal</span>',
                background: document.documentElement.classList.contains('dark') ? '#0f172a' : '#ffffff',
                color: document.documentElement.classList.contains('dark') ? '#f1f5f9' : '#1e293b',
                html: `
                    <div class="text-left space-y-4">
                        <div>
                            <label class="block text-[10px] font-bold text-slate-400 dark:text-slate-500 uppercase tracking-widest mb-1.5 ml-1">Status Kehadiran</label>
                            <select id="edit_status" class="w-full px-3 py-2.5 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl text-sm focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-600 transition-all font-medium text-slate-700 dark:text-slate-200 appearance-none" onchange="toggleEditFields(this.value)">
                                <option value="masuk" ${currentStatus === 'masuk' ? 'selected' : ''}>Masuk</option>
                                <option value="tidak_masuk" ${currentStatus === 'tidak_masuk' ? 'selected' : ''}>Tidak Masuk (Absen)</option>
                            </select>
                        </div>
                        
                        <div id="time_fields_container" class="${currentStatus === 'tidak_masuk' ? 'hidden' : 'space-y-4'}">
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-[10px] font-bold text-slate-400 dark:text-slate-500 uppercase tracking-widest mb-1.5 ml-1">Jam Mulai</label>
                                    <input type="time" id="edit_jam_mulai" value="${jamMulai}" class="w-full px-3 py-2.5 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl text-sm focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-600 transition-all font-medium text-slate-700 dark:text-slate-200">
                                </div>
                                <div>
                                    <label class="block text-[10px] font-bold text-slate-400 dark:text-slate-500 uppercase tracking-widest mb-1.5 ml-1">Jam Selesai</label>
                                    <input type="time" id="edit_jam_selesai" value="${jamSelesai}" class="w-full px-3 py-2.5 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl text-sm focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-600 transition-all font-medium text-slate-700 dark:text-slate-200">
                                </div>
                            </div>
                            
                            <div>
                                <label class="block text-[10px] font-bold text-slate-400 dark:text-slate-500 uppercase tracking-widest mb-1.5 ml-1">Pilih Ruangan Kuliah</label>
                                ${ruangHtml}
                            </div>
                        </div>
                    </div>
                `,
                showCancelButton: true,
                confirmButtonText: 'Simpan Perubahan',
                cancelButtonText: 'Batal',
                confirmButtonColor: '#4f46e5',
                customClass: {
                    confirmButton: 'text-xs px-6 py-2.5 rounded-lg font-bold uppercase tracking-wider',
                    cancelButton: 'text-xs px-6 py-2.5 rounded-lg font-bold uppercase tracking-wider text-slate-600 dark:text-slate-400'
                },
                preConfirm: () => {
                    const status = document.getElementById('edit_status').value;
                    let jamText = item.jam_text;
                    let ruangText = item.ruangan_text;

                    if (status === 'masuk') {
                        const jamMulaiVal = document.getElementById('edit_jam_mulai').value;
                        const jamSelesaiVal = document.getElementById('edit_jam_selesai').value;
                        jamText = jamMulaiVal.replace(':', '.') + ' - ' + jamSelesaiVal.replace(':', '.');
                        ruangText = document.getElementById('edit_ruang').value;
                    } else {
                        jamText = '-';
                        ruangText = '-';
                    }

                    return {
                        jam: jamText,
                        ruang: ruangText,
                        status: status
                    };
                }
            }).then((res) => {
                if (res.isConfirmed) {
                    currentJadwal[index].jam_text = res.value.jam;
                    currentJadwal[index].ruangan_text = res.value.ruang;
                    currentJadwal[index].status = res.value.status;
                }
                renderJadwalList();
            });
        }

        function toggleEditFields(status) {
            const container = document.getElementById('time_fields_container');
            if (status === 'tidak_masuk') {
                container.classList.add('hidden');
                container.classList.remove('space-y-4');
            } else {
                container.classList.remove('hidden');
                container.classList.add('space-y-4');
            }
        }

        function submitCustomJadwal() {
            const selectedItems = currentJadwal.filter(i => i.checked).map(i => ({
                matkul: i.matkul,
                jam: i.jam_text,
                ruangan: i.ruangan_text,
                status: i.status || 'masuk'
            }));

            if (selectedItems.length === 0) {
                Swal.fire('Peringatan', 'Pilih minimal satu jadwal untuk dikirim.', 'warning')
                    .then(() => renderJadwalList());
                return;
            }

            const form = document.createElement('form');
            form.method = 'POST';
            form.action = '../views/reminder';

            const inputAction = document.createElement('input');
            inputAction.type = 'hidden';
            inputAction.name = 'action';
            inputAction.value = 'jadwal_custom';
            form.appendChild(inputAction);

            const inputDay = document.createElement('input');
            inputDay.type = 'hidden';
            inputDay.name = 'day';
            inputDay.value = window.lastSelectedDay;
            form.appendChild(inputDay);

            const inputPayload = document.createElement('input');
            inputPayload.type = 'hidden';
            inputPayload.name = 'custom_payload';
            inputPayload.value = JSON.stringify(selectedItems);
            form.appendChild(inputPayload);

            document.body.appendChild(form);
            form.submit();
        }

        // Show Session Alerts with SweetAlert
        <?php if (isset($_SESSION['success'])): ?>
            Swal.fire({
                icon: 'success',
                title: 'Berhasil!',
                text: '<?= addslashes($_SESSION['success']) ?>',
                confirmButtonColor: '#3085d6'
            });
            <?php unset($_SESSION['success']); ?>
        <?php endif; ?>

        <?php if (isset($_SESSION['error'])): ?>
            Swal.fire({
                icon: 'error',
                title: 'Gagal!',
                text: '<?= addslashes($_SESSION['error']) ?>',
                confirmButtonColor: '#d33'
            });
            <?php unset($_SESSION['error']); ?>
        <?php endif; ?>

        <?php if (isset($_SESSION['info'])): ?>
            Swal.fire({
                icon: 'info',
                title: 'Informasi',
                text: '<?= addslashes($_SESSION['info']) ?>',
                confirmButtonColor: '#3085d6'
            });
            <?php unset($_SESSION['info']); ?>
        <?php endif; ?>
    </script>
</body>

</html>