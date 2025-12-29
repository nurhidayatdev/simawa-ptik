<?php
session_start();
include '../../koneksi.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../../index.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$nama_lengkap = $_SESSION['nama_lengkap'];
$foto = $_SESSION['foto'];
$role = $_SESSION['role'];

if ($_SESSION['role'] === 'Mahasiswa' || $_SESSION['role'] === 'Bendahara' || $_SESSION['role'] === 'Sekretaris') {
    echo "<!DOCTYPE html>
    <html>
    <head>
        <script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script>
    </head>
    <body>
        <script>
            Swal.fire({
                icon: 'error',
                title: 'Akses Ditolak!',
                text: 'Hanya Admin yang dapat mengakses halaman ini.',
                confirmButtonColor: '#3085d6',
                confirmButtonText: 'Kembali'
            }).then((result) => {
                window.location.href = '../../index.php';
            });
        </script>
    </body>
    </html>";
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
        // Get matkul name for notification
        $q_mk = mysqli_query($koneksi, "SELECT nama_matkul FROM mata_kuliah WHERE id = $mata_kuliah_id");
        $mk_data = mysqli_fetch_assoc($q_mk);
        $nama_matkul = $mk_data['nama_matkul'] ?? 'Mata Kuliah';

        // Remove existing groups for this mata kuliah and period
        $stmt = mysqli_prepare($koneksi, "DELETE FROM kelompok WHERE matkul_id = ? AND periode_id = ?");
        mysqli_stmt_bind_param($stmt, 'ii', $mata_kuliah_id, $periode_id);
        mysqli_stmt_execute($stmt);

        // Insert new groups
        $insert = mysqli_prepare($koneksi, "INSERT INTO kelompok (matkul_id, kelompok_no, anggota, periode_id) VALUES (?, ?, ?, ?)");
        $message_groups = "";
        
        foreach ($groups as $no => $anggotaArr) {
            $anggotaStr = implode(',', array_map(function($s){ return $s['nim']; }, $anggotaArr));
            $kel_no = $no + 1;
            mysqli_stmt_bind_param($insert, 'iisi', $mata_kuliah_id, $kel_no, $anggotaStr, $periode_id);
            mysqli_stmt_execute($insert);

            // Format message for Telegram
            $namesArr = array_map(function($s){ return "• " . $s['nama_lengkap']; }, $anggotaArr);
            $message_groups .= "👥 *Kelompok {$kel_no}*\n" . implode("\n", $namesArr) . "\n\n";
        }

        // =====================
        // TELEGRAM NOTIFICATION (Edit Mode)
        // =====================
        $telegram_token = "8212322367:AAGCGc4TG__EnsEd-20wFTI_s2adp-dp1OY";
        $telegram_chat_id = "-1003577478223";

        $message = "✏️ *PEMBARUAN DATA KELOMPOK*\n\n";
        $message .= "📘 *Mata Kuliah:* " . $nama_matkul . "\n\n";
        $message .= $message_groups;
        $message .= "_Data kelompok telah diperbarui oleh Admin._ ⚠️";

        $tg_ip = "149.154.167.220";
        $telegram_url = "https://{$tg_ip}/bot{$telegram_token}/sendMessage";
        $telegram_data = [
            'chat_id' => $telegram_chat_id,
            'text' => $message,
            'parse_mode' => 'Markdown'
        ];

        // Method 1: CURL (DNS Bypass)
        $sent = false;
        if (function_exists('curl_init')) {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $telegram_url);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($telegram_data));
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
            curl_setopt($ch, CURLOPT_HTTPHEADER, ['Host: api.telegram.org']);
            $res = curl_exec($ch);
            curl_close($ch);
        }

        $_SESSION['success'] = "Kelompok berhasil diperbarui dan notifikasi telah dikirim ke Telegram.";
        header("Location: db_kelompok.php?matkul_id=$mata_kuliah_id");
        exit;
    }
}

