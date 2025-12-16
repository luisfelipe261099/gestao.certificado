<?php
/**
 * Página de Primeiro Pagamento (Checkout)
 * Padrão MVP - Camada de Apresentação
 */

require_once '../app/config/config.php';

// Verificar autenticação
if (!isAuthenticated()) {
    redirect(APP_URL . '/login.php');
}

$user = getCurrentUser();
$parceiro_id = $user['parceiro_id'] ?? $user['id'];

// Verificar se já tem assinatura ativa E se já tem pagamentos realizados
$conn = getDBConnection();

// Verificar pagamentos
$stmt = $conn->prepare("SELECT count(*) as total FROM faturas WHERE parceiro_id = ? AND status = 'pago'");
$stmt->bind_param("i", $parceiro_id);
$stmt->execute();
$paid_invoices = $stmt->get_result()->fetch_assoc()['total'];
$stmt->close();

// Verificar assinatura ativa
$stmt = $conn->prepare("SELECT status FROM assinaturas WHERE parceiro_id = ? AND status = 'ativa' LIMIT 1");
$stmt->bind_param("i", $parceiro_id);
$stmt->execute();
$has_active_sub = $stmt->get_result()->num_rows > 0;
$stmt->close();

if ($has_active_sub && $paid_invoices > 0) {
    // Já tem assinatura ativa e pagou, redirecionar para dashboard
    redirect(APP_URL . '/parceiro/dashboard.php');
}

