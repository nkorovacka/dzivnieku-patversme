<?php
// ==============================
// ✅ LOGIN.PHP — SirdsPaws versija
// ==============================
ini_set('session.cookie_path', '/');
ini_set('session.cookie_lifetime', 86400);
ini_set('session.gc_maxlifetime', 86400);
ini_set('session.cookie_secure', false); // true, ja izmanto HTTPS
ini_set('session.cookie_httponly', true);

session_start();
require_once __DIR__ . '/vendor/autoload.php';

// Ielādē .env
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->safeLoad();

// Pieslēgums DB
try {
    $conn = new PDO(
        "mysql:host=" . ($_ENV['DB_HOST'] ?? 'localhost') . 
        ";port=" . ($_ENV['DB_PORT'] ?? 3306) . 
        ";dbname=" . ($_ENV['DB_NAME'] ?? 'dzivnieku_patversme') . 
        ";charset=utf8",
        $_ENV['DB_USER'] ?? 'root',
        $_ENV['DB_PASS'] ?? ''
    );
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("<script>alert('❌ Neizdevās pieslēgties datubāzei!'); window.location='login.html';</script>");
}

// ✅ Ja lietotājs jau ir ielogojies
if (isset($_SESSION["user_id"])) {
    header("Location: " . ($_SESSION["admin"] ? "admin.php" : "index.php"));
    exit;
}

// ✅ Ja forma iesniegta
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $epasts = trim($_POST["epasts"] ?? '');
    $parole = trim($_POST["parole"] ?? '');

    if (empty($epasts) || empty($parole)) {
        echo "<script>alert('❌ Lūdzu aizpildi visus laukus!'); window.history.back();</script>";
        exit;
    }

    $stmt = $conn->prepare("SELECT * FROM lietotaji WHERE epasts = ?");
    $stmt->execute([$epasts]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        echo "<script>alert('❌ Lietotājs ar šo e-pastu nav reģistrēts!'); window.location='login.html';</script>";
        exit;
    }

    if (!password_verify($parole, $user["parole"])) {
        echo "<script>alert('❌ Nepareiza parole!'); window.location='login.html';</script>";
        exit;
    }

    // ✅ Saglabā sesiju
    $_SESSION["user_id"] = $user["id"];
    $_SESSION["lietotajvards"] = $user["lietotajvards"];
    $_SESSION["epasts"] = $user["epasts"];
    $_SESSION["admin"] = (int)$user["admin"];

    // ✅ Novirzīšana
    header("Location: " . ($_SESSION["admin"] ? "admin.php" : "index.php"));
    exit;
}

// Ja GET pieprasījums → pāriet uz login.html
header("Location: login.html");
exit;
