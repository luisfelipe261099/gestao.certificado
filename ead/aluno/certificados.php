<?php
/**
 * Certificados - Portal do Aluno
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
// BUSCAR CERTIFICADOS DO ALUNO
// ============================================================================

try {
    $stmt = $pdo->prepare('
        SELECT
            c.id,
            c.numero_certificado,
            c.arquivo_url,
            c.data_geracao,
            c.data_validade,
            c.status,
            cu.nome as curso_nome,
            cu.carga_horaria,
            p.nome_empresa as parceiro_nome
        FROM certificados c
        INNER JOIN cursos cu ON c.curso_id = cu.id
        INNER JOIN parceiros p ON cu.parceiro_id = p.id
        WHERE c.aluno_id = ?
        ORDER BY c.data_geracao DESC
    ');
    $stmt->execute([$aluno_id]);
    $certificados = $stmt->fetchAll();

} catch (Exception $e) {
    $certificados = [];
    $erro_certificados = $e->getMessage();
}

// ============================================================================
// ESTATÍSTICAS
// ============================================================================

$total_certificados = count($certificados);
$certificados_ativos = 0;
$certificados_baixados = 0;

foreach ($certificados as $cert) {
    if ($cert['status'] === 'gerado' || $cert['status'] === 'enviado') {
        $certificados_ativos++;
    }
    if ($cert['status'] === 'baixado') {
        $certificados_baixados++;
    }
}

?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Certificados - Portal do Aluno</title>
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
            grid-template-columns: repeat(3, 1fr);
            gap: 16px;
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
            width: 56px;
            height: 56px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 16px;
            font-size: 28px;
        }

        .stat-card.purple .icon {
            background: linear-gradient(135deg, rgba(110, 65, 193, 0.1) 0%, rgba(139, 95, 214, 0.1) 100%);
            color: #6E41C1;
        }

        .stat-card.green .icon {
            background: linear-gradient(135deg, rgba(52, 199, 89, 0.1) 0%, rgba(52, 199, 89, 0.2) 100%);
            color: #34C759;
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

        /* CERTIFICATES GRID */
        .certificates-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(380px, 1fr));
            gap: 24px;
        }

        .certificate-card {
            background: white;
            border-radius: 20px;
            padding: 32px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.06);
            transition: all 0.3s;
            border: 2px solid transparent;
            position: relative;
            overflow: hidden;
        }

        .certificate-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 6px;
            background: linear-gradient(90deg, #6E41C1 0%, #8B5FD6 100%);
        }

        .certificate-card:hover {
            transform: translateY(-6px);
            box-shadow: 0 12px 24px rgba(110, 65, 193, 0.15);
            border-color: #6E41C1;
        }

        .certificate-icon {
            width: 80px;
            height: 80px;
            background: linear-gradient(135deg, #6E41C1 0%, #8B5FD6 100%);
            border-radius: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
            box-shadow: 0 8px 16px rgba(110, 65, 193, 0.3);
        }

        .certificate-icon .material-icons-outlined {
            font-size: 40px;
            color: white;
        }

        .certificate-title {
            font-size: 20px;
            font-weight: 700;
            color: #1D1D1F;
            text-align: center;
            margin-bottom: 8px;
        }


        .certificate-subtitle {
            font-size: 14px;
            color: #86868B;
            text-align: center;
            margin-bottom: 24px;
        }

        .certificate-info {
            background: #F5F5F7;
            border-radius: 12px;
            padding: 16px;
            margin-bottom: 16px;
        }

        .info-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 8px 0;
        }

        .info-row:not(:last-child) {
            border-bottom: 1px solid #E5E5E7;
        }

        .info-label {
            font-size: 13px;
            color: #86868B;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .info-value {
            font-size: 14px;
            font-weight: 600;
            color: #1D1D1F;
        }

        .certificate-number {
            background: linear-gradient(135deg, rgba(110, 65, 193, 0.1) 0%, rgba(139, 95, 214, 0.1) 100%);
            border-radius: 8px;
            padding: 12px;
            text-align: center;
            margin-bottom: 20px;
        }

        .certificate-number-label {
            font-size: 11px;
            color: #86868B;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 4px;
        }

        .certificate-number-value {
            font-size: 16px;
            font-weight: 700;
            color: #6E41C1;
            font-family: 'Courier New', monospace;
        }

        .status-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }

        .status-badge.gerado {
            background: rgba(0, 122, 255, 0.1);
            color: #007AFF;
        }

        .status-badge.enviado {
            background: rgba(255, 149, 0, 0.1);
            color: #FF9500;
        }

        .status-badge.baixado {
            background: rgba(52, 199, 89, 0.1);
            color: #34C759;
        }

        .status-badge.cancelado {
            background: rgba(255, 59, 48, 0.1);
            color: #FF3B30;
        }

        .btn-download {
            width: 100%;
            padding: 14px 24px;
            background: linear-gradient(135deg, #6E41C1 0%, #8B5FD6 100%);
            color: white;
            border: none;
            border-radius: 12px;
            font-size: 15px;
            font-weight: 600;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            transition: all 0.3s;
            text-decoration: none;
        }

        .btn-download:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 16px rgba(110, 65, 193, 0.3);
        }

        .btn-download:active {
            transform: translateY(0);
        }

        .empty-state {
            text-align: center;
            padding: 80px 20px;
            background: white;
            border-radius: 20px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04);
        }

        .empty-state .material-icons-outlined {
            font-size: 80px;
            color: #E5E5E7;
            margin-bottom: 20px;
        }

        .empty-state h3 {
            font-size: 22px;
            font-weight: 700;
            color: #1D1D1F;
            margin-bottom: 12px;
        }

        .empty-state p {
            font-size: 15px;
            color: #86868B;
            margin-bottom: 24px;
        }

        .btn-primary {
            padding: 12px 24px;
            background: linear-gradient(135deg, #6E41C1 0%, #8B5FD6 100%);
            color: white;
            border: none;
            border-radius: 10px;
            font-size: 15px;
            font-weight: 600;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            text-decoration: none;
            transition: all 0.3s;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 16px rgba(110, 65, 193, 0.3);
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

            .certificates-grid {
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
                <a href="certificados.php" class="nav-item active">
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
                    <span>Certificados</span>
                </div>
                <h1>Meus Certificados</h1>
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
                        <span class="material-icons-outlined">workspace_premium</span>
                    </div>
                    <div class="stat-value"><?php echo $total_certificados; ?></div>
                    <div class="stat-label">Total de Certificados</div>
                </div>

                <div class="stat-card green">
                    <div class="icon">
                        <span class="material-icons-outlined">verified</span>
                    </div>
                    <div class="stat-value"><?php echo $certificados_ativos; ?></div>
                    <div class="stat-label">Certificados Ativos</div>
                </div>

                <div class="stat-card blue">
                    <div class="icon">
                        <span class="material-icons-outlined">download</span>
                    </div>
                    <div class="stat-value"><?php echo $certificados_baixados; ?></div>
                    <div class="stat-label">Downloads Realizados</div>
                </div>
            </div>


            <!-- CERTIFICATES LIST -->
            <?php if (empty($certificados)): ?>
                <div class="empty-state">
                    <span class="material-icons-outlined">workspace_premium</span>
                    <h3>Nenhum certificado disponível</h3>
                    <p>Você ainda não possui certificados. Complete seus cursos para receber certificados de conclusão.</p>
                    <a href="meus-cursos.php" class="btn-primary">
                        <span class="material-icons-outlined" style="font-size: 20px;">school</span>
                        Ver Meus Cursos
                    </a>
                </div>
            <?php else: ?>
                <div class="certificates-grid">
                    <?php foreach ($certificados as $cert): ?>
                        <?php
                        $status_labels = [
                            'gerado' => 'Gerado',
                            'enviado' => 'Enviado',
                            'baixado' => 'Baixado',
                            'cancelado' => 'Cancelado'
                        ];

                        $status_icons = [
                            'gerado' => 'check_circle',
                            'enviado' => 'send',
                            'baixado' => 'download_done',
                            'cancelado' => 'cancel'
                        ];

                        $data_geracao = new DateTime($cert['data_geracao']);
                        $data_validade = $cert['data_validade'] ? new DateTime($cert['data_validade']) : null;
                        ?>
                        <div class="certificate-card">
                            <div class="certificate-icon">
                                <span class="material-icons-outlined">workspace_premium</span>
                            </div>

                            <h3 class="certificate-title"><?php echo htmlspecialchars($cert['curso_nome']); ?></h3>
                            <p class="certificate-subtitle"><?php echo htmlspecialchars($cert['parceiro_nome']); ?></p>

                            <div class="certificate-number">
                                <div class="certificate-number-label">Número do Certificado</div>
                                <div class="certificate-number-value"><?php echo htmlspecialchars($cert['numero_certificado']); ?></div>
                            </div>

                            <div class="certificate-info">
                                <div class="info-row">
                                    <div class="info-label">
                                        <span class="material-icons-outlined" style="font-size: 16px;">event</span>
                                        Data de Emissão
                                    </div>
                                    <div class="info-value"><?php echo $data_geracao->format('d/m/Y'); ?></div>
                                </div>

                                <div class="info-row">
                                    <div class="info-label">
                                        <span class="material-icons-outlined" style="font-size: 16px;">schedule</span>
                                        Carga Horária
                                    </div>
                                    <div class="info-value"><?php echo $cert['carga_horaria']; ?>h</div>
                                </div>

                                <div class="info-row">
                                    <div class="info-label">
                                        <span class="material-icons-outlined" style="font-size: 16px;">calendar_month</span>
                                        Validade
                                    </div>
                                    <div class="info-value">
                                        <?php echo $data_validade ? $data_validade->format('d/m/Y') : 'Permanente'; ?>
                                    </div>
                                </div>

                                <div class="info-row">
                                    <div class="info-label">
                                        <span class="material-icons-outlined" style="font-size: 16px;">info</span>
                                        Status
                                    </div>
                                    <div class="info-value">
                                        <span class="status-badge <?php echo $cert['status']; ?>">
                                            <span class="material-icons-outlined" style="font-size: 14px;">
                                                <?php echo $status_icons[$cert['status']] ?? 'info'; ?>
                                            </span>
                                            <?php echo $status_labels[$cert['status']] ?? $cert['status']; ?>
                                        </span>
                                    </div>
                                </div>
                            </div>

                            <?php if ($cert['arquivo_url']): ?>
                                <a href="<?php echo htmlspecialchars($cert['arquivo_url']); ?>" class="btn-download" download target="_blank">
                                    <span class="material-icons-outlined" style="font-size: 20px;">download</span>
                                    Baixar Certificado
                                </a>
                            <?php else: ?>
                                <button class="btn-download" style="opacity: 0.5; cursor: not-allowed;" disabled>
                                    <span class="material-icons-outlined" style="font-size: 20px;">block</span>
                                    PDF não disponível
                                </button>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </main>

</body>
</html>