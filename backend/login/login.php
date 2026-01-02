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
  <!-- Google tag (gtag.js) -->
  <script async src="https://www.googletagmanager.com/gtag/js?id=G-Q0TXETXV6Y"></script>
  <script>
    window.dataLayer = window.dataLayer || [];

    function gtag() {
      dataLayer.push(arguments);
    }
    gtag('js', new Date());

    gtag('config', 'G-Q0TXETXV6Y');
  </script>
  <link href="../../frontend/tailwind/src/output.css" rel="stylesheet">
  <link href="../../frontend/tailwind/src/input.css" rel="stylesheet">
  <!-- Font Awesome for eye icon -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
  <!-- jQuery & SweetAlert2 -->
  <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery-validate/1.19.5/jquery.validate.min.js"></script>
  <style>
    .error {
      color: #ef4444;
      font-size: 0.75rem;
      margin-top: 0.25rem;
    }

    input.error {
      border-color: #ef4444;
    }
  </style>
</head>

<body class="bg-indigo-950 min-h-screen flex items-center justify-center relative overflow-hidden">
  <!-- Background patterns (Optional for premium feel) -->
  <div class="absolute inset-0 z-0 opacity-20 pointer-events-none">
    <div class="absolute top-[-10%] left-[-10%] w-[40%] h-[40%] bg-indigo-500 rounded-full blur-[120px]"></div>
    <div class="absolute bottom-[-10%] right-[-10%] w-[40%] h-[40%] bg-purple-500 rounded-full blur-[120px]"></div>
  </div>

  <div class="max-w-md w-full mx-4 z-10">
    <div class="bg-white/95 backdrop-blur-sm rounded-2xl shadow-2xl p-8 border border-white/20">
      <div class="text-center mb-8">

        <h2 class="text-3xl font-bold text-slate-800">Login</h2>
        <p class="text-slate-500 text-sm mt-2">Silakan masuk dengan akun anda.</p>
      </div>

      <form method="POST" id="loginForm" novalidate>
        <div class="space-y-5">
          <div>
            <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">Email Address</label>
            <div class="relative">
              <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-slate-400">
                <i class="fas fa-envelope"></i>
              </span>
              <input type="email" name="email" id="email" required
                class="block w-full rounded-xl border border-slate-200 py-2.5 pl-10 pr-3 focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 transition-all text-sm"
                placeholder="nama@example.com">
            </div>
          </div>

          <div>
            <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">Password</label>
            <div class="relative">
              <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-slate-400">
                <i class="fas fa-lock"></i>
              </span>
              <input type="password" name="password" id="password" required
                class="block w-full rounded-xl border border-slate-200 py-2.5 pl-10 pr-10 focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 transition-all text-sm"
                placeholder="••••••••">
              <button type="button" id="togglePassword" class="absolute inset-y-0 right-0 pr-3 flex items-center text-slate-400 hover:text-indigo-600 transition-colors">
                <i class="fas fa-eye" id="eyeIcon"></i>
              </button>
            </div>
          </div>

          <button type="submit"
            class="w-full bg-indigo-900 text-white py-3 rounded-xl font-bold hover:bg-indigo-800 transition-all shadow-lg shadow-indigo-900/20 active:scale-[0.98]">
            Masuk Sekarang
          </button>
        </div>
      </form>

      <div class="mt-8 pt-6 border-t border-slate-100 text-center">
        <p class="text-[10px] text-slate-400 leading-relaxed uppercase tracking-widest font-semibold">
          &copy; 2025 Kelas PTIK C<br>Teknik Informatika dan Komputer FT UNM
        </p>
      </div>
    </div>
  </div>

  <script>
    $(document).ready(function() {
      // Password Toggle Logic
      $('#togglePassword').on('click', function() {
        const passwordField = $('#password');
        const eyeIcon = $('#eyeIcon');

        if (passwordField.attr('type') === 'password') {
          passwordField.attr('type', 'text');
          eyeIcon.removeClass('fa-eye').addClass('fa-eye-slash');
        } else {
          passwordField.attr('type', 'password');
          eyeIcon.removeClass('fa-eye-slash').addClass('fa-eye');
        }
      });

      // jQuery Validation
      $("#loginForm").validate({
        rules: {
          email: {
            required: true,
            email: true
          },
          password: {
            required: true,
            minlength: 3
          }
        },
        messages: {
          email: {
            required: "Email tidak boleh kosong",
            email: "Masukkan alamat email yang valid"
          },
          password: {
            required: "Password tidak boleh kosong",
            minlength: "Password minimal 3 karakter"
          }
        },
        errorPlacement: function(error, element) {
          error.appendTo(element.parent().parent());
        }
      });

      // PHP Status Handling
      <?php if ($login_status === 'success'): ?>
        Swal.fire({
          icon: 'success',
          title: 'Berhasil Login!',
          text: '<?= $login_message; ?>',
          timer: 1700,
          showConfirmButton: false,
          background: '#fff',
          iconColor: '#4f46e5'
        }).then(() => {
          window.location.href = "../dashboard/dashboard.php";
        });
      <?php elseif ($login_status === 'error'): ?>
        Swal.fire({
          icon: 'error',
          title: 'Gagal Login!',
          text: '<?= $login_message; ?>',
          confirmButtonColor: '#4f46e5',
          iconColor: '#4f46e5'
        });
      <?php endif; ?>
    });
  </script>
</body>

</html>