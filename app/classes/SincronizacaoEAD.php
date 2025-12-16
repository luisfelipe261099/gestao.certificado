<?php
/**
 * Classe: SincronizacaoEAD
 * Responsável por sincronizar dados entre Sistema de Parceiro e EAD
 */

class SincronizacaoEAD {
    
    private $conn;
    
    public function __construct($connection) {
        $this->conn = $connection;
    }
    
    /**
     * Sincroniza um aluno para o EAD
     * @param int $aluno_id ID do aluno
     * @return bool
     */
    public function sincronizarAluno($aluno_id) {
        // Busca dados do aluno
        $query = "SELECT * FROM alunos WHERE id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("i", $aluno_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            return false;
        }
        
        $aluno = $result->fetch_assoc();
        
        // Verifica se já existe no EAD
        $check_query = "SELECT id FROM alunos WHERE email = ? AND parceiro_id = ?";
        $check_stmt = $this->conn->prepare($check_query);
        $check_stmt->bind_param("si", $aluno['email'], $aluno['parceiro_id']);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();
        
        if ($check_result->num_rows > 0) {
            // Já existe, atualiza
            $existing = $check_result->fetch_assoc();
            $id_ead = $existing['id'];
            
            $update_query = "
                UPDATE alunos 
                SET nome = ?, cpf = ?, telefone = ?, ativo = ?
                WHERE id = ?
            ";
            $update_stmt = $this->conn->prepare($update_query);
            $update_stmt->bind_param("sssii", 
                $aluno['nome'], 
                $aluno['cpf'], 
                $aluno['telefone'], 
                $aluno['ativo'],
                $id_ead
            );
            $update_stmt->execute();
        } else {
            // Novo aluno, insere
            $insert_query = "
                INSERT INTO alunos (parceiro_id, nome, email, cpf, telefone, ativo)
                VALUES (?, ?, ?, ?, ?, ?)
            ";
            $insert_stmt = $this->conn->prepare($insert_query);
            $insert_stmt->bind_param("issssi",
                $aluno['parceiro_id'],
                $aluno['nome'],
                $aluno['email'],
                $aluno['cpf'],
                $aluno['telefone'],
                $aluno['ativo']
            );
            $insert_stmt->execute();
            $id_ead = $this->conn->insert_id;
        }
        
        // Marca como sincronizado
        $sync_query = "UPDATE alunos SET ead_sincronizado = 1, id_ead = ? WHERE id = ?";
        $sync_stmt = $this->conn->prepare($sync_query);
        $sync_stmt->bind_param("ii", $id_ead, $aluno_id);
        $sync_stmt->execute();
        
