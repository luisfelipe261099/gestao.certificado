<?php
/**
 * Get Parceiro - Sistema de Certificados
 * Retorna dados do parceiro em JSON
 */

require_once __DIR__ . '/bootstrap.php';

// Verificar autenticação
if (!isAuthenticated()) {
    http_response_code(401);
    echo json_encode(['error' => 'Não autenticado']);
    exit;
}

// Verificar se ID foi fornecido
if (!isset($_GET['id']) || empty($_GET['id'])) {
    http_response_code(400);
    echo json_encode(['error' => 'ID não fornecido']);
    exit;
}

$parceiro_id = intval($_GET['id']);
$conn = getDBConnection();

try {
    // Buscar parceiro
    $stmt = $conn->prepare("SELECT id, nome_empresa, email FROM parceiros WHERE id = ?");
    $stmt->bind_param("i", $parceiro_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        http_response_code(404);
        echo json_encode(['error' => 'Parceiro não encontrado']);
        exit;
    }
    
    $parceiro = $result->fetch_assoc();
    $stmt->close();
    
    // Retornar JSON
    header('Content-Type: application/json');
    echo json_encode($parceiro);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}

$conn->close();
?>

