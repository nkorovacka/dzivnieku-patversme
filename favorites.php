<?php
session_start();

// Проверка авторизации
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

require_once 'db.php';

$user_id = $_SESSION['user_id'];

// Получение избранных животных пользователя
$query = "SELECT p.*, 
          (SELECT photo_url FROM pet_photos WHERE pet_id = p.id LIMIT 1) as photo_url
          FROM pets p
          INNER JOIN favorites f ON p.id = f.pet_id
          WHERE f.user_id = ?
          ORDER BY f.created_at DESC";

$stmt = $conn->prepare($query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$favorites = $result->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="lv">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Favorīti - SirdsPaws</title>
    <link rel="stylesheet" href="index.css">
    <style>
        .favorites-hero {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 4rem 0;
            text-align: center;
            position: relative;
            overflow: hidden;
        }

        .favorites-hero::before {
            content: '';
            position: absolute;
            width: 400px;
            height: 400px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 50%;
            top: -100px;
            right: -50px;
            animation: float 20s infinite ease-in-out;
        }

        .favorites-hero::after {
            content: '';
            position: absolute;
            width: 300px;
            height: 300px;
            background: rgba(255, 255, 255, 0.08);
            border-radius: 50%;
            bottom: -100px;
            left: -50px;
            animation: float 15s infinite ease-in-out reverse;
        }

        .favorites-hero .container {
            position: relative;
            z-index: 1;
        }

        .favorites-hero h1 {
            font-size: 3rem;
            margin-bottom: 1rem;
            font-weight: 800;
            letter-spacing: -1px;
        }

        .favorites-hero p {
            font-size: 1.3rem;
            opacity: 0.95;
            font-weight: 300;
        }

        .favorites-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 4rem 2rem;
        }

        .pets-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 2rem;
            margin-top: 2rem;
        }

        .pet-card {
            background: white;
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            position: relative;
            border: 1px solid #e8ecf1;
        }

        .pet-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 12px 30px rgba(102, 126, 234, 0.2);
        }

        .pet-card-image {
            width: 100%;
            height: 250px;
            object-fit: cover;
        }

        .pet-card-content {
            padding: 1.5rem;
        }

        .pet-card h3 {
            font-size: 1.5rem;
            color: #1a1a2e;
            margin-bottom: 0.5rem;
            font-weight: 700;
        }

        .pet-info {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
            margin: 1rem 0;
        }

        .pet-info-item {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            color: #666;
            font-size: 0.95rem;
        }

        .pet-info-item span {
            font-weight: 600;
            color: #475569;
        }

        .pet-card-actions {
            display: flex;
            gap: 1rem;
            margin-top: 1rem;
        }

        .btn {
            padding: 0.75rem 1.5rem;
            border-radius: 10px;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s ease;
            text-align: center;
            cursor: pointer;
            border: none;
            font-size: 1rem;
        }

        .btn-primary {
            background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%);
            color: white;
            flex: 1;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(99, 102, 241, 0.4);
        }

        .btn-remove {
            background: #ff6b6b;
            color: white;
            padding: 0.75rem;
            width: 45px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .btn-remove:hover {
            background: #ee5a5a;
            transform: scale(1.05);
        }

        .empty-state {
            text-align: center;
            padding: 5rem 2rem;
            background: linear-gradient(135deg, #f8f9ff 0%, #f0f2ff 100%);
            border-radius: 20px;
            margin: 2rem 0;
        }

        .empty-state-icon {
            font-size: 6rem;
            margin-bottom: 1.5rem;
            opacity: 0.7;
        }

        .empty-state h2 {
            font-size: 2.2rem;
            color: #1a1a2e;
            margin-bottom: 1rem;
            font-weight: 700;
        }

        .empty-state p {
            font-size: 1.2rem;
            color: #666;
            margin-bottom: 2rem;
            line-height: 1.6;
        }

        .favorite-badge {
            position: absolute;
            top: 1rem;
            right: 1rem;
            background: white;
            border-radius: 50%;
            width: 50px;
            height: 50px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            z-index: 10;
            transition: all 0.3s ease;
        }

        .favorite-badge:hover {
            transform: scale(1.1);
        }

        .favorite-badge.active {
            background: #ff6b6b;
        }

        .favorite-badge svg {
            width: 26px;
            height: 26px;
            fill: #ff6b6b;
        }

        .favorite-badge.active svg {
            fill: white;
        }

        @media (max-width: 768px) {
            .pets-grid {
                grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
                gap: 1.5rem;
            }

            .favorites-hero h1 {
                font-size: 2.2rem;
            }

            .favorites-hero p {
                font-size: 1.1rem;
            }
        }
    </style>
</head>
<body>

<!-- Header -->
<header class="main-header">
    <nav class="nav-container">
        <a href="index.php" class="logo">🐾 SirdsPaws</a>
        
        <ul class="nav-links">
            <li><a href="index.php">Sākums</a></li>
            <li><a href="pets.php">Dzīvnieki</a></li>
            <li><a href="events.php">Pasākumi</a></li>
            <?php if (isset($_SESSION['user_id'])): ?>
                <li><a href="favorites.php" class="active">Favorīti</a></li>
                <li><a href="applications.php">Pieteikumi</a></li>
            <?php endif; ?>
        </ul>

        <div class="auth-links">
            <?php if (isset($_SESSION['user_id'])): ?>
                <a href="profile.php">Profils</a>
                <a href="logout.php">Iziet</a>
            <?php else: ?>
                <a href="login.php">Ieiet</a>
                <a href="register.php">Reģistrēties</a>
            <?php endif; ?>
        </div>
    </nav>
</header>

<!-- Hero Section -->
<section class="favorites-hero">
    <div class="container">
        <h1>💖 Mani favorītie dzīvnieki</h1>
        <p>Šeit Tu vari redzēt visus dzīvniekus, kurus esi pievienojis favorītos</p>
    </div>
</section>

<!-- Favorites Content -->
<section class="favorites-container">
    <?php if (empty($favorites)): ?>
        <div class="empty-state">
            <div class="empty-state-icon">🐾</div>
            <h2>Tavs favorītu saraksts ir tukšs</h2>
            <p>Sāc pievienot dzīvniekus favorītos, lai viņi parādītos šeit!<br>Katrs dzīvnieks ir īpašs un gaida savu otro iespēju.</p>
            <a href="pets.php" class="btn btn-primary" style="display: inline-block; margin-top: 1rem;">Skatīt dzīvniekus</a>
        </div>
    <?php else: ?>
        <div class="pets-grid">
            <?php foreach ($favorites as $pet): ?>
                <div class="pet-card">
                    <div class="favorite-badge active" data-pet-id="<?php echo $pet['id']; ?>" onclick="toggleFavorite(<?php echo $pet['id']; ?>)">
                        <svg viewBox="0 0 24 24">
                            <path d="M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z"/>
                        </svg>
                    </div>
                    
                    <img src="<?php echo htmlspecialchars($pet['photo_url'] ?? 'images/default-pet.jpg'); ?>" 
                         alt="<?php echo htmlspecialchars($pet['name']); ?>" 
                         class="pet-card-image">
                    
                    <div class="pet-card-content">
                        <h3><?php echo htmlspecialchars($pet['name']); ?></h3>
                        
                        <div class="pet-info">
                            <div class="pet-info-item">
                                <span>Suga:</span> <?php echo htmlspecialchars($pet['species']); ?>
                            </div>
                            <div class="pet-info-item">
                                <span>Vecums:</span> <?php echo htmlspecialchars($pet['age']); ?> gadi
                            </div>
                            <div class="pet-info-item">
                                <span>Dzimums:</span> <?php echo $pet['gender'] == 'male' ? 'Tēviņš' : 'Mātīte'; ?>
                            </div>
                            <?php if ($pet['breed']): ?>
                            <div class="pet-info-item">
                                <span>Šķirne:</span> <?php echo htmlspecialchars($pet['breed']); ?>
                            </div>
                            <?php endif; ?>
                        </div>

                        <div class="pet-card-actions">
                            <a href="pet_details.php?id=<?php echo $pet['id']; ?>" class="btn btn-primary">
                                Skatīt profilu
                            </a>
                            <button class="btn btn-remove" onclick="toggleFavorite(<?php echo $pet['id']; ?>)" title="Noņemt no favorītiem">
                                ✕
                            </button>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</section>

<!-- Footer -->
<footer style="background: #1a1a2e; color: white; padding: 3rem 0 1rem 0;">
    <div class="container">
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 3rem; margin-bottom: 2rem;">
            <div>
                <h3 style="color: #667eea; margin-bottom: 1.5rem; font-size: 1.8rem; font-weight: 700;">
                    🐾 SirdsPaws
                </h3>
                <p style="margin-bottom: 1.5rem; line-height: 1.8; color: #b8b8c8;">
                    Palīdzam dzīvniekiem atrast mīlošas mājas un cilvēkiem - uzticamus draugus.
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
                    <div><a href="pets.php" style="color: #b8b8c8; text-decoration: none;">Dzīvnieki</a></div>
                    <div><a href="events.php" style="color: #b8b8c8; text-decoration: none;">Pasākumi</a></div>
                    <div><a href="register.php" style="color: #b8b8c8; text-decoration: none;">Reģistrēties</a></div>
                </div>
            </div>

            <div>
                <h4 style="color: white; margin-bottom: 1rem; font-weight: 600;">Darba laiks</h4>
                <div style="color: #b8b8c8; line-height: 2;">
                    <div>P-Pk: 9:00 - 18:00</div>
                    <div>S: 10:00 - 16:00</div>
                    <div>Sv: 10:00 - 14:00</div>
                </div>
            </div>
        </div>

        <div style="border-top: 1px solid rgba(255,255,255,0.1); padding-top: 2rem; text-align: center; color: #b8b8c8;">
            <p style="margin: 0;">© 2025 SirdsPaws. Radīts ar ❤️ dzīvniekiem</p>
        </div>
    </div>
</footer>

<script>
function toggleFavorite(petId) {
    fetch('toggle_favorite.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({ pet_id: petId })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Перезагрузить страницу для обновления списка
            location.reload();
        } else {
            alert('Произошла ошибка: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Произошла ошибка при обновлении избранного');
    });
}
</script>

</body>
</html>