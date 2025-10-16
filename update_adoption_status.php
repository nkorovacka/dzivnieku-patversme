<?php
session_start();
require_once 'db_conn.php';

// Tikai administratoriem
if (!isset($_SESSION['admin']) || $_SESSION['admin'] != 1) {
    header("Location: login.html");
    exit;
}

// Tikai POST pieprasījumi
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: admin.php?page=adoptions");
    exit;
}

$id = (int)($_POST['id'] ?? 0);
$pet_id = (int)($_POST['pet_id'] ?? 0);
$status = trim($_POST['status'] ?? '');

if (!$id || !$pet_id || !in_array($status, ['apstiprināts', 'noraidīts'])) {
    die("❌ Nederīgi dati!");
}

try {
    $conn->beginTransaction();

    // ✅ Atjauno pieteikuma statusu
    $stmt = $conn->prepare("UPDATE adopcijas_pieteikumi SET statuss = ? WHERE id = ?");
    $stmt->execute([$status, $id]);

    // ✅ Maina dzīvnieka statusu
    if ($status === 'apstiprināts') {
        $conn->prepare("UPDATE dzivnieki SET statuss = 'adoptēts' WHERE id = ?")->execute([$pet_id]);
    } elseif ($status === 'noraidīts') {
        $conn->prepare("UPDATE dzivnieki SET statuss = 'pieejams' WHERE id = ?")->execute([$pet_id]);
    }

    $conn->commit();

    // ✅ Pāradresē atpakaļ uz admin paneli ar paziņojumu
    header("Location: admin.php?page=adoptions&success=1");
    exit;

} catch (Throwable $e) {
    $conn->rollBack();
    echo "<pre>Kļūda: " . htmlspecialchars($e->getMessage()) . "</pre>";
    exit;
}
