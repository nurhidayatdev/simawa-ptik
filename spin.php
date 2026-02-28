<?php
include 'koneksi.php';

// Ambil data mahasiswa
$q_mhs = mysqli_query($koneksi, "SELECT id, nim, nama_lengkap, nama_panggilan, jenis_kelamin, foto FROM mahasiswa ORDER BY nama_lengkap ASC");
$mahasiswa = [];
while ($row = mysqli_fetch_assoc($q_mhs)) {
    $mahasiswa[] = $row;
}

// Ambil data kelompok (periode aktif saja)
$q_periode = mysqli_query($koneksi, "SELECT id FROM periode WHERE status = 'aktif' LIMIT 1");
$periode = mysqli_fetch_assoc($q_periode);
$periode_id = $periode['id'] ?? 0;

$q_kelompok = mysqli_query($koneksi, "
    SELECT k.kelompok_no, k.anggota, mk.nama_matkul, k.dosen, k.keterangan 
    FROM kelompok k 
    JOIN mata_kuliah mk ON k.matkul_id = mk.id 
    WHERE k.periode_id = $periode_id
    ORDER BY mk.nama_matkul, k.dosen, k.kelompok_no
");

$kelompok_data = [];
while ($row = mysqli_fetch_assoc($q_kelompok)) {
    $key = $row['nama_matkul'] . " - " . $row['dosen'] . ($row['keterangan'] ? " (" . $row['keterangan'] . ")" : "");
    if (!isset($kelompok_data[$key])) {
        $kelompok_data[$key] = [];
    }
    $kelompok_data[$key][] = $row;
}

// Include foto helpers
include_once 'functions/foto_helpers.php';
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <title>Random Picker - PTIK C 2024</title>
    <?php include 'includes/head.php'; ?>
    <style>
        .picker-container {
            background: rgba(255, 255, 255, 0.7);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.3);
        }

        .spin-card {
            transition: all 0.5s cubic-bezier(0.175, 0.885, 0.32, 1.275);
        }

        .spin-active {
            animation: shuffle 0.1s infinite;
        }

        @keyframes shuffle {
            0% {
                transform: translateY(0);
            }

            50% {
                transform: translateY(-5px);
            }

            100% {
                transform: translateY(5px);
            }
        }

        .result-glow {
            box-shadow: 0 0 20px rgba(79, 70, 229, 0.4);
            animation: glow 1.5s infinite alternate;
        }

        @keyframes glow {
            from {
                box-shadow: 0 0 10px rgba(79, 70, 229, 0.2);
            }

            to {
                box-shadow: 0 0 30px rgba(79, 70, 229, 0.6);
            }
        }

        .confetti {
            position: absolute;
            width: 10px;
            height: 10px;
            background-color: #f00;
            border-radius: 50%;
        }
    </style>
</head>

