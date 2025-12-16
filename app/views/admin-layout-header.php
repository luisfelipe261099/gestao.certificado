<?php
/**
 * Layout Header para Admin - ERP Style
 * Inclui CSS, Sidebar e Top Header
 */

// Pega dados do usuário logado
$user = getCurrentUser();
$current_page = basename($_SERVER['PHP_SELF'], '.php');
?>
<?php require_once '../app/views/header.php'; ?>

<!-- Ocultar sidebar e topbar antigos -->
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

  .stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
    gap: 20px;
    margin-bottom: 24px
  }

  .stat-card {
    padding: 24px
  }

  .stat-card .stat-label {
    font-size: .85rem;
    color: var(--text-medium);
    margin-bottom: 10px;
    text-transform: uppercase;
    letter-spacing: .5px;
    font-weight: 500
  }

  .stat-card .stat-value {
    font-size: 2.5rem;
    font-weight: 700;
    color: var(--text-dark);
    margin-bottom: 8px
  }

  .stat-card .stat-change {
    font-size: .85rem;
    color: var(--status-green);
    font-weight: 500
  }

  .stat-card .icon {
    font-size: 32px;
    color: var(--primary-color);
    margin-bottom: 12px
  }

  .table-section {
    margin-bottom: 24px
  }

  .table-section h2 {
    font-size: 1.2rem;
    margin-bottom: 16px;
    font-weight: 600;
    display: flex;
    align-items: center
  }

  .table-section h2 .icon {
    color: var(--primary-color);
    font-size: 24px
  }

  .table-responsive {
    overflow-x: auto
  }

  .table {
    width: 100%;
    border-collapse: collapse
  }

  .table thead {
    background: var(--sidebar-bg)
  }

  .table th {
    padding: 12px 16px;
    text-align: left;
    font-size: .85rem;
    font-weight: 600;
    color: var(--text-dark);
    text-transform: uppercase;
    letter-spacing: .5px;
    border-bottom: 2px solid var(--border-light)
  }

  .table td {
    padding: 14px 16px;
    border-bottom: 1px solid var(--border-light);
    font-size: .9rem;
    color: var(--text-dark)
  }

  .table tbody tr:hover {
    background: var(--sidebar-bg)
  }

  .badge {
    display: inline-block;
    padding: 4px 12px;
    border-radius: 20px;
    font-size: .8rem;
    font-weight: 600
  }

  .badge-success {
    background: #d4edda;
    color: #155724
  }

  .badge-warning {
    background: #fff3cd;
    color: #856404
  }

  .badge-danger {
    background: #f8d7da;
    color: #721c24
  }

  .badge-info {
    background: #d1ecf1;
    color: #0c5460
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

  .button-primary .icon {
    color: #fff
  }

  .blur-text {
    filter: blur(5px);
    user-select: none
  }

  .page-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 24px
  }

  .page-header h1 {
    margin: 0
  }

  .action-buttons {
    display: flex;
    gap: 12px
  }

  .alert {
    padding: 12px 20px;
    border-radius: 8px;
    margin-bottom: 20px;
    display: flex;
    align-items: center;
    font-size: .9rem
  }

  .alert .icon {
    margin-right: 12px;
    font-size: 20px
  }

  .alert-success {
    background: #d4edda;
    color: #155724;
    border: 1px solid #c3e6cb
  }

  .alert-danger {
    background: #f8d7da;
    color: #721c24;
    border: 1px solid #f5c6cb
  }

  .alert-warning {
    background: #fff3cd;
    color: #856404;
    border: 1px solid #ffeaa7
  }

  .alert-info {
    background: #d1ecf1;
    color: #0c5460;
    border: 1px solid #bee5eb
  }

  .modal.fade .modal-dialog {
    transition: transform .3s ease-out
  }

  .modal.show {
    display: block !important
  }

  .modal-backdrop {
    position: fixed;
    top: 0;
    left: 0;
    z-index: 1040;
    width: 100vw;
    height: 100vh;
    background-color: #000
  }

  .modal-backdrop.fade {
    opacity: 0
  }

  .modal-backdrop.show {
    opacity: .5
  }

  .modal {
    position: fixed;
    top: 0;
    left: 0;
    z-index: 1050;
    width: 100%;
    height: 100%;
    overflow: hidden;
    outline: 0
  }

  .modal-dialog {
    position: relative;
    width: auto;
    margin: .5rem;
    pointer-events: none
  }

  .modal.fade .modal-dialog {
    transform: translate(0, -50px)
  }

  .modal.show .modal-dialog {
    transform: none
  }

  .modal-content {
    position: relative;
    display: flex;
    flex-direction: column;
    width: 100%;
    pointer-events: auto;
    background-color: var(--card-bg);
    background-clip: padding-box;
    border: 1px solid rgba(0, 0, 0, .2);
    border-radius: var(--border-radius-card);
    outline: 0;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.15)
  }

  .modal-header {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    padding: 20px 24px;
    border-bottom: 1px solid var(--border-light);
    border-top-left-radius: calc(var(--border-radius-card) - 1px);
    border-top-right-radius: calc(var(--border-radius-card) - 1px)
  }

  .modal-header h2,
  .modal-header h5 {
    margin: 0;
    font-size: 1.3rem
  }

  .modal-title {
    margin-bottom: 0;
    line-height: 1.5
  }

  .modal-body {
    position: relative;
    flex: 1 1 auto;
    padding: 24px
  }

  .modal-footer {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    justify-content: flex-end;
    padding: 16px 24px;
    border-top: 1px solid var(--border-light);
    border-bottom-right-radius: calc(var(--border-radius-card) - 1px);
    border-bottom-left-radius: calc(var(--border-radius-card) - 1px);
    gap: 12px
  }

  .modal-footer>* {
    margin: 0
  }

  .close {
    padding: 0;
    background-color: transparent;
    border: 0;
    color: var(--text-medium);
    font-size: 28px;
    font-weight: bold;
    line-height: 1;
    cursor: pointer;
    opacity: .5
  }

  .close:hover {
    color: var(--text-dark);
    opacity: .75
  }

  @media (min-width:576px) {
    .modal-dialog {
      max-width: 600px;
      margin: 1.75rem auto
    }
  }

  .form-group {
    margin-bottom: 20px
  }

  .form-group label {
    display: block;
    margin-bottom: 8px;
    font-weight: 500;
    color: var(--text-dark);
    font-size: .9rem
  }

  .form-group input,
  .form-group select,
  .form-group textarea {
    width: 100%;
    padding: 10px 14px;
    border: 1px solid var(--border-medium);
    border-radius: 8px;
    font-size: .9rem;
    font-family: 'Inter', sans-serif;
    transition: border-color .2s
  }

  .form-group input:focus,
  .form-group select:focus,
  .form-group textarea:focus {
    outline: none;
    border-color: var(--primary-color)
  }

  .form-group textarea {
    resize: vertical;
    min-height: 100px
  }
