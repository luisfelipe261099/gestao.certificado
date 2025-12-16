<?php
/**
 * Página de Gerenciar Questões do Exercício
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
$questao_id = (int)($_GET['questao_id'] ?? 0);
$erros = [];
$mensagem = '';
$tipo_mensagem = '';

// Obter exercício
$exercicio = $exercicio_model->obter_por_id($exercicio_id);

if (!$exercicio) {
    $_SESSION['mensagem'] = 'Exercício não encontrado!';
    $_SESSION['tipo_mensagem'] = 'danger';
    header('Location: exercicios.php');
    exit;
}

// Verificar se o exercício pertence a um curso do parceiro
$stmt = $pdo->prepare('
    SELECT c.parceiro_id FROM aulas a
    INNER JOIN cursos c ON a.id = (SELECT aula_id FROM exercicios WHERE id = ?)
    WHERE a.id = (SELECT aula_id FROM exercicios WHERE id = ?)
');
$stmt->execute([$exercicio_id, $exercicio_id]);
$curso_check = $stmt->fetch();

if (!$curso_check || $curso_check['parceiro_id'] != $parceiro_id) {
    $_SESSION['mensagem'] = 'Acesso negado!';
    $_SESSION['tipo_mensagem'] = 'danger';
    header('Location: exercicios.php');
    exit;
}

$questao = null;
if ($questao_id > 0) {
    $questao = $questao_model->obter_por_id($questao_id);
    if (!$questao) {
        $_SESSION['mensagem'] = 'Questão não encontrada!';
        $_SESSION['tipo_mensagem'] = 'danger';
        header('Location: questoes-exercicio.php?exercicio_id=' . $exercicio_id);
        exit;
    }
}

// Processar formulário
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $acao = sanitizar($_POST['acao'] ?? '');
    
    if ($acao === 'salvar_questao') {
        $titulo = sanitizar($_POST['titulo'] ?? '');
        $descricao = sanitizar($_POST['descricao'] ?? '');
        $tipo = sanitizar($_POST['tipo'] ?? 'multipla_escolha');
        $pontuacao = (int)($_POST['pontuacao'] ?? 1);
        
        // Validações
        if (empty($titulo)) {
            $erros[] = 'Título da questão é obrigatório';
        }
        if ($pontuacao <= 0) {
            $erros[] = 'Pontuação deve ser maior que 0';
        }
        
        if (empty($erros)) {
            if ($questao_id > 0) {
                // Atualizar questão
                $resultado = $questao_model->atualizar($questao_id, [
                    'titulo' => $titulo,
                    'descricao' => $descricao,
                    'tipo' => $tipo,
                    'pontuacao' => $pontuacao
                ]);
            } else {
                // Criar nova questão
                $resultado = $questao_model->criar([
                    'exercicio_id' => $exercicio_id,
                    'titulo' => $titulo,
                    'descricao' => $descricao,
                    'tipo' => $tipo,
                    'pontuacao' => $pontuacao
                ]);
                if ($resultado['sucesso']) {
                    $questao_id = $resultado['id'];
                }
            }
            
            if ($resultado['sucesso']) {
                $mensagem = $questao_id > 0 ? 'Questão atualizada com sucesso!' : 'Questão criada com sucesso!';
                $tipo_mensagem = 'success';
                $questao = $questao_model->obter_por_id($questao_id);
            } else {
                $erros[] = $resultado['erro'];
            }
        }
    } elseif ($acao === 'adicionar_opcao') {
        $texto = sanitizar($_POST['texto_opcao'] ?? '');
        $eh_correta = isset($_POST['eh_correta']) ? 1 : 0;
        
        if (empty($texto)) {
            $erros[] = 'Texto da opção é obrigatório';
        }
        
        if (empty($erros) && $questao_id > 0) {
            $resultado = $questao_model->adicionar_opcao($questao_id, $texto, $eh_correta);
            if ($resultado['sucesso']) {
                $mensagem = 'Opção adicionada com sucesso!';
                $tipo_mensagem = 'success';
            } else {
                $erros[] = $resultado['erro'];
            }
        }
    } elseif ($acao === 'deletar_opcao') {
        $opcao_id = (int)($_POST['opcao_id'] ?? 0);
        if ($opcao_id > 0) {
            $resultado = $questao_model->deletar_opcao($opcao_id);
            if ($resultado['sucesso']) {
                $mensagem = 'Opção deletada com sucesso!';
                $tipo_mensagem = 'success';
            }
        }
    }
}

$titulo_pagina = 'Questões do Exercício';
require_once '../includes/ead-layout-header.php';
?>

<!-- Page Heading -->
<div class="d-sm-flex align-items-center justify-content-between mb-4">
    <div>
        <h1 class="h3 mb-0 text-gray-800"><i class="fas fa-question-circle" style="color: #9c166f;"></i> Gerenciar Questões</h1>
        <p class="text-muted small mt-1">Crie e edite questões para o exercício</p>
    </div>
    <a href="editar-exercicio.php?id=<?php echo $exercicio_id; ?>" class="d-none d-sm-inline-block btn btn-lg shadow-sm" style="background-color: #9c166f; color: white; border: none;">
        <i class="fas fa-arrow-left"></i> Voltar
    </a>
</div>

<!-- Info Card -->
<div class="card shadow mb-4" style="border-left: 4px solid #9c166f;">
    <div class="card-header py-3" style="background-color: #9c166f;">
        <h6 class="m-0 font-weight-bold text-white"><i class="fas fa-tasks"></i> Exercício</h6>
    </div>
    <div class="card-body">
        <strong><?php echo htmlspecialchars($exercicio['titulo']); ?></strong>
    </div>
</div>

<!-- Mensagens -->
<?php if ($mensagem): ?>
    <div class="alert alert-<?php echo $tipo_mensagem === 'success' ? 'success' : 'danger'; ?> alert-dismissible fade show" role="alert">
        <i class="fas fa-<?php echo $tipo_mensagem === 'success' ? 'check-circle' : 'exclamation-circle'; ?>"></i> <?php echo $mensagem; ?>
        <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
    </div>
<?php endif; ?>

<?php if (!empty($erros)): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <strong><i class="fas fa-exclamation-circle"></i> Erros encontrados:</strong>
        <ul class="mb-0 mt-2">
            <?php foreach ($erros as $erro): ?>
                <li><?php echo $erro; ?></li>
            <?php endforeach; ?>
        </ul>
        <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
    </div>
<?php endif; ?>

<!-- Formulário de Questão -->
<div class="card shadow mb-4">
    <div class="card-header py-3" style="background-color: #9c166f;">
        <h6 class="m-0 font-weight-bold text-white">
            <i class="fas fa-<?php echo $questao_id > 0 ? 'edit' : 'plus'; ?>"></i>
            <?php echo $questao_id > 0 ? 'Editar Questão' : 'Nova Questão'; ?>
        </h6>
    </div>
    <div class="card-body">
        <form method="POST">
            <input type="hidden" name="acao" value="salvar_questao">

            <div class="form-group">
                <label for="titulo"><i class="fas fa-heading" style="color: #9c166f;"></i> <strong>Título da Questão <span class="text-danger">*</span></strong></label>
                <input type="text" class="form-control" id="titulo" name="titulo"
                       placeholder="Ex: Qual é a capital do Brasil?" required
                       value="<?php echo $questao ? htmlspecialchars($questao['titulo']) : ''; ?>">
            </div>

            <div class="form-group">
                <label for="descricao"><i class="fas fa-align-left" style="color: #9c166f;"></i> <strong>Descrição</strong></label>
                <textarea class="form-control" id="descricao" name="descricao"
                          rows="3" placeholder="Descrição adicional..."><?php echo $questao ? htmlspecialchars($questao['descricao'] ?? '') : ''; ?></textarea>
            </div>

            <div class="row">
                <div class="col-md-6">
                    <div class="form-group">
                        <label for="tipo"><i class="fas fa-list" style="color: #9c166f;"></i> <strong>Tipo <span class="text-danger">*</span></strong></label>
                        <select class="form-control" id="tipo" name="tipo" required>
                            <option value="multipla_escolha" <?php echo ($questao && $questao['tipo'] === 'multipla_escolha') ? 'selected' : ''; ?>>Múltipla Escolha</option>
                            <option value="verdadeiro_falso" <?php echo ($questao && $questao['tipo'] === 'verdadeiro_falso') ? 'selected' : ''; ?>>Verdadeiro/Falso</option>
                            <option value="dissertativa" <?php echo ($questao && $questao['tipo'] === 'dissertativa') ? 'selected' : ''; ?>>Dissertativa</option>
                            <option value="pratica" <?php echo ($questao && $questao['tipo'] === 'pratica') ? 'selected' : ''; ?>>Prática</option>
                        </select>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label for="pontuacao"><i class="fas fa-star" style="color: #9c166f;"></i> <strong>Pontuação <span class="text-danger">*</span></strong></label>
                        <input type="number" class="form-control" id="pontuacao" name="pontuacao"
                               placeholder="1" min="1" required
                               value="<?php echo $questao ? $questao['pontuacao'] : '1'; ?>">
                    </div>
                </div>
            </div>

            <div class="form-group mt-4">
                <button type="submit" class="btn btn-lg shadow-sm" style="background-color: #9c166f; color: white; border: none;">
                    <i class="fas fa-save"></i> <?php echo $questao_id > 0 ? 'Atualizar' : 'Criar'; ?> Questão
                </button>
                <a href="editar-exercicio.php?id=<?php echo $exercicio_id; ?>" class="btn btn-lg shadow-sm" style="background-color: #6c757d; color: white; border: none;">
                    <i class="fas fa-times"></i> Cancelar
                </a>
            </div>
        </form>
    </div>
</div>

<?php if ($questao): ?>
    <!-- Seção de Opções -->
    <div class="card shadow mb-4">
        <div class="card-header py-3 d-flex justify-content-between align-items-center" style="background-color: #9c166f;">
            <h6 class="m-0 font-weight-bold text-white">
                <i class="fas fa-list"></i> Opções de Resposta (<?php echo count($questao_model->obter_opcoes($questao_id)); ?>)
            </h6>
        </div>
        <div class="card-body">
            <?php
            $opcoes = $questao_model->obter_opcoes($questao_id);
            if (!empty($opcoes)):
            ?>
                <div class="table-responsive mb-4">
                    <table class="table table-hover table-bordered">
                        <thead style="background-color: #f8f9fa;">
                            <tr>
                                <th style="color: #9c166f;"><i class="fas fa-align-left"></i> Opção</th>
                                <th style="color: #9c166f;"><i class="fas fa-check-circle"></i> Correta?</th>
                                <th style="color: #9c166f;"><i class="fas fa-cog"></i> Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($opcoes as $opcao): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($opcao['texto']); ?></td>
                                    <td>
                                        <?php if ($opcao['eh_correta']): ?>
                                            <span class="badge" style="background-color: #28a745; color: white;"><i class="fas fa-check-circle"></i> Correta</span>
                                        <?php else: ?>
                                            <span class="badge" style="background-color: #6c757d; color: white;"><i class="fas fa-times-circle"></i> Incorreta</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <form method="POST" style="display:inline;"
                                              onsubmit="return confirm('Tem certeza que deseja deletar esta opção?');">
                                            <input type="hidden" name="acao" value="deletar_opcao">
                                            <input type="hidden" name="opcao_id" value="<?php echo $opcao['id']; ?>">
                                            <button type="submit" class="btn btn-sm btn-danger" title="Deletar">
                                                <i class="fas fa-trash"></i> Deletar
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="alert alert-info alert-dismissible fade show" role="alert">
                    <i class="fas fa-info-circle"></i> <strong>Nenhuma opção adicionada.</strong>
                    Adicione opções usando o formulário abaixo.
                    <button type="button" class="close" data-dismiss="alert">
                        <span>&times;</span>
                    </button>
                </div>
            <?php endif; ?>

            <!-- Formulário para Adicionar Opção -->
            <div class="card shadow" style="border-left: 4px solid #9c166f;">
                <div class="card-header py-3" style="background-color: #9c166f;">
                    <h6 class="m-0 font-weight-bold text-white"><i class="fas fa-plus"></i> Adicionar Nova Opção</h6>
                </div>
                <div class="card-body">
                    <form method="POST">
                        <input type="hidden" name="acao" value="adicionar_opcao">
                        <div class="form-group">
                            <label for="texto_opcao"><i class="fas fa-align-left" style="color: #9c166f;"></i> <strong>Texto da Opção <span class="text-danger">*</span></strong></label>
                            <input type="text" class="form-control" id="texto_opcao" name="texto_opcao"
                                   placeholder="Ex: Brasília" required>
                        </div>
                        <div class="form-check mb-3">
                            <input type="checkbox" class="form-check-input" id="eh_correta" name="eh_correta">
                            <label class="form-check-label" for="eh_correta">
                                <i class="fas fa-check-circle" style="color: #9c166f;"></i> Esta é a resposta correta?
                            </label>
                        </div>
                        <button type="submit" class="btn btn-lg shadow-sm" style="background-color: #9c166f; color: white; border: none;">
                            <i class="fas fa-plus"></i> Adicionar Opção
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
<?php endif; ?>

<?php
require_once '../includes/ead-layout-footer.php';
?>

