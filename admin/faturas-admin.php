<?php
/**
 * ============================================================================
 * GERENCIAR FATURAS - SISTEMA DE CERTIFICADOS
 * ============================================================================
 *
 * Esta página permite que o admin veja, crie, edite e delete faturas.
 * Fatura = documento oficial que mostra quanto o parceiro deve pagar.
 * Aqui o admin pode gerar boletos, acompanhar pagamentos, etc.
 *
 * Padrão MVP - Camada de Apresentação
 * ============================================================================
 */

// Inclui o arquivo de configuração
require_once '../app/config/config.php';

// ============================================================================
// VERIFICAR AUTENTICAÇÃO E PERMISSÃO
// ============================================================================
// Verifica se está logado e se é admin
if (!isAuthenticated() || !hasRole(ROLE_ADMIN)) {
    redirect(APP_URL . '/login.php');
}

// Define o título da página
$page_title = 'Faturas - ' . APP_NAME;

// Conecta ao banco de dados
$conn = getDBConnection();

// ============================================================================
// BUSCAR LISTA DE FATURAS
// ============================================================================
// Consulta SQL com JOIN = combina dados de várias tabelas
//
// SELECT = pega dados
// FROM faturas f = da tabela "faturas" (apelido "f")
// JOIN parceiros p = junta com a tabela "parceiros" (apelido "p")
//
// Isso permite pegar o nome da empresa junto com a fatura
$faturas = [];
$result = $conn->query("
    SELECT
        f.id,                           -- ID da fatura
        f.numero_fatura,                -- Número da fatura (ex: NF-001)
        p.nome_empresa as parceiro,     -- Nome da empresa
        f.valor,                        -- Valor da fatura
        f.status,                       -- Status (paga, pendente, vencida, cancelada)
        f.data_emissao,                 -- Data que foi emitida
        f.data_vencimento               -- Data que vence
    FROM faturas f
    JOIN parceiros p ON f.parceiro_id = p.id  -- Conecta pelo ID do parceiro
    ORDER BY f.data_vencimento DESC           -- Ordena pelas que vencem primeiro
");

if ($result) {
    while ($row = $result->fetch_assoc()) {
        $faturas[] = $row;
    }
}

// ============================================================================
// BUSCAR LISTA DE PARCEIROS (para o formulário)
// ============================================================================
// Buscamos os parceiros ativos para mostrar no select (dropdown)
$parceiros = [];
$result = $conn->query("
    SELECT id, nome_empresa
    FROM parceiros
    WHERE ativo = 1
    ORDER BY nome_empresa ASC
");

if ($result) {
    while ($row = $result->fetch_assoc()) {
        $parceiros[] = $row;
    }
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
                <h1 class="h3 mb-0 text-gray-800">Gerenciar Faturas</h1>
                <a href="#" class="d-none d-sm-inline-block btn btn-sm btn-primary shadow-sm" data-toggle="modal" data-target="#novaFatura">
                    <i class="fas fa-plus fa-sm text-white-50"></i> Nova Fatura
                </a>
            </div>

            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Lista de Faturas</h6>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered" width="100%" cellspacing="0">
                            <thead>
                                <tr>
                                    <th>Número</th>
                                    <th>Parceiro</th>
                                    <th>Valor</th>
                                    <th>Status</th>
                                    <th>Emissão</th>
                                    <th>Vencimento</th>
                                    <th>Ações</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($faturas as $fatura): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($fatura['numero_fatura']); ?></td>
                                        <td><?php echo htmlspecialchars($fatura['parceiro']); ?></td>
                                        <td>R$ <?php echo number_format($fatura['valor'], 2, ',', '.'); ?></td>
                                        <td>
                                            <span class="badge badge-<?php echo ($fatura['status'] === 'paga') ? 'success' : (($fatura['status'] === 'vencida') ? 'danger' : 'warning'); ?>">
                                                <?php echo htmlspecialchars($fatura['status']); ?>
                                            </span>
                                        </td>
                                        <td><?php echo date('d/m/Y', strtotime($fatura['data_emissao'])); ?></td>
                                        <td><?php echo date('d/m/Y', strtotime($fatura['data_vencimento'])); ?></td>
                                        <td>
                                            <form method="POST" action="../app/actions/gerar-boleto-local.php" style="display:inline;">
                                                <input type="hidden" name="fatura_id" value="<?php echo $fatura['id']; ?>">
                                                <button type="submit" class="btn btn-sm btn-success" title="Gerar Boleto Local"><i class="fas fa-barcode"></i></button>
                                            </form>
                                            <a href="#" class="btn btn-sm btn-info" title="Editar" data-toggle="modal" data-target="#editarFatura" onclick="setFaturaEdit(<?php echo htmlspecialchars(json_encode($fatura)); ?>)"><i class="fas fa-edit"></i></a>
                                            <a href="../app/actions/deletar-fatura.php?id=<?php echo $fatura['id']; ?>" class="btn btn-sm btn-danger" title="Deletar" onclick="return confirm('Tem certeza que deseja deletar esta fatura?')"><i class="fas fa-trash"></i></a>
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

    <footer class="sticky-footer bg-white">
        <div class="container my-auto">
            <div class="copyright text-center my-auto">
                <span>Copyright &copy; Sistema de Certificados 2025</span>
            </div>
        </div>
    </footer>
</div>

<!-- Modal Nova Fatura -->
<div class="modal fade" id="novaFatura" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Nova Fatura</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form method="POST" action="../app/actions/criar-fatura.php">
                <div class="modal-body">
                    <div class="form-group">
                        <label for="parceiro_id">Parceiro *</label>
                        <select class="form-control" id="parceiro_id" name="parceiro_id" required>
                            <option value="">Selecione um parceiro</option>
                            <?php foreach ($parceiros as $parceiro): ?>
                                <option value="<?php echo $parceiro['id']; ?>">
                                    <?php echo htmlspecialchars($parceiro['nome_empresa']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="numero_fatura">Número da Fatura *</label>
                        <input type="text" class="form-control" id="numero_fatura" name="numero_fatura" required>
                    </div>
                    <div class="form-group">
                        <label for="descricao">Descrição</label>
                        <textarea class="form-control" id="descricao" name="descricao" rows="2"></textarea>
                    </div>
                    <div class="form-group">
                        <label for="valor">Valor *</label>
                        <input type="number" step="0.01" class="form-control" id="valor" name="valor" required>
                    </div>
                    <div class="form-group">
                        <label for="data_emissao">Data de Emissão *</label>
                        <input type="date" class="form-control" id="data_emissao" name="data_emissao" required>
                    </div>
                    <div class="form-group">
                        <label for="data_vencimento">Data de Vencimento *</label>
                        <input type="date" class="form-control" id="data_vencimento" name="data_vencimento" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Criar Fatura</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Editar Fatura -->
<div class="modal fade" id="editarFatura" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Editar Fatura</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form method="POST" action="../app/actions/editar-fatura.php">
                <div class="modal-body">
                    <input type="hidden" id="edit_id" name="id">
                    <div class="form-group">
                        <label for="edit_numero_fatura">Número da Fatura *</label>
                        <input type="text" class="form-control" id="edit_numero_fatura" name="numero_fatura" required>
                    </div>
                    <div class="form-group">
                        <label for="edit_valor">Valor *</label>
                        <input type="number" step="0.01" class="form-control" id="edit_valor" name="valor" required>
                    </div>
                    <div class="form-group">
                        <label for="edit_status">Status *</label>
                        <select class="form-control" id="edit_status" name="status" required>
                            <option value="paga">Paga</option>
                            <option value="pendente">Pendente</option>
                            <option value="vencida">Vencida</option>
                            <option value="cancelada">Cancelada</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Salvar Alterações</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function setFaturaEdit(fatura) {
    document.getElementById('edit_id').value = fatura.id;
    document.getElementById('edit_numero_fatura').value = fatura.numero_fatura;
    document.getElementById('edit_valor').value = fatura.valor;
    document.getElementById('edit_status').value = fatura.status;
}
</script>

<?php $conn->close(); ?>
<?php require_once '../app/views/admin-layout-footer.php'; ?>

