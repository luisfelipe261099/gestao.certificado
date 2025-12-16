<?php
/**
 * Header Template - Parceiro
 * Sistema EAD Pro
 */

// Definir encoding UTF-8
header('Content-Type: text/html; charset=utf-8');

// Verificar autenticação integrada
if (!isset($_SESSION['ead_autenticado']) || $_SESSION['ead_autenticado'] !== true) {
    header('Location: login.php?timeout=1');
    exit;
}

$parceiro_id = $_SESSION['ead_parceiro_id'];
$nome_parceiro = $_SESSION['ead_nome'] ?? 'Parceiro';
$email_parceiro = $_SESSION['ead_email'] ?? '';
$empresa_parceiro = $_SESSION['ead_parceiro_nome'] ?? '';
$logo_url = $_SESSION['ead_logo_url'] ?? '';

// Página atual para ativar menu
$pagina_atual = basename($_SERVER['PHP_SELF'], '.php');
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($titulo_pagina) ? $titulo_pagina . ' - EAD Pro' : 'EAD Pro'; ?></title>
    
    <!-- Bootstrap CSS -->
    <link href="../vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link href="../vendor/fontawesome-free/css/all.min.css" rel="stylesheet">
    
    <!-- DataTables CSS -->
    <link href="../vendor/datatables/dataTables.bootstrap4.min.css" rel="stylesheet">
    
    <!-- SB Admin 2 CSS -->
    <link href="../css/sb-admin-2.min.css" rel="stylesheet">
    
    <!-- Custom CSS -->
    <link href="../css/sb-admin-2-custom.css" rel="stylesheet">
    
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js"></script>
</head>
<body id="page-top">
    <div id="wrapper">
        <!-- Sidebar -->
        <ul class="navbar-nav bg-gradient-primary sidebar sidebar-dark accordion" id="accordionSidebar">
            <!-- Brand -->
            <a class="sidebar-brand d-flex align-items-center justify-content-center" href="dashboard.php">
                <div class="sidebar-brand-icon rotate-n-15">
                    <i class="fas fa-graduation-cap"></i>
                </div>
                <div class="sidebar-brand-text mx-3">EAD Pro<sup>P</sup></div>
            </a>

            <hr class="sidebar-divider my-0">

            <!-- Dashboard -->
            <li class="nav-item <?php echo $pagina_atual === 'dashboard' ? 'active' : ''; ?>">
                <a class="nav-link" href="dashboard.php">
                    <i class="fas fa-fw fa-tachometer-alt"></i>
                    <span>Dashboard</span>
                </a>
            </li>

            <hr class="sidebar-divider">

            <!-- Aprendizado -->
            <div class="sidebar-heading">Aprendizado</div>

            <!-- Cursos -->
            <li class="nav-item <?php echo in_array($pagina_atual, ['cursos', 'criar-curso', 'editar-curso', 'curso-detalhes']) ? 'active' : ''; ?>">
                <a class="nav-link collapsed" href="#" data-toggle="collapse" data-target="#collapseCursos">
                    <i class="fas fa-fw fa-book"></i>
                    <span>Cursos</span>
                </a>
                <div id="collapseCursos" class="collapse <?php echo in_array($pagina_atual, ['cursos', 'criar-curso', 'editar-curso', 'curso-detalhes']) ? 'show' : ''; ?>" data-parent="#accordionSidebar">
                    <div class="bg-white py-2 collapse-inner rounded">
                        <a class="collapse-item <?php echo $pagina_atual === 'cursos' ? 'active' : ''; ?>" href="cursos.php">Meus Cursos</a>
                        <a class="collapse-item <?php echo $pagina_atual === 'criar-curso' ? 'active' : ''; ?>" href="criar-curso.php">Criar Curso</a>
                    </div>
                </div>
            </li>

            <!-- Aulas -->
            <li class="nav-item <?php echo in_array($pagina_atual, ['aulas', 'criar-aula', 'editar-aula']) ? 'active' : ''; ?>">
                <a class="nav-link collapsed" href="#" data-toggle="collapse" data-target="#collapseAulas">
                    <i class="fas fa-fw fa-video"></i>
                    <span>Aulas</span>
                </a>
                <div id="collapseAulas" class="collapse <?php echo in_array($pagina_atual, ['aulas', 'criar-aula', 'editar-aula']) ? 'show' : ''; ?>" data-parent="#accordionSidebar">
                    <div class="bg-white py-2 collapse-inner rounded">
                        <a class="collapse-item <?php echo $pagina_atual === 'aulas' ? 'active' : ''; ?>" href="aulas.php">Minhas Aulas</a>
                        <a class="collapse-item <?php echo $pagina_atual === 'criar-aula' ? 'active' : ''; ?>" href="criar-aula.php">Criar Aula</a>
                    </div>
                </div>
            </li>

            <!-- Conteúdo -->
            <li class="nav-item <?php echo in_array($pagina_atual, ['upload-conteudo', 'editar-conteudo']) ? 'active' : ''; ?>">
                <a class="nav-link" href="upload-conteudo.php">
                    <i class="fas fa-fw fa-file-upload"></i>
                    <span>Conteúdo</span>
                </a>
            </li>

            <!-- Exercícios -->
            <li class="nav-item <?php echo in_array($pagina_atual, ['exercicios', 'criar-exercicio', 'editar-exercicio']) ? 'active' : ''; ?>">
                <a class="nav-link collapsed" href="#" data-toggle="collapse" data-target="#collapseExercicios">
                    <i class="fas fa-fw fa-tasks"></i>
                    <span>Exercícios</span>
                </a>
                <div id="collapseExercicios" class="collapse <?php echo in_array($pagina_atual, ['exercicios', 'criar-exercicio', 'editar-exercicio']) ? 'show' : ''; ?>" data-parent="#accordionSidebar">
                    <div class="bg-white py-2 collapse-inner rounded">
                        <a class="collapse-item <?php echo $pagina_atual === 'exercicios' ? 'active' : ''; ?>" href="exercicios.php">Meus Exercícios</a>
                        <a class="collapse-item <?php echo $pagina_atual === 'criar-exercicio' ? 'active' : ''; ?>" href="criar-exercicio.php">Criar Exercício</a>
                    </div>
                </div>
            </li>

            <hr class="sidebar-divider">

            <!-- Gerenciamento -->
            <div class="sidebar-heading">Gerenciamento</div>

            <!-- Alunos -->
            <li class="nav-item <?php echo in_array($pagina_atual, ['alunos', 'aluno-detalhes', 'alunos-importar']) ? 'active' : ''; ?>">
                <a class="nav-link collapsed" href="#" data-toggle="collapse" data-target="#collapseAlunos">
                    <i class="fas fa-fw fa-users"></i>
                    <span>Alunos</span>
                </a>
                <div id="collapseAlunos" class="collapse <?php echo in_array($pagina_atual, ['alunos', 'aluno-detalhes', 'alunos-importar']) ? 'show' : ''; ?>" data-parent="#accordionSidebar">
                    <div class="bg-white py-2 collapse-inner rounded">
                        <a class="collapse-item <?php echo $pagina_atual === 'alunos' ? 'active' : ''; ?>" href="alunos.php">Meus Alunos</a>
                        <a class="collapse-item <?php echo $pagina_atual === 'alunos-importar' ? 'active' : ''; ?>" href="alunos-importar.php">Importar Alunos</a>
                    </div>
                </div>
            </li>

            <hr class="sidebar-divider d-none d-md-block">

            <!-- Voltar para Painel do Parceiro -->
            <li class="nav-item">
                <a class="nav-link" href="../../parceiro/dashboard-parceiro.php">
                    <i class="fas fa-fw fa-arrow-left"></i>
                    <span>Voltar ao Painel</span>
                </a>
            </li>

            <hr class="sidebar-divider d-none d-md-block">

            <!-- Sidebar Toggler (Sidebar) -->
            <div class="text-center d-none d-md-inline">
                <button class="rounded-circle border-0" id="sidebarToggle"></button>
            </div>
        </ul>
        <!-- End of Sidebar -->

        <!-- Content Wrapper -->
        <div id="content-wrapper" class="d-flex flex-column">
            <!-- Main Content -->
            <div id="content">
                <!-- Topbar -->
                <nav class="navbar navbar-expand navbar-dark topbar mb-4 static-top shadow-lg" style="background: linear-gradient(135deg, #9c166f 0%, #6b0f52 100%) !important;">
                    <!-- Sidebar Toggle (Topbar) -->
                    <button id="sidebarToggleTop" class="btn btn-link text-white d-md-none rounded-circle mr-3">
                        <i class="fa fa-bars"></i>
                    </button>

                    <!-- Topbar Brand/Logo -->
                    

                    <!-- Topbar Navbar -->
                    <ul class="navbar-nav ml-auto">
                        <!-- Nav Item - Alerts -->
                        <li class="nav-item dropdown no-arrow mx-1">
                            <a class="nav-link dropdown-toggle text-white" href="#" id="alertsDropdown" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                <i class="fas fa-bell fa-fw"></i>
                                <span class="badge badge-light badge-counter">3+</span>
                            </a>
                            <div class="dropdown-menu dropdown-menu-right shadow animated--grow-in" aria-labelledby="alertsDropdown">
                                <h6 class="dropdown-header bg-primary text-white">Notificações</h6>
                                <a class="dropdown-item d-flex align-items-center" href="#">
                                    <div class="mr-3">
                                        <div class="icon-circle bg-primary">
                                            <i class="fas fa-file-alt text-white"></i>
                                        </div>
                                    </div>
                                    <div>
                                        <div class="small text-gray-500">Novo aluno inscrito</div>
                                        <span class="font-weight-bold">Há 2 horas</span>
                                    </div>
                                </a>
                                <a class="dropdown-item text-center small text-gray-500" href="#">Ver todas as notificações</a>
                            </div>
                        </li>

                        <div class="topbar-divider d-none d-sm-block" style="border-right: 1px solid rgba(255,255,255,0.3);"></div>

                        <!-- Nav Item - User Information -->
                        <li class="nav-item dropdown no-arrow">
                            <a class="nav-link dropdown-toggle text-white" href="#" id="userDropdown" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                <span class="mr-2 d-none d-lg-inline small"><?php echo htmlspecialchars($nome_parceiro); ?></span>
                                <i class="fas fa-user-circle fa-lg"></i>
                            </a>
                            <div class="dropdown-menu dropdown-menu-right shadow animated--grow-in" aria-labelledby="userDropdown">
                                <a class="dropdown-item" href="#">
                                    <i class="fas fa-user fa-sm fa-fw mr-2 text-primary"></i>
                                    Meu Perfil
                                </a>
                                <a class="dropdown-item" href="#">
                                    <i class="fas fa-cogs fa-sm fa-fw mr-2 text-primary"></i>
                                    Configurações
                                </a>
                                <div class="dropdown-divider"></div>
                                <a class="dropdown-item" href="logout.php">
                                    <i class="fas fa-sign-out-alt fa-sm fa-fw mr-2 text-danger"></i>
                                    Sair
                                </a>
                            </div>
                        </li>
                    </ul>
                </nav>
                <!-- End of Topbar -->

                <!-- Begin Page Content -->
                <div class="container-fluid">

