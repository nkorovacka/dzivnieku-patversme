<?php
session_start();
require_once __DIR__ . '/vendor/autoload.php';

if (!isset($_SESSION['user_id'])) {
    echo "<script>alert('Lūdzu, pieslēdzies, lai aizpildītu adopcijas anketu!'); window.location.href='login.html';</script>";
    exit;
}

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
    die("Savienojuma kļūda: " . $conn->connect_error);
}

$pet_id = intval($_GET['pet_id'] ?? 0);
if (!$pet_id) {
    echo "<script>alert('Nav norādīts dzīvnieks!'); window.location.href='pets.php';</script>";
    exit;
}

$stmt = $conn->prepare("SELECT vards, suga, vecums, dzimums FROM dzivnieki WHERE id = ?");
$stmt->bind_param("i", $pet_id);
$stmt->execute();
$pet = $stmt->get_result()->fetch_assoc();
$stmt->close();
$conn->close();

if (!$pet) {
    echo "<script>alert('Dzīvnieks nav atrasts!'); window.location.href='pets.php';</script>";
    exit;
}

function h($s){ return htmlspecialchars($s ?? '', ENT_QUOTES, 'UTF-8'); }
?>
<!DOCTYPE html>
<html lang="lv">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Adopcijas anketa — <?= h($pet['vards']) ?></title>
<style>
  body {
    font-family: 'Inter', sans-serif;
    background: linear-gradient(135deg, #667eea, #764ba2);
    color: #333;
    display: flex; justify-content: center; align-items: center;
    min-height: 100vh; margin: 0;
  }
  .form-wrapper {
    background: #fff;
    border-radius: 20px;
    box-shadow: 0 10px 30px rgba(0,0,0,.15);
    padding: 2rem 2.5rem;
    width: 92%; max-width: 620px;
  }
  .form-wrapper h2 { text-align: center; color: #4c1d95; margin: 0 0 1rem; }
  .pet-info { display: flex; gap: 15px; align-items: center; margin-bottom: 1.2rem; }
  .pet-info img { width: 120px; height: 120px; border-radius: 15px; object-fit: cover; }
  label { font-weight: 600; display: block; margin-top: 1rem; }
  input, select, textarea {
    width: 100%; padding: 10px; border-radius: 10px;
    border: 1px solid #ccc; font-size: 1rem; margin-top: 6px;
  }
  .help { font-size: .9rem; color: #6b7280; margin-top: 4px; }
  button {
    background: linear-gradient(135deg,#6366f1,#8b5cf6);
    border: none; color: white; padding: 12px 20px;
    border-radius: 10px; font-weight: 600; margin-top: 1.4rem; width: 100%;
    cursor: pointer;
  }
  button:hover { opacity: .9; }
  .back { display:block; text-align:center; margin-top: 1rem; color:#555; text-decoration:none; }
  .back:hover { text-decoration: underline; }
</style>
</head>
<body>

<div class="form-wrapper">
  <h2>🐾 Adopcijas anketa</h2>

  <div class="pet-info">
    <img src="<?= !empty($pet['attels']) ? htmlspecialchars($pet['attels']) : 'kitty.jpg' ?>" 
     alt="<?= h($pet['vards']) ?>">

    <div>
      <h3 style="margin:0 0 .25rem 0;"><?= h($pet['vards']) ?></h3>
      <p style="margin:0; color:#6b7280;">
        <?= h($pet['suga']) ?> — <?= h($pet['dzimums']) ?>, <?= h($pet['vecums']) ?> g.
      </p>
    </div>
  </div>

  <form action="adopt_pet.php" method="POST">
    <input type="hidden" name="pet_id" value="<?= (int)$pet_id ?>">

    <label for="arrival_date">Vēlamais datums</label>
    <input type="date" id="arrival_date" name="arrival_date" required>
    <div id="dateHelp" class="help">Izvēlies datumu (P–Pk 9–18, S 10–16, Sv 10–14).</div>

    <label for="arrival_time">Vēlamais laiks</label>
    <select id="arrival_time" name="arrival_time" required>
      <option value="">Izvēlies laiku...</option>
      <!-- opcijas tiks ģenerētas dinamiski -->
    </select>
    <div id="timeHelp" class="help"></div>

    <label for="notes">Pastāsti par sevi</label>
    <textarea id="notes" name="notes" placeholder="Kāpēc vēlies adoptēt šo dzīvnieku?" required></textarea>

    <button type="submit">📤 Nosūtīt pieteikumu</button>
  </form>

  <a href="pets.php" class="back">⬅ Atpakaļ uz dzīvniekiem</a>
</div>

<script>
// ===== Konfigurācija darba laikam (uz stundas soli) =====
function businessHoursFor(dateStr){
  const d = new Date(dateStr + 'T12:00:00'); // stabilizējam TZ
  const dow = d.getDay(); // 0=Sv, 6=S
  if (isNaN(dow)) return {start:9, end:18, label:'P–Pk: 9–18'};
  if (dow === 0) return {start:10, end:14, label:'Sv: 10–14'};
  if (dow === 6) return {start:10, end:16, label:'S: 10–16'};
  return {start:9, end:18, label:'P–Pk: 9–18'};
}

// ===== iestata min datumu (rīt) =====
const dateInput = document.getElementById('arrival_date');
const timeSelect = document.getElementById('arrival_time');
const timeHelp  = document.getElementById('timeHelp');

const tomorrow = new Date();
tomorrow.setDate(tomorrow.getDate() + 1);
dateInput.min = tomorrow.toISOString().split('T')[0];
if (!dateInput.value) dateInput.value = dateInput.min;

// paņem aizņemtos laikus no servera (tavs test_times.php)
async function fetchTaken(dateStr){
  try {
    const res = await fetch(`test_times.php?date=${encodeURIComponent(dateStr)}&pet_id=<?= (int)$pet_id ?>`, {
      headers: {'Cache-Control':'no-store'}
    });
    if (!res.ok) throw new Error('HTTP ' + res.status);
    const json = await res.json();
    // sagaidām masīvu, piemēram ["10:00","12:00"]
    return Array.isArray(json) ? json : [];
  } catch(e){
    console.warn('Neizdevās ielādēt aizņemtos laikus:', e);
    return [];
  }
}

// atjauno laiku opcijas, atspējojot aizņemtos
async function refreshTimes(){
  const dateStr = dateInput.value;
  if (!dateStr){ timeSelect.innerHTML = '<option value="">Izvēlies laiku...</option>'; return; }

  const cfg = businessHoursFor(dateStr);
  timeHelp.textContent = `Pieejamais darba laiks: ${cfg.label}`;

  // ielādē aizņemtos laikus
  const taken = await fetchTaken(dateStr); // HH:MM masīvs
  const setTaken = new Set(taken);

  // ģenerē opcijas
  let html = '<option value="">Izvēlies laiku...</option>';
  for(let h = cfg.start; h < cfg.end; h++){
    const t = String(h).padStart(2,'0') + ':00';
    const disabled = setTaken.has(t);
    html += `<option value="${t}" ${disabled?'disabled':''}>${t}${disabled?' — aizņemts':''}</option>`;
  }
  timeSelect.innerHTML = html;
}

dateInput.addEventListener('change', refreshTimes);
document.addEventListener('DOMContentLoaded', refreshTimes);
</script>

</body>
</html>
