<?php
/**
 * Página de Upload de Conteúdo
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
$aula_id = (int)($_GET['aula_id'] ?? $_POST['aula_id'] ?? 0);
$erros = [];
$aula = null;
$conteudos = [];

// Obter todas as aulas do parceiro
$stmt = $pdo->prepare('
    SELECT a.*, c.nome as curso_nome FROM aulas a
    INNER JOIN cursos c ON a.curso_id = c.id
    WHERE c.parceiro_id = ?
    ORDER BY c.nome, a.ordem
');
$stmt->execute([$parceiro_id]);
$todas_aulas = $stmt->fetchAll();

// Se aula_id foi fornecido, obter aula específica
if ($aula_id > 0) {
    $aula = $aula_model->obter_por_id($aula_id);

    if ($aula) {
        // Verificar se a aula pertence a um curso do parceiro
        $stmt = $pdo->prepare('SELECT parceiro_id FROM cursos WHERE id = ?');
        $stmt->execute([$aula['curso_id']]);
        $curso_check = $stmt->fetch();

        if ($curso_check['parceiro_id'] != $parceiro_id) {
            $aula = null;
            $aula_id = 0;
        } else {
            // Obter conteúdo existente
            $conteudos = $conteudo_model->obter_por_aula($aula_id);
        }
    } else {
        $aula_id = 0;
    }
}

// Processar exclusão de conteúdo
if (isset($_GET['excluir'])) {
    $conteudo_id = (int)$_GET['excluir'];

    // Verificar se o conteúdo pertence ao parceiro
    $stmt = $pdo->prepare('
        SELECT ca.* FROM conteudo_aulas ca
        INNER JOIN aulas a ON ca.aula_id = a.id
        INNER JOIN cursos c ON a.curso_id = c.id
        WHERE ca.id = ? AND c.parceiro_id = ?
    ');
    $stmt->execute([$conteudo_id, $parceiro_id]);
    $conteudo_excluir = $stmt->fetch();

    if ($conteudo_excluir) {
        $resultado = $conteudo_model->deletar($conteudo_id);

        if ($resultado['sucesso']) {
            $_SESSION['mensagem'] = 'Conteúdo excluído com sucesso!';
            $_SESSION['tipo_mensagem'] = 'success';
        } else {
            $_SESSION['mensagem'] = 'Erro ao excluir conteúdo: ' . $resultado['erro'];
            $_SESSION['tipo_mensagem'] = 'danger';
        }

        header('Location: upload-conteudo.php?aula_id=' . $conteudo_excluir['aula_id']);
        exit;
    }
}

// Processar upload
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $aula_id = (int)($_POST['aula_id'] ?? 0);
    $tipo = sanitizar($_POST['tipo'] ?? '');
    $titulo = sanitizar($_POST['titulo'] ?? '');
    $descricao = sanitizar($_POST['descricao'] ?? '');
    $url_video = sanitizar($_POST['url_video'] ?? '');

    // Validações
    if (empty($aula_id)) {
        $erros[] = 'Selecione uma aula';
    }
    if (empty($tipo)) {
        $erros[] = 'Tipo de conteúdo é obrigatório';
    }
    if (empty($titulo)) {
        $erros[] = 'Título é obrigatório';
    }

    // Processar arquivo se for material
    $url_arquivo = null;
    if ($tipo === 'material' && isset($_FILES['arquivo'])) {
        $arquivo = $_FILES['arquivo'];

        if ($arquivo['error'] !== UPLOAD_ERR_OK) {
            $erros[] = 'Erro ao fazer upload do arquivo';
        } else {
            // Validar tamanho (máx 100MB)
            if ($arquivo['size'] > 100 * 1024 * 1024) {
                $erros[] = 'Arquivo muito grande (máximo 100MB)';
            } else {
                // Criar diretório se não existir
                $upload_dir = '../uploads/conteudo/';
                if (!is_dir($upload_dir)) {
                    mkdir($upload_dir, 0755, true);
                }

                // Gerar nome único
                $nome_arquivo = uniqid() . '_' . basename($arquivo['name']);
                $caminho_arquivo = $upload_dir . $nome_arquivo;

                if (move_uploaded_file($arquivo['tmp_name'], $caminho_arquivo)) {
                    $url_arquivo = 'uploads/conteudo/' . $nome_arquivo;
                } else {
                    $erros[] = 'Erro ao salvar arquivo';
                }
            }
        }
    }

    // Se não houver erros, criar conteúdo
    if (empty($erros)) {
        // Converter URL de vídeo para formato embed
        $url_final = $tipo === 'video' ? $url_video : $url_arquivo;

        if ($tipo === 'video' && !empty($url_video)) {
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
        }

        $resultado = $conteudo_model->criar([
            'aula_id' => $aula_id,
            'tipo' => $tipo,
            'titulo' => $titulo,
            'descricao' => $descricao,
            'url_arquivo' => $url_final,
            'ordem' => count($conteudos) + 1
        ]);
        
        if ($resultado['sucesso']) {
            $_SESSION['mensagem'] = 'Conteúdo adicionado com sucesso!';
            $_SESSION['tipo_mensagem'] = 'success';
            header('Location: upload-conteudo.php?aula_id=' . $aula_id);
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

$titulo_pagina = 'Upload de Conteúdo';
require_once '../includes/ead-layout-header.php';
?>

<!-- Cabeçalho Moderno -->
<div style="background: linear-gradient(135deg, rgba(110, 65, 193, 0.08) 0%, rgba(139, 95, 214, 0.04) 100%); border-radius: 16px; padding: 28px 32px; margin-bottom: 28px; box-shadow: 0 2px 8px rgba(0, 0, 0, 0.06);">
    <div style="display: flex; align-items: center; justify-content: space-between;">
        <div style="display: flex; align-items: center; gap: 16px;">
            <div style="width: 56px; height: 56px; background: linear-gradient(135deg, #6E41C1 0%, #8B5FD6 100%); border-radius: 14px; display: flex; align-items: center; justify-content: center; box-shadow: 0 4px 12px rgba(110, 65, 193, 0.3);">
                <span class="material-icons-outlined" style="font-size: 28px; color: white;">video_library</span>
            </div>
            <div>
                <h1 style="font-size: 28px; font-weight: 700; color: #1D1D1F; margin: 0; line-height: 1.2;">Gerenciar Conteúdo</h1>
                <p style="color: #86868B; font-size: 14px; margin: 4px 0 0 0;">Organize vídeos, materiais e exercícios das suas aulas</p>
            </div>
        </div>
        <a href="aulas.php" class="button button-secondary" style="text-decoration: none;">
            <span class="material-icons-outlined">arrow_back</span>
            <span>Voltar</span>
        </a>
    </div>
</div>

<!-- Seletor de Aula Destacado -->
<div style="background: white; border-radius: 12px; padding: 24px; margin-bottom: 28px; box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05); border: 2px solid #E5E5E7;">
    <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 16px;">
        <span class="material-icons-outlined" style="color: #6E41C1; font-size: 24px;">school</span>
        <h3 style="margin: 0; font-size: 18px; font-weight: 600; color: #1D1D1F;">Selecione a Aula</h3>
    </div>
    <form method="GET">
        <select class="form-control" id="aula_select" name="aula_id" onchange="this.form.submit()" style="font-size: 16px; padding: 14px;">
            <option value="">-- Escolha uma aula para gerenciar o conteúdo --</option>
            <?php foreach ($todas_aulas as $a): ?>
                <option value="<?php echo $a['id']; ?>" <?php echo ($aula_id == $a['id']) ? 'selected' : ''; ?>>
                    📚 <?php echo htmlspecialchars($a['curso_nome']); ?> → <?php echo htmlspecialchars($a['titulo']); ?>
                </option>
            <?php endforeach; ?>
        </select>
    </form>
</div>

<!-- Mensagens -->
<?php if ($mensagem): ?>
    <div class="alert alert-<?php echo $tipo_mensagem === 'success' ? 'success' : 'danger'; ?>">
        <span class="material-icons-outlined"><?php echo $tipo_mensagem === 'success' ? 'check_circle' : 'error'; ?></span>
        <span><?php echo $mensagem; ?></span>
    </div>
<?php endif; ?>

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

<?php if ($aula): ?>
    <!-- Info da Aula -->
    <div style="background: linear-gradient(135deg, #6E41C1 0%, #8B5FD6 100%); border-radius: 12px; padding: 24px; margin-bottom: 28px; color: white;">
        <div style="display: flex; align-items: center; justify-content: space-between;">
            <div>
                <div style="font-size: 14px; opacity: 0.9; margin-bottom: 4px;">Gerenciando conteúdo da aula:</div>
                <h2 style="margin: 0; font-size: 24px; font-weight: 700; color: white;"><?php echo htmlspecialchars($aula['titulo']); ?></h2>
            </div>
            <a href="visualizar-aula.php?aula_id=<?php echo $aula_id; ?>" class="button" style="background: white; color: #6E41C1; text-decoration: none; border: none;">
                <span class="material-icons-outlined">visibility</span>
                <span>Pré-visualizar</span>
            </a>
        </div>
    </div>

    <!-- Layout em 2 Colunas -->
    <div style="display: grid; grid-template-columns: 1fr 1.5fr; gap: 28px; align-items: start;">

        <!-- Coluna Esquerda: Formulário -->
        <div class="card" style="position: sticky; top: 20px;">
            <h2>
                <span class="material-icons-outlined">add_circle</span>
                Adicionar Conteúdo
            </h2>

            <form method="POST" enctype="multipart/form-data" id="formConteudo">
                <input type="hidden" name="aula_id" value="<?php echo $aula_id; ?>">

                <!-- Seletor de Tipo com Cards -->
                <div style="margin-bottom: 24px;">
                    <label style="display: block; font-size: 14px; font-weight: 600; color: #1D1D1F; margin-bottom: 12px;">
                        Tipo de Conteúdo <span style="color: #FF3B30;">*</span>
                    </label>
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
                        <label class="tipo-card" onclick="selecionarTipo('video')">
                            <input type="radio" name="tipo" value="video" id="tipo-video" required style="display: none;">
                            <div class="tipo-card-content">
                                <span class="material-icons-outlined" style="font-size: 32px; color: #6E41C1;">play_circle</span>
                                <div style="font-weight: 600; margin-top: 8px;">Vídeo</div>
                                <div style="font-size: 12px; color: #86868B;">YouTube, Vimeo</div>
                            </div>
                        </label>
                        <label class="tipo-card" onclick="selecionarTipo('material')">
                            <input type="radio" name="tipo" value="material" id="tipo-material" required style="display: none;">
                            <div class="tipo-card-content">
                                <span class="material-icons-outlined" style="font-size: 32px; color: #6E41C1;">description</span>
                                <div style="font-weight: 600; margin-top: 8px;">Material</div>
                                <div style="font-size: 12px; color: #86868B;">PDF, DOC, etc</div>
                            </div>
                        </label>
                        <label class="tipo-card" onclick="selecionarTipo('texto')">
                            <input type="radio" name="tipo" value="texto" id="tipo-texto" required style="display: none;">
                            <div class="tipo-card-content">
                                <span class="material-icons-outlined" style="font-size: 32px; color: #6E41C1;">article</span>
                                <div style="font-weight: 600; margin-top: 8px;">Texto</div>
                                <div style="font-size: 12px; color: #86868B;">Conteúdo escrito</div>
                            </div>
                        </label>
                        <label class="tipo-card" onclick="selecionarTipo('exercicio')">
                            <input type="radio" name="tipo" value="exercicio" id="tipo-exercicio" required style="display: none;">
                            <div class="tipo-card-content">
                                <span class="material-icons-outlined" style="font-size: 32px; color: #6E41C1;">quiz</span>
                                <div style="font-weight: 600; margin-top: 8px;">Exercício</div>
                                <div style="font-size: 12px; color: #86868B;">Questões</div>
                            </div>
                        </label>
                    </div>
                </div>

                <div class="form-group">
                    <label for="titulo">Título <span style="color: #FF3B30;">*</span></label>
                    <input type="text" class="form-control" id="titulo" name="titulo"
                           placeholder="Ex: Introdução ao PHP" required>
                </div>

                <div class="form-group">
                    <label for="descricao">Descrição</label>
                    <textarea class="form-control" id="descricao" name="descricao"
                              rows="3" placeholder="Descreva o conteúdo..."></textarea>
                </div>

                <!-- Campo de Vídeo -->
                <div id="campo-video" style="display:none;">
                    <div class="form-group">
                        <label for="url_video">URL do Vídeo <span style="color: #FF3B30;">*</span></label>
                        <input type="url" class="form-control" id="url_video" name="url_video"
                               placeholder="https://www.youtube.com/embed/...">
                        <small style="color: #86868B; font-size: 12px; margin-top: 4px; display: block;">Cole a URL do vídeo</small>
                    </div>
                </div>

                <!-- Campo de Material -->
                <div id="campo-material" style="display:none;">
                    <div class="form-group">
                        <label for="arquivo">Arquivo <span style="color: #FF3B30;">*</span></label>
                        <input type="file" class="form-control" id="arquivo" name="arquivo" style="padding: 10px;">
                        <small style="color: #86868B; font-size: 12px; margin-top: 4px; display: block;">Máximo 100MB</small>
                    </div>
                </div>

                <button type="submit" class="button button-primary" style="width: 100%; justify-content: center;">
                    <span class="material-icons-outlined">add</span>
                    <span>Adicionar</span>
                </button>
            </form>
        </div>

        <!-- Coluna Direita: Lista de Conteúdos -->
        <div class="card">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                <h2 style="margin: 0;">
                    <span class="material-icons-outlined">format_list_numbered</span>
                    Conteúdos (<?php echo count($conteudos); ?>)
                </h2>
            </div>

            <?php if (empty($conteudos)): ?>
                <div class="empty-state">
                    <span class="material-icons-outlined" style="font-size: 64px; color: #E5E5E7;">video_library</span>
                    <p style="font-size: 16px; font-weight: 600; color: #1D1D1F; margin: 16px 0 8px 0;">Nenhum conteúdo ainda</p>
                    <p style="font-size: 14px; color: #86868B; margin: 0;">Use o formulário ao lado para adicionar vídeos, materiais e exercícios</p>
                </div>
            <?php else: ?>
                <!-- Lista de Cards de Conteúdo -->
                <div style="display: flex; flex-direction: column; gap: 12px;">
                    <?php foreach ($conteudos as $conteudo): ?>
                        <div class="conteudo-item">
                            <div style="display: flex; align-items: center; gap: 16px;">
                                <!-- Ordem -->
                                <div style="width: 40px; height: 40px; background: linear-gradient(135deg, #6E41C1 0%, #8B5FD6 100%); border-radius: 10px; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                                    <span style="color: white; font-weight: 700; font-size: 16px;"><?php echo $conteudo['ordem']; ?></span>
                                </div>

                                <!-- Ícone do Tipo -->
                                <div style="width: 48px; height: 48px; background: rgba(110, 65, 193, 0.1); border-radius: 10px; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                                    <span class="material-icons-outlined" style="font-size: 24px; color: #6E41C1;">
                                        <?php
                                            echo $conteudo['tipo'] === 'video' ? 'play_circle' :
                                                 ($conteudo['tipo'] === 'material' ? 'description' :
                                                  ($conteudo['tipo'] === 'texto' ? 'article' : 'quiz'));
                                        ?>
                                    </span>
                                </div>

                                <!-- Info -->
                                <div style="flex: 1; min-width: 0;">
                                    <div style="font-weight: 600; font-size: 15px; color: #1D1D1F; margin-bottom: 2px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                                        <?php echo htmlspecialchars($conteudo['titulo']); ?>
                                    </div>
                                    <div style="display: flex; align-items: center; gap: 8px;">
                                        <span class="badge" style="background: rgba(110, 65, 193, 0.1); color: #6E41C1; padding: 4px 10px; border-radius: 6px; font-size: 12px; font-weight: 600;">
                                            <?php echo ucfirst($conteudo['tipo']); ?>
                                        </span>
                                        <?php if ($conteudo['descricao']): ?>
                                            <span style="font-size: 13px; color: #86868B; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                                                <?php echo htmlspecialchars(substr($conteudo['descricao'], 0, 50)); ?><?php echo strlen($conteudo['descricao']) > 50 ? '...' : ''; ?>
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                </div>

                                <!-- Ações -->
                                <div style="display: flex; gap: 8px; flex-shrink: 0;">
                                    <a href="editar-conteudo.php?id=<?php echo $conteudo['id']; ?>"
                                       class="button button-secondary"
                                       style="text-decoration: none; padding: 10px 16px; font-size: 13px;"
                                       title="Editar">
                                        <span class="material-icons-outlined" style="font-size: 18px;">edit</span>
                                    </a>
                                    <button onclick="confirmarExclusao(<?php echo $conteudo['id']; ?>, '<?php echo htmlspecialchars(addslashes($conteudo['titulo'])); ?>')"
                                            class="button button-danger"
                                            style="padding: 10px 16px; font-size: 13px;"
                                            title="Excluir">
                                        <span class="material-icons-outlined" style="font-size: 18px;">delete</span>
                                    </button>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
<?php else: ?>
    <div class="alert alert-info">
        <span class="material-icons-outlined">info</span>
        <span><strong>Selecione uma aula</strong> para adicionar conteúdo</span>
    </div>
<?php endif; ?>

<style>
/* Cards de Tipo de Conteúdo */
.tipo-card {
    cursor: pointer;
    display: block;
    transition: all 0.2s ease;
}

