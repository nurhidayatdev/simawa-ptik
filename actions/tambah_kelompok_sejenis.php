<?php
session_start();
include '../koneksi.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../../index");
    exit;
}

$user_id = $_SESSION['user_id'];
$nama_lengkap = $_SESSION['nama_lengkap'];
$foto = $_SESSION['foto'];
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

// Handle save
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'save') {
    $mata_kuliah_id = intval($_POST['mata_kuliah_id'] ?? 0);
    $groups_json = $_POST['groups_json'] ?? '[]';
    $groups = json_decode($groups_json, true);

    if ($mata_kuliah_id && is_array($groups)) {
        $dosen_name = mysqli_real_escape_string($koneksi, $_POST['dosen'] ?? '');
        $keterangan = mysqli_real_escape_string($koneksi, $_POST['keterangan'] ?? '');

        if (empty($dosen_name)) {
            $_SESSION['error'] = "Silakan pilih dosen terlebih dahulu.";
            header("Location: tambah_kelompok_sejenis?matkul_id=$mata_kuliah_id");
            exit;
        }

        $where_k_chk = getKelasWhereClause('', true);
        $check = mysqli_query($koneksi, "SELECT id FROM kelompok WHERE matkul_id = $mata_kuliah_id AND periode_id = $periode_id AND dosen = '$dosen_name' AND keterangan = '$keterangan' $where_k_chk LIMIT 1");
        if (mysqli_num_rows($check) > 0) {
            $_SESSION['error'] = "Kelompok dengan dosen dan keterangan ini sudah ada.";
            header("Location: tambah_kelompok_sejenis?matkul_id=$mata_kuliah_id");
            exit;
        }

        // Get matkul name for notification
        $q_mk = mysqli_query($koneksi, "SELECT nama_matkul FROM mata_kuliah WHERE id = $mata_kuliah_id");
        $mk_data = mysqli_fetch_assoc($q_mk);
        $nama_matkul = $mk_data['nama_matkul'] ?? 'Mata Kuliah';
        $target_kelas_id = getKelasScope() ?? 1;

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
        // WHATSAPP NOTIFICATION
        // =====================
        include_once '../functions/kirim_kelompok.php';
        $res = kirimKelompokReminder($koneksi, $mata_kuliah_id, $dosen_name, $keterangan, 'new');

        $_SESSION['success'] = "Kelompok (Gender) berhasil disimpan " . ($res['status'] === 'success' ? "dan notifikasi telah dikirim ke WhatsApp." : "namun gagal mengirim notifikasi: " . ($res['message'] ?? ''));
        header("Location: ../views/kelompok");
        exit;
    } else {
        $_SESSION['error'] = "Data tidak valid.";
        header("Location: ../views/kelompok");
        exit;
    }
}

