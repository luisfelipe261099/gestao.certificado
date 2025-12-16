<?php
// ULTRA MINIMAL VERSION - Just serve the existing file
// NO output buffering, NO FPDF generation, JUST file serving

// Disable ALL error output
@ini_set('display_errors', '0');
@ini_set('log_errors', '0');
@error_reporting(0);

// Start session
session_start();

// Simple auth check
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'parceiro') {
    http_response_code(403);
    die();
}

$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
if ($id <= 0) {
    http_response_code(400);
    die();
}

// Database config
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'sistema_parceiro_murilo');

$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($conn->connect_error) {
    http_response_code(500);
    die();
}

$parceiro_id = $_SESSION['parceiro_id'] ?? 0;

//Get certificate file URL
$stmt = $conn->prepare("SELECT arquivo_url, numero_certificado FROM certificados WHERE id = ? AND parceiro_id = ?");
$stmt->bind_param("ii", $id, $parceiro_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    http_response_code(404);
    die();
}

$row = $result->fetch_assoc();
$arquivo_url = $row['arquivo_url'];
$numero = $row['numero_certificado'] ?? "certificado-$id";

if (empty($arquivo_url)) {
    http_response_code(404);
    die();
}

// Convert URL to file path
$path = str_replace('http://localhost/gestao.certificado', 'C:/xampp/htdocs/gestao.certificado', $arquivo_url);
$path = str_replace('/', DIRECTORY_SEPARATOR, $path);

if (!file_exists($path)) {
    http_response_code(404);
    die();
}

// Send file
$filename = 'certificado-' . preg_replace('/[^A-Za-z0-9_-]/', '', $numero) . '.pdf';

header('Content-Description: File Transfer');
header('Content-Type: application/pdf');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Content-Transfer-Encoding: binary');
header('Expires: 0');
header('Cache-Control: must-revalidate');
header('Pragma: public');
header('Content-Length: ' . filesize($path));

readfile($path);
exit;
