<?php
/**
 * Boletos Asaas - Sistema de Certificados
 * Padrão MVP - Camada de Apresentação
 */

require_once '../app/config/config.php';

if (!isAuthenticated() || !hasRole(ROLE_ADMIN)) {
    redirect(APP_URL . '/login.php');
}

$page_title = 'Boletos Asaas - ' . APP_NAME;
$conn = getDBConnection();

// Buscar boletos
$boletos = [];
$result = $conn->query("
    SELECT b.id, b.asaas_id, p.nome_empresa as parceiro, b.valor, b.status, b.data_vencimento, b.url_boleto, b.linha_digitavel, b.criado_em
    FROM asaas_boletos b
    JOIN parceiros p ON b.parceiro_id = p.id
    ORDER BY b.criado_em DESC
");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $boletos[] = $row;
    }
}

// Verificar se Asaas está configurado
$asaas_configurado = false;
$result_config = $conn->query("SELECT ativo FROM asaas_config WHERE ativo = 1 LIMIT 1");
if ($result_config && $result_config->num_rows > 0) {
    $asaas_configurado = true;
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
                <h1 class="h3 mb-0 text-gray-800">Boletos Asaas</h1>
            </div>

            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Lista de Boletos Gerados</h6>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered" width="100%" cellspacing="0">
                            <thead>
                                <tr>
                                    <th>Parceiro</th>
                                    <th>Valor</th>
                                    <th>Status</th>
                                    <th>Vencimento</th>
                                    <th>Linha Digitável</th>
                                    <th>Ações</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($boletos)): ?>
                                    <tr>
                                        <td colspan="6" class="text-center text-muted py-4">
                                            <i class="fas fa-inbox"></i> Nenhum boleto gerado
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($boletos as $boleto): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($boleto['parceiro']); ?></td>
                                            <td><strong>R$ <?php echo number_format($boleto['valor'], 2, ',', '.'); ?></strong></td>
                                            <td>
                                                <?php
                                                    $status_class = 'secondary';
                                                    $status_icon = 'fa-clock';
                                                    $status_label = $boleto['status'];

                                                    if ($boleto['status'] === 'recebido') {
                                                        $status_class = 'success';
                                                        $status_icon = 'fa-check-circle';
                                                        $status_label = 'Recebido';
                                                    } elseif ($boleto['status'] === 'cancelado') {
                                                        $status_class = 'danger';
                                                        $status_icon = 'fa-times-circle';
                                                        $status_label = 'Cancelado';
                                                    } elseif ($boleto['status'] === 'expirado') {
                                                        $status_class = 'dark';
                                                        $status_icon = 'fa-calendar-times';
                                                        $status_label = 'Expirado';
                                                    } elseif ($boleto['status'] === 'confirmado') {
                                                        $status_class = 'info';
                                                        $status_icon = 'fa-check';
                                                        $status_label = 'Confirmado';
                                                    } else {
                                                        $status_label = 'Pendente';
                                                    }
                                                ?>
                                                <span class="badge badge-<?php echo $status_class; ?>">
                                                    <i class="fas <?php echo $status_icon; ?>"></i> <?php echo $status_label; ?>
                                                </span>
                                            </td>
                                            <td><?php echo date('d/m/Y', strtotime($boleto['data_vencimento'])); ?></td>
                                            <td>
                                                <?php if ($boleto['linha_digitavel']): ?>
                                                    <code style="font-size: 0.85em;"><?php echo htmlspecialchars($boleto['linha_digitavel']); ?></code>
                                                <?php else: ?>
                                                    <span class="text-muted">-</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php if ($boleto['status'] === 'pendente' && $asaas_configurado): ?>
                                                    <form method="POST" action="../app/actions/enviar-boleto-asaas.php" style="display:inline;" onsubmit="return confirm('Enviar este boleto para Asaas?');">
                                                        <input type="hidden" name="boleto_id" value="<?php echo $boleto['id']; ?>">
                                                        <button type="submit" class="btn btn-sm btn-warning" title="Enviar para Asaas">
                                                            <i class="fas fa-paper-plane"></i> Enviar
                                                        </button>
                                                    </form>
                                                <?php endif; ?>
                                                <?php if ($boleto['url_boleto'] && $boleto['url_boleto'] !== '#'): ?>
                                                    <a href="<?php echo htmlspecialchars($boleto['url_boleto']); ?>" target="_blank" class="btn btn-sm btn-primary" title="Visualizar Boleto">
                                                        <i class="fas fa-download"></i> Download
                                                    </a>
                                                <?php endif; ?>
                                                <?php if (!$boleto['url_boleto'] || $boleto['url_boleto'] === '#'): ?>
                                                    <span class="text-muted small">Sem ações</span>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
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

