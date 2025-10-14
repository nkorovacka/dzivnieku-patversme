<?php
ini_set('session.cookie_path', '/');
ini_set('session.cookie_lifetime', 86400);
ini_set('session.gc_maxlifetime', 86400);
ini_set('session.cookie_secure', false); // true ja izmanto HTTPS
ini_set('session.cookie_httponly', true);
session_start();
require_once __DIR__ . '/vendor/autoload.php';

// Ielādē .env faila datus
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->safeLoad();

// Izveido savienojumu ar datubāzi
$conn = new mysqli(
    $_ENV['DB_HOST'] ?? 'localhost',
    $_ENV['DB_USER'] ?? 'root',
    $_ENV['DB_PASS'] ?? '',
    $_ENV['DB_NAME'] ?? 'dzivnieku_patversme',
    $_ENV['DB_PORT'] ?? 3306
);

if ($conn->connect_error) {
    die("❌ Neizdevās izveidot savienojumu ar datubāzi: " . $conn->connect_error);
}

// Iegūst visus pieejamos dzīvniekus
$query = "SELECT * FROM dzivnieki WHERE statuss = 'pieejams'";
$result = $conn->query($query);

$pets = [];
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $pets[] = $row;
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
    <link rel="stylesheet" href="pets.css">
</head>
<body>
    <?php include 'navbar.php'; ?>
    <main class="container">
        <section class="main-content">
            <div class="search-section">
                <div class="search-container">
                    <input type="text" class="search-input" id="searchInput" placeholder="Meklēt dzīvniekus pēc vārda...">
                    <select class="filter-select" id="typeFilter">
                        <option value="">Visi dzīvnieki</option>
                        <option value="suns">Suņi</option>
                        <option value="kaķis">Kaķi</option>
                        <option value="trusis">Truši</option>
                    </select>
                    <select class="filter-select" id="ageFilter">
                        <option value="">Jebkurš vecums</option>
                        <option value="1">Mazuļi (0–1 gads)</option>
                        <option value="3">Jauni (1–3 gadi)</option>
                        <option value="4">Pieauguši (3+ gadi)</option>
                    </select>
                </div>
            </div>

            <div class="pets-grid" id="petsGrid">
                <?php if (count($pets) > 0): ?>
                    <?php foreach ($pets as $pet): ?>
                        <div class="pet-card" 
                             data-name="<?= strtolower($pet['vards']) ?>" 
                             data-type="<?= strtolower($pet['suga']) ?>" 
                             data-age="<?= (int)$pet['vecums'] ?>">
                            <div class="pet-image">
                                <?php if (!empty($pet['attels'])): ?>
                                    <img src="<?= htmlspecialchars($pet['attels']) ?>" alt="<?= htmlspecialchars($pet['vards']) ?>">
                                <?php else: ?>
                                    <img src="kitty.jpg" alt="Nav attēla">
                                <?php endif; ?>
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
                                    <?php if (!empty($pet['sugas_veids'])): ?>
                                        <span class="pet-detail"><?= htmlspecialchars($pet['sugas_veids']) ?></span>
                                    <?php endif; ?>
                                </div>
                                <p class="pet-description">
                                    <?= htmlspecialchars($pet['apraksts'] ?? 'Apraksts nav pieejams.') ?>
                                </p>

                                <div class="pet-actions">
                                    <form action="adopt_pet.php" method="POST" style="display:inline;">
                                        <input type="hidden" name="pet_id" value="<?= $pet['id'] ?>">
                                        <button type="submit" class="btn btn-adopt">Adoptēt</button>
                                    </form>
                                    <form action="toggle_favorite.php" method="POST" style="flex: 1;">
                                        <input type="hidden" name="pet_id" value="<?= $pet['id'] ?>">
                                        <button class="btn btn-favorite" type="submit">❤</button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="empty-state" style="grid-column: 1 / -1;">
                        <div class="empty-state-icon">🐾</div>
                        <h3>Nav pieejamu dzīvnieku</h3>
                        <p>Patlaban visi mūsu mīluļi ir atraduši mājas 💕</p>
                    </div>
                <?php endif; ?>
            </div>
        </section>
    </main>

    <footer style="background: #1a1a2e; color: white; padding: 3rem 0 1rem 0;">
        <div class="container">
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 3rem; margin-bottom: 2rem;">
                <div>
                    <h3 style="color: #667eea; margin-bottom: 1.5rem; font-size: 1.8rem; font-weight: 700;">
                        🐾 SirdsPaws
                    </h3>
                    <p style="margin-bottom: 1.5rem; line-height: 1.8; color: #b8b8c8;">
                        Palīdzam dzīvniekiem atrast mīlošas mājas un cilvēkiem — uzticamus draugus.
                    </p>
                </div>
                <div>
                    <h4 style="color: white; margin-bottom: 1rem; font-weight: 600;">Kontakti</h4>
                    <div style="color: #b8b8c8; line-height: 2;">
                        <div>📍 Daugavgrīvas iela 123, Rīga</div>
                        <div>📞 +371 26 123 456</div>
                        <div>✉️ info@sirdspaws.lv</div>
                    </div>
                </div>
                <div>
                    <h4 style="color: white; margin-bottom: 1rem; font-weight: 600;">Saites</h4>
                    <div style="color: #b8b8c8; line-height: 2;">
                        <a href="pets.php" style="color: #b8b8c8; text-decoration: none;">Dzīvnieki</a><br>
                        <a href="events.html" style="color: #b8b8c8; text-decoration: none;">Pasākumi</a><br>
                        <a href="register.html" style="color: #b8b8c8; text-decoration: none;">Reģistrēties</a>
                    </div>
                </div>
                <div>
                    <h4 style="color: white; margin-bottom: 1rem; font-weight: 600;">Darba laiks</h4>
                    <div style="color: #b8b8c8; line-height: 2;">
                        <div>P–Pk: 9:00–18:00</div>
                        <div>S: 10:00–16:00</div>
                        <div>Sv: 10:00–14:00</div>
                    </div>
                </div>
            </div>
            <div style="border-top: 1px solid rgba(255,255,255,0.1); padding-top: 2rem; text-align: center; color: #b8b8c8;">
                <p style="margin: 0;">© 2025 SirdsPaws. Radīts ar ❤️ dzīvniekiem</p>
            </div>
        </div>
    </footer>

    <script>
        // Vienkārša meklēšana un filtri klienta pusē
        const searchInput = document.getElementById('searchInput');
        const typeFilter = document.getElementById('typeFilter');
        const ageFilter = document.getElementById('ageFilter');
        const petsGrid = document.getElementById('petsGrid');

        function filterPets() {
            const searchTerm = searchInput.value.toLowerCase();
            const type = typeFilter.value;
            const age = parseInt(ageFilter.value) || 0;

            const cards = petsGrid.querySelectorAll('.pet-card');
            cards.forEach(card => {
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

        searchInput.addEventListener('input', filterPets);
        typeFilter.addEventListener('change', filterPets);
        ageFilter.addEventListener('change', filterPets);
    </script>
</body>
</html>
