<?php
/**
 * Hook: sincronizar-aluno.php
 * Sincroniza aluno para o EAD quando criado/atualizado no sistema
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../classes/SincronizacaoEAD.php';

/**
 * Sincroniza um aluno para o EAD
 * @param int $aluno_id ID do aluno
 * @return bool
 */
function sincronizarAlunoEAD($aluno_id) {
    try {
        $conn = getDBConnection();
        $sincronizacao = new SincronizacaoEAD($conn);
        
        $resultado = $sincronizacao->sincronizarAluno($aluno_id);
        
        $conn->close();
        
        return $resultado;
    } catch (Exception $e) {
        error_log("Erro ao sincronizar aluno {$aluno_id}: " . $e->getMessage());
        return false;
    }
}

/**
 * Sincroniza um curso para o EAD
 * @param int $curso_id ID do curso
 * @return bool
 */
function sincronizarCursoEAD($curso_id) {
    try {
        $conn = getDBConnection();
        $sincronizacao = new SincronizacaoEAD($conn);
        
        $resultado = $sincronizacao->sincronizarCurso($curso_id);
        
        $conn->close();
        
        return $resultado;
    } catch (Exception $e) {
        error_log("Erro ao sincronizar curso {$curso_id}: " . $e->getMessage());
        return false;
    }
}

/**
 * Sincroniza uma inscrição para o EAD
 * @param int $inscricao_id ID da inscrição
 * @return bool
 */
function sincronizarInscricaoEAD($inscricao_id) {
    try {
        $conn = getDBConnection();
        $sincronizacao = new SincronizacaoEAD($conn);
        
        $resultado = $sincronizacao->sincronizarInscricao($inscricao_id);
        
        $conn->close();
        
        return $resultado;
    } catch (Exception $e) {
        error_log("Erro ao sincronizar inscrição {$inscricao_id}: " . $e->getMessage());
        return false;
    }
}

/**
 * Sincroniza todos os dados de um parceiro
 * @param int $parceiro_id ID do parceiro
 * @return array
 */
function sincronizarParceiroEAD($parceiro_id) {
    try {
        $conn = getDBConnection();
        $sincronizacao = new SincronizacaoEAD($conn);
        
        $resultado = $sincronizacao->sincronizarParceiro($parceiro_id);
        
        $conn->close();
        
        return $resultado;
    } catch (Exception $e) {
        error_log("Erro ao sincronizar parceiro {$parceiro_id}: " . $e->getMessage());
        return [
            'alunos_sincronizados' => 0,
            'cursos_sincronizados' => 0,
            'inscricoes_sincronizadas' => 0,
            'erros' => [$e->getMessage()]
        ];
    }
}
?>

