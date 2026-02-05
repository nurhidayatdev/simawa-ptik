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
		$rowClass = (($id++ % 2 == 0) ? 'bg-slate-100' : 'bg-white') . ' hover:bg-indigo-100';
		echo '<tr class="' . $rowClass . '">';
		echo '<td class="px-3 py-2 whitespace-nowrap text-xs text-slate-800">' . ($no++) . '</td>';
		echo '<td class="px-3 py-2 text-xs text-slate-800">' . htmlspecialchars($row['nama_matkul'] ?? '—') . '</td>';
		echo '<td class="px-3 py-2 whitespace-nowrap text-xs text-slate-800">
				<span class="block">' . ($row['deadline_tanggal'] ? date('Y-m-d', strtotime($row['deadline_tanggal'])) : '') . '</span>
				<span class="block md:inline md:ml-1 text-[10px] md:text-xs text-slate-500 md:text-slate-800">' . htmlspecialchars($row['deadline_pukul']) . '</span>
			  </td>';
		$desc = strip_tags($row['deskripsi']);
		if (strlen($desc) > 120) $desc = substr($desc, 0, 117) . '...';
		echo '<td class="hidden md:table-cell px-3 py-2 whitespace-nowrap text-xs text-slate-800">' . htmlspecialchars($desc) . '</td>';
		if (!empty($row['file_tugas'])) {
			echo '<td class="hidden md:table-cell px-3 py-2 whitespace-nowrap text-xs text-slate-800"><a href="../storage/tugas/' . htmlspecialchars($row['file_tugas']) . '" target="_blank" class="text-blue-600 underline">Lihat</a></td>';
		} else {
			echo '<td class="hidden md:table-cell px-3 py-2 whitespace-nowrap text-xs text-slate-800">-</td>';
		}
		if (!empty($row['link_kirim'])) {
			echo '<td class="hidden md:table-cell px-3 py-2 whitespace-nowrap text-xs text-slate-800"><a href="' . htmlspecialchars($row['link_kirim']) . '" target="_blank" class="text-blue-600 underline">Kirim</a></td>';
		} else {
			echo '<td class="hidden md:table-cell px-3 py-2 whitespace-nowrap text-xs text-slate-800">-</td>';
		}
		$statusColor = ($row['status'] === 'selesai') ? 'bg-green-100 text-green-800 border-green-200' : 'bg-yellow-100 text-yellow-800 border-yellow-200';
		echo '<td class="hidden md:table-cell px-3 py-2 whitespace-nowrap text-xs"><span class="px-2 py-1 rounded-full border ' . $statusColor . '">' . ucfirst($row['status']) . '</span></td>';
		echo '<td class="px-3 py-2 whitespace-nowrap text-sm text-slate-500">';
		echo '<button type="button" onclick="openModal(this)" 
				data-matkul="' . htmlspecialchars($row['nama_matkul'] ?? '—') . '" 
				data-deskripsi="' . htmlspecialchars($row['deskripsi']) . '" 
				data-deadline="' . ($row['deadline_tanggal'] ? date('d M Y', strtotime($row['deadline_tanggal'])) : '') . ' - ' . htmlspecialchars($row['deadline_pukul']) . '" 
				data-link="' . htmlspecialchars($row['link_kirim']) . '" 
				data-file="' . htmlspecialchars($row['file_tugas']) . '" 
				data-status="' . $row['status'] . '" 
				title="Info">
				<i class="fa fa-info-circle text-indigo-600 hover:text-indigo-900 mr-3"></i>
			  </button>';
		if ($role === 'Administrator Utama' || $role === 'Sekretaris' || $role === 'Koordinator Akademik'):
			echo '<a href="../actions/edit.php?tabel=tugas&id=' . $row['id'] . '" title="Edit"><i class="fa fa-edit text-blue-600 hover:text-blue-900 mr-3"></i></a>';
			echo '<a href="../actions/hapus.php?tabel=tugas&id=' . $row['id'] . '" title="Hapus" onclick="return confirm(\'Hapus tugas ini?\');"><i class="fa fa-trash text-red-600 hover:text-red-900"></i></a>';
		endif;
		echo '</td>';
		echo '</tr>';
	}
	exit;
}

