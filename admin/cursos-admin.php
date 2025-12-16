<?php
/**
 * ============================================================================
 * GERENCIAR CURSOS - SISTEMA DE CERTIFICADOS
 * ============================================================================
 */

require_once '../app/config/config.php';

// Verificar autenticação e permissão
if (!isAuthenticated() || !hasRole(ROLE_ADMIN)) {
    redirect(APP_URL . '/login.php');
}

$page_title = 'Cursos - ' . APP_NAME;
$conn = getDBConnection();

// ============================================================================
// PAGINAÇÃO E FILTROS
// ============================================================================
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$per_page = isset($_GET['per_page']) ? (int)$_GET['per_page'] : 20;
$page = max(1, $page);
$offset = ($page - 1) * $per_page;

// Filtros
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$status_filter = isset($_GET['status']) ? trim($_GET['status']) : '';
$parceiro_filter = isset($_GET['parceiro']) ? (int)$_GET['parceiro'] : 0;
$order_by = isset($_GET['order_by']) ? $_GET['order_by'] : 'criado_em';
$order_dir = isset($_GET['order_dir']) && $_GET['order_dir'] === 'ASC' ? 'ASC' : 'DESC';

// Validar order_by
$allowed_order = ['criado_em', 'nome', 'carga_horaria', 'total_certificados', 'ativo'];
if (!in_array($order_by, $allowed_order)) {
    $order_by = 'criado_em';
}

// Construir WHERE clause
$where_conditions = [];
$params = [];
$types = '';

if (!empty($search)) {
    $where_conditions[] = "(c.nome LIKE ? OR c.descricao LIKE ?)";
    $search_param = "%{$search}%";
    $params[] = $search_param;
    $params[] = $search_param;
    $types .= 'ss';
}

if ($status_filter !== '') {
    $where_conditions[] = "c.ativo = ?";
    $params[] = (int)$status_filter;
    $types .= 'i';
}

if ($parceiro_filter > 0) {
    $where_conditions[] = "c.parceiro_id = ?";
    $params[] = $parceiro_filter;
    $types .= 'i';
}

$where_sql = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';

// Contar total de registros
$count_sql = "
    SELECT COUNT(DISTINCT c.id) as total
    FROM cursos c
    LEFT JOIN parceiros p ON c.parceiro_id = p.id
    LEFT JOIN certificados cert ON c.id = cert.curso_id
    {$where_sql}
";

$stmt = $conn->prepare($count_sql);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$total_result = $stmt->get_result();
$total_records = $total_result->fetch_assoc()['total'];
$total_pages = ceil($total_records / $per_page);

// Buscar cursos
$cursos = [];
$sql = "
    SELECT
        c.id,
        c.nome,
        c.carga_horaria,
        c.descricao,
        c.ativo,
        c.criado_em,
        p.nome_empresa as parceiro_nome,
        COUNT(DISTINCT cert.id) as total_certificados
    FROM cursos c
    LEFT JOIN parceiros p ON c.parceiro_id = p.id
    LEFT JOIN certificados cert ON c.id = cert.curso_id
    {$where_sql}
    GROUP BY c.id
    ORDER BY {$order_by} {$order_dir}
    LIMIT ? OFFSET ?
";

$params[] = $per_page;
$params[] = $offset;
$types .= 'ii';

$stmt = $conn->prepare($sql);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();

if ($result) {
    while ($row = $result->fetch_assoc()) {
        $cursos[] = $row;
    }
}

