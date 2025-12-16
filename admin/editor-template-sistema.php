<?php
/**
 * Editor Visual de Template do Sistema
 * Permite arrastar campos e ajustar posições e estilos
 */

require_once '../app/config/config.php';

if (!isAuthenticated() || !hasRole(ROLE_ADMIN)) {
    redirect(APP_URL . '/login.php');
}

$template_id = (int) ($_GET['id'] ?? 0);

if ($template_id <= 0) {
    $_SESSION['error'] = 'Template inválido';
    redirect(APP_URL . '/admin/templates-sistema.php');
}

$conn = getDBConnection();

// Buscar template
$stmt = $conn->prepare("SELECT * FROM templates_certificados WHERE id = ? AND template_sistema = 1");
$stmt->bind_param("i", $template_id);
$stmt->execute();
$template = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$template) {
    $_SESSION['error'] = 'Template não encontrado';
    redirect(APP_URL . '/admin/templates-sistema.php');
}

// Buscar campos customizados
$campos_customizados = [];
$stmt = $conn->prepare("SELECT * FROM template_campos_customizados WHERE template_id = ? ORDER BY ordem ASC");
$stmt->bind_param("i", $template_id);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $campos_customizados[] = $row;
}
$stmt->close();

$page_title = 'Editar Template do Sistema - ' . APP_NAME;
?>
<?php require_once '../app/views/admin-layout-header.php'; ?>

<style>
    .editor-container {
        display: flex;
        gap: 20px;
        padding: 20px;
    }

    .editor-preview {
        flex: 1;
        background: #f5f5f5;
        border-radius: 12px;
        padding: 20px;
        position: relative;
    }

    .template-image-container {
        position: relative;
        display: inline-block;
        border: 2px solid #ddd;
        background: white;
    }

    .template-image {
        display: block;
        max-width: 100%;
        height: auto;
    }

    .campo-marker {
        position: absolute;
        background: rgba(110, 65, 193, 0.8);
        color: white;
        padding: 4px 8px;
        border-radius: 4px;
        font-size: 11px;
        cursor: move;
        user-select: none;
        border: 2px solid #6E41C1;
        white-space: nowrap;
        z-index: 10;
    }

    .campo-marker:hover {
        background: rgba(110, 65, 193, 1);
        box-shadow: 0 2px 8px rgba(110, 65, 193, 0.4);
    }

    .campo-marker.dragging {
        opacity: 0.7;
        cursor: grabbing;
    }

    .editor-sidebar {
        width: 300px;
        background: white;
        border-radius: 12px;
        padding: 20px;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
    }

    .campo-config {
        margin-bottom: 20px;
        padding: 15px;
        background: #f8f9fa;
        border-radius: 8px;
        border-left: 4px solid #6E41C1;
    }

    .campo-config h4 {
        margin: 0 0 10px 0;
        color: #6E41C1;
        font-size: 14px;
    }

    .campo-info {
        font-size: 12px;
        color: #666;
        margin: 5px 0;
    }

    .save-button {
        width: 100%;
        padding: 12px;
        background: #34C759;
        color: white;
        border: none;
        border-radius: 8px;
        font-size: 16px;
        font-weight: 600;
        cursor: pointer;
        margin-top: 20px;
    }

    .save-button:hover {
        background: #2da64a;
    }

    .style-controls {
        margin-top: 10px;
        padding-top: 10px;
        border-top: 1px solid #eee;
    }

    .form-group {
        margin-bottom: 8px;
    }

    .form-group label {
        display: block;
        font-size: 11px;
        color: #666;
        margin-bottom: 2px;
    }

    .style-input {
        width: 100%;
        padding: 4px;
        border: 1px solid #ddd;
        border-radius: 4px;
        font-size: 12px;
    }

    .row-group {
        display: flex;
        gap: 10px;
    }
</style>

<!-- Header -->
<div class="page-header">
    <h1>Editor de Template: <?php echo htmlspecialchars($template['nome']); ?></h1>
    <div class="action-buttons">
        <a href="templates-sistema.php" class="button button-secondary">
            <span class="icon">arrow_back</span> Voltar
        </a>
    </div>
