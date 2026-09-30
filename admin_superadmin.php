<?php
// admin_superadmin.php - Super Admin Dashboard for RT/RW Master Data
$active_tab = 'superadmin';
require_once 'includes/header_admin.php';

if (get_user_role() !== 'super_admin') {
    header("Location: admin_dashboard.php");
    exit;
}

$message = null;

// Handle Add RT
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['nomor_rt'])) {
    $nomor_rt = trim($_POST['nomor_rt']);
    $rw = trim($_POST['rw']);
    $kelurahan = trim($_POST['kelurahan']);
    $kecamatan = trim($_POST['kecamatan']);

    $stmtIns = $pdo->prepare("INSERT INTO rt (nomor_rt, rw, kelurahan, kecamatan) VALUES (:n, :rw, :kel, :kec)");
    $stmtIns->execute(['n' => $nomor_rt, 'rw' => $rw, 'kel' => $kelurahan, 'kec' => $kecamatan]);
    $message = "Unit RT baru berhasil didaftarkan!";
}

// Fetch all RT units
$stmtRt = $pdo->query("SELECT * FROM rt ORDER BY id_rt ASC");
$rt_list = $stmtRt->fetchAll();

// Fetch Super Admin accounts
$stmtSA = $pdo->query("SELECT * FROM super_admin");
$sa_list = $stmtSA->fetchAll();
?>

<div class="flex justify-between items-center bg-purple-50 p-6 rounded-xl border border-purple-200">
    <div>
        <h1 class="text-2xl font-bold text-purple-900">Dashboard Super Admin</h1>
        <p class="text-sm text-purple-700">Kelola Data Master Wilayah RT, RW, Kelurahan, Kecamatan & Administrator</p>
    </div>
    <button onclick="document.getElementById('modalTambahRT').classList.remove('hidden')" class="px-4 py-2 bg-purple-700 text-white text-xs font-bold rounded-lg hover:bg-purple-800 flex items-center gap-1">
        <span class="material-symbols-outlined text-sm">add_home_work</span> Register Unit RT Baru
    </button>
</div>

<?php if ($message): ?>
<div class="p-3 bg-green-100 text-green-800 rounded-lg text-sm font-semibold">
    <?= htmlspecialchars($message) ?>
</div>
<?php endif; ?>

<!-- Master RT Table -->
<div class="bg-white rounded-xl border border-outline-variant shadow-sm overflow-hidden">
    <div class="p-4 bg-gray-50 border-b border-outline-variant font-bold text-sm text-on-surface">
        Daftar Unit RT Terdaftar Dalam Sistem
    </div>
    <div class="overflow-x-auto">
        <table class="w-full text-left text-sm">
            <thead class="bg-surface-container-low text-xs text-outline uppercase">
                <tr>
                    <th class="p-3">ID RT</th>
                    <th class="p-3">Nomor RT</th>
                    <th class="p-3">Nomor RW</th>
                    <th class="p-3">Kelurahan</th>
                    <th class="p-3">Kecamatan</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-outline-variant">
                <?php foreach ($rt_list as $r): ?>
                <tr>
                    <td class="p-3 font-mono text-xs">#<?= $r['id_rt'] ?></td>
                    <td class="p-3 font-bold text-primary">RT <?= htmlspecialchars($r['nomor_rt']) ?></td>
                    <td class="p-3 font-semibold">RW <?= htmlspecialchars($r['rw']) ?></td>
                    <td class="p-3"><?= htmlspecialchars($r['kelurahan']) ?></td>
                    <td class="p-3"><?= htmlspecialchars($r['kecamatan']) ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal Tambah RT -->
<div id="modalTambahRT" class="fixed inset-0 bg-black/50 z-50 hidden flex items-center justify-center p-4">
    <div class="bg-white rounded-xl max-w-md w-full p-6 space-y-4">
        <h3 class="font-bold text-lg text-on-surface">Register Wilayah RT Baru</h3>
        <form action="admin_superadmin.php" method="POST" class="space-y-3">
            <div class="grid grid-cols-2 gap-2">
                <div>
                    <label class="text-xs font-semibold text-outline">Nomor RT</label>
                    <input type="text" name="nomor_rt" class="w-full h-10 border rounded-lg px-3 text-sm" placeholder="002" required />
                </div>
                <div>
                    <label class="text-xs font-semibold text-outline">Nomor RW</label>
                    <input type="text" name="rw" class="w-full h-10 border rounded-lg px-3 text-sm" placeholder="005" required />
                </div>
            </div>
            <div>
                <label class="text-xs font-semibold text-outline">Kelurahan</label>
                <input type="text" name="kelurahan" class="w-full h-10 border rounded-lg px-3 text-sm" placeholder="Mekar" required />
            </div>
            <div>
                <label class="text-xs font-semibold text-outline">Kecamatan</label>
                <input type="text" name="kecamatan" class="w-full h-10 border rounded-lg px-3 text-sm" placeholder="Cibinong" required />
            </div>
            <div class="flex justify-end gap-2 pt-2">
                <button type="button" onclick="document.getElementById('modalTambahRT').classList.add('hidden')" class="px-4 py-2 border rounded-lg text-xs font-semibold">Batal</button>
                <button type="submit" class="px-4 py-2 bg-purple-700 text-white rounded-lg text-xs font-semibold">Daftarkan RT</button>
            </div>
        </form>
    </div>
</div>

<?php require_once 'includes/footer_admin.php'; ?>
