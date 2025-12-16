<?php
/**
 * ============================================================================
 * GERENCIAR RECEITAS - SISTEMA DE CERTIFICADOS
 * ============================================================================
 *
 * Esta página permite que o admin veja, crie, edite e delete receitas.
 * Receita = dinheiro que entra no sistema (pagamentos dos parceiros).
 * Aqui o admin registra todas as receitas: assinaturas, renovações, upgrades, etc.
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
$page_title = 'Receitas - ' . APP_NAME;

// Conecta ao banco de dados
$conn = getDBConnection();

// ============================================================================
// BUSCAR LISTA DE RECEITAS
// ============================================================================
// Consulta SQL com JOIN = combina dados de várias tabelas
//
// SELECT = pega dados
// FROM receitas r = da tabela "receitas" (apelido "r")
// JOIN parceiros pa = junta com a tabela "parceiros" (apelido "pa")
//
// Isso permite pegar o nome da empresa junto com a receita
$receitas = [];
$result = $conn->query("
    SELECT
        r.id,                           -- ID da receita
        r.tipo,                         -- Tipo (assinatura, renovação, upgrade, outro)
        pa.nome_empresa as parceiro,    -- Nome da empresa que pagou
        r.valor,                        -- Valor recebido
        r.status,                       -- Status (pago, pendente, cancelado)
        r.data_receita,                 -- Data que foi recebido
        r.metodo_pagamento              -- Método (cartão, boleto, PIX, etc)
    FROM receitas r
    JOIN parceiros pa ON r.parceiro_id = pa.id  -- Conecta pelo ID do parceiro
    ORDER BY r.data_receita DESC                -- Ordena pelos mais recentes
");

if ($result) {
    while ($row = $result->fetch_assoc()) {
        $receitas[] = $row;
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

// ============================================================================
// BUSCAR LISTA DE ASSINATURAS (para o formulário)
// ============================================================================
// Buscamos as assinaturas ativas para mostrar no select (dropdown)
// Isso permite vincular uma receita a uma assinatura específica
$assinaturas = [];
$result = $conn->query("
    SELECT
        a.id,                   -- ID da assinatura
        p.nome_empresa,         -- Nome da empresa
        pl.nome                 -- Nome do plano
    FROM assinaturas a
    JOIN parceiros p ON a.parceiro_id = p.id      -- Conecta com parceiros
    JOIN planos pl ON a.plano_id = pl.id          -- Conecta com planos
    WHERE a.status = 'ativa'                       -- Apenas assinaturas ativas
    ORDER BY p.nome_empresa ASC
");

if ($result) {
    while ($row = $result->fetch_assoc()) {
        $assinaturas[] = $row;
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
                <h1 class="h3 mb-0 text-gray-800">Gerenciar Receitas</h1>
                <a href="#" class="d-none d-sm-inline-block btn btn-sm btn-primary shadow-sm" data-toggle="modal" data-target="#novaReceita">
                    <i class="fas fa-plus fa-sm text-white-50"></i> Nova Receita
                </a>
            </div>

            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Lista de Receitas</h6>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered" width="100%" cellspacing="0">
                            <thead>
                                <tr>
                                    <th>Tipo</th>
                                    <th>Parceiro</th>
                                    <th>Valor</th>
                                    <th>Método</th>
                                    <th>Status</th>
                                    <th>Data</th>
                                    <th>Ações</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($receitas as $receita): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($receita['tipo']); ?></td>
                                        <td><?php echo htmlspecialchars($receita['parceiro']); ?></td>
                                        <td>R$ <?php echo number_format($receita['valor'], 2, ',', '.'); ?></td>
                                        <td><?php echo htmlspecialchars($receita['metodo_pagamento']); ?></td>
                                        <td>
                                            <span class="badge badge-<?php echo ($receita['status'] === 'pago') ? 'success' : (($receita['status'] === 'cancelado') ? 'danger' : 'warning'); ?>">
                                                <?php echo htmlspecialchars($receita['status']); ?>
                                            </span>
                                        </td>
                                        <td><?php echo date('d/m/Y', strtotime($receita['data_receita'])); ?></td>
                                        <td>
                                            <a href="#" class="btn btn-sm btn-info" title="Editar" data-toggle="modal" data-target="#editarReceita" onclick="setReceitaEdit(<?php echo htmlspecialchars(json_encode($receita)); ?>)"><i class="fas fa-edit"></i></a>
                                            <a href="../app/actions/deletar-receita.php?id=<?php echo $receita['id']; ?>" class="btn btn-sm btn-danger" title="Deletar" onclick="return confirm('Tem certeza que deseja deletar esta receita?')"><i class="fas fa-trash"></i></a>
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

<!-- Modal Nova Receita -->
<div class="modal fade" id="novaReceita" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Nova Receita</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form method="POST" action="../app/actions/criar-receita.php">
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
                        <label for="assinatura_id">Assinatura</label>
                        <select class="form-control" id="assinatura_id" name="assinatura_id">
                            <option value="">Selecione uma assinatura</option>
                            <?php foreach ($assinaturas as $assinatura): ?>
                                <option value="<?php echo $assinatura['id']; ?>">
                                    <?php echo htmlspecialchars($assinatura['nome_empresa'] . ' - ' . $assinatura['nome']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="tipo">Tipo *</label>
                        <select class="form-control" id="tipo" name="tipo" required>
                            <option value="assinatura">Assinatura</option>
                            <option value="renovacao">Renovação</option>
                            <option value="upgrade">Upgrade</option>
                            <option value="outro">Outro</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="valor">Valor *</label>
                        <input type="number" step="0.01" class="form-control" id="valor" name="valor" required>
                    </div>
                    <div class="form-group">
                        <label for="metodo_pagamento">Método de Pagamento</label>
                        <input type="text" class="form-control" id="metodo_pagamento" name="metodo_pagamento">
                    </div>
                    <div class="form-group">
                        <label for="data_receita">Data da Receita *</label>
                        <input type="date" class="form-control" id="data_receita" name="data_receita" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Criar Receita</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Editar Receita -->
<div class="modal fade" id="editarReceita" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Editar Receita</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form method="POST" action="../app/actions/editar-receita.php">
                <div class="modal-body">
                    <input type="hidden" id="edit_id" name="id">
                    <div class="form-group">
                        <label for="edit_tipo">Tipo *</label>
                        <select class="form-control" id="edit_tipo" name="tipo" required>
                            <option value="assinatura">Assinatura</option>
                            <option value="renovacao">Renovação</option>
                            <option value="upgrade">Upgrade</option>
                            <option value="outro">Outro</option>
                        </select>
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
function setReceitaEdit(receita) {
    document.getElementById('edit_id').value = receita.id;
    document.getElementById('edit_tipo').value = receita.tipo;
    document.getElementById('edit_valor').value = receita.valor;
    document.getElementById('edit_status').value = receita.status;
}
</script>

<?php $conn->close(); ?>
<?php require_once '../app/views/admin-layout-footer.php'; ?>

