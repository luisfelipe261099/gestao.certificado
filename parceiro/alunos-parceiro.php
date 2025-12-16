<?php
/**
 * ============================================================================
 * ALUNOS DO PARCEIRO - SISTEMA DE CERTIFICADOS
 * ============================================================================
 *
 * Esta página mostra ao parceiro (empresa):
 * - Lista de todos os seus alunos
 * - Dados de cada aluno (nome, email, CPF)
 * - Opção de adicionar novos alunos
 * - Opção de editar alunos
 * - Opção de deletar alunos
 * - Opção de gerar certificados para alunos
 *
 * Padrão MVP - Camada de Apresentação
 * ============================================================================
 */

// Inclui o arquivo de configuração
require_once '../app/config/config.php';

// ============================================================================
// VERIFICAR AUTENTICAÇÃO E PERMISSÃO
// ============================================================================
// Verifica se está logado e se é parceiro
if (!isAuthenticated() || !hasRole(ROLE_PARCEIRO)) {
    redirect(APP_URL . '/login.php');
}

// Define o título da página
$page_title = 'Alunos - ' . APP_NAME;

// Pega dados do usuário logado
$user = getCurrentUser();

// Conecta ao banco de dados
$conn = getDBConnection();

// ============================================================================
// BUSCAR ALUNOS DO PARCEIRO
// ============================================================================
// Alunos = pessoas que vão receber certificados
// Buscamos todos os alunos do parceiro logado
$alunos = [];
$parceiro_id = $user['parceiro_id'] ?? $user['id'];
$stmt = $conn->prepare("
    SELECT
        id,                                -- ID do aluno
        nome,                              -- Nome do aluno
        email,                             -- Email do aluno
        cpf,                               -- CPF do aluno
        criado_em                          -- Quando foi criado
    FROM alunos
    WHERE parceiro_id = ?                  -- Apenas alunos deste parceiro
    ORDER BY criado_em DESC                -- Mais recentes primeiro
");

if ($stmt) {
    // Substitui o ? pelo ID do parceiro
    $stmt->bind_param("i", $parceiro_id);

    // Executa a consulta
    $stmt->execute();

    // Pega o resultado
    $result = $stmt->get_result();

    // Percorre todos os alunos encontrados
    while ($row = $result->fetch_assoc()) {
        $alunos[] = $row;
    }

    // Fecha a consulta
    $stmt->close();
}

// Calcular estatísticas
$total_alunos = count($alunos);

// ============================================================================
// BUSCAR CURSOS DO PARCEIRO
// ============================================================================
// Cursos = disciplinas/treinamentos que o parceiro oferece
// Buscamos apenas os cursos ativos (ativo = 1)
$cursos = [];
$stmt = $conn->prepare("
    SELECT
        id,                                -- ID do curso
        nome                               -- Nome do curso
    FROM cursos
    WHERE parceiro_id = ? AND ativo = 1    -- Apenas cursos ativos deste parceiro
    ORDER BY nome                          -- Ordenado por nome
");

if ($stmt) {
    // Substitui o ? pelo ID do parceiro
    $stmt->bind_param("i", $parceiro_id);

    // Executa a consulta
    $stmt->execute();

    // Pega o resultado
    $result = $stmt->get_result();

    // Percorre todos os cursos encontrados
    while ($row = $result->fetch_assoc()) {
        $cursos[] = $row;
    }

    // Fecha a consulta
    $stmt->close();
}
?>
<?php require_once '../app/views/header.php'; ?>
<?php require_once '../app/views/sidebar-parceiro.php'; ?>

<div id="content-wrapper" class="d-flex flex-column">
    <div id="content">
        <?php require_once '../app/views/topbar.php'; ?>

        <style>
            @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap');
            @import url('https://fonts.googleapis.com/icon?family=Material+Icons+Outlined');

            :root {
                --primary-color: #6E41C1;
                --primary-hover: #56349A;
                --sidebar-bg: #F5F5F7;
                --sidebar-text: #1D1D1F;
                --content-bg: #FFFFFF;
                --card-bg: #FFFFFF;
                --text-dark: #1D1D1F;
                --text-medium: #6B6B6B;
                --text-light: #ADADAD;
                --border-light: #E0E0E0;
                --border-medium: #D0D0D0;
                --shadow-subtle: 0 2px 8px rgba(0, 0, 0, 0.05);
                --border-radius-card: 14px;
                --border-radius-button: 10px;
                --status-green: #34C759
            }

            #accordionSidebar,
            .topbar,
            .scroll-to-top {
                display: none !important
            }

            #content {
                padding: 0 !important
            }

            .container-fluid {
                padding: 0 !important
            }

            body,
            .content-area {
                font-family: 'Inter', sans-serif;
                color: var(--text-dark);
                background: var(--content-bg)
            }

            .icon {
                font-family: 'Material Icons Outlined';
                font-size: 20px;
                vertical-align: middle;
                margin-right: 10px;
                color: var(--text-medium)
            }

            .erp-container {
                display: flex;
                height: 100vh
            }

            .sidebar {
                width: 250px;
                background: var(--sidebar-bg);
                color: var(--sidebar-text);
                padding: 24px 20px;
                display: flex;
                flex-direction: column;
                border-right: 1px solid var(--border-light);
                flex-shrink: 0
            }

            .sidebar-header {
                font-size: 1.5rem;
                font-weight: 700;
                text-align: center;
                padding-bottom: 20px;
                margin-bottom: 20px;
                color: var(--text-dark)
            }

            .nav-section-title {
                font-size: .7rem;
                color: var(--text-light);
                margin: 24px 0 12px 15px;
                letter-spacing: .7px;
                font-weight: 500
            }

            .sidebar-nav {
                overflow-y: auto
            }

            .sidebar-nav ul {
                list-style: none;
                margin: 0;
                padding: 0
            }

            .sidebar-nav li {
                margin-bottom: 6px
            }

            .sidebar-nav a {
                display: flex;
                align-items: center;
                padding: 11px 15px;
                border-radius: 8px;
                color: var(--sidebar-text);
                transition: all .2s;
                font-weight: 500
            }

            .sidebar-nav a:hover {
                background: var(--border-light)
            }

            .sidebar-nav a.active {
                background: var(--primary-color);
                color: var(--card-bg);
                font-weight: 600
            }

            .sidebar-nav a.active .icon {
                color: var(--card-bg)
            }

            .sidebar-footer {
                margin-top: auto;
                padding-top: 15px;
                border-top: 1px solid var(--border-light)
            }

            .sidebar-footer ul {
                list-style: none;
                margin: 0;
                padding: 0
            }

            .sidebar-footer li {
                margin-bottom: 5px
            }

            .sidebar-footer a {
                display: flex;
                align-items: center;
                padding: 10px 15px;
                border-radius: 8px;
                color: var(--text-medium);
                font-weight: 500;
                transition: all .2s
            }

            .sidebar-footer a:hover {
                background: var(--border-light);
                color: var(--text-dark)
            }

            .main-wrapper {
                flex: 1;
                display: flex;
                flex-direction: column;
                overflow: hidden
            }

            .top-header {
                height: 60px;
                background: var(--content-bg);
                border-bottom: 1px solid var(--border-light);
                display: flex;
                justify-content: flex-end;
                align-items: center;
                padding: 0 24px;
                position: sticky;
                top: 0;
                z-index: 10;
                flex-shrink: 0
            }

            .notifications {
                margin-right: 20px;
                cursor: pointer;
                color: var(--text-medium)
            }

            .user-profile {
                display: flex;
                align-items: center;
                cursor: pointer;
                padding: 5px 10px;
                border-radius: 8px;
                transition: background-color .2s
            }

            .user-profile:hover {
                background: var(--border-light)
            }

            .user-profile .icon {
                font-size: 24px;
                margin-right: 8px;
                color: var(--text-medium)
            }

            .content-area {
                flex: 1;
                padding: 24px;
                overflow-y: auto
            }

            .content-area h1 {
                font-size: 2rem;
                margin-bottom: 20px;
                font-weight: 700
            }

            .card {
                background: var(--card-bg);
                border-radius: var(--border-radius-card);
                padding: 24px;
                box-shadow: var(--shadow-subtle);
                border: 1px solid var(--border-light)
            }

            .button {
                display: inline-flex;
                align-items: center;
                justify-content: center;
                padding: 10px 18px;
                border-radius: var(--border-radius-button);
                font-weight: 500;
                text-align: center;
                cursor: pointer;
                transition: all .2s;
                border: none;
                font-size: .9rem;
                min-width: 110px
            }

            .button-primary {
                background: var(--primary-color);
                color: #fff
            }

            .button-primary:hover {
                background: var(--primary-hover);
                transform: translateY(-1px)
            }

            .button-secondary {
                background: #F0F0F0;
                color: var(--text-dark);
                border: 1px solid var(--border-medium)
            }

            .button-secondary:hover {
                background: #E6E6E6;
                transform: translateY(-1px)
            }

            .button .icon {
                font-size: 16px;
                margin-left: 8px;
                color: inherit
            }

            a {
                text-decoration: none;
                color: var(--primary-color);
                transition: color .2s ease
            }

            a:hover {
                color: var(--primary-hover);
                text-decoration: none !important
            }

            h1,
            h2,
            h3,
            h4 {
                font-weight: 600;
                color: var(--text-dark)
            }

            html,
            body {
                height: 100%;
                overflow: hidden
            }

            .icon {
                font-weight: 400;
                font-style: normal;
                line-height: 1;
                letter-spacing: normal;
                text-transform: none;
                display: inline-block;
                white-space: nowrap;
                word-wrap: normal;
                direction: ltr;
                -webkit-font-smoothing: antialiased
            }

            .sidebar-nav a {
                cursor: pointer;
                text-decoration: none !important;
                border-bottom: none !important
            }

            .sidebar-nav a:hover,
            .sidebar-nav a:focus {
                background: var(--border-light) !important;
                color: var(--sidebar-text) !important;
                text-decoration: none !important;
                outline: none
            }
        </style>

        <div class="container-fluid" style="padding:0;">
            <div class="erp-container">
                <aside class="sidebar">
                    <div class="sidebar-header">FaCiencia</div>
                    <nav class="sidebar-nav">
                        <span class="nav-section-title">Navegação</span>
                        <ul>
                            <li><a href="<?php echo APP_URL; ?>/parceiro/dashboard-parceiro.php"><span
                                        class="icon">dashboard</span> Dashboard</a></li>
                        </ul>
                        <span class="nav-section-title">Acadêmico</span>
                        <ul>
                            <li><a href="<?php echo APP_URL; ?>/parceiro/cursos-parceiro.php"><span
                                        class="icon">school</span> Cursos</a></li>
                            <li><a href="<?php echo APP_URL; ?>/parceiro/alunos-parceiro.php" class="active"><span
                                        class="icon">group</span> Alunos</a></li>
                        </ul>
                        <span class="nav-section-title">Certificação</span>
                        <ul>
                            <li><a href="<?php echo APP_URL; ?>/parceiro/templates-parceiro.php"
                                    title="Templates de Certificados"><span class="icon">article</span> Templates</a>
                            </li>
                            <li><a href="<?php echo APP_URL; ?>/parceiro/gerar-certificados.php"><span
                                        class="icon">workspace_premium</span> Emitir Cert.</a></li>
                        </ul>
                        <span class="nav-section-title">Minha Conta</span>
                        <ul>
                            <li><a href="<?php echo APP_URL; ?>/parceiro/financeiro.php"><span
                                        class="icon">credit_card</span> Financeiro</a></li>
                            <li><a href="<?php echo APP_URL; ?>/parceiro/meu-plano.php"><span
                                        class="icon">price_check</span> Meu Plano</a></li>
                        </ul>
                    </nav>
                    <div class="sidebar-footer">
                        <ul>
                            <li><a href="<?php echo APP_URL; ?>/parceiro/perfil-parceiro.php"><span
                                        class="icon">person</span> Meu Perfil</a></li>
                            <li><a href="<?php echo APP_URL; ?>/app/actions/logout.php"><span class="icon">logout</span>
                                    Sair</a></li>
                        </ul>
                    </div>
                </aside>

                <div class="main-wrapper">
                    <header class="top-header">
                        <span class="icon notifications">notifications</span>
                        <div class="user-profile">
                            <span class="icon">account_circle</span>
                            <?php echo htmlspecialchars($user['nome'] ?? ($user['email'] ?? 'Usuário')); ?>
                        </div>
                    </header>

                    <main class="content-area">

                        <div class="container-fluid">
                            <!-- Page Heading - Design Moderno -->
                            <div
                                style="background: linear-gradient(135deg, rgba(110, 65, 193, 0.03) 0%, rgba(110, 65, 193, 0.01) 100%); border-radius: 16px; padding: 28px 32px; margin-bottom: 32px; border: 1px solid rgba(110, 65, 193, 0.08);">
                                <div class="d-flex align-items-center justify-content-between flex-wrap"
                                    style="gap: 20px;">
                                    <!-- Título e Descrição -->
                                    <div style="flex: 1; min-width: 250px;">
                                        <div style="display: flex; align-items: center; gap: 16px; margin-bottom: 8px;">
                                            <!-- Ícone Grande -->
                                            <div
                                                style="width: 56px; height: 56px; background: linear-gradient(135deg, #6E41C1 0%, #8B5FD6 100%); border-radius: 14px; display: flex; align-items: center; justify-content: center; box-shadow: 0 4px 12px rgba(110, 65, 193, 0.25);">
                                                <span class="material-icons-outlined"
                                                    style="font-size: 32px; color: white;">group</span>
                                            </div>
                                            <!-- Título -->
                                            <div>
                                                <h1
                                                    style="margin: 0; font-size: 28px; font-weight: 700; color: #1D1D1F; letter-spacing: -0.5px;">
                                                    Meus Alunos
                                                </h1>
                                                <p
                                                    style="margin: 4px 0 0 0; font-size: 14px; color: #86868B; font-weight: 500;">
                                                    Gerencie todos os alunos inscritos nos seus cursos
                                                </p>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Botões de Ação -->
                                    <div style="display: flex; gap: 12px; flex-wrap: wrap;">
                                        <button type="button" class="btn btn-secondary" data-toggle="modal"
                                            data-target="#importarAlunos"
                                            style="background: white; color: #1D1D1F; border: 2px solid #E5E5E7; border-radius: 12px; padding: 14px 24px; font-weight: 600; font-size: 15px; transition: all 0.3s ease; display: inline-flex; align-items: center; gap: 10px; white-space: nowrap;"
                                            onmouseover="this.style.background='#F5F5F7'; this.style.borderColor='#6E41C1'"
                                            onmouseout="this.style.background='white'; this.style.borderColor='#E5E5E7'">
                                            <span class="material-icons-outlined"
                                                style="font-size: 22px;">upload_file</span>
                                            <span>Importar Alunos</span>
                                        </button>
                                        <button type="button" class="btn btn-primary" data-toggle="modal"
                                            data-target="#novoAluno"
                                            style="background: linear-gradient(135deg, #6E41C1 0%, #8B5FD6 100%); border: none; border-radius: 12px; padding: 14px 28px; font-weight: 600; font-size: 15px; box-shadow: 0 4px 16px rgba(110, 65, 193, 0.3); transition: all 0.3s ease; display: inline-flex; align-items: center; gap: 10px; white-space: nowrap;"
                                            onmouseover="this.style.transform='translateY(-3px)'; this.style.boxShadow='0 8px 24px rgba(110, 65, 193, 0.4)'"
                                            onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 4px 16px rgba(110, 65, 193, 0.3)'">
                                            <span class="material-icons-outlined"
                                                style="font-size: 22px;">person_add</span>
                                            <span>Novo Aluno</span>
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <!-- Estatísticas de Alunos - Design Moderno -->
                            <div class="row mb-4">
                                <!-- Total de Alunos -->
                                <div class="col-xl-3 col-md-6 mb-4">
                                    <div class="card h-100"
                                        style="border: none; border-radius: 14px; background: white; box-shadow: 0 2px 8px rgba(0,0,0,0.08); border-left: 4px solid #6E41C1; transition: all 0.3s ease;"
                                        onmouseover="this.style.transform='translateY(-4px)'; this.style.boxShadow='0 8px 20px rgba(110, 65, 193, 0.15)'"
                                        onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 2px 8px rgba(0,0,0,0.08)'">
                                        <div class="card-body" style="padding: 20px;">
                                            <div
                                                style="display: flex; align-items: center; gap: 12px; margin-bottom: 12px;">
                                                <div
                                                    style="width: 48px; height: 48px; background: linear-gradient(135deg, rgba(110, 65, 193, 0.1) 0%, rgba(110, 65, 193, 0.05) 100%); border-radius: 12px; display: flex; align-items: center; justify-content: center;">
                                                    <span class="material-icons-outlined"
                                                        style="font-size: 24px; color: #6E41C1;">group</span>
                                                </div>
                                                <div>
                                                    <div
                                                        style="font-size: 11px; font-weight: 600; color: #6E41C1; text-transform: uppercase; letter-spacing: 0.5px;">
                                                        Total de Alunos</div>
                                                    <div
                                                        style="font-size: 28px; font-weight: 700; color: #1D1D1F; line-height: 1;">
                                                        <?php echo $total_alunos; ?></div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Alertas de Sucesso/Erro/Info - Design Moderno -->
                            <?php if (isset($_SESSION['success'])): ?>
                                <div class="alert alert-dismissible fade show" role="alert"
                                    style="background: linear-gradient(135deg, rgba(52, 199, 89, 0.1) 0%, rgba(52, 199, 89, 0.05) 100%); border: 2px solid rgba(52, 199, 89, 0.3); border-radius: 12px; padding: 16px 20px; margin-bottom: 24px; display: flex; align-items: center; gap: 12px;">
                                    <div
                                        style="width: 40px; height: 40px; background: #34C759; border-radius: 10px; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                                        <span class="material-icons-outlined"
                                            style="font-size: 24px; color: white;">check_circle</span>
                                    </div>
                                    <div style="flex: 1;">
                                        <strong style="color: #34C759; font-weight: 700; font-size: 14px;">Sucesso!</strong>
                                        <span
                                            style="color: #1D1D1F; font-size: 14px; margin-left: 8px;"><?php echo $_SESSION['success']; ?></span>
                                    </div>
                                    <button type="button" class="close" data-dismiss="alert" aria-label="Close"
                                        style="color: #34C759; opacity: 1; font-size: 24px; font-weight: 300; padding: 0; margin: 0; background: none; border: none; cursor: pointer; transition: all 0.2s ease;"
                                        onmouseover="this.style.transform='rotate(90deg)'"
                                        onmouseout="this.style.transform='rotate(0deg)'">
                                        <span aria-hidden="true">&times;</span>
                                    </button>
                                </div>
                                <?php unset($_SESSION['success']); ?>
                            <?php endif; ?>

                            <?php if (isset($_SESSION['error'])): ?>
                                <div class="alert alert-dismissible fade show" role="alert"
                                    style="background: linear-gradient(135deg, rgba(255, 59, 48, 0.1) 0%, rgba(255, 59, 48, 0.05) 100%); border: 2px solid rgba(255, 59, 48, 0.3); border-radius: 12px; padding: 16px 20px; margin-bottom: 24px; display: flex; align-items: center; gap: 12px;">
                                    <div
                                        style="width: 40px; height: 40px; background: #FF3B30; border-radius: 10px; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                                        <span class="material-icons-outlined"
                                            style="font-size: 24px; color: white;">error</span>
                                    </div>
                                    <div style="flex: 1;">
                                        <strong style="color: #FF3B30; font-weight: 700; font-size: 14px;">Erro!</strong>
                                        <span
                                            style="color: #1D1D1F; font-size: 14px; margin-left: 8px;"><?php echo $_SESSION['error']; ?></span>
                                    </div>
                                    <button type="button" class="close" data-dismiss="alert" aria-label="Close"
                                        style="color: #FF3B30; opacity: 1; font-size: 24px; font-weight: 300; padding: 0; margin: 0; background: none; border: none; cursor: pointer; transition: all 0.2s ease;"
                                        onmouseover="this.style.transform='rotate(90deg)'"
                                        onmouseout="this.style.transform='rotate(0deg)'">
                                        <span aria-hidden="true">&times;</span>
                                    </button>
                                </div>
                                <?php unset($_SESSION['error']); ?>
                            <?php endif; ?>

                            <?php if (empty($alunos)): ?>
                                <div class="alert alert-dismissible fade show" role="alert"
                                    style="background: linear-gradient(135deg, rgba(0, 122, 255, 0.1) 0%, rgba(0, 122, 255, 0.05) 100%); border: 2px solid rgba(0, 122, 255, 0.3); border-radius: 12px; padding: 16px 20px; margin-bottom: 24px; display: flex; align-items: center; gap: 12px;">
                                    <div
                                        style="width: 40px; height: 40px; background: #007AFF; border-radius: 10px; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                                        <span class="material-icons-outlined"
                                            style="font-size: 24px; color: white;">info</span>
                                    </div>
                                    <div style="flex: 1;">
                                        <strong style="color: #007AFF; font-weight: 700; font-size: 14px;">Nenhum aluno
                                            cadastrado!</strong>
                                        <span style="color: #1D1D1F; font-size: 14px; margin-left: 8px;">Comece importando
                                            alunos ou criando um novo.</span>
                                    </div>
                                    <button type="button" class="close" data-dismiss="alert" aria-label="Close"
                                        style="color: #007AFF; opacity: 1; font-size: 24px; font-weight: 300; padding: 0; margin: 0; background: none; border: none; cursor: pointer; transition: all 0.2s ease;"
                                        onmouseover="this.style.transform='rotate(90deg)'"
                                        onmouseout="this.style.transform='rotate(0deg)'">
                                        <span aria-hidden="true">&times;</span>
                                    </button>
                                </div>
                            <?php endif; ?>

                            <!-- Tabela de Alunos - Design Moderno -->
                            <div class="card"
                                style="border: none; border-radius: 14px; background: white; box-shadow: 0 2px 8px rgba(0,0,0,0.08);">
                                <div class="card-header"
                                    style="background: linear-gradient(135deg, rgba(110, 65, 193, 0.05) 0%, rgba(110, 65, 193, 0.02) 100%); border-radius: 14px 14px 0 0; padding: 20px 24px; border: none; border-bottom: 1px solid #F5F5F7;">
                                    <div style="display: flex; justify-content: space-between; align-items: center;">
                                        <div style="display: flex; align-items: center; gap: 12px;">
                                            <span class="material-icons-outlined"
                                                style="font-size: 24px; color: #6E41C1;">list_alt</span>
                                            <h6 style="margin: 0; font-weight: 700; color: #1D1D1F; font-size: 16px;">
                                                Lista de Alunos</h6>
                                        </div>
                                        <span
                                            style="background: #6E41C1; color: white; padding: 4px 12px; border-radius: 20px; font-size: 12px; font-weight: 600;">
                                            <?php echo $total_alunos; ?> aluno(s)
                                        </span>
                                    </div>
                                </div>
                                <div class="card-body" style="padding: 0;">
                                    <div class="table-responsive">
                                        <table class="table table-hover" width="100%" cellspacing="0" id="alunosTable"
                                            style="margin-bottom: 0;">
                                            <thead>
                                                <tr style="background: #F5F5F7; border-bottom: 2px solid #E5E5E7;">
                                                    <th
                                                        style="padding: 16px 20px; font-size: 12px; font-weight: 700; color: #6E41C1; text-transform: uppercase; letter-spacing: 0.5px; border: none;">
                                                        <span class="material-icons-outlined"
                                                            style="font-size: 16px; vertical-align: middle; margin-right: 6px;">person</span>
                                                        Nome
                                                    </th>
                                                    <th
                                                        style="padding: 16px 20px; font-size: 12px; font-weight: 700; color: #6E41C1; text-transform: uppercase; letter-spacing: 0.5px; border: none;">
                                                        <span class="material-icons-outlined"
                                                            style="font-size: 16px; vertical-align: middle; margin-right: 6px;">email</span>
                                                        Email
                                                    </th>
                                                    <th
                                                        style="padding: 16px 20px; font-size: 12px; font-weight: 700; color: #6E41C1; text-transform: uppercase; letter-spacing: 0.5px; border: none;">
                                                        <span class="material-icons-outlined"
                                                            style="font-size: 16px; vertical-align: middle; margin-right: 6px;">badge</span>
                                                        CPF
                                                    </th>
                                                    <th
                                                        style="padding: 16px 20px; font-size: 12px; font-weight: 700; color: #6E41C1; text-transform: uppercase; letter-spacing: 0.5px; border: none;">
                                                        <span class="material-icons-outlined"
                                                            style="font-size: 16px; vertical-align: middle; margin-right: 6px;">calendar_today</span>
                                                        Data de Registro
                                                    </th>
                                                    <th class="text-center"
                                                        style="padding: 16px 20px; font-size: 12px; font-weight: 700; color: #6E41C1; text-transform: uppercase; letter-spacing: 0.5px; border: none;">
                                                        <span class="material-icons-outlined"
                                                            style="font-size: 16px; vertical-align: middle; margin-right: 6px;">settings</span>
                                                        Ações
                                                    </th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($alunos as $aluno): ?>
                                                    <tr style="border-bottom: 1px solid #F5F5F7; transition: all 0.2s ease;"
                                                        onmouseover="this.style.background='rgba(110, 65, 193, 0.02)'"
                                                        onmouseout="this.style.background='white'">
                                                        <td style="padding: 16px 20px; border: none;">
                                                            <div style="display: flex; align-items: center; gap: 10px;">
                                                                <div
                                                                    style="width: 36px; height: 36px; background: linear-gradient(135deg, rgba(110, 65, 193, 0.1) 0%, rgba(110, 65, 193, 0.05) 100%); border-radius: 8px; display: flex; align-items: center; justify-content: center;">
                                                                    <span class="material-icons-outlined"
                                                                        style="font-size: 18px; color: #6E41C1;">person</span>
                                                                </div>
                                                                <strong
                                                                    style="color: #1D1D1F; font-size: 14px; font-weight: 600;"><?php echo htmlspecialchars($aluno['nome']); ?></strong>
                                                            </div>
                                                        </td>
                                                        <td style="padding: 16px 20px; border: none;">
                                                            <span
                                                                style="color: #86868B; font-size: 13px; display: inline-flex; align-items: center; gap: 6px;">
                                                                <span class="material-icons-outlined"
                                                                    style="font-size: 16px;">alternate_email</span>
                                                                <?php echo htmlspecialchars($aluno['email']); ?>
                                                            </span>
                                                        </td>
                                                        <td style="padding: 16px 20px; border: none;">
                                                            <code
                                                                style="background: linear-gradient(135deg, rgba(110, 65, 193, 0.05) 0%, rgba(110, 65, 193, 0.02) 100%); color: #6E41C1; padding: 4px 10px; border-radius: 6px; font-size: 12px; font-weight: 600;">
                                                    <?php echo htmlspecialchars($aluno['cpf']); ?>
                                                </code>
                                                        </td>
                                                        <td style="padding: 16px 20px; border: none;">
                                                            <span
                                                                style="color: #86868B; font-size: 13px; display: inline-flex; align-items: center; gap: 6px;">
                                                                <span class="material-icons-outlined"
                                                                    style="font-size: 16px;">event</span>
                                                                <?php echo date('d/m/Y', strtotime($aluno['criado_em'])); ?>
                                                            </span>
                                                        </td>
                                                        <td class="text-center" style="padding: 16px 20px; border: none;">
                                                            <div
                                                                style="display: inline-flex; gap: 6px; justify-content: center;">
                                                                <!-- Botão Visualizar -->
                                                                <button type="button" data-toggle="modal"
                                                                    data-target="#visualizarAluno-<?php echo (int) $aluno['id']; ?>"
                                                                    title="Visualizar detalhes"
                                                                    style="background: linear-gradient(135deg, rgba(110, 65, 193, 0.1) 0%, rgba(110, 65, 193, 0.05) 100%); color: #6E41C1; border: none; border-radius: 8px; padding: 8px 10px; cursor: pointer; transition: all 0.2s ease; display: inline-flex; align-items: center; justify-content: center;"
                                                                    onmouseover="this.style.background='#6E41C1'; this.style.color='white'; this.style.transform='translateY(-2px)'; this.style.boxShadow='0 4px 8px rgba(110, 65, 193, 0.3)'"
                                                                    onmouseout="this.style.background='linear-gradient(135deg, rgba(110, 65, 193, 0.1) 0%, rgba(110, 65, 193, 0.05) 100%)'; this.style.color='#6E41C1'; this.style.transform='translateY(0)'; this.style.boxShadow='none'">
                                                                    <span class="material-icons-outlined"
                                                                        style="font-size: 18px;">visibility</span>
                                                                </button>

                                                                <!-- Botão Editar -->
                                                                <button type="button" data-toggle="modal"
                                                                    data-target="#editarAluno-<?php echo (int) $aluno['id']; ?>"
                                                                    title="Editar aluno"
                                                                    style="background: linear-gradient(135deg, rgba(0, 122, 255, 0.1) 0%, rgba(0, 122, 255, 0.05) 100%); color: #007AFF; border: none; border-radius: 8px; padding: 8px 10px; cursor: pointer; transition: all 0.2s ease; display: inline-flex; align-items: center; justify-content: center;"
                                                                    onmouseover="this.style.background='#007AFF'; this.style.color='white'; this.style.transform='translateY(-2px)'; this.style.boxShadow='0 4px 8px rgba(0, 122, 255, 0.3)'"
                                                                    onmouseout="this.style.background='linear-gradient(135deg, rgba(0, 122, 255, 0.1) 0%, rgba(0, 122, 255, 0.05) 100%)'; this.style.color='#007AFF'; this.style.transform='translateY(0)'; this.style.boxShadow='none'">
                                                                    <span class="material-icons-outlined"
                                                                        style="font-size: 18px;">edit</span>
                                                                </button>

                                                                <!-- Botão Excluir -->
                                                                <form
                                                                    action="<?php echo APP_URL; ?>/app/actions/excluir-aluno.php"
                                                                    method="post" style="display:inline; margin: 0;"
                                                                    onsubmit="return confirm('⚠️ Tem certeza que deseja excluir este aluno?\n\n❌ Esta ação é irreversível!\n✖️ Certificados e inscrições serão removidos.');">
                                                                    <input type="hidden" name="id"
                                                                        value="<?php echo (int) $aluno['id']; ?>">
                                                                    <button type="submit" title="Excluir aluno"
                                                                        style="background: linear-gradient(135deg, rgba(255, 59, 48, 0.1) 0%, rgba(255, 59, 48, 0.05) 100%); color: #FF3B30; border: none; border-radius: 8px; padding: 8px 10px; cursor: pointer; transition: all 0.2s ease; display: inline-flex; align-items: center; justify-content: center;"
                                                                        onmouseover="this.style.background='#FF3B30'; this.style.color='white'; this.style.transform='translateY(-2px)'; this.style.boxShadow='0 4px 8px rgba(255, 59, 48, 0.3)'"
                                                                        onmouseout="this.style.background='linear-gradient(135deg, rgba(255, 59, 48, 0.1) 0%, rgba(255, 59, 48, 0.05) 100%)'; this.style.color='#FF3B30'; this.style.transform='translateY(0)'; this.style.boxShadow='none'">
                                                                        <span class="material-icons-outlined"
                                                                            style="font-size: 18px;">delete</span>
                                                                    </button>
                                                                </form>
                                                            </div>
                                                        </td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                </div>


            </div>

            <!-- Modais de Visualização de Aluno -->
            <?php foreach ($alunos as $aluno): ?>
                <div class="modal fade" id="visualizarAluno-<?php echo (int) $aluno['id']; ?>" tabindex="-1" role="dialog"
                    aria-hidden="true">
                    <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
                        <div class="modal-content"
                            style="border-radius: 16px; border: none; box-shadow: 0 20px 60px rgba(110, 65, 193, 0.3);">
                            <!-- Header Moderno -->
                            <div class="modal-header"
                                style="background: linear-gradient(135deg, #6E41C1 0%, #8B5FD6 100%); border-radius: 16px 16px 0 0; padding: 24px 32px; border: none;">
                                <div style="display: flex; align-items: center; gap: 12px;">
                                    <div
                                        style="width: 48px; height: 48px; background: rgba(255,255,255,0.2); border-radius: 12px; display: flex; align-items: center; justify-content: center;">
                                        <span class="material-icons-outlined"
                                            style="font-size: 28px; color: white;">person</span>
                                    </div>
                                    <div>
                                        <h5 class="modal-title"
                                            style="color: white; font-weight: 600; font-size: 22px; margin: 0;">
                                            Detalhes do Aluno
                                        </h5>
                                        <p style="color: rgba(255,255,255,0.9); font-size: 13px; margin: 4px 0 0 0;">
                                            Informações completas do cadastro</p>
                                    </div>
                                </div>
                                <button type="button" class="close" data-dismiss="modal" aria-label="Close"
                                    style="color: white; opacity: 1; text-shadow: none; font-size: 32px; font-weight: 300; margin: 0; padding: 0;">
                                    <span aria-hidden="true">&times;</span>
                                </button>
                            </div>

                            <div class="modal-body" style="padding: 32px;">
                                <!-- Informações Pessoais -->
                                <div style="margin-bottom: 24px;">
                                    <h6
                                        style="color: #6E41C1; font-weight: 700; font-size: 14px; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 16px; display: flex; align-items: center; gap: 8px;">
                                        <span class="material-icons-outlined" style="font-size: 20px;">person</span>
                                        Informações Pessoais
                                    </h6>
                                    <div class="row">
                                        <div class="col-md-6" style="margin-bottom: 16px;">
                                            <div style="background: #F5F5F7; border-radius: 10px; padding: 12px 16px;">
                                                <label
                                                    style="font-size: 11px; font-weight: 600; color: #86868B; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 4px; display: block;">Nome
                                                    Completo</label>
                                                <p style="font-size: 15px; font-weight: 600; color: #1D1D1F; margin: 0;">
                                                    <?php echo htmlspecialchars($aluno['nome']); ?></p>
                                            </div>
                                        </div>
                                        <div class="col-md-6" style="margin-bottom: 16px;">
                                            <div style="background: #F5F5F7; border-radius: 10px; padding: 12px 16px;">
                                                <label
                                                    style="font-size: 11px; font-weight: 600; color: #86868B; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 4px; display: block;">Email</label>
                                                <p
                                                    style="font-size: 15px; font-weight: 600; color: #1D1D1F; margin: 0; word-break: break-all;">
                                                    <?php echo htmlspecialchars($aluno['email']); ?></p>
                                            </div>
                                        </div>
                                        <div class="col-md-6" style="margin-bottom: 16px;">
                                            <div style="background: #F5F5F7; border-radius: 10px; padding: 12px 16px;">
                                                <label
                                                    style="font-size: 11px; font-weight: 600; color: #86868B; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 4px; display: block;">CPF</label>
                                                <p style="font-size: 15px; font-weight: 600; color: #1D1D1F; margin: 0;">
                                                    <?php echo $aluno['cpf'] ? htmlspecialchars($aluno['cpf']) : '<span style="color: #86868B;">Não informado</span>'; ?>
                                                </p>
                                            </div>
                                        </div>
                                        <div class="col-md-6" style="margin-bottom: 16px;">
                                            <div style="background: #F5F5F7; border-radius: 10px; padding: 12px 16px;">
                                                <label
                                                    style="font-size: 11px; font-weight: 600; color: #86868B; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 4px; display: block;">Telefone</label>
                                                <p style="font-size: 15px; font-weight: 600; color: #1D1D1F; margin: 0;">
                                                    <?php echo $aluno['telefone'] ? htmlspecialchars($aluno['telefone']) : '<span style="color: #86868B;">Não informado</span>'; ?>
                                                </p>
                                            </div>
                                        </div>
                                        <div class="col-md-6" style="margin-bottom: 16px;">
                                            <div style="background: #F5F5F7; border-radius: 10px; padding: 12px 16px;">
                                                <label
                                                    style="font-size: 11px; font-weight: 600; color: #86868B; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 4px; display: block;">Data
                                                    de Nascimento</label>
                                                <p style="font-size: 15px; font-weight: 600; color: #1D1D1F; margin: 0;">
                                                    <?php echo $aluno['data_nascimento'] ? date('d/m/Y', strtotime($aluno['data_nascimento'])) : '<span style="color: #86868B;">Não informado</span>'; ?>
                                                </p>
                                            </div>
                                        </div>
                                        <div class="col-md-6" style="margin-bottom: 16px;">
                                            <div style="background: #F5F5F7; border-radius: 10px; padding: 12px 16px;">
                                                <label
                                                    style="font-size: 11px; font-weight: 600; color: #86868B; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 4px; display: block;">Data
                                                    de Cadastro</label>
                                                <p style="font-size: 15px; font-weight: 600; color: #1D1D1F; margin: 0;">
                                                    <?php echo date('d/m/Y', strtotime($aluno['criado_em'])); ?></p>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Endereço -->
                                <div style="margin-bottom: 0;">
                                    <h6
                                        style="color: #6E41C1; font-weight: 700; font-size: 14px; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 16px; display: flex; align-items: center; gap: 8px;">
                                        <span class="material-icons-outlined" style="font-size: 20px;">location_on</span>
                                        Endereço
                                    </h6>
                                    <div class="row">
                                        <div class="col-md-12" style="margin-bottom: 16px;">
                                            <div style="background: #F5F5F7; border-radius: 10px; padding: 12px 16px;">
                                                <label
                                                    style="font-size: 11px; font-weight: 600; color: #86868B; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 4px; display: block;">Endereço
                                                    Completo</label>
                                                <p style="font-size: 15px; font-weight: 600; color: #1D1D1F; margin: 0;">
                                                    <?php echo $aluno['endereco'] ? htmlspecialchars($aluno['endereco']) : '<span style="color: #86868B;">Não informado</span>'; ?>
                                                </p>
                                            </div>
                                        </div>
                                        <div class="col-md-5" style="margin-bottom: 16px;">
                                            <div style="background: #F5F5F7; border-radius: 10px; padding: 12px 16px;">
                                                <label
                                                    style="font-size: 11px; font-weight: 600; color: #86868B; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 4px; display: block;">Cidade</label>
                                                <p style="font-size: 15px; font-weight: 600; color: #1D1D1F; margin: 0;">
                                                    <?php echo $aluno['cidade'] ? htmlspecialchars($aluno['cidade']) : '<span style="color: #86868B;">Não informado</span>'; ?>
                                                </p>
                                            </div>
                                        </div>
                                        <div class="col-md-3" style="margin-bottom: 16px;">
                                            <div style="background: #F5F5F7; border-radius: 10px; padding: 12px 16px;">
                                                <label
                                                    style="font-size: 11px; font-weight: 600; color: #86868B; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 4px; display: block;">Estado</label>
                                                <p style="font-size: 15px; font-weight: 600; color: #1D1D1F; margin: 0;">
                                                    <?php echo $aluno['estado'] ? htmlspecialchars($aluno['estado']) : '<span style="color: #86868B;">-</span>'; ?>
                                                </p>
                                            </div>
                                        </div>
                                        <div class="col-md-4" style="margin-bottom: 16px;">
                                            <div style="background: #F5F5F7; border-radius: 10px; padding: 12px 16px;">
                                                <label
                                                    style="font-size: 11px; font-weight: 600; color: #86868B; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 4px; display: block;">CEP</label>
                                                <p style="font-size: 15px; font-weight: 600; color: #1D1D1F; margin: 0;">
                                                    <?php echo $aluno['cep'] ? htmlspecialchars($aluno['cep']) : '<span style="color: #86868B;">Não informado</span>'; ?>
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Footer Moderno -->
                            <div class="modal-footer"
                                style="background: #F5F5F7; border-radius: 0 0 16px 16px; padding: 20px 32px; border: none; justify-content: space-between;">
                                <button type="button" class="btn btn-secondary" data-dismiss="modal"
                                    style="background: white; color: #1D1D1F; border: 2px solid #E5E5E7; border-radius: 10px; padding: 10px 24px; font-weight: 600; font-size: 14px; transition: all 0.3s ease;"
                                    onmouseover="this.style.background='#F5F5F7'"
                                    onmouseout="this.style.background='white'">
                                    <span class="material-icons-outlined"
                                        style="font-size: 18px; vertical-align: middle; margin-right: 6px;">close</span>
                                    Fechar
                                </button>
                                <button type="button" data-dismiss="modal" data-toggle="modal"
                                    data-target="#editarAluno-<?php echo (int) $aluno['id']; ?>"
                                    style="background: linear-gradient(135deg, #6E41C1 0%, #8B5FD6 100%); color: white; border: none; border-radius: 10px; padding: 10px 28px; font-weight: 600; font-size: 14px; box-shadow: 0 4px 12px rgba(110, 65, 193, 0.3); transition: all 0.3s ease;"
                                    onmouseover="this.style.transform='translateY(-2px)'; this.style.boxShadow='0 6px 20px rgba(110, 65, 193, 0.4)'"
                                    onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 4px 12px rgba(110, 65, 193, 0.3)'">
                                    <span class="material-icons-outlined"
                                        style="font-size: 18px; vertical-align: middle; margin-right: 6px;">edit</span>
                                    Editar Aluno
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>

            <!-- Modais de Edição de Aluno -->
            <?php foreach ($alunos as $aluno): ?>
                <div class="modal fade" id="editarAluno-<?php echo (int) $aluno['id']; ?>" tabindex="-1" role="dialog"
                    aria-hidden="true">
                    <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
                        <div class="modal-content"
                            style="border-radius: 16px; border: none; box-shadow: 0 20px 60px rgba(0, 122, 255, 0.3);">
                            <!-- Header Moderno com Gradiente Azul -->
                            <div class="modal-header"
                                style="background: linear-gradient(135deg, #007AFF 0%, #5856D6 100%); border-radius: 16px 16px 0 0; padding: 24px 32px; border: none;">
                                <div style="display: flex; align-items: center; gap: 12px;">
                                    <div
                                        style="width: 48px; height: 48px; background: rgba(255,255,255,0.2); border-radius: 12px; display: flex; align-items: center; justify-content: center;">
                                        <span class="material-icons-outlined"
                                            style="font-size: 28px; color: white;">edit</span>
                                    </div>
                                    <div>
                                        <h5 class="modal-title"
                                            style="color: white; font-weight: 600; font-size: 22px; margin: 0;">
                                            Editar Aluno
                                        </h5>
                                        <p style="color: rgba(255,255,255,0.9); font-size: 13px; margin: 4px 0 0 0;">
                                            Atualize as informações do aluno</p>
                                    </div>
                                </div>
                                <button type="button" class="close" data-dismiss="modal" aria-label="Close"
                                    style="color: white; opacity: 1; text-shadow: none; font-size: 32px; font-weight: 300; margin: 0; padding: 0;">
                                    <span aria-hidden="true">&times;</span>
                                </button>
                            </div>

                            <form method="POST" action="../app/actions/editar-aluno.php">
                                <input type="hidden" name="id" value="<?php echo (int) $aluno['id']; ?>">
                                <div class="modal-body" style="padding: 32px;">
                                    <!-- Nome Completo -->
                                    <div class="form-group" style="margin-bottom: 20px;">
                                        <label
                                            style="display: flex; align-items: center; gap: 8px; font-weight: 600; color: #1D1D1F; margin-bottom: 8px; font-size: 14px;">
                                            <span class="material-icons-outlined"
                                                style="font-size: 20px; color: #007AFF;">person</span>
                                            Nome Completo <span style="color: #FF3B30;">*</span>
                                        </label>
                                        <input type="text" class="form-control" name="nome"
                                            value="<?php echo htmlspecialchars($aluno['nome']); ?>" required
                                            style="border: 2px solid #E5E5E7; border-radius: 10px; padding: 12px 16px; font-size: 15px; transition: all 0.3s ease;"
                                            onfocus="this.style.borderColor='#007AFF'; this.style.boxShadow='0 0 0 4px rgba(0, 122, 255, 0.1)'"
                                            onblur="this.style.borderColor='#E5E5E7'; this.style.boxShadow='none'">
                                    </div>

                                    <!-- Email -->
                                    <div class="form-group" style="margin-bottom: 20px;">
                                        <label
                                            style="display: flex; align-items: center; gap: 8px; font-weight: 600; color: #1D1D1F; margin-bottom: 8px; font-size: 14px;">
                                            <span class="material-icons-outlined"
                                                style="font-size: 20px; color: #007AFF;">email</span>
                                            Email <span style="color: #FF3B30;">*</span>
                                        </label>
                                        <input type="email" class="form-control" name="email"
                                            value="<?php echo htmlspecialchars($aluno['email']); ?>" required
                                            style="border: 2px solid #E5E5E7; border-radius: 10px; padding: 12px 16px; font-size: 15px; transition: all 0.3s ease;"
                                            onfocus="this.style.borderColor='#007AFF'; this.style.boxShadow='0 0 0 4px rgba(0, 122, 255, 0.1)'"
                                            onblur="this.style.borderColor='#E5E5E7'; this.style.boxShadow='none'">
                                    </div>

                                    <!-- CPF e Telefone -->
                                    <div class="form-row">
                                        <div class="form-group col-md-6" style="margin-bottom: 20px;">
                                            <label
                                                style="display: flex; align-items: center; gap: 8px; font-weight: 600; color: #1D1D1F; margin-bottom: 8px; font-size: 14px;">
                                                <span class="material-icons-outlined"
                                                    style="font-size: 20px; color: #007AFF;">badge</span>
                                                CPF
                                            </label>
                                            <input type="text" class="form-control" name="cpf"
                                                value="<?php echo htmlspecialchars($aluno['cpf'] ?? ''); ?>"
                                                placeholder="000.000.000-00"
                                                style="border: 2px solid #E5E5E7; border-radius: 10px; padding: 12px 16px; font-size: 15px; transition: all 0.3s ease;"
                                                onfocus="this.style.borderColor='#007AFF'; this.style.boxShadow='0 0 0 4px rgba(0, 122, 255, 0.1)'"
                                                onblur="this.style.borderColor='#E5E5E7'; this.style.boxShadow='none'">
                                        </div>
                                        <div class="form-group col-md-6" style="margin-bottom: 20px;">
                                            <label
                                                style="display: flex; align-items: center; gap: 8px; font-weight: 600; color: #1D1D1F; margin-bottom: 8px; font-size: 14px;">
                                                <span class="material-icons-outlined"
                                                    style="font-size: 20px; color: #007AFF;">phone</span>
                                                Telefone
                                            </label>
                                            <input type="text" class="form-control" name="telefone"
                                                value="<?php echo htmlspecialchars($aluno['telefone'] ?? ''); ?>"
                                                placeholder="(11) 99999-9999"
                                                style="border: 2px solid #E5E5E7; border-radius: 10px; padding: 12px 16px; font-size: 15px; transition: all 0.3s ease;"
                                                onfocus="this.style.borderColor='#007AFF'; this.style.boxShadow='0 0 0 4px rgba(0, 122, 255, 0.1)'"
                                                onblur="this.style.borderColor='#E5E5E7'; this.style.boxShadow='none'">
                                        </div>
                                    </div>

                                    <!-- Data de Nascimento -->
                                    <div class="form-group" style="margin-bottom: 20px;">
                                        <label
                                            style="display: flex; align-items: center; gap: 8px; font-weight: 600; color: #1D1D1F; margin-bottom: 8px; font-size: 14px;">
                                            <span class="material-icons-outlined"
                                                style="font-size: 20px; color: #007AFF;">cake</span>
                                            Data de Nascimento
                                        </label>
                                        <input type="date" class="form-control" name="data_nascimento"
                                            value="<?php echo htmlspecialchars($aluno['data_nascimento'] ?? ''); ?>"
                                            style="border: 2px solid #E5E5E7; border-radius: 10px; padding: 12px 16px; font-size: 15px; transition: all 0.3s ease;"
                                            onfocus="this.style.borderColor='#007AFF'; this.style.boxShadow='0 0 0 4px rgba(0, 122, 255, 0.1)'"
                                            onblur="this.style.borderColor='#E5E5E7'; this.style.boxShadow='none'">
                                    </div>

                                    <!-- Endereço -->
                                    <div class="form-group" style="margin-bottom: 20px;">
                                        <label
                                            style="display: flex; align-items: center; gap: 8px; font-weight: 600; color: #1D1D1F; margin-bottom: 8px; font-size: 14px;">
                                            <span class="material-icons-outlined"
                                                style="font-size: 20px; color: #007AFF;">home</span>
                                            Endereço
                                        </label>
                                        <input type="text" class="form-control" name="endereco"
                                            value="<?php echo htmlspecialchars($aluno['endereco'] ?? ''); ?>"
                                            placeholder="Rua, número, complemento"
                                            style="border: 2px solid #E5E5E7; border-radius: 10px; padding: 12px 16px; font-size: 15px; transition: all 0.3s ease;"
                                            onfocus="this.style.borderColor='#007AFF'; this.style.boxShadow='0 0 0 4px rgba(0, 122, 255, 0.1)'"
                                            onblur="this.style.borderColor='#E5E5E7'; this.style.boxShadow='none'">
                                    </div>

                                    <!-- Cidade, Estado e CEP -->
                                    <div class="form-row">
                                        <div class="form-group col-md-6" style="margin-bottom: 20px;">
                                            <label
                                                style="display: flex; align-items: center; gap: 8px; font-weight: 600; color: #1D1D1F; margin-bottom: 8px; font-size: 14px;">
                                                <span class="material-icons-outlined"
                                                    style="font-size: 20px; color: #007AFF;">location_city</span>
                                                Cidade
                                            </label>
                                            <input type="text" class="form-control" name="cidade"
                                                value="<?php echo htmlspecialchars($aluno['cidade'] ?? ''); ?>"
                                                placeholder="Cidade"
                                                style="border: 2px solid #E5E5E7; border-radius: 10px; padding: 12px 16px; font-size: 15px; transition: all 0.3s ease;"
                                                onfocus="this.style.borderColor='#007AFF'; this.style.boxShadow='0 0 0 4px rgba(0, 122, 255, 0.1)'"
                                                onblur="this.style.borderColor='#E5E5E7'; this.style.boxShadow='none'">
                                        </div>
                                        <div class="form-group col-md-3" style="margin-bottom: 20px;">
                                            <label
                                                style="display: flex; align-items: center; gap: 8px; font-weight: 600; color: #1D1D1F; margin-bottom: 8px; font-size: 14px;">
                                                <span class="material-icons-outlined"
                                                    style="font-size: 20px; color: #007AFF;">map</span>
                                                Estado
                                            </label>
                                            <input type="text" class="form-control" name="estado"
                                                value="<?php echo htmlspecialchars($aluno['estado'] ?? ''); ?>"
                                                maxlength="2" placeholder="SP"
                                                style="border: 2px solid #E5E5E7; border-radius: 10px; padding: 12px 16px; font-size: 15px; transition: all 0.3s ease;"
                                                onfocus="this.style.borderColor='#007AFF'; this.style.boxShadow='0 0 0 4px rgba(0, 122, 255, 0.1)'"
                                                onblur="this.style.borderColor='#E5E5E7'; this.style.boxShadow='none'">
                                        </div>
                                        <div class="form-group col-md-3" style="margin-bottom: 20px;">
                                            <label
                                                style="display: flex; align-items: center; gap: 8px; font-weight: 600; color: #1D1D1F; margin-bottom: 8px; font-size: 14px;">
                                                <span class="material-icons-outlined"
                                                    style="font-size: 20px; color: #007AFF;">pin_drop</span>
                                                CEP
                                            </label>
                                            <input type="text" class="form-control" name="cep"
                                                value="<?php echo htmlspecialchars($aluno['cep'] ?? ''); ?>"
                                                placeholder="00000-000"
                                                style="border: 2px solid #E5E5E7; border-radius: 10px; padding: 12px 16px; font-size: 15px; transition: all 0.3s ease;"
                                                onfocus="this.style.borderColor='#007AFF'; this.style.boxShadow='0 0 0 4px rgba(0, 122, 255, 0.1)'"
                                                onblur="this.style.borderColor='#E5E5E7'; this.style.boxShadow='none'">
                                        </div>
                                    </div>
                                </div>

                                <!-- Footer Moderno -->
                                <div class="modal-footer"
                                    style="background: #F5F5F7; border-radius: 0 0 16px 16px; padding: 20px 32px; border: none; gap: 12px;">
                                    <button type="button" class="btn btn-secondary" data-dismiss="modal"
                                        style="background: white; color: #1D1D1F; border: 2px solid #E5E5E7; border-radius: 10px; padding: 10px 24px; font-weight: 600; font-size: 14px; transition: all 0.3s ease;"
                                        onmouseover="this.style.background='#F5F5F7'"
                                        onmouseout="this.style.background='white'">
                                        <span class="material-icons-outlined"
                                            style="font-size: 18px; vertical-align: middle; margin-right: 6px;">close</span>
                                        Cancelar
                                    </button>
                                    <button type="submit" class="btn btn-primary"
                                        style="background: linear-gradient(135deg, #007AFF 0%, #5856D6 100%); color: white; border: none; border-radius: 10px; padding: 10px 28px; font-weight: 600; font-size: 14px; box-shadow: 0 4px 12px rgba(0, 122, 255, 0.3); transition: all 0.3s ease;"
                                        onmouseover="this.style.transform='translateY(-2px)'; this.style.boxShadow='0 6px 20px rgba(0, 122, 255, 0.4)'"
                                        onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 4px 12px rgba(0, 122, 255, 0.3)'">
                                        <span class="material-icons-outlined"
                                            style="font-size: 18px; vertical-align: middle; margin-right: 6px;">save</span>
                                        Salvar Alterações
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>

            <!-- Modal Importar Alunos - Design Moderno -->
            <div class="modal fade" id="importarAlunos" tabindex="-1" role="dialog" aria-hidden="true">
                <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
                    <div class="modal-content"
                        style="border-radius: 16px; border: none; box-shadow: 0 20px 60px rgba(0, 122, 255, 0.3);">
                        <!-- Header Moderno com Gradiente Azul -->
                        <div class="modal-header"
                            style="background: linear-gradient(135deg, #007AFF 0%, #5856D6 100%); border-radius: 16px 16px 0 0; padding: 24px 32px; border: none;">
                            <div style="display: flex; align-items: center; gap: 12px;">
                                <div
                                    style="width: 48px; height: 48px; background: rgba(255,255,255,0.2); border-radius: 12px; display: flex; align-items: center; justify-content: center;">
                                    <span class="material-icons-outlined"
                                        style="font-size: 28px; color: white;">upload_file</span>
                                </div>
                                <div>
                                    <h5 class="modal-title"
                                        style="color: white; font-weight: 600; font-size: 22px; margin: 0;">
                                        Importar Alunos
                                    </h5>
                                    <p style="color: rgba(255,255,255,0.9); font-size: 13px; margin: 4px 0 0 0;">
                                        Adicione múltiplos alunos de uma vez</p>
                                </div>
                            </div>
                            <button type="button" class="close" data-dismiss="modal" aria-label="Close"
                                style="color: white; opacity: 1; text-shadow: none; font-size: 32px; font-weight: 300; margin: 0; padding: 0;">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>

                        <form method="POST" action="../app/actions/importar-alunos.php" enctype="multipart/form-data">
                            <div class="modal-body" style="padding: 32px;">
                                <!-- Info Box -->
                                <div
                                    style="background: linear-gradient(135deg, rgba(0, 122, 255, 0.05) 0%, rgba(88, 86, 214, 0.05) 100%); border-radius: 12px; padding: 16px; margin-bottom: 24px; border-left: 4px solid #007AFF;">
                                    <div style="display: flex; align-items: start; gap: 12px;">
                                        <span class="material-icons-outlined"
                                            style="font-size: 24px; color: #007AFF;">info</span>
                                        <div>
                                            <strong style="color: #007AFF; font-size: 14px;">Importe uma planilha Excel
                                                ou CSV</strong>
                                            <p style="color: #86868B; font-size: 13px; margin: 4px 0 0 0;">Adicione
                                                múltiplos alunos de uma vez usando um arquivo de planilha.</p>
                                        </div>
                                    </div>
                                </div>

                                <!-- Upload de Arquivo -->
                                <div class="form-group" style="margin-bottom: 24px;">
                                    <label for="arquivo"
                                        style="display: flex; align-items: center; gap: 8px; font-weight: 600; color: #1D1D1F; margin-bottom: 8px; font-size: 14px;">
                                        <span class="material-icons-outlined"
                                            style="font-size: 20px; color: #007AFF;">attach_file</span>
                                        Selecione o arquivo (Excel ou CSV)
                                    </label>
                                    <input type="file" class="form-control-file" id="arquivo" name="arquivo"
                                        accept=".xlsx,.xls,.csv" required
                                        style="border: 2px dashed #E5E5E7; border-radius: 10px; padding: 20px; width: 100%; cursor: pointer; transition: all 0.3s ease;"
                                        onmouseover="this.style.borderColor='#007AFF'; this.style.background='rgba(0, 122, 255, 0.02)'"
                                        onmouseout="this.style.borderColor='#E5E5E7'; this.style.background='white'">
                                    <small class="form-text"
                                        style="color: #86868B; font-size: 12px; margin-top: 6px;">Formatos aceitos:
                                        .xlsx, .xls, .csv</small>
                                </div>

                                <!-- Botão Baixar Planilha -->
                                <div class="form-group" style="margin-bottom: 24px;">
                                    <a href="../app/actions/baixar-planilha-padrao.php"
                                        style="background: white; color: #007AFF; border: 2px solid #007AFF; border-radius: 10px; padding: 10px 20px; font-weight: 600; font-size: 14px; display: inline-flex; align-items: center; gap: 8px; transition: all 0.3s ease; text-decoration: none;"
                                        onmouseover="this.style.background='#007AFF'; this.style.color='white'"
                                        onmouseout="this.style.background='white'; this.style.color='#007AFF'">
                                        <span class="material-icons-outlined" style="font-size: 18px;">download</span>
                                        Baixar Planilha Padrão
                                    </a>
                                </div>

                                <hr style="border: none; border-top: 1px solid #F5F5F7; margin: 24px 0;">

                                <!-- Colunas Obrigatórias e Opcionais -->
                                <div class="row">
                                    <div class="col-md-6">
                                        <div
                                            style="background: linear-gradient(135deg, rgba(255, 149, 0, 0.05) 0%, rgba(255, 149, 0, 0.02) 100%); border-radius: 12px; padding: 16px; border-left: 4px solid #FF9500;">
                                            <div
                                                style="display: flex; align-items: center; gap: 8px; margin-bottom: 12px;">
                                                <span class="material-icons-outlined"
                                                    style="font-size: 20px; color: #FF9500;">priority_high</span>
                                                <strong style="color: #FF9500; font-size: 14px;">Colunas
                                                    Obrigatórias</strong>
                                            </div>
                                            <ul style="margin: 0; padding-left: 20px; color: #1D1D1F; font-size: 13px;">
                                                <li style="margin-bottom: 6px;"><strong>Nome</strong> - Nome completo
                                                </li>
                                                <li><strong>Email</strong> - Email válido</li>
                                            </ul>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div
                                            style="background: linear-gradient(135deg, rgba(0, 122, 255, 0.05) 0%, rgba(0, 122, 255, 0.02) 100%); border-radius: 12px; padding: 16px; border-left: 4px solid #007AFF;">
                                            <div
                                                style="display: flex; align-items: center; gap: 8px; margin-bottom: 12px;">
                                                <span class="material-icons-outlined"
                                                    style="font-size: 20px; color: #007AFF;">info</span>
                                                <strong style="color: #007AFF; font-size: 14px;">Colunas
                                                    Opcionais</strong>
                                            </div>
                                            <ul style="margin: 0; padding-left: 20px; color: #1D1D1F; font-size: 13px;">
                                                <li style="margin-bottom: 6px;">CPF</li>
                                                <li style="margin-bottom: 6px;">Telefone</li>
                                                <li>Data de Nascimento</li>
                                            </ul>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Footer Moderno -->
                            <div class="modal-footer"
                                style="background: #F5F5F7; border-radius: 0 0 16px 16px; padding: 20px 32px; border: none; gap: 12px;">
                                <button type="button" class="btn btn-secondary" data-dismiss="modal"
                                    style="background: white; color: #1D1D1F; border: 2px solid #E5E5E7; border-radius: 10px; padding: 10px 24px; font-weight: 600; font-size: 14px; transition: all 0.3s ease;"
                                    onmouseover="this.style.background='#F5F5F7'"
                                    onmouseout="this.style.background='white'">
                                    <span class="material-icons-outlined"
                                        style="font-size: 18px; vertical-align: middle; margin-right: 6px;">close</span>
                                    Cancelar
                                </button>
                                <button type="submit" class="btn btn-primary"
                                    style="background: linear-gradient(135deg, #007AFF 0%, #5856D6 100%); color: white; border: none; border-radius: 10px; padding: 10px 28px; font-weight: 600; font-size: 14px; box-shadow: 0 4px 12px rgba(0, 122, 255, 0.3); transition: all 0.3s ease;"
                                    onmouseover="this.style.transform='translateY(-2px)'; this.style.boxShadow='0 6px 20px rgba(0, 122, 255, 0.4)'"
                                    onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 4px 12px rgba(0, 122, 255, 0.3)'">
                                    <span class="material-icons-outlined"
                                        style="font-size: 18px; vertical-align: middle; margin-right: 6px;">cloud_upload</span>
                                    Importar
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Modal Novo Aluno - Design Moderno -->
            <div class="modal fade" id="novoAluno" tabindex="-1" role="dialog" aria-hidden="true">
                <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
                    <div class="modal-content"
                        style="border-radius: 16px; border: none; box-shadow: 0 20px 60px rgba(110, 65, 193, 0.3);">
                        <!-- Header Moderno com Gradiente Roxo -->
                        <div class="modal-header"
                            style="background: linear-gradient(135deg, #6E41C1 0%, #8B5FD6 100%); border-radius: 16px 16px 0 0; padding: 24px 32px; border: none;">
                            <div style="display: flex; align-items: center; gap: 12px;">
                                <div
                                    style="width: 48px; height: 48px; background: rgba(255,255,255,0.2); border-radius: 12px; display: flex; align-items: center; justify-content: center;">
                                    <span class="material-icons-outlined"
                                        style="font-size: 28px; color: white;">person_add</span>
                                </div>
                                <div>
                                    <h5 class="modal-title"
                                        style="color: white; font-weight: 600; font-size: 22px; margin: 0;">
                                        Novo Aluno
                                    </h5>
                                    <p style="color: rgba(255,255,255,0.9); font-size: 13px; margin: 4px 0 0 0;">
                                        Cadastre um novo aluno no sistema</p>
                                </div>
                            </div>
                            <button type="button" class="close" data-dismiss="modal" aria-label="Close"
                                style="color: white; opacity: 1; text-shadow: none; font-size: 32px; font-weight: 300; margin: 0; padding: 0;">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>

                        <form method="POST" action="../app/actions/criar-aluno.php" id="formNovoAluno">
                            <div class="modal-body" style="padding: 32px;">
                                <!-- Nome Completo -->
                                <div class="form-group" style="margin-bottom: 20px;">
                                    <label for="nome"
                                        style="display: flex; align-items: center; gap: 8px; font-weight: 600; color: #1D1D1F; margin-bottom: 8px; font-size: 14px;">
                                        <span class="material-icons-outlined"
                                            style="font-size: 20px; color: #6E41C1;">person</span>
                                        Nome Completo <span style="color: #FF3B30;">*</span>
                                    </label>
                                    <input type="text" class="form-control" id="nome" name="nome"
                                        placeholder="Digite o nome completo" required
                                        style="border: 2px solid #E5E5E7; border-radius: 10px; padding: 12px 16px; font-size: 15px; transition: all 0.3s ease;"
                                        onfocus="this.style.borderColor='#6E41C1'; this.style.boxShadow='0 0 0 4px rgba(110, 65, 193, 0.1)'"
                                        onblur="this.style.borderColor='#E5E5E7'; this.style.boxShadow='none'">
                                </div>

                                <!-- Email -->
                                <div class="form-group" style="margin-bottom: 20px;">
                                    <label for="email"
                                        style="display: flex; align-items: center; gap: 8px; font-weight: 600; color: #1D1D1F; margin-bottom: 8px; font-size: 14px;">
                                        <span class="material-icons-outlined"
                                            style="font-size: 20px; color: #6E41C1;">email</span>
                                        Email <span style="color: #FF3B30;">*</span>
                                    </label>
                                    <input type="email" class="form-control" id="email" name="email"
                                        placeholder="exemplo@email.com" required
                                        style="border: 2px solid #E5E5E7; border-radius: 10px; padding: 12px 16px; font-size: 15px; transition: all 0.3s ease;"
                                        onfocus="this.style.borderColor='#6E41C1'; this.style.boxShadow='0 0 0 4px rgba(110, 65, 193, 0.1)'"
                                        onblur="this.style.borderColor='#E5E5E7'; this.style.boxShadow='none'">
                                </div>

                                <!-- CPF e Telefone -->
                                <div class="form-row">
                                    <div class="form-group col-md-6" style="margin-bottom: 20px;">
                                        <label for="cpf"
                                            style="display: flex; align-items: center; gap: 8px; font-weight: 600; color: #1D1D1F; margin-bottom: 8px; font-size: 14px;">
                                            <span class="material-icons-outlined"
                                                style="font-size: 20px; color: #6E41C1;">badge</span>
                                            CPF
                                        </label>
                                        <input type="text" class="form-control" id="cpf" name="cpf"
                                            placeholder="000.000.000-00"
                                            style="border: 2px solid #E5E5E7; border-radius: 10px; padding: 12px 16px; font-size: 15px; transition: all 0.3s ease;"
                                            onfocus="this.style.borderColor='#6E41C1'; this.style.boxShadow='0 0 0 4px rgba(110, 65, 193, 0.1)'"
                                            onblur="this.style.borderColor='#E5E5E7'; this.style.boxShadow='none'">
                                    </div>
                                    <div class="form-group col-md-6" style="margin-bottom: 20px;">
                                        <label for="telefone"
                                            style="display: flex; align-items: center; gap: 8px; font-weight: 600; color: #1D1D1F; margin-bottom: 8px; font-size: 14px;">
                                            <span class="material-icons-outlined"
                                                style="font-size: 20px; color: #6E41C1;">phone</span>
                                            Telefone
                                        </label>
                                        <input type="text" class="form-control" id="telefone" name="telefone"
                                            placeholder="(11) 99999-9999"
                                            style="border: 2px solid #E5E5E7; border-radius: 10px; padding: 12px 16px; font-size: 15px; transition: all 0.3s ease;"
                                            onfocus="this.style.borderColor='#6E41C1'; this.style.boxShadow='0 0 0 4px rgba(110, 65, 193, 0.1)'"
                                            onblur="this.style.borderColor='#E5E5E7'; this.style.boxShadow='none'">
                                    </div>
                                </div>

                                <!-- Data de Nascimento -->
                                <div class="form-group" style="margin-bottom: 20px;">
                                    <label for="data_nascimento"
                                        style="display: flex; align-items: center; gap: 8px; font-weight: 600; color: #1D1D1F; margin-bottom: 8px; font-size: 14px;">
                                        <span class="material-icons-outlined"
                                            style="font-size: 20px; color: #6E41C1;">cake</span>
                                        Data de Nascimento
                                    </label>
                                    <input type="date" class="form-control" id="data_nascimento" name="data_nascimento"
                                        style="border: 2px solid #E5E5E7; border-radius: 10px; padding: 12px 16px; font-size: 15px; transition: all 0.3s ease;"
                                        onfocus="this.style.borderColor='#6E41C1'; this.style.boxShadow='0 0 0 4px rgba(110, 65, 193, 0.1)'"
                                        onblur="this.style.borderColor='#E5E5E7'; this.style.boxShadow='none'">
                                </div>

                                <!-- Endereço -->
                                <div class="form-group" style="margin-bottom: 20px;">
                                    <label for="endereco"
                                        style="display: flex; align-items: center; gap: 8px; font-weight: 600; color: #1D1D1F; margin-bottom: 8px; font-size: 14px;">
                                        <span class="material-icons-outlined"
                                            style="font-size: 20px; color: #6E41C1;">home</span>
                                        Endereço
                                    </label>
                                    <input type="text" class="form-control" id="endereco" name="endereco"
                                        placeholder="Rua, número, complemento"
                                        style="border: 2px solid #E5E5E7; border-radius: 10px; padding: 12px 16px; font-size: 15px; transition: all 0.3s ease;"
                                        onfocus="this.style.borderColor='#6E41C1'; this.style.boxShadow='0 0 0 4px rgba(110, 65, 193, 0.1)'"
                                        onblur="this.style.borderColor='#E5E5E7'; this.style.boxShadow='none'">
                                </div>

                                <!-- Cidade, Estado e CEP -->
                                <div class="form-row">
                                    <div class="form-group col-md-6" style="margin-bottom: 20px;">
                                        <label for="cidade"
                                            style="display: flex; align-items: center; gap: 8px; font-weight: 600; color: #1D1D1F; margin-bottom: 8px; font-size: 14px;">
                                            <span class="material-icons-outlined"
                                                style="font-size: 20px; color: #6E41C1;">location_city</span>
                                            Cidade
                                        </label>
                                        <input type="text" class="form-control" id="cidade" name="cidade"
                                            placeholder="Cidade"
                                            style="border: 2px solid #E5E5E7; border-radius: 10px; padding: 12px 16px; font-size: 15px; transition: all 0.3s ease;"
                                            onfocus="this.style.borderColor='#6E41C1'; this.style.boxShadow='0 0 0 4px rgba(110, 65, 193, 0.1)'"
                                            onblur="this.style.borderColor='#E5E5E7'; this.style.boxShadow='none'">
                                    </div>
                                    <div class="form-group col-md-3" style="margin-bottom: 20px;">
                                        <label for="estado"
                                            style="display: flex; align-items: center; gap: 8px; font-weight: 600; color: #1D1D1F; margin-bottom: 8px; font-size: 14px;">
                                            <span class="material-icons-outlined"
                                                style="font-size: 20px; color: #6E41C1;">map</span>
                                            Estado
                                        </label>
                                        <input type="text" class="form-control" id="estado" name="estado" maxlength="2"
                                            placeholder="SP"
                                            style="border: 2px solid #E5E5E7; border-radius: 10px; padding: 12px 16px; font-size: 15px; transition: all 0.3s ease;"
                                            onfocus="this.style.borderColor='#6E41C1'; this.style.boxShadow='0 0 0 4px rgba(110, 65, 193, 0.1)'"
                                            onblur="this.style.borderColor='#E5E5E7'; this.style.boxShadow='none'">
                                    </div>
                                    <div class="form-group col-md-3" style="margin-bottom: 20px;">
                                        <label for="cep"
                                            style="display: flex; align-items: center; gap: 8px; font-weight: 600; color: #1D1D1F; margin-bottom: 8px; font-size: 14px;">
                                            <span class="material-icons-outlined"
                                                style="font-size: 20px; color: #6E41C1;">pin_drop</span>
                                            CEP
                                        </label>
                                        <input type="text" class="form-control" id="cep" name="cep"
                                            placeholder="00000-000"
                                            style="border: 2px solid #E5E5E7; border-radius: 10px; padding: 12px 16px; font-size: 15px; transition: all 0.3s ease;"
                                            onfocus="this.style.borderColor='#6E41C1'; this.style.boxShadow='0 0 0 4px rgba(110, 65, 193, 0.1)'"
                                            onblur="this.style.borderColor='#E5E5E7'; this.style.boxShadow='none'">
                                    </div>
                                </div>
                                <!-- Vincular a Cursos -->
                                <?php if (!empty($cursos)): ?>
                                    <hr style="border: none; border-top: 1px solid #F5F5F7; margin: 24px 0;">

                                    <div class="form-group" style="margin-bottom: 0;">
                                        <label
                                            style="display: flex; align-items: center; gap: 8px; font-weight: 600; color: #1D1D1F; margin-bottom: 12px; font-size: 14px;">
                                            <span class="material-icons-outlined"
                                                style="font-size: 20px; color: #6E41C1;">school</span>
                                            Vincular a Cursos (Opcional)
                                        </label>
                                        <div
                                            style="border: 2px solid #E5E5E7; border-radius: 10px; padding: 16px; background: #F5F5F7; max-height: 200px; overflow-y: auto;">
                                            <?php foreach ($cursos as $curso): ?>
                                                <div class="custom-control custom-checkbox" style="margin-bottom: 12px;">
                                                    <input type="checkbox" class="custom-control-input"
                                                        id="curso_<?php echo $curso['id']; ?>" name="cursos[]"
                                                        value="<?php echo $curso['id']; ?>" style="cursor: pointer;">
                                                    <label class="custom-control-label" for="curso_<?php echo $curso['id']; ?>"
                                                        style="cursor: pointer; color: #1D1D1F; font-size: 14px; font-weight: 500;">
                                                        <?php echo htmlspecialchars($curso['nome']); ?>
                                                    </label>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                        <small class="form-text"
                                            style="color: #86868B; font-size: 12px; margin-top: 8px;">Selecione os cursos
                                            aos quais este aluno será vinculado</small>
                                    </div>
                                <?php else: ?>
                                    <hr style="border: none; border-top: 1px solid #F5F5F7; margin: 24px 0;">

                                    <div
                                        style="background: linear-gradient(135deg, rgba(0, 122, 255, 0.05) 0%, rgba(0, 122, 255, 0.02) 100%); border-radius: 12px; padding: 16px; border-left: 4px solid #007AFF;">
                                        <div style="display: flex; align-items: start; gap: 12px;">
                                            <span class="material-icons-outlined"
                                                style="font-size: 24px; color: #007AFF;">info</span>
                                            <div>
                                                <strong style="color: #007AFF; font-size: 14px;">Nenhum curso
                                                    disponível.</strong>
                                                <p style="color: #86868B; font-size: 13px; margin: 4px 0 0 0;">
                                                    <a href="cursos-parceiro.php"
                                                        style="color: #007AFF; text-decoration: underline;">Criar um
                                                        curso</a> primeiro.
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                <?php endif; ?>
                            </div>

                            <!-- Footer Moderno -->
                            <div class="modal-footer"
                                style="background: #F5F5F7; border-radius: 0 0 16px 16px; padding: 20px 32px; border: none; gap: 12px;">
                                <button type="button" class="btn btn-secondary" data-dismiss="modal"
                                    style="background: white; color: #1D1D1F; border: 2px solid #E5E5E7; border-radius: 10px; padding: 10px 24px; font-weight: 600; font-size: 14px; transition: all 0.3s ease;"
                                    onmouseover="this.style.background='#F5F5F7'"
                                    onmouseout="this.style.background='white'">
                                    <span class="material-icons-outlined"
                                        style="font-size: 18px; vertical-align: middle; margin-right: 6px;">close</span>
                                    Cancelar
                                </button>
                                <button type="submit" class="btn btn-primary"
                                    style="background: linear-gradient(135deg, #6E41C1 0%, #8B5FD6 100%); color: white; border: none; border-radius: 10px; padding: 10px 28px; font-weight: 600; font-size: 14px; box-shadow: 0 4px 12px rgba(110, 65, 193, 0.3); transition: all 0.3s ease;"
                                    onmouseover="this.style.transform='translateY(-2px)'; this.style.boxShadow='0 6px 20px rgba(110, 65, 193, 0.4)'"
                                    onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 4px 12px rgba(110, 65, 193, 0.3)'">
                                    <span class="material-icons-outlined"
                                        style="font-size: 18px; vertical-align: middle; margin-right: 6px;">save</span>
                                    Registrar Aluno
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- CSS Customizado para Design Moderno -->
            <style>
                /* ===== DATATABLES CUSTOMIZAÇÃO ===== */
                .dataTables_wrapper .dataTables_length select {
                    border: 2px solid #E5E5E7 !important;
                    border-radius: 8px !important;
                    padding: 6px 32px 6px 12px !important;
                    font-size: 13px !important;
                    color: #1D1D1F !important;
                    background-color: white !important;
                    transition: all 0.3s ease !important;
                }

                .dataTables_wrapper .dataTables_length select:focus {
                    border-color: #6E41C1 !important;
                    box-shadow: 0 0 0 4px rgba(110, 65, 193, 0.1) !important;
                    outline: none !important;
                }

                .dataTables_wrapper .dataTables_filter input {
                    border: 2px solid #E5E5E7 !important;
                    border-radius: 10px !important;
                    padding: 10px 16px !important;
                    font-size: 14px !important;
                    color: #1D1D1F !important;
                    transition: all 0.3s ease !important;
                }

                .dataTables_wrapper .dataTables_filter input:focus {
                    border-color: #6E41C1 !important;
                    box-shadow: 0 0 0 4px rgba(110, 65, 193, 0.1) !important;
                    outline: none !important;
                }

                .dataTables_wrapper .dataTables_paginate .paginate_button {
                    border: 2px solid #E5E5E7 !important;
                    border-radius: 8px !important;
                    padding: 6px 14px !important;
                    margin: 0 4px !important;
                    background: white !important;
                    color: #1D1D1F !important;
                    font-weight: 600 !important;
                    font-size: 13px !important;
                    transition: all 0.2s ease !important;
                }

                .dataTables_wrapper .dataTables_paginate .paginate_button:hover {
                    background: #F5F5F7 !important;
                    border-color: #6E41C1 !important;
                    color: #6E41C1 !important;
                }

                .dataTables_wrapper .dataTables_paginate .paginate_button.current {
                    background: linear-gradient(135deg, #6E41C1 0%, #8B5FD6 100%) !important;
                    border-color: #6E41C1 !important;
                    color: white !important;
                    box-shadow: 0 2px 8px rgba(110, 65, 193, 0.3) !important;
                }

                .dataTables_wrapper .dataTables_paginate .paginate_button.disabled {
                    opacity: 0.4 !important;
                    cursor: not-allowed !important;
                }

                .dataTables_wrapper .dataTables_info {
                    color: #86868B !important;
                    font-size: 13px !important;
                    font-weight: 500 !important;
                }

                /* ===== CHECKBOX CUSTOMIZAÇÃO ===== */
                .custom-control-input:checked~.custom-control-label::before {
                    background-color: #6E41C1 !important;
                    border-color: #6E41C1 !important;
                }

                .custom-control-input:focus~.custom-control-label::before {
                    box-shadow: 0 0 0 4px rgba(110, 65, 193, 0.1) !important;
                }

                /* ===== MODAL ANIMAÇÕES ===== */
                .modal.fade .modal-dialog {
                    transition: transform 0.3s ease-out, opacity 0.3s ease-out;
                    transform: scale(0.9) translateY(-20px);
                    opacity: 0;
                }

                .modal.show .modal-dialog {
                    transform: scale(1) translateY(0);
                    opacity: 1;
                }

                /* ===== SCROLLBAR CUSTOMIZADA ===== */
                ::-webkit-scrollbar {
                    width: 8px;
                    height: 8px;
                }

                ::-webkit-scrollbar-track {
                    background: #F5F5F7;
                    border-radius: 10px;
                }

                ::-webkit-scrollbar-thumb {
                    background: linear-gradient(135deg, #6E41C1 0%, #8B5FD6 100%);
                    border-radius: 10px;
                }

                ::-webkit-scrollbar-thumb:hover {
                    background: #6E41C1;
                }

                /* ===== ANIMAÇÕES ===== */
                @keyframes slideInDown {
                    from {
                        transform: translateY(-20px);
                        opacity: 0;
                    }

                    to {
                        transform: translateY(0);
                        opacity: 1;
                    }
                }

                .alert {
                    animation: slideInDown 0.4s ease-out;
                }

                /* ===== RESPONSIVIDADE ===== */
                @media (max-width: 768px) {
                    .modal-body {
                        padding: 20px !important;
                    }

                    .modal-header {
                        padding: 20px !important;
                    }

                    .modal-footer {
                        padding: 16px 20px !important;
                    }
                }
            </style>

            <script>
                // Validação do formulário de novo aluno
                document.getElementById('formNovoAluno').addEventListener('submit', function (e) {
                    const nome = document.getElementById('nome').value.trim();
                    const email = document.getElementById('email').value.trim();

                    if (!nome || nome.length < 3) {
                        e.preventDefault();
                        alert('Por favor, digite um nome válido (mínimo 3 caracteres)');
                        return false;
                    }

                    if (!email || !email.includes('@')) {
                        e.preventDefault();
                        alert('Por favor, digite um email válido');
                        return false;
                    }

                    return true;
                });
            </script>

            </main>
            <footer class="sticky-footer bg-white">
                <div class="container my-auto">
                    <div class="copyright text-center my-auto">
                        <span>Copyright &copy; Sistema de Certificados 2025</span>
                    </div>
                </div>
            </footer>

        </div>
    </div>
</div>


<?php require_once '../app/views/footer.php'; ?>
<?php $conn->close(); ?>