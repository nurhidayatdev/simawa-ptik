<?php
session_start();
include '../../koneksi.php';

if (!isset($_SESSION['user_id'])) {
	header("Location: ../../index.php");
	exit;
}

$user_id = $_SESSION['user_id'];
$nama_lengkap = $_SESSION['nama_lengkap'] ?? '';
$foto = $_SESSION['foto'] ?? '';

$role = $_SESSION['role'];

/* =====================
   AMBIL PERIODE AKTIF
   ===================== */
$q_periode = mysqli_query($koneksi, "SELECT * FROM periode WHERE status = 'aktif' LIMIT 1");
$periode = mysqli_fetch_assoc($q_periode);
$periode_text = $periode ? $periode['tahun_periode'] . ' - ' . $periode['semester_nama'] : 'Tidak ada periode aktif';

// counts
$counts = [];
$tables = [
	'mahasiswa' => 'db_mahasiswa.php',
	'mata_kuliah' => 'db_matkul.php',
	'dosen' => 'db_dosen.php',
	'jadwal_matkul' => 'db_jadwal.php',
	'tugas' => 'db_tugas.php',
	'galeri' => 'db_galeri.php',
	'pesan' => 'db_pesan.php',
	'akademik' => 'db_berita.php'
];

foreach ($tables as $table => $link) {
	$res = mysqli_query($koneksi, "SELECT COUNT(*) AS cnt FROM $table");
	if ($res) {
		$row = mysqli_fetch_assoc($res);
		$counts[$table] = $row['cnt'] ?? 0;
	} else {
		$counts[$table] = 0;
	}
}

// Task Status Distribution
$q_task_stat = mysqli_query($koneksi, "SELECT status, COUNT(*) as cnt FROM tugas GROUP BY status");
$task_stats = ['belum' => 0, 'selesai' => 0];
while ($row = mysqli_fetch_assoc($q_task_stat)) {
    $task_stats[$row['status']] = $row['cnt'];
}

// Recent Messages (Anonymous)
$q_recent_pesan = mysqli_query($koneksi, "SELECT * FROM pesan ORDER BY tanggal DESC LIMIT 5");

// Recent News (Akademik)
$q_recent_berita = mysqli_query($koneksi, "SELECT * FROM akademik ORDER BY id DESC LIMIT 5");

// Gallery Photos Count
$q_gal_photos = mysqli_query($koneksi, "SELECT COUNT(*) as cnt FROM galeri_foto");
$gallery_photos_count = mysqli_fetch_assoc($q_gal_photos)['cnt'] ?? 0;

?>
<!DOCTYPE html>
<html lang="en">

<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<meta name="theme-color" content="#6c27dc" />
	<link rel="icon" href="img/icon.png" />
	<title>Dashboard Ringkasan - PTIK C</title>
	<link href="../../frontend/tailwind/src/output.css" rel="stylesheet">
	<link href="../../frontend/tailwind/src/input.css" rel="stylesheet">
	<link rel="preconnect" href="https://fonts.googleapis.com">
	<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
	<link href="https://fonts.googleapis.com/css2?family=Poppins:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&display=swap" rel="stylesheet">
	<!-- Font Awesome -->
	<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
	<style>
		.card-hover:hover { transform: translateY(-4px); }
		.rotate-180 { transform: rotate(180deg); }
		@media (max-width: 768px) { .mobile-menu-open { transform: translateX(0) !important; } }
	</style>
</head>

