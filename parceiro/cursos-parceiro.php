<?php
/**
 * Cursos Parceiro - Sistema de Certificados
 * Padrão MVP - Camada de Apresentação
 * Layout: SB Admin 2
 */

require_once '../app/config/config.php';

if (!isAuthenticated() || !hasRole(ROLE_PARCEIRO)) {
    redirect(APP_URL . '/login.php');
}

$page_title = 'Cursos - ' . APP_NAME;
$user = getCurrentUser();
$conn = getDBConnection();

// Buscar cursos do parceiro
$cursos = [];
$parceiro_id = $user['parceiro_id'] ?? $user['id'];
$stmt = $conn->prepare("SELECT id, nome, descricao, ativo, criado_em FROM cursos WHERE parceiro_id = ? ORDER BY criado_em DESC");
if ($stmt) {
    $stmt->bind_param("i", $parceiro_id);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $cursos[] = $row;
    }
    $stmt->close();
}

// Calcular estatísticas
$total_cursos = count($cursos);
$cursos_ativos = count(array_filter($cursos, function($c) { return $c['ativo']; }));
$cursos_inativos = $total_cursos - $cursos_ativos;
?>
<?php require_once '../app/views/header.php'; ?>
<?php require_once '../app/views/sidebar-parceiro.php'; ?>

<div id="content-wrapper" class="d-flex flex-column">
    <div id="content">
        <?php require_once '../app/views/topbar.php'; ?>

        <style>
