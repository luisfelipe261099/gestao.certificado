<?php
/**
 * Arquivo: login-token.php
 * Responsável por validar token JWT e criar sessão no EAD
 */

// Iniciar sessão ANTES de qualquer coisa
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../../app/config/config.php';
require_once __DIR__ . '/../../app/classes/AutenticacaoIntegrada.php';

// Verifica se token foi fornecido
if (!isset($_GET['token']) || empty($_GET['token'])) {
    die('Token não fornecido');
}

$token = $_GET['token'];
$conn = getDBConnection();

// Cria instância de autenticação
$auth = new AutenticacaoIntegrada($conn);

// Valida token
$payload = $auth->validarTokenEAD($token);

if (!$payload) {
    die('Token inválido ou expirado');
}

// Cria sessão no EAD
if ($auth->criarSessaoEAD($payload)) {
    // Redireciona para dashboard do EAD
    redirect(APP_URL . '/ead/parceiro/dashboard.php');
} else {
    die('Erro ao criar sessão');
}

$conn->close();
?>

