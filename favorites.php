<?php
session_start();
?>
<!DOCTYPE html>
<html lang="lv">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Favorīti - SirdsPaws</title>
    <link rel="stylesheet" href="index.css">
</head>
<body>

<?php include 'navbar.php'; ?>  <!-- šis iekļauj kopējo navigāciju -->

<main class="container">
    <section class="main-content">
        <div class="hero">
            <h1>Mani favorītie dzīvnieki</h1>
            <p>Šeit Tu vari redzēt visus dzīvniekus, kurus esi pievienojis favorītos</p>
        </div>

        <div class="pets-grid">
            <div class="pet-card">
                <img src="images/cat1.jpg" alt="Kaķis" />
                <h3>Riks</h3>
                <p>Kaķis</p>
            </div>
            <div class="pet-card">
                <img src="images/dog1.jpg" alt="Suns" />
                <h3>Baksis</h3>
                <p>Suns</p>
            </div>
            <div class="pet-card">
                <img src="images/cat2.jpg" alt="Kaķis" />
                <h3>Murziks</h3>
                <p>Kaķis</p>
            </div>
            <div class="pet-card">
                <img src="images/dog2.jpg" alt="Suns" />
                <h3>Žaks</h3>
                <p>Suns</p>
            </div>
        </div>
    </section>
</main>

<?php include 'footer.php'; ?> <!-- ja tev ir atsevišķs footer fails -->

<script src="script.js"></script>
</body>
</html>
