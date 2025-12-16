<?php
/**
 * Página de Gerenciamento de Cursos
 * Sistema EAD Pro
 */

// Definir encoding UTF-8
header('Content-Type: text/html; charset=utf-8');

require_once '../config/database.php';
require_once '../app/models/Curso.php';

// Verificar autenticação
verificar_autenticacao('parceiro');

$parceiro_id = $_SESSION['ead_parceiro_id'];
$curso_model = new Curso($pdo);

// Obter filtro de busca
$filtro = sanitizar($_GET['filtro'] ?? '');

// Obter cursos
$cursos = $curso_model->obter_por_parceiro($parceiro_id, $filtro);

// Processar ações
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $acao = sanitizar($_POST['acao'] ?? '');

    if ($acao === 'deletar') {
        $curso_id = (int)$_POST['curso_id'];
        $resultado = $curso_model->deletar($curso_id);

        if ($resultado['sucesso']) {
            $_SESSION['mensagem'] = 'Curso deletado com sucesso!';
            $_SESSION['tipo_mensagem'] = 'success';
            header('Location: cursos.php');
            exit;
        }
    }
}

// Obter mensagem de sessão
$mensagem = $_SESSION['mensagem'] ?? '';
$tipo_mensagem = $_SESSION['tipo_mensagem'] ?? '';
unset($_SESSION['mensagem'], $_SESSION['tipo_mensagem']);

$titulo_pagina = 'Meus Cursos';
require_once '../includes/ead-layout-header.php';
?>

<!-- Cabeçalho Moderno -->
<div style="background: linear-gradient(135deg, rgba(110, 65, 193, 0.08) 0%, rgba(139, 95, 214, 0.04) 100%); border-radius: 16px; padding: 28px 32px; margin-bottom: 28px; box-shadow: 0 2px 8px rgba(0, 0, 0, 0.06);">
    <div style="display: flex; align-items: center; justify-content: space-between;">
        <div style="display: flex; align-items: center; gap: 16px;">
            <div style="width: 56px; height: 56px; background: linear-gradient(135deg, #6E41C1 0%, #8B5FD6 100%); border-radius: 14px; display: flex; align-items: center; justify-content: center; box-shadow: 0 4px 12px rgba(110, 65, 193, 0.3);">
                <span class="material-icons-outlined" style="font-size: 28px; color: white;">menu_book</span>
            </div>
            <div>
                <h1 style="font-size: 28px; font-weight: 700; color: #1D1D1F; margin: 0; line-height: 1.2;">Meus Cursos</h1>
                <p style="color: #86868B; font-size: 14px; margin: 4px 0 0 0;">Gerencie seus cursos e conteúdo educacional</p>
            </div>
        </div>
        <a href="criar-curso.php" class="button button-primary" style="text-decoration: none;">
            <span class="material-icons-outlined">add</span>
            <span>Novo Curso</span>
        </a>
    </div>
</div>

<!-- Mensagem de Sucesso/Erro -->
<?php if ($mensagem): ?>
    <div class="alert alert-<?php echo $tipo_mensagem === 'success' ? 'success' : 'danger'; ?>">
        <span class="material-icons-outlined"><?php echo $tipo_mensagem === 'success' ? 'check_circle' : 'error'; ?></span>
        <span><?php echo $mensagem; ?></span>
    </div>
<?php endif; ?>

<!-- Card de Estatísticas -->
<div class="stats-grid" style="grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); margin-bottom: 28px;">
    <div class="stat-card">
        <span class="material-icons-outlined">menu_book</span>
        <div class="stat-label">Total de Cursos</div>
        <div class="stat-value"><?php echo count($cursos); ?></div>
        <div class="stat-change">Criados</div>
    </div>
</div>

<!-- Filtro de Busca -->
<div class="card" style="margin-bottom: 28px;">
    <h2>
        <span class="material-icons-outlined">search</span>
        Buscar Cursos
    </h2>
    <form method="GET" style="display: flex; gap: 12px; align-items: flex-end;">
        <div class="form-group" style="flex: 1; margin-bottom: 0;">
            <label>Nome do Curso</label>
            <input type="text" name="filtro" class="form-control" placeholder="Digite o nome do curso..."
                   value="<?php echo htmlspecialchars($filtro); ?>">
        </div>
        <button type="submit" class="button button-primary" style="text-decoration: none;">
            <span class="material-icons-outlined">search</span>
            <span>Buscar</span>
        </button>
        <?php if ($filtro): ?>
            <a href="cursos.php" class="button button-secondary" style="text-decoration: none;">
                <span class="material-icons-outlined">close</span>
                <span>Limpar</span>
            </a>
        <?php endif; ?>
    </form>
</div>

<!-- Cursos em Grid -->
<?php if (empty($cursos)): ?>
    <div class="card">
        <div class="empty-state">
            <span class="material-icons-outlined">menu_book</span>
            <h3>Nenhum curso encontrado</h3>
            <p>Crie seu primeiro curso clicando no botão "Novo Curso"</p>
            <a href="criar-curso.php" class="button button-primary" style="text-decoration: none;">
                <span class="material-icons-outlined">add</span>
                Criar Primeiro Curso
            </a>
        </div>
    </div>
