-- Migration: 003_drop_foto_columns_and_enable_initials_avatar.sql
-- Description: Drop column `foto` from `users` and `mahasiswa` to use automated initials avatar generation.

ALTER TABLE `users` DROP COLUMN IF EXISTS `foto`;
ALTER TABLE `mahasiswa` DROP COLUMN IF EXISTS `foto`;
