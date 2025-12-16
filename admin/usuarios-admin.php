<?php
/**
 * ============================================================================
 * GERENCIAR USUÁRIOS ADMINISTRATIVOS
 * ============================================================================
 */

require_once '../app/config/config.php';

// Verificar autenticação e permissão
if (!isAuthenticated() || !hasRole(ROLE_ADMIN)) {
    redirect(APP_URL . '/login.php');
}

$page_title = 'Usuários Administrativos - ' . APP_NAME;
$conn = getDBConnection();

// Buscar administradores
$admins = [];
$result = $conn->query("SELECT id, nome, email, ativo, criado_em FROM administradores ORDER BY nome ASC");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $admins[] = $row;
    }
}

require_once '../app/views/admin-layout-header.php';
?>

<div class="page-header">
    <h1>Usuários Administrativos</h1>
    <div class="action-buttons">
        <button class="button button-primary" onclick="openModal('novoAdmin')">
            <span class="icon">add</span> Novo Administrador
        </button>
    </div>
</div>

<section class="table-section">
    <div class="card">
        <h2><span class="icon">admin_panel_settings</span>Lista de Administradores</h2>
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>Nome</th>
                        <th>Email</th>
                        <th>Status</th>
                        <th>Data Criação</th>
                        <th>Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($admins as $admin): ?>
                        <tr>
                            <td><strong><?php echo htmlspecialchars($admin['nome']); ?></strong></td>
                            <td><?php echo htmlspecialchars($admin['email']); ?></td>
                            <td>
                                <span class="badge badge-<?php echo $admin['ativo'] ? 'success' : 'danger'; ?>">
                                    <?php echo $admin['ativo'] ? 'Ativo' : 'Inativo'; ?>
                                </span>
                            </td>
                            <td><?php echo date('d/m/Y H:i', strtotime($admin['criado_em'])); ?></td>
                            <td>
                                <?php if ($admin['id'] != $_SESSION['user_id']): ?>
                                    <a href="../app/actions/deletar-usuario-admin.php?id=<?php echo $admin['id']; ?>"
                                        class="btn btn-sm btn-danger"
                                        onclick="return confirm('Tem certeza que deseja remover este administrador?')"
                                        title="Remover">
                                        <i class="fas fa-trash"></i>
                                    </a>
                                <?php else: ?>
                                    <span class="text-muted" style="font-size: 0.8rem;">(Você)</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</section>

<!-- Modal Novo Admin -->
<div class="modal fade" id="novoAdmin" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Novo Administrador</h5>
                <button type="button" class="close" onclick="closeModal('novoAdmin')" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form method="POST" action="../app/actions/criar-usuario-admin.php">
                <div class="modal-body">
                    <div class="form-group">
                        <label for="nome">Nome Completo</label>
                        <input type="text" class="form-control" id="nome" name="nome" required>
                    </div>
                    <div class="form-group">
                        <label for="email">Email</label>
                        <input type="email" class="form-control" id="email" name="email" required>
                    </div>
                    <div class="form-group">
                        <label for="senha">Senha</label>
                        <input type="password" class="form-control" id="senha" name="senha" required minlength="6">
                        <small class="form-text text-muted">Mínimo de 6 caracteres.</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="closeModal('novoAdmin')">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Criar Administrador</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once '../app/views/footer.php'; ?>

<script>
    function openModal(modalId) {
        const modal = document.getElementById(modalId);
        if (modal) {
            modal.classList.add('show');
            modal.style.display = 'block';
            modal.removeAttribute('aria-hidden');

            const backdrop = document.createElement('div');
            backdrop.className = 'modal-backdrop fade show';
            backdrop.id = modalId + '-backdrop';
            document.body.appendChild(backdrop);
            document.body.classList.add('modal-open');
        }
    }

    function closeModal(modalId) {
        const modal = document.getElementById(modalId);
        if (modal) {
            modal.classList.remove('show');
            modal.style.display = 'none';
            modal.setAttribute('aria-hidden', 'true');

            const backdrop = document.getElementById(modalId + '-backdrop');
            if (backdrop) backdrop.remove();
            document.body.classList.remove('modal-open');
        }
    }

    // Fechar modal ao clicar fora
    window.onclick = function (event) {
        if (event.target.classList.contains('modal')) {
            closeModal(event.target.id);
        }
    }
</script>

<?php $conn->close(); ?>