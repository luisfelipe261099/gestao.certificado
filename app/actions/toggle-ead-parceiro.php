<?php
/**
 * Toggle EAD para Parceiro
 * Ativa ou desativa o acesso ao módulo EAD para um parceiro
 */

require_once '../config/config.php';

// Verificar autenticação
if (!isAuthenticated() || !hasRole(ROLE_ADMIN)) {
    $_SESSION['error'] = 'Acesso negado.';
    redirect(APP_URL . '/login.php');
}

// Validar dados
$parceiro_id = isset($_POST['parceiro_id']) ? intval($_POST['parceiro_id']) : 0;
$ead_ativo = isset($_POST['ead_ativo']) ? intval($_POST['ead_ativo']) : 0;

if ($parceiro_id <= 0) {
    $_SESSION['error'] = 'Parceiro inválido.';
    redirect(APP_URL . '/admin/parceiros-admin.php');
}

$conn = getDBConnection();

// Atualizar status do EAD
$stmt = $conn->prepare("UPDATE parceiros SET ead_ativo = ? WHERE id = ?");
$stmt->bind_param("ii", $ead_ativo, $parceiro_id);

if ($stmt->execute()) {
    $status = $ead_ativo == 1 ? 'ativado' : 'desativado';
    $_SESSION['success'] = "EAD {$status} com sucesso para este parceiro!";
} else {
    $_SESSION['error'] = 'Erro ao atualizar: ' . $conn->error;
}

$stmt->close();
$conn->close();

redirect(APP_URL . '/admin/parceiros-admin.php');
?>

