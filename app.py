from flask import Flask, render_template, request, redirect, url_for, session, flash
from datetime import datetime
import db

app = Flask(__name__)
app.secret_key = 'smart_rt_super_secret_key_2026'

# Initialize database schema & seed data
db.init_db()

def get_now_str():
    return datetime.now().strftime('%d %b %Y • %H:%M WIB')

@app.context_processor
def inject_global_vars():
    return dict(now_date=datetime.now().strftime('%A, %d %b %Y'))

# -----------------------------------------------------
# AUTH ROUTES
# -----------------------------------------------------

@app.route('/')
def index():
    if 'user_id' not in session:
        return redirect(url_for('login'))
    if session.get('role') in ['admin_rt', 'super_admin']:
        return redirect(url_for('admin_dashboard'))
    return redirect(url_for('warga_pengumuman'))

@app.route('/login', methods=['GET', 'POST'])
def login():
    error = None
    if request.method == 'POST':
        identifier = request.form.get('identifier', '').strip()
        password = request.form.get('password', '').strip()

        conn = db.get_db()
        cursor = conn.cursor()

        # Check Super Admin table
        cursor.execute("SELECT * FROM super_admin WHERE username = ? AND password = ?", (identifier, password))
        sa = cursor.fetchone()
        if sa:
            session['user_id'] = sa['id_superadmin']
            session['user_name'] = sa['nama']
            session['role'] = 'super_admin'
            session['id_rt'] = 1
            conn.close()
            return redirect(url_for('admin_dashboard'))

        # Check Warga table
        cursor.execute('''
            SELECT w.*, k.id_rt, r.nomor_rt, r.rw 
            FROM warga w 
            JOIN keluarga k ON w.id_keluarga = k.id_keluarga
            JOIN rt r ON k.id_rt = r.id_rt
            WHERE (w.email_username = ? OR w.no_telepon = ?) AND w.password = ?
        ''', (identifier, identifier, password))
        warga = cursor.fetchone()
        conn.close()

        if warga:
            session['user_id'] = warga['id_warga']
            session['user_name'] = warga['nama']
            session['role'] = warga['role']
            session['id_rt'] = warga['id_rt']
            session['nomor_rt'] = warga['nomor_rt']
            session['rw'] = warga['rw']

            if warga['role'] == 'admin_rt':
                return redirect(url_for('admin_dashboard'))
            else:
                return redirect(url_for('warga_pengumuman'))

        error = "Username/No HP atau password salah. Coba: budi / warga123 atau admin_rt / admin123"

    return render_template('login.html', error=error)

@app.route('/logout')
def logout():
    session.clear()
    return redirect(url_for('login'))

# -----------------------------------------------------
# WARGA ROUTES (CITIZEN PORTAL)
# -----------------------------------------------------

@app.route('/warga/pengumuman')
def warga_pengumuman():
    if 'user_id' not in session:
        return redirect(url_for('login'))
    
    conn = db.get_db()
    cursor = conn.cursor()
    cursor.execute('''
        SELECT p.*, r.nomor_rt, r.rw
        FROM pengumuman p
        JOIN rt r ON p.id_rt = r.id_rt
        ORDER BY p.tanggal_publish DESC
    ''')
    pengumuman_list = [dict(row) for row in cursor.fetchall()]
    conn.close()

    return render_template('warga_pengumuman.html', pengumuman_list=pengumuman_list)

@app.route('/warga/kegiatan')
def warga_kegiatan():
    if 'user_id' not in session:
        return redirect(url_for('login'))
    
    success_msg = request.args.get('msg')
    conn = db.get_db()
    cursor = conn.cursor()
    cursor.execute('''
        SELECT k.*, r.nomor_rt, w.nama AS pj_nama
        FROM kegiatan k
        JOIN rt r ON k.id_rt = r.id_rt
        LEFT JOIN warga w ON k.id_warga = w.id_warga
        ORDER BY k.tanggal_kegiatan ASC
    ''')
    kegiatan_list = [dict(row) for row in cursor.fetchall()]
    conn.close()

    return render_template('warga_kegiatan.html', kegiatan_list=kegiatan_list, success_msg=success_msg)

