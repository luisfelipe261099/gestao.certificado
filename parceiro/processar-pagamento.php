<?php
require_once '../app/config/config.php';
require_once '../app/lib/AsaasAPI.php';

// Verificar se parceiro está logado
if (!isAuthenticated()) {
    header('Location: login.php');
    exit;
}

$user = getCurrentUser();
$parceiro_id = $user['parceiro_id'] ?? $user['id'];
$forma_pagamento = $_GET['forma'] ?? '';

// Validar forma de pagamento
$formas_validas = ['boleto', 'pix', 'cartao_credito'];
if (!in_array($forma_pagamento, $formas_validas)) {
    $_SESSION['erro'] = 'Forma de pagamento inválida';
    header('Location: escolher-pagamento.php');
    exit;
}

$conn = getDBConnection();

// Buscar dados do parceiro
$stmt = $conn->prepare("SELECT * FROM parceiros WHERE id = ?");
$stmt->bind_param("i", $parceiro_id);
$stmt->execute();
$parceiro = $stmt->get_result()->fetch_assoc();
$stmt->close();

// Buscar assinatura ativa (com fallback de colunas para compatibilidade)
// Detectar colunas existentes em 'planos'
$valor_col = 'p.valor';
$cert_col = 'p.quantidade_certificados';
$res = $conn->query("SHOW COLUMNS FROM planos LIKE 'valor'");
if (!$res || $res->num_rows === 0) {
    $valor_col = 'p.preco';
}
$res = $conn->query("SHOW COLUMNS FROM planos LIKE 'quantidade_certificados'");
if (!$res || $res->num_rows === 0) {
    // tentar coluna antiga
    $res2 = $conn->query("SHOW COLUMNS FROM planos LIKE 'certificados_mensais'");
    if ($res2 && $res2->num_rows > 0) {
        $cert_col = 'p.certificados_mensais';
    } else {
        $cert_col = 'a.certificados_totais';
    }
}

$query = "
    SELECT a.*, p.nome as plano_nome, p.max_parcelas, {$valor_col} AS valor, {$cert_col} AS certificados_mensais
    FROM assinaturas a
    JOIN planos p ON a.plano_id = p.id
    WHERE a.parceiro_id = ?
    ORDER BY a.criado_em DESC
    LIMIT 1
";

$stmt = $conn->prepare($query);
if (!$stmt) {
    error_log('Falha ao preparar SELECT assinatura: ' . $conn->error . ' | Query: ' . $query);
    $_SESSION['erro'] = 'Erro ao carregar sua assinatura (estrutura do banco).';
    header('Location: escolher-pagamento.php');
    exit;
}
$stmt->bind_param("i", $parceiro_id);
$stmt->execute();
$assinatura = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$assinatura) {
    $_SESSION['erro'] = 'Você não possui uma assinatura ativa.';
    header('Location: meu-plano.php');
    exit;
}

