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

    $days_map = ['Sunday' => 'Minggu', 'Monday' => 'Senin', 'Tuesday' => 'Selasa', 'Wednesday' => 'Rabu', 'Thursday' => 'Kamis', 'Friday' => 'Jumat', 'Saturday' => 'Sabtu'];
    $tomorrow_eng = date('l', strtotime('+1 day'));
    $tomorrow_ind = $days_map[$tomorrow_eng];

    // 1. Get Tomorrow's Schedule
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
                     WHERE jm.periode_id = $p_id AND jm.hari = '$tomorrow_ind'
                     ORDER BY jk_start.jam_mulai ASC";

    $res_jadwal = mysqli_query($koneksi, $query_jadwal);
    $jadwal = [];
    while ($row = mysqli_fetch_assoc($res_jadwal)) {
        // Format jam display (e.g. 07.30 - 09.10)
        $jam_display = date('H.i', strtotime($row['jam_mulai'])) . ' - ' . date('H.i', strtotime($row['jam_selesai']));
        $jadwal[] = [
            'id' => $row['id'],
            'matkul' => $row['nama_matkul'],
            'jam_text' => $jam_display,
            'ruangan_text' => $row['nama_ruangan'],
            'ruangan_id' => $row['ruangan_id'],
            'checked' => true // default checked
        ];
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
            'val' => $row['nama_ruangan'], // We send text to telegram, so value is content
            'label' => $row['nama_ruangan']
        ];
    }

    echo json_encode([
        'tomorrow' => $tomorrow_ind,
        'date' => date('d M Y', strtotime('+1 day')),
        'jadwal' => $jadwal,
        'opt_jam' => $opts_jam,
        'opt_ruang' => $opts_ruang
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
                                <button type="button" onclick="confirmTelegram()" class="bg-blue-600 text-white px-3 py-2 rounded-lg hover:bg-blue-800 text-center text-xs flex items-center justify-center" title="Kirim Telegram">
                                    <i class="fa fa-paper-plane"></i>
                                </button>


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

        // SweetAlert Confirmation for Telegram
        function confirmTelegram() {
            Swal.fire({
                title: 'Kirim Jadwal?',
                text: "Pilih metode pengiriman jadwal ke Telegram.",
                icon: 'question',
                showCancelButton: true,
                showDenyButton: true,
                confirmButtonColor: '#3085d6',
                denyButtonColor: '#f59e0b',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Kirim Langsung',
                denyButtonText: 'Sesuaikan Jadwal',
                cancelButtonText: 'Batal'
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = '../functions/kirim_jadwal.php';
                } else if (result.isDenied) {
                    fetchDataAndShowList();
                }
            });
        }

        function fetchDataAndShowList() {
            Swal.showLoading();
            fetch('?fetch_adjust_data=1')
                .then(r => r.json())
                .then(data => {
                    currentJadwal = data.jadwal;
                    optJam = data.opt_jam;
                    optRuang = data.opt_ruang;
                    renderJadwalList(data.tomorrow, data.date);
                })
                .catch(err => {
                    console.error(err);
                    Swal.fire('Error', 'Gagal mengambil data jadwal', 'error');
                });
        }

        function renderJadwalList(dayName, dateStr) {
            // Store date info globally if provided
            if (dayName) window.lastDayName = dayName;
            if (dateStr) window.lastDateStr = dateStr;

            // Use stored values if arguments missing (on re-render)
            dayName = dayName || window.lastDayName;
            dateStr = dateStr || window.lastDateStr;

            let html = `<div class="text-left text-sm mb-2">Hari: <b>${dayName}</b>, ${dateStr}</div>`;

            if (currentJadwal.length === 0) {
                html += '<p class="text-red-500 text-sm">Tidak ada jadwal untuk besok.</p>';
            } else {
                html += `<div class="overflow-x-auto max-h-64 border rounded-lg">
                    <table class="w-full text-xs text-left">
                        <thead class="bg-indigo-50 text-indigo-900 border-b">
                            <tr>
                                <th class="p-2 w-8 text-center">#</th>
                                <th class="p-2">Mata Kuliah</th>
                                <th class="p-2">Jam</th>
                                <th class="p-2">Ruangan</th>
                                <th class="p-2 w-16 text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y">`;

                currentJadwal.forEach((item, index) => {
                    const checked = item.checked ? 'checked' : '';
                    html += `<tr>
                        <td class="p-2 text-center"><input type="checkbox" onchange="toggleCheck(${index})" ${checked}></td>
                        <td class="p-2 font-medium">${item.matkul}</td>
                        <td class="p-2 text-slate-500">${item.jam_text}</td>
                        <td class="p-2 text-slate-500">${item.ruangan_text}</td>
                        <td class="p-2 text-center">
                            <button type="button" onclick="showEditForm(${index})" class="text-blue-600 hover:text-blue-800 bg-blue-50 hover:bg-blue-100 px-2 py-1 rounded">Edit</button>
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
                confirmButtonText: 'Kirim',
                cancelButtonText: 'Batal'
            }).then((result) => {
                if (result.isConfirmed) {
                    submitCustomJadwal();
                }
            });
        }

        function toggleCheck(index) {
            currentJadwal[index].checked = !currentJadwal[index].checked;
        }

        function showEditForm(index) {
            const item = currentJadwal[index];

            // Generate Jam Options
            let jamHtml = `<select id="edit_jam" class="w-full px-3 py-2 border rounded-md text-sm mb-3 focus:outline-none focus:ring-1 focus:ring-indigo-500">`;
            optJam.forEach(opt => {
                const selected = (opt.val === item.jam_text) ? 'selected' : '';
                jamHtml += `<option value="${opt.val}" ${selected}>${opt.label}</option>`;
            });
            jamHtml += `</select>`;

            // Generate Ruangan Options
            let ruangHtml = `<select id="edit_ruang" class="w-full px-3 py-2 border rounded-md text-sm focus:outline-none focus:ring-1 focus:ring-indigo-500">`;
            optRuang.forEach(opt => {
                const selected = (opt.val === item.ruangan_text) ? 'selected' : '';
                ruangHtml += `<option value="${opt.val}" ${selected}>${opt.label}</option>`;
            });
            ruangHtml += `</select>`;

            Swal.fire({
                title: 'Edit Item Jadwal',
                html: `
                    <div class="text-left">
                        <label class="block text-xs font-bold text-slate-700 mb-1">Mata Kuliah</label>
                        <input type="text" value="${item.matkul}" disabled class="w-full px-3 py-2 border bg-slate-100 rounded-md text-sm mb-3">
                        
                        <label class="block text-xs font-bold text-slate-700 mb-1">Jam</label>
                        ${jamHtml}
                        
                        <label class="block text-xs font-bold text-slate-700 mb-1">Ruangan</label>
                        ${ruangHtml}
                    </div>
                `,
                showCancelButton: true,
                confirmButtonText: 'Simpan',
                cancelButtonText: 'Batal',
                preConfirm: () => {
                    const newJam = document.getElementById('edit_jam').value;
                    const newRuang = document.getElementById('edit_ruang').value;
                    return {
                        jam: newJam,
                        ruang: newRuang
                    };
                }
            }).then((res) => {
                if (res.isConfirmed) {
                    // Update data
                    currentJadwal[index].jam_text = res.value.jam;
                    currentJadwal[index].ruangan_text = res.value.ruang;
                    // Re-render list
                    renderJadwalList();
                } else {
                    // Back to list without saving
                    renderJadwalList();
                }
            });
        }

        function submitCustomJadwal() {
            const selectedItems = currentJadwal.filter(i => i.checked).map(i => ({
                matkul: i.matkul,
                jam: i.jam_text,
                ruangan: i.ruangan_text
            }));

            if (selectedItems.length === 0) {
                Swal.fire('Peringatan', 'Pilih minimal satu jadwal untuk dikirim.', 'warning')
                    .then(() => renderJadwalList());
                return;
            }

            // Create hidden form to submit array
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = '../functions/kirim_jadwal.php';

            const inputAction = document.createElement('input');
            inputAction.type = 'hidden';
            inputAction.name = 'action';
            inputAction.value = 'kirim_custom';
            form.appendChild(inputAction);

            const inputPayload = document.createElement('input');
            inputPayload.type = 'hidden';
            inputPayload.name = 'custom_payload';
            inputPayload.value = JSON.stringify(selectedItems); // Send as JSON string
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