<?php
// 1) Composer
$autoload = __DIR__ . '/vendor/autoload.php';
if (!file_exists($autoload)) {
    die('Autoloader not found. Run "composer install" in the project root.');
}
require_once $autoload;

// 2) .env (не обязательно, но удобно)
if (file_exists(__DIR__ . '/.env')) {
    Dotenv\Dotenv::createImmutable(__DIR__)->safeLoad();
}

// 3) PDO подключение (использует .env, но есть дефолты)
$host = $_ENV['DB_HOST'] ?? 'shinkansen.proxy.rlwy.net';
$name = $_ENV['DB_NAME'] ?? 'railway';
$user = $_ENV['DB_USER'] ?? 'root';
$pass = $_ENV['DB_PASS'] ?? 'oYVsYmRdokiELhESSYyNUiTfHwwpqEfE';
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$name;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (Throwable $e) {
    http_response_code(500);
    exit('Database connection failed: ' . $e->getMessage());
}
