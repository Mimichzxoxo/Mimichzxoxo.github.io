-- DDL ERD WargaConnect / SIM RT/RW

-- Tabel RT
CREATE TABLE IF NOT EXISTS rt (
    id_rt INTEGER PRIMARY KEY AUTOINCREMENT,
    nomor_rt VARCHAR(10) NOT NULL,
    rw VARCHAR(10) NOT NULL,
    kelurahan VARCHAR(100) NOT NULL,
    kecamatan VARCHAR(100) NOT NULL
);

-- Tabel Keluarga
CREATE TABLE IF NOT EXISTS keluarga (
    id_keluarga INTEGER PRIMARY KEY AUTOINCREMENT,
    id_rt INTEGER NOT NULL,
    alamat TEXT NOT NULL,
    kode_pos VARCHAR(10),
    FOREIGN KEY (id_rt) REFERENCES rt(id_rt) ON DELETE CASCADE
);

-- Tabel Warga
CREATE TABLE IF NOT EXISTS warga (
    id_warga INTEGER PRIMARY KEY AUTOINCREMENT,
    id_keluarga INTEGER NOT NULL,
    nama VARCHAR(150) NOT NULL,
    jenis_kelamin TEXT CHECK(jenis_kelamin IN ('L', 'P')) NOT NULL,
    no_telepon VARCHAR(20),
    email_username VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    role VARCHAR(50) NOT NULL,
    status_warga VARCHAR(50) NOT NULL,
    FOREIGN KEY (id_keluarga) REFERENCES keluarga(id_keluarga) ON DELETE CASCADE
);

-- Tabel Super Admin
CREATE TABLE IF NOT EXISTS super_admin (
    id_superadmin INTEGER PRIMARY KEY AUTOINCREMENT,
    username VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    nama VARCHAR(150) NOT NULL
);

-- Tabel Pengumuman
CREATE TABLE IF NOT EXISTS pengumuman (
    id_pengumuman INTEGER PRIMARY KEY AUTOINCREMENT,
    id_rt INTEGER NOT NULL,
    judul VARCHAR(200) NOT NULL,
    konten TEXT NOT NULL,
    tanggal_publish DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_rt) REFERENCES rt(id_rt) ON DELETE CASCADE
);

-- Tabel Aspirasi / Pengaduan
CREATE TABLE IF NOT EXISTS aspirasi (
    id_pengaduan INTEGER PRIMARY KEY AUTOINCREMENT,
    id_warga INTEGER NOT NULL,
    judul VARCHAR(200) NOT NULL,
    isi_laporan TEXT NOT NULL,
    foto_bukti VARCHAR(255),
    status TEXT CHECK(status IN ('pending', 'diproses', 'selesai')) DEFAULT 'pending',
    FOREIGN KEY (id_warga) REFERENCES warga(id_warga) ON DELETE CASCADE
);

-- Tabel Surat
CREATE TABLE IF NOT EXISTS surat (
    id_surat INTEGER PRIMARY KEY AUTOINCREMENT,
    id_warga INTEGER NOT NULL,
    jenis_surat VARCHAR(100) NOT NULL,
    tanggal_surat DATE NOT NULL,
    keperluan TEXT NOT NULL,
    no_surat VARCHAR(100),
    status VARCHAR(50) NOT NULL,
    FOREIGN KEY (id_warga) REFERENCES warga(id_warga) ON DELETE CASCADE
);

-- Tabel Inventaris
CREATE TABLE IF NOT EXISTS inventaris (
    id_inventaris INTEGER PRIMARY KEY AUTOINCREMENT,
    id_rt INTEGER NOT NULL,
    nama_inventaris VARCHAR(150) NOT NULL,
    kategori VARCHAR(100),
    jumlah INTEGER NOT NULL DEFAULT 0,
    satuan VARCHAR(50),
    kondisi TEXT CHECK(kondisi IN ('Baik', 'Rusak')) DEFAULT 'Baik',
    lokasi_simpan VARCHAR(150),
    FOREIGN KEY (id_rt) REFERENCES rt(id_rt) ON DELETE CASCADE
);

