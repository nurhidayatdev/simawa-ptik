-- ==============================================================================
-- SIMAWA PTIK 2024 - MIGRATION 001: MULTI-CLASS ARCHITECTURE
-- File: database/migrations/001_create_table_kelas_and_add_kelas_id.sql
-- Tujuan:
--   1. Membuat tabel master `kelas` untuk mendukung multi-kelas di prodi PTIK 2024.
--   2. Mendaftarkan 1 data kelas default (ID=1: "PTIK C 2024") untuk 38 user lama.
--   3. Menambahkan kolom `kelas_id` dan relasi Foreign Key pada 9 tabel operasional:
--      users, mahasiswa, jadwal_matkul, absensi, kas, arus_kas, tugas, kelompok, piket_proyektor.
--   4. Memastikan SELURUH data lama otomatis terikat ke kelas_id = 1 (Zero Data Loss).
-- ==============================================================================

-- ------------------------------------------------------------------------------
-- 1. BUAT TABEL `kelas`
-- ------------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `kelas` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nama_kelas` varchar(100) NOT NULL COMMENT 'Nama kelas, misal: PTIK C 2024, PTIK A 2024',
  `angkatan` varchar(10) NOT NULL DEFAULT '2024',
  `prodi` varchar(100) NOT NULL DEFAULT 'Pendidikan Teknik Informatika dan Komputer',
  `ketua_tingkat_user_id` int(11) DEFAULT NULL COMMENT 'User ID ketua tingkat penanggung jawab kelas',
  `status_aktif` enum('aktif','non-aktif') NOT NULL DEFAULT 'aktif',
  `dibuat_pada` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_kelas_ketua` (`ketua_tingkat_user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ------------------------------------------------------------------------------
-- 2. SEEDING KELAS DEFAULT (ID=1: PTIK C 2024)
-- Mengaitkan ketua_tingkat_user_id = 16 (Ketua Tingkat eksisting akun ketua@simawa.id)
-- ------------------------------------------------------------------------------
INSERT INTO `kelas` (`id`, `nama_kelas`, `angkatan`, `prodi`, `ketua_tingkat_user_id`, `status_aktif`, `dibuat_pada`)
SELECT 1, 'PTIK C 2024', '2024', 'Pendidikan Teknik Informatika dan Komputer', 16, 'aktif', NOW()
WHERE NOT EXISTS (SELECT 1 FROM `kelas` WHERE `id` = 1);

-- Pasang Foreign Key ketua_tingkat_user_id ke users(id)
ALTER TABLE `kelas`
  ADD CONSTRAINT `fk_kelas_ketua_user`
  FOREIGN KEY (`ketua_tingkat_user_id`) REFERENCES `users` (`id`)
  ON DELETE SET NULL ON UPDATE CASCADE;

-- ------------------------------------------------------------------------------
-- 3. TABEL `users`
-- Catatan: kelas_id bernilai NULL khusus untuk Administrator Utama (Superadmin)
-- ------------------------------------------------------------------------------
ALTER TABLE `users` ADD COLUMN `kelas_id` int(11) NULL AFTER `role`;

-- Kaitkan seluruh user lama non-admin ke kelas default 1
UPDATE `users` SET `kelas_id` = 1 WHERE `role` != 'Administrator Utama' AND `kelas_id` IS NULL;

ALTER TABLE `users`
  ADD KEY `idx_users_kelas` (`kelas_id`),
  ADD CONSTRAINT `fk_users_kelas`
  FOREIGN KEY (`kelas_id`) REFERENCES `kelas` (`id`)
  ON DELETE SET NULL ON UPDATE CASCADE;

-- ------------------------------------------------------------------------------
-- 4. TABEL `mahasiswa`
-- ------------------------------------------------------------------------------
ALTER TABLE `mahasiswa` ADD COLUMN `kelas_id` int(11) NULL AFTER `user_id`;

-- Migrasi seluruh biodata mahasiswa lama ke kelas 1
UPDATE `mahasiswa` SET `kelas_id` = 1 WHERE `kelas_id` IS NULL;

-- Kunci kolom menjadi NOT NULL setelah data terisi
ALTER TABLE `mahasiswa` MODIFY COLUMN `kelas_id` int(11) NOT NULL;

ALTER TABLE `mahasiswa`
  ADD KEY `idx_mahasiswa_kelas` (`kelas_id`),
  ADD CONSTRAINT `fk_mahasiswa_kelas`
  FOREIGN KEY (`kelas_id`) REFERENCES `kelas` (`id`)
  ON DELETE CASCADE ON UPDATE CASCADE;

-- ------------------------------------------------------------------------------
-- 5. TABEL `jadwal_matkul`
-- Jadwal kuliah terisolasi per kelas
-- ------------------------------------------------------------------------------
ALTER TABLE `jadwal_matkul` ADD COLUMN `kelas_id` int(11) NULL AFTER `periode_id`;

UPDATE `jadwal_matkul` SET `kelas_id` = 1 WHERE `kelas_id` IS NULL;

ALTER TABLE `jadwal_matkul` MODIFY COLUMN `kelas_id` int(11) NOT NULL;

ALTER TABLE `jadwal_matkul`
  ADD KEY `idx_jadwal_kelas` (`kelas_id`),
  ADD CONSTRAINT `fk_jadwal_kelas`
  FOREIGN KEY (`kelas_id`) REFERENCES `kelas` (`id`)
  ON DELETE CASCADE ON UPDATE CASCADE;

-- ------------------------------------------------------------------------------
-- 6. TABEL `absensi`
-- Presensi pertemuan perkuliahan per kelas
-- ------------------------------------------------------------------------------
ALTER TABLE `absensi` ADD COLUMN `kelas_id` int(11) NULL AFTER `periode_id`;

UPDATE `absensi` SET `kelas_id` = 1 WHERE `kelas_id` IS NULL;

ALTER TABLE `absensi` MODIFY COLUMN `kelas_id` int(11) NOT NULL;

ALTER TABLE `absensi`
  ADD KEY `idx_absensi_kelas` (`kelas_id`),
  ADD CONSTRAINT `fk_absensi_kelas`
  FOREIGN KEY (`kelas_id`) REFERENCES `kelas` (`id`)
  ON DELETE CASCADE ON UPDATE CASCADE;

-- ------------------------------------------------------------------------------
-- 7. TABEL `kas`
-- Setoran kas mahasiswa per kelas
-- ------------------------------------------------------------------------------
ALTER TABLE `kas` ADD COLUMN `kelas_id` int(11) NULL AFTER `periode_id`;

UPDATE `kas` SET `kelas_id` = 1 WHERE `kelas_id` IS NULL;

ALTER TABLE `kas` MODIFY COLUMN `kelas_id` int(11) NOT NULL;

ALTER TABLE `kas`
  ADD KEY `idx_kas_kelas` (`kelas_id`),
  ADD CONSTRAINT `fk_kas_kelas`
  FOREIGN KEY (`kelas_id`) REFERENCES `kelas` (`id`)
  ON DELETE CASCADE ON UPDATE CASCADE;

-- ------------------------------------------------------------------------------
-- 8. TABEL `arus_kas`
-- Pemasukan dan pengeluaran operasional per kelas
-- ------------------------------------------------------------------------------
ALTER TABLE `arus_kas` ADD COLUMN `kelas_id` int(11) NULL AFTER `id`;

UPDATE `arus_kas` SET `kelas_id` = 1 WHERE `kelas_id` IS NULL;

ALTER TABLE `arus_kas` MODIFY COLUMN `kelas_id` int(11) NOT NULL;

ALTER TABLE `arus_kas`
  ADD KEY `idx_aruskas_kelas` (`kelas_id`),
  ADD CONSTRAINT `fk_aruskas_kelas`
  FOREIGN KEY (`kelas_id`) REFERENCES `kelas` (`id`)
  ON DELETE CASCADE ON UPDATE CASCADE;

-- ------------------------------------------------------------------------------
-- 9. TABEL `tugas`
-- Daftar tugas perkuliahan per kelas
-- ------------------------------------------------------------------------------
ALTER TABLE `tugas` ADD COLUMN `kelas_id` int(11) NULL AFTER `periode_id`;

UPDATE `tugas` SET `kelas_id` = 1 WHERE `kelas_id` IS NULL;

ALTER TABLE `tugas` MODIFY COLUMN `kelas_id` int(11) NOT NULL;

ALTER TABLE `tugas`
  ADD KEY `idx_tugas_kelas` (`kelas_id`),
  ADD CONSTRAINT `fk_tugas_kelas`
  FOREIGN KEY (`kelas_id`) REFERENCES `kelas` (`id`)
  ON DELETE CASCADE ON UPDATE CASCADE;

-- ------------------------------------------------------------------------------
-- 10. TABEL `kelompok`
-- Pembagian kelompok tugas per kelas
-- ------------------------------------------------------------------------------
ALTER TABLE `kelompok` ADD COLUMN `kelas_id` int(11) NULL AFTER `periode_id`;

UPDATE `kelompok` SET `kelas_id` = 1 WHERE `kelas_id` IS NULL;

ALTER TABLE `kelompok` MODIFY COLUMN `kelas_id` int(11) NOT NULL;

ALTER TABLE `kelompok`
  ADD KEY `idx_kelompok_kelas` (`kelas_id`),
  ADD CONSTRAINT `fk_kelompok_kelas`
  FOREIGN KEY (`kelas_id`) REFERENCES `kelas` (`id`)
  ON DELETE CASCADE ON UPDATE CASCADE;

-- ------------------------------------------------------------------------------
-- 11. TABEL `piket_proyektor`
-- Giliran piket proyektor per kelas
-- ------------------------------------------------------------------------------
ALTER TABLE `piket_proyektor` ADD COLUMN `kelas_id` int(11) NULL AFTER `periode_id`;

UPDATE `piket_proyektor` SET `kelas_id` = 1 WHERE `kelas_id` IS NULL;

ALTER TABLE `piket_proyektor` MODIFY COLUMN `kelas_id` int(11) NOT NULL;

ALTER TABLE `piket_proyektor`
  ADD KEY `idx_piket_kelas` (`kelas_id`),
  ADD CONSTRAINT `fk_piket_kelas`
  FOREIGN KEY (`kelas_id`) REFERENCES `kelas` (`id`)
  ON DELETE CASCADE ON UPDATE CASCADE;

-- ==============================================================================
-- SELESAI: Seluruh tabel operasional kini terikat secara aman ke `kelas.id`
-- ==============================================================================
