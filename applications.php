<?php
session_start();
if (!isset($_SESSION["user_id"])) {
  echo "<script>alert('Lūdzu, pieslēdzies, lai skatītu savus pieteikumus!'); window.location.href='login.php';</script>";
  exit;
}
?>
<!DOCTYPE html>
<html lang="lv">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Mani pieteikumi — SirdsPaws</title>
  <link rel="stylesheet" href="index.css">
  <link rel="stylesheet" href="applications.css">
</head>
<body>

<?php include 'navbar.php'; ?>

<section class="hero" style="min-height:300px;">
  <div class="container">
    <h1 class="hero-title">Mani pieteikumi</h1>
    <p class="hero-subtitle">Šeit Tu vari apskatīt visus savus adopcijas pieteikumus 🐾</p>
  </div>
</section>

<main class="container content-section">
  <div class="filters-card">
    <h2 class="section-title">Filtri</h2>
    <div class="filters">
      <div class="filter-group">
        <label for="filter-status">Statuss</label>
        <select id="filter-status">
          <option value="">Visi</option>
          <option value="gaida apstiprinājumu">Gaida apstiprinājumu</option>
          <option value="apstiprināts">Apstiprināts</option>
          <option value="noraidīts">Noraidīts</option>
        </select>
      </div>
      <button id="refresh-btn" class="btn">Atsvaidzināt</button>
    </div>
  </div>

  <div class="applications-list" id="apps-cards"></div>
  <div id="apps-empty" style="display:none;">Nav atrasts neviens pieteikums.</div>
  <div id="apps-error" style="display:none;">Neizdevās ielādēt pieteikumus.</div>
</main>

<footer>
  <div class="container">
    <p>&copy; 2025 SirdsPaws — Mīlestība katram dzīvniekam 🐾</p>
  </div>
</footer>

<script src="applications.js"></script>
</body>
</html>
