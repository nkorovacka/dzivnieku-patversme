<?php
if (session_status() === PHP_SESSION_NONE) session_start();
?>
<header class="main-header">
  <div class="container nav-container">
    <a href="index.php" class="logo">🐾 SirdsPaws</a>
    <nav>
      <ul class="nav-links">
        <li><a href="index.php">Sākums</a></li>
        <li><a href="pets.php">Dzīvnieki</a></li>
        <li><a href="favorites.php">Favorīti</a></li>
        <li><a href="favorites.html">Favorīti</a></li>
        <li><a href="applications.php">Mani pieteikumi</a></li>
        <li><a href="events.html">Pasākumi</a></li>
      </ul>
    </nav>

    <div class="auth-links">
      <?php if (isset($_SESSION['epasts'])): ?>
          <span style="margin-right:10px;">Sveiks, <?=htmlspecialchars($_SESSION['lietotajvards'])?></span>
          <?php if (!empty($_SESSION['admin']) && $_SESSION['admin'] == 1): ?>
            <a href="admin.php">Admin</a>
          <?php endif; ?>
          <a href="logout.php">Izrakstīties</a>
      <?php else: ?>
          <a href="login.html">Pieslēgties</a>
          <a href="register.html">Reģistrēties</a>
      <?php endif; ?>
    </div>
  </div>
</header>
