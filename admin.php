<?php
session_start();

// ✅ Pārbauda, vai lietotājs ir ielogojies un ir admins
if (!isset($_SESSION["epasts"])) {
    header("Location: login.html");
    exit;
}
if (!isset($_SESSION["admin"]) || $_SESSION["admin"] != 1) {
    header("Location: index.php");
    exit;
}

// ✅ DB pieslēgums
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

// ✅ Nolasām visus lietotājus
$result = $conn->query("SELECT id, lietotajvards, epasts, admin FROM lietotaji ORDER BY id ASC");
?>
<!DOCTYPE html>
<html lang="lv">
<head>
    <meta charset="UTF-8">
    <title>Admin panelis — SirdsPaws</title>
    <link rel="stylesheet" href="admin.css">
</head>
<body>

<header>
    <h1>🐾 Admin panelis</h1>
    <p>Sveiks, <?= htmlspecialchars($_SESSION['lietotajvards']) ?>!</p>

    <nav>
        <a href="admin.php" class="active">👥 Lietotāji</a>
        <a href="admin_adoptions.php">🐶 Adopcijas pieteikumi</a>
        <a href="logout.php" class="logout">Izrakstīties</a>
    </nav>
</header>

<main>
    <h2>Lietotāji</h2>

    <table>
        <tr>
            <th>ID</th>
            <th>Lietotājvārds</th>
            <th>E-pasts</th>
            <th>Loma</th>
            <th>Darbība</th>
        </tr>

        <?php while ($row = $result->fetch_assoc()): ?>
            <tr>
                <td><?= $row['id'] ?></td>
                <td><?= htmlspecialchars($row['lietotajvards']) ?></td>
                <td><?= htmlspecialchars($row['epasts']) ?></td>
                <td class="<?= $row['admin'] ? 'admin' : 'user' ?>">
                    <?= $row['admin'] ? 'Administrators' : 'Lietotājs' ?>
                </td>
                <td>
                    <?php if ($row['admin'] != 1): ?>
                        <form method="POST" action="delete_user.php" style="display:inline;">
                            <input type="hidden" name="id" value="<?= $row['id'] ?>">
                            <button type="submit" class="btn delete" onclick="return confirm('Vai tiešām dzēst šo lietotāju?');">
                                ❌ Dzēst
                            </button>
                        </form>
                    <?php else: ?>
                        <span style="color:#999;">—</span>
                    <?php endif; ?>
                </td>
            </tr>
        <?php endwhile; ?>
    </table>
</main>

</body>
</html>
