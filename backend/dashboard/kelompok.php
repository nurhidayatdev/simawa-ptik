<?php
session_start();
include '../../koneksi.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../../index.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$nama_lengkap = $_SESSION['nama_lengkap'];
$foto = $_SESSION['foto'];
$role = $_SESSION['role'];

if ($_SESSION['role'] === 'Mahasiswa' || $_SESSION['role'] === 'Bendahara' || $_SESSION['role'] === 'Sekretaris') {
    // Kita tetap butuh HTML dikit untuk menjalankan JS
    echo "<!DOCTYPE html>
    <html>
    <head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <meta name='theme-color' content='#1e1b4b' />
    <link rel='icon' href='../img/main/icon.png' />
    <title>Kelompok - PTIK C 2024</title>
    <!-- Google tag (gtag.js) -->
    <script async src=\"https://www.googletagmanager.com/gtag/js?id=G-Q0TXETXV6Y\"></script>
    <script>
        window.dataLayer = window.dataLayer || [];

        function gtag() {
            dataLayer.push(arguments);
        }
        gtag('js', new Date());

        gtag('config', 'G-Q0TXETXV6Y');
    </script>
    <link rel='icon' href='../img/main/icon.png' />
  <link rel='preconnect' href='https://fonts.googleapis.com'>
    <link rel='preconnect' href='https://fonts.gstatic.com' crossorigin>
    <link href='https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap' rel='stylesheet'>
    <style>
        body { font-family: 'Poppins', sans-serif !important; }
    </style>
        <script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script>
    </head>
    <body>
        <script>
            Swal.fire({
                icon: 'error',
                title: 'Akses Ditolak!',
                text: 'Hanya Admin yang dapat mengakses halaman ini.',
                confirmButtonColor: '#3085d6',
                confirmButtonText: 'Kembali'
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = '../../index.php';
                } else {
                    window.location.href = '../../index.php';
                }
            });
        </script>
    </body>
    </html>";
    exit();
}


/* =====================
   AMBIL PERIODE AKTIF
   ===================== */
$q_periode = mysqli_query($koneksi, "SELECT id FROM periode WHERE status = 'aktif' LIMIT 1");
$periode = mysqli_fetch_assoc($q_periode);
$periode_id = $periode['id'];

