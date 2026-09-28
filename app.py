import os
import sqlite3
from flask import Flask, render_template, request, redirect, url_for, session, flash, jsonify
from werkzeug.security import check_password_hash, generate_password_hash
from werkzeug.utils import secure_filename
from database import get_db, init_db

app = Flask(__name__)
app.secret_key = 'wargaconnect_super_secret_key_rt_rw'
UPLOAD_FOLDER = os.path.join(os.path.dirname(__file__), 'static', 'uploads')
os.makedirs(UPLOAD_FOLDER, exist_ok=True)
app.config['UPLOAD_FOLDER'] = UPLOAD_FOLDER

# Ensure DB initialized on startup
init_db()

# --- HELPER FUNCTIONS & DECORATORS ---
def is_logged_in():
    return 'user_id' in session or 'admin_id' in session

def current_user():
    db = get_db()
    if 'user_id' in session:
        user = db.execute("""
            SELECT w.*, k.alamat, k.kode_pos, r.nomor_rt, r.rw, r.kelurahan, r.kecamatan 
            FROM warga w 
            JOIN keluarga k ON w.id_keluarga = k.id_keluarga
            JOIN rt r ON k.id_rt = r.id_rt
            WHERE w.id_warga = ?
        """, (session['user_id'],)).fetchone()
        return dict(user) if user else None
    elif 'admin_id' in session:
        admin = db.execute("SELECT * FROM super_admin WHERE id_superadmin = ?", (session['admin_id'],)).fetchone()
        return dict(admin) if admin else None
    return None

# --- AUTH ROUTES ---
@app.route('/login', methods=['GET', 'POST'])
def login():
    if request.method == 'POST':
        identifier = request.form.get('identifier', '').strip()
        password = request.form.get('password', '').strip()
        
        db = get_db()
        
        # First check Super Admin
        admin = db.execute("SELECT * FROM super_admin WHERE username = ?", (identifier,)).fetchone()
        if admin and check_password_hash(admin['password'], password):
            session.clear()
            session['admin_id'] = admin['id_superadmin']
            session['role'] = 'super_admin'
            session['nama'] = admin['nama']
            flash('Selamat datang di Portal Administrasi RT/RW!', 'success')
            return redirect(url_for('admin_dashboard'))
        
        # Then check Warga (by email_username or phone)
        warga = db.execute("SELECT * FROM warga WHERE email_username = ? OR no_telepon = ?", (identifier, identifier)).fetchone()
        if warga and check_password_hash(warga['password'], password):
            session.clear()
            session['user_id'] = warga['id_warga']
            session['role'] = warga['role']
            session['nama'] = warga['nama']
            flash('Selamat datang di WargaConnect!', 'success')
            return redirect(url_for('warga_beranda'))
            
        flash('Username / No HP atau Kata Sandi salah!', 'error')
        
    return render_template('login.html')

@app.route('/logout')
def logout():
    session.clear()
    flash('Anda telah keluar.', 'info')
    return redirect(url_for('login'))

# --- WARGA ROUTES ---
@app.route('/')
@app.route('/warga/beranda')
def warga_beranda():
    if not is_logged_in():
        return redirect(url_for('login'))
    
    db = get_db()
    user = current_user()
    
    pengumuman_list = db.execute("SELECT * FROM pengumuman ORDER BY tanggal_publish DESC LIMIT 3").fetchall()
    kegiatan_list = db.execute("SELECT k.*, w.nama as pj_nama FROM kegiatan k LEFT JOIN warga w ON k.id_warga = w.id_warga ORDER BY k.tanggal_kegiatan DESC LIMIT 3").fetchall()
    aspirasi_user = db.execute("SELECT * FROM aspirasi WHERE id_warga = ? ORDER BY id_pengaduan DESC LIMIT 3", (session.get('user_id', 0),)).fetchall()
    
    return render_template('warga/beranda.html', user=user, pengumuman=pengumuman_list, kegiatan=kegiatan_list, aspirasi=aspirasi_user)

