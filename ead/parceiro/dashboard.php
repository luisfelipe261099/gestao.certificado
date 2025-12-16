<?php
/**
 * Dashboard do Parceiro
 * Sistema EAD Pro
 */

// Definir encoding UTF-8
header('Content-Type: text/html; charset=utf-8');

require_once '../config/database.php';

// Verificar autenticação
verificar_autenticacao('parceiro');

$parceiro_id = $_SESSION['ead_parceiro_id'];

// Inicializar variáveis
$total_cursos = 0;
$total_alunos = 0;
$total_aulas = 0;
$receita_total = 0;
$cursos_recentes = [];
$alunos_recentes = [];

// Buscar estatísticas
try {
    // Total de cursos
    $stmt = $pdo->prepare('SELECT COUNT(*) as total FROM cursos WHERE parceiro_id = ? AND ativo = 1');
    $stmt->execute([$parceiro_id]);
    $result = $stmt->fetch();
    $total_cursos = $result['total'] ?? 0;

    // Total de alunos
    $stmt = $pdo->prepare('
        SELECT COUNT(DISTINCT ia.aluno_id) as total
        FROM inscricoes_alunos ia
        JOIN cursos c ON ia.curso_id = c.id
        WHERE c.parceiro_id = ? AND ia.status IN ("inscrito", "em_progresso")
    ');
    $stmt->execute([$parceiro_id]);
    $result = $stmt->fetch();
    $total_alunos = $result['total'] ?? 0;

    // Total de aulas
    $stmt = $pdo->prepare('
        SELECT COUNT(*) as total
        FROM aulas a
        JOIN cursos c ON a.curso_id = c.id
        WHERE c.parceiro_id = ? AND a.ativa = 1
    ');
    $stmt->execute([$parceiro_id]);
    $result = $stmt->fetch();
    $total_aulas = $result['total'] ?? 0;

    // Receita total (tabela transacoes pode não existir)
    $receita_total = 0;
    try {
        $stmt = $pdo->prepare('
            SELECT SUM(valor) as total
            FROM transacoes
            WHERE parceiro_id = ? AND tipo = "receita" AND status = "confirmado"
        ');
        $stmt->execute([$parceiro_id]);
        $result = $stmt->fetch();
        $receita_total = $result['total'] ?? 0;
    } catch (Exception $e) {
        // Tabela transacoes pode não existir, usar 0
        $receita_total = 0;
    }

    // Cursos recentes
    $stmt = $pdo->prepare('
        SELECT id, nome, criado_em, (
            SELECT COUNT(*) FROM inscricoes_alunos WHERE curso_id = cursos.id AND status IN ("inscrito", "em_progresso")
        ) as alunos_ativos
        FROM cursos
        WHERE parceiro_id = ? AND ativo = 1
        ORDER BY criado_em DESC
        LIMIT 5
    ');
    $stmt->execute([$parceiro_id]);
    $cursos_recentes = $stmt->fetchAll() ?? [];

    // Alunos recentes
    $stmt = $pdo->prepare('
        SELECT DISTINCT a.id, a.nome, a.email, ia.data_inscricao as data_inscricao, c.nome as curso
        FROM alunos a
        JOIN inscricoes_alunos ia ON a.id = ia.aluno_id
        JOIN cursos c ON ia.curso_id = c.id
        WHERE c.parceiro_id = ? AND ia.status IN ("inscrito", "em_progresso")
        ORDER BY ia.data_inscricao DESC
        LIMIT 5
    ');
    $stmt->execute([$parceiro_id]);
    $alunos_recentes = $stmt->fetchAll() ?? [];
    
} catch (Exception $e) {
    log_erro('Erro ao buscar estatísticas: ' . $e->getMessage());
    $total_cursos = 0;
    $total_alunos = 0;
    $total_aulas = 0;
    $receita_total = 0;
    $cursos_recentes = [];
    $alunos_recentes = [];
}

$titulo_pagina = 'Dashboard';
require_once '../includes/ead-layout-header.php';
?>

<!-- Cabeçalho Moderno -->
<div style="background: linear-gradient(135deg, rgba(110, 65, 193, 0.08) 0%, rgba(139, 95, 214, 0.04) 100%); border-radius: 16px; padding: 28px 32px; margin-bottom: 28px; box-shadow: 0 2px 8px rgba(0, 0, 0, 0.06);">
    <div style="display: flex; align-items: center; justify-content: space-between;">
        <div style="display: flex; align-items: center; gap: 16px;">
            <div style="width: 56px; height: 56px; background: linear-gradient(135deg, #6E41C1 0%, #8B5FD6 100%); border-radius: 14px; display: flex; align-items: center; justify-content: center; box-shadow: 0 4px 12px rgba(110, 65, 193, 0.3);">
                <span class="material-icons-outlined" style="font-size: 28px; color: white;">dashboard</span>
            </div>
            <div>
                <h1 style="font-size: 28px; font-weight: 700; color: #1D1D1F; margin: 0; line-height: 1.2;">Dashboard</h1>
                <p style="color: #86868B; font-size: 14px; margin: 4px 0 0 0;">Visão geral de seus cursos e alunos</p>
            </div>
        </div>
        <a href="criar-curso.php" class="button button-primary" style="text-decoration: none;">
            <span class="material-icons-outlined">add</span>
            <span>Novo Curso</span>
        </a>
    </div>
</div>

<!-- Cards de Estatísticas -->
<div class="stats-grid">
    <div class="stat-card">
        <span class="material-icons-outlined">menu_book</span>
        <div class="stat-label">Cursos Ativos</div>
        <div class="stat-value"><?php echo number_format($total_cursos, 0, ',', '.'); ?></div>
        <div class="stat-change">Publicados</div>
    </div>

    <div class="stat-card">
        <span class="material-icons-outlined">group</span>
        <div class="stat-label">Total de Alunos</div>
        <div class="stat-value"><?php echo number_format($total_alunos, 0, ',', '.'); ?></div>
        <div class="stat-change">Inscritos</div>
    </div>

    <div class="stat-card">
        <span class="material-icons-outlined">play_circle</span>
        <div class="stat-label">Aulas Criadas</div>
        <div class="stat-value"><?php echo number_format($total_aulas, 0, ',', '.'); ?></div>
        <div class="stat-change">Disponíveis</div>
    </div>

    <div class="stat-card">
        <span class="material-icons-outlined">payments</span>
        <div class="stat-label">Receita Total</div>
        <div class="stat-value">R$ <?php echo number_format($receita_total, 2, ',', '.'); ?></div>
        <div class="stat-change">Acumulado</div>
    </div>
</div>

<!-- Grid de Conteúdo -->
<div style="display: grid; grid-template-columns: 2fr 1fr; gap: 24px; margin-bottom: 24px;">

    <!-- Meus Cursos -->
    <div>
        <?php if (count($cursos_recentes) > 0): ?>
        <div class="card">
            <h2>
                <span class="material-icons-outlined">menu_book</span>
                Meus Cursos
            </h2>
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Curso</th>
                            <th>Alunos</th>
                            <th>Data</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($cursos_recentes as $curso): ?>
                        <tr>
                            <td><strong><?php echo htmlspecialchars($curso['nome']); ?></strong></td>
                            <td><span class="badge badge-info"><?php echo $curso['alunos_ativos']; ?> alunos</span></td>
                            <td><?php echo date('d/m/Y', strtotime($curso['criado_em'] ?? date('Y-m-d'))); ?></td>
                            <td>
                                <a href="curso-detalhes.php?id=<?php echo $curso['id']; ?>" class="button button-secondary" style="padding: 6px 12px; font-size: 13px; text-decoration: none;">
                                    <span class="material-icons-outlined" style="font-size: 16px;">visibility</span>
                                    Visualizar
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php else: ?>
        <div class="card">
            <div class="empty-state">
                <span class="material-icons-outlined">menu_book</span>
                <h3>Nenhum curso criado ainda</h3>
                <p>Comece criando seu primeiro curso!</p>
                <a href="criar-curso.php" class="button button-primary" style="text-decoration: none;">
                    <span class="material-icons-outlined">add</span>
                    Criar Primeiro Curso
                </a>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <!-- Alunos Recentes -->
    <div>
        <?php if (count($alunos_recentes) > 0): ?>
        <div class="card">
            <h2>
                <span class="material-icons-outlined">group</span>
                Alunos Recentes
            </h2>
            <div style="display: flex; flex-direction: column; gap: 12px;">
                <?php foreach ($alunos_recentes as $aluno): ?>
                <div style="padding: 14px; background-color: rgba(110, 65, 193, 0.04); border-radius: 10px; border: 1px solid rgba(110, 65, 193, 0.1);">
                    <div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 6px;">
                        <div style="display: flex; align-items: center; gap: 8px;">
                            <span class="material-icons-outlined" style="font-size: 20px; color: #6E41C1;">person</span>
                            <strong style="font-size: 14px; color: #1D1D1F;"><?php echo htmlspecialchars($aluno['nome']); ?></strong>
                        </div>
                        <span style="font-size: 12px; color: #86868B;"><?php echo date('d/m/Y', strtotime($aluno['data_inscricao'])); ?></span>
                    </div>
                    <div style="display: flex; align-items: center; gap: 6px; padding-left: 28px;">
                        <span class="material-icons-outlined" style="font-size: 16px; color: #86868B;">menu_book</span>
                        <span style="font-size: 13px; color: #86868B;"><?php echo htmlspecialchars($aluno['curso']); ?></span>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php else: ?>
        <div class="card">
            <div class="empty-state" style="padding: 40px 20px;">
                <span class="material-icons-outlined" style="font-size: 48px;">group</span>
                <h3 style="font-size: 16px;">Nenhum aluno inscrito</h3>
                <p style="font-size: 13px;">Os alunos aparecerão aqui quando se inscreverem</p>
            </div>
        </div>
        <?php endif; ?>
    </div>

</div>

<?php
require_once '../includes/ead-layout-footer.php';
?>