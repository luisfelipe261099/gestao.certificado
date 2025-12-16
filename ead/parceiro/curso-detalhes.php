<?php
/**
 * Página de Detalhes do Curso
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
$curso_id = (int)($_GET['id'] ?? 0);

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

// Obter estatísticas
$stats = $curso_model->obter_estatisticas($curso_id);

// Garantir que todos os valores existem
$stats = array_merge([
    'total_alunos' => 0,
    'total_aulas' => 0,
    'progresso_medio' => 0,
    'alunos_concluidos' => 0
], $stats ?? []);

// Obter aulas
$aulas = $aula_model->obter_por_curso($curso_id);

// Obter alunos inscritos
$stmt = $pdo->prepare('
    SELECT a.id, a.nome, a.email, ia.frequencia as progresso, ia.status, ia.data_inscricao
    FROM inscricoes_alunos ia
    JOIN alunos a ON ia.aluno_id = a.id
    WHERE ia.curso_id = ? AND ia.status IN ("inscrito", "em_progresso")
    ORDER BY ia.data_inscricao DESC
    LIMIT 10
');
$stmt->execute([$curso_id]);
$alunos_recentes = $stmt->fetchAll();

$titulo_pagina = 'Detalhes do Curso';
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
                <h1 style="font-size: 28px; font-weight: 700; color: #1D1D1F; margin: 0; line-height: 1.2;">Detalhes do Curso</h1>
                <p style="color: #86868B; font-size: 14px; margin: 4px 0 0 0;">Acompanhe as informações e estatísticas do curso</p>
            </div>
        </div>
        <a href="cursos.php" class="button button-secondary" style="text-decoration: none;">
            <span class="material-icons-outlined">arrow_back</span>
            <span>Voltar</span>
        </a>
    </div>
</div>

<!-- Cabeçalho do Curso -->
<div class="card" style="margin-bottom: 28px;">
    <div style="display: flex; justify-content: space-between; align-items: start;">
        <div style="flex: 1;">
            <h2 style="margin: 0 0 12px 0;">
                <span class="material-icons-outlined">menu_book</span>
                <?php echo htmlspecialchars($curso['nome']); ?>
            </h2>
            <p style="color: #86868B; margin: 0 0 16px 0;"><?php echo htmlspecialchars($curso['descricao']); ?></p>
            <span class="badge badge-<?php echo $curso['ativo'] ? 'success' : 'danger'; ?>">
                <?php echo $curso['ativo'] ? 'Ativo' : 'Inativo'; ?>
            </span>
        </div>
        <a href="editar-curso.php?id=<?php echo $curso['id']; ?>" class="button button-secondary" style="text-decoration: none;">
            <span class="material-icons-outlined">edit</span>
            <span>Editar</span>
        </a>
    </div>
</div>

<!-- Estatísticas -->
<div class="stats-grid" style="margin-bottom: 28px;">
    <div class="stat-card">
        <span class="material-icons-outlined">group</span>
        <div class="stat-label">Alunos Inscritos</div>
        <div class="stat-value"><?php echo $stats['total_alunos']; ?></div>
        <div class="stat-change">Total</div>
    </div>

    <div class="stat-card">
        <span class="material-icons-outlined">play_circle</span>
        <div class="stat-label">Total de Aulas</div>
        <div class="stat-value"><?php echo $stats['total_aulas']; ?></div>
        <div class="stat-change">Criadas</div>
    </div>

    <div class="stat-card">
        <span class="material-icons-outlined">trending_up</span>
        <div class="stat-label">Progresso Médio</div>
        <div class="stat-value"><?php echo number_format($stats['progresso_medio'], 0); ?>%</div>
        <div class="stat-change">Dos alunos</div>
    </div>

    <div class="stat-card">
        <span class="material-icons-outlined">check_circle</span>
        <div class="stat-label">Alunos Concluídos</div>
        <div class="stat-value"><?php echo $stats['alunos_concluidos']; ?></div>
        <div class="stat-change">Finalizaram</div>
    </div>
</div>

<!-- Informações do Curso -->
<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 24px; margin-bottom: 28px;">
    <div class="card">
        <h2>
            <span class="material-icons-outlined">info</span>
            Informações
        </h2>
        <div style="display: flex; flex-direction: column; gap: 16px;">
            <div>
                <div style="font-size: 12px; font-weight: 600; color: #86868B; text-transform: uppercase; margin-bottom: 4px;">Carga Horária</div>
                <div style="font-size: 16px; color: #1D1D1F; font-weight: 500;"><?php echo $curso['carga_horaria'] ?? 'Não definida'; ?> horas</div>
            </div>
            <div>
                <div style="font-size: 12px; font-weight: 600; color: #86868B; text-transform: uppercase; margin-bottom: 4px;">Criado em</div>
                <div style="font-size: 16px; color: #1D1D1F; font-weight: 500;"><?php echo date('d/m/Y H:i', strtotime($curso['criado_em'] ?? date('Y-m-d'))); ?></div>
            </div>
        </div>
    </div>

    <div class="card">
        <h2>
            <span class="material-icons-outlined">settings</span>
            Configurações
        </h2>
        <div>
            <div style="font-size: 12px; font-weight: 600; color: #86868B; text-transform: uppercase; margin-bottom: 8px;">Status</div>
            <span class="badge badge-<?php echo $curso['ativo'] ? 'success' : 'danger'; ?>">
                <?php echo $curso['ativo'] ? 'Ativo' : 'Inativo'; ?>
            </span>
        </div>
    </div>
</div>

<!-- Aulas -->
<div class="card" style="margin-bottom: 28px;">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
        <h2 style="margin: 0;">
            <span class="material-icons-outlined">play_circle</span>
            Aulas (<?php echo count($aulas); ?>)
        </h2>
        <a href="criar-aula.php?curso_id=<?php echo $curso['id']; ?>" class="button button-primary" style="text-decoration: none;">
            <span class="material-icons-outlined">add</span>
            <span>Nova Aula</span>
        </a>
    </div>

    <?php if (empty($aulas)): ?>
        <div class="empty-state">
            <span class="material-icons-outlined">video_library</span>
            <p>Nenhuma aula criada ainda</p>
            <a href="criar-aula.php?curso_id=<?php echo $curso['id']; ?>" class="button button-primary" style="text-decoration: none;">
                <span class="material-icons-outlined">add</span>
                <span>Criar Primeira Aula</span>
            </a>
        </div>
    <?php else: ?>
        <table class="table">
            <thead>
                <tr>
                    <th>Ordem</th>
                    <th>Título</th>
                    <th>Duração</th>
                    <th>Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($aulas as $aula): ?>
                    <tr>
                        <td>
                            <span class="badge badge-info" style="background: #6E41C1; color: white; padding: 6px 12px; border-radius: 6px; font-weight: 600;">
                                <?php echo $aula['ordem']; ?>
                            </span>
                        </td>
                        <td style="font-weight: 500;"><?php echo htmlspecialchars($aula['titulo']); ?></td>
                        <td>
                            <span style="display: flex; align-items: center; gap: 6px; color: #86868B;">
                                <span class="material-icons-outlined" style="font-size: 18px;">schedule</span>
                                <?php echo $aula['duracao_minutos'] ? $aula['duracao_minutos'] . ' min' : '-'; ?>
                            </span>
                        </td>
                        <td>
                            <a href="editar-aula.php?id=<?php echo $aula['id']; ?>" class="button button-secondary" style="text-decoration: none; padding: 8px 16px; font-size: 13px;">
                                <span class="material-icons-outlined" style="font-size: 18px;">edit</span>
                                <span>Editar</span>
                            </a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>

<!-- Alunos Recentes -->
<div class="card">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
        <h2 style="margin: 0;">
            <span class="material-icons-outlined">group</span>
            Alunos Recentes (<?php echo count($alunos_recentes); ?>)
        </h2>
        <a href="alunos.php?curso_id=<?php echo $curso['id']; ?>" class="button button-secondary" style="text-decoration: none;">
            <span class="material-icons-outlined">list</span>
            <span>Ver Todos</span>
        </a>
    </div>

    <?php if (empty($alunos_recentes)): ?>
        <div class="empty-state">
            <span class="material-icons-outlined">group</span>
            <p>Nenhum aluno inscrito</p>
        </div>
    <?php else: ?>
        <table class="table">
            <thead>
                <tr>
                    <th>Nome</th>
                    <th>Email</th>
                    <th>Progresso</th>
                    <th>Data de Inscrição</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($alunos_recentes as $aluno): ?>
                    <tr>
                        <td style="font-weight: 600;"><?php echo htmlspecialchars($aluno['nome']); ?></td>
                        <td style="color: #86868B;"><?php echo htmlspecialchars($aluno['email']); ?></td>
                        <td>
                            <div style="background: #E5E5E7; border-radius: 10px; height: 20px; overflow: hidden; min-width: 120px;">
                                <div style="background: linear-gradient(135deg, #6E41C1 0%, #8B5FD6 100%); height: 100%; width: <?php echo $aluno['progresso']; ?>%; display: flex; align-items: center; justify-content: center; color: white; font-size: 11px; font-weight: 600;">
                                    <?php echo $aluno['progresso']; ?>%
                                </div>
                            </div>
                        </td>
                        <td>
                            <span style="display: flex; align-items: center; gap: 6px; color: #86868B;">
                                <span class="material-icons-outlined" style="font-size: 18px;">calendar_today</span>
                                <?php echo date('d/m/Y', strtotime($aluno['data_inscricao'])); ?>
                            </span>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>

<?php
require_once '../includes/ead-layout-footer.php';
?>