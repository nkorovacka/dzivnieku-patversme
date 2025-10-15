<?php
session_start();
echo "<h2>🧹 Очистка сессии</h2>";
echo "<p>Старая сессия:</p>";
echo "<pre>";
print_r($_SESSION);
echo "</pre>";

$_SESSION = array();
session_destroy();

echo "<p style='color: green;'>✅ Сессия очищена!</p>";
echo "<p><a href='register.php'>Зарегистрироваться</a> | <a href='login.php'>Войти</a></p>";
?>
