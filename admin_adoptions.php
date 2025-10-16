<?php
session_start();
if (!isset($_SESSION['epasts']) || $_SESSION['admin'] != 1) {
    header("Location: login.html");
    exit;
}

require_once 'db_conn.php';

$stmt = $conn->query("
    SELECT 
        a.id,
        a.pet_id,
        a.lietotaja_id,
        a.datums,
        a.laiks,
        a.piezimes,
        a.statuss,
        a.created_at,
        l.lietotajvards,
        l.epasts,
        d.vards AS dzivnieka_vards,
        d.suga
    FROM adopcijas_pieteikumi a
    JOIN lietotaji l ON a.lietotaja_id = l.id
    JOIN dzivnieki d ON a.pet_id = d.id
    ORDER BY a.created_at DESC
");
$pieteikumi = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="lv">
<head>
  <meta charset="UTF-8">
  <title>Adopcijas pieteikumi — Admin panelis</title>
  <link rel="stylesheet" href="admin.css">
  <style>
    body {
      background: #f8f9ff;
      font-family: 'Inter', sans-serif;
      color: #333;
      margin: 0;
    }

    main {
      max-width: 1200px;
      margin: 3rem auto;
      padding: 0 1.5rem;
    }

    h2 {
      color: #4c1d95;
      text-align: center;
      margin-bottom: 2rem;
      font-weight: 700;
    }

    table {
      width: 100%;
      border-collapse: collapse;
      background: white;
      border-radius: 12px;
      overflow: hidden;
      box-shadow: 0 4px 20px rgba(0,0,0,0.08);
    }

    th, td {
      padding: 14px 16px;
      text-align: left;
      vertical-align: top;
    }

    th {
      background: #6366f1;
      color: white;
      text-transform: uppercase;
      font-size: 0.9rem;
      letter-spacing: 0.5px;
    }

    tr:nth-child(even) {
      background: #f4f6ff;
    }

    tr:hover {
      background: #eef2ff;
    }

    .status {
      padding: 6px 12px;
      border-radius: 20px;
      font-weight: 600;
      text-transform: capitalize;
    }

    .status.gaida_apstiprinājumu { background: #fde047; color: #1e293b; }
    .status.apstiprināts { background: #22c55e; color: white; }
    .status.noraidīts { background: #ef4444; color: white; }

    .btn {
      border: none;
      padding: 8px 14px;
      border-radius: 8px;
      font-weight: 600;
      cursor: pointer;
      transition: all 0.2s;
      font-size: 0.95rem;
    }

    .btn.approve {
      background: #22c55e;
      color: white;
    }

    .btn.reject {
      background: #ef4444;
      color: white;
    }

    .btn:hover {
      opacity: 0.9;
      transform: scale(1.03);
    }

    td small {
      color: #6b7280;
      font-size: 0.85rem;
    }

    td form {
      display: inline-block;
      margin-right: 6px;
    }
  </style>
</head>
<body>

<?php include 'admin_navbar.php'; ?>

<main>
  <h2>Adopcijas pieteikumi</h2>

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

    <?php if (empty($pieteikumi)): ?>
      <tr><td colspan="8" style="text-align:center; padding:20px;">Nav neviena adopcijas pieteikuma.</td></tr>
    <?php else: ?>
      <?php foreach ($pieteikumi as $p): ?>
        <tr>
          <td><?= $p['id'] ?></td>
          <td>
            <?= htmlspecialchars($p['lietotajvards']) ?><br>
            <small><?= htmlspecialchars($p['epasts']) ?></small>
          </td>
          <td><?= htmlspecialchars($p['dzivnieka_vards']) ?> (<?= htmlspecialchars($p['suga']) ?>)</td>
          <td><?= htmlspecialchars($p['datums']) ?></td>
          <td><?= htmlspecialchars($p['laiks']) ?></td>
          <td><?= nl2br(htmlspecialchars($p['piezimes'] ?? '—')) ?></td>
          <td>
            <span class="status <?= htmlspecialchars(str_replace(' ', '_', strtolower($p['statuss']))) ?>">
              <?= htmlspecialchars($p['statuss']) ?>
            </span>
          </td>
          <td>
            <?php if ($p['statuss'] === 'gaida apstiprinājumu'): ?>
              <form method="POST" action="update_adoption_status.php">
                <input type="hidden" name="id" value="<?= $p['id'] ?>">
                <input type="hidden" name="pet_id" value="<?= $p['pet_id'] ?>">
                <input type="hidden" name="status" value="apstiprināts">
                <button class="btn approve">Apstiprināt</button>
              </form>
              <form method="POST" action="update_adoption_status.php">
                <input type="hidden" name="id" value="<?= $p['id'] ?>">
                <input type="hidden" name="pet_id" value="<?= $p['pet_id'] ?>">
                <input type="hidden" name="status" value="noraidīts">
                <button class="btn reject">Noraidīt</button>
              </form>
            <?php elseif ($p['statuss'] === 'apstiprināts'): ?>
              <form method="POST" action="update_adoption_status.php">
                <input type="hidden" name="id" value="<?= $p['id'] ?>">
                <input type="hidden" name="pet_id" value="<?= $p['pet_id'] ?>">
                <input type="hidden" name="status" value="noraidīts">
                <button class="btn reject">Noraidīt</button>
              </form>
            <?php elseif ($p['statuss'] === 'noraidīts'): ?>
              <form method="POST" action="update_adoption_status.php">
                <input type="hidden" name="id" value="<?= $p['id'] ?>">
                <input type="hidden" name="pet_id" value="<?= $p['pet_id'] ?>">
                <input type="hidden" name="status" value="apstiprināts">
                <button class="btn approve">Apstiprināt</button>
              </form>
            <?php endif; ?>
          </td>
        </tr>
      <?php endforeach; ?>
    <?php endif; ?>
  </table>
</main>
</body>
</html>
