<?php
include 'koneksi.php';
include_once 'functions/foto_helpers.php';

// Ambil periode aktif
$q_periode = mysqli_query($koneksi, "SELECT * FROM periode WHERE status = 'aktif' LIMIT 1");
$periode = mysqli_fetch_assoc($q_periode);
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <title>Sejarah & Perjalanan - PTIK C 2024</title>
    <?php include 'includes/head.php'; ?>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Poppins', sans-serif;
        }

        .plus-jakarta {
            font-family: 'Plus Jakarta Sans', sans-serif;
        }

        .glass-card {
            background: rgba(255, 255, 255, 0.75);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            border: 1px solid rgba(255, 255, 255, 0.4);
        }

        .bg-mesh {
            background-color: #ffffff;
            background-image:
                radial-gradient(at 0% 0%, rgba(79, 70, 229, 0.05) 0px, transparent 50%),
                radial-gradient(at 100% 0%, rgba(6, 182, 212, 0.05) 0px, transparent 50%),
                radial-gradient(at 100% 100%, rgba(79, 70, 229, 0.05) 0px, transparent 50%),
                radial-gradient(at 0% 100%, rgba(79, 70, 229, 0.05) 0px, transparent 50%);
            transition: background-color 0.3s;
        }

        .dark .bg-mesh {
            background-color: #020617;
            background-image:
                radial-gradient(at 0% 0%, rgba(79, 70, 229, 0.15) 0px, transparent 50%),
                radial-gradient(at 100% 0%, rgba(6, 182, 212, 0.15) 0px, transparent 50%),
                radial-gradient(at 100% 100%, rgba(79, 70, 229, 0.15) 0px, transparent 50%),
                radial-gradient(at 0% 100%, rgba(79, 70, 229, 0.15) 0px, transparent 50%);
        }

        .narrative-container {
            position: relative;
            padding-left: 2rem;
        }

        @media (min-width: 1024px) {
            .narrative-container {
                padding-left: 4rem;
            }
        }

        .narrative-line {
            position: absolute;
            left: 0;
            top: 0;
            bottom: 0;
            width: 1px;
            background: linear-gradient(to bottom, #4f46e5 0%, #e2e8f0 100%);
            opacity: 0.3;
        }

        .dark .narrative-line {
            background: linear-gradient(to bottom, #6366f1 0%, #1e1b4b 100%);
            opacity: 0.5;
        }

        .story-block {
            position: relative;
            margin-bottom: 7rem;
        }

        .story-dot {
            position: absolute;
            left: -2.35rem;
            top: 0.4rem;
            width: 12px;
            height: 12px;
            background: #4f46e5;
            border-radius: 50%;
            border: 2px solid white;
            box-shadow: 0 0 0 4px rgba(79, 70, 229, 0.1);
            z-index: 10;
            transition: all 0.3s;
        }

        .dark .story-dot {
            background: #6366f1;
            border-color: #0f172a;
            box-shadow: 0 0 0 4px rgba(99, 102, 241, 0.2);
        }

        @media (min-width: 1024px) {
            .story-dot {
                left: -4rem;
                transform: translateX(50%);
            }
        }

        .story-date {
            font-size: 0.65rem;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.15em;
            color: #6366f1;
            margin-bottom: 0.5rem;
            display: block;
            transition: color 0.3s;
        }

        .dark .story-date {
            color: #818cf8;
        }

        .story-title {
            font-size: 1.35rem;
            font-weight: 800;
            color: #1e1b4b;
            margin-bottom: 1.25rem;
            line-height: 1.3;
            font-family: 'Plus Jakarta Sans', sans-serif;
            transition: color 0.3s;
        }

        .dark .story-title {
            color: #f8fafc;
        }

        .story-content {
            font-size: 0.85rem;
            line-height: 1.8;
            color: #475569;
            text-align: justify;
            transition: color 0.3s;
        }

        .dark .story-content {
            color: #94a3b8;
        }

        .highlight-text {
            color: #1e1b4b;
            font-weight: 700;
            transition: color 0.3s;
        }

        .dark .highlight-text {
            color: #818cf8;
        }

        .dropcap::first-letter {
            float: left;
            font-size: 2.5rem;
            line-height: 1;
            font-weight: 900;
            margin-right: 0.6rem;
            color: #4f46e5;
            font-family: 'Plus Jakarta Sans', sans-serif;
            transition: color 0.3s;
        }

        .dark .dropcap::first-letter {
            color: #6366f1;
        }



        .img-actual {
            width: 100%;
            height: auto;
            display: block;
            border-radius: 1.25rem;
        }
    </style>
</head>

<body class="bg-mesh min-h-screen transition-colors duration-300">

    <?php include 'includes/header_main.php'; ?>

    <!-- Spacer -->
    <div class="h-10"></div>

    <main class="container mx-auto px-6 py-12 flex-grow">
        <h1 class="text-3xl font-bold mb-2 text-gray-800 dark:text-white scroll-reveal text-center transition-colors">Sejarah & Perjalanan</h1>
        <p class="text-gray-500 dark:text-slate-400 mb-8 scroll-reveal text-sm text-center max-w-2xl mx-auto transition-colors">Kilas balik perjalanan ProTechNoTy dari awal terbentuk hingga menjadi komunitas digital yang solid.</p>
        <div class="narrative-container">
            <div class="narrative-line"></div>

            <!-- Bab 1: Genesis -->
            <article class="story-block">
                <div class="story-dot"></div>
                <span class="story-date">24 Agustus 2024</span>
                <h2 class="story-title">Fajar Baru: Momentum Terbentuknya Keluarga ProTechNoTy</h2>
                <div class="story-content dropcap">
                    Segala sesuatunya bermula pada <span class="highlight-text">24 Agustus 2024</span>. Di bawah terik matahari kampus Universitas Negeri Makassar, tepat pada hari terakhir pelaksanaan PKKMB Jurusan Teknik Informatika dan Komputer, sebuah entitas baru resmi lahir. 37 individu yang datang dari berbagai latar belakang berkumpul, menyadari bahwa mereka kini berada dalam satu perahu yang sama: PTIK Kelas C Angkatan 2024.
                    <br><br>
                    Hari itu bukan sekadar formalitas akademik. Itulah saat di mana struktur emosional dan organisasional mulai dibangun. Melalui proses pemilihan yang demokratis di tengah kelelahan pasca-PKKMB, terpilihlah jajaran pengurus perdana yang memikul beban amanah kelas. <span class="highlight-text">Andi Fahril S</span> secara resmi diamanahkan sebagai Ketua Tingkat pertama, didampingi oleh <span class="highlight-text">Adelia Magfira Hapati</span> sebagai Sekretaris dan <span class="highlight-text">Islatul Adha</span> sebagai Bendahara. 
                    <br><br>
                    Tidak butuh waktu lama bagi semangat ini untuk merambah dunia digital. Malam itu juga, grup komunikasi WhatsApp resmi dibentuk, menjadi saksi bisu pecahnya percakapan perdana yang penuh antusiasme. Identitas visual pun langsung digagas; sebuah logo kelas pertama lahir dari tangan dingin <span class="highlight-text">Nur Hidayat</span> yang menjabat di divisi Media Kreatif, memberikan wajah pertama bagi komunitas yang baru berumur hitungan jam ini.
                </div>
                <div class="flex flex-col md:flex-row gap-4 mt-6">
                    <div class="w-full flex-[2]">
                        <img src="storage/gallery/last_day_pkkmb_1771308356_0.jpg" alt="Pembentukan Kelas ptik c 24" class="img-actual" onerror="this.style.display='none';">
                    </div>
                    <div class="w-full flex-1 !max-w-[350px]">
                        <img src="assets/image/logo_lama.jpeg" alt="Logo Lama ptik c 24" class="img-actual" onerror="this.style.display='none';">
                    </div>
                </div>
            </article>

            <!-- Bab 2: Rebranding -->
            <article class="story-block">
                <div class="story-dot"></div>
                <span class="story-date">25 Februari 2025</span>
                <h2 class="story-title">Transformasi Visual: Proyek Rebranding Logo 2025</h2>
                <div class="story-content">
                    Seiring berjalannya waktu, kelas menyadari bahwa identitas visual harus berevolusi sejalan dengan visi teknologi yang semakin tajam. Tepat pada <span class="highlight-text">25 Februari 2025</span>, ProTechNoTy melakukan langkah besar melalui proyek rebranding. Nur Hidayat kembali mengambil peran sentral dalam merancang ulang simbol kelas. 
                    <br><br>
                    Logo baru ini tidak hanya sekadar estetika, namun merupakan representasi dari filosofi <span class="italic font-medium">Programmer, Technology, Innovation, and Creativity</span>. Rebranding ini menandai kedewasaan kelas dalam memandang pentingnya <span class="italic">personal branding</span> sebagai entitas digital di lingkungan Fakultas Teknik.
                </div>
                <div class="mt-6 !max-w-[350px]">
                    <img src="assets/image/logo_baru.jpeg" alt="Logo Baru ptik c 24" class="img-actual" onerror="this.style.display='none';">
                </div>
            </article>



            <!-- Bab 4: Estafet Keti -->
            <article class="story-block">
                <div class="story-dot"></div>
                <span class="story-date">15 Agustus 2025</span>
                <h2 class="story-title">Estafet Kepemimpinan: Babak Baru Struktur Organisasi</h2>
                <div class="story-content">
                    Salah satu titik balik paling krusial dalam sejarah organisasi kelas terjadi pada <span class="highlight-text">15 Agustus 2025</span>. Andi Fahril S, yang telah memimpin kelas melewati masa-masa awal yang sulit, secara resmi mengundurkan diri dari jabatannya sebagai Ketua Tingkat. Pengunduran diri ini bukanlah akhir, melainkan sebuah perpindahan peran strategis. 
                    <br><br>
                    Kepemimpinan kelas kemudian dipercayakan kepada <span class="highlight-text">Shofiyah Rosyadah</span>, yang terpilih sebagai Ketua Tingkat baru. Di bawah kepemimpinan Shofiyah, kelas mulai menerapkan pendekatan-pendekatan baru yang lebih inklusif. Sementara itu, Andi Fahril S tidak lantas berpangku tangan; ia kembali ke akar minatnya dengan memperkuat tim Media Kreatif, memastikan setiap output visual maupun dokumentasi kelas tetap memiliki standar kualitas yang tinggi.
                </div>
            </article>

            <!-- Bab 5: Web Statis -->
            <article class="story-block">
                <div class="story-dot"></div>
                <span class="story-date">12 September 2025</span>
                <h2 class="story-title">Pondasi Digital: Mengudara Secara Resmi di Dunia Maya</h2>
                <div class="story-content">
                    Sebagai mahasiswa Teknik Informatika, eksistensi digital adalah sebuah keharusan. Pada <span class="highlight-text">12 September 2025</span>, tonggak digital pertama berhasil ditancapkan. Website resmi kelas versi pertama diluncurkan di subdomain <a href="https://ptikc24.vercel.app" target="_blank" class="text-indigo-600 font-bold hover:underline">ptikc24.vercel.app</a>. 
                    <br><br>
                    Meskipun saat itu website tersebut masih bersifat statis—dibangun dengan teknologi dasar HTML, CSS, dan JavaScript oleh Nur Hidayat—platform ini memberikan dampak luar biasa. Untuk pertama kalinya, 36 mahasiswa memiliki akses tunggal untuk mengecek jadwal kuliah, daftar nama, serta daftar tugas yang terus diperbarui secara manual. Inilah bukti nyata bahwa ProTechNoTy siap bertransformasi menjadi kelas digital yang modern.
                </div>
                <div class="mt-6 max-w-2xl">
                    <img src="assets/image/web_lama.png" alt="Web Lama ptik c 24" class="img-actual" onerror="this.style.display='none';">
                </div>
            </article>



            <!-- Bab 7: Ekspansi Struktur -->
            <article class="story-block">
                <div class="story-dot"></div>
                <span class="story-date">19 November 2025</span>
                <h2 class="story-title">Internal Expansion: Memperkuat Barisan Administratif</h2>
                <div class="story-content">
                    Guna mengimbangi beban kerja administratif yang semakin besar seiring meningkatnya aktivitas kelas, pada <span class="highlight-text">19 November 2025</span>, dilakukan penguatan struktur organisasi. Personil baru ditambahkan ke dalam jajaran inti kepengurusan. 
                    <br><br>
                    <span class="highlight-text">Alfira Resky Pratiwi</span> resmi bergabung untuk memperkuat tim Sekretaris, sementara <span class="highlight-text">Adelia Rahmadani Tahir</span> bergabung untuk mendukung transparansi finansial di tim Bendahara. Penambahan ini bukan sekadar mengisi kekosongan, melainkan langkah strategis untuk memastikan setiap administrasi dan keuangan kelas terarsip dengan sempurna.
                </div>
            </article>

            <!-- Bab 8: Web Sistem Informasi -->
            <article class="story-block">
                <div class="story-dot"></div>
                <span class="story-date">07 Januari 2026</span>
                <h2 class="story-title">Revolusi Sistem: Transformasi Menjadi Sistem Informasi Dinamis</h2>
                <div class="story-content">
                    Memasuki gerbang tahun baru, ProTechNoTy merayakan pencapaian teknologi terbesarnya. Pada <span class="highlight-text">7 Januari 2026</span>, era website statis resmi berakhir. Website kelas resmi berpindah dan berevolusi menjadi sebuah **Sistem Informasi Terintegrasi** yang jauh lebih canggih di alamat <a href="https://ptikc24.fwh.is" target="_blank" class="text-indigo-600 font-bold hover:underline">ptikc24.fwh.is</a>. 
                    <br><br>
                    Bukan lagi sekadar halaman pajangan, sistem ini kini mampu mengelola database secara cerdas, mencatat absensi, monitoring tugas secara *real-time*, hingga fitur interaktif lainnya. Sistem ini menjadi tulang punggung aktivitas harian mahasiswa, mempermudah koordinasi, dan meminimalisir kesalahan informasi. Inilah wujud nyata dari kemandirian teknologi yang kita cita-citakan sejak awal.
                </div>
                <div class="mt-6 max-w-2xl">
                    <img src="assets/image/web_baru.png" alt="Web Baru ptik c 24" class="img-actual" onerror="this.style.display='none';">
                </div>
            </article>

            <!-- Bab 9: Manager Tugas -->
            <article class="story-block">
                <div class="story-dot"></div>
                <span class="story-date">21 Februari 2026</span>
                <h2 class="story-title">Manager Tugas: Pengawasan Akademik Berbasis Teknologi</h2>
                <div class="story-content">
                    Milestone terakhir yang tercatat dalam kronik ini terjadi pada <span class="highlight-text">21 Februari 2026</span>. Menyadari pentingnya manajemen waktu bagi mahasiswa teknik, dibentuklah peran baru dalam struktur kepengurusan: <span class="highlight-text">Manager Tugas</span>. 
                    <br><br>
                    Amanah ini diberikan kepada <span class="highlight-text">Rifaldi</span> dan <span class="highlight-text">Mustamin</span>. Sebagai Manager Tugas, mereka bertanggung jawab untuk menginput, memverifikasi, dan memantau progres setiap tugas kuliah melalui web Sistem Informasi. Peran mereka menjadi kunci dalam menjaga tingkat kelulusan mata kuliah tetap tinggi dan meminimalisir adanya tugas yang terlewatkan oleh rekan-rekan kelas.
                </div>
            </article>

            <!-- Bab 10: Kondisi Saat Ini -->
            <article class="story-block">
                <div class="story-dot"></div>
                <span class="story-date">Sekarang</span>
                <h2 class="story-title">Solidaritas 35 Mahasiswa</h2>
                <div class="story-content space-y-6">
                    
                    <!-- Paragraf Mengenai Anggota -->
                    <p>
                        Hingga saat ini, ProTechNoTy kini resmi beranggotakan <span class="highlight-text">35 Mahasiswa</span>. Dalam perjalanannya, kita telah merelakan rekan-rekan seperti <span class="highlight-text">Muhammad Rafly Putratama</span> dan <span class="highlight-text">M. Farhan</span> yang memutuskan untuk tidak melanjutkan kuliah. Meskipun jumlah personil berkurang, ikatan kekeluargaan di antara kita justru semakin kuat dan solid dalam menghadapi setiap tantangan akademik di PTIK C.
                    </p>
                    
                    <!-- Paragraf 1: Struktur Pengurus -->
                    <p>
                        Hingga saat ini, roda organisasi ProTechNoTy Class 24 terus berputar dengan efisien di bawah kepemimpinan <span class="highlight-text">Shofiyah Rosyadah</span> sebagai Ketua Tingkat. Ketertiban administrasi dikelola oleh duo Sekretaris <span class="highlight-text">Adelia Magfira Hapati</span> dan <span class="highlight-text">Alfira Resky Pratiwi</span>, sementara transparansi keuangan dijaga ketat oleh Bendahara <span class="highlight-text">Islatul Adha</span> dan <span class="highlight-text">Adelia Rahmadani Tahir</span>. Eksistensi digital dan visual kelas tetap berada di tangan kreatif <span class="highlight-text">Nur Hidayat</span> dan <span class="highlight-text">Andi Fahril S</span>, sedangkan kedisiplinan akademik melalui monitoring tugas dipercayakan sepenuhnya kepada <span class="highlight-text">Rifaldi</span> dan <span class="highlight-text">Mustamin</span> sebagai Manager Tugas.
                    </p>

                    <!-- Paragraf 2: Mahasiswa (Laki-laki) -->
                    <p>
                        Kekuatan kelas ini juga terpancar dari barisan mahasiswa laki-laki yang berjumlah 17 personil, mereka adalah 
                        <?php 
                        $qLaki = mysqli_query($koneksi, "SELECT nama_lengkap FROM mahasiswa WHERE jenis_kelamin = 'Laki-laki' ORDER BY nama_lengkap ASC");
                        $listL = [];
                        while($r = mysqli_fetch_assoc($qLaki)) $listL[] = "<span class='highlight-text'>".ucwords(strtolower($r['nama_lengkap']))."</span>";
                        $lastL = array_pop($listL);
                        echo implode(", ", $listL) . ", serta " . $lastL;
                        ?>. 
                        Keseluruhan personil putra ini senantiasa menunjukkan dedikasi yang tinggi dalam setiap diskusi teknis, praktikum, maupun project kolaboratif, memperkuat pondasi teknis di dalam komunitas ProTechNoTy.
                    </p>

                    <!-- Paragraf 3: Mahasiswi (Perempuan) -->
                    <p>
                        Tak kalah krusial, kehadiran 18 mahasiswi memberikan warna ketelitian dan kreativitas yang luar biasa bagi kelas, mereka terdiri dari 
                        <?php 
                        $qPr = mysqli_query($koneksi, "SELECT nama_lengkap FROM mahasiswa WHERE jenis_kelamin = 'Perempuan' ORDER BY nama_lengkap ASC");
                        $listP = [];
                        while($r = mysqli_fetch_assoc($qPr)) $listP[] = "<span class='highlight-text'>".ucwords(strtolower($r['nama_lengkap']))."</span>";
                        $lastP = array_pop($listP);
                        echo implode(", ", $listP) . ", serta " . $lastP;
                        ?>. 
                        Sinergi antara ketekunan akademik and inisiatif kreatif yang dibawa oleh para mahasiswi mahasiswi ini memastikan bahwa setiap langkah kelas tidak hanya fokus pada pencapaian teknis, namun juga harmoni sosial dan estetika organisasi yang matang.
                    </p>

                    <div class="mt-8 max-w-2xl">
                        <img src="storage/gallery/foto_studio_1771304290_0.jpg" alt="Kondisi Sekarang ptik c 24" class="img-actual" onerror="this.style.display='none';">
                    </div>

                </div>
            </article>
        </div>

        <div class="text-center opacity-30 dark:opacity-20 mt-20">
            <img src="assets/image/icon.webp" class="h-12 w-12 mx-auto grayscale invert dark:invert-0 mb-4 opacity-50">
            <p class="text-[9px] font-bold tracking-[0.3em] uppercase dark:text-slate-400 transition-colors">ProTechNoTy Class 24 • Since 2024</p>
        </div>

    </main>

    <?php include 'includes/footer_main.php'; ?>
    <?php include 'includes/script_main.php'; ?>

    <script>
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('opacity-100', 'translate-y-0');
                    entry.target.classList.remove('opacity-0', 'translate-y-8');
                }
            });
        }, { threshold: 0.1 });

        document.querySelectorAll('.story-block').forEach((el) => {
            el.classList.add('transition-all', 'duration-700', 'opacity-0', 'translate-y-8');
            observer.observe(el);
        });
    </script>
</body>

</html>
