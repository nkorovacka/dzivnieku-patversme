<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
require_once 'db_conn.php';

if (empty($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$userId = (int)$_SESSION['user_id'];

$query = "SELECT * FROM lietotaji WHERE id = :user_id LIMIT 1";
$stmt = $conn->prepare($query);
$stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
$stmt->execute();
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    session_destroy();
    header("Location: login.php");
    exit();
}

$success_message = '';
$error_message = '';
$bonus_awarded = false;

function awardPointsForFavorites($userId, $conn) {
    $checkQuery = "SELECT COUNT(*) FROM points_history WHERE user_id = :user_id AND reason = 'favorites_bonus'";
    $checkStmt = $conn->prepare($checkQuery);
    $checkStmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
    $checkStmt->execute();
    $alreadyAwarded = $checkStmt->fetchColumn() > 0;
    
    if ($alreadyAwarded) {
        return false;
    }
    
    $query = "SELECT COUNT(*) FROM favorites WHERE user_id = :user_id";
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
    $stmt->execute();
    $favorites_count = $stmt->fetchColumn();

    $updateCountQuery = "UPDATE lietotaji SET favorites_count = :count WHERE id = :user_id";
    $updateStmt = $conn->prepare($updateCountQuery);
    $updateStmt->bindParam(':count', $favorites_count, PDO::PARAM_INT);
    $updateStmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
    $updateStmt->execute();

    if ($favorites_count >= 5) {
        $bonusPoints = 10;
        $query = "UPDATE lietotaji SET points = points + :bonusPoints, total_earned = total_earned + :bonusPoints WHERE id = :user_id";
        $stmt = $conn->prepare($query);
        $stmt->bindParam(':bonusPoints', $bonusPoints, PDO::PARAM_INT);
        $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
        $stmt->execute();

        $historyQuery = "INSERT INTO points_history (user_id, points, reason, created_at) VALUES (:user_id, :bonusPoints, 'favorites_bonus', NOW())";
        $stmt = $conn->prepare($historyQuery);
        $stmt->bindParam(':bonusPoints', $bonusPoints, PDO::PARAM_INT);
        $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
        $stmt->execute();

        return true;
    }
    return false;
}

function awardPointsForEvents($userId, $conn) {
    $checkQuery = "SELECT COUNT(*) FROM points_history WHERE user_id = :user_id AND reason = 'event_bonus'";
    $checkStmt = $conn->prepare($checkQuery);
    $checkStmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
    $checkStmt->execute();
    $alreadyAwarded = $checkStmt->fetchColumn() > 0;
    
    if ($alreadyAwarded) {
        return false;
    }
    
    $query = "SELECT COUNT(*) FROM pasakumu_pieteikumi WHERE lietotaja_id = :user_id";
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
    $stmt->execute();
    $events_count = $stmt->fetchColumn();

    $updateCountQuery = "UPDATE lietotaji SET events_attended = :count WHERE id = :user_id";
    $updateStmt = $conn->prepare($updateCountQuery);
    $updateStmt->bindParam(':count', $events_count, PDO::PARAM_INT);
    $updateStmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
    $updateStmt->execute();

    if ($events_count >= 1) {
        $bonusPoints = 15;
        $query = "UPDATE lietotaji SET points = points + :bonusPoints, total_earned = total_earned + :bonusPoints WHERE id = :user_id";
        $stmt = $conn->prepare($query);
        $stmt->bindParam(':bonusPoints', $bonusPoints, PDO::PARAM_INT);
        $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
        $stmt->execute();

        $historyQuery = "INSERT INTO points_history (user_id, points, reason, created_at) VALUES (:user_id, :bonusPoints, 'event_bonus', NOW())";
        $stmt = $conn->prepare($historyQuery);
        $stmt->bindParam(':bonusPoints', $bonusPoints, PDO::PARAM_INT);
        $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
        $stmt->execute();

        return true;
    }
    return false;
}

function awardPointsForApplication($userId, $conn) {
    // Check if points for applications have already been awarded
    $checkQuery = "SELECT COUNT(*) FROM points_history WHERE user_id = :user_id AND reason = 'application_bonus'";
    $checkStmt = $conn->prepare($checkQuery);
    $checkStmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
    $checkStmt->execute();
    $alreadyAwarded = $checkStmt->fetchColumn() > 0;
    
    if ($alreadyAwarded) {
        return false;
    }
    
    // Count the number of approved applications submitted by the user
    $query = "SELECT COUNT(*) FROM pieteikumi WHERE lietotaja_id = :user_id AND statuss = 'apstiprinats'";
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
    $stmt->execute();
    $applications_count = $stmt->fetchColumn();

    // Update the user's application count in the `lietotaji` table
    $updateCountQuery = "UPDATE lietotaji SET applications_count = :count WHERE id = :user_id";
    $updateStmt = $conn->prepare($updateCountQuery);
    $updateStmt->bindParam(':count', $applications_count, PDO::PARAM_INT);
    $updateStmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
    $updateStmt->execute();

    // Award bonus points if at least 1 application is approved
    if ($applications_count >= 1) {
        $bonusPoints = 20;  // Award 20 points for a submitted application
        $query = "UPDATE lietotaji SET points = points + :bonusPoints, total_earned = total_earned + :bonusPoints WHERE id = :user_id";
        $stmt = $conn->prepare($query);
        $stmt->bindParam(':bonusPoints', $bonusPoints, PDO::PARAM_INT);
        $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
        $stmt->execute();

        // Log the bonus points in the history
        $historyQuery = "INSERT INTO points_history (user_id, points, reason, created_at) VALUES (:user_id, :bonusPoints, 'application_bonus', NOW())";
        $stmt = $conn->prepare($historyQuery);
        $stmt->bindParam(':bonusPoints', $bonusPoints, PDO::PARAM_INT);
        $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
        $stmt->execute();

        return true;
    }
    return false;
}

function calculateProfileComplete($user) {
    $fields = ['full_name', 'phone', 'address'];
    $filled = 0;
    $total = count($fields);
    
    foreach ($fields as $field) {
        if (!empty($user[$field])) {
            $filled++;
        }
    }
    
    return round(($filled / $total) * 100);
}

if (empty($user['first_login'])) {
    $bonusPoints = 10;
    $query = "UPDATE lietotaji SET points = points + :bonusPoints, total_earned = total_earned + :bonusPoints, first_login = 1 WHERE id = :user_id";
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':bonusPoints', $bonusPoints, PDO::PARAM_INT);
    $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
    $stmt->execute();

    $historyQuery = "INSERT INTO points_history (user_id, points, reason, created_at) VALUES (:user_id, :bonusPoints, 'registration_bonus', NOW())";
    $stmt = $conn->prepare($historyQuery);
    $stmt->bindParam(':bonusPoints', $bonusPoints, PDO::PARAM_INT);
    $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
    $stmt->execute();

    $bonus_awarded = true;
    $success_message = '🎉 Jūs saņēmāt bonusu par reģistrāciju! +10 punkti!';
    
    $stmt = $conn->prepare("SELECT * FROM lietotaji WHERE id = :user_id LIMIT 1");
    $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
    $stmt->execute();
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
}

