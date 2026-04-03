<?php
require_once '../config.php';
header('Content-Type: application/json; charset=utf-8');

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
$conn->set_charset(DB_CHARSET ?? 'utf8mb4');

function read_input(): array {
  $raw = file_get_contents('php://input');
  if ($raw) {
    $json = json_decode($raw, true);
    if (json_last_error() === JSON_ERROR_NONE && is_array($json)) {
      return $json;
    }
  }
  // fallback ke POST biasa
  return $_POST ?? [];
}

try {
  $method = $_SERVER['REQUEST_METHOD'];
  $input = read_input();

  if ($method === 'GET') {
    // Hanya field yang dipakai di frontend
    $sql = "SELECT id, name, description, latitude, longitude
            FROM servers
            ORDER BY name";
    $rs = $conn->query($sql);
    echo json_encode($rs->fetch_all(MYSQLI_ASSOC), JSON_UNESCAPED_UNICODE);
    exit;
  }

  if ($method === 'POST') {
    $name = $input['name'] ?? '';
    $desc = $input['description'] ?? '';
    $lat  = isset($input['lat']) ? (float)$input['lat'] : (float)($input['latitude'] ?? 0);
    $lng  = isset($input['lng']) ? (float)$input['lng'] : (float)($input['longitude'] ?? 0);

    $stmt = $conn->prepare("INSERT INTO servers (name, description, latitude, longitude) VALUES (?,?,?,?)");
    $stmt->bind_param("ssdd", $name, $desc, $lat, $lng);
    $stmt->execute();

    echo json_encode(['status' => 'ok', 'id' => $conn->insert_id], JSON_UNESCAPED_UNICODE);
    exit;
  }

  if ($method === 'PUT') {
    $id   = (int)($input['id'] ?? 0);
    $name = $input['name'] ?? '';
    $desc = $input['description'] ?? '';
    $lat  = isset($input['lat']) ? (float)$input['lat'] : (float)($input['latitude'] ?? 0);
    $lng  = isset($input['lng']) ? (float)$input['lng'] : (float)($input['longitude'] ?? 0);

    $stmt = $conn->prepare("UPDATE servers SET name=?, description=?, latitude=?, longitude=? WHERE id=?");
    $stmt->bind_param("ssddi", $name, $desc, $lat, $lng, $id);
    $stmt->execute();

    echo json_encode(['status' => 'ok'], JSON_UNESCAPED_UNICODE);
    exit;
  }

  if ($method === 'DELETE') {
    // ambil id dari query ?id= atau dari body
    $id = isset($_GET['id']) ? (int)$_GET['id'] : (int)($input['id'] ?? 0);
    $stmt = $conn->prepare("DELETE FROM servers WHERE id=?");
    $stmt->bind_param("i", $id);
    $stmt->execute();

    echo json_encode(['status' => 'ok'], JSON_UNESCAPED_UNICODE);
    exit;
  }

  // metode lain
  http_response_code(405);
  echo json_encode(['error' => 'Method not allowed']);
} catch (Throwable $e) {
  http_response_code(500);
  echo json_encode(['error' => $e->getMessage()]);
}
