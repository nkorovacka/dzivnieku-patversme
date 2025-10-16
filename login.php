<?php
session_start();
require_once __DIR__ . '/vendor/autoload.php';

// Ielādē .env konfigurāciju
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->safeLoad();

// Datubāzes pieslēgšanās informācija
$servername = $_ENV['DB_HOST'] ?? 'localhost';
$username   = $_ENV['DB_USER'] ?? 'root';
$password   = $_ENV['DB_PASS'] ?? '';
$dbname     = $_ENV['DB_NAME'] ?? 'dzivnieku_patversme';
$port       = $_ENV['DB_PORT'] ?? 3306;

// ✅ Izveido drošu savienojumu ar MySQLi (jo pārējās lapas izmanto mysqli)
$conn = new mysqli($servername, $username, $password, $dbname, $port);
if ($conn->connect_error) {
    die("Savienojuma kļūda: " . $conn->connect_error);
}

// ✅ Ja lietotājs jau ir ielogojies
if (isset($_SESSION["epasts"])) {
    if (!empty($_SESSION["admin"]) && $_SESSION["admin"] == 1) {
        header("Location: admin.php");
    } else {
        header("Location: index.php");
    }
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

    // ✅ Pārbauda, vai lietotājs eksistē
    $stmt = $conn->prepare("SELECT * FROM lietotaji WHERE epasts = ?");
    $stmt->bind_param("s", $epasts);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result ? $result->fetch_assoc() : null;

    if (!$user) {
        echo "<script>alert('❌ Lietotājs ar šo e-pastu nav reģistrēts!'); window.location.href='login.html';</script>";
        exit;
    }

    // ✅ Pārbauda paroli
    if (!password_verify($parole, $user["parole"])) {
        echo "<script>alert('❌ Nepareiza parole!'); window.location.href='login.html';</script>";
        exit;
    }

    // ✅ Saglabā sesijā lietotāja datus
    $_SESSION["user_id"] = (int)$user["id"];
    $_SESSION["lietotajvards"] = $user["lietotajvards"];
    $_SESSION["epasts"] = $user["epasts"];
    $_SESSION["admin"] = (int)$user["admin"];

    // ✅ Novirza pēc lomas
    if ($_SESSION["admin"] === 1) {
        header("Location: admin.php");
    } else {
        header("Location: index.php");
    }
    exit;
}
?>

<!DOCTYPE html>
<html lang="lv">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pieteikšanās — SirdsPaws</title>
    <link rel="stylesheet" href="index.css">
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #667eea, #764ba2);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem;
        }

        .login-box {
            background: #fff;
            border-radius: 16px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.15);
            width: 100%;
            max-width: 420px;
            padding: 2.5rem;
        }

        h1 {
            text-align: center;
            margin-bottom: 1.5rem;
            color: #4f46e5;
        }

        label {
            font-weight: 600;
            color: #374151;
            display: block;
            margin-bottom: 6px;
            margin-top: 16px;
        }

        input {
            width: 100%;
            padding: 12px;
            border-radius: 10px;
            border: 1.8px solid #e5e7eb;
            font-size: 1rem;
            transition: all .2s;
        }

        input:focus {
            border-color: #6366f1;
            box-shadow: 0 0 0 3px rgba(99,102,241,.2);
            outline: none;
        }

        button {
            width: 100%;
            margin-top: 24px;
            padding: 12px;
            background: linear-gradient(135deg, #6366f1, #8b5cf6);
            color: #fff;
            border: none;
            border-radius: 10px;
            font-weight: 700;
            cursor: pointer;
            transition: .2s;
        }

        button:hover {
            opacity: .9;
transform: scale(1.02);
        }

        .links {
            text-align: center;
            margin-top: 20px;
            font-size: 0.95rem;
            color: #6b7280;
        }

        .links a {
            color: #6366f1;
            text-decoration: none;
            font-weight: 600;
        }
    </style>
</head>
<body>
    <div class="login-box">
        <h1>🐾 Pieteikšanās</h1>

        <form method="POST" action="">
            <label for="epasts">E-pasts</label>
            <input type="email" id="epasts" name="epasts" placeholder="ievadi savu e-pastu" required>

            <label for="parole">Parole</label>
            <input type="password" id="parole" name="parole" placeholder="ievadi paroli" required>

            <button type="submit">Pieteikties</button>
        </form>

        <div class="links">
            <p>Nav konta? <a href="register.html">Reģistrējies šeit</a></p>
            <p><a href="index.php">← Atpakaļ uz sākumlapu</a></p>
        </div>
    </div>
</body>
</html>