@app.route('/warga/kegiatan/rsvp', methods=['POST'])
def warga_kegiatan_rsvp():
    if 'user_id' not in session:
        return redirect(url_for('login'))
    
    id_kegiatan = request.form.get('id_kegiatan')
    id_warga = session['user_id']

    conn = db.get_db()
    cursor = conn.cursor()
    # Check existing RSVP
    cursor.execute("SELECT * FROM peserta_kegiatan WHERE id_kegiatan = ? AND id_warga = ?", (id_kegiatan, id_warga))
    if not cursor.fetchone():
        cursor.execute("INSERT INTO peserta_kegiatan (id_kegiatan, id_warga, status_kehadiran) VALUES (?, ?, 'hadir')", (id_kegiatan, id_warga))
        conn.commit()
    conn.close()

    return redirect(url_for('warga_kegiatan', msg='Terima kasih! Keikutsertaan Anda telah dicatat.'))

@app.route('/warga/aspirasi', methods=['GET', 'POST'])
def warga_aspirasi():
    if 'user_id' not in session:
        return redirect(url_for('login'))
    
    id_warga = session['user_id']
    success_msg = None

    if request.method == 'POST':
        judul = request.form.get('judul')
        isi_laporan = request.form.get('isi_laporan')
        
        conn = db.get_db()
        cursor = conn.cursor()
        cursor.execute("INSERT INTO aspirasi (id_warga, judul, isi_laporan, status) VALUES (?, ?, ?, 'pending')", (id_warga, judul, isi_laporan))
        conn.commit()
        conn.close()
        success_msg = "Aspirasi Anda berhasil dikirim dan sedang dalam antrean pengurus RT."

    conn = db.get_db()
    cursor = conn.cursor()
    cursor.execute("SELECT * FROM aspirasi WHERE id_warga = ? ORDER BY id_pengaduan DESC", (id_warga,))
    aspirasi_list = [dict(row) for row in cursor.fetchall()]
    conn.close()

    return render_template('warga_aspirasi.html', aspirasi_list=aspirasi_list, success_msg=success_msg)

@app.route('/warga/surat', methods=['GET', 'POST'])
def warga_surat():
    if 'user_id' not in session:
        return redirect(url_for('login'))
    
    id_warga = session['user_id']
    success_msg = None

    if request.method == 'POST':
        jenis_surat = request.form.get('jenis_surat')
        keperluan = request.form.get('keperluan')
        
        # Execute SP AjukanSuratPengantar
        res = db.sp_AjukanSuratPengantar(id_warga, jenis_surat, keperluan)
        success_msg = res.get('pesan')

    conn = db.get_db()
    cursor = conn.cursor()
    cursor.execute("SELECT * FROM surat WHERE id_warga = ? ORDER BY id_surat DESC", (id_warga,))
    surat_list = [dict(row) for row in cursor.fetchall()]
    conn.close()

    return render_template('warga_surat.html', surat_list=surat_list, success_msg=success_msg)

@app.route('/warga/iuran')
def warga_iuran():
    if 'user_id' not in session:
        return redirect(url_for('login'))
    
    id_warga = session['user_id']
    success_msg = request.args.get('msg')

    conn = db.get_db()
    cursor = conn.cursor()
    cursor.execute("SELECT * FROM iuran ORDER BY id_iuran DESC")
    iuran_list = [dict(row) for row in cursor.fetchall()]

    cursor.execute('''
        SELECT p.*, i.nama_iuran
        FROM pembayaran p
        JOIN iuran i ON p.id_iuran = i.id_iuran
        WHERE p.id_warga = ?
        ORDER BY p.id_pembayaran DESC
    ''', (id_warga,))
    pembayaran_list = [dict(row) for row in cursor.fetchall()]
    conn.close()

    return render_template('warga_iuran.html', iuran_list=iuran_list, pembayaran_list=pembayaran_list, success_msg=success_msg)