if (!$bonus_awarded) {
    $bonus_awarded = awardPointsForFavorites($userId, $conn) || awardPointsForEvents($userId, $conn) || awardPointsForApplication($userId, $conn);
    
    if ($bonus_awarded) {
        $stmt = $conn->prepare("SELECT * FROM lietotaji WHERE id = :user_id LIMIT 1");
        $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
        $stmt->execute();
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_profile') {
    $full_name = trim($_POST['full_name']);
    $phone = trim($_POST['phone']);
    $address = trim($_POST['address']);

    if (empty($full_name) || empty($phone) || empty($address)) {
        $error_message = 'Lūdzu, aizpildiet visus obligātos laukus!';
    } else {
        $update_query = "UPDATE lietotaji SET full_name = :full_name, phone = :phone, address = :address, updated_at = NOW() WHERE id = :user_id";
        $stmt = $conn->prepare($update_query);
        $stmt->bindParam(':full_name', $full_name);
        $stmt->bindParam(':phone', $phone);
        $stmt->bindParam(':address', $address);
        $stmt->bindParam(':user_id', $userId);
        
        if ($stmt->execute()) {
            $reload_query = "SELECT * FROM lietotaji WHERE id = :user_id";
            $reload_stmt = $conn->prepare($reload_query);
            $reload_stmt->bindParam(':user_id', $userId);
            $reload_stmt->execute();
            $user = $reload_stmt->fetch(PDO::FETCH_ASSOC);

            $profile_complete = calculateProfileComplete($user);
            $updateProfileQuery = "UPDATE lietotaji SET profile_complete = :profile_complete WHERE id = :user_id";
            $updateProfileStmt = $conn->prepare($updateProfileQuery);
            $updateProfileStmt->bindParam(':profile_complete', $profile_complete, PDO::PARAM_INT);
            $updateProfileStmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
            $updateProfileStmt->execute();
            
            $user['profile_complete'] = $profile_complete;

            $success_message = 'Profils veiksmīgi atjaunināts!';
        } else {
            $error_message = 'Kļūda, atjauninot profilu. Lūdzu, mēģiniet vēlreiz.';
        }
    }
}

$favoritesQuery = "SELECT COUNT(*) FROM favorites WHERE user_id = :user_id";
$favoritesStmt = $conn->prepare($favoritesQuery);
$favoritesStmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
$favoritesStmt->execute();
$user['favorites_count'] = $favoritesStmt->fetchColumn();

$applicationsQuery = "SELECT COUNT(*) FROM pieteikumi WHERE lietotaja_id = :user_id";
$applicationsStmt = $conn->prepare($applicationsQuery);
$applicationsStmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
$applicationsStmt->execute();
$user['applications_count'] = $applicationsStmt->fetchColumn();

$eventsQuery = "SELECT COUNT(*) FROM pasakumu_pieteikumi WHERE lietotaja_id = :user_id";
$eventsStmt = $conn->prepare($eventsQuery);
$eventsStmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
$eventsStmt->execute();
$user['events_attended'] = $eventsStmt->fetchColumn();

$user['profile_complete'] = calculateProfileComplete($user);

$points = (int)$user['points'];
$level_name = 'Bronzas līmenis';
$level_icon = '🥉';
$level_color = '#BDC3C7';

if ($points >= 1000) {
    $level_name = 'Karalis';
    $level_icon = '👑';
    $level_color = '#FFD700';
} elseif ($points >= 600) {
    $level_name = 'Dimanta līmenis';
    $level_icon = '💎';
    $level_color = '#E74C3C';
} elseif ($points >= 300) {
    $level_name = 'Zelta līmenis';
    $level_icon = '🥇';
    $level_color = '#3498DB';
} elseif ($points >= 100) {
    $level_name = 'Sudraba līmenis';
    $level_icon = '🥈';
    $level_color = '#95A5A6';
}

$updateLevelQuery = "UPDATE lietotaji SET level_name = :level_name WHERE id = :user_id";
$updateLevelStmt = $conn->prepare($updateLevelQuery);
$updateLevelStmt->bindParam(':level_name', $level_name);
$updateLevelStmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
$updateLevelStmt->execute();

$earned_ids = [];
if (!empty($user['achievements_json'])) {
    $decoded = json_decode($user['achievements_json'], true);
    if (is_array($decoded)) {
        $earned_ids = $decoded;
    }
}

$achievements_updated = false;

if (!in_array(1, $earned_ids) && !empty($user['first_login'])) {
    $earned_ids[] = 1;
    $achievements_updated = true;
}

if (!in_array(2, $earned_ids) && $user['profile_complete'] >= 100) {
    $earned_ids[] = 2;
    $achievements_updated = true;
}

if (!in_array(3, $earned_ids) && $user['favorites_count'] >= 5) {
    $earned_ids[] = 3;
    $achievements_updated = true;
}

if (!in_array(4, $earned_ids) && $user['applications_count'] >= 1) {
    $earned_ids[] = 4;
    $achievements_updated = true;
}

if (!in_array(5, $earned_ids) && $user['events_attended'] >= 1) {
    $earned_ids[] = 5;
    $achievements_updated = true;
}

if ($achievements_updated) {
    $achievements_json = json_encode($earned_ids);
    $updateAchievementsQuery = "UPDATE lietotaji SET achievements_json = :achievements WHERE id = :user_id";
    $updateAchievementsStmt = $conn->prepare($updateAchievementsQuery);
    $updateAchievementsStmt->bindParam(':achievements', $achievements_json);
    $updateAchievementsStmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
    $updateAchievementsStmt->execute();
}

$all_achievements = [
    ['id' => 1, 'name' => 'Pirmais Solis', 'desc' => 'Reģistrējies sistēmā', 'icon' => '🎯', 'points' => 10],
    ['id' => 2, 'name' => 'Pilnīgs Profils', 'desc' => 'Aizpildīts viss profils', 'icon' => '📱', 'points' => 20],
    ['id' => 3, 'name' => 'Dzīvnieku Draugs', 'desc' => 'Pievienoti 5 favorīti', 'icon' => '❤️', 'points' => 30],
    ['id' => 4, 'name' => 'Atbildīgs Adopcētājs', 'desc' => 'Iesniegts pirmais pieteikums', 'icon' => '📝', 'points' => 50],
    ['id' => 5, 'name' => 'Aktīvais Dalībnieks', 'desc' => 'Apmeklēti pasākumi', 'icon' => '🎪', 'points' => 40],
];

$initial = mb_strtoupper(mb_substr($user['lietotajvards'], 0, 1));
?>
<!DOCTYPE html>
<html lang="lv">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Mans profils - <?php echo htmlspecialchars($user['lietotajvards']); ?></title>
<link rel="stylesheet" href="index.css">
<style>
/* Your CSS styles here */
body { background: linear-gradient(135deg,#667eea 0%,#764ba2 100%); min-height:100vh; padding-bottom:40px; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; margin:0; }
.account-container { max-width:1400px; margin:40px auto; padding:0 20px; }
.page-header { background:#fff; padding:30px; border-radius:15px; box-shadow:0 5px 20px rgba(0,0,0,.1); margin-bottom:30px; text-align:center; }
.page-header h1 { color:#333; font-size:32px; margin:0 0 10px 0; }
.page-header p { color:#666; font-size:16px; margin:0; }
.profile-grid { display:grid; grid-template-columns:350px 1fr; gap:30px; }
.card { background:#fff; border-radius:15px; padding:30px; box-shadow:0 5px 20px rgba(0,0,0,.1); }
.profile-avatar { width:120px; height:120px; background:linear-gradient(135deg,#667eea 0%,#764ba2 100%); border-radius:50%; display:flex; align-items:center; justify-content:center; margin:0 auto 20px; font-size:48px; color:#fff; font-weight:bold; }
.profile-username { font-size:24px; font-weight:bold; color:#333; text-align:center; margin-bottom:8px; }
.profile-email { color:#666; font-size:14px; text-align:center; word-break:break-word; margin-bottom:25px; }
.info-section { margin-bottom:25px; }
.info-section h3 { font-size:14px; color:#999; text-transform:uppercase; margin:0 0 15px 0; font-weight:600; }
.info-item { background:#f9f9f9; padding:15px; border-radius:8px; margin-bottom:12px; }
.info-label { font-size:12px; color:#666; text-transform:uppercase; font-weight:600; margin-bottom:5px; }
.info-value { font-size:16px; color:#333; font-weight:500; }
.info-value.empty { color:#999; font-style:italic; }
.section-title { font-size:22px; font-weight:bold; color:#333; margin:0 0 20px 0; padding-bottom:15px; border-bottom:3px solid #667eea; }
.form-group { margin-bottom:20px; }
label { display:block; margin-bottom:8px; color:#555; font-weight:600; font-size:14px; }
input, textarea { width:100%; padding:12px 15px; border:2px solid #e0e0e0; border-radius:8px; font-size:16px; box-sizing:border-box; }
input:focus, textarea:focus { outline:none; border-color:#667eea; }
textarea { resize:vertical; min-height:100px; font-family:inherit; }
.btn { padding:12px 30px; background:linear-gradient(135deg,#667eea 0%,#764ba2 100%); color:#fff; border:none; border-radius:8px; font-size:16px; font-weight:600; cursor:pointer; }
.btn:hover { transform:translateY(-2px); box-shadow:0 5px 15px rgba(102,126,234,0.4); }
.alert { padding:15px 20px; border-radius:8px; margin-bottom:25px; font-weight:500; }
.alert-success { background:#d4edda; border:2px solid #c3e6cb; color:#155724; }
.alert-error { background:#f8d7da; border:2px solid #f5c6cb; color:#721c24; }
.alert-bonus { background:linear-gradient(135deg,#fff3cd 0%,#ffeaa7 100%); border:2px solid #ffc107; color:#856404; font-size:16px; }
.bonus-section { background:#fff; border-radius:15px; padding:30px; box-shadow:0 5px 20px rgba(0,0,0,.1); margin-top:30px; }
.level-card { background:linear-gradient(135deg, <?php echo $level_color; ?> 0%, <?php echo $level_color; ?>dd 100%); padding:25px; border-radius:12px; color:#fff; margin-bottom:25px; display:flex; align-items:center; gap:20px; }
.level-icon { font-size:60px; }
.level-info h3 { font-size:24px; margin:0 0 8px 0; font-weight:bold; }
.level-info p { margin:0 0 15px 0; opacity:.9; }
.points-display { margin-left:auto; text-align:right; }
.points { font-size:36px; font-weight:bold; line-height:1; }
.points-display .label { font-size:14px; opacity:.9; text-transform:uppercase; }
.points-display .sublabel { font-size:12px; opacity:.7; margin-top:5px; }
.progress-bar-container { background:rgba(255,255,255,.3); height:8px; border-radius:10px; overflow:hidden; }
.progress-bar { background:#fff; height:100%; border-radius:10px; }
.section-title-bonus { font-size:20px; font-weight:bold; color:#333; margin:25px 0 15px 0; }
.achievements-grid { display:grid; grid-template-columns:repeat(auto-fill, minmax(200px,1fr)); gap:15px; margin-top:20px; }
.achievement-card { background:#f8f9ff; padding:20px; border-radius:12px; text-align:center; border:2px solid #e5e7eb; }
.achievement-card.earned { background:linear-gradient(135deg,#f0f9ff 0%,#e0f2fe 100%); border-color:#6366f1; }
.achievement-card.locked { opacity:.5; filter:grayscale(100%); }
.achievement-icon { font-size:40px; margin-bottom:12px; }
.achievement-name { font-size:16px; font-weight:bold; color:#333; margin-bottom:8px; }
.achievement-desc { font-size:13px; color:#666; margin-bottom:10px; }
.achievement-points { font-size:14px; font-weight:bold; color:#6366f1; }
.stats-grid { display:grid; grid-template-columns:repeat(auto-fit,minmax(200px,1fr)); gap:15px; margin-top:20px; }
.stat-card { background:#f8f9ff; padding:20px; border-radius:12px; text-align:center; border:2px solid #e5e7eb; }
.stat-number { font-size:32px; font-weight:bold; color:#6366f1; margin-bottom:5px; }
.stat-label { color:#666; font-size:14px; }
@media (max-width:968px){ .profile-grid{ grid-template-columns:1fr; } .achievements-grid{ grid-template-columns:repeat(auto-fill,minmax(150px,1fr)); } .stats-grid{ grid-template-columns:repeat(2,1fr); } }
@media (max-width:576px){ .level-card{ flex-direction:column; text-align:center; } .points-display{ margin-left:0; margin-top:15px; } .stats-grid{ grid-template-columns:1fr; } }

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
            <div class="profile-username"><?php echo htmlspecialchars($user['lietotajvards']); ?></div>
            <div class="profile-email"><?php echo htmlspecialchars($user['epasts']); ?></div>

            <div class="info-section">
                <h3>Personīgā informācija</h3>
                <div class="info-item">
                    <div class="info-label">Pilnais vārds</div>
                    <
                    <div class="info-value <?php echo empty($user['full_name']) ? 'empty' : ''; ?>"><?php echo !empty($user['full_name']) ? htmlspecialchars($user['full_name']) : 'Nav norādīts'; ?></div>
                </div>
                <div class="info-item">
                    <div class="info-label">Telefons</div>
                    <div class="info-value <?php echo empty($user['phone']) ? 'empty' : ''; ?>"><?php echo !empty($user['phone']) ? htmlspecialchars($user['phone']) : 'Nav norādīts'; ?></div>
                </div>
                <div class="info-item">
                    <div class="info-label">Adrese</div>
                    <div class="info-value <?php echo empty($user['address']) ? 'empty' : ''; ?>"><?php echo !empty($user['address']) ? nl2br(htmlspecialchars($user['address'])) : 'Nav norādīta'; ?></div>
                </div>
            </div>

            <div class="info-section">
                <h3>Konta statistika</h3>
                <div class="info-item">
                    <div class="info-label">Reģistrācijas datums</div>
                    <div class="info-value"><?php echo !empty($user['created_at']) ? date('d.m.Y', strtotime($user['created_at'])) : '—'; ?></div>
                </div>
                <div class="info-item">
                    <div class="info-label">Pēdējā atjaunināšana</div>
                    <div class="info-value"><?php echo !empty($user['updated_at']) ? date('d.m.Y H:i', strtotime($user['updated_at'])) : '—'; ?></div>
                </div>
            </div>
        </aside>

        <main class="card">
            <?php if ($bonus_awarded): ?>
                <div class="alert alert-bonus">🎉 Jūs saņēmāt bonusu!</div>
            <?php endif; ?>
            
            <?php if (!empty($success_message)): ?>
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
                    <input type="text" id="full_name" name="full_name" value="<?php echo htmlspecialchars($user['full_name']); ?>" placeholder="Ievadiet savu pilno vārdu" maxlength="100" required>
                </div>
                <div class="form-group">
                    <label for="phone">Telefons *</label>
                    <input type="tel" id="phone" name="phone" value="<?php echo htmlspecialchars($user['phone']); ?>" placeholder="+371 12345678" maxlength="20" required>
                </div>
                <div class="form-group">
                    <label for="address">Adrese *</label>
                    <textarea id="address" name="address" placeholder="Ievadiet savu adresi" required><?php echo htmlspecialchars($user['address']); ?></textarea>
                </div>
                <button type="submit" class="btn">💾 Saglabāt izmaiņas</button>
            </form>
        </main>
    </div>

    <div class="bonus-section">
        <div class="level-card">
            <div class="level-icon"><?php echo $level_icon; ?></div>
            <div class="level-info">
                <h3><?php echo htmlspecialchars($level_name); ?></h3>
                <p>Tavs pašreizējais līmenis</p>
                <div class="progress-bar-container">
                    <div class="progress-bar" style="width: <?php echo min(100, ($points % 100)); ?>%"></div>
                </div>
            </div>
            <div class="points-display">
                <div class="points"><?php echo $points; ?></div>
                <div class="label">punkti</div>
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
                    <div class="achievement-points"><?php echo $is_earned ? '✅ Iegūts' : '+' . $ach['points'] . ' punkti'; ?></div>
                </div>
            <?php endforeach; ?>
        </div>

        <h3 class="section-title-bonus">📊 Mana statistika</h3>
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-number"><?php echo (int)$user['favorites_count']; ?></div>
                <div class="stat-label">Favorīti</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo (int)$user['applications_count']; ?></div>
                <div class="stat-label">Pieteikumi</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo (int)$user['events_attended']; ?></div>
                <div class="stat-label">Apmeklēti pasākumi</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo (int)$user['profile_complete']; ?>%</div>
                <div class="stat-label">Profils aizpildīts</div>
            </div>
        </div>
    </div>
</div>

</body>
</html>