@import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap');@import url('https://fonts.googleapis.com/icon?family=Material+Icons+Outlined');:root{--primary-color:#6E41C1;--primary-hover:#56349A;--sidebar-bg:#F5F5F7;--sidebar-text:#1D1D1F;--content-bg:#FFFFFF;--card-bg:#FFFFFF;--text-dark:#1D1D1F;--text-medium:#6B6B6B;--text-light:#ADADAD;--border-light:#E0E0E0;--border-medium:#D0D0D0;--shadow-subtle:0 2px 8px rgba(0,0,0,0.05);--border-radius-card:14px;--border-radius-button:10px;--status-green:#34C759}
#accordionSidebar,.topbar,.scroll-to-top{display:none!important}#content{padding:0!important}.container-fluid{padding:0!important}body,.content-area{font-family:'Inter',sans-serif;color:var(--text-dark);background:var(--content-bg)}.icon{font-family:'Material Icons Outlined';font-size:20px;vertical-align:middle;margin-right:10px;color:var(--text-medium)}
.erp-container{display:flex;height:100vh}.sidebar{width:250px;background:var(--sidebar-bg);color:var(--sidebar-text);padding:24px 20px;display:flex;flex-direction:column;border-right:1px solid var(--border-light);flex-shrink:0}.sidebar-header{font-size:1.5rem;font-weight:700;text-align:center;padding-bottom:20px;margin-bottom:20px;color:var(--text-dark)}.nav-section-title{font-size:.7rem;color:var(--text-light);margin:24px 0 12px 15px;letter-spacing:.7px;font-weight:500}.sidebar-nav{overflow-y:auto}.sidebar-nav ul{list-style:none;margin:0;padding:0}.sidebar-nav li{margin-bottom:6px}.sidebar-nav a{display:flex;align-items:center;padding:11px 15px;border-radius:8px;color:var(--sidebar-text);transition:all .2s;font-weight:500}.sidebar-nav a:hover{background:var(--border-light)}.sidebar-nav a.active{background:var(--primary-color);color:var(--card-bg);font-weight:600}.sidebar-nav a.active .icon{color:var(--card-bg)}.sidebar-footer{margin-top:auto;padding-top:15px;border-top:1px solid var(--border-light)}.sidebar-footer ul{list-style:none;margin:0;padding:0}.sidebar-footer li{margin-bottom:5px}.sidebar-footer a{display:flex;align-items:center;padding:10px 15px;border-radius:8px;color:var(--text-medium);font-weight:500;transition:all .2s}.sidebar-footer a:hover{background:var(--border-light);color:var(--text-dark)}
.main-wrapper{flex:1;display:flex;flex-direction:column;overflow:hidden}.top-header{height:60px;background:var(--content-bg);border-bottom:1px solid var(--border-light);display:flex;justify-content:flex-end;align-items:center;padding:0 24px;position:sticky;top:0;z-index:10;flex-shrink:0}.notifications{margin-right:20px;cursor:pointer;color:var(--text-medium)}.user-profile{display:flex;align-items:center;cursor:pointer;padding:5px 10px;border-radius:8px;transition:background-color .2s}.user-profile:hover{background:var(--border-light)}.user-profile .icon{font-size:24px;margin-right:8px;color:var(--text-medium)}.content-area{flex:1;padding:24px;overflow-y:auto}.content-area h1{font-size:2rem;margin-bottom:20px;font-weight:700}
.card{background:var(--card-bg);border-radius:var(--border-radius-card);padding:24px;box-shadow:var(--shadow-subtle);border:1px solid var(--border-light)}.button{display:inline-flex;align-items:center;justify-content:center;padding:10px 18px;border-radius:var(--border-radius-button);font-weight:500;text-align:center;cursor:pointer;transition:all .2s;border:none;font-size:.9rem;min-width:110px}.button-primary{background:var(--primary-color);color:#fff}.button-primary:hover{background:var(--primary-hover);transform:translateY(-1px)}.button-secondary{background:#F0F0F0;color:var(--text-dark);border:1px solid var(--border-medium)}.button-secondary:hover{background:#E6E6E6;transform:translateY(-1px)}.button .icon{font-size:16px;margin-left:8px;color:inherit}
a{text-decoration:none;color:var(--primary-color);transition:color .2s ease}a:hover{color:var(--primary-hover);text-decoration:none!important}h1,h2,h3,h4{font-weight:600;color:var(--text-dark)}html,body{height:100%;overflow:hidden}.icon{font-weight:400;font-style:normal;line-height:1;letter-spacing:normal;text-transform:none;display:inline-block;white-space:nowrap;word-wrap:normal;direction:ltr;-webkit-font-smoothing:antialiased}.sidebar-nav a{cursor:pointer;text-decoration:none!important;border-bottom:none!important}.sidebar-nav a:hover,.sidebar-nav a:focus{background:var(--border-light)!important;color:var(--sidebar-text)!important;text-decoration:none!important;outline:none}
        </style>

        <div class="container-fluid" style="padding:0;">
          <div class="erp-container">
            <aside class="sidebar">
              <div class="sidebar-header">FaCiencia</div>
              <nav class="sidebar-nav">
                <span class="nav-section-title">Navegação</span>
                <ul>
                  <li><a href="<?php echo APP_URL; ?>/parceiro/dashboard-parceiro.php"><span class="icon">dashboard</span> Dashboard</a></li>
                </ul>
                <span class="nav-section-title">Acadêmico</span>
                <ul>
                  <li><a href="<?php echo APP_URL; ?>/parceiro/cursos-parceiro.php" class="active"><span class="icon">school</span> Cursos</a></li>
                  <li><a href="<?php echo APP_URL; ?>/parceiro/alunos-parceiro.php"><span class="icon">group</span> Alunos</a></li>
                </ul>
                <span class="nav-section-title">Certificação</span>
                <ul>
                  <li><a href="<?php echo APP_URL; ?>/parceiro/templates-parceiro.php" title="Templates de Certificados"><span class="icon">article</span> Templates</a></li>
                  <li><a href="<?php echo APP_URL; ?>/parceiro/gerar-certificados.php"><span class="icon">workspace_premium</span> Emitir Cert.</a></li>
                </ul>
                <span class="nav-section-title">Minha Conta</span>
                <ul>
                  <li><a href="<?php echo APP_URL; ?>/parceiro/financeiro.php"><span class="icon">credit_card</span> Financeiro</a></li>
                  <li><a href="<?php echo APP_URL; ?>/parceiro/meu-plano.php"><span class="icon">price_check</span> Meu Plano</a></li>
                </ul>
              </nav>
              <div class="sidebar-footer">
                <ul>
                  <li><a href="<?php echo APP_URL; ?>/parceiro/perfil-parceiro.php"><span class="icon">person</span> Meu Perfil</a></li>
                  <li><a href="<?php echo APP_URL; ?>/app/actions/logout.php"><span class="icon">logout</span> Sair</a></li>
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

        <!-- Begin Page Content -->
        <div class="container-fluid">

            <!-- Page Heading - Design Moderno -->
            <div style="background: linear-gradient(135deg, rgba(110, 65, 193, 0.03) 0%, rgba(110, 65, 193, 0.01) 100%); border-radius: 16px; padding: 28px 32px; margin-bottom: 32px; border: 1px solid rgba(110, 65, 193, 0.08);">
                <div class="d-flex align-items-center justify-content-between flex-wrap" style="gap: 20px;">
                    <!-- Título e Descrição -->
                    <div style="flex: 1; min-width: 250px;">
                        <div style="display: flex; align-items: center; gap: 16px; margin-bottom: 8px;">
                            <!-- Ícone Grande -->
                            <div style="width: 56px; height: 56px; background: linear-gradient(135deg, #6E41C1 0%, #8B5FD6 100%); border-radius: 14px; display: flex; align-items: center; justify-content: center; box-shadow: 0 4px 12px rgba(110, 65, 193, 0.25);">
                                <span class="material-icons-outlined" style="font-size: 32px; color: white;">school</span>
                            </div>
                            <!-- Título -->
                            <div>
                                <h1 style="margin: 0; font-size: 28px; font-weight: 700; color: #1D1D1F; letter-spacing: -0.5px;">
                                    Meus Cursos
                                </h1>
                                <p style="margin: 4px 0 0 0; font-size: 14px; color: #86868B; font-weight: 500;">
                                    Gerencie todos os seus cursos e conteúdos
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Botão Novo Curso -->
                    <div>
                        <button type="button" class="btn btn-primary" data-toggle="modal" data-target="#novoCurso"
                                style="background: linear-gradient(135deg, #6E41C1 0%, #8B5FD6 100%); border: none; border-radius: 12px; padding: 14px 28px; font-weight: 600; font-size: 15px; box-shadow: 0 4px 16px rgba(110, 65, 193, 0.3); transition: all 0.3s ease; display: inline-flex; align-items: center; gap: 10px; white-space: nowrap;"
                                onmouseover="this.style.transform='translateY(-3px)'; this.style.boxShadow='0 8px 24px rgba(110, 65, 193, 0.4)'"
                                onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 4px 16px rgba(110, 65, 193, 0.3)'">
                            <span class="material-icons-outlined" style="font-size: 22px;">add_circle</span>
                            <span>Novo Curso</span>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Alertas de Sucesso/Erro - Design Moderno -->
            <?php if (isset($_SESSION['success'])): ?>
                <div class="alert alert-dismissible fade show" role="alert"
                     style="background: linear-gradient(135deg, rgba(52, 199, 89, 0.1) 0%, rgba(52, 199, 89, 0.05) 100%); border: 2px solid rgba(52, 199, 89, 0.3); border-radius: 12px; padding: 16px 20px; margin-bottom: 24px; display: flex; align-items: center; gap: 12px;">
                    <div style="width: 40px; height: 40px; background: #34C759; border-radius: 10px; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                        <span class="material-icons-outlined" style="font-size: 24px; color: white;">check_circle</span>
                    </div>
                    <div style="flex: 1;">
                        <strong style="color: #34C759; font-weight: 700; font-size: 14px;">Sucesso!</strong>
                        <span style="color: #1D1D1F; font-size: 14px; margin-left: 8px;"><?php echo $_SESSION['success']; ?></span>
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
                    <div style="width: 40px; height: 40px; background: #FF3B30; border-radius: 10px; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                        <span class="material-icons-outlined" style="font-size: 24px; color: white;">error</span>
                    </div>
                    <div style="flex: 1;">
                        <strong style="color: #FF3B30; font-weight: 700; font-size: 14px;">Erro!</strong>
                        <span style="color: #1D1D1F; font-size: 14px; margin-left: 8px;"><?php echo $_SESSION['error']; ?></span>
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

            <!-- Estatísticas de Cursos - Design Moderno -->
            <div class="row mb-4">
                <!-- Total de Cursos -->
                <div class="col-xl-3 col-md-6 mb-4">
                    <div class="card h-100" style="border: none; border-radius: 14px; background: white; box-shadow: 0 2px 8px rgba(0,0,0,0.08); border-left: 4px solid #6E41C1; transition: all 0.3s ease;"
                         onmouseover="this.style.transform='translateY(-4px)'; this.style.boxShadow='0 8px 20px rgba(110, 65, 193, 0.15)'"
                         onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 2px 8px rgba(0,0,0,0.08)'">
                        <div class="card-body" style="padding: 20px;">
                            <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 12px;">
                                <div style="width: 48px; height: 48px; background: linear-gradient(135deg, rgba(110, 65, 193, 0.1) 0%, rgba(110, 65, 193, 0.05) 100%); border-radius: 12px; display: flex; align-items: center; justify-content: center;">
                                    <span class="material-icons-outlined" style="font-size: 24px; color: #6E41C1;">school</span>
                                </div>
                                <div>
                                    <div style="font-size: 11px; font-weight: 600; color: #6E41C1; text-transform: uppercase; letter-spacing: 0.5px;">Total de Cursos</div>
                                    <div style="font-size: 28px; font-weight: 700; color: #1D1D1F; line-height: 1;"><?php echo $total_cursos; ?></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Cursos Ativos -->
                <div class="col-xl-3 col-md-6 mb-4">
                    <div class="card h-100" style="border: none; border-radius: 14px; background: white; box-shadow: 0 2px 8px rgba(0,0,0,0.08); border-left: 4px solid #34C759; transition: all 0.3s ease;"
                         onmouseover="this.style.transform='translateY(-4px)'; this.style.boxShadow='0 8px 20px rgba(52, 199, 89, 0.15)'"
                         onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 2px 8px rgba(0,0,0,0.08)'">
                        <div class="card-body" style="padding: 20px;">
                            <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 12px;">
                                <div style="width: 48px; height: 48px; background: linear-gradient(135deg, rgba(52, 199, 89, 0.1) 0%, rgba(52, 199, 89, 0.05) 100%); border-radius: 12px; display: flex; align-items: center; justify-content: center;">
                                    <span class="material-icons-outlined" style="font-size: 24px; color: #34C759;">check_circle</span>
                                </div>
                                <div>
                                    <div style="font-size: 11px; font-weight: 600; color: #34C759; text-transform: uppercase; letter-spacing: 0.5px;">Cursos Ativos</div>
                                    <div style="font-size: 28px; font-weight: 700; color: #1D1D1F; line-height: 1;"><?php echo $cursos_ativos; ?></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Cursos Inativos -->
                <div class="col-xl-3 col-md-6 mb-4">
                    <div class="card h-100" style="border: none; border-radius: 14px; background: white; box-shadow: 0 2px 8px rgba(0,0,0,0.08); border-left: 4px solid #FF9500; transition: all 0.3s ease;"
                         onmouseover="this.style.transform='translateY(-4px)'; this.style.boxShadow='0 8px 20px rgba(255, 149, 0, 0.15)'"
                         onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 2px 8px rgba(0,0,0,0.08)'">
                        <div class="card-body" style="padding: 20px;">
                            <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 12px;">
                                <div style="width: 48px; height: 48px; background: linear-gradient(135deg, rgba(255, 149, 0, 0.1) 0%, rgba(255, 149, 0, 0.05) 100%); border-radius: 12px; display: flex; align-items: center; justify-content: center;">
                                    <span class="material-icons-outlined" style="font-size: 24px; color: #FF9500;">cancel</span>
                                </div>
                                <div>
                                    <div style="font-size: 11px; font-weight: 600; color: #FF9500; text-transform: uppercase; letter-spacing: 0.5px;">Cursos Inativos</div>
                                    <div style="font-size: 28px; font-weight: 700; color: #1D1D1F; line-height: 1;"><?php echo $cursos_inativos; ?></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Taxa de Ativação -->
                <div class="col-xl-3 col-md-6 mb-4">
                    <div class="card h-100" style="border: none; border-radius: 14px; background: white; box-shadow: 0 2px 8px rgba(0,0,0,0.08); border-left: 4px solid #007AFF; transition: all 0.3s ease;"
                         onmouseover="this.style.transform='translateY(-4px)'; this.style.boxShadow='0 8px 20px rgba(0, 122, 255, 0.15)'"
                         onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 2px 8px rgba(0,0,0,0.08)'">
                        <div class="card-body" style="padding: 20px;">
                            <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 12px;">
                                <div style="width: 48px; height: 48px; background: linear-gradient(135deg, rgba(0, 122, 255, 0.1) 0%, rgba(0, 122, 255, 0.05) 100%); border-radius: 12px; display: flex; align-items: center; justify-content: center;">
                                    <span class="material-icons-outlined" style="font-size: 24px; color: #007AFF;">trending_up</span>
                                </div>
                                <div>
                                    <div style="font-size: 11px; font-weight: 600; color: #007AFF; text-transform: uppercase; letter-spacing: 0.5px;">Taxa de Ativação</div>
                                    <div style="font-size: 28px; font-weight: 700; color: #1D1D1F; line-height: 1;">
                                        <?php echo $total_cursos > 0 ? round(($cursos_ativos / $total_cursos) * 100) : 0; ?>%
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tabela de Cursos - Design Moderno -->
            <div class="card" style="border: none; border-radius: 14px; background: white; box-shadow: 0 2px 8px rgba(0,0,0,0.08);">
                <div class="card-header" style="background: linear-gradient(135deg, rgba(110, 65, 193, 0.05) 0%, rgba(110, 65, 193, 0.02) 100%); border-radius: 14px 14px 0 0; padding: 20px 24px; border: none; border-bottom: 1px solid #F5F5F7;">
                    <div style="display: flex; justify-content: space-between; align-items: center;">
                        <div style="display: flex; align-items: center; gap: 12px;">
                            <span class="material-icons-outlined" style="font-size: 24px; color: #6E41C1;">list_alt</span>
                            <h6 style="margin: 0; font-weight: 700; color: #1D1D1F; font-size: 16px;">Lista de Cursos</h6>
                        </div>
                        <span style="background: #6E41C1; color: white; padding: 4px 12px; border-radius: 20px; font-size: 12px; font-weight: 600;">
                            <?php echo $total_cursos; ?> curso(s)
                        </span>
                    </div>
                </div>
                <div class="card-body" style="padding: 0;">
                    <?php if (empty($cursos)): ?>
                        <div style="text-align: center; padding: 60px 20px;">
                            <div style="width: 80px; height: 80px; background: linear-gradient(135deg, rgba(110, 65, 193, 0.1) 0%, rgba(110, 65, 193, 0.05) 100%); border-radius: 20px; display: inline-flex; align-items: center; justify-content: center; margin-bottom: 20px;">
                                <span class="material-icons-outlined" style="font-size: 48px; color: #6E41C1;">inbox</span>
                            </div>
                            <p style="color: #86868B; font-size: 16px; font-weight: 600; margin-bottom: 8px;">Nenhum curso encontrado</p>
                            <small style="color: #C7C7CC; font-size: 14px;">Clique em "Novo Curso" para criar seu primeiro curso</small>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover" id="dataTable" width="100%" cellspacing="0" style="margin-bottom: 0;">
                                <thead>
                                    <tr style="background: #F5F5F7; border-bottom: 2px solid #E5E5E7;">
                                        <th style="padding: 16px 20px; font-size: 12px; font-weight: 700; color: #6E41C1; text-transform: uppercase; letter-spacing: 0.5px; border: none;">
                                            <span class="material-icons-outlined" style="font-size: 16px; vertical-align: middle; margin-right: 6px;">book</span>
                                            Nome
                                        </th>
                                        <th style="padding: 16px 20px; font-size: 12px; font-weight: 700; color: #6E41C1; text-transform: uppercase; letter-spacing: 0.5px; border: none;">
                                            <span class="material-icons-outlined" style="font-size: 16px; vertical-align: middle; margin-right: 6px;">description</span>
                                            Descrição
                                        </th>
                                        <th style="padding: 16px 20px; font-size: 12px; font-weight: 700; color: #6E41C1; text-transform: uppercase; letter-spacing: 0.5px; border: none;">
                                            <span class="material-icons-outlined" style="font-size: 16px; vertical-align: middle; margin-right: 6px;">toggle_on</span>
                                            Status
                                        </th>
                                        <th style="padding: 16px 20px; font-size: 12px; font-weight: 700; color: #6E41C1; text-transform: uppercase; letter-spacing: 0.5px; border: none;">
                                            <span class="material-icons-outlined" style="font-size: 16px; vertical-align: middle; margin-right: 6px;">calendar_today</span>
                                            Data de Criação
                                        </th>
                                        <th class="text-center" style="padding: 16px 20px; font-size: 12px; font-weight: 700; color: #6E41C1; text-transform: uppercase; letter-spacing: 0.5px; border: none;">
                                            <span class="material-icons-outlined" style="font-size: 16px; vertical-align: middle; margin-right: 6px;">settings</span>
                                            Ações
                                        </th>
                                    </tr>
                                </thead>
                                <tfoot>
                                    <tr style="background: #F5F5F7; border-top: 2px solid #E5E5E7;">
                                        <th style="padding: 16px 20px; font-size: 12px; font-weight: 600; color: #86868B; border: none;">Nome</th>
                                        <th style="padding: 16px 20px; font-size: 12px; font-weight: 600; color: #86868B; border: none;">Descrição</th>
                                        <th style="padding: 16px 20px; font-size: 12px; font-weight: 600; color: #86868B; border: none;">Status</th>
                                        <th style="padding: 16px 20px; font-size: 12px; font-weight: 600; color: #86868B; border: none;">Data de Criação</th>
                                        <th class="text-center" style="padding: 16px 20px; font-size: 12px; font-weight: 600; color: #86868B; border: none;">Ações</th>
                                    </tr>
                                </tfoot>
                                <tbody>
                                    <?php foreach ($cursos as $curso): ?>
                                        <tr style="border-bottom: 1px solid #F5F5F7; transition: all 0.2s ease;"
                                            onmouseover="this.style.background='rgba(110, 65, 193, 0.02)'"
                                            onmouseout="this.style.background='white'">
                                            <td style="padding: 16px 20px; border: none;">
                                                <div style="display: flex; align-items: center; gap: 10px;">
                                                    <div style="width: 36px; height: 36px; background: linear-gradient(135deg, rgba(110, 65, 193, 0.1) 0%, rgba(110, 65, 193, 0.05) 100%); border-radius: 8px; display: flex; align-items: center; justify-content: center;">
                                                        <span class="material-icons-outlined" style="font-size: 18px; color: #6E41C1;">school</span>
                                                    </div>
                                                    <strong style="color: #1D1D1F; font-size: 14px; font-weight: 600;"><?php echo htmlspecialchars($curso['nome']); ?></strong>
                                                </div>
                                            </td>
                                            <td style="padding: 16px 20px; border: none;">
                                                <span style="color: #86868B; font-size: 13px;">
                                                    <?php
                                                    $desc = htmlspecialchars($curso['descricao']);
                                                    echo strlen($desc) > 60 ? substr($desc, 0, 60) . '...' : $desc;
                                                    ?>
                                                </span>
                                            </td>
                                            <td style="padding: 16px 20px; border: none;">
                                                <?php if ($curso['ativo']): ?>
                                                    <span style="background: linear-gradient(135deg, rgba(52, 199, 89, 0.1) 0%, rgba(52, 199, 89, 0.05) 100%); color: #34C759; padding: 6px 12px; border-radius: 20px; font-size: 12px; font-weight: 600; display: inline-flex; align-items: center; gap: 4px;">
                                                        <span class="material-icons-outlined" style="font-size: 14px;">check_circle</span>
                                                        Ativo
                                                    </span>
                                                <?php else: ?>
                                                    <span style="background: linear-gradient(135deg, rgba(134, 134, 139, 0.1) 0%, rgba(134, 134, 139, 0.05) 100%); color: #86868B; padding: 6px 12px; border-radius: 20px; font-size: 12px; font-weight: 600; display: inline-flex; align-items: center; gap: 4px;">
                                                        <span class="material-icons-outlined" style="font-size: 14px;">cancel</span>
                                                        Inativo
                                                    </span>
                                                <?php endif; ?>
                                            </td>
                                            <td style="padding: 16px 20px; border: none;">
                                                <span style="color: #86868B; font-size: 13px; display: inline-flex; align-items: center; gap: 6px;">
                                                    <span class="material-icons-outlined" style="font-size: 16px;">event</span>
                                                    <?php echo date('d/m/Y', strtotime($curso['criado_em'])); ?>
                                                </span>
                                            </td>
                                            <td class="text-center" style="padding: 16px 20px; border: none;">
                                                <div style="display: inline-flex; gap: 8px;">
                                                    <button type="button" data-toggle="modal" data-target="#editarCurso"
                                                        data-curso-id="<?php echo $curso['id']; ?>"
                                                        data-curso-nome="<?php echo htmlspecialchars($curso['nome']); ?>"
                                                        data-curso-descricao="<?php echo htmlspecialchars($curso['descricao']); ?>"
                                                        data-curso-ativo="<?php echo $curso['ativo']; ?>"
                                                        title="Editar curso"
                                                        style="background: linear-gradient(135deg, rgba(0, 122, 255, 0.1) 0%, rgba(0, 122, 255, 0.05) 100%); color: #007AFF; border: none; border-radius: 8px; padding: 8px 12px; cursor: pointer; transition: all 0.2s ease; display: inline-flex; align-items: center; gap: 4px;"
                                                        onmouseover="this.style.background='#007AFF'; this.style.color='white'"
                                                        onmouseout="this.style.background='linear-gradient(135deg, rgba(0, 122, 255, 0.1) 0%, rgba(0, 122, 255, 0.05) 100%)'; this.style.color='#007AFF'">
                                                        <span class="material-icons-outlined" style="font-size: 16px;">edit</span>
                                                    </button>
                                                    <form method="POST" action="../app/actions/excluir-curso.php" style="display:inline; margin: 0;" onsubmit="return confirm('Tem certeza que deseja excluir este curso?');">
                                                        <input type="hidden" name="curso_id" value="<?php echo $curso['id']; ?>">
                                                        <button type="submit" title="Excluir curso"
                                                                style="background: linear-gradient(135deg, rgba(255, 59, 48, 0.1) 0%, rgba(255, 59, 48, 0.05) 100%); color: #FF3B30; border: none; border-radius: 8px; padding: 8px 12px; cursor: pointer; transition: all 0.2s ease; display: inline-flex; align-items: center; gap: 4px;"
                                                                onmouseover="this.style.background='#FF3B30'; this.style.color='white'"
                                                                onmouseout="this.style.background='linear-gradient(135deg, rgba(255, 59, 48, 0.1) 0%, rgba(255, 59, 48, 0.05) 100%)'; this.style.color='#FF3B30'">
                                                            <span class="material-icons-outlined" style="font-size: 16px;">delete</span>
                                                        </button>
                                                    </form>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

        </div>
        <!-- /.container-fluid -->

    </div>
    <!-- End of Main Content -->



</div>

<!-- Modal Novo Curso - Design Moderno -->
<div class="modal fade" id="novoCurso" tabindex="-1" role="dialog" aria-labelledby="novoCursoLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
        <div class="modal-content" style="border-radius: 16px; border: none; box-shadow: 0 20px 60px rgba(110, 65, 193, 0.3);">
            <!-- Header Moderno com Gradiente -->
            <div class="modal-header" style="background: linear-gradient(135deg, #6E41C1 0%, #8B5FD6 100%); border-radius: 16px 16px 0 0; padding: 24px 32px; border: none;">
                <div style="display: flex; align-items: center; gap: 12px;">
                    <div style="width: 48px; height: 48px; background: rgba(255,255,255,0.2); border-radius: 12px; display: flex; align-items: center; justify-content: center;">
                        <span class="material-icons-outlined" style="font-size: 28px; color: white;">school</span>
                    </div>
                    <div>
                        <h5 class="modal-title" id="novoCursoLabel" style="color: white; font-weight: 600; font-size: 22px; margin: 0;">
                            Criar Novo Curso
                        </h5>
                        <p style="color: rgba(255,255,255,0.9); font-size: 13px; margin: 4px 0 0 0;">Preencha as informações do curso</p>
                    </div>
                </div>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close" style="color: white; opacity: 1; text-shadow: none; font-size: 32px; font-weight: 300; margin: 0; padding: 0;">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>

            <form method="POST" action="../app/actions/criar-curso.php" id="formNovoCurso">
                <div class="modal-body" style="padding: 32px;">
                    <!-- Nome do Curso -->
                    <div class="form-group" style="margin-bottom: 24px;">
                        <label for="nome" style="display: flex; align-items: center; gap: 8px; font-weight: 600; color: #1D1D1F; margin-bottom: 8px; font-size: 14px;">
                            <span class="material-icons-outlined" style="font-size: 20px; color: #6E41C1;">book</span>
                            Nome do Curso
                        </label>
                        <input type="text" class="form-control" id="nome" name="nome"
                               placeholder="Ex: Introdução ao PHP" required
                               style="border: 2px solid #E5E5E7; border-radius: 10px; padding: 12px 16px; font-size: 15px; transition: all 0.3s ease;"
                               onfocus="this.style.borderColor='#6E41C1'; this.style.boxShadow='0 0 0 4px rgba(110, 65, 193, 0.1)'"
                               onblur="this.style.borderColor='#E5E5E7'; this.style.boxShadow='none'">
                        <small class="form-text" style="color: #86868B; font-size: 12px; margin-top: 6px;">Máximo 100 caracteres</small>
                    </div>

                    <!-- Descrição -->
                    <div class="form-group" style="margin-bottom: 24px;">
                        <label for="descricao" style="display: flex; align-items: center; gap: 8px; font-weight: 600; color: #1D1D1F; margin-bottom: 8px; font-size: 14px;">
                            <span class="material-icons-outlined" style="font-size: 20px; color: #6E41C1;">description</span>
                            Descrição
                        </label>
                        <textarea class="form-control" id="descricao" name="descricao" rows="4"
                                  placeholder="Descreva o conteúdo e objetivos do curso..." required
                                  style="border: 2px solid #E5E5E7; border-radius: 10px; padding: 12px 16px; font-size: 15px; transition: all 0.3s ease; resize: vertical;"
                                  onfocus="this.style.borderColor='#6E41C1'; this.style.boxShadow='0 0 0 4px rgba(110, 65, 193, 0.1)'"
                                  onblur="this.style.borderColor='#E5E5E7'; this.style.boxShadow='none'"></textarea>
                        <small class="form-text" style="color: #86868B; font-size: 12px; margin-top: 6px;">Máximo 500 caracteres</small>
                    </div>

                    <!-- Carga Horária -->
                    <div class="form-group" style="margin-bottom: 24px;">
                        <label for="carga_horaria" style="display: flex; align-items: center; gap: 8px; font-weight: 600; color: #1D1D1F; margin-bottom: 8px; font-size: 14px;">
                            <span class="material-icons-outlined" style="font-size: 20px; color: #6E41C1;">schedule</span>
                            Carga Horária (horas)
                        </label>
                        <input type="number" class="form-control" id="carga_horaria" name="carga_horaria"
                               min="0" placeholder="Ex: 40" value="0"
                               style="border: 2px solid #E5E5E7; border-radius: 10px; padding: 12px 16px; font-size: 15px; transition: all 0.3s ease;"
                               onfocus="this.style.borderColor='#6E41C1'; this.style.boxShadow='0 0 0 4px rgba(110, 65, 193, 0.1)'"
                               onblur="this.style.borderColor='#E5E5E7'; this.style.boxShadow='none'">
                    </div>

                    <!-- Switch Ativar Curso -->
                    <div style="background: linear-gradient(135deg, rgba(110, 65, 193, 0.05) 0%, rgba(139, 95, 214, 0.05) 100%); border-radius: 12px; padding: 16px; display: flex; align-items: center; gap: 12px;">
                        <div class="custom-control custom-switch" style="padding-left: 2.5rem;">
                            <input type="checkbox" class="custom-control-input" id="ativo" name="ativo" checked>
                            <label class="custom-control-label" for="ativo" style="font-weight: 600; color: #1D1D1F; cursor: pointer; display: flex; align-items: center; gap: 8px;">
                                <span class="material-icons-outlined" style="font-size: 20px; color: #34C759;">check_circle</span>
                                Ativar curso imediatamente
                            </label>
                        </div>
                    </div>
                </div>

                <!-- Footer Moderno -->
                <div class="modal-footer" style="background: #F5F5F7; border-radius: 0 0 16px 16px; padding: 20px 32px; border: none; gap: 12px;">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal"
                            style="background: white; color: #1D1D1F; border: 2px solid #E5E5E7; border-radius: 10px; padding: 10px 24px; font-weight: 600; font-size: 14px; transition: all 0.3s ease;"
                            onmouseover="this.style.background='#F5F5F7'"
                            onmouseout="this.style.background='white'">
                        <span class="material-icons-outlined" style="font-size: 18px; vertical-align: middle; margin-right: 6px;">close</span>
                        Cancelar
                    </button>
                    <button type="submit" class="btn btn-primary"
                            style="background: linear-gradient(135deg, #6E41C1 0%, #8B5FD6 100%); color: white; border: none; border-radius: 10px; padding: 10px 28px; font-weight: 600; font-size: 14px; box-shadow: 0 4px 12px rgba(110, 65, 193, 0.3); transition: all 0.3s ease;"
                            onmouseover="this.style.transform='translateY(-2px)'; this.style.boxShadow='0 6px 20px rgba(110, 65, 193, 0.4)'"
                            onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 4px 12px rgba(110, 65, 193, 0.3)'">
                        <span class="material-icons-outlined" style="font-size: 18px; vertical-align: middle; margin-right: 6px;">save</span>
                        Criar Curso
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Editar Curso - Design Moderno -->
<div class="modal fade" id="editarCurso" tabindex="-1" role="dialog" aria-labelledby="editarCursoLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
        <div class="modal-content" style="border-radius: 16px; border: none; box-shadow: 0 20px 60px rgba(0, 122, 255, 0.3);">
            <!-- Header Moderno com Gradiente Azul -->
            <div class="modal-header" style="background: linear-gradient(135deg, #007AFF 0%, #5856D6 100%); border-radius: 16px 16px 0 0; padding: 24px 32px; border: none;">
                <div style="display: flex; align-items: center; gap: 12px;">
                    <div style="width: 48px; height: 48px; background: rgba(255,255,255,0.2); border-radius: 12px; display: flex; align-items: center; justify-content: center;">
                        <span class="material-icons-outlined" style="font-size: 28px; color: white;">edit</span>
                    </div>
                    <div>
                        <h5 class="modal-title" id="editarCursoLabel" style="color: white; font-weight: 600; font-size: 22px; margin: 0;">
                            Editar Curso
                        </h5>
                        <p style="color: rgba(255,255,255,0.9); font-size: 13px; margin: 4px 0 0 0;">Atualize as informações do curso</p>
                    </div>
                </div>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close" style="color: white; opacity: 1; text-shadow: none; font-size: 32px; font-weight: 300; margin: 0; padding: 0;">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>

            <form method="POST" action="../app/actions/editar-curso.php" id="formEditarCurso">
                <div class="modal-body" style="padding: 32px;">
                    <input type="hidden" id="edit_curso_id" name="curso_id">

                    <!-- Nome do Curso -->
                    <div class="form-group" style="margin-bottom: 24px;">
                        <label for="edit_nome" style="display: flex; align-items: center; gap: 8px; font-weight: 600; color: #1D1D1F; margin-bottom: 8px; font-size: 14px;">
                            <span class="material-icons-outlined" style="font-size: 20px; color: #007AFF;">book</span>
                            Nome do Curso
                        </label>
                        <input type="text" class="form-control" id="edit_nome" name="nome" required
                               style="border: 2px solid #E5E5E7; border-radius: 10px; padding: 12px 16px; font-size: 15px; transition: all 0.3s ease;"
                               onfocus="this.style.borderColor='#007AFF'; this.style.boxShadow='0 0 0 4px rgba(0, 122, 255, 0.1)'"
                               onblur="this.style.borderColor='#E5E5E7'; this.style.boxShadow='none'">
                        <small class="form-text" style="color: #86868B; font-size: 12px; margin-top: 6px;">Máximo 100 caracteres</small>
                    </div>

                    <!-- Descrição -->
                    <div class="form-group" style="margin-bottom: 24px;">
                        <label for="edit_descricao" style="display: flex; align-items: center; gap: 8px; font-weight: 600; color: #1D1D1F; margin-bottom: 8px; font-size: 14px;">
                            <span class="material-icons-outlined" style="font-size: 20px; color: #007AFF;">description</span>
                            Descrição
                        </label>
                        <textarea class="form-control" id="edit_descricao" name="descricao" rows="4" required
                                  style="border: 2px solid #E5E5E7; border-radius: 10px; padding: 12px 16px; font-size: 15px; transition: all 0.3s ease; resize: vertical;"
                                  onfocus="this.style.borderColor='#007AFF'; this.style.boxShadow='0 0 0 4px rgba(0, 122, 255, 0.1)'"
                                  onblur="this.style.borderColor='#E5E5E7'; this.style.boxShadow='none'"></textarea>
                        <small class="form-text" style="color: #86868B; font-size: 12px; margin-top: 6px;">Máximo 500 caracteres</small>
                    </div>

                    <!-- Carga Horária -->
                    <div class="form-group" style="margin-bottom: 24px;">
                        <label for="edit_carga_horaria" style="display: flex; align-items: center; gap: 8px; font-weight: 600; color: #1D1D1F; margin-bottom: 8px; font-size: 14px;">
                            <span class="material-icons-outlined" style="font-size: 20px; color: #007AFF;">schedule</span>
                            Carga Horária (horas)
                        </label>
                        <input type="number" class="form-control" id="edit_carga_horaria" name="carga_horaria" min="0"
                               style="border: 2px solid #E5E5E7; border-radius: 10px; padding: 12px 16px; font-size: 15px; transition: all 0.3s ease;"
                               onfocus="this.style.borderColor='#007AFF'; this.style.boxShadow='0 0 0 4px rgba(0, 122, 255, 0.1)'"
                               onblur="this.style.borderColor='#E5E5E7'; this.style.boxShadow='none'">
                    </div>

                    <!-- Switch Curso Ativo -->
                    <div style="background: linear-gradient(135deg, rgba(0, 122, 255, 0.05) 0%, rgba(88, 86, 214, 0.05) 100%); border-radius: 12px; padding: 16px; display: flex; align-items: center; gap: 12px;">
                        <div class="custom-control custom-switch" style="padding-left: 2.5rem;">
                            <input type="checkbox" class="custom-control-input" id="edit_ativo" name="ativo">
                            <label class="custom-control-label" for="edit_ativo" style="font-weight: 600; color: #1D1D1F; cursor: pointer; display: flex; align-items: center; gap: 8px;">
                                <span class="material-icons-outlined" style="font-size: 20px; color: #34C759;">check_circle</span>
                                Curso Ativo
                            </label>
                        </div>
                    </div>
                </div>

                <!-- Footer Moderno -->
                <div class="modal-footer" style="background: #F5F5F7; border-radius: 0 0 16px 16px; padding: 20px 32px; border: none; gap: 12px;">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal"
                            style="background: white; color: #1D1D1F; border: 2px solid #E5E5E7; border-radius: 10px; padding: 10px 24px; font-weight: 600; font-size: 14px; transition: all 0.3s ease;"
                            onmouseover="this.style.background='#F5F5F7'"
                            onmouseout="this.style.background='white'">
                        <span class="material-icons-outlined" style="font-size: 18px; vertical-align: middle; margin-right: 6px;">close</span>
                        Cancelar
                    </button>
                    <button type="submit" class="btn btn-primary"
                            style="background: linear-gradient(135deg, #007AFF 0%, #5856D6 100%); color: white; border: none; border-radius: 10px; padding: 10px 28px; font-weight: 600; font-size: 14px; box-shadow: 0 4px 12px rgba(0, 122, 255, 0.3); transition: all 0.3s ease;"
                            onmouseover="this.style.transform='translateY(-2px)'; this.style.boxShadow='0 6px 20px rgba(0, 122, 255, 0.4)'"
                            onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 4px 12px rgba(0, 122, 255, 0.3)'">
                        <span class="material-icons-outlined" style="font-size: 18px; vertical-align: middle; margin-right: 6px;">save</span>
                        Atualizar Curso
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Estilos Customizados para Modais Modernos -->
<style>
/* Animação de entrada do modal */
.modal.fade .modal-dialog {
    transform: scale(0.9) translateY(-20px);
    opacity: 0;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

.modal.show .modal-dialog {
    transform: scale(1) translateY(0);
    opacity: 1;
}

/* Backdrop com blur */
.modal-backdrop.show {
    backdrop-filter: blur(8px);
    background-color: rgba(0, 0, 0, 0.5);
}

/* Estilo do switch customizado */
.custom-control-input:checked ~ .custom-control-label::before {
    background: linear-gradient(135deg, #6E41C1 0%, #8B5FD6 100%);
    border-color: #6E41C1;
}

.custom-control-input:focus ~ .custom-control-label::before {
    box-shadow: 0 0 0 4px rgba(110, 65, 193, 0.1);
}

/* Animação de hover nos inputs */
.form-control:hover {
    border-color: #C7C7CC !important;
}

/* Scrollbar customizada para textarea */
textarea::-webkit-scrollbar {
    width: 8px;
}

textarea::-webkit-scrollbar-track {
    background: #F5F5F7;
    border-radius: 4px;
}

textarea::-webkit-scrollbar-thumb {
    background: #C7C7CC;
    border-radius: 4px;
}

textarea::-webkit-scrollbar-thumb:hover {
    background: #6E41C1;
}

/* Animação de pulse no botão submit */
@keyframes pulse {
    0%, 100% { transform: scale(1); }
    50% { transform: scale(1.02); }
}

.btn-primary:active {
    animation: pulse 0.3s ease;
}

/* Estilo para labels com ícones */
label {
    user-select: none;
}

/* Transição suave para todos os elementos do modal */
.modal-content * {
    transition: all 0.2s ease;
}

/* Efeito de foco nos inputs */
.form-control:focus {
    outline: none;
}

/* Estilo para o close button */
.modal-header .close:hover {
    transform: rotate(90deg);
    transition: transform 0.3s ease;
}

/* Estilo da tabela moderna */
#dataTable tbody tr {
    cursor: pointer;
}

/* Paginação do DataTables */
.dataTables_wrapper .dataTables_paginate .paginate_button {
    background: white !important;
    border: 2px solid #E5E5E7 !important;
    border-radius: 8px !important;
    color: #1D1D1F !important;
    padding: 6px 12px !important;
    margin: 0 4px !important;
    transition: all 0.2s ease !important;
}

.dataTables_wrapper .dataTables_paginate .paginate_button:hover {
    background: rgba(110, 65, 193, 0.05) !important;
    border-color: #6E41C1 !important;
    color: #6E41C1 !important;
}

.dataTables_wrapper .dataTables_paginate .paginate_button.current {
    background: #6E41C1 !important;
    border-color: #6E41C1 !important;
    color: white !important;
}

.dataTables_wrapper .dataTables_paginate .paginate_button.disabled {
    opacity: 0.4 !important;
    cursor: not-allowed !important;
}

/* Filtro e busca do DataTables */
.dataTables_wrapper .dataTables_filter input {
    border: 2px solid #E5E5E7 !important;
    border-radius: 10px !important;
    padding: 8px 16px !important;
    transition: all 0.3s ease !important;
}

.dataTables_wrapper .dataTables_filter input:focus {
    border-color: #6E41C1 !important;
    box-shadow: 0 0 0 4px rgba(110, 65, 193, 0.1) !important;
    outline: none !important;
}

.dataTables_wrapper .dataTables_length select {
    border: 2px solid #E5E5E7 !important;
    border-radius: 8px !important;
    padding: 6px 12px !important;
    transition: all 0.3s ease !important;
}

.dataTables_wrapper .dataTables_length select:focus {
    border-color: #6E41C1 !important;
    box-shadow: 0 0 0 4px rgba(110, 65, 193, 0.1) !important;
    outline: none !important;
}

/* Info do DataTables */
.dataTables_wrapper .dataTables_info {
    color: #86868B !important;
    font-size: 13px !important;
    font-weight: 500 !important;
}

/* Animação de entrada dos alertas */
.alert.show {
    animation: slideInDown 0.4s cubic-bezier(0.4, 0, 0.2, 1);
}

@keyframes slideInDown {
    from {
        opacity: 0;
        transform: translateY(-20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

/* Responsividade */
@media (max-width: 768px) {
    .modal-dialog {
        margin: 1rem;
    }

    .modal-body {
        padding: 20px !important;
    }

    .modal-header, .modal-footer {
        padding: 16px 20px !important;
    }

    /* Cards de estatísticas em mobile */
    .col-xl-3 {
        margin-bottom: 16px !important;
    }

    /* Tabela responsiva */
    .table-responsive {
        border-radius: 0 0 14px 14px;
    }

    /* Cabeçalho da página em mobile */
    .d-flex.align-items-center.justify-content-between.flex-wrap {
        flex-direction: column !important;
        align-items: flex-start !important;
    }

    /* Botão Novo Curso em mobile */
    .d-flex.align-items-center.justify-content-between.flex-wrap > div:last-child {
        width: 100%;
    }

    .d-flex.align-items-center.justify-content-between.flex-wrap > div:last-child button {
        width: 100%;
        justify-content: center;
    }
}
</style>

<!-- Scripts -->
<script>
$(document).ready(function() {
    // Inicializar DataTable com configurações melhoradas
    if ($('#dataTable').length) {
        $('#dataTable').DataTable({
            "language": {
                "url": "//cdn.datatables.net/plug-ins/1.10.21/i18n/Portuguese-Brasil.json"
            },
            "pageLength": 10,
            "order": [[3, "desc"]],
            "columnDefs": [
                { "orderable": false, "targets": 4 } // Desabilitar ordenação na coluna de ações
            ],
            "dom": '<"row"<"col-sm-12 col-md-6"l><"col-sm-12 col-md-6"f>>' +
                   '<"row"<"col-sm-12"tr>>' +
                   '<"row"<"col-sm-12 col-md-5"i><"col-sm-12 col-md-7"p>>',
            "drawCallback": function() {
                // Adicionar classes Bootstrap aos elementos do DataTables
                $('.dataTables_wrapper .dataTables_paginate .paginate_button').addClass('btn btn-sm btn-outline-primary');
                $('.dataTables_wrapper .dataTables_paginate .paginate_button.current').addClass('btn-primary text-white');
            }
        });
    }

    // Preencher modal de edição com dados do curso
    $('#editarCurso').on('show.bs.modal', function (e) {
        var button = $(e.relatedTarget);
        var cursoId = button.data('curso-id');
        var cursoNome = button.data('curso-nome');
        var cursoDescricao = button.data('curso-descricao');
        var cursoAtivo = button.data('curso-ativo');

        $('#edit_curso_id').val(cursoId);
        $('#edit_nome').val(cursoNome);
        $('#edit_descricao').val(cursoDescricao);
        $('#edit_ativo').prop('checked', cursoAtivo == 1);
    });

    // Validação de formulários
    $('#formNovoCurso, #formEditarCurso').on('submit', function(e) {
        var nome = $(this).find('[name="nome"]').val().trim();
        var descricao = $(this).find('[name="descricao"]').val().trim();

        if (nome.length === 0) {
            e.preventDefault();
            alert('Por favor, preencha o nome do curso');
            return false;
        }

        if (descricao.length === 0) {
            e.preventDefault();
            alert('Por favor, preencha a descrição do curso');
            return false;
        }
    });

    // Animação ao abrir modais
    $('.modal').on('show.bs.modal', function() {
        $(this).find('.modal-content').addClass('animated fadeIn');
    });

    // Validação visual em tempo real
    $('input[required], textarea[required]').on('blur', function() {
        if ($(this).val().trim() === '') {
            $(this).css({
                'border-color': '#FF3B30',
                'box-shadow': '0 0 0 4px rgba(255, 59, 48, 0.1)'
            });
        } else {
            $(this).css({
                'border-color': '#34C759',
                'box-shadow': '0 0 0 4px rgba(52, 199, 89, 0.1)'
            });
            setTimeout(() => {
                $(this).css({
                    'border-color': '#E5E5E7',
                    'box-shadow': 'none'
                });
            }, 1500);
        }
    });

    // Contador de caracteres para nome do curso
    $('#nome, #edit_nome').on('input', function() {
        const maxLength = 100;
        const currentLength = $(this).val().length;
        const remaining = maxLength - currentLength;

        let color = '#86868B';
        if (remaining < 20) color = '#FF9500';
        if (remaining < 10) color = '#FF3B30';

        $(this).next('small').html(`${remaining} caracteres restantes`).css('color', color);
    });

    // Contador de caracteres para descrição
    $('#descricao, #edit_descricao').on('input', function() {
        const maxLength = 500;
        const currentLength = $(this).val().length;
        const remaining = maxLength - currentLength;

        let color = '#86868B';
        if (remaining < 50) color = '#FF9500';
        if (remaining < 20) color = '#FF3B30';

        $(this).next('small').html(`${remaining} caracteres restantes`).css('color', color);
    });

    // Efeito de sucesso ao submeter formulário
    $('#formNovoCurso, #formEditarCurso').on('submit', function(e) {
        const submitBtn = $(this).find('button[type="submit"]');
        submitBtn.html('<span class="material-icons-outlined" style="font-size: 18px; vertical-align: middle; margin-right: 6px; animation: spin 1s linear infinite;">sync</span>Salvando...');
        submitBtn.prop('disabled', true);
    });

    // Limpar formulário ao fechar modal de novo curso
    $('#novoCurso').on('hidden.bs.modal', function() {
        $('#formNovoCurso')[0].reset();
        $('#formNovoCurso input, #formNovoCurso textarea').css({
            'border-color': '#E5E5E7',
            'box-shadow': 'none'
        });
    });
});

// Animação de rotação para ícone de loading
const style = document.createElement('style');
style.textContent = `
    @keyframes spin {
        from { transform: rotate(0deg); }
        to { transform: rotate(360deg); }
    }
`;
document.head.appendChild(style);
</script>

              </main>
              <footer class="sticky-footer bg-white">
                  <div class="container my-auto">
                      <div class="copyright text-center my-auto">
                          <span>Copyright &copy; Sistema de Certificados <?php echo date('Y'); ?></span>
                      </div>
                  </div>
              </footer>

            </div>
          </div>
        </div>


<?php require_once '../app/views/footer.php'; ?>
<?php $conn->close(); ?>

