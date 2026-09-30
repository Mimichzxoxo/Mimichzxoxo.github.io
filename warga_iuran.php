<?php
// warga_iuran.php - Citizen dues payment and history
$active_tab = 'iuran';
require_once 'includes/header_warga.php';

$id_warga = $_SESSION['user_id'];
$id_keluarga = $_SESSION['id_keluarga'] ?? 1;
$message = null;

// Handle payment submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id_iuran'])) {
    $id_iuran = intval($_POST['id_iuran']);
    $jumlah_bayar = floatval($_POST['jumlah_bayar']);
    $metode = trim($_POST['metode_pembayaran'] ?? 'Transfer Bank');
    $tgl_sekarang = date('Y-m-d H:i:s');

    $stmtIns = $pdo->prepare("
        INSERT INTO pembayaran (id_iuran, id_warga, tanggal_bayar, jumlah_bayar, metode_pembayaran, status_verifikasi) 
        VALUES (:i, :w, :t, :j, :m, 'Menunggu Verifikasi')
    ");
    $stmtIns->execute([
        'i' => $id_iuran,
        'w' => $id_warga,
        't' => $tgl_sekarang,
        'j' => $jumlah_bayar,
        'm' => $metode
    ]);
    $message = "Pembayaran berhasil dikirim! Menunggu verifikasi dari Admin RT.";
}

// Fetch active iuran billing
$stmtIuran = $pdo->query("SELECT * FROM iuran ORDER BY id_iuran DESC");
$iuran_list = $stmtIuran->fetchAll();

// Fetch history payments for this citizen
$stmtPay = $pdo->prepare("
    SELECT p.*, i.nama_iuran 
    FROM pembayaran p 
    JOIN iuran i ON p.id_iuran = i.id_iuran 
    WHERE p.id_warga = :w 
    ORDER BY p.tanggal_bayar DESC
");
$stmtPay->execute(['w' => $id_warga]);
$pembayaran_list = $stmtPay->fetchAll();
?>

<div class="flex flex-col gap-1 my-2">
    <h2 class="text-xl font-bold text-on-surface">Iuran & Kas RT</h2>
    <p class="text-sm text-on-surface-variant">Bayar dan pantau riwayat keikutsertaan iuran bulanan</p>
</div>

<?php if ($message): ?>
<div class="p-3 bg-green-100 text-green-800 rounded-lg text-sm font-semibold flex items-center gap-2">
    <span class="material-symbols-outlined">check_circle</span>
    <span><?= htmlspecialchars($message) ?></span>
</div>
<?php endif; ?>

<!-- Tagihan Iuran Aktif -->
<div class="flex flex-col gap-3 my-2">
    <h3 class="font-bold text-md text-on-surface">Tagihan Iuran Tersedia</h3>
    <?php foreach ($iuran_list as $i): ?>
    <div class="bg-white border border-outline-variant rounded-xl p-4 flex justify-between items-center shadow-sm">
        <div>
            <h4 class="font-bold text-on-surface"><?= htmlspecialchars($i['nama_iuran']) ?></h4>
            <p class="text-xs text-on-surface-variant">Periode: <?= htmlspecialchars($i['periode']) ?></p>
            <p class="text-sm font-bold text-primary mt-1">Rp <?= number_format($i['nominal'], 0, ',', '.') ?></p>
        </div>
        <button onclick="openModal(<?= $i['id_iuran'] ?>, '<?= htmlspecialchars($i['nama_iuran']) ?>', <?= $i['nominal'] ?>)" class="px-3 py-2 bg-primary text-white text-xs font-bold rounded-lg hover:bg-primary-container">
            Bayar Sekarang
        </button>
    </div>
    <?php endforeach; ?>
</div>

<!-- Riwayat Pembayaran -->
<div class="flex flex-col gap-3 my-4">
    <h3 class="font-bold text-md text-on-surface">Riwayat Pembayaran Saya</h3>
    <?php if (empty($pembayaran_list)): ?>
        <div class="p-6 text-center text-outline bg-white rounded-xl border border-outline-variant text-sm">
            Belum ada riwayat pembayaran.
        </div>
    <?php else: ?>
        <div class="flex flex-col gap-2">
            <?php foreach ($pembayaran_list as $p): ?>
            <div class="bg-white border border-outline-variant rounded-xl p-3 flex justify-between items-center text-xs">
                <div>
                    <span class="font-bold text-on-surface block text-sm"><?= htmlspecialchars($p['nama_iuran']) ?></span>
                    <span class="text-outline"><?= date('d M Y H:i', strtotime($p['tanggal_bayar'])) ?> • <?= htmlspecialchars($p['metode_pembayaran']) ?></span>
                </div>
                <div class="text-right">
                    <span class="font-bold text-sm block">Rp <?= number_format($p['jumlah_bayar'], 0, ',', '.') ?></span>
                    <?php if ($p['status_verifikasi'] === 'Disetujui'): ?>
                        <span class="px-2 py-0.5 bg-green-100 text-green-700 rounded font-semibold text-[10px]">Disetujui</span>
                    <?php elseif ($p['status_verifikasi'] === 'Ditolak'): ?>
                        <span class="px-2 py-0.5 bg-red-100 text-red-700 rounded font-semibold text-[10px]">Ditolak</span>
                    <?php else: ?>
                        <span class="px-2 py-0.5 bg-yellow-100 text-yellow-800 rounded font-semibold text-[10px]">Pending</span>
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<!-- Payment Modal -->
<div id="paymentModal" class="fixed inset-0 bg-black/50 z-50 hidden flex items-center justify-center p-4">
    <div class="bg-white rounded-xl max-w-sm w-full p-5 space-y-4">
        <h3 class="font-bold text-lg text-on-surface" id="modalTitle">Konfirmasi Pembayaran</h3>
        <form action="warga_iuran.php" method="POST" class="space-y-3">
            <input type="hidden" name="id_iuran" id="modalIdIuran">
            <div>
                <label class="text-xs text-outline font-semibold">Nominal Pembayaran (Rp)</label>
                <input type="number" name="jumlah_bayar" id="modalNominal" class="w-full h-10 border rounded-lg px-3 text-sm" required>
            </div>
            <div>
                <label class="text-xs text-outline font-semibold">Metode Pembayaran</label>
                <select name="metode_pembayaran" class="w-full h-10 border rounded-lg px-3 text-sm">
                    <option value="Transfer Bank BCA">Transfer Bank BCA</option>
                    <option value="QRIS / E-Wallet">QRIS / E-Wallet</option>
                    <option value="Tunai Ke Bendahara">Tunai Ke Bendahara</option>
                </select>
            </div>
            <div class="flex justify-end gap-2 pt-2">
                <button type="button" onclick="closeModal()" class="px-4 py-2 border rounded-lg text-xs font-semibold">Batal</button>
                <button type="submit" class="px-4 py-2 bg-primary text-white rounded-lg text-xs font-semibold">Kirim Pembayaran</button>
            </div>
        </form>
    </div>
</div>

<script>
function openModal(id, title, nominal) {
    document.getElementById('modalIdIuran').value = id;
    document.getElementById('modalTitle').innerText = 'Bayar: ' + title;
    document.getElementById('modalNominal').value = nominal;
    document.getElementById('paymentModal').classList.remove('hidden');
}
function closeModal() {
    document.getElementById('paymentModal').classList.add('hidden');
}
</script>

<?php require_once 'includes/footer_warga.php'; ?>
