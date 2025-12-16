<?php
/**
 * Gerar Boleto Local - Sistema de Certificados
 * Gera boleto localmente sem enviar para Asaas
 */

require_once '../config/config.php';

if (!isAuthenticated() || !hasRole(ROLE_ADMIN)) {
    $_SESSION['error'] = 'Acesso negado';
    redirect(APP_URL . '/login.php');
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $_SESSION['error'] = 'Método não permitido';
    redirect(APP_URL . '/admin/faturas-admin.php');
}

$conn = getDBConnection();

// Validar dados
$fatura_id = isset($_POST['fatura_id']) ? (int)$_POST['fatura_id'] : 0;

if (empty($fatura_id)) {
    $_SESSION['error'] = 'Fatura não encontrada';
    redirect(APP_URL . '/admin/faturas-admin.php');
}

// Buscar fatura
$stmt = $conn->prepare("
    SELECT f.id, f.parceiro_id, f.numero_fatura, f.valor, f.data_vencimento, f.descricao,
           p.nome_empresa, p.email, p.cnpj
    FROM faturas f
    JOIN parceiros p ON f.parceiro_id = p.id
    WHERE f.id = ?
");
$stmt->bind_param("i", $fatura_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    $_SESSION['error'] = 'Fatura não encontrada';
    $stmt->close();
    $conn->close();
    redirect(APP_URL . '/admin/faturas-admin.php');
}

$fatura = $result->fetch_assoc();
$stmt->close();

// Verificar se boleto já existe
$stmt = $conn->prepare("SELECT id FROM asaas_boletos WHERE fatura_id = ?");
$stmt->bind_param("i", $fatura_id);
$stmt->execute();
if ($stmt->get_result()->num_rows > 0) {
    $_SESSION['error'] = 'Boleto já foi gerado para esta fatura';
    $stmt->close();
    $conn->close();
    redirect(APP_URL . '/admin/faturas-admin.php');
}
$stmt->close();

// Gerar dados do boleto localmente
$numero_boleto = 'BOL-' . $fatura['parceiro_id'] . '-' . date('YmdHis');
$asaas_id = 'LOCAL-' . $fatura_id . '-' . date('YmdHis');
$linha_digitavel = gerarLinhaDigitavel($fatura['valor'], $fatura['data_vencimento']);
$url_boleto = '#'; // Será preenchido quando enviar para Asaas
$status_boleto = 'pendente'; // Status pendente (será enviado para Asaas)

// Inserir boleto local
$stmt = $conn->prepare("
    INSERT INTO asaas_boletos (fatura_id, parceiro_id, asaas_id, numero_boleto, valor, data_vencimento, status, url_boleto, linha_digitavel, descricao)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
");

if ($stmt) {
    $stmt->bind_param("iissdsssss", $fatura_id, $fatura['parceiro_id'], $asaas_id, $numero_boleto, $fatura['valor'], $fatura['data_vencimento'], $status_boleto, $url_boleto, $linha_digitavel, $fatura['descricao']);

    if ($stmt->execute()) {
        $_SESSION['success'] = 'Boleto gerado localmente com sucesso! Clique em "Enviar para Asaas" para enviar.';
    } else {
        $_SESSION['error'] = 'Erro ao gerar boleto: ' . $stmt->error;
    }
    $stmt->close();
}

$conn->close();
redirect(APP_URL . '/admin/faturas-admin.php');

/**
 * Gerar linha digitável simulada
 */
function gerarLinhaDigitavel($valor, $data_vencimento) {
    $banco = '001'; // Banco do Brasil
    $agencia = '0001';
    $conta = '000001';
    $dv = rand(0, 9);
    $numero_sequencial = str_pad(rand(1, 999999), 6, '0', STR_PAD_LEFT);
    
    // Formato simplificado
    $linha = $banco . '.' . $agencia . ' ' . $conta . '.' . $dv . ' ' . $numero_sequencial . ' ' . str_pad((int)($valor * 100), 10, '0', STR_PAD_LEFT);
    
    return $linha;
}
?>

