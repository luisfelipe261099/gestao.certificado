<?php
/**
 * ============================================================================
 * GERENCIAR PAGAMENTOS - SISTEMA DE CERTIFICADOS
 * ============================================================================
 *
 * Esta página permite que o admin veja, crie, edite e delete pagamentos.
 * Pagamentos = dinheiro que os parceiros pagam pelas assinaturas.
 * Aqui o admin registra quando um parceiro pagou e por qual método (cartão, boleto, etc).
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
$page_title = 'Pagamentos - ' . APP_NAME;

// Conecta ao banco de dados
$conn = getDBConnection();

// ============================================================================
// BUSCAR LISTA DE PAGAMENTOS
// ============================================================================
// Consulta SQL com JOIN = combina dados de várias tabelas
//
// SELECT = pega dados
// FROM pagamentos p = da tabela "pagamentos" (apelido "p")
// JOIN parceiros pa = junta com a tabela "parceiros" (apelido "pa")
//
// Isso permite pegar o nome da empresa junto com o pagamento
$pagamentos = [];
$result = $conn->query("
    SELECT
        p.id,                           -- ID do pagamento
        p.descricao,                    -- Descrição (ex: Assinatura Mensal)
        pa.nome_empresa as parceiro,    -- Nome da empresa que pagou
        p.valor,                        -- Quanto foi pago
        p.status,                       -- Status (pago, pendente, cancelado)
        p.data_pagamento,               -- Data do pagamento
        p.metodo                        -- Método (cartão, boleto, PIX, etc)
    FROM pagamentos p
    JOIN parceiros pa ON p.parceiro_id = pa.id  -- Conecta pelo ID do parceiro
    ORDER BY p.data_pagamento DESC              -- Ordena pelos mais recentes
");

if ($result) {
    while ($row = $result->fetch_assoc()) {
        $pagamentos[] = $row;
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
                <h1 class="h3 mb-0 text-gray-800">Gerenciar Pagamentos</h1>
                <a href="#" class="d-none d-sm-inline-block btn btn-sm btn-primary shadow-sm" data-toggle="modal" data-target="#novoPagamento">
                    <i class="fas fa-plus fa-sm text-white-50"></i> Novo Pagamento
                </a>
            </div>

            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Lista de Pagamentos</h6>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered" width="100%" cellspacing="0">
                            <thead>
                                <tr>
                                    <th>Descrição</th>
                                    <th>Parceiro</th>
                                    <th>Valor</th>
                                    <th>Método</th>
                                    <th>Status</th>
                                    <th>Data</th>
                                    <th>Ações</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($pagamentos as $pagamento): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($pagamento['descricao']); ?></td>
                                        <td><?php echo htmlspecialchars($pagamento['parceiro']); ?></td>
                                        <td>R$ <?php echo number_format($pagamento['valor'], 2, ',', '.'); ?></td>
                                        <td><?php echo htmlspecialchars($pagamento['metodo']); ?></td>
                                        <td>
                                            <span class="badge badge-<?php echo ($pagamento['status'] === 'pago') ? 'success' : (($pagamento['status'] === 'cancelado') ? 'danger' : 'warning'); ?>">
                                                <?php echo htmlspecialchars($pagamento['status']); ?>
                                            </span>
                                        </td>
                                        <td><?php echo date('d/m/Y', strtotime($pagamento['data_pagamento'])); ?></td>
                                        <td>
                                            <a href="#" class="btn btn-sm btn-info" title="Editar" data-toggle="modal" data-target="#editarPagamento" onclick="setPagamentoEdit(<?php echo htmlspecialchars(json_encode($pagamento)); ?>)"><i class="fas fa-edit"></i></a>
                                            <a href="../app/actions/deletar-pagamento.php?id=<?php echo $pagamento['id']; ?>" class="btn btn-sm btn-danger" title="Deletar" onclick="return confirm('Tem certeza que deseja deletar este pagamento?')"><i class="fas fa-trash"></i></a>
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

<!-- Modal Novo Pagamento -->
<div class="modal fade" id="novoPagamento" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Novo Pagamento</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form method="POST" action="../app/actions/criar-pagamento.php">
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
                        <label for="descricao">Descrição *</label>
                        <input type="text" class="form-control" id="descricao" name="descricao" required>
                    </div>
                    <div class="form-group">
                        <label for="valor">Valor *</label>
                        <input type="number" step="0.01" class="form-control" id="valor" name="valor" required>
                    </div>
                    <div class="form-group">
                        <label for="metodo">Método de Pagamento *</label>
                        <select class="form-control" id="metodo" name="metodo" required>
                            <option value="cartao">Cartão</option>
                            <option value="boleto">Boleto</option>
                            <option value="transferencia">Transferência</option>
                            <option value="pix">PIX</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="data_pagamento">Data do Pagamento *</label>
                        <input type="date" class="form-control" id="data_pagamento" name="data_pagamento" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Criar Pagamento</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Editar Pagamento -->
<div class="modal fade" id="editarPagamento" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Editar Pagamento</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form method="POST" action="../app/actions/editar-pagamento.php">
                <div class="modal-body">
                    <input type="hidden" id="edit_id" name="id">
                    <div class="form-group">
                        <label for="edit_descricao">Descrição *</label>
                        <input type="text" class="form-control" id="edit_descricao" name="descricao" required>
                    </div>
                    <div class="form-group">
                        <label for="edit_valor">Valor *</label>
                        <input type="number" step="0.01" class="form-control" id="edit_valor" name="valor" required>
                    </div>
                    <div class="form-group">
                        <label for="edit_status">Status *</label>
                        <select class="form-control" id="edit_status" name="status" required>
                            <option value="pago">Pago</option>
                            <option value="pendente">Pendente</option>
                            <option value="cancelado">Cancelado</option>
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
function setPagamentoEdit(pagamento) {
    document.getElementById('edit_id').value = pagamento.id;
    document.getElementById('edit_descricao').value = pagamento.descricao;
    document.getElementById('edit_valor').value = pagamento.valor;
    document.getElementById('edit_status').value = pagamento.status;
}
</script>

<?php $conn->close(); ?>
<?php require_once '../app/views/admin-layout-footer.php'; ?>

