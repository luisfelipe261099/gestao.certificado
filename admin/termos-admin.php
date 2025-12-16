<?php
/**
 * Gerenciamento de Termos de Serviço - Painel Administrativo
 * Criar, editar e gerenciar termos
 */

require_once '../app/config/config.php';
require_once '../app/models/Contrato.php';

// Verificar autenticação e permissão de admin
if (!isAuthenticated() || !hasRole(ROLE_ADMIN)) {
    redirect('../login.php');
}

$conn = getDBConnection();
$contrato_model = new Contrato($conn);

$mensagem = '';
$tipo_mensagem = '';
$acao = $_GET['acao'] ?? 'listar';
$termo_id = $_GET['id'] ?? null;
$termo = null;

// ============================================================
// PROCESSAR AÇÕES
// ============================================================

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $acao_post = $_POST['acao'] ?? '';
    
    // Criar novo termo
    if ($acao_post === 'criar') {
        $titulo = $_POST['titulo'] ?? '';
        $conteudo = $_POST['conteudo'] ?? '';
        $tipo = $_POST['tipo'] ?? 'termos_gerais';
        
        if (empty($titulo) || empty($conteudo)) {
            $mensagem = "❌ Título e conteúdo são obrigatórios!";
            $tipo_mensagem = "danger";
        } else {
            // Obter versão anterior
            $sql_versao = "SELECT MAX(versao) as max_versao FROM termos_servico WHERE tipo = ?";
            $stmt = $conn->prepare($sql_versao);
            $stmt->bind_param("s", $tipo);
            $stmt->execute();
            $result = $stmt->get_result()->fetch_assoc();
            $nova_versao = ($result['max_versao'] ?? 0) + 1;
            
            // Desativar versão anterior
            $sql_desativar = "UPDATE termos_servico SET ativo = 0 WHERE tipo = ? AND ativo = 1";
            $stmt = $conn->prepare($sql_desativar);
            $stmt->bind_param("s", $tipo);
            $stmt->execute();
            
            // Criar novo termo
            $dados = [
                'titulo' => $titulo,
                'conteudo' => $conteudo,
                'tipo' => $tipo,
                'versao' => $nova_versao
            ];
            
            if ($contrato_model->criar_termo($dados)) {
                $mensagem = "✅ Termo criado com sucesso! Versão: " . $nova_versao;
                $tipo_mensagem = "success";
                $acao = 'listar';
            } else {
                $mensagem = "❌ Erro ao criar termo!";
                $tipo_mensagem = "danger";
            }
        }
    }
    
    // Desativar termo
    if ($acao_post === 'desativar') {
        $id = intval($_POST['id']);
        $sql = "UPDATE termos_servico SET ativo = 0 WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $id);
        
        if ($stmt->execute()) {
            $mensagem = "✅ Termo desativado com sucesso!";
            $tipo_mensagem = "success";
            $acao = 'listar';
        } else {
            $mensagem = "❌ Erro ao desativar termo!";
            $tipo_mensagem = "danger";
        }
    }
}

// Obter termos
$sql_termos = "SELECT * FROM termos_servico ORDER BY tipo, versao DESC";
$result_termos = $conn->query($sql_termos);
$termos = $result_termos->fetch_all(MYSQLI_ASSOC);

// Se for editar, obter termo específico
if ($acao === 'editar' && $termo_id) {
    $termo = $contrato_model->obter_termo(intval($termo_id));
}

