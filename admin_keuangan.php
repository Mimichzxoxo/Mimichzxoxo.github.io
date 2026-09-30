<?php
// admin_keuangan.php - Cash flow ledger (income & expenses)
$active_tab = 'keuangan';
require_once 'includes/header_admin.php';

$id_rt = $_SESSION['id_rt'] ?? 1;
$message = null;

// Handle manual income/expense entry
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['jenis_transaksi'])) {
    $jenis = trim($_POST['jenis_transaksi']);
    $kategori = trim($_POST['kategori']);
    $jumlah = floatval($_POST['jumlah']);
    $keterangan = trim($_POST['keterangan']);
    $tgl = date('Y-m-d H:i:s');

    $stmtIns = $pdo->prepare("
        INSERT INTO keuangan (id_rt, jenis_transaksi, kategori, jumlah, keterangan, tanggal) 
        VALUES (:rt, :j, :k, :jml, :ket, :t)
    ");
    $stmtIns->execute([
        'rt' => $id_rt,
        'j' => $jenis,
        'k' => $kategori,
        'jml' => $jumlah,
        'ket' => $keterangan,
        't' => $tgl
    ]);
    $message = "Pencatatan kas keuangan berhasil ditambahkan!";
}

// Calculate total income, expense, and current balance
$total_pemasukan = $pdo->query("SELECT SUM(jumlah) FROM keuangan WHERE jenis_transaksi = 'Pemasukan'")->fetchColumn() ?: 0;
$total_pengeluaran = $pdo->query("SELECT SUM(jumlah) FROM keuangan WHERE jenis_transaksi = 'Pengeluaran'")->fetchColumn() ?: 0;
$saldo_akhir = $total_pemasukan - $total_pengeluaran;

// Fetch financial ledger
$stmtKeu = $pdo->query("SELECT * FROM keuangan ORDER BY tanggal DESC");
$keuangan_list = $stmtKeu->fetchAll();
?>

<div class="flex justify-between items-center">
    <div>
        <h1 class="text-2xl font-bold text-on-surface">Buku Kas & Keuangan RT</h1>
        <p class="text-sm text-on-surface-variant">Laporan transparansi keuangan kas masuk dan kas keluar RT</p>
    </div>
    <button onclick="document.getElementById('modalTambahKas').classList.remove('hidden')" class="px-4 py-2 bg-primary text-white text-xs font-bold rounded-lg hover:bg-primary-container flex items-center gap-1">
        <span class="material-symbols-outlined text-sm">add_card</span> Catat Transaksi Baru
    </button>
</div>

<?php if ($message): ?>
<div class="p-3 bg-green-100 text-green-800 rounded-lg text-sm font-semibold">
    <?= htmlspecialchars($message) ?>
</div>
<?php endif; ?>

<!-- Financial Summary Cards -->
<div class="grid grid-cols-1 md:grid-cols-3 gap-4">
    <div class="bg-white p-5 rounded-xl border border-outline-variant shadow-sm">
        <span class="text-xs text-outline font-semibold block">Total Pemasukan Kas</span>
        <span class="text-2xl font-bold text-green-600">Rp <?= number_format($total_pemasukan, 0, ',', '.') ?></span>
    </div>
    <div class="bg-white p-5 rounded-xl border border-outline-variant shadow-sm">
        <span class="text-xs text-outline font-semibold block">Total Pengeluaran Kas</span>
        <span class="text-2xl font-bold text-red-600">Rp <?= number_format($total_pengeluaran, 0, ',', '.') ?></span>
    </div>
    <div class="bg-white p-5 rounded-xl border border-outline-variant shadow-sm">
        <span class="text-xs text-outline font-semibold block">Saldo Akhir RT Saat Ini</span>
        <span class="text-2xl font-bold text-primary">Rp <?= number_format($saldo_akhir, 0, ',', '.') ?></span>
    </div>
</div>

<!-- Ledger Table -->
<div class="bg-white rounded-xl border border-outline-variant shadow-sm overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-left text-sm">
            <thead class="bg-surface-container-low text-xs text-outline uppercase">
                <tr>
                    <th class="p-3">Tanggal</th>
                    <th class="p-3">Jenis</th>
                    <th class="p-3">Kategori</th>
                    <th class="p-3">Keterangan</th>
                    <th class="p-3 text-right">Jumlah (Rp)</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-outline-variant">
                <?php foreach ($keuangan_list as $k): ?>
                <tr>
                    <td class="p-3 text-xs"><?= date('d/m/Y H:i', strtotime($k['tanggal'])) ?></td>
                    <td class="p-3">
                        <span class="px-2 py-0.5 rounded text-[10px] font-bold <?= ($k['jenis_transaksi']==='Pemasukan')?'bg-green-100 text-green-800':'bg-red-100 text-red-800' ?>">
                            <?= htmlspecialchars($k['jenis_transaksi']) ?>
                        </span>
                    </td>
                    <td class="p-3 font-semibold text-xs"><?= htmlspecialchars($k['kategori']) ?></td>
                    <td class="p-3 text-xs text-outline"><?= htmlspecialchars($k['keterangan']) ?></td>
                    <td class="p-3 text-right font-bold <?= ($k['jenis_transaksi']==='Pemasukan')?'text-green-600':'text-red-600' ?>">
                        <?= ($k['jenis_transaksi']==='Pemasukan') ? '+' : '-' ?> Rp <?= number_format($k['jumlah'], 0, ',', '.') ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal Catat Transaksi -->
<div id="modalTambahKas" class="fixed inset-0 bg-black/50 z-50 hidden flex items-center justify-center p-4">
    <div class="bg-white rounded-xl max-w-md w-full p-6 space-y-4">
        <h3 class="font-bold text-lg text-on-surface">Form Catat Kas Keuangan</h3>
        <form action="admin_keuangan.php" method="POST" class="space-y-3">
            <div>
                <label class="text-xs font-semibold text-outline">Jenis Transaksi</label>
                <select name="jenis_transaksi" class="w-full h-10 border rounded-lg px-3 text-sm">
                    <option value="Pemasukan">Pemasukan (Kas Masuk)</option>
                    <option value="Pengeluaran">Pengeluaran (Kas Keluar)</option>
                </select>
            </div>
            <div>
                <label class="text-xs font-semibold text-outline">Kategori</label>
                <input type="text" name="kategori" class="w-full h-10 border rounded-lg px-3 text-sm" placeholder="Contoh: Iuran / Maintenance / Konsumsi" required />
            </div>
            <div>
                <label class="text-xs font-semibold text-outline">Jumlah Nominal (Rp)</label>
                <input type="number" name="jumlah" class="w-full h-10 border rounded-lg px-3 text-sm" required />
            </div>
            <div>
                <label class="text-xs font-semibold text-outline">Keterangan Tambahan</label>
                <textarea name="keterangan" rows="2" class="w-full border rounded-lg p-2 text-sm" required></textarea>
            </div>
            <div class="flex justify-end gap-2 pt-2">
                <button type="button" onclick="document.getElementById('modalTambahKas').classList.add('hidden')" class="px-4 py-2 border rounded-lg text-xs font-semibold">Batal</button>
                <button type="submit" class="px-4 py-2 bg-primary text-white rounded-lg text-xs font-semibold">Simpan Transaksi</button>
            </div>
        </form>
    </div>
</div>

<?php require_once 'includes/footer_admin.php'; ?>
