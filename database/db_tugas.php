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
    $_SESSION['error'] = 'Akses ditolak! Administrator Utama tidak mengelola tugas perkuliahan kelas.';
    header('Location: ../dashboard_admin.php');
    exit;
}


/* =====================
   AMBIL PERIODE AKTIF
   ===================== */
$q_periode = mysqli_query($koneksi, "SELECT id FROM periode WHERE status = 'aktif' LIMIT 1");
$periode = mysqli_fetch_assoc($q_periode);
$periode_id = $periode['id'];

// Auto-update status based on deadline
mysqli_query($koneksi, "UPDATE tugas SET status = 'selesai' WHERE (deadline_tanggal < CURDATE() OR (deadline_tanggal = CURDATE() AND deadline_pukul < CURTIME())) AND status = 'belum'");

$search = $_GET['search'] ?? '';
$status_filter = $_GET['status'] ?? 'belum';

if (!empty($search)) {
	$stmt = mysqli_prepare(
		$koneksi,
		"SELECT t.id, t.matkul_id, t.deadline_tanggal, t.deadline_pukul, t.deskripsi, t.file_tugas, t.link_kirim, t.status, mk.nama_matkul
		 FROM tugas t
		 LEFT JOIN mata_kuliah mk ON t.matkul_id = mk.id
		 WHERE (mk.nama_matkul LIKE ? OR t.deskripsi LIKE ?)
		 AND t.periode_id = ?
		 AND t.status = ?
		 ORDER BY t.deadline_tanggal ASC"
	);

	$like = "%$search%";
	mysqli_stmt_bind_param($stmt, "ssis", $like, $like, $periode_id, $status_filter);
	mysqli_stmt_execute($stmt);
	$result = mysqli_stmt_get_result($stmt);
} else {
	$result = mysqli_query($koneksi, "SELECT t.id, t.matkul_id, t.deadline_tanggal, t.deadline_pukul, t.deskripsi, t.file_tugas, t.link_kirim, t.status, mk.nama_matkul FROM tugas t LEFT JOIN mata_kuliah mk ON t.matkul_id = mk.id WHERE t.periode_id = $periode_id AND t.status = '$status_filter' ORDER BY t.deadline_tanggal ASC");
}

