<?php

require_once '../config/config.php';

header('Content-Type: application/json');

// Verificar se parceiro está logado
if (!isset($_SESSION['parceiro_id'])) {
    echo json_encode(['success' => false, 'error' => 'Não autorizado']);
    exit;
}

$parceiro_id = $_SESSION['parceiro_id'];
$assinatura_id = $_POST['assinatura_id'] ?? 0;
$forma_pagamento = $_POST['forma_pagamento'] ?? '';

// Validar forma de pagamento
$formas_validas = ['boleto', 'pix', 'cartao_credito'];
if (!in_array($forma_pagamento, $formas_validas)) {
    echo json_encode(['success' => false, 'error' => 'Forma de pagamento inválida']);
    exit;
}

$conn = getDBConnection();

// Verificar se assinatura pertence ao parceiro
$stmt = $conn->prepare("SELECT id FROM assinaturas WHERE id = ? AND parceiro_id = ?");
$stmt->bind_param("ii", $assinatura_id, $parceiro_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode(['success' => false, 'error' => 'Assinatura não encontrada']);
    $stmt->close();
    $conn->close();
    exit;
}
$stmt->close();

// Atualizar forma de pagamento na assinatura SE a coluna existir (evita erro em bancos antigos)
$updated = true;
$erroUpdate = '';
$colRes = $conn->query("SHOW COLUMNS FROM assinaturas LIKE 'forma_pagamento'");
if ($colRes && $colRes->num_rows > 0) {
    $stmt = $conn->prepare("UPDATE assinaturas SET forma_pagamento = ?, atualizado_em = NOW() WHERE id = ?");
    if ($stmt) {
        $stmt->bind_param("si", $forma_pagamento, $assinatura_id);
        $updated = $stmt->execute();
        if (!$updated) { $erroUpdate = $stmt->error; }
        $stmt->close();
    } else {
        $updated = false;
        $erroUpdate = $conn->error;
    }
}

// Registrar preferência do parceiro (independente do update acima)
$stmt_pref = $conn->prepare("INSERT INTO preferencias_pagamento (parceiro_id, forma_pagamento, criado_em) VALUES (?, ?, NOW()) ON DUPLICATE KEY UPDATE forma_pagamento = VALUES(forma_pagamento)");
if ($stmt_pref) {
    $stmt_pref->bind_param("is", $parceiro_id, $forma_pagamento);
    $stmt_pref->execute();
    $stmt_pref->close();
}

// Resposta
if ($updated) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'error' => 'Erro ao salvar forma de pagamento' . ($erroUpdate ? " ($erroUpdate)" : '')]);
}

$conn->close();
?>

