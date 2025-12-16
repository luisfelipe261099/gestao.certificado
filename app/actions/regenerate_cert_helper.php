<?php
/**
 * Script para regenerar certificado existente
 * Isso é necessário quando um certificado foi gerado com erro e precisa ser recriado
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

$_SERVER['HTTP_HOST'] = 'localhost';

session_start();
// Mock auth
$_SESSION['user_id'] = 1;
$_SESSION['user_role'] = 'parceiro';
$_SESSION['parceiro_id'] = 31;

// ID do certificado a regenerar
$cert_id = 53;

require_once __DIR__ . '/../config/config.php';

try {
    $conn = getDBConnection();

    // Buscar dados do certificado
    $stmt = $conn->prepare("SELECT c.*, a.nome as aluno_nome, cu.nome as curso_nome, cu.carga_horaria, ia.data_conclusao 
                            FROM certificados c
                            JOIN alunos a ON a.id = c.aluno_id
                            JOIN cursos cu ON cu.id = c.curso_id
                            LEFT JOIN inscricoes_alunos ia ON ia.aluno_id = c.aluno_id AND ia.curso_id = c.curso_id
                            WHERE c.id = ?");
    $stmt->bind_param("i", $cert_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        die("Certificate not found!\n");
    }

    $cert = $result->fetch_assoc();

    echo "Certificate found:\n";
    echo "- ID: {$cert['id']}\n";
    echo "- Student: {$cert['aluno_nome']}\n";
    echo "- Course: {$cert['curso_nome']}\n";
    echo "- Template ID: {$cert['template_id']}\n";
    echo "\nTo regenerate this certificate, please:\n";
    echo "1. Go to: http://localhost/gestao.certificado/parceiro/gerar-certificados.php\n";
    echo "2. Select the student: {$cert['aluno_nome']}\n";
    echo "3. Select the course: {$cert['curso_nome']}\n";
    echo "4. Click 'Gerar Certificado'\n";
    echo "\nOr you can delete the old certificate and generate a new one.\n";

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
