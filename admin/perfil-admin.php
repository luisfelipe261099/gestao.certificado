<?php
/**
 * ============================================================================
 * PERFIL DO ADMINISTRADOR - SISTEMA DE CERTIFICADOS
 * ============================================================================
 *
 * Esta página permite que o admin veja e edite seus dados pessoais.
 * Aqui o admin pode:
 * - Ver seu nome, email, telefone
 * - Editar essas informações
 * - Alterar sua senha
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
$page_title = 'Meu Perfil - ' . APP_NAME;

// Pega os dados do usuário logado
$user = getCurrentUser();

// Conecta ao banco de dados
$conn = getDBConnection();

// ============================================================================
// BUSCAR DADOS DO USUÁRIO LOGADO
// ============================================================================
// Consulta preparada = forma segura de fazer consultas
// prepare() = prepara a consulta
// bind_param("i", $user['id']) = substitui o ? pelo ID do usuário
// "i" significa que é um inteiro (número)
$usuario = [];
$stmt = $conn->prepare("
    SELECT id, nome, email, telefone, data_criacao
    FROM usuarios
    WHERE id = ?
");

if ($stmt) {
    // Substitui o ? pelo ID do usuário logado
    $stmt->bind_param("i", $user['id']);

    // Executa a consulta
    $stmt->execute();

    // Pega o resultado
    $result = $stmt->get_result();

    // Se encontrou o usuário, pega os dados
    if ($row = $result->fetch_assoc()) {
        $usuario = $row;
    }

    // Fecha a consulta
    $stmt->close();
}
?>
<?php require_once '../app/views/admin-layout-header.php'; ?>

        <div class="container-fluid">
            <div class="d-sm-flex align-items-center justify-content-between mb-4">
                <h1 class="h3 mb-0 text-gray-800">Meu Perfil</h1>
            </div>

            <div class="row">
                <div class="col-lg-8">
                    <div class="card shadow mb-4">
                        <div class="card-header py-3">
                            <h6 class="m-0 font-weight-bold text-primary">Informações Pessoais</h6>
                        </div>
                        <div class="card-body">
                            <form method="POST" action="../app/actions/atualizar-perfil.php">
                                <div class="form-group">
                                    <label for="nome">Nome Completo</label>
                                    <input type="text" class="form-control" id="nome" name="nome" 
                                           value="<?php echo htmlspecialchars($usuario['nome'] ?? ''); ?>" required>
                                </div>
                                <div class="form-group">
                                    <label for="email">Email</label>
                                    <input type="email" class="form-control" id="email" name="email" 
                                           value="<?php echo htmlspecialchars($usuario['email'] ?? ''); ?>" required>
                                </div>
                                <div class="form-group">
                                    <label for="telefone">Telefone</label>
                                    <input type="tel" class="form-control" id="telefone" name="telefone" 
                                           value="<?php echo htmlspecialchars($usuario['telefone'] ?? ''); ?>">
                                </div>
                                <button type="submit" class="btn btn-primary">Salvar Alterações</button>
                            </form>
                        </div>
                    </div>
                </div>

                <div class="col-lg-4">
                    <div class="card shadow mb-4">
                        <div class="card-header py-3">
                            <h6 class="m-0 font-weight-bold text-primary">Informações da Conta</h6>
                        </div>
                        <div class="card-body">
                            <p><strong>Tipo de Usuário:</strong> Administrador</p>
                            <p><strong>Membro desde:</strong> <?php echo date('d/m/Y', strtotime($usuario['data_criacao'] ?? 'now')); ?></p>
                            <hr>
                            <a href="#" class="btn btn-warning btn-block mb-2" data-toggle="modal" data-target="#alterarSenha">
                                <i class="fas fa-key"></i> Alterar Senha
                            </a>
                        </div>
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

<!-- Modal Alterar Senha -->
<div class="modal fade" id="alterarSenha" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Alterar Senha</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form method="POST" action="../app/actions/alterar-senha.php">
                <div class="modal-body">
                    <div class="form-group">
                        <label for="senha_atual">Senha Atual</label>
                        <input type="password" class="form-control" id="senha_atual" name="senha_atual" required>
                    </div>
                    <div class="form-group">
                        <label for="nova_senha">Nova Senha</label>
                        <input type="password" class="form-control" id="nova_senha" name="nova_senha" required>
                    </div>
                    <div class="form-group">
                        <label for="confirmar_senha">Confirmar Senha</label>
                        <input type="password" class="form-control" id="confirmar_senha" name="confirmar_senha" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Alterar Senha</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php $conn->close(); ?>
<?php require_once '../app/views/admin-layout-footer.php'; ?>

