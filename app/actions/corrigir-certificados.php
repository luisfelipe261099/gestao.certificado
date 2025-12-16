<?php
/**
 * ============================================================================
 * AÇÃO: CORRIGIR CERTIFICADOS INCONSISTENTES
 * ============================================================================
 * 
 * Este script corrige dados inconsistentes na tabela assinaturas
 * Recalcula certificados_usados e certificados_disponiveis baseado em
 * certificados realmente gerados na tabela certificados
 * 
 * Uso:
 * - Acesse: /app/actions/corrigir-certificados.php?parceiro_id=16
 * - Ou deixe em branco para corrigir todas as assinaturas ativas
 * 
 * ============================================================================
 */

require_once __DIR__ . '/../config/config.php';

$conn = getDBConnection();
$parceiro_id = isset($_GET['parceiro_id']) ? intval($_GET['parceiro_id']) : null;

try {
    echo "╔════════════════════════════════════════════════════════════════════════════╗\n";
    echo "║                  CORRIGINDO CERTIFICADOS INCONSISTENTES                    ║\n";
    echo "╚════════════════════════════════════════════════════════════════════════════╝\n\n";

    // Debug: Verificar estrutura das tabelas
    echo "🔍 Verificando estrutura das tabelas...\n\n";

    // Verificar colunas da tabela assinaturas
    $result_cols = $conn->query("DESCRIBE assinaturas");
    if ($result_cols) {
        echo "✅ Tabela 'assinaturas' encontrada com colunas:\n";
        while ($col = $result_cols->fetch_assoc()) {
            echo "   - {$col['Field']} ({$col['Type']})\n";
        }
        echo "\n";
    }

    // Verificar colunas da tabela parceiros
    $result_cols = $conn->query("DESCRIBE parceiros");
    if ($result_cols) {
        echo "✅ Tabela 'parceiros' encontrada com colunas:\n";
        while ($col = $result_cols->fetch_assoc()) {
            echo "   - {$col['Field']} ({$col['Type']})\n";
        }
        echo "\n";
    } else {
        echo "❌ Tabela 'parceiros' NÃO encontrada!\n\n";
    }

    echo "─────────────────────────────────────────────────────────────────────────────\n\n";

    // ========================================================================
    // 1. BUSCAR ASSINATURAS A CORRIGIR
    // ========================================================================

    if ($parceiro_id) {
        $query = "
            SELECT
                a.id,
                a.parceiro_id,
                COALESCE(par.nome_empresa, 'Parceiro Desconhecido') as parceiro_nome,
                a.certificados_totais,
                a.certificados_usados,
                a.certificados_disponiveis
            FROM assinaturas a
            LEFT JOIN parceiros par ON a.parceiro_id = par.id
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
                a.certificados_totais,
                a.certificados_usados,
                a.certificados_disponiveis
            FROM assinaturas a
            LEFT JOIN parceiros par ON a.parceiro_id = par.id
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
        $totais = $assinatura['certificados_totais'];
        $usados_atual = $assinatura['certificados_usados'];
        $disponiveis_atual = $assinatura['certificados_disponiveis'];
        
        echo "─────────────────────────────────────────────────────────────────────────────\n";
        echo "Assinatura ID: $ass_id | Parceiro: $parc_nome (ID: $parc_id)\n";
        echo "Dados Atuais: Totais=$totais, Usados=$usados_atual, Disponíveis=$disponiveis_atual\n";
        
        // Contar certificados realmente gerados
        $query_count = "
            SELECT 
                COUNT(*) as total_gerados,
                COUNT(CASE WHEN status != 'cancelado' THEN 1 END) as nao_cancelados,
                COUNT(CASE WHEN status = 'cancelado' THEN 1 END) as cancelados
            FROM certificados
            WHERE parceiro_id = $parc_id
        ";
        
        $result_count = $conn->query($query_count);
        if (!$result_count) {
            echo "❌ Erro ao contar certificados: " . $conn->error . "\n";
            $erros++;
            continue;
        }
        
        $count_row = $result_count->fetch_assoc();
        $gerados = $count_row['nao_cancelados'] ?? 0;
        $cancelados = $count_row['cancelados'] ?? 0;
        
        echo "Certificados Reais: Gerados=$gerados, Cancelados=$cancelados\n";
        
        // Calcular novos valores
        $usados_novo = $gerados;
        $disponiveis_novo = $totais - $gerados;
        
        // Verificar se precisa corrigir
        if ($usados_novo == $usados_atual && $disponiveis_novo == $disponiveis_atual) {
            echo "✅ Dados já estão corretos!\n";
            continue;
        }
        
        // Corrigir
        echo "🔧 Corrigindo: Usados $usados_atual → $usados_novo, Disponíveis $disponiveis_atual → $disponiveis_novo\n";
        
        $update_query = "
            UPDATE assinaturas
            SET 
                certificados_usados = $usados_novo,
                certificados_disponiveis = $disponiveis_novo,
                atualizado_em = NOW()
            WHERE id = $ass_id
            LIMIT 1
        ";
        
        if ($conn->query($update_query)) {
            echo "✅ Corrigida com sucesso!\n";
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

