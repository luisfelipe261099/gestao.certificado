<?php
/**
 * Perfil Parceiro - Sistema de Certificados
 * Padrão MVP - Camada de Apresentação
 */

require_once '../app/config/config.php';

if (!isAuthenticated() || !hasRole(ROLE_PARCEIRO)) {
    redirect(APP_URL . '/login.php');
}

$page_title = 'Meu Perfil - ' . APP_NAME;
$user = getCurrentUser();
$conn = getDBConnection();

// Buscar dados do parceiro
$parceiro = [];
$parceiro_id = $user['parceiro_id'] ?? $user['id'];
$stmt = $conn->prepare("SELECT id, nome_empresa, email, telefone, cnpj, endereco, cidade, estado, cep, criado_em FROM parceiros WHERE id = ?");
if ($stmt) {
    $stmt->bind_param("i", $parceiro_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        $parceiro = $row;
    }
    $stmt->close();
}
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
                  <li><a href="<?php echo APP_URL; ?>/parceiro/financeiro.php"><span class="icon">credit_card</span> Financeiro</a></li>
                  <li><a href="<?php echo APP_URL; ?>/parceiro/meu-plano.php"><span class="icon">price_check</span> Meu Plano</a></li>
                </ul>
              </nav>
              <div class="sidebar-footer">
                <ul>
                  <li><a href="<?php echo APP_URL; ?>/parceiro/perfil-parceiro.php" class="active"><span class="icon">person</span> Meu Perfil</a></li>
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
                <div style="background: linear-gradient(135deg, rgba(110, 65, 193, 0.08) 0%, rgba(139, 95, 214, 0.04) 100%); border-radius: 16px; padding: 28px 32px; margin-bottom: 28px; box-shadow: 0 2px 8px rgba(0, 0, 0, 0.06);">
                  <div style="display: flex; align-items: center; gap: 16px;">
                    <div style="width: 56px; height: 56px; background: linear-gradient(135deg, #6E41C1 0%, #8B5FD6 100%); border-radius: 14px; display: flex; align-items: center; justify-content: center; box-shadow: 0 4px 12px rgba(110, 65, 193, 0.3);">
                      <span class="material-icons-outlined" style="font-size: 28px; color: white;">person</span>
                    </div>
                    <div>
                      <h1 style="font-size: 28px; font-weight: 700; color: #1D1D1F; margin: 0; line-height: 1.2;">Meu Perfil</h1>
                      <p style="color: #86868B; font-size: 14px; margin: 4px 0 0 0;">Gerencie as informações da sua empresa</p>
                    </div>
                  </div>
                </div>

                <!-- Grid de 2 Colunas -->
                <div style="display: grid; grid-template-columns: 1fr 380px; gap: 24px;">
                  <!-- Card Principal - Informações da Empresa -->
                  <div style="background: white; border-radius: 16px; padding: 28px; box-shadow: 0 2px 12px rgba(0, 0, 0, 0.08); border: 1px solid #E5E5E7;">
                    <!-- Header do Card -->
                    <div style="background: linear-gradient(135deg, #6E41C1 0%, #8B5FD6 100%); border-radius: 12px; padding: 20px 24px; margin-bottom: 24px;">
                      <h2 style="color: white; font-size: 18px; font-weight: 700; margin: 0; display: flex; align-items: center; gap: 10px;">
                        <span class="material-icons-outlined" style="font-size: 22px;">business</span>
                        Informações da Empresa
                      </h2>
                    </div>

                    <!-- Formulário -->
                    <form method="POST" action="../app/actions/atualizar-perfil-parceiro.php">
                      <div style="margin-bottom: 20px;">
                        <label for="nome_empresa" style="font-weight: 600; color: #1D1D1F; font-size: 14px; margin-bottom: 8px; display: flex; align-items: center; gap: 6px;">
                          <span class="material-icons-outlined" style="font-size: 18px; color: #6E41C1;">business_center</span>
                          Nome da Empresa <span style="color: #FF3B30;">*</span>
                        </label>
                        <input type="text" class="form-control" id="nome_empresa" name="nome_empresa"
                               value="<?php echo htmlspecialchars($parceiro['nome_empresa'] ?? ''); ?>" required
                               style="border: 2px solid #E5E5E7; border-radius: 10px; padding: 12px 16px; font-size: 14px; width: 100%; transition: all 0.2s ease;"
                               onfocus="this.style.borderColor='#6E41C1'; this.style.boxShadow='0 0 0 3px rgba(110, 65, 193, 0.1)'"
                               onblur="this.style.borderColor='#E5E5E7'; this.style.boxShadow='none'">
                      </div>

                      <div style="margin-bottom: 20px;">
                        <label for="cnpj" style="font-weight: 600; color: #1D1D1F; font-size: 14px; margin-bottom: 8px; display: flex; align-items: center; gap: 6px;">
                          <span class="material-icons-outlined" style="font-size: 18px; color: #6E41C1;">badge</span>
                          CNPJ
                        </label>
                        <input type="text" class="form-control" id="cnpj" name="cnpj"
                               value="<?php echo htmlspecialchars($parceiro['cnpj'] ?? ''); ?>" placeholder="00.000.000/0000-00"
                               style="border: 2px solid #E5E5E7; border-radius: 10px; padding: 12px 16px; font-size: 14px; width: 100%; transition: all 0.2s ease;"
                               onfocus="this.style.borderColor='#6E41C1'; this.style.boxShadow='0 0 0 3px rgba(110, 65, 193, 0.1)'"
                               onblur="this.style.borderColor='#E5E5E7'; this.style.boxShadow='none'">
                      </div>

                      <div style="margin-bottom: 20px;">
                        <label for="email" style="font-weight: 600; color: #1D1D1F; font-size: 14px; margin-bottom: 8px; display: flex; align-items: center; gap: 6px;">
                          <span class="material-icons-outlined" style="font-size: 18px; color: #6E41C1;">email</span>
                          Email <span style="color: #FF3B30;">*</span>
                        </label>
                        <input type="email" class="form-control" id="email" name="email"
                               value="<?php echo htmlspecialchars($parceiro['email'] ?? ''); ?>" required
                               style="border: 2px solid #E5E5E7; border-radius: 10px; padding: 12px 16px; font-size: 14px; width: 100%; transition: all 0.2s ease;"
                               onfocus="this.style.borderColor='#6E41C1'; this.style.boxShadow='0 0 0 3px rgba(110, 65, 193, 0.1)'"
                               onblur="this.style.borderColor='#E5E5E7'; this.style.boxShadow='none'">
                      </div>

                      <div style="margin-bottom: 20px;">
                        <label for="telefone" style="font-weight: 600; color: #1D1D1F; font-size: 14px; margin-bottom: 8px; display: flex; align-items: center; gap: 6px;">
                          <span class="material-icons-outlined" style="font-size: 18px; color: #6E41C1;">phone</span>
                          Telefone
                        </label>
                        <input type="tel" class="form-control" id="telefone" name="telefone"
                               value="<?php echo htmlspecialchars($parceiro['telefone'] ?? ''); ?>" placeholder="(00) 00000-0000"
                               style="border: 2px solid #E5E5E7; border-radius: 10px; padding: 12px 16px; font-size: 14px; width: 100%; transition: all 0.2s ease;"
                               onfocus="this.style.borderColor='#6E41C1'; this.style.boxShadow='0 0 0 3px rgba(110, 65, 193, 0.1)'"
                               onblur="this.style.borderColor='#E5E5E7'; this.style.boxShadow='none'">
                      </div>

                      <div style="margin-bottom: 24px;">
                        <label for="endereco" style="font-weight: 600; color: #1D1D1F; font-size: 14px; margin-bottom: 8px; display: flex; align-items: center; gap: 6px;">
                          <span class="material-icons-outlined" style="font-size: 18px; color: #6E41C1;">location_on</span>
                          Endereço
                        </label>
                        <input type="text" class="form-control" id="endereco" name="endereco"
                               value="<?php echo htmlspecialchars($parceiro['endereco'] ?? ''); ?>"
                               style="border: 2px solid #E5E5E7; border-radius: 10px; padding: 12px 16px; font-size: 14px; width: 100%; transition: all 0.2s ease;"
                               onfocus="this.style.borderColor='#6E41C1'; this.style.boxShadow='0 0 0 3px rgba(110, 65, 193, 0.1)'"
                               onblur="this.style.borderColor='#E5E5E7'; this.style.boxShadow='none'">
                      </div>

                      <button type="submit"
                              style="background: linear-gradient(135deg, #6E41C1 0%, #8B5FD6 100%); color: white; border: none; border-radius: 10px; padding: 12px 28px; font-weight: 600; cursor: pointer; transition: all 0.2s ease; display: inline-flex; align-items: center; gap: 8px; box-shadow: 0 4px 12px rgba(110, 65, 193, 0.3);"
                              onmouseover="this.style.transform='translateY(-2px)'; this.style.boxShadow='0 6px 20px rgba(110, 65, 193, 0.4)'"
                              onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 4px 12px rgba(110, 65, 193, 0.3)'">
                        <span class="material-icons-outlined" style="font-size: 18px;">save</span>
                        Salvar Alterações
                      </button>
                    </form>
                  </div>

                  <!-- Card Lateral - Informações da Conta -->
                  <div style="background: white; border-radius: 16px; padding: 24px; box-shadow: 0 2px 12px rgba(0, 0, 0, 0.08); border: 1px solid #E5E5E7; height: fit-content;">
                    <!-- Header -->
                    <div style="background: linear-gradient(135deg, #6E41C1 0%, #8B5FD6 100%); border-radius: 12px; padding: 16px 20px; margin-bottom: 20px;">
                      <h3 style="color: white; font-size: 16px; font-weight: 700; margin: 0; display: flex; align-items: center; gap: 8px;">
                        <span class="material-icons-outlined" style="font-size: 20px;">account_circle</span>
                        Informações da Conta
                      </h3>
                    </div>

                    <!-- Conteúdo -->
                    <div style="margin-bottom: 16px;">
                      <p style="font-weight: 600; color: #86868B; font-size: 13px; margin-bottom: 6px;">Tipo de Usuário:</p>
                      <span style="background: linear-gradient(135deg, rgba(110, 65, 193, 0.1) 0%, rgba(110, 65, 193, 0.05) 100%); color: #6E41C1; padding: 6px 14px; border-radius: 8px; font-size: 13px; font-weight: 600; display: inline-block;">Parceiro</span>
                    </div>

                    <div style="margin-bottom: 20px;">
                      <p style="font-weight: 600; color: #86868B; font-size: 13px; margin-bottom: 6px;">Membro desde:</p>
                      <p style="color: #1D1D1F; font-size: 14px; font-weight: 600; margin: 0;"><?php echo date('d/m/Y', strtotime($parceiro['criado_em'] ?? 'now')); ?></p>
                    </div>

                    <div style="height: 1px; background: #E5E5E7; margin: 20px 0;"></div>

                    <button data-toggle="modal" data-target="#alterarSenha"
                            style="background: linear-gradient(135deg, #6E41C1 0%, #8B5FD6 100%); color: white; border: none; border-radius: 10px; padding: 12px 20px; font-weight: 600; cursor: pointer; transition: all 0.2s ease; display: flex; align-items: center; justify-content: center; gap: 8px; width: 100%; box-shadow: 0 4px 12px rgba(110, 65, 193, 0.3);"
                            onmouseover="this.style.transform='translateY(-2px)'; this.style.boxShadow='0 6px 20px rgba(110, 65, 193, 0.4)'"
                            onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 4px 12px rgba(110, 65, 193, 0.3)'">
                      <span class="material-icons-outlined" style="font-size: 18px;">lock</span>
                      Alterar Senha
                    </button>
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

