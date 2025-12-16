<?php
/**
 * ============================================================================
 * LOGOUT - ARQUIVO DE REDIRECIONAMENTO
 * ============================================================================
 *
 * Este arquivo existe na raiz para compatibilidade com links antigos
 * Redireciona para o arquivo de logout correto em app/actions/logout.php
 *
 * ============================================================================
 */

// Redireciona para o arquivo de logout correto
header('Location: app/actions/logout.php');
exit;

