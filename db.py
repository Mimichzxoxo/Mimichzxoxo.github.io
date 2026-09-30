import sqlite3
import os
from datetime import datetime

DB_FILE = os.path.join(os.path.dirname(os.path.abspath(__file__)), 'smart_rt.db')

def get_db():
    conn = sqlite3.connect(DB_FILE)
    conn.row_factory = sqlite3.Row
    return conn

def init_db():
    conn = get_db()
    cursor = conn.cursor()
    
    # Enable foreign keys
    cursor.execute("PRAGMA foreign_keys = ON;")
    
    # 1. RT
    cursor.execute('''
        CREATE TABLE IF NOT EXISTS rt (
            id_rt INTEGER PRIMARY KEY AUTOINCREMENT,
            nomor_rt TEXT NOT NULL,
            rw TEXT NOT NULL,
            kelurahan TEXT NOT NULL,
            kecamatan TEXT NOT NULL
        )
    ''')

    # 2. Keluarga
    cursor.execute('''
        CREATE TABLE IF NOT EXISTS keluarga (
            id_keluarga INTEGER PRIMARY KEY AUTOINCREMENT,
            id_rt INTEGER NOT NULL,
            alamat TEXT NOT NULL,
            kode_pos TEXT,
            FOREIGN KEY (id_rt) REFERENCES rt(id_rt) ON DELETE CASCADE
        )
    ''')

    # 3. Warga
    cursor.execute('''
        CREATE TABLE IF NOT EXISTS warga (
            id_warga INTEGER PRIMARY KEY AUTOINCREMENT,
            id_keluarga INTEGER NOT NULL,
            nama TEXT NOT NULL,
            jenis_kelamin TEXT NOT NULL,
            no_telepon TEXT,
            email_username TEXT UNIQUE NOT NULL,
            password TEXT NOT NULL,
            role TEXT NOT NULL,
            status_warga TEXT NOT NULL,
            FOREIGN KEY (id_keluarga) REFERENCES keluarga(id_keluarga) ON DELETE CASCADE
        )
    ''')

    # 4. Super Admin
    cursor.execute('''
        CREATE TABLE IF NOT EXISTS super_admin (
            id_superadmin INTEGER PRIMARY KEY AUTOINCREMENT,
            username TEXT UNIQUE NOT NULL,
            password TEXT NOT NULL,
            nama TEXT NOT NULL
        )
    ''')

    # 5. Pengumuman
    cursor.execute('''
        CREATE TABLE IF NOT EXISTS pengumuman (
            id_pengumuman INTEGER PRIMARY KEY AUTOINCREMENT,
            id_rt INTEGER NOT NULL,
            judul TEXT NOT NULL,
            konten TEXT NOT NULL,
            tanggal_publish DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (id_rt) REFERENCES rt(id_rt) ON DELETE CASCADE
        )
    ''')

    # 6. Aspirasi
    cursor.execute('''
        CREATE TABLE IF NOT EXISTS aspirasi (
            id_pengaduan INTEGER PRIMARY KEY AUTOINCREMENT,
            id_warga INTEGER NOT NULL,
            judul TEXT NOT NULL,
            isi_laporan TEXT NOT NULL,
            foto_bukti TEXT,
            status TEXT DEFAULT 'pending',
            FOREIGN KEY (id_warga) REFERENCES warga(id_warga) ON DELETE CASCADE
        )
    ''')

    # 7. Surat
    cursor.execute('''
        CREATE TABLE IF NOT EXISTS surat (
            id_surat INTEGER PRIMARY KEY AUTOINCREMENT,
            id_warga INTEGER NOT NULL,
            jenis_surat TEXT NOT NULL,
            tanggal_surat DATE NOT NULL,
            keperluan TEXT NOT NULL,
            no_surat TEXT,
            status TEXT NOT NULL,
            FOREIGN KEY (id_warga) REFERENCES warga(id_warga) ON DELETE CASCADE
        )
    ''')

    # 8. Inventaris
    cursor.execute('''
        CREATE TABLE IF NOT EXISTS inventaris (
            id_inventaris INTEGER PRIMARY KEY AUTOINCREMENT,
            id_rt INTEGER NOT NULL,
            nama_inventaris TEXT NOT NULL,
            kategori TEXT,
            jumlah INTEGER NOT NULL DEFAULT 0,
            satuan TEXT,
            kondisi TEXT DEFAULT 'Baik',
            lokasi_simpan TEXT,
            FOREIGN KEY (id_rt) REFERENCES rt(id_rt) ON DELETE CASCADE
        )
    ''')

    # 9. Kegiatan
    cursor.execute('''
        CREATE TABLE IF NOT EXISTS kegiatan (
            id_kegiatan INTEGER PRIMARY KEY AUTOINCREMENT,
            id_rt INTEGER NOT NULL,
            id_warga INTEGER,
            nama_kegiatan TEXT NOT NULL,
            tanggal_kegiatan DATETIME NOT NULL,
            lokasi TEXT,
            keterangan TEXT,
            FOREIGN KEY (id_rt) REFERENCES rt(id_rt) ON DELETE CASCADE,
            FOREIGN KEY (id_warga) REFERENCES warga(id_warga) ON DELETE SET NULL
        )
    ''')

    # 10. Peserta Kegiatan
    cursor.execute('''
        CREATE TABLE IF NOT EXISTS peserta_kegiatan (
            id_peserta INTEGER PRIMARY KEY AUTOINCREMENT,
            id_kegiatan INTEGER NOT NULL,
            id_warga INTEGER NOT NULL,
            status_kehadiran TEXT DEFAULT 'hadir',
            FOREIGN KEY (id_kegiatan) REFERENCES kegiatan(id_kegiatan) ON DELETE CASCADE,
            FOREIGN KEY (id_warga) REFERENCES warga(id_warga) ON DELETE CASCADE
        )
    ''')

    # 11. Iuran
    cursor.execute('''
        CREATE TABLE IF NOT EXISTS iuran (
            id_iuran INTEGER PRIMARY KEY AUTOINCREMENT,
            id_rt INTEGER NOT NULL,
            nama_iuran TEXT NOT NULL,
            nominal REAL NOT NULL,
            jenis_periode TEXT,
            bulan TEXT,
            tahun INTEGER,
            keterangan TEXT,
            FOREIGN KEY (id_rt) REFERENCES rt(id_rt) ON DELETE CASCADE
        )
    ''')

    # 12. Pembayaran
    cursor.execute('''
        CREATE TABLE IF NOT EXISTS pembayaran (
            id_pembayaran INTEGER PRIMARY KEY AUTOINCREMENT,
            id_iuran INTEGER NOT NULL,
            id_warga INTEGER NOT NULL,
            tanggal_bayar DATETIME NOT NULL,
            bukti_bayar TEXT,
            metode_bayar TEXT,
            jumlah_bayar REAL NOT NULL,
            status_verifikasi TEXT DEFAULT 'pending',
            FOREIGN KEY (id_iuran) REFERENCES iuran(id_iuran) ON DELETE CASCADE,
            FOREIGN KEY (id_warga) REFERENCES warga(id_warga) ON DELETE CASCADE
        )
    ''')

    # 13. Keuangan
    cursor.execute('''
        CREATE TABLE IF NOT EXISTS keuangan (
            id_keuangan INTEGER PRIMARY KEY AUTOINCREMENT,
            id_rt INTEGER NOT NULL,
            id_warga INTEGER,
            id_inventaris INTEGER,
            tanggal DATETIME NOT NULL,
            jenis TEXT NOT NULL,
            kategori TEXT NOT NULL,
            jumlah REAL NOT NULL,
            keterangan TEXT,
            bukti_nota TEXT,
            FOREIGN KEY (id_rt) REFERENCES rt(id_rt) ON DELETE CASCADE,
            FOREIGN KEY (id_warga) REFERENCES warga(id_warga) ON DELETE SET NULL,
            FOREIGN KEY (id_inventaris) REFERENCES inventaris(id_inventaris) ON DELETE SET NULL
        )
    ''')

    # 14. Database Triggers (Native SQLite Triggers)
    cursor.execute('''
        CREATE TRIGGER IF NOT EXISTS trg_auto_no_surat
        AFTER UPDATE ON surat
        FOR EACH ROW
        WHEN (NEW.status LIKE '%Lunas%' OR NEW.status LIKE '%Selesai%') AND (NEW.no_surat IS NULL OR NEW.no_surat = '')
        BEGIN
            UPDATE surat 
            SET no_surat = '045/' || NEW.id_surat || '/SP/RT02/10/2024'
            WHERE id_surat = NEW.id_surat;
        END;
    ''')

    cursor.execute('''
        CREATE TRIGGER IF NOT EXISTS trg_auto_keuangan_pembayaran
        AFTER UPDATE ON pembayaran
        FOR EACH ROW
        WHEN NEW.status_verifikasi = 'Lunas' AND OLD.status_verifikasi != 'Lunas'
        BEGIN
            INSERT INTO keuangan (id_rt, id_warga, id_inventaris, tanggal, jenis, kategori, jumlah, keterangan, bukti_nota)
            SELECT i.id_rt, NEW.id_warga, NULL, DATETIME('now'), 'pemasukan', 'iuran', NEW.jumlah_bayar, 'Trigger Auto-Kas: Iuran ' || i.nama_iuran, NULL
            FROM iuran i WHERE i.id_iuran = NEW.id_iuran;
        END;
    ''')

    conn.commit()
    seed_data(conn)
    conn.close()