@app.route('/warga/iuran/bayar', methods=['POST'])
def warga_iuran_bayar():
    if 'user_id' not in session:
        return redirect(url_for('login'))
    
    id_warga = session['user_id']
    id_iuran = int(request.form.get('id_iuran'))
    metode_bayar = request.form.get('metode_bayar')
    jumlah_bayar = float(request.form.get('jumlah_bayar', 0))
    tgl_now = datetime.now().strftime('%Y-%m-%d %H:%M:%S')
    bukti_bayar = f"bukti_{id_warga}_{id_iuran}.jpg"

    # Execute SP CatatIuran
    res = db.sp_CatatIuran(id_iuran, id_warga, tgl_now, bukti_bayar, metode_bayar, jumlah_bayar)
    return redirect(url_for('warga_iuran', msg=res.get('pesan')))

# -----------------------------------------------------
# ADMIN & SUPER ADMIN PORTAL ROUTES
# -----------------------------------------------------

@app.route('/admin/dashboard')
def admin_dashboard():
    if 'user_id' not in session or session.get('role') not in ['admin_rt', 'super_admin']:
        return redirect(url_for('login'))
    
    conn = db.get_db()
    cursor = conn.cursor()

    cursor.execute("SELECT COUNT(*) FROM warga")
    total_warga = cursor.fetchone()[0]

    cursor.execute("SELECT COUNT(*) FROM keluarga")
    total_keluarga = cursor.fetchone()[0]

    cursor.execute("SELECT COALESCE(SUM(jumlah), 0) FROM keuangan WHERE jenis = 'pemasukan'")
    pemasukan = cursor.fetchone()[0]
    cursor.execute("SELECT COALESCE(SUM(jumlah), 0) FROM keuangan WHERE jenis = 'pengeluaran'")
    pengeluaran = cursor.fetchone()[0]
    saldo_kas = pemasukan - pengeluaran

    cursor.execute("SELECT COUNT(*) FROM surat WHERE status = 'Menunggu Validasi'")
    pending_surat = cursor.fetchone()[0]

    cursor.execute("SELECT COUNT(*) FROM pembayaran WHERE status_verifikasi = 'pending'")
    pending_pembayaran = cursor.fetchone()[0]

    cursor.execute('''
        SELECT s.*, w.nama AS nama_pemohon, r.nomor_rt
        FROM surat s
        JOIN warga w ON s.id_warga = w.id_warga
        JOIN keluarga k ON w.id_keluarga = k.id_keluarga
        JOIN rt r ON k.id_rt = r.id_rt
        ORDER BY s.id_surat DESC LIMIT 5
    ''')
    recent_surat = [dict(row) for row in cursor.fetchall()]

    conn.close()

    return render_template('admin_dashboard.html',
                           total_warga=total_warga,
                           total_keluarga=total_keluarga,
                           saldo_kas=saldo_kas,
                           pending_surat=pending_surat,
                           pending_pembayaran=pending_pembayaran,
                           recent_surat=recent_surat)

@app.route('/admin/surat')
def admin_surat():
    if 'user_id' not in session or session.get('role') not in ['admin_rt', 'super_admin']:
        return redirect(url_for('login'))
    
    select_id = request.args.get('select_id')
    success_msg = request.args.get('msg')

    conn = db.get_db()
    cursor = conn.cursor()

    cursor.execute('''
        SELECT s.*, w.nama AS nama_pemohon, w.jenis_kelamin, r.nomor_rt, r.rw
        FROM surat s
        JOIN warga w ON s.id_warga = w.id_warga
        JOIN keluarga k ON w.id_keluarga = k.id_keluarga
        JOIN rt r ON k.id_rt = r.id_rt
        ORDER BY s.id_surat DESC
    ''')
    surat_list = [dict(row) for row in cursor.fetchall()]

    active_surat = None
    if select_id:
        for s in surat_list:
            if str(s['id_surat']) == str(select_id):
                active_surat = s
                break
    if not active_surat and surat_list:
        active_surat = surat_list[0]

    cursor.execute("SELECT a.*, w.nama AS nama_warga FROM aspirasi a JOIN warga w ON a.id_warga = w.id_warga ORDER BY a.id_pengaduan DESC")
    aspirasi_list = [dict(row) for row in cursor.fetchall()]

    conn.close()

    return render_template('admin_surat.html', surat_list=surat_list, active_surat=active_surat, aspirasi_list=aspirasi_list, success_msg=success_msg)