@app.route('/warga/pengumuman')
def warga_pengumuman():
    if not is_logged_in():
        return redirect(url_for('login'))
    db = get_db()
    user = current_user()
    list_pengumuman = db.execute("SELECT p.*, r.nomor_rt, r.rw FROM pengumuman p JOIN rt r ON p.id_rt = r.id_rt ORDER BY p.tanggal_publish DESC").fetchall()
    return render_template('warga/pengumuman.html', user=user, pengumuman=list_pengumuman)

@app.route('/warga/aspirasi', methods=['GET', 'POST'])
def warga_aspirasi():
    if not is_logged_in() or 'user_id' not in session:
        return redirect(url_for('login'))
    
    db = get_db()
    user = current_user()
    
    if request.method == 'POST':
        judul = request.form.get('judul')
        isi_laporan = request.form.get('isi_laporan')
        foto = request.files.get('foto_bukti')
        foto_path = None
        if foto and foto.filename:
            fname = secure_filename(foto.filename)
            foto.save(os.path.join(app.config['UPLOAD_FOLDER'], fname))
            foto_path = fname
            
        db.execute("INSERT INTO aspirasi (id_warga, judul, isi_laporan, foto_bukti, status) VALUES (?, ?, ?, ?, 'pending')",
                   (session['user_id'], judul, isi_laporan, foto_path))
        db.commit()
        flash('Aspirasi Anda berhasil dikirim!', 'success')
        return redirect(url_for('warga_aspirasi'))
        
    aspirasi_saya = db.execute("SELECT * FROM aspirasi WHERE id_warga = ? ORDER BY id_pengaduan DESC", (session['user_id'],)).fetchall()
    return render_template('warga/aspirasi.html', user=user, aspirasi=aspirasi_saya)

@app.route('/warga/kegiatan')
def warga_kegiatan():
    if not is_logged_in():
        return redirect(url_for('login'))
    db = get_db()
    user = current_user()
    kegiatan_list = db.execute("""
        SELECT k.*, w.nama as pj_nama,
        (SELECT COUNT(*) FROM peserta_kegiatan pk WHERE pk.id_kegiatan = k.id_kegiatan) as total_peserta,
        (SELECT status_kehadiran FROM peserta_kegiatan pk WHERE pk.id_kegiatan = k.id_kegiatan AND pk.id_warga = ?) as my_status
        FROM kegiatan k 
        LEFT JOIN warga w ON k.id_warga = w.id_warga 
        ORDER BY k.tanggal_kegiatan DESC
    """, (session.get('user_id', 0),)).fetchall()
    return render_template('warga/kegiatan.html', user=user, kegiatan=kegiatan_list)

@app.route('/warga/kegiatan/ikut/<int:id_kegiatan>', methods=['POST'])
def warga_kegiatan_ikut(id_kegiatan):
    if not is_logged_in() or 'user_id' not in session:
        return redirect(url_for('login'))
    db = get_db()
    existing = db.execute("SELECT * FROM peserta_kegiatan WHERE id_kegiatan = ? AND id_warga = ?", (id_kegiatan, session['user_id'])).fetchone()
    if not existing:
        db.execute("INSERT INTO peserta_kegiatan (id_kegiatan, id_warga, status_kehadiran) VALUES (?, ?, 'hadir')", (id_kegiatan, session['user_id']))
        db.commit()
        flash('Anda berhasil mendaftar keikutsertaan kegiatan!', 'success')
    else:
        flash('Anda sudah terdaftar pada kegiatan ini.', 'info')
    return redirect(url_for('warga_kegiatan'))

