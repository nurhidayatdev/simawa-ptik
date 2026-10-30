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
            <img src="<?= $base ?>assets/image/logo.webp" alt="Logo SIMAWA PTIK 2024" class="h-10 w-10 mr-3">
            <div>
                <h1 class="text-lg font-bold">SIMAWA PTIK 2024</h1>
                <p class="text-xs text-indigo-200">Portal Mahasiswa PTIK 2024</p>
            </div>
        </div>

        <!-- Actions & Menu -->
        <div class="flex items-center gap-4">
            <!-- Theme Toggle (Visible Always) -->
            <button id="theme-toggle" class="relative inline-flex items-center h-6 w-11 flex-shrink-0 cursor-pointer rounded-full border border-white/20 bg-white/10 transition-all duration-300 ease-in-out focus:outline-none">
                <span id="theme-toggle-thumb" class="pointer-events-none absolute left-0.5 h-4.5 w-4.5 transform rounded-full bg-white shadow-md transition duration-300 ease-in-out flex items-center justify-center dark:translate-x-5">
                    <i id="theme-toggle-light-icon" class="fa-solid fa-sun text-[9px] text-orange-500 transition-all duration-300 absolute"></i>
                    <i id="theme-toggle-dark-icon" class="fa-solid fa-moon text-[9px] text-indigo-900 transition-all duration-300 absolute opacity-0 scale-0"></i>
                </span>
            </button>

            <!-- Hamburger Button (Mobile) -->
            <button id="menuToggle" class="md:hidden text-2xl focus:outline-none">
                <i class="fas fa-bars"></i>
            </button>

            <!-- Desktop Nav -->
            <nav class="hidden md:block border-l border-indigo-900/50 pl-4">
                <ul class="flex space-x-6">
                    <li>
                        <a href="<?= $base ?>index" class="flex items-center hover:text-indigo-200 transition text-sm">
                            <i class="fas fa-home mr-2"></i>Beranda
                        </a>
                    </li>
                    <li>
                        <a href="<?= $base ?>akademik" class="flex items-center hover:text-indigo-200 transition text-sm">
                            <i class="fas fa-graduation-cap mr-2"></i>Akademik
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
    </div>

    <nav id="mobileMenu" class="hidden md:hidden absolute top-full left-0 w-full bg-indigo-950 shadow-md z-50">
        <ul class="flex flex-col space-y-2 p-4">
            <li>
                <a href="<?= $base ?>index" class="flex items-center space-x-3 hover:text-indigo-200 transition text-sm py-2">
                    <i class="fas fa-home w-5"></i><span>Beranda</span>
                </a>
            </li>
            <li>
                <a href="<?= $base ?>akademik" class="flex items-center space-x-3 hover:text-indigo-200 transition text-sm py-2">
                    <i class="fas fa-graduation-cap w-5"></i><span>Akademik</span>
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
