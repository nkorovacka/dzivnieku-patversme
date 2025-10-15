<?php
// profile.php — Lietotāja profils
session_start();
require_once __DIR__ . '/db_conn.php';
if (!isset($_SESSION['user_id'])) { header('Location: login.php'); exit; }
$uid = (int)$_SESSION['user_id'];

// (pēc vajadzības) ieslēdz kļūdu rādīšanu atkļūdošanai
// ini_set('display_errors', 1);
// error_reporting(E_ALL);

// Palīgfunkcija drošai teksta izvadei
function esc($s){ return htmlspecialchars((string)$s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }
// Palīgfunkcija datuma/laika formatēšanai
function fdt($ts){ return $ts ? date('d.m.Y H:i', strtotime($ts)) : '—'; }

/** 1) ĪSĀ STATISTIKA */
$stats = [
  'events_count'  => 0,
  'walks_count'   => 0,
  'minutes_total' => 0,
  'points_total'  => 0,
  'fav_dog_name'  => null
];

// Vaicājums statistikai
$st = $conn->prepare("
  SELECT
    (SELECT COUNT(*) FROM event_participants ep WHERE ep.user_id = ?) AS events_count,
    (SELECT COUNT(*) FROM walks w WHERE w.user_id = ?) AS walks_count,
    (SELECT IFNULL(SUM(w.duration_min),0) FROM walks w WHERE w.user_id = ?) AS minutes_total,
    (SELECT IFNULL(SUM(bp.points),0) FROM bonus_points bp WHERE bp.user_id = ?) AS points_total,
    (SELECT d.name
       FROM walks w
       JOIN dogs d ON d.id = w.dog_id
       WHERE w.user_id = ?
       GROUP BY d.id
       ORDER BY COUNT(*) DESC
       LIMIT 1) AS fav_dog_name
");
$st->bind_param("iiiii", $uid,$uid,$uid,$uid,$uid);
$st->execute();
$r = $st->get_result();
if ($row = $r->fetch_assoc()) { $stats = $row; }

/** 2) LIETOTĀJA DALĪBA PASĀKUMOS (no jaunākā) */
$ev = $conn->prepare("
  SELECT e.title, e.location, e.starts_at
  FROM event_participants ep
  JOIN events e ON e.id = ep.event_id
  WHERE ep.user_id = ?
  ORDER BY e.starts_at DESC
");
$ev->bind_param("i", $uid);
$ev->execute();
$eventRows = $ev->get_result();

/** 3) PASTAIGU VĒSTURE (pēdējās 50) */
$wk = $conn->prepare("
  SELECT w.walked_at, w.duration_min, d.name AS dog_name, w.notes
  FROM walks w
  JOIN dogs d ON d.id = w.dog_id
  WHERE w.user_id = ?
  ORDER BY w.walked_at DESC
  LIMIT 50
");
$wk->bind_param("i", $uid);
$wk->execute();
$walkRows = $wk->get_result();

/** 4) BONUSA PUNKTU VĒSTURE (pēdējās 50) */
$bp = $conn->prepare("
  SELECT created_at, points, reason
  FROM bonus_points
  WHERE user_id = ?
  ORDER BY created_at DESC
  LIMIT 50
");
$bp->bind_param("i", $uid);
$bp->execute();
$pointRows = $bp->get_result();

// Paziņojums, ja pievienota jauna pastaiga
$walkAdded = isset($_GET['walk_added']);
?>
<!doctype html>
<html lang="lv">
<head>
  <meta charset="utf-8">
  <title>Profils</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <!-- Projekta galvenais stils -->
  <link rel="stylesheet" href="styles.css">
</head>
<body>

<?php include __DIR__ . '/navbar.php'; ?>

<section class="section">
  <div class="container">

    <h1 style="margin-bottom:10px;">Profils</h1>

    <div class="actions">
      <a href="walks_new.php" class="btn btn-primary">+ Pievienot pastaigu</a>
      <a href="events.php" class="btn btn-white">Pasākumi</a>
    </div>

    <?php if ($walkAdded): ?>
      <div class="notice">✅ Pastaiga pievienota veiksmīgi</div>
    <?php endif; ?>

    <!-- STATISTIKAS PANELIS -->
    <div class="panel" style="margin-top:12px;">
      <div class="kpis">
        <div class="kpi">
          <div class="val"><?= (int)$stats['events_count'] ?></div>
          <div class="label">Pasākumi</div>
        </div>
        <div class="kpi">
          <div class="val"><?= (int)$stats['walks_count'] ?></div>
          <div class="label">Pastaigas</div>
        </div>
        <div class="kpi">
          <div class="val"><?= (int)$stats['minutes_total'] ?></div>
          <div class="label">Minūtes</div>
        </div>
        <div class="kpi">
          <div class="val"><?= number_format(((int)$stats['minutes_total'])/60, 1, '.', '') ?></div>
          <div class="label">Stundas</div>
        </div>
        <div class="kpi">
          <div class="val"><?= (int)$stats['points_total'] ?></div>
          <div class="label">Bonusa punkti</div>
        </div>
      </div>
      <p style="margin-top:10px;color:#475569">
        Mīļākais suns: <b><?= esc($stats['fav_dog_name'] ?? '—') ?></b>
      </p>
    </div>

    <!-- GALVENĀ SATURA TĪKLS -->
    <div style="display:grid;grid-template-columns:1fr;gap:16px;margin-top:16px;">

      <!-- DALĪBA PASĀKUMOS -->
      <div class="panel">
        <h2 style="margin-bottom:8px;">Dalība pasākumos</h2>
        <table class="table">
          <thead>
            <tr>
              <th>Nosaukums</th>
              <th>Vieta</th>
              <th>Laiks</th>
            </tr>
          </thead>
          <tbody>
          <?php if ($eventRows->num_rows === 0): ?>
            <tr><td colspan="3" style="color:#475569">Nav dalības nevienā pasākumā.</td></tr>
          <?php else: ?>
            <?php while($e = $eventRows->fetch_assoc()): ?>
              <tr>
                <td><?= esc($e['title']) ?></td>
                <td><span class="badge"><?= esc($e['location'] ?: '—') ?></span></td>
                <td><?= esc(fdt($e['starts_at'])) ?></td>
              </tr>
            <?php endwhile; ?>
          <?php endif; ?>
          </tbody>
        </table>
      </div>

      <!-- PASTAIGU VĒSTURE -->
      <div class="panel">
        <h2 style="margin-bottom:8px;">Pastaigu vēsture (pēdējās 50)</h2>
        <table class="table">
          <thead>
            <tr>
              <th>Datums</th>
              <th>Suns</th>
              <th>Ilgums (min)</th>
              <th>Piezīmes</th>
            </tr>
          </thead>
          <tbody>
          <?php if ($walkRows->num_rows === 0): ?>
            <tr><td colspan="4" style="color:#475569">Pagaidām nav reģistrētu pastaigu.</td></tr>
          <?php else: ?>
            <?php while($w = $walkRows->fetch_assoc()): ?>
              <tr>
                <td><?= esc(fdt($w['walked_at'])) ?></td>
                <td><?= esc($w['dog_name']) ?></td>
                <td><?= (int)$w['duration_min'] ?></td>
                <td><?= esc($w['notes']) ?></td>
              </tr>
            <?php endwhile; ?>
          <?php endif; ?>
          </tbody>
        </table>
      </div>

      <!-- BONUSA PUNKTU VĒSTURE -->
      <div class="panel">
        <h2 style="margin-bottom:8px;">Bonusa punktu vēsture (pēdējās 50)</h2>
        <table class="table">
          <thead>
            <tr>
              <th>Datums</th>
              <th>Punkti</th>
              <th>Iemesls</th>
            </tr>
          </thead>
          <tbody>
          <?php if ($pointRows->num_rows === 0): ?>
            <tr><td colspan="3" style="color:#475569">Vēl nav bonusa punktu ierakstu.</td></tr>
          <?php else: ?>
            <?php while($p = $pointRows->fetch_assoc()): ?>
              <tr>
                <td><?= esc(fdt($p['created_at'])) ?></td>
                <td><?= (int)$p['points'] ?></td>
                <td><?= esc($p['reason']) ?></td>
              </tr>
            <?php endwhile; ?>
          <?php endif; ?>
          </tbody>
        </table>
      </div>

    </div>

  </div>
</section>

</body>
</html>
