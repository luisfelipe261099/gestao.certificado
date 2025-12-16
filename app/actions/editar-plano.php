<?php
/**
 * Editar Plano - Sistema de Certificados
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
$id = isset($_POST['id']) ? intval($_POST['id']) : 0;
$nome = isset($_POST['nome']) ? trim($_POST['nome']) : '';
$certificados = isset($_POST['certificados']) ? intval($_POST['certificados']) : 0;
$certificados_mensais = isset($_POST['certificados_mensais']) ? intval($_POST['certificados_mensais']) : 0;
$templates = isset($_POST['templates']) ? intval($_POST['templates']) : 5;
$valor = isset($_POST['valor']) ? floatval($_POST['valor']) : 0;
$max_parcelas = isset($_POST['max_parcelas']) ? intval($_POST['max_parcelas']) : 1;
$descricao = isset($_POST['descricao']) ? trim($_POST['descricao']) : '';

// Validações
if (empty($id) || empty($nome) || $certificados <= 0 || $certificados_mensais <= 0 || $templates <= 0 || $valor <= 0 || $max_parcelas <= 0) {
    $_SESSION['error'] = 'Preencha todos os campos obrigatórios corretamente.';
    redirect(APP_URL . '/admin/planos-admin.php');
}

$conn = getDBConnection();

try {
    // Verificar se plano existe
    $stmt = $conn->prepare("SELECT id FROM planos WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        $_SESSION['error'] = 'Plano não encontrado.';
        redirect(APP_URL . '/admin/planos-admin.php');
    }

    $stmt->close();

    // Atualizar plano
    $stmt = $conn->prepare("
        UPDATE planos
        SET nome = ?, quantidade_certificados = ?, certificados_mensais = ?, quantidade_templates = ?, valor = ?, max_parcelas = ?, descricao = ?
        WHERE id = ?
    ");

    $stmt->bind_param("siiidisi", $nome, $certificados, $certificados_mensais, $templates, $valor, $max_parcelas, $descricao, $id);

    if ($stmt->execute()) {
        $_SESSION['success'] = 'Plano atualizado com sucesso!';
    } else {
        $_SESSION['error'] = 'Erro ao atualizar plano: ' . $conn->error;
    }

    $stmt->close();

} catch (Exception $e) {
    $_SESSION['error'] = 'Erro ao processar: ' . $e->getMessage();
}

$conn->close();
redirect(APP_URL . '/admin/planos-admin.php');
?>