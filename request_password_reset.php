<?php
require_once __DIR__ . '/vendor/autoload.php';
session_start();

// load env (vlucas/phpdotenv)
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->safeLoad();

$host = $_ENV['DB_HOST'] ?? 'localhost';
$user = $_ENV['DB_USER'] ?? 'root';
$pass = $_ENV['DB_PASS'] ?? '';
$db   = $_ENV['DB_NAME'] ?? 'dzivnieku_patversme';
$port = $_ENV['DB_PORT'] ?? 3306;

$conn = new mysqli($host, $user, $pass, $db, (int)$port);
if ($conn->connect_error) die('DB err');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $_SESSION['msg'] = "Nederīgs e-pasts.";
        header('Location: request_password_reset.html');
        exit;
    }

    // same message regardless if email exists (avoid enumeration)
    $_SESSION['msg'] = "Ja e-pasts pastāv, uz to nosūtīta saite, seko instrukcijām.";

    // Check if user exists
    $stmt = $conn->prepare("SELECT id FROM lietotaji WHERE epasts = ?");
    $stmt->bind_param('s', $email);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($res->num_rows === 0) {
        // still show neutral message
        header('Location: request_password_reset.html');
        exit;
    }

    // generate token
    $token = bin2hex(random_bytes(32)); // 64 hex chars
    $token_hash = hash('sha256', $token);
    $expires = (new DateTime('+1 hour'))->format('Y-m-d H:i:s');

    // delete previous tokens for this email (optional)
    $del = $conn->prepare("DELETE FROM password_resets WHERE email = ?");
    $del->bind_param('s', $email);
    $del->execute();

    // store hashed token
    $ins = $conn->prepare("INSERT INTO password_resets (email, token_hash, expires_at) VALUES (?, ?, ?)");
    $ins->bind_param('sss', $email, $token_hash, $expires);
    $ins->execute();

    // send email with link
    $appUrl = rtrim($_ENV['APP_URL'] ?? 'http://localhost', '/');
    $resetLink = $appUrl . '/reset_password.php?token=' . $token . '&email=' . urlencode($email);

    // send via PHPMailer
    $mail = new PHPMailer\PHPMailer\PHPMailer(true);
    try {
        // SMTP settings from .env
        $mail->isSMTP();
        $mail->Host = $_ENV['SMTP_HOST'];
        $mail->SMTPAuth = true;
        $mail->Username = $_ENV['SMTP_USER'];
        $mail->Password = $_ENV['SMTP_PASS'];
        $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = (int)($_ENV['SMTP_PORT'] ?? 587);

        $mail->setFrom('no-reply@yourdomain.com', 'SirdsPaws');
        $mail->addAddress($email);
        $mail->Subject = 'Paroles atjaunošana — SirdsPaws';
        $mail->isHTML(true);
        $mail->Body = "
            Sveiki,<br><br>
            Saņēmām pieprasījumu nomainīt Jūsu paroli. Klikšķini uz saites zemāk (derīga 1 stundu):<br><br>
            <a href=\"{$resetLink}\">Atjaunot paroli</a><br><br>
            Ja Tu nerīkojies, vari ignorēt šo e-pastu.
        ";

        $mail->send();
    } catch (Exception $e) {
        // log error — bet parādīsim vienādu ziņu lietotājam
        error_log("Mail error: " . $mail->ErrorInfo);
    }

    header('Location: request_password_reset.html');
    exit;
}
