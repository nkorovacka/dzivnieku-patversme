<?php
session_start();

require_once __DIR__ . '/db_conn.php';

// Database connection
$host = 'shinkansen.proxy.rlwy.net'; // Updated host
$port = '36226'; // Updated port
$dbname = 'railway';
$username = 'root'; // your username
$password = 'oYVsYmRdokiELhESSYyNUiTfHwwpqEfE'; // your password

try {
    $pdo = new PDO("mysql:host=$host;port=$port;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'register') {
    header('Content-Type: application/json');
    
    if (!isset($_SESSION['user_id'])) {
        echo json_encode(['success' => false, 'message' => 'Lūdzu, pieslēdzieties, lai reģistrētos pasākumam!']);
        exit;
    }
    
    $event_id = $_POST['event_id'];
    $user_id = $_SESSION['user_id'];
    
    try {
        // Check if already registered
        $stmt = $pdo->prepare("SELECT id FROM pasakumu_pieteikumi WHERE pasakuma_id = ? AND lietotaja_id = ?");
        $stmt->execute([$event_id, $user_id]);
        
        if ($stmt->fetch()) {
            // Unregister
            $stmt = $pdo->prepare("DELETE FROM pasakumu_pieteikumi WHERE pasakuma_id = ? AND lietotaja_id = ?");
            $stmt->execute([$event_id, $user_id]);
            echo json_encode(['success' => true, 'action' => 'unregistered', 'message' => 'Jūs atteicāties no pasākuma']);
        } else {
            // Check if event is full
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
                exit;
            }
            
            // Register
            $stmt = $pdo->prepare("INSERT INTO pasakumu_pieteikumi (pasakuma_id, lietotaja_id, registracijas_datums) VALUES (?, ?, NOW())");
            $stmt->execute([$event_id, $user_id]);
            echo json_encode(['success' => true, 'action' => 'registered', 'message' => 'Jūs veiksmīgi piereģistrējāties pasākumam!']);
        }
    } catch(PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Kļūda: ' . $e->getMessage()]);
    }
    exit;
}

$category_filter = isset($_GET['category']) ? $_GET['category'] : 'all';

$sql = "
    SELECT 
        p.*,
        COUNT(pp.id) as current_participants,
        p.max_dalibnieki as max_participants
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

$user_registrations = [];
if (isset($_SESSION['user_id'])) {
    $stmt = $pdo->prepare("SELECT pasakuma_id FROM pasakumu_pieteikumi WHERE lietotaja_id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $user_registrations[] = $row['pasakuma_id'];
    }
}

// Category mapping
$category_map = [
    'adoption' => 'Adopcijas Diena',
    'volunteer' => 'Brīvprātīgie',
    'training' => 'Apmācība',
    'fundraising' => 'Labdarība'
];

