<?php
// warga_aspirasi.php - Complaints and feedback portal for citizens
$active_tab = 'aspirasi';
require_once 'includes/header_warga.php';

$id_warga = $_SESSION['user_id'];
$message = null;

// Handle submission of new aspirasi
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['topik'])) {
    $topik = trim($_POST['topik']);
    $pesan = trim($_POST['pesan']);
    $tgl = date('Y-m-d H:i:s');

    $stmtIns = $pdo->prepare("
        INSERT INTO aspirasi (id_warga, topik, pesan, tanggal_kirim, status_tanggapan) 
        VALUES (:w, :t, :p, :tg, 'Belum Ditanggapi')
    ");
    $stmtIns->execute([
        'w' => $id_warga,
        't' => $topik,
        'p' => $pesan,
        'tg' => $tgl
    ]);
    $message = "Aspirasi / pengaduan Anda berhasil terkirim!";
}

// Fetch user's aspirasi list
$stmt = $pdo->prepare("SELECT * FROM aspirasi WHERE id_warga = :w ORDER BY tanggal_kirim DESC");
$stmt->execute(['w' => $id_warga]);
$aspirasi_list = $stmt->fetchAll();
?>

<div class="flex flex-col gap-1 my-2">
    <h2 class="text-xl font-bold text-on-surface">Aspirasi & Pengaduan Warga</h2>
    <p class="text-sm text-on-surface-variant">Sampaikan saran, kritik, atau laporan lingkungan kepada pengurus RT</p>
</div>

<?php if ($message): ?>
<div class="p-3 bg-green-100 text-green-800 rounded-lg text-sm font-semibold flex items-center gap-2">
    <span class="material-symbols-outlined">check_circle</span>
    <span><?= htmlspecialchars($message) ?></span>
</div>
<?php endif; ?>

<!-- Form Kirim Aspirasi -->
<div class="bg-white border border-outline-variant rounded-xl p-4 shadow-sm my-2 space-y-3">
    <h3 class="font-bold text-md text-on-surface flex items-center gap-2">
        <span class="material-symbols-outlined text-primary">chat</span> Sampaikan Aspirasi Baru
    </h3>
    <form action="warga_aspirasi.php" method="POST" class="space-y-3">
        <div>
            <label class="text-xs font-semibold text-outline">Topik / Subjek</label>
            <input type="text" name="topik" class="w-full h-10 border rounded-lg px-3 text-sm" placeholder="Contoh: Lampu Jalan Padam / Kebersihan Selokan" required />
        </div>
        <div>
            <label class="text-xs font-semibold text-outline">Pesan Lengkap</label>
            <textarea name="pesan" rows="3" class="w-full border rounded-lg p-2 text-sm" placeholder="Tuliskan keluhan atau masukan Anda secara detail..." required></textarea>
        </div>
        <button type="submit" class="w-full h-10 bg-primary text-white text-xs font-bold rounded-lg hover:bg-primary-container transition">
            Kirim Aspirasi
        </button>
    </form>
</div>

<!-- Riwayat Aspirasi -->
<div class="flex flex-col gap-3 my-4">
    <h3 class="font-bold text-md text-on-surface">Riwayat Aspirasi Saya</h3>
    <?php if (empty($aspirasi_list)): ?>
        <div class="p-6 text-center text-outline bg-white rounded-xl border border-outline-variant text-sm">
            Belum ada aspirasi terkirim.
        </div>
    <?php else: ?>
        <div class="flex flex-col gap-3">
            <?php foreach ($aspirasi_list as $a): ?>
            <div class="bg-white border border-outline-variant rounded-xl p-4 space-y-2 shadow-sm">
                <div class="flex justify-between items-start">
                    <h4 class="font-bold text-on-surface text-sm"><?= htmlspecialchars($a['topik']) ?></h4>
                    <span class="text-xs text-outline"><?= date('d M Y H:i', strtotime($a['tanggal_kirim'])) ?></span>
                </div>
                <p class="text-xs text-on-surface-variant leading-relaxed"><?= nl2br(htmlspecialchars($a['pesan'])) ?></p>
                <div class="pt-2 border-t border-outline-variant text-xs">
                    <?php if (!empty($a['tanggapan'])): ?>
                        <div class="bg-surface-container-low p-2 rounded text-primary">
                            <span class="font-bold block">Tanggapan RT:</span>
                            <span><?= htmlspecialchars($a['tanggapan']) ?></span>
                        </div>
                    <?php else: ?>
                        <span class="text-yellow-700 bg-yellow-100 px-2 py-0.5 rounded font-semibold text-[10px]">Menunggu Tanggapan</span>
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<?php require_once 'includes/footer_warga.php'; ?>
