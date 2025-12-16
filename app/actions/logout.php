<?php
/**
 * ============================================================================
 * LOGOUT - DESCONECTAR DO SISTEMA
 * ============================================================================
 *
 * Este arquivo é uma "ação" - ele faz algo quando o usuário clica em "Sair"
 *
 * O que faz:
 * 1. Destroi a sessão (apaga o arquivo temporário com dados do usuário)
 * 2. Redireciona para a página de login
 *
 * Padrão MVP - Camada de Ação
 * ============================================================================
 */

// Inclui o arquivo de configuração
require_once __DIR__ . '/bootstrap.php';

// ============================================================================
// PASSO 1: DESTRUIR A SESSÃO
// ============================================================================
// session_destroy() = apaga o arquivo temporário com dados do usuário
// Depois disso, o usuário não está mais logado
session_destroy();

// ============================================================================
// PASSO 2: REDIRECIONAR PARA A PÁGINA DE LOGIN
// ============================================================================
// Envia o usuário de volta para a página de login
redirect(APP_URL . '/login.php');
?>

