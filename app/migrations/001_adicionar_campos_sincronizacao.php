<?php
/**
 * MIGRAÇÃO 001: Adicionar campos de sincronização EAD
 * Data: 29/10/2025
 */

require_once __DIR__ . '/../config/config.php';

$conn = getDBConnection();

try {
    echo "Iniciando migração 001...\n";
    
    // 1. Adicionar campos na tabela alunos
    echo "1. Adicionando campos em 'alunos'...\n";
    $queries = [
        "ALTER TABLE alunos ADD COLUMN ead_sincronizado TINYINT(1) DEFAULT 0 AFTER ativo",
        "ALTER TABLE alunos ADD COLUMN id_ead INT(11) DEFAULT NULL AFTER ead_sincronizado",
        
        // 2. Adicionar campos na tabela cursos
        "ALTER TABLE cursos ADD COLUMN ead_sincronizado TINYINT(1) DEFAULT 0 AFTER ativo",
        "ALTER TABLE cursos ADD COLUMN id_ead INT(11) DEFAULT NULL AFTER ead_sincronizado",
        
        // 3. Adicionar campos na tabela inscricoes_alunos
        "ALTER TABLE inscricoes_alunos ADD COLUMN ead_sincronizado TINYINT(1) DEFAULT 0 AFTER frequencia",
        "ALTER TABLE inscricoes_alunos ADD COLUMN id_ead_inscricao INT(11) DEFAULT NULL AFTER ead_sincronizado",
    ];
    
    foreach ($queries as $query) {
        try {
            $conn->query($query);
            echo "   ✓ " . substr($query, 0, 50) . "...\n";
        } catch (Exception $e) {
            // Campo pode já existir, ignorar erro
            if (strpos($e->getMessage(), 'Duplicate column') === false) {
                throw $e;
            }
            echo "   ⚠ Campo já existe (ignorado)\n";
        }
    }
    
    echo "\n✅ Migração 001 concluída com sucesso!\n";
    
} catch (Exception $e) {
    echo "\n❌ Erro na migração: " . $e->getMessage() . "\n";
    exit(1);
}

$conn->close();
?>

