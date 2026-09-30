<?php
// admin_dashboard.php - Admin RT Main Dashboard
$active_tab = 'dashboard';
require_once 'includes/header_admin.php';

$id_rt = $_SESSION['id_rt'] ?? 1;

// Fetch metrics
$total_warga = $pdo->query("SELECT COUNT(*) FROM warga")->fetchColumn();
$total_keluarga = $pdo->query("SELECT COUNT(*) FROM keluarga")->fetchColumn();
$surat_pending = $pdo->query("SELECT COUNT(*) FROM surat WHERE status_surat = 'Diproses'")->fetchColumn();
$iuran_pending = $pdo->query("SELECT COUNT(*) FROM pembayaran WHERE status_verifikasi = 'Menunggu Verifikasi'")->fetchColumn();

// Fetch RT info
$stmtRt = $pdo->prepare("SELECT * FROM rt WHERE id_rt = :id");
$stmtRt->execute(['id' => $id_rt]);
$rt = $stmtRt->fetch();

// Fetch latest requests
$stmtSurat = $pdo->query("SELECT s.*, w.nama FROM surat s JOIN warga w ON s.id_warga = w.id_warga WHERE s.status_surat = 'Diproses' LIMIT 5");
$latest_surat = $stmtSurat->fetchAll();
?>

<!-- Header title & info -->
<div class="flex justify-between items-center bg-white p-6 rounded-xl border border-outline-variant shadow-sm">
    <div>
        <h1 class="text-2xl font-bold text-on-surface">Dashboard Pengurus RT</h1>
        <p class="text-sm text-on-surface-variant">Wilayah RT <?= htmlspecialchars($rt['nomor_rt'] ?? '001') ?> / RW <?= htmlspecialchars($rt['rw'] ?? '005') ?> - Kel. <?= htmlspecialchars($rt['kelurahan'] ?? 'Mekar') ?></p>
    </div>
    <span class="px-3 py-1 bg-green-100 text-green-800 font-bold rounded-lg text-xs">Sistem Aktif</span>
</div>

<!-- Stats Grid -->
<div class="grid grid-cols-1 md:grid-cols-4 gap-4">
    <div class="bg-white p-5 rounded-xl border border-outline-variant shadow-sm flex items-center gap-4">
        <div class="w-12 h-12 bg-blue-100 text-blue-800 rounded-xl flex items-center justify-center">
            <span class="material-symbols-outlined text-2xl">group</span>
        </div>
        <div>
            <span class="text-xs text-outline font-semibold block">Total Warga</span>
            <span class="text-2xl font-bold text-on-surface"><?= number_format($total_warga) ?></span>
        </div>
    </div>
    
    <div class="bg-white p-5 rounded-xl border border-outline-variant shadow-sm flex items-center gap-4">
        <div class="w-12 h-12 bg-indigo-100 text-indigo-800 rounded-xl flex items-center justify-center">
            <span class="material-symbols-outlined text-2xl">home</span>
        </div>
        <div>
            <span class="text-xs text-outline font-semibold block">Total Kepala Keluarga</span>
            <span class="text-2xl font-bold text-on-surface"><?= number_format($total_keluarga) ?></span>
        </div>
    </div>

    <div class="bg-white p-5 rounded-xl border border-outline-variant shadow-sm flex items-center gap-4">
        <div class="w-12 h-12 bg-yellow-100 text-yellow-800 rounded-xl flex items-center justify-center">
            <span class="material-symbols-outlined text-2xl">mark_email_unread</span>
        </div>
        <div>
            <span class="text-xs text-outline font-semibold block">Surat Menunggu</span>
            <span class="text-2xl font-bold text-on-surface"><?= number_format($surat_pending) ?></span>
        </div>
    </div>

    <div class="bg-white p-5 rounded-xl border border-outline-variant shadow-sm flex items-center gap-4">
        <div class="w-12 h-12 bg-emerald-100 text-emerald-800 rounded-xl flex items-center justify-center">
            <span class="material-symbols-outlined text-2xl">payments</span>
        </div>
        <div>
            <span class="text-xs text-outline font-semibold block">Verifikasi Iuran</span>
            <span class="text-2xl font-bold text-on-surface"><?= number_format($iuran_pending) ?></span>
        </div>
    </div>
</div>

<!-- Pending Letters Table -->
<div class="bg-white rounded-xl border border-outline-variant shadow-sm overflow-hidden p-6 space-y-4">
    <div class="flex justify-between items-center">
        <h3 class="font-bold text-lg text-on-surface">Pengajuan Surat Terbaru (Perlu Verifikasi)</h3>
        <a href="admin_surat.php" class="text-xs text-primary font-bold hover:underline">Kelola Semua Surat &rarr;</a>
    </div>

    <div class="overflow-x-auto">
        <table class="w-full text-left text-sm">
            <thead class="bg-surface-container-low text-xs text-outline uppercase">
                <tr>
                    <th class="p-3">Nama Pemohon</th>
                    <th class="p-3">Jenis Surat</th>
                    <th class="p-3">Keperluan</th>
                    <th class="p-3">Tanggal</th>
                    <th class="p-3 text-right">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-outline-variant">
                <?php if (empty($latest_surat)): ?>
                    <tr>
                        <td colspan="5" class="p-4 text-center text-outline">Tidak ada pengajuan surat yang pending.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($latest_surat as $ls): ?>
                    <tr>
                        <td class="p-3 font-semibold text-on-surface"><?= htmlspecialchars($ls['nama']) ?></td>
                        <td class="p-3"><?= htmlspecialchars($ls['jenis_surat']) ?></td>
                        <td class="p-3 text-xs text-outline"><?= htmlspecialchars($ls['keperluan']) ?></td>
                        <td class="p-3 text-xs"><?= date('d/m/Y H:i', strtotime($ls['tanggal_pengajuan'])) ?></td>
                        <td class="p-3 text-right">
                            <a href="admin_surat.php" class="px-3 py-1 bg-primary text-white text-xs font-bold rounded hover:bg-primary-container">Verifikasi</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once 'includes/footer_admin.php'; ?>
