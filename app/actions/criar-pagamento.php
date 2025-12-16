<?php
/**
 * Criar Pagamento - Sistema de Certificados
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

$parceiro_id = isset($_POST['parceiro_id']) ? (int)$_POST['parceiro_id'] : 0;
$descricao = isset($_POST['descricao']) ? trim($_POST['descricao']) : '';
$valor = isset($_POST['valor']) ? (float)$_POST['valor'] : 0;
$metodo = isset($_POST['metodo']) ? trim($_POST['metodo']) : '';
$data_pagamento = isset($_POST['data_pagamento']) ? trim($_POST['data_pagamento']) : '';

if (empty($parceiro_id) || empty($descricao) || empty($valor) || empty($metodo) || empty($data_pagamento)) {
    $_SESSION['error'] = 'Preencha todos os campos obrigatórios';
    redirect(APP_URL . '/admin/pagamentos-admin.php');
}

if ($valor <= 0) {
    $_SESSION['error'] = 'Valor deve ser maior que zero';
    redirect(APP_URL . '/admin/pagamentos-admin.php');
}

// Verificar se parceiro existe
$stmt = $conn->prepare("SELECT id FROM parceiros WHERE id = ? AND ativo = 1");
$stmt->bind_param("i", $parceiro_id);
$stmt->execute();
if ($stmt->get_result()->num_rows === 0) {
    $_SESSION['error'] = 'Parceiro não encontrado';
    redirect(APP_URL . '/admin/pagamentos-admin.php');
}

// Inserir pagamento
$status = 'pago';
$stmt = $conn->prepare("
    INSERT INTO pagamentos (parceiro_id, descricao, valor, data_pagamento, metodo, status, criado_em)
    VALUES (?, ?, ?, ?, ?, ?, NOW())
");
$stmt->bind_param("isdsss", $parceiro_id, $descricao, $valor, $data_pagamento, $metodo, $status);

if ($stmt->execute()) {
    $_SESSION['success'] = 'Pagamento criado com sucesso!';
} else {
    $_SESSION['error'] = 'Erro ao criar pagamento: ' . $conn->error;
}

$conn->close();
redirect(APP_URL . '/admin/pagamentos-admin.php');
?>

