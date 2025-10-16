<?php
session_start();
require_once __DIR__ . '/vendor/autoload.php';

// 🧠 Ja lietotājs nav ielogojies
if (!isset($_SESSION['user_id'])) {
  echo "<script>alert('Lūdzu, pieslēdzies, lai adoptētu dzīvnieku!'); window.location.href='login.html';</script>";
  exit;
}

// 🔧 Ielādē .env
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->safeLoad();

// 🔗 Savienojums ar datubāzi
$conn = new mysqli(
  $_ENV['DB_HOST'] ?? 'localhost',
  $_ENV['DB_USER'] ?? 'root',
  $_ENV['DB_PASS'] ?? '',
  $_ENV['DB_NAME'] ?? 'dzivnieku_patversme',
  $_ENV['DB_PORT'] ?? 3306
);
if ($conn->connect_error) {
  die("❌ Datubāzes kļūda: " . $conn->connect_error);
}

// ==========================
// 🧩 Ievades dati
// ==========================
$pet_id = intval($_POST['pet_id'] ?? 0);
$arrival_date = trim($_POST['arrival_date'] ?? '');
$arrival_time = trim($_POST['arrival_time'] ?? '');
$notes = trim($_POST['notes'] ?? '');
$user_id = intval($_SESSION['user_id']);

if (!$pet_id || !$arrival_date || !$arrival_time || !$notes) {
  echo "<script>alert('Lūdzu aizpildi visus laukus!'); window.history.back();</script>";
  exit;
}

// ==========================
// 🐕 Pārbauda dzīvnieku
// ==========================
$stmt = $conn->prepare("SELECT id, statuss FROM dzivnieki WHERE id = ?");
$stmt->bind_param("i", $pet_id);
$stmt->execute();
$pet = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$pet) {
  echo "<script>alert('Dzīvnieks nav atrasts!'); window.location.href='pets.php';</script>";
  exit;
}

if ($pet['statuss'] !== 'pieejams') {
  echo "<script>alert('Šis dzīvnieks šobrīd nav pieejams adopcijai!'); window.location.href='pets.php';</script>";
  exit;
}

// ==========================
// 🚫 Pārbauda, vai lietotājs jau pieteicies
// ==========================
$stmt = $conn->prepare("
  SELECT id FROM adopcijas_pieteikumi 
  WHERE lietotaja_id = ? AND pet_id = ? 
    AND statuss IN ('gaida apstiprinājumu', 'apstiprināts')
");
$stmt->bind_param("ii", $user_id, $pet_id);
$stmt->execute();
$exists = $stmt->get_result()->fetch_assoc();
$stmt->close();

if ($exists) {
  echo "<script>alert('Tu jau esi iesniedzis pieteikumu par šo dzīvnieku!'); window.location.href='applications.php';</script>";
  exit;
}

// ==========================
// 💾 Saglabā pieteikumu ar garumzīmēm
// ==========================
$stmt = $conn->prepare("
  INSERT INTO adopcijas_pieteikumi 
    (lietotaja_id, pet_id, datums, laiks, piezimes, statuss)
  VALUES (?, ?, ?, ?, ?, 'gaida apstiprinājumu')
");
$stmt->bind_param("iisss", $user_id, $pet_id, $arrival_date, $arrival_time, $notes);

if (!$stmt->execute()) {
  error_log("❌ adopt_pet.php kļūda: " . $stmt->error);
  echo "<script>alert('Neizdevās saglabāt pieteikumu.'); window.history.back();</script>";
  $stmt->close();
  $conn->close();
  exit;
}
$stmt->close();

// ==========================
// 🔄 Atjauno dzīvnieka statusu
// ==========================
$stmt = $conn->prepare("UPDATE dzivnieki SET statuss = 'rezervēts' WHERE id = ?");
$stmt->bind_param("i", $pet_id);
$stmt->execute();
$stmt->close();

$conn->close();

// ==========================
// 🎉 Paziņojums un pāradresācija
// ==========================
echo "<script>
  alert('🐾 Tavs adopcijas pieteikums veiksmīgi nosūtīts! Dzīvnieks tagad ir rezervēts.');
  window.location.href='applications.php';
</script>";
exit;
?>
