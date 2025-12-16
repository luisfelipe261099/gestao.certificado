<?php
/**
 * Verificar URLs no Banco de Dados
 * Script para diagnosticar URLs com o caminho antigo
 */

require_once __DIR__ . '/bootstrap.php';

// Verificar se é admin
if (!isAuthenticated() || !hasRole(ROLE_ADMIN)) {
    die('Acesso negado. Apenas administradores podem executar esta ação.');
}

$conn = getDBConnection();
$resultado = [];

try {
    // Verificar certificados com URL antiga
    $sql_cert_antiga = "SELECT COUNT(*) as total FROM `certificados` 
                        WHERE `arquivo_url` LIKE '%startbootstrap-sb-admin-2-gh-pages%'";
    $result = $conn->query($sql_cert_antiga);
    $row = $result->fetch_assoc();
    $resultado['certificados_com_url_antiga'] = $row['total'];
    
    // Verificar certificados com URL nova
    $sql_cert_nova = "SELECT COUNT(*) as total FROM `certificados` 
                      WHERE `arquivo_url` LIKE '%gestao_certificado_murilo%'";
    $result = $conn->query($sql_cert_nova);
    $row = $result->fetch_assoc();
    $resultado['certificados_com_url_nova'] = $row['total'];
    
    // Verificar templates com URL antiga
    $sql_template_antiga = "SELECT COUNT(*) as total FROM `templates_certificados` 
                            WHERE `arquivo_url` LIKE '%startbootstrap-sb-admin-2-gh-pages%'";
    $result = $conn->query($sql_template_antiga);
    $row = $result->fetch_assoc();
    $resultado['templates_com_url_antiga'] = $row['total'];
    
    // Verificar templates com URL nova
    $sql_template_nova = "SELECT COUNT(*) as total FROM `templates_certificados` 
                          WHERE `arquivo_url` LIKE '%gestao_certificado_murilo%'";
    $result = $conn->query($sql_template_nova);
    $row = $result->fetch_assoc();
    $resultado['templates_com_url_nova'] = $row['total'];
    
    // Listar certificados com URL antiga (para debug)
    $sql_cert_lista = "SELECT id, numero_certificado, arquivo_url FROM `certificados` 
                       WHERE `arquivo_url` LIKE '%startbootstrap-sb-admin-2-gh-pages%' LIMIT 10";
    $result = $conn->query($sql_cert_lista);
    $resultado['certificados_lista_antiga'] = [];
    while ($row = $result->fetch_assoc()) {
        $resultado['certificados_lista_antiga'][] = $row;
    }
    
    // Listar templates com URL antiga (para debug)
    $sql_template_lista = "SELECT id, nome, arquivo_url FROM `templates_certificados` 
                           WHERE `arquivo_url` LIKE '%startbootstrap-sb-admin-2-gh-pages%' LIMIT 10";
    $result = $conn->query($sql_template_lista);
    $resultado['templates_lista_antiga'] = [];
    while ($row = $result->fetch_assoc()) {
        $resultado['templates_lista_antiga'][] = $row;
    }
    
    // Verificar configuração atual
    $resultado['APP_URL_atual'] = APP_URL;
    $resultado['DIR_PARCEIRO_atual'] = DIR_PARCEIRO;
    $resultado['DIR_ADMIN_atual'] = DIR_ADMIN;
    
    $conn->close();
    
} catch (Exception $e) {
    $resultado['erro'] = $e->getMessage();
}

// Retornar resultado como JSON
header('Content-Type: application/json; charset=utf-8');
echo json_encode($resultado, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
?>

