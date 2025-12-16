<?php
/**
 * Criar Receita - Sistema de Certificados
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

$parceiro_id = isset($_POST['parceiro_id']) ? (int)$_POST['parceiro_id'] : 0;
$assinatura_id = isset($_POST['assinatura_id']) ? (int)$_POST['assinatura_id'] : null;
$tipo = isset($_POST['tipo']) ? trim($_POST['tipo']) : '';
$valor = isset($_POST['valor']) ? (float)$_POST['valor'] : 0;
$metodo_pagamento = isset($_POST['metodo_pagamento']) ? trim($_POST['metodo_pagamento']) : '';
$data_receita = isset($_POST['data_receita']) ? trim($_POST['data_receita']) : '';

if (empty($parceiro_id) || empty($tipo) || empty($valor) || empty($data_receita)) {
    $_SESSION['error'] = 'Preencha todos os campos obrigatórios';
    redirect(APP_URL . '/admin/receitas-admin.php');
}

if ($valor <= 0) {
    $_SESSION['error'] = 'Valor deve ser maior que zero';
    redirect(APP_URL . '/admin/receitas-admin.php');
}

// Verificar se parceiro existe
$stmt = $conn->prepare("SELECT id FROM parceiros WHERE id = ? AND ativo = 1");
$stmt->bind_param("i", $parceiro_id);
$stmt->execute();
if ($stmt->get_result()->num_rows === 0) {
    $_SESSION['error'] = 'Parceiro não encontrado';
    redirect(APP_URL . '/admin/receitas-admin.php');
}

// Inserir receita
$status = 'pago';
if (empty($assinatura_id)) {
    $stmt = $conn->prepare("
        INSERT INTO receitas (parceiro_id, tipo, valor, data_receita, metodo_pagamento, status, criado_em)
        VALUES (?, ?, ?, ?, ?, ?, NOW())
    ");
    $stmt->bind_param("isdsss", $parceiro_id, $tipo, $valor, $data_receita, $metodo_pagamento, $status);
} else {
    $stmt = $conn->prepare("
        INSERT INTO receitas (parceiro_id, assinatura_id, tipo, valor, data_receita, metodo_pagamento, status, criado_em)
        VALUES (?, ?, ?, ?, ?, ?, ?, NOW())
    ");
    $stmt->bind_param("iisdsss", $parceiro_id, $assinatura_id, $tipo, $valor, $data_receita, $metodo_pagamento, $status);
}

if ($stmt->execute()) {
    $_SESSION['success'] = 'Receita criada com sucesso!';
} else {
    $_SESSION['error'] = 'Erro ao criar receita: ' . $conn->error;
}

$conn->close();
redirect(APP_URL . '/admin/receitas-admin.php');
?>

