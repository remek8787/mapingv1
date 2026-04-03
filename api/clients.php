<?php
header("Content-Type: application/json; charset=utf-8");
require_once __DIR__ . "/../config.php";
require_once __DIR__ . "/../routeros_api.class.php";

$API = new RouterosAPI();
$API->debug = false;

// 🔧 konfigurasi router (samakan dgn punyamu)
$host = "103.196.85.2";
$user = "robot";
$pass = "@Robot2024";
$port = 10328;

/**
 * Ambil semua client dari DB + info ODP (name, capacity)
 * Index array dengan kunci secret (lowercase) agar mudah merge dgn data aktif Mikrotik.
 */
$clients = [];

$sql = "
  SELECT
    c.id, c.name, c.address, c.description, c.odp_id,
    c.latitude, c.longitude, c.secret_username,
    o.name     AS odp_name,
    o.capacity AS odp_capacity
  FROM clients c
  LEFT JOIN odp o ON o.id = c.odp_id
";
$q = mysqli_query($conn, $sql);

if ($q) {
  while ($r = mysqli_fetch_assoc($q)) {
    $secretKey = strtolower(trim($r['secret_username'] ?? ''));
    if ($secretKey === '') continue;

    $clients[$secretKey] = [
      "id"              => (int)$r['id'],
      "name"            => $r['name'] ?: "-",
      "address"         => $r['address'],
      "description"     => $r['description'],
      "odp_id"          => isset($r['odp_id']) ? (int)$r['odp_id'] : null,
      "odp_name"        => $r['odp_name'] ?? null,
      "odp_capacity"    => isset($r['odp_capacity']) ? (int)$r['odp_capacity'] : null,
      "latitude"        => is_numeric($r['latitude'])  ? (float)$r['latitude']  : null,
      "longitude"       => is_numeric($r['longitude']) ? (float)$r['longitude'] : null,
      "secret_username" => $r['secret_username'],
      "status"          => "inactive",
      "ip"              => null,     // alias singkat utk frontend
      "ip_address"      => null,     // tetap disertakan utk kompatibilitas
      "uptime"          => null,
      "service"         => null
    ];
  }
}

/**
 * Ambil data aktif dari Mikrotik lalu merge ke daftar.
 * Jika ada user aktif tapi belum ada di DB → tambahkan entri dummy.
 */
if ($API->connect($host, $user, $pass, $port)) {
  $actives = $API->comm("/ppp/active/print");
  $API->disconnect();

  foreach ($actives as $a) {
    if (empty($a['name'])) continue;

    $secretKey = strtolower(trim($a['name']));
    $ip        = $a['address'] ?? null;
    $uptime    = $a['uptime']  ?? null;
    $service   = $a['service'] ?? null;

    if (isset($clients[$secretKey])) {
      // update status menjadi active + meta
      $clients[$secretKey]["status"]     = "active";
      $clients[$secretKey]["ip"]         = $ip;
      $clients[$secretKey]["ip_address"] = $ip;
      $clients[$secretKey]["uptime"]     = $uptime;
      $clients[$secretKey]["service"]    = $service;
    } else {
      // user aktif tapi belum tercatat di DB → masukkan sebagai dummy
      $clients[$secretKey] = [
        "id"              => null,
        "name"            => "-",
        "address"         => "-",
        "description"     => null,
        "odp_id"          => null,
        "odp_name"        => null,
        "odp_capacity"    => null,
        "latitude"        => 0,
        "longitude"       => 0,
        "secret_username" => $a['name'],
        "status"          => "active",
        "ip"              => $ip,
        "ip_address"      => $ip,
        "uptime"          => $uptime,
        "service"         => $service
      ];
    }
  }
}

// keluarkan hasil sebagai list (bukan associative by secret)
echo json_encode(array_values($clients), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
