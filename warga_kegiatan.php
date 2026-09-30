<?php
// warga_kegiatan.php - RT activities list & registration
$active_tab = 'kegiatan';
require_once 'includes/header_warga.php';

$id_rt = $_SESSION['id_rt'] ?? 1;
$id_warga = $_SESSION['user_id'];
$message = null;

// Handle registration to activity
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id_kegiatan'])) {
    $id_kegiatan = intval($_POST['id_kegiatan']);
    
    // Check if already registered
    $stmtCheck = $pdo->prepare("SELECT * FROM peserta_kegiatan WHERE id_kegiatan = :k AND id_warga = :w");
    $stmtCheck->execute(['k' => $id_kegiatan, 'w' => $id_warga]);
    if ($stmtCheck->rowCount() == 0) {
        $stmtIns = $pdo->prepare("INSERT INTO peserta_kegiatan (id_kegiatan, id_warga, status_kehadiran) VALUES (:k, :w, 'Hadir')");
        $stmtIns->execute(['k' => $id_kegiatan, 'w' => $id_warga]);
        $message = "Berhasil mendaftar ke kegiatan!";
    } else {
        $message = "Anda sudah terdaftar dalam kegiatan ini.";
    }
}

// Fetch activities
$stmt = $pdo->prepare("SELECT * FROM kegiatan WHERE id_rt = :id_rt ORDER BY tanggal_kegiatan ASC");
$stmt->execute(['id_rt' => $id_rt]);
$kegiatan_list = $stmt->fetchAll();

// Get registered IDs for current warga
$stmtReg = $pdo->prepare("SELECT id_kegiatan FROM peserta_kegiatan WHERE id_warga = :w");
$stmtReg->execute(['w' => $id_warga]);
$registered_ids = $stmtReg->fetchAll(PDO::FETCH_COLUMN);
?>

<div class="flex flex-col gap-1 my-2">
    <h2 class="text-xl font-bold text-on-surface">Agenda Kegiatan RT</h2>
    <p class="text-sm text-on-surface-variant">Ikuti kegiatan dan kerja bakti dilingkungan RT Anda</p>
</div>

<?php if ($message): ?>
<div class="p-3 bg-primary-container/20 text-primary-container rounded-lg text-sm font-semibold flex items-center gap-2">
    <span class="material-symbols-outlined">info</span>
    <span><?= htmlspecialchars($message) ?></span>
</div>
<?php endif; ?>

<div class="flex flex-col gap-4 my-2">
    <?php if (empty($kegiatan_list)): ?>
        <div class="p-8 text-center text-outline bg-white rounded-xl border border-outline-variant">
            Belum ada agenda kegiatan.
        </div>
    <?php else: ?>
        <?php foreach ($kegiatan_list as $k): ?>
        <?php $is_registered = in_array($k['id_kegiatan'], $registered_ids); ?>
        <div class="bg-white border border-outline-variant rounded-xl p-4 flex flex-col gap-3 shadow-sm">
            <div class="flex justify-between items-start">
                <span class="px-2 py-1 bg-surface-container-low text-on-surface-variant rounded text-xs font-semibold flex items-center gap-1">
                    <span class="material-symbols-outlined text-[14px]">event</span> <?= date('d M Y • H:i', strtotime($k['tanggal_kegiatan'])) ?> WIB
                </span>
                <span class="px-2 py-1 bg-surface-container-low text-on-surface-variant rounded text-xs">📍 <?= htmlspecialchars($k['lokasi']) ?></span>
            </div>
            <h3 class="font-bold text-lg text-on-surface"><?= htmlspecialchars($k['nama_kegiatan']) ?></h3>
            <p class="text-sm text-on-surface-variant"><?= nl2br(htmlspecialchars($k['deskripsi'])) ?></p>
            <div class="flex justify-between items-center pt-2 border-t border-outline-variant">
                <form action="warga_kegiatan.php" method="POST">
                    <input type="hidden" name="id_kegiatan" value="<?= $k['id_kegiatan'] ?>">
                    <?php if ($is_registered): ?>
                        <button type="button" disabled class="px-4 py-2 bg-green-100 text-green-800 rounded-lg text-xs font-bold flex items-center gap-1 cursor-default">
                            <span class="material-symbols-outlined text-sm">check_circle</span> Terdaftar
                        </button>
                    <?php else: ?>
                        <button type="submit" class="px-4 py-2 bg-primary text-white rounded-lg text-xs font-bold hover:bg-primary-container transition">
                            Ikuti Kegiatan
                        </button>
                    <?php endif; ?>
                </form>
            </div>
        </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<?php require_once 'includes/footer_warga.php'; ?>
