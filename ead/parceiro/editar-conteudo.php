<?php
/**
 * Página de Editar Conteúdo
 * Sistema EAD Pro
 */

require_once '../config/database.php';
require_once '../app/models/Aula.php';
require_once '../app/models/ConteudoAula.php';

// Verificar autenticação
verificar_autenticacao('parceiro');

$parceiro_id = $_SESSION['ead_parceiro_id'];
$aula_model = new Aula($pdo);
$conteudo_model = new ConteudoAula($pdo);
$conteudo_id = (int)($_GET['id'] ?? 0);
$erros = [];

// Obter conteúdo
$conteudo = $conteudo_model->obter_por_id($conteudo_id);

if (!$conteudo) {
    $_SESSION['mensagem'] = 'Conteúdo não encontrado!';
    $_SESSION['tipo_mensagem'] = 'danger';
    header('Location: aulas.php');
    exit;
}

// Obter aula
$aula = $aula_model->obter_por_id($conteudo['aula_id']);

// Verificar se a aula pertence a um curso do parceiro
$stmt = $pdo->prepare('SELECT parceiro_id FROM cursos WHERE id = ?');
$stmt->execute([$aula['curso_id']]);
$curso_check = $stmt->fetch();

if ($curso_check['parceiro_id'] != $parceiro_id) {
    $_SESSION['mensagem'] = 'Acesso negado!';
    $_SESSION['tipo_mensagem'] = 'danger';
    header('Location: aulas.php');
    exit;
}

// Processar formulário
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $titulo = sanitizar($_POST['titulo'] ?? '');
    $descricao = sanitizar($_POST['descricao'] ?? '');
    $url_video = sanitizar($_POST['url_video'] ?? '');
    
    // Validações
    if (empty($titulo)) {
        $erros[] = 'Título é obrigatório';
    }
    
    // Se não houver erros, atualizar conteúdo
    if (empty($erros)) {
        $dados_atualizacao = [
            'titulo' => $titulo,
            'descricao' => $descricao
        ];

        if ($conteudo['tipo'] === 'video' && !empty($url_video)) {
            // Converter URL de vídeo para formato embed
            $url_final = $url_video;

            // Se for URL do YouTube (watch?v=), converter para embed
            if (strpos($url_video, 'youtube.com/watch?v=') !== false) {
                preg_match('/[?&]v=([^&]+)/', $url_video, $matches);
                if (isset($matches[1])) {
                    $url_final = 'https://www.youtube.com/embed/' . $matches[1];
                }
            }
            // Se for URL curta do YouTube (youtu.be/), converter para embed
            elseif (strpos($url_video, 'youtu.be/') !== false) {
                preg_match('/youtu\.be\/([^?]+)/', $url_video, $matches);
                if (isset($matches[1])) {
                    $url_final = 'https://www.youtube.com/embed/' . $matches[1];
                }
            }
            // Se for Vimeo, converter para embed
            elseif (strpos($url_video, 'vimeo.com/') !== false && strpos($url_video, '/video/') === false) {
                preg_match('/vimeo\.com\/(\d+)/', $url_video, $matches);
                if (isset($matches[1])) {
                    $url_final = 'https://player.vimeo.com/video/' . $matches[1];
                }
            }

            $dados_atualizacao['url_arquivo'] = $url_final;
        }

        $resultado = $conteudo_model->atualizar($conteudo_id, $dados_atualizacao);
        
        if ($resultado['sucesso']) {
            $_SESSION['mensagem'] = 'Conteúdo atualizado com sucesso!';
            $_SESSION['tipo_mensagem'] = 'success';
            header('Location: upload-conteudo.php?aula_id=' . $conteudo['aula_id']);
            exit;
        } else {
            $erros[] = $resultado['erro'];
        }
    }
}

// Obter mensagem de sessão
$mensagem = $_SESSION['mensagem'] ?? '';
$tipo_mensagem = $_SESSION['tipo_mensagem'] ?? '';
unset($_SESSION['mensagem'], $_SESSION['tipo_mensagem']);

$titulo_pagina = 'Editar Conteúdo';
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
                <h1 style="font-size: 28px; font-weight: 700; color: #1D1D1F; margin: 0; line-height: 1.2;">Editar Conteúdo</h1>
                <p style="color: #86868B; font-size: 14px; margin: 4px 0 0 0;">Atualize os dados do conteúdo</p>
            </div>
        </div>
        <a href="upload-conteudo.php?aula_id=<?php echo $conteudo['aula_id']; ?>" class="button button-secondary" style="text-decoration: none;">
            <span class="material-icons-outlined">arrow_back</span>
            <span>Voltar</span>
        </a>
    </div>
</div>

