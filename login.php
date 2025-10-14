<?php

session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/vendor/autoload.php';

// Ielādē .env mainīgos
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->safeLoad();

// Datubāzes pieslēgšanās informācija
$servername = $_ENV['DB_HOST'] ?? 'localhost';
$username   = $_ENV['DB_USER'] ?? 'root';
$password   = $_ENV['DB_PASS'] ?? '';
$dbname     = $_ENV['DB_NAME'] ?? 'dzivnieku_patversme';
$port       = $_ENV['DB_PORT'] ?? 3306;

// ✅ Ja forma tika iesniegta
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    try {
        $conn = new PDO("mysql:host=$servername;port=$port;dbname=$dbname;charset=utf8", $username, $password);
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    } catch (PDOException $e) {
        die("<script>alert('❌ Neizdevās pieslēgties datubāzei!'); console.error('DB Error: " . addslashes($e->getMessage()) . "'); window.location.href='login.html';</script>");
    }

    $epasts = trim($_POST["epasts"] ?? '');
    $parole = trim($_POST["parole"] ?? '');

    if (empty($epasts) || empty($parole)) {
        $error = "Lūdzu aizpildi visus laukus!";
    } else {
        // Проверка пользователя
        $check = $conn->prepare("SELECT * FROM lietotaji WHERE epasts = ?");
        $check->bind_param("s", $epasts);
        $check->execute();
        $result = $check->get_result();

    // Pārbauda lietotāju
    $check = $conn->prepare("SELECT * FROM lietotaji WHERE epasts = ?");
    $check->execute([$epasts]);
    $user = $check->fetch(PDO::FETCH_ASSOC);

                // Перенаправление
                if ($_SESSION["admin"] === 1) {
                    header("Location: admin.php");
                } else {
                    header("Location: index.php");
                }
                exit;
            }
        }
        $check->close();
    }
    $conn->close();
}
?>
<!DOCTYPE html>
<html lang="lv">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ieiet - SirdsPaws</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem;
        }

    // ✅ Saglabā sesijā lietotāja info (ar user_id!)
    $_SESSION["user_id"] = $user["id"];
    $_SESSION["lietotajvards"] = $user["lietotajvards"];
    $_SESSION["epasts"] = $user["epasts"];
    $_SESSION["admin"] = (int)$user["admin"];

        .auth-box h2 {
            text-align: center;
            color: #1a1a2e;
            margin-bottom: 2rem;
            font-size: 2rem;
        }

// ✅ Ja forma nav iesniegta, bet lietotājs jau ir ielogojies
if (isset($_SESSION["epasts"])) {
    if (!empty($_SESSION["admin"]) && $_SESSION["admin"] == 1) {
        header("Location: admin.php");
    } else {
        header("Location: index.php");
    }
    exit;
}

// Citādi atver login formu
header("Location: login.html");
exit;
?>
