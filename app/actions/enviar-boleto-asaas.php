<?php
/**
 * Enviar Boleto para Asaas - Sistema de Certificados
 * Envia boleto local para Asaas
 */

require_once '../config/config.php';

if (!isAuthenticated() || !hasRole(ROLE_ADMIN)) {
    http_response_code(403);
    die('Acesso negado');
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    die('Método não permitido');
}

$conn = getDBConnection();

// Validar dados
$boleto_id = isset($_POST['boleto_id']) ? (int)$_POST['boleto_id'] : 0;

if (empty($boleto_id)) {
    $_SESSION['error'] = 'Boleto não encontrado';
    redirect(APP_URL . '/admin/boletos-asaas.php');
}

// Buscar boleto
$stmt = $conn->prepare("
    SELECT b.id, b.fatura_id, b.parceiro_id, b.valor, b.data_vencimento, b.descricao,
           p.nome_empresa, p.email, p.cnpj
    FROM asaas_boletos b
    JOIN parceiros p ON b.parceiro_id = p.id
    WHERE b.id = ?
");
$stmt->bind_param("i", $boleto_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    $_SESSION['error'] = 'Boleto não encontrado';
    $stmt->close();
    $conn->close();
    redirect(APP_URL . '/admin/boletos-asaas.php');
}

$boleto = $result->fetch_assoc();
$stmt->close();

// Verificar se Asaas está configurado
$stmt = $conn->prepare("SELECT api_key, wallet_id, ambiente, ativo FROM asaas_config WHERE ativo = 1 LIMIT 1");
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    $_SESSION['error'] = 'Asaas não está configurado. Configure em: Integrações > Configurar Asaas';
    $stmt->close();
    $conn->close();
    redirect(APP_URL . '/admin/boletos-asaas.php');
}

$config = $result->fetch_assoc();
$stmt->close();

// Tentar enviar para Asaas
require_once '../lib/AsaasAPI.php';

$asaas = new AsaasAPI($conn);

// Preparar dados do boleto
$dados_boleto = [
    'nome_cliente' => $boleto['nome_empresa'],
    'email_cliente' => $boleto['email'],
    'cpf_cnpj' => $boleto['cnpj'],
    'valor' => $boleto['valor'],
    'data_vencimento' => $boleto['data_vencimento'],
    'descricao' => $boleto['descricao'],
    'referencia_externa' => 'FAT-' . $boleto['fatura_id']
];

// Chamar API Asaas
$resultado = $asaas->criarBoleto($dados_boleto);

if ($resultado['success'] && isset($resultado['data']['id'])) {
    // Atualizar boleto com dados do Asaas
    $dados_asaas = $resultado['data'];
    $asaas_id = $dados_asaas['id'];
    $numero_boleto = $dados_asaas['invoiceNumber'] ?? ($dados_asaas['nossoNumero'] ?? '');
    $url_boleto = $dados_asaas['bankSlipUrl'] ?? ($dados_asaas['invoiceUrl'] ?? ($dados_asaas['bankSlip']['url'] ?? ''));
    $linha_digitavel = $dados_asaas['identificationField'] ?? ($dados_asaas['bankSlip']['barCode'] ?? ($dados_asaas['barCode'] ?? ''));
    $status_boleto = 'pendente';

    $stmt = $conn->prepare("
        UPDATE asaas_boletos
        SET asaas_id = ?, numero_boleto = ?, url_boleto = ?, linha_digitavel = ?, status = ?
        WHERE id = ?
    ");

    if ($stmt) {
        $stmt->bind_param("sssssi", $asaas_id, $numero_boleto, $url_boleto, $linha_digitavel, $status_boleto, $boleto_id);

        if ($stmt->execute()) {
            // Atualizar fatura com asaas_id
            $stmt_fatura = $conn->prepare("UPDATE faturas SET asaas_id = ? WHERE id = ?");
            if ($stmt_fatura) {
                $stmt_fatura->bind_param("si", $asaas_id, $boleto['fatura_id']);
                $stmt_fatura->execute();
                $stmt_fatura->close();
            }

            $_SESSION['success'] = 'Boleto enviado para Asaas com sucesso! URL: ' . $url_boleto;
        } else {
            $_SESSION['error'] = 'Erro ao atualizar boleto: ' . $stmt->error;
        }
        $stmt->close();
    }
} else {
    $erro_msg = $resultado['error'] ?? 'Erro desconhecido ao enviar boleto para Asaas';
    $_SESSION['error'] = 'Erro ao enviar boleto para Asaas: ' . $erro_msg;
}

$conn->close();
redirect(APP_URL . '/admin/boletos-asaas.php');
?>

