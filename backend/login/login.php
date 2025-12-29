<?php
include '../../koneksi.php';
session_start();

$login_status = '';
$login_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $email = trim($_POST['email']);
  $password = trim($_POST['password']);

  if (empty($email) || empty($password)) {
    $login_status = 'error';
    $login_message = 'Email dan Password tidak boleh kosong!';
  } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $login_status = 'error';
    $login_message = 'Format email tidak valid!';
  } else {
    $query = "SELECT 
    u.id AS user_id,
    m.nim,
    m.nama_lengkap,
    m.nama_panggilan,
    u.email,
    m.foto,
    u.password,
    u.role
FROM users u
JOIN mahasiswa m ON m.user_id = u.id WHERE email = ? AND password = ?";
    $stmt = mysqli_prepare($koneksi, $query);
    mysqli_stmt_bind_param($stmt, "ss", $email, $password);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    if ($data = mysqli_fetch_assoc($result)) {
      $_SESSION['user_id']       = $data['user_id'];
$_SESSION['email']         = $data['email'];
$_SESSION['nama_lengkap']  = $data['nama_lengkap'];
$_SESSION['nama_panggilan']  = $data['nama_panggilan'];
$_SESSION['role']          = $data['role'];
$_SESSION['foto']          = $data['foto'];
      $login_status = 'success';
      $login_message = "Selamat datang, " . htmlspecialchars($data['nama_lengkap']) . "!";
    } else {
      $login_status = 'error';
      $login_message = 'Email atau Password salah!';
    }
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
    <title>Login - Kelas PTIK C 2024</title>
    <link href="../../frontend/tailwind/src/output.css" rel="stylesheet">
    <link href="../../frontend/tailwind/src/input.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>

<body class="bg-slate-100 min-h-screen flex items-center justify-center">
    <div class="max-w-md w-full mx-4">
        <div class="bg-white rounded-lg shadow-lg p-8">
            <div class="text-center mb-8">
                <h2 class="text-3xl font-bold">Login</h2>
                <p class="text-slate-600">Welcome back! Please login to your account.</p>
            </div>
            <form method="POST" id="loginForm" novalidate>
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-slate-700">Email</label>
                        <input type="email" name="email"
                            class="mt-1 block w-full rounded-md border border-slate-300 py-2 px-3 focus:outline-none focus:ring-1 focus:ring-slate-400">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700">Password</label>
                        <input type="password" name="password"
                            class="mt-1 block w-full rounded-md border border-slate-300 py-2 px-3 focus:outline-none focus:ring-1 focus:ring-slate-400">
                    </div>
                    <button type="submit"
                        class="w-full bg-indigo-950 text-white py-2 px-4 rounded-md hover:bg-indigo-700">Login</button>
                </div>
            </form>
            <p class="text-center mt-4 text-sm text-slate-600">
                © 2025 Kelas PTIK C - Teknik Informatika dan Komputer FT UNM. All rights reserved.
            </p>
        </div>
    </div>
    <script>
        <?php if ($login_status === 'success'): ?>
      Swal.fire({
        icon: 'success',
        title: 'Berhasil Login!',
        html: '<?= $login_message; ?><br><small>Mengarahkan ke dashboard...</small>',
        timer: 2000,
        timerProgressBar: true,
        showConfirmButton: false
      }).then(() => {
        window.location.href = "../dashboard/dashboard.php";
      });
    <?php elseif ($login_status === 'error'): ?>
      Swal.fire({
        icon: 'error',
        title: 'Gagal Login!',
        text: '<?= $login_message; ?>',
        confirmButtonText: 'Coba Lagi'
      });
    <?php endif; ?>
    </script>
</body>

</html>