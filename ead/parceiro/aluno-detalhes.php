<?php
/**
 * Página de Detalhes do Aluno
 * Sistema EAD Pro
 */

require_once '../config/database.php';
require_once '../app/models/Aluno.php';
require_once '../app/models/Curso.php';

// Verificar autenticação
verificar_autenticacao('parceiro');

$parceiro_id = $_SESSION['ead_parceiro_id'];
$aluno_model = new Aluno($pdo);
$curso_model = new Curso($pdo);
$aluno_id = (int)($_GET['id'] ?? 0);
$curso_id = (int)($_GET['curso_id'] ?? 0);

// Obter aluno
$aluno = $aluno_model->obter_por_id($aluno_id);

if (!$aluno) {
    $_SESSION['mensagem'] = 'Aluno não encontrado!';
    $_SESSION['tipo_mensagem'] = 'danger';
    header('Location: alunos.php');
    exit;
}

// Obter progresso do aluno no curso
$progresso = $aluno_model->obter_progresso($aluno_id, $curso_id);

if (!$progresso) {
    $_SESSION['mensagem'] = 'Inscrição não encontrada!';
    $_SESSION['tipo_mensagem'] = 'danger';
    header('Location: alunos.php?curso_id=' . $curso_id);
    exit;
}

// Obter curso
$curso = $curso_model->obter_por_id($curso_id);

// Verificar se o curso pertence ao parceiro
$stmt = $pdo->prepare('SELECT parceiro_id FROM cursos WHERE id = ?');
$stmt->execute([$curso_id]);
$curso_check = $stmt->fetch();

if ($curso_check['parceiro_id'] != $parceiro_id) {
    $_SESSION['mensagem'] = 'Acesso negado!';
    $_SESSION['tipo_mensagem'] = 'danger';
    header('Location: alunos.php');
    exit;
}

// Obter estatísticas do aluno
$stats = $aluno_model->obter_estatisticas($aluno_id);

// Garantir que todos os valores existem
$stats = array_merge([
    'total_cursos' => 0,
    'cursos_concluidos' => 0,
    'progresso_medio' => 0
], $stats ?? []);

