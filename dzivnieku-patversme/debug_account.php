<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>🔍 Диагностика account.php</h2>";

echo "Шаг 1: Запуск сессии...<br>";
session_start();

if (!isset($_SESSION['user_id'])) {
    die("❌ Не авторизован. <a href='login.php'>Войдите</a>");
}
echo "✅ User ID: " . $_SESSION['user_id'] . "<br><br>";

echo "Шаг 2: Подключение к БД...<br>";
require_once 'db_conn.php';
echo "✅ Подключено<br><br>";

echo "Шаг 3: Получение данных пользователя...<br>";
$stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
if (!$stmt) {
    die("❌ Ошибка подготовки запроса: " . $conn->error);
}

$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$stmt->close();

if (!$user) {
    die("❌ Пользователь не найден в БД");
}

echo "✅ Пользователь найден!<br><br>";
echo "<pre>";
print_r($user);
echo "</pre>";

echo "<h3>Проверка полей таблицы:</h3>";
$result = $conn->query("DESCRIBE users");
echo "<table border='1' cellpadding='5'>";
echo "<tr><th>Поле</th><th>Тип</th><th>Null</th></tr>";
while ($row = $result->fetch_assoc()) {
    echo "<tr>";
    echo "<td>" . $row['Field'] . "</td>";
    echo "<td>" . $row['Type'] . "</td>";
    echo "<td>" . $row['Null'] . "</td>";
    echo "</tr>";
}
echo "</table>";

echo "<br><h3>✅ Все проверки пройдены!</h3>";
echo "<a href='account.php'>Открыть account.php</a>";
?>
