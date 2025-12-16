<?php
/**
 * Solicitações de Planos - Admin
 */
require_once '../app/config/config.php';

if (!isAuthenticated() || !hasRole(ROLE_ADMIN)) {
    redirect(APP_URL . '/login.php');
}

$page_title = 'Solicitações de Planos - ' . APP_NAME;
$conn = getDBConnection();

// Buscar solicitações
$solicitacoes = [];
$sql = "
    SELECT s.id, s.parceiro_id, s.plano_novo_id, s.status, s.tipo, s.criado_em,
           p.nome_empresa AS parceiro,
           pl.nome AS plano_nome
    FROM solicitacoes_planos s
    JOIN parceiros p ON s.parceiro_id = p.id
    JOIN planos pl ON s.plano_novo_id = pl.id
    ORDER BY s.criado_em DESC
";
$res = $conn->query($sql);
if ($res) {
    while ($row = $res->fetch_assoc()) { $solicitacoes[] = $row; }
}
?>
<?php require_once '../app/views/admin-layout-header.php'; ?>

                <!-- Page Header -->
                <div class="page-header">
                    <h1><span class="icon">request_page</span>Solicitações de Planos</h1>
                </div>

                <?php if (isset($_SESSION['success'])): ?>
                    <div class="alert alert-success" style="padding: 15px; border-radius: 8px; margin-bottom: 20px;">
                        <span class="icon">check_circle</span> <?php echo htmlspecialchars($_SESSION['success']); ?>
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

            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Lista</h6>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered" width="100%" cellspacing="0">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Parceiro</th>
                                    <th>Plano Solicitado</th>
                                    <th>Status</th>
                                    <th>Criado em</th>
                                    <th>Ações</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($solicitacoes as $s): ?>
                                    <tr>
                                        <td><?php echo (int)$s['id']; ?></td>
                                        <td><?php echo htmlspecialchars($s['parceiro']); ?></td>
                                        <td><?php echo htmlspecialchars($s['plano_nome']); ?></td>
                                        <td>
                                            <span class="badge badge-<?php echo $s['status']==='pendente'?'warning':($s['status']==='aprovada'?'success':'danger'); ?>">
                                                <?php echo htmlspecialchars($s['status']); ?>
                                            </span>
                                        </td>
                                        <td><?php echo date('d/m/Y H:i', strtotime($s['criado_em'])); ?></td>
                                        <td>
                                            <?php if ($s['status'] === 'pendente'): ?>
                                                <form method="POST" action="../app/actions/aprovar-solicitacao-plano.php" style="display:inline;">
                                                    <input type="hidden" name="id" value="<?php echo (int)$s['id']; ?>">
                                                    <button type="submit" class="btn btn-sm btn-success" title="Aprovar"><i class="fas fa-check"></i></button>
                                                </form>
                                                <form method="POST" action="../app/actions/recusar-solicitacao-plano.php" style="display:inline;" onsubmit="return confirm('Recusar esta solicitação?');">
                                                    <input type="hidden" name="id" value="<?php echo (int)$s['id']; ?>">
                                                    <button type="submit" class="btn btn-sm btn-danger" title="Recusar"><i class="fas fa-times"></i></button>
                                                </form>
                                            <?php else: ?>
                                                <em>Sem ações</em>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

<?php $conn->close(); ?>
<?php require_once '../app/views/admin-layout-footer.php'; ?>

