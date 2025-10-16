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
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
$conn->set_charset('utf8mb4');
if ($conn->connect_error) {
    die("Savienojuma kļūda: " . $conn->connect_error);
}

// ====== POST darbības (tikai admin) ======
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'add_user') {
        $lietotajvards = trim($_POST['lietotajvards'] ?? '');
        $epasts = trim($_POST['epasts'] ?? '');
        $parole = trim($_POST['parole'] ?? '');
        $isAdmin = isset($_POST['admin']) ? 1 : 0;

        if ($lietotajvards && $epasts && $parole) {
            $hashed = password_hash($parole, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("INSERT INTO lietotaji (lietotajvards, epasts, parole, admin) VALUES (?, ?, ?, ?)");
            $stmt->bind_param('sssi', $lietotajvards, $epasts, $hashed, $isAdmin);
            $stmt->execute();
        }
        header('Location: admin.php');
        exit;
    }

    if ($action === 'set_role') {
        $uid = (int)($_POST['user_id'] ?? 0);
        $adminFlag = (int)($_POST['admin'] ?? 0);
        // Neļaujam noņemt sev admin tiesības
        if ($uid && $uid !== (int)($_SESSION['user_id'] ?? 0)) {
            $stmt = $conn->prepare('UPDATE lietotaji SET admin = ? WHERE id = ?');
            $stmt->bind_param('ii', $adminFlag, $uid);
            $stmt->execute();
        }
        header('Location: admin.php');
        exit;
    }

    if ($action === 'add_animal') {
        $vards = trim($_POST['vards'] ?? '');
        $suga = trim($_POST['suga'] ?? '');
        $vecums = trim($_POST['vecums'] ?? '');
        $apraksts = trim($_POST['apraksts'] ?? '');
        $statuss = trim($_POST['statuss'] ?? 'pieejams');
        if ($vards && $suga) {
            try {
                $stmt = $conn->prepare('INSERT INTO dzivnieki (vards, suga, vecums, apraksts, statuss) VALUES (?, ?, ?, ?, ?)');
                $stmt->bind_param('sssss', $vards, $suga, $vecums, $apraksts, $statuss);
                $stmt->execute();
            } catch (Throwable $e) {
                http_response_code(500);
                echo '<pre style="padding:20px;">Kļūda pievienojot dzīvnieku: ' . htmlspecialchars($e->getMessage()) . '</pre>';
                exit;
            }
        }
        header('Location: admin.php');
        exit;
    }

    if ($action === 'delete_animal') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id) {
<<<<<<< Updated upstream
            $stmt = $conn->prepare('DELETE FROM dzivnieki WHERE id = ?');
            $stmt->bind_param('i', $id);
            $stmt->execute();
=======
            try {
                $conn->begin_transaction();

                // 1) Delete dependent rows if they exist
                // favorites.pet_id
                try {
                    $stmt = $conn->prepare('DELETE FROM favorites WHERE pet_id = ?');
                    $stmt->bind_param('i', $id);
                    $stmt->execute();
                } catch (Throwable $e) { /* table may not exist, ignore */ }

                // pieteikumi.dzivnieka_id (current project schema)
                try {
                    $stmt = $conn->prepare('DELETE FROM pieteikumi WHERE dzivnieka_id = ?');
                    $stmt->bind_param('i', $id);
                    $stmt->execute();
                } catch (Throwable $e) { /* table may not exist, ignore */ }

                // adopcijas_pieteikumi.pet_id (some deployments)
                try {
                    $stmt = $conn->prepare('DELETE FROM adopcijas_pieteikumi WHERE pet_id = ?');
                    $stmt->bind_param('i', $id);
                    $stmt->execute();
                } catch (Throwable $e) { /* table may not exist, ignore */ }

                // 2) Delete the animal
                $stmt = $conn->prepare('DELETE FROM dzivnieki WHERE id = ?');
                $stmt->bind_param('i', $id);
                $stmt->execute();

                $conn->commit();
            } catch (Throwable $e) {
                $conn->rollback();
                http_response_code(500);
                echo '<pre style="padding:20px;">Neizdevās dzēst dzīvnieku: ' . htmlspecialchars($e->getMessage()) . '</pre>';
                exit;
            }
>>>>>>> Stashed changes
        }
        header('Location: admin.php');
        exit;
    }
}

