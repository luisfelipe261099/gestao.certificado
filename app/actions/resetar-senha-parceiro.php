<?php
/**
 * Action: Resetar Senha do Parceiro
 * Permite que admin resete a senha de acesso do parceiro
 */

require_once '../config/config.php';

// Verificar se é admin
if (!isAuthenticated() || !hasRole(ROLE_ADMIN)) {
    $_SESSION['error'] = 'Acesso negado.';
    header('Location: ' . APP_URL . '/login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $_SESSION['error'] = 'Método inválido.';
    header('Location: ' . APP_URL . '/admin/parceiros-admin.php');
    exit;
}

$parceiro_id = (int) ($_POST['parceiro_id'] ?? 0);
$enviar_email = isset($_POST['enviar_email']) && $_POST['enviar_email'] == '1';

if ($parceiro_id <= 0) {
    $_SESSION['error'] = 'Parceiro inválido.';
    header('Location: ' . APP_URL . '/admin/parceiros-admin.php');
    exit;
}

try {
    $conn = getDBConnection();

    // Verificar se parceiro existe
    $stmt = $conn->prepare("SELECT id, nome_empresa, email FROM parceiros WHERE id = ?");
    $stmt->bind_param("i", $parceiro_id);
    $stmt->execute();
    $parceiro = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$parceiro) {
        throw new Exception('Parceiro não encontrado.');
    }

    // Verificar se parceiro tem usuário de acesso
    $stmt = $conn->prepare("SELECT id, email FROM usuarios_parceiro WHERE parceiro_id = ? AND ativo = 1 LIMIT 1");
    $stmt->bind_param("i", $parceiro_id);
    $stmt->execute();
    $usuario = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$usuario) {
        throw new Exception('Este parceiro ainda não possui acesso ao sistema. Use o botão "Criar Acesso" primeiro.');
    }

    // Gerar nova senha (usar a fornecida pelo admin ou gerar aleatória)
    $nova_senha = trim($_POST['nova_senha'] ?? '');

    if (empty($nova_senha)) {
        // Gerar senha aleatória se não foi fornecida
        $nova_senha = bin2hex(random_bytes(4)); // Senha de 8 caracteres
    }

    // Validar tamanho mínimo
    if (strlen($nova_senha) < 6) {
        throw new Exception('A senha deve ter pelo menos 6 caracteres.');
    }

    $senha_hash = password_hash($nova_senha, PASSWORD_DEFAULT);

    // Atualizar senha
    $stmt = $conn->prepare("UPDATE usuarios_parceiro SET senha_hash = ? WHERE id = ?");
    $stmt->bind_param("si", $senha_hash, $usuario['id']);
    $stmt->execute();
    $stmt->close();

    // Enviar email se solicitado
    if ($enviar_email) {
        $to = $usuario['email'];
        $subject = "Nova Senha de Acesso - " . APP_NAME;
        $message = "
        <html>
        <head>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background: linear-gradient(135deg, #6E41C1 0%, #8B5FD6 100%); color: white; padding: 20px; text-align: center; border-radius: 8px 8px 0 0; }
                .content { background: #f9f9f9; padding: 30px; border: 1px solid #ddd; border-top: none; }
                .senha-box { background: #fff; border: 2px solid #6E41C1; padding: 15px; text-align: center; margin: 20px 0; border-radius: 8px; }
                .senha { font-size: 24px; font-weight: bold; color: #6E41C1; letter-spacing: 2px; }
                .footer { text-align: center; padding: 20px; color: #666; font-size: 12px; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h1>Sua Senha Foi Resetada</h1>
                </div>
                <div class='content'>
                    <p>Olá, <strong>{$parceiro['nome_empresa']}</strong>!</p>
                    
                    <p>Sua senha de acesso ao sistema foi resetada por um administrador.</p>
                    
                    <div class='senha-box'>
                        <p style='margin: 0 0 5px 0;'>Sua Nova Senha:</p>
                        <div class='senha'>{$nova_senha}</div>
                    </div>
                    
                    <p><strong>⚠️ IMPORTANTE:</strong></p>
                    <ul>
                        <li>Esta senha é temporária</li>
                        <li>Recomendamos que você altere sua senha após o login</li>
                        <li>Anote essa senha em local seguro</li>
                    </ul>
                    
                    <p><strong>Dados de Acesso:</strong></p>
                    <ul>
                        <li><strong>URL:</strong> " . APP_URL . "/login.php</li>
                        <li><strong>Email:</strong> {$usuario['email']}</li>
                        <li><strong>Senha:</strong> {$nova_senha}</li>
                    </ul>
                    
                    <p>Se você não solicitou esta alteração, entre em contato conosco imediatamente.</p>
                </div>
                <div class='footer'>
                    <p>Este é um email automático. Não responda a esta mensagem.</p>
                    <p>&copy; " . date('Y') . " " . APP_NAME . ". Todos os direitos reservados.</p>
                </div>
            </div>
        </body>
        </html>
        ";

        $headers = "MIME-Version: 1.0" . "\r\n";
        $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
        $headers .= "From: " . APP_NAME . " <noreply@faciencia.edu.br>" . "\r\n";

        @mail($to, $subject, $message, $headers);
    }

    // Salvar senha em sessão para exibir ao admin
    $_SESSION['nova_senha_gerada'] = $nova_senha;
    $_SESSION['nova_senha_email'] = $usuario['email'];
    $_SESSION['success'] = "Senha resetada com sucesso!";

    header('Location: ' . APP_URL . '/admin/parceiros-admin.php');
    exit;

} catch (Exception $e) {
    $_SESSION['error'] = 'Erro ao resetar senha: ' . $e->getMessage();
    header('Location: ' . APP_URL . '/admin/parceiros-admin.php');
    exit;
}
?>