@app.route('/warga/surat', methods=['GET', 'POST'])
def warga_surat():
    if not is_logged_in() or 'user_id' not in session:
        return redirect(url_for('login'))
    db = get_db()
    user = current_user()
    if request.method == 'POST':
        jenis_surat = request.form.get('jenis_surat')
        keperluan = request.form.get('keperluan')
        no_surat = f"0{session['user_id']}/{jenis_surat[:3].upper()}/RT02/V/2024"
        db.execute("INSERT INTO surat (id_warga, jenis_surat, tanggal_surat, keperluan, no_surat, status) VALUES (?, ?, DATE('now'), ?, ?, 'pending')",
                   (session['user_id'], jenis_surat, keperluan, no_surat))
        db.commit()
        flash('Pengajuan surat pengantar berhasil dibuat!', 'success')
        return redirect(url_for('warga_surat'))
        
    surat_saya = db.execute("SELECT * FROM surat WHERE id_warga = ? ORDER BY id_surat DESC", (session['user_id'],)).fetchall()
    return render_template('warga/surat.html', user=user, surat=surat_saya)

@app.route('/warga/iuran', methods=['GET', 'POST'])
def warga_iuran():
    if not is_logged_in() or 'user_id' not in session:
        return redirect(url_for('login'))
    db = get_db()
    user = current_user()
    
    if request.method == 'POST':
        id_iuran = request.form.get('id_iuran')
        metode = request.form.get('metode_bayar')
        jumlah = request.form.get('jumlah_bayar')
        bukti = request.files.get('bukti_bayar')
        bukti_path = 'default_bukti.jpg'
        if bukti and bukti.filename:
            fname = secure_filename(bukti.filename)
            bukti.save(os.path.join(app.config['UPLOAD_FOLDER'], fname))
            bukti_path = fname
            
        db.execute("""INSERT INTO pembayaran 
            (id_iuran, id_warga, tanggal_bayar, bukti_bayar, metode_bayar, jumlah_bayar, status_verifikasi) 
            VALUES (?, ?, DATETIME('now'), ?, ?, ?, 'pending')""",
            (id_iuran, session['user_id'], bukti_path, metode, jumlah))
        db.commit()
        flash('Pembayaran iuran berhasil dikirim, menunggu verifikasi Admin.', 'success')
        return redirect(url_for('warga_iuran'))
        
    iuran_list = db.execute("SELECT * FROM iuran ORDER BY id_iuran DESC").fetchall()
    pembayaran_saya = db.execute("""
        SELECT p.*, i.nama_iuran 
        FROM pembayaran p 
        JOIN iuran i ON p.id_iuran = i.id_iuran 
        WHERE p.id_warga = ? 
        ORDER BY p.id_pembayaran DESC
    """, (session['user_id'],)).fetchall()
    
    return render_template('warga/iuran.html', user=user, iuran=iuran_list, pembayaran=pembayaran_saya)

@app.route('/warga/profil')
def warga_profil():
    if not is_logged_in():
        return redirect(url_for('login'))
    user = current_user()
    return render_template('warga/profil.html', user=user)

