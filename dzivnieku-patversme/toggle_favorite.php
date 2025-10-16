<?php
session_start();
require_once __DIR__ . '/vendor/autoload.php';

// Ielādē .env failu
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->safeLoad();

// Pārbauda, vai lietotājs ir pieteicies
if (!isset($_SESSION['user_id'])) {
    echo "<script>alert('Lai pievienotu favorītiem, lūdzu pieslēdzies.'); window.location.href='login.html';</script>";
    exit;
}

// Savienojums ar datubāzi
$conn = new mysqli(
    $_ENV['DB_HOST'] ?? 'localhost',
    $_ENV['DB_USER'] ?? 'root',
    $_ENV['DB_PASS'] ?? '',
    $_ENV['DB_NAME'] ?? 'dzivnieku_patversme',
    $_ENV['DB_PORT'] ?? 3306
);

if ($conn->connect_error) {
    die("❌ Neizdevās izveidot savienojumu ar datubāzi: " . $conn->connect_error);
}

// Pārbauda, vai saņemts pet_id
if (!isset($_POST['pet_id'])) {
    echo "<script>alert('Kļūda: nav norādīts dzīvnieks.'); window.history.back();</script>";
    exit;
}

$pet_id = intval($_POST['pet_id']);
$user_id = intval($_SESSION['user_id']);

// Pārbauda, vai šis dzīvnieks jau ir favorītos
$check = $conn->prepare("SELECT id FROM favorites WHERE user_id = ? AND pet_id = ?");
$check->bind_param("ii", $user_id, $pet_id);
$check->execute();
$result = $check->get_result();

if ($result->num_rows > 0) {
    // Ja jau favorītos — noņem
    $del = $conn->prepare("DELETE FROM favorites WHERE user_id = ? AND pet_id = ?");
    $del->bind_param("ii", $user_id, $pet_id);
    $del->execute();
    $message = "Dzīvnieks noņemts no favorītiem.";
} else {
    // Ja nav — pievieno
    $add = $conn->prepare("INSERT INTO favorites (user_id, pet_id) VALUES (?, ?)");
    $add->bind_param("ii", $user_id, $pet_id);
    $add->execute();
    $message = "Dzīvnieks pievienots favorītiem!";
}

$conn->close();

// Pāradresē atpakaļ uz pets.php ar ziņu
echo "<script>alert('$message'); window.location.href='pets.php';</script>";
exit;
?>
