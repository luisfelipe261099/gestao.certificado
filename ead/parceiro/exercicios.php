<?php
/**
 * Página de Gerenciamento de Exercícios
 * Sistema EAD Pro
 */

require_once '../config/database.php';
require_once '../app/models/Aula.php';
require_once '../app/models/Exercicio.php';

// Verificar autenticação
verificar_autenticacao('parceiro');

$parceiro_id = $_SESSION['ead_parceiro_id'];
$aula_model = new Aula($pdo);
$exercicio_model = new Exercicio($pdo);
$aula_id = (int)($_GET['aula_id'] ?? 0);

// Se não houver aula_id, mostrar lista de aulas
$aula = null;
$todas_aulas = [];

if ($aula_id > 0) {
    // Obter aula específica
    $aula = $aula_model->obter_por_id($aula_id);

    if (!$aula) {
        $_SESSION['mensagem'] = 'Aula não encontrada!';
        $_SESSION['tipo_mensagem'] = 'danger';
        header('Location: aulas.php');
        exit;
    }

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
} else {
    // Obter todas as aulas do parceiro
    $stmt = $pdo->prepare('
        SELECT a.*, c.nome as curso_nome FROM aulas a
        INNER JOIN cursos c ON a.curso_id = c.id
        WHERE c.parceiro_id = ? AND a.ativa = 1
        ORDER BY c.nome, a.ordem
    ');
    $stmt->execute([$parceiro_id]);
    $todas_aulas = $stmt->fetchAll();
}

// Obter exercícios (apenas se houver aula selecionada)
$exercicios = [];
if ($aula_id > 0) {
    $exercicios = $exercicio_model->obter_por_aula($aula_id);
}

// Processar ações
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $acao = sanitizar($_POST['acao'] ?? '');
    
    if ($acao === 'deletar') {
        $exercicio_id = (int)$_POST['exercicio_id'];
        $resultado = $exercicio_model->deletar($exercicio_id);
        
        if ($resultado['sucesso']) {
            $_SESSION['mensagem'] = 'Exercício deletado com sucesso!';
            $_SESSION['tipo_mensagem'] = 'success';
            header('Location: exercicios.php?aula_id=' . $aula_id);
            exit;
        }
    }
}

// Obter mensagem de sessão
$mensagem = $_SESSION['mensagem'] ?? '';
$tipo_mensagem = $_SESSION['tipo_mensagem'] ?? '';
unset($_SESSION['mensagem'], $_SESSION['tipo_mensagem']);

$titulo_pagina = 'Exercícios';
require_once '../includes/ead-layout-header.php';
?>

<!-- Cabeçalho Moderno -->
<div style="background: linear-gradient(135deg, rgba(110, 65, 193, 0.08) 0%, rgba(139, 95, 214, 0.04) 100%); border-radius: 16px; padding: 28px 32px; margin-bottom: 28px;">
    <div style="display: flex; align-items: center; justify-content: space-between;">
        <div style="display: flex; align-items: center; gap: 16px;">
            <div style="width: 56px; height: 56px; background: linear-gradient(135deg, #6E41C1 0%, #8B5FD6 100%); border-radius: 14px; display: flex; align-items: center; justify-content: center; box-shadow: 0 4px 12px rgba(110, 65, 193, 0.3);">
                <span class="material-icons-outlined" style="font-size: 28px; color: white;">quiz</span>
            </div>
            <div>
                <h1 style="font-size: 28px; font-weight: 700; color: #1D1D1F; margin: 0; line-height: 1.2;">Gerenciar Exercícios</h1>
                <p style="color: #86868B; font-size: 14px; margin: 4px 0 0 0;">Crie e gerencie exercícios para suas aulas</p>
            </div>
        </div>
        <a href="aulas.php" class="button button-secondary" style="text-decoration: none;">
            <span class="material-icons-outlined">arrow_back</span>
            <span>Voltar</span>
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

<?php if ($aula): ?>
    <!-- Cards de Estatísticas -->
    <div class="stats-grid" style="grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); margin-bottom: 28px;">
        <div class="stat-card">
            <span class="material-icons-outlined">quiz</span>
            <div class="stat-label">Total de Exercícios</div>
            <div class="stat-value"><?php echo count($exercicios); ?></div>
            <div class="stat-change">Criados</div>
        </div>
    </div>

    <!-- Info Card -->
    <div class="card" style="margin-bottom: 28px;">
        <h2>
            <span class="material-icons-outlined">play_circle</span>
            Aula Selecionada
        </h2>
        <p style="margin: 0 0 8px 0;"><strong><?php echo htmlspecialchars($aula['titulo']); ?></strong></p>
        <p style="color: #86868B; font-size: 14px; margin: 0;">
            <span class="material-icons-outlined" style="font-size: 16px; vertical-align: middle;">schedule</span>
            Duração: <?php echo $aula['duracao_minutos'] ? $aula['duracao_minutos'] . ' minutos' : 'Não definida'; ?>
        </p>
    </div>

    <!-- Botão Criar Exercício -->
    <div style="margin-bottom: 28px;">
        <a href="criar-exercicio.php?aula_id=<?php echo $aula_id; ?>" class="button button-primary" style="text-decoration: none;">
            <span class="material-icons-outlined">add</span>
            <span>Novo Exercício</span>
        </a>
    </div>
