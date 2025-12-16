<?php
/**
 * Página de Gerenciamento de Alunos
 * Sistema EAD Pro
 */

require_once '../config/database.php';
require_once '../app/models/Curso.php';
require_once '../app/models/Aluno.php';

// Verificar autenticação
verificar_autenticacao('parceiro');

$parceiro_id = $_SESSION['ead_parceiro_id'];
$curso_model = new Curso($pdo);
$aluno_model = new Aluno($pdo);

// Obter filtro de curso
$curso_id = (int)($_GET['curso_id'] ?? 0);
$filtro = sanitizar($_GET['filtro'] ?? '');

// Obter cursos do parceiro
$cursos = $curso_model->obter_por_parceiro($parceiro_id);

// Se houver filtro de curso, obter alunos
$alunos = [];
if ($curso_id > 0) {
    // Verificar se o curso pertence ao parceiro
    $curso = $curso_model->obter_por_id($curso_id);
    if ($curso) {
        $stmt = $pdo->prepare('SELECT parceiro_id FROM cursos WHERE id = ?');
        $stmt->execute([$curso_id]);
        $curso_check = $stmt->fetch();
        
        if ($curso_check['parceiro_id'] == $parceiro_id) {
            $alunos = $aluno_model->obter_por_curso($curso_id, $filtro ?: null);
        }
    }
}

// Processar ações
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $acao = sanitizar($_POST['acao'] ?? '');
    
    if ($acao === 'remover') {
        $aluno_id = (int)$_POST['aluno_id'];
        $resultado = $aluno_model->remover_inscricao($aluno_id, $curso_id);
        
        if ($resultado['sucesso']) {
            $_SESSION['mensagem'] = 'Aluno removido do curso com sucesso!';
            $_SESSION['tipo_mensagem'] = 'success';
            header('Location: alunos.php?curso_id=' . $curso_id);
            exit;
        }
    }
}

// Obter mensagem de sessão
$mensagem = $_SESSION['mensagem'] ?? '';
$tipo_mensagem = $_SESSION['tipo_mensagem'] ?? '';
unset($_SESSION['mensagem'], $_SESSION['tipo_mensagem']);

$titulo_pagina = 'Meus Alunos';
require_once '../includes/ead-layout-header.php';
?>

<!-- Cabeçalho Moderno -->
<div style="background: linear-gradient(135deg, rgba(110, 65, 193, 0.08) 0%, rgba(139, 95, 214, 0.04) 100%); border-radius: 16px; padding: 28px 32px; margin-bottom: 28px; box-shadow: 0 2px 8px rgba(0, 0, 0, 0.06);">
    <div style="display: flex; align-items: center; justify-content: space-between;">
        <div style="display: flex; align-items: center; gap: 16px;">
            <div style="width: 56px; height: 56px; background: linear-gradient(135deg, #6E41C1 0%, #8B5FD6 100%); border-radius: 14px; display: flex; align-items: center; justify-content: center; box-shadow: 0 4px 12px rgba(110, 65, 193, 0.3);">
                <span class="material-icons-outlined" style="font-size: 28px; color: white;">group</span>
            </div>
            <div>
                <h1 style="font-size: 28px; font-weight: 700; color: #1D1D1F; margin: 0; line-height: 1.2;">Gerenciar Alunos</h1>
                <p style="color: #86868B; font-size: 14px; margin: 4px 0 0 0;">Gerencie alunos inscritos em seus cursos</p>
            </div>
        </div>
        <a href="credenciais-alunos.php" class="button button-primary" style="text-decoration: none;">
            <span class="material-icons-outlined">vpn_key</span>
            <span>Credenciais</span>
        </a>
    </div>
</div>

<!-- Mensagem de Sucesso/Erro -->
<?php if ($mensagem): ?>
    <div class="alert alert-<?php echo $tipo_mensagem === 'success' ? 'success' : 'danger'; ?>">
        <span class="material-icons-outlined"><?php echo $tipo_mensagem === 'success' ? 'check_circle' : 'error'; ?></span>
        <span><?php echo $mensagem; ?></span>
    </div>
<?php endif; ?>

<!-- Cards de Estatísticas -->
<?php if ($curso_id > 0): ?>
<div class="stats-grid" style="grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); margin-bottom: 28px;">
    <div class="stat-card">
        <span class="material-icons-outlined">group</span>
        <div class="stat-label">Total de Alunos</div>
        <div class="stat-value"><?php echo count($alunos); ?></div>
        <div class="stat-change">Inscritos</div>
    </div>
</div>
<?php endif; ?>

