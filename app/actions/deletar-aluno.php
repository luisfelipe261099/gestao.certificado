<?php
/**
 * ============================================================================
 * DELETAR ALUNO - SISTEMA DE CERTIFICADOS
 * ============================================================================
 */

require_once '../config/config.php';

// Verificar autenticação e permissão
if (!isAuthenticated()) {
    redirect(APP_URL . '/login.php');
}

$is_admin = hasRole(ROLE_ADMIN);
$is_parceiro = hasRole(ROLE_PARCEIRO);

if (!$is_admin && !$is_parceiro) {
    $_SESSION['error'] = 'Acesso negado';
    redirect(APP_URL . '/login.php');
}

$conn = getDBConnection();
$user = getCurrentUser();

// Validar ID
$id = intval($_GET['id'] ?? 0);

if ($id <= 0) {
    $_SESSION['error'] = 'ID do aluno inválido';
    $redirect_url = $is_admin ? APP_URL . '/admin/alunos-admin.php' : APP_URL . '/parceiro/alunos-parceiro.php';
    redirect($redirect_url);
}

// Verificar se o aluno existe e se o usuário tem permissão
if ($is_parceiro) {
    $parceiro_id = $user['parceiro_id'] ?? $user['id'];
    $stmt = $conn->prepare("SELECT id FROM alunos WHERE id = ? AND parceiro_id = ?");
    $stmt->bind_param("ii", $id, $parceiro_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        $_SESSION['error'] = 'Aluno não encontrado ou você não tem permissão para deletá-lo';
        $stmt->close();
        $conn->close();
        redirect(APP_URL . '/parceiro/alunos-parceiro.php');
    }
    $stmt->close();
}

// Iniciar transação
$conn->begin_transaction();

try {
    // Deletar certificados do aluno
    $stmt = $conn->prepare("DELETE FROM certificados WHERE aluno_id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $certificados_deletados = $stmt->affected_rows;
    $stmt->close();
    
    // Deletar aluno
    $stmt = $conn->prepare("DELETE FROM alunos WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $stmt->close();
    
    // Commit da transação
    $conn->commit();
    
    $_SESSION['success'] = 'Aluno deletado com sucesso! ' . $certificados_deletados . ' certificado(s) também foram removidos.';
    
} catch (Exception $e) {
    // Rollback em caso de erro
    $conn->rollback();
    $_SESSION['error'] = 'Erro ao deletar aluno: ' . $e->getMessage();
}

$conn->close();

$redirect_url = $is_admin ? APP_URL . '/admin/alunos-admin.php' : APP_URL . '/parceiro/alunos-parceiro.php';
redirect($redirect_url);

