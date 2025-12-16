<?php
/**
 * Página de Editar Aula
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
$aula_id = (int)($_GET['id'] ?? 0);
$erros = [];

// Obter aula
$aula = $aula_model->obter_por_id($aula_id);

if (!$aula) {
    $_SESSION['mensagem'] = 'Aula não encontrada!';
    $_SESSION['tipo_mensagem'] = 'danger';
    header('Location: cursos.php');
    exit;
}

// Obter curso
$curso = $curso_model->obter_por_id($aula['curso_id']);

// Verificar se o curso pertence ao parceiro
$stmt = $pdo->prepare('SELECT parceiro_id FROM cursos WHERE id = ?');
$stmt->execute([$aula['curso_id']]);
$curso_check = $stmt->fetch();

if ($curso_check['parceiro_id'] != $parceiro_id) {
    $_SESSION['mensagem'] = 'Acesso negado!';
    $_SESSION['tipo_mensagem'] = 'danger';
    header('Location: cursos.php');
    exit;
}

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
    
    // Se não houver erros, atualizar aula
    if (empty($erros)) {
        $resultado = $aula_model->atualizar($aula_id, [
            'titulo' => $titulo,
            'descricao' => $descricao,
            'duracao_minutos' => $duracao_minutos
        ]);
        
        if ($resultado['sucesso']) {
            $_SESSION['mensagem'] = 'Aula atualizada com sucesso!';
            $_SESSION['tipo_mensagem'] = 'success';
            header('Location: curso-detalhes.php?id=' . $aula['curso_id']);
            exit;
        } else {
            $erros[] = $resultado['erro'];
        }
    }
}

$titulo_pagina = 'Editar Aula';
require_once '../includes/ead-layout-header.php';
?>

<!-- Cabeçalho Moderno -->
<div style="background: linear-gradient(135deg, rgba(110, 65, 193, 0.08) 0%, rgba(139, 95, 214, 0.04) 100%); border-radius: 16px; padding: 28px 32px; margin-bottom: 28px; box-shadow: 0 2px 8px rgba(0, 0, 0, 0.06);">
    <div style="display: flex; align-items: center; justify-content: space-between;">
        <div style="display: flex; align-items: center; gap: 16px;">
            <div style="width: 56px; height: 56px; background: linear-gradient(135deg, #6E41C1 0%, #8B5FD6 100%); border-radius: 14px; display: flex; align-items: center; justify-content: center; box-shadow: 0 4px 12px rgba(110, 65, 193, 0.3);">
                <span class="material-icons-outlined" style="font-size: 28px; color: white;">edit</span>
            </div>
            <div>
                <h1 style="font-size: 28px; font-weight: 700; color: #1D1D1F; margin: 0; line-height: 1.2;">Editar Aula</h1>
                <p style="color: #86868B; font-size: 14px; margin: 4px 0 0 0;">Atualize os dados da aula</p>
            </div>
        </div>
        <a href="curso-detalhes.php?id=<?php echo $aula['curso_id']; ?>" class="button button-secondary" style="text-decoration: none;">
            <span class="material-icons-outlined">arrow_back</span>
            <span>Voltar</span>
        </a>
    </div>
</div>

<!-- Informações da Aula -->
<div class="card" style="margin-bottom: 28px;">
    <h2>
        <span class="material-icons-outlined">info</span>
        Informações
    </h2>
    <div style="display: flex; gap: 24px;">
        <div>
            <div style="font-size: 12px; font-weight: 600; color: #86868B; text-transform: uppercase; margin-bottom: 4px;">Curso</div>
            <div style="font-size: 16px; color: #1D1D1F; font-weight: 500;"><?php echo htmlspecialchars($curso['nome']); ?></div>
        </div>
        <div>
            <div style="font-size: 12px; font-weight: 600; color: #86868B; text-transform: uppercase; margin-bottom: 4px;">Aula</div>
            <span class="badge badge-info" style="background: #6E41C1; color: white; padding: 6px 12px; border-radius: 6px; font-weight: 600;">#<?php echo $aula['ordem']; ?></span>
        </div>
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
        <div class="form-group">
            <label for="titulo">Título da Aula <span style="color: #FF3B30;">*</span></label>
            <input type="text" class="form-control" id="titulo" name="titulo"
                   placeholder="Ex: Introdução ao PHP" required
                   value="<?php echo htmlspecialchars($aula['titulo']); ?>">
        </div>

        <div class="form-group">
            <label for="descricao">Descrição</label>
            <textarea class="form-control" id="descricao" name="descricao"
                      rows="4" placeholder="Descreva o conteúdo da aula..."><?php echo htmlspecialchars($aula['descricao'] ?? ''); ?></textarea>
        </div>

        <div class="form-group">
            <label for="duracao_minutos">Duração (minutos)</label>
            <input type="number" class="form-control" id="duracao_minutos" name="duracao_minutos"
                   placeholder="0" min="0"
                   value="<?php echo $aula['duracao_minutos'] ?? ''; ?>">
        </div>

        <div style="display: flex; gap: 12px; margin-top: 28px;">
            <button type="submit" class="button button-primary">
                <span class="material-icons-outlined">save</span>
                <span>Salvar Alterações</span>
            </button>
            <a href="curso-detalhes.php?id=<?php echo $aula['curso_id']; ?>" class="button button-secondary" style="text-decoration: none;">
                <span class="material-icons-outlined">close</span>
                <span>Cancelar</span>
            </a>
        </div>
    </form>
</div>

<?php
require_once '../includes/ead-layout-footer.php';
?>