<?php

// 🔧 Sesijas iestatījumi — lai tā būtu pieejama visās lapās un saglabātos ilgāk
ini_set('session.cookie_path', '/');
ini_set('session.cookie_lifetime', 86400);
ini_set('session.gc_maxlifetime', 86400);
ini_set('session.cookie_secure', false); // true ja izmanto HTTPS
ini_set('session.cookie_httponly', true);

session_start();
require_once 'db_conn.php';

require_once __DIR__ . '/vendor/autoload.php'; // ielādē Dotenv

// Ielādē .env mainīgos
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->safeLoad();

// Datubāzes pieslēgšanās informācija no .env
$servername = $_ENV['DB_HOST'] ?? 'localhost';
$username   = $_ENV['DB_USER'] ?? 'root';
$password   = $_ENV['DB_PASS'] ?? '';
$dbname     = $_ENV['DB_NAME'] ?? 'dzivnieku_patversme';
$port       = $_ENV['DB_PORT'] ?? 3306;

// ✅ JA JAU ILOGOJIES — NOVIRZA UZ ATBILSTOŠO LAPU
if (isset($_SESSION["epasts"])) {
    if (!empty($_SESSION["admin"]) && $_SESSION["admin"] == 1) {
        header("Location: admin.php");
    } else {
        header("Location: index.php");
    }
    exit;
}

// ✅ Izveido PDO savienojumu ar datubāzi
try {
    $conn = new PDO("mysql:host=$servername;port=$port;dbname=$dbname;charset=utf8", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("<script>alert('❌ Neizdevās pieslēgties datubāzei!'); console.error('DB Error: " . addslashes($e->getMessage()) . "'); window.location.href='login.html';</script>");
}

// ✅ Ja forma tika iesniegta
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $epasts = trim($_POST["epasts"] ?? '');
    $parole = trim($_POST["parole"] ?? '');

    if (empty($epasts) || empty($parole)) {
        echo "<script>alert('❌ Lūdzu aizpildi visus laukus!'); window.history.back();</script>";
        exit;
    }

    // Pārbauda, vai lietotājs eksistē
    $check = $conn->prepare("SELECT * FROM lietotaji WHERE epasts = ?");
    $check->execute([$epasts]);
    $user = $check->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        echo "<script>alert('❌ Lietotājs ar šo e-pastu nav reģistrēts!'); window.location.href='login.html';</script>";
        exit;
    }

    // Pārbauda paroli
    if (!password_verify($parole, $user["parole"])) {
        echo "<script>alert('❌ Nepareiza parole!'); window.location.href='login.html';</script>";
        exit;
    }

    // ✅ Saglabā sesijā lietotāja info (pievienots user_id!)
    $_SESSION["user_id"] = $user["id"];
    $_SESSION["lietotajvards"] = $user["lietotajvards"];
    $_SESSION["epasts"] = $user["epasts"];
    $_SESSION["admin"] = (int)$user["admin"];

    // ✅ Novirza uz atbilstošo lapu
    if ($_SESSION["admin"] === 1) {
        header("Location: admin.php");
    } else {
        header("Location: index.php");
    }
    exit;
}

// Ja forma netika iesniegta (GET pieprasījums)
header("Location: login.html");
exit;
?>
<!DOCTYPE html>
<html lang="lv">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pieteikšanās - Dzīvnieku Patversme</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: Arial, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
        }
        .container {
            background: white;
            padding: 40px;
            border-radius: 10px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.2);
            width: 100%;
            max-width: 400px;
        }
        h1 { text-align: center; color: #333; margin-bottom: 30px; }
        .form-group { margin-bottom: 20px; }
        label { display: block; margin-bottom: 5px; color: #555; font-weight: bold; }
        input {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 16px;
        }
        input:focus { outline: none; border-color: #667eea; }
        .btn {
            width: 100%;
            padding: 12px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 5px;
            font-size: 18px;
            font-weight: bold;
            cursor: pointer;
        }
        .btn:hover { transform: translateY(-2px); }
        .error {
            background: #fee;
            border: 1px solid #fcc;
            color: #c00;
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        .register-link, .home-link {
            text-align: center;
            margin-top: 15px;
            color: #666;
        }
        .register-link a, .home-link a {
            color: #667eea;
            text-decoration: none;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Pieteikšanās</h1>
        
        <?php if ($error): ?>
            <div class="error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        
        <form method="POST" action="">
            <div class="form-group">
                <label>Lietotājvārds vai E-pasts</label>
                <input type="text" name="username" required value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>">
            </div>
            
            <div class="form-group">
                <label>Parole</label>
                <input type="password" name="password" required>
            </div>
            
            <button type="submit" class="btn">Pieteikties</button>
        </form>
        
        <div class="register-link">
            Nav konta? <a href="register.php">Reģistrēties</a>
        </div>
        
        <div class="home-link">
            <a href="index.php">← Uz sākumlapu</a>
        </div>
    </div>
</body>
</html>