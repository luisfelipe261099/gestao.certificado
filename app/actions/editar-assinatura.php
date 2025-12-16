<?php
/**
 * Editar Assinatura - Sistema de Certificados
 */

require_once __DIR__ . '/bootstrap.php';

// Verificar autenticação e permissão
if (!isAuthenticated() || !hasRole(ROLE_ADMIN)) {
    redirect(APP_URL . '/login.php');
}

// Verificar método POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect(APP_URL . '/admin/assinaturas-admin.php');
}

// Validar dados
$id = isset($_POST['id']) ? intval($_POST['id']) : 0;
$parceiro_id = isset($_POST['parceiro_id']) ? intval($_POST['parceiro_id']) : 0;
$plano_id = isset($_POST['plano_id']) ? intval($_POST['plano_id']) : 0;
$data_inicio = isset($_POST['data_inicio']) ? trim($_POST['data_inicio']) : '';
$status = isset($_POST['status']) ? trim($_POST['status']) : 'ativa';

// Validações
if (empty($id) || empty($parceiro_id) || empty($plano_id) || empty($data_inicio)) {
    $_SESSION['error'] = 'Preencha todos os campos obrigatórios.';
    redirect(APP_URL . '/admin/assinaturas-admin.php');
}

$conn = getDBConnection();

try {
    // Verificar se assinatura existe
    $stmt = $conn->prepare("SELECT id FROM assinaturas WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        $_SESSION['error'] = 'Assinatura não encontrada.';
        redirect(APP_URL . '/admin/assinaturas-admin.php');
    }
    
    $stmt->close();
    
    // Calcular data de vencimento (30 dias após início)
    $data_vencimento = date('Y-m-d', strtotime($data_inicio . ' +30 days'));

    // Buscar quantidade de certificados do plano
    $stmt_plano = $conn->prepare("SELECT quantidade_certificados FROM planos WHERE id = ?");
    $stmt_plano->bind_param("i", $plano_id);
    $stmt_plano->execute();
    $result_plano = $stmt_plano->get_result();
    $row_plano = $result_plano->fetch_assoc();
    $quantidade_certificados = $row_plano['quantidade_certificados'];
    $stmt_plano->close();

    $certificados_usados = 0;
    $certificados_disponiveis = $quantidade_certificados;

    // Atualizar assinatura
    $stmt = $conn->prepare("
        UPDATE assinaturas
        SET parceiro_id = ?, plano_id = ?, data_inicio = ?, data_vencimento = ?, certificados_totais = ?, certificados_usados = ?, certificados_disponiveis = ?, status = ?
        WHERE id = ?
    ");

    $stmt->bind_param("iissiiiisi", $parceiro_id, $plano_id, $data_inicio, $data_vencimento, $quantidade_certificados, $certificados_usados, $certificados_disponiveis, $status, $id);
    
    if ($stmt->execute()) {
        $_SESSION['success'] = 'Assinatura atualizada com sucesso!';
    } else {
        $_SESSION['error'] = 'Erro ao atualizar assinatura: ' . $conn->error;
    }
    
    $stmt->close();
    
} catch (Exception $e) {
    $_SESSION['error'] = 'Erro ao processar: ' . $e->getMessage();
}

$conn->close();
redirect(APP_URL . '/admin/assinaturas-admin.php');
?>

