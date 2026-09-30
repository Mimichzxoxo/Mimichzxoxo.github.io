<?php
// config.php - Database connection & session setup for Smart RT PHP Native

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

$db_host = '127.0.0.1';
$db_user = 'root';
$db_pass = '';
$db_name = 'smart_rt_db';

try {
    // 1. Try connecting to MySQL without specifying database first
    $pdo_init = new PDO("mysql:host=$db_host;charset=utf8mb4", $db_user, $db_pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);

    // 2. Create database if it does not exist
    $pdo_init->exec("CREATE DATABASE IF NOT EXISTS `$db_name` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    $pdo_init->exec("USE `$db_name`");

    // 3. Check if table 'rt' exists, if not, import database.sql
    $stmt = $pdo_init->query("SHOW TABLES LIKE 'rt'");
    if ($stmt->rowCount() == 0) {
        $sql_file = __DIR__ . '/database.sql';
        if (file_exists($sql_file)) {
            $sql_content = file_get_contents($sql_file);
            // Remove comments and execute multi-queries
            $pdo_init->exec($sql_content);
        }
    }

    $pdo = $pdo_init;

} catch (PDOException $e) {
    die("<div style='font-family: sans-serif; padding: 20px; background: #fee2e2; color: #991b1b; border-radius: 8px; margin: 20px;'>
        <h2 style='margin-top:0;'>⚠️ Gagal Terhubung ke MySQL Database</h2>
        <p><b>Pesan Error:</b> " . htmlspecialchars($e->getMessage()) . "</p>
        <hr style='border-color: #fca5a5;'>
        <p><b>Petunjuk Pengerjaan:</b></p>
        <ul>
            <li>Pastikan <b>XAMPP / Laragon / MySQL Server</b> Anda sudah diaktifkan (Status Running).</li>
            <li>Pastikan username (<code>root</code>) dan password (default kosong) pada <code>config.php</code> sudah sesuai.</li>
        </ul>
    </div>");
}

// Helper Functions
function is_logged_in() {
    return isset($_SESSION['user_id']);
}

function require_login() {
    if (!is_logged_in()) {
        header("Location: index.php");
        exit;
    }
}

function get_logged_user() {
    return $_SESSION['user_name'] ?? 'User';
}

function get_user_role() {
    return $_SESSION['role'] ?? 'warga';
}
