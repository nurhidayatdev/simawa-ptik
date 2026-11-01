<?php
include 'koneksi.php';

include 'includes/pesan.php';

// Include foto helpers for cache busting
include_once 'functions/foto_helpers.php';

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
  <style>
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
  </style>
</head>

<body class="bg-gray-50 dark:bg-slate-950 min-h-screen flex flex-col transition-colors duration-300">

  <?php include 'includes/header_main.php'; ?>

  <!-- spacer to avoid content hidden under fixed header -->
  <div class="h-10"></div>


  <div class="container mx-auto px-6 py-12 flex-grow">
    <h1 class="text-3xl font-bold mb-2 text-gray-800 dark:text-white scroll-reveal text-center transition-colors">Data Mahasiswa</h1>
    <p class="text-gray-500 dark:text-slate-400 mb-8 scroll-reveal text-sm text-center italic">Direktori profil seluruh rekan mahasiswa PTIK C 2024, jalin silaturahmi dan kenali lebih dalam setiap jiwa di angkatan kita.</p>

    <div class="flex flex-col sm:flex-row items-center justify-center gap-3 mb-10 scroll-reveal">
      <input id="searchInput" type="search" placeholder="Cari nama, NIM..." class="w-full sm:w-1/2 px-5 py-3 rounded-full border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 text-slate-900 dark:text-white shadow-sm focus:ring-2 focus:ring-indigo-500 transition-all outline-none" />
    </div>

    <!-- Card view (hidden by default) -->
    <div id="cardView">
      <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 gap-4 md:gap-6">
        <?php foreach ($rows as $row) :
          $searchTextCard = strtolower($row['nim'] . ' ' . $row['nama_lengkap']);
        ?>
          <div onclick="openModal(this)"
            data-nama="<?php echo htmlspecialchars($row['nama_lengkap']); ?>"
            data-nim="<?php echo htmlspecialchars($row['nim']); ?>"
                        data-bio="<?php echo htmlspecialchars($row['bio'] ?? ''); ?>"
            data-foto="<?= profilePhotoUrl($row['nama_lengkap']) ?>"
            data-initials="<?= strtoupper(substr($row['nama_lengkap'], 0, 2)) ?>"
                        data-search="<?php echo htmlspecialchars($searchTextCard, ENT_QUOTES); ?>"
            class="cursor-pointer bg-white dark:bg-slate-900 rounded-2xl shadow-md overflow-hidden border border-slate-200 dark:border-slate-800 hover:shadow-lg transition-all group hover:-translate-y-1 scroll-reveal">
            <div class="bg-gradient-to-br from-indigo-800 to-indigo-950 h-14 relative">
              <div class="absolute -bottom-8 left-1/2 -translate-x-1/2">
                <div class="w-16 h-16 rounded-2xl flex items-center justify-center text-white text-lg font-bold shadow-lg border-4 border-white dark:border-slate-800 overflow-hidden bg-indigo-900 group-hover:scale-105 transition-transform">
                  <img src="<?= profilePhotoUrl($row['nama_lengkap']) ?>" alt="<?php echo htmlspecialchars($row['nama_lengkap']); ?>" class="w-full h-full object-cover">
                </div>
              </div>
            </div>
            <div class="pt-10 pb-4 px-3 text-center">
              <h4 class="text-xs font-bold text-slate-900 dark:text-slate-100 truncate mb-1"><?php echo htmlspecialchars($row['nama_lengkap']); ?></h4>
              <p class="text-[10px] text-slate-500 dark:text-slate-400 mb-2 truncate"><?php echo htmlspecialchars($row['bio'] ?? ''); ?></p>
              
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    </div>
  </div>

  <!-- Student Detail Modal -->
  <div id="studentModal" class="fixed inset-0 z-[100] hidden" aria-labelledby="modal-title" role="dialog" aria-modal="true">
    <!-- Backdrop -->
    <div class="fixed inset-0 bg-black/60 backdrop-blur-sm transition-opacity opacity-0 pointer-events-none" id="modalBackdrop"></div>

    <div class="fixed inset-0 z-10 w-screen overflow-y-auto">
      <div class="flex min-h-full items-center justify-center p-4 text-center sm:p-0">
        <!-- Modal Panel -->
        <div class="relative transform overflow-hidden rounded-3xl bg-white dark:bg-slate-900 text-left shadow-2xl transition-all sm:my-8 w-full max-w-sm scale-95 opacity-0 border border-white dark:border-slate-800" id="modalPanel">

          <!-- Header Pattern -->
          <div class="h-28 bg-gradient-to-br from-indigo-800 to-indigo-950 relative overflow-hidden">
            <div class="absolute inset-0 opacity-20 flex items-center justify-start pointer-events-none pl-5">
              <img src="assets/image/logo.webp" alt="Pattern" class="h-full w-auto object-contain scale-80 origin-left">
            </div>
            <button type="button" onclick="closeModal()" class="absolute top-4 right-4 w-8 h-8 flex items-center justify-center bg-black/20 hover:bg-black/40 text-white rounded-full transition-all backdrop-blur-md z-10 focus:outline-none">
              <i class="fas fa-times text-xs"></i>
            </button>
          </div>

          <!-- Content -->
          <div class="px-6 pb-8 -mt-14 relative z-0">
            <!-- Profile Image -->
            <div class="flex justify-center">
              <div class="w-28 h-28 rounded-2xl p-1.5 bg-white dark:bg-slate-800 shadow-xl rotate-0 hover:rotate-3 transition-transform duration-500 border border-indigo-50 dark:border-slate-700">
                <div id="modalFotoContainer" class="w-full h-full rounded-xl overflow-hidden bg-indigo-50 dark:bg-indigo-950/30 flex items-center justify-center text-indigo-300 dark:text-indigo-600 text-4xl font-bold border border-indigo-50 dark:border-indigo-900/50">
                  <!-- Image injected via JS -->
                </div>
              </div>
            </div>

            <div class="text-center mt-5">
              <h3 class="text-xl font-bold text-slate-900 dark:text-white leading-tight" id="modalNama"></h3>
              <p class="text-sm text-slate-500 dark:text-slate-400 mt-2 px-4 line-clamp-3" id="modalBio"></p>

              <div class="flex items-center justify-center gap-2 mt-5">
                <div class="px-4 py-1.5 bg-indigo-50 dark:bg-indigo-950/50 text-indigo-700 dark:text-indigo-400 rounded-full text-xs font-semibold border border-indigo-100 dark:border-indigo-800 shadow-sm flex items-center gap-1.5">
                  <i class="fas fa-id-card text-indigo-400"></i>
                  <span id="modalNim"></span>
                </div>
                
              </div>

              
            </div>
          </div>

        </div>
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

    // Modal Logic
    const modal = document.getElementById('studentModal');
    const modalBackdrop = document.getElementById('modalBackdrop');
    const modalPanel = document.getElementById('modalPanel');

    function openModal(el) {
      const data = el.dataset;

      document.getElementById('modalNama').textContent = data.nama;
      document.getElementById('modalNim').textContent = data.nim;
      
      const bioEl = document.getElementById('modalBio');
      if (data.bio) {
        bioEl.textContent = data.bio;
        bioEl.parentElement.classList.remove('hidden');
      } else {
        bioEl.textContent = "Tidak ada bio.";
      }

      // Foto
      const fotoContainer = document.getElementById('modalFotoContainer');
      if (data.foto) {
        fotoContainer.innerHTML = `<img src="${data.foto}" alt="${data.nama}" class="w-full h-full object-cover">`;
      } else {
        fotoContainer.innerHTML = `<span>${data.initials}</span>`;
      }

      

      // Show Modal with Animation
      modal.classList.remove('hidden');
      // Trigger reflow
      void modal.offsetWidth;

      // Animate in
      modalBackdrop.classList.remove('opacity-0', 'pointer-events-none');
      modalBackdrop.classList.add('opacity-100', 'pointer-events-auto');

      modalPanel.classList.remove('scale-95', 'opacity-0');
      modalPanel.classList.add('scale-100', 'opacity-100');
    }

    function closeModal() {
      // Animate out
      modalBackdrop.classList.remove('opacity-100', 'pointer-events-auto');
      modalBackdrop.classList.add('opacity-0', 'pointer-events-none');

      modalPanel.classList.remove('scale-100', 'opacity-100');
      modalPanel.classList.add('scale-95', 'opacity-0');

      // Hide after animation (300ms)
      setTimeout(() => {
        modal.classList.add('hidden');
      }, 300);
    }

    // Close on backdrop click
    modalBackdrop.addEventListener('click', closeModal);

    // Close on Escape key
    document.addEventListener('keydown', function(event) {
      if (event.key === "Escape" && !modal.classList.contains('hidden')) {
        closeModal();
      }
    });

    // Scroll Reveal Implementation
    document.addEventListener('DOMContentLoaded', function() {
      const observerOptions = {
        threshold: 0.1,
        rootMargin: '0px 0px -50px 0px'
      };

      const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
          if (entry.isIntersecting) {
            entry.target.classList.add('active');
          } else {
            entry.target.classList.remove('active');
          }
        });
      }, observerOptions);

      const revealElements = document.querySelectorAll('.scroll-reveal');
      revealElements.forEach(el => observer.observe(el));
    });
  </script>
</body>

</html>