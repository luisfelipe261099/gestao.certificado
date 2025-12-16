<?php
/**
 * ============================================================================
 * GERENCIAR ALUNOS - SISTEMA DE CERTIFICADOS
 * ============================================================================
 */

require_once '../app/config/config.php';

// Verificar autenticação e permissão
if (!isAuthenticated() || !hasRole(ROLE_ADMIN)) {
    redirect(APP_URL . '/login.php');
}

$page_title = 'Alunos - ' . APP_NAME;
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
$parceiro_filter = isset($_GET['parceiro']) ? (int)$_GET['parceiro'] : 0;
$order_by = isset($_GET['order_by']) ? $_GET['order_by'] : 'criado_em';
$order_dir = isset($_GET['order_dir']) && $_GET['order_dir'] === 'ASC' ? 'ASC' : 'DESC';

// Validar order_by
$allowed_order = ['criado_em', 'nome', 'cpf', 'email', 'total_certificados'];
if (!in_array($order_by, $allowed_order)) {
    $order_by = 'criado_em';
}

// Construir WHERE clause
$where_conditions = [];
$params = [];
$types = '';

if (!empty($search)) {
    $where_conditions[] = "(a.nome LIKE ? OR a.cpf LIKE ? OR a.email LIKE ?)";
    $search_param = "%{$search}%";
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
    $types .= 'sss';
}

if ($parceiro_filter > 0) {
    $where_conditions[] = "a.parceiro_id = ?";
    $params[] = $parceiro_filter;
    $types .= 'i';
}

$where_sql = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';

// Contar total de registros
$count_sql = "
    SELECT COUNT(DISTINCT a.id) as total
    FROM alunos a
    LEFT JOIN parceiros p ON a.parceiro_id = p.id
    LEFT JOIN certificados c ON a.id = c.aluno_id
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

// Buscar alunos
$alunos = [];
$sql = "
    SELECT
        a.id,
        a.nome,
        a.cpf,
        a.email,
        a.criado_em,
        p.nome_empresa as parceiro_nome,
        COUNT(DISTINCT c.id) as total_certificados
    FROM alunos a
    LEFT JOIN parceiros p ON a.parceiro_id = p.id
    LEFT JOIN certificados c ON a.id = c.aluno_id
    {$where_sql}
    GROUP BY a.id
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
        $alunos[] = $row;
    }
}

