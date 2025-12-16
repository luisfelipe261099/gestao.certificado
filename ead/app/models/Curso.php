<?php
/**
 * Modelo de Curso
 * Sistema EAD Pro
 */

class Curso {
    private $pdo;
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
    }
    
    /**
     * Criar novo curso
     */
    public function criar($dados) {
        try {
            $stmt = $this->pdo->prepare('
                INSERT INTO cursos (
                    parceiro_id, nome, descricao, carga_horaria, ativo, criado_em
                ) VALUES (?, ?, ?, ?, 1, NOW())
            ');

            $stmt->execute([
                $dados['parceiro_id'],
                $dados['nome'],
                $dados['descricao'] ?? null,
                $dados['carga_horaria'] ?? 0
            ]);

            return ['sucesso' => true, 'id' => $this->pdo->lastInsertId()];
        } catch (Exception $e) {
            return ['sucesso' => false, 'erro' => $e->getMessage()];
        }
    }
    
    /**
     * Obter todos os cursos do parceiro
     */
    public function obter_por_parceiro($parceiro_id, $filtro = null) {
        try {
            $sql = 'SELECT * FROM cursos WHERE parceiro_id = ? AND ativo = 1';
            $params = [$parceiro_id];

            if ($filtro) {
                $sql .= ' AND (nome LIKE ? OR descricao LIKE ?)';
                $params[] = "%$filtro%";
                $params[] = "%$filtro%";
            }

            $sql .= ' ORDER BY criado_em DESC';

            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);

            return $stmt->fetchAll();
        } catch (Exception $e) {
            return [];
        }
    }
    
    /**
     * Obter curso por ID
     */
    public function obter_por_id($id) {
        try {
            $stmt = $this->pdo->prepare('SELECT * FROM cursos WHERE id = ? AND ativo = 1');
            $stmt->execute([$id]);
            return $stmt->fetch();
        } catch (Exception $e) {
            return null;
        }
    }
    
    /**
     * Atualizar curso
     */
    public function atualizar($id, $dados) {
        try {
            $campos = [];
            $valores = [];

            foreach ($dados as $campo => $valor) {
                if (in_array($campo, ['nome', 'descricao', 'carga_horaria', 'ativo'])) {
                    $campos[] = "$campo = ?";
                    $valores[] = $valor;
                }
            }

            if (empty($campos)) {
                return ['sucesso' => false, 'erro' => 'Nenhum campo para atualizar'];
            }

            $valores[] = $id;
            $sql = 'UPDATE cursos SET ' . implode(', ', $campos) . ', atualizado_em = NOW() WHERE id = ?';

            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($valores);

            return ['sucesso' => true];
        } catch (Exception $e) {
            return ['sucesso' => false, 'erro' => $e->getMessage()];
        }
    }
    
    /**
     * Deletar curso (soft delete)
     */
    public function deletar($id) {
        try {
            $stmt = $this->pdo->prepare('UPDATE cursos SET ativo = 0 WHERE id = ?');
            $stmt->execute([$id]);
            return ['sucesso' => true];
        } catch (Exception $e) {
            return ['sucesso' => false, 'erro' => $e->getMessage()];
        }
    }
    
    /**
     * Obter estatísticas do curso
     */
    public function obter_estatisticas($curso_id) {
        try {
            $stats = [
                'total_alunos' => 0,
                'total_aulas' => 0,
                'progresso_medio' => 0,
                'alunos_concluidos' => 0
            ];

            // Total de alunos
            $stmt = $this->pdo->prepare('
                SELECT COUNT(*) as total FROM inscricoes_alunos
                WHERE curso_id = ? AND status IN ("inscrito", "em_progresso")
            ');
            $stmt->execute([$curso_id]);
            $result = $stmt->fetch();
            $stats['total_alunos'] = $result['total'] ?? 0;

            // Total de aulas
            $stmt = $this->pdo->prepare('
                SELECT COUNT(*) as total FROM aulas
                WHERE curso_id = ? AND ativa = 1
            ');
            $stmt->execute([$curso_id]);
            $result = $stmt->fetch();
            $stats['total_aulas'] = $result['total'] ?? 0;

            // Progresso médio (usando frequência)
            $stmt = $this->pdo->prepare('
                SELECT AVG(frequencia) as media FROM inscricoes_alunos
                WHERE curso_id = ? AND status IN ("inscrito", "em_progresso")
            ');
            $stmt->execute([$curso_id]);
            $result = $stmt->fetch();
            $stats['progresso_medio'] = round($result['media'] ?? 0, 2);

            // Alunos concluídos
            $stmt = $this->pdo->prepare('
                SELECT COUNT(*) as total FROM inscricoes_alunos
                WHERE curso_id = ? AND status = "concluido"
            ');
            $stmt->execute([$curso_id]);
            $result = $stmt->fetch();
            $stats['alunos_concluidos'] = $result['total'] ?? 0;

            return $stats;
        } catch (Exception $e) {
            return [
                'total_alunos' => 0,
                'total_aulas' => 0,
                'progresso_medio' => 0,
                'alunos_concluidos' => 0
            ];
        }
    }
}
?>