<!-- Modal Alterar Senha -->
<div class="modal fade" id="alterarSenha" tabindex="-1" role="dialog" aria-hidden="true">
  <div class="modal-dialog" role="document" style="max-width: 500px;">
    <div class="modal-content" style="border: none; border-radius: 16px; overflow: hidden; box-shadow: 0 8px 32px rgba(0, 0, 0, 0.12);">
      <!-- Header -->
      <div style="background: linear-gradient(135deg, #6E41C1 0%, #8B5FD6 100%); padding: 24px 28px; border: none;">
        <div style="display: flex; align-items: center; justify-content: space-between;">
          <div style="display: flex; align-items: center; gap: 12px;">
            <div style="width: 44px; height: 44px; background: rgba(255, 255, 255, 0.2); border-radius: 12px; display: flex; align-items: center; justify-content: center;">
              <span class="material-icons-outlined" style="font-size: 24px; color: white;">lock</span>
            </div>
            <h5 style="color: white; font-weight: 700; font-size: 20px; margin: 0;">Alterar Senha</h5>
          </div>
          <button type="button" class="close" data-dismiss="modal" aria-label="Close"
                  style="color: white; opacity: 1; font-size: 28px; font-weight: 300; text-shadow: none; padding: 0; margin: 0; background: none; border: none; cursor: pointer; transition: all 0.2s ease;"
                  onmouseover="this.style.transform='rotate(90deg)'"
                  onmouseout="this.style.transform='rotate(0deg)'">
            <span aria-hidden="true">&times;</span>
          </button>
        </div>
      </div>

      <form method="POST" action="../app/actions/alterar-senha.php">
        <div style="padding: 28px;">
          <!-- Senha Atual -->
          <div style="margin-bottom: 20px;">
            <label for="senha_atual" style="font-weight: 600; color: #1D1D1F; font-size: 14px; margin-bottom: 8px; display: flex; align-items: center; gap: 6px;">
              <span class="material-icons-outlined" style="font-size: 18px; color: #6E41C1;">lock_open</span>
              Senha Atual <span style="color: #FF3B30;">*</span>
            </label>
            <input type="password" class="form-control" id="senha_atual" name="senha_atual" required
                   style="border: 2px solid #E5E5E7; border-radius: 10px; padding: 12px 16px; font-size: 14px; width: 100%; transition: all 0.2s ease;"
                   onfocus="this.style.borderColor='#6E41C1'; this.style.boxShadow='0 0 0 3px rgba(110, 65, 193, 0.1)'"
                   onblur="this.style.borderColor='#E5E5E7'; this.style.boxShadow='none'">
          </div>

          <!-- Nova Senha -->
          <div style="margin-bottom: 20px;">
            <label for="nova_senha" style="font-weight: 600; color: #1D1D1F; font-size: 14px; margin-bottom: 8px; display: flex; align-items: center; gap: 6px;">
              <span class="material-icons-outlined" style="font-size: 18px; color: #6E41C1;">vpn_key</span>
              Nova Senha <span style="color: #FF3B30;">*</span>
            </label>
            <input type="password" class="form-control" id="nova_senha" name="nova_senha" required
                   style="border: 2px solid #E5E5E7; border-radius: 10px; padding: 12px 16px; font-size: 14px; width: 100%; transition: all 0.2s ease;"
                   onfocus="this.style.borderColor='#6E41C1'; this.style.boxShadow='0 0 0 3px rgba(110, 65, 193, 0.1)'"
                   onblur="this.style.borderColor='#E5E5E7'; this.style.boxShadow='none'">
          </div>

          <!-- Confirmar Senha -->
          <div style="margin-bottom: 20px;">
            <label for="confirmar_senha" style="font-weight: 600; color: #1D1D1F; font-size: 14px; margin-bottom: 8px; display: flex; align-items: center; gap: 6px;">
              <span class="material-icons-outlined" style="font-size: 18px; color: #6E41C1;">check_circle</span>
              Confirmar Senha <span style="color: #FF3B30;">*</span>
            </label>
            <input type="password" class="form-control" id="confirmar_senha" name="confirmar_senha" required
                   style="border: 2px solid #E5E5E7; border-radius: 10px; padding: 12px 16px; font-size: 14px; width: 100%; transition: all 0.2s ease;"
                   onfocus="this.style.borderColor='#6E41C1'; this.style.boxShadow='0 0 0 3px rgba(110, 65, 193, 0.1)'"
                   onblur="this.style.borderColor='#E5E5E7'; this.style.boxShadow='none'">
          </div>

          <!-- Dica de Segurança -->
          <div style="background: linear-gradient(135deg, rgba(110, 65, 193, 0.05) 0%, rgba(110, 65, 193, 0.02) 100%); border: 2px solid rgba(110, 65, 193, 0.2); border-radius: 10px; padding: 12px 16px; display: flex; align-items: start; gap: 10px;">
            <span class="material-icons-outlined" style="font-size: 18px; color: #6E41C1; flex-shrink: 0;">info</span>
            <p style="color: #1D1D1F; font-size: 12px; margin: 0; line-height: 1.5;">
              <strong style="color: #6E41C1;">Dica:</strong> Use uma senha forte com pelo menos 8 caracteres, incluindo letras, números e símbolos.
            </p>
          </div>
        </div>

        <div style="padding: 20px 28px; background: #F5F5F7; border-top: 1px solid #E5E5E7; display: flex; gap: 12px; justify-content: flex-end;">
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
            <span class="material-icons-outlined" style="font-size: 16px; vertical-align: middle; margin-right: 4px;">lock</span>
            Alterar Senha
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

<?php require_once '../app/views/footer.php'; ?>
<?php $conn->close(); ?>

