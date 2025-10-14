<?php
// ✅ Pilnīgi droša sesijas test konfigurācija
session_set_cookie_params([
    'lifetime' => 86400,     // 1 diena
    'path' => '/',
    'domain' => '',          // pašreizējais host (localhost)
    'secure' => false,       // nav HTTPS
    'httponly' => true,
    'samesite' => 'Lax'
]);

ini_set('session.save_path', '/var/lib/php/sessions');
ini_set('session.gc_maxlifetime', 86400);
ini_set('session.cookie_lifetime', 86400);
ini_set('session.cookie_secure', false);
ini_set('session.cookie_httponly', true);

session_start();

echo "<pre>";
echo "=== PHP SESSION TEST ===\n";
echo "Session ID: " . session_id() . "\n\n";

// Ja nav iestatīts — iestata test vērtību
if (!isset($_SESSION['test_counter'])) {
    $_SESSION['test_counter'] = 1;
    echo "🔹 Pirmā ielāde — iestatīts test_counter = 1\n";
} else {
    $_SESSION['test_counter']++;
    echo "✅ Sesija saglabājas! test_counter = " . $_SESSION['test_counter'] . "\n";
}

echo "\nSESSION SATURS:\n";
print_r($_SESSION);

echo "\nCOOKIE SATURS:\n";
print_r($_COOKIE);

echo "\nServer info:\n";
echo "session.save_path = " . ini_get('session.save_path') . "\n";
echo "session.cookie_domain = " . var_export(ini_get('session.cookie_domain'), true) . "\n";
echo "session.cookie_path = " . ini_get('session.cookie_path') . "\n";
echo "session.cookie_lifetime = " . ini_get('session.cookie_lifetime') . "\n";
echo "</pre>";
?>
