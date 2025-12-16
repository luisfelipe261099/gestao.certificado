<?php
/**
 * Logout do Parceiro
 * Sistema EAD Pro
 */

require_once '../config/database.php';

iniciar_sessao();

// Destruir sessão
session_destroy();

// Redirecionar para login
header('Location: login.php?logout=1');
exit;

?>

