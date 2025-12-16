<?php
/**
 * Action: Ativar/Desativar Template do Sistema
 */

require_once '../config/config.php';

if (!isAuthenticated() || !hasRole(ROLE_ADMIN)) {
    $_SESSION['error'] = 'Acesso negado.';
    header('Location: ' . APP_URL . '/login.php');
    exit;
}

$template_id = (int) ($_GET['id'] ?? 0);
$novo_status = (int) ($_GET['status'] ?? 0);

if ($template_id <= 0) {
    $_SESSION['error'] = 'Template inválido.';
    header('Location: ' . APP_URL . '/admin/templates-sistema.php');
    exit;
}

try {
    $conn = getDBConnection();

    // Verificar se é template do sistema
    $stmt = $conn->prepare("SELECT id, nome FROM templates_certificados WHERE id = ? AND template_sistema = 1");
    $stmt->bind_param("i", $template_id);
    $stmt->execute();
    $template = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$template) {
        throw new Exception('Template não encontrado ou não é um template do sistema.');
    }

    // Atualizar status
    $stmt = $conn->prepare("UPDATE templates_certificados SET ativo = ? WHERE id = ?");
    $stmt->bind_param("ii", $novo_status, $template_id);
    $stmt->execute();
    $stmt->close();

    $acao = $novo_status == 1 ? 'ativado' : 'desativado';
    $_SESSION['success'] = "Template '{$template['nome']}' $acao com sucesso!";

} catch (Exception $e) {
    $_SESSION['error'] = 'Erro: ' . $e->getMessage();
}

header('Location: ' . APP_URL . '/admin/templates-sistema.php');
exit;
?>