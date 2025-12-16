<?php
/**
 * Editar Curso - Sistema de Certificados
 * Padrão MVP - Camada de Ação
 */

// Limpar qualquer output anterior
ob_start();

require_once '../config/config.php';
require_once '../hooks/sincronizar-aluno.php';

// Verificar autenticação e permissão
if (!isAuthenticated()) {
    http_response_code(403);
    echo json_encode(['erro' => 'Acesso negado']);
    exit;
}

$is_admin = hasRole(ROLE_ADMIN);
$is_parceiro = hasRole(ROLE_PARCEIRO);

if (!$is_admin && !$is_parceiro) {
    http_response_code(403);
    echo json_encode(['erro' => 'Acesso negado']);
    exit;
}

// Verificar método POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['erro' => 'Método não permitido']);
    exit;
}

try {
    $conn = getDBConnection();
    $user = getCurrentUser();

    // Validar dados
    $curso_id = intval($_POST['id'] ?? $_POST['curso_id'] ?? 0);
    $nome = sanitize($_POST['nome'] ?? '');
    $descricao = sanitize($_POST['descricao'] ?? '');
    $carga_horaria = intval($_POST['carga_horaria'] ?? 0);
    $ativo = isset($_POST['ativo']) ? intval($_POST['ativo']) : 1;

    $redirect_url = $is_admin ? APP_URL . '/admin/cursos-admin.php' : APP_URL . '/parceiro/cursos-parceiro.php';

    if (empty($curso_id)) {
        $_SESSION['error'] = 'ID do curso inválido';
        header('Location: ' . $redirect_url);
        exit;
    }

    if (empty($nome)) {
        $_SESSION['error'] = 'Nome do curso é obrigatório';
        header('Location: ' . $redirect_url);
        exit;
    }

    // Verificar permissão
    if ($is_parceiro) {
        $parceiro_id = $user['parceiro_id'] ?? $user['id'];
        $stmt = $conn->prepare("SELECT id FROM cursos WHERE id = ? AND parceiro_id = ?");
        $stmt->bind_param("ii", $curso_id, $parceiro_id);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 0) {
            $_SESSION['error'] = 'Curso não encontrado ou sem permissão';
            $stmt->close();
            header('Location: ' . $redirect_url);
            exit;
        }
        $stmt->close();
    }

    // Atualizar curso
    $stmt = $conn->prepare("
        UPDATE cursos
        SET nome = ?, descricao = ?, carga_horaria = ?, ativo = ?
        WHERE id = ?
    ");

    if ($stmt) {
        $stmt->bind_param("ssiii", $nome, $descricao, $carga_horaria, $ativo, $curso_id);

        if ($stmt->execute()) {
            // Sincronizar curso atualizado para o EAD (se aplicável)
            if (function_exists('sincronizarCursoEAD')) {
                sincronizarCursoEAD($curso_id);
            }

            $_SESSION['success'] = 'Curso atualizado com sucesso!';
        } else {
            $_SESSION['error'] = 'Erro ao atualizar curso: ' . $stmt->error;
        }

        $stmt->close();
    } else {
        $_SESSION['error'] = 'Erro ao preparar query: ' . $conn->error;
    }

    $conn->close();

} catch (Exception $e) {
    error_log("Erro ao editar curso: " . $e->getMessage());
    $_SESSION['error'] = 'Erro ao editar curso: ' . $e->getMessage();
}

// Limpar output buffer
ob_end_clean();

// Redirecionar
$redirect_url = $is_admin ? APP_URL . '/admin/cursos-admin.php' : APP_URL . '/parceiro/cursos-parceiro.php';
header('Location: ' . $redirect_url);
header('Connection: close');
http_response_code(302);
exit;
?>

