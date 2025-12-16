<?php
/**
 * Alterar Senha do Aluno
 * Sistema EAD Pro
 */

require_once '../config/database.php';
require_once '../app/models/Aluno.php';

iniciar_sessao();

$page_title = 'Alterar Senha';
include '../includes/header-aluno.php';

$aluno_id = $_SESSION['usuario_id'];
$aluno_model = new Aluno($pdo);
$aluno = $aluno_model->obter_por_id($aluno_id);

$mensagem = '';
$erro = '';

// Processar alteração de senha
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $senha_atual = $_POST['senha_atual'] ?? '';
    $senha_nova = $_POST['senha_nova'] ?? '';
    $senha_confirma = $_POST['senha_confirma'] ?? '';

    if (empty($senha_atual) || empty($senha_nova) || empty($senha_confirma)) {
        $erro = 'Todos os campos são obrigatórios';
    } elseif ($senha_nova !== $senha_confirma) {
        $erro = 'As senhas não conferem';
    } elseif (strlen($senha_nova) < 6) {
        $erro = 'A senha deve ter no mínimo 6 caracteres';
    } else {
        // Verificar senha atual
        if (password_verify($senha_atual, $aluno['senha'])) {
            // Atualizar senha
            $nova_senha_hash = password_hash($senha_nova, PASSWORD_BCRYPT, ['cost' => 12]);
            $stmt = $pdo->prepare('UPDATE alunos SET senha = ? WHERE id = ?');
            
            if ($stmt->execute([$nova_senha_hash, $aluno_id])) {
                $mensagem = 'Senha alterada com sucesso!';
            } else {
                $erro = 'Erro ao alterar senha';
            }
        } else {
            $erro = 'Senha atual incorreta';
        }
    }
}
?>

<!-- Cabeçalho da Página -->
<div class="d-sm-flex align-items-center justify-content-between mb-4">
    <h1 class="h3 mb-0 text-gray-800">Alterar Senha</h1>
</div>

<?php if (!empty($mensagem)): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($mensagem); ?>
        <button type="button" class="close" data-dismiss="alert">
            <span>&times;</span>
        </button>
    </div>
<?php endif; ?>

<?php if (!empty($erro)): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($erro); ?>
        <button type="button" class="close" data-dismiss="alert">
            <span>&times;</span>
        </button>
    </div>
<?php endif; ?>

<div class="row justify-content-center">
    <div class="col-lg-6">
        <div class="card shadow">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">
                    <i class="fas fa-lock"></i> Alterar Senha
                </h6>
            </div>
            <div class="card-body">
                <form method="POST">
                    <div class="form-group">
                        <label for="senha_atual">Senha Atual</label>
                        <input type="password" class="form-control" id="senha_atual" name="senha_atual" required>
                        <small class="form-text text-muted">Digite sua senha atual para confirmar</small>
                    </div>

                    <hr>

                    <div class="form-group">
                        <label for="senha_nova">Nova Senha</label>
                        <input type="password" class="form-control" id="senha_nova" name="senha_nova" required>
                        <small class="form-text text-muted">Mínimo 6 caracteres</small>
                    </div>

                    <div class="form-group">
                        <label for="senha_confirma">Confirmar Nova Senha</label>
                        <input type="password" class="form-control" id="senha_confirma" name="senha_confirma" required>
                    </div>

                    <div class="form-group">
                        <button type="submit" class="btn btn-primary btn-block">
                            <i class="fas fa-save"></i> Alterar Senha
                        </button>
                        <a href="perfil-aluno.php" class="btn btn-secondary btn-block mt-2">
                            <i class="fas fa-arrow-left"></i> Voltar
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer-aluno.php'; ?>