// If this is an AJAX live-search request, return only the table rows
if (isset($_GET['ajax']) && $_GET['ajax'] == '1') {
	$no = 1;
	$id = 1;
	while ($row = mysqli_fetch_assoc($result)) {
		$isEven = ($id++ % 2 == 0);
		$rowClass = ($isEven ? 'bg-slate-50 dark:bg-slate-800/30' : 'bg-white dark:bg-slate-900') . ' hover:bg-indigo-50 dark:hover:bg-indigo-900/20 transition-colors';
		echo '<tr class="' . $rowClass . '">';
		echo '<td class="px-3 py-2 whitespace-nowrap text-xs text-slate-800 dark:text-slate-200 font-medium">' . ($no++) . '</td>';
		echo '<td class="px-3 py-2 text-xs font-semibold text-slate-800 dark:text-slate-100">' . htmlspecialchars($row['nama_matkul'] ?? '—') . '</td>';
		echo '<td class="px-3 py-2 whitespace-nowrap text-xs">
				<span class="block text-slate-800 dark:text-slate-200 font-bold">' . ($row['deadline_tanggal'] ? date('Y-m-d', strtotime($row['deadline_tanggal'])) : '') . '</span>
				<span class="block md:inline md:ml-1 text-[10px] text-slate-500 dark:text-slate-400 font-mono">' . htmlspecialchars($row['deadline_pukul']) . '</span>
			  </td>';
		$desc = strip_tags($row['deskripsi']);
		if (strlen($desc) > 120) $desc = substr($desc, 0, 117) . '...';
		echo '<td class="hidden md:table-cell px-3 py-2 text-xs text-slate-600 dark:text-slate-400">' . htmlspecialchars($desc) . '</td>';

		echo '<td class="hidden md:table-cell px-3 py-2 whitespace-nowrap text-xs">';
		if (!empty($row['file_tugas'])) {
			echo '<a href="../storage/tugas/' . htmlspecialchars($row['file_tugas']) . '" target="_blank" class="text-blue-600 dark:text-blue-400 hover:underline font-medium"><i class="fas fa-file-download mr-1"></i>Unduh</a>';
		} else {
			echo '<span class="text-slate-400 dark:text-slate-600">-</span>';
		}
		echo '</td>';

		echo '<td class="hidden md:table-cell px-3 py-2 whitespace-nowrap text-xs">';
		if (!empty($row['link_kirim'])) {
			echo '<a href="' . htmlspecialchars($row['link_kirim']) . '" target="_blank" class="text-indigo-600 dark:text-indigo-400 hover:underline font-medium"><i class="fas fa-paper-plane mr-1"></i>Kirim</a>';
		} else {
			echo '<span class="text-slate-400 dark:text-slate-600">-</span>';
		}
		echo '</td>';

		$isSelesai = ($row['status'] === 'selesai');
		$statusColor = $isSelesai
			? 'bg-green-100 dark:bg-green-900/40 text-green-700 dark:text-green-400 border-green-200 dark:border-green-800/50'
			: 'bg-yellow-100 dark:bg-yellow-900/40 text-yellow-700 dark:text-yellow-400 border-yellow-200 dark:border-yellow-800/50';

		echo '<td class="hidden md:table-cell px-3 py-2 whitespace-nowrap text-xs">
				<span class="px-2 py-0.5 rounded-full border text-[10px] font-bold uppercase tracking-tighter ' . $statusColor . '">' . ucfirst($row['status']) . '</span>
			  </td>';
		echo '<td class="px-3 py-2 whitespace-nowrap text-sm text-center">';
		echo '<div class="flex items-center justify-center gap-3">';
		echo '<button type="button" onclick="openModal(this)" 
				data-matkul="' . htmlspecialchars($row['nama_matkul'] ?? '—') . '" 
				data-deskripsi="' . htmlspecialchars($row['deskripsi']) . '" 
				data-deadline="' . ($row['deadline_tanggal'] ? date('d M Y', strtotime($row['deadline_tanggal'])) : '') . ' - ' . htmlspecialchars($row['deadline_pukul']) . '" 
				data-link="' . htmlspecialchars($row['link_kirim']) . '" 
				data-file="' . htmlspecialchars($row['file_tugas']) . '" 
				data-status="' . $row['status'] . '" 
				title="Info" class="text-indigo-600 dark:text-indigo-400 hover:text-indigo-900 dark:hover:text-indigo-300 transition-colors">
				<i class="fa fa-info-circle"></i>
			  </button>';
		if ($role === 'Sekretaris' || $role === 'Ketua Tingkat'):
			echo '<a href="../actions/edit.php?tabel=tugas&id=' . $row['id'] . '" title="Edit" class="text-blue-600 dark:text-blue-400 hover:text-blue-900 dark:hover:text-blue-300 transition-colors"><i class="fa fa-edit"></i></a>';
			echo '<a href="../actions/hapus.php?tabel=tugas&id=' . $row['id'] . '" title="Hapus" class="text-red-600 dark:text-red-400 hover:text-red-900 dark:hover:text-red-300 transition-colors"><i class="fa fa-trash"></i></a>';
		endif;
		echo '</div>';
		echo '</td>';
		echo '</tr>';
	}
	exit;
}

?>
<!DOCTYPE html>
<html lang="en">

