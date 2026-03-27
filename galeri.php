<?php
include 'koneksi.php';

include 'includes/pesan.php';
?>
<!DOCTYPE html>
<html lang="id">

<head>
	<title>Galeri Kelas - PTIK C 2024</title>
	<?php include 'includes/head.php'; ?>
	<style>
		.gallery-img {
			image-rendering: -webkit-optimize-contrast;
			image-rendering: crisp-edges;
			transform: translateZ(0);
			/* Force GPU rendering for smoother scaling */
			backface-visibility: hidden;
		}

		/* Ratio 16:9 aspect ratio box */
		.aspect-16-9 {
			aspect-ratio: 16 / 9;
		}


	</style>
</head>

<body class="bg-gray-50 dark:bg-slate-950 min-h-screen flex flex-col transition-colors duration-300">

	<?php include 'includes/header_main.php'; ?>

	<!-- spacer to avoid content hidden under fixed header -->
	<div class="h-10"></div>

	<main class="flex-grow">
		<section class="py-12">
			<div class="container mx-auto px-6">
				<h1 class="text-3xl font-bold mb-2 text-gray-800 dark:text-white scroll-reveal text-center transition-colors">Galeri Kegiatan</h1>
				<p class="text-gray-500 dark:text-slate-400 mb-8 scroll-reveal text-sm text-center max-w-2xl mx-auto">Abadikan setiap momen berharga dan keseruan kegiatan kelas dalam dokumentasi visual yang tertata.</p>

				<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
					<?php
					$query_galeri = "SELECT * FROM galeri ORDER BY tanggal_kegiatan DESC";
					$result_galeri = mysqli_query($koneksi, $query_galeri);

					if (mysqli_num_rows($result_galeri) > 0) {
						while ($row = mysqli_fetch_assoc($result_galeri)) {
							$galeri_id = $row['id'];
							$slider_id = "s" . $galeri_id;
					?>
							<div class="bg-white dark:bg-slate-900 rounded-xl shadow-md overflow-hidden flex flex-col scroll-reveal border border-transparent dark:border-slate-800">
								<div class="relative group aspect-16-9 overflow-hidden">
									<div class="slider h-full overflow-hidden relative" data-slider-id="<?= $slider_id ?>">
										<div class="slides h-full flex transition-transform duration-500">
											<?php
											$query_foto = "SELECT * FROM galeri_foto WHERE galeri_id = $galeri_id";
											$result_foto = mysqli_query($koneksi, $query_foto);
											if (mysqli_num_rows($result_foto) > 0) {
												while ($foto = mysqli_fetch_assoc($result_foto)) {
											?>
													<img src="storage/gallery/<?= $foto['foto_path'] ?>" class="w-full h-full object-cover flex-shrink-0 cursor-pointer hover:opacity-90 transition-opacity gallery-img" alt="<?= $row['judul'] ?> ptik c 24" loading="lazy" onclick="openModal(this)">
												<?php
												}
											} else {
												?>
												<img src="assets/image/logo.webp" class="w-full h-full object-cover flex-shrink-0 cursor-pointer hover:opacity-90 transition-opacity gallery-img" alt="No image ptik c 24" loading="lazy" onclick="openModal(this)">
											<?php
											}
											?>
										</div>
									</div>
									<button data-prev data-slider="<?= $slider_id ?>" class="absolute left-2 top-1/2 transform -translate-y-1/2 bg-white/80 dark:bg-slate-800/80 rounded-full p-2 shadow-md hidden group-hover:block transition-all hover:bg-white dark:hover:bg-slate-700 z-10"><i class="fas fa-chevron-left text-indigo-800 dark:text-indigo-400"></i></button>
									<button data-next data-slider="<?= $slider_id ?>" class="absolute right-2 top-1/2 transform -translate-y-1/2 bg-white/80 dark:bg-slate-800/80 rounded-full p-2 shadow-md hidden group-hover:block transition-all hover:bg-white dark:hover:bg-slate-700 z-10"><i class="fas fa-chevron-right text-indigo-800 dark:text-indigo-400"></i></button>
								</div>
								<div class="p-3">
									<h3 class="font-semibold text-sm mb-1 truncate dark:text-white"><?= $row['judul'] ?></h3>
									<p class="text-[10px] text-gray-400 dark:text-slate-500"><i class="far fa-calendar-alt mr-1"></i><?= date('d M Y', strtotime($row['tanggal_kegiatan'])) ?></p>
								</div>
							</div>
					<?php
						}
					} else {
						echo "<p class='text-center text-gray-500 dark:text-slate-400 col-span-full py-20'>Belum ada galeri kegiatan.</p>";
					}
					?>
				</div>
			</div>
		</section>
	</main>

	<?php include 'includes/footer_main.php'; ?>

	<!-- Modal Gallery -->
	<div id="imageModal" class="fixed inset-0 z-[100] hidden bg-black/90 backdrop-blur-sm items-center justify-center p-4 transition-all duration-300 opacity-0">
		<button onclick="closeModal()" class="absolute top-6 right-6 text-white text-3xl hover:text-gray-400 transition-colors z-[110]">
			<i class="fas fa-times"></i>
		</button>

		<!-- Modal Navigation -->
		<button id="modalPrev" class="absolute left-4 md:left-10 top-1/2 -translate-y-1/2 text-white/50 hover:text-white text-4xl transition-colors z-[110] p-4">
			<i class="fas fa-chevron-left"></i>
		</button>
		<button id="modalNext" class="absolute right-4 md:right-10 top-1/2 -translate-y-1/2 text-white/50 hover:text-white text-4xl transition-colors z-[110] p-4">
			<i class="fas fa-chevron-right"></i>
		</button>

		<div class="max-w-5xl w-full flex flex-col justify-center items-center gap-4">
			<div class="relative group w-full flex justify-center">
				<img id="modalImage" src="" class="max-h-[85vh] max-w-full rounded-lg shadow-2xl transform scale-95 transition-transform duration-300 gallery-img" alt="Preview ptik c 24">
			</div>
			<!-- Photo Indicator -->
			<div id="photoIndicator" class="text-white/70 text-sm font-medium bg-white/10 px-4 py-1 rounded-full backdrop-blur-md">
				1 / 1
			</div>
		</div>
	</div>

	<?php include 'includes/script_main.php'; ?>

	<script>
		// Simple per-card slider logic
		document.addEventListener('DOMContentLoaded', function() {
			const sliders = document.querySelectorAll('.slider');
			sliders.forEach(slider => {
				const slides = slider.querySelector('.slides');
				const slideItems = slides.children;
				const slideCount = slideItems.length;
				let idx = 0;
				const id = slider.getAttribute('data-slider-id');

				const nextBtn = document.querySelector(`[data-next][data-slider="${id}"]`);
				const prevBtn = document.querySelector(`[data-prev][data-slider="${id}"]`);

				function update() {
					slides.style.transform = `translateX(-${idx * 100}%)`;
				}

				if (nextBtn) nextBtn.addEventListener('click', (e) => {
					e.stopPropagation();
					idx = (idx + 1) % slideCount;
					update();
				});
				if (prevBtn) prevBtn.addEventListener('click', (e) => {
					e.stopPropagation();
					idx = (idx - 1 + slideCount) % slideCount;
					update();
				});
			});
		});

		// Modal Logic
		const modal = document.getElementById('imageModal');
		const modalImg = document.getElementById('modalImage');
		const indicator = document.getElementById('photoIndicator');
		const modalPrev = document.getElementById('modalPrev');
		const modalNext = document.getElementById('modalNext');

		let currentPhotos = [];
		let currentPhotoIndex = 0;

		function openModal(imgElement) {
			const slidesContainer = imgElement.parentElement;
			const images = Array.from(slidesContainer.querySelectorAll('img'));

			currentPhotos = images.map(img => img.src);
			currentPhotoIndex = images.indexOf(imgElement);

			updateModalContent();

			modal.classList.remove('hidden');
			modal.classList.add('flex');
			document.body.style.overflow = 'hidden';

			setTimeout(() => {
				modal.classList.add('opacity-100');
				modalImg.classList.add('scale-100');
				modalImg.classList.remove('scale-95');
			}, 10);
		}

		function updateModalContent() {
			if (currentPhotos.length === 0) return;

			// Start fade out before changing source
			modalImg.style.opacity = '0.5';

			setTimeout(() => {
				modalImg.src = currentPhotos[currentPhotoIndex];
				indicator.textContent = `${currentPhotoIndex + 1} / ${currentPhotos.length}`;
				modalImg.style.opacity = '1';

				// Hide navigation if only 1 photo
				modalPrev.classList.toggle('hidden', currentPhotos.length <= 1);
				modalNext.classList.toggle('hidden', currentPhotos.length <= 1);
			}, 150);
		}

		modalNext.addEventListener('click', (e) => {
			e.stopPropagation();
			currentPhotoIndex = (currentPhotoIndex + 1) % currentPhotos.length;
			updateModalContent();
		});

		modalPrev.addEventListener('click', (e) => {
			e.stopPropagation();
			currentPhotoIndex = (currentPhotoIndex - 1 + currentPhotos.length) % currentPhotos.length;
			updateModalContent();
		});

		function closeModal() {
			modal.classList.remove('opacity-100');
			modalImg.classList.add('scale-95');
			modalImg.classList.remove('scale-100');
			document.body.style.overflow = '';
			setTimeout(() => {
				modal.classList.add('hidden');
				modal.classList.remove('flex');
			}, 300);
		}

		modal.addEventListener('click', (e) => {
			if (e.target === modal || e.target.closest('div') === modal.querySelector('.max-w-5xl')) {
				if (e.target === modal) closeModal();
			}
		});

		document.addEventListener('keydown', (e) => {
			if (modal.classList.contains('hidden')) return;

			if (e.key === 'Escape') closeModal();
			if (e.key === 'ArrowRight' && currentPhotos.length > 1) {
				currentPhotoIndex = (currentPhotoIndex + 1) % currentPhotos.length;
				updateModalContent();
			}
			if (e.key === 'ArrowLeft' && currentPhotos.length > 1) {
				currentPhotoIndex = (currentPhotoIndex - 1 + currentPhotos.length) % currentPhotos.length;
				updateModalContent();
			}
		});


	</script>
</body>

</html>