<?php else: ?>
    <!-- Seleção de Aula -->
    <?php if (empty($todas_aulas)): ?>
        <div class="card">
            <div style="text-align: center; padding: 48px 24px; color: #86868B;">
                <span class="material-icons-outlined" style="font-size: 64px; color: #E5E5E7; margin-bottom: 16px;">school</span>
                <p style="font-size: 16px; margin: 0 0 8px 0; color: #1D1D1F; font-weight: 600;">Nenhuma aula disponível</p>
                <p style="font-size: 14px; margin: 0 0 20px 0;">Crie uma aula primeiro para adicionar exercícios</p>
                <a href="criar-aula.php" class="button button-primary" style="text-decoration: none;">
                    <span class="material-icons-outlined">add</span>
                    <span>Criar Aula</span>
                </a>
            </div>
        </div>
    <?php else: ?>
        <div class="card">
            <h2>
                <span class="material-icons-outlined">school</span>
                Selecione uma Aula
            </h2>

            <div style="display: grid; gap: 16px;">
                <?php foreach ($todas_aulas as $a): ?>
                    <div style="display: flex; align-items: center; justify-content: space-between; padding: 20px; background: #F5F5F7; border-radius: 12px; border: 2px solid #E5E5E7; transition: all 0.2s ease;"
                         onmouseover="this.style.borderColor='#6E41C1'; this.style.background='rgba(110, 65, 193, 0.05)';"
                         onmouseout="this.style.borderColor='#E5E5E7'; this.style.background='#F5F5F7';">
                        <div style="flex: 1;">
                            <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 8px;">
                                <span class="material-icons-outlined" style="color: #6E41C1; font-size: 24px;">play_circle</span>
                                <h3 style="font-size: 16px; font-weight: 600; color: #1D1D1F; margin: 0;">
                                    <?php echo htmlspecialchars($a['titulo']); ?>
                                </h3>
                            </div>
                            <div style="display: flex; gap: 20px; margin-left: 36px;">
                                <div style="display: flex; align-items: center; gap: 6px; color: #86868B; font-size: 13px;">
                                    <span class="material-icons-outlined" style="font-size: 16px;">book</span>
                                    <span><?php echo htmlspecialchars($a['curso_nome']); ?></span>
                                </div>
                                <div style="display: flex; align-items: center; gap: 6px; color: #86868B; font-size: 13px;">
                                    <span class="material-icons-outlined" style="font-size: 16px;">schedule</span>
                                    <span><?php echo $a['duracao_minutos'] ? $a['duracao_minutos'] . ' min' : 'Não definida'; ?></span>
                                </div>
                            </div>
                        </div>
                        <a href="exercicios.php?aula_id=<?php echo $a['id']; ?>" class="button button-primary" style="text-decoration: none;">
                            <span class="material-icons-outlined">quiz</span>
                            <span>Exercícios</span>
                        </a>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>
<?php endif; ?>

