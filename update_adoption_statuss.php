<?php
session_start();

if (!isset($_SESSION['admin']) || $_SESSION['admin'] != 1) {
    header("Location: index.php");
    exit;
}

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
if ($conn->connect_error) die("Savienojuma kļūda: " . $conn->connect_error);

$id = intval($_POST['id'] ?? 0);
$status = $_POST['status'] ?? '';

$valid_status = ['gaida apstiprinājumu', 'apstiprinats', 'noraidits'];
if (!$id || !in_array($status, $valid_status)) {
    die("❌ Nederīgi dati!");
}

// ✅ Atjauno statusu DB
$stmt = $conn->prepare("UPDATE adopcijas_pieteikumi SET statuss = ? WHERE id = ?");
$stmt->bind_param("si", $status, $id);
$stmt->execute();

// Ja apstiprina — atzīmē arī dzīvnieku kā adoptētu
if ($status === 'apstiprinats') {
    $conn->query("UPDATE dzivnieki d
                  JOIN adopcijas_pieteikumi a ON d.id = a.pet_id
                  SET d.statuss = 'adoptēts'
                  WHERE a.id = $id");
}

// Ja atgriež vai noraida — dzīvnieks atkal kļūst pieejams
if ($status !== 'apstiprinats') {
    $conn->query("UPDATE dzivnieki d
                  JOIN adopcijas_pieteikumi a ON d.id = a.pet_id
                  SET d.statuss = 'pieejams'
                  WHERE a.id = $id");
}

header("Location: admin_adoptions.php");
exit;
?>
