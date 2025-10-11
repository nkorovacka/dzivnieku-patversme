<?php
session_start();

require_once __DIR__ . '/vendor/autoload.php'; // ielādē Dotenv

// Ielādē .env mainīgos
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->safeLoad(); // safeLoad() neizraisa kļūdu, ja .env nav

// Datubāzes pieslēgšanās informācija no .env
$servername = $_ENV['DB_HOST'] ?? 'localhost';
$username   = $_ENV['DB_USER'] ?? 'root';
$password   = $_ENV['DB_PASS'] ?? '';
$dbname     = $_ENV['DB_NAME'] ?? 'dzivnieku_patversme';
$port       = $_ENV['DB_PORT'] ?? 3306;

// ✅ JA JAU ILOGOJIES — PĀRBAUDA KUR JĀNOVIRZA
if (isset($_SESSION["epasts"])) {
    if (!empty($_SESSION["admin"]) && $_SESSION["admin"] == 1) {
        header("Location: admin.php");
    } else {
        header("Location: index.php");
    }
    exit;
}

// Izveido savienojumu ar datubāzi
$conn = new mysqli($servername, $username, $password, $dbname, $port);
if ($conn->connect_error) {
    die("<script>alert('❌ Savienojuma kļūda ar datubāzi!'); window.location.href='login.html';</script>");
}

// Ja forma tika iesniegta
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $epasts = trim($_POST["epasts"] ?? '');
    $parole = trim($_POST["parole"] ?? '');

    if (empty($epasts) || empty($parole)) {
        echo "<script>alert('❌ Lūdzu aizpildi visus laukus!'); window.history.back();</script>";
        exit;
    }

    // Pārbauda, vai lietotājs eksistē
    $check = $conn->prepare("SELECT * FROM lietotaji WHERE epasts = ?");
    $check->bind_param("s", $epasts);
    $check->execute();
    $result = $check->get_result();

    if ($result->num_rows === 0) {
        echo "<script>alert('❌ Lietotājs ar šo e-pastu nav reģistrēts!'); window.location.href='login.html';</script>";
        exit;
    }

    $user = $result->fetch_assoc();

    // Pārbauda paroli
    if (!password_verify($parole, $user["parole"])) {
        echo "<script>alert('❌ Nepareiza parole!'); window.location.href='login.html';</script>";
        exit;
    }

    // ✅ Saglabā sesijā lietotāja info
    $_SESSION["lietotajvards"] = $user["lietotajvards"];
    $_SESSION["epasts"] = $user["epasts"];
    $_SESSION["admin"] = (int)$user["admin"];

    // ✅ Ja ir admins → uz admin paneli
    if ($_SESSION["admin"] === 1) {
        header("Location: admin.php");
    } else {
        header("Location: index.php");
    }
    exit;
}

$conn->close();

// Ja forma netika iesniegta (GET pieprasījums)
header("Location: login.html");
exit;
?>
