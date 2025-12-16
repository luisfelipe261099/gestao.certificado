<?php
/**
 * Criar Assinatura - Sistema de Certificados
 * Ação para criar nova assinatura (Admin)
 */

require_once __DIR__ . '/bootstrap.php';

// Verificar autenticação e permissão
if (!isAuthenticated() || !hasRole(ROLE_ADMIN)) {
    http_response_code(403);
    die('Acesso negado');
}

// Verificar método POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    die('Método não permitido');
}

// Validar dados
$parceiro_id = isset($_POST['parceiro_id']) ? intval($_POST['parceiro_id']) : 0;
$plano_id = isset($_POST['plano_id']) ? intval($_POST['plano_id']) : 0;
$data_inicio = isset($_POST['data_inicio']) ? trim($_POST['data_inicio']) : '';

$errors = [];

if ($parceiro_id <= 0) {
    $errors[] = 'Parceiro é obrigatório';
}

if ($plano_id <= 0) {
    $errors[] = 'Plano é obrigatório';
}

if (empty($data_inicio)) {
    $errors[] = 'Data de início é obrigatória';
}

// Se houver erros, redirecionar com mensagem
if (!empty($errors)) {
    $_SESSION['error'] = implode(', ', $errors);
    redirect(APP_URL . '/admin/assinaturas-admin.php');
    exit;
}

try {
    $conn = getDBConnection();

    // Buscar quantidade de certificados do plano
    $stmt = $conn->prepare("SELECT quantidade_certificados FROM planos WHERE id = ?");
    if ($stmt) {
        $stmt->bind_param("i", $plano_id);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 0) {
            $_SESSION['error'] = 'Plano não encontrado';
            $stmt->close();
            $conn->close();
            redirect(APP_URL . '/admin/assinaturas-admin.php');
            exit;
        }

        $row = $result->fetch_assoc();
        $quantidade_certificados = $row['quantidade_certificados'];
        $stmt->close();
    }

    // Calcular data de vencimento (30 dias após início)
    $data_vencimento = date('Y-m-d', strtotime($data_inicio . ' +30 days'));
    $status = 'ativa';
    $criado_em = date('Y-m-d H:i:s');
    $certificados_usados = 0;
    $certificados_disponiveis = $quantidade_certificados;
    $renovacao_automatica = 1;

    $stmt = $conn->prepare("
        INSERT INTO assinaturas (parceiro_id, plano_id, data_inicio, data_vencimento, certificados_totais, certificados_usados, certificados_disponiveis, status, renovacao_automatica, criado_em)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
    ");

    if ($stmt) {
        $stmt->bind_param("iissiiisi", $parceiro_id, $plano_id, $data_inicio, $data_vencimento, $quantidade_certificados, $certificados_usados, $certificados_disponiveis, $status, $renovacao_automatica);

        if ($stmt->execute()) {
            $_SESSION['success'] = 'Assinatura criada com sucesso!';
        } else {
            $_SESSION['error'] = 'Erro ao criar assinatura: ' . $stmt->error;
        }
        $stmt->close();
    }

    $conn->close();
    
} catch (Exception $e) {
    error_log("Erro ao criar assinatura: " . $e->getMessage());
    $_SESSION['error'] = 'Erro ao criar assinatura: ' . $e->getMessage();
}

redirect(APP_URL . '/admin/assinaturas-admin.php');
?>