// Buscar detalhes da assinatura pendente/criada no registro
$stmt = $conn->prepare("
    SELECT a.*, p.nome as plano_nome, p.valor, p.certificados_mensais, p.max_parcelas, p.quantidade_certificados 
    FROM assinaturas a 
    JOIN planos p ON a.plano_id = p.id 
    WHERE a.parceiro_id = ? 
    ORDER BY a.criado_em DESC 
    LIMIT 1
");
$stmt->bind_param("i", $parceiro_id);
$stmt->execute();
$result = $stmt->get_result();
$assinatura = $result->fetch_assoc();
$stmt->close();

if (!$assinatura) {
    // Se não tiver assinatura (algo errado no fluxo), redirecionar para planos ou login
    redirect(APP_URL . '/login.php');
}

// Se for Boleto e permitir parcelamento, mas não tiver parcelas definidas, mostrar seleção
$forma_pagamento = $_GET['forma'] ?? '';
if ($forma_pagamento === 'boleto' && ($assinatura['max_parcelas'] ?? 1) > 1 && !isset($_GET['parcelas']) && !isset($_POST['parcelas'])) {
    $page_title = "Parcelamento no Boleto";
    // include '../includes/header-parceiro.php'; // Arquivo não existe, substituído por HTML inline
    ?>
    <!DOCTYPE html>
    <html lang="pt-BR">

    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title><?php echo $page_title; ?></title>
        <link href="<?php echo DIR_VENDOR; ?>/fontawesome-free/css/all.min.css" rel="stylesheet" type="text/css">
        <link href="<?php echo DIR_CSS; ?>/sb-admin-2.min.css" rel="stylesheet">
        <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
        <style>
            body {
                font-family: 'Inter', sans-serif;
            }
        </style>
    </head>

    <body class="bg-light">
        <div class="container-fluid mt-5">
            <div class="row">
                <div class="col-md-6 offset-md-3">
                    <div class="card shadow">
                        <div class="card-header py-3">
                            <h6 class="m-0 font-weight-bold text-primary">
                                <i class="fas fa-file-invoice-dollar"></i> Opções de Parcelamento
                            </h6>
                        </div>
                        <div class="card-body">
                            <p>Seu plano permite parcelamento no boleto. Escolha como deseja pagar:</p>
                            <form method="GET" action="processar-pagamento.php">
                                <input type="hidden" name="forma" value="boleto">

                                <div class="form-group">
                                    <label>Número de Parcelas</label>
                                    <select class="form-control" name="parcelas" required>
                                        <?php for ($i = 1; $i <= $assinatura['max_parcelas']; $i++): ?>
                                            <option value="<?php echo $i; ?>">
                                                <?php echo $i; ?>x de R$
                                                <?php echo number_format($assinatura['valor'] / $i, 2, ',', '.'); ?>
                                                (Total: R$ <?php echo number_format($assinatura['valor'], 2, ',', '.'); ?>)
                                            </option>
                                        <?php endfor; ?>
                                    </select>
                                </div>

                                <button type="submit" class="btn btn-primary btn-block">
                                    <i class="fas fa-check"></i> Gerar Boleto
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </body>

    </html>
    <?php
    // include '../includes/footer-parceiro.php'; // Arquivo não existe
    exit;
}

$page_title = 'Pagamento - ' . APP_NAME;
?>
<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons+Outlined" rel="stylesheet">
    <style>
        :root {
            --primary-color: #0052CC;
            --primary-hover: #0747A6;
            --success-color: #36B37E;
            --text-dark: #172B4D;
            --text-medium: #5E6C84;
            --border-color: #DFE1E6;
            --bg-color: #F4F5F7;
            --card-bg: #FFFFFF;
        }

        body {
            font-family: 'Inter', sans-serif;
            background-color: var(--bg-color);
            color: var(--text-dark);
            margin: 0;
            padding: 0;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        /* Topbar Simplificada */
        .topbar {
            background: white;
            height: 60px;
            border-bottom: 1px solid var(--border-color);
            display: flex;
            align-items: center;
            padding: 0 24px;
            justify-content: center;
        }

        .logo {
            font-weight: 700;
            font-size: 20px;
            color: var(--primary-color);
            display: flex;
            align-items: center;
            gap: 8px;
        }

        /* Stepper */
        .stepper-container {
            display: flex;
            justify-content: center;
            margin: 40px 0;
            width: 100%;
            max-width: 600px;
            position: relative;
        }

        .stepper-container::before {
            content: '';
            position: absolute;
            top: 15px;
            left: 50px;
            right: 50px;
            height: 2px;
            background: #EBECF0;
            z-index: 0;
        }

        .step {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
            z-index: 1;
            gap: 8px;
        }

        .step-circle {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            background: #EBECF0;
            color: var(--text-medium);
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            font-size: 14px;
            transition: all 0.3s ease;
        }

        .step span {
            font-size: 14px;
            font-weight: 500;
            color: var(--text-medium);
        }

        .step.active .step-circle {
            background: var(--primary-color);
            color: white;
            box-shadow: 0 0 0 4px rgba(0, 82, 204, 0.1);
        }

        .step.completed .step-circle {
            background: var(--success-color);
            color: white;
        }

        .main-container {
            max-width: 1000px;
            width: 100%;
            display: grid;
            grid-template-columns: 1fr 1.5fr;
            gap: 0;
            background: var(--card-bg);
            border-radius: 8px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.12), 0 1px 2px rgba(0, 0, 0, 0.24);
            overflow: hidden;
        }

        /* Lado Esquerdo - Resumo do Plano */
        .plan-summary {
            background: #FAFBFC;
            padding: 40px;
            border-right: 1px solid var(--border-color);
            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        .plan-summary h2 {
            font-size: 20px;
            font-weight: 700;
            margin-bottom: 8px;
            color: var(--text-dark);
        }

        .plan-summary p {
            color: var(--text-medium);
            font-size: 14px;
            margin-bottom: 32px;
        }

        .plan-card {
            background: white;
            border: 1px solid var(--border-color);
            border-radius: 4px;
            padding: 24px;
            box-shadow: 0 1px 2px rgba(0, 0, 0, 0.05);
        }

        .plan-name {
            font-size: 16px;
            font-weight: 600;
            color: var(--text-dark);
            margin-bottom: 4px;
        }

        .plan-price {
            font-size: 28px;
            font-weight: 700;
            color: var(--primary-color);
            margin-bottom: 24px;
        }

        .plan-price span {
            font-size: 14px;
            color: var(--text-medium);
            font-weight: 500;
        }

        .plan-feature {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 12px;
            font-size: 14px;
            color: var(--text-dark);
        }

        .plan-feature .material-icons-outlined {
            color: var(--success-color);
            font-size: 18px;
        }

        /* Lado Direito - Pagamento */
        .payment-section {
            padding: 40px 60px;
            background: white;
        }

        .payment-header {
            margin-bottom: 32px;
        }

        .payment-header h1 {
            font-size: 24px;
            font-weight: 700;
            margin-bottom: 8px;
            color: var(--text-dark);
        }

        .payment-header p {
            color: var(--text-medium);
            font-size: 14px;
        }

        .payment-options {
            display: grid;
            gap: 16px;
            margin-bottom: 32px;
        }

        .payment-option {
            display: flex;
            align-items: center;
            padding: 20px;
            border: 2px solid var(--border-color);
            border-radius: 4px;
            cursor: pointer;
            transition: all 0.2s ease;
            position: relative;
        }

        .payment-option:hover {
            border-color: var(--primary-color);
            background-color: #FAFBFC;
        }

        .payment-option.recommended {
            border-color: var(--success-color);
            background-color: #E3FCEF;
        }

        .payment-icon-wrapper {
            width: 48px;
            height: 48px;
            border-radius: 4px;
            background: #F4F5F7;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 16px;
        }

        .payment-option.recommended .payment-icon-wrapper {
            background: rgba(54, 179, 126, 0.2);
        }

        .payment-info {
            flex: 1;
        }

        .payment-title {
            font-weight: 600;
            font-size: 15px;
            color: var(--text-dark);
            margin-bottom: 2px;
        }

        .payment-desc {
            font-size: 13px;
            color: var(--text-medium);
        }

        .badge-rec {
            position: absolute;
            top: -10px;
            right: 20px;
            background: var(--success-color);
            color: white;
            font-size: 10px;
            font-weight: 700;
            padding: 4px 8px;
            border-radius: 12px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .btn-later-link {
            display: block;
            text-align: center;
            color: var(--text-medium);
            font-size: 13px;
            text-decoration: none;
            margin-top: 24px;
            transition: color 0.2s;
        }

        .btn-later-link:hover {
            color: var(--text-dark);
            text-decoration: underline;
        }

        @media (max-width: 900px) {
            .main-container {
                grid-template-columns: 1fr;
            }

            .plan-summary {
                border-right: none;
                border-bottom: 1px solid var(--border-color);
                padding: 30px;
            }

            .payment-section {
                padding: 30px;
            }

            .stepper-container {
                display: none;
                /* Esconde stepper em mobile para economizar espaço */
            }
        }

        .page-content-wrapper {
            width: 100%;
            display: flex;
            flex-direction: column;
            align-items: center;
            flex: 1;
        }
    </style>

    <!-- Stepper -->
    <div class="page-content-wrapper"
        style="width: 100%; display: flex; flex-direction: column; align-items: center; flex: 1;">
        <!-- Stepper -->
        <div class="stepper-container">
            <div class="step completed">
                <div class="step-circle"><span class="material-icons-outlined" style="font-size: 16px;">check</span>
                </div>
                <span>Aceite do Contrato</span>
            </div>
            <div class="step active">
                <div class="step-circle">2</div>
                <span>Pagamento</span>
            </div>
            <div class="step">
                <div class="step-circle">3</div>
                <span>Conclusão</span>
            </div>
        </div>

        <div class="main-container">
            <!-- Resumo do Plano -->
            <div class="plan-summary">
                <h2>Seu Plano</h2>
                <p>Você está a um passo de começar.</p>

                <div class="plan-card">
                    <div class="plan-name"><?php echo htmlspecialchars($assinatura['plano_nome']); ?></div>
                    <div class="plan-price">
                        R$ <?php echo number_format($assinatura['valor'], 2, ',', '.'); ?>
                        <span>/mês</span>
                    </div>

                    <div class="plan-feature">
                        <span class="material-icons-outlined">check_circle</span>
                        <span><?php echo ($assinatura['certificados_mensais'] > 0) ? $assinatura['certificados_mensais'] : ($assinatura['quantidade_certificados'] ?? $assinatura['certificados_totais']); ?>
                            certificados mensais</span>
                    </div>
                    <div class="plan-feature">
                        <span class="material-icons-outlined">check_circle</span>
                        <span>Acesso total à plataforma</span>
                    </div>
                    <div class="plan-feature">
                        <span class="material-icons-outlined">check_circle</span>
                        <span>Suporte prioritário</span>
                    </div>

                    <div
                        style="margin-top: 20px; padding-top: 20px; border-top: 1px solid #EBECF0; font-size: 13px; color: var(--text-medium);">
                        Vencimento da fatura:
                        <strong><?php echo date('d/m/Y', strtotime($assinatura['data_vencimento'])); ?></strong>
                    </div>
                </div>
            </div>

            <!-- Opções de Pagamento -->
            <div class="payment-section">
                <div class="payment-header">
                    <h1>Como você prefere pagar?</h1>
                    <p>Escolha uma forma de pagamento segura para ativar sua conta.</p>
                </div>

                <?php if (!empty($_SESSION['erro'])): ?>
                    <div class="alert alert-danger mb-4" role="alert"
                        style="border-radius: 4px; font-size: 14px; background: #FFEBE6; color: #BF2600; border: 1px solid #FFBDAD; padding: 12px;">
                        <?php echo htmlspecialchars($_SESSION['erro']);
                        unset($_SESSION['erro']); ?>
                    </div>
                <?php endif; ?>

                <div class="payment-options">
                    <!-- PIX -->
                    <div class="payment-option recommended" onclick="selecionarPagamento('pix')">
                        <div class="badge-rec">Mais Rápido</div>
                        <div class="payment-icon-wrapper">
                            <span class="material-icons-outlined"
                                style="color: #36B37E; font-size: 28px;">qr_code_2</span>
                        </div>
                        <div class="payment-info">
                            <div class="payment-title">Pix</div>
                            <div class="payment-desc">Aprovação imediata. Liberação instantânea.</div>
                        </div>
                        <span class="material-icons-outlined" style="color: #36B37E;">chevron_right</span>
                    </div>

                    <!-- Cartão de Crédito -->
                    <div class="payment-option" onclick="selecionarPagamento('cartao_credito')">
                        <div class="payment-icon-wrapper">
                            <span class="material-icons-outlined"
                                style="color: #0052CC; font-size: 28px;">credit_card</span>
                        </div>
                        <div class="payment-info">
                            <div class="payment-title">Cartão de Crédito</div>
                            <div class="payment-desc">Até 12x sem juros. Renovação automática.</div>
                        </div>
                        <span class="material-icons-outlined" style="color: var(--border-color);">chevron_right</span>
                    </div>

                    <!-- Boleto Bancário -->
                    <div class="payment-option" onclick="selecionarPagamento('boleto')">
                        <div class="payment-icon-wrapper">
                            <span class="material-icons-outlined"
                                style="color: #FFAB00; font-size: 28px;">receipt_long</span>
                        </div>
                        <div class="payment-info">
                            <div class="payment-title">Boleto Bancário</div>
                            <div class="payment-desc">
                                <?php if (($assinatura['max_parcelas'] ?? 1) > 1): ?>
                                    Parcelamento em até <?php echo $assinatura['max_parcelas']; ?>x
                                <?php else: ?>
                                    Pagamento à vista
                                <?php endif; ?>
                            </div>
                        </div>
                        <span class="material-icons-outlined" style="color: var(--border-color);">chevron_right</span>
                    </div>
                </div>

                <!-- Link Pagar Mais Tarde -->
                <a href="#" class="btn-later-link" onclick="pagarMaisTarde()">
                    Pagar mais tarde e acessar o sistema
                </a>
            </div>
        </div>
    </div>

    <script>
        function selecionarPagamento(metodo) {
            // Redirecionar para a página de processamento com o método selecionado
            window.location.href = 'processar-pagamento.php?forma=' + metodo;
        }

        function pagarMaisTarde() {
            if (confirm('Tem certeza que deseja pagar mais tarde?\n\nVocê poderá acessar o sistema, mas NÃO poderá emitir certificados até que o pagamento seja realizado.')) {
                window.location.href = '../app/actions/pagar-mais-tarde.php';
            }
        }
    </script>

    <?php require_once '../app/views/footer.php'; ?>