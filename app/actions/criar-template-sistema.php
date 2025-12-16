<?php
/**
 * Action: Criar Template do Sistema
 * Cria template padrão visível para todos os parceiros
 */

require_once '../config/config.php';

if (!isAuthenticated() || !hasRole(ROLE_ADMIN)) {
    $_SESSION['error'] = 'Acesso negado.';
    header('Location: ' . APP_URL . '/login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $_SESSION['error'] = 'Método inválido.';
    header('Location: ' . APP_URL . '/admin/templates-sistema.php');
    exit;
}

$nome = trim($_POST['nome'] ?? '');
$descricao = trim($_POST['descricao'] ?? '');
$ativo = isset($_POST['ativo']) && $_POST['ativo'] == '1' ? 1 : 0;

if (empty($nome)) {
    $_SESSION['error'] = 'Nome do template é obrigatório.';
    header('Location: ' . APP_URL . '/admin/templates-sistema.php');
    exit;
}

// Upload da imagem
$upload_dir = __DIR__ . '/../../uploads/templates/';
if (!file_exists($upload_dir)) {
    mkdir($upload_dir, 0777, true);
}

try {
    $conn = getDBConnection();

    // Processar upload
    if (!isset($_FILES['arquivo']) || $_FILES['arquivo']['error'] != UPLOAD_ERR_OK) {
        throw new Exception('Nenhum arquivo foi enviado ou houve erro no upload.');
    }

    $file = $_FILES['arquivo'];
    $allowed = ['image/jpeg', 'image/jpg', 'image/png'];

    if (!in_array($file['type'], $allowed)) {
        throw new Exception('Apenas imagens JPG e PNG são permitidas.');
    }

    if ($file['size'] > 10 * 1024 * 1024) { // 10MB
        throw new Exception('O arquivo é muito grande. Máximo: 10MB');
    }

    // Gerar nome único
    $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = 'template_sistema_' . uniqid() . '.' . $ext;
    $filepath = $upload_dir . $filename;

    if (!move_uploaded_file($file['tmp_name'], $filepath)) {
        throw new Exception('Erro ao salvar arquivo.');
    }

    // Pegar dimensões da imagem
    $size = getimagesize($filepath);
    $largura = $size[0];
    $altura = $size[1];

    // URL do arquivo
    $arquivo_url = APP_URL . '/uploads/templates/' . $filename;

    // Inserir no banco (SEM coluna 'arquivo', só 'arquivo_url')
    $stmt = $conn->prepare("
        INSERT INTO templates_certificados 
        (parceiro_id, nome, descricao, arquivo_url, largura_mm, altura_mm, ativo, template_sistema, criado_em)
        VALUES 
        (NULL, ?, ?, ?, ?, ?, ?, 1, NOW())
    ");

    $stmt->bind_param(
        "sssddi",
        $nome,
        $descricao,
        $arquivo_url,
        $largura,
        $altura,
        $ativo
    );

    if (!$stmt->execute()) {
        throw new Exception('Erro ao salvar template no banco de dados.');
    }

    $template_id = $conn->insert_id;
    $stmt->close();

    $_SESSION['success'] = "Template do sistema '{$nome}' criado com sucesso! ID: {$template_id}";
    header('Location: ' . APP_URL . '/admin/templates-sistema.php');
    exit;

} catch (Exception $e) {
    // Remover arquivo se foi feito upload
    if (isset($filepath) && file_exists($filepath)) {
        @unlink($filepath);
    }

    $_SESSION['error'] = 'Erro ao criar template: ' . $e->getMessage();
    header('Location: ' . APP_URL . '/admin/templates-sistema.php');
    exit;
}
?>