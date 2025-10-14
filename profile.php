<?php
<<<<<<< HEAD
// profile.php — Lietotāja profils
ini_set('session.cookie_path', '/');
ini_set('session.cookie_lifetime', 86400);
ini_set('session.gc_maxlifetime', 86400);
ini_set('session.cookie_secure', false); // true ja izmanto HTTPS
ini_set('session.cookie_httponly', true);
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
=======
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'config.php';
require_once 'bonusu_sistema.php';

// Проверка авторизации
if (!isset($_SESSION["lietotajvards"])) {
    header("Location: login.php");
    exit;
}

$conn = getConnection();
$epasts = $_SESSION["epasts"];

// Получить данные пользователя
$stmt = $conn->prepare("SELECT * FROM lietotaji WHERE epasts = ?");
$stmt->execute([$epasts]);
$user = $stmt->fetch();

if (!$user) {
    session_destroy();
    header("Location: login.php");
    exit;
}

// Инициализация бонусной системы
$bonusuSistema = new BonusuSistema($conn);

// Проверяем, есть ли у пользователя запись в bonusu системе
$stats = $bonusuSistema->iegutLietotajaStatistiku($user['id']);

// Если нет записи - создаём
if (!$stats) {
    $bonusuSistema->inicializetLietotajuBonusu($user['id']);
    $stats = $bonusuSistema->iegutLietotajaStatistiku($user['id']);
}

$vesture = $bonusuSistema->iegutTransakcijuVesturi($user['id'], 20);
$privilegijas = $bonusuSistema->iegutVisasPrivilegijas();
$manas_privilegijas = $bonusuSistema->iegutLietotajaPrivilegijas($user['id']);

// Aprēķinām progresu līdz nākamajam līmenim
$pieredze_limenim = BonusuSistema::PIEREDZE_UZ_LIMENI;
$tagadeja_pieredze = $stats['pieredze'] % $pieredze_limenim;
$progress_procenti = ($tagadeja_pieredze / $pieredze_limenim) * 100;
?>

