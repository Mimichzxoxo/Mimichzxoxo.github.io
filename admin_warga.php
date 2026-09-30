<?php
// admin_warga.php - Manage citizen data & add new citizens
$active_tab = 'warga';
require_once 'includes/header_admin.php';

$message = null;

// Handle Add Warga
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'tambah') {
    $nama = trim($_POST['nama']);
    $jenis_kelamin = trim($_POST['jenis_kelamin']);
    $no_telepon = trim($_POST['no_telepon']);
    $email_username = trim($_POST['email_username']);
    $password = trim($_POST['password']);
    $role = trim($_POST['role'] ?? 'warga');
    $status_warga = trim($_POST['status_warga'] ?? 'Tetap');
    $id_keluarga = intval($_POST['id_keluarga'] ?? 1);

    try {
        $stmtIns = $pdo->prepare("
            INSERT INTO warga (id_keluarga, nama, jenis_kelamin, no_telepon, email_username, password, role, status_warga) 
            VALUES (:k, :n, :jk, :telp, :user, :pwd, :role, :st)
        ");
        $stmtIns->execute([
            'k' => $id_keluarga,
            'n' => $nama,
            'jk' => $jenis_kelamin,
            'telp' => $no_telepon,
            'user' => $email_username,
            'pwd' => $password,
            'role' => $role,
            'st' => $status_warga
        ]);
        $message = "Berhasil menambahkan data warga baru!";
    } catch (PDOException $e) {
        $message = "Gagal menambahkan warga (Username/Email sudah terdaftar).";
    }
}

// Fetch all citizens
$stmtWarga = $pdo->query("
    SELECT w.*, k.alamat, k.kode_pos 
    FROM warga w 
    JOIN keluarga k ON w.id_keluarga = k.id_keluarga 
    ORDER BY w.id_warga DESC
");
$warga_list = $stmtWarga->fetchAll();

// Fetch families for select option
$stmtKeluarga = $pdo->query("SELECT id_keluarga, alamat FROM keluarga");
$keluarga_list = $stmtKeluarga->fetchAll();
?>

<div class="flex justify-between items-center">
    <div>
        <h1 class="text-2xl font-bold text-on-surface">Data Kependudukan Warga RT</h1>
        <p class="text-sm text-on-surface-variant">Kelola data warga, anggota keluarga, dan hak akses portal</p>
    </div>
    <button onclick="document.getElementById('modalTambah').classList.remove('hidden')" class="px-4 py-2 bg-primary text-white text-xs font-bold rounded-lg hover:bg-primary-container flex items-center gap-1">
        <span class="material-symbols-outlined text-sm">person_add</span> Tambah Warga Baru
    </button>
</div>

<?php if ($message): ?>
<div class="p-3 bg-blue-100 text-blue-900 rounded-lg text-sm font-semibold">
    <?= htmlspecialchars($message) ?>
</div>
<?php endif; ?>

<!-- Citizen Data Table -->
<div class="bg-white rounded-xl border border-outline-variant shadow-sm overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-left text-sm">
            <thead class="bg-surface-container-low text-xs text-outline uppercase">
                <tr>
                    <th class="p-3">Nama Lengkap</th>
                    <th class="p-3">JK</th>
                    <th class="p-3">No. Telepon</th>
                    <th class="p-3">Username</th>
                    <th class="p-3">Alamat</th>
                    <th class="p-3">Role</th>
                    <th class="p-3">Status</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-outline-variant">
                <?php foreach ($warga_list as $w): ?>
                <tr>
                    <td class="p-3 font-semibold text-on-surface"><?= htmlspecialchars($w['nama']) ?></td>
                    <td class="p-3"><?= htmlspecialchars($w['jenis_kelamin']) ?></td>
                    <td class="p-3"><?= htmlspecialchars($w['no_telepon']) ?></td>
                    <td class="p-3 font-mono text-xs"><?= htmlspecialchars($w['email_username']) ?></td>
                    <td class="p-3 text-xs text-outline"><?= htmlspecialchars($w['alamat']) ?></td>
                    <td class="p-3">
                        <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase <?= ($w['role']==='admin_rt')?'bg-blue-100 text-blue-800':'bg-gray-100 text-gray-800' ?>">
                            <?= htmlspecialchars($w['role']) ?>
                        </span>
                    </td>
                    <td class="p-3">
                        <span class="px-2 py-0.5 rounded text-[10px] font-bold <?= ($w['status_warga']==='Tetap')?'bg-green-100 text-green-800':'bg-yellow-100 text-yellow-800' ?>">
                            <?= htmlspecialchars($w['status_warga']) ?>
                        </span>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal Tambah Warga -->
<div id="modalTambah" class="fixed inset-0 bg-black/50 z-50 hidden flex items-center justify-center p-4">
    <div class="bg-white rounded-xl max-w-md w-full p-6 space-y-4">
        <h3 class="font-bold text-lg text-on-surface">Form Tambah Warga Baru</h3>
        <form action="admin_warga.php" method="POST" class="space-y-3">
            <input type="hidden" name="action" value="tambah">
            <div>
                <label class="text-xs font-semibold text-outline">Nama Lengkap</label>
                <input type="text" name="nama" class="w-full h-10 border rounded-lg px-3 text-sm" required />
            </div>
            <div class="grid grid-cols-2 gap-2">
                <div>
                    <label class="text-xs font-semibold text-outline">Jenis Kelamin</label>
                    <select name="jenis_kelamin" class="w-full h-10 border rounded-lg px-3 text-sm">
                        <option value="L">Laki-Laki (L)</option>
                        <option value="P">Perempuan (P)</option>
                    </select>
                </div>
                <div>
                    <label class="text-xs font-semibold text-outline">No Telepon</label>
                    <input type="text" name="no_telepon" class="w-full h-10 border rounded-lg px-3 text-sm" required />
                </div>
            </div>
            <div class="grid grid-cols-2 gap-2">
                <div>
                    <label class="text-xs font-semibold text-outline">Username / Email</label>
                    <input type="text" name="email_username" class="w-full h-10 border rounded-lg px-3 text-sm" required />
                </div>
                <div>
                    <label class="text-xs font-semibold text-outline">Password</label>
                    <input type="text" name="password" class="w-full h-10 border rounded-lg px-3 text-sm" value="warga123" required />
                </div>
            </div>
            <div class="grid grid-cols-2 gap-2">
                <div>
                    <label class="text-xs font-semibold text-outline">Role Sistem</label>
                    <select name="role" class="w-full h-10 border rounded-lg px-3 text-sm">
                        <option value="warga">Warga</option>
                        <option value="admin_rt">Admin RT</option>
                    </select>
                </div>
                <div>
                    <label class="text-xs font-semibold text-outline">Status Warga</label>
                    <select name="status_warga" class="w-full h-10 border rounded-lg px-3 text-sm">
                        <option value="Tetap">Tetap</option>
                        <option value="Kontrak">Kontrak</option>
                        <option value="Kos">Kos</option>
                    </select>
                </div>
            </div>
            <div>
                <label class="text-xs font-semibold text-outline">Pilih Keluarga / Rumah</label>
                <select name="id_keluarga" class="w-full h-10 border rounded-lg px-3 text-sm">
                    <?php foreach ($keluarga_list as $k): ?>
                    <option value="<?= $k['id_keluarga'] ?>"><?= htmlspecialchars($k['alamat']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="flex justify-end gap-2 pt-2">
                <button type="button" onclick="document.getElementById('modalTambah').classList.add('hidden')" class="px-4 py-2 border rounded-lg text-xs font-semibold">Batal</button>
                <button type="submit" class="px-4 py-2 bg-primary text-white rounded-lg text-xs font-semibold">Simpan Data Warga</button>
            </div>
        </form>
    </div>
</div>

<?php require_once 'includes/footer_admin.php'; ?>
