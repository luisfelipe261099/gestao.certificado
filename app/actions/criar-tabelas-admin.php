<?php
/**
 * ============================================================================
 * CRIAR TABELAS NECESSÁRIAS PARA O ADMIN - SISTEMA DE CERTIFICADOS
 * ============================================================================
 */

require_once '../config/config.php';

// Verificar autenticação e permissão (apenas admin)
if (!isAuthenticated() || !hasRole(ROLE_ADMIN)) {
    die('Acesso negado');
}

$conn = getDBConnection();

echo "<h1>Criando/Verificando Tabelas do Sistema</h1>";
echo "<pre>";

// 1. Tabela de configurações (usando nome correto)
echo "\n1. Verificando tabela 'configuracoes_sistema'...\n";
$result = $conn->query("SHOW TABLES LIKE 'configuracoes_sistema'");
if ($result->num_rows == 0) {
    $sql = "CREATE TABLE configuracoes_sistema (
        id INT AUTO_INCREMENT PRIMARY KEY,
        chave VARCHAR(100) UNIQUE NOT NULL,
        valor LONGTEXT,
        tipo ENUM('string','integer','boolean','json') DEFAULT 'string',
        descricao TEXT,
        criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        atualizado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_chave (chave)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

    if ($conn->query($sql)) {
        echo "✓ Tabela 'configuracoes_sistema' criada com sucesso!\n";
    } else {
        echo "✗ Erro ao criar tabela 'configuracoes_sistema': " . $conn->error . "\n";
    }
} else {
    echo "✓ Tabela 'configuracoes_sistema' já existe!\n";
}

// 2. Verificar/Adicionar campo data_cancelamento em certificados
echo "\n2. Verificando campo 'data_cancelamento' na tabela 'certificados'...\n";
$result = $conn->query("SHOW COLUMNS FROM certificados LIKE 'data_cancelamento'");
if ($result->num_rows == 0) {
    $sql = "ALTER TABLE certificados ADD COLUMN data_cancelamento DATETIME NULL AFTER status";
    if ($conn->query($sql)) {
        echo "✓ Campo 'data_cancelamento' adicionado com sucesso!\n";
    } else {
        echo "✗ Erro ao adicionar campo 'data_cancelamento': " . $conn->error . "\n";
    }
} else {
    echo "✓ Campo 'data_cancelamento' já existe!\n";
}

// 3. Verificar/Adicionar campo atualizado_em em cursos
echo "\n3. Verificando campo 'atualizado_em' na tabela 'cursos'...\n";
$result = $conn->query("SHOW COLUMNS FROM cursos LIKE 'atualizado_em'");
if ($result->num_rows == 0) {
    $sql = "ALTER TABLE cursos ADD COLUMN atualizado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP AFTER criado_em";
    if ($conn->query($sql)) {
        echo "✓ Campo 'atualizado_em' adicionado com sucesso!\n";
    } else {
        echo "✗ Erro ao adicionar campo 'atualizado_em': " . $conn->error . "\n";
    }
} else {
    echo "✓ Campo 'atualizado_em' já existe!\n";
}

// 4. Inserir configurações padrão
echo "\n4. Inserindo configurações padrão...\n";
$configuracoes_padrao = [
    ['site_nome', 'FaCiencia - Sistema de Certificados', 'string', 'Nome do sistema'],
    ['site_email', 'contato@faciencia.edu.br', 'string', 'Email de contato'],
    ['site_telefone', '(41) 3333-3333', 'string', 'Telefone de contato'],
    ['certificado_validade_dias', '0', 'integer', 'Validade dos certificados em dias (0 = permanente)'],
    ['certificado_assinatura_digital', '1', 'boolean', 'Habilitar assinatura digital'],
    ['email_notificacoes', '1', 'boolean', 'Enviar notificações por email'],
    ['manutencao_modo', '0', 'boolean', 'Modo manutenção'],
    ['api_asaas_enabled', '0', 'boolean', 'Habilitar integração Asaas'],
    ['api_asaas_ambiente', 'sandbox', 'string', 'Ambiente Asaas (sandbox/production)'],
    ['api_asaas_key', '', 'string', 'API Key Asaas']
];

$stmt = $conn->prepare("
    INSERT INTO configuracoes_sistema (chave, valor, tipo, descricao)
    VALUES (?, ?, ?, ?)
    ON DUPLICATE KEY UPDATE tipo = ?, descricao = ?
");

foreach ($configuracoes_padrao as $config) {
    $stmt->bind_param("ssssss", $config[0], $config[1], $config[2], $config[3], $config[2], $config[3]);
    if ($stmt->execute()) {
        echo "✓ Configuração '{$config[0]}' inserida/atualizada\n";
    } else {
        echo "✗ Erro ao inserir configuração '{$config[0]}': " . $stmt->error . "\n";
    }
}
$stmt->close();

// 5. Verificar estrutura das tabelas principais
echo "\n5. Verificando estrutura das tabelas principais...\n";

$tabelas_verificar = ['parceiros', 'cursos', 'alunos', 'certificados', 'assinaturas', 'planos'];

foreach ($tabelas_verificar as $tabela) {
    $result = $conn->query("SHOW TABLES LIKE '$tabela'");
    if ($result->num_rows > 0) {
        echo "✓ Tabela '$tabela' existe\n";
        
        // Contar registros
        $count_result = $conn->query("SELECT COUNT(*) as total FROM $tabela");
        $count = $count_result->fetch_assoc()['total'];
        echo "  → $count registro(s)\n";
    } else {
        echo "✗ Tabela '$tabela' NÃO existe!\n";
    }
}

echo "\n";
echo "========================================\n";
echo "PROCESSO CONCLUÍDO!\n";
echo "========================================\n";
echo "</pre>";

echo "<br><a href='" . APP_URL . "/admin/configuracoes-admin.php'>← Voltar para Configurações</a>";

$conn->close();

