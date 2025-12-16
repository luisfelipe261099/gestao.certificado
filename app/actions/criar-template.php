<?php
/**
 * Criar Template - Sistema de Certificados
 * Ação para criar novo template de certificado (Parceiro)
 */


// Iniciar buffer de saída para evitar problemas com headers
ob_start();

require_once __DIR__ . '/bootstrap.php';

// Verificar autenticação e permissão
if (!isAuthenticated() || !hasRole(ROLE_PARCEIRO)) {
    $_SESSION['error'] = 'Acesso negado. Você precisa estar autenticado como parceiro.';
    if (ob_get_level()) { @ob_end_clean(); }
    header('Location: ' . APP_URL . '/login.php');
    exit;
}

// Verificar método POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $_SESSION['error'] = 'Método não permitido';
    if (ob_get_level()) { @ob_end_clean(); }
    header('Location: ' . APP_URL . '/parceiro/templates-parceiro.php');
    exit;
}

// Validar dados
$nome = sanitize($_POST['nome'] ?? '');
$descricao = sanitize($_POST['descricao'] ?? '');

$errors = [];

if (empty($nome)) {
    $errors[] = 'Nome é obrigatório';
}

if (empty($descricao)) {
    $errors[] = 'Descrição é obrigatória';
}

// Se houver erros, redirecionar com mensagem
if (!empty($errors)) {
    $_SESSION['error'] = implode(', ', $errors);
    redirect(APP_URL . '/parceiro/templates-parceiro.php');
    exit;
}

try {
    $conn = getDBConnection();
    $user = getCurrentUser();
    $parceiro_id = $user['parceiro_id'] ?? $user['id'];

    // Processar upload de arquivo (FRENTE) se existir
    $arquivo_url = null;
    if (isset($_FILES['arquivo']) && $_FILES['arquivo']['error'] === UPLOAD_ERR_OK) {
        $arquivo_tmp = $_FILES['arquivo']['tmp_name'];
        $arquivo_nome = basename($_FILES['arquivo']['name']);
        $arquivo_ext = pathinfo($arquivo_nome, PATHINFO_EXTENSION);

        // Validar extensão (aceita PDF e imagens JPG/PNG)
        $extensoes_permitidas = ['pdf', 'jpg', 'jpeg', 'png'];
        if (!in_array(strtolower($arquivo_ext), $extensoes_permitidas)) {
            $_SESSION['error'] = 'Tipo de arquivo não permitido. Envie um PDF, JPG ou PNG.';
            redirect(APP_URL . '/parceiro/templates-parceiro.php');
            exit;
        }

        // Criar diretório se não existir
        $upload_dir = __DIR__ . '/../../uploads/templates/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }

        // Salvar arquivo
        $arquivo_novo = $upload_dir . uniqid() . '.' . $arquivo_ext;
        if (move_uploaded_file($arquivo_tmp, $arquivo_novo)) {
            $arquivo_url = APP_URL . '/uploads/templates/' . basename($arquivo_novo);
        }
    }

    // Processar upload de arquivo (VERSO) se existir - OPCIONAL
    $arquivo_verso_url = null;
    if (isset($_FILES['arquivo_verso']) && $_FILES['arquivo_verso']['error'] === UPLOAD_ERR_OK) {
        $arquivo_verso_tmp = $_FILES['arquivo_verso']['tmp_name'];
        $arquivo_verso_nome = basename($_FILES['arquivo_verso']['name']);
        $arquivo_verso_ext = pathinfo($arquivo_verso_nome, PATHINFO_EXTENSION);

        // Validar extensão (aceita PDF e imagens JPG/PNG)
        $extensoes_permitidas = ['pdf', 'jpg', 'jpeg', 'png'];
        if (!in_array(strtolower($arquivo_verso_ext), $extensoes_permitidas)) {
            $_SESSION['error'] = 'Tipo de arquivo do verso não permitido. Envie um PDF, JPG ou PNG.';
            redirect(APP_URL . '/parceiro/templates-parceiro.php');
            exit;
        }

        // Criar diretório se não existir
        $upload_dir = __DIR__ . '/../../uploads/templates/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }

        // Salvar arquivo
        $arquivo_verso_novo = $upload_dir . 'verso_' . uniqid() . '.' . $arquivo_verso_ext;
        if (move_uploaded_file($arquivo_verso_tmp, $arquivo_verso_novo)) {
            $arquivo_verso_url = APP_URL . '/uploads/templates/' . basename($arquivo_verso_novo);
        }
    }

    // Inserir template
    $ativo = 1;
    $stmt = $conn->prepare("
        INSERT INTO templates_certificados (parceiro_id, nome, descricao, arquivo_url, arquivo_verso_url, ativo, criado_em)
        VALUES (?, ?, ?, ?, ?, ?, NOW())
    ");

    if ($stmt) {
        $stmt->bind_param("issssi", $parceiro_id, $nome, $descricao, $arquivo_url, $arquivo_verso_url, $ativo);

        if ($stmt->execute()) {
            $_SESSION['success'] = 'Template criado com sucesso!';
        } else {
            $_SESSION['error'] = 'Erro ao criar template: ' . $stmt->error;
        }
        $stmt->close();
    }

    $conn->close();

} catch (Exception $e) {
    error_log("Erro ao criar template: " . $e->getMessage());
    $_SESSION['error'] = 'Erro ao criar template: ' . $e->getMessage();
}

// Redirecionar com fallback seguro
if (ob_get_level()) { @ob_end_clean(); }
$redirectUrl = APP_URL . '/parceiro/templates-parceiro.php';
if (!headers_sent()) {
    header('Location: ' . $redirectUrl, true, 302);
    exit;
}
echo '<!DOCTYPE html><html><head><meta charset="utf-8">'
   . '<meta http-equiv="refresh" content="0;url=' . htmlspecialchars($redirectUrl, ENT_QUOTES, 'UTF-8') . '">'
   . '<title>Redirecionando...</title>'
   . '</head><body>'
   . 'Redirecionando... Se não redirecionar, '
   . '<a href="' . htmlspecialchars($redirectUrl, ENT_QUOTES, 'UTF-8') . '">clique aqui</a>.'
   . '<script>try{window.location.replace(' . json_encode($redirectUrl) . ');}catch(e){}</script>'
   . '</body></html>';
exit;
?>

