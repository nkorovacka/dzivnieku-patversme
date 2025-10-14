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

$bonusuSistema = new BonusuSistema($conn);
$rezultats = $bonusuSistema->sanemt_dienas_bonusu($user['id']);

if ($rezultats) {
    echo json_encode([
        'success' => true, 
        'punkti' => BonusuSistema::BONUSS_DIENAS,
        'message' => 'Bonuss veiksmīgi saņemts! +' . BonusuSistema::BONUSS_DIENAS . ' punkti'
    ]);
} else {
    echo json_encode([
        'success' => false, 
        'message' => 'Jūs jau esat saņēmis bonusu šodien!'
    ]);
}
?>