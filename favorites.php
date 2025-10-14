<?php
session_start();
require_once __DIR__ . '/vendor/autoload.php';

// ✅ Ja lietotājs nav ielogojies, pāradresē uz login
if (!isset($_SESSION['epasts'])) {
    echo "<script>alert('Lūdzu pieslēdzies, lai redzētu favorītus!'); window.location.href='login.html';</script>";
    exit;
}

// DB savienojums
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->safeLoad();

$conn = new mysqli(
    $_ENV['DB_HOST'] ?? 'localhost',
    $_ENV['DB_USER'] ?? 'root',
    $_ENV['DB_PASS'] ?? '',
    $_ENV['DB_NAME'] ?? 'dzivnieku_patversme',
    $_ENV['DB_PORT'] ?? 3306
);
if ($conn->connect_error) {
    die("Savienojuma kļūda: " . $conn->connect_error);
}

// Lietotāja ID
$user_email = $_SESSION['epasts'];
$userQuery = $conn->prepare("SELECT id FROM lietotaji WHERE epasts = ?");
$userQuery->bind_param("s", $user_email);
$userQuery->execute();
$userRes = $userQuery->get_result();
$user = $userRes->fetch_assoc();
$user_id = $user['id'];

// Dzēš no favorītiem
if (isset($_POST['remove_id'])) {
    $pet_id = intval($_POST['remove_id']);
    $del = $conn->prepare("DELETE FROM favorites WHERE user_id = ? AND pet_id = ?");
    $del->bind_param("ii", $user_id, $pet_id);
    $del->execute();
}

// Atlasa favorītus
$query = $conn->prepare("
    SELECT d.id, d.vards, d.suga, d.attels, d.statuss, d.vecums, d.dzimums
    FROM favorites f
    JOIN dzivnieki d ON f.pet_id = d.id
    WHERE f.user_id = ?
");
$query->bind_param("i", $user_id);
$query->execute();
$result = $query->get_result();
?>

<!DOCTYPE html>
<html lang="lv">
<head>
  <meta charset="UTF-8">
  <title>Mani favorīti — SirdsPaws</title>
  <link rel="stylesheet" href="index.css">
  <style>
    body {
      background-color: #f8fafc;
      font-family: 'Inter', sans-serif;
    }

    .favorites-container {
      width: 90%;
      max-width: 1200px;
      margin: 60px auto;
      text-align: center;
    }

    .favorites-container h1 {
      font-size: 2rem;
      color: #4f46e5;
      margin-bottom: 0.5rem;
    }

    .favorites-container p {
      color: #6b7280;
      margin-bottom: 2rem;
    }

    .pets-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
      gap: 30px;
    }

    .pet-card {
      background: white;
      border-radius: 16px;
      box-shadow: 0 6px 16px rgba(0, 0, 0, 0.08);
      overflow: hidden;
      transition: transform 0.25s ease, box-shadow 0.25s ease;
      position: relative;
    }

    .pet-card:hover {
      transform: translateY(-6px);
      box-shadow: 0 10px 20px rgba(0,0,0,0.15);
    }

    .pet-image img {
      width: 100%;
      height: 220px;
      object-fit: cover;
    }

    .pet-info {
      padding: 1rem 1.2rem;
    }

    .pet-info h3 {
      margin: 0;
      color: #1f2937;
      font-size: 1.25rem;
      font-weight: 600;
    }

    .pet-info .pet-suga {
      color: #6b7280;
      font-size: 0.9rem;
      margin-bottom: 8px;
    }

    .pet-badge {
      position: absolute;
      top: 10px;
      left: 10px;
      background: #4f46e5;
      color: white;
      font-size: 0.8rem;
      font-weight: bold;
      padding: 4px 10px;
      border-radius: 8px;
      text-transform: uppercase;
    }

    .pet-badge.unavailable {
      background: #9ca3af;
    }

    .remove-btn {
      background: linear-gradient(90deg, #ef4444, #dc2626);
      color: white;
      border: none;
      padding: 10px 20px;
      border-radius: 10px;
      cursor: pointer;
      font-weight: 600;
      transition: transform 0.2s, background 0.2s;
      margin-top: 10px;
    }

    .remove-btn:hover {
      transform: scale(1.05);
      background: linear-gradient(90deg, #dc2626, #b91c1c);
    }

    .empty {
      margin-top: 60px;
      font-size: 1.2rem;
      color: #6b7280;
      display: flex;
      flex-direction: column;
      align-items: center;
    }

    .empty span {
      font-size: 3rem;
      margin-bottom: 10px;
    }
  </style>
</head>
<body>
  <?php include 'navbar.php'; ?>

  <div class="favorites-container">
    <h1>🐾 Mani favorīti</h1>
    <p>Šie ir dzīvnieki, kas tev īpaši iepatikušies 💜</p>

    <div class="pets-grid">
      <?php if ($result->num_rows > 0): ?>
        <?php while ($row = $result->fetch_assoc()): ?>
          <div class="pet-card">
            <div class="pet-image">
              <img src="<?= htmlspecialchars($row['attels']) ?: 'kitty.jpg' ?>" alt="<?= htmlspecialchars($row['vards']) ?>">
              <div class="pet-badge <?= $row['statuss'] !== 'pieejams' ? 'unavailable' : '' ?>">
                <?= htmlspecialchars($row['statuss']) ?>
              </div>
            </div>

            <div class="pet-info">
              <h3><?= htmlspecialchars($row['vards']) ?></h3>
              <div class="pet-suga"><?= htmlspecialchars($row['suga']) ?></div>
              <p style="color:#6b7280; font-size:0.9rem;">
                <?= !empty($row['vecums']) ? $row['vecums'] . ' gadu vecs ' : '' ?>
                <?= !empty($row['dzimums']) ? '(' . htmlspecialchars($row['dzimums']) . ')' : '' ?>
              </p>

              <form method="POST" action="">
                <input type="hidden" name="remove_id" value="<?= $row['id'] ?>">
                <button type="submit" class="remove-btn">🗑 Noņemt no favorītiem</button>
              </form>
            </div>
          </div>
        <?php endwhile; ?>
      <?php else: ?>
        <div class="empty">
          <span>💔</span>
          <p>Tu vēl neesi pievienojis nevienu dzīvnieku favorītos</p>
        </div>
      <?php endif; ?>
    </div>
  </div>

  <?php include 'footer.php'; ?>
</body>
</html>