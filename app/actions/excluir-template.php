<?php
/**
 * Excluir Template de Certificado (Parceiro)
 */

ob_start();
require_once __DIR__ . '/bootstrap.php';

try {
    if (!isAuthenticated() || !hasRole(ROLE_PARCEIRO)) {
        $_SESSION['error'] = 'Acesso negado. Faça login como parceiro.';
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

    $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
    if ($id <= 0) {
        throw new Exception('ID do template inválido.');
    }

    // Verificar se o template existe e pertence ao parceiro
    $stmt = $conn->prepare('SELECT id, arquivo_url, ativo FROM templates_certificados WHERE id = ? AND parceiro_id = ? LIMIT 1');
    if (!$stmt) { throw new Exception('Falha ao consultar template.'); }
    $stmt->bind_param('ii', $id, $parceiro_id);
    $stmt->execute();
    $res = $stmt->get_result();
    $tpl = $res->fetch_assoc();
    $stmt->close();

    if (!$tpl) {
        throw new Exception('Template não encontrado para este parceiro.');
    }

    // Remover arquivo físico se existir em uploads/templates
    $arquivo_url = $tpl['arquivo_url'] ?? '';
    if ($arquivo_url) {
        $basename = basename(parse_url($arquivo_url, PHP_URL_PATH) ?: '');
        if ($basename && preg_match('/\.pdf$/i', $basename)) {
            $candidate = realpath(__DIR__ . '/../../uploads/templates/' . $basename);
            if ($candidate && is_file($candidate)) {
                @unlink($candidate);
            }
        }
    }

    // Excluir o template
    $stmt = $conn->prepare('DELETE FROM templates_certificados WHERE id = ? AND parceiro_id = ? LIMIT 1');
    if (!$stmt) { throw new Exception('Falha ao preparar exclusão.'); }
    $stmt->bind_param('ii', $id, $parceiro_id);
    if (!$stmt->execute()) {
        throw new Exception('Erro ao excluir template: ' . $stmt->error);
    }
    $stmt->close();

    $_SESSION['success'] = 'Template excluído com sucesso!';
    $conn->close();

} catch (Exception $e) {
    error_log('Erro ao excluir template: ' . $e->getMessage());
    $_SESSION['error'] = $e->getMessage();
}

if (ob_get_level()) { @ob_end_clean(); }
$redirectUrl = APP_URL . '/parceiro/templates-parceiro.php';
if (!headers_sent()) {
    header('Location: ' . $redirectUrl);
    exit;
}
// Fallback de redirecionamento
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="utf-8">
  <meta http-equiv="refresh" content="0;url=<?php echo htmlspecialchars($redirectUrl, ENT_QUOTES, 'UTF-8'); ?>">
  <title>Redirecionando...</title>
</head>
<body>
  Redirecionando... Se não redirecionar, <a href="<?php echo htmlspecialchars($redirectUrl, ENT_QUOTES, 'UTF-8'); ?>">clique aqui</a>.
  <script>try{window.location.replace(<?php echo json_encode($redirectUrl); ?>);}catch(e){}</script>
</body>
</html>

