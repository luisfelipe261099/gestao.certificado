<?php
/**
 * ============================================================================
 * DELETAR CURSO - SISTEMA DE CERTIFICADOS
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
    $_SESSION['error'] = 'ID do curso inválido';
    $redirect_url = $is_admin ? APP_URL . '/admin/cursos-admin.php' : APP_URL . '/parceiro/cursos-parceiro.php';
    redirect($redirect_url);
}

// Verificar se o curso existe e se o usuário tem permissão
if ($is_parceiro) {
    $parceiro_id = $user['parceiro_id'] ?? $user['id'];
    $stmt = $conn->prepare("SELECT id FROM cursos WHERE id = ? AND parceiro_id = ?");
    $stmt->bind_param("ii", $id, $parceiro_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        $_SESSION['error'] = 'Curso não encontrado ou você não tem permissão para deletá-lo';
        $stmt->close();
        $conn->close();
        redirect(APP_URL . '/parceiro/cursos-parceiro.php');
    }
    $stmt->close();
}

// Verificar se existem certificados vinculados ao curso
$stmt = $conn->prepare("SELECT COUNT(*) as total FROM certificados WHERE curso_id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();
$stmt->close();

if ($row['total'] > 0) {
    $_SESSION['error'] = 'Não é possível deletar este curso pois existem ' . $row['total'] . ' certificado(s) vinculado(s) a ele.';
    $redirect_url = $is_admin ? APP_URL . '/admin/cursos-admin.php' : APP_URL . '/parceiro/cursos-parceiro.php';
    $conn->close();
    redirect($redirect_url);
}

// Deletar curso
$stmt = $conn->prepare("DELETE FROM cursos WHERE id = ?");
$stmt->bind_param("i", $id);

if ($stmt->execute()) {
    $_SESSION['success'] = 'Curso deletado com sucesso!';
} else {
    $_SESSION['error'] = 'Erro ao deletar curso: ' . $stmt->error;
}

$stmt->close();
$conn->close();

$redirect_url = $is_admin ? APP_URL . '/admin/cursos-admin.php' : APP_URL . '/parceiro/cursos-parceiro.php';
redirect($redirect_url);

