<?php
// Simple test: Try to download certificate 53 directly
$_SERVER['HTTP_HOST'] = 'localhost';
$_SERVER['REQUEST_METHOD'] = 'GET';
$_GET['id'] = 53;

session_start();
// Mock session for testing
$_SESSION['user_id'] = 1;
$_SESSION['user_role'] = 'parceiro';
$_SESSION['user_email'] = 'teste@parceiro.com';
$_SESSION['parceiro_id'] = 31; // From error logs

// Capture the entire output
ob_start();

try {
    include __DIR__ . '/download-certificado.php';
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage();
}

$output = ob_get_clean();

// Save the output
file_put_contents(__DIR__ . '/direct_test_53.pdf', $output);

// Analyze
echo "Output size: " . strlen($output) . " bytes\n";
echo "Starts with PDF: " . (substr($output, 0, 4) === '%PDF' ? 'YES' : 'NO') . "\n";

if (substr($output, 0, 4) !== '%PDF') {
    echo "First 200 bytes:\n";
    echo substr($output, 0, 200) . "\n\n";
    echo "First 200 bytes (hex):\n";
    echo bin2hex(substr($output, 0, 200)) . "\n";
}
