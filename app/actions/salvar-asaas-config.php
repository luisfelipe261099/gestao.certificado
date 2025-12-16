<?php
/**
 * Salvar Configuração Asaas - Sistema de Certificados
 */

require_once __DIR__ . '/bootstrap.php';

if (!isAuthenticated() || !hasRole(ROLE_ADMIN)) {
    $_SESSION['error'] = 'Acesso negado';
    redirect(APP_URL . '/login.php');
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $_SESSION['error'] = 'Método não permitido';
    redirect(APP_URL . '/admin/asaas-config.php');
}

$conn = getDBConnection();

$api_key = isset($_POST['api_key']) ? trim($_POST['api_key']) : '';
$wallet_id = isset($_POST['wallet_id']) ? trim($_POST['wallet_id']) : '';
$ambiente = isset($_POST['ambiente']) ? trim($_POST['ambiente']) : 'producao';
$ativo = isset($_POST['ativo']) ? 1 : 0;

if (empty($api_key)) {
    $_SESSION['error'] = 'API Key é obrigatória';
    redirect(APP_URL . '/admin/asaas-config.php');
}

if (!in_array($ambiente, ['producao', 'sandbox'])) {
    $_SESSION['error'] = 'Ambiente inválido';
    redirect(APP_URL . '/admin/asaas-config.php');
}

// Verificar se já existe configuração
$result = $conn->query("SELECT id FROM asaas_config LIMIT 1");

if ($result && $result->num_rows > 0) {
    // Atualizar
    $stmt = $conn->prepare("
        UPDATE asaas_config 
        SET api_key = ?, wallet_id = ?, ambiente = ?, ativo = ?, atualizado_em = NOW()
        WHERE id = 1
    ");
    $stmt->bind_param("sssi", $api_key, $wallet_id, $ambiente, $ativo);
    
    if ($stmt->execute()) {
        $_SESSION['success'] = 'Configuração Asaas atualizada com sucesso!';
    } else {
        $_SESSION['error'] = 'Erro ao atualizar configuração: ' . $conn->error;
    }
} else {
    // Inserir
    $stmt = $conn->prepare("
        INSERT INTO asaas_config (api_key, wallet_id, ambiente, ativo, criado_em)
        VALUES (?, ?, ?, ?, NOW())
    ");
    $stmt->bind_param("sssi", $api_key, $wallet_id, $ambiente, $ativo);
    
    if ($stmt->execute()) {
        $_SESSION['success'] = 'Configuração Asaas salva com sucesso!';
    } else {
        $_SESSION['error'] = 'Erro ao salvar configuração: ' . $conn->error;
    }
}

$conn->close();
redirect(APP_URL . '/admin/asaas-config.php');
?>

