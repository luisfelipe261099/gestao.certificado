<?php
/**
 * ============================================================================
 * ASSINAR CONTRATO - SISTEMA DE CERTIFICADOS
 * ============================================================================
 * Processa a assinatura do contrato pelo parceiro
 * ============================================================================
 */

require_once __DIR__ . '/bootstrap.php';

// Requer login como parceiro
if (!isAuthenticated() || !hasRole(ROLE_PARCEIRO)) {
    $_SESSION['error'] = 'Acesso negado. Faça login como parceiro.';
    redirect(APP_URL . '/login.php');
}

// Somente POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $_SESSION['error'] = 'Método não permitido';
    redirect(APP_URL . '/parceiro/contratos.php');
}

$contrato_id = isset($_POST['contrato_id']) ? (int)$_POST['contrato_id'] : 0;
if ($contrato_id <= 0) {
    $_SESSION['error'] = 'Contrato inválido';
    redirect(APP_URL . '/parceiro/contratos.php');
}

try {
    $conn = getDBConnection();
    $user = getCurrentUser();
    $parceiro_id = $user['parceiro_id'] ?? $user['id'];

    // ========================================================================
    // BUSCAR CONTRATO
    // ========================================================================
    $stmt = $conn->prepare("
        SELECT id, parceiro_id, status, tipo, solicitacao_plano_id, assinatura_id
        FROM contratos
        WHERE id = ? AND parceiro_id = ?
        LIMIT 1
    ");

    if (!$stmt) {
        $_SESSION['error'] = 'Erro ao buscar contrato.';
        $conn->close();
        redirect(APP_URL . '/parceiro/contratos.php');
    }

    $stmt->bind_param('ii', $contrato_id, $parceiro_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        $_SESSION['error'] = 'Contrato não encontrado.';
        $stmt->close();
        $conn->close();
        redirect(APP_URL . '/parceiro/contratos.php');
    }

    $contrato = $result->fetch_assoc();
    $stmt->close();

    // Verificar se já foi assinado
    if ($contrato['status'] !== 'pendente_assinatura') {
        $_SESSION['error'] = 'Este contrato já foi processado.';
        $conn->close();
        redirect(APP_URL . '/parceiro/contratos.php');
    }

    // ========================================================================
    // ATUALIZAR CONTRATO COMO ASSINADO
    // ========================================================================
    $stmt = $conn->prepare("
        UPDATE contratos
        SET status = 'assinado', assinado_em = NOW(), assinado_por_parceiro = 1, atualizado_em = NOW()
        WHERE id = ? AND parceiro_id = ?
    ");

    if (!$stmt) {
        $_SESSION['error'] = 'Erro ao assinar contrato.';
        $conn->close();
        redirect(APP_URL . '/parceiro/contratos.php');
    }

    $stmt->bind_param('ii', $contrato_id, $parceiro_id);

    if (!$stmt->execute()) {
        $_SESSION['error'] = 'Erro ao assinar contrato: ' . $stmt->error;
        $stmt->close();
        $conn->close();
        redirect(APP_URL . '/parceiro/contratos.php');
    }

    $stmt->close();

    // ========================================================================
    // SE FOR MUDANÇA DE PLANO, ATUALIZAR SOLICITAÇÃO
    // ========================================================================
    if ($contrato['tipo'] === 'mudanca_plano' && $contrato['solicitacao_plano_id']) {
        $stmt = $conn->prepare("
            UPDATE solicitacoes_planos
            SET atualizado_em = NOW()
            WHERE id = ? AND parceiro_id = ?
        ");

        if ($stmt) {
            $stmt->bind_param('ii', $contrato['solicitacao_plano_id'], $parceiro_id);
            $stmt->execute();
            $stmt->close();
        }
    }

    // ========================================================================
    // SE FOR RENOVAÇÃO, CONFIRMAR RENOVAÇÃO
    // ========================================================================
    if ($contrato['tipo'] === 'renovacao' && $contrato['assinatura_id']) {
        // Atualizar assinatura para confirmar renovação
        $stmt = $conn->prepare("
            UPDATE assinaturas
            SET status = 'ativa', atualizado_em = NOW()
            WHERE id = ? AND parceiro_id = ?
        ");

        if ($stmt) {
            $stmt->bind_param('ii', $contrato['assinatura_id'], $parceiro_id);
            $stmt->execute();
            $stmt->close();
        }
    }

    $conn->close();

    $_SESSION['success'] = 'Contrato assinado com sucesso! Você pode agora utilizar seu plano.';

} catch (Exception $e) {
    error_log('Erro ao assinar contrato: ' . $e->getMessage());
    $_SESSION['error'] = 'Erro ao assinar contrato: ' . $e->getMessage();
}

redirect(APP_URL . '/parceiro/contratos.php');
?>

