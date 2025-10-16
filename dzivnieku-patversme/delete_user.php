<?php
session_start();

// Tikai admin drīkst dzēst
if (!isset($_SESSION['admin']) || $_SESSION['admin'] != 1) {
    header("Location: index.php");
    exit;
}

require_once __DIR__ . '/vendor/autoload.php';
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->safeLoad();

$servername = $_ENV['DB_HOST'] ?? 'localhost';
$username   = $_ENV['DB_USER'] ?? 'root';
$password   = $_ENV['DB_PASS'] ?? '';
$dbname     = $_ENV['DB_NAME'] ?? 'dzivnieku_patversme';
$port       = $_ENV['DB_PORT'] ?? 3306;

$conn = new mysqli($servername, $username, $password, $dbname, (int)$port);
if ($conn->connect_error) {
    die("Savienojuma kļūda: " . $conn->connect_error);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id'])) {
    $id = (int)$_POST['id'];

    // Drošības nolūkos neļaujam dzēst pašam sevi
    if ($id === (int)$_SESSION['user_id']) {
        echo "<script>alert('❌ Nevar dzēst sevi!'); window.location.href='admin.php';</script>";
        exit;
    }

    $stmt = $conn->prepare("DELETE FROM lietotaji WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();

    echo "<script>alert('✅ Lietotājs izdzēsts veiksmīgi!'); window.location.href='admin.php';</script>";
    exit;
}

header("Location: admin.php");
exit;
?>
