<?php
/**
 * Deletar Pagamento - Sistema de Certificados
 */

require_once __DIR__ . '/bootstrap.php';

if (!isAuthenticated() || !hasRole(ROLE_ADMIN)) {
    $_SESSION['error'] = 'Acesso negado';
    redirect(APP_URL . '/login.php');
}

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (empty($id)) {
    $_SESSION['error'] = 'ID inválido';
    redirect(APP_URL . '/admin/pagamentos-admin.php');
}

$conn = getDBConnection();

// Verificar se pagamento existe
$stmt = $conn->prepare("SELECT id FROM pagamentos WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
if ($stmt->get_result()->num_rows === 0) {
    $_SESSION['error'] = 'Pagamento não encontrado';
    redirect(APP_URL . '/admin/pagamentos-admin.php');
}

// Deletar pagamento
$stmt = $conn->prepare("DELETE FROM pagamentos WHERE id = ?");
$stmt->bind_param("i", $id);

if ($stmt->execute()) {
    $_SESSION['success'] = 'Pagamento deletado com sucesso!';
} else {
    $_SESSION['error'] = 'Erro ao deletar pagamento: ' . $conn->error;
}

$conn->close();
redirect(APP_URL . '/admin/pagamentos-admin.php');
?>

