<?php
/**
 * Página de Importar Alunos
 * Sistema EAD Pro
 */

require_once '../config/database.php';
require_once '../app/models/Curso.php';

// Verificar autenticação
verificar_autenticacao('parceiro');

$parceiro_id = $_SESSION['ead_parceiro_id'];
$curso_model = new Curso($pdo);
$curso_id = (int)($_GET['curso_id'] ?? $_POST['curso_id'] ?? 0);
$erros = [];
$sucessos = [];
$curso = null;

// Obter cursos do parceiro
$cursos = $curso_model->obter_por_parceiro($parceiro_id);

if ($curso_id > 0) {
    // Obter curso
    $curso = $curso_model->obter_por_id($curso_id);

    if (!$curso) {
        $_SESSION['mensagem'] = 'Curso não encontrado!';
        $_SESSION['tipo_mensagem'] = 'danger';
        header('Location: cursos.php');
        exit;
    }

    // Verificar se o curso pertence ao parceiro
    if ($curso['parceiro_id'] != $parceiro_id) {
        $_SESSION['mensagem'] = 'Acesso negado!';
        $_SESSION['tipo_mensagem'] = 'danger';
        header('Location: cursos.php');
        exit;
    }
}

// Processar upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['arquivo'])) {
    $arquivo = $_FILES['arquivo'];
    
    // Validar arquivo
    if ($arquivo['error'] !== UPLOAD_ERR_OK) {
        $erros[] = 'Erro ao fazer upload do arquivo';
    } elseif (!in_array($arquivo['type'], ['text/csv', 'text/plain', 'application/vnd.ms-excel'])) {
        $erros[] = 'Formato de arquivo inválido. Use CSV';
    } else {
        // Ler arquivo
        $handle = fopen($arquivo['tmp_name'], 'r');
        $linha = 0;
        $alunos_adicionados = 0;
        
        while (($dados = fgetcsv($handle, 1000, ',')) !== false) {
            $linha++;
            
            // Pular cabeçalho
            if ($linha === 1) continue;
            
            // Validar dados
            if (count($dados) < 2) {
                $erros[] = "Linha $linha: Dados incompletos";
                continue;
            }
            
            $nome = sanitizar($dados[0]);
            $email = sanitizar($dados[1]);
            
            if (empty($nome) || empty($email)) {
                $erros[] = "Linha $linha: Nome ou email vazio";
                continue;
            }
            
            if (!validar_email($email)) {
                $erros[] = "Linha $linha: Email inválido";
                continue;
            }
            
            // Verificar se aluno existe
            $stmt = $pdo->prepare('SELECT id FROM alunos WHERE email = ?');
            $stmt->execute([$email]);
            $aluno_existente = $stmt->fetch();
            
            if ($aluno_existente) {
                $aluno_id = $aluno_existente['id'];
            } else {
                // Criar novo aluno
                try {
                    $stmt = $pdo->prepare('
                        INSERT INTO alunos (nome, email, ativo)
                        VALUES (?, ?, TRUE)
                    ');
                    $stmt->execute([$nome, $email]);
                    $aluno_id = $pdo->lastInsertId();
                } catch (Exception $e) {
                    $erros[] = "Linha $linha: Erro ao criar aluno";
                    continue;
                }
            }
            
            // Inscrever aluno no curso
            try {
                $stmt = $pdo->prepare('
                    INSERT INTO inscricoes (aluno_id, curso_id, status, progresso)
                    VALUES (?, ?, "ativo", 0)
                    ON DUPLICATE KEY UPDATE status = "ativo"
                ');
                $stmt->execute([$aluno_id, $curso_id]);
                $alunos_adicionados++;
            } catch (Exception $e) {
                // Aluno já inscrito
            }
        }
        
        fclose($handle);
        
        if ($alunos_adicionados > 0) {
            $sucessos[] = "$alunos_adicionados aluno(s) importado(s) com sucesso!";
        }
    }
}

$titulo_pagina = 'Importar Alunos';
require_once '../includes/ead-layout-header.php';
?>

<!-- Cabeçalho -->
<div style="background: linear-gradient(135deg, rgba(110, 65, 193, 0.08) 0%, rgba(139, 95, 214, 0.04) 100%); border-radius: 16px; padding: 28px 32px; margin-bottom: 28px;">
    <div style="display: flex; align-items: center; justify-content: space-between;">
        <div style="display: flex; align-items: center; gap: 16px;">
            <div style="width: 56px; height: 56px; background: linear-gradient(135deg, #6E41C1 0%, #8B5FD6 100%); border-radius: 14px; display: flex; align-items: center; justify-content: center; box-shadow: 0 4px 12px rgba(110, 65, 193, 0.3);">
                <span class="material-icons-outlined" style="font-size: 28px; color: white;">upload_file</span>
            </div>
            <div>
                <h1 style="font-size: 28px; font-weight: 700; color: #1D1D1F; margin: 0; line-height: 1.2;">Importar Alunos</h1>
                <p style="color: #86868B; font-size: 14px; margin: 4px 0 0 0;">Importe alunos em lote usando arquivo CSV</p>
            </div>
        </div>
        <?php if ($curso): ?>
            <a href="alunos.php?curso_id=<?php echo $curso_id; ?>" class="button button-secondary" style="text-decoration: none;">
                <span class="material-icons-outlined">arrow_back</span>
                <span>Voltar</span>
            </a>
        <?php endif; ?>
    </div>
</div>

<?php if ($curso): ?>
    <!-- Curso Selecionado -->
    <div class="card" style="margin-bottom: 24px;">
        <div style="display: flex; align-items: center; gap: 12px; padding: 16px; background: linear-gradient(135deg, rgba(110, 65, 193, 0.05) 0%, rgba(139, 95, 214, 0.02) 100%); border-radius: 12px;">
            <span class="material-icons-outlined" style="color: #6E41C1; font-size: 32px;">book</span>
            <div>
                <div style="font-size: 13px; color: #86868B; margin-bottom: 2px;">Curso Selecionado</div>
                <div style="font-size: 16px; font-weight: 600; color: #1D1D1F;"><?php echo htmlspecialchars($curso['nome']); ?></div>
                <div style="font-size: 13px; color: #86868B; margin-top: 2px;">Os alunos importados serão inscritos neste curso</div>
            </div>
        </div>
    </div>
<?php else: ?>
    <!-- Seleção de Curso -->
    <?php if (empty($cursos)): ?>
        <div class="card">
            <div style="text-align: center; padding: 48px 24px; color: #86868B;">
                <span class="material-icons-outlined" style="font-size: 64px; color: #E5E5E7; margin-bottom: 16px;">book</span>
                <p style="font-size: 16px; margin: 0 0 8px 0; color: #1D1D1F; font-weight: 600;">Nenhum curso disponível</p>
                <p style="font-size: 14px; margin: 0 0 20px 0;">Crie um curso primeiro para importar alunos</p>
                <a href="criar-curso.php" class="button button-primary" style="text-decoration: none;">
                    <span class="material-icons-outlined">add</span>
                    <span>Criar Curso</span>
                </a>
            </div>
        </div>
    <?php else: ?>
        <div class="card">
            <h2>
                <span class="material-icons-outlined">book</span>
                Selecione um Curso
            </h2>

            <div style="display: grid; gap: 16px;">
                <?php foreach ($cursos as $c): ?>
                    <div style="display: flex; align-items: center; justify-content: space-between; padding: 20px; background: #F5F5F7; border-radius: 12px; border: 2px solid #E5E5E7; transition: all 0.2s ease;"
                         onmouseover="this.style.borderColor='#6E41C1'; this.style.background='rgba(110, 65, 193, 0.05)';"
                         onmouseout="this.style.borderColor='#E5E5E7'; this.style.background='#F5F5F7';">
                        <div style="flex: 1;">
                            <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 8px;">
                                <span class="material-icons-outlined" style="color: #6E41C1; font-size: 24px;">book</span>
                                <h3 style="font-size: 16px; font-weight: 600; color: #1D1D1F; margin: 0;">
                                    <?php echo htmlspecialchars($c['nome']); ?>
                                </h3>
                            </div>
                            <?php if ($c['descricao']): ?>
                                <p style="color: #86868B; font-size: 13px; margin: 0 0 0 36px; line-height: 1.4;">
                                    <?php echo htmlspecialchars(substr($c['descricao'], 0, 100)) . (strlen($c['descricao']) > 100 ? '...' : ''); ?>
                                </p>
                            <?php endif; ?>
                        </div>
                        <a href="alunos-importar.php?curso_id=<?php echo $c['id']; ?>" class="button button-primary" style="text-decoration: none;">
                            <span class="material-icons-outlined">upload</span>
                            <span>Importar Alunos</span>
                        </a>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>

    <!-- Mensagens -->
    <?php if (!empty($sucessos)): ?>
        <div class="alert alert-success" style="margin-bottom: 24px;">
            <div style="display: flex; align-items: start; gap: 12px;">
                <span class="material-icons-outlined" style="color: #34C759; flex-shrink: 0;">check_circle</span>
                <div>
                    <?php foreach ($sucessos as $sucesso): ?>
                        <p style="margin: 0 0 4px 0;"><?php echo $sucesso; ?></p>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <?php if (!empty($erros)): ?>
        <div class="alert alert-warning" style="margin-bottom: 24px;">
            <div style="display: flex; align-items: start; gap: 12px;">
                <span class="material-icons-outlined" style="color: #FF9500; flex-shrink: 0;">warning</span>
                <div>
                    <strong style="display: block; margin-bottom: 8px;">Avisos:</strong>
                    <ul style="margin: 0; padding-left: 20px;">
                        <?php foreach ($erros as $erro): ?>
                            <li><?php echo $erro; ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <!-- Formulário -->
    <div class="card" style="margin-bottom: 24px;">
        <h2>
            <span class="material-icons-outlined">upload_file</span>
            Importar de Arquivo CSV
        </h2>
        <form method="POST" enctype="multipart/form-data">
            <input type="hidden" name="curso_id" value="<?php echo $curso_id; ?>">

            <!-- Upload de Arquivo -->
            <div style="margin-bottom: 24px;">
                <label for="arquivo" style="display: block; font-size: 13px; font-weight: 600; color: #1D1D1F; margin-bottom: 8px;">
                    <span class="material-icons-outlined" style="font-size: 16px; vertical-align: middle; color: #6E41C1;">upload_file</span>
                    Selecione o arquivo CSV <span style="color: #FF3B30;">*</span>
                </label>
                <div style="position: relative;">
                    <input type="file" id="arquivo" name="arquivo" accept=".csv" required
                           style="width: 100%; padding: 12px 16px; border: 2px dashed #E5E5E7; border-radius: 10px; font-size: 14px; cursor: pointer; transition: all 0.2s ease;"
                           onchange="updateFileName(this)"
                           onfocus="this.style.borderColor='#6E41C1'; this.style.background='rgba(110, 65, 193, 0.02)';"
                           onblur="this.style.borderColor='#E5E5E7'; this.style.background='white';">
                </div>
                <small style="display: block; margin-top: 6px; font-size: 12px; color: #86868B;">
                    <span class="material-icons-outlined" style="font-size: 14px; vertical-align: middle;">info</span>
                    Formato: CSV com colunas: Nome, Email
                </small>
            </div>

            <!-- Formato do Arquivo -->
            <div style="background: rgba(255, 149, 0, 0.08); border-left: 4px solid #FF9500; padding: 20px; border-radius: 8px; margin-bottom: 24px;">
                <div style="display: flex; gap: 12px; margin-bottom: 16px;">
                    <span class="material-icons-outlined" style="color: #FF9500; font-size: 24px; flex-shrink: 0;">info</span>
                    <div>
                        <h3 style="font-size: 15px; font-weight: 600; color: #1D1D1F; margin: 0 0 8px 0;">Formato do Arquivo</h3>
                        <p style="font-size: 13px; color: #86868B; margin: 0 0 16px 0;">O arquivo CSV deve ter as seguintes colunas:</p>
                    </div>
                </div>

                <div style="background: white; border-radius: 8px; overflow: hidden; border: 1px solid #E5E5E7;">
                    <table style="width: 100%; border-collapse: collapse;">
                        <thead>
                            <tr style="background: linear-gradient(135deg, #6E41C1 0%, #8B5FD6 100%);">
                                <th style="padding: 12px 16px; text-align: left; font-size: 13px; font-weight: 600; color: white; border-right: 1px solid rgba(255,255,255,0.1);">
                                    <span class="material-icons-outlined" style="font-size: 16px; vertical-align: middle; margin-right: 6px;">person</span>
                                    NOME
                                </th>
                                <th style="padding: 12px 16px; text-align: left; font-size: 13px; font-weight: 600; color: white;">
                                    <span class="material-icons-outlined" style="font-size: 16px; vertical-align: middle; margin-right: 6px;">email</span>
                                    EMAIL
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr style="border-bottom: 1px solid #E5E5E7;">
                                <td style="padding: 12px 16px; font-size: 13px; color: #1D1D1F; border-right: 1px solid #E5E5E7;">João Silva</td>
                                <td style="padding: 12px 16px; font-size: 13px; color: #1D1D1F;">joao@example.com</td>
                            </tr>
                            <tr>
                                <td style="padding: 12px 16px; font-size: 13px; color: #1D1D1F; border-right: 1px solid #E5E5E7;">Maria Santos</td>
                                <td style="padding: 12px 16px; font-size: 13px; color: #1D1D1F;">maria@example.com</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Botões -->
            <div style="display: flex; gap: 12px; justify-content: flex-end; margin-top: 24px;">
                <a href="alunos.php?curso_id=<?php echo $curso_id; ?>" class="button button-secondary" style="text-decoration: none; padding: 12px 24px;">
                    <span class="material-icons-outlined">close</span>
                    <span>Cancelar</span>
                </a>
                <button type="submit" class="button button-primary" style="padding: 12px 24px;">
                    <span class="material-icons-outlined">upload</span>
                    <span>Importar Alunos</span>
                </button>
            </div>
        </form>
    </div>

    <!-- Download Template -->
    <div class="card">
        <div style="display: flex; align-items: center; justify-content: space-between; padding: 20px; background: linear-gradient(135deg, rgba(52, 199, 89, 0.08) 0%, rgba(52, 199, 89, 0.02) 100%); border-radius: 12px; border: 2px solid rgba(52, 199, 89, 0.2);">
            <div style="display: flex; align-items: center; gap: 16px;">
                <div style="width: 48px; height: 48px; background: linear-gradient(135deg, #34C759 0%, #30D158 100%); border-radius: 12px; display: flex; align-items: center; justify-content: center;">
                    <span class="material-icons-outlined" style="font-size: 24px; color: white;">download</span>
                </div>
                <div>
                    <h3 style="font-size: 16px; font-weight: 600; color: #1D1D1F; margin: 0 0 4px 0;">Template CSV</h3>
                    <p style="font-size: 13px; color: #86868B; margin: 0;">Baixe o template CSV para facilitar a importação</p>
                </div>
            </div>
            <button onclick="downloadTemplate()" class="button button-primary" style="background: linear-gradient(135deg, #34C759 0%, #30D158 100%); padding: 12px 24px;">
                <span class="material-icons-outlined">download</span>
                <span>Baixar Template</span>
            </button>
        </div>
    </div>
<?php endif; ?>

<script>
// Atualizar nome do arquivo
function updateFileName(input) {
    if (input.files && input.files[0]) {
        const fileName = input.files[0].name;
        const fileSize = (input.files[0].size / 1024).toFixed(2);
        console.log('Arquivo selecionado: ' + fileName + ' (' + fileSize + ' KB)');
    }
}

// Download template
function downloadTemplate() {
    var csv = 'Nome,Email\nJoão Silva,joao@example.com\nMaria Santos,maria@example.com';
    var blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
    var url = window.URL.createObjectURL(blob);
    var a = document.createElement('a');
    a.href = url;
    a.download = 'template_alunos.csv';
    document.body.appendChild(a);
    a.click();
    document.body.removeChild(a);
    window.URL.revokeObjectURL(url);
}
</script>

<?php
require_once '../includes/ead-layout-footer.php';
?>