<?php
/**
 * Deletar Plano - Sistema de Certificados
 */

require_once __DIR__ . '/bootstrap.php';

// Verificar autenticação e permissão
if (!isAuthenticated() || !hasRole(ROLE_ADMIN)) {
    redirect(APP_URL . '/login.php');
}

// Verificar se ID foi fornecido
if (!isset($_GET['id']) || empty($_GET['id'])) {
    $_SESSION['error'] = 'ID do plano não fornecido.';
    redirect(APP_URL . '/admin/planos-admin.php');
}

$plano_id = intval($_GET['id']);
$conn = getDBConnection();

try {
    // Verificar se plano existe
    $stmt = $conn->prepare("SELECT id FROM planos WHERE id = ?");
    $stmt->bind_param("i", $plano_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        $_SESSION['error'] = 'Plano não encontrado.';
        redirect(APP_URL . '/admin/planos-admin.php');
    }
    
    $stmt->close();
    
    // Deletar plano
    $stmt = $conn->prepare("DELETE FROM planos WHERE id = ?");
    $stmt->bind_param("i", $plano_id);
    
    if ($stmt->execute()) {
        $_SESSION['success'] = 'Plano deletado com sucesso!';
    } else {
        $_SESSION['error'] = 'Erro ao deletar plano: ' . $conn->error;
    }
    
    $stmt->close();
    
} catch (Exception $e) {
    $_SESSION['error'] = 'Erro ao processar: ' . $e->getMessage();
}

$conn->close();
redirect(APP_URL . '/admin/planos-admin.php');
?>

