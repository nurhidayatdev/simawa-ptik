# PROJECT CONTEXT DOCUMENT — SIMAWA PTIK 2024
## Sistem Informasi dan Manajemen Akademik Mahasiswa PTIK 2024
### Universitas Negeri Makassar (UNM)

> **Tujuan dokumen ini:** Memberikan pemahaman menyeluruh tentang proyek SIMAWA PTIK 2024 kepada AI lain, developer, atau pembaca baru, mencakup latar belakang, arsitektur sistem, struktur kode mutakhir, basis data, RBAC, integrasi, standar desain (`design.md`), dan konteks pengujian ISO/IEC 25010 yang dilakukan dalam mata kuliah Pengujian Perangkat Lunak (PPL).

---

## 1. IDENTITAS DAN LATAR BELAKANG PROYEK

| Atribut | Detail |
| :--- | :--- |
| **Nama Sistem** | SIMAWA PTIK 2024 (Sistem Informasi dan Manajemen Akademik Mahasiswa PTIK 2024) |
| **Sub-identitas** | ProTechNoTy Class — Generasi 2024 |
| **Versi Aktif** | v3.2.0 (Build Oktober 2026) |
| **Pengembang Utama** | Nur Hidayat (@nurhidayatdev) & Tim Pengembang Internal PTIK UNM |
| **Repositori Resmi** | `https://github.com/nurhidayatdev/simawa-ptik.git` |
| **URL Lokal (Pengujian)** | `http://localhost/simawa-ptik/` |
| **Basis Data** | `ptik24c` (MariaDB/MySQL di XAMPP localhost) |
| **Server Stack** | XAMPP: Apache 2.4.x + PHP 8.2+ + MariaDB 10.4+ |
| **Institusi** | Prodi PTIK, Jurusan TIK, Fakultas Teknik, Universitas Negeri Makassar |
| **Konteks Akademik** | Digunakan secara nyata oleh Mahasiswa PTIK 2024 untuk administrasi & akademik perkuliahan |
| **Jumlah Pengguna Aktif** | 38 orang (1 Admin, 3 Pengurus, 34 Mahasiswa Reguler) |
| **Mata Kuliah Terkait** | Pengujian Perangkat Lunak (PPL) — sistem ini menjadi objek pengujian formal ISO/IEC 25010 |

### 1.1 Apa itu SIMAWA PTIK 2024?

SIMAWA PTIK 2024 adalah **portal sistem informasi dan manajemen akademik mahasiswa berbasis web** yang dibangun secara internal untuk mendukung kelancaran perkuliahan mahasiswa Pendidikan Teknik Informatika dan Komputer (PTIK) angkatan 2024 FT UNM. Sistem ini berfungsi sebagai **"single entry point"** untuk seluruh aktivitas administrasi, akademik, presensi, transparansi keuangan kas, manajemen kelompok, dan pelacakan tenggat tugas kuliah.

Sistem ini **bukan sistem institusional resmi pengganti SIAKAD UNM**, melainkan portal internal mandiri yang dibangun, dikelola, dan digunakan langsung oleh mahasiswa kelas. Tim penguji adalah pengguna nyata sekaligus memiliki akses penuh ke kode sumber (**white-box access**).

### 1.2 Masalah yang Diselesaikan

Sebelum SIMAWA PTIK hadir, operasional perkuliahan menghadapi sejumlah hambatan:
- Informasi jadwal dan tugas sering tenggelam di grup WhatsApp.
- Transparansi kas kelas membutuhkan pencatatan manual yang rentan kesalahan dan lambat direkap.
- Absensi manual per pertemuan sulit direkap persentasenya secara cepat.
- Penataan kelompok tugas dan piket proyektor sering menimbulkan ketidakseimbangan jika dilakukan manual.
- Mahasiswa kesulitan memantau deadline tugas kuliah yang bertumpuk dari berbagai dosen.

---

## 2. TECH STACK DAN DEPENDENSI

