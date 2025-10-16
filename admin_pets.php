<?php
require_once 'db_conn.php';

// --- Pievienot / Rediģēt / Dzēst dzīvnieku ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // ✅ Pievienot jaunu dzīvnieku
    if ($action === 'add') {
        $vards = trim($_POST['vards']);
        $suga = trim($_POST['suga']);
        $vecums = trim($_POST['vecums']);
        $dzimums = trim($_POST['dzimums']);
        $apraksts = trim($_POST['apraksts']);
        $statuss = $_POST['statuss'] ?? 'pieejams';
        $attels = null;

        if (!empty($_FILES['attels']['name']) && $_FILES['attels']['error'] === UPLOAD_ERR_OK) {
            $uploadDir = __DIR__ . '/uploads/';
            if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);

            $ext = strtolower(pathinfo($_FILES['attels']['name'], PATHINFO_EXTENSION));
            $allowed = ['jpg', 'jpeg', 'png', 'webp'];
            if (in_array($ext, $allowed)) {
                $fileName = uniqid('pet_', true) . '.' . $ext;
                $target = $uploadDir . $fileName;
                if (move_uploaded_file($_FILES['attels']['tmp_name'], $target)) {
                    $attels = 'uploads/' . $fileName;
                }
            }
        }

        $stmt = $conn->prepare("INSERT INTO dzivnieki (vards, suga, vecums, dzimums, apraksts, statuss, attels)
                                VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$vards, $suga, $vecums, $dzimums, $apraksts, $statuss, $attels]);

        header("Location: admin.php?page=pets&success=1");
        exit;
    }

    // ✅ Rediģēt dzīvnieku
    if ($action === 'edit') {
        $id = (int)$_POST['id'];
        $vards = trim($_POST['vards']);
        $suga = trim($_POST['suga']);
        $vecums = trim($_POST['vecums']);
        $dzimums = trim($_POST['dzimums']);
        $apraksts = trim($_POST['apraksts']);
        $statuss = $_POST['statuss'];
        $attels = $_POST['old_attels'] ?? null;

        if (!empty($_FILES['attels']['name']) && $_FILES['attels']['error'] === UPLOAD_ERR_OK) {
            $uploadDir = __DIR__ . '/uploads/';
            if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);

            $ext = strtolower(pathinfo($_FILES['attels']['name'], PATHINFO_EXTENSION));
            $allowed = ['jpg', 'jpeg', 'png', 'webp'];
            if (in_array($ext, $allowed)) {
                $fileName = uniqid('pet_', true) . '.' . $ext;
                $target = $uploadDir . $fileName;
                if (move_uploaded_file($_FILES['attels']['tmp_name'], $target)) {
                    $attels = 'uploads/' . $fileName;
                }
            }
        }

        $stmt = $conn->prepare("UPDATE dzivnieki SET vards=?, suga=?, vecums=?, dzimums=?, apraksts=?, statuss=?, attels=? WHERE id=?");
        $stmt->execute([$vards, $suga, $vecums, $dzimums, $apraksts, $statuss, $attels, $id]);

        header("Location: admin.php?page=pets&success=1");
        exit;
    }

    // ✅ Dzēst dzīvnieku
    if ($action === 'delete') {
        $id = (int)$_POST['id'];
        $stmt = $conn->prepare("DELETE FROM dzivnieki WHERE id = ?");
        $stmt->execute([$id]);
        header("Location: admin.php?page=pets&deleted=1");
        exit;
    }
}

// --- Ja rediģē ---
$editAnimal = null;
if (isset($_GET['edit'])) {
    $id = (int)$_GET['edit'];
    $stmt = $conn->prepare("SELECT * FROM dzivnieki WHERE id = ?");
    $stmt->execute([$id]);
    $editAnimal = $stmt->fetch(PDO::FETCH_ASSOC);
}

// --- Dzīvnieku saraksts ---
$animals = $conn->query("SELECT * FROM dzivnieki ORDER BY id DESC")->fetchAll(PDO::FETCH_ASSOC);
?>

<h2>Dzīvnieki</h2>

<?php if (isset($_GET['success'])): ?>
  <div style="background:#22c55e;color:white;padding:10px;border-radius:8px;margin-bottom:10px;">
    ✅ Izmaiņas saglabātas!
  </div>
