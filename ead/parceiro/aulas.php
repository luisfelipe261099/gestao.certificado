<?php
/**
 * Página de Gerenciamento de Aulas
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

// Obter filtro de curso
$curso_id = (int)($_GET['curso_id'] ?? 0);

// Obter cursos do parceiro
$cursos = $curso_model->obter_por_parceiro($parceiro_id);

// Se houver filtro de curso, obter aulas
$aulas = [];
if ($curso_id > 0) {
    // Verificar se o curso pertence ao parceiro
    $curso = $curso_model->obter_por_id($curso_id);
    if ($curso) {
        $stmt = $pdo->prepare('SELECT parceiro_id FROM cursos WHERE id = ?');
        $stmt->execute([$curso_id]);
        $curso_check = $stmt->fetch();
        
        if ($curso_check['parceiro_id'] == $parceiro_id) {
            $aulas = $aula_model->obter_por_curso($curso_id);
        }
    }
}

// Processar ações
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $acao = sanitizar($_POST['acao'] ?? '');
    
    if ($acao === 'deletar') {
        $aula_id = (int)$_POST['aula_id'];
        $resultado = $aula_model->deletar($aula_id);
        
        if ($resultado['sucesso']) {
            $_SESSION['mensagem'] = 'Aula deletada com sucesso!';
            $_SESSION['tipo_mensagem'] = 'success';
            header('Location: aulas.php?curso_id=' . $curso_id);
            exit;
        }
    }
}

// Obter mensagem de sessão
$mensagem = $_SESSION['mensagem'] ?? '';
$tipo_mensagem = $_SESSION['tipo_mensagem'] ?? '';
unset($_SESSION['mensagem'], $_SESSION['tipo_mensagem']);

$titulo_pagina = 'Minhas Aulas';
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
                <h1 style="font-size: 28px; font-weight: 700; color: #1D1D1F; margin: 0; line-height: 1.2;">Minhas Aulas</h1>
                <p style="color: #86868B; font-size: 14px; margin: 4px 0 0 0;">Crie e gerencie suas aulas e conteúdo</p>
            </div>
        </div>
        <?php if ($curso_id > 0): ?>
            <a href="criar-aula.php?curso_id=<?php echo $curso_id; ?>" class="button button-primary" style="text-decoration: none;">
                <span class="material-icons-outlined">add</span>
                <span>Nova Aula</span>
            </a>
        <?php endif; ?>
    </div>
</div>

<!-- Mensagem de Sucesso/Erro -->
<?php if ($mensagem): ?>
    <div class="alert alert-<?php echo $tipo_mensagem === 'success' ? 'success' : 'danger'; ?>">
        <span class="material-icons-outlined"><?php echo $tipo_mensagem === 'success' ? 'check_circle' : 'error'; ?></span>
        <span><?php echo $mensagem; ?></span>
    </div>
<?php endif; ?>

<!-- Cards de Estatísticas -->
<?php if ($curso_id > 0): ?>
<div class="stats-grid" style="grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); margin-bottom: 28px;">
    <div class="stat-card">
        <span class="material-icons-outlined">play_circle</span>
        <div class="stat-label">Total de Aulas</div>
        <div class="stat-value"><?php echo count($aulas); ?></div>
        <div class="stat-change">Criadas</div>
    </div>
</div>
<?php endif; ?>

<!-- Seleção de Curso -->
<div class="card" style="margin-bottom: 28px;">
    <h2>
        <span class="material-icons-outlined">menu_book</span>
        Selecione um Curso
    </h2>
    <form method="GET">
        <div class="form-group" style="margin-bottom: 0;">
            <label>Curso</label>
            <select name="curso_id" id="curso_id" class="form-control" onchange="this.form.submit()">
                <option value="">-- Escolha um curso --</option>
                <?php foreach ($cursos as $c): ?>
                    <option value="<?php echo $c['id']; ?>" <?php echo $curso_id === $c['id'] ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($c['nome']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
    </form>
</div>

<?php if ($curso_id > 0): ?>
    <!-- Aulas em Tabela -->
    <?php if (empty($aulas)): ?>
        <div class="card">
            <div class="empty-state">
                <span class="material-icons-outlined">play_circle</span>
                <h3>Nenhuma aula criada</h3>
                <p>Crie sua primeira aula clicando no botão "Nova Aula"</p>
            </div>
        </div>
    <?php else: ?>
    <div class="card">
        <h2>
            <span class="material-icons-outlined">play_circle</span>
            Aulas Criadas (<?php echo count($aulas); ?>)
        </h2>
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>Ordem</th>
                        <th>Título</th>
                        <th>Duração</th>
                        <th>Status</th>
                        <th>Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($aulas as $aula): ?>
                        <tr>
                            <td><strong>#<?php echo $aula['ordem']; ?></strong></td>
                            <td><?php echo htmlspecialchars($aula['titulo']); ?></td>
                            <td><?php echo $aula['duracao_minutos'] ? $aula['duracao_minutos'] . ' min' : '-'; ?></td>
                            <td>
                                <span class="badge badge-<?php echo $aula['ativa'] ? 'success' : 'warning'; ?>">
                                    <?php echo $aula['ativa'] ? 'Ativa' : 'Inativa'; ?>
                                </span>
                            </td>
                            <td>
                                <div style="display: flex; gap: 8px; flex-wrap: wrap;">
                                    <a href="upload-conteudo.php?aula_id=<?php echo $aula['id']; ?>"
                                       class="button button-primary" style="padding: 6px 12px; font-size: 13px; text-decoration: none;">
                                        <span class="material-icons-outlined" style="font-size: 16px;">video_library</span>
                                        <span>Conteúdo</span>
                                    </a>
                                    <a href="exercicios.php?aula_id=<?php echo $aula['id']; ?>"
                                       class="button button-success" style="padding: 6px 12px; font-size: 13px; text-decoration: none;">
                                        <span class="material-icons-outlined" style="font-size: 16px;">quiz</span>
                                        <span>Exercícios</span>
                                    </a>
                                    <a href="editar-aula.php?id=<?php echo $aula['id']; ?>"
                                       class="button button-secondary" style="padding: 6px 12px; font-size: 13px; text-decoration: none;">
                                        <span class="material-icons-outlined" style="font-size: 16px;">edit</span>
                                    </a>
                                    <form method="POST" style="display:inline;" onsubmit="return confirm('Tem certeza que deseja deletar esta aula?');">
                                        <input type="hidden" name="acao" value="deletar">
                                        <input type="hidden" name="aula_id" value="<?php echo $aula['id']; ?>">
                                        <button type="submit" class="button button-danger" style="padding: 6px 12px; font-size: 13px;">
                                            <span class="material-icons-outlined" style="font-size: 16px;">delete</span>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php endif; ?>
<?php else: ?>
    <div class="alert alert-info">
        <span class="material-icons-outlined">info</span>
        <span><strong>Selecione um curso</strong> para gerenciar suas aulas.</span>
    </div>
<?php endif; ?>

<?php
require_once '../includes/ead-layout-footer.php';
?>