### 2.1 Backend
| Komponen | Detail |
| :--- | :--- |
| **Bahasa** | PHP 8.2+ (prosedural template-driven, tanpa framework MVC) |
| **Database** | MariaDB/MySQL 10.4+ via ekstensi `mysqli` (Prepared Statements) |
| **Server Web** | Apache 2.4.x (XAMPP) |
| **Session Management** | PHP Native Session (`$_SESSION`) |
| **Konfigurasi Env** | File `.env` custom dengan `functions/env_loader.php` (dilengkapi `.env.example`) |
| **Timezone** | Asia/Makassar (WITA, UTC+8) |

### 2.2 Frontend & UI System
| Komponen | Detail |
| :--- | :--- |
| **CSS Framework** | Tailwind CSS (dikompilasi lokal via `@tailwindcss/cli` & didukung CDN script) |
| **Design System** | Terstandarisasi resmi dalam dokumen `design.md` (Dual-theme native, Slate & Indigo palette) |
| **Ikon** | Font Awesome 6.4.0 (Free Webfonts) |
| **Tipografi** | Google Fonts — Poppins (`font-sans`) |
| **Interaksi / Dialog** | SweetAlert2 v11 (otomatis mendukung dark/light theme) |
| **Grafik / Chart** | Chart.js (Visualisasi data kehadiran, tugas, dan tren keuangan kas) |
| **jQuery** | jQuery 3.7.1 |
| **Tema** | Dark/Light Mode — dikontrol via class `dark` pada `<html>`, persistensi via `localStorage` |

### 2.3 Integrasi Eksternal
| Layanan | Fungsi |
| :--- | :--- |
| **Fonnte API** (`api.fonnte.com`) | Gateway notifikasi WhatsApp otomatis ke Grup Kelas |
| **Google Analytics** | Tracking analitik pengunjung (GA4: `G-Q0TXETXV6Y`) |
| **Credential Management API** | Browser prompt simpan kredensial saat login |

### 2.4 Perintah Build Tailwind
```bash
npx @tailwindcss/cli -i assets/tailwind/src/input.css -o assets/tailwind/src/output.css --watch
```

---

## 3. STRUKTUR DIREKTORI PROYEK (MUKTAHIR)

