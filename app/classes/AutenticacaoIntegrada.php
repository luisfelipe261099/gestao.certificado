<?php
/**
 * Classe: AutenticacaoIntegrada
 * Responsável por gerenciar autenticação compartilhada entre Sistema e EAD
 * Usa JWT (JSON Web Tokens) para autenticação sem estado
 */

class AutenticacaoIntegrada {
    
    private $conn;
    private $secret_key = 'sua_chave_secreta_super_segura_aqui_2025'; // TODO: Mover para .env
    private $token_expiration = 3600; // 1 hora
    
    public function __construct($connection) {
        $this->conn = $connection;
    }
    
    /**
     * Gera um token JWT para acesso ao EAD
     * @param int $usuario_id ID do usuário
     * @param int $parceiro_id ID do parceiro
     * @return string Token JWT
     */
    public function gerarTokenEAD($usuario_id, $parceiro_id) {
        $header = [
            'alg' => 'HS256',
            'typ' => 'JWT'
        ];
        
        $payload = [
            'usuario_id' => $usuario_id,
            'parceiro_id' => $parceiro_id,
            'tipo' => 'parceiro',
            'iat' => time(),
            'exp' => time() + $this->token_expiration
        ];
        
        $header_encoded = $this->base64url_encode(json_encode($header));
        $payload_encoded = $this->base64url_encode(json_encode($payload));
        
        $signature = hash_hmac(
            'sha256',
            $header_encoded . '.' . $payload_encoded,
            $this->secret_key,
            true
        );
        $signature_encoded = $this->base64url_encode($signature);
        
        return $header_encoded . '.' . $payload_encoded . '.' . $signature_encoded;
    }
    
    /**
     * Valida um token JWT
     * @param string $token Token JWT
     * @return array|false Payload do token ou false se inválido
     */
    public function validarTokenEAD($token) {
        $parts = explode('.', $token);
        
        if (count($parts) !== 3) {
            return false;
        }
        
        list($header_encoded, $payload_encoded, $signature_encoded) = $parts;
        
        // Verifica a assinatura
        $signature = hash_hmac(
            'sha256',
            $header_encoded . '.' . $payload_encoded,
            $this->secret_key,
            true
        );
        $signature_expected = $this->base64url_encode($signature);
        
        if ($signature_encoded !== $signature_expected) {
            return false;
        }
        
        // Decodifica o payload
        $payload = json_decode($this->base64url_decode($payload_encoded), true);
        
        // Verifica expiração
        if ($payload['exp'] < time()) {
            return false;
        }
        
        return $payload;
    }
    
    /**
     * Cria sessão do EAD a partir de um token válido
     * @param array $payload Payload do token JWT
     * @return bool
     */
    public function criarSessaoEAD($payload) {
        // Busca dados do usuário
        $query = "
            SELECT up.id, up.email, up.nome, p.id as parceiro_id, p.nome_empresa
            FROM usuarios_parceiro up
            JOIN parceiros p ON up.parceiro_id = p.id
            WHERE up.id = ? AND p.id = ?
        ";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("ii", $payload['usuario_id'], $payload['parceiro_id']);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            return false;
        }
        
        $user = $result->fetch_assoc();
        
        // Cria sessão
        $_SESSION['ead_usuario_id'] = $user['id'];
        $_SESSION['ead_email'] = $user['email'];
        $_SESSION['ead_nome'] = $user['nome'];
        $_SESSION['ead_parceiro_id'] = $user['parceiro_id'];
        $_SESSION['ead_parceiro_nome'] = $user['nome_empresa'];
        $_SESSION['ead_autenticado'] = true;
        $_SESSION['ead_token_gerado'] = time();
        
        return true;
    }
    
    /**
     * Verifica se usuário está autenticado no EAD
     * @return bool
     */
    public function estaAutenticadoEAD() {
        return isset($_SESSION['ead_autenticado']) && $_SESSION['ead_autenticado'] === true;
    }
    
    /**
     * Obtém dados do usuário autenticado no EAD
     * @return array|null
     */
    public function obterUsuarioEAD() {
        if (!$this->estaAutenticadoEAD()) {
            return null;
        }
        
        return [
            'id' => $_SESSION['ead_usuario_id'],
            'email' => $_SESSION['ead_email'],
            'nome' => $_SESSION['ead_nome'],
            'parceiro_id' => $_SESSION['ead_parceiro_id'],
            'parceiro_nome' => $_SESSION['ead_parceiro_nome']
        ];
    }
    
    /**
     * Faz logout do EAD
     */
    public function logoutEAD() {
        unset($_SESSION['ead_usuario_id']);
        unset($_SESSION['ead_email']);
        unset($_SESSION['ead_nome']);
        unset($_SESSION['ead_parceiro_id']);
        unset($_SESSION['ead_parceiro_nome']);
        unset($_SESSION['ead_autenticado']);
        unset($_SESSION['ead_token_gerado']);
    }
    
    /**
     * Codifica string em base64url
     */
    private function base64url_encode($data) {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }
    
    /**
     * Decodifica string em base64url
     */
    private function base64url_decode($data) {
        return base64_decode(strtr($data, '-_', '+/') . str_repeat('=', 4 - strlen($data) % 4));
    }
}
?>

