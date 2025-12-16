<?php
/**
 * Fórum do Aluno
 * Sistema EAD Pro
 */

require_once '../config/database.php';

iniciar_sessao();

$page_title = 'Fórum';
include '../includes/header-aluno.php';

$aluno_id = $_SESSION['usuario_id'];

// Obter tópicos do fórum
$stmt = $pdo->prepare('
    SELECT f.*, a.nome as autor_nome, COUNT(r.id) as total_respostas
    FROM forum_topicos f
    LEFT JOIN alunos a ON f.aluno_id = a.id
    LEFT JOIN forum_respostas r ON f.id = r.topico_id
    GROUP BY f.id
    ORDER BY f.data_criacao DESC
    LIMIT 20
');
$stmt->execute();
$topicos = $stmt->fetchAll();
?>

<!-- Cabeçalho da Página -->
<div class="d-sm-flex align-items-center justify-content-between mb-4">
    <h1 class="h3 mb-0 text-gray-800">Fórum</h1>
    <a href="#" class="d-none d-sm-inline-block btn btn-sm btn-primary shadow-sm" data-toggle="modal" data-target="#novoTopicoModal">
        <i class="fas fa-plus fa-sm text-white-50"></i> Novo Tópico
    </a>
</div>

<!-- Tópicos do Fórum -->
<div class="card shadow mb-4">
    <div class="card-body">
        <?php if (empty($topicos)): ?>
            <p class="text-muted text-center py-4">Nenhum tópico no fórum ainda</p>
        <?php else: ?>
            <div class="list-group">
                <?php foreach ($topicos as $topico): ?>
                    <a href="#" class="list-group-item list-group-item-action">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <h6 class="font-weight-bold mb-1"><?php echo htmlspecialchars($topico['titulo']); ?></h6>
                                <p class="text-muted small mb-1"><?php echo htmlspecialchars($topico['autor_nome']); ?></p>
                                <p class="text-gray-600 small"><?php echo htmlspecialchars(substr($topico['conteudo'], 0, 100)); ?>...</p>
                            </div>
                            <div class="text-right">
                                <span class="badge badge-primary"><?php echo $topico['total_respostas']; ?> respostas</span>
                                <small class="text-muted d-block mt-2"><?php echo date('d/m/Y', strtotime($topico['data_criacao'])); ?></small>
                            </div>
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Modal - Novo Tópico -->
<div class="modal fade" id="novoTopicoModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Criar Novo Tópico</h5>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <form method="POST" action="criar-topico.php">
                <div class="modal-body">
                    <div class="form-group">
                        <label for="titulo">Título</label>
                        <input type="text" class="form-control" id="titulo" name="titulo" required>
                    </div>
                    <div class="form-group">
                        <label for="conteudo">Conteúdo</label>
                        <textarea class="form-control" id="conteudo" name="conteudo" rows="5" required></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Criar Tópico</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include '../includes/footer-aluno.php'; ?>

