<?php
// warga_surat.php - Request cover letters (surat pengantar)
$active_tab = 'surat';
require_once 'includes/header_warga.php';

$id_warga = $_SESSION['user_id'];
$message = null;

// Handle submission for new cover letter
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['jenis_surat'])) {
    $jenis_surat = trim($_POST['jenis_surat']);
    $keperluan = trim($_POST['keperluan']);
    $tgl = date('Y-m-d H:i:s');
    
    // Automatic letter number generation (Simulating trigger/format)
    $no_surat = 'SRT/' . date('Ym') . '/' . rand(1000, 9999);

    $stmtIns = $pdo->prepare("
        INSERT INTO surat (id_warga, nomor_surat, jenis_surat, tanggal_pengajuan, keperluan, status_surat) 
        VALUES (:w, :n, :j, :t, :k, 'Diproses')
    ");
    $stmtIns->execute([
        'w' => $id_warga,
        'n' => $no_surat,
        'j' => $jenis_surat,
        't' => $tgl,
        'k' => $keperluan
    ]);
    $message = "Pengajuan surat pengantar berhasil dikirim!";
}

// Fetch letters requested by current citizen
$stmt = $pdo->prepare("SELECT * FROM surat WHERE id_warga = :w ORDER BY tanggal_pengajuan DESC");
$stmt->execute(['w' => $id_warga]);
$surat_list = $stmt->fetchAll();
?>

<div class="flex flex-col gap-1 my-2">
    <h2 class="text-xl font-bold text-on-surface">Surat Pengantar RT</h2>
    <p class="text-sm text-on-surface-variant">Ajukan surat pengantar resmi RT/RW secara online</p>
</div>

<?php if ($message): ?>
<div class="p-3 bg-green-100 text-green-800 rounded-lg text-sm font-semibold flex items-center gap-2">
    <span class="material-symbols-outlined">check_circle</span>
    <span><?= htmlspecialchars($message) ?></span>
</div>
<?php endif; ?>

<!-- Form Ajukan Surat -->
<div class="bg-white border border-outline-variant rounded-xl p-4 shadow-sm my-2 space-y-3">
    <h3 class="font-bold text-md text-on-surface flex items-center gap-2">
        <span class="material-symbols-outlined text-primary">add_notes</span> Buat Pengajuan Baru
    </h3>
    <form action="warga_surat.php" method="POST" class="space-y-3">
        <div>
            <label class="text-xs font-semibold text-outline">Jenis Surat Pengantar</label>
            <select name="jenis_surat" class="w-full h-10 border rounded-lg px-3 text-sm" required>
                <option value="Surat Pengantar KTP / KK">Surat Pengantar KTP / KK</option>
                <option value="Surat Keterangan Dominasi / Tempat Tinggal">Surat Keterangan Domisili</option>
                <option value="Surat Keterangan Usaha (SKU)">Surat Keterangan Usaha (SKU)</option>
                <option value="Surat Keterangan Tidak Mampu (SKTM)">Surat Keterangan Tidak Mampu (SKTM)</option>
                <option value="Surat Pengantar Nikah">Surat Pengantar Nikah</option>
            </select>
        </div>
        <div>
            <label class="text-xs font-semibold text-outline">Keperluan / Alasan</label>
            <textarea name="keperluan" rows="2" class="w-full border rounded-lg p-2 text-sm" placeholder="Jelaskan keperluan pembuatan surat pengantar ini..." required></textarea>
        </div>
        <button type="submit" class="w-full h-10 bg-primary text-white text-xs font-bold rounded-lg hover:bg-primary-container transition">
            Kirim Pengajuan Surat
        </button>
    </form>
</div>

<!-- Riwayat Surat -->
<div class="flex flex-col gap-3 my-4">
    <h3 class="font-bold text-md text-on-surface">Riwayat Pengajuan Surat Saya</h3>
    <?php if (empty($surat_list)): ?>
        <div class="p-6 text-center text-outline bg-white rounded-xl border border-outline-variant text-sm">
            Belum ada pengajuan surat.
        </div>
    <?php else: ?>
        <div class="flex flex-col gap-2">
            <?php foreach ($surat_list as $s): ?>
            <div class="bg-white border border-outline-variant rounded-xl p-3 flex justify-between items-center text-xs">
                <div>
                    <span class="font-bold text-on-surface block text-sm"><?= htmlspecialchars($s['jenis_surat']) ?></span>
                    <span class="text-outline">No: <?= htmlspecialchars($s['nomor_surat'] ?? '-') ?> • <?= date('d M Y H:i', strtotime($s['tanggal_pengajuan'])) ?></span>
                    <p class="text-on-surface-variant text-xs mt-1">Keperluan: <?= htmlspecialchars($s['keperluan']) ?></p>
                </div>
                <div>
                    <?php if ($s['status_surat'] === 'Disetujui'): ?>
                        <span class="px-2 py-1 bg-green-100 text-green-700 rounded font-semibold text-[10px]">Disetujui</span>
                    <?php elseif ($s['status_surat'] === 'Ditolak'): ?>
                        <span class="px-2 py-1 bg-red-100 text-red-700 rounded font-semibold text-[10px]">Ditolak</span>
                    <?php else: ?>
                        <span class="px-2 py-1 bg-yellow-100 text-yellow-800 rounded font-semibold text-[10px]">Diproses</span>
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<?php require_once 'includes/footer_warga.php'; ?>
