-- ==============================================================================
-- SIMAWA PTIK 2024 - MIGRATION 002: CLEAN ARCHITECTURE & ROLE ISOLATION
-- File: database/migrations/002_clean_architecture_users_mahasiswa.sql
-- Tujuan:
--   1. Menambahkan kolom `nama_lengkap`, `foto`, dan `linked_student_nim` ke tabel `users`.
--   2. Memindahkan data nama dan foto akun yang ada dari `mahasiswa` ke `users`.
--   3. Menjadikan tabel `mahasiswa` murni hanya untuk akun ber-role `Mahasiswa`.
--   4. Mendukung Soft Informational Linking: Akun fungsional (Ketua Tingkat/Pengurus)
--      memiliki catatan opsional `linked_student_nim` tanpa menggabungkan permission/data.
-- ==============================================================================

-- 1. Tambah kolom ke tabel users jika belum ada
ALTER TABLE `users`
  ADD COLUMN IF NOT EXISTS `nama_lengkap` VARCHAR(100) NOT NULL DEFAULT '' AFTER `role`,
  ADD COLUMN IF NOT EXISTS `foto` VARCHAR(255) NULL AFTER `nama_lengkap`,
  ADD COLUMN IF NOT EXISTS `linked_student_nim` VARCHAR(20) NULL COMMENT 'Catatan informasi NIM asli mahasiswa di dunia nyata' AFTER `foto`;

-- 2. Migrasikan nama_lengkap dan foto dari mahasiswa ke users untuk semua akun yang ada
UPDATE `users` u
JOIN `mahasiswa` m ON m.user_id = u.id
SET 
  u.nama_lengkap = m.nama_lengkap,
  u.foto = m.foto;

-- 3. Set nama & foto Administrator Utama secara eksplisit
UPDATE `users`
SET 
  `nama_lengkap` = 'Administrator SIMAWA',
  `foto` = IFNULL(`foto`, 'Yaya.png')
WHERE `role` = 'Administrator Utama';

-- 4. Set linked_student_nim awal untuk akun Ketua Tingkat eksisting (User 16: ketua@simawa.id)
UPDATE `users`
SET `linked_student_nim` = '240209501001'
WHERE `id` = 16 AND `role` = 'Ketua Tingkat';

-- 5. Lepaskan kaitan user_id = 27 (Administrator Utama) dari tabel mahasiswa
-- Administrator Utama adalah Superadmin sistem dan bukan mahasiswa di kelas 1
UPDATE `mahasiswa` SET `user_id` = NULL WHERE `user_id` = 27;
