<?php
require_once '../app/config/config.php';

// Verificar se parceiro está logado
if (!isAuthenticated()) {
    header('Location: login.php');
    exit;
}

$user = getCurrentUser();
$parceiro_id = $user['parceiro_id'] ?? $user['id'];
$cobranca_id = $_GET['id'] ?? 0;

// Se passou redirect=asaas, redirecionar direto para o boleto do Asaas
$redirect_asaas = isset($_GET['redirect']) && $_GET['redirect'] === 'asaas';

$conn = getDBConnection();

// Buscar cobrança
$stmt = $conn->prepare("
    SELECT 
        c.*,
        f.numero_fatura,
        f.descricao as fatura_descricao,
        p.nome as plano_nome
    FROM asaas_cobrancas c
    LEFT JOIN faturas f ON c.fatura_id = f.id
    LEFT JOIN assinaturas a ON c.assinatura_id = a.id
    LEFT JOIN planos p ON a.plano_id = p.id
    WHERE c.id = ? AND c.parceiro_id = ?
");
$stmt->bind_param("ii", $cobranca_id, $parceiro_id);
$stmt->execute();
$cobranca = $stmt->get_result()->fetch_assoc();
$stmt->close();
$conn->close();

if (!$cobranca) {
    $_SESSION['erro'] = 'Cobrança não encontrada';
    header('Location: meu-plano.php');
    exit;
}

// Redirecionar diretamente para o boleto do Asaas se solicitado ou se é boleto
if ($redirect_asaas || (isset($_GET['abrir']) && $_GET['abrir'] === 'boleto')) {
    if ($cobranca['pdf_url']) {
        header('Location: ' . $cobranca['pdf_url']);
        exit;
    } elseif ($cobranca['invoice_url']) {
        header('Location: ' . $cobranca['invoice_url']);
        exit;
    }
}

$page_title = "Visualizar Cobrança";
include '../includes/header-parceiro.php';
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <h1 class="h3 mb-4 text-gray-800">
                <i class="fas fa-file-invoice"></i> Cobrança #<?php echo $cobranca['id']; ?>
            </h1>
        </div>
    </div>

    <?php if (isset($_SESSION['sucesso'])): ?>
        <div class="alert alert-success alert-dismissible fade show">
            <i class="fas fa-check-circle"></i> <?php echo $_SESSION['sucesso'];
            unset($_SESSION['sucesso']); ?>
            <button type="button" class="close" data-dismiss="alert">&times;</button>
        </div>
    <?php endif; ?>

    <!-- Informações da Cobrança -->
    <div class="row mb-4">
        <div class="col-md-12">
            <div class="card shadow">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Informações da Cobrança</h6>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-3">
                            <strong>Número da Fatura:</strong><br>
                            <?php echo htmlspecialchars($cobranca['numero_fatura']); ?>
                        </div>
                        <div class="col-md-3">
                            <strong>Valor:</strong><br>
                            R$ <?php echo number_format($cobranca['valor'], 2, ',', '.'); ?>
                        </div>
                        <div class="col-md-3">
                            <strong>Vencimento:</strong><br>
                            <?php echo date('d/m/Y', strtotime($cobranca['data_vencimento'])); ?>
                        </div>
                        <div class="col-md-3">
                            <strong>Status:</strong><br>
                            <?php
                            $status_badges = [
                                'pendente' => 'warning',
                                'confirmado' => 'info',
                                'recebido' => 'success',
                                'expirado' => 'danger',
                                'cancelado' => 'secondary'
                            ];
                            $badge = $status_badges[$cobranca['status']] ?? 'secondary';
                            ?>
                            <span class="badge badge-<?php echo $badge; ?>">
                                <?php echo strtoupper($cobranca['status']); ?>
                            </span>
                        </div>
                    </div>
                    <hr>
                    <div class="row">
                        <div class="col-md-12">
                            <strong>Descrição:</strong><br>
                            <?php echo htmlspecialchars($cobranca['observacoes'] ?? $cobranca['fatura_descricao']); ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- BOLETO -->
    <?php if ($cobranca['tipo_cobranca'] === 'boleto' && $cobranca['pdf_url']): ?>
        <div class="row mb-4">
            <div class="col-md-12">
                <div class="card shadow border-left-warning">
                    <div class="card-header py-3 bg-warning text-white">
                        <h6 class="m-0 font-weight-bold">
                            <i class="fas fa-barcode"></i> Boleto Bancário
                        </h6>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-12 mb-3">
                                <label><strong>Linha Digitável:</strong></label>
                                <div class="input-group">
                                    <input type="text" class="form-control" id="linha-digitavel"
                                        value="<?php echo htmlspecialchars($cobranca['linha_digitavel']); ?>" readonly>
                                    <div class="input-group-append">
                                        <button class="btn btn-outline-secondary" type="button"
                                            onclick="copiarTexto('linha-digitavel')">
                                            <i class="fas fa-copy"></i> Copiar
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="text-center">
                            <a href="<?php echo htmlspecialchars($cobranca['pdf_url']); ?>" target="_blank"
                                class="btn btn-warning btn-lg">
                                <i class="fas fa-download"></i> Baixar Boleto PDF
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <!-- PIX -->
    <?php if ($cobranca['tipo_cobranca'] === 'pix' && $cobranca['qr_code_pix']): ?>
        <div class="row mb-4">
            <div class="col-md-12">
                <div class="card shadow border-left-success">
                    <div class="card-header py-3 bg-success text-white">
                        <h6 class="m-0 font-weight-bold">
                            <i class="fas fa-qrcode"></i> PIX - Pagamento Instantâneo
                        </h6>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6 text-center">
                                <h6>QR Code</h6>
                                <img src="data:image/png;base64,<?php echo $cobranca['qr_code_pix']; ?>" alt="QR Code PIX"
                                    class="img-fluid" style="max-width: 300px;">
                                <p class="text-muted mt-2">Escaneie com o app do seu banco</p>
                            </div>
                            <div class="col-md-6">
                                <h6>PIX Copia e Cola</h6>
                                <div class="input-group mb-3">
                                    <textarea class="form-control" id="pix-copia-cola" rows="5"
                                        readonly><?php echo htmlspecialchars($cobranca['pix_copia_cola']); ?></textarea>
                                </div>
                                <button class="btn btn-success btn-block" onclick="copiarTexto('pix-copia-cola')">
                                    <i class="fas fa-copy"></i> Copiar Código PIX
                                </button>

                                <div class="alert alert-info mt-3">
                                    <i class="fas fa-info-circle"></i>
                                    <strong>Como pagar:</strong>
                                    <ol class="mb-0 mt-2">
                                        <li>Abra o app do seu banco</li>
                                        <li>Escolha "Pagar com PIX"</li>
                                        <li>Escaneie o QR Code ou cole o código</li>
                                        <li>Confirme o pagamento</li>
                                    </ol>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <!-- CARTÃO -->
    <?php if ($cobranca['tipo_cobranca'] === 'cartao_credito'): ?>
        <div class="row mb-4">
            <div class="col-md-12">
                <div class="card shadow border-left-primary">
                    <div class="card-header py-3 bg-primary text-white">
                        <h6 class="m-0 font-weight-bold">
                            <i class="fas fa-credit-card"></i> Cartão de Crédito
                        </h6>
                    </div>
                    <div class="card-body">
                        <?php if ($cobranca['status'] === 'recebido'): ?>
                            <div class="alert alert-success">
                                <i class="fas fa-check-circle"></i>
                                <strong>Pagamento aprovado!</strong> Sua assinatura foi renovada com sucesso.
                            </div>
                        <?php elseif ($cobranca['status'] === 'pendente'): ?>
                            <div class="alert alert-info">
                                <i class="fas fa-clock"></i>
                                <strong>Processando...</strong> Aguardando confirmação da operadora do cartão.
                            </div>
                        <?php else: ?>
                            <div class="alert alert-warning">
                                <i class="fas fa-exclamation-triangle"></i>
                                <strong>Status:</strong> <?php echo strtoupper($cobranca['status']); ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <!-- Botões de Ação -->
    <div class="row">
        <div class="col-md-12 text-center">
            <a href="meu-plano.php" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Voltar para Meu Plano
            </a>

            <?php if ($cobranca['invoice_url']): ?>
                <a href="<?php echo htmlspecialchars($cobranca['invoice_url']); ?>" target="_blank" class="btn btn-info">
                    <i class="fas fa-file-invoice"></i> Ver Fatura Completa
                </a>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
    function copiarTexto(elementId) {
        const elemento = document.getElementById(elementId);
        elemento.select();
        elemento.setSelectionRange(0, 99999); // Para mobile

        document.execCommand('copy');

        // Feedback visual
        const btn = event.target.closest('button');
        const textoOriginal = btn.innerHTML;
        btn.innerHTML = '<i class="fas fa-check"></i> Copiado!';
        btn.classList.add('btn-success');

        setTimeout(() => {
            btn.innerHTML = textoOriginal;
            btn.classList.remove('btn-success');
        }, 2000);
    }
</script>

<?php include '../includes/footer-parceiro.php'; ?>