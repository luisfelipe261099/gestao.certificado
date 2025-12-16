<?php
/**
 * Modelo de Aluno
 * Sistema EAD Pro
 */

class Aluno {
    private $pdo;
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
    }
    
    /**
     * Obter alunos inscritos em um curso
     */
    public function obter_por_curso($curso_id, $filtro = null) {
        try {
            $sql = '
                SELECT a.id, a.nome, a.email, ia.frequencia as progresso, ia.status, ia.data_inscricao, ia.nota_final
                FROM inscricoes_alunos ia
                JOIN alunos a ON ia.aluno_id = a.id
                WHERE ia.curso_id = ?
            ';
            $params = [$curso_id];

            if ($filtro) {
                $sql .= ' AND (a.nome LIKE ? OR a.email LIKE ?)';
                $params[] = "%$filtro%";
                $params[] = "%$filtro%";
            }

            $sql .= ' ORDER BY ia.data_inscricao DESC';

            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);

            return $stmt->fetchAll();
        } catch (Exception $e) {
            return [];
        }
    }
    
    /**
     * Obter aluno por ID
     */
    public function obter_por_id($id) {
        try {
            $stmt = $this->pdo->prepare('SELECT * FROM alunos WHERE id = ? AND ativo = 1');
            $stmt->execute([$id]);
            return $stmt->fetch();
        } catch (Exception $e) {
            return null;
        }
    }
    
    /**
     * Obter progresso do aluno em um curso
     */
    public function obter_progresso($aluno_id, $curso_id) {
        try {
            $stmt = $this->pdo->prepare('
                SELECT * FROM inscricoes_alunos
                WHERE aluno_id = ? AND curso_id = ?
            ');
            $stmt->execute([$aluno_id, $curso_id]);
            return $stmt->fetch();
        } catch (Exception $e) {
            return null;
        }
    }
    
    /**
     * Atualizar progresso do aluno
     */
    public function atualizar_progresso($aluno_id, $curso_id, $progresso) {
        try {
            $stmt = $this->pdo->prepare('
                UPDATE inscricoes 
                SET progresso = ? 
                WHERE aluno_id = ? AND curso_id = ?
            ');
            $stmt->execute([$progresso, $aluno_id, $curso_id]);
            return ['sucesso' => true];
        } catch (Exception $e) {
            return ['sucesso' => false, 'erro' => $e->getMessage()];
        }
    }
    
    /**
     * Obter estatísticas do aluno
     */
    public function obter_estatisticas($aluno_id) {
        try {
            $stats = [
                'total_cursos' => 0,
                'cursos_concluidos' => 0,
                'progresso_medio' => 0
            ];

            // Total de cursos
            $stmt = $this->pdo->prepare('
                SELECT COUNT(*) as total FROM inscricoes_alunos
                WHERE aluno_id = ? AND status IN ("inscrito", "em_progresso")
            ');
            $stmt->execute([$aluno_id]);
            $result = $stmt->fetch();
            $stats['total_cursos'] = $result['total'] ?? 0;

            // Cursos concluídos
            $stmt = $this->pdo->prepare('
                SELECT COUNT(*) as total FROM inscricoes_alunos
                WHERE aluno_id = ? AND status = "concluido"
            ');
            $stmt->execute([$aluno_id]);
            $result = $stmt->fetch();
            $stats['cursos_concluidos'] = $result['total'] ?? 0;

            // Progresso médio (usando frequência)
            $stmt = $this->pdo->prepare('
                SELECT AVG(frequencia) as media FROM inscricoes_alunos
                WHERE aluno_id = ? AND status IN ("inscrito", "em_progresso")
            ');
            $stmt->execute([$aluno_id]);
            $result = $stmt->fetch();
            $stats['progresso_medio'] = round($result['media'] ?? 0, 2);

            return $stats;
        } catch (Exception $e) {
            return [
                'total_cursos' => 0,
                'cursos_concluidos' => 0,
                'progresso_medio' => 0
            ];
        }
    }
    
    /**
     * Inscrever aluno em um curso
     */
    public function inscrever($aluno_id, $curso_id) {
        try {
            // Verificar se já está inscrito
            $stmt = $this->pdo->prepare('
                SELECT id FROM inscricoes 
                WHERE aluno_id = ? AND curso_id = ?
            ');
            $stmt->execute([$aluno_id, $curso_id]);
            
            if ($stmt->fetch()) {
                return ['sucesso' => false, 'erro' => 'Aluno já está inscrito neste curso'];
            }
            
            // Inscrever
            $stmt = $this->pdo->prepare('
                INSERT INTO inscricoes (aluno_id, curso_id, status, progresso)
                VALUES (?, ?, "ativo", 0)
            ');
            $stmt->execute([$aluno_id, $curso_id]);
            
            return ['sucesso' => true, 'id' => $this->pdo->lastInsertId()];
        } catch (Exception $e) {
            return ['sucesso' => false, 'erro' => $e->getMessage()];
        }
    }
    
    /**
     * Remover inscrição
     */
    public function remover_inscricao($aluno_id, $curso_id) {
        try {
            $stmt = $this->pdo->prepare('
                UPDATE inscricoes
                SET status = "cancelado"
                WHERE aluno_id = ? AND curso_id = ?
            ');
            $stmt->execute([$aluno_id, $curso_id]);
            return ['sucesso' => true];
        } catch (Exception $e) {
            return ['sucesso' => false, 'erro' => $e->getMessage()];
        }
    }

    /**
     * Login do aluno
     */
    public function login($email, $senha) {
        try {
            $stmt = $this->pdo->prepare('SELECT * FROM alunos WHERE email = ? AND ativo = 1');
            $stmt->execute([$email]);
            $aluno = $stmt->fetch();

            if (!$aluno) {
                return ['sucesso' => false, 'mensagem' => 'Email ou senha incorretos'];
            }

            if (!isset($aluno['senha_hash']) || empty($aluno['senha_hash'])) {
                return ['sucesso' => false, 'mensagem' => 'Aluno sem credenciais de acesso'];
            }

            if (!password_verify($senha, $aluno['senha_hash'])) {
                return ['sucesso' => false, 'mensagem' => 'Email ou senha incorretos'];
            }

            return ['sucesso' => true, 'aluno' => $aluno];
        } catch (Exception $e) {
            return ['sucesso' => false, 'mensagem' => $e->getMessage()];
        }
    }
}
?>

