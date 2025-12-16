<?php
/**
 * Página de Editar Exercício
 * Sistema EAD Pro
 */

require_once '../config/database.php';
require_once '../app/models/Aula.php';
require_once '../app/models/Exercicio.php';
require_once '../app/models/Questao.php';

// Verificar autenticação
verificar_autenticacao('parceiro');

$parceiro_id = $_SESSION['ead_parceiro_id'];
$aula_model = new Aula($pdo);
$exercicio_model = new Exercicio($pdo);
$questao_model = new Questao($pdo);
$exercicio_id = (int)($_GET['id'] ?? 0);
$erros = [];
$questoes = [];

// Obter exercício
$exercicio = $exercicio_model->obter_por_id($exercicio_id);

if (!$exercicio) {
    $_SESSION['mensagem'] = 'Exercício não encontrado!';
    $_SESSION['tipo_mensagem'] = 'danger';
    header('Location: aulas.php');
    exit;
}

// Obter aula
$aula = $aula_model->obter_por_id($exercicio['aula_id']);

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

// Obter questões do exercício
$questoes = $questao_model->obter_por_exercicio($exercicio_id);

// Processar formulário
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $titulo = sanitizar($_POST['titulo'] ?? '');
    $descricao = sanitizar($_POST['descricao'] ?? '');
    $tipo = sanitizar($_POST['tipo'] ?? 'multipla_escolha');
    $pontuacao_maxima = (int)($_POST['pontuacao_maxima'] ?? 10);
    
    // Validações
    if (empty($titulo)) {
        $erros[] = 'Título do exercício é obrigatório';
    }
    if (strlen($titulo) < 3) {
        $erros[] = 'Título deve ter pelo menos 3 caracteres';
    }
    if ($pontuacao_maxima <= 0) {
        $erros[] = 'Pontuação máxima deve ser maior que 0';
    }
    
    // Se não houver erros, atualizar exercício
    if (empty($erros)) {
        $resultado = $exercicio_model->atualizar($exercicio_id, [
            'titulo' => $titulo,
            'descricao' => $descricao,
            'tipo' => $tipo,
            'pontuacao_maxima' => $pontuacao_maxima
        ]);
        
        if ($resultado['sucesso']) {
            $_SESSION['mensagem'] = 'Exercício atualizado com sucesso!';
            $_SESSION['tipo_mensagem'] = 'success';
            header('Location: exercicios.php?aula_id=' . $exercicio['aula_id']);
            exit;
        } else {
            $erros[] = $resultado['erro'];
        }
    }
}

$titulo_pagina = 'Editar Exercício';
require_once '../includes/ead-layout-header.php';
?>

<!-- Page Heading -->
<div class="d-sm-flex align-items-center justify-content-between mb-4">
    <div>
        <h1 class="h3 mb-0 text-gray-800"><i class="fas fa-edit" style="color: #9c166f;"></i> Editar Exercício</h1>
        <p class="text-muted small mt-1">Atualize os dados do exercício</p>
    </div>
    <a href="exercicios.php?aula_id=<?php echo $exercicio['aula_id']; ?>" class="d-none d-sm-inline-block btn btn-lg shadow-sm" style="background-color: #9c166f; color: white; border: none;">
        <i class="fas fa-arrow-left"></i> Voltar
    </a>
</div>

<!-- Informações da Aula -->
<div class="card shadow mb-4" style="border-left: 4px solid #9c166f;">
    <div class="card-header py-3" style="background-color: #9c166f;">
        <h6 class="m-0 font-weight-bold text-white"><i class="fas fa-video"></i> Aula</h6>
    </div>
    <div class="card-body">
        <strong><?php echo htmlspecialchars($aula['titulo']); ?></strong>
    </div>
</div>

<!-- Erros -->
<?php if (!empty($erros)): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <strong><i class="fas fa-exclamation-circle"></i> Erros encontrados:</strong>
        <ul class="mb-0 mt-2">
            <?php foreach ($erros as $erro): ?>
                <li><?php echo $erro; ?></li>
            <?php endforeach; ?>
        </ul>
        <button type="button" class="close" data-dismiss="alert">
            <span>&times;</span>
        </button>
    </div>
<?php endif; ?>