</style>

<!-- Sidebar -->
<?php require_once '../app/views/sidebar-admin.php'; ?>

<!-- Content Wrapper -->
<div id="content-wrapper" class="d-flex flex-column">
  <!-- Main Content -->
  <div id="content">
    <!-- Topbar -->
    <?php require_once '../app/views/topbar.php'; ?>

    <div class="container-fluid" style="padding:0;">
      <div class="erp-container">
        <aside class="sidebar">
          <div class="sidebar-header">FaCiencia</div>
          <nav class="sidebar-nav">
            <span class="nav-section-title">Navegação</span>
            <ul>
              <li><a href="<?php echo APP_URL; ?>/admin/dashboard-admin.php"
                  class="<?php echo $current_page == 'dashboard-admin' ? 'active' : ''; ?>"><span
                    class="icon">dashboard</span> Dashboard</a></li>
            </ul>
            <span class="nav-section-title">Gestão</span>
            <ul>
              <li><a href="<?php echo APP_URL; ?>/admin/parceiros-admin.php"
                  class="<?php echo $current_page == 'parceiros-admin' ? 'active' : ''; ?>"><span
                    class="icon">business</span> Parceiros</a></li>
              <li><a href="<?php echo APP_URL; ?>/admin/assinaturas-admin.php"
                  class="<?php echo $current_page == 'assinaturas-admin' ? 'active' : ''; ?>"><span
                    class="icon">card_membership</span> Assinaturas</a></li>
              <li><a href="<?php echo APP_URL; ?>/admin/planos-admin.php"
                  class="<?php echo $current_page == 'planos-admin' ? 'active' : ''; ?>"><span
                    class="icon">price_check</span> Planos</a></li>
              <li><a href="<?php echo APP_URL; ?>/admin/solicitacoes-planos.php"
                  class="<?php echo $current_page == 'solicitacoes-planos' ? 'active' : ''; ?>"><span
                    class="icon">request_page</span> Solicitações</a></li>
            </ul>
            <span class="nav-section-title">Certificação</span>
            <ul>
              <li><a href="<?php echo APP_URL; ?>/admin/certificados-admin.php"
                  class="<?php echo $current_page == 'certificados-admin' ? 'active' : ''; ?>"><span
                    class="icon">workspace_premium</span> Certificados</a></li>
              <li><a href="<?php echo APP_URL; ?>/admin/templates-sistema.php"
                  class="<?php echo $current_page == 'templates-sistema' ? 'active' : ''; ?>"><span
                    class="icon">article</span> Templates</a></li>

              <li><a href="<?php echo APP_URL; ?>/admin/cursos-admin.php"
                  class="<?php echo $current_page == 'cursos-admin' ? 'active' : ''; ?>"><span
                    class="icon">school</span> Cursos</a></li>
              <li><a href="<?php echo APP_URL; ?>/admin/alunos-admin.php"
                  class="<?php echo $current_page == 'alunos-admin' ? 'active' : ''; ?>"><span class="icon">group</span>
                  Alunos</a></li>
            </ul>
            <span class="nav-section-title">Financeiro</span>
            <ul>
              <li><a href="<?php echo APP_URL; ?>/admin/faturas-admin.php"
                  class="<?php echo $current_page == 'faturas-admin' ? 'active' : ''; ?>"><span
                    class="icon">receipt</span> Faturas</a></li>
              <li><a href="<?php echo APP_URL; ?>/admin/pagamentos-admin.php"
                  class="<?php echo $current_page == 'pagamentos-admin' ? 'active' : ''; ?>"><span
                    class="icon">payment</span> Pagamentos</a></li>
              <li><a href="<?php echo APP_URL; ?>/admin/receitas-admin.php"
                  class="<?php echo $current_page == 'receitas-admin' ? 'active' : ''; ?>"><span
                    class="icon">attach_money</span> Receitas</a></li>
              <li><a href="<?php echo APP_URL; ?>/admin/boletos-asaas.php"
                  class="<?php echo $current_page == 'boletos-asaas' ? 'active' : ''; ?>"><span
                    class="icon">description</span> Boletos Asaas</a></li>
            </ul>
            <span class="nav-section-title">Jurídico</span>
            <ul>
              <li><a href="<?php echo APP_URL; ?>/admin/contratos-admin.php"
                  class="<?php echo $current_page == 'contratos-admin' ? 'active' : ''; ?>"><span
                    class="icon">gavel</span> Contratos</a></li>
              <li><a href="<?php echo APP_URL; ?>/admin/termos-admin.php"
                  class="<?php echo $current_page == 'termos-admin' ? 'active' : ''; ?>"><span
                    class="icon">policy</span> Termos</a></li>
            </ul>
            <span class="nav-section-title">Sistema</span>
            <ul>
              <li><a href="<?php echo APP_URL; ?>/admin/relatorios-admin.php"
                  class="<?php echo $current_page == 'relatorios-admin' ? 'active' : ''; ?>"><span
                    class="icon">assessment</span> Relatórios</a></li>
              <li><a href="<?php echo APP_URL; ?>/admin/configuracoes-admin.php"
                  class="<?php echo $current_page == 'configuracoes-admin' ? 'active' : ''; ?>"><span
                    class="icon">settings</span> Configurações</a></li>
              <li><a href="<?php echo APP_URL; ?>/admin/asaas-config.php"
                  class="<?php echo $current_page == 'asaas-config' ? 'active' : ''; ?>"><span class="icon">api</span>
                  Config. Asaas</a></li>
              <li><a href="<?php echo APP_URL; ?>/admin/tutoriais.php"
                  class="<?php echo $current_page == 'tutoriais' ? 'active' : ''; ?>"><span
                    class="icon">help_outline</span>
                  Tutoriais</a></li>
            </ul>
          </nav>
          <div class="sidebar-footer">
            <ul>
              <li><a href="<?php echo APP_URL; ?>/admin/perfil-admin.php"
                  class="<?php echo $current_page == 'perfil-admin' ? 'active' : ''; ?>"><span
                    class="icon">person</span> Meu Perfil</a></li>
              <li><a href="<?php echo APP_URL; ?>/app/actions/logout.php"><span class="icon">logout</span> Sair</a></li>
            </ul>
          </div>
        </aside>

        <div class="main-wrapper">
          <header class="top-header">
            <span class="icon notifications">notifications</span>
            <div class="user-profile">
              <span class="icon">account_circle</span>
              <?php echo htmlspecialchars($user['name'] ?? ($user['email'] ?? 'Administrador')); ?>
            </div>
          </header>

          <main class="content-area">