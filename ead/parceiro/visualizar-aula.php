<?php
/**
 * Visualizar Aula - Sistema EAD Pro
 * Página para visualizar como a aula ficará para os alunos
 */

require_once '../config/database.php';

iniciar_sessao();

// Verificar autenticação
if (!isset($_SESSION['ead_parceiro_id']) || $_SESSION['ead_autenticado'] !== true) {
    // Se não estiver logado, redirecionar para login
    header('Location: login.php');
    exit;
}

// Incluir modelos
require_once '../app/models/Aula.php';
require_once '../app/models/ConteudoAula.php';
require_once '../app/models/Curso.php';

$parceiro_id = $_SESSION['ead_parceiro_id'];
$aula_id = (int)($_GET['aula_id'] ?? 0);

// Validar aula_id
if ($aula_id <= 0) {
    header('Location: aulas.php');
    exit;
}

// Inicializar modelos
$aula_model = new Aula($pdo);
$conteudo_model = new ConteudoAula($pdo);
$curso_model = new Curso($pdo);

// Obter aula
$aula = $aula_model->obter_por_id($aula_id);

if (!$aula) {
    die('Aula não encontrada');
}

// Verificar se a aula pertence a um curso do parceiro
$stmt = $pdo->prepare('SELECT parceiro_id FROM cursos WHERE id = ?');
$stmt->execute([$aula['curso_id']]);
$curso_check = $stmt->fetch();

if (!$curso_check || $curso_check['parceiro_id'] != $parceiro_id) {
    die('Você não tem permissão para visualizar esta aula');
}

// Obter curso
$curso = $curso_model->obter_por_id($aula['curso_id']);

// Obter conteúdo da aula
$conteudos = $conteudo_model->obter_por_aula($aula_id);

// Agrupar conteúdo por tipo
$videos = array_filter($conteudos, fn($c) => $c['tipo'] === 'video');
$materiais = array_filter($conteudos, fn($c) => $c['tipo'] === 'material');
$textos = array_filter($conteudos, fn($c) => $c['tipo'] === 'texto');
$exercicios = array_filter($conteudos, fn($c) => $c['tipo'] === 'exercicio');

$titulo_pagina = 'Visualizar Aula';
require_once '../includes/ead-layout-header.php';
?>

<!-- Cabeçalho Moderno -->
<div style="background: linear-gradient(135deg, rgba(110, 65, 193, 0.08) 0%, rgba(139, 95, 214, 0.04) 100%); border-radius: 16px; padding: 28px 32px; margin-bottom: 28px; box-shadow: 0 2px 8px rgba(0, 0, 0, 0.06);">
    <div style="display: flex; align-items: center; justify-content: space-between;">
        <div style="display: flex; align-items: center; gap: 16px;">
            <div style="width: 56px; height: 56px; background: linear-gradient(135deg, #6E41C1 0%, #8B5FD6 100%); border-radius: 14px; display: flex; align-items: center; justify-content: center; box-shadow: 0 4px 12px rgba(110, 65, 193, 0.3);">
                <span class="material-icons-outlined" style="font-size: 28px; color: white;">visibility</span>
            </div>
            <div>
                <h1 style="font-size: 28px; font-weight: 700; color: #1D1D1F; margin: 0; line-height: 1.2;">Pré-visualização da Aula</h1>
                <p style="color: #86868B; font-size: 14px; margin: 4px 0 0 0;">Veja como a aula aparecerá para os alunos</p>
            </div>
        </div>
        <a href="upload-conteudo.php?aula_id=<?php echo $aula_id; ?>" class="button button-secondary" style="text-decoration: none;">
            <span class="material-icons-outlined">arrow_back</span>
            <span>Voltar</span>
        </a>
    </div>
