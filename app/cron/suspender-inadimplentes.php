<?php
/**
 * ============================================================================
 * CRON JOB: Suspender Assinaturas Inadimplentes
 * ============================================================================
 * 
 * Este script deve ser executado diariamente para:
 * 1. Verificar assinaturas vencidas há mais de 3 dias
 * 2. Suspender assinaturas inadimplentes
 * 3. Bloquear geração de certificados
 * 4. Enviar emails de notificação
 * 
 * Executar via cron:
 * 0 9 * * * /usr/bin/php /caminho/app/cron/suspender-inadimplentes.php
 * ============================================================================
 */

require_once __DIR__ . '/../config/config.php';

// Conectar ao banco
$conn = getDBConnection();

echo "=== SUSPENSÃO DE INADIMPLENTES ===\n";
echo "Data/Hora: " . date('Y-m-d H:i:s') . "\n\n";

// ============================================================================
// 1. BUSCAR ASSINATURAS VENCIDAS HÁ MAIS DE 3 DIAS
// ============================================================================

$stmt = $conn->prepare("
    SELECT 
        a.id,
        a.parceiro_id,
        a.plano_id,
        a.data_vencimento,
        p.nome_empresa,
        p.email,
        pl.nome as plano_nome,
        pl.valor,
        DATEDIFF(CURDATE(), a.data_vencimento) as dias_vencido
    FROM assinaturas a
    JOIN parceiros p ON a.parceiro_id = p.id
    JOIN planos pl ON a.plano_id = pl.id
    WHERE a.status = 'ativa'
    AND a.data_vencimento < DATE_SUB(CURDATE(), INTERVAL 3 DAY)
    ORDER BY a.data_vencimento ASC
");

$stmt->execute();
$result = $stmt->get_result();

$total_assinaturas = $result->num_rows;
echo "Assinaturas inadimplentes encontradas: $total_assinaturas\n\n";

if ($total_assinaturas === 0) {
    echo "Nenhuma assinatura inadimplente para suspender.\n";
    $conn->close();
    exit(0);
}

$suspensoes_realizadas = 0;
$emails_enviados = 0;
$erros = 0;

// ============================================================================
// 2. PROCESSAR CADA ASSINATURA INADIMPLENTE
// ============================================================================

while ($assinatura = $result->fetch_assoc()) {
    echo "---------------------------------------------------\n";
    echo "Processando: {$assinatura['nome_empresa']}\n";
    echo "Plano: {$assinatura['plano_nome']}\n";
    echo "Vencimento: " . date('d/m/Y', strtotime($assinatura['data_vencimento'])) . "\n";
    echo "Dias vencido: {$assinatura['dias_vencido']}\n";
    
    // ========================================================================
    // 2.1. SUSPENDER ASSINATURA
    // ========================================================================
    
    $stmt_update = $conn->prepare("
        UPDATE assinaturas 
        SET status = 'suspensa', 
            atualizado_em = NOW() 
        WHERE id = ?
    ");
    $stmt_update->bind_param("i", $assinatura['id']);
    
    if ($stmt_update->execute()) {
        echo "✓ Assinatura suspensa com sucesso\n";
        $suspensoes_realizadas++;
        $stmt_update->close();
        
        // ====================================================================
        // 2.2. REGISTRAR LOG DE SUSPENSÃO
        // ====================================================================
        
        $stmt_log = $conn->prepare("
            INSERT INTO log_renovacoes (assinatura_id, parceiro_id, plano_id, tipo, data_vencimento_anterior, data_vencimento_nova, valor, status, mensagem, criado_em)
            VALUES (?, ?, ?, 'automatica', ?, ?, ?, 'falha', 'Assinatura suspensa por inadimplência', NOW())
        ");
        $stmt_log->bind_param('iiissd',
            $assinatura['id'],
            $assinatura['parceiro_id'],
            $assinatura['plano_id'],
            $assinatura['data_vencimento'],
            $assinatura['data_vencimento'],
            $assinatura['valor']
        );
        $stmt_log->execute();
        $stmt_log->close();
        
    } else {
        echo "✗ Erro ao suspender assinatura: " . $stmt_update->error . "\n";
        $erros++;
        $stmt_update->close();
        continue;
    }
    
    // ========================================================================
    // 2.3. ENVIAR EMAIL DE NOTIFICAÇÃO
    // ========================================================================
    
    $to = $assinatura['email'];
    $subject = "URGENTE: Assinatura Suspensa por Falta de Pagamento";
    
    $message = "Olá {$assinatura['nome_empresa']},\n\n";
    $message .= "Sua assinatura do plano {$assinatura['plano_nome']} foi SUSPENSA por falta de pagamento.\n\n";
    $message .= "Detalhes:\n";
    $message .= "- Data de vencimento: " . date('d/m/Y', strtotime($assinatura['data_vencimento'])) . "\n";
    $message .= "- Dias em atraso: {$assinatura['dias_vencido']} dias\n";
    $message .= "- Valor: R$ " . number_format($assinatura['valor'], 2, ',', '.') . "\n\n";
    
    $message .= "CONSEQUÊNCIAS DA SUSPENSÃO:\n";
    $message .= "- Você NÃO poderá gerar novos certificados\n";
    $message .= "- Seus alunos NÃO poderão acessar os cursos\n";
    $message .= "- Seus dados serão mantidos por 30 dias\n\n";
    
    $message .= "COMO REATIVAR:\n";
    $message .= "1. Acesse o portal: " . APP_URL . "/parceiro/meu-plano.php\n";
    $message .= "2. Efetue o pagamento da fatura em aberto\n";
    $message .= "3. Sua assinatura será reativada automaticamente\n\n";
    
    $message .= "ATENÇÃO: Se o pagamento não for realizado em 30 dias, sua conta será CANCELADA e todos os dados serão EXCLUÍDOS permanentemente.\n\n";
    
    $message .= "Em caso de dúvidas, entre em contato conosco.\n\n";
    $message .= "Atenciosamente,\n";
    $message .= APP_NAME;
    
    $headers = "From: " . APP_EMAIL . "\r\n";
    $headers .= "Reply-To: " . APP_EMAIL . "\r\n";
    $headers .= "X-Mailer: PHP/" . phpversion();
    
    if (mail($to, $subject, $message, $headers)) {
        echo "✓ Email de suspensão enviado para: {$assinatura['email']}\n";
        $emails_enviados++;
    } else {
        echo "✗ Erro ao enviar email para: {$assinatura['email']}\n";
        $erros++;
    }
    
    echo "\n";
}

$stmt->close();

// ============================================================================
// 3. VERIFICAR ASSINATURAS SUSPENSAS HÁ MAIS DE 30 DIAS (CANCELAR)
// ============================================================================

echo "---------------------------------------------------\n";
echo "Verificando assinaturas suspensas há mais de 30 dias...\n\n";

$stmt_cancelar = $conn->prepare("
    SELECT 
        a.id,
        a.parceiro_id,
        p.nome_empresa,
        p.email,
        pl.nome as plano_nome,
        DATEDIFF(CURDATE(), a.atualizado_em) as dias_suspensa
    FROM assinaturas a
    JOIN parceiros p ON a.parceiro_id = p.id
    JOIN planos pl ON a.plano_id = pl.id
    WHERE a.status = 'suspensa'
    AND a.atualizado_em < DATE_SUB(CURDATE(), INTERVAL 30 DAY)
");

$stmt_cancelar->execute();
$result_cancelar = $stmt_cancelar->get_result();

$total_cancelamentos = $result_cancelar->num_rows;
echo "Assinaturas para cancelar: $total_cancelamentos\n\n";

$cancelamentos_realizados = 0;

while ($assinatura = $result_cancelar->fetch_assoc()) {
    echo "Cancelando: {$assinatura['nome_empresa']}\n";
    echo "Dias suspensa: {$assinatura['dias_suspensa']}\n";
    
    // Cancelar assinatura
    $stmt_update = $conn->prepare("
        UPDATE assinaturas 
        SET status = 'cancelada', 
            atualizado_em = NOW() 
        WHERE id = ?
    ");
    $stmt_update->bind_param("i", $assinatura['id']);
    
    if ($stmt_update->execute()) {
        echo "✓ Assinatura cancelada\n";
        $cancelamentos_realizados++;
        $stmt_update->close();
        
        // Enviar email de cancelamento
        $to = $assinatura['email'];
        $subject = "Assinatura Cancelada - {$assinatura['plano_nome']}";
        
        $message = "Olá {$assinatura['nome_empresa']},\n\n";
        $message .= "Sua assinatura do plano {$assinatura['plano_nome']} foi CANCELADA.\n\n";
        $message .= "Motivo: Falta de pagamento por mais de 30 dias.\n\n";
        $message .= "Seus dados foram removidos do sistema.\n\n";
        $message .= "Para contratar novamente, acesse: " . APP_URL . "\n\n";
        $message .= "Atenciosamente,\n";
        $message .= APP_NAME;
        
        $headers = "From: " . APP_EMAIL . "\r\n";
        $headers .= "Reply-To: " . APP_EMAIL . "\r\n";
        
        mail($to, $subject, $message, $headers);
        
    } else {
        echo "✗ Erro ao cancelar: " . $stmt_update->error . "\n";
        $stmt_update->close();
    }
    
    echo "\n";
}

$stmt_cancelar->close();
$conn->close();

// ============================================================================
// 4. RESUMO FINAL
// ============================================================================

echo "===================================================\n";
echo "RESUMO DA EXECUÇÃO\n";
echo "===================================================\n";
echo "Assinaturas suspensas: $suspensoes_realizadas\n";
echo "Assinaturas canceladas: $cancelamentos_realizados\n";
echo "Emails enviados: $emails_enviados\n";
echo "Erros: $erros\n";
echo "===================================================\n";
echo "Finalizado em: " . date('Y-m-d H:i:s') . "\n";

exit(0);
?>

