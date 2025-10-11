<?php
session_start();

// ✅ Pārbauda, vai lietotājs vispār ir ielogojies
if (!isset($_SESSION["epasts"])) {
    header("Location: login.html");
    exit;
}

// ✅ Pārbauda, vai lietotājs ir administrators
if (!isset($_SESSION["admin"]) || $_SESSION["admin"] != 1) {
    header("Location: index.php");
    exit;
}

// ✅ Ielādē Dotenv (ja vajag DB datus)
require_once __DIR__ . '/vendor/autoload.php';
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

// ✅ Piemērs: nolasām visus lietotājus
$result = $conn->query("SELECT id, lietotajvards, epasts, admin FROM lietotaji ORDER BY id ASC");
?>
<!DOCTYPE html>
<html lang="lv">
<head>
    <meta charset="UTF-8">
    <title>Admin panelis — SirdsPaws</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f4f6ff;
            color: #1e293b;
            margin: 0;
            padding: 0;
        }
        header {
            background: linear-gradient(135deg, #6366f1, #8b5cf6);
            color: white;
            padding: 20px;
            text-align: center;
        }
        h1 { margin: 0; }
        table {
            width: 90%;
            margin: 30px auto;
            border-collapse: collapse;
            background: white;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 4px 10px rgba(0,0,0,0.1);
        }
        th, td {
            padding: 14px 20px;
            border-bottom: 1px solid #e5e7eb;
            text-align: left;
        }
        th {
            background: #eef2ff;
            color: #4f46e5;
        }
        tr:hover { background: #f9fafb; }
        .admin { color: green; font-weight: bold; }
        .user { color: #6b7280; }
        a.logout {
            display: inline-block;
            margin: 20px auto;
            background: #ef4444;
            color: white;
            text-decoration: none;
            padding: 10px 18px;
            border-radius: 8px;
            font-weight: bold;
        }
        a.logout:hover { background: #dc2626; }
    </style>
</head>
<body>

<header>
    <h1>🐾 Admin panelis</h1>
    <p>Sveiks, <?= htmlspecialchars($_SESSION['lietotajvards']) ?>!</p>
</header>

<main>
    <h2 style="text-align:center;">Lietotāji</h2>

    <table>
        <tr>
            <th>ID</th>
            <th>Lietotājvārds</th>
            <th>E-pasts</th>
            <th>Loma</th>
        </tr>
        <?php while ($row = $result->fetch_assoc()): ?>
            <tr>
                <td><?= $row['id'] ?></td>
                <td><?= htmlspecialchars($row['lietotajvards']) ?></td>
                <td><?= htmlspecialchars($row['epasts']) ?></td>
                <td class="<?= $row['admin'] ? 'admin' : 'user' ?>">
                    <?= $row['admin'] ? 'Administrators' : 'Lietotājs' ?>
                </td>
            </tr>
        <?php endwhile; ?>
    </table>

    <div style="text-align:center;">
        <a href="logout.php" class="logout">Izrakstīties</a>
    </div>
</main>

</body>
</html>
