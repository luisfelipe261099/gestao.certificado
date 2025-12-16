<?php
/**
 * Configuração Asaas - Sistema de Certificados
 * Padrão MVP - Camada de Apresentação
 */

require_once '../app/config/config.php';

if (!isAuthenticated() || !hasRole(ROLE_ADMIN)) {
    redirect(APP_URL . '/login.php');
}

$page_title = 'Configuração Asaas - ' . APP_NAME;
$conn = getDBConnection();

// Buscar configuração atual
$config = null;
$result = $conn->query("SELECT * FROM asaas_config LIMIT 1");
if ($result && $result->num_rows > 0) {
    $config = $result->fetch_assoc();
}
?>
<?php require_once '../app/views/admin-layout-header.php'; ?>
            <!-- Mensagens de Feedback -->
            <?php if (isset($_SESSION['success'])): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($_SESSION['success']); ?>
                    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <?php unset($_SESSION['success']); ?>
            <?php endif; ?>

            <?php if (isset($_SESSION['error'])): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($_SESSION['error']); ?>
                    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <?php unset($_SESSION['error']); ?>
            <?php endif; ?>

            <div class="d-sm-flex align-items-center justify-content-between mb-4">
                <h1 class="h3 mb-0 text-gray-800">Configuração Asaas</h1>
            </div>

            <div class="row">
                <div class="col-lg-8">
                    <div class="card shadow mb-4">
                        <div class="card-header py-3">
                            <h6 class="m-0 font-weight-bold text-primary">Credenciais Asaas</h6>
                        </div>
                        <div class="card-body">
                            <form method="POST" action="../app/actions/salvar-asaas-config.php">
                                <div class="form-group">
                                    <label for="api_key">API Key *</label>
                                    <input type="password" class="form-control" id="api_key" name="api_key" 
                                           value="<?php echo $config ? htmlspecialchars($config['api_key']) : ''; ?>" required>
                                    <small class="form-text text-muted">
                                        Obtenha sua API Key em: <a href="https://app.asaas.com/settings/api" target="_blank">app.asaas.com/settings/api</a>
                                    </small>
                                </div>

                                <div class="form-group">
                                    <label for="wallet_id">Wallet ID (Opcional)</label>
                                    <input type="text" class="form-control" id="wallet_id" name="wallet_id" 
                                           value="<?php echo $config ? htmlspecialchars($config['wallet_id']) : ''; ?>">
                                    <small class="form-text text-muted">
                                        ID da carteira para receber os pagamentos
                                    </small>
                                </div>

                                <div class="form-group">
                                    <label for="ambiente">Ambiente *</label>
                                    <select class="form-control" id="ambiente" name="ambiente" required>
                                        <option value="producao" <?php echo ($config && $config['ambiente'] === 'producao') ? 'selected' : ''; ?>>
                                            Produção
                                        </option>
                                        <option value="sandbox" <?php echo ($config && $config['ambiente'] === 'sandbox') ? 'selected' : ''; ?>>
                                            Sandbox (Testes)
                                        </option>
                                    </select>
                                </div>

                                <div class="form-group">
                                    <div class="custom-control custom-checkbox">
                                        <input type="checkbox" class="custom-control-input" id="ativo" name="ativo" 
                                               <?php echo ($config && $config['ativo']) ? 'checked' : ''; ?>>
                                        <label class="custom-control-label" for="ativo">
                                            Ativar integração Asaas
                                        </label>
                                    </div>
                                </div>

                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save"></i> Salvar Configuração
                                </button>
                            </form>
                        </div>
                    </div>
                </div>

                <div class="col-lg-4">
                    <div class="card shadow mb-4 border-left-info">
                        <div class="card-header py-3 bg-info">
                            <h6 class="m-0 font-weight-bold text-white">Status da Integração</h6>
                        </div>
                        <div class="card-body">
                            <?php if ($config && $config['ativo']): ?>
                                <div class="alert alert-success">
                                    <i class="fas fa-check-circle"></i> Integração Ativa
                                </div>
                                <p><strong>Ambiente:</strong> <?php echo ucfirst($config['ambiente']); ?></p>
                                <p><strong>API Key:</strong> <?php echo substr($config['api_key'], 0, 10) . '...'; ?></p>
                                <?php if ($config['wallet_id']): ?>
                                    <p><strong>Wallet ID:</strong> <?php echo htmlspecialchars($config['wallet_id']); ?></p>
                                <?php endif; ?>
                            <?php else: ?>
                                <div class="alert alert-warning">
                                    <i class="fas fa-exclamation-triangle"></i> Integração Inativa
                                </div>
                                <p>Configure as credenciais do Asaas para ativar a integração.</p>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="card shadow mb-4 border-left-success">
                        <div class="card-header py-3 bg-success">
                            <h6 class="m-0 font-weight-bold text-white">Funcionalidades</h6>
                        </div>
                        <div class="card-body">
                            <ul class="list-unstyled">
                                <li><i class="fas fa-check text-success"></i> Gerar boletos</li>
                                <li><i class="fas fa-check text-success"></i> Acompanhar pagamentos</li>
                                <li><i class="fas fa-check text-success"></i> Webhooks automáticos</li>
                                <li><i class="fas fa-check text-success"></i> Cancelar boletos</li>
                                <li><i class="fas fa-check text-success"></i> Relatórios</li>
                            </ul>
                        </div>
                    </div>
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

<?php $conn->close(); ?>
<?php require_once '../app/views/admin-layout-footer.php'; ?>

