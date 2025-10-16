<?php
session_start();
header('Content-Type: application/json; charset=UTF-8');
require_once __DIR__ . '/../vendor/autoload.php';
error_reporting(E_ALL);
ini_set('display_errors', 1);

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
  echo json_encode(['ok' => false, 'error' => 'Datubāzes kļūda']);
  exit;
}

$user_id = intval($_SESSION['user_id']);
$app_id = intval($_POST['id'] ?? 0);

if (!$app_id) {
  echo json_encode(['ok' => false, 'error' => 'Nav norādīts pieteikums']);
  exit;
}

// atrodam pet_id
$stmt = $conn->prepare("SELECT pet_id FROM adopcijas_pieteikumi WHERE id = ? AND lietotaja_id = ?");
$stmt->bind_param('ii', $app_id, $user_id);
$stmt->execute();
$res = $stmt->get_result();
$app = $res->fetch_assoc();
$stmt->close();

if (!$app) {
  echo json_encode(['ok' => false, 'error' => 'Pieteikums nav atrasts']);
  exit;
}

$pet_id = intval($app['pet_id']);

// dzēšam pieteikumu
$stmt = $conn->prepare("DELETE FROM adopcijas_pieteikumi WHERE id = ? AND lietotaja_id = ?");
$stmt->bind_param('ii', $app_id, $user_id);
$stmt->execute();
$stmt->close();

// pārbaudām, vai vairs nav pieteikumu šim dzīvniekam
$q = $conn->prepare("SELECT COUNT(*) AS total FROM adopcijas_pieteikumi WHERE pet_id = ?");
$q->bind_param('i', $pet_id);
$q->execute();
$count = $q->get_result()->fetch_assoc()['total'] ?? 0;
$q->close();

if ($count == 0) {
  $u = $conn->prepare("UPDATE dzivnieki SET statuss = 'pieejams' WHERE id = ?");
  $u->bind_param('i', $pet_id);
  $u->execute();
  $u->close();
}

echo json_encode(['ok' => true]);
