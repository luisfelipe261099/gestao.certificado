<?php
/**
 * Ação Admin: Marcar Fatura como Paga Manualmente
 */

require_once __DIR__ . '/../config/config.php';

// Verificar se é admin
if (!isAuthenticated() || !hasRole(ROLE_ADMIN)) {
    http_response_code(403);
    die(json_encode(['success' => false, 'message' => 'Acesso negado']));
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    die(json_encode(['success' => false, 'message' => 'Método não permitido']));
}

$fatura_id = isset($_POST['fatura_id']) ? (int) $_POST['fatura_id'] : 0;

if ($fatura_id <= 0) {
    http_response_code(400);
    die(json_encode(['success' => false, 'message' => 'ID de fatura inválido']));
}

try {
    $conn = getDBConnection();

    // Buscar fatura
    $stmt = $conn->prepare("SELECT id, parceiro_id, status FROM faturas WHERE id = ?");
    $stmt->bind_param("i", $fatura_id);
    $stmt->execute();
    $fatura = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$fatura) {
        http_response_code(404);
        die(json_encode(['success' => false, 'message' => 'Fatura não encontrada']));
    }

    if ($fatura['status'] === 'pago') {
        die(json_encode(['success' => true, 'message' => 'Fatura já está paga']));
    }

    // Atualizar status da fatura
    $stmt = $conn->prepare("UPDATE faturas SET status = 'pago', data_pagamento = NOW(), metodo_pagamento = 'manual_admin', updated_at = NOW() WHERE id = ?");
    $stmt->bind_param("i", $fatura_id);
    $stmt->execute();
    $stmt->close();

    // Atualizar flag de pagamento pendente do parceiro
    $stmt = $conn->prepare("UPDATE parceiros SET pagamento_pendente = 0, updated_at = NOW() WHERE id = ?");
    $stmt->bind_param("i", $fatura['parceiro_id']);
    $stmt->execute();
    $stmt->close();

    // Log da ação
    error_log("ADMIN: Fatura #$fatura_id marcada como paga manualmente para parceiro #{$fatura['parceiro_id']}");

    echo json_encode([
        'success' => true,
        'message' => 'Fatura marcada como paga com sucesso!'
    ]);

} catch (Exception $e) {
    error_log("Erro ao marcar fatura como paga: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Erro ao processar: ' . $e->getMessage()
    ]);
}
