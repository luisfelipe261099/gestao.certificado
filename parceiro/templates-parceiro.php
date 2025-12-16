<?php
/**
 * Templates Parceiro - Sistema de Certificados
 * PadrÃ£o MVP - Camada de ApresentaÃ§Ã£o
 */

require_once '../app/config/config.php';

if (!isAuthenticated() || !hasRole(ROLE_PARCEIRO)) {
  redirect(APP_URL . '/login.php');
}

$page_title = 'Templates de Certificados - ' . APP_NAME;
$user = getCurrentUser();
$conn = getDBConnection();

// Buscar templates do parceiro + templates do sistema
$templates = [];
$parceiro_id = $user['parceiro_id'] ?? $user['id'];

// Fontes disponíveis
$fonts = [
  'Arial',
  'Helvetica',
  'Times',
  'Courier',
  'Symbol',
  'AbrilFatface-Regular',
  'AmaticSC-Regular',
  'Anton-Regular',
  'Bangers-Regular',
  'BebasNeue-Regular',
  'CevicheOne-Regular',
  'Creepster-Regular',
  'GreatVibes-Regular',
  'IndieFlower-Regular',
  'Lato-Regular',
  'Lobster-Regular',
  'Mukta',
  'NotoSans',
  'Pacifico-Regular',
  'Poppins-Regular',
  'Ubuntu-Regular'
];

