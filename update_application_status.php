<?php
session_start();
require_once 'db_conn.php';

if (!isset($_SESSION['admin']) || $_SESSION['admin'] != 1) {
    header("Location: login.html");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: admin_adoptions.php");
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

    // ✅ Atjauno adopcijas pieteikuma statusu
    $stmt = $conn->prepare("UPDATE adopcijas_pieteikumi SET statuss = ? WHERE id = ?");
    $stmt->execute([$status, $id]);

    // ✅ Maina dzīvnieka statusu atkarībā no jaunā pieteikuma statusa
    if ($status === 'apstiprināts') {
        $conn->prepare("UPDATE dzivnieki SET statuss = 'adoptēts' WHERE id = ?")->execute([$pet_id]);
    } elseif ($status === 'noraidīts') {
        $conn->prepare("UPDATE dzivnieki SET statuss = 'pieejams' WHERE id = ?")->execute([$pet_id]);
    }

    $conn->commit();

    header("Location: admin_adoptions.php");
    exit;
} catch (Throwable $e) {
    $conn->rollBack();
    echo "<pre>Kļūda: " . htmlspecialchars($e->getMessage()) . "</pre>";
    exit;
}
