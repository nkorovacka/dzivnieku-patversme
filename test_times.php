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
  echo json_encode(["error" => "DB connection failed"]);
  exit;
}

$date = $_GET['date'] ?? '';
if (!$date) {
  echo json_encode([]);
  exit;
}

// ❗️Ja kāds jebkurā dzīvniekam tajā datumā ir pieteicies (gaida vai apstiprināts),
// tie laiki tiek uzskatīti par aizņemtiem visiem.
$stmt = $conn->prepare("
  SELECT laiks
  FROM adopcijas_pieteikumi
  WHERE datums = ?
    AND statuss IN ('gaida apstiprinājumu', 'apstiprinats')
");
$stmt->bind_param("s", $date);
$stmt->execute();
$res = $stmt->get_result();

$taken = [];
while ($row = $res->fetch_assoc()) {
  $taken[] = substr($row['laiks'], 0, 5); // tikai HH:MM
}

echo json_encode($taken, JSON_UNESCAPED_UNICODE);
