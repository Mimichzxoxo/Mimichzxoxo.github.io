<?php
// includes/header_warga.php
require_once __DIR__ . '/../config.php';
require_login();
$active_tab = $active_tab ?? 'pengumuman';
$user_name = get_logged_user();
$initial = strtoupper(substr($user_name, 0, 1));
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8"/>
    <meta content="width=device-width, initial-scale=1.0" name="viewport"/>
    <title>Smart RT - Warga Portal</title>
    <!-- Google Fonts & Material Symbols -->
    <link href="https://fonts.googleapis.com" rel="preconnect"/>
    <link crossorigin="" href="https://fonts.gstatic.com" rel="preconnect"/>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700&display=swap" rel="stylesheet"/>
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet"/>
    <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: "#003461",
                        "primary-container": "#004b87",
                        "on-primary": "#ffffff",
                        "primary-fixed": "#d3e4ff",
                        "primary-fixed-dim": "#a3c9ff",
                        surface: "#f8f9fa",
                        "surface-container-low": "#f3f4f5",
                        "on-surface": "#191c1d",
                        "on-surface-variant": "#424750",
                        outline: "#727781",
                        "outline-variant": "#c2c6d1",
                        error: "#ba1a1a",
                        "error-container": "#ffdad6"
                    }
                }
            }
        }
    </script>
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; background-color: #f8f9fa; min-height: 100vh; }
    </style>
</head>
<body class="bg-surface text-on-surface min-h-screen pb-[80px]">
    <!-- TopAppBar -->
    <header class="fixed top-0 w-full z-50 bg-surface border-b border-outline-variant">
        <div class="flex items-center justify-between px-4 h-12 w-full max-w-[600px] mx-auto">
            <div class="flex items-center gap-3">
                <div class="w-8 h-8 rounded-full bg-primary-container text-white flex items-center justify-center font-bold text-sm">
                    <?= $initial ?>
                </div>
                <span class="text-lg text-primary font-bold">Smart RT</span>
            </div>
            <div class="flex items-center gap-2">
                <span class="text-xs text-on-surface-variant hidden sm:inline"><?= htmlspecialchars($user_name) ?></span>
                <a href="logout.php" class="w-8 h-8 flex items-center justify-center rounded-full text-error hover:bg-error-container transition-colors" title="Keluar">
                    <span class="material-symbols-outlined text-[20px]">logout</span>
                </a>
            </div>
        </div>
    </header>

    <!-- Main Content Container -->
    <main class="w-full max-w-[600px] mx-auto pt-[64px] px-4 flex flex-col gap-4">
