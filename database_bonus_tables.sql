-- Таблица для хранения очков пользователей
CREATE TABLE IF NOT EXISTS user_points (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    points INT DEFAULT 0,
    total_earned INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_user (user_id)
);

-- Таблица справочника достижений
CREATE TABLE IF NOT EXISTS achievements (
    id INT AUTO_INCREMENT PRIMARY KEY,
    achievement_key VARCHAR(50) UNIQUE NOT NULL,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    icon VARCHAR(10) DEFAULT '🏆',
    points INT DEFAULT 0,
    requirement_type ENUM('favorites', 'applications', 'events', 'profile', 'custom') DEFAULT 'custom',
    requirement_value INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Таблица достижений пользователей
CREATE TABLE IF NOT EXISTS user_achievements (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    achievement_id INT NOT NULL,
    earned_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (achievement_id) REFERENCES achievements(id) ON DELETE CASCADE,
    UNIQUE KEY unique_user_achievement (user_id, achievement_id)
);

-- Таблица статистики пользователей
CREATE TABLE IF NOT EXISTS user_stats (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    favorites_count INT DEFAULT 0,
    applications_count INT DEFAULT 0,
    events_attended INT DEFAULT 0,
    profile_complete INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_user_stats (user_id)
);

-- Таблица истории начисления очков
CREATE TABLE IF NOT EXISTS points_history (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    points INT NOT NULL,
    reason VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_created (user_id, created_at)
);

-- Вставка базовых достижений
INSERT INTO achievements (achievement_key, name, description, icon, points, requirement_type, requirement_value) VALUES
('first_step', 'Pirmais Solis', 'Reģistrējies sistēmā', '🎯', 10, 'custom', 0),
('profile_complete', 'Pilnīgs Profils', 'Aizpildīts viss profils', '📱', 20, 'profile', 100),
('animal_lover', 'Dzīvnieku Draugs', 'Pievienoti 5 favorīti', '❤️', 30, 'favorites', 5),
('first_application', 'Atbildīgs Adopcētājs', 'Iesniegts pirmais pieteikums', '📝', 50, 'applications', 1),
('event_participant', 'Aktīvais Dalībnieks', 'Apmeklēti 3 pasākumi', '🎪', 40, 'events', 3),
('super_fan', 'Super Fans', 'Pievienoti 10 favorīti', '💝', 50, 'favorites', 10),
('active_applicant', 'Aktīvs Pieteicējs', 'Iesniegti 5 pieteikumi', '📋', 75, 'applications', 5),
('event_master', 'Pasākumu Meistars', 'Apmeklēti 10 pasākumi', '🎭', 100, 'events', 10)
ON DUPLICATE KEY UPDATE name=VALUES(name);