```
simawa-ptik/
├── .env.example                  # Template variabel lingkungan (aman dipublikasikan)
├── .gitignore                    # Pengabaian file lokal (.env, sql dumps, cache)
├── .htaccess                     # URL rewrite (clean URL, RewriteBase /simawa-ptik/)
├── koneksi.php                   # Koneksi MySQLi + init settings + enforcement maintenance
├── index.php                     # Landing page utama publik
├── login.php                     # Halaman autentikasi login pengguna
├── dashboard.php                 # Router & dashboard internal operasional kelas
├── dashboard_admin.php           # Executive Dashboard khusus Administrator Utama (Multi-Kelas)
├── design.md                     # STANDAR RESMI UI/UX & Design System SIMAWA PTIK
├── PROJECT_CONTEXT.md            # DOKUMEN INI (Konteks menyeluruh proyek & pengujian)
│
├── [HALAMAN MODUL PUBLIK / FRONTEND]
├── akademik.php                  # Pusat informasi akademik & kurikulum
├── jadwal.php                    # Jadwal perkuliahan mingguan aktif
├── kalender.php                  # Kalender akademik interaktif
├── mahasiswa.php                 # Direktori data mahasiswa
├── matakuliah.php                # Daftar mata kuliah semester aktif & SKS
├── tugas.php                     # Daftar tugas kuliah + deadline tracker
├── info.php                      # Pengumuman & informasi perkuliahan
├── panduan.php                   # Dokumentasi panduan penggunaan platform & bot WA
├── download.php                  # Pusat unduhan dokumen & aset akademik
├── tentang.php                   # Profil platform SIMAWA PTIK & ProTechNoTy Class
│
├── actions/                      # Controller PHP: proses POST/form (19 file)
│   ├── bagikan_kelompok.php      # Kirim notifikasi WA pembagian kelompok
│   ├── bulk_krs.php              # UI input KRS massal mahasiswa
│   ├── bulk_krs_action.php       # Backend pemrosesan KRS massal
│   ├── edit.php                  # Form edit serbaguna
│   ├── edit_kelompok.php         # Edit struktur anggota kelompok
│   ├── edit_krs.php              # Edit status KRS mahasiswa
│   ├── edit_profile.php          # Pembaruan profil & foto pengguna
│   ├── hapus.php                 # Handler penghapusan data dengan konfirmasi
│   ├── reset_password.php        # Reset password akun pengguna
│   ├── tambah.php                # Form penambahan data serbaguna
│   ├── tambah_absensi.php        # Input absensi per pertemuan
│   ├── tambah_kas_individu.php   # Pencatatan iuran kas individu
│   ├── tambah_kas_minggu.php     # Pencatatan kas massal per minggu
│   ├── tambah_kelompok_berimbang.php # Algoritma pembagian kelompok otomatis
│   ├── tambah_kelompok_murni.php     # Pembagian kelompok manual
│   ├── tambah_kelompok_sejenis.php   # Pembagian kelompok berdasarkan kriteria
│   ├── tambah_piket_proyektor.php    # Penjadwalan piket proyektor kelas
│   ├── toggle_maintenance.php    # Toggle aktivasi status maintenance mode
│   └── validasi_krs.php          # Validasi KRS per mahasiswa
│
├── database/                     # CRUD Master Data — ADMIN ONLY (13 file, RBAC)
│   ├── db_berita.php             # Kelola pengumuman & berita
│   ├── db_dosen.php              # Kelola master data dosen
│   ├── db_jadwal.php             # Kelola jadwal kuliah per ruangan/jam
│   ├── db_jam.php                # Kelola master slot jam kuliah
│   ├── db_kalender.php           # Kelola agenda kalender akademik
│   ├── db_kelompok.php           # Kelola master data kelompok tugas
│   ├── db_mahasiswa.php          # Kelola master data mahasiswa & akun
│   ├── db_matkul.php             # Kelola master mata kuliah & SKS
│   ├── db_periode.php            # Kelola periode semester akademik aktif
│   ├── db_ruangan.php            # Kelola master ruang perkuliahan
│   ├── db_settings.php           # Pengaturan sistem & pesan maintenance
│   ├── db_tugas.php              # Kelola daftar tugas perkuliahan
│   └── db_users.php              # Kelola user credential & role sistem
│
├── views/                        # Halaman view interaktif dengan proteksi sesi (11 file)
│   ├── kalender_view.php         # Kalender interaktif back-office
│   ├── kelompok.php              # Tampilan visual pembagian kelompok
│   ├── ksm.php                   # Pratinjau & cetak Kartu Studi Mahasiswa
│   ├── manajemen_absen.php       # Pengelolaan presensi kelas (Sekretaris/Admin)
│   ├── manajemen_kas.php         # Pengelolaan keuangan kas (Bendahara/Admin)
│   ├── piket_proyektor.php       # Jadwal & status piket proyektor perkuliahan
│   ├── profile.php               # Halaman pengaturan profil & password saya
│   ├── rekap_absen.php           # Rekapitulasi persentase kehadiran mahasiswa
│   ├── rekap_kas.php             # Rekapitulasi transaksi & transparansi kas
│   ├── reminder.php              # Pengaturan notifikasi reminder tenggat tugas
│   └── tugas_saya.php            # Pelacak status pengerjaan tugas individu
│
├── includes/                     # Komponen reusable & parsial layout (9 file)
│   ├── alert_actions.php         # Flash alert umpan balik aksi form
│   ├── alert_role.php            # SweetAlert penolakan hak akses non-admin
│   ├── footer_db.php             # Footer dashboard back-office
│   ├── footer_main.php           # Footer resmi 4 kolom publik (design.md compliant)
│   ├── head.php                  # Header HTML, meta, CSS, kalkulasi $base dinamis
│   ├── header_main.php           # Fixed navbar halaman publik dengan dark mode toggle
│   ├── menu_db.php               # Sidebar responsif & topbar dashboard internal
│   ├── script_db.php             # Library JS dashboard (Chart.js, SweetAlert2)
│   └── script_main.php           # Skrip navigasi publik, reveal observer, tema
│
├── functions/                    # Helper & fungsi utilitas (11 file)
│   ├── env_loader.php            # Parser file .env menjadi fungsi env()
│   ├── foto_helpers.php          # Helper resolusi URL foto profil & avatar fallback
│   ├── helpers.php               # sendFonnteMessage() — integrasi gateway WhatsApp
│   ├── kirim_jadwal.php          # Notifikasi WA jadwal kuliah mingguan
│   ├── kirim_kas.php             # Notifikasi WA konfirmasi pembayaran kas
│   ├── kirim_kelompok.php        # Notifikasi WA rincian pembagian kelompok
│   ├── kirim_krs.php             # Notifikasi WA status pengajuan KRS
│   ├── kirim_notif_krs.php       # Notifikasi WA pengingat batas waktu KRS
│   ├── kirim_piket_reminder.php  # Notifikasi WA pengingat giliran piket
│   ├── kirim_tugas.php           # Notifikasi WA pengingat tenggat tugas
│   └── maintenance.php           # Logic inisialisasi & pemaksaan maintenance mode
│
├── assets/                       # Aset gambar, logo resmi, dan styling CSS
└── storage/                      # Direktori upload foto profil & arsip tugas
```

