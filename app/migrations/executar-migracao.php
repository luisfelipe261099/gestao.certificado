<?php
/**
 * Script: executar-migracao.php
 * Executa todas as migrações necessárias
 */

require_once __DIR__ . '/../config/config.php';

echo "╔════════════════════════════════════════════════════════════════╗\n";
echo "║         EXECUTANDO MIGRAÇÕES DE INTEGRAÇÃO EAD                ║\n";
echo "╚════════════════════════════════════════════════════════════════╝\n\n";

$migrações = [
    '001_adicionar_campos_sincronizacao.php',
    '002_criar_tabelas_ead.php'
];

$sucesso = 0;
$erro = 0;

foreach ($migrações as $migracao) {
    $arquivo = __DIR__ . '/' . $migracao;
    
    if (!file_exists($arquivo)) {
        echo "❌ Arquivo não encontrado: {$migracao}\n";
        $erro++;
        continue;
    }
    
    echo "\n📋 Executando: {$migracao}\n";
    echo "─────────────────────────────────────────────────────────────\n";
    
    try {
        ob_start();
        include $arquivo;
        $output = ob_get_clean();
        echo $output;
        $sucesso++;
    } catch (Exception $e) {
        echo "❌ Erro: " . $e->getMessage() . "\n";
        $erro++;
    }
}

echo "\n╔════════════════════════════════════════════════════════════════╗\n";
echo "║                    RESUMO DAS MIGRAÇÕES                        ║\n";
echo "╠════════════════════════════════════════════════════════════════╣\n";
echo "║ ✅ Sucesso: {$sucesso}                                                  ║\n";
echo "║ ❌ Erros: {$erro}                                                    ║\n";
echo "╚════════════════════════════════════════════════════════════════╝\n";

if ($erro === 0) {
    echo "\n🎉 Todas as migrações foram executadas com sucesso!\n";
    echo "\nPróximos passos:\n";
    echo "1. Adicionar botão 'Acessar EAD' no dashboard do parceiro\n";
    echo "2. Integrar sincronização nos formulários de criação\n";
    echo "3. Testar fluxo completo\n";
} else {
    echo "\n⚠️  Algumas migrações falharam. Verifique os erros acima.\n";
}
?>

