<?php
/**
 * ============================================================================
 * CRIAR PARCEIRO - SISTEMA DE CERTIFICADOS
 * ============================================================================
 *
 * Este arquivo é uma "ação" - ele faz algo quando o admin clica em "Criar Parceiro"
 *
 * O que faz:
 * 1. Recebe os dados do formulário
 * 2. Valida os dados
 * 3. Cria o parceiro no banco de dados
 * 4. Cria automaticamente: assinatura, fatura, pagamento e receita
 * 5. Redireciona de volta para a lista de parceiros
 *
 * Padrão MVP - Camada de Ação
 * ============================================================================
 */

// Inclui o arquivo de configuração
require_once __DIR__ . '/bootstrap.php';

// ============================================================================
// PASSO 1: VERIFICAR AUTENTICAÇÃO E PERMISSÃO
// ============================================================================
// Verifica se está logado e se é admin
// Se não for, mostra erro 403 (Acesso Negado)
if (!isAuthenticated() || !hasRole(ROLE_ADMIN)) {
    http_response_code(403);
    die('Acesso negado');
}

// ============================================================================
// PASSO 2: VERIFICAR SE FOI ENVIADO POR POST
// ============================================================================
// POST = método de envio de formulário (seguro)
// Se não for POST, mostra erro 405 (Método não permitido)
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    die('Método não permitido');
}

// ============================================================================
// PASSO 3: PEGAR E LIMPAR OS DADOS DO FORMULÁRIO
// ============================================================================
// sanitize() = limpa os dados para segurança
// ?? '' = se não existir, usa string vazia
$nome_empresa = sanitize($_POST['nome_empresa'] ?? '');
$cnpj = sanitize($_POST['cnpj'] ?? '');
$email = sanitize($_POST['email'] ?? '');
$plano_id = isset($_POST['plano_id']) ? (int) $_POST['plano_id'] : 0;  // Converte para inteiro
$telefone = sanitize($_POST['telefone'] ?? '');
$endereco = sanitize($_POST['endereco'] ?? '');
$cidade = sanitize($_POST['cidade'] ?? '');
$estado = sanitize($_POST['estado'] ?? '');
$cep = sanitize($_POST['cep'] ?? '');

// ============================================================================
// PASSO 4: VALIDAR OS DADOS
// ============================================================================
// Cria um array para guardar erros
$errors = [];

// Verifica se nome da empresa foi preenchido
if (empty($nome_empresa)) {
    $errors[] = 'Nome da empresa é obrigatório';
}

// Verifica se CNPJ foi preenchido
if (empty($cnpj)) {
    $errors[] = 'CNPJ é obrigatório';
}

// Verifica se email foi preenchido e se é válido
if (empty($email) || !isValidEmail($email)) {
    $errors[] = 'Email inválido';
}

// Verifica se plano foi selecionado
if (empty($plano_id)) {
    $errors[] = 'Plano é obrigatório';
}

// ============================================================================
// PASSO 5: SE HOUVER ERROS, REDIRECIONA COM MENSAGEM
// ============================================================================
// Se há erros, junta todos em uma string e guarda na sessão
if (!empty($errors)) {
    $_SESSION['error'] = implode(', ', $errors);  // Junta com vírgula
    redirect(APP_URL . '/admin/parceiros-admin.php');
    exit;
}