---

## 4. SISTEM AUTENTIKASI DAN RBAC

### 4.1 Alur Autentikasi
1. Mahasiswa/Pengurus menginput email dan password pada `login.php`.
2. Validasi kredensial dilakukan melalui Prepared Statements ke tabel `users` (dijoin dengan data `mahasiswa`).
3. Jika login valid, sistem mencatat data sesi ke `$_SESSION` (`user_id`, `email`, `nama_lengkap`, `nama_panggilan`, `role`, `foto`).
4. `enforce_maintenance_mode($koneksi)` berjalan di setiap halaman: Jika mode pemeliharaan aktif dan akun bukan Administrator Utama, akses ditolak secara otomatis.
5. Notifikasi login berhasil dimunculkan menggunakan SweetAlert2 sebelum diarahkan ke `dashboard`.

> **TEMUAN KEAMANAN (CATATAN PPL):** Penyimpanan password pada rilis awal masih menggunakan komparasi teks. Penerapan fungsi `password_hash()` dan `password_verify()` merupakan rekomendasi prioritas pada aspek Security ISO/IEC 25010.

### 4.2 Level Hak Akses Pengguna (RBAC)

| Peran Sistem | Nilai di `$_SESSION['role']` | Hak Akses & Cakupan Operasional |
| :--- | :--- | :--- |
| **Administrator Utama** | `'Administrator Utama'` | Hak akses penuh (*superadmin*): semua menu CRUD di folder `database/`, konfigurasi sistem, dan toggle maintenance. |
| **Ketua Tingkat** | `'Ketua Tingkat'` | Akses manajerial perkuliahan: absensi, kas, pembagian kelompok, jadwal piket, dan manajemen tugas. |
| **Sekretaris** | `'Sekretaris'` | Input dan rekap presensi mahasiswa (`views/manajemen_absen.php`, `views/rekap_absen.php`). |
| **Bendahara** | `'Bendahara'` | Input transaksi kas, rekapitulasi saldo, dan penagihan iuran kelas (`views/manajemen_kas.php`, `views/rekap_kas.php`). |
| **Mahasiswa** | `'Mahasiswa'` | Akses portal mahasiswa: memantau jadwal, tugas pribadi, histori kas sendiri, KSM, dan kelompok. |

---

## 5. REKAPITULASI BASIS DATA (22 TABEL AKTIF)

