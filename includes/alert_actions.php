<?php
echo "
    <!DOCTYPE html>
    <html>
    <head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <meta name='theme-color' content='#1e1b4b' />
    <link rel='icon' href='../assets/image/icon.png' />
    <title>PTIK C 2024</title>
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
    title: 'Berhasil!',
    text: 'Data berhasil ditambahkan!',
    icon: 'success',
    allowOutsideClick: false,
    allowEscapeKey: false,
    confirmButtonText: 'OK'
}).then(() => {
    window.location.href = '$redirect';
});
</script>

    </body>
    </html>";
