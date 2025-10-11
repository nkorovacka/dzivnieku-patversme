<?php
$servername = "shinkansen.proxy.rlwy.net"; // Publiskais Railway host
$username = "root"; // MYSQLUSER
$password = "oYVsYmRdokiELhESSYyNUiTfHwwpqEfE"; // MYSQLPASSWORD
$dbname = "railway"; // MYSQLDATABASE
$port = 36226; // Ports no MYSQL_PUBLIC_URL

$conn = new mysqli($servername, $username, $password, $dbname, $port);

if ($conn->connect_error) {
    die("❌ Savienojuma kļūda: " . $conn->connect_error);
}

// echo "✅ Savienojums ar Railway datubāzi izdevies!";
?>
