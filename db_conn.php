<?php
// Optional autoload/.env
if (file_exists(__DIR__ . '/vendor/autoload.php')) {
    require_once __DIR__ . '/vendor/autoload.php';
    if (class_exists('Dotenv\\Dotenv')) {
        $dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
        $dotenv->safeLoad();
    }
}

$servername = $_ENV['DB_HOST'] ?? getenv('DB_HOST') ?: '127.0.0.1';
$username = $_ENV['DB_USER'] ?? getenv('DB_USER') ?: 'root';
$password = $_ENV['DB_PASS'] ?? getenv('DB_PASS') ?: '';
$dbname = $_ENV['DB_NAME'] ?? getenv('DB_NAME') ?: 'dzivnieku_patversme';
$port = (int)($_ENV['DB_PORT'] ?? getenv('DB_PORT') ?: 3306);

if (!class_exists('mysqli')) {
    http_response_code(500);
    die("❌ PHP mysqli extension nav pieejams. Lūdzu, uzstādi php-mysql (mysqli/pdo_mysql).\n" .
        "Ubuntu: sudo apt-get install -y php" . PHP_MAJOR_VERSION . "." . PHP_MINOR_VERSION . "-mysql\n");
}

$conn = new mysqli($servername, $username, $password, $dbname, $port);
if ($conn->connect_error) {
    die("❌ Savienojuma kļūda: " . $conn->connect_error);
}
?>
