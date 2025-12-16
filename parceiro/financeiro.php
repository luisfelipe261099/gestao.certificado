<?php
/**
 * Financeiro do Parceiro - Faturas e Boletos
 */
require_once '../app/config/config.php';

if (!isAuthenticated() || !hasRole(ROLE_PARCEIRO)) {
    redirect(APP_URL . '/login.php');
}

$page_title = 'Meu Financeiro - ' . APP_NAME;
$conn = getDBConnection();
$user = getCurrentUser();
$parceiro_id = $user['parceiro_id'] ?? $user['id'];

// Faturas do parceiro
$faturas = [];
$stmt = $conn->prepare("SELECT id, numero_fatura, descricao, valor, data_emissao, data_vencimento, status FROM faturas WHERE parceiro_id = ? ORDER BY data_vencimento DESC");
$stmt->bind_param('i', $parceiro_id);
$stmt->execute();
$res = $stmt->get_result();
while ($row = $res->fetch_assoc()) { $faturas[] = $row; }
$stmt->close();

// Boletos do parceiro
$boletos = [];
$stmt = $conn->prepare("SELECT id, fatura_id, valor, data_vencimento, status, url_boleto, linha_digitavel, descricao FROM asaas_boletos WHERE parceiro_id = ? ORDER BY data_vencimento DESC");
$stmt->bind_param('i', $parceiro_id);
$stmt->execute();
$res = $stmt->get_result();
while ($row = $res->fetch_assoc()) { $boletos[] = $row; }
$stmt->close();
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
              <li><a href="<?php echo APP_URL; ?>/parceiro/cursos-parceiro.php"><span class="icon">school</span> Cursos</a></li>
              <li><a href="<?php echo APP_URL; ?>/parceiro/alunos-parceiro.php"><span class="icon">group</span> Alunos</a></li>
            </ul>
            <span class="nav-section-title">Certificação</span>
            <ul>
              <li><a href="<?php echo APP_URL; ?>/parceiro/templates-parceiro.php" title="Templates de Certificados"><span class="icon">article</span> Templates</a></li>
              <li><a href="<?php echo APP_URL; ?>/parceiro/gerar-certificados.php"><span class="icon">workspace_premium</span> Emitir Cert.</a></li>
            </ul>
            <span class="nav-section-title">Minha Conta</span>
            <ul>
              <li><a href="<?php echo APP_URL; ?>/parceiro/financeiro.php" class="active"><span class="icon">credit_card</span> Financeiro</a></li>
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
            <!-- Cabeçalho da Página -->
            <div style="background: linear-gradient(135deg, rgba(110, 65, 193, 0.03) 0%, rgba(110, 65, 193, 0.01) 100%); border-radius: 16px; padding: 28px 32px; margin-bottom: 32px; border: 1px solid rgba(110, 65, 193, 0.08);">
                <div class="d-flex align-items-center justify-content-between flex-wrap" style="gap: 20px;">
                    <div style="flex: 1; min-width: 250px;">
                        <div style="display: flex; align-items: center; gap: 16px; margin-bottom: 8px;">
                            <div style="width: 56px; height: 56px; background: linear-gradient(135deg, #6E41C1 0%, #8B5FD6 100%); border-radius: 14px; display: flex; align-items: center; justify-content: center; box-shadow: 0 4px 12px rgba(110, 65, 193, 0.25);">
                                <span class="material-icons-outlined" style="font-size: 32px; color: white;">credit_card</span>
                            </div>
                            <div>
                                <h1 style="font-size: 28px; font-weight: 700; color: #1D1D1F; margin: 0; letter-spacing: -0.5px;">Meu Financeiro</h1>
                                <p style="color: #86868B; font-size: 14px; margin: 4px 0 0 0;">Gerencie suas faturas e boletos</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Cards de Estatísticas -->
            <div class="row mb-4">
                <div class="col-xl-3 col-md-6 mb-4">
                    <div style="background: white; border-left: 4px solid #6E41C1; border-radius: 12px; padding: 20px 24px; box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08); transition: all 0.3s ease; cursor: pointer;"
                         onmouseover="this.style.transform='translateY(-4px)'; this.style.boxShadow='0 8px 16px rgba(110, 65, 193, 0.15)'"
                         onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 2px 8px rgba(0, 0, 0, 0.08)'">
                        <div style="display: flex; align-items: center; gap: 16px;">
                            <div style="width: 48px; height: 48px; background: linear-gradient(135deg, rgba(110, 65, 193, 0.1) 0%, rgba(110, 65, 193, 0.05) 100%); border-radius: 12px; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                                <span class="material-icons-outlined" style="font-size: 24px; color: #6E41C1;">receipt_long</span>
                            </div>
                            <div style="flex: 1;">
                                <p style="color: #86868B; font-size: 12px; font-weight: 600; margin: 0 0 4px 0; text-transform: uppercase; letter-spacing: 0.5px;">Total de Faturas</p>
                                <h3 style="color: #1D1D1F; font-size: 28px; font-weight: 700; margin: 0;"><?php echo count($faturas); ?></h3>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-xl-3 col-md-6 mb-4">
                    <div style="background: white; border-left: 4px solid #6E41C1; border-radius: 12px; padding: 20px 24px; box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08); transition: all 0.3s ease; cursor: pointer;"
                         onmouseover="this.style.transform='translateY(-4px)'; this.style.boxShadow='0 8px 16px rgba(110, 65, 193, 0.15)'"
                         onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 2px 8px rgba(0, 0, 0, 0.08)'">
                        <div style="display: flex; align-items: center; gap: 16px;">
                            <div style="width: 48px; height: 48px; background: linear-gradient(135deg, rgba(110, 65, 193, 0.1) 0%, rgba(110, 65, 193, 0.05) 100%); border-radius: 12px; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                                <span class="material-icons-outlined" style="font-size: 24px; color: #6E41C1;">qr_code_2</span>
                            </div>
                            <div style="flex: 1;">
                                <p style="color: #86868B; font-size: 12px; font-weight: 600; margin: 0 0 4px 0; text-transform: uppercase; letter-spacing: 0.5px;">Total de Boletos</p>
                                <h3 style="color: #1D1D1F; font-size: 28px; font-weight: 700; margin: 0;"><?php echo count($boletos); ?></h3>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Alertas -->
            <?php if (isset($_SESSION['success'])): ?>
                <div style="background: linear-gradient(135deg, rgba(52, 199, 89, 0.05) 0%, rgba(52, 199, 89, 0.02) 100%); border: 2px solid rgba(52, 199, 89, 0.2); border-radius: 12px; padding: 16px 20px; margin-bottom: 24px; display: flex; align-items: center; gap: 12px; animation: slideInDown 0.4s ease;">
                    <div style="width: 40px; height: 40px; background: linear-gradient(135deg, rgba(52, 199, 89, 0.1) 0%, rgba(52, 199, 89, 0.05) 100%); border-radius: 50%; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                        <span class="material-icons-outlined" style="font-size: 24px; color: #34C759;">check_circle</span>
                    </div>
                    <div style="flex: 1;">
                        <p style="color: #1D1D1F; font-size: 14px; margin: 0; font-weight: 500;"><?php echo htmlspecialchars($_SESSION['success']); ?></p>
                    </div>
                    <button type="button" onclick="this.parentElement.style.display='none'" style="background: none; border: none; color: #34C759; cursor: pointer; padding: 4px; transition: transform 0.2s ease;"
                            onmouseover="this.style.transform='rotate(90deg)'"
                            onmouseout="this.style.transform='rotate(0deg)'">
                        <span class="material-icons-outlined" style="font-size: 20px;">close</span>
                    </button>
                </div>
                <?php unset($_SESSION['success']); ?>
            <?php endif; ?>

            <?php if (isset($_SESSION['error'])): ?>
                <div style="background: linear-gradient(135deg, rgba(255, 59, 48, 0.05) 0%, rgba(255, 59, 48, 0.02) 100%); border: 2px solid rgba(255, 59, 48, 0.2); border-radius: 12px; padding: 16px 20px; margin-bottom: 24px; display: flex; align-items: center; gap: 12px; animation: slideInDown 0.4s ease;">
                    <div style="width: 40px; height: 40px; background: linear-gradient(135deg, rgba(255, 59, 48, 0.1) 0%, rgba(255, 59, 48, 0.05) 100%); border-radius: 50%; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                        <span class="material-icons-outlined" style="font-size: 24px; color: #FF3B30;">error</span>
                    </div>
                    <div style="flex: 1;">
                        <p style="color: #1D1D1F; font-size: 14px; margin: 0; font-weight: 500;"><?php echo htmlspecialchars($_SESSION['error']); ?></p>
                    </div>
                    <button type="button" onclick="this.parentElement.style.display='none'" style="background: none; border: none; color: #FF3B30; cursor: pointer; padding: 4px; transition: transform 0.2s ease;"
                            onmouseover="this.style.transform='rotate(90deg)'"
                            onmouseout="this.style.transform='rotate(0deg)'">
                        <span class="material-icons-outlined" style="font-size: 20px;">close</span>
                    </button>
                </div>
                <?php unset($_SESSION['error']); ?>
            <?php endif; ?>

            <?php if (empty($faturas)): ?>
                <div style="background: linear-gradient(135deg, rgba(110, 65, 193, 0.05) 0%, rgba(110, 65, 193, 0.02) 100%); border: 2px solid rgba(110, 65, 193, 0.2); border-radius: 12px; padding: 16px 20px; margin-bottom: 24px; display: flex; align-items: center; gap: 12px; animation: slideInDown 0.4s ease;">
                    <span class="material-icons-outlined" style="font-size: 24px; color: #6E41C1;">info</span>
                    <div style="flex: 1;">
                        <strong style="color: #6E41C1; font-size: 14px;">Nenhuma fatura cadastrada!</strong>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Tabela de Faturas -->
            <div class="row">
                <div class="col-lg-12">
                    <div style="background: white; border-radius: 16px; box-shadow: 0 2px 12px rgba(0, 0, 0, 0.08); overflow: hidden; margin-bottom: 24px;">
                        <!-- Header da Tabela -->
                        <div style="background: linear-gradient(135deg, #6E41C1 0%, #8B5FD6 100%); padding: 20px 24px; display: flex; align-items: center; justify-content: space-between;">
                            <div style="display: flex; align-items: center; gap: 12px;">
                                <div style="width: 40px; height: 40px; background: rgba(255, 255, 255, 0.2); border-radius: 10px; display: flex; align-items: center; justify-content: center;">
                                    <span class="material-icons-outlined" style="font-size: 22px; color: white;">receipt_long</span>
                                </div>
                                <h6 style="color: white; font-weight: 700; font-size: 16px; margin: 0;">Faturas</h6>
                            </div>
                            <span style="background: rgba(255, 255, 255, 0.2); color: white; padding: 6px 14px; border-radius: 20px; font-size: 13px; font-weight: 600;">
                                <?php echo count($faturas); ?> fatura<?php echo count($faturas) != 1 ? 's' : ''; ?>
                            </span>
                        </div>

                        <!-- Body da Tabela -->
                        <div style="padding: 0;">
                            <div class="table-responsive">
                                <table class="table table-hover" width="100%" cellspacing="0" id="faturasTable" style="margin: 0;">
                                    <thead style="background: linear-gradient(135deg, rgba(110, 65, 193, 0.05) 0%, rgba(110, 65, 193, 0.02) 100%); border-bottom: 2px solid #F5F5F7;">
                                        <tr>
                                            <th style="padding: 16px 24px; font-weight: 700; color: #1D1D1F; font-size: 13px; text-transform: uppercase; letter-spacing: 0.5px; border: none;">
                                                <span class="material-icons-outlined" style="font-size: 16px; vertical-align: middle; margin-right: 6px; color: #6E41C1;">tag</span>
                                                #
                                            </th>
                                            <th style="padding: 16px 24px; font-weight: 700; color: #1D1D1F; font-size: 13px; text-transform: uppercase; letter-spacing: 0.5px; border: none;">
                                                <span class="material-icons-outlined" style="font-size: 16px; vertical-align: middle; margin-right: 6px; color: #6E41C1;">qr_code</span>
                                                Número
                                            </th>
                                            <th style="padding: 16px 24px; font-weight: 700; color: #1D1D1F; font-size: 13px; text-transform: uppercase; letter-spacing: 0.5px; border: none;">
                                                <span class="material-icons-outlined" style="font-size: 16px; vertical-align: middle; margin-right: 6px; color: #6E41C1;">description</span>
                                                Descrição
                                            </th>
                                            <th style="padding: 16px 24px; font-weight: 700; color: #1D1D1F; font-size: 13px; text-transform: uppercase; letter-spacing: 0.5px; border: none;">
                                                <span class="material-icons-outlined" style="font-size: 16px; vertical-align: middle; margin-right: 6px; color: #6E41C1;">payments</span>
                                                Valor
                                            </th>
                                            <th style="padding: 16px 24px; font-weight: 700; color: #1D1D1F; font-size: 13px; text-transform: uppercase; letter-spacing: 0.5px; border: none;">
                                                <span class="material-icons-outlined" style="font-size: 16px; vertical-align: middle; margin-right: 6px; color: #6E41C1;">calendar_today</span>
                                                Emissão
                                            </th>
                                            <th style="padding: 16px 24px; font-weight: 700; color: #1D1D1F; font-size: 13px; text-transform: uppercase; letter-spacing: 0.5px; border: none;">
                                                <span class="material-icons-outlined" style="font-size: 16px; vertical-align: middle; margin-right: 6px; color: #6E41C1;">event_busy</span>
                                                Vencimento
                                            </th>
                                            <th style="padding: 16px 24px; font-weight: 700; color: #1D1D1F; font-size: 13px; text-transform: uppercase; letter-spacing: 0.5px; border: none;">
                                                <span class="material-icons-outlined" style="font-size: 16px; vertical-align: middle; margin-right: 6px; color: #6E41C1;">toggle_on</span>
                                                Status
                                            </th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($faturas as $f): ?>
                                            <tr style="transition: all 0.2s ease; border-bottom: 1px solid #F5F5F7;"
                                                onmouseover="this.style.background='rgba(110, 65, 193, 0.02)'"
                                                onmouseout="this.style.background='white'">
                                                <td style="padding: 16px 24px; color: #86868B; font-size: 13px; border: none;">
                                                    #<?php echo (int)$f['id']; ?>
                                                </td>
                                                <td style="padding: 16px 24px; border: none;">
                                                    <code style="background: #F5F5F7; color: #6E41C1; padding: 4px 8px; border-radius: 6px; font-size: 12px; font-weight: 600;">
                                                        <?php echo htmlspecialchars($f['numero_fatura']); ?>
                                                    </code>
                                                </td>
                                                <td style="padding: 16px 24px; color: #1D1D1F; font-size: 14px; border: none;">
                                                    <?php echo htmlspecialchars($f['descricao']); ?>
                                                </td>
                                                <td style="padding: 16px 24px; border: none;">
                                                    <strong style="color: #1D1D1F; font-size: 15px;">R$ <?php echo number_format((float)$f['valor'], 2, ',', '.'); ?></strong>
                                                </td>
                                                <td style="padding: 16px 24px; color: #86868B; font-size: 13px; border: none;">
                                                    <?php echo date('d/m/Y', strtotime($f['data_emissao'])); ?>
                                                </td>
                                                <td style="padding: 16px 24px; color: #86868B; font-size: 13px; border: none;">
                                                    <?php echo date('d/m/Y', strtotime($f['data_vencimento'])); ?>
                                                </td>
                                                <td style="padding: 16px 24px; border: none;">
                                                    <?php
                                                    $statusColor = $f['status']==='pendente' ? '#FF9500' : ($f['status']==='paga' ? '#34C759' : '#86868B');
                                                    $statusBg = $f['status']==='pendente' ? 'linear-gradient(135deg, rgba(255, 149, 0, 0.1) 0%, rgba(255, 149, 0, 0.05) 100%)' : ($f['status']==='paga' ? 'linear-gradient(135deg, rgba(52, 199, 89, 0.1) 0%, rgba(52, 199, 89, 0.05) 100%)' : 'linear-gradient(135deg, rgba(134, 134, 139, 0.1) 0%, rgba(134, 134, 139, 0.05) 100%)');
                                                    $statusIcon = $f['status']==='pendente' ? 'pending' : ($f['status']==='paga' ? 'check_circle' : 'cancel');
                                                    ?>
                                                    <span style="background: <?php echo $statusBg; ?>; color: <?php echo $statusColor; ?>; padding: 6px 12px; border-radius: 8px; font-size: 12px; font-weight: 600; display: inline-flex; align-items: center; gap: 6px;">
                                                        <span class="material-icons-outlined" style="font-size: 16px;"><?php echo $statusIcon; ?></span>
                                                        <?php echo htmlspecialchars($f['status']); ?>
                                                    </span>
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

            <?php if (empty($boletos)): ?>
                <div style="background: linear-gradient(135deg, rgba(110, 65, 193, 0.05) 0%, rgba(110, 65, 193, 0.02) 100%); border: 2px solid rgba(110, 65, 193, 0.2); border-radius: 12px; padding: 16px 20px; margin-bottom: 24px; display: flex; align-items: center; gap: 12px; animation: slideInDown 0.4s ease;">
                    <span class="material-icons-outlined" style="font-size: 24px; color: #6E41C1;">info</span>
                    <div style="flex: 1;">
                        <strong style="color: #6E41C1; font-size: 14px;">Nenhum boleto cadastrado!</strong>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Tabela de Boletos -->
            <div class="row">
                <div class="col-lg-12">
                    <div style="background: white; border-radius: 16px; box-shadow: 0 2px 12px rgba(0, 0, 0, 0.08); overflow: hidden; margin-bottom: 24px;">
                        <!-- Header da Tabela -->
                        <div style="background: linear-gradient(135deg, #6E41C1 0%, #8B5FD6 100%); padding: 20px 24px; display: flex; align-items: center; justify-content: space-between;">
                            <div style="display: flex; align-items: center; gap: 12px;">
                                <div style="width: 40px; height: 40px; background: rgba(255, 255, 255, 0.2); border-radius: 10px; display: flex; align-items: center; justify-content: center;">
                                    <span class="material-icons-outlined" style="font-size: 22px; color: white;">qr_code_2</span>
                                </div>
                                <h6 style="color: white; font-weight: 700; font-size: 16px; margin: 0;">Boletos</h6>
                            </div>
                            <span style="background: rgba(255, 255, 255, 0.2); color: white; padding: 6px 14px; border-radius: 20px; font-size: 13px; font-weight: 600;">
                                <?php echo count($boletos); ?> boleto<?php echo count($boletos) != 1 ? 's' : ''; ?>
                            </span>
                        </div>

                        <!-- Body da Tabela -->
                        <div style="padding: 0;">
                            <div class="table-responsive">
                                <table class="table table-hover" width="100%" cellspacing="0" id="boletosTable" style="margin: 0;">
                                    <thead style="background: linear-gradient(135deg, rgba(110, 65, 193, 0.05) 0%, rgba(110, 65, 193, 0.02) 100%); border-bottom: 2px solid #F5F5F7;">
                                        <tr>
                                            <th style="padding: 16px 24px; font-weight: 700; color: #1D1D1F; font-size: 13px; text-transform: uppercase; letter-spacing: 0.5px; border: none;">
                                                <span class="material-icons-outlined" style="font-size: 16px; vertical-align: middle; margin-right: 6px; color: #6E41C1;">tag</span>
                                                #
                                            </th>
                                            <th style="padding: 16px 24px; font-weight: 700; color: #1D1D1F; font-size: 13px; text-transform: uppercase; letter-spacing: 0.5px; border: none;">
                                                <span class="material-icons-outlined" style="font-size: 16px; vertical-align: middle; margin-right: 6px; color: #6E41C1;">receipt_long</span>
                                                Fatura
                                            </th>
                                            <th style="padding: 16px 24px; font-weight: 700; color: #1D1D1F; font-size: 13px; text-transform: uppercase; letter-spacing: 0.5px; border: none;">
                                                <span class="material-icons-outlined" style="font-size: 16px; vertical-align: middle; margin-right: 6px; color: #6E41C1;">payments</span>
                                                Valor
                                            </th>
                                            <th style="padding: 16px 24px; font-weight: 700; color: #1D1D1F; font-size: 13px; text-transform: uppercase; letter-spacing: 0.5px; border: none;">
                                                <span class="material-icons-outlined" style="font-size: 16px; vertical-align: middle; margin-right: 6px; color: #6E41C1;">event_busy</span>
                                                Vencimento
                                            </th>
                                            <th style="padding: 16px 24px; font-weight: 700; color: #1D1D1F; font-size: 13px; text-transform: uppercase; letter-spacing: 0.5px; border: none;">
                                                <span class="material-icons-outlined" style="font-size: 16px; vertical-align: middle; margin-right: 6px; color: #6E41C1;">toggle_on</span>
                                                Status
                                            </th>
                                            <th style="padding: 16px 24px; font-weight: 700; color: #1D1D1F; font-size: 13px; text-transform: uppercase; letter-spacing: 0.5px; border: none;">
                                                <span class="material-icons-outlined" style="font-size: 16px; vertical-align: middle; margin-right: 6px; color: #6E41C1;">link</span>
                                                Link
                                            </th>
                                            <th style="padding: 16px 24px; font-weight: 700; color: #1D1D1F; font-size: 13px; text-transform: uppercase; letter-spacing: 0.5px; border: none;">
                                                <span class="material-icons-outlined" style="font-size: 16px; vertical-align: middle; margin-right: 6px; color: #6E41C1;">qr_code</span>
                                                Linha Digitável
                                            </th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($boletos as $b): ?>
                                            <tr style="transition: all 0.2s ease; border-bottom: 1px solid #F5F5F7;"
                                                onmouseover="this.style.background='rgba(110, 65, 193, 0.02)'"
                                                onmouseout="this.style.background='white'">
                                                <td style="padding: 16px 24px; color: #86868B; font-size: 13px; border: none;">
                                                    #<?php echo (int)$b['id']; ?>
                                                </td>
                                                <td style="padding: 16px 24px; color: #86868B; font-size: 13px; border: none;">
                                                    Fatura #<?php echo (int)$b['fatura_id']; ?>
                                                </td>
                                                <td style="padding: 16px 24px; border: none;">
                                                    <strong style="color: #1D1D1F; font-size: 15px;">R$ <?php echo number_format((float)$b['valor'], 2, ',', '.'); ?></strong>
                                                </td>
                                                <td style="padding: 16px 24px; color: #86868B; font-size: 13px; border: none;">
                                                    <?php echo date('d/m/Y', strtotime($b['data_vencimento'])); ?>
                                                </td>
                                                <td style="padding: 16px 24px; border: none;">
                                                    <?php
                                                    $statusColor = $b['status']==='pendente' ? '#FF9500' : (($b['status']==='pago' || $b['status']==='paga') ? '#34C759' : '#86868B');
                                                    $statusBg = $b['status']==='pendente' ? 'linear-gradient(135deg, rgba(255, 149, 0, 0.1) 0%, rgba(255, 149, 0, 0.05) 100%)' : (($b['status']==='pago' || $b['status']==='paga') ? 'linear-gradient(135deg, rgba(52, 199, 89, 0.1) 0%, rgba(52, 199, 89, 0.05) 100%)' : 'linear-gradient(135deg, rgba(134, 134, 139, 0.1) 0%, rgba(134, 134, 139, 0.05) 100%)');
                                                    $statusIcon = $b['status']==='pendente' ? 'pending' : (($b['status']==='pago' || $b['status']==='paga') ? 'check_circle' : 'cancel');
                                                    ?>
                                                    <span style="background: <?php echo $statusBg; ?>; color: <?php echo $statusColor; ?>; padding: 6px 12px; border-radius: 8px; font-size: 12px; font-weight: 600; display: inline-flex; align-items: center; gap: 6px;">
                                                        <span class="material-icons-outlined" style="font-size: 16px;"><?php echo $statusIcon; ?></span>
                                                        <?php echo htmlspecialchars($b['status']); ?>
                                                    </span>
                                                </td>
                                                <td style="padding: 16px 24px; border: none;">
                                                    <?php if (!empty($b['url_boleto'])): ?>
                                                        <a href="<?php echo htmlspecialchars($b['url_boleto']); ?>" target="_blank" title="Abrir boleto"
                                                           style="background: linear-gradient(135deg, rgba(110, 65, 193, 0.1) 0%, rgba(110, 65, 193, 0.05) 100%); color: #6E41C1; border: none; border-radius: 8px; padding: 8px 12px; cursor: pointer; transition: all 0.2s ease; display: inline-flex; align-items: center; gap: 6px; text-decoration: none; font-size: 13px; font-weight: 600;"
                                                           onmouseover="this.style.background='#6E41C1'; this.style.color='white'; this.style.transform='translateY(-2px)'; this.style.boxShadow='0 4px 8px rgba(110, 65, 193, 0.3)'"
                                                           onmouseout="this.style.background='linear-gradient(135deg, rgba(110, 65, 193, 0.1) 0%, rgba(110, 65, 193, 0.05) 100%)'; this.style.color='#6E41C1'; this.style.transform='translateY(0)'; this.style.boxShadow='none'">
                                                            <span class="material-icons-outlined" style="font-size: 16px;">open_in_new</span>
                                                            Abrir
                                                        </a>
                                                    <?php else: ?>
                                                        <span style="color: #86868B; font-style: italic;">-</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td style="padding: 16px 24px; border: none;">
                                                    <code style="background: #F5F5F7; color: #6E41C1; padding: 6px 10px; border-radius: 6px; font-size: 11px; font-weight: 500; font-family: 'Courier New', monospace; word-break: break-all; display: block;">
                                                        <?php echo htmlspecialchars($b['linha_digitavel'] ?? '-'); ?>
                                                    </code>
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

          </main>
        </div>
      </div>
    </div>

  </div>

  <footer class="sticky-footer bg-white">
    <div class="container my-auto">
      <div class="copyright text-center my-auto">
        <span>Copyright &copy; Sistema de Certificados 2025</span>
      </div>
    </div>
  </footer>