# --- ADMIN PORTAL ROUTES ---
@app.route('/admin/dashboard')
def admin_dashboard():
    if not is_logged_in() or session.get('role') != 'super_admin':
        flash('Akses khusus Super Admin!', 'error')
        return redirect(url_for('login'))
        
    db = get_db()
    admin = current_user()
    
    # Counts & Metrics
    total_warga = db.execute("SELECT COUNT(*) FROM warga").fetchone()[0]
    total_kk = db.execute("SELECT COUNT(*) FROM keluarga").fetchone()[0]
    total_surat_pending = db.execute("SELECT COUNT(*) FROM surat WHERE status = 'pending'").fetchone()[0]
    total_pembayaran_pending = db.execute("SELECT COUNT(*) FROM pembayaran WHERE status_verifikasi = 'pending'").fetchone()[0]
    
    # Financial total
    pemasukan = db.execute("SELECT COALESCE(SUM(jumlah), 0) FROM keuangan WHERE jenis = 'pemasukan'").fetchone()[0]
    pengeluaran = db.execute("SELECT COALESCE(SUM(jumlah), 0) FROM keuangan WHERE jenis = 'pengeluaran'").fetchone()[0]
    saldo_kas = pemasukan - pengeluaran
    
    recent_surat = db.execute("SELECT s.*, w.nama FROM surat s JOIN warga w ON s.id_warga = w.id_warga ORDER BY s.id_surat DESC LIMIT 4").fetchall()
    recent_pembayaran = db.execute("SELECT p.*, w.nama, i.nama_iuran FROM pembayaran p JOIN warga w ON p.id_warga = w.id_warga JOIN iuran i ON p.id_iuran = i.id_iuran ORDER BY p.id_pembayaran DESC LIMIT 3").fetchall()
    recent_aspirasi = db.execute("SELECT a.*, w.nama FROM aspirasi a JOIN warga w ON a.id_warga = w.id_warga ORDER BY a.id_pengaduan DESC LIMIT 3").fetchall()
    
    return render_template('admin/dashboard.html', 
                           admin=admin, 
                           total_warga=total_warga, 
                           total_kk=total_kk, 
                           total_surat_pending=total_surat_pending,
                           total_pembayaran_pending=total_pembayaran_pending,
                           saldo_kas=saldo_kas,
                           pemasukan=pemasukan,
                           pengeluaran=pengeluaran,
                           recent_surat=recent_surat,
                           recent_pembayaran=recent_pembayaran,
                           recent_aspirasi=recent_aspirasi)

@app.route('/admin/rt', methods=['GET', 'POST'])
def admin_rt():
    if not is_logged_in() or session.get('role') != 'super_admin':
        return redirect(url_for('login'))
    db = get_db()
    if request.method == 'POST':
        action = request.form.get('action')
        if action == 'add':
            db.execute("INSERT INTO rt (nomor_rt, rw, kelurahan, kecamatan) VALUES (?, ?, ?, ?)",
                       (request.form['nomor_rt'], request.form['rw'], request.form['kelurahan'], request.form['kecamatan']))
        elif action == 'delete':
            db.execute("DELETE FROM rt WHERE id_rt = ?", (request.form['id_rt'],))
        db.commit()
        return redirect(url_for('admin_rt'))
        
    rt_list = db.execute("""
        SELECT r.*, 
        (SELECT COUNT(*) FROM keluarga k WHERE k.id_rt = r.id_rt) as total_kk
        FROM rt r ORDER BY r.nomor_rt ASC
    """).fetchall()
    return render_template('admin/data_rt.html', admin=current_user(), rt_list=rt_list)

@app.route('/admin/keluarga', methods=['GET', 'POST'])
def admin_keluarga():
    if not is_logged_in() or session.get('role') != 'super_admin':
        return redirect(url_for('login'))
    db = get_db()
    if request.method == 'POST':
        action = request.form.get('action')
        if action == 'add':
            db.execute("INSERT INTO keluarga (id_rt, alamat, kode_pos) VALUES (?, ?, ?)",
                       (request.form['id_rt'], request.form['alamat'], request.form['kode_pos']))
        elif action == 'delete':
            db.execute("DELETE FROM keluarga WHERE id_keluarga = ?", (request.form['id_keluarga'],))
        db.commit()
        return redirect(url_for('admin_keluarga'))
        
    keluarga_list = db.execute("""
        SELECT k.*, r.nomor_rt, r.rw,
        (SELECT COUNT(*) FROM warga w WHERE w.id_keluarga = k.id_keluarga) as total_anggota
        FROM keluarga k JOIN rt r ON k.id_rt = r.id_rt ORDER BY k.id_keluarga DESC
    """).fetchall()
    rt_list = db.execute("SELECT * FROM rt ORDER BY nomor_rt ASC").fetchall()
    return render_template('admin/data_keluarga.html', admin=current_user(), keluarga_list=keluarga_list, rt_list=rt_list)