$where_jm_matkuls = getKelasWhereClause('jm', true);
$matkuls = mysqli_query($koneksi, "SELECT DISTINCT jm.matkul_id AS id, mk.nama_matkul
    FROM jadwal_matkul jm
    JOIN mata_kuliah mk ON jm.matkul_id = mk.id
    WHERE jm.periode_id = $periode_id $where_jm_matkuls
    ORDER BY mk.semester, mk.nama_matkul");

// Determine selected matkul from GET so we can load only mahasiswa yang mengambil matkul ini (dari krs)
$selected_matkul = intval($_GET['matkul_id'] ?? 0);
$students = [];

if ($selected_matkul) {
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

    $where_mhs_kelas = getKelasWhereClause('m', true);
    $stmt = mysqli_prepare($koneksi, "SELECT m.nim, m.nama_lengkap, m.jenis_kelamin, m.foto FROM krs k JOIN mahasiswa m ON k.mahasiswa_id = m.id WHERE k.matkul_id = ? AND k.periode_id = ? $where_mhs_kelas ORDER BY m.nim");
    mysqli_stmt_bind_param($stmt, 'ii', $selected_matkul, $periode_id);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    while ($r = mysqli_fetch_assoc($res)) {
        // Pre-calculate photo URL for JS usage
        $r['foto_url'] = profilePhotoUrl($r['foto'], '../');
        $students[] = $r;
    }
}

?>
<!DOCTYPE html>
<html lang="en">

<head>

    <title>Kelompok - SIMAWA PTIK 2024</title>
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

        /* live-search script removed from style block; see script at document end */
    </style>

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>

<body class="bg-slate-100 dark:bg-slate-950 transition-colors">
    <?php include '../includes/menu_db.php'; ?>

    <main class="ml-0 lg:ml-48 pt-20 p-6 flex flex-col min-h-screen">
        <div class="bg-white dark:bg-slate-900/50 rounded-xl border border-slate-200 dark:border-slate-800 p-6 flex-grow w-full shadow-sm transition-all">
            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-2">
                <div>
                    <h3 class="text-xl font-bold text-slate-800 dark:text-slate-100">Atur Kelompok Baru (Per Gender)</h3>
                    <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">Buat pembagian kelompok mahasiswa berdasarkan jenis kelamin (tidak dicampur).</p>
                </div>
                <a href="../views/kelompok" class="bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 px-4 py-2 rounded-lg hover:bg-slate-200 dark:hover:bg-slate-700 text-xs font-bold transition-all shadow-sm">
                    <i class="fa fa-arrow-left mr-2"></i> Kembali
                </a>
            </div>

            <div id="statusAlert" class="mb-4 hidden">
                <?php if (isset($existing_data) && $existing_data): ?>
                    <div class="p-3 bg-red-50 dark:bg-red-900/20 text-red-600 dark:text-red-400 text-[10px] font-bold uppercase tracking-wider rounded-lg border border-red-100 dark:border-red-900/30">
                        <i class="fa fa-exclamation-triangle mr-2"></i> PERHATIAN: Kelompok untuk mata kuliah ini sudah dibuat.
                    </div>
                <?php endif; ?>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 my-8">
                <!-- Pemilihan Mata Kuliah -->
                <div class="lg:col-span-1">
                    <form id="selectForm" method="GET">
                        <label class="text-[10px] font-bold text-slate-400 dark:text-slate-500 uppercase tracking-widest block mb-2 px-1">Mata Kuliah</label>
                        <div class="relative">
                            <select id="mata_kuliah" name="matkul_id" class="w-full bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-xs font-bold text-indigo-700 dark:text-indigo-400 rounded-lg px-4 py-3 appearance-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 cursor-pointer shadow-sm transition-all outline-none">
                                <option value="">-- Pilih Mata Kuliah --</option>
                                <?php while ($mk = mysqli_fetch_assoc($matkuls)) : ?>
                                    <option value="<?= $mk['id'] ?>" <?= ($mk['id'] == $selected_matkul) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($mk['nama_matkul']) ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                            <div class="absolute inset-y-0 right-0 flex items-center px-3 pointer-events-none text-slate-400 dark:text-slate-500">
                                <i class="fa fa-chevron-down text-[10px]"></i>
                            </div>
                        </div>
                    </form>
                </div>

                <!-- Konfigurasi Kelompok -->
                <div class="lg:col-span-2">
                    <form id="kelompokForm" method="POST">
                        <input type="hidden" name="action" value="save">
                        <input type="hidden" name="mata_kuliah_id" id="mata_kuliah_hidden" value="<?= $selected_matkul ?>">
                        <input type="hidden" name="groups_json" id="groups_json" value="[]">

                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                            <div>
                                <label class="text-[10px] font-bold text-slate-400 dark:text-slate-500 uppercase tracking-widest block mb-2 px-1">Dosen Pengampu</label>
                                <div class="relative">
                                    <select name="dosen" id="dosen" class="w-full bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-xs font-bold text-slate-700 dark:text-slate-200 rounded-lg px-4 py-3 appearance-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 cursor-pointer shadow-sm transition-all outline-none" required>
                                        <option value="">-- Pilih Dosen --</option>
                                        <?php foreach ($lecturers as $l): ?>
                                            <option value="<?= htmlspecialchars($l) ?>"><?= htmlspecialchars($l) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                    <div class="absolute inset-y-0 right-0 flex items-center px-3 pointer-events-none text-slate-400 dark:text-slate-500">
                                        <i class="fa fa-chevron-down text-[10px]"></i>
                                    </div>
                                </div>
                            </div>

                            <div>
                                <label class="text-[10px] font-bold text-slate-400 dark:text-slate-500 uppercase tracking-widest block mb-2 px-1">Keterangan (Opsional)</label>
                                <input name="keterangan" id="keterangan" type="text" placeholder="Contoh: Tugas 1" class="w-full bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-xs font-medium text-slate-700 dark:text-slate-200 rounded-lg px-4 py-3 focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 shadow-sm transition-all outline-none" />
                            </div>

                            <div>
                                <label class="text-[10px] font-bold text-slate-400 dark:text-slate-500 uppercase tracking-widest block mb-1 px-1">Anggota per Kelompok</label>
                                <input id="per_group" type="number" min="1" max="17" value="3" class="w-full bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-xs font-bold text-slate-700 dark:text-slate-200 rounded-lg px-4 py-3 focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 shadow-sm transition-all outline-none" />
                            </div>
                        </div>

                        <div class="flex flex-wrap gap-3 mt-6 justify-end">
                            <button id="acakBtn" type="button" class="bg-amber-500 hover:bg-amber-600 text-white px-5 py-2.5 rounded-lg text-xs font-bold flex items-center gap-2 transition-all shadow-sm">
                                <i class="fa fa-random"></i> Acak Per Gender
                            </button>
                            <button id="manualBtn" type="button" class="bg-sky-500 hover:bg-sky-600 text-white px-5 py-2.5 rounded-lg text-xs font-bold flex items-center gap-2 transition-all shadow-sm">
                                <i class="fa fa-hand-pointer"></i> Atur Manual
                            </button>
                            <button id="simpanBtn" type="submit" class="bg-indigo-600 hover:bg-indigo-700 text-white px-8 py-2.5 rounded-lg text-xs font-bold flex items-center gap-2 transition-all shadow-lg">
                                <i class="fa fa-save"></i> Simpan Kelompok
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
        // Students data from PHP (only those who took selected matkul via KRS)
        const students = <?= json_encode($students, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) ?>;

        // Urutkan: Perempuan dulu di atas
        students.sort((a, b) => {
            if (a.jenis_kelamin === 'Perempuan' && b.jenis_kelamin !== 'Perempuan') return -1;
            if (a.jenis_kelamin !== 'Perempuan' && b.jenis_kelamin === 'Perempuan') return 1;
            return 0;
        });

        function shuffleArray(array) {
            for (let i = array.length - 1; i > 0; i--) {
                const j = Math.floor(Math.random() * (i + 1));
                [array[i], array[j]] = [array[j], array[i]];
            }
        }

        function makeGroups(perGroup) {
            const boys = students.filter(s => s.jenis_kelamin === 'Laki-laki');
            const girls = students.filter(s => s.jenis_kelamin === 'Perempuan');
            const others = students.filter(s => s.jenis_kelamin !== 'Laki-laki' && s.jenis_kelamin !== 'Perempuan');

            shuffleArray(boys);
            shuffleArray(girls);
            shuffleArray(others);

            const groups = [];

            // Helper function to split array into chunks
            function chunk(array, size) {
                const results = [];
                for (let i = 0; i < array.length; i += size) {
                    results.push(array.slice(i, i + size));
                }
                return results;
            }

            // Distribute girls into groups (only girls)
            const girlGroups = chunk(girls, perGroup);
            groups.push(...girlGroups);

            // Distribute boys into groups (only boys)
            const boyGroups = chunk(boys, perGroup);
            groups.push(...boyGroups);

            // Distribute others into groups (only others)
            const otherGroups = chunk(others, perGroup);
            groups.push(...otherGroups);

            return groups;
        }

        function renderGroups(groups) {
            currentGroups = groups;
            const container = document.getElementById('groupsContainer');
            container.innerHTML = '';
            groups.forEach((g, idx) => {
                const div = document.createElement('div');
                div.className = 'bg-white dark:bg-slate-800/50 rounded-xl border border-slate-200 dark:border-slate-700 overflow-hidden hover:shadow-md transition-all group-card';

                let membersHtml = '';
                g.forEach(s => {
                    membersHtml += `
                        <li class="flex items-center gap-3">
                            <div class="h-8 w-8 rounded-full overflow-hidden border border-slate-200 dark:border-slate-700 shrink-0 shadow-sm">
                                <img src="${s.foto_url}" class="w-full h-full object-cover">
                            </div>
                            <div class="flex flex-col truncate">
                                <span class="text-[11px] font-semibold text-slate-700 dark:text-slate-200 truncate">${s.nama_lengkap}</span>
                                <span class="text-[9px] text-slate-400 dark:text-slate-500 capitalize">${s.jenis_kelamin}</span>
                            </div>
                        </li>
                    `;
                });

                const genderLabel = g.length > 0 ? (g[0].jenis_kelamin === 'Laki-laki' ? '♂️ Laki-laki' : '♀️ Perempuan') : 'Kosong';
                const genderColor = g.length > 0 ? (g[0].jenis_kelamin === 'Laki-laki' ? 'text-blue-600 dark:text-blue-400' : 'text-pink-600 dark:text-pink-400') : 'text-slate-400 dark:text-slate-600';

                div.innerHTML = `
                    <div class="bg-slate-50/50 dark:bg-slate-800/50 px-4 py-3 border-b border-slate-100 dark:border-slate-700 flex justify-between items-center transition-colors">
                        <h4 class="font-bold text-indigo-900 dark:text-indigo-400 text-sm">Kelompok ${idx + 1}</h4>
                        <span class="text-[9px] font-bold uppercase tracking-wider ${genderColor}">${genderLabel}</span>
                    </div>
                    <div class="p-4">
                        <ul class="space-y-3">
                            ${membersHtml}
                        </ul>
                    </div>
                `;
                container.appendChild(div);
            });
            document.getElementById('groups_json').value = JSON.stringify(groups);
        }

        // Render editable groups for manual creation
        let currentGroups = [];

        function renderEditable(groups) {
            currentGroups = groups;
            const container = document.getElementById('groupsContainer');
            container.innerHTML = '';

            function getAvailableStudents() {
                const used = {};
                currentGroups.forEach(g => g.forEach(m => {
                    if (m && m.nim) used[m.nim] = true;
                }));
                return students.filter(s => !used[s.nim]);
            }

            groups.forEach((g, idx) => {
                const div = document.createElement('div');
                div.className = 'bg-white dark:bg-slate-800/50 rounded-xl border border-slate-200 dark:border-slate-700 overflow-hidden hover:shadow-md transition-all group-card flex flex-col';

                let membersHtml = '';
                (g || []).forEach((s, si) => {
                    membersHtml += `
                        <li class="flex items-center justify-between gap-3 group/item">
                            <div class="flex items-center gap-3 truncate">
                                <div class="h-8 w-8 rounded-full overflow-hidden border border-slate-200 dark:border-slate-700 shrink-0 shadow-sm">
                                    <img src="${s.foto_url}" class="w-full h-full object-cover">
                                </div>
                                <div class="flex flex-col truncate">
                                    <span class="text-[11px] font-semibold text-slate-700 dark:text-slate-200 truncate">${s.nama_lengkap}</span>
                                    <span class="text-[9px] text-slate-400 dark:text-slate-500 capitalize">${s.jenis_kelamin}</span>
                                </div>
                            </div>
                            <button type="button" onclick="removeMember(${idx}, ${si})" class="text-red-400 hover:text-red-600 transition-colors p-1 opacity-0 group-hover/item:opacity-100">
                                <i class="fa fa-times-circle"></i>
                            </button>
                        </li>
                    `;
                });

                const genderLabel = g.length > 0 ? (g[0].jenis_kelamin === 'Laki-laki' ? '♂️ Laki-laki' : '♀️ Perempuan') : 'Belum Ditentukan';
                const genderColor = g.length > 0 ? (g[0].jenis_kelamin === 'Laki-laki' ? 'text-blue-600 dark:text-blue-400' : 'text-pink-600 dark:text-pink-400') : 'text-slate-400 dark:text-slate-600';

                div.innerHTML = `
                    <div class="bg-slate-50/50 dark:bg-slate-800/50 px-4 py-3 border-b border-slate-100 dark:border-slate-700 flex justify-between items-center transition-colors">
                        <h4 class="font-bold text-indigo-900 dark:text-indigo-400 text-sm">Kelompok ${idx + 1}</h4>
                        <span class="text-[9px] font-bold uppercase tracking-wider ${genderColor}">${genderLabel}</span>
                    </div>
                    <div class="p-4 flex-grow">
                        <ul class="space-y-3 mb-4">
                            ${membersHtml}
                        </ul>
                    </div>
                    <div class="p-3 bg-slate-50/50 dark:bg-slate-800 border-t border-slate-100 dark:border-slate-700 transition-colors">
                        <div class="flex flex-col gap-2">
                            <select class="student-select w-full bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 text-[10px] font-medium text-slate-700 dark:text-slate-200 rounded-md px-2 py-1.5 focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 shadow-sm transition-all outline-none">
                                <option value="">+ Tambah Anggota</option>
                                ${getAvailableStudents().map(s => `<option value="${s.nim}">${s.nama_lengkap} (${s.jenis_kelamin})</option>`).join('')}
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
                // Check gender consistency
                if (currentGroups[idx].length > 0 && currentGroups[idx][0].jenis_kelamin !== stu.jenis_kelamin) {
                    Swal.fire({
                        icon: 'warning',
                        title: 'Peringatan Gender',
                        text: 'Kelompok ini sudah berisi ' + currentGroups[idx][0].jenis_kelamin + '. Anda tidak boleh mencampur jenis kelamin.'
                    });
                    return;
                }
                currentGroups[idx].push(stu);
                renderEditable(currentGroups);
            }
        };

        document.getElementById('acakBtn').addEventListener('click', () => {
            const per = parseInt(document.getElementById('per_group').value) || 1;
            const groups = makeGroups(per);
            currentGroups = groups;
            renderGroups(groups);
        });

        document.getElementById('manualBtn').addEventListener('click', () => {
            // If currentGroups already has data, just render it as editable
            if (currentGroups && currentGroups.length > 0) {
                renderEditable(currentGroups);
                return;
            }

            const per = parseInt(document.getElementById('per_group').value) || 1;
            const n = Math.max(1, Math.ceil((students.length || 0) / per));
            const groups = [];
            for (let i = 0; i < n; i++) groups.push([]);
            renderEditable(groups);
        });

        // Auto-submit select to reload students list
        document.getElementById('mata_kuliah').addEventListener('change', function() {
            document.getElementById('selectForm').submit();
        });

        // Ensure hidden matkul id updates before save
        document.getElementById('kelompokForm').addEventListener('submit', function(e) {
            const selectVal = document.getElementById('mata_kuliah').value || document.getElementById('mata_kuliah_hidden').value;
            document.getElementById('mata_kuliah_hidden').value = selectVal;

            // PHP level check also exists, but let's be nice to the user
            <?php if (isset($existing_data) && $existing_data): ?>
                e.preventDefault();
                Swal.fire({
                    icon: 'error',
                    title: 'Sudah Ada!',
                    text: 'Kelompok untuk mata kuliah ini sudah ada. Silakan gunakan menu Edit.'
                });
                return false;
            <?php endif; ?>
        });

        // When form submits, ensure a matkul is selected and groups_json present
        document.getElementById('kelompokForm').addEventListener('submit', function(e) {
            const matkul = document.getElementById('mata_kuliah').value;
            if (!matkul) {
                e.preventDefault();
                alert('Pilih mata kuliah terlebih dahulu.');
                return false;
            }
            const groupsVal = document.getElementById('groups_json').value || '[]';
            const groups = JSON.parse(groupsVal);
            if (!Array.isArray(groups) || groups.length === 0) {
                e.preventDefault();
                alert('Belum ada pembagian kelompok. Tekan tombol "Acak" terlebih dahulu.');
                return false;
            }

            // Final check: ensure no mixed gender groups
            for (let i = 0; i < groups.length; i++) {
                if (groups[i].length > 1) {
                    const firstGender = groups[i][0].jenis_kelamin;
                    for (let j = 1; j < groups[i].length; j++) {
                        if (groups[i][j].jenis_kelamin !== firstGender) {
                            e.preventDefault();
                            Swal.fire({
                                icon: 'error',
                                title: 'Kesalahan Gender',
                                text: 'Terdapat kelompok (Kelompok ' + (i + 1) + ') yang memiliki campuran jenis kelamin. Harap atur ulang.'
                            });
                            return false;
                        }
                    }
                }
            }

            return true;
        });

        // SweetAlert2 Session Messages
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

        <?php if (isset($_SESSION['error'])): ?>
            Swal.fire({
                icon: 'error',
                title: 'Gagal!',
                text: '<?= $_SESSION['error'] ?>',
                timer: 3000,
                showConfirmButton: false
            });
            <?php unset($_SESSION['error']); ?>
        <?php endif; ?>
    </script>

</body>

</html>