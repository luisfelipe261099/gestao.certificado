<?php
/**
 * ============================================================================
 * AÇÃO: CORRIGIR CERTIFICADOS_TOTAIS
 * ============================================================================
 * 
 * Este script corrige o valor de certificados_totais baseado no plano
 * 
 * Uso:
 * - Acesse: /app/actions/corrigir-totais-certificados.php?parceiro_id=16
 * - Ou deixe em branco para corrigir todas as assinaturas
 * 
 * ============================================================================
 */

require_once __DIR__ . '/../config/config.php';

$conn = getDBConnection();
$parceiro_id = isset($_GET['parceiro_id']) ? intval($_GET['parceiro_id']) : null;

try {
    echo "╔════════════════════════════════════════════════════════════════════════════╗\n";
    echo "║              CORRIGINDO CERTIFICADOS_TOTAIS DAS ASSINATURAS               ║\n";
    echo "╚════════════════════════════════════════════════════════════════════════════╝\n\n";

    // ========================================================================
    // 1. BUSCAR ASSINATURAS COM CERTIFICADOS_TOTAIS ZERADO
    // ========================================================================
    
    if ($parceiro_id) {
        $query = "
            SELECT 
                a.id,
                a.parceiro_id,
                COALESCE(par.nome_empresa, 'Parceiro Desconhecido') as parceiro_nome,
                a.plano_id,
                p.nome as plano_nome,
                p.quantidade_certificados,
                a.certificados_totais,
                a.certificados_usados,
                a.certificados_disponiveis
            FROM assinaturas a
            LEFT JOIN parceiros par ON a.parceiro_id = par.id
            LEFT JOIN planos p ON a.plano_id = p.id
            WHERE a.status = 'ativa' AND a.parceiro_id = ?
            ORDER BY a.parceiro_id
        ";
        
        $stmt = $conn->prepare($query);
        if (!$stmt) {
            throw new Exception("Erro ao preparar query: " . $conn->error);
        }
        $stmt->bind_param("i", $parceiro_id);
        $stmt->execute();
        $result = $stmt->get_result();
    } else {
        $query = "
            SELECT 
                a.id,
                a.parceiro_id,
                COALESCE(par.nome_empresa, 'Parceiro Desconhecido') as parceiro_nome,
                a.plano_id,
                p.nome as plano_nome,
                p.quantidade_certificados,
                a.certificados_totais,
                a.certificados_usados,
                a.certificados_disponiveis
            FROM assinaturas a
            LEFT JOIN parceiros par ON a.parceiro_id = par.id
            LEFT JOIN planos p ON a.plano_id = p.id
            WHERE a.status = 'ativa'
            ORDER BY a.parceiro_id
        ";
        
        $result = $conn->query($query);
        if (!$result) {
            throw new Exception("Erro ao buscar assinaturas: " . $conn->error);
        }
    }
    
    $assinaturas = [];
    while ($row = $result->fetch_assoc()) {
        $assinaturas[] = $row;
    }
    
    if (empty($assinaturas)) {
        echo "⚠️  Nenhuma assinatura encontrada para corrigir.\n";
        $conn->close();
        exit;
    }
    
    echo "📊 Encontradas " . count($assinaturas) . " assinatura(s) para verificar.\n\n";
    
    // ========================================================================
    // 2. VERIFICAR E CORRIGIR CADA ASSINATURA
    // ========================================================================
    
    $corrigidas = 0;
    $erros = 0;
    
    foreach ($assinaturas as $assinatura) {
        $ass_id = $assinatura['id'];
        $parc_id = $assinatura['parceiro_id'];
        $parc_nome = $assinatura['parceiro_nome'];
        $plano_nome = $assinatura['plano_nome'];
        $quantidade_certificados = $assinatura['quantidade_certificados'] ?? 0;
        $totais_atual = $assinatura['certificados_totais'];
        $usados = $assinatura['certificados_usados'];
        $disponiveis = $assinatura['certificados_disponiveis'];
        
        echo "─────────────────────────────────────────────────────────────────────────────\n";
        echo "Assinatura ID: $ass_id | Parceiro: $parc_nome (ID: $parc_id)\n";
        echo "Plano: $plano_nome | Quantidade no Plano: $quantidade_certificados\n";
        echo "Dados Atuais: Totais=$totais_atual, Usados=$usados, Disponíveis=$disponiveis\n";
        
        // Verificar se precisa corrigir
        if ($totais_atual == $quantidade_certificados) {
            echo "✅ Certificados_totais já está correto!\n";
            continue;
        }
        
        // Corrigir
        echo "🔧 Corrigindo: Totais $totais_atual → $quantidade_certificados\n";
        
        // Recalcular disponíveis
        $disponiveis_novo = $quantidade_certificados - $usados;
        
        $update_query = "
            UPDATE assinaturas
            SET 
                certificados_totais = $quantidade_certificados,
                certificados_disponiveis = $disponiveis_novo,
                atualizado_em = NOW()
            WHERE id = $ass_id
            LIMIT 1
        ";
        
        if ($conn->query($update_query)) {
            echo "✅ Corrigida com sucesso! Disponíveis: $disponiveis → $disponiveis_novo\n";
            $corrigidas++;
        } else {
            echo "❌ Erro ao corrigir: " . $conn->error . "\n";
            $erros++;
        }
    }
    
    // ========================================================================
    // 3. RESUMO
    // ========================================================================
    
    echo "\n╔════════════════════════════════════════════════════════════════════════════╗\n";
    echo "║                              RESUMO DA CORREÇÃO                            ║\n";
    echo "╚════════════════════════════════════════════════════════════════════════════╝\n";
    echo "✅ Assinaturas corrigidas: $corrigidas\n";
    echo "❌ Erros encontrados: $erros\n";
    echo "📊 Total processado: " . count($assinaturas) . "\n\n";
    
    // ========================================================================
    // 4. VERIFICAR RESULTADO FINAL
    // ========================================================================
    
    echo "Verificando resultado final...\n\n";
    
    $query_final = "
        SELECT 
            a.id,
            a.parceiro_id,
            COALESCE(par.nome_empresa, 'Parceiro Desconhecido') as parceiro_nome,
            a.certificados_totais,
            a.certificados_usados,
            a.certificados_disponiveis
        FROM assinaturas a
        LEFT JOIN parceiros par ON a.parceiro_id = par.id
        WHERE a.status = 'ativa'
        ORDER BY a.parceiro_id
    ";
    
    $result = $conn->query($query_final);
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $soma = $row['certificados_usados'] + $row['certificados_disponiveis'];
            $status = ($soma == $row['certificados_totais']) ? '✅' : '❌';
            echo "$status Parceiro {$row['parceiro_id']} ({$row['parceiro_nome']}): {$row['certificados_usados']} + {$row['certificados_disponiveis']} = {$soma} (esperado: {$row['certificados_totais']})\n";
        }
    } else {
        echo "❌ Erro ao verificar resultado: " . $conn->error . "\n";
    }
    
    echo "\n✅ Correção concluída!\n";
    
} catch (Exception $e) {
    echo "❌ Erro: " . $e->getMessage() . "\n";
} finally {
    $conn->close();
}
?>

