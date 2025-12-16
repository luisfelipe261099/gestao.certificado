<?php
/**
 * Importar Alunos - Sistema de Certificados
 * Ação para importar alunos via planilha Excel ou CSV
 */

ob_start();

require_once '../config/config.php';

// Requer login como parceiro
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
    header('Location: ' . APP_URL . '/parceiro/alunos-parceiro.php');
    exit;
}

try {
    // Validar arquivo
    if (!isset($_FILES['arquivo']) || $_FILES['arquivo']['error'] !== UPLOAD_ERR_OK) {
        throw new Exception('Nenhum arquivo foi enviado ou houve um erro no upload.');
    }
    
    $file = $_FILES['arquivo'];
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    
    if (!in_array($ext, ['xlsx', 'xls', 'csv'])) {
        throw new Exception('Formato de arquivo não suportado. Use .xlsx, .xls ou .csv');
    }
    
    $conn = getDBConnection();
    $user = getCurrentUser();
    $parceiro_id = $user['parceiro_id'] ?? $user['id'];
    
    $dados = [];
    
    // Processar arquivo
    if ($ext === 'csv') {
        // Processar CSV
        $handle = fopen($file['tmp_name'], 'r');
        if (!$handle) {
            throw new Exception('Não foi possível abrir o arquivo CSV.');
        }
        
        // Ler cabeçalho
        $header = fgetcsv($handle, 0, ';');
        if (!$header) {
            throw new Exception('Arquivo CSV vazio ou inválido.');
        }
        
        // Normalizar cabeçalho
        $header = array_map('strtolower', array_map('trim', $header));
        
        // Ler linhas
        while (($row = fgetcsv($handle, 0, ';')) !== false) {
            if (empty(array_filter($row))) continue; // Pular linhas vazias
            
            $linha = array_combine($header, $row);
            $dados[] = $linha;
        }
        fclose($handle);
    } else {
        // Processar Excel
        if (!class_exists('PhpOffice\PhpSpreadsheet\IOFactory')) {
            throw new Exception('Biblioteca PhpSpreadsheet não disponível.');
        }
        
        $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($file['tmp_name']);
        $sheet = $spreadsheet->getActiveSheet();
        
        $rows = $sheet->toArray();
        if (empty($rows)) {
            throw new Exception('Arquivo Excel vazio.');
        }
        
        // Primeira linha é cabeçalho
        $header = array_map('strtolower', array_map('trim', $rows[0]));
        
        // Processar linhas
        for ($i = 1; $i < count($rows); $i++) {
            if (empty(array_filter($rows[$i]))) continue;
            
            $linha = array_combine($header, $rows[$i]);
            $dados[] = $linha;
        }
    }
    
    if (empty($dados)) {
        throw new Exception('Nenhum dado foi encontrado no arquivo.');
    }
    
    // Validar e inserir dados
    $importados = 0;
    $erros = [];
    
    foreach ($dados as $idx => $linha) {
        $nome = trim($linha['nome'] ?? '');
        $email = trim($linha['email'] ?? '');
        $cpf = trim($linha['cpf'] ?? '');
        $telefone = trim($linha['telefone'] ?? '');
        $data_nascimento = trim($linha['data de nascimento'] ?? '');
        $endereco = trim($linha['endereço'] ?? '');
        $cidade = trim($linha['cidade'] ?? '');
        $estado = trim($linha['estado'] ?? '');
        $cep = trim($linha['cep'] ?? '');
        
        // Validar campos obrigatórios
        if (empty($nome) || empty($email)) {
            $erros[] = "Linha " . ($idx + 2) . ": Nome e Email são obrigatórios.";
            continue;
        }
        
        // Converter data se necessário
        if (!empty($data_nascimento)) {
            $data_obj = \DateTime::createFromFormat('d/m/Y', $data_nascimento);
            if ($data_obj) {
                $data_nascimento = $data_obj->format('Y-m-d');
            } else {
                $data_nascimento = null;
            }
        }
        
        // Inserir aluno
        $stmt = $conn->prepare("
            INSERT INTO alunos (parceiro_id, nome, email, cpf, telefone, data_nascimento, endereco, cidade, estado, cep)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        if ($stmt) {
            $stmt->bind_param(
                'isssssssss',
                $parceiro_id,
                $nome,
                $email,
                $cpf,
                $telefone,
                $data_nascimento,
                $endereco,
                $cidade,
                $estado,
                $cep
            );
            
            if ($stmt->execute()) {
                $importados++;
            } else {
                $erros[] = "Linha " . ($idx + 2) . ": Erro ao inserir - " . $stmt->error;
            }
            $stmt->close();
        }
    }
    
    $conn->close();
    
    // Preparar mensagem
    $msg = "$importados aluno(s) importado(s) com sucesso.";
    if (!empty($erros)) {
        $msg .= " " . count($erros) . " erro(s): " . implode(" | ", array_slice($erros, 0, 3));
    }
    
    $_SESSION['success'] = $msg;
    
} catch (Exception $e) {
    $_SESSION['error'] = 'Erro ao importar: ' . $e->getMessage();
}

if (ob_get_level()) { @ob_end_clean(); }
header('Location: ' . APP_URL . '/parceiro/alunos-parceiro.php');
exit;

