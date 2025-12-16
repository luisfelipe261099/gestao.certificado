<?php
/**
 * ============================================================================
 * GERENCIAR CERTIFICADOS - SISTEMA DE CERTIFICADOS
 * ============================================================================
 */

require_once '../app/config/config.php';

// Verificar autenticação e permissão
if (!isAuthenticated() || !hasRole(ROLE_ADMIN)) {
    redirect(APP_URL . '/login.php');
}

$page_title = 'Certificados - ' . APP_NAME;
$conn = getDBConnection();

// ============================================================================
// PAGINAÇÃO E FILTROS
// ============================================================================
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$per_page = isset($_GET['per_page']) ? (int)$_GET['per_page'] : 20;
$page = max(1, $page); // Garante que a página seja no mínimo 1
$offset = ($page - 1) * $per_page;

// Filtros
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$status_filter = isset($_GET['status']) ? trim($_GET['status']) : '';
$order_by = isset($_GET['order_by']) ? $_GET['order_by'] : 'data_geracao';
$order_dir = isset($_GET['order_dir']) && $_GET['order_dir'] === 'ASC' ? 'ASC' : 'DESC';

// Validar order_by para evitar SQL injection
$allowed_order = ['data_geracao', 'numero_certificado', 'aluno_nome', 'curso_nome', 'status'];
if (!in_array($order_by, $allowed_order)) {
    $order_by = 'data_geracao';
}

// Construir WHERE clause
$where_conditions = [];
$params = [];
$types = '';

if (!empty($search)) {
    $where_conditions[] = "(a.nome LIKE ? OR a.cpf LIKE ? OR c.numero_certificado LIKE ? OR cur.nome LIKE ?)";
    $search_param = "%{$search}%";
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
    $types .= 'ssss';
}

if (!empty($status_filter)) {
    $where_conditions[] = "c.status = ?";
    $params[] = $status_filter;
    $types .= 's';
}

$where_sql = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';

// Contar total de registros
$count_sql = "
    SELECT COUNT(*) as total
    FROM certificados c
    LEFT JOIN alunos a ON c.aluno_id = a.id
    LEFT JOIN cursos cur ON c.curso_id = cur.id
    LEFT JOIN parceiros p ON c.parceiro_id = p.id
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

// Buscar certificados com informações de parceiro, curso e aluno
$certificados = [];
$sql = "
    SELECT
        c.id,
        c.numero_certificado,
        c.data_geracao,
        c.status,
        a.nome as aluno_nome,
        a.cpf as aluno_cpf,
        cur.nome as curso_nome,
        cur.carga_horaria,
        p.nome_empresa as parceiro_nome
    FROM certificados c
    LEFT JOIN alunos a ON c.aluno_id = a.id
    LEFT JOIN cursos cur ON c.curso_id = cur.id
    LEFT JOIN parceiros p ON c.parceiro_id = p.id
    {$where_sql}
    ORDER BY {$order_by} {$order_dir}
    LIMIT ? OFFSET ?
";

$stmt = $conn->prepare($sql);
$params[] = $per_page;
$params[] = $offset;
$types .= 'ii';

if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();

if ($result) {
    while ($row = $result->fetch_assoc()) {
        $certificados[] = $row;
    }
}