// Buscar estatísticas
$stats = [];
$result = $conn->query("
    SELECT
        COUNT(*) as total,
        COUNT(DISTINCT parceiro_id) as parceiros_distintos,
        SUM(CASE WHEN DATE(criado_em) >= DATE_SUB(CURDATE(), INTERVAL 30 DAY) THEN 1 ELSE 0 END) as ultimos_30_dias
    FROM alunos
");

if ($result) {
    $stats = $result->fetch_assoc();
}

// Buscar parceiros para o filtro
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

                <!-- Estatísticas -->
                <div class="stats-grid" style="grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); margin-bottom: 24px;">
                    <div class="card stat-card">
                        <span class="icon">group</span>
                        <div class="stat-label">Total de Alunos</div>
                        <div class="stat-value"><?php echo number_format($stats['total'] ?? 0, 0, ',', '.'); ?></div>
                        <div class="stat-change">Cadastrados</div>
                    </div>
                    <div class="card stat-card">
                        <span class="icon" style="color: #007AFF;">business</span>
                        <div class="stat-label">Parceiros Ativos</div>
                        <div class="stat-value"><?php echo number_format($stats['parceiros_distintos'] ?? 0, 0, ',', '.'); ?></div>
                        <div class="stat-change">Com alunos</div>
                    </div>
                    <div class="card stat-card">
                        <span class="icon" style="color: #34C759;">trending_up</span>
                        <div class="stat-label">Últimos 30 Dias</div>
                        <div class="stat-value"><?php echo number_format($stats['ultimos_30_dias'] ?? 0, 0, ',', '.'); ?></div>
                        <div class="stat-change">Novos alunos</div>
                    </div>
                </div>

                <!-- Page Header -->
                <div class="page-header">
                    <h1>Gerenciar Alunos</h1>
                    <div class="action-buttons">
                        <button class="button button-secondary" onclick="window.location.href='<?php echo APP_URL; ?>/admin/relatorios-admin.php?tipo=alunos'">
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
                                <input type="text" id="search" name="search" class="form-control" placeholder="Nome, CPF ou Email" value="<?php echo htmlspecialchars($search); ?>">
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

                <!-- Tabela de Alunos -->
                <section class="table-section">
                    <div class="card">
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px;">
                            <h2><span class="icon">group</span>Lista de Alunos</h2>
                            <div style="color: var(--text-medium); font-size: 0.9rem;">
                                Mostrando <?php echo number_format(min($offset + 1, $total_records)); ?> - <?php echo number_format(min($offset + $per_page, $total_records)); ?> de <?php echo number_format($total_records); ?> registros
                            </div>
                        </div>
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>
                                            <a href="?page=<?php echo $page; ?>&per_page=<?php echo $per_page; ?>&search=<?php echo urlencode($search); ?>&parceiro=<?php echo $parceiro_filter; ?>&order_by=nome&order_dir=<?php echo ($order_by === 'nome' && $order_dir === 'ASC') ? 'DESC' : 'ASC'; ?>" style="color: inherit; text-decoration: none; display: flex; align-items: center; gap: 4px;">
                                                Nome
                                                <?php if ($order_by === 'nome'): ?>
                                                    <span class="icon" style="font-size: 16px;"><?php echo $order_dir === 'ASC' ? 'arrow_upward' : 'arrow_downward'; ?></span>
                                                <?php endif; ?>
                                            </a>
                                        </th>
                                        <th>
                                            <a href="?page=<?php echo $page; ?>&per_page=<?php echo $per_page; ?>&search=<?php echo urlencode($search); ?>&parceiro=<?php echo $parceiro_filter; ?>&order_by=cpf&order_dir=<?php echo ($order_by === 'cpf' && $order_dir === 'ASC') ? 'DESC' : 'ASC'; ?>" style="color: inherit; text-decoration: none; display: flex; align-items: center; gap: 4px;">
                                                CPF
                                                <?php if ($order_by === 'cpf'): ?>
                                                    <span class="icon" style="font-size: 16px;"><?php echo $order_dir === 'ASC' ? 'arrow_upward' : 'arrow_downward'; ?></span>
                                                <?php endif; ?>
                                            </a>
                                        </th>
                                        <th>
                                            <a href="?page=<?php echo $page; ?>&per_page=<?php echo $per_page; ?>&search=<?php echo urlencode($search); ?>&parceiro=<?php echo $parceiro_filter; ?>&order_by=email&order_dir=<?php echo ($order_by === 'email' && $order_dir === 'ASC') ? 'DESC' : 'ASC'; ?>" style="color: inherit; text-decoration: none; display: flex; align-items: center; gap: 4px;">
                                                Email
                                                <?php if ($order_by === 'email'): ?>
                                                    <span class="icon" style="font-size: 16px;"><?php echo $order_dir === 'ASC' ? 'arrow_upward' : 'arrow_downward'; ?></span>
                                                <?php endif; ?>
                                            </a>
                                        </th>
                                        <th>Parceiro</th>
                                        <th>
                                            <a href="?page=<?php echo $page; ?>&per_page=<?php echo $per_page; ?>&search=<?php echo urlencode($search); ?>&parceiro=<?php echo $parceiro_filter; ?>&order_by=total_certificados&order_dir=<?php echo ($order_by === 'total_certificados' && $order_dir === 'ASC') ? 'DESC' : 'ASC'; ?>" style="color: inherit; text-decoration: none; display: flex; align-items: center; gap: 4px;">
                                                Certificados
                                                <?php if ($order_by === 'total_certificados'): ?>
                                                    <span class="icon" style="font-size: 16px;"><?php echo $order_dir === 'ASC' ? 'arrow_upward' : 'arrow_downward'; ?></span>
                                                <?php endif; ?>
                                            </a>
                                        </th>
                                        <th>
                                            <a href="?page=<?php echo $page; ?>&per_page=<?php echo $per_page; ?>&search=<?php echo urlencode($search); ?>&parceiro=<?php echo $parceiro_filter; ?>&order_by=criado_em&order_dir=<?php echo ($order_by === 'criado_em' && $order_dir === 'ASC') ? 'DESC' : 'ASC'; ?>" style="color: inherit; text-decoration: none; display: flex; align-items: center; gap: 4px;">
                                                Data Cadastro
                                                <?php if ($order_by === 'criado_em'): ?>
                                                    <span class="icon" style="font-size: 16px;"><?php echo $order_dir === 'ASC' ? 'arrow_upward' : 'arrow_downward'; ?></span>
                                                <?php endif; ?>
                                            </a>
                                        </th>
                                        <th>Ações</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (!empty($alunos)): ?>
                                        <?php foreach ($alunos as $aluno): ?>
                                            <tr>
                                                <td><strong><?php echo htmlspecialchars($aluno['nome']); ?></strong></td>
                                                <td><?php echo htmlspecialchars($aluno['cpf']); ?></td>
                                                <td><?php echo htmlspecialchars($aluno['email'] ?? 'N/A'); ?></td>
                                                <td><?php echo htmlspecialchars($aluno['parceiro_nome'] ?? 'N/A'); ?></td>
                                                <td>
                                                    <span class="badge badge-info"><?php echo $aluno['total_certificados']; ?></span>
                                                </td>
                                                <td><?php echo date('d/m/Y', strtotime($aluno['criado_em'])); ?></td>
                                                <td>
                                                    <button class="button button-secondary" style="min-width:auto;padding:6px 12px;font-size:.85rem;margin-right:4px;" title="Ver Certificados" onclick="window.location.href='<?php echo APP_URL; ?>/admin/certificados-admin.php?aluno_id=<?php echo $aluno['id']; ?>'">
                                                        <span class="icon" style="margin:0;font-size:16px;">workspace_premium</span>
                                                    </button>
                                                    <a href="../app/actions/deletar-aluno.php?id=<?php echo $aluno['id']; ?>" class="button button-secondary" style="min-width:auto;padding:6px 12px;font-size:.85rem;background:#f8d7da;border-color:#f5c6cb;" title="Deletar" onclick="return confirm('Tem certeza que deseja deletar este aluno? Todos os certificados associados serão removidos.')">
                                                        <span class="icon" style="margin:0;font-size:16px;color:#721c24;">delete</span>
                                                    </a>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="7" style="text-align: center; color: var(--text-medium);">Nenhum aluno cadastrado.</td>
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
                                    <a href="?page=1&per_page=<?php echo $per_page; ?>&search=<?php echo urlencode($search); ?>&parceiro=<?php echo $parceiro_filter; ?>&order_by=<?php echo $order_by; ?>&order_dir=<?php echo $order_dir; ?>" class="button button-secondary" style="min-width: auto; padding: 8px 12px;">
                                        <span class="icon">first_page</span>
                                    </a>
                                    <a href="?page=<?php echo $page - 1; ?>&per_page=<?php echo $per_page; ?>&search=<?php echo urlencode($search); ?>&parceiro=<?php echo $parceiro_filter; ?>&order_by=<?php echo $order_by; ?>&order_dir=<?php echo $order_dir; ?>" class="button button-secondary" style="min-width: auto; padding: 8px 12px;">
                                        <span class="icon">chevron_left</span>
                                    </a>
                                <?php endif; ?>

                                <?php
                                $start_page = max(1, $page - 2);
                                $end_page = min($total_pages, $page + 2);

                                for ($i = $start_page; $i <= $end_page; $i++):
                                ?>
                                    <a href="?page=<?php echo $i; ?>&per_page=<?php echo $per_page; ?>&search=<?php echo urlencode($search); ?>&parceiro=<?php echo $parceiro_filter; ?>&order_by=<?php echo $order_by; ?>&order_dir=<?php echo $order_dir; ?>" class="button <?php echo $i === $page ? 'button-primary' : 'button-secondary'; ?>" style="min-width: auto; padding: 8px 16px;">
                                        <?php echo $i; ?>
                                    </a>
                                <?php endfor; ?>

                                <?php if ($page < $total_pages): ?>
                                    <a href="?page=<?php echo $page + 1; ?>&per_page=<?php echo $per_page; ?>&search=<?php echo urlencode($search); ?>&parceiro=<?php echo $parceiro_filter; ?>&order_by=<?php echo $order_by; ?>&order_dir=<?php echo $order_dir; ?>" class="button button-secondary" style="min-width: auto; padding: 8px 12px;">
                                        <span class="icon">chevron_right</span>
                                    </a>
                                    <a href="?page=<?php echo $total_pages; ?>&per_page=<?php echo $per_page; ?>&search=<?php echo urlencode($search); ?>&parceiro=<?php echo $parceiro_filter; ?>&order_by=<?php echo $order_by; ?>&order_dir=<?php echo $order_dir; ?>" class="button button-secondary" style="min-width: auto; padding: 8px 12px;">
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

