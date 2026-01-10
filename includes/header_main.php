<?php
// Deteksi folder dasar untuk mendukung file di berbagai subfolder
if (!isset($base)) {
    $base = "";
    if (file_exists('koneksi.php')) {
        $base = "";
    } elseif (file_exists('../koneksi.php')) {
        $base = "../";
    } elseif (file_exists('../../koneksi.php')) {
        $base = "../../";
    }
}
?>
<header class="bg-indigo-950 text-white shadow-lg fixed top-0 left-0 right-0 z-50">
    <div class="container mx-auto px-4 py-2 flex justify-between items-center">
        <!-- Logo -->
        <div class="flex items-center">
            <img src="<?= $base ?>assets/image/logo.webp" alt="Logo PTIK C 2024" class="h-10 w-10 mr-3">
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
                    <a href="<?= $base ?>index" class="flex items-center hover:text-indigo-200 transition text-sm">
                        <i class="fas fa-home mr-2"></i>Beranda
                    </a>
                </li>
                <li>
                    <a href="<?= $base ?>mahasiswa" class="flex items-center hover:text-indigo-200 transition text-sm">
                        <i class="fas fa-user-graduate mr-2"></i>Mahasiswa
                    </a>
                </li>
                <li>
                    <a href="<?= $base ?>akademik" class="flex items-center hover:text-indigo-200 transition text-sm">
                        <i class="fas fa-graduation-cap mr-2"></i>Akademik
                    </a>
                </li>
                <li>
                    <a href="<?= $base ?>galeri" class="flex items-center hover:text-indigo-200 transition text-sm">
                        <i class="fas fa-images mr-2"></i>Galeri
                    </a>
                </li>
                <li>
                    <a href="<?= $base ?>login" class="flex items-center hover:text-indigo-200 transition text-sm">
                        <i class="fas fa-sign-in-alt mr-2"></i>Login
                    </a>
                </li>
            </ul>
        </nav>
    </div>

    <nav id="mobileMenu" class="hidden md:hidden absolute top-full left-0 w-full bg-indigo-950 shadow-md z-50">
        <ul class="flex flex-col space-y-2 p-4">
            <li>
                <a href="<?= $base ?>index" class="flex items-center space-x-3 hover:text-indigo-200 transition text-sm py-2">
                    <i class="fas fa-home w-5"></i><span>Beranda</span>
                </a>
            </li>
            <li>
                <a href="<?= $base ?>mahasiswa" class="flex items-center space-x-3 hover:text-indigo-200 transition text-sm py-2">
                    <i class="fas fa-user-graduate w-5"></i><span>Mahasiswa</span>
                </a>
            </li>
            <li>
                <a href="<?= $base ?>akademik" class="flex items-center space-x-3 hover:text-indigo-200 transition text-sm py-2">
                    <i class="fas fa-graduation-cap w-5"></i><span>Akademik</span>
                </a>
            </li>
            <li>
                <a href="<?= $base ?>galeri" class="flex items-center space-x-3 hover:text-indigo-200 transition text-sm py-2">
                    <i class="fas fa-images w-5"></i><span>Galeri</span>
                </a>
            </li>
            <li>
                <a href="<?= $base ?>login" class="flex items-center space-x-3 hover:text-indigo-200 transition text-sm py-2">
                    <i class="fas fa-sign-in-alt w-5"></i><span>Login</span>
                </a>
            </li>
        </ul>
    </nav>
</header>