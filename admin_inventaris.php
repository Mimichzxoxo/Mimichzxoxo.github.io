<?php
// admin_inventaris.php - Manage RT assets and inventory
$active_tab = 'inventaris';
require_once 'includes/header_admin.php';

$id_rt = $_SESSION['id_rt'] ?? 1;
$message = null;

// Handle Add Item
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['nama_barang'])) {
    $nama = trim($_POST['nama_barang']);
    $jumlah = intval($_POST['jumlah_barang']);
    $kondisi = trim($_POST['kondisi']);

    $stmtIns = $pdo->prepare("INSERT INTO inventaris (id_rt, nama_barang, jumlah_barang, kondisi) VALUES (:rt, :n, :j, :k)");
    $stmtIns->execute(['rt' => $id_rt, 'n' => $nama, 'j' => $jumlah, 'k' => $kondisi]);
    $message = "Barang inventaris berhasil ditambahkan!";
}

// Fetch Inventory list
$stmtInv = $pdo->query("SELECT * FROM inventaris ORDER BY id_barang DESC");
$inventaris_list = $stmtInv->fetchAll();
?>

<div class="flex justify-between items-center">
    <div>
        <h1 class="text-2xl font-bold text-on-surface">Inventaris & Aset RT</h1>
        <p class="text-sm text-on-surface-variant">Kelola daftar barang, tenda, kursi, dan sarana umum RT</p>
    </div>
    <button onclick="document.getElementById('modalTambahBarang').classList.remove('hidden')" class="px-4 py-2 bg-primary text-white text-xs font-bold rounded-lg hover:bg-primary-container flex items-center gap-1">
        <span class="material-symbols-outlined text-sm">inventory</span> Tambah Barang Baru
    </button>
</div>

<?php if ($message): ?>
<div class="p-3 bg-green-100 text-green-800 rounded-lg text-sm font-semibold">
    <?= htmlspecialchars($message) ?>
</div>
<?php endif; ?>

<div class="bg-white rounded-xl border border-outline-variant shadow-sm overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-left text-sm">
            <thead class="bg-surface-container-low text-xs text-outline uppercase">
                <tr>
                    <th class="p-3">Nama Barang / Aset</th>
                    <th class="p-3">Jumlah Unit</th>
                    <th class="p-3">Kondisi Barang</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-outline-variant">
                <?php foreach ($inventaris_list as $inv): ?>
                <tr>
                    <td class="p-3 font-semibold text-on-surface"><?= htmlspecialchars($inv['nama_barang']) ?></td>
                    <td class="p-3 font-bold text-primary"><?= number_format($inv['jumlah_barang']) ?> unit</td>
                    <td class="p-3">
                        <span class="px-2 py-0.5 rounded text-[10px] font-bold <?= ($inv['kondisi']==='Baik')?'bg-green-100 text-green-800':'bg-yellow-100 text-yellow-800' ?>">
                            <?= htmlspecialchars($inv['kondisi']) ?>
                        </span>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal Tambah Barang -->
<div id="modalTambahBarang" class="fixed inset-0 bg-black/50 z-50 hidden flex items-center justify-center p-4">
    <div class="bg-white rounded-xl max-w-md w-full p-6 space-y-4">
        <h3 class="font-bold text-lg text-on-surface">Form Tambah Barang Inventaris</h3>
        <form action="admin_inventaris.php" method="POST" class="space-y-3">
            <div>
                <label class="text-xs font-semibold text-outline">Nama Barang / Aset</label>
                <input type="text" name="nama_barang" class="w-full h-10 border rounded-lg px-3 text-sm" placeholder="Contoh: Tenda Hajatan / Kursi Lipat" required />
            </div>
            <div>
                <label class="text-xs font-semibold text-outline">Jumlah Unit</label>
                <input type="number" name="jumlah_barang" class="w-full h-10 border rounded-lg px-3 text-sm" value="1" required />
            </div>
            <div>
                <label class="text-xs font-semibold text-outline">Kondisi Barang</label>
                <select name="kondisi" class="w-full h-10 border rounded-lg px-3 text-sm">
                    <option value="Baik">Baik</option>
                    <option value="Perlu Perbaikan">Perlu Perbaikan</option>
                    <option value="Rusak Berat">Rusak Berat</option>
                </select>
            </div>
            <div class="flex justify-end gap-2 pt-2">
                <button type="button" onclick="document.getElementById('modalTambahBarang').classList.add('hidden')" class="px-4 py-2 border rounded-lg text-xs font-semibold">Batal</button>
                <button type="submit" class="px-4 py-2 bg-primary text-white rounded-lg text-xs font-semibold">Simpan Barang</button>
            </div>
        </form>
    </div>
</div>

<?php require_once 'includes/footer_admin.php'; ?>
