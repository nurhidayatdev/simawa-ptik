<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include 'koneksi.php';

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
        u.kelas_id AS user_kelas_id,
        u.nama_lengkap AS user_nama_lengkap,
        u.linked_student_nim,
        m.nim AS mhs_nim,
        m.nama_lengkap AS mhs_nama_lengkap,
        m.nama_panggilan AS mhs_nama_panggilan,
        m.kelas_id AS mhs_kelas_id,
        u.email,
        u.password,
        u.role,
        k.nama_kelas
    FROM users u
    LEFT JOIN mahasiswa m ON m.user_id = u.id
    LEFT JOIN kelas k ON (k.id = u.kelas_id OR k.id = m.kelas_id)
    WHERE u.email = ? AND u.password = ?";
    $stmt = mysqli_prepare($koneksi, $query);
    mysqli_stmt_bind_param($stmt, "ss", $email, $password);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    if ($data = mysqli_fetch_assoc($result)) {
      if (is_maintenance_mode($koneksi) && $data['role'] !== 'Administrator Utama') {
        $login_status = 'error';
        $login_message = get_maintenance_message($koneksi);
      } else {
        $_SESSION['user_id']            = $data['user_id'];
        $_SESSION['email']              = $data['email'];
        $_SESSION['nama_lengkap']       = !empty($data['user_nama_lengkap']) ? $data['user_nama_lengkap'] : (!empty($data['mhs_nama_lengkap']) ? $data['mhs_nama_lengkap'] : $data['email']);
        $_SESSION['nama_panggilan']     = !empty($data['mhs_nama_panggilan']) ? $data['mhs_nama_panggilan'] : explode(' ', $_SESSION['nama_lengkap'])[0];
        $_SESSION['role']               = $data['role'];
        $_SESSION['foto']               = '';
        $_SESSION['linked_student_nim'] = $data['linked_student_nim'] ?? null;
        
        // Simpan kelas_id & nama_kelas ke sesi
        if ($data['role'] === 'Administrator Utama') {
          $_SESSION['kelas_id']   = null;
          $_SESSION['nama_kelas'] = 'Semua Kelas';
        } else {
          $_SESSION['kelas_id']   = (int)($data['user_kelas_id'] ?? $data['mhs_kelas_id'] ?? 1);
          $_SESSION['nama_kelas'] = $data['nama_kelas'] ?? 'PTIK C 2024';
        }

        $login_status = 'success';
        $login_message = "Selamat datang, " . htmlspecialchars($_SESSION['nama_lengkap']) . "!";
      }
    } else {
      $login_status = 'error';
      $login_message = 'Email atau Password salah!';
    }
  }
}

if (isset($_SESSION['maintenance_blocked'])) {
  $login_status = 'error';
  $login_message = $_SESSION['maintenance_blocked'];
  unset($_SESSION['maintenance_blocked']);
}

?>
<!DOCTYPE html>
<html lang="en">

<head>

  <title>Login - SIMAWA PTIK 2024</title>
  <?php include 'includes/head.php'; ?>
  <style>
    .error {
      color: #ef4444;
      font-size: 0.75rem;
      margin-top: 0.25rem;
    }

    input.error {
      border-color: #ef4444;
    }

    /* SweetAlert Responsive Styles */
    .swal2-popup {
      border-radius: 1.25rem !important;
      padding: 1.5rem !important;
    }

    .swal2-title {
      font-size: 1.25rem !important;
      font-weight: 700 !important;
    }

    .swal2-html-container {
      font-size: 0.875rem !important;
      color: #64748b !important;
    }

    @media (max-width: 640px) {
      .swal2-popup {
        margin: auto !important;
        width: 90% !important;
        max-width: 400px !important;
        padding: 1rem !important;
      }

      .swal2-title {
        font-size: 1.1rem !important;
      }

      .swal2-html-container {
        font-size: 0.8rem !important;
      }
    }

    /* Handle Auto-fill background in Dark Mode */
    .dark input:-webkit-autofill,
    .dark input:-webkit-autofill:hover,
    .dark input:-webkit-autofill:focus,
    .dark input:-webkit-autofill:active {
      -webkit-box-shadow: 0 0 0 30px #020617 inset !important;
      -webkit-text-fill-color: white !important;
      transition: background-color 5000s ease-in-out 0s;
    }
  </style>
  <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery-validate/1.19.5/jquery.validate.min.js"></script>

</head>

