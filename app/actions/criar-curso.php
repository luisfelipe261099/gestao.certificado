<?php
/**
 * Criar Curso - Sistema de Certificados
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
    $nome = sanitize($_POST['nome'] ?? '');
    $descricao = sanitize($_POST['descricao'] ?? '');
    $carga_horaria = intval($_POST['carga_horaria'] ?? 0);
    $ativo = isset($_POST['ativo']) ? 1 : 0;

    // Se for admin, pode especificar parceiro_id ou deixar null (curso global)
    if ($is_admin) {
        $parceiro_id = !empty($_POST['parceiro_id']) ? intval($_POST['parceiro_id']) : null;
    } else {
        // Se for parceiro, usar o parceiro_id do usuário logado
        $parceiro_id = $user['parceiro_id'] ?? $user['id'];
    }

    if (empty($nome)) {
        $_SESSION['error'] = 'Nome do curso é obrigatório';
        $redirect_url = $is_admin ? APP_URL . '/admin/cursos-admin.php' : APP_URL . '/parceiro/cursos-parceiro.php';
        header('Location: ' . $redirect_url);
        exit;
    }

    // Inserir curso
    if ($parceiro_id) {
        $stmt = $conn->prepare("
            INSERT INTO cursos (parceiro_id, nome, descricao, carga_horaria, ativo, criado_em)
            VALUES (?, ?, ?, ?, ?, NOW())
        ");
        $stmt->bind_param("issii", $parceiro_id, $nome, $descricao, $carga_horaria, $ativo);
    } else {
        // Curso global (sem parceiro específico)
        $stmt = $conn->prepare("
            INSERT INTO cursos (nome, descricao, carga_horaria, ativo, criado_em)
            VALUES (?, ?, ?, ?, NOW())
        ");
        $stmt->bind_param("ssii", $nome, $descricao, $carga_horaria, $ativo);
    }

    if ($stmt) {
        if ($stmt->execute()) {
            $curso_id = $stmt->insert_id;

            // Sincronizar curso para o EAD (se aplicável)
            if (function_exists('sincronizarCursoEAD')) {
                sincronizarCursoEAD($curso_id);
            }

            $_SESSION['success'] = 'Curso criado com sucesso!';
        } else {
            $_SESSION['error'] = 'Erro ao criar curso: ' . $stmt->error;
        }

        $stmt->close();
    } else {
        $_SESSION['error'] = 'Erro ao preparar query: ' . $conn->error;
    }

    $conn->close();

} catch (Exception $e) {
    error_log("Erro ao criar curso: " . $e->getMessage());
    $_SESSION['error'] = 'Erro ao criar curso: ' . $e->getMessage();
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

