<?php
/**
 * Editar Aluno - Sistema de Certificados
 * Ação para editar aluno existente (Parceiro)
 */

// Limpar qualquer output anterior
ob_start();

require_once '../config/config.php';
require_once '../hooks/sincronizar-aluno.php';

// Verificar autenticação e permissão
if (!isAuthenticated() || !hasRole(ROLE_PARCEIRO)) {
    $_SESSION['error'] = 'Acesso negado. Você precisa estar autenticado como parceiro.';
    header('Location: ' . APP_URL . '/login.php');
    exit;
}

// Verificar método POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $_SESSION['error'] = 'Método não permitido';
    header('Location: ' . APP_URL . '/parceiro/alunos-parceiro.php');
    exit;
}

// Validar dados
$id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
$nome = sanitize($_POST['nome'] ?? '');
$email = sanitize($_POST['email'] ?? '');
$cpf = sanitize($_POST['cpf'] ?? '');
$telefone = sanitize($_POST['telefone'] ?? '');
$data_nascimento = sanitize($_POST['data_nascimento'] ?? '');
$endereco = sanitize($_POST['endereco'] ?? '');
$cidade = sanitize($_POST['cidade'] ?? '');
$estado = sanitize($_POST['estado'] ?? '');
$cep = sanitize($_POST['cep'] ?? '');

$errors = [];

if (!$id) {
    $errors[] = 'ID do aluno inválido';
}

if (empty($nome)) {
    $errors[] = 'Nome é obrigatório';
}

if (empty($email) || !isValidEmail($email)) {
    $errors[] = 'Email inválido';
}

// Se houver erros, redirecionar com mensagem
if (!empty($errors)) {
    $_SESSION['error'] = implode(', ', $errors);
    header('Location: ' . APP_URL . '/parceiro/alunos-parceiro.php');
    exit;
}

try {
    $conn = getDBConnection();
    $user = getCurrentUser();
    $parceiro_id = $user['parceiro_id'] ?? $user['id'];

    // Verificar se o aluno pertence ao parceiro
    $stmt = $conn->prepare("SELECT id FROM alunos WHERE id = ? AND parceiro_id = ?");
    $stmt->bind_param("ii", $id, $parceiro_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        $_SESSION['error'] = 'Aluno não encontrado ou não pertence a este parceiro.';
        $stmt->close();
        $conn->close();
        header('Location: ' . APP_URL . '/parceiro/alunos-parceiro.php');
        exit;
    }
    $stmt->close();

    // Verificar se email já existe para OUTRO aluno deste parceiro
    $stmt = $conn->prepare("SELECT id FROM alunos WHERE email = ? AND parceiro_id = ? AND id != ?");
    if ($stmt) {
        $stmt->bind_param("sii", $email, $parceiro_id, $id);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $_SESSION['error'] = 'Email já cadastrado para outro aluno.';
            $stmt->close();
            $conn->close();
            header('Location: ' . APP_URL . '/parceiro/alunos-parceiro.php');
            exit;
        }
        $stmt->close();
    }

    // Atualizar aluno
    $stmt = $conn->prepare("
        UPDATE alunos 
        SET nome = ?, email = ?, cpf = ?, telefone = ?, data_nascimento = ?, endereco = ?, cidade = ?, estado = ?, cep = ?
        WHERE id = ? AND parceiro_id = ?
    ");

    if ($stmt) {
        $stmt->bind_param("sssssssssii", $nome, $email, $cpf, $telefone, $data_nascimento, $endereco, $cidade, $estado, $cep, $id, $parceiro_id);

        if ($stmt->execute()) {
            // Sincronizar aluno para o EAD
            sincronizarAlunoEAD($id);

            $_SESSION['success'] = 'Aluno atualizado com sucesso!';
        } else {
            $_SESSION['error'] = 'Erro ao atualizar aluno: ' . $stmt->error;
        }
        $stmt->close();
    }

    $conn->close();

} catch (Exception $e) {
    error_log("Erro ao atualizar aluno: " . $e->getMessage());
    $_SESSION['error'] = 'Erro ao atualizar aluno: ' . $e->getMessage();
}

// Encerrar buffer se ativo
if (ob_get_level()) {
    @ob_end_clean();
}

$redirectUrl = APP_URL . '/parceiro/alunos-parceiro.php';

// Se ainda é possível enviar cabeçalhos, faz via header()
if (!headers_sent()) {
    header('Location: ' . $redirectUrl, true, 302);
    exit;
}

// Fallback seguro via HTML/JS quando cabeçalhos já foram enviados
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