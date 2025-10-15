<?php

session_start();
require_once __DIR__ . '/db_conn.php';

// ✅ Ja forma tika iesniegta
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $epasts = trim($_POST["epasts"] ?? '');
    $parole = trim($_POST["parole"] ?? '');

    if (empty($epasts) || empty($parole)) {
        echo "<script>alert('❌ Lūdzu aizpildi visus laukus!'); window.history.back();</script>";
        exit;
    }

    // Pārbauda lietotāju
    $check = $conn->prepare("SELECT * FROM lietotaji WHERE epasts = ?");
    $check->bind_param("s", $epasts);
    $check->execute();
    $result = $check->get_result();
    $user = $result ? $result->fetch_assoc() : null;

    if (!$user) {
        echo "<script>alert('❌ Lietotājs ar šo e-pastu nav reģistrēts!'); window.location.href='login.html';</script>";
        exit;
    }

    // Pārbauda paroli
    if (!password_verify($parole, $user["parole"])) {
        echo "<script>alert('❌ Nepareiza parole!'); window.location.href='login.html';</script>";
        exit;
    }

    // ✅ Saglabā sesijā lietotāja info (ar user_id!)
    $_SESSION["user_id"] = (int)$user["id"];
    $_SESSION["lietotajvards"] = $user["lietotajvards"];
    $_SESSION["epasts"] = $user["epasts"];
    $_SESSION["admin"] = (int)$user["admin"];

    // ✅ Novirza uz atbilstošo lapu
    if ($_SESSION["admin"] === 1) {
        header("Location: admin.php");
    } else {
        header("Location: index.php");
    }
    exit;
}

// ✅ Ja forma nav iesniegta, bet lietotājs jau ir ielogojies
if (isset($_SESSION["epasts"])) {
    if (!empty($_SESSION["admin"]) && $_SESSION["admin"] == 1) {
        header("Location: admin.php");
    } else {
        header("Location: index.php");
    }
    exit;
}

// Citādi atver login formu
header("Location: login.html");
exit;
?>
