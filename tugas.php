<?php
include 'koneksi.php';

include 'includes/pesan.php';

// Auto-update status based on deadline before fetching
mysqli_query($koneksi, "UPDATE tugas SET status = 'selesai' WHERE (deadline_tanggal < CURDATE() OR (deadline_tanggal = CURDATE() AND deadline_pukul < CURTIME())) AND status = 'belum'");

$view_history = isset($_GET['history']) && $_GET['history'] == '1';
$status_filter = $view_history ? 'selesai' : 'belum';

// Get active period
$q_active = mysqli_query($koneksi, "SELECT id, tahun_periode, semester_nama FROM periode WHERE status = 'aktif' LIMIT 1");
$active_period = mysqli_fetch_assoc($q_active);
$periode_id = $active_period['id'] ?? 0;

$query = mysqli_query($koneksi, "
    SELECT t.*, mk.nama_matkul 
    FROM tugas t 
    LEFT JOIN mata_kuliah mk ON t.matkul_id = mk.id 
    WHERE t.periode_id = '$periode_id' AND t.status = '$status_filter' 
    ORDER BY t.deadline_tanggal DESC
");

// Fetch distinct mata kuliah for filter
$query_matkul = mysqli_query($koneksi, "
    SELECT DISTINCT mk.nama_matkul 
    FROM tugas t 
    JOIN mata_kuliah mk ON t.matkul_id = mk.id 
    WHERE t.periode_id = '$periode_id' AND t.status = '$status_filter'
    ORDER BY mk.nama_matkul ASC
");

?>
<!DOCTYPE html>
<html lang="id">

<head>
    <title>Tugas Mata Kuliah - Kelas PTIK C 2024</title>
    <?php include 'includes/head.php'; ?>
</head>

<body class="bg-gray-50 dark:bg-slate-950 min-h-screen flex flex-col transition-colors duration-300">

    <?php include 'includes/header_main.php'; ?>

    <!-- spacer to avoid content hidden under fixed header -->
    <div class="h-10"></div>

    <main class="flex-grow">
        <section class="py-12 p-6">
            <div class="container mx-auto">
                <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4 mb-8">
                    <div>
                        <h2 class="text-3xl font-bold text-gray-800 dark:text-white transition-colors">Tugas Kuliah</h2>
                        <p class="text-xs text-gray-500 dark:text-slate-400 mt-1 transition-colors"><?= $active_period['tahun_periode'] ?> - <?= $active_period['semester_nama'] ?></p>
                    </div>

                    <div class="flex gap-2 items-center">
                        <select id="filterMatkul" class="bg-white dark:bg-slate-900 border border-gray-200 dark:border-slate-800 text-gray-700 dark:text-slate-200 text-xs rounded-lg focus:ring-indigo-500 focus:border-indigo-500 block px-3 py-2 shadow-sm transition-colors">
                            <option value="">Semua Mata Kuliah</option>
                            <?php while ($m = mysqli_fetch_assoc($query_matkul)): ?>
                                <option value="<?= htmlspecialchars($m['nama_matkul']) ?>"><?= htmlspecialchars($m['nama_matkul']) ?></option>
                            <?php endwhile; ?>
                        </select>

                        <?php if ($view_history): ?>
                            <a href="tugas" class="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-lg shadow-sm text-xs font-semibold flex items-center transition h-full">
                                <i class="fas fa-arrow-left mr-2"></i> Kembali
                            </a>
                        <?php else: ?>
                            <a href="tugas?history=1" class="bg-white dark:bg-slate-900 hover:bg-gray-50 dark:hover:bg-slate-800 text-gray-700 dark:text-slate-200 px-4 py-2 rounded-lg shadow-sm border border-gray-200 dark:border-slate-800 text-xs font-semibold flex items-center transition h-full">
                                <i class="fas fa-history mr-2"></i> Riwayat
                            </a>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    <?php if (mysqli_num_rows($query) > 0) : ?>
                        <?php while ($data = mysqli_fetch_assoc($query)) : ?>
                            <article
                                class="task-card bg-white dark:bg-slate-900 rounded-xl shadow-sm border border-gray-100 dark:border-slate-800 overflow-hidden hover:shadow-md transition flex flex-col cursor-pointer hover:border-indigo-200 dark:hover:border-indigo-800"
                                onclick="openModal(this)"
                                data-matkul="<?= htmlspecialchars($data['nama_matkul']) ?>"
                                data-deskripsi="<?= htmlspecialchars($data['deskripsi']) ?>"
                                data-deadline="<?= date('d M Y', strtotime($data['deadline_tanggal'])) ?> - <?= date('H:i', strtotime($data['deadline_pukul'])) ?>"
                                data-link="<?= htmlspecialchars($data['link_kirim']) ?>"
                                data-file="<?= htmlspecialchars($data['file_tugas']) ?>"
                                data-status="<?= $data['status'] ?>">
                                <div class="p-5 flex-grow">
                                    <div class="flex justify-between items-start mb-4">
                                        <span class="bg-indigo-50 dark:bg-indigo-950/50 text-indigo-700 dark:text-indigo-400 text-[10px] font-bold px-2 py-1 rounded uppercase tracking-wider transition-colors">
                                            <?= htmlspecialchars($data['nama_matkul']) ?>
                                        </span>
                                        <?php if ($data['status'] == 'selesai'): ?>
                                            <span class="bg-gray-100 dark:bg-slate-800 text-gray-500 dark:text-slate-400 text-[10px] font-bold px-2 py-1 rounded uppercase tracking-wider transition-colors">Selesai</span>
                                        <?php else: ?>
                                            <span class="bg-yellow-50 dark:bg-yellow-950/30 text-yellow-700 dark:text-yellow-500 text-[10px] font-bold px-2 py-1 rounded uppercase tracking-wider transition-colors">Aktif</span>
                                        <?php endif; ?>
                                    </div>

                                    <h3 class="font-bold text-gray-800 dark:text-white mb-2 truncate transition-colors"><?= htmlspecialchars($data['nama_matkul']) ?></h3>
                                    <div class="text-xs text-gray-600 dark:text-slate-400 mb-4 line-clamp-3 transition-colors"><?= nl2br(htmlspecialchars($data['deskripsi'])) ?></div>

                                    <div class="space-y-2 border-t border-gray-50 dark:border-slate-800 pt-2 mt-auto">
                                        <div class="flex items-center text-xs text-gray-500 dark:text-slate-400 transition-colors">
                                            <i class="far fa-clock w-5 text-indigo-500"></i>
                                            <span class="font-medium text-gray-700 dark:text-slate-300 mr-1 transition-colors">Deadline:</span>
                                            <span><?= date('d M Y', strtotime($data['deadline_tanggal'])) ?> - <?= date('H:i', strtotime($data['deadline_pukul'])) ?></span>
                                        </div>
                                    </div>
                                </div>
                                <div class="px-5 py-3 bg-gray-50 dark:bg-slate-800/50 text-center text-xs text-indigo-600 dark:text-indigo-400 font-medium border-t border-gray-100 dark:border-slate-800 transition-colors">
                                    Lihat Detail Tugas
                                </div>
                            </article>
                        <?php endwhile; ?>
                    <?php else : ?>
                        <div class="col-span-full text-center py-20 bg-white dark:bg-slate-900 rounded-xl border border-dashed border-gray-300 dark:border-slate-700 transition-colors">
                            <i class="fas fa-clipboard-list text-5xl text-gray-200 dark:text-slate-800 mb-4 transition-colors"></i>
                            <p class="text-gray-400 dark:text-slate-500 text-sm italic transition-colors">Belum ada data tugas yang tersedia.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </section>
    </main>
    <!-- Task Detail Modal -->
    <div id="taskModal" class="fixed inset-0 bg-black/50 dark:bg-black/80 z-[60] hidden items-center justify-center p-4 backdrop-blur-sm transition-opacity duration-300">
        <div class="bg-white dark:bg-slate-900 rounded-2xl shadow-2xl max-w-lg w-full max-h-[90vh] flex flex-col transform scale-95 opacity-0 transition-all duration-300 border border-slate-100 dark:border-slate-800" id="modalContent">
            <!-- Modal Header -->
            <div class="p-6 border-b border-gray-100 dark:border-slate-800 flex justify-between items-start bg-indigo-50/50 dark:bg-indigo-950/20 rounded-t-2xl transition-colors">
                <div>
                    <span id="modalStatus" class="inline-block px-2 py-1 text-[10px] font-bold rounded uppercase tracking-wider mb-2"></span>
                    <h3 id="modalTitle" class="text-xl font-bold text-gray-900 dark:text-white leading-snug transition-colors"></h3>
                </div>
                <button onclick="closeModal()" class="text-gray-400 hover:text-gray-600 dark:hover:text-white transition p-1 hover:bg-gray-100 dark:hover:bg-slate-800 rounded-full">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>

            <!-- Modal Body -->
            <div class="p-6 overflow-y-auto">
                <div class="mb-6">
                    <h4 class="text-xs font-bold text-gray-500 dark:text-slate-400 uppercase tracking-wider mb-2 transition-colors">Deskripsi Tugas</h4>
                    <div id="modalDesc" class="text-sm text-gray-700 dark:text-slate-300 whitespace-pre-line leading-relaxed transition-colors"></div>
                </div>

                <div class="grid grid-cols-1 gap-4 bg-gray-50 dark:bg-slate-800/50 p-4 rounded-xl border border-gray-100 dark:border-slate-800 transition-colors">
                    <div class="flex items-start">
                        <div class="bg-indigo-100 dark:bg-indigo-900/50 p-2 rounded-lg mr-3 transition-colors">
                            <i class="far fa-clock text-indigo-600 dark:text-indigo-400 text-sm"></i>
                        </div>
                        <div>
                            <p class="text-[10px] font-bold text-gray-500 dark:text-slate-500 uppercase transition-colors">Deadline Pengumpulan</p>
                            <p id="modalDeadline" class="text-sm font-semibold text-gray-800 dark:text-white transition-colors"></p>
                        </div>
                    </div>

                    <div id="modalFileContainer" class="items-start hidden">
                        <div class="bg-blue-100 dark:bg-blue-900/50 p-2 rounded-lg mr-3 transition-colors">
                            <i class="far fa-file-alt text-blue-600 dark:text-blue-400 text-sm"></i>
                        </div>
                        <div>
                            <p class="text-[10px] font-bold text-gray-500 dark:text-slate-500 uppercase transition-colors">Lampiran File</p>
                            <a id="modalFileLink" href="#" target="_blank" class="text-sm font-semibold text-blue-600 dark:text-blue-400 hover:text-blue-800 dark:hover:text-blue-300 hover:underline flex items-center transition-colors">
                                Unduh Dokumen <i class="fas fa-external-link-alt ml-1 text-[10px]"></i>
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Modal Footer -->
            <div class="p-4 border-t border-gray-100 dark:border-slate-800 bg-gray-50 dark:bg-slate-800/50 rounded-b-2xl flex justify-end gap-3 transition-colors">
                <button onclick="closeModal()" class="px-4 py-2 bg-white dark:bg-slate-900 border border-gray-300 dark:border-slate-700 text-gray-700 dark:text-slate-300 text-xs font-semibold rounded-lg hover:bg-gray-50 dark:hover:bg-slate-800 transition">
                    Tutup
                </button>
                <a id="modalActionBtn" href="#" target="_blank" class="px-5 py-2 bg-indigo-600 text-white text-xs font-semibold rounded-lg hover:bg-indigo-700 shadow-md hover:shadow-lg transition flex items-center">
                    <i class="fas fa-paper-plane mr-2"></i> Kirim Tugas
                </a>
                <span id="modalNoAction" class="hidden px-4 py-2 bg-gray-200 dark:bg-slate-800 text-gray-500 dark:text-slate-500 text-xs font-semibold rounded-lg cursor-not-allowed items-center transition-colors">
                    <i class="fas fa-lock mr-2"></i> Tidak ada link
                </span>
            </div>
        </div>
    </div>

    <?php include 'includes/footer_main.php'; ?>
    <?php include 'includes/script_main.php'; ?>


    <script>
        // Filter Logic
        const filterSelect = document.getElementById('filterMatkul');
        const tasks = document.querySelectorAll('.task-card');

        filterSelect.addEventListener('change', function() {
            const selected = this.value;
            tasks.forEach(task => {
                const matkul = task.dataset.matkul;
                if (!selected || matkul === selected) {
                    task.style.display = 'flex';
                } else {
                    task.style.display = 'none';
                }
            });
        });

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
                statusBadge.className = 'inline-block px-2 py-1 text-[10px] font-bold rounded uppercase tracking-wider mb-2 bg-gray-100 dark:bg-slate-800 text-gray-500 dark:text-slate-400';
            } else {
                statusBadge.textContent = 'Aktif';
                statusBadge.className = 'inline-block px-2 py-1 text-[10px] font-bold rounded uppercase tracking-wider mb-2 bg-yellow-100 dark:bg-yellow-900/30 text-yellow-700 dark:text-yellow-500';
            }

            // File Handling
            const fileContainer = document.getElementById('modalFileContainer');
            const fileLink = document.getElementById('modalFileLink');
            if (data.file) {
                fileContainer.classList.remove('hidden');
                fileLink.href = 'storage/tugas/' + data.file;
            } else {
                fileContainer.classList.add('hidden');
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
            // Small timeout to allow display:block to apply before opacity transition
            setTimeout(() => {
                modalContent.classList.remove('scale-95', 'opacity-0');
                modalContent.classList.add('scale-100', 'opacity-100');
            }, 10);
        }

        function closeModal() {
            modalContent.classList.remove('scale-100', 'opacity-100');
            modalContent.classList.add('scale-95', 'opacity-0');

            setTimeout(() => {
                modal.classList.add('hidden');
                modal.classList.remove('flex');
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
    </script>
</body>

</html>