<?php
/**
 * Retorno do Pagamento Asaas
 * Página para onde o usuário é redirecionado após pagar no Asaas
 */

require_once '../app/config/config.php';

// Verificar se parceiro está logado
if (!isAuthenticated() || !hasRole(ROLE_PARCEIRO)) {
    header('Location: ' . APP_URL . '/login.php');
    exit;
}

$user = getCurrentUser();
$parceiro_id = $user['parceiro_id'] ?? $user['id'];
$status = $_GET['status'] ?? 'aguardando';

$conn = getDBConnection();

// Verificar se já tem pagamento confirmado
$stmt = $conn->prepare("SELECT COUNT(*) as total FROM faturas WHERE parceiro_id = ? AND status = 'pago'");
$stmt->bind_param("i", $parceiro_id);
$stmt->execute();
$result = $stmt->get_result()->fetch_assoc();
$stmt->close();

$ja_pagou = $result['total'] > 0;

$page_title = 'Retorno do Pagamento - ' . APP_NAME;
require_once '../app/views/header.php';
?>

<style>
    @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap');
    @import url('https://fonts.googleapis.com/icon?family=Material+Icons+Outlined');

    body {
        font-family: 'Inter', sans-serif;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        min-height: 100vh;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 20px;
    }

    .return-container {
        max-width: 600px;
        width: 100%;
        background: #fff;
        border-radius: 20px;
        box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
        overflow: hidden;
        text-align: center;
    }

    .return-header {
        background: linear-gradient(135deg, #6E41C1 0%, #8B5FD6 100%);
        color: #fff;
        padding: 40px;
    }

    .return-header h1 {
        font-size: 28px;
        font-weight: 700;
        margin: 0 0 10px 0;
    }

    .return-content {
        padding: 40px;
    }

    .status-icon {
        font-size: 80px;
        margin-bottom: 20px;
    }

    .btn-primary {
        background: linear-gradient(135deg, #6E41C1 0%, #8B5FD6 100%);
        color: #fff;
        border: none;
        border-radius: 8px;
        padding: 12px 30px;
        font-weight: 600;
        text-decoration: none;
        display: inline-block;
        margin-top: 20px;
    }

    .btn-primary:hover {
        opacity: 0.9;
        color: #fff;
        text-decoration: none;
    }

    .alert-box {
        background: #f8f9fa;
        border-left: 4px solid #6E41C1;
        padding: 20px;
        border-radius: 8px;
        margin-top: 20px;
        text-align: left;
    }
</style>

<div class="return-container">
    <div class="return-header">
        <h1>Retorno do Pagamento</h1>
    </div>

    <div class="return-content">
        <?php if ($ja_pagou): ?>
            <!-- Pagamento Confirmado -->
            <div class="status-icon" style="color:#34C759">✅</div>
            <h2>Pagamento Confirmado!</h2>
            <p style="color:#6B6B6B; margin: 20px 0">
                Seu pagamento foi processado com sucesso. Agora você tem acesso completo ao sistema!
            </p>
            <a href="<?php echo APP_URL; ?>/parceiro/dashboard-parceiro.php" class="btn-primary">
                Ir para o Dashboard
            </a>

        <?php elseif ($status === 'sucesso'): ?>
            <!-- Aguardando Confirmação -->
            <div class="status-icon" style="color:#FF9F0A">⏳</div>
            <h2>Pagamento Processado</h2>
            <p style="color:#6B6B6B; margin: 20px 0">
                Seu pagamento foi processado e está aguardando confirmação. Isso pode levar alguns minutos.
            </p>

            <div class="alert-box">
                <strong>ℹ️ O que acontece agora?</strong>
                <ul style="margin: 10px 0; padding-left: 20px; text-align: left">
                    <li>PIX: Confirmação em até 5 minutos</li>
                    <li>Cartão: Confirmação instantânea</li>
                    <li>Boleto: Confirmação em 1-3 dias úteis</li>
                </ul>
                <p style="margin-top: 10px">
                    <strong>Enquanto isso:</strong> Você pode navegar no sistema, mas não poderá emitir certificados até a
                    confirmação do pagamento.
                </p>
            </div>

            <a href="<?php echo APP_URL; ?>/parceiro/financeiro.php" class="btn-primary">
                Ver Financeiro
            </a>

        <?php else: ?>
            <!-- Aguardando/Indefinido -->
            <div class="status-icon" style="color:#6E41C1">💳</div>
            <h2>Retorno do Pagamento</h2>
            <p style="color:#6B6B6B; margin: 20px 0">
                Seu pagamento está sendo processado.
            </p>

            <div class="alert-box">
                <strong>📋 Verifique o status:</strong>
                <p style="margin-top: 10px">
                    Acesse a página de Financeiro para verificar o status do seu pagamento.
                </p>
            </div>

            <a href="<?php echo APP_URL; ?>/parceiro/financeiro.php" class="btn-primary">
                Ver Financeiro
            </a>
        <?php endif; ?>

        <p style="margin-top: 30px; font-size: 14px; color: #ADADAD">
            Se tiver dúvidas, entre em contato com o suporte.
        </p>
    </div>
</div>

<?php require_once '../app/views/footer.php'; ?>