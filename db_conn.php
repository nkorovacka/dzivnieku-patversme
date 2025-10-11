<?php
$servername = "localhost";
$username = "nikoko";
$password = "janis123"; 
$dbname = "dzivnieku_patversme";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Savienojuma kļūda: " . $conn->connect_error);
}
?>
