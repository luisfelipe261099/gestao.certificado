<?php
/**
 * Atualizar Template de Certificado (Parceiro)
 */

ob_start();
require_once '../config/config.php';

try {
    if (!isAuthenticated() || !hasRole(ROLE_PARCEIRO)) {
        $_SESSION['error'] = 'Acesso negado. Faça login como parceiro.';
        if (ob_get_level()) {
            @ob_end_clean();
        }
        header('Location: ' . APP_URL . '/login.php');
        exit;
    }

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        $_SESSION['error'] = 'Método não permitido.';
        if (ob_get_level()) {
            @ob_end_clean();
        }
        header('Location: ' . APP_URL . '/parceiro/templates-parceiro.php');
        exit;
    }

    $conn = getDBConnection();
    $user = getCurrentUser();
    $parceiro_id = $user['parceiro_id'] ?? $user['id'];

    // Coletar e sanitizar dados
    $id = isset($_POST['id']) ? (int) $_POST['id'] : 0;
    $nome = trim($_POST['nome'] ?? '');
    $descricao = trim($_POST['descricao'] ?? '');
    $ativo = isset($_POST['ativo']) && $_POST['ativo'] == '1' ? 1 : 0;

    $largura_mm = isset($_POST['largura_mm']) ? (int) $_POST['largura_mm'] : null;
    $altura_mm = isset($_POST['altura_mm']) ? (int) $_POST['altura_mm'] : null;
    $posicao_nome_x = isset($_POST['posicao_nome_x']) ? (int) $_POST['posicao_nome_x'] : null;
    $posicao_nome_y = isset($_POST['posicao_nome_y']) ? (int) $_POST['posicao_nome_y'] : null;
    $posicao_curso_x = isset($_POST['posicao_curso_x']) ? (int) $_POST['posicao_curso_x'] : null;
    $posicao_curso_y = isset($_POST['posicao_curso_y']) ? (int) $_POST['posicao_curso_y'] : null;
    $posicao_data_x = isset($_POST['posicao_data_x']) ? (int) $_POST['posicao_data_x'] : null;
    $posicao_data_y = isset($_POST['posicao_data_y']) ? (int) $_POST['posicao_data_y'] : null;

    if ($id <= 0 || $nome === '' || $descricao === '') {
        throw new Exception('Dados inválidos.');
    }

    // Verificar propriedade do template
    $stmt = $conn->prepare('SELECT id FROM templates_certificados WHERE id = ? AND parceiro_id = ? LIMIT 1');
    if (!$stmt) {
        throw new Exception('Falha ao preparar verificação do template.');
    }
    $stmt->bind_param('ii', $id, $parceiro_id);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($res->num_rows === 0) {
        throw new Exception('Template não encontrado para este parceiro.');
    }
    $stmt->close();

    // Processar upload de arquivo (VERSO) se existir - OPCIONAL
    $arquivo_verso_url = null;
    $update_verso = false;

    if (isset($_FILES['arquivo_verso'])) {
        if ($_FILES['arquivo_verso']['error'] === UPLOAD_ERR_OK) {
            $arquivo_verso_tmp = $_FILES['arquivo_verso']['tmp_name'];
            $arquivo_verso_nome = basename($_FILES['arquivo_verso']['name']);
            $arquivo_verso_ext = pathinfo($arquivo_verso_nome, PATHINFO_EXTENSION);

            // Validar extensão (aceita PDF e imagens JPG/PNG)
            $extensoes_permitidas = ['pdf', 'jpg', 'jpeg', 'png'];
            if (!in_array(strtolower($arquivo_verso_ext), $extensoes_permitidas)) {
                throw new Exception('Tipo de arquivo do verso não permitido. Envie um PDF, JPG ou PNG.');
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
                $update_verso = true;
            } else {
                throw new Exception('Erro ao mover o arquivo enviado. Verifique as permissões da pasta.');
            }
        } elseif ($_FILES['arquivo_verso']['error'] !== UPLOAD_ERR_NO_FILE) {
            // Se houve erro e não foi "nenhum arquivo enviado"
            $upload_errors = [
                UPLOAD_ERR_INI_SIZE => 'O arquivo excede o tamanho máximo permitido pelo servidor.',
                UPLOAD_ERR_FORM_SIZE => 'O arquivo excede o tamanho máximo permitido pelo formulário.',
                UPLOAD_ERR_PARTIAL => 'O upload do arquivo foi feito apenas parcialmente.',
                UPLOAD_ERR_NO_TMP_DIR => 'Pasta temporária ausente.',
                UPLOAD_ERR_CANT_WRITE => 'Falha ao escrever o arquivo no disco.',
                UPLOAD_ERR_EXTENSION => 'Uma extensão do PHP interrompeu o upload do arquivo.'
            ];
            $error_code = $_FILES['arquivo_verso']['error'];
            $error_msg = $upload_errors[$error_code] ?? 'Erro desconhecido no upload do arquivo.';
            throw new Exception('Erro no upload do verso: ' . $error_msg);
        }
    }

    // Se marcar ativo, desativar os demais do parceiro
    if ($ativo === 1) {
        $stmt = $conn->prepare('UPDATE templates_certificados SET ativo = 0 WHERE parceiro_id = ? AND id <> ?');
        if ($stmt) {
            $stmt->bind_param('ii', $parceiro_id, $id);
            $stmt->execute();
            $stmt->close();
        }
    }

    // Coletar novos campos de visibilidade e estilo
    $exibir_nome = isset($_POST['exibir_nome']) ? 1 : 0;
    $tamanho_fonte_nome = isset($_POST['tamanho_fonte_nome']) ? (int) $_POST['tamanho_fonte_nome'] : 24;
    $cor_nome = $_POST['cor_nome'] ?? '#000000';

    $exibir_curso = isset($_POST['exibir_curso']) ? 1 : 0;
    $tamanho_fonte_curso = isset($_POST['tamanho_fonte_curso']) ? (int) $_POST['tamanho_fonte_curso'] : 16;
    $cor_curso = $_POST['cor_curso'] ?? '#000000';

    $exibir_data = isset($_POST['exibir_data']) ? 1 : 0;
    $tamanho_fonte_data = isset($_POST['tamanho_fonte_data']) ? (int) $_POST['tamanho_fonte_data'] : 14;
    $cor_data = $_POST['cor_data'] ?? '#000000';

    $exibir_carga_horaria = isset($_POST['exibir_carga_horaria']) ? 1 : 0;
    $tamanho_fonte_carga_horaria = isset($_POST['tamanho_fonte_carga_horaria']) ? (int) $_POST['tamanho_fonte_carga_horaria'] : 12;
    $cor_carga_horaria = $_POST['cor_carga_horaria'] ?? '#000000';

    $exibir_numero_certificado = isset($_POST['exibir_numero_certificado']) ? 1 : 0;
    $tamanho_fonte_numero_certificado = isset($_POST['tamanho_fonte_numero_certificado']) ? (int) $_POST['tamanho_fonte_numero_certificado'] : 12;
    $cor_numero_certificado = $_POST['cor_numero_certificado'] ?? '#000000';

    $fonte_nome = $_POST['fonte_nome'] ?? 'Arial';
    $fonte_curso = $_POST['fonte_curso'] ?? 'Arial';
    $fonte_data = $_POST['fonte_data'] ?? 'Arial';
    $fonte_carga_horaria = $_POST['fonte_carga_horaria'] ?? 'Arial';
    $fonte_numero_certificado = $_POST['fonte_numero_certificado'] ?? 'Arial';

    $posicao_carga_horaria_x = isset($_POST['posicao_carga_horaria_x']) ? (int) $_POST['posicao_carga_horaria_x'] : 0;
    $posicao_carga_horaria_y = isset($_POST['posicao_carga_horaria_y']) ? (int) $_POST['posicao_carga_horaria_y'] : 0;
    $posicao_numero_certificado_x = isset($_POST['posicao_numero_certificado_x']) ? (int) $_POST['posicao_numero_certificado_x'] : 0;
    $posicao_numero_certificado_y = isset($_POST['posicao_numero_certificado_y']) ? (int) $_POST['posicao_numero_certificado_y'] : 0;

    // Atualizar template (incluindo verso se foi enviado)
    if ($update_verso) {
        $sql = 'UPDATE templates_certificados SET nome = ?, descricao = ?, ativo = ?, largura_mm = ?, altura_mm = ?, posicao_nome_x = ?, posicao_nome_y = ?, posicao_curso_x = ?, posicao_curso_y = ?, posicao_data_x = ?, posicao_data_y = ?, arquivo_verso_url = ?, exibir_nome = ?, tamanho_fonte_nome = ?, cor_nome = ?, fonte_nome = ?, exibir_curso = ?, tamanho_fonte_curso = ?, cor_curso = ?, fonte_curso = ?, exibir_data = ?, tamanho_fonte_data = ?, cor_data = ?, fonte_data = ?, exibir_carga_horaria = ?, tamanho_fonte_carga_horaria = ?, cor_carga_horaria = ?, fonte_carga_horaria = ?, posicao_carga_horaria_x = ?, posicao_carga_horaria_y = ?, exibir_numero_certificado = ?, tamanho_fonte_numero_certificado = ?, cor_numero_certificado = ?, fonte_numero_certificado = ?, posicao_numero_certificado_x = ?, posicao_numero_certificado_y = ?, atualizado_em = NOW() WHERE id = ? AND parceiro_id = ?';
    } else {
        $sql = 'UPDATE templates_certificados SET nome = ?, descricao = ?, ativo = ?, largura_mm = ?, altura_mm = ?, posicao_nome_x = ?, posicao_nome_y = ?, posicao_curso_x = ?, posicao_curso_y = ?, posicao_data_x = ?, posicao_data_y = ?, exibir_nome = ?, tamanho_fonte_nome = ?, cor_nome = ?, fonte_nome = ?, exibir_curso = ?, tamanho_fonte_curso = ?, cor_curso = ?, fonte_curso = ?, exibir_data = ?, tamanho_fonte_data = ?, cor_data = ?, fonte_data = ?, exibir_carga_horaria = ?, tamanho_fonte_carga_horaria = ?, cor_carga_horaria = ?, fonte_carga_horaria = ?, posicao_carga_horaria_x = ?, posicao_carga_horaria_y = ?, exibir_numero_certificado = ?, tamanho_fonte_numero_certificado = ?, cor_numero_certificado = ?, fonte_numero_certificado = ?, posicao_numero_certificado_x = ?, posicao_numero_certificado_y = ?, atualizado_em = NOW() WHERE id = ? AND parceiro_id = ?';
    }
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        throw new Exception('Falha ao preparar atualização do template.');
    }

    // Tratar nulls: usar valores NULL via bind (i = int, s = string, pode passar null se allow_null?)
    // Defaults: 2048x1152 (px); posi es 0 para centraliza e7 e3o autom e1tica
    $largura_mm = $largura_mm ?: 2048;
    $altura_mm = $altura_mm ?: 1152;
    $posicao_nome_x = ($posicao_nome_x !== null) ? $posicao_nome_x : 0;
    $posicao_nome_y = ($posicao_nome_y !== null) ? $posicao_nome_y : 0;
    $posicao_curso_x = ($posicao_curso_x !== null) ? $posicao_curso_x : 0;
    $posicao_curso_y = ($posicao_curso_y !== null) ? $posicao_curso_y : 0;
    $posicao_data_x = ($posicao_data_x !== null) ? $posicao_data_x : 0;
    $posicao_data_y = ($posicao_data_y !== null) ? $posicao_data_y : 0;

    if ($update_verso) {
        $stmt->bind_param(
            'ssssssssssssssssssssssssssssssssssssss',
            $nome,
            $descricao,
            $ativo,
            $largura_mm,
            $altura_mm,
            $posicao_nome_x,
            $posicao_nome_y,
            $posicao_curso_x,
            $posicao_curso_y,
            $posicao_data_x,
            $posicao_data_y,
            $arquivo_verso_url,
            $exibir_nome,
            $tamanho_fonte_nome,
            $cor_nome,
            $fonte_nome,
            $exibir_curso,
            $tamanho_fonte_curso,
            $cor_curso,
            $fonte_curso,
            $exibir_data,
            $tamanho_fonte_data,
            $cor_data,
            $fonte_data,
            $exibir_carga_horaria,
            $tamanho_fonte_carga_horaria,
            $cor_carga_horaria,
            $fonte_carga_horaria,
            $posicao_carga_horaria_x,
            $posicao_carga_horaria_y,
            $exibir_numero_certificado,
            $tamanho_fonte_numero_certificado,
            $cor_numero_certificado,
            $fonte_numero_certificado,
            $posicao_numero_certificado_x,
            $posicao_numero_certificado_y,
            $id,
            $parceiro_id
        );
    } else {
        $stmt->bind_param(
            'sssssssssssssssssssssssssssssssssssss',
            $nome,
            $descricao,
            $ativo,
            $largura_mm,
            $altura_mm,
            $posicao_nome_x,
            $posicao_nome_y,
            $posicao_curso_x,
            $posicao_curso_y,
            $posicao_data_x,
            $posicao_data_y,
            $exibir_nome,
            $tamanho_fonte_nome,
            $cor_nome,
            $fonte_nome,
            $exibir_curso,
            $tamanho_fonte_curso,
            $cor_curso,
            $fonte_curso,
            $exibir_data,
            $tamanho_fonte_data,
            $cor_data,
            $fonte_data,
            $exibir_carga_horaria,
            $tamanho_fonte_carga_horaria,
            $cor_carga_horaria,
            $fonte_carga_horaria,
            $posicao_carga_horaria_x,
            $posicao_carga_horaria_y,
            $exibir_numero_certificado,
            $tamanho_fonte_numero_certificado,
            $cor_numero_certificado,
            $fonte_numero_certificado,
            $posicao_numero_certificado_x,
            $posicao_numero_certificado_y,
            $id,
            $parceiro_id
        );
    }

    if (!$stmt->execute()) {
        throw new Exception('Erro ao atualizar template: ' . $stmt->error);
    }
    $stmt->close();

    // Processar campos customizados
    if (isset($_POST['campos']) && is_array($_POST['campos'])) {
        // Limpar campos antigos
        $stmt_delete = $conn->prepare("DELETE FROM template_campos_customizados WHERE template_id = ?");
        if ($stmt_delete) {
            $stmt_delete->bind_param("i", $id);
            $stmt_delete->execute();
            $stmt_delete->close();
        }

        // Inserir novos campos
        foreach ($_POST['campos'] as $campo) {
            $tipo_campo = $campo['tipo_campo'] ?? 'texto';
            $label = trim($campo['label'] ?? '');
            $valor_padrao = trim($campo['valor_padrao'] ?? '');
            $posicao_x = (int) ($campo['posicao_x'] ?? 0);
            $posicao_y = (int) ($campo['posicao_y'] ?? 0);
            $tamanho_fonte = (int) ($campo['tamanho_fonte'] ?? 16);
            $cor_hex = $campo['cor_hex'] ?? '#000000';
            $ordem = (int) ($campo['ordem'] ?? 0);
            $exibir = isset($campo['exibir']) ? 1 : 0;
            $fonte = $campo['fonte'] ?? 'Arial';

            if (!empty($label)) {
                $stmt_insert = $conn->prepare("
                    INSERT INTO template_campos_customizados
                    (template_id, tipo_campo, label, valor_padrao, posicao_x, posicao_y, tamanho_fonte, cor_hex, ordem, exibir, fonte)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                ");

                if ($stmt_insert) {
                    $stmt_insert->bind_param(
                        "isssiiisiss",
                        $id,
                        $tipo_campo,
                        $label,
                        $valor_padrao,
                        $posicao_x,
                        $posicao_y,
                        $tamanho_fonte,
                        $cor_hex,
                        $ordem,
                        $exibir,
                        $fonte
                    );
                    $stmt_insert->execute();
                    $stmt_insert->close();
                }
            }
        }
    }

    $_SESSION['success'] = 'Template atualizado com sucesso!';

    $conn->close();

} catch (Exception $e) {
    error_log('Erro ao atualizar template: ' . $e->getMessage());
    $_SESSION['error'] = $e->getMessage();
}

if (ob_get_level()) {
    @ob_end_clean();
}
$redirectUrl = APP_URL . '/parceiro/templates-parceiro.php';
if (!headers_sent()) {
    header('Location: ' . $redirectUrl);
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