// Obter inscrição do aluno
$stmt = $pdo->prepare('
    SELECT id FROM inscricoes_alunos
    WHERE aluno_id = ? AND curso_id = ?
');
$stmt->execute([$aluno_id, $curso_id]);
$inscricao = $stmt->fetch();
$inscricao_id = $inscricao['id'] ?? 0;

// Obter aulas concluídas
$stmt = $pdo->prepare('
    SELECT COUNT(*) as total FROM progresso_aluno
    WHERE inscricao_id = ? AND data_conclusao IS NOT NULL
');
$stmt->execute([$inscricao_id]);
$aulas_concluidas = $stmt->fetch()['total'];

// Obter total de aulas
$stmt = $pdo->prepare('
    SELECT COUNT(*) as total FROM aulas
    WHERE curso_id = ?
');
$stmt->execute([$curso_id]);
$total_aulas = $stmt->fetch()['total'];

$titulo_pagina = 'Detalhes do Aluno';
require_once '../includes/ead-layout-header.php';
?>

<!-- Cabeçalho Moderno -->
<div style="background: linear-gradient(135deg, rgba(110, 65, 193, 0.08) 0%, rgba(139, 95, 214, 0.04) 100%); border-radius: 16px; padding: 28px 32px; margin-bottom: 28px; box-shadow: 0 2px 8px rgba(0, 0, 0, 0.06);">
    <div style="display: flex; align-items: center; justify-content: space-between;">
        <div style="display: flex; align-items: center; gap: 16px;">
            <div style="width: 56px; height: 56px; background: linear-gradient(135deg, #6E41C1 0%, #8B5FD6 100%); border-radius: 14px; display: flex; align-items: center; justify-content: center; box-shadow: 0 4px 12px rgba(110, 65, 193, 0.3);">
                <span class="material-icons-outlined" style="font-size: 28px; color: white;">person</span>
            </div>
            <div>
                <h1 style="font-size: 28px; font-weight: 700; color: #1D1D1F; margin: 0; line-height: 1.2;">Detalhes do Aluno</h1>
                <p style="color: #86868B; font-size: 14px; margin: 4px 0 0 0;">Acompanhe o progresso e informações do aluno</p>
            </div>
        </div>
        <a href="alunos.php?curso_id=<?php echo $curso_id; ?>" class="button button-secondary" style="text-decoration: none;">
        <i class="fas fa-arrow-left"></i> Voltar
    </a>
</div>

<!-- Cabeçalho do Aluno -->
<div class="card shadow mb-4">
    <div class="card-header py-3" style="background-color: #9c166f;">
        <h6 class="m-0 font-weight-bold text-white">
            <i class="fas fa-user-circle"></i> <?php echo htmlspecialchars($aluno['nome']); ?>
        </h6>
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-8">
                <p class="mb-2">
                    <i class="fas fa-envelope" style="color: #9c166f;"></i> <strong>Email:</strong> <?php echo htmlspecialchars($aluno['email']); ?>
                </p>
                <p class="mb-2">
                    <i class="fas fa-phone" style="color: #9c166f;"></i> <strong>Telefone:</strong> <?php echo htmlspecialchars($aluno['telefone'] ?? 'Não informado'); ?>
                </p>
                <p class="mb-0">
                    <i class="fas fa-book" style="color: #9c166f;"></i> <strong>Curso:</strong> <?php echo htmlspecialchars($curso['nome']); ?>
                </p>
            </div>
            <div class="col-md-4 text-right">
                <span class="badge badge-lg" style="font-size: 14px; padding: 10px 15px; background-color: <?php echo $progresso['status'] === 'concluido' ? '#28a745' : '#9c166f'; ?>; color: white;">
                    <i class="fas fa-<?php echo $progresso['status'] === 'concluido' ? 'check-circle' : 'hourglass-half'; ?>"></i>
                    <?php echo ucfirst($progresso['status']); ?>
                </span>
            </div>
        </div>
    </div>
</div>

<!-- Informações do Curso -->
<div class="card shadow mb-4">
    <div class="card-header py-3" style="background-color: #9c166f;">
        <h6 class="m-0 font-weight-bold text-white"><i class="fas fa-book"></i> Informações do Curso</h6>
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-6">
                <p class="mb-2"><strong>Data de Inscrição:</strong></p>
                <p class="text-muted"><?php echo date('d/m/Y H:i', strtotime($progresso['criado_em'] ?? date('Y-m-d'))); ?></p>
            </div>
            <div class="col-md-6">
                <p class="mb-2"><strong>Status:</strong></p>
                <p><span class="badge badge-<?php echo $progresso['status'] === 'concluido' ? 'success' : ($progresso['status'] === 'ativo' ? 'info' : 'warning'); ?>">
                    <i class="fas fa-<?php echo $progresso['status'] === 'concluido' ? 'check-circle' : 'hourglass-half'; ?>"></i>
                    <?php echo ucfirst($progresso['status']); ?>
                </span></p>
            </div>
        </div>
    </div>
</div>

<!-- Estatísticas -->
<div class="row mb-4">
    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card shadow h-100 py-2" style="border-left: 4px solid #9c166f;">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-uppercase mb-1" style="color: #9c166f;">Progresso Geral</div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $progresso['progresso']; ?>%</div>
                    </div>
                    <div class="col-auto">
                        <i class="fas fa-chart-pie fa-2x" style="color: #e8d4e8;"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card shadow h-100 py-2" style="border-left: 4px solid #28a745;">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-uppercase mb-1" style="color: #28a745;">Aulas Concluídas</div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $aulas_concluidas; ?>/<?php echo $total_aulas; ?></div>
                    </div>
                    <div class="col-auto">
                        <i class="fas fa-check-circle fa-2x" style="color: #d4edda;"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card shadow h-100 py-2" style="border-left: 4px solid #ffc107;">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-uppercase mb-1" style="color: #ffc107;">Nota Final</div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $progresso['nota_final'] ? number_format($progresso['nota_final'], 1, ',', '.') : '-'; ?></div>
                    </div>
                    <div class="col-auto">
                        <i class="fas fa-star fa-2x" style="color: #fff3cd;"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card shadow h-100 py-2" style="border-left: 4px solid #17a2b8;">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-uppercase mb-1" style="color: #17a2b8;">Total de Cursos</div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $stats['total_cursos']; ?></div>
                    </div>
                    <div class="col-auto">
                        <i class="fas fa-book fa-2x" style="color: #d1ecf1;"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Progresso Detalhado -->
<div class="card shadow mb-4">
    <div class="card-header py-3" style="background-color: #9c166f;">
        <h6 class="m-0 font-weight-bold text-white"><i class="fas fa-chart-bar"></i> Progresso Detalhado</h6>
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-6">
                <h6 class="font-weight-bold mb-3"><i class="fas fa-chart-pie"></i> Progresso Geral</h6>
                <div class="progress" style="height: 30px;">
                    <div class="progress-bar bg-success" role="progressbar"
                         style="width: <?php echo $progresso['progresso']; ?>%"
                         aria-valuenow="<?php echo $progresso['progresso']; ?>"
                         aria-valuemin="0" aria-valuemax="100">
                        <?php echo $progresso['progresso']; ?>%
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <h6 class="font-weight-bold mb-3"><i class="fas fa-check-circle"></i> Aulas Concluídas</h6>
                <div class="progress" style="height: 30px;">
                    <div class="progress-bar bg-info" role="progressbar"
                         style="width: <?php echo $total_aulas > 0 ? ($aulas_concluidas / $total_aulas * 100) : 0; ?>%"
                         aria-valuenow="<?php echo $total_aulas > 0 ? ($aulas_concluidas / $total_aulas * 100) : 0; ?>"
                         aria-valuemin="0" aria-valuemax="100">
                                        <?php echo $aulas_concluidas; ?>/<?php echo $total_aulas; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Informações Adicionais -->
                <div class="row mt-4">
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header">
                                <h5><i class="fas fa-info-circle"></i> Informações Pessoais</h5>
                            </div>
                            <div class="card-body">
                                <p><strong>CPF:</strong> <?php echo htmlspecialchars($aluno['cpf'] ?? 'Não informado'); ?></p>
                                <p><strong>Data de Nascimento:</strong> <?php echo $aluno['data_nascimento'] ? date('d/m/Y', strtotime($aluno['data_nascimento'])) : 'Não informado'; ?></p>
                                <p><strong>Gênero:</strong> <?php echo htmlspecialchars($aluno['genero'] ?? 'Não informado'); ?></p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header">
                                <h5><i class="fas fa-map-marker-alt"></i> Endereço</h5>
                            </div>
                            <div class="card-body">
                                <p><strong>Endereço:</strong> <?php echo htmlspecialchars($aluno['endereco'] ?? 'Não informado'); ?></p>
                                <p><strong>Cidade:</strong> <?php echo htmlspecialchars($aluno['cidade'] ?? 'Não informado'); ?></p>
                                <p><strong>Estado:</strong> <?php echo htmlspecialchars($aluno['estado'] ?? 'Não informado'); ?></p>
                                <p><strong>CEP:</strong> <?php echo htmlspecialchars($aluno['cep'] ?? 'Não informado'); ?></p>
                            </div>
                        </div>
                    </div>
                </div>

<?php
require_once '../includes/ead-layout-footer.php';
?>