def seed_data(conn):
    cursor = conn.cursor()
    cursor.execute("SELECT COUNT(*) FROM rt")
    if cursor.fetchone()[0] == 0:
        # Seed RTs
        cursor.execute("INSERT INTO rt (nomor_rt, rw, kelurahan, kecamatan) VALUES ('01', '08', 'Sukamaju', 'Cilodong')")
        cursor.execute("INSERT INTO rt (nomor_rt, rw, kelurahan, kecamatan) VALUES ('02', '08', 'Sukamaju', 'Cilodong')")
        cursor.execute("INSERT INTO rt (nomor_rt, rw, kelurahan, kecamatan) VALUES ('03', '08', 'Sukamaju', 'Cilodong')")
        
        # Seed Keluarga
        cursor.execute("INSERT INTO keluarga (id_rt, alamat, kode_pos) VALUES (1, 'Jl. Merpati No. 12, RT 01', '16415')")
        cursor.execute("INSERT INTO keluarga (id_rt, alamat, kode_pos) VALUES (2, 'Jl. Dahlia No. 14, RT 02', '16415')")
        cursor.execute("INSERT INTO keluarga (id_rt, alamat, kode_pos) VALUES (3, 'Jl. Mawar No. 05, RT 03', '16415')")

        # Seed Super Admin
        cursor.execute("INSERT INTO super_admin (username, password, nama) VALUES ('superadmin', 'admin123', 'Pak Bambang Pamungkas')")

        # Seed Warga (Citizens & Admins)
        cursor.execute('''
            INSERT INTO warga (id_keluarga, nama, jenis_kelamin, no_telepon, email_username, password, role, status_warga)
            VALUES (1, 'Budi Santoso', 'L', '081234567890', 'budi', 'warga123', 'warga', 'Tetap')
        ''')
        cursor.execute('''
            INSERT INTO warga (id_keluarga, nama, jenis_kelamin, no_telepon, email_username, password, role, status_warga)
            VALUES (2, 'Hendra Gunawan', 'L', '081987654321', 'hendra', 'warga123', 'warga', 'Tetap')
        ''')
        cursor.execute('''
            INSERT INTO warga (id_keluarga, nama, jenis_kelamin, no_telepon, email_username, password, role, status_warga)
            VALUES (3, 'Pak Bambang Pamungkas', 'L', '081122334455', 'admin_rt', 'admin123', 'admin_rt', 'Tetap')
        ''')
        cursor.execute('''
            INSERT INTO warga (id_keluarga, nama, jenis_kelamin, no_telepon, email_username, password, role, status_warga)
            VALUES (1, 'Siti Rahmawati', 'P', '081333444555', 'siti', 'warga123', 'warga', 'Tetap')
        ''')

        # Seed Pengumuman
        cursor.execute('''
            INSERT INTO pengumuman (id_rt, judul, konten, tanggal_publish)
            VALUES (2, 'Pemadaman Listrik Sementara', 'Diberitahukan kepada seluruh warga RT 02, akan ada pemadaman listrik dari PLN pada hari Sabtu, pkl 09:00 - 12:00 WIB untuk pemeliharaan jaringan.', '2024-10-24 08:00:00')
        ''')
        cursor.execute('''
            INSERT INTO pengumuman (id_rt, judul, konten, tanggal_publish)
            VALUES (3, 'Kerja Bakti Rutin & Senam Pagi', 'Mari ramaikan kerja bakti membersihkan saluran air persiapan musim hujan, dilanjutkan dengan senam pagi bersama di lapangan RT 03.', '2024-10-23 14:30:00')
        ''')
        cursor.execute('''
            INSERT INTO pengumuman (id_rt, judul, konten, tanggal_publish)
            VALUES (1, 'Perubahan Jadwal Pengambilan Sampah', 'Mulai bulan depan, jadwal pengambilan sampah oleh petugas akan diubah menjadi setiap hari Senin, Rabu, dan Jumat pagi.', '2024-10-20 10:00:00')
        ''')

        # Seed Aspirasi
        cursor.execute('''
            INSERT INTO aspirasi (id_warga, judul, isi_laporan, foto_bukti, status)
            VALUES (1, 'Perbaikan Selokan RT 01', 'Selokan sering mampet saat hujan deras, mohon dilakukan pembersihan rutin.', NULL, 'selesai')
        ''')
        cursor.execute('''
            INSERT INTO aspirasi (id_warga, judul, isi_laporan, foto_bukti, status)
            VALUES (2, 'Lampu PJU Lapangan Bulutangkis Padam', 'Penerangan di area lapangan bulutangkis RT 02 sudah mati 3 malam, mohon pergantian bohlam LED dari kas pemeliharaan RW.', NULL, 'diproses')
        ''')
        cursor.execute('''
            INSERT INTO aspirasi (id_warga, judul, isi_laporan, foto_bukti, status)
            VALUES (4, 'Penumpukan Sampah Dahan Pohon Usai Hujan', 'Dahan pohon tumbang di gang mawar belum terangkut oleh truk sampah DLH. Membutuhkan kerja bakti warga atau koordinasi kebersihan.', NULL, 'pending')
        ''')

        # Seed Surat
        cursor.execute('''
            INSERT INTO surat (id_warga, jenis_surat, tanggal_surat, keperluan, no_surat, status)
            VALUES (2, 'Surat Keterangan Domisili', '2024-05-24', 'Pembuatan KTP baru luar kota', '045/SKD/RT02/V/2024', 'Menunggu Validasi')
        ''')
        cursor.execute('''
            INSERT INTO surat (id_warga, jenis_surat, tanggal_surat, keperluan, no_surat, status)
            VALUES (1, 'Surat Keterangan Usaha (SKU)', '2024-05-23', 'Pengajuan KUR Mikro BRI', '044/SKU/RT01/V/2024', 'Lunas / Selesai (TTE Terbit)')
        ''')
        cursor.execute('''
            INSERT INTO surat (id_warga, jenis_surat, tanggal_surat, keperluan, no_surat, status)
            VALUES (4, 'SKTM (Tidak Mampu)', '2024-05-22', 'Beasiswa Pendidikan Anak Kuliah', '043/SKTM/RT01/V/2024', 'Diproses Kelurahan')
        ''')

        # Seed Inventaris
        cursor.execute('''
            INSERT INTO inventaris (id_rt, nama_inventaris, kategori, jumlah, satuan, kondisi, lokasi_simpan)
            VALUES (1, 'Tenda Pesta 4x6m', 'Fasilitas Umum', 2, 'unit', 'Baik', 'Gudang Balai RT 01')
        ''')
        cursor.execute('''
            INSERT INTO inventaris (id_rt, nama_inventaris, kategori, jumlah, satuan, kondisi, lokasi_simpan)
            VALUES (2, 'Kursi Lipat Chitose', 'Perlengkapan', 50, 'buah', 'Baik', 'Balai Warga RT 02')
        ''')
        cursor.execute('''
            INSERT INTO inventaris (id_rt, nama_inventaris, kategori, jumlah, satuan, kondisi, lokasi_simpan)
            VALUES (3, 'Sound System Portable', 'Elektronik', 1, 'set', 'Baik', 'Rumah Pak RT 03')
        ''')

        # Seed Kegiatan
        cursor.execute('''
            INSERT INTO kegiatan (id_rt, id_warga, nama_kegiatan, tanggal_kegiatan, lokasi, keterangan)
            VALUES (3, 3, 'Kerja Bakti Bulanan RT 03', '2024-11-12 07:00:00', 'Sepanjang Jalan Merpati', 'Pembersihan selokan dan perapihan dahan pohon. Wajib untuk seluruh KK.')
        ''')
        cursor.execute('''
            INSERT INTO kegiatan (id_rt, id_warga, nama_kegiatan, tanggal_kegiatan, lokasi, keterangan)
            VALUES (2, 3, 'Posyandu Balita & Lansia', '2024-11-15 08:00:00', 'Balai Warga RT 02', 'Pemeriksaan rutin tumbuh kembang balita dan cek kesehatan lansia.')
        ''')
        cursor.execute('''
            INSERT INTO kegiatan (id_rt, id_warga, nama_kegiatan, tanggal_kegiatan, lokasi, keterangan)
            VALUES (1, 3, 'Arisan Ibu-ibu PKK', '2024-11-20 16:00:00', 'Rumah Bu RT (Blok C2)', 'Pertemuan bulanan ibu-ibu PKK dan pengocokan arisan.')
        ''')

        # Seed Peserta Kegiatan
        cursor.execute("INSERT INTO peserta_kegiatan (id_kegiatan, id_warga, status_kehadiran) VALUES (1, 1, 'hadir')")
        cursor.execute("INSERT INTO peserta_kegiatan (id_kegiatan, id_warga, status_kehadiran) VALUES (1, 2, 'hadir')")
        cursor.execute("INSERT INTO peserta_kegiatan (id_kegiatan, id_warga, status_kehadiran) VALUES (2, 4, 'izin')")

        # Seed Iuran
        cursor.execute('''
            INSERT INTO iuran (id_rt, nama_iuran, nominal, jenis_periode, bulan, tahun, keterangan)
            VALUES (1, 'Iuran Sampah & Keamanan', 150000.00, 'Bulanan', 'Oktober', 2024, 'Iuran rutin bulanan warga RT 01')
        ''')
        cursor.execute('''
            INSERT INTO iuran (id_rt, nama_iuran, nominal, jenis_periode, bulan, tahun, keterangan)
            VALUES (2, 'Kas Kebersihan', 75000.00, 'Bulanan', 'Oktober', 2024, 'Iuran kebersihan lingkungan RT 02')
        ''')

        # Seed Pembayaran
        cursor.execute('''
            INSERT INTO pembayaran (id_iuran, id_warga, tanggal_bayar, bukti_bayar, metode_bayar, jumlah_bayar, status_verifikasi)
            VALUES (1, 1, '2024-10-24 10:15:00', 'bukti_transfer_1.jpg', 'Transfer Bank', 150000.00, 'pending')
        ''')
        cursor.execute('''
            INSERT INTO pembayaran (id_iuran, id_warga, tanggal_bayar, bukti_bayar, metode_bayar, jumlah_bayar, status_verifikasi)
            VALUES (2, 2, '2024-10-23 14:20:00', 'bukti_qris_2.jpg', 'QRIS', 75000.00, 'pending')
        ''')
        cursor.execute('''
            INSERT INTO pembayaran (id_iuran, id_warga, tanggal_bayar, bukti_bayar, metode_bayar, jumlah_bayar, status_verifikasi)
            VALUES (1, 4, '2024-10-21 09:00:00', 'bukti_cash_3.jpg', 'Tunai', 150000.00, 'Lunas')
        ''')

        # Seed Keuangan
        cursor.execute('''
            INSERT INTO keuangan (id_rt, id_warga, id_inventaris, tanggal, jenis, kategori, jumlah, keterangan, bukti_nota)
            VALUES (1, 4, NULL, '2024-10-21 09:00:00', 'pemasukan', 'iuran', 150000.00, 'Pembayaran Iuran: Iuran Sampah & Keamanan (Siti Rahmawati)', NULL)
        ''')
        cursor.execute('''
            INSERT INTO keuangan (id_rt, id_warga, id_inventaris, tanggal, jenis, kategori, jumlah, keterangan, bukti_nota)
            VALUES (1, NULL, 1, '2024-10-15 11:30:00', 'pengeluaran', 'pembelian', 500000.00, 'Pembelian Tenda Tambahan RT 01', 'nota_tenda.jpg')
        ''')

        conn.commit()

