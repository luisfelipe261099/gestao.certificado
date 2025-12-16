<?php
/**
 * Curso - Visualização Individual do Curso
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

// Verificar se o ID do curso foi fornecido
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header('Location: meus-cursos.php');
    exit;
}

$aluno_id = $_SESSION['usuario_id'];
$curso_id = (int)$_GET['id'];

$aluno_model = new Aluno($pdo);
$aluno = $aluno_model->obter_por_id($aluno_id);

// ============================================================================
// BUSCAR DADOS DO CURSO E VERIFICAR SE O ALUNO ESTÁ INSCRITO
// ============================================================================

try {
    $stmt = $pdo->prepare('
        SELECT
            c.*,
            p.nome_empresa as parceiro_nome,
            ia.id as inscricao_id,
            ia.status as status_inscricao,
            ia.data_inscricao,
            ia.data_conclusao
        FROM cursos c
        INNER JOIN parceiros p ON c.parceiro_id = p.id
        LEFT JOIN inscricoes_alunos ia ON c.id = ia.curso_id AND ia.aluno_id = ?
        WHERE c.id = ?
    ');
    $stmt->execute([$aluno_id, $curso_id]);
    $curso = $stmt->fetch();

    if (!$curso) {
        die("Curso não encontrado.");
    }

    // Verificar se o aluno está inscrito
    if (!$curso['inscricao_id']) {
        die("Você não está inscrito neste curso.");
    }

} catch (Exception $e) {
    die("Erro ao buscar dados do curso: " . $e->getMessage());
}

// ============================================================================
// BUSCAR AULAS DO CURSO COM PROGRESSO
// ============================================================================

try {
    $stmt = $pdo->prepare('
        SELECT
            a.id,
            a.titulo,
            a.descricao,
            a.ordem,
            a.duracao_minutos,
            a.ativa,
            pa.visualizado,
            pa.tempo_gasto_minutos,
            pa.data_visualizacao,
            (SELECT COUNT(*) FROM conteudo_aulas WHERE aula_id = a.id) as total_conteudos,
            (SELECT COUNT(*) FROM ead_exercicios WHERE aula_id = a.id) as total_exercicios
        FROM ead_aulas a
        LEFT JOIN ead_progresso_aluno pa ON a.id = pa.aula_id AND pa.inscricao_id = ?
        WHERE a.curso_id = ? AND a.ativa = 1
        ORDER BY a.ordem ASC
    ');
    $stmt->execute([$curso['inscricao_id'], $curso_id]);
    $aulas = $stmt->fetchAll();

} catch (Exception $e) {
    die("Erro ao buscar aulas: " . $e->getMessage());
}

// ============================================================================
// CALCULAR ESTATÍSTICAS DO CURSO
// ============================================================================

$total_aulas = count($aulas);
$aulas_concluidas = 0;
$tempo_total_gasto = 0;

foreach ($aulas as $aula) {
    if ($aula['visualizado']) {
        $aulas_concluidas++;
    }
    $tempo_total_gasto += $aula['tempo_gasto_minutos'] ?? 0;
}

$progresso = $total_aulas > 0 ? round(($aulas_concluidas / $total_aulas) * 100) : 0;
$horas_estudadas = floor($tempo_total_gasto / 60);
$minutos_estudados = $tempo_total_gasto % 60;

?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($curso['nome']); ?> - Portal do Aluno</title>
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

        .topbar-left {
            flex: 1;
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

        .breadcrumb a:hover {
            text-decoration: underline;
        }

        .topbar-left h1 {
            font-size: 28px;
            font-weight: 700;
            color: #1D1D1F;
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
           COURSE HEADER
        ============================================ */
        .course-header {
            background: linear-gradient(135deg, #6E41C1 0%, #8B5FD6 100%);
            border-radius: 20px;
            padding: 40px;
            color: white;
            margin-bottom: 32px;
        }

        .course-header-top {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 24px;
        }

        .course-info h2 {
            font-size: 32px;
            font-weight: 700;
            margin-bottom: 12px;
        }

        .course-meta {
            display: flex;
            align-items: center;
            gap: 24px;
            font-size: 14px;
            opacity: 0.9;
        }

        .course-meta-item {
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .course-status {
            padding: 8px 16px;
            background: rgba(255, 255, 255, 0.2);
            border-radius: 20px;
            font-size: 13px;
            font-weight: 600;
        }

        .course-description {
            font-size: 15px;
            line-height: 1.6;
            opacity: 0.95;
            margin-bottom: 24px;
        }

        .course-stats {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 20px;
        }

        .stat-item {
            background: rgba(255, 255, 255, 0.15);
            padding: 20px;
            border-radius: 12px;
            backdrop-filter: blur(10px);
        }

        .stat-value {
            font-size: 28px;
            font-weight: 700;
            margin-bottom: 4px;
        }

        .stat-label {
            font-size: 13px;
            opacity: 0.9;
        }

        /* ============================================
           PROGRESS BAR
        ============================================ */
        .progress-section {
            background: white;
            border-radius: 16px;
            padding: 24px;
            border: 1px solid #E5E5E7;
            margin-bottom: 32px;
        }

        .progress-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 16px;
        }

        .progress-title {
            font-size: 18px;
            font-weight: 700;
            color: #1D1D1F;
        }

        .progress-value {
            font-size: 24px;
            font-weight: 700;
            color: #6E41C1;
        }

        .progress-bar {
            height: 12px;
            background: #F5F5F7;
            border-radius: 6px;
            overflow: hidden;
        }

        .progress-fill {
            height: 100%;
            background: linear-gradient(90deg, #6E41C1 0%, #8B5FD6 100%);
            border-radius: 6px;
            transition: width 0.3s;
        }

        /* ============================================
           LESSONS LIST
        ============================================ */
        .lessons-section {
            background: white;
            border-radius: 16px;
            border: 1px solid #E5E5E7;
            overflow: hidden;
        }

        .lessons-header {
            padding: 24px;
            border-bottom: 1px solid #E5E5E7;
        }

        .lessons-header h3 {
            font-size: 20px;
            font-weight: 700;
            color: #1D1D1F;
        }

        .lessons-list {
            list-style: none;
        }

        .lesson-item {
            border-bottom: 1px solid #E5E5E7;
            transition: all 0.2s;
        }

        .lesson-item:last-child {
            border-bottom: none;
        }

        .lesson-item:hover {
            background: #F5F5F7;
        }

        .lesson-link {
            display: flex;
            align-items: center;
            gap: 20px;
            padding: 20px 24px;
            text-decoration: none;
            color: #1D1D1F;
        }

        .lesson-number {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: #F5F5F7;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 14px;
            color: #86868B;
            flex-shrink: 0;
        }

        .lesson-item.completed .lesson-number {
            background: linear-gradient(135deg, #34C759 0%, #30D158 100%);
            color: white;
        }

        .lesson-content {
            flex: 1;
        }

        .lesson-title {
            font-size: 16px;
            font-weight: 600;
            color: #1D1D1F;
            margin-bottom: 4px;
        }

        .lesson-meta {
            display: flex;
            align-items: center;
            gap: 16px;
            font-size: 13px;
            color: #86868B;
        }

        .lesson-meta-item {
            display: flex;
            align-items: center;
            gap: 4px;
        }

        .lesson-status {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 8px 16px;
            border-radius: 20px;
            font-size: 13px;
            font-weight: 600;
        }

        .lesson-status.completed {
            background: rgba(52, 199, 89, 0.1);
            color: #34C759;
        }

        .lesson-status.in-progress {
            background: rgba(110, 65, 193, 0.1);
            color: #6E41C1;
        }

        .lesson-status.not-started {
            background: #F5F5F7;
            color: #86868B;
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

            .course-stats {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        @media (max-width: 640px) {
            .topbar {
                padding: 16px 20px;
            }

            .course-header {
                padding: 24px;
            }

            .course-info h2 {
                font-size: 24px;
            }

            .course-stats {
                grid-template-columns: 1fr;
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
                    <a href="meus-cursos.php">Meus Cursos</a>
                    <span class="material-icons-outlined" style="font-size: 16px;">chevron_right</span>
                    <span><?php echo htmlspecialchars($curso['nome']); ?></span>
                </div>
                <h1><?php echo htmlspecialchars($curso['nome']); ?></h1>
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
            <!-- COURSE HEADER -->
            <div class="course-header">
                <div class="course-header-top">
                    <div class="course-info">
                        <h2><?php echo htmlspecialchars($curso['nome']); ?></h2>
                        <div class="course-meta">
                            <?php if ($curso['instrutor']): ?>
                                <div class="course-meta-item">
                                    <span class="material-icons-outlined" style="font-size: 18px;">person</span>
                                    <span><?php echo htmlspecialchars($curso['instrutor']); ?></span>
                                </div>
                            <?php endif; ?>
                            <div class="course-meta-item">
                                <span class="material-icons-outlined" style="font-size: 18px;">business</span>
                                <span><?php echo htmlspecialchars($curso['parceiro_nome']); ?></span>
                            </div>
                            <?php if ($curso['carga_horaria']): ?>
                                <div class="course-meta-item">
                                    <span class="material-icons-outlined" style="font-size: 18px;">schedule</span>
                                    <span><?php echo $curso['carga_horaria']; ?>h</span>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="course-status">
                        <?php
                        $status_labels = [
                            'inscrito' => 'Inscrito',
                            'em_progresso' => 'Em Progresso',
                            'concluido' => 'Concluído',
                            'cancelado' => 'Cancelado'
                        ];
                        echo $status_labels[$curso['status_inscricao']] ?? 'Inscrito';
                        ?>
                    </div>
                </div>

                <?php if ($curso['descricao']): ?>
                    <p class="course-description"><?php echo htmlspecialchars($curso['descricao']); ?></p>
                <?php endif; ?>

                <div class="course-stats">
                    <div class="stat-item">
                        <div class="stat-value"><?php echo $total_aulas; ?></div>
                        <div class="stat-label">Aulas Totais</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-value"><?php echo $aulas_concluidas; ?></div>
                        <div class="stat-label">Aulas Concluídas</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-value"><?php echo $progresso; ?>%</div>
                        <div class="stat-label">Progresso</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-value"><?php echo $horas_estudadas; ?>h <?php echo $minutos_estudados; ?>m</div>
                        <div class="stat-label">Tempo Estudado</div>
                    </div>
                </div>
            </div>

            <!-- PROGRESS SECTION -->
            <div class="progress-section">
                <div class="progress-header">
                    <h3 class="progress-title">Seu Progresso no Curso</h3>
                    <span class="progress-value"><?php echo $progresso; ?>%</span>
                </div>
                <div class="progress-bar">
                    <div class="progress-fill" style="width: <?php echo $progresso; ?>%"></div>
                </div>
            </div>

            <!-- LESSONS LIST -->
            <div class="lessons-section">
                <div class="lessons-header">
                    <h3>Aulas do Curso</h3>
                </div>
                <ul class="lessons-list">
                    <?php if (empty($aulas)): ?>
                        <li style="padding: 40px; text-align: center; color: #86868B;">
                            <span class="material-icons-outlined" style="font-size: 48px; opacity: 0.3;">video_library</span>
                            <p style="margin-top: 16px;">Nenhuma aula disponível ainda.</p>
                        </li>
                    <?php else: ?>
                        <?php foreach ($aulas as $index => $aula): ?>
                            <li class="lesson-item <?php echo $aula['visualizado'] ? 'completed' : ''; ?>">
                                <a href="aula.php?id=<?php echo $aula['id']; ?>" class="lesson-link">
                                    <div class="lesson-number">
                                        <?php if ($aula['visualizado']): ?>
                                            <span class="material-icons-outlined" style="font-size: 20px;">check</span>
                                        <?php else: ?>
                                            <?php echo $index + 1; ?>
                                        <?php endif; ?>
                                    </div>
                                    <div class="lesson-content">
                                        <h4 class="lesson-title"><?php echo htmlspecialchars($aula['titulo']); ?></h4>
                                        <div class="lesson-meta">
                                            <?php if ($aula['duracao_minutos']): ?>
                                                <div class="lesson-meta-item">
                                                    <span class="material-icons-outlined" style="font-size: 16px;">schedule</span>
                                                    <span><?php echo $aula['duracao_minutos']; ?> min</span>
                                                </div>
                                            <?php endif; ?>
                                            <?php if ($aula['total_conteudos'] > 0): ?>
                                                <div class="lesson-meta-item">
                                                    <span class="material-icons-outlined" style="font-size: 16px;">play_circle</span>
                                                    <span><?php echo $aula['total_conteudos']; ?> conteúdo(s)</span>
                                                </div>
                                            <?php endif; ?>
                                            <?php if ($aula['total_exercicios'] > 0): ?>
                                                <div class="lesson-meta-item">
                                                    <span class="material-icons-outlined" style="font-size: 16px;">assignment</span>
                                                    <span><?php echo $aula['total_exercicios']; ?> exercício(s)</span>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    <div class="lesson-status <?php echo $aula['visualizado'] ? 'completed' : ($aula['tempo_gasto_minutos'] > 0 ? 'in-progress' : 'not-started'); ?>">
                                        <?php if ($aula['visualizado']): ?>
                                            <span class="material-icons-outlined" style="font-size: 16px;">check_circle</span>
                                            <span>Concluída</span>
                                        <?php elseif ($aula['tempo_gasto_minutos'] > 0): ?>
                                            <span class="material-icons-outlined" style="font-size: 16px;">play_circle</span>
                                            <span>Em Progresso</span>
                                        <?php else: ?>
                                            <span class="material-icons-outlined" style="font-size: 16px;">radio_button_unchecked</span>
                                            <span>Não Iniciada</span>
                                        <?php endif; ?>
                                    </div>
                                </a>
                            </li>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </main>

</body>
</html>

