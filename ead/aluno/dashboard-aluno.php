<?php
/**
 * Dashboard do Aluno - Sistema EAD FaCiencia
 * Design Moderno e Completo
 */

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
// ESTATÍSTICAS GERAIS
// ============================================================================

// Total de cursos inscritos
$stmt = $pdo->prepare('SELECT COUNT(*) as total FROM inscricoes_alunos WHERE aluno_id = ?');
$stmt->execute([$aluno_id]);
$total_cursos = $stmt->fetch()['total'] ?? 0;

// Cursos concluídos
$stmt = $pdo->prepare('SELECT COUNT(*) as total FROM inscricoes_alunos WHERE aluno_id = ? AND status = "concluido"');
$stmt->execute([$aluno_id]);
$cursos_concluidos = $stmt->fetch()['total'] ?? 0;

// Cursos em progresso
$stmt = $pdo->prepare('SELECT COUNT(*) as total FROM inscricoes_alunos WHERE aluno_id = ? AND status IN ("inscrito", "em_progresso")');
$stmt->execute([$aluno_id]);
$cursos_em_progresso = $stmt->fetch()['total'] ?? 0;

// Horas estudadas (soma do tempo gasto em minutos)
$stmt = $pdo->prepare('
    SELECT COALESCE(SUM(tempo_gasto_minutos), 0) as total_minutos
    FROM ead_progresso_aluno pa
    INNER JOIN inscricoes_alunos ia ON pa.inscricao_id = ia.id
    WHERE ia.aluno_id = ?
');
$stmt->execute([$aluno_id]);
$total_minutos = $stmt->fetch()['total_minutos'] ?? 0;
$horas_estudadas = round($total_minutos / 60, 1);

// ============================================================================
// CURSOS EM ANDAMENTO (com progresso real)
// ============================================================================

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
        COUNT(DISTINCT a.id) as total_aulas,
        COUNT(DISTINCT CASE WHEN pa.visualizado = 1 THEN pa.aula_id END) as aulas_concluidas,
        ROUND(
            (COUNT(DISTINCT CASE WHEN pa.visualizado = 1 THEN pa.aula_id END) * 100.0) /
            NULLIF(COUNT(DISTINCT a.id), 0),
            0
        ) as progresso
    FROM inscricoes_alunos ia
    INNER JOIN cursos c ON ia.curso_id = c.id
    LEFT JOIN ead_aulas a ON c.id = a.curso_id AND a.ativa = 1
    LEFT JOIN ead_progresso_aluno pa ON ia.id = pa.inscricao_id AND a.id = pa.aula_id
    WHERE ia.aluno_id = ? AND ia.status IN ("inscrito", "em_progresso")
    GROUP BY ia.id, ia.curso_id, ia.status, ia.data_inscricao, c.nome, c.descricao, c.carga_horaria, c.instrutor
    ORDER BY ia.data_inscricao DESC
    LIMIT 4
');
$stmt->execute([$aluno_id]);
$cursos_andamento = $stmt->fetchAll();

// ============================================================================
// PRÓXIMAS AULAS (aulas não visualizadas dos cursos em andamento)
// ============================================================================

$stmt = $pdo->prepare('
    SELECT
        a.id as aula_id,
        a.titulo as aula_titulo,
        a.descricao as aula_descricao,
        a.duracao_minutos,
        a.ordem,
        c.id as curso_id,
        c.nome as curso_nome,
        ia.id as inscricao_id
    FROM ead_aulas a
    INNER JOIN cursos c ON a.curso_id = c.id
    INNER JOIN inscricoes_alunos ia ON c.id = ia.curso_id
    LEFT JOIN ead_progresso_aluno pa ON a.id = pa.aula_id AND ia.id = pa.inscricao_id
    WHERE ia.aluno_id = ?
        AND ia.status IN ("inscrito", "em_progresso")
        AND a.ativa = 1
        AND (pa.visualizado IS NULL OR pa.visualizado = 0)
    ORDER BY a.ordem ASC
    LIMIT 3
');
$stmt->execute([$aluno_id]);
$proximas_aulas = $stmt->fetchAll();

// ============================================================================
// ATIVIDADES RECENTES
// ============================================================================

$stmt = $pdo->prepare('
    SELECT
        "aula" as tipo,
        a.titulo as titulo,
        c.nome as curso_nome,
        pa.data_visualizacao as data_acao,
        pa.tempo_gasto_minutos
    FROM ead_progresso_aluno pa
    INNER JOIN ead_aulas a ON pa.aula_id = a.id
    INNER JOIN inscricoes_alunos ia ON pa.inscricao_id = ia.id
    INNER JOIN cursos c ON ia.curso_id = c.id
    WHERE ia.aluno_id = ? AND pa.visualizado = 1
    ORDER BY pa.data_visualizacao DESC
    LIMIT 5
');
$stmt->execute([$aluno_id]);
$atividades_recentes = $stmt->fetchAll();

?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Portal do Aluno</title>
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
           SIDEBAR
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
            position: relative;
        }

        .topbar-btn:hover {
            background: #E5E5E7;
        }

        .topbar-btn .badge {
            position: absolute;
            top: 6px;
            right: 6px;
            width: 8px;
            height: 8px;
            background: #FF3B30;
            border-radius: 50%;
            border: 2px solid white;
        }

        .content-wrapper {
            padding: 40px;
        }

        /* ============================================
           STATS CARDS
        ============================================ */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 24px;
            margin-bottom: 40px;
        }

        .stat-card {
            background: white;
            border-radius: 16px;
            padding: 24px;
            border: 1px solid #E5E5E7;
            transition: all 0.3s;
        }

        .stat-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 12px 24px rgba(0, 0, 0, 0.08);
        }

        .stat-card-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 16px;
        }

        .stat-card-icon {
            width: 48px;
            height: 48px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
        }

        .stat-card-icon.purple {
            background: linear-gradient(135deg, rgba(110, 65, 193, 0.1) 0%, rgba(139, 95, 214, 0.1) 100%);
            color: #6E41C1;
        }

        .stat-card-icon.green {
            background: linear-gradient(135deg, rgba(52, 199, 89, 0.1) 0%, rgba(48, 209, 88, 0.1) 100%);
            color: #34C759;
        }

        .stat-card-icon.blue {
            background: linear-gradient(135deg, rgba(0, 122, 255, 0.1) 0%, rgba(10, 132, 255, 0.1) 100%);
            color: #007AFF;
        }

        .stat-card-icon.orange {
            background: linear-gradient(135deg, rgba(255, 149, 0, 0.1) 0%, rgba(255, 159, 10, 0.1) 100%);
            color: #FF9500;
        }

        .stat-card-value {
            font-size: 32px;
            font-weight: 700;
            color: #1D1D1F;
            margin-bottom: 4px;
        }

        .stat-card-label {
            font-size: 14px;
            color: #86868B;
            font-weight: 500;
        }

        /* ============================================
           SECTION HEADERS
        ============================================ */
        .section-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 24px;
        }

        .section-title {
            font-size: 20px;
            font-weight: 700;
            color: #1D1D1F;
        }

        .section-link {
            color: #6E41C1;
            text-decoration: none;
            font-size: 14px;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 4px;
            transition: all 0.2s;
        }

        .section-link:hover {
            gap: 8px;
        }

        /* ============================================
           COURSE CARDS
        ============================================ */
        .courses-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
            gap: 24px;
            margin-bottom: 40px;
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

        .course-card-title {
            font-size: 18px;
            font-weight: 700;
            margin-bottom: 8px;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
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

        .course-card-description {
            font-size: 14px;
            color: #86868B;
            line-height: 1.6;
            margin-bottom: 20px;
            display: -webkit-box;
            -webkit-line-clamp: 3;
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

        .btn-outline {
            background: white;
            color: #6E41C1;
            border: 1.5px solid #6E41C1;
        }

        .btn-outline:hover {
            background: #6E41C1;
            color: white;
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

            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }

            .courses-grid {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 640px) {
            .stats-grid {
                grid-template-columns: 1fr;
            }

            .topbar {
                padding: 16px 20px;
            }

            .topbar-left h1 {
                font-size: 22px;
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
                <a href="dashboard-aluno.php" class="nav-item active">
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
                <h1>Olá, <?php echo htmlspecialchars(explode(' ', $aluno['nome'] ?? 'Aluno')[0]); ?>! 👋</h1>
                <p>Bem-vindo de volta ao seu painel de estudos</p>
            </div>
            <div class="topbar-right">
                <button class="topbar-btn" title="Notificações">
                    <span class="material-icons-outlined">notifications</span>
                    <span class="badge"></span>
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
                <div class="stat-card">
                    <div class="stat-card-header">
                        <div class="stat-card-icon purple">
                            <span class="material-icons-outlined">school</span>
                        </div>
                    </div>
                    <div class="stat-card-value"><?php echo $total_cursos; ?></div>
                    <div class="stat-card-label">Cursos Inscritos</div>
                </div>

                <div class="stat-card">
                    <div class="stat-card-header">
                        <div class="stat-card-icon green">
                            <span class="material-icons-outlined">check_circle</span>
                        </div>
                    </div>
                    <div class="stat-card-value"><?php echo $cursos_concluidos; ?></div>
                    <div class="stat-card-label">Cursos Concluídos</div>
                </div>

                <div class="stat-card">
                    <div class="stat-card-header">
                        <div class="stat-card-icon blue">
                            <span class="material-icons-outlined">play_circle</span>
                        </div>
                    </div>
                    <div class="stat-card-value"><?php echo $cursos_em_progresso; ?></div>
                    <div class="stat-card-label">Em Andamento</div>
                </div>

                <div class="stat-card">
                    <div class="stat-card-header">
                        <div class="stat-card-icon orange">
                            <span class="material-icons-outlined">schedule</span>
                        </div>
                    </div>
                    <div class="stat-card-value"><?php echo $horas_estudadas; ?>h</div>
                    <div class="stat-card-label">Horas Estudadas</div>
                </div>
            </div>

            <!-- CURSOS EM ANDAMENTO -->
            <div class="section-header">
                <h2 class="section-title">Meus Cursos em Andamento</h2>
                <a href="meus-cursos.php" class="section-link">
                    Ver todos
                    <span class="material-icons-outlined" style="font-size: 18px;">arrow_forward</span>
                </a>
            </div>

            <?php if (empty($cursos_andamento)): ?>
                <div class="empty-state">
                    <div class="empty-state-icon">
                        <span class="material-icons-outlined">school</span>
                    </div>
                    <h3 class="empty-state-title">Nenhum curso em andamento</h3>
                    <p class="empty-state-text">
                        Você ainda não iniciou nenhum curso. Explore nosso catálogo e comece sua jornada de aprendizado!
                    </p>
                    <a href="meus-cursos.php" class="btn btn-primary">
                        <span class="material-icons-outlined">add</span>
                        Explorar Cursos
                    </a>
                </div>
            <?php else: ?>
                <div class="courses-grid">
                    <?php foreach ($cursos_andamento as $curso): ?>
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
    </main>

</body>
</html>

