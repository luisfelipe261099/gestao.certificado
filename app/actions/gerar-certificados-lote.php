<?php
/**
 * ============================================================================
 * GERAR CERTIFICADOS EM LOTE - Sistema de Certificados
 * ============================================================================
 * 
 * Gera múltiplos certificados de uma vez e retorna um arquivo ZIP
 * 
 * ============================================================================
 */

ob_start();
require_once __DIR__ . '/bootstrap.php';

// Verificar autenticação
if (!isAuthenticated() || !hasRole(ROLE_PARCEIRO)) {
    $_SESSION['error'] = 'Acesso negado. Faça login como parceiro.';
    if (ob_get_level()) { @ob_end_clean(); }
    header('Location: ' . APP_URL . '/login.php');
    exit;
}

// Somente POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $_SESSION['error'] = 'Método não permitido';
    if (ob_get_level()) { @ob_end_clean(); }
    header('Location: ' . APP_URL . '/parceiro/gerar-certificados.php');
    exit;
}

try {
    $conn = getDBConnection();
    $user = getCurrentUser();
    $parceiro_id = $user['parceiro_id'] ?? $user['id'];

    // Receber dados
    $alunos_ids = isset($_POST['alunos']) && is_array($_POST['alunos']) ? $_POST['alunos'] : [];
    $curso_id = isset($_POST['curso']) ? intval($_POST['curso']) : 0;
    $data_conclusao = isset($_POST['data_conclusao']) ? trim($_POST['data_conclusao']) : '';
    $template_id = isset($_POST['template']) ? intval($_POST['template']) : 0;

    // Validações
    if (empty($alunos_ids)) {
        throw new Exception('Selecione pelo menos um aluno');
    }

    if ($curso_id <= 0) {
        throw new Exception('Curso é obrigatório');
    }

    if (empty($data_conclusao)) {
        throw new Exception('Data de conclusão é obrigatória');
    }

    // Limitar quantidade (segurança)
    if (count($alunos_ids) > 100) {
        throw new Exception('Limite máximo de 100 certificados por lote');
    }

    // Verificar créditos disponíveis
    $creditos_necessarios = count($alunos_ids);
    $stmt = $conn->prepare("
        SELECT SUM(creditos_disponiveis) as total_creditos
        FROM assinaturas
        WHERE parceiro_id = ? AND status = 'ativa'
    ");
    $stmt->bind_param("i", $parceiro_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $creditos_disponiveis = (int)($row['total_creditos'] ?? 0);
    $stmt->close();

    if ($creditos_disponiveis < $creditos_necessarios) {
        throw new Exception("Créditos insuficientes. Você precisa de {$creditos_necessarios} créditos, mas possui apenas {$creditos_disponiveis}.");
    }

    // Criar diretório temporário para os certificados
    $temp_dir = __DIR__ . '/../../uploads/temp/lote_' . time() . '_' . uniqid();
    if (!is_dir($temp_dir)) {
        mkdir($temp_dir, 0755, true);
    }

    $certificados_gerados = [];
    $erros = [];

    // Gerar cada certificado chamando a lógica diretamente
    foreach ($alunos_ids as $aluno_id) {
        $aluno_id = intval($aluno_id);

        try {
            // Simular POST para a lógica de geração
            $_POST_BACKUP = $_POST;
            $_POST = [
                'aluno' => $aluno_id,
                'curso' => $curso_id,
                'data_conclusao' => $data_conclusao,
                'template' => $template_id
            ];

            // Capturar output e incluir o script
            ob_start();

            // Definir flag para não redirecionar
            define('GERAR_LOTE_MODE', true);

            try {
                include __DIR__ . '/gerar-certificado.php';
            } catch (Exception $e) {
                error_log('Erro ao incluir gerar-certificado.php: ' . $e->getMessage());
            }

            ob_end_clean();

            // Restaurar POST
            $_POST = $_POST_BACKUP;

            // Buscar o certificado gerado mais recente
            $stmt = $conn->prepare("
                SELECT id, numero_certificado, arquivo_path
                FROM certificados
                WHERE parceiro_id = ? AND aluno_id = ? AND curso_id = ?
                ORDER BY data_geracao DESC
                LIMIT 1
            ");
            $stmt->bind_param("iii", $parceiro_id, $aluno_id, $curso_id);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($cert = $result->fetch_assoc()) {
                $certificados_gerados[] = $cert;
            } else {
                $erros[] = "Aluno ID {$aluno_id}: Certificado não foi gerado";
            }
            $stmt->close();

        } catch (Exception $e) {
            $erros[] = "Aluno ID {$aluno_id}: " . $e->getMessage();
        }
    }

    if (empty($certificados_gerados)) {
        throw new Exception('Nenhum certificado foi gerado. Erros: ' . implode('; ', $erros));
    }

    // Criar diretório temp se não existir
    $temp_base_dir = __DIR__ . '/../../uploads/temp';
    if (!is_dir($temp_base_dir)) {
        mkdir($temp_base_dir, 0755, true);
    }

    // Criar arquivo ZIP
    $zip_filename = 'certificados_lote_' . date('Y-m-d_His') . '.zip';
    $zip_path = $temp_base_dir . '/' . $zip_filename;

    if (!class_exists('ZipArchive')) {
        throw new Exception('Extensão ZipArchive não está disponível no servidor');
    }

    $zip = new ZipArchive();
    if ($zip->open($zip_path, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== TRUE) {
        throw new Exception('Não foi possível criar o arquivo ZIP');
    }

    // Adicionar certificados ao ZIP
    foreach ($certificados_gerados as $cert) {
        // Converter URL para caminho local
        $arquivo_path = $cert['arquivo_path'];

        // Remover o domínio se houver
        if (strpos($arquivo_path, 'http://') === 0 || strpos($arquivo_path, 'https://') === 0) {
            $arquivo_path = preg_replace('|^https?://[^/]+|', '', $arquivo_path);
        }

        // Remover APP_BASE_PATH se houver
        if (strpos($arquivo_path, APP_BASE_PATH) === 0) {
            $arquivo_path = substr($arquivo_path, strlen(APP_BASE_PATH));
        }

        $cert_path = __DIR__ . '/../../' . ltrim($arquivo_path, '/');

        if (file_exists($cert_path)) {
            $zip->addFile($cert_path, $cert['numero_certificado'] . '.pdf');
        } else {
            error_log('Arquivo não encontrado para ZIP: ' . $cert_path);
        }
    }

    $zip->close();

    // Limpar diretório temporário
    if (is_dir($temp_dir)) {
        @rmdir($temp_dir);
    }

    // Download do ZIP
    if (file_exists($zip_path)) {
        if (ob_get_level()) { @ob_end_clean(); }
        
        header('Content-Type: application/zip');
        header('Content-Disposition: attachment; filename="' . $zip_filename . '"');
        header('Content-Length: ' . filesize($zip_path));
        header('Cache-Control: no-cache, must-revalidate');
        header('Pragma: no-cache');
        
        readfile($zip_path);
        
        // Deletar ZIP após download
        @unlink($zip_path);
        exit;
    }

} catch (Exception $e) {
    error_log('Erro ao gerar certificados em lote: ' . $e->getMessage());
    $_SESSION['error'] = $e->getMessage();
    if (ob_get_level()) { @ob_end_clean(); }
    header('Location: ' . APP_URL . '/parceiro/gerar-certificados.php');
    exit;
}

