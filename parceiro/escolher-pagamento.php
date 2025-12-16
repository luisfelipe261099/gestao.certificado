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
$conn = getDBConnection();

// Buscar dados do parceiro
$stmt = $conn->prepare("SELECT * FROM parceiros WHERE id = ?");
$stmt->bind_param("i", $parceiro_id);
$stmt->execute();
$parceiro = $stmt->get_result()->fetch_assoc();
$stmt->close();

// Buscar assinatura ativa (com fallback de colunas para compatibilidade)
$valor_col = 'p.valor';
$cert_col = 'p.quantidade_certificados';
$res = $conn->query("SHOW COLUMNS FROM planos LIKE 'valor'");
if (!$res || $res->num_rows === 0) {
    $valor_col = 'p.preco';
}
$res = $conn->query("SHOW COLUMNS FROM planos LIKE 'quantidade_certificados'");
if (!$res || $res->num_rows === 0) {
    $res2 = $conn->query("SHOW COLUMNS FROM planos LIKE 'certificados_mensais'");
    if ($res2 && $res2->num_rows > 0) {
        $cert_col = 'p.certificados_mensais';
    } else {
        $cert_col = 'a.certificados_totais';
    }
}

$query = "
    SELECT a.*, p.nome as plano_nome, {$valor_col} as valor, {$cert_col} as certificados_mensais
    FROM assinaturas a
    JOIN planos p ON a.plano_id = p.id
    WHERE a.parceiro_id = ?
    ORDER BY a.criado_em DESC
    LIMIT 1
";

