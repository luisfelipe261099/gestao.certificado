<?php
/**
 * Modelo Contrato
 * Gerencia termos de serviço e contratos assinados
 */

class Contrato {
    private $conn;

    public function __construct($connection) {
        $this->conn = $connection;
    }

    /**
     * Obter IP real do cliente (considerando proxies)
     */
    private function obter_ip_real() {
        // Verificar se está atrás de um proxy
        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            $ip = $_SERVER['HTTP_CLIENT_IP'];
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            // Pode conter múltiplos IPs, pegar o primeiro
            $ips = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
            $ip = trim($ips[0]);
        } elseif (!empty($_SERVER['REMOTE_ADDR'])) {
            $ip = $_SERVER['REMOTE_ADDR'];
        } else {
            $ip = 'Desconhecido';
        }

        // Validar se é um IP válido
        if (filter_var($ip, FILTER_VALIDATE_IP)) {
            return $ip;
        }

        // Se for localhost (::1 ou 127.0.0.1), tentar obter do header X-Forwarded-For
        if ($ip === '::1' || $ip === '127.0.0.1') {
            if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
                $ips = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
                $real_ip = trim($ips[0]);
                if (filter_var($real_ip, FILTER_VALIDATE_IP)) {
                    return $real_ip;
                }
            }
        }

        return $ip;
    }
    
    /**
     * Obter termo de serviço ativo
     */
    public function obter_termo_ativo($tipo = 'termos_gerais') {
        $stmt = $this->conn->prepare("
            SELECT * FROM termos_servico 
            WHERE tipo = ? AND ativo = 1 
            ORDER BY versao DESC 
            LIMIT 1
        ");
        $stmt->bind_param("s", $tipo);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }
    
    /**
     * Obter termo por ID
     */
    public function obter_termo($id) {
        $stmt = $this->conn->prepare("SELECT * FROM termos_servico WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }
    
    /**
     * Criar novo termo de serviço
     */
    public function criar_termo($dados) {
        $stmt = $this->conn->prepare("
            INSERT INTO termos_servico (titulo, conteudo, tipo, versao)
            VALUES (?, ?, ?, ?)
        ");
        
        $stmt->bind_param("sssi", 
            $dados['titulo'],
            $dados['conteudo'],
            $dados['tipo'],
            $dados['versao']
        );
        
        return $stmt->execute();
    }
    
    /**
     * Verificar se usuário já aceitou os termos
     */
    public function usuario_aceitou_termos($usuario_id, $tipo_usuario, $tipo_termo = 'termos_gerais') {
        $stmt = $this->conn->prepare("
            SELECT id FROM contratos_assinados 
            WHERE usuario_id = ? 
            AND tipo_usuario = ? 
            AND termo_id = (
                SELECT id FROM termos_servico 
                WHERE tipo = ? AND ativo = 1 
                ORDER BY versao DESC LIMIT 1
            )
            AND ativo = 1
            LIMIT 1
        ");
        
        $stmt->bind_param("iss", $usuario_id, $tipo_usuario, $tipo_termo);
        $stmt->execute();
        return $stmt->get_result()->num_rows > 0;
    }
    
    /**
     * Registrar assinatura de contrato
     */
    public function registrar_assinatura($dados) {
        $stmt = $this->conn->prepare("
            INSERT INTO contratos_assinados
            (usuario_id, tipo_usuario, termo_id, plano_id, assinatura_digital, ip_assinatura, user_agent)
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");

        $ip = $this->obter_ip_real();
        $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';

        // Corrigir bind_param: i=int, s=string
        // usuario_id (int), tipo_usuario (string), termo_id (int), plano_id (int), assinatura_digital (string), ip (string), user_agent (string)
        $stmt->bind_param("isiisss",
            $dados['usuario_id'],
            $dados['tipo_usuario'],
            $dados['termo_id'],
            $dados['plano_id'],
            $dados['assinatura_digital'],
            $ip,
            $user_agent
        );

        if ($stmt->execute()) {
            $contrato_id = $this->conn->insert_id;

            // Registrar no histórico
            $this->registrar_historico($contrato_id, $dados['usuario_id'], $dados['tipo_usuario'], 'assinado', 'Contrato assinado');

            return $contrato_id;
        }

        return false;
    }
    
    /**
     * Registrar no histórico
     */
    public function registrar_historico($contrato_id, $usuario_id, $tipo_usuario, $acao, $descricao = null) {
        $stmt = $this->conn->prepare("
            INSERT INTO historico_contratos 
            (contrato_id, usuario_id, tipo_usuario, acao, descricao)
            VALUES (?, ?, ?, ?, ?)
        ");
        
        $stmt->bind_param("iisss", $contrato_id, $usuario_id, $tipo_usuario, $acao, $descricao);
        return $stmt->execute();
    }
    
    /**
     * Obter contratos assinados por usuário
     */
    public function obter_contratos_usuario($usuario_id, $tipo_usuario) {
        $stmt = $this->conn->prepare("
            SELECT ca.*, ts.titulo, ts.tipo, ts.versao
            FROM contratos_assinados ca
            JOIN termos_servico ts ON ca.termo_id = ts.id
            WHERE ca.usuario_id = ? AND ca.tipo_usuario = ?
            ORDER BY ca.data_assinatura DESC
        ");
        
        $stmt->bind_param("is", $usuario_id, $tipo_usuario);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }
    
    /**
     * Obter histórico de contrato
     */
    public function obter_historico($contrato_id) {
        $stmt = $this->conn->prepare("
            SELECT * FROM historico_contratos 
            WHERE contrato_id = ? 
            ORDER BY criado_em DESC
        ");
        
        $stmt->bind_param("i", $contrato_id);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }
    
    /**
     * Atualizar status de termos_aceitos na tabela do usuário
     */
    public function marcar_termos_aceitos($usuario_id, $tipo_usuario) {
        $tabela = '';
        
        switch($tipo_usuario) {
            case 'admin':
                $tabela = 'administradores';
                break;
            case 'parceiro':
                $tabela = 'parceiros';
                break;
            case 'aluno':
                $tabela = 'alunos';
                break;
            default:
                return false;
        }
        
        $sql = "UPDATE $tabela SET termos_aceitos = 1 WHERE id = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $usuario_id);
        return $stmt->execute();
    }
    
    /**
     * Verificar se precisa aceitar novos termos (mudança de plano)
     */
    public function precisa_aceitar_novo_termo($usuario_id, $tipo_usuario, $plano_id = null) {
        // Se mudou de plano, precisa aceitar novo contrato
        if ($plano_id) {
            $stmt = $this->conn->prepare("
                SELECT id FROM contratos_assinados 
                WHERE usuario_id = ? 
                AND tipo_usuario = ? 
                AND plano_id = ? 
                AND ativo = 1
                LIMIT 1
            ");
            
            $stmt->bind_param("isi", $usuario_id, $tipo_usuario, $plano_id);
            $stmt->execute();
            return $stmt->get_result()->num_rows === 0;
        }
        
        return false;
    }
}
?>