<?php elseif (isset($_GET['deleted'])): ?>
  <div style="background:#ef4444;color:white;padding:10px;border-radius:8px;margin-bottom:10px;">
    🗑 Dzīvnieks dzēsts!
  </div>
<?php endif; ?>

<table>
  <tr>
    <th>Foto</th><th>Vārds</th><th>Suga</th><th>Vecums</th><th>Statuss</th><th>Darbības</th>
  </tr>
  <?php foreach ($animals as $a): ?>
    <tr>
      <td><?= $a['attels'] ? "<img src='{$a['attels']}' class='pet-thumbnail'>" : "<span style='color:#999;'>Nav</span>" ?></td>
      <td><?= htmlspecialchars($a['vards']) ?></td>
      <td><?= htmlspecialchars($a['suga']) ?></td>
      <td><?= htmlspecialchars($a['vecums']) ?></td>
      <td><?= htmlspecialchars($a['statuss']) ?></td>
      <td>
        <a href="admin.php?page=pets&edit=<?= $a['id'] ?>" class="btn btn-blue">✏️ Rediģēt</a>
        <form method="POST" action="admin.php?page=pets" style="display:inline;">
          <input type="hidden" name="action" value="delete">
          <input type="hidden" name="id" value="<?= $a['id'] ?>">
          <button class="btn btn-red" onclick="return confirm('Dzēst dzīvnieku?')">🗑 Dzēst</button>
        </form>
      </td>
    </tr>
  <?php endforeach; ?>
</table>

<section>
  <h3><?= $editAnimal ? 'Rediģēt dzīvnieku' : 'Pievienot dzīvnieku' ?></h3>
  <form method="POST" action="admin.php?page=pets" enctype="multipart/form-data" style="display:flex;gap:10px;flex-wrap:wrap;">
    <input type="hidden" name="action" value="<?= $editAnimal ? 'edit' : 'add' ?>">
    <?php if ($editAnimal): ?>
      <input type="hidden" name="id" value="<?= $editAnimal['id'] ?>">
      <input type="hidden" name="old_attels" value="<?= htmlspecialchars($editAnimal['attels']) ?>">
    <?php endif; ?>

    <input name="vards" placeholder="Vārds" value="<?= htmlspecialchars($editAnimal['vards'] ?? '') ?>" required>
    <input name="suga" placeholder="Suga" value="<?= htmlspecialchars($editAnimal['suga'] ?? '') ?>" required>
    <input name="vecums" placeholder="Vecums" value="<?= htmlspecialchars($editAnimal['vecums'] ?? '') ?>">
    <select name="dzimums">
      <option value="mātīte" <?= (isset($editAnimal['dzimums']) && $editAnimal['dzimums'] === 'mātīte') ? 'selected' : '' ?>>Mātīte</option>
      <option value="tēviņš" <?= (isset($editAnimal['dzimums']) && $editAnimal['dzimums'] === 'tēviņš') ? 'selected' : '' ?>>Tēviņš</option>
    </select>
    <select name="statuss">
      <option value="pieejams" <?= (isset($editAnimal['statuss']) && $editAnimal['statuss'] === 'pieejams') ? 'selected' : '' ?>>Pieejams</option>
      <option value="rezervets" <?= (isset($editAnimal['statuss']) && $editAnimal['statuss'] === 'rezervets') ? 'selected' : '' ?>>Rezervēts</option>
      <option value="adoptets" <?= (isset($editAnimal['statuss']) && $editAnimal['statuss'] === 'adoptets') ? 'selected' : '' ?>>Adoptēts</option>
    </select>
    <input name="apraksts" placeholder="Apraksts" value="<?= htmlspecialchars($editAnimal['apraksts'] ?? '') ?>">
    <input type="file" name="attels" accept="image/*">
    <?php if ($editAnimal && $editAnimal['attels']): ?>
      <img src="<?= htmlspecialchars($editAnimal['attels']) ?>" style="height:60px;border-radius:6px;">
    <?php endif; ?>
    <button type="submit" class="btn btn-green"><?= $editAnimal ? '💾 Saglabāt izmaiņas' : '➕ Pievienot' ?></button>
  </form>
</section>
