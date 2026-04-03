<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../config.php';

$method = $_SERVER['REQUEST_METHOD'];

/* ===== Helpers ===== */
function to_int_or_null($v){ return ($v === '' || $v === null) ? null : (int)$v; }
function to_float_or_null($v){ return ($v === '' || $v === null) ? null : (float)$v; }

/* ==================== GET ==================== */
if ($method === 'GET') {
  $id           = isset($_GET['id']) ? (int)$_GET['id'] : 0;
  $server_id    = isset($_GET['server_id']) ? (int)$_GET['server_id'] : 0;
  $odc_id       = isset($_GET['odc_id']) ? (int)$_GET['odc_id'] : 0;
  $parent_id    = isset($_GET['parent_odp_id']) ? (int)$_GET['parent_odp_id'] : 0;
  $q            = isset($_GET['q']) ? trim($_GET['q']) : '';

  $sql = "SELECT 
            o.*,
            s.name  AS server_name,
            od.name AS odc_name,
            pop.name AS parent_odp_name
          FROM odp o
          LEFT JOIN servers s ON s.id = o.server_id
          LEFT JOIN odc od     ON od.id = o.odc_id
          LEFT JOIN odp pop    ON pop.id = o.parent_odp_id";

  $where = []; $bind = []; $types = '';
  if ($id>0)        { $where[]="o.id=?";             $bind[]=$id;        $types.='i'; }
  if ($server_id>0) { $where[]="o.server_id=?";      $bind[]=$server_id; $types.='i'; }
  if ($odc_id>0)    { $where[]="o.odc_id=?";         $bind[]=$odc_id;    $types.='i'; }
  if ($parent_id>0) { $where[]="o.parent_odp_id=?";  $bind[]=$parent_id; $types.='i'; }
  if ($q!=='')      { $where[]="o.name LIKE ?";      $bind[]="%$q%";     $types.='s'; }
  if ($where) $sql .= " WHERE ".implode(' AND ', $where);
  $sql .= " ORDER BY o.name ASC";

  if ($bind) {
    $stmt=$conn->prepare($sql);
    $stmt->bind_param($types, ...$bind);
    $stmt->execute();
    $res=$stmt->get_result();
  } else {
    $res=$conn->query($sql);
  }

  $rows=[];
  while($r=$res->fetch_assoc()){ $rows[]=$r; }
  echo json_encode($rows, JSON_UNESCAPED_UNICODE); exit;
}

/* ==================== POST ==================== */
if ($method === 'POST') {
  // kalau perlu dukung JSON body
  if (stripos($_SERVER['CONTENT_TYPE'] ?? '', 'application/json') !== false) {
    $raw = file_get_contents('php://input');
    $data = json_decode($raw, true) ?: [];
  } else {
    $data = $_POST;
  }

  $id            = (int)($data['id'] ?? 0);
  $name          = $data['name'] ?? '';
  $capacity      = to_int_or_null($data['capacity'] ?? null);
  $description   = $data['description'] ?? null;
  $server_id     = to_int_or_null($data['server_id'] ?? null);
  $odc_id        = to_int_or_null($data['odc_id'] ?? null);
  $parent_odp_id = to_int_or_null($data['parent_odp_id'] ?? null);
  $lat           = to_float_or_null($data['latitude'] ?? null);
  $lng           = to_float_or_null($data['longitude'] ?? null);
  $status        = $data['status'] ?? 'installed';

  if ($name === '') {
    http_response_code(422);
    echo json_encode(['ok'=>false,'error'=>'Nama ODP wajib diisi']); exit;
  }

  if ($id > 0) {
    $stmt = $conn->prepare(
      "UPDATE odp SET 
        name=?, capacity=?, description=?, server_id=?, odc_id=?, parent_odp_id=?, 
        latitude=?, longitude=?, status=?
       WHERE id=?"
    );
    // s i s i i i d d s i
    $stmt->bind_param(
      "sisiiiddsi",
      $name, $capacity, $description, $server_id, $odc_id, $parent_odp_id, $lat, $lng, $status, $id
    );
    $ok = $stmt->execute();
    echo json_encode(['ok'=>$ok,'id'=>$id]); exit;
  } else {
    $stmt = $conn->prepare(
      "INSERT INTO odp (name, capacity, description, server_id, odc_id, parent_odp_id, latitude, longitude, status)
       VALUES (?,?,?,?,?,?,?,?,?)"
    );
    $stmt->bind_param(
      "sisiiidds",
      $name, $capacity, $description, $server_id, $odc_id, $parent_odp_id, $lat, $lng, $status
    );
    $ok = $stmt->execute();
    echo json_encode(['ok'=>$ok,'id'=>$conn->insert_id]); exit;
  }
}

/* =================== DELETE =================== */
if ($method === 'DELETE') {
  parse_str(file_get_contents('php://input'), $del);
  $id = isset($del['id']) ? (int)$del['id'] : 0;
  if ($id > 0) {
    $stmt = $conn->prepare("DELETE FROM odp WHERE id=?");
    $stmt->bind_param("i", $id);
    $ok = $stmt->execute();
    echo json_encode(['ok'=>$ok]); exit;
  }
  http_response_code(400);
  echo json_encode(['ok'=>false,'error'=>'id invalid']); exit;
}

http_response_code(405);
echo json_encode(['error'=>'Method not allowed']);
