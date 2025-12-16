<?php
/**
 * Perfil do Aluno
 * Sistema EAD Pro
 */

require_once '../config/database.php';
require_once '../app/models/Aluno.php';

iniciar_sessao();

$page_title = 'Perfil';
include '../includes/header-aluno.php';

$aluno_id = $_SESSION['usuario_id'];
$aluno_model = new Aluno($pdo);
$aluno = $aluno_model->obter_por_id($aluno_id);

$mensagem = '';
$erro = '';

// Processar atualização de perfil
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome = sanitizar($_POST['nome'] ?? '');
    $email = sanitizar($_POST['email'] ?? '');
    $telefone = sanitizar($_POST['telefone'] ?? '');
    $bio = sanitizar($_POST['bio'] ?? '');

    if (empty($nome) || empty($email)) {
        $erro = 'Nome e email são obrigatórios';
    } else {
        $stmt = $pdo->prepare('
            UPDATE alunos 
            SET nome = ?, email = ?, telefone = ?, bio = ?
            WHERE id = ?
        ');
        
        if ($stmt->execute([$nome, $email, $telefone, $bio, $aluno_id])) {
            $mensagem = 'Perfil atualizado com sucesso!';
            $_SESSION['nome'] = $nome;
            $_SESSION['email'] = $email;
            $aluno = $aluno_model->obter_por_id($aluno_id);
        } else {
            $erro = 'Erro ao atualizar perfil';
        }
    }
}
?>

<!-- Cabeçalho da Página -->
<div class="d-sm-flex align-items-center justify-content-between mb-4">
    <h1 class="h3 mb-0 text-gray-800">Meu Perfil</h1>
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

<div class="row">
    <!-- Foto e Informações Básicas -->
    <div class="col-lg-4 mb-4">
        <div class="card shadow h-100">
            <div class="card-body text-center">
                <img src="<?php echo htmlspecialchars($aluno['foto_url'] ?? 'https://via.placeholder.com/150'); ?>" 
                     class="img-profile rounded-circle mb-3" style="width: 150px; height: 150px; object-fit: cover;">
                <h6 class="font-weight-bold"><?php echo htmlspecialchars($aluno['nome']); ?></h6>
                <p class="text-gray-600 small"><?php echo htmlspecialchars($aluno['email']); ?></p>
                <hr>
                <p class="text-gray-600 small">
                    <i class="fas fa-calendar"></i> Membro desde <?php echo date('d/m/Y', strtotime($aluno['data_criacao'])); ?>
                </p>
            </div>
        </div>
    </div>

    <!-- Formulário de Edição -->
    <div class="col-lg-8 mb-4">
        <div class="card shadow">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">
                    <i class="fas fa-edit"></i> Editar Perfil
                </h6>
            </div>
            <div class="card-body">
                <form method="POST">
                    <div class="form-group">
                        <label for="nome">Nome Completo</label>
                        <input type="text" class="form-control" id="nome" name="nome" 
                               value="<?php echo htmlspecialchars($aluno['nome']); ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="email">Email</label>
                        <input type="email" class="form-control" id="email" name="email" 
                               value="<?php echo htmlspecialchars($aluno['email']); ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="telefone">Telefone</label>
                        <input type="tel" class="form-control" id="telefone" name="telefone" 
                               value="<?php echo htmlspecialchars($aluno['telefone'] ?? ''); ?>">
                    </div>

                    <div class="form-group">
                        <label for="bio">Bio</label>
                        <textarea class="form-control" id="bio" name="bio" rows="4"><?php echo htmlspecialchars($aluno['bio'] ?? ''); ?></textarea>
                    </div>

                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Salvar Alterações
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Segurança -->
<div class="card shadow mb-4">
    <div class="card-header py-3">
        <h6 class="m-0 font-weight-bold text-primary">
            <i class="fas fa-lock"></i> Segurança
        </h6>
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-6 mb-3">
                <h6 class="font-weight-bold">Alterar Senha</h6>
                <p class="text-gray-600 small">Atualize sua senha regularmente para manter sua conta segura</p>
                <a href="alterar-senha.php" class="btn btn-secondary btn-sm">
                    <i class="fas fa-key"></i> Alterar Senha
                </a>
            </div>
            <div class="col-md-6 mb-3">
                <h6 class="font-weight-bold">Autenticação de Dois Fatores</h6>
                <p class="text-gray-600 small">Adicione uma camada extra de segurança à sua conta</p>
                <button class="btn btn-secondary btn-sm" disabled>
                    <i class="fas fa-shield-alt"></i> Ativar 2FA (Em breve)
                </button>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer-aluno.php'; ?>

