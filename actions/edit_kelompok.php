<?php
session_start();
include '../koneksi.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../../index");
    exit;
}

$user_id = $_SESSION['user_id'];
$nama_lengkap = $_SESSION['nama_lengkap'];
$foto = $_SESSION['foto'] ?? '';
$role = $_SESSION['role'];

// Include foto helpers for cache busting
include_once '../functions/foto_helpers.php';

if ($_SESSION['role'] === 'Mahasiswa' || $_SESSION['role'] === 'Bendahara' || $_SESSION['role'] === 'Sekretaris') {
    include '../includes/alert_role.php';
    exit();
}

/* =====================
   AMBIL PERIODE AKTIF
   ===================== */
$q_periode = mysqli_query($koneksi, "SELECT id FROM periode WHERE status = 'aktif' LIMIT 1");
$periode = mysqli_fetch_assoc($q_periode);
$periode_id = $periode['id'];

// Handle save (Edit Mode)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'save') {
    $mata_kuliah_id = intval($_POST['mata_kuliah_id'] ?? 0);
    $groups_json = $_POST['groups_json'] ?? '[]';
    $groups = json_decode($groups_json, true);

    if ($mata_kuliah_id && is_array($groups)) {
        $dosen_name = mysqli_real_escape_string($koneksi, $_POST['dosen'] ?? '');
        $keterangan = mysqli_real_escape_string($koneksi, $_POST['keterangan'] ?? '');

        // Get matkul name for notification
        $q_mk = mysqli_query($koneksi, "SELECT nama_matkul FROM mata_kuliah WHERE id = $mata_kuliah_id");
        $mk_data = mysqli_fetch_assoc($q_mk);
        $nama_matkul = $mk_data['nama_matkul'] ?? 'Mata Kuliah';

        $target_kelas_id = getKelasScope() ?? 1;
        $q_chk = mysqli_query($koneksi, "SELECT kelas_id FROM kelompok WHERE matkul_id = $mata_kuliah_id AND periode_id = $periode_id AND dosen = '$dosen_name' AND keterangan = '$keterangan' LIMIT 1");
        if ($r_chk = mysqli_fetch_assoc($q_chk)) {
            if (!empty($r_chk['kelas_id'])) {
                requireKelasAccess($r_chk['kelas_id']);
                $target_kelas_id = (int)$r_chk['kelas_id'];
            }
        }

        // Remove existing groups for this specific set
        $where_k_del = getKelasWhereClause('', true);
        $stmt = mysqli_prepare($koneksi, "DELETE FROM kelompok WHERE matkul_id = ? AND periode_id = ? AND dosen = ? AND keterangan = ? $where_k_del");
        mysqli_stmt_bind_param($stmt, 'iiss', $mata_kuliah_id, $periode_id, $dosen_name, $keterangan);
        mysqli_stmt_execute($stmt);

        // Insert new groups
        $insert = mysqli_prepare($koneksi, "INSERT INTO kelompok (matkul_id, kelompok_no, anggota, periode_id, dosen, keterangan, kelas_id) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $message_groups = "";

        $kel_count = 1;
        foreach ($groups as $no => $anggotaArr) {
            if (empty($anggotaArr)) continue;

            $anggotaStr = implode(',', array_map(function ($s) {
                return $s['nim'];
            }, $anggotaArr));

            mysqli_stmt_bind_param($insert, 'iisissi', $mata_kuliah_id, $kel_count, $anggotaStr, $periode_id, $dosen_name, $keterangan, $target_kelas_id);
            mysqli_stmt_execute($insert);

            // Format message for WhatsApp
            $namesArr = array_map(function ($s) {
                return "• " . $s['nama_lengkap'];
            }, $anggotaArr);
            $message_groups .= "👥 *Kelompok {$kel_count}*\n" . implode("\n", $namesArr) . "\n\n";
            $kel_count++;
        }

        // =====================
        // WHATSAPP NOTIFICATION (Edit Mode)
        // =====================
        include_once '../functions/kirim_kelompok.php';
        $res = kirimKelompokReminder($koneksi, $mata_kuliah_id, $dosen_name, $keterangan, 'edit');

        $_SESSION['success'] = "Kelompok berhasil diperbarui " . ($res['status'] === 'success' ? "dan notifikasi telah dikirim ke WhatsApp." : "namun gagal mengirim notifikasi: " . ($res['message'] ?? ''));
        header("Location: ../views/kelompok?matkul_id=$mata_kuliah_id&dosen=" . urlencode($dosen_name) . "&keterangan=" . urlencode($keterangan));
        exit;
    }
}

