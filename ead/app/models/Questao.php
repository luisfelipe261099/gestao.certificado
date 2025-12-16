<?php
/**
 * Modelo de Questão
 * Sistema EAD Pro
 */

class Questao {
    private $pdo;
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
    }
    
    /**
     * Criar nova questão
     */
    public function criar($dados) {
        try {
            $sql = '
                INSERT INTO questoes_exercicios
                (exercicio_id, titulo, descricao, tipo, ordem, pontuacao, ativo)
                VALUES (?, ?, ?, ?, ?, ?, 1)
            ';

            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([
                $dados['exercicio_id'],
                $dados['titulo'],
                $dados['descricao'] ?? null,
                $dados['tipo'] ?? 'multipla_escolha',
                $dados['ordem'] ?? 1,
                $dados['pontuacao'] ?? 1
            ]);

            return ['sucesso' => true, 'id' => $this->pdo->lastInsertId()];
        } catch (Exception $e) {
            return ['sucesso' => false, 'erro' => $e->getMessage()];
        }
    }

    /**
     * Obter questões por exercício
     */
    public function obter_por_exercicio($exercicio_id) {
        try {
            $stmt = $this->pdo->prepare('
                SELECT * FROM questoes_exercicios
                WHERE exercicio_id = ? AND ativo = 1
                ORDER BY ordem ASC
            ');
            $stmt->execute([$exercicio_id]);
            return $stmt->fetchAll();
        } catch (Exception $e) {
            return [];
        }
    }
    
    /**
     * Obter questão por ID
     */
    public function obter_por_id($id) {
        try {
            $stmt = $this->pdo->prepare('SELECT * FROM questoes_exercicios WHERE id = ?');
            $stmt->execute([$id]);
            return $stmt->fetch();
        } catch (Exception $e) {
            return null;
        }
    }
    
    /**
     * Atualizar questão
     */
    public function atualizar($id, $dados) {
        try {
            $campos = [];
            $valores = [];
            
            foreach ($dados as $campo => $valor) {
                if (in_array($campo, ['titulo', 'descricao', 'tipo', 'ordem', 'pontuacao', 'ativo'])) {
                    $campos[] = "$campo = ?";
                    $valores[] = $valor;
                }
            }
            
            if (empty($campos)) {
                return ['sucesso' => false, 'erro' => 'Nenhum campo para atualizar'];
            }
            
            $valores[] = $id;
            
            $sql = 'UPDATE questoes_exercicios SET ' . implode(', ', $campos) . ' WHERE id = ?';
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($valores);
            
            return ['sucesso' => true];
        } catch (Exception $e) {
            return ['sucesso' => false, 'erro' => $e->getMessage()];
        }
    }
    
    /**
     * Deletar questão (soft delete)
     */
    public function deletar($id) {
        try {
            $stmt = $this->pdo->prepare('UPDATE questoes_exercicios SET ativo = 0 WHERE id = ?');
            $stmt->execute([$id]);
            return ['sucesso' => true];
        } catch (Exception $e) {
            return ['sucesso' => false, 'erro' => $e->getMessage()];
        }
    }
    
    /**
     * Adicionar opção de resposta
     */
    public function adicionar_opcao($questao_id, $texto, $eh_correta = false) {
        try {
            $stmt = $this->pdo->prepare('
                INSERT INTO opcoes_questoes (questao_id, texto, eh_correta, ordem)
                VALUES (?, ?, ?, (SELECT COUNT(*) + 1 FROM opcoes_questoes WHERE questao_id = ?))
            ');
            $stmt->execute([$questao_id, $texto, $eh_correta ? 1 : 0, $questao_id]);
            
            return ['sucesso' => true, 'id' => $this->pdo->lastInsertId()];
        } catch (Exception $e) {
            return ['sucesso' => false, 'erro' => $e->getMessage()];
        }
    }
    
    /**
     * Obter opções de uma questão
     */
    public function obter_opcoes($questao_id) {
        try {
            $stmt = $this->pdo->prepare('
                SELECT * FROM opcoes_questoes
                WHERE questao_id = ?
                ORDER BY ordem ASC
            ');
            $stmt->execute([$questao_id]);
            return $stmt->fetchAll();
        } catch (Exception $e) {
            return [];
        }
    }
    
    /**
     * Deletar opção
     */
    public function deletar_opcao($opcao_id) {
        try {
            $stmt = $this->pdo->prepare('DELETE FROM opcoes_questoes WHERE id = ?');
            $stmt->execute([$opcao_id]);
            return ['sucesso' => true];
        } catch (Exception $e) {
            return ['sucesso' => false, 'erro' => $e->getMessage()];
        }
    }
}
?>

