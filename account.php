<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
require_once 'db_conn.php';

// Проверка авторизации
if (empty($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$userId = (int)$_SESSION['user_id'];

// Загрузка данных пользователя
$query = "SELECT * FROM lietotaji WHERE id = ? LIMIT 1";
$stmt = $pdo->prepare($query);
$stmt->execute([$userId]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    session_destroy();
    header("Location: login.php");
    exit();
}

// Загрузка зарегистрированных мероприятий пользователя
$events_query = "
    SELECT 
        p.*,
        pp.registracijas_datums,
        COUNT(pp2.id) as current_participants
    FROM pasakumu_pieteikumi pp
    JOIN pasakumi p ON pp.pasakuma_id = p.id
    LEFT JOIN pasakumu_pieteikumi pp2 ON p.id = pp2.pasakuma_id
    WHERE pp.lietotaja_id = ?
    GROUP BY p.id, pp.registracijas_datums
    ORDER BY p.datums ASC
";
$events_stmt = $pdo->prepare($events_query);
$events_stmt->execute([$userId]);
$user_events = $events_stmt->fetchAll(PDO::FETCH_ASSOC);

// Инициализация переменных
$success_message = '';
$error_message = '';
$bonus_awarded = false;

// Обработка формы
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_profile') {
    
    $full_name = trim($_POST['full_name']);
    $phone = trim($_POST['phone']);
    $address = trim($_POST['address']);
    
    // Старое значение заполненности
    $old_complete = (int)$user['profile_complete'];
    
    // Новое значение заполненности
    $new_complete = 0;
    if (!empty($full_name) && !empty($phone) && !empty($address)) {
        $new_complete = 100;
    }
    
    // Обновляем профиль
    $update_query = "UPDATE lietotaji SET full_name = ?, phone = ?, address = ?, profile_complete = ?, updated_at = NOW() WHERE id = ?";
    $update_stmt = $pdo->prepare($update_query);
    
    if ($update_stmt->execute([$full_name, $phone, $address, $new_complete, $userId])) {
        
        // Если профиль стал 100% впервые
        if ($old_complete < 100 && $new_complete === 100) {
            
            // Проверяем, нет ли уже достижения ID=2
            $check_ach = "SELECT achievements_json FROM lietotaji WHERE id = ?";
            $check_stmt = $pdo->prepare($check_ach);
            $check_stmt->execute([$userId]);
            $check_data = $check_stmt->fetch(PDO::FETCH_ASSOC);
            
            $has_achievement = false;
            if (!empty($check_data['achievements_json'])) {
                $achievements_array = json_decode($check_data['achievements_json'], true);
                if (is_array($achievements_array) && in_array(2, $achievements_array)) {
                    $has_achievement = true;
                }
            }
            
            // Если достижения нет - начисляем бонус
            if (!$has_achievement) {
                
                // 1. Добавляем очки
                $add_points = "UPDATE lietotaji SET points = points + 20, total_earned = total_earned + 20 WHERE id = ?";
                $points_stmt = $pdo->prepare($add_points);
                $points_stmt->execute([$userId]);
                
                // 2. Записываем в историю (если таблица существует)
                try {
                    $history = "INSERT INTO points_history (user_id, points, reason, created_at) VALUES (?, 20, 'profile_complete', NOW())";
                    $history_stmt = $pdo->prepare($history);
                    $history_stmt->execute([$userId]);
                } catch (PDOException $e) {
                    // Игнорируем если таблица не существует
                }
                
                // 3. Добавляем достижение в JSON
                $current_ach = $check_data['achievements_json'];
                if (empty($current_ach)) {
                    $new_ach = json_encode([2]);
                } else {
                    $ach_array = json_decode($current_ach, true);
                    if (!is_array($ach_array)) {
                        $ach_array = [];
                    }
                    $ach_array[] = 2;
                    $new_ach = json_encode($ach_array);
                }
                
                $update_ach = "UPDATE lietotaji SET achievements_json = ? WHERE id = ?";
                $ach_stmt = $pdo->prepare($update_ach);
                $ach_stmt->execute([$new_ach, $userId]);
                
                // 4. Обновляем уровень
                $current_points_query = "SELECT points FROM lietotaji WHERE id = ?";
                $cp_stmt = $pdo->prepare($current_points_query);
                $cp_stmt->execute([$userId]);
                $cp_data = $cp_stmt->fetch(PDO::FETCH_ASSOC);
                
                $points = (int)$cp_data['points'];
                $level_name = 'Iesācējs';
                if ($points >= 1000) {
                    $level_name = 'SirdsPaws Leģenda';
                } elseif ($points >= 600) {
                    $level_name = 'Dzīvnieku Varonis';
                } elseif ($points >= 300) {
                    $level_name = 'Aktīvs Atbalstītājs';
                } elseif ($points >= 100) {
                    $level_name = 'Patversmes Draugs';
                }
                
                $update_level = "UPDATE lietotaji SET level_name = ? WHERE id = ?";
                $level_stmt = $pdo->prepare($update_level);
                $level_stmt->execute([$level_name, $userId]);
                
                $bonus_awarded = true;
                $success_message = 'Profils veiksmīgi atjaunināts! 🎉 Jūs saņēmāt +20 punktus!';
            } else {
                $success_message = 'Profils veiksmīgi atjaunināts!';
            }
        } else {
            $success_message = 'Profils veiksmīgi atjaunināts!';
        }
        
        // Перезагружаем данные пользователя
        $reload_query = "SELECT * FROM lietotaji WHERE id = ?";
        $reload_stmt = $pdo->prepare($reload_query);
        $reload_stmt->execute([$userId]);
        $user = $reload_stmt->fetch(PDO::FETCH_ASSOC);
        
    } else {
        $error_message = 'Kļūda atjauninot profilu!';
    }
}

// Определение уровня для визуала
$points = (int)($user['points'] ?? 0);
$level_icon = '🥉';
$level_color = '#BDC3C7';

if ($points >= 1000) {
    $level_icon = '👑';
    $level_color = '#FFD700';
} elseif ($points >= 600) {
    $level_icon = '💎';
    $level_color = '#E74C3C';
} elseif ($points >= 300) {
    $level_icon = '🥇';
    $level_color = '#3498DB';
} elseif ($points >= 100) {
    $level_icon = '🥈';
    $level_color = '#95A5A6';
}

// Парсим достижения
$earned_ids = [];
if (!empty($user['achievements_json'])) {
    $decoded = json_decode($user['achievements_json'], true);
    if (is_array($decoded)) {
        $earned_ids = $decoded;
    }
}

// Список всех достижений
$all_achievements = [
    ['id' => 1, 'name' => 'Pirmais Solis', 'desc' => 'Reģistrējies sistēmā', 'icon' => '🎯', 'points' => 10],
    ['id' => 2, 'name' => 'Pilnīgs Profils', 'desc' => 'Aizpildīts viss profils', 'icon' => '📱', 'points' => 20],
    ['id' => 3, 'name' => 'Dzīvnieku Draugs', 'desc' => 'Pievienoti 5 favorīti', 'icon' => '❤️', 'points' => 30],
    ['id' => 4, 'name' => 'Atbildīgs Adopcētājs', 'desc' => 'Iesniegts pirmais pieteikums', 'icon' => '📝', 'points' => 50],
    ['id' => 5, 'name' => 'Aktīvais Dalībnieks', 'desc' => 'Apmeklēti 3 pasākumi', 'icon' => '🎪', 'points' => 40],
];

// Маппинг категорий и иконок
$category_map = [
    'adoption' => 'Adopcijas Diena',
    'volunteer' => 'Brīvprātīgie',
    'training' => 'Apmācība',
    'fundraising' => 'Labdarība'
];

$icon_map = [
    'adoption' => '🐕🐈',
    'volunteer' => '🧹🏡',
    'training' => '📚🎓',
    'fundraising' => '💝🎪'
];

// Первая буква username
$initial = mb_strtoupper(mb_substr($user['lietotajvards'] ?? 'U', 0, 1));
?>
<!DOCTYPE html>
<html lang="lv">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Mans profils - <?php echo htmlspecialchars($user['lietotajvards'] ?? 'User'); ?></title>
<link rel="stylesheet" href="index.css">
<style>
body { 
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); 
    min-height: 100vh; 
    padding-bottom: 40px; 
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
    margin: 0;
}
.account-container { max-width: 1400px; margin: 40px auto; padding: 0 20px; }
.page-header { 
    background: #fff; 
    padding: 30px; 
    border-radius: 15px; 
    box-shadow: 0 5px 20px rgba(0,0,0,0.1); 
    margin-bottom: 30px; 
    text-align: center; 
}
.page-header h1 { color: #333; font-size: 32px; margin: 0 0 10px 0; }
.page-header p { color: #666; font-size: 16px; margin: 0; }
.profile-grid { display: grid; grid-template-columns: 350px 1fr; gap: 30px; }
.card { 
    background: #fff; 
    border-radius: 15px; 
    padding: 30px; 
    box-shadow: 0 5px 20px rgba(0,0,0,0.1); 
}
.profile-avatar { 
    width: 120px; 
    height: 120px; 
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); 
    border-radius: 50%; 
    display: flex; 
    align-items: center; 
    justify-content: center; 
    margin: 0 auto 20px; 
    font-size: 48px; 
    color: #fff; 
    font-weight: bold; 
}
.profile-username { font-size: 24px; font-weight: bold; color: #333; text-align: center; margin-bottom: 8px; }
.profile-email { color: #666; font-size: 14px; text-align: center; word-break: break-word; margin-bottom: 25px; }
.info-section { margin-bottom: 25px; }
.info-section h3 { 
    font-size: 14px; 
    color: #999; 
    text-transform: uppercase; 
    margin: 0 0 15px 0; 
    font-weight: 600; 
}
.info-item { background: #f9f9f9; padding: 15px; border-radius: 8px; margin-bottom: 12px; }
.info-label { font-size: 12px; color: #666; text-transform: uppercase; font-weight: 600; margin-bottom: 5px; }
.info-value { font-size: 16px; color: #333; font-weight: 500; }
.info-value.empty { color: #999; font-style: italic; }
.section-title { 
    font-size: 22px; 
    font-weight: bold; 
    color: #333; 
    margin: 0 0 20px 0; 
    padding-bottom: 15px; 
    border-bottom: 3px solid #667eea; 
}
.form-group { margin-bottom: 20px; }
label { display: block; margin-bottom: 8px; color: #555; font-weight: 600; font-size: 14px; }
input, textarea { 
    width: 100%; 
    padding: 12px 15px; 
    border: 2px solid #e0e0e0; 
    border-radius: 8px; 
    font-size: 16px; 
    box-sizing: border-box;
}
input:focus, textarea:focus { outline: none; border-color: #667eea; }
textarea { resize: vertical; min-height: 100px; font-family: inherit; }
.btn { 
    padding: 12px 30px; 
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); 
    color: #fff; 
    border: none; 
    border-radius: 8px; 
    font-size: 16px; 
    font-weight: 600; 
    cursor: pointer; 
}
.btn:hover { transform: translateY(-2px); box-shadow: 0 5px 15px rgba(102,126,234,0.4); }
.alert { padding: 15px 20px; border-radius: 8px; margin-bottom: 25px; font-weight: 500; }
.alert-success { background: #d4edda; border: 2px solid #c3e6cb; color: #155724; }
.alert-error { background: #f8d7da; border: 2px solid #f5c6cb; color: #721c24; }
.alert-bonus { background: linear-gradient(135deg, #fff3cd 0%, #ffeaa7 100%); border: 2px solid #ffc107; color: #856404; font-size: 18px; }
.bonus-section { background: #fff; border-radius: 15px; padding: 30px; box-shadow: 0 5px 20px rgba(0,0,0,0.1); margin-top: 30px; }
.level-card { 
    background: linear-gradient(135deg, <?php echo $level_color; ?> 0%, <?php echo $level_color; ?>dd 100%); 
    padding: 25px; 
    border-radius: 12px; 
    color: #fff; 
    margin-bottom: 25px; 
    display: flex; 
    align-items: center; 
    gap: 20px; 
}
.level-icon { font-size: 60px; }
.level-info h3 { font-size: 24px; margin: 0 0 8px 0; font-weight: bold; }
.level-info p { margin: 0 0 15px 0; opacity: 0.9; }
.points-display { margin-left: auto; text-align: right; }
.points { font-size: 36px; font-weight: bold; line-height: 1; }
.points-display .label { font-size: 14px; opacity: 0.9; text-transform: uppercase; }
.points-display .sublabel { font-size: 12px; opacity: 0.7; margin-top: 5px; }
.progress-bar-container { background: rgba(255,255,255,0.3); height: 8px; border-radius: 10px; overflow: hidden; }
.progress-bar { background: #fff; height: 100%; border-radius: 10px; }
.section-title-bonus { font-size: 20px; font-weight: bold; color: #333; margin: 25px 0 15px 0; }
.achievements-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 15px; margin-top: 20px; }
.achievement-card { background: #f8f9ff; padding: 20px; border-radius: 12px; text-align: center; border: 2px solid #e5e7eb; }
.achievement-card.earned { background: linear-gradient(135deg, #f0f9ff 0%, #e0f2fe 100%); border-color: #6366f1; }
.achievement-card.locked { opacity: 0.5; filter: grayscale(100%); }
.achievement-icon { font-size: 40px; margin-bottom: 12px; }
.achievement-name { font-size: 16px; font-weight: bold; color: #333; margin-bottom: 8px; }
.achievement-desc { font-size: 13px; color: #666; margin-bottom: 10px; }
.achievement-points { font-size: 14px; font-weight: bold; color: #6366f1; }
.stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; margin-top: 20px; }
.stat-card { background: #f8f9ff; padding: 20px; border-radius: 12px; text-align: center; border: 2px solid #e5e7eb; }
.stat-number { font-size: 32px; font-weight: bold; color: #6366f1; margin-bottom: 5px; }
.stat-label { color: #666; font-size: 14px; }

/* Стили для мероприятий */
.events-grid-profile { 
    display: grid; 
    grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); 
    gap: 20px; 
    margin-top: 20px; 
}
.event-card-profile { 
    background: #f8f9ff; 
    border-radius: 12px; 
    overflow: hidden; 
    border: 2px solid #e5e7eb; 
    transition: all 0.3s; 
}
.event-card-profile:hover { 
    transform: translateY(-4px); 
    box-shadow: 0 8px 20px rgba(99,102,241,0.15); 
}
.event-header-profile { 
    padding: 20px; 
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); 
    color: white; 
    display: flex; 
    align-items: center; 
    gap: 15px; 
}
.event-icon-profile { font-size: 32px; }
.event-header-text h4 { margin: 0 0 5px 0; font-size: 18px; font-weight: bold; }
.event-header-text p { margin: 0; font-size: 13px; opacity: 0.9; }
.event-body-profile { padding: 20px; }
.event-info-row { 
    display: flex; 
    align-items: center; 
    gap: 10px; 
    margin-bottom: 12px; 
    font-size: 14px; 
    color: #555; 
}
.event-info-row strong { color: #333; }
.event-badge-profile { 
    display: inline-block; 
    padding: 5px 12px; 
    border-radius: 20px; 
    font-size: 12px; 
    font-weight: bold; 
    margin-top: 10px; 
}
.badge-upcoming-profile { background: #10b981; color: white; }
.badge-past-profile { background: #6b7280; color: white; }
.empty-events { 
    text-align: center; 
    padding: 40px 20px; 
    color: #999; 
}
.empty-events-icon { font-size: 64px; margin-bottom: 20px; opacity: 0.3; }
.empty-events h3 { color: #666; font-size: 20px; margin-bottom: 10px; }
.empty-events p { color: #999; font-size: 14px; }
.empty-events a { 
    display: inline-block; 
    margin-top: 20px; 
    padding: 12px 30px; 
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); 
    color: white; 
    text-decoration: none; 
    border-radius: 8px; 
    font-weight: 600; 
}

@media (max-width: 968px) { 
    .profile-grid { grid-template-columns: 1fr; } 
    .achievements-grid { grid-template-columns: repeat(auto-fill, minmax(150px, 1fr)); } 
    .stats-grid { grid-template-columns: repeat(2, 1fr); }
    .events-grid-profile { grid-template-columns: 1fr; }
}
@media (max-width: 576px) { 
    .level-card { flex-direction: column; text-align: center; } 
    .points-display { margin-left: 0; margin-top: 15px; } 
    .stats-grid { grid-template-columns: 1fr; }
}
</style>
</head>
<body>
<?php include 'navbar.php'; ?>

<div class="account-container">
    <div class="page-header">
        <h1>👤 Mans profils</h1>
        <p>Pārvaldiet savu profilu un iestatījumus</p>
    </div>

    <div class="profile-grid">
        <aside class="card">
            <div class="profile-avatar"><?php echo $initial; ?></div>
            <div class="profile-username"><?php echo htmlspecialchars($user['lietotajvards'] ?? 'User'); ?></div>
            <div class="profile-email"><?php echo htmlspecialchars($user['epasts'] ?? ''); ?></div>

            <div class="info-section">
                <h3>Personīgā informācija</h3>
                <div class="info-item">
                    <div class="info-label">Pilnais vārds</div>
                    <div class="info-value <?php echo empty($user['full_name']) ? 'empty' : ''; ?>">
                        <?php echo !empty($user['full_name']) ? htmlspecialchars($user['full_name']) : 'Nav norādīts'; ?>
                    </div>
                </div>
                <div class="info-item">
                    <div class="info-label">Telefons</div>
                    <div class="info-value <?php echo empty($user['phone']) ? 'empty' : ''; ?>">
                        <?php echo !empty($user['phone']) ? htmlspecialchars($user['phone']) : 'Nav norādīts'; ?>
                    </div>
                </div>
                <div class="info-item">
                    <div class="info-label">Adrese</div>
                    <div class="info-value <?php echo empty($user['address']) ? 'empty' : ''; ?>">
                        <?php echo !empty($user['address']) ? nl2br(htmlspecialchars($user['address'])) : 'Nav norādīta'; ?>
                    </div>
                </div>
            </div>

            <div class="info-section">
                <h3>Konta statistika</h3>
                <div class="info-item">
                    <div class="info-label">Reģistrācijas datums</div>
                    <div class="info-value">
                        <?php echo !empty($user['created_at']) ? date('d.m.Y', strtotime($user['created_at'])) : '—'; ?>
                    </div>
                </div>
                <div class="info-item">
                    <div class="info-label">Pēdējā atjaunināšana</div>
                    <div class="info-value">
                        <?php echo !empty($user['updated_at']) ? date('d.m.Y H:i', strtotime($user['updated_at'])) : '—'; ?>
                    </div>
                </div>
            </div>
        </aside>

        <main class="card">
            <?php if ($bonus_awarded): ?>
                <div class="alert alert-bonus">
                    🎉 Apsveicam! Jūs ieguvāt jaunu sasniegumu "Pilnīgs Profils" un +20 punktus!
                </div>
            <?php endif; ?>
            
            <?php if (!empty($success_message) && !$bonus_awarded): ?>
                <div class="alert alert-success">✅ <?php echo htmlspecialchars($success_message); ?></div>
            <?php endif; ?>
            
            <?php if (!empty($error_message)): ?>
                <div class="alert alert-error">❌ <?php echo htmlspecialchars($error_message); ?></div>
            <?php endif; ?>

            <h2 class="section-title">📝 Rediģēt profilu</h2>
            <form method="POST" action="">
                <input type="hidden" name="action" value="update_profile">
                <div class="form-group">
                    <label for="full_name">Pilnais vārds *</label>
                    <input 
                        type="text" 
                        id="full_name" 
                        name="full_name" 
                        value="<?php echo htmlspecialchars($user['full_name'] ?? ''); ?>" 
                        placeholder="Ievadiet savu pilno vārdu"
                        maxlength="100"
                    >
                </div>
                <div class="form-group">
                    <label for="phone">Telefons *</label>
                    <input 
                        type="tel" 
                        id="phone" 
                        name="phone" 
                        value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>" 
                        placeholder="+371 12345678"
                        maxlength="20"
                    >
                </div>
                <div class="form-group">
                    <label for="address">Adrese *</label>
                    <textarea id="address" name="address" placeholder="Ievadiet savu adresi"><?php echo htmlspecialchars($user['address'] ?? ''); ?></textarea>
                </div>
                <p style="font-size:13px; color:#666; margin-bottom:20px;">
                    * Aizpildiet visus laukus, lai saņemtu +20 punktus un sasniegumu "Pilnīgs Profils"
                </p>
                <button type="submit" class="btn">💾 Saglabāt izmaiņas</button>
            </form>
        </main>
    </div>

    <!-- Секция с мероприятиями -->
    <div class="bonus-section">
        <h3 class="section-title-bonus">🎉 Mani pasākumi</h3>
        <?php if (count($user_events) > 0): ?>
            <div class="events-grid-profile">
                <?php 
                $today = new DateTime();
                foreach ($user_events as $event): 
                    $event_date = new DateTime($event['datums']);
                    $is_past = $event_date < $today;
                    $formatted_date = $event_date->format('d.m.Y');
                    $time_start = date('H:i', strtotime($event['laiks_sakums']));
                    $time_end = date('H:i', strtotime($event['laiks_beigas']));
                    $reg_date = new DateTime($event['registracijas_datums']);
                    $formatted_reg_date = $reg_date->format('d.m.Y H:i');
                ?>
                <div class="event-card-profile">
                    <div class="event-header-profile">
                        <div class="event-icon-profile"><?php echo $icon_map[$event['kategorija']] ?? '🎉'; ?></div>
                        <div class="event-header-text">
                            <h4><?php echo htmlspecialchars($event['nosaukums']); ?></h4>
                            <p><?php echo $category_map[$event['kategorija']] ?? $event['kategorija']; ?></p>
                        </div>
                    </div>
                    <div class="event-body-profile">
                        <div class="event-info-row">
                            📅 <strong><?php echo $formatted_date; ?></strong>
                        </div>
                        <div class="event-info-row">
                            🕐 <strong><?php echo $time_start . ' - ' . $time_end; ?></strong>
                        </div>
                        <div class="event-info-row">
                            📍 <strong><?php echo htmlspecialchars($event['vieta']); ?></strong>
                        </div>
                        <div class="event-info-row">
                            👥 <strong><?php echo $event['current_participants']; ?>/<?php echo $event['max_dalibnieki']; ?></strong> dalībnieki
                        </div>
                        <div class="event-info-row">
                            ✅ Reģistrēts: <strong><?php echo $formatted_reg_date; ?></strong>
                        </div>
                        <span class="event-badge-profile <?php echo $is_past ? 'badge-past-profile' : 'badge-upcoming-profile'; ?>">
                            <?php echo $is_past ? '✓ Pagājis' : '📅 Gaidāms'; ?>
                        </span>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="empty-events">
                <div class="empty-events-icon">🎪</div>
                <h3>Nav reģistrētu pasākumu</h3>
                <p>Jūs vēl neesat reģistrējies nevienam pasākumam</p>
                <a href="events.php">Skatīt pasākumus</a>
            </div>
        <?php endif; ?>
    </div>

    <div class="bonus-section">
        <div class="level-card">
            <div class="level-icon"><?php echo $level_icon; ?></div>
            <div class="level-info">
                <h3><?php echo htmlspecialchars($user['level_name'] ?? 'Iesācējs'); ?></h3>
                <p>Tavs pašreizējais līmenis</p>
                <div class="progress-bar-container">
                    <?php 
                    $progress = 0;
                    if ($points < 100) {
                        $progress = $points;
                    } elseif ($points < 300) {
                        $progress = (($points - 100) / 200) * 100;
                    } elseif ($points < 600) {
                        $progress = (($points - 300) / 300) * 100;
                    } elseif ($points < 1000) {
                        $progress = (($points - 600) / 400) * 100;
                    } else {
                        $progress = 100;
                    }
                    ?>
                    <div class="progress-bar" style="width: <?php echo min(100, max(0, $progress)); ?>%"></div>
                </div>
            </div>
            <div class="points-display">
                <div class="points"><?php echo $points; ?></div>
                <div class="label">punkti</div>
                <div class="sublabel">Kopā: <?php echo (int)($user['total_earned'] ?? 0); ?></div>
            </div>
        </div>

        <h3 class="section-title-bonus">🏆 Mani sasniegumi</h3>
        <div class="achievements-grid">
            <?php foreach ($all_achievements as $ach): ?>
                <?php $is_earned = in_array($ach['id'], $earned_ids); ?>
                <div class="achievement-card <?php echo $is_earned ? 'earned' : 'locked'; ?>">
                    <div class="achievement-icon"><?php echo $ach['icon']; ?></div>
                    <div class="achievement-name"><?php echo $ach['name']; ?></div>
                    <div class="achievement-desc"><?php echo $ach['desc']; ?></div>
                    <div class="achievement-points">
                        <?php echo $is_earned ? '✅ Iegūts' : '+' . $ach['points'] . ' punkti'; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <h3 class="section-title-bonus">📊 Mana statistika</h3>
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-number"><?php echo (int)($user['favorites_count'] ?? 0); ?></div>
                <div class="stat-label">Favorīti</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo (int)($user['applications_count'] ?? 0); ?></div>
                <div class="stat-label">Pieteikumi</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo count($user_events); ?></div>
                <div class="stat-label">Reģistrēti pasākumi</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo (int)($user['profile_complete'] ?? 0); ?>%</div>
                <div class="stat-label">Profils aizpildīts</div>
            </div>
        </div>
    </div>
</div>

</body>
</html>