@app.route('/admin/surat/approve', methods=['POST'])
def admin_surat_approve():
    if 'user_id' not in session or session.get('role') not in ['admin_rt', 'super_admin']:
        return redirect(url_for('login'))
    
    id_surat = request.form.get('id_surat')
    action = request.form.get('action')
    status_baru = 'Lunas / Selesai (TTE Terbit)' if action == 'approve' else 'Ditolak'
    no_surat = f"045/SKD/RT02/{datetime.now().strftime('%m/%Y')}" if action == 'approve' else None

    conn = db.get_db()
    cursor = conn.cursor()
    cursor.execute("UPDATE surat SET status = ?, no_surat = COALESCE(?, no_surat) WHERE id_surat = ?", (status_baru, no_surat, id_surat))
    conn.commit()
    conn.close()

    msg = "Permohonan surat berhasil disetujui & TTE Digital diterbitkan." if action == 'approve' else "Permohonan surat ditolak."
    return redirect(url_for('admin_surat', msg=msg))

@app.route('/admin/kegiatan')
def admin_kegiatan():
    if 'user_id' not in session or session.get('role') not in ['admin_rt', 'super_admin']:
        return redirect(url_for('login'))
    
    success_msg = request.args.get('msg')
    conn = db.get_db()
    cursor = conn.cursor()

    cursor.execute("SELECT * FROM rt")
    rt_list = [dict(row) for row in cursor.fetchall()]

    cursor.execute('''
        SELECT k.*, r.nomor_rt, w.nama AS pj_nama
        FROM kegiatan k
        JOIN rt r ON k.id_rt = r.id_rt
        LEFT JOIN warga w ON k.id_warga = w.id_warga
        ORDER BY k.id_kegiatan DESC
    ''')
    kegiatan_list = [dict(row) for row in cursor.fetchall()]

    cursor.execute('''
        SELECT pk.*, w.nama AS nama_warga, k.nama_kegiatan
        FROM peserta_kegiatan pk
        JOIN warga w ON pk.id_warga = w.id_warga
        JOIN kegiatan k ON pk.id_kegiatan = k.id_kegiatan
        ORDER BY pk.id_peserta DESC
    ''')
    peserta_list = [dict(row) for row in cursor.fetchall()]

    conn.close()

    return render_template('admin_kegiatan.html', rt_list=rt_list, kegiatan_list=kegiatan_list, peserta_list=peserta_list, success_msg=success_msg)

@app.route('/admin/kegiatan/tambah', methods=['POST'])
def admin_kegiatan_tambah():
    if 'user_id' not in session or session.get('role') not in ['admin_rt', 'super_admin']:
        return redirect(url_for('login'))
    
    id_rt = int(request.form.get('id_rt'))
    nama_kegiatan = request.form.get('nama_kegiatan')
    tanggal_kegiatan = request.form.get('tanggal_kegiatan')
    lokasi = request.form.get('lokasi')
    keterangan = request.form.get('keterangan')
    pj_warga = session['user_id']

    conn = db.get_db()
    cursor = conn.cursor()
    cursor.execute('''
        INSERT INTO kegiatan (id_rt, id_warga, nama_kegiatan, tanggal_kegiatan, lokasi, keterangan)
        VALUES (?, ?, ?, ?, ?, ?)
    ''', (id_rt, pj_warga, nama_kegiatan, tanggal_kegiatan, lokasi, keterangan))
    conn.commit()
    conn.close()

    return redirect(url_for('admin_kegiatan', msg='Agenda kegiatan baru berhasil disimpan.'))