# =====================================================
# STORED PROCEDURE IMPLEMENTATIONS (SIMULATED IN PYTHON/SQLITE)
# =====================================================

def sp_TambahWarga(id_rt, id_keluarga, nama, jenis_kelamin, no_telepon, email_username, password, role, status_warga):
    conn = get_db()
    cursor = conn.cursor()
    cursor.execute('''
        INSERT INTO warga (id_keluarga, nama, jenis_kelamin, no_telepon, email_username, password, role, status_warga)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?)
    ''', (id_keluarga, nama, jenis_kelamin, no_telepon, email_username, password, role, status_warga))
    new_id = cursor.lastrowid
    conn.commit()
    conn.close()
    return {"new_id_warga": new_id, "pesan": "Warga berhasil ditambahkan"}

def sp_CatatIuran(id_iuran, id_warga, tanggal_bayar, bukti_bayar, metode_bayar, jumlah_bayar):
    conn = get_db()
    cursor = conn.cursor()
    cursor.execute('''
        INSERT INTO pembayaran (id_iuran, id_warga, tanggal_bayar, bukti_bayar, metode_bayar, jumlah_bayar, status_verifikasi)
        VALUES (?, ?, ?, ?, ?, ?, 'pending')
    ''', (id_iuran, id_warga, tanggal_bayar, bukti_bayar, metode_bayar, jumlah_bayar))
    new_id = cursor.lastrowid
    conn.commit()
    conn.close()
    return {"new_id_pembayaran": new_id, "pesan": "Pembayaran iuran berhasil dicatat"}

