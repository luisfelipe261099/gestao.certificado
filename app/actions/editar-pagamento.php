<?php
/**
 * Editar Pagamento - Sistema de Certificados
 */

require_once __DIR__ . '/bootstrap.php';

if (!isAuthenticated() || !hasRole(ROLE_ADMIN)) {
    $_SESSION['error'] = 'Acesso negado';
    redirect(APP_URL . '/login.php');
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $_SESSION['error'] = 'Método não permitido';
    redirect(APP_URL . '/admin/pagamentos-admin.php');
}

$conn = getDBConnection();

$id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
$descricao = isset($_POST['descricao']) ? trim($_POST['descricao']) : '';
$valor = isset($_POST['valor']) ? (float)$_POST['valor'] : 0;
$status = isset($_POST['status']) ? trim($_POST['status']) : '';

if (empty($id) || empty($descricao) || empty($valor) || empty($status)) {
    $_SESSION['error'] = 'Preencha todos os campos obrigatórios';
    redirect(APP_URL . '/admin/pagamentos-admin.php');
}

// Verificar se pagamento existe
$stmt = $conn->prepare("SELECT id FROM pagamentos WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
if ($stmt->get_result()->num_rows === 0) {
    $_SESSION['error'] = 'Pagamento não encontrado';
    redirect(APP_URL . '/admin/pagamentos-admin.php');
}

// Atualizar pagamento
$stmt = $conn->prepare("
    UPDATE pagamentos 
    SET descricao = ?, valor = ?, status = ?, atualizado_em = NOW()
    WHERE id = ?
");
$stmt->bind_param("sdsi", $descricao, $valor, $status, $id);

if ($stmt->execute()) {
    $_SESSION['success'] = 'Pagamento atualizado com sucesso!';
} else {
    $_SESSION['error'] = 'Erro ao atualizar pagamento: ' . $conn->error;
}

$conn->close();
redirect(APP_URL . '/admin/pagamentos-admin.php');
?>

