<?php
session_start();
if (!isset($_SESSION["epasts"])) {
    header("Location: login.html");
    exit;
}
if ($_SESSION["admin"] != 1) {
    header("Location: index.php");
    exit;
}

$page = $_GET['page'] ?? 'users';
?>
<!DOCTYPE html>
<html lang="lv">
<head>
    <meta charset="UTF-8">
    <title>Admin panelis — SirdsPaws</title>
    <link rel="stylesheet" href="admin.css">
</head>
<body>
<header>
    <h1>🐾 Admin panelis</h1>
    <p>Sveiks, <?= htmlspecialchars($_SESSION['lietotajvards']) ?>!</p>
    <nav>
        <a href="admin.php?page=users" class="<?= $page === 'users' ? 'active' : '' ?>">👥 Lietotāji</a>
        <a href="admin.php?page=pets" class="<?= $page === 'pets' ? 'active' : '' ?>">🐶 Dzīvnieki</a>
        <a href="admin.php?page=adoptions" class="<?= $page === 'adoptions' ? 'active' : '' ?>">📋 Pieteikumi</a>
        <a href="admin.php?page=events" class="<?= $page === 'events' ? 'active' : '' ?>">📅 Pasākumi</a>
        <a href="logout.php" class="logout">Izrakstīties</a>
    </nav>
</header>

<main>
<?php
    if ($page === 'pets') {
        include 'admin_pets.php';
    } elseif ($page === 'adoptions') {
        include 'admin_adoptions.php';
    } elseif ($page === 'events') {
        include 'admin_events.php';
    } else {
        include 'admin_users.php';
    }
?>
</main>
</body>
</html>