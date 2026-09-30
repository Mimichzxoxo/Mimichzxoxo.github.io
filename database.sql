-- =====================================================
-- SIM RT/RW - Smart RT Database Schema & Stored Procedures
-- Compatible with MySQL 5.7+ / 8.0+ / MariaDB
-- =====================================================

CREATE DATABASE IF NOT EXISTS `smart_rt_db` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `smart_rt_db`;

-- -----------------------------------------------------
-- Drop tables if exists (Order handles Foreign Keys)
-- -----------------------------------------------------
SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS `keuangan`;
DROP TABLE IF EXISTS `pembayaran`;
DROP TABLE IF EXISTS `iuran`;
DROP TABLE IF EXISTS `peserta_kegiatan`;
DROP TABLE IF EXISTS `kegiatan`;
DROP TABLE IF EXISTS `inventaris`;
DROP TABLE IF EXISTS `surat`;
DROP TABLE IF EXISTS `aspirasi`;
DROP TABLE IF EXISTS `pengumuman`;
DROP TABLE IF EXISTS `super_admin`;
DROP TABLE IF EXISTS `warga`;
DROP TABLE IF EXISTS `keluarga`;
DROP TABLE IF EXISTS `rt`;
SET FOREIGN_KEY_CHECKS = 1;

-- -----------------------------------------------------
-- 1. Tabel RT
-- -----------------------------------------------------
CREATE TABLE `rt` (
    `id_rt` INT AUTO_INCREMENT PRIMARY KEY,
    `nomor_rt` VARCHAR(10) NOT NULL,
    `rw` VARCHAR(10) NOT NULL,
    `kelurahan` VARCHAR(100) NOT NULL,
    `kecamatan` VARCHAR(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- -----------------------------------------------------
-- 2. Tabel Keluarga
-- -----------------------------------------------------
CREATE TABLE `keluarga` (
    `id_keluarga` INT AUTO_INCREMENT PRIMARY KEY,
    `id_rt` INT NOT NULL,
    `alamat` TEXT NOT NULL,
    `kode_pos` VARCHAR(10),
    FOREIGN KEY (`id_rt`) REFERENCES `rt`(`id_rt`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- -----------------------------------------------------
-- 3. Tabel Warga
-- -----------------------------------------------------
CREATE TABLE `warga` (
    `id_warga` INT AUTO_INCREMENT PRIMARY KEY,
    `id_keluarga` INT NOT NULL,
    `nama` VARCHAR(150) NOT NULL,
    `jenis_kelamin` ENUM('L', 'P') NOT NULL,
    `no_telepon` VARCHAR(20),
    `email_username` VARCHAR(100) UNIQUE NOT NULL,
    `password` VARCHAR(255) NOT NULL,
    `role` VARCHAR(50) NOT NULL,
    `status_warga` VARCHAR(50) NOT NULL,
    FOREIGN KEY (`id_keluarga`) REFERENCES `keluarga`(`id_keluarga`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- -----------------------------------------------------
-- 4. Tabel Super Admin
-- -----------------------------------------------------
CREATE TABLE `super_admin` (
    `id_superadmin` INT AUTO_INCREMENT PRIMARY KEY,
    `username` VARCHAR(100) UNIQUE NOT NULL,
    `password` VARCHAR(255) NOT NULL,
    `nama` VARCHAR(150) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- -----------------------------------------------------
-- 5. Tabel Pengumuman
-- -----------------------------------------------------
CREATE TABLE `pengumuman` (
    `id_pengumuman` INT AUTO_INCREMENT PRIMARY KEY,
    `id_rt` INT NOT NULL,
    `judul` VARCHAR(200) NOT NULL,
    `konten` TEXT NOT NULL,
    `tanggal_publish` DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`id_rt`) REFERENCES `rt`(`id_rt`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- -----------------------------------------------------
-- 6. Tabel Aspirasi / Pengaduan
-- -----------------------------------------------------
CREATE TABLE `aspirasi` (
    `id_pengaduan` INT AUTO_INCREMENT PRIMARY KEY,
    `id_warga` INT NOT NULL,
    `judul` VARCHAR(200) NOT NULL,
    `isi_laporan` TEXT NOT NULL,
    `foto_bukti` VARCHAR(255),
    `status` ENUM('pending', 'diproses', 'selesai') DEFAULT 'pending',
    FOREIGN KEY (`id_warga`) REFERENCES `warga`(`id_warga`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- -----------------------------------------------------
-- 7. Tabel Surat
-- -----------------------------------------------------
CREATE TABLE `surat` (
    `id_surat` INT AUTO_INCREMENT PRIMARY KEY,
    `id_warga` INT NOT NULL,
    `jenis_surat` VARCHAR(100) NOT NULL,
    `tanggal_surat` DATE NOT NULL,
    `keperluan` TEXT NOT NULL,
    `no_surat` VARCHAR(100),
    `status` VARCHAR(50) NOT NULL,
    FOREIGN KEY (`id_warga`) REFERENCES `warga`(`id_warga`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- -----------------------------------------------------
-- 8. Tabel Inventaris
-- -----------------------------------------------------
CREATE TABLE `inventaris` (
    `id_inventaris` INT AUTO_INCREMENT PRIMARY KEY,
    `id_rt` INT NOT NULL,
    `nama_inventaris` VARCHAR(150) NOT NULL,
    `kategori` VARCHAR(100),
    `jumlah` INT NOT NULL DEFAULT 0,
    `satuan` VARCHAR(50),
    `kondisi` ENUM('Baik', 'Rusak') DEFAULT 'Baik',
    `lokasi_simpan` VARCHAR(150),
    FOREIGN KEY (`id_rt`) REFERENCES `rt`(`id_rt`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- -----------------------------------------------------
-- 9. Tabel Kegiatan
-- -----------------------------------------------------
CREATE TABLE `kegiatan` (
    `id_kegiatan` INT AUTO_INCREMENT PRIMARY KEY,
    `id_rt` INT NOT NULL,
    `id_warga` INT, -- Penanggung Jawab (PJ)
    `nama_kegiatan` VARCHAR(150) NOT NULL,
    `tanggal_kegiatan` DATETIME NOT NULL,
    `lokasi` VARCHAR(150),
    `keterangan` TEXT,
    FOREIGN KEY (`id_rt`) REFERENCES `rt`(`id_rt`) ON DELETE CASCADE,
    FOREIGN KEY (`id_warga`) REFERENCES `warga`(`id_warga`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- -----------------------------------------------------
-- 10. Tabel Peserta Kegiatan
-- -----------------------------------------------------
CREATE TABLE `peserta_kegiatan` (
    `id_peserta` INT AUTO_INCREMENT PRIMARY KEY,
    `id_kegiatan` INT NOT NULL,
    `id_warga` INT NOT NULL,
    `status_kehadiran` ENUM('hadir', 'izin', 'alfa') DEFAULT 'hadir',
    FOREIGN KEY (`id_kegiatan`) REFERENCES `kegiatan`(`id_kegiatan`) ON DELETE CASCADE,
    FOREIGN KEY (`id_warga`) REFERENCES `warga`(`id_warga`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- -----------------------------------------------------
-- 11. Tabel Iuran
-- -----------------------------------------------------
CREATE TABLE `iuran` (
    `id_iuran` INT AUTO_INCREMENT PRIMARY KEY,
    `id_rt` INT NOT NULL,
    `nama_iuran` VARCHAR(150) NOT NULL,
    `nominal` DECIMAL(12, 2) NOT NULL,
    `jenis_periode` VARCHAR(50),
    `bulan` VARCHAR(20),
    `tahun` INT,
    `keterangan` TEXT,
    FOREIGN KEY (`id_rt`) REFERENCES `rt`(`id_rt`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- -----------------------------------------------------
-- 12. Tabel Pembayaran
-- -----------------------------------------------------
CREATE TABLE `pembayaran` (
    `id_pembayaran` INT AUTO_INCREMENT PRIMARY KEY,
    `id_iuran` INT NOT NULL,
    `id_warga` INT NOT NULL,
    `tanggal_bayar` DATETIME NOT NULL,
    `bukti_bayar` VARCHAR(255),
    `metode_bayar` VARCHAR(50),
    `jumlah_bayar` DECIMAL(12, 2) NOT NULL,
    `status_verifikasi` ENUM('blm lunas', 'pending', 'Lunas') DEFAULT 'pending',
    FOREIGN KEY (`id_iuran`) REFERENCES `iuran`(`id_iuran`) ON DELETE CASCADE,
    FOREIGN KEY (`id_warga`) REFERENCES `warga`(`id_warga`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- -----------------------------------------------------
-- 13. Tabel Keuangan
-- -----------------------------------------------------
CREATE TABLE `keuangan` (
    `id_keuangan` INT AUTO_INCREMENT PRIMARY KEY,
    `id_rt` INT NOT NULL,
    `id_warga` INT,
    `id_inventaris` INT,
    `tanggal` DATETIME NOT NULL,
    `jenis` ENUM('pemasukan', 'pengeluaran') NOT NULL,
    `kategori` ENUM('iuran', 'pembelian', 'konsumsi kegiatan', 'lainnya') NOT NULL,
    `jumlah` DECIMAL(12, 2) NOT NULL,
    `keterangan` TEXT,
    `bukti_nota` VARCHAR(255),
    FOREIGN KEY (`id_rt`) REFERENCES `rt`(`id_rt`) ON DELETE CASCADE,
    FOREIGN KEY (`id_warga`) REFERENCES `warga`(`id_warga`) ON DELETE SET NULL,
    FOREIGN KEY (`id_inventaris`) REFERENCES `inventaris`(`id_inventaris`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =====================================================
-- DATABASE TRIGGERS
-- =====================================================

DROP TRIGGER IF EXISTS `trg_auto_no_surat`;
DROP TRIGGER IF EXISTS `trg_auto_keuangan_pembayaran`;
DROP TRIGGER IF EXISTS `trg_default_status_warga`;

DELIMITER //

-- Trigger 1: Otomatis generate Nomor Surat saat Surat Disetujui / Di-update
CREATE TRIGGER `trg_auto_no_surat`
BEFORE UPDATE ON `surat`
FOR EACH ROW
BEGIN
    IF NEW.`status` LIKE '%Lunas%' OR NEW.`status` LIKE '%Selesai%' THEN
        IF NEW.`no_surat` IS NULL OR NEW.`no_surat` = '' THEN
            SET NEW.`no_surat` = CONCAT('045/', NEW.`id_surat`, '/SP/RT02/', DATE_FORMAT(NOW(), '%m/%Y'));
        END IF;
    END IF;
END //

-- Trigger 2: Otomatis mencatat transaksi Pemasukan Keuangan saat Iuran Lunas
CREATE TRIGGER `trg_auto_keuangan_pembayaran`
AFTER UPDATE ON `pembayaran`
FOR EACH ROW
BEGIN
    DECLARE `v_id_rt` INT;
    DECLARE `v_nama_iuran` VARCHAR(150);

    IF NEW.`status_verifikasi` = 'Lunas' AND OLD.`status_verifikasi` != 'Lunas' THEN
        SELECT `id_rt`, `nama_iuran` INTO `v_id_rt`, `v_nama_iuran`
        FROM `iuran` WHERE `id_iuran` = NEW.`id_iuran`;

        INSERT INTO `keuangan` (
            `id_rt`, `id_warga`, `id_inventaris`, `tanggal`, `jenis`, `kategori`, `jumlah`, `keterangan`, `bukti_nota`
        ) VALUES (
            `v_id_rt`, NEW.`id_warga`, NULL, NOW(), 'pemasukan', 'iuran', NEW.`jumlah_bayar`, CONCAT('Trigger Auto-Kas: Iuran ', `v_nama_iuran`), NULL
        );
    END IF;
END //

-- Trigger 3: Validasi status warga default saat pendaftaran
CREATE TRIGGER `trg_default_status_warga`
BEFORE INSERT ON `warga`
FOR EACH ROW
BEGIN
    IF NEW.`status_warga` IS NULL OR NEW.`status_warga` = '' THEN
        SET NEW.`status_warga` = 'Tetap';
    END IF;
END //

DELIMITER ;

-- =====================================================
-- STORED PROCEDURES
-- =====================================================

DROP PROCEDURE IF EXISTS `sp_TambahWarga`;
DROP PROCEDURE IF EXISTS `sp_CatatIuran`;
DROP PROCEDURE IF EXISTS `sp_VerifikasiIuran`;
DROP PROCEDURE IF EXISTS `sp_AjukanSuratPengantar`;
DROP PROCEDURE IF EXISTS `sp_GetDashboardSuperAdmin`;

DELIMITER //

-- 1. SP Tambah Warga
CREATE PROCEDURE `sp_TambahWarga`(
    IN `p_id_rt` INT,
    IN `p_id_keluarga` INT,
    IN `p_nama` VARCHAR(150),
    IN `p_jenis_kelamin` ENUM('L', 'P'),
    IN `p_no_telepon` VARCHAR(20),
    IN `p_email_username` VARCHAR(100),
    IN `p_password` VARCHAR(255),
    IN `p_role` VARCHAR(50),
    IN `p_status_warga` VARCHAR(50)
)
BEGIN
    INSERT INTO `warga` (
        `id_keluarga`, `nama`, `jenis_kelamin`, `no_telepon`, `email_username`, `password`, `role`, `status_warga`
    ) 
    VALUES (
        `p_id_keluarga`, `p_nama`, `p_jenis_kelamin`, `p_no_telepon`, `p_email_username`, `p_password`, `p_role`, `p_status_warga`
    );
    
    SELECT LAST_INSERT_ID() AS new_id_warga, 'Warga berhasil ditambahkan via SP' AS pesan;
END //

-- 2. SP Catat Iuran
CREATE PROCEDURE `sp_CatatIuran`(
    IN `p_id_iuran` INT,
    IN `p_id_warga` INT,
    IN `p_tanggal_bayar` DATETIME,
    IN `p_bukti_bayar` VARCHAR(255),
    IN `p_metode_bayar` VARCHAR(50),
    IN `p_jumlah_bayar` DECIMAL(12, 2)
)
BEGIN
    INSERT INTO `pembayaran` (
        `id_iuran`, `id_warga`, `tanggal_bayar`, `bukti_bayar`, `metode_bayar`, `jumlah_bayar`, `status_verifikasi`
    ) 
    VALUES (
        `p_id_iuran`, `p_id_warga`, `p_tanggal_bayar`, `p_bukti_bayar`, `p_metode_bayar`, `p_jumlah_bayar`, 'pending'
    );
    
    SELECT LAST_INSERT_ID() AS new_id_pembayaran, 'Pembayaran iuran berhasil dicatat via SP' AS pesan;
END //

-- 3. SP Verifikasi Iuran
CREATE PROCEDURE `sp_VerifikasiIuran`(
    IN `p_id_pembayaran` INT,
    IN `p_status_baru` ENUM('blm lunas', 'pending', 'Lunas')
)
BEGIN
    UPDATE `pembayaran`
    SET `status_verifikasi` = `p_status_baru`
    WHERE `id_pembayaran` = `p_id_pembayaran`;

    SELECT 'Verifikasi iuran berhasil diperbarui via SP' AS pesan;
END //

-- 4. SP Ajukan Surat Pengantar
CREATE PROCEDURE `sp_AjukanSuratPengantar`(
    IN `p_id_warga` INT,
    IN `p_jenis_surat` VARCHAR(100),
    IN `p_keperluan` TEXT
)
BEGIN
    INSERT INTO `surat` (
        `id_warga`, `jenis_surat`, `tanggal_surat`, `keperluan`, `no_surat`, `status`
    ) 
    VALUES (
        `p_id_warga`, `p_jenis_surat`, CURDATE(), `p_keperluan`, NULL, 'Menunggu Validasi'
    );

    SELECT LAST_INSERT_ID() AS new_id_surat, 'Permohonan surat pengantar berhasil dikirim via SP' AS pesan;
END //

-- 5. SP Get Dashboard Super Admin
CREATE PROCEDURE `sp_GetDashboardSuperAdmin`(
    IN `p_bulan` VARCHAR(20),
    IN `p_tahun` INT
)
BEGIN
    SELECT 
        `rt`.`id_rt`,
        `rt`.`nomor_rt`,
        `rt`.`rw`,
        `rt`.`kelurahan`,
        `rt`.`kecamatan`,
        COUNT(DISTINCT `w`.`id_warga`) AS `total_warga`,
        COUNT(DISTINCT `k`.`id_keluarga`) AS `total_keluarga`,
        COALESCE(SUM(CASE WHEN `p`.`status_verifikasi` = 'Lunas' THEN `p`.`jumlah_bayar` ELSE 0 END), 0) AS `total_iuran_terkumpul`,
        SUM(CASE WHEN `p`.`status_verifikasi` = 'pending' THEN 1 ELSE 0 END) AS `antrean_verifikasi`
    FROM `rt`
    LEFT JOIN `keluarga` `k` ON `rt`.`id_rt` = `k`.`id_rt`
    LEFT JOIN `warga` `w` ON `k`.`id_keluarga` = `w`.`id_keluarga`
    LEFT JOIN `iuran` `i` ON `rt`.`id_rt` = `i`.`id_rt` AND (`p_bulan` IS NULL OR `i`.`bulan` = `p_bulan`) AND (`p_tahun` IS NULL OR `i`.`tahun` = `p_tahun`)
    LEFT JOIN `pembayaran` `p` ON `i`.`id_iuran` = `p`.`id_iuran`
    GROUP BY `rt`.`id_rt`, `rt`.`nomor_rt`, `rt`.`rw`, `rt`.`kelurahan`, `rt`.`kecamatan`;
END //

DELIMITER ;

-- =====================================================
-- DCL (DATA CONTROL LANGUAGE) - PRIVILEGES & ACCOUNTS MANAGEMENT
-- =====================================================

-- 1. Create User Accounts for Roles
CREATE USER IF NOT EXISTS 'warga_user'@'localhost' IDENTIFIED BY 'warga_pass_123';
CREATE USER IF NOT EXISTS 'admin_rt_user'@'localhost' IDENTIFIED BY 'admin_rt_pass_123';
CREATE USER IF NOT EXISTS 'superadmin_user'@'localhost' IDENTIFIED BY 'superadmin_pass_123';

-- 2. Grant DCL Privileges for 'warga_user' (Limited Citizen Access)
GRANT SELECT, INSERT, UPDATE ON `smart_rt_db`.`aspirasi` TO 'warga_user'@'localhost';
GRANT SELECT, INSERT ON `smart_rt_db`.`surat` TO 'warga_user'@'localhost';
GRANT SELECT, INSERT, UPDATE ON `smart_rt_db`.`pembayaran` TO 'warga_user'@'localhost';
GRANT SELECT ON `smart_rt_db`.`pengumuman` TO 'warga_user'@'localhost';
GRANT SELECT ON `smart_rt_db`.`kegiatan` TO 'warga_user'@'localhost';
GRANT SELECT, INSERT ON `smart_rt_db`.`peserta_kegiatan` TO 'warga_user'@'localhost';
GRANT SELECT ON `smart_rt_db`.`iuran` TO 'warga_user'@'localhost';

-- 3. Grant DCL Privileges for 'admin_rt_user' (Full Operational RT Level Access & SP Execution)
GRANT SELECT, INSERT, UPDATE, DELETE ON `smart_rt_db`.* TO 'admin_rt_user'@'localhost';
GRANT EXECUTE ON PROCEDURE `smart_rt_db`.`sp_TambahWarga` TO 'admin_rt_user'@'localhost';
GRANT EXECUTE ON PROCEDURE `smart_rt_db`.`sp_CatatIuran` TO 'admin_rt_user'@'localhost';
GRANT EXECUTE ON PROCEDURE `smart_rt_db`.`sp_VerifikasiIuran` TO 'admin_rt_user'@'localhost';
GRANT EXECUTE ON PROCEDURE `smart_rt_db`.`sp_AjukanSuratPengantar` TO 'admin_rt_user'@'localhost';

-- 4. Grant DCL Privileges for 'superadmin_user' (Full Database Control with Grant Option)
GRANT ALL PRIVILEGES ON `smart_rt_db`.* TO 'superadmin_user'@'localhost' WITH GRANT OPTION;

-- 5. Apply Privileges
FLUSH PRIVILEGES;
