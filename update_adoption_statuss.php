<?php
session_start();

if (!isset($_SESSION['epasts']) || $_SESSION['admin'] != 1) {
    header("Location: login.html");
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

if ($conn->connect_error) {
    die("Savienojuma kļūda: " . $conn->connect_error);
}

$id = intval($_POST['id'] ?? 0);
$status = $_POST['status'] ?? '';

if (!$id || !in_array($status, ['apstiprinats', 'noraidits'])) {
    echo "<script>alert('Nederīgi dati.'); window.location.href='admin_adoptions.php';</script>";
    exit;
}

// 🔹 Atjauno pieteikuma statusu
$stmt = $conn->prepare("UPDATE adopcijas_pieteikumi SET statuss = ? WHERE id = ?");
$stmt->bind_param("si", $status, $id);
$stmt->execute();

// 🔹 Atrodi pet_id šim pieteikumam
$petStmt = $conn->prepare("SELECT pet_id FROM adopcijas_pieteikumi WHERE id = ?");
$petStmt->bind_param("i", $id);
$petStmt->execute();
$res = $petStmt->get_result();
$pet = $res->fetch_assoc();
$pet_id = $pet['pet_id'] ?? 0;

// 🔹 Atjauno dzīvnieka statusu atkarībā no pieteikuma
if ($pet_id) {
    if ($status === 'apstiprinats') {
        $updatePet = $conn->prepare("UPDATE dzivnieki SET statuss = 'adoptets' WHERE id = ?");
    } else {
        $updatePet = $conn->prepare("UPDATE dzivnieki SET statuss = 'pieejams' WHERE id = ?");
    }
    $updatePet->bind_param("i", $pet_id);
    $updatePet->execute();
    $updatePet->close();
}

$stmt->close();
$petStmt->close();
$conn->close();

echo "<script>
alert('✅ Pieteikuma statuss veiksmīgi atjaunots!');
window.location.href='admin_adoptions.php';
</script>";
exit;
?>
