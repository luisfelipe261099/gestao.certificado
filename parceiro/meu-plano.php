<?php
/**
 * ============================================================================
 * MEU PLANO - SISTEMA DE CERTIFICADOS
 * ============================================================================
 *
 * Esta página permite que o parceiro (empresa) visualize:
 * - Seu plano atual
 * - Informações de certificados (total, usados, disponíveis)
 * - Data de vencimento
 * - Opções para renovar ou mudar de plano
 *
 * Padrão MVP - Camada de Apresentação
 * ============================================================================
 */

require_once '../app/config/config.php';

// ============================================================================
// VERIFICAR AUTENTICAÇÃO E PERMISSÃO
// ============================================================================
if (!isAuthenticated() || !hasRole(ROLE_PARCEIRO)) {
  redirect(APP_URL . '/login.php');
}

// ============================================================================
// PREPARAR DADOS
// ============================================================================
$page_title = 'Meu Plano - ' . APP_NAME;
$user = getCurrentUser();
$conn = getDBConnection();
$parceiro_id = $user['parceiro_id'] ?? $user['id'];

// ============================================================================
// BUSCAR ASSINATURA DO PARCEIRO (qualquer status não cancelado/expirado)
// ============================================================================
$assinatura = [];
$stmt = $conn->prepare("
    SELECT
        a.id,
        a.data_inicio,
        a.data_vencimento,
        a.certificados_totais,
        a.certificados_usados,
        a.certificados_disponiveis,
        a.renovacao_automatica,
        a.status,
        pl.id as plano_id,
        pl.nome as plano_nome,
        pl.descricao,
        pl.valor,
        pl.quantidade_certificados,
        pl.limite_cursos,
        pl.limite_alunos,
        pl.limite_templates,
        pl.suporte_prioritario
    FROM assinaturas a
    JOIN planos pl ON a.plano_id = pl.id
    WHERE a.parceiro_id = ? 
      AND (a.status IS NULL OR a.status = '' OR a.status IN ('ativa', 'aguardando_pagamento', 'pendente'))
    ORDER BY a.criado_em DESC
    LIMIT 1
");

if ($stmt) {
  $stmt->bind_param("i", $parceiro_id);
  $stmt->execute();
  $result = $stmt->get_result();
  if ($row = $result->fetch_assoc()) {
    $assinatura = $row;
  }
  $stmt->close();
}

// ============================================================================
// BUSCAR TODOS OS PLANOS DISPONÍVEIS
// ============================================================================
$planos = [];
$stmt = $conn->prepare("
    SELECT id, nome, descricao, valor, quantidade_certificados, limite_cursos, limite_alunos, limite_templates, suporte_prioritario
    FROM planos
    WHERE ativo = 1
    ORDER BY valor ASC
");

if ($stmt) {
  $stmt->execute();
  $result = $stmt->get_result();
  while ($row = $result->fetch_assoc()) {
    $planos[] = $row;
  }
  $stmt->close();
}

// ============================================================================
// BUSCAR SOLICITAÇÕES PENDENTES
// ============================================================================
$solicitacao_pendente = null;
$stmt = $conn->prepare("
    SELECT id, plano_novo_id, status, criado_em
    FROM solicitacoes_planos
    WHERE parceiro_id = ? AND status = 'pendente'
    LIMIT 1
");

if ($stmt) {
  $stmt->bind_param("i", $parceiro_id);
  $stmt->execute();
  $result = $stmt->get_result();
  if ($row = $result->fetch_assoc()) {
    $solicitacao_pendente = $row;
  }
  $stmt->close();
}

$conn->close();

// ============================================================================
// CALCULAR DIAS PARA VENCIMENTO
// ============================================================================
$dias_para_vencimento = 0;
if (!empty($assinatura)) {
  $data_venc = new DateTime($assinatura['data_vencimento']);
  $hoje = new DateTime();
  $intervalo = $hoje->diff($data_venc);
  $dias_para_vencimento = $intervalo->days;
  if ($intervalo->invert) {
    $dias_para_vencimento = -$dias_para_vencimento;
  }
}

// ============================================================================
// CALCULAR TOTAIS/USO DE CERTIFICADOS (com fallbacks e normalização)
// ============================================================================
$total_certificados = 0;
$usados = 0;
$disponiveis = 0;
$percentual_uso = 0;
$percentual_uso_display = '0';
if (!empty($assinatura)) {
  $total_certificados = (int) ($assinatura['certificados_totais'] ?? 0);
  if ($total_certificados <= 0) {
    $total_certificados = (int) ($assinatura['quantidade_certificados'] ?? 0);
  }
  $usados = max(0, (int) ($assinatura['certificados_usados'] ?? 0));
  $calc_disponiveis = $total_certificados - $usados;
  $disponiveis = isset($assinatura['certificados_disponiveis']) ? (int) $assinatura['certificados_disponiveis'] : $calc_disponiveis;
  if ($disponiveis < 0)
    $disponiveis = max(0, $calc_disponiveis);
  if ($disponiveis > $total_certificados)
    $disponiveis = $total_certificados;
  if ($total_certificados > 0) {
    $percentual_exato = ($usados / $total_certificados) * 100;
    $percentual_uso = min(100, max(0, round($percentual_exato)));

    // Para exibição: se há certificados usados mas percentual arredondado é 0, mostrar com decimal
    if ($usados > 0 && $percentual_uso == 0) {
      $percentual_uso_display = number_format($percentual_exato, 1, ',', '.');
    } else {
      $percentual_uso_display = (string) $percentual_uso;
    }
  }
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
              <li><a href="<?php echo APP_URL; ?>/parceiro/dashboard-parceiro.php"><span class="icon">dashboard</span>
                  Dashboard</a></li>
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
              <li><a href="<?php echo APP_URL; ?>/parceiro/meu-plano.php" class="active"><span
                    class="icon">price_check</span> Meu Plano</a></li>
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
              <?php echo htmlspecialchars($user['nome'] ?? ($user['email'] ?? 'Usuário')); ?>
            </div>
          </header>

          <main class="content-area">
            <!-- Cabeçalho Moderno -->
            <div
              style="background: linear-gradient(135deg, rgba(110, 65, 193, 0.08) 0%, rgba(139, 95, 214, 0.04) 100%); border-radius: 16px; padding: 28px 32px; margin-bottom: 28px; box-shadow: 0 2px 8px rgba(0, 0, 0, 0.06);">
              <div
                style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 20px;">
                <div style="display: flex; align-items: center; gap: 16px;">
                  <div
                    style="width: 56px; height: 56px; background: linear-gradient(135deg, #6E41C1 0%, #8B5FD6 100%); border-radius: 14px; display: flex; align-items: center; justify-content: center; box-shadow: 0 4px 12px rgba(110, 65, 193, 0.3);">
                    <span class="material-icons-outlined" style="font-size: 28px; color: white;">price_check</span>
                  </div>
                  <div>
                    <h1 style="font-size: 28px; font-weight: 700; color: #1D1D1F; margin: 0; line-height: 1.2;">Meu
                      Plano</h1>
                    <p style="color: #86868B; font-size: 14px; margin: 4px 0 0 0;">Gerencie sua assinatura e
                      certificados</p>
                  </div>
                </div>
              </div>
            </div>

            <!-- Alertas Modernos -->
            <?php if (isset($_SESSION['success'])): ?>
              <div
                style="background: linear-gradient(135deg, rgba(52, 199, 89, 0.1) 0%, rgba(52, 199, 89, 0.05) 100%); border: 2px solid rgba(52, 199, 89, 0.3); border-radius: 12px; padding: 16px 20px; margin-bottom: 20px; display: flex; align-items: center; gap: 12px; animation: slideDown 0.3s ease;">
                <div
                  style="width: 40px; height: 40px; background: #34C759; border-radius: 10px; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                  <span class="material-icons-outlined" style="font-size: 20px; color: white;">check_circle</span>
                </div>
                <div style="flex: 1;">
                  <strong style="color: #34C759; font-size: 14px; display: block; margin-bottom: 2px;">Sucesso!</strong>
                  <span
                    style="color: #1D1D1F; font-size: 14px;"><?php echo htmlspecialchars($_SESSION['success']); ?></span>
                </div>
                <button onclick="this.parentElement.remove()"
                  style="background: none; border: none; color: #34C759; cursor: pointer; padding: 4px; font-size: 20px; line-height: 1;">&times;</button>
              </div>
              <?php unset($_SESSION['success']); ?>
            <?php endif; ?>

            <?php if (isset($_SESSION['error'])): ?>
              <div
                style="background: linear-gradient(135deg, rgba(255, 59, 48, 0.1) 0%, rgba(255, 59, 48, 0.05) 100%); border: 2px solid rgba(255, 59, 48, 0.3); border-radius: 12px; padding: 16px 20px; margin-bottom: 20px; display: flex; align-items: center; gap: 12px; animation: slideDown 0.3s ease;">
                <div
                  style="width: 40px; height: 40px; background: #FF3B30; border-radius: 10px; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                  <span class="material-icons-outlined" style="font-size: 20px; color: white;">error</span>
                </div>
                <div style="flex: 1;">
                  <strong style="color: #FF3B30; font-size: 14px; display: block; margin-bottom: 2px;">Erro!</strong>
                  <span
                    style="color: #1D1D1F; font-size: 14px;"><?php echo htmlspecialchars($_SESSION['error']); ?></span>
                </div>
                <button onclick="this.parentElement.remove()"
                  style="background: none; border: none; color: #FF3B30; cursor: pointer; padding: 4px; font-size: 20px; line-height: 1;">&times;</button>
              </div>
              <?php unset($_SESSION['error']); ?>
            <?php endif; ?>

            <!-- Aviso de Pagamento Pendente -->
            <?php if (!empty($assinatura) && in_array($assinatura['status'], ['aguardando_pagamento', 'pendente'])): ?>
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

            <!-- Solicitação Pendente -->
            <?php if ($solicitacao_pendente): ?>
              <div
                style="background: linear-gradient(135deg, rgba(110, 65, 193, 0.1) 0%, rgba(110, 65, 193, 0.05) 100%); border: 2px solid rgba(110, 65, 193, 0.3); border-radius: 12px; padding: 16px 20px; margin-bottom: 20px; display: flex; align-items: center; gap: 12px; animation: slideDown 0.3s ease;">
                <div
                  style="width: 40px; height: 40px; background: #6E41C1; border-radius: 10px; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                  <span class="material-icons-outlined" style="font-size: 20px; color: white;">info</span>
                </div>
                <div style="flex: 1;">
                  <strong style="color: #6E41C1; font-size: 14px; display: block; margin-bottom: 2px;">Solicitação
                    Pendente!</strong>
                  <span style="color: #1D1D1F; font-size: 14px;">Você tem uma solicitação de mudança de plano aguardando
                    aprovação do administrativo.</span>
                </div>
                <button onclick="this.parentElement.remove()"
                  style="background: none; border: none; color: #6E41C1; cursor: pointer; padding: 4px; font-size: 20px; line-height: 1;">&times;</button>
              </div>
            <?php endif; ?>

            <!-- Plano Atual -->
            <?php if (!empty($assinatura)): ?>
              <div style="display: grid; grid-template-columns: 1fr 380px; gap: 24px; margin-bottom: 24px;">
                <!-- Card Principal do Plano -->
                <div
                  style="background: white; border-radius: 16px; padding: 28px; box-shadow: 0 2px 12px rgba(0, 0, 0, 0.08); border: 1px solid #E5E5E7;">
                  <!-- Header do Card -->
                  <div
                    style="background: linear-gradient(135deg, #6E41C1 0%, #8B5FD6 100%); border-radius: 12px; padding: 20px 24px; margin-bottom: 24px;">
                    <div style="display: flex; align-items: center; gap: 12px;">
                      <span class="material-icons-outlined" style="font-size: 24px; color: white;">star</span>
                      <h2 style="color: white; font-size: 20px; font-weight: 700; margin: 0;">Plano Atual:
                        <?php echo htmlspecialchars($assinatura['plano_nome']); ?>
                      </h2>
                    </div>
                  </div>

                  <div>
                    <!-- Descrição e Valor -->
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 24px;">
                      <div>
                        <p style="font-weight: 600; color: #86868B; font-size: 13px; margin-bottom: 6px;">Descrição:</p>
                        <p style="color: #1D1D1F; font-size: 14px; line-height: 1.5;">
                          <?php echo htmlspecialchars($assinatura['descricao'] ?? 'Sem descrição'); ?>
                        </p>
                      </div>
                      <div>
                        <p style="font-weight: 600; color: #86868B; font-size: 13px; margin-bottom: 6px;">Valor Mensal:
                        </p>
                        <p style="color: #34C759; font-size: 24px; font-weight: 700; margin: 0;">R$
                          <?php echo number_format($assinatura['valor'], 2, ',', '.'); ?>
                        </p>
                      </div>
                    </div>

                    <div style="height: 1px; background: #E5E5E7; margin: 24px 0;"></div>

                    <!-- Cards de Certificados -->
                    <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 16px; margin-bottom: 24px;">
                      <!-- Disponíveis -->
                      <div
                        style="background: linear-gradient(135deg, rgba(110, 65, 193, 0.05) 0%, rgba(110, 65, 193, 0.02) 100%); border-left: 4px solid #6E41C1; border-radius: 12px; padding: 20px; text-align: center;">
                        <p style="font-weight: 600; color: #86868B; font-size: 13px; margin-bottom: 8px;">Certificados
                          Disponíveis</p>
                        <h3 style="color: #6E41C1; font-size: 32px; font-weight: 700; margin: 0;">
                          <?php echo (int) $disponiveis; ?>
                        </h3>
                        <small style="color: #86868B; font-size: 12px;">de
                          <?php echo (int) $total_certificados; ?></small>
                      </div>

                      <!-- Usados -->
                      <div
                        style="background: linear-gradient(135deg, rgba(110, 65, 193, 0.05) 0%, rgba(110, 65, 193, 0.02) 100%); border-left: 4px solid #6E41C1; border-radius: 12px; padding: 20px; text-align: center;">
                        <p style="font-weight: 600; color: #86868B; font-size: 13px; margin-bottom: 8px;">Certificados
                          Usados</p>
                        <h3 style="color: #6E41C1; font-size: 32px; font-weight: 700; margin: 0;">
                          <?php echo (int) $usados; ?>
                        </h3>
                        <small style="color: #86868B; font-size: 12px;"><?php echo $percentual_uso_display; ?>% do
                          total</small>
                      </div>

                      <!-- Vencimento -->
                      <div
                        style="background: linear-gradient(135deg, rgba(110, 65, 193, 0.05) 0%, rgba(110, 65, 193, 0.02) 100%); border-left: 4px solid <?php echo $dias_para_vencimento <= 7 ? '#FF3B30' : '#6E41C1'; ?>; border-radius: 12px; padding: 20px; text-align: center;">
                        <p style="font-weight: 600; color: #86868B; font-size: 13px; margin-bottom: 8px;">Vencimento</p>
                        <h3
                          style="color: <?php echo $dias_para_vencimento <= 7 ? '#FF3B30' : '#6E41C1'; ?>; font-size: 18px; font-weight: 700; margin: 0;">
                          <?php echo date('d/m/Y', strtotime($assinatura['data_vencimento'])); ?>
                        </h3>
                        <small style="color: #86868B; font-size: 12px;"><?php echo $dias_para_vencimento; ?> dias</small>
                      </div>
                    </div>

                    <div style="height: 1px; background: #E5E5E7; margin: 24px 0;"></div>

                    <!-- Barra de Progresso -->
                    <div style="margin-bottom: 24px;">
                      <div
                        style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px;">
                        <span style="font-weight: 600; color: #1D1D1F; font-size: 14px;">Uso de Certificados</span>
                        <span
                          style="font-weight: 700; color: #6E41C1; font-size: 16px;"><?php echo $percentual_uso_display; ?>%</span>
                      </div>
                      <div style="width: 100%; height: 12px; background: #E5E5E7; border-radius: 6px; overflow: hidden;">
                        <div
                          style="width: <?php echo $percentual_uso; ?>%; height: 100%; background: linear-gradient(135deg, #6E41C1 0%, #8B5FD6 100%); transition: width 0.3s ease;">
                        </div>
                      </div>
                    </div>

                    <div style="height: 1px; background: #E5E5E7; margin: 24px 0;"></div>

                    <!-- Limites do Plano -->
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 24px;">
                      <div>
                        <p style="margin-bottom: 12px;"><strong style="color: #1D1D1F;">Limite de Cursos:</strong> <span
                            style="color: #6E41C1; font-weight: 600;"><?php echo $assinatura['limite_cursos'] ?? 'Ilimitado'; ?></span>
                        </p>
                        <p style="margin-bottom: 12px;"><strong style="color: #1D1D1F;">Limite de Alunos:</strong> <span
                            style="color: #6E41C1; font-weight: 600;"><?php echo $assinatura['limite_alunos'] ?? 'Ilimitado'; ?></span>
                        </p>
                      </div>
                      <div>
                        <p style="margin-bottom: 12px;"><strong style="color: #1D1D1F;">Limite de Templates:</strong>
                          <span
                            style="color: #6E41C1; font-weight: 600;"><?php echo $assinatura['limite_templates'] ?? 'Ilimitado'; ?></span>
                        </p>
                        <p style="margin-bottom: 12px;"><strong style="color: #1D1D1F;">Suporte Prioritário:</strong>
                          <?php echo $assinatura['suporte_prioritario'] ? '<span style="background: #34C759; color: white; padding: 4px 12px; border-radius: 6px; font-size: 12px; font-weight: 600;">Sim</span>' : '<span style="background: #86868B; color: white; padding: 4px 12px; border-radius: 6px; font-size: 12px; font-weight: 600;">Não</span>'; ?>
                        </p>
                      </div>
                    </div>

                    <div style="height: 1px; background: #E5E5E7; margin: 24px 0;"></div>

                    <!-- Botões de Ação -->
                    <div style="display: flex; gap: 12px;">
                      <button data-toggle="modal" data-target="#renovarPlano"
                        style="background: linear-gradient(135deg, #6E41C1 0%, #8B5FD6 100%); color: white; border: none; border-radius: 10px; padding: 12px 24px; font-weight: 600; cursor: pointer; transition: all 0.2s ease; display: inline-flex; align-items: center; gap: 8px; box-shadow: 0 4px 12px rgba(110, 65, 193, 0.3);"
                        onmouseover="this.style.transform='translateY(-2px)'; this.style.boxShadow='0 6px 20px rgba(110, 65, 193, 0.4)'"
                        onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 4px 12px rgba(110, 65, 193, 0.3)'">
                        <span class="material-icons-outlined" style="font-size: 18px;">sync</span>
                        Renovar Plano
                      </button>
                      <button data-toggle="modal" data-target="#mudarPlano"
                        style="background: linear-gradient(135deg, rgba(110, 65, 193, 0.1) 0%, rgba(110, 65, 193, 0.05) 100%); color: #6E41C1; border: 2px solid rgba(110, 65, 193, 0.3); border-radius: 10px; padding: 12px 24px; font-weight: 600; cursor: pointer; transition: all 0.2s ease; display: inline-flex; align-items: center; gap: 8px;"
                        onmouseover="this.style.background='#6E41C1'; this.style.color='white'; this.style.borderColor='#6E41C1'"
                        onmouseout="this.style.background='linear-gradient(135deg, rgba(110, 65, 193, 0.1) 0%, rgba(110, 65, 193, 0.05) 100%)'; this.style.color='#6E41C1'; this.style.borderColor='rgba(110, 65, 193, 0.3)'">
                        <span class="material-icons-outlined" style="font-size: 18px;">swap_horiz</span>
                        Mudar de Plano
                      </button>
                    </div>
                  </div>
                </div>

                <!-- Resumo Lateral -->
                <div
                  style="background: white; border-radius: 16px; padding: 24px; box-shadow: 0 2px 12px rgba(0, 0, 0, 0.08); border: 1px solid #E5E5E7;">
                  <!-- Header -->
                  <div
                    style="background: linear-gradient(135deg, #6E41C1 0%, #8B5FD6 100%); border-radius: 12px; padding: 16px 20px; margin-bottom: 20px;">
                    <h3
                      style="color: white; font-size: 16px; font-weight: 700; margin: 0; display: flex; align-items: center; gap: 8px;">
                      <span class="material-icons-outlined" style="font-size: 20px;">summarize</span>
                      Resumo da Assinatura
                    </h3>
                  </div>

                  <!-- Conteúdo -->
                  <div style="margin-bottom: 16px;">
                    <p style="font-weight: 600; color: #86868B; font-size: 13px; margin-bottom: 6px;">Status:</p>
                    <span
                      style="background: #34C759; color: white; padding: 6px 14px; border-radius: 8px; font-size: 13px; font-weight: 600; display: inline-block;">Ativa</span>
                  </div>

                  <div style="margin-bottom: 16px;">
                    <p style="font-weight: 600; color: #86868B; font-size: 13px; margin-bottom: 6px;">Data de Início:</p>
                    <p style="color: #1D1D1F; font-size: 14px; font-weight: 600; margin: 0;">
                      <?php echo date('d/m/Y', strtotime($assinatura['data_inicio'])); ?>
                    </p>
                  </div>

                  <div style="margin-bottom: 16px;">
                    <p style="font-weight: 600; color: #86868B; font-size: 13px; margin-bottom: 6px;">Data de Vencimento:
                    </p>
                    <p style="color: #1D1D1F; font-size: 14px; font-weight: 600; margin: 0;">
                      <?php echo date('d/m/Y', strtotime($assinatura['data_vencimento'])); ?>
                    </p>
                  </div>

                  <div style="margin-bottom: 20px;">
                    <p style="font-weight: 600; color: #86868B; font-size: 13px; margin-bottom: 6px;">Renovação
                      Automática:</p>
                    <?php echo $assinatura['renovacao_automatica'] ? '<span style="background: #34C759; color: white; padding: 6px 14px; border-radius: 8px; font-size: 13px; font-weight: 600; display: inline-block;">Ativada</span>' : '<span style="background: #FF9500; color: white; padding: 6px 14px; border-radius: 8px; font-size: 13px; font-weight: 600; display: inline-block;">Desativada</span>'; ?>
                  </div>

                  <div style="height: 1px; background: #E5E5E7; margin: 20px 0;"></div>

                  <div
                    style="background: linear-gradient(135deg, rgba(110, 65, 193, 0.05) 0%, rgba(110, 65, 193, 0.02) 100%); border: 2px solid rgba(110, 65, 193, 0.2); border-radius: 10px; padding: 12px 16px; display: flex; align-items: start; gap: 10px;">
                    <span class="material-icons-outlined"
                      style="font-size: 18px; color: #6E41C1; flex-shrink: 0;">info</span>
                    <p style="color: #1D1D1F; font-size: 12px; margin: 0; line-height: 1.5;">
                      Sua assinatura será renovada automaticamente em
                      <?php echo date('d/m/Y', strtotime($assinatura['data_vencimento'])); ?>.
                    </p>
                  </div>
                </div>
              </div>
            <?php else: ?>
              <div
                style="background: linear-gradient(135deg, rgba(255, 149, 0, 0.1) 0%, rgba(255, 149, 0, 0.05) 100%); border: 2px solid rgba(255, 149, 0, 0.3); border-radius: 12px; padding: 16px 20px; display: flex; align-items: center; gap: 12px;">
                <span class="material-icons-outlined" style="font-size: 24px; color: #FF9500;">warning</span>
                <div>
                  <strong style="color: #FF9500; font-size: 14px;">Você não possui um plano ativo no momento.</strong>
                  <a href="<?php echo APP_URL; ?>/admin/planos-admin.php"
                    style="color: #6E41C1; font-weight: 600; text-decoration: underline;">Contratar um plano</a>
                </div>
              </div>
            <?php endif; ?>

            <!-- Outros Planos Disponíveis -->
            <div
              style="background: white; border-radius: 16px; padding: 28px; box-shadow: 0 2px 12px rgba(0, 0, 0, 0.08); border: 1px solid #E5E5E7; margin-top: 24px;">
              <!-- Header -->
              <div
                style="background: linear-gradient(135deg, #6E41C1 0%, #8B5FD6 100%); border-radius: 12px; padding: 20px 24px; margin-bottom: 24px;">
                <h3
                  style="color: white; font-size: 18px; font-weight: 700; margin: 0; display: flex; align-items: center; gap: 10px;">
                  <span class="material-icons-outlined" style="font-size: 22px;">grid_view</span>
                  Outros Planos Disponíveis
                </h3>
              </div>

              <!-- Grid de Planos -->
              <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 20px;">
                <?php foreach ($planos as $plano): ?>
                  <?php if (empty($assinatura) || $plano['id'] != $assinatura['plano_id']): ?>
                    <div
                      style="background: linear-gradient(135deg, rgba(110, 65, 193, 0.03) 0%, rgba(110, 65, 193, 0.01) 100%); border: 2px solid #E5E5E7; border-radius: 14px; padding: 24px; transition: all 0.3s ease; cursor: pointer;"
                      onmouseover="this.style.borderColor='#6E41C1'; this.style.transform='translateY(-4px)'; this.style.boxShadow='0 8px 20px rgba(110, 65, 193, 0.15)'"
                      onmouseout="this.style.borderColor='#E5E5E7'; this.style.transform='translateY(0)'; this.style.boxShadow='none'">

                      <h4 style="color: #1D1D1F; font-size: 20px; font-weight: 700; margin-bottom: 8px;">
                        <?php echo htmlspecialchars($plano['nome']); ?>
                      </h4>
                      <p style="color: #86868B; font-size: 13px; margin-bottom: 16px; line-height: 1.5;">
                        <?php echo htmlspecialchars($plano['descricao'] ?? ''); ?>
                      </p>

                      <div style="margin-bottom: 20px;">
                        <span style="color: #34C759; font-size: 32px; font-weight: 700;">R$
                          <?php echo number_format($plano['valor'], 2, ',', '.'); ?></span>
                        <span style="color: #86868B; font-size: 14px;">/mês</span>
                      </div>

                      <ul style="list-style: none; padding: 0; margin: 0 0 20px 0;">
                        <li style="display: flex; align-items: center; gap: 8px; margin-bottom: 10px;">
                          <span class="material-icons-outlined" style="font-size: 18px; color: #34C759;">check_circle</span>
                          <span
                            style="color: #1D1D1F; font-size: 13px;"><?php echo (int) $plano['quantidade_certificados']; ?>
                            certificados</span>
                        </li>
                        <li style="display: flex; align-items: center; gap: 8px; margin-bottom: 10px;">
                          <span class="material-icons-outlined" style="font-size: 18px; color: #34C759;">check_circle</span>
                          <span
                            style="color: #1D1D1F; font-size: 13px;"><?php echo $plano['limite_cursos'] ?? 'Ilimitado'; ?>
                            cursos</span>
                        </li>
                        <li style="display: flex; align-items: center; gap: 8px; margin-bottom: 10px;">
                          <span class="material-icons-outlined" style="font-size: 18px; color: #34C759;">check_circle</span>
                          <span
                            style="color: #1D1D1F; font-size: 13px;"><?php echo $plano['limite_alunos'] ?? 'Ilimitado'; ?>
                            alunos</span>
                        </li>
                        <?php if ($plano['suporte_prioritario']): ?>
                          <li style="display: flex; align-items: center; gap: 8px; margin-bottom: 10px;">
                            <span class="material-icons-outlined" style="font-size: 18px; color: #34C759;">check_circle</span>
                            <span style="color: #1D1D1F; font-size: 13px;">Suporte Prioritário</span>
                          </li>
                        <?php endif; ?>
                      </ul>
                    </div>
                  <?php endif; ?>
                <?php endforeach; ?>
              </div>
            </div>

          </main>
        </div>
      </div>
    </div>

  </div>
</div>

<!-- Modal Renovar Plano -->
<div class="modal fade" id="renovarPlano" tabindex="-1" role="dialog" aria-hidden="true">
  <div class="modal-dialog" role="document" style="max-width: 500px;">
    <div class="modal-content"
      style="border: none; border-radius: 16px; overflow: hidden; box-shadow: 0 8px 32px rgba(0, 0, 0, 0.12);">
      <!-- Header -->
      <div style="background: linear-gradient(135deg, #6E41C1 0%, #8B5FD6 100%); padding: 24px 28px; border: none;">
        <div style="display: flex; align-items: center; justify-content: space-between;">
          <div style="display: flex; align-items: center; gap: 12px;">
            <div
              style="width: 44px; height: 44px; background: rgba(255, 255, 255, 0.2); border-radius: 12px; display: flex; align-items: center; justify-content: center;">
              <span class="material-icons-outlined" style="font-size: 24px; color: white;">sync</span>
            </div>
            <h5 style="color: white; font-weight: 700; font-size: 20px; margin: 0;">Renovar Plano</h5>
          </div>
          <button type="button" class="close" data-dismiss="modal" aria-label="Close"
            style="color: white; opacity: 1; font-size: 28px; font-weight: 300; text-shadow: none; padding: 0; margin: 0; background: none; border: none; cursor: pointer; transition: all 0.2s ease;"
            onmouseover="this.style.transform='rotate(90deg)'" onmouseout="this.style.transform='rotate(0deg)'">
            <span aria-hidden="true">&times;</span>
          </button>
        </div>
      </div>

      <form method="POST" action="../app/actions/renovar-plano.php">
        <div style="padding: 28px;">
          <p style="color: #1D1D1F; font-size: 14px; margin-bottom: 16px;">
            Você está prestes a renovar seu plano <strong
              style="color: #6E41C1;"><?php echo htmlspecialchars($assinatura['plano_nome'] ?? ''); ?></strong> por mais
            30 dias.
          </p>
          <p style="color: #1D1D1F; font-size: 14px; margin-bottom: 20px;">
            <strong>Valor:</strong> <span style="color: #34C759; font-size: 18px; font-weight: 700;">R$
              <?php echo number_format($assinatura['valor'] ?? 0, 2, ',', '.'); ?></span>
          </p>

          <div
            style="background: linear-gradient(135deg, rgba(110, 65, 193, 0.05) 0%, rgba(110, 65, 193, 0.02) 100%); border: 2px solid rgba(110, 65, 193, 0.2); border-radius: 10px; padding: 12px 16px; display: flex; align-items: start; gap: 10px;">
            <span class="material-icons-outlined" style="font-size: 18px; color: #6E41C1; flex-shrink: 0;">info</span>
            <p style="color: #1D1D1F; font-size: 13px; margin: 0; line-height: 1.5;">
              Ao confirmar, geraremos automaticamente a fatura e o boleto no Asaas para você pagar agora.
              Se preferir PIX ou Cartão (com parcelamento), clique em "Escolher outra forma de pagamento".
            </p>
          </div>
        </div>

        <div
          style="padding: 20px 28px; background: #F5F5F7; border-top: 1px solid #E5E5E7; display: flex; gap: 12px; justify-content: flex-end; flex-wrap: wrap;">
          <a href="escolher-pagamento.php" class="btn"
            style="background: #FFFFFF; color: #6E41C1; border: 2px solid #6E41C1; border-radius: 10px; padding: 10px 24px; font-weight: 600;">
            <span class="material-icons-outlined"
              style="font-size: 16px; vertical-align: middle; margin-right: 4px;">payments</span>
            Escolher outra forma de pagamento
          </a>
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
              style="font-size: 16px; vertical-align: middle; margin-right: 4px;">sync</span>
            Renovar Plano (Boleto)
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Modal Mudar de Plano -->
<div class="modal fade" id="mudarPlano" tabindex="-1" role="dialog" aria-hidden="true">
  <div class="modal-dialog" role="document" style="max-width: 500px;">
    <div class="modal-content"
      style="border: none; border-radius: 16px; overflow: hidden; box-shadow: 0 8px 32px rgba(0, 0, 0, 0.12);">
      <!-- Header -->
      <div style="background: linear-gradient(135deg, #6E41C1 0%, #8B5FD6 100%); padding: 24px 28px; border: none;">
        <div style="display: flex; align-items: center; justify-content: space-between;">
          <div style="display: flex; align-items: center; gap: 12px;">
            <div
              style="width: 44px; height: 44px; background: rgba(255, 255, 255, 0.2); border-radius: 12px; display: flex; align-items: center; justify-content: center;">
              <span class="material-icons-outlined" style="font-size: 24px; color: white;">swap_horiz</span>
            </div>
            <h5 style="color: white; font-weight: 700; font-size: 20px; margin: 0;">Mudar de Plano</h5>
          </div>
          <button type="button" class="close" data-dismiss="modal" aria-label="Close"
            style="color: white; opacity: 1; font-size: 28px; font-weight: 300; text-shadow: none; padding: 0; margin: 0; background: none; border: none; cursor: pointer; transition: all 0.2s ease;"
            onmouseover="this.style.transform='rotate(90deg)'" onmouseout="this.style.transform='rotate(0deg)'">
            <span aria-hidden="true">&times;</span>
          </button>
        </div>
      </div>

      <form method="POST" action="../app/actions/mudar-plano.php">
        <div style="padding: 28px;">
          <div style="margin-bottom: 20px;">
            <label for="novo_plano"
              style="font-weight: 600; color: #1D1D1F; font-size: 14px; margin-bottom: 8px; display: flex; align-items: center; gap: 6px;">
              <span class="material-icons-outlined" style="font-size: 18px; color: #6E41C1;">price_check</span>
              Selecione um novo plano <span style="color: #FF3B30;">*</span>
            </label>
            <select class="form-control" id="novo_plano" name="novo_plano" required
              style="border: 2px solid #E5E5E7; border-radius: 10px; padding: 10px 14px; font-size: 14px; transition: all 0.2s ease;"
              onfocus="this.style.borderColor='#6E41C1'; this.style.boxShadow='0 0 0 3px rgba(110, 65, 193, 0.1)'"
              onblur="this.style.borderColor='#E5E5E7'; this.style.boxShadow='none'">
              <option value="">Escolha um plano</option>
              <?php foreach ($planos as $pl): ?>
                <?php if (empty($assinatura) || $pl['id'] != $assinatura['plano_id']): ?>
                  <option value="<?php echo (int) $pl['id']; ?>">
                    <?php echo htmlspecialchars($pl['nome']); ?> - R$
                    <?php echo number_format($pl['valor'], 2, ',', '.'); ?>/mês
                  </option>
                <?php endif; ?>
              <?php endforeach; ?>
            </select>
          </div>

          <div
            style="background: linear-gradient(135deg, rgba(110, 65, 193, 0.05) 0%, rgba(110, 65, 193, 0.02) 100%); border: 2px solid rgba(110, 65, 193, 0.2); border-radius: 10px; padding: 12px 16px; display: flex; align-items: start; gap: 10px;">
            <span class="material-icons-outlined" style="font-size: 18px; color: #6E41C1; flex-shrink: 0;">info</span>
            <p style="color: #1D1D1F; font-size: 13px; margin: 0; line-height: 1.5;">
              Sua solicitação será enviada para aprovação do administrativo.
            </p>
          </div>
        </div>

        <div
          style="padding: 20px 28px; background: #F5F5F7; border-top: 1px solid #E5E5E7; display: flex; gap: 12px; justify-content: flex-end;">
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
              style="font-size: 16px; vertical-align: middle; margin-right: 4px;">send</span>
            Solicitar Mudança
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

<?php require_once '../app/views/footer.php'; ?>