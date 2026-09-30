<?php
// index.php - Main entry & login page for Smart RT PHP Native
require_once 'config.php';

$error = null;

if (is_logged_in()) {
    $role = get_user_role();
    if ($role === 'super_admin' || $role === 'admin_rt') {
        header("Location: admin_dashboard.php");
        exit;
    } else {
        header("Location: warga_pengumuman.php");
        exit;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $identifier = trim($_POST['identifier'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if (!empty($identifier) && !empty($password)) {
        // 1. Check Super Admin
        $stmt = $pdo->prepare("SELECT * FROM super_admin WHERE username = :id AND password = :pwd");
        $stmt->execute(['id' => $identifier, 'pwd' => $password]);
        $sa = $stmt->fetch();

        if ($sa) {
            $_SESSION['user_id'] = $sa['id_superadmin'];
            $_SESSION['user_name'] = $sa['nama'];
            $_SESSION['role'] = 'super_admin';
            $_SESSION['id_rt'] = 1;
            header("Location: admin_dashboard.php");
            exit;
        }

        // 2. Check Warga (Includes Admin RT role in status/role)
        $stmt = $pdo->prepare("
            SELECT w.*, k.id_rt, r.nomor_rt, r.rw 
            FROM warga w 
            JOIN keluarga k ON w.id_keluarga = k.id_keluarga
            JOIN rt r ON k.id_rt = r.id_rt
            WHERE (w.email_username = :id OR w.no_telepon = :id2) AND w.password = :pwd
        ");
        $stmt->execute(['id' => $identifier, 'id2' => $identifier, 'pwd' => $password]);
        $warga = $stmt->fetch();

        if ($warga) {
            $_SESSION['user_id'] = $warga['id_warga'];
            $_SESSION['user_name'] = $warga['nama'];
            $_SESSION['role'] = $warga['role']; // 'warga', 'admin_rt', or 'super_admin'
            $_SESSION['id_rt'] = $warga['id_rt'];
            $_SESSION['id_keluarga'] = $warga['id_keluarga'];

            if ($warga['role'] === 'admin_rt' || $warga['role'] === 'super_admin') {
                header("Location: admin_dashboard.php");
            } else {
                header("Location: warga_pengumuman.php");
            }
            exit;
        } else {
            $error = 'Username/No Telepon atau Kata Sandi salah.';
        }
    } else {
        $error = 'Harap isi semua kolom login.';
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8"/>
    <meta content="width=device-width, initial-scale=1.0" name="viewport"/>
    <title>Masuk - Smart RT (PHP)</title>
    <!-- Google Fonts & Material Symbols -->
    <link href="https://fonts.googleapis.com" rel="preconnect"/>
    <link crossorigin="" href="https://fonts.gstatic.com" rel="preconnect"/>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700&display=swap" rel="stylesheet"/>
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet"/>
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
    <script>
      tailwind.config = {
        theme: {
          extend: {
            colors: {
              primary: "#003461",
              "primary-container": "#004b87",
              "primary-fixed": "#d3e4ff",
              "primary-fixed-dim": "#a3c9ff",
              surface: "#f8f9fa",
              "surface-container-lowest": "#ffffff",
              "surface-container-low": "#f3f4f5",
              "on-surface": "#191c1d",
              "on-surface-variant": "#424750",
              outline: "#727781",
              "outline-variant": "#c2c6d1",
              error: "#ba1a1a",
              "error-container": "#ffdad6",
              "on-error-container": "#93000a"
            }
          }
        }
      }
    </script>
    <style>
        body { min-height: max(884px, 100dvh); font-family: 'Plus Jakarta Sans', sans-serif; }
    </style>
</head>
<body class="bg-surface text-on-surface antialiased min-h-screen flex flex-col items-center justify-center p-4">
    <main class="w-full max-w-[480px] bg-surface-container-lowest rounded-xl md:border border-outline-variant p-6 md:p-8 flex flex-col gap-6 shadow-sm">
        <!-- Brand Header -->
        <div class="w-full flex justify-center mb-2">
            <div class="w-full h-[180px] rounded-xl relative overflow-hidden flex items-center justify-center" style="background: linear-gradient(135deg, #003461 0%, #004b87 100%);">
                <div class="text-center text-white p-4">
                    <span class="material-symbols-outlined text-6xl text-primary-fixed mb-2">roofing</span>
                    <h2 class="text-2xl font-bold tracking-tight">Smart RT</h2>
                    <p class="text-xs text-primary-fixed-dim">PHP Native Edition • MySQL Database</p>
                </div>
            </div>
        </div>

        <div class="flex flex-col gap-1 text-center md:text-left">
            <h1 class="text-xl font-bold text-on-surface">Selamat Datang di Smart RT</h1>
            <p class="text-sm text-on-surface-variant">Masuk untuk mengakses layanan RT/RW digital Anda</p>
        </div>

        <?php if ($error): ?>
        <div class="p-3 bg-error-container text-on-error-container rounded-lg text-sm flex items-center gap-2">
            <span class="material-symbols-outlined text-error">error</span>
            <span><?= htmlspecialchars($error) ?></span>
        </div>
        <?php endif; ?>

        <!-- Login Form -->
        <form class="flex flex-col gap-4 w-full" action="index.php" method="POST">
            <div class="flex flex-col gap-1">
                <label class="text-xs font-semibold text-on-surface" for="identifier">Username / No HP</label>
                <div class="relative w-full">
                    <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-outline">account_circle</span>
                    <input class="w-full h-12 pl-10 pr-4 bg-surface-container-lowest border border-outline rounded-xl text-sm focus:outline-none focus:border-primary-container focus:ring-1 focus:ring-primary-container" id="identifier" name="identifier" placeholder="Contoh: budi / admin_rt / superadmin" type="text" required/>
                </div>
            </div>

            <div class="flex flex-col gap-1">
                <label class="text-xs font-semibold text-on-surface" for="password">Kata Sandi</label>
                <div class="relative w-full">
                    <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-outline">lock</span>
                    <input class="w-full h-12 pl-10 pr-4 bg-surface-container-lowest border border-outline rounded-xl text-sm focus:outline-none focus:border-primary-container focus:ring-1 focus:ring-primary-container" id="password" name="password" placeholder="Masukkan kata sandi Anda" type="password" required/>
                </div>
            </div>

            <div class="p-3 bg-surface-container-low rounded-lg text-xs space-y-2 text-on-surface-variant">
                <p class="font-semibold text-primary">Akses Cepat Demo:</p>
                <div class="flex flex-wrap gap-2 pt-1">
                    <button type="button" onclick="fillCred('budi', 'warga123')" class="px-2 py-1 bg-white rounded border hover:bg-primary-container hover:text-white text-xs transition">Warga: budi / warga123</button>
                    <button type="button" onclick="fillCred('admin_rt', 'admin123')" class="px-2 py-1 bg-white rounded border hover:bg-primary-container hover:text-white text-xs transition">Admin RT: admin_rt / admin123</button>
                    <button type="button" onclick="fillCred('superadmin', 'admin123')" class="px-2 py-1 bg-white rounded border hover:bg-primary-container hover:text-white text-xs transition">Super Admin: superadmin / admin123</button>
                </div>
            </div>

            <button type="submit" class="w-full h-12 bg-primary hover:bg-primary-container text-white font-semibold rounded-xl transition-colors shadow">
                Masuk Ke Sistem
            </button>
        </form>
    </main>

    <script>
        function fillCred(user, pwd) {
            document.getElementById('identifier').value = user;
            document.getElementById('password').value = pwd;
        }
    </script>
</body>
</html>