</div>

<style>
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

/* DataTables Customization */
#faturasTable_wrapper .dataTables_length select,
#faturasTable_wrapper .dataTables_filter input,
#boletosTable_wrapper .dataTables_length select,
#boletosTable_wrapper .dataTables_filter input {
    border: 2px solid #E5E5E7;
    border-radius: 8px;
    padding: 6px 12px;
    font-size: 13px;
    transition: all 0.2s ease;
}

#faturasTable_wrapper .dataTables_length select:focus,
#faturasTable_wrapper .dataTables_filter input:focus,
#boletosTable_wrapper .dataTables_length select:focus,
#boletosTable_wrapper .dataTables_filter input:focus {
    border-color: #6E41C1;
    outline: none;
    box-shadow: 0 0 0 3px rgba(110, 65, 193, 0.1);
}

#faturasTable_wrapper .dataTables_paginate .paginate_button,
#boletosTable_wrapper .dataTables_paginate .paginate_button {
    border-radius: 8px;
    padding: 6px 12px;
    margin: 0 2px;
    border: 1px solid #E5E5E7;
    transition: all 0.2s ease;
}

#faturasTable_wrapper .dataTables_paginate .paginate_button:hover,
#boletosTable_wrapper .dataTables_paginate .paginate_button:hover {
    background: rgba(110, 65, 193, 0.05);
    border-color: #6E41C1;
    color: #6E41C1 !important;
}

#faturasTable_wrapper .dataTables_paginate .paginate_button.current,
#boletosTable_wrapper .dataTables_paginate .paginate_button.current {
    background: linear-gradient(135deg, #6E41C1 0%, #8B5FD6 100%);
    border-color: #6E41C1;
    color: white !important;
}
</style>

<script>
// Inicializar DataTables se disponível
$(document).ready(function() {
    if ($.fn.DataTable) {
        $('#faturasTable').DataTable({
            language: {
                url: '//cdn.datatables.net/plug-ins/1.13.7/i18n/pt-BR.json'
            },
            pageLength: 10,
            order: [[5, 'desc']], // Ordenar por vencimento (coluna 5) decrescente
            columnDefs: []
        });

        $('#boletosTable').DataTable({
            language: {
                url: '//cdn.datatables.net/plug-ins/1.13.7/i18n/pt-BR.json'
            },
            pageLength: 10,
            order: [[3, 'desc']], // Ordenar por vencimento (coluna 3) decrescente
            columnDefs: []
        });
    }
});
</script>

<?php require_once '../app/views/footer.php'; ?>
<?php $conn->close(); ?>

