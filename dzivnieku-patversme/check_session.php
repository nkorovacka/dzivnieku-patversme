<?php
session_start();

echo "<h2>🔍 parbaude</h2>";
echo "<pre>";
print_r($_SESSION);
echo "</pre>";

if (isset($_SESSION['user_id'])) {
    echo "<p style='color: green;'>✅ autorizacija ir veiksmīga! User ID: " . $_SESSION['user_id'] . "</p>";
    echo "<p><a href='account.php'>Открыть профиль</a></p>";
} else {
    echo "<p style='color: red;'>❌ neveiksmīga autorizācija</p>";
    echo "<p><a href='login.php'>Войти</a> | <a href='register.php'>Регистрация</a></p>";
}
?>