        return true;
    }
    
    /**
     * Sincroniza um curso para o EAD
     * @param int $curso_id ID do curso
     * @return bool
     */
    public function sincronizarCurso($curso_id) {
        // Busca dados do curso
        $query = "SELECT * FROM cursos WHERE id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("i", $curso_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            return false;
        }
        
        $curso = $result->fetch_assoc();
        
        // Verifica se já existe no EAD
        $check_query = "SELECT id FROM cursos WHERE nome = ? AND parceiro_id = ?";
        $check_stmt = $this->conn->prepare($check_query);
        $check_stmt->bind_param("si", $curso['nome'], $curso['parceiro_id']);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();
        
        if ($check_result->num_rows > 0) {
            // Já existe, atualiza
            $existing = $check_result->fetch_assoc();
            $id_ead = $existing['id'];
            
            $update_query = "
                UPDATE cursos 
                SET descricao = ?, carga_horaria = ?, ativo = ?
                WHERE id = ?
            ";
            $update_stmt = $this->conn->prepare($update_query);
            $update_stmt->bind_param("siii",
                $curso['descricao'],
                $curso['carga_horaria'],
                $curso['ativo'],
                $id_ead
            );
            $update_stmt->execute();
        } else {
            // Novo curso, insere
            $insert_query = "
                INSERT INTO cursos (parceiro_id, nome, descricao, carga_horaria, ativo)
                VALUES (?, ?, ?, ?, ?)
            ";
            $insert_stmt = $this->conn->prepare($insert_query);
            $insert_stmt->bind_param("issii",
                $curso['parceiro_id'],
                $curso['nome'],
                $curso['descricao'],
                $curso['carga_horaria'],
                $curso['ativo']
            );
            $insert_stmt->execute();
            $id_ead = $this->conn->insert_id;
        }
        
        // Marca como sincronizado
        $sync_query = "UPDATE cursos SET ead_sincronizado = 1, id_ead = ? WHERE id = ?";
        $sync_stmt = $this->conn->prepare($sync_query);
        $sync_stmt->bind_param("ii", $id_ead, $curso_id);
        $sync_stmt->execute();
        
        return true;
    }
    
    /**
     * Sincroniza uma inscrição para o EAD
     * @param int $inscricao_id ID da inscrição
     * @return bool
     */
    public function sincronizarInscricao($inscricao_id) {
        // Busca dados da inscrição
        $query = "SELECT * FROM inscricoes_alunos WHERE id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("i", $inscricao_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            return false;
        }
        
        $inscricao = $result->fetch_assoc();
        
        // Busca IDs do EAD
        $aluno_query = "SELECT id_ead FROM alunos WHERE id = ?";
        $aluno_stmt = $this->conn->prepare($aluno_query);
        $aluno_stmt->bind_param("i", $inscricao['aluno_id']);
        $aluno_stmt->execute();
        $aluno_result = $aluno_stmt->get_result();
        $aluno_ead = $aluno_result->fetch_assoc();
        
        $curso_query = "SELECT id_ead FROM cursos WHERE id = ?";
        $curso_stmt = $this->conn->prepare($curso_query);
        $curso_stmt->bind_param("i", $inscricao['curso_id']);
        $curso_stmt->execute();
        $curso_result = $curso_stmt->get_result();
        $curso_ead = $curso_result->fetch_assoc();
        
        if (!$aluno_ead['id_ead'] || !$curso_ead['id_ead']) {
            return false;
        }
        
        // Verifica se já existe
        $check_query = "SELECT id FROM inscricoes_alunos WHERE aluno_id = ? AND curso_id = ?";
        $check_stmt = $this->conn->prepare($check_query);
        $check_stmt->bind_param("ii", $aluno_ead['id_ead'], $curso_ead['id_ead']);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();
        
        if ($check_result->num_rows > 0) {
            // Já existe
            $existing = $check_result->fetch_assoc();
            $id_ead_inscricao = $existing['id'];
        } else {
            // Nova inscrição
            $insert_query = "
                INSERT INTO inscricoes_alunos (aluno_id, curso_id, data_inscricao, status)
                VALUES (?, ?, NOW(), 'ativa')
            ";
            $insert_stmt = $this->conn->prepare($insert_query);
            $insert_stmt->bind_param("ii", $aluno_ead['id_ead'], $curso_ead['id_ead']);
            $insert_stmt->execute();
            $id_ead_inscricao = $this->conn->insert_id;
        }
        
        // Marca como sincronizado
        $sync_query = "UPDATE inscricoes_alunos SET ead_sincronizado = 1, id_ead_inscricao = ? WHERE id = ?";
        $sync_stmt = $this->conn->prepare($sync_query);
        $sync_stmt->bind_param("ii", $id_ead_inscricao, $inscricao_id);
        $sync_stmt->execute();
        
        return true;
    }
    
    /**
     * Sincroniza todos os dados não sincronizados de um parceiro
     * @param int $parceiro_id ID do parceiro
     * @return array Resultado da sincronização
     */
    public function sincronizarParceiro($parceiro_id) {
        $resultado = [
            'alunos_sincronizados' => 0,
            'cursos_sincronizados' => 0,
            'inscricoes_sincronizadas' => 0,
            'erros' => []
        ];
        
        // Sincroniza alunos
        $alunos_query = "SELECT id FROM alunos WHERE parceiro_id = ? AND ead_sincronizado = 0";
        $alunos_stmt = $this->conn->prepare($alunos_query);
        $alunos_stmt->bind_param("i", $parceiro_id);
        $alunos_stmt->execute();
        $alunos_result = $alunos_stmt->get_result();
        
        while ($aluno = $alunos_result->fetch_assoc()) {
            if ($this->sincronizarAluno($aluno['id'])) {
                $resultado['alunos_sincronizados']++;
            } else {
                $resultado['erros'][] = "Erro ao sincronizar aluno {$aluno['id']}";
            }
        }
        
        // Sincroniza cursos
        $cursos_query = "SELECT id FROM cursos WHERE parceiro_id = ? AND ead_sincronizado = 0";
        $cursos_stmt = $this->conn->prepare($cursos_query);
        $cursos_stmt->bind_param("i", $parceiro_id);
        $cursos_stmt->execute();
        $cursos_result = $cursos_stmt->get_result();
        
        while ($curso = $cursos_result->fetch_assoc()) {
            if ($this->sincronizarCurso($curso['id'])) {
                $resultado['cursos_sincronizados']++;
            } else {
                $resultado['erros'][] = "Erro ao sincronizar curso {$curso['id']}";
            }
        }
        
        // Sincroniza inscrições
        $inscricoes_query = "
            SELECT ia.id FROM inscricoes_alunos ia
            JOIN alunos a ON ia.aluno_id = a.id
            WHERE a.parceiro_id = ? AND ia.ead_sincronizado = 0
        ";
        $inscricoes_stmt = $this->conn->prepare($inscricoes_query);
        $inscricoes_stmt->bind_param("i", $parceiro_id);
        $inscricoes_stmt->execute();
        $inscricoes_result = $inscricoes_stmt->get_result();
        
        while ($inscricao = $inscricoes_result->fetch_assoc()) {
            if ($this->sincronizarInscricao($inscricao['id'])) {
                $resultado['inscricoes_sincronizadas']++;
            } else {
                $resultado['erros'][] = "Erro ao sincronizar inscrição {$inscricao['id']}";
            }
        }
        
        return $resultado;
    }
}
?>

