<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "🔍 Pārbaude Railway datubāzes savienojuma...\n\n";

// Ielādējam db_conn.php
require_once 'db_conn.php';

echo "✅ Savienojums ar Railway datubāzi veiksmīgs!\n\n";

// Pārbaudām vai eksistē users tabula
$result = $conn->query("SHOW TABLES LIKE 'users'");

if ($result->num_rows > 0) {
    echo "✅ Tabula 'users' eksistē\n\n";
    
    // Parādām tabulas struktūru
    echo "📋 Tabulas struktūra:\n";
    $result = $conn->query("DESCRIBE users");
    while ($row = $result->fetch_assoc()) {
        echo "  - " . $row['Field'] . " (" . $row['Type'] . ")\n";
    }
    
    // Saskaitām lietotājus
    $result = $conn->query("SELECT COUNT(*) as count FROM users");
    $row = $result->fetch_assoc();
    echo "\n📊 Lietotāju skaits datubāzē: " . $row['count'] . "\n";
} else {
    echo "⚠️ Tabula 'users' neeksistē!\n";
    echo "Izveidojiet to ar komandu zemāk.\n";
}

$conn->close();
?>
