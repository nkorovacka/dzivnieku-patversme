<?php
session_start();
require_once __DIR__ . '/vendor/autoload.php';

// Ielādē .env failu
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

// Izveido savienojumu ar datubāzi
$conn = new mysqli(
    $_ENV['DB_HOST'],
    $_ENV['DB_USER'],
    $_ENV['DB_PASS'],
    $_ENV['DB_NAME'],
    $_ENV['DB_PORT']
);

// Pārbauda savienojumu
if ($conn->connect_error) {
    die("<script>alert('❌ Savienojuma kļūda ar datubāzi!'); window.history.back();</script>");
}

// Kad forma tiek iesniegta
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $lietotajvards = trim($_POST["lietotajvards"]);
    $epasts = trim($_POST["epasts"]);
    $parole = trim($_POST["parole"]);
    $confirm = trim($_POST["confirm"]);

    // ✅ Validācija servera pusē
    if (!preg_match("/^[A-Za-z0-9_]{3,20}$/", $lietotajvards)) {
        echo "<script>alert('❌ Lietotājvārds nav derīgs!'); window.history.back();</script>";
        exit;
    }

    if (!filter_var($epasts, FILTER_VALIDATE_EMAIL)) {
        echo "<script>alert('❌ E-pasta adrese nav derīga!'); window.history.back();</script>";
        exit;
    }

    if (!preg_match("/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[!@#$%^&*]).{8,}$/", $parole)) {
        echo "<script>alert('❌ Parolei jābūt vismaz 8 simboliem, ar lielajiem/mazajiem burtiem, ciparu un speciālu simbolu!'); window.history.back();</script>";
        exit;
    }

    if ($parole !== $confirm) {
        echo "<script>alert('❌ Paroles nesakrīt!'); window.history.back();</script>";
        exit;
    }

    // Pārbauda, vai lietotājs jau eksistē
    $check = $conn->prepare("SELECT * FROM lietotaji WHERE epasts = ? OR lietotajvards = ?");
    $check->bind_param("ss", $epasts, $lietotajvards);
    $check->execute();
    $result = $check->get_result();

    if ($result->num_rows > 0) {
        echo "<script>alert('⚠️ Lietotājs ar šo e-pastu vai lietotājvārdu jau eksistē!'); window.history.back();</script>";
        exit;
    }

    // Šifrē paroli
    $hashed = password_hash($parole, PASSWORD_DEFAULT);

    // Saglabā lietotāju
    $insert = $conn->prepare("INSERT INTO lietotaji (lietotajvards, epasts, parole, admin) VALUES (?, ?, ?, 0)");
    $insert->bind_param("sss", $lietotajvards, $epasts, $hashed);

    if ($insert->execute()) {
        $_SESSION["lietotajvards"] = $lietotajvards;
        $_SESSION["epasts"] = $epasts;
        $_SESSION["admin"] = 0;

        echo "<script>alert('✅ Reģistrācija veiksmīga!'); window.location.href = 'index.html';</script>";
        exit;
    } else {
        echo "<script>alert('❌ Kļūda saglabājot lietotāju: " . $conn->error . "'); window.history.back();</script>";
    }

    $insert->close();
}

$conn->close();
?>