</div>
<!-- Banner da Aula -->
<div style="background: linear-gradient(135deg, #6E41C1 0%, #8B5FD6 100%); border-radius: 16px; padding: 32px; margin-bottom: 28px; color: white; box-shadow: 0 8px 24px rgba(110, 65, 193, 0.25);">
    <div style="display: flex; align-items: start; justify-content: space-between; gap: 24px;">
        <div style="flex: 1;">
            <div style="display: inline-block; background: rgba(255, 255, 255, 0.2); padding: 6px 14px; border-radius: 20px; font-size: 13px; font-weight: 600; margin-bottom: 12px;">
                📚 <?php echo htmlspecialchars($curso['nome']); ?>
            </div>
            <h2 style="font-size: 32px; font-weight: 700; margin: 0 0 12px 0; color: white; line-height: 1.2;">
                <?php echo htmlspecialchars($aula['titulo']); ?>
            </h2>
            <?php if ($aula['descricao']): ?>
                <p style="font-size: 16px; opacity: 0.95; margin: 0; line-height: 1.6;">
                    <?php echo htmlspecialchars($aula['descricao']); ?>
                </p>
            <?php endif; ?>
        </div>
        <?php if ($aula['duracao_minutos']): ?>
            <div style="background: rgba(255, 255, 255, 0.15); backdrop-filter: blur(10px); border-radius: 12px; padding: 20px; text-align: center; min-width: 120px;">
                <span class="material-icons-outlined" style="font-size: 32px; color: white; display: block; margin-bottom: 8px;">schedule</span>
                <div style="font-size: 24px; font-weight: 700; color: white;"><?php echo $aula['duracao_minutos']; ?></div>
                <div style="font-size: 13px; opacity: 0.9;">minutos</div>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Estatísticas -->
<div class="stats-grid" style="margin-bottom: 28px;">
    <div class="stat-card">
        <span class="material-icons-outlined">play_circle</span>
        <div class="stat-label">Vídeos</div>
        <div class="stat-value"><?php echo count($videos); ?></div>
    </div>

    <div class="stat-card">
        <span class="material-icons-outlined">description</span>
        <div class="stat-label">Materiais</div>
        <div class="stat-value"><?php echo count($materiais); ?></div>
    </div>

    <div class="stat-card">
        <span class="material-icons-outlined">article</span>
        <div class="stat-label">Textos</div>
        <div class="stat-value"><?php echo count($textos); ?></div>
    </div>

    <div class="stat-card">
        <span class="material-icons-outlined">quiz</span>
        <div class="stat-label">Exercícios</div>
        <div class="stat-value"><?php echo count($exercicios); ?></div>
    </div>
</div>

<!-- Navegação por Tipo de Conteúdo -->
<div style="background: white; border-radius: 12px; padding: 8px; margin-bottom: 28px; box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05); display: flex; gap: 8px; flex-wrap: wrap;">
    <button class="tab-button active" onclick="mostrarTab('videos')" id="tab-videos">
        <span class="material-icons-outlined">play_circle</span>
        <span>Vídeos</span>
        <span class="tab-badge"><?php echo count($videos); ?></span>
    </button>
    <button class="tab-button" onclick="mostrarTab('materiais')" id="tab-materiais">
        <span class="material-icons-outlined">description</span>
        <span>Materiais</span>
        <span class="tab-badge"><?php echo count($materiais); ?></span>
    </button>
    <button class="tab-button" onclick="mostrarTab('textos')" id="tab-textos">
        <span class="material-icons-outlined">article</span>
        <span>Textos</span>
        <span class="tab-badge"><?php echo count($textos); ?></span>
    </button>
    <button class="tab-button" onclick="mostrarTab('exercicios')" id="tab-exercicios">
        <span class="material-icons-outlined">quiz</span>
        <span>Exercícios</span>
        <span class="tab-badge"><?php echo count($exercicios); ?></span>
    </button>
</div>

