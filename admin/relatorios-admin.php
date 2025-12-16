<?php
/**
 * ============================================================================
 * RELATÓRIOS - SISTEMA DE CERTIFICADOS
 * ============================================================================
 */

require_once '../app/config/config.php';

// Verificar autenticação e permissão
if (!isAuthenticated() || !hasRole(ROLE_ADMIN)) {
    redirect(APP_URL . '/login.php');
}

$page_title = 'Relatórios - ' . APP_NAME;
$conn = getDBConnection();

// Período padrão: últimos 30 dias
$data_inicio = $_GET['data_inicio'] ?? date('Y-m-01'); // Primeiro dia do mês
$data_fim = $_GET['data_fim'] ?? date('Y-m-d'); // Hoje

// Relatório de Certificados por Período
$cert_periodo = [];
$result = $conn->query("
    SELECT 
        DATE(data_emissao) as data,
        COUNT(*) as total
    FROM certificados
    WHERE DATE(data_emissao) BETWEEN '$data_inicio' AND '$data_fim'
    GROUP BY DATE(data_emissao)
    ORDER BY data DESC
");

if ($result) {
    while ($row = $result->fetch_assoc()) {
        $cert_periodo[] = $row;
    }
}

// Certificados por Parceiro
$cert_parceiro = [];
$result = $conn->query("
    SELECT 
        p.nome_empresa,
        COUNT(c.id) as total_certificados,
        SUM(CASE WHEN c.status = 'ativo' THEN 1 ELSE 0 END) as ativos,
        SUM(CASE WHEN c.status = 'cancelado' THEN 1 ELSE 0 END) as cancelados
    FROM parceiros p
    LEFT JOIN certificados c ON p.id = c.parceiro_id 
        AND DATE(c.data_emissao) BETWEEN '$data_inicio' AND '$data_fim'
    GROUP BY p.id
    HAVING total_certificados > 0
    ORDER BY total_certificados DESC
    LIMIT 10
");

if ($result) {
    while ($row = $result->fetch_assoc()) {
        $cert_parceiro[] = $row;
    }
}

// Cursos mais emitidos
$cursos_top = [];
$result = $conn->query("
    SELECT 
        cur.nome,
        cur.carga_horaria,
        COUNT(c.id) as total_certificados
    FROM cursos cur
    LEFT JOIN certificados c ON cur.id = c.curso_id 
        AND DATE(c.data_emissao) BETWEEN '$data_inicio' AND '$data_fim'
    GROUP BY cur.id
    HAVING total_certificados > 0
    ORDER BY total_certificados DESC
    LIMIT 10
");

if ($result) {
    while ($row = $result->fetch_assoc()) {
        $cursos_top[] = $row;
    }
}

// Estatísticas gerais do período
$stats_periodo = [];
$result = $conn->query("
    SELECT 
        COUNT(*) as total_certificados,
        COUNT(DISTINCT parceiro_id) as total_parceiros,
        COUNT(DISTINCT aluno_id) as total_alunos,
        COUNT(DISTINCT curso_id) as total_cursos
    FROM certificados
    WHERE DATE(data_emissao) BETWEEN '$data_inicio' AND '$data_fim'
");

if ($result) {
    $stats_periodo = $result->fetch_assoc();
}
?>
<?php require_once '../app/views/admin-layout-header.php'; ?>

                <!-- Cabeçalho Moderno -->
                <div style="background: linear-gradient(135deg, rgba(110, 65, 193, 0.08) 0%, rgba(139, 95, 214, 0.04) 100%); border-radius: 16px; padding: 28px 32px; margin-bottom: 28px; box-shadow: 0 2px 8px rgba(0, 0, 0, 0.06);">
                  <div style="display: flex; align-items: center; gap: 16px;">
                    <div style="width: 56px; height: 56px; background: linear-gradient(135deg, #6E41C1 0%, #8B5FD6 100%); border-radius: 14px; display: flex; align-items: center; justify-content: center; box-shadow: 0 4px 12px rgba(110, 65, 193, 0.3);">
                      <span class="material-icons-outlined" style="font-size: 28px; color: white;">assessment</span>
                    </div>
                    <div>
                      <h1 style="font-size: 28px; font-weight: 700; color: #1D1D1F; margin: 0; line-height: 1.2;">Relatórios e Análises</h1>
                      <p style="color: #86868B; font-size: 14px; margin: 4px 0 0 0;">Visualize estatísticas e dados do sistema</p>
                    </div>
                  </div>
                </div>

                <!-- Filtro de Período -->
                <div class="card" style="margin-bottom: 24px;">
                    <h2><span class="icon">filter_alt</span>Filtrar Período</h2>
                    <form method="GET" style="display: flex; gap: 16px; align-items: flex-end;">
                        <div class="form-group" style="margin: 0; flex: 1;">
                            <label for="data_inicio">Data Início</label>
                            <input type="date" id="data_inicio" name="data_inicio" value="<?php echo $data_inicio; ?>">
                        </div>
                        <div class="form-group" style="margin: 0; flex: 1;">
                            <label for="data_fim">Data Fim</label>
                            <input type="date" id="data_fim" name="data_fim" value="<?php echo $data_fim; ?>">
                        </div>
                        <button type="submit" class="button button-primary">
                            <span class="icon">search</span> Filtrar
                        </button>
                    </form>
                </div>

                <!-- Estatísticas do Período -->
                <div class="stats-grid" style="margin-bottom: 24px;">
                    <div class="card stat-card">
                        <span class="icon" style="color: #6E41C1;">workspace_premium</span>
                        <div class="stat-label">Certificados Emitidos</div>
                        <div class="stat-value"><?php echo number_format($stats_periodo['total_certificados'] ?? 0, 0, ',', '.'); ?></div>
                        <div class="stat-change">No período</div>
                    </div>
                    <div class="card stat-card">
                        <span class="icon" style="color: #6E41C1;">business</span>
                        <div class="stat-label">Parceiros Ativos</div>
                        <div class="stat-value"><?php echo number_format($stats_periodo['total_parceiros'] ?? 0, 0, ',', '.'); ?></div>
                        <div class="stat-change">Com emissões</div>
                    </div>
                    <div class="card stat-card">
                        <span class="icon" style="color: #6E41C1;">group</span>
                        <div class="stat-label">Alunos Certificados</div>
                        <div class="stat-value"><?php echo number_format($stats_periodo['total_alunos'] ?? 0, 0, ',', '.'); ?></div>
                        <div class="stat-change">Únicos</div>
                    </div>
                    <div class="card stat-card">
                        <span class="icon" style="color: #6E41C1;">school</span>
                        <div class="stat-label">Cursos Diferentes</div>
                        <div class="stat-value"><?php echo number_format($stats_periodo['total_cursos'] ?? 0, 0, ',', '.'); ?></div>
                        <div class="stat-change">Emitidos</div>
                    </div>
                </div>

                <!-- Botões de Ação -->
                <div style="display: flex; justify-content: flex-end; gap: 12px; margin-bottom: 24px;">
                    <button class="button button-secondary" onclick="window.print()"
                            style="display: inline-flex; align-items: center; gap: 8px;">
                        <span class="icon" style="margin: 0; font-size: 18px;">print</span> Imprimir
                    </button>
                    <button class="button button-primary" onclick="exportarCSV()"
                            style="display: inline-flex; align-items: center; gap: 8px;">
                        <span class="icon" style="margin: 0; font-size: 18px;">download</span> Exportar CSV
                    </button>
                </div>

                <!-- Certificados por Parceiro -->
                <section class="table-section">
                    <div class="card">
                        <h2><span class="icon">business</span>Top 10 Parceiros (Período)</h2>
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Parceiro</th>
                                        <th>Total Certificados</th>
                                        <th>Ativos</th>
                                        <th>Cancelados</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (!empty($cert_parceiro)): ?>
                                        <?php foreach ($cert_parceiro as $item): ?>
                                            <tr>
                                                <td><strong><?php echo htmlspecialchars($item['nome_empresa']); ?></strong></td>
                                                <td><?php echo number_format($item['total_certificados'], 0, ',', '.'); ?></td>
                                                <td><span class="badge badge-success"><?php echo $item['ativos']; ?></span></td>
                                                <td><span class="badge badge-danger"><?php echo $item['cancelados']; ?></span></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="4" style="text-align: center; color: var(--text-medium);">Nenhum dado no período selecionado.</td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </section>

                <!-- Cursos Mais Emitidos -->
                <section class="table-section">
                    <div class="card">
                        <h2><span class="icon">school</span>Top 10 Cursos Mais Emitidos</h2>
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Curso</th>
                                        <th>Carga Horária</th>
                                        <th>Certificados Emitidos</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (!empty($cursos_top)): ?>
                                        <?php foreach ($cursos_top as $item): ?>
                                            <tr>
                                                <td><strong><?php echo htmlspecialchars($item['nome']); ?></strong></td>
                                                <td><?php echo $item['carga_horaria']; ?>h</td>
                                                <td><span class="badge badge-info"><?php echo number_format($item['total_certificados'], 0, ',', '.'); ?></span></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="3" style="text-align: center; color: var(--text-medium);">Nenhum dado no período selecionado.</td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </section>

                <!-- Emissões por Dia -->
                <section class="table-section">
                    <div class="card">
                        <h2><span class="icon">calendar_today</span>Emissões por Dia</h2>
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Data</th>
                                        <th>Certificados Emitidos</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (!empty($cert_periodo)): ?>
                                        <?php foreach ($cert_periodo as $item): ?>
                                            <tr>
                                                <td><?php echo date('d/m/Y', strtotime($item['data'])); ?></td>
                                                <td><span class="badge badge-info"><?php echo number_format($item['total'], 0, ',', '.'); ?></span></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="2" style="text-align: center; color: var(--text-medium);">Nenhuma emissão no período selecionado.</td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </section>

<?php require_once '../app/views/admin-layout-footer.php'; ?>

<script>
function exportarCSV() {
    alert('Funcionalidade de exportação CSV será implementada em breve!');
}
</script>

<?php $conn->close(); ?>