// Handle save
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'save') {
    $mata_kuliah_id = intval($_POST['mata_kuliah_id'] ?? 0);
    $groups_json = $_POST['groups_json'] ?? '[]';
    $groups = json_decode($groups_json, true);

    if ($mata_kuliah_id && is_array($groups)) {
        $dosen_name = mysqli_real_escape_string($koneksi, $_POST['dosen'] ?? '');
        $keterangan = mysqli_real_escape_string($koneksi, $_POST['keterangan'] ?? '');

        if (empty($dosen_name)) {
            $_SESSION['error'] = "Silakan pilih dosen terlebih dahulu.";
            header("Location: kelompok.php?matkul_id=$mata_kuliah_id");
            exit;
        }

        // [CHECK] Prevent duplicate groups for the same matkul, period, dosen, and keterangan
        $check = mysqli_query($koneksi, "SELECT id FROM kelompok WHERE matkul_id = $mata_kuliah_id AND periode_id = $periode_id AND dosen = '$dosen_name' AND keterangan = '$keterangan' LIMIT 1");
        if (mysqli_num_rows($check) > 0) {
            $_SESSION['error'] = "Kelompok dengan dosen dan keterangan ini sudah ada.";
            header("Location: db_kelompok.php?matkul_id=$mata_kuliah_id");
            exit;
        }

        // Get matkul name for notification
        $q_mk = mysqli_query($koneksi, "SELECT nama_matkul FROM mata_kuliah WHERE id = $mata_kuliah_id");
        $mk_data = mysqli_fetch_assoc($q_mk);
        $nama_matkul = $mk_data['nama_matkul'] ?? 'Mata Kuliah';

        // Insert new groups
        $insert = mysqli_prepare($koneksi, "INSERT INTO kelompok (matkul_id, kelompok_no, anggota, periode_id, dosen, keterangan) VALUES (?, ?, ?, ?, ?, ?)");
        $message_groups = "";

        foreach ($groups as $no => $anggotaArr) {
            $anggotaStr = implode(',', array_map(function ($s) {
                return $s['nim'];
            }, $anggotaArr));
            $kel_no = $no + 1;
            mysqli_stmt_bind_param($insert, 'iisiss', $mata_kuliah_id, $kel_no, $anggotaStr, $periode_id, $dosen_name, $keterangan);
            mysqli_stmt_execute($insert);

            // Format message for Telegram
            $namesArr = array_map(function ($s) {
                return "• " . $s['nama_lengkap'];
            }, $anggotaArr);
            $message_groups .= "👥 *Kelompok {$kel_no}*\n" . implode("\n", $namesArr) . "\n\n";
        }

        // =====================
        // TELEGRAM NOTIFICATION
        // =====================
        $telegram_token = "8212322367:AAGCGc4TG__EnsEd-20wFTI_s2adp-dp1OY";
        $telegram_chat_id = "-1003577478223";

        $message = "📢 *PEMBAGIAN KELOMPOK BARU*\n\n";
        $message .= "📘 *Mata Kuliah:* " . $nama_matkul . "\n";
        $message .= "👨‍🏫 *Dosen:* " . $dosen_name . "\n";
        if (!empty($keterangan)) {
            $message .= "📝 *Keterangan:* " . $keterangan . "\n";
        }
        $message .= "\n" . $message_groups;
        $message .= "_Silakan hubungi teman kelompok kalian untuk koordinasi!_ 🤝";
        $message .= "_Silakan hubungi teman kelompok kalian untuk koordinasi!_ 🤝";

        $tg_ip = "149.154.167.220";
        $telegram_url = "https://{$tg_ip}/bot{$telegram_token}/sendMessage";
        $telegram_data = [
            'chat_id' => $telegram_chat_id,
            'text' => $message,
            'parse_mode' => 'Markdown'
        ];

        // Method 1: CURL (DNS Bypass)
        $sent = false;
        if (function_exists('curl_init')) {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $telegram_url);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($telegram_data));
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
            curl_setopt($ch, CURLOPT_HTTPHEADER, ['Host: api.telegram.org']);
            $res = curl_exec($ch);
            $info = curl_getinfo($ch);
            curl_close($ch);
            if ($info['http_code'] == 200) $sent = true;
        }

        // Method 2: Fallback
        if (!$sent && ini_get('allow_url_fopen')) {
            $opts = [
                'http' => [
                    'method' => 'POST',
                    'header' => "Content-Type: application/x-www-form-urlencoded\r\nHost: api.telegram.org\r\n",
                    'content' => http_build_query($telegram_data),
                    'timeout' => 10
                ],
                'ssl' => ['verify_peer' => false, 'verify_peer_name' => false]
            ];
            @file_get_contents($telegram_url, false, stream_context_create($opts));
        }

        $_SESSION['success'] = "Kelompok berhasil disimpan dan notifikasi telah dikirim ke Telegram.";
        header("Location: db_kelompok.php");
        exit;
    } else {
        $_SESSION['error'] = "Data tidak valid.";
        header("Location: db_kelompok.php");
        exit;
    }
}

