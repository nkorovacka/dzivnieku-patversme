<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['lietotaja_id'])) {
    header('Location: login.html');
    exit;
}

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

$lietotaja_id = $_SESSION['lietotaja_id'];

// Fetch user information
$stmt = $pdo->prepare("SELECT * FROM lietotaji WHERE id = ?");
$stmt->execute([$lietotaja_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

// Fetch user's registered events
$stmt = $pdo->prepare("
    SELECT 
        p.*,
        pp.pieteikuma_datums,
        pp.statuss,
        COUNT(pp2.id) as current_participants
    FROM pasakumu_pieteikumi pp
    JOIN pasakumi p ON pp.pasakuma_id = p.id
    LEFT JOIN pasakumu_pieteikumi pp2 ON p.id = pp2.pasakuma_id
    WHERE pp.lietotaja_id = ?
    GROUP BY p.id, pp.pieteikuma_datums, pp.statuss
    ORDER BY p.datums ASC
");
$stmt->execute([$lietotaja_id]);
$registered_events = $stmt->fetchAll(PDO::FETCH_ASSOC);

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
    <title>Mans Profils - SirdsPaws</title>
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
            white-space: nowrap;
            transition: transform 0.2s;
        }

        .logo:hover {
            transform: scale(1.05);
        }

        nav ul {
            list-style: none;
            display: flex;
            gap: 8px;
        }

        nav a {
            color: #475569;
            text-decoration: none;
            font-size: 16px;
            padding: 10px 20px;
            border-radius: 12px;
            transition: all 0.2s;
            font-weight: 500;
        }

        nav a:hover {
            background: #f1f5ff;
            color: #6366f1;
        }

        nav a.active {
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

        .auth-links a:hover {
            transform: translateY(-2px);
            background: #f1f5ff;
        }

        /* PROFILE SECTION */
        .profile-hero {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 3rem 2rem;
            text-align: center;
        }

        .profile-hero h1 {
            font-size: 2.5rem;
            margin-bottom: 0.5rem;
        }

        .profile-content {
            padding: 3rem 0;
        }

        .profile-card {
            background: white;
            border-radius: 20px;
            padding: 2rem;
            box-shadow: 0 5px 20px rgba(0,0,0,0.08);
            margin-bottom: 2rem;
        }

        .profile-card h2 {
            font-size: 1.8rem;
            color: #1e293b;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .user-info {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .info-item {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }

        .info-label {
            font-size: 0.9rem;
            color: #64748b;
            font-weight: 600;
        }

        .info-value {
            font-size: 1.1rem;
            color: #1e293b;
            font-weight: 500;
        }

        /* EVENTS GRID */
        .events-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 2rem;
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
            height: 180px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 3.5rem;
        }

        .event-content {
            padding: 1.5rem;
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
            font-size: 1.3rem;
            font-weight: 700;
            color: #1e293b;
            margin-bottom: 1rem;
        }

        .event-meta {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
            margin-bottom: 1rem;
            color: #64748b;
            font-size: 0.9rem;
        }

        .meta-item {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .status-badge {
            display: inline-block;
            padding: 6px 12px;
            border-radius: 8px;
            font-size: 13px;
            font-weight: 600;
            margin-top: 1rem;
        }

        .status-confirmed {
            background: #d1fae5;
            color: #065f46;
        }

        .no-events {
            text-align: center;
            padding: 3rem;
            color: #64748b;
        }

        .no-events h3 {
            font-size: 1.5rem;
            margin-bottom: 1rem;
        }

        .btn {
            display: inline-block;
            padding: 12px 28px;
            background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%);
            color: white;
            text-decoration: none;
            border-radius: 12px;
            font-weight: 600;
            transition: all 0.3s;
            margin-top: 1rem;
        }

        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(99,102,241,0.4);
        }

        @media (max-width: 768px) {
            .events-grid {
                grid-template-columns: 1fr;
            }
            
            .nav-container {
                flex-wrap: wrap;
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
                <ul>
                    <li><a href="index.php">Sākums</a></li>
                    <li><a href="pets.php">Dzīvnieki</a></li>
                    <li><a href="favorites.php">Favorīti</a></li>
                    <li><a href="applications.php">Mani pieteikumi</a></li>
                    <li><a href="events.php">Pasākumi</a></li>
                </ul>
            </nav>

            <div class="auth-links">
                <span style="margin-right:10px;">Sveiks, <?php echo htmlspecialchars($user['lietotajvards'] ?? 'Lietotājs'); ?></span>
                <?php if (!empty($user['admin']) && $user['admin'] == 1): ?>
                    <a href="admin.php">Admin</a>
                <?php endif; ?>
                <a href="logout.php">Izrakstīties</a>
            </div>

            <?php include 'profile_icon.php'; ?>
        </div>
    </header>

    <!-- PROFILE HERO -->
    <section class="profile-hero">
        <div class="container">
            <h1>👤 Mans Profils</h1>
            <p>Sveicināti atpakaļ, <?php echo htmlspecialchars($user['lietotajvards']); ?>!</p>
        </div>
    </section>

    <!-- PROFILE CONTENT -->
    <section class="profile-content">
        <div class="container">
            
            <!-- USER INFO CARD -->
            <div class="profile-card">
                <h2>📋 Mana Informācija</h2>
                <div class="user-info">
                    <div class="info-item">
                        <span class="info-label">Lietotājvārds</span>
                        <span class="info-value"><?php echo htmlspecialchars($user['lietotajvards']); ?></span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">E-pasts</span>
                        <span class="info-value"><?php echo htmlspecialchars($user['epasts']); ?></span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Vārds</span>
                        <span class="info-value"><?php echo htmlspecialchars($user['vards'] ?? 'Nav norādīts'); ?></span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Uzvārds</span>
                        <span class="info-value"><?php echo htmlspecialchars($user['uzvards'] ?? 'Nav norādīts'); ?></span>
                    </div>
                </div>
            </div>

            <!-- REGISTERED EVENTS CARD -->
            <div class="profile-card">
                <h2>🎉 Mani Pasākumi (<?php echo count($registered_events); ?>)</h2>
                
                <?php if (empty($registered_events)): ?>
                    <div class="no-events">
                        <h3>Nav reģistrētu pasākumu</h3>
                        <p>Jūs vēl neesat piereģistrējies nevienam pasākumam.</p>
                        <a href="events.php" class="btn">Skatīt Pasākumus</a>
                    </div>
                <?php else: ?>
                    <div class="events-grid">
                        <?php foreach ($registered_events as $event): ?>
                            <?php
                            // Format date
                            $date = new DateTime($event['datums']);
                            $formatted_date = $date->format('j. F, Y');
                            $months_lv = [
                                'January' => 'janvāris', 'February' => 'februāris', 'March' => 'marts',
                                'April' => 'aprīlis', 'May' => 'maijs', 'June' => 'jūnijs',
                                'July' => 'jūlijs', 'August' => 'augusts', 'September' => 'septembris',
                                'October' => 'oktobris', 'November' => 'novembris', 'December' => 'decembris'
                            ];
                            foreach ($months_lv as $en => $lv) {
                                $formatted_date = str_replace($en, $lv, $formatted_date);
                            }
                            
                            $icon = isset($icon_map[$event['kategorija']]) ? $icon_map[$event['kategorija']] : '🎉';
                            $category_name = isset($category_map[$event['kategorija']]) ? $category_map[$event['kategorija']] : ucfirst($event['kategorija']);
                            ?>
                            
                            <div class="event-card">
                                <div class="event-image">
                                    <?php echo $icon; ?>
                                </div>
                                <div class="event-content">
                                    <span class="event-category"><?php echo htmlspecialchars($category_name); ?></span>
                                    <h3 class="event-title"><?php echo htmlspecialchars($event['nosaukums']); ?></h3>
                                    <div class="event-meta">
                                        <div class="meta-item">
                                            📅 <strong><?php echo $formatted_date; ?></strong>
                                        </div>
                                        <div class="meta-item">
                                            🕐 <strong><?php echo htmlspecialchars($event['laiks']); ?></strong>
                                        </div>
                                        <div class="meta-item">
                                            📍 <strong><?php echo htmlspecialchars($event['vieta']); ?></strong>
                                        </div>
                                        <div class="meta-item">
                                            👥 <strong><?php echo $event['current_participants']; ?>/<?php echo $event['max_dalibnieki']; ?> dalībnieki</strong>
                                        </div>
                                    </div>
                                    <span class="status-badge status-confirmed">✓ Reģistrēts</span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

        </div>
    </section>

</body>
</html>