<body class="bg-slate-100">
	<!-- Header dengan z-index lebih tinggi -->
	<header class="bg-indigo-950  fixed w-full top-0 z-50">
		<div class="flex justify-between items-center px-6 py-2">
			<div class="flex items-center">
				<img src="../../img/logo.png" alt="Logo Universitas Negeri Makassar" class="h-10 w-10 mr-3">
				<span class="text-xl font-bold text-white">Dashboard PTIK C</span>
			</div>
			<!-- Mobile Menu Button dengan z-index yang sesuai -->
			<button id="mobile-menu-button" class="text-slate-100 hover:text-indigo-950 lg:hidden p-2 rounded-lg hover:bg-gray-100">
				<i class="fa fa-bars w-6 h-6"></i>
			</button>
			<div class="hidden lg:flex items-center space-x-4">
				<div class="relative">
					<button id="profile-button" class="flex items-center space-x-2">
						<span class="text-white"><?php echo htmlspecialchars($nama_lengkap); ?></span>
						<img src="../img/profile/<?php echo htmlspecialchars($foto); ?>" alt="Profile" class="w-8 h-8 rounded-full">
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
                             
                             <a href="db_berita.php"
                                 class="block px-4 py-2 text-xs text-slate-800 hover:bg-slate-100 rounded-lg">Berita</a>
                             <a href="db_kalender.php"
                                 class="block px-4 py-2 text-xs text-slate-800 hover:bg-slate-100 rounded-lg">Kalender</a>
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

                        <a href="db_galeri.php" class="flex items-center px-4 py-2 text-slate-800 hover:bg-gray-100 rounded-lg">
                            <i class="fa fa-images w-5 h-5 mr-4"></i>
                            <span class="text-xs">Galeri</span>
                        </a>

                        <a href="db_pesan.php" class="flex items-center px-4 py-2 text-slate-800 hover:bg-gray-100 rounded-lg">
                            <i class="fa fa-envelope w-5 h-5 mr-4"></i>
                            <span class="text-xs">Pesan Anonim</span>
                        </a>

<?php if ($role === 'Administrator Utama'): ?>
                        <button
                            class="flex items-center justify-between w-full px-4 py-2 text-slate-800 hover:bg-gray-100 rounded-lg">
                            <div class="flex items-center">
                                <i class="fa fa-user w-5 h-5 mr-4"></i>
                                <a href="db_users.php">
                                    <span class="text-xs">Users</span>
                                </a>
                            </div>
                        </button>
