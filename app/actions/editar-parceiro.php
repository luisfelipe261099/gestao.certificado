<?php
/**
 * Editar Parceiro - Sistema de Certificados
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
$id = isset($_POST['id']) ? intval($_POST['id']) : 0;
$nome_empresa = isset($_POST['nome_empresa']) ? trim($_POST['nome_empresa']) : '';
$cnpj = isset($_POST['cnpj']) ? trim($_POST['cnpj']) : '';
$email = isset($_POST['email']) ? trim($_POST['email']) : '';
$telefone = isset($_POST['telefone']) ? trim($_POST['telefone']) : '';
$endereco = isset($_POST['endereco']) ? trim($_POST['endereco']) : '';
$cidade = isset($_POST['cidade']) ? trim($_POST['cidade']) : '';
$estado = isset($_POST['estado']) ? trim($_POST['estado']) : '';
$cep = isset($_POST['cep']) ? trim($_POST['cep']) : '';
$nova_senha = isset($_POST['nova_senha']) ? trim($_POST['nova_senha']) : '';
$confirmar_senha = isset($_POST['confirmar_senha']) ? trim($_POST['confirmar_senha']) : '';

// Validações
if (empty($id) || empty($nome_empresa) || empty($cnpj) || empty($email)) {
    $_SESSION['error'] = 'Preencha todos os campos obrigatórios.';
    redirect(APP_URL . '/admin/parceiros-admin.php');
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $_SESSION['error'] = 'Email inválido.';
    redirect(APP_URL . '/admin/parceiros-admin.php');
}

// Validar senha se foi fornecida
$alterar_senha = false;
if (!empty($nova_senha) || !empty($confirmar_senha)) {
    if ($nova_senha !== $confirmar_senha) {
        $_SESSION['error'] = 'As senhas não coincidem.';
        redirect(APP_URL . '/admin/parceiros-admin.php');
    }

    if (strlen($nova_senha) < 6) {
        $_SESSION['error'] = 'A senha deve ter pelo menos 6 caracteres.';
        redirect(APP_URL . '/admin/parceiros-admin.php');
    }

    $alterar_senha = true;
}

$conn = getDBConnection();

try {
    // Verificar se parceiro existe
    $stmt = $conn->prepare("SELECT id FROM parceiros WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        $_SESSION['error'] = 'Parceiro não encontrado.';
        redirect(APP_URL . '/admin/parceiros-admin.php');
    }
    
    $stmt->close();
    
    // Verificar se email já existe (para outro parceiro)
    $stmt = $conn->prepare("SELECT id FROM parceiros WHERE email = ? AND id != ?");
    $stmt->bind_param("si", $email, $id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $_SESSION['error'] = 'Este email já está cadastrado para outro parceiro.';
        redirect(APP_URL . '/admin/parceiros-admin.php');
    }
    
    $stmt->close();

    // Atualizar parceiro
    $stmt = $conn->prepare("
        UPDATE parceiros
        SET nome_empresa = ?, cnpj = ?, email = ?, telefone = ?,
            endereco = ?, cidade = ?, estado = ?, cep = ?
        WHERE id = ?
    ");

    $stmt->bind_param(
        "ssssssssi",
        $nome_empresa, $cnpj, $email, $telefone,
        $endereco, $cidade, $estado, $cep, $id
    );

    if ($stmt->execute()) {
        $mensagem_sucesso = 'Parceiro atualizado com sucesso!';

        // Se deve alterar a senha, atualizar na tabela usuarios
        if ($alterar_senha) {
            $senha_hash = password_hash($nova_senha, PASSWORD_DEFAULT);

            // Buscar o usuario_id do parceiro
            $stmt_user = $conn->prepare("SELECT id FROM usuarios WHERE parceiro_id = ? LIMIT 1");
            $stmt_user->bind_param("i", $id);
            $stmt_user->execute();
            $result_user = $stmt_user->get_result();

            if ($result_user->num_rows > 0) {
                $user_row = $result_user->fetch_assoc();
                $usuario_id = $user_row['id'];

                // Atualizar senha do usuário
                $stmt_senha = $conn->prepare("UPDATE usuarios SET senha = ? WHERE id = ?");
                $stmt_senha->bind_param("si", $senha_hash, $usuario_id);

                if ($stmt_senha->execute()) {
                    $mensagem_sucesso .= ' Senha alterada com sucesso!';
                } else {
                    $mensagem_sucesso .= ' Mas houve erro ao alterar a senha.';
                }

                $stmt_senha->close();
            }

            $stmt_user->close();
        }

        $_SESSION['success'] = $mensagem_sucesso;
    } else {
        $_SESSION['error'] = 'Erro ao atualizar parceiro: ' . $conn->error;
    }

    $stmt->close();
    
} catch (Exception $e) {
    $_SESSION['error'] = 'Erro ao processar: ' . $e->getMessage();
}

$conn->close();
redirect(APP_URL . '/admin/parceiros-admin.php');
?>

