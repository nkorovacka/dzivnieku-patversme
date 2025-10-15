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
require_once __DIR__ . '/db_conn.php';
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
        .delete-btn {
            color: white;
            background: #ef4444;
            border: none;
            padding: 8px 14px;
            border-radius: 6px;
            cursor: pointer;
            font-weight: bold;
            transition: background 0.2s;
        }
        .delete-btn:hover { background: #dc2626; }
        .logout {
            display: inline-block;
            margin: 20px auto;
            background: #ef4444;
            color: white;
            text-decoration: none;
            padding: 10px 18px;
            border-radius: 8px;
            font-weight: bold;
        }
        .logout:hover { background: #dc2626; }
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
                            <button type="submit" class="delete-btn" onclick="return confirm('Vai tiešām dzēst šo lietotāju?');">Dzēst</button>
                        </form>
                    <?php else: ?>
                        <span style="color:#999;">—</span>
                    <?php endif; ?>
                </td>
            </tr>
        <?php endwhile; ?>
    </table>
    <div style="text-align:center; margin:20px;"></div>

    <div style="text-align:center;">
        <a href="logout.php" class="logout">Izrakstīties</a>
    </div>
</main>

</body>
</html>
