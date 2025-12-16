<?php
/**
 * Histórico de Progresso do Aluno
 * Sistema EAD Pro
 */

require_once '../config/database.php';

iniciar_sessao();

$page_title = 'Histórico';
include '../includes/header-aluno.php';

$aluno_id = $_SESSION['usuario_id'];

// Obter histórico de progresso
$stmt = $pdo->prepare('
    SELECT p.*, c.nome as curso_nome, a.titulo as aula_titulo, i.frequencia as progresso_curso
    FROM progresso_aluno p
    INNER JOIN inscricoes_alunos i ON p.inscricao_id = i.id
    INNER JOIN cursos c ON i.curso_id = c.id
    LEFT JOIN aulas a ON p.aula_id = a.id
    WHERE i.aluno_id = ?
    ORDER BY p.data_conclusao DESC
    LIMIT 50
');
$stmt->execute([$aluno_id]);
$historico = $stmt->fetchAll();

// Obter estatísticas
$stmt = $pdo->prepare('
    SELECT
        COUNT(DISTINCT i.curso_id) as total_cursos,
        SUM(p.tempo_gasto_minutos) as tempo_total,
        AVG(i.frequencia) as progresso_medio
    FROM progresso_aluno p
    INNER JOIN inscricoes_alunos i ON p.inscricao_id = i.id
    WHERE i.aluno_id = ?
');
$stmt->execute([$aluno_id]);
$stats = $stmt->fetch();
?>

<!-- Cabeçalho da Página -->
<div class="d-sm-flex align-items-center justify-content-between mb-4">
    <h1 class="h3 mb-0 text-gray-800">Histórico de Progresso</h1>
</div>

<!-- Estatísticas -->
<div class="row mb-4">
    <div class="col-md-4 mb-4">
        <div class="card border-left-primary shadow h-100 py-2">
            <div class="card-body">
                <div class="text-primary font-weight-bold text-uppercase mb-1">Cursos Estudados</div>
                <div class="h3 mb-0 font-weight-bold text-gray-800"><?php echo $stats['total_cursos'] ?? 0; ?></div>
            </div>
        </div>
    </div>

    <div class="col-md-4 mb-4">
        <div class="card border-left-success shadow h-100 py-2">
            <div class="card-body">
                <div class="text-success font-weight-bold text-uppercase mb-1">Tempo Total</div>
                <div class="h3 mb-0 font-weight-bold text-gray-800"><?php echo round(($stats['tempo_total'] ?? 0) / 60); ?>h</div>
            </div>
        </div>
    </div>

    <div class="col-md-4 mb-4">
        <div class="card border-left-info shadow h-100 py-2">
            <div class="card-body">
                <div class="text-info font-weight-bold text-uppercase mb-1">Progresso Médio</div>
                <div class="h3 mb-0 font-weight-bold text-gray-800"><?php echo round($stats['progresso_medio'] ?? 0); ?>%</div>
            </div>
        </div>
    </div>
</div>

<!-- Histórico Detalhado -->
<div class="card shadow mb-4">
    <div class="card-header py-3">
        <h6 class="m-0 font-weight-bold text-primary">
            <i class="fas fa-history"></i> Atividades Recentes
        </h6>
    </div>
    <div class="card-body">
        <?php if (empty($historico)): ?>
            <p class="text-muted text-center py-4">Nenhuma atividade registrada</p>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-bordered" width="100%" cellspacing="0">
                    <thead>
                        <tr>
                            <th>Curso</th>
                            <th>Aula</th>
                            <th>Progresso</th>
                            <th>Tempo Estudado</th>
                            <th>Data</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($historico as $item): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($item['curso_nome']); ?></td>
                                <td><?php echo htmlspecialchars($item['aula_titulo'] ?? 'Geral'); ?></td>
                                <td>
                                    <div class="progress" style="height: 20px;">
                                        <div class="progress-bar bg-primary" role="progressbar" style="width: <?php echo $item['progresso_curso'] ?? 0; ?>%">
                                            <?php echo $item['progresso_curso'] ?? 0; ?>%
                                        </div>
                                    </div>
                                </td>
                                <td><?php echo round(($item['tempo_gasto_minutos'] ?? 0) / 60); ?> min</td>
                                <td><?php echo date('d/m/Y H:i', strtotime($item['data_conclusao'] ?? 'now')); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php include '../includes/footer-aluno.php'; ?>