<?php endif; ?>
                    </div>
                </div>
            </nav>
        </div>
    </aside>

	<main class="ml-0 lg:ml-48 pt-20 p-6 flex flex-col min-h-screen">
		<div class="max-w-6xl mx-auto flex-grow w-full">
			

            <!-- Welcome & Realtime Section -->
            <div class="mb-6 bg-white  p-6 rounded-lg shadow-sm flex flex-col md:flex-row justify-between items-center gap-4">
                <div class="flex items-center gap-4">
                     <div class="h-14 w-14 rounded-full overflow-hidden border-2 border-indigo-200">
                        <img src="../img/profile/<?= htmlspecialchars($foto) ?>" alt="Profile" class="w-full h-full object-cover">
                     </div>
                     <div>
                        <h2 class="font-bold text-lg text-slate-800">Selamat Datang, <span class="text-indigo-600"><?= htmlspecialchars($nama_lengkap) ?></span>!</h2>
                        <p class="text-sm text-slate-600 flex items-center gap-2">
                            <i class="fa fa-calendar-check text-green-600"></i>
                            Periode Aktif: <span class="font-semibold bg-green-100 text-green-700 px-2 py-0.5 rounded text-xs"><?= htmlspecialchars($periode_text) ?></span>
                         </p>
                    </div>
                </div>
                
                <div class="flex items-center gap-4 mt-2 md:mt-0">
                    <div class="flex items-center gap-1.5 bg-slate-100 px-3 py-1.5 rounded-lg border border-slate-200">
                        <i class="fa fa-calendar-alt text-indigo-500 text-xs"></i>
                        <div id="realtime-date" class="text-xs font-medium text-slate-600 uppercase tracking-wide">...</div>
                    </div>
                    <div class="flex items-center gap-1.5 bg-slate-100 px-3 py-1.5 rounded-lg border border-slate-200">
                        <i class="fa fa-clock text-indigo-500 text-xs"></i>
                        <div id="realtime-clock" class="text-xs font-mono font-bold text-slate-700 tracking-wider">--:--:--</div>
                    </div>
                </div>
               
            </div>

				<a href="db_mahasiswa.php" class="bg-white p-4 rounded-xl shadow-sm border border-slate-200 card-hover flex items-center gap-4">
                    <div class="h-12 w-12 bg-indigo-100 rounded-lg flex items-center justify-center text-indigo-600">
                        <i class="fa fa-user-graduate text-xl"></i>
                    </div>
                    <div>
					    <div class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Mahasiswa</div>
					    <div class="text-xl font-bold text-slate-800"><?= number_format($counts['mahasiswa']) ?></div>
                    </div>
				</a>

				<a href="db_matkul.php" class="bg-white p-4 rounded-xl shadow-sm border border-slate-200 card-hover flex items-center gap-4">
                    <div class="h-12 w-12 bg-blue-100 rounded-lg flex items-center justify-center text-blue-600">
                        <i class="fa fa-book text-xl"></i>
                    </div>
                    <div>
					    <div class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Mata Kuliah</div>
					    <div class="text-xl font-bold text-slate-800"><?= number_format($counts['mata_kuliah']) ?></div>
                    </div>
				</a>

				<a href="db_tugas.php" class="bg-white p-4 rounded-xl shadow-sm border border-slate-200 card-hover flex items-center gap-4">
                    <div class="h-12 w-12 bg-orange-100 rounded-lg flex items-center justify-center text-orange-600">
                        <i class="fa fa-tasks text-xl"></i>
                    </div>
                    <div>
					    <div class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Tugas Aktif</div>
					    <div class="text-xl font-bold text-slate-800"><?= number_format($counts['tugas']) ?></div>
                    </div>
				</a>

				<a href="db_galeri.php" class="bg-white p-4 rounded-xl shadow-sm border border-slate-200 card-hover flex items-center gap-4">
                    <div class="h-12 w-12 bg-purple-100 rounded-lg flex items-center justify-center text-purple-600">
                        <i class="fa fa-images text-xl"></i>
                    </div>
                    <div>
					    <div class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Galeri Foto</div>
					    <div class="text-xl font-bold text-slate-800"><?= number_format($gallery_photos_count) ?></div>
                    </div>
				</a>
			</div>

            <!-- Visualization Section -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mt-6">
                <!-- Task Completion Chart -->
                <div class="bg-white p-6 rounded-xl shadow-sm border border-slate-200 lg:col-span-1">
                    <h3 class="text-sm font-bold text-slate-800 mb-4 flex items-center gap-2">
                        <i class="fa fa-chart-pie text-indigo-500"></i>
                        Status Tugas
                    </h3>
                    <div class="relative h-64">
                        <canvas id="taskStatusChart"></canvas>
                    </div>
                </div>

                <!-- Recent Activity & Info -->
                <div class="bg-white p-6 rounded-xl shadow-sm border border-slate-200 lg:col-span-2">
                    <h3 class="text-sm font-bold text-slate-800 mb-4 flex items-center gap-2">
                        <i class="fa fa-envelope-open-text text-indigo-500"></i>
                        Pesan Anonim Terbaru
                    </h3>
                    <div class="space-y-4">
                        <?php if (mysqli_num_rows($q_recent_pesan) > 0): ?>
                            <?php while($p = mysqli_fetch_assoc($q_recent_pesan)): ?>
                            <div class="flex gap-4 p-3 rounded-lg bg-slate-50 border border-slate-100 border-l-4 border-l-indigo-500">
                                <div class="flex-grow">
                                    <p class="text-xs text-slate-700 line-clamp-1 italic">"<?= htmlspecialchars($p['isi_pesan']) ?>"</p>
                                    <span class="text-[10px] text-slate-400"><?= date('d M Y, H:i', strtotime($p['tanggal'])) ?></span>
                                </div>
                            </div>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <p class="text-xs text-slate-400 text-center py-8">Belum ada pesan anonim.</p>
                        <?php endif; ?>
                    </div>
                    <div class="mt-4 text-center">
                        <a href="db_pesan.php" class="text-[10px] font-bold text-indigo-600 hover:text-indigo-800 uppercase tracking-widest">Lihat Semua Pesan <i class="fa fa-arrow-right ml-1"></i></a>
                    </div>
                </div>
            </div>

            <!-- News Activity Section -->
            <div class="grid grid-cols-1 lg:grid-cols-1 gap-6 mt-6">
                <div class="bg-white p-6 rounded-xl shadow-sm border border-slate-200">
                    <h3 class="text-sm font-bold text-slate-800 mb-4 flex items-center gap-2">
                        <i class="fa fa-newspaper text-indigo-500"></i>
                        Berita & Pengumuman Terbaru
                    </h3>
                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-xs">
                            <thead>
                                <tr class="text-slate-400 uppercase tracking-widest border-b border-slate-100">
                                    <th class="pb-2 font-bold">Judul Berita</th>
                                    <th class="pb-2 font-bold">Preview</th>
                                    <th class="pb-2 font-bold text-right">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-50">
                                <?php if (mysqli_num_rows($q_recent_berita) > 0): ?>
                                    <?php while($b = mysqli_fetch_assoc($q_recent_berita)): ?>
                                    <tr>
                                        <td class="py-3 font-semibold text-slate-700"><?= htmlspecialchars($b['judul']) ?></td>
                                        <td class="py-3 text-slate-500 line-clamp-1"><?= htmlspecialchars(substr($b['deskripsi'], 0, 50)) ?>...</td>
                                        <td class="py-3 text-right">
                                            <a href="db_berita.php" class="text-indigo-600 hover:underline font-bold">Edit</a>
                                        </td>
                                    </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="3" class="py-10 text-center text-slate-400 italic">Belum ada berita dipublikasikan.</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

			

		</div>
		<footer class="text-xs text-indigo-900 text-center mb-0 pb-0 mt-6">© 2025 Kelas PTIK C - Teknik Informatika dan Komputer FT UNM. All rights reserved.</footer>
	</main>

	<script>
		// Mobile menu toggle dengan perbaikan
		const mobileMenuButton = document.getElementById('mobile-menu-button');
		const sidebar = document.getElementById('sidebar');

		if (mobileMenuButton) {
			mobileMenuButton.addEventListener('click', (e) => {
				e.stopPropagation(); // Prevent event bubbling
				sidebar.classList.toggle('-translate-x-full');
				sidebar.classList.toggle('mobile-menu-open');
			});
		}

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
		// Realtime Clock
        function updateClock() {
            const now = new Date();
            const timeString = now.toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit', second: '2-digit' });
            const dateString = now.toLocaleDateString('id-ID', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' });
            
            document.getElementById('realtime-clock').textContent = timeString.replaceAll('.', ':');
            document.getElementById('realtime-date').textContent = dateString;
        }
        setInterval(updateClock, 1000);
        updateClock(); // initial call

        // Task Status Chart
        const taskCtx = document.getElementById('taskStatusChart').getContext('2d');
        new Chart(taskCtx, {
            type: 'doughnut',
            data: {
                labels: ['Belum Selesai', 'Selesai'],
                datasets: [{
                    data: [<?= $task_stats['belum'] ?>, <?= $task_stats['selesai'] ?>],
                    backgroundColor: ['#f59e0b', '#10b981'],
                    borderWidth: 0,
                    hoverOffset: 4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            usePointStyle: true,
                            padding: 20,
                            font: { size: 10 }
                        }
                    }
                },
                cutout: '70%'
            }
        });
	</script>
</body>

</html>

