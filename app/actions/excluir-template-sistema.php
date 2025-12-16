<?php
/**
 * Action: Excluir Template do Sistema (apenas admin)
 */

require_once '../config/config.php';

if (!isAuthenticated() || !hasRole(ROLE_ADMIN)) {
    $_SESSION['error'] = 'Acesso negado.';
    header('Location: ' . APP_URL . '/login.php');
    exit;
}

$template_id = (int) ($_GET['id'] ?? 0);

if ($template_id <= 0) {
    $_SESSION['error'] = 'Template inválido.';
    header('Location: ' . APP_URL . '/admin/templates-sistema.php');
    exit;
}

try {
    $conn = getDBConnection();

    // Verificar se é template do sistema
    $stmt = $conn->prepare("SELECT id, nome, arquivo_url FROM templates_certificados WHERE id = ? AND template_sistema = 1");
    $stmt->bind_param("i", $template_id);
    $stmt->execute();
    $template = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$template) {
        throw new Exception('Template não encontrado ou não é um template do sistema.');
    }

    // Excluir campos customizados associados
    $stmt = $conn->prepare("DELETE FROM template_campos_customizados WHERE template_id = ?");
    $stmt->bind_param("i", $template_id);
    $stmt->execute();
    $stmt->close();

    // Excluir template
    $stmt = $conn->prepare("DELETE FROM templates_certificados WHERE id = ?");
    $stmt->bind_param("i", $template_id);
    $stmt->execute();
    $stmt->close();

    // Opcional: Excluir arquivo de imagem
    if (!empty($template['arquivo_url'])) {
        $arquivo_path = __DIR__ . '/../../' . str_replace(APP_URL . '/', '', $template['arquivo_url']);
        if (file_exists($arquivo_path)) {
            @unlink($arquivo_path);
        }
    }

    $_SESSION['success'] = "Template '{$template['nome']}' excluído com sucesso!";

} catch (Exception $e) {
    $_SESSION['error'] = 'Erro ao excluir: ' . $e->getMessage();
}

header('Location: ' . APP_URL . '/admin/templates-sistema.php');
exit;
?>