<body class="bg-slate-50 min-h-screen flex flex-col">

    <div class="container mx-auto px-4 py-8 flex-grow flex items-center justify-center">
        <div class="max-w-6xl mx-auto w-full grid lg:grid-cols-10 gap-8 items-stretch">

            <!-- Kolom Kiri: Roda (70%) -->
            <div class="order-2 lg:order-1 lg:col-span-7 flex justify-center">
                <div onclick="startSpin()" class="relative picker-container rounded-[3rem] p-6 w-full max-w-[650px] flex flex-col items-center justify-center shadow-2xl border-2 border-indigo-100 bg-gradient-to-br from-white to-indigo-50/30 overflow-hidden cursor-pointer group h-full">
                    <!-- Pointer -->
                    <div class="absolute top-12 left-1/2 -translate-x-1/2 z-20">
                        <div class="w-10 h-10 bg-indigo-600 shadow-lg border-4 border-white rotate-45 rounded-sm"></div>
                    </div>

                    <canvas id="wheelCanvas" width="500" height="500" class="max-w-full h-auto drop-shadow-2xl transition-transform duration-[4000ms] cubic-bezier(0.1, 0, 0, 1)"></canvas>

                    <!-- Center Circle Overlay (Static) -->
                    <div id="center-circle" class="absolute left-1/2 top-1/2 -translate-x-1/2 -translate-y-1/2 w-16 h-16 bg-white rounded-full shadow-inner border-4 border-indigo-100 flex items-center justify-center z-10 group-hover:scale-110 transition-transform">
                        <div id="countdown-display" class="w-8 h-8 bg-indigo-600 rounded-full animate-pulse shadow-indigo-200 shadow-lg flex items-center justify-center text-white text-[10px] font-bold"></div>
                    </div>

                    <!-- Click Hint -->
                    <div class="absolute bottom-6 left-1/2 -translate-x-1/2 text-[10px] font-bold text-indigo-400 uppercase tracking-widest opacity-60 group-hover:opacity-100 transition-opacity whitespace-nowrap">
                        KLIK RODA UNTUK MEMUTAR
                    </div>
                </div>
            </div>

            <!-- Kolom Kanan: Judul & Pengaturan (30%) -->
            <div class="order-1 lg:order-2 lg:col-span-3">
                <div class="bg-white/80 backdrop-blur-sm p-8 rounded-[2.5rem] shadow-xl shadow-indigo-100/50 border border-white h-full flex flex-col">
                    <div class="mb-10 text-center lg:text-left">
                        <h1 class="text-3xl lg:text-4xl font-extrabold text-indigo-950 mb-3 tracking-tight">Random Picker</h1>
                        <p class="text-slate-500 text-sm">Pilih mahasiswa atau kelompok secara acak dalam satu putaran roda.</p>
                    </div>

                    <!-- Mode Selector -->
                    <div class="flex p-1.5 bg-slate-200/60 rounded-2xl mb-6">
                        <button onclick="setMode('mahasiswa')" id="btn-mhs" class="flex-1 py-2.5 text-[11px] font-bold rounded-xl transition-all bg-white text-indigo-600 shadow-sm border border-slate-100">
                            <i class="fas fa-user-graduate mr-2"></i>Mahasiswa
                        </button>
                        <button onclick="setMode('kelompok')" id="btn-klp" class="flex-1 py-2.5 text-[11px] font-bold rounded-xl transition-all text-slate-600 hover:text-slate-900 border border-transparent">
                            <i class="fas fa-users mr-2"></i>Kelompok
                        </button>
                    </div>

                    <!-- Filters -->
                    <div id="filter-mahasiswa" class="picker-container p-4 rounded-2xl mb-6 shadow-sm border border-slate-100">
                        <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-4">Filter Jenis Kelamin</label>
                        <div class="grid grid-cols-4 gap-2">
                            <label class="cursor-pointer">
                                <input type="radio" name="gender" value="all" checked class="hidden peer">
                                <div class="text-center py-2.5 px-1 rounded-xl border border-slate-100 peer-checked:bg-indigo-600 peer-checked:text-white peer-checked:border-indigo-600 transition-all text-[10px] font-semibold">
                                    Semua
                                </div>
                            </label>
                            <label class="cursor-pointer">
                                <input type="radio" name="gender" value="Laki-laki" class="hidden peer">
                                <div class="text-center py-2.5 px-1 rounded-xl border border-slate-100 peer-checked:bg-sky-500 peer-checked:text-white peer-checked:border-sky-500 transition-all text-[10px] font-semibold">
                                    Laki
                                </div>
                            </label>
                            <label class="cursor-pointer">
                                <input type="radio" name="gender" value="Perempuan" class="hidden peer">
                                <div class="text-center py-2.5 px-1 rounded-xl border border-slate-100 peer-checked:bg-pink-500 peer-checked:text-white peer-checked:border-pink-500 transition-all text-[10px] font-semibold">
                                    Cewe
                                </div>
                            </label>
                            <label class="cursor-pointer">
                                <input type="radio" name="gender" value="custom" class="hidden peer">
                                <div class="text-center py-2.5 px-1 rounded-xl border border-slate-100 peer-checked:bg-amber-500 peer-checked:text-white peer-checked:border-amber-500 transition-all text-[10px] font-semibold">
                                    Pilih
                                </div>
                            </label>
                        </div>

                        <!-- Custom Selection Trigger -->
                        <div id="custom-selection-trigger" class="mt-4 hidden animate-in fade-in slide-in-from-top-2">
                            <button onclick="openCustomSelection()" class="w-full py-2 px-4 rounded-xl border-2 border-dashed border-amber-200 text-amber-700 bg-amber-50 hover:bg-amber-100 transition-all text-[10px] font-bold flex items-center justify-between">
                                <span><i class="fas fa-list-check mr-2"></i>PILIH MAHASISWA</span>
                                <span id="custom-count-badge" class="bg-amber-500 text-white px-2 py-0.5 rounded-full text-[9px]">0</span>
                            </button>
                        </div>
                    </div>

                    <div id="filter-kelompok" class="picker-container p-4 rounded-2xl mb-6 shadow-sm border border-slate-100 hidden">
                        <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-2">Pilih Mata Kuliah / Set</label>
                        <div class="relative">
                            <select id="select-kelompok" class="w-full p-3 pr-8 rounded-xl border border-slate-100 bg-white text-[11px] font-medium focus:ring-2 focus:ring-indigo-500 outline-none appearance-none cursor-pointer">
                                <option value="">-- Pilih Set --</option>
                                <?php foreach ($kelompok_data as $setName => $groups): ?>
                                    <option value="<?= htmlspecialchars($setName) ?>"><?= htmlspecialchars($setName) ?></option>
                                <?php endforeach; ?>
                            </select>
                            <div class="absolute right-3 top-1/2 -translate-y-1/2 pointer-events-none text-slate-400 text-[10px]">
                                <i class="fas fa-chevron-down"></i>
                            </div>
                        </div>
                    </div>

                    <div class="mb-6">
                        <button onclick="shuffleList()" class="w-full py-3 bg-white text-indigo-600 rounded-2xl border border-indigo-100 shadow-sm hover:shadow-md hover:bg-indigo-50 transition-all font-bold text-xs flex items-center justify-center gap-2 group">
                            <i class="fas fa-random group-hover:rotate-180 transition-transform duration-500"></i>
                            Acak Urutan Wheel
                        </button>
                        <p class="mt-4 text-[10px] text-slate-400 italic leading-relaxed text-center lg:text-left">
                            Klik tombol di atas untuk mengacak ulang posisi peserta pada roda tanpa mengubah daftar yang terpilih. Perubahan urutan dapat meningkatkan sensasi kebetulan saat diputar.
                        </p>
                    </div>

                    <!-- Tombol Kembali -->
                    <div class="pt-6 border-t border-slate-100 mt-auto">
                        <a href="dashboard.php" class="flex items-center justify-center gap-3 w-full py-3.5 bg-slate-50 text-slate-600 rounded-2xl border border-slate-200 hover:bg-slate-100 transition-all font-bold text-xs">
                            <i class="fas fa-th-large"></i>
                            Kembali ke Dashboard
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    </div>

    <!-- Custom Selection Modal -->
    <div id="customSelectionModal" class="fixed inset-0 z-[70] hidden items-center justify-center p-4">
        <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm" onclick="closeCustomSelection()"></div>
        <div class="relative bg-white rounded-[2.5rem] w-full max-w-2xl max-h-[80vh] flex flex-col shadow-2xl overflow-hidden scale-95 opacity-0 transition-all duration-300" id="customContent">
            <div class="p-6 border-b border-slate-100 flex items-center justify-between bg-slate-50/50">
                <div>
                    <h2 class="text-xl font-bold text-slate-900">Pilih Mahasiswa</h2>
                    <p class="text-xs text-slate-500">Pilih mahasiswa yang ingin dimasukkan ke dalam roda</p>
                </div>
                <button onclick="closeCustomSelection()" class="w-10 h-10 rounded-full hover:bg-slate-200 flex items-center justify-center transition-colors">
                    <i class="fas fa-times text-slate-400"></i>
                </button>
            </div>

            <div class="flex-grow overflow-y-auto p-6 bg-white">
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3" id="selection-list">
                    <?php foreach ($mahasiswa as $mhs): ?>
                        <label class="flex items-center gap-3 p-3 rounded-2xl border border-slate-100 hover:bg-slate-50 cursor-pointer transition-all group">
                            <input type="checkbox" value="<?= $mhs['id'] ?>" class="mhs-checkbox w-5 h-5 rounded-lg border-2 border-slate-200 text-indigo-600 focus:ring-indigo-500 transition-all cursor-pointer">
                            <div class="flex flex-col">
                                <span class="text-xs font-bold text-slate-700 group-hover:text-indigo-600 transition-colors"><?= htmlspecialchars($mhs['nama_panggilan'] ?: $mhs['nama_lengkap']) ?></span>
                                <span class="text-[10px] text-slate-400 uppercase"><?= htmlspecialchars($mhs['nama_lengkap']) ?></span>
                            </div>
                        </label>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="p-6 border-t border-slate-100 bg-slate-50/50 flex gap-3">
                <button onclick="selectAll(true)" class="flex-grow py-3 px-4 rounded-xl border border-slate-200 bg-white text-xs font-bold hover:bg-slate-100 transition-all">Pilih Semua</button>
                <button onclick="selectAll(false)" class="flex-grow py-3 px-4 rounded-xl border border-slate-200 bg-white text-xs font-bold hover:bg-slate-100 transition-all">Hapus Semua</button>
                <button onclick="closeCustomSelection()" class="flex-[2] py-3 px-4 rounded-xl bg-indigo-600 text-white text-xs font-bold hover:bg-indigo-700 shadow-lg shadow-indigo-100 transition-all">Simpan Pilihan</button>
            </div>
        </div>
    </div>

    <!-- Result Modal (Hidden) -->
    <div id="resultModal" class="fixed inset-0 z-[60] hidden items-center justify-center p-4">
        <div class="fixed inset-0 bg-indigo-950/40 backdrop-blur-md" onclick="closeModal()"></div>
        <div class="relative bg-white rounded-[3rem] p-10 max-w-sm w-full shadow-2xl scale-90 opacity-0 transition-all duration-300" id="resultContent">
            <button onclick="closeModal()" class="absolute top-6 right-6 text-slate-400 hover:text-slate-600">
                <i class="fas fa-times text-xl"></i>
            </button>

            <div class="text-center">
                <div id="result-badge" class="inline-block px-4 py-1.5 rounded-full text-[10px] font-bold uppercase tracking-widest mb-6 border">
                    MAHASISWA TERPILIH
                </div>

                <div id="result-image-container" class="w-32 h-32 mx-auto rounded-3xl overflow-hidden mb-6 border-4 border-white shadow-xl bg-indigo-50 flex items-center justify-center">
                    <!-- Image or Initials -->
                </div>

                <h3 id="result-name" class="text-2xl font-bold text-slate-900 line-clamp-2">NAMA LENGKAP</h3>
                <p id="result-sub" class="text-slate-500 mt-2 mb-8">NIM / KETERANGAN</p>

                <button onclick="closeModal()" class="w-full bg-slate-900 text-white py-4 rounded-2xl font-bold hover:bg-slate-800 transition-all">
                    Tutup
                </button>
            </div>
        </div>
    </div>

    <?php include 'includes/script_main.php'; ?>


    <script src="https://cdn.jsdelivr.net/npm/canvas-confetti@1.6.0/dist/confetti.browser.min.js"></script>

    <script>
        let currentMode = 'mahasiswa';
        let isSpinning = false;
        let currentRotation = 0;

        // State for dynamic lists (so winners can be removed)
        let activeMahasiswa = [];
        let activeKelompok = [];

        const originalMahasiswa = <?= json_encode($mahasiswa) ?>;
        const originalKelompokData = <?= json_encode($kelompok_data) ?>;
        const profilePath = 'storage/profile/';

        const canvas = document.getElementById('wheelCanvas');
        const ctx = canvas.getContext('2d');

        // UI Colors
        const colors = [
            '#4f46e5', '#6366f1', '#818cf8', '#a5b4fc',
            '#4338ca', '#3730a3', '#312e81'
        ];

        function profilePhotoUrl(foto) {
            if (!foto) return '';
            return profilePath + encodeURIComponent(foto) + '?v=' + Date.now();
        }

        let selectedCustomIds = [];

        // Initialize state
        function resetLists() {
            activeMahasiswa = JSON.parse(JSON.stringify(originalMahasiswa));
            activeKelompok = [];
            selectedCustomIds = [];
            updateCheckboxUI();
            updateCustomBadge();
        }
        resetLists();

        function getFilteredList() {
            if (currentMode === 'mahasiswa') {
                const gender = document.querySelector('input[name="gender"]:checked').value;
                if (gender === 'all') return activeMahasiswa;
                if (gender === 'custom') {
                    return activeMahasiswa.filter(m => selectedCustomIds.includes(String(m.id)));
                }
                return activeMahasiswa.filter(m => m.jenis_kelamin === gender);
            } else {
                const selectedSet = document.getElementById('select-kelompok').value;
                if (!selectedSet) return [];

                // If nested list for this set doesn't exist in active state, init it
                if (!activeKelompok[selectedSet]) {
                    activeKelompok[selectedSet] = JSON.parse(JSON.stringify(originalKelompokData[selectedSet])).map(g => ({
                        nama_lengkap: 'Kelompok ' + g.kelompok_no,
                        nama_panggilan: 'Klp ' + g.kelompok_no, // Label in the wheel
                        keterangan: g.nama_matkul + ' - ' + g.dosen,
                        anggota: g.anggota,
                        id: g.kelompok_no
                    }));
                }
                return activeKelompok[selectedSet];
            }
        }

        function drawWheel() {
            // Fix "Burik" visual by handling Device Pixel Ratio (Retina/HIDPI)
            const dpr = window.devicePixelRatio || 1;
            const rect = canvas.getBoundingClientRect();

            // Set drawing buffer size
            canvas.width = rect.width * dpr;
            canvas.height = rect.height * dpr;

            // Scale context to match CSS size
            ctx.scale(dpr, dpr);

            const list = getFilteredList();
            const numSegments = list.length || 1;
            const centerX = rect.width / 2;
            const centerY = rect.height / 2;
            const radius = (Math.min(rect.width, rect.height) / 2) * 0.72;
            const arcSize = (2 * Math.PI) / numSegments;

            ctx.clearRect(0, 0, rect.width, rect.height);

            if (list.length === 0) {
                ctx.beginPath();
                ctx.arc(centerX, centerY, radius, 0, 2 * Math.PI);
                ctx.fillStyle = '#f8fafc';
                ctx.fill();
                ctx.strokeStyle = '#e2e8f0';
                ctx.lineWidth = 2;
                ctx.stroke();

                ctx.fillStyle = '#94a3b8';
                ctx.font = 'bold 16px Poppins';
                ctx.textAlign = 'center';
                ctx.fillText('Data Kosong / Selesai', centerX, centerY);
                return;
            }

            for (let i = 0; i < numSegments; i++) {
                const angle = i * arcSize;

                // Draw Segment
                ctx.beginPath();
                ctx.moveTo(centerX, centerY);
                ctx.arc(centerX, centerY, radius, angle, angle + arcSize);
                ctx.fillStyle = colors[i % colors.length];
                ctx.fill();
                ctx.strokeStyle = 'rgba(255,255,255,0.4)';
                ctx.lineWidth = 1.5;
                ctx.stroke();

                // Draw Text
                ctx.save();
                ctx.translate(centerX, centerY);
                ctx.rotate(angle + arcSize / 2);
                ctx.textAlign = 'right';
                ctx.fillStyle = '#ffffff';

                // Responsive font size (DIPERBESAR MENTOK)
                let fontSize = 20;
                if (list.length <= 3) fontSize = 45;
                else if (list.length <= 6) fontSize = 32;
                else if (list.length <= 12) fontSize = 22;
                else if (list.length <= 25) fontSize = 16;
                else fontSize = 12;

                ctx.font = `bold ${fontSize}px Poppins`;

                // Gunakan nama_panggilan jika ada (di kelompok, nama_panggilan = 'Klp X')
                let text = list[i].nama_panggilan || list[i].nama_lengkap;

                // Batas karakter lebih leluasa agar tidak "Kelomp.."
                const maxChars = fontSize > 40 ? 10 : 18;
                if (text.length > maxChars) text = text.substring(0, maxChars - 2) + '..';
                ctx.fillText(text, radius - 15, 5);
                ctx.restore();
            }
        }

        function setMode(mode) {
            if (isSpinning) return;
            currentMode = mode;

            const btnMhs = document.getElementById('btn-mhs');
            const btnKlp = document.getElementById('btn-klp');
            const filterMhs = document.getElementById('filter-mahasiswa');
            const filterKlp = document.getElementById('filter-kelompok');

            if (mode === 'mahasiswa') {
                btnMhs.className = "flex-1 py-2.5 text-[11px] font-bold rounded-xl transition-all bg-white text-indigo-600 shadow-sm border border-slate-100";
                btnKlp.className = "flex-1 py-2.5 text-[11px] font-bold rounded-xl transition-all text-slate-600 hover:text-slate-900 border border-transparent";
                filterMhs.classList.remove('hidden');
                filterKlp.classList.add('hidden');
            } else {
                btnKlp.className = "flex-1 py-2.5 text-[11px] font-bold rounded-xl transition-all bg-white text-indigo-600 shadow-sm border border-slate-100";
                btnMhs.className = "flex-1 py-2.5 text-[11px] font-bold rounded-xl transition-all text-slate-600 hover:text-slate-900 border border-transparent";
                filterMhs.classList.add('hidden');
                filterKlp.classList.remove('hidden');
            }
            drawWheel();
        }

        function removeWinner(winner) {
            if (currentMode === 'mahasiswa') {
                activeMahasiswa = activeMahasiswa.filter(m => m.nama_lengkap !== winner.nama_lengkap);
            } else {
                const selectedSet = document.getElementById('select-kelompok').value;
                if (selectedSet && activeKelompok[selectedSet]) {
                    activeKelompok[selectedSet] = activeKelompok[selectedSet].filter(k => k.nama_lengkap !== winner.nama_lengkap);
                }
            }
            drawWheel();
        }

        function startSpin() {
            if (isSpinning) return;

            const list = getFilteredList();
            if (list.length === 0) {
                alert('Tidak ada data tersisa atau belum dipilih!');
                return;
            }

            isSpinning = true;

            const numSegments = list.length;
            const arcSize = 360 / numSegments;
            const winnerIdx = Math.floor(Math.random() * numSegments);
            const winnerData = list[winnerIdx];

            const centerAngleOfWinner = (winnerIdx + 0.5) * arcSize;
            const targetPos = 270 - centerAngleOfWinner;

            const extraSpins = (6 + Math.floor(Math.random() * 4)) * 360;
            const finalRotation = currentRotation + extraSpins + (targetPos - (currentRotation % 360));

            currentRotation = finalRotation;
            canvas.style.transform = `rotate(${currentRotation}deg)`;

            // Countdown Logic
            const display = document.getElementById('countdown-display');
            let timeLeft = 4;
            display.innerText = timeLeft;
            display.classList.remove('animate-pulse');

            const timer = setInterval(() => {
                timeLeft--;
                if (timeLeft > 0) {
                    display.innerText = timeLeft;
                } else {
                    clearInterval(timer);
                    display.innerText = '';
                    display.classList.add('animate-pulse');
                }
            }, 1000);

            setTimeout(() => {
                showResult(winnerData);

                // REMOVE WINNER from logic
                removeWinner(winnerData);

                isSpinning = false;
            }, 4800); // Bertambah dari 4100ms untuk memberikan jeda dramatis 0.8 detik
        }

        function showResult(winner) {
            const modal = document.getElementById('resultModal');
            modal.classList.remove('hidden');
            modal.classList.add('flex');
            const content = document.getElementById('resultContent');
            const badge = document.getElementById('result-badge');
            const name = document.getElementById('result-name');
            const sub = document.getElementById('result-sub');
            const imgContainer = document.getElementById('result-image-container');

            name.textContent = winner.nama_lengkap;

            if (currentMode === 'mahasiswa') {
                badge.textContent = 'MAHASISWA TERPILIH';
                badge.className = 'inline-block px-4 py-1.5 rounded-full text-[10px] font-bold uppercase tracking-widest mb-6 border ' +
                    (winner.jenis_kelamin === 'Laki-laki' ? 'bg-sky-50 text-sky-600 border-sky-100' : 'bg-pink-50 text-pink-600 border-pink-100');
                sub.textContent = winner.nama_panggilan || 'Mahasiswa PTIK C 2024';
                imgContainer.classList.remove('hidden');

                if (winner.foto) {
                    imgContainer.innerHTML = `<img src="${profilePhotoUrl(winner.foto)}" class="w-full h-full object-cover">`;
                } else {
                    const initials = winner.nama_lengkap.split(' ').map(n => n[0]).join('').substring(0, 2).toUpperCase();
                    imgContainer.innerHTML = `<span class="text-3xl font-bold text-indigo-300">${initials}</span>`;
                }
            } else {
                badge.textContent = 'KELOMPOK TERPILIH';
                badge.className = 'inline-block px-4 py-1.5 rounded-full text-[10px] font-bold uppercase tracking-widest mb-6 border bg-amber-50 text-amber-600 border-amber-100';
                sub.textContent = ''; // Matkul dan Dosen dihilangkan
                imgContainer.classList.add('hidden');

                // Helper untuk menerjemahkan NIM ke Nama Lengkap jika perlu
                const resolveMemberName = (m) => {
                    const memberId = m.trim();
                    const mhs = originalMahasiswa.find(item => item.nim === memberId || item.id == memberId || item.nama_lengkap === memberId);
                    return mhs ? mhs.nama_lengkap : memberId;
                };

                // Menampilkan daftar anggota
                if (winner.anggota) {
                    const membersDiv = document.createElement('div');
                    membersDiv.className = 'mt-5 p-5 bg-slate-50 rounded-2xl border border-slate-100 text-left';
                    membersDiv.innerHTML = `
                        <div class="text-[9px] font-bold text-slate-400 uppercase tracking-widest mb-3">Anggota Kelompok</div>
                        <div class="space-y-2">
                            ${winner.anggota.split(',').map((m, i) => `
                                <div class="flex items-center gap-2 text-[11px] text-slate-700 font-medium">
                                    <span class="w-4 h-4 rounded-full bg-white border border-slate-200 flex items-center justify-center text-[8px] text-slate-400 font-bold">${i + 1}</span>
                                    ${resolveMemberName(m)}
                                </div>
                            `).join('')}
                        </div>
                    `;
                    sub.after(membersDiv);
                }
            }

            modal.classList.remove('hidden');
            setTimeout(() => {
                content.classList.remove('scale-90', 'opacity-0');
                content.classList.add('scale-100', 'opacity-100');

                confetti({
                    particleCount: 150,
                    spread: 70,
                    origin: {
                        y: 0.6
                    },
                    colors: ['#4f46e5', '#818cf8', '#c7d2fe', '#f472b6']
                });
            }, 10);
        }

        function closeModal() {
            const modal = document.getElementById('resultModal');
            const content = document.getElementById('resultContent');
            content.classList.add('scale-90', 'opacity-0');
            content.classList.remove('scale-100', 'opacity-100');

            // Clean up appended members list
            const extraList = content.querySelector('.bg-slate-50');
            if (extraList) extraList.remove();

            setTimeout(() => {
                modal.classList.add('hidden');
                modal.classList.remove('flex');
            }, 300);
        }

        // Shuffle Function
        function shuffleList() {
            if (isSpinning) return;

            if (currentMode === 'mahasiswa') {
                // Fisher-Yates Shuffle for activeMahasiswa
                for (let i = activeMahasiswa.length - 1; i > 0; i--) {
                    const j = Math.floor(Math.random() * (i + 1));
                    [activeMahasiswa[i], activeMahasiswa[j]] = [activeMahasiswa[j], activeMahasiswa[i]];
                }
            } else {
                const selectedSet = document.getElementById('select-kelompok').value;
                if (selectedSet && activeKelompok[selectedSet]) {
                    // Fisher-Yates Shuffle for activeKelompok
                    for (let i = activeKelompok[selectedSet].length - 1; i > 0; i--) {
                        const j = Math.floor(Math.random() * (i + 1));
                        [activeKelompok[selectedSet][i], activeKelompok[selectedSet][j]] = [activeKelompok[selectedSet][j], activeKelompok[selectedSet][i]];
                    }
                } else {
                    alert('Pilih set kelompok terlebih dahulu!');
                    return;
                }
            }

            // Visual feedback on the wheel
            drawWheel();

            // Tiny animation to indicate shuffle happened
            canvas.classList.add('scale-95', 'opacity-50');
            setTimeout(() => {
                canvas.classList.remove('scale-95', 'opacity-50');
            }, 200);
        }

        // Custom Selection Helpers
        function openCustomSelection() {
            const modal = document.getElementById('customSelectionModal');
            const content = document.getElementById('customContent');
            modal.classList.remove('hidden');
            modal.classList.add('flex');
            setTimeout(() => {
                content.classList.remove('scale-95', 'opacity-0');
                content.classList.add('scale-100', 'opacity-100');
            }, 10);
        }

        function closeCustomSelection() {
            const modal = document.getElementById('customSelectionModal');
            const content = document.getElementById('customContent');
            content.classList.add('scale-95', 'opacity-0');
            content.classList.remove('scale-100', 'opacity-100');
            setTimeout(() => {
                modal.classList.add('hidden');
                modal.classList.remove('flex');
                drawWheel();
            }, 300);
        }

        function selectAll(checked) {
            const checkboxes = document.querySelectorAll('.mhs-checkbox');
            checkboxes.forEach(cb => cb.checked = checked);
            updateSelectedFromUI();
        }

        function updateSelectedFromUI() {
            const checkboxes = document.querySelectorAll('.mhs-checkbox:checked');
            selectedCustomIds = Array.from(checkboxes).map(cb => cb.value);
            updateCustomBadge();
        }

        function updateCheckboxUI() {
            document.querySelectorAll('.mhs-checkbox').forEach(cb => {
                cb.checked = selectedCustomIds.includes(cb.value);
            });
        }

        function updateCustomBadge() {
            const badge = document.getElementById('custom-count-badge');
            if (badge) badge.innerText = selectedCustomIds.length;
        }

        // Listen for gender change including custom
        document.querySelectorAll('input[name="gender"]').forEach(radio => {
            radio.addEventListener('change', (e) => {
                const trigger = document.getElementById('custom-selection-trigger');
                if (e.target.value === 'custom') {
                    trigger.classList.remove('hidden');
                } else {
                    trigger.classList.add('hidden');
                }
                drawWheel();
            });
        });

        document.querySelectorAll('.mhs-checkbox').forEach(cb => {
            cb.addEventListener('change', updateSelectedFromUI);
        });

        document.getElementById('select-kelompok').addEventListener('change', drawWheel);

        window.addEventListener('load', drawWheel);
        window.addEventListener('resize', drawWheel);
    </script>
    <?php include 'includes/footer_db.php'; ?>
    <div class="mt-5"></div>
</body>

</html>