// Icon mapping
$icon_map = [
    'adoption' => '🐕🐈',
    'volunteer' => '🧹🏡',
    'training' => '📚🎓',
    'fundraising' => '💝🎪'
];
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

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: #f5f7fa;
            color: #1a1a1a;
            line-height: 1.6;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
        }

        /* ==========================
           HEADER
        ========================== */
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
            flex-wrap: nowrap;
        }

        .logo {
            font-size: 28px;
            font-weight: 700;
            color: #6366f1;
            text-decoration: none;
            white-space: nowrap;
            transition: transform 0.2s;
        }

        .logo:hover {
            transform: scale(1.05);
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

        .auth-links a {
            color: #6366f1;
            text-decoration: none;
            font-size: 15px;
            font-weight: 600;
            padding: 8px 18px;
            border-radius: 10px;
            transition: all 0.2s;
        }

        .auth-links a:first-child {
            color: #6366f1;
            border: 2px solid #6366f1;
        }

        .auth-links a:last-child {
            background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%);
            color: white;
            box-shadow: 0 2px 8px rgba(99,102,241,0.3);
        }

        .auth-links a:hover {
            transform: translateY(-2px);
        }

        /* ==========================
           HERO SECTION
        ========================== */
        .hero {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 4rem 2rem;
            text-align: center;
            position: relative;
            overflow: hidden;
        }

        .hero::before {
            content: '';
            position: absolute;
            width: 500px;
            height: 500px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 50%;
            top: -200px;
            right: -100px;
            animation: float 20s infinite ease-in-out;
        }

        @keyframes float {
            0%, 100% { transform: translateY(0px); }
            50% { transform: translateY(-30px); }
        }

        .hero h1 {
            font-size: 3.5rem;
            margin-bottom: 1rem;
            font-weight: 800;
            position: relative;
            z-index: 1;
        }

        .hero p {
            font-size: 1.3rem;
            opacity: 0.95;
            position: relative;
            z-index: 1;
        }

        /* ==========================
           TABS
        ========================== */
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
            box-shadow: 0 4px 15px rgba(99,102,241,0.3);
        }

        /* ==========================
           EVENTS GRID
        ========================== */
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
            position: relative;
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
            text-transform: uppercase;
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

        .meta-item {
            display: flex;
            align-items: center;
            gap: 8px;
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
            text-decoration: none;
            display: inline-block;
        }

        .btn-primary {
            background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%);
            color: white;
            box-shadow: 0 4px 15px rgba(99,102,241,0.3);
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(99,102,241,0.4);
        }

        .btn-secondary {
            background: #f1f5f9;
            color: #475569;
        }

        .btn-secondary:hover {
            background: #e2e8f0;
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

        .btn-registered:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(99,102,241,0.4);
        }

        .no-events {
            text-align: center;
            padding: 4rem 2rem;
            color: #64748b;
        }

        .no-events h3 {
            font-size: 1.5rem;
            margin-bottom: 0.5rem;
        }

        /* ==========================
           FOOTER
        ========================== */
        footer {
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
            font-size: 1.8rem;
            font-weight: 700;
        }

        footer h4 {
            color: white;
            margin-bottom: 1rem;
            font-weight: 600;
        }

        footer p, footer a {
            color: #b8b8c8;
            line-height: 1.8;
            text-decoration: none;
        }

        footer a:hover {
            color: #667eea;
        }

        .footer-bottom {
            border-top: 1px solid rgba(255,255,255,0.1);
            padding-top: 2rem;
            text-align: center;
            color: #b8b8c8;
        }

        /* ==========================
           RESPONSIVE
        ========================== */
        @media (max-width: 900px) {
            .nav-container {
                flex-wrap: wrap;
                justify-content: center;
                gap: 12px;
            }

            .hero h1 {
                font-size: 2.5rem;
            }

            .events-grid {
                grid-template-columns: 1fr;
            }

            .tabs-container {
                margin: -30px 20px 2rem;
            }
        }
    </style>