Database operasional menggunakan nama basis data **`ptik24c`** dengan 22 tabel inti:
1. **`users`**: Kredensial akun, email, password, role, dan tanggal pendaftaran.
2. **`mahasiswa`**: Biodata mahasiswa (NIM, nama lengkap, panggilan, foto profil, jenis kelamin, kontak).
3. **`dosen`**: Data dosen pengampu mata kuliah beserta NIP dan kontak.
4. **`mata_kuliah`**: Kurikulum mata kuliah aktif, kode MK, bobot SKS, dan semester.
5. **`jadwal_matkul`**: Jadwal perkuliahan mingguan (hari, jam mulai/selesai, ruangan, dosen).
6. **`ruangan`**: Master data ruang kuliah di lingkungan kampus FT UNM.
7. **`jam_kuliah`**: Master slot sesi waktu perkuliahan.
8. **`periode`**: Semester akademik aktif (misal: Semester Genap 2025/2026).
9. **`absensi`**: Pencatatan presensi per pertemuan (Hadir, Izin, Sakit, Alpa).
10. **`kas`**: Catatan setoran iuran kas kelas per mahasiswa dan per minggu.
11. **`arus_kas`**: Arus kas operasional kelas (pemasukan eksternal dan pengeluaran).
12. **`tugas`**: Daftar tugas perkuliahan, deskripsi, deadline tanggal & pukul.
13. **`tugas_personal`**: Status pengerjaan tugas per mahasiswa (Belum/Selesai).
14. **`tugas_global_done`**: Penanda status penyelesaian global tugas kelas.
15. **`kelompok`**: Hasil pembagian kelompok tugas mahasiswa per mata kuliah.
16. **`piket_proyektor`**: Jadwal mahasiswa yang bertugas mengelola proyektor perkuliahan.
17. **`kalender_akademik`**: Agenda dan kegiatan akademik (tanggal mulai & selesai).
18. **`akademik`**: Konten informasi dan kurikulum akademik.
19. **`krs`**: Data Kartu Rencana Studi yang diambil mahasiswa.
20. **`krs_temp`**: Draft pengajuan KRS sementara sebelum divalidasi.
21. **`krs_validasi`**: Log status validasi KRS oleh admin/pengurus.
22. **`settings`**: Konfigurasi platform global (status maintenance mode dan pesan sistem).

> *Catatan refaktor:* Tabel eksperimental atau non-inti seperti `vibe_check`, `galeri`, dan `pesan` telah dihapus sepenuhnya demi kebersihan arsitektur dan stabilitas operasional.

---

## 6. INTEGRASI NOTIFIKASI WHATSAPP (FONNTE API)

Notifikasi otomatis memanfaatkan layanan Fonnte Gateway (`https://api.fonnte.com/send`) melalui fungsi sentral `sendFonnteMessage($message, $target)` di `functions/helpers.php`:

| Pemicu Aksi | Skrip Notifikasi | Target Pengiriman |
| :--- | :--- | :--- |
| **Input Presensi Kelas** | `functions/kirim_jadwal.php` | Grup WhatsApp Utama Kelas |
| **Konfirmasi Setoran Kas** | `functions/kirim_kas.php` | Grup WhatsApp Utama Kelas |
| **Pembagian Kelompok Baru** | `functions/kirim_kelompok.php` | Grup WhatsApp Utama Kelas |
| **Pengumuman Status KRS** | `functions/kirim_krs.php` | Grup WhatsApp Utama Kelas |
| **Reminder Giliran Piket** | `functions/kirim_piket_reminder.php` | Grup WhatsApp Utama Kelas |
| **Peringatan Tenggat Tugas** | `functions/kirim_tugas.php` | Grup WhatsApp Tugas Khusus |

Variabel lingkungan Fonnte didefinisikan pada `.env`:
```env
FONNTE_TOKEN=your_fonnte_token_here
FONNTE_GROUP_ID=your_main_group_id@g.us
FONNTE_TUGAS_GROUP_ID=your_task_group_id@g.us
```

---

## 7. STANDAR DESAIN DAN UI/UX (`design.md`)

Seluruh pembuatan dan pemeliharaan antarmuka wajib mengacu pada **`design.md`** sebagai sumber kebenaran desain tunggal:
1. **Aturan Dual-Theme Native:** Setiap elemen antarmuka wajib memiliki varian `dark:` untuk mendukung mode gelap tanpa inkonsistensi kontras.
2. **Palet Warna:** Dominasi Indigo (`#4f46e5`) sebagai aksen utama dan Slate (`#0f172a` / `#ffffff`) sebagai permukaan netral.
3. **Layout Shell Terstandar:**
   - **Frontend Publik:** Menggunakan `includes/header_main.php`, fixed navbar, container centered, dan `includes/footer_main.php` 4 kolom responsif.
   - **Back-Office Dashboard:** Menggunakan `includes/menu_db.php`, wrapper konten `ml-0 lg:ml-48 pt-20 p-4 md:p-6`, dan `includes/footer_db.php`.
