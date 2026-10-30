# 🎨 SIMAWA PTIK — DESIGN SYSTEM & GUIDELINE DOKUMENTASI

> **Versi Dokumen:** 1.0  
> **Terakhir Diperbarui:** 2026-10-30  
> **Target Aplikasi:** SIMAWA PTIK 2024 (Sistem Informasi Mahasiswa PTIK — ProTechNoTy Class)  
> **Tech Stack:** PHP (Native/Procedural), Tailwind CSS (CDN), FontAwesome 6, SweetAlert2, MySQLi

Dokumen ini adalah **pedoman resmi (single source of truth)** untuk setiap pembuatan halaman baru, perbaikan bug UI, refaktor komponen, maupun penambahan fitur di repositori **SIMAWA PTIK**.

---

## 📑 DAFTAR ISI
1. [Prinsip Desain & Identitas Sistem](#1-prinsip-desain--identitas-sistem)
2. [Color Palette & Dark Mode](#2-color-palette--dark-mode)
3. [Tipografi & Ikonografi](#3-tipografi--ikonografi)
4. [Arsitektur Layout & Struktur Halaman](#4-arsitektur-layout--struktur-halaman)
   - [Public Page (Frontend/Landing)](#41-public-page-frontendlanding)
   - [Back-Office / Dashboard & Management Page](#42-back-office--dashboard--management-page)
   - [Action / CRUD Form Page](#43-action--crud-form-page)
5. [Standar Komponen UI (Tailwind Boilerplate)](#5-standar-komponen-ui-tailwind-boilerplate)
   - [Container & Kartu (Cards)](#51-container--kartu-cards)
   - [Statistik & Metric Cards](#52-statistik--metric-cards)
   - [Tabel Data (Data Tables)](#53-tabel-data-data-tables)
   - [Badges & Tag Status](#54-badges--tag-status)
   - [Formulir & Input Controls](#55-formulir--input-controls)
   - [Tombol (Buttons & Actions)](#56-tombol-buttons--actions)
   - [Modal Dialog](#57-modal-dialog)
   - [Notifikasi & SweetAlert2](#58-notifikasi--sweetalert2)
6. [Aturan Akses Berbasis Role (RBAC)](#6-aturan-akses-berbasis-role-rbac)
7. [Aturan Path, URL Base, dan Aset](#7-aturan-path-url-base-dan-aset)
8. [DOs and DON'Ts (Aturan Wajib Pengembang)](#8-dos-and-donts-aturan-wajib-pengembang)

---

## 1. PRINSIP DESAIN & IDENTITAS SISTEM

1. **Modern, Clean, & High-Contrast**: Mengadopsi style modern SaaS dengan sudut membulat rapi (`rounded-xl` hingga `rounded-2xl`), border tipis berdefinisi tinggi, dan kontras warna yang ramah mata baik dalam mode terang maupun gelap.
2. **Dual-Theme Native (Light & Dark)**: Setiap elemen **wajib** menyertakan varian `dark:`, tanpa pengecualian. Tema dikontrol oleh class `dark` pada tag `<html>` yang tersinkronisasi via `localStorage.getItem('theme')`.
3. **Identitas Resmi**:
   - Nama Sistem: **SIMAWA PTIK 2024** / **SIMAWA PTIK**
   - Sub-identitas: **ProTechNoTy Class**
   - Afiliasi: **Pendidikan Teknik Informatika dan Komputer — Universitas Negeri Makassar**
   - *Catatan:* Dilarang keras memakai nama lama seperti "PTIK C" atau path usang seperti `/ptikc24/`.

---

## 2. COLOR PALETTE & DARK MODE

SIMAWA PTIK menggunakan palet warna standar Tailwind dengan dominan **Indigo** sebagai warna brand utama dan **Slate** sebagai warna netral.

### 2.1 Brand & Neutral Palette
| Kategori | Light Mode Class | Dark Mode Class | Hex / Nilai | Penggunaan |
| :--- | :--- | :--- | :--- | :--- |
| **Brand Primary** | `bg-indigo-600` / `text-indigo-600` | `bg-indigo-500` / `text-indigo-400` | `#4f46e5` | Tombol utama, link aktif, focus ring |
| **Brand Dark** | `bg-indigo-900` | `bg-slate-800` | `#312e81` | Header tabel, hero accent |
| **Brand Deep** | `bg-indigo-950` | `bg-slate-950` | `#1e1b4b` | Navbar public header, sidebar gelap |
| **Brand Hover** | `hover:bg-indigo-700` | `hover:bg-indigo-600` | `#4338ca` | State hover tombol primary |
| **Page Background** | `bg-slate-100` | `dark:bg-slate-950` | `#f1f5f9` / `#020617` | Latar belakang seluruh halaman |
| **Card / Surface** | `bg-white` | `dark:bg-slate-900` | `#ffffff` / `#0f172a` | Container kartu, dropdown, modal |
| **Subtle Surface** | `bg-slate-50` | `dark:bg-slate-800/50` | `#f8fafc` / `#1e293b` | Form input, zebra striping tabel |
| **Border Line** | `border-slate-200` | `dark:border-slate-800` | `#e2e8f0` / `#1e293b` | Garis pemisah, border card/input |
| **Text Primary** | `text-slate-800` / `text-slate-900` | `dark:text-white` | `#1e293b` / `#ffffff` | Judul, label penting, data tabel |
| **Text Muted** | `text-slate-500` / `text-slate-600` | `dark:text-slate-400` | `#64748b` / `#94a3b8` | Subtitle, placeholder, keterangan |

### 2.2 Semantic & Status Palette
| Status | Badge / Background | Border | Teks | Contoh Kasus |
| :--- | :--- | :--- | :--- | :--- |
| **Success** | `bg-emerald-100 dark:bg-emerald-950/60` | `border-emerald-200 dark:border-emerald-800/50` | `text-emerald-700 dark:text-emerald-400` | Lunas, Hadir, Aktif, Selesai |
| **Danger / Error** | `bg-rose-100 dark:bg-rose-950/60` | `border-rose-200 dark:border-rose-800/50` | `text-rose-700 dark:text-rose-400` | Belum Bayar, Alpha, Ditolak, Hapus |
| **Warning** | `bg-amber-100 dark:bg-amber-950/60` | `border-amber-200 dark:border-amber-800/50` | `text-amber-700 dark:text-amber-400` | Izin, Pending, Belum Dinilai, Edit |
| **Info / Accent** | `bg-cyan-100 dark:bg-cyan-950/60` | `border-cyan-200 dark:border-cyan-800/50` | `text-cyan-700 dark:text-cyan-400` | Sakit, Materi, File Download, Info |

---

## 3. TIPOGRAFI & IKONOGRAFI

### 3.1 Font Family
- Font utama: **Poppins** (`font-sans`), diambil dari Google Fonts via `includes/head.php`:
  ```html
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
  ```

### 3.2 Skala Tipografi
- **H1 (Page Title)**: `text-2xl md:text-3xl font-extrabold text-slate-800 dark:text-white tracking-tight`
- **H2 (Section Title)**: `text-xl md:text-2xl font-bold text-slate-800 dark:text-white`
- **H3 (Card / Subheader)**: `text-lg font-bold text-slate-800 dark:text-white`
- **Micro-Label / Section Kicker**: `text-[10px] md:text-xs font-bold uppercase tracking-widest text-indigo-600 dark:text-indigo-400`
- **Body Text**: `text-sm text-slate-600 dark:text-slate-300 leading-relaxed`
- **Small / Helper Text**: `text-xs text-slate-400 dark:text-slate-500`

### 3.3 Ikonografi
- Gunakan **FontAwesome 6 Free** (`<i class="fa-solid fa-..."></i>` atau `<i class="fa fa-..."></i>`).
- Aturan kombinasi ikon:
  - Sertakan `mr-2` atau `gap-2` saat berdampingan dengan label teks.
  - Untuk tombol aksi tabel, gunakan ukuran proporsional `text-xs` atau `text-sm`.
  - Gunakan ikon standar:
    - Edit: `fa-pen-to-square` / `fa-pencil`
    - Hapus: `fa-trash` / `fa-trash-can`
    - Tambah: `fa-plus`
    - Simpan: `fa-floppy-disk` / `fa-save`
    - Kembali: `fa-arrow-left`
    - Unduh: `fa-download`
    - Filter / Cari: `fa-filter` / `fa-magnifying-glass`

---

## 4. ARSITEKTUR LAYOUT & STRUKTUR HALAMAN

Aplikasi terbagi menjadi **3 tipe arsitektur layout utama**. Setiap tipe memiliki aturan template yang ketat.

---

### 4.1 Public Page (Frontend/Landing)
Digunakan untuk halaman publik yang dapat diakses sebelum login (`index.php`, `jadwal.php`, `kalender.php`, `matakuliah.php`, `tugas.php`, `tentang.php`, `panduan.php`, `download.php`).

```php
<?php
// Letak: root (contoh: jadwal.php)
include 'koneksi.php';
$title = "Jadwal Perkuliahan - SIMAWA PTIK";
include 'includes/head.php';
?>

<body class="bg-slate-100 dark:bg-slate-950 text-slate-800 dark:text-slate-200 font-sans flex flex-col min-h-screen transition-colors duration-300">

  <!-- Header / Navbar Utama -->
  <?php include 'includes/header_main.php'; ?>

  <!-- Spacer agar konten tidak tertutup fixed navbar -->
  <div class="h-10 md:h-14"></div>

  <!-- Main Content Wrapper -->
  <main class="flex-grow container mx-auto px-4 md:px-6 py-8 md:py-12">
    <!-- Header Halaman -->
    <div class="mb-8 text-center max-w-2xl mx-auto">
      <span class="text-xs font-bold uppercase tracking-widest text-indigo-600 dark:text-indigo-400">Akademik</span>
      <h1 class="text-3xl font-extrabold text-slate-800 dark:text-white mt-1">Jadwal Perkuliahan</h1>
      <p class="text-sm text-slate-500 dark:text-slate-400 mt-2">Daftar jadwal perkuliahan aktif untuk mahasiswa SIMAWA PTIK.</p>
    </div>

    <!-- Konten Halaman (Card / Grid / Tabel) -->
    <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 p-6 shadow-sm">
      <!-- Isi spesifik di sini -->
    </div>
  </main>

  <!-- Footer Publik -->
  <?php include 'includes/footer_main.php'; ?>

  <!-- Script Publik (Dark Mode Toggle & Mobile Menu) -->
  <?php include 'includes/script_main.php'; ?>
</body>
</html>
```

---

### 4.2 Back-Office / Dashboard & Management Page
Digunakan untuk halaman aplikasi internal (direktori `database/*.php`, `views/*.php`, `dashboard.php`).

```php
<?php
session_start();
// Sesuaikan kedalaman include ke koneksi.php
include '../koneksi.php'; 

// Proteksi Auth Sederhana
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit;
}

$title = "Kelola Mahasiswa - SIMAWA PTIK";
include '../includes/head.php';
?>

<body class="bg-slate-100 dark:bg-slate-950 text-slate-800 dark:text-slate-200 font-sans antialiased min-h-screen flex flex-col transition-colors duration-300">

  <!-- Navigasi Shell (Top Bar + Fixed Sidebar w-48) -->
  <?php include '../includes/menu_db.php'; ?>

  <!-- Main Shell (Offset Margin Left ml-0 lg:ml-48, pt-20 untuk topbar) -->
  <main class="ml-0 lg:ml-48 pt-20 p-4 md:p-6 flex flex-col min-h-screen">
    <div class="bg-white dark:bg-slate-900 rounded-xl md:rounded-2xl border border-slate-200 dark:border-slate-800 p-5 md:p-6 flex-grow w-full shadow-sm">
      
      <!-- Alert Aksi / Flash Message -->
      <?php if (file_exists('../includes/alert_actions.php')) include '../includes/alert_actions.php'; ?>

      <!-- Header Halaman & Action Bar -->
      <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 mb-6 pb-5 border-b border-slate-100 dark:border-slate-800">
        <div>
          <span class="text-[10px] md:text-xs font-bold uppercase tracking-widest text-indigo-600 dark:text-indigo-400">Database Master</span>
          <h1 class="text-2xl font-extrabold text-slate-800 dark:text-white mt-1">Data Mahasiswa</h1>
          <p class="text-xs text-slate-400 dark:text-slate-500 mt-1">Kelola biodata, akun, dan status mahasiswa aktif.</p>
        </div>
        <div class="flex items-center gap-2">
          <a href="../actions/tambah.php?type=mahasiswa" class="inline-flex items-center gap-2 px-4 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-semibold rounded-xl shadow-sm hover:shadow transition-all">
            <i class="fa-solid fa-plus"></i> Tambah Mahasiswa
          </a>
        </div>
      </div>

      <!-- Konten Tabel / Data / Grid -->
      <div class="overflow-x-auto rounded-xl border border-slate-200 dark:border-slate-800">
        <!-- Struktur Tabel standar ada di bagian 5.3 -->
      </div>

    </div>

    <!-- Footer Back-office -->
    <?php include '../includes/footer_db.php'; ?>
  </main>

  <!-- Script Back-office (Theme toggle, sidebar, SweetAlert) -->
  <?php include '../includes/script_db.php'; ?>
</body>
</html>
```

---

### 4.3 Action / CRUD Form Page
Digunakan untuk halaman input, edit data, dan konfirmasi transaksi (`actions/tambah.php`, `actions/edit.php`, `actions/tambah_kas_minggu.php`, dll).

```php
<?php
session_start();
include '../koneksi.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit;
}

$title = "Tambah Data Mahasiswa - SIMAWA PTIK";
include '../includes/head.php';
?>

<body class="bg-slate-100 dark:bg-slate-950 text-slate-800 dark:text-slate-200 font-sans min-h-screen flex flex-col transition-colors duration-300">
  
  <?php include '../includes/menu_db.php'; ?>

  <main class="ml-0 lg:ml-48 pt-20 p-4 md:p-6 flex flex-col min-h-screen">
    <div class="max-w-4xl mx-auto w-full bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 p-6 md:p-8 shadow-sm">
      
      <!-- Header Form & Tombol Kembali -->
      <div class="flex items-center justify-between gap-4 mb-6 pb-4 border-b border-slate-100 dark:border-slate-800">
        <div>
          <span class="text-xs font-bold uppercase tracking-widest text-indigo-600 dark:text-indigo-400">Formulir Data</span>
          <h1 class="text-2xl font-bold text-slate-800 dark:text-white mt-1">Tambah Mahasiswa Baru</h1>
        </div>
        <a href="../database/db_mahasiswa.php" class="inline-flex items-center gap-2 px-3.5 py-2 rounded-xl bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-600 dark:text-slate-300 text-xs font-semibold transition-all">
          <i class="fa-solid fa-arrow-left"></i> Kembali
        </a>
      </div>

      <!-- Form Grid -->
      <form action="../actions/proses_tambah.php" method="POST" enctype="multipart/form-data" class="space-y-6">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
          <!-- Input Field Contoh -->
          <div>
            <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300 mb-2">NIM Mahasiswa <span class="text-rose-500">*</span></label>
            <input type="text" name="nim" required placeholder="Contoh: 24020950..." class="w-full px-3.5 py-2.5 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl text-slate-800 dark:text-white text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-all placeholder:text-slate-400">
          </div>

          <div>
            <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300 mb-2">Nama Lengkap <span class="text-rose-500">*</span></label>
            <input type="text" name="nama" required placeholder="Nama sesuai KTM" class="w-full px-3.5 py-2.5 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl text-slate-800 dark:text-white text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-all placeholder:text-slate-400">
          </div>
        </div>

        <!-- Tombol Aksi Bawah -->
        <div class="flex items-center justify-end gap-3 pt-4 border-t border-slate-100 dark:border-slate-800">
          <button type="reset" class="px-5 py-2.5 bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-600 dark:text-slate-300 text-xs font-semibold rounded-xl transition-all">
            Reset
          </button>
          <button type="submit" class="inline-flex items-center gap-2 px-6 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-semibold rounded-xl shadow-sm hover:shadow transition-all">
            <i class="fa-solid fa-floppy-disk"></i> Simpan Data
          </button>
        </div>
      </form>

    </div>
    <?php include '../includes/footer_db.php'; ?>
  </main>

  <?php include '../includes/script_db.php'; ?>
</body>
</html>
```

---

## 5. STANDAR KOMPONEN UI (TAILWIND BOILERPLATE)

Setiap pengembang **wajib menyalin struktur class berikut** agar seluruh halaman seragam dan konsisten.

### 5.1 Container & Kartu (Cards)
Gunakan radius `rounded-xl` atau `rounded-2xl`, dengan border halus dan transisi hover jika kartu bersifat interaktif.

```html
<!-- Standard Content Card -->
<div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 p-6 shadow-sm">
  <h3 class="text-base font-bold text-slate-800 dark:text-white mb-2">Judul Kartu</h3>
  <p class="text-sm text-slate-500 dark:text-slate-400">Deskripsi atau konten kartu di sini.</p>
</div>

<!-- Interactive / Clickable Card -->
<a href="#" class="block bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 p-6 shadow-sm hover:border-indigo-500 dark:hover:border-indigo-500 hover:shadow-md transition-all group">
  <div class="flex items-center justify-between">
    <span class="text-xs font-bold text-indigo-600 dark:text-indigo-400 uppercase tracking-wider">Modul</span>
    <i class="fa-solid fa-arrow-right text-slate-400 group-hover:text-indigo-600 group-hover:translate-x-1 transition-all"></i>
  </div>
  <h4 class="text-lg font-bold text-slate-800 dark:text-white mt-2">Akademik & KSM</h4>
</a>
```

---

### 5.2 Statistik & Metric Cards
Digunakan pada dashboard utama (`dashboard.php`) maupun rekap.

```html
<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
  <!-- Stat Card Mahasiswa -->
  <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 p-5 shadow-sm flex items-center justify-between">
    <div>
      <span class="text-[10px] font-bold uppercase tracking-widest text-slate-400 dark:text-slate-500">Total Mahasiswa</span>
      <h3 class="text-2xl font-extrabold text-slate-800 dark:text-white mt-1">36</h3>
      <span class="text-[11px] text-emerald-600 dark:text-emerald-400 font-medium flex items-center gap-1 mt-1">
        <i class="fa-solid fa-circle-check text-[9px]"></i> 100% Aktif
      </span>
    </div>
    <div class="w-12 h-12 rounded-2xl bg-indigo-50 dark:bg-indigo-950/60 text-indigo-600 dark:text-indigo-400 flex items-center justify-center text-lg border border-indigo-100 dark:border-indigo-900/50">
      <i class="fa-solid fa-user-graduate"></i>
    </div>
  </div>

  <!-- Stat Card Kas -->
  <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 p-5 shadow-sm flex items-center justify-between">
    <div>
      <span class="text-[10px] font-bold uppercase tracking-widest text-slate-400 dark:text-slate-500">Saldo Kas</span>
      <h3 class="text-2xl font-extrabold text-slate-800 dark:text-white mt-1">Rp 1.450.000</h3>
      <span class="text-[11px] text-indigo-600 dark:text-indigo-400 font-medium flex items-center gap-1 mt-1">
        <i class="fa-solid fa-clock-rotate-left text-[9px]"></i> Minggu ke-12
      </span>
    </div>
    <div class="w-12 h-12 rounded-2xl bg-emerald-50 dark:bg-emerald-950/60 text-emerald-600 dark:text-emerald-400 flex items-center justify-center text-lg border border-emerald-100 dark:border-emerald-900/50">
      <i class="fa-solid fa-wallet"></i>
    </div>
  </div>
</div>
```

---

### 5.3 Tabel Data (Data Tables)
Header tabel **wajib** menggunakan background `bg-indigo-900 dark:bg-slate-800` dengan font `text-white text-xs font-bold uppercase tracking-wider`. Baris tabel memiliki zebra hover `hover:bg-slate-50/80 dark:hover:bg-slate-800/50`.

```html
<div class="overflow-x-auto rounded-xl border border-slate-200 dark:border-slate-800 shadow-sm">
  <table class="w-full text-left text-sm text-slate-600 dark:text-slate-300">
    <thead class="bg-indigo-900 dark:bg-slate-800 text-white text-xs font-bold uppercase tracking-wider">
      <tr>
        <th class="px-4 py-3.5 text-center w-12">No</th>
        <th class="px-4 py-3.5">NIM</th>
        <th class="px-4 py-3.5">Nama Lengkap</th>
        <th class="px-4 py-3.5 text-center">Status</th>
        <th class="px-4 py-3.5 text-center w-28">Aksi</th>
      </tr>
    </thead>
    <tbody class="divide-y divide-slate-100 dark:divide-slate-800 bg-white dark:bg-slate-900">
      <!-- Contoh Baris Data -->
      <tr class="hover:bg-slate-50/80 dark:hover:bg-slate-800/50 transition-colors">
        <td class="px-4 py-3.5 text-center text-xs font-semibold text-slate-400">1</td>
        <td class="px-4 py-3.5 font-mono text-xs font-bold text-slate-800 dark:text-white">240209502045</td>
        <td class="px-4 py-3.5 font-medium text-slate-800 dark:text-slate-100">Nurhidayat</td>
        <td class="px-4 py-3.5 text-center">
          <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[11px] font-semibold bg-emerald-100 dark:bg-emerald-950/60 text-emerald-700 dark:text-emerald-400 border border-emerald-200 dark:border-emerald-800/50">
            Aktif
          </span>
        </td>
        <td class="px-4 py-3.5 text-center">
          <div class="inline-flex items-center gap-1.5">
            <a href="../actions/edit.php?id=1" class="px-2.5 py-1 text-xs font-medium rounded-lg bg-amber-500/10 text-amber-600 dark:text-amber-400 hover:bg-amber-500 hover:text-white transition-all" title="Edit">
              <i class="fa-solid fa-pen-to-square"></i>
            </a>
            <button onclick="confirmHapus(1)" class="px-2.5 py-1 text-xs font-medium rounded-lg bg-rose-500/10 text-rose-600 dark:text-rose-400 hover:bg-rose-500 hover:text-white transition-all" title="Hapus">
              <i class="fa-solid fa-trash"></i>
            </button>
          </div>
        </td>
      </tr>
    </tbody>
  </table>
</div>
```

---

### 5.4 Badges & Tag Status
Gunakan ukuran teks `text-[10px]` atau `text-[11px]` dengan font tebal (`font-semibold` atau `font-bold`), padding rapat, dan border semi-transparan.

```html
<!-- Status Lunas / Hadir / Aktif -->
<span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider bg-emerald-100 dark:bg-emerald-950/60 text-emerald-700 dark:text-emerald-400 border border-emerald-200 dark:border-emerald-800/50">
  <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span> Hadir
</span>

<!-- Status Alpha / Belum Bayar -->
<span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider bg-rose-100 dark:bg-rose-950/60 text-rose-700 dark:text-rose-400 border border-rose-200 dark:border-rose-800/50">
  <span class="w-1.5 h-1.5 rounded-full bg-rose-500"></span> Alpha
</span>

<!-- Status Izin / Pending -->
<span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider bg-amber-100 dark:bg-amber-950/60 text-amber-700 dark:text-amber-400 border border-amber-200 dark:border-amber-800/50">
  <span class="w-1.5 h-1.5 rounded-full bg-amber-500"></span> Izin
</span>

<!-- Status Sakit / Info Netral -->
<span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider bg-cyan-100 dark:bg-cyan-950/60 text-cyan-700 dark:text-cyan-400 border border-cyan-200 dark:border-cyan-800/50">
  <span class="w-1.5 h-1.5 rounded-full bg-cyan-500"></span> Sakit
</span>
```

---

### 5.5 Formulir & Input Controls
Input wajib menyertakan state fokus `focus:ring-2 focus:ring-indigo-500` dan background adaptif `bg-slate-50 dark:bg-slate-800`.

```html
<!-- Input Teks -->
<div class="space-y-1.5">
  <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300">
    Nama Mata Kuliah <span class="text-rose-500">*</span>
  </label>
  <input type="text" name="nama_mk" placeholder="Contoh: Pemrograman Web" class="w-full px-3.5 py-2.5 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl text-slate-800 dark:text-white text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-all placeholder:text-slate-400">
</div>

<!-- Select Option -->
<div class="space-y-1.5">
  <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300">
    Pilih Semester
  </label>
  <select name="semester" class="w-full px-3.5 py-2.5 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl text-slate-800 dark:text-white text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-all">
    <option value="1">Semester 1</option>
    <option value="2">Semester 2</option>
  </select>
</div>

<!-- File Input Custom -->
<div class="space-y-1.5">
  <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300">Upload Dokumen</label>
  <input type="file" name="dokumen" class="block w-full text-xs text-slate-500 dark:text-slate-400 file:mr-4 file:py-2.5 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-semibold file:bg-indigo-50 file:text-indigo-600 hover:file:bg-indigo-100 dark:file:bg-indigo-950/60 dark:file:text-indigo-400 cursor-pointer">
</div>
```

---

### 5.6 Tombol (Buttons & Actions)

```html
<!-- Primary Button (Indigo) -->
<button type="submit" class="inline-flex items-center justify-center gap-2 px-5 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-semibold rounded-xl shadow-sm hover:shadow transition-all">
  <i class="fa-solid fa-floppy-disk"></i> Simpan Perubahan
</button>

<!-- Secondary / Neutral Button -->
<a href="#" class="inline-flex items-center justify-center gap-2 px-5 py-2.5 bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 text-xs font-semibold rounded-xl transition-all">
  <i class="fa-solid fa-arrow-left"></i> Batal
</a>

<!-- Danger Button (Rose) -->
<button type="button" class="inline-flex items-center justify-center gap-2 px-5 py-2.5 bg-rose-600 hover:bg-rose-700 text-white text-xs font-semibold rounded-xl shadow-sm transition-all">
  <i class="fa-solid fa-trash"></i> Hapus Permanen
</button>

<!-- Success Button (Emerald) -->
<button type="button" class="inline-flex items-center justify-center gap-2 px-5 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-semibold rounded-xl shadow-sm transition-all">
  <i class="fa-solid fa-file-excel"></i> Export Excel
</button>
```

---

### 5.7 Modal Dialog
Gunakan backdrop blur `backdrop-blur-sm` dengan z-index `z-50` dan transisi halus.

```html
<!-- Backdrop & Container Modal (Default: hidden) -->
<div id="modalCustom" class="fixed inset-0 bg-slate-950/60 backdrop-blur-sm z-50 flex items-center justify-center p-4 hidden">
  <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl w-full max-w-lg shadow-2xl overflow-hidden animate-in fade-in zoom-in duration-200">
    
    <!-- Modal Header -->
    <div class="flex items-center justify-between px-6 py-4 border-b border-slate-100 dark:border-slate-800">
      <h3 class="text-base font-bold text-slate-800 dark:text-white">Detail Tugas Perkuliahan</h3>
      <button onclick="toggleModal('modalCustom')" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 text-sm">
        <i class="fa-solid fa-xmark"></i>
      </button>
    </div>

    <!-- Modal Body -->
    <div class="p-6 space-y-4 text-sm text-slate-600 dark:text-slate-300">
      <p>Isi informasi modal di sini...</p>
    </div>

    <!-- Modal Footer -->
    <div class="flex items-center justify-end gap-2.5 px-6 py-4 bg-slate-50 dark:bg-slate-800/40 border-t border-slate-100 dark:border-slate-800">
      <button onclick="toggleModal('modalCustom')" class="px-4 py-2 rounded-xl bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 text-xs font-semibold transition-all">
        Tutup
      </button>
    </div>

  </div>
</div>
```

---

### 5.8 Notifikasi & SweetAlert2
Gunakan integrasi SweetAlert2 yang selaras dengan tema gelap/terang SIMAWA PTIK:

```javascript
// Konfirmasi Hapus Data
function confirmHapus(id, targetUrl) {
  const isDark = document.documentElement.classList.contains('dark');
  Swal.fire({
    title: 'Hapus Data Ini?',
    text: "Data yang dihapus tidak dapat dipulihkan kembali!",
    icon: 'warning',
    showCancelButton: true,
    confirmButtonColor: '#e11d48', // rose-600
    cancelButtonColor: '#64748b',  // slate-500
    confirmButtonText: 'Ya, Hapus!',
    cancelButtonText: 'Batal',
    background: isDark ? '#0f172a' : '#ffffff',
    color: isDark ? '#f8fafc' : '#0f172a'
  }).then((result) => {
    if (result.isConfirmed) {
      window.location.href = targetUrl + '?id=' + id;
    }
  });
}
```

---

## 6. ATURAN AKSES BERBASIS ROLE (RBAC)

Di SIMAWA PTIK terdapat 4 role pengguna utama:
1. **`Administrator Utama`**: Memiliki akses penuh ke seluruh menu master (`database/*`), pengaturan sistem, pengguna, dan transaksi.
2. **`Sekretaris`**: Mengelola absensi mahasiswa, rekapitulasi, dan agenda kalender akademik.
3. **`Bendahara`**: Mengelola kas kelas mingguan, iuran individu, dan rekapitulasi keuangan.
4. **`Mahasiswa`**: Mengakses kartu studi mahasiswa (KSM), kelompok tugas, melihat rekap kas pribadi, piket proyektor, dan status absensi.

### Standar Pengecekan Hak Akses di Halaman:
```php
// Contoh proteksi hanya untuk Administrator dan Bendahara
$allowed_roles = ['Administrator Utama', 'Bendahara'];
if (!in_array($_SESSION['role'], $allowed_roles)) {
    // Tampilkan alert penolakan atau redirect
    if (file_exists('../includes/alert_role.php')) {
        include '../includes/alert_role.php';
        exit;
    } else {
        header("Location: ../dashboard.php");
        exit;
    }
}
```

---

## 7. ATURAN PATH, URL BASE, DAN ASET

Untuk memastikan portabilitas (bisa dijalankan di localhost XAMPP subfolder `/simawa-ptik/` maupun domain root produksi):
1. **Gunakan Variable `$base`**:
   `includes/head.php` otomatis menghitung `$base` relatif terhadap kedalaman folder:
   ```php
   // Contoh memanggil aset atau halaman:
   <a href="<?= $base ?>dashboard.php">Dashboard</a>
   <img src="<?= $base ?>assets/img/logo.png" alt="Logo">
   ```
2. **Penyimpanan Foto Profil**:
   - Direktori upload: `uploads/` atau `storage/profile_photos/`.
   - Gunakan fallback default jika foto kosong:
     ```php
     $fotoSrc = (!empty($row['foto']) && file_exists(__DIR__ . '/../' . $row['foto'])) 
         ? $base . $row['foto'] 
         : $base . 'assets/img/default-avatar.png';
     ```
3. **Konfigurasi `.htaccess`**:
   `RewriteBase /simawa-ptik/` harus selalu selaras dengan folder project di XAMPP.

---

## 8. DOS AND DON'TS (ATURAN WAJIB PENGEMBANG)

### ✅ WAJIB DILAKUKAN (DOs):
1. **Selalu sertakan pasangan `dark:` pada semua styling warna background, teks, dan border.**
2. **Gunakan layout shell standar** (`ml-0 lg:ml-48 pt-20 p-4 md:p-6`) pada semua halaman back-office/dashboard.
3. **Bungkus tabel di dalam container responsif** (`<div class="overflow-x-auto rounded-xl border ...">`).
4. **Sediakan pesan feedback** menggunakan session flash (`$_SESSION['sukses']` atau `$_SESSION['error']`) setelah operasi form selesai.
5. **Sanitasi setiap input database** menggunakan `mysqli_real_escape_string()` atau prepared statements.

### ❌ DILARANG KERAS (DON'Ts):
1. **Dilarang menggunakan nama kelas lama "PTIK C"** dalam teks UI, footer, judul halaman, atau dokumen baru.
2. **Dilarang hardcode path XAMPP lama** (seperti `/ptikc24/`).
3. **Dilarang membuat style CSS inline** (`style="..."`) jika Tailwind sudah menyediakannya.
4. **Dilarang menghapus class dark mode toggle** dari script layout.
5. **Dilarang melompat langsung ke eksekusi `DELETE` tanpa konfirmasi (SweetAlert/Modal)**.

---
*Dokumen ini dibuat secara otomatis dan komprehensif berdasarkan penelusuran arsitektur kode sumber SIMAWA PTIK 2024.*

