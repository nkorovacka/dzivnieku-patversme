<?php
require_once 'db_conn.php';

echo "<h2>👥 Пользователи в Railway БД</h2>";

$result = $conn->query("SELECT id, username, email, created_at FROM users ORDER BY id");

if ($result->num_rows > 0) {
    echo "<table border='1' cellpadding='10' style='border-collapse: collapse;'>";
    echo "<tr><th>ID</th><th>Username</th><th>Email</th><th>Дата регистрации</th></tr>";
    
    while ($row = $result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . $row['id'] . "</td>";
        echo "<td>" . htmlspecialchars($row['username']) . "</td>";
        echo "<td>" . htmlspecialchars($row['email']) . "</td>";
        echo "<td>" . date('d.m.Y H:i', strtotime($row['created_at'])) . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    echo "<p>Всего пользователей: " . $result->num_rows . "</p>";
} else {
    echo "<p style='color: red;'>❌ В Railway базе данных нет пользователей!</p>";
    echo "<p>Зарегистрируйтесь заново: <a href='register.php'>Регистрация</a></p>";
}

$conn->close();
?>
