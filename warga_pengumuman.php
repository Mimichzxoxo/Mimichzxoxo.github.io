<?php
// warga_pengumuman.php - Display list of RT announcements
$active_tab = 'pengumuman';
require_once 'includes/header_warga.php';

$id_rt = $_SESSION['id_rt'] ?? 1;

// Fetch announcements
$stmt = $pdo->prepare("
    SELECT p.*, r.nomor_rt, r.rw 
    FROM pengumuman p 
    JOIN rt r ON p.id_rt = r.id_rt 
    WHERE p.id_rt = :id_rt 
    ORDER BY p.tanggal_publish DESC
");
$stmt->execute(['id_rt' => $id_rt]);
$pengumuman_list = $stmt->fetchAll();
?>

<div class="flex flex-col gap-1 my-2">
    <h2 class="text-xl font-bold text-on-surface">Pengumuman RT/RW</h2>
    <p class="text-sm text-on-surface-variant">Informasi terbaru dari pengurus RT/RW Anda</p>
</div>

<div class="flex flex-col gap-4 my-2">
    <?php if (empty($pengumuman_list)): ?>
        <div class="p-8 text-center text-outline bg-surface rounded-xl border border-outline-variant">
            Belum ada pengumuman terbaru.
        </div>
    <?php else: ?>
        <?php foreach ($pengumuman_list as $p): ?>
        <div class="bg-white border border-outline-variant rounded-xl overflow-hidden shadow-sm p-4 flex flex-col gap-2 transition-transform hover:-translate-y-1">
            <div class="flex justify-between items-start">
                <span class="px-2 py-1 rounded bg-primary-container/15 text-primary-container text-xs font-semibold inline-flex items-center gap-1">
                    <span class="material-symbols-outlined text-[14px]">campaign</span> RT <?= htmlspecialchars($p['nomor_rt']) ?> / RW <?= htmlspecialchars($p['rw']) ?>
                </span>
                <span class="text-xs text-outline"><?= date('d M Y H:i', strtotime($p['tanggal_publish'])) ?></span>
            </div>
            <h3 class="font-bold text-on-surface text-lg"><?= htmlspecialchars($p['judul']) ?></h3>
            <p class="text-on-surface-variant text-sm leading-relaxed"><?= nl2br(htmlspecialchars($p['konten'])) ?></p>
        </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<?php require_once 'includes/footer_warga.php'; ?>
