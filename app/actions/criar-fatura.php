<?php
/**
 * Criar Fatura - Sistema de Certificados
 * Padrão MVP - Camada de Ação
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

// Validar dados
$parceiro_id = isset($_POST['parceiro_id']) ? (int)$_POST['parceiro_id'] : 0;
$numero_fatura = isset($_POST['numero_fatura']) ? trim($_POST['numero_fatura']) : '';
$descricao = isset($_POST['descricao']) ? trim($_POST['descricao']) : '';
$valor = isset($_POST['valor']) ? (float)$_POST['valor'] : 0;
$data_emissao = isset($_POST['data_emissao']) ? trim($_POST['data_emissao']) : '';
$data_vencimento = isset($_POST['data_vencimento']) ? trim($_POST['data_vencimento']) : '';

// Validações
if (empty($parceiro_id) || empty($numero_fatura) || empty($valor) || empty($data_emissao) || empty($data_vencimento)) {
    $_SESSION['error'] = 'Preencha todos os campos obrigatórios';
    redirect(APP_URL . '/admin/faturas-admin.php');
}

if ($valor <= 0) {
    $_SESSION['error'] = 'Valor deve ser maior que zero';
    redirect(APP_URL . '/admin/faturas-admin.php');
}

// Verificar se parceiro existe
$stmt = $conn->prepare("SELECT id FROM parceiros WHERE id = ? AND ativo = 1");
$stmt->bind_param("i", $parceiro_id);
$stmt->execute();
if ($stmt->get_result()->num_rows === 0) {
    $_SESSION['error'] = 'Parceiro não encontrado';
    redirect(APP_URL . '/admin/faturas-admin.php');
}

// Verificar se número de fatura já existe
$stmt = $conn->prepare("SELECT id FROM faturas WHERE numero_fatura = ?");
$stmt->bind_param("s", $numero_fatura);
$stmt->execute();
if ($stmt->get_result()->num_rows > 0) {
    $_SESSION['error'] = 'Número de fatura já existe';
    redirect(APP_URL . '/admin/faturas-admin.php');
}

// Inserir fatura
$status = 'pendente';
$stmt = $conn->prepare("
    INSERT INTO faturas (parceiro_id, numero_fatura, descricao, valor, data_emissao, data_vencimento, status, criado_em)
    VALUES (?, ?, ?, ?, ?, ?, ?, NOW())
");
$stmt->bind_param("issddsss", $parceiro_id, $numero_fatura, $descricao, $valor, $data_emissao, $data_vencimento, $status);

if ($stmt->execute()) {
    $_SESSION['success'] = 'Fatura criada com sucesso!';
} else {
    $_SESSION['error'] = 'Erro ao criar fatura: ' . $conn->error;
}

$conn->close();
redirect(APP_URL . '/admin/faturas-admin.php');
?>

