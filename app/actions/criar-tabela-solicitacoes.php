<?php
/**
 * ============================================================================
 * CRIAR TABELA: solicitacoes_planos
 * ============================================================================
 * Este script cria a tabela de solicitações de planos se ela não existir
 * ============================================================================
 */

require_once __DIR__ . '/bootstrap.php';

// Verificar autenticação (apenas admin)
if (!isAuthenticated() || !hasRole(ROLE_ADMIN)) {
    $_SESSION['error'] = 'Acesso negado. Apenas administradores podem executar esta ação.';
    redirect(APP_URL . '/login.php');
}

try {
    $conn = getDBConnection();
    
    // SQL para criar a tabela
    $sql = "CREATE TABLE IF NOT EXISTS `solicitacoes_planos` (
      `id` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
      `parceiro_id` int(11) NOT NULL,
      `plano_novo_id` int(11) NOT NULL,
      `status` enum('pendente','aprovada','recusada','cancelada') DEFAULT 'pendente',
      `tipo` enum('mudanca','renovacao','nova') DEFAULT 'mudanca',
      `observacao` varchar(255) DEFAULT NULL,
      `fatura_id` int(11) DEFAULT NULL,
      `aprovado_por` int(11) DEFAULT NULL,
      `aprovado_em` timestamp NULL DEFAULT NULL,
      `criado_em` timestamp NOT NULL DEFAULT current_timestamp(),
      `atualizado_em` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
      
      INDEX `idx_status` (`status`),
      INDEX `idx_parceiro_id` (`parceiro_id`),
      INDEX `idx_plano_novo_id` (`plano_novo_id`),
      INDEX `idx_criado_em` (`criado_em`),
      
      CONSTRAINT `fk_solicitacoes_parceiros` FOREIGN KEY (`parceiro_id`) REFERENCES `parceiros` (`id`) ON DELETE CASCADE,
      CONSTRAINT `fk_solicitacoes_planos` FOREIGN KEY (`plano_novo_id`) REFERENCES `planos` (`id`) ON DELETE RESTRICT
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    
    if ($conn->query($sql) === TRUE) {
        $_SESSION['success'] = 'Tabela solicitacoes_planos criada com sucesso!';
    } else {
        $_SESSION['error'] = 'Erro ao criar tabela: ' . $conn->error;
    }
    
    $conn->close();
    
} catch (Exception $e) {
    $_SESSION['error'] = 'Erro: ' . $e->getMessage();
}

redirect(APP_URL . '/admin/dashboard-admin.php');
?>

