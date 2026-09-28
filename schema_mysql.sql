-- ===================================================
-- DATABASE SCHEMA FOR PHPMYADMIN (MySQL / MariaDB)
-- WargaConnect / SIM RT/RW - 13 TABEL ERD
-- ===================================================

CREATE DATABASE IF NOT EXISTS db_wargaconnect DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE db_wargaconnect;

-- 1. Tabel RT
CREATE TABLE IF NOT EXISTS rt (
    id_rt INT AUTO_INCREMENT PRIMARY KEY,
    nomor_rt VARCHAR(10) NOT NULL,
    rw VARCHAR(10) NOT NULL,
    kelurahan VARCHAR(100) NOT NULL,
    kecamatan VARCHAR(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 2. Tabel Keluarga
CREATE TABLE IF NOT EXISTS keluarga (
    id_keluarga INT AUTO_INCREMENT PRIMARY KEY,
    id_rt INT NOT NULL,
    alamat TEXT NOT NULL,
    kode_pos VARCHAR(10),
    FOREIGN KEY (id_rt) REFERENCES rt(id_rt) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 3. Tabel Warga
CREATE TABLE IF NOT EXISTS warga (
    id_warga INT AUTO_INCREMENT PRIMARY KEY,
    id_keluarga INT NOT NULL,
    nama VARCHAR(150) NOT NULL,
    jenis_kelamin ENUM('L', 'P') NOT NULL,
    no_telepon VARCHAR(20),
    email_username VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    role VARCHAR(50) NOT NULL,
    status_warga VARCHAR(50) NOT NULL,
    FOREIGN KEY (id_keluarga) REFERENCES keluarga(id_keluarga) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 4. Tabel Super Admin
CREATE TABLE IF NOT EXISTS super_admin (
    id_superadmin INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    nama VARCHAR(150) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 5. Tabel Pengumuman
CREATE TABLE IF NOT EXISTS pengumuman (
    id_pengumuman INT AUTO_INCREMENT PRIMARY KEY,
    id_rt INT NOT NULL,
    judul VARCHAR(200) NOT NULL,
    konten TEXT NOT NULL,
    tanggal_publish DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_rt) REFERENCES rt(id_rt) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 6. Tabel Aspirasi / Pengaduan
CREATE TABLE IF NOT EXISTS aspirasi (
    id_pengaduan INT AUTO_INCREMENT PRIMARY KEY,
    id_warga INT NOT NULL,
    judul VARCHAR(200) NOT NULL,
    isi_laporan TEXT NOT NULL,
    foto_bukti VARCHAR(255),
    status ENUM('pending', 'diproses', 'selesai') DEFAULT 'pending',
    FOREIGN KEY (id_warga) REFERENCES warga(id_warga) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 7. Tabel Surat
CREATE TABLE IF NOT EXISTS surat (
    id_surat INT AUTO_INCREMENT PRIMARY KEY,
    id_warga INT NOT NULL,
    jenis_surat VARCHAR(100) NOT NULL,
    tanggal_surat DATE NOT NULL,
    keperluan TEXT NOT NULL,
    no_surat VARCHAR(100),
    status VARCHAR(50) NOT NULL,
    FOREIGN KEY (id_warga) REFERENCES warga(id_warga) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 8. Tabel Inventaris
CREATE TABLE IF NOT EXISTS inventaris (
    id_inventaris INT AUTO_INCREMENT PRIMARY KEY,
    id_rt INT NOT NULL,
    nama_inventaris VARCHAR(150) NOT NULL,
    kategori VARCHAR(100),
    jumlah INT NOT NULL DEFAULT 0,
    satuan VARCHAR(50),
    kondisi ENUM('Baik', 'Rusak') DEFAULT 'Baik',
    lokasi_simpan VARCHAR(150),
    FOREIGN KEY (id_rt) REFERENCES rt(id_rt) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 9. Tabel Kegiatan
CREATE TABLE IF NOT EXISTS kegiatan (
    id_kegiatan INT AUTO_INCREMENT PRIMARY KEY,
    id_rt INT NOT NULL,
    id_warga INT NULL,
    nama_kegiatan VARCHAR(150) NOT NULL,
    tanggal_kegiatan DATETIME NOT NULL,
    lokasi VARCHAR(150),
    keterangan TEXT,
    FOREIGN KEY (id_rt) REFERENCES rt(id_rt) ON DELETE CASCADE,
    FOREIGN KEY (id_warga) REFERENCES warga(id_warga) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 10. Tabel Peserta Kegiatan
CREATE TABLE IF NOT EXISTS peserta_kegiatan (
    id_peserta INT AUTO_INCREMENT PRIMARY KEY,
    id_kegiatan INT NOT NULL,
    id_warga INT NOT NULL,
    status_kehadiran ENUM('hadir', 'izin', 'alfa') DEFAULT 'hadir',
    FOREIGN KEY (id_kegiatan) REFERENCES kegiatan(id_kegiatan) ON DELETE CASCADE,
    FOREIGN KEY (id_warga) REFERENCES warga(id_warga) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 11. Tabel Iuran
CREATE TABLE IF NOT EXISTS iuran (
    id_iuran INT AUTO_INCREMENT PRIMARY KEY,
    id_rt INT NOT NULL,
    nama_iuran VARCHAR(150) NOT NULL,
    nominal DECIMAL(12, 2) NOT NULL,
    jenis_periode VARCHAR(50),
    bulan VARCHAR(20),
    tahun INT,
    keterangan TEXT,
    FOREIGN KEY (id_rt) REFERENCES rt(id_rt) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 12. Tabel Pembayaran
CREATE TABLE IF NOT EXISTS pembayaran (
    id_pembayaran INT AUTO_INCREMENT PRIMARY KEY,
    id_iuran INT NOT NULL,
    id_warga INT NOT NULL,
    tanggal_bayar DATETIME NOT NULL,
    bukti_bayar VARCHAR(255),
    metode_bayar VARCHAR(50),
    jumlah_bayar DECIMAL(12, 2) NOT NULL,
    status_verifikasi ENUM('blm lunas', 'pending', 'Lunas') DEFAULT 'pending',
    FOREIGN KEY (id_iuran) REFERENCES iuran(id_iuran) ON DELETE CASCADE,
    FOREIGN KEY (id_warga) REFERENCES warga(id_warga) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 13. Tabel Keuangan
CREATE TABLE IF NOT EXISTS keuangan (
    id_keuangan INT AUTO_INCREMENT PRIMARY KEY,
    id_rt INT NOT NULL,
    id_warga INT NULL,
    id_inventaris INT NULL,
    tanggal DATETIME NOT NULL,
    jenis ENUM('pemasukan', 'pengeluaran') NOT NULL,
    kategori ENUM('iuran', 'pembelian', 'konsumsi kegiatan', 'lainnya') NOT NULL,
    jumlah DECIMAL(12, 2) NOT NULL,
    keterangan TEXT,
    bukti_nota VARCHAR(255),
    FOREIGN KEY (id_rt) REFERENCES rt(id_rt) ON DELETE CASCADE,
    FOREIGN KEY (id_warga) REFERENCES warga(id_warga) ON DELETE SET NULL,
    FOREIGN KEY (id_inventaris) REFERENCES inventaris(id_inventaris) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ===================================================
-- INITIAL DATA DEMO SEEDING FOR PHPMYADMIN
-- ===================================================

INSERT INTO rt (id_rt, nomor_rt, rw, kelurahan, kecamatan) VALUES
(1, '001', '08', 'Sukamaju', 'Cilodong'),
(2, '002', '08', 'Sukamaju', 'Cilodong'),
(3, '003', '08', 'Sukamaju', 'Cilodong'),
(4, '004', '08', 'Sukamaju', 'Cilodong'),
(5, '005', '08', 'Sukamaju', 'Cilodong'),
(6, '006', '08', 'Sukamaju', 'Cilodong')
ON DUPLICATE KEY UPDATE nomor_rt=VALUES(nomor_rt);

INSERT INTO keluarga (id_keluarga, id_rt, alamat, kode_pos) VALUES
(1, 1, 'Jl. Mawar No. 12, RT 01 / RW 08', '16415'),
(2, 2, 'Jl. Dahlia No. 14, RT 02 / RW 08', '16415'),
(3, 3, 'Jl. Merpati No. 05, RT 03 / RW 08', '16415'),
(4, 4, 'Jl. Anggrek No. 22, RT 04 / RW 08', '16415'),
(5, 5, 'Jl. Melati No. 08, RT 05 / RW 08', '16415')
ON DUPLICATE KEY UPDATE alamat=VALUES(alamat);

-- Password admin: admin123 (werkzeug pbkdf2 hash)
INSERT INTO super_admin (id_superadmin, username, password, nama) VALUES
(1, 'admin', 'scrypt:32768:8:1$NlhF4lRjStHGBx9C$89e90db721d74384d59a8c0ea0415a777e4fbac7df1601a93e3e005085dcf7eeec8d7be69d519b910e53aef172e2cf6fb174b2fb41c2c2f42bc7ea7d307aa5bd', 'Pak Bambang Pamungkas')
ON DUPLICATE KEY UPDATE nama=VALUES(nama);

-- Password warga: warga123 (werkzeug pbkdf2 hash)
INSERT INTO warga (id_warga, id_keluarga, nama, jenis_kelamin, no_telepon, email_username, password, role, status_warga) VALUES
(1, 2, 'Hendra Gunawan', 'L', '081234567890', 'warga', 'scrypt:32768:8:1$NlhF4lRjStHGBx9C$89e90db721d74384d59a8c0ea0415a777e4fbac7df1601a93e3e005085dcf7eeec8d7be69d519b910e53aef172e2cf6fb174b2fb41c2c2f42bc7ea7d307aa5bd', 'Warga', 'Aktif'),
(2, 4, 'Maya Anggraini', 'P', '082198765432', 'maya', 'scrypt:32768:8:1$NlhF4lRjStHGBx9C$89e90db721d74384d59a8c0ea0415a777e4fbac7df1601a93e3e005085dcf7eeec8d7be69d519b910e53aef172e2cf6fb174b2fb41c2c2f42bc7ea7d307aa5bd', 'Warga', 'Aktif'),
(3, 1, 'Agus Wicaksono', 'L', '081311223344', 'agus', 'scrypt:32768:8:1$NlhF4lRjStHGBx9C$89e90db721d74384d59a8c0ea0415a777e4fbac7df1601a93e3e005085dcf7eeec8d7be69d519b910e53aef172e2cf6fb174b2fb41c2c2f42bc7ea7d307aa5bd', 'Ketua RT', 'Aktif')
ON DUPLICATE KEY UPDATE nama=VALUES(nama);

INSERT INTO pengumuman (id_pengumuman, id_rt, judul, konten) VALUES
(1, 2, 'Pemadaman Listrik Sementara', 'Diberitahukan kepada seluruh warga, akan ada pemadaman listrik dari PLN pada hari Sabtu pkl 09:00 - 12:00 WIB.'),
(2, 3, 'Kerja Bakti Rutin & Senam Pagi', 'Mari ramaikan kerja bakti membersihkan saluran air persiapan musim hujan.')
ON DUPLICATE KEY UPDATE judul=VALUES(judul);

INSERT INTO aspirasi (id_pengaduan, id_warga, judul, isi_laporan, foto_bukti, status) VALUES
(1, 1, 'Perbaikan Selokan RT 03', 'Selokan sering mampet saat hujan deras.', NULL, 'selesai'),
(2, 1, 'Ronda Malam Tambahan', 'Mohon jadwal ronda malam ditambah menjelang libur panjang.', NULL, 'diproses')
ON DUPLICATE KEY UPDATE judul=VALUES(judul);

INSERT INTO surat (id_surat, id_warga, jenis_surat, tanggal_surat, keperluan, no_surat, status) VALUES
(1, 1, 'Keterangan Domisili', '2024-05-24', 'Pembuatan KTP baru luar kota', '045/SKD/RT02/V/2024', 'pending'),
(2, 2, 'Keterangan Usaha (SKU)', '2024-05-23', 'Pengajuan KUR Mikro BRI', '044/SKU/RT04/V/2024', 'selesai')
ON DUPLICATE KEY UPDATE jenis_surat=VALUES(jenis_surat);

INSERT INTO inventaris (id_inventaris, id_rt, nama_inventaris, kategori, jumlah, satuan, kondisi, lokasi_simpan) VALUES
(1, 2, 'Tenda Hajatan 4x6m', 'Perlengkapan Event', 2, 'unit', 'Baik', 'Gudang Balai RT 02'),
(2, 3, 'Kursi Plastik Napolly', 'Perlengkapan Event', 50, 'buah', 'Baik', 'Gudang Balai RT 03')
ON DUPLICATE KEY UPDATE nama_inventaris=VALUES(nama_inventaris);

INSERT INTO iuran (id_iuran, id_rt, nama_iuran, nominal, jenis_periode, bulan, tahun, keterangan) VALUES
(1, 2, 'Iuran Sampah & Kebersihan', 30000.00, 'Bulanan', 'Oktober', 2024, 'Pembayaran iuran kebersihan rutin'),
(2, 2, 'Iuran Keamanan & Satpam', 45000.00, 'Bulanan', 'Oktober', 2024, 'Pembayaran gaji hansip')
ON DUPLICATE KEY UPDATE nama_iuran=VALUES(nama_iuran);
