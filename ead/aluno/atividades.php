<?php
/**
 * Atividades e Exercícios - Portal do Aluno
 * Sistema EAD FaCiencia
 */

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once '../config/database.php';
require_once '../app/models/Aluno.php';

iniciar_sessao();

// Verificar autenticação
if (!isset($_SESSION['usuario_id']) || $_SESSION['tipo_usuario'] !== 'aluno') {
    header('Location: login-aluno.php');
    exit;
}

$aluno_id = $_SESSION['usuario_id'];
$aluno_model = new Aluno($pdo);
$aluno = $aluno_model->obter_por_id($aluno_id);

// ============================================================================
// BUSCAR EXERCÍCIOS PENDENTES
// ============================================================================

try {
    $stmt = $pdo->prepare('
        SELECT
            ex.id,
            ex.titulo,
            ex.descricao,
            ex.tipo,
            ex.pontuacao_maxima,
            a.titulo as aula_titulo,
            a.id as aula_id,
            c.nome as curso_nome,
            c.id as curso_id,
            ia.id as inscricao_id,
            (SELECT COUNT(*) FROM questoes_exercicios WHERE exercicio_id = ex.id) as total_questoes,
            (SELECT COUNT(*) FROM ead_respostas_exercicios WHERE exercicio_id = ex.id AND inscricao_id = ia.id) as questoes_respondidas
        FROM ead_exercicios ex
        INNER JOIN ead_aulas a ON ex.aula_id = a.id
        INNER JOIN cursos c ON a.curso_id = c.id
        INNER JOIN inscricoes_alunos ia ON c.id = ia.curso_id
        WHERE ia.aluno_id = ?
        AND ia.status IN ("inscrito", "em_progresso")
        AND a.ativa = 1
        HAVING questoes_respondidas < total_questoes OR questoes_respondidas IS NULL
        ORDER BY ex.criado_em DESC
        LIMIT 10
    ');
    $stmt->execute([$aluno_id]);
    $exercicios_pendentes = $stmt->fetchAll();

} catch (Exception $e) {
    $exercicios_pendentes = [];
    $erro_pendentes = $e->getMessage();
}

// ============================================================================
// BUSCAR EXERCÍCIOS CONCLUÍDOS
// ============================================================================

try {
    $stmt = $pdo->prepare('
        SELECT
            ex.id,
            ex.titulo,
            ex.descricao,
            ex.tipo,
            ex.pontuacao_maxima,
            a.titulo as aula_titulo,
            a.id as aula_id,
            c.nome as curso_nome,
            c.id as curso_id,
            ia.id as inscricao_id,
            (SELECT COUNT(*) FROM questoes_exercicios WHERE exercicio_id = ex.id) as total_questoes,
            (SELECT SUM(pontuacao_obtida) FROM ead_respostas_exercicios WHERE exercicio_id = ex.id AND inscricao_id = ia.id) as pontuacao_obtida,
            (SELECT MAX(data_resposta) FROM ead_respostas_exercicios WHERE exercicio_id = ex.id AND inscricao_id = ia.id) as data_conclusao
        FROM ead_exercicios ex
        INNER JOIN ead_aulas a ON ex.aula_id = a.id
        INNER JOIN cursos c ON a.curso_id = c.id
        INNER JOIN inscricoes_alunos ia ON c.id = ia.curso_id
        WHERE ia.aluno_id = ?
        AND ia.status IN ("inscrito", "em_progresso", "concluido")
        AND a.ativa = 1
        AND (SELECT COUNT(*) FROM ead_respostas_exercicios WHERE exercicio_id = ex.id AND inscricao_id = ia.id) >= (SELECT COUNT(*) FROM questoes_exercicios WHERE exercicio_id = ex.id)
        ORDER BY data_conclusao DESC
        LIMIT 10
    ');
    $stmt->execute([$aluno_id]);
    $exercicios_concluidos = $stmt->fetchAll();

} catch (Exception $e) {
    $exercicios_concluidos = [];
    $erro_concluidos = $e->getMessage();
}

// ============================================================================
// ESTATÍSTICAS
// ============================================================================

try {
    $stmt = $pdo->prepare('
        SELECT
            COUNT(DISTINCT ex.id) as total_exercicios,
            COUNT(DISTINCT CASE
                WHEN (SELECT COUNT(*) FROM ead_respostas_exercicios WHERE exercicio_id = ex.id AND inscricao_id = ia.id) >= (SELECT COUNT(*) FROM questoes_exercicios WHERE exercicio_id = ex.id)
                THEN ex.id
            END) as exercicios_concluidos,
            COALESCE(SUM(CASE
                WHEN (SELECT COUNT(*) FROM ead_respostas_exercicios WHERE exercicio_id = ex.id AND inscricao_id = ia.id) >= (SELECT COUNT(*) FROM questoes_exercicios WHERE exercicio_id = ex.id)
                THEN (SELECT SUM(pontuacao_obtida) FROM ead_respostas_exercicios WHERE exercicio_id = ex.id AND inscricao_id = ia.id)
            END), 0) as pontuacao_total
        FROM ead_exercicios ex
        INNER JOIN ead_aulas a ON ex.aula_id = a.id
        INNER JOIN cursos c ON a.curso_id = c.id
        INNER JOIN inscricoes_alunos ia ON c.id = ia.curso_id
        WHERE ia.aluno_id = ?
        AND ia.status IN ("inscrito", "em_progresso", "concluido")
        AND a.ativa = 1
    ');
    $stmt->execute([$aluno_id]);
    $stats = $stmt->fetch();

    $total_exercicios = $stats['total_exercicios'] ?? 0;
    $exercicios_concluidos_count = $stats['exercicios_concluidos'] ?? 0;
    $pontuacao_total = $stats['pontuacao_total'] ?? 0;
    $exercicios_pendentes_count = $total_exercicios - $exercicios_concluidos_count;

} catch (Exception $e) {
    $total_exercicios = 0;
    $exercicios_concluidos_count = 0;
    $pontuacao_total = 0;
    $exercicios_pendentes_count = 0;
}

?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Atividades - Portal do Aluno</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons+Outlined" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', sans-serif;
            background: #F5F5F7;
            color: #1D1D1F;
        }

        /* SIDEBAR (mesma estrutura do dashboard) */
        .sidebar {
            position: fixed;
            left: 0;
            top: 0;
            bottom: 0;
            width: 280px;
            background: white;
            border-right: 1px solid #E5E5E7;
            display: flex;
            flex-direction: column;
            z-index: 1000;
        }

        .sidebar-header {
            padding: 24px 20px;
            border-bottom: 1px solid #E5E5E7;
        }

        .sidebar-logo {
            display: flex;
            align-items: center;
            gap: 12px;
            text-decoration: none;
        }

        .sidebar-logo .icon {
            width: 40px;
            height: 40px;
            background: linear-gradient(135deg, #6E41C1 0%, #8B5FD6 100%);
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 24px;
        }

        .sidebar-logo .text {
            font-size: 18px;
            font-weight: 700;
            color: #1D1D1F;
        }

        .sidebar-nav {
            flex: 1;
            padding: 20px 0;
            overflow-y: auto;
        }

        .nav-section {
            margin-bottom: 24px;
        }

        .nav-section-title {
            padding: 0 20px;
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
            color: #86868B;
            letter-spacing: 0.5px;
            margin-bottom: 8px;
        }

        .nav-item {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px 20px;
            color: #1D1D1F;
            text-decoration: none;
            transition: all 0.2s;
            position: relative;
        }

        .nav-item:hover {
            background: #F5F5F7;
            color: #6E41C1;
        }

        .nav-item.active {
            background: linear-gradient(90deg, rgba(110, 65, 193, 0.1) 0%, transparent 100%);
            color: #6E41C1;
            font-weight: 600;
        }

        .nav-item.active::before {
            content: '';
            position: absolute;
            left: 0;
            top: 0;
            bottom: 0;
            width: 3px;
            background: #6E41C1;
        }

        .nav-item .material-icons-outlined {
            font-size: 22px;
        }

        .sidebar-footer {
            padding: 20px;
            border-top: 1px solid #E5E5E7;
        }

        .user-profile {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px;
            background: #F5F5F7;
            border-radius: 12px;
        }

        .user-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: linear-gradient(135deg, #6E41C1 0%, #8B5FD6 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 600;
            font-size: 16px;
        }

        .user-info {
            flex: 1;
        }

        .user-name {
            font-size: 14px;
            font-weight: 600;
            color: #1D1D1F;
            margin-bottom: 2px;
        }


        /* MAIN CONTENT */
        .main-content {
            margin-left: 280px;
            min-height: 100vh;
        }

        .topbar {
            background: white;
            border-bottom: 1px solid #E5E5E7;
            padding: 20px 40px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: sticky;
            top: 0;
            z-index: 100;
        }

        .topbar-left h1 {
            font-size: 28px;
            font-weight: 700;
            color: #1D1D1F;
        }

        .breadcrumb {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 14px;
            color: #86868B;
            margin-bottom: 8px;
        }

        .breadcrumb a {
            color: #6E41C1;
            text-decoration: none;
        }

        .topbar-right {
            display: flex;
            gap: 12px;
        }

        .topbar-btn {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            border: none;
            background: #F5F5F7;
            color: #1D1D1F;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.2s;
        }

        .topbar-btn:hover {
            background: #E5E5E7;
            color: #6E41C1;
        }

        .content-wrapper {
            padding: 40px;
        }

        /* STATS CARDS */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 24px;
            margin-bottom: 32px;
        }

        .stat-card {
            background: white;
            border-radius: 16px;
            padding: 24px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04);
            transition: all 0.3s;
        }

        .stat-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 8px 16px rgba(0, 0, 0, 0.08);
        }

        .stat-card .icon {
            width: 48px;
            height: 48px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 16px;
            font-size: 24px;
        }

        .stat-card.purple .icon {
            background: linear-gradient(135deg, rgba(110, 65, 193, 0.1) 0%, rgba(139, 95, 214, 0.1) 100%);
            color: #6E41C1;
        }

        .stat-card.green .icon {
            background: linear-gradient(135deg, rgba(52, 199, 89, 0.1) 0%, rgba(52, 199, 89, 0.2) 100%);
            color: #34C759;
        }

        .stat-card.orange .icon {
            background: linear-gradient(135deg, rgba(255, 149, 0, 0.1) 0%, rgba(255, 149, 0, 0.2) 100%);
            color: #FF9500;
        }

        .stat-card.blue .icon {
            background: linear-gradient(135deg, rgba(0, 122, 255, 0.1) 0%, rgba(0, 122, 255, 0.2) 100%);
            color: #007AFF;
        }

        .stat-value {
            font-size: 32px;
            font-weight: 700;
            color: #1D1D1F;
            margin-bottom: 4px;
        }

        .stat-label {
            font-size: 14px;
            color: #86868B;
        }

        /* TABS */
        .tabs {
            display: flex;
            gap: 8px;
            margin-bottom: 24px;
            border-bottom: 2px solid #E5E5E7;
        }

        .tab {
            padding: 12px 24px;
            background: none;
            border: none;
            color: #86868B;
            font-size: 15px;
            font-weight: 600;
            cursor: pointer;
            position: relative;
            transition: all 0.2s;
        }

        .tab:hover {
            color: #6E41C1;
        }

        .tab.active {
            color: #6E41C1;
        }

        .tab.active::after {
            content: '';
            position: absolute;
            bottom: -2px;
            left: 0;
            right: 0;
            height: 2px;
            background: #6E41C1;
        }

        .tab-content {
            display: none;
        }

        .tab-content.active {
            display: block;
        }

        /* EXERCISE CARDS */
        .exercises-grid {
            display: grid;
            gap: 16px;
        }

        .exercise-card {
            background: white;
            border-radius: 16px;
            padding: 24px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04);
            transition: all 0.3s;
            cursor: pointer;
        }

        .exercise-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 16px rgba(0, 0, 0, 0.08);
            border-left: 4px solid #6E41C1;
        }

        .exercise-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 16px;
        }

        .exercise-title {
            font-size: 18px;
            font-weight: 700;
            color: #1D1D1F;
            margin-bottom: 8px;
        }

        .exercise-meta {
            display: flex;
            gap: 16px;
            font-size: 13px;
            color: #86868B;
            margin-bottom: 12px;
        }

        .exercise-meta-item {
            display: flex;
            align-items: center;
            gap: 4px;
        }

        .exercise-type-badge {
            padding: 6px 12px;
            border-radius: 8px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
        }

        .exercise-type-badge.multipla_escolha {
            background: rgba(110, 65, 193, 0.1);
            color: #6E41C1;
        }

        .exercise-type-badge.dissertativa {
            background: rgba(0, 122, 255, 0.1);
            color: #007AFF;
        }

        .exercise-type-badge.pratica {
            background: rgba(52, 199, 89, 0.1);
            color: #34C759;
        }

        .exercise-progress {
            margin-top: 16px;
        }

        .progress-bar {
            height: 8px;
            background: #E5E5E7;
            border-radius: 4px;
            overflow: hidden;
            margin-top: 8px;
        }

        .progress-fill {
            height: 100%;
            background: linear-gradient(90deg, #6E41C1 0%, #8B5FD6 100%);
            transition: width 0.3s;
        }

        .progress-fill.complete {
            background: linear-gradient(90deg, #34C759 0%, #30D158 100%);
        }

        .exercise-score {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-top: 12px;
            padding: 12px;
            background: #F5F5F7;
            border-radius: 8px;
        }

        .score-value {
            font-size: 20px;
            font-weight: 700;
            color: #34C759;
        }

        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #86868B;
        }

        .empty-state .material-icons-outlined {
            font-size: 64px;
            opacity: 0.3;
            margin-bottom: 16px;
        }

        @media (max-width: 1200px) {
            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        @media (max-width: 768px) {
            .sidebar {
                transform: translateX(-100%);
            }

            .main-content {
                margin-left: 0;
            }

            .stats-grid {
                grid-template-columns: 1fr;
            }

            .content-wrapper {
                padding: 20px;
            }
        }
    </style>
</head>
<body>


    <!-- SIDEBAR -->
    <aside class="sidebar">
        <div class="sidebar-header">
            <a href="dashboard-aluno.php" class="sidebar-logo">
                <div class="icon">
                    <span class="material-icons-outlined">school</span>
                </div>
                <div class="text">Portal do Aluno</div>
            </a>
        </div>

        <nav class="sidebar-nav">
            <div class="nav-section">
                <div class="nav-section-title">Menu Principal</div>
                <a href="dashboard-aluno.php" class="nav-item">
                    <span class="material-icons-outlined">dashboard</span>
                    <span>Dashboard</span>
                </a>
                <a href="meus-cursos.php" class="nav-item">
                    <span class="material-icons-outlined">school</span>
                    <span>Meus Cursos</span>
                </a>
                <a href="calendario.php" class="nav-item">
                    <span class="material-icons-outlined">event</span>
                    <span>Calendário</span>
                </a>
                <a href="atividades.php" class="nav-item active">
                    <span class="material-icons-outlined">assignment</span>
                    <span>Atividades</span>
                </a>
            </div>

            <div class="nav-section">
                <div class="nav-section-title">Recursos</div>
                <a href="biblioteca.php" class="nav-item">
                    <span class="material-icons-outlined">local_library</span>
                    <span>Biblioteca</span>
                </a>
                <a href="certificados.php" class="nav-item">
                    <span class="material-icons-outlined">workspace_premium</span>
                    <span>Certificados</span>
                </a>
                <a href="avisos.php" class="nav-item">
                    <span class="material-icons-outlined">notifications</span>
                    <span>Avisos</span>
                </a>
            </div>

            <div class="nav-section">
                <div class="nav-section-title">Conta</div>
                <a href="perfil.php" class="nav-item">
                    <span class="material-icons-outlined">person</span>
                    <span>Meu Perfil</span>
                </a>
                <a href="configuracoes.php" class="nav-item">
                    <span class="material-icons-outlined">settings</span>
                    <span>Configurações</span>
                </a>
                <a href="logout-aluno.php" class="nav-item">
                    <span class="material-icons-outlined">logout</span>
                    <span>Sair</span>
                </a>
            </div>
        </nav>

        <div class="sidebar-footer">
            <div class="user-profile">
                <div class="user-avatar">
                    <?php echo strtoupper(substr($aluno['nome'] ?? 'A', 0, 1)); ?>
                </div>
                <div class="user-info">
                    <div class="user-name"><?php echo htmlspecialchars(explode(' ', $aluno['nome'] ?? 'Aluno')[0]); ?></div>
                    <div class="user-email"><?php echo htmlspecialchars(substr($aluno['email'] ?? '', 0, 20)); ?></div>
                </div>
            </div>
        </div>
    </aside>

    <!-- MAIN CONTENT -->
    <main class="main-content">
        <!-- TOPBAR -->
        <div class="topbar">
            <div class="topbar-left">
                <div class="breadcrumb">
                    <a href="dashboard-aluno.php">Dashboard</a>
                    <span class="material-icons-outlined" style="font-size: 16px;">chevron_right</span>
                    <span>Atividades</span>
                </div>
                <h1>Minhas Atividades</h1>
            </div>
            <div class="topbar-right">
                <button class="topbar-btn" title="Notificações">
                    <span class="material-icons-outlined">notifications</span>
                </button>
                <button class="topbar-btn" title="Mensagens">
                    <span class="material-icons-outlined">mail</span>
                </button>
                <button class="topbar-btn" title="Ajuda">
                    <span class="material-icons-outlined">help</span>
                </button>
            </div>
        </div>

        <!-- CONTENT -->
        <div class="content-wrapper">
            <!-- STATS CARDS -->
            <div class="stats-grid">
                <div class="stat-card purple">
                    <div class="icon">
                        <span class="material-icons-outlined">assignment</span>
                    </div>
                    <div class="stat-value"><?php echo $total_exercicios; ?></div>
                    <div class="stat-label">Total de Atividades</div>
                </div>

                <div class="stat-card green">
                    <div class="icon">
                        <span class="material-icons-outlined">check_circle</span>
                    </div>
                    <div class="stat-value"><?php echo $exercicios_concluidos_count; ?></div>
                    <div class="stat-label">Concluídas</div>
                </div>

                <div class="stat-card orange">
                    <div class="icon">
                        <span class="material-icons-outlined">pending</span>
                    </div>
                    <div class="stat-value"><?php echo $exercicios_pendentes_count; ?></div>
                    <div class="stat-label">Pendentes</div>
                </div>

                <div class="stat-card blue">
                    <div class="icon">
                        <span class="material-icons-outlined">star</span>
                    </div>
                    <div class="stat-value"><?php echo number_format($pontuacao_total, 0); ?></div>
                    <div class="stat-label">Pontos Conquistados</div>
                </div>
            </div>

            <!-- TABS -->
            <div class="tabs">
                <button class="tab active" onclick="switchTab('pendentes')">
                    <span class="material-icons-outlined" style="font-size: 18px; vertical-align: middle; margin-right: 4px;">pending_actions</span>
                    Pendentes (<?php echo count($exercicios_pendentes); ?>)
                </button>
                <button class="tab" onclick="switchTab('concluidas')">
                    <span class="material-icons-outlined" style="font-size: 18px; vertical-align: middle; margin-right: 4px;">task_alt</span>
                    Concluídas (<?php echo count($exercicios_concluidos); ?>)
                </button>
            </div>

            <!-- TAB CONTENT: PENDENTES -->
            <div id="pendentes" class="tab-content active">
                <div class="exercises-grid">
                    <?php if (empty($exercicios_pendentes)): ?>
                        <div class="empty-state">
                            <span class="material-icons-outlined">task_alt</span>
                            <p style="font-size: 18px; font-weight: 600; margin-bottom: 8px;">Parabéns!</p>
                            <p>Você não tem atividades pendentes no momento.</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($exercicios_pendentes as $exercicio): ?>
                            <?php
                            $progresso = 0;
                            if ($exercicio['total_questoes'] > 0) {
                                $progresso = round(($exercicio['questoes_respondidas'] / $exercicio['total_questoes']) * 100);
                            }

                            $tipo_labels = [
                                'multipla_escolha' => 'Múltipla Escolha',
                                'dissertativa' => 'Dissertativa',
                                'pratica' => 'Prática'
                            ];
                            ?>
                            <div class="exercise-card" onclick="window.location.href='exercicio.php?id=<?php echo $exercicio['id']; ?>'">
                                <div class="exercise-header">
                                    <div style="flex: 1;">
                                        <h3 class="exercise-title"><?php echo htmlspecialchars($exercicio['titulo']); ?></h3>
                                        <div class="exercise-meta">
                                            <div class="exercise-meta-item">
                                                <span class="material-icons-outlined" style="font-size: 16px;">school</span>
                                                <span><?php echo htmlspecialchars($exercicio['curso_nome']); ?></span>
                                            </div>
                                            <div class="exercise-meta-item">
                                                <span class="material-icons-outlined" style="font-size: 16px;">play_lesson</span>
                                                <span><?php echo htmlspecialchars($exercicio['aula_titulo']); ?></span>
                                            </div>
                                            <div class="exercise-meta-item">
                                                <span class="material-icons-outlined" style="font-size: 16px;">quiz</span>
                                                <span><?php echo $exercicio['total_questoes']; ?> questões</span>
                                            </div>
                                        </div>
                                        <?php if ($exercicio['descricao']): ?>
                                            <p style="color: #86868B; font-size: 14px; margin-top: 8px;">
                                                <?php echo htmlspecialchars(substr($exercicio['descricao'], 0, 150)); ?>
                                                <?php echo strlen($exercicio['descricao']) > 150 ? '...' : ''; ?>
                                            </p>
                                        <?php endif; ?>
                                    </div>
                                    <span class="exercise-type-badge <?php echo $exercicio['tipo']; ?>">
                                        <?php echo $tipo_labels[$exercicio['tipo']] ?? $exercicio['tipo']; ?>
                                    </span>
                                </div>

                                <?php if ($progresso > 0): ?>
                                    <div class="exercise-progress">
                                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 4px;">
                                            <span style="font-size: 13px; color: #86868B;">Progresso</span>
                                            <span style="font-size: 13px; font-weight: 600; color: #6E41C1;"><?php echo $progresso; ?>%</span>
                                        </div>
                                        <div class="progress-bar">
                                            <div class="progress-fill" style="width: <?php echo $progresso; ?>%"></div>
                                        </div>
                                        <p style="font-size: 12px; color: #86868B; margin-top: 4px;">
                                            <?php echo $exercicio['questoes_respondidas']; ?> de <?php echo $exercicio['total_questoes']; ?> questões respondidas
                                        </p>
                                    </div>
                                <?php else: ?>
                                    <div style="margin-top: 16px; padding: 12px; background: rgba(255, 149, 0, 0.1); border-radius: 8px; display: flex; align-items: center; gap: 8px;">
                                        <span class="material-icons-outlined" style="color: #FF9500; font-size: 20px;">info</span>
                                        <span style="color: #FF9500; font-size: 13px; font-weight: 600;">Atividade não iniciada</span>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

            <!-- TAB CONTENT: CONCLUÍDAS -->
            <div id="concluidas" class="tab-content">
                <div class="exercises-grid">
                    <?php if (empty($exercicios_concluidos)): ?>
                        <div class="empty-state">
                            <span class="material-icons-outlined">assignment</span>
                            <p style="font-size: 18px; font-weight: 600; margin-bottom: 8px;">Nenhuma atividade concluída</p>
                            <p>Complete suas atividades pendentes para vê-las aqui.</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($exercicios_concluidos as $exercicio): ?>
                            <?php
                            $tipo_labels = [
                                'multipla_escolha' => 'Múltipla Escolha',
                                'dissertativa' => 'Dissertativa',
                                'pratica' => 'Prática'
                            ];

                            $percentual = 0;
                            if ($exercicio['pontuacao_maxima'] > 0) {
                                $percentual = round(($exercicio['pontuacao_obtida'] / $exercicio['pontuacao_maxima']) * 100);
                            }

                            $data_conclusao = new DateTime($exercicio['data_conclusao']);
                            ?>
                            <div class="exercise-card" onclick="window.location.href='exercicio.php?id=<?php echo $exercicio['id']; ?>'">
                                <div class="exercise-header">
                                    <div style="flex: 1;">
                                        <h3 class="exercise-title"><?php echo htmlspecialchars($exercicio['titulo']); ?></h3>
                                        <div class="exercise-meta">
                                            <div class="exercise-meta-item">
                                                <span class="material-icons-outlined" style="font-size: 16px;">school</span>
                                                <span><?php echo htmlspecialchars($exercicio['curso_nome']); ?></span>
                                            </div>
                                            <div class="exercise-meta-item">
                                                <span class="material-icons-outlined" style="font-size: 16px;">play_lesson</span>
                                                <span><?php echo htmlspecialchars($exercicio['aula_titulo']); ?></span>
                                            </div>
                                            <div class="exercise-meta-item">
                                                <span class="material-icons-outlined" style="font-size: 16px;">event</span>
                                                <span>Concluída em <?php echo $data_conclusao->format('d/m/Y'); ?></span>
                                            </div>
                                        </div>
                                    </div>
                                    <span class="exercise-type-badge <?php echo $exercicio['tipo']; ?>">
                                        <?php echo $tipo_labels[$exercicio['tipo']] ?? $exercicio['tipo']; ?>
                                    </span>
                                </div>

                                <div class="exercise-score">
                                    <span class="material-icons-outlined" style="color: #34C759; font-size: 24px;">emoji_events</span>
                                    <div style="flex: 1;">
                                        <div style="display: flex; align-items: baseline; gap: 8px;">
                                            <span class="score-value"><?php echo number_format($exercicio['pontuacao_obtida'], 1); ?></span>
                                            <span style="color: #86868B; font-size: 14px;">de <?php echo $exercicio['pontuacao_maxima']; ?> pontos</span>
                                        </div>
                                        <div class="progress-bar" style="margin-top: 8px;">
                                            <div class="progress-fill complete" style="width: <?php echo $percentual; ?>%"></div>
                                        </div>
                                    </div>
                                    <div style="text-align: right;">
                                        <div style="font-size: 24px; font-weight: 700; color: #34C759;"><?php echo $percentual; ?>%</div>
                                        <div style="font-size: 12px; color: #86868B;">Aproveitamento</div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </main>

    <script>
        function switchTab(tabId) {
            // Remove active class from all tabs and contents
            document.querySelectorAll('.tab').forEach(tab => tab.classList.remove('active'));
            document.querySelectorAll('.tab-content').forEach(content => content.classList.remove('active'));

            // Add active class to clicked tab and corresponding content
            event.target.closest('.tab').classList.add('active');
            document.getElementById(tabId).classList.add('active');
        }
    </script>

</body>
</html>