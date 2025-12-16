<?php
/**
 * ============================================================================
 * CONFIGURAÇÕES DO SISTEMA - SISTEMA DE CERTIFICADOS
 * ============================================================================
 */

require_once '../app/config/config.php';

// Verificar autenticação e permissão
if (!isAuthenticated() || !hasRole(ROLE_ADMIN)) {
    redirect(APP_URL . '/login.php');
}

$page_title = 'Configurações - ' . APP_NAME;
$conn = getDBConnection();

// Buscar configurações atuais (usando tabela correta)
$configuracoes = [];
$result = $conn->query("SELECT * FROM configuracoes_sistema ORDER BY chave ASC");

if ($result) {
    while ($row = $result->fetch_assoc()) {
        $configuracoes[$row['chave']] = $row['valor'];
    }
}

// Se não houver configurações, criar padrões
if (empty($configuracoes)) {
    $defaults = [
        'site_nome' => 'FaCiencia - Sistema de Certificados',
        'site_email' => 'contato@faciencia.edu.br',
        'site_telefone' => '(41) 3333-3333',
        'certificado_validade_dias' => '0',
        'certificado_assinatura_digital' => '1',
        'email_notificacoes' => '1',
        'manutencao_modo' => '0',
        'api_asaas_enabled' => '0',
        'api_asaas_key' => '',
        'api_asaas_ambiente' => 'sandbox'
    ];
    
    foreach ($defaults as $chave => $valor) {
        $configuracoes[$chave] = $valor;
    }
}
?>
<?php require_once '../app/views/admin-layout-header.php'; ?>

                <!-- Mensagens de Feedback -->
                <?php if (isset($_SESSION['success'])): ?>
                    <div class="alert alert-success">
                        <span class="icon">check_circle</span>
                        <?php echo htmlspecialchars($_SESSION['success']); ?>
                    </div>
                    <?php unset($_SESSION['success']); ?>
                <?php endif; ?>

                <?php if (isset($_SESSION['error'])): ?>
                    <div class="alert alert-danger">
                        <span class="icon">error</span>
                        <?php echo htmlspecialchars($_SESSION['error']); ?>
                    </div>
                    <?php unset($_SESSION['error']); ?>
                <?php endif; ?>

                <!-- Page Header -->
                <div class="page-header">
                    <h1>Configurações do Sistema</h1>
                </div>

                <!-- Configurações Gerais -->
                <section class="table-section">
                    <div class="card">
                        <h2><span class="icon">settings</span>Configurações Gerais</h2>
                        <form method="POST" action="../app/actions/salvar-configuracoes.php">
                            <div style="padding: 20px;">
                                <div class="form-group">
                                    <label for="site_nome">Nome do Sistema</label>
                                    <input type="text" id="site_nome" name="site_nome" value="<?php echo htmlspecialchars($configuracoes['site_nome'] ?? ''); ?>">
                                </div>

                                <div class="form-group">
                                    <label for="site_email">Email de Contato</label>
                                    <input type="email" id="site_email" name="site_email" value="<?php echo htmlspecialchars($configuracoes['site_email'] ?? ''); ?>">
                                </div>

                                <div class="form-group">
                                    <label for="site_telefone">Telefone de Contato</label>
                                    <input type="text" id="site_telefone" name="site_telefone" value="<?php echo htmlspecialchars($configuracoes['site_telefone'] ?? ''); ?>">
                                </div>

                                <hr style="margin: 24px 0; border: none; border-top: 1px solid var(--border-color);">

                                <h3 style="color: var(--primary-color); margin-bottom: 16px;">Certificados</h3>

                                <div class="form-group">
                                    <label for="certificado_validade_dias">Validade dos Certificados (dias)</label>
                                    <input type="number" id="certificado_validade_dias" name="certificado_validade_dias" value="<?php echo htmlspecialchars($configuracoes['certificado_validade_dias'] ?? '0'); ?>" min="0">
                                    <small style="color: var(--text-medium); display: block; margin-top: 4px;">0 = sem validade (permanente)</small>
                                </div>

                                <div class="form-group">
                                    <label style="display: flex; align-items: center; cursor: pointer;">
                                        <input type="checkbox" name="certificado_assinatura_digital" value="1" <?php echo (($configuracoes['certificado_assinatura_digital'] ?? '0') == '1') ? 'checked' : ''; ?> style="margin-right: 8px;">
                                        Habilitar Assinatura Digital nos Certificados
                                    </label>
                                </div>

                                <hr style="margin: 24px 0; border: none; border-top: 1px solid var(--border-color);">

                                <h3 style="color: var(--primary-color); margin-bottom: 16px;">Notificações</h3>

                                <div class="form-group">
                                    <label style="display: flex; align-items: center; cursor: pointer;">
                                        <input type="checkbox" name="email_notificacoes" value="1" <?php echo (($configuracoes['email_notificacoes'] ?? '0') == '1') ? 'checked' : ''; ?> style="margin-right: 8px;">
                                        Enviar Notificações por Email
                                    </label>
                                </div>

                                <hr style="margin: 24px 0; border: none; border-top: 1px solid var(--border-color);">

                                <h3 style="color: var(--primary-color); margin-bottom: 16px;">Sistema</h3>

                                <div class="form-group">
                                    <label style="display: flex; align-items: center; cursor: pointer;">
                                        <input type="checkbox" name="manutencao_modo" value="1" <?php echo (($configuracoes['manutencao_modo'] ?? '0') == '1') ? 'checked' : ''; ?> style="margin-right: 8px;">
                                        Modo Manutenção (bloqueia acesso de parceiros)
                                    </label>
                                </div>

                                <div style="margin-top: 24px;">
                                    <button type="submit" class="button button-primary">
                                        <span class="icon">save</span> Salvar Configurações
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </section>

                <!-- Integração Asaas -->
                <section class="table-section">
                    <div class="card">
                        <h2><span class="icon">payment</span>Integração Asaas (Pagamentos)</h2>
                        <form method="POST" action="../app/actions/salvar-configuracoes.php">
                            <div style="padding: 20px;">
                                <div class="form-group">
                                    <label style="display: flex; align-items: center; cursor: pointer;">
                                        <input type="checkbox" name="api_asaas_enabled" value="1" <?php echo (($configuracoes['api_asaas_enabled'] ?? '0') == '1') ? 'checked' : ''; ?> style="margin-right: 8px;">
                                        Habilitar Integração com Asaas
                                    </label>
                                </div>

                                <div class="form-group">
                                    <label for="api_asaas_ambiente">Ambiente</label>
                                    <select id="api_asaas_ambiente" name="api_asaas_ambiente">
                                        <option value="sandbox" <?php echo (($configuracoes['api_asaas_ambiente'] ?? 'sandbox') == 'sandbox') ? 'selected' : ''; ?>>Sandbox (Testes)</option>
                                        <option value="production" <?php echo (($configuracoes['api_asaas_ambiente'] ?? 'sandbox') == 'production') ? 'selected' : ''; ?>>Produção</option>
                                    </select>
                                </div>

                                <div class="form-group">
                                    <label for="api_asaas_key">API Key</label>
                                    <input type="password" id="api_asaas_key" name="api_asaas_key" value="<?php echo htmlspecialchars($configuracoes['api_asaas_key'] ?? ''); ?>" placeholder="$aact_...">
                                    <small style="color: var(--text-medium); display: block; margin-top: 4px;">Obtenha sua chave em: https://www.asaas.com/</small>
                                </div>

                                <div style="margin-top: 24px;">
                                    <button type="submit" class="button button-primary">
                                        <span class="icon">save</span> Salvar Integração
                                    </button>
                                    <button type="button" class="button button-secondary" onclick="testarAsaas()">
                                        <span class="icon">check_circle</span> Testar Conexão
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </section>

                <!-- Informações do Sistema -->
                <section class="table-section">
                    <div class="card">
                        <h2><span class="icon">info</span>Informações do Sistema</h2>
                        <div style="padding: 20px;">
                            <table style="width: 100%; border-collapse: collapse;">
                                <tr style="border-bottom: 1px solid var(--border-color);">
                                    <td style="padding: 12px 0; font-weight: 600;">Versão do Sistema:</td>
                                    <td style="padding: 12px 0;">1.0.0</td>
                                </tr>
                                <tr style="border-bottom: 1px solid var(--border-color);">
                                    <td style="padding: 12px 0; font-weight: 600;">Versão PHP:</td>
                                    <td style="padding: 12px 0;"><?php echo phpversion(); ?></td>
                                </tr>
                                <tr style="border-bottom: 1px solid var(--border-color);">
                                    <td style="padding: 12px 0; font-weight: 600;">Servidor:</td>
                                    <td style="padding: 12px 0;"><?php echo $_SERVER['SERVER_SOFTWARE'] ?? 'N/A'; ?></td>
                                </tr>
                                <tr style="border-bottom: 1px solid var(--border-color);">
                                    <td style="padding: 12px 0; font-weight: 600;">Banco de Dados:</td>
                                    <td style="padding: 12px 0;">MySQL <?php echo $conn->server_info; ?></td>
                                </tr>
                                <tr>
                                    <td style="padding: 12px 0; font-weight: 600;">Última Atualização:</td>
                                    <td style="padding: 12px 0;">04/11/2025</td>
                                </tr>
                            </table>
                        </div>
                    </div>
                </section>

<?php require_once '../app/views/admin-layout-footer.php'; ?>

<script>
function testarAsaas() {
    const apiKey = document.getElementById('api_asaas_key').value;
    const ambiente = document.getElementById('api_asaas_ambiente').value;
    
    if (!apiKey) {
        alert('Por favor, insira a API Key antes de testar.');
        return;
    }
    
    // Aqui você implementaria a chamada AJAX para testar a conexão
    alert('Testando conexão com Asaas...\n\nAmbiente: ' + ambiente + '\nAPI Key: ' + apiKey.substring(0, 10) + '...');
}
</script>

<?php $conn->close(); ?>

