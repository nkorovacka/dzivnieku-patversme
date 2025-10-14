<?php
// Параметры подключения к базе данных Railway
$host = 'shinkansen.proxy.rlwy.net';
$port = 36226;
$username = 'root';
$password = 'oYVsYmRdokiELhESSYyNUiTfHwwpqEfE';
$database = 'railway';

// Создание подключения с указанием порта
$conn = new mysqli($host, $username, $password, $database, $port);

// Проверка подключения
if ($conn->connect_error) {
    // Показываем детальную информацию об ошибке
    die("❌ Neizdevās pieslēgties datubāzei!<br>" . 
        "Kļūda: " . $conn->connect_error . "<br>" .
        "Kļūdas kods: " . $conn->connect_errno);
}

// Установка кодировки
$conn->set_charset("utf8mb4");

// Для отладки (уберите после проверки):
// echo "✅ Datubāze savienota veiksmīgi ar Railway!";
?>