@app.route('/admin/warga', methods=['GET', 'POST'])
def admin_warga():
    if not is_logged_in() or session.get('role') != 'super_admin':
        return redirect(url_for('login'))
    db = get_db()
    if request.method == 'POST':
        action = request.form.get('action')
        if action == 'add':
            pwd = generate_password_hash(request.form.get('password', 'warga123'))
            db.execute("""INSERT INTO warga 
                (id_keluarga, nama, jenis_kelamin, no_telepon, email_username, password, role, status_warga) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)""",
                (request.form['id_keluarga'], request.form['nama'], request.form['jenis_kelamin'],
                 request.form['no_telepon'], request.form['email_username'], pwd,
                 request.form['role'], request.form['status_warga']))
        elif action == 'delete':
            db.execute("DELETE FROM warga WHERE id_warga = ?", (request.form['id_warga'],))
        db.commit()
        return redirect(url_for('admin_warga'))
        
    warga_list = db.execute("""
        SELECT w.*, k.alamat, r.nomor_rt 
        FROM warga w 
        JOIN keluarga k ON w.id_keluarga = k.id_keluarga
        JOIN rt r ON k.id_rt = r.id_rt
        ORDER BY w.id_warga DESC
    """).fetchall()
    keluarga_list = db.execute("SELECT k.*, r.nomor_rt FROM keluarga k JOIN rt r ON k.id_rt = r.id_rt").fetchall()
    return render_template('admin/data_warga.html', admin=current_user(), warga_list=warga_list, keluarga_list=keluarga_list)

@app.route('/admin/kegiatan', methods=['GET', 'POST'])
def admin_kegiatan():
    if not is_logged_in() or session.get('role') != 'super_admin':
        return redirect(url_for('login'))
    db = get_db()
    if request.method == 'POST':
        action = request.form.get('action')
        if action == 'add':
            db.execute("""INSERT INTO kegiatan (id_rt, id_warga, nama_kegiatan, tanggal_kegiatan, lokasi, keterangan)
                VALUES (?, ?, ?, ?, ?, ?)""",
                (request.form['id_rt'], request.form['id_warga'] or None, request.form['nama_kegiatan'],
                 request.form['tanggal_kegiatan'], request.form['lokasi'], request.form['keterangan']))
        elif action == 'delete':
            db.execute("DELETE FROM kegiatan WHERE id_kegiatan = ?", (request.form['id_kegiatan'],))
        db.commit()
        return redirect(url_for('admin_kegiatan'))
        
    kegiatan_list = db.execute("""
        SELECT k.*, r.nomor_rt, w.nama as pj_nama,
        (SELECT COUNT(*) FROM peserta_kegiatan pk WHERE pk.id_kegiatan = k.id_kegiatan) as total_peserta
        FROM kegiatan k 
        JOIN rt r ON k.id_rt = r.id_rt
        LEFT JOIN warga w ON k.id_warga = w.id_warga
        ORDER BY k.tanggal_kegiatan DESC
    """).fetchall()
    rt_list = db.execute("SELECT * FROM rt").fetchall()
    warga_list = db.execute("SELECT * FROM warga").fetchall()
    return render_template('admin/kegiatan.html', admin=current_user(), kegiatan_list=kegiatan_list, rt_list=rt_list, warga_list=warga_list)

