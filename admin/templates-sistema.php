<?php
/**
 * Templates Padrão do Sistema (Admin)
 * Templates criados aqui são visíveis para TODOS os parceiros
 */

require_once '../app/config/config.php';

if (!isAuthenticated() || !hasRole(ROLE_ADMIN)) {
    redirect(APP_URL . '/login.php');
}

$page_title = 'Templates do Sistema - ' . APP_NAME;
$conn = getDBConnection();

// Buscar templates do SISTEMA (template_sistema = 1)
$templates = [];
$stmt = $conn->prepare("
    SELECT t.*, 
           (SELECT COUNT(*) FROM certificados WHERE template_id = t.id) as total_usos
    FROM templates_certificados t
    WHERE t.template_sistema = 1 
    ORDER BY t.ativo DESC, t.criado_em DESC
");
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $templates[] = $row;
}
$stmt->close();
?>
<?php require_once '../app/views/admin-layout-header.php'; ?>

<style>
    .template-card {
        background: white;
        border: 2px solid #e0e0e0;
        border-radius: 12px;
        padding: 20px;
        margin-bottom: 20px;
        transition: all 0.3s;
    }

    .template-card:hover {
        border-color: #6E41C1;
        box-shadow: 0 4px 12px rgba(110, 65, 193, 0.15);
    }

    .template-preview {
        width: 100%;
        height: 200px;
        object-fit: cover;
        border-radius: 8px;
        margin-bottom: 15px;
    }

    .badge-sistema {
        background: linear-gradient(135deg, #6E41C1 0%, #8B5FD6 100%);
        color: white;
        padding: 4px 12px;
        border-radius: 20px;
        font-size: 12px;
        font-weight: 600;
    }

    .badge-ativo {
        background: #34C759;
        color: white;
        padding: 4px 12px;
        border-radius: 20px;
        font-size: 12px;
    }

    .badge-inativo {
        background: #999;
        color: white;
        padding: 4px 12px;
        border-radius: 20px;
        font-size: 12px;
    }

    .btn-excluir {
        background: #dc3545 !important;
        color: white !important;
    }

    .btn-excluir:hover {
        background: #c82333 !important;
    }
</style>

<!-- Mensagens -->
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

<!-- Header -->
<div class="page-header">
    <h1>Templates do Sistema</h1>
    <div class="action-buttons">
        <button class="button button-primary" onclick="document.getElementById('novoTemplate').style.display='block'">
            <span class="icon">add</span> Novo Template Padrão
        </button>
    </div>
</div>

<!-- Info Box -->
<div class="alert alert-info" style="margin-bottom: 25px;">
    <span class="icon">info</span>
    <div>
        <strong>ℹ️ Templates do Sistema:</strong><br>
        Templates criados aqui são templates padrão que aparecerão para TODOS os parceiros.<br>
        Os parceiros podem usar e visualizar esses templates, mas NÃO podem excluir.
    </div>
</div>

<!-- Lista de Templates -->
<section class="table-section">
    <div class="card">
        <h2><span class="icon">layers</span>Templates Padrão do Sistema</h2>

        <?php if (empty($templates)): ?>
            <div style="text-align: center; padding: 40px; color: #999;">
                <span class="icon" style="font-size: 48px; color: #ddd;">inbox</span>
                <p>Nenhum template padrão criado ainda.</p>
                <p style="font-size: 14px;">Crie templates que todos os parceiros poderão usar!</p>
            </div>
        <?php else: ?>
            <div
                style="display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 20px; padding: 20px;">
                <?php foreach ($templates as $template): ?>
                    <div class="template-card">
                        <?php if (!empty($template['arquivo_url'])): ?>
                            <img src="<?php echo htmlspecialchars($template['arquivo_url']); ?>" alt="Preview"
                                class="template-preview"
                                onerror="this.src='data:image/svg+xml,%3Csvg xmlns=%22http://www.w3.org/2000/svg%22 width=%22300%22 height=%22200%22%3E%3Crect fill=%22%23f0f0f0%22 width=%22300%22 height=%22200%22/%3E%3Ctext x=%2250%25%22 y=%2250%25%22 dominant-baseline=%22middle%22 text-anchor=%22middle%22 font-family=%22Arial%22 font-size=%2218%22 fill=%22%23999%22%3ESem Preview%3C/text%3E%3C/svg%3E'">
                        <?php endif; ?>

                        <div style="margin-bottom: 10px;">
                            <h3 style="margin: 0 0 8px 0; font-size: 18px; color: #1D1D1F;">
                                <?php echo htmlspecialchars($template['nome']); ?>
                            </h3>
                            <div style="display: flex; gap: 8px; flex-wrap: wrap;">
                                <span class="badge-sistema">SISTEMA</span>
                                <?php if ($template['ativo'] == 1): ?>
                                    <span class="badge-ativo">Ativo</span>
                                <?php else: ?>
                                    <span class="badge-inativo">Inativo</span>
                                <?php endif; ?>
                            </div>
                        </div>

                        <p style="color: #666; font-size: 14px; margin: 10px 0;">
                            <?php echo htmlspecialchars($template['descricao'] ?? 'Sem descrição'); ?>
                        </p>

                        <div style="padding-top: 15px; border-top: 1px solid #e0e0e0; margin-top: 15px;">
                            <div
                                style="display: flex; justify-content: space-between; align-items: center; color: #999; font-size: 13px; margin-bottom: 15px;">
                                <span>📊
                                    Usado<?php echo $template['total_usos'] > 0 ? ' ' . $template['total_usos'] . 'x' : ' 0x'; ?></span>
                                <span>📅 <?php echo date('d/m/Y', strtotime($template['criado_em'])); ?></span>
                            </div>

                            <!-- Botões de ação -->
                            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 8px; margin-bottom: 8px;">
                                <a href="../app/actions/visualizar-template.php?id=<?php echo $template['id']; ?>"
                                    class="button button-secondary" style="text-align: center; font-size: 13px; padding: 8px;"
                                    target="_blank">
                                    <span class="icon" style="margin: 0 5px 0 0; font-size: 16px;">visibility</span>
                                    Ver
                                </a>
                                <button onclick="editarTemplate(<?php echo $template['id']; ?>)" class="button button-secondary"
                                    style="font-size: 13px; padding: 8px;">
                                    <span class="icon" style="margin: 0 5px 0 0; font-size: 16px;">edit</span>
                                    Editar
                                </button>
                            </div>

                            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 8px;">
                                <button
                                    onclick="toggleStatus(<?php echo $template['id']; ?>, <?php echo $template['ativo']; ?>)"
                                    class="button button-secondary" style="font-size: 13px; padding: 8px;">
                                    <span class="icon" style="margin: 0 5px 0 0; font-size: 16px;">
                                        <?php echo $template['ativo'] ? 'visibility_off' : 'visibility'; ?>
                                    </span>
                                    <?php echo $template['ativo'] ? 'Ocultar' : 'Ativar'; ?>
                                </button>
                                <button
                                    onclick="excluirTemplate(<?php echo $template['id']; ?>, '<?php echo htmlspecialchars($template['nome'], ENT_QUOTES); ?>')"
                                    class="button btn-excluir" style="font-size: 13px; padding: 8px;">
                                    <span class="icon" style="margin: 0 5px 0 0; font-size: 16px;">delete</span>
                                    Excluir
                                </button>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</section>

<!-- Modal Novo Template -->
<div class="modal" id="novoTemplate" style="background-color: rgba(0, 0, 0, 0.6);">
    <div class="modal-content" style="max-width: 600px; margin: 5% auto;">
        <div class="modal-header">
            <h2>Novo Template do Sistema</h2>
            <button class="close"
                onclick="document.getElementById('novoTemplate').style.display='none'">&times;</button>
        </div>
        <form method="POST" action="../app/actions/criar-template-sistema.php" enctype="multipart/form-data">
            <div class="modal-body">
                <div class="form-group">
                    <label for="nome">Nome do Template *</label>
                    <input type="text" class="form-control" id="nome" name="nome" required
                        placeholder="Ex: Certificado Padrão FaCiencia">
                </div>

                <div class="form-group">
                    <label for="descricao">Descrição</label>
                    <textarea class="form-control" id="descricao" name="descricao" rows="3"
                        placeholder="Descreva o template..."></textarea>
                </div>

                <div class="form-group">
                    <label for="arquivo">Imagem de Fundo (PNG/JPG) *</label>
                    <input type="file" class="form-control" id="arquivo" name="arquivo" accept="image/*" required>
                    <small style="color: #666;">Tamanho recomendado: 1920x1080px ou proporção similar</small>
                </div>

                <div class="form-group">
                    <label>
                        <input type="checkbox" name="ativo" value="1" checked style="width: auto; margin-right: 8px;">
                        Ativar template imediatamente
                    </label>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="button button-secondary"
                    onclick="document.getElementById('novoTemplate').style.display='none'">Cancelar</button>
                <button type="submit" class="button button-primary">Criar Template</button>
            </div>
        </form>
    </div>
</div>

<script>
    // Fechar modal ao clicar fora
    window.onclick = function (event) {
        if (event.target.classList.contains('modal')) {
            event.target.style.display = 'none';
        }
    }

    function editarTemplate(id) {
        window.location.href = 'editor-template-sistema.php?id=' + id;
    }

    function toggleStatus(id, statusAtual) {
        const acao = statusAtual == 1 ? 'desativar' : 'ativar';
        if (confirm(`Tem certeza que deseja ${acao} este template?`)) {
            window.location.href = `../app/actions/toggle-template-sistema.php?id=${id}&status=${statusAtual == 1 ? 0 : 1}`;
        }
    }

    function excluirTemplate(id, nome) {
        if (confirm(`⚠️ ATENÇÃO!\n\nDeseja realmente EXCLUIR o template "${nome}"?\n\nEsta ação NÃO pode ser desfeita!\n\nTodos os certificados gerados com este template continuarão existindo, mas você não poderá mais usar este template.`)) {
            window.location.href = `../app/actions/excluir-template-sistema.php?id=${id}`;
        }
    }
</script>

<?php require_once '../app/views/admin-layout-footer.php'; ?>
<?php $conn->close(); ?>