// Se for Boleto e permitir parcelamento, mas não tiver parcelas definidas, mostrar seleção
if ($forma_pagamento === 'boleto' && ($assinatura['max_parcelas'] ?? 1) > 1 && !isset($_GET['parcelas']) && !isset($_POST['parcelas'])) {
    $page_title = "Parcelamento no Boleto";
    // include '../includes/header-parceiro.php'; // Arquivo não existe
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

// Se for cartão, mostrar formulário
if (false && $forma_pagamento === 'cartao_credito' && $_SERVER['REQUEST_METHOD'] !== 'POST') {
    $page_title = "Pagamento com Cartão de Crédito";
    include '../includes/header-parceiro.php';
    ?>

    <div class="container-fluid">
        <div class="row">
            <div class="col-md-8 offset-md-2">
                <div class="card shadow">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-primary">
                            <i class="fas fa-credit-card"></i> Dados do Cartão de Crédito
                        </h6>
                    </div>
                    <div class="card-body">
                        <form method="POST" id="form-cartao">
                            <input type="hidden" name="forma" value="cartao_credito">

                            <div class="row">
                                <div class="col-md-12 mb-3">
                                    <label>Número do Cartão</label>
                                    <input type="text" class="form-control" name="cartao_numero"
                                        placeholder="0000 0000 0000 0000" maxlength="19" required>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-8 mb-3">
                                    <label>Nome no Cartão</label>
                                    <input type="text" class="form-control" name="cartao_nome"
                                        placeholder="Como está no cartão" required>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label>CVV</label>
                                    <input type="text" class="form-control" name="cartao_cvv" placeholder="123"
                                        maxlength="4" required>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label>Mês de Validade</label>
                                    <select class="form-control" name="cartao_mes" required>
                                        <option value="">Selecione</option>
                                        <?php for ($i = 1; $i <= 12; $i++): ?>
                                            <option value="<?php echo str_pad($i, 2, '0', STR_PAD_LEFT); ?>">
                                                <?php echo str_pad($i, 2, '0', STR_PAD_LEFT); ?>
                                            </option>
                                        <?php endfor; ?>
                                    </select>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label>Ano de Validade</label>
                                    <select class="form-control" name="cartao_ano" required>
                                        <option value="">Selecione</option>
                                        <?php
                                        $ano_atual = date('Y');
                                        for ($i = 0; $i <= 15; $i++):
                                            $ano = $ano_atual + $i;
                                            ?>
                                            <option value="<?php echo $ano; ?>"><?php echo $ano; ?></option>
                                        <?php endfor; ?>
                                    </select>
                                </div>
                            </div>

                            <div class="alert alert-info">
                                <i class="fas fa-info-circle"></i>
                                <strong>Valor:</strong> R$ <?php echo number_format($assinatura['valor'], 2, ',', '.'); ?>
                            </div>

                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label>Parcelamento</label>
                                    <select class="form-control" name="parcelas" required>
                                        <?php for ($i = 1; $i <= 12; $i++): ?>
                                            <option value="<?php echo $i; ?>"><?php echo $i; ?>x de R$
                                                <?php echo number_format($assinatura['valor'] / $i, 2, ',', '.'); ?>
                                            </option>
                                        <?php endfor; ?>
                                    </select>
                                </div>
                            </div>

                            <div class="form-check mb-3">
                                <input type="checkbox" class="form-check-input" id="salvar-cartao" name="salvar_cartao"
                                    value="1">
                                <label class="form-check-label" for="salvar-cartao">
                                    Salvar cartão para renovação automática
                                </label>
                            </div>

                            <button type="submit" class="btn btn-primary btn-block">
                                <i class="fas fa-lock"></i> Pagar R$
                                <?php echo number_format($assinatura['valor'], 2, ',', '.'); ?>
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Formatar número do cartão
        $('input[name="cartao_numero"]').on('input', function () {
            let value = $(this).val().replace(/\s/g, '');
            let formattedValue = value.match(/.{1,4}/g)?.join(' ') || value;
            $(this).val(formattedValue);
        });

        // Apenas números no CVV
        $('input[name="cartao_cvv"]').on('input', function () {
            $(this).val($(this).val().replace(/\D/g, ''));
        });
    </script>

    <?php
    include '../includes/footer-parceiro.php';
    exit;
}

// Processar pagamento
$asaas = new AsaasAPI($conn);

// Calcular valor da parcela (para boleto parcelado)
$num_parcelas = 1;
if ($forma_pagamento === 'boleto' && isset($_GET['parcelas'])) {
    $num_parcelas = max(1, (int) $_GET['parcelas']);
}
$valor_parcela = $assinatura['valor'] / $num_parcelas;

$dados_cobranca = [
    'nome_cliente' => $parceiro['nome_empresa'],
    'email_cliente' => $parceiro['email'],
    'cpf_cnpj' => $parceiro['cnpj'],
    'telefone' => $parceiro['telefone'],
    'endereco' => $parceiro['endereco'],
    'cidade' => $parceiro['cidade'],
    'estado' => $parceiro['estado'],
    'cep' => $parceiro['cep'],
    'valor' => $valor_parcela,
    'data_vencimento' => date('Y-m-d', strtotime('+5 days')),
    'descricao' => "Renovação - {$assinatura['plano_nome']}" . ($num_parcelas > 1 ? " (Parcela 1/{$num_parcelas})" : ""),
    'referencia_externa' => "RENOV-{$assinatura['id']}-" . date('YmdHis'),
    'parcelas' => $num_parcelas
];

// URLs de retorno do Asaas - IMPORTANTE!
// Após pagamento com sucesso, redirecionar para o dashboard do parceiro
$return_url = APP_URL . '/parceiro/retorno-pagamento.php?status=aguardando';
$success_url = APP_URL . '/parceiro/dashboard-parceiro.php';

$dados_cobranca['success_url'] = $success_url;
$dados_cobranca['return_url'] = $return_url;

// Forçar cartão diretamente no checkout de cartão e auto redirect após pagamento
if ($forma_pagamento === 'cartao_credito') {
    $dados_cobranca['auto_redirect'] = true;
}


// Adicionar dados do cartão se for pagamento com cartão
if ($forma_pagamento === 'cartao_credito' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $dados_cobranca['cartao_numero'] = str_replace(' ', '', $_POST['cartao_numero']);
    $dados_cobranca['cartao_nome'] = $_POST['cartao_nome'];
    $dados_cobranca['cartao_cvv'] = $_POST['cartao_cvv'];
    $dados_cobranca['cartao_mes'] = $_POST['cartao_mes'];
    $dados_cobranca['cartao_ano'] = $_POST['cartao_ano'];
    $dados_cobranca['numero'] = $parceiro['numero'] ?? '';
    $dados_cobranca['parcelas'] = isset($_POST['parcelas']) ? (int) $_POST['parcelas'] : 1;
}

// Parcelas já foram calculadas acima para boleto

// Criar cobrança
$resultado = null;
switch ($forma_pagamento) {
    case 'pix':
        // Criar cobrança PIX direta (sem invoiceUrl)
        $resultado = $asaas->criarCobrancaPix($dados_cobranca);
        break;
    case 'cartao_credito':
        // Para cartão, usar invoiceUrl (checkout hospedado) que permite pagamento sem coletar dados sensíveis
        // NOTA: Requer domínio configurado no Asaas em Minha Conta > Informações > Site
        $resultado = $asaas->criarCobrancaHosted($dados_cobranca, 'CREDIT_CARD');
        break;
    default:
        $resultado = $asaas->criarBoleto($dados_cobranca);
}

if (!$resultado['success']) {
    $err = $resultado['error'] ?? 'Erro desconhecido';
    $code = $resultado['http_code'] ?? 0;
    $apiUrl = $resultado['url'] ?? '';
    error_log("Asaas falhou (forma={$forma_pagamento}) code={$code} url={$apiUrl} erro={$err}");
    $_SESSION['erro'] = 'Erro ao gerar cobrança: ' . $err;
    header('Location: escolher-pagamento.php');
    exit;
}

// Salvar fatura
$numero_fatura = 'FAT-' . $parceiro_id . '-' . date('YmdHis');
$stmt = $conn->prepare("
    INSERT INTO faturas (parceiro_id, assinatura_id, numero_fatura, descricao, valor, data_emissao, data_vencimento, status, forma_pagamento, tipo, asaas_id, criado_em)
    VALUES (?, ?, ?, ?, ?, NOW(), ?, 'pendente', ?, 'renovacao', ?, NOW())
");
if (!$stmt) {
    error_log('Falha ao preparar INSERT faturas: ' . $conn->error);
    $_SESSION['erro'] = 'Erro ao salvar fatura.';
    header('Location: escolher-pagamento.php');
    exit;
}
$stmt->bind_param(
    'iissdsss',
    $parceiro_id,
    $assinatura['id'],
    $numero_fatura,
    $dados_cobranca['descricao'],
    $valor_parcela,
    $dados_cobranca['data_vencimento'],
    $forma_pagamento,
    $resultado['data']['id']
);
if (!$stmt->execute()) {
    error_log('Falha ao inserir fatura: ' . $stmt->error);
    $_SESSION['erro'] = 'Erro ao salvar fatura: ' . $stmt->error;
    header('Location: escolher-pagamento.php');
    exit;
}
$fatura_id = $conn->insert_id;
$stmt->close();

// Salvar cobrança
$asaas_id = $resultado['data']['id'];
$url_boleto = $resultado['data']['bankSlipUrl'] ?? null;
$linha_digitavel = $resultado['data']['identificationField'] ?? null;
$qr_code_pix = $resultado['data']['encodedImage'] ?? null;
$pix_copia_cola = $resultado['data']['payload'] ?? null;
$invoice_url = $resultado['data']['invoiceUrl'] ?? null;

$stmt = $conn->prepare("
    INSERT INTO asaas_cobrancas (fatura_id, assinatura_id, parceiro_id, asaas_id, valor, data_vencimento, status, tipo_cobranca, pdf_url, linha_digitavel, qr_code_pix, pix_copia_cola, invoice_url, observacoes, criado_em)
    VALUES (?, ?, ?, ?, ?, ?, 'pendente', ?, ?, ?, ?, ?, ?, ?, NOW())
");
if (!$stmt) {
    error_log('Falha ao preparar INSERT asaas_cobrancas: ' . $conn->error);
    $_SESSION['erro'] = 'Erro ao salvar cobrança.';
    header('Location: escolher-pagamento.php');
    exit;
}
$stmt->bind_param(
    'iiisdssssssss',
    $fatura_id,
    $assinatura['id'],
    $parceiro_id,
    $asaas_id,
    $valor_parcela,
    $dados_cobranca['data_vencimento'],
    $forma_pagamento,
    $url_boleto,
    $linha_digitavel,
    $qr_code_pix,
    $pix_copia_cola,
    $invoice_url,
    $dados_cobranca['descricao']
);
if (!$stmt->execute()) {
    error_log('Falha ao inserir asaas_cobrancas: ' . $stmt->error);
    $_SESSION['erro'] = 'Erro ao salvar cobrança: ' . $stmt->error;
    header('Location: escolher-pagamento.php');
    exit;
}
$cobranca_id = $conn->insert_id;
$stmt->close();

$conn->close();

// Redirecionar: Para todos os tipos, abrir o link do Asaas diretamente
$_SESSION['sucesso'] = 'Cobrança gerada com sucesso!';

// A invoiceUrl é mais confiável porque está disponível imediatamente
// O bankSlipUrl pode demorar alguns segundos para ser gerado
if (!empty($invoice_url)) {
    // Redirecionar para a página de pagamento do Asaas (funciona para todos os tipos)
    header("Location: " . $invoice_url);
} elseif ($forma_pagamento === 'boleto' && !empty($url_boleto)) {
    // Fallback: PDF do boleto diretamente
    header("Location: " . $url_boleto);
} else {
    // Fallback final: página local de visualização
    header("Location: visualizar-cobranca.php?id=$cobranca_id");
}
exit;
?>