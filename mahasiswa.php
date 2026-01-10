<?php
include 'koneksi.php';

include 'includes/pesan.php';

$result = mysqli_query($koneksi, "SELECT * FROM mahasiswa ORDER BY nim ASC");
$rows = [];
while ($r = mysqli_fetch_assoc($result)) {
  $rows[] = $r;
}
?>
<!DOCTYPE html>
<html lang="id">

<head>
  <title>Mahasiswa - Kelas PTIK C 2024</title>
  <?php include 'includes/head.php'; ?>
</head>

<body class="bg-gray-50 min-h-screen flex flex-col">
  <?php include 'includes/header_main.php'; ?>

  <!-- spacer to avoid content hidden under fixed header -->
  <div class="h-10"></div>


  <div class="container mx-auto p-6 mt-4">
    <h1 class="text-2xl font-bold mb-4 text-center text-indigo-950">Mahasiswa</h1>

    <div class="flex flex-col sm:flex-row items-center justify-center gap-3 mb-6">
      <input id="searchInput" type="search" placeholder="Cari nama, panggilan, NIM" class="w-full sm:w-1/2 px-4 py-2 rounded-full border border-slate-200 shadow-sm" />
    </div>

    <!-- Card view (hidden by default) -->
    <div id="cardView">
      <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 gap-4 md:gap-6">
        <?php foreach ($rows as $row) :
          $searchTextCard = strtolower($row['nim'] . ' ' . $row['nama_lengkap'] . ' ' . $row['nama_panggilan']);
        ?>
          <div data-search="<?php echo htmlspecialchars($searchTextCard, ENT_QUOTES); ?>" class="bg-white rounded-2xl shadow-md overflow-hidden border border-slate-200 hover:shadow-lg transition-all group">
            <div class="bg-gradient-to-br from-indigo-800 to-indigo-950 h-14 relative">
              <div class="absolute -bottom-8 left-1/2 -translate-x-1/2">
                <div class="w-16 h-16 rounded-2xl flex items-center justify-center text-white text-lg font-bold shadow-lg border-4 border-white overflow-hidden bg-indigo-900 group-hover:scale-105 transition-transform">
                  <?php if (!empty($row['foto'])): ?>
                    <img src="storage/profile/<?php echo htmlspecialchars($row['foto']); ?>" alt="<?php echo htmlspecialchars($row['nama_lengkap']); ?>" class="w-full h-full object-cover">
                  <?php else: ?>
                    <span><?php echo strtoupper(substr($row['nama_lengkap'], 0, 2)); ?></span>
                  <?php endif; ?>
                </div>
              </div>
            </div>
            <div class="pt-10 pb-4 px-3 text-center">
              <h4 class="text-xs font-bold text-slate-900 truncate mb-1"><?php echo htmlspecialchars($row['nama_lengkap']); ?></h4>
              <p class="text-[10px] text-slate-500 mb-2 truncate"><?php echo htmlspecialchars($row['bio'] ?? ''); ?></p>
              <?php if (!empty($row['instagram'])): ?>
                <a href="https://instagram.com/<?php echo ltrim(htmlspecialchars($row['instagram']), '@'); ?>" target="_blank" class="inline-flex items-center gap-1 px-2 py-0.5 bg-gradient-to-r from-purple-500 to-pink-500 text-white text-[9px] rounded-full hover:from-purple-600 hover:to-pink-600 transition-all shadow-sm">
                  <i class="fab fa-instagram"></i>
                  <span><?php echo htmlspecialchars($row['instagram']); ?></span>
                </a>
              <?php endif; ?>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    </div>
  </div>

  <?php include 'includes/footer_main.php'; ?>
<?php include 'includes/script_main.php'; ?>

  <script>
    


    // Search/filter functionality
    const searchInput = document.getElementById('searchInput');

    function applyFilter(q) {
      const ql = q.trim().toLowerCase();
      // filter cards
      document.querySelectorAll('#cardView [data-search]').forEach(card => {
        const s = (card.dataset.search || '').toLowerCase();
        if (!ql || s.indexOf(ql) !== -1) card.classList.remove('hidden');
        else card.classList.add('hidden');
      });
    }

    searchInput.addEventListener('input', (e) => applyFilter(e.target.value));
  </script>
</body>

</html>