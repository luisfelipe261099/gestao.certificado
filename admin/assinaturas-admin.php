<?php
/**
 * ============================================================================
 * GERENCIAR ASSINATURAS - SISTEMA DE CERTIFICADOS
 * ============================================================================
 *
 * Esta página permite que o admin veja, crie, edite e delete assinaturas.
 * Assinatura = quando um parceiro contrata um plano.
 * Por exemplo: Empresa XYZ assinou o Plano Pro por 1 ano.
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
$page_title = 'Assinaturas - ' . APP_NAME;

// Conecta ao banco de dados
$conn = getDBConnection();

// ============================================================================
// BUSCAR LISTA DE ASSINATURAS
// ============================================================================
// Consulta SQL com JOIN = combina dados de várias tabelas
//
// SELECT = pega dados
// FROM assinaturas a = da tabela "assinaturas" (com apelido "a")
// JOIN parceiros p = junta com a tabela "parceiros" (apelido "p")
// JOIN planos pl = junta com a tabela "planos" (apelido "pl")
//
// Isso permite pegar o nome do parceiro e do plano junto com a assinatura
$assinaturas = [];
$result = $conn->query("
    SELECT
        a.id,
        a.parceiro_id,
        a.plano_id,
        p.nome_empresa as parceiro,      -- Nome da empresa do parceiro
        pl.nome as plano,                 -- Nome do plano
        pl.valor as valor,                -- Valor do plano
        a.status,                         -- Status da assinatura (ativa, cancelada, etc)
        a.data_inicio,                    -- Quando começou
        a.data_vencimento                 -- Quando termina
    FROM assinaturas a
    JOIN parceiros p ON a.parceiro_id = p.id      -- Conecta pelo ID do parceiro
    JOIN planos pl ON a.plano_id = pl.id          -- Conecta pelo ID do plano
    ORDER BY a.data_vencimento DESC                -- Ordena pelas que vencem primeiro
");

if ($result) {
    while ($row = $result->fetch_assoc()) {
        $assinaturas[] = $row;
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
// BUSCAR LISTA DE PLANOS (para o formulário)
// ============================================================================
// Buscamos os planos ativos para mostrar no select (dropdown)
$planos = [];
$result = $conn->query("
    SELECT id, nome, quantidade_certificados
    FROM planos
    WHERE ativo = 1
    ORDER BY quantidade_certificados ASC
");

if ($result) {
    while ($row = $result->fetch_assoc()) {
        $planos[] = $row;
    }
}
?>
<?php require_once '../app/views/admin-layout-header.php'; ?>

                <!-- Page Header -->
                <div class="page-header">
                    <h1>Gerenciar Assinaturas</h1>
                    <div class="action-buttons">
                        <button class="button button-primary" onclick="document.getElementById('novaAssinatura').style.display='block'">
                            <span class="icon">add</span> Nova Assinatura
                        </button>
                    </div>
                </div>

                <!-- Tabela de Assinaturas -->
                <section class="table-section">
                    <div class="card">
                        <h2><span class="icon">card_membership</span>Lista de Assinaturas</h2>
                        <div class="table-responsive">
                            <table class="table">
                            <thead>
                                <tr>
                                    <th>Parceiro</th>
                                    <th>Plano</th>
                                    <th>Valor</th>
                                    <th>Status</th>
                                    <th>Início</th>
                                    <th>Vencimento</th>
                                    <th>Ações</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($assinaturas as $assinatura): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($assinatura['parceiro']); ?></td>
                                        <td><?php echo htmlspecialchars($assinatura['plano']); ?></td>
                                        <td>R$ <?php echo number_format($assinatura['valor'], 2, ',', '.'); ?></td>
                                        <td>
                                            <span class="badge badge-<?php echo ($assinatura['status'] === 'ativa') ? 'success' : 'danger'; ?>">
                                                <?php echo htmlspecialchars($assinatura['status']); ?>
                                            </span>
                                        </td>
                                        <td><?php echo date('d/m/Y', strtotime($assinatura['data_inicio'])); ?></td>
                                        <td><?php echo date('d/m/Y', strtotime($assinatura['data_vencimento'])); ?></td>
                                        <td>
                                            <a href="#" class="btn btn-sm btn-info" title="Editar" data-toggle="modal" data-target="#editarAssinatura" onclick="setAssinaturaEdit(<?php echo htmlspecialchars(json_encode($assinatura)); ?>)"><i class="fas fa-edit"></i></a>
                                            <a href="../app/actions/deletar-assinatura.php?id=<?php echo $assinatura['id']; ?>" class="btn btn-sm btn-danger" title="Deletar" onclick="return confirm('Tem certeza que deseja deletar esta assinatura?')"><i class="fas fa-trash"></i></a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                  </div>
                </section>

<?php require_once '../app/views/admin-layout-footer.php'; ?>

<!-- Modal Nova Assinatura -->
<div class="modal" id="novaAssinatura" style="background-color: rgba(0, 0, 0, 0.6);">
    <div class="modal-content" style="max-width: 600px; margin: 5% auto; max-height: 90vh; overflow-y: auto; box-shadow: 0 10px 40px rgba(0, 0, 0, 0.3);">
        <div class="modal-header">
            <h2>Nova Assinatura</h2>
            <button class="close" onclick="document.getElementById('novaAssinatura').style.display='none'">&times;</button>
        </div>
        <form method="POST" action="../app/actions/criar-assinatura.php">
            <div class="modal-body">
                <div class="form-group">
                    <label for="parceiro_id">Parceiro *</label>
                    <select id="parceiro_id" name="parceiro_id" required>
                        <option value="">Selecione um parceiro</option>
                        <?php foreach ($parceiros as $parceiro): ?>
                            <option value="<?php echo $parceiro['id']; ?>">
                                <?php echo htmlspecialchars($parceiro['nome_empresa']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label for="plano_id">Plano *</label>
                    <select id="plano_id" name="plano_id" required>
                        <option value="">Selecione um plano</option>
                        <?php foreach ($planos as $plano): ?>
                            <option value="<?php echo $plano['id']; ?>">
                                <?php echo htmlspecialchars($plano['nome']); ?> (<?php echo $plano['quantidade_certificados']; ?> certificados)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label for="data_inicio">Data de Início *</label>
                    <input type="date" id="data_inicio" name="data_inicio" required>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="button button-secondary" onclick="document.getElementById('novaAssinatura').style.display='none'">Cancelar</button>
                <button type="submit" class="button button-primary">Criar Assinatura</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Editar Assinatura -->
<div class="modal" id="editarAssinatura" style="background-color: rgba(0, 0, 0, 0.6);">
    <div class="modal-content" style="max-width: 600px; margin: 5% auto; max-height: 90vh; overflow-y: auto; box-shadow: 0 10px 40px rgba(0, 0, 0, 0.3);">
        <div class="modal-header">
            <h2>Editar Assinatura</h2>
            <button class="close" onclick="document.getElementById('editarAssinatura').style.display='none'">&times;</button>
        </div>
        <form method="POST" action="../app/actions/editar-assinatura.php">
            <div class="modal-body">
                <input type="hidden" id="edit_id" name="id">
                <div class="form-group">
                    <label for="edit_parceiro_id">Parceiro *</label>
                    <select id="edit_parceiro_id" name="parceiro_id" required>
                        <option value="">Selecione um parceiro</option>
                        <?php foreach ($parceiros as $parceiro): ?>
                            <option value="<?php echo $parceiro['id']; ?>">
                                <?php echo htmlspecialchars($parceiro['nome_empresa']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label for="edit_plano_id">Plano *</label>
                    <select id="edit_plano_id" name="plano_id" required>
                        <option value="">Selecione um plano</option>
                        <?php foreach ($planos as $plano): ?>
                            <option value="<?php echo $plano['id']; ?>">
                                <?php echo htmlspecialchars($plano['nome']); ?> (<?php echo $plano['quantidade_certificados']; ?> certificados)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label for="edit_data_inicio">Data de Início *</label>
                    <input type="date" id="edit_data_inicio" name="data_inicio" required>
                </div>
                <div class="form-group">
                    <label for="edit_status">Status *</label>
                    <select id="edit_status" name="status" required>
                        <option value="ativa">Ativa</option>
                        <option value="cancelada">Cancelada</option>
                        <option value="expirada">Expirada</option>
                    </select>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="button button-secondary" onclick="document.getElementById('editarAssinatura').style.display='none'">Cancelar</button>
                <button type="submit" class="button button-primary">Salvar Alterações</button>
            </div>
        </form>
    </div>
</div>

<script>
function setAssinaturaEdit(assinatura) {
    document.getElementById('edit_id').value = assinatura.id;
    document.getElementById('edit_parceiro_id').value = assinatura.parceiro_id || '';
    document.getElementById('edit_plano_id').value = assinatura.plano_id || '';
    document.getElementById('edit_data_inicio').value = assinatura.data_inicio || '';
    document.getElementById('edit_status').value = assinatura.status || 'ativa';
    document.getElementById('editarAssinatura').style.display = 'block';
}

// Fechar modal ao clicar fora
window.onclick = function(event) {
    if (event.target.classList.contains('modal')) {
        event.target.style.display = 'none';
    }
}
</script>

<?php $conn->close(); ?>

