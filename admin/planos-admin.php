<?php
/**
 * ============================================================================
 * GERENCIAR PLANOS - SISTEMA DE CERTIFICADOS
 * ============================================================================
 *
 * Esta página permite que o admin veja, crie, edite e delete planos.
 * Planos = pacotes de certificados que os parceiros podem contratar.
 * Por exemplo: Plano Básico (100 certificados), Plano Pro (500 certificados), etc.
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
$page_title = 'Planos - ' . APP_NAME;

// Conecta ao banco de dados
$conn = getDBConnection();

// ============================================================================
// BUSCAR LISTA DE PLANOS
// ============================================================================
// Consulta SQL para pegar todos os planos
// SELECT = pega dados
// FROM planos = da tabela "planos"
// ORDER BY quantidade_certificados ASC = ordena pela quantidade (menor para maior)
$planos = [];
$result = $conn->query("
    SELECT id, nome, quantidade_certificados, certificados_mensais, valor, max_parcelas, descricao, ativo
    FROM planos
    ORDER BY quantidade_certificados ASC
");

// Se a consulta funcionou
if ($result) {
    // Pega cada plano e adiciona ao array
    while ($row = $result->fetch_assoc()) {
        $planos[] = $row;
    }
}
?>
<?php require_once '../app/views/admin-layout-header.php'; ?>

<style>
    /* Fix para modais do Bootstrap funcionarem corretamente */
    .modal.fade {
        display: none !important;
    }

    .modal.fade.show {
        display: block !important;
    }

    .modal-backdrop {
        z-index: 999;
    }

    .modal {
        z-index: 1000;
    }

    .modal-dialog {
        margin: 1.75rem auto;
    }

    .modal-content {
        background-color: #fff;
        border: 1px solid rgba(0, 0, 0, .2);
        border-radius: 14px;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.15);
    }

    .modal-header {
        padding: 20px 24px;
        border-bottom: 1px solid #E0E0E0;
    }

    .modal-body {
        padding: 24px;
    }

    .modal-footer {
        padding: 16px 24px;
        border-top: 1px solid #E0E0E0;
    }

    .modal-title {
        font-size: 1.3rem;
        font-weight: 600;
        color: #1D1D1F;
    }

    .close {
        font-size: 28px;
        font-weight: bold;
        color: #6B6B6B;
        opacity: 1;
    }

    .close:hover {
        color: #1D1D1F;
    }

    /* Estilos para formulários dentro do modal */
    .modal .form-control {
        border: 1px solid #D0D0D0;
        border-radius: 8px;
        padding: 10px 14px;
        font-size: 0.9rem;
    }

    .modal .form-control:focus {
        border-color: #6E41C1;
        box-shadow: 0 0 0 0.2rem rgba(110, 65, 193, 0.25);
    }

    .modal .btn {
        border-radius: 10px;
        padding: 10px 18px;
        font-weight: 500;
    }

    .modal .btn-primary {
        background-color: #6E41C1;
        border-color: #6E41C1;
    }

    .modal .btn-primary:hover {
        background-color: #56349A;
        border-color: #56349A;
    }

    .modal .btn-secondary {
        background-color: #F0F0F0;
        border-color: #D0D0D0;
        color: #1D1D1F;
    }

    .modal .btn-secondary:hover {
        background-color: #E6E6E6;
    }

    .modal .form-text {
        font-size: 0.8rem;
        color: #6B6B6B;
        margin-top: 5px;
    }
</style>

<!-- Page Header -->
<div class="page-header">
    <h1>Gerenciar Planos</h1>
    <div class="action-buttons">
        <button class="button button-primary" onclick="openModal('novoPlano')">
            <span class="icon">add</span> Novo Plano
        </button>
    </div>
</div>

