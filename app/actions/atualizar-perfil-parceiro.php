<?php
/**
 * Atualizar Perfil Parceiro - Sistema de Certificados
 * Ação para atualizar dados do perfil do parceiro
 */

require_once __DIR__ . '/bootstrap.php';

// Verificar autenticação e permissão
if (!isAuthenticated() || !hasRole(ROLE_PARCEIRO)) {
    http_response_code(403);
    die('Acesso negado');
}

// Verificar método POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    die('Método não permitido');
}

// Validar dados
$nome_empresa = sanitize($_POST['nome_empresa'] ?? '');
$cnpj = sanitize($_POST['cnpj'] ?? '');
$telefone = sanitize($_POST['telefone'] ?? '');
$endereco = sanitize($_POST['endereco'] ?? '');
$cidade = sanitize($_POST['cidade'] ?? '');
$estado = sanitize($_POST['estado'] ?? '');
$cep = sanitize($_POST['cep'] ?? '');
$website = sanitize($_POST['website'] ?? '');

$errors = [];

if (empty($nome_empresa)) {
    $errors[] = 'Nome da empresa é obrigatório';
}

// Se houver erros, redirecionar com mensagem
if (!empty($errors)) {
    $_SESSION['error'] = implode(', ', $errors);
    redirect(APP_URL . '/parceiro/perfil-parceiro.php');
    exit;
}

try {
    $conn = getDBConnection();
    $user = getCurrentUser();
    $parceiro_id = $user['id'];

    // Atualizar dados do parceiro
    $stmt = $conn->prepare("
        UPDATE parceiros
        SET nome_empresa = ?, cnpj = ?, telefone = ?, endereco = ?, cidade = ?, estado = ?, cep = ?, website = ?, atualizado_em = NOW()
        WHERE id = ?
    ");

    if ($stmt) {
        $stmt->bind_param("ssssssssi", $nome_empresa, $cnpj, $telefone, $endereco, $cidade, $estado, $cep, $website, $parceiro_id);

        if ($stmt->execute()) {
            $_SESSION['success'] = 'Perfil atualizado com sucesso!';
        } else {
            $_SESSION['error'] = 'Erro ao atualizar perfil: ' . $stmt->error;
        }
        $stmt->close();
    }
    
    $conn->close();
    
} catch (Exception $e) {
    error_log("Erro ao atualizar perfil: " . $e->getMessage());
    $_SESSION['error'] = 'Erro ao atualizar perfil: ' . $e->getMessage();
}

redirect(APP_URL . '/parceiro/perfil-parceiro.php');
?>