@app.route('/admin/peserta-kegiatan', methods=['GET', 'POST'])
def admin_peserta_kegiatan():
    if not is_logged_in() or session.get('role') != 'super_admin':
        return redirect(url_for('login'))
    db = get_db()
    if request.method == 'POST':
        action = request.form.get('action')
        if action == 'add':
            db.execute("INSERT INTO peserta_kegiatan (id_kegiatan, id_warga, status_kehadiran) VALUES (?, ?, ?)",
                       (request.form['id_kegiatan'], request.form['id_warga'], request.form['status_kehadiran']))
        elif action == 'update_status':
            db.execute("UPDATE peserta_kegiatan SET status_kehadiran = ? WHERE id_peserta = ?",
                       (request.form['status_kehadiran'], request.form['id_peserta']))
        elif action == 'delete':
            db.execute("DELETE FROM peserta_kegiatan WHERE id_peserta = ?", (request.form['id_peserta'],))
        db.commit()
        return redirect(url_for('admin_peserta_kegiatan'))
        
    peserta_list = db.execute("""
        SELECT pk.*, k.nama_kegiatan, k.tanggal_kegiatan, w.nama as nama_warga, r.nomor_rt
        FROM peserta_kegiatan pk
        JOIN kegiatan k ON pk.id_kegiatan = k.id_kegiatan
        JOIN warga w ON pk.id_warga = w.id_warga
        JOIN keluarga kel ON w.id_keluarga = kel.id_keluarga
        JOIN rt r ON kel.id_rt = r.id_rt
        ORDER BY pk.id_peserta DESC
    """).fetchall()
    kegiatan_list = db.execute("SELECT * FROM kegiatan ORDER BY tanggal_kegiatan DESC").fetchall()
    warga_list = db.execute("SELECT * FROM warga ORDER BY nama ASC").fetchall()
    return render_template('admin/peserta_kegiatan.html', admin=current_user(), peserta_list=peserta_list, kegiatan_list=kegiatan_list, warga_list=warga_list)

@app.route('/admin/inventaris', methods=['GET', 'POST'])
def admin_inventaris():
    if not is_logged_in() or session.get('role') != 'super_admin':
        return redirect(url_for('login'))
    db = get_db()
    if request.method == 'POST':
        action = request.form.get('action')
        if action == 'add':
            db.execute("""INSERT INTO inventaris (id_rt, nama_inventaris, kategori, jumlah, satuan, kondisi, lokasi_simpan)
                VALUES (?, ?, ?, ?, ?, ?, ?)""",
                (request.form['id_rt'], request.form['nama_inventaris'], request.form['kategori'],
                 request.form['jumlah'], request.form['satuan'], request.form['kondisi'], request.form['lokasi_simpan']))
        elif action == 'delete':
            db.execute("DELETE FROM inventaris WHERE id_inventaris = ?", (request.form['id_inventaris'],))
        db.commit()
        return redirect(url_for('admin_inventaris'))
        
    inventaris_list = db.execute("""
        SELECT i.*, r.nomor_rt 
        FROM inventaris i JOIN rt r ON i.id_rt = r.id_rt 
        ORDER BY i.id_inventaris DESC
    """).fetchall()
    rt_list = db.execute("SELECT * FROM rt").fetchall()
    return render_template('admin/inventaris.html', admin=current_user(), inventaris_list=inventaris_list, rt_list=rt_list)

@app.route('/admin/surat', methods=['GET', 'POST'])
def admin_surat():
    if not is_logged_in() or session.get('role') != 'super_admin':
        return redirect(url_for('login'))
    db = get_db()
    if request.method == 'POST':
        id_surat = request.form.get('id_surat')
        status = request.form.get('status')
        db.execute("UPDATE surat SET status = ? WHERE id_surat = ?", (status, id_surat))
        db.commit()
        flash(f'Status surat ID #{id_surat} diperbarui menjadi {status}.', 'success')
        return redirect(url_for('admin_surat'))
        
    surat_list = db.execute("""
        SELECT s.*, w.nama as nama_warga, r.nomor_rt 
        FROM surat s 
        JOIN warga w ON s.id_warga = w.id_warga
        JOIN keluarga k ON w.id_keluarga = k.id_keluarga
        JOIN rt r ON k.id_rt = r.id_rt
        ORDER BY s.id_surat DESC
    """).fetchall()
    return render_template('admin/surat.html', admin=current_user(), surat_list=surat_list)

