<?php
/**
 * Editar Fatura - Sistema de Certificados
 */

require_once __DIR__ . '/bootstrap.php';

if (!isAuthenticated() || !hasRole(ROLE_ADMIN)) {
    $_SESSION['error'] = 'Acesso negado';
    redirect(APP_URL . '/login.php');
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $_SESSION['error'] = 'Método não permitido';
    redirect(APP_URL . '/admin/faturas-admin.php');
}

$conn = getDBConnection();

$id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
$numero_fatura = isset($_POST['numero_fatura']) ? trim($_POST['numero_fatura']) : '';
$valor = isset($_POST['valor']) ? (float)$_POST['valor'] : 0;
$status = isset($_POST['status']) ? trim($_POST['status']) : '';

if (empty($id) || empty($numero_fatura) || empty($valor) || empty($status)) {
    $_SESSION['error'] = 'Preencha todos os campos obrigatórios';
    redirect(APP_URL . '/admin/faturas-admin.php');
}

// Verificar se fatura existe
$stmt = $conn->prepare("SELECT id FROM faturas WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
if ($stmt->get_result()->num_rows === 0) {
    $_SESSION['error'] = 'Fatura não encontrada';
    redirect(APP_URL . '/admin/faturas-admin.php');
}

// Atualizar fatura
$stmt = $conn->prepare("
    UPDATE faturas 
    SET numero_fatura = ?, valor = ?, status = ?, atualizado_em = NOW()
    WHERE id = ?
");
$stmt->bind_param("sdsi", $numero_fatura, $valor, $status, $id);

if ($stmt->execute()) {
    $_SESSION['success'] = 'Fatura atualizada com sucesso!';
} else {
    $_SESSION['error'] = 'Erro ao atualizar fatura: ' . $conn->error;
}

$conn->close();
redirect(APP_URL . '/admin/faturas-admin.php');
?>