<head>

	<title>Data Tugas Kuliah - SIMAWA PTIK 2024</title>
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
			<div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-6">
				<div>
					<h1 class="text-xl font-bold text-slate-800 dark:text-slate-100">Data Tugas Kuliah</h1>
				</div>

				<div class="flex-col sm:flex-row gap-2 w-full sm:w-auto">
					<form method="GET" class="w-full">
						<div class="flex flex-row gap-2 items-center">
							<select name="status" id="statusFilter" onchange="this.form.submit()"
								class="basis-1/4 px-2 py-2 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-xs text-slate-700 dark:text-slate-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 appearance-none cursor-pointer">
								<option value="belum" <?= ($status_filter == 'belum') ? 'selected' : '' ?>>Belum Selesai</option>
								<option value="selesai" <?= ($status_filter == 'selesai') ? 'selected' : '' ?>>Selesai</option>
							</select>
							<div class="relative flex-1 min-w-0">
								<input
									id="searchInput"
									type="text"
									name="search"
									value="<?= htmlspecialchars($_GET['search'] ?? '') ?>"
									placeholder="Cari Mata Kuliah..."
									class="w-full pl-9 pr-3 py-2 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 text-xs text-slate-700 dark:text-slate-200 transition-all">
								<i class="fa fa-search absolute left-3 top-2.5 text-slate-400 dark:text-slate-500 text-xs"></i>
							</div>
							<?php if ($role === 'Sekretaris' || $role === 'Ketua Tingkat'): ?>
								<button type="button" onclick="confirmReminderWhatsApp()" class="bg-blue-600 hover:bg-blue-700 text-white p-2.5 rounded-lg transition-all shadow-sm flex items-center justify-center shrink-0" title="Kirim Reminder WhatsApp">
									<i class="fa fa-paper-plane text-xs"></i>
								</button>
								<a href="../tugas" class="bg-indigo-600 hover:bg-indigo-700 text-white p-2.5 rounded-lg transition-all shadow-sm flex items-center justify-center shrink-0" title="Lihat View">
									<i class="fa fa-eye text-xs"></i>
								</a>
								<a href="../actions/tambah.php?tabel=tugas" class="bg-indigo-600 hover:bg-indigo-700 text-white p-2.5 rounded-lg transition-all shadow-sm flex items-center justify-center shrink-0" title="Tambah">
									<i class="fa fa-plus text-xs"></i>
								</a>
							<?php endif; ?>
						</div>
					</form>
				</div>
			</div>

			<div class="overflow-x-auto rounded-xl border border-slate-200 dark:border-slate-800 shadow-sm">
				<table class="min-w-full divide-y divide-slate-200 dark:divide-slate-800">
					<thead class="bg-indigo-900 dark:bg-slate-800">
						<tr>
							<th class="px-3 py-3 text-left text-[10px] font-bold text-white uppercase tracking-widest">No</th>
							<th class="px-3 py-3 text-left text-[10px] font-bold text-white uppercase tracking-widest">Mata Kuliah</th>
							<th class="px-3 py-3 text-left text-[10px] font-bold text-white uppercase tracking-widest">Deadline</th>
							<th class="hidden md:table-cell px-3 py-3 text-left text-[10px] font-bold text-white uppercase tracking-widest">Deskripsi</th>
							<th class="hidden md:table-cell px-3 py-3 text-left text-[10px] font-bold text-white uppercase tracking-widest">File</th>
							<th class="hidden md:table-cell px-3 py-3 text-left text-[10px] font-bold text-white uppercase tracking-widest">Link</th>
							<th class="hidden md:table-cell px-3 py-3 text-left text-[10px] font-bold text-white uppercase tracking-widest">Status</th>
							<th class="px-3 py-3 text-[10px] font-bold text-white uppercase tracking-widest text-center">Actions</th>
						</tr>
					</thead>
					<tbody class="bg-white dark:bg-slate-900 divide-y divide-slate-200 dark:divide-slate-800">
						<?php
						$no = 1;
						$id = 1;
						while ($row = mysqli_fetch_assoc($result)) :
							$isEven = ($id++ % 2 == 0);
							$rowClass = ($isEven ? 'bg-slate-50/50 dark:bg-slate-800/20' : 'bg-white dark:bg-slate-900') . ' hover:bg-indigo-50 dark:hover:bg-indigo-900/10 transition-colors';
						?>
							<tr class="<?= $rowClass ?>">
								<td class="px-3 py-2 whitespace-nowrap text-xs text-slate-800 dark:text-slate-200 font-medium"><?= $no++; ?></td>
								<td class="px-3 py-2 text-xs font-bold text-slate-800 dark:text-slate-100"><?= htmlspecialchars($row['nama_matkul'] ?? '—'); ?></td>
								<td class="px-3 py-2 whitespace-nowrap text-xs text-slate-800 dark:text-slate-200">
									<span class="block font-bold"><?= ($row['deadline_tanggal'] ? date('Y-m-d', strtotime($row['deadline_tanggal'])) : ''); ?></span>
									<span class="block md:inline md:ml-1 text-[10px] text-slate-500 dark:text-slate-400 font-mono"><?= htmlspecialchars($row['deadline_pukul']); ?></span>
								</td>
								<td class="hidden md:table-cell px-3 py-2 text-xs text-slate-600 dark:text-slate-400">
									<div class="line-clamp-1 max-w-[200px]">
										<?php $d = strip_tags($row['deskripsi']);
										echo htmlspecialchars((strlen($d) > 80) ? substr($d, 0, 77) . '...' : $d); ?>
									</div>
								</td>
								<td class="hidden md:table-cell px-3 py-2 whitespace-nowrap text-xs">
									<?php if (!empty($row['file_tugas'])): ?>
										<a href="../storage/tugas/<?= htmlspecialchars($row['file_tugas']) ?>" target="_blank" class="text-blue-600 dark:text-blue-400 hover:underline font-bold flex items-center gap-1.5"><i class="fas fa-file-download text-[10px]"></i>Lihat</a>
									<?php else: echo '<span class="text-slate-300 dark:text-slate-600">—</span>';
									endif; ?>
								</td>
								<td class="hidden md:table-cell px-3 py-2 whitespace-nowrap text-xs">
									<?php if (!empty($row['link_kirim'])): ?>
										<a href="<?= htmlspecialchars($row['link_kirim']) ?>" target="_blank" class="text-indigo-600 dark:text-indigo-400 hover:underline font-bold flex items-center gap-1.5"><i class="fas fa-paper-plane text-[10px]"></i>Kirim</a>
									<?php else: echo '<span class="text-slate-300 dark:text-slate-600">—</span>';
									endif; ?>
								</td>
								<td class="hidden md:table-cell px-3 py-2 whitespace-nowrap text-xs">
									<?php
									$isSelesai = ($row['status'] === 'selesai');
									$statusColor = $isSelesai
										? 'bg-green-100 dark:bg-green-900/40 text-green-700 dark:text-green-400 border-green-200 dark:border-green-800/50'
										: 'bg-yellow-100 dark:bg-yellow-900/40 text-yellow-700 dark:text-yellow-400 border-yellow-200 dark:border-yellow-800/50';
									?>
									<span class="px-2 py-0.5 rounded-full border text-[10px] font-black uppercase tracking-tighter <?= $statusColor ?>"><?= ucfirst($row['status']) ?></span>
								</td>
								<td class="px-3 py-2 whitespace-nowrap text-sm text-center">
									<div class="flex items-center justify-center gap-3">
										<button type="button" onclick="openModal(this)"
											data-matkul="<?= htmlspecialchars($row['nama_matkul'] ?? '—') ?>"
											data-deskripsi="<?= htmlspecialchars($row['deskripsi']) ?>"
											data-deadline="<?= ($row['deadline_tanggal'] ? date('d M Y', strtotime($row['deadline_tanggal'])) : '') . ' - ' . htmlspecialchars($row['deadline_pukul']) ?>"
											data-link="<?= htmlspecialchars($row['link_kirim']) ?>"
											data-file="<?= htmlspecialchars($row['file_tugas']) ?>"
											data-status="<?= $row['status'] ?>"
											title="Info" class="text-indigo-600 dark:text-indigo-400 hover:text-indigo-900 dark:hover:text-indigo-200 transition-colors">
											<i class="fa fa-info-circle"></i>
										</button>
										<?php if ($role === 'Sekretaris' || $role === 'Ketua Tingkat'): ?>
											<a href="../actions/edit.php?tabel=tugas&id=<?= $row['id']; ?>" title="Edit" class="text-blue-600 dark:text-blue-400 hover:text-blue-900 dark:hover:text-blue-200 transition-colors"><i class="fa fa-edit"></i></a>
											<a href="../actions/hapus.php?tabel=tugas&id=<?= $row['id']; ?>" title="Hapus" class="text-red-600 dark:text-red-400 hover:text-red-900 dark:hover:text-red-200 transition-colors" ><i class="fa fa-trash"></i></a>
										<?php endif; ?>
									</div>
								</td>
							</tr>
						<?php endwhile; ?>
					</tbody>
				</table>
			</div>
		</div>
		<?php include '../includes/footer_db.php'; ?>
	</main>

	<!-- Task Detail Modal -->
	<div id="taskModal" class="fixed inset-0 bg-black/60 z-[60] hidden items-center justify-center p-4 backdrop-blur-md transition-opacity duration-300">
		<div class="bg-white dark:bg-slate-900 rounded-2xl shadow-2xl max-w-lg w-full max-h-[90vh] flex flex-col transform scale-95 opacity-0 transition-all duration-300 border border-slate-200 dark:border-slate-800" id="modalContent">
			<!-- Modal Header -->
			<div class="p-6 border-b border-gray-100 dark:border-slate-800 flex justify-between items-start bg-slate-50/50 dark:bg-slate-800/30 rounded-t-2xl">
				<div>
					<span id="modalStatus" class="inline-block px-2 py-0.5 text-[10px] font-black rounded uppercase tracking-wider mb-2 border"></span>
					<h3 id="modalTitle" class="text-xl font-bold text-slate-900 dark:text-white leading-snug"></h3>
				</div>
				<button onclick="closeModal()" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 transition-all p-2 hover:bg-slate-100 dark:hover:bg-slate-800 rounded-full">
					<i class="fas fa-times text-lg"></i>
				</button>
			</div>

			<!-- Modal Body -->
			<div class="p-6 overflow-y-auto custom-scrollbar">
				<div class="mb-8">
					<h4 class="text-[10px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-widest mb-3 flex items-center gap-2">
						<i class="fas fa-align-left text-[8px]"></i> Deskripsi Tugas
					</h4>
					<div id="modalDesc" class="text-sm text-slate-700 dark:text-slate-300 whitespace-pre-line leading-relaxed font-medium"></div>
				</div>

				<div class="grid grid-cols-1 gap-4 bg-slate-50 dark:bg-slate-800/50 p-5 rounded-2xl border border-slate-100 dark:border-slate-700/50">
					<div class="flex items-start">
						<div class="bg-indigo-100 dark:bg-indigo-900/40 p-2.5 rounded-xl mr-4 border border-indigo-200 dark:border-indigo-800/50">
							<i class="far fa-clock text-indigo-600 dark:text-indigo-400 text-sm"></i>
						</div>
						<div>
							<p class="text-[10px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-widest">Deadline Pengumpulan</p>
							<p id="modalDeadline" class="text-sm font-bold text-slate-800 dark:text-slate-200 mt-0.5"></p>
						</div>
					</div>

					<div id="modalFileContainer" class="items-start hidden pt-4 border-t border-slate-200/50 dark:border-slate-700/50">
						<div class="bg-blue-100 dark:bg-blue-900/40 p-2.5 rounded-xl mr-4 border border-blue-200 dark:border-blue-800/50">
							<i class="far fa-file-alt text-blue-600 dark:text-blue-400 text-sm"></i>
						</div>
						<div class="flex-1">
							<p class="text-[10px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-widest">Lampiran File</p>
							<a id="modalFileLink" href="#" target="_blank" class="text-sm font-bold text-blue-600 dark:text-blue-400 hover:text-blue-800 dark:hover:text-blue-300 hover:underline flex items-center mt-0.5 transition-colors">
								Unduh Dokumen <i class="fas fa-external-link-alt ml-2 text-[10px]"></i>
							</a>
						</div>
					</div>
				</div>
			</div>

			<!-- Modal Footer -->
			<div class="p-6 border-t border-slate-100 dark:border-slate-800 bg-slate-50 dark:bg-slate-800/30 rounded-b-2xl flex items-center justify-between">
				<button onclick="closeModal()" class="px-5 py-2.5 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-700 dark:text-slate-300 text-xs font-bold rounded-xl hover:bg-slate-50 dark:hover:bg-slate-700 transition-all shadow-sm">
					Tutup
				</button>
				<div class="flex items-center gap-3">
					<a id="modalActionBtn" href="#" target="_blank" class="px-6 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-bold rounded-xl shadow-lg shadow-indigo-500/20 hover:shadow-indigo-500/40 transition-all flex items-center">
						<i class="fas fa-paper-plane mr-2"></i> Kirim Tugas
					</a>
					<span id="modalNoAction" class="hidden px-5 py-2.5 bg-slate-200 dark:bg-slate-800 text-slate-400 dark:text-slate-600 text-xs font-bold rounded-xl cursor-not-allowed items-center">
						<i class="fas fa-lock mr-2"></i> Tidak ada link
					</span>
				</div>
			</div>
		</div>
	</div>
	<?php include '../includes/script_db.php'; ?>
	<script>
		// Live search via AJAX (debounced) - keep focus in input
		(function() {
			const input = document.getElementById('searchInput');
			const statusSelect = document.getElementById('statusFilter');
			if (!input || !statusSelect) return;
			let timer = null;

			function performSearch() {
				clearTimeout(timer);
				timer = setTimeout(() => {
					const q = encodeURIComponent(input.value || '');
					const s = encodeURIComponent(statusSelect.value || 'belum');
					const url = window.location.pathname + '?search=' + q + '&status=' + s + '&ajax=1';
					fetch(url, {
							credentials: 'same-origin'
						})
						.then(r => r.text())
						.then(html => {
							const tbody = document.querySelector('table tbody');
							if (tbody) tbody.innerHTML = html || '<tr><td colspan="8" class="px-3 py-2 text-xs text-slate-500 text-center">No results</td></tr>';
						}).catch(err => console.error(err));
				}, 250);
			}
			input.addEventListener('input', performSearch);
			statusSelect.addEventListener('change', performSearch);
		})();

		// Modal Logic
		const modal = document.getElementById('taskModal');
		const modalContent = document.getElementById('modalContent');

		function openModal(element) {
			const data = element.dataset;

			// Populate Data
			document.getElementById('modalTitle').textContent = data.matkul;
			document.getElementById('modalDesc').textContent = data.deskripsi;
			document.getElementById('modalDeadline').textContent = data.deadline;

			// Status styling
			const statusBadge = document.getElementById('modalStatus');
			if (data.status === 'selesai') {
				statusBadge.textContent = 'Selesai';
				statusBadge.className = 'inline-block px-2 py-0.5 text-[10px] font-black rounded uppercase tracking-wider mb-2 bg-slate-100 dark:bg-slate-800 text-slate-500 dark:text-slate-400 border-slate-200 dark:border-slate-700';
			} else {
				statusBadge.textContent = 'Belum Selesai';
				statusBadge.className = 'inline-block px-2 py-0.5 text-[10px] font-black rounded uppercase tracking-wider mb-2 bg-yellow-100 dark:bg-yellow-900/40 text-yellow-700 dark:text-yellow-400 border-yellow-200 dark:border-yellow-800/50';
			}

			// File Handling
			const fileContainer = document.getElementById('modalFileContainer');
			const fileLink = document.getElementById('modalFileLink');
			if (data.file) {
				fileContainer.classList.remove('hidden');
				fileContainer.classList.add('flex');
				fileLink.href = '../storage/tugas/' + data.file;
			} else {
				fileContainer.classList.add('hidden');
				fileContainer.classList.remove('flex');
			}

			// Action Button Handling
			const actionBtn = document.getElementById('modalActionBtn');
			const noAction = document.getElementById('modalNoAction');
			if (data.link) {
				actionBtn.href = data.link;
				actionBtn.classList.remove('hidden');
				noAction.classList.add('hidden');
			} else {
				actionBtn.classList.add('hidden');
				noAction.classList.remove('hidden');
			}

			// Show Modal with Animation
			modal.classList.remove('hidden');
			modal.classList.add('flex');
			setTimeout(() => {
				modalContent.classList.remove('scale-95', 'opacity-0');
				modalContent.classList.add('scale-100', 'opacity-100');
			}, 10);
		}

		function closeModal() {
			modalContent.classList.remove('scale-100', 'opacity-100');
			modalContent.classList.add('scale-95', 'opacity-0');

			setTimeout(() => {
				modal.classList.remove('flex');
				modal.classList.add('hidden');
			}, 300);
		}

		// Close on outside click
		modal.addEventListener('click', (e) => {
			if (e.target === modal) {
				closeModal();
			}
		});

		// Close on Escape key
		document.addEventListener('keydown', (e) => {
			if (e.key === 'Escape' && !modal.classList.contains('hidden')) {
				closeModal();
			}
		});

		// SweetAlert Confirmation for WhatsApp Reminder
		function confirmReminderWhatsApp() {
			Swal.fire({
				title: 'Kirim Reminder WhatsApp?',
				text: "Mata kuliah yang belum dikumpulkan akan dikirim ke grup.",
				icon: 'question',
				showCancelButton: true,
				confirmButtonColor: '#3085d6',
				cancelButtonColor: '#d33',
				confirmButtonText: 'Ya, Kirim!',
				cancelButtonText: 'Batal'
			}).then((result) => {
				if (result.isConfirmed) {
					window.location.href = '../functions/kirim_tugas.php';
				}
			});
		}

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
				text: '<?= $_SESSION['error'] ?>'
			});
			<?php unset($_SESSION['error']); ?>
		<?php endif; ?>

		<?php if (isset($_SESSION['info'])): ?>
			Swal.fire({
				icon: 'info',
				title: 'Informasi',
				text: '<?= $_SESSION['info'] ?>'
			});
			<?php unset($_SESSION['info']); ?>
		<?php endif; ?>
	</script>
</body>

</html>