<?php
/**
 * Página de Criar Novo Curso
 * Sistema EAD Pro
 */

require_once '../config/database.php';
require_once '../app/models/Curso.php';

// Verificar autenticação
verificar_autenticacao('parceiro');

$parceiro_id = $_SESSION['ead_parceiro_id'];
$curso_model = new Curso($pdo);
$erros = [];
$sucesso = false;

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

    // Se não houver erros, criar curso
    if (empty($erros)) {
        $resultado = $curso_model->criar([
            'parceiro_id' => $parceiro_id,
            'nome' => $nome,
            'descricao' => $descricao,
            'carga_horaria' => $carga_horaria
        ]);

        if ($resultado['sucesso']) {
            $sucesso = true;
            $_SESSION['mensagem'] = 'Curso criado com sucesso!';
            $_SESSION['tipo_mensagem'] = 'success';
            header('Location: cursos.php');
            exit;
        } else {
            $erros[] = $resultado['erro'];
        }
    }
}

$titulo_pagina = 'Criar Novo Curso';
require_once '../includes/ead-layout-header.php';
?>

<!-- Cabeçalho Moderno -->
<div style="background: linear-gradient(135deg, rgba(110, 65, 193, 0.08) 0%, rgba(139, 95, 214, 0.04) 100%); border-radius: 16px; padding: 28px 32px; margin-bottom: 28px; box-shadow: 0 2px 8px rgba(0, 0, 0, 0.06);">
    <div style="display: flex; align-items: center; justify-content: space-between;">
        <div style="display: flex; align-items: center; gap: 16px;">
            <div style="width: 56px; height: 56px; background: linear-gradient(135deg, #6E41C1 0%, #8B5FD6 100%); border-radius: 14px; display: flex; align-items: center; justify-content: center; box-shadow: 0 4px 12px rgba(110, 65, 193, 0.3);">
                <span class="material-icons-outlined" style="font-size: 28px; color: white;">add_circle</span>
            </div>
            <div>
                <h1 style="font-size: 28px; font-weight: 700; color: #1D1D1F; margin: 0; line-height: 1.2;">Criar Novo Curso</h1>
                <p style="color: #86868B; font-size: 14px; margin: 4px 0 0 0;">Preencha os dados abaixo para criar um novo curso</p>
            </div>
        </div>
        <a href="cursos.php" class="button button-secondary" style="text-decoration: none;">
            <span class="material-icons-outlined">arrow_back</span>
            <span>Voltar</span>
        </a>
    </div>
</div>

<!-- Erros -->
<?php if (!empty($erros)): ?>
    <div class="alert alert-danger">
        <span class="material-icons-outlined">error</span>
        <div>
            <strong>Erros encontrados:</strong>
            <ul style="margin: 8px 0 0 0; padding-left: 20px;">
                <?php foreach ($erros as $erro): ?>
                    <li><?php echo $erro; ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    </div>
<?php endif; ?>

<!-- Formulário -->
<div class="card">
    <h2>
        <span class="material-icons-outlined">menu_book</span>
        Dados do Curso
    </h2>

    <form method="POST">
        <div class="form-group">
            <label for="nome">Nome do Curso <span style="color: #FF3B30;">*</span></label>
            <input type="text" class="form-control" id="nome" name="nome"
                   placeholder="Ex: Introdução ao PHP" required
                   value="<?php echo $_POST['nome'] ?? ''; ?>">
            <small style="color: #86868B; font-size: 12px; display: block; margin-top: 4px;">
                <span class="material-icons-outlined" style="font-size: 14px; vertical-align: middle;">info</span>
                Mínimo 3 caracteres
            </small>
        </div>

        <div class="form-group">
            <label for="descricao">Descrição <span style="color: #FF3B30;">*</span></label>
            <textarea class="form-control" id="descricao" name="descricao" rows="4"
                      placeholder="Descreva o conteúdo do curso..." required><?php echo $_POST['descricao'] ?? ''; ?></textarea>
        </div>

        <div class="form-group">
            <label for="carga_horaria">Carga Horária (horas)</label>
            <input type="number" class="form-control" id="carga_horaria" name="carga_horaria"
                   placeholder="0" min="0"
                   value="<?php echo $_POST['carga_horaria'] ?? ''; ?>">
        </div>

        <div style="border-top: 1px solid #E5E5E7; margin: 24px 0 0 0; padding-top: 24px; display: flex; gap: 12px;">
            <button type="submit" class="button button-primary" style="text-decoration: none;">
                <span class="material-icons-outlined">save</span>
                <span>Criar Curso</span>
            </button>
            <a href="cursos.php" class="button button-secondary" style="text-decoration: none;">
                <span class="material-icons-outlined">close</span>
                <span>Cancelar</span>
            </a>
        </div>
    </form>
</div>

<?php
require_once '../includes/ead-layout-footer.php';
?>