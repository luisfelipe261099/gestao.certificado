<?php
/**
 * Webhook Asaas - Atualização Automática de Pagamentos
 * URL para configurar no Asaas: https://seusite.com/app/webhooks/asaas.php
 */

// Log tudo para debug
$logFile = __DIR__ . '/../../logs/webhook_asaas.log';
$input = file_get_contents('php://input');
file_put_contents($logFile, date('Y-m-d H:i:s') . " - Webhook recebido:\n" . $input . "\n\n", FILE_APPEND);

// Decodificar JSON
$data = json_decode($input, true);

if (!$data) {
    http_response_code(400);
    die('Invalid JSON');
}

require_once __DIR__ . '/../config/config.php';

$conn = getDBConnection();

try {
    // Asaas envia events com diferentes tipos
    $event = $data['event'] ?? '';
    $payment = $data['payment'] ?? [];

    // Log do evento
    error_log("WEBHOOK ASAAS: Event=$event, Payment ID=" . ($payment['id'] ?? 'N/A'));

    // Eventos que indicam pagamento confirmado
    $pagamentoConfirmado = in_array($event, [
        'PAYMENT_CONFIRMED',
        'PAYMENT_RECEIVED',
        'PAYMENT_RECEIVED_IN_CASH'
    ]);

    if ($pagamentoConfirmado && !empty($payment['id'])) {
        $asaas_id = $payment['id'];
        $valor = $payment['value'] ?? 0;
        $data_pagamento = $payment['paymentDate'] ?? date('Y-m-d');

        // Buscar fatura pelo external_reference ou asaas_id
        $external_ref = $payment['externalReference'] ?? '';

        $stmt = $conn->prepare("
            SELECT f.id, f.parceiro_id 
            FROM faturas f 
            WHERE f.asaas_payment_id = ? OR f.external_reference = ?
            LIMIT 1
        ");
        $stmt->bind_param("ss", $asaas_id, $external_ref);
        $stmt->execute();
        $fatura = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if ($fatura) {
            // Atualizar status da fatura para PAGO
            $stmt = $conn->prepare("
                UPDATE faturas 
                SET status = 'pago', 
                    data_pagamento = ?,
                    asaas_payment_id = ?,
                    updated_at = NOW()
                WHERE id = ?
            ");
            $stmt->bind_param("ssi", $data_pagamento, $asaas_id, $fatura['id']);
            $stmt->execute();
            $stmt->close();

            // LIBERAR O PARCEIRO - Crucial!
            $stmt = $conn->prepare("
                UPDATE parceiros 
                SET pagamento_pendente = 0,
                    updated_at = NOW()
                WHERE id = ?
            ");
            $stmt->bind_param("i", $fatura['parceiro_id']);
            $stmt->execute();
            $stmt->close();

            error_log("WEBHOOK ASAAS: Fatura #{$fatura['id']} marcada como PAGA. Parceiro #{$fatura['parceiro_id']} LIBERADO!");

            // Atualizar também a tabela de boletos se existir
            $stmt = $conn->prepare("
                UPDATE asaas_boletos 
                SET status = 'pago',
                    data_pagamento = ?
                WHERE fatura_id = ?
            ");
            $stmt->bind_param("si", $data_pagamento, $fatura['id']);
            $stmt->execute();
            $stmt->close();

            http_response_code(200);
            echo json_encode(['success' => true, 'message' => 'Pagamento confirmado']);
        } else {
            error_log("WEBHOOK ASAAS: Fatura não encontrada para Asaas ID: $asaas_id");
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Fatura não encontrada']);
        }
    } else {
        // Outros eventos (apenas log)
        error_log("WEBHOOK ASAAS: Evento $event recebido (não processado)");
        http_response_code(200);
        echo json_encode(['success' => true, 'message' => 'Evento recebido']);
    }

} catch (Exception $e) {
    error_log("ERRO WEBHOOK ASAAS: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

$conn->close();
