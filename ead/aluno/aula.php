<?php
/**
 * Aula do Aluno
 * Sistema EAD Pro
 */

require_once '../config/database.php';
require_once '../app/models/Aula.php';
require_once '../app/models/ConteudoAula.php';
require_once '../app/models/Curso.php';

iniciar_sessao();

$page_title = 'Aula';
include '../includes/header-aluno.php';

$aluno_id = $_SESSION['usuario_id'];
$curso_id = (int)($_GET['curso_id'] ?? 0);
$aula_id = (int)($_GET['aula_id'] ?? 0);

// Inicializar modelos
$aula_model = new Aula($pdo);
$conteudo_model = new ConteudoAula($pdo);
$curso_model = new Curso($pdo);

// Validar inscrição do aluno no curso
$stmt = $pdo->prepare('SELECT * FROM inscricoes WHERE aluno_id = ? AND curso_id = ?');
$stmt->execute([$aluno_id, $curso_id]);
$inscricao = $stmt->fetch();

if (!$inscricao) {
    die('Você não está inscrito neste curso');
}

// Obter curso
$curso = $curso_model->obter_por_id($curso_id);

// Obter todas as aulas do curso
$stmt = $pdo->prepare('SELECT * FROM aulas WHERE curso_id = ? ORDER BY ordem ASC');
$stmt->execute([$curso_id]);
$todas_aulas = $stmt->fetchAll();

// Se não especificou aula_id, pegar a primeira
if ($aula_id === 0 && !empty($todas_aulas)) {
    $aula_id = $todas_aulas[0]['id'];
}

// Obter aula atual
$aula = $aula_model->obter_por_id($aula_id);

if (!$aula || $aula['curso_id'] != $curso_id) {
    die('Aula não encontrada');
}

// Obter conteúdo da aula
$conteudos = $conteudo_model->obter_por_aula($aula_id);

// Agrupar conteúdo por tipo
$videos = array_filter($conteudos, fn($c) => $c['tipo'] === 'video');
$materiais = array_filter($conteudos, fn($c) => $c['tipo'] === 'material');
$textos = array_filter($conteudos, fn($c) => $c['tipo'] === 'texto');
$exercicios = array_filter($conteudos, fn($c) => $c['tipo'] === 'exercicio');

// Encontrar índice da aula atual
$aula_index = array_search($aula_id, array_column($todas_aulas, 'id'));
$proxima_aula = $aula_index < count($todas_aulas) - 1 ? $todas_aulas[$aula_index + 1] : null;
$aula_anterior = $aula_index > 0 ? $todas_aulas[$aula_index - 1] : null;
?>

<!-- Breadcrumb -->
<nav aria-label="breadcrumb">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="dashboard-aluno.php">Dashboard</a></li>
        <li class="breadcrumb-item"><a href="meus-cursos.php">Meus Cursos</a></li>
        <li class="breadcrumb-item active"><?php echo htmlspecialchars($curso['nome']); ?></li>
        <li class="breadcrumb-item active"><?php echo htmlspecialchars($aula['titulo']); ?></li>
    </ol>
</nav>