def sp_VerifikasiIuran(id_pembayaran, status_baru):
    conn = get_db()
    cursor = conn.cursor()
    cursor.execute("UPDATE pembayaran SET status_verifikasi = ? WHERE id_pembayaran = ?", (status_baru, id_pembayaran))
    
    if status_baru == 'Lunas':
        cursor.execute('''
            SELECT i.id_rt, p.id_warga, p.jumlah_bayar, i.nama_iuran
            FROM pembayaran p
            JOIN iuran i ON p.id_iuran = i.id_iuran
            WHERE p.id_pembayaran = ?
        ''', (id_pembayaran,))
        row = cursor.fetchone()
        if row:
            id_rt, id_warga, jumlah, nama_iuran = row['id_rt'], row['id_warga'], row['jumlah_bayar'], row['nama_iuran']
            now_str = datetime.now().strftime('%Y-%m-%d %H:%M:%S')
            cursor.execute('''
                INSERT INTO keuangan (id_rt, id_warga, id_inventaris, tanggal, jenis, kategori, jumlah, keterangan, bukti_nota)
                VALUES (?, ?, NULL, ?, 'pemasukan', 'iuran', ?, ?, NULL)
            ''', (id_rt, id_warga, now_str, jumlah, f"Pembayaran Iuran: {nama_iuran}"))
            
    conn.commit()
    conn.close()
    return {"pesan": "Verifikasi iuran berhasil diperbarui"}

