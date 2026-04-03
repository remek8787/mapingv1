<?php
require_once __DIR__ . '/../config.php';
header('Content-Type: application/json; charset=utf-8');

$method = $_SERVER['REQUEST_METHOD'];

/**
 * Helper: bersihkan angka nullable (kembalikan null jika kosong)
 */
function to_int_or_null($v) {
  if ($v === '' || $v === null) return null;
  return (int)$v;
}
function to_float_or_null($v) {
  if ($v === '' || $v === null) return null;
  return (float)$v;
}

if ($method === 'GET') {
  // Filter opsional
  $id        = isset($_GET['id']) ? (int)$_GET['id'] : 0;
  $server_id = isset($_GET['server_id']) ? (int)$_GET['server_id'] : 0;
  $q         = isset($_GET['q']) ? trim($_GET['q']) : '';

  // Base query
  $sql = "SELECT o.*, s.name AS server_name
          FROM odc o
          LEFT JOIN servers s ON s.id = o.server_id";
  $where = [];
  $bind  = [];
  $types = '';

  if ($id > 0)         { $where[] = "o.id = ?";         $bind[] = $id;        $types .= 'i'; }
  if ($server_id > 0)  { $where[] = "o.server_id = ?";  $bind[] = $server_id; $types .= 'i'; }
  if ($q !== '')       { $where[] = "o.name LIKE ?";    $bind[] = "%$q%";     $types .= 's'; }

  if ($where) $sql .= " WHERE ".implode(' AND ', $where);
  $sql .= " ORDER BY o.name ASC";

  if ($bind) {
    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types, ...$bind);
    $stmt->execute();
    $res = $stmt->get_result();
  } else {
    $res = $conn->query($sql);
  }

  $rows = [];
  while ($r = $res->fetch_assoc()) {
    // Pastikan kolom kapasitas_in / kapasitas_out ikut ada walau null
    if (!array_key_exists('kapasitas_in', $r))  $r['kapasitas_in']  = null;
    if (!array_key_exists('kapasitas_out', $r)) $r['kapasitas_out'] = null;
    $rows[] = $r;
  }
  echo json_encode($rows, JSON_UNESCAPED_UNICODE); exit;
}

if ($method === 'POST') {
  // Terima form-encoded biasa
  $id           = (int)($_POST['id'] ?? 0);
  $name         = $_POST['name'] ?? '';
  $capacity     = to_int_or_null($_POST['capacity'] ?? null);
  $kap_in       = to_int_or_null($_POST['kapasitas_in'] ?? null);
  $kap_out      = to_int_or_null($_POST['kapasitas_out'] ?? null);
  $description  = $_POST['description'] ?? null;
  $server_id    = to_int_or_null($_POST['server_id'] ?? null);
  $lat          = to_float_or_null($_POST['latitude'] ?? null);
  $lng          = to_float_or_null($_POST['longitude'] ?? null);
  $status       = $_POST['status'] ?? 'installed';

  // Minimal validasi
  if ($name === '') { http_response_code(422); echo json_encode(['ok'=>false,'error'=>'Nama ODC wajib diisi']); exit; }

  if ($id > 0) {
    $stmt = $conn->prepare(
      "UPDATE odc SET 
        name=?, capacity=?, kapasitas_in=?, kapasitas_out=?, description=?, 
        server_id=?, latitude=?, longitude=?, status=? 
       WHERE id=?"
    );
    // s i i i s i d d s i
    $stmt->bind_param(
      "siiisiddsi",
      $name, $capacity, $kap_in, $kap_out, $description, $server_id, $lat, $lng, $status, $id
    );
    $ok = $stmt->execute();
    echo json_encode(['ok'=>$ok,'id'=>$id]); exit;
  } else {
    $stmt = $conn->prepare(
      "INSERT INTO odc (name, capacity, kapasitas_in, kapasitas_out, description, server_id, latitude, longitude, status)
       VALUES (?,?,?,?,?,?,?,?,?)"
    );
    // s i i i s i d d s
    $stmt->bind_param(
      "siiisidds",
      $name, $capacity, $kap_in, $kap_out, $description, $server_id, $lat, $lng, $status
    );
    $ok = $stmt->execute();
    echo json_encode(['ok'=>$ok,'id'=>$conn->insert_id]); exit;
  }
}

if ($method === 'DELETE') {
  parse_str(file_get_contents("php://input"), $del);
  $id = isset($del['id']) ? (int)$del['id'] : 0;
  if ($id > 0) {
    $stmt = $conn->prepare("DELETE FROM odc WHERE id=?");
    $stmt->bind_param("i", $id);
    $ok = $stmt->execute();
    echo json_encode(['ok'=>$ok]); exit;
  }
  http_response_code(400);
  echo json_encode(['ok'=>false,'error'=>'id invalid']); exit;
}

http_response_code(405);
echo json_encode(['error'=>'Method not allowed']);
