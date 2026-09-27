-- ============================================================
-- LaundryKu - Cadangan Database (skema + data lengkap)
-- Versi LOKAL: otomatis membuat database 'laundry'.
-- Cara pakai: import di phpMyAdmin (atau jalankan install.php).
-- ============================================================

CREATE DATABASE IF NOT EXISTS `laundry` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `laundry`;

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS=0;
SET SQL_MODE='NO_AUTO_VALUE_ON_ZERO';

DROP TABLE IF EXISTS `permintaan_akun`;
DROP TABLE IF EXISTS `log_aktivitas`;
DROP TABLE IF EXISTS `users`;
DROP TABLE IF EXISTS `pengaturan`;
DROP TABLE IF EXISTS `pelanggan`;
DROP TABLE IF EXISTS `paket`;
DROP TABLE IF EXISTS `transaksi`;
DROP TABLE IF EXISTS `transaksi_detail`;
DROP TABLE IF EXISTS `notifikasi`;

CREATE TABLE `users` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `username` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `password_hash` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `nama` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `role` enum('root','manager','staff') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'staff',
  `aktif` tinyint(1) NOT NULL DEFAULT '1',
  `gagal_login` int NOT NULL DEFAULT '0',
  `terkunci_hingga` datetime DEFAULT NULL,
  `last_login` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `users` (`id`, `username`, `password_hash`, `nama`, `role`, `aktif`, `created_at`) VALUES ('1', 'admin', '$2y$10$BToPWZpJFpH.z4c2aEB.JOWHO0NdVSMG8btMW7zc8z8P5sVCIZjLa', 'Administrator', 'root', '1', '2026-09-24 13:29:27');

