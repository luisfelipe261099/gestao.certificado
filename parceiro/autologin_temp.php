<?php
session_start();
$_SESSION['user_id'] = 15;
$_SESSION['user_email'] = 'teste@teste.com';
$_SESSION['user_role'] = 'parceiro';
$_SESSION['user_name'] = 'Parceiro Teste';
$_SESSION['parceiro_id'] = 15;
$_SESSION['logged_in'] = true;

header("Location: templates-parceiro.php");
exit;
