<?php
session_start();
header('Content-Type: application/json; charset=UTF-8');
require_once __DIR__ . '/../vendor/autoload.php';

if (!isset($_SESSION['user_id'])) {
  echo json_encode(['ok' => false, 'error' => 'Nav autorizācijas']);
  exit;
}

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->safeLoad();

$conn = new mysqli(
  $_ENV['DB_HOST'] ?? 'localhost',
  $_ENV['DB_USER'] ?? 'root',
  $_ENV['DB_PASS'] ?? '',
  $_ENV['DB_NAME'] ?? 'dzivnieku_patversme',
  $_ENV['DB_PORT'] ?? 3306
);

if ($conn->connect_error) {
  echo json_encode(['ok' => false, 'error' => 'DB savienojuma kļūda']);
  exit;
}

$user_id = intval($_SESSION['user_id']);
$status_filter = $_GET['status'] ?? '';

$query = "
  SELECT 
    a.id,
    d.vards AS animal_name,
    d.suga AS animal_type,
    d.attels AS image,
    a.datums AS date,
    a.laiks AS time,
    a.piezimes AS message,
    a.statuss AS status,
    a.created_at
  FROM adopcijas_pieteikumi a
  JOIN dzivnieki d ON a.pet_id = d.id
  WHERE a.lietotaja_id = ?
";
if (!empty($status_filter)) {
  $query .= " AND a.statuss = ?";
}

$stmt = $conn->prepare($query);
if (!empty($status_filter)) {
  $stmt->bind_param('is', $user_id, $status_filter);
} else {
  $stmt->bind_param('i', $user_id);
}
$stmt->execute();
$res = $stmt->get_result();

$data = [];
while ($row = $res->fetch_assoc()) {
  $row['image'] = !empty($row['image']) ? $row['image'] : 'kitty.jpg';
  $data[] = $row;
}

echo json_encode(['ok' => true, 'data' => $data], JSON_UNESCAPED_UNICODE);