// Buscar limite de templates do plano ativo
$limite_templates = 5; // Valor padrão
$stmt_plano = $conn->prepare("
    SELECT p.quantidade_templates 
    FROM assinaturas a 
    JOIN planos p ON a.plano_id = p.id 
    WHERE a.parceiro_id = ? AND a.status = 'ativa' 
    LIMIT 1
");
if ($stmt_plano) {
  $stmt_plano->bind_param("i", $parceiro_id);
  $stmt_plano->execute();
  $result_plano = $stmt_plano->get_result();
  if ($row_plano = $result_plano->fetch_assoc()) {
    $limite_templates = (int) $row_plano['quantidade_templates'];
  }
  $stmt_plano->close();
}

// Query: Busca templates do parceiro OU templates do sistema (template_sistema = 1)
$stmt = $conn->prepare("
  SELECT * FROM templates_certificados 
  WHERE parceiro_id = ? OR (template_sistema = 1 AND ativo = 1)
  ORDER BY template_sistema DESC, ativo DESC, criado_em DESC
");

$meus_templates_count = 0;

if ($stmt) {
  $stmt->bind_param("i", $parceiro_id);
  $stmt->execute();
  $result = $stmt->get_result();
  while ($row = $result->fetch_assoc()) {
    // Contar apenas templates criados pelo parceiro (não do sistema)
    if ($row['parceiro_id'] == $parceiro_id && $row['template_sistema'] == 0) {
      $meus_templates_count++;
    }

    // Buscar campos customizados do template
    $stmt_campos = $conn->prepare("SELECT id, tipo_campo, label, valor_padrao, posicao_x, posicao_y, tamanho_fonte, cor_hex, ordem FROM template_campos_customizados WHERE template_id = ? ORDER BY ordem ASC");
    if ($stmt_campos) {
      $stmt_campos->bind_param("i", $row['id']);
      $stmt_campos->execute();
      $result_campos = $stmt_campos->get_result();
      $row['campos_customizados'] = [];
      while ($campo = $result_campos->fetch_assoc()) {
        $row['campos_customizados'][] = $campo;
      }
      $stmt_campos->close();
    }
    $templates[] = $row;
  }
  $stmt->close();
}

$pode_criar_template = ($meus_templates_count < $limite_templates);
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
            <span class="nav-section-title">NavegaÃ§Ã£o</span>
            <ul>
              <li><a href="<?php echo APP_URL; ?>/parceiro/dashboard-parceiro.php"><span class="icon">dashboard</span>
                  Dashboard</a></li>
            </ul>
            <span class="nav-section-title">AcadÃªmico</span>
            <ul>
              <li><a href="<?php echo APP_URL; ?>/parceiro/cursos-parceiro.php"><span class="icon">school</span>
                  Cursos</a></li>
              <li><a href="<?php echo APP_URL; ?>/parceiro/alunos-parceiro.php"><span class="icon">group</span>
                  Alunos</a></li>
            </ul>
            <span class="nav-section-title">CertificaÃ§Ã£o</span>
            <ul>
              <li><a href="<?php echo APP_URL; ?>/parceiro/templates-parceiro.php" class="active"
                  title="Templates de Certificados"><span class="icon">article</span> Templates</a></li>
              <li><a href="<?php echo APP_URL; ?>/parceiro/gerar-certificados.php"><span
                    class="icon">workspace_premium</span> Emitir Cert.</a></li>
            </ul>
            <span class="nav-section-title">Minha Conta</span>
            <ul>
              <li><a href="<?php echo APP_URL; ?>/parceiro/financeiro.php"><span class="icon">credit_card</span>
                  Financeiro</a></li>
              <li><a href="<?php echo APP_URL; ?>/parceiro/meu-plano.php"><span class="icon">price_check</span> Meu
                  Plano</a></li>
            </ul>
          </nav>
          <div class="sidebar-footer">
            <ul>
              <li><a href="<?php echo APP_URL; ?>/parceiro/perfil-parceiro.php"><span class="icon">person</span> Meu
                  Perfil</a></li>
              <li><a href="<?php echo APP_URL; ?>/app/actions/logout.php"><span class="icon">logout</span> Sair</a></li>
            </ul>
          </div>
        </aside>

        <div class="main-wrapper">
          <header class="top-header">
            <span class="icon notifications">notifications</span>
            <div class="user-profile">
              <span class="icon">account_circle</span>
              <?php echo htmlspecialchars($user['nome'] ?? ($user['email'] ?? 'UsuÃ¡rio')); ?>
            </div>
          </header>

          <main class="content-area">

            <div class="container-fluid">
              <!-- CabeÃ§alho da PÃ¡gina - Design Moderno -->
              <div
                style="background: linear-gradient(135deg, rgba(110, 65, 193, 0.03) 0%, rgba(110, 65, 193, 0.01) 100%); border-radius: 16px; padding: 28px 32px; margin-bottom: 32px; border: 1px solid rgba(110, 65, 193, 0.08);">
                <div class="d-flex align-items-center justify-content-between flex-wrap" style="gap: 20px;">
                  <div style="flex: 1; min-width: 250px;">
                    <div style="display: flex; align-items: center; gap: 16px; margin-bottom: 8px;">
                      <div
                        style="width: 56px; height: 56px; background: linear-gradient(135deg, #6E41C1 0%, #8B5FD6 100%); border-radius: 14px; display: flex; align-items: center; justify-content: center; box-shadow: 0 4px 12px rgba(110, 65, 193, 0.25);">
                        <span class="material-icons-outlined" style="font-size: 32px; color: white;">article</span>
                      </div>
                      <div>
                        <h1
                          style="font-size: 28px; font-weight: 700; color: #1D1D1F; margin: 0; letter-spacing: -0.5px;">
                          Templates de Certificados</h1>
                        <p style="color: #86868B; font-size: 14px; margin: 4px 0 0 0;">Crie e customize os modelos de
                          certificados para seus cursos</p>
                      </div>
                    </div>
                  </div>
                  <div style="display: flex; gap: 10px; align-items: center;">
                    <?php if ($pode_criar_template): ?>
                      <button type="button" data-toggle="modal" data-target="#novoTemplate"
                        style="background: linear-gradient(135deg, #6E41C1 0%, #8B5FD6 100%); color: white; border: none; border-radius: 12px; padding: 12px 24px; font-weight: 600; font-size: 15px; box-shadow: 0 4px 12px rgba(110, 65, 193, 0.3); transition: all 0.3s ease; display: inline-flex; align-items: center; gap: 8px; cursor: pointer;"
                        onmouseover="this.style.transform='translateY(-2px)'; this.style.boxShadow='0 6px 20px rgba(110, 65, 193, 0.4)'"
                        onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 4px 12px rgba(110, 65, 193, 0.3)'">
                        <span class="material-icons-outlined" style="font-size: 20px;">add_circle</span>
                        Novo Template
                      </button>
                    <?php else: ?>
                      <button type="button" disabled
                        style="background: #E5E5E7; color: #86868B; border: none; border-radius: 12px; padding: 12px 24px; font-weight: 600; font-size: 15px; cursor: not-allowed; display: inline-flex; align-items: center; gap: 8px;"
                        title="Limite de templates atingido. Faça upgrade do seu plano.">
                        <span class="material-icons-outlined" style="font-size: 20px;">lock</span>
                        Limite Atingido
                      </button>
                    <?php endif; ?>
                  </div>
                </div>
                <!-- Barra de Progresso de Uso -->
                <div
                  style="margin-top: 20px; background: rgba(110, 65, 193, 0.05); border-radius: 8px; padding: 12px 16px;">
                  <div
                    style="display: flex; justify-content: space-between; margin-bottom: 8px; font-size: 13px; font-weight: 600; color: #6E41C1;">
                    <span>Uso de Templates</span>
                    <span><?php echo $meus_templates_count; ?> / <?php echo $limite_templates; ?></span>
                  </div>
                  <div style="height: 6px; background: #E0E0E0; border-radius: 3px; overflow: hidden;">
                    <?php $porcentagem = min(100, ($meus_templates_count / $limite_templates) * 100); ?>
                    <div
                      style="height: 100%; width: <?php echo $porcentagem; ?>%; background: <?php echo ($porcentagem >= 100) ? '#FF3B30' : '#6E41C1'; ?>; border-radius: 3px;">
                    </div>
                  </div>
                  <?php if (!$pode_criar_template): ?>
                    <div
                      style="margin-top: 8px; font-size: 12px; color: #FF3B30; display: flex; align-items: center; gap: 4px;">
                      <span class="material-icons-outlined" style="font-size: 14px;">info</span>
                      Você atingiu o limite de templates do seu plano.
                    </div>
                  <?php endif; ?>
                </div>
              </div>

              <!-- EstatÃ­sticas de Templates - Design Moderno -->
              <div class="row mb-4">
                <!-- Total de Templates -->
                <div class="col-xl-3 col-md-6 mb-4">
                  <div class="card h-100"
                    style="border: none; border-radius: 14px; background: white; box-shadow: 0 2px 8px rgba(0,0,0,0.08); border-left: 4px solid #6E41C1; transition: all 0.3s ease;"
                    onmouseover="this.style.transform='translateY(-4px)'; this.style.boxShadow='0 8px 20px rgba(110, 65, 193, 0.15)'"
                    onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 2px 8px rgba(0,0,0,0.08)'">
                    <div class="card-body" style="padding: 20px;">
                      <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 12px;">
                        <div
                          style="width: 48px; height: 48px; background: linear-gradient(135deg, rgba(110, 65, 193, 0.1) 0%, rgba(110, 65, 193, 0.05) 100%); border-radius: 12px; display: flex; align-items: center; justify-content: center;">
                          <span class="material-icons-outlined" style="font-size: 24px; color: #6E41C1;">article</span>
                        </div>
                        <div>
                          <div
                            style="font-size: 11px; font-weight: 600; color: #6E41C1; text-transform: uppercase; letter-spacing: 0.5px;">
                            Total de Templates</div>
                          <div style="font-size: 28px; font-weight: 700; color: #1D1D1F; line-height: 1;">
                            <?php echo count($templates); ?>
                          </div>
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
                    <span class="material-icons-outlined" style="font-size: 24px; color: white;">check_circle</span>
                  </div>
                  <div style="flex: 1;">
                    <strong style="color: #34C759; font-weight: 700; font-size: 14px;">Sucesso!</strong>
                    <span
                      style="color: #1D1D1F; font-size: 14px; margin-left: 8px;"><?php echo $_SESSION['success']; ?></span>
                  </div>
                  <button type="button" class="close" data-dismiss="alert" aria-label="Close"
                    style="color: #34C759; opacity: 1; font-size: 24px; font-weight: 300; padding: 0; margin: 0; background: none; border: none; cursor: pointer; transition: all 0.2s ease;"
                    onmouseover="this.style.transform='rotate(90deg)'" onmouseout="this.style.transform='rotate(0deg)'">
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
                    <span class="material-icons-outlined" style="font-size: 24px; color: white;">error</span>
                  </div>
                  <div style="flex: 1;">
                    <strong style="color: #FF3B30; font-weight: 700; font-size: 14px;">Erro!</strong>
                    <span
                      style="color: #1D1D1F; font-size: 14px; margin-left: 8px;"><?php echo $_SESSION['error']; ?></span>
                  </div>
                  <button type="button" class="close" data-dismiss="alert" aria-label="Close"
                    style="color: #FF3B30; opacity: 1; font-size: 24px; font-weight: 300; padding: 0; margin: 0; background: none; border: none; cursor: pointer; transition: all 0.2s ease;"
                    onmouseover="this.style.transform='rotate(90deg)'" onmouseout="this.style.transform='rotate(0deg)'">
                    <span aria-hidden="true">&times;</span>
                  </button>
                </div>
                <?php unset($_SESSION['error']); ?>
              <?php endif; ?>


              <?php if (empty($templates)): ?>
                <div class="alert alert-dismissible fade show" role="alert"
                  style="background: linear-gradient(135deg, rgba(110, 65, 193, 0.1) 0%, rgba(110, 65, 193, 0.05) 100%); border: 2px solid rgba(110, 65, 193, 0.3); border-radius: 12px; padding: 16px 20px; margin-bottom: 24px; display: flex; align-items: center; gap: 12px;">
                  <div
                    style="width: 40px; height: 40px; background: #6E41C1; border-radius: 10px; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                    <span class="material-icons-outlined" style="font-size: 24px; color: white;">info</span>
                  </div>
                  <div style="flex: 1;">
                    <strong style="color: #6E41C1; font-weight: 700; font-size: 14px;">Nenhum template
                      cadastrado!</strong>
                    <span style="color: #1D1D1F; font-size: 14px; margin-left: 8px;">Crie seu primeiro template de
                      certificado.</span>
                  </div>
                  <button type="button" class="close" data-dismiss="alert" aria-label="Close"
                    style="color: #6E41C1; opacity: 1; font-size: 24px; font-weight: 300; padding: 0; margin: 0; background: none; border: none; cursor: pointer; transition: all 0.2s ease;"
                    onmouseover="this.style.transform='rotate(90deg)'" onmouseout="this.style.transform='rotate(0deg)'">
                    <span aria-hidden="true">&times;</span>
                  </button>
                </div>
              <?php endif; ?>

              <!-- Tabela de Templates - Design Moderno -->
              <div class="card"
                style="border: none; border-radius: 14px; background: white; box-shadow: 0 2px 8px rgba(0,0,0,0.08);">
                <div class="card-header"
                  style="background: linear-gradient(135deg, rgba(110, 65, 193, 0.05) 0%, rgba(110, 65, 193, 0.02) 100%); border-radius: 14px 14px 0 0; padding: 20px 24px; border: none; border-bottom: 1px solid #F5F5F7;">
                  <div style="display: flex; justify-content: space-between; align-items: center;">
                    <div style="display: flex; align-items: center; gap: 12px;">
                      <span class="material-icons-outlined" style="font-size: 24px; color: #6E41C1;">list_alt</span>
                      <h6 style="margin: 0; font-weight: 700; color: #1D1D1F; font-size: 16px;">Meus Templates</h6>
                    </div>
                    <span
                      style="background: #6E41C1; color: white; padding: 4px 12px; border-radius: 20px; font-size: 12px; font-weight: 600;">
                      <?php echo count($templates); ?> template(s)
                    </span>
                  </div>
                </div>
                <div class="card-body" style="padding: 0;">
                  <div class="table-responsive">
                    <table class="table table-hover" width="100%" cellspacing="0" id="templatesTable"
                      style="margin-bottom: 0;">
                      <thead>
                        <tr style="background: #F5F5F7; border-bottom: 2px solid #E5E5E7;">
                          <th
                            style="padding: 16px 20px; font-size: 12px; font-weight: 700; color: #6E41C1; text-transform: uppercase; letter-spacing: 0.5px; border: none;">
                            <span class="material-icons-outlined"
                              style="font-size: 16px; vertical-align: middle; margin-right: 6px;">description</span>
                            Nome
                          </th>
                          <th
                            style="padding: 16px 20px; font-size: 12px; font-weight: 700; color: #6E41C1; text-transform: uppercase; letter-spacing: 0.5px; border: none;">
                            <span class="material-icons-outlined"
                              style="font-size: 16px; vertical-align: middle; margin-right: 6px;">notes</span>
                            DescriÃ§Ã£o
                          </th>
                          <th
                            style="padding: 16px 20px; font-size: 12px; font-weight: 700; color: #6E41C1; text-transform: uppercase; letter-spacing: 0.5px; border: none;">
                            <span class="material-icons-outlined"
                              style="font-size: 16px; vertical-align: middle; margin-right: 6px;">toggle_on</span>
                            Status
                          </th>
                          <th
                            style="padding: 16px 20px; font-size: 12px; font-weight: 700; color: #6E41C1; text-transform: uppercase; letter-spacing: 0.5px; border: none;">
                            <span class="material-icons-outlined"
                              style="font-size: 16px; vertical-align: middle; margin-right: 6px;">calendar_today</span>
                            Data de CriaÃ§Ã£o
                          </th>
                          <th class="text-center"
                            style="padding: 16px 20px; font-size: 12px; font-weight: 700; color: #6E41C1; text-transform: uppercase; letter-spacing: 0.5px; border: none;">
                            <span class="material-icons-outlined"
                              style="font-size: 16px; vertical-align: middle; margin-right: 6px;">settings</span>
                            AÃ§Ãµes
                          </th>
                        </tr>
                      </thead>
                      <tbody>
                        <?php foreach ($templates as $template): ?>
                          <tr style="border-bottom: 1px solid #F5F5F7; transition: all 0.2s ease;"
                            onmouseover="this.style.background='rgba(110, 65, 193, 0.02)'"
                            onmouseout="this.style.background='white'">
                            <td style="padding: 16px 20px; border: none;">
                              <div style="display: flex; align-items: center; gap: 10px;">
                                <div
                                  style="width: 36px; height: 36px; background: linear-gradient(135deg, rgba(110, 65, 193, 0.1) 0%, rgba(110, 65, 193, 0.05) 100%); border-radius: 8px; display: flex; align-items: center; justify-content: center;">
                                  <span class="material-icons-outlined"
                                    style="font-size: 18px; color: #6E41C1;">article</span>
                                </div>
                                <strong
                                  style="color: #1D1D1F; font-size: 14px; font-weight: 600;"><?php echo htmlspecialchars($template['nome']); ?></strong>
                              </div>
                            </td>
                            <td style="padding: 16px 20px; border: none;">
                              <span style="color: #86868B; font-size: 13px;">
                                <?php echo htmlspecialchars(substr($template['descricao'], 0, 60)); ?>
                                <?php echo strlen($template['descricao']) > 60 ? '...' : ''; ?>
                              </span>
                            </td>
                            <td style="padding: 16px 20px; border: none;">
                              <?php if ($template['ativo']): ?>
                                <span
                                  style="background: linear-gradient(135deg, rgba(52, 199, 89, 0.1) 0%, rgba(52, 199, 89, 0.05) 100%); color: #34C759; padding: 6px 12px; border-radius: 20px; font-size: 12px; font-weight: 600; display: inline-flex; align-items: center; gap: 4px;">
                                  <span class="material-icons-outlined" style="font-size: 14px;">check_circle</span>
                                  Ativo
                                </span>
                              <?php else: ?>
                                <span
                                  style="background: linear-gradient(135deg, rgba(134, 134, 139, 0.1) 0%, rgba(134, 134, 139, 0.05) 100%); color: #86868B; padding: 6px 12px; border-radius: 20px; font-size: 12px; font-weight: 600; display: inline-flex; align-items: center; gap: 4px;">
                                  <span class="material-icons-outlined" style="font-size: 14px;">cancel</span>
                                  Inativo
                                </span>
                              <?php endif; ?>
                            </td>
                            <td style="padding: 16px 20px; border: none;">
                              <span
                                style="color: #86868B; font-size: 13px; display: inline-flex; align-items: center; gap: 6px;">
                                <span class="material-icons-outlined" style="font-size: 16px;">event</span>
                                <?php echo date('d/m/Y', strtotime($template['criado_em'])); ?>
                              </span>
                            </td>
                            <td class="text-center" style="padding: 16px 20px; border: none;">
                              <div style="display: inline-flex; gap: 6px; justify-content: center;">
                                <!-- BotÃ£o Visualizar Template -->
                                <a href="<?php echo APP_URL; ?>/app/actions/visualizar-template.php?id=<?php echo (int) $template['id']; ?>"
                                  target="_blank" title="Visualizar template (gerar exemplo)"
                                  style="background: linear-gradient(135deg, rgba(0, 122, 255, 0.1) 0%, rgba(0, 122, 255, 0.05) 100%); color: #007AFF; border: none; border-radius: 8px; padding: 8px 10px; cursor: pointer; transition: all 0.2s ease; display: inline-flex; align-items: center; justify-content: center; text-decoration: none;"
                                  onmouseover="this.style.background='#007AFF'; this.style.color='white'; this.style.transform='translateY(-2px)'; this.style.boxShadow='0 4px 8px rgba(0, 122, 255, 0.3)'"
                                  onmouseout="this.style.background='linear-gradient(135deg, rgba(0, 122, 255, 0.1) 0%, rgba(0, 122, 255, 0.05) 100%)'; this.style.color='#007AFF'; this.style.transform='translateY(0)'; this.style.boxShadow='none'">
                                  <span class="material-icons-outlined" style="font-size: 18px;">visibility</span>
                                </a>

                                <!-- BotÃ£o Editar -->
                                <button type="button" data-toggle="modal"
                                  data-target="#editarTemplate-<?php echo (int) $template['id']; ?>"
                                  title="Editar template"
                                  style="background: linear-gradient(135deg, rgba(110, 65, 193, 0.1) 0%, rgba(110, 65, 193, 0.05) 100%); color: #6E41C1; border: none; border-radius: 8px; padding: 8px 10px; cursor: pointer; transition: all 0.2s ease; display: inline-flex; align-items: center; justify-content: center;"
                                  onmouseover="this.style.background='#6E41C1'; this.style.color='white'; this.style.transform='translateY(-2px)'; this.style.boxShadow='0 4px 8px rgba(110, 65, 193, 0.3)'"
                                  onmouseout="this.style.background='linear-gradient(135deg, rgba(110, 65, 193, 0.1) 0%, rgba(110, 65, 193, 0.05) 100%)'; this.style.color='#6E41C1'; this.style.transform='translateY(0)'; this.style.boxShadow='none'">
                                  <span class="material-icons-outlined" style="font-size: 18px;">edit</span>
                                </button>

                                <!-- BotÃ£o Ajustar PosiÃ§Ãµes -->
                                <button type="button" class="btn-ajustar" data-toggle="modal"
                                  data-target="#editarTemplate-<?php echo (int) $template['id']; ?>"
                                  title="Ajustar posiÃ§Ãµes"
                                  style="background: linear-gradient(135deg, rgba(110, 65, 193, 0.1) 0%, rgba(110, 65, 193, 0.05) 100%); color: #6E41C1; border: none; border-radius: 8px; padding: 8px 10px; cursor: pointer; transition: all 0.2s ease; display: inline-flex; align-items: center; justify-content: center;"
                                  onmouseover="this.style.background='#6E41C1'; this.style.color='white'; this.style.transform='translateY(-2px)'; this.style.boxShadow='0 4px 8px rgba(110, 65, 193, 0.3)'"
                                  onmouseout="this.style.background='linear-gradient(135deg, rgba(110, 65, 193, 0.1) 0%, rgba(110, 65, 193, 0.05) 100%)'; this.style.color='#6E41C1'; this.style.transform='translateY(0)'; this.style.boxShadow='none'">
                                  <span class="material-icons-outlined" style="font-size: 18px;">open_with</span>
                                </button>

                                <!-- BotÃ£o Excluir -->
                                <form action="<?php echo APP_URL; ?>/app/actions/excluir-template.php" method="post"
                                  style="display:inline; margin: 0;"
                                  onsubmit="return confirm('âš ï¸ Tem certeza que deseja excluir este template?\n\nâŒ Esta aÃ§Ã£o nÃ£o pode ser desfeita!');">
                                  <input type="hidden" name="id" value="<?php echo (int) $template['id']; ?>">
                                  <button type="submit" title="Excluir template"
                                    style="background: linear-gradient(135deg, rgba(255, 59, 48, 0.1) 0%, rgba(255, 59, 48, 0.05) 100%); color: #FF3B30; border: none; border-radius: 8px; padding: 8px 10px; cursor: pointer; transition: all 0.2s ease; display: inline-flex; align-items: center; justify-content: center;"
                                    onmouseover="this.style.background='#FF3B30'; this.style.color='white'; this.style.transform='translateY(-2px)'; this.style.boxShadow='0 4px 8px rgba(255, 59, 48, 0.3)'"
                                    onmouseout="this.style.background='linear-gradient(135deg, rgba(255, 59, 48, 0.1) 0%, rgba(255, 59, 48, 0.05) 100%)'; this.style.color='#FF3B30'; this.style.transform='translateY(0)'; this.style.boxShadow='none'">
                                    <span class="material-icons-outlined" style="font-size: 18px;">delete</span>
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

      <!-- Modais de EdiÃ§Ã£o de Template -->
      <?php foreach ($templates as $t): ?>
        <div class="modal fade" id="editarTemplate-<?php echo (int) $t['id']; ?>" tabindex="-1" role="dialog"
          aria-hidden="true">
          <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
              <div class="modal-header"
                style="background: linear-gradient(135deg, #6E41C1 0%, #8B5FD6 100%); border-radius: 16px 16px 0 0; padding: 24px 28px; border: none;">
                <div style="display: flex; align-items: center; gap: 12px;">
                  <div
                    style="width: 44px; height: 44px; background: rgba(255, 255, 255, 0.2); border-radius: 12px; display: flex; align-items: center; justify-content: center;">
                    <span class="material-icons-outlined" style="font-size: 24px; color: white;">edit</span>
                  </div>
                  <h5 class="modal-title" style="color: white; font-weight: 700; font-size: 20px; margin: 0;">Editar
                    Template: <?php echo htmlspecialchars($t['nome']); ?></h5>
                </div>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"
                  style="color: white; opacity: 1; font-size: 28px; font-weight: 300; text-shadow: none; transition: all 0.2s ease;"
                  onmouseover="this.style.transform='rotate(90deg)'" onmouseout="this.style.transform='rotate(0deg)'">
                  <span aria-hidden="true">&times;</span>
                </button>
              </div>
              <form id="formEditarTemplate-<?php echo $t['id']; ?>" method="POST"
                action="<?php echo APP_URL; ?>/app/actions/atualizar-template.php" enctype="multipart/form-data">
                <div class="modal-body">
                  <input type="hidden" name="id" value="<?php echo (int) $t['id']; ?>">
                  <div class="form-row">
                    <div class="form-group col-md-6">
                      <label
                        style="font-weight: 600; color: #1D1D1F; font-size: 14px; margin-bottom: 8px; display: flex; align-items: center; gap: 6px;">
                        <span class="material-icons-outlined" style="font-size: 18px; color: #6E41C1;">description</span>
                        Nome <span style="color: #FF3B30;">*</span>
                      </label>
                      <input type="text" class="form-control" name="nome"
                        value="<?php echo htmlspecialchars($t['nome']); ?>" placeholder="Nome do template" required>
                    </div>
                    <div class="form-group col-md-6">
                      <label
                        style="font-weight: 600; color: #1D1D1F; font-size: 14px; margin-bottom: 8px; display: flex; align-items: center; gap: 6px;">
                        <span class="material-icons-outlined" style="font-size: 18px; color: #6E41C1;">toggle_on</span>
                        Status
                      </label>
                      <div class="form-check mt-2">
                        <input type="checkbox" class="form-check-input" id="ativo-<?php echo (int) $t['id']; ?>"
                          name="ativo" value="1" <?php echo ($t['ativo'] ? 'checked' : ''); ?>>
                        <label class="form-check-label" for="ativo-<?php echo (int) $t['id']; ?>"
                          style="font-weight: 600; color: #1D1D1F; font-size: 14px;">
                          Tornar este template ativo
                        </label>
                      </div>
                    </div>
                  </div>
                  <div class="form-group">
                    <label
                      style="font-weight: 600; color: #1D1D1F; font-size: 14px; margin-bottom: 8px; display: flex; align-items: center; gap: 6px;">
                      <span class="material-icons-outlined" style="font-size: 18px; color: #6E41C1;">notes</span>
                      DescriÃ§Ã£o <span style="color: #FF3B30;">*</span>
                    </label>
                    <textarea class="form-control" name="descricao" rows="3" placeholder="Descreva o template"
                      required><?php echo htmlspecialchars($t['descricao']); ?></textarea>
                  </div>

                  <!-- DimensÃµes do Template -->
                  <div
                    style="background: linear-gradient(135deg, rgba(110, 65, 193, 0.05) 0%, rgba(110, 65, 193, 0.02) 100%); border-left: 3px solid #6E41C1; border-radius: 10px; padding: 16px 20px; margin-bottom: 20px;">
                    <h6
                      style="color: #6E41C1; font-weight: 700; font-size: 14px; margin-bottom: 12px; display: flex; align-items: center; gap: 8px;">
                      <span class="material-icons-outlined" style="font-size: 20px;">aspect_ratio</span>
                      DimensÃµes do Template
                    </h6>
                    <div class="form-row">
                      <div class="form-group col-md-4">
                        <label style="font-weight: 600; color: #1D1D1F; font-size: 13px; margin-bottom: 6px;">Largura
                          (px)</label>
                        <input type="number" class="form-control" name="largura_mm" min="1" max="10000"
                          value="<?php echo (int) ($t['largura_mm'] ?? 2048); ?>">
                      </div>
                      <div class="form-group col-md-4">
                        <label style="font-weight: 600; color: #1D1D1F; font-size: 13px; margin-bottom: 6px;">Altura
                          (px)</label>
                        <input type="number" class="form-control" name="altura_mm" min="1" max="10000"
                          value="<?php echo (int) ($t['altura_mm'] ?? 1152); ?>">
                      </div>
                      <div class="form-group col-md-4 d-flex align-items-end">
                        <small style="color: #86868B; font-size: 12px;">PadrÃ£o: 2048 x 1152 px</small>
                      </div>
                    </div>
                  </div>

                  <!-- ConfiguraÃ§Ã£o dos Campos PadrÃ£o -->
                  <div
                    style="background: linear-gradient(135deg, rgba(110, 65, 193, 0.05) 0%, rgba(110, 65, 193, 0.02) 100%); border-left: 3px solid #6E41C1; border-radius: 10px; padding: 16px 20px; margin-bottom: 20px;">
                    <h6
                      style="color: #6E41C1; font-weight: 700; font-size: 14px; margin-bottom: 12px; display: flex; align-items: center; gap: 8px;">
                      <span class="material-icons-outlined" style="font-size: 20px;">settings_applications</span>
                      ConfiguraÃ§Ã£o dos Campos PadrÃ£o
                    </h6>

                    <!-- Campo Nome -->
                    <div class="mb-3"
                      style="background: white; padding: 12px; border-radius: 8px; border: 1px solid #E0E0E0;">
                      <div class="d-flex align-items-center justify-content-between mb-2">
                        <div class="custom-control custom-switch">
                          <input type="checkbox" class="custom-control-input toggle-campo"
                            id="exibir_nome_<?php echo $t['id']; ?>" name="exibir_nome" value="1" <?php echo ($t['exibir_nome'] ?? 1) ? 'checked' : ''; ?> data-target="nome">
                          <label class="custom-control-label" for="exibir_nome_<?php echo $t['id']; ?>"
                            style="font-weight: 600; color: #1D1D1F;">Exibir Nome do Aluno</label>
                        </div>
                      </div>
                      <div class="form-row config-campo-nome"
                        style="<?php echo ($t['exibir_nome'] ?? 1) ? '' : 'display:none;'; ?>">
                        <div class="form-group col-md-3">
                          <label style="font-size: 12px; font-weight: 600;">PosiÃ§Ã£o X</label>
                          <input type="number" class="form-control form-control-sm" name="posicao_nome_x"
                            value="<?php echo (int) ($t['posicao_nome_x'] ?? 0); ?>">
                        </div>
                        <div class="form-group col-md-3">
                          <label style="font-size: 12px; font-weight: 600;">PosiÃ§Ã£o Y</label>
                          <input type="number" class="form-control form-control-sm" name="posicao_nome_y"
                            value="<?php echo (int) ($t['posicao_nome_y'] ?? 0); ?>">
                        </div>
                        <div class="form-group col-md-3">
                          <label style="font-size: 12px; font-weight: 600;">Fonte</label>
                          <select class="form-control form-control-sm update-preview" name="fonte_nome" data-target="nome"
                            data-prop="fontFamily">
                            <?php foreach ($fonts as $f): ?>
                              <option value="<?php echo $f; ?>" <?php echo ($t['fonte_nome'] ?? 'Arial') === $f ? 'selected' : ''; ?>><?php echo $f; ?></option>
                            <?php endforeach; ?>
                          </select>
                        </div>
                        <div class="form-group col-md-3">
                          <label style="font-size: 12px; font-weight: 600;">Tamanho Fonte</label>
                          <input type="number" class="form-control form-control-sm update-preview"
                            name="tamanho_fonte_nome" value="<?php echo (int) ($t['tamanho_fonte_nome'] ?? 24); ?>"
                            data-target="nome" data-prop="fontSize">
                        </div>
                        <div class="form-group col-md-3">
                          <label style="font-size: 12px; font-weight: 600;">Cor</label>
                          <input type="color" class="form-control form-control-sm update-preview" name="cor_nome"
                            value="<?php echo htmlspecialchars($t['cor_nome'] ?? '#000000'); ?>"
                            style="height: 31px; padding: 2px;" data-target="nome" data-prop="color">
                        </div>
                      </div>
                    </div>

                    <!-- Campo Curso -->
                    <div class="mb-3"
                      style="background: white; padding: 12px; border-radius: 8px; border: 1px solid #E0E0E0;">
                      <div class="d-flex align-items-center justify-content-between mb-2">
                        <div class="custom-control custom-switch">
                          <input type="checkbox" class="custom-control-input toggle-campo"
                            id="exibir_curso_<?php echo $t['id']; ?>" name="exibir_curso" value="1" <?php echo ($t['exibir_curso'] ?? 1) ? 'checked' : ''; ?> data-target="curso">
                          <label class="custom-control-label" for="exibir_curso_<?php echo $t['id']; ?>"
                            style="font-weight: 600; color: #1D1D1F;">Exibir Nome do Curso</label>
                        </div>
                      </div>
                      <div class="form-row config-campo-curso"
                        style="<?php echo ($t['exibir_curso'] ?? 1) ? '' : 'display:none;'; ?>">
                        <div class="form-group col-md-3">
                          <label style="font-size: 12px; font-weight: 600;">PosiÃ§Ã£o X</label>
                          <input type="number" class="form-control form-control-sm" name="posicao_curso_x"
                            value="<?php echo (int) ($t['posicao_curso_x'] ?? 0); ?>">
                        </div>
                        <div class="form-group col-md-3">
                          <label style="font-size: 12px; font-weight: 600;">PosiÃ§Ã£o Y</label>
                          <input type="number" class="form-control form-control-sm" name="posicao_curso_y"
                            value="<?php echo (int) ($t['posicao_curso_y'] ?? 0); ?>">
                        </div>
                        <div class="form-group col-md-3">
                          <label style="font-size: 12px; font-weight: 600;">Fonte</label>
                          <select class="form-control form-control-sm update-preview" name="fonte_curso"
                            data-target="curso" data-prop="fontFamily">
                            <?php foreach ($fonts as $f): ?>
                              <option value="<?php echo $f; ?>" <?php echo ($t['fonte_curso'] ?? 'Arial') === $f ? 'selected' : ''; ?>><?php echo $f; ?></option>
                            <?php endforeach; ?>
                          </select>
                        </div>
                        <div class="form-group col-md-3">
                          <label style="font-size: 12px; font-weight: 600;">Tamanho Fonte</label>
                          <input type="number" class="form-control form-control-sm update-preview"
                            name="tamanho_fonte_curso" value="<?php echo (int) ($t['tamanho_fonte_curso'] ?? 16); ?>"
                            data-target="curso" data-prop="fontSize">
                        </div>
                        <div class="form-group col-md-3">
                          <label style="font-size: 12px; font-weight: 600;">Cor</label>
                          <input type="color" class="form-control form-control-sm update-preview" name="cor_curso"
                            value="<?php echo htmlspecialchars($t['cor_curso'] ?? '#000000'); ?>"
                            style="height: 31px; padding: 2px;" data-target="curso" data-prop="color">
                        </div>
                      </div>
                    </div>

                    <!-- Campo Data -->
                    <div class="mb-3"
                      style="background: white; padding: 12px; border-radius: 8px; border: 1px solid #E0E0E0;">
                      <div class="d-flex align-items-center justify-content-between mb-2">
                        <div class="custom-control custom-switch">
                          <input type="checkbox" class="custom-control-input toggle-campo"
                            id="exibir_data_<?php echo $t['id']; ?>" name="exibir_data" value="1" <?php echo ($t['exibir_data'] ?? 1) ? 'checked' : ''; ?> data-target="data">
                          <label class="custom-control-label" for="exibir_data_<?php echo $t['id']; ?>"
                            style="font-weight: 600; color: #1D1D1F;">Exibir Data de Conclusão</label>
                        </div>
                      </div>
                      <div class="form-row config-campo-data"
                        style="<?php echo ($t['exibir_data'] ?? 1) ? '' : 'display:none;'; ?>">
                        <div class="form-group col-md-3">
                          <label style="font-size: 12px; font-weight: 600;">Posição X</label>
                          <input type="number" class="form-control form-control-sm" name="posicao_data_x"
                            value="<?php echo (int) ($t['posicao_data_x'] ?? 0); ?>">
                        </div>
                        <div class="form-group col-md-3">
                          <label style="font-size: 12px; font-weight: 600;">Posição Y</label>
                          <input type="number" class="form-control form-control-sm" name="posicao_data_y"
                            value="<?php echo (int) ($t['posicao_data_y'] ?? 0); ?>">
                        </div>
                        <div class="form-group col-md-3">
                          <label style="font-size: 12px; font-weight: 600;">Fonte</label>
                          <select class="form-control form-control-sm update-preview" name="fonte_data" data-target="data"
                            data-prop="fontFamily">
                            <?php foreach ($fonts as $f): ?>
                              <option value="<?php echo $f; ?>" <?php echo ($t['fonte_data'] ?? 'Arial') === $f ? 'selected' : ''; ?>><?php echo $f; ?></option>
                            <?php endforeach; ?>
                          </select>
                        </div>
                        <div class="form-group col-md-3">
                          <label style="font-size: 12px; font-weight: 600;">Tamanho Fonte</label>
                          <input type="number" class="form-control form-control-sm update-preview"
                            name="tamanho_fonte_data" value="<?php echo (int) ($t['tamanho_fonte_data'] ?? 14); ?>"
                            data-target="data" data-prop="fontSize">
                        </div>
                        <div class="form-group col-md-3">
                          <label style="font-size: 12px; font-weight: 600;">Cor</label>
                          <input type="color" class="form-control form-control-sm update-preview" name="cor_data"
                            value="<?php echo htmlspecialchars($t['cor_data'] ?? '#000000'); ?>"
                            style="height: 31px; padding: 2px;" data-target="data" data-prop="color">
                        </div>
                      </div>
                    </div>

                    <!-- Campo Carga Horária -->
                    <div class="mb-3"
                      style="background: white; padding: 12px; border-radius: 8px; border: 1px solid #E0E0E0;">
                      <div class="d-flex align-items-center justify-content-between mb-2">
                        <div class="custom-control custom-switch">
                          <input type="checkbox" class="custom-control-input toggle-campo"
                            id="exibir_carga_horaria_<?php echo $t['id']; ?>" name="exibir_carga_horaria" value="1" <?php echo ($t['exibir_carga_horaria'] ?? 1) ? 'checked' : ''; ?> data-target="carga_horaria">
                          <label class="custom-control-label" for="exibir_carga_horaria_<?php echo $t['id']; ?>"
                            style="font-weight: 600; color: #1D1D1F;">Exibir Carga Horária</label>
                        </div>
                      </div>
                      <div class="form-row config-campo-carga_horaria"
                        style="<?php echo ($t['exibir_carga_horaria'] ?? 1) ? '' : 'display:none;'; ?>">
                        <div class="form-group col-md-3">
                          <label style="font-size: 12px; font-weight: 600;">Posição X</label>
                          <input type="number" class="form-control form-control-sm" name="posicao_carga_horaria_x"
                            value="<?php echo (int) ($t['posicao_carga_horaria_x'] ?? 0); ?>">
                        </div>
                        <div class="form-group col-md-3">
                          <label style="font-size: 12px; font-weight: 600;">Posição Y</label>
                          <input type="number" class="form-control form-control-sm" name="posicao_carga_horaria_y"
                            value="<?php echo (int) ($t['posicao_carga_horaria_y'] ?? 0); ?>">
                        </div>
                        <div class="form-group col-md-3">
                          <label style="font-size: 12px; font-weight: 600;">Fonte</label>
                          <select class="form-control form-control-sm update-preview" name="fonte_carga_horaria"
                            data-target="carga_horaria" data-prop="fontFamily">
                            <?php foreach ($fonts as $f): ?>
                              <option value="<?php echo $f; ?>" <?php echo ($t['fonte_carga_horaria'] ?? 'Arial') === $f ? 'selected' : ''; ?>><?php echo $f; ?></option>
                            <?php endforeach; ?>
                          </select>
                        </div>
                        <div class="form-group col-md-3">
                          <label style="font-size: 12px; font-weight: 600;">Tamanho Fonte</label>
                          <input type="number" class="form-control form-control-sm update-preview"
                            name="tamanho_fonte_carga_horaria"
                            value="<?php echo (int) ($t['tamanho_fonte_carga_horaria'] ?? 12); ?>"
                            data-target="carga_horaria" data-prop="fontSize">
                        </div>
                        <div class="form-group col-md-3">
                          <label style="font-size: 12px; font-weight: 600;">Cor</label>
                          <input type="color" class="form-control form-control-sm update-preview" name="cor_carga_horaria"
                            value="<?php echo htmlspecialchars($t['cor_carga_horaria'] ?? '#000000'); ?>"
                            style="height: 31px; padding: 2px;" data-target="carga_horaria" data-prop="color">
                        </div>
                      </div>
                    </div>

                    <!-- Campo Número do Certificado -->
                    <div class="mb-3"
                      style="background: white; padding: 12px; border-radius: 8px; border: 1px solid #E0E0E0;">
                      <div class="d-flex align-items-center justify-content-between mb-2">
                        <div class="custom-control custom-switch">
                          <input type="checkbox" class="custom-control-input toggle-campo"
                            id="exibir_numero_certificado_<?php echo $t['id']; ?>" name="exibir_numero_certificado"
                            value="1" <?php echo ($t['exibir_numero_certificado'] ?? 1) ? 'checked' : ''; ?>
                            data-target="numero_certificado">
                          <label class="custom-control-label" for="exibir_numero_certificado_<?php echo $t['id']; ?>"
                            style="font-weight: 600; color: #1D1D1F;">Exibir Número do Certificado</label>
                        </div>
                      </div>
                      <div class="form-row config-campo-numero_certificado"
                        style="<?php echo ($t['exibir_numero_certificado'] ?? 1) ? '' : 'display:none;'; ?>">
                        <div class="form-group col-md-3">
                          <label style="font-size: 12px; font-weight: 600;">Posição X</label>
                          <input type="number" class="form-control form-control-sm" name="posicao_numero_certificado_x"
                            value="<?php echo (int) ($t['posicao_numero_certificado_x'] ?? 0); ?>">
                        </div>
                        <div class="form-group col-md-3">
                          <label style="font-size: 12px; font-weight: 600;">Posição Y</label>
                          <input type="number" class="form-control form-control-sm" name="posicao_numero_certificado_y"
                            value="<?php echo (int) ($t['posicao_numero_certificado_y'] ?? 0); ?>">
                        </div>
                        <div class="form-group col-md-3">
                          <label style="font-size: 12px; font-weight: 600;">Fonte</label>
                          <select class="form-control form-control-sm update-preview" name="fonte_numero_certificado"
                            data-target="numero_certificado" data-prop="fontFamily">
                            <?php foreach ($fonts as $f): ?>
                              <option value="<?php echo $f; ?>" <?php echo ($t['fonte_numero_certificado'] ?? 'Arial') === $f ? 'selected' : ''; ?>><?php echo $f; ?></option>
                            <?php endforeach; ?>
                          </select>
                        </div>
                        <div class="form-group col-md-3">
                          <label style="font-size: 12px; font-weight: 600;">Tamanho Fonte</label>
                          <input type="number" class="form-control form-control-sm update-preview"
                            name="tamanho_fonte_numero_certificado"
                            value="<?php echo (int) ($t['tamanho_fonte_numero_certificado'] ?? 12); ?>"
                            data-target="numero_certificado" data-prop="fontSize">
                        </div>
                        <div class="form-group col-md-3">
                          <label style="font-size: 12px; font-weight: 600;">Cor</label>
                          <div class="d-flex align-items-center">
                            <input type="color" class="form-control form-control-sm update-preview color-picker-input"
                              name="cor_numero_certificado"
                              value="<?php echo htmlspecialchars($t['cor_numero_certificado'] ?? '#000000'); ?>"
                              style="height: 31px; padding: 2px; width: 40px; flex: 0 0 40px;"
                              data-target="numero_certificado" data-prop="color">
                            <input type="text" class="form-control form-control-sm color-text-input ml-1"
                              value="<?php echo htmlspecialchars($t['cor_numero_certificado'] ?? '#000000'); ?>"
                              maxlength="7" style="font-size: 12px; text-transform: uppercase;">
                          </div>
                        </div>
                      </div>
                    </div>

                    <!-- Campo QR Code -->
                    <div class="mb-3"
                      style="background: white; padding: 12px; border-radius: 8px; border: 1px solid #E0E0E0;">
                      <div class="d-flex align-items-center justify-content-between mb-2">
                        <div class="custom-control custom-switch">
                          <input type="checkbox" class="custom-control-input toggle-campo"
                            id="exibir_qrcode_<?php echo $t['id']; ?>" name="exibir_qrcode" value="1" <?php echo ($t['exibir_qrcode'] ?? 0) ? 'checked' : ''; ?> data-target="qrcode">
                          <label class="custom-control-label" for="exibir_qrcode_<?php echo $t['id']; ?>"
                            style="font-weight: 600; color: #1D1D1F;">Exibir QR Code de Validação</label>
                        </div>
                      </div>
                      <div class="form-row config-campo-qrcode"
                        style="<?php echo ($t['exibir_qrcode'] ?? 0) ? '' : 'display:none;'; ?>">
                        <div class="form-group col-md-4">
                          <label style="font-size: 12px; font-weight: 600;">Posição X</label>
                          <input type="number" class="form-control form-control-sm" name="posicao_qrcode_x"
                            value="<?php echo (int) ($t['posicao_qrcode_x'] ?? 0); ?>">
                        </div>
                        <div class="form-group col-md-4">
                          <label style="font-size: 12px; font-weight: 600;">Posição Y</label>
                          <input type="number" class="form-control form-control-sm" name="posicao_qrcode_y"
                            value="<?php echo (int) ($t['posicao_qrcode_y'] ?? 0); ?>">
                        </div>
                        <div class="form-group col-md-4">
                          <label style="font-size: 12px; font-weight: 600;">Tamanho (px)</label>
                          <input type="number" class="form-control form-control-sm update-preview" name="tamanho_qrcode"
                            value="<?php echo (int) ($t['tamanho_qrcode'] ?? 100); ?>" data-target="qrcode"
                            data-prop="size">
                        </div>
                      </div>
                    </div>

                  </div>
                  <!-- BotÃ£o Ajuste Visual -->
                  <div class="form-group">
                    <button type="button" class="btn btn-sm" data-toggle="collapse"
                      data-target="#preview-<?php echo (int) $t['id']; ?>" onclick="
    // Sincronizar inputs de cor
    $(document).on('input', '.color-picker-input', function() {
        var hex = $(this).val();
        $(this).siblings('.color-text-input').val(hex);
        // Disparar evento para atualizar preview
        $(this).trigger('change');
    });

    $(document).on('input', '.color-text-input', function() {
        var hex = $(this).val();
        if (/^#[0-9A-F]{6}$/i.test(hex)) {
            $(this).siblings('.color-picker-input').val(hex).trigger('change');
        }
    });

    // Inicializar Preview
    initPreview(<?php echo (int) $t['id']; ?>);"
                      style="background: linear-gradient(135deg, rgba(110, 65, 193, 0.1) 0%, rgba(110, 65, 193, 0.05) 100%); color: #6E41C1; border: 2px solid rgba(110, 65, 193, 0.3); border-radius: 10px; padding: 10px 20px; font-weight: 600; transition: all 0.2s ease; display: inline-flex; align-items: center; gap: 8px;"
                      onmouseover="this.style.background='#6E41C1'; this.style.color='white'; this.style.borderColor='#6E41C1'"
                      onmouseout="this.style.background='linear-gradient(135deg, rgba(110, 65, 193, 0.1) 0%, rgba(110, 65, 193, 0.05) 100%)'; this.style.color='#6E41C1'; this.style.borderColor='rgba(110, 65, 193, 0.3)'">
                      <span class="material-icons-outlined" style="font-size: 18px;">open_with</span>
                      Ajuste Visual (arraste os rÃ³tulos sobre a imagem)
                    </button>
                  </div>
                  <div id="preview-<?php echo (int) $t['id']; ?>" class="collapse">
                    <?php $isPdf = preg_match('/\.pdf($|\?)/i', parse_url($t['arquivo_url'] ?? '', PHP_URL_PATH) ?? '') === 1; ?>
                    <?php if (!$isPdf && !empty($t['arquivo_url'])): ?>
                      <!-- Toolbar de Visibilidade -->
                      <div class="d-flex align-items-center justify-content-center mb-3 p-2"
                        style="background: #f8f9fa; border-radius: 8px; border: 1px solid #e9ecef; gap: 15px;">
                        <span
                          style="font-size: 12px; font-weight: 600; color: #6c757d; text-transform: uppercase;">Exibir:</span>
                        <label class="custom-control custom-checkbox mb-0" style="cursor: pointer;">
                          <input type="checkbox" class="custom-control-input toolbar-toggle" data-target="nome" <?php echo ($t['exibir_nome'] ?? 1) ? 'checked' : ''; ?>>
                          <span class="custom-control-label" style="font-size: 13px; font-weight: 500;">Nome</span>
                        </label>
                        <label class="custom-control custom-checkbox mb-0" style="cursor: pointer;">
                          <input type="checkbox" class="custom-control-input toolbar-toggle" data-target="curso" <?php echo ($t['exibir_curso'] ?? 1) ? 'checked' : ''; ?>>
                          <span class="custom-control-label" style="font-size: 13px; font-weight: 500;">Curso</span>
                        </label>
                        <label class="custom-control custom-checkbox mb-0" style="cursor: pointer;">
                          <input type="checkbox" class="custom-control-input toolbar-toggle" data-target="data" <?php echo ($t['exibir_data'] ?? 1) ? 'checked' : ''; ?>>
                          <span class="custom-control-label" style="font-size: 13px; font-weight: 500;">Data</span>
                        </label>
                        <label class="custom-control custom-checkbox mb-0" style="cursor: pointer;">
                          <input type="checkbox" class="custom-control-input toolbar-toggle" data-target="carga_horaria"
                            <?php echo ($t['exibir_carga_horaria'] ?? 1) ? 'checked' : ''; ?>>
                          <span class="custom-control-label" style="font-size: 13px; font-weight: 500;">Carga Horária</span>
                        </label>
                        <label class="custom-control custom-checkbox mb-0" style="cursor: pointer;">
                          <input type="checkbox" class="custom-control-input toolbar-toggle"
                            data-target="numero_certificado" <?php echo ($t['exibir_numero_certificado'] ?? 1) ? 'checked' : ''; ?>>
                          <span class="custom-control-label" style="font-size: 13px; font-weight: 500;">Nº
                            Certificado</span>
                        </label>
                        <label class="custom-control custom-checkbox mb-0" style="cursor: pointer;">
                          <input type="checkbox" class="custom-control-input toolbar-toggle" data-target="qrcode" <?php echo ($t['exibir_qrcode'] ?? 0) ? 'checked' : ''; ?>>
                          <span class="custom-control-label" style="font-size: 13px; font-weight: 500;">QR Code</span>
                        </label>
                      </div>

                      <div class="position-preview" data-template-id="<?php echo (int) $t['id']; ?>">
                        <div class="canvas">
                          <img src="<?php echo htmlspecialchars($t['arquivo_url']); ?>" alt="Preview do template"
                            class="tpl-img" data-nome-x="<?php echo (int) ($t['posicao_nome_x'] ?? 0); ?>"
                            data-nome-y="<?php echo (int) ($t['posicao_nome_y'] ?? 0); ?>"
                            data-curso-x="<?php echo (int) ($t['posicao_curso_x'] ?? 0); ?>"
                            data-curso-y="<?php echo (int) ($t['posicao_curso_y'] ?? 0); ?>"
                            data-data-x="<?php echo (int) ($t['posicao_data_x'] ?? 0); ?>"
                            data-data-y="<?php echo (int) ($t['posicao_data_y'] ?? 0); ?>">
                          <div class="marker m-nome">Nome</div>
                          <div class="marker m-curso">Curso</div>
                          <div class="marker m-data">Data</div>
                          <div class="marker m-qrcode" data-target="qrcode">QR Code</div>

                          <!-- Marcadores para campos customizados -->
                          <?php if (!empty($t['campos_customizados'])): ?>
                            <?php foreach ($t['campos_customizados'] as $idx => $campo): ?>
                              <div class="marker m-custom" data-campo-idx="<?php echo $idx; ?>"
                                data-campo-x="<?php echo (int) ($campo['posicao_x'] ?? 0); ?>"
                                data-campo-y="<?php echo (int) ($campo['posicao_y'] ?? 0); ?>"
                                style="background-color: <?php echo htmlspecialchars($campo['cor_hex'] ?? '#0078d4'); ?>;">
                                <?php echo htmlspecialchars(substr($campo['label'], 0, 15)); ?>
                              </div>
                            <?php endforeach; ?>
                          <?php endif; ?>
                        </div>
                      </div>
                      <small class="text-muted d-block mt-2">Dica: arraste os rÃ³tulos (Nome/Curso/Data e campos
                        customizados). Ao salvar, as coordenadas serÃ£o atualizadas.</small>
                    <?php else: ?>
                      <div class="alert alert-warning mb-0">PrÃ©via visual indisponÃ­vel para PDF. Use os campos numÃ©ricos
                        acima ou envie um template em imagem (JPG/PNG).</div>
                    <?php endif; ?>
                  </div>

                  <!-- Dica -->
                  <div
                    style="background: linear-gradient(135deg, rgba(110, 65, 193, 0.05) 0%, rgba(110, 65, 193, 0.02) 100%); border: 2px solid rgba(110, 65, 193, 0.2); border-radius: 10px; padding: 12px 16px; margin-bottom: 20px; display: flex; align-items: center; gap: 12px;">
                    <span class="material-icons-outlined" style="font-size: 20px; color: #6E41C1;">info</span>
                    <span style="color: #1D1D1F; font-size: 13px;">
                      <strong style="color: #6E41C1;">Dica:</strong> Ajuste as coordenadas e gere um certificado de teste
                      para validar a posiÃ§Ã£o.
                    </span>
                  </div>

                  <!-- Campos Customizados -->
                  <div style="border-top: 2px solid #F5F5F7; margin: 24px 0; padding-top: 24px;">
                    <h6
                      style="color: #1D1D1F; font-weight: 700; font-size: 16px; margin-bottom: 16px; display: flex; align-items: center; gap: 8px;">
                      <span class="material-icons-outlined" style="font-size: 22px; color: #6E41C1;">add_box</span>
                      Campos Customizados
                    </h6>
                  </div>
                  <div class="campos-customizados-container" data-template-id="<?php echo (int) $t['id']; ?>">
                    <?php if (!empty($t['campos_customizados'])): ?>
                      <?php foreach ($t['campos_customizados'] as $idx => $campo): ?>
                        <div class="campo-customizado" data-campo-id="<?php echo (int) $campo['id']; ?>"
                          style="background: white; border: 2px solid #E5E5E7; border-radius: 12px; padding: 16px; margin-bottom: 12px; transition: all 0.2s ease;"
                          onmouseover="this.style.borderColor='#6E41C1'; this.style.boxShadow='0 4px 12px rgba(110, 65, 193, 0.1)'"
                          onmouseout="this.style.borderColor='#E5E5E7'; this.style.boxShadow='none'">
                          <div class="form-row">
                            <div class="form-group col-md-4 mb-2">
                              <label
                                style="font-weight: 600; color: #1D1D1F; font-size: 12px; margin-bottom: 6px;">Tipo</label>
                              <select class="form-control form-control-sm campo-tipo"
                                name="campos[<?php echo $idx; ?>][tipo_campo]"
                                style="border: 1px solid #E5E5E7; border-radius: 8px; padding: 8px 12px; font-size: 13px;">
                                <option value="texto" <?php echo ($campo['tipo_campo'] === 'texto') ? 'selected' : ''; ?>>Texto
                                  Fixo</option>
                                <option value="campo_dinamico" <?php echo ($campo['tipo_campo'] === 'campo_dinamico') ? 'selected' : ''; ?>>Campo DinÃ¢mico</option>
                              </select>
                            </div>
                            <div class="form-group col-md-4 mb-2">
                              <label
                                style="font-weight: 600; color: #1D1D1F; font-size: 12px; margin-bottom: 6px;">RÃ³tulo</label>
                              <input type="text" class="form-control form-control-sm campo-label"
                                name="campos[<?php echo $idx; ?>][label]"
                                value="<?php echo htmlspecialchars($campo['label']); ?>" placeholder="Ex: Assinatura, Selo"
                                style="border: 1px solid #E5E5E7; border-radius: 8px; padding: 8px 12px; font-size: 13px;">
                            </div>
                            <div class="form-group col-md-4 mb-2">
                              <label
                                style="font-weight: 600; color: #1D1D1F; font-size: 12px; margin-bottom: 6px;">Fonte</label>
                              <select class="form-control form-control-sm" name="campos[<?php echo $idx; ?>][fonte]">
                                <option value="Arial" <?php echo ($campo['fonte'] ?? 'Arial') === 'Arial' ? 'selected' : ''; ?>>
                                  Arial</option>
                                <option value="Times" <?php echo ($campo['fonte'] ?? 'Arial') === 'Times' ? 'selected' : ''; ?>>
                                  Times New Roman</option>
                                <option value="Courier" <?php echo ($campo['fonte'] ?? 'Arial') === 'Courier' ? 'selected' : ''; ?>>Courier New</option>
                                <option value="Ceviche One" <?php echo ($campo['fonte'] ?? 'Arial') === 'Ceviche One' ? 'selected' : ''; ?>>Ceviche One</option>
                                <option value="Roboto-Regular" <?php echo ($campo['fonte'] ?? 'Arial') === 'Roboto-Regular' ? 'selected' : ''; ?>>Roboto</option>
                                <option value="Lato-Regular" <?php echo ($campo['fonte'] ?? 'Arial') === 'Lato-Regular' ? 'selected' : ''; ?>>Lato</option>
                                <option value="Ubuntu-Regular" <?php echo ($campo['fonte'] ?? 'Arial') === 'Ubuntu-Regular' ? 'selected' : ''; ?>>Ubuntu</option>
                                <option value="Merriweather-Regular" <?php echo ($campo['fonte'] ?? 'Arial') === 'Merriweather-Regular' ? 'selected' : ''; ?>>Merriweather</option>
                                <option value="Poppins-Regular" <?php echo ($campo['fonte'] ?? 'Arial') === 'Poppins-Regular' ? 'selected' : ''; ?>>Poppins</option>
                                <option value="PTSans-Regular" <?php echo ($campo['fonte'] ?? 'Arial') === 'PTSans-Regular' ? 'selected' : ''; ?>>PT Sans</option>
                                <option value="Anton-Regular" <?php echo ($campo['fonte'] ?? 'Arial') === 'Anton-Regular' ? 'selected' : ''; ?>>Anton</option>
                                <option value="Lobster-Regular" <?php echo ($campo['fonte'] ?? 'Arial') === 'Lobster-Regular' ? 'selected' : ''; ?>>Lobster</option>
                                <option value="Pacifico-Regular" <?php echo ($campo['fonte'] ?? 'Arial') === 'Pacifico-Regular' ? 'selected' : ''; ?>>Pacifico</option>
                                <option value="IndieFlower-Regular" <?php echo ($campo['fonte'] ?? 'Arial') === 'IndieFlower-Regular' ? 'selected' : ''; ?>>Indie Flower</option>
                                <option value="ShadowsIntoLight-Regular" <?php echo ($campo['fonte'] ?? 'Arial') === 'ShadowsIntoLight-Regular' ? 'selected' : ''; ?>>Shadows Into Light</option>
                                <option value="AmaticSC-Regular" <?php echo ($campo['fonte'] ?? 'Arial') === 'AmaticSC-Regular' ? 'selected' : ''; ?>>Amatic SC</option>
                                <option value="AbrilFatface-Regular" <?php echo ($campo['fonte'] ?? 'Arial') === 'AbrilFatface-Regular' ? 'selected' : ''; ?>>Abril Fatface</option>
                                <option value="BebasNeue-Regular" <?php echo ($campo['fonte'] ?? 'Arial') === 'BebasNeue-Regular' ? 'selected' : ''; ?>>Bebas Neue</option>
                                <option value="Creepster-Regular" <?php echo ($campo['fonte'] ?? 'Arial') === 'Creepster-Regular' ? 'selected' : ''; ?>>Creepster</option>
                                <option value="Bangers-Regular" <?php echo ($campo['fonte'] ?? 'Arial') === 'Bangers-Regular' ? 'selected' : ''; ?>>Bangers</option>
                                <option value="Satisfy-Regular" <?php echo ($campo['fonte'] ?? 'Arial') === 'Satisfy-Regular' ? 'selected' : ''; ?>>Satisfy</option>
                                <option value="GreatVibes-Regular" <?php echo ($campo['fonte'] ?? 'Arial') === 'GreatVibes-Regular' ? 'selected' : ''; ?>>Great Vibes</option>
                              </select>
                            </div>
                            <div class="form-group col-md-4 mb-2">
                              <label style="font-weight: 600; color: #1D1D1F; font-size: 12px; margin-bottom: 6px;">Tamanho
                                Fonte</label>
                              <input type="number" class="form-control form-control-sm"
                                name="campos[<?php echo $idx; ?>][tamanho_fonte]"
                                value="<?php echo (int) ($campo['tamanho_fonte'] ?? 16); ?>" min="8" max="72"
                                style="border: 1px solid #E5E5E7; border-radius: 8px; padding: 8px 12px; font-size: 13px;">
                            </div>
                            <div class="form-group col-md-12 mb-2">
                              <div class="custom-control custom-switch">
                                <input type="checkbox" class="custom-control-input toggle-campo-custom"
                                  id="exibir_campo_<?php echo $idx; ?>_<?php echo $t['id']; ?>"
                                  name="campos[<?php echo $idx; ?>][exibir]" value="1" <?php echo ($campo['exibir'] ?? 1) ? 'checked' : ''; ?> data-campo-idx="<?php echo $idx; ?>"
                                  data-template-id="<?php echo $t['id']; ?>">
                                <label class="custom-control-label"
                                  for="exibir_campo_<?php echo $idx; ?>_<?php echo $t['id']; ?>"
                                  style="font-size: 12px;">Exibir este campo</label>
                              </div>
                            </div>
                          </div>
                          <div class="form-row">
                            <div class="form-group col-md-3 mb-2">
                              <label style="font-weight: 600; color: #1D1D1F; font-size: 12px; margin-bottom: 6px;">PosiÃ§Ã£o
                                X</label>
                              <input type="number" class="form-control form-control-sm"
                                name="campos[<?php echo $idx; ?>][posicao_x]"
                                value="<?php echo (int) ($campo['posicao_x'] ?? 0); ?>" min="0"
                                style="border: 1px solid #E5E5E7; border-radius: 8px; padding: 8px 12px; font-size: 13px;">
                            </div>
                            <div class="form-group col-md-3 mb-2">
                              <label style="font-weight: 600; color: #1D1D1F; font-size: 12px; margin-bottom: 6px;">PosiÃ§Ã£o
                                Y</label>
                              <input type="number" class="form-control form-control-sm"
                                name="campos[<?php echo $idx; ?>][posicao_y]"
                                value="<?php echo (int) ($campo['posicao_y'] ?? 0); ?>" min="0"
                                style="border: 1px solid #E5E5E7; border-radius: 8px; padding: 8px 12px; font-size: 13px;">
                            </div>
                            <div class="form-group col-md-3 mb-2">
                              <label
                                style="font-weight: 600; color: #1D1D1F; font-size: 12px; margin-bottom: 6px;">Cor</label>
                              <input type="color" class="form-control form-control-sm"
                                name="campos[<?php echo $idx; ?>][cor_hex]"
                                value="<?php echo htmlspecialchars($campo['cor_hex'] ?? '#000000'); ?>"
                                style="border: 1px solid #E5E5E7; border-radius: 8px; padding: 4px; height: 38px;">
                            </div>
                            <div class="form-group col-md-3 mb-2">
                              <label
                                style="font-weight: 600; color: #1D1D1F; font-size: 12px; margin-bottom: 6px;">Ordem</label>
                              <input type="number" class="form-control form-control-sm"
                                name="campos[<?php echo $idx; ?>][ordem]" value="<?php echo (int) ($campo['ordem'] ?? 0); ?>"
                                min="0"
                                style="border: 1px solid #E5E5E7; border-radius: 8px; padding: 8px 12px; font-size: 13px;">
                            </div>
                          </div>
                          <div class="form-group mb-2">
                            <label style="font-weight: 600; color: #1D1D1F; font-size: 12px; margin-bottom: 6px;">Valor
                              PadrÃ£o
                              / ConteÃºdo</label>
                            <textarea class="form-control form-control-sm" name="campos[<?php echo $idx; ?>][valor_padrao]"
                              rows="2" placeholder="Texto que aparecerÃ¡ no certificado"
                              style="border: 1px solid #E5E5E7; border-radius: 8px; padding: 8px 12px; font-size: 13px;"><?php echo htmlspecialchars($campo['valor_padrao'] ?? ''); ?></textarea>
                          </div>
                          <div style="text-align: right;">
                            <button type="button" class="btn btn-sm btn-remover-campo"
                              style="background: linear-gradient(135deg, rgba(255, 59, 48, 0.1) 0%, rgba(255, 59, 48, 0.05) 100%); color: #FF3B30; border: 1px solid rgba(255, 59, 48, 0.3); border-radius: 8px; padding: 6px 14px; font-weight: 600; font-size: 12px; transition: all 0.2s ease; display: inline-flex; align-items: center; gap: 6px;"
                              onmouseover="this.style.background='#FF3B30'; this.style.color='white'; this.style.borderColor='#FF3B30'"
                              onmouseout="this.style.background='linear-gradient(135deg, rgba(255, 59, 48, 0.1) 0%, rgba(255, 59, 48, 0.05) 100%)'; this.style.color='#FF3B30'; this.style.borderColor='rgba(255, 59, 48, 0.3)'">
                              <span class="material-icons-outlined" style="font-size: 16px;">delete</span>
                              Remover
                            </button>
                          </div>
                        </div>
                      <?php endforeach; ?>
                    <?php endif; ?>
                  </div>
                  <button type="button" class="btn btn-sm btn-adicionar-campo"
                    data-template-id="<?php echo (int) $t['id']; ?>"
                    style="background: linear-gradient(135deg, rgba(110, 65, 193, 0.1) 0%, rgba(110, 65, 193, 0.05) 100%); color: #6E41C1; border: 2px dashed rgba(110, 65, 193, 0.3); border-radius: 10px; padding: 10px 20px; font-weight: 600; transition: all 0.2s ease; display: inline-flex; align-items: center; gap: 8px; width: 100%;"
                    onmouseover="this.style.background='rgba(110, 65, 193, 0.05)'; this.style.borderColor='#6E41C1'"
                    onmouseout="this.style.background='linear-gradient(135deg, rgba(110, 65, 193, 0.1) 0%, rgba(110, 65, 193, 0.05) 100%)'; this.style.borderColor='rgba(110, 65, 193, 0.3)'">
                    <span class="material-icons-outlined" style="font-size: 18px;">add_circle</span>
                    Adicionar Campo Customizado
                  </button>

                  <!-- Upload de Arquivo do Verso - OPCIONAL -->
                  <div style="border-top: 2px solid #F5F5F7; margin: 24px 0; padding-top: 24px;">
                    <h6
                      style="color: #1D1D1F; font-weight: 700; font-size: 16px; margin-bottom: 16px; display: flex; align-items: center; gap: 8px;">
                      <span class="material-icons-outlined" style="font-size: 22px; color: #6E41C1;">flip_to_back</span>
                      Atualizar Verso do Certificado (Opcional)
                    </h6>
                    <div class="form-group">
                      <label for="arquivo_verso_<?php echo (int) $t['id']; ?>"
                        style="font-weight: 600; color: #1D1D1F; font-size: 14px; margin-bottom: 8px; display: flex; align-items: center; gap: 6px;">
                        <span class="material-icons-outlined" style="font-size: 18px; color: #6E41C1;">upload_file</span>
                        Novo Arquivo do Verso
                      </label>
                      <div
                        style="border: 2px dashed #E5E5E7; border-radius: 12px; padding: 20px; text-align: center; background: #F5F5F7; transition: all 0.2s ease; cursor: pointer;"
                        onmouseover="this.style.borderColor='#6E41C1'; this.style.background='rgba(110, 65, 193, 0.02)'"
                        onmouseout="this.style.borderColor='#E5E5E7'; this.style.background='#F5F5F7'"
                        onclick="document.getElementById('arquivo_verso_<?php echo (int) $t['id']; ?>').click()">
                        <span class="material-icons-outlined"
                          style="font-size: 40px; color: #86868B; margin-bottom: 8px;">cloud_upload</span>
                        <p style="color: #1D1D1F; font-weight: 600; margin: 0 0 4px 0; font-size: 13px;">Clique para
                          selecionar novo arquivo do verso</p>
                        <small style="color: #86868B; font-size: 12px;">Formatos aceitos: PDF, JPG, PNG (mÃ¡x. 10MB) -
                          Deixe em branco para manter o atual</small>
                        <input type="file" class="form-control-file" id="arquivo_verso_<?php echo (int) $t['id']; ?>"
                          name="arquivo_verso" accept=".pdf,.jpg,.jpeg,.png" style="display: none;">
                      </div>
                      <div id="arquivo-verso-nome-<?php echo (int) $t['id']; ?>"
                        style="margin-top: 8px; color: #6E41C1; font-size: 13px; font-weight: 600; display: none;"></div>
                      <?php if (!empty($t['arquivo_verso_url'])): ?>
                        <div
                          style="margin-top: 12px; padding: 12px; background: rgba(110, 65, 193, 0.05); border-radius: 8px; border: 1px solid rgba(110, 65, 193, 0.2);">
                          <small style="color: #6E41C1; font-weight: 600; display: flex; align-items: center; gap: 6px;">
                            <span class="material-icons-outlined" style="font-size: 16px;">check_circle</span>
                            Verso atual: <a href="<?php echo htmlspecialchars($t['arquivo_verso_url']); ?>" target="_blank"
                              style="color: #6E41C1; text-decoration: underline;">Visualizar</a>
                          </small>
                        </div>
                      <?php else: ?>
                        <div
                          style="margin-top: 12px; padding: 12px; background: rgba(255, 149, 0, 0.05); border-radius: 8px; border: 1px solid rgba(255, 149, 0, 0.2);">
                          <small style="color: #FF9500; font-weight: 600; display: flex; align-items: center; gap: 6px;">
                            <span class="material-icons-outlined" style="font-size: 16px;">info</span>
                            Nenhum verso configurado. Os certificados terÃ£o apenas a frente.
                          </small>
                        </div>
                      <?php endif; ?>
                    </div>
                  </div>
                </div>
                <div class="modal-footer"
                  style="background: #F5F5F7; border-radius: 0 0 16px 16px; padding: 20px 28px; border-top: 1px solid #E5E5E7;">
                  <button type="button" class="btn" data-dismiss="modal"
                    style="background: white; color: #86868B; border: 2px solid #E5E5E7; border-radius: 10px; padding: 10px 24px; font-weight: 600; transition: all 0.2s ease;"
                    onmouseover="this.style.borderColor='#6E41C1'; this.style.color='#6E41C1'"
                    onmouseout="this.style.borderColor='#E5E5E7'; this.style.color='#86868B'">
                    Cancelar
                  </button>
                  <button type="submit" class="btn"
                    style="background: linear-gradient(135deg, #6E41C1 0%, #8B5FD6 100%); color: white; border: none; border-radius: 10px; padding: 10px 24px; font-weight: 600; box-shadow: 0 4px 12px rgba(110, 65, 193, 0.3); transition: all 0.3s ease;"
                    onmouseover="this.style.transform='translateY(-2px)'; this.style.boxShadow='0 6px 20px rgba(110, 65, 193, 0.4)'"
                    onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 4px 12px rgba(110, 65, 193, 0.3)'">
                    <span class="material-icons-outlined"
                      style="font-size: 18px; vertical-align: middle; margin-right: 4px;">save</span>
                    Salvar AlteraÃ§Ãµes
                  </button>
                </div>
              </form>
            </div>
          </div>
        </div>
      <?php endforeach; ?>

      <!-- Modal Novo Template -->
      <div class="modal fade" id="novoTemplate" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">
          <div class="modal-content">
            <div class="modal-header"
              style="background: linear-gradient(135deg, #6E41C1 0%, #8B5FD6 100%); border-radius: 16px 16px 0 0; padding: 24px 28px; border: none;">
              <div style="display: flex; align-items: center; gap: 12px;">
                <div
                  style="width: 44px; height: 44px; background: rgba(255, 255, 255, 0.2); border-radius: 12px; display: flex; align-items: center; justify-content: center;">
                  <span class="material-icons-outlined" style="font-size: 24px; color: white;">add_circle</span>
                </div>
                <h5 class="modal-title" style="color: white; font-weight: 700; font-size: 20px; margin: 0;">Novo
                  Template de Certificado</h5>
              </div>
              <button type="button" class="close" data-dismiss="modal" aria-label="Close"
                style="color: white; opacity: 1; font-size: 28px; font-weight: 300; text-shadow: none; transition: all 0.2s ease;"
                onmouseover="this.style.transform='rotate(90deg)'" onmouseout="this.style.transform='rotate(0deg)'">
                <span aria-hidden="true">&times;</span>
              </button>
            </div>
            <style>
              .position-preview {
                position: relative;
                border: 1px dashed #ddd;
                background: #f8f9fa;
                padding: 8px;
                overflow: auto;
              }

              .position-preview .canvas {
                position: relative;
                display: inline-block;
              }

              .position-preview img.tpl-img {
                max-width: 100%;
                height: auto;
                display: block;
              }

              .position-preview .marker {
                position: absolute;
                background: rgba(0, 123, 255, 0.85);
                color: #fff;
                padding: 2px 6px;
                border-radius: 3px;
                cursor: move;
                font-size: 12px;
                line-height: 1.2;
                user-select: none;
                z-index: 100;
              }

              .marker.m-nome {
                background-color: #007bff;
                color: white;
              }

              .marker.m-curso {
                background-color: #28a745;
                color: white;
              }

              .marker.m-data {
                background-color: #ff9800;
                color: white;
              }

              .marker.m-carga_horaria {
                background-color: #9c27b0;
                color: white;
              }

              .marker.m-numero_certificado {
                background-color: #607d8b;
                color: white;
              }

              .marker.m-qrcode {
                background-color: #000000;
                color: white;
                display: flex;
                align-items: center;
                justify-content: center;
                opacity: 0.8;
              }

              .marker.m-custom {
                background-color: #0078d4;
                color: white;
              }
            </style>
            <script>
              (function () {
                // Estado global de drag
                let currentDrag = null;

                // Listeners globais para drag (apenas uma vez)
                const onMouseMove = (e) => {
                  if (!currentDrag) return;
                  const img = currentDrag.img;
                  if (!img) return;

                  e.preventDefault(); // Evitar seleção de texto

                  const imgRect = img.getBoundingClientRect();
                  // Calcular centro relativo à imagem
                  let x = e.clientX - imgRect.left - currentDrag.offX + currentDrag.el.offsetWidth / 2;
                  let y = e.clientY - imgRect.top - currentDrag.offY + currentDrag.el.offsetHeight / 2;

                  // Limitar à área da imagem
                  x = Math.max(0, Math.min(x, imgRect.width));
                  y = Math.max(0, Math.min(y, imgRect.height));

                  if (currentDrag.setPos) {
                    currentDrag.setPos(currentDrag.el, x, y);
                  }
                };

                const onMouseUp = () => {
                  if (!currentDrag) return;
                  if (currentDrag.persist) {
                    currentDrag.persist();
                  }
                  currentDrag = null;
                };

                document.addEventListener('mousemove', onMouseMove);
                document.addEventListener('mouseup', onMouseUp);

                function initPreview(preview) {
                  if (preview.dataset.initialized) return;
                  preview.dataset.initialized = "true";

                  console.log('DEBUG: initPreview called for preview', preview);
                  const modal = preview.closest('.modal');
                  const img = preview.querySelector('img.tpl-img');
                  if (!img) {
                    console.error('DEBUG: Image not found in initPreview');
                    return;
                  }
                  const markers = {
                    nome: preview.querySelector('.marker.m-nome'),
                    curso: preview.querySelector('.marker.m-curso'),
                    data: preview.querySelector('.marker.m-data'),
                    carga_horaria: preview.querySelector('.marker.m-carga_horaria'),
                    numero_certificado: preview.querySelector('.marker.m-numero_certificado'),
                    qrcode: preview.querySelector('.marker.m-qrcode')
                  };

                  function qn(name) { return modal.querySelector('[name="' + name + '"]'); }
                  function nums(el) { const v = parseInt((el && el.value) || '0', 10); return isNaN(v) ? 0 : v; }

                  function setPos(el, x, y) {
                    if (!el) return;
                    el.style.left = (x - el.offsetWidth / 2) + "px";
                    el.style.top = (y - el.offsetHeight / 2) + "px";
                  }

                  function placeMarkers() {
                    if (!img.complete || img.naturalHeight === 0) {
                      setTimeout(placeMarkers, 100);
                      return;
                    }
                    const natW = img.naturalWidth || 2048, natH = img.naturalHeight || 1152;
                    const rect = img.getBoundingClientRect();
                    const dispW = rect.width, dispH = rect.height;

                    if (dispW === 0 || dispH === 0) return;

                    const sx = dispW / natW, sy = dispH / natH;
                    const nomeX = nums(qn('posicao_nome_x'));
                    const nomeY = nums(qn('posicao_nome_y')) || Math.round(natH * 0.40);
                    const cursoX = nums(qn('posicao_curso_x'));
                    const cursoY = nums(qn('posicao_curso_y')) || Math.round(natH * 0.55);
                    const dataX = nums(qn('posicao_data_x'));
                    const dataY = nums(qn('posicao_data_y')) || Math.round(natH * 0.72);
                    const cargaX = nums(qn('posicao_carga_horaria_x'));
                    const cargaY = nums(qn('posicao_carga_horaria_y')) || Math.round(natH * 0.80);
                    const numX = nums(qn('posicao_numero_certificado_x'));
                    const numY = nums(qn('posicao_numero_certificado_y')) || Math.round(natH * 0.88);
                    const qrX = nums(qn('posicao_qrcode_x'));
                    const qrY = nums(qn('posicao_qrcode_y')) || Math.round(natH * 0.80);
                    const qrSize = nums(qn('tamanho_qrcode')) || 100;

                    const cx = (x) => Math.round(x * sx), cy = (y) => Math.round(y * sy);
                    const centerX = Math.round(dispW / 2);

                    setPos(markers.nome, nomeX > 0 ? cx(nomeX) : centerX, cy(nomeY));
                    setPos(markers.curso, cursoX > 0 ? cx(cursoX) : centerX, cy(cursoY));
                    setPos(markers.data, dataX > 0 ? cx(dataX) : centerX, cy(dataY));
                    setPos(markers.carga_horaria, cargaX > 0 ? cx(cargaX) : centerX, cy(cargaY));
                    setPos(markers.numero_certificado, numX > 0 ? cx(numX) : centerX, cy(numY));

                    // QR Code positioning and sizing
                    if (markers.qrcode) {
                      const scaledQrSize = Math.round(qrSize * sx);
                      markers.qrcode.style.width = scaledQrSize + 'px';
                      markers.qrcode.style.height = scaledQrSize + 'px';
                      // Adjust font size for visibility
                      markers.qrcode.style.fontSize = Math.max(10, scaledQrSize / 5) + 'px';
                      setPos(markers.qrcode, qrX > 0 ? cx(qrX) : (dispW - scaledQrSize - 20), qrY > 0 ? cy(qrY) : (dispH - scaledQrSize - 20));
                    }

                    // Posicionar campos customizados
                    preview.querySelectorAll('.marker.m-custom').forEach(el => {
                      const idx = el.getAttribute('data-campo-idx');
                      const customX = nums(modal.querySelector(`[name="campos[${idx}][posicao_x]"]`));
                      const customY = nums(modal.querySelector(`[name="campos[${idx}][posicao_y]"]`));
                      setPos(el, customX > 0 ? cx(customX) : centerX, cy(customY));
                    });
                  }

                  function persist(el, field) {
                    const imgRect = img.getBoundingClientRect();
                    const natW = img.naturalWidth || 2048, natH = img.naturalHeight || 1152;
                    const sx = natW / imgRect.width, sy = natH / imgRect.height;
                    const left = parseFloat(el.style.left || '0') + el.offsetWidth / 2;
                    const top = parseFloat(el.style.top || '0') + el.offsetHeight / 2;
                    const nx = Math.round(left * sx);
                    const ny = Math.round(top * sy);

                    const map = {
                      nome: { x: 'posicao_nome_x', y: 'posicao_nome_y' },
                      curso: { x: 'posicao_curso_x', y: 'posicao_curso_y' },
                      data: { x: 'posicao_data_x', y: 'posicao_data_y' },
                      carga_horaria: { x: 'posicao_carga_horaria_x', y: 'posicao_carga_horaria_y' },
                      numero_certificado: { x: 'posicao_numero_certificado_x', y: 'posicao_numero_certificado_y' },
                      qrcode: { x: 'posicao_qrcode_x', y: 'posicao_qrcode_y' }
                    };
                    const fx = qn(map[field].x), fy = qn(map[field].y);
                    if (fx) fx.value = nx; if (fy) fy.value = ny;
                  }

                  function persistCustom(el, idx) {
                    const imgRect = img.getBoundingClientRect();
                    const natW = img.naturalWidth || 2048, natH = img.naturalHeight || 1152;
                    const sx = natW / imgRect.width, sy = natH / imgRect.height;
                    const left = parseFloat(el.style.left || '0') + el.offsetWidth / 2;
                    const top = parseFloat(el.style.top || '0') + el.offsetHeight / 2;
                    const nx = Math.round(left * sx);
                    const ny = Math.round(top * sy);
                    const fx = modal.querySelector(`[name="campos[${idx}][posicao_x]"]`);
                    const fy = modal.querySelector(`[name="campos[${idx}][posicao_y]"]`);
                    if (fx) fx.value = nx; if (fy) fy.value = ny;
                  }

                  const attachDrag = (el, field, img) => {
                    if (!el) return;

                    el.style.cursor = 'move';
                    el.style.pointerEvents = 'auto';

                    el.addEventListener('mousedown', (e) => {
                      currentDrag = {
                        el: el,
                        field: field,
                        img: img,
                        setPos: setPos,
                        persist: () => {
                          if (field) persist(el, field);
                          else {
                            const idx = el.getAttribute('data-campo-idx');
                            persistCustom(el, idx);
                          }
                        },
                        offX: e.clientX - el.getBoundingClientRect().left,
                        offY: e.clientY - el.getBoundingClientRect().top
                      };
                      e.preventDefault();
                      e.stopPropagation();
                    });
                  };

                  img.addEventListener('load', placeMarkers);

                  const collapse = preview.closest('.collapse');
                  if (collapse) {
                    if (typeof $ !== 'undefined') {
                      $(collapse).on('shown.bs.collapse', placeMarkers);
                    } else {
                      collapse.addEventListener('shown.bs.collapse', placeMarkers);
                    }
                  }

                  window.addEventListener('resize', placeMarkers);

                  attachDrag(markers.nome, 'nome', img);
                  attachDrag(markers.curso, 'curso', img);
                  attachDrag(markers.data, 'data', img);
                  attachDrag(markers.carga_horaria, 'carga_horaria', img);
                  attachDrag(markers.numero_certificado, 'numero_certificado', img);
                  attachDrag(markers.qrcode, 'qrcode', img);

                  preview.querySelectorAll('.marker.m-custom').forEach(el => {
                    const idx = el.getAttribute('data-campo-idx');
                    attachDrag(el, null, img);
                  });

                  // Shared visibility logic
                  function setVisibility(target, visible) {
                    const marker = markers[target];
                    const configRow = modal.querySelector('.config-campo-' + target);
                    const formToggle = modal.querySelector(`.toggle-campo[data-target="${target}"]`);
                    const toolbarToggle = modal.querySelector(`.toolbar-toggle[data-target="${target}"]`);

                    if (marker) marker.style.display = visible ? 'block' : 'none';
                    if (configRow) configRow.style.display = visible ? 'flex' : 'none';

                    if (formToggle && formToggle.checked !== visible) formToggle.checked = visible;
                    if (toolbarToggle && toolbarToggle.checked !== visible) toolbarToggle.checked = visible;
                  }

                  modal.querySelectorAll('.toggle-campo, .toolbar-toggle').forEach(toggle => {
                    toggle.addEventListener('change', function () {
                      const target = this.getAttribute('data-target');
                      setVisibility(target, this.checked);
                    });
                  });

                  modal.querySelectorAll('.toggle-campo-custom').forEach(toggle => {
                    toggle.addEventListener('change', function () {
                      const idx = this.getAttribute('data-campo-idx');
                      const marker = preview.querySelector(`.marker.m-custom[data-campo-idx="${idx}"]`);
                      if (marker) {
                        marker.style.display = this.checked ? 'block' : 'none';
                      }
                    });
                  });

                  modal.querySelectorAll('.toggle-campo').forEach(toggle => {
                    const target = toggle.getAttribute('data-target');
                    setVisibility(target, toggle.checked);
                  });

                  modal.querySelectorAll('.update-preview').forEach(input => {
                    const handler = function () {
                      const target = this.getAttribute('data-target');
                      const prop = this.getAttribute('data-prop');
                      const marker = markers[target];
                      if (!marker) return;

                      if (prop === 'fontSize') {
                        const imgRect = img.getBoundingClientRect();
                        const natH = img.naturalHeight || 1152;
                        // Evitar divisão por zero
                        if (natH > 0) {
                          const sy = imgRect.height / natH;
                          // Aplicar escala, mas garantir um mínimo visível
                          const scaledSize = Math.max(10, this.value * sy);
                          marker.style.fontSize = scaledSize + 'px';
                        }
                      } else if (prop === 'size') {
                        // Handle QR Code resizing
                        const imgRect = img.getBoundingClientRect();
                        const natW = img.naturalWidth || 2048;
                        if (natW > 0) {
                          const sx = imgRect.width / natW;
                          const scaledSize = Math.round(this.value * sx);
                          marker.style.width = scaledSize + 'px';
                          marker.style.height = scaledSize + 'px';
                          marker.style.fontSize = Math.max(10, scaledSize / 5) + 'px';
                        }
                      } else if (prop === 'color') {
                        // Para marcadores padrão, a cor de fundo identifica o campo. 
                        // A cor do texto pode ser alterada se desejado, mas aqui mantemos o padrão de background color para identificação.
                        // Se quiser mudar a cor do TEXTO do marcador: marker.style.color = this.value;
                        // Se quiser mudar o background (como estava): marker.style.backgroundColor = this.value;
                        // O código original mudava o background. Vamos manter, mas talvez fosse melhor mudar a cor da fonte para simular o PDF.
                        // Vamos mudar a cor da fonte para ficar mais fiel ao resultado final, e manter um background translúcido.
                        marker.style.color = this.value;
                        marker.style.backgroundColor = 'rgba(255, 255, 255, 0.7)';
                        marker.style.border = '1px solid ' + this.value;
                      } else if (prop === 'fontFamily') {
                        marker.style.fontFamily = this.value;
                      }
                    };
                    input.addEventListener('input', handler);
                    input.addEventListener('change', handler);
                    // Delay inicial para garantir que a imagem carregou
                    setTimeout(() => handler.call(input), 500);
                  });

                  window.addEventListener('resize', () => {
                    modal.querySelectorAll('.update-preview[data-prop="fontSize"]').forEach(input => {
                      input.dispatchEvent(new Event('change'));
                    });
                  });

                  modal.addEventListener('customFieldAdded', (e) => {
                    const newMarker = e.detail.marker;
                    const idx = e.detail.idx;
                    attachDrag(newMarker, null, img);

                    const container = modal.querySelector(`.campos-customizados-container`);
                    const novoCampoDiv = container.querySelectorAll('.campo-customizado')[idx];

                    if (novoCampoDiv) {
                      const labelInput = novoCampoDiv.querySelector('.campo-label');
                      if (labelInput) {
                        labelInput.addEventListener('input', function () {
                          newMarker.textContent = this.value || 'Novo Campo';
                        });
                      }
                      const colorInput = novoCampoDiv.querySelector('input[type="color"]');
                      if (colorInput) {
                        colorInput.addEventListener('input', function () {
                          newMarker.style.backgroundColor = this.value;
                        });
                      }
                      const toggle = novoCampoDiv.querySelector('.toggle-campo-custom');
                      if (toggle) {
                        toggle.addEventListener('change', function () {
                          newMarker.style.display = this.checked ? 'block' : 'none';
                        });
                      }
                    }
                  });

                  // Executar posicionamento inicial
                  placeMarkers();
                }

                // Inicialização robusta
                function initialize() {
                  if (typeof $ !== 'undefined') {
                    $(document).on('shown.bs.modal', '.modal', function () {
                      const preview = this.querySelector('.position-preview');
                      if (preview) initPreview(preview);
                    });
                    // Fallback para modais já abertos
                    $('.modal.show').each(function () {
                      const preview = this.querySelector('.position-preview');
                      if (preview) initPreview(preview);
                    });
                  } else {
                    document.querySelectorAll('.modal').forEach(modal => {
                      modal.addEventListener('shown.bs.modal', function () {
                        const preview = this.querySelector('.position-preview');
                        if (preview) initPreview(preview);
                      });
                    });
                  }
                }

                if (document.readyState === 'loading') {
                  document.addEventListener('DOMContentLoaded', initialize);
                } else {
                  initialize();
                }
              })();
            </script>



            <script>
              // Gerenciar campos customizados
              (function () {
                function initCamposCustomizados() {
                  console.log('DEBUG: initCamposCustomizados running on load');
                  // BotÃ£o adicionar campo
                  const btns = document.querySelectorAll('.btn-adicionar-campo');
                  console.log('DEBUG: Found add buttons:', btns.length);
                  btns.forEach(btn => {
                    btn.addEventListener('click', function () {
                      const templateId = this.getAttribute('data-template-id');
                      const container =
                        document.querySelector(`.campos-customizados-container[data-template-id="${templateId}"]`);
                      if (!container) return;

                      const campos = container.querySelectorAll('.campo-customizado');
                      const novoIdx = campos.length;

                      const novoHtml = `
            <div class="campo-customizado"
              style="background: white; border: 2px solid #E5E5E7; border-radius: 12px; padding: 16px; margin-bottom: 12px; transition: all 0.2s ease;"
              onmouseover="this.style.borderColor='#6E41C1'; this.style.boxShadow='0 4px 12px rgba(110, 65, 193, 0.1)'"
              onmouseout="this.style.borderColor='#E5E5E7'; this.style.boxShadow='none'">
              <div class="form-row">
                <div class="form-group col-md-4 mb-2">
                  <label style="font-weight: 600; color: #1D1D1F; font-size: 12px; margin-bottom: 6px;">Tipo</label>
                  <select class="form-control form-control-sm campo-tipo" name="campos[${novoIdx}][tipo_campo]"
                    style="border: 1px solid #E5E5E7; border-radius: 8px; padding: 8px 12px; font-size: 13px;">
                    <option value="texto">Texto Fixo</option>
                    <option value="campo_dinamico">Campo DinÃ¢mico</option>
                  </select>
                </div>
                <div class="form-group col-md-4 mb-2">
                  <label style="font-weight: 600; color: #1D1D1F; font-size: 12px; margin-bottom: 6px;">RÃ³tulo</label>
                  <input type="text" class="form-control form-control-sm campo-label" name="campos[${novoIdx}][label]"
                    placeholder="Ex: Assinatura, Selo"
                    style="border: 1px solid #E5E5E7; border-radius: 8px; padding: 8px 12px; font-size: 13px;">
                </div>
                <div class="form-group col-md-4 mb-2">
                  <label style="font-weight: 600; color: #1D1D1F; font-size: 12px; margin-bottom: 6px;">Fonte</label>
                  <select class="form-control form-control-sm" name="campos[${novoIdx}][fonte]"
                    style="border: 1px solid #E5E5E7; border-radius: 8px; padding: 8px 12px; font-size: 13px;">
                    <option value="Arial">Arial</option>
                    <option value="Times">Times New Roman</option>
                    <option value="Courier">Courier New</option>
                    <option value="Ceviche One">Ceviche One</option>
                  </select>
                </div>
                <div class="form-group col-md-4 mb-2">
                  <label style="font-weight: 600; color: #1D1D1F; font-size: 12px; margin-bottom: 6px;">Tamanho
                    Fonte</label>
                  <input type="number" class="form-control form-control-sm" name="campos[${novoIdx}][tamanho_fonte]"
                    value="16" min="8" max="72"
                    style="border: 1px solid #E5E5E7; border-radius: 8px; padding: 8px 12px; font-size: 13px;">
                </div>
              <div class="form-group col-md-12 mb-2">
                    <div class="custom-control custom-switch">
                    <input type="checkbox" class="custom-control-input toggle-campo-custom"
                        id="exibir_campo_${novoIdx}_${templateId}"
                        name="campos[${novoIdx}][exibir]" value="1" checked
                        data-campo-idx="${novoIdx}" data-template-id="${templateId}">
                    <label class="custom-control-label"
                        for="exibir_campo_${novoIdx}_${templateId}"
                        style="font-size: 12px;">Exibir este campo</label>
                    </div>
                </div>
              </div>
              <div class="form-row">
                <div class="form-group col-md-3 mb-2">
                  <label style="font-weight: 600; color: #1D1D1F; font-size: 12px; margin-bottom: 6px;">PosiÃ§Ã£o
                    X</label>
                  <input type="number" class="form-control form-control-sm" name="campos[${novoIdx}][posicao_x]"
                    value="0" min="0"
                    style="border: 1px solid #E5E5E7; border-radius: 8px; padding: 8px 12px; font-size: 13px;">
                </div>
                <div class="form-group col-md-3 mb-2">
                  <label style="font-weight: 600; color: #1D1D1F; font-size: 12px; margin-bottom: 6px;">PosiÃ§Ã£o
                    Y</label>
                  <input type="number" class="form-control form-control-sm" name="campos[${novoIdx}][posicao_y]"
                    value="0" min="0"
                    style="border: 1px solid #E5E5E7; border-radius: 8px; padding: 8px 12px; font-size: 13px;">
                </div>
                <div class="form-group col-md-3 mb-2">
                  <label style="font-weight: 600; color: #1D1D1F; font-size: 12px; margin-bottom: 6px;">Cor</label>
                  <input type="color" class="form-control form-control-sm" name="campos[${novoIdx}][cor_hex]"
                    value="#000000" style="border: 1px solid #E5E5E7; border-radius: 8px; padding: 4px; height: 38px;">
                </div>
                <div class="form-group col-md-3 mb-2">
                  <label style="font-weight: 600; color: #1D1D1F; font-size: 12px; margin-bottom: 6px;">Ordem</label>
                  <input type="number" class="form-control form-control-sm" name="campos[${novoIdx}][ordem]"
                    value="${novoIdx}" min="0"
                    style="border: 1px solid #E5E5E7; border-radius: 8px; padding: 8px 12px; font-size: 13px;">
                </div>
              </div>
              <div class="form-group mb-2">
                <label style="font-weight: 600; color: #1D1D1F; font-size: 12px; margin-bottom: 6px;">Valor PadrÃ£o /
                  ConteÃºdo</label>
                <textarea class="form-control form-control-sm" name="campos[${novoIdx}][valor_padrao]" rows="2"
                  placeholder="Texto que aparecerÃ¡ no certificado"
                  style="border: 1px solid #E5E5E7; border-radius: 8px; padding: 8px 12px; font-size: 13px;"></textarea>
              </div>
              <div style="text-align: right;">
                <button type="button" class="btn btn-sm btn-remover-campo"
                  style="background: linear-gradient(135deg, rgba(255, 59, 48, 0.1) 0%, rgba(255, 59, 48, 0.05) 100%); color: #FF3B30; border: 1px solid rgba(255, 59, 48, 0.3); border-radius: 8px; padding: 6px 14px; font-weight: 600; font-size: 12px; transition: all 0.2s ease; display: inline-flex; align-items: center; gap: 6px;"
                  onmouseover="this.style.background='#FF3B30'; this.style.color='white'; this.style.borderColor='#FF3B30'"
                  onmouseout="this.style.background='linear-gradient(135deg, rgba(255, 59, 48, 0.1) 0%, rgba(255, 59, 48, 0.05) 100%)'; this.style.color='#FF3B30'; this.style.borderColor='rgba(255, 59, 48, 0.3)'">
                  <span class="material-icons-outlined" style="font-size: 16px;">delete</span>
                  Remover
                </button>
              </div>
            </div>
            `;

                      const div = document.createElement('div');
                      div.innerHTML = novoHtml;
                      const novoElemento = div.firstElementChild;
                      container.appendChild(novoElemento);

                      // Adicionar marcador no preview se estiver aberto
                      const modal = container.closest('.modal');
                      const preview = modal.querySelector('.position-preview');
                      if (preview) {
                        const canvas = preview.querySelector('.canvas');
                        const img = preview.querySelector('img.tpl-img');
                        if (canvas && img) {
                          const marker = document.createElement('div');
                          marker.className = 'marker m-custom';
                          marker.setAttribute('data-campo-idx', novoIdx);
                          marker.setAttribute('data-campo-x', 0);
                          marker.setAttribute('data-campo-y', 0);
                          marker.style.backgroundColor = '#000000';
                          marker.style.zIndex = '100';
                          marker.textContent = 'Novo Campo';

                          // Posicionar no centro
                          // Posicionar no centro (com delay para g                                                                                     arantir renderizaÃ§Ã£o)
                          setTimeout(() => {
                            const rect = img.getBoundingClientRect();
                            const centerX = rect.width > 0 ? rect.width / 2 : 100;
                            const centerY = rect.height > 0 ? rect.height / 2 : 100;
                            marker.style.left = (centerX - 50) + 'px'; // Aproximado
                            marker.style.top = (centerY - 10) + 'px';

                            canvas.appendChild(marker);

                            // Trigger event for initPreview to attach listeners
                            console.log('DEBUG: Dispatching customFieldAdded event');
                            const event = new CustomEvent('customFieldAdded', { detail: { marker: marker, idx: novoIdx } });
                            modal.dispatchEvent(event);
                          }, 1000);
                        } else {
                          console.error('DEBUG: Canvas or Img not found inside preview');
                        }
                      } else {
                        console.error('DEBUG: Preview container not found in initCamposCustomizados');
                      }
                      container.appendChild(div.firstElementChild);

                      // Reattach event listeners
                      attachRemoveListeners();
                    });
                  });

                  // BotÃ£o remover campo
                  function attachRemoveListeners() {
                    document.querySelectorAll('.btn-remover-campo').forEach(btn => {
                      btn.removeEventListener('click', removeHandler);
                      btn.addEventListener('click', removeHandler);
                    });
                  }

                  function removeHandler(e) {
                    e.preventDefault();
                    if (confirm('Tem certeza que deseja remover este campo?')) {
                      this.closest('.campo-customizado').remove();
                    }
                  }

                  attachRemoveListeners();
                }

                document.addEventListener('DOMContentLoaded', initCamposCustomizados);
              })();

              // JavaScript para mostrar nome do arquivo do verso selecionado nos modais de ediÃ§Ã£o
              document.addEventListener('DOMContentLoaded', function () {
                // Para cada modal de ediÃ§Ã£o
                document.querySelectorAll('[id^="arquivo_verso_"]').forEach(function (input) {
                  input.addEventListener('change', function (e) {
                    const templateId = this.id.replace('arquivo_verso_', '');
                    const fileName = e.target.files[0]?.name;
                    const fileNameDiv = document.getElementById('arquivo-verso-nome-' + templateId);
                    if (fileName && fileNameDiv) {
                      fileNameDiv.innerHTML = '<span class="material-icons-outlined"
                      style = "font-size: 16px; vertical-align: middle; margin-right: 4px;" > check_circle</span > ' + fileName;
                      fileNameDiv.style.display = 'block';
                    } else if (fileNameDiv) {
                      fileNameDiv.style.display = 'none';
                    }
                  });
                });
              });
            </script>

            <form method="POST" action="../app/actions/criar-template.php" enctype="multipart/form-data"
              id="formNovoTemplate">
              <div class="modal-body" style="padding: 28px 32px;">
                <!-- Info Box -->
                <div
                  style="background: linear-gradient(135deg, rgba(110, 65, 193, 0.05) 0%, rgba(110, 65, 193, 0.02) 100%); border: 2px solid rgba(110, 65, 193, 0.2); border-radius: 12px; padding: 16px 20px; margin-bottom: 24px; display: flex; align-items: start; gap: 12px;">
                  <span class="material-icons-outlined" style="font-size: 24px; color: #6E41C1;">info</span>
                  <div>
                    <strong style="color: #6E41C1; font-size: 14px;">Crie um novo template</strong>
                    <p style="color: #1D1D1F; font-size: 13px; margin: 4px 0 0 0;">
                      Para seus certificados. VocÃª pode usar PDF ou imagens (JPG/PNG).
                    </p>
                  </div>
                </div>

                <!-- Nome do Template -->
                <div class="form-group">
                  <label for="nome"
                    style="font-weight: 600; color: #1D1D1F; font-size: 14px; margin-bottom: 8px; display: flex; align-items: center; gap: 6px;">
                    <span class="material-icons-outlined" style="font-size: 18px; color: #6E41C1;">description</span>
                    Nome do Template <span style="color: #FF3B30;">*</span>
                  </label>
                  <input type="text" class="form-control" id="nome" name="nome"
                    placeholder="Ex: Certificado PadrÃ£o 2025" required
                    style="border: 2px solid #E5E5E7; border-radius: 10px; padding: 12px 16px; font-size: 14px; transition: all 0.2s ease;"
                    onfocus="this.style.borderColor='#6E41C1'; this.style.boxShadow='0 0 0 3px rgba(110, 65, 193, 0.1)'"
                    onblur="this.style.borderColor='#E5E5E7'; this.style.boxShadow='none'">
                </div>

                <!-- DescriÃ§Ã£o -->
                <div class="form-group">
                  <label for="descricao"
                    style="font-weight: 600; color: #1D1D1F; font-size: 14px; margin-bottom: 8px; display: flex; align-items: center; gap: 6px;">
                    <span class="material-icons-outlined" style="font-size: 18px; color: #6E41C1;">notes</span>
                    DescriÃ§Ã£o <span style="color: #FF3B30;">*</span>
                  </label>
                  <textarea class="form-control" id="descricao" name="descricao" rows="3"
                    placeholder="Descreva o template e seu uso" required
                    style="border: 2px solid #E5E5E7; border-radius: 10px; padding: 12px 16px; font-size: 14px; transition: all 0.2s ease;"
                    onfocus="this.style.borderColor='#6E41C1'; this.style.boxShadow='0 0 0 3px rgba(110, 65, 193, 0.1)'"
                    onblur="this.style.borderColor='#E5E5E7'; this.style.boxShadow='none'"></textarea>
                </div>

                <!-- Upload de Arquivo (Frente) -->
                <div class="form-group">
                  <label for="arquivo"
                    style="font-weight: 600; color: #1D1D1F; font-size: 14px; margin-bottom: 8px; display: flex; align-items: center; gap: 6px;">
                    <span class="material-icons-outlined" style="font-size: 18px; color: #6E41C1;">upload_file</span>
                    Arquivo do Template (Frente) <span style="color: #FF3B30;">*</span>
                  </label>
                  <div
                    style="border: 2px dashed #E5E5E7; border-radius: 12px; padding: 24px; text-align: center; background: #F5F5F7; transition: all 0.2s ease; cursor: pointer;"
                    onmouseover="this.style.borderColor='#6E41C1'; this.style.background='rgba(110, 65, 193, 0.02)'"
                    onmouseout="this.style.borderColor='#E5E5E7'; this.style.background='#F5F5F7'"
                    onclick="document.getElementById('arquivo').click()">
                    <span class="material-icons-outlined"
                      style="font-size: 48px; color: #6E41C1; margin-bottom: 8px;">cloud_upload</span>
                    <p style="color: #1D1D1F; font-weight: 600; margin: 0 0 4px 0;">Clique para selecionar o arquivo da
                      frente</p>
                    <small style="color: #86868B; font-size: 12px;">Formatos aceitos: PDF, JPG, PNG (mÃ¡x. 10MB)</small>
                    <input type="file" class="form-control-file" id="arquivo" name="arquivo"
                      accept=".pdf,.jpg,.jpeg,.png" required style="display: none;">
                  </div>
                  <div id="arquivo-nome"
                    style="margin-top: 8px; color: #6E41C1; font-size: 13px; font-weight: 600; display: none;"></div>
                </div>

                <!-- Upload de Arquivo (Verso) - OPCIONAL -->
                <div class="form-group">
                  <label for="arquivo_verso"
                    style="font-weight: 600; color: #1D1D1F; font-size: 14px; margin-bottom: 8px; display: flex; align-items: center; gap: 6px;">
                    <span class="material-icons-outlined" style="font-size: 18px; color: #6E41C1;">flip_to_back</span>
                    Arquivo do Verso (Opcional)
                  </label>
                  <div
                    style="border: 2px dashed #E5E5E7; border-radius: 12px; padding: 24px; text-align: center; background: #F5F5F7; transition: all 0.2s ease; cursor: pointer;"
                    onmouseover="this.style.borderColor='#6E41C1'; this.style.background='rgba(110, 65, 193, 0.02)'"
                    onmouseout="this.style.borderColor='#E5E5E7'; this.style.background='#F5F5F7'"
                    onclick="document.getElementById('arquivo_verso').click()">
                    <span class="material-icons-outlined"
                      style="font-size: 48px; color: #86868B; margin-bottom: 8px;">cloud_upload</span>
                    <p style="color: #1D1D1F; font-weight: 600; margin: 0 0 4px 0;">Clique para selecionar o arquivo do
                      verso</p>
                    <small style="color: #86868B; font-size: 12px;">Formatos aceitos: PDF, JPG, PNG (mÃ¡x. 10MB) - Deixe
                      em branco se nÃ£o houver verso</small>
                    <input type="file" class="form-control-file" id="arquivo_verso" name="arquivo_verso"
                      accept=".pdf,.jpg,.jpeg,.png" style="display: none;">
                  </div>
                  <div id="arquivo-verso-nome"
                    style="margin-top: 8px; color: #6E41C1; font-size: 13px; font-weight: 600; display: none;"></div>
                </div>
              </div>
              <div class="modal-footer"
                style="background: #F5F5F7; border-radius: 0 0 16px 16px; padding: 20px 28px; border-top: 1px solid #E5E5E7;">
                <button type="button" class="btn" data-dismiss="modal"
                  style="background: white; color: #86868B; border: 2px solid #E5E5E7; border-radius: 10px; padding: 10px 24px; font-weight: 600; transition: all 0.2s ease;"
                  onmouseover="this.style.borderColor='#6E41C1'; this.style.color='#6E41C1'"
                  onmouseout="this.style.borderColor='#E5E5E7'; this.style.color='#86868B'">
                  Cancelar
                </button>
                <button type="submit" class="btn"
                  style="background: linear-gradient(135deg, #6E41C1 0%, #8B5FD6 100%); color: white; border: none; border-radius: 10px; padding: 10px 24px; font-weight: 600; box-shadow: 0 4px 12px rgba(110, 65, 193, 0.3); transition: all 0.3s ease;"
                  onmouseover="this.style.transform='translateY(-2px)'; this.style.boxShadow='0 6px 20px rgba(110, 65, 193, 0.4)'"
                  onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 4px 12px rgba(110, 65, 193, 0.3)'">
                  <span class="material-icons-outlined"
                    style="font-size: 18px; vertical-align: middle; margin-right: 4px;">add_circle</span>
                  Criar Template
                </button>
              </div>
            </form>
            <script>
              // Mostrar nome do arquivo selecionado (Frente)
              document.getElementById('arquivo').addEventListener('change', function (e) {
                const fileName = e.target.files[0]?.name;
                const fileNameDiv = document.getElementById('arquivo-nome');
                if (fileName) {
                  fileNameDiv.innerHTML = '<span class="material-icons-outlined" style="font-size: 16px; vertical-align: middle; margin-right: 4px;">check_circle</span> ' + fileName;
                  fileNameDiv.style.display = 'block';
                } else {
                  fileNameDiv.style.display = 'none';
                }
              });

              // Mostrar nome do arquivo selecionado (Verso)
              document.getElementById('arquivo_verso').addEventListener('change', function (e) {
                const fileName = e.target.files[0]?.name;
                const fileNameDiv = document.getElementById('arquivo-verso-nome');
                if (fileName) {
                  fileNameDiv.innerHTML = '<span class="material-icons-outlined" style="font-size: 16px; vertical-align: middle; margin-right: 4px;">check_circle</span> ' + fileName;
                  fileNameDiv.style.display = 'block';
                } else {
                  fileNameDiv.style.display = 'none';
                }
              });

              // ValidaÃ§Ã£o do formulÃ¡rio
              document.getElementById('formNovoTemplate').addEventListener('submit', function (e) {
                const nome = document.getElementById('nome').value.trim();
                const descricao = document.getElementById('descricao').value.trim();
                const arquivo = document.getElementById('arquivo').files.length;

                if (!nome || nome.length < 3) {
                  e.preventDefault();
                  alert('âš ï¸ Por favor, digite um nome vÃ¡lido (mÃ­nimo 3 caracteres)');
                  return false;
                }

                if (!descricao || descricao.length < 5) {
                  e.preventDefault();
                  alert('âš ï¸ Por favor, digite uma descriÃ§Ã£o vÃ¡lida (mÃ­nimo 5 caracteres)');
                  return false;
                }

                if (arquivo === 0) {
                  e.preventDefault();
                  alert('âš ï¸ Por favor, selecione um arquivo');
                  return false;
                }

                return true;
              });
            </script>
          </div>
        </div>
      </div>

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



<!-- CSS Customizado para Design Moderno -->
<style>
  /* DataTables CustomizaÃ§Ã£o */
  #templatesTable_wrapper .dataTables_length select {
    border: 2px solid #E5E5E7;
    border-radius: 10px;
    padding: 6px 12px;
    font-size: 14px;
    color: #1D1D1F;
    transition: all 0.2s ease;
  }

  #templatesTable_wrapper .dataTables_length select:focus {
    outline: none;
    border-color: #6E41C1;
    box-shadow: 0 0 0 3px rgba(110, 65, 193, 0.1);
  }

  #templatesTable_wrapper .dataTables_filter input {
    border: 2px solid #E5E5E7;
    border-radius: 10px;
    padding: 8px 16px;
    font-size: 14px;
    color: #1D1D1F;
    transition: all 0.2s ease;
  }

  #templatesTable_wrapper .dataTables_filter input:focus {
    outline: none;
    border-color: #6E41C1;
    box-shadow: 0 0 0 3px rgba(110, 65, 193, 0.1);
  }

  #templatesTable_wrapper .dataTables_paginate .paginate_button {
    border-radius: 8px;
    padding: 6px 12px;
    margin: 0 2px;
    border: 2px solid transparent;
    background: transparent;
    color: #86868B !important;
    font-weight: 600;
    transition: all 0.2s ease;
  }

  #templatesTable_wrapper .dataTables_paginate .paginate_button:hover {
    background: rgba(110, 65, 193, 0.05);
    border-color: #6E41C1;
    color: #6E41C1 !important;
  }

  #templatesTable_wrapper .dataTables_paginate .paginate_button.current {
    background: #6E41C1 !important;
    border-color: #6E41C1 !important;
    color: white !important;
  }

  /* Checkbox Customizado */
  .form-check-input {
    width: 20px;
    height: 20px;
    border: 2px solid #E5E5E7;
    border-radius: 6px;
    cursor: pointer;
    transition: all 0.2s ease;
  }

  .form-check-input:checked {
    background-color: #6E41C1;
    border-color: #6E41C1;
  }

  .form-check-input:focus {
    box-shadow: 0 0 0 3px rgba(110, 65, 193, 0.1);
  }

  /* Modal CustomizaÃ§Ã£o */
  .modal-content {
    border: none;
    border-radius: 16px;
    box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
  }

  .modal-body {
    padding: 28px 32px;
  }

  .modal-footer {
    padding: 20px 32px;
    border-top: 1px solid #F5F5F7;
  }

  /* Form Inputs Modernos */
  .form-control {
    border: 2px solid #E5E5E7;
    border-radius: 10px;
    padding: 10px 16px;
    font-size: 14px;
    color: #1D1D1F;
    transition: all 0.2s ease;
  }

  .form-control:focus {
    border-color: #6E41C1;
    box-shadow: 0 0 0 3px rgba(110, 65, 193, 0.1);
  }

  /* Scrollbar Customizada */
  ::-webkit-scrollbar {
    width: 10px;
    height: 10px;
  }

  ::-webkit-scrollbar-track {
    background: #F5F5F7;
    border-radius: 10px;
  }

  ::-webkit-scrollbar-thumb {
    background: #E5E5E7;
    border-radius: 10px;
    transition: all 0.2s ease;
  }

  ::-webkit-scrollbar-thumb:hover {
    background: #6E41C1;
  }

  /* AnimaÃ§Ãµes */
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

  .alert {
    animation: slideInDown 0.3s ease;
  }

  /* Responsividade */
  @media (max-width: 768px) {
    .card-header {
      padding: 16px 20px !important;
    }

    .card-body {
      padding: 16px !important;
    }

    .modal-body {
      padding: 20px !important;
    }

    .modal-footer {
      padding: 16px 20px !important;
    }

    table td,
    table th {
      padding: 12px 16px !important;
    }
  }
</style>

<?php require_once '../app/views/footer.php'; ?>
<?php $conn->close(); ?>