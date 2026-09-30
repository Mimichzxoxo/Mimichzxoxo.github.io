<?php
// admin_surat.php - Verify & approve cover letters
$active_tab = 'surat';
require_once 'includes/header_admin.php';

$message = null;

// Handle Verification (Approve / Reject)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id_surat'])) {
    $id_surat = intval($_POST['id_surat']);
    $status = trim($_POST['status_surat']);

    $stmtUpd = $pdo->prepare("UPDATE surat SET status_surat = :s WHERE id_surat = :id");
    $stmtUpd->execute(['s' => $status, 'id' => $id_surat]);
    $message = "Status surat berhasil diperbarui menjadi: " . $status;
}

// Fetch all letters
$stmtSurat = $pdo->query("
    SELECT s.*, w.nama, w.no_telepon 
    FROM surat s 
    JOIN warga w ON s.id_warga = w.id_warga 
    ORDER BY s.tanggal_pengajuan DESC
");
$surat_list = $stmtSurat->fetchAll();
?>

<div>
    <h1 class="text-2xl font-bold text-on-surface">Layanan Verifikasi Surat Pengantar</h1>
    <p class="text-sm text-on-surface-variant">Tinjau, setujui, dan cetak surat pengantar RT untuk warga</p>
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
                    <th class="p-3">No. Surat</th>
                    <th class="p-3">Nama Pemohon</th>
                    <th class="p-3">Jenis Surat</th>
                    <th class="p-3">Keperluan</th>
                    <th class="p-3">Tgl Pengajuan</th>
                    <th class="p-3">Status</th>
                    <th class="p-3 text-right">Aksi Verifikasi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-outline-variant">
                <?php foreach ($surat_list as $s): ?>
                <tr>
                    <td class="p-3 font-mono text-xs"><?= htmlspecialchars($s['nomor_surat']) ?></td>
                    <td class="p-3 font-semibold text-on-surface"><?= htmlspecialchars($s['nama']) ?></td>
                    <td class="p-3"><?= htmlspecialchars($s['jenis_surat']) ?></td>
                    <td class="p-3 text-xs text-outline max-w-[200px] font-sans"><?= htmlspecialchars($s['keperluan']) ?></td>
                    <td class="p-3 text-xs"><?= date('d/m/Y H:i', strtotime($s['tanggal_pengajuan'])) ?></td>
                    <td class="p-3">
                        <?php if ($s['status_surat'] === 'Disetujui'): ?>
                            <span class="px-2 py-0.5 bg-green-100 text-green-800 rounded text-[10px] font-bold">Disetujui</span>
                        <?php elseif ($s['status_surat'] === 'Ditolak'): ?>
                            <span class="px-2 py-0.5 bg-red-100 text-red-800 rounded text-[10px] font-bold">Ditolak</span>
                        <?php else: ?>
                            <span class="px-2 py-0.5 bg-yellow-100 text-yellow-800 rounded text-[10px] font-bold">Diproses</span>
                        <?php endif; ?>
                    </td>
                    <td class="p-3 text-right">
                        <?php if ($s['status_surat'] === 'Diproses'): ?>
                        <div class="flex justify-end gap-1">
                            <form action="admin_surat.php" method="POST" class="inline">
                                <input type="hidden" name="id_surat" value="<?= $s['id_surat'] ?>">
                                <input type="hidden" name="status_surat" value="Disetujui">
                                <button type="submit" class="px-2 py-1 bg-green-600 text-white rounded text-xs font-semibold hover:bg-green-700">Setujui</button>
                            </form>
                            <form action="admin_surat.php" method="POST" class="inline">
                                <input type="hidden" name="id_surat" value="<?= $s['id_surat'] ?>">
                                <input type="hidden" name="status_surat" value="Ditolak">
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
