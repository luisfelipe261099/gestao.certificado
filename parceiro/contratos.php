<?php
/**
 * ============================================================================
 * CONTRATOS - SISTEMA DE CERTIFICADOS
 * ============================================================================
 * Página para o parceiro visualizar e assinar contratos
 * ============================================================================
 */

require_once '../app/config/config.php';

// Verificar autenticação
if (!isAuthenticated() || !hasRole(ROLE_PARCEIRO)) {
    redirect(APP_URL . '/login.php');
}

$page_title = 'Meus Contratos - ' . APP_NAME;
$user = getCurrentUser();
$conn = getDBConnection();
$parceiro_id = $user['parceiro_id'] ?? $user['id'];

// ============================================================================
// BUSCAR CONTRATOS PENDENTES DE ASSINATURA
// ============================================================================
$contratos_pendentes = [];
$stmt = $conn->prepare("
    SELECT c.id, c.numero_contrato, c.tipo, c.plano_id, c.valor_mensal, c.data_inicio, 
           c.criado_em, pl.nome as plano_nome, pl.descricao
    FROM contratos c
    JOIN planos pl ON c.plano_id = pl.id
    WHERE c.parceiro_id = ? AND c.status = 'pendente_assinatura'
    ORDER BY c.criado_em DESC
");

if ($stmt) {
    $stmt->bind_param('i', $parceiro_id);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $contratos_pendentes[] = $row;
    }
    $stmt->close();
}

// ============================================================================
// BUSCAR CONTRATOS ASSINADOS
// ============================================================================
$contratos_assinados = [];
$stmt = $conn->prepare("
    SELECT c.id, c.numero_contrato, c.tipo, c.plano_id, c.valor_mensal, c.data_inicio, 
           c.assinado_em, pl.nome as plano_nome
    FROM contratos c
    JOIN planos pl ON c.plano_id = pl.id
    WHERE c.parceiro_id = ? AND c.status = 'assinado'
    ORDER BY c.assinado_em DESC
    LIMIT 10
");

if ($stmt) {
    $stmt->bind_param('i', $parceiro_id);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $contratos_assinados[] = $row;
    }
    $stmt->close();
}

$conn->close();
?>
<?php require_once '../app/views/header.php'; ?>
<?php require_once '../app/views/sidebar-parceiro.php'; ?>

<div id="content-wrapper" class="d-flex flex-column">
    <div id="content">
        <?php require_once '../app/views/topbar.php'; ?>

        <div class="container-fluid">
            <!-- Page Heading -->
            <div class="d-sm-flex align-items-center justify-content-between mb-4">
                <h1 class="h3 mb-0 text-gray-800">Meus Contratos</h1>
            </div>

            <!-- Alertas -->
            <?php if (isset($_SESSION['success'])): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <?php echo htmlspecialchars($_SESSION['success']); ?>
                    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <?php unset($_SESSION['success']); ?>
            <?php endif; ?>

            <?php if (isset($_SESSION['error'])): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <?php echo htmlspecialchars($_SESSION['error']); ?>
                    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <?php unset($_SESSION['error']); ?>
            <?php endif; ?>

            <!-- Contratos Pendentes de Assinatura -->
            <?php if (!empty($contratos_pendentes)): ?>
                <div class="card shadow mb-4">
                    <div class="card-header py-3 bg-danger">
                        <h6 class="m-0 font-weight-bold text-white">
                            <i class="fas fa-exclamation-circle"></i> Contratos Pendentes de Assinatura
                        </h6>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-bordered" width="100%" cellspacing="0">
                                <thead>
                                    <tr>
                                        <th>Número</th>
                                        <th>Tipo</th>
                                        <th>Plano</th>
                                        <th>Valor Mensal</th>
                                        <th>Data de Início</th>
                                        <th>Criado em</th>
                                        <th>Ações</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($contratos_pendentes as $contrato): ?>
                                        <tr>
                                            <td><strong><?php echo htmlspecialchars($contrato['numero_contrato']); ?></strong></td>
                                            <td>
                                                <?php 
                                                    $tipo_label = [
                                                        'assinatura' => 'Nova Assinatura',
                                                        'renovacao' => 'Renovação',
                                                        'mudanca_plano' => 'Mudança de Plano'
                                                    ];
                                                    echo $tipo_label[$contrato['tipo']] ?? $contrato['tipo'];
                                                ?>
                                            </td>
                                            <td><?php echo htmlspecialchars($contrato['plano_nome']); ?></td>
                                            <td>R$ <?php echo number_format($contrato['valor_mensal'], 2, ',', '.'); ?></td>
                                            <td><?php echo date('d/m/Y', strtotime($contrato['data_inicio'])); ?></td>
                                            <td><?php echo date('d/m/Y H:i', strtotime($contrato['criado_em'])); ?></td>
                                            <td>
                                                <button class="btn btn-sm btn-primary" data-toggle="modal" data-target="#visualizarContrato<?php echo (int)$contrato['id']; ?>">
                                                    <i class="fas fa-eye"></i> Visualizar
                                                </button>
                                                <button class="btn btn-sm btn-success" data-toggle="modal" data-target="#assinarContrato<?php echo (int)$contrato['id']; ?>">
                                                    <i class="fas fa-pen"></i> Assinar
                                                </button>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Contratos Assinados -->
            <?php if (!empty($contratos_assinados)): ?>
                <div class="card shadow">
                    <div class="card-header py-3 bg-success">
                        <h6 class="m-0 font-weight-bold text-white">
                            <i class="fas fa-check-circle"></i> Contratos Assinados
                        </h6>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-bordered" width="100%" cellspacing="0">
                                <thead>
                                    <tr>
                                        <th>Número</th>
                                        <th>Tipo</th>
                                        <th>Plano</th>
                                        <th>Valor Mensal</th>
                                        <th>Assinado em</th>
                                        <th>Ações</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($contratos_assinados as $contrato): ?>
                                        <tr>
                                            <td><strong><?php echo htmlspecialchars($contrato['numero_contrato']); ?></strong></td>
                                            <td>
                                                <?php 
                                                    $tipo_label = [
                                                        'assinatura' => 'Nova Assinatura',
                                                        'renovacao' => 'Renovação',
                                                        'mudanca_plano' => 'Mudança de Plano'
                                                    ];
                                                    echo $tipo_label[$contrato['tipo']] ?? $contrato['tipo'];
                                                ?>
                                            </td>
                                            <td><?php echo htmlspecialchars($contrato['plano_nome']); ?></td>
                                            <td>R$ <?php echo number_format($contrato['valor_mensal'], 2, ',', '.'); ?></td>
                                            <td><?php echo date('d/m/Y H:i', strtotime($contrato['assinado_em'])); ?></td>
                                            <td>
                                                <button class="btn btn-sm btn-info" data-toggle="modal" data-target="#visualizarContrato<?php echo (int)$contrato['id']; ?>">
                                                    <i class="fas fa-eye"></i> Visualizar
                                                </button>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <?php if (empty($contratos_pendentes) && empty($contratos_assinados)): ?>
                <div class="alert alert-info">
                    <i class="fas fa-info-circle"></i> Você não possui contratos no momento.
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require_once '../app/views/footer.php'; ?>

