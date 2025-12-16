<?php
/**
 * ============================================================================
 * PÁGINA INICIAL - SISTEMA DE CERTIFICADOS
 * ============================================================================
 *
 * Esta é a página inicial do sistema.
 * Ela não mostra nada - apenas redireciona o usuário para o lugar certo:
 * - Se está logado: vai para o dashboard
 * - Se não está logado: vai para a página de login
 *
 * Padrão MVP - Camada de Apresentação
 * ============================================================================
 */

// Inclui o arquivo de configuração
require_once 'app/config/config.php';

// ============================================================================
// VERIFICAR SE ESTÁ LOGADO
// ============================================================================
// Se o usuário já está autenticado (logado), redireciona para o dashboard
if (isAuthenticated()) {
    // Verifica se é admin ou parceiro
    if (hasRole(ROLE_ADMIN)) {
        // Admin vai para o dashboard do admin
        redirect(DIR_ADMIN . '/dashboard-admin.php');
    } else {
        // Parceiro vai para o dashboard do parceiro
        redirect(DIR_PARCEIRO . '/dashboard-parceiro.php');
    }
}

// ============================================================================
// SE NÃO ESTÁ LOGADO, VAI PARA LOGIN
// ============================================================================
// Se chegou aqui, significa que não está logado
// Então redireciona para a página de login
redirect('login.php');
?>