@app.route('/admin/warga')
def admin_warga():
    if 'user_id' not in session or session.get('role') not in ['admin_rt', 'super_admin']:
        return redirect(url_for('login'))
    
    success_msg = request.args.get('msg')
    conn = db.get_db()
    cursor = conn.cursor()

    cursor.execute('''
        SELECT k.*, r.nomor_rt 
        FROM keluarga k
        JOIN rt r ON k.id_rt = r.id_rt
    ''')
    keluarga_list = [dict(row) for row in cursor.fetchall()]

    cursor.execute('''
        SELECT w.*, k.alamat, r.nomor_rt
        FROM warga w
        JOIN keluarga k ON w.id_keluarga = k.id_keluarga
        JOIN rt r ON k.id_rt = r.id_rt
        ORDER BY w.id_warga DESC
    ''')
    warga_list = [dict(row) for row in cursor.fetchall()]

    conn.close()

    return render_template('admin_warga.html', keluarga_list=keluarga_list, warga_list=warga_list, success_msg=success_msg)

@app.route('/admin/warga/tambah', methods=['POST'])
def admin_warga_tambah():
    if 'user_id' not in session or session.get('role') not in ['admin_rt', 'super_admin']:
        return redirect(url_for('login'))
    
    id_keluarga = int(request.form.get('id_keluarga'))
    nama = request.form.get('nama')
    jenis_kelamin = request.form.get('jenis_kelamin')
    no_telepon = request.form.get('no_telepon')
    email_username = request.form.get('email_username')
    password = request.form.get('password')
    role = request.form.get('role')
    status_warga = request.form.get('status_warga')

    # Execute SP TambahWarga
    res = db.sp_TambahWarga(1, id_keluarga, nama, jenis_kelamin, no_telepon, email_username, password, role, status_warga)
    return redirect(url_for('admin_warga', msg=res.get('pesan')))

@app.route('/admin/iuran')
def admin_iuran():
    if 'user_id' not in session or session.get('role') not in ['admin_rt', 'super_admin']:
        return redirect(url_for('login'))
    
    success_msg = request.args.get('msg')
    conn = db.get_db()
    cursor = conn.cursor()

    cursor.execute("SELECT * FROM rt")
    rt_list = [dict(row) for row in cursor.fetchall()]

    cursor.execute('''
        SELECT p.*, w.nama AS nama_warga, i.nama_iuran
        FROM pembayaran p
        JOIN warga w ON p.id_warga = w.id_warga
        JOIN iuran i ON p.id_iuran = i.id_iuran
        ORDER BY p.id_pembayaran DESC
    ''')
    pembayaran_list = [dict(row) for row in cursor.fetchall()]

    conn.close()

    return render_template('admin_iuran.html', rt_list=rt_list, pembayaran_list=pembayaran_list, success_msg=success_msg)

@app.route('/admin/iuran/master/tambah', methods=['POST'])
def admin_iuran_master_tambah():
    if 'user_id' not in session or session.get('role') not in ['admin_rt', 'super_admin']:
        return redirect(url_for('login'))
    
    id_rt = int(request.form.get('id_rt'))
    nama_iuran = request.form.get('nama_iuran')
    nominal = float(request.form.get('nominal', 0))
    bulan = request.form.get('bulan', 'Oktober')
    tahun = 2024

    conn = db.get_db()
    cursor = conn.cursor()
    cursor.execute('''
        INSERT INTO iuran (id_rt, nama_iuran, nominal, jenis_periode, bulan, tahun, keterangan)
        VALUES (?, ?, ?, 'Bulanan', ?, ?, 'Master iuran dibuat oleh admin')
    ''', (id_rt, nama_iuran, nominal, bulan, tahun))
    conn.commit()
    conn.close()

    return redirect(url_for('admin_iuran', msg='Master Iuran berhasil ditambahkan.'))

@app.route('/admin/iuran/verifikasi', methods=['POST'])
def admin_iuran_verifikasi():
    if 'user_id' not in session or session.get('role') not in ['admin_rt', 'super_admin']:
        return redirect(url_for('login'))
    
    id_pembayaran = int(request.form.get('id_pembayaran'))
    status_baru = request.form.get('status_baru')

    # Execute SP VerifikasiIuran
    res = db.sp_VerifikasiIuran(id_pembayaran, status_baru)
    return redirect(url_for('admin_iuran', msg=res.get('pesan')))

