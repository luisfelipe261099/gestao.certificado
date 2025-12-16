<?php
/**
 * Página de Editar Curso
 * Sistema EAD Pro
 */

require_once '../config/database.php';
require_once '../app/models/Curso.php';

// Verificar autenticação
verificar_autenticacao('parceiro');

$parceiro_id = $_SESSION['ead_parceiro_id'];
$curso_model = new Curso($pdo);
$curso_id = (int)($_GET['id'] ?? 0);
$erros = [];

// Obter curso
$curso = $curso_model->obter_por_id($curso_id);

if (!$curso) {
    $_SESSION['mensagem'] = 'Curso não encontrado!';
    $_SESSION['tipo_mensagem'] = 'danger';
    header('Location: cursos.php');
    exit;
}

// Verificar se o curso pertence ao parceiro
$stmt = $pdo->prepare('SELECT parceiro_id FROM cursos WHERE id = ?');
$stmt->execute([$curso_id]);
$curso_check = $stmt->fetch();

if ($curso_check['parceiro_id'] != $parceiro_id) {
    $_SESSION['mensagem'] = 'Acesso negado!';
    $_SESSION['tipo_mensagem'] = 'danger';
    header('Location: cursos.php');
    exit;
}

// Processar formulário
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome = sanitizar($_POST['nome'] ?? '');
    $descricao = sanitizar($_POST['descricao'] ?? '');
    $carga_horaria = (int)($_POST['carga_horaria'] ?? 0);

    // Validações
    if (empty($nome)) {
        $erros[] = 'Nome do curso é obrigatório';
    }
    if (strlen($nome) < 3) {
        $erros[] = 'Nome deve ter pelo menos 3 caracteres';
    }
    if (empty($descricao)) {
        $erros[] = 'Descrição é obrigatória';
    }

    // Se não houver erros, atualizar curso
    if (empty($erros)) {
        $resultado = $curso_model->atualizar($curso_id, [
            'nome' => $nome,
            'descricao' => $descricao,
            'carga_horaria' => $carga_horaria
        ]);

        if ($resultado['sucesso']) {
            $_SESSION['mensagem'] = 'Curso atualizado com sucesso!';
            $_SESSION['tipo_mensagem'] = 'success';
            header('Location: cursos.php');
            exit;
        } else {
            $erros[] = $resultado['erro'];
        }
    }
}

$titulo_pagina = 'Editar Curso';
require_once '../includes/ead-layout-header.php';
?>

<!-- Page Heading -->
<div class="d-sm-flex align-items-center justify-content-between mb-4">
    <div>
        <h1 class="h3 mb-0 text-gray-800"><i class="fas fa-edit" style="color: #9c166f;"></i> Editar Curso</h1>
        <p class="text-muted small mt-1">Atualize os dados do curso</p>
    </div>
    <a href="cursos.php" class="d-none d-sm-inline-block btn btn-lg shadow-sm" style="background-color: #9c166f; color: white; border: none;">
        <i class="fas fa-arrow-left"></i> Voltar
    </a>
</div>

<!-- Erros -->
<?php if (!empty($erros)): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <strong><i class="fas fa-exclamation-circle"></i> Erros encontrados:</strong>
        <ul class="mb-0 mt-2">
            <?php foreach ($erros as $erro): ?>
                <li><?php echo $erro; ?></li>
            <?php endforeach; ?>
        </ul>
        <button type="button" class="close" data-dismiss="alert">
            <span>&times;</span>
        </button>
    </div>
<?php endif; ?>

<!-- Formulário -->
<div class="card shadow mb-4">
    <div class="card-header py-3" style="background-color: #9c166f;">
        <h6 class="m-0 font-weight-bold text-white"><i class="fas fa-book"></i> Dados do Curso</h6>
    </div>
    <div class="card-body">
        <form method="POST">
            <div class="form-group">
                <label for="nome"><i class="fas fa-heading" style="color: #9c166f;"></i> <strong>Nome do Curso <span class="text-danger">*</span></strong></label>
                <input type="text" class="form-control" id="nome" name="nome"
                       placeholder="Ex: Introdução ao PHP" required
                       value="<?php echo htmlspecialchars($curso['nome']); ?>">
            </div>

            <div class="form-group">
                <label for="descricao"><i class="fas fa-align-left" style="color: #9c166f;"></i> <strong>Descrição <span class="text-danger">*</span></strong></label>
                <textarea class="form-control" id="descricao" name="descricao"
                          rows="4" placeholder="Descreva o conteúdo do curso..." required><?php echo htmlspecialchars($curso['descricao']); ?></textarea>
            </div>

            <div class="form-group">
                <label for="carga_horaria"><i class="fas fa-clock" style="color: #9c166f;"></i> <strong>Carga Horária (horas)</strong></label>
                <input type="number" class="form-control" id="carga_horaria" name="carga_horaria"
                       placeholder="0" min="0"
                       value="<?php echo $curso['carga_horaria'] ?? ''; ?>">
            </div>

            <div class="form-group mt-4">
                <button type="submit" class="btn btn-lg shadow-sm" style="background-color: #9c166f; color: white; border: none;">
                    <i class="fas fa-save"></i> Salvar Alterações
                </button>
                <a href="cursos.php" class="btn btn-lg shadow-sm" style="background-color: #6c757d; color: white; border: none;">
                    <i class="fas fa-times"></i> Cancelar
                </a>
            </div>
        </form>
    </div>
</div>

<?php
require_once '../includes/ead-layout-footer.php';
?>