$conn->close();
?>
<?php require_once '../app/views/admin-layout-header.php'; ?>

                <!-- Page Header -->
                <div class="page-header">
                    <h1><span class="icon">policy</span>Gerenciar Termos de Serviço</h1>
                    <a href="?acao=criar" class="button button-primary">
                        <span class="icon">add</span> Novo Termo
                    </a>
                </div>

                <!-- Mensagens -->
                <?php if (!empty($mensagem)): ?>
                    <div class="alert alert-<?php echo $tipo_mensagem; ?>" style="padding: 15px; border-radius: 8px; margin-bottom: 20px;">
                        <?php echo $mensagem; ?>
                    </div>
                <?php endif; ?>

                    <?php if ($acao === 'criar'): ?>
                        <!-- Formulário Criar Termo -->
                        <div class="card shadow mb-4">
                            <div class="card-header py-3">
                                <h6 class="m-0 font-weight-bold text-primary">Criar Novo Termo</h6>
                            </div>
                            <div class="card-body">
                                <form method="POST">
                                    <input type="hidden" name="acao" value="criar">
                                    
                                    <div class="form-group">
                                        <label for="titulo">Título *</label>
                                        <input type="text" class="form-control" id="titulo" name="titulo" required>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label for="tipo">Tipo de Termo *</label>
                                        <select class="form-control" id="tipo" name="tipo" required>
                                            <option value="termos_gerais">Termos Gerais</option>
                                            <option value="contrato_parceiro">Contrato Parceiro</option>
                                            <option value="contrato_aluno">Contrato Aluno</option>
                                            <option value="politica_privacidade">Política de Privacidade</option>
                                        </select>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label for="conteudo">Conteúdo *</label>
                                        <textarea class="form-control" id="conteudo" name="conteudo" rows="15" required></textarea>
                                        <small class="form-text text-muted">Suporta Markdown</small>
                                    </div>
                                    
                                    <div class="form-group">
                                        <button type="submit" class="btn btn-success">
                                            <i class="fas fa-save"></i> Salvar Termo
                                        </button>
                                        <a href="?acao=listar" class="btn btn-secondary">
                                            <i class="fas fa-times"></i> Cancelar
                                        </a>
                                    </div>
                                </form>
                            </div>
                        </div>
                    <?php else: ?>
                        <!-- Lista de Termos -->
                        <div class="card shadow mb-4">
                            <div class="card-header py-3">
                                <h6 class="m-0 font-weight-bold text-primary">Termos Cadastrados</h6>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-hover">
                                        <thead class="bg-light">
                                            <tr>
                                                <th>Título</th>
                                                <th>Tipo</th>
                                                <th>Versão</th>
                                                <th>Status</th>
                                                <th>Criado em</th>
                                                <th>Ações</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($termos as $t): ?>
                                                <tr>
                                                    <td><?php echo htmlspecialchars(substr($t['titulo'], 0, 40)); ?></td>
                                                    <td>
                                                        <span class="badge badge-info">
                                                            <?php echo ucfirst(str_replace('_', ' ', $t['tipo'])); ?>
                                                        </span>
                                                    </td>
                                                    <td><?php echo $t['versao']; ?></td>
                                                    <td>
                                                        <span class="badge <?php echo $t['ativo'] ? 'badge-success' : 'badge-secondary'; ?>">
                                                            <?php echo $t['ativo'] ? '✅ Ativo' : '❌ Inativo'; ?>
                                                        </span>
                                                    </td>
                                                    <td><?php echo date('d/m/Y H:i', strtotime($t['criado_em'])); ?></td>
                                                    <td>
                                                        <button class="btn btn-sm btn-info" data-toggle="modal" data-target="#modalVisualizar<?php echo $t['id']; ?>">
                                                            <i class="fas fa-eye"></i> Ver
                                                        </button>
                                                        <?php if ($t['ativo']): ?>
                                                            <form method="POST" style="display: inline;">
                                                                <input type="hidden" name="acao" value="desativar">
                                                                <input type="hidden" name="id" value="<?php echo $t['id']; ?>">
                                                                <button type="submit" class="btn btn-sm btn-warning" 
                                                                        onclick="return confirm('Desativar este termo?')">
                                                                    <i class="fas fa-ban"></i> Desativar
                                                                </button>
                                                            </form>
                                                        <?php endif; ?>
                                                    </td>
                                                </tr>

                                                <!-- Modal Visualizar -->
                                                <div class="modal fade" id="modalVisualizar<?php echo $t['id']; ?>" tabindex="-1">
                                                    <div class="modal-dialog modal-lg">
                                                        <div class="modal-content">
                                                            <div class="modal-header">
                                                                <h5 class="modal-title"><?php echo htmlspecialchars($t['titulo']); ?></h5>
                                                                <button type="button" class="close" data-dismiss="modal">
                                                                    <span>&times;</span>
                                                                </button>
                                                            </div>
                                                            <div class="modal-body">
                                                                <p><strong>Versão:</strong> <?php echo $t['versao']; ?></p>
                                                                <p><strong>Tipo:</strong> <?php echo ucfirst(str_replace('_', ' ', $t['tipo'])); ?></p>
                                                                <hr>
                                                                <div style="max-height: 400px; overflow-y: auto;">
                                                                    <?php echo nl2br(htmlspecialchars($t['conteudo'])); ?>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>

<?php require_once '../app/views/admin-layout-footer.php'; ?>

