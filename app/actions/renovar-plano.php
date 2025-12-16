<?php
/**
 * ============================================================================
 * RENOVAR PLANO - SISTEMA DE CERTIFICADOS
 * ============================================================================
 * Esta ação processa a renovação do plano do parceiro
 * Gera: Contrato, Fatura, Boleto e Receita
 * ============================================================================
 */

require_once __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/../lib/AsaasAPI.php';

// Requer login como parceiro
if (!isAuthenticated() || !hasRole(ROLE_PARCEIRO)) {
    $_SESSION['error'] = 'Acesso negado. Faça login como parceiro.';
    redirect(APP_URL . '/login.php');
}

// Somente POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $_SESSION['error'] = 'Método não permitido';
    redirect(APP_URL . '/parceiro/meu-plano.php');
}

try {
    $conn = getDBConnection();
    $user = getCurrentUser();
    $parceiro_id = $user['parceiro_id'] ?? $user['id'];

    // ========================================================================
    // BUSCAR ASSINATURA ATIVA
    // ========================================================================
    $stmt = $conn->prepare("
        SELECT a.id, a.plano_id, a.data_vencimento, pl.quantidade_certificados, pl.valor, pl.nome as plano_nome
        FROM assinaturas a
        JOIN planos pl ON a.plano_id = pl.id
        WHERE a.parceiro_id = ? AND a.status = 'ativa'
        LIMIT 1
    ");

    if (!$stmt) {
        $_SESSION['error'] = 'Erro ao buscar assinatura.';
        $conn->close();
        redirect(APP_URL . '/parceiro/meu-plano.php');
    }

    $stmt->bind_param('i', $parceiro_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        $_SESSION['error'] = 'Você não possui uma assinatura ativa.';
        $stmt->close();
        $conn->close();
        redirect(APP_URL . '/parceiro/meu-plano.php');
    }

    $assinatura = $result->fetch_assoc();
    $stmt->close();

    // ========================================================================
    // CALCULAR NOVAS DATAS
    // ========================================================================
    $data_vencimento_atual = new DateTime($assinatura['data_vencimento']);
    $data_vencimento_nova = $data_vencimento_atual->modify('+30 days')->format('Y-m-d');
    $data_inicio = date('Y-m-d');
    $quantidade_certificados = $assinatura['quantidade_certificados'];
    $certificados_disponiveis = $quantidade_certificados;
    $certificados_usados = 0;
    $valor = $assinatura['valor'];

    // ========================================================================
    // GERAR NÚMERO DO CONTRATO
    // ========================================================================
    $numero_contrato = 'CONT-' . $parceiro_id . '-' . date('YmdHis');

    // ========================================================================
    // CRIAR CONTRATO DE RENOVAÇÃO (opcional: somente se a tabela existir)
    // ========================================================================
    $contrato_id = null;
    $tem_contratos = false;

    if ($check = $conn->query("SHOW TABLES LIKE 'contratos'")) {
        $tem_contratos = ($check->num_rows > 0);
        $check->close();
    }

    if ($tem_contratos) {
        $stmt = $conn->prepare("
            INSERT INTO contratos (parceiro_id, assinatura_id, tipo, numero_contrato, plano_id, valor_mensal, data_inicio, status, criado_em)
            VALUES (?, ?, 'renovacao', ?, ?, ?, ?, 'pendente_assinatura', NOW())
        ");

        if ($stmt) {
            $stmt->bind_param('iisids', $parceiro_id, $assinatura['id'], $numero_contrato, $assinatura['plano_id'], $valor, $data_inicio);
            if ($stmt->execute()) {
                $contrato_id = $conn->insert_id;
            } else {
                // Não bloquear a renovação se o módulo de contratos falhar
                error_log('Falha ao criar contrato (opcional): ' . $stmt->error);
            }
            $stmt->close();
        } else {
            error_log('Falha ao preparar inserção de contrato (opcional): ' . $conn->error);
        }
    }

    // ========================================================================
    // GERAR NÚMERO DA FATURA
    // ========================================================================
    $numero_fatura = 'FAT-' . $parceiro_id . '-' . date('YmdHis');
    $data_vencimento_fatura = date('Y-m-d', strtotime('+7 days'));

    // ========================================================================
    // CRIAR FATURA
    // ========================================================================
    $stmt = $conn->prepare("
        INSERT INTO faturas (parceiro_id, assinatura_id, numero_fatura, descricao, valor, data_emissao, data_vencimento, status, criado_em)
        VALUES (?, ?, ?, ?, ?, ?, ?, 'pendente', NOW())
    ");

    if ($stmt) {
        $descricao = 'Renovação - Plano ' . $assinatura['plano_nome'];
        $stmt->bind_param('iisssss', $parceiro_id, $assinatura['id'], $numero_fatura, $descricao, $valor, $data_inicio, $data_vencimento_fatura);
        $stmt->execute();
        $fatura_id = $conn->insert_id;
        $stmt->close();
    }

    // ========================================================================
    // GERAR COBRANÇA AUTOMÁTICA (BOLETO) NO ASAAS E SALVAR
    // ========================================================================
    $cobranca_id = null;
    $asaas = new AsaasAPI($conn);
    if ($asaas->isConfigured()) {
        // Buscar dados do parceiro
        $stmt = $conn->prepare("SELECT nome_empresa, cnpj, email, telefone, endereco, cidade, estado, cep FROM parceiros WHERE id = ? LIMIT 1");
        $stmt->bind_param('i', $parceiro_id);
        $stmt->execute();
        $parc_res = $stmt->get_result();
        $parceiroRow = $parc_res->fetch_assoc();
        $stmt->close();

        if ($parceiroRow) {
            $dados_boleto = [
                'nome_cliente' => $parceiroRow['nome_empresa'],
                'email_cliente' => $parceiroRow['email'],
                'cpf_cnpj' => $parceiroRow['cnpj'],
                'telefone' => $parceiroRow['telefone'],
                'endereco' => $parceiroRow['endereco'],
                'cidade' => $parceiroRow['cidade'],
                'estado' => $parceiroRow['estado'],
                'cep' => $parceiroRow['cep'],
                'valor' => $valor,
                'data_vencimento' => $data_vencimento_fatura,
                'descricao' => $descricao,
                'referencia_externa' => 'FAT-' . $fatura_id
            ];

            $res_cobranca = $asaas->criarBoleto($dados_boleto);
            if ($res_cobranca['success']) {
                $d = $res_cobranca['data'];
                $asaas_id = $d['id'] ?? null;
                $pdf_url = $d['bankSlipUrl'] ?? ($d['invoiceUrl'] ?? ($d['bankSlip']['url'] ?? null));
                $linha_digitavel = $d['identificationField'] ?? ($d['bankSlip']['barCode'] ?? ($d['barCode'] ?? null));
                $invoice_url = $d['invoiceUrl'] ?? null;

                // Inserir em asaas_cobrancas
                $stmt = $conn->prepare("INSERT INTO asaas_cobrancas (fatura_id, assinatura_id, parceiro_id, asaas_id, valor, data_vencimento, status, tipo_cobranca, pdf_url, linha_digitavel, invoice_url, observacoes, criado_em) VALUES (?, ?, ?, ?, ?, ?, 'pendente', 'boleto', ?, ?, ?, ?, NOW())");
                $stmt->bind_param('iiisdssss', $fatura_id, $assinatura['id'], $parceiro_id, $asaas_id, $valor, $data_vencimento_fatura, $pdf_url, $linha_digitavel, $invoice_url, $descricao);
                if ($stmt->execute()) { $cobranca_id = $conn->insert_id; }
                $stmt->close();

                // Inserir também em asaas_boletos (compatibilidade com telas antigas)
                $stmt = $conn->prepare("INSERT INTO asaas_boletos (fatura_id, parceiro_id, asaas_id, valor, data_vencimento, status, url_boleto, linha_digitavel, descricao, criado_em) VALUES (?, ?, ?, ?, ?, 'pendente', ?, ?, ?, NOW())");
                $stmt->bind_param('iisdsss', $fatura_id, $parceiro_id, $asaas_id, $valor, $data_vencimento_fatura, $pdf_url, $linha_digitavel, $descricao);
                $stmt->execute();
                $stmt->close();


                // Atualizar fatura com asaas_id e forma_pagamento
                if (!empty($asaas_id)) {
                    $stmt = $conn->prepare("UPDATE faturas SET asaas_id = ?, forma_pagamento = 'boleto' WHERE id = ?");
                    $stmt->bind_param('si', $asaas_id, $fatura_id);
                    $stmt->execute();
                    $stmt->close();
                }
            } else {
                error_log('Falha ao gerar boleto Asaas na renovação: ' . ($res_cobranca['error'] ?? 'sem detalhe'));
            }
        }
    }

    // ========================================================================
    // CRIAR RECEITA (RENOVAÇÃO)
    // ========================================================================
    $stmt = $conn->prepare("
        INSERT INTO receitas (parceiro_id, assinatura_id, tipo, valor, data_receita, status, criado_em)
        VALUES (?, ?, 'renovacao', ?, ?, 'pendente', NOW())
    ");

    if ($stmt) {
        $data_receita = date('Y-m-d');
        $stmt->bind_param('iids', $parceiro_id, $assinatura['id'], $valor, $data_receita);
        $stmt->execute();
        $stmt->close();
    }

    // ========================================================================
    // ATUALIZAR ASSINATURA
    // ========================================================================
    $stmt = $conn->prepare("
        UPDATE assinaturas
        SET data_vencimento = ?, certificados_totais = ?, certificados_usados = ?, certificados_disponiveis = ?, status = 'ativa', atualizado_em = NOW()
        WHERE id = ? AND parceiro_id = ?
    ");

    if (!$stmt) {
        $_SESSION['error'] = 'Erro ao atualizar assinatura.';
        $conn->close();
        redirect(APP_URL . '/parceiro/meu-plano.php');
    }

    $stmt->bind_param('siiiis', $data_vencimento_nova, $quantidade_certificados, $certificados_usados, $certificados_disponiveis, $assinatura['id'], $parceiro_id);

    if (!$stmt->execute()) {
        $_SESSION['error'] = 'Erro ao renovar plano: ' . $stmt->error;
        $stmt->close();
        $conn->close();
        redirect(APP_URL . '/parceiro/meu-plano.php');
    }

    $stmt->close();

    // ========================================================================
    // CRIAR SOLICITAÇÃO DE RENOVAÇÃO (PARA AUDITORIA)
    // ========================================================================
    $stmt = $conn->prepare("
        INSERT INTO solicitacoes_planos (parceiro_id, plano_novo_id, status, tipo, criado_em)
        VALUES (?, ?, 'aprovada', 'renovacao', NOW())
    ");

    if ($stmt) {
        $stmt->bind_param('ii', $parceiro_id, $assinatura['plano_id']);
        $stmt->execute();
        $stmt->close();
    }

    $conn->close();

    $msg = $contrato_id
        ? 'Plano renovado com sucesso! Contrato e fatura foram gerados. Você precisa assinar o contrato para confirmar a renovação.'
        : 'Plano renovado com sucesso! Fatura e boleto foram gerados.';
    $_SESSION['success'] = $msg;
    if ($contrato_id) { $_SESSION['contrato_id'] = $contrato_id; }
    $_SESSION['fatura_id'] = $fatura_id ?? null;
    $_SESSION['cobranca_id'] = $cobranca_id ?? null;

} catch (Exception $e) {
    error_log('Erro ao renovar plano: ' . $e->getMessage());
    $_SESSION['error'] = 'Erro ao renovar plano: ' . $e->getMessage();
}

// Redirecionamento pós-renovação
if (!empty($contrato_id)) {
    redirect(APP_URL . '/parceiro/contratos.php');
} elseif (!empty($cobranca_id)) {
    redirect(APP_URL . '/parceiro/visualizar-cobranca.php?id=' . $cobranca_id);
} else {
    // Caso não tenha sido possível gerar boleto automaticamente, permitir escolher forma de pagamento
    redirect(APP_URL . '/parceiro/escolher-pagamento.php');
}
?>

