<?php
echo "<!DOCTYPE html>
    <html>
    <head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <meta name='theme-color' content='#1e1b4b' />
    <title>PTIK C 2024</title>
    <link rel='icon' href='../assets/image/icon.png' />
    <link rel='preconnect' href='https://fonts.googleapis.com'>
    <link rel='preconnect' href='https://fonts.gstatic.com' crossorigin>
    <link href='https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap' rel='stylesheet'>
    <style>
        body { 
            font-family: 'Poppins', sans-serif !important; 
            background-color: #f8fafc;
            transition: background-color 0.3s;
        }
        .dark body {
            background-color: #020617;
        }
    </style>
    <!-- Google tag (gtag.js) -->
    <script async src=\"https://www.googletagmanager.com/gtag/js?id=G-Q0TXETXV6Y\"></script>
    <script>
        window.dataLayer = window.dataLayer || [];

        function gtag() {
            dataLayer.push(arguments);
        }
        gtag('js', new Date());

        gtag('config', 'G-Q0TXETXV6Y');

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
    <script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script>
    </head>
    <body>
        <script>
            const isDarkMode = document.documentElement.classList.contains('dark');
            Swal.fire({
                icon: 'error',
                title: 'Akses Ditolak!',
                text: 'Hanya Admin yang dapat mengakses halaman ini.',
                background: isDarkMode ? '#0f172a' : '#fff',
                color: isDarkMode ? '#f1f5f9' : '#1e293b',
                confirmButtonColor: '#4f46e5',
                confirmButtonText: 'Kembali'
            }).then((result) => {
                window.location.href = '../index.php';
            });
        </script>
    </body>
    </html>";
    ?>