<?php
session_start();
require_once __DIR__ . '/vendor/autoload.php';

// 🧠 Ja lietotājs nav ielogojies
if (!isset($_SESSION['user_id'])) {
  echo "<script>alert('Lūdzu pieslēdzies, lai adoptētu dzīvnieku!'); window.location.href='login.html';</script>";
  exit;
}

// 🔧 Ielādē .env
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->safeLoad();

// 🔗 Pieslēgšanās DB
$conn = new mysqli(
  $_ENV['DB_HOST'] ?? 'localhost',
  $_ENV['DB_USER'] ?? 'root',
  $_ENV['DB_PASS'] ?? '',
  $_ENV['DB_NAME'] ?? 'dzivnieku_patversme',
  $_ENV['DB_PORT'] ?? 3306
);
if ($conn->connect_error) {
  die("❌ Neizdevās savienoties ar datubāzi: " . $conn->connect_error);
}
$conn->autocommit(true);

// ==========================
// 🧩 IEGŪST FORMAS DATUS
// ==========================
$pet_id = intval($_POST['pet_id'] ?? 0);
$arrival_date = $_POST['arrival_date'] ?? '';
$arrival_time = $_POST['arrival_time'] ?? '';
$notes = trim($_POST['notes'] ?? '');
$user_id = intval($_SESSION['user_id']);

// 🔍 Debug log
error_log("=== adopt_pet.php START ===");
error_log("POST: " . print_r($_POST, true));
error_log("SESSION user_id: " . $user_id);
error_log("pet_id = " . $pet_id);
error_log("arrival_date = " . $arrival_date . ", arrival_time = " . $arrival_time);

// ==========================
// ⚠️ PĀRBAUDA, VAI DATI OK
// ==========================
if (!$pet_id || !$arrival_date || !$arrival_time || !$notes) {
  error_log("❌ Kļūda: nepilni dati.");
  echo "<script>alert('Lūdzu aizpildi visus laukus!'); window.history.back();</script>";
  exit;
}

// ==========================
// 🐾 IEVADA ADOPCIJAS PIETEIKUMU
// ==========================
$stmt = $conn->prepare("
  INSERT INTO adopcijas_pieteikumi (lietotaja_id, pet_id, datums, laiks, piezimes, statuss)
  VALUES (?, ?, ?, ?, ?, 'gaida apstiprinājumu')
");
$stmt->bind_param("iisss", $user_id, $pet_id, $arrival_date, $arrival_time, $notes);

if (!$stmt->execute()) {
  error_log("❌ Kļūda saglabājot pieteikumu: " . $stmt->error);
  echo "<script>alert('Kļūda, saglabājot pieteikumu!'); window.history.back();</script>";
  $stmt->close();
  $conn->close();
  exit;
}
$stmt->close();
error_log("✅ Adopcijas pieteikums saglabāts pet_id=$pet_id");

// ==========================
// 🐕 ATJAUNO DZĪVNIEKA STATUSU
// ==========================
$update = $conn->prepare("UPDATE dzivnieki SET statuss = 'rezervets' WHERE id = ?");
$update->bind_param("i", $pet_id);
$update->execute();

// 🔍 Diagnostika
if ($update->affected_rows > 0) {
  error_log("✅ DZIVNIEKS ATJAUNOTS: ID=$pet_id (rezervets)");
  echo "<script>console.log('✅ Dzīvnieka statuss veiksmīgi mainīts uz rezervets (ID: $pet_id)');</script>";
} else {
  error_log("⚠️ STATUSS NETIKA MAINĪTS! ID=$pet_id | ERROR=" . $update->error);
  echo "<script>console.warn('⚠️ Statuss netika mainīts (ID: $pet_id). Skaties PHP error log.');</script>";
}
$update->close();

$conn->close();

error_log("=== adopt_pet.php END ===");

echo "<script>
  alert('🐾 Tavs adopcijas pieteikums ir nosūtīts! Dzīvnieks tagad ir rezervēts (ja viss noritēja veiksmīgi).');
  window.location.href='pets.php';
</script>";
exit;
?>
