<?php
// Пока убираем авторизацию и базу данных
// session_start();

// Пример данных, будто бы это пришло из базы
$favorites = [
    [
        'id' => 1,
        'name' => 'Muris',
        'species' => 'Kaķis',
        'age' => 3,
        'gender' => 'male',
        'breed' => 'Eiropas īsspalvainais',
        'photo_url' => 'https://placekitten.com/400/300'
    ],
    [
        'id' => 2,
        'name' => 'Rekss',
        'species' => 'Suns',
        'age' => 5,
        'gender' => 'male',
        'breed' => 'Vācu aitu suns',
        'photo_url' => 'https://placedog.net/500'
    ],
    [
        'id' => 3,
        'name' => 'Bella',
        'species' => 'Kaķis',
        'age' => 2,
        'gender' => 'female',
        'breed' => 'Siāmas kaķis',
        'photo_url' => 'https://placekitten.com/401/300'
    ]
];
?>
<!DOCTYPE html>
<html lang="lv">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Favorīti - SirdsPaws</title>
    <link rel="stylesheet" href="index.css">
    <style>
        /* Твой оригинальный CSS */
        body {
            font-family: 'Segoe UI', sans-serif;
            background-color: #f8f9fc;
            margin: 0;
            padding: 0;
        }
        .favorites-hero {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 4rem 0;
            text-align: center;
        }
        .favorites-hero h1 {
            font-size: 2.5rem;
            margin-bottom: 1rem;
            font-weight: 700;
        }
        .favorites-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 3rem 1rem;
        }
        .pets-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 2rem;
            margin-top: 2rem;
        }
        .pet-card {
            background: white;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            position: relative;
        }
        .pet-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.15);
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
        }
        .pet-card-actions {
            display: flex;
            gap: 1rem;
            margin-top: 1rem;
        }
        .btn {
            padding: 0.75rem 1.5rem;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s ease;
            text-align: center;
            cursor: pointer;
            border: none;
            font-size: 1rem;
        }
        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            flex: 1;
        }
        .btn-primary:hover {
            transform: scale(1.05);
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.4);
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
        .favorite-badge {
            position: absolute;
            top: 1rem;
            right: 1rem;
            background: #ff6b6b;
            border-radius: 50%;
            width: 45px;
            height: 45px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            box-shadow: 0 2px 10px rgba(0,0,0,0.15);
            z-index: 10;
        }
        .favorite-badge svg {
            width: 24px;
            height: 24px;
            fill: white;
        }
    </style>
</head>
<body>

<!-- Можно подключить navbar, если он у тебя есть -->
<!-- <?php // include 'navbar.php'; ?> -->

<section class="favorites-hero">
    <div class="container">
        <h1>💖 Mani favorītie dzīvnieki</h1>
        <p>Šeit Tu vari redzēt visus dzīvniekus, kurus esi pievienojis favorītos</p>
    </div>
</section>

<section class="favorites-container">
    <?php if (empty($favorites)): ?>
        <div class="empty-state">
            <h2>Tavs favorītu saraksts ir tukšs</h2>
            <p>Sāc pievienot dzīvniekus favorītos, lai viņi parādītos šeit!</p>
            <a href="pets.php" class="btn btn-primary">Skatīt dzīvniekus</a>
        </div>
    <?php else: ?>
        <div class="pets-grid">
            <?php foreach ($favorites as $pet): ?>
                <div class="pet-card">
                    <div class="favorite-badge">
                        <svg viewBox="0 0 24 24">
                            <path d="M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 
                            2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 
                            4.5 2.09C13.09 3.81 14.76 3 16.5 3 
                            19.58 3 22 5.42 22 8.5c0 3.78-3.4 
                            6.86-8.55 11.54L12 21.35z"/>
                        </svg>
                    </div>
                    
                    <img src="<?php echo htmlspecialchars($pet['photo_url']); ?>" 
                         alt="<?php echo htmlspecialchars($pet['name']); ?>" 
                         class="pet-card-image">
                    
                    <div class="pet-card-content">
                        <h3><?php echo htmlspecialchars($pet['name']); ?></h3>
                        <div class="pet-info">
                            <div class="pet-info-item"><span>Suga:</span> <?php echo htmlspecialchars($pet['species']); ?></div>
                            <div class="pet-info-item"><span>Vecums:</span> <?php echo htmlspecialchars($pet['age']); ?> gadi</div>
                            <div class="pet-info-item"><span>Dzimums:</span> <?php echo $pet['gender'] == 'male' ? 'Tēviņš' : 'Mātīte'; ?></div>
                            <div class="pet-info-item"><span>Šķirne:</span> <?php echo htmlspecialchars($pet['breed']); ?></div>
                        </div>

                        <div class="pet-card-actions">
                            <a href="#" class="btn btn-primary">Skatīt profilu</a>
                            <button class="btn btn-remove" onclick="alert('Noņemt no favorītiem: <?php echo $pet['name']; ?>')">✕</button>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</section>

</body>
</html>