<?php else: ?>
    <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(400px, 1fr)); gap: 24px;">
        <?php foreach ($cursos as $curso):
            $stats = $curso_model->obter_estatisticas($curso['id']);
        ?>
            <div class="card" style="transition: all 0.3s ease; cursor: pointer;"
                 onmouseover="this.style.transform='translateY(-4px)'; this.style.boxShadow='0 8px 20px rgba(110, 65, 193, 0.15)';"
                 onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 2px 8px rgba(0,0,0,0.05)';">

                <!-- Header do Card -->
                <div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 16px;">
                    <div style="flex: 1;">
                        <h3 style="font-size: 18px; font-weight: 600; color: #1D1D1F; margin: 0 0 8px 0; display: flex; align-items: center; gap: 8px;">
                            <span class="material-icons-outlined" style="color: #6E41C1; font-size: 24px;">menu_book</span>
                            <?php echo htmlspecialchars($curso['nome']); ?>
                        </h3>
                        <p style="color: #86868B; font-size: 13px; margin: 0; line-height: 1.5;">
                            <?php echo htmlspecialchars($curso['descricao'] ?? 'Sem descrição'); ?>
                        </p>
                    </div>
                    <span class="badge badge-<?php echo $curso['ativo'] ? 'success' : 'warning'; ?>" style="flex-shrink: 0;">
                        <span class="material-icons-outlined" style="font-size: 14px;"><?php echo $curso['ativo'] ? 'check_circle' : 'pause_circle'; ?></span>
                        <?php echo $curso['ativo'] ? 'Ativo' : 'Inativo'; ?>
                    </span>
                </div>

                <!-- Estatísticas -->
                <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 16px; padding: 16px; background: rgba(110, 65, 193, 0.04); border-radius: 10px; margin-bottom: 16px;">
                    <div style="text-align: center;">
                        <span class="material-icons-outlined" style="font-size: 28px; color: #6E41C1; display: block; margin-bottom: 4px;">group</span>
                        <div style="font-size: 11px; color: #86868B; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 4px;">Alunos</div>
                        <div style="font-size: 20px; font-weight: 700; color: #1D1D1F;"><?php echo $stats['total_alunos'] ?? 0; ?></div>
                    </div>
                    <div style="text-align: center;">
                        <span class="material-icons-outlined" style="font-size: 28px; color: #6E41C1; display: block; margin-bottom: 4px;">play_circle</span>
                        <div style="font-size: 11px; color: #86868B; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 4px;">Aulas</div>
                        <div style="font-size: 20px; font-weight: 700; color: #1D1D1F;"><?php echo $stats['total_aulas'] ?? 0; ?></div>
                    </div>
                    <div style="text-align: center;">
                        <span class="material-icons-outlined" style="font-size: 28px; color: #6E41C1; display: block; margin-bottom: 4px;">schedule</span>
                        <div style="font-size: 11px; color: #86868B; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 4px;">Carga</div>
                        <div style="font-size: 20px; font-weight: 700; color: #1D1D1F;"><?php echo $curso['carga_horaria'] ?? 0; ?>h</div>
                    </div>
                </div>

                <!-- Data de Criação -->
                <div style="display: flex; align-items: center; gap: 6px; color: #86868B; font-size: 12px; margin-bottom: 16px; padding-bottom: 16px; border-bottom: 1px solid #E5E5E7;">
                    <span class="material-icons-outlined" style="font-size: 16px;">calendar_today</span>
                    <span>Criado em <?php echo date('d/m/Y', strtotime($curso['criado_em'] ?? date('Y-m-d'))); ?></span>
                </div>

                <!-- Ações -->
                <div style="display: flex; gap: 8px;">
                    <a href="curso-detalhes.php?id=<?php echo $curso['id']; ?>" class="button button-primary" style="flex: 1; text-decoration: none; padding: 8px 12px; font-size: 13px;">
                        <span class="material-icons-outlined" style="font-size: 16px;">visibility</span>
                        <span>Detalhes</span>
                    </a>
                    <a href="editar-curso.php?id=<?php echo $curso['id']; ?>" class="button button-secondary" style="text-decoration: none; padding: 8px 12px; font-size: 13px;">
                        <span class="material-icons-outlined" style="font-size: 16px;">edit</span>
                    </a>
                    <form method="POST" style="display:inline;" onsubmit="return confirm('Tem certeza que deseja deletar este curso?');">
                        <input type="hidden" name="acao" value="deletar">
                        <input type="hidden" name="curso_id" value="<?php echo $curso['id']; ?>">
                        <button type="submit" class="button button-danger" style="padding: 8px 12px; font-size: 13px;">
                            <span class="material-icons-outlined" style="font-size: 16px;">delete</span>
                        </button>
                    </form>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<?php
require_once '../includes/ead-layout-footer.php';
?>

