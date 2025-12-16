<?php
/**
 * Editar Receita - Sistema de Certificados
 */

require_once __DIR__ . '/bootstrap.php';

if (!isAuthenticated() || !hasRole(ROLE_ADMIN)) {
    $_SESSION['error'] = 'Acesso negado';
    redirect(APP_URL . '/login.php');
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $_SESSION['error'] = 'Método não permitido';
    redirect(APP_URL . '/admin/receitas-admin.php');
}

$conn = getDBConnection();

$id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
$tipo = isset($_POST['tipo']) ? trim($_POST['tipo']) : '';
$valor = isset($_POST['valor']) ? (float)$_POST['valor'] : 0;
$status = isset($_POST['status']) ? trim($_POST['status']) : '';

if (empty($id) || empty($tipo) || empty($valor) || empty($status)) {
    $_SESSION['error'] = 'Preencha todos os campos obrigatórios';
    redirect(APP_URL . '/admin/receitas-admin.php');
}

// Verificar se receita existe
$stmt = $conn->prepare("SELECT id FROM receitas WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
if ($stmt->get_result()->num_rows === 0) {
    $_SESSION['error'] = 'Receita não encontrada';
    redirect(APP_URL . '/admin/receitas-admin.php');
}

// Atualizar receita
$stmt = $conn->prepare("
    UPDATE receitas 
    SET tipo = ?, valor = ?, status = ?, atualizado_em = NOW()
    WHERE id = ?
");
$stmt->bind_param("sdsi", $tipo, $valor, $status, $id);

if ($stmt->execute()) {
    $_SESSION['success'] = 'Receita atualizada com sucesso!';
} else {
    $_SESSION['error'] = 'Erro ao atualizar receita: ' . $conn->error;
}

$conn->close();
redirect(APP_URL . '/admin/receitas-admin.php');
?>

