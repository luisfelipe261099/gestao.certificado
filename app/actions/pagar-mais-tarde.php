<?php
/**
 * Ação: Pagar Mais Tarde
 * Permite que o parceiro acesse o sistema mas bloqueia emissão de certificados
 */

require_once __DIR__ . '/../config/config.php';

if (!isAuthenticated() || !hasRole(ROLE_PARCEIRO)) {
    header('Location: ' . APP_URL . '/login.php');
    exit;
}

$user = getCurrentUser();
$parceiro_id = $user['parceiro_id'] ?? $user['id'];
$conn = getDBConnection();

try {
    // Log para debug
    error_log("pagar-mais-tarde: parceiro_id = $parceiro_id");

    // Marcar no banco que o parceiro optou por pagar depois
    // Verificar se a coluna pagamento_pendente existe
    $result = $conn->query("SHOW COLUMNS FROM parceiros LIKE 'pagamento_pendente'");
    if ($result && $result->num_rows > 0) {
        $stmt = $conn->prepare("UPDATE parceiros SET pagamento_pendente = 1 WHERE id = ?");
        if ($stmt) {
            $stmt->bind_param("i", $parceiro_id);
            $stmt->execute();
            error_log("pagar-mais-tarde: UPDATE parceiros affected_rows = " . $stmt->affected_rows);
            $stmt->close();
        }
    }

    // Atualizar a assinatura para status 'aguardando_pagamento' (mantém o plano associado)
    // Isso permite que o usuário veja seu plano no dashboard, mas fica bloqueado de emitir certificados
    // Buscar assinaturas que NÃO estejam já ativas ou canceladas
    $stmt = $conn->prepare("
        UPDATE assinaturas 
        SET status = 'aguardando_pagamento' 
        WHERE parceiro_id = ? 
          AND status NOT IN ('ativa', 'cancelada', 'expirada', 'aguardando_pagamento')
        ORDER BY criado_em DESC 
        LIMIT 1
    ");

    if ($stmt) {
        $stmt->bind_param("i", $parceiro_id);
        $stmt->execute();
        $affected = $stmt->affected_rows;
        error_log("pagar-mais-tarde: UPDATE assinaturas affected_rows = $affected");
        $stmt->close();

        // Se não atualizou nenhuma linha, pode ser que a assinatura já existe com outro status
        // Nesse caso, vamos verificar se existe alguma assinatura para este parceiro
        if ($affected == 0) {
            $stmt2 = $conn->prepare("SELECT id, status FROM assinaturas WHERE parceiro_id = ? ORDER BY criado_em DESC LIMIT 1");
            if ($stmt2) {
                $stmt2->bind_param("i", $parceiro_id);
                $stmt2->execute();
                $result2 = $stmt2->get_result();
                if ($row = $result2->fetch_assoc()) {
                    error_log("pagar-mais-tarde: Assinatura existente id={$row['id']} status={$row['status']}");
                    // Se encontrou uma assinatura 'pendente', atualizar para 'aguardando_pagamento'
                    if ($row['status'] === 'pendente') {
                        $stmt3 = $conn->prepare("UPDATE assinaturas SET status = 'aguardando_pagamento' WHERE id = ?");
                        $stmt3->bind_param("i", $row['id']);
                        $stmt3->execute();
                        error_log("pagar-mais-tarde: Forçou update para aguardando_pagamento");
                        $stmt3->close();
                    }
                } else {
                    error_log("pagar-mais-tarde: Nenhuma assinatura encontrada para parceiro_id=$parceiro_id");
                }
                $stmt2->close();
            }
        }
    }

    $conn->close();

    $_SESSION['warning'] = 'Você optou por pagar mais tarde. Poderá acessar o sistema, mas não poderá emitir certificados até que o pagamento seja confirmado.';
    header('Location: ' . APP_URL . '/parceiro/dashboard-parceiro.php');
    exit;

} catch (Throwable $e) {
    error_log("Erro em pagar-mais-tarde: " . $e->getMessage());
    $_SESSION['erro'] = 'Erro ao processar sua escolha. Tente novamente.';
    header('Location: ' . APP_URL . '/parceiro/primeiro-pagamento.php');
    exit;
}
