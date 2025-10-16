<?php
require_once __DIR__ . '/vendor/autoload.php';
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

$servername = $_ENV['DB_HOST'];
$username = $_ENV['DB_USER'];
$password = $_ENV['DB_PASS'];
$dbname = $_ENV['DB_NAME'];
$port = $_ENV['DB_PORT'];

try {
    $dsn = "mysql:host={$servername};port={$port};dbname={$dbname};charset=utf8mb4";
    
    $pdo = new PDO($dsn, $username, $password);
    $conn = $pdo; // для совместимости со старым кодом
    
    // Устанавливаем режим обработки ошибок
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    
    // echo "✅ Savienojums ar datubāzi izdevās!"; // закомментируйте это
    
} catch (PDOException $e) {
    die("❌ Savienojuma kļūda: " . $e->getMessage());
}