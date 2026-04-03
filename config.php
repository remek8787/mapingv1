<?php
// Konfigurasi MikroTik (ini terpisah dari MySQL)
define('MT_HOST', '103.196.85.2');
define('MT_USER', 'robot');
define('MT_PASS', '@Robot2024');
define('MT_PORT', 10328);
define('PER_PAGE', 15);

// Database
define('DB_HOST', '127.0.0.1');   // coba 127.0.0.1 dulu
define('DB_PORT', 3306);          // sesuaikan jika MySQL-mu tidak di 3306
define('DB_NAME', 'mapping_network');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
try {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME, DB_PORT);
    $conn->set_charset(DB_CHARSET);
} catch (mysqli_sql_exception $e) {
    die("Gagal konek MySQL: " . $e->getMessage());
}
