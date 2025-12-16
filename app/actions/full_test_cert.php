<?php
/**
 * Script de teste completo: Gerar certificado e tentar baixar
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

// Setup environment
$_SERVER['HTTP_HOST'] = 'localhost';
$_SERVER['REQUEST_METHOD'] = 'POST';
$_SERVER['DOCUMENT_ROOT'] = 'C:/xampp/htdocs';

session_start();
$_SESSION['user_id'] = 1;
$_SESSION['user_role'] = 'parceiro';
$_SESSION['user_email'] = 'teste@parceiro.com';
$_SESSION['parceiro_id'] = 31;

// Step 1: Find available student and course
require_once __DIR__ . '/../config/config.php';

$conn = getDBConnection();

// Get first active student
$res = $conn->query("SELECT id, nome FROM alunos WHERE parceiro_id = 31 LIMIT 1");
if (!$res || $res->num_rows === 0) {
    die("ERROR: No student found for parceiro 31\n");
}
$student = $res->fetch_assoc();
echo "✓ Found student: {$student['nome']} (ID: {$student['id']})\n";

// Get first active course
$res = $conn->query("SELECT id, nome FROM cursos WHERE parceiro_id = 31 LIMIT 1");
if (!$res || $res->num_rows === 0) {
    die("ERROR: No course found for parceiro 31\n");
}
$course = $res->fetch_assoc();
echo "✓ Found course: {$course['nome']} (ID: {$course['id']})\n";

// Get active template
$res = $conn->query("SELECT id, arquivo_url FROM templates_certificados WHERE parceiro_id = 31 AND ativo = 1 LIMIT 1");
if (!$res || $res->num_rows === 0) {
    die("ERROR: No active template found for parceiro 31\n");
}
$template = $res->fetch_assoc();
echo "✓ Found template: ID {$template['id']}\n";

// Step 2: Generate certificate via POST simulation
echo "\n=== STEP 1: Generating Certificate ===\n";

$_POST['aluno'] = $student['id'];
$_POST['curso'] = $course['id'];
$_POST['data_conclusao'] = date('Y-m-d');
$_POST['template'] = $template['id'];

ob_start();
try {
    include __DIR__ . '/gerar-certificado.php';
    $output = ob_get_clean();
    echo "Generation output (first 200 chars): " . substr($output, 0, 200) . "\n";
} catch (Exception $e) {
    ob_end_clean();
    die("ERROR generating certificate: " . $e->getMessage() . "\n");
}

// Step 3: Find the newly generated certificate
$res = $conn->query("SELECT id, numero_certificado, arquivo_url FROM certificados WHERE parceiro_id = 31 ORDER BY id DESC LIMIT 1");
if (!$res || $res->num_rows === 0) {
    die("ERROR: No certificate found after generation\n");
}
$cert = $res->fetch_assoc();
echo "✓ Certificate generated: ID {$cert['id']}, Number: {$cert['numero_certificado']}\n";

if ($cert['arquivo_url']) {
    // Try to find the file
    $url = $cert['arquivo_url'];
    $path = str_replace('http://localhost/gestao.certificado', __DIR__ . '/../..', $url);
    $path = str_replace('/', DIRECTORY_SEPARATOR, $path);

    if (file_exists($path)) {
        $size = filesize($path);
        echo "✓ Certificate file exists: $path (Size: $size bytes)\n";

        // Check if it's a valid PDF
        $header = file_get_contents($path, false, null, 0, 4);
        if ($header === '%PDF') {
            echo "✓ File starts with PDF header\n";
        } else {
            echo "✗ WARNING: File does NOT start with PDF header!\n";
            echo "  First 20 bytes (hex): " . bin2hex(file_get_contents($path, false, null, 0, 20)) . "\n";
        }
    } else {
        echo "✗ WARNING: Certificate file not found at: $path\n";
    }
} else {
    echo "✗ WARNING: No arquivo_url set for certificate\n";
}

// Step 4: Try to download the certificate
echo "\n=== STEP 2: Testing Download ===\n";

$_SERVER['REQUEST_METHOD'] = 'GET';
$_GET['id'] = $cert['id'];
unset($_POST);

ob_start();
try {
    include __DIR__ . '/download-certificado.php';
    $download_output = ob_get_clean();

    echo "Download output size: " . strlen($download_output) . " bytes\n";

    if (strlen($download_output) === 0) {
        echo "✗ ERROR: Download output is EMPTY\n";
    } elseif (substr($download_output, 0, 4) === '%PDF') {
        echo "✓ SUCCESS: Download output is a valid PDF\n";
        file_put_contents(__DIR__ . '/test_download_result.pdf', $download_output);
        echo "✓ Saved to: test_download_result.pdf\n";
    } else {
        echo "✗ ERROR: Download output is NOT a valid PDF\n";
        echo "  First 200 bytes: " . substr($download_output, 0, 200) . "\n";
        echo "  First 100 bytes (hex): " . bin2hex(substr($download_output, 0, 100)) . "\n";
        file_put_contents(__DIR__ . '/test_download_ERROR.txt', $download_output);
        echo "✗ Saved ERROR output to: test_download_ERROR.txt\n";
    }
} catch (Exception $e) {
    ob_end_clean();
    echo "✗ ERROR downloading: " . $e->getMessage() . "\n";
}

echo "\n=== TEST COMPLETE ===\n";