<!-- Informações da Aula -->
<div class="card" style="margin-bottom: 24px;">
    <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 20px;">
        <h2 style="margin: 0; font-size: 18px; font-weight: 600; color: #1D1D1F;">
            <span class="material-icons-outlined" style="vertical-align: middle; color: #6E41C1;">info</span>
            Informações
        </h2>
    </div>

    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
        <div>
            <div style="font-size: 13px; color: #86868B; margin-bottom: 4px;">Aula</div>
            <div style="font-size: 15px; font-weight: 600; color: #1D1D1F;">
                <?php echo htmlspecialchars($aula['titulo']); ?>
            </div>
        </div>
        <div>
            <div style="font-size: 13px; color: #86868B; margin-bottom: 4px;">Tipo de Conteúdo</div>
            <div>
                <?php
                $tipo_icons = [
                    'video' => 'play_circle',
                    'material' => 'description',
                    'texto' => 'article',
                    'exercicio' => 'quiz'
                ];
                $tipo_labels = [
                    'video' => 'Vídeo',
                    'material' => 'Material',
                    'texto' => 'Texto',
                    'exercicio' => 'Exercício'
                ];
                $icon = $tipo_icons[$conteudo['tipo']] ?? 'description';
                $label = $tipo_labels[$conteudo['tipo']] ?? ucfirst($conteudo['tipo']);
                ?>
                <span class="badge" style="background: rgba(110, 65, 193, 0.1); color: #6E41C1; padding: 6px 12px; border-radius: 6px; font-size: 13px; font-weight: 600;">
                    <span class="material-icons-outlined" style="font-size: 16px; vertical-align: middle;"><?php echo $icon; ?></span>
                    <?php echo $label; ?>
                </span>
            </div>
        </div>
    </div>
</div>

<!-- Erros -->
<?php if (!empty($erros)): ?>
    <div class="alert alert-danger" style="margin-bottom: 24px;">
        <div style="display: flex; align-items: start; gap: 12px;">
            <span class="material-icons-outlined" style="color: #FF3B30; flex-shrink: 0;">error</span>
            <div style="flex: 1;">
                <strong>Erros encontrados:</strong>
                <ul style="margin: 8px 0 0 0; padding-left: 20px;">
                    <?php foreach ($erros as $erro): ?>
                        <li><?php echo htmlspecialchars($erro); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>
    </div>
<?php endif; ?>

<!-- Formulário -->
<div class="card">
    <h2>
        <span class="material-icons-outlined">edit</span>
        Editar Conteúdo
    </h2>

    <form method="POST">
        <div style="margin-bottom: 20px;">
            <label for="titulo" style="display: block; font-weight: 600; color: #1D1D1F; margin-bottom: 8px;">
                Título <span style="color: #FF3B30;">*</span>
            </label>
            <input type="text" class="form-control" id="titulo" name="titulo"
                   placeholder="Ex: Introdução ao PHP" required
                   value="<?php echo htmlspecialchars($conteudo['titulo']); ?>">
        </div>

        <div style="margin-bottom: 20px;">
            <label for="descricao" style="display: block; font-weight: 600; color: #1D1D1F; margin-bottom: 8px;">
                Descrição
            </label>
            <textarea class="form-control" id="descricao" name="descricao"
                      rows="4" placeholder="Descreva o conteúdo..."><?php echo htmlspecialchars($conteudo['descricao'] ?? ''); ?></textarea>
        </div>

        <?php if ($conteudo['tipo'] === 'video'): ?>
            <div style="margin-bottom: 20px;">
                <label for="url_video" style="display: block; font-weight: 600; color: #1D1D1F; margin-bottom: 8px;">
                    URL do Vídeo <span style="color: #FF3B30;">*</span>
                </label>
                <input type="url" class="form-control" id="url_video" name="url_video"
                       placeholder="https://www.youtube.com/watch?v=..." required
                       value="<?php echo htmlspecialchars($conteudo['url_arquivo'] ?? ''); ?>">
                <small style="display: block; margin-top: 6px; font-size: 13px; color: #86868B;">
                    <span class="material-icons-outlined" style="font-size: 14px; vertical-align: middle;">info</span>
                    Cole a URL do YouTube ou Vimeo (será convertida automaticamente)
                </small>
            </div>
        <?php elseif ($conteudo['tipo'] === 'material'): ?>
            <div style="background: rgba(110, 65, 193, 0.05); border-radius: 12px; padding: 16px; margin-bottom: 20px;">
                <div style="display: flex; align-items: center; gap: 12px;">
                    <span class="material-icons-outlined" style="color: #6E41C1; font-size: 24px;">description</span>
                    <div>
                        <div style="font-size: 13px; color: #86868B; margin-bottom: 2px;">Arquivo Atual</div>
                        <a href="<?php echo htmlspecialchars($conteudo['url_arquivo']); ?>" target="_blank"
                           style="font-size: 14px; font-weight: 600; color: #6E41C1; text-decoration: none;">
                            <?php echo basename($conteudo['url_arquivo']); ?>
                            <span class="material-icons-outlined" style="font-size: 16px; vertical-align: middle;">open_in_new</span>
                        </a>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <div style="display: flex; gap: 12px; margin-top: 28px;">
            <button type="submit" class="button button-primary">
                <span class="material-icons-outlined">save</span>
                <span>Salvar Alterações</span>
            </button>
            <a href="upload-conteudo.php?aula_id=<?php echo $conteudo['aula_id']; ?>" class="button button-secondary" style="text-decoration: none;">
                <span class="material-icons-outlined">close</span>
                <span>Cancelar</span>
            </a>
        </div>
    </form>
</div>

<?php
require_once '../includes/ead-layout-footer.php';
?>