<?php
// Script para capturar exatamente o que está sendo enviado ao navegador
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Mock environment
$_SERVER['HTTP_HOST'] = 'localhost';
$_SERVER['REQUEST_METHOD'] = 'GET';
$_GET['id'] = 53;

// Start output buffer to capture EVERYTHING
ob_start();

// Include the download script
try {
    include __DIR__ . '/download-certificado.php';
} catch (Exception $e) {
    echo "EXCEPTION: " . $e->getMessage();
}

// Get the captured output
$output = ob_get_clean();

// Save to file
file_put_contents(__DIR__ . '/captured_output_53.bin', $output);

// Analyze the output
echo "Output size: " . strlen($output) . " bytes\n";
echo "First 100 bytes (hex): " . bin2hex(substr($output, 0, 100)) . "\n";
echo "First 100 bytes (raw): " . substr($output, 0, 100) . "\n";

// Check if it starts with PDF header
if (substr($output, 0, 4) === '%PDF') {
    echo "✓ Starts with PDF header\n";
} else {
    echo "✗ DOES NOT start with PDF header!\n";
    echo "First 200 chars:\n";
    echo substr($output, 0, 200) . "\n";
}

// Check for common corruption signs
if (strpos($output, 'Warning:') !== false) {
    echo "✗ Contains PHP warnings!\n";
}
if (strpos($output, 'Notice:') !== false) {
    echo "✗ Contains PHP notices!\n";
}
if (strpos($output, '<?php') !== false) {
    echo "✗ Contains PHP tags!\n";
}

// Try to validate PDF structure
$pdf_header_pos = strpos($output, '%PDF');
if ($pdf_header_pos !== false && $pdf_header_pos > 0) {
    echo "✗ PDF header found at position $pdf_header_pos (should be 0)!\n";
    echo "Junk before PDF: " . bin2hex(substr($output, 0, $pdf_header_pos)) . "\n";
}