def sp_AjukanSuratPengantar(id_warga, jenis_surat, keperluan):
    conn = get_db()
    cursor = conn.cursor()
    tgl_today = datetime.now().strftime('%Y-%m-%d')
    cursor.execute('''
        INSERT INTO surat (id_warga, jenis_surat, tanggal_surat, keperluan, no_surat, status)
        VALUES (?, ?, ?, ?, NULL, 'Menunggu Validasi')
    ''', (id_warga, jenis_surat, tgl_today, keperluan))
    new_id = cursor.lastrowid
    conn.commit()
    conn.close()
    return {"new_id_surat": new_id, "pesan": "Permohonan surat pengantar berhasil dikirim ke Admin RT"}

def sp_GetDashboardSuperAdmin(bulan=None, tahun=None):
    conn = get_db()
    cursor = conn.cursor()
    query = '''
        SELECT 
            rt.id_rt,
            rt.nomor_rt,
            rt.rw,
            rt.kelurahan,
            rt.kecamatan,
            COUNT(DISTINCT w.id_warga) AS total_warga,
            COUNT(DISTINCT k.id_keluarga) AS total_keluarga,
            COALESCE(SUM(CASE WHEN p.status_verifikasi = 'Lunas' THEN p.jumlah_bayar ELSE 0 END), 0) AS total_iuran_terkumpul,
            SUM(CASE WHEN p.status_verifikasi = 'pending' THEN 1 ELSE 0 END) AS antrean_verifikasi
        FROM rt
        LEFT JOIN keluarga k ON rt.id_rt = k.id_rt
        LEFT JOIN warga w ON k.id_keluarga = w.id_keluarga
        LEFT JOIN iuran i ON rt.id_rt = i.id_rt
        LEFT JOIN pembayaran p ON i.id_iuran = p.id_iuran
        GROUP BY rt.id_rt, rt.nomor_rt, rt.rw, rt.kelurahan, rt.kecamatan
    '''
    cursor.execute(query)
    results = [dict(row) for row in cursor.fetchall()]
    conn.close()
    return results