@app.route('/admin/iuran', methods=['GET', 'POST'])
def admin_iuran():
    if not is_logged_in() or session.get('role') != 'super_admin':
        return redirect(url_for('login'))
    db = get_db()
    if request.method == 'POST':
        action = request.form.get('action')
        if action == 'add':
            db.execute("""INSERT INTO iuran (id_rt, nama_iuran, nominal, jenis_periode, bulan, tahun, keterangan)
                VALUES (?, ?, ?, ?, ?, ?, ?)""",
                (request.form['id_rt'], request.form['nama_iuran'], request.form['nominal'],
                 request.form['jenis_periode'], request.form['bulan'], request.form['tahun'], request.form['keterangan']))
        elif action == 'delete':
            db.execute("DELETE FROM iuran WHERE id_iuran = ?", (request.form['id_iuran'],))
        db.commit()
        return redirect(url_for('admin_iuran'))
        
    iuran_list = db.execute("""
        SELECT i.*, r.nomor_rt 
        FROM iuran i JOIN rt r ON i.id_rt = r.id_rt 
        ORDER BY i.id_iuran DESC
    """).fetchall()
    rt_list = db.execute("SELECT * FROM rt").fetchall()
    return render_template('admin/iuran.html', admin=current_user(), iuran_list=iuran_list, rt_list=rt_list)

@app.route('/admin/pembayaran', methods=['GET', 'POST'])
def admin_pembayaran():
    if not is_logged_in() or session.get('role') != 'super_admin':
        return redirect(url_for('login'))
    db = get_db()
    if request.method == 'POST':
        id_pembayaran = request.form.get('id_pembayaran')
        status = request.form.get('status_verifikasi')
        db.execute("UPDATE pembayaran SET status_verifikasi = ? WHERE id_pembayaran = ?", (status, id_pembayaran))
        db.commit()
        flash('Verifikasi pembayaran berhasil diperbarui!', 'success')
        return redirect(url_for('admin_pembayaran'))
        
    pembayaran_list = db.execute("""
        SELECT p.*, w.nama as nama_warga, i.nama_iuran, r.nomor_rt
        FROM pembayaran p
        JOIN warga w ON p.id_warga = w.id_warga
        JOIN iuran i ON p.id_iuran = i.id_iuran
        JOIN rt r ON i.id_rt = r.id_rt
        ORDER BY p.id_pembayaran DESC
    """).fetchall()
    return render_template('admin/pembayaran.html', admin=current_user(), pembayaran_list=pembayaran_list)

@app.route('/admin/keuangan', methods=['GET', 'POST'])
def admin_keuangan():
    if not is_logged_in() or session.get('role') != 'super_admin':
        return redirect(url_for('login'))
    db = get_db()
    if request.method == 'POST':
        action = request.form.get('action')
        if action == 'add':
            db.execute("""INSERT INTO keuangan 
                (id_rt, id_warga, id_inventaris, tanggal, jenis, kategori, jumlah, keterangan, bukti_nota)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)""",
                (request.form['id_rt'], request.form['id_warga'] or None, request.form['id_inventaris'] or None,
                 request.form['tanggal'], request.form['jenis'], request.form['kategori'],
                 request.form['jumlah'], request.form['keterangan'], request.form.get('bukti_nota')))
        elif action == 'delete':
            db.execute("DELETE FROM keuangan WHERE id_keuangan = ?", (request.form['id_keuangan'],))
        db.commit()
        return redirect(url_for('admin_keuangan'))
        
    keuangan_list = db.execute("""
        SELECT k.*, r.nomor_rt, w.nama as nama_warga, i.nama_inventaris
        FROM keuangan k
        JOIN rt r ON k.id_rt = r.id_rt
        LEFT JOIN warga w ON k.id_warga = w.id_warga
        LEFT JOIN inventaris i ON k.id_inventaris = i.id_inventaris
        ORDER BY k.tanggal DESC
    """).fetchall()
    
    pemasukan = db.execute("SELECT COALESCE(SUM(jumlah), 0) FROM keuangan WHERE jenis = 'pemasukan'").fetchone()[0]
    pengeluaran = db.execute("SELECT COALESCE(SUM(jumlah), 0) FROM keuangan WHERE jenis = 'pengeluaran'").fetchone()[0]
    
    rt_list = db.execute("SELECT * FROM rt").fetchall()
    warga_list = db.execute("SELECT * FROM warga").fetchall()
    inventaris_list = db.execute("SELECT * FROM inventaris").fetchall()
    
    return render_template('admin/keuangan.html', admin=current_user(), 
                           keuangan_list=keuangan_list, pemasukan=pemasukan, pengeluaran=pengeluaran,
                           rt_list=rt_list, warga_list=warga_list, inventaris_list=inventaris_list)

