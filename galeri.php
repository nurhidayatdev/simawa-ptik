<?php
include 'koneksi.php';

include 'includes/pesan.php';
?>
<!DOCTYPE html>
<html lang="id">

<head>
	<title>Galeri Kelas - PTIK C 2024</title>
	<?php include 'includes/head.php'; ?>
</head>

<body class="bg-gray-50 min-h-screen flex flex-col">
	<?php include 'includes/header_main.php'; ?>

	<!-- spacer to avoid content hidden under fixed header -->
	<div class="h-10"></div>

	<main class="flex-grow">
		<section class="py-12">
			<div class="container mx-auto px-4">
				<h2 class="text-3xl font-bold mb-6 text-gray-800">Galeri Kegiatan</h2>

				<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
					<?php
					$query_galeri = "SELECT * FROM galeri ORDER BY tanggal_kegiatan DESC";
					$result_galeri = mysqli_query($koneksi, $query_galeri);

					if (mysqli_num_rows($result_galeri) > 0) {
						while ($row = mysqli_fetch_assoc($result_galeri)) {
							$galeri_id = $row['id'];
							$slider_id = "s" . $galeri_id;
					?>
							<div class="bg-white rounded-xl shadow-md overflow-hidden">
								<div class="relative group">
									<div class="slider overflow-hidden relative" data-slider-id="<?= $slider_id ?>">
										<div class="slides flex transition-transform duration-500">
											<?php
											$query_foto = "SELECT * FROM galeri_foto WHERE galeri_id = $galeri_id";
											$result_foto = mysqli_query($koneksi, $query_foto);
											if (mysqli_num_rows($result_foto) > 0) {
												while ($foto = mysqli_fetch_assoc($result_foto)) {
											?>
													<img src="storage/gallery/<?= $foto['foto_path'] ?>" class="w-full h-48 object-cover flex-shrink-0" alt="<?= $row['judul'] ?>">
												<?php
												}
											} else {
												?>
												<img src="assets/image/logo.webp" class="w-full h-48 object-cover flex-shrink-0" alt="No image">
											<?php
											}
											?>
										</div>
									</div>
									<button data-prev data-slider="<?= $slider_id ?>" class="absolute left-2 top-1/2 transform -translate-y-1/2 bg-white/80 rounded-full p-2 shadow-md hidden group-hover:block"><i class="fas fa-chevron-left text-indigo-800"></i></button>
									<button data-next data-slider="<?= $slider_id ?>" class="absolute right-2 top-1/2 transform -translate-y-1/2 bg-white/80 rounded-full p-2 shadow-md hidden group-hover:block"><i class="fas fa-chevron-right text-indigo-800"></i></button>
								</div>
								<div class="p-4">
									<h3 class="font-semibold text-lg mb-1"><?= $row['judul'] ?></h3>
									<p class="text-[10px] text-gray-400"><i class="far fa-calendar-alt mr-1"></i><?= date('d M Y', strtotime($row['tanggal_kegiatan'])) ?></p>
								</div>
							</div>
					<?php
						}
					} else {
						echo "<p class='text-center text-gray-500 col-span-full'>Belum ada galeri kegiatan.</p>";
					}
					?>
				</div>
			</div>
		</section>
	</main>

	<?php include 'includes/footer_main.php'; ?>
	<?php include 'includes/script_main.php'; ?>

	<script>
		// Simple per-card slider logic
		document.addEventListener('DOMContentLoaded', function() {
			const sliders = document.querySelectorAll('.slider');
			sliders.forEach(slider => {
				const slides = slider.querySelector('.slides');
				const slideCount = slides.children.length;
				let idx = 0;
				const id = slider.getAttribute('data-slider-id');

				const nextBtn = document.querySelector(`[data-next][data-slider="${id}"]`);
				const prevBtn = document.querySelector(`[data-prev][data-slider="${id}"]`);

				function update() {
					slides.style.transform = `translateX(-${idx * 100}%)`;
				}

				if (nextBtn) nextBtn.addEventListener('click', () => {
					idx = (idx + 1) % slideCount;
					update();
				});
				if (prevBtn) prevBtn.addEventListener('click', () => {
					idx = (idx - 1 + slideCount) % slideCount;
					update();
				});

				// auto-play
				setInterval(() => {
					idx = (idx + 1) % slideCount;
					update();
				}, 4000);
			});
		});
	</script>
</body>

</html>