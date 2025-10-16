<?php
session_start();

if (!isset($_SESSION["epasts"])) {
    header("Location: login.html");
    exit;
}
if (!isset($_SESSION["admin"]) || $_SESSION["admin"] != 1) {
    header("Location: index.php");
    exit;
}

require_once __DIR__ . '/db_conn.php';
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
$conn->set_charset('utf8mb4');
if ($conn->connect_error) {
    die("Savienojuma kļūda: " . $conn->connect_error);
}

// ====== POST darbības ======
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
        $dzimums = trim($_POST['dzimums'] ?? '');
        $apraksts = trim($_POST['apraksts'] ?? '');
        $statuss = trim($_POST['statuss'] ?? 'pieejams');
        $attels = '';

        if (isset($_FILES['attels']) && $_FILES['attels']['error'] === UPLOAD_ERR_OK) {
            $uploadDir = __DIR__ . '/uploads/';
            
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }

            $fileExtension = strtolower(pathinfo($_FILES['attels']['name'], PATHINFO_EXTENSION));
            $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

            if (in_array($fileExtension, $allowedExtensions)) {
                $newFileName = uniqid('pet_', true) . '.' . $fileExtension;
                $uploadPath = $uploadDir . $newFileName;

                if (move_uploaded_file($_FILES['attels']['tmp_name'], $uploadPath)) {
                    $attels = 'uploads/' . $newFileName;
                }
            }
        }

        if ($vards && $suga) {
            try {
                $stmt = $conn->prepare('INSERT INTO dzivnieki (vards, suga, vecums, dzimums, apraksts, statuss, attels) VALUES (?, ?, ?, ?, ?, ?, ?)');
                $stmt->bind_param('sssssss', $vards, $suga, $vecums, $dzimums, $apraksts, $statuss, $attels);
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
            try {
                $conn->begin_transaction();

                try {
                    $stmt = $conn->prepare('DELETE FROM favorites WHERE pet_id = ?');
                    $stmt->bind_param('i', $id);
                    $stmt->execute();
                } catch (Throwable $e) { }

                try {
                    $stmt = $conn->prepare('DELETE FROM pieteikumi WHERE dzivnieka_id = ?');
                    $stmt->bind_param('i', $id);
                    $stmt->execute();
                } catch (Throwable $e) { }

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
        }
        header('Location: admin.php');
        exit;
    }

    // НОВОЕ: Принять заявку
    if ($action === 'approve_application') {
        $pet_id = (int)($_POST['pet_id'] ?? 0);
        
        if ($pet_id) {
            try {
                $conn->begin_transaction();
                
                // Меняем статус животного на "adoptēts"
                $stmt = $conn->prepare('UPDATE dzivnieki SET statuss = ? WHERE id = ?');
                $petStatus = 'adoptets';
                $stmt->bind_param('si', $petStatus, $pet_id);
                $stmt->execute();
                
                // Меняем статус заявки на "apstiprinats"
                $stmt = $conn->prepare('UPDATE pieteikumi SET statuss = ? WHERE dzivnieka_id = ? AND statuss = ?');
                $approvedStatus = 'apstiprinats';
                $pendingStatus = 'gaida_apstiprinajumu';
                $stmt->bind_param('sis', $approvedStatus, $pet_id, $pendingStatus);
                $stmt->execute();
                
                $conn->commit();
            } catch (Throwable $e) {
                $conn->rollback();
                http_response_code(500);
                echo '<pre style="padding:20px;">Kļūda: ' . htmlspecialchars($e->getMessage()) . '</pre>';
                exit;
            }
        }
        header('Location: admin.php');
        exit;
    }

    // НОВОЕ: Отклонить заявку
    if ($action === 'reject_application') {
        $pet_id = (int)($_POST['pet_id'] ?? 0);
        
        if ($pet_id) {
            try {
                $conn->begin_transaction();
                
                // Возвращаем статус животного на "pieejams"
                $stmt = $conn->prepare('UPDATE dzivnieki SET statuss = ? WHERE id = ?');
                $petStatus = 'pieejams';
                $stmt->bind_param('si', $petStatus, $pet_id);
                $stmt->execute();
                
                // Удаляем заявку (или меняем статус на "noraidits")
                $stmt = $conn->prepare('DELETE FROM pieteikumi WHERE dzivnieka_id = ? AND statuss = ?');
                $pendingStatus = 'gaida_apstiprinajumu';
                $stmt->bind_param('is', $pet_id, $pendingStatus);
                $stmt->execute();
                
                $conn->commit();
            } catch (Throwable $e) {
                $conn->rollback();
                http_response_code(500);
                echo '<pre style="padding:20px;">Kļūda: ' . htmlspecialchars($e->getMessage()) . '</pre>';
                exit;
            }
        }
        header('Location: admin.php');
        exit;
    }
}

// Nolasām visus lietotājus
$result = $conn->query("SELECT id, lietotajvards, epasts, admin FROM lietotaji ORDER BY id ASC");

