<?php
require_once 'db_conn.php';

// ============================
// 🔧 PASĀKUMU PIEVIENOŠANA / DZĒŠANA
// ============================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // ✅ Pievienot pasākumu
    if ($action === 'add') {
        $nosaukums = trim($_POST['nosaukums']);
        $apraksts = trim($_POST['apraksts']);
        $kategorija = $_POST['kategorija'] ?? 'adoption';
        $datums = $_POST['datums'];
        $laiks_sakums = $_POST['laiks_sakums'];
        $laiks_beigas = $_POST['laiks_beigas'];
        $vieta = trim($_POST['vieta']);
        $max_dalibnieki = (int)$_POST['max_dalibnieki'];

        if (!$nosaukums || !$apraksts || !$datums || !$laiks_sakums || !$laiks_beigas || !$vieta) {
            echo "<script>alert('Lūdzu aizpildi visus laukus!');</script>";
        } else {
            $stmt = $conn->prepare("
                INSERT INTO pasakumi (nosaukums, apraksts, kategorija, datums, laiks_sakums, laiks_beigas, vieta, max_dalibnieki)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([$nosaukums, $apraksts, $kategorija, $datums, $laiks_sakums, $laiks_beigas, $vieta, $max_dalibnieki]);
            header("Location: admin.php?page=events&success=1");
            exit;
        }
    }

    // ✅ Dzēst pasākumu
    if ($action === 'delete') {
        $id = (int)$_POST['id'];
        $stmt = $conn->prepare("DELETE FROM pasakumi WHERE id = ?");
        $stmt->execute([$id]);
        header("Location: admin.php?page=events&deleted=1");
        exit;
    }
}

// ============================
// 📅 Pasākumu saraksts
// ============================
$events = $conn->query("SELECT * FROM pasakumi ORDER BY datums DESC, laiks_sakums ASC")->fetchAll(PDO::FETCH_ASSOC);
?>

<h2>Pasākumi</h2>

<?php if (isset($_GET['success'])): ?>
  <div style="background:#22c55e;color:white;padding:10px;border-radius:8px;margin-bottom:10px;">
    ✅ Pasākums veiksmīgi pievienots!
  </div>
<?php elseif (isset($_GET['deleted'])): ?>
  <div style="background:#ef4444;color:white;padding:10px;border-radius:8px;margin-bottom:10px;">
    🗑 Pasākums dzēsts!
  </div>
<?php endif; ?>

<table>
  <tr>
    <th>Nosaukums</th>
    <th>Kategorija</th>
    <th>Datums</th>
    <th>Laiks</th>
    <th>Vieta</th>
    <th>Dalībnieki</th>
    <th>Darbības</th>
  </tr>
  <?php foreach ($events as $e): ?>
    <tr>
      <td><?= htmlspecialchars($e['nosaukums']) ?></td>
      <td><?= htmlspecialchars(ucfirst($e['kategorija'])) ?></td>
      <td><?= htmlspecialchars($e['datums']) ?></td>
      <td><?= htmlspecialchars($e['laiks_sakums']) ?> – <?= htmlspecialchars($e['laiks_beigas']) ?></td>
      <td><?= htmlspecialchars($e['vieta']) ?></td>
      <td><?= htmlspecialchars($e['max_dalibnieki']) ?></td>
      <td>
        <form method="POST" action="admin.php?page=events" style="display:inline;">
          <input type="hidden" name="action" value="delete">
          <input type="hidden" name="id" value="<?= $e['id'] ?>">
          <button class="btn btn-red" onclick="return confirm('Vai tiešām dzēst šo pasākumu?')">🗑 Dzēst</button>
        </form>
      </td>
    </tr>
  <?php endforeach; ?>
  <?php if (empty($events)): ?>
    <tr><td colspan="7" style="text-align:center; padding:20px;">Nav pievienotu pasākumu.</td></tr>
  <?php endif; ?>
</table>

<section>
  <h3>Pievienot jaunu pasākumu</h3>
  <form method="POST" action="admin.php?page=events" style="display:flex; flex-wrap:wrap; gap:10px;">
    <input type="hidden" name="action" value="add">

    <input type="text" name="nosaukums" placeholder="Nosaukums" required>
    <select name="kategorija" required>
      <option value="adoption">Adopcijas pasākums</option>
      <option value="volunteer">Brīvprātīgie</option>
      <option value="training">Apmācības</option>
      <option value="fundraising">Ziedojumu vākšana</option>
    </select>

    <input type="date" name="datums" required>
    <input type="time" name="laiks_sakums" required>
    <input type="time" name="laiks_beigas" required>

    <input type="text"


name="vieta" placeholder="Vieta" required>
    <input type="number" name="max_dalibnieki" placeholder="Max. dalībnieki" min="1" value="50" required style="width:150px;">

    <textarea name="apraksts" placeholder="Apraksts" required style="flex:1 1 100%; min-height:100px;"></textarea>

    <button type="submit" class="btn btn-green">➕ Pievienot pasākumu</button>
  </form>
</section>