<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
require_once 'db_conn.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Получение данных пользователя
$stmt = $conn->prepare("SELECT id, username, email, full_name, phone, address, created_at, updated_at FROM users WHERE id = ?");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$stmt->close();

if (!$user) {
    session_destroy();
    header("Location: login.php");
    exit();
}

$_SESSION['lietotajvards'] = $user['username'];
$_SESSION['epasts'] = $user['email'];

// Получаем статистику пользователя (для примера используем моковые данные)
$user_stats = [
    'points' => 75, // Баллы пользователя
    'favorites_count' => 3,
    'applications_count' => 1,
    'events_attended' => 0,
    'profile_complete' => (!empty($user['full_name']) && !empty($user['phone']) && !empty($user['address'])) ? 100 : 60
];

// Определяем уровень
function getUserLevel($points) {
    if ($points >= 1000) return ['name' => 'SirdsPaws Leģenda', 'icon' => '👑', 'color' => '#FFD700'];
    if ($points >= 600) return ['name' => 'Dzīvnieku Varonis', 'icon' => '💎', 'color' => '#E74C3C'];
    if ($points >= 300) return ['name' => 'Aktīvs Atbalstītājs', 'icon' => '🥇', 'color' => '#3498DB'];
    if ($points >= 100) return ['name' => 'Patversmes Draugs', 'icon' => '🥈', 'color' => '#95A5A6'];
    return ['name' => 'Iesācējs', 'icon' => '🥉', 'color' => '#BDC3C7'];
}

$current_level = getUserLevel($user_stats['points']);

// Достижения
$achievements = [
    [
        'id' => 'first_step',
        'name' => 'Pirmais Solis',
        'description' => 'Reģistrējies sistēmā',
        'icon' => '🎯',
        'earned' => true,
        'points' => 10
    ],
    [
        'id' => 'profile_complete',
        'name' => 'Pilnīgs Profils',
        'description' => 'Aizpildīts viss profils',
        'icon' => '📱',
        'earned' => $user_stats['profile_complete'] == 100,
        'points' => 20
    ],
    [
        'id' => 'animal_lover',
        'name' => 'Dzīvnieku Draugs',
        'description' => 'Pievienoti 5 favorīti',
        'icon' => '❤️',
        'earned' => $user_stats['favorites_count'] >= 5,
        'points' => 30
    ],
    [
        'id' => 'first_application',
        'name' => 'Atbildīgs Adopcētājs',
        'description' => 'Iesniegts pirmais pieteikums',
        'icon' => '📝',
        'earned' => $user_stats['applications_count'] >= 1,
        'points' => 50
    ],
    [
        'id' => 'event_participant',
        'name' => 'Aktīvais Dalībnieks',
        'description' => 'Apmeklēti 3 pasākumi',
        'icon' => '🎪',
        'earned' => $user_stats['events_attended'] >= 3,
        'points' => 40
    ]
];

