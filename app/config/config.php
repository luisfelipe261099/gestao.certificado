<?php
/**
 * ============================================================================
 * ARQUIVO DE CONFIGURAÇÃO DO SISTEMA DE CERTIFICADOS
 * ============================================================================
 *
 * Este arquivo contém todas as configurações principais do sistema.
 * É como o "coração" da aplicação - aqui definimos como tudo funciona.
 *
 * Padrão MVP - Camada de Configuração
 * MVP significa: Model (dados), View (o que o usuário vê), Presenter (lógica)
 * ============================================================================
 */

// ============================================================================
// PASSO 1: INICIAR A SESSÃO
// ============================================================================
// Uma "sessão" é como um arquivo temporário que guarda informações do usuário
// enquanto ele está usando o sistema. Por exemplo: qual usuário está logado.
// IMPORTANTE: Isso DEVE ser feito ANTES de qualquer output (antes de qualquer HTML)
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

// ============================================================================
// PASSO 2: CONFIGURAÇÕES DO BANCO DE DADOS
// ============================================================================
// O banco de dados é como um grande arquivo onde guardamos todas as informações
// (parceiros, planos, certificados, etc.)
//
// DB_HOST: Onde o banco de dados está (localhost = no mesmo computador)
// DB_USER: Usuário para acessar o banco
// DB_PASS: Senha do usuário
// DB_NAME: Nome do banco de dados que queremos usar

// Detectar se é localhost ou produção para usar credenciais corretas
$is_localhost = (
    php_sapi_name() === 'cli' ||
    strpos($_SERVER['HTTP_HOST'] ?? '', 'localhost') !== false ||
    strpos($_SERVER['HTTP_HOST'] ?? '', '127.0.0.1') !== false ||
    stripos(__DIR__, 'xampp') !== false // Fallback robusto para ambiente XAMPP
);

if ($is_localhost) {
    // LOCAL: Usar credenciais de desenvolvimento
    define('DB_HOST', 'localhost');
    define('DB_USER', 'root');
    define('DB_PASS', '');
    define('DB_NAME', 'sistema_parceiro');
} else {
    // PRODUÇÃO: Configure suas credenciais aqui ou use variáveis de ambiente
    define('DB_HOST', getenv('DB_HOST') ?: 'localhost');
    define('DB_USER', getenv('DB_USER') ?: 'seu_usuario');
    define('DB_PASS', getenv('DB_PASS') ?: 'sua_senha');
    define('DB_NAME', getenv('DB_NAME') ?: 'seu_banco');
}

// ============================================================================
// PASSO 3: DEFINIR CAMINHO BASE E PROTOCOLO DA APLICAÇÃO
// ============================================================================
// Define o caminho base da aplicação (já detectamos $is_localhost acima)
if ($is_localhost) {
    // LOCAL: http://localhost/gestao.certificado
    $app_base_path = '/gestao.certificado';
    $app_protocol = 'http';
} else {
    // PRODUÇÃO: https://app.faciencia.edu.br/gestao.certificado
    $app_base_path = '/gestao.certificado';
    $app_protocol = 'https';
}

// ============================================================================
// PASSO 4: CONFIGURAÇÕES DA APLICAÇÃO
// ============================================================================
// Informações gerais sobre o sistema
define('APP_NAME', 'Sistema de Certificados');  // Nome do sistema
define('APP_VERSION', '1.0.0');                 // Versão do sistema
define('APP_PROTOCOL', $app_protocol);          // Protocolo (http ou https)
define('APP_BASE_PATH', $app_base_path);        // Caminho base (/gestao.certificado)
define('APP_URL', APP_PROTOCOL . '://' . $_SERVER['HTTP_HOST'] . APP_BASE_PATH); // URL base completa

// ============================================================================
// PASSO 5: CONFIGURAÇÕES DE SESSÃO
// ============================================================================
// Sessão = informações temporárias do usuário enquanto ele está logado
define('SESSION_TIMEOUT', 3600);  // Tempo máximo de sessão: 3600 segundos = 1 hora
define('SESSION_NAME', 'sistema_parceiro_murilo'); // Nome da sessão

// ============================================================================
// PASSO 6: TIPOS DE USUÁRIOS (ROLES)
// ============================================================================
// Existem dois tipos de usuários no sistema:
// 1. ADMIN: Administrador - pode gerenciar tudo (parceiros, planos, etc.)
// 2. PARCEIRO: Parceiro - pode gerenciar seus próprios dados
define('ROLE_ADMIN', 'admin');       // Tipo de usuário: Administrador
define('ROLE_PARCEIRO', 'parceiro'); // Tipo de usuário: Parceiro

// ============================================================================
// PASSO 7: DEFINIÇÃO DOS DIRETÓRIOS
// ============================================================================
// Diretórios = pastas do sistema. Aqui definimos os caminhos para cada pasta
// Isso facilita quando precisamos incluir arquivos ou acessar recursos
define('DIR_ADMIN', APP_URL . '/admin');           // Pasta do admin
define('DIR_PARCEIRO', APP_URL . '/parceiro');     // Pasta do parceiro
define('DIR_ASSETS', APP_URL . '/vendor');         // Pasta de recursos (CSS, JS, imagens)
define('DIR_VENDOR', APP_URL . '/vendor');         // Pasta de bibliotecas externas
define('DIR_CSS', APP_URL . '/ead/css');           // Pasta de estilos CSS
define('DIR_JS', APP_URL . '/ead/js');             // Pasta de scripts JavaScript
define('DIR_IMG', APP_URL . '/ead/img');           // Pasta de imagens

// ============================================================================
// FUNÇÕES AUXILIARES DO SISTEMA
// ============================================================================
// Funções são como "receitas" - você escreve uma vez e usa várias vezes

