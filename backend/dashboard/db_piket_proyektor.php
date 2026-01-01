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
    FROM piket_proyektor pm
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
    <title>Jadwal Piket Proyektor - PTIK C 2024</title>
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

    <!-- Sidebar dengan z-index di bawah header -->
    <aside id="sidebar"
        class="fixed left-0 top-0 h-screen w-48 bg-white transform -translate-x-full lg:translate-x-0 transition-transform duration-200 ease-in-out z-40">
        <!-- Tambahan padding top agar tidak tertutup header -->
        <div class="pt-16">
            <nav class="mt-4 h-[calc(100vh-4rem)] overflow-y-auto pb-10">
                <div class="px-4 space-y-2">
                    <!-- Dashboard Menu -->
                    <a href="dashboard.php" class="flex items-center px-4 py-2 text-slate-700  rounded-lg hover:bg-slate-100">
                        <i class="fa fa-home w-4 h-4 mr-4"></i>
                        <span class="text-xs font-medium">Beranda</span>
                    </a>

                    

                    <!-- Components Menu -->
                    <div class="space-y-1">
                        <div>
                            <p class="px-4 text-[10px] font-semibold text-slate-400 uppercase tracking-wider mb-2">Akademik</p>
                            <div class="space-y-1">
                                <button class="submenu-button flex items-center justify-between w-full px-4 py-1 text-slate-800 hover:bg-slate-100 rounded-lg transition-colors">
                                    <div class="flex items-center">
                                        <i class="fa fa-university w-5 h-5 mr-3 text-indigo-600"></i>
                                        <span class="text-xs font-medium">Master</span>
                                    </div>
                                    <i class="fa fa-chevron-down submenu-arrow w-3 h-3 transition-transform duration-200"></i>
                                </button>
                                <div class="submenu pl-1 space-y-1 hidden overflow-y-auto max-h-52 border-l-2 border-slate-100 ml-6">
                                    <a href="db_mahasiswa.php" class="block px-4 py-1 text-xs text-slate-600 hover:text-indigo-600 hover:bg-slate-50 rounded-lg">Mahasiswa</a>
                                    <a href="db_dosen.php" class="block px-4 py-1 text-xs text-slate-600 hover:text-indigo-600 hover:bg-slate-50 rounded-lg">Dosen</a>
                                    <a href="db_matkul.php" class="block px-4 py-1 text-xs text-slate-600 hover:text-indigo-600 hover:bg-slate-50 rounded-lg">Mata Kuliah</a>
                                    <a href="db_berita.php" class="block px-4 py-1 text-xs text-slate-600 hover:text-indigo-600 hover:bg-slate-50 rounded-lg">Berita & Info</a>
                                    <a href="db_kalender.php" class="block px-4 py-1 text-xs text-slate-600 hover:text-indigo-600 hover:bg-slate-50 rounded-lg">Kalender Akademik</a>
                                </div>

                                <a href="db_jadwal.php" class="flex items-center px-4 py-1 text-slate-800 hover:bg-slate-100 rounded-lg transition-colors">
                                    <i class="fa fa-list-alt w-5 h-5 mr-3 text-indigo-600"></i>
                                    <span class="text-xs font-medium">Jadwal Kuliah</span>
                                </a>
                                <a href="db_absensi.php?matkul_id=33&pertemuan=1" class="flex items-center px-4 py-1 text-slate-800 hover:bg-slate-100 rounded-lg transition-colors">
                                    <i class="fa fa-calendar-check w-5 h-5 mr-3 text-indigo-600"></i>
                                    <span class="text-xs font-medium">Absensi</span>
                                </a>
                                <a href="db_tugas.php" class="flex items-center px-4 py-1 text-slate-800 hover:bg-slate-100 rounded-lg transition-colors">
                                    <i class="fa fa-tasks w-5 h-5 mr-3 text-indigo-600"></i>
                                    <span class="text-xs font-medium">Tugas Kuliah</span>
                                </a>
                                <a href="db_kelompok.php" class="flex items-center px-4 py-1 text-slate-800 hover:bg-slate-100 rounded-lg transition-colors">
                                    <i class="fa fa-users w-5 h-5 mr-3 text-indigo-600"></i>
                                    <span class="text-xs font-medium">Kelompok</span>
                                </a>
                                <a href="db_kas.php?minggu_ke=1" class="flex items-center px-4 py-1 text-slate-800 hover:bg-slate-100 rounded-lg transition-colors">
                                    <i class="fa fa-wallet w-5 h-5 mr-3 text-indigo-600"></i>
                                    <span class="text-xs font-medium">Keuangan</span>
                                </a>
                            </div>
                        </div>

                        <div>
                            <p class="px-4 text-[10px] font-semibold text-slate-400 uppercase tracking-wider mb-2">Aktivitas</p>
                            <div class="space-y-1">
                                <button class="submenu-button flex items-center justify-between w-full px-4 py-1 text-slate-800 hover:bg-slate-100 rounded-lg transition-colors">
                                    <div class="flex items-center">
                                        <i class="fa fa-wand-magic-sparkles w-5 h-5 mr-3 text-emerald-600"></i>
                                        <span class="text-xs font-medium">Hiburan</span>
                                    </div>
                                    <i class="fa fa-chevron-down submenu-arrow w-3 h-3 transition-transform duration-200"></i>
                                </button>
                                <div class="submenu pl-1 space-y-1 hidden overflow-y-auto max-h-52 border-l-2 border-slate-100 ml-6">
                                    <a href="misi.php" class="block px-4 py-1 text-xs text-slate-600 hover:text-indigo-600 hover:bg-slate-50 rounded-lg">Pusat Misi</a>
                                    <a href="kuis_harian.php" class="block px-4 py-1 text-xs text-slate-600 hover:text-indigo-600 hover:bg-slate-50 rounded-lg">Kuis Harian</a>
                                    <a href="db_pesan.php" class="block px-4 py-1 text-xs text-slate-600 hover:text-indigo-600 hover:bg-slate-50 rounded-lg">Pesan Anonim</a>
                                    
                                </div>

                            <a href="kalender_view.php" class="flex items-center px-4 py-1 text-slate-800 hover:bg-slate-100 rounded-lg transition-colors">
                                    <i class="fa fa-calendar w-5 h-5 mr-3 text-emerald-600"></i>
                                    <span class="text-xs font-medium">Kalender</span>
                                </a>
                                
                                <a href="db_galeri.php" class="flex items-center px-4 py-1 text-slate-800 hover:bg-slate-100 rounded-lg transition-colors">
                                    <i class="fa fa-images w-5 h-5 mr-3 text-emerald-600"></i>
                                    <span class="text-xs font-medium">Galeri Kelas</span>
                                </a>
                            
                            </div>
                        </div>


                        <div>
                            <p class="px-4 text-[10px] font-semibold text-slate-400 uppercase tracking-wider mb-2">Pengaturan</p>
                            <div class="space-y-1">
                                <button class="submenu-button flex items-center justify-between w-full px-4 py-1 text-slate-800 hover:bg-slate-100 rounded-lg transition-colors">
                                    <div class="flex items-center">
                                        <i class="fa fa-user w-5 h-5 mr-3 text-slate-800"></i>
                                        <span class="text-xs font-medium">Akun</span>
                                    </div>
                                    <i class="fa fa-chevron-down submenu-arrow w-3 h-3 transition-transform duration-200"></i>
                                </button>
                                <div class="submenu pl-1 space-y-1 hidden overflow-y-auto max-h-52 border-l-2 border-slate-100 ml-6">
                                    <a href="profile.php" class="block px-4 py-1 text-xs text-slate-600 hover:text-indigo-600 hover:bg-slate-50 rounded-lg">Profile Saya</a>
                                    <a href="krs.php" class="block px-4 py-1 text-xs text-slate-600 hover:text-indigo-600 hover:bg-slate-50 rounded-lg">KRS</a>
                                    
                                </div>