<!-- Formulário -->
<div class="card shadow mb-4">
    <div class="card-header py-3" style="background-color: #9c166f;">
        <h6 class="m-0 font-weight-bold text-white"><i class="fas fa-tasks"></i> Dados do Exercício</h6>
    </div>
    <div class="card-body">
        <form method="POST">
            <div class="form-group">
                <label for="titulo"><i class="fas fa-heading" style="color: #9c166f;"></i> <strong>Título do Exercício <span class="text-danger">*</span></strong></label>
                <input type="text" class="form-control" id="titulo" name="titulo"
                       placeholder="Ex: Questão sobre variáveis" required
                       value="<?php echo htmlspecialchars($exercicio['titulo']); ?>">
            </div>

            <div class="form-group">
                <label for="descricao"><i class="fas fa-align-left" style="color: #9c166f;"></i> <strong>Descrição</strong></label>
                <textarea class="form-control" id="descricao" name="descricao"
                          rows="4" placeholder="Descreva o exercício..."><?php echo htmlspecialchars($exercicio['descricao'] ?? ''); ?></textarea>
            </div>

            <div class="row">
                <div class="col-md-6">
                    <div class="form-group">
                        <label for="tipo"><i class="fas fa-list" style="color: #9c166f;"></i> <strong>Tipo de Exercício <span class="text-danger">*</span></strong></label>
                        <select class="form-control" id="tipo" name="tipo" required>
                            <option value="multipla_escolha" <?php echo $exercicio['tipo'] === 'multipla_escolha' ? 'selected' : ''; ?>>Múltipla Escolha</option>
                            <option value="verdadeiro_falso" <?php echo $exercicio['tipo'] === 'verdadeiro_falso' ? 'selected' : ''; ?>>Verdadeiro/Falso</option>
                            <option value="dissertativa" <?php echo $exercicio['tipo'] === 'dissertativa' ? 'selected' : ''; ?>>Dissertativa</option>
                            <option value="pratica" <?php echo $exercicio['tipo'] === 'pratica' ? 'selected' : ''; ?>>Prática</option>
                        </select>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label for="pontuacao_maxima"><i class="fas fa-star" style="color: #9c166f;"></i> <strong>Pontuação Máxima <span class="text-danger">*</span></strong></label>
                        <input type="number" class="form-control" id="pontuacao_maxima" name="pontuacao_maxima"
                               placeholder="10" min="1" required
                               value="<?php echo $exercicio['pontuacao_maxima']; ?>">
                    </div>
                </div>
            </div>

            <div class="form-group mt-4">
                <button type="submit" class="btn btn-lg shadow-sm" style="background-color: #9c166f; color: white; border: none;">
                                    <i class="fas fa-save"></i> Salvar Alterações
                                </button>
                                <a href="exercicios.php?aula_id=<?php echo $exercicio['aula_id']; ?>" class="btn btn-secondary btn-lg">
                                    <i class="fas fa-times"></i> Cancelar
                                </a>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Seção de Questões -->
                <div class="card mt-4 shadow">
                    <div class="card-header py-3 d-flex justify-content-between align-items-center" style="background-color: #9c166f;">
                        <h6 class="m-0 font-weight-bold text-white">
                            <i class="fas fa-question-circle"></i> Questões (<?php echo count($questoes); ?>)
                        </h6>
                        <a href="questoes-exercicio.php?exercicio_id=<?php echo $exercicio_id; ?>" class="btn btn-sm shadow-sm" style="background-color: #28a745; color: white; border: none;">
                            <i class="fas fa-plus"></i> Adicionar Questão
                        </a>
                    </div>
                    <div class="card-body">
                        <?php if (empty($questoes)): ?>
                            <div class="alert alert-info">
                                <i class="fas fa-inbox"></i> Nenhuma questão adicionada.
                                <a href="questoes-exercicio.php?exercicio_id=<?php echo $exercicio_id; ?>">Adicione uma questão</a>.
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-hover table-bordered">
                                    <thead style="background-color: #f8f9fa;">
                                        <tr>
                                            <th style="color: #9c166f;">Questão</th>
                                            <th style="color: #9c166f;">Tipo</th>
                                            <th style="color: #9c166f;">Pontuação</th>
                                            <th style="color: #9c166f;">Opções</th>
                                            <th style="color: #9c166f;">Ações</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($questoes as $q): ?>
                                            <?php $opcoes = $questao_model->obter_opcoes($q['id']); ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($q['titulo']); ?></td>
                                                <td>
                                                    <span class="badge" style="background-color: #9c166f; color: white;">
                                                        <?php echo ucfirst(str_replace('_', ' ', $q['tipo'])); ?>
                                                    </span>
                                                </td>
                                                <td><?php echo $q['pontuacao']; ?> pts</td>
                                                <td><?php echo count($opcoes); ?></td>
                                                <td>
                                                    <a href="questoes-exercicio.php?exercicio_id=<?php echo $exercicio_id; ?>&questao_id=<?php echo $q['id']; ?>"
                                                       class="btn btn-sm btn-warning" title="Editar">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                    <form method="POST" style="display:inline;"
                                                          onsubmit="return confirm('Tem certeza que deseja deletar esta questão?');">
                                                        <input type="hidden" name="acao" value="deletar_questao">
                                                        <input type="hidden" name="questao_id" value="<?php echo $q['id']; ?>">
                                                        <button type="submit" class="btn btn-sm btn-danger" title="Deletar">
                                                            <i class="fas fa-trash"></i>
                                                        </button>
                                                    </form>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

<?php
require_once '../includes/ead-layout-footer.php';
?>