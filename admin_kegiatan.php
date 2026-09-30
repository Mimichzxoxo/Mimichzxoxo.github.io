<?php
// admin_kegiatan.php - Manage agenda & activity participants
$active_tab = 'kegiatan';
require_once 'includes/header_admin.php';

$id_rt = $_SESSION['id_rt'] ?? 1;
$message = null;

// Handle Add Activity
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['nama_kegiatan'])) {
    $nama = trim($_POST['nama_kegiatan']);
    $tgl = trim($_POST['tanggal_kegiatan']);
    $lokasi = trim($_POST['lokasi']);
    $deskripsi = trim($_POST['deskripsi']);

    $stmtIns = $pdo->prepare("
        INSERT INTO kegiatan (id_rt, nama_kegiatan, tanggal_kegiatan, lokasi, deskripsi) 
        VALUES (:rt, :n, :t, :l, :d)
    ");
    $stmtIns->execute(['rt' => $id_rt, 'n' => $nama, 't' => $tgl, 'l' => $lokasi, 'd' => $deskripsi]);
    $message = "Agenda kegiatan baru berhasil diterbitkan!";
}

// Fetch Activities with participant count
$stmtKeg = $pdo->query("
    SELECT k.*, COUNT(p.id_peserta) as total_peserta 
    FROM kegiatan k 
    LEFT JOIN peserta_kegiatan p ON k.id_kegiatan = p.id_kegiatan 
    GROUP BY k.id_kegiatan 
    ORDER BY k.tanggal_kegiatan DESC
");
$kegiatan_list = $stmtKeg->fetchAll();
?>

<div class="flex justify-between items-center">
    <div>
        <h1 class="text-2xl font-bold text-on-surface">Agenda & Kegiatan Warga RT</h1>
        <p class="text-sm text-on-surface-variant">Buat jadwal kerja bakti, rapat warga, atau acara kebersamaan RT</p>
    </div>
    <button onclick="document.getElementById('modalTambahKegiatan').classList.remove('hidden')" class="px-4 py-2 bg-primary text-white text-xs font-bold rounded-lg hover:bg-primary-container flex items-center gap-1">
        <span class="material-symbols-outlined text-sm">event</span> Tambah Agenda Baru
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
                    <th class="p-3">Nama Kegiatan</th>
                    <th class="p-3">Tanggal & Waktu</th>
                    <th class="p-3">Lokasi</th>
                    <th class="p-3">Deskripsi</th>
                    <th class="p-3 text-center">Total Peserta</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-outline-variant">
                <?php foreach ($kegiatan_list as $k): ?>
                <tr>
                    <td class="p-3 font-semibold text-on-surface"><?= htmlspecialchars($k['nama_kegiatan']) ?></td>
                    <td class="p-3 text-xs"><?= date('d/m/Y H:i', strtotime($k['tanggal_kegiatan'])) ?></td>
                    <td class="p-3 text-xs"><?= htmlspecialchars($k['lokasi']) ?></td>
                    <td class="p-3 text-xs text-outline max-w-[250px]"><?= htmlspecialchars($k['deskripsi']) ?></td>
                    <td class="p-3 text-center">
                        <span class="px-2 py-1 bg-blue-100 text-blue-800 rounded-full text-xs font-bold">
                            👥 <?= $k['total_peserta'] ?> Warga
                        </span>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal Tambah Kegiatan -->
<div id="modalTambahKegiatan" class="fixed inset-0 bg-black/50 z-50 hidden flex items-center justify-center p-4">
    <div class="bg-white rounded-xl max-w-md w-full p-6 space-y-4">
        <h3 class="font-bold text-lg text-on-surface">Form Buat Agenda Kegiatan</h3>
        <form action="admin_kegiatan.php" method="POST" class="space-y-3">
            <div>
                <label class="text-xs font-semibold text-outline">Nama Kegiatan</label>
                <input type="text" name="nama_kegiatan" class="w-full h-10 border rounded-lg px-3 text-sm" placeholder="Contoh: Kerja Bakti Massal / Rapat RT" required />
            </div>
            <div>
                <label class="text-xs font-semibold text-outline">Tanggal & Waktu</label>
                <input type="datetime-local" name="tanggal_kegiatan" class="w-full h-10 border rounded-lg px-3 text-sm" required />
            </div>
            <div>
                <label class="text-xs font-semibold text-outline">Lokasi Pelaksanaan</label>
                <input type="text" name="lokasi" class="w-full h-10 border rounded-lg px-3 text-sm" placeholder="Contoh: Lapangan Serbaguna / Pos Ronda" required />
            </div>
            <div>
                <label class="text-xs font-semibold text-outline">Deskripsi Kegiatan</label>
                <textarea name="deskripsi" rows="2" class="w-full border rounded-lg p-2 text-sm" placeholder="Rincian acara..." required></textarea>
            </div>
            <div class="flex justify-end gap-2 pt-2">
                <button type="button" onclick="document.getElementById('modalTambahKegiatan').classList.add('hidden')" class="px-4 py-2 border rounded-lg text-xs font-semibold">Batal</button>
                <button type="submit" class="px-4 py-2 bg-primary text-white rounded-lg text-xs font-semibold">Terbitkan Kegiatan</button>
            </div>
        </form>
    </div>
</div>

<?php require_once 'includes/footer_admin.php'; ?>
