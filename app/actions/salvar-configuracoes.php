<?php
/**
 * ============================================================================
 * SALVAR CONFIGURAÇÕES - SISTEMA DE CERTIFICADOS
 * ============================================================================
 */

require_once '../config/config.php';

// Verificar autenticação e permissão (apenas admin)
if (!isAuthenticated() || !hasRole(ROLE_ADMIN)) {
    $_SESSION['error'] = 'Acesso negado';
    redirect(APP_URL . '/login.php');
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect(APP_URL . '/admin/configuracoes-admin.php');
}

$conn = getDBConnection();

// Criar tabela de configurações se não existir (usando nome correto)
$conn->query("
    CREATE TABLE IF NOT EXISTS configuracoes_sistema (
        id INT AUTO_INCREMENT PRIMARY KEY,
        chave VARCHAR(100) UNIQUE NOT NULL,
        valor LONGTEXT,
        tipo ENUM('string','integer','boolean','json') DEFAULT 'string',
        descricao TEXT,
        criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        atualizado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )
");

// Função para salvar/atualizar configuração
function salvarConfiguracao($conn, $chave, $valor, $tipo = 'string', $descricao = '') {
    $stmt = $conn->prepare("
        INSERT INTO configuracoes_sistema (chave, valor, tipo, descricao)
        VALUES (?, ?, ?, ?)
        ON DUPLICATE KEY UPDATE valor = ?, tipo = ?, descricao = ?
    ");
    $stmt->bind_param("sssssss", $chave, $valor, $tipo, $descricao, $valor, $tipo, $descricao);
    $stmt->execute();
    $stmt->close();
}

try {
    // Configurações Gerais
    if (isset($_POST['site_nome'])) {
        salvarConfiguracao($conn, 'site_nome', $_POST['site_nome'], 'string', 'Nome do sistema');
    }

    if (isset($_POST['site_email'])) {
        salvarConfiguracao($conn, 'site_email', $_POST['site_email'], 'string', 'Email de contato');
    }

    if (isset($_POST['site_telefone'])) {
        salvarConfiguracao($conn, 'site_telefone', $_POST['site_telefone'], 'string', 'Telefone de contato');
    }

    // Configurações de Certificados
    if (isset($_POST['certificado_validade_dias'])) {
        salvarConfiguracao($conn, 'certificado_validade_dias', $_POST['certificado_validade_dias'], 'integer', 'Validade dos certificados em dias (0 = permanente)');
    }

    $cert_assinatura = isset($_POST['certificado_assinatura_digital']) ? '1' : '0';
    salvarConfiguracao($conn, 'certificado_assinatura_digital', $cert_assinatura, 'boolean', 'Habilitar assinatura digital');

    // Configurações de Notificações
    $email_notif = isset($_POST['email_notificacoes']) ? '1' : '0';
    salvarConfiguracao($conn, 'email_notificacoes', $email_notif, 'boolean', 'Enviar notificações por email');

    // Configurações de Sistema
    $manutencao = isset($_POST['manutencao_modo']) ? '1' : '0';
    salvarConfiguracao($conn, 'manutencao_modo', $manutencao, 'boolean', 'Modo manutenção');

    // Configurações Asaas
    if (isset($_POST['api_asaas_enabled'])) {
        $asaas_enabled = isset($_POST['api_asaas_enabled']) ? '1' : '0';
        salvarConfiguracao($conn, 'api_asaas_enabled', $asaas_enabled, 'boolean', 'Habilitar integração Asaas');
    }

    if (isset($_POST['api_asaas_ambiente'])) {
        salvarConfiguracao($conn, 'api_asaas_ambiente', $_POST['api_asaas_ambiente'], 'string', 'Ambiente Asaas (sandbox/production)');
    }

    if (isset($_POST['api_asaas_key'])) {
        // Apenas salvar se não estiver vazio
        if (!empty($_POST['api_asaas_key'])) {
            salvarConfiguracao($conn, 'api_asaas_key', $_POST['api_asaas_key'], 'string', 'API Key Asaas');
        }
    }

    $_SESSION['success'] = 'Configurações salvas com sucesso!';

} catch (Exception $e) {
    error_log("Erro ao salvar configurações: " . $e->getMessage());
    $_SESSION['error'] = 'Erro ao salvar configurações: ' . $e->getMessage();
}

$conn->close();
redirect(APP_URL . '/admin/configuracoes-admin.php');