4. **Komponen Konsisten:** Menggunakan radius standar `rounded-xl` hingga `rounded-2xl`, header tabel berlatar `bg-indigo-900 dark:bg-slate-800`, dan SweetAlert2 dialog konfirmasi hapus data.

---

## 8. KONTEKS PENGUJIAN PERANGKAT LUNAK (MATA KULIAH PPL)

### 8.1 Kerangka Standar Pengujian
- **Standar Kualitas Perangkat Lunak:** ISO/IEC 25010:2011 (8 Karakteristik Kualitas).
- **Standar Dokumentasi Pengujian:** IEEE Std 829-2008 (*Test Summary Report & Test Plans*).
- **Kerangka Kompetensi:** ISTQB CTFL v4.0.
- **Metrik Pengukuran Kualitas:** ISO/IEC 25023:2016.

### 8.2 Pemetaan 8 Karakteristik ISO/IEC 25010 pada SIMAWA PTIK
| No | Karakteristik | Fokus Evaluasi | Instrumen & Alat Uji |
| :---: | :--- | :--- | :--- |
| 1 | **Functional Suitability** | Kelengkapan & kebenaran alur KRS, Kas, Absensi, Kelompok, Tugas | Selenium, Test Case Manual (60 skenario) |
| 2 | **Performance Efficiency** | Waktu respons dashboard & kueri rekapitulasi data mahasiswa | Apache JMeter v5.6, DevTools Network |
| 3 | **Compatibility** | Dukungan multi-browser (Chrome, Firefox, Edge) & interoperabilitas API | Postman, cURL, Browser Testing |
| 4 | **Usability** | Kemudahan navigasi, kejelasan informasi, kepatuhan tema gelap/terang | Kuesioner SUS (System Usability Scale), Lighthouse |
| 5 | **Reliability** | Penanganan eksepsi SQL, ketiadaan fatal error saat data bernilai null | PHP Error Log, JMeter Fault Injection |
| 6 | **Security** | Evaluasi otentikasi login, pencegahan bypass RBAC, audit SQL Injection | OWASP ZAP, Burp Suite Community |
| 7 | **Maintainability** | Keterbacaan kode, kepatuhan standar `design.md`, modularitas helper | SonarLint, PHP_CodeSniffer |
| 8 | **Portability** | Kemudahan instalasi di XAMPP localhost serta responsivitas layar | DevTools Device Mode (Mobile, Tablet, Desktop) |

---

## 9. DOKUMEN PENDUKUNG DI REPOSITORI

| Dokumen | Fungsi & Peruntukan |
| :--- | :--- |
| [**`design.md`**](design.md) | Pedoman resmi standar UI/UX, palet warna, tipografi, boilerplate komponen, dan aturan DOs/DON'Ts. |
| [**`PROJECT_CONTEXT.md`**](PROJECT_CONTEXT.md) | **Dokumen ini** — panduan arsitektur komprehensif untuk pengembang & AI pendamping. |
| [**`.env.example`**](.env.example) | Contoh template konfigurasi database dan API gateway WhatsApp. |

---

## 10. FAQ & CATATAN UNTUK PENGEMBANG / AI

**Q: Bagaimana cara menjalankan repositori ini di XAMPP lokal?**  
A: Kloning repositori ke `c:\xampp\htdocs\simawa-ptik`, salin `.env.example` menjadi `.env`, buat database `ptik24c` di MySQL dan impor skema tabel, lalu buka browser di `http://localhost/simawa-ptik/`.

**Q: Mengapa tidak ada framework PHP (seperti Laravel)?**  
A: Proyek ini dirancang menggunakan PHP prosedural murni agar ringan dijalankan langsung pada paket XAMPP standar tanpa dependensi Composer di server lokal mahasiswa.

**Q: Mengapa fitur galeri, pesan anonim, kuis harian, dan vibe check tidak ada di menu?**  
A: Fitur-fitur tersebut telah dibersihkan secara resmi agar sistem berfokus penuh sebagai **Sistem Informasi Manajemen Akademik Mahasiswa (SIMAWA PTIK)** yang bersih, teruji, dan stabil.
