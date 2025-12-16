<?php
/**
 * Modelo de Parceiro
 * Sistema EAD Pro
 */

class Parceiro {
    private $pdo;
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
    }
    
    /**
     * Registrar novo parceiro
     */
    public function registrar($dados) {
        try {
            // Validar dados obrigatórios
            if (empty($dados['nome']) || empty($dados['email']) || empty($dados['senha'])) {
                return [
                    'sucesso' => false,
                    'mensagem' => 'Nome, email e senha são obrigatórios'
                ];
            }
            
            // Validar email
            if (!filter_var($dados['email'], FILTER_VALIDATE_EMAIL)) {
                return [
                    'sucesso' => false,
                    'mensagem' => 'Email inválido'
                ];
            }
            
            // Verificar se email já existe
            $stmt = $this->pdo->prepare('SELECT id FROM parceiros WHERE email = ?');
            $stmt->execute([$dados['email']]);
            
            if ($stmt->rowCount() > 0) {
                return [
                    'sucesso' => false,
                    'mensagem' => 'Email já cadastrado'
                ];
            }
            
            // Validar força da senha
            if (strlen($dados['senha']) < 8) {
                return [
                    'sucesso' => false,
                    'mensagem' => 'Senha deve ter no mínimo 8 caracteres'
                ];
            }
            
            // Hash da senha
            $senha_hash = password_hash($dados['senha'], PASSWORD_BCRYPT, ['cost' => 12]);
            
            // Gerar token de verificação
            $token_verificacao = bin2hex(random_bytes(32));
            
            // Inserir parceiro
            $sql = 'INSERT INTO parceiros (nome, email, senha, cpf, telefone, empresa, descricao, token_verificacao) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?)';
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([
                $dados['nome'],
                $dados['email'],
                $senha_hash,
                $dados['cpf'] ?? null,
                $dados['telefone'] ?? null,
                $dados['empresa'] ?? null,
                $dados['descricao'] ?? null,
                $token_verificacao
            ]);
            
            $parceiro_id = $this->pdo->lastInsertId();
            
            return [
                'sucesso' => true,
                'mensagem' => 'Parceiro registrado com sucesso',
                'parceiro_id' => $parceiro_id,
                'token' => $token_verificacao
            ];
            
        } catch (Exception $e) {
            log_erro('Erro ao registrar parceiro: ' . $e->getMessage());
            return [
                'sucesso' => false,
                'mensagem' => 'Erro ao registrar parceiro'
            ];
        }
    }
    
    /**
     * Login do parceiro
     */
    public function login($email, $senha) {
        try {
            // Validar entrada
            if (empty($email) || empty($senha)) {
                return [
                    'sucesso' => false,
                    'mensagem' => 'Email e senha são obrigatórios'
                ];
            }
            
            // Buscar parceiro
            $stmt = $this->pdo->prepare('SELECT * FROM parceiros WHERE email = ? AND ativo = 1');
            $stmt->execute([$email]);
            
            if ($stmt->rowCount() === 0) {
                return [
                    'sucesso' => false,
                    'mensagem' => 'Email ou senha incorretos'
                ];
            }
            
            $parceiro = $stmt->fetch();
            
            // Verificar senha
            if (!password_verify($senha, $parceiro['senha'])) {
                return [
                    'sucesso' => false,
                    'mensagem' => 'Email ou senha incorretos'
                ];
            }
            
            // Verificar se email foi verificado
            if (!$parceiro['verificado']) {
                return [
                    'sucesso' => false,
                    'mensagem' => 'Email não verificado. Verifique seu email para continuar.',
                    'email_nao_verificado' => true
                ];
            }
            
            return [
                'sucesso' => true,
                'mensagem' => 'Login realizado com sucesso',
                'parceiro' => [
                    'id' => $parceiro['id'],
                    'parceiro_id' => $parceiro['id'],  // ID do parceiro (mesmo que id)
                    'nome' => $parceiro['nome'],
                    'email' => $parceiro['email'],
                    'empresa' => $parceiro['empresa'],
                    'logo_url' => $parceiro['logo_url']
                ]
            ];
            
        } catch (Exception $e) {
            log_erro('Erro ao fazer login: ' . $e->getMessage());
            return [
                'sucesso' => false,
                'mensagem' => 'Erro ao fazer login'
            ];
        }
    }
    
    /**
     * Obter parceiro por ID
     */
    public function obter_por_id($id) {
        try {
            $stmt = $this->pdo->prepare('SELECT * FROM parceiros WHERE id = ?');
            $stmt->execute([$id]);
            
            return $stmt->fetch();
            
        } catch (Exception $e) {
            log_erro('Erro ao obter parceiro: ' . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Atualizar perfil do parceiro
     */
    public function atualizar_perfil($id, $dados) {
        try {
            $campos = [];
            $valores = [];
            
            // Campos permitidos para atualização
            $campos_permitidos = ['nome', 'telefone', 'empresa', 'descricao', 'logo_url'];
            
            foreach ($campos_permitidos as $campo) {
                if (isset($dados[$campo])) {
                    $campos[] = "$campo = ?";
                    $valores[] = $dados[$campo];
                }
            }
            
            if (empty($campos)) {
                return [
                    'sucesso' => false,
                    'mensagem' => 'Nenhum campo para atualizar'
                ];
            }
            
            $valores[] = $id;
            
            $sql = 'UPDATE parceiros SET ' . implode(', ', $campos) . ', data_atualizacao = NOW() WHERE id = ?';
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($valores);
            
            return [
                'sucesso' => true,
                'mensagem' => 'Perfil atualizado com sucesso'
            ];
            
        } catch (Exception $e) {
            log_erro('Erro ao atualizar perfil: ' . $e->getMessage());
            return [
                'sucesso' => false,
                'mensagem' => 'Erro ao atualizar perfil'
            ];
        }
    }
    
    /**
     * Verificar email
     */
    public function verificar_email($token) {
        try {
            $stmt = $this->pdo->prepare('SELECT id FROM parceiros WHERE token_verificacao = ?');
            $stmt->execute([$token]);
            
            if ($stmt->rowCount() === 0) {
                return [
                    'sucesso' => false,
                    'mensagem' => 'Token inválido'
                ];
            }
            
            $parceiro = $stmt->fetch();
            
            // Atualizar status de verificação
            $stmt = $this->pdo->prepare('UPDATE parceiros SET verificado = 1, token_verificacao = NULL WHERE id = ?');
            $stmt->execute([$parceiro['id']]);
            
            return [
                'sucesso' => true,
                'mensagem' => 'Email verificado com sucesso'
            ];
            
        } catch (Exception $e) {
            log_erro('Erro ao verificar email: ' . $e->getMessage());
            return [
                'sucesso' => false,
                'mensagem' => 'Erro ao verificar email'
            ];
        }
    }
    
    /**
     * Recuperar senha
     */
    public function recuperar_senha($email) {
        try {
            $stmt = $this->pdo->prepare('SELECT id FROM parceiros WHERE email = ?');
            $stmt->execute([$email]);
            
            if ($stmt->rowCount() === 0) {
                // Não revelar se email existe ou não por segurança
                return [
                    'sucesso' => true,
                    'mensagem' => 'Se o email existe, você receberá um link de recuperação'
                ];
            }
            
            $parceiro = $stmt->fetch();
            $token = bin2hex(random_bytes(32));
            
            // Salvar token
            $stmt = $this->pdo->prepare('UPDATE parceiros SET token_verificacao = ? WHERE id = ?');
            $stmt->execute([$token, $parceiro['id']]);
            
            return [
                'sucesso' => true,
                'mensagem' => 'Se o email existe, você receberá um link de recuperação',
                'token' => $token // Em produção, enviar por email
            ];
            
        } catch (Exception $e) {
            log_erro('Erro ao recuperar senha: ' . $e->getMessage());
            return [
                'sucesso' => false,
                'mensagem' => 'Erro ao recuperar senha'
            ];
        }
    }
}

?>

