<?php
session_start();
include '../koneksi.php';

$nama_panggilan = $_SESSION['nama_panggilan'];
$nama_lengkap = $_SESSION['nama_lengkap'];
$user_id = $_SESSION['user_id'];
$foto = $_SESSION['foto'];
$role = $_SESSION['role'];

// Include foto helpers for cache busting
include_once '../functions/foto_helpers.php';
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



if (isset($_POST['update'])) {
    mysqli_query($koneksi, "UPDATE mahasiswa SET 
        nama_lengkap='{$_POST['nama_lengkap']}',
        nama_panggilan='{$_POST['nama_panggilan']}',
        bio='{$_POST['bio']}',
        instagram='{$_POST['instagram']}'
        WHERE user_id='$user_id'
    ");

    // Server-side password validation
    $password = $_POST['password'];
    if (!preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)[a-zA-Z\d]{8,}$/', $password)) {
        $error_message = "Password must be at least 8 characters, contain uppercase, lowercase, numbers, and no symbols.";
        include '../includes/alert_actions.php';
        exit;
    }

    mysqli_query($koneksi, "UPDATE users SET 
        email='{$_POST['email']}',
        password='{$password}'
        WHERE id='$user_id'
    ");

    $redirect = "../views/profile.php";







    include '../includes/alert_actions.php';
    exit;
}

?>
<!DOCTYPE html>
<html lang="en">

<head>

    <title>Edit Profile - SIMAWA PTIK 2024</title>
    <?php include '../includes/head.php'; ?>
    <style>
        .rotate-180 {
            transform: rotate(180deg);
        }

        @media (max-width: 768px) {
            .mobile-menu-open {
                transform: translateX(0) !important;
            }
        }
    </style>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.5.13/cropper.min.css" rel="stylesheet">
</head>

<body class="bg-slate-100 dark:bg-slate-950 transition-colors">
    <?php include '../includes/menu_db.php'; ?>

    <main class="ml-0 md:ml-48 pt-20 p-6 flex flex-col min-h-screen">
        <div class="bg-white dark:bg-slate-900/50 rounded-xl border border-gray-200 dark:border-slate-800 p-6 flex-grow w-full shadow-sm">

            <form class="space-y-6" method="POST" enctype="multipart/form-data">
                <div class="border-b border-gray-200 dark:border-slate-800 pb-6">



                    <h3 class="text-2xl font-bold mb-6 text-slate-800 dark:text-slate-100">Edit Profile</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label class="block text-xs font-medium text-slate-700 dark:text-slate-300 mb-1">Nama Lengkap</label>
                            <input type="text" name="nama_lengkap" value="<?= htmlspecialchars($data['nama_lengkap'] ?? ''); ?>"
                                class="w-full px-4 py-2 border border-gray-300 dark:border-slate-700 rounded-lg focus:outline-none bg-slate-50 dark:bg-slate-900/50 text-slate-500 dark:text-slate-400 text-xs cursor-not-allowed transition-colors" readonly>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-slate-700 dark:text-slate-300 mb-1">Email</label>
                            <input type="text" name="email" value="<?= htmlspecialchars($data['email'] ?? ''); ?>"
                                class="w-full px-4 py-2 border border-gray-300 dark:border-slate-700 rounded-lg focus:outline-none bg-slate-50 dark:bg-slate-900/50 text-slate-500 dark:text-slate-400 text-xs cursor-not-allowed transition-colors" readonly>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-slate-700 dark:text-slate-300 mb-1">Password</label>
                            <input type="text" name="password" value="<?= htmlspecialchars($data['password']); ?>"
                                class="w-full px-4 py-2 border border-gray-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-slate-200 rounded-lg focus:outline-none focus:ring-1 focus:ring-indigo-500 dark:focus:ring-indigo-600 text-xs transition-colors"
                                minlength="8"
                                pattern="^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)[a-zA-Z\d]{8,}$"
                                title="Password must be at least 8 characters long, contain at least one uppercase letter, one lowercase letter, one number, and NO symbols."
                                required>
                            <p class="mt-1 text-[10px] text-slate-500 dark:text-slate-500 italic">* Minimal 8 karakter, harus ada huruf besar, kecil, angka, dan tanpa simbol.</p>
                        </div>
                         <div>
                            <label class="block text-xs font-medium text-slate-700 dark:text-slate-300 mb-1">Instagram</label>
                            <input name="instagram" value="<?= htmlspecialchars($data['instagram'] ?? ''); ?>" placeholder="@Username"
                                class="w-full px-4 py-2 border border-gray-300 dark:border-slate-700 rounded-lg focus:outline-none bg-slate-50 dark:bg-slate-900/50 text-slate-500 dark:text-slate-400 text-xs cursor-not-allowed transition-colors" readonly></input>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-slate-700 dark:text-slate-300 mb-1">Bio</label>
                            <input name="bio" value="<?= htmlspecialchars($data['bio'] ?? ''); ?>" maxlength="35"
                                class="w-full px-4 py-2 border border-gray-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-slate-200 rounded-lg focus:outline-none focus:ring-1 focus:ring-indigo-500 dark:focus:ring-indigo-600 text-xs transition-colors"></input>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-slate-700 dark:text-slate-300 mb-1">Avatar Akun</label>
                            <div class="flex items-center space-x-4 mt-2">
                                <img src="<?= profilePhotoUrl($data['nama_lengkap'] ?? $nama_lengkap, '../') ?>" class="rounded-2xl border border-gray-200 dark:border-slate-700 shadow-md" style="width:96px; height:96px;" />
                                <div class="text-xs text-slate-500 dark:text-slate-400">
                                    <p class="font-semibold text-slate-700 dark:text-slate-200">Avatar Inisial Otomatis</p>
                                    <p class="text-[11px] mt-0.5">Avatar akun dibuat otomatis oleh sistem berdasarkan inisial nama Anda.</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                            </div>
                        </div>
                    </div>





                </div>

                <div class="flex justify-end pt-4">
                    <button type="submit" name="update"
                        class="px-8 py-2.5 bg-indigo-800 dark:bg-indigo-700 text-white rounded-lg hover:bg-indigo-700 dark:hover:bg-indigo-600 text-xs font-bold shadow-lg shadow-indigo-900/10 transition-all active:scale-95">Submit Perubahan</button>
                </div>
            </form>
        </div>
        <?php include '../includes/footer_db.php'; ?>
    </main>
    <?php include '../includes/script_db.php'; ?>
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    
    
</body>

</html>