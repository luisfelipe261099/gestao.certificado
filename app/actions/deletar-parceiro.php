<?php
/**
 * ============================================================================
 * DELETAR PARCEIRO - SISTEMA DE CERTIFICADOS
 * ============================================================================
 *
 * Este arquivo é uma "ação" - ele faz algo quando o admin clica em "Deletar Parceiro"
 *
 * O que faz:
 * 1. Verifica se está logado e é admin
 * 2. Recebe o ID do parceiro pela URL
 * 3. Verifica se o parceiro existe
 * 4. Deleta o parceiro do banco de dados
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
if (!isAuthenticated() || !hasRole(ROLE_ADMIN)) {
    redirect(APP_URL . '/login.php');
}

// ============================================================================
// PASSO 2: PEGAR O ID DO PARCEIRO DA URL
// ============================================================================
// $_GET['id'] = pega o ID da URL (ex: deletar-parceiro.php?id=5)
// isset() = verifica se existe
// empty() = verifica se está vazio
if (!isset($_GET['id']) || empty($_GET['id'])) {
    $_SESSION['error'] = 'ID do parceiro não fornecido.';
    redirect(APP_URL . '/admin/parceiros-admin.php');
}

// intval() = converte para inteiro (segurança)
$parceiro_id = intval($_GET['id']);

// Conecta ao banco de dados
$conn = getDBConnection();

// ============================================================================
// PASSO 3: TENTAR DELETAR O PARCEIRO (COM TRATAMENTO DE ERROS)
// ============================================================================
try {
    // ========================================================================
    // PASSO 3.1: VERIFICAR SE O PARCEIRO EXISTE
    // ========================================================================
    // Antes de deletar, verifica se o parceiro existe
    $stmt = $conn->prepare("SELECT id FROM parceiros WHERE id = ?");
    $stmt->bind_param("i", $parceiro_id);
    $stmt->execute();
    $result = $stmt->get_result();

    // Se não encontrou, mostra erro
    if ($result->num_rows === 0) {
        $_SESSION['error'] = 'Parceiro não encontrado.';
        redirect(APP_URL . '/admin/parceiros-admin.php');
    }

    $stmt->close();

    // ========================================================================
    // PASSO 3.1.5: DELETAR DEPENDÊNCIAS (EVITAR ERRO DE FK)
    // ========================================================================
    // Lista de tabelas que dependem de parceiro_id
    $tables = [
        'receitas',
        'asaas_cobrancas',
        'asaas_boletos',
        'faturas',
        'contratos',
        'log_renovacoes',
        'solicitacoes_planos',
        'preferencias_pagamento',
        'usuarios_parceiro',
        'assinaturas' // Deletar assinaturas manualmente também
    ];

    foreach ($tables as $table) {
        // Verificar se tabela existe
        $check = $conn->query("SHOW TABLES LIKE '$table'");
        if ($check && $check->num_rows > 0) {
            // Verificar se tem parceiro_id
            $colCheck = $conn->query("SHOW COLUMNS FROM $table LIKE 'parceiro_id'");
            if ($colCheck && $colCheck->num_rows > 0) {
                $stmt_del = $conn->prepare("DELETE FROM $table WHERE parceiro_id = ?");
                if ($stmt_del) {
                    $stmt_del->bind_param("i", $parceiro_id);
                    $stmt_del->execute();
                    $stmt_del->close();
                }
            }
        }
    }

    // ========================================================================
    // PASSO 3.2: DELETAR O PARCEIRO
    // ========================================================================
    // DELETE = remove um registro
    // FROM parceiros = da tabela "parceiros"
    // WHERE id = ? = onde o ID é igual ao ID fornecido
    $stmt = $conn->prepare("DELETE FROM parceiros WHERE id = ?");
    $stmt->bind_param("i", $parceiro_id);

    // Executa a deleção
    if ($stmt->execute()) {
        $_SESSION['success'] = 'Parceiro deletado com sucesso!';
    } else {
        $_SESSION['error'] = 'Erro ao deletar parceiro: ' . $conn->error;
    }

    $stmt->close();

    // ============================================================================
// PASSO 4: SE HOUVER EXCEÇÃO (ERRO), CAPTURA E MOSTRA MENSAGEM
// ============================================================================
} catch (Exception $e) {
    $_SESSION['error'] = 'Erro ao processar: ' . $e->getMessage();
}

// Fecha a conexão com o banco
$conn->close();

// ============================================================================
// PASSO 5: REDIRECIONAR DE VOLTA PARA A LISTA DE PARCEIROS
// ============================================================================
redirect(APP_URL . '/admin/parceiros-admin.php');
?>