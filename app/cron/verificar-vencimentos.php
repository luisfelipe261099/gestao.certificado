<?php
/**
 * ============================================================================
 * CRON JOB: Verificar Vencimentos de Assinaturas
 * ============================================================================
 * 
 * Este script deve ser executado diariamente para:
 * 1. Verificar assinaturas que vencem em 7 dias
 * 2. Enviar emails de lembrete para os parceiros
 * 3. Criar cobranças automáticas se necessário
 * 
 * Executar via cron:
 * 0 8 * * * /usr/bin/php /caminho/app/cron/verificar-vencimentos.php
 * ============================================================================
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../lib/AsaasAPI.php';

// Conectar ao banco
$conn = getDBConnection();

echo "=== VERIFICAÇÃO DE VENCIMENTOS ===\n";
echo "Data/Hora: " . date('Y-m-d H:i:s') . "\n\n";

// ============================================================================
// 1. BUSCAR ASSINATURAS QUE VENCEM EM 7 DIAS
// ============================================================================

$stmt = $conn->prepare("
    SELECT 
        a.id,
        a.parceiro_id,
        a.plano_id,
        a.data_vencimento,
        a.renovacao_automatica,
        a.asaas_subscription_id,
        a.forma_pagamento,
        p.nome_empresa,
        p.email,
        p.cnpj,
        p.telefone,
        p.endereco,
        p.cidade,
        p.estado,
        p.cep,
        pl.nome as plano_nome,
        pl.valor
    FROM assinaturas a
    JOIN parceiros p ON a.parceiro_id = p.id
    JOIN planos pl ON a.plano_id = pl.id
    WHERE a.status = 'ativa'
    AND a.data_vencimento BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY)
    AND a.renovacao_automatica = 1
    ORDER BY a.data_vencimento ASC
");

$stmt->execute();
$result = $stmt->get_result();

$total_assinaturas = $result->num_rows;
echo "Assinaturas encontradas: $total_assinaturas\n\n";

if ($total_assinaturas === 0) {
    echo "Nenhuma assinatura vencendo nos próximos 7 dias.\n";
    $conn->close();
    exit(0);
}

$asaas = new AsaasAPI($conn);
$emails_enviados = 0;
$cobrancas_criadas = 0;
$erros = 0;

// ============================================================================
// 2. PROCESSAR CADA ASSINATURA
// ============================================================================

while ($assinatura = $result->fetch_assoc()) {
    echo "---------------------------------------------------\n";
    echo "Processando: {$assinatura['nome_empresa']}\n";
    echo "Plano: {$assinatura['plano_nome']}\n";
    echo "Vencimento: " . date('d/m/Y', strtotime($assinatura['data_vencimento'])) . "\n";
    
    $dias_restantes = (strtotime($assinatura['data_vencimento']) - time()) / (60 * 60 * 24);
    echo "Dias restantes: " . ceil($dias_restantes) . "\n";

    // ========================================================================
    // 2.1. VERIFICAR SE JÁ TEM ASSINATURA RECORRENTE NO ASAAS
    // ========================================================================
    
    if (!empty($assinatura['asaas_subscription_id'])) {
        echo "✓ Assinatura recorrente já existe no Asaas\n";
        echo "  ID: {$assinatura['asaas_subscription_id']}\n";
        
        // Asaas vai gerar a cobrança automaticamente
        // Apenas enviar lembrete
        
    } else {
        echo "⚠ Assinatura recorrente NÃO existe no Asaas\n";
        echo "  Criando cobrança manual...\n";
        
        // ====================================================================
        // 2.2. CRIAR COBRANÇA MANUAL (se não tem assinatura recorrente)
        // ====================================================================
        
        $dados_cobranca = [
            'nome_cliente' => $assinatura['nome_empresa'],
            'email_cliente' => $assinatura['email'],
            'cpf_cnpj' => $assinatura['cnpj'],
            'telefone' => $assinatura['telefone'],
            'endereco' => $assinatura['endereco'],
            'cidade' => $assinatura['cidade'],
            'estado' => $assinatura['estado'],
            'cep' => $assinatura['cep'],
            'valor' => $assinatura['valor'],
            'data_vencimento' => $assinatura['data_vencimento'],
            'descricao' => "Renovação - {$assinatura['plano_nome']}",
            'referencia_externa' => "RENOV-{$assinatura['id']}-" . date('YmdHis')
        ];
        
        // Escolher método baseado na forma de pagamento preferida
        $forma_pagamento = $assinatura['forma_pagamento'] ?? 'boleto';
        
        $resultado = null;
        switch ($forma_pagamento) {
            case 'pix':
                $resultado = $asaas->criarCobrancaPix($dados_cobranca);
                break;
            case 'cartao_credito':
                // Para cartão, precisa dos dados do cartão
                // Neste caso, apenas criar boleto como fallback
                $resultado = $asaas->criarBoleto($dados_cobranca);
                break;
            default:
                $resultado = $asaas->criarBoleto($dados_cobranca);
        }
        
        if ($resultado['success']) {
            echo "✓ Cobrança criada com sucesso\n";
            $cobrancas_criadas++;
            
            // Salvar cobrança no banco
            $asaas_id = $resultado['data']['id'];
            $url_boleto = $resultado['data']['bankSlipUrl'] ?? null;
            $linha_digitavel = $resultado['data']['identificationField'] ?? null;
            $qr_code_pix = $resultado['data']['encodedImage'] ?? null;
            $pix_copia_cola = $resultado['data']['payload'] ?? null;
            
            // Criar fatura
            $numero_fatura = 'FAT-' . $assinatura['parceiro_id'] . '-' . date('YmdHis');
            $stmt_fatura = $conn->prepare("
                INSERT INTO faturas (parceiro_id, assinatura_id, numero_fatura, descricao, valor, data_emissao, data_vencimento, status, forma_pagamento, tipo, asaas_id, criado_em)
                VALUES (?, ?, ?, ?, ?, NOW(), ?, 'pendente', ?, 'renovacao', ?, NOW())
            ");
            $stmt_fatura->bind_param('iissdss', 
                $assinatura['parceiro_id'],
                $assinatura['id'],
                $numero_fatura,
                $dados_cobranca['descricao'],
                $assinatura['valor'],
                $assinatura['data_vencimento'],
                $forma_pagamento,
                $asaas_id
            );
            $stmt_fatura->execute();
            $fatura_id = $conn->insert_id;
            $stmt_fatura->close();
            
            // Criar registro em asaas_cobrancas
            $invoice_url = $resultado['data']['invoiceUrl'] ?? null;
            $stmt_cobranca = $conn->prepare("
                INSERT INTO asaas_cobrancas (fatura_id, assinatura_id, parceiro_id, asaas_id, valor, data_vencimento, status, tipo_cobranca, pdf_url, linha_digitavel, qr_code_pix, pix_copia_cola, invoice_url, observacoes, criado_em)
                VALUES (?, ?, ?, ?, ?, ?, 'pendente', ?, ?, ?, ?, ?, ?, ?, NOW())
            ");
            $stmt_cobranca->bind_param('iiisdssssssss',
                $fatura_id,
                $assinatura['id'],
                $assinatura['parceiro_id'],
                $asaas_id,
                $assinatura['valor'],
                $assinatura['data_vencimento'],
                $forma_pagamento,
                $url_boleto,
                $linha_digitavel,
                $qr_code_pix,
                $pix_copia_cola,
                $invoice_url,
                $dados_cobranca['descricao']
            );
            $stmt_cobranca->execute();
            $stmt_cobranca->close();
            
        } else {
            echo "✗ Erro ao criar cobrança: " . ($resultado['error'] ?? 'Erro desconhecido') . "\n";
            $erros++;
        }
    }
    
    // ========================================================================
    // 2.3. ENVIAR EMAIL DE LEMBRETE
    // ========================================================================
    
    $to = $assinatura['email'];
    $subject = "Lembrete: Renovação do Plano {$assinatura['plano_nome']}";
    
    $message = "Olá {$assinatura['nome_empresa']},\n\n";
    $message .= "Sua assinatura do plano {$assinatura['plano_nome']} vence em breve.\n\n";
    $message .= "Data de vencimento: " . date('d/m/Y', strtotime($assinatura['data_vencimento'])) . "\n";
    $message .= "Valor: R$ " . number_format($assinatura['valor'], 2, ',', '.') . "\n\n";
    
    if ($assinatura['renovacao_automatica']) {
        $message .= "Sua renovação é automática. ";
        
        if ($assinatura['forma_pagamento'] === 'cartao_credito') {
            $message .= "O valor será debitado automaticamente no cartão cadastrado.\n\n";
        } else {
            $message .= "Você receberá o boleto/PIX para pagamento.\n\n";
        }
    }
    
    $message .= "Acesse o portal para mais detalhes:\n";
    $message .= APP_URL . "/parceiro/meu-plano.php\n\n";
    $message .= "Atenciosamente,\n";
    $message .= APP_NAME;
    
    $headers = "From: " . APP_EMAIL . "\r\n";
    $headers .= "Reply-To: " . APP_EMAIL . "\r\n";
    $headers .= "X-Mailer: PHP/" . phpversion();
    
    if (mail($to, $subject, $message, $headers)) {
        echo "✓ Email enviado para: {$assinatura['email']}\n";
        $emails_enviados++;
    } else {
        echo "✗ Erro ao enviar email para: {$assinatura['email']}\n";
        $erros++;
    }
    
    echo "\n";
}

$stmt->close();
$conn->close();

// ============================================================================
// 3. RESUMO FINAL
// ============================================================================

echo "===================================================\n";
echo "RESUMO DA EXECUÇÃO\n";
echo "===================================================\n";
echo "Total de assinaturas processadas: $total_assinaturas\n";
echo "Emails enviados: $emails_enviados\n";
echo "Cobranças criadas: $cobrancas_criadas\n";
echo "Erros: $erros\n";
echo "===================================================\n";
echo "Finalizado em: " . date('Y-m-d H:i:s') . "\n";

exit(0);
?>