<!DOCTYPE html>
<html lang="lv">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mans Profils - SirdsPaws</title>
    <link rel="stylesheet" href="index.css">
    <style>
        .profile-container {
            max-width: 1200px;
            margin: 40px auto;
            padding: 0 20px;
        }

        .profile-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 40px;
            border-radius: 20px;
            color: white;
            margin-bottom: 30px;
            box-shadow: 0 10px 40px rgba(102, 126, 234, 0.3);
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 20px;
        }

        .profile-header-left {
            display: flex;
            align-items: center;
            gap: 20px;
        }

        .profile-avatar {
            width: 80px;
            height: 80px;
            background: rgba(255, 255, 255, 0.2);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2.5rem;
            color: white;
            border: 3px solid rgba(255, 255, 255, 0.3);
        }

        .profile-header-info h1 {
            margin: 0 0 5px 0;
            font-size: 2rem;
        }

        .profile-header-info p {
            margin: 0;
            opacity: 0.9;
        }

        .badge {
            display: inline-block;
            padding: 0.4rem 1rem;
            background: rgba(255, 255, 255, 0.2);
            color: white;
            border-radius: 20px;
            font-size: 0.9rem;
            font-weight: 600;
            border: 1px solid rgba(255, 255, 255, 0.3);
        }

        .tabs-container {
            display: flex;
            gap: 10px;
            margin-bottom: 30px;
            flex-wrap: wrap;
        }

        .tab-btn {
            padding: 12px 24px;
            background: white;
            border: 2px solid #e5e7eb;
            border-radius: 12px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            color: #475569;
        }

        .tab-btn:hover {
            background: #f8f9ff;
            border-color: #6366f1;
        }

        .tab-btn.active {
            background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%);
            color: white;
            border-color: transparent;
            box-shadow: 0 4px 12px rgba(99, 102, 241, 0.3);
        }

        .tab-content {
            display: none;
        }

        .tab-content.active {
            display: block;
            animation: fadeIn 0.4s ease;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        /* ПРОФИЛЬ */
        .profile-info-card {
            background: white;
            border-radius: 16px;
            padding: 30px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
            margin-bottom: 20px;
        }

        .info-row {
            display: flex;
            padding: 1rem 0;
            border-bottom: 1px solid #f1f5f9;
        }

        .info-row:last-child {
            border-bottom: none;
        }

        .info-label {
            font-weight: 600;
            color: #475569;
            width: 200px;
        }

        .info-value {
            color: #1e293b;
            flex: 1;
        }

        .btn-logout {
            background: linear-gradient(135deg, #ef4444, #dc2626);
            color: white;
            padding: 12px 30px;
            border: none;
            border-radius: 10px;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            transition: all 0.3s ease;
            box-shadow: 0 4px 12px rgba(239, 68, 68, 0.3);
        }

        .btn-logout:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 16px rgba(239, 68, 68, 0.4);
        }

        /* BONUSU STATISTIKA */
        .bonus-stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 30px;
            border-radius: 16px;
            color: white;
            text-align: center;
            box-shadow: 0 8px 25px rgba(102, 126, 234, 0.25);
            transition: transform 0.3s ease;
        }

        .stat-card:hover {
            transform: translateY(-5px);
        }

        .stat-card h3 {
            margin: 0 0 10px 0;
            font-size: 14px;
            opacity: 0.9;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .stat-card .value {
            font-size: 42px;
            font-weight: 800;
            margin: 10px 0;
        }

        .stat-card .label {
            font-size: 13px;
            opacity: 0.8;
        }

        /* LĪMEŅA SISTĒMA */
        .level-card {
            background: white;
            padding: 30px;
            border-radius: 16px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
            margin-bottom: 30px;
        }

        .level-display {
            display: flex;
            align-items: center;
            gap: 20px;
            margin-bottom: 20px;
        }

        .level-badge {
            width: 80px;
            height: 80px;
            background: linear-gradient(135deg, #fbbf24, #f59e0b);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 32px;
            font-weight: 800;
            color: white;
            box-shadow: 0 6px 20px rgba(251, 191, 36, 0.4);
        }

        .level-info h2 {
            margin: 0;
            font-size: 24px;
            color: #1e293b;
        }

        .level-info p {
            margin: 5px 0 0 0;
            color: #64748b;
        }

        .progress-bar {
            width: 100%;
            height: 24px;
            background: #f1f5f9;
            border-radius: 12px;
            overflow: hidden;
            position: relative;
        }

        .progress-fill {
            height: 100%;
            background: linear-gradient(90deg, #6366f1, #8b5cf6);
            border-radius: 12px;
            transition: width 0.6s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 600;
            font-size: 12px;
        }

        /* DIENAS BONUSS */
        .daily-bonus-section {
            background: white;
            padding: 30px;
            border-radius: 16px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
            margin-bottom: 30px;
            text-align: center;
        }

        .daily-bonus-btn {
            padding: 16px 40px;
            background: linear-gradient(135deg, #10b981, #059669);
            color: white;
            border: none;
            border-radius: 12px;
            font-size: 18px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 6px 20px rgba(16, 185, 129, 0.3);
        }

        .daily-bonus-btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(16, 185, 129, 0.4);
        }

        .daily-bonus-btn:disabled {
            background: #cbd5e1;
            cursor: not-allowed;
            box-shadow: none;
        }

        /* VEIKALS */
        .shop-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 20px;
        }

        .privilege-card {
            background: white;
            border: 2px solid #e5e7eb;
            border-radius: 16px;
            padding: 25px;
            transition: all 0.3s ease;
        }

        .privilege-card:hover {
            border-color: #6366f1;
            box-shadow: 0 8px 25px rgba(99, 102, 241, 0.15);
            transform: translateY(-5px);
        }

        .privilege-icon {
            font-size: 48px;
            margin-bottom: 15px;
        }

        .privilege-card h3 {
            margin: 0 0 10px 0;
            font-size: 20px;
            color: #1e293b;
        }

        .privilege-card p {
            color: #64748b;
            margin-bottom: 20px;
            font-size: 14px;
        }

        .privilege-price {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-top: 15px;
        }

        .price-tag {
            font-size: 24px;
            font-weight: 800;
            color: #6366f1;
        }

        .buy-btn {
            padding: 10px 20px;
            background: linear-gradient(135deg, #6366f1, #8b5cf6);
            color: white;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .buy-btn:hover {
            transform: scale(1.05);
            box-shadow: 0 4px 12px rgba(99, 102, 241, 0.3);
        }

        .owned-badge {
            background: #10b981;
            color: white;
            padding: 8px 16px;
            border-radius: 20px;
            font-size: 13px;
            font-weight: 600;
        }

        /* TRANSAKCIJU VĒSTURE */
        .history-list {
            background: white;
            border-radius: 16px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
            overflow: hidden;
        }

        .history-item {
            padding: 20px 25px;
            border-bottom: 1px solid #f1f5f9;
            display: flex;
            justify-content: space-between;
            align-items: center;
            transition: background 0.2s ease;
        }

        .history-item:hover {
            background: #f8f9ff;
        }

        .history-item:last-child {
            border-bottom: none;
        }

        .history-info h4 {
            margin: 0 0 5px 0;
            font-size: 16px;
            color: #1e293b;
        }

        .history-info small {
            color: #94a3b8;
            font-size: 13px;
        }

        .history-points {
            font-size: 22px;
            font-weight: 800;
        }

        .history-points.positive {
            color: #10b981;
        }

        .history-points.negative {
            color: #ef4444;
        }

        /* MANAS PRIVILĒĢIJAS */
        .my-privileges-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
        }

        .my-privilege-card {
            background: linear-gradient(135deg, #f8f9ff 0%, #eef2ff 100%);
            border: 2px solid #c7d2fe;
            border-radius: 16px;
            padding: 25px;
            text-align: center;
        }

        .my-privilege-card .privilege-icon {
            font-size: 56px;
            margin-bottom: 10px;
        }

        .empty-state {
            text-align: center;
            padding: 60px 20px;
            background: white;
            border-radius: 16px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
        }

        .empty-state-icon {
            font-size: 64px;
            margin-bottom: 20px;
        }

        .empty-state h3 {
            color: #64748b;
            margin-bottom: 10px;
        }

        .empty-state p {
            color: #94a3b8;
        }

        @media (max-width: 768px) {
            .profile-header {
                flex-direction: column;
                text-align: center;
            }

            .profile-header-left {
                flex-direction: column;
            }

            .profile-header h1 {
                font-size: 1.8rem;
            }
            
            .tabs-container {
                overflow-x: auto;
                flex-wrap: nowrap;
            }
            
            .stat-card .value {
                font-size: 32px;
            }

            .info-row {
                flex-direction: column;
                gap: 5px;
            }

            .info-label {
                width: 100%;
            }

            .level-display {
                flex-direction: column;
                text-align: center;
            }
        }
    </style>
</head>
<body>
    <?php include 'navbar.php'; ?>

    <div class="profile-container">
        <div class="profile-header">
            <div class="profile-header-left">
                <div class="profile-avatar">
                    <?php echo strtoupper(substr($user['lietotajvards'], 0, 1)); ?>
                </div>
                <div class="profile-header-info">
                    <h1>👋 Sveiks, <?php echo htmlspecialchars($user['lietotajvards']); ?>!</h1>
                    <p>Tavs profils un bonusu sistēma</p>
                </div>
            </div>
            <?php if ($user['admin']): ?>
                <span class="badge">👑 Administrators</span>
            <?php endif; ?>
        </div>

        <div class="tabs-container">
            <button class="tab-btn active" onclick="openTab('profils')">👤 Profils</button>
            <button class="tab-btn" onclick="openTab('bonusi')">🎁 Bonusi</button>
            <button class="tab-btn" onclick="openTab('veikals')">🛍️ Veikals</button>
            <button class="tab-btn" onclick="openTab('vesture')">📜 Vēsture</button>
            <button class="tab-btn" onclick="openTab('manas')">⭐ Manas privilēģijas</button>
        </div>

        <!-- PROFILS TAB -->
        <div id="profils" class="tab-content active">
            <div class="profile-info-card">
                <h2 style="margin-bottom: 20px; color: #1e293b;">📋 Mana Informācija</h2>
                
                <div class="info-row">
                    <div class="info-label">Lietotājvārds:</div>
                    <div class="info-value"><?php echo htmlspecialchars($user['lietotajvards']); ?></div>
                </div>

                <div class="info-row">
                    <div class="info-label">E-pasts:</div>
                    <div class="info-value"><?php echo htmlspecialchars($user['epasts']); ?></div>
                </div>

                <div class="info-row">
                    <div class="info-label">Loma:</div>
                    <div class="info-value">
                        <?php echo $user['admin'] ? '👑 Administrators' : '👤 Lietotājs'; ?>
                    </div>
                </div>

                <div class="info-row">
                    <div class="info-label">Reģistrācijas datums:</div>
                    <div class="info-value"><?php echo date('d.m.Y H:i', strtotime($user['created_at'])); ?></div>
                </div>

                <div class="info-row">
                    <div class="info-label">Bonusu līmenis:</div>
                    <div class="info-value">
                        <strong style="color: #6366f1; font-size: 18px;">Līmenis <?php echo $stats['limenis']; ?></strong>
                    </div>
                </div>

                <div class="info-row">
                    <div class="info-label">Kopā bonusu:</div>
                    <div class="info-value">
                        <strong style="color: #10b981; font-size: 18px;"><?php echo number_format($stats['esosie_punkti']); ?> 💎</strong>
                    </div>
                </div>
            </div>

            <div style="text-align: center; margin-top: 30px;">
                <a href="logout.php" class="btn-logout">🚪 Iziet no konta</a>
            </div>
        </div>

        <!-- BONUSI TAB -->
        <div id="bonusi" class="tab-content">
            <div class="bonus-stats-grid">
                <div class="stat-card">
                    <h3>Pašreizējie Punkti</h3>
                    <div class="value"><?php echo number_format($stats['esosie_punkti']); ?></div>
                    <div class="label">Pieejami tērēšanai</div>
                </div>
                <div class="stat-card">
                    <h3>Kopā Nopelnīts</h3>
                    <div class="value"><?php echo number_format($stats['kopeja_nopelnita_summa']); ?></div>
                    <div class="label">Visu laiku</div>
                </div>
                <div class="stat-card">
                    <h3>Tavs Līmenis</h3>
                    <div class="value"><?php echo $stats['limenis']; ?></div>
                    <div class="label">Augošs spēks!</div>
                </div>
            </div>

            <div class="level-card">
                <div class="level-display">
                    <div class="level-badge"><?php echo $stats['limenis']; ?></div>
                    <div class="level-info">
                        <h2>Līmenis <?php echo $stats['limenis']; ?></h2>
                        <p><?php echo $tagadeja_pieredze; ?> / <?php echo $pieredze_limenim; ?> pieredzes punkti</p>
                    </div>
                </div>
                <div class="progress-bar">
                    <div class="progress-fill" style="width: <?php echo $progress_procenti; ?>%">
                        <?php echo round($progress_procenti); ?>%
                    </div>
                </div>
            </div>

            <div class="daily-bonus-section">
                <h2>🎁 Ikdienas Bonuss</h2>
                <p style="color: #64748b; margin: 10px 0 20px 0;">Saņem <?php echo BonusuSistema::BONUSS_DIENAS; ?> punktus katru dienu!</p>
                <button class="daily-bonus-btn" onclick="sanemtDienasBonusu()" id="dailyBonusBtn">
                    Saņemt Bonusu
                </button>
                <p id="bonusMessage" style="margin-top: 15px; font-weight: 600;"></p>
            </div>
        </div>

        <!-- VEIKALS TAB -->
        <div id="veikals" class="tab-content">
            <h2 style="margin-bottom: 25px; font-size: 28px;">🛍️ Privilēģiju Veikals</h2>
            <div class="shop-grid">
                <?php foreach ($privilegijas as $priv): ?>
                    <?php 
                    $ir_nopirkta = false;
                    foreach ($manas_privilegijas as $mp) {
                        if ($mp['id'] == $priv['id']) {
                            $ir_nopirkta = true;
                            break;
                        }
                    }
                    ?>
                    <div class="privilege-card">
                        <div class="privilege-icon"><?php echo $priv['ikona']; ?></div>
                        <h3><?php echo htmlspecialchars($priv['nosaukums']); ?></h3>
                        <p><?php echo htmlspecialchars($priv['apraksts']); ?></p>
                        <div class="privilege-price">
                            <span class="price-tag"><?php echo number_format($priv['cena']); ?> 💎</span>
                            <?php if ($ir_nopirkta): ?>
                                <span class="owned-badge">✓ Pieder</span>
                            <?php else: ?>
                                <button class="buy-btn" onclick="nopirktPrivilegiju(<?php echo $priv['id']; ?>)">
                                    Nopirkt
                                </button>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- VĒSTURE TAB -->
        <div id="vesture" class="tab-content">
            <h2 style="margin-bottom: 25px; font-size: 28px;">📜 Transakciju Vēsture</h2>
            <div class="history-list">
                <?php if (empty($vesture)): ?>
                    <div class="empty-state">
                        <div class="empty-state-icon">📜</div>
                        <h3>Nav transakciju</h3>
                        <p>Sāc nopelnīt bonusus, lai redzētu vēsturi!</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($vesture as $trans): ?>
                        <div class="history-item">
                            <div class="history-info">
                                <h4><?php echo htmlspecialchars($trans['apraksts']); ?></h4>
                                <small><?php echo date('d.m.Y H:i', strtotime($trans['izveidots'])); ?></small>
                            </div>
                            <div class="history-points <?php echo $trans['punkti'] > 0 ? 'positive' : 'negative'; ?>">
                                <?php echo $trans['punkti'] > 0 ? '+' : ''; ?><?php echo number_format($trans['punkti']); ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <!-- MANAS PRIVILĒĢIJAS TAB -->
        <div id="manas" class="tab-content">
            <h2 style="margin-bottom: 25px; font-size: 28px;">⭐ Manas Privilēģijas</h2>
            <?php if (empty($manas_privilegijas)): ?>
                <div class="empty-state">
                    <div class="empty-state-icon">🎁</div>
                    <h3>Tev vēl nav privilēģiju</h3>
                    <p>Apmeklē veikalu, lai iegādātos pirmo!</p>
                    <button class="buy-btn" onclick="openTab('veikals')" style="margin-top: 20px; padding: 12px 30px; font-size: 16px;">
                        Doties uz veikalu →
                    </button>
                </div>
            <?php else: ?>
                <div class="my-privileges-grid">
                    <?php foreach ($manas_privilegijas as $priv): ?>
                        <div class="my-privilege-card">
                            <div class="privilege-icon"><?php echo $priv['ikona']; ?></div>
                            <h3><?php echo htmlspecialchars($priv['nosaukums']); ?></h3>
                            <p><?php echo htmlspecialchars($priv['apraksts']); ?></p>
                            <small style="color: #94a3b8;">
                                Iegūts: <?php echo date('d.m.Y', strtotime($priv['iegutes'])); ?>
                            </small>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
        function openTab(tabName) {
            // Paslēpjam visus tab
            const contents = document.querySelectorAll('.tab-content');
            contents.forEach(content => content.classList.remove('active'));
            
            // Noņemam active no pogām
            const buttons = document.querySelectorAll('.tab-btn');
            buttons.forEach(btn => btn.classList.remove('active'));
            
            // Aktivizējam izvēlēto
            document.getElementById(tabName).classList.add('active');
            event.target.classList.add('active');
        }

        function sanemtDienasBonusu() {
            const btn = document.getElementById('dailyBonusBtn');
            const message = document.getElementById('bonusMessage');
            
            btn.disabled = true;
            btn.textContent = 'Apstrādā...';
            
            fetch('sanemt_dienas_bonusu.php', {
                method: 'POST'
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    message.style.color = '#10b981';
                    message.textContent = '✅ ' + data.message;
                    setTimeout(() => location.reload(), 1500);
                } else {
                    message.style.color = '#ef4444';
                    message.textContent = '❌ ' + data.message;
                    btn.disabled = false;
                    btn.textContent = 'Saņemt Bonusu';
                }
            })
            .catch(error => {
                message.style.color = '#ef4444';
                message.textContent = '❌ Kļūda savienojumā';
                btn.disabled = false;
                btn.textContent = 'Saņemt Bonusu';
            });
        }

        function nopirktPrivilegiju(privilegijaId) {
            if (!confirm('Vai tiešām vēlies nopirkt šo privilēģiju?')) {
                return;
            }
            
            fetch('nopirkt_privilegiju.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ privilegija_id: privilegijaId })
            })
            .then(response => response.json())
            .then(data => {
                alert(data.message);
                if (data.success) {
                    location.reload();
                }
            })
            .catch(error => {
                alert('❌ Kļūda savienojumā');
            });
        }

        // Pārbaudām vai šodien jau saņemts bonuss
        window.addEventListener('DOMContentLoaded', function() {
            const lastBonus = '<?php echo $stats['pedeja_dienas_balva'] ?? ''; ?>';
            const today = new Date().toISOString().split('T')[0];
            
            if (lastBonus === today) {
                const btn = document.getElementById('dailyBonusBtn');
                const message = document.getElementById('bonusMessage');
                if (btn) {
                    btn.disabled = true;
                    btn.textContent = 'Jau saņemts šodien';
                }
                if (message) {
                    message.style.color = '#64748b';
                    message.textContent = 'Atgriezies rīt pēc jauna bonusa!';
                }
            }
        });
    </script>
</body>
</html>
>>>>>>> 5b26ea7 (Pievienoti faili ar bonusu sistemu)
