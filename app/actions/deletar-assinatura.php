<?php
/**
 * Deletar Assinatura - Sistema de Certificados
 */

require_once __DIR__ . '/bootstrap.php';

// Verificar autenticação e permissão
if (!isAuthenticated() || !hasRole(ROLE_ADMIN)) {
    redirect(APP_URL . '/login.php');
}

// Verificar se ID foi fornecido
if (!isset($_GET['id']) || empty($_GET['id'])) {
    $_SESSION['error'] = 'ID da assinatura não fornecido.';
    redirect(APP_URL . '/admin/assinaturas-admin.php');
}

$assinatura_id = intval($_GET['id']);
$conn = getDBConnection();

try {
    // Verificar se assinatura existe
    $stmt = $conn->prepare("SELECT id FROM assinaturas WHERE id = ?");
    $stmt->bind_param("i", $assinatura_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        $_SESSION['error'] = 'Assinatura não encontrada.';
        redirect(APP_URL . '/admin/assinaturas-admin.php');
    }

    $stmt->close();

    // Deletar dependências para evitar erro de chave estrangeira
    $tables = ['receitas', 'asaas_cobrancas', 'faturas', 'contratos', 'log_renovacoes'];
    foreach ($tables as $table) {
        // Verificar se a tabela existe antes de tentar deletar (segurança para tabelas opcionais)
        $check = $conn->query("SHOW TABLES LIKE '$table'");
        if ($check && $check->num_rows > 0) {
            // Verificar se a coluna assinatura_id existe na tabela
            $colCheck = $conn->query("SHOW COLUMNS FROM $table LIKE 'assinatura_id'");
            if ($colCheck && $colCheck->num_rows > 0) {
                $stmt_del = $conn->prepare("DELETE FROM $table WHERE assinatura_id = ?");
                if ($stmt_del) {
                    $stmt_del->bind_param("i", $assinatura_id);
                    $stmt_del->execute();
                    $stmt_del->close();
                }
            }
        }
    }

    // Deletar assinatura
    $stmt = $conn->prepare("DELETE FROM assinaturas WHERE id = ?");
    $stmt->bind_param("i", $assinatura_id);

    if ($stmt->execute()) {
        $_SESSION['success'] = 'Assinatura e dados vinculados deletados com sucesso!';
    } else {
        $_SESSION['error'] = 'Erro ao deletar assinatura: ' . $conn->error;
    }

    $stmt->close();

} catch (Exception $e) {
    $_SESSION['error'] = 'Erro ao processar: ' . $e->getMessage();
}

$conn->close();
redirect(APP_URL . '/admin/assinaturas-admin.php');
?>