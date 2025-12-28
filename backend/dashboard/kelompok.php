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

if ($_SESSION['role'] !== 'Administrator Utama' || $_SESSION['role'] !== 'Ketua Tingkat') {
    // Kita tetap butuh HTML dikit untuk menjalankan JS
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
                if (result.isConfirmed) {
                    window.location.href = '../../index.php';
                } else {
                    window.location.href = '../../index.php';
                }
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

// Handle save
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'save') {
    $mata_kuliah_id = intval($_POST['mata_kuliah_id'] ?? 0);
    $groups_json = $_POST['groups_json'] ?? '[]';
    $groups = json_decode($groups_json, true);

    if ($mata_kuliah_id && is_array($groups)) {
        // Remove existing groups for this mata kuliah and period
        $stmt = mysqli_prepare($koneksi, "DELETE FROM kelompok WHERE matkul_id = ? AND periode_id = ?");
        mysqli_stmt_bind_param($stmt, 'ii', $mata_kuliah_id, $periode_id);
        mysqli_stmt_execute($stmt);

        // Insert new groups
        $insert = mysqli_prepare($koneksi, "INSERT INTO kelompok (matkul_id, kelompok_no, anggota, periode_id) VALUES (?, ?, ?, ?)");
        foreach ($groups as $no => $anggotaArr) {
            $anggotaStr = implode(',', array_map(function($s){ return $s['nim']; }, $anggotaArr));
            $kel_no = $no + 1;
            mysqli_stmt_bind_param($insert, 'iisi', $mata_kuliah_id, $kel_no, $anggotaStr, $periode_id);
            mysqli_stmt_execute($insert);
        }

        $_SESSION['flash'] = "Kelompok berhasil disimpan.";
        header("Location: db_kelompok.php");
        exit;
    } else {
        $_SESSION['flash'] = "Data tidak valid.";
        header("Location: db_kelompok.php");
        exit;
    }
}

