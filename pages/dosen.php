<?php
include '../koneksi.php';

// Handle Anonymous Message Form Submission
if (isset($_POST['kirim_pesan'])) {
    $pesan_raw = $_POST['pesan_anonim'];
    $pesan_db = mysqli_real_escape_string($koneksi, $pesan_raw);
    if (!empty($pesan_raw)) {
        $query_pesan = "INSERT INTO pesan (isi_pesan) VALUES ('$pesan_db')";
        if (mysqli_query($koneksi, $query_pesan)) {
            // Kirim notifikasi ke Telegram
            include_once '../backend/crud/kirim_pesan_telegram.php';
            sendTelegramPesan($pesan_raw);

            // Redirect to prevent form resubmission
            header("Location: dosen.php?status=success#footer_form");
            exit;
        }
    }
}

$result = mysqli_query($koneksi, "SELECT * FROM dosen ORDER BY nama_dosen ASC");
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#1e1b4b" />
    <title>Dosen - PTIK C 2024</title>
    <link rel="icon" href="../backend/img/main/icon.webp" />
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="../frontend/tailwind/src/output.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&display=swap" rel="stylesheet">
    <style>
        * {
            font-family: "Poppins", sans-serif;
        }
    </style>
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
</head>

<body class="bg-gray-50 min-h-screen flex flex-col">
    <!-- Header & Navigation -->
    <header class="bg-indigo-950 text-white shadow-lg fixed top-0 left-0 right-0 z-50">
        <div class="container mx-auto px-4 py-2 flex justify-between items-center">
            <!-- Logo -->
            <div class="flex items-center">
                <img src="../backend/img/main/logo.webp" alt="Logo Universitas Negeri Makassar" class="h-10 w-10 mr-3">
                <div>
                    <h1 class="text-lg font-bold">PTIK C 2024</h1>
                    <p class="text-xs">Teknik Informatika dan Komputer - FT UNM</p>
                </div>
            </div>

            <!-- Hamburger Button -->
            <button id="menuToggle" class="md:hidden text-2xl focus:outline-none">
                <i class="fas fa-bars"></i>
            </button>

            <nav class="hidden md:block">
                <ul class="flex space-x-6">
                    <li>
                        <a href="../index.php" class="flex items-center hover:text-indigo-200 transition text-sm">
                            <i class="fas fa-home mr-2"></i>Beranda
                        </a>
                    </li>
                    <li>
                        <a href="mahasiswa.php" class="flex items-center hover:text-indigo-200 transition text-sm">
                            <i class="fas fa-user-graduate mr-2"></i>Mahasiswa
                        </a>
                    </li>
                    <li>
                        <a href="akademik.php" class="flex items-center hover:text-indigo-200 transition text-sm">
                            <i class="fas fa-graduation-cap mr-2"></i>Akademik
                        </a>
                    </li>
                    <li>
                        <a href="galeri.php" class="flex items-center hover:text-indigo-200 transition text-sm">
                            <i class="fas fa-images mr-2"></i>Galeri
                        </a>
                    </li>
                    <li>
                        <a href="../backend/login/login.php" class="flex items-center hover:text-indigo-200 transition text-sm">
                            <i class="fas fa-sign-in-alt mr-2"></i>Login
                        </a>
                    </li>
                </ul>
            </nav>
        </div>

        <nav id="mobileMenu" class="hidden md:hidden absolute top-full left-0 w-full bg-indigo-950 shadow-md z-50">
            <ul class="flex flex-col space-y-2 p-4">
                <li>
                    <a href="../index.php" class="flex items-center space-x-3 hover:text-indigo-200 transition text-sm py-2">
                        <i class="fas fa-home w-5"></i><span>Beranda</span>
                    </a>
                </li>
                <li>
                    <a href="mahasiswa.php" class="flex items-center space-x-3 hover:text-indigo-200 transition text-sm py-2">
                        <i class="fas fa-user-graduate w-5"></i><span>Mahasiswa</span>
                    </a>
                </li>
                <li>
                    <a href="akademik.php" class="flex items-center space-x-3 hover:text-indigo-200 transition text-sm py-2">
                        <i class="fas fa-graduation-cap w-5"></i><span>Akademik</span>
                    </a>
                </li>
                <li>
                    <a href="galeri.php" class="flex items-center space-x-3 hover:text-indigo-200 transition text-sm py-2">
                        <i class="fas fa-images w-5"></i><span>Galeri</span>
                    </a>
                </li>
                <li>
                    <a href="../backend/login/login.php" class="flex items-center space-x-3 hover:text-indigo-200 transition text-sm py-2">
                        <i class="fas fa-sign-in-alt w-5"></i><span>Login</span>
                    </a>
                </li>
            </ul>
        </nav>
    </header>

    <!-- spacer to avoid content hidden under fixed header -->
    <div class="h-10"></div>


    <div class="container mx-auto p-6 mt-4">
        <h1 class="text-2xl font-bold mb-8 text-center text-indigo-950">Daftar Dosen JTIK</h1>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <?php
            $no = 1;
            while ($row = mysqli_fetch_assoc($result)) : ?>
                <div class="bg-white p-4 rounded-xl shadow-sm border border-slate-200 flex items-center hover:border-indigo-400 hover:shadow-md transition-all group">
                    <div class="bg-indigo-900 text-white w-10 h-10 rounded-lg flex items-center justify-center mr-4 text-sm font-bold shadow-indigo-200 shadow-lg group-hover:scale-110 transition-transform">
                        <?php echo $no++; ?>
                    </div>
                    <div class="flex flex-col">
                        <span class="text-sm font-semibold text-slate-800 group-hover:text-indigo-900 transition-colors uppercase"><?php echo htmlspecialchars($row['nama_dosen']); ?></span>
                    </div>
                </div>
            <?php endwhile; ?>
        </div>
    </div>

    <!-- Footer -->
    <footer class="bg-indigo-950 text-white py-4 mt-auto">
        <div class="container mx-auto px-4">
            <div class="grid md:grid-cols-4 gap-8">
                <div>
                    <h3 class="text-lg font-bold mb-4">PTIK C 2024</h3>
                    <p class="mb-4 text-xs">Jurusan Teknik Informatika dan Komputer</p>
                    <p class="text-xs">Fakultas Teknik</p>
                    <p class="text-xs">Universitas Negeri Makassar</p>
                </div>

                <div>
                    <h3 class="text-lg font-bold mb-4">Tautan Cepat</h3>
                    <ul class="space-y-1">
                        <li><a href="kalender.php" class="hover:text-indigo-200 transition text-xs">Kalender Akademik</a></li>
                        <li><a href="matakuliah.php" class="hover:text-indigo-200 transition text-xs">Kurikulum PTIK</a></li>
                        <li><a href="jadwal.php" class="hover:text-indigo-200 transition text-xs">Jadwal Mata Kuliah</a></li>
                        <li><a href="tugas.php" class="hover:text-indigo-200 transition text-xs">Tugas Mata Kuliah</a></li>
                    </ul>
                </div>

                <div>
                    <h3 class="text-lg font-bold mb-4">Kontak</h3>
                    <p class="mb-2 text-xs"><i class="fas fa-map-marker-alt mr-2 text-sm"></i> Jl. A.P. Pettarani, Makassar</p>
                    <p class="mb-2 text-xs"><i class="fas fa-envelope mr-2 text-sm"></i> ptikc2024@unm.ac.id</p>
                    <a href="https://www.instagram.com/ptikc_24">
                        <p class="mb-2 text-xs"><i class="fab fa-instagram mr-2 text-sm"></i></i> @ptikc_24</p>
                    </a>
                </div>
                <div>
                    <h3 class="text-lg font-bold mb-4">Kirim Pesan Anonim</h3>

                    <form action="" method="POST" class="space-y-2" id="footer_form">
                        <textarea
                            name="pesan_anonim"
                            rows="3"
                            maxlength="300"
                            class="w-full p-3 rounded-lg bg-indigo-900/50 border border-indigo-800 text-white text-xs focus:outline-none focus:ring-2 focus:ring-indigo-500 placeholder-indigo-300/50"
                            placeholder="Tulis pesan rahasia atau saran di sini..."
                            required></textarea>
                        <button
                            type="submit"
                            name="kirim_pesan"
                            class="w-full bg-indigo-600 hover:bg-indigo-500 text-white py-2 rounded-lg text-xs font-semibold transition flex items-center justify-center space-x-2">
                            <i class="fas fa-paper-plane text-xs"></i>
                            <span>Kirim Sekarang</span>
                        </button>
                    </form>

                    <?php
                    if (isset($_GET['status']) && $_GET['status'] == 'success') {
                        echo "<p class='text-[10px] text-green-400 mt-2'><i class='fas fa-check-circle mr-1'></i> Pesan berhasil dikirim secara anonim!</p>";
                    }
                    ?>
                </div>
            </div>
        </div>
        </div>

        <div class="border-t border-indigo-800 mt-4 pt-4 text-center text-xs">
            <p>© 2025 Kelas PTIK C - Teknik Informatika dan Komputer FT UNM. All rights reserved.</p>
        </div>
        </div>
    </footer>

    <script>
        const menuToggle = document.getElementById('menuToggle');
        const mobileMenu = document.getElementById('mobileMenu');
        const menuIcon = menuToggle.querySelector('i');

        menuToggle.addEventListener('click', () => {
            mobileMenu.classList.toggle('hidden');
            if (mobileMenu.classList.contains('hidden')) {
                menuIcon.classList.remove('fa-xmark');
                menuIcon.classList.add('fa-bars');
            } else {
                menuIcon.classList.remove('fa-bars');
                menuIcon.classList.add('fa-xmark');
            }
        });
    </script>
</body>

</html>