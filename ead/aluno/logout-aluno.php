<?php
/**
 * Logout do Aluno
 * Sistema EAD Pro
 */

require_once '../config/database.php';

iniciar_sessao();

// Destruir sessão
session_destroy();

// Redirecionar para login
header('Location: login-aluno.php');
exit;

