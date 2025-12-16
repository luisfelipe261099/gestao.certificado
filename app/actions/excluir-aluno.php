<?php
/**
 * Excluir Aluno (Parceiro)
 * - Verifica propriedade
 * - Remove arquivos de certificados do aluno (uploads/certificados)
 * - Exclui aluno (FKs em cascata removem certificados/inscrições)
 */

ob_start();
require_once '../config/config.php';
require_once '../hooks/sincronizar-aluno.php';

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
        header('Location: ' . APP_URL . '/parceiro/alunos-parceiro.php');
        exit;
    }

    $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
    if ($id <= 0) {
        throw new Exception('ID do aluno inválido.');
    }

    $conn = getDBConnection();
    $user = getCurrentUser();
    $parceiro_id = $user['parceiro_id'] ?? $user['id'];

    // Validar que o aluno pertence ao parceiro
    $stmt = $conn->prepare('SELECT id FROM alunos WHERE id = ? AND parceiro_id = ? LIMIT 1');
    if (!$stmt) { throw new Exception('Falha ao consultar aluno.'); }
    $stmt->bind_param('ii', $id, $parceiro_id);
    $stmt->execute();
    $res = $stmt->get_result();
    $aluno = $res->fetch_assoc();
    $stmt->close();

    if (!$aluno) {
        throw new Exception('Aluno não encontrado para este parceiro.');
    }

    // Remover arquivos físicos de certificados deste aluno (se existirem)
    $stmt = $conn->prepare('SELECT arquivo_url FROM certificados WHERE parceiro_id = ? AND aluno_id = ?');
    if ($stmt) {
        $stmt->bind_param('ii', $parceiro_id, $id);
        $stmt->execute();
        $res = $stmt->get_result();
        while ($row = $res->fetch_assoc()) {
            $arquivo_url = $row['arquivo_url'] ?? '';
            if ($arquivo_url) {
                $basename = basename(parse_url($arquivo_url, PHP_URL_PATH) ?: '');
                if ($basename && preg_match('/\.pdf$/i', $basename)) {
                    $candidate = realpath(__DIR__ . '/../../uploads/certificados/' . $basename);
                    if ($candidate && is_file($candidate)) { @unlink($candidate); }
                }
            }
        }
        $stmt->close();
    }

    // Obter dados do aluno antes de excluir (para sincronização)
    $aluno_id_ead = $aluno['id_ead'] ?? null;

    // Excluir o aluno (ON DELETE CASCADE cuida de certificados/inscrições)
    $stmt = $conn->prepare('DELETE FROM alunos WHERE id = ? AND parceiro_id = ? LIMIT 1');
    if (!$stmt) { throw new Exception('Falha ao preparar exclusão.'); }
    $stmt->bind_param('ii', $id, $parceiro_id);
    if (!$stmt->execute()) {
        throw new Exception('Erro ao excluir aluno: ' . $stmt->error);
    }
    $stmt->close();

    // Desativar aluno no EAD se foi sincronizado
    if ($aluno_id_ead) {
        try {
            $stmt = $conn->prepare('UPDATE alunos SET ativo = 0 WHERE id = ?');
            if ($stmt) {
                $stmt->bind_param('i', $aluno_id_ead);
                $stmt->execute();
                $stmt->close();
            }
        } catch (Exception $e) {
            error_log("Aviso: Não foi possível desativar aluno no EAD: " . $e->getMessage());
        }
    }

    $_SESSION['success'] = 'Aluno excluído com sucesso!';
    $conn->close();

} catch (Exception $e) {
    error_log('Erro ao excluir aluno: ' . $e->getMessage());
    $_SESSION['error'] = $e->getMessage();
}

if (ob_get_level()) { @ob_end_clean(); }
$redirectUrl = APP_URL . '/parceiro/alunos-parceiro.php';
if (!headers_sent()) {
    header('Location: ' . $redirectUrl);
    exit;
}
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