$matkuls = mysqli_query($koneksi, "SELECT DISTINCT jm.matkul_id AS id, mk.nama_matkul
    FROM jadwal_matkul jm
    JOIN mata_kuliah mk ON jm.matkul_id = mk.id
    WHERE jm.periode_id = $periode_id
    ORDER BY mk.semester, mk.nama_matkul");

// Determine selected matkul from GET so we can load only mahasiswa yang mengambil matkul ini (dari krs)
$selected_matkul = intval($_GET['matkul_id'] ?? 0);
$students = [];
if ($selected_matkul) {
    $stmt = mysqli_prepare($koneksi, "SELECT m.nim, m.nama_lengkap FROM krs k JOIN mahasiswa m ON k.mahasiswa_id = m.id WHERE k.matkul_id = ? AND k.periode_id = ? ORDER BY m.nim");
    mysqli_stmt_bind_param($stmt, 'ii', $selected_matkul, $periode_id);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    while ($r = mysqli_fetch_assoc($res)) {
        $students[] = $r;
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#6c27dc" />
    <link rel="icon" href="img/icon.png" />
    <title>Kelas PTIK C 2024 - Teknik Informatika dan Komputer</title>
    <link href="../../frontend/tailwind/src/output.css" rel="stylesheet">
    <link href="../../frontend/tailwind/src/input.css" rel="stylesheet">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&display=swap" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
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
</head>
<body class="bg-slate-100">
    <header class="bg-indigo-950  fixed w-full top-0 z-50">
        <div class="flex justify-between items-center px-6 py-2">
            <div class="flex items-center">
                <img src="../../img/logo.png" alt="Logo Universitas Negeri Makassar" class="h-10 w-10 mr-3">
                <span class="text-xl font-bold text-white">Dashboard PTIK C</span>
            </div>
            <!-- Mobile Menu Button dengan z-index yang sesuai -->
            <button id="mobile-menu-button" class="text-slate-100 hover:text-indigo-950 lg:hidden p-2 rounded-lg hover:bg-gray-100">
                <i class="fa fa-bars w-6 h-5"></i>
            </button>
            <div class="hidden lg:flex items-center space-x-4">
                <div class="relative">
                    <button id="profile-button" class="flex items-center space-x-2">
                        <span class="text-white"><?= htmlspecialchars($nama_lengkap) ?></span>
                        <img src="../img/profile/<?= htmlspecialchars($foto) ?>" alt="Profile" class="w-8 h-8 rounded-full">
                    </button>
                    <div id="profile-dropdown" class="absolute right-0 mt-2 w-48 bg-white rounded-md shadow-lg hidden">
                        <a href="profile.php" class="block px-4 py-2 text-sm hover:bg-gray-100">Profile</a>
                        <a href="../../index.php" class="block px-4 py-2 text-sm hover:bg-gray-100">Logout</a>
                    </div>
                </div>
            </div>
        </div>
    </header>

    <aside id="sidebar"
        class="fixed left-0 top-0 h-screen w-48 bg-white transform -translate-x-full lg:translate-x-0 transition-transform duration-200 ease-in-out z-40">
        <!-- Tambahan padding top agar tidak tertutup header -->
        <div class="pt-16">
            <nav class="mt-6">
                <div class="px-4 space-y-2">
                    <!-- Dashboard Menu -->
                    <a href="dashboard.php" class="flex items-center px-4 py-2 text-slate-700  rounded-lg hover:bg-slate-100">
                                <i class="fa fa-home w-4 h-4 mr-4"></i>
                        <span class="text-xs">Beranda</span>
                    </a>

                    <!-- Components Menu -->
                    <div class="space-y-2">

                        <button
                            class="submenu-button flex items-center justify-between w-full px-4 py-2 text-slate-800 hover:bg-slate-100 rounded-lg">
                            <div class="flex items-center">
                                <i class="fa fa-list w-5 h-5 mr-4"></i>
                                <span class="text-xs">Akademik</span>
                            </div>
                            <i class="fa fa-chevron-down submenu-arrow w-4 h-4 transition-transform duration-200"></i>
                        </button>

                        <div class="submenu pl-8 space-y-1 hidden overflow-y-auto max-h-52">
                            <a href="db_mahasiswa.php"
                                class="block px-4 py-2 text-xs text-slate-800 hover:bg-slate-100 rounded-lg">Mahasiswa</a>

                                <a href="db_dosen.php"
                                class="block px-4 py-2 text-xs text-slate-800 hover:bg-slate-100 rounded-lg">Dosen</a>

                            <a href="db_matkul.php"
                                class="block px-4 py-2 text-xs text-slate-800 hover:bg-slate-100 rounded-lg">Mata Kuliah</a>

                            <a href="db_jadwal.php"
                                class="block px-4 py-2 text-xs text-slate-800 hover:bg-slate-100 rounded-lg">Jadwal</a>

                                <a href="db_tugas.php"
                                class="block px-4 py-2 text-xs text-slate-800 hover:bg-slate-100 rounded-lg">Tugas</a>

                            
                        </div>

                        <button
                            class="flex items-center justify-between w-full px-4 py-2 text-slate-800 hover:bg-gray-100 rounded-lg">
                            <div class="flex items-center">
                                <i class="fa fa-calendar w-5 h-5 mr-4"></i>
                                <a href="db_absensi.php?matkul_id=33&pertemuan=1">
                                    <span class="text-xs">Absensi</span>
                                </a>
                            </div>
                        </button>

                        <button
                            class="flex items-center justify-between w-full px-4 py-2 text-slate-800 hover:bg-gray-100 rounded-lg">
                            <div class="flex items-center">
                                <i class="fa fa-wallet w-5 h-5 mr-4"></i>
                                <a href="db_kas.php?minggu_ke=1">
                                    <span class="text-xs">Kas Mingguan</span>
                                </a>
                            </div>
                        </button>

                        

                        <button
                            class="flex items-center justify-between w-full px-4 py-2 text-slate-800 hover:bg-gray-100 rounded-lg">
                            <div class="flex items-center">
                                <i class="fa fa-users w-5 h-5 mr-4"></i>
                                <a href="db_kelompok.php">
                                    <span class="text-xs">Kelompok</span>
                                </a>
                            </div>
                        </button>

                        <button
                            class="flex items-center justify-between w-full px-4 py-2 text-slate-800 hover:bg-gray-100 rounded-lg">
                            <div class="flex items-center">
                                <i class="fa fa-user w-5 h-5 mr-4"></i>
                                <a href="db_users.php">
                                    <span class="text-xs">Users</span>
                                </a>
                            </div>
                        </button>

                    </div>
                </div>
            </nav>
        </div>
    </aside>

<main class="ml-0 lg:ml-48 pt-20 p-6 flex flex-col min-h-screen">
    <div class="bg-white rounded-lg border border-slate-200 p-6 flex-grow w-full">
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-2">
                <h3 class="text-xl font-bold text-slate-800 mb-4">Pengaturan Kelompok</h3>
                <div class="flex flex-col sm:flex-row gap-2 w-full sm:w-auto">
                    <form method="GET" class="flex flex-col sm:flex-row gap-4 w-full sm:w-auto">
                        

                        
                        
                        <a href="db_kelompok.php" class="bg-indigo-600 text-white px-4 py-2 rounded-lg hover:bg-indigo-800 text-center text-xs">
                           
                                Kembali
                          
                        </a>
                    </form>
                </div>
            </div>
<div class="flex flex-wrap items-end gap-4 mb-4">
    
    <form id="selectForm" method="GET" class="flex-1 min-w-[200px]">
        <label class="text-xs block mb-1">Pilih Mata Kuliah</label>
        <select id="mata_kuliah" name="matkul_id" class="w-full px-3 py-2 border rounded text-xs">
            <option value="">-- Pilih --</option>
            <?php while ($mk = mysqli_fetch_assoc($matkuls)) : ?>
                <option value="<?= $mk['id'] ?>" <?= ($mk['id'] == $selected_matkul) ? 'selected' : '' ?>>
                    <?= htmlspecialchars($mk['nama_matkul']) ?>
                </option>
            <?php endwhile; ?>
        </select>
    </form>

    <form id="kelompokForm" method="POST" class="flex flex-wrap items-end gap-2">
        <input type="hidden" name="action" value="save">
        <input type="hidden" name="mata_kuliah_id" id="mata_kuliah_hidden" value="<?= $selected_matkul ?>">
        <input type="hidden" name="groups_json" id="groups_json" value="[]">

        <div>
            <label class="text-xs block mb-1">Jumlah Orang per Kelompok</label>
            <input id="per_group" type="number" min="1" max="17" value="3" class="w-full px-3 py-2 border rounded text-xs" />
        </div>

        <button id="acakBtn" type="button" class="bg-yellow-500 hover:bg-yellow-700 text-white px-4 py-2 rounded text-xs h-[34px]">Acak</button>
        
        <button id="manualBtn" type="button" class="bg-blue-500 hover:bg-blue-700 text-white px-4 py-2 rounded text-xs h-[34px]">Manual</button>
        
        <button id="simpanBtn" type="submit" class="bg-indigo-800 hover:bg-indigo-950 text-white px-4 py-2 rounded text-xs h-[34px]">Simpan</button>
    </form>
</div>

<div id="groupsContainer" class="flex flex-wrap gap-4 justify-between mt-4"></div>
    </div>
    <footer class="text-xs text-indigo-900 text-center mb-0 pb-0 mt-6">
      © 2025 Kelas PTIK C - Teknik Informatika dan Komputer FT UNM. All rights reserved.
    </footer>
</main>

<script>

    const mobileMenuButton = document.getElementById('mobile-menu-button');
        const sidebar = document.getElementById('sidebar');

        mobileMenuButton.addEventListener('click', (e) => {
            e.stopPropagation(); // Prevent event bubbling
            sidebar.classList.toggle('-translate-x-full');
            sidebar.classList.toggle('mobile-menu-open');
        });

        // Close sidebar when clicking outside
        document.addEventListener('click', (e) => {
            if (window.innerWidth < 768) { // Only on mobile
                if (!sidebar.contains(e.target) && !mobileMenuButton.contains(e.target)) {
                    sidebar.classList.add('-translate-x-full');
                    sidebar.classList.remove('mobile-menu-open');
                }
            }
        });

        // Submenu toggles
        const submenuButtons = document.querySelectorAll('.submenu-button');

        submenuButtons.forEach(button => {
            button.addEventListener('click', () => {
                const submenu = button.nextElementSibling;
                const arrow = button.querySelector('.submenu-arrow');

                submenu.classList.toggle('hidden');
                if (arrow) arrow.classList.toggle('rotate-180');
            });
        });

        // Profile dropdown
        const profileButton = document.getElementById('profile-button');
        const profileDropdown = document.getElementById('profile-dropdown');

        if (profileButton && profileDropdown) {
            profileButton.addEventListener('click', (e) => {
                e.stopPropagation();
                profileDropdown.classList.toggle('hidden');
            });

            document.addEventListener('click', (e) => {
                if (!profileButton.contains(e.target)) {
                    profileDropdown.classList.add('hidden');
                }
            });
        }
// Students data from PHP (only those who took selected matkul via KRS)
const students = <?= json_encode($students, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) ?>;

function shuffleArray(array) {
    for (let i = array.length - 1; i > 0; i--) {
        const j = Math.floor(Math.random() * (i + 1));
        [array[i], array[j]] = [array[j], array[i]];
    }
}

function makeGroups(perGroup) {
    const copy = students.slice();
    shuffleArray(copy);
    const groups = [];
    for (let i = 0; i < copy.length; i += perGroup) {
        groups.push(copy.slice(i, i + perGroup));
    }
    return groups;
}

function renderGroups(groups) {
    currentGroups = groups;
    const container = document.getElementById('groupsContainer');
    container.innerHTML = '';
    groups.forEach((g, idx) => {
        const div = document.createElement('div');
        div.className = 'p-4 border rounded bg-slate-50 w-56';
        const title = document.createElement('h4');
        title.className = 'font-semibold mb-2';
        title.textContent = 'Kelompok ' + (idx + 1);
        div.appendChild(title);
        const ul = document.createElement('ul');
        ul.className = 'list-disc pl-5 text-sm';
        g.forEach(s => {
            const li = document.createElement('li');
            li.textContent = s.nama_lengkap;
            ul.appendChild(li);
        });
        div.appendChild(ul);
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
        currentGroups.forEach(g => g.forEach(m => { if (m && m.nim) used[m.nim] = true; }));
        return students.filter(s => !used[s.nim]);
    }

    groups.forEach((g, idx) => {
        const div = document.createElement('div');
        div.className = 'p-4 border rounded bg-slate-50 w-56 flex flex-col';
        const title = document.createElement('h4');
        title.className = 'font-semibold mb-2';
        title.textContent = 'Kelompok ' + (idx + 1);
        div.appendChild(title);

        const ul = document.createElement('ul');
        ul.className = 'list-disc pl-5 text-sm mb-2';
        (g || []).forEach((s, si) => {
            const li = document.createElement('li');
            li.className = 'flex justify-between items-center';
            const span = document.createElement('span');
            span.textContent = s.nama_lengkap || s;
            const btn = document.createElement('button');
            btn.type = 'button';
            btn.className = 'text-red-600 ml-2 text-xs';
            btn.textContent = 'Hapus';
            btn.addEventListener('click', () => {
                currentGroups[idx].splice(si, 1);
                renderEditable(currentGroups);
            });
            li.appendChild(span);
            li.appendChild(btn);
            ul.appendChild(li);
        });
        div.appendChild(ul);

        // Add from available students
        const avail = getAvailableStudents();
        const select = document.createElement('select');
        select.className = 'w-full px-2 py-1 border rounded text-sm mb-1';
        const opt0 = document.createElement('option'); opt0.value=''; opt0.textContent='Tambah dari daftar...'; select.appendChild(opt0);
        avail.forEach(s => { const o = document.createElement('option'); o.value = s.nim; o.textContent = s.nama_lengkap; select.appendChild(o); });
        const addBtn = document.createElement('button');
        addBtn.type='button';
        addBtn.className='bg-green-600 text-white px-2 py-1 rounded text-sm';
        addBtn.textContent='Tambah';
        addBtn.addEventListener('click', () => {
            const val = select.value;
            if (!val) return;
            const stu = students.find(x=>x.nim===val);
            if (stu) {
                currentGroups[idx].push(stu);
                // reset select so user sees it's added
                select.value = '';
                renderEditable(currentGroups);
            }
        });
        div.appendChild(select);
        div.appendChild(addBtn);

        container.appendChild(div);
    });
    document.getElementById('groups_json').value = JSON.stringify(currentGroups);
}

document.getElementById('acakBtn').addEventListener('click', () => {
    const per = parseInt(document.getElementById('per_group').value) || 1;
    const groups = makeGroups(per);
    currentGroups = groups;
    renderGroups(groups);
});

document.getElementById('manualBtn').addEventListener('click', () => {
    const per = parseInt(document.getElementById('per_group').value) || 1;
    const n = Math.max(1, Math.ceil((students.length || 0) / per));
    const groups = [];
    for (let i = 0; i < n; i++) groups.push([]);
    renderEditable(groups);
});

// Auto-submit select to reload students list
document.getElementById('mata_kuliah').addEventListener('change', function(){
    document.getElementById('selectForm').submit();
});

// Ensure hidden matkul id updates before save
document.getElementById('kelompokForm').addEventListener('submit', function(){
    const selectVal = document.getElementById('mata_kuliah').value || document.getElementById('mata_kuliah_hidden').value;
    document.getElementById('mata_kuliah_hidden').value = selectVal;
});

// When form submits, ensure a matkul is selected and groups_json present
document.getElementById('kelompokForm').addEventListener('submit', function(e){
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
    // Append selected mata kuliah id into form as name expected by server
    // The select already has name="mata_kuliah_id"
    return true;
});
</script>

</body>
</html>
