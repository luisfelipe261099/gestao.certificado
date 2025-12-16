<?php
/**
 * Recusar Solicitação de Plano - Admin
 */
require_once __DIR__ . '/bootstrap.php';

if (!isAuthenticated() || !hasRole(ROLE_ADMIN)) {
    $_SESSION['error'] = 'Acesso negado';
    redirect(APP_URL . '/login.php');
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $_SESSION['error'] = 'Método não permitido';
    redirect(APP_URL . '/admin/solicitacoes-planos.php');
}

$id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
if ($id <= 0) { redirect(APP_URL . '/admin/solicitacoes-planos.php'); }

try {
    $conn = getDBConnection();

    // Verificar pendência
    $stmt = $conn->prepare("SELECT status FROM solicitacoes_planos WHERE id = ? LIMIT 1");
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($res->num_rows === 0) {
        $_SESSION['error'] = 'Solicitação não encontrada';
        $stmt->close();
        $conn->close();
        redirect(APP_URL . '/admin/solicitacoes-planos.php');
    }
    $row = $res->fetch_assoc();
    $stmt->close();

    if ($row['status'] !== 'pendente') {
        $_SESSION['error'] = 'Solicitação já processada';
        $conn->close();
        redirect(APP_URL . '/admin/solicitacoes-planos.php');
    }

    $stmt = $conn->prepare("UPDATE solicitacoes_planos SET status = 'recusada', atualizado_em = NOW() WHERE id = ?");
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $stmt->close();

    $_SESSION['success'] = 'Solicitação recusada.';
    $conn->close();
} catch (Exception $e) {
    error_log('Erro ao recusar solicitação de plano: ' . $e->getMessage());
    $_SESSION['error'] = 'Erro ao recusar solicitação.';
}

redirect(APP_URL . '/admin/solicitacoes-planos.php');

