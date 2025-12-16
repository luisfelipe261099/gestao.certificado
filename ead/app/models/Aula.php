<?php
/**
 * Modelo de Aula
 * Sistema EAD Pro
 */

class Aula {
    private $pdo;
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
    }
    
    /**
     * Criar nova aula
     */
    public function criar($dados) {
        try {
            $stmt = $this->pdo->prepare('
                INSERT INTO aulas (
                    curso_id, titulo, descricao, ordem, duracao_minutos, ativa
                ) VALUES (?, ?, ?, ?, ?, ?)
            ');
            
            $stmt->execute([
                $dados['curso_id'],
                $dados['titulo'],
                $dados['descricao'] ?? null,
                $dados['ordem'] ?? 1,
                $dados['duracao_minutos'] ?? null,
                $dados['ativa'] ?? true
            ]);
            
            return ['sucesso' => true, 'id' => $this->pdo->lastInsertId()];
        } catch (Exception $e) {
            return ['sucesso' => false, 'erro' => $e->getMessage()];
        }
    }
    
    /**
     * Obter aulas do curso
     */
    public function obter_por_curso($curso_id) {
        try {
            $stmt = $this->pdo->prepare('
                SELECT * FROM aulas
                WHERE curso_id = ? AND ativa = 1
                ORDER BY ordem ASC
            ');
            $stmt->execute([$curso_id]);
            return $stmt->fetchAll();
        } catch (Exception $e) {
            return [];
        }
    }

    /**
     * Obter aula por ID
     */
    public function obter_por_id($id) {
        try {
            $stmt = $this->pdo->prepare('SELECT * FROM aulas WHERE id = ? AND ativa = 1');
            $stmt->execute([$id]);
            return $stmt->fetch();
        } catch (Exception $e) {
            return null;
        }
    }
    
    /**
     * Atualizar aula
     */
    public function atualizar($id, $dados) {
        try {
            $campos = [];
            $valores = [];
            
            foreach ($dados as $campo => $valor) {
                if (in_array($campo, ['titulo', 'descricao', 'ordem', 'duracao_minutos', 'ativa'])) {
                    $campos[] = "$campo = ?";
                    $valores[] = $valor;
                }
            }
            
            if (empty($campos)) {
                return ['sucesso' => false, 'erro' => 'Nenhum campo para atualizar'];
            }
            
            $valores[] = $id;
            $sql = 'UPDATE aulas SET ' . implode(', ', $campos) . ' WHERE id = ?';
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($valores);
            
            return ['sucesso' => true];
        } catch (Exception $e) {
            return ['sucesso' => false, 'erro' => $e->getMessage()];
        }
    }
    
    /**
     * Deletar aula (soft delete)
     */
    public function deletar($id) {
        try {
            $stmt = $this->pdo->prepare('UPDATE aulas SET ativa = 0 WHERE id = ?');
            $stmt->execute([$id]);
            return ['sucesso' => true];
        } catch (Exception $e) {
            return ['sucesso' => false, 'erro' => $e->getMessage()];
        }
    }
    
    /**
     * Reordenar aulas
     */
    public function reordenar($curso_id, $ordem_array) {
        try {
            foreach ($ordem_array as $index => $aula_id) {
                $stmt = $this->pdo->prepare('UPDATE aulas SET ordem = ? WHERE id = ? AND curso_id = ?');
                $stmt->execute([$index + 1, $aula_id, $curso_id]);
            }
            return ['sucesso' => true];
        } catch (Exception $e) {
            return ['sucesso' => false, 'erro' => $e->getMessage()];
        }
    }
}
?>

