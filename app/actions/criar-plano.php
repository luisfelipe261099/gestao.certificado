<?php
/**
 * Criar Plano - Sistema de Certificados
 */

require_once __DIR__ . '/bootstrap.php';

// Verificar autenticação e permissão
if (!isAuthenticated() || !hasRole(ROLE_ADMIN)) {
    redirect(APP_URL . '/login.php');
}

// Verificar método POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect(APP_URL . '/admin/planos-admin.php');
}

// Validar dados
$nome = isset($_POST['nome']) ? trim($_POST['nome']) : '';
$certificados = isset($_POST['certificados']) ? intval($_POST['certificados']) : 0;
$certificados_mensais = isset($_POST['certificados_mensais']) ? intval($_POST['certificados_mensais']) : 0;
$templates = isset($_POST['templates']) ? intval($_POST['templates']) : 5;
$valor = isset($_POST['valor']) ? floatval($_POST['valor']) : 0;
$max_parcelas = isset($_POST['max_parcelas']) ? intval($_POST['max_parcelas']) : 1;
$descricao = isset($_POST['descricao']) ? trim($_POST['descricao']) : '';

// Validações
if (empty($nome) || $certificados <= 0 || $certificados_mensais <= 0 || $templates <= 0 || $valor <= 0 || $max_parcelas <= 0) {
    $_SESSION['error'] = 'Preencha todos os campos obrigatórios corretamente.';
    redirect(APP_URL . '/admin/planos-admin.php');
}

$conn = getDBConnection();

try {
    // Verificar se plano com mesmo nome já existe
    $stmt = $conn->prepare("SELECT id FROM planos WHERE nome = ?");
    $stmt->bind_param("s", $nome);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $_SESSION['error'] = 'Já existe um plano com este nome.';
        redirect(APP_URL . '/admin/planos-admin.php');
    }

    $stmt->close();

    // Criar plano
    $ativo = 1;
    $criado_em = date('Y-m-d H:i:s');

    $stmt = $conn->prepare("
        INSERT INTO planos (nome, quantidade_certificados, certificados_mensais, quantidade_templates, valor, max_parcelas, descricao, ativo, criado_em)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");

    $stmt->bind_param("siiidisis", $nome, $certificados, $certificados_mensais, $templates, $valor, $max_parcelas, $descricao, $ativo, $criado_em);

    if ($stmt->execute()) {
        $_SESSION['success'] = 'Plano criado com sucesso!';
    } else {
        $_SESSION['error'] = 'Erro ao criar plano: ' . $conn->error;
    }

    $stmt->close();

} catch (Exception $e) {
    $_SESSION['error'] = 'Erro ao processar: ' . $e->getMessage();
}

$conn->close();
redirect(APP_URL . '/admin/planos-admin.php');
?>