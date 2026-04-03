<?php
header("Content-Type: application/json; charset=utf-8");
require_once __DIR__ . "/../routeros_api.class.php";

$API = new RouterosAPI();
$API->debug = false;

// 🔧 Konfigurasi Mikrotik
$host = "103.196.85.2";   // IP Router
$user = "robot";          // Username
$pass = "@Robot2024";     // Password
$port = 10328;            // Port API

$data = [];
$q = isset($_GET['q']) ? strtolower(trim($_GET['q'])) : '';
$limit = isset($_GET['limit']) ? intval($_GET['limit']) : 0; // default ambil semua, bisa kasih ?limit=5

if ($API->connect($host, $user, $pass, $port)) {
    $secrets = $API->comm("/ppp/secret/print");
    $API->disconnect();

    foreach ($secrets as $s) {
        if (!empty($s['name'])) {
            $name = $s['name'];
            $desc = isset($s['comment']) ? $s['comment'] : ""; // ambil comment Mikrotik kalau ada

            if ($q === '' || strpos(strtolower($name), $q) !== false) {
                $data[] = [
                    "secret_username" => $name,
                    "description" => $desc
                ];
            }
        }
    }
}

// kalau ada limit → potong array
if ($limit > 0) {
    $data = array_slice($data, 0, $limit);
}

echo json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