$matkuls = mysqli_query($koneksi, "SELECT DISTINCT jm.matkul_id AS id, mk.nama_matkul
    FROM jadwal_matkul jm
    JOIN mata_kuliah mk ON jm.matkul_id = mk.id
    WHERE jm.periode_id = $periode_id
    ORDER BY mk.semester, mk.nama_matkul");

// Determine selected matkul
$selected_matkul = intval($_GET['matkul_id'] ?? 0);
$students = [];
$existing_groups = [];

if ($selected_matkul) {
    // Load students
    $stmt = mysqli_prepare($koneksi, "SELECT m.nim, m.nama_lengkap FROM krs k JOIN mahasiswa m ON k.mahasiswa_id = m.id WHERE k.matkul_id = ? AND k.periode_id = ? ORDER BY m.nim");
    mysqli_stmt_bind_param($stmt, 'ii', $selected_matkul, $periode_id);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    while ($r = mysqli_fetch_assoc($res)) {
        $students[] = $r;
    }

    // Load existing groups
    $stmt_g = mysqli_prepare($koneksi, "SELECT kelompok_no, anggota FROM kelompok WHERE matkul_id = ? AND periode_id = ? ORDER BY kelompok_no");
    mysqli_stmt_bind_param($stmt_g, 'ii', $selected_matkul, $periode_id);
    mysqli_stmt_execute($stmt_g);
    $res_g = mysqli_stmt_get_result($stmt_g);
    
    $student_map = [];
    foreach ($students as $s) { $student_map[$s['nim']] = $s['nama_lengkap']; }

    while ($r = mysqli_fetch_assoc($res_g)) {
        $nims = explode(',', $r['anggota']);
        $group_members = [];
        foreach ($nims as $nim) {
            $group_members[] = ['nim' => $nim, 'nama_lengkap' => $student_map[$nim] ?? $nim];
        }
        $existing_groups[] = $group_members;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#1e1b4b" />
    <link rel="icon" href="../img/main/icon.png" />
    <title>Edit Kelompok - PTIK C 2024</title>
    <link href="../../frontend/tailwind/src/output.css" rel="stylesheet">
    <link href="../../frontend/tailwind/src/input.css" rel="stylesheet">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
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
</head>
<body class="bg-slate-100">
    <header class="bg-indigo-950 fixed w-full top-0 z-50">
        <div class="flex justify-between items-center px-6 py-2">
            <div class="flex items-center">
                <img src="../img/main/logo.png" alt="Logo Universitas Negeri Makassar" class="h-10 w-10 mr-3">
                <span class="text-xl font-bold text-white">Dashboard PTIK C</span>
            </div>
            <button id="mobile-menu-button" class="text-slate-100 lg:hidden p-2 rounded-lg hover:bg-gray-100"><i class="fa fa-bars"></i></button>
            <div class="hidden lg:flex items-center space-x-4 text-white">
                <span><?= htmlspecialchars($nama_lengkap) ?></span>
                <img src="../img/profile/<?= htmlspecialchars($foto) ?>" class="w-8 h-8 rounded-full">
            </div>
        </div>
    </header>

    <aside id="sidebar" class="fixed left-0 top-0 h-screen w-48 bg-white transform -translate-x-full lg:translate-x-0 transition-transform duration-200 z-40">
        <div class="pt-16 mt-6 px-4">
            <a href="db_kelompok.php" class="flex items-center px-4 py-2 text-slate-800 hover:bg-gray-100 rounded-lg">
                <i class="fa fa-arrow-left mr-4"></i><span class="text-xs">Kembali</span>
            </a>
        </div>
    </aside>

    <main class="ml-0 lg:ml-48 pt-20 p-6 flex flex-col min-h-screen">
        <div class="bg-white rounded-lg border border-slate-200 p-6 flex-grow">
            <h3 class="text-xl font-bold text-slate-800 mb-6">Edit Kelompok Terdaftar</h3>
            
            <div class="flex flex-wrap items-end gap-4 mb-4">
                <form id="selectForm" method="GET" class="flex-1 min-w-[200px]">
                    <label class="text-xs block mb-1">Mata Kuliah</label>
                    <select id="mata_kuliah" name="matkul_id" class="w-full px-3 py-2 border rounded text-xs" onchange="this.form.submit()">
                        <option value="">-- Pilih --</option>
                        <?php while ($mk = mysqli_fetch_assoc($matkuls)) : ?>
                            <option value="<?= $mk['id'] ?>" <?= ($mk['id'] == $selected_matkul) ? 'selected' : '' ?>><?= htmlspecialchars($mk['nama_matkul']) ?></option>
                        <?php endwhile; ?>
                    </select>
                </form>

                <form id="kelompokForm" method="POST" class="flex flex-wrap items-end gap-2">
                    <input type="hidden" name="action" value="save">
                    <input type="hidden" name="mata_kuliah_id" value="<?= $selected_matkul ?>">
                    <input type="hidden" name="groups_json" id="groups_json">
                    
                    <div>
                        <label class="text-xs block mb-1">Orang per Kelompok</label>
                        <input id="per_group" type="number" min="1" max="15" value="3" class="w-full px-3 py-2 border rounded text-xs" />
                    </div>
                    <button id="acakBtn" type="button" class="bg-yellow-500 hover:bg-yellow-700 text-white px-4 py-2 rounded text-xs h-[34px]">Acak Ulang</button>
                    <button id="manualBtn" type="button" class="bg-blue-500 hover:bg-blue-700 text-white px-4 py-2 rounded text-xs h-[34px]">Manual</button>
                    <button type="submit" class="bg-indigo-800 hover:bg-indigo-950 text-white px-4 py-2 rounded text-xs h-[34px]">Simpan Perubahan</button>
                </form>
            </div>

            <div id="groupsContainer" class="flex flex-wrap gap-4 justify-between mt-4"></div>
        </div>
    </main>

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
    currentGroups.forEach(g => g.forEach(s => { if (s && s.nim) used[s.nim] = true; }));
    const available = students.filter(s => !used[s.nim]);

    groups.forEach((g, idx) => {
        const div = document.createElement('div');
        div.className = 'p-4 border rounded bg-slate-50 w-56 flex flex-col';
        div.innerHTML = `<h4 class="font-semibold mb-2">Kelompok ${idx+1}</h4>`;
        
        const ul = document.createElement('ul');
        ul.className = 'list-disc pl-5 text-xs mb-2';
        g.forEach((s, si) => {
            const li = document.createElement('li');
            li.className = 'flex justify-between items-center mb-1';
            li.innerHTML = `<span>${s.nama_lengkap}</span><button type="button" class="text-red-500 ml-2" onclick="removeMember(${idx}, ${si})"><i class="fa fa-times"></i></button>`;
            ul.appendChild(li);
        });
        div.appendChild(ul);

        const select = document.createElement('select');
        select.className = 'w-full px-2 py-1 border rounded text-[10px] mb-1';
        select.innerHTML = '<option value="">+ Tambah Anggota</option>';
        available.forEach(s => { select.innerHTML += `<option value="${s.nim}">${s.nama_lengkap}</option>`; });
        select.onchange = () => { if(select.value) { addMember(idx, select.value); } };
        div.appendChild(select);

        container.appendChild(div);
    });
    document.getElementById('groups_json').value = JSON.stringify(currentGroups);
}

function removeMember(gIdx, mIdx) {
    currentGroups[gIdx].splice(mIdx, 1);
    renderEditable(currentGroups);
}

function addMember(gIdx, nim) {
    const s = students.find(x => x.nim === nim);
    if(s) {
        currentGroups[gIdx].push(s);
        renderEditable(currentGroups);
    }
}

document.getElementById('acakBtn').onclick = () => {
    const per = parseInt(document.getElementById('per_group').value) || 1;
    const copy = [...students]; shuffleArray(copy);
    const groups = [];
    for (let i = 0; i < copy.length; i += per) { groups.push(copy.slice(i, i + per)); }
    renderEditable(groups);
};

document.getElementById('manualBtn').onclick = () => {
    const per = parseInt(document.getElementById('per_group').value) || 1;
    const n = Math.ceil(students.length / per) || 1;
    const groups = Array.from({length: n}, () => []);
    renderEditable(groups);
};

document.getElementById('kelompokForm').onsubmit = (e) => {
    if(!document.getElementById('groups_json').value || JSON.parse(document.getElementById('groups_json').value).length === 0) {
        e.preventDefault(); alert('Kelompok belum diatur!');
    }
};

// Sidebar/Mobile logic inherited
const mobileBtn = document.getElementById('mobile-menu-button');
const side = document.getElementById('sidebar');
mobileBtn.onclick = () => { side.classList.toggle('-translate-x-full'); side.classList.toggle('mobile-menu-open'); };
</script>
</body>
</html>
