<?php
// includes/header_admin.php
require_once __DIR__ . '/../config.php';
require_login();

$user_role = get_user_role();
if ($user_role !== 'admin_rt' && $user_role !== 'super_admin') {
    header("Location: warga_pengumuman.php");
    exit;
}

$active_tab = $active_tab ?? 'dashboard';
$user_name = get_logged_user();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8"/>
    <meta content="width=device-width, initial-scale=1.0" name="viewport"/>
    <title>Smart RT - Portal Administrasi</title>
    <!-- Google Fonts & Material Symbols -->
    <link href="https://fonts.googleapis.com" rel="preconnect"/>
    <link crossorigin="" href="https://fonts.gstatic.com" rel="preconnect"/>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Plus+Jakarta+Sans:wght@500;600;700;800&display=swap" rel="stylesheet"/>
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200" rel="stylesheet"/>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: "#006194",
                        "primary-container": "#007bb9",
                        "on-primary": "#ffffff",
                        surface: "#f8f9ff",
                        "surface-container-low": "#eff4ff",
                        "surface-container-lowest": "#ffffff",
                        "on-surface": "#0b1c30",
                        "on-surface-variant": "#3f4850",
                        outline: "#707881",
                        "outline-variant": "#bfc7d2",
                        error: "#ba1a1a"
                    }
                }
            }
        };
    </script>
</head>
<body class="bg-surface font-sans text-on-surface antialiased flex">

    <!-- Sidebar Desktop -->
    <aside class="w-64 bg-white h-screen border-r border-outline-variant fixed left-0 top-0 flex flex-col justify-between p-4 z-40">
        <div>
            <div class="flex items-center gap-3 px-3 py-4 border-b border-outline-variant mb-4">
                <span class="material-symbols-outlined text-primary text-3xl">roofing</span>
                <div>
                    <h1 class="font-bold text-lg text-primary leading-none">Smart RT</h1>
                    <span class="text-[10px] text-outline uppercase font-semibold">Admin Panel PHP</span>
                </div>
            </div>

            <nav class="space-y-1">
                <a href="admin_dashboard.php" class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-semibold <?= ($active_tab=='dashboard') ? 'bg-primary text-white' : 'text-on-surface-variant hover:bg-surface-container-low' ?>">
                    <span class="material-symbols-outlined text-xl">dashboard</span> Dashboard
                </a>
                <a href="admin_warga.php" class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-semibold <?= ($active_tab=='warga') ? 'bg-primary text-white' : 'text-on-surface-variant hover:bg-surface-container-low' ?>">
                    <span class="material-symbols-outlined text-xl">group</span> Data Warga
                </a>
                <a href="admin_surat.php" class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-semibold <?= ($active_tab=='surat') ? 'bg-primary text-white' : 'text-on-surface-variant hover:bg-surface-container-low' ?>">
                    <span class="material-symbols-outlined text-xl">description</span> Layanan Surat
                </a>
                <a href="admin_iuran.php" class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-semibold <?= ($active_tab=='iuran') ? 'bg-primary text-white' : 'text-on-surface-variant hover:bg-surface-container-low' ?>">
                    <span class="material-symbols-outlined text-xl">payments</span> Iuran Warga
                </a>
                <a href="admin_keuangan.php" class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-semibold <?= ($active_tab=='keuangan') ? 'bg-primary text-white' : 'text-on-surface-variant hover:bg-surface-container-low' ?>">
                    <span class="material-symbols-outlined text-xl">account_balance_wallet</span> Kas Keuangan
                </a>
                <a href="admin_inventaris.php" class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-semibold <?= ($active_tab=='inventaris') ? 'bg-primary text-white' : 'text-on-surface-variant hover:bg-surface-container-low' ?>">
                    <span class="material-symbols-outlined text-xl">inventory_2</span> Inventaris RT
                </a>
                <a href="admin_kegiatan.php" class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-semibold <?= ($active_tab=='kegiatan') ? 'bg-primary text-white' : 'text-on-surface-variant hover:bg-surface-container-low' ?>">
                    <span class="material-symbols-outlined text-xl">event</span> Agenda Kegiatan
                </a>
                <?php if ($user_role === 'super_admin'): ?>
                <a href="admin_superadmin.php" class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-semibold <?= ($active_tab=='superadmin') ? 'bg-purple-700 text-white' : 'text-purple-700 hover:bg-purple-50' ?>">
                    <span class="material-symbols-outlined text-xl">admin_panel_settings</span> Master RT/RW
                </a>
                <?php endif; ?>
            </nav>
        </div>

        <div class="border-t border-outline-variant pt-3 flex items-center justify-between">
            <div class="text-xs">
                <p class="font-bold text-on-surface truncate"><?= htmlspecialchars($user_name) ?></p>
                <p class="text-outline uppercase text-[10px]"><?= htmlspecialchars($user_role) ?></p>
            </div>
            <a href="logout.php" class="text-error hover:bg-red-50 p-1.5 rounded-lg" title="Keluar">
                <span class="material-symbols-outlined text-xl">logout</span>
            </a>
        </div>
    </aside>

    <!-- Main Content wrapper -->
    <div class="ml-64 flex-1 min-h-screen flex flex-col">
        <!-- Top Navbar -->
        <header class="h-16 border-b border-outline-variant bg-white flex items-center justify-between px-6 sticky top-0 z-30">
            <h2 class="font-bold text-lg text-on-surface">Portal Pengurus Smart RT</h2>
            <div class="flex items-center gap-3">
                <a href="warga_pengumuman.php" class="text-xs bg-surface-container-low px-3 py-1.5 rounded-lg font-semibold text-primary hover:bg-primary hover:text-white transition">
                    Lihat Mode Warga 📱
                </a>
            </div>
        </header>

        <main class="p-6 space-y-6 flex-1">