<body class="bg-indigo-950 m-0 p-0 transition-colors duration-300">

  <!-- Main Wrapper with perfect centering -->
  <div class="min-h-screen w-full flex items-center justify-center p-4 relative overflow-hidden">

    <!-- Background patterns (Handled inside relative wrapper) -->
    <div class="absolute inset-0 z-0 opacity-20 pointer-events-none">
      <div class="absolute top-[-10%] left-[-10%] w-[40%] h-[40%] bg-indigo-500 rounded-full blur-[120px]"></div>
      <div class="absolute bottom-[-10%] right-[-10%] w-[40%] h-[40%] bg-purple-500 rounded-full blur-[120px]"></div>
    </div>

    <!-- Login Card -->
    <div class="w-full max-w-md z-10">
      <div class="bg-white/95 dark:bg-slate-900/80 backdrop-blur-md rounded-2xl shadow-2xl p-8 border border-white/20 dark:border-slate-800 transition-colors">
        <div class="text-center mb-6">
          <h2 class="text-3xl font-bold text-slate-800 dark:text-white">Login</h2>
          <p class="text-slate-500 dark:text-slate-400 text-sm mt-2">Silakan masuk dengan akun anda.</p>
        </div>

        <?php if (is_maintenance_mode($koneksi)): ?>
          <div class="mb-6 p-4 rounded-xl bg-amber-500/10 border border-amber-500/30 text-amber-600 dark:text-amber-400 text-xs flex items-start gap-3 shadow-sm">
            <i class="fas fa-screwdriver-wrench text-amber-500 text-base mt-0.5 shrink-0"></i>
            <div>
              <p class="font-bold text-amber-700 dark:text-amber-300">Mode Maintenance Aktif</p>
              <p class="text-[11px] mt-0.5 leading-relaxed text-amber-600/90 dark:text-amber-400/90">
                Hanya <strong>Administrator Utama</strong> yang diizinkan untuk login saat pemeliharaan sistem.
              </p>
            </div>
          </div>
        <?php endif; ?>

        <form method="POST" id="loginForm" novalidate>

          <div class="space-y-5">
            <div>
              <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1">Email Address</label>
              <div class="relative">
                <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-slate-400 dark:text-slate-500">
                  <i class="fas fa-envelope"></i>
                </span>
                <input type="email" name="email" id="email" required autocomplete="username"
                  class="block w-full rounded-xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-950 text-slate-900 dark:text-white py-2.5 pl-10 pr-3 focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 transition-all text-sm placeholder:text-slate-400 dark:placeholder:text-slate-600"
                  placeholder="nim@simawa.id">
              </div>
            </div>

            <div>
              <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1">Password</label>
              <div class="relative">
                <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-slate-400 dark:text-slate-500">
                  <i class="fas fa-lock"></i>
                </span>
                <input type="password" name="password" id="password" required autocomplete="current-password"
                  class="block w-full rounded-xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-950 text-slate-900 dark:text-white py-2.5 pl-10 pr-10 focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 transition-all text-sm placeholder:text-slate-400 dark:placeholder:text-slate-600"
                  placeholder="********">
                <button type="button" id="togglePassword" class="absolute inset-y-0 right-0 pr-3 flex items-center text-slate-400 dark:text-slate-500 hover:text-indigo-600 dark:hover:text-indigo-400 transition-colors">
                  <i class="fas fa-eye" id="eyeIcon"></i>
                </button>
              </div>
            </div>

            <button type="submit"
              class="w-full bg-indigo-900 dark:bg-indigo-700 text-white py-3 rounded-xl font-bold hover:bg-indigo-800 dark:hover:bg-indigo-600 transition-all shadow-lg shadow-indigo-900/20 active:scale-[0.98]">
              Masuk Sekarang
            </button>
          </div>
        </form>

        <div class="mt-8 pt-6 border-t border-slate-100 dark:border-slate-800 text-center transition-colors">
          <p class="text-[10px] text-slate-400 dark:text-slate-500 leading-relaxed uppercase tracking-widest font-semibold">
            &copy; <?php echo date("Y"); ?> SIMAWA PTIK 2024<br>Teknik Informatika dan Komputer FT UNM
          </p>
        </div>
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
            minlength: 8
          }
        },
        messages: {
          email: {
            required: "Email tidak boleh kosong",
            email: "Masukkan alamat email yang valid"
          },
          password: {
            required: "Password tidak boleh kosong",
            minlength: "Password minimal 8 karakter"
          }
        },
        errorPlacement: function(error, element) {
          error.appendTo(element.parent().parent());
        }
      });

      // PHP Status Handling
      <?php if ($login_status === 'success'): ?>
        // Trigger Save Password Prompt via Credential Management API
        if (window.PasswordCredential) {
          const cred = new PasswordCredential({
            id: "<?= addslashes($email) ?>",
            password: "<?= addslashes($password) ?>",
            name: "<?= addslashes($_SESSION['nama_lengkap'] ?? '') ?>"
          });
          navigator.credentials.store(cred);
        }

        Swal.fire({
          icon: 'success',
          title: 'Berhasil Login!',
          text: '<?= $login_message; ?>',
          timer: 1700,
          showConfirmButton: false,
          background: document.documentElement.classList.contains('dark') ? '#0f172a' : '#fff',
          color: document.documentElement.classList.contains('dark') ? '#fff' : '#64748b',
          iconColor: '#4f46e5'
        }).then(() => {
          window.location.href = "dashboard";
        });
      <?php elseif ($login_status === 'error'): ?>
        Swal.fire({
          icon: 'error',
          title: 'Gagal Login!',
          text: '<?= $login_message; ?>',
          confirmButtonColor: '#4f46e5',
          background: document.documentElement.classList.contains('dark') ? '#0f172a' : '#fff',
          color: document.documentElement.classList.contains('dark') ? '#fff' : '#64748b',
          iconColor: '#4f46e5'
        });
      <?php endif; ?>
    });
  </script>
</body>

</html>