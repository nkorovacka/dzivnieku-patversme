<?php
session_start();
require_once 'check_admin.php';

header('Content-Type: application/json');

echo json_encode([
    'isLoggedIn' => isset($_SESSION['user_email']),
    'isAdmin' => isAdmin(),
    'email' => $_SESSION['user_email'] ?? null
]);
?>
