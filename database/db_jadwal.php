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
                     WHERE jm.periode_id = $p_id AND jm.hari = '$target_ind'
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
                 WHERE jm.periode_id = $p_id 
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
WHERE jm.periode_id = $periode_id
ORDER BY jm.hari, jk_start.jam_mulai");
}

// If AJAX live-search request, return table rows only
if (isset($_GET['ajax']) && $_GET['ajax'] == '1') {
    $no = 1;
    $id = 1;
    while ($row = mysqli_fetch_assoc($result)) {
        $rowClass = (($id++ % 2 == 0) ? 'bg-slate-100' : 'bg-white') . ' hover:bg-indigo-100';
        echo '<tr class="' . $rowClass . '">';
        echo '<td class="px-3 py-2 whitespace-nowrap text-xs text-slate-800">' . ($no++) . '</td>';
        echo '<td class="px-3 py-2 whitespace-nowrap text-xs text-slate-800">' . htmlspecialchars($row['hari']) . '</td>';
        echo '<td class="px-3 py-2 whitespace-nowrap text-xs text-slate-800">' . htmlspecialchars($row['jam_mulai_ke']) . '-' . htmlspecialchars($row['jam_selesai_ke']) . '</td>';
        echo '<td class="px-3 py-2 whitespace-nowrap text-xs text-slate-800">' . date('H.i', strtotime($row['jam_mulai'])) . ' - ' . date('H.i', strtotime($row['jam_selesai'])) . '</td>';
        echo '<td class="px-3 py-2 whitespace-nowrap text-xs text-slate-800">' . htmlspecialchars($row['ruangan']) . '</td>';
        echo '<td class="px-3 py-2 text-xs text-slate-800">' . htmlspecialchars($row['nama_matkul']) . '</td>';
        echo '<td class="px-3 py-2 whitespace-nowrap text-xs text-slate-800">' . htmlspecialchars($row['sks']) . '</td>';
        echo '<td class="px-3 py-2 text-xs text-slate-800">' . htmlspecialchars($row['dosen_pengampu']) . '</td>';
        echo '<td class="px-3 py-2 text-xs text-slate-800">' . htmlspecialchars($row['dosen_mitra']) . '</td>';
        if ($role === 'Administrator Utama' || $role === 'Sekretaris'):
            echo '<td class="px-3 py-2 whitespace-nowrap text-sm text-slate-500">';
            echo '<a href="../actions/edit.php?tabel=jadwal_matkul&id=' . $row['id'] . '" title="Edit"><i class="fa fa-edit text-blue-600 hover:text-blue-900 mr-3"></i></a>';
            echo '<a href="../actions/hapus.php?tabel=jadwal_matkul&id=' . $row['id'] . '" title="Hapus" onclick="return confirm(\'Hapus jadwal ini?\');"><i class="fa fa-trash text-red-600 hover:text-red-900"></i></a>';
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

    <title>Data Jadwal Kuliah - PTIK C 2024</title>
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

<body class="bg-slate-100">
    <?php include '../includes/menu_db.php'; ?>

    <main class="ml-0 lg:ml-48 pt-20 p-6 flex flex-col min-h-screen">
        <div class="bg-white rounded-lg border border-slate-200 p-6 flex-grow w-full">
            <!-- Modified this section for better mobile responsiveness -->
            <!-- Session Alerts handled by SweetAlert -->

            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-6">
                <h3 class="text-xl font-bold text-slate-800">Jadwal Mata Kuliah</h3>
                <div class="flex-col sm:flex-row gap-2 w-full sm:w-auto">
                    <form method="GET" class="w-full">
                        <div class="flex flex-row gap-2 items-center">
                            <input
                                id="searchInput"
                                type="text"
                                name="search"
                                value="<?= htmlspecialchars($_GET['search'] ?? '') ?>"
                                placeholder="Cari Hari / Mata Kuliah..."
                                class="flex-grow px-3 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-1 focus:ring-slate-400 text-xs">

                            <?php if ($role === 'Administrator Utama' || $role === 'Sekretaris'): ?>
                                <button type="button" onclick="confirmJadwal()" class="bg-blue-600 text-white px-3 py-2 rounded-lg hover:bg-blue-800 text-center text-xs flex items-center justify-center" title="Kirim WhatsApp">
                                    <i class="fa fa-paper-plane"></i>
                                </button>


                                <a href="../jadwal" class="bg-indigo-600 text-white px-3 py-2 rounded-lg hover:bg-indigo-800 text-center text-xs flex items-center justify-center" title="Lihat View">
                                    <i class="fa fa-eye"></i>
                                </a>

                                <a href="../actions/tambah.php?tabel=jadwal_matkul" class="bg-green-600 text-white px-3 py-2 rounded-lg hover:bg-green-800 text-center text-xs flex items-center justify-center" title="Tambah">
                                    <i class="fa fa-plus"></i>
                                </a>
                            <?php endif; ?>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Rest of the content remains the same -->
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200">
                    <thead class="bg-indigo-900">
                        <tr>
                            <th class="px-3 py-3 text-left text-xs font-bold text-white uppercase tracking-wider">
                                No</th>
                            <th class="px-3 py-3 text-left text-xs font-bold text-white uppercase tracking-wider">
                                Hari</th>
                            <th class="px-3 py-3 text-left text-xs font-bold text-white uppercase tracking-wider">
                                Jam</th>
                            <th class="px-3 py-3 text-left text-xs font-bold text-white uppercase tracking-wider">
                                Pukul</th>
                            <th class="px-3 py-3 text-left text-xs font-bold text-white uppercase tracking-wider">
                                Ruangan</th>
                            <th class="px-3 py-3 text-left text-xs font-bold text-white uppercase tracking-wider">
                                Mata Kuliah</th>
                            <th class="px-3 py-3 text-left text-xs font-bold text-white uppercase tracking-wider">
                                SKS</th>
                            <th class="px-3 py-3 text-left text-xs font-bold text-white uppercase tracking-wider">
                                Dosen Pengampu</th>
                            <th class="px-3 py-3 text-left text-xs font-bold text-white uppercase tracking-wider">
                                Dosen Mitra</th>
                            <?php if ($role === 'Administrator Utama' || $role === 'Sekretaris'): ?>
                                <th class="px-3 py-3 text-left text-xs font-bold text-white uppercase tracking-wider">
                                    Actions</th>
                            <?php endif; ?>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-slate-200">
                        <?php
                        $no = 1;
                        $id = 1;
                        while ($row = mysqli_fetch_assoc($result)) : ?>
                            <tr class="<?= ($id++ % 2 == 0) ? 'bg-slate-100' : 'bg-white' ?> hover:bg-indigo-100">

                                <td class="px-3 py-2 whitespace-nowrap text-xs text-slate-800"><?= $no++; ?></td>

                                <td class="px-3 py-2 whitespace-nowrap text-xs text-slate-800"><?= $row['hari']; ?></td>
                                <td class="px-3 py-2 whitespace-nowrap text-xs text-slate-800"><?= $row['jam_mulai_ke'] . '-' . $row['jam_selesai_ke']; ?></td>
                                <td class="px-3 py-2 whitespace-nowrap text-xs text-slate-800"><?= date('H.i', strtotime($row['jam_mulai'])); ?> - <?= date('H.i', strtotime($row['jam_selesai'])); ?></td>

                                <td class="px-3 py-2 whitespace-nowrap text-xs text-slate-800"><?= $row['ruangan']; ?></td>
                                <td class="px-3 py-2 text-xs text-slate-800"><?= $row['nama_matkul']; ?></td>
                                <td class="px-3 py-2 whitespace-nowrap text-xs text-slate-800"><?= $row['sks']; ?></td>
                                <td class="px-3 py-2 text-xs text-slate-800"><?= $row['dosen_pengampu']; ?></td>
                                <td class="px-3 py-2 text-xs text-slate-800"><?= $row['dosen_mitra']; ?></td>
                                <?php if ($role === 'Administrator Utama' || $role === 'Sekretaris'): ?>
                                    <td class="px-3 py-2 whitespace-nowrap text-sm text-slate-500">
                                        <a href="../actions/edit.php?tabel=jadwal_matkul&id=<?= $row['id']; ?>" title="Edit"><i class="fa fa-edit text-blue-600 hover:text-blue-900 mr-3"></i></a>

                                        <a href="../actions/hapus.php?tabel=jadwal_matkul&id=<?= $row['id']; ?>" title="Hapus" onclick="return confirm('Hapus jadwal ini?');"><i class="fa fa-trash text-red-600 hover:text-red-900"></i></a>

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
                text: "Pilih jadwal yang ingin dikirim ke WhatsApp PTIK C.",
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
                <span>Hari: <b>${dayName}</b>, ${dateStr}</span>
                <button type="button" onclick="addNewMatkul()" class="bg-indigo-600 hover:bg-indigo-700 text-white text-[10px] font-bold px-3 py-1.5 rounded-lg transition flex items-center gap-1.5">
                    <i class="fas fa-plus"></i> Tambah Matkul
                </button>
            </div>`;

            if (currentJadwal.length === 0) {
                html += '<p class="text-slate-400 text-xs text-center py-8">Belum ada jadwal yang ditambahkan.</p>';
            } else {
                html += `<div class="overflow-x-auto max-h-64 border rounded-lg bg-slate-50">
                    <table class="w-full text-xs text-left">
                        <thead class="bg-indigo-900 text-white border-b sticky top-0">
                            <tr>
                                <th class="p-2 w-8 text-center"><i class="fas fa-check-circle"></i></th>
                                <th class="p-2">Mata Kuliah</th>
                                <th class="p-2">Jam & Ruangan</th>
                                <th class="p-2 w-16 text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y bg-white">`;

                currentJadwal.forEach((item, index) => {
                    const checked = item.checked ? 'checked' : '';
                    const isAbsent = item.status === 'tidak_masuk';
                    const statusBadge = isAbsent ? '<span class="text-[8px] bg-red-100 text-red-600 px-1.5 py-0.5 rounded-md ml-1.5 font-bold uppercase tracking-tighter">Tidak Masuk</span>' : '';
                    const detailText = isAbsent ? '-' : `${item.jam_text} (${item.ruangan_text})`;
                    html += `<tr class="${!item.checked ? 'opacity-50' : ''}">
                        <td class="p-2 text-center"><input type="checkbox" onchange="toggleCheck(${index})" ${checked} class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500"></td>
                        <td class="p-2">
                            <div class="font-bold text-slate-800">${item.matkul}</div>
                            ${statusBadge}
                        </td>
                        <td class="p-2 text-slate-500 font-medium">${detailText}</td>
                        <td class="p-2 text-center">
                            <div class="flex gap-1 justify-center">
                                <button type="button" onclick="showEditForm(${index})" class="p-1.5 text-blue-600 hover:bg-blue-50 rounded transition"><i class="fas fa-edit"></i></button>
                                <button type="button" onclick="removeItem(${index})" class="p-1.5 text-red-600 hover:bg-red-50 rounded transition"><i class="fas fa-trash"></i></button>
                            </div>
                        </td>
                    </tr>`;
                });

                html += `</tbody></table></div>`;
            }

            Swal.fire({
                title: 'Sesuaikan Jadwal',
                html: html,
                width: '650px',
                showCancelButton: true,
                confirmButtonText: '<i class="fab fa-whatsapp mr-1.5"></i> Kirim WhatsApp',
                cancelButtonText: 'Kembali',
                confirmButtonColor: '#4f46e5',
                customClass: {
                    confirmButton: 'text-xs px-6 py-2.5 rounded-lg font-bold uppercase tracking-wider',
                    cancelButton: 'text-xs px-6 py-2.5 rounded-lg font-bold uppercase tracking-wider'
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
            let mkSelect = `<select id="new_matkul" class="w-full px-3 py-2 border border-slate-200 rounded-xl text-sm mb-4 focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-600 transition-all font-medium">`;
            mkSelect += `<option value="" disabled selected>-- Pilih Mata Kuliah --</option>`;
            optMk.forEach(opt => {
                mkSelect += `<option value="${opt.nama}">${opt.nama}</option>`;
            });
            mkSelect += `</select>`;

            let ruangSelect = `<select id="new_ruang" class="w-full px-3 py-2 border border-slate-200 rounded-xl text-sm focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-600 transition-all font-medium">`;
            ruangSelect += `<option value="" disabled selected>-- Pilih Ruangan --</option>`;
            optRuang.forEach(opt => {
                ruangSelect += `<option value="${opt.val}">${opt.label}</option>`;
            });
            ruangSelect += `</select>`;

            Swal.fire({
                title: 'Tambah Mata Kuliah',
                html: `
                    <div class="text-left space-y-4">
                        <div>
                            <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1.5 ml-1">Pilih Mata Kuliah</label>
                            ${mkSelect}
                        </div>
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1.5 ml-1">Jam Mulai</label>
                                <input type="time" id="new_jam_mulai" class="w-full px-3 py-2 border border-slate-200 rounded-xl text-sm focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-600 transition-all font-medium">
                            </div>
                            <div>
                                <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1.5 ml-1">Jam Selesai</label>
                                <input type="time" id="new_jam_selesai" class="w-full px-3 py-2 border border-slate-200 rounded-xl text-sm focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-600 transition-all font-medium">
                            </div>
                        </div>
                        <div>
                            <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1.5 ml-1">Pilih Ruangan</label>
                            ${ruangSelect}
                        </div>
                    </div>
                `,
                showCancelButton: true,
                confirmButtonText: 'Tambah',
                cancelButtonText: 'Batal',
                confirmButtonColor: '#4f46e5',
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

            let ruangHtml = `<select id="edit_ruang" class="w-full px-3 py-2.5 border border-slate-200 rounded-xl text-sm focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-600 transition-all font-medium">`;
            optRuang.forEach(opt => {
                const selected = (opt.val === item.ruangan_text) ? 'selected' : '';
                ruangHtml += `<option value="${opt.val}" ${selected}>${opt.label}</option>`;
            });
            ruangHtml += `</select>`;

            Swal.fire({
                title: 'Edit Item Jadwal',
                html: `
                    <div class="text-left space-y-4">
                        <div>
                            <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1.5 ml-1">Status Kehadiran</label>
                            <select id="edit_status" class="w-full px-3 py-2.5 border border-slate-200 rounded-xl text-sm focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-600 transition-all font-medium" onchange="toggleEditFields(this.value)">
                                <option value="masuk" ${currentStatus === 'masuk' ? 'selected' : ''}>Masuk</option>
                                <option value="tidak_masuk" ${currentStatus === 'tidak_masuk' ? 'selected' : ''}>Tidak Masuk (Absen)</option>
                            </select>
                        </div>
                        
                        <div id="time_fields_container" class="${currentStatus === 'tidak_masuk' ? 'hidden' : 'space-y-4'}">
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1.5 ml-1">Jam Mulai</label>
                                    <input type="time" id="edit_jam_mulai" value="${jamMulai}" class="w-full px-3 py-2.5 border border-slate-200 rounded-xl text-sm focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-600 transition-all font-medium">
                                </div>
                                <div>
                                    <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1.5 ml-1">Jam Selesai</label>
                                    <input type="time" id="edit_jam_selesai" value="${jamSelesai}" class="w-full px-3 py-2.5 border border-slate-200 rounded-xl text-sm focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-600 transition-all font-medium">
                                </div>
                            </div>
                            
                            <div>
                                <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1.5 ml-1">Pilih Ruangan Kuliah</label>
                                ${ruangHtml}
                            </div>
                        </div>
                    </div>
                `,
                showCancelButton: true,
                confirmButtonText: 'Simpan Perubahan',
                cancelButtonText: 'Batal',
                confirmButtonColor: '#4f46e5',
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