$where_jm_matkuls = getKelasWhereClause('jm', true);
$matkuls = mysqli_query($koneksi, "SELECT DISTINCT jm.matkul_id AS id, mk.nama_matkul
    FROM jadwal_matkul jm
    JOIN mata_kuliah mk ON jm.matkul_id = mk.id
    WHERE jm.periode_id = $periode_id $where_jm_matkuls
    ORDER BY mk.semester, mk.nama_matkul");

// Determine selected matkul
$selected_matkul = intval($_GET['matkul_id'] ?? 0);
$selected_dosen = $_GET['dosen'] ?? '';
$selected_keterangan = $_GET['keterangan'] ?? '';

$students = [];
$existing_groups = [];

if ($selected_matkul && !empty($selected_dosen)) {
    // Load students
    $where_mhs_kelas = getKelasWhereClause('m', true);
    $stmt = mysqli_prepare($koneksi, "SELECT m.nim, m.nama_lengkap, m.jenis_kelamin FROM krs k JOIN mahasiswa m ON k.mahasiswa_id = m.id WHERE k.matkul_id = ? AND k.periode_id = ? $where_mhs_kelas ORDER BY m.nim");
    mysqli_stmt_bind_param($stmt, 'ii', $selected_matkul, $periode_id);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    while ($r = mysqli_fetch_assoc($res)) {
        // Pre-calculate photo URL for JS usage
        $r['foto_url'] = profilePhotoUrl($r['nama_lengkap'], '../');
        $students[] = $r;
    }

    // Get lecturers from schedule
    $lecturers = [];
    $q_lecturers = mysqli_query($koneksi, "
        SELECT DISTINCT d.nama_dosen 
        FROM jadwal_matkul jm
        LEFT JOIN dosen d ON jm.dosen_pengampu_id = d.id
        WHERE jm.matkul_id = $selected_matkul AND jm.periode_id = $periode_id
        UNION
        SELECT DISTINCT d.nama_dosen 
        FROM jadwal_matkul jm
        LEFT JOIN dosen d ON jm.dosen_mitra_id = d.id
        WHERE jm.matkul_id = $selected_matkul AND jm.periode_id = $periode_id
    ");
    while ($rl = mysqli_fetch_assoc($q_lecturers)) {
        if ($rl['nama_dosen']) $lecturers[] = $rl['nama_dosen'];
    }

    // Load existing groups
    $where_k_stmt_g = getKelasWhereClause('', true);
    $stmt_g = mysqli_prepare($koneksi, "SELECT kelompok_no, anggota FROM kelompok WHERE matkul_id = ? AND periode_id = ? AND dosen = ? AND keterangan = ? $where_k_stmt_g ORDER BY kelompok_no");
    mysqli_stmt_bind_param($stmt_g, 'iiss', $selected_matkul, $periode_id, $selected_dosen, $selected_keterangan);
    mysqli_stmt_execute($stmt_g);
    $res_g = mysqli_stmt_get_result($stmt_g);

    $student_map = [];
    foreach ($students as $s) {
        $student_map[$s['nim']] = [
            'nama' => $s['nama_lengkap'],
            
        ];
    }

    while ($r = mysqli_fetch_assoc($res_g)) {
        $nims = explode(',', $r['anggota']);
        $group_members = [];
        foreach ($nims as $nim) {
            $s_data = $student_map[$nim] ?? ['nama' => $nim, 'foto' => ''];
            $group_members[] = ['nim' => $nim, 'nama_lengkap' => $s_data['nama'], 'foto_url' => profilePhotoUrl($s_data['nama'], '../')];
        }
        $existing_groups[] = $group_members;
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>

    <title>Edit Kelompok - SIMAWA PTIK 2024</title>
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
            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-2">
                <div>
                    <h3 class="text-xl font-bold text-slate-800 dark:text-slate-100">Edit Kelompok</h3>
                    <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">Ubah atau acak ulang pembagian kelompok mahasiswa.</p>
                </div>
                <a href="../views/kelompok?matkul_id=<?= $selected_matkul ?>&dosen=<?= urlencode($selected_dosen) ?>&keterangan=<?= urlencode($selected_keterangan) ?>" class="bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400 px-4 py-2 rounded-lg hover:bg-slate-200 dark:hover:bg-slate-700 text-xs font-bold transition-all shadow-sm">
                    <i class="fa fa-arrow-left mr-2"></i> Kembali
                </a>
            </div>


            <div class="grid grid-cols-1 gap-6 my-8">
                <!-- Konfigurasi Edit -->
                <div class="w-full">
                    <form id="kelompokForm" method="POST">
                        <input type="hidden" name="action" value="save">
                        <input type="hidden" name="mata_kuliah_id" value="<?= $selected_matkul ?>">
                        <input type="hidden" name="groups_json" id="groups_json">

                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6">
                            <div>
                                <label class="text-[10px] font-bold text-slate-400 dark:text-slate-500 uppercase tracking-widest block mb-2 px-1">Dosen Pengampu</label>
                                <div class="relative">
                                    <select name="dosen" id="dosen" class="w-full bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-xs font-bold text-indigo-700 dark:text-indigo-400 rounded-lg px-4 py-3 appearance-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 cursor-pointer shadow-sm transition-all" required>
                                        <option value="">-- Pilih Dosen --</option>
                                        <?php foreach ($lecturers as $l): ?>
                                            <option value="<?= htmlspecialchars($l) ?>" <?= ($l == $selected_dosen) ? 'selected' : '' ?>><?= htmlspecialchars($l) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                    <div class="absolute inset-y-0 right-0 flex items-center px-3 pointer-events-none text-slate-400 dark:text-slate-600">
                                        <i class="fa fa-chevron-down text-[10px]"></i>
                                    </div>
                                </div>
                            </div>

                            <div>
                                <label class="text-[10px] font-bold text-slate-400 dark:text-slate-500 uppercase tracking-widest block mb-2 px-1">Keterangan (Opsional)</label>
                                <input name="keterangan" id="keterangan" type="text" value="<?= htmlspecialchars($selected_keterangan) ?>" placeholder="Contoh: Tugas 1" class="w-full bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-xs font-medium text-slate-700 dark:text-slate-200 rounded-lg px-4 py-3 focus:ring-2 focus:ring-indigo-500/20 dark:focus:ring-indigo-600/20 focus:border-indigo-500 shadow-sm transition-all" />
                            </div>

                            <div>
                                <label class="text-[10px] font-bold text-slate-400 dark:text-slate-500 uppercase tracking-widest block mb-2 px-1">Anggota per Kelompok</label>
                                <input id="per_group" type="number" min="1" max="15" value="3" class="w-full bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-xs font-bold text-slate-700 dark:text-slate-200 rounded-lg px-4 py-3 focus:ring-2 focus:ring-indigo-500/20 dark:focus:ring-indigo-600/20 focus:border-indigo-500 shadow-sm transition-all" />
                            </div>
                        </div>

                        <div class="flex flex-wrap gap-3 justify-end items-center">
                            <button id="acakBtn" type="button" class="bg-amber-500 hover:bg-amber-600 text-white px-5 py-2.5 rounded-lg text-xs font-bold flex items-center gap-2 transition-all shadow-sm">
                                <i class="fa fa-random"></i> Acak Ulang
                            </button>
                            <button id="manualBtn" type="button" class="bg-sky-500 hover:bg-sky-600 text-white px-5 py-2.5 rounded-lg text-xs font-bold flex items-center gap-2 transition-all shadow-sm">
                                <i class="fa fa-hand-pointer"></i> Atur Manual
                            </button>
                            <button type="submit" class="bg-indigo-600 hover:bg-indigo-700 text-white px-8 py-2.5 rounded-lg text-xs font-bold flex items-center gap-2 transition-all shadow-lg">
                                <i class="fa fa-save"></i> Simpan Perubahan
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <div id="groupsContainer" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4 mt-8"></div>
        </div>

        <?php include '../includes/footer_db.php'; ?>
    </main>
    <?php include '../includes/script_db.php'; ?>

    <script>
        const students = <?= json_encode($students) ?>;
        const existingGroups = <?= json_encode($existing_groups) ?>;
        let currentGroups = [];

        window.addEventListener('DOMContentLoaded', () => {
            if (existingGroups && existingGroups.length > 0) {
                renderEditable(existingGroups);
            }
        });

        function shuffleArray(array) {
            for (let i = array.length - 1; i > 0; i--) {
                const j = Math.floor(Math.random() * (i + 1));
                [array[i], array[j]] = [array[j], array[i]];
            }
        }

        function renderEditable(groups) {
            currentGroups = groups;
            const container = document.getElementById('groupsContainer');
            container.innerHTML = '';

            const used = {};
            currentGroups.forEach(g => g.forEach(s => {
                if (s && s.nim) used[s.nim] = true;
            }));
            const available = students.filter(s => !used[s.nim]);

            groups.forEach((g, idx) => {
                const div = document.createElement('div');
                div.className = 'bg-white dark:bg-slate-800/50 rounded-xl border border-slate-200 dark:border-slate-700 overflow-hidden hover:shadow-md transition-all group-card flex flex-col';

                let membersHtml = '';
                g.forEach((s, si) => {
                    membersHtml += `
                        <li class="flex items-center justify-between gap-3 group/item">
                            <div class="flex items-center gap-3 truncate">
                                <div class="h-8 w-8 rounded-full overflow-hidden border border-slate-200 dark:border-slate-700 shrink-0 shadow-sm">
                                    <img src="${s.foto_url}" class="w-full h-full object-cover">
                                </div>
                                <span class="text-[11px] font-semibold text-slate-700 dark:text-slate-300 truncate">${s.nama_lengkap}</span>
                            </div>
                            <button type="button" onclick="removeMember(${idx}, ${si})" class="text-red-400 dark:text-red-500 hover:text-red-600 dark:hover:text-red-400 transition-colors p-1 opacity-0 group-hover/item:opacity-100">
                                <i class="fa fa-times-circle"></i>
                            </button>
                        </li>
                    `;
                });

                div.innerHTML = `
                    <div class="bg-slate-50 dark:bg-slate-800 px-4 py-3 border-b border-slate-100 dark:border-slate-700">
                        <h4 class="font-bold text-indigo-900 dark:text-indigo-400 text-sm">Kelompok ${idx+1}</h4>
                    </div>
                    <div class="p-4 flex-grow">
                        <ul class="space-y-3 mb-4">
                            ${membersHtml}
                        </ul>
                    </div>
                    <div class="p-3 bg-slate-50 dark:bg-slate-800 border-t border-slate-100 dark:border-slate-700">
                        <div class="flex flex-col gap-2">
                            <select class="student-select w-full bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 text-[10px] font-medium text-slate-700 dark:text-slate-300 rounded-md px-2 py-1.5 focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 shadow-sm transition-all">
                                <option value="">+ Tambah Anggota</option>
                                ${available.map(s => `<option value="${s.nim}">${s.nama_lengkap}</option>`).join('')}
                            </select>
                            <button type="button" onclick="addMemberManual(${idx}, this)" class="bg-indigo-600 hover:bg-indigo-700 text-white px-3 py-1.5 rounded-md text-[10px] font-bold transition-all shadow-sm">
                                + Tambah
                            </button>
                        </div>
                    </div>
                `;
                container.appendChild(div);
            });
            document.getElementById('groups_json').value = JSON.stringify(currentGroups);
        }

        window.removeMember = function(gIdx, si) {
            currentGroups[gIdx].splice(si, 1);
            renderEditable(currentGroups);
        };

        window.addMemberManual = function(idx, btn) {
            const select = btn.parentElement.querySelector('.student-select');
            const val = select.value;
            if (!val) return;
            const stu = students.find(x => x.nim === val);
            if (stu) {
                currentGroups[idx].push(stu);
                renderEditable(currentGroups);
            }
        };

        document.getElementById('acakBtn').onclick = () => {
            const per = parseInt(document.getElementById('per_group').value) || 1;
            const boys = students.filter(s => s.jenis_kelamin === 'Laki-laki');
            const girls = students.filter(s => s.jenis_kelamin === 'Perempuan');
            const others = students.filter(s => s.jenis_kelamin !== 'Laki-laki' && s.jenis_kelamin !== 'Perempuan');

            shuffleArray(boys);
            shuffleArray(girls);
            shuffleArray(others);

            const numGroups = Math.ceil(students.length / per);
            const groups = Array.from({
                length: numGroups
            }, () => []);

            // Distribute boys
            let groupIdx = 0;
            boys.forEach(b => {
                groups[groupIdx].push(b);
                groupIdx = (groupIdx + 1) % numGroups;
            });

            // Distribute girls
            groupIdx = 0;
            girls.forEach(g => {
                let searchCount = 0;
                while (groups[groupIdx].length >= per && searchCount < numGroups) {
                    groupIdx = (groupIdx + 1) % numGroups;
                    searchCount++;
                }
                groups[groupIdx].push(g);
                groupIdx = (groupIdx + 1) % numGroups;
            });

            // Distribute others
            groupIdx = 0;
            others.forEach(o => {
                let searchCount = 0;
                while (groups[groupIdx].length >= per && searchCount < numGroups) {
                    groupIdx = (groupIdx + 1) % numGroups;
                    searchCount++;
                }
                groups[groupIdx].push(o);
                groupIdx = (groupIdx + 1) % numGroups;
            });

            renderEditable(groups);
        };

        document.getElementById('manualBtn').onclick = () => {
            // If currentGroups already has data, just render it as editable
            if (currentGroups && currentGroups.length > 0) {
                renderEditable(currentGroups);
                return;
            }

            const per = parseInt(document.getElementById('per_group').value) || 1;
            const n = Math.ceil(students.length / per) || 1;
            const groups = Array.from({
                length: n
            }, () => []);
            renderEditable(groups);
        };

        document.getElementById('kelompokForm').onsubmit = (e) => {
            if (!document.getElementById('groups_json').value || JSON.parse(document.getElementById('groups_json').value).length === 0) {
                e.preventDefault();
                alert('Kelompok belum diatur!');
            }
        };

        // Sidebar/Mobile logic inherited
        const mobileBtn = document.getElementById('mobile-menu-button');
        const side = document.getElementById('sidebar');
        mobileBtn.onclick = () => {
            side.classList.toggle('-translate-x-full');
            side.classList.toggle('mobile-menu-open');
        };
    </script>
</body>

</html>