// ============================================================================
// PASSO 6: TENTAR CRIAR O PARCEIRO (COM TRATAMENTO DE ERROS)
// ============================================================================
// try = tenta fazer algo
// catch = se der erro, faz algo
try {
    // Conecta ao banco de dados
    $conn = getDBConnection();

    // ========================================================================
    // PASSO 6.1: VERIFICAR SE EMAIL OU CNPJ JÁ EXISTEM
    // ========================================================================
    // Não queremos dois parceiros com o mesmo email ou CNPJ
    $stmt = $conn->prepare("SELECT id FROM parceiros WHERE email = ? OR cnpj = ?");
    if ($stmt) {
        // "ss" = dois strings (email e cnpj)
        $stmt->bind_param("ss", $email, $cnpj);
        $stmt->execute();
        $result = $stmt->get_result();

        // Se encontrou algum resultado, significa que já existe
        if ($result->num_rows > 0) {
            $_SESSION['error'] = 'Email ou CNPJ já cadastrado no sistema';
            $stmt->close();
            $conn->close();
            redirect(APP_URL . '/admin/parceiros-admin.php');
            exit;
        }
        $stmt->close();
    }

    // ========================================================================
    // PASSO 6.2: INSERIR O PARCEIRO NO BANCO DE DADOS
    // ========================================================================
    // INSERT = adiciona um novo registro
    // INTO parceiros = na tabela "parceiros"
    // VALUES = com esses valores
    // NOW() = data e hora atual
    $ativo = 1;  // 1 = ativo, 0 = inativo
    $stmt = $conn->prepare("
        INSERT INTO parceiros (nome_empresa, cnpj, email, telefone, endereco, cidade, estado, cep, ativo, criado_em)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
    ");

    if ($stmt) {
        // "ssssssssi" = 8 strings e 1 inteiro
        $stmt->bind_param("ssssssssi", $nome_empresa, $cnpj, $email, $telefone, $endereco, $cidade, $estado, $cep, $ativo);

        // Executa a inserção
        if ($stmt->execute()) {
            // insert_id = ID do novo parceiro criado
            $parceiro_id = $stmt->insert_id;

            // ================================================================
            // PASSO 6.3: BUSCAR O VALOR DO PLANO SELECIONADO
            // ================================================================
            // Precisamos do valor do plano para criar a fatura, pagamento e receita
            $stmt_plano = $conn->prepare("SELECT valor FROM planos WHERE id = ?");
            $valor_plano = '0.00';  // Valor padrão
            if ($stmt_plano) {
                $stmt_plano->bind_param("i", $plano_id);
                $stmt_plano->execute();
                $result_plano = $stmt_plano->get_result();
                if ($result_plano->num_rows > 0) {
                    $row_plano = $result_plano->fetch_assoc();
                    $valor_plano = (string) $row_plano['valor'];  // Converte para string
                }
                $stmt_plano->close();
            }

            // ================================================================
            // PASSO 6.4: CRIAR ASSINATURA AUTOMATICAMENTE
            // ================================================================
            // Quando um parceiro é criado, ele já começa com uma assinatura
            // A assinatura dura 1 ano
            $data_inicio = date('Y-m-d');                          // Hoje
            $data_vencimento = date('Y-m-d', strtotime('+1 year')); // Daqui a 1 ano
            $status_assinatura = 'pendente'; // Alterado para pendente para exigir pagamento

            $stmt_assinatura = $conn->prepare("
                INSERT INTO assinaturas (parceiro_id, plano_id, data_inicio, data_vencimento, status, criado_em)
                VALUES (?, ?, ?, ?, ?, NOW())
            ");

            $assinatura_id = 0;
            if ($stmt_assinatura) {
                $stmt_assinatura->bind_param("iisss", $parceiro_id, $plano_id, $data_inicio, $data_vencimento, $status_assinatura);
                if ($stmt_assinatura->execute()) {
                    $assinatura_id = $stmt_assinatura->insert_id;
                }
                $stmt_assinatura->close();
            }

            // ================================================================
            // PASSO 6.5: CRIAR FATURA AUTOMATICAMENTE
            // ================================================================
            // Fatura = documento de cobrança
            // Vence em 30 dias
            $numero_fatura = 'FAT-' . $parceiro_id . '-' . date('YmdHis');  // Número único
            $valor_fatura = $valor_plano;
            $data_emissao = date('Y-m-d');                          // Hoje
            $data_vencimento = date('Y-m-d', strtotime('+30 days')); // Daqui a 30 dias
            $status_fatura = 'pendente';
            $descricao_fatura = 'Fatura inicial - ' . $nome_empresa;

            $stmt_fatura = $conn->prepare("
                INSERT INTO faturas (parceiro_id, assinatura_id, numero_fatura, valor, status, data_emissao, data_vencimento, descricao, criado_em)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())
            ");

            if ($stmt_fatura) {
                $stmt_fatura->bind_param("iissssss", $parceiro_id, $assinatura_id, $numero_fatura, $valor_fatura, $status_fatura, $data_emissao, $data_vencimento, $descricao_fatura);
                $stmt_fatura->execute();
                $stmt_fatura->close();
            }

            // ================================================================
            // PASSO 6.6: CRIAR PAGAMENTO AUTOMATICAMENTE
            // ================================================================
            // Pagamento = registro de que o parceiro deve pagar
            // Método padrão = boleto
            $valor_pagamento = $valor_plano;
            $data_pagamento = date('Y-m-d');
            $status_pagamento = 'pendente';
            $descricao_pagamento = 'Pagamento inicial - ' . $nome_empresa;
            $metodo_pagamento = 'boleto';

            $stmt_pagamento = $conn->prepare("
                INSERT INTO pagamentos (parceiro_id, descricao, valor, status, data_pagamento, metodo, criado_em)
                VALUES (?, ?, ?, ?, ?, ?, NOW())
            ");

            if ($stmt_pagamento) {
                $stmt_pagamento->bind_param("isssss", $parceiro_id, $descricao_pagamento, $valor_pagamento, $status_pagamento, $data_pagamento, $metodo_pagamento);
                $stmt_pagamento->execute();
                $stmt_pagamento->close();
            }

            // ================================================================
            // PASSO 6.7: CRIAR RECEITA AUTOMATICAMENTE
            // ================================================================
            // Receita = dinheiro que entra no sistema
            // Tipo = assinatura (porque é a primeira vez)
            $valor_receita = $valor_plano;
            $data_receita = date('Y-m-d');
            $status_receita = 'pendente';
            $tipo_receita = 'assinatura';

            $stmt_receita = $conn->prepare("
                INSERT INTO receitas (parceiro_id, valor, status, data_receita, tipo, criado_em)
                VALUES (?, ?, ?, ?, ?, NOW())
            ");

            if ($stmt_receita) {
                $stmt_receita->bind_param("issss", $parceiro_id, $valor_receita, $status_receita, $data_receita, $tipo_receita);
                $stmt_receita->execute();
                $stmt_receita->close();
            }

            // ================================================================
            // PASSO 6.8: MOSTRAR MENSAGEM DE SUCESSO
            // ================================================================
            $_SESSION['success'] = "Parceiro criado com sucesso! Assinatura (1 ano), fatura, pagamento e receita criados automaticamente com valor do contrato.";
        } else {
            // Se não conseguiu executar, mostra erro
            $_SESSION['error'] = 'Erro ao criar parceiro: ' . $stmt->error;
        }
        $stmt->close();
    }

    $conn->close();

    // ============================================================================
// PASSO 7: SE HOUVER EXCEÇÃO (ERRO), CAPTURA E MOSTRA MENSAGEM
// ============================================================================
} catch (Exception $e) {
    error_log("Erro ao criar parceiro: " . $e->getMessage());
    $_SESSION['error'] = 'Erro ao criar parceiro: ' . $e->getMessage();
}

// ============================================================================
// PASSO 8: REDIRECIONAR DE VOLTA PARA A LISTA DE PARCEIROS
// ============================================================================
redirect(APP_URL . '/admin/parceiros-admin.php');
?>