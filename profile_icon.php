<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/** Самовосстановление user_id по lietotajvards / epasts из lietotaji */
(function () {
    if (!empty($_SESSION['user_id'])) return;

    $lietotajvards = isset($_SESSION['lietotajvards']) ? trim((string)$_SESSION['lietotajvards']) : '';
    $epasts        = isset($_SESSION['epasts'])        ? trim((string)$_SESSION['epasts'])        : '';

    if ($lietotajvards === '' && $epasts === '') return;

    // Пытаемся подключить db_conn.php и получить $conn (mysqli)
    $conn = null;
    $paths = [ __DIR__ . '/../db_conn.php', __DIR__ . '/../../db_conn.php', __DIR__ . '/db_conn.php' ];
    foreach ($paths as $p) {
        if (file_exists($p)) {
            require_once $p;
            if (isset($conn) && $conn instanceof mysqli) break;
        }
    }
    if (!($conn instanceof mysqli)) return;

    if ($lietotajvards !== '') {
        if ($stmt = $conn->prepare("SELECT id, lietotajvards, epasts FROM lietotaji WHERE lietotajvards = ? LIMIT 1")) {
            $stmt->bind_param("s", $lietotajvards);
            $stmt->execute();
            $res = $stmt->get_result();
            if ($row = $res->fetch_assoc()) {
                $_SESSION['user_id']       = (int)$row['id'];
                $_SESSION['lietotajvards'] = $row['lietotajvards'];
                $_SESSION['epasts']        = $row['epasts'];
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
            if ($row = $res->fetch_assoc()) {
                $_SESSION['user_id']       = (int)$row['id'];
                $_SESSION['lietotajvards'] = $row['lietotajvards'];
                $_SESSION['epasts']        = $row['epasts'];
                $stmt->close();
                return;
            }
            $stmt->close();
        }
    }
})();

$isLoggedIn = !empty($_SESSION['user_id'])
           || !empty($_SESSION['lietotajvards'])
           || !empty($_SESSION['epasts']);

$profileUrl   = $isLoggedIn ? 'account.php' : 'register.php';
$profileTitle = $isLoggedIn ? 'Mans konts'  : 'Reģistrēties';
$userName     = (string)($_SESSION['lietotajvards'] ?? '');
?>
<style>
/* стили как раньше, сокращены для ясности */
.profile-icon-wrapper{position:relative;display:inline-block}
.profile-icon-link{display:block;text-decoration:none}
.profile-icon{width:42px;height:42px;border-radius:50%;background:linear-gradient(135deg,#667eea 0%,#764ba2 100%);display:flex;align-items:center;justify-content:center;cursor:pointer;transition:.3s;border:2px solid transparent;box-shadow:0 2px 8px rgba(0,0,0,.1)}
.profile-icon.logged-in{background:linear-gradient(135deg,#11998e 0%,#38ef7d 100%)}
.profile-tooltip{position:absolute;bottom:-35px;right:0;background:#333;color:#fff;padding:6px 12px;border-radius:6px;font-size:13px;white-space:nowrap;opacity:0;visibility:hidden;transition:.3s;pointer-events:none}
.profile-icon-wrapper:hover .profile-tooltip{opacity:1;visibility:visible;bottom:-40px}
.status-indicator{position:absolute;bottom:2px;right:2px;width:12px;height:12px;background:#4CAF50;border:2px solid #fff;border-radius:50%}
</style>

<div class="profile-icon-wrapper" title="<?php echo htmlspecialchars($profileTitle); ?>">
  <a href="<?php echo htmlspecialchars($profileUrl); ?>" class="profile-icon-link">
    <div class="profile-icon <?php echo $isLoggedIn ? 'logged-in' : 'guest'; ?>">
      <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="24" height="24" fill="#fff">
        <?php if ($isLoggedIn): ?>
          <path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/>
        <?php else: ?>
          <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm0 3c1.66 0 3 1.34 3 3s-1.34 3-3 3-3-1.34-3-3 1.34-3 3-3zm0 14.2c-2.5 0-4.71-1.28-6-3.22.03-1.99 4-3.08 6-3.08 1.99 0 5.97 1.09 6 3.08-1.29 1.94-3.5 3.22-6 3.22z"/>
        <?php endif; ?>
      </svg>
      <?php if ($isLoggedIn): ?><span class="status-indicator"></span><?php endif; ?>
    </div>
  </a>
  <div class="profile-tooltip">
    <?php echo $isLoggedIn ? htmlspecialchars($userName ?: 'Mans konts') : 'Reģistrēties'; ?>
  </div>
</div>