@app.route('/admin/keuangan', methods=['GET', 'POST'])
def admin_keuangan():
    if 'user_id' not in session or session.get('role') not in ['admin_rt', 'super_admin']:
        return redirect(url_for('login'))
    
    success_msg = None
    if request.method == 'POST':
        id_rt = int(request.form.get('id_rt'))
        jenis = request.form.get('jenis')
        kategori = request.form.get('kategori')
        jumlah = float(request.form.get('jumlah', 0))
        keterangan = request.form.get('keterangan')
        tgl_now = datetime.now().strftime('%Y-%m-%d %H:%M:%S')

        conn = db.get_db()
        cursor = conn.cursor()
        cursor.execute('''
            INSERT INTO keuangan (id_rt, id_warga, id_inventaris, tanggal, jenis, kategori, jumlah, keterangan, bukti_nota)
            VALUES (?, ?, NULL, ?, ?, ?, ?, ?, NULL)
        ''', (id_rt, session['user_id'], tgl_now, jenis, kategori, jumlah, keterangan))
        conn.commit()
        conn.close()
        success_msg = "Transaksi kas berhasil disimpan."

    conn = db.get_db()
    cursor = conn.cursor()

    cursor.execute("SELECT * FROM rt")
    rt_list = [dict(row) for row in cursor.fetchall()]

    cursor.execute('''
        SELECT k.*, r.nomor_rt
        FROM keuangan k
        JOIN rt r ON k.id_rt = r.id_rt
        ORDER BY k.id_keuangan DESC
    ''')
    keuangan_list = [dict(row) for row in cursor.fetchall()]

    conn.close()

    return render_template('admin_keuangan.html', rt_list=rt_list, keuangan_list=keuangan_list, success_msg=success_msg)

@app.route('/admin/inventaris', methods=['GET', 'POST'])
def admin_inventaris():
    if 'user_id' not in session or session.get('role') not in ['admin_rt', 'super_admin']:
        return redirect(url_for('login'))
    
    success_msg = None
    if request.method == 'POST':
        id_rt = int(request.form.get('id_rt'))
        nama_inventaris = request.form.get('nama_inventaris')
        kategori = request.form.get('kategori')
        jumlah = int(request.form.get('jumlah', 1))
        satuan = request.form.get('satuan', 'unit')
        kondisi = request.form.get('kondisi', 'Baik')
        lokasi_simpan = request.form.get('lokasi_simpan')

        conn = db.get_db()
        cursor = conn.cursor()
        cursor.execute('''
            INSERT INTO inventaris (id_rt, nama_inventaris, kategori, jumlah, satuan, kondisi, lokasi_simpan)
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ''', (id_rt, nama_inventaris, kategori, jumlah, satuan, kondisi, lokasi_simpan))
        conn.commit()
        conn.close()
        success_msg = "Barang inventaris baru berhasil didaftarkan."

    conn = db.get_db()
    cursor = conn.cursor()

    cursor.execute("SELECT * FROM rt")
    rt_list = [dict(row) for row in cursor.fetchall()]

    cursor.execute('''
        SELECT inv.*, r.nomor_rt
        FROM inventaris inv
        JOIN rt r ON inv.id_rt = r.id_rt
        ORDER BY inv.id_inventaris DESC
    ''')
    inventaris_list = [dict(row) for row in cursor.fetchall()]

    conn.close()

    return render_template('admin_inventaris.html', rt_list=rt_list, inventaris_list=inventaris_list, success_msg=success_msg)

@app.route('/admin/superadmin')
def admin_superadmin():
    if 'user_id' not in session or session.get('role') not in ['admin_rt', 'super_admin']:
        return redirect(url_for('login'))
    
    bulan = request.args.get('bulan', '')
    tahun = request.args.get('tahun', '')
    p_tahun = int(tahun) if tahun.isdigit() else None
    p_bulan = bulan if bulan else None

    # Execute SP GetDashboardSuperAdmin
    sp_dashboard_data = db.sp_GetDashboardSuperAdmin(p_bulan, p_tahun)

    return render_template('admin_superadmin.html', sp_dashboard_data=sp_dashboard_data, selected_bulan=bulan, selected_tahun=tahun)

if __name__ == '__main__':
    print("Starting Smart RT Web Server on http://127.0.0.1:5000")
    app.run(host='127.0.0.1', port=5000, debug=True)
