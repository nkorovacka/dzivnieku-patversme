<?php
/**
 * Компонент иконки профиля
 * Файл: includes/profile_icon.php
 * 
 * Использование: включите этот файл в ваш header.php
 * require_once 'includes/profile_icon.php';
 */

// Проверяем сессию
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Определяем, авторизован ли пользователь
$isLoggedIn = isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);

// URL для перенаправления
$profileUrl = $isLoggedIn ? 'account.php' : 'register.php';

// Заголовок для подсказки
$profileTitle = $isLoggedIn ? 'Mans konts' : 'Reģistrēties';

// Получаем имя пользователя, если авторизован
$userName = $isLoggedIn && isset($_SESSION['user_name']) ? $_SESSION['user_name'] : '';
?>

<style>
    .profile-icon-wrapper {
        position: relative;
        display: inline-block;
    }

    .profile-icon-link {
        display: block;
        text-decoration: none;
    }

    .profile-icon {
        width: 42px;
        height: 42px;
        border-radius: 50%;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        border: 2px solid transparent;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
    }

    .profile-icon:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        border-color: rgba(255, 255, 255, 0.3);
    }

    .profile-icon:active {
        transform: translateY(0);
    }

    .profile-icon svg {
        width: 24px;
        height: 24px;
        fill: white;
        transition: transform 0.3s ease;
    }

    .profile-icon:hover svg {
        transform: scale(1.1);
    }

    /* Стиль для неавторизованного пользователя */
    .profile-icon.guest {
        background: linear-gradient(135deg, #667eea 0%, #667eea 100%);
    }

    /* Стиль для авторизованного пользователя */
    .profile-icon.logged-in {
        background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);
    }

    /* Тултип с именем пользователя */
    .profile-tooltip {
        position: absolute;
        bottom: -35px;
        right: 0;
        background-color: #333;
        color: white;
        padding: 6px 12px;
        border-radius: 6px;
        font-size: 13px;
        white-space: nowrap;
        opacity: 0;
        visibility: hidden;
        transition: all 0.3s ease;
        pointer-events: none;
        z-index: 1000;
    }

    .profile-tooltip::before {
        content: '';
        position: absolute;
        top: -6px;
        right: 15px;
        width: 0;
        height: 0;
        border-left: 6px solid transparent;
        border-right: 6px solid transparent;
        border-bottom: 6px solid #333;
    }

    .profile-icon-wrapper:hover .profile-tooltip {
        opacity: 1;
        visibility: visible;
        bottom: -40px;
    }

    /* Индикатор авторизации */
    .status-indicator {
        position: absolute;
        bottom: 2px;
        right: 2px;
        width: 12px;
        height: 12px;
        background-color: #4CAF50;
        border: 2px solid white;
        border-radius: 50%;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
    }

    .status-indicator.offline {
        background-color: #9e9e9e;
    }

    /* Анимация пульсации для авторизованного пользователя */
    @keyframes pulse {
        0%, 100% {
            opacity: 1;
        }
        50% {
            opacity: 0.7;
        }
    }

    .profile-icon.logged-in .status-indicator {
        animation: pulse 2s ease-in-out infinite;
    }

    /* Адаптивность для мобильных */
    @media (max-width: 768px) {
        .profile-icon {
            width: 38px;
            height: 38px;
        }
        
        .profile-icon svg {
            width: 22px;
            height: 22px;
        }
    }
</style>

<div class="profile-icon-wrapper">
    <a href="<?php echo htmlspecialchars($profileUrl); ?>" class="profile-icon-link">
        <div class="profile-icon <?php echo $isLoggedIn ? 'logged-in' : 'guest'; ?>">
            <?php if ($isLoggedIn): ?>
                <!-- Иконка для авторизованного пользователя -->
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">
                    <path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/>
                </svg>
                <!-- Индикатор онлайн статуса -->
                <span class="status-indicator"></span>
            <?php else: ?>
                <!-- Иконка для неавторизованного пользователя -->
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">
                    <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm0 3c1.66 0 3 1.34 3 3s-1.34 3-3 3-3-1.34-3-3 1.34-3 3-3zm0 14.2c-2.5 0-4.71-1.28-6-3.22.03-1.99 4-3.08 6-3.08 1.99 0 5.97 1.09 6 3.08-1.29 1.94-3.5 3.22-6 3.22z"/>
                </svg>
                <!-- Индикатор оффлайн статуса -->
                <span class="status-indicator offline"></span>
            <?php endif; ?>
        </div>
    </a>
    
    <!-- Тултип с информацией -->
    <div class="profile-tooltip">
        <?php if ($isLoggedIn): ?>
            <?php echo $userName ? htmlspecialchars($userName) : 'Mans konts'; ?>
        <?php else: ?>
            Reģistrēties
        <?php endif; ?>
    </div>
</div>