<?php if ($role === 'Administrator Utama'): ?>
                                <button class="submenu-button flex items-center justify-between w-full px-4 py-1 text-slate-800 hover:bg-slate-100 rounded-lg transition-colors">
                                    <div class="flex items-center">
                                        <i class="fa-solid fa-earth-americas w-5 h-5 mr-3 text-slate-800"></i>
                                        <span class="text-xs font-medium">Website</span>
                                    </div>
                                    <i class="fa fa-chevron-down submenu-arrow w-3 h-3 transition-transform duration-200"></i>
                                </button>
                                <div class="submenu pl-1 space-y-1 hidden overflow-y-auto max-h-52 border-l-2 border-slate-100 ml-6">
                                    <a href="db_users.php" class="block px-4 py-1 text-xs text-slate-600 hover:text-indigo-600 hover:bg-slate-50 rounded-lg">Users</a>
                                    <a href="db_periode.php" class="block px-4 py-1 text-xs text-slate-600 hover:text-indigo-600 hover:bg-slate-50 rounded-lg">Periode</a>
                                    <a href="reminder.php" class="block px-4 py-1 text-xs text-slate-600 hover:text-indigo-600 hover:bg-slate-50 rounded-lg">Reminder</a>
                                </div>
                              <?php endif; ?>
                 
                                <a href="../../index.php" class="flex items-center px-4 py-1 text-slate-800 hover:bg-red-50 hover:text-red-700 rounded-lg transition-colors">
                                    <i class="fa fa-sign-out-alt w-5 h-5 mr-3 text-red-600"></i>
                                    <span class="text-xs font-medium">Logout</span>
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
                    <h1 class="text-2xl font-bold text-slate-800">Jadwal Piket Proyektor</h1>
                    <p class="text-slate-500 text-sm mt-1">Penanggung jawab pengambilan proyektor Admin Kampus</p>
                </div>
                
                <!-- Week Selector -->
                <div class="flex items-center gap-2">
                    <a href="acak_piket_proyektor.php?minggu=<?= $minggu_ke ?>" class="px-3 py-2 bg-indigo-600 hover:bg-indigo-800 text-white rounded-lg text-xs font-bold transition-colors shadow-sm">
                        <i class="fa fa-shuffle mr-2"></i>Acak Jadwal
                    </a>

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
                            <th class="hidden md:table-cell px-4 py-3 text-left text-xs font-bold uppercase tracking-wider">Ruangan</th>
                            <th class="px-4 py-3 text-left text-xs font-bold uppercase tracking-wider bg-orange-600/90 text-white">
                                <i class="fa fa-user-shield mr-2"></i>PJ Proyektor
                            </th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-slate-200">
                        <?php if (empty($schedule)): ?>
                            <tr><td colspan="5" class="px-4 py-8 text-center text-slate-500">Belum ada jadwal kuliah.</td></tr>
                        <?php else: ?>
                            <?php $no = 1; foreach ($schedule as $idx => $row): ?>
                                <tr class="hover:bg-indigo-50/50 transition-colors">
                                    <td class="px-4 py-3 text-xs text-slate-600 font-medium"><?= $no++ ?></td>
                                    <td class="px-4 py-3">
                                        <div class="text-xs font-bold text-slate-800"><?= $row['hari'] ?></div>
                                        <div class="text-[10px] text-slate-500"><?= date('H.i', strtotime($row['jam_mulai'])) ?> - <?= date('H.i', strtotime($row['jam_selesai'])) ?></div>
                                    </td>
                                    <td class="px-4 py-3 text-xs text-slate-700 font-semibold"><?= $row['nama_matkul'] ?></td>
                                    <td class="hidden md:table-cell px-4 py-3 text-xs text-slate-600"><?= $row['ruangan'] ?></td>
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
                    <p>Jadwal piket proyektor ini digilir secara otomatis berdasarkan urutan NIM mahasiswa. Jika Anda berhalangan hadir, harap menukar jadwal dengan teman sekelas secara mandiri.</p>
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
