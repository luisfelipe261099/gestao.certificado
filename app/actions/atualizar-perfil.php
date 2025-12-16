<?php
/**
 * Atualizar Perfil - Sistema de Certificados
 * Ação para atualizar dados do perfil do admin
 */

require_once __DIR__ . '/bootstrap.php';

// Verificar autenticação
if (!isAuthenticated()) {
    http_response_code(403);
    die('Acesso negado');
}

// Verificar método POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    die('Método não permitido');
}

// Validar dados
$nome = sanitize($_POST['nome'] ?? '');
$email = sanitize($_POST['email'] ?? '');
$telefone = sanitize($_POST['telefone'] ?? '');

$errors = [];

if (empty($nome)) {
    $errors[] = 'Nome é obrigatório';
}

if (empty($email) || !isValidEmail($email)) {
    $errors[] = 'Email inválido';
}

// Se houver erros, redirecionar com mensagem
if (!empty($errors)) {
    $_SESSION['error'] = implode(', ', $errors);
    $user = getCurrentUser();
    if ($user['tipo_usuario'] === ROLE_ADMIN) {
        redirect(APP_URL . '/admin/perfil-admin.php');
    } else {
        redirect(APP_URL . '/parceiro/perfil-parceiro.php');
    }
    exit;
}

try {
    $conn = getDBConnection();
    $user = getCurrentUser();
    $user_id = $user['id'];
    
    // Atualizar dados do usuário
    $stmt = $conn->prepare("
        UPDATE usuarios 
        SET nome = ?, email = ?, data_atualizacao = NOW()
        WHERE id = ?
    ");
    
    if ($stmt) {
        $stmt->bind_param("ssi", $nome, $email, $user_id);
        
        if ($stmt->execute()) {
            $_SESSION['success'] = 'Perfil atualizado com sucesso!';
            // Atualizar dados da sessão
            $_SESSION['user_name'] = $nome;
            $_SESSION['user_email'] = $email;
        } else {
            $_SESSION['error'] = 'Erro ao atualizar perfil';
        }
        $stmt->close();
    }
    
    $conn->close();
    
} catch (Exception $e) {
    error_log("Erro ao atualizar perfil: " . $e->getMessage());
    $_SESSION['error'] = 'Erro ao atualizar perfil: ' . $e->getMessage();
}

// Redirecionar para a página apropriada
$user = getCurrentUser();
if ($user['tipo_usuario'] === ROLE_ADMIN) {
    redirect(APP_URL . '/admin/perfil-admin.php');
} else {
    redirect(APP_URL . '/parceiro/perfil-parceiro.php');
}
?>

