<?php
/**
 * ============================================================================
 * DASHBOARD DO ADMINISTRADOR - SISTEMA DE CERTIFICADOS
 * ============================================================================
 *
 * Esta é a página principal do administrador.
 * Aqui o admin vê um resumo de tudo: quantos parceiros, assinaturas, etc.
 * É como um "painel de controle" do sistema.
 *
 * Padrão MVP - Camada de Apresentação
 * ============================================================================
 */

// Inclui o arquivo de configuração
require_once '../app/config/config.php';

// Inclui a classe DashboardPresenter
// Presenter = classe que prepara os dados para mostrar na tela
require_once '../app/presenters/DashboardPresenter.php';

// ============================================================================
// VERIFICAR AUTENTICAÇÃO E PERMISSÃO
// ============================================================================
// Verifica se o usuário está logado E se é admin
// Se não for, redireciona para login
if (!isAuthenticated() || !hasRole(ROLE_ADMIN)) {
  redirect(APP_URL . '/login.php');
}

// ============================================================================
// PREPARAR OS DADOS PARA MOSTRAR
// ============================================================================
// Conecta ao banco de dados
$conn = getDBConnection();

// Cria um objeto DashboardPresenter
// Presenter = classe que pega dados do banco e prepara para mostrar
$presenter = new DashboardPresenter($conn);

// Chama o método que prepara todos os dados do dashboard
// Isso retorna um array com estatísticas, parceiros recentes, etc.
$data = $presenter->prepareAdminDashboard();

// Define o título da página
$page_title = 'Dashboard do Administrador - ' . APP_NAME;

// Pega dados do usuário logado
$user = getCurrentUser();
?>
<?php require_once '../app/views/header.php'; ?>

<!-- Sidebar -->
<?php require_once '../app/views/sidebar-admin.php'; ?>

