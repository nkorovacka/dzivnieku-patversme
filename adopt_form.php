<?php
session_start();
require_once __DIR__ . '/vendor/autoload.php';

if (!isset($_SESSION['user_id'])) {
  echo "<script>alert('Lūdzu pieslēdzies, lai aizpildītu adopcijas anketu!'); window.location.href='login.html';</script>";
  exit;
}

$pet_id = intval($_GET['pet_id'] ?? 0);
if (!$pet_id) {
  echo "<script>alert('Kļūda: nav norādīts dzīvnieks.'); window.location.href='pets.php';</script>";
  exit;
}

// ielādē .env un dabū dzīvnieka datus
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->safeLoad();

$conn = new mysqli(
  $_ENV['DB_HOST'] ?? 'localhost',
  $_ENV['DB_USER'] ?? 'root',
  $_ENV['DB_PASS'] ?? '',
  $_ENV['DB_NAME'] ?? 'dzivnieku_patversme',
  $_ENV['DB_PORT'] ?? 3306
);
if ($conn->connect_error) {
  die("❌ Neizdevās savienoties ar datubāzi: " . $conn->connect_error);
}

$petStmt = $conn->prepare("SELECT vards, attels, suga, vecums, dzimums FROM dzivnieki WHERE id = ?");
$petStmt->bind_param("i", $pet_id);
$petStmt->execute();
$petRes = $petStmt->get_result();
$pet = $petRes->fetch_assoc();

function h($s){ return htmlspecialchars((string)$s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }
?>
<!DOCTYPE html>
<html lang="lv">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Adopcijas anketa — SirdsPaws</title>
  <link rel="stylesheet" href="index.css">
  <style>
    body{
      font-family:"Inter",sans-serif;
      background:linear-gradient(135deg,#667eea,#764ba2);
      margin:0; padding:0; min-height:100vh;
    }
    .wrap{
      max-width:960px; margin:40px auto;
      background:#fff; border-radius:16px;
      box-shadow:0 10px 25px rgba(0,0,0,.15);
      overflow:hidden;
    }
    .header{
      background:linear-gradient(135deg,#6366f1,#8b5cf6);
      color:white; padding:20px 30px;
      display:flex; justify-content:space-between; align-items:center;
    }
    .header h1{margin:0; font-size:1.5rem;}
    .pet-info{display:flex;gap:20px;padding:25px;border-bottom:1px solid #eee;}
    .pet-info img{width:200px;height:180px;object-fit:cover;border-radius:12px;}
    .pet-meta h2{margin:0 0 10px;color:#111827;}
    .pet-meta p{margin:0;color:#6b7280;}

    form{padding:25px;}
    label{display:block;font-weight:600;color:#374151;margin-bottom:6px;margin-top:16px;}
    input, select, textarea{
      width:100%;padding:10px 12px;border-radius:10px;
      border:1.8px solid #e5e7eb;font:inherit;background:#fff;
      transition:all .2s;
    }
    input:focus, select:focus, textarea:focus{
      border-color:#6366f1; box-shadow:0 0 0 3px rgba(99,102,241,.2); outline:none;
    }
    textarea{resize:vertical;min-height:100px;}
    .help{font-size:.9rem;color:#6b7280;margin-top:3px;}

    .actions{display:flex;gap:12px;margin-top:25px;flex-wrap:wrap;}
    .btn{border:none;border-radius:10px;padding:10px 20px;font-weight:700;cursor:pointer;transition:.2s;}
    .btn-primary{background:linear-gradient(135deg,#6366f1,#8b5cf6);color:white;}
    .btn-primary:hover{opacity:.9;}
    .btn-ghost{background:#f3f4f6;color:#111827;}
    .btn-ghost:hover{background:#e5e7eb;}
  </style>
</head>
<body>
  <?php include 'navbar.php'; ?>

  <div class="wrap">
    <div class="header">
      <h1>🐾 Adopcijas anketa</h1>
      <span>Dzīvnieks #<?= (int)$pet_id ?></span>
    </div>

    <div class="pet-info">
      <img src="<?= h($pet['attels'] ?: 'kitty.jpg') ?>" alt="Dzīvnieka bilde">
      <div class="pet-meta">
        <h2><?= h($pet['vards'] ?? 'Mīlulis') ?></h2>
        <p><?= h($pet['suga'] ?? '') ?> <?= h($pet['dzimums'] ?? '') ?>, <?= h($pet['vecums'] ?? '') ?> gadi</p>
      </div>
    </div>

    <form action="adopt_pet.php" method="POST">
      <input type="hidden" name="pet_id" value="<?= (int)$pet_id ?>">

      <label for="arrival_date">Vēlamais apmeklējuma datums</label>
      <input type="date" id="arrival_date" name="arrival_date" required>
      <div class="help">Izvēlies datumu (ne agrāk kā šodien).</div>

      <label for="arrival_time">Vēlamā stunda</label>
      <select id="arrival_time" name="arrival_time" required>
        <option value="">Izvēlies laiku...</option>
      </select>
      <div class="help" id="timeHelp">Darba laiks: P–Pk 9:00–18:00, S 10:00–16:00, Sv 10:00–14:00.</div>

      <label for="notes">Īss apraksts par sevi</label>
      <textarea id="notes" name="notes" placeholder="Pastāsti, kāpēc vēlies adoptēt un par savu dzīvesvietu..." required></textarea>

      <div class="actions">
        <button class="btn btn-primary" type="submit">📤 Nosūtīt pieteikumu</button>
        <a href="pets.php" class="btn btn-ghost">⬅ Atpakaļ uz dzīvniekiem</a>
      </div>
    </form>
  </div>

  <script>
  // Minimālais datums = šodiena
  const dateInput = document.getElementById('arrival_date');
  const timeSelect = document.getElementById('arrival_time');
  const helpText = document.getElementById('timeHelp');

  const tomorrow = new Date();
tomorrow.setDate(tomorrow.getDate() + 1); // rītdienas datums
const minDate = tomorrow.toISOString().split('T')[0];
dateInput.min = minDate;
if (!dateInput.value) dateInput.value = minDate;

  function updateTimeOptions() {
    if (!dateInput.value) return;
    const date = new Date(dateInput.value);
    const day = date.getDay(); // 0=Sv,1=Pirmdiena,...
    let start=9, end=18, text="P–Pk: 9:00–18:00";

    switch(day){
      case 0: start=10; end=14; text="Sv: 10:00–14:00"; break;
      case 6: start=10; end=16; text="S: 10:00–16:00"; break;
      default: start=9; end=18; text="P–Pk: 9:00–18:00";
    }

    timeSelect.innerHTML = '<option value="">Izvēlies laiku...</option>';
    for(let h=start; h<end; h++){
      const time = `${String(h).padStart(2,'0')}:00`;
      const opt = document.createElement('option');
      opt.value = time;
      opt.textContent = time;
      timeSelect.appendChild(opt);
    }
    helpText.textContent = "Pieejamais laiks: " + text;
  }

  dateInput.addEventListener('change', updateTimeOptions);

  // 🔥 Izsauc uzreiz, lai stundas parādās jau sākumā
  document.addEventListener('DOMContentLoaded', updateTimeOptions);
  </script>
</body>
</html>
