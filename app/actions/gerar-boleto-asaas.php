<?php
/**
 * Gerar Boleto Asaas - Sistema de Certificados
 */

require_once __DIR__ . '/bootstrap.php';
require_once '../lib/AsaasAPI.php';

if (!isAuthenticated() || !hasRole(ROLE_ADMIN)) {
    $_SESSION['error'] = 'Acesso negado';
    redirect(APP_URL . '/login.php');
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $_SESSION['error'] = 'Método não permitido';
    redirect(APP_URL . '/admin/faturas-admin.php');
}

$conn = getDBConnection();
$asaas = new AsaasAPI($conn);

if (!$asaas->isConfigured()) {
    $_SESSION['error'] = 'Asaas não está configurado';
    redirect(APP_URL . '/admin/faturas-admin.php');
}

$fatura_id = isset($_POST['fatura_id']) ? (int)$_POST['fatura_id'] : 0;

if (empty($fatura_id)) {
    $_SESSION['error'] = 'Fatura não especificada';
    redirect(APP_URL . '/admin/faturas-admin.php');
}

// Buscar fatura
$stmt = $conn->prepare("
    SELECT f.*, p.nome_empresa, p.cnpj, p.email, p.telefone, p.endereco, p.cidade, p.estado, p.cep
    FROM faturas f
    JOIN parceiros p ON f.parceiro_id = p.id
    WHERE f.id = ?
");
$stmt->bind_param("i", $fatura_id);
$stmt->execute();
$resultado = $stmt->get_result();

if ($resultado->num_rows === 0) {
    $_SESSION['error'] = 'Fatura não encontrada';
    redirect(APP_URL . '/admin/faturas-admin.php');
}

$fatura = $resultado->fetch_assoc();

// Preparar dados para Asaas
$dados_boleto = [
    'nome_cliente' => $fatura['nome_empresa'],
    'email_cliente' => $fatura['email'],
    'cpf_cnpj' => $fatura['cnpj'],
    'telefone' => $fatura['telefone'],
    'endereco' => $fatura['endereco'],
    'cidade' => $fatura['cidade'],
    'estado' => $fatura['estado'],
    'cep' => $fatura['cep'],
    'valor' => $fatura['valor'],
    'data_vencimento' => $fatura['data_vencimento'],
    'descricao' => $fatura['descricao'] ?? 'Fatura ' . $fatura['numero_fatura'],
    'referencia_externa' => 'FAT-' . $fatura['id']
];

// Criar boleto no Asaas
$resposta = $asaas->criarBoleto($dados_boleto);

if (!$resposta['success']) {
    $_SESSION['error'] = 'Erro ao gerar boleto: ' . $resposta['error'];
    redirect(APP_URL . '/admin/faturas-admin.php');
}

$dados_asaas = $resposta['data'];
$asaas_id = $dados_asaas['id'];
// URLs e linha digitável variam conforme a versão da API; usar fallbacks
$url_boleto = $dados_asaas['bankSlipUrl'] ?? ($dados_asaas['invoiceUrl'] ?? ($dados_asaas['bankSlip']['url'] ?? ''));
$linha_digitavel = $dados_asaas['identificationField'] ?? ($dados_asaas['bankSlip']['barCode'] ?? ($dados_asaas['barCode'] ?? ''));

// Salvar boleto no banco
$stmt = $conn->prepare("
    INSERT INTO asaas_boletos (fatura_id, parceiro_id, asaas_id, valor, data_vencimento, status, url_boleto, linha_digitavel, descricao, criado_em)
    VALUES (?, ?, ?, ?, ?, 'pendente', ?, ?, ?, NOW())
");
$stmt->bind_param("iisdsss", $fatura_id, $fatura['parceiro_id'], $asaas_id, $fatura['valor'], $fatura['data_vencimento'], $url_boleto, $linha_digitavel, $dados_boleto['descricao']);

if ($stmt->execute()) {
    // Atualizar fatura com asaas_id
    $stmt = $conn->prepare("UPDATE faturas SET asaas_id = ? WHERE id = ?");
    $stmt->bind_param("si", $asaas_id, $fatura_id);
    $stmt->execute();

    $_SESSION['success'] = 'Boleto gerado com sucesso! URL: ' . $url_boleto;
} else {
    $_SESSION['error'] = 'Erro ao salvar boleto: ' . $conn->error;
}

$conn->close();
redirect(APP_URL . '/admin/faturas-admin.php');
?>