$matkuls = mysqli_query($koneksi, "SELECT DISTINCT jm.matkul_id AS id, mk.nama_matkul
    FROM jadwal_matkul jm
    JOIN mata_kuliah mk ON jm.matkul_id = mk.id
    WHERE jm.periode_id = $periode_id
    ORDER BY mk.semester, mk.nama_matkul");

// Determine selected matkul from GET so we can load only mahasiswa yang mengambil matkul ini (dari krs)
$selected_matkul = intval($_GET['matkul_id'] ?? 0);
$students = [];

if ($selected_matkul) {
    // Get lecturers from schedule
    $lecturers = [];
    $q_lecturers = mysqli_query($koneksi, "
        SELECT DISTINCT d.nama_dosen 
        FROM jadwal_matkul jm
        LEFT JOIN dosen d ON jm.dosen_pengampu_id = d.id
        WHERE jm.matkul_id = $selected_matkul AND jm.periode_id = $periode_id
        UNION
        SELECT DISTINCT d.nama_dosen 
        FROM jadwal_matkul jm
        LEFT JOIN dosen d ON jm.dosen_mitra_id = d.id
        WHERE jm.matkul_id = $selected_matkul AND jm.periode_id = $periode_id
    ");
    while ($rl = mysqli_fetch_assoc($q_lecturers)) {
        if ($rl['nama_dosen']) $lecturers[] = $rl['nama_dosen'];
    }

    $stmt = mysqli_prepare($koneksi, "SELECT m.nim, m.nama_lengkap FROM krs k JOIN mahasiswa m ON k.mahasiswa_id = m.id WHERE k.matkul_id = ? AND k.periode_id = ? ORDER BY m.nim");
    mysqli_stmt_bind_param($stmt, 'ii', $selected_matkul, $periode_id);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    while ($r = mysqli_fetch_assoc($res)) {
        $students[] = $r;
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
    <title>Kelompok - PTIK C 2024</title>
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
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&display=swap" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
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

        /* live-search script removed from style block; see script at document end */
    </style>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>

<body class="bg-slate-100">
    <header class="bg-indigo-950  fixed w-full top-0 z-50">
        <div class="flex justify-between items-center px-6 py-2">
            <div class="flex items-center">
                <img src="../img/main/logo.png" alt="Logo Universitas Negeri Makassar" class="h-10 w-10 mr-3">
                <span class="text-xl font-bold text-white">Dashboard PTIK C</span>
            </div>
            <!-- Mobile Menu Button dengan z-index yang sesuai -->
            <button id="mobile-menu-button" class="text-slate-100 hover:text-indigo-950 lg:hidden p-2 rounded-lg hover:bg-gray-100">
                <i class="fa fa-bars w-6 h-6"></i>
            </button>
            <div class="hidden lg:flex items-center space-x-4">
                <div class="relative">
                    <button id="profile-button" class="flex items-center space-x-2">
                        <span class="text-white font-semibold"><?php echo htmlspecialchars($nama_lengkap); ?></span>
                        <img src="../img/profile/<?php echo htmlspecialchars($foto); ?>" alt="Profile" class="w-8 h-8 rounded-full">
                    </button>
                    <div id="profile-dropdown" class="absolute right-0 mt-2 w-40 bg-white rounded-md shadow-lg hidden">
                        <a href="profile.php" class="block px-4 py-2 text-xs hover:bg-gray-100 font-semibold text-indigo-900"><i class="fa fa-user mr-2 text-indigo-900"></i>Profile</a>
                        <a href="../../index.php" class="block px-4 py-2 text-xs hover:bg-gray-100 font-semibold text-indigo-900"><i class="fa fa-sign-out mr-2 text-indigo-900"></i>Logout</a>
                    </div>
                </div>
            </div>
        </div>
    </header>

    <!-- Sidebar dengan z-index di bawah header -->
    <aside id="sidebar"
        class="fixed left-0 top-0 h-screen w-48 bg-white transform -translate-x-full lg:translate-x-0 transition-transform duration-200 ease-in-out z-40">
        <!-- Tambahan padding top agar tidak tertutup header -->
        <div class="pt-16">
            <nav class="mt-4 h-[calc(100vh-4rem)] overflow-y-auto pb-10">
                <div class="px-4 space-y-2">
                    <!-- Dashboard Menu -->
                    <a href="dashboard.php" class="flex items-center px-4 py-2 text-indigo-900  rounded-lg hover:bg-slate-100">
                        <i class="fa fa-home w-4 h-4 mr-4"></i>
                        <span class="text-xs font-medium">Dashboard</span>
                    </a>



                    <!-- Components Menu -->
                    <div class="space-y-1">
                        <div>
                            <p class="px-4 text-[10px] font-semibold text-slate-400 uppercase tracking-wider mb-2">Akademik</p>
                            <div class="space-y-1">
                                <button class="submenu-button flex items-center justify-between w-full px-4 py-1 text-slate-800 hover:bg-slate-100 rounded-lg transition-colors">
                                    <div class="flex items-center">
                                        <i class="fa fa-university w-5 h-5 mr-3 text-indigo-900"></i>
                                        <span class="text-xs font-medium">Master</span>
                                    </div>
                                    <i class="fa fa-chevron-down submenu-arrow w-3 h-3 transition-transform duration-200"></i>
                                </button>
                                <div class="submenu pl-1 space-y-1 hidden overflow-y-auto max-h-52 border-l-2 border-slate-100 ml-6">
                                    <a href="db_mahasiswa.php" class="block px-4 py-1 text-xs text-slate-600 hover:text-indigo-600 hover:bg-slate-50 rounded-lg">Mahasiswa</a>
                                    <a href="db_dosen.php" class="block px-4 py-1 text-xs text-slate-600 hover:text-indigo-600 hover:bg-slate-50 rounded-lg">Dosen</a>
                                    <a href="db_matkul.php" class="block px-4 py-1 text-xs text-slate-600 hover:text-indigo-600 hover:bg-slate-50 rounded-lg">Mata Kuliah</a>
                                    <a href="db_berita.php" class="block px-4 py-1 text-xs text-slate-600 hover:text-indigo-600 hover:bg-slate-50 rounded-lg">Berita & Info</a>
                                    <a href="db_kalender.php" class="block px-4 py-1 text-xs text-slate-600 hover:text-indigo-600 hover:bg-slate-50 rounded-lg">Kalender Akademik</a>
                                    <a href="db_galeri.php" class="block px-4 py-1 text-xs text-slate-600 hover:text-indigo-600 hover:bg-slate-50 rounded-lg">Galeri Kelas</a>
                                </div>

                                <a href="db_jadwal.php" class="flex items-center px-4 py-1 text-slate-800 hover:bg-slate-100 rounded-lg transition-colors">
                                    <i class="fa fa-list-alt w-5 h-5 mr-3 text-indigo-900"></i>
                                    <span class="text-xs font-medium">Jadwal Kuliah</span>
                                </a>

                                <a href="db_tugas.php" class="flex items-center px-4 py-1 text-slate-800 hover:bg-slate-100 rounded-lg transition-colors">
                                    <i class="fa fa-tasks w-5 h-5 mr-3 text-indigo-900"></i>
                                    <span class="text-xs font-medium">Tugas Kuliah</span>
                                </a>
                                <a href="db_kelompok.php" class="flex items-center px-4 py-1 text-slate-800 hover:bg-slate-100 rounded-lg transition-colors">
                                    <i class="fa fa-users w-5 h-5 mr-3 text-indigo-900"></i>
                                    <span class="text-xs font-medium">Kelompok</span>
                                </a>
                                <a href="kalender_view.php" class="flex items-center px-4 py-1 text-slate-800 hover:bg-slate-100 rounded-lg transition-colors">
                                    <i class="fa fa-calendar w-5 h-5 mr-3 text-indigo-900"></i>
                                    <span class="text-xs font-medium">Kalender</span>
                                </a>

                            </div>
                        </div>

                        <div>
                            <p class="px-4 text-[10px] font-semibold text-slate-400 uppercase tracking-wider mb-2">Administrasi</p>
                            <div class="space-y-1">
                                <button class="submenu-button flex items-center justify-between w-full px-4 py-1 text-slate-800 hover:bg-slate-100 rounded-lg transition-colors">
                                    <div class="flex items-center">
                                        <i class="fa fa-clipboard-list w-5 h-5 mr-3 text-indigo-900"></i>
                                        <span class="text-xs font-medium">Absensi</span>
                                    </div>
                                    <i class="fa fa-chevron-down submenu-arrow w-3 h-3 transition-transform duration-200"></i>
                                </button>
                                <div class="submenu pl-1 space-y-1 hidden overflow-y-auto max-h-52 border-l-2 border-slate-100 ml-6">
                                    <a href="db_absensi.php" class="block px-4 py-1 text-xs text-slate-600 hover:text-indigo-600 hover:bg-slate-50 rounded-lg">Data Absen</a>
                                    <?php if ($role === 'Administrator Utama' || $role === 'Sekretaris'): ?>
                                        <a href="absensi.php" class="block px-4 py-1 text-xs text-slate-600 hover:text-indigo-600 hover:bg-slate-50 rounded-lg">Input Absen</a>
                                    <?php endif; ?>
                                </div>

                                <button class="submenu-button flex items-center justify-between w-full px-4 py-1 text-slate-800 hover:bg-slate-100 rounded-lg transition-colors">
                                    <div class="flex items-center">
                                        <i class="fa fa-wallet w-5 h-5 mr-3 text-indigo-900"></i>
                                        <span class="text-xs font-medium">Keuangan</span>
                                    </div>
                                    <i class="fa fa-chevron-down submenu-arrow w-3 h-3 transition-transform duration-200"></i>
                                </button>
                                <div class="submenu pl-1 space-y-1 hidden overflow-y-auto max-h-52 border-l-2 border-slate-100 ml-6">
                                    <a href="db_kas.php" class="block px-4 py-1 text-xs text-slate-600 hover:text-indigo-600 hover:bg-slate-50 rounded-lg">Data Kas</a>
                                    <?php if ($role === 'Administrator Utama' || $role === 'Bendahara'): ?>
                                        <a href="arus_kas.php" class="block px-4 py-1 text-xs text-slate-600 hover:text-indigo-600 hover:bg-slate-50 rounded-lg">Arus Kas</a>
                                        <a href="kas.php" class="block px-4 py-1 text-xs text-slate-600 hover:text-indigo-600 hover:bg-slate-50 rounded-lg">Input Per Minggu</a>
                                        <a href="kas_individu.php" class="block px-4 py-1 text-xs text-slate-600 hover:text-indigo-600 hover:bg-slate-50 rounded-lg">Input Per Individu</a>
                                    <?php endif; ?>
                                </div>


                            </div>
                        </div>

                        <div>
                            <p class="px-4 text-[10px] font-semibold text-slate-400 uppercase tracking-wider mb-2">Aktivitas</p>
                            <div class="space-y-1">




                                <a href="misi.php" class="flex items-center px-4 py-1 text-slate-800 hover:bg-slate-100 rounded-lg transition-colors">
                                    <i class="fa fa-trophy w-5 h-5 mr-3 text-indigo-900"></i>
                                    <span class="text-xs font-medium">Pusat Misi</span>
                                </a>
                                <a href="kuis_harian.php" class="flex items-center px-4 py-1 text-slate-800 hover:bg-slate-100 rounded-lg transition-colors">
                                    <i class="fa fa-gamepad w-5 h-5 mr-3 text-indigo-900"></i>
                                    <span class="text-xs font-medium">Kuis Harian</span>
                                </a>
                                <a href="db_pesan.php" class="flex items-center px-4 py-1 text-slate-800 hover:bg-slate-100 rounded-lg transition-colors">
                                    <i class="fa fa-comments w-5 h-5 mr-3 text-indigo-900"></i>
                                    <span class="text-xs font-medium">Pesan Anonim</span>
                                </a>

                            </div>
                        </div>


                        <div>
                            <p class="px-4 text-[10px] font-semibold text-slate-400 uppercase tracking-wider mb-2">Pengaturan</p>
                            <div class="space-y-1">
                                <button class="submenu-button flex items-center justify-between w-full px-4 py-1 text-slate-800 hover:bg-slate-100 rounded-lg transition-colors">
                                    <div class="flex items-center">
                                        <i class="fa fa-user w-5 h-5 mr-3 text-indigo-900"></i>
                                        <span class="text-xs font-medium">Akun</span>
                                    </div>
                                    <i class="fa fa-chevron-down submenu-arrow w-3 h-3 transition-transform duration-200"></i>
                                </button>
                                <div class="submenu pl-1 space-y-1 hidden overflow-y-auto max-h-52 border-l-2 border-slate-100 ml-6">
                                    <a href="profile.php" class="block px-4 py-1 text-xs text-slate-600 hover:text-indigo-600 hover:bg-slate-50 rounded-lg">Profile Saya</a>
                                    <a href="krs.php" class="block px-4 py-1 text-xs text-slate-600 hover:text-indigo-600 hover:bg-slate-50 rounded-lg">KRS</a>

                                </div>
                                <?php if ($role === 'Administrator Utama'): ?>
                                    <button class="submenu-button flex items-center justify-between w-full px-4 py-1 text-slate-800 hover:bg-slate-100 rounded-lg transition-colors">
                                        <div class="flex items-center">
                                            <i class="fa-solid fa-earth-americas w-5 h-5 mr-3 text-indigo-900"></i>
                                            <span class="text-xs font-medium">Website</span>
                                        </div>
                                        <i class="fa fa-chevron-down submenu-arrow w-3 h-3 transition-transform duration-200"></i>
                                    </button>
                                    <div class="submenu pl-1 space-y-1 hidden overflow-y-auto max-h-52 border-l-2 border-slate-100 ml-6">
                                        <a href="db_users.php" class="block px-4 py-1 text-xs text-slate-600 hover:text-indigo-600 hover:bg-slate-50 rounded-lg">Users</a>
                                        <a href="db_periode.php" class="block px-4 py-1 text-xs text-slate-600 hover:text-indigo-600 hover:bg-slate-50 rounded-lg">Periode</a>
                                        <a href="reminder.php" class="block px-4 py-1 text-xs text-slate-600 hover:text-indigo-600 hover:bg-slate-50 rounded-lg">Reminder</a>
                                    </div>
                                <?php endif; ?>


                                <a href="tentang.php" class="flex items-center px-4 py-1 text-slate-800 hover:bg-slate-100 rounded-lg transition-colors">
                                    <i class="fa fa-info-circle w-5 h-5 mr-3 text-indigo-900"></i>
                                    <span class="text-xs font-medium">Tentang Aplikasi</span>
                                </a>

                                <a href="../../index.php" class="flex items-center px-4 py-1 text-slate-800 hover:bg-red-50 hover:text-red-700 rounded-lg transition-colors">
                                    <i class="fa fa-sign-out-alt w-5 h-5 mr-3 text-indigo-900"></i>
                                    <span class="text-xs font-medium">Logout</span>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </nav>
        </div>
    </aside>

    <main class="ml-0 lg:ml-48 pt-20 p-6 flex flex-col min-h-screen">
        <div class="bg-white rounded-lg border border-slate-200 p-6 flex-grow w-full">
            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-2">
                <h3 class="text-xl font-bold text-slate-800 mb-4">Pengaturan Kelompok (Baru)</h3>
                <div class="flex flex-col sm:flex-row gap-2 w-full sm:w-auto">
                    <form method="GET" class="flex flex-col sm:flex-row gap-4 w-full sm:w-auto">




                        <a href="db_kelompok.php" class="bg-indigo-600 text-white px-4 py-2 rounded-lg hover:bg-indigo-800 text-center text-xs">

                            Kembali

                        </a>
                    </form>
                </div>
            </div>
            <div id="statusAlert" class="mb-4 hidden">
                <?php if (isset($existing_data) && $existing_data): ?>
                    <div class="p-3 bg-red-100 text-red-700 text-xs rounded-lg border border-red-200">
                        <i class="fa fa-exclamation-triangle mr-2"></i> PERHATIAN: Kelompok untuk mata kuliah ini sudah dibuat. Anda tidak bisa menambahkannya lagi.
                    </div>
                <?php endif; ?>
            </div>

            <div class="flex flex-wrap items-end gap-4 mb-4">

                <form id="selectForm" method="GET" class="flex-1 min-w-[200px]">
                    <label class="text-xs block mb-1">Pilih Mata Kuliah</label>
                    <select id="mata_kuliah" name="matkul_id" class="w-full px-3 py-2 border rounded text-xs">
                        <option value="">-- Pilih --</option>
                        <?php while ($mk = mysqli_fetch_assoc($matkuls)) : ?>
                            <option value="<?= $mk['id'] ?>" <?= ($mk['id'] == $selected_matkul) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($mk['nama_matkul']) ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </form>

                <form id="kelompokForm" method="POST" class="flex flex-wrap items-end gap-2">
                    <input type="hidden" name="action" value="save">
                    <input type="hidden" name="mata_kuliah_id" id="mata_kuliah_hidden" value="<?= $selected_matkul ?>">
                    <input type="hidden" name="groups_json" id="groups_json" value="[]">

                    <div>
                        <label class="text-xs block mb-1">Dosen</label>
                        <select name="dosen" id="dosen" class="w-full px-3 py-2 border rounded text-xs" required>
                            <option value="">-- Pilih Dosen --</option>
                            <?php foreach ($lecturers as $l): ?>
                                <option value="<?= htmlspecialchars($l) ?>"><?= htmlspecialchars($l) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div>
                        <label class="text-xs block mb-1">Keterangan (Opsional)</label>
                        <input name="keterangan" id="keterangan" type="text" placeholder="Contoh: Tugas 1" class="w-full px-3 py-2 border rounded text-xs" />
                    </div>

                    <div>
                        <label class="text-xs block mb-1">Orang per Kelompok</label>
                        <input id="per_group" type="number" min="1" max="17" value="3" class="w-full px-3 py-2 border rounded text-xs" />
                    </div>

                    <button id="acakBtn" type="button" class="bg-yellow-500 hover:bg-yellow-700 text-white px-4 py-2 rounded text-xs h-[34px]">Acak</button>

                    <button id="manualBtn" type="button" class="bg-blue-500 hover:bg-blue-700 text-white px-4 py-2 rounded text-xs h-[34px]">Manual</button>

                    <button id="simpanBtn" type="submit" class="bg-indigo-800 hover:bg-indigo-950 text-white px-4 py-2 rounded text-xs h-[34px]">Simpan</button>
                </form>
            </div>

            <div id="groupsContainer" class="flex flex-wrap gap-4 justify-between mt-4"></div>
        </div>
        <footer class="text-xs text-indigo-900 text-center mb-0 pb-0 mt-6">
            © 2025 Kelas PTIK C - Teknik Informatika dan Komputer FT UNM. All rights reserved.
        </footer>
    </main>

    <script>
        const mobileMenuButton = document.getElementById('mobile-menu-button');
        const sidebar = document.getElementById('sidebar');

        mobileMenuButton.addEventListener('click', (e) => {
            e.stopPropagation(); // Prevent event bubbling
            sidebar.classList.toggle('-translate-x-full');
            sidebar.classList.toggle('mobile-menu-open');
        });

        // Close sidebar when clicking outside
        document.addEventListener('click', (e) => {
            if (window.innerWidth < 768) { // Only on mobile
                if (!sidebar.contains(e.target) && !mobileMenuButton.contains(e.target)) {
                    sidebar.classList.add('-translate-x-full');
                    sidebar.classList.remove('mobile-menu-open');
                }
            }
        });

        // Submenu toggles
        const submenuButtons = document.querySelectorAll('.submenu-button');

        submenuButtons.forEach(button => {
            button.addEventListener('click', () => {
                const submenu = button.nextElementSibling;
                const arrow = button.querySelector('.submenu-arrow');

                submenu.classList.toggle('hidden');
                if (arrow) arrow.classList.toggle('rotate-180');
            });
        });

        // Profile dropdown
        const profileButton = document.getElementById('profile-button');
        const profileDropdown = document.getElementById('profile-dropdown');

        if (profileButton && profileDropdown) {
            profileButton.addEventListener('click', (e) => {
                e.stopPropagation();
                profileDropdown.classList.toggle('hidden');
            });

            document.addEventListener('click', (e) => {
                if (!profileButton.contains(e.target)) {
                    profileDropdown.classList.add('hidden');
                }
            });
        }
        // Students data from PHP (only those who took selected matkul via KRS)
        const students = <?= json_encode($students, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) ?>;

        // Removed existingGroups loading for Add Only page

        function shuffleArray(array) {
            for (let i = array.length - 1; i > 0; i--) {
                const j = Math.floor(Math.random() * (i + 1));
                [array[i], array[j]] = [array[j], array[i]];
            }
        }

        function makeGroups(perGroup) {
            const copy = students.slice();
            shuffleArray(copy);
            const groups = [];
            for (let i = 0; i < copy.length; i += perGroup) {
                groups.push(copy.slice(i, i + perGroup));
            }
            return groups;
        }

        function renderGroups(groups) {
            currentGroups = groups;
            const container = document.getElementById('groupsContainer');
            container.innerHTML = '';
            groups.forEach((g, idx) => {
                const div = document.createElement('div');
                div.className = 'p-4 border rounded bg-slate-50 w-56';
                const title = document.createElement('h4');
                title.className = 'font-semibold mb-2';
                title.textContent = 'Kelompok ' + (idx + 1);
                div.appendChild(title);
                const ul = document.createElement('ul');
                ul.className = 'list-disc pl-5 text-sm';
                g.forEach(s => {
                    const li = document.createElement('li');
                    li.textContent = s.nama_lengkap;
                    ul.appendChild(li);
                });
                div.appendChild(ul);
                container.appendChild(div);
            });
            document.getElementById('groups_json').value = JSON.stringify(groups);
        }

        // Render editable groups for manual creation
        let currentGroups = [];

        function renderEditable(groups) {
            currentGroups = groups;
            const container = document.getElementById('groupsContainer');
            container.innerHTML = '';

            function getAvailableStudents() {
                const used = {};
                currentGroups.forEach(g => g.forEach(m => {
                    if (m && m.nim) used[m.nim] = true;
                }));
                return students.filter(s => !used[s.nim]);
            }

            groups.forEach((g, idx) => {
                const div = document.createElement('div');
                div.className = 'p-4 border rounded bg-slate-50 w-56 flex flex-col';
                const title = document.createElement('h4');
                title.className = 'font-semibold mb-2';
                title.textContent = 'Kelompok ' + (idx + 1);
                div.appendChild(title);

                const ul = document.createElement('ul');
                ul.className = 'list-disc pl-5 text-sm mb-2';
                (g || []).forEach((s, si) => {
                    const li = document.createElement('li');
                    li.className = 'flex justify-between items-center';
                    const span = document.createElement('span');
                    span.textContent = s.nama_lengkap || s;
                    const btn = document.createElement('button');
                    btn.type = 'button';
                    btn.className = 'text-red-600 ml-2 text-xs';
                    btn.textContent = 'Hapus';
                    btn.addEventListener('click', () => {
                        currentGroups[idx].splice(si, 1);
                        renderEditable(currentGroups);
                    });
                    li.appendChild(span);
                    li.appendChild(btn);
                    ul.appendChild(li);
                });
                div.appendChild(ul);

                // Add from available students
                const avail = getAvailableStudents();
                const select = document.createElement('select');
                select.className = 'w-full px-2 py-1 border rounded text-sm mb-1';
                const opt0 = document.createElement('option');
                opt0.value = '';
                opt0.textContent = 'Tambah dari daftar...';
                select.appendChild(opt0);
                avail.forEach(s => {
                    const o = document.createElement('option');
                    o.value = s.nim;
                    o.textContent = s.nama_lengkap;
                    select.appendChild(o);
                });
                const addBtn = document.createElement('button');
                addBtn.type = 'button';
                addBtn.className = 'bg-green-600 text-white px-2 py-1 rounded text-sm';
                addBtn.textContent = 'Tambah';
                addBtn.addEventListener('click', () => {
                    const val = select.value;
                    if (!val) return;
                    const stu = students.find(x => x.nim === val);
                    if (stu) {
                        currentGroups[idx].push(stu);
                        // reset select so user sees it's added
                        select.value = '';
                        renderEditable(currentGroups);
                    }
                });
                div.appendChild(select);
                div.appendChild(addBtn);

                container.appendChild(div);
            });
            document.getElementById('groups_json').value = JSON.stringify(currentGroups);
        }

        document.getElementById('acakBtn').addEventListener('click', () => {
            const per = parseInt(document.getElementById('per_group').value) || 1;
            const groups = makeGroups(per);
            currentGroups = groups;
            renderGroups(groups);
        });

        document.getElementById('manualBtn').addEventListener('click', () => {
            const per = parseInt(document.getElementById('per_group').value) || 1;
            const n = Math.max(1, Math.ceil((students.length || 0) / per));
            const groups = [];
            for (let i = 0; i < n; i++) groups.push([]);
            renderEditable(groups);
        });

        // Auto-submit select to reload students list
        document.getElementById('mata_kuliah').addEventListener('change', function() {
            document.getElementById('selectForm').submit();
        });

        // Ensure hidden matkul id updates before save
        document.getElementById('kelompokForm').addEventListener('submit', function(e) {
            const selectVal = document.getElementById('mata_kuliah').value || document.getElementById('mata_kuliah_hidden').value;
            document.getElementById('mata_kuliah_hidden').value = selectVal;

            // PHP level check also exists, but let's be nice to the user
            <?php if (isset($existing_data) && $existing_data): ?>
                e.preventDefault();
                Swal.fire({
                    icon: 'error',
                    title: 'Sudah Ada!',
                    text: 'Kelompok untuk mata kuliah ini sudah ada. Silakan gunakan menu Edit.'
                });
                return false;
            <?php endif; ?>
        });

        // When form submits, ensure a matkul is selected and groups_json present
        document.getElementById('kelompokForm').addEventListener('submit', function(e) {
            const matkul = document.getElementById('mata_kuliah').value;
            if (!matkul) {
                e.preventDefault();
                alert('Pilih mata kuliah terlebih dahulu.');
                return false;
            }
            const groupsVal = document.getElementById('groups_json').value || '[]';
            const groups = JSON.parse(groupsVal);
            if (!Array.isArray(groups) || groups.length === 0) {
                e.preventDefault();
                alert('Belum ada pembagian kelompok. Tekan tombol "Acak" terlebih dahulu.');
                return false;
            }
            // Append selected mata kuliah id into form as name expected by server
            // The select already has name="mata_kuliah_id"
            return true;
        });

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
                text: '<?= $_SESSION['error'] ?>',
                timer: 3000,
                showConfirmButton: false
            });
            <?php unset($_SESSION['error']); ?>
        <?php endif; ?>
    </script>

</body>

</html>