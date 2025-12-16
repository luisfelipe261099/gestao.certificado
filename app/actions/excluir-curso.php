<?php
/**
 * Excluir Curso - Sistema de Certificados
 * Padrão MVP - Camada de Ação
 */

// Limpar qualquer output anterior
ob_start();

require_once '../config/config.php';
require_once '../hooks/sincronizar-aluno.php';

// Verificar autenticação e permissão
if (!isAuthenticated() || !hasRole(ROLE_PARCEIRO)) {
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
    $parceiro_id = $user['parceiro_id'] ?? $user['id'];
    
    // Validar dados
    $curso_id = intval($_POST['curso_id'] ?? 0);
    
    if (empty($curso_id)) {
        $_SESSION['error'] = 'ID do curso inválido';
        header('Location: ' . APP_URL . '/parceiro/cursos-parceiro.php');
        exit;
    }
    
    // Verificar se o curso pertence ao parceiro
    $stmt = $conn->prepare("SELECT id, id_ead FROM cursos WHERE id = ? AND parceiro_id = ?");
    if ($stmt) {
        $stmt->bind_param("ii", $curso_id, $parceiro_id);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 0) {
            $_SESSION['error'] = 'Curso não encontrado ou sem permissão';
            $stmt->close();
            header('Location: ' . APP_URL . '/parceiro/cursos-parceiro.php');
            exit;
        }

        $curso = $result->fetch_assoc();
        $curso_id_ead = $curso['id_ead'] ?? null;
        $stmt->close();
    }
    
    // Verificar se há inscrições no curso
    $stmt = $conn->prepare("SELECT COUNT(*) as total FROM inscricoes_alunos WHERE curso_id = ?");
    if ($stmt) {
        $stmt->bind_param("i", $curso_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        
        if ($row['total'] > 0) {
            $_SESSION['error'] = 'Não é possível excluir um curso com inscrições de alunos';
            $stmt->close();
            header('Location: ' . APP_URL . '/parceiro/cursos-parceiro.php');
            exit;
        }
        
        $stmt->close();
    }
    
    // Excluir curso
    $stmt = $conn->prepare("DELETE FROM cursos WHERE id = ? AND parceiro_id = ?");

    if ($stmt) {
        $stmt->bind_param("ii", $curso_id, $parceiro_id);

        if ($stmt->execute()) {
            // Desativar curso no EAD se foi sincronizado
            if ($curso_id_ead) {
                try {
                    $stmt_ead = $conn->prepare("UPDATE cursos SET ativo = 0 WHERE id = ?");
                    if ($stmt_ead) {
                        $stmt_ead->bind_param("i", $curso_id_ead);
                        $stmt_ead->execute();
                        $stmt_ead->close();
                    }
                } catch (Exception $e) {
                    error_log("Aviso: Não foi possível desativar curso no EAD: " . $e->getMessage());
                }
            }

            $_SESSION['success'] = 'Curso excluído com sucesso!';
        } else {
            $_SESSION['error'] = 'Erro ao excluir curso: ' . $stmt->error;
        }

        $stmt->close();
    } else {
        $_SESSION['error'] = 'Erro ao preparar query: ' . $conn->error;
    }
    
    $conn->close();
    
} catch (Exception $e) {
    error_log("Erro ao excluir curso: " . $e->getMessage());
    $_SESSION['error'] = 'Erro ao excluir curso: ' . $e->getMessage();
}

// Limpar output buffer
ob_end_clean();

// Redirecionar
header('Location: ' . APP_URL . '/parceiro/cursos-parceiro.php');
header('Connection: close');
http_response_code(302);
exit;
?>