<!-- Tabela de Planos -->
<section class="table-section">
    <div class="card">
        <h2><span class="icon">price_check</span>Lista de Planos</h2>
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>Nome</th>
                        <th>Certificados (Total)</th>
                        <th>Limite Mensal</th>
                        <th>Templates</th>
                        <th>Valor Mensal</th>
                        <th>Parcelas</th>
                        <th>Descrição</th>
                        <th>Status</th>
                        <th>Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($planos as $plano): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($plano['nome']); ?></td>
                            <td><?php echo $plano['quantidade_certificados']; ?></td>
                            <td><?php echo $plano['certificados_mensais']; ?></td>
                            <td><?php echo $plano['quantidade_templates'] ?? 5; ?></td>
                            <td>R$ <?php echo number_format($plano['valor'], 2, ',', '.'); ?> (1 ano)</td>
                            <td>
                                <span class="badge badge-info">
                                    Até <?php echo $plano['max_parcelas'] ?? 1; ?>x
                                </span>
                            </td>
                            <td><?php echo htmlspecialchars($plano['descricao']); ?></td>
                            <td>
                                <span class="badge badge-<?php echo ($plano['ativo']) ? 'success' : 'danger'; ?>">
                                    <?php echo ($plano['ativo']) ? 'Ativo' : 'Inativo'; ?>
                                </span>
                            </td>
                            <td>
                                <a href="#" class="btn btn-sm btn-info" title="Editar"
                                    onclick="setPlanoEdit(<?php echo htmlspecialchars(json_encode($plano)); ?>); openModal('editarPlano'); return false;"><i
                                        class="fas fa-edit"></i></a>
                                <a href="../app/actions/deletar-plano.php?id=<?php echo $plano['id']; ?>"
                                    class="btn btn-sm btn-danger" title="Deletar"
                                    onclick="return confirm('Tem certeza que deseja deletar este plano?')"><i
                                        class="fas fa-trash"></i></a>
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

    <!-- Modal Novo Plano -->
    <div class="modal fade" id="novoPlano" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Novo Plano</h5>
                    <button type="button" class="close" onclick="closeModal('novoPlano')" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <form method="POST" action="../app/actions/criar-plano.php">
                    <div class="modal-body">
                        <div class="form-group">
                            <label for="nome">Nome do Plano</label>
                            <input type="text" class="form-control" id="nome" name="nome" required
                                placeholder="Ex: Plano Básico">
                        </div>
                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="certificados">Certificados (Total)</label>
                                    <input type="number" class="form-control" id="certificados" name="certificados"
                                        required min="1" placeholder="Ex: 100">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="certificados_mensais">Limite Mensal</label>
                                    <input type="number" class="form-control" id="certificados_mensais"
                                        name="certificados_mensais" required min="1" placeholder="Ex: 10">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="templates">Quantidade de Templates</label>
                                    <input type="number" class="form-control" id="templates" name="templates" required
                                        min="1" value="5" placeholder="Ex: 5">
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="valor">Valor Anual (R$)</label>
                                    <input type="number" step="0.01" class="form-control" id="valor" name="valor"
                                        required min="0" placeholder="Ex: 599.90">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="max_parcelas">Máximo de Parcelas</label>
                                    <select class="form-control" id="max_parcelas" name="max_parcelas" required>
                                        <option value="1">1x (À vista)</option>
                                        <option value="2">Até 2x</option>
                                        <option value="3">Até 3x</option>
                                        <option value="4">Até 4x</option>
                                        <option value="6">Até 6x</option>
                                        <option value="12">Até 12x</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div class="form-group">
                            <small class="form-text text-muted">Define quantas vezes o cliente pode parcelar este
                                plano</small>
                        </div>
                        <div class="form-group">
                            <label for="descricao">Descrição</label>
                            <textarea class="form-control" id="descricao" name="descricao" rows="3"
                                placeholder="Descreva os benefícios deste plano..."></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary"
                            onclick="closeModal('novoPlano')">Cancelar</button>
                        <button type="submit" class="btn btn-primary">Criar Plano</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal Editar Plano -->
    <div class="modal fade" id="editarPlano" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Editar Plano</h5>
                    <button type="button" class="close" onclick="closeModal('editarPlano')" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <form method="POST" action="../app/actions/editar-plano.php">
                    <div class="modal-body">
                        <input type="hidden" id="edit_id" name="id">
                        <div class="form-group">
                            <label for="edit_nome">Nome do Plano</label>
                            <input type="text" class="form-control" id="edit_nome" name="nome" required>
                        </div>
                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="edit_certificados">Certificados (Total)</label>
                                    <input type="number" class="form-control" id="edit_certificados" name="certificados"
                                        required min="1">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="edit_certificados_mensais">Limite Mensal</label>
                                    <input type="number" class="form-control" id="edit_certificados_mensais"
                                        name="certificados_mensais" required min="1">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="edit_templates">Quantidade de Templates</label>
                                    <input type="number" class="form-control" id="edit_templates" name="templates"
                                        required min="1">
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="edit_valor">Valor Anual (R$)</label>
                                    <input type="number" step="0.01" class="form-control" id="edit_valor" name="valor"
                                        required min="0">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="edit_max_parcelas">Máximo de Parcelas</label>
                                    <select class="form-control" id="edit_max_parcelas" name="max_parcelas" required>
                                        <option value="1">1x (À vista)</option>
                                        <option value="2">Até 2x</option>
                                        <option value="3">Até 3x</option>
                                        <option value="4">Até 4x</option>
                                        <option value="6">Até 6x</option>
                                        <option value="12">Até 12x</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div class="form-group">
                            <small class="form-text text-muted">Define quantas vezes o cliente pode parcelar este
                                plano</small>
                        </div>
                        <div class="form-group">
                            <label for="edit_descricao">Descrição</label>
                            <textarea class="form-control" id="edit_descricao" name="descricao" rows="3"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary"
                            onclick="closeModal('editarPlano')">Cancelar</button>
                        <button type="submit" class="btn btn-primary">Salvar Alterações</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <?php require_once '../app/views/footer.php'; ?>

    <script>
        // Funções para controlar modais
        function openModal(modalId) {
            const modal = document.getElementById(modalId);
            if (modal) {
                modal.classList.add('show');
                modal.style.display = 'block';
                modal.removeAttribute('aria-hidden');

                // Criar backdrop
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

                // Remover backdrop
                const backdrop = document.getElementById(modalId + '-backdrop');
                if (backdrop) {
                    backdrop.remove();
                }
                document.body.classList.remove('modal-open');
            }
        }

        // Fechar modal ao clicar no backdrop
        document.addEventListener('click', function (e) {
            if (e.target.classList.contains('modal-backdrop')) {
                const modalId = e.target.id.replace('-backdrop', '');
                closeModal(modalId);
            }
        });

        // Fechar modal com ESC
        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape') {
                const modals = document.querySelectorAll('.modal.show');
                modals.forEach(modal => {
                    closeModal(modal.id);
                });
            }
        });

        function setPlanoEdit(plano) {
            document.getElementById('edit_id').value = plano.id;
            document.getElementById('edit_nome').value = plano.nome;
            document.getElementById('edit_certificados').value = plano.quantidade_certificados;
            document.getElementById('edit_certificados_mensais').value = plano.certificados_mensais || 0;
            document.getElementById('edit_templates').value = plano.quantidade_templates || 5;
            document.getElementById('edit_valor').value = plano.valor;
            document.getElementById('edit_max_parcelas').value = plano.max_parcelas || 1;
            document.getElementById('edit_descricao').value = plano.descricao;
        }
    </script>

    <?php $conn->close(); ?>