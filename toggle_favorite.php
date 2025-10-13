<?php
session_start();
require_once 'db.php';

header('Content-Type: application/json');

// Проверяем авторизацию
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Пользователь не авторизован']);
    exit;
}

$user_id = $_SESSION['user_id'];
$data = json_decode(file_get_contents('php://input'), true);
$pet_id = intval($data['pet_id'] ?? 0);

if (!$pet_id) {
    echo json_encode(['success' => false, 'message' => 'Некорректный ID животного']);
    exit;
}

// Проверяем, есть ли уже запись в избранных
$query = "SELECT id FROM favorites WHERE user_id = ? AND pet_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("ii", $user_id, $pet_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    // Если уже в избранных — удаляем
    $delete = $conn->prepare("DELETE FROM favorites WHERE user_id = ? AND pet_id = ?");
    $delete->bind_param("ii", $user_id, $pet_id);
    $delete->execute();
    echo json_encode(['success' => true, 'message' => 'Удалено из избранных']);
} else {
    // Если нет — добавляем
    $insert = $conn->prepare("INSERT INTO favorites (user_id, pet_id) VALUES (?, ?)");
    $insert->bind_param("ii", $user_id, $pet_id);
    $insert->execute();
    echo json_encode(['success' => true, 'message' => 'Добавлено в избранные']);
}
