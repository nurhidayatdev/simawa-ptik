# Telegram Task Reminder - Setup Guide

## 📋 Deskripsi
Script otomatis untuk mengirim reminder ke Telegram H-1 sebelum deadline tugas.

## 📁 File yang Dibuat
1. `telegram_reminder.php` - Script utama
2. `run_telegram_reminder.bat` - Batch file untuk Windows
3. `telegram_reminder.log` - Log file (dibuat otomatis)

## 🚀 Cara Menjalankan

### Opsi 1: Manual (untuk testing)
```bash
# Via Command Line
cd C:\xampp\htdocs\ptikc24\backend\cron
php telegram_reminder.php

# Atau double-click file:
run_telegram_reminder.bat
```

### Opsi 2: Via Browser (untuk testing)
Akses URL berikut di browser:
```
http://localhost/ptikc24/backend/cron/telegram_reminder.php
```

### Opsi 3: Otomatis dengan Windows Task Scheduler (RECOMMENDED)

#### Langkah-langkah Setup:

1. **Buka Task Scheduler**
   - Tekan `Win + R`
   - Ketik `taskschd.msc`
   - Tekan Enter

2. **Buat Task Baru**
   - Klik "Create Basic Task" di panel kanan
   - Nama: `Telegram Reminder Tugas`
   - Deskripsi: `Kirim reminder H-1 deadline tugas ke Telegram`
   - Klik Next

3. **Set Trigger (Jadwal)**
   - Pilih "Daily" (Setiap Hari)
   - Klik Next
   - Set waktu: **07:00** (atau waktu yang diinginkan, disarankan pagi hari)
   - Recur every: **1 days**
   - Klik Next

4. **Set Action**
   - Pilih "Start a program"
   - Klik Next
   - Program/script: Browse ke file `run_telegram_reminder.bat`
     ```
     C:\xampp\htdocs\ptikc24\backend\cron\run_telegram_reminder.bat
     ```
   - Klik Next

5. **Finish**
   - Review settings
   - Centang "Open the Properties dialog..."
   - Klik Finish

6. **Properties (Optional tapi disarankan)**
   - Tab "General":
     - Centang "Run whether user is logged on or not"
     - Centang "Run with highest privileges"
   - Tab "Conditions":
     - Uncheck "Start the task only if the computer is on AC power"
   - Tab "Settings":
     - Centang "Run task as soon as possible after a scheduled start is missed"
   - Klik OK

## 📊 Cara Kerja

1. Script berjalan setiap hari pada waktu yang ditentukan
2. Mengecek database untuk tugas dengan deadline = besok
3. Jika ada, kirim notifikasi ke grup Telegram
4. Jika tidak ada, tidak kirim apa-apa
5. Semua aktivitas tercatat di file `telegram_reminder.log`

## 📝 Format Pesan Telegram

```
⏰ REMINDER: DEADLINE TUGAS BESOK! ⏰

📅 Tanggal: [tanggal besok]

Berikut tugas yang harus dikumpulkan BESOK:

1. [Nama Mata Kuliah]
   ⏰ Deadline: [jam] WIB
   📝 [Deskripsi]
   🔗 Link: [link pengumpulan]

2. [Mata Kuliah lainnya...]

Jangan lupa kerjakan dan kumpulkan tepat waktu! 🚀
H-1 sebelum deadline ⚠️
```

## 🔍 Troubleshooting

### Cek Log File
Jika ada masalah, cek file log:
```
C:\xampp\htdocs\ptikc24\backend\cron\telegram_reminder.log
```

### Test Manual
Jalankan script secara manual untuk testing:
```bash
cd C:\xampp\htdocs\ptikc24\backend\cron
php telegram_reminder.php
```

### Common Issues

1. **Script tidak berjalan**
   - Pastikan XAMPP Apache dan MySQL sudah running
   - Cek path PHP di file .bat sudah benar (`C:\xampp\php\php.exe`)

2. **Pesan tidak terkirim**
   - Cek koneksi internet
   - Cek token Telegram dan chat_id
   - Lihat error di log file

3. **Database error**
   - Pastikan MySQL running
   - Cek koneksi database di `koneksi.php`

## ⚙️ Konfigurasi

Edit file `telegram_reminder.php` jika perlu mengubah:
- Telegram Token (baris 16)
- Chat ID (baris 17)
- Waktu H-1 (baris 20-21)

## 📌 Rekomendasi Waktu

**Waktu Terbaik untuk Menjalankan:**
- **07:00 WIB** - Pagi hari agar mahasiswa bisa membaca di pagi hari
- **20:00 WIB** - Malam hari sebagai reminder tambahan

**Tips:**
- Bisa membuat 2 scheduled task dengan waktu berbeda
- Pagi untuk reminder awal, malam untuk reminder kedua

## ✅ Verifikasi Setup

Setelah setup Task Scheduler:
1. Right-click pada task yang dibuat
2. Pilih "Run"
3. Cek Telegram grup - jika ada tugas besok, akan muncul notifikasi
4. Cek log file untuk memastikan script berjalan

## 📞 Support

Jika ada pertanyaan atau masalah, hubungi administrator sistem.
