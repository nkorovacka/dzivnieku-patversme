<?php
$servername = "localhost";
$username = "nikoko";
$password = "janis123"; 
$dbname = "dzivnieku_patversme";

// izveido savienojumu
$conn = new mysqli($servername, $username, $password, $dbname);

// pārbauda savienojumu
if ($conn->connect_error) {
    die("Savienojuma kļūda: " . $conn->connect_error);
}

// ja forma ir iesniegta
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $lietotajvards = trim($_POST["lietotajvards"]);
    $epasts = trim($_POST["epasts"]);
    $parole = trim($_POST["parole"]);
    $confirm = trim($_POST["confirm"]);

    if (empty($lietotajvards) || empty($epasts) || empty($parole)) {
        echo "❌ Lūdzu aizpildi visus laukus!";
        exit;
    }

    if ($parole !== $confirm) {
        echo "❌ Paroles nesakrīt!";
        exit;
    }

    // pārbauda vai e-pasts vai lietotājvārds jau eksistē
    $check = $conn->prepare("SELECT * FROM lietotaji WHERE epasts = ? OR lietotajvards = ?");
    $check->bind_param("ss", $epasts, $lietotajvards);
    $check->execute();
    $result = $check->get_result();

    if ($result->num_rows > 0) {
        echo "⚠️ Lietotājs ar šo e-pastu vai lietotājvārdu jau eksistē!";
        exit;
    }

    // šifrē paroli
    $hashed = password_hash($parole, PASSWORD_DEFAULT);

    // ievieto jaunu lietotāju
    $insert = $conn->prepare("INSERT INTO lietotaji (lietotajvards, epasts, parole, admin) VALUES (?, ?, ?, 0)");
    $insert->bind_param("sss", $lietotajvards, $epasts, $hashed);

    if ($insert->execute()) {
        echo "✅ Reģistrācija veiksmīga!";
    } else {
        echo "❌ Kļūda saglabājot lietotāju: " . $conn->error;
    }

    $insert->close();
}
$conn->close();
?>