<div class="row">
    <!-- Conteúdo Principal -->
    <div class="col-lg-8">
        <!-- Vídeo -->
        <?php if (!empty($videos)): ?>
            <div class="card shadow mb-4">
                <div class="card-body p-0">
                    <?php $primeiro_video = reset($videos); ?>
                    <div class="embed-responsive embed-responsive-16by9">
                        <iframe class="embed-responsive-item" src="<?php echo htmlspecialchars($primeiro_video['url_arquivo']); ?>" allowfullscreen></iframe>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <!-- Informações da Aula -->
        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">
                    <i class="fas fa-book"></i> <?php echo htmlspecialchars($aula['titulo']); ?>
                </h6>
            </div>
            <div class="card-body">
                <p><?php echo nl2br(htmlspecialchars($aula['descricao'] ?? '')); ?></p>
                <hr>
                <div class="row">
                    <div class="col-md-6">
                        <small class="text-gray-500">Duração</small>
                        <p class="font-weight-bold"><?php echo $aula['duracao_minutos'] ?? 'Não definida'; ?> minutos</p>
                    </div>
                    <div class="col-md-6">
                        <small class="text-gray-500">Conteúdos</small>
                        <p class="font-weight-bold"><?php echo count($conteudos); ?> itens</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Abas de Conteúdo -->
        <ul class="nav nav-tabs mb-4" role="tablist">
            <li class="nav-item">
                <a class="nav-link active" id="materiais-tab" data-toggle="tab" href="#materiais" role="tab">
                    <i class="fas fa-file"></i> Materiais (<?php echo count($materiais); ?>)
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" id="textos-tab" data-toggle="tab" href="#textos" role="tab">
                    <i class="fas fa-file-alt"></i> Textos (<?php echo count($textos); ?>)
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" id="exercicios-tab" data-toggle="tab" href="#exercicios" role="tab">
                    <i class="fas fa-tasks"></i> Exercícios (<?php echo count($exercicios); ?>)
                </a>
            </li>
        </ul>

        <div class="tab-content">
            <!-- Materiais -->
            <div class="tab-pane fade show active" id="materiais" role="tabpanel">
                <div class="card shadow">
                    <div class="card-body">
                        <?php if (empty($materiais)): ?>
                            <p class="text-muted text-center py-4">Nenhum material disponível</p>
                        <?php else: ?>
                            <div class="list-group">
                                <?php foreach ($materiais as $material): ?>
                                    <a href="<?php echo htmlspecialchars($material['url_arquivo']); ?>" class="list-group-item list-group-item-action" download>
                                        <i class="fas fa-file-pdf text-danger"></i> <?php echo htmlspecialchars($material['titulo']); ?>
                                        <small class="text-muted d-block"><?php echo htmlspecialchars($material['descricao'] ?? ''); ?></small>
                                    </a>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Textos -->
            <div class="tab-pane fade" id="textos" role="tabpanel">
                <div class="card shadow">
                    <div class="card-body">
                        <?php if (empty($textos)): ?>
                            <p class="text-muted text-center py-4">Nenhum texto disponível</p>
                        <?php else: ?>
                            <?php foreach ($textos as $texto): ?>
                                <div class="mb-4">
                                    <h6 class="font-weight-bold mb-2"><?php echo htmlspecialchars($texto['titulo']); ?></h6>
                                    <p><?php echo nl2br(htmlspecialchars($texto['descricao'] ?? '')); ?></p>
                                    <hr>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Exercícios -->
            <div class="tab-pane fade" id="exercicios" role="tabpanel">
                <div class="card shadow">
                    <div class="card-body">
                        <?php if (empty($exercicios)): ?>
                            <p class="text-muted text-center py-4">Nenhum exercício disponível</p>
                        <?php else: ?>
                            <div class="list-group">
                                <?php foreach ($exercicios as $exercicio): ?>
                                    <div class="list-group-item">
                                        <h6 class="font-weight-bold mb-2">
                                            <i class="fas fa-tasks text-warning"></i> <?php echo htmlspecialchars($exercicio['titulo']); ?>
                                        </h6>
                                        <p class="text-muted"><?php echo htmlspecialchars($exercicio['descricao'] ?? ''); ?></p>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Navegação entre Aulas -->
        <div class="row mt-4">
            <div class="col-md-6">
                <?php if ($aula_anterior): ?>
                    <a href="aula.php?curso_id=<?php echo $curso_id; ?>&aula_id=<?php echo $aula_anterior['id']; ?>" class="btn btn-secondary btn-block">
                        <i class="fas fa-arrow-left"></i> Aula Anterior
                    </a>
                <?php endif; ?>
            </div>
            <div class="col-md-6">
                <?php if ($proxima_aula): ?>
                    <a href="aula.php?curso_id=<?php echo $curso_id; ?>&aula_id=<?php echo $proxima_aula['id']; ?>" class="btn btn-primary btn-block">
                        Próxima Aula <i class="fas fa-arrow-right"></i>
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Sidebar - Lista de Aulas -->
    <div class="col-lg-4">
        <div class="card shadow">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">
                    <i class="fas fa-list"></i> Aulas do Curso
                </h6>
            </div>
            <div class="card-body" style="max-height: 600px; overflow-y: auto;">
                <div class="list-group">
                    <?php foreach ($todas_aulas as $index => $a): ?>
                        <a href="aula.php?curso_id=<?php echo $curso_id; ?>&aula_id=<?php echo $a['id']; ?>" 
                           class="list-group-item list-group-item-action <?php echo $a['id'] == $aula_id ? 'active' : ''; ?>">
                            <div class="d-flex justify-content-between align-items-center">
                                <span><?php echo ($index + 1) . '. ' . htmlspecialchars($a['titulo']); ?></span>
                                <?php if ($a['id'] == $aula_id): ?>
                                    <i class="fas fa-check-circle text-success"></i>
                                <?php endif; ?>
                            </div>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer-aluno.php'; ?>

