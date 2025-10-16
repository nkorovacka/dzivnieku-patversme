<?php
require_once __DIR__ . '/vendor/autoload.php';
session_start();

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->safeLoad();

$host = $_ENV['DB_HOST'] ?? 'localhost';
$user = $_ENV['DB_USER'] ?? 'root';
$pass = $_ENV['DB_PASS'] ?? '';
$db   = $_ENV['DB_NAME'] ?? 'dzivnieku_patversme';
$port = $_ENV['DB_PORT'] ?? 3306;

$conn = new mysqli($host, $user, $pass, $db, (int)$port);
if ($conn->connect_error) die('DB err');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}

$email = $_POST['email'] ?? '';
$token = $_POST['token'] ?? '';
$pw = $_POST['password'] ?? '';
$pw2 = $_POST['password_confirm'] ?? '';

if ($pw !== $pw2) {
    $message = "❌ Paroles nesakrīt.";
    $success = false;
} elseif (!preg_match("/^(?=.*[a-z])(?=.*[A-Z])(?=.*\\d)(?=.*[!@#\$%^&*]).{8,}$/", $pw)) {
    $message = "⚠️ Parolei jābūt vismaz 8 simboliem, ar lielajiem/mazajiem burtiem, cipariem un speciālajām zīmēm.";
    $success = false;
} else {
    // verify token
    $token_hash = hash('sha256', $token);
    $stmt = $conn->prepare("SELECT * FROM password_resets WHERE email = ? AND token_hash = ?");
    $stmt->bind_param('ss', $email, $token_hash);
    $stmt->execute();
    $res = $stmt->get_result();

    if ($res->num_rows === 0) {
        $message = "❌ Nederīgs vai izbeidzies atjaunošanas tokens.";
        $success = false;
    } else {
        $row = $res->fetch_assoc();
        if (new DateTime() > new DateTime($row['expires_at'])) {
            $message = "⌛ Token ir beidzies. Lūdzu pieprasi jaunu paroli.";
            $success = false;
        } else {
            // update user password
            $hashed = password_hash($pw, PASSWORD_DEFAULT);
            $up = $conn->prepare("UPDATE lietotaji SET parole = ? WHERE epasts = ?");
            $up->bind_param('ss', $hashed, $email);
            $ok = $up->execute();

            if ($ok) {
                // delete token
                $del = $conn->prepare("DELETE FROM password_resets WHERE email = ?");
                $del->bind_param('s', $email);
                $del->execute();

                $message = "✅ Parole veiksmīgi nomainīta!";
                $success = true;
            } else {
                $message = "❌ Kļūda saglabājot jauno paroli.";
                $success = false;
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="lv">
<head>
  <meta charset="UTF-8">
  <title>Paroles atjaunošana — SirdsPaws</title>
  <style>
    body {
      font-family: "Segoe UI", Arial, sans-serif;
      background: linear-gradient(135deg, #667eea, #764ba2);
      display: flex;
      justify-content: center;
      align-items: center;
      height: 100vh;
      margin: 0;
    }
    .card {
      background: white;
      padding: 40px;
      border-radius: 16px;
      box-shadow: 0 8px 20px rgba(0, 0, 0, 0.15);
      width: 380px;
      text-align: center;
      animation: fadeIn 0.6s ease-in-out;
    }
    @keyframes fadeIn {
      from { opacity: 0; transform: translateY(20px); }
      to { opacity: 1; transform: translateY(0); }
    }
    h2 {
      color: <?= $success ? "#22c55e" : "#ef4444" ?>;
      margin-bottom: 15px;
    }
    p {
      color: #1e293b;
      font-size: 16px;
      margin-bottom: 25px;
    }
    a.button {
      display: inline-block;
      padding: 12px 20px;
      border-radius: 8px;
      text-decoration: none;
      color: white;
      background: linear-gradient(135deg, #6366f1, #8b5cf6);
      font-weight: bold;
      transition: transform 0.2s, box-shadow 0.2s;
    }
    a.button:hover {
      transform: translateY(-2px);
      box-shadow: 0 5px 15px rgba(99, 102, 241, 0.4);
    }
  </style>
</head>
<body>
  <div class="card">
    <h2><?= $message ?></h2>
    <?php if ($success): ?>
      <p>Tagad vari pieteikties ar savu jauno paroli.</p>
      <a href="login.html" class="button">Pieslēgties</a>
    <?php else: ?>
      <p>Atgriezies un mēģini vēlreiz.</p>
      <a href="request_password_reset.html" class="button">Atpakaļ</a>
    <?php endif; ?>
  </div>
</body>
</html>
