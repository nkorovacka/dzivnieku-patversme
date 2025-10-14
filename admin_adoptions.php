<?php
session_start();

if (!isset($_SESSION['epasts']) || $_SESSION['admin'] != 1) {
    header("Location: login.html");
    exit;
}

require_once __DIR__ . '/vendor/autoload.php';
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->safeLoad();

$conn = new mysqli(
    $_ENV['DB_HOST'] ?? 'localhost',
    $_ENV['DB_USER'] ?? 'root',
    $_ENV['DB_PASS'] ?? '',
    $_ENV['DB_NAME'] ?? 'dzivnieku_patversme',
    $_ENV['DB_PORT'] ?? 3306
);
if ($conn->connect_error) die("Savienojuma kļūda: " . $conn->connect_error);

$query = "
SELECT a.id, a.datums, a.laiks, a.piezimes, a.statuss,
       l.lietotajvards, l.epasts,
       d.vards AS dzivnieka_vards, d.suga
FROM adopcijas_pieteikumi a
JOIN lietotaji l ON a.lietotaja_id = l.id
JOIN dzivnieki d ON a.pet_id = d.id
ORDER BY a.datums DESC, a.laiks ASC
";
$result = $conn->query($query);
?>
<!DOCTYPE html>
<html lang="lv">
<head>
  <meta charset="UTF-8">
  <title>Adopcijas pieteikumi — Admin panelis</title>
  <link rel="stylesheet" href="admin.css">
</head>
<body>
<header>
  <h1>🐾 Adopcijas pieteikumi</h1>
  <p>Sveiks, <?= htmlspecialchars($_SESSION['lietotajvards']) ?>!</p>
  <nav>
    <a href="admin.php">👥 Lietotāji</a>
    <a href="admin_adoptions.php" class="active">🐶 Pieteikumi</a>
    <a href="logout.php" class="logout">Izrakstīties</a>
  </nav>
</header>

<main>
  <h2>Visi adopcijas pieteikumi</h2>
  <table>
    <tr>
      <th>ID</th>
      <th>Lietotājs</th>
      <th>Dzīvnieks</th>
      <th>Datums</th>
      <th>Laiks</th>
      <th>Piezīmes</th>
      <th>Statuss</th>
      <th>Darbības</th>
    </tr>

    <?php if ($result && $result->num_rows > 0): ?>
      <?php while ($row = $result->fetch_assoc()): ?>
        <tr>
          <td><?= $row['id'] ?></td>
          <td><?= htmlspecialchars($row['lietotajvards']) ?><br><small><?= htmlspecialchars($row['epasts']) ?></small></td>
          <td><?= htmlspecialchars($row['dzivnieka_vards']) ?> (<?= htmlspecialchars($row['suga']) ?>)</td>
          <td><?= htmlspecialchars($row['datums']) ?></td>
          <td><?= htmlspecialchars($row['laiks']) ?></td>
          <td><?= nl2br(htmlspecialchars($row['piezimes'])) ?></td>
          <td>
            <span class="status <?= $row['statuss'] ?>">
              <?= htmlspecialchars(ucfirst($row['statuss'])) ?>
            </span>
          </td>
          <td>
            <?php if ($row['statuss'] == 'gaida apstiprinājumu'): ?>
              <form method="POST" action="update_adoption_status.php" style="display:inline;">
                <input type="hidden" name="id" value="<?= $row['id'] ?>">
                <input type="hidden" name="status" value="apstiprinats">
                <button class="btn approve">✅ Apstiprināt</button>
              </form>
              <form method="POST" action="update_adoption_status.php" style="display:inline;">
                <input type="hidden" name="id" value="<?= $row['id'] ?>">
                <input type="hidden" name="status" value="noraidits">
                <button class="btn reject">❌ Noraidīt</button>
              </form>
            <?php elseif ($row['statuss'] == 'apstiprinats'): ?>
              <form method="POST" action="update_adoption_status.php" style="display:inline;">
                <input type="hidden" name="id" value="<?= $row['id'] ?>">
                <input type="hidden" name="status" value="noraidits">
                <button class="btn reject">❌ Noraidīt</button>
              </form>
            <?php elseif ($row['statuss'] == 'noraidits'): ?>
              <form method="POST" action="update_adoption_status.php" style="display:inline;">
                <input type="hidden" name="id" value="<?= $row['id'] ?>">
                <input type="hidden" name="status" value="apstiprinats">
                <button class="btn approve">✅ Apstiprināt</button>
              </form>
            <?php endif; ?>
          </td>
        </tr>
      <?php endwhile; ?>
    <?php else: ?>
      <tr><td colspan="8" style="text-align:center; padding:20px;">Nav pieteikumu.</td></tr>
    <?php endif; ?>
  </table>
</main>
</body>
</html>
