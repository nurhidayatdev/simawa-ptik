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
    } elseif ($_POST['action'] == 'edit_personal') {
        $id = intval($_POST['id']);
        $judul = mysqli_real_escape_string($koneksi, $_POST['judul']);
        $deskripsi = mysqli_real_escape_string($koneksi, $_POST['deskripsi']);
        $deadline = mysqli_real_escape_string($koneksi, $_POST['deadline']);
        mysqli_query($koneksi, "UPDATE tugas_personal SET judul = '$judul', deskripsi = '$deskripsi', deadline = '$deadline' WHERE id = '$id' AND user_id = '$user_id'");
        $_SESSION['success'] = "Tugas personal berhasil diupdate.";
    } elseif ($_POST['action'] == 'complete_all_global') {
        mysqli_query($koneksi, "
            INSERT INTO tugas_global_done (user_id, tugas_id, periode_id) 
            SELECT '$user_id', t.id, '$periode_id' 
            FROM tugas t 
            LEFT JOIN tugas_global_done gd ON t.id = gd.tugas_id AND gd.user_id = '$user_id' AND gd.periode_id = '$periode_id'
            WHERE t.periode_id = '$periode_id' AND gd.id IS NULL
        ");
        $_SESSION['success'] = "Semua tugas kelas telah ditandai selesai.";
    } elseif ($_POST['action'] == 'complete_all_personal') {
        mysqli_query($koneksi, "UPDATE tugas_personal SET status = 'selesai' WHERE user_id = '$user_id' AND periode_id = '$periode_id' AND status = 'belum'");
        $_SESSION['success'] = "Semua tugas pribadi telah ditandai selesai.";
    }
    $redir = $show_done ? "?show_done=1" : "";
    header("Location: tugas_saya" . $redir);
    exit;
}

// Get Personal Tasks
$personal_order = $show_done ? "DESC" : "ASC";
$personal_cond = $show_done ? " AND status = 'selesai'" : " AND status = 'belum'";
$personal_tasks = mysqli_query($koneksi, "SELECT * FROM tugas_personal WHERE user_id = '$user_id' AND periode_id = '$periode_id' $personal_cond ORDER BY deadline $personal_order");

