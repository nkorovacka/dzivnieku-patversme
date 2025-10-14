<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'config.php';

// Если уже залогинен
if (isset($_SESSION["lietotajvards"])) {
    if (!empty($_SESSION["admin"]) && $_SESSION["admin"] == 1) {
        header("Location: admin.php");
    } else {
        header("Location: index.php");
    }
    exit;
}

$error = '';

// Обработка формы
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $conn = getConnection();
    
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

        if ($result->num_rows === 0) {
            $error = "Lietotājs ar šo e-pastu nav reģistrēts!";
        } else {
            $user = $result->fetch_assoc();
            
            // Проверка пароля
            if (!password_verify($parole, $user["parole"])) {
                $error = "Nepareiza parole!";
            } else {
                // Успешный вход
                $_SESSION["lietotajvards"] = $user["lietotajvards"];
                $_SESSION["epasts"] = $user["epasts"];
                $_SESSION["admin"] = (int)$user["admin"];

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

        .auth-box {
            background: white;
            padding: 3rem;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            max-width: 450px;
            width: 100%;
        }

        .auth-box h2 {
            text-align: center;
            color: #1a1a2e;
            margin-bottom: 2rem;
            font-size: 2rem;
        }

        .alert {
            padding: 1rem;
            border-radius: 10px;
            margin-bottom: 1rem;
            text-align: center;
        }

        .alert-error {
            background: #fee;
            color: #c33;
            border: 1px solid #fcc;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            color: #475569;
            font-weight: 600;
        }

        .form-group input {
            width: 100%;
            padding: 0.9rem;
            border: 2px solid #e2e8f0;
            border-radius: 10px;
            font-size: 1rem;
            transition: all 0.3s;
        }

        .form-group input:focus {
            outline: none;
            border-color: #6366f1;
            box-shadow: 0 0 0 3px rgba(99,102,241,0.1);
        }

        .btn-submit {
            width: 100%;
            padding: 1rem;
            background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%);
            color: white;
            border: none;
            border-radius: 10px;
            font-size: 1.1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
        }

        .btn-submit:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(99,102,241,0.4);
        }

        .auth-footer {
            text-align: center;
            margin-top: 1.5rem;
            color: #64748b;
        }

        .auth-footer a {
            color: #6366f1;
            text-decoration: none;
            font-weight: 600;
        }

        .auth-footer a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="auth-box">
        <h2>🐾 Ieiet sistēmā</h2>
        
        <?php if ($error): ?>
            <div class="alert alert-error">❌ <?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <form method="POST" action="">
            <div class="form-group">
                <label>E-pasts:</label>
                <input type="email" name="epasts" required
                       value="<?php echo htmlspecialchars($_POST['epasts'] ?? ''); ?>">
            </div>

            <div class="form-group">
                <label>Parole:</label>
                <input type="password" name="parole" required>
            </div>

            <button type="submit" class="btn-submit">Ieiet</button>
        </form>

        <div class="auth-footer">
            Nav konta? <a href="register.php">Reģistrēties</a>
        </div>
    </div>
</body>
</html>