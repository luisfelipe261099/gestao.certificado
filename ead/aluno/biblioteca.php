<?php
/**
 * Biblioteca - Portal do Aluno
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

// Filtros
$tipo_filtro = isset($_GET['tipo']) ? $_GET['tipo'] : 'todos';
$curso_filtro = isset($_GET['curso']) ? (int)$_GET['curso'] : 0;

// ============================================================================
// BUSCAR MATERIAIS DA BIBLIOTECA
// ============================================================================

try {
    $sql = '
        SELECT
            ca.id,
            ca.tipo,
            ca.titulo,
            ca.conteudo,
            ca.url,
            ca.ordem,
            a.titulo as aula_titulo,
            a.id as aula_id,
            c.nome as curso_nome,
            c.id as curso_id,
            ia.id as inscricao_id
        FROM conteudo_aulas ca
        INNER JOIN ead_aulas a ON ca.aula_id = a.id
        INNER JOIN cursos c ON a.curso_id = c.id
        INNER JOIN inscricoes_alunos ia ON c.id = ia.curso_id
        WHERE ia.aluno_id = ?
        AND ia.status IN ("inscrito", "em_progresso", "concluido")
        AND a.ativa = 1
    ';

    $params = [$aluno_id];

    // Aplicar filtro de tipo
    if ($tipo_filtro !== 'todos') {
        $sql .= ' AND ca.tipo = ?';
        $params[] = $tipo_filtro;
    }

    // Aplicar filtro de curso
    if ($curso_filtro > 0) {
        $sql .= ' AND c.id = ?';
        $params[] = $curso_filtro;
    }

    $sql .= ' ORDER BY c.nome, a.ordem, ca.ordem';

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $materiais = $stmt->fetchAll();

} catch (Exception $e) {
    $materiais = [];
    $erro_materiais = $e->getMessage();
}

// ============================================================================
// BUSCAR CURSOS DO ALUNO (para filtro)
// ============================================================================

try {
    $stmt = $pdo->prepare('
        SELECT DISTINCT
            c.id,
            c.nome
        FROM cursos c
        INNER JOIN inscricoes_alunos ia ON c.id = ia.curso_id
        WHERE ia.aluno_id = ?
        AND ia.status IN ("inscrito", "em_progresso", "concluido")
        ORDER BY c.nome
    ');
    $stmt->execute([$aluno_id]);
    $cursos = $stmt->fetchAll();

} catch (Exception $e) {
    $cursos = [];
}

// ============================================================================
// ESTATÍSTICAS
// ============================================================================

try {
    $stmt = $pdo->prepare('
        SELECT
            COUNT(*) as total,
            SUM(CASE WHEN ca.tipo = "video" THEN 1 ELSE 0 END) as videos,
            SUM(CASE WHEN ca.tipo = "pdf" THEN 1 ELSE 0 END) as pdfs,
            SUM(CASE WHEN ca.tipo = "texto" THEN 1 ELSE 0 END) as textos,
            SUM(CASE WHEN ca.tipo = "link" THEN 1 ELSE 0 END) as links
        FROM conteudo_aulas ca
        INNER JOIN ead_aulas a ON ca.aula_id = a.id
        INNER JOIN cursos c ON a.curso_id = c.id
        INNER JOIN inscricoes_alunos ia ON c.id = ia.curso_id
        WHERE ia.aluno_id = ?
        AND ia.status IN ("inscrito", "em_progresso", "concluido")
        AND a.ativa = 1
    ');
    $stmt->execute([$aluno_id]);
    $stats = $stmt->fetch();

    $total_materiais = $stats['total'] ?? 0;
    $total_videos = $stats['videos'] ?? 0;
    $total_pdfs = $stats['pdfs'] ?? 0;
    $total_textos = $stats['textos'] ?? 0;
    $total_links = $stats['links'] ?? 0;

} catch (Exception $e) {
    $total_materiais = 0;
    $total_videos = 0;
    $total_pdfs = 0;
    $total_textos = 0;
    $total_links = 0;
}

// Organizar materiais por curso
$materiais_por_curso = [];
foreach ($materiais as $material) {
    $curso_id = $material['curso_id'];
    if (!isset($materiais_por_curso[$curso_id])) {
        $materiais_por_curso[$curso_id] = [
            'curso_nome' => $material['curso_nome'],
            'materiais' => []
        ];
    }
    $materiais_por_curso[$curso_id]['materiais'][] = $material;
}

?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Biblioteca - Portal do Aluno</title>
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

        /* SIDEBAR */
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
            grid-template-columns: repeat(5, 1fr);
            gap: 16px;
            margin-bottom: 32px;
        }

        .stat-card {
            background: white;
            border-radius: 16px;
            padding: 20px;
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
            margin-bottom: 12px;
            font-size: 24px;
        }

        .stat-card.purple .icon {
            background: linear-gradient(135deg, rgba(110, 65, 193, 0.1) 0%, rgba(139, 95, 214, 0.1) 100%);
            color: #6E41C1;
        }

        .stat-card.red .icon {
            background: linear-gradient(135deg, rgba(255, 59, 48, 0.1) 0%, rgba(255, 59, 48, 0.2) 100%);
            color: #FF3B30;
        }

        .stat-card.blue .icon {
            background: linear-gradient(135deg, rgba(0, 122, 255, 0.1) 0%, rgba(0, 122, 255, 0.2) 100%);
            color: #007AFF;
        }

        .stat-card.green .icon {
            background: linear-gradient(135deg, rgba(52, 199, 89, 0.1) 0%, rgba(52, 199, 89, 0.2) 100%);
            color: #34C759;
        }

        .stat-card.orange .icon {
            background: linear-gradient(135deg, rgba(255, 149, 0, 0.1) 0%, rgba(255, 149, 0, 0.2) 100%);
            color: #FF9500;
        }

        .stat-value {
            font-size: 28px;
            font-weight: 700;
            color: #1D1D1F;
            margin-bottom: 4px;
        }

        .stat-label {
            font-size: 13px;
            color: #86868B;
        }

        /* FILTERS */
        .filters {
            background: white;
            border-radius: 16px;
            padding: 20px 24px;
            margin-bottom: 24px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04);
            display: flex;
            gap: 16px;
            align-items: center;
        }

        .filter-group {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .filter-group label {
            font-size: 14px;
            font-weight: 600;
            color: #1D1D1F;
        }

        .filter-select {
            padding: 8px 16px;
            border: 1px solid #E5E5E7;
            border-radius: 8px;
            font-size: 14px;
            color: #1D1D1F;
            background: white;
            cursor: pointer;
            transition: all 0.2s;
        }

        .filter-select:hover {
            border-color: #6E41C1;
        }

        .filter-select:focus {
            outline: none;
            border-color: #6E41C1;
            box-shadow: 0 0 0 3px rgba(110, 65, 193, 0.1);
        }

        /* COURSE SECTIONS */
        .course-section {
            margin-bottom: 32px;
        }

        .course-section-header {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 16px;
            padding-bottom: 12px;
            border-bottom: 2px solid #E5E5E7;
        }

        .course-section-title {
            font-size: 20px;
            font-weight: 700;
            color: #1D1D1F;
        }

        .course-section-count {
            padding: 4px 12px;
            background: rgba(110, 65, 193, 0.1);
            color: #6E41C1;
            border-radius: 12px;
            font-size: 13px;
            font-weight: 600;
        }

        /* MATERIAL CARDS */
        .materials-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 16px;
        }

        .material-card {
            background: white;
            border-radius: 16px;
            padding: 20px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04);
            transition: all 0.3s;
            cursor: pointer;
            border: 2px solid transparent;
        }

        .material-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 8px 16px rgba(0, 0, 0, 0.08);
            border-color: #6E41C1;
        }

        .material-icon {
            width: 56px;
            height: 56px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 16px;
            font-size: 28px;
        }

        .material-icon.video {
            background: linear-gradient(135deg, rgba(255, 59, 48, 0.1) 0%, rgba(255, 59, 48, 0.2) 100%);
            color: #FF3B30;
        }

        .material-icon.pdf {
            background: linear-gradient(135deg, rgba(0, 122, 255, 0.1) 0%, rgba(0, 122, 255, 0.2) 100%);
            color: #007AFF;
        }

        .material-icon.texto {
            background: linear-gradient(135deg, rgba(52, 199, 89, 0.1) 0%, rgba(52, 199, 89, 0.2) 100%);
            color: #34C759;
        }

        .material-icon.link {
            background: linear-gradient(135deg, rgba(255, 149, 0, 0.1) 0%, rgba(255, 149, 0, 0.2) 100%);
            color: #FF9500;
        }

        .material-title {
            font-size: 16px;
            font-weight: 600;
            color: #1D1D1F;
            margin-bottom: 8px;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        .material-meta {
            font-size: 13px;
            color: #86868B;
            display: flex;
            align-items: center;
            gap: 4px;
        }

        .material-type-badge {
            display: inline-block;
            padding: 4px 10px;
            border-radius: 6px;
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
            margin-top: 12px;
        }

        .material-type-badge.video {
            background: rgba(255, 59, 48, 0.1);
            color: #FF3B30;
        }

        .material-type-badge.pdf {
            background: rgba(0, 122, 255, 0.1);
            color: #007AFF;
        }

        .material-type-badge.texto {
            background: rgba(52, 199, 89, 0.1);
            color: #34C759;
        }

        .material-type-badge.link {
            background: rgba(255, 149, 0, 0.1);
            color: #FF9500;
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
                grid-template-columns: repeat(3, 1fr);
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
                grid-template-columns: repeat(2, 1fr);
            }

            .materials-grid {
                grid-template-columns: 1fr;
            }

            .content-wrapper {
                padding: 20px;
            }

            .filters {
                flex-direction: column;
                align-items: stretch;
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
                <a href="atividades.php" class="nav-item">
                    <span class="material-icons-outlined">assignment</span>
                    <span>Atividades</span>
                </a>
            </div>

            <div class="nav-section">
                <div class="nav-section-title">Recursos</div>
                <a href="biblioteca.php" class="nav-item active">
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
                    <span>Biblioteca</span>
                </div>
                <h1>Biblioteca de Materiais</h1>
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
                        <span class="material-icons-outlined">folder</span>
                    </div>
                    <div class="stat-value"><?php echo $total_materiais; ?></div>
                    <div class="stat-label">Total de Materiais</div>
                </div>

                <div class="stat-card red">
                    <div class="icon">
                        <span class="material-icons-outlined">play_circle</span>
                    </div>
                    <div class="stat-value"><?php echo $total_videos; ?></div>
                    <div class="stat-label">Vídeos</div>
                </div>

                <div class="stat-card blue">
                    <div class="icon">
                        <span class="material-icons-outlined">picture_as_pdf</span>
                    </div>
                    <div class="stat-value"><?php echo $total_pdfs; ?></div>
                    <div class="stat-label">PDFs</div>
                </div>

                <div class="stat-card green">
                    <div class="icon">
                        <span class="material-icons-outlined">article</span>
                    </div>
                    <div class="stat-value"><?php echo $total_textos; ?></div>
                    <div class="stat-label">Textos</div>
                </div>

                <div class="stat-card orange">
                    <div class="icon">
                        <span class="material-icons-outlined">link</span>
                    </div>
                    <div class="stat-value"><?php echo $total_links; ?></div>
                    <div class="stat-label">Links</div>
                </div>
            </div>


            <!-- FILTERS -->
            <div class="filters">
                <div class="filter-group">
                    <label>
                        <span class="material-icons-outlined" style="font-size: 18px; vertical-align: middle;">filter_list</span>
                        Tipo:
                    </label>
                    <select class="filter-select" onchange="window.location.href='biblioteca.php?tipo=' + this.value + '&curso=<?php echo $curso_filtro; ?>'">
                        <option value="todos" <?php echo $tipo_filtro === 'todos' ? 'selected' : ''; ?>>Todos</option>
                        <option value="video" <?php echo $tipo_filtro === 'video' ? 'selected' : ''; ?>>Vídeos</option>
                        <option value="pdf" <?php echo $tipo_filtro === 'pdf' ? 'selected' : ''; ?>>PDFs</option>
                        <option value="texto" <?php echo $tipo_filtro === 'texto' ? 'selected' : ''; ?>>Textos</option>
                        <option value="link" <?php echo $tipo_filtro === 'link' ? 'selected' : ''; ?>>Links</option>
                    </select>
                </div>

                <div class="filter-group">
                    <label>
                        <span class="material-icons-outlined" style="font-size: 18px; vertical-align: middle;">school</span>
                        Curso:
                    </label>
                    <select class="filter-select" onchange="window.location.href='biblioteca.php?tipo=<?php echo $tipo_filtro; ?>&curso=' + this.value">
                        <option value="0" <?php echo $curso_filtro === 0 ? 'selected' : ''; ?>>Todos os Cursos</option>
                        <?php foreach ($cursos as $curso): ?>
                            <option value="<?php echo $curso['id']; ?>" <?php echo $curso_filtro === $curso['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($curso['nome']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <!-- MATERIALS BY COURSE -->
            <?php if (empty($materiais_por_curso)): ?>
                <div class="empty-state">
                    <span class="material-icons-outlined">folder_open</span>
                    <p style="font-size: 18px; font-weight: 600; margin-bottom: 8px;">Nenhum material encontrado</p>
                    <p>Não há materiais disponíveis com os filtros selecionados.</p>
                </div>
            <?php else: ?>
                <?php foreach ($materiais_por_curso as $curso_id => $curso_data): ?>
                    <div class="course-section">
                        <div class="course-section-header">
                            <span class="material-icons-outlined" style="color: #6E41C1; font-size: 24px;">school</span>
                            <h2 class="course-section-title"><?php echo htmlspecialchars($curso_data['curso_nome']); ?></h2>
                            <span class="course-section-count"><?php echo count($curso_data['materiais']); ?> materiais</span>
                        </div>

                        <div class="materials-grid">
                            <?php foreach ($curso_data['materiais'] as $material): ?>
                                <?php
                                $tipo_icons = [
                                    'video' => 'play_circle',
                                    'pdf' => 'picture_as_pdf',
                                    'texto' => 'article',
                                    'link' => 'link'
                                ];

                                $tipo_labels = [
                                    'video' => 'Vídeo',
                                    'pdf' => 'PDF',
                                    'texto' => 'Texto',
                                    'link' => 'Link Externo'
                                ];

                                $icon = $tipo_icons[$material['tipo']] ?? 'description';
                                $label = $tipo_labels[$material['tipo']] ?? $material['tipo'];

                                // Determinar ação do clique
                                $onclick = '';
                                if ($material['tipo'] === 'video' || $material['tipo'] === 'texto') {
                                    $onclick = "window.location.href='aula.php?id={$material['aula_id']}'";
                                } elseif ($material['tipo'] === 'pdf' || $material['tipo'] === 'link') {
                                    $url = htmlspecialchars($material['url'] ?? '');
                                    $onclick = "window.open('$url', '_blank')";
                                }
                                ?>
                                <div class="material-card" onclick="<?php echo $onclick; ?>">
                                    <div class="material-icon <?php echo $material['tipo']; ?>">
                                        <span class="material-icons-outlined"><?php echo $icon; ?></span>
                                    </div>
                                    <h3 class="material-title"><?php echo htmlspecialchars($material['titulo']); ?></h3>
                                    <div class="material-meta">
                                        <span class="material-icons-outlined" style="font-size: 16px;">play_lesson</span>
                                        <span><?php echo htmlspecialchars($material['aula_titulo']); ?></span>
                                    </div>
                                    <span class="material-type-badge <?php echo $material['tipo']; ?>">
                                        <?php echo $label; ?>
                                    </span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </main>

</body>
</html>