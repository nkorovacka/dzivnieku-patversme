<?php
require_once 'db_conn.php';

// ✅ Pievieno jaunu lietotāju
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'add_user') {
    $vards = trim($_POST['lietotajvards']);
    $epasts = trim($_POST['epasts']);
    $parole = password_hash($_POST['parole'], PASSWORD_DEFAULT);
    $admin = isset($_POST['admin']) ? 1 : 0;

    $stmt = $conn->prepare("INSERT INTO lietotaji (lietotajvards, epasts, parole, admin) VALUES (?, ?, ?, ?)");
    $stmt->execute([$vards, $epasts, $parole, $admin]);
    header("Location: admin.php?page=users");
    exit;
}

// ✅ Dzēš lietotāju
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'delete_user') {
    $id = (int)$_POST['id'];
    $stmt = $conn->prepare("DELETE FROM lietotaji WHERE id = ?");
    $stmt->execute([$id]);
    header("Location: admin.php?page=users");
    exit;
}

// ✅ Maina lietotāja lomu (admin / lietotājs)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'set_role') {
    $id = (int)$_POST['id'];
    $newRole = (int)$_POST['admin'];
    $stmt = $conn->prepare("UPDATE lietotaji SET admin = ? WHERE id = ?");
    $stmt->execute([$newRole, $id]);
    header("Location: admin.php?page=users");
    exit;
}

$users = $conn->query("SELECT * FROM lietotaji ORDER BY id ASC")->fetchAll(PDO::FETCH_ASSOC);
?>

<h2>Lietotāji</h2>
<table>
    <tr>
        <th>ID</th>
        <th>Lietotājvārds</th>
        <th>E-pasts</th>
        <th>Loma</th>
        <th>Darbība</th>
    </tr>
    <?php foreach ($users as $u): ?>
        <tr>
            <td><?= $u['id'] ?></td>
            <td><?= htmlspecialchars($u['lietotajvards']) ?></td>
            <td><?= htmlspecialchars($u['epasts']) ?></td>
            <td class="<?= $u['admin'] ? 'admin' : 'user' ?>">
                <?= $u['admin'] ? 'Administrators' : 'Lietotājs' ?>
            </td>
            <td>
                <!-- ✅ Mainīt lomu -->
                <form method="POST" action="admin_users.php" style="display:inline;">
                    <input type="hidden" name="action" value="set_role">
                    <input type="hidden" name="id" value="<?= $u['id'] ?>">
                    <input type="hidden" name="admin" value="<?= $u['admin'] ? 0 : 1 ?>">
                    <button type="submit" class="btn btn-blue">
                        <?= $u['admin'] ? 'Noņemt adminu' : 'Padarīt adminu' ?>
                    </button>
                </form>

                <!-- ✅ Dzēst lietotāju (izņemot sevi pašu) -->
                <?php if ($u['id'] != ($_SESSION['user_id'] ?? 0)): ?>
                    <form method="POST" action="admin_users.php" style="display:inline;">
                        <input type="hidden" name="action" value="delete_user">
                        <input type="hidden" name="id" value="<?= $u['id'] ?>">
                        <button type="submit" class="btn btn-red" onclick="return confirm('Vai tiešām dzēst lietotāju?')">Dzēst</button>
                    </form>
                <?php endif; ?>
            </td>
        </tr>
    <?php endforeach; ?>
</table>

<section>
    <h3>Pievienot lietotāju</h3>
    <form method="POST" action="admin_users.php" style="display:flex; gap:10px; flex-wrap:wrap;">
        <input type="hidden" name="action" value="add_user">
        <input name="lietotajvards" placeholder="Lietotājvārds" required>
        <input type="email" name="epasts" placeholder="E-pasts" required>
        <input type="password" name="parole" placeholder="Parole" required>
        <label><input type="checkbox" name="admin"> Admin</label>
        <button type="submit" class="btn btn-green">Pievienot</button>
    </form>
</section>