?>
<!DOCTYPE html>
<html lang="en">

<head>

	<title>Data Tugas Kuliah - PTIK C 2024</title>
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
			<div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-6">
				<h3 class="text-xl font-bold text-slate-800">Data Tugas</h3>

				<div class="flex-col sm:flex-row gap-2 w-full sm:w-auto">
					<form method="GET" class="w-full">
						<div class="flex flex-row gap-2 items-center">
							<select name="status" id="statusFilter" onchange="this.form.submit()"
								class="basis-1/5 px-2 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-1 focus:ring-slate-400 text-xs">
								<option value="belum" <?= ($status_filter == 'belum') ? 'selected' : '' ?>>Belum Selesai</option>
								<option value="selesai" <?= ($status_filter == 'selesai') ? 'selected' : '' ?>>Selesai</option>
							</select>
							<input
								id="searchInput"
								type="text"
								name="search"
								value="<?= htmlspecialchars($_GET['search'] ?? '') ?>"
								placeholder="Cari Mata Kuliah / Deskripsi..."
								class="basis-3/5 w-full px-3 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-1 focus:ring-slate-400 text-xs">
							<?php if ($role === 'Administrator Utama' || $role === 'Sekretaris' || $role === 'Koordinator Akademik'): ?>
								<button type="button" onclick="confirmReminderTelegram()" class="bg-blue-600 text-white px-3 py-2 rounded-lg hover:bg-blue-800 text-center text-xs flex items-center justify-center mr-1" title="Kirim Reminder Telegram">
									<i class="fa fa-paper-plane"></i>
								</button>
								<a href="../tugas" class="bg-indigo-600 text-white px-3 py-2 rounded-lg hover:bg-indigo-800 text-center text-xs flex items-center justify-center mr-1" title="Lihat View">
									<i class="fa fa-eye"></i>
								</a>
								<a href="../actions/tambah.php?tabel=tugas" class="bg-green-600 text-white px-3 py-2 rounded-lg hover:bg-green-800 text-center text-xs flex items-center justify-center" title="Tambah">
									<i class="fa fa-plus"></i>
								</a>
							<?php endif; ?>
						</div>
					</form>
				</div>

			</div>

			<div class="overflow-x-auto">
				<table class="min-w-full divide-y divide-slate-200">
					<thead class="bg-indigo-900">
						<tr>
							<th class="px-3 py-3 text-left text-xs font-bold text-white uppercase tracking-wider">
								No</th>
							<th class="px-3 py-3 text-left text-xs font-bold text-white uppercase tracking-wider">
								Mata Kuliah</th>
							<th class="px-3 py-3 text-left text-xs font-bold text-white uppercase tracking-wider">
								Deadline</th>
							<th class="hidden md:table-cell px-3 py-3 text-left text-xs font-bold text-white uppercase tracking-wider">
								Deskripsi</th>
							<th class="hidden md:table-cell px-3 py-3 text-left text-xs font-bold text-white uppercase tracking-wider">
								File</th>
							<th class="hidden md:table-cell px-3 py-3 text-left text-xs font-bold text-white uppercase tracking-wider">
								Link Kirim</th>
							<th class="hidden md:table-cell px-3 py-3 text-left text-xs font-bold text-white uppercase tracking-wider">
								Status</th>
							<th class="px-3 py-3 text-left text-xs font-bold text-white uppercase tracking-wider">
								Actions</th>
						</tr>
					</thead>
					<tbody class="bg-white divide-y divide-slate-200">
						<?php
						$no = 1;
						$id = 1;
						while ($row = mysqli_fetch_assoc($result)) : ?>
							<tr class="<?= ($id++ % 2 == 0) ? 'bg-slate-100' : 'bg-white' ?> hover:bg-indigo-100">

								<td class="px-3 py-2 whitespace-nowrap text-xs text-slate-800"><?= $no++; ?></td>
								<td class="px-3 py-2 text-xs text-slate-800"><?= htmlspecialchars($row['nama_matkul'] ?? '—'); ?></td>
								<td class="px-3 py-2 whitespace-nowrap text-xs text-slate-800">
									<span class="block"><?= ($row['deadline_tanggal'] ? date('Y-m-d', strtotime($row['deadline_tanggal'])) : ''); ?></span>
									<span class="block md:inline md:ml-1 text-[10px] md:text-xs text-slate-500 md:text-slate-800"><?= htmlspecialchars($row['deadline_pukul']); ?></span>
								</td>
								<td class="hidden md:table-cell px-3 py-2 text-xs text-slate-800">
									<?php $d = strip_tags($row['deskripsi']);
									echo htmlspecialchars((strlen($d) > 120) ? substr($d, 0, 117) . '...' : $d); ?>
								</td>
								<td class="hidden md:table-cell px-3 py-2 whitespace-nowrap text-xs text-slate-800">
									<?php if (!empty($row['file_tugas'])): ?>
										<a href="../storage/tugas/<?= htmlspecialchars($row['file_tugas']) ?>" target="_blank" class="text-blue-600 underline">Lihat</a>
									<?php else: echo '-';
									endif; ?>
								</td>
								<td class="hidden md:table-cell px-3 py-2 whitespace-nowrap text-xs text-slate-800">
									<?php if (!empty($row['link_kirim'])): ?>
										<a href="<?= htmlspecialchars($row['link_kirim']) ?>" target="_blank" class="text-blue-600 underline">Kirim</a>
									<?php else: echo '-';
									endif; ?>
								</td>
								<td class="hidden md:table-cell px-3 py-2 whitespace-nowrap text-xs text-slate-800">
									<?php
									$statusColor = ($row['status'] === 'selesai') ? 'bg-green-100 text-green-800 border-green-200' : 'bg-yellow-100 text-yellow-800 border-yellow-200';
									?>
									<span class="px-1.5 py-0.5 rounded-full border text-[10px] font-semibold <?= $statusColor ?>"><?= ucfirst($row['status']) ?></span>
								</td>
								<td class="px-3 py-2 whitespace-nowrap text-sm text-slate-500">
									<button type="button" onclick="openModal(this)"
										data-matkul="<?= htmlspecialchars($row['nama_matkul'] ?? '—') ?>"
										data-deskripsi="<?= htmlspecialchars($row['deskripsi']) ?>"
										data-deadline="<?= ($row['deadline_tanggal'] ? date('d M Y', strtotime($row['deadline_tanggal'])) : '') . ' - ' . htmlspecialchars($row['deadline_pukul']) ?>"
										data-link="<?= htmlspecialchars($row['link_kirim']) ?>"
										data-file="<?= htmlspecialchars($row['file_tugas']) ?>"
										data-status="<?= $row['status'] ?>"
										title="Info">
										<i class="fa fa-info-circle text-indigo-600 hover:text-indigo-900 mr-3 cursor-pointer"></i>
									</button>
									<?php if ($role === 'Administrator Utama' || $role === 'Sekretaris' || $role === 'Koordinator Akademik'): ?>
										<a href="../actions/edit.php?tabel=tugas&id=<?= $row['id']; ?>" title="Edit"><i class="fa fa-edit text-blue-600 hover:text-blue-900 mr-3"></i></a>
										<a href="../actions/hapus.php?tabel=tugas&id=<?= $row['id']; ?>" title="Hapus" onclick="return confirm('Hapus tugas ini?');"><i class="fa fa-trash text-red-600 hover:text-red-900"></i></a>
									<?php endif; ?>
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
	<div id="taskModal" class="fixed inset-0 bg-black/50 z-[60] hidden items-center justify-center p-4 backdrop-blur-sm transition-opacity duration-300">
		<div class="bg-white rounded-2xl shadow-2xl max-w-lg w-full max-h-[90vh] flex flex-col transform scale-95 opacity-0 transition-all duration-300" id="modalContent">
			<!-- Modal Header -->
			<div class="p-6 border-b border-gray-100 flex justify-between items-start bg-indigo-50/50 rounded-t-2xl">
				<div>
					<span id="modalStatus" class="inline-block px-2 py-1 text-[10px] font-bold rounded uppercase tracking-wider mb-2"></span>
					<h3 id="modalTitle" class="text-xl font-bold text-gray-900 leading-snug"></h3>
				</div>
				<button onclick="closeModal()" class="text-gray-400 hover:text-gray-600 transition p-1 hover:bg-gray-100 rounded-full">
					<i class="fas fa-times text-xl"></i>
				</button>
			</div>

			<!-- Modal Body -->
			<div class="p-6 overflow-y-auto">
				<div class="mb-6">
					<h4 class="text-xs font-bold text-gray-500 uppercase tracking-wider mb-2">Deskripsi Tugas</h4>
					<div id="modalDesc" class="text-sm text-gray-700 whitespace-pre-line leading-relaxed"></div>
				</div>

				<div class="grid grid-cols-1 gap-4 bg-gray-50 p-4 rounded-xl border border-gray-100">
					<div class="flex items-start">
						<div class="bg-indigo-100 p-2 rounded-lg mr-3">
							<i class="far fa-clock text-indigo-600 text-sm"></i>
						</div>
						<div>
							<p class="text-[10px] font-bold text-gray-500 uppercase">Deadline Pengumpulan</p>
							<p id="modalDeadline" class="text-sm font-semibold text-gray-800"></p>
						</div>
					</div>

					<div id="modalFileContainer" class="items-start hidden">
						<div class="bg-blue-100 p-2 rounded-lg mr-3">
							<i class="far fa-file-alt text-blue-600 text-sm"></i>
						</div>
						<div>
							<p class="text-[10px] font-bold text-gray-500 uppercase">Lampiran File</p>
							<a id="modalFileLink" href="#" target="_blank" class="text-sm font-semibold text-blue-600 hover:text-blue-800 hover:underline flex items-center">
								Unduh Dokumen <i class="fas fa-external-link-alt ml-1 text-[10px]"></i>
							</a>
						</div>
					</div>
				</div>
			</div>

			<!-- Modal Footer -->
			<div class="p-4 border-t border-gray-100 bg-gray-50 rounded-b-2xl flex justify-end gap-3">
				<button onclick="closeModal()" class="px-4 py-2 bg-white border border-gray-300 text-gray-700 text-xs font-semibold rounded-lg hover:bg-gray-50 transition">
					Tutup
				</button>
				<a id="modalActionBtn" href="#" target="_blank" class="px-5 py-2 bg-indigo-600 text-white text-xs font-semibold rounded-lg hover:bg-indigo-700 shadow-md hover:shadow-lg transition flex items-center">
					<i class="fas fa-paper-plane mr-2"></i> Kirim Tugas
				</a>
				<span id="modalNoAction" class="hidden px-4 py-2 bg-gray-200 text-gray-500 text-xs font-semibold rounded-lg cursor-not-allowed items-center">
					<i class="fas fa-lock mr-2"></i> Tidak ada link
				</span>
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
				statusBadge.className = 'inline-block px-2 py-1 text-[10px] font-bold rounded uppercase tracking-wider mb-2 bg-gray-100 text-gray-500';
			} else {
				statusBadge.textContent = 'Aktif';
				statusBadge.className = 'inline-block px-2 py-1 text-[10px] font-bold rounded uppercase tracking-wider mb-2 bg-yellow-100 text-yellow-700';
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

		// SweetAlert Confirmation for Telegram Reminder
		function confirmReminderTelegram() {
			Swal.fire({
				title: 'Kirim Reminder Telegram?',
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