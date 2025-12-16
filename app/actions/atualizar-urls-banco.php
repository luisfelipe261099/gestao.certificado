<?php
/**
 * Atualizar URLs no Banco de Dados
 * Script para corrigir URLs que usam o caminho antigo
 */

require_once __DIR__ . '/bootstrap.php';

// Verificar se é admin
if (!isAuthenticated() || !hasRole(ROLE_ADMIN)) {
    die('Acesso negado. Apenas administradores podem executar esta ação.');
}

$conn = getDBConnection();
$resultado = [];

try {
    // Atualizar URLs de certificados
    $sql_cert = "UPDATE `certificados` 
                 SET `arquivo_url` = REPLACE(`arquivo_url`, 
                     'http://localhost/startbootstrap-sb-admin-2-gh-pages', 
                     'http://localhost/gestao_certificado_murilo')
                 WHERE `arquivo_url` LIKE '%startbootstrap-sb-admin-2-gh-pages%'";
    
    if ($conn->query($sql_cert)) {
        $resultado['certificados'] = [
            'sucesso' => true,
            'linhas_afetadas' => $conn->affected_rows,
            'mensagem' => 'URLs de certificados atualizadas com sucesso!'
        ];
    } else {
        $resultado['certificados'] = [
            'sucesso' => false,
            'erro' => $conn->error
        ];
    }
    
    // Atualizar URLs de templates
    $sql_templates = "UPDATE `templates_certificados` 
                      SET `arquivo_url` = REPLACE(`arquivo_url`, 
                          'http://localhost/startbootstrap-sb-admin-2-gh-pages', 
                          'http://localhost/gestao_certificado_murilo')
                      WHERE `arquivo_url` LIKE '%startbootstrap-sb-admin-2-gh-pages%'";
    
    if ($conn->query($sql_templates)) {
        $resultado['templates'] = [
            'sucesso' => true,
            'linhas_afetadas' => $conn->affected_rows,
            'mensagem' => 'URLs de templates atualizadas com sucesso!'
        ];
    } else {
        $resultado['templates'] = [
            'sucesso' => false,
            'erro' => $conn->error
        ];
    }
    
    $conn->close();
    
} catch (Exception $e) {
    $resultado['erro'] = $e->getMessage();
}

// Retornar resultado como JSON
header('Content-Type: application/json; charset=utf-8');
echo json_encode($resultado, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
?>

