<?php
/**
 * Calendário - Portal do Aluno
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
$aluno_model = new Aluno($pdo);
$aluno = $aluno_model->obter_por_id($aluno_id);

// Obter mês e ano atual ou da URL
$mes = isset($_GET['mes']) ? (int)$_GET['mes'] : (int)date('m');
$ano = isset($_GET['ano']) ? (int)$_GET['ano'] : (int)date('Y');

// Validar mês e ano
if ($mes < 1 || $mes > 12) $mes = (int)date('m');
if ($ano < 2020 || $ano > 2030) $ano = (int)date('Y');

// Calcular primeiro e último dia do mês
$primeiro_dia = mktime(0, 0, 0, $mes, 1, $ano);
$ultimo_dia = mktime(0, 0, 0, $mes + 1, 0, $ano);
$dias_no_mes = date('t', $primeiro_dia);
$dia_semana_inicio = date('w', $primeiro_dia); // 0 = domingo

// Mês anterior e próximo
$mes_anterior = $mes - 1;
$ano_anterior = $ano;
if ($mes_anterior < 1) {
    $mes_anterior = 12;
    $ano_anterior--;
}

$mes_proximo = $mes + 1;
$ano_proximo = $ano;
if ($mes_proximo > 12) {
    $mes_proximo = 1;
    $ano_proximo++;
}

// ============================================================================
// BUSCAR EVENTOS DO MÊS
// ============================================================================

try {
    // Buscar eventos gerais (sem curso) e eventos dos cursos do aluno
    $stmt = $pdo->prepare('
        SELECT
            e.id,
            e.titulo,
            e.descricao,
            e.tipo,
            e.data_inicio,
            e.data_fim,
            e.local,
            e.link,
            e.cor,
            c.nome as curso_nome,
            a.titulo as aula_titulo
        FROM ead_eventos e
        LEFT JOIN cursos c ON e.curso_id = c.id
        LEFT JOIN ead_aulas a ON e.aula_id = a.id
        WHERE e.ativo = 1
        AND YEAR(e.data_inicio) = ?
        AND MONTH(e.data_inicio) = ?
        AND (
            e.curso_id IS NULL
            OR e.curso_id IN (
                SELECT curso_id FROM inscricoes_alunos
                WHERE aluno_id = ? AND status IN ("inscrito", "em_progresso")
            )
        )
        ORDER BY e.data_inicio ASC
    ');
    $stmt->execute([$ano, $mes, $aluno_id]);
    $eventos = $stmt->fetchAll();

    // Organizar eventos por dia
    $eventos_por_dia = [];
    foreach ($eventos as $evento) {
        $dia = (int)date('d', strtotime($evento['data_inicio']));
        if (!isset($eventos_por_dia[$dia])) {
            $eventos_por_dia[$dia] = [];
        }
        $eventos_por_dia[$dia][] = $evento;
    }

} catch (Exception $e) {
    $eventos = [];
    $eventos_por_dia = [];
    $erro_eventos = $e->getMessage();
}

// ============================================================================
// BUSCAR PRÓXIMOS EVENTOS (próximos 7 dias)
// ============================================================================

try {
    $stmt = $pdo->prepare('
        SELECT
            e.id,
            e.titulo,
            e.descricao,
            e.tipo,
            e.data_inicio,
            e.data_fim,
            e.local,
            e.link,
            e.cor,
            c.nome as curso_nome
        FROM ead_eventos e
        LEFT JOIN cursos c ON e.curso_id = c.id
        WHERE e.ativo = 1
        AND e.data_inicio >= NOW()
        AND e.data_inicio <= DATE_ADD(NOW(), INTERVAL 7 DAY)
        AND (
            e.curso_id IS NULL
            OR e.curso_id IN (
                SELECT curso_id FROM inscricoes_alunos
                WHERE aluno_id = ? AND status IN ("inscrito", "em_progresso")
            )
        )
        ORDER BY e.data_inicio ASC
        LIMIT 5
    ');
    $stmt->execute([$aluno_id]);
    $proximos_eventos = $stmt->fetchAll();

} catch (Exception $e) {
    $proximos_eventos = [];
}

// Nomes dos meses
$meses = [
    1 => 'Janeiro', 2 => 'Fevereiro', 3 => 'Março', 4 => 'Abril',
    5 => 'Maio', 6 => 'Junho', 7 => 'Julho', 8 => 'Agosto',
    9 => 'Setembro', 10 => 'Outubro', 11 => 'Novembro', 12 => 'Dezembro'
];

// Dias da semana
$dias_semana = ['Dom', 'Seg', 'Ter', 'Qua', 'Qui', 'Sex', 'Sáb'];

?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Calendário - Portal do Aluno</title>
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

        .breadcrumb a:hover {
            text-decoration: underline;
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

        /* ============================================
           CALENDAR LAYOUT
        ============================================ */
        .calendar-layout {
            display: grid;
            grid-template-columns: 1fr 350px;
            gap: 24px;
        }

        .calendar-main {
            background: white;
            border-radius: 16px;
            padding: 24px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04);
        }

        .calendar-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 24px;
        }

        .calendar-title {
            font-size: 24px;
            font-weight: 700;
            color: #1D1D1F;
        }

        .calendar-nav {
            display: flex;
            gap: 8px;
        }

        .calendar-nav-btn {
            width: 36px;
            height: 36px;
            border-radius: 8px;
            border: 1px solid #E5E5E7;
            background: white;
            color: #1D1D1F;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.2s;
        }

        .calendar-nav-btn:hover {
            background: #F5F5F7;
            border-color: #6E41C1;
            color: #6E41C1;
        }

        .calendar-grid {
            display: grid;
            grid-template-columns: repeat(7, 1fr);
            gap: 1px;
            background: #E5E5E7;
            border: 1px solid #E5E5E7;
            border-radius: 12px;
            overflow: hidden;
        }

        .calendar-day-header {
            background: #F5F5F7;
            padding: 12px;
            text-align: center;
            font-size: 12px;
            font-weight: 600;
            color: #86868B;
            text-transform: uppercase;
        }

        .calendar-day {
            background: white;
            min-height: 100px;
            padding: 8px;
            position: relative;
            cursor: pointer;
            transition: all 0.2s;
        }

        .calendar-day:hover {
            background: #F5F5F7;
        }

        .calendar-day.other-month {
            background: #FAFAFA;
            color: #C7C7CC;
        }

        .calendar-day.today {
            background: linear-gradient(135deg, rgba(110, 65, 193, 0.1) 0%, rgba(139, 95, 214, 0.1) 100%);
        }

        .calendar-day-number {
            font-size: 14px;
            font-weight: 600;
            color: #1D1D1F;
            margin-bottom: 4px;
        }

        .calendar-day.today .calendar-day-number {
            display: inline-block;
            width: 28px;
            height: 28px;
            line-height: 28px;
            text-align: center;
            background: linear-gradient(135deg, #6E41C1 0%, #8B5FD6 100%);
            color: white;
            border-radius: 50%;
        }

        .calendar-event-dot {
            width: 6px;
            height: 6px;
            border-radius: 50%;
            display: inline-block;
            margin: 2px;
        }

        /* ============================================
           SIDEBAR DIREITA - PRÓXIMOS EVENTOS
        ============================================ */
        .calendar-sidebar {
            display: flex;
            flex-direction: column;
            gap: 24px;
        }

        .upcoming-events {
            background: white;
            border-radius: 16px;
            padding: 24px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04);
        }

        .upcoming-events-title {
            font-size: 18px;
            font-weight: 700;
            color: #1D1D1F;
            margin-bottom: 16px;
        }

        .event-item {
            padding: 16px;
            border-radius: 12px;
            background: #F5F5F7;
            margin-bottom: 12px;
            border-left: 4px solid #6E41C1;
            transition: all 0.2s;
        }

        .event-item:hover {
            background: #E5E5E7;
            transform: translateX(4px);
        }

        .event-item.tipo-aula_ao_vivo {
            border-left-color: #34C759;
        }

        .event-item.tipo-prazo {
            border-left-color: #FF9500;
        }

        .event-item.tipo-aviso {
            border-left-color: #007AFF;
        }

        .event-date {
            font-size: 12px;
            color: #86868B;
            margin-bottom: 4px;
            display: flex;
            align-items: center;
            gap: 4px;
        }

        .event-title {
            font-size: 14px;
            font-weight: 600;
            color: #1D1D1F;
            margin-bottom: 4px;
        }

        .event-course {
            font-size: 12px;
            color: #6E41C1;
        }

        .event-type-badge {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 6px;
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
            margin-top: 8px;
        }

        .event-type-badge.aula_ao_vivo {
            background: rgba(52, 199, 89, 0.1);
            color: #34C759;
        }

        .event-type-badge.prazo {
            background: rgba(255, 149, 0, 0.1);
            color: #FF9500;
        }

        .event-type-badge.aviso {
            background: rgba(0, 122, 255, 0.1);
            color: #007AFF;
        }

        .event-type-badge.evento {
            background: rgba(110, 65, 193, 0.1);
            color: #6E41C1;
        }

        .empty-state {
            text-align: center;
            padding: 40px 20px;
            color: #86868B;
        }

        .empty-state .material-icons-outlined {
            font-size: 48px;
            opacity: 0.3;
            margin-bottom: 16px;
        }

        /* ============================================
           RESPONSIVE
        ============================================ */
        @media (max-width: 1200px) {
            .calendar-layout {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 768px) {
            .sidebar {
                transform: translateX(-100%);
            }

            .main-content {
                margin-left: 0;
            }

            .content-wrapper {
                padding: 20px;
            }

            .calendar-day {
                min-height: 80px;
                padding: 4px;
            }

            .calendar-day-number {
                font-size: 12px;
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
                <a href="calendario.php" class="nav-item active">
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
                    <span>Calendário</span>
                </div>
                <h1>Calendário</h1>
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
            <div class="calendar-layout">
                <!-- CALENDÁRIO PRINCIPAL -->
                <div class="calendar-main">
                    <div class="calendar-header">
                        <h2 class="calendar-title"><?php echo $meses[$mes] . ' ' . $ano; ?></h2>
                        <div class="calendar-nav">
                            <a href="?mes=<?php echo $mes_anterior; ?>&ano=<?php echo $ano_anterior; ?>" class="calendar-nav-btn">
                                <span class="material-icons-outlined">chevron_left</span>
                            </a>
                            <a href="?mes=<?php echo date('m'); ?>&ano=<?php echo date('Y'); ?>" class="calendar-nav-btn" title="Hoje">
                                <span class="material-icons-outlined">today</span>
                            </a>
                            <a href="?mes=<?php echo $mes_proximo; ?>&ano=<?php echo $ano_proximo; ?>" class="calendar-nav-btn">
                                <span class="material-icons-outlined">chevron_right</span>
                            </a>
                        </div>
                    </div>

                    <div class="calendar-grid">
                        <!-- Cabeçalhos dos dias da semana -->
                        <?php foreach ($dias_semana as $dia): ?>
                            <div class="calendar-day-header"><?php echo $dia; ?></div>
                        <?php endforeach; ?>

                        <!-- Dias vazios antes do primeiro dia do mês -->
                        <?php for ($i = 0; $i < $dia_semana_inicio; $i++): ?>
                            <div class="calendar-day other-month"></div>
                        <?php endfor; ?>

                        <!-- Dias do mês -->
                        <?php for ($dia = 1; $dia <= $dias_no_mes; $dia++): ?>
                            <?php
                            $data_atual = mktime(0, 0, 0, $mes, $dia, $ano);
                            $is_today = (date('Y-m-d', $data_atual) === date('Y-m-d'));
                            $has_events = isset($eventos_por_dia[$dia]);
                            ?>
                            <div class="calendar-day <?php echo $is_today ? 'today' : ''; ?>">
                                <div class="calendar-day-number"><?php echo $dia; ?></div>
                                <?php if ($has_events): ?>
                                    <?php foreach ($eventos_por_dia[$dia] as $evento): ?>
                                        <div class="calendar-event-dot" style="background: <?php echo htmlspecialchars($evento['cor']); ?>" title="<?php echo htmlspecialchars($evento['titulo']); ?>"></div>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>
                        <?php endfor; ?>
                    </div>
                </div>

                <!-- SIDEBAR DIREITA - PRÓXIMOS EVENTOS -->
                <div class="calendar-sidebar">
                    <div class="upcoming-events">
                        <h3 class="upcoming-events-title">Próximos Eventos</h3>

                        <?php if (empty($proximos_eventos)): ?>
                            <div class="empty-state">
                                <span class="material-icons-outlined">event_available</span>
                                <p>Nenhum evento próximo</p>
                            </div>
                        <?php else: ?>
                            <?php foreach ($proximos_eventos as $evento): ?>
                                <div class="event-item tipo-<?php echo $evento['tipo']; ?>">
                                    <div class="event-date">
                                        <span class="material-icons-outlined" style="font-size: 14px;">schedule</span>
                                        <?php
                                        $data = new DateTime($evento['data_inicio']);
                                        echo $data->format('d/m/Y H:i');
                                        ?>
                                    </div>
                                    <div class="event-title"><?php echo htmlspecialchars($evento['titulo']); ?></div>
                                    <?php if ($evento['curso_nome']): ?>
                                        <div class="event-course"><?php echo htmlspecialchars($evento['curso_nome']); ?></div>
                                    <?php endif; ?>
                                    <span class="event-type-badge <?php echo $evento['tipo']; ?>">
                                        <?php
                                        $tipos = [
                                            'aula_ao_vivo' => 'Aula ao Vivo',
                                            'prazo' => 'Prazo',
                                            'aviso' => 'Aviso',
                                            'evento' => 'Evento'
                                        ];
                                        echo $tipos[$evento['tipo']] ?? $evento['tipo'];
                                        ?>
                                    </span>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </main>

</body>
</html>

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

