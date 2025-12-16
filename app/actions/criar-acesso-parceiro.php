<?php
/**
 * Criar Acesso Parceiro - Sistema de Certificados
 */

require_once __DIR__ . '/bootstrap.php';

// Verificar autenticação e permissão
if (!isAuthenticated() || !hasRole(ROLE_ADMIN)) {
    redirect(APP_URL . '/login.php');
}

// Verificar método POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect(APP_URL . '/admin/parceiros-admin.php');
}

// Validar dados
$parceiro_id = isset($_POST['parceiro_id']) ? intval($_POST['parceiro_id']) : 0;
$email = isset($_POST['email']) ? trim($_POST['email']) : '';
$senha = isset($_POST['senha']) ? trim($_POST['senha']) : '';
$nome = isset($_POST['nome']) ? trim($_POST['nome']) : '';

// Validações
if (empty($parceiro_id) || empty($email) || empty($nome)) {
    $_SESSION['error'] = 'Preencha todos os campos obrigatórios.';
    redirect(APP_URL . '/admin/parceiros-admin.php');
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $_SESSION['error'] = 'Email inválido.';
    redirect(APP_URL . '/admin/parceiros-admin.php');
}

// Gerar senha se não fornecida
if (empty($senha)) {
    $senha = bin2hex(random_bytes(6)); // Gera 12 caracteres
}

$conn = getDBConnection();

try {
    // Verificar se parceiro existe
    $stmt = $conn->prepare("SELECT id FROM parceiros WHERE id = ?");
    $stmt->bind_param("i", $parceiro_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        $_SESSION['error'] = 'Parceiro não encontrado.';
        redirect(APP_URL . '/admin/parceiros-admin.php');
    }
    
    $stmt->close();
    
    // Verificar se usuário já existe
    $stmt = $conn->prepare("SELECT id FROM usuarios_parceiro WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $_SESSION['error'] = 'Este email já possui acesso ao sistema.';
        redirect(APP_URL . '/admin/parceiros-admin.php');
    }
    
    $stmt->close();
    
    // Hash da senha
    $senha_hash = password_hash($senha, PASSWORD_DEFAULT);
    $ativo = 1;
    $criado_em = date('Y-m-d H:i:s');
    
    // Criar usuário parceiro
    $stmt = $conn->prepare("
        INSERT INTO usuarios_parceiro (parceiro_id, email, nome, senha_hash, ativo, criado_em)
        VALUES (?, ?, ?, ?, ?, ?)
    ");
    
    $stmt->bind_param("isssss", $parceiro_id, $email, $nome, $senha_hash, $ativo, $criado_em);
    
    if ($stmt->execute()) {
        $_SESSION['success'] = "Acesso criado com sucesso! Email: $email | Senha: $senha";
    } else {
        $_SESSION['error'] = 'Erro ao criar acesso: ' . $conn->error;
    }
    
    $stmt->close();
    
} catch (Exception $e) {
    $_SESSION['error'] = 'Erro ao processar: ' . $e->getMessage();
}

$conn->close();
redirect(APP_URL . '/admin/parceiros-admin.php');
?>

