<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "Шаг 1: Запуск сессии...<br>";
session_start();

echo "Шаг 2: Подключение к БД...<br>";
require_once 'db_conn.php';

echo "Шаг 3: Проверка авторизации...<br>";
if (!isset($_SESSION['user_id'])) {
    die("❌ Пользователь не авторизован. <a href='login.php'>Войдите</a>");
}

echo "✅ User ID: " . $_SESSION['user_id'] . "<br>";

echo "Шаг 4: Получение данных пользователя...<br>";
$stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$stmt->close();

if ($user) {
    echo "✅ Данные пользователя получены!<br>";
    echo "<pre>";
    print_r($user);
    echo "</pre>";
} else {
    echo "❌ Пользователь не найден в БД";
}
?>
