<?php
// admin_iuran.php - Verify citizen dues payments
$active_tab = 'iuran';
require_once 'includes/header_admin.php';

$message = null;

// Handle Verification
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id_pembayaran'])) {
    $id_pembayaran = intval($_POST['id_pembayaran']);
    $status = trim($_POST['status_verifikasi']);

    $stmtUpd = $pdo->prepare("UPDATE pembayaran SET status_verifikasi = :s WHERE id_pembayaran = :id");
    $stmtUpd->execute(['s' => $status, 'id' => $id_pembayaran]);

    // If approved, insert into financial record (keuangan)
    if ($status === 'Disetujui') {
        $stmtPay = $pdo->prepare("SELECT p.*, i.nama_iuran FROM pembayaran p JOIN iuran i ON p.id_iuran = i.id_iuran WHERE p.id_pembayaran = :id");
        $stmtPay->execute(['id' => $id_pembayaran]);
        $p = $stmtPay->fetch();

        if ($p) {
            $stmtKeu = $pdo->prepare("
                INSERT INTO keuangan (id_rt, jenis_transaksi, kategori, jumlah, keterangan, tanggal) 
                VALUES (:rt, 'Pemasukan', 'Iuran Warga', :jml, :ket, :tgl)
            ");
            $stmtKeu->execute([
                'rt' => $_SESSION['id_rt'] ?? 1,
                'jml' => $p['jumlah_bayar'],
                'ket' => 'Pembayaran ' . $p['nama_iuran'] . ' - ID Warga #' . $p['id_warga'],
                'tgl' => date('Y-m-d H:i:s')
            ]);
        }
    }

    $message = "Pembayaran iuran berhasil diverifikasi: " . $status;
}

// Fetch all payment submissions
$stmtPay = $pdo->query("
    SELECT p.*, w.nama, i.nama_iuran 
    FROM pembayaran p 
    JOIN warga w ON p.id_warga = w.id_warga 
    JOIN iuran i ON p.id_iuran = i.id_iuran 
    ORDER BY p.tanggal_bayar DESC
");
$pembayaran_list = $stmtPay->fetchAll();
?>

<div>
    <h1 class="text-2xl font-bold text-on-surface">Verifikasi Pembayaran Iuran Warga</h1>
    <p class="text-sm text-on-surface-variant">Konfirmasi bukti transfer / pembayaran iuran bulanan dari warga</p>
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
                    <th class="p-3">Warga Pembayar</th>
                    <th class="p-3">Nama Iuran</th>
                    <th class="p-3">Jumlah Bayar</th>
                    <th class="p-3">Metode</th>
                    <th class="p-3">Tgl Bayar</th>
                    <th class="p-3">Status Verifikasi</th>
                    <th class="p-3 text-right">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-outline-variant">
                <?php foreach ($pembayaran_list as $p): ?>
                <tr>
                    <td class="p-3 font-semibold text-on-surface"><?= htmlspecialchars($p['nama']) ?></td>
                    <td class="p-3"><?= htmlspecialchars($p['nama_iuran']) ?></td>
                    <td class="p-3 font-bold text-primary">Rp <?= number_format($p['jumlah_bayar'], 0, ',', '.') ?></td>
                    <td class="p-3 text-xs text-outline"><?= htmlspecialchars($p['metode_pembayaran']) ?></td>
                    <td class="p-3 text-xs"><?= date('d/m/Y H:i', strtotime($p['tanggal_bayar'])) ?></td>
                    <td class="p-3">
                        <?php if ($p['status_verifikasi'] === 'Disetujui'): ?>
                            <span class="px-2 py-0.5 bg-green-100 text-green-800 rounded text-[10px] font-bold">Disetujui</span>
                        <?php elseif ($p['status_verifikasi'] === 'Ditolak'): ?>
                            <span class="px-2 py-0.5 bg-red-100 text-red-800 rounded text-[10px] font-bold">Ditolak</span>
                        <?php else: ?>
                            <span class="px-2 py-0.5 bg-yellow-100 text-yellow-800 rounded text-[10px] font-bold">Menunggu</span>
                        <?php endif; ?>
                    </td>
                    <td class="p-3 text-right">
                        <?php if ($p['status_verifikasi'] === 'Menunggu Verifikasi'): ?>
                        <div class="flex justify-end gap-1">
                            <form action="admin_iuran.php" method="POST" class="inline">
                                <input type="hidden" name="id_pembayaran" value="<?= $p['id_pembayaran'] ?>">
                                <input type="hidden" name="status_verifikasi" value="Disetujui">
                                <button type="submit" class="px-2 py-1 bg-green-600 text-white rounded text-xs font-semibold hover:bg-green-700">Setujui</button>
                            </form>
                            <form action="admin_iuran.php" method="POST" class="inline">
                                <input type="hidden" name="id_pembayaran" value="<?= $p['id_pembayaran'] ?>">
                                <input type="hidden" name="status_verifikasi" value="Ditolak">
                                <button type="submit" class="px-2 py-1 bg-red-600 text-white rounded text-xs font-semibold hover:bg-red-700">Tolak</button>
                            </form>
                        </div>
                        <?php else: ?>
                            <span class="text-xs text-outline">Selesai</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once 'includes/footer_admin.php'; ?>
