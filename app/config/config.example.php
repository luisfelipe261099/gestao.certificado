<?php
/**
 * ============================================================================
 * ARQUIVO DE CONFIGURAÇÃO DO SISTEMA DE CERTIFICADOS
 * ============================================================================
 * 
 * INSTRUÇÕES:
 * 1. Copie este arquivo para config.php
 * 2. Preencha as credenciais do seu ambiente
 * 3. Nunca commit o arquivo config.php no Git
 * 
 * ============================================================================
 */

// Iniciar sessão
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

// ============================================================================
// CONFIGURAÇÕES DO BANCO DE DADOS
// ============================================================================
// Preencha com suas credenciais de banco de dados

define('DB_HOST', 'localhost');
define('DB_USER', 'seu_usuario');       // Altere para seu usuário
define('DB_PASS', 'sua_senha');         // Altere para sua senha
define('DB_NAME', 'nome_do_banco');     // Altere para o nome do banco

// ============================================================================
// CONFIGURAÇÕES DA APLICAÇÃO
// ============================================================================

// Detectar ambiente (local vs produção)
$is_localhost = (
    php_sapi_name() === 'cli' ||
    strpos($_SERVER['HTTP_HOST'] ?? '', 'localhost') !== false ||
    strpos($_SERVER['HTTP_HOST'] ?? '', '127.0.0.1') !== false
);

$app_base_path = '/gestao.certificado';  // Ajuste conforme necessário
$app_protocol = $is_localhost ? 'http' : 'https';

define('APP_NAME', 'Sistema de Certificados');
define('APP_VERSION', '1.0.0');
define('APP_PROTOCOL', $app_protocol);
define('APP_BASE_PATH', $app_base_path);
define('APP_URL', APP_PROTOCOL . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost') . APP_BASE_PATH);

// ============================================================================
// DIRETÓRIOS
// ============================================================================

define('ROOT_DIR', dirname(__DIR__, 2));
define('APP_DIR', dirname(__DIR__));
define('CONFIG_DIR', __DIR__);
define('DIR_CSS', APP_URL . '/vendor/sbadmin2/css');
define('DIR_JS', APP_URL . '/vendor/sbadmin2/js');
define('DIR_VENDOR', APP_URL . '/vendor/sbadmin2/vendor');
define('DIR_ASSETS', APP_URL . '/assets');
define('DIR_UPLOADS', APP_URL . '/uploads');
define('FPDF_FONTPATH', ROOT_DIR . '/vendor/setasign/fpdf/font/');

// ============================================================================
// ROLES DE USUÁRIO
// ============================================================================

define('ROLE_ADMIN', 'admin');
define('ROLE_PARCEIRO', 'parceiro');
define('ROLE_ALUNO', 'aluno');

// ============================================================================
// FUNÇÕES AUXILIARES
// ============================================================================

/**
 * Conexão com o banco de dados
 */
function getDBConnection()
{
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    if ($conn->connect_error) {
        die("Erro de conexão: " . $conn->connect_error);
    }
    $conn->set_charset("utf8mb4");
    return $conn;
}

/**
 * Verifica se o usuário está autenticado
 */
function isAuthenticated()
{
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

/**
 * Verifica se o usuário tem uma role específica
 */
function hasRole($role)
{
    return isset($_SESSION['user_role']) && $_SESSION['user_role'] === $role;
}

/**
 * Obtém dados do usuário atual
 */
function getCurrentUser()
{
    if (!isAuthenticated()) {
        return null;
    }
    return [
        'id' => $_SESSION['user_id'] ?? null,
        'email' => $_SESSION['user_email'] ?? null,
        'nome' => $_SESSION['user_nome'] ?? null,
        'role' => $_SESSION['user_role'] ?? null,
        'parceiro_id' => $_SESSION['parceiro_id'] ?? null
    ];
}

/**
 * Redireciona para uma URL
 */
function redirect($url)
{
    header("Location: $url");
    exit;
}
?>