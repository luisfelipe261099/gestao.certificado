<?php
/**
 * ============================================================================
 * CANCELAR CERTIFICADO - SISTEMA DE CERTIFICADOS
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
    $_SESSION['error'] = 'ID do certificado inválido';
    $redirect_url = $is_admin ? APP_URL . '/admin/certificados-admin.php' : APP_URL . '/parceiro/certificados-parceiro.php';
    redirect($redirect_url);
}

// Verificar se o certificado existe e se o usuário tem permissão
if ($is_parceiro) {
    $parceiro_id = $user['parceiro_id'] ?? $user['id'];
    $stmt = $conn->prepare("SELECT id, status FROM certificados WHERE id = ? AND parceiro_id = ?");
    $stmt->bind_param("ii", $id, $parceiro_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        $_SESSION['error'] = 'Certificado não encontrado ou você não tem permissão para cancelá-lo';
        $stmt->close();
        $conn->close();
        redirect(APP_URL . '/parceiro/certificados-parceiro.php');
    }
    
    $cert = $result->fetch_assoc();
    $stmt->close();
} else {
    // Admin pode cancelar qualquer certificado
    $stmt = $conn->prepare("SELECT id, status FROM certificados WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        $_SESSION['error'] = 'Certificado não encontrado';
        $stmt->close();
        $conn->close();
        redirect(APP_URL . '/admin/certificados-admin.php');
    }
    
    $cert = $result->fetch_assoc();
    $stmt->close();
}

// Verificar se o certificado já está cancelado
if ($cert['status'] === 'cancelado') {
    $_SESSION['warning'] = 'Este certificado já está cancelado';
    $redirect_url = $is_admin ? APP_URL . '/admin/certificados-admin.php' : APP_URL . '/parceiro/certificados-parceiro.php';
    $conn->close();
    redirect($redirect_url);
}

// Cancelar certificado
$stmt = $conn->prepare("UPDATE certificados SET status = 'cancelado', data_cancelamento = NOW() WHERE id = ?");
$stmt->bind_param("i", $id);

if ($stmt->execute()) {
    $_SESSION['success'] = 'Certificado cancelado com sucesso!';
} else {
    $_SESSION['error'] = 'Erro ao cancelar certificado: ' . $stmt->error;
}

$stmt->close();
$conn->close();

$redirect_url = $is_admin ? APP_URL . '/admin/certificados-admin.php' : APP_URL . '/parceiro/certificados-parceiro.php';
redirect($redirect_url);

