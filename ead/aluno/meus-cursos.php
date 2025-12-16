<?php
/**
 * Meus Cursos - Portal do Aluno
 * Sistema EAD FaCiencia
 */

// Habilitar exibição de erros para debug
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

try {
    $aluno_model = new Aluno($pdo);
    $aluno = $aluno_model->obter_por_id($aluno_id);
} catch (Exception $e) {
    die("Erro ao buscar dados do aluno: " . $e->getMessage());
}

// ============================================================================
// CURSOS EM PROGRESSO (com progresso real)
// ============================================================================

try {
$stmt = $pdo->prepare('
    SELECT
        ia.id as inscricao_id,
        ia.curso_id,
        ia.status,
        ia.data_inscricao,
        c.nome as curso_nome,
        c.descricao,
        c.carga_horaria,
        c.instrutor,
        p.nome_empresa as parceiro_nome,
        COUNT(DISTINCT a.id) as total_aulas,
        COUNT(DISTINCT CASE WHEN pa.visualizado = 1 THEN pa.aula_id END) as aulas_concluidas,
        ROUND(
            (COUNT(DISTINCT CASE WHEN pa.visualizado = 1 THEN pa.aula_id END) * 100.0) /
            NULLIF(COUNT(DISTINCT a.id), 0),
            0
        ) as progresso
    FROM inscricoes_alunos ia
    INNER JOIN cursos c ON ia.curso_id = c.id
    INNER JOIN parceiros p ON c.parceiro_id = p.id
    LEFT JOIN ead_aulas a ON c.id = a.curso_id AND a.ativa = 1
    LEFT JOIN ead_progresso_aluno pa ON ia.id = pa.inscricao_id AND a.id = pa.aula_id
    WHERE ia.aluno_id = ? AND ia.status IN ("inscrito", "em_progresso")
    GROUP BY ia.id, ia.curso_id, ia.status, ia.data_inscricao, c.nome, c.descricao, c.carga_horaria, c.instrutor, p.nome_empresa
    ORDER BY ia.data_inscricao DESC
');
$stmt->execute([$aluno_id]);
$em_progresso = $stmt->fetchAll();
} catch (Exception $e) {
    die("Erro ao buscar cursos em progresso: " . $e->getMessage());
}

// ============================================================================
// CURSOS CONCLUÍDOS
// ============================================================================

try {
$stmt = $pdo->prepare('
    SELECT
        ia.id as inscricao_id,
        ia.curso_id,
        ia.status,
        ia.data_inscricao,
        ia.data_conclusao,
        c.nome as curso_nome,
        c.descricao,
        c.carga_horaria,
        c.instrutor,
        p.nome_empresa as parceiro_nome,
        COUNT(DISTINCT a.id) as total_aulas
    FROM inscricoes_alunos ia
    INNER JOIN cursos c ON ia.curso_id = c.id
    INNER JOIN parceiros p ON c.parceiro_id = p.id
    LEFT JOIN ead_aulas a ON c.id = a.curso_id AND a.ativa = 1
    WHERE ia.aluno_id = ? AND ia.status = "concluido"
    GROUP BY ia.id, ia.curso_id, ia.status, ia.data_inscricao, ia.data_conclusao, c.nome, c.descricao, c.carga_horaria, c.instrutor, p.nome_empresa
    ORDER BY ia.data_conclusao DESC
');
$stmt->execute([$aluno_id]);
$concluidos = $stmt->fetchAll();
} catch (Exception $e) {
    die("Erro ao buscar cursos concluídos: " . $e->getMessage());
}

// ============================================================================
// CURSOS SUSPENSOS/CANCELADOS
// ============================================================================

try {
$stmt = $pdo->prepare('
    SELECT
        ia.id as inscricao_id,
        ia.curso_id,
        ia.status,
        ia.data_inscricao,
        c.nome as curso_nome,
        c.descricao,
        c.carga_horaria,
        c.instrutor,
        p.nome_empresa as parceiro_nome,
        COUNT(DISTINCT a.id) as total_aulas,
        COUNT(DISTINCT CASE WHEN pa.visualizado = 1 THEN pa.aula_id END) as aulas_concluidas,
        ROUND(
            (COUNT(DISTINCT CASE WHEN pa.visualizado = 1 THEN pa.aula_id END) * 100.0) /
            NULLIF(COUNT(DISTINCT a.id), 0),
            0
        ) as progresso
    FROM inscricoes_alunos ia
    INNER JOIN cursos c ON ia.curso_id = c.id
    INNER JOIN parceiros p ON c.parceiro_id = p.id
    LEFT JOIN ead_aulas a ON c.id = a.curso_id AND a.ativa = 1
    LEFT JOIN ead_progresso_aluno pa ON ia.id = pa.inscricao_id AND a.id = pa.aula_id
    WHERE ia.aluno_id = ? AND ia.status = "cancelado"
    GROUP BY ia.id, ia.curso_id, ia.status, ia.data_inscricao, c.nome, c.descricao, c.carga_horaria, c.instrutor, p.nome_empresa
    ORDER BY ia.data_inscricao DESC
');
$stmt->execute([$aluno_id]);
$suspensos = $stmt->fetchAll();
} catch (Exception $e) {
    die("Erro ao buscar cursos suspensos: " . $e->getMessage());
}

?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Meus Cursos - Portal do Aluno</title>
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

        /* ============================================
           SIDEBAR (mesma do dashboard)
        ============================================ */
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

        .user-email {
            font-size: 12px;
            color: #86868B;
        }

        /* ============================================
           MAIN CONTENT
        ============================================ */
        .main-content {
            margin-left: 280px;
            min-height: 100vh;
        }

        .topbar {
            background: white;
            border-bottom: 1px solid #E5E5E7;
            padding: 20px 40px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            position: sticky;
            top: 0;
            z-index: 100;
        }

        .topbar-left h1 {
            font-size: 28px;
            font-weight: 700;
            color: #1D1D1F;
        }

        .topbar-left p {
            font-size: 14px;
            color: #86868B;
            margin-top: 4px;
        }

        .topbar-right {
            display: flex;
            align-items: center;
            gap: 16px;
        }

        .topbar-btn {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: #F5F5F7;
            border: none;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.2s;
        }

        .topbar-btn:hover {
            background: #E5E5E7;
        }

        .content-wrapper {
            padding: 40px;
        }

        /* ============================================
           TABS
        ============================================ */
        .tabs-container {
            margin-bottom: 32px;
        }

        .tabs {
            display: flex;
            gap: 8px;
            border-bottom: 2px solid #E5E5E7;
            margin-bottom: 32px;
        }

        .tab {
            padding: 14px 24px;
            background: none;
            border: none;
            font-size: 15px;
            font-weight: 600;
            color: #86868B;
            cursor: pointer;
            transition: all 0.2s;
            position: relative;
            display: flex;
            align-items: center;
            gap: 8px;
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

        .tab-badge {
            background: #E5E5E7;
            color: #86868B;
            padding: 2px 8px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 600;
        }

        .tab.active .tab-badge {
            background: linear-gradient(135deg, rgba(110, 65, 193, 0.15) 0%, rgba(139, 95, 214, 0.15) 100%);
            color: #6E41C1;
        }

        .tab-content {
            display: none;
        }

        .tab-content.active {
            display: block;
        }

        /* ============================================
           COURSE CARDS
        ============================================ */
        .courses-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(340px, 1fr));
            gap: 24px;
        }

        .course-card {
            background: white;
            border-radius: 16px;
            border: 1px solid #E5E5E7;
            overflow: hidden;
            transition: all 0.3s;
            cursor: pointer;
        }

        .course-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 12px 24px rgba(0, 0, 0, 0.08);
            border-color: #6E41C1;
        }

        .course-card-header {
            padding: 24px;
            background: linear-gradient(135deg, #6E41C1 0%, #8B5FD6 100%);
            color: white;
        }

        .course-card-header.completed {
            background: linear-gradient(135deg, #34C759 0%, #30D158 100%);
        }

        .course-card-header.suspended {
            background: linear-gradient(135deg, #FF9500 0%, #FF9F0A 100%);
        }

        .course-card-title {
            font-size: 18px;
            font-weight: 700;
            margin-bottom: 8px;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
            min-height: 48px;
        }

        .course-card-meta {
            display: flex;
            align-items: center;
            gap: 16px;
            font-size: 13px;
            opacity: 0.9;
        }

        .course-card-meta-item {
            display: flex;
            align-items: center;
            gap: 4px;
        }

        .course-card-body {
            padding: 24px;
        }

        .course-card-instructor {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 13px;
            color: #86868B;
            margin-bottom: 12px;
        }

        .course-card-description {
            font-size: 14px;
            color: #86868B;
            line-height: 1.6;
            margin-bottom: 20px;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        .course-progress {
            margin-bottom: 20px;
        }

        .course-progress-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 8px;
        }

        .course-progress-label {
            font-size: 13px;
            color: #86868B;
            font-weight: 500;
        }

        .course-progress-value {
            font-size: 13px;
            font-weight: 700;
            color: #6E41C1;
        }

        .course-progress-value.completed {
            color: #34C759;
        }

        .course-progress-bar {
            height: 8px;
            background: #F5F5F7;
            border-radius: 4px;
            overflow: hidden;
        }

        .course-progress-fill {
            height: 100%;
            background: linear-gradient(90deg, #6E41C1 0%, #8B5FD6 100%);
            border-radius: 4px;
            transition: width 0.3s;
        }

        .course-progress-fill.completed {
            background: linear-gradient(90deg, #34C759 0%, #30D158 100%);
        }

        .course-card-footer {
            display: flex;
            gap: 12px;
        }

        .btn {
            padding: 12px 20px;
            border-radius: 10px;
            font-size: 14px;
            font-weight: 600;
            border: none;
            cursor: pointer;
            transition: all 0.2s;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            text-decoration: none;
        }

        .btn-primary {
            background: linear-gradient(135deg, #6E41C1 0%, #8B5FD6 100%);
            color: white;
            flex: 1;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 16px rgba(110, 65, 193, 0.3);
        }

        .btn-success {
            background: linear-gradient(135deg, #34C759 0%, #30D158 100%);
            color: white;
            flex: 1;
        }

        .btn-success:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 16px rgba(52, 199, 89, 0.3);
        }

        .btn-warning {
            background: linear-gradient(135deg, #FF9500 0%, #FF9F0A 100%);
            color: white;
            flex: 1;
        }

        .btn-warning:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 16px rgba(255, 149, 0, 0.3);
        }

        /* ============================================
           EMPTY STATE
        ============================================ */
        .empty-state {
            background: white;
            border-radius: 16px;
            border: 1px solid #E5E5E7;
            padding: 60px 40px;
            text-align: center;
        }

        .empty-state-icon {
            width: 80px;
            height: 80px;
            margin: 0 auto 24px;
            background: linear-gradient(135deg, rgba(110, 65, 193, 0.1) 0%, rgba(139, 95, 214, 0.1) 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #6E41C1;
            font-size: 40px;
        }

        .empty-state-title {
            font-size: 20px;
            font-weight: 700;
            color: #1D1D1F;
            margin-bottom: 12px;
        }

        .empty-state-text {
            font-size: 14px;
            color: #86868B;
            margin-bottom: 24px;
            line-height: 1.6;
        }

        /* ============================================
           RESPONSIVE
        ============================================ */
        @media (max-width: 1024px) {
            .sidebar {
                transform: translateX(-100%);
            }

            .main-content {
                margin-left: 0;
            }

            .content-wrapper {
                padding: 24px;
            }

            .courses-grid {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 640px) {
            .topbar {
                padding: 16px 20px;
            }

            .topbar-left h1 {
                font-size: 22px;
            }

            .tabs {
                overflow-x: auto;
                -webkit-overflow-scrolling: touch;
            }

            .tab {
                white-space: nowrap;
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
                <a href="meus-cursos.php" class="nav-item active">
                    <span class="material-icons-outlined">school</span>
                    <span>Meus Cursos</span>
                </a>
                <a href="calendario.php" class="nav-item">
                    <span class="material-icons-outlined">event</span>
                    <span>Calendário</span>
                </a>
                <a href="atividades.php" class="nav-item">
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
                <a href="logout.php" class="nav-item">
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
                <h1>Meus Cursos</h1>
                <p>Gerencie todos os seus cursos em um só lugar</p>
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
            <!-- TABS -->
            <div class="tabs-container">
                <div class="tabs">
                    <button class="tab active" onclick="switchTab('em-progresso')">
                        <span class="material-icons-outlined" style="font-size: 20px;">play_circle</span>
                        Em Progresso
                        <span class="tab-badge"><?php echo count($em_progresso); ?></span>
                    </button>
                    <button class="tab" onclick="switchTab('concluidos')">
                        <span class="material-icons-outlined" style="font-size: 20px;">check_circle</span>
                        Concluídos
                        <span class="tab-badge"><?php echo count($concluidos); ?></span>
                    </button>
                    <button class="tab" onclick="switchTab('suspensos')">
                        <span class="material-icons-outlined" style="font-size: 20px;">pause_circle</span>
                        Suspensos
                        <span class="tab-badge"><?php echo count($suspensos); ?></span>
                    </button>
                </div>

                <!-- TAB CONTENT: EM PROGRESSO -->
                <div id="em-progresso" class="tab-content active">
                    <?php if (empty($em_progresso)): ?>
                        <div class="empty-state">
                            <div class="empty-state-icon">
                                <span class="material-icons-outlined">play_circle</span>
                            </div>
                            <h3 class="empty-state-title">Nenhum curso em progresso</h3>
                            <p class="empty-state-text">
                                Você ainda não iniciou nenhum curso. Explore nosso catálogo e comece sua jornada de aprendizado!
                            </p>
                            <a href="dashboard-aluno.php" class="btn btn-primary">
                                <span class="material-icons-outlined">explore</span>
                                Explorar Cursos
                            </a>
                        </div>
                    <?php else: ?>
                        <div class="courses-grid">
                            <?php foreach ($em_progresso as $curso): ?>
                                <div class="course-card" onclick="window.location.href='curso.php?id=<?php echo $curso['curso_id']; ?>'">
                                    <div class="course-card-header">
                                        <h3 class="course-card-title"><?php echo htmlspecialchars($curso['curso_nome']); ?></h3>
                                        <div class="course-card-meta">
                                            <div class="course-card-meta-item">
                                                <span class="material-icons-outlined" style="font-size: 16px;">play_circle</span>
                                                <span><?php echo $curso['total_aulas']; ?> aulas</span>
                                            </div>
                                            <?php if ($curso['carga_horaria']): ?>
                                                <div class="course-card-meta-item">
                                                    <span class="material-icons-outlined" style="font-size: 16px;">schedule</span>
                                                    <span><?php echo $curso['carga_horaria']; ?>h</span>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    <div class="course-card-body">
                                        <?php if ($curso['instrutor']): ?>
                                            <div class="course-card-instructor">
                                                <span class="material-icons-outlined" style="font-size: 16px;">person</span>
                                                <span><?php echo htmlspecialchars($curso['instrutor']); ?></span>
                                            </div>
                                        <?php endif; ?>

                                        <?php if ($curso['descricao']): ?>
                                            <p class="course-card-description"><?php echo htmlspecialchars($curso['descricao']); ?></p>
                                        <?php endif; ?>

                                        <div class="course-progress">
                                            <div class="course-progress-header">
                                                <span class="course-progress-label">Progresso do curso</span>
                                                <span class="course-progress-value"><?php echo $curso['progresso'] ?? 0; ?>%</span>
                                            </div>
                                            <div class="course-progress-bar">
                                                <div class="course-progress-fill" style="width: <?php echo $curso['progresso'] ?? 0; ?>%"></div>
                                            </div>
                                        </div>

                                        <div class="course-card-footer">
                                            <a href="curso.php?id=<?php echo $curso['curso_id']; ?>" class="btn btn-primary" onclick="event.stopPropagation()">
                                                <span class="material-icons-outlined" style="font-size: 18px;">play_arrow</span>
                                                Continuar Estudando
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- TAB CONTENT: CONCLUÍDOS -->
                <div id="concluidos" class="tab-content">
                    <?php if (empty($concluidos)): ?>
                        <div class="empty-state">
                            <div class="empty-state-icon">
                                <span class="material-icons-outlined">check_circle</span>
                            </div>
                            <h3 class="empty-state-title">Nenhum curso concluído</h3>
                            <p class="empty-state-text">
                                Complete seus cursos para receber certificados e conquistar novas habilidades!
                            </p>
                        </div>
                    <?php else: ?>
                        <div class="courses-grid">
                            <?php foreach ($concluidos as $curso): ?>
                                <div class="course-card" onclick="window.location.href='curso.php?id=<?php echo $curso['curso_id']; ?>'">
                                    <div class="course-card-header completed">
                                        <h3 class="course-card-title"><?php echo htmlspecialchars($curso['curso_nome']); ?></h3>
                                        <div class="course-card-meta">
                                            <div class="course-card-meta-item">
                                                <span class="material-icons-outlined" style="font-size: 16px;">play_circle</span>
                                                <span><?php echo $curso['total_aulas']; ?> aulas</span>
                                            </div>
                                            <?php if ($curso['carga_horaria']): ?>
                                                <div class="course-card-meta-item">
                                                    <span class="material-icons-outlined" style="font-size: 16px;">schedule</span>
                                                    <span><?php echo $curso['carga_horaria']; ?>h</span>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    <div class="course-card-body">
                                        <?php if ($curso['instrutor']): ?>
                                            <div class="course-card-instructor">
                                                <span class="material-icons-outlined" style="font-size: 16px;">person</span>
                                                <span><?php echo htmlspecialchars($curso['instrutor']); ?></span>
                                            </div>
                                        <?php endif; ?>

                                        <?php if ($curso['descricao']): ?>
                                            <p class="course-card-description"><?php echo htmlspecialchars($curso['descricao']); ?></p>
                                        <?php endif; ?>

                                        <div class="course-progress">
                                            <div class="course-progress-header">
                                                <span class="course-progress-label">Concluído em <?php echo date('d/m/Y', strtotime($curso['data_conclusao'])); ?></span>
                                                <span class="course-progress-value completed">100%</span>
                                            </div>
                                            <div class="course-progress-bar">
                                                <div class="course-progress-fill completed" style="width: 100%"></div>
                                            </div>
                                        </div>

                                        <div class="course-card-footer">
                                            <a href="certificados.php?curso_id=<?php echo $curso['curso_id']; ?>" class="btn btn-success" onclick="event.stopPropagation()">
                                                <span class="material-icons-outlined" style="font-size: 18px;">workspace_premium</span>
                                                Ver Certificado
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- TAB CONTENT: SUSPENSOS -->
                <div id="suspensos" class="tab-content">
                    <?php if (empty($suspensos)): ?>
                        <div class="empty-state">
                            <div class="empty-state-icon">
                                <span class="material-icons-outlined">pause_circle</span>
                            </div>
                            <h3 class="empty-state-title">Nenhum curso suspenso</h3>
                            <p class="empty-state-text">
                                Você não possui cursos suspensos no momento.
                            </p>
                        </div>
                    <?php else: ?>
                        <div class="courses-grid">
                            <?php foreach ($suspensos as $curso): ?>
                                <div class="course-card" onclick="window.location.href='curso.php?id=<?php echo $curso['curso_id']; ?>'">
                                    <div class="course-card-header suspended">
                                        <h3 class="course-card-title"><?php echo htmlspecialchars($curso['curso_nome']); ?></h3>
                                        <div class="course-card-meta">
                                            <div class="course-card-meta-item">
                                                <span class="material-icons-outlined" style="font-size: 16px;">play_circle</span>
                                                <span><?php echo $curso['total_aulas']; ?> aulas</span>
                                            </div>
                                            <?php if ($curso['carga_horaria']): ?>
                                                <div class="course-card-meta-item">
                                                    <span class="material-icons-outlined" style="font-size: 16px;">schedule</span>
                                                    <span><?php echo $curso['carga_horaria']; ?>h</span>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    <div class="course-card-body">
                                        <?php if ($curso['instrutor']): ?>
                                            <div class="course-card-instructor">
                                                <span class="material-icons-outlined" style="font-size: 16px;">person</span>
                                                <span><?php echo htmlspecialchars($curso['instrutor']); ?></span>
                                            </div>
                                        <?php endif; ?>

                                        <?php if ($curso['descricao']): ?>
                                            <p class="course-card-description"><?php echo htmlspecialchars($curso['descricao']); ?></p>
                                        <?php endif; ?>

                                        <div class="course-progress">
                                            <div class="course-progress-header">
                                                <span class="course-progress-label">Progresso antes da suspensão</span>
                                                <span class="course-progress-value"><?php echo $curso['progresso'] ?? 0; ?>%</span>
                                            </div>
                                            <div class="course-progress-bar">
                                                <div class="course-progress-fill" style="width: <?php echo $curso['progresso'] ?? 0; ?>%"></div>
                                            </div>
                                        </div>

                                        <div class="course-card-footer">
                                            <a href="curso.php?id=<?php echo $curso['curso_id']; ?>" class="btn btn-warning" onclick="event.stopPropagation()">
                                                <span class="material-icons-outlined" style="font-size: 18px;">play_arrow</span>
                                                Retomar Curso
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
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

