<?php
// Run once to create admin user if not exists
require_once __DIR__ . '/db_conn.php';

$email = 'Admin123@gmail.com';
$passwordPlain = 'Admin*123';
$username = 'Admin';

$stmt = $conn->prepare("SELECT id FROM lietotaji WHERE epasts = ?");
$stmt->bind_param("s", $email);
$stmt->execute();
$res = $stmt->get_result();
if ($res && $res->num_rows > 0) {
    echo "Admin already exists.\n";
    exit;
}

$hash = password_hash($passwordPlain, PASSWORD_DEFAULT);
$ins = $conn->prepare("INSERT INTO lietotaji (lietotajvards, epasts, parole, admin) VALUES (?, ?, ?, 1)");
$ins->bind_param("sss", $username, $email, $hash);
if ($ins->execute()) {
    echo "Admin user created.\n";
} else {
    echo "Failed to create admin: " . $conn->error . "\n";
}
?>


