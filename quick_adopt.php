<?php
session_start();
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
    echo json_encode(['success' => false, 'message' => 'Datubāzes kļūda']);
    exit;
}

// Проверка авторизации
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Lūdzu, pieslēdzies!', 'redirect' => 'login.html']);
    exit;
}

// Проверка pet_id
if (!isset($_POST['pet_id']) || empty($_POST['pet_id'])) {
    echo json_encode(['success' => false, 'message' => 'Nav norādīts dzīvnieks']);
    exit;
}

$pet_id = intval($_POST['pet_id']);
$user_id = intval($_SESSION['user_id']);

// Проверка существования животного
$checkPet = $conn->prepare("SELECT id, statuss, vards FROM dzivnieki WHERE id = ?");
$checkPet->bind_param("i", $pet_id);
$checkPet->execute();
$petResult = $checkPet->get_result();

if ($petResult->num_rows === 0) {
    echo json_encode(['success' => false, 'message' => 'Dzīvnieks nav atrasts']);
    exit;
}

$pet = $petResult->fetch_assoc();
if ($pet['statuss'] !== 'pieejams') {
    echo json_encode(['success' => false, 'message' => 'Šis dzīvnieks vairs nav pieejams']);
    exit;
}

// Проверка дубликата заявки
$checkApp = $conn->prepare("SELECT id FROM pieteikumi WHERE lietotaja_id = ? AND dzivnieka_id = ?");
$checkApp->bind_param("ii", $user_id, $pet_id);
$checkApp->execute();
$appResult = $checkApp->get_result();

if ($appResult->num_rows > 0) {
    echo json_encode(['success' => false, 'message' => 'Tu jau esi iesniedzis pieteikumu šim dzīvniekam']);
    exit;
}

// Создание заявки
$pieteikuma_veids = 'adopcija';
$pieteikuma_teksts = 'Ātrā adopcijas pieteikuma no ' . date('Y-m-d H:i:s');

$stmt = $conn->prepare("
    INSERT INTO pieteikumi (dzivnieka_id, lietotaja_id, pieteikuma_veids, pieteikuma_teksts, statuss)
    VALUES (?, ?, ?, ?, 'gaida_apstiprinajumu')
");
$stmt->bind_param("iiss", $pet_id, $user_id, $pieteikuma_veids, $pieteikuma_teksts);

if ($stmt->execute()) {
    echo json_encode([
        'success' => true, 
        'message' => '✅ Pieteikums veiksmīgi iesniegts par ' . htmlspecialchars($pet['vards']) . '!'
    ]);
} else {
    echo json_encode(['success' => false, 'message' => 'Radās kļūda']);
}

$conn->close();
?>