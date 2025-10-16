<?php
session_start();
require_once __DIR__ . '/vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->safeLoad();

// Pieslēgums datubāzei
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

// Nolasām visus dzīvniekus
$result = $conn->query("SELECT * FROM dzivnieki");
$pets = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];

// Ja lietotājs ir ielogojies, iegūst favorītus
$favorites = [];
if (isset($_SESSION['user_id'])) {
  $uid = intval($_SESSION['user_id']);
  $favQuery = $conn->query("SELECT pet_id FROM favorites WHERE user_id = $uid");
  while ($f = $favQuery->fetch_assoc()) {
    $favorites[] = (int)$f['pet_id'];
  }
}
?>
<!DOCTYPE html>
<html lang="lv">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Dzīvnieki — SirdsPaws</title>
  <link rel="stylesheet" href="index.css">
  <link rel="stylesheet" href="pets.css?v=6">
</head>
<body>

<?php include 'navbar.php'; ?>

<main class="main-container">
  <section class="main-content">

    <div class="search-section">
      <div class="search-container">
        <input type="text" id="searchInput" class="search-input" placeholder="Meklēt dzīvniekus pēc vārda...">
        <select id="typeFilter" class="filter-select">
          <option value="">Visi dzīvnieki</option>
          <option value="suns">Suņi</option>
          <option value="kaķis">Kaķi</option>
          <option value="trusis">Truši</option>
        </select>
        <select id="ageFilter" class="filter-select">
          <option value="">Jebkurš vecums</option>
          <option value="1">Mazuļi (0–1 gads)</option>
          <option value="3">Jauni (1–3 gadi)</option>
          <option value="4">Pieauguši (3+ gadi)</option>
        </select>
      </div>
    </div>

    <div class="pets-grid" id="petsGrid">
      <?php if (!empty($pets)): ?>
        <?php foreach ($pets as $pet): ?>
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
          <div class="pet-card"
               data-name="<?= strtolower($pet['vards']) ?>"
               data-type="<?= strtolower($pet['suga']) ?>"
               data-age="<?= (int)$pet['vecums'] ?>">

            <div class="pet-image">
              <img src="<?= !empty($pet['attels']) ? htmlspecialchars($pet['attels']) : 'kitty.jpg' ?>" 
                   alt="<?= htmlspecialchars($pet['vards']) ?>">
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
                  <form action="adopt_form.php" method="get" onsubmit="return confirmAdopt('<?= htmlspecialchars($pet['vards']) ?>')">
                    <input type="hidden" name="pet_id" value="<?= $pet['id'] ?>">
                    <button type="submit" class="btn btn-adopt">🐾 Adoptēt</button>
                  </form>
                <?php else: ?>
                  <button class="btn btn-adopt" disabled><?= $statusText ?></button>
                <?php endif; ?>

                <form action="toggle_favorite.php" method="POST">
                  <input type="hidden" name="pet_id" value="<?= $pet['id'] ?>">
                  <button class="btn btn-favorite <?= in_array($pet['id'], $favorites) ? 'active' : '' ?>" type="submit">❤</button>
                </form>
              </div>
            </div>
          </div>
        <?php endforeach; ?>
      <?php else: ?>
        <div class="empty-state">
          <div class="empty-state-icon">🐾</div>
          <h3>Nav pieejamu dzīvnieku</h3>
          <p>Patlaban visi mūsu mīluļi ir atraduši mājas 💕</p>
        </div>
      <?php endif; ?>
    </div>
  </section>
</main>

<footer style="text-align:center; padding:2rem; color:#888;">
  <p>© 2025 SirdsPaws — Mīlestība katram dzīvniekam 🐾</p>
</footer>

<script>
function confirmAdopt(name) {
  return confirm(`Vai tiešām vēlies adoptēt ${name}?`);
}

const searchInput = document.getElementById('searchInput');
const typeFilter = document.getElementById('typeFilter');
const ageFilter = document.getElementById('ageFilter');
const petsGrid = document.getElementById('petsGrid');

function filterPets() {
  const searchTerm = searchInput.value.toLowerCase();
  const type = typeFilter.value;
  const age = parseInt(ageFilter.value) || 0;

  petsGrid.querySelectorAll('.pet-card').forEach(card => {
    const name = card.dataset.name;
    const petType = card.dataset.type;
    const petAge = parseInt(card.dataset.age);

    const matchesSearch = name.includes(searchTerm);
    const matchesType = !type || petType === type;
    const matchesAge =
      !age ||
      (age === 1 && petAge <= 1) ||
      (age === 3 && petAge > 1 && petAge <= 3) ||
      (age === 4 && petAge > 3);

    card.style.display = (matchesSearch && matchesType && matchesAge) ? '' : 'none';
  });
}

[searchInput, typeFilter, ageFilter].forEach(el => el.addEventListener('input', filterPets));
</script>

</body>
</html>
