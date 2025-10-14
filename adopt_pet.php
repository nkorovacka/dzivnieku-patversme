<?php
session_start();
require_once __DIR__ . '/vendor/autoload.php';

if (!isset($_SESSION['user_id'])) {
  echo "<script>alert('Lūdzu pieslēdzies, lai adoptētu dzīvnieku!'); window.location.href='login.html';</script>";
  exit;
}

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->safeLoad();

$conn = new mysqli(
  $_ENV['DB_HOST'], $_ENV['DB_USER'], $_ENV['DB_PASS'],
  $_ENV['DB_NAME'], $_ENV['DB_PORT']
);

if ($conn->connect_error) {
  die("❌ Neizdevās savienoties ar datubāzi: " . $conn->connect_error);
}

$pet_id = intval($_POST['pet_id'] ?? 0);
$arrival_date = $_POST['arrival_date'] ?? '';
$arrival_time = $_POST['arrival_time'] ?? '';
$notes = trim($_POST['notes'] ?? '');
$user_id = intval($_SESSION['user_id']);

if (!$pet_id || !$arrival_date || !$arrival_time || !$notes) {
  echo "<script>alert('Lūdzu aizpildi visus laukus!'); window.history.back();</script>";
  exit;
}

// Saglabā adopcijas pieteikumu
$stmt = $conn->prepare("
  INSERT INTO adopcijas_pieteikumi (lietotaja_id, pet_id, datums, laiks, piezimes, statuss)
  VALUES (?, ?, ?, ?, ?, 'gaida apstiprinājumu')
");
$stmt->bind_param("iisss", $user_id, $pet_id, $arrival_date, $arrival_time, $notes);
$stmt->execute();

echo "<script>alert('🐾 Tavs adopcijas pieteikums ir nosūtīts! Patversmes darbinieki ar tevi sazināsies.'); window.location.href='pets.php';</script>";
exit;
?>
