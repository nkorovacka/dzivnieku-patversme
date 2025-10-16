<?php
header('Content-Type: application/json; charset=UTF-8');

require_once __DIR__ . '/vendor/autoload.php';
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->safeLoad();

$conn = new mysqli(
  $_ENV['DB_HOST'] ?? 'localhost',
  $_ENV['DB_USER'] ?? 'root',
  $_ENV['DB_PASS'] ?? '',
  $_ENV['DB_NAME'] ?? 'dzivnieku_patversme',
  $_ENV['DB_PORT'] ?? 3306
);
if ($conn->connect_error) {
  http_response_code(500);
  echo json_encode(["error" => "Datubāzes savienojuma kļūda"]);
  exit;
}

$date = $_GET['date'] ?? '';
if (!$date) {
  echo json_encode([]);
  exit;
}

// ✅ Aizņemti laiki VISIEM dzīvniekiem konkrētajā datumā
$stmt = $conn->prepare("
  SELECT laiks
  FROM adopcijas_pieteikumi
  WHERE datums = ?
    AND statuss IN ('gaida apstiprinajumu', 'apstiprinats')
");
$stmt->bind_param("s", $date);
$stmt->execute();
$res = $stmt->get_result();

$taken = [];
while ($row = $res->fetch_assoc()) {
  $time = substr($row['laiks'], 0, 5);
  if (!in_array($time, $taken)) {
    $taken[] = $time;
  }
}

$stmt->close();
$conn->close();

echo json_encode($taken, JSON_UNESCAPED_UNICODE);
