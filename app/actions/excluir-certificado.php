<?php
/**
 * Excluir Certificado (Parceiro)
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
        header('Location: ' . APP_URL . '/parceiro/gerar-certificados.php');
        exit;
    }

    $conn = getDBConnection();
    $user = getCurrentUser();
    $parceiro_id = $user['parceiro_id'] ?? $user['id'];

    $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
    if ($id <= 0) {
        throw new Exception('ID do certificado inválido.');
    }

    // Verificar se o certificado existe e pertence ao parceiro
    $stmt = $conn->prepare('SELECT id, arquivo_url FROM certificados WHERE id = ? AND parceiro_id = ? LIMIT 1');
    if (!$stmt) { throw new Exception('Falha ao consultar certificado.'); }
    $stmt->bind_param('ii', $id, $parceiro_id);
    $stmt->execute();
    $res = $stmt->get_result();
    $cert = $res->fetch_assoc();
    $stmt->close();

    if (!$cert) {
        throw new Exception('Certificado não encontrado para este parceiro.');
    }

    // Remover arquivo físico se existir em uploads/certificados
    $arquivo_url = $cert['arquivo_url'] ?? '';
    if ($arquivo_url) {
        $basename = basename(parse_url($arquivo_url, PHP_URL_PATH) ?: '');
        if ($basename && preg_match('/\.pdf$/i', $basename)) {
            $candidate = realpath(__DIR__ . '/../../uploads/certificados/' . $basename);
            if ($candidate && is_file($candidate)) {
                @unlink($candidate);
            }
        }
    }

    // Excluir o certificado
    $stmt = $conn->prepare('DELETE FROM certificados WHERE id = ? AND parceiro_id = ? LIMIT 1');
    if (!$stmt) { throw new Exception('Falha ao preparar exclusão.'); }
    $stmt->bind_param('ii', $id, $parceiro_id);
    if (!$stmt->execute()) {
        throw new Exception('Erro ao excluir certificado: ' . $stmt->error);
    }
    $stmt->close();

    // ============================================================
    // INCREMENTAR CERTIFICADOS DISPONÍVEIS DA ASSINATURA
    // ============================================================
    // Quando um certificado é deletado, devemos devolver o certificado
    // para o pool de disponíveis (decrementar usados, incrementar disponíveis)
    $stmt = $conn->prepare('
        UPDATE assinaturas
        SET certificados_usados = GREATEST(0, certificados_usados - 1),
            certificados_disponiveis = certificados_disponiveis + 1,
            atualizado_em = NOW()
        WHERE parceiro_id = ? AND status = "ativa"
        LIMIT 1
    ');
    if ($stmt) {
        $stmt->bind_param('i', $parceiro_id);
        if (!$stmt->execute()) {
            error_log('Erro ao atualizar certificados da assinatura ao deletar: ' . $stmt->error);
        }
        $stmt->close();
    }

    $_SESSION['success'] = 'Certificado excluído com sucesso!';
    $conn->close();

} catch (Exception $e) {
    error_log('Erro ao excluir certificado: ' . $e->getMessage());
    $_SESSION['error'] = $e->getMessage();
}

if (ob_get_level()) { @ob_end_clean(); }
$redirectUrl = APP_URL . '/parceiro/gerar-certificados.php';
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

