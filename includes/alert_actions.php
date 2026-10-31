<?php
$is_error = !empty($error_message);
$alert_title = $is_error ? 'Perhatian!' : 'Berhasil!';
$alert_text = $is_error ? $error_message : 'Data berhasil diproses!';
$alert_icon = $is_error ? 'error' : 'success';
$confirm_color = $is_error ? '#ef4444' : '#4f46e5';
$next_action = $is_error ? "window.history.back();" : "window.location.href = '$redirect';";

echo "
    <!DOCTYPE html>
    <html>
    <head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <meta name='theme-color' content='#1e1b4b' />
    <link rel='icon' href='../assets/image/icon.png' />
    <title>SIMAWA PTIK 2024</title>
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
    <link rel='preconnect' href='https://fonts.googleapis.com'>
    <link rel='preconnect' href='https://fonts.gstatic.com' crossorigin>
    <link href='https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap' rel='stylesheet'>
    <script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script>
    </head>
    <body>
        <script>
            const isDarkMode = localStorage.getItem('theme') === 'dark' || document.documentElement.classList.contains('dark');
            Swal.fire({
                title: " . json_encode($alert_title) . ",
                html: " . json_encode($alert_text) . ",
                icon: '$alert_icon',
                background: isDarkMode ? '#0f172a' : '#fff',
                color: isDarkMode ? '#f1f5f9' : '#1e293b',
                confirmButtonColor: '$confirm_color',
                allowOutsideClick: false,
                allowEscapeKey: false,
                confirmButtonText: 'OK'
            }).then(() => {
                $next_action
            });
        </script>
    </body>
    </html>";