<!-- Lista de Exercícios -->
<?php if ($aula): ?>
    <?php if (empty($exercicios)): ?>
        <div class="card">
            <div style="text-align: center; padding: 48px 24px; color: #86868B;">
                <span class="material-icons-outlined" style="font-size: 64px; color: #E5E5E7; margin-bottom: 16px;">quiz</span>
                <p style="font-size: 16px; margin: 0 0 8px 0; color: #1D1D1F; font-weight: 600;">Nenhum exercício criado</p>
                <p style="font-size: 14px; margin: 0 0 20px 0;">Crie seu primeiro exercício clicando no botão "Novo Exercício"</p>
                <a href="criar-exercicio.php?aula_id=<?php echo $aula_id; ?>" class="button button-primary" style="text-decoration: none;">
                    <span class="material-icons-outlined">add</span>
                    <span>Novo Exercício</span>
                </a>
            </div>
        </div>
    <?php else: ?>
        <div class="card">
            <h2>
                <span class="material-icons-outlined">quiz</span>
                Exercícios (<?php echo count($exercicios); ?>)
            </h2>

            <div style="display: grid; gap: 16px;">
                <?php foreach ($exercicios as $exercicio): ?>
                    <?php $stats = $exercicio_model->obter_estatisticas($exercicio['id']); ?>
                    <div style="display: flex; align-items: center; gap: 20px; padding: 20px; background: white; border: 2px solid #E5E5E7; border-radius: 12px; transition: all 0.2s ease;"
                         onmouseover="this.style.borderColor='#6E41C1'; this.style.boxShadow='0 4px 12px rgba(110, 65, 193, 0.1)';"
                         onmouseout="this.style.borderColor='#E5E5E7'; this.style.boxShadow='none';">

                        <!-- Ícone -->
                        <div style="width: 48px; height: 48px; background: linear-gradient(135deg, #6E41C1 0%, #8B5FD6 100%); border-radius: 12px; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                            <span class="material-icons-outlined" style="font-size: 24px; color: white;">quiz</span>
                        </div>

                        <!-- Conteúdo -->
                        <div style="flex: 1; min-width: 0;">
                            <h3 style="font-size: 16px; font-weight: 600; color: #1D1D1F; margin: 0 0 8px 0;">
                                <?php echo htmlspecialchars($exercicio['titulo']); ?>
                            </h3>
                            <div style="display: flex; gap: 16px; flex-wrap: wrap;">
                                <div style="display: flex; align-items: center; gap: 6px;">
                                    <span class="badge" style="background: rgba(110, 65, 193, 0.1); color: #6E41C1; padding: 4px 10px; border-radius: 6px; font-size: 12px; font-weight: 600;">
                                        <?php echo ucfirst(str_replace('_', ' ', $exercicio['tipo'])); ?>
                                    </span>
                                </div>
                                <div style="display: flex; align-items: center; gap: 6px; color: #86868B; font-size: 13px;">
                                    <span class="material-icons-outlined" style="font-size: 16px;">star</span>
                                    <span><?php echo $exercicio['pontuacao_maxima']; ?> pts</span>
                                </div>
                                <div style="display: flex; align-items: center; gap: 6px; color: #86868B; font-size: 13px;">
                                    <span class="material-icons-outlined" style="font-size: 16px;">comment</span>
                                    <span><?php echo $stats['total_respostas']; ?> respostas</span>
                                </div>
                                <div>
                                    <span class="badge" style="background: <?php echo $exercicio['ativa'] ? 'rgba(52, 199, 89, 0.1)' : 'rgba(255, 59, 48, 0.1)'; ?>; color: <?php echo $exercicio['ativa'] ? '#34C759' : '#FF3B30'; ?>; padding: 4px 10px; border-radius: 6px; font-size: 12px; font-weight: 600;">
                                        <span class="material-icons-outlined" style="font-size: 14px; vertical-align: middle;">
                                            <?php echo $exercicio['ativa'] ? 'check_circle' : 'cancel'; ?>
                                        </span>
                                        <?php echo $exercicio['ativa'] ? 'Ativo' : 'Inativo'; ?>
                                    </span>
                                </div>
                            </div>
                        </div>

                        <!-- Ações -->
                        <div style="display: flex; gap: 8px; flex-shrink: 0;">
                            <a href="visualizar-exercicio.php?exercicio_id=<?php echo $exercicio['id']; ?>"
                               class="button button-secondary"
                               style="text-decoration: none; padding: 10px 16px; font-size: 13px;"
                               title="Visualizar">
                                <span class="material-icons-outlined" style="font-size: 18px;">visibility</span>
                            </a>
                            <a href="editar-exercicio.php?id=<?php echo $exercicio['id']; ?>"
                               class="button button-secondary"
                               style="text-decoration: none; padding: 10px 16px; font-size: 13px;"
                               title="Editar">
                                <span class="material-icons-outlined" style="font-size: 18px;">edit</span>
                            </a>
                            <form method="POST" style="display: inline;"
                                  onsubmit="return confirm('Tem certeza que deseja deletar este exercício?\n\nEsta ação não pode ser desfeita.');">
                                <input type="hidden" name="acao" value="deletar">
                                <input type="hidden" name="exercicio_id" value="<?php echo $exercicio['id']; ?>">
                                <button type="submit"
                                        class="button button-danger"
                                        style="padding: 10px 16px; font-size: 13px;"
                                        title="Deletar">
                                    <span class="material-icons-outlined" style="font-size: 18px;">delete</span>
                                </button>
                            </form>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>
<?php endif; ?>

<?php
require_once '../includes/ead-layout-footer.php';
?>