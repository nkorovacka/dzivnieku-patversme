#!/usr/bin/env php
<?php
/**
 * Bonus Management CLI Tool
 * Управление бонусами и достижениями через командную строку
 * 
 * Использование:
 * php manage_bonus.php <команда> [параметры]
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/db_conn.php';

class BonusManager {
    private $conn;
    
    public function __construct($conn) {
        $this->conn = $conn;
    }
    
    // Добавить очки пользователю
    public function addPoints($userId, $points, $reason = '') {
        // Проверяем существование пользователя
        $stmt = $this->conn->prepare("SELECT username FROM users WHERE id = ?");
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            echo "❌ Ошибка: Пользователь с ID $userId не найден\n";
            return false;
        }
        
        $user = $result->fetch_assoc();
        
        // Создаем запись user_points если её нет
        $stmt = $this->conn->prepare("INSERT INTO user_points (user_id, points, total_earned) VALUES (?, 0, 0) ON DUPLICATE KEY UPDATE user_id=user_id");
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        
        // Обновляем очки
        $stmt = $this->conn->prepare("UPDATE user_points SET points = points + ?, total_earned = total_earned + ? WHERE user_id = ?");
        $stmt->bind_param("iii", $points, $points, $userId);
        
        if ($stmt->execute()) {
            // Добавляем в историю
            $stmt = $this->conn->prepare("INSERT INTO points_history (user_id, points, reason) VALUES (?, ?, ?)");
            $stmt->bind_param("iis", $userId, $points, $reason);
            $stmt->execute();
            
            // Получаем текущее количество очков
            $stmt = $this->conn->prepare("SELECT points FROM user_points WHERE user_id = ?");
            $stmt->bind_param("i", $userId);
            $stmt->execute();
            $result = $stmt->get_result();
            $currentPoints = $result->fetch_assoc()['points'];
            
            echo "✅ Успешно добавлено $points очков пользователю {$user['username']} (ID: $userId)\n";
            echo "📊 Текущее количество очков: $currentPoints\n";
            if ($reason) echo "💬 Причина: $reason\n";
            
            // Проверяем достижения
            $this->checkAchievements($userId);
            
            return true;
        }
        
        echo "❌ Ошибка при добавлении очков\n";
        return false;
    }
    
    // Снять очки
    public function removePoints($userId, $points, $reason = '') {
        $negativePoints = -abs($points);
        return $this->addPoints($userId, $negativePoints, $reason ?: 'Снятие очков');
    }
    
    // Присвоить достижение
    public function grantAchievement($userId, $achievementKey) {
        // Проверяем пользователя
        $stmt = $this->conn->prepare("SELECT username FROM users WHERE id = ?");
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            echo "❌ Ошибка: Пользователь с ID $userId не найден\n";
            return false;
        }
        
        $user = $result->fetch_assoc();
        
        // Проверяем достижение
        $stmt = $this->conn->prepare("SELECT id, name, points, icon FROM achievements WHERE achievement_key = ?");
        $stmt->bind_param("s", $achievementKey);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            echo "❌ Ошибка: Достижение '$achievementKey' не найдено\n";
            return false;
        }
        
        $achievement = $result->fetch_assoc();
        
        // Проверяем, не получено ли уже
        $stmt = $this->conn->prepare("SELECT id FROM user_achievements WHERE user_id = ? AND achievement_id = ?");
        $stmt->bind_param("ii", $userId, $achievement['id']);
        $stmt->execute();
        
        if ($stmt->get_result()->num_rows > 0) {
            echo "⚠️ Пользователь {$user['username']} уже имеет это достижение\n";
            return false;
        }
        
        // Присваиваем достижение
        $stmt = $this->conn->prepare("INSERT INTO user_achievements (user_id, achievement_id) VALUES (?, ?)");
        $stmt->bind_param("ii", $userId, $achievement['id']);
        
        if ($stmt->execute()) {
            echo "🏆 Достижение '{$achievement['name']}' {$achievement['icon']} присвоено пользователю {$user['username']}\n";
            
            // Начисляем очки за достижение
            if ($achievement['points'] > 0) {
                $this->addPoints($userId, $achievement['points'], "Достижение: {$achievement['name']}");
            }
            
            return true;
        }
        
        echo "❌ Ошибка при присвоении достижения\n";
        return false;
    }
    
    // Обновить статистику
    public function updateStats($userId, $field, $value) {
        $allowedFields = ['favorites_count', 'applications_count', 'events_attended', 'profile_complete'];
        
        if (!in_array($field, $allowedFields)) {
            echo "❌ Ошибка: Недопустимое поле. Разрешены: " . implode(', ', $allowedFields) . "\n";
            return false;
        }
        
        // Создаем запись если её нет
        $stmt = $this->conn->prepare("INSERT INTO user_stats (user_id) VALUES (?) ON DUPLICATE KEY UPDATE user_id=user_id");
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        
        // Обновляем
        $sql = "UPDATE user_stats SET $field = ? WHERE user_id = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("ii", $value, $userId);
        
        if ($stmt->execute()) {
            echo "✅ Статистика обновлена: $field = $value для пользователя ID $userId\n";
            
            // Проверяем достижения после обновления статистики
            $this->checkAchievements($userId);
            
            return true;
        }
        
        echo "❌ Ошибка при обновлении статистики\n";
        return false;
    }
    
    // Увеличить статистику
    public function incrementStats($userId, $field, $increment = 1) {
        $allowedFields = ['favorites_count', 'applications_count', 'events_attended'];
        
        if (!in_array($field, $allowedFields)) {
            echo "❌ Ошибка: Недопустимое поле для инкремента\n";
            return false;
        }
        
        // Создаем запись если её нет
        $stmt = $this->conn->prepare("INSERT INTO user_stats (user_id) VALUES (?) ON DUPLICATE KEY UPDATE user_id=user_id");
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        
        // Увеличиваем
        $sql = "UPDATE user_stats SET $field = $field + ? WHERE user_id = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("ii", $increment, $userId);
        
        if ($stmt->execute()) {
            // Получаем новое значение
            $stmt = $this->conn->prepare("SELECT $field FROM user_stats WHERE user_id = ?");
            $stmt->bind_param("i", $userId);
            $stmt->execute();
            $newValue = $stmt->get_result()->fetch_assoc()[$field];
            
            echo "✅ Статистика увеличена: $field = $newValue (+" . $increment . ") для пользователя ID $userId\n";
            
            // Проверяем достижения
            $this->checkAchievements($userId);
            
            return true;
        }
        
        return false;
    }
    
    // Проверить и начислить достижения автоматически
    public function checkAchievements($userId) {
        // Получаем статистику пользователя
        $stmt = $this->conn->prepare("SELECT * FROM user_stats WHERE user_id = ?");
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $stats = $stmt->get_result()->fetch_assoc();
        
        if (!$stats) return;
        
        // Получаем все достижения
        $result = $this->conn->query("SELECT * FROM achievements WHERE requirement_type != 'custom'");
        
        while ($achievement = $result->fetch_assoc()) {
            // Проверяем, не получено ли уже
            $stmt = $this->conn->prepare("SELECT id FROM user_achievements WHERE user_id = ? AND achievement_id = ?");
            $stmt->bind_param("ii", $userId, $achievement['id']);
            $stmt->execute();
            
            if ($stmt->get_result()->num_rows > 0) continue;
            
            // Проверяем условия
            $earned = false;
            switch ($achievement['requirement_type']) {
                case 'favorites':
                    $earned = $stats['favorites_count'] >= $achievement['requirement_value'];
                    break;
                case 'applications':
                    $earned = $stats['applications_count'] >= $achievement['requirement_value'];
                    break;
                case 'events':
                    $earned = $stats['events_attended'] >= $achievement['requirement_value'];
                    break;
                case 'profile':
                    $earned = $stats['profile_complete'] >= $achievement['requirement_value'];
                    break;
            }
            
            if ($earned) {
                $this->grantAchievement($userId, $achievement['achievement_key']);
            }
        }
    }
    
    // Показать информацию о пользователе
    public function showUserInfo($userId) {
        $stmt = $this->conn->prepare("
            SELECT u.*, up.points, up.total_earned, us.*
            FROM users u
            LEFT JOIN user_points up ON u.id = up.user_id
            LEFT JOIN user_stats us ON u.id = us.user_id
            WHERE u.id = ?
        ");
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            echo "❌ Пользователь с ID $userId не найден\n";
            return;
        }
        
        $user = $result->fetch_assoc();
        
        echo "\n" . str_repeat("=", 60) . "\n";
        echo "👤 ИНФОРМАЦИЯ О ПОЛЬЗОВАТЕЛЕ\n";
        echo str_repeat("=", 60) . "\n\n";
        
        echo "ID: {$user['id']}\n";
        echo "Имя пользователя: {$user['username']}\n";
        echo "Email: {$user['email']}\n";
        echo "Полное имя: " . ($user['full_name'] ?: 'Не указано') . "\n";
        echo "\n--- БОНУСЫ И ДОСТИЖЕНИЯ ---\n";
        echo "💰 Текущие очки: " . ($user['points'] ?: 0) . "\n";
        echo "📈 Всего заработано: " . ($user['total_earned'] ?: 0) . "\n";
        
        // Уровень
        $points = $user['points'] ?: 0;
        $level = $this->getUserLevel($points);
        echo "🏆 Уровень: {$level['icon']} {$level['name']}\n";
        
        echo "\n--- СТАТИСТИКА ---\n";
        echo "❤️ Избранное: " . ($user['favorites_count'] ?: 0) . "\n";
        echo "📝 Заявки: " . ($user['applications_count'] ?: 0) . "\n";
        echo "🎪 Посещённые события: " . ($user['events_attended'] ?: 0) . "\n";
        echo "📊 Профиль заполнен: " . ($user['profile_complete'] ?: 0) . "%\n";
        
        // Достижения
        $stmt = $this->conn->prepare("
            SELECT a.name, a.icon, a.points, ua.earned_at
            FROM user_achievements ua
            JOIN achievements a ON ua.achievement_id = a.id
            WHERE ua.user_id = ?
            ORDER BY ua.earned_at DESC
        ");
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $achievements = $stmt->get_result();
        
        echo "\n--- ПОЛУЧЕННЫЕ ДОСТИЖЕНИЯ (" . $achievements->num_rows . ") ---\n";
        while ($ach = $achievements->fetch_assoc()) {
            echo "{$ach['icon']} {$ach['name']} (+{$ach['points']} очков) - " . 
                 date('d.m.Y H:i', strtotime($ach['earned_at'])) . "\n";
        }
        
        echo "\n" . str_repeat("=", 60) . "\n\n";
    }
    
    // Показать всех пользователей с очками
    public function listUsers() {
        $result = $this->conn->query("
            SELECT u.id, u.username, u.email, COALESCE(up.points, 0) as points
            FROM users u
            LEFT JOIN user_points up ON u.id = up.user_id
            ORDER BY points DESC
        ");
        
        echo "\n" . str_repeat("=", 70) . "\n";
        echo "📋 СПИСОК ПОЛЬЗОВАТЕЛЕЙ\n";
        echo str_repeat("=", 70) . "\n\n";
        printf("%-5s %-20s %-30s %s\n", "ID", "Имя пользователя", "Email", "Очки");
        echo str_repeat("-", 70) . "\n";
        
        while ($user = $result->fetch_assoc()) {
            printf("%-5d %-20s %-30s %d\n", 
                $user['id'], 
                substr($user['username'], 0, 20),
                substr($user['email'], 0, 30),
                $user['points']
            );
        }
        
        echo "\n";
    }
    
    // Показать все достижения
    public function listAchievements() {
        $result = $this->conn->query("SELECT * FROM achievements ORDER BY points ASC");
        
        echo "\n" . str_repeat("=", 80) . "\n";
        echo "🏆 СПИСОК ДОСТИЖЕНИЙ\n";
        echo str_repeat("=", 80) . "\n\n";
        
        while ($ach = $result->fetch_assoc()) {
            echo "{$ach['icon']} {$ach['name']} (Ключ: {$ach['achievement_key']})\n";
            echo "   Описание: {$ach['description']}\n";
            echo "   Очки: +{$ach['points']}\n";
            if ($ach['requirement_type'] != 'custom') {
                echo "   Требование: {$ach['requirement_type']} >= {$ach['requirement_value']}\n";
            }
            echo "\n";
        }
    }
    
    // История очков
    public function showPointsHistory($userId, $limit = 10) {
        $stmt = $this->conn->prepare("
            SELECT points, reason, created_at
            FROM points_history
            WHERE user_id = ?
            ORDER BY created_at DESC
            LIMIT ?
        ");
        $stmt->bind_param("ii", $userId, $limit);
        $stmt->execute();
        $result = $stmt->get_result();
        
        echo "\n📜 ИСТОРИЯ НАЧИСЛЕНИЯ ОЧКОВ (последние $limit записей)\n";
        echo str_repeat("-", 70) . "\n";
        
        if ($result->num_rows === 0) {
            echo "История пуста\n\n";
            return;
        }
        
        while ($row = $result->fetch_assoc()) {
            $sign = $row['points'] >= 0 ? '+' : '';
            echo date('d.m.Y H:i', strtotime($row['created_at'])) . " | ";
            echo $sign . $row['points'] . " очков | ";
            echo ($row['reason'] ?: 'Без причины') . "\n";
        }
        echo "\n";
    }
    
    // Инициализация для пользователя
    public function initUser($userId) {
        // Создаем записи если их нет
        $stmt = $this->conn->prepare("INSERT INTO user_points (user_id) VALUES (?) ON DUPLICATE KEY UPDATE user_id=user_id");
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        
        $stmt = $this->conn->prepare("INSERT INTO user_stats (user_id) VALUES (?) ON DUPLICATE KEY UPDATE user_id=user_id");
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        
        // Даём стартовое достижение
        $this->grantAchievement($userId, 'first_step');
        
        echo "✅ Пользователь ID $userId инициализирован в бонусной системе\n";
    }
    
    private function getUserLevel($points) {
        if ($points >= 1000) return ['name' => 'SirdsPaws Leģenda', 'icon' => '👑', 'color' => '#FFD700'];
        if ($points >= 600) return ['name' => 'Dzīvnieku Varonis', 'icon' => '💎', 'color' => '#E74C3C'];
        if ($points >= 300) return ['name' => 'Aktīvs Atbalstītājs', 'icon' => '🥇', 'color' => '#3498DB'];
        if ($points >= 100) return ['name' => 'Patversmes Draugs', 'icon' => '🥈', 'color' => '#95A5A6'];
        return ['name' => 'Iesācējs', 'icon' => '🥉', 'color' => '#BDC3C7'];
    }
}

// Обработка команд
function showHelp() {
    echo "\n";
    echo "🎮 УПРАВЛЕНИЕ БОНУСНОЙ СИСТЕМОЙ\n";
    echo str_repeat("=", 70) . "\n\n";
    echo "Использование: php manage_bonus.php <команда> [параметры]\n\n";
    echo "КОМАНДЫ:\n\n";
    echo "  add-points <user_id> <points> [причина]\n";
    echo "    Добавить очки пользователю\n";
    echo "    Пример: php manage_bonus.php add-points 1 50 \"За активность\"\n\n";
    
    echo "  remove-points <user_id> <points> [причина]\n";
    echo "    Снять очки у пользователя\n";
    echo "    Пример: php manage_bonus.php remove-points 1 20 \"Нарушение правил\"\n\n";
    
    echo "  grant-achievement <user_id> <achievement_key>\n";
    echo "    Присвоить достижение пользователю\n";
    echo "    Пример: php manage_bonus.php grant-achievement 1 first_step\n\n";
    
    echo "  update-stats <user_id> <поле> <значение>\n";
    echo "    Установить значение статистики\n";
    echo "    Поля: favorites_count, applications_count, events_attended, profile_complete\n";
    echo "    Пример: php manage_bonus.php update-stats 1 favorites_count 5\n\n";
    
    echo "  increment-stats <user_id> <поле> [прирост]\n";
    echo "    Увеличить статистику на указанное значение (по умолчанию +1)\n";
    echo "    Пример: php manage_bonus.php increment-stats 1 favorites_count 1\n\n";
    
    echo "  user-info <user_id>\n";
    echo "    Показать полную информацию о пользователе\n";
    echo "    Пример: php manage_bonus.php user-info 1\n\n";
    
    echo "  list-users\n";
    echo "    Показать список всех пользователей с очками\n";
    echo "    Пример: php manage_bonus.php list-users\n\n";
    
    echo "  list-achievements\n";
    echo "    Показать список всех достижений\n";
    echo "    Пример: php manage_bonus.php list-achievements\n\n";
    
    echo "  history <user_id> [лимит]\n";
    echo "    Показать историю начисления очков\n";
    echo "    Пример: php manage_bonus.php history 1 20\n\n";
    
    echo "  init-user <user_id>\n";
    echo "    Инициализировать пользователя в бонусной системе\n";
    echo "    Пример: php manage_bonus.php init-user 1\n\n";
    
    echo "  check-achievements <user_id>\n";
    echo "    Проверить и начислить достижения пользователю\n";
    echo "    Пример: php manage_bonus.php check-achievements 1\n\n";
    
    echo "  help\n";
    echo "    Показать эту справку\n\n";
}

// Главная логика
if ($argc < 2) {
    showHelp();
    exit(0);
}

$manager = new BonusManager($conn);
$command = $argv[1];

switch ($command) {
    case 'add-points':
        if ($argc < 4) {
            echo "❌ Использование: add-points <user_id> <points> [причина]\n";
            exit(1);
        }
        $userId = intval($argv[2]);
        $points = intval($argv[3]);
        $reason = $argv[4] ?? '';
        $manager->addPoints($userId, $points, $reason);
        break;
        
    case 'remove-points':
        if ($argc < 4) {
            echo "❌ Использование: remove-points <user_id> <points> [причина]\n";
            exit(1);
        }
        $userId = intval($argv[2]);
        $points = intval($argv[3]);
        $reason = $argv[4] ?? '';
        $manager->removePoints($userId, $points, $reason);
        break;
        
    case 'grant-achievement':
        if ($argc < 4) {
            echo "❌ Использование: grant-achievement <user_id> <achievement_key>\n";
            exit(1);
        }
        $userId = intval($argv[2]);
        $achievementKey = $argv[3];
        $manager->grantAchievement($userId, $achievementKey);
        break;
        
    case 'update-stats':
        if ($argc < 5) {
            echo "❌ Использование: update-stats <user_id> <field> <value>\n";
            exit(1);
        }
        $userId = intval($argv[2]);
        $field = $argv[3];
        $value = intval($argv[4]);
        $manager->updateStats($userId, $field, $value);
        break;
        
    case 'increment-stats':
        if ($argc < 4) {
            echo "❌ Использование: increment-stats <user_id> <field> [increment]\n";
            exit(1);
        }
        $userId = intval($argv[2]);
        $field = $argv[3];
        $increment = isset($argv[4]) ? intval($argv[4]) : 1;
        $manager->incrementStats($userId, $field, $increment);
        break;
        
    case 'user-info':
        if ($argc < 3) {
            echo "❌ Использование: user-info <user_id>\n";
            exit(1);
        }
        $userId = intval($argv[2]);
        $manager->showUserInfo($userId);
        break;
        
    case 'list-users':
        $manager->listUsers();
        break;
        
    case 'list-achievements':
        $manager->listAchievements();
        break;
        
    case 'history':
        if ($argc < 3) {
            echo "❌ Использование: history <user_id> [limit]\n";
            exit(1);
        }
        $userId = intval($argv[2]);
        $limit = isset($argv[3]) ? intval($argv[3]) : 10;
        $manager->showPointsHistory($userId, $limit);
        break;
        
    case 'init-user':
        if ($argc < 3) {
            echo "❌ Использование: init-user <user_id>\n";
            exit(1);
        }
        $userId = intval($argv[2]);
        $manager->initUser($userId);
        break;
        
    case 'check-achievements':
        if ($argc < 3) {
            echo "❌ Использование: check-achievements <user_id>\n";
            exit(1);
        }
        $userId = intval($argv[2]);
        $manager->checkAchievements($userId);
        echo "✅ Проверка достижений завершена\n";
        break;
        
    case 'help':
    case '--help':
    case '-h':
        showHelp();
        break;
        
    default:
        echo "❌ Неизвестная команда: $command\n";
        echo "Используйте 'php manage_bonus.php help' для справки\n";
        exit(1);
}

$conn->close();