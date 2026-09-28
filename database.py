import sqlite3
import os
from werkzeug.security import generate_password_hash

DB_PATH = os.path.join(os.path.dirname(__file__), 'wargaconnect.db')

def get_db():
    conn = sqlite3.connect(DB_PATH)
    conn.row_factory = sqlite3.Row
    conn.execute("PRAGMA foreign_keys = ON;")
    return conn

def init_db():
    conn = get_db()
    cursor = conn.cursor()
    
    # Read schema
    schema_path = os.path.join(os.path.dirname(__file__), 'schema.sql')
    with open(schema_path, 'r', encoding='utf-8') as f:
        cursor.executescript(f.read())
    
    # Seed data if database is empty
    cursor.execute("SELECT COUNT(*) FROM rt")
    if cursor.fetchone()[0] == 0:
        print("Seeding initial data...")
        
        # 1. Tabel RT
        rt_data = [
            ('001', '08', 'Sukamaju', 'Cilodong'),
            ('002', '08', 'Sukamaju', 'Cilodong'),
            ('003', '08', 'Sukamaju', 'Cilodong'),
            ('004', '08', 'Sukamaju', 'Cilodong'),
            ('005', '08', 'Sukamaju', 'Cilodong'),
            ('006', '08', 'Sukamaju', 'Cilodong')
        ]
        cursor.executemany("INSERT INTO rt (nomor_rt, rw, kelurahan, kecamatan) VALUES (?, ?, ?, ?)", rt_data)
        
        # 2. Tabel Keluarga
        keluarga_data = [
            (1, 'Jl. Mawar No. 12, RT 01 / RW 08', '16415'),
            (2, 'Jl. Dahlia No. 14, RT 02 / RW 08', '16415'),
            (3, 'Jl. Merpati No. 05, RT 03 / RW 08', '16415'),
            (4, 'Jl. Anggrek No. 22, RT 04 / RW 08', '16415'),
            (5, 'Jl. Melati No. 08, RT 05 / RW 08', '16415')
        ]
        cursor.executemany("INSERT INTO keluarga (id_rt, alamat, kode_pos) VALUES (?, ?, ?)", keluarga_data)
        
        # 3. Tabel Super Admin
        admin_pass = generate_password_hash('admin123')
        cursor.execute("INSERT INTO super_admin (username, password, nama) VALUES (?, ?, ?)", 
                       ('admin', admin_pass, 'Pak Bambang Pamungkas'))
        
        # 4. Tabel Warga
        warga_pass = generate_password_hash('warga123')
        warga_data = [
            (2, 'Hendra Gunawan', 'L', '081234567890', 'warga', warga_pass, 'Warga', 'Aktif'),
            (4, 'Maya Anggraini', 'P', '082198765432', 'maya', warga_pass, 'Warga', 'Aktif'),
            (1, 'Agus Wicaksono', 'L', '081311223344', 'agus', warga_pass, 'Ketua RT', 'Aktif'),
            (3, 'Siti Aminah', 'P', '085712345678', 'siti', warga_pass, 'Warga', 'Aktif'),
            (5, 'Budi Santoso', 'L', '081900112233', 'budi', warga_pass, 'Warga', 'Aktif')
        ]
        cursor.executemany("""INSERT INTO warga 
            (id_keluarga, nama, jenis_kelamin, no_telepon, email_username, password, role, status_warga) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)""", warga_data)
        
        # 5. Tabel Pengumuman
        pengumuman_data = [
            (2, 'Pemadaman Listrik Sementara', 'Diberitahukan kepada seluruh warga, akan ada pemadaman listrik dari PLN pada hari Sabtu, pkl 09:00 - 12:00 WIB untuk pemeliharaan jaringan.'),
            (3, 'Kerja Bakti Rutin & Senam Pagi', 'Mari ramaikan kerja bakti membersihkan saluran air persiapan musim hujan, dilanjutkan dengan senam pagi bersama.'),
            (1, 'Perubahan Jadwal Pengambilan Sampah', 'Mulai bulan depan, jadwal pengambilan sampah oleh petugas akan diubah menjadi setiap hari Senin, Rabu, dan Jumat pagi.')
        ]
        cursor.executemany("INSERT INTO pengumuman (id_rt, judul, konten) VALUES (?, ?, ?)", pengumuman_data)
        
        # 6. Tabel Aspirasi
        aspirasi_data = [
            (1, 'Perbaikan Selokan RT 03', 'Selokan sering mampet saat hujan deras, mohon dilakukan pembersihan rutin.', None, 'selesai'),
            (1, 'Ronda Malam Tambahan', 'Mohon jadwal ronda malam ditambah menjelang libur panjang untuk keamanan.', None, 'diproses'),
            (2, 'Lampu PJU Lapangan Bulutangkis Padam', 'Penerangan di area lapangan bulutangkis RT 03 mati 3 malam, mohon pergantian bohlam LED.', None, 'pending')
        ]
        cursor.executemany("INSERT INTO aspirasi (id_warga, judul, isi_laporan, foto_bukti, status) VALUES (?, ?, ?, ?, ?)", aspirasi_data)
        
        # 7. Tabel Surat
        surat_data = [
            (1, 'Keterangan Domisili', '2024-05-24', 'Pembuatan KTP baru luar kota', '045/SKD/RT02/V/2024', 'pending'),
            (2, 'Keterangan Usaha (SKU)', '2024-05-23', 'Pengajuan KUR Mikro BRI', '044/SKU/RT04/V/2024', 'selesai'),
            (3, 'SKTM (Tidak Mampu)', '2024-05-22', 'Beasiswa Pendidikan Anak Kuliah', '043/SKTM/RT01/V/2024', 'diproses'),
            (4, 'Pengantar Ganti KK', '2024-05-21', 'Penambahan Anggota Keluarga Baru', '042/SP-KK/RT03/V/2024', 'selesai')
        ]
        cursor.executemany("INSERT INTO surat (id_warga, jenis_surat, tanggal_surat, keperluan, no_surat, status) VALUES (?, ?, ?, ?, ?, ?)", surat_data)
        
        # 8. Tabel Inventaris
        inventaris_data = [
            (2, 'Tenda Hajatan 4x6m', 'Perlengkapan Event', 2, 'unit', 'Baik', 'Gudang Balai RT 02'),
            (3, 'Kursi Plastik Napolly', 'Perlengkapan Event', 50, 'buah', 'Baik', 'Gudang Balai RT 03'),
            (1, 'Mesin Rumput Honda', 'Peralatan Kebersihan', 1, 'unit', 'Baik', 'Pos Ronda RT 01'),
            (2, 'Sound System Portable', 'Elektronik', 1, 'set', 'Baik', 'Rumah Pak RT 02')
        ]
        cursor.executemany("INSERT INTO inventaris (id_rt, nama_inventaris, kategori, jumlah, satuan, kondisi, lokasi_simpan) VALUES (?, ?, ?, ?, ?, ?, ?)", inventaris_data)
        
        # 9. Tabel Kegiatan
        kegiatan_data = [
            (3, 3, 'Kerja Bakti Bulanan RT 03', '2024-10-12 07:00:00', 'Sepanjang Jalan Merpati', 'Wajib membawa cangkul & sapu lidi'),
            (2, 1, 'Posyandu Balita & Lansia', '2024-10-15 08:00:00', 'Balai Warga RT 02', 'Pemeriksaan kesehatan rutin bulanan'),
            (2, 4, 'Arisan Ibu-ibu PKK', '2024-10-20 16:00:00', 'Rumah Bu RT (Blok C2)', 'Kegiatan rutin PKK RT 02')
        ]
        cursor.executemany("INSERT INTO kegiatan (id_rt, id_warga, nama_kegiatan, tanggal_kegiatan, lokasi, keterangan) VALUES (?, ?, ?, ?, ?, ?)", kegiatan_data)
        
        # 10. Tabel Peserta Kegiatan
        peserta_data = [
            (1, 1, 'hadir'),
            (1, 2, 'hadir'),
            (1, 5, 'izin'),
            (2, 4, 'hadir'),
            (3, 4, 'hadir')
        ]
        cursor.executemany("INSERT INTO peserta_kegiatan (id_kegiatan, id_warga, status_kehadiran) VALUES (?, ?, ?)", peserta_data)
        
        # 11. Tabel Iuran
        iuran_data = [
            (2, 'Iuran Sampah & Kebersihan', 30000.00, 'Bulanan', 'Oktober', 2024, 'Pembayaran iuran kebersihan rutin'),
            (2, 'Iuran Keamanan & Satpam', 45000.00, 'Bulanan', 'Oktober', 2024, 'Pembayaran gaji hansip & ronda'),
            (2, 'Kas Kematian & Sosial', 15000.00, 'Bulanan', 'Oktober', 2024, 'Dana sosial duka & sakit')
        ]
        cursor.executemany("INSERT INTO iuran (id_rt, nama_iuran, nominal, jenis_periode, bulan, tahun, keterangan) VALUES (?, ?, ?, ?, ?, ?, ?)", iuran_data)
        
        # 12. Tabel Pembayaran
        pembayaran_data = [
            (1, 1, '2024-10-01 10:15:00', 'bukti1.jpg', 'Transfer BCA', 30000.00, 'Lunas'),
            (2, 1, '2024-10-01 10:16:00', 'bukti2.jpg', 'Transfer BCA', 45000.00, 'Lunas'),
            (1, 2, '2024-10-03 14:20:00', 'bukti3.jpg', 'QRIS Mandiri', 30000.00, 'pending'),
            (2, 5, '2024-10-04 09:30:00', 'bukti4.jpg', 'Tunai', 45000.00, 'pending')
        ]
        cursor.executemany("INSERT INTO pembayaran (id_iuran, id_warga, tanggal_bayar, bukti_bayar, metode_bayar, jumlah_bayar, status_verifikasi) VALUES (?, ?, ?, ?, ?, ?, ?)", pembayaran_data)
        
        # 13. Tabel Keuangan
        keuangan_data = [
            (2, 1, None, '2024-10-01 11:00:00', 'pemasukan', 'iuran', 750000.00, 'Penerimaan Iuran Kebersihan 25 KK', 'nota1.pdf'),
            (2, None, 1, '2024-10-02 13:30:00', 'pengueluaran', 'pembelian', 250000.00, 'Pembelian BBM Mesin Rumput', 'nota2.jpg'),
            (2, 3, None, '2024-10-05 08:00:00', 'pengeluaran', 'konsumsi kegiatan', 150000.00, 'Konsumsi Kerja Bakti Bulanan', 'nota3.jpg')
        ]
        # Fix typing check for enum
        cursor.executemany("""INSERT INTO keuangan 
            (id_rt, id_warga, id_inventaris, tanggal, jenis, kategori, jumlah, keterangan, bukti_nota) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)""", [
            (2, 1, None, '2024-10-01 11:00:00', 'pemasukan', 'iuran', 750000.00, 'Penerimaan Iuran Kebersihan 25 KK', 'nota1.pdf'),
            (2, None, 1, '2024-10-02 13:30:00', 'pengeluaran', 'pembelian', 250000.00, 'Pembelian BBM Mesin Rumput', 'nota2.jpg'),
            (2, 3, None, '2024-10-05 08:00:00', 'pengeluaran', 'konsumsi kegiatan', 150000.00, 'Konsumsi Kerja Bakti Bulanan', 'nota3.jpg')
        ])
        
        conn.commit()
        print("Database seeded successfully!")
    
    conn.close()

if __name__ == '__main__':
    init_db()
