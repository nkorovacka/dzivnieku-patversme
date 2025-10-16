<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
require_once 'db_conn.php'; // ожидается $conn = new mysqli(...)

/* -----------------------------------------------------------
   0) Самовосстановление user_id из сессии (lietotajvards/epasts)
------------------------------------------------------------ */
function ensureUserIdFromSession(mysqli $conn): void {
    if (!empty($_SESSION['user_id'])) return;

    $lietotajvards = isset($_SESSION['lietotajvards']) ? trim((string)$_SESSION['lietotajvards']) : '';
    $epasts        = isset($_SESSION['epasts'])        ? trim((string)$_SESSION['epasts'])        : '';

    if ($lietotajvards !== '') {
        if ($stmt = $conn->prepare("SELECT id, lietotajvards, epasts FROM lietotaji WHERE lietotajvards = ? LIMIT 1")) {
            $stmt->bind_param("s", $lietotajvards);
            $stmt->execute();
            $res = $stmt->get_result();
            if ($u = $res->fetch_assoc()) {
                $_SESSION['user_id']       = (int)$u['id'];
                $_SESSION['lietotajvards'] = $u['lietotajvards'];
                $_SESSION['epasts']        = $u['epasts'];
                $stmt->close();
                return;
            }
            $stmt->close();
        }
    }
    if ($epasts !== '') {
        if ($stmt = $conn->prepare("SELECT id, lietotajvards, epasts FROM lietotaji WHERE epasts = ? LIMIT 1")) {
            $stmt->bind_param("s", $epasts);
            $stmt->execute();
            $res = $stmt->get_result();
            if ($u = $res->fetch_assoc()) {
                $_SESSION['user_id']       = (int)$u['id'];
                $_SESSION['lietotajvards'] = $u['lietotajvards'];
                $_SESSION['epasts']        = $u['epasts'];
            }
            $stmt->close();
        }
    }
}
ensureUserIdFromSession($conn);

