<?php
/**
 * Ação: Criar Usuário Administrativo
 */

require_once '../config/config.php';

// Verificar autenticação e permissão
if (!isAuthenticated() || !hasRole(ROLE_ADMIN)) {
    redirect(APP_URL . '/login.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome = sanitize($_POST['nome'] ?? '');
    $email = sanitize($_POST['email'] ?? '');
    $senha = $_POST['senha'] ?? '';

    if (empty($nome) || empty($email) || empty($senha)) {
        $_SESSION['error'] = 'Todos os campos são obrigatórios.';
        redirect(APP_URL . '/admin/usuarios-admin.php');
    }

    if (strlen($senha) < 6) {
        $_SESSION['error'] = 'A senha deve ter pelo menos 6 caracteres.';
        redirect(APP_URL . '/admin/usuarios-admin.php');
    }

    $conn = getDBConnection();

    // Verificar se email já existe
    $stmt = $conn->prepare("SELECT id FROM administradores WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    if ($stmt->get_result()->num_rows > 0) {
        $_SESSION['error'] = 'Este email já está cadastrado.';
        $stmt->close();
        $conn->close();
        redirect(APP_URL . '/admin/usuarios-admin.php');
    }
    $stmt->close();

    // Inserir novo admin
    $senha_hash = password_hash($senha, PASSWORD_DEFAULT);
    $stmt = $conn->prepare("INSERT INTO administradores (nome, email, senha_hash, ativo, criado_em) VALUES (?, ?, ?, 1, NOW())");
    $stmt->bind_param("sss", $nome, $email, $senha_hash);

    if ($stmt->execute()) {
        $_SESSION['success'] = 'Administrador criado com sucesso!';
    } else {
        $_SESSION['error'] = 'Erro ao criar administrador: ' . $stmt->error;
    }

    $stmt->close();
    $conn->close();

    redirect(APP_URL . '/admin/usuarios-admin.php');
}
