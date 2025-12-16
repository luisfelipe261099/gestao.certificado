<?php
$host = 'localhost';
$user = 'root';
$pass = '';
$db = 'sistema_parceiro_murilo';

$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error)
    die("Connection failed: " . $conn->connect_error);

$result = $conn->query("SELECT id, nome_empresa, email FROM parceiros LIMIT 1");
if ($row = $result->fetch_assoc()) {
    echo "ID: " . $row['id'] . "\n";
    echo "Nome: " . $row['nome_empresa'] . "\n";
    echo "Email: " . $row['email'] . "\n";
} else {
    echo "No user found.\n";
}
$conn->close();
?>