<?php
session_start();
require_once 'check_admin.php';
require_once 'db_conn.php';

// Check if user is admin
checkAdminAccess();

header('Content-Type: application/json');

try {
    // Get all users from database
    $sql = "SELECT id, email, name, role, created_at as registeredAt FROM users ORDER BY created_at DESC";
    $result = $conn->query($sql);

    $users = [];
    if ($result->num_rows > 0) {
        while($row = $result->fetch_assoc()) {
            $users[] = $row;
        }
    }

    echo json_encode([
        'success' => true,
        'users' => $users
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Kļūda ielādējot lietotājus: ' . $e->getMessage()
    ]);
}

$conn->close();
?>
