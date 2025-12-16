<?php
/**
 * ============================================================================
 * GERENCIAR PARCEIROS - SISTEMA DE CERTIFICADOS
 * ============================================================================
 *
 * Esta página permite que o admin veja, crie, edite e delete parceiros.
 * Parceiros = empresas que usam o sistema para gerar certificados.
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
$page_title = 'Parceiros - ' . APP_NAME;

// Conecta ao banco de dados
$conn = getDBConnection();

// ============================================================================
// BUSCAR LISTA DE PARCEIROS
// ============================================================================
// Consulta SQL = pergunta ao banco de dados
// SELECT = pega dados
// FROM parceiros = da tabela "parceiros"
// ORDER BY criado_em DESC = ordena pela data de criação (mais recentes primeiro)
$parceiros = [];
$result = $conn->query("
    SELECT id, nome_empresa, cnpj, email, telefone, ativo, ead_ativo, criado_em
    FROM parceiros
    ORDER BY criado_em DESC
");

// Se a consulta funcionou
if ($result) {
    // fetch_assoc() = pega uma linha de cada vez
    // while = repete enquanto houver linhas
    while ($row = $result->fetch_assoc()) {
        $parceiros[] = $row; // Adiciona cada parceiro ao array
    }
}

// ============================================================================
// BUSCAR LISTA DE PLANOS
// ============================================================================
// Planos = pacotes que os parceiros podem contratar
// Buscamos apenas os planos ativos (ativo = 1)
$planos = [];
$result = $conn->query("
    SELECT id, nome, valor
    FROM planos
    WHERE ativo = 1
    ORDER BY nome ASC
");

if ($result) {
    while ($row = $result->fetch_assoc()) {
        $planos[] = $row;
    }
}
?>
<?php require_once '../app/views/admin-layout-header.php'; ?>

<!-- Mensagens de Feedback -->
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

<!-- Exibir Senha Gerada -->
<?php if (isset($_SESSION['nova_senha_gerada'])): ?>
    <div
        style="background: linear-gradient(135deg, #d4edda 0%, #c3e6cb 100%); border: 3px solid #28a745; padding: 25px; border-radius: 12px; margin-bottom: 25px; box-shadow: 0 4px 15px rgba(40, 167, 69, 0.3);">
        <div style="text-align: center;">
            <span class="icon" style="font-size: 48px; color: #28a745;">check_circle</span>
            <h2 style="color: #155724; margin: 10px 0;">✅ Senha Resetada!</h2>
        </div>

        <div
            style="background: white; border: 4px solid #6E41C1; padding: 25px; border-radius: 12px; margin: 20px 0; text-align: center;">
            <p style="margin: 0 0 10px 0; font-size: 16px; font-weight: 600; color: #333;">🔑 NOVA SENHA:</p>
            <div
                style="font-size: 42px; font-weight: bold; color: #6E41C1; letter-spacing: 5px; font-family: 'Courier New', monospace; padding: 15px; background: #f8f9fa; border-radius: 8px;">
                <?php echo htmlspecialchars($_SESSION['nova_senha_gerada']); ?>
            </div>
            <p style="margin: 15px 0 0 0; font-size: 14px; color: #666;">
                📧 Email: <strong><?php echo htmlspecialchars($_SESSION['nova_senha_email']); ?></strong>
            </p>
        </div>

        <div style="background: #fff3cd; border: 2px solid #ffc107; padding: 15px; border-radius: 8px; margin-top: 15px;">
            <p style="margin: 0; color: #856404; font-size: 14px;">
                <strong>⚠️ ANOTE ESTA SENHA AGORA!</strong><br>
                Por segurança, ela será exibida apenas UMA vez e desaparecerá quando você atualizar a página.
            </p>
        </div>
    </div>
    <?php
    unset($_SESSION['nova_senha_gerada']);
    unset($_SESSION['nova_senha_email']);
?>
<?php endif; ?>

<!-- Page Header -->
<div class="page-header">
    <h1>Gerenciar Parceiros</h1>
    <div class="action-buttons">
        <button class="button button-primary" onclick="document.getElementById('novoParceiro').style.display='block'">
            <span class="icon">add</span> Novo Parceiro
        </button>
    </div>
</div>

<!-- Tabela de Parceiros -->
<section class="table-section">
    <div class="card">
        <h2><span class="icon">business</span>Lista de Parceiros</h2>
        <div class="table-responsive">
            <table class="table" id="dataTable">
                <thead>
                    <tr>
                        <th>Empresa</th>
                        <th>CNPJ</th>
                        <th>Email</th>
                        <th>Telefone</th>
                        <th>Status</th>
                        <th>EAD</th>
                        <th>Data de Criação</th>
                        <th>Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($parceiros as $parceiro): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($parceiro['nome_empresa']); ?></td>
                            <td><?php echo htmlspecialchars($parceiro['cnpj']); ?></td>
                            <td><?php echo htmlspecialchars($parceiro['email']); ?></td>
                            <td><?php echo htmlspecialchars($parceiro['telefone'] ?? '-'); ?></td>
                            <td>
                                <span class="badge badge-<?php echo ($parceiro['ativo'] == 1) ? 'success' : 'danger'; ?>">
                                    <?php echo ($parceiro['ativo'] == 1) ? 'Ativo' : 'Inativo'; ?>
                                </span>
                            </td>
                            <td>
                                <form method="POST" action="../app/actions/toggle-ead-parceiro.php"
                                    style="display: inline;">
                                    <input type="hidden" name="parceiro_id" value="<?php echo $parceiro['id']; ?>">
                                    <input type="hidden" name="ead_ativo"
                                        value="<?php echo ($parceiro['ead_ativo'] == 1) ? 0 : 1; ?>">
                                    <button type="submit"
                                        class="badge badge-<?php echo ($parceiro['ead_ativo'] == 1) ? 'success' : 'secondary'; ?>"
                                        style="border:none;cursor:pointer;"
                                        title="<?php echo ($parceiro['ead_ativo'] == 1) ? 'Desativar EAD' : 'Ativar EAD'; ?>">
                                        <?php echo ($parceiro['ead_ativo'] == 1) ? 'Ativo' : 'Inativo'; ?>
                                    </button>
                                </form>
                            </td>
                            <td><?php echo date('d/m/Y', strtotime($parceiro['criado_em'])); ?></td>
                            <td>
                                <button class="button button-secondary"
                                    style="min-width:auto;padding:6px 12px;font-size:.85rem;margin-right:4px;"
                                    title="Criar Acesso"
                                    onclick="setParceiroId(<?php echo $parceiro['id']; ?>);document.getElementById('criarAcesso').style.display='block'">
                                    <span class="icon" style="margin:0;font-size:16px;">key</span>
                                </button>
                                <button class="button button-secondary"
                                    style="min-width:auto;padding:6px 12px;font-size:.85rem;margin-right:4px;"
                                    title="Editar"
                                    onclick="setParceiroEdit(<?php echo htmlspecialchars(json_encode($parceiro)); ?>);document.getElementById('editarParceiro').style.display='block'">
                                    <span class="icon" style="margin:0;font-size:16px;">edit</span>
                                </button>
                                <button class="button button-secondary"
                                    style="min-width:auto;padding:6px 12px;font-size:.85rem;margin-right:4px;"
                                    title="Resetar Senha"
                                    onclick="setResetSenha(<?php echo $parceiro['id']; ?>, '<?php echo htmlspecialchars($parceiro['nome_empresa'], ENT_QUOTES); ?>');document.getElementById('resetarSenha').style.display='block'">
                                    <span class="icon" style="margin:0;font-size:16px;">lock_reset</span>
                                </button>
                                <a href="../app/actions/deletar-parceiro.php?id=<?php echo $parceiro['id']; ?>"
                                    class="button button-secondary"
                                    style="min-width:auto;padding:6px 12px;font-size:.85rem;background:#f8d7da;border-color:#f5c6cb;"
                                    title="Deletar"
                                    onclick="return confirm('Tem certeza que deseja deletar este parceiro?')">
                                    <span class="icon" style="margin:0;font-size:16px;color:#721c24;">delete</span>
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</section>

<?php require_once '../app/views/admin-layout-footer.php'; ?>

<!-- Modal Novo Parceiro -->
<div class="modal" id="novoParceiro" style="background-color: rgba(0, 0, 0, 0.6);">
    <div class="modal-content"
        style="max-width: 700px; margin: 3% auto; max-height: 90vh; overflow-y: auto; box-shadow: 0 10px 40px rgba(0, 0, 0, 0.3);">
        <div class="modal-header">
            <h2>Novo Parceiro</h2>
            <button class="close"
                onclick="document.getElementById('novoParceiro').style.display='none'">&times;</button>
        </div>
        <form method="POST" action="../app/actions/criar-parceiro.php">
            <div class="modal-body">
                <div class="form-group">
                    <label for="nome_empresa">Nome da Empresa *</label>
                    <input type="text" class="form-control" id="nome_empresa" name="nome_empresa" required>
                </div>
                <div class="form-group">
                    <label for="cnpj">CNPJ *</label>
                    <input type="text" class="form-control" id="cnpj" name="cnpj" placeholder="00.000.000/0000-00"
                        required>
                </div>
                <div class="form-group">
                    <label for="email">Email *</label>
                    <input type="email" class="form-control" id="email" name="email" required>
                </div>
                <div class="form-group">
                    <label for="plano_id">Plano (Contrato 1 Ano) *</label>
                    <select class="form-control" id="plano_id" name="plano_id" required>
                        <option value="">Selecione um plano</option>
                        <?php foreach ($planos as $plano): ?>
                            <option value="<?php echo $plano['id']; ?>" data-valor="<?php echo $plano['valor']; ?>">
                                <?php echo htmlspecialchars($plano['nome']); ?> - R$
                                <?php echo number_format($plano['valor'], 2, ',', '.'); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label for="telefone">Telefone</label>
                    <input type="tel" class="form-control" id="telefone" name="telefone" placeholder="(00) 00000-0000">
                </div>
                <div class="form-group">
                    <label for="cep">CEP</label>
                    <input type="text" class="form-control" id="cep" name="cep" placeholder="00000-000">
                </div>
                <div class="form-group">
                    <label for="endereco">Endereço</label>
                    <input type="text" class="form-control" id="endereco" name="endereco">
                </div>
                <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 12px;">
                    <div class="form-group">
                        <label for="cidade">Cidade</label>
                        <input type="text" class="form-control" id="cidade" name="cidade">
                    </div>
                    <div class="form-group">
                        <label for="estado">Estado</label>
                        <input type="text" class="form-control" id="estado" name="estado" maxlength="2"
                            placeholder="SP">
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="button button-secondary"
                    onclick="document.getElementById('novoParceiro').style.display='none'">Cancelar</button>
                <button type="submit" class="button button-primary">Criar Parceiro</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Editar Parceiro -->
<div class="modal" id="editarParceiro" style="background-color: rgba(0, 0, 0, 0.6);">
    <div class="modal-content"
        style="max-width: 700px; margin: 3% auto; max-height: 90vh; overflow-y: auto; box-shadow: 0 10px 40px rgba(0, 0, 0, 0.3);">
        <div class="modal-header">
            <h2>Editar Parceiro</h2>
            <button class="close"
                onclick="document.getElementById('editarParceiro').style.display='none'">&times;</button>
        </div>
        <form method="POST" action="../app/actions/editar-parceiro.php">
            <div class="modal-body">
                <input type="hidden" id="edit_id" name="id">
                <div class="form-group">
                    <label for="edit_nome_empresa">Nome da Empresa *</label>
                    <input type="text" class="form-control" id="edit_nome_empresa" name="nome_empresa" required>
                </div>
                <div class="form-group">
                    <label for="edit_cnpj">CNPJ *</label>
                    <input type="text" class="form-control" id="edit_cnpj" name="cnpj" required>
                </div>
                <div class="form-group">
                    <label for="edit_email">Email *</label>
                    <input type="email" class="form-control" id="edit_email" name="email" required>
                </div>
                <div class="form-group">
                    <label for="edit_telefone">Telefone</label>
                    <input type="tel" class="form-control" id="edit_telefone" name="telefone">
                </div>
                <div class="form-group">
                    <label for="edit_cep">CEP</label>
                    <input type="text" class="form-control" id="edit_cep" name="cep" placeholder="00000-000">
                </div>
                <div class="form-group">
                    <label for="edit_endereco">Endereço</label>
                    <input type="text" class="form-control" id="edit_endereco" name="endereco">
                </div>
                <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 12px;">
                    <div class="form-group">
                        <label for="edit_cidade">Cidade</label>
                        <input type="text" class="form-control" id="edit_cidade" name="cidade">
                    </div>
                    <div class="form-group">
                        <label for="edit_estado">Estado</label>
                        <input type="text" class="form-control" id="edit_estado" name="estado" maxlength="2"
                            placeholder="SP">
                    </div>
                </div>

                <hr style="margin: 24px 0; border: none; border-top: 1px solid var(--border-light);">

                <div
                    style="background: linear-gradient(135deg, rgba(110, 65, 193, 0.05) 0%, rgba(110, 65, 193, 0.02) 100%); border: 2px solid rgba(110, 65, 193, 0.2); border-radius: 10px; padding: 16px; margin-bottom: 20px;">
                    <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 8px;">
                        <span class="icon" style="color: #6E41C1; font-size: 20px;">info</span>
                        <strong style="color: #6E41C1; font-size: 14px;">Alterar Senha (Opcional)</strong>
                    </div>
                    <p style="color: #1D1D1F; font-size: 13px; margin: 0; line-height: 1.5;">
                        Deixe os campos em branco se não quiser alterar a senha do parceiro.
                    </p>
                </div>

                <div class="form-group">
                    <label for="edit_nova_senha">Nova Senha</label>
                    <input type="password" class="form-control" id="edit_nova_senha" name="nova_senha"
                        placeholder="Deixe em branco para não alterar">
                </div>
                <div class="form-group">
                    <label for="edit_confirmar_senha">Confirmar Nova Senha</label>
                    <input type="password" class="form-control" id="edit_confirmar_senha" name="confirmar_senha"
                        placeholder="Deixe em branco para não alterar">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="button button-secondary"
                    onclick="document.getElementById('editarParceiro').style.display='none'">Cancelar</button>
                <button type="submit" class="button button-primary">Salvar Alterações</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Criar Acesso Parceiro -->
<div class="modal" id="criarAcesso" style="background-color: rgba(0, 0, 0, 0.6);">
    <div class="modal-content"
        style="max-width: 600px; margin: 5% auto; max-height: 90vh; overflow-y: auto; box-shadow: 0 10px 40px rgba(0, 0, 0, 0.3);">
        <div class="modal-header">
            <h2>Criar Acesso para Parceiro</h2>
            <button class="close" onclick="document.getElementById('criarAcesso').style.display='none'">&times;</button>
        </div>
        <form method="POST" action="../app/actions/criar-acesso-parceiro.php">
            <div class="modal-body">
                <input type="hidden" id="acesso_parceiro_id" name="parceiro_id">
                <div class="alert alert-info">
                    <span class="icon">info</span> Isso criará um usuário de acesso para o parceiro fazer login no
                    sistema.
                </div>
                <div class="form-group">
                    <label for="acesso_email">Email do Parceiro *</label>
                    <input type="email" class="form-control" id="acesso_email" name="email" readonly>
                </div>
                <div class="form-group">
                    <label for="acesso_senha">Senha Temporária *</label>
                    <input type="text" class="form-control" id="acesso_senha" name="senha"
                        placeholder="Deixe em branco para gerar automaticamente">
                </div>
                <div class="form-group">
                    <label for="acesso_nome">Nome do Usuário *</label>
                    <input type="text" class="form-control" id="acesso_nome" name="nome" required>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="button button-secondary"
                    onclick="document.getElementById('criarAcesso').style.display='none'">Cancelar</button>
                <button type="submit" class="button button-primary">Criar Acesso</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Resetar Senha -->
<div class="modal" id="resetarSenha" style="background-color: rgba(0, 0, 0, 0.6);">
    <div class="modal-content" style="max-width: 500px; margin: 5% auto; box-shadow: 0 10px 40px rgba(0, 0, 0, 0.3);">
        <div class="modal-header">
            <h2>Resetar Senha do Parceiro</h2>
            <button class="close"
                onclick="document.getElementById('resetarSenha').style.display='none'">&times;</button>
        </div>
        <form method="POST" action="../app/actions/resetar-senha-parceiro.php">
            <div class="modal-body">
                <input type="hidden" id="reset_parceiro_id" name="parceiro_id">

                <div class="alert alert-warning"
                    style="background: #fff3cd; border-color: #ffc107; color: #856404; margin-bottom: 20px;">
                    <span class="icon" style="color: #856404;">warning</span>
                    <div>
                        <strong>Atenção!</strong><br>
                        Isso irá gerar uma nova senha aleatória e <strong>invalidar a senha atual</strong>.<br>
                        A nova senha será exibida apenas UMA vez.
                    </div>
                </div>

                <div style="background: #f8f9fa; padding: 15px; border-radius: 8px; margin-bottom: 15px;">
                    <strong>Parceiro:</strong><br>
                    <span id="reset_parceiro_nome" style="color: #6E41C1; font-size: 16px;"></span>
                </div>

                <div class="form-group">
                    <label for="reset_nova_senha">Nova Senha (opcional)</label>
                    <input type="text" class="form-control" id="reset_nova_senha" name="nova_senha"
                        placeholder="Deixe em branco para gerar automaticamente">
                    <small style="color: #666; font-size: 12px;">Se deixar em branco, uma senha aleatória será
                        gerada</small>
                </div>

                <div class="form-group">
                    <label>
                        <input type="checkbox" name="enviar_email" value="1" checked
                            style="width: auto; margin-right: 8px;">
                        Enviar nova senha por email ao parceiro
                    </label>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="button button-secondary"
                    onclick="document.getElementById('resetarSenha').style.display='none'">Cancelar</button>
                <button type="submit" class="button button-primary" style="background: #dc3545;">
                    <span class="icon">lock_reset</span> Resetar Senha
                </button>
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
</script>

<script>
    function setParceiroEdit(parceiro) {
        document.getElementById('edit_id').value = parceiro.id;
        document.getElementById('edit_nome_empresa').value = parceiro.nome_empresa;
        document.getElementById('edit_cnpj').value = parceiro.cnpj;
        document.getElementById('edit_email').value = parceiro.email;
        document.getElementById('edit_telefone').value = parceiro.telefone || '';
        document.getElementById('edit_endereco').value = parceiro.endereco || '';
        document.getElementById('edit_cidade').value = parceiro.cidade || '';
        document.getElementById('edit_estado').value = parceiro.estado || '';
        document.getElementById('edit_cep').value = parceiro.cep || '';
    }

    function setParceiroId(parceiroId) {
        document.getElementById('acesso_parceiro_id').value = parceiroId;
        // Buscar email do parceiro via AJAX
        fetch('../app/actions/get-parceiro.php?id=' + parceiroId)
            .then(response => response.json())
            .then(data => {
                document.getElementById('acesso_email').value = data.email;
                document.getElementById('acesso_nome').value = data.nome_empresa;
            });
    }

    function setResetSenha(parceiroId, nomeEmpresa) {
        document.getElementById('reset_parceiro_id').value = parceiroId;
        document.getElementById('reset_parceiro_nome').textContent = nomeEmpresa;
    }

    // Função para buscar CEP
    function buscarCep(valor, prefixo) {
        var cep = valor.replace(/\D/g, '');
        if (cep != "") {
            var validacep = /^[0-9]{8}$/;
            if (validacep.test(cep)) {
                document.getElementById(prefixo + 'endereco').value = "...";
                document.getElementById(prefixo + 'cidade').value = "...";
                document.getElementById(prefixo + 'estado').value = "...";

                fetch('https://viacep.com.br/ws/' + cep + '/json/')
                    .then(response => response.json())
                    .then(data => {
                        if (!("erro" in data)) {
                            document.getElementById(prefixo + 'endereco').value = data.logradouro;
                            document.getElementById(prefixo + 'cidade').value = data.localidade;
                            document.getElementById(prefixo + 'estado').value = data.uf;
                        } else {
                            alert("CEP não encontrado.");
                            limparCampos(prefixo);
                        }
                    })
                    .catch(error => {
                        console.error('Erro:', error);
                        alert("Erro ao buscar CEP.");
                        limparCampos(prefixo);
                    });
            } else {
                alert("Formato de CEP inválido.");
                limparCampos(prefixo);
            }
        }
    }

    function limparCampos(prefixo) {
        document.getElementById(prefixo + 'endereco').value = "";
        document.getElementById(prefixo + 'cidade').value = "";
        document.getElementById(prefixo + 'estado').value = "";
    }

    document.addEventListener('DOMContentLoaded', function () {
        var cepInput = document.getElementById('cep');
        if (cepInput) {
            cepInput.addEventListener('blur', function () {
                buscarCep(this.value, '');
            });
        }

        var editCepInput = document.getElementById('edit_cep');
        if (editCepInput) {
            editCepInput.addEventListener('blur', function () {
                buscarCep(this.value, 'edit_');
            });
        }
    });
</script>

<?php $conn->close(); ?>