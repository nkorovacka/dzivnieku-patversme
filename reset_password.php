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

// Iegūstam token un email no URL
$token = $_GET['token'] ?? '';
$email = $_GET['email'] ?? '';

if (!$token || !$email) {
    $error = "❌ Nederīga vai nepilnīga saite.";
} else {
    $token_hash = hash('sha256', $token);
    $stmt = $conn->prepare("SELECT * FROM password_resets WHERE email = ? AND token_hash = ?");
    $stmt->bind_param('ss', $email, $token_hash);
    $stmt->execute();
    $res = $stmt->get_result();

    if ($res->num_rows === 0) {
        $error = "❌ Nederīgs vai izbeidzies atjaunošanas tokens.";
    } else {
        $row = $res->fetch_assoc();
        if (new DateTime() > new DateTime($row['expires_at'])) {
            $error = "⌛ Saite ir beigusies. Lūdzu pieprasi jaunu paroles atjaunošanas saiti.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="lv">
<head>
  <meta charset="UTF-8">
  <title>Atjaunot paroli — SirdsPaws</title>
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
    .container {
      background: white;
      padding: 40px 50px;
      border-radius: 16px;
      box-shadow: 0 8px 25px rgba(0, 0, 0, 0.2);
      width: 380px;
      text-align: center;
      animation: fadeIn 0.6s ease-in-out;
    }
    @keyframes fadeIn {
      from { opacity: 0; transform: translateY(20px); }
      to { opacity: 1; transform: translateY(0); }
    }
    h2 {
      color: #4f46e5;
      margin-bottom: 1.5rem;
      font-size: 1.8rem;
    }
    p {
      color: #1e293b;
      font-size: 15px;
      margin-bottom: 1rem;
    }
    input {
      width: 100%;
      padding: 12px;
      margin: 10px 0;
      border: 1px solid #ccc;
      border-radius: 8px;
      font-size: 15px;
      transition: border 0.2s;
    }
    input:focus {
      outline: none;
      border-color: #6366f1;
      box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.2);
    }
    button {
      width: 100%;
      padding: 12px;
      border: none;
      border-radius: 8px;
      background: linear-gradient(135deg, #6366f1, #8b5cf6);
      color: white;
      font-weight: bold;
      font-size: 16px;
      cursor: pointer;
      transition: transform 0.2s, box-shadow 0.2s;
      margin-top: 10px;
    }
    button:hover {
      transform: translateY(-2px);
      box-shadow: 0 5px 15px rgba(99, 102, 241, 0.4);
    }
    .back {
      display: block;
      margin-top: 1.2rem;
      text-decoration: none;
      color: #6366f1;
      font-size: 14px;
    }
    .back:hover {
      text-decoration: underline;
    }
  </style>
</head>
<body>

  <div class="container">
    <?php if (!empty($error)): ?>
      <h2>❌ Kļūda</h2>
      <p><?= htmlspecialchars($error) ?></p>
      <a href="request_password_reset.html" class="back">⬅ Atpakaļ</a>
    <?php else: ?>
      <h2>🔐 Iestatīt jaunu paroli</h2>
      <form action="handle_reset.php" method="post">
        <input type="hidden" name="token" value="<?= htmlspecialchars($token) ?>">
        <input type="hidden" name="email" value="<?= htmlspecialchars($email) ?>">
        <input type="password" name="password" placeholder="Jaunā parole" required>
        <input type="password" name="password_confirm" placeholder="Apstiprini jauno paroli" required>
        <button type="submit">Atjaunot paroli</button>
        <a href="login.html" class="back">⬅ Atpakaļ uz pieteikšanos</a>
      </form>
    <?php endif; ?>
  </div>

</body>
</html>