<!-- Conteúdo das Abas -->
<div class="tab-content-wrapper">
    <!-- Vídeos -->
    <div class="tab-content-item active" id="content-videos">
        <?php if (empty($videos)): ?>
            <div class="empty-state">
                <span class="material-icons-outlined" style="font-size: 64px; color: #E5E5E7;">play_circle</span>
                <p style="font-size: 16px; font-weight: 600; color: #1D1D1F; margin: 16px 0 8px 0;">Nenhum vídeo adicionado</p>
                <p style="font-size: 14px; color: #86868B; margin: 0;">Adicione vídeos para enriquecer sua aula</p>
            </div>
        <?php else: ?>
            <div style="display: flex; flex-direction: column; gap: 24px;">
                <?php foreach ($videos as $index => $video):
                    // Converter URL do YouTube para formato embed
                    $url = $video['url_arquivo'];

                    // Se for URL do YouTube (watch?v=), converter para embed
                    if (strpos($url, 'youtube.com/watch?v=') !== false) {
                        preg_match('/[?&]v=([^&]+)/', $url, $matches);
                        if (isset($matches[1])) {
                            $url = 'https://www.youtube.com/embed/' . $matches[1];
                        }
                    }
                    // Se for URL curta do YouTube (youtu.be/), converter para embed
                    elseif (strpos($url, 'youtu.be/') !== false) {
                        preg_match('/youtu\.be\/([^?]+)/', $url, $matches);
                        if (isset($matches[1])) {
                            $url = 'https://www.youtube.com/embed/' . $matches[1];
                        }
                    }
                    // Se for Vimeo, converter para embed
                    elseif (strpos($url, 'vimeo.com/') !== false && strpos($url, '/video/') === false) {
                        preg_match('/vimeo\.com\/(\d+)/', $url, $matches);
                        if (isset($matches[1])) {
                            $url = 'https://player.vimeo.com/video/' . $matches[1];
                        }
                    }
                ?>
                    <div class="card">
                        <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 16px;">
                            <div style="width: 40px; height: 40px; background: linear-gradient(135deg, #6E41C1 0%, #8B5FD6 100%); border-radius: 10px; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                                <span style="color: white; font-weight: 700; font-size: 16px;"><?php echo $index + 1; ?></span>
                            </div>
                            <div>
                                <h3 style="margin: 0; font-size: 18px; font-weight: 600; color: #1D1D1F;">
                                    <?php echo htmlspecialchars($video['titulo']); ?>
                                </h3>
                                <?php if ($video['descricao']): ?>
                                    <p style="margin: 4px 0 0 0; font-size: 14px; color: #86868B;">
                                        <?php echo htmlspecialchars($video['descricao']); ?>
                                    </p>
                                <?php endif; ?>
                            </div>
                        </div>
                        <!-- Player de vídeo com tamanho reduzido -->
                        <div style="max-width: 800px; margin: 0 auto;">
                            <div style="position: relative; padding-bottom: 56.25%; height: 0; overflow: hidden; border-radius: 12px; background: #000; box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);">
                                <iframe
                                    style="position: absolute; top: 0; left: 0; width: 100%; height: 100%; border: 0;"
                                    src="<?php echo htmlspecialchars($url); ?>"
                                    allowfullscreen
                                    allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture">
                                </iframe>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- Materiais -->
    <div class="tab-content-item" id="content-materiais">
        <?php if (empty($materiais)): ?>
            <div class="empty-state">
                <span class="material-icons-outlined" style="font-size: 64px; color: #E5E5E7;">description</span>
                <p style="font-size: 16px; font-weight: 600; color: #1D1D1F; margin: 16px 0 8px 0;">Nenhum material adicionado</p>
                <p style="font-size: 14px; color: #86868B; margin: 0;">Adicione PDFs, documentos e outros materiais de apoio</p>
            </div>
        <?php else: ?>
            <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 16px;">
                <?php foreach ($materiais as $material): ?>
                    <a href="<?php echo htmlspecialchars($material['url_arquivo']); ?>"
                       download
                       class="material-card"
                       style="text-decoration: none; display: block;">
                        <div style="background: white; border: 2px solid #E5E5E7; border-radius: 12px; padding: 20px; transition: all 0.2s ease; cursor: pointer;">
                            <div style="display: flex; align-items: start; gap: 16px;">
                                <div style="width: 56px; height: 56px; background: rgba(110, 65, 193, 0.1); border-radius: 12px; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                                    <span class="material-icons-outlined" style="font-size: 28px; color: #6E41C1;">description</span>
                                </div>
                                <div style="flex: 1; min-width: 0;">
                                    <h4 style="margin: 0 0 8px 0; font-size: 16px; font-weight: 600; color: #1D1D1F; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                                        <?php echo htmlspecialchars($material['titulo']); ?>
                                    </h4>
                                    <?php if ($material['descricao']): ?>
                                        <p style="margin: 0; font-size: 14px; color: #86868B; line-height: 1.5;">
                                            <?php echo htmlspecialchars($material['descricao']); ?>
                                        </p>
                                    <?php endif; ?>
                                    <div style="margin-top: 12px; display: flex; align-items: center; gap: 8px;">
                                        <span class="material-icons-outlined" style="font-size: 18px; color: #6E41C1;">download</span>
                                        <span style="font-size: 13px; font-weight: 600; color: #6E41C1;">Baixar Material</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- Textos -->
    <div class="tab-content-item" id="content-textos">
        <?php if (empty($textos)): ?>
            <div class="empty-state">
                <span class="material-icons-outlined" style="font-size: 64px; color: #E5E5E7;">article</span>
                <p style="font-size: 16px; font-weight: 600; color: #1D1D1F; margin: 16px 0 8px 0;">Nenhum texto adicionado</p>
                <p style="font-size: 14px; color: #86868B; margin: 0;">Adicione conteúdo textual para complementar a aula</p>
            </div>
        <?php else: ?>
            <div style="display: flex; flex-direction: column; gap: 20px;">
                <?php foreach ($textos as $index => $texto): ?>
                    <div class="card">
                        <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 16px;">
                            <div style="width: 40px; height: 40px; background: linear-gradient(135deg, #6E41C1 0%, #8B5FD6 100%); border-radius: 10px; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                                <span style="color: white; font-weight: 700; font-size: 16px;"><?php echo $index + 1; ?></span>
                            </div>
                            <h3 style="margin: 0; font-size: 18px; font-weight: 600; color: #1D1D1F;">
                                <?php echo htmlspecialchars($texto['titulo']); ?>
                            </h3>
                        </div>
                        <div style="font-size: 15px; line-height: 1.7; color: #1D1D1F;">
                            <?php echo nl2br(htmlspecialchars($texto['descricao'] ?? '')); ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- Exercícios -->
    <div class="tab-content-item" id="content-exercicios">
        <?php if (empty($exercicios)): ?>
            <div class="empty-state">
                <span class="material-icons-outlined" style="font-size: 64px; color: #E5E5E7;">quiz</span>
                <p style="font-size: 16px; font-weight: 600; color: #1D1D1F; margin: 16px 0 8px 0;">Nenhum exercício adicionado</p>
                <p style="font-size: 14px; color: #86868B; margin: 0;">Adicione exercícios para avaliar o aprendizado</p>
            </div>
        <?php else: ?>
            <div style="display: flex; flex-direction: column; gap: 16px;">
                <?php foreach ($exercicios as $index => $exercicio): ?>
                    <div class="card">
                        <div style="display: flex; align-items: start; gap: 16px;">
                            <div style="width: 48px; height: 48px; background: rgba(110, 65, 193, 0.1); border-radius: 10px; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                                <span class="material-icons-outlined" style="font-size: 24px; color: #6E41C1;">quiz</span>
                            </div>
                            <div style="flex: 1;">
                                <h3 style="margin: 0 0 8px 0; font-size: 17px; font-weight: 600; color: #1D1D1F;">
                                    <?php echo htmlspecialchars($exercicio['titulo']); ?>
                                </h3>
                                <?php if ($exercicio['descricao']): ?>
                                    <p style="margin: 0; font-size: 14px; color: #86868B; line-height: 1.6;">
                                        <?php echo htmlspecialchars($exercicio['descricao']); ?>
                                    </p>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<style>