// Buscar estatísticas
$stats = [];
$result = $conn->query("
    SELECT
        COUNT(*) as total,
        SUM(CASE WHEN status = 'gerado' OR status = 'enviado' OR status = 'baixado' THEN 1 ELSE 0 END) as ativos,
        SUM(CASE WHEN status = 'cancelado' THEN 1 ELSE 0 END) as cancelados,
        SUM(CASE WHEN DATE(data_geracao) = CURDATE() THEN 1 ELSE 0 END) as hoje
    FROM certificados
");

if ($result) {
    $stats = $result->fetch_assoc();
}
?>
<?php require_once '../app/views/admin-layout-header.php'; ?>

                <!-- Estatísticas -->
                <div class="stats-grid" style="grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); margin-bottom: 24px;">
                    <div class="card stat-card">
                        <span class="icon">workspace_premium</span>
                        <div class="stat-label">Total de Certificados</div>
                        <div class="stat-value"><?php echo number_format($stats['total'] ?? 0, 0, ',', '.'); ?></div>
                        <div class="stat-change">Todos os tempos</div>
                    </div>
                    <div class="card stat-card">
                        <span class="icon" style="color: #34C759;">check_circle</span>
                        <div class="stat-label">Certificados Ativos</div>
                        <div class="stat-value"><?php echo number_format($stats['ativos'] ?? 0, 0, ',', '.'); ?></div>
                        <div class="stat-change">Em vigor</div>
                    </div>
                    <div class="card stat-card">
                        <span class="icon" style="color: #FF3B30;">cancel</span>
                        <div class="stat-label">Cancelados</div>
                        <div class="stat-value"><?php echo number_format($stats['cancelados'] ?? 0, 0, ',', '.'); ?></div>
                        <div class="stat-change">Total cancelado</div>
                    </div>
                    <div class="card stat-card">
                        <span class="icon" style="color: #007AFF;">today</span>
                        <div class="stat-label">Emitidos Hoje</div>
                        <div class="stat-value"><?php echo number_format($stats['hoje'] ?? 0, 0, ',', '.'); ?></div>
                        <div class="stat-change">Últimas 24h</div>
                    </div>
                </div>

                <!-- Page Header -->
                <div class="page-header">
                    <h1>Gerenciar Certificados</h1>
                    <div class="action-buttons">
                        <button class="button button-secondary" onclick="window.location.href='<?php echo APP_URL; ?>/admin/relatorios-admin.php?tipo=certificados'">
                            <span class="icon">assessment</span> Relatório
                        </button>
                    </div>
                </div>

                <!-- Filtros de Busca -->
                <section class="filter-section" style="margin-bottom: 24px;">
                    <div class="card">
                        <form method="GET" action="" style="display: grid; grid-template-columns: 2fr 1fr 1fr auto; gap: 12px; align-items: end;">
                            <div class="form-group" style="margin-bottom: 0;">
                                <label for="search">Buscar</label>
                                <input type="text" id="search" name="search" class="form-control" placeholder="Nome, CPF, Código ou Curso" value="<?php echo htmlspecialchars($search); ?>">
                            </div>
                            <div class="form-group" style="margin-bottom: 0;">
                                <label for="status">Status</label>
                                <select id="status" name="status" class="form-control">
                                    <option value="">Todos</option>
                                    <option value="gerado" <?php echo $status_filter === 'gerado' ? 'selected' : ''; ?>>Gerado</option>
                                    <option value="enviado" <?php echo $status_filter === 'enviado' ? 'selected' : ''; ?>>Enviado</option>
                                    <option value="baixado" <?php echo $status_filter === 'baixado' ? 'selected' : ''; ?>>Baixado</option>
                                    <option value="cancelado" <?php echo $status_filter === 'cancelado' ? 'selected' : ''; ?>>Cancelado</option>
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

                <!-- Tabela de Certificados -->
                <section class="table-section">
                    <div class="card">
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px;">
                            <h2><span class="icon">workspace_premium</span>Lista de Certificados</h2>
                            <div style="color: var(--text-medium); font-size: 0.9rem;">
                                Mostrando <?php echo number_format(min($offset + 1, $total_records)); ?> - <?php echo number_format(min($offset + $per_page, $total_records)); ?> de <?php echo number_format($total_records); ?> registros
                            </div>
                        </div>
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>
                                            <a href="?page=<?php echo $page; ?>&per_page=<?php echo $per_page; ?>&search=<?php echo urlencode($search); ?>&status=<?php echo urlencode($status_filter); ?>&order_by=numero_certificado&order_dir=<?php echo ($order_by === 'numero_certificado' && $order_dir === 'ASC') ? 'DESC' : 'ASC'; ?>" style="color: inherit; text-decoration: none; display: flex; align-items: center; gap: 4px;">
                                                Código
                                                <?php if ($order_by === 'numero_certificado'): ?>
                                                    <span class="icon" style="font-size: 16px;"><?php echo $order_dir === 'ASC' ? 'arrow_upward' : 'arrow_downward'; ?></span>
                                                <?php endif; ?>
                                            </a>
                                        </th>
                                        <th>
                                            <a href="?page=<?php echo $page; ?>&per_page=<?php echo $per_page; ?>&search=<?php echo urlencode($search); ?>&status=<?php echo urlencode($status_filter); ?>&order_by=aluno_nome&order_dir=<?php echo ($order_by === 'aluno_nome' && $order_dir === 'ASC') ? 'DESC' : 'ASC'; ?>" style="color: inherit; text-decoration: none; display: flex; align-items: center; gap: 4px;">
                                                Aluno
                                                <?php if ($order_by === 'aluno_nome'): ?>
                                                    <span class="icon" style="font-size: 16px;"><?php echo $order_dir === 'ASC' ? 'arrow_upward' : 'arrow_downward'; ?></span>
                                                <?php endif; ?>
                                            </a>
                                        </th>
                                        <th>CPF</th>
                                        <th>
                                            <a href="?page=<?php echo $page; ?>&per_page=<?php echo $per_page; ?>&search=<?php echo urlencode($search); ?>&status=<?php echo urlencode($status_filter); ?>&order_by=curso_nome&order_dir=<?php echo ($order_by === 'curso_nome' && $order_dir === 'ASC') ? 'DESC' : 'ASC'; ?>" style="color: inherit; text-decoration: none; display: flex; align-items: center; gap: 4px;">
                                                Curso
                                                <?php if ($order_by === 'curso_nome'): ?>
                                                    <span class="icon" style="font-size: 16px;"><?php echo $order_dir === 'ASC' ? 'arrow_upward' : 'arrow_downward'; ?></span>
                                                <?php endif; ?>
                                            </a>
                                        </th>
                                        <th>Carga Horária</th>
                                        <th>Parceiro</th>
                                        <th>
                                            <a href="?page=<?php echo $page; ?>&per_page=<?php echo $per_page; ?>&search=<?php echo urlencode($search); ?>&status=<?php echo urlencode($status_filter); ?>&order_by=data_geracao&order_dir=<?php echo ($order_by === 'data_geracao' && $order_dir === 'ASC') ? 'DESC' : 'ASC'; ?>" style="color: inherit; text-decoration: none; display: flex; align-items: center; gap: 4px;">
                                                Data Emissão
                                                <?php if ($order_by === 'data_geracao'): ?>
                                                    <span class="icon" style="font-size: 16px;"><?php echo $order_dir === 'ASC' ? 'arrow_upward' : 'arrow_downward'; ?></span>
                                                <?php endif; ?>
                                            </a>
                                        </th>
                                        <th>
                                            <a href="?page=<?php echo $page; ?>&per_page=<?php echo $per_page; ?>&search=<?php echo urlencode($search); ?>&status=<?php echo urlencode($status_filter); ?>&order_by=status&order_dir=<?php echo ($order_by === 'status' && $order_dir === 'ASC') ? 'DESC' : 'ASC'; ?>" style="color: inherit; text-decoration: none; display: flex; align-items: center; gap: 4px;">
                                                Status
                                                <?php if ($order_by === 'status'): ?>
                                                    <span class="icon" style="font-size: 16px;"><?php echo $order_dir === 'ASC' ? 'arrow_upward' : 'arrow_downward'; ?></span>
                                                <?php endif; ?>
                                            </a>
                                        </th>
                                        <th>Ações</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (!empty($certificados)): ?>
                                        <?php foreach ($certificados as $cert): ?>
                                            <tr>
                                                <td><strong><?php echo htmlspecialchars($cert['numero_certificado']); ?></strong></td>
                                                <td><?php echo htmlspecialchars($cert['aluno_nome'] ?? 'N/A'); ?></td>
                                                <td><?php echo htmlspecialchars($cert['aluno_cpf'] ?? 'N/A'); ?></td>
                                                <td><?php echo htmlspecialchars($cert['curso_nome'] ?? 'N/A'); ?></td>
                                                <td><?php echo $cert['carga_horaria'] ?? 0; ?>h</td>
                                                <td><?php echo htmlspecialchars($cert['parceiro_nome'] ?? 'N/A'); ?></td>
                                                <td><?php echo date('d/m/Y', strtotime($cert['data_geracao'])); ?></td>
                                                <td>
                                                    <span class="badge badge-<?php echo ($cert['status'] == 'cancelado') ? 'danger' : 'success'; ?>">
                                                        <?php echo ucfirst($cert['status']); ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <a href="<?php echo APP_URL; ?>/validar.php?codigo=<?php echo $cert['numero_certificado']; ?>" target="_blank" class="button button-secondary" style="min-width:auto;padding:6px 12px;font-size:.85rem;margin-right:4px;" title="Visualizar">
                                                        <span class="icon" style="margin:0;font-size:16px;">visibility</span>
                                                    </a>
                                                    <?php if ($cert['status'] != 'cancelado'): ?>
                                                        <a href="../app/actions/cancelar-certificado.php?id=<?php echo $cert['id']; ?>" class="button button-secondary" style="min-width:auto;padding:6px 12px;font-size:.85rem;background:#f8d7da;border-color:#f5c6cb;" title="Cancelar" onclick="return confirm('Tem certeza que deseja cancelar este certificado?')">
                                                            <span class="icon" style="margin:0;font-size:16px;color:#721c24;">cancel</span>
                                                        </a>
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="9" style="text-align: center; color: var(--text-medium);">Nenhum certificado encontrado.</td>
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
                                    <a href="?page=1&per_page=<?php echo $per_page; ?>&search=<?php echo urlencode($search); ?>&status=<?php echo urlencode($status_filter); ?>&order_by=<?php echo $order_by; ?>&order_dir=<?php echo $order_dir; ?>" class="button button-secondary" style="min-width: auto; padding: 8px 12px;">
                                        <span class="icon">first_page</span>
                                    </a>
                                    <a href="?page=<?php echo $page - 1; ?>&per_page=<?php echo $per_page; ?>&search=<?php echo urlencode($search); ?>&status=<?php echo urlencode($status_filter); ?>&order_by=<?php echo $order_by; ?>&order_dir=<?php echo $order_dir; ?>" class="button button-secondary" style="min-width: auto; padding: 8px 12px;">
                                        <span class="icon">chevron_left</span>
                                    </a>
                                <?php endif; ?>

                                <?php
                                // Mostrar páginas ao redor da página atual
                                $start_page = max(1, $page - 2);
                                $end_page = min($total_pages, $page + 2);

                                for ($i = $start_page; $i <= $end_page; $i++):
                                ?>
                                    <a href="?page=<?php echo $i; ?>&per_page=<?php echo $per_page; ?>&search=<?php echo urlencode($search); ?>&status=<?php echo urlencode($status_filter); ?>&order_by=<?php echo $order_by; ?>&order_dir=<?php echo $order_dir; ?>" class="button <?php echo $i === $page ? 'button-primary' : 'button-secondary'; ?>" style="min-width: auto; padding: 8px 16px;">
                                        <?php echo $i; ?>
                                    </a>
                                <?php endfor; ?>

                                <?php if ($page < $total_pages): ?>
                                    <a href="?page=<?php echo $page + 1; ?>&per_page=<?php echo $per_page; ?>&search=<?php echo urlencode($search); ?>&status=<?php echo urlencode($status_filter); ?>&order_by=<?php echo $order_by; ?>&order_dir=<?php echo $order_dir; ?>" class="button button-secondary" style="min-width: auto; padding: 8px 12px;">
                                        <span class="icon">chevron_right</span>
                                    </a>
                                    <a href="?page=<?php echo $total_pages; ?>&per_page=<?php echo $per_page; ?>&search=<?php echo urlencode($search); ?>&status=<?php echo urlencode($status_filter); ?>&order_by=<?php echo $order_by; ?>&order_dir=<?php echo $order_dir; ?>" class="button button-secondary" style="min-width: auto; padding: 8px 12px;">
                                        <span class="icon">last_page</span>
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                </section>

<?php require_once '../app/views/admin-layout-footer.php'; ?>

<script>
// Fechar modal ao clicar fora
window.onclick = function(event) {
    if (event.target.classList.contains('modal')) {
        event.target.style.display = 'none';
    }
}
</script>

<?php $conn->close(); ?>

