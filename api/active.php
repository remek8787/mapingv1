<?php
header("Content-Type: application/json; charset=utf-8");
require_once __DIR__ . "/../routeros_api.class.php";

$API = new RouterosAPI();
$API->debug = false;

// 🔧 konfigurasi router
$host = "103.196.85.2";
$user = "robot";
$pass = "@Robot2024";
$port = 10328;

$data = [];
if ($API->connect($host,$user,$pass,$port)) {
    $actives = $API->comm("/ppp/active/print");
    $API->disconnect();
    foreach ($actives as $a) {
        if (!empty($a['name'])) {
            $data[] = [
                "secret_username"=>$a['name'],
                "status"=>"active",
                "address"=>$a['address'] ?? null,
                "uptime"=>$a['uptime'] ?? null,
                "service"=>$a['service'] ?? null
            ];
        }
    }
}
echo json_encode($data,JSON_PRETTY_PRINT);
