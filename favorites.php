<?php
session_start();
require_once __DIR__ . '/vendor/autoload.php';

// ✅ Ja lietotājs nav ielogojies, pāradresē uz login
if (!isset($_SESSION['epasts'])) {
    echo "<script>alert('Lūdzu pieslēdzies, lai redzētu favorītus!'); window.location.href='login.html';</script>";
    exit;
}

// 🔧 DB savienojums
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

// 🔍 Lietotāja ID
$user_email = $_SESSION['epasts'];
$stmt = $conn->prepare("SELECT id FROM lietotaji WHERE epasts = ?");
$stmt->bind_param("s", $user_email);
$stmt->execute();
$userRes = $stmt->get_result();
$user = $userRes->fetch_assoc();
$user_id = $user['id'];

// 🗑 Dzēš no favorītiem
if (isset($_POST['remove_id'])) {
    $pet_id = intval($_POST['remove_id']);
    $del = $conn->prepare("DELETE FROM favorites WHERE user_id = ? AND pet_id = ?");
    $del->bind_param("ii", $user_id, $pet_id);
    $del->execute();
}

// 🐾 Atlasa favorītus ar dzīvnieku datiem
$q = $conn->prepare("
  SELECT d.id, d.vards, d.suga, d.attels, d.statuss, d.vecums, d.dzimums, d.apraksts
  FROM favorites f
  JOIN dzivnieki d ON f.pet_id = d.id
  WHERE f.user_id = ?
");
$q->bind_param("i", $user_id);
$q->execute();
$favorites = $q->get_result();
?>

<!DOCTYPE html>
<html lang="lv">
<head>
  <meta charset="UTF-8">
  <title>Mani favorīti — SirdsPaws</title>
  <link rel="stylesheet" href="index.css">
  <link rel="stylesheet" href="pets.css?v=5">
</head>
<body>
  <?php include 'navbar.php'; ?>

  <main class="main-container">
    <section class="main-content">
      <div class="search-section" style="text-align:center;">
        <h1>🐾 Mani favorīti</h1>
        <p>Šie ir dzīvnieki, kas tev īpaši iepatikušies 💜</p>
      </div>

      <div class="pets-grid">
        <?php if ($favorites->num_rows > 0): ?>
          <?php while ($pet = $favorites->fetch_assoc()): ?>
            <?php
              $status = strtolower($pet['statuss'] ?? 'pieejams');
              switch ($status) {
                  case 'adoptets':
                      $statusClass = 'status-adopted';
                      $statusText = 'Adoptēts';
                      break;
                  case 'procesā':
                  case 'pending':
                  case 'rezervets':
                      $statusClass = 'status-pending';
                      $statusText = 'Adopcijas procesā';
                      break;
                  default:
                      $statusClass = 'status-available';
                      $statusText = 'Pieejams adoptēšanai';
              }
            ?>
            <div class="pet-card">
              <div class="pet-image">
                <img src="<?= htmlspecialchars($pet['attels'] ?: 'kitty.jpg') ?>" alt="<?= htmlspecialchars($pet['vards']) ?>">
                <span class="pet-status <?= $statusClass ?>"><?= $statusText ?></span>
              </div>

              <div class="pet-info">
                <div class="pet-header">
                  <h3 class="pet-name"><?= htmlspecialchars($pet['vards']) ?></h3>
                  <span class="pet-type"><?= htmlspecialchars($pet['suga']) ?></span>
                </div>

                <div class="pet-details">
                  <?php if (!empty($pet['vecums'])): ?>
                    <span class="pet-detail"><?= htmlspecialchars($pet['vecums']) ?> g.</span>
                  <?php endif; ?>
                  <?php if (!empty($pet['dzimums'])): ?>
                    <span class="pet-detail"><?= htmlspecialchars($pet['dzimums']) ?></span>
                  <?php endif; ?>
                </div>

                <p class="pet-description"><?= htmlspecialchars($pet['apraksts'] ?? 'Apraksts nav pieejams.') ?></p>

                <div class="pet-actions">
                  <?php if ($status === 'pieejams'): ?>
                    <form action="adopt_form.php" method="GET">
                      <input type="hidden" name="pet_id" value="<?= $pet['id'] ?>">
                      <button type="submit" class="btn btn-adopt">Adoptēt</button>
                    </form>
                  <?php else: ?>
                    <button class="btn btn-adopt" disabled><?= $statusText ?></button>
                  <?php endif; ?>

                  <form method="POST" action="">
                    <input type="hidden" name="remove_id" value="<?= $pet['id'] ?>">
                    <button type="submit" class="btn btn-favorite active">🗑 Noņemt</button>
                  </form>
                </div>
              </div>
            </div>
          <?php endwhile; ?>
        <?php else: ?>
          <div class="empty-state">
            <div class="empty-state-icon">💔</div>
            <h3>Tu vēl neesi pievienojis nevienu dzīvnieku favorītos</h3>
          </div>
        <?php endif; ?>
      </div>
    </section>
  </main>

  <?php include 'footer.php'; ?>
</body>
</html>