</div>

<!-- Mensagens -->
<?php if (isset($_SESSION['success'])): ?>
    <div class="alert alert-success">
        <span class="icon">check_circle</span>
    </div>
<?php endif; ?>

<!-- Instruções -->
<div class="alert alert-info" style="margin-bottom: 20px;">
    <span class="icon">info</span>
    <div>
        <strong>Como usar:</strong><br>
        Arraste os campos roxos sobre a imagem para ajustar as posições.<br>
        Use os controles laterais para ajustar tamanho, cor e fonte.<br>
        Clique em "Salvar Posições" quando terminar.
    </div>
</div>

<!-- Editor -->
<div class="editor-container">
    <div class="editor-preview">
        <div class="template-image-container" id="imageContainer">
            <img src="<?php echo htmlspecialchars($template['arquivo_url']); ?>" class="template-image"
                id="templateImage" alt="Template">

            <!-- Markers dos campos -->

            <!-- Campo CPF -->
            <?php foreach ($campos_customizados as $campo): ?>
                <div class="campo-marker" data-campo-id="<?php echo $campo['id']; ?>" data-tipo="customizado"
                    data-label="<?php echo htmlspecialchars($campo['label']); ?>"
                    data-x="<?php echo $campo['posicao_x']; ?>" data-y="<?php echo $campo['posicao_y']; ?>"
                    style="left: 0; top: 0; display: none;"> <!-- Hidden until positioned by JS -->
                    <?php echo htmlspecialchars($campo['label']); ?>
                </div>
            <?php endforeach; ?>

            <!-- Campo CURSO -->
            <?php if ($template['exibir_curso']): ?>
                <div class="campo-marker" data-campo="curso" data-tipo="padrao"
                    data-x="<?php echo $template['posicao_curso_x']; ?>"
                    data-y="<?php echo $template['posicao_curso_y']; ?>" style="left: 0; top: 0; display: none;">
                    CURSO
                </div>
            <?php endif; ?>

            <!-- Campo CARGA HORÁRIA -->
            <?php if ($template['exibir_carga_horaria']): ?>
                <div class="campo-marker" data-campo="carga_horaria" data-tipo="padrao"
                    data-x="<?php echo $template['posicao_carga_horaria_x']; ?>"
                    data-y="<?php echo $template['posicao_carga_horaria_y']; ?>" style="left: 0; top: 0; display: none;">
                    CARGA HORÁRIA
                </div>
            <?php endif; ?>

            <!-- Campo POLO PARCEIRO (Apenas Template 25) -->
            <?php if ($template_id == 25 || $template['exibir_polo']): ?>
                <div class="campo-marker" data-campo="polo" data-tipo="padrao"
                    data-x="<?php echo $template['posicao_polo_x']; ?>" data-y="<?php echo $template['posicao_polo_y']; ?>"
                    style="left: 0; top: 0; display: none;">
                    NOME DO POLO PARCEIRO
                </div>
            <?php endif; ?>

            <!-- Campo QR CODE -->
            <div class="campo-marker" data-campo="qrcode" data-tipo="padrao"
                data-x="<?php echo $template['posicao_qrcode_x'] ?? 0; ?>"
                data-y="<?php echo $template['posicao_qrcode_y'] ?? 0; ?>"
                style="left: 0; top: 0; display: <?php echo ($template['exibir_qrcode'] ?? 0) ? 'block' : 'none'; ?>; background: rgba(0, 123, 255, 0.8); border-color: #007bff;">
                QR CODE
            </div>
        </div>
    </div>

    <div class="editor-sidebar">
        <h3>Campos Ativos</h3>

        <?php
        $fonts = [
            'Arial',
            'Helvetica',
            'Times',
            'Courier',
            'Symbol',
            'AbrilFatface-Regular',
            'AmaticSC-Regular',
            'Anton-Regular',
            'Bangers-Regular',
            'BebasNeue-Regular',
            'CevicheOne-Regular',
            'Creepster-Regular',
            'GreatVibes-Regular',
            'IndieFlower-Regular',
            'Lato-Regular',
            'Lobster-Regular',
            'Mukta',
            'NotoSans',
            'Pacifico-Regular',
            'Poppins-Regular',
            'Ubuntu-Regular'
        ];
        ?>

        <?php foreach ($campos_customizados as $campo): ?>
            <div class="campo-config">
                <h4><?php echo htmlspecialchars($campo['label']); ?></h4>
                <div class="campo-info" id="info-campo-<?php echo $campo['id']; ?>">
                    X: <?php echo $campo['posicao_x']; ?> | Y: <?php echo $campo['posicao_y']; ?>
                </div>

                <div class="style-controls">
                    <div class="row-group">
                        <div class="form-group" style="flex: 1;">
                            <label>Tamanho (pt)</label>
                            <input type="number" class="style-input input-size" data-target-type="customizado"
                                data-target-id="<?php echo $campo['id']; ?>"
                                value="<?php echo $campo['tamanho_fonte'] ?: 14; ?>" onchange="updateMarkerStyle(this)">
                        </div>
                        <div class="form-group" style="width: 60px;">
                            <label>Cor</label>
                            <input type="color" class="style-input input-color" data-target-type="customizado"
                                data-target-id="<?php echo $campo['id']; ?>"
                                value="<?php echo $campo['cor_hex'] ?: '#000000'; ?>" onchange="updateMarkerStyle(this)">
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Fonte</label>
                        <select class="style-input input-font" data-target-type="customizado"
                            data-target-id="<?php echo $campo['id']; ?>" onchange="updateMarkerStyle(this)">
                            <?php
                            $currentFont = $campo['fonte'] ?: 'Arial';
                            foreach ($fonts as $f) {
                                $sel = ($f == $currentFont) ? 'selected' : '';
                                echo "<option value='$f' $sel>$f</option>";
                            }
                            ?>
                        </select>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>

        <?php if ($template['exibir_curso']): ?>
            <div class="campo-config">
                <h4>CURSO</h4>
                <div class="campo-info" id="info-curso">
                    X: <?php echo $template['posicao_curso_x']; ?> | Y: <?php echo $template['posicao_curso_y']; ?>
                </div>

                <div class="style-controls">
                    <div class="row-group">
                        <div class="form-group" style="flex: 1;">
                            <label>Tamanho (pt)</label>
                            <input type="number" class="style-input input-size" data-target-type="padrao"
                                data-target-id="curso" value="<?php echo $template['tamanho_fonte_curso'] ?: 16; ?>"
                                onchange="updateMarkerStyle(this)">
                        </div>
                        <div class="form-group" style="width: 60px;">
                            <label>Cor</label>
                            <input type="color" class="style-input input-color" data-target-type="padrao"
                                data-target-id="curso" value="<?php echo $template['cor_curso'] ?: '#000000'; ?>"
                                onchange="updateMarkerStyle(this)">
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Fonte</label>
                        <select class="style-input input-font" data-target-type="padrao" data-target-id="curso"
                            onchange="updateMarkerStyle(this)">
                            <?php
                            $currentFont = $template['fonte_curso'] ?: 'Arial';
                            foreach ($fonts as $f) {
                                $sel = ($f == $currentFont) ? 'selected' : '';
                                echo "<option value='$f' $sel>$f</option>";
                            }
                            ?>
                        </select>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <?php if ($template['exibir_carga_horaria']): ?>
            <div class="campo-config">
                <h4>CARGA HORÁRIA</h4>
                <div class="campo-info" id="info-carga_horaria">
                    X: <?php echo $template['posicao_carga_horaria_x']; ?> | Y:
                    <?php echo $template['posicao_carga_horaria_y']; ?>
                </div>

                <div class="style-controls">
                    <div class="row-group">
                        <div class="form-group" style="flex: 1;">
                            <label>Tamanho (pt)</label>
                            <input type="number" class="style-input input-size" data-target-type="padrao"
                                data-target-id="carga_horaria"
                                value="<?php echo $template['tamanho_fonte_carga_horaria'] ?: 12; ?>"
                                onchange="updateMarkerStyle(this)">
                        </div>
                        <div class="form-group" style="width: 60px;">
                            <label>Cor</label>
                            <input type="color" class="style-input input-color" data-target-type="padrao"
                                data-target-id="carga_horaria"
                                value="<?php echo $template['cor_carga_horaria'] ?: '#000000'; ?>"
                                onchange="updateMarkerStyle(this)">
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Fonte</label>
                        <select class="style-input input-font" data-target-type="padrao" data-target-id="carga_horaria"
                            onchange="updateMarkerStyle(this)">
                            <?php
                            $currentFont = $template['fonte_carga_horaria'] ?: 'Arial';
                            foreach ($fonts as $f) {
                                $sel = ($f == $currentFont) ? 'selected' : '';
                                echo "<option value='$f' $sel>$f</option>";
                            }
                            ?>
                        </select>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <?php if ($template_id == 25 || $template['exibir_polo']): ?>
            <div class="campo-config">
                <h4>NOME DO POLO PARCEIRO</h4>
                <div class="campo-info" id="info-polo">
                    X: <?php echo $template['posicao_polo_x']; ?> | Y: <?php echo $template['posicao_polo_y']; ?>
                </div>

                <div class="style-controls">
                    <div class="row-group">
                        <div class="form-group" style="flex: 1;">
                            <label>Tamanho (pt)</label>
                            <input type="number" class="style-input input-size" data-target-type="padrao"
                                data-target-id="polo" value="<?php echo $template['tamanho_fonte_polo'] ?: 12; ?>"
                                onchange="updateMarkerStyle(this)">
                        </div>
                        <div class="form-group" style="width: 60px;">
                            <label>Cor</label>
                            <input type="color" class="style-input input-color" data-target-type="padrao"
                                data-target-id="polo" value="<?php echo $template['cor_polo'] ?: '#000000'; ?>"
                                onchange="updateMarkerStyle(this)">
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Fonte</label>
                        <select class="style-input input-font" data-target-type="padrao" data-target-id="polo"
                            onchange="updateMarkerStyle(this)">
                            <?php
                            $currentFont = $template['fonte_polo'] ?: 'Arial';
                            foreach ($fonts as $f) {
                                $sel = ($f == $currentFont) ? 'selected' : '';
                                echo "<option value='$f' $sel>$f</option>";
                            }
                            ?>
                        </select>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <!-- QR CODE CONTROL -->
        <div class="campo-config" style="border-left-color: #007bff;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px;">
                <h4 style="color: #007bff; margin: 0;">QR CODE VERIFICAÇÃO</h4>
                <label class="switch" style="font-size: 12px;">
                    <input type="checkbox" id="toggle-qrcode" <?php echo ($template['exibir_qrcode'] ?? 0) ? 'checked' : ''; ?> onchange="toggleQrCode(this)">
                    Exibir
                </label>
            </div>

            <div id="qrcode-controls"
                style="display: <?php echo ($template['exibir_qrcode'] ?? 0) ? 'block' : 'none'; ?>;">
                <div class="campo-info" id="info-qrcode">
                    X: <?php echo $template['posicao_qrcode_x'] ?? 0; ?> | Y:
                    <?php echo $template['posicao_qrcode_y'] ?? 0; ?>
                </div>

                <div class="style-controls">
                    <div class="form-group">
                        <label>Tamanho (px)</label>
                        <input type="number" class="style-input input-size" data-target-type="padrao"
                            data-target-id="qrcode" value="<?php echo $template['tamanho_qrcode'] ?: 100; ?>"
                            onchange="updateMarkerStyle(this)">
                    </div>
                </div>
            </div>
        </div>

        <button class="save-button" onclick="salvarPosicoes()">
            <span class="icon">save</span> Salvar Posições
        </button>
    </div>
