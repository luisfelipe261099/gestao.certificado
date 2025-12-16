<?php
/**
 * Deletar Campo Customizado de Template
 */

ob_start();
require_once '../config/config.php';

try {
    if (!isAuthenticated() || !hasRole(ROLE_PARCEIRO)) {
        $_SESSION['error'] = 'Acesso negado.';
        if (ob_get_level()) { @ob_end_clean(); }
        header('Location: ' . APP_URL . '/login.php');
        exit;
    }

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        $_SESSION['error'] = 'Método não permitido.';
        if (ob_get_level()) { @ob_end_clean(); }
        header('Location: ' . APP_URL . '/parceiro/templates-parceiro.php');
        exit;
    }

    $conn = getDBConnection();
    $user = getCurrentUser();
    $parceiro_id = $user['parceiro_id'] ?? $user['id'];

    $campo_id = isset($_POST['campo_id']) ? (int)$_POST['campo_id'] : 0;

    if ($campo_id <= 0) {
        throw new Exception('ID do campo inválido.');
    }

    // Verificar se o campo pertence a um template do parceiro
    $stmt = $conn->prepare("
        SELECT tcc.id FROM template_campos_customizados tcc
        JOIN templates_certificados tc ON tcc.template_id = tc.id
        WHERE tcc.id = ? AND tc.parceiro_id = ?
    ");
    if (!$stmt) { throw new Exception('Falha ao preparar verificação.'); }
    $stmt->bind_param('ii', $campo_id, $parceiro_id);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($res->num_rows === 0) {
        throw new Exception('Campo não encontrado ou sem permissão.');
    }
    $stmt->close();

    // Deletar campo
    $stmt = $conn->prepare("DELETE FROM template_campos_customizados WHERE id = ?");
    if (!$stmt) { throw new Exception('Falha ao preparar deleção.'); }
    $stmt->bind_param('i', $campo_id);
    if (!$stmt->execute()) {
        throw new Exception('Erro ao deletar campo: ' . $stmt->error);
    }
    $stmt->close();

    $_SESSION['success'] = 'Campo removido com sucesso!';
    $conn->close();

} catch (Exception $e) {
    error_log('Erro ao deletar campo customizado: ' . $e->getMessage());
    $_SESSION['error'] = $e->getMessage();
}

if (ob_get_level()) { @ob_end_clean(); }
$redirectUrl = APP_URL . '/parceiro/templates-parceiro.php';
if (!headers_sent()) {
    header('Location: ' . $redirectUrl);
    exit;
}
echo '<!DOCTYPE html><html><head><meta charset="utf-8">'
   . '<meta http-equiv="refresh" content="0;url=' . htmlspecialchars($redirectUrl, ENT_QUOTES, 'UTF-8') . '">'
   . '<title>Redirecionando...</title>'
   . '</head><body>'
   . 'Redirecionando... Se não redirecionar, '
   . '<a href="' . htmlspecialchars($redirectUrl, ENT_QUOTES, 'UTF-8') . '">clique aqui</a>.'
   . '<script>try{window.location.replace(' . json_encode($redirectUrl) . ');}catch(e){}</script>'
   . '</body></html>';
exit;