.tipo-card-content {
    background: white;
    border: 2px solid #E5E5E7;
    border-radius: 12px;
    padding: 20px;
    text-align: center;
    transition: all 0.2s ease;
}

.tipo-card:hover .tipo-card-content {
    border-color: #6E41C1;
    background: rgba(110, 65, 193, 0.02);
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(110, 65, 193, 0.15);
}

.tipo-card input:checked + .tipo-card-content {
    border-color: #6E41C1;
    background: linear-gradient(135deg, rgba(110, 65, 193, 0.08) 0%, rgba(139, 95, 214, 0.04) 100%);
    box-shadow: 0 4px 12px rgba(110, 65, 193, 0.2);
}

/* Item de Conteúdo */
.conteudo-item {
    background: white;
    border: 1px solid #E5E5E7;
    border-radius: 12px;
    padding: 16px;
    transition: all 0.2s ease;
}

.conteudo-item:hover {
    border-color: #6E41C1;
    box-shadow: 0 4px 12px rgba(110, 65, 193, 0.1);
    transform: translateY(-2px);
}

/* Responsivo */
@media (max-width: 1024px) {
    div[style*="grid-template-columns: 1fr 1.5fr"] {
        grid-template-columns: 1fr !important;
    }

    .card[style*="position: sticky"] {
        position: relative !important;
    }
}
</style>