// ✅ Nolasām visus lietotājus
$result = $conn->query("SELECT id, lietotajvards, epasts, admin FROM lietotaji ORDER BY id ASC");
// ✅ Nolasām visus dzīvniekus
$animals = $conn->query("SELECT id, vards, suga, vecums, statuss FROM dzivnieki ORDER BY id DESC");
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
                    <form method="POST" action="admin.php" style="display:inline; margin-right:8px;">
                        <input type="hidden" name="action" value="set_role">
                        <input type="hidden" name="user_id" value="<?= $row['id'] ?>">
                        <input type="hidden" name="admin" value="<?= $row['admin'] ? 0 : 1 ?>">
                        <button type="submit" class="delete-btn" style="background:#4f46e5;" onclick="return confirm('Mainīt lomu?');">
                            <?= $row['admin'] ? 'Noņemt admin' : 'Padarīt admin' ?>
                        </button>
                    </form>
                    <?php if ($row['admin'] != 1): ?>
                        <form method="POST" action="delete_user.php" style="display:inline;">
                            <input type="hidden" name="id" value="<?= $row['id'] ?>">
                            <button type="submit" class="delete-btn" onclick="return confirm('Vai tiešām dzēst šo lietotāju?');">Dzēst</button>
                        </form>
                    <?php endif; ?>
                </td>
            </tr>
        <?php endwhile; ?>
    </table>
    <div style="text-align:center; margin:20px;"></div>

    <!-- Pievienot lietotāju -->
    <section style="width:90%; margin:0 auto 40px auto; background:white; padding:20px; border-radius:12px; box-shadow:0 4px 10px rgba(0,0,0,0.05);">
        <h3>Pievienot lietotāju</h3>
        <form method="POST" action="admin.php" style="display:flex; gap:12px; flex-wrap:wrap; align-items:flex-end;">
            <input type="hidden" name="action" value="add_user">
            <div>
                <label>Lietotājvārds</label><br>
                <input name="lietotajvards" required>
            </div>
            <div>
                <label>E-pasts</label><br>
                <input type="email" name="epasts" required>
            </div>
            <div>
                <label>Parole</label><br>
                <input type="password" name="parole" required>
            </div>
            <label style="display:flex; align-items:center; gap:6px;"><input type="checkbox" name="admin"> Admin</label>
            <button type="submit" class="delete-btn" style="background:#10b981;">Pievienot</button>
        </form>
    </section>

    <h2 style="text-align:center;">Dzīvnieki</h2>
    <table>
        <tr>
            <th>ID</th>
            <th>Vārds</th>
            <th>Suga</th>
            <th>Vecums</th>
            <th>Statuss</th>
            <th>Darbība</th>
        </tr>
        <?php while ($a = $animals->fetch_assoc()): ?>
            <tr>
                <td><?= $a['id'] ?></td>
                <td><?= htmlspecialchars($a['vards'] ?? '') ?></td>
                <td><?= htmlspecialchars($a['suga'] ?? '') ?></td>
                <td><?= htmlspecialchars($a['vecums'] ?? '') ?></td>
                <td><?= htmlspecialchars($a['statuss'] ?? '') ?></td>
                <td>
                    <form method="POST" action="admin.php" onsubmit="return confirm('Dzēst dzīvnieku?');">
                        <input type="hidden" name="action" value="delete_animal">
                        <input type="hidden" name="id" value="<?= $a['id'] ?>">
                        <button type="submit" class="delete-btn">Dzēst</button>
                    </form>
                </td>
            </tr>
        <?php endwhile; ?>
    </table>

    <!-- Pievienot dzīvnieku -->
    <section style="width:90%; margin:20px auto 0 auto; background:white; padding:20px; border-radius:12px; box-shadow:0 4px 10px rgba(0,0,0,0.05);">
        <h3>Pievienot dzīvnieku</h3>
        <form method="POST" action="admin.php" style="display:flex; gap:12px; flex-wrap:wrap; align-items:flex-end;">
            <input type="hidden" name="action" value="add_animal">
            <div>
                <label>Vārds</label><br>
                <input name="vards" required>
            </div>
            <div>
                <label>Suga</label><br>
                <select name="suga" required>
                    <option value="suns">Suns</option>
                    <option value="kaķis">Kaķis</option>
                </select>
            </div>
            <div>
                <label>Vecums</label><br>
                <input name="vecums" placeholder="2 gadi">
            </div>
            <div style="flex:1; min-width:240px;">
                <label>Apraksts</label><br>
                <input name="apraksts" placeholder="Draudzīgs un aktīvs...">
            </div>
            <div>
                <label>Statuss</label><br>
                <select name="statuss">
                    <option value="pieejams">pieejams</option>
                    <option value="rezervets">rezervēts</option>
                    <option value="adoptets">adoptēts</option>
                </select>
            </div>
            <button type="submit" class="delete-btn" style="background:#10b981;">Pievienot</button>
        </form>
    </section>

    <div style="text-align:center; display:flex; gap:12px; justify-content:center;">
        <a href="index.php" class="logout" style="background:#3b82f6;">Uz sākumu</a>
        <a href="logout.php" class="logout">Izrakstīties</a>
    </div>
</main>

</body>
</html>
