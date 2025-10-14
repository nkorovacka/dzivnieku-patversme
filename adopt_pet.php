<?php
session_start();
require_once __DIR__ . '/vendor/autoload.php';

// Ielādē .env failu
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->safeLoad();

// Savienojums ar datubāzi
$conn = new mysqli(
    $_ENV['DB_HOST'] ?? 'localhost',
    $_ENV['DB_USER'] ?? 'root',
    $_ENV['DB_PASS'] ?? '',
    $_ENV['DB_NAME'] ?? 'dzivnieku_patversme',
    $_ENV['DB_PORT'] ?? 3306
);

if ($conn->connect_error) {
    die("❌ Datubāzes savienojuma kļūda: " . $conn->connect_error);
}

// Pārbauda, vai lietotājs ir pieteicies
if (!isset($_SESSION['user_id'])) {
    echo "<script>alert('Lūdzu, pieslēdzies, lai iesniegtu adopcijas pieteikumu.'); window.location.href='login.html';</script>";
    exit;
}

// Pārbauda, vai pet_id ir nosūtīts
if (!isset($_POST['pet_id']) || empty($_POST['pet_id'])) {
    echo "<script>alert('Nav norādīts dzīvnieks.'); window.history.back();</script>";
    exit;
}

$pet_id = intval($_POST['pet_id']);
$user_id = intval($_SESSION['user_id']);

// Pārbauda, vai dzīvnieks eksistē
$checkPet = $conn->prepare("SELECT id, statuss FROM dzivnieki WHERE id = ?");
$checkPet->bind_param("i", $pet_id);
$checkPet->execute();
$petResult = $checkPet->get_result();

if ($petResult->num_rows === 0) {
    echo "<script>alert('Šāds dzīvnieks nav atrasts.'); window.history.back();</script>";
    exit;
}

$pet = $petResult->fetch_assoc();
if ($pet['statuss'] !== 'pieejams') {
    echo "<script>alert('Šis dzīvnieks vairs nav pieejams adopcijai.'); window.location.href='pets.php';</script>";
    exit;
}

// Pārbauda, vai lietotājam jau ir pieteikums šim dzīvniekam
$checkApp = $conn->prepare("SELECT id FROM pieteikumi WHERE lietotaja_id = ? AND dzivnieka_id = ?");
$checkApp->bind_param("ii", $user_id, $pet_id);
$checkApp->execute();
$appResult = $checkApp->get_result();

if ($appResult->num_rows > 0) {
    echo "<script>alert('Tu jau esi iesniedzis pieteikumu šim dzīvniekam.'); window.location.href='pets.php';</script>";
    exit;
}

// Izveido jaunu pieteikumu
$pieteikuma_veids = 'adopcija';
$pieteikuma_teksts = 'Automātiski ģenerēts adopcijas pieteikums no SirdsPaws sistēmas.';

$stmt = $conn->prepare("
    INSERT INTO pieteikumi (dzivnieka_id, lietotaja_id, pieteikuma_veids, pieteikuma_teksts, statuss)
    VALUES (?, ?, ?, ?, 'gaida_apstiprinajumu')
");
$stmt->bind_param("iiss", $pet_id, $user_id, $pieteikuma_veids, $pieteikuma_teksts);
$ok = $stmt->execute();

if ($ok) {
    echo "<script>alert('✅ Pieteikums veiksmīgi iesniegts! Administrators ar tevi sazināsies tuvākajā laikā.'); window.location.href='pets.php';</script>";
} else {
    echo "<script>alert('❌ Radās kļūda, iesniedzot pieteikumu.'); window.history.back();</script>";
}

$conn->close();
exit;
?>
