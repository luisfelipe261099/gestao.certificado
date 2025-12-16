<?php
/**
 * ============================================================================
 * DASHBOARD DO PARCEIRO - SISTEMA DE CERTIFICADOS
 * ============================================================================
 *
 * Esta é a página inicial que o parceiro (empresa) vê quando faz login.
 * Aqui o parceiro pode ver:
 * - Seu plano atual
 * - Quantos certificados tem disponíveis
 * - Quantos certificados já usou
 * - Seus alunos
 * - Seus cursos
 * - Seus certificados gerados
 *
 * Padrão MVP - Camada de Apresentação
 * ============================================================================
 */

// Inclui o arquivo de configuração
require_once '../app/config/config.php';

// Inclui o presenter (classe que prepara os dados)
require_once '../app/presenters/DashboardPresenter.php';
require_once '../app/models/Contrato.php';

// ============================================================================
// VERIFICAR AUTENTICAÇÃO E PERMISSÃO
// ============================================================================
// Verifica se está logado e se é parceiro (não admin)
if (!isAuthenticated() || !hasRole(ROLE_PARCEIRO)) {
  redirect(APP_URL . '/login.php');
}

// ============================================================================
// VERIFICAR SE PRECISA ACEITAR TERMOS
// ============================================================================
$user = getCurrentUser();
$conn = getDBConnection();

// Verificar se o parceiro já aceitou os termos
$contrato_model = new Contrato($conn);
$usuario_id_para_verificar = $user['parceiro_id'] ?? $user['id'];
// Verificar se aceitou o contrato de parceiro (tipo = 'contrato_parceiro')
$precisa_aceitar = !$contrato_model->usuario_aceitou_termos($usuario_id_para_verificar, 'parceiro', 'contrato_parceiro');

if ($precisa_aceitar) {
  redirect(APP_URL . '/aceitar-termos.php');
}

// Verificar se o EAD está ativado para este parceiro
$stmt = $conn->prepare("SELECT ead_ativo FROM parceiros WHERE id = ?");
$stmt->bind_param("i", $user['parceiro_id']);
$stmt->execute();
$result = $stmt->get_result();
$parceiro_info = $result->fetch_assoc();
$stmt->close();
$ead_ativo = $parceiro_info ? $parceiro_info['ead_ativo'] : 0;
$conn->close();

// ============================================================================
// PREPARAR DADOS PARA O DASHBOARD
// ============================================================================
// Conecta ao banco de dados
$conn = getDBConnection();

// Cria um objeto presenter (classe que prepara dados)
$presenter = new DashboardPresenter($conn);

// Pega dados do usuário logado
$user = getCurrentUser();

// Pega o ID do parceiro
// ?? = se não existir, usa o outro valor
$parceiro_id = $user['parceiro_id'] ?? $user['id'];

// Prepara os dados do dashboard (estatísticas, etc)
$data = $presenter->prepareParceiroDashboard($parceiro_id);