// ИЗМЕНЕНО: Загружаем животных с информацией о заявках
$animals = $conn->query("
    SELECT 
        d.id,
        d.vards,
        d.suga,
        d.vecums,
        d.dzimums,
        d.statuss,
        d.attels,
        l.lietotajvards AS pieteicejs,
        l.epasts AS pieteiceja_epasts
    FROM dzivnieki d
    LEFT JOIN pieteikumi p ON d.id = p.dzivnieka_id AND p.statuss = 'gaida_apstiprinajumu'
    LEFT JOIN lietotaji l ON p.lietotaja_id = l.id
    ORDER BY d.id DESC
");
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
        h2 {
            text-align: center;
            margin-top: 40px;
        }
        table {
            width: 95%;
            margin: 30px auto;
            border-collapse: collapse;
            background: white;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 4px 10px rgba(0,0,0,0.1);
        }
        th, td {
            padding: 14px 16px;
            border-bottom: 1px solid #e5e7eb;
            text-align: left;
        }
        th {
            background: #eef2ff;
            color: #4f46e5;
            font-size: 14px;
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
            font-size: 13px;
        }
        .delete-btn:hover { background: #dc2626; }
        .approve-btn {
            color: white;
            background: #10b981;
            border: none;
            padding: 8px 14px;
            border-radius: 6px;
            cursor: pointer;
            font-weight: bold;
            transition: background 0.2s;
            margin-right: 6px;
            font-size: 13px;
        }
        .approve-btn:hover { background: #059669; }
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
        .pet-thumbnail {
            width: 50px;
            height: 50px;
            object-fit: cover;
            border-radius: 6px;
        }
        .applicant-info {
            font-size: 12px;
            color: #6b7280;
        }
        .applicant-name {
            font-weight: bold;
            color: #1e293b;
        }
    </style>
</head>
<body>

<header>
    <h1>Admin panelis</h1>
    <p>Sveiks, <?= htmlspecialchars($_SESSION['lietotajvards']) ?>!</p>
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

    <section style="width:95%; margin:0 auto 40px auto; background:white; padding:20px; border-radius:12px; box-shadow:0 4px 10px rgba(0,0,0,0.05);">
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

    <h2>Dzīvnieki</h2>
    <table>
        <tr>
            <th>Foto</th>
            <th>ID</th>
            <th>Vārds</th>
            <th>Suga</th>
            <th>Dzimums</th>
            <th>Vecums</th>
            <th>Statuss</th>
            <th>Pieteicējs</th>
            <th>Darbība</th>
        </tr>
        <?php while ($a = $animals->fetch_assoc()): ?>
            <tr>
                <td>
                    <?php if (!empty($a['attels'])): ?>
                        <img src="<?= htmlspecialchars($a['attels']) ?>" alt="<?= htmlspecialchars($a['vards']) ?>" class="pet-thumbnail">
                    <?php else: ?>
                        <span style="color:#999;">Nav</span>
                    <?php endif; ?>
                </td>
                <td><?= $a['id'] ?></td>
                <td><?= htmlspecialchars($a['vards'] ?? '') ?></td>
                <td><?= htmlspecialchars($a['suga'] ?? '') ?></td>
                <td><?= htmlspecialchars($a['dzimums'] ?? '') ?></td>
                <td><?= htmlspecialchars($a['vecums'] ?? '') ?></td>
                <td><?= htmlspecialchars($a['statuss'] ?? '') ?></td>
                <td>
                    <?php if ($a['statuss'] === 'rezervets' && !empty($a['pieteicejs'])): ?>
                        <div class="applicant-info">
                            <div class="applicant-name"><?= htmlspecialchars($a['pieteicejs']) ?></div>
                            <div><?= htmlspecialchars($a['pieteiceja_epasts']) ?></div>
                        </div>
                    <?php else: ?>
                        <span style="color:#999;">—</span>
                    <?php endif; ?>
                </td>
                <td>
                    <?php if ($a['statuss'] === 'rezervets'): ?>
                        <form method="POST" action="admin.php" style="display:inline;" onsubmit="return confirm('Apstiprināt adopciju?');">
                            <input type="hidden" name="action" value="approve_application">
                            <input type="hidden" name="pet_id" value="<?= $a['id'] ?>">
                            <button type="submit" class="approve-btn">Apstiprināt</button>
                        </form>
                        <form method="POST" action="admin.php" style="display:inline;" onsubmit="return confirm('Noraidīt pieteikumu?');">
                            <input type="hidden" name="action" value="reject_application">
                            <input type="hidden" name="pet_id" value="<?= $a['id'] ?>">
                            <button type="submit" class="delete-btn">Noraidīt</button>
                        </form>
                    <?php else: ?>
                        <form method="POST" action="admin.php" onsubmit="return confirm('Dzēst dzīvnieku?');">
                            <input type="hidden" name="action" value="delete_animal">
                            <input type="hidden" name="id" value="<?= $a['id'] ?>">
                            <button type="submit" class="delete-btn">Dzēst</button>
                        </form>
                    <?php endif; ?>
                </td>
            </tr>
        <?php endwhile; ?>
    </table>

    <section style="width:95%; margin:20px auto 0 auto; background:white; padding:20px; border-radius:12px; box-shadow:0 4px 10px rgba(0,0,0,0.05);">
        <h3>Pievienot dzīvnieku</h3>
        <form method="POST" action="admin.php" enctype="multipart/form-data" style="display:flex; gap:12px; flex-wrap:wrap; align-items:flex-end;">
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
                    <option value="trusis">Trusis</option>
                </select>
            </div>
            <div>
                <label>Dzimums</label><br>
                <select name="dzimums" required>
                    <option value="">Izvēlies...</option>
                    <option value="mātīte">Mātīte</option>
                    <option value="tēviņš">Tēviņš</option>
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
            <div>
                <label>Foto</label><br>
                <input type="file" name="attels" accept="image/*">
            </div>
            <button type="submit" class="delete-btn" style="background:#10b981;">Pievienot</button>
        </form>
    </section>

    <div style="text-align:center; display:flex; gap:12px; justify-content:center; margin-top:40px; padding-bottom:40px;">
        <a href="index.php" class="logout" style="background:#3b82f6;">Uz sākumu</a>
        <a href="logout.php" class="logout">Izrakstīties</a>
    </div>
</main>

</body>
</html>