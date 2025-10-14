<?php
session_start();
require_once 'config.php';
require_once 'bonusu_sistema.php';

header('Content-Type: application/json');

if (!isset($_SESSION["lietotajvards"])) {
    echo json_encode(['success' => false, 'message' => 'Jūs neesat autorizēts']);
    exit();
}

$conn = getConnection();
$epasts = $_SESSION["epasts"];

// Получаем ID пользователя
$stmt = $conn->prepare("SELECT id FROM lietotaji WHERE epasts = ?");
$stmt->execute([$epasts]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    echo json_encode(['success' => false, 'message' => 'Lietotājs nav atrasts']);
    exit();
}

$data = json_decode(file_get_contents('php://input'), true);
$privilegija_id = $data['privilegija_id'] ?? null;

if (!$privilegija_id) {
    echo json_encode(['success' => false, 'message' => 'Nav norādīta privilēģija']);
    exit();
}

$bonusuSistema = new BonusuSistema($conn);
$rezultats = $bonusuSistema->nopirktPrivilegiju($user['id'], $privilegija_id);

echo json_encode($rezultats);
?><?php
session_start();
require_once 'db_conn.php';
require_once 'bonusu_sistema.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Jūs neesat autorizēts']);
    exit();
}

$data = json_decode(file_get_contents('php://input'), true);
$privilegija_id = $data['privilegija_id'] ?? null;

if (!$privilegija_id) {
    echo json_encode(['success' => false, 'message' => 'Nav norādīta privilēģija']);
    exit();
}

$bonusuSistema = new BonusuSistema($conn);
$rezultats = $bonusuSistema->nopirktPrivilegiju($_SESSION['user_id'], $privilegija_id);

echo json_encode($rezultats);
?>