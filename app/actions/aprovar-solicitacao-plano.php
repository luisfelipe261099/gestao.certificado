<?php
/**
 * Aprovar Solicitação de Plano - Admin
 */
require_once __DIR__ . '/bootstrap.php';

if (!isAuthenticated() || !hasRole(ROLE_ADMIN)) {
    $_SESSION['error'] = 'Acesso negado';
    redirect(APP_URL . '/login.php');
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $_SESSION['error'] = 'Método não permitido';
    redirect(APP_URL . '/admin/solicitacoes-planos.php');
}

$id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
if ($id <= 0) { redirect(APP_URL . '/admin/solicitacoes-planos.php'); }

try {
    $conn = getDBConnection();
    $admin = getCurrentUser();
    $aprovado_por = $admin['id'];

    // Buscar solicitação pendente
    $stmt = $conn->prepare('SELECT s.id, s.parceiro_id, s.plano_novo_id, s.status, p.nome_empresa, pl.nome, pl.quantidade_certificados, pl.valor
                            FROM solicitacoes_planos s
                            JOIN parceiros p ON s.parceiro_id = p.id
                            JOIN planos pl ON s.plano_novo_id = pl.id
                            WHERE s.id = ? LIMIT 1');
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($res->num_rows === 0) {
        $_SESSION['error'] = 'Solicitação não encontrada';
        $stmt->close();
        $conn->close();
        redirect(APP_URL . '/admin/solicitacoes-planos.php');
    }
    $sol = $res->fetch_assoc();
    $stmt->close();

    if ($sol['status'] !== 'pendente') {
        $_SESSION['error'] = 'Solicitação já processada';
        $conn->close();
        redirect(APP_URL . '/admin/solicitacoes-planos.php');
    }

    $parceiro_id = (int)$sol['parceiro_id'];
    $plano_id = (int)$sol['plano_novo_id'];
    $qtd_certs = (int)$sol['quantidade_certificados'];

    // Verificar se existe assinatura ativa
    $assinatura = null;
    $stmt = $conn->prepare("SELECT id, certificados_usados FROM assinaturas WHERE parceiro_id = ? AND status = 'ativa' LIMIT 1");
    $stmt->bind_param('i', $parceiro_id);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($res->num_rows > 0) { $assinatura = $res->fetch_assoc(); }
    $stmt->close();

    if ($assinatura) {
        // Atualizar assinatura existente
        $usados = (int)$assinatura['certificados_usados'];
        $disp = max(0, $qtd_certs - $usados);
        $stmt = $conn->prepare('UPDATE assinaturas SET plano_id = ?, certificados_totais = ?, certificados_disponiveis = ?, atualizado_em = NOW() WHERE id = ?');
        $stmt->bind_param('iiii', $plano_id, $qtd_certs, $disp, $assinatura['id']);
        $stmt->execute();
        $stmt->close();
    } else {
        // Criar nova assinatura
        $data_inicio = date('Y-m-d');
        $data_venc = date('Y-m-d', strtotime('+30 days'));
        $status = 'ativa';
        $usados = 0;
        $disp = $qtd_certs;
        $renov = 1;
        $stmt = $conn->prepare('INSERT INTO assinaturas (parceiro_id, plano_id, data_inicio, data_vencimento, certificados_totais, certificados_usados, certificados_disponiveis, status, renovacao_automatica, criado_em) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())');
        $stmt->bind_param('iissiiisi', $parceiro_id, $plano_id, $data_inicio, $data_venc, $qtd_certs, $usados, $disp, $status, $renov);
        $stmt->execute();
        $stmt->close();
    }

    // ========================================================================
    // CRIAR CONTRATO DE MUDANÇA DE PLANO
    // ========================================================================
    $numero_contrato = 'CONT-' . $parceiro_id . '-' . date('YmdHis');
    $data_inicio_contrato = date('Y-m-d');
    $tipo_contrato = 'mudanca_plano';

    $stmt = $conn->prepare("
        INSERT INTO contratos (parceiro_id, solicitacao_plano_id, tipo, numero_contrato, plano_id, valor_mensal, data_inicio, status, criado_em)
        VALUES (?, ?, ?, ?, ?, ?, ?, 'pendente_assinatura', NOW())
    ");

    if ($stmt) {
        $stmt->bind_param('iisidi', $parceiro_id, $id, $tipo_contrato, $numero_contrato, $plano_id, $sol['valor'], $data_inicio_contrato);
        $stmt->execute();
        $contrato_id = $conn->insert_id;
        $stmt->close();
    }

    // ========================================================================
    // CRIAR FATURA (FINANCEIRO)
    // ========================================================================
    $numero_fatura = 'FAT-' . $parceiro_id . '-' . date('YmdHis');
    $descricao = 'Mudança de plano para ' . $sol['nome'];
    $valor = (float)$sol['valor'];
    $data_emissao = date('Y-m-d');
    $data_vencimento = date('Y-m-d', strtotime('+7 days'));
    $status_f = 'pendente';

    $stmt = $conn->prepare('INSERT INTO faturas (parceiro_id, numero_fatura, descricao, valor, data_emissao, data_vencimento, status, criado_em) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())');
    $stmt->bind_param('issdsss', $parceiro_id, $numero_fatura, $descricao, $valor, $data_emissao, $data_vencimento, $status_f);
    $stmt->execute();
    $fatura_id = $conn->insert_id;
    $stmt->close();

    // ========================================================================
    // CRIAR RECEITA
    // ========================================================================
    $tipo_receita = 'upgrade';
    $data_receita = date('Y-m-d');
    $status_receita = 'pendente';

    $stmt = $conn->prepare("
        INSERT INTO receitas (parceiro_id, tipo, valor, data_receita, status, criado_em)
        VALUES (?, ?, ?, ?, ?, NOW())
    ");

    if ($stmt) {
        $stmt->bind_param('isdss', $parceiro_id, $tipo_receita, $valor, $data_receita, $status_receita);
        $stmt->execute();
        $stmt->close();
    }

    // ========================================================================
    // ATUALIZAR SOLICITAÇÃO COMO APROVADA
    // ========================================================================
    $stmt = $conn->prepare("UPDATE solicitacoes_planos SET status = 'aprovada', fatura_id = ?, aprovado_por = ?, aprovado_em = NOW(), atualizado_em = NOW() WHERE id = ?");
    $stmt->bind_param('iii', $fatura_id, $aprovado_por, $id);
    $stmt->execute();
    $stmt->close();

    $_SESSION['success'] = 'Solicitação aprovada! Contrato, assinatura e fatura foram criados. O parceiro precisa assinar o contrato.';
    $conn->close();
} catch (Exception $e) {
    error_log('Erro ao aprovar solicitação de plano: ' . $e->getMessage());
    $_SESSION['error'] = 'Erro ao aprovar solicitação.';
}

redirect(APP_URL . '/admin/solicitacoes-planos.php');

