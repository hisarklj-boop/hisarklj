<?php
// Database configuration
define('DB_HOST', 'localhost');
define('DB_NAME', 'okul_kantin');
define('DB_USER', 'webadmin');
define('DB_PASS', 'Web@Admin2025!');

try {
    $db = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false
        ]
    );
} catch (PDOException $e) {
    die("Veritabanı bağlantı hatası!");
}

// Session ayarları
session_start();
?>
