<?php
/**
 * Configuração do Banco de Dados
 * Sistema EAD Pro
 */

// ============================================================================
// DETECÇÃO AUTOMÁTICA DO AMBIENTE (LOCAL vs PRODUÇÃO)
// ============================================================================
$http_host = $_SERVER['HTTP_HOST'] ?? 'localhost';
$is_localhost = (
    php_sapi_name() === 'cli' ||
    strpos($http_host, 'localhost') !== false ||
    strpos($http_host, '127.0.0.1') !== false ||
    stripos(__DIR__, 'xampp') !== false // Fallback robusto para ambiente XAMPP
);

if ($is_localhost) {
    // LOCAL: Usar credenciais de desenvolvimento
    define('DB_HOST', 'localhost');
    define('DB_USER', 'root');
    define('DB_PASS', '');
    define('DB_NAME', 'sistema_parceiro_murilo');

    $app_base_path = '/gestao.certificado';
    $app_protocol = 'http';
} else {
    // PRODUÇÃO: Usar credenciais de produção
    define('DB_HOST', 'localhost');
    define('DB_USER', 'sistema_parceiro_murilo');
    define('DB_PASS', 'T3cn0l0g1a@');
    define('DB_NAME', 'sistema_parceiro_murilo');

    $app_base_path = '/gestao.certificado';
    $app_protocol = 'https';
}

// Configurações da Aplicação
define('APP_NAME', 'EAD Pro');
define('APP_URL', $app_protocol . '://' . $http_host . $app_base_path);
define('APP_PATH', dirname(dirname(__FILE__)));

// Configurações de Segurança
define('JWT_SECRET', 'sua_chave_secreta_super_segura_aqui_2024');
define('SESSION_TIMEOUT', 3600); // 1 hora em segundos
define('PASSWORD_HASH_ALGO', PASSWORD_BCRYPT);

// Configurações de Email
define('MAIL_HOST', 'smtp.gmail.com');
define('MAIL_PORT', 587);
define('MAIL_USER', 'seu_email@gmail.com');
define('MAIL_PASS', 'sua_senha_app');
define('MAIL_FROM', 'noreply@eadpro.com');

// Configurações de Upload
define('UPLOAD_PATH', APP_PATH . '/uploads');
define('MAX_UPLOAD_SIZE', 50 * 1024 * 1024); // 50MB
define('ALLOWED_EXTENSIONS', ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'zip', 'rar']);
define('ALLOWED_VIDEO_EXTENSIONS', ['mp4', 'avi', 'mov', 'mkv', 'webm']);
define('ALLOWED_IMAGE_EXTENSIONS', ['jpg', 'jpeg', 'png', 'gif', 'webp']);

// Configurações de Paginação
define('ITEMS_PER_PAGE', 10);

// Timezone
date_default_timezone_set('America/Sao_Paulo');

// Conexão com o Banco de Dados
try {
    $pdo = new PDO(
        'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4',
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]
    );
} catch (PDOException $e) {
    die('Erro ao conectar ao banco de dados: ' . $e->getMessage());
}

// Função para iniciar sessão segura
function iniciar_sessao()
{
    if (session_status() === PHP_SESSION_NONE) {
        session_start();

        // Verificar timeout da sessão
        if (isset($_SESSION['ultimo_acesso'])) {
            if (time() - $_SESSION['ultimo_acesso'] > SESSION_TIMEOUT) {
                session_destroy();
                header('Location: ' . APP_URL . '/login.php?timeout=1');
                exit;
            }
        }

        $_SESSION['ultimo_acesso'] = time();
    }
}

// Função para verificar se usuário está autenticado
function verificar_autenticacao($tipo = 'parceiro')
{
    iniciar_sessao();

    // Verifica autenticação integrada (com prefixo 'ead_')
    if (!isset($_SESSION['ead_autenticado']) || $_SESSION['ead_autenticado'] !== true) {
        header('Location: ' . APP_URL . '/ead/parceiro/login.php');
        exit;
    }
}

// Função para sanitizar entrada
function sanitizar($dados)
{
    if (is_array($dados)) {
        return array_map('sanitizar', $dados);
    }
    return htmlspecialchars(trim($dados), ENT_QUOTES, 'UTF-8');
}

// Função para validar email
function validar_email($email)
{
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

// Função para gerar token
function gerar_token($tamanho = 32)
{
    return bin2hex(random_bytes($tamanho));
}

// Função para hash de senha
function hash_senha($senha)
{
    return password_hash($senha, PASSWORD_BCRYPT, ['cost' => 12]);
}

// Função para verificar senha
function verificar_senha($senha, $hash)
{
    return password_verify($senha, $hash);
}

// Função para log de erros
function log_erro($mensagem, $arquivo = null)
{
    $log_dir = APP_PATH . '/logs';
    if (!is_dir($log_dir)) {
        mkdir($log_dir, 0755, true);
    }

    $log_file = $log_dir . '/erro_' . date('Y-m-d') . '.log';
    $timestamp = date('Y-m-d H:i:s');
    $mensagem_log = "[$timestamp] $mensagem";

    if ($arquivo) {
        $mensagem_log .= " (Arquivo: $arquivo)";
    }

    file_put_contents($log_file, $mensagem_log . "\n", FILE_APPEND);
}

// Função para resposta JSON
function resposta_json($sucesso, $mensagem, $dados = null, $codigo = 200)
{
    header('Content-Type: application/json; charset=utf-8');
    http_response_code($codigo);

    $resposta = [
        'sucesso' => $sucesso,
        'mensagem' => $mensagem
    ];

    if ($dados !== null) {
        $resposta['dados'] = $dados;
    }

    echo json_encode($resposta, JSON_UNESCAPED_UNICODE);
    exit;
}

?>