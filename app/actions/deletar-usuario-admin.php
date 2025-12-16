<?php
/**
 * Ação: Deletar Usuário Administrativo
 */

require_once '../config/config.php';

// Verificar autenticação e permissão
if (!isAuthenticated() || !hasRole(ROLE_ADMIN)) {
    redirect(APP_URL . '/login.php');
}

$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;

if ($id <= 0) {
    $_SESSION['error'] = 'ID inválido.';
    redirect(APP_URL . '/admin/usuarios-admin.php');
}

// Impedir auto-deleção
if ($id == $_SESSION['user_id']) {
    $_SESSION['error'] = 'Você não pode excluir sua própria conta.';
    redirect(APP_URL . '/admin/usuarios-admin.php');
}

$conn = getDBConnection();

// Deletar admin
$stmt = $conn->prepare("DELETE FROM administradores WHERE id = ?");
$stmt->bind_param("i", $id);

if ($stmt->execute()) {
    $_SESSION['success'] = 'Administrador removido com sucesso!';
} else {
    $_SESSION['error'] = 'Erro ao remover administrador: ' . $stmt->error;
}

$stmt->close();
$conn->close();

redirect(APP_URL . '/admin/usuarios-admin.php');
