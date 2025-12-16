<?php
/**
 * Deletar Fatura - Sistema de Certificados
 */

require_once __DIR__ . '/bootstrap.php';

if (!isAuthenticated() || !hasRole(ROLE_ADMIN)) {
    $_SESSION['error'] = 'Acesso negado';
    redirect(APP_URL . '/login.php');
}

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (empty($id)) {
    $_SESSION['error'] = 'ID inválido';
    redirect(APP_URL . '/admin/faturas-admin.php');
}

$conn = getDBConnection();

// Verificar se fatura existe
$stmt = $conn->prepare("SELECT id FROM faturas WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
if ($stmt->get_result()->num_rows === 0) {
    $_SESSION['error'] = 'Fatura não encontrada';
    redirect(APP_URL . '/admin/faturas-admin.php');
}

// Deletar fatura
$stmt = $conn->prepare("DELETE FROM faturas WHERE id = ?");
$stmt->bind_param("i", $id);

if ($stmt->execute()) {
    $_SESSION['success'] = 'Fatura deletada com sucesso!';
} else {
    $_SESSION['error'] = 'Erro ao deletar fatura: ' . $conn->error;
}

$conn->close();
redirect(APP_URL . '/admin/faturas-admin.php');
?>

