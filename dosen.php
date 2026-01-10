<?php
include 'koneksi.php';

include 'includes/pesan.php';

$result = mysqli_query($koneksi, "SELECT * FROM dosen ORDER BY nama_dosen ASC");
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <title>Dosen - PTIK C 2024</title>
    <?php include 'includes/head.php'; ?>
</head>

<body class="bg-gray-50 min-h-screen flex flex-col">
    <!-- Header & Navigation -->
    <?php include 'includes/header_main.php'; ?>

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

    <?php include 'includes/footer_main.php'; ?>
<?php include 'includes/script_main.php'; ?>
</body>

</html>