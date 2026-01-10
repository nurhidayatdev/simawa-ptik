<?php
session_start();
include '../koneksi.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../../index.php");
    exit;
}

$nama_lengkap = $_SESSION['nama_lengkap'];
$user_id = $_SESSION['user_id'];
$foto = $_SESSION['foto'];
$role = $_SESSION['role'];

$query = "SELECT id FROM mahasiswa WHERE user_id = ?";
$stmt  = mysqli_prepare($koneksi, $query);
mysqli_stmt_bind_param($stmt, "i", $_SESSION['user_id']);
mysqli_stmt_execute($stmt);
$mahasiswa = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

$mahasiswa_id = $mahasiswa['id'];


// ambil data user
$query = "
SELECT 
    u.id AS user_id,
    u.role,
    u.password,
    m.nim,
    m.nama_lengkap,
    m.nama_panggilan,
    u.email,
    m.foto,
    m.instagram,
    m.bio
FROM users u
JOIN mahasiswa m ON m.user_id = u.id
WHERE u.id = ?
LIMIT 1
";

$stmt = mysqli_prepare($koneksi, $query);
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$data = mysqli_fetch_assoc($result);



$query_krs = "
SELECT 
    mk.kode_matkul,
    mk.nama_matkul,
    mk.sks,
    mk.semester
FROM krs k
JOIN mahasiswa m ON k.mahasiswa_id = m.id
JOIN mata_kuliah mk ON k.matkul_id = mk.id
WHERE m.user_id = ?
";


$stmt_krs = mysqli_prepare($koneksi, $query_krs);
mysqli_stmt_bind_param($stmt_krs, "i", $user_id);
mysqli_stmt_execute($stmt_krs);
$result_krs = mysqli_stmt_get_result($stmt_krs);


?>
<!DOCTYPE html>
<html lang="en">

<head>
    
    <title>Profile - PTIK C 2024</title>
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

</head>

<body class="bg-slate-100">
    <?php include '../includes/menu_db.php'; ?>

    <main class="ml-0 md:ml-48 pt-20 p-6 flex flex-col min-h-screen">
        <div class="bg-white rounded-lg border border-slate-200 p-6 flex-grow w-full">
            <div class="flex justify-between items-center mb-6">
                <h1 class="text-2xl font-bold text-slate-800">User Profile</h1>
                <a href="../actions/edit_profile.php?<?= $data['user_id']; ?>" id="edit-profile" class="px-4 py-2 bg-indigo-900 text-white rounded-lg hover:bg-indigo-800 text-xs">
                    Edit Profile
                </a>

            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <!-- Profile Picture Section -->
                <div class="md:col-span-1 flex flex-col items-center">
                    <img src="../storage/profile/<?= htmlspecialchars($data['foto']) ?>" alt="Profile Picture"
                        class="w-32 h-32 rounded-full mb-4">
                    <h2 class="text-xl font-semibold mb-2 text-slate-800"><?= htmlspecialchars($data['nama_lengkap']) ?></h2>
                    <p class="text-slate-600"><?= htmlspecialchars($data['role']) ?></p>
                </div>

                <!-- Profile Details Section -->
                <div class="md:col-span-2">
                    <!-- Replace the content with form wrapper -->
                    <form method="POST" class="space-y-4">
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-slate-700 mb-1">Email</label>
                                <input type="email" name="email" value="<?= htmlspecialchars($data['email']) ?>"
                                    class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-slate-400 text-xs"
                                    disabled>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-slate-700 mb-1">Password</label>
                                <input type="password" name="password" value="<?= htmlspecialchars($data['password']) ?>"
                                    class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-slate-400 text-xs"
                                    disabled>
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-slate-700 mb-1">Instagram</label>
                                <input type="text" name="instagram" value="<?= htmlspecialchars($data['instagram']) ?>"
                                    class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-slate-400 text-xs"
                                    disabled>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-slate-700 mb-1">Bio</label>
                                <input type="text" name="password" value="<?= htmlspecialchars($data['bio']) ?>"
                                    class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-slate-400 text-xs"
                                    disabled>
                            </div>
                        </div>

                        <!-- Bio Section -->

                        <hr class="my-6">

                        <h2 class="text-xl font-bold mb-4 text-slate-800">Kartu Rencana Studi (KRS)</h2>
                        <a href="../actions/edit_krs.php" id="edit-profile" class="px-4 py-2 bg-indigo-900 text-white rounded-lg hover:bg-indigo-800 text-xs">

                            Edit KRS

                        </a>
                        <br>
                        <br>
                        <?php if (mysqli_num_rows($result_krs) > 0): ?>
                            <div class="overflow-x-auto">
                                <table class="min-w-full divide-y divide-slate-200">
                                    <thead class="bg-indigo-900">
                                        <tr>
                                            <th class="px-6 py-3 text-left text-xs font-bold text-white uppercase tracking-wider">No</th>
                                            <th class="px-6 py-3 text-left text-xs font-bold text-white uppercase tracking-wider">Kode</th>
                                            <th class="px-6 py-3 text-left text-xs font-bold text-white uppercase tracking-wider">Mata Kuliah</th>
                                            <th class="px-6 py-3 text-left text-xs font-bold text-white uppercase tracking-wider">SKS</th>
                                        </tr>
                                    </thead>
                                    <tbody class="bg-white divide-y divide-slate-200">
                                        <?php
                                        $no = 1;
                                        $id = 1;
                                        while ($krs = mysqli_fetch_assoc($result_krs)) : ?>
                                            <tr class="<?= ($id++ % 2 == 0) ? 'bg-slate-100' : 'bg-white' ?> hover:bg-indigo-100">
                                                <td class="px-6 py-2 whitespace-nowrap text-xs text-slate-800"><?= $no++ ?></td>
                                                <td class="px-6 py-2 whitespace-nowrap text-xs text-slate-800"><?= $krs['kode_matkul'] ?></td>
                                                <td class="px-6 py-2 text-xs text-slate-800"><?= $krs['nama_matkul'] ?></td>
                                                <td class="px-6 py-2 whitespace-nowrap text-xs text-slate-800"><?= $krs['sks'] ?></td>
                                            </tr>
                                        <?php endwhile; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <p class="text-slate-500 italic">Belum ada mata kuliah yang diambil.</p>
                        <?php endif; ?>


                        <!-- Submit Button -->

                    </form>
                </div>
            </div>
        </div>
        <?php include '../includes/footer_db.php'; ?>
    </main>
    <?php include '../includes/script_db.php'; ?>
    
</body>

</html>