</head>
<body>

    <!-- HEADER -->
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
                <?php if (isset($_SESSION['user_id'])): ?>
                    <a href="profile.php">Profils</a>
                    <?php if (!empty($_SESSION['admin']) && $_SESSION['admin'] == 1): ?>
                        <a href="admin.php">Admin</a>
                    <?php endif; ?>
                    <a href="logout.php">Izrakstīties</a>
                <?php else: ?>
                    <a href="login.html">Pieslēgties</a>
                    <a href="register.html">Reģistrēties</a>
                <?php endif; ?>
            </div>
        </div>
    </header>

    <!-- HERO -->
    <section class="hero">
        <div class="container">
            <h1>🎉 Pasākumi un Aktivitātes</h1>
            <p>Pievienojies mūsu pasākumiem un palīdzi dzīvniekiem atrast mājas!</p>
        </div>
    </section>

    <!-- TABS -->
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

    <!-- EVENTS GRID -->
    <section class="events-section">
        <div class="container">
            <div class="events-grid" id="eventsGrid">
                
                <!-- Event 1 -->
                <div class="event-card" data-category="adoption">
                    <div class="event-image">
                        🐕🐈
                        <span class="event-badge badge-upcoming">Brīvas vietas</span>
                    </div>
                    <div class="event-content">
                        <span class="event-category">Adopcijas Diena</span>
                        <h3 class="event-title">Liela Adopcijas Diena</h3>
                        <div class="event-meta">
                            <div class="meta-item">
                                📅 <strong>20. oktobris, 2025</strong>
                            </div>
                            <div class="meta-item">
                                🕐 <strong>10:00 - 16:00</strong>
                            </div>
                            <div class="meta-item">
                                📍 <strong>Patversmes teritorija</strong>
                            </div>
                        </div>
                        <p class="event-description">
                            Pievienojies mūsu lielākajam adopcijas pasākumam! Satiec mūsu dzīvniekus, uzzini par viņu stāstiem un palīdzi atrast viņiem mīlošas mājas.
                        </p>
                        <div class="event-footer">
                            <span class="participants-info">👥 28/50 dalībnieki</span>
                            <button class="btn btn-primary" onclick="registerEvent(this, 'Liela Adopcijas Diena')">Pierakstīties</button>
                        </div>
                    </div>
                </div>

                <!-- Event 2 -->
                <div class="event-card" data-category="volunteer">
                    <div class="event-image">
                        🧹🏡
                        <span class="event-badge badge-spots-left">Maz vietu</span>
                    </div>
                    <div class="event-content">
                        <span class="event-category">Brīvprātīgie</span>
                        <h3 class="event-title">Ziemas Talka</h3>
                        <div class="event-meta">
                            <div class="meta-item">
                                📅 <strong>27. oktobris, 2025</strong>
                            </div>
                            <div class="meta-item">
                                🕐 <strong>09:00 - 15:00</strong>
                            </div>
                            <div class="meta-item">
                                📍 <strong>Patversmes teritorija</strong>
                            </div>
                        </div>
                        <p class="event-description">
                            Palīdzi mums sagatavoties ziemai! Kopā sakopsim teritoriju, sagatavosim būdas un izveidosim siltas vietas dzīvniekiem.
                        </p>
                        <div class="event-footer">
                            <span class="participants-info">👥 18/20 dalībnieki</span>
                            <button class="btn btn-primary" onclick="registerEvent(this, 'Ziemas Talka')">Pierakstīties</button>
                        </div>
                    </div>
                </div>

                <!-- Event 3 -->
                <div class="event-card" data-category="training">
                    <div class="event-image">
                        📚🎓
                        <span class="event-badge badge-upcoming">Brīvas vietas</span>
                    </div>
                    <div class="event-content">
                        <span class="event-category">Apmācība</span>
                        <h3 class="event-title">Suņu Uzvedības Kurss</h3>
                        <div class="event-meta">
                            <div class="meta-item">
                                📅 <strong>3. novembris, 2025</strong>
                            </div>
                            <div class="meta-item">
                                🕐 <strong>14:00 - 17:00</strong>
                            </div>
                            <div class="meta-item">
                                📍 <strong>Apmācību telpa</strong>
                            </div>
                        </div>
                        <p class="event-description">
                            Uzzini par suņu uzvedību, komunikāciju un drošības noteikumiem. Ideāli brīvprātīgajiem un potenciālajiem adoptētājiem!
                        </p>
                        <div class="event-footer">
                            <span class="participants-info">👥 12/25 dalībnieki</span>
                            <button class="btn btn-primary" onclick="registerEvent(this, 'Suņu Uzvedības Kurss')">Pierakstīties</button>
                        </div>
                    </div>
                </div>

                <!-- Event 4 -->
                <div class="event-card" data-category="fundraising">
                    <div class="event-image">
                        💝🎪
                        <span class="event-badge badge-upcoming">Brīvas vietas</span>
                    </div>
                    <div class="event-content">
                        <span class="event-category">Labdarība</span>
                        <h3 class="event-title">Labdarības Tirdziņš</h3>
                        <div class="event-meta">
                            <div class="meta-item">
                                📅 <strong>10. novembris, 2025</strong>
                            </div>
                            <div class="meta-item">
                                🕐 <strong>11:00 - 18:00</strong>
                            </div>
                            <div class="meta-item">
                                📍 <strong>Rīgas Centrāltirgus</strong>
                            </div>
                        </div>
                        <p class="event-description">
                            Labdarības tirdziņš ar roku darbu izstrādājumiem! Visi ieņēmumi tiks ziedoti patversmes dzīvniekiem.
                        </p>
                        <div class="event-footer">
                            <span class="participants-info">👥 35/60 dalībnieki</span>
                            <button class="btn btn-primary" onclick="registerEvent(this, 'Labdarības Tirdziņš')">Pierakstīties</button>
                        </div>
                    </div>
                </div>

                <!-- Event 5 -->
                <div class="event-card" data-category="volunteer">
                    <div class="event-image">
                        🐾🚶
                    </div>
                    <div class="event-content">
                        <span class="event-category">Brīvprātīgie</span>
                        <h3 class="event-title">Grupu Suņu Pastaiga</h3>
                        <div class="event-meta">
                            <div class="meta-item">
                                📅 <strong>Katru sestdienu</strong>
                            </div>
                            <div class="meta-item">
                                🕐 <strong>10:00 - 12:00</strong>
                            </div>
                            <div class="meta-item">
                                📍 <strong>Mežaparks</strong>
                            </div>
                        </div>
                        <p class="event-description">
                            Katru nedēļas nogali dodamies uz grupu pastaigu! Lieliski socializācijai un aktīvam laikam kopā ar suņiem.
                        </p>
                        <div class="event-footer">
                            <span class="participants-info">👥 Pastāvīgs</span>
                            <button class="btn btn-primary" onclick="registerEvent(this, 'Grupu Suņu Pastaiga')">Pierakstīties</button>
                        </div>
                    </div>
                </div>

                <!-- Event 6 -->
                <div class="event-card" data-category="adoption">
                    <div class="event-image">
                        🐱❤️
                        <span class="event-badge badge-upcoming">Brīvas vietas</span>
                    </div>
                    <div class="event-content">
                        <span class="event-category">Adopcijas Diena</span>
                        <h3 class="event-title">Kaķēnu Adopcijas Pasākums</h3>
                        <div class="event-meta">
                            <div class="meta-item">
                                📅 <strong>17. novembris, 2025</strong>
                            </div>
                            <div class="meta-item">
                                🕐 <strong>12:00 - 17:00</strong>
                            </div>
                            <div class="meta-item">
                                📍 <strong>Patversmes teritorija</strong>
                            </div>
                        </div>
                        <p class="event-description">
                            Īpašs pasākums, kas veltīts kaķēniem! Satiec mūsu jaunākos iemītniekus un dod viņiem iespēju atrast mājvietu.
                        </p>
                        <div class="event-footer">
                            <span class="participants-info">👥 15/40 dalībnieki</span>
                            <button class="btn btn-primary" onclick="registerEvent(this, 'Kaķēnu Adopcijas Pasākums')">Pierakstīties</button>
                        </div>
                    </div>
                </div>


    <!-- FOOTER -->
    <footer>
        <div class="container">
            <div class="footer-grid">
                <div>
                    <h3>🐾 SirdsPaws</h3>
                    <p style="margin-bottom: 1.5rem;">
                        Palīdzam dzīvniekiem atrast mīlošas mājas un cilvēkiem - uzticamus draugus.
                    </p>
                </div>

                <div>
                    <h4>Kontakti</h4>
                    <div style="line-height: 2;">
                        <div>📍 Daugavgrīvas iela 123, Rīga</div>
                        <div>📞 +371 26 123 456</div>
                        <div>✉️ info@sirdspaws.lv</div>
                    </div>
                </div>

                <div>
                    <h4>Saites</h4>
                    <div style="line-height: 2;">
                        <div><a href="pets.html">Dzīvnieki</a></div>
                        <div><a href="events.php">Pasākumi</a></div>
                        <div><a href="register.html">Reģistrēties</a></div>
                    </div>
                </div>

                <div>
                    <h4>Darba laiks</h4>
                    <div style="line-height: 2;">
                        <div>P-Pk: 9:00 - 18:00</div>
                        <div>S: 10:00 - 16:00</div>
                        <div>Sv: 10:00 - 14:00</div>
                    </div>
                </div>
            </div>

            <div class="footer-bottom">
                <p style="margin: 0;">© 2025 SirdsPaws. Radīts ar ❤️ dzīvniekiem</p>
            </div>
        </div>
    </footer>

    <script>
        // Filter events by category
        function filterEvents(category) {
            window.location.href = 'events.php?category=' + category;
        }

        function registerEvent(eventId, eventName, button) {
            <?php if (!isset($_SESSION['user_id'])): ?>
                alert('Lūdzu, pieslēdzieties, lai reģistrētos pasākumam!');
                window.location.href = 'login.html';
                return;
            <?php endif; ?>

            // Disable button during request
            button.disabled = true;
            const originalText = button.textContent;

            fetch('events.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'action=register&event_id=' + eventId
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert(data.message);
                    
                    if (data.action === 'registered') {
                        button.textContent = '✓ Reģistrēts';
                        button.classList.remove('btn-primary');
                        button.classList.add('btn-registered');
                    } else {
                        button.textContent = 'Pierakstīties';
                        button.classList.remove('btn-registered');
                        button.classList.add('btn-primary');
                    }
                    
                    // Reload page to update participant counts
                    setTimeout(() => {
                        location.reload();
                    }, 1000);
                } else {
                    alert(data.message);
                    button.textContent = originalText;
                }
                button.disabled = false;
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Kļūda reģistrējoties. Lūdzu, mēģiniet vēlreiz.');
                button.textContent = originalText;
                button.disabled = false;
            });
        }
    </script>
</body>
</html>