$success_message = '';
$error_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    
    if ($_POST['action'] === 'update_profile') {
        $full_name = trim($_POST['full_name'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $address = trim($_POST['address'] ?? '');
        
        $stmt = $conn->prepare("UPDATE users SET full_name = ?, phone = ?, address = ? WHERE id = ?");
        $stmt->bind_param("sssi", $full_name, $phone, $address, $_SESSION['user_id']);
        
        if ($stmt->execute()) {
            $success_message = 'Profils veiksmīgi atjaunināts!';
            $user['full_name'] = $full_name;
            $user['phone'] = $phone;
            $user['address'] = $address;
        } else {
            $error_message = 'Kļūda atjauninot profilu: ' . $stmt->error;
        }
        $stmt->close();
    }
    
    if ($_POST['action'] === 'change_password') {
        $current_password = $_POST['current_password'] ?? '';
        $new_password = $_POST['new_password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';
        
        if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
            $error_message = 'Lūdzu, aizpildiet visus laukus';
        } elseif ($new_password !== $confirm_password) {
            $error_message = 'Jaunās paroles nesakrīt';
        } elseif (strlen($new_password) < 6) {
            $error_message = 'Jaunajai parolei jābūt vismaz 6 simboliem';
        } else {
            $stmt = $conn->prepare("SELECT password FROM users WHERE id = ?");
            $stmt->bind_param("i", $_SESSION['user_id']);
            $stmt->execute();
            $result = $stmt->get_result();
            $pass_data = $result->fetch_assoc();
            $stmt->close();
            
            if (password_verify($current_password, $pass_data['password'])) {
                $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                $stmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
                $stmt->bind_param("si", $hashed_password, $_SESSION['user_id']);
                
                if ($stmt->execute()) {
                    $success_message = 'Parole veiksmīgi nomainīta!';
                } else {
                    $error_message = 'Kļūda mainot paroli: ' . $stmt->error;
                }
                $stmt->close();
            } else {
                $error_message = 'Nepareiza pašreizējā parole';
            }
        }
    }
}

function getInitial($username) {
    return strtoupper(mb_substr($username, 0, 1));
}
?>
<!DOCTYPE html>
<html lang="lv">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mans profils - <?php echo htmlspecialchars($user['username']); ?></title>
    <link rel="stylesheet" href="index.css">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding-bottom: 40px;
        }
        
        .account-container { 
            max-width: 1400px; 
            margin: 40px auto; 
            padding: 0 20px; 
        }
        
        .page-header {
            background: white;
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.1);
            margin-bottom: 30px;
            text-align: center;
        }
        
        .page-header h1 { color: #333; font-size: 32px; margin-bottom: 10px; margin-top: 0; }
        .page-header p { color: #666; font-size: 16px; margin: 0; }
        
        /* Бонусная секция */
        .bonus-section {
            background: white;
            border-radius: 15px;
            padding: 30px;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.1);
            margin-bottom: 30px;
        }
        
        .level-card {
            background: linear-gradient(135deg, <?php echo $current_level['color']; ?> 0%, <?php echo $current_level['color']; ?>dd 100%);
            padding: 25px;
            border-radius: 12px;
            color: white;
            margin-bottom: 25px;
            display: flex;
            align-items: center;
            gap: 20px;
        }
        
        .level-icon {
            font-size: 60px;
            line-height: 1;
        }
        
        .level-info h3 {
            margin: 0 0 5px 0;
            font-size: 24px;
        }
        
        .level-info p {
            margin: 0;
            opacity: 0.9;
        }
        
        .points-display {
            margin-left: auto;
            text-align: right;
        }
        
        .points-display .points {
            font-size: 36px;
            font-weight: bold;
            line-height: 1;
        }
        
        .points-display .label {
            font-size: 14px;
            opacity: 0.9;
        }
        
        .progress-bar-container {
            background: rgba(255,255,255,0.3);
            height: 8px;
            border-radius: 10px;
            margin-top: 15px;
            overflow: hidden;
        }
        
        .progress-bar {
            background: white;
            height: 100%;
            border-radius: 10px;
            transition: width 0.5s ease;
        }
        
        .achievements-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 15px;
            margin-top: 20px;
        }
        
        .achievement-card {
            background: #f8f9ff;
            padding: 20px;
            border-radius: 12px;
            text-align: center;
            border: 2px solid #e5e7eb;
            transition: all 0.3s;
            position: relative;
        }
        
        .achievement-card.earned {
            background: linear-gradient(135deg, #f0f9ff 0%, #e0f2fe 100%);
            border-color: #6366f1;
        }
        
        .achievement-card.locked {
            opacity: 0.5;
            filter: grayscale(100%);
        }
        
        .achievement-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 15px rgba(99,102,241,0.2);
        }
        
        .achievement-icon {
            font-size: 40px;
            margin-bottom: 10px;
        }
        
        .achievement-name {
            font-weight: bold;
            margin-bottom: 5px;
            color: #333;
        }
        
        .achievement-desc {
            font-size: 13px;
            color: #666;
            margin-bottom: 8px;
        }
        
        .achievement-points {
            background: #6366f1;
            color: white;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: bold;
            display: inline-block;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-top: 20px;
        }
        
        .stat-card {
            background: #f8f9ff;
            padding: 20px;
            border-radius: 12px;
            text-align: center;
        }
        
        .stat-number {
            font-size: 32px;
            font-weight: bold;
            color: #6366f1;
            margin-bottom: 5px;
        }
        
        .stat-label {
            color: #666;
            font-size: 14px;
        }
        
        .section-title-bonus {
            font-size: 20px;
            font-weight: bold;
            color: #333;
            margin-bottom: 15px;
            margin-top: 25px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .profile-grid {
            display: grid;
            grid-template-columns: 350px 1fr;
            gap: 30px;
        }
        
        .card {
            background: white;
            border-radius: 15px;
            padding: 30px;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.1);
        }
        
        .profile-sidebar { position: sticky; top: 120px; }
        
        .profile-avatar-section {
            text-align: center;
            padding-bottom: 25px;
            border-bottom: 2px solid #f0f0f0;
            margin-bottom: 25px;
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
            color: white;
            font-weight: bold;
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.3);
        }
        
        .profile-username {
            font-size: 24px;
            font-weight: bold;
            color: #333;
            margin-bottom: 8px;
        }
        
        .profile-email {
            color: #666;
            font-size: 14px;
            word-break: break-word;
        }
        
        .info-section { margin-bottom: 25px; }
        .info-section:last-child { margin-bottom: 0; }
        
        .info-section h3 {
            font-size: 14px;
            color: #999;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 15px;
            font-weight: 600;
            margin-top: 0;
        }
        
        .info-item {
            background: #f9f9f9;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 12px;
        }
        
        .info-item:last-child { margin-bottom: 0; }
        
        .info-label {
            font-size: 12px;
            color: #666;
            text-transform: uppercase;
            font-weight: 600;
            margin-bottom: 5px;
            letter-spacing: 0.5px;
        }
        
        .info-value { font-size: 16px; color: #333; font-weight: 500; }
        .info-value.empty { color: #999; font-style: italic; }
        
        .form-section { margin-bottom: 40px; }
        .form-section:last-child { margin-bottom: 0; }
        
        .section-title {
            font-size: 22px;
            font-weight: bold;
            color: #333;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 3px solid #667eea;
            margin-top: 0;
        }
        
        .form-group { margin-bottom: 20px; }
        
        label {
            display: block;
            margin-bottom: 8px;
            color: #555;
            font-weight: 600;
            font-size: 14px;
        }
        
        input, textarea {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 16px;
            font-family: inherit;
            transition: all 0.3s;
        }
        
        input:focus, textarea:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }
        
        textarea { resize: vertical; min-height: 100px; }
        
        .btn {
            padding: 12px 30px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        }
        
        .btn:active { transform: translateY(0); }
        
        .alert {
            padding: 15px 20px;
            border-radius: 8px;
            margin-bottom: 25px;
            font-weight: 500;
        }
        
        .alert-success {
            background: #d4edda;
            border: 2px solid #c3e6cb;
            color: #155724;
        }
        
        .alert-error {
            background: #f8d7da;
            border: 2px solid #f5c6cb;
            color: #721c24;
        }
        
        .divider {
            height: 2px;
            background: linear-gradient(to right, transparent, #e0e0e0, transparent);
            margin: 40px 0;
        }
        
        @media (max-width: 968px) {
            .profile-grid { grid-template-columns: 1fr; }
            .profile-sidebar { position: static; }
            .achievements-grid { grid-template-columns: repeat(auto-fill, minmax(150px, 1fr)); }
        }
        
        @media (max-width: 576px) {
            .card { padding: 20px; }
            .page-header h1 { font-size: 24px; }
            .level-card { flex-direction: column; text-align: center; }
            .points-display { margin-left: 0; margin-top: 15px; }
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
        
        <!-- Бонусная секция -->
        <div class="bonus-section">
            <div class="level-card">
                <div class="level-icon"><?php echo $current_level['icon']; ?></div>
                <div class="level-info">
                    <h3><?php echo $current_level['name']; ?></h3>
                    <p>Tavs pašreizējais līmenis</p>
                    <div class="progress-bar-container">
                        <div class="progress-bar" style="width: <?php echo min(100, ($user_stats['points'] % 100)); ?>%"></div>
                    </div>
                </div>
                <div class="points-display">
                    <div class="points"><?php echo $user_stats['points']; ?></div>
                    <div class="label">punkti</div>
                </div>
            </div>
            
            <h3 class="section-title-bonus">🏆 Mani sasniegumi</h3>
            <div class="achievements-grid">
                <?php foreach ($achievements as $achievement): ?>
                    <div class="achievement-card <?php echo $achievement['earned'] ? 'earned' : 'locked'; ?>">
                        <div class="achievement-icon"><?php echo $achievement['icon']; ?></div>
                        <div class="achievement-name"><?php echo $achievement['name']; ?></div>
                        <div class="achievement-desc"><?php echo $achievement['description']; ?></div>
                        <div class="achievement-points">+<?php echo $achievement['points']; ?> punkti</div>
                    </div>
                <?php endforeach; ?>
            </div>
            
            <h3 class="section-title-bonus">📊 Mana statistika</h3>
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-number"><?php echo $user_stats['favorites_count']; ?></div>
                    <div class="stat-label">Favorīti</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?php echo $user_stats['applications_count']; ?></div>
                    <div class="stat-label">Pieteikumi</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?php echo $user_stats['events_attended']; ?></div>
                    <div class="stat-label">Apmeklēti pasākumi</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?php echo $user_stats['profile_complete']; ?>%</div>
                    <div class="stat-label">Profils aizpildīts</div>
                </div>
            </div>
        </div>
        
        <div class="profile-grid">
            <aside class="card profile-sidebar">
                <div class="profile-avatar-section">
                    <div class="profile-avatar">
                        <?php echo getInitial($user['username']); ?>
                    </div>
                    <div class="profile-username"><?php echo htmlspecialchars($user['username']); ?></div>
                    <div class="profile-email"><?php echo htmlspecialchars($user['email']); ?></div>
                </div>
                
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
                            <?php echo date('d.m.Y', strtotime($user['created_at'])); ?>
                        </div>
                    </div>
                    
                    <div class="info-item">
                        <div class="info-label">Pēdējā atjaunināšana</div>
                        <div class="info-value">
                            <?php echo date('d.m.Y', strtotime($user['updated_at'])); ?>
                        </div>
                    </div>
                </div>
            </aside>
            
            <main class="card">
                <?php if (!empty($success_message)): ?>
                    <div class="alert alert-success">
                        ✅ <?php echo htmlspecialchars($success_message); ?>
                    </div>
                <?php endif; ?>
                
                <?php if (!empty($error_message)): ?>
                    <div class="alert alert-error">
                        ❌ <?php echo htmlspecialchars($error_message); ?>
                    </div>
                <?php endif; ?>
                
                <div class="form-section">
                    <h2 class="section-title">📝 Rediģēt profilu</h2>
                    
                    <form method="POST" action="">
                        <input type="hidden" name="action" value="update_profile">
                        
                        <div class="form-group">
                            <label for="full_name">Pilnais vārds</label>
                            <input 
                                type="text" 
                                id="full_name" 
                                name="full_name" 
                                value="<?php echo htmlspecialchars($user['full_name'] ?? ''); ?>" 
                                placeholder="Ievadiet savu pilno vārdu">
                        </div>
                        
                        <div class="form-group">
                            <label for="phone">Telefons</label>
                            <input 
                                type="tel" 
                                id="phone" 
                                name="phone" 
                                value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>" 
                                placeholder="+371 12345678">
                        </div>
                        
                        <div class="form-group">
                            <label for="address">Adrese</label>
                            <textarea 
                                id="address" 
                                name="address" 
                                placeholder="Ievadiet savu adresi"><?php echo htmlspecialchars($user['address'] ?? ''); ?></textarea>
                        </div>
                        
                        <button type="submit" class="btn">💾 Saglabāt izmaiņas</button>
                    </form>
                </div>
                
                <div class="divider"></div>
                
                <div class="form-section">
                    <h2 class="section-title">🔒 Mainīt paroli</h2>
                    
                    <form method="POST" action="">
                        <input type="hidden" name="action" value="change_password">
                        
                        <div class="form-group">
                            <label for="current_password">Pašreizējā parole</label>
                            <input 
                                type="password" 
                                id="current_password" 
                                name="current_password" 
                                required 
                                placeholder="Ievadiet pašreizējo paroli">
                        </div>
                        
                        <div class="form-group">
                            <label for="new_password">Jaunā parole</label>
                            <input 
                                type="password" 
                                id="new_password" 
                                name="new_password" 
                                required 
                                placeholder="Vismaz 6 simboli">
                        </div>
                        
                        <div class="form-group">
                            <label for="confirm_password">Apstipriniet jauno paroli</label>
                            <input 
                                type="password" 
                                id="confirm_password" 
                                name="confirm_password" 
                                required 
                                placeholder="Atkārtojiet jauno paroli">
                        </div>
                        
                        <button type="submit" class="btn">🔑 Mainīt paroli</button>
                    </form>
                </div>
            </main>
        </div>
    </div>
</body>
</html>