<!-- Content Wrapper -->
<div id="content-wrapper" class="d-flex flex-column">

  <!-- Main Content -->
  <div id="content">

    <!-- Topbar -->
    <?php require_once '../app/views/topbar.php'; ?>

    <!-- Begin Page Content -->
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

      #toggleReceita {
        color: #ffc107;
        transition: all .3s ease;
        cursor: pointer
      }

      #toggleReceita:hover {
        color: #ff9800;
        transform: scale(1.2)
      }
    </style>

    <div class="container-fluid" style="padding:0;">
      <div class="erp-container">
        <aside class="sidebar">
          <div class="sidebar-header">FaCiencia</div>
          <nav class="sidebar-nav">
            <span class="nav-section-title">Navegação</span>
            <ul>
              <li><a href="<?php echo APP_URL; ?>/admin/dashboard-admin.php" class="active"><span
                    class="icon">dashboard</span> Dashboard</a></li>
            </ul>
            <span class="nav-section-title">Gestão</span>
            <ul>
              <li><a href="<?php echo APP_URL; ?>/admin/parceiros-admin.php"><span class="icon">business</span>
                  Parceiros</a></li>
              <li><a href="<?php echo APP_URL; ?>/admin/assinaturas-admin.php"><span class="icon">card_membership</span>
                  Assinaturas</a></li>
              <li><a href="<?php echo APP_URL; ?>/admin/planos-admin.php"><span class="icon">price_check</span>
                  Planos</a></li>
              <li><a href="<?php echo APP_URL; ?>/admin/solicitacoes-planos.php"><span class="icon">request_page</span>
                  Solicitações</a></li>
            </ul>
            <span class="nav-section-title">Certificação</span>
            <ul>
              <li><a href="<?php echo APP_URL; ?>/admin/certificados-admin.php"><span
                    class="icon">workspace_premium</span> Certificados</a></li>
              <li><a href="<?php echo APP_URL; ?>/admin/templates-sistema.php"><span class="icon">article</span>
                  Templates</a></li>

              <li><a href="<?php echo APP_URL; ?>/admin/cursos-admin.php"><span class="icon">school</span> Cursos</a>
              </li>
              <li><a href="<?php echo APP_URL; ?>/admin/alunos-admin.php"><span class="icon">group</span> Alunos</a>
              </li>
            </ul>
            <span class="nav-section-title">Financeiro</span>
            <ul>
              <li><a href="<?php echo APP_URL; ?>/admin/faturas-admin.php"><span class="icon">receipt</span> Faturas</a>
              </li>
              <li><a href="<?php echo APP_URL; ?>/admin/pagamentos-admin.php"><span class="icon">payment</span>
                  Pagamentos</a></li>
              <li><a href="<?php echo APP_URL; ?>/admin/receitas-admin.php"><span class="icon">attach_money</span>
                  Receitas</a></li>
              <li><a href="<?php echo APP_URL; ?>/admin/boletos-asaas.php"><span class="icon">description</span> Boletos
                  Asaas</a></li>
            </ul>
            <span class="nav-section-title">Jurídico</span>
            <ul>
              <li><a href="<?php echo APP_URL; ?>/admin/contratos-admin.php"><span class="icon">gavel</span>
                  Contratos</a></li>
              <li><a href="<?php echo APP_URL; ?>/admin/termos-admin.php"><span class="icon">policy</span> Termos</a>
              </li>
            </ul>
            <span class="nav-section-title">Sistema</span>
            <ul>
              <li><a href="<?php echo APP_URL; ?>/admin/relatorios-admin.php"><span class="icon">assessment</span>
                  Relatórios</a></li>
              <li><a href="<?php echo APP_URL; ?>/admin/configuracoes-admin.php"><span class="icon">settings</span>
                  Configurações</a></li>
              <li><a href="<?php echo APP_URL; ?>/admin/asaas-config.php"
                  class="<?php echo $current_page == 'asaas-config' ? 'active' : ''; ?>"><span class="icon">api</span>
                  Config. Asaas</a></li>
              <li><a href="<?php echo APP_URL; ?>/admin/tutoriais.php"
                  class="<?php echo $current_page == 'tutoriais' ? 'active' : ''; ?>"><span
                    class="icon">help_outline</span>
                  Tutoriais</a></li>
              <li><a href="<?php echo APP_URL; ?>/admin/usuarios-admin.php"><span
                    class="icon">admin_panel_settings</span> Usuários Admin</a></li>
            </ul>
          </nav>
          <div class="sidebar-footer">
            <ul>
              <li><a href="<?php echo APP_URL; ?>/admin/perfil-admin.php"><span class="icon">person</span> Meu
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
              <?php echo htmlspecialchars($user['name'] ?? ($user['email'] ?? 'Administrador')); ?>
            </div>
          </header>

          <main class="content-area">
            <h1>Dashboard do Administrador</h1>

            <!-- Estatísticas Gerais -->
            <div class="stats-grid">
              <!-- Card: Total de Parceiros -->
              <!-- Card: Total de Parceiros -->
              <div class="card stat-card card-stats-parceiros">
                <span class="icon">business</span>
                <div class="stat-label">Total de Parceiros</div>
                <div class="stat-value"><?php echo $data['stats']['total_parceiros']; ?></div>
                <div class="stat-change">Empresas cadastradas</div>
              </div>

              <!-- Card: Assinaturas Ativas -->
              <!-- Card: Assinaturas Ativas -->
              <div class="card stat-card card-stats-assinaturas">
                <span class="icon">card_membership</span>
                <div class="stat-label">Assinaturas Ativas</div>
                <div class="stat-value"><?php echo $data['stats']['assinaturas_ativas']; ?></div>
                <div class="stat-change">Planos em vigor</div>
              </div>

              <!-- Card: Certificados Gerados -->
              <div class="card stat-card">
                <span class="icon">workspace_premium</span>
                <div class="stat-label">Certificados Gerados</div>
                <div class="stat-value"><?php echo $data['stats']['certificados_gerados']; ?></div>
                <div class="stat-change">Total emitido</div>
              </div>

              <!-- Card: Receita Total -->
              <div class="card stat-card">
                <span class="icon" style="color: #FFD60A;">payments</span>
                <div class="stat-label">
                  Receita Total
                  <span class="material-icons-outlined" id="toggleReceita"
                    style="font-size: 18px; vertical-align: middle; margin-left: 8px; cursor: pointer;">visibility</span>
                </div>
                <div class="stat-value">
                  <span id="receitaValor" class="blur-text">
                    R$ <?php echo number_format($data['stats']['receita_total'], 2, ',', '.'); ?>
                  </span>
                </div>
                <div class="stat-change">Faturamento total</div>
              </div>
            </div>

            <!-- Tabela: Parceiros Recentes -->
            <section class="table-section">
              <div class="card">
                <h2><span class="icon">business</span>Parceiros Recentes</h2>
                <div class="table-responsive">
                  <table class="table">
                    <thead>
                      <tr>
                        <th>Nome</th>
                        <th>Plano</th>
                        <th>Status</th>
                        <th>Data Cadastro</th>
                      </tr>
                    </thead>
                    <tbody>
                      <?php if (!empty($data['recent_parceiros'])): ?>
                        <?php foreach ($data['recent_parceiros'] as $parceiro): ?>
                          <tr>
                            <td><strong><?php echo htmlspecialchars($parceiro['nome']); ?></strong></td>
                            <td><?php echo htmlspecialchars($parceiro['plano']); ?></td>
                            <td><span
                                class="badge badge-success"><?php echo htmlspecialchars($parceiro['status']); ?></span></td>
                            <td><?php echo $presenter->formatDate($parceiro['data_criacao']); ?></td>
                          </tr>
                        <?php endforeach; ?>
                      <?php else: ?>
                        <tr>
                          <td colspan="4" style="text-align: center; color: var(--text-medium);">Nenhum parceiro
                            registrado.</td>
                        </tr>
                      <?php endif; ?>
                    </tbody>
                  </table>
                </div>
              </div>
            </section>

            <!-- Tabela: Assinaturas Vencendo -->
            <section class="table-section">
              <div class="card">
                <h2><span class="icon" style="color: #FF9F0A;">warning</span>Assinaturas Vencendo em Breve</h2>
                <div class="table-responsive">
                  <table class="table">
                    <thead>
                      <tr>
                        <th>Parceiro</th>
                        <th>Dias Restantes</th>
                        <th>Ação</th>
                      </tr>
                    </thead>
                    <tbody>
                      <?php if (!empty($data['expiring_subscriptions'])): ?>
                        <?php foreach ($data['expiring_subscriptions'] as $sub): ?>
                          <tr>
                            <td><strong><?php echo htmlspecialchars($sub['nome']); ?></strong></td>
                            <td><span class="badge badge-warning"><?php echo $sub['dias_restantes']; ?> dias</span></td>
                            <td>
                              <a href="<?php echo APP_URL; ?>/admin/assinaturas.php" class="button button-secondary"
                                style="min-width: auto; padding: 6px 12px; font-size: 0.85rem;">
                                <span class="icon" style="margin: 0; font-size: 16px;">visibility</span>
                              </a>
                            </td>
                          </tr>
                        <?php endforeach; ?>
                      <?php else: ?>
                        <tr>
                          <td colspan="3" style="text-align: center; color: var(--text-medium);">Nenhuma assinatura
                            vencendo em breve.</td>
                        </tr>
                      <?php endif; ?>
                    </tbody>
                  </table>
                </div>
              </div>
            </section>

          </main>
        </div>
      </div>
    </div>

  </div>
  <!-- End of Main Content -->

</div>
<!-- End of Content Wrapper -->

<?php require_once '../app/views/footer.php'; ?>

<script>
  // Toggle para mostrar/ocultar receita
  document.getElementById('toggleReceita').addEventListener('click', function () {
    const receitaValor = document.getElementById('receitaValor');
    const icon = this;

    if (receitaValor.classList.contains('blur-text')) {
      receitaValor.classList.remove('blur-text');
      icon.textContent = 'visibility_off';
    } else {
      receitaValor.classList.add('blur-text');
      icon.textContent = 'visibility';
    }
  });
</script>

<?php $conn->close(); ?>
</body>

</html>