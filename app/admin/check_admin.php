<?php
session_start();

// Admin credentials
define('ADMIN_EMAIL', 'Admin123@gmail.com');
define('ADMIN_PASSWORD', 'Admin*123');

function isAdmin() {
    return isset($_SESSION['user_email']) && $_SESSION['user_email'] === ADMIN_EMAIL;
}

function checkAdminAccess() {
    if (!isAdmin()) {
        header('Location: index.html');
        exit();
    }
}
?>
