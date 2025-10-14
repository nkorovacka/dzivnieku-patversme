<?php
require_once 'config.php';

$conn = getConnection();

// SQL для создания таблицы
$sql = "CREATE TABLE IF NOT EXISTS lietotaji (
    id INT AUTO_INCREMENT PRIMARY KEY,
    lietotajvards VARCHAR(50) UNIQUE NOT NULL,
    epasts VARCHAR(100) UNIQUE NOT NULL,
    parole VARCHAR(255) NOT NULL,
    admin TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

if ($conn->query($sql) === TRUE) {
    echo "✅ Tabula 'lietotaji' izveidota veiksmīgi!<br>";
    
    // Создание тестового пользователя
    $test_username = "admin";
    $test_email = "admin@test.lv";
    $test_password = password_hash("admin123", PASSWORD_BCRYPT);
    
    $insert = "INSERT INTO lietotaji (lietotajvards, epasts, parole, admin) 
               VALUES ('$test_username', '$test_email', '$test_password', 1)";
    
    if ($conn->query($insert) === TRUE) {
        echo "✅ Testa lietotājs izveidots!<br>";
        echo "E-pasts: admin@test.lv<br>";
        echo "Parole: admin123<br>";
    }
} else {
    echo "❌ Kļūda: " . $conn->error;
}

// Показать все таблицы
$tables = $conn->query("SHOW TABLES");
echo "<br><strong>Esošās tabulas:</strong><br>";
while ($row = $tables->fetch_array()) {
    echo "- " . $row[0] . "<br>";
}

$conn->close();
?>