/* -----------------------------------------------------------
   1) Проверка авторизации
------------------------------------------------------------ */
if (empty($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}
$userId = (int)$_SESSION['user_id'];

/* -----------------------------------------------------------
   2) Утилиты работы с БД/ачивками/баллами
------------------------------------------------------------ */
function txn(mysqli $conn, callable $fn) {
    $conn->begin_transaction();
    try {
        $res = $fn();
        $conn->commit();
        return $res;
    } catch (Throwable $e) {
        $conn->rollback();
        throw $e;
    }
}

function get_user(mysqli $conn, int $uid): ?array {
    $sql = "SELECT *
            FROM lietotaji
            WHERE id = ?
            LIMIT 1";
    $q = $conn->prepare($sql);
    $q->bind_param("i", $uid);
    $q->execute();
    $res = $q->get_result()->fetch_assoc();
    $q->close();
    return $res ?: null;
}

/** Начислить очки и записать историю */
function award_points(mysqli $conn, int $uid, int $points, string $reason): bool {
    return txn($conn, function() use ($conn, $uid, $points, $reason) {
        $u = $conn->prepare("UPDATE lietotaji SET points = points + ?, total_earned = total_earned + ? WHERE id = ?");
        $u->bind_param("iii", $points, $points, $uid);
        if (!$u->execute()) { $u->close(); throw new Exception('award_points UPDATE failed'); }
        $u->close();

        $h = $conn->prepare("INSERT INTO points_history (user_id, points, reason, created_at) VALUES (?, ?, ?, NOW())");
        $h->bind_param("iis", $uid, $points, $reason);
        if (!$h->execute()) { $h->close(); throw new Exception('points_history INSERT failed'); }
        $h->close();

        // Пересчёт уровня по новым очкам
        $r = $conn->prepare("SELECT points FROM lietotaji WHERE id = ?");
        $r->bind_param("i", $uid); $r->execute();
        $row = $r->get_result()->fetch_assoc();
        $r->close();
        $p = (int)($row['points'] ?? 0);

        if ($p >= 1000)      $level = 'SirdsPaws Leģenda';
        elseif ($p >= 600)   $level = 'Dzīvnieku Varonis';
        elseif ($p >= 300)   $level = 'Aktīvs Atbalstītājs';
        elseif ($p >= 100)   $level = 'Patversmes Draugs';
        else                 $level = 'Iesācējs';

        $lv = $conn->prepare("UPDATE lietotaji SET level_name = ? WHERE id = ?");
        $lv->bind_param("si", $level, $uid);
        if (!$lv->execute()) { $lv->close(); throw new Exception('level update failed'); }
        $lv->close();

        return true;
    });
}

/** Есть ли ачивка (валидный JSON через CAST) */
function has_achievement(mysqli $conn, int $uid, int $achId): bool {
    $candidateJson = json_encode($achId); // "1"
    $sql = "SELECT JSON_CONTAINS(COALESCE(achievements_json, JSON_ARRAY()), CAST(? AS JSON), '$') AS has_it
            FROM lietotaji
            WHERE id = ?";
    $q = $conn->prepare($sql);
    $q->bind_param("si", $candidateJson, $uid);
    $q->execute();
    $row = $q->get_result()->fetch_assoc();
    $q->close();
    return !empty($row) && (int)$row['has_it'] === 1;
}

/** Добавить ачивку, если нет (валидный JSON, idempotent) */
function add_achievement(mysqli $conn, int $uid, int $achId): bool {
    if (has_achievement($conn, $uid, $achId)) return true;
    $candidateJson = json_encode($achId);
    $sql = "UPDATE lietotaji
            SET achievements_json = JSON_ARRAY_APPEND(COALESCE(achievements_json, JSON_ARRAY()), '$', CAST(? AS JSON))
            WHERE id = ?";
    $q = $conn->prepare($sql);
    $q->bind_param("si", $candidateJson, $uid);
    $ok = $q->execute();
    $q->close();
    return $ok;
}

/** Одноразовый бонус за регистрацию (+10, ачивка #1) */
function ensure_registration_bonus_once(mysqli $conn, int $uid): bool {
    if (has_achievement($conn, $uid, 1)) return false; // уже начисляли
    return txn($conn, function() use ($conn, $uid) {
        if (!add_achievement($conn, $uid, 1)) throw new Exception('add_achievement(1) failed');
        if (!award_points($conn, $uid, 10, 'registration_bonus')) throw new Exception('award +10 failed');
        return true;
    });
}

/** Синхронизация favorites_count из таблицы favorites, выдача ачивки #3 (+30) при достижении 5 */
function sync_favorites_and_bonus(mysqli $conn, int $uid): array {
    $awarded = false;

    // текущий счётчик
    $cur = $conn->prepare("SELECT favorites_count FROM lietotaji WHERE id = ?");
    $cur->bind_param("i", $uid);
    $cur->execute();
    $row = $cur->get_result()->fetch_assoc();
    $cur->close();
    $storedCount = (int)($row['favorites_count'] ?? 0);

    // фактический из favorites
    $cnt = $conn->prepare("SELECT COUNT(*) AS c FROM favorites WHERE user_id = ?");
    $cnt->bind_param("i", $uid);
    $cnt->execute();
    $cRow = $cnt->get_result()->fetch_assoc();
    $cnt->close();
    $realCount = (int)($cRow['c'] ?? 0);

    // обновляем поле
    if ($realCount !== $storedCount) {
        $u = $conn->prepare("UPDATE lietotaji SET favorites_count = ?, updated_at = NOW() WHERE id = ?");
        $u->bind_param("ii", $realCount, $uid);
        $u->execute();
        $u->close();
    }

    // если стало >=5 и ачивки 3 нет — начислить
    if ($realCount >= 5 && !has_achievement($conn, $uid, 3)) {
        $ok = txn($conn, function() use ($conn, $uid) {
            if (!add_achievement($conn, $uid, 3)) throw new Exception('add_achievement(3) failed');
            if (!award_points($conn, $uid, 30, 'favorites_5')) throw new Exception('award +30 failed');
            return true;
        });
        if ($ok) $awarded = true;
    }

    return ['favorites_count' => $realCount, 'bonus_awarded' => $awarded];
}

/* -----------------------------------------------------------
   3) Получаем пользователя
------------------------------------------------------------ */
$user = get_user($conn, $userId);
if (!$user) {
    session_destroy();
    header("Location: login.php");
    exit();
}

// Гарантируем ключи
$user += [
    'full_name'          => '',
    'phone'              => '',
    'address'            => '',
    'created_at'         => null,
    'updated_at'         => null,
    'points'             => 0,
    'total_earned'       => 0,
    'favorites_count'    => 0,
    'applications_count' => 0,
    'events_attended'    => 0,
    'profile_complete'   => 0,
    'level_name'         => 'Iesācējs',
    'achievements_json'  => null,
];

/* -----------------------------------------------------------
   4) Одноразовый бонус за регистрацию (при первом входе)
------------------------------------------------------------ */
$reg_bonus_awarded_now = false;
if (empty($_SESSION['__reg_bonus_checked'])) {
    $reg_bonus_awarded_now = ensure_registration_bonus_once($conn, $userId);
    $_SESSION['__reg_bonus_checked'] = 1;
    if ($reg_bonus_awarded_now) {
        $user = get_user($conn, $userId); // перечитать очки/уровень
    }
}

/* -----------------------------------------------------------
   5) Синхронизируем фавориты и потенциально выдаём бонус
------------------------------------------------------------ */
$fav_res = sync_favorites_and_bonus($conn, $userId);
if ($fav_res['bonus_awarded']) {
    $user = get_user($conn, $userId); // обновить очки/уровень и favorites_count
}

/* -----------------------------------------------------------
   6) Обработка формы обновления профиля (+20 и ачивка #2 один раз)
------------------------------------------------------------ */
$success_message = '';
$error_message   = '';
$bonus_awarded   = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'update_profile') {
    $full_name = trim($_POST['full_name'] ?? '');
    $phone     = trim($_POST['phone'] ?? '');
    $address   = trim($_POST['address'] ?? '');

    $old_complete = (int)$user['profile_complete'];
    $new_complete = (!empty($full_name) && !empty($phone) && !empty($address)) ? 100 : 0;

    $upd = $conn->prepare("UPDATE lietotaji 
                           SET full_name = ?, phone = ?, address = ?, profile_complete = ?, updated_at = NOW()
                           WHERE id = ?");
    $upd->bind_param("sssii", $full_name, $phone, $address, $new_complete, $userId);

    if ($upd->execute()) {
        $upd->close();

        // если впервые достигли 100% — ачивка #2 +20 очков
        if ($old_complete < 100 && $new_complete === 100 && !has_achievement($conn, $userId, 2)) {
            $ok = txn($conn, function() use ($conn, $userId) {
                if (!add_achievement($conn, $userId, 2)) throw new Exception('add_achievement(2) failed');
                if (!award_points($conn, $userId, 20, 'profile_complete')) throw new Exception('award +20 failed');
                return true;
            });
            if ($ok) {
                $bonus_awarded   = true;
                $success_message = 'Profils veiksmīgi atjaunināts! 🎉 +20 punkti par pilnu profilu!';
            } else {
                $success_message = 'Profils veiksmīgi atjaunināts!';
            }
        } else {
            $success_message = 'Profils veiksmīgi atjaunināts!';
        }
        // перечитать пользователя
        $user = get_user($conn, $userId);
    } else {
        $error_message = 'Kļūda atjauninot profilu!';
        $upd->close();
    }
}

/* -----------------------------------------------------------
   7) Визуал: иконка уровня и цвет
------------------------------------------------------------ */
$points = (int)$user['points'];
$level_icon  = '🥉';
$level_color = '#BDC3C7';
if     ($points >= 1000) { $level_icon='👑'; $level_color='#FFD700'; }
elseif ($points >= 600)  { $level_icon='💎'; $level_color='#E74C3C'; }
elseif ($points >= 300)  { $level_icon='🥇'; $level_color='#3498DB'; }
elseif ($points >= 100)  { $level_icon='🥈'; $level_color='#95A5A6'; }

// achievements_json → массив id
$earned_ids = [];
if (!empty($user['achievements_json'])) {
    $decoded = json_decode($user['achievements_json'], true);
    if (is_array($decoded)) $earned_ids = $decoded;
}

// Список достижений
$all_achievements = [
    ['id' => 1, 'name' => 'Pirmais Solis',     'desc' => 'Reģistrējies sistēmā',        'icon' => '🎯', 'points' => 10],
    ['id' => 2, 'name' => 'Pilnīgs Profils',   'desc' => 'Aizpildīts viss profils',     'icon' => '📱', 'points' => 20],
    ['id' => 3, 'name' => 'Dzīvnieku Draugs',  'desc' => 'Pievienoti 5 favorīti',       'icon' => '❤️', 'points' => 30],
    ['id' => 4, 'name' => 'Atbildīgs Adopcētājs', 'desc' => 'Iesniegts pirmais pieteikums', 'icon' => '📝', 'points' => 50],
    ['id' => 5, 'name' => 'Aktīvais Dalībnieks',  'desc' => 'Apmeklēti 3 pasākumi',     'icon' => '🎪', 'points' => 40],
];

// Первая буква username
$initial = mb_strtoupper(mb_substr($user['lietotajvards'], 0, 1));

function getInitial($username) { return strtoupper(mb_substr((string)$username, 0, 1)); }
?>
<!DOCTYPE html>
<html lang="lv">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Mans profils - <?php echo htmlspecialchars($user['lietotajvards']); ?></title>
<link rel="stylesheet" href="index.css">
<style>
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
            <?php if ($reg_bonus_awarded_now): ?>
                <div class="alert alert-bonus">🎯 Pirmais solis: +10 punkti par reģistrāciju!</div>
            <?php endif; ?>
            <?php if ($fav_res['bonus_awarded']): ?>
                <div class="alert alert-bonus">❤️ Apsveicam! +30 punkti par 5 favorītiem!</div>
            <?php endif; ?>
            <?php if ($bonus_awarded): ?>
                <div class="alert alert-bonus">📱 Pilnīgs profils: +20 punkti pievienoti!</div>
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
                    <input type="text" id="full_name" name="full_name" value="<?php echo htmlspecialchars($user['full_name']); ?>" placeholder="Ievadiet savu pilno vārdu" maxlength="100">
                </div>
                <div class="form-group">
                    <label for="phone">Telefons *</label>
                    <input type="tel" id="phone" name="phone" value="<?php echo htmlspecialchars($user['phone']); ?>" placeholder="+371 12345678" maxlength="20">
                </div>
                <div class="form-group">
                    <label for="address">Adrese *</label>
                    <textarea id="address" name="address" placeholder="Ievadiet savu adresi"><?php echo htmlspecialchars($user['address']); ?></textarea>
                </div>
                <p style="font-size:13px;color:#666;margin-bottom:20px;">
                    * Aizpildiet visus laukus, lai saņemtu +20 punktus un sasniegumu "Pilnīgs Profils"
                </p>
                <button type="submit" class="btn">💾 Saglabāt izmaiņas</button>
            </form>
        </main>
    </div>

    <div class="bonus-section">
        <div class="level-card">
            <div class="level-icon"><?php echo $level_icon; ?></div>
            <div class="level-info">
                <h3><?php echo htmlspecialchars($user['level_name']); ?></h3>
                <p>Tavs pašreizējais līmenis</p>
                <div class="progress-bar-container">
                    <?php 
                    $progress = 0;
                    if ($points < 100)        $progress = $points;
                    elseif ($points < 300)    $progress = (($points - 100) / 200) * 100;
                    elseif ($points < 600)    $progress = (($points - 300) / 300) * 100;
                    elseif ($points < 1000)   $progress = (($points - 600) / 400) * 100;
                    else                      $progress = 100;
                    ?>
                    <div class="progress-bar" style="width: <?php echo min(100, max(0, $progress)); ?>%"></div>
                </div>
            </div>
            <div class="points-display">
                <div class="points"><?php echo (int)$user['points']; ?></div>
                <div class="label">punkti</div>
                <div class="sublabel">Kopā: <?php echo (int)$user['total_earned']; ?></div>
            </div>
        </div>

        <h3 class="section-title-bonus">🏆 Mani sasniegumi</h3>
        <div class="achievements-grid">
            <?php foreach ($all_achievements as $ach): ?>
                <?php $is_earned = in_array($ach['id'], $earned_ids, true); ?>
                <div class="achievement-card <?php echo $is_earned ? 'earned' : 'locked'; ?>">
                    <div class="achievement-icon"><?php echo $ach['icon']; ?></div>
                    <div class="achievement-name"><?php echo $ach['name']; ?></div>
                    <div class="achievement-desc"><?php echo $ach['desc']; ?></div>
                    <div class="achievement-points">
                        <?php echo $is_earned ? '✅ Iegūts' : '+' . (int)$ach['points'] . ' punkti'; ?>
                    </div>
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