<!-- Seleção de Curso -->
<div class="card" style="margin-bottom: 28px;">
    <h2>
        <span class="material-icons-outlined">menu_book</span>
        Selecione um Curso
    </h2>
    <form method="GET">
        <div class="form-group" style="margin-bottom: 0;">
            <label>Curso</label>
            <select name="curso_id" id="curso_id" class="form-control" onchange="this.form.submit()">
                <option value="">-- Escolha um curso --</option>
                <?php foreach ($cursos as $c): ?>
                    <option value="<?php echo $c['id']; ?>" <?php echo $curso_id === $c['id'] ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($c['nome']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
    </form>
</div>

<?php if ($curso_id > 0): ?>
    <!-- Filtro de Busca -->
    <div class="card" style="margin-bottom: 28px;">
        <h2>
            <span class="material-icons-outlined">search</span>
            Buscar Alunos
        </h2>
        <form method="GET" style="display: flex; gap: 12px; align-items: flex-end;">
            <input type="hidden" name="curso_id" value="<?php echo $curso_id; ?>">
            <div class="form-group" style="flex: 1; margin-bottom: 0;">
                <label>Nome ou Email</label>
                <input type="text" name="filtro" class="form-control"
                       placeholder="Digite o nome ou email..." value="<?php echo htmlspecialchars($filtro); ?>">
            </div>
            <button type="submit" class="button button-primary" style="text-decoration: none;">
                <span class="material-icons-outlined">search</span>
                <span>Buscar</span>
            </button>
            <?php if ($filtro): ?>
            <a href="alunos.php?curso_id=<?php echo $curso_id; ?>" class="button button-secondary" style="text-decoration: none;">
                <span class="material-icons-outlined">close</span>
                <span>Limpar</span>
            </a>
            <?php endif; ?>
        </form>
    </div>

    <!-- Tabela de Alunos -->
    <?php if (empty($alunos)): ?>
        <div class="card">
            <div class="empty-state">
                <span class="material-icons-outlined">group</span>
                <h3>Nenhum aluno encontrado</h3>
                <p>Alunos inscritos aparecerão aqui</p>
            </div>
        </div>
    <?php else: ?>
    <div class="card">
        <h2>
            <span class="material-icons-outlined">group</span>
            Alunos Inscritos (<?php echo count($alunos); ?>)
        </h2>
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>Nome</th>
                        <th>Email</th>
                        <th>Progresso</th>
                        <th>Nota Final</th>
                        <th>Status</th>
                        <th>Data Inscrição</th>
                        <th>Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($alunos as $aluno): ?>
                        <tr>
                            <td><strong><?php echo htmlspecialchars($aluno['nome']); ?></strong></td>
                            <td><?php echo htmlspecialchars($aluno['email']); ?></td>
                            <td>
                                <div style="background: #E5E5E7; border-radius: 10px; height: 20px; overflow: hidden;">
                                    <div style="background: linear-gradient(135deg, #6E41C1 0%, #8B5FD6 100%); height: 100%; width: <?php echo $aluno['progresso']; ?>%; display: flex; align-items: center; justify-content: center; color: white; font-size: 11px; font-weight: 600;">
                                        <?php echo $aluno['progresso']; ?>%
                                    </div>
                                </div>
                            </td>
                            <td><?php echo $aluno['nota_final'] ? number_format($aluno['nota_final'], 1, ',', '.') : '-'; ?></td>
                            <td>
                                <?php
                                $status_badges = [
                                    'ativo' => 'success',
                                    'concluido' => 'info',
                                    'cancelado' => 'danger'
                                ];
                                $badge_class = $status_badges[$aluno['status']] ?? 'info';
                                ?>
                                <span class="badge badge-<?php echo $badge_class; ?>">
                                    <?php echo ucfirst($aluno['status']); ?>
                                </span>
                            </td>
                            <td><?php echo date('d/m/Y', strtotime($aluno['criado_em'] ?? date('Y-m-d'))); ?></td>
                            <td>
                                <div style="display: flex; gap: 8px;">
                                    <a href="aluno-detalhes.php?id=<?php echo $aluno['id']; ?>&curso_id=<?php echo $curso_id; ?>"
                                       class="button button-secondary" style="padding: 6px 12px; font-size: 13px; text-decoration: none;">
                                        <span class="material-icons-outlined" style="font-size: 16px;">visibility</span>
                                    </a>
                                    <form method="POST" style="display:inline;"
                                          onsubmit="return confirm('Tem certeza que deseja remover este aluno?');">
                                        <input type="hidden" name="acao" value="remover">
                                        <input type="hidden" name="aluno_id" value="<?php echo $aluno['id']; ?>">
                                        <button type="submit" class="button button-danger" style="padding: 6px 12px; font-size: 13px;">
                                            <span class="material-icons-outlined" style="font-size: 16px;">delete</span>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php endif; ?>

<?php else: ?>
    <div class="alert alert-info">
        <span class="material-icons-outlined">info</span>
        <span><strong>Selecione um curso</strong> para gerenciar seus alunos.</span>
    </div>
<?php endif; ?>

<?php
require_once '../includes/ead-layout-footer.php';
?>