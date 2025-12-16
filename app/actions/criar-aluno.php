<?php
/**
 * Criar Aluno - Sistema de Certificados
 * Ação para criar novo aluno (Parceiro)
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

    // Verificar se email já existe para este parceiro
    $stmt = $conn->prepare("SELECT id FROM alunos WHERE email = ? AND parceiro_id = ?");
    if ($stmt) {
        $stmt->bind_param("si", $email, $parceiro_id);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $_SESSION['error'] = 'Email já cadastrado para este parceiro';
            $stmt->close();
            $conn->close();
            header('Location: ' . APP_URL . '/parceiro/alunos-parceiro.php');
            exit;
        }
        $stmt->close();
    }

    // Inserir aluno
    $ativo = 1;
    $stmt = $conn->prepare("
        INSERT INTO alunos (parceiro_id, nome, email, cpf, telefone, data_nascimento, endereco, cidade, estado, cep, ativo, criado_em)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
    ");

    if ($stmt) {
        $stmt->bind_param("isssssssssi", $parceiro_id, $nome, $email, $cpf, $telefone, $data_nascimento, $endereco, $cidade, $estado, $cep, $ativo);

        if ($stmt->execute()) {
            $aluno_id = $stmt->insert_id;

            // Vincular cursos se fornecidos
            $cursos = $_POST['cursos'] ?? [];
            if (!empty($cursos) && is_array($cursos)) {
                $stmt_curso = $conn->prepare("
                    INSERT INTO inscricoes_alunos (aluno_id, curso_id, parceiro_id, status, data_inscricao)
                    VALUES (?, ?, ?, 'inscrito', NOW())
                ");

                if ($stmt_curso) {
                    foreach ($cursos as $curso_id) {
                        $curso_id = intval($curso_id);
                        $stmt_curso->bind_param("iii", $aluno_id, $curso_id, $parceiro_id);
                        if ($stmt_curso->execute()) {
                            $inscricao_id = $stmt_curso->insert_id;
                            // Sincronizar inscrição para o EAD
                            sincronizarInscricaoEAD($inscricao_id);
                        }
                    }
                    $stmt_curso->close();
                }
            }

            // Sincronizar aluno para o EAD
            sincronizarAlunoEAD($aluno_id);

            $_SESSION['success'] = 'Aluno criado com sucesso!';
        } else {
            $_SESSION['error'] = 'Erro ao criar aluno: ' . $stmt->error;
        }
        $stmt->close();
    }

    $conn->close();

} catch (Exception $e) {
    error_log("Erro ao criar aluno: " . $e->getMessage());
    $_SESSION['error'] = 'Erro ao criar aluno: ' . $e->getMessage();
}

// Encerrar buffer se ativo
if (ob_get_level()) { @ob_end_clean(); }

$redirectUrl = APP_URL . '/parceiro/alunos-parceiro.php';

// Se ainda é possível enviar cabeçalhos, faz via header()
if (!headers_sent()) {
    header('Location: ' . $redirectUrl, true, 302);
    exit;
}

// Fallback seguro via HTML/JS quando cabeçalhos já foram enviados (ex.: BOM)
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