// Get Global Tasks
$global_order = $show_done ? "DESC" : "ASC";
$global_cond = $show_done ? " AND gd.id IS NOT NULL" : " AND gd.id IS NULL";
$global_tasks = mysqli_query($koneksi, "
    SELECT t.*, mk.nama_matkul, gd.id as is_done
    FROM tugas t
    LEFT JOIN mata_kuliah mk ON t.matkul_id = mk.id
    LEFT JOIN tugas_global_done gd ON t.id = gd.tugas_id AND gd.user_id = '$user_id' AND gd.periode_id = '$periode_id'
    WHERE t.periode_id = '$periode_id' $global_cond
    ORDER BY t.deadline_tanggal $global_order
");

// Get Counts for Badges
$count_personal_active = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT COUNT(*) as cnt FROM tugas_personal WHERE user_id = '$user_id' AND periode_id = '$periode_id' AND status = 'belum'"))['cnt'] ?? 0;
$count_global_active = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT COUNT(*) as cnt FROM tugas t LEFT JOIN tugas_global_done gd ON t.id = gd.tugas_id AND gd.user_id = '$user_id' AND gd.periode_id = '$periode_id' WHERE t.periode_id = '$periode_id' AND gd.id IS NULL"))['cnt'] ?? 0;
$total_active = $count_personal_active + $count_global_active;

$count_personal_done = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT COUNT(*) as cnt FROM tugas_personal WHERE user_id = '$user_id' AND periode_id = '$periode_id' AND status = 'selesai'"))['cnt'] ?? 0;
$count_global_done = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT COUNT(*) as cnt FROM tugas t JOIN tugas_global_done gd ON t.id = gd.tugas_id AND gd.user_id = '$user_id' AND gd.periode_id = '$periode_id' WHERE t.periode_id = '$periode_id'"))['cnt'] ?? 0;
$total_done = $count_personal_done + $count_global_done;
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <title>Tugas Saya - PTIK C 2024</title>
    <?php include '../includes/head.php'; ?>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        .custom-scrollbar::-webkit-scrollbar {
            width: 4px;
        }

        .custom-scrollbar::-webkit-scrollbar-track {
            background: #f1f5f9;
            border-radius: 10px;
        }

        .custom-scrollbar::-webkit-scrollbar-thumb {
            background: #cbd5e1;
            border-radius: 10px;
        }

        .custom-scrollbar::-webkit-scrollbar-thumb:hover {
            background: #94a3b8;
        }
    </style>
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
                        <a href="tugas_saya" class="px-4 py-2 rounded-xl text-[10px] font-bold uppercase tracking-wider transition-all flex items-center gap-2 <?= !$show_done ? 'bg-indigo-600 text-white shadow-lg shadow-indigo-100' : 'text-slate-500 hover:text-slate-700' ?>">
                            Tugas Aktif
                            <?php if ($total_active > 0): ?>
                                <span class="flex items-center justify-center min-w-[18px] h-[18px] px-1 rounded-full text-[8px] font-extrabold <?= !$show_done ? 'bg-white text-indigo-600' : 'bg-slate-200 text-slate-600' ?>">
                                    <?= $total_active ?>
                                </span>
                            <?php endif; ?>
                        </a>
                        <a href="tugas_saya?show_done=1" class="px-4 py-2 rounded-xl text-[10px] font-bold uppercase tracking-wider transition-all flex items-center gap-2 <?= $show_done ? 'bg-indigo-600 text-white shadow-lg shadow-indigo-100' : 'text-slate-500 hover:text-slate-700' ?>">
                            Tugas Selesai
                            <?php if ($total_done > 0): ?>
                                <span class="flex items-center justify-center min-w-[18px] h-[18px] px-1 rounded-full text-[8px] font-extrabold <?= $show_done ? 'bg-white text-indigo-600' : 'bg-slate-200 text-slate-600' ?>">
                                    <?= $total_done ?>
                                </span>
                            <?php endif; ?>
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
                    <div class="flex justify-between items-center mb-4">
                        <h3 class="text-lg font-semibold text-slate-800 flex items-center gap-2">
                            <i class="fas fa-university text-indigo-600"></i> Tugas Kelas
                        </h3>
                        <?php if (!$show_done && $count_global_active > 0): ?>
                            <form id="form-complete-global" method="POST">
                                <input type="hidden" name="action" value="complete_all_global">
                                <button type="button" onclick="confirmCompleteAll('global')" class="text-[9px] font-bold text-indigo-600 bg-indigo-50 px-2.5 py-1.5 rounded-xl hover:bg-indigo-100 transition-all border border-indigo-100/50 flex items-center gap-1.5 ring-offset-2 focus:ring-2 focus:ring-indigo-100 outline-none">
                                    <i class="fas fa-check-double"></i> Selesaikan Semua
                                </button>
                            </form>
                        <?php endif; ?>
                    </div>
                    <div class="space-y-4">
                        <?php if (mysqli_num_rows($global_tasks) > 0): ?>
                            <?php while ($row = mysqli_fetch_assoc($global_tasks)):
                                $row['deadline_fmt'] = date('d M Y', strtotime($row['deadline_tanggal'])) . ' ' . date('H:i', strtotime($row['deadline_pukul'])) . ' WITA';
                                $row['deskripsi_clean'] = strip_tags($row['deskripsi']);
                                $deadline_ts = strtotime($row['deadline_tanggal'] . ' ' . $row['deadline_pukul']);
                                $is_overdue = ($deadline_ts < time() && !$row['is_done']);
                                $json_data = htmlspecialchars(json_encode($row));
                            ?>
                                <div onclick="showDetail('global', <?= $json_data ?>)" class="bg-white p-5 rounded-3xl border shadow-sm hover:shadow-md transition-all cursor-pointer group <?= $row['is_done'] ? 'border-slate-200 opacity-60 grayscale-[0.5]' : ($is_overdue ? 'border-rose-200 bg-rose-50/20' : 'border-slate-200') ?>">
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
                                                    <div class="flex items-center gap-1.5">
                                                        <?php if ($is_overdue): ?>
                                                            <span class="bg-rose-500 text-white text-[8px] font-extrabold px-1.5 py-0.5 rounded-md animate-pulse">TERLEWAT</span>
                                                        <?php endif; ?>
                                                        <span class="text-[10px] font-semibold text-slate-500"><?= date('d M Y', strtotime($row['deadline_tanggal'])) ?></span>
                                                    </div>
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
                    <div class="flex justify-between items-center mb-4">
                        <h3 class="text-lg font-semibold text-slate-800 flex items-center gap-2">
                            <i class="fas fa-user text-indigo-600"></i> Tugas Pribadi
                        </h3>
                        <?php if (!$show_done && $count_personal_active > 0): ?>
                            <form id="form-complete-personal" method="POST">
                                <input type="hidden" name="action" value="complete_all_personal">
                                <button type="button" onclick="confirmCompleteAll('personal')" class="text-[9px] font-bold text-rose-600 bg-rose-50 px-2.5 py-1.5 rounded-xl hover:bg-rose-100 transition-all border border-rose-100/50 flex items-center gap-1.5 ring-offset-2 focus:ring-2 focus:ring-rose-100 outline-none">
                                    <i class="fas fa-check-double"></i> Selesaikan Semua
                                </button>
                            </form>
                        <?php endif; ?>
                    </div>
                    <div class="space-y-4">
                        <?php if (mysqli_num_rows($personal_tasks) > 0): ?>
                            <?php while ($row = mysqli_fetch_assoc($personal_tasks)):
                                $row['deadline_fmt'] = $row['deadline'] ? date('d M Y, H:i', strtotime($row['deadline'])) : 'Tanpa Deadline';
                                $deadline_ts = $row['deadline'] ? strtotime($row['deadline']) : null;
                                $is_overdue = ($deadline_ts && $deadline_ts < time() && $row['status'] == 'belum');
                                $json_data = htmlspecialchars(json_encode($row));
                            ?>
                                <div onclick="showDetail('personal', <?= $json_data ?>)" class="bg-white p-4 rounded-2xl border shadow-sm hover:shadow-md transition-all cursor-pointer group <?= $row['status'] == 'selesai' ? 'border-slate-200 opacity-60 grayscale-[0.5]' : ($is_overdue ? 'border-rose-200 bg-rose-50/20' : 'border-slate-200') ?>">
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
                                                    <?php if ($is_overdue): ?>
                                                        <span class="bg-rose-500 text-white text-[8px] font-extrabold px-1.5 py-0.5 rounded-md animate-pulse">TERLEWAT</span>
                                                    <?php endif; ?>
                                                    <span class="text-[10px] font-semibold"><?= $row['deadline'] ? date('d M Y, H:i', strtotime($row['deadline'])) : '' ?></span>
                                                    <button type="button" onclick="event.stopPropagation(); showEditModal(<?= $json_data ?>)" class="hover:text-indigo-500 transition-colors">
                                                        <i class="fas fa-edit text-[10px]"></i>
                                                    </button>
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

    <!-- Modal Edit Tugas -->
    <div id="editModal" class="fixed inset-0 bg-slate-900/40 backdrop-blur-sm z-[100] hidden items-center justify-center p-4">
        <div class="bg-white rounded-[2rem] w-full max-w-sm overflow-hidden shadow-2xl animate-fade-in-up">
            <div class="bg-amber-500 p-6 text-white text-center">
                <h3 class="text-xl font-bold">Edit Tugas</h3>
                <p class="text-xs text-amber-100 mt-1 opacity-80">Perbarui rencana tugas Anda.</p>
            </div>
            <form method="POST" class="p-6 space-y-4">
                <input type="hidden" name="action" value="edit_personal">
                <input type="hidden" name="id" id="edit_id">
                <div>
                    <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1.5 ml-1">Judul Tugas</label>
                    <input type="text" name="judul" id="edit_judul" required placeholder="Contoh: Belajar PHP Dasar" class="w-full px-4 py-3 rounded-xl bg-slate-50 border border-slate-200 focus:outline-none focus:ring-2 focus:ring-amber-500/20 focus:border-amber-600 transition-all text-sm font-medium">
                </div>
                <div>
                    <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1.5 ml-1">Deadline (Opsional)</label>
                    <input type="datetime-local" name="deadline" id="edit_deadline" class="w-full px-4 py-3 rounded-xl bg-slate-50 border border-slate-200 focus:outline-none focus:ring-2 focus:ring-amber-500/20 focus:border-amber-600 transition-all text-sm font-medium">
                </div>
                <div>
                    <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1.5 ml-1">Keterangan</label>
                    <textarea name="deskripsi" id="edit_deskripsi" rows="3" placeholder="Tambahkan catatan jika perlu..." class="w-full px-4 py-3 rounded-xl bg-slate-50 border border-slate-200 focus:outline-none focus:ring-2 focus:ring-amber-500/20 focus:border-amber-600 transition-all text-sm font-medium resize-none"></textarea>
                </div>
                <div class="flex gap-3 pt-2">
                    <button type="button" onclick="closeEditModal()" class="flex-1 px-4 py-3 rounded-xl bg-slate-100 text-slate-600 text-xs font-bold uppercase tracking-widest hover:bg-slate-200 transition-colors">Batal</button>
                    <button type="submit" class="flex-1 px-4 py-3 rounded-xl bg-amber-500 text-white text-xs font-bold uppercase tracking-widest hover:bg-amber-600 shadow-lg shadow-amber-200 transition-all">Update</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Detail Task Modal -->
    <div id="detailModal" class="fixed inset-0 bg-black/50 z-[150] hidden items-center justify-center p-4 backdrop-blur-sm transition-opacity duration-300">
        <div class="bg-white rounded-2xl shadow-2xl max-w-lg w-full max-h-[90vh] flex flex-col transform scale-95 opacity-0 transition-all duration-300" id="detailModalContent">
            <!-- Modal Header -->
            <div id="detailHeader" class="p-6 border-b border-gray-100 flex justify-between items-start bg-indigo-50/50 rounded-t-2xl transition-colors duration-300">
                <div>
                    <span id="detailBadge" class="inline-block px-2 py-1 text-[10px] font-bold rounded uppercase tracking-wider mb-2"></span>
                    <h3 id="detailTitle" class="text-xl font-bold text-gray-900 leading-snug"></h3>
                    <p id="detailSubtitle" class="text-[10px] text-gray-500 font-medium mt-1"></p>
                </div>
                <button onclick="closeDetailModal()" class="text-gray-400 hover:text-gray-600 transition p-1 hover:bg-gray-100 rounded-full">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>

            <!-- Modal Body -->
            <div class="p-6 overflow-y-auto">
                <div class="mb-6">
                    <h4 class="text-xs font-bold text-gray-500 uppercase tracking-wider mb-2">Deskripsi Tugas</h4>
                    <div id="detailDesc" class="text-sm text-gray-700 whitespace-pre-line leading-relaxed"></div>
                </div>

                <div class="grid grid-cols-1 gap-4 bg-gray-50 p-4 rounded-xl border border-gray-100">
                    <div class="flex items-start">
                        <div id="deadlineIcon" class="bg-indigo-100 p-2 rounded-lg mr-3">
                            <i class="far fa-clock text-indigo-600 text-sm"></i>
                        </div>
                        <div>
                            <p class="text-[10px] font-bold text-gray-500 uppercase">Tenggat Waktu</p>
                            <p id="detailDeadline" class="text-sm font-semibold text-gray-800"></p>
                        </div>
                    </div>

                    <div id="detailStatusContainer" class="flex items-start">
                        <div id="statusIcon" class="bg-emerald-100 p-2 rounded-lg mr-3">
                            <i class="fas fa-info-circle text-emerald-600 text-sm"></i>
                        </div>
                        <div>
                            <p class="text-[10px] font-bold text-gray-500 uppercase">Status</p>
                            <p id="detailStatusText" class="text-sm font-semibold text-gray-800"></p>
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
                <button onclick="closeDetailModal()" class="px-4 py-2 bg-white border border-gray-300 text-gray-700 text-xs font-semibold rounded-lg hover:bg-gray-50 transition">
                    Tutup
                </button>
                <div id="detailActions" class="flex gap-2">
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

        function showEditModal(data) {
            document.getElementById('edit_id').value = data.id;
            document.getElementById('edit_judul').value = data.judul;
            document.getElementById('edit_deskripsi').value = data.deskripsi || '';

            if (data.deadline) {
                // Convert YYYY-MM-DD HH:MM:SS to YYYY-MM-DDTHH:MM
                const dt = data.deadline.replace(' ', 'T').substring(0, 16);
                document.getElementById('edit_deadline').value = dt;
            } else {
                document.getElementById('edit_deadline').value = '';
            }

            document.getElementById('editModal').classList.remove('hidden');
            document.getElementById('editModal').classList.add('flex');
        }

        function closeEditModal() {
            document.getElementById('editModal').classList.add('hidden');
            document.getElementById('editModal').classList.remove('flex');
        }

        function showDetail(type, data) {
            const modal = document.getElementById('detailModal');
            const modalContent = document.getElementById('detailModalContent');
            const header = document.getElementById('detailHeader');
            const badge = document.getElementById('detailBadge');
            const title = document.getElementById('detailTitle');
            const subtitle = document.getElementById('detailSubtitle');
            const desc = document.getElementById('detailDesc');
            const deadline = document.getElementById('detailDeadline');
            const deadlineIcon = document.getElementById('deadlineIcon');
            const statusIcon = document.getElementById('statusIcon');
            const statusText = document.getElementById('detailStatusText');
            const actions = document.getElementById('detailActions');
            const fileContainer = document.getElementById('modalFileContainer');

            actions.innerHTML = '';
            fileContainer.classList.add('hidden');

            if (type === 'global') {
                header.className = 'p-6 border-b border-gray-100 flex justify-between items-start bg-indigo-50/50 rounded-t-2xl transition-colors duration-300';
                badge.className = 'inline-block px-2 py-1 text-[10px] font-bold rounded uppercase tracking-wider mb-2 bg-indigo-100 text-indigo-700';
                badge.innerText = 'Tugas Kelas';
                title.innerText = data.nama_matkul;
                subtitle.innerText = 'Tugas dari Dosen / Admin';
                desc.innerText = data.deskripsi || 'Tidak ada deskripsi detail untuk tugas ini.';
                deadline.innerText = data.deadline_fmt;

                deadlineIcon.className = 'bg-indigo-100 p-2 rounded-lg mr-3';
                deadlineIcon.querySelector('i').className = 'far fa-clock text-indigo-600 text-sm';

                if (data.is_done) {
                    statusIcon.className = 'bg-emerald-100 p-2 rounded-lg mr-3';
                    statusIcon.querySelector('i').className = 'fas fa-check-circle text-emerald-600 text-sm';
                    statusText.innerText = 'Selesai';
                } else {
                    statusIcon.className = 'bg-amber-100 p-2 rounded-lg mr-3';
                    statusIcon.querySelector('i').className = 'fas fa-clock text-amber-600 text-sm';
                    statusText.innerText = 'Belum Selesai';
                }

                if (data.file_tugas) {
                    fileContainer.classList.remove('hidden');
                    fileContainer.classList.add('flex');
                    document.getElementById('modalFileLink').href = '../storage/tugas/' + data.file_tugas;
                }

                if (data.link_kirim) {
                    actions.innerHTML = `
                        <a href="${data.link_kirim}" target="_blank" class="px-5 py-2 bg-indigo-600 text-white text-xs font-semibold rounded-lg hover:bg-indigo-700 shadow-md hover:shadow-lg transition flex items-center">
                            <i class="fas fa-paper-plane mr-2"></i> Kirim Tugas
                        </a>
                    `;
                } else {
                    actions.innerHTML = `
                        <span class="px-4 py-2 bg-gray-200 text-gray-500 text-xs font-semibold rounded-lg cursor-not-allowed flex items-center">
                            <i class="fas fa-lock mr-2"></i> Tidak ada link
                        </span>
                    `;
                }
            } else {
                header.className = 'p-6 border-b border-gray-100 flex justify-between items-start bg-rose-50/50 rounded-t-2xl transition-colors duration-300';
                badge.className = 'inline-block px-2 py-1 text-[10px] font-bold rounded uppercase tracking-wider mb-2 bg-rose-100 text-rose-700';
                badge.innerText = 'Tugas Pribadi';
                title.innerText = data.judul;
                subtitle.innerText = 'Catatan Tugas Mandiri';
                desc.innerText = data.deskripsi || 'Tidak ada catatan tambahan.';
                deadline.innerText = data.deadline_fmt;

                deadlineIcon.className = 'bg-rose-100 p-2 rounded-lg mr-3';
                deadlineIcon.querySelector('i').className = 'far fa-clock text-rose-600 text-sm';

                if (data.status === 'selesai') {
                    statusIcon.className = 'bg-emerald-100 p-2 rounded-lg mr-3';
                    statusIcon.querySelector('i').className = 'fas fa-check-circle text-emerald-600 text-sm';
                    statusText.innerText = 'Selesai';
                } else {
                    statusIcon.className = 'bg-amber-100 p-2 rounded-lg mr-3';
                    statusIcon.querySelector('i').className = 'fas fa-clock text-amber-600 text-sm';
                    statusText.innerText = 'Dalam Proses';
                }
            }

            modal.classList.remove('hidden');
            modal.classList.add('flex');
            setTimeout(() => {
                modalContent.classList.remove('scale-95', 'opacity-0');
                modalContent.classList.add('scale-100', 'opacity-100');
            }, 10);
            document.body.style.overflow = 'hidden';
        }

        function closeDetailModal() {
            const modal = document.getElementById('detailModal');
            const modalContent = document.getElementById('detailModalContent');

            modalContent.classList.remove('scale-100', 'opacity-100');
            modalContent.classList.add('scale-95', 'opacity-0');

            setTimeout(() => {
                modal.classList.remove('flex');
                modal.classList.add('hidden');
                document.body.style.overflow = 'auto';
            }, 300);
        }

        function confirmCompleteAll(type) {
            const title = type === 'global' ? 'Selesaikan Semua Tugas Kelas?' : 'Selesaikan Semua Tugas Pribadi?';
            const color = type === 'global' ? '#4f46e5' : '#e11d48';

            Swal.fire({
                title: title,
                text: "Semua tugas yang aktif akan ditandai sebagai selesai!",
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: color,
                cancelButtonColor: '#f1f5f9',
                confirmButtonText: 'Ya, Selesaikan!',
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
                    document.getElementById('form-complete-' + type).submit();
                }
            });
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
        document.getElementById('addModal').addEventListener('click', function(e) {
            if (e.target === this) closeAddModal();
        });
        document.getElementById('editModal').addEventListener('click', function(e) {
            if (e.target === this) closeEditModal();
        });
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