<?php
/**
 * Header do Aluno - Sistema EAD Pro
 * Include para todas as páginas do aluno
 */

// Verificar autenticação
if (!isset($_SESSION['usuario_id']) || $_SESSION['tipo_usuario'] !== 'aluno') {
    header('Location: ../aluno/login-aluno.php');
    exit;
}

$aluno_id = $_SESSION['usuario_id'];
$nome_aluno = $_SESSION['nome'] ?? 'Aluno';
$email_aluno = $_SESSION['email'] ?? '';
$foto_aluno = $_SESSION['foto_url'] ?? 'https://via.placeholder.com/32';
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="description" content="Sistema EAD Pro - Aluno">
    <meta name="author" content="">

    <title><?php echo isset($page_title) ? htmlspecialchars($page_title) . ' - EAD Pro' : 'EAD Pro'; ?></title>

    <link href="../vendor/fontawesome-free/css/all.min.css" rel="stylesheet" type="text/css">
    <link href="https://fonts.googleapis.com/css?family=Nunito:200,200i,300,300i,400,400i,600,600i,700,700i,800,800i,900,900i" rel="stylesheet">
    <link href="../css/sb-admin-2.min.css" rel="stylesheet">
    <link href="../css/sb-admin-2-custom.css" rel="stylesheet">
</head>
<body id="page-top">
    <div id="wrapper">
        <!-- Sidebar -->
        <ul class="navbar-nav bg-gradient-primary sidebar sidebar-dark accordion" id="accordionSidebar">
            <a class="sidebar-brand d-flex align-items-center justify-content-center" href="dashboard-aluno.php">
                <div class="sidebar-brand-icon rotate-n-15">
                    <i class="fas fa-graduation-cap"></i>
                </div>
                <div class="sidebar-brand-text mx-3">EAD <sup>Pro</sup></div>
            </a>

            <hr class="sidebar-divider my-0">

            <li class="nav-item">
                <a class="nav-link" href="dashboard-aluno.php">
                    <i class="fas fa-fw fa-tachometer-alt"></i>
                    <span>Dashboard</span>
                </a>
            </li>

            <hr class="sidebar-divider">

            <div class="sidebar-heading">Aprendizado</div>

            <li class="nav-item">
                <a class="nav-link" href="meus-cursos.php">
                    <i class="fas fa-fw fa-graduation-cap"></i>
                    <span>Meus Cursos</span>
                </a>
            </li>

            <li class="nav-item">
                <a class="nav-link" href="forum.php">
                    <i class="fas fa-fw fa-comments"></i>
                    <span>Fórum</span>
                </a>
            </li>

            <hr class="sidebar-divider">

            <div class="sidebar-heading">Conta</div>

            <li class="nav-item">
                <a class="nav-link" href="historico.php">
                    <i class="fas fa-fw fa-chart-line"></i>
                    <span>Histórico</span>
                </a>
            </li>

            <li class="nav-item">
                <a class="nav-link" href="certificados.php">
                    <i class="fas fa-fw fa-certificate"></i>
                    <span>Certificados</span>
                </a>
            </li>

            <li class="nav-item">
                <a class="nav-link" href="perfil-aluno.php">
                    <i class="fas fa-fw fa-user"></i>
                    <span>Perfil</span>
                </a>
            </li>

            <li class="nav-item">
                <a class="nav-link" href="ajuda.php">
                    <i class="fas fa-fw fa-question-circle"></i>
                    <span>Ajuda</span>
                </a>
            </li>

            <hr class="sidebar-divider d-none d-md-block">

            <div class="text-center d-none d-md-inline">
                <button class="rounded-circle border-0" id="sidebarToggle"></button>
            </div>
        </ul>

        <div id="content-wrapper" class="d-flex flex-column">
            <div id="content">
                <!-- Topbar -->
                <nav class="navbar navbar-expand navbar-dark topbar mb-4 static-top shadow-lg" style="background: linear-gradient(135deg, #9c166f 0%, #6b0f52 100%) !important;">
                    <button id="sidebarToggleTop" class="btn btn-link text-white d-md-none rounded-circle mr-3">
                        <i class="fa fa-bars"></i>
                    </button>

                    <!-- Topbar Brand/Logo -->
                    <div class="navbar-brand mr-auto ml-2 d-none d-lg-inline">
                        <span class="text-white font-weight-bold" style="font-size: 0.9rem;">
                            Gestão Educacional
                        </span>
                    </div>

                    <form class="d-none d-sm-inline-block form-inline mr-auto ml-md-3 my-2 my-md-0 mw-100 navbar-search">
                        <div class="input-group">
                            <input type="text" class="form-control bg-white border-0 small" placeholder="Buscar..." aria-label="Search">
                            <div class="input-group-append">
                                <button class="btn btn-light" type="button">
                                    <i class="fas fa-search fa-sm text-primary"></i>
                                </button>
                            </div>
                        </div>
                    </form>

                    <ul class="navbar-nav ml-auto">
                        <li class="nav-item dropdown no-arrow">
                            <a class="nav-link dropdown-toggle text-white" href="#" id="userDropdown" role="button" data-toggle="dropdown">
                                <span class="mr-2 d-none d-lg-inline small"><?php echo htmlspecialchars($nome_aluno); ?></span>
                                <img class="img-profile rounded-circle" src="<?php echo htmlspecialchars($foto_aluno); ?>" alt="Foto do aluno">
                            </a>
                            <div class="dropdown-menu dropdown-menu-right shadow animated--grow-in" aria-labelledby="userDropdown">
                                <a class="dropdown-item" href="perfil-aluno.php">
                                    <i class="fas fa-user fa-sm fa-fw mr-2 text-primary"></i> Perfil
                                </a>
                                <a class="dropdown-item" href="ajuda.php">
                                    <i class="fas fa-question-circle fa-sm fa-fw mr-2 text-primary"></i> Ajuda
                                </a>
                                <div class="dropdown-divider"></div>
                                <a class="dropdown-item" href="logout-aluno.php">
                                    <i class="fas fa-sign-out-alt fa-sm fa-fw mr-2 text-danger"></i> Sair
                                </a>
                            </div>
                        </li>
                    </ul>
                </nav>

                <div class="container-fluid">