<script>
function selecionarTipo(tipo) {
    // Marcar o radio button
    document.getElementById('tipo-' + tipo).checked = true;

    // Atualizar formulário
    const campoVideo = document.getElementById('campo-video');
    const campoMaterial = document.getElementById('campo-material');
    const urlVideo = document.getElementById('url_video');
    const arquivo = document.getElementById('arquivo');

    // Ocultar todos os campos
    campoVideo.style.display = 'none';
    campoMaterial.style.display = 'none';

    // Remover required
    urlVideo.removeAttribute('required');
    arquivo.removeAttribute('required');

    // Mostrar campo apropriado
    if (tipo === 'video') {
        campoVideo.style.display = 'block';
        urlVideo.setAttribute('required', 'required');
    } else if (tipo === 'material') {
        campoMaterial.style.display = 'block';
        arquivo.setAttribute('required', 'required');
    }
}

function confirmarExclusao(id, titulo) {
    if (confirm('Tem certeza que deseja excluir o conteúdo "' + titulo + '"?\n\nEsta ação não pode ser desfeita.')) {
        window.location.href = 'upload-conteudo.php?aula_id=<?php echo $aula_id; ?>&excluir=' + id;
    }
}
</script>

<?php
require_once '../includes/ead-layout-footer.php';
?>