<?php
/**
 * Gerenciamento de Credenciais de Alunos
 * Sistema EAD Pro
 */

// Habilitar exibição de erros para debug
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once '../config/database.php';
require_once '../app/models/Aluno.php';

// Verificar autenticação
verificar_autenticacao('parceiro');

$parceiro_id = $_SESSION['ead_parceiro_id'];
$aluno_model = new Aluno($pdo);

$mensagem = '';
$tipo_mensagem = '';
$alunos = [];

// Obter todos os alunos inscritos nos cursos do parceiro
$stmt = $pdo->prepare('
    SELECT DISTINCT a.id, a.nome, a.email, a.ativo, a.criado_em as data_criacao
    FROM alunos a
    INNER JOIN inscricoes_alunos ia ON a.id = ia.aluno_id
    INNER JOIN cursos c ON ia.curso_id = c.id
    WHERE c.parceiro_id = ?
    ORDER BY a.ativo DESC, a.nome
');
$stmt->execute([$parceiro_id]);
$alunos = $stmt->fetchAll();

// Processar ações
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $acao = $_POST['acao'] ?? '';
        $aluno_id = (int)($_POST['aluno_id'] ?? 0);

        if ($acao === 'criar_credencial' || $acao === 'editar_credencial') {
            $email = trim($_POST['email'] ?? '');
            $senha = $_POST['senha'] ?? '';
            $confirma_senha = $_POST['confirma_senha'] ?? '';

        // Validações
        if (empty($email)) {
            $_SESSION['mensagem'] = 'Email é obrigatório';
            $_SESSION['tipo_mensagem'] = 'danger';
            header('Location: credenciais-alunos.php');
            exit;
        } elseif (!empty($senha) && $senha !== $confirma_senha) {
            $_SESSION['mensagem'] = 'As senhas não conferem';
            $_SESSION['tipo_mensagem'] = 'danger';
            header('Location: credenciais-alunos.php');
            exit;
        } elseif (!empty($senha) && strlen($senha) < 6) {
            $_SESSION['mensagem'] = 'A senha deve ter no mínimo 6 caracteres';
            $_SESSION['tipo_mensagem'] = 'danger';
            header('Location: credenciais-alunos.php');
            exit;
        } else {
            // Verificar se email já existe
            $stmt = $pdo->prepare('SELECT id FROM alunos WHERE email = ? AND id != ?');
            $stmt->execute([$email, $aluno_id]);

            if ($stmt->fetch()) {
                $_SESSION['mensagem'] = 'Este email já está em uso';
                $_SESSION['tipo_mensagem'] = 'danger';
                header('Location: credenciais-alunos.php');
                exit;
            } else {
                // Preparar dados para atualizar
                if (!empty($senha)) {
                    $senha_hash = password_hash($senha, PASSWORD_BCRYPT, ['cost' => 12]);
                    $stmt = $pdo->prepare('
                        UPDATE alunos
                        SET email = ?, senha_hash = ?, ativo = 1
                        WHERE id = ?
                    ');
                    $stmt->execute([$email, $senha_hash, $aluno_id]);
                } else {
                    $stmt = $pdo->prepare('
                        UPDATE alunos
                        SET email = ?, ativo = 1
                        WHERE id = ?
                    ');
                    $stmt->execute([$email, $aluno_id]);
                }

                $acao_msg = ($acao === 'criar_credencial') ? 'criadas' : 'atualizadas';
                $_SESSION['mensagem'] = "Credenciais $acao_msg com sucesso! O aluno pode fazer login agora.";
                $_SESSION['tipo_mensagem'] = 'success';

                // Redirect para evitar reenvio do formulário
                header('Location: credenciais-alunos.php');
                exit;
            }
        }
    } elseif ($acao === 'deletar_credencial') {
        // Deletar credenciais (desativar aluno)
        $stmt = $pdo->prepare('
            UPDATE alunos
            SET ativo = 0, email = NULL, senha_hash = NULL
            WHERE id = ?
        ');

        if ($stmt->execute([$aluno_id])) {
            $_SESSION['mensagem'] = 'Credenciais deletadas com sucesso!';
            $_SESSION['tipo_mensagem'] = 'success';
        } else {
            $_SESSION['mensagem'] = 'Erro ao deletar credenciais';
            $_SESSION['tipo_mensagem'] = 'danger';
        }

        // Redirect para evitar reenvio do formulário
        header('Location: credenciais-alunos.php');
        exit;
    }
    } catch (Exception $e) {
        // Log do erro
        error_log("Erro em credenciais-alunos.php: " . $e->getMessage());
        $_SESSION['mensagem'] = 'Erro ao processar ação: ' . $e->getMessage();
        $_SESSION['tipo_mensagem'] = 'danger';
        header('Location: credenciais-alunos.php');
        exit;
    }
}

// Verificar se há mensagem na sessão
if (isset($_SESSION['mensagem'])) {
    $mensagem = $_SESSION['mensagem'];
    $tipo_mensagem = $_SESSION['tipo_mensagem'];
    unset($_SESSION['mensagem']);
    unset($_SESSION['tipo_mensagem']);
}

$titulo_pagina = 'Credenciais de Alunos';
require_once '../includes/ead-layout-header.php';
?>

<!-- Cabeçalho -->
<div style="background: linear-gradient(135deg, rgba(110, 65, 193, 0.08) 0%, rgba(139, 95, 214, 0.04) 100%); border-radius: 16px; padding: 28px 32px; margin-bottom: 28px;">
    <div style="display: flex; align-items: center; justify-content: space-between;">
        <div style="display: flex; align-items: center; gap: 16px;">
            <div style="width: 56px; height: 56px; background: linear-gradient(135deg, #6E41C1 0%, #8B5FD6 100%); border-radius: 14px; display: flex; align-items: center; justify-content: center; box-shadow: 0 4px 12px rgba(110, 65, 193, 0.3);">
                <span class="material-icons-outlined" style="font-size: 28px; color: white;">vpn_key</span>
            </div>
            <div>
                <h1 style="font-size: 28px; font-weight: 700; color: #1D1D1F; margin: 0; line-height: 1.2;">Credenciais de Alunos</h1>
                <p style="color: #86868B; font-size: 14px; margin: 4px 0 0 0;">Crie e gerencie credenciais de acesso para seus alunos</p>
            </div>
        </div>
        <a href="../aluno/login-aluno.php" class="button button-primary" style="text-decoration: none;" target="_blank">
            <span class="material-icons-outlined">login</span>
            <span>Login do Aluno</span>
        </a>
    </div>
</div>

<!-- Cards de Estatísticas -->
<div class="stats-grid" style="grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); margin-bottom: 28px;">
    <div class="stat-card">
        <span class="material-icons-outlined">people</span>
        <div class="stat-label">Total de Alunos</div>
        <div class="stat-value"><?php echo count($alunos); ?></div>
        <div class="stat-change">Inscritos nos cursos</div>
    </div>
    <div class="stat-card">
        <span class="material-icons-outlined">check_circle</span>
        <div class="stat-label">Com Credenciais</div>
        <div class="stat-value"><?php echo count(array_filter($alunos, fn($a) => $a['ativo'])); ?></div>
        <div class="stat-change">Podem acessar</div>
    </div>
    <div class="stat-card">
        <span class="material-icons-outlined">lock</span>
        <div class="stat-label">Sem Acesso</div>
        <div class="stat-value"><?php echo count(array_filter($alunos, fn($a) => !$a['ativo'])); ?></div>
        <div class="stat-change">Aguardando credenciais</div>
    </div>
</div>

<!-- Mensagem -->
<?php if ($mensagem): ?>
    <div class="alert alert-<?php echo $tipo_mensagem === 'success' ? 'success' : 'danger'; ?>" style="margin-bottom: 24px;">
        <div style="display: flex; align-items: start; gap: 12px;">
            <span class="material-icons-outlined" style="color: <?php echo $tipo_mensagem === 'success' ? '#34C759' : '#FF3B30'; ?>; flex-shrink: 0;">
                <?php echo $tipo_mensagem === 'success' ? 'check_circle' : 'error'; ?>
            </span>
            <span><?php echo htmlspecialchars($mensagem); ?></span>
        </div>
    </div>
<?php endif; ?>

<!-- Lista de Alunos -->
<?php if (empty($alunos)): ?>
    <div class="card">
        <div style="text-align: center; padding: 48px 24px; color: #86868B;">
            <span class="material-icons-outlined" style="font-size: 64px; color: #E5E5E7; margin-bottom: 16px;">people</span>
            <p style="font-size: 16px; margin: 0 0 8px 0; color: #1D1D1F; font-weight: 600;">Nenhum aluno inscrito</p>
            <p style="font-size: 14px; margin: 0;">Alunos inscritos nos seus cursos aparecerão aqui</p>
        </div>
    </div>
<?php else: ?>
    <div class="card">
        <h2>
            <span class="material-icons-outlined">people</span>
            Gerenciar Credenciais (<?php echo count($alunos); ?>)
        </h2>

        <div style="display: grid; gap: 16px;">
            <?php foreach ($alunos as $aluno): ?>
                <div style="display: flex; align-items: center; gap: 20px; padding: 20px; background: white; border: 2px solid #E5E5E7; border-radius: 12px; transition: all 0.2s ease;"
                     onmouseover="this.style.borderColor='#6E41C1'; this.style.boxShadow='0 4px 12px rgba(110, 65, 193, 0.1)';"
                     onmouseout="this.style.borderColor='#E5E5E7'; this.style.boxShadow='none';">

                    <!-- Avatar -->
                    <div style="width: 48px; height: 48px; background: linear-gradient(135deg, #6E41C1 0%, #8B5FD6 100%); border-radius: 12px; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                        <span class="material-icons-outlined" style="font-size: 24px; color: white;">person</span>
                    </div>

                    <!-- Informações -->
                    <div style="flex: 1; min-width: 0;">
                        <h3 style="font-size: 16px; font-weight: 600; color: #1D1D1F; margin: 0 0 8px 0;">
                            <?php echo htmlspecialchars($aluno['nome']); ?>
                        </h3>
                        <div style="display: flex; gap: 16px; flex-wrap: wrap; align-items: center;">
                            <div style="display: flex; align-items: center; gap: 6px; color: #86868B; font-size: 13px;">
                                <span class="material-icons-outlined" style="font-size: 16px;">email</span>
                                <?php if ($aluno['ativo'] && $aluno['email']): ?>
                                    <code style="background: rgba(110, 65, 193, 0.1); color: #6E41C1; padding: 2px 8px; border-radius: 4px; font-size: 12px;">
                                        <?php echo htmlspecialchars($aluno['email']); ?>
                                    </code>
                                <?php else: ?>
                                    <span style="color: #86868B;">Não definido</span>
                                <?php endif; ?>
                            </div>
                            <div style="display: flex; align-items: center; gap: 6px; color: #86868B; font-size: 13px;">
                                <span class="material-icons-outlined" style="font-size: 16px;">calendar_today</span>
                                <span><?php echo date('d/m/Y', strtotime($aluno['data_criacao'])); ?></span>
                            </div>
                            <div>
                                <?php if ($aluno['ativo']): ?>
                                    <span class="badge" style="background: rgba(52, 199, 89, 0.1); color: #34C759; padding: 4px 10px; border-radius: 6px; font-size: 12px; font-weight: 600;">
                                        <span class="material-icons-outlined" style="font-size: 14px; vertical-align: middle;">check_circle</span>
                                        Ativo
                                    </span>
                                <?php else: ?>
                                    <span class="badge" style="background: rgba(255, 149, 0, 0.1); color: #FF9500; padding: 4px 10px; border-radius: 6px; font-size: 12px; font-weight: 600;">
                                        <span class="material-icons-outlined" style="font-size: 14px; vertical-align: middle;">lock</span>
                                        Sem Acesso
                                    </span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <!-- Ações -->
                    <div style="display: flex; gap: 8px; flex-shrink: 0;">
                        <?php if ($aluno['ativo']): ?>
                            <!-- Aluno com credenciais -->
                            <button class="button button-secondary" style="padding: 10px 16px; font-size: 13px;"
                                    data-toggle="modal" data-target="#modalCredencial"
                                    onclick="preencherAlunoEditar(<?php echo $aluno['id']; ?>, '<?php echo htmlspecialchars(addslashes($aluno['nome'])); ?>', '<?php echo htmlspecialchars(addslashes($aluno['email'])); ?>')"
                                    title="Visualizar">
                                <span class="material-icons-outlined" style="font-size: 18px;">visibility</span>
                            </button>
                            <button class="button button-secondary" style="padding: 10px 16px; font-size: 13px;"
                                    data-toggle="modal" data-target="#modalCredencial"
                                    onclick="preencherAlunoEditar(<?php echo $aluno['id']; ?>, '<?php echo htmlspecialchars(addslashes($aluno['nome'])); ?>', '<?php echo htmlspecialchars(addslashes($aluno['email'])); ?>', true)"
                                    title="Editar">
                                <span class="material-icons-outlined" style="font-size: 18px;">edit</span>
                            </button>
                            <form method="POST" style="display: inline;"
                                  onsubmit="return confirm('Tem certeza que deseja deletar as credenciais deste aluno?\n\nO aluno perderá o acesso ao sistema.');">
                                <input type="hidden" name="acao" value="deletar_credencial">
                                <input type="hidden" name="aluno_id" value="<?php echo $aluno['id']; ?>">
                                <button type="submit" class="button button-danger" style="padding: 10px 16px; font-size: 13px;" title="Deletar">
                                    <span class="material-icons-outlined" style="font-size: 18px;">delete</span>
                                </button>
                            </form>
                        <?php else: ?>
                            <!-- Aluno sem credenciais -->
                            <button class="button button-primary" style="padding: 10px 16px; font-size: 13px;"
                                    data-toggle="modal" data-target="#modalCredencial"
                                    onclick="preencherAluno(<?php echo $aluno['id']; ?>, '<?php echo htmlspecialchars(addslashes($aluno['nome'])); ?>')"
                                    title="Criar Credencial">
                                <span class="material-icons-outlined" style="font-size: 18px;">vpn_key</span>
                                <span>Criar Credencial</span>
                            </button>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
<?php endif; ?>

<!-- Modal de Credencial -->
<div class="modal fade" id="modalCredencial" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-dialog-centered" role="document" style="max-width: 500px;">
        <div class="modal-content" style="border: none; border-radius: 16px; overflow: hidden;">
            <!-- Header -->
            <div style="background: linear-gradient(135deg, #6E41C1 0%, #8B5FD6 100%); padding: 24px 28px;">
                <div style="display: flex; align-items: center; justify-content: space-between;">
                    <div style="display: flex; align-items: center; gap: 12px;">
                        <span class="material-icons-outlined" style="font-size: 28px; color: white;">vpn_key</span>
                        <h5 style="color: white; margin: 0; font-size: 18px; font-weight: 600;" id="modalTitulo">Criar Credencial de Acesso</h5>
                    </div>
                    <button type="button" class="close" data-dismiss="modal" style="color: white; opacity: 1; text-shadow: none; font-size: 28px; font-weight: 300; margin: 0; padding: 0;">
                        <span>&times;</span>
                    </button>
                </div>
            </div>

            <form method="POST">
                <div style="padding: 28px;">
                    <input type="hidden" name="acao" id="acao" value="criar_credencial">
                    <input type="hidden" name="aluno_id" id="aluno_id" value="">

                    <!-- Aluno -->
                    <div style="margin-bottom: 20px;">
                        <label style="display: block; font-size: 13px; font-weight: 600; color: #1D1D1F; margin-bottom: 8px;">
                            <span class="material-icons-outlined" style="font-size: 16px; vertical-align: middle; color: #6E41C1;">person</span>
                            Aluno
                        </label>
                        <input type="text" id="aluno_nome" disabled
                               style="width: 100%; padding: 12px 16px; border: 2px solid #E5E5E7; border-radius: 10px; font-size: 14px; background: #F5F5F7; color: #86868B;">
                    </div>

                    <!-- Email -->
                    <div style="margin-bottom: 20px;">
                        <label for="email" style="display: block; font-size: 13px; font-weight: 600; color: #1D1D1F; margin-bottom: 8px;">
                            <span class="material-icons-outlined" style="font-size: 16px; vertical-align: middle; color: #6E41C1;">email</span>
                            Email para Login
                        </label>
                        <input type="email" id="email" name="email" required
                               style="width: 100%; padding: 12px 16px; border: 2px solid #E5E5E7; border-radius: 10px; font-size: 14px; transition: all 0.2s ease;"
                               onfocus="this.style.borderColor='#6E41C1'; this.style.boxShadow='0 0 0 3px rgba(110, 65, 193, 0.1)';"
                               onblur="this.style.borderColor='#E5E5E7'; this.style.boxShadow='none';">
                        <small style="display: block; margin-top: 6px; font-size: 12px; color: #86868B;">Este será o email usado para fazer login</small>
                    </div>

                    <!-- Senha -->
                    <div style="margin-bottom: 20px;">
                        <label for="senha" style="display: block; font-size: 13px; font-weight: 600; color: #1D1D1F; margin-bottom: 8px;">
                            <span class="material-icons-outlined" style="font-size: 16px; vertical-align: middle; color: #6E41C1;">lock</span>
                            Senha
                        </label>
                        <div style="position: relative;">
                            <input type="password" id="senha" name="senha"
                                   style="width: 100%; padding: 12px 48px 12px 16px; border: 2px solid #E5E5E7; border-radius: 10px; font-size: 14px; transition: all 0.2s ease;"
                                   onfocus="this.style.borderColor='#6E41C1'; this.style.boxShadow='0 0 0 3px rgba(110, 65, 193, 0.1)';"
                                   onblur="this.style.borderColor='#E5E5E7'; this.style.boxShadow='none';">
                            <button type="button" onclick="toggleSenha()"
                                    style="position: absolute; right: 12px; top: 50%; transform: translateY(-50%); background: none; border: none; padding: 8px; cursor: pointer; color: #86868B;">
                                <span class="material-icons-outlined" id="iconSenha" style="font-size: 20px;">visibility</span>
                            </button>
                        </div>
                        <small style="display: block; margin-top: 6px; font-size: 12px; color: #86868B;">Deixe em branco para manter a senha atual (apenas ao editar)</small>
                    </div>

                    <!-- Confirmar Senha -->
                    <div style="margin-bottom: 20px;">
                        <label for="confirma_senha" style="display: block; font-size: 13px; font-weight: 600; color: #1D1D1F; margin-bottom: 8px;">
                            <span class="material-icons-outlined" style="font-size: 16px; vertical-align: middle; color: #6E41C1;">lock</span>
                            Confirmar Senha
                        </label>
                        <div style="position: relative;">
                            <input type="password" id="confirma_senha" name="confirma_senha"
                                   style="width: 100%; padding: 12px 48px 12px 16px; border: 2px solid #E5E5E7; border-radius: 10px; font-size: 14px; transition: all 0.2s ease;"
                                   onfocus="this.style.borderColor='#6E41C1'; this.style.boxShadow='0 0 0 3px rgba(110, 65, 193, 0.1)';"
                                   onblur="this.style.borderColor='#E5E5E7'; this.style.boxShadow='none';">
                            <button type="button" onclick="toggleConfirmaSenha()"
                                    style="position: absolute; right: 12px; top: 50%; transform: translateY(-50%); background: none; border: none; padding: 8px; cursor: pointer; color: #86868B;">
                                <span class="material-icons-outlined" id="iconConfirma" style="font-size: 20px;">visibility</span>
                            </button>
                        </div>
                    </div>

                    <!-- Alert Info -->
                    <div style="background: rgba(110, 65, 193, 0.08); border-left: 4px solid #6E41C1; padding: 16px; border-radius: 8px; margin-bottom: 0;">
                        <div style="display: flex; gap: 12px;">
                            <span class="material-icons-outlined" style="color: #6E41C1; font-size: 20px; flex-shrink: 0;">info</span>
                            <div style="font-size: 13px; color: #1D1D1F; line-height: 1.5;">
                                <strong style="display: block; margin-bottom: 4px;">Importante:</strong>
                                Compartilhe essas credenciais com o aluno para que ele possa acessar o sistema.
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Footer -->
                <div style="padding: 20px 28px; background: #F5F5F7; border-top: 1px solid #E5E5E7; display: flex; gap: 12px; justify-content: flex-end;">
                    <button type="button" class="button button-secondary" data-dismiss="modal" style="padding: 12px 24px;">
                        Cancelar
                    </button>
                    <button type="submit" class="button button-primary" id="btnSubmit" style="padding: 12px 24px;">
                        <span class="material-icons-outlined" style="font-size: 18px;">save</span>
                        <span id="btnSubmitText">Criar Credencial</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function toggleSenha() {
    const campo = document.getElementById('senha');
    const icon = document.getElementById('iconSenha');

    if (campo.type === 'password') {
        campo.type = 'text';
        icon.textContent = 'visibility_off';
    } else {
        campo.type = 'password';
        icon.textContent = 'visibility';
    }
}

function toggleConfirmaSenha() {
    const campo = document.getElementById('confirma_senha');
    const icon = document.getElementById('iconConfirma');

    if (campo.type === 'password') {
        campo.type = 'text';
        icon.textContent = 'visibility_off';
    } else {
        campo.type = 'password';
        icon.textContent = 'visibility';
    }
}

function preencherAluno(id, nome) {
    document.getElementById('acao').value = 'criar_credencial';
    document.getElementById('aluno_id').value = id;
    document.getElementById('aluno_nome').value = nome;
    document.getElementById('email').value = '';
    document.getElementById('senha').value = '';
    document.getElementById('confirma_senha').value = '';
    document.getElementById('senha').required = true;
    document.getElementById('confirma_senha').required = true;
    document.getElementById('email').disabled = false;
    document.getElementById('senha').disabled = false;
    document.getElementById('confirma_senha').disabled = false;
    document.getElementById('modalTitulo').innerHTML = '<span class="material-icons-outlined" style="font-size: 28px; color: white; vertical-align: middle; margin-right: 8px;">vpn_key</span>Criar Credencial de Acesso';
    document.getElementById('btnSubmitText').textContent = 'Criar Credencial';
    document.getElementById('btnSubmit').disabled = false;
}

function preencherAlunoEditar(id, nome, email, editar = false) {
    // Resetar todos os campos primeiro
    document.getElementById('aluno_id').value = id;
    document.getElementById('aluno_nome').value = nome;
    document.getElementById('email').value = email;
    document.getElementById('senha').value = '';
    document.getElementById('confirma_senha').value = '';

    // Sempre habilitar os campos primeiro
    document.getElementById('email').disabled = false;
    document.getElementById('email').readOnly = false;
    document.getElementById('senha').disabled = false;
    document.getElementById('senha').readOnly = false;
    document.getElementById('confirma_senha').disabled = false;
    document.getElementById('confirma_senha').readOnly = false;

    // Remover required dos campos de senha
    document.getElementById('senha').required = false;
    document.getElementById('confirma_senha').required = false;

    if (editar) {
        // Modo EDITAR - campos habilitados para edição
        document.getElementById('acao').value = 'editar_credencial';
        document.getElementById('modalTitulo').innerHTML = '<span class="material-icons-outlined" style="font-size: 28px; color: white; vertical-align: middle; margin-right: 8px;">edit</span>Editar Credencial de Acesso';
        document.getElementById('btnSubmitText').textContent = 'Atualizar Credencial';
        document.getElementById('btnSubmit').disabled = false;
        document.getElementById('btnSubmit').type = 'submit';
    } else {
        // Modo VISUALIZAR - campos desabilitados
        document.getElementById('acao').value = '';
        document.getElementById('modalTitulo').innerHTML = '<span class="material-icons-outlined" style="font-size: 28px; color: white; vertical-align: middle; margin-right: 8px;">visibility</span>Visualizar Credencial de Acesso';
        document.getElementById('btnSubmitText').textContent = 'Fechar';
        document.getElementById('btnSubmit').type = 'button';
        document.getElementById('btnSubmit').onclick = function() {
            $('#modalCredencial').modal('hide');
        };

        // Desabilitar campos no modo visualizar
        document.getElementById('email').readOnly = true;
        document.getElementById('senha').readOnly = true;
        document.getElementById('confirma_senha').readOnly = true;
    }

    // Abrir modal
    $('#modalCredencial').modal('show');
}
</script>

<?php
require_once '../includes/ead-layout-footer.php';
?>