/* Tabs */
.tab-button {
    background: transparent;
    border: none;
    padding: 12px 20px;
    border-radius: 8px;
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 14px;
    font-weight: 600;
    color: #86868B;
    cursor: pointer;
    transition: all 0.2s ease;
}

.tab-button:hover {
    background: rgba(110, 65, 193, 0.05);
    color: #6E41C1;
}

.tab-button.active {
    background: linear-gradient(135deg, rgba(110, 65, 193, 0.1) 0%, rgba(139, 95, 214, 0.05) 100%);
    color: #6E41C1;
}

.tab-badge {
    background: #6E41C1;
    color: white;
    padding: 2px 8px;
    border-radius: 10px;
    font-size: 12px;
    font-weight: 700;
}

.tab-button.active .tab-badge {
    background: #6E41C1;
}

.tab-content-item {
    display: none;
}

.tab-content-item.active {
    display: block;
}

/* Material Card Hover */
.material-card:hover > div {
    border-color: #6E41C1;
    box-shadow: 0 4px 12px rgba(110, 65, 193, 0.15);
    transform: translateY(-2px);
}
</style>

<script>
function mostrarTab(tabName) {
    // Remover active de todos os botões
    document.querySelectorAll('.tab-button').forEach(btn => {
        btn.classList.remove('active');
    });

    // Adicionar active no botão clicado
    document.getElementById('tab-' + tabName).classList.add('active');

    // Esconder todos os conteúdos
    document.querySelectorAll('.tab-content-item').forEach(content => {
        content.classList.remove('active');
    });

    // Mostrar conteúdo selecionado
    document.getElementById('content-' + tabName).classList.add('active');
}
</script>

<?php
require_once '../includes/ead-layout-footer.php';
?>

