<?php
/**
 * Arquivo: acessar-ead.php
 * Responsável por gerar token e redirecionar para o EAD
 */

require_once __DIR__ . '/../app/config/config.php';
require_once __DIR__ . '/../app/classes/AutenticacaoIntegrada.php';

// Verifica se usuário está autenticado
if (!isAuthenticated() || !hasRole(ROLE_PARCEIRO)) {
    redirect(APP_URL . '/index.php');
}

$user = getCurrentUser();
$conn = getDBConnection();

// Verificar se o EAD está ativado para este parceiro
$stmt = $conn->prepare("SELECT ead_ativo FROM parceiros WHERE id = ?");
$stmt->bind_param("i", $user['parceiro_id']);
$stmt->execute();
$result = $stmt->get_result();
$parceiro = $result->fetch_assoc();
$stmt->close();

if (!$parceiro || $parceiro['ead_ativo'] != 1) {
    $_SESSION['error'] = 'O módulo EAD não está disponível para sua empresa. Entre em contato com o administrador.';
    redirect(APP_URL . '/parceiro/dashboard-parceiro.php');
}

// Cria instância de autenticação
$auth = new AutenticacaoIntegrada($conn);

// Gera token JWT
$token = $auth->gerarTokenEAD($user['id'], $user['parceiro_id']);

// Redireciona para o EAD com o token
$ead_url = APP_URL . '/ead/parceiro/login-token.php?token=' . urlencode($token);
redirect($ead_url);

$conn->close();
?>

