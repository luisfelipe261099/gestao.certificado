<?php
/**
 * Modelo de Conteúdo de Aula
 * Sistema EAD Pro
 */

class ConteudoAula {
    private $pdo;
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
    }
    
    /**
     * Criar novo conteúdo
     */
    public function criar($dados) {
        try {
            $sql = '
                INSERT INTO conteudo_aulas
                (aula_id, tipo, titulo, descricao, url_arquivo, ordem)
                VALUES (?, ?, ?, ?, ?, ?)
            ';

            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([
                $dados['aula_id'],
                $dados['tipo'],
                $dados['titulo'],
                $dados['descricao'] ?? null,
                $dados['url_arquivo'] ?? null,
                $dados['ordem'] ?? 1
            ]);

            return ['sucesso' => true, 'id' => $this->pdo->lastInsertId()];
        } catch (Exception $e) {
            return ['sucesso' => false, 'erro' => $e->getMessage()];
        }
    }
    
    /**
     * Obter conteúdo por aula
     */
    public function obter_por_aula($aula_id) {
        try {
            $stmt = $this->pdo->prepare('
                SELECT * FROM conteudo_aulas
                WHERE aula_id = ?
                ORDER BY ordem ASC
            ');
            $stmt->execute([$aula_id]);
            return $stmt->fetchAll();
        } catch (Exception $e) {
            return [];
        }
    }
    
    /**
     * Obter conteúdo por ID
     */
    public function obter_por_id($id) {
        try {
            $stmt = $this->pdo->prepare('SELECT * FROM conteudo_aulas WHERE id = ?');
            $stmt->execute([$id]);
            return $stmt->fetch();
        } catch (Exception $e) {
            return null;
        }
    }
    
    /**
     * Atualizar conteúdo
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
            
            $sql = 'UPDATE conteudo_aulas SET ' . implode(', ', $campos) . ' WHERE id = ?';
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($valores);
            
            return ['sucesso' => true];
        } catch (Exception $e) {
            return ['sucesso' => false, 'erro' => $e->getMessage()];
        }
    }
    
    /**
     * Deletar conteúdo
     */
    public function deletar($id) {
        try {
            $stmt = $this->pdo->prepare('DELETE FROM conteudo_aulas WHERE id = ?');
            $stmt->execute([$id]);
            return ['sucesso' => true];
        } catch (Exception $e) {
            return ['sucesso' => false, 'erro' => $e->getMessage()];
        }
    }
    
    /**
     * Obter conteúdo por tipo
     */
    public function obter_por_tipo($aula_id, $tipo) {
        try {
            $stmt = $this->pdo->prepare('
                SELECT * FROM conteudo_aulas
                WHERE aula_id = ? AND tipo = ?
                ORDER BY ordem ASC
            ');
            $stmt->execute([$aula_id, $tipo]);
            return $stmt->fetchAll();
        } catch (Exception $e) {
            return [];
        }
    }
    
    /**
     * Reordenar conteúdo
     */
    public function reordenar($aula_id, $ordem_array) {
        try {
            foreach ($ordem_array as $ordem => $id) {
                $stmt = $this->pdo->prepare('UPDATE conteudo_aulas SET ordem = ? WHERE id = ?');
                $stmt->execute([$ordem + 1, $id]);
            }
            return ['sucesso' => true];
        } catch (Exception $e) {
            return ['sucesso' => false, 'erro' => $e->getMessage()];
        }
    }
}
?>

