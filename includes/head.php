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
<meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Website Resmi Kelas PTIK C 2024 - Jurusan Teknik Informatika dan Komputer, Universitas Negeri Makassar.">
    <meta name="theme-color" content="#1e1b4b" />
<link rel="icon" href="<?= $base ?>assets/image/icon.webp" />
<script>
    // Early theme detection (mencegah flash)
    (function() {
        const theme = localStorage.getItem('theme') || 'light';
        const isDark = theme === 'dark' || (theme === 'system' && window.matchMedia('(prefers-color-scheme: dark)').matches);
        if (isDark) {
            document.documentElement.classList.add('dark');
        } else {
            document.documentElement.classList.remove('dark');
        }
    })();
</script>
<link href="<?= $base ?>assets/tailwind/src/output.css?v=<?php echo filemtime($base . 'assets/tailwind/src/output.css'); ?>" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Poppins:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&display=swap" rel="stylesheet">
<style>
        * {
            font-family: "Poppins", sans-serif;
            scroll-behavior: smooth;
        }

        /* Scroll Reveal Animation Styles */
        .scroll-reveal {
            opacity: 0;
            transform: translateY(30px);
            transition: all 0.8s cubic-bezier(0.4, 0, 0.2, 1);
            will-change: opacity, transform;
        }

        .scroll-reveal.active {
            opacity: 1;
            transform: translateY(0);
        }

        /* Global Custom Scrollbar */
        ::-webkit-scrollbar {
            width: 10px;
            height: 10px;
        }
        ::-webkit-scrollbar-track {
            background: transparent;
        }
        ::-webkit-scrollbar-thumb {
            background: #cbd5e1;
            border-radius: 20px;
            border: 3px solid transparent;
            background-clip: content-box;
        }
        .dark ::-webkit-scrollbar-thumb {
            background: #334155;
            border: 3px solid transparent;
            background-clip: content-box;
        }
        ::-webkit-scrollbar-thumb:hover {
            background: #94a3b8;
            background-clip: content-box;
        }
        .dark ::-webkit-scrollbar-thumb:hover {
            background: #475569;
            background-clip: content-box;
        }

        /* Firefox Support */
        * {
            scrollbar-width: thin;
            scrollbar-color: #cbd5e1 transparent;
        }
        .dark * {
            scrollbar-color: #334155 transparent;
        }

        /* --- SWEETALERT2 DARK MODE --- */
        .dark .swal2-popup {
            background-color: #0f172a !important;
            color: #f1f5f9 !important;
            border: 1px solid #1e293b !important;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5) !important;
        }
        .dark .swal2-title, 
        .dark .swal2-html-container,
        .dark .swal2-content {
            color: #f1f5f9 !important;
        }
        .dark .swal2-close {
            color: #94a3b8 !important;
        }
        .dark .swal2-close:hover {
            color: #f1f5f9 !important;
        }
    </style>
<script async src="https://www.googletagmanager.com/gtag/js?id=G-Q0TXETXV6Y"></script>
    <script>
        window.dataLayer = window.dataLayer || [];

        function gtag() {
            dataLayer.push(arguments);
        }
        gtag('js', new Date());

        gtag('config', 'G-Q0TXETXV6Y');
    </script>