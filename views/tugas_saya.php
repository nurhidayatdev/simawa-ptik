<?php
session_start();
include '../koneksi.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../index");
    exit;
}

$user_id = $_SESSION['user_id'];
$role = $_SESSION['role'];
$nama_lengkap = $_SESSION['nama_lengkap'];
$foto = $_SESSION['foto'];

// Filter logic
$show_done = isset($_GET['show_done']) && $_GET['show_done'] == '1';

// Periode Aktif
$q_periode = mysqli_query($koneksi, "SELECT id FROM periode WHERE status = 'aktif' LIMIT 1");
$periode = mysqli_fetch_assoc($q_periode);
$periode_id = $periode['id'] ?? 0;

// Handle Actions
if (isset($_POST['action'])) {
    if ($_POST['action'] == 'add_personal') {
        $judul = mysqli_real_escape_string($koneksi, $_POST['judul']);
        $deskripsi = mysqli_real_escape_string($koneksi, $_POST['deskripsi']);
        $deadline = mysqli_real_escape_string($koneksi, $_POST['deadline']);
        mysqli_query($koneksi, "INSERT INTO tugas_personal (user_id, periode_id, judul, deskripsi, deadline) VALUES ('$user_id', '$periode_id', '$judul', '$deskripsi', '$deadline')");
        $_SESSION['success'] = "Tugas personal berhasil ditambahkan.";
    } elseif ($_POST['action'] == 'toggle_personal') {
        $id = intval($_POST['id']);
        $status = $_POST['status'] == 'selesai' ? 'belum' : 'selesai';
        mysqli_query($koneksi, "UPDATE tugas_personal SET status = '$status' WHERE id = '$id' AND user_id = '$user_id'");
    } elseif ($_POST['action'] == 'delete_personal') {
        $id = intval($_POST['id']);
        mysqli_query($koneksi, "DELETE FROM tugas_personal WHERE id = '$id' AND user_id = '$user_id'");
        $_SESSION['success'] = "Tugas personal berhasil dihapus.";
    } elseif ($_POST['action'] == 'toggle_global') {
        $tugas_id = intval($_POST['tugas_id']);
        $check = mysqli_query($koneksi, "SELECT id FROM tugas_global_done WHERE user_id = '$user_id' AND tugas_id = '$tugas_id'");
        if (mysqli_num_rows($check) > 0) {
            mysqli_query($koneksi, "DELETE FROM tugas_global_done WHERE user_id = '$user_id' AND tugas_id = '$tugas_id'");
        } else {
            mysqli_query($koneksi, "INSERT INTO tugas_global_done (user_id, tugas_id, periode_id) VALUES ('$user_id', '$tugas_id', '$periode_id')");
        }
    }
    $redir = $show_done ? "?show_done=1" : "";
    header("Location: tugas_saya" . $redir);
    exit;
}

// Get Personal Tasks
$personal_cond = $show_done ? " AND status = 'selesai'" : " AND status = 'belum'";
$personal_tasks = mysqli_query($koneksi, "SELECT * FROM tugas_personal WHERE user_id = '$user_id' AND periode_id = '$periode_id' $personal_cond ORDER BY deadline ASC");