CREATE TABLE `log_aktivitas` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int unsigned DEFAULT NULL,
  `aksi` varchar(60) COLLATE utf8mb4_unicode_ci NOT NULL,
  `keterangan` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `ip` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `fk_log_user` (`user_id`),
  CONSTRAINT `fk_log_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `pengaturan` (
  `kunci` varchar(60) COLLATE utf8mb4_unicode_ci NOT NULL,
  `nilai` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`kunci`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `pengaturan` (`kunci`, `nilai`, `updated_at`) VALUES ('alamat_usaha', '', '2026-09-24 21:47:28');
INSERT INTO `pengaturan` (`kunci`, `nilai`, `updated_at`) VALUES ('batas_login_gagal', '5', '2026-09-25 00:00:00');
INSERT INTO `pengaturan` (`kunci`, `nilai`, `updated_at`) VALUES ('catatan_struk', '', '2026-09-25 00:00:00');
INSERT INTO `pengaturan` (`kunci`, `nilai`, `updated_at`) VALUES ('diskon_maks_persen', '0', '2026-09-25 00:00:00');
INSERT INTO `pengaturan` (`kunci`, `nilai`, `updated_at`) VALUES ('dp_minimal_persen', '0', '2026-09-25 00:00:00');
INSERT INTO `pengaturan` (`kunci`, `nilai`, `updated_at`) VALUES ('email_usaha', '', '2026-09-25 00:00:00');
INSERT INTO `pengaturan` (`kunci`, `nilai`, `updated_at`) VALUES ('footer_struk', '', '2026-09-24 21:47:28');
INSERT INTO `pengaturan` (`kunci`, `nilai`, `updated_at`) VALUES ('header_struk', '', '2026-09-24 21:47:28');
INSERT INTO `pengaturan` (`kunci`, `nilai`, `updated_at`) VALUES ('instagram', '', '2026-09-25 00:00:00');
INSERT INTO `pengaturan` (`kunci`, `nilai`, `updated_at`) VALUES ('jam_buka', '', '2026-09-25 00:00:00');
INSERT INTO `pengaturan` (`kunci`, `nilai`, `updated_at`) VALUES ('jam_tutup', '', '2026-09-25 00:00:00');
INSERT INTO `pengaturan` (`kunci`, `nilai`, `updated_at`) VALUES ('logo', '', '2026-09-24 21:47:28');
INSERT INTO `pengaturan` (`kunci`, `nilai`, `updated_at`) VALUES ('mata_uang', 'Rp', '2026-09-25 00:00:00');
INSERT INTO `pengaturan` (`kunci`, `nilai`, `updated_at`) VALUES ('min_password', '6', '2026-09-25 00:00:00');
INSERT INTO `pengaturan` (`kunci`, `nilai`, `updated_at`) VALUES ('nama_usaha', '', '2026-09-24 21:47:28');
INSERT INTO `pengaturan` (`kunci`, `nilai`, `updated_at`) VALUES ('notif_suara', '1', '2026-09-26 00:00:00');
INSERT INTO `pengaturan` (`kunci`, `nilai`, `updated_at`) VALUES ('notif_volume', '80', '2026-09-26 00:00:00');
INSERT INTO `pengaturan` (`kunci`, `nilai`, `updated_at`) VALUES ('pajak_persen', '0', '2026-09-25 00:00:00');
INSERT INTO `pengaturan` (`kunci`, `nilai`, `updated_at`) VALUES ('prefix_nota', 'LND', '2026-09-25 00:00:00');
INSERT INTO `pengaturan` (`kunci`, `nilai`, `updated_at`) VALUES ('promo_struk', '', '2026-09-25 00:00:00');
INSERT INTO `pengaturan` (`kunci`, `nilai`, `updated_at`) VALUES ('satuan_berat', 'kg', '2026-09-25 00:00:00');
INSERT INTO `pengaturan` (`kunci`, `nilai`, `updated_at`) VALUES ('sesi_kedaluwarsa_menit', '0', '2026-09-25 00:00:00');
INSERT INTO `pengaturan` (`kunci`, `nilai`, `updated_at`) VALUES ('telepon_usaha', '', '2026-09-24 21:47:28');
INSERT INTO `pengaturan` (`kunci`, `nilai`, `updated_at`) VALUES ('whatsapp', '', '2026-09-25 00:00:00');

CREATE TABLE `pelanggan` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `nama` varchar(120) COLLATE utf8mb4_unicode_ci NOT NULL,
  `telepon` varchar(30) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `alamat` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `pelanggan` (`id`, `nama`, `telepon`, `alamat`, `created_at`) VALUES ('1', 'Budi Santoso', '081234567890', 'Jl. Melati No. 12, Jakarta', '2026-09-24 13:29:27');
INSERT INTO `pelanggan` (`id`, `nama`, `telepon`, `alamat`, `created_at`) VALUES ('2', 'Siti Rahmawati', '081298765432', 'Jl. Flamboyan No. 3, Depok', '2026-09-24 13:29:27');
INSERT INTO `pelanggan` (`id`, `nama`, `telepon`, `alamat`, `created_at`) VALUES ('3', 'Andi Wijaya', '082123456789', 'Perum Griya Asri Blok C5, Bekasi', '2026-09-24 13:29:27');
INSERT INTO `pelanggan` (`id`, `nama`, `telepon`, `alamat`, `created_at`) VALUES ('6', 'Arga Abiyyu', '080987654321', 'Jl. Lintas Sulit Air - Tanjung Alai, No. 19, Solok', '2026-09-24 21:23:28');

CREATE TABLE `paket` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `nama` varchar(120) COLLATE utf8mb4_unicode_ci NOT NULL,
  `harga_per_kg` decimal(12,2) NOT NULL DEFAULT '0.00',
  `estimasi_hari` int NOT NULL DEFAULT '2',
  `keterangan` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `paket` (`id`, `nama`, `harga_per_kg`, `estimasi_hari`, `keterangan`, `created_at`) VALUES ('1', 'Cuci Reguler', '7000.00', '2', 'Cuci + pengering, tanpa setrika', '2026-09-24 13:29:27');
INSERT INTO `paket` (`id`, `nama`, `harga_per_kg`, `estimasi_hari`, `keterangan`, `created_at`) VALUES ('2', 'Cuci Express', '10000.00', '1', 'Selesai 1 hari, prioritas', '2026-09-24 13:29:27');
INSERT INTO `paket` (`id`, `nama`, `harga_per_kg`, `estimasi_hari`, `keterangan`, `created_at`) VALUES ('3', 'Cuci + Setrika', '12000.00', '3', 'Cuci dan setrika rapi', '2026-09-24 13:29:27');
INSERT INTO `paket` (`id`, `nama`, `harga_per_kg`, `estimasi_hari`, `keterangan`, `created_at`) VALUES ('4', 'Setrika Saja', '8000.00', '1', 'Khusus setrika', '2026-09-24 13:29:27');

CREATE TABLE `transaksi` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `no_nota` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL,
  `pelanggan_id` int unsigned NOT NULL,
  `tanggal_masuk` date NOT NULL,
  `estimasi_selesai` date DEFAULT NULL,
  `tanggal_selesai` date DEFAULT NULL,
  `total_berat` decimal(10,2) NOT NULL DEFAULT '0.00',
  `total_harga` decimal(12,2) NOT NULL DEFAULT '0.00',
  `diskon` decimal(12,2) NOT NULL DEFAULT '0.00',
  `pajak` decimal(12,2) NOT NULL DEFAULT '0.00',
  `uang_muka` decimal(12,2) NOT NULL DEFAULT '0.00',
  `status` enum('diterima','dicuci','disetrika','siap_diambil','selesai') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'diterima',
  `status_bayar` enum('belum','lunas') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'belum',
  `keterangan` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `no_nota` (`no_nota`),
  KEY `fk_trx_pelanggan` (`pelanggan_id`),
  CONSTRAINT `fk_trx_pelanggan` FOREIGN KEY (`pelanggan_id`) REFERENCES `pelanggan` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `transaksi` (`id`, `no_nota`, `pelanggan_id`, `tanggal_masuk`, `estimasi_selesai`, `tanggal_selesai`, `total_berat`, `total_harga`, `uang_muka`, `status`, `status_bayar`, `keterangan`, `created_at`) VALUES ('1', 'LND-20260924-001', '1', '2026-09-24', '2026-09-26', '2026-09-24', '5.00', '35000.00', '35000.00', 'siap_diambil', 'lunas', NULL, '2026-09-24 13:29:27');
INSERT INTO `transaksi` (`id`, `no_nota`, `pelanggan_id`, `tanggal_masuk`, `estimasi_selesai`, `tanggal_selesai`, `total_berat`, `total_harga`, `uang_muka`, `status`, `status_bayar`, `keterangan`, `created_at`) VALUES ('2', 'LND-20260923-001', '2', '2026-09-23', '2026-09-26', NULL, '8.00', '64000.00', '20000.00', 'dicuci', 'belum', NULL, '2026-09-24 13:29:27');
INSERT INTO `transaksi` (`id`, `no_nota`, `pelanggan_id`, `tanggal_masuk`, `estimasi_selesai`, `tanggal_selesai`, `total_berat`, `total_harga`, `uang_muka`, `status`, `status_bayar`, `keterangan`, `created_at`) VALUES ('3', 'LND-20260922-001', '3', '2026-09-22', '2026-09-25', '2026-09-23', '3.50', '42000.00', '42000.00', 'selesai', 'lunas', NULL, '2026-09-24 13:29:27');
INSERT INTO `transaksi` (`id`, `no_nota`, `pelanggan_id`, `tanggal_masuk`, `estimasi_selesai`, `tanggal_selesai`, `total_berat`, `total_harga`, `uang_muka`, `status`, `status_bayar`, `keterangan`, `created_at`) VALUES ('4', 'LND-20260924-002', '1', '2026-09-24', '2026-09-27', NULL, '5.00', '45000.00', '45000.00', 'disetrika', 'lunas', 'uji otomatis', '2026-09-24 13:30:10');
INSERT INTO `transaksi` (`id`, `no_nota`, `pelanggan_id`, `tanggal_masuk`, `estimasi_selesai`, `tanggal_selesai`, `total_berat`, `total_harga`, `uang_muka`, `status`, `status_bayar`, `keterangan`, `created_at`) VALUES ('5', 'LND-20260924-003', '1', '2026-09-24', '2026-09-25', NULL, '2.00', '24000.00', '0.00', 'diterima', 'belum', '', '2026-09-24 22:01:49');

CREATE TABLE `transaksi_detail` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `transaksi_id` int unsigned NOT NULL,
  `paket_id` int unsigned NOT NULL,
  `berat` decimal(10,2) NOT NULL DEFAULT '0.00',
  `subtotal` decimal(12,2) NOT NULL DEFAULT '0.00',
  PRIMARY KEY (`id`),
  KEY `fk_det_trx` (`transaksi_id`),
  KEY `fk_det_paket` (`paket_id`),
  CONSTRAINT `fk_det_paket` FOREIGN KEY (`paket_id`) REFERENCES `paket` (`id`),
  CONSTRAINT `fk_det_trx` FOREIGN KEY (`transaksi_id`) REFERENCES `transaksi` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=19 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `transaksi_detail` (`id`, `transaksi_id`, `paket_id`, `berat`, `subtotal`) VALUES ('1', '1', '1', '5.00', '35000.00');
INSERT INTO `transaksi_detail` (`id`, `transaksi_id`, `paket_id`, `berat`, `subtotal`) VALUES ('2', '2', '4', '8.00', '64000.00');
INSERT INTO `transaksi_detail` (`id`, `transaksi_id`, `paket_id`, `berat`, `subtotal`) VALUES ('3', '3', '3', '3.50', '42000.00');
INSERT INTO `transaksi_detail` (`id`, `transaksi_id`, `paket_id`, `berat`, `subtotal`) VALUES ('4', '4', '1', '3.00', '21000.00');
INSERT INTO `transaksi_detail` (`id`, `transaksi_id`, `paket_id`, `berat`, `subtotal`) VALUES ('5', '5', '3', '2.00', '24000.00');

CREATE TABLE `notifikasi` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `jenis` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'info',
  `judul` varchar(120) COLLATE utf8mb4_unicode_ci NOT NULL,
  `pesan` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `user_id` int unsigned DEFAULT NULL,
  `pengirim` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `tautan` varchar(120) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `dibaca` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_notif_penerima` (`user_id`),
  CONSTRAINT `fk_notif_penerima` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `notifikasi` (`id`, `jenis`, `judul`, `pesan`, `tautan`, `dibaca`, `created_at`) VALUES ('1', 'info', 'Selamat Datang di LaundryKu', 'Aplikasi siap dipakai. Mulai dengan menambah pelanggan baru dan membuat nota transaksi.', 'dashboard', '0', '2026-09-25 06:44:31');
INSERT INTO `notifikasi` (`id`, `jenis`, `judul`, `pesan`, `tautan`, `dibaca`, `created_at`) VALUES ('2', 'info', 'Fitur Notifikasi Aktif', 'Lonceng di pojok kanan atas menampilkan notifikasi transaksi baru, perubahan status, dan pembayaran secara realtime.', 'notifikasi', '0', '2026-09-25 06:44:31');

CREATE TABLE `permintaan_akun` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `jenis` enum('toko') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'toko',
  `nama_toko` varchar(120) COLLATE utf8mb4_unicode_ci NOT NULL,
  `nama_pemohon` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `username` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `password_hash` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `telepon` varchar(30) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `alamat` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `catatan` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` enum('menunggu','disetujui','ditolak') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'menunggu',
  `diproses_oleh` int unsigned DEFAULT NULL,
  `diproses_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_perm_username` (`username`),
  KEY `fk_perm_proses` (`diproses_oleh`),
  CONSTRAINT `fk_perm_proses` FOREIGN KEY (`diproses_oleh`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS=1;