$stmt = $conn->prepare($query);
if (!$stmt) {
    error_log('Falha ao preparar SELECT assinatura (escolher): ' . $conn->error . ' | Query: ' . $query);
    $_SESSION['erro'] = 'Erro ao carregar sua assinatura (estrutura do banco).';
    header('Location: meu-plano.php');
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

$page_title = 'Escolher Forma de Pagamento - ' . APP_NAME;
require_once '../app/views/header.php';
require_once '../app/views/sidebar-parceiro.php';
?>
<div id="content-wrapper" class="d-flex flex-column">
    <div id="content">
        <?php require_once '../app/views/topbar.php'; ?>

        <style>
            @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap');
            @import url('https://fonts.googleapis.com/icon?family=Material+Icons+Outlined');

            :root {
                --primary-color: #6E41C1;
                --primary-hover: #56349A;
                --sidebar-bg: #F5F5F7;
                --sidebar-text: #1D1D1F;
                --content-bg: #FFFFFF;
                --card-bg: #FFFFFF;
                --text-dark: #1D1D1F;
                --text-medium: #6B6B6B;
                --text-light: #ADADAD;
                --border-light: #E0E0E0;
                --shadow-subtle: 0 2px 8px rgba(0, 0, 0, 0.05);
                --radius: 14px
            }

            /* Esconde layout antigo */
            #accordionSidebar,
            .topbar,
            .scroll-to-top {
                display: none !important
            }

            #content {
                padding: 0 !important
            }

            body {
                font-family: 'Inter', sans-serif;
                color: var(--text-dark);
                background: var(--content-bg)
            }

            /* Layout ERP */
            .erp-container {
                display: flex;
                min-height: 100vh
            }

            .erp-sidebar {
                width: 250px;
                background: var(--sidebar-bg);
                border-right: 1px solid var(--border-light);
                padding: 24px 20px
            }

            .erp-sidebar .header {
                font-weight: 700;
                font-size: 1.2rem;
                margin-bottom: 18px
            }

            .erp-sidebar .nav-section {
                font-size: .7rem;
                color: var(--text-light);
                margin: 16px 0 8px 6px
            }

            .erp-sidebar a {
                display: flex;
                align-items: center;
                padding: 10px 12px;
                border-radius: 10px;
                color: var(--sidebar-text);
                text-decoration: none;
                margin-bottom: 6px
            }

            .erp-sidebar a:hover {
                background: #ececf1
            }

            .erp-sidebar .icon {
                font-family: 'Material Icons Outlined';
                font-size: 20px;
                margin-right: 10px;
                color: var(--text-medium)
            }

            .erp-main {
                flex: 1;
                display: flex;
                flex-direction: column
            }

            .erp-top {
                height: 60px;
                border-bottom: 1px solid var(--border-light);
                display: flex;
                justify-content: flex-end;
                align-items: center;
                padding: 0 20px
            }

            .erp-content {
                flex: 1;
                overflow: auto;
                padding: 24px
            }

            .card-modern {
                background: #fff;
                border: 1px solid var(--border-light);
                border-radius: var(--radius);
                box-shadow: var(--shadow-subtle);
                padding: 22px
            }

            .action {
                display: inline-flex;
                align-items: center;
                gap: 8px;
                background: linear-gradient(135deg, #6E41C1 0%, #8B5FD6 100%);
                color: #fff;
                border: none;
                border-radius: 10px;
                padding: 10px 16px;
                font-weight: 600
            }
        </style>

        <div class="erp-container">
            <aside class="erp-sidebar">
                <div class="header">FaCiencia</div>
                <div class="nav-section">Navegação</div>
                <a href="<?php echo APP_URL; ?>/parceiro/dashboard-parceiro.php"><span
                        class="icon">dashboard</span>Dashboard</a>
                <div class="nav-section">Acadêmico</div>
                <a href="<?php echo APP_URL; ?>/parceiro/cursos-parceiro.php"><span class="icon">school</span>Cursos</a>
                <a href="<?php echo APP_URL; ?>/parceiro/alunos-parceiro.php"><span class="icon">group</span>Alunos</a>
                <div class="nav-section">Certificação</div>
                <a href="<?php echo APP_URL; ?>/parceiro/templates-parceiro.php"><span
                        class="icon">article</span>Templates</a>
                <a href="<?php echo APP_URL; ?>/parceiro/gerar-certificados.php"><span
                        class="icon">workspace_premium</span>Emitir Cert.</a>
                <div class="nav-section">Minha Conta</div>
                <a href="<?php echo APP_URL; ?>/parceiro/financeiro.php"><span
                        class="icon">credit_card</span>Financeiro</a>
                <a href="<?php echo APP_URL; ?>/parceiro/meu-plano.php" class="active"><span
                        class="icon">price_check</span>Meu Plano</a>
            </aside>
            <div class="erp-main">
                <div class="erp-top">
                    <div style="display:flex;align-items:center;gap:10px;color:var(--text-medium)"><span
                            class="material-icons-outlined">account_circle</span><?php echo htmlspecialchars($parceiro['nome_empresa'] ?? ($parceiro['email'] ?? 'Parceiro')); ?>
                    </div>
                </div>
                <div class="erp-content">
                    <div class="card-modern"
                        style="margin-bottom:16px;display:flex;justify-content:space-between;align-items:center">
                        <div>
                            <div style="font-size:22px;font-weight:700">Escolher forma de pagamento</div>
                            <div style="color:var(--text-medium);margin-top:4px">Plano:
                                <?php echo htmlspecialchars($assinatura['plano_nome']); ?> • Valor: R$
                                <?php echo number_format($assinatura['valor'], 2, ',', '.'); ?> • Venc.:
                                <?php echo date('d/m/Y', strtotime($assinatura['data_vencimento'])); ?>
                            </div>
                        </div>
                        <a href="<?php echo APP_URL; ?>/parceiro/financeiro.php" class="action"><span
                                class="material-icons-outlined">receipt_long</span>Ver Financeiro</a>
                    </div>

                    <?php if (!empty($_SESSION['erro'])): ?>
                        <div class="alert alert-danger" role="alert" style="margin-bottom:16px">
                            <?php echo htmlspecialchars($_SESSION['erro']);
                            unset($_SESSION['erro']); ?>
                        </div>
                    <?php endif; ?>
                    <?php if (!empty($_SESSION['sucesso'])): ?>
                        <div class="alert alert-success" role="alert" style="margin-bottom:16px">
                            <?php echo htmlspecialchars($_SESSION['sucesso']);
                            unset($_SESSION['sucesso']); ?>
                        </div>
                    <?php endif; ?>

                    <div style="display:grid;grid-template-columns:repeat(3,minmax(240px,1fr));gap:20px">
                        <div class="card-modern" style="text-align:center">
                            <div class="material-icons-outlined" style="font-size:40px;color:#f4a100">barcode</div>
                            <div style="font-weight:700;margin-top:8px">Boleto Bancário</div>
                            <div style="color:var(--text-medium);font-size:14px;margin-top:6px">Confirmação em até 3
                                dias úteis</div>
                            <a class="action btn-selecionar" data-forma="boleto"
                                href="processar-pagamento.php?forma=boleto" style="margin-top:14px"><span
                                    class="material-icons-outlined">check_circle</span>Selecionar</a>
                        </div>
                        <div class="card-modern" style="text-align:center;border:2px solid #34C759">
                            <div class="material-icons-outlined" style="font-size:40px;color:#34C759">qr_code_2</div>
                            <div style="font-weight:700;margin-top:8px">PIX <span
                                    style="font-size:12px;background:#34C759;color:#fff;padding:2px 6px;border-radius:6px;vertical-align:middle">RECOMENDADO</span>
                            </div>
                            <div style="color:var(--text-medium);font-size:14px;margin-top:6px">Confirmação instantânea
                            </div>
                            <a class="action btn-selecionar" data-forma="pix" href="processar-pagamento.php?forma=pix"
                                style="margin-top:14px"><span
                                    class="material-icons-outlined">payments</span>Selecionar</a>
                        </div>
                        <div class="card-modern" style="text-align:center">
                            <div class="material-icons-outlined" style="font-size:40px;color:#6E41C1">credit_card</div>
                            <div style="font-weight:700;margin-top:8px">Cartão de Crédito</div>
                            <div style="color:var(--text-medium);font-size:14px;margin-top:6px">Até 12x (no Asaas)</div>
                            <a class="action btn-selecionar" data-forma="cartao_credito"
                                href="processar-pagamento.php?forma=cartao_credito" style="margin-top:14px"><span
                                    class="material-icons-outlined">check_circle</span>Selecionar</a>
                        </div>
                    </div>

                </div>
            </div>
        </div>


        <div class="container-fluid legacy-content" style="display:none">
            <div class="row">
                <div class="col-12">
                    <h1 class="h3 mb-4 text-gray-800">
                        <i class="fas fa-credit-card"></i> Escolher Forma de Pagamento
                    </h1>
                </div>
            </div>

            <!-- Informações do Plano -->
            <div class="row mb-4">
                <div class="col-md-12">
                    <div class="card shadow">
                        <div class="card-header py-3">
                            <h6 class="m-0 font-weight-bold text-primary">Seu Plano Atual</h6>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-4">
                                    <strong>Plano:</strong> <?php echo htmlspecialchars($assinatura['plano_nome']); ?>
                                </div>
                                <div class="col-md-4">
                                    <strong>Valor Mensal:</strong> R$
                                    <?php echo number_format($assinatura['valor'], 2, ',', '.'); ?>
                                </div>
                                <div class="col-md-4">
                                    <strong>Vencimento:</strong>
                                    <?php echo date('d/m/Y', strtotime($assinatura['data_vencimento'])); ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Formas de Pagamento -->
            <div class="row">
                <!-- BOLETO -->
                <div class="col-md-4 mb-4">
                    <div class="card shadow h-100 forma-pagamento-card" data-forma="boleto">
                        <div class="card-body text-center">
                            <div class="icon-circle bg-warning text-white mb-3">
                                <i class="fas fa-barcode fa-3x"></i>
                            </div>
                            <h5 class="card-title">Boleto Bancário</h5>
                            <p class="text-muted">Confirmação em até 3 dias úteis</p>

                            <ul class="text-left mt-3">
                                <li>✓ Pague em qualquer banco</li>
                                <li>✓ Pague em lotéricas</li>
                                <li>✓ Sem taxas adicionais</li>
                                <li>⚠ Confirmação em 1-3 dias</li>
                            </ul>

                            <button class="btn btn-warning btn-block mt-3 btn-selecionar" data-forma="boleto">
                                <i class="fas fa-check"></i> Selecionar Boleto
                            </button>
                        </div>
                    </div>
                </div>

                <!-- PIX -->
                <div class="col-md-4 mb-4">
                    <div class="card shadow h-100 forma-pagamento-card border-success" data-forma="pix">
                        <div class="card-body text-center">
                            <div class="icon-circle bg-success text-white mb-3">
                                <i class="fas fa-qrcode fa-3x"></i>
                            </div>
                            <h5 class="card-title">PIX <span class="badge badge-success">RECOMENDADO</span></h5>
                            <p class="text-muted">Confirmação INSTANTÂNEA</p>

                            <ul class="text-left mt-3">
                                <li>✓ Confirmação instantânea</li>
                                <li>✓ Disponível 24/7</li>
                                <li>✓ Sem taxas adicionais</li>
                                <li>✓ QR Code ou Copia e Cola</li>
                            </ul>

                            <button class="btn btn-success btn-block mt-3 btn-selecionar" data-forma="pix">
                                <i class="fas fa-check"></i> Selecionar PIX
                            </button>
                        </div>
                    </div>
                </div>

                <!-- CARTÃO DE CRÉDITO -->
                <div class="col-md-4 mb-4">
                    <div class="card shadow h-100 forma-pagamento-card" data-forma="cartao_credito">
                        <div class="card-body text-center">
                            <div class="icon-circle bg-primary text-white mb-3">
                                <i class="fas fa-credit-card fa-3x"></i>
                            </div>
                            <h5 class="card-title">Cartão de Crédito</h5>
                            <p class="text-muted">Renovação automática</p>

                            <ul class="text-left mt-3">
                                <li>✓ Confirmação instantânea</li>
                                <li>✓ Renovação automática</li>
                                <li>✓ Sem preocupação</li>
                                <li>✓ Parcelamento disponível</li>
                            </ul>

                            <button class="btn btn-primary btn-block mt-3 btn-selecionar" data-forma="cartao_credito">
                                <i class="fas fa-check"></i> Selecionar Cartão
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Informações Adicionais -->
            <div class="row mt-4">
                <div class="col-md-12">
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle"></i>
                        <strong>Informação:</strong> Você pode alterar a forma de pagamento a qualquer momento.
                        A mudança será aplicada na próxima renovação.
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal de Confirmação -->
        <div class="modal fade" id="modalConfirmacao" tabindex="-1" role="dialog">
            <div class="modal-dialog" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Confirmar Forma de Pagamento</h5>
                        <button type="button" class="close" data-dismiss="modal">
                            <span>&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <p>Você selecionou: <strong id="forma-selecionada"></strong></p>
                        <p>Esta será sua forma de pagamento para as próximas renovações.</p>
                        <p class="text-muted">Deseja continuar?</p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                        <button type="button" class="btn btn-primary" id="btn-confirmar">Confirmar</button>
                    </div>
                </div>
            </div>
        </div>

        <style>
            .forma-pagamento-card {
                cursor: pointer;
                transition: all 0.3s;
            }

            .forma-pagamento-card:hover {
                transform: translateY(-5px);
                box-shadow: 0 10px 20px rgba(0, 0, 0, 0.2) !important;
            }

            .icon-circle {
                width: 100px;
                height: 100px;
                border-radius: 50%;
                display: flex;
                align-items: center;
                justify-content: center;
                margin: 0 auto;
            }
        </style>

        <script>
            document.addEventListener('DOMContentLoaded', function () {
                var botoes = document.querySelectorAll('.btn-selecionar');
                botoes.forEach(function (btn) {
                    btn.addEventListener('click', function (e) {
                        e.preventDefault();
                        var formaSelecionada = this.getAttribute('data-forma');
                        var nomes = { boleto: 'Boleto Bancário', pix: 'PIX', cartao_credito: 'Cartão de Crédito' };
                        var nomeForma = nomes[formaSelecionada] || formaSelecionada;
                        if (!confirm('Confirmar forma de pagamento: ' + nomeForma + '?')) {
                            return;
                        }
                        var destino = this.getAttribute('href') || ('processar-pagamento.php?forma=' + encodeURIComponent(formaSelecionada));
                        var formData = new URLSearchParams();
                        formData.append('assinatura_id', <?php echo (int) $assinatura['id']; ?>);
                        formData.append('forma_pagamento', formaSelecionada);
                        fetch('../app/actions/salvar-forma-pagamento.php', {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' },
                            body: formData.toString()
                        })
                            .then(function (r) { return r.json(); })
                            .then(function (response) {
                                if (response && response.success) {
                                    window.location.href = destino;
                                } else {
                                    alert('Aviso: preferência não foi salva. Prosseguindo com o pagamento...');
                                    window.location.href = destino;
                                }
                            })
                            .catch(function () {
                                // Falha de rede/JS: seguir para o pagamento mesmo assim
                                window.location.href = destino;
                            });
                    });
                });
            });
        </script>

        <?php require_once '../app/views/footer.php'; ?>