</div>

<script>
    // Sistema de arrastar campos
    let isDragging = false;
    let currentMarker = null;
    let offsetX = 0;
    let offsetY = 0;

    const markers = document.querySelectorAll('.campo-marker');
    const container = document.getElementById('imageContainer');
    const img = document.getElementById('templateImage');

    // Inicializar posições quando a imagem carregar
    img.onload = function () {
        adjustMarkers();
        initMarkerStyles();
    };

    // Se já estiver carregada (cache)
    if (img.complete) {
        adjustMarkers();
        initMarkerStyles();
    }

    // Reajustar ao redimensionar a tela
    window.addEventListener('resize', adjustMarkers);

    function adjustMarkers() {
        if (!img.clientWidth || !img.naturalWidth) return;

        const ratio = img.naturalWidth / img.clientWidth;

        markers.forEach(marker => {
            // Se estiver arrastando, não atualiza (evita glitch)
            if (marker.classList.contains('dragging')) return;

            const originalX = parseInt(marker.dataset.x) || 0;
            const originalY = parseInt(marker.dataset.y) || 0;

            // Converter True Pixels para Screen Pixels
            const screenX = Math.round(originalX / ratio);
            const screenY = Math.round(originalY / ratio);

            marker.style.left = screenX + 'px';
            marker.style.top = screenY + 'px';
            marker.style.display = 'block'; // Mostrar após posicionar

            // Atualizar sidebar com valores reais
            updateSidebarInfo(marker, originalX, originalY);
        });
    }

    markers.forEach(marker => {
        marker.addEventListener('mousedown', startDrag);
    });

    function startDrag(e) {
        isDragging = true;
        currentMarker = e.target;
        currentMarker.classList.add('dragging');

        const rect = currentMarker.getBoundingClientRect();
        const containerRect = container.getBoundingClientRect();

        offsetX = e.clientX - rect.left;
        offsetY = e.clientY - rect.top;

        document.addEventListener('mousemove', drag);
        document.addEventListener('mouseup', stopDrag);

        e.preventDefault();
    }

    function drag(e) {
        if (!isDragging || !currentMarker) return;

        const containerRect = container.getBoundingClientRect();

        let x = e.clientX - containerRect.left - offsetX;
        let y = e.clientY - containerRect.top - offsetY;

        // Limites
        x = Math.max(0, Math.min(x, containerRect.width - currentMarker.offsetWidth));
        y = Math.max(0, Math.min(y, containerRect.height - currentMarker.offsetHeight));

        currentMarker.style.left = x + 'px';
        currentMarker.style.top = y + 'px';

        // Atualizar sidebar e dataset
        const ratio = img.naturalWidth / img.clientWidth;
        const realX = Math.round(x * ratio);
        const realY = Math.round(y * ratio);

        // Atualizar dataset para persistência visual se redimensionar
        currentMarker.dataset.x = realX;
        currentMarker.dataset.y = realY;

        updateSidebarInfo(currentMarker, realX, realY);
    }

    function stopDrag() {
        if (currentMarker) {
            currentMarker.classList.remove('dragging');
        }
        isDragging = false;
        currentMarker = null;
        document.removeEventListener('mousemove', drag);
        document.removeEventListener('mouseup', stopDrag);
    }

    function updateSidebarInfo(marker, x, y) {
        // x e y aqui devem ser True Pixels
        const tipo = marker.dataset.tipo;

        if (tipo === 'customizado') {
            const campoId = marker.dataset.campoId;
            const info = document.getElementById('info-campo-' + campoId);
            if (info) {
                info.textContent = `X: ${Math.round(x)} | Y: ${Math.round(y)}`;
            }
        } else {
            const campo = marker.dataset.campo;
            const info = document.getElementById('info-' + campo);
            if (info) {
                info.textContent = `X: ${Math.round(x)} | Y: ${Math.round(y)}`;
            }
        }
    }

    function updateMarkerStyle(input) {
        const type = input.dataset.targetType;
        const id = input.dataset.targetId;
        let marker;

        if (type === 'customizado') {
            marker = document.querySelector(`.campo-marker[data-campo-id="${id}"]`);
        } else {
            marker = document.querySelector(`.campo-marker[data-campo="${id}"]`);
        }

        if (!marker) return;

        // Atualizar visualmente (aproximado)
        if (input.classList.contains('input-size')) {
            // Converter pt para px (aproximado 1.33)
            // REMOVIDO A PEDIDO: Não alterar tamanho do marker para facilitar arrasto
            // marker.style.fontSize = (input.value * 1.33) + 'px';
        } else if (input.classList.contains('input-color')) {
            // Ajustar cor do texto para contraste
            marker.style.backgroundColor = 'rgba(255,255,255,0.8)';
            marker.style.color = input.value;
            marker.style.border = '1px solid ' + input.value;
        } else if (input.classList.contains('input-font')) {
            marker.style.fontFamily = input.value;
        }
    }

    // Inicializar estilos dos markers
    function initMarkerStyles() {
        document.querySelectorAll('.style-input').forEach(input => {
            updateMarkerStyle(input);
        });
    }

    function salvarPosicoes() {
        const posicoes = [];
        const img = document.getElementById('templateImage');
        const ratio = img.naturalWidth / img.clientWidth;

        // QR Code settings
        const qrCheckbox = document.getElementById('toggle-qrcode');
        const exibirQrCode = qrCheckbox ? qrCheckbox.checked : false;
        let qrCodeData = null;

        markers.forEach(marker => {
            const left = parseInt(marker.style.left) || 0;
            const top = parseInt(marker.style.top) || 0;

            // Converter para coordenadas reais da imagem
            const realX = Math.round(left * ratio);
            const realY = Math.round(top * ratio);

            const tipo = marker.dataset.tipo;

            // Coletar estilos dos inputs
            let fontSize, color, fontFamily;

            if (tipo === 'customizado') {
                const id = marker.dataset.campoId;
                const inputSize = document.querySelector(`.input-size[data-target-id="${id}"]`);
                if (inputSize) {
                    fontSize = inputSize.value;
                    color = document.querySelector(`.input-color[data-target-id="${id}"]`).value;
                    fontFamily = document.querySelector(`.input-font[data-target-id="${id}"]`).value;
                }

                posicoes.push({
                    tipo: 'customizado',
                    id: id,
                    x: realX,
                    y: realY,
                    fontSize: fontSize,
                    color: color,
                    fontFamily: fontFamily
                });
            } else {
                const campo = marker.dataset.campo;
                const inputSize = document.querySelector(`.input-size[data-target-id="${campo}"]`);

                // Se for QR Code, tratar diferente
                if (campo === 'qrcode') {
                    qrCodeData = {
                        exibir: exibirQrCode,
                        x: realX,
                        y: realY,
                        tamanho: inputSize ? inputSize.value : 100
                    };
                    return; // Não adiciona ao array de posições padrão
                }

                if (inputSize) {
                    fontSize = inputSize.value;
                    color = document.querySelector(`.input-color[data-target-id="${campo}"]`).value;
                    fontFamily = document.querySelector(`.input-font[data-target-id="${campo}"]`).value;
                }

                posicoes.push({
                    tipo: 'padrao',
                    campo: campo,
                    x: realX,
                    y: realY,
                    fontSize: fontSize,
                    color: color,
                    fontFamily: fontFamily
                });
            }
        });

        // Enviar para salvar
        fetch('../app/actions/salvar-posicoes-template.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                template_id: <?php echo $template_id; ?>,
                posicoes: posicoes,
                qrcode: qrCodeData
            })
        })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('✓ Posições salvas com sucesso!');
                    location.reload();
                } else {
                    alert('✗ Erro ao salvar: ' + data.error);
                }
            })
            .catch(error => {
                alert('✗ Erro de comunicação: ' + error);
            });
    }

    function toggleQrCode(checkbox) {
        const marker = document.querySelector('.campo-marker[data-campo="qrcode"]');
        const controls = document.getElementById('qrcode-controls');

        if (checkbox.checked) {
            marker.style.display = 'block';
            controls.style.display = 'block';
            // Se estiver em 0,0, move para o centro
            if (parseInt(marker.dataset.x) === 0 && parseInt(marker.dataset.y) === 0) {
                marker.style.left = '50%';
                marker.style.top = '50%';
            }
        } else {
            marker.style.display = 'none';
            controls.style.display = 'none';
        }
    }
</script>

<?php require_once '../app/views/admin-layout-footer.php'; ?>
<?php $conn->close(); ?>