// ============================================================================
// BUSCAR ASSINATURA DO PARCEIRO (qualquer status não cancelado/expirado)
// ============================================================================
// Assinatura = o plano que o parceiro contratou
$assinatura = [];
$stmt = $conn->prepare("
    SELECT
        a.id,                              -- ID da assinatura
        a.data_inicio,                     -- Quando começou
        a.data_vencimento,                 -- Quando termina
        a.status,                          -- Status da assinatura
        pl.quantidade_certificados AS certificados_totais, -- Total do plano (tabela planos)
        pl.nome as plano_nome,             -- Nome do plano
        pl.descricao,                      -- Descrição do plano
        pl.valor                           -- Valor do plano
    FROM assinaturas a
    JOIN planos pl ON a.plano_id = pl.id  -- Junta com a tabela de planos
    WHERE a.parceiro_id = ? 
      AND (a.status IS NULL OR a.status = '' OR a.status IN ('ativa', 'aguardando_pagamento', 'pendente'))
    ORDER BY a.criado_em DESC
    LIMIT 1                                -- Pega apenas uma
");

if ($stmt) {
  // Substitui o ? pelo ID do parceiro
  $stmt->bind_param("i", $parceiro_id);

  // Executa a consulta
  $stmt->execute();

  // Pega o resultado
  $result = $stmt->get_result();

  // Se encontrou uma assinatura, pega os dados
  if ($row = $result->fetch_assoc()) {
    $assinatura = $row;
  }

  // Fecha a consulta
  $stmt->close();
}

// Define o título da página
$page_title = 'Dashboard do Parceiro - ' . APP_NAME;
?>
<?php require_once '../app/views/header.php'; ?>

<!-- Sidebar -->
<?php require_once '../app/views/sidebar-parceiro.php'; ?>

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

      .hero-kpi-section {
        padding: 24px;
        margin-bottom: 20px
      }

      .hero-kpi-section h2 {
        font-size: 1.15rem;
        margin-bottom: 15px
      }

      .hero-kpi-section h2 .icon {
        font-size: 24px;
        margin-right: 12px
      }

      .hero-kpi-usage {
        display: flex;
        align-items: baseline;
        gap: 10px
      }

      .hero-kpi-usage .value {
        font-size: 3rem;
        font-weight: 700
      }

      .hero-kpi-usage .limit {
        font-size: 1.5rem;
        font-weight: 500
      }

      .hero-kpi-section .progress-bar {
        height: 10px;
        margin: 15px 0;
        background: #EFEFF3;
        border-radius: 6px
      }

      .hero-kpi-section .progress {
        height: 100%;
        background: #FF9500;
        border-radius: 6px
      }

      .secondary-kpi-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
        gap: 20px;
        margin-bottom: 20px
      }

      .kpi-card {
        padding: 20px
      }

      .kpi-card .kpi-label {
        font-size: .85rem;
        margin-bottom: 10px;
        text-transform: uppercase;
        letter-spacing: .5px
      }

      .kpi-card .kpi-value {
        font-size: 2rem
      }

      .quick-access-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
        gap: 20px;
        margin-bottom: 20px
      }

      .access-card {
        padding: 24px;
        display: flex;
        flex-direction: column
      }

      .access-card .icon {
        font-size: 32px;
        margin-bottom: 15px
      }

      .icon-ead {
        color: #5AC8FA
      }

      .icon-curso {
        color: #FF9F0A
      }

      .icon-aluno {
        color: #34C759
      }

      .access-card h3 {
        font-size: 1.15rem;
        margin-bottom: 8px
      }

      .access-card p {
        font-size: .9rem;
        flex: 1;
        margin-bottom: 20px;
        line-height: 1.5;
        color: var(--text-medium)
      }

      .access-card .button {
        min-width: 120px
      }

      .plan-section {
        margin-bottom: 24px
      }

      .plan-card .card-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 20px;
        border-bottom: 1px solid var(--border-light);
        padding-bottom: 12px
      }

      .plan-card h3 {
        font-size: 1.15rem;
        display: flex;
        align-items: center
      }

      .plan-card h3 .icon {
        color: #FFD60A;
        font-size: 24px
      }

      .plan-details {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 20px 24px
      }

      .plan-details-label {
        display: block;
        color: var(--text-medium);
        margin-bottom: 4px;
        font-weight: 500;
        font-size: .85rem
      }

      .plan-details-value {
        font-size: .95rem;
        font-weight: 600;
        color: var(--text-dark)
      }

      .plan-details-value.status-active {
        color: var(--status-green);
        font-weight: 700
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

      .hero-kpi-section small {
        font-size: .85rem;
        margin-top: 10px;
        display: block
      }
    </style>

    <div class="container-fluid" style="padding:0;">
      <div class="erp-container">
        <aside class="sidebar">
          <div class="sidebar-header">FaCiencia</div>
          <nav class="sidebar-nav">
            <span class="nav-section-title">Navegação</span>
            <ul>
              <li><a href="<?php echo APP_URL; ?>/parceiro/dashboard-parceiro.php" class="active"><span
                    class="icon">dashboard</span> Dashboard</a></li>
            </ul>
            <span class="nav-section-title">Acadêmico</span>
            <ul>
              <li><a href="<?php echo APP_URL; ?>/parceiro/cursos-parceiro.php"><span class="icon">school</span>
                  Cursos</a></li>
              <li><a href="<?php echo APP_URL; ?>/parceiro/alunos-parceiro.php"><span class="icon">group</span>
                  Alunos</a></li>
            </ul>
            <span class="nav-section-title">Certificação</span>
            <ul>
              <li><a href="<?php echo APP_URL; ?>/parceiro/templates-parceiro.php"
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
              <li><a href="<?php echo APP_URL; ?>/perfil.php"><span class="icon">person</span> Meu Perfil</a></li>
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
            <h1>Painel Principal</h1>

            <!-- Aviso de Pagamento Pendente -->
            <?php if (!empty($assinatura) && in_array($assinatura['status'] ?? '', ['aguardando_pagamento', 'pendente'])): ?>
              <div
                style="background: linear-gradient(135deg, rgba(255, 159, 10, 0.15) 0%, rgba(255, 159, 10, 0.08) 100%); border: 2px solid rgba(255, 159, 10, 0.4); border-radius: 12px; padding: 20px 24px; margin-bottom: 24px; display: flex; align-items: flex-start; gap: 16px;">
                <div
                  style="width: 48px; height: 48px; background: linear-gradient(135deg, #FF9F0A 0%, #FFB340 100%); border-radius: 12px; display: flex; align-items: center; justify-content: center; flex-shrink: 0; box-shadow: 0 4px 12px rgba(255, 159, 10, 0.3);">
                  <span class="material-icons-outlined" style="font-size: 24px; color: white;">warning</span>
                </div>
                <div style="flex: 1;">
                  <strong style="color: #CC7F00; font-size: 16px; display: block; margin-bottom: 6px;">⚠️ Pagamento
                    Pendente</strong>
                  <p style="color: #1D1D1F; font-size: 14px; margin: 0 0 12px 0; line-height: 1.5;">
                    Você possui um plano ativo, mas <strong>não poderá emitir certificados</strong> até que o pagamento
                    seja confirmado.
                  </p>
                  <a href="<?php echo APP_URL; ?>/parceiro/primeiro-pagamento.php"
                    style="display: inline-flex; align-items: center; gap: 8px; background: linear-gradient(135deg, #FF9F0A 0%, #FFB340 100%); color: white; padding: 10px 20px; border-radius: 8px; font-weight: 600; font-size: 14px; text-decoration: none; box-shadow: 0 2px 8px rgba(255, 159, 10, 0.3); transition: all 0.2s ease;">
                    <span class="material-icons-outlined" style="font-size: 18px;">payment</span>
                    Realizar Pagamento Agora
                  </a>
                </div>
              </div>
            <?php endif; ?>

            <!-- Aviso de sessão (warning) -->
            <?php if (isset($_SESSION['warning'])): ?>
              <div
                style="background: linear-gradient(135deg, rgba(255, 159, 10, 0.1) 0%, rgba(255, 159, 10, 0.05) 100%); border: 2px solid rgba(255, 159, 10, 0.3); border-radius: 12px; padding: 16px 20px; margin-bottom: 20px; display: flex; align-items: center; gap: 12px;">
                <div
                  style="width: 40px; height: 40px; background: #FF9F0A; border-radius: 10px; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                  <span class="material-icons-outlined" style="font-size: 20px; color: white;">info</span>
                </div>
                <div style="flex: 1;">
                  <span
                    style="color: #1D1D1F; font-size: 14px;"><?php echo htmlspecialchars($_SESSION['warning']); ?></span>
                </div>
                <button onclick="this.parentElement.remove()"
                  style="background: none; border: none; color: #FF9F0A; cursor: pointer; padding: 4px; font-size: 20px; line-height: 1;">&times;</button>
              </div>
              <?php unset($_SESSION['warning']); ?>
            <?php endif; ?>

            <section class="hero-kpi-section">
              <h2><span class="icon">receipt_long</span>Certificados do Plano</h2>
              <?php
              $totais = (int) ($assinatura['certificados_totais'] ?? 0); // vem de planos.quantidade_certificados
              $usados_calc = (int) ($data['stats']['certificados_gerados'] ?? 0); // certificados emitidos reais
              $disponiveis = max(0, $totais - $usados_calc);
              if ($totais > 0 && $disponiveis > $totais) {
                $disponiveis = $totais;
              }
              $perc_disp = $totais > 0 ? min(100, ($disponiveis / $totais) * 100) : 0;
              ?>
              <div class="hero-kpi-usage">
                <span class="value"><?php echo $disponiveis; ?></span>
                <span class="limit">/ <?php echo $totais; ?></span>
              </div>
              <div class="progress-bar">
                <div class="progress" style="width: <?php echo number_format($perc_disp, 1, '.', ''); ?>%;"></div>
              </div>
              <small>Você gerou <?php echo $usados_calc; ?> de <?php echo $totais; ?> certificados disponíveis.
                Certificados deletados não diminuem o total gasto.</small>
            </section>

            <section class="secondary-kpi-grid">
              <div class="card kpi-card">
                <span class="kpi-label">Alunos Registrados</span>
                <span class="kpi-value"><?php echo (int) ($data['stats']['alunos_registrados'] ?? 0); ?></span>
              </div>
              <div class="card kpi-card">
                <span class="kpi-label">Cursos Ativos</span>
                <span class="kpi-value"><?php echo (int) ($data['stats']['cursos_ativos'] ?? 0); ?></span>
              </div>
              <div class="card kpi-card">
                <span class="kpi-label">Certificados Emitidos</span>
                <span class="kpi-value"><?php echo (int) ($usados_calc ?? 0); ?></span>
              </div>
            </section>

            <section class="quick-access-grid">
              <div class="card access-card">
                <span class="icon icon-ead">video_library</span>
                <h3>Módulo EAD</h3>
                <p>Gerencie o conteúdo das aulas, exercícios e acompanhe o progresso dos alunos.</p>
                <!-- EAD Temporariamente Desativado -->
                <button class="button button-primary" onclick="openEadModal()"
                  style="background: #E5E5E7; color: #86868B; cursor: pointer;">
                  Acessar EAD <span class="icon">lock</span>
                </button>
              </div>
              <div class="card access-card">
                <span class="icon icon-curso">add_circle_outline</span>
                <h3>Novo Curso</h3>
                <p>Crie e configure um novo curso, adicionando seus módulos e materiais didáticos.</p>
                <a class="button button-secondary" href="<?php echo APP_URL; ?>/parceiro/cursos-parceiro.php">Criar
                  Curso</a>
              </div>
              <div class="card access-card">
                <span class="icon icon-aluno">person_add_alt_1</span>
                <h3>Registrar Aluno</h3>
                <p>Adicione novos estudantes à sua plataforma e gerencie suas matrículas.</p>
                <a class="button button-secondary" href="<?php echo APP_URL; ?>/parceiro/alunos-parceiro.php">Adicionar
                  Aluno</a>
              </div>
            </section>

            <section class="plan-section">
              <div class="card plan-card">
                <div class="card-header">
                  <h3><span class="icon">price_check</span>Detalhes do Plano</h3>
                  <a href="<?php echo APP_URL; ?>/parceiro/meu-plano.php">Gerenciar Assinatura &gt;</a>
                </div>
                <?php if (!empty($assinatura)): ?>
                  <div class="plan-details">
                    <div><span class="plan-details-label">Plano Atual</span><span
                        class="plan-details-value"><?php echo htmlspecialchars($assinatura['plano_nome']); ?></span></div>
                    <div><span class="plan-details-label">Certificados Inclusos</span><span
                        class="plan-details-value"><?php echo (int) $assinatura['certificados_totais']; ?>
                        Certificados</span></div>
                    <div><span class="plan-details-label">Status</span><span
                        class="plan-details-value status-active">Ativo</span></div>
                    <div><span class="plan-details-label">Valor Total</span><span class="plan-details-value">R$
                        <?php echo number_format((float) $assinatura['valor'], 2, ',', '.'); ?></span></div>
                    <div><span class="plan-details-label">Contratado em</span><span
                        class="plan-details-value"><?php echo date('d/m/Y', strtotime($assinatura['data_inicio'])); ?></span>
                    </div>
                    <div><span class="plan-details-label">Vencimento</span><span
                        class="plan-details-value"><?php echo date('d/m/Y', strtotime($assinatura['data_vencimento'])); ?></span>
                    </div>
                  </div>
                <?php else: ?>
                  <div class="text-muted">Você não possui uma assinatura ativa no momento.</div>
                <?php endif; ?>
              </div>
            </section>

          </main>
        </div>
      </div>
    </div>
    <!-- /.container-fluid -->

  </div>
  <!-- End of Main Content -->

  <!-- Footer -->
  <footer class="sticky-footer bg-white">
    <div class="container my-auto">
      <div class="copyright text-center my-auto">
        <span>Copyright &copy; Sistema de Certificados 2025</span>
      </div>
    </div>
  </footer>
  <!-- End of Footer -->

</div>
<!-- End of Content Wrapper -->

<?php require_once '../app/views/footer.php'; ?>

<?php $conn->close(); ?>

<!-- Modal EAD Bloqueado -->
<div id="eadModal" class="modal"
  style="display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; overflow: auto; background-color: rgba(0,0,0,0.5); align-items: center; justify-content: center;">
  <div class="modal-content"
    style="background-color: #fefefe; padding: 30px; border-radius: 12px; width: 90%; max-width: 500px; text-align: center; position: relative; box-shadow: 0 4px 20px rgba(0,0,0,0.2);">
    <span class="close" onclick="closeEadModal()"
      style="position: absolute; top: 15px; right: 20px; color: #aaa; font-size: 28px; font-weight: bold; cursor: pointer;">&times;</span>

    <div style="margin-bottom: 20px;">
      <span class="icon" style="font-size: 64px; color: #FF9F0A;">lock</span>
    </div>

    <h2 style="margin-bottom: 15px; color: #1D1D1F;">Módulo Indisponível</h2>

    <p style="color: #6B6B6B; line-height: 1.6; margin-bottom: 25px;">
      Este módulo não está disponível no seu plano, pois no momento não vamos disponibilizar o EAD.
    </p>

    <button class="button button-primary" onclick="closeEadModal()" style="width: 100%;">Entendi</button>
  </div>
</div>

<script>
  function openEadModal() {
    const modal = document.getElementById('eadModal');
    modal.style.display = 'flex';
  }

  function closeEadModal() {
    const modal = document.getElementById('eadModal');
    modal.style.display = 'none';
  }

  // Fechar ao clicar fora do modal
  window.onclick = function (event) {
    const modal = document.getElementById('eadModal');
    if (event.target == modal) {
      modal.style.display = 'none';
    }
  }
</script>