<?php
session_start();
require_once __DIR__ . '/vendor/autoload.php';

if (!isset($_SESSION["epasts"])) {
    header("Location: login.html");
    exit;
}

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

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $old_pass = trim($_POST["old_pass"]);
    $new_pass = trim($_POST["new_pass"]);
    $confirm_pass = trim($_POST["confirm_pass"]);
    $email = $_SESSION["epasts"];

    if (empty($old_pass) || empty($new_pass) || empty($confirm_pass)) {
        echo "<script>alert('❌ Lūdzu aizpildi visus laukus!'); window.history.back();</script>";
        exit;
    }

    if ($new_pass !== $confirm_pass) {
        echo "<script>alert('❌ Jaunā parole nesakrīt ar apstiprinājumu!'); window.history.back();</script>";
        exit;
    }

    // Atrod lietotāju
    $stmt = $conn->prepare("SELECT parole FROM lietotaji WHERE epasts = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();

    if (!$user || !password_verify($old_pass, $user['parole'])) {
        echo "<script>alert('❌ Nepareiza esošā parole!'); window.history.back();</script>";
        exit;
    }

    // Saglabā jauno paroli
    $new_hash = password_hash($new_pass, PASSWORD_DEFAULT);
    $update = $conn->prepare("UPDATE lietotaji SET parole = ? WHERE epasts = ?");
    $update->bind_param("ss", $new_hash, $email);
    $update->execute();

    echo "<script>alert('✅ Parole veiksmīgi nomainīta!'); window.location.href='index.php';</script>";
    exit;
}
?>
<!DOCTYPE html>
<html lang="lv">
<head>
    <meta charset="UTF-8">
    <title>Mainīt paroli</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background: #eef2ff;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
        }
        form {
            background: white;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            width: 350px;
        }
        h2 {
            text-align: center;
            color: #4f46e5;
            margin-bottom: 20px;
        }
        input {
            width: 100%;
            padding: 10px;
            margin: 8px 0;
            border-radius: 8px;
            border: 1px solid #ccc;
        }
        button {
            background: linear-gradient(135deg, #6366f1, #8b5cf6);
            color: white;
            border: none;
            padding: 10px;
            width: 100%;
            border-radius: 8px;
            cursor: pointer;
            font-weight: bold;
        }
        button:hover {
            background: linear-gradient(135deg, #4f46e5, #7c3aed);
        }
    </style>
</head>
<body>
    <form method="POST" action="">
        <h2>Mainīt paroli</h2>
        <input type="password" name="old_pass" placeholder="Pašreizējā parole" required>
        <input type="password" name="new_pass" placeholder="Jaunā parole" required>
        <input type="password" name="confirm_pass" placeholder="Apstiprini jauno paroli" required>
        <button type="submit">Saglabāt</button>
    </form>
</body>
</html>
