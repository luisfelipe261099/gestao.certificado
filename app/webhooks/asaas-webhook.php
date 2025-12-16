<?php
/**
 * Webhook Asaas - Recebe notificações de pagamentos
 * Não requer autenticação
 */

require_once '../config/config.php';
require_once '../lib/AsaasAPI.php';

// Obter dados do webhook
$dados_webhook = json_decode(file_get_contents('php://input'), true);

if (empty($dados_webhook)) {
    http_response_code(400);
    echo json_encode(['error' => 'Dados vazios']);
    exit;
}

$conn = getDBConnection();
$asaas = new AsaasAPI($conn);

// Processar webhook
$resultado = $asaas->processarWebhook($dados_webhook);

if ($resultado['success']) {
    http_response_code(200);
    echo json_encode(['success' => true, 'message' => 'Webhook processado']);
} else {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => $resultado['error']]);
}

$conn->close();
?>

