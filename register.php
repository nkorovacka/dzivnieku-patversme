<?php
// 🔧 Sesijas iestatījumi — pieejama visās lapās un ilgāk saglabājas
ini_set('session.cookie_path', '/');
ini_set('session.cookie_lifetime', 86400);
ini_set('session.gc_maxlifetime', 86400);
ini_set('session.cookie_secure', false); // true, ja izmanto HTTPS
ini_set('session.cookie_httponly', true);
session_start();

require_once __DIR__ . '/vendor/autoload.php';

// Ielādē .env failu
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->safeLoad();

// Izveido savienojumu ar datubāzi
$conn = new mysqli(
    $_ENV['DB_HOST'] ?? 'localhost',
    $_ENV['DB_USER'] ?? 'root',
    $_ENV['DB_PASS'] ?? '',
    $_ENV['DB_NAME'] ?? 'dzivnieku_patversme',
    $_ENV['DB_PORT'] ?? 3306
);

// Если уже залогинен, перенаправляем на аккаунт
if (isset($_SESSION['user_id'])) {
    header("Location: account.php");
    exit();
}

// Kad forma tiek iesniegta
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $lietotajvards = trim($_POST["lietotajvards"] ?? '');
    $epasts = trim($_POST["epasts"] ?? '');
    $parole = trim($_POST["parole"] ?? '');
    $confirm = trim($_POST["confirm"] ?? '');

    // ✅ Validācija servera pusē
    if (!preg_match("/^[A-Za-z0-9_]{3,20}$/", $lietotajvards)) {
        echo "<script>alert('❌ Lietotājvārds nav derīgs!'); window.history.back();</script>";
        exit;
    }

    if (!filter_var($epasts, FILTER_VALIDATE_EMAIL)) {
        echo "<script>alert('❌ E-pasta adrese nav derīga!'); window.history.back();</script>";
        exit;
    }

    if (!preg_match("/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[!@#$%^&*]).{8,}$/", $parole)) {
        echo "<script>alert('❌ Parolei jābūt vismaz 8 simboliem, ar lielajiem/mazajiem burtiem, ciparu un speciālu simbolu!'); window.history.back();</script>";
        exit;
    }

    if ($parole !== $confirm) {
        echo "<script>alert('❌ Paroles nesakrīt!'); window.history.back();</script>";
        exit;
    }

    // Pārbauda, vai lietotājs jau eksistē
    $check = $conn->prepare("SELECT * FROM lietotaji WHERE epasts = ? OR lietotajvards = ?");
    $check->bind_param("ss", $epasts, $lietotajvards);
    $check->execute();
    $result = $check->get_result();

    if ($result->num_rows > 0) {
        echo "<script>alert('⚠️ Lietotājs ar šo e-pastu vai lietotājvārdu jau eksistē!'); window.history.back();</script>";
        exit;
    }

    // Šifrē paroli
    $hashed = password_hash($parole, PASSWORD_DEFAULT);

    // Saglabā lietotāju
    $insert = $conn->prepare("INSERT INTO lietotaji (lietotajvards, epasts, parole, admin) VALUES (?, ?, ?, 0)");
    $insert->bind_param("sss", $lietotajvards, $epasts, $hashed);

    if ($insert->execute()) {
        // ✅ Uzreiz automātiski ielogojas
        $_SESSION["user_id"] = $conn->insert_id;
        $_SESSION["lietotajvards"] = $lietotajvards;
        $_SESSION["epasts"] = $epasts;
        $_SESSION["admin"] = 0;

        header("Location: index.php");
        exit;
    } else {
        echo "<script>alert('❌ Kļūda saglabājot lietotāju: " . addslashes($conn->error) . "'); window.history.back();</script>";
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="lv">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reģistrācija - Dzīvnieku Patversme</title>
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
            max-width: 500px;
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
        .required { color: red; }
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
        .success {
            background: #efe;
            border: 1px solid #cfc;
            color: #0a0;
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        .login-link {
            text-align: center;
            margin-top: 20px;
            color: #666;
        }
        .login-link a {
            color: #667eea;
            text-decoration: none;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Reģistrācija</h1>
        
        <?php if ($error): ?>
            <div class="error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="success"><?php echo $success; ?></div>
        <?php endif; ?>
        
        <form method="POST" action="">
            <div class="form-group">
                <label>Lietotājvārds <span class="required">*</span></label>
                <input type="text" name="username" required value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>">
            </div>
            
            <div class="form-group">
                <label>E-pasts <span class="required">*</span></label>
                <input type="email" name="email" required value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
            </div>
            
            <div class="form-group">
                <label>Pilnais vārds</label>
                <input type="text" name="full_name" value="<?php echo htmlspecialchars($_POST['full_name'] ?? ''); ?>">
            </div>
            
            <div class="form-group">
                <label>Telefons</label>
                <input type="tel" name="phone" value="<?php echo htmlspecialchars($_POST['phone'] ?? ''); ?>">
            </div>
            
            <div class="form-group">
                <label>Parole <span class="required">*</span></label>
                <input type="password" name="password" required>
            </div>
            
            <div class="form-group">
                <label>Apstipriniet paroli <span class="required">*</span></label>
                <input type="password" name="confirm_password" required>
            </div>
            
            <button type="submit" class="btn">Reģistrēties</button>
        </form>
        
        <div class="login-link">
            Jau ir konts? <a href="login.php">Pieteikties</a>
        </div>
    </div>
</body>
</html>