@app.route('/admin/pengumuman', methods=['GET', 'POST'])
def admin_pengumuman():
    if not is_logged_in() or session.get('role') != 'super_admin':
        return redirect(url_for('login'))
    db = get_db()
    if request.method == 'POST':
        action = request.form.get('action')
        if action == 'add':
            db.execute("INSERT INTO pengumuman (id_rt, judul, konten) VALUES (?, ?, ?)",
                       (request.form['id_rt'], request.form['judul'], request.form['konten']))
        elif action == 'delete':
            db.execute("DELETE FROM pengumuman WHERE id_pengumuman = ?", (request.form['id_pengumuman'],))
        db.commit()
        return redirect(url_for('admin_pengumuman'))
        
    pengumuman_list = db.execute("""
        SELECT p.*, r.nomor_rt 
        FROM pengumuman p JOIN rt r ON p.id_rt = r.id_rt 
        ORDER BY p.tanggal_publish DESC
    """).fetchall()
    rt_list = db.execute("SELECT * FROM rt").fetchall()
    return render_template('admin/pengumuman.html', admin=current_user(), pengumuman_list=pengumuman_list, rt_list=rt_list)

@app.route('/admin/aspirasi', methods=['GET', 'POST'])
def admin_aspirasi():
    if not is_logged_in() or session.get('role') != 'super_admin':
        return redirect(url_for('login'))
    db = get_db()
    if request.method == 'POST':
        id_pengaduan = request.form.get('id_pengaduan')
        status = request.form.get('status')
        db.execute("UPDATE aspirasi SET status = ? WHERE id_pengaduan = ?", (status, id_pengaduan))
        db.commit()
        flash('Status aspirasi diperbarui!', 'success')
        return redirect(url_for('admin_aspirasi'))
        
    aspirasi_list = db.execute("""
        SELECT a.*, w.nama as nama_warga, r.nomor_rt 
        FROM aspirasi a
        JOIN warga w ON a.id_warga = w.id_warga
        JOIN keluarga k ON w.id_keluarga = k.id_keluarga
        JOIN rt r ON k.id_rt = r.id_rt
        ORDER BY a.id_pengaduan DESC
    """).fetchall()
    return render_template('admin/aspirasi.html', admin=current_user(), aspirasi_list=aspirasi_list)

@app.route('/admin/super-admin', methods=['GET', 'POST'])
def admin_super_admin():
    if not is_logged_in() or session.get('role') != 'super_admin':
        return redirect(url_for('login'))
    db = get_db()
    if request.method == 'POST':
        action = request.form.get('action')
        if action == 'add':
            pwd = generate_password_hash(request.form['password'])
            db.execute("INSERT INTO super_admin (username, password, nama) VALUES (?, ?, ?)",
                       (request.form['username'], pwd, request.form['nama']))
        elif action == 'delete':
            db.execute("DELETE FROM super_admin WHERE id_superadmin = ?", (request.form['id_superadmin'],))
        db.commit()
        return redirect(url_for('admin_super_admin'))
        
    admins = db.execute("SELECT * FROM super_admin ORDER BY id_superadmin DESC").fetchall()
    return render_template('admin/super_admin.html', admin=current_user(), admins=admins)

if __name__ == '__main__':
    app.run(debug=True, port=5000)
