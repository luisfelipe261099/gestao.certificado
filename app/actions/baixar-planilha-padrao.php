<?php
/**
 * Baixar Planilha Padrão - Sistema de Certificados
 * Ação para baixar a planilha padrão para importação de alunos
 */

require_once '../config/config.php';

// Requer login como parceiro
if (!isAuthenticated() || !hasRole(ROLE_PARCEIRO)) {
    $_SESSION['error'] = 'Acesso negado. Faça login como parceiro.';
    header('Location: ' . APP_URL . '/login.php');
    exit;
}

// Verificar se a biblioteca PhpSpreadsheet está disponível
$hasPhpSpreadsheet = class_exists('PhpOffice\PhpSpreadsheet\Spreadsheet');

if ($hasPhpSpreadsheet) {
    // Usar PhpSpreadsheet para criar arquivo Excel
    use PhpOffice\PhpSpreadsheet\Spreadsheet;
    use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
    
    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();
    
    // Definir cabeçalhos
    $headers = ['Nome', 'Email', 'CPF', 'Telefone', 'Data de Nascimento', 'Endereço', 'Cidade', 'Estado', 'CEP'];
    $col = 1;
    foreach ($headers as $header) {
        $sheet->setCellValueByColumnAndRow($col, 1, $header);
        $col++;
    }
    
    // Estilizar cabeçalho
    $headerStyle = [
        'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
        'fill' => ['fillType' => 'solid', 'startColor' => ['rgb' => '4472C4']],
        'alignment' => ['horizontal' => 'center', 'vertical' => 'center']
    ];
    
    $sheet->getStyle('A1:I1')->applyFromArray($headerStyle);
    
    // Adicionar exemplo de linha
    $sheet->setCellValue('A2', 'João Silva');
    $sheet->setCellValue('B2', 'joao@example.com');
    $sheet->setCellValue('C2', '123.456.789-00');
    $sheet->setCellValue('D2', '(11) 99999-9999');
    $sheet->setCellValue('E2', '15/01/1990');
    $sheet->setCellValue('F2', 'Rua Exemplo, 123');
    $sheet->setCellValue('G2', 'São Paulo');
    $sheet->setCellValue('H2', 'SP');
    $sheet->setCellValue('I2', '01234-567');
    
    // Ajustar largura das colunas
    foreach (range('A', 'I') as $col) {
        $sheet->getColumnDimension($col)->setAutoSize(true);
    }
    
    // Definir nome do arquivo
    $filename = 'planilha_alunos_' . date('Y-m-d_H-i-s') . '.xlsx';
    
    // Enviar headers para download
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Cache-Control: no-cache, no-store, must-revalidate');
    header('Pragma: no-cache');
    header('Expires: 0');
    
    // Escrever arquivo
    $writer = new Xlsx($spreadsheet);
    $writer->save('php://output');
    exit;
} else {
    // Fallback: criar CSV
    $filename = 'planilha_alunos_' . date('Y-m-d_H-i-s') . '.csv';
    
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Cache-Control: no-cache, no-store, must-revalidate');
    header('Pragma: no-cache');
    header('Expires: 0');
    
    $output = fopen('php://output', 'w');
    
    // BOM para UTF-8
    fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
    
    // Cabeçalhos
    fputcsv($output, ['Nome', 'Email', 'CPF', 'Telefone', 'Data de Nascimento', 'Endereço', 'Cidade', 'Estado', 'CEP'], ';');
    
    // Exemplo
    fputcsv($output, ['João Silva', 'joao@example.com', '123.456.789-00', '(11) 99999-9999', '15/01/1990', 'Rua Exemplo, 123', 'São Paulo', 'SP', '01234-567'], ';');
    
    fclose($output);
    exit;
}

