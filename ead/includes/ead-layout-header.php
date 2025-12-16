<?php
/**
 * ============================================================================
 * HEADER MODERNO - EAD PRO
 * ============================================================================
 * Layout estilo Apple com cor #6E41C1
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

// Página atual para ativar menu
$pagina_atual = basename($_SERVER['PHP_SELF'], '.php');
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($titulo_pagina) ? $titulo_pagina . ' - EAD Pro' : 'EAD Pro'; ?></title>
    
    <!-- Google Fonts - Inter -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    
    <!-- Material Icons -->
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons+Outlined" rel="stylesheet">
    
    <style>
        :root {
            --primary-color: #6E41C1;
            --primary-hover: #56349A;
            --primary-light: #8B5FD6;
            --sidebar-bg: #F5F5F7;
            --sidebar-text: #1D1D1F;
            --content-bg: #FFFFFF;
            --card-bg: #FFFFFF;
            --text-dark: #1D1D1F;
            --text-medium: #86868B;
            --text-light: #ADADAD;
            --border-light: #E5E5E7;
            --border-medium: #D0D0D0;
            --shadow-subtle: 0 2px 8px rgba(0,0,0,0.05);
            --shadow-medium: 0 4px 12px rgba(0,0,0,0.08);
            --border-radius-card: 14px;
            --border-radius-button: 10px;
            --status-green: #34C759;
            --status-red: #FF3B30;
            --status-orange: #FF9500;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            background-color: var(--content-bg);
            color: var(--text-dark);
            font-size: 14px;
            line-height: 1.6;
            overflow-x: hidden;
        }

        /* Layout Principal */
        .layout-container {
            display: flex;
            min-height: 100vh;
        }

        /* Sidebar */
        .sidebar {
            width: 260px;
            background-color: var(--sidebar-bg);
            border-right: 1px solid var(--border-light);
            display: flex;
            flex-direction: column;
            position: fixed;
            height: 100vh;
            overflow-y: auto;
            z-index: 1000;
        }

        .sidebar::-webkit-scrollbar {
            width: 6px;
        }

        .sidebar::-webkit-scrollbar-track {
            background: transparent;
        }

        .sidebar::-webkit-scrollbar-thumb {
            background: var(--border-medium);
            border-radius: 3px;
        }

        /* Logo */
        .sidebar-logo {
            padding: 24px 20px;
            border-bottom: 1px solid var(--border-light);
        }

        .sidebar-logo a {
            display: flex;
            align-items: center;
            gap: 12px;
            text-decoration: none;
            color: var(--text-dark);
        }

        .sidebar-logo-icon {
            width: 40px;
            height: 40px;
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--primary-light) 100%);
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 4px 12px rgba(110, 65, 193, 0.3);
        }

        .sidebar-logo-icon .material-icons-outlined {
            color: white;
            font-size: 24px;
        }

        .sidebar-logo-text {
            font-size: 18px;
            font-weight: 700;
            color: var(--text-dark);
        }

        /* Menu */
        .sidebar-menu {
            flex: 1;
            padding: 16px 12px;
        }

        .menu-section {
            margin-bottom: 24px;
        }

        .menu-section-title {
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
            color: var(--text-medium);
            padding: 0 12px 8px 12px;
            letter-spacing: 0.5px;
        }

        .menu-item {
            margin-bottom: 4px;
        }

        .menu-item a {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 10px 12px;
            border-radius: 8px;
            text-decoration: none;
            color: var(--sidebar-text);
            font-size: 14px;
            font-weight: 500;
            transition: all 0.2s ease;
        }

        .menu-item a:hover {
            background-color: rgba(110, 65, 193, 0.08);
            color: var(--primary-color);
        }

        .menu-item a.active {
            background-color: var(--primary-color);
            color: white;
            box-shadow: 0 2px 8px rgba(110, 65, 193, 0.3);
        }

        .menu-item .material-icons-outlined {
            font-size: 20px;
        }

        /* Submenu */
        .menu-item-parent {
            position: relative;
        }

        .menu-item-parent > a {
            justify-content: space-between;
        }

        .menu-item-parent .menu-arrow {
            font-size: 18px;
            transition: transform 0.2s ease;
        }

        .menu-item-parent.open .menu-arrow {
            transform: rotate(180deg);
        }

        .submenu {
            max-height: 0;
            overflow: hidden;
            transition: max-height 0.3s ease;
            padding-left: 12px;
        }

        .submenu.open {
            max-height: 500px;
        }

        .submenu-item {
            margin-bottom: 2px;
        }

        .submenu-item a {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 8px 12px 8px 32px;
            border-radius: 6px;
            text-decoration: none;
            color: var(--sidebar-text);
            font-size: 13px;
            font-weight: 500;
            transition: all 0.2s ease;
        }

        .submenu-item a:hover {
            background-color: rgba(110, 65, 193, 0.08);
            color: var(--primary-color);
        }

        .submenu-item a.active {
            background-color: rgba(110, 65, 193, 0.15);
            color: var(--primary-color);
            font-weight: 600;
        }

        .submenu-item .material-icons-outlined {
            font-size: 16px;
        }

        /* User Info */
        .sidebar-user {
            padding: 16px;
            border-top: 1px solid var(--border-light);
            background-color: rgba(110, 65, 193, 0.04);
        }

        .sidebar-user-info {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 12px;
        }

        .sidebar-user-avatar {
            width: 40px;
            height: 40px;
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--primary-light) 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 600;
            font-size: 16px;
        }

        .sidebar-user-details {
            flex: 1;
            min-width: 0;
        }

        .sidebar-user-name {
            font-size: 13px;
            font-weight: 600;
            color: var(--text-dark);
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .sidebar-user-email {
            font-size: 11px;
            color: var(--text-medium);
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .sidebar-user-actions {
            display: flex;
            gap: 8px;
        }

        .sidebar-user-actions a {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            padding: 8px 12px;
            border-radius: 8px;
            text-decoration: none;
            font-size: 12px;
            font-weight: 500;
            transition: all 0.2s ease;
        }

        .btn-logout {
            background-color: var(--status-red);
            color: white;
        }

        .btn-logout:hover {
            background-color: #E02020;
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(255, 59, 48, 0.3);
        }

        /* Main Content */
        .main-content {
            flex: 1;
            margin-left: 260px;
            padding: 32px;
            min-height: 100vh;
            background-color: var(--content-bg);
        }

        /* Responsive */
        @media (max-width: 768px) {
            .sidebar {
                transform: translateX(-100%);
                transition: transform 0.3s ease;
            }

            .sidebar.active {
                transform: translateX(0);
            }

            .main-content {
                margin-left: 0;
                padding: 20px;
            }
        }

        /* Material Icons */
        .material-icons-outlined {
            font-family: 'Material Icons Outlined';
            font-weight: normal;
            font-style: normal;
            font-size: 24px;
            display: inline-block;
            line-height: 1;
            text-transform: none;
            letter-spacing: normal;
            word-wrap: normal;
            white-space: nowrap;
            direction: ltr;
        }
    </style>
</head>
<body>
    <div class="layout-container">
        <!-- Sidebar -->
        <aside class="sidebar">
            <!-- Logo -->
            <div class="sidebar-logo">
                <a href="dashboard.php">
                    <div class="sidebar-logo-icon">
                        <span class="material-icons-outlined">school</span>
                    </div>
                    <span class="sidebar-logo-text">EAD Pro</span>
                </a>
            </div>

            <!-- Menu -->
            <nav class="sidebar-menu">
                <!-- Navegação -->
                <div class="menu-section">
                    <div class="menu-section-title">Navegação</div>
                    <div class="menu-item">
                        <a href="dashboard.php" class="<?php echo $pagina_atual === 'dashboard' ? 'active' : ''; ?>">
                            <span class="material-icons-outlined">dashboard</span>
                            <span>Dashboard</span>
                        </a>
                    </div>
                </div>

                <!-- Conteúdo -->
                <div class="menu-section">
                    <div class="menu-section-title">Conteúdo</div>

                    <!-- Cursos com Submenu -->
                    <div class="menu-item menu-item-parent <?php echo in_array($pagina_atual, ['cursos', 'criar-curso', 'editar-curso', 'curso-detalhes']) ? 'open' : ''; ?>">
                        <a href="#" onclick="toggleSubmenu(event, this)">
                            <div style="display: flex; align-items: center; gap: 12px;">
                                <span class="material-icons-outlined">menu_book</span>
                                <span>Cursos</span>
                            </div>
                            <span class="material-icons-outlined menu-arrow">expand_more</span>
                        </a>
                        <div class="submenu <?php echo in_array($pagina_atual, ['cursos', 'criar-curso', 'editar-curso', 'curso-detalhes']) ? 'open' : ''; ?>">
                            <div class="submenu-item">
                                <a href="cursos.php" class="<?php echo $pagina_atual === 'cursos' ? 'active' : ''; ?>">
                                    <span class="material-icons-outlined">list</span>
                                    <span>Meus Cursos</span>
                                </a>
                            </div>
                            <div class="submenu-item">
                                <a href="criar-curso.php" class="<?php echo $pagina_atual === 'criar-curso' ? 'active' : ''; ?>">
                                    <span class="material-icons-outlined">add_circle</span>
                                    <span>Criar Curso</span>
                                </a>
                            </div>
                        </div>
                    </div>

                    <!-- Aulas com Submenu -->
                    <div class="menu-item menu-item-parent <?php echo in_array($pagina_atual, ['aulas', 'criar-aula', 'editar-aula', 'visualizar-aula', 'editar-conteudo', 'upload-conteudo']) ? 'open' : ''; ?>">
                        <a href="#" onclick="toggleSubmenu(event, this)">
                            <div style="display: flex; align-items: center; gap: 12px;">
                                <span class="material-icons-outlined">play_circle</span>
                                <span>Aulas</span>
                            </div>
                            <span class="material-icons-outlined menu-arrow">expand_more</span>
                        </a>
                        <div class="submenu <?php echo in_array($pagina_atual, ['aulas', 'criar-aula', 'editar-aula', 'visualizar-aula', 'editar-conteudo', 'upload-conteudo']) ? 'open' : ''; ?>">
                            <div class="submenu-item">
                                <a href="aulas.php" class="<?php echo $pagina_atual === 'aulas' ? 'active' : ''; ?>">
                                    <span class="material-icons-outlined">list</span>
                                    <span>Minhas Aulas</span>
                                </a>
                            </div>
                            <div class="submenu-item">
                                <a href="criar-aula.php" class="<?php echo $pagina_atual === 'criar-aula' ? 'active' : ''; ?>">
                                    <span class="material-icons-outlined">add_circle</span>
                                    <span>Criar Aula</span>
                                </a>
                            </div>
                        </div>
                    </div>

                    <!-- Exercícios com Submenu -->
                    <div class="menu-item menu-item-parent <?php echo in_array($pagina_atual, ['exercicios', 'criar-exercicio', 'editar-exercicio', 'questoes-exercicio']) ? 'open' : ''; ?>">
                        <a href="#" onclick="toggleSubmenu(event, this)">
                            <div style="display: flex; align-items: center; gap: 12px;">
                                <span class="material-icons-outlined">quiz</span>
                                <span>Exercícios</span>
                            </div>
                            <span class="material-icons-outlined menu-arrow">expand_more</span>
                        </a>
                        <div class="submenu <?php echo in_array($pagina_atual, ['exercicios', 'criar-exercicio', 'editar-exercicio', 'questoes-exercicio']) ? 'open' : ''; ?>">
                            <div class="submenu-item">
                                <a href="exercicios.php" class="<?php echo $pagina_atual === 'exercicios' ? 'active' : ''; ?>">
                                    <span class="material-icons-outlined">list</span>
                                    <span>Meus Exercícios</span>
                                </a>
                            </div>
                            <div class="submenu-item">
                                <a href="criar-exercicio.php" class="<?php echo $pagina_atual === 'criar-exercicio' ? 'active' : ''; ?>">
                                    <span class="material-icons-outlined">add_circle</span>
                                    <span>Criar Exercício</span>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Gestão -->
                <div class="menu-section">
                    <div class="menu-section-title">Gestão</div>

                    <!-- Alunos com Submenu -->
                    <div class="menu-item menu-item-parent <?php echo in_array($pagina_atual, ['alunos', 'aluno-detalhes', 'alunos-importar', 'credenciais-alunos']) ? 'open' : ''; ?>">
                        <a href="#" onclick="toggleSubmenu(event, this)">
                            <div style="display: flex; align-items: center; gap: 12px;">
                                <span class="material-icons-outlined">group</span>
                                <span>Alunos</span>
                            </div>
                            <span class="material-icons-outlined menu-arrow">expand_more</span>
                        </a>
                        <div class="submenu <?php echo in_array($pagina_atual, ['alunos', 'aluno-detalhes', 'alunos-importar', 'credenciais-alunos']) ? 'open' : ''; ?>">
                            <div class="submenu-item">
                                <a href="alunos.php" class="<?php echo in_array($pagina_atual, ['alunos', 'aluno-detalhes']) ? 'active' : ''; ?>">
                                    <span class="material-icons-outlined">list</span>
                                    <span>Meus Alunos</span>
                                </a>
                            </div>
                            <div class="submenu-item">
                                <a href="alunos-importar.php" class="<?php echo $pagina_atual === 'alunos-importar' ? 'active' : ''; ?>">
                                    <span class="material-icons-outlined">upload_file</span>
                                    <span>Importar Alunos</span>
                                </a>
                            </div>
                            <div class="submenu-item">
                                <a href="credenciais-alunos.php" class="<?php echo $pagina_atual === 'credenciais-alunos' ? 'active' : ''; ?>">
                                    <span class="material-icons-outlined">vpn_key</span>
                                    <span>Credenciais</span>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </nav>

            <!-- User Info -->
            <div class="sidebar-user">
                <div class="sidebar-user-info">
                    <div class="sidebar-user-avatar">
                        <?php echo strtoupper(substr($nome_parceiro, 0, 1)); ?>
                    </div>
                    <div class="sidebar-user-details">
                        <div class="sidebar-user-name"><?php echo htmlspecialchars($nome_parceiro); ?></div>
                        <div class="sidebar-user-email"><?php echo htmlspecialchars($email_parceiro); ?></div>
                    </div>
                </div>
                <div class="sidebar-user-actions">
                    <a href="logout.php" class="btn-logout">
                        <span class="material-icons-outlined" style="font-size: 16px;">logout</span>
                        <span>Sair</span>
                    </a>
                </div>
            </div>
        </aside>

        <!-- Main Content -->
        <main class="main-content">

