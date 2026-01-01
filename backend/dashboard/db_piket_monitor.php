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

// Ambil Periode Aktif
$q_periode = mysqli_query($koneksi, "SELECT id FROM periode WHERE status = 'aktif' LIMIT 1");
$periode = mysqli_fetch_assoc($q_periode);
$periode_id = $periode['id'] ?? 0;

// Set Minggu (Default 1)
$minggu_ke = isset($_GET['minggu']) ? max(1, intval($_GET['minggu'])) : 1;

// Handle Generate / Acak Jadwal
if (isset($_POST['acak_jadwal'])) {
    // 1. Hapus jadwal lama untuk minggu ini
    mysqli_query($koneksi, "DELETE FROM piket_monitor WHERE minggu_ke = '$minggu_ke' AND periode_id = '$periode_id'");

    // 2. Ambil Data Mahasiswa (Urut Random untuk variasi saat generate)
    $students = [];
    $q_mhs = mysqli_query($koneksi, "SELECT id, nim FROM mahasiswa ORDER BY RAND()"); // Random order base
    while ($row = mysqli_fetch_assoc($q_mhs)) {
        $students[] = $row;
    }
    $total_students = count($students);

    // 3. Ambil Mapping KRS
    $krs_map = [];
    $q_krs = mysqli_query($koneksi, "SELECT mahasiswa_id, matkul_id FROM krs WHERE periode_id = '$periode_id'");
    while ($row = mysqli_fetch_assoc($q_krs)) {
        $krs_map[$row['mahasiswa_id']][] = $row['matkul_id'];
    }

    // 4. Ambil Jadwal Kuliah (Urut Waktu)
    $schedule_data = [];
    $q_jadwal = mysqli_query($koneksi, "
        SELECT jm.id, jm.matkul_id, jm.hari, jm.jam_mulai 
        FROM jadwal_matkul jm 
        WHERE jm.periode_id = '$periode_id' 
        ORDER BY FIELD(jm.hari, 'Senin','Selasa','Rabu','Kamis','Jumat','Sabtu','Minggu'), jm.jam_mulai ASC
    ");
    while ($row = mysqli_fetch_assoc($q_jadwal)) {
        $schedule_data[] = $row;
    }
    $total_classes = count($schedule_data);

    // 5. Algoritma Distribusi (Siklus 2 Minggu)
    if ($total_students > 0 && $total_classes > 0) {
        // Tentukan Tim Aktif (Ganjil/Genap)
        $is_odd_week = ($minggu_ke % 2) != 0;
        $split_point = ceil($total_students / 2);
        
        // Karena base sudah di-RAND(), kita cukup slice saja
        $team_active = $is_odd_week 
            ? array_slice($students, 0, $split_point) 
            : array_slice($students, $split_point);
        
        $total_active = count($team_active);
        $base_alloc = floor($total_active / $total_classes);
        $extra_alloc = $total_active % $total_classes;

        // Tracking penugasan minggu ini
        $assigned_ids = []; 

        foreach ($schedule_data as $idx => $class) {
            $target = $base_alloc + ($idx < $extra_alloc ? 1 : 0);
            if ($target < 1 && $total_active > 0) $target = 1;

            $assigned_count = 0;
            $matkul_id = $class['matkul_id'];
            $jadwal_id = $class['id'];

            foreach ($team_active as $student) {
                if ($assigned_count >= $target) break;

                $m_id = $student['id'];
                if (in_array($m_id, $assigned_ids)) continue;

                // Cek KRS
                $takes_course = isset($krs_map[$m_id]) && in_array($matkul_id, $krs_map[$m_id]);
                
                if ($takes_course) {
                    // Simpan ke DB
                    $insert = "INSERT INTO piket_monitor (mahasiswa_id, jadwal_matkul_id, minggu_ke, periode_id) VALUES ('$m_id', '$jadwal_id', '$minggu_ke', '$periode_id')";
                    if (mysqli_query($koneksi, $insert)) {
                        $assigned_ids[] = $m_id;
                        $assigned_count++;
                    }
                }
            }
        }
        $_SESSION['success'] = "Jadwal piket minggu ke-$minggu_ke berhasil diacak!";
    }
    // Redirect agar tidak resubmit
    echo "<script>window.location.href='db_piket_monitor.php?minggu=$minggu_ke';</script>";
    exit;
}

// ========================
// DISPLAY LOGIC (Fetch from DB)
// ========================

// 1. Ambil Jadwal Kuliah Master
$schedule = [];
$q_jadwal = mysqli_query($koneksi, "
    SELECT jm.id, jm.hari, jk_start.jam_mulai, jk_end.jam_selesai, r.nama_ruangan AS ruangan, mk.nama_matkul 
    FROM jadwal_matkul jm 
    JOIN mata_kuliah mk ON jm.matkul_id = mk.id 
    LEFT JOIN ruangan r ON jm.ruangan_id = r.id
    LEFT JOIN jam_kuliah jk_start ON jm.jam_mulai_ke = jk_start.jam_ke
    LEFT JOIN jam_kuliah jk_end ON jm.jam_selesai_ke = jk_end.jam_ke
    WHERE jm.periode_id = '$periode_id' 
    ORDER BY FIELD(jm.hari, 'Senin','Selasa','Rabu','Kamis','Jumat','Sabtu','Minggu'), jk_start.jam_mulai ASC
");
while ($row = mysqli_fetch_assoc($q_jadwal)) {
    $row['pjs'] = []; // Init empty array for PJs
    $schedule[$row['id']] = $row;
}

// 2. Ambil Data Piket dari DB untuk minggu ini
$q_piket = mysqli_query($koneksi, "
    SELECT pm.jadwal_matkul_id, m.nama_lengkap, m.nim, m.foto 
    FROM piket_monitor pm
    JOIN mahasiswa m ON pm.mahasiswa_id = m.id
    WHERE pm.minggu_ke = '$minggu_ke' AND pm.periode_id = '$periode_id'
");

while ($row = mysqli_fetch_assoc($q_piket)) {
    if (isset($schedule[$row['jadwal_matkul_id']])) {
        $schedule[$row['jadwal_matkul_id']]['pjs'][] = $row;
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
    <title>Jadwal Piket Monitor - PTIK C 2024</title>
    <link href="../../frontend/tailwind/src/output.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Poppins', sans-serif; }
    </style>
</head>
<body class="bg-slate-100">
    <!-- Header -->
    <header class="bg-indigo-950 fixed w-full top-0 z-50">
        <div class="flex justify-between items-center px-6 py-2">
            <div class="flex items-center">
                <img src="../img/main/logo.png" alt="Logo" class="h-10 w-10 mr-3">
                <span class="text-xl font-bold text-white">Dashboard PTIK C</span>
            </div>
            <button id="mobile-menu-button" class="text-slate-100 lg:hidden p-2 rounded-lg hover:bg-gray-100/10">
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

    <!-- Sidebar (Matches dashboard.php structure) -->
    <aside id="sidebar" class="fixed left-0 top-0 h-screen w-48 bg-white transform -translate-x-full lg:translate-x-0 transition-transform duration-200 ease-in-out z-40 border-r border-slate-200">
        <div class="pt-16">
            <nav class="mt-4 h-[calc(100vh-4rem)] overflow-y-auto pb-10">
                <div class="px-4 space-y-2">
                    <a href="dashboard.php" class="flex items-center px-4 py-2 text-slate-700 rounded-lg hover:bg-slate-100">
                        <i class="fa fa-home w-4 h-4 mr-4"></i>
                        <span class="text-xs font-medium">Beranda</span>
                    </a>
                    
                    <!-- Components Menu -->
                    <div class="space-y-1">
                        <div>
                            <p class="px-4 text-[10px] font-semibold text-slate-400 uppercase tracking-wider mb-2">Akademik</p>
                            <div class="space-y-1">
                                <!-- Copied standard links for consistency -->
                                <a href="db_mahasiswa.php" class="block px-4 py-1 text-xs text-slate-600 hover:text-indigo-600 hover:bg-slate-50 rounded-lg">Mahasiswa</a>
                                <a href="db_dosen.php" class="block px-4 py-1 text-xs text-slate-600 hover:text-indigo-600 hover:bg-slate-50 rounded-lg">Dosen</a>
                                <a href="db_matkul.php" class="block px-4 py-1 text-xs text-slate-600 hover:text-indigo-600 hover:bg-slate-50 rounded-lg">Mata Kuliah</a>
                                <a href="db_berita.php" class="block px-4 py-1 text-xs text-slate-600 hover:text-indigo-600 hover:bg-slate-50 rounded-lg">Berita & Info</a>
                                <a href="db_kalender.php" class="block px-4 py-1 text-xs text-slate-600 hover:text-indigo-600 hover:bg-slate-50 rounded-lg">Kalender</a>
                                <a href="db_piket_monitor.php" class="block px-4 py-1 text-xs text-slate-600 hover:bg-indigo-50 text-indigo-700 font-bold rounded-lg transition-colors">Piket Monitor</a>
                                
                                <a href="db_jadwal.php" class="flex items-center px-4 py-1 text-slate-800 hover:bg-slate-100 rounded-lg transition-colors">
                                    <i class="fa fa-list-alt w-5 h-5 mr-3 text-indigo-600"></i>
                                    <span class="text-xs font-medium">Jadwal Kuliah</span>
                                </a>
                                 <a href="db_tugas.php" class="flex items-center px-4 py-1 text-slate-800 hover:bg-slate-100 rounded-lg transition-colors">
                                    <i class="fa fa-tasks w-5 h-5 mr-3 text-indigo-600"></i>
                                    <span class="text-xs font-medium">Tugas Kuliah</span>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </nav>
        </div>
    </aside>

    <main class="ml-0 lg:ml-48 pt-20 p-6 flex flex-col min-h-screen">
        <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-6">
            <div class="flex flex-col md:flex-row justify-between items-center mb-6 gap-4">
                <div>
                    <h1 class="text-2xl font-bold text-slate-800">Jadwal Piket Monitor</h1>
                    <p class="text-slate-500 text-sm mt-1">Penanggung jawab pengambilan monitor Admin Kampus</p>
                </div>
                
                <!-- Week Selector -->
                <div class="flex items-center gap-2">
                    <form method="POST" onsubmit="return confirm('Acak ulang jadwal untuk Minggu ke-<?= $minggu_ke ?>? Data lama akan dihapus.');">
                        <button type="submit" name="acak_jadwal" class="px-3 py-2 bg-indigo-600 hover:bg-indigo-800 text-white rounded-lg text-xs font-bold transition-colors shadow-sm">
                            <i class="fa fa-shuffle mr-2"></i>Acak Jadwal
                        </button>
                    </form>

                    <div class="flex items-center gap-2 bg-slate-100 p-1 rounded-lg">
                        <a href="?minggu=<?= max(1, $minggu_ke - 1) ?>" class="p-2 hover:bg-white rounded-md transition-colors <?= $minggu_ke <= 1 ? 'opacity-50 pointer-events-none' : '' ?>">
                            <i class="fa fa-chevron-left text-slate-600"></i>
                        </a>
                        <span class="px-4 font-bold text-indigo-900 text-sm">Minggu Ke-<?= $minggu_ke ?></span>
                        <a href="?minggu=<?= $minggu_ke + 1 ?>" class="p-2 hover:bg-white rounded-md transition-colors">
                            <i class="fa fa-chevron-right text-slate-600"></i>
                        </a>
                    </div>
                </div>
            </div>

            <div class="overflow-x-auto rounded-lg border border-slate-200">
                <table class="min-w-full divide-y divide-slate-200">
                    <thead class="bg-indigo-900 text-white">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-bold uppercase tracking-wider">No</th>
                            <th class="px-4 py-3 text-left text-xs font-bold uppercase tracking-wider">Hari & Jam</th>
                            <th class="px-4 py-3 text-left text-xs font-bold uppercase tracking-wider">Mata Kuliah</th>
                            <th class="px-4 py-3 text-left text-xs font-bold uppercase tracking-wider">Ruangan</th>
                            <th class="px-4 py-3 text-left text-xs font-bold uppercase tracking-wider bg-orange-600/90 text-white">
                                <i class="fa fa-user-shield mr-2"></i>PJ Monitor
                            </th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-slate-200">
                        <?php if (empty($schedule)): ?>
                            <tr><td colspan="5" class="px-4 py-8 text-center text-slate-500">Belum ada jadwal kuliah.</td></tr>
                        <?php else: ?>
                            <?php foreach ($schedule as $idx => $row): ?>
                                <tr class="hover:bg-indigo-50/50 transition-colors">
                                    <td class="px-4 py-3 text-xs text-slate-600 font-medium"><?= $idx + 1 ?></td>
                                    <td class="px-4 py-3">
                                        <div class="text-xs font-bold text-slate-800"><?= $row['hari'] ?></div>
                                        <div class="text-[10px] text-slate-500"><?= date('H.i', strtotime($row['jam_mulai'])) ?> - <?= date('H.i', strtotime($row['jam_selesai'])) ?></div>
                                    </td>
                                    <td class="px-4 py-3 text-xs text-slate-700 font-semibold"><?= $row['nama_matkul'] ?></td>
                                    <td class="px-4 py-3 text-xs text-slate-600"><?= $row['ruangan'] ?></td>
                                    <td class="px-4 py-3 bg-orange-50/30">
                                        <div class="flex flex-col gap-3">
                                            <?php if (!empty($row['pjs'])): ?>
                                                <?php foreach ($row['pjs'] as $pj): ?>
                                                    <div class="flex items-center gap-3">
                                                        <img src="../img/profile/<?= $pj['foto'] ?: 'default.png' ?>" class="w-8 h-8 rounded-full border border-slate-200 object-cover">
                                                        <div>
                                                            <div class="text-xs font-bold text-indigo-900"><?= $pj['nama_lengkap'] ?></div>
                                                            <div class="text-[10px] text-slate-500"><?= $pj['nim'] ?></div>
                                                        </div>
                                                    </div>
                                                <?php endforeach; ?>
                                            <?php else: ?>
                                                <span class="text-slate-400 text-xs italic">- Belum ditentukan -</span>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <div class="mt-6 p-4 bg-blue-50 border border-blue-200 rounded-lg flex items-start gap-3">
                <i class="fa fa-info-circle text-blue-600 mt-0.5"></i>
                <div class="text-xs text-blue-800">
                    <p class="font-bold mb-1">Informasi Rotasi Piket</p>
                    <p>Jadwal piket monitor ini digilir secara otomatis berdasarkan urutan NIM mahasiswa. Jika Anda berhalangan hadir, harap menukar jadwal dengan teman sekelas secara mandiri.</p>
                </div>
            </div>
        </div>

        <footer class="text-[10px] text-indigo-950/50 text-center py-6 mt-auto">
            © 2025 Kelas PTIK C - FT UNM.
        </footer>
    </main>
    
    <script>
        const mobileMenuButton = document.getElementById('mobile-menu-button');
        const sidebar = document.getElementById('sidebar');
        mobileMenuButton.addEventListener('click', (e) => {
            e.stopPropagation();
            sidebar.classList.toggle('-translate-x-full');
        });
        document.addEventListener('click', (e) => {
            if (window.innerWidth < 1024 && !sidebar.contains(e.target) && !mobileMenuButton.contains(e.target)) {
                sidebar.classList.add('-translate-x-full');
            }
        });
        const profileButton = document.getElementById('profile-button');
        const profileDropdown = document.getElementById('profile-dropdown');
        if(profileButton && profileDropdown){
            profileButton.addEventListener('click', (e)=>{ e.stopPropagation(); profileDropdown.classList.toggle('hidden'); });
            document.addEventListener('click', ()=> profileDropdown.classList.add('hidden'));
        }
    </script>
</body>
</html>
