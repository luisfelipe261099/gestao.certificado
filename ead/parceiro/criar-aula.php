<?php
/**
 * Página de Criar Nova Aula
 * Sistema EAD Pro
 */

require_once '../config/database.php';
require_once '../app/models/Curso.php';
require_once '../app/models/Aula.php';

// Verificar autenticação
verificar_autenticacao('parceiro');

$parceiro_id = $_SESSION['ead_parceiro_id'];
$curso_model = new Curso($pdo);
$aula_model = new Aula($pdo);
$curso_id = (int)($_GET['curso_id'] ?? $_POST['curso_id'] ?? 0);
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

// Obter próxima ordem
$stmt = $pdo->prepare('SELECT MAX(ordem) as max_ordem FROM aulas WHERE curso_id = ?');
$stmt->execute([$curso_id]);
$resultado = $stmt->fetch();
$proxima_ordem = ($resultado['max_ordem'] ?? 0) + 1;

// Processar formulário
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $titulo = sanitizar($_POST['titulo'] ?? '');
    $descricao = sanitizar($_POST['descricao'] ?? '');
    $duracao_minutos = (int)($_POST['duracao_minutos'] ?? 0);
    
    // Validações
    if (empty($titulo)) {
        $erros[] = 'Título da aula é obrigatório';
    }
    if (strlen($titulo) < 3) {
        $erros[] = 'Título deve ter pelo menos 3 caracteres';
    }
    
    // Se não houver erros, criar aula
    if (empty($erros)) {
        $resultado = $aula_model->criar([
            'curso_id' => $curso_id,
            'titulo' => $titulo,
            'descricao' => $descricao,
            'ordem' => $proxima_ordem,
            'duracao_minutos' => $duracao_minutos
        ]);
        
        if ($resultado['sucesso']) {
            $_SESSION['mensagem'] = 'Aula criada com sucesso!';
            $_SESSION['tipo_mensagem'] = 'success';
            header('Location: curso-detalhes.php?id=' . $curso_id);
            exit;
        } else {
            $erros[] = $resultado['erro'];
        }
    }
}

$titulo_pagina = 'Criar Aula';
require_once '../includes/ead-layout-header.php';
?>

<!-- Cabeçalho Moderno -->
<div style="background: linear-gradient(135deg, rgba(110, 65, 193, 0.08) 0%, rgba(139, 95, 214, 0.04) 100%); border-radius: 16px; padding: 28px 32px; margin-bottom: 28px; box-shadow: 0 2px 8px rgba(0, 0, 0, 0.06);">
    <div style="display: flex; align-items: center; justify-content: space-between;">
        <div style="display: flex; align-items: center; gap: 16px;">
            <div style="width: 56px; height: 56px; background: linear-gradient(135deg, #6E41C1 0%, #8B5FD6 100%); border-radius: 14px; display: flex; align-items: center; justify-content: center; box-shadow: 0 4px 12px rgba(110, 65, 193, 0.3);">
                <span class="material-icons-outlined" style="font-size: 28px; color: white;">play_circle</span>
            </div>
            <div>
                <h1 style="font-size: 28px; font-weight: 700; color: #1D1D1F; margin: 0; line-height: 1.2;">Criar Aula</h1>
                <p style="color: #86868B; font-size: 14px; margin: 4px 0 0 0;">Crie uma nova aula para o curso</p>
            </div>
        </div>
        <a href="curso-detalhes.php?id=<?php echo $curso_id; ?>" class="button button-secondary" style="text-decoration: none;">
            <span class="material-icons-outlined">arrow_back</span>
            <span>Voltar</span>
        </a>
    </div>
</div>

<!-- Curso Selecionado -->
<div class="card" style="margin-bottom: 28px;">
    <h2>
        <span class="material-icons-outlined">menu_book</span>
        Curso Selecionado
    </h2>
    <div>
        <div style="font-size: 16px; font-weight: 600; color: #1D1D1F; margin-bottom: 4px;"><?php echo htmlspecialchars($curso['nome']); ?></div>
        <p style="color: #86868B; font-size: 14px; margin: 0;">A aula será adicionada a este curso</p>
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
        <span class="material-icons-outlined">play_circle</span>
        Dados da Aula
    </h2>
    <form method="POST">
        <input type="hidden" name="curso_id" value="<?php echo $curso_id; ?>">

        <div class="form-group">
            <label for="titulo">Título da Aula <span style="color: #FF3B30;">*</span></label>
            <input type="text" class="form-control" id="titulo" name="titulo"
                   placeholder="Ex: Introdução ao PHP" required
                   value="<?php echo $_POST['titulo'] ?? ''; ?>">
            <small style="color: #86868B; font-size: 12px; margin-top: 4px; display: block;">Mínimo 3 caracteres</small>
        </div>

        <div class="form-group">
            <label for="descricao">Descrição</label>
            <textarea class="form-control" id="descricao" name="descricao"
                      rows="4" placeholder="Descreva o conteúdo da aula..."><?php echo $_POST['descricao'] ?? ''; ?></textarea>
        </div>

        <div class="form-group">
            <label for="duracao_minutos">Duração (minutos)</label>
            <input type="number" class="form-control" id="duracao_minutos" name="duracao_minutos"
                   placeholder="0" min="0"
                   value="<?php echo $_POST['duracao_minutos'] ?? ''; ?>">
        </div>

        <div class="alert alert-info" style="margin-bottom: 24px;">
            <span class="material-icons-outlined">info</span>
            <span><strong>Ordem:</strong> Esta aula será a aula <span style="background: #6E41C1; color: white; padding: 4px 10px; border-radius: 6px; font-weight: 600;">#<?php echo $proxima_ordem; ?></span> do curso</span>
        </div>

        <div style="display: flex; gap: 12px; margin-top: 28px;">
            <button type="submit" class="button button-primary">
                <span class="material-icons-outlined">save</span>
                <span>Criar Aula</span>
            </button>
            <a href="curso-detalhes.php?id=<?php echo $curso_id; ?>" class="button button-secondary" style="text-decoration: none;">
                <span class="material-icons-outlined">close</span>
                <span>Cancelar</span>
            </a>
        </div>
    </form>
</div>

<?php
require_once '../includes/ead-layout-footer.php';
?>