// Buscar estatísticas
$stats = [];
$result = $conn->query("
    SELECT
        COUNT(*) as total,
        SUM(CASE WHEN ativo = 1 THEN 1 ELSE 0 END) as ativos,
        SUM(CASE WHEN ativo = 0 THEN 1 ELSE 0 END) as inativos
    FROM cursos
");

if ($result) {
    $stats = $result->fetch_assoc();
}

// Buscar parceiros para o formulário
$parceiros = [];
$result = $conn->query("
    SELECT id, nome_empresa
    FROM parceiros
    WHERE ativo = 1
    ORDER BY nome_empresa ASC
");

if ($result) {
    while ($row = $result->fetch_assoc()) {
        $parceiros[] = $row;
    }
}
?>
<?php require_once '../app/views/admin-layout-header.php'; ?>

                <!-- Mensagens de Feedback -->
                <?php if (isset($_SESSION['success'])): ?>
                    <div class="alert alert-success">
                        <span class="icon">check_circle</span>
                        <?php echo htmlspecialchars($_SESSION['success']); ?>
                    </div>
                    <?php unset($_SESSION['success']); ?>
                <?php endif; ?>

                <?php if (isset($_SESSION['error'])): ?>
                    <div class="alert alert-danger">
                        <span class="icon">error</span>
                        <?php echo htmlspecialchars($_SESSION['error']); ?>
                    </div>
                    <?php unset($_SESSION['error']); ?>
                <?php endif; ?>

                <!-- Estatísticas -->
                <div class="stats-grid" style="grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); margin-bottom: 24px;">
                    <div class="card stat-card">
                        <span class="icon">school</span>
                        <div class="stat-label">Total de Cursos</div>
                        <div class="stat-value"><?php echo number_format($stats['total'] ?? 0, 0, ',', '.'); ?></div>
                        <div class="stat-change">Cadastrados</div>
                    </div>
                    <div class="card stat-card">
                        <span class="icon" style="color: #34C759;">check_circle</span>
                        <div class="stat-label">Cursos Ativos</div>
                        <div class="stat-value"><?php echo number_format($stats['ativos'] ?? 0, 0, ',', '.'); ?></div>
                        <div class="stat-change">Disponíveis</div>
                    </div>
                    <div class="card stat-card">
                        <span class="icon" style="color: #FF9500;">block</span>
                        <div class="stat-label">Cursos Inativos</div>
                        <div class="stat-value"><?php echo number_format($stats['inativos'] ?? 0, 0, ',', '.'); ?></div>
                        <div class="stat-change">Desativados</div>
                    </div>
                </div>

                <!-- Page Header -->
                <div class="page-header">
                    <h1>Gerenciar Cursos</h1>
                    <div class="action-buttons">
                        <button class="button button-primary" onclick="document.getElementById('novoCurso').style.display='block'">
                            <span class="icon">add</span> Novo Curso
                        </button>
                    </div>
                </div>

                <!-- Filtros de Busca -->
                <section class="filter-section" style="margin-bottom: 24px;">
                    <div class="card">
                        <form method="GET" action="" style="display: grid; grid-template-columns: 2fr 1fr 1fr 1fr auto; gap: 12px; align-items: end;">
                            <div class="form-group" style="margin-bottom: 0;">
                                <label for="search">Buscar</label>
                                <input type="text" id="search" name="search" class="form-control" placeholder="Nome ou descrição do curso" value="<?php echo htmlspecialchars($search); ?>">
                            </div>
                            <div class="form-group" style="margin-bottom: 0;">
                                <label for="status">Status</label>
                                <select id="status" name="status" class="form-control">
                                    <option value="">Todos</option>
                                    <option value="1" <?php echo $status_filter === '1' ? 'selected' : ''; ?>>Ativo</option>
                                    <option value="0" <?php echo $status_filter === '0' ? 'selected' : ''; ?>>Inativo</option>
                                </select>
                            </div>
                            <div class="form-group" style="margin-bottom: 0;">
                                <label for="parceiro">Parceiro</label>
                                <select id="parceiro" name="parceiro" class="form-control">
                                    <option value="">Todos</option>
                                    <?php foreach ($parceiros as $p): ?>
                                        <option value="<?php echo $p['id']; ?>" <?php echo $parceiro_filter == $p['id'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($p['nome_empresa']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="form-group" style="margin-bottom: 0;">
                                <label for="per_page">Por Página</label>
                                <select id="per_page" name="per_page" class="form-control">
                                    <option value="10" <?php echo $per_page == 10 ? 'selected' : ''; ?>>10</option>
                                    <option value="20" <?php echo $per_page == 20 ? 'selected' : ''; ?>>20</option>
                                    <option value="50" <?php echo $per_page == 50 ? 'selected' : ''; ?>>50</option>
                                    <option value="100" <?php echo $per_page == 100 ? 'selected' : ''; ?>>100</option>
                                </select>
                            </div>
                            <div style="display: flex; gap: 8px;">
                                <button type="submit" class="button button-primary" style="min-width: auto; padding: 10px 20px;">
                                    <span class="icon">search</span> Filtrar
                                </button>
                                <a href="?" class="button button-secondary" style="min-width: auto; padding: 10px 20px;">
                                    <span class="icon">refresh</span>
                                </a>
                            </div>
                        </form>
                    </div>
                </section>

                <!-- Tabela de Cursos -->
                <section class="table-section">
                    <div class="card">
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px;">
                            <h2><span class="icon">school</span>Lista de Cursos</h2>
                            <div style="color: var(--text-medium); font-size: 0.9rem;">
                                Mostrando <?php echo number_format(min($offset + 1, $total_records)); ?> - <?php echo number_format(min($offset + $per_page, $total_records)); ?> de <?php echo number_format($total_records); ?> registros
                            </div>
                        </div>
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>
                                            <a href="?page=<?php echo $page; ?>&per_page=<?php echo $per_page; ?>&search=<?php echo urlencode($search); ?>&status=<?php echo urlencode($status_filter); ?>&parceiro=<?php echo $parceiro_filter; ?>&order_by=nome&order_dir=<?php echo ($order_by === 'nome' && $order_dir === 'ASC') ? 'DESC' : 'ASC'; ?>" style="color: inherit; text-decoration: none; display: flex; align-items: center; gap: 4px;">
                                                Nome do Curso
                                                <?php if ($order_by === 'nome'): ?>
                                                    <span class="icon" style="font-size: 16px;"><?php echo $order_dir === 'ASC' ? 'arrow_upward' : 'arrow_downward'; ?></span>
                                                <?php endif; ?>
                                            </a>
                                        </th>
                                        <th>
                                            <a href="?page=<?php echo $page; ?>&per_page=<?php echo $per_page; ?>&search=<?php echo urlencode($search); ?>&status=<?php echo urlencode($status_filter); ?>&parceiro=<?php echo $parceiro_filter; ?>&order_by=carga_horaria&order_dir=<?php echo ($order_by === 'carga_horaria' && $order_dir === 'ASC') ? 'DESC' : 'ASC'; ?>" style="color: inherit; text-decoration: none; display: flex; align-items: center; gap: 4px;">
                                                Carga Horária
                                                <?php if ($order_by === 'carga_horaria'): ?>
                                                    <span class="icon" style="font-size: 16px;"><?php echo $order_dir === 'ASC' ? 'arrow_upward' : 'arrow_downward'; ?></span>
                                                <?php endif; ?>
                                            </a>
                                        </th>
                                        <th>Parceiro</th>
                                        <th>
                                            <a href="?page=<?php echo $page; ?>&per_page=<?php echo $per_page; ?>&search=<?php echo urlencode($search); ?>&status=<?php echo urlencode($status_filter); ?>&parceiro=<?php echo $parceiro_filter; ?>&order_by=total_certificados&order_dir=<?php echo ($order_by === 'total_certificados' && $order_dir === 'ASC') ? 'DESC' : 'ASC'; ?>" style="color: inherit; text-decoration: none; display: flex; align-items: center; gap: 4px;">
                                                Certificados Emitidos
                                                <?php if ($order_by === 'total_certificados'): ?>
                                                    <span class="icon" style="font-size: 16px;"><?php echo $order_dir === 'ASC' ? 'arrow_upward' : 'arrow_downward'; ?></span>
                                                <?php endif; ?>
                                            </a>
                                        </th>
                                        <th>
                                            <a href="?page=<?php echo $page; ?>&per_page=<?php echo $per_page; ?>&search=<?php echo urlencode($search); ?>&status=<?php echo urlencode($status_filter); ?>&parceiro=<?php echo $parceiro_filter; ?>&order_by=ativo&order_dir=<?php echo ($order_by === 'ativo' && $order_dir === 'ASC') ? 'DESC' : 'ASC'; ?>" style="color: inherit; text-decoration: none; display: flex; align-items: center; gap: 4px;">
                                                Status
                                                <?php if ($order_by === 'ativo'): ?>
                                                    <span class="icon" style="font-size: 16px;"><?php echo $order_dir === 'ASC' ? 'arrow_upward' : 'arrow_downward'; ?></span>
                                                <?php endif; ?>
                                            </a>
                                        </th>
                                        <th>
                                            <a href="?page=<?php echo $page; ?>&per_page=<?php echo $per_page; ?>&search=<?php echo urlencode($search); ?>&status=<?php echo urlencode($status_filter); ?>&parceiro=<?php echo $parceiro_filter; ?>&order_by=criado_em&order_dir=<?php echo ($order_by === 'criado_em' && $order_dir === 'ASC') ? 'DESC' : 'ASC'; ?>" style="color: inherit; text-decoration: none; display: flex; align-items: center; gap: 4px;">
                                                Data Criação
                                                <?php if ($order_by === 'criado_em'): ?>
                                                    <span class="icon" style="font-size: 16px;"><?php echo $order_dir === 'ASC' ? 'arrow_upward' : 'arrow_downward'; ?></span>
                                                <?php endif; ?>
                                            </a>
                                        </th>
                                        <th>Ações</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (!empty($cursos)): ?>
                                        <?php foreach ($cursos as $curso): ?>
                                            <tr>
                                                <td><strong><?php echo htmlspecialchars($curso['nome']); ?></strong></td>
                                                <td><?php echo $curso['carga_horaria']; ?>h</td>
                                                <td><?php echo htmlspecialchars($curso['parceiro_nome'] ?? 'Todos'); ?></td>
                                                <td><?php echo $curso['total_certificados']; ?></td>
                                                <td>
                                                    <span class="badge badge-<?php echo ($curso['ativo'] == 1) ? 'success' : 'danger'; ?>">
                                                        <?php echo ($curso['ativo'] == 1) ? 'Ativo' : 'Inativo'; ?>
                                                    </span>
                                                </td>
                                                <td><?php echo date('d/m/Y', strtotime($curso['criado_em'])); ?></td>
                                                <td>
                                                    <button class="button button-secondary" style="min-width:auto;padding:6px 12px;font-size:.85rem;margin-right:4px;" title="Editar" onclick="editarCurso(<?php echo htmlspecialchars(json_encode($curso)); ?>)">
                                                        <span class="icon" style="margin:0;font-size:16px;">edit</span>
                                                    </button>
                                                    <a href="../app/actions/deletar-curso.php?id=<?php echo $curso['id']; ?>" class="button button-secondary" style="min-width:auto;padding:6px 12px;font-size:.85rem;background:#f8d7da;border-color:#f5c6cb;" title="Deletar" onclick="return confirm('Tem certeza que deseja deletar este curso?')">
                                                        <span class="icon" style="margin:0;font-size:16px;color:#721c24;">delete</span>
                                                    </a>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="7" style="text-align: center; color: var(--text-medium);">Nenhum curso cadastrado.</td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>

                        <!-- Paginação -->
                        <?php if ($total_pages > 1): ?>
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 20px; padding-top: 20px; border-top: 1px solid var(--border-color);">
                            <div style="color: var(--text-medium); font-size: 0.9rem;">
                                Página <?php echo $page; ?> de <?php echo $total_pages; ?>
                            </div>
                            <div style="display: flex; gap: 8px; flex-wrap: wrap;">
                                <?php if ($page > 1): ?>
                                    <a href="?page=1&per_page=<?php echo $per_page; ?>&search=<?php echo urlencode($search); ?>&status=<?php echo urlencode($status_filter); ?>&parceiro=<?php echo $parceiro_filter; ?>&order_by=<?php echo $order_by; ?>&order_dir=<?php echo $order_dir; ?>" class="button button-secondary" style="min-width: auto; padding: 8px 12px;">
                                        <span class="icon">first_page</span>
                                    </a>
                                    <a href="?page=<?php echo $page - 1; ?>&per_page=<?php echo $per_page; ?>&search=<?php echo urlencode($search); ?>&status=<?php echo urlencode($status_filter); ?>&parceiro=<?php echo $parceiro_filter; ?>&order_by=<?php echo $order_by; ?>&order_dir=<?php echo $order_dir; ?>" class="button button-secondary" style="min-width: auto; padding: 8px 12px;">
                                        <span class="icon">chevron_left</span>
                                    </a>
                                <?php endif; ?>

                                <?php
                                $start_page = max(1, $page - 2);
                                $end_page = min($total_pages, $page + 2);

                                for ($i = $start_page; $i <= $end_page; $i++):
                                ?>
                                    <a href="?page=<?php echo $i; ?>&per_page=<?php echo $per_page; ?>&search=<?php echo urlencode($search); ?>&status=<?php echo urlencode($status_filter); ?>&parceiro=<?php echo $parceiro_filter; ?>&order_by=<?php echo $order_by; ?>&order_dir=<?php echo $order_dir; ?>" class="button <?php echo $i === $page ? 'button-primary' : 'button-secondary'; ?>" style="min-width: auto; padding: 8px 16px;">
                                        <?php echo $i; ?>
                                    </a>
                                <?php endfor; ?>

                                <?php if ($page < $total_pages): ?>
                                    <a href="?page=<?php echo $page + 1; ?>&per_page=<?php echo $per_page; ?>&search=<?php echo urlencode($search); ?>&status=<?php echo urlencode($status_filter); ?>&parceiro=<?php echo $parceiro_filter; ?>&order_by=<?php echo $order_by; ?>&order_dir=<?php echo $order_dir; ?>" class="button button-secondary" style="min-width: auto; padding: 8px 12px;">
                                        <span class="icon">chevron_right</span>
                                    </a>
                                    <a href="?page=<?php echo $total_pages; ?>&per_page=<?php echo $per_page; ?>&search=<?php echo urlencode($search); ?>&status=<?php echo urlencode($status_filter); ?>&parceiro=<?php echo $parceiro_filter; ?>&order_by=<?php echo $order_by; ?>&order_dir=<?php echo $order_dir; ?>" class="button button-secondary" style="min-width: auto; padding: 8px 12px;">
                                        <span class="icon">last_page</span>
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                </section>

<?php require_once '../app/views/admin-layout-footer.php'; ?>

<!-- Modal Novo Curso -->
<div class="modal" id="novoCurso" style="background-color: rgba(0, 0, 0, 0.6);">
    <div class="modal-content" style="max-width: 600px; margin: 5% auto; max-height: 90vh; overflow-y: auto; box-shadow: 0 10px 40px rgba(0, 0, 0, 0.3);">
        <div class="modal-header">
            <h2>Novo Curso</h2>
            <button class="close" onclick="document.getElementById('novoCurso').style.display='none'">&times;</button>
        </div>
        <form method="POST" action="../app/actions/criar-curso.php">
            <div class="modal-body">
                <div class="form-group">
                    <label for="nome">Nome do Curso *</label>
                    <input type="text" id="nome" name="nome" required>
                </div>
                <div class="form-group">
                    <label for="carga_horaria">Carga Horária (horas) *</label>
                    <input type="number" id="carga_horaria" name="carga_horaria" min="1" required>
                </div>
                <div class="form-group">
                    <label for="parceiro_id">Parceiro (opcional)</label>
                    <select id="parceiro_id" name="parceiro_id">
                        <option value="">Todos os parceiros</option>
                        <?php foreach ($parceiros as $parceiro): ?>
                            <option value="<?php echo $parceiro['id']; ?>">
                                <?php echo htmlspecialchars($parceiro['nome_empresa']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label for="descricao">Descrição</label>
                    <textarea id="descricao" name="descricao"></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="button button-secondary" onclick="document.getElementById('novoCurso').style.display='none'">Cancelar</button>
                <button type="submit" class="button button-primary">Criar Curso</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Editar Curso -->
<div class="modal" id="editarCurso" style="background-color: rgba(0, 0, 0, 0.6);">
    <div class="modal-content" style="max-width: 600px; margin: 5% auto; max-height: 90vh; overflow-y: auto; box-shadow: 0 10px 40px rgba(0, 0, 0, 0.3);">
        <div class="modal-header">
            <h2>Editar Curso</h2>
            <button class="close" onclick="document.getElementById('editarCurso').style.display='none'">&times;</button>
        </div>
        <form method="POST" action="../app/actions/editar-curso.php">
            <div class="modal-body">
                <input type="hidden" id="edit_id" name="id">
                <div class="form-group">
                    <label for="edit_nome">Nome do Curso *</label>
                    <input type="text" id="edit_nome" name="nome" required>
                </div>
                <div class="form-group">
                    <label for="edit_carga_horaria">Carga Horária (horas) *</label>
                    <input type="number" id="edit_carga_horaria" name="carga_horaria" min="1" required>
                </div>
                <div class="form-group">
                    <label for="edit_descricao">Descrição</label>
                    <textarea id="edit_descricao" name="descricao"></textarea>
                </div>
                <div class="form-group">
                    <label for="edit_ativo">Status</label>
                    <select id="edit_ativo" name="ativo">
                        <option value="1">Ativo</option>
                        <option value="0">Inativo</option>
                    </select>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="button button-secondary" onclick="document.getElementById('editarCurso').style.display='none'">Cancelar</button>
                <button type="submit" class="button button-primary">Salvar Alterações</button>
            </div>
        </form>
    </div>
</div>

<script>
function editarCurso(curso) {
    document.getElementById('edit_id').value = curso.id;
    document.getElementById('edit_nome').value = curso.nome;
    document.getElementById('edit_carga_horaria').value = curso.carga_horaria;
    document.getElementById('edit_descricao').value = curso.descricao || '';
    document.getElementById('edit_ativo').value = curso.ativo;
    document.getElementById('editarCurso').style.display = 'block';
}

// Fechar modal ao clicar fora
window.onclick = function(event) {
    if (event.target.classList.contains('modal')) {
        event.target.style.display = 'none';
    }
}
</script>

<?php $conn->close(); ?>

