<?php
/**
 * Header Comum - Componente Reutilizável
 * Padrão MVP - Camada de Apresentação
 */

if (!isAuthenticated()) {
    redirect(APP_URL . '/login.php');
}

$user = getCurrentUser();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="description" content="<?php echo APP_NAME; ?>">
    <title><?php echo $page_title ?? APP_NAME; ?></title>

    <!-- Font Awesome -->
    <link href="<?php echo DIR_VENDOR; ?>/fontawesome-free/css/all.min.css" rel="stylesheet" type="text/css">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css?family=Nunito:200,200i,300,300i,400,400i,600,600i,700,700i,800,800i,900,900i" rel="stylesheet">
    <!-- SB Admin 2 CSS -->
    <link href="<?php echo DIR_CSS; ?>/sb-admin-2.min.css" rel="stylesheet">
    <!-- DataTables CSS -->
    <link href="https://cdn.datatables.net/1.10.21/css/dataTables.bootstrap4.min.css" rel="stylesheet">
    <!-- Topbar Custom CSS -->
    <link href="<?php echo APP_URL; ?>/css/topbar-custom.css" rel="stylesheet">
    <!-- Tema Roxo para Parceiro -->
    <?php if (isset($user) && $user['role'] === ROLE_PARCEIRO): ?>
        <link href="<?php echo DIR_CSS; ?>/parceiro-theme.css" rel="stylesheet">
    <?php endif; ?>
</head>
<body id="page-top">
    <div id="wrapper">

