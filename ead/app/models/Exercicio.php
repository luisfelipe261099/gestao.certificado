<?php
/**
 * Modelo de Exercício
 * Sistema EAD Pro
 */

class Exercicio {
    private $pdo;
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
    }
    
    /**
     * Criar novo exercício
     */
    public function criar($dados) {
        try {
            $sql = '
                INSERT INTO exercicios
                (aula_id, titulo, descricao, tipo, pontuacao_maxima, ativo)
                VALUES (?, ?, ?, ?, ?, 1)
            ';

            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([
                $dados['aula_id'],
                $dados['titulo'],
                $dados['descricao'] ?? null,
                $dados['tipo'] ?? 'multipla_escolha',
                $dados['pontuacao_maxima'] ?? 10
            ]);

            return ['sucesso' => true, 'id' => $this->pdo->lastInsertId()];
        } catch (Exception $e) {
            return ['sucesso' => false, 'erro' => $e->getMessage()];
        }
    }

    /**
     * Obter exercícios por aula
     */
    public function obter_por_aula($aula_id) {
        try {
            $stmt = $this->pdo->prepare('
                SELECT * FROM exercicios
                WHERE aula_id = ? AND ativo = 1
                ORDER BY id DESC
            ');
            $stmt->execute([$aula_id]);
            return $stmt->fetchAll();
        } catch (Exception $e) {
            return [];
        }
    }
    
    /**
     * Obter exercício por ID
     */
    public function obter_por_id($id) {
        try {
            $stmt = $this->pdo->prepare('SELECT * FROM exercicios WHERE id = ?');
            $stmt->execute([$id]);
            return $stmt->fetch();
        } catch (Exception $e) {
            return null;
        }
    }
    
    /**
     * Atualizar exercício
     */
    public function atualizar($id, $dados) {
        try {
            $campos = [];
            $valores = [];
            
            foreach ($dados as $campo => $valor) {
                $campos[] = "$campo = ?";
                $valores[] = $valor;
            }
            
            $valores[] = $id;
            
            $sql = 'UPDATE exercicios SET ' . implode(', ', $campos) . ' WHERE id = ?';
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($valores);
            
            return ['sucesso' => true];
        } catch (Exception $e) {
            return ['sucesso' => false, 'erro' => $e->getMessage()];
        }
    }
    
    /**
     * Deletar exercício (soft delete)
     */
    public function deletar($id) {
        try {
            $stmt = $this->pdo->prepare('UPDATE exercicios SET ativo = 0 WHERE id = ?');
            $stmt->execute([$id]);
            return ['sucesso' => true];
        } catch (Exception $e) {
            return ['sucesso' => false, 'erro' => $e->getMessage()];
        }
    }
    
    /**
     * Obter estatísticas do exercício
     */
    public function obter_estatisticas($exercicio_id) {
        try {
            $stats = [];
            
            // Total de respostas
            $stmt = $this->pdo->prepare('
                SELECT COUNT(*) as total FROM respostas_exercicios 
                WHERE exercicio_id = ?
            ');
            $stmt->execute([$exercicio_id]);
            $stats['total_respostas'] = $stmt->fetch()['total'];
            
            // Média de pontuação
            $stmt = $this->pdo->prepare('
                SELECT AVG(pontuacao) as media FROM respostas_exercicios 
                WHERE exercicio_id = ?
            ');
            $stmt->execute([$exercicio_id]);
            $stats['media_pontuacao'] = round($stmt->fetch()['media'] ?? 0, 2);
            
            return $stats;
        } catch (Exception $e) {
            return [];
        }
    }
}
?>