// Get Global Tasks
$global_cond = $show_done ? " AND gd.id IS NOT NULL" : " AND gd.id IS NULL";
$global_tasks = mysqli_query($koneksi, "
    SELECT t.*, mk.nama_matkul, gd.id as is_done
    FROM tugas t
    LEFT JOIN mata_kuliah mk ON t.matkul_id = mk.id
    LEFT JOIN tugas_global_done gd ON t.id = gd.tugas_id AND gd.user_id = '$user_id' AND gd.periode_id = '$periode_id'
    WHERE t.periode_id = '$periode_id' AND t.status = 'belum' $global_cond
    ORDER BY t.deadline_tanggal ASC
");

?>
<!DOCTYPE html>
<html lang="id">

<head>
    <title>Tugas Saya - PTIK C 2024</title>
    <?php include '../includes/head.php'; ?>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>

<body class="bg-slate-50">
    <?php include '../includes/menu_db.php'; ?>

    <main class="ml-0 lg:ml-48 pt-20 p-6 flex flex-col min-h-screen">
        <div class="max-w-5xl mx-auto w-full">
            <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4 mb-8">
                <div>
                    <h2 class="text-2xl font-bold text-slate-800">Tugas Saya</h2>
                    <p class="text-sm text-slate-500">Kelola tugas kuliah dan tugas pribadi Anda di sini.</p>
                </div>
                <div class="flex items-center gap-2">
                    <div class="flex bg-white/50 backdrop-blur-sm p-1 rounded-2xl border border-slate-200 shadow-sm">
                        <a href="tugas_saya" class="px-4 py-2 rounded-xl text-[10px] font-bold uppercase tracking-wider transition-all <?= !$show_done ? 'bg-indigo-600 text-white shadow-lg shadow-indigo-100' : 'text-slate-500 hover:text-slate-700' ?>">
                            Tugas Aktif
                        </a>
                        <a href="tugas_saya?show_done=1" class="px-4 py-2 rounded-xl text-[10px] font-bold uppercase tracking-wider transition-all <?= $show_done ? 'bg-indigo-600 text-white shadow-lg shadow-indigo-100' : 'text-slate-500 hover:text-slate-700' ?>">
                            Tugas Selesai
                        </a>
                    </div>
                    <button onclick="showAddModal()" class="bg-white hover:bg-slate-50 text-indigo-600 border border-indigo-100 w-10 h-10 rounded-2xl shadow-sm transition-all flex items-center justify-center">
                        <i class="fas fa-plus"></i>
                    </button>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                <!-- Global Tasks (From Admin) -->
                <div>
                    <h3 class="text-lg font-semibold text-slate-800 mb-4 flex items-center gap-2">
                        <i class="fas fa-university text-indigo-600"></i> Tugas Kelas
                    </h3>
                    <div class="space-y-4">
                        <?php if (mysqli_num_rows($global_tasks) > 0): ?>
                            <?php while ($row = mysqli_fetch_assoc($global_tasks)):
                                $row['deadline_fmt'] = date('d M Y', strtotime($row['deadline_tanggal'])) . ' ' . date('H:i', strtotime($row['deadline_pukul'])) . ' WITA';
                                $row['deskripsi_clean'] = strip_tags($row['deskripsi']);
                                $json_data = htmlspecialchars(json_encode($row));
                            ?>
                                <div onclick="showDetail('global', <?= $json_data ?>)" class="bg-white p-5 rounded-3xl border border-slate-200 shadow-sm hover:shadow-md transition-all cursor-pointer group <?= $row['is_done'] ? 'opacity-60 grayscale-[0.5]' : '' ?>">
                                    <div class="flex items-start gap-4">
                                        <form method="POST" class="mt-1" onclick="event.stopPropagation()">
                                            <input type="hidden" name="action" value="toggle_global">
                                            <input type="hidden" name="tugas_id" value="<?= $row['id'] ?>">
                                            <button type="submit" class="w-7 h-7 rounded-xl border-2 flex items-center justify-center transition-all <?= $row['is_done'] ? 'bg-indigo-600 border-indigo-600 text-white' : 'border-slate-200 text-transparent hover:border-indigo-400' ?>">
                                                <i class="fas fa-check text-xs"></i>
                                            </button>
                                        </form>
                                        <div class="flex-1">
                                            <div class="flex justify-between items-start mb-1">
                                                <span class="text-[10px] font-bold text-indigo-600 uppercase tracking-widest"><?= htmlspecialchars($row['nama_matkul']) ?></span>
                                                <div class="flex flex-col items-end">
                                                    <span class="text-[10px] font-semibold text-slate-500"><?= date('d M Y', strtotime($row['deadline_tanggal'])) ?></span>
                                                    <span class="text-[9px] font-medium text-rose-400 uppercase tracking-tighter"><?= date('H:i', strtotime($row['deadline_pukul'])) ?> WITA</span>
                                                </div>
                                            </div>
                                            <h4 class="text-sm font-semibold text-slate-800 leading-tight <?= $row['is_done'] ? 'line-through text-slate-400' : '' ?>"><?= htmlspecialchars($row['nama_matkul']) ?></h4>
                                            <div class="text-[11px] text-slate-500 mt-2 leading-relaxed line-clamp-2"><?= $row['deskripsi_clean'] ?></div>

                                            <div class="flex flex-wrap gap-2 mt-4" onclick="event.stopPropagation()">
                                                <?php if (!empty($row['file_tugas'])): ?>
                                                    <a href="../storage/tugas/<?= htmlspecialchars($row['file_tugas']) ?>" target="_blank" class="flex items-center gap-1.5 px-3 py-1.5 bg-blue-50 text-blue-600 rounded-lg text-[10px] font-semibold border border-blue-100 hover:bg-blue-100 transition-colors">
                                                        <i class="fas fa-file-download"></i> Unduh File
                                                    </a>
                                                <?php endif; ?>

                                                <?php if (!empty($row['link_kirim'])): ?>
                                                    <a href="<?= htmlspecialchars($row['link_kirim']) ?>" target="_blank" class="flex items-center gap-1.5 px-3 py-1.5 bg-indigo-50 text-indigo-600 rounded-lg text-[10px] font-semibold border border-indigo-100 hover:bg-indigo-100 transition-colors">
                                                        <i class="fas fa-paper-plane"></i> Kirim Tugas
                                                    </a>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <div class="bg-slate-100/50 border-2 border-dashed border-slate-200 rounded-2xl p-8 text-center">
                                <i class="fas fa-check-circle text-slate-300 text-3xl mb-2"></i>
                                <p class="text-xs text-slate-400 font-medium"><?= $show_done ? 'Belum ada tugas kelas.' : 'Semua tugas kelas telah selesai! ✨' ?></p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Personal Tasks -->
                <div>
                    <h3 class="text-lg font-semibold text-slate-800 mb-4 flex items-center gap-2">
                        <i class="fas fa-user text-indigo-600"></i> Tugas Pribadi
                    </h3>
                    <div class="space-y-4">
                        <?php if (mysqli_num_rows($personal_tasks) > 0): ?>
                            <?php while ($row = mysqli_fetch_assoc($personal_tasks)):
                                $row['deadline_fmt'] = $row['deadline'] ? date('d M Y, H:i', strtotime($row['deadline'])) : 'Tanpa Deadline';
                                $json_data = htmlspecialchars(json_encode($row));
                            ?>
                                <div onclick="showDetail('personal', <?= $json_data ?>)" class="bg-white p-4 rounded-2xl border border-slate-200 shadow-sm hover:shadow-md transition-all cursor-pointer group <?= $row['status'] == 'selesai' ? 'opacity-60 grayscale-[0.5]' : '' ?>">
                                    <div class="flex items-start gap-3">
                                        <form method="POST" class="mt-1" onclick="event.stopPropagation()">
                                            <input type="hidden" name="action" value="toggle_personal">
                                            <input type="hidden" name="id" value="<?= $row['id'] ?>">
                                            <input type="hidden" name="status" value="<?= $row['status'] ?>">
                                            <button type="submit" class="w-6 h-6 rounded-lg border-2 flex items-center justify-center transition-all <?= $row['status'] == 'selesai' ? 'bg-indigo-600 border-indigo-600 text-white' : 'border-slate-300 text-transparent hover:border-indigo-400' ?>">
                                                <i class="fas fa-check text-[10px]"></i>
                                            </button>
                                        </form>
                                        <div class="flex-1">
                                            <div class="flex justify-between items-start">
                                                <span class="text-[10px] font-bold text-rose-500 uppercase tracking-widest">Pribadi</span>
                                                <div class="flex items-center gap-2 text-slate-400" onclick="event.stopPropagation()">
                                                    <span class="text-[10px] font-semibold"><?= $row['deadline'] ? date('d M', strtotime($row['deadline'])) : '' ?></span>
                                                    <form id="delete-form-<?= $row['id'] ?>" method="POST">
                                                        <input type="hidden" name="action" value="delete_personal">
                                                        <input type="hidden" name="id" value="<?= $row['id'] ?>">
                                                        <button type="button" onclick="confirmDelete(<?= $row['id'] ?>)" class="hover:text-red-500 transition-colors">
                                                            <i class="fas fa-trash-alt text-[10px]"></i>
                                                        </button>
                                                    </form>
                                                </div>
                                            </div>
                                            <h4 class="text-sm font-semibold text-slate-800 mt-1 <?= $row['status'] == 'selesai' ? 'line-through text-slate-400' : '' ?>"><?= htmlspecialchars($row['judul']) ?></h4>
                                            <?php if ($row['deskripsi']): ?>
                                                <p class="text-[10px] text-slate-500 mt-1 line-clamp-2"><?= htmlspecialchars($row['deskripsi']) ?></p>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <div class="bg-slate-100/50 border-2 border-dashed border-slate-200 rounded-2xl p-8 text-center">
                                <i class="fas fa-plus-circle text-slate-300 text-3xl mb-2 cursor-pointer" onclick="showAddModal()"></i>
                                <p class="text-xs text-slate-400 font-medium"><?= $show_done ? 'Belum ada tugas pribadi.' : 'Tugas pribadi sudah beres semua! 🚀' ?></p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <?php include '../includes/footer_db.php'; ?>
    </main>

    <!-- Modal Tambah Tugas -->
    <div id="addModal" class="fixed inset-0 bg-slate-900/40 backdrop-blur-sm z-[100] hidden items-center justify-center p-4">
        <div class="bg-white rounded-[2rem] w-full max-w-sm overflow-hidden shadow-2xl animate-fade-in-up">
            <div class="bg-indigo-600 p-6 text-white text-center">
                <h3 class="text-xl font-bold">Tambah Tugas</h3>
                <p class="text-xs text-indigo-100 mt-1 opacity-80">Rencanakan tugas harian Anda.</p>
            </div>
            <form method="POST" class="p-6 space-y-4">
                <input type="hidden" name="action" value="add_personal">
                <div>
                    <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1.5 ml-1">Judul Tugas</label>
                    <input type="text" name="judul" required placeholder="Contoh: Belajar PHP Dasar" class="w-full px-4 py-3 rounded-xl bg-slate-50 border border-slate-200 focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-600 transition-all text-sm font-medium">
                </div>
                <div>
                    <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1.5 ml-1">Deadline (Opsional)</label>
                    <input type="datetime-local" name="deadline" class="w-full px-4 py-3 rounded-xl bg-slate-50 border border-slate-200 focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-600 transition-all text-sm font-medium">
                </div>
                <div>
                    <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1.5 ml-1">Keterangan</label>
                    <textarea name="deskripsi" rows="3" placeholder="Tambahkan catatan jika perlu..." class="w-full px-4 py-3 rounded-xl bg-slate-50 border border-slate-200 focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-600 transition-all text-sm font-medium resize-none"></textarea>
                </div>
                <div class="flex gap-3 pt-2">
                    <button type="button" onclick="closeAddModal()" class="flex-1 px-4 py-3 rounded-xl bg-slate-100 text-slate-600 text-xs font-bold uppercase tracking-widest hover:bg-slate-200 transition-colors">Batal</button>
                    <button type="submit" class="flex-1 px-4 py-3 rounded-xl bg-indigo-600 text-white text-xs font-bold uppercase tracking-widest hover:bg-indigo-700 shadow-lg shadow-indigo-200 transition-all">Simpan</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Detail Task Modal -->
    <div id="detailModal" class="fixed inset-0 bg-slate-900/40 backdrop-blur-sm z-[150] hidden items-center justify-center p-4">
        <div class="bg-white rounded-[2rem] w-full max-w-sm overflow-hidden shadow-2xl animate-fade-in-up">
            <div id="detailHeader" class="p-6 text-white relative">
                <button onclick="closeDetailModal()" class="absolute top-5 right-5 w-7 h-7 flex items-center justify-center rounded-full bg-black/10 hover:bg-black/20 transition-all">
                    <i class="fas fa-times text-xs"></i>
                </button>
                <div class="flex justify-between items-start mb-3">
                    <span id="detailBadge" class="px-2.5 py-0.5 rounded-full text-[8px] font-bold uppercase tracking-[0.1em] bg-white/20">Label</span>
                </div>
                <h3 id="detailTitle" class="text-xl font-bold leading-tight mb-1">Judul Tugas</h3>
                <p id="detailSubtitle" class="text-xs text-white/70 font-medium">Subtitle</p>
            </div>
            <div class="p-6 space-y-5">
                <div>
                    <label class="block text-[9px] font-bold text-slate-400 uppercase tracking-widest mb-2 ml-1">Deskripsi Tugas</label>
                    <div id="detailDesc" class="text-xs text-slate-600 bg-slate-50/50 p-4 rounded-2xl border border-slate-100 min-h-[80px] leading-relaxed">
                        ...
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div class="bg-slate-50/50 p-3 rounded-2xl border border-slate-100">
                        <label class="block text-[8px] font-bold text-slate-400 uppercase tracking-widest mb-1.5">Tenggat Waktu</label>
                        <div class="flex items-center gap-2 text-slate-700 font-bold">
                            <div class="w-7 h-7 rounded-lg bg-indigo-50 flex items-center justify-center">
                                <i class="far fa-calendar-alt text-indigo-500 text-[10px]"></i>
                            </div>
                            <span id="detailDeadline" class="text-[10px]">Date</span>
                        </div>
                    </div>
                    <div class="bg-slate-50/50 p-3 rounded-2xl border border-slate-100">
                        <label class="block text-[8px] font-bold text-slate-400 uppercase tracking-widest mb-1.5">Status Saat Ini</label>
                        <div id="detailStatus" class="flex items-center gap-2 font-bold">
                            ...
                        </div>
                    </div>
                </div>

                <div id="detailActions" class="flex flex-col gap-2 pt-1">
                    <!-- Dynamic buttons -->
                </div>
            </div>
        </div>
    </div>

    <script>
        function showAddModal() {
            document.getElementById('addModal').classList.remove('hidden');
            document.getElementById('addModal').classList.add('flex');
        }

        function closeAddModal() {
            document.getElementById('addModal').classList.add('hidden');
            document.getElementById('addModal').classList.remove('flex');
        }

        function showDetail(type, data) {
            const modal = document.getElementById('detailModal');
            const header = document.getElementById('detailHeader');
            const badge = document.getElementById('detailBadge');
            const title = document.getElementById('detailTitle');
            const subtitle = document.getElementById('detailSubtitle');
            const desc = document.getElementById('detailDesc');
            const deadline = document.getElementById('detailDeadline');
            const status = document.getElementById('detailStatus');
            const actions = document.getElementById('detailActions');

            actions.innerHTML = '';

            if (type === 'global') {
                header.className = 'p-6 text-white relative bg-gradient-to-br from-indigo-600 to-violet-700';
                badge.innerText = 'Tugas Kelas';
                title.innerText = data.nama_matkul;
                subtitle.innerText = 'Tugas dari Dosen / Admin';
                desc.innerHTML = data.deskripsi || '<span class="italic text-slate-400 text-xs text-center block py-4">Tidak ada deskripsi detail untuk tugas ini.</span>';
                deadline.innerText = data.deadline_fmt;

                if (data.is_done) {
                    status.className = 'flex items-center gap-2.5 text-emerald-600 font-bold';
                    status.innerHTML = '<div class="w-7 h-7 rounded-lg bg-emerald-50 flex items-center justify-center"><i class="fas fa-check-circle text-xs"></i></div> <span class="text-[11px]">Selesai</span>';
                } else {
                    status.className = 'flex items-center gap-2.5 text-amber-500 font-bold';
                    status.innerHTML = '<div class="w-7 h-7 rounded-lg bg-amber-50 flex items-center justify-center"><i class="fas fa-clock text-xs"></i></div> <span class="text-[11px]">Belum Beres</span>';
                }

                if (data.file_tugas) {
                    actions.innerHTML += `
                        <a href="../storage/tugas/${data.file_tugas}" target="_blank" class="w-full flex items-center justify-center gap-2.5 py-3.5 rounded-2xl bg-blue-600 text-white text-xs font-bold uppercase tracking-widest hover:bg-blue-700 transition-all shadow-lg shadow-blue-100">
                            <i class="fas fa-file-download"></i> Unduh Berkas Tugas
                        </a>
                    `;
                }
                if (data.link_kirim) {
                    actions.innerHTML += `
                        <a href="${data.link_kirim}" target="_blank" class="w-full flex items-center justify-center gap-2.5 py-3.5 rounded-2xl bg-indigo-50 text-indigo-600 text-xs font-bold uppercase tracking-widest hover:bg-indigo-100 transition-all border border-indigo-100">
                            <i class="fas fa-paper-plane"></i> Kirim Jawaban Tugas
                        </a>
                    `;
                }
            } else {
                header.className = 'p-6 text-white relative bg-gradient-to-br from-rose-500 to-pink-600';
                badge.innerText = 'Tugas Pribadi';
                title.innerText = data.judul;
                subtitle.innerText = 'Catatan Tugas Mandiri';
                desc.innerHTML = data.deskripsi || '<span class="italic text-slate-400 text-xs text-center block py-4">Tidak ada catatan tambahan.</span>';
                deadline.innerText = data.deadline_fmt;

                if (data.status === 'selesai') {
                    status.className = 'flex items-center gap-2.5 text-emerald-600 font-bold';
                    status.innerHTML = '<div class="w-7 h-7 rounded-lg bg-emerald-50 flex items-center justify-center"><i class="fas fa-check-circle text-xs"></i></div> <span class="text-[11px]">Selesai</span>';
                } else {
                    status.className = 'flex items-center gap-2.5 text-rose-500 font-bold';
                    status.innerHTML = '<div class="w-7 h-7 rounded-lg bg-rose-50 flex items-center justify-center"><i class="fas fa-clock text-xs"></i></div> <span class="text-[11px]">Berjalan</span>';
                }
            }

            modal.classList.remove('hidden');
            modal.classList.add('flex');
            document.body.style.overflow = 'hidden';
        }

        function closeDetailModal() {
            document.getElementById('detailModal').classList.add('hidden');
            document.getElementById('detailModal').classList.remove('flex');
            document.body.style.overflow = 'auto';
        }

        function confirmDelete(id) {
            Swal.fire({
                title: 'Hapus Tugas?',
                text: "Tugas yang dihapus tidak dapat dikembalikan!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#ef4444',
                cancelButtonColor: '#f1f5f9',
                confirmButtonText: 'Ya, Hapus!',
                cancelButtonText: 'Batal',
                reverseButtons: true,
                padding: '2rem',
                customClass: {
                    popup: 'rounded-[2rem]',
                    confirmButton: 'rounded-xl px-6 py-3 text-xs font-bold uppercase tracking-widest',
                    cancelButton: 'rounded-xl px-6 py-3 text-xs font-bold uppercase tracking-widest text-slate-600'
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    document.getElementById('delete-form-' + id).submit();
                }
            });
        }

        // Close on backdrop click
        document.getElementById('detailModal').addEventListener('click', function(e) {
            if (e.target === this) closeDetailModal();
        });

        <?php if (isset($_SESSION['success'])): ?>
            Swal.fire({
                icon: 'success',
                title: 'Berhasil!',
                text: '<?= $_SESSION['success'] ?>',
                confirmButtonColor: '#4f46e5',
                padding: '2rem',
                customClass: {
                    popup: 'rounded-[2rem]',
                    confirmButton: 'rounded-xl px-6 py-3 text-xs font-bold uppercase tracking-widest'
                }
            });
            <?php unset($_SESSION['success']); ?>
        <?php endif; ?>
    </script>
    <?php include '../includes/script_db.php'; ?>
</body>

</html>