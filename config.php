<?php
// Конфигурация базы данных Railway
define('DB_HOST', 'shinkansen.proxy.rlwy.net');
define('DB_PORT', 36226);
define('DB_USER', 'root');
define('DB_PASS', 'oYVsYmRdokiELhESSYyNUiTfHwwpqEfE');
define('DB_NAME', 'railway');

// Функция для подключения к базе данных через PDO
function getConnection() {
    try {
        $dsn = "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";charset=utf8mb4";
        $conn = new PDO($dsn, DB_USER, DB_PASS);
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        return $conn;
    } catch(PDOException $e) {
        die("Kļūda savienojumā: " . $e->getMessage());
    }
}
?>