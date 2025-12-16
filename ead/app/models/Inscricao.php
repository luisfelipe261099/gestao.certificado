<?php
/**
 * Modelo de Inscrição
 * Sistema EAD Pro
 */

class Inscricao {
    private $pdo;
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
    }
    
    /**
     * Obter inscrição por ID
     */
    public function obter_por_id($id) {
        try {
            $stmt = $this->pdo->prepare('SELECT * FROM inscricoes_alunos WHERE id = ?');
            $stmt->execute([$id]);
            return $stmt->fetch();
        } catch (Exception $e) {
            return null;
        }
    }
    
    /**
     * Obter inscrições do aluno
     */
    public function obter_por_aluno($aluno_id, $status = null) {
        try {
            $sql = 'SELECT * FROM inscricoes_alunos WHERE aluno_id = ?';
            $params = [$aluno_id];

            if ($status) {
                $sql .= ' AND status = ?';
                $params[] = $status;
            }

            $sql .= ' ORDER BY data_inscricao DESC';

            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll();
        } catch (Exception $e) {
            return [];
        }
    }

    /**
     * Obter inscrições de um curso
     */
    public function obter_por_curso($curso_id, $status = null) {
        try {
            $sql = 'SELECT * FROM inscricoes_alunos WHERE curso_id = ?';
            $params = [$curso_id];

            if ($status) {
                $sql .= ' AND status = ?';
                $params[] = $status;
            }

            $sql .= ' ORDER BY data_inscricao DESC';

            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll();
        } catch (Exception $e) {
            return [];
        }
    }
    
    /**
     * Criar inscrição
     */
    public function criar($aluno_id, $curso_id) {
        try {
            // Verificar se já está inscrito
            $stmt = $this->pdo->prepare('
                SELECT id FROM inscricoes_alunos
                WHERE aluno_id = ? AND curso_id = ?
            ');
            $stmt->execute([$aluno_id, $curso_id]);

            if ($stmt->fetch()) {
                return ['sucesso' => false, 'erro' => 'Aluno já está inscrito neste curso'];
            }

            // Criar inscrição
            $stmt = $this->pdo->prepare('
                INSERT INTO inscricoes_alunos (aluno_id, curso_id, status, frequencia)
                VALUES (?, ?, "em_progresso", 0)
            ');
            $stmt->execute([$aluno_id, $curso_id]);

            return ['sucesso' => true, 'id' => $this->pdo->lastInsertId()];
        } catch (Exception $e) {
            return ['sucesso' => false, 'erro' => $e->getMessage()];
        }
    }
    
    /**
     * Atualizar progresso
     */
    public function atualizar_progresso($inscricao_id, $progresso) {
        try {
            $stmt = $this->pdo->prepare('
                UPDATE inscricoes 
                SET progresso = ? 
                WHERE id = ?
            ');
            $stmt->execute([$progresso, $inscricao_id]);
            return ['sucesso' => true];
        } catch (Exception $e) {
            return ['sucesso' => false, 'erro' => $e->getMessage()];
        }
    }
    
    /**
     * Marcar como concluído
     */
    public function marcar_concluido($inscricao_id, $nota_final = null) {
        try {
            $stmt = $this->pdo->prepare('
                UPDATE inscricoes 
                SET status = "concluido", 
                    progresso = 100,
                    data_conclusao = NOW(),
                    nota_final = ?
                WHERE id = ?
            ');
            $stmt->execute([$nota_final, $inscricao_id]);
            return ['sucesso' => true];
        } catch (Exception $e) {
            return ['sucesso' => false, 'erro' => $e->getMessage()];
        }
    }
    
    /**
     * Cancelar inscrição
     */
    public function cancelar($inscricao_id) {
        try {
            $stmt = $this->pdo->prepare('
                UPDATE inscricoes 
                SET status = "cancelado" 
                WHERE id = ?
            ');
            $stmt->execute([$inscricao_id]);
            return ['sucesso' => true];
        } catch (Exception $e) {
            return ['sucesso' => false, 'erro' => $e->getMessage()];
        }
    }
    
    /**
     * Salvar inscrição (para depois)
     */
    public function salvar($inscricao_id) {
        try {
            $stmt = $this->pdo->prepare('
                UPDATE inscricoes 
                SET status = "salvo" 
                WHERE id = ?
            ');
            $stmt->execute([$inscricao_id]);
            return ['sucesso' => true];
        } catch (Exception $e) {
            return ['sucesso' => false, 'erro' => $e->getMessage()];
        }
    }
}
?>

