# WargaConnect - Sistem Informasi Manajemen RT/RW (SIM RT/RW)

Aplikasi Web Manajemen RT/RW modern berbasis **Python Flask**, **SQLite3**, **Tailwind CSS**, dan **Material Symbols**. Aplikasi ini mencakup **13 Tabel ERD** kependudukan, keuangan, pelayanan surat TTE, pengumuman, aspirasi, dan kegiatan warga.

---

## 🚀 Fitur Utama

### 📱 Portal Warga (Responsive Mobile App)
- **Beranda Mandiri**: Profil kependudukan, pengumuman terbaru, & jadwal kegiatan.
- **Pengumuman Feed**: Informasi resmi dari pengurus RT/RW dengan filter kategori.
- **Form & Tracking Aspirasi**: Pengiriman keluhan/saran warga & pemantauan status real-time.
- **Kegiatan & RSVP**: Pendaftaran kehadiran gotong royong, posyandu, & arisan.
- **Pengajuan Surat Pengantar**: Permohonan Surat Keterangan Domisili, SKU, SKTM, dll.
- **Iuran & Pembayaran**: Tagihan iuran & konfirmasi pembayaran via transfer/QRIS.

### 🖥️ Portal Administrator (Desktop Portal)
- **Dashboard Analytics**: Metrics kependudukan, saldo kas RT, & notifikasi permohonan.
- **Master Data Wilayah & Warga**: CRUD Data RT, Data Keluarga (KK), & Data Warga.
- **Kegiatan & Absensi**: Agenda acara, Penanggung Jawab (PJ Warga), & presensi (`hadir`, `izin`, `alfa`).
- **Validasi Surat & TTE**: Peninjauan & penerbitan surat pengantar ber-TTE Digital.
- **Verifikasi Iuran & Kas**: Verifikasi bukti pembayaran warga & pencatatan Buku Kas Keuangan (Pemasukan/Pengeluaran).
- **Inventaris RT**: Manajemen aset barang & kondisi lokasi simpan.
- **Super Admin**: Pengelolaan akses akun administrator.

---

## 📂 Struktur ERD Database (13 Tabel)

1. `rt`
2. `keluarga`
3. `warga`
4. `super_admin`
5. `pengumuman`
6. `aspirasi`
7. `surat`
8. `inventaris`
9. `kegiatan`
10. `peserta_kegiatan`
11. `iuran`
12. `pembayaran`
13. `keuangan`

---

## 🛠️ Cara Menjalankan Aplikasi

1. Clone repositori ini:
   ```bash
   git clone https://github.com/USERNAME/REPO_NAME.git
   cd REPO_NAME
   ```

2. Install dependensi Python:
   ```bash
   pip install flask werkzeug
   ```

3. Jalankan aplikasi:
   ```bash
   python app.py
   ```

4. Buka di browser:
   `http://127.0.0.1:5000`

### 🔑 Akun Demo Default
- **Admin**: `admin` / `admin123`
- **Warga**: `warga` / `warga123`