/**
 * FUNÇÃO: getDBConnection()
 *
 * O que faz: Conecta ao banco de dados
 *
 * Como funciona:
 * 1. Tenta criar uma conexão com o banco usando as configurações acima
 * 2. Se conseguir, retorna a conexão
 * 3. Se não conseguir, mostra um erro e para o programa
 *
 * Retorna: Uma conexão com o banco de dados (ou para o programa se falhar)
 */
function getDBConnection()
{
    try {
        // mysqli = biblioteca do PHP para conectar ao MySQL
        // Passamos: host, usuário, senha, nome do banco
        $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

        // Verifica se houve erro na conexão
        if ($conn->connect_error) {
            throw new Exception("Erro de conexão: " . $conn->connect_error);
        }

        // Define o tipo de caracteres como UTF-8 (suporta acentos, etc.)
        $conn->set_charset("utf8mb4");
        return $conn;
    } catch (Exception $e) {
        // Se algo der errado, mostra a mensagem de erro e para
        die("Erro ao conectar ao banco de dados: " . $e->getMessage());
    }
}

/**
 * FUNÇÃO: isAuthenticated()
 *
 * O que faz: Verifica se o usuário está logado
 *
 * Como funciona:
 * Verifica se existem informações do usuário na sessão
 * Se existem user_id e user_role, significa que o usuário está logado
 *
 * Retorna: true (sim, está logado) ou false (não, não está logado)
 */
function isAuthenticated()
{
    return isset($_SESSION['user_id']) && isset($_SESSION['user_role']);
}

/**
 * FUNÇÃO: getCurrentUser()
 *
 * O que faz: Pega as informações do usuário que está logado
 *
 * Como funciona:
 * 1. Verifica se o usuário está autenticado
 * 2. Se sim, coleta todas as informações dele da sessão
 * 3. Se for parceiro, também pega o ID do parceiro
 * 4. Retorna um array com todas as informações
 *
 * Retorna: Array com dados do usuário ou null se não estiver logado
 */
function getCurrentUser()
{
    if (isAuthenticated()) {
        // Cria um array com as informações do usuário
        $user = [
            'id' => $_SESSION['user_id'],           // ID do usuário
            'email' => $_SESSION['user_email'],     // Email do usuário
            'role' => $_SESSION['user_role'],       // Tipo (admin ou parceiro)
            'name' => $_SESSION['user_name'] ?? 'Usuário' // Nome (ou "Usuário" se não tiver)
        ];

        // Se for parceiro, adiciona o ID do parceiro
        if ($_SESSION['user_role'] === ROLE_PARCEIRO && isset($_SESSION['parceiro_id'])) {
            $user['parceiro_id'] = $_SESSION['parceiro_id'];
        }

        return $user;
    }
    return null; // Retorna null se não estiver logado
}

/**
 * FUNÇÃO: redirect($url)
 *
 * O que faz: Redireciona o usuário para outra página
 *
 * Como funciona:
 * Usa o comando "header" do PHP para enviar o navegador para outra URL
 * Depois usa "exit()" para parar de executar o código
 *
 * Parâmetro: $url = endereço para onde redirecionar
 */
function redirect($url)
{
    header("Location: " . $url);
    exit(); // Para a execução do código
}

/**
 * FUNÇÃO: hasRole($role)
 *
 * O que faz: Verifica se o usuário tem um tipo específico (admin ou parceiro)
 *
 * Como funciona:
 * 1. Verifica se o usuário está autenticado
 * 2. Compara o tipo do usuário com o tipo que estamos procurando
 * 3. Retorna true se for igual, false se for diferente
 *
 * Parâmetro: $role = tipo que queremos verificar (ROLE_ADMIN ou ROLE_PARCEIRO)
 * Retorna: true ou false
 */
function hasRole($role)
{
    if (!isAuthenticated()) {
        return false; // Se não está logado, não tem nenhum role
    }
    return $_SESSION['user_role'] === $role; // Compara o tipo
}

/**
 * FUNÇÃO: logout()
 *
 * O que faz: Desconecta o usuário (faz logout)
 *
 * Como funciona:
 * 1. Destroi a sessão (apaga todas as informações do usuário)
 * 2. Redireciona para a página inicial
 */
function logout()
{
    session_destroy(); // Apaga a sessão
    redirect(APP_URL . '/index.php'); // Vai para a página inicial
}

/**
 * FUNÇÃO: sanitize($input)
 *
 * O que faz: Limpa dados que vêm do usuário para evitar problemas de segurança
 *
 * Como funciona:
 * 1. Remove espaços em branco no início e fim (trim)
 * 2. Converte caracteres especiais em código HTML (htmlspecialchars)
 * Isso evita que alguém injete código malicioso
 *
 * Parâmetro: $input = texto que queremos limpar
 * Retorna: Texto limpo e seguro
 */
function sanitize($input)
{
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

/**
 * FUNÇÃO: isValidEmail($email)
 *
 * O que faz: Verifica se um email é válido
 *
 * Como funciona:
 * Usa a função filter_var do PHP com FILTER_VALIDATE_EMAIL
 * Isso verifica se o email tem o formato correto (algo@algo.com)
 *
 * Parâmetro: $email = email que queremos validar
 * Retorna: true se for válido, false se não for
 */
function isValidEmail($email)
{
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

// ============================================================================
// CONFIGURAÇÃO DE TRATAMENTO DE ERROS
// ============================================================================
// Isso configura como o PHP deve lidar com erros

error_reporting(E_ALL);  // Reporta TODOS os erros
ini_set('display_errors', 0); // NÃO mostra erros na tela (por segurança)
ini_set('log_errors', 1); // Registra erros em um arquivo
ini_set('error_log', __DIR__ . '/../../logs/error.log'); // Arquivo onde guardar os erros