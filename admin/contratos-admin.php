<?php
/**
 * Gerenciamento de Contratos - Painel Administrativo
 * Visualizar, forçar assinatura e gerenciar termos
 */

require_once '../app/config/config.php';
require_once '../app/models/Contrato.php';

// Verificar autenticação e permissão de admin
if (!isAuthenticated()) {
    redirect('../login.php');
}

if (!hasRole(ROLE_ADMIN)) {
    // Log de tentativa de acesso não autorizado
    error_log("SEGURANÇA: Tentativa de acesso não autorizado a /admin/contratos-admin.php");
    error_log("Usuário: " . ($_SESSION['user_email'] ?? 'desconhecido'));
    error_log("Role: " . ($_SESSION['user_role'] ?? 'desconhecido'));
    redirect('../login.php');
}

$conn = getDBConnection();
$contrato_model = new Contrato($conn);

// Variáveis
$filtro_tipo = $_GET['tipo'] ?? 'todos';
$filtro_status = $_GET['status'] ?? 'todos';
$busca = $_GET['busca'] ?? '';
$mensagem = '';
$tipo_mensagem = '';

// ============================================================
// PROCESSAR AÇÕES
// ============================================================

// Forçar assinatura novamente
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['acao'])) {
    $acao = $_POST['acao'];
    
    if ($acao === 'forcar_assinatura') {
        $usuario_id = intval($_POST['usuario_id']);
        $tipo_usuario = $_POST['tipo_usuario'];
        
        // Marcar termos como não aceitos
        $sql = "UPDATE " . ($tipo_usuario === 'admin' ? 'administradores' : ($tipo_usuario === 'parceiro' ? 'parceiros' : 'alunos')) . " 
                SET termos_aceitos = 0 WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $usuario_id);
        
        if ($stmt->execute()) {
            $mensagem = "✅ Usuário será obrigado a assinar o contrato no próximo acesso!";
            $tipo_mensagem = "success";
        } else {
            $mensagem = "❌ Erro ao forçar assinatura!";
            $tipo_mensagem = "danger";
        }
    }
    
    // Desativar contrato
    if ($acao === 'desativar_contrato') {
        $contrato_id = intval($_POST['contrato_id']);
        $sql = "UPDATE contratos_assinados SET ativo = 0 WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $contrato_id);

        if ($stmt->execute()) {
            $mensagem = "✅ Contrato desativado com sucesso!";
            $tipo_mensagem = "success";
        } else {
            $mensagem = "❌ Erro ao desativar contrato!";
            $tipo_mensagem = "danger";
        }
    }

    // Deletar contrato
    if ($acao === 'deletar_contrato') {
        $contrato_id = intval($_POST['contrato_id']);
        $usuario_id = intval($_POST['usuario_id']);
        $tipo_usuario = $_POST['tipo_usuario'];

        // Primeiro, deletar o contrato
        $sql_delete = "DELETE FROM contratos_assinados WHERE id = ?";
        $stmt_delete = $conn->prepare($sql_delete);
        $stmt_delete->bind_param("i", $contrato_id);

        if ($stmt_delete->execute()) {
            // Depois, marcar termos como não aceitos para forçar assinatura novamente
            $tabela = ($tipo_usuario === 'admin' ? 'administradores' : ($tipo_usuario === 'parceiro' ? 'parceiros' : 'alunos'));
            $sql_update = "UPDATE $tabela SET termos_aceitos = 0 WHERE id = ?";
            $stmt_update = $conn->prepare($sql_update);
            $stmt_update->bind_param("i", $usuario_id);

            if ($stmt_update->execute()) {
                $mensagem = "✅ Contrato deletado com sucesso! Usuário será obrigado a assinar novamente no próximo acesso.";
                $tipo_mensagem = "success";
            } else {
                $mensagem = "⚠️ Contrato deletado, mas erro ao marcar termos como não aceitos!";
                $tipo_mensagem = "warning";
            }
        } else {
            $mensagem = "❌ Erro ao deletar contrato!";
            $tipo_mensagem = "danger";
        }
    }
}

// ============================================================
// OBTER DADOS
// ============================================================

// Contar usuários por status
// Apenas Parceiros precisam aceitar termos
$stats = [];

// Stats Parceiro (APENAS PARCEIROS)
$sql_parceiro = "
    SELECT
        COUNT(DISTINCT p.id) as total,
        COUNT(DISTINCT CASE WHEN p.termos_aceitos = 1 AND ca.id IS NOT NULL THEN p.id END) as aceitos,
        COUNT(DISTINCT CASE WHEN p.termos_aceitos = 0 OR ca.id IS NULL THEN p.id END) as nao_aceitos
    FROM parceiros p
    LEFT JOIN contratos_assinados ca ON p.id = ca.usuario_id AND ca.tipo_usuario = 'parceiro' AND ca.ativo = 1
";
$result = $conn->query($sql_parceiro);
if ($result) {
    $row = $result->fetch_assoc();
    $stats['parceiro'] = ['tipo' => 'parceiro', 'total' => $row['total'], 'aceitos' => $row['aceitos'] ?? 0, 'nao_aceitos' => $row['nao_aceitos'] ?? 0];
}

// Obter contratos assinados com filtros
$contratos = [];

// Query com LEFT JOIN para obter dados do usuário
// APENAS PARCEIROS
$sql_contratos = "
    SELECT
        ca.*,
        ts.titulo,
        ts.tipo as tipo_termo,
        ts.versao,
        COALESCE(p.nome_empresa, 'Usuário Deletado') as nome_usuario,
        COALESCE(p.email, 'N/A') as email_usuario,
        CASE
            WHEN p.id IS NOT NULL THEN 'parceiro'
            ELSE 'deletado'
        END as status_usuario
    FROM contratos_assinados ca
    JOIN termos_servico ts ON ca.termo_id = ts.id
    LEFT JOIN parceiros p ON ca.usuario_id = p.id AND ca.tipo_usuario = 'parceiro'
    WHERE ca.tipo_usuario = 'parceiro'
";

if ($filtro_status !== 'todos') {
    $status_val = ($filtro_status === 'ativo') ? 1 : 0;
    $sql_contratos .= " AND ca.ativo = " . $status_val;
}

$sql_contratos .= " ORDER BY ca.data_assinatura DESC LIMIT 100";

$result_contratos = $conn->query($sql_contratos);
if ($result_contratos) {
    while ($row = $result_contratos->fetch_assoc()) {
        $contratos[] = $row;
    }
}

// Obter usuários que NÃO aceitaram termos
// Apenas Parceiros
$usuarios_nao_aceitos = [];

// Parceiro não aceitos (APENAS PARCEIROS)
$sql_parceiro_nao = "
    SELECT DISTINCT p.id, p.nome_empresa as nome, p.email, 'parceiro' as tipo
    FROM parceiros p
    LEFT JOIN contratos_assinados ca ON p.id = ca.usuario_id AND ca.tipo_usuario = 'parceiro' AND ca.ativo = 1
    WHERE p.termos_aceitos = 0 OR ca.id IS NULL
    ORDER BY p.nome_empresa
";
$result = $conn->query($sql_parceiro_nao);
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $usuarios_nao_aceitos[] = $row;
    }
}

$conn->close();
?>
<?php require_once '../app/views/admin-layout-header.php'; ?>

                <!-- Page Header -->
                <div class="page-header">
                    <h1><span class="icon">description</span>Gerenciar Contratos</h1>
                </div>

                <!-- Mensagens -->
                <?php if (!empty($mensagem)): ?>
                    <div class="alert alert-<?php echo $tipo_mensagem; ?>" style="padding: 15px; border-radius: 8px; margin-bottom: 20px;">
                        <?php echo $mensagem; ?>
                    </div>
                <?php endif; ?>

                <!-- Estatísticas -->
                <div class="stats-grid" style="grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); margin-bottom: 24px;">
                    <?php foreach ($stats as $tipo => $stat): ?>
                        <div class="card stat-card">
                            <div class="stat-label"><?php echo ucfirst($tipo); ?>s</div>
                            <div class="stat-value"><?php echo $stat['total'] ?? 0; ?></div>
                            <div style="display: flex; gap: 10px; margin-top: 10px;">
                                <span class="badge badge-success">✅ <?php echo $stat['aceitos'] ?? 0; ?> Aceitos</span>
                                <span class="badge badge-danger">❌ <?php echo $stat['nao_aceitos'] ?? 0; ?> Pendentes</span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                    <!-- Usuários que NÃO aceitaram -->
                    <div class="card shadow mb-4">
                        <div class="card-header py-3">
                            <h6 class="m-0 font-weight-bold text-danger">
                                ⚠️ Usuários que NÃO aceitaram os termos (<?php echo count($usuarios_nao_aceitos); ?>)
                            </h6>
                        </div>
                        <div class="card-body">
                            <?php if (empty($usuarios_nao_aceitos)): ?>
                                <p class="text-success">✅ Todos os usuários aceitaram os termos!</p>
                            <?php else: ?>
                                <div class="table-responsive">
                                    <table class="table table-hover">
                                        <thead class="bg-light">
                                            <tr>
                                                <th>Nome</th>
                                                <th>Email</th>
                                                <th>Tipo</th>
                                                <th>Ações</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($usuarios_nao_aceitos as $usuario): ?>
                                                <tr>
                                                    <td><?php echo htmlspecialchars($usuario['nome']); ?></td>
                                                    <td><?php echo htmlspecialchars($usuario['email']); ?></td>
                                                    <td>
                                                        <span class="badge badge-warning">
                                                            <?php echo ucfirst($usuario['tipo']); ?>
                                                        </span>
                                                    </td>
                                                    <td>
                                                        <form method="POST" style="display: inline;">
                                                            <input type="hidden" name="acao" value="forcar_assinatura">
                                                            <input type="hidden" name="usuario_id" value="<?php echo $usuario['id']; ?>">
                                                            <input type="hidden" name="tipo_usuario" value="<?php echo $usuario['tipo']; ?>">
                                                            <button type="submit" class="btn btn-sm btn-warning" 
                                                                    onclick="return confirm('Forçar assinatura novamente?')">
                                                                <i class="fas fa-redo"></i> Forçar Assinatura
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

                    <!-- Contratos Assinados -->
                    <div class="card shadow mb-4">
                        <div class="card-header py-3">
                            <h6 class="m-0 font-weight-bold text-primary">✅ Contratos Assinados</h6>
                        </div>
                        <div class="card-body">
                            <!-- Filtros -->
                            <form method="GET" class="mb-3">
                                <div class="row">
                                    <div class="col-md-4">
                                        <select name="status" class="form-control" onchange="this.form.submit()">
                                            <option value="todos">Todos os Status</option>
                                            <option value="ativo" <?php echo $filtro_status === 'ativo' ? 'selected' : ''; ?>>Ativo</option>
                                            <option value="inativo" <?php echo $filtro_status === 'inativo' ? 'selected' : ''; ?>>Inativo</option>
                                        </select>
                                    </div>
                                    <div class="col-md-5">
                                        <input type="text" name="busca" class="form-control" placeholder="Buscar por nome ou email..." value="<?php echo htmlspecialchars($busca); ?>">
                                    </div>
                                    <div class="col-md-3">
                                        <button type="submit" class="btn btn-primary btn-block">
                                            <i class="fas fa-search"></i> Buscar
                                        </button>
                                    </div>
                                </div>
                            </form>

                            <!-- Tabela -->
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead class="bg-light">
                                        <tr>
                                            <th>Usuário</th>
                                            <th>Email</th>
                                            <th>Tipo</th>
                                            <th>Contrato</th>
                                            <th>Data Assinatura</th>
                                            <th>IP</th>
                                            <th>Status</th>
                                            <th>Ações</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($contratos as $contrato): ?>
                                            <tr <?php echo $contrato['status_usuario'] === 'deletado' ? 'style="background-color: #f8d7da;"' : ''; ?>>
                                                <td>
                                                    <?php echo htmlspecialchars($contrato['nome_usuario']); ?>
                                                    <?php if ($contrato['status_usuario'] === 'deletado'): ?>
                                                        <span class="badge badge-danger ml-2">🗑️ DELETADO</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td><?php echo htmlspecialchars($contrato['email_usuario']); ?></td>
                                                <td>
                                                    <span class="badge badge-info">
                                                        <?php echo ucfirst($contrato['tipo_usuario']); ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <small><?php echo htmlspecialchars(substr($contrato['titulo'], 0, 30)); ?>...</small>
                                                </td>
                                                <td><?php echo date('d/m/Y H:i', strtotime($contrato['data_assinatura'])); ?></td>
                                                <td><small><?php echo htmlspecialchars($contrato['ip_assinatura']); ?></small></td>
                                                <td>
                                                    <span class="badge <?php echo $contrato['ativo'] ? 'badge-success' : 'badge-secondary'; ?>">
                                                        <?php echo $contrato['ativo'] ? '✅ Ativo' : '❌ Inativo'; ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <button class="btn btn-sm btn-info" data-toggle="modal" data-target="#modalDetalhes<?php echo $contrato['id']; ?>">
                                                        <i class="fas fa-eye"></i> Ver
                                                    </button>
                                                    <form method="POST" style="display:inline;" onsubmit="return confirm('Tem certeza que deseja deletar este contrato?');">
                                                        <input type="hidden" name="acao" value="deletar_contrato">
                                                        <input type="hidden" name="contrato_id" value="<?php echo $contrato['id']; ?>">
                                                        <input type="hidden" name="usuario_id" value="<?php echo $contrato['usuario_id']; ?>">
                                                        <input type="hidden" name="tipo_usuario" value="<?php echo $contrato['tipo_usuario']; ?>">
                                                        <button type="submit" class="btn btn-sm btn-danger">
                                                            <i class="fas fa-trash"></i> Deletar
                                                        </button>
                                                    </form>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Modais (Fora da tabela) -->
            <?php foreach ($contratos as $contrato): ?>
                <!-- Modal Detalhes Contrato -->
                <div class="modal fade" id="modalDetalhes<?php echo $contrato['id']; ?>" tabindex="-1" role="dialog">
                                                <div class="modal-dialog modal-lg" role="document">
                                                    <div class="modal-content">
                                                        <div class="modal-header">
                                                            <h5 class="modal-title">📋 Detalhes do Contrato</h5>
                                                            <button type="button" class="close" data-dismiss="modal">
                                                                <span>&times;</span>
                                                            </button>
                                                        </div>
                                                        <div class="modal-body">
                                                            <!-- Informações do Usuário -->
                                                            <div class="card mb-3">
                                                                <div class="card-header bg-light">
                                                                    <h6 class="m-0"><i class="fas fa-user"></i> Informações do Usuário</h6>
                                                                </div>
                                                                <div class="card-body">
                                                                    <div class="row">
                                                                        <div class="col-md-6">
                                                                            <strong>👤 Usuário:</strong><br>
                                                                            <?php echo htmlspecialchars($contrato['nome_usuario']); ?>
                                                                        </div>
                                                                        <div class="col-md-6">
                                                                            <strong>📧 Email:</strong><br>
                                                                            <?php echo htmlspecialchars($contrato['email_usuario']); ?>
                                                                        </div>
                                                                    </div>
                                                                    <div class="row mt-2">
                                                                        <div class="col-md-6">
                                                                            <strong>👥 Tipo de Usuário:</strong><br>
                                                                            <span class="badge badge-info"><?php echo ucfirst($contrato['tipo_usuario']); ?></span>
                                                                        </div>
                                                                        <div class="col-md-6">
                                                                            <strong>📋 Tipo de Termo:</strong><br>
                                                                            <span class="badge badge-secondary"><?php echo ucfirst(str_replace('_', ' ', $contrato['tipo_termo'])); ?></span>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            </div>

                                                            <!-- Informações do Contrato -->
                                                            <div class="card mb-3">
                                                                <div class="card-header bg-light">
                                                                    <h6 class="m-0"><i class="fas fa-file-contract"></i> Informações do Contrato</h6>
                                                                </div>
                                                                <div class="card-body">
                                                                    <div class="row">
                                                                        <div class="col-md-8">
                                                                            <strong>📄 Contrato:</strong><br>
                                                                            <?php echo htmlspecialchars($contrato['titulo']); ?>
                                                                        </div>
                                                                        <div class="col-md-4">
                                                                            <strong>🔢 Versão:</strong><br>
                                                                            <span class="badge badge-primary">v<?php echo $contrato['versao']; ?></span>
                                                                        </div>
                                                                    </div>
                                                                    <div class="row mt-2">
                                                                        <div class="col-md-6">
                                                                            <strong>📅 Data de Assinatura:</strong><br>
                                                                            <?php echo date('d/m/Y H:i:s', strtotime($contrato['data_assinatura'])); ?>
                                                                        </div>
                                                                        <div class="col-md-6">
                                                                            <strong>✅ Status:</strong><br>
                                                                            <span class="badge <?php echo $contrato['ativo'] ? 'badge-success' : 'badge-secondary'; ?>">
                                                                                <?php echo $contrato['ativo'] ? '✅ Ativo' : '❌ Inativo'; ?>
                                                                            </span>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            </div>

                                                            <!-- Informações de Auditoria -->
                                                            <div class="card mb-3">
                                                                <div class="card-header bg-light">
                                                                    <h6 class="m-0"><i class="fas fa-shield-alt"></i> Informações de Auditoria</h6>
                                                                </div>
                                                                <div class="card-body">
                                                                    <div class="row">
                                                                        <div class="col-md-12">
                                                                            <strong>🌐 IP de Assinatura:</strong><br>
                                                                            <code style="background-color: #f5f5f5; padding: 8px; border-radius: 4px; display: inline-block;">
                                                                                <?php echo htmlspecialchars($contrato['ip_assinatura']); ?>
                                                                            </code>
                                                                        </div>
                                                                    </div>
                                                                    <div class="row mt-2">
                                                                        <div class="col-md-12">
                                                                            <strong>🖥️ User Agent:</strong><br>
                                                                            <small><code style="background-color: #f5f5f5; padding: 8px; border-radius: 4px; display: block; word-break: break-all;">
                                                                                <?php echo htmlspecialchars($contrato['user_agent']); ?>
                                                                            </code></small>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            </div>

                                                            <!-- Assinatura Digital -->
                                                            <?php if (!empty($contrato['assinatura_digital'])): ?>
                                                                <div class="card mb-3">
                                                                    <div class="card-header bg-light">
                                                                        <h6 class="m-0"><i class="fas fa-pen-fancy"></i> Assinatura Digital</h6>
                                                                    </div>
                                                                    <div class="card-body text-center">
                                                                        <img src="<?php echo $contrato['assinatura_digital']; ?>" alt="Assinatura" style="max-width: 100%; max-height: 200px; border: 2px solid #ddd; padding: 10px; border-radius: 4px;">
                                                                    </div>
                                                                </div>
                                                            <?php else: ?>
                                                                <div class="alert alert-warning">
                                                                    <i class="fas fa-exclamation-triangle"></i> Nenhuma assinatura digital registrada
                                                                </div>
                                                            <?php endif; ?>
                                                        </div>
                                                        <div class="modal-footer">
                                                            <button type="button" class="btn btn-secondary" data-dismiss="modal">Fechar</button>

                                                            <!-- Botão Forçar Assinatura -->
                                                            <form method="POST" style="display: inline;">
                                                                <input type="hidden" name="acao" value="forcar_assinatura">
                                                                <input type="hidden" name="usuario_id" value="<?php echo $contrato['usuario_id']; ?>">
                                                                <input type="hidden" name="tipo_usuario" value="<?php echo $contrato['tipo_usuario']; ?>">
                                                                <button type="submit" class="btn btn-warning" onclick="return confirm('Tem certeza que deseja forçar o usuário a assinar novamente?');">
                                                                    <i class="fas fa-redo"></i> Forçar Assinatura
                                                                </button>
                                                            </form>

                                                            <!-- Botão Deletar Contrato -->
                                                            <form method="POST" style="display: inline;">
                                                                <input type="hidden" name="acao" value="deletar_contrato">
                                                                <input type="hidden" name="contrato_id" value="<?php echo $contrato['id']; ?>">
                                                                <input type="hidden" name="usuario_id" value="<?php echo $contrato['usuario_id']; ?>">
                                                                <input type="hidden" name="tipo_usuario" value="<?php echo $contrato['tipo_usuario']; ?>">
                                                                <button type="submit" class="btn btn-danger" onclick="return confirm('⚠️ ATENÇÃO!\n\nVocê está prestes a DELETAR este contrato.\n\nO usuário será obrigado a assinar novamente no próximo acesso.\n\nTem certeza?');">
                                                                    <i class="fas fa-trash"></i> Deletar Contrato
                                                                </button>
                                                            </form>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>


<script>
// Função para abrir/fechar modais
function abrirModal(id) {
    document.getElementById(id).style.display = 'block';
}
function fecharModal(id) {
    document.getElementById(id).style.display = 'none';
}
</script>

<?php require_once '../app/views/admin-layout-footer.php'; ?>