-- Tabel Kegiatan
CREATE TABLE IF NOT EXISTS kegiatan (
    id_kegiatan INTEGER PRIMARY KEY AUTOINCREMENT,
    id_rt INTEGER NOT NULL,
    id_warga INTEGER, -- Untuk Penanggung Jawab (PJ)
    nama_kegiatan VARCHAR(150) NOT NULL,
    tanggal_kegiatan DATETIME NOT NULL,
    lokasi VARCHAR(150),
    keterangan TEXT,
    FOREIGN KEY (id_rt) REFERENCES rt(id_rt) ON DELETE CASCADE,
    FOREIGN KEY (id_warga) REFERENCES warga(id_warga) ON DELETE SET NULL
);

-- Tabel Peserta Kegiatan
CREATE TABLE IF NOT EXISTS peserta_kegiatan (
    id_peserta INTEGER PRIMARY KEY AUTOINCREMENT,
    id_kegiatan INTEGER NOT NULL,
    id_warga INTEGER NOT NULL,
    status_kehadiran TEXT CHECK(status_kehadiran IN ('hadir', 'izin', 'alfa')) DEFAULT 'hadir',
    FOREIGN KEY (id_kegiatan) REFERENCES kegiatan(id_kegiatan) ON DELETE CASCADE,
    FOREIGN KEY (id_warga) REFERENCES warga(id_warga) ON DELETE CASCADE
);

-- Tabel Iuran
CREATE TABLE IF NOT EXISTS iuran (
    id_iuran INTEGER PRIMARY KEY AUTOINCREMENT,
    id_rt INTEGER NOT NULL,
    nama_iuran VARCHAR(150) NOT NULL,
    nominal DECIMAL(12, 2) NOT NULL,
    jenis_periode VARCHAR(50),
    bulan VARCHAR(20),
    tahun INTEGER,
    keterangan TEXT,
    FOREIGN KEY (id_rt) REFERENCES rt(id_rt) ON DELETE CASCADE
);

-- Tabel Pembayaran
CREATE TABLE IF NOT EXISTS pembayaran (
    id_pembayaran INTEGER PRIMARY KEY AUTOINCREMENT,
    id_iuran INTEGER NOT NULL,
    id_warga INTEGER NOT NULL,
    tanggal_bayar DATETIME NOT NULL,
    bukti_bayar VARCHAR(255),
    metode_bayar VARCHAR(50),
    jumlah_bayar DECIMAL(12, 2) NOT NULL,
    status_verifikasi TEXT CHECK(status_verifikasi IN ('blm lunas', 'pending', 'Lunas')) DEFAULT 'pending',
    FOREIGN KEY (id_iuran) REFERENCES iuran(id_iuran) ON DELETE CASCADE,
    FOREIGN KEY (id_warga) REFERENCES warga(id_warga) ON DELETE CASCADE
);

-- Tabel Keuangan
CREATE TABLE IF NOT EXISTS keuangan (
    id_keuangan INTEGER PRIMARY KEY AUTOINCREMENT,
    id_rt INTEGER NOT NULL,
    id_warga INTEGER,
    id_inventaris INTEGER,
    tanggal DATETIME NOT NULL,
    jenis TEXT CHECK(jenis IN ('pemasukan', 'pengeluaran')) NOT NULL,
    kategori TEXT CHECK(kategori IN ('iuran', 'pembelian', 'konsumsi kegiatan', 'lainnya')) NOT NULL,
    jumlah DECIMAL(12, 2) NOT NULL,
    keterangan TEXT,
    bukti_nota VARCHAR(255),
    FOREIGN KEY (id_rt) REFERENCES rt(id_rt) ON DELETE CASCADE,
    FOREIGN KEY (id_warga) REFERENCES warga(id_warga) ON DELETE SET NULL,
    FOREIGN KEY (id_inventaris) REFERENCES inventaris(id_inventaris) ON DELETE SET NULL
);
