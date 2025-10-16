<?php
// ===================================
// КРИТИЧЕСКИ ВАЖНО: Буферизация вывода
// ===================================
ob_start();

session_start();

// Database connection
$host = 'shinkansen.proxy.rlwy.net';
$port = '36226';
$dbname = 'railway';
$username = 'root';
$password = 'oYVsYmRdokiELhESSYyNUiTfHwwpqEfE';

try {
    $pdo = new PDO("mysql:host=$host;port=$port;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

// ===================================
// ОБРАБОТКА AJAX РЕГИСТРАЦИИ
// ===================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'register') {
    // ОЧИЩАЕМ весь буфер перед отправкой JSON
    ob_end_clean();
    ob_start();
    
    header('Content-Type: application/json');
    
    // ПРОВЕРКА АВТОРИЗАЦИИ для регистрации
    if (!isset($_SESSION['user_id'])) {
        echo json_encode([
            'success' => false, 
            'redirect' => true,
            'message' => 'Lūdzu, pieslēdzieties, lai reģistrētos pasākumam!'
        ]);
        ob_end_flush();
        exit;
    }
    
    $event_id = intval($_POST['event_id']);
    $user_id = $_SESSION['user_id'];
    
    try {
        // Проверка, зарегистрирован ли уже
        $stmt = $pdo->prepare("SELECT id FROM pasakumu_pieteikumi WHERE pasakuma_id = ? AND lietotaja_id = ?");
        $stmt->execute([$event_id, $user_id]);
        
        if ($stmt->fetch()) {
            // Отмена регистрации
            $stmt = $pdo->prepare("DELETE FROM pasakumu_pieteikumi WHERE pasakuma_id = ? AND lietotaja_id = ?");
            $stmt->execute([$event_id, $user_id]);
            echo json_encode(['success' => true, 'action' => 'unregistered', 'message' => 'Jūs atteicāties no pasākuma']);
        } else {
            // Проверка заполненности
            $stmt = $pdo->prepare("
                SELECT p.max_dalibnieki, COUNT(pp.id) as current_count 
                FROM pasakumi p 
                LEFT JOIN pasakumu_pieteikumi pp ON p.id = pp.pasakuma_id 
                WHERE p.id = ? 
                GROUP BY p.id
            ");
            $stmt->execute([$event_id]);
            $event = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($event && $event['current_count'] >= $event['max_dalibnieki']) {
                echo json_encode(['success' => false, 'message' => 'Pasākums ir pilns!']);
                ob_end_flush();
                exit;
            }
            
            // Регистрация
            $stmt = $pdo->prepare("INSERT INTO pasakumu_pieteikumi (pasakuma_id, lietotaja_id, registracijas_datums) VALUES (?, ?, NOW())");
            $stmt->execute([$event_id, $user_id]);
            echo json_encode(['success' => true, 'action' => 'registered', 'message' => 'Jūs veiksmīgi piereģistrējāties pasākumam!']);
        }
    } catch(PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Kļūda: ' . $e->getMessage()]);
    }
    
    ob_end_flush();
    exit;
}

// Получение фильтра категории
$category_filter = isset($_GET['category']) ? $_GET['category'] : 'all';

// Получение событий из базы данных
$sql = "
    SELECT 
        p.*,
        COUNT(pp.id) as current_participants
    FROM pasakumi p
    LEFT JOIN pasakumu_pieteikumi pp ON p.id = pp.pasakuma_id
";

if ($category_filter !== 'all') {
    $sql .= " WHERE p.kategorija = :category";
}

$sql .= " GROUP BY p.id ORDER BY p.datums ASC";

$stmt = $pdo->prepare($sql);
if ($category_filter !== 'all') {
    $stmt->bindParam(':category', $category_filter);
}
$stmt->execute();
$events = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Получение регистраций текущего пользователя (только если авторизован)
$user_registrations = [];
if (isset($_SESSION['user_id'])) {
    $stmt = $pdo->prepare("SELECT pasakuma_id FROM pasakumu_pieteikumi WHERE lietotaja_id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $user_registrations[] = $row['pasakuma_id'];
    }
}

// Маппинг категорий
$category_map = [
    'adoption' => 'Adopcijas Diena',
    'volunteer' => 'Brīvprātīgie',
    'training' => 'Apmācība',
    'fundraising' => 'Labdarība'
];

// Маппинг иконок
$icon_map = [
    'adoption' => '🐕🐈',
    'volunteer' => '🧹🏡',
    'training' => '📚🎓',
    'fundraising' => '💝🎪'
];

// Проверка авторизации
$is_logged_in = isset($_SESSION['user_id']);

// Выводим накопленный буфер
ob_end_flush();
?>
<!DOCTYPE html>
<html lang="lv">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pasākumi - SirdsPaws</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        html, body {
            height: 100%;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: #f5f7fa;
            color: #1a1a1a;
            line-height: 1.6;
            display: flex;
            flex-direction: column;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
        }

        /* HEADER */
        .main-header {
            position: sticky;
            top: 0;
            z-index: 9999;
            background: #fff;
            box-shadow: 0 1px 3px rgba(0,0,0,0.08);
        }

        .main-header::after {
            content: "";
            display: block;
            height: 3px;
            background: linear-gradient(90deg, #6366f1, #8b5cf6, #a855f7);
        }

        .nav-container {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 24px;
            padding: 20px 32px;
            min-height: 80px;
        }

        .logo {
            font-size: 28px;
            font-weight: 700;
            color: #6366f1;
            text-decoration: none;
            transition: transform 0.2s;
        }

        .nav-links {
            list-style: none;
            display: flex;
            gap: 8px;
        }

        .nav-links a {
            color: #475569;
            text-decoration: none;
            font-size: 16px;
            padding: 10px 20px;
            border-radius: 12px;
            transition: all 0.2s;
            font-weight: 500;
        }

        .nav-links a:hover {
            background: #f1f5ff;
            color: #6366f1;
        }

        .nav-links a.active {
            background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%);
            color: #fff;
            font-weight: 600;
            box-shadow: 0 2px 8px rgba(99,102,241,0.3);
        }

        .auth-links {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .auth-links a, .auth-links span {
            color: #6366f1;
            text-decoration: none;
            font-size: 15px;
            font-weight: 600;
            padding: 8px 18px;
            border-radius: 10px;
            transition: all 0.2s;
        }

        .auth-links a {
            border: 2px solid #6366f1;
        }

        .auth-links a:last-child {
            background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%);
            color: white;
            border: none;
        }

        .main-content {
            flex: 1 0 auto;
        }

        .hero {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 4rem 2rem;
            text-align: center;
            position: relative;
            overflow: hidden;
        }

        .hero h1 {
            font-size: 3.5rem;
            margin-bottom: 1rem;
            font-weight: 800;
        }

        .hero p {
            font-size: 1.3rem;
            opacity: 0.95;
        }

        .tabs-container {
            background: white;
            padding: 2rem;
            margin: -50px auto 3rem;
            max-width: 1100px;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.15);
            position: relative;
            z-index: 10;
        }

        .tabs {
            display: flex;
            gap: 10px;
            justify-content: center;
            flex-wrap: wrap;
        }

        .tab-btn {
            padding: 12px 30px;
            background: #f1f5f9;
            border: none;
            border-radius: 12px;
            font-size: 16px;
            font-weight: 600;
            color: #475569;
            cursor: pointer;
            transition: all 0.3s;
        }

        .tab-btn:hover {
            background: #e2e8f0;
            transform: translateY(-2px);
        }

        .tab-btn.active {
            background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%);
            color: white;
        }

        .events-section {
            padding: 2rem 0 5rem;
        }

        .events-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 2rem;
            margin-top: 2rem;
        }

        .event-card {
            background: white;
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 5px 20px rgba(0,0,0,0.08);
            transition: all 0.3s;
        }

        .event-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 15px 40px rgba(99,102,241,0.2);
        }

        .event-image {
            width: 100%;
            height: 200px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 4rem;
            position: relative;
        }

        .event-badge {
            position: absolute;
            top: 15px;
            right: 15px;
            padding: 6px 15px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 700;
        }

        .badge-upcoming {
            background: #10b981;
            color: white;
        }

        .badge-full {
            background: #ef4444;
            color: white;
        }

        .badge-spots-left {
            background: #f59e0b;
            color: white;
        }

        .event-content {
            padding: 1.8rem;
        }

        .event-category {
            display: inline-block;
            padding: 4px 12px;
            background: #f1f5ff;
            color: #6366f1;
            border-radius: 6px;
            font-size: 13px;
            font-weight: 600;
            margin-bottom: 1rem;
        }

        .event-title {
            font-size: 1.5rem;
            font-weight: 700;
            color: #1e293b;
            margin-bottom: 1rem;
        }

        .event-meta {
            display: flex;
            flex-direction: column;
            gap: 0.7rem;
            margin-bottom: 1.2rem;
            color: #64748b;
            font-size: 0.95rem;
        }

        .event-description {
            color: #64748b;
            line-height: 1.6;
            margin-bottom: 1.5rem;
        }

        .event-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding-top: 1rem;
            border-top: 1px solid #e2e8f0;
        }

        .participants-info {
            font-size: 14px;
            color: #64748b;
            font-weight: 600;
        }

        .btn {
            padding: 12px 28px;
            border: none;
            border-radius: 12px;
            font-weight: 600;
            cursor: pointer;
            font-size: 15px;
            transition: all 0.3s;
        }

        .btn-primary {
            background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%);
            color: white;
            box-shadow: 0 4px 15px rgba(99,102,241,0.3);
        }

        .btn-primary:hover {
            transform: translateY(-2px);
        }

        .btn-disabled {
            background: #cbd5e1;
            color: #94a3b8;
            cursor: not-allowed;
        }

        .btn-registered {
            background: #10b981;
            color: white;
        }

        footer {
            flex-shrink: 0;
            background: #1a1a2e;
            color: white;
            padding: 3rem 0 1rem 0;
        }

        .footer-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 3rem;
            margin-bottom: 2rem;
        }

        footer h3 {
            color: #667eea;
            margin-bottom: 1.5rem;
        }

        footer h4 {
            color: white;
            margin-bottom: 1rem;
        }

        footer a {
            color: #b8b8c8;
            text-decoration: none;
        }

        .footer-bottom {
            border-top: 1px solid rgba(255,255,255,0.1);
            padding-top: 2rem;
            text-align: center;
        }

        @media (max-width: 900px) {
            .hero h1 {
                font-size: 2.5rem;
            }
            .events-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>

    <div class="main-content">
        <header class="main-header">
            <div class="container nav-container">
                <a href="index.php" class="logo">🐾 SirdsPaws</a>
                <nav>
                    <ul class="nav-links">
                        <li><a href="index.php">Sākums</a></li>
                        <li><a href="pets.php">Dzīvnieki</a></li>
                        <li><a href="favorites.php">Favorīti</a></li>
                        <li><a href="applications.php">Mani pieteikumi</a></li>
                        <li><a href="events.php" class="active">Pasākumi</a></li>
                    </ul>
                </nav>

                <div class="auth-links">
                    <?php if (isset($_SESSION['epasts'])): ?>
                        <span style="margin-right:10px;">Sveiks, <?=htmlspecialchars($_SESSION['lietotajvards'] ?? 'User')?></span>
                        <?php if (!empty($_SESSION['admin']) && $_SESSION['admin'] == 1): ?>
                            <a href="admin.php">Admin</a>
                        <?php endif; ?>
                        <a href="logout.php">Izrakstīties</a>
                    <?php else: ?>
                        <a href="login.html">Pieslēgties</a>
                        <a href="register.html">Reģistrēties</a>
                    <?php endif; ?>
                </div>

                <?php 
                // Включаем profile_icon только при отображении HTML (не при AJAX запросах)
                if (!isset($_POST['action'])) {
                    if (file_exists('profile_icon.php')) {
                        include 'profile_icon.php';
                    }
                }
                ?>
            </div>
        </header>

        <section class="hero">
            <div class="container">
                <h1>🎉 Pasākumi un Aktivitātes</h1>
                <p>Pievienojies mūsu pasākumiem un palīdzi dzīvniekiem atrast mājas!</p>
            </div>
        </section>

        <div class="container">
            <div class="tabs-container">
                <div class="tabs">
                    <button class="tab-btn <?php echo $category_filter === 'all' ? 'active' : ''; ?>" onclick="filterEvents('all')">Visi Pasākumi</button>
                    <button class="tab-btn <?php echo $category_filter === 'adoption' ? 'active' : ''; ?>" onclick="filterEvents('adoption')">Adopcijas Dienas</button>
                    <button class="tab-btn <?php echo $category_filter === 'volunteer' ? 'active' : ''; ?>" onclick="filterEvents('volunteer')">Brīvprātīgo Darbs</button>
                    <button class="tab-btn <?php echo $category_filter === 'training' ? 'active' : ''; ?>" onclick="filterEvents('training')">Apmācības</button>
                    <button class="tab-btn <?php echo $category_filter === 'fundraising' ? 'active' : ''; ?>" onclick="filterEvents('fundraising')">Labdarība</button>
                </div>
            </div>
        </div>

        <section class="events-section">
            <div class="container">
                <?php if (count($events) > 0): ?>
                    <div class="events-grid">
                        <?php foreach ($events as $event): 
                            $is_registered = in_array($event['id'], $user_registrations);
                            $is_full = $event['current_participants'] >= $event['max_dalibnieki'];
                            $spots_left = $event['max_dalibnieki'] - $event['current_participants'];
                            
                            $badge_class = 'badge-upcoming';
                            $badge_text = 'Brīvas vietas';
                            
                            if ($is_full) {
                                $badge_class = 'badge-full';
                                $badge_text = 'Pilns';
                            } elseif ($spots_left <= 5) {
                                $badge_class = 'badge-spots-left';
                                $badge_text = 'Maz vietu';
                            }
                            
                            $date = new DateTime($event['datums']);
                            $formatted_date = $date->format('d.m.Y');
                            $time_start = date('H:i', strtotime($event['laiks_sakums']));
                            $time_end = date('H:i', strtotime($event['laiks_beigas']));
                        ?>
                        <div class="event-card">
                            <div class="event-image">
                                <?php echo $icon_map[$event['kategorija']] ?? '🎉'; ?>
                                <span class="event-badge <?php echo $badge_class; ?>"><?php echo $badge_text; ?></span>
                            </div>
                            <div class="event-content">
                                <span class="event-category"><?php echo $category_map[$event['kategorija']] ?? $event['kategorija']; ?></span>
                                <h3 class="event-title"><?php echo htmlspecialchars($event['nosaukums']); ?></h3>
                                <div class="event-meta">
                                    <div>📅 <strong><?php echo $formatted_date; ?></strong></div>
                                    <div>🕐 <strong><?php echo $time_start . ' - ' . $time_end; ?></strong></div>
                                    <div>📍 <strong><?php echo htmlspecialchars($event['vieta']); ?></strong></div>
                                </div>
                                <p class="event-description"><?php echo htmlspecialchars($event['apraksts']); ?></p>
                                <div class="event-footer">
                                    <span class="participants-info">
                                        👥 <?php echo $event['current_participants']; ?>/<?php echo $event['max_dalibnieki']; ?> dalībnieki
                                    </span>
                                    <?php if ($is_full): ?>
                                        <button class="btn btn-disabled" disabled>Pilns</button>
                                    <?php elseif ($is_registered): ?>
                                        <button class="btn btn-registered" onclick="registerEvent(<?php echo $event['id']; ?>, this)">✓ Reģistrēts</button>
                                    <?php else: ?>
                                        <button class="btn btn-primary" onclick="registerEvent(<?php echo $event['id']; ?>, this)">Pierakstīties</button>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div style="text-align:center;padding:4rem 2rem;">
                        <h3>Nav atrasti pasākumi</h3>
                        <p>Šobrīd nav pieejamu pasākumu šajā kategorijā.</p>
                    </div>
                <?php endif; ?>
            </div>
        </section>
    </div>

    <footer>
        <div class="container">
            <div class="footer-grid">
                <div>
                    <h3>🐾 SirdsPaws</h3>
                    <p>Palīdzam dzīvniekiem atrast mīlošas mājas.</p>
                </div>
                <div>
                    <h4>Kontakti</h4>
                    <div>📍 Daugavgrīvas iela 123, Rīga</div>
                    <div>📞 +371 26 123 456</div>
                    <div>✉️ info@sirdspaws.lv</div>
                </div>
                <div>
                    <h4>Saites</h4>
                    <div><a href="pets.php">Dzīvnieki</a></div>
                    <div><a href="events.php">Pasākumi</a></div>
                </div>
            </div>
            <div class="footer-bottom">
                <p>© 2025 SirdsPaws. Radīts ar ❤️ dzīvniekiem</p>
            </div>
        </div>
    </footer>

    <script>
        // Передаем статус авторизации из PHP в JavaScript
        const isLoggedIn = <?php echo $is_logged_in ? 'true' : 'false'; ?>;

        function filterEvents(category) {
            window.location.href = 'events.php?category=' + category;
        }

        function registerEvent(eventId, button) {
            // ПРОВЕРКА: Если пользователь не авторизован, перенаправляем на страницу входа
            if (!isLoggedIn) {
                if (confirm('Lai reģistrētos pasākumam, jums ir jāpieslēdzas. Vai vēlaties pieslēgties tagad?')) {
                    window.location.href = 'login.html';
                }
                return;
            }

            console.log('Registering for event:', eventId);
            
            button.disabled = true;
            const originalText = button.textContent;
            button.textContent = 'Loading...';

            fetch('events.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'action=register&event_id=' + eventId
            })
            .then(response => {
                console.log('Response status:', response.status);
                return response.text();
            })
            .then(text => {
                console.log('Response text:', text);
                const data = JSON.parse(text);
                console.log('Parsed data:', data);
                
                // Проверка на необходимость редиректа
                if (data.redirect) {
                    alert(data.message);
                    window.location.href = 'login.html';
                    return;
                }
                
                if (data.success) {
                    alert(data.message);
                    location.reload();
                } else {
                    alert(data.message);
                    button.textContent = originalText;
                    button.disabled = false;
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Kļūda: ' + error.message);
                button.textContent = originalText;
                button.disabled = false;
            });
        }
    </script>
</body>
</html>