<?php
/**
 * Página de Visualização de Exercício (Preview)
 * Sistema EAD Pro
 */

require_once '../config/database.php';
require_once '../app/models/Exercicio.php';
require_once '../app/models/Questao.php';

// Verificar autenticação
verificar_autenticacao('parceiro');

$parceiro_id = $_SESSION['ead_parceiro_id'];
$exercicio_model = new Exercicio($pdo);
$questao_model = new Questao($pdo);
$exercicio_id = (int)($_GET['exercicio_id'] ?? 0);

if ($exercicio_id === 0) {
    $_SESSION['mensagem'] = 'Exercício não encontrado!';
    $_SESSION['tipo_mensagem'] = 'danger';
    header('Location: exercicios.php');
    exit;
}

// Obter exercício
$exercicio = $exercicio_model->obter_por_id($exercicio_id);

if (!$exercicio) {
    $_SESSION['mensagem'] = 'Exercício não encontrado!';
    $_SESSION['tipo_mensagem'] = 'danger';
    header('Location: exercicios.php');
    exit;
}

// Verificar se o exercício pertence ao parceiro
$stmt = $pdo->prepare('
    SELECT c.parceiro_id FROM exercicios e
    INNER JOIN aulas a ON e.aula_id = a.id
    INNER JOIN cursos c ON a.curso_id = c.id
    WHERE e.id = ?
');
$stmt->execute([$exercicio_id]);
$check = $stmt->fetch();

if (!$check || $check['parceiro_id'] != $parceiro_id) {
    $_SESSION['mensagem'] = 'Acesso negado!';
    $_SESSION['tipo_mensagem'] = 'danger';
    header('Location: exercicios.php');
    exit;
}

// Obter aula
$stmt = $pdo->prepare('SELECT * FROM aulas WHERE id = ?');
$stmt->execute([$exercicio['aula_id']]);
$aula = $stmt->fetch();

// Obter questões
$questoes = $questao_model->obter_por_exercicio($exercicio_id);

// Obter opções para cada questão
foreach ($questoes as &$questao) {
    $questao['opcoes'] = $questao_model->obter_opcoes($questao['id']);
}

$titulo_pagina = 'Visualizar Exercício';
require_once '../includes/ead-layout-header.php';
?>

<!-- Cabeçalho -->
<div style="background: linear-gradient(135deg, rgba(110, 65, 193, 0.08) 0%, rgba(139, 95, 214, 0.04) 100%); border-radius: 16px; padding: 28px 32px; margin-bottom: 28px;">
    <div style="display: flex; align-items: center; justify-content: space-between;">
        <div style="display: flex; align-items: center; gap: 16px;">
            <div style="width: 56px; height: 56px; background: linear-gradient(135deg, #6E41C1 0%, #8B5FD6 100%); border-radius: 14px; display: flex; align-items: center; justify-content: center; box-shadow: 0 4px 12px rgba(110, 65, 193, 0.3);">
                <span class="material-icons-outlined" style="font-size: 28px; color: white;">visibility</span>
            </div>
            <div>
                <h1 style="font-size: 28px; font-weight: 700; color: #1D1D1F; margin: 0;">Pré-visualização do Exercício</h1>
                <p style="color: #86868B; font-size: 14px; margin: 4px 0 0 0;">Veja como o exercício aparecerá para os alunos</p>
            </div>
        </div>
        <a href="exercicios.php?aula_id=<?php echo $exercicio['aula_id']; ?>" class="button button-secondary" style="text-decoration: none;">
            <span class="material-icons-outlined">arrow_back</span>
            <span>Voltar</span>
        </a>
    </div>
</div>

<!-- Banner do Exercício -->
<div class="card" style="margin-bottom: 28px; background: linear-gradient(135deg, #6E41C1 0%, #8B5FD6 100%); color: white; border: none;">
    <div style="padding: 32px;">
        <div style="display: flex; align-items: start; gap: 20px;">
            <div style="width: 64px; height: 64px; background: rgba(255, 255, 255, 0.2); border-radius: 16px; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                <span class="material-icons-outlined" style="font-size: 36px;">quiz</span>
            </div>
            <div style="flex: 1;">
                <h2 style="font-size: 24px; font-weight: 700; margin: 0 0 12px 0; color: white;">
                    <?php echo htmlspecialchars($exercicio['titulo']); ?>
                </h2>
                <?php if ($exercicio['descricao']): ?>
                    <p style="font-size: 15px; margin: 0 0 16px 0; opacity: 0.95; line-height: 1.6;">
                        <?php echo nl2br(htmlspecialchars($exercicio['descricao'])); ?>
                    </p>
                <?php endif; ?>
                
                <div style="display: flex; gap: 24px; flex-wrap: wrap;">
                    <div style="display: flex; align-items: center; gap: 8px;">
                        <span class="material-icons-outlined" style="font-size: 20px;">school</span>
                        <span style="font-size: 14px;"><?php echo htmlspecialchars($aula['titulo']); ?></span>
                    </div>
                    <div style="display: flex; align-items: center; gap: 8px;">
                        <span class="material-icons-outlined" style="font-size: 20px;">help</span>
                        <span style="font-size: 14px;"><?php echo count($questoes); ?> <?php echo count($questoes) == 1 ? 'Questão' : 'Questões'; ?></span>
                    </div>
                    <div style="display: flex; align-items: center; gap: 8px;">
                        <span class="material-icons-outlined" style="font-size: 20px;">star</span>
                        <span style="font-size: 14px;"><?php echo $exercicio['pontuacao_maxima']; ?> Pontos</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Alerta de Preview -->
<div style="background: rgba(255, 149, 0, 0.1); border-left: 4px solid #FF9500; border-radius: 8px; padding: 16px; margin-bottom: 28px;">
    <div style="display: flex; align-items: center; gap: 12px;">
        <span class="material-icons-outlined" style="color: #FF9500; font-size: 24px;">info</span>
        <div>
            <strong style="color: #1D1D1F; display: block; margin-bottom: 4px;">Modo de Visualização</strong>
            <span style="color: #86868B; font-size: 14px;">Esta é uma pré-visualização. As respostas não serão salvas.</span>
        </div>
    </div>
</div>

<!-- Questões -->
<?php if (empty($questoes)): ?>
    <div class="card">
        <div style="text-align: center; padding: 48px 24px; color: #86868B;">
            <span class="material-icons-outlined" style="font-size: 64px; color: #E5E5E7; margin-bottom: 16px;">quiz</span>
            <p style="font-size: 16px; margin: 0;">Nenhuma questão cadastrada</p>
            <p style="font-size: 14px; margin: 8px 0 0 0;">Edite o exercício para adicionar questões</p>
        </div>
    </div>
<?php else: ?>
    <?php foreach ($questoes as $index => $questao): ?>
        <div class="card" style="margin-bottom: 24px;">
            <!-- Header da Questão -->
            <div style="display: flex; align-items: start; gap: 16px; margin-bottom: 20px;">
                <div style="width: 40px; height: 40px; background: linear-gradient(135deg, #6E41C1 0%, #8B5FD6 100%); border-radius: 10px; display: flex; align-items: center; justify-content: center; color: white; font-weight: 700; font-size: 16px; flex-shrink: 0;">
                    <?php echo $index + 1; ?>
                </div>
                <div style="flex: 1;">
                    <h3 style="font-size: 18px; font-weight: 600; color: #1D1D1F; margin: 0 0 8px 0; line-height: 1.4;">
                        <?php echo htmlspecialchars($questao['titulo']); ?>
                    </h3>
                    <?php if ($questao['descricao']): ?>
                        <p style="color: #86868B; font-size: 14px; margin: 0; line-height: 1.6;">
                            <?php echo nl2br(htmlspecialchars($questao['descricao'])); ?>
                        </p>
                    <?php endif; ?>
                </div>
                <div style="display: flex; align-items: center; gap: 6px; padding: 6px 12px; background: rgba(110, 65, 193, 0.1); border-radius: 8px; flex-shrink: 0;">
                    <span class="material-icons-outlined" style="font-size: 16px; color: #6E41C1;">star</span>
                    <span style="font-size: 13px; font-weight: 600; color: #6E41C1;"><?php echo $questao['pontuacao']; ?> pt<?php echo $questao['pontuacao'] > 1 ? 's' : ''; ?></span>
                </div>
            </div>

            <!-- Opções -->
            <?php if ($questao['tipo'] === 'multipla_escolha' && !empty($questao['opcoes'])): ?>
                <div style="display: flex; flex-direction: column; gap: 12px;">
                    <?php foreach ($questao['opcoes'] as $opcao_index => $opcao): ?>
                        <label style="display: flex; align-items: center; gap: 12px; padding: 16px; background: #F5F5F7; border: 2px solid #E5E5E7; border-radius: 10px; cursor: pointer; transition: all 0.2s ease;"
                               onmouseover="this.style.borderColor='#6E41C1'; this.style.background='rgba(110, 65, 193, 0.05)';"
                               onmouseout="this.style.borderColor='#E5E5E7'; this.style.background='#F5F5F7';">
                            <input type="radio" name="questao_<?php echo $questao['id']; ?>" value="<?php echo $opcao['id']; ?>"
                                   style="width: 20px; height: 20px; cursor: pointer; accent-color: #6E41C1;">
                            <div style="flex: 1;">
                                <div style="display: flex; align-items: center; gap: 8px;">
                                    <span style="display: inline-flex; align-items: center; justify-content: center; width: 24px; height: 24px; background: white; border: 2px solid #6E41C1; border-radius: 6px; font-weight: 700; font-size: 12px; color: #6E41C1;">
                                        <?php echo chr(65 + $opcao_index); ?>
                                    </span>
                                    <span style="font-size: 15px; color: #1D1D1F;">
                                        <?php echo htmlspecialchars($opcao['texto']); ?>
                                    </span>
                                </div>
                            </div>
                        </label>
                    <?php endforeach; ?>
                </div>
            <?php elseif ($questao['tipo'] === 'dissertativa'): ?>
                <textarea class="form-control" rows="5" placeholder="Digite sua resposta aqui..." disabled
                          style="background: #F5F5F7; border: 2px solid #E5E5E7; border-radius: 10px; padding: 16px; font-size: 14px;"></textarea>
            <?php endif; ?>
        </div>
    <?php endforeach; ?>

    <!-- Botão de Envio (Desabilitado) -->
    <div style="display: flex; gap: 12px; margin-top: 28px;">
        <button type="button" class="button button-primary" disabled style="opacity: 0.5; cursor: not-allowed;">
            <span class="material-icons-outlined">send</span>
            <span>Enviar Respostas</span>
        </button>
        <a href="exercicios.php?aula_id=<?php echo $exercicio['aula_id']; ?>" class="button button-secondary" style="text-decoration: none;">
            <span class="material-icons-outlined">close</span>
            <span>Fechar Visualização</span>
        </a>
    </div>
<?php endif; ?>

<style>
/* Animação para opções selecionadas */
input[type="radio"]:checked + div {
    font-weight: 600;
}

input[type="radio"]:checked {
    transform: scale(1.1);
}
</style>

<?php
require_once '../includes/ead-layout-footer.php';
?>

