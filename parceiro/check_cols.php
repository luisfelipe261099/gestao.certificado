<?php
$host = 'localhost';
$user = 'root';
$pass = '';
$db = 'sistema_parceiro_murilo';

$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error)
    die("Connection failed: " . $conn->connect_error);

$result = $conn->query("SHOW COLUMNS FROM parceiros");
while ($row = $result->fetch_assoc()) {
    echo $row['Field'] . "\n";
}
$conn->close();
?>