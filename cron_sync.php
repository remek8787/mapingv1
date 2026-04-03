<?php
require_once __DIR__.'/config.php';
require_once __DIR__.'/db.php';
require_once __DIR__.'/routeros_api.class.php';

$API = new RouterosAPI();
if (defined('MT_PORT')) {
  $API->port = MT_PORT;
}

if (!$API->connect(MT_HOST, MT_USER, MT_PASS)) {
  exit("Failed connect to MikroTik\n");
}

// Ambil data dari RouterOS
$secrets = $API->comm('/ppp/secret/print');   // fields: name, profile, service, caller-id, last-logged-out, ...
$active  = $API->comm('/ppp/active/print');   // fields: name, address, caller-id, uptime, ...
$API->disconnect();

$pdo = pdo();
$pdo->beginTransaction();

/* ===============================
   UPSERT master clients dari /ppp/secret
   - Simpan: username, display_name (turunan), profile, service, caller_id, last_logout
   =============================== */
$up = $pdo->prepare("
  INSERT INTO clients (username, display_name, profile, service, caller_id, last_logout)
  VALUES (:u, :d, :p, :sv, :c, :lo)
  ON DUPLICATE KEY UPDATE
    display_name = VALUES(display_name),
    profile      = VALUES(profile),
    service      = VALUES(service),
    caller_id    = VALUES(caller_id),
    last_logout  = VALUES(last_logout)
");

foreach ($secrets as $s) {
  $u = $s['name'] ?? '';
  if ($u === '') continue;

  // display_name opsional: ambil bagian setelah underscore pertama jika ada
  $disp = preg_replace('/^[^_]+_/', '', $u);

  // last-logged-out → DATETIME (jika ada)
  $lastLogout = null;
  if (!empty($s['last-logged-out'])) {
    $ts = strtotime($s['last-logged-out']);
    if ($ts !== false) {
      $lastLogout = date('Y-m-d H:i:s', $ts);
    }
  }

  $up->execute([
    ':u'  => $u,
    ':d'  => $disp,
    ':p'  => $s['profile']   ?? null,
    ':sv' => $s['service']   ?? null,   // biasanya 'pppoe'
    ':c'  => $s['caller-id'] ?? null,
    ':lo' => $lastLogout
  ]);
}

/* ===============================
   Snapshot /ppp/active → tabel connections
   =============================== */
$ins = $pdo->prepare("
  INSERT INTO connections (username, ip_addr, caller_id, uptime_seconds)
  VALUES (:u, :ip, :c, :up)
");

foreach ($active as $a) {
  $ins->execute([
    ':u'  => $a['name']      ?? '',
    ':ip' => $a['address']   ?? null,
    ':c'  => $a['caller-id'] ?? null,
    ':up' => parseUptime($a['uptime'] ?? '')
  ]);
}

/* (Opsional) Bersihkan snapshot lama biar tabel tidak membengkak */
$pdo->exec("DELETE FROM connections WHERE last_seen < NOW() - INTERVAL 14 DAY");

$pdo->commit();

/* ===============================
   Helpers
   =============================== */
function parseUptime($s) {
  if (!$s) return 0;
  preg_match_all('/(\d+)([wdhms])/', $s, $m, PREG_SET_ORDER);
  $t = 0;
  foreach ($m as $x) {
    $n = (int)$x[1];
    $u = $x[2];
    $t += [
      'w' => 604800,
      'd' => 86400,
      'h' => 3600,
      'm' => 60,
      's' => 1
    ][$u] * $n;
  }
  return $t;
}
