<?php
/**
 * Action: Salvar Posições e Estilos do Template
 */

require_once '../config/config.php';

header('Content-Type: application/json');

if (!isAuthenticated() || !hasRole(ROLE_ADMIN)) {
    echo json_encode(['success' => false, 'error' => 'Acesso negado']);
    exit;
}

// Receber dados JSON
$json = file_get_contents('php://input');
$data = json_decode($json, true);

$template_id = (int) ($data['template_id'] ?? 0);
$posicoes = $data['posicoes'] ?? [];

if ($template_id <= 0) {
    echo json_encode(['success' => false, 'error' => 'Template inválido']);
    exit;
}

try {
    $conn = getDBConnection();

    // Verificar se é template do sistema
    $stmt = $conn->prepare("SELECT id FROM templates_certificados WHERE id = ? AND template_sistema = 1");
    $stmt->bind_param("i", $template_id);
    $stmt->execute();
    if ($stmt->get_result()->num_rows == 0) {
        throw new Exception('Template não encontrado ou não é do sistema');
    }
    $stmt->close();

    // Atualizar posições e estilos
    foreach ($posicoes as $pos) {
        $fontSize = isset($pos['fontSize']) ? intval($pos['fontSize']) : null;
        $color = isset($pos['color']) ? $pos['color'] : null;
        $fontFamily = isset($pos['fontFamily']) ? $pos['fontFamily'] : null;

        if ($pos['tipo'] == 'customizado') {
            // Campo customizado
            $sql = "UPDATE template_campos_customizados SET posicao_x = ?, posicao_y = ?";
            $types = "ii";
            $params = [$pos['x'], $pos['y']];

            if ($fontSize !== null) {
                $sql .= ", tamanho_fonte = ?";
                $types .= "i";
                $params[] = $fontSize;
            }
            if ($color !== null) {
                $sql .= ", cor_hex = ?";
                $types .= "s";
                $params[] = $color;
            }
            if ($fontFamily !== null) {
                $sql .= ", fonte = ?";
                $types .= "s";
                $params[] = $fontFamily;
            }

            $sql .= " WHERE id = ?";
            $types .= "i";
            $params[] = $pos['id'];

            $stmt = $conn->prepare($sql);
            $stmt->bind_param($types, ...$params);
            $stmt->execute();
            $stmt->close();

        } else if ($pos['tipo'] == 'padrao') {
            // Campo padrão
            $campo = $pos['campo'];
            $sql = "";
            $types = "ii";
            $params = [$pos['x'], $pos['y']];

            // Mapeamento de campos para colunas
            $map = [
                'curso' => ['x' => 'posicao_curso_x', 'y' => 'posicao_curso_y', 'size' => 'tamanho_fonte_curso', 'color' => 'cor_curso', 'font' => 'fonte_curso'],
                'carga_horaria' => ['x' => 'posicao_carga_horaria_x', 'y' => 'posicao_carga_horaria_y', 'size' => 'tamanho_fonte_carga_horaria', 'color' => 'cor_carga_horaria', 'font' => 'fonte_carga_horaria'],
                'polo' => ['x' => 'posicao_polo_x', 'y' => 'posicao_polo_y', 'size' => 'tamanho_fonte_polo', 'color' => 'cor_polo', 'font' => 'fonte_polo'],
            ];

            if (isset($map[$campo])) {
                $cols = $map[$campo];
                $sql = "UPDATE templates_certificados SET {$cols['x']} = ?, {$cols['y']} = ?";

                if ($fontSize !== null) {
                    $sql .= ", {$cols['size']} = ?";
                    $types .= "i";
                    $params[] = $fontSize;
                }
                if ($color !== null) {
                    $sql .= ", {$cols['color']} = ?";
                    $types .= "s";
                    $params[] = $color;
                }
                if ($fontFamily !== null) {
                    $sql .= ", {$cols['font']} = ?";
                    $types .= "s";
                    $params[] = $fontFamily;
                }

                $sql .= " WHERE id = ?";
                $types .= "i";
                $params[] = $template_id;

                $stmt = $conn->prepare($sql);
                $stmt->bind_param($types, ...$params);
                $stmt->execute();
                $stmt->close();
            }
        }
    }



    // Salvar configurações do QR Code
    if (isset($data['qrcode']) && is_array($data['qrcode'])) {
        $qr = $data['qrcode'];
        $exibir = isset($qr['exibir']) ? ($qr['exibir'] ? 1 : 0) : 0;
        $x = isset($qr['x']) ? intval($qr['x']) : 0;
        $y = isset($qr['y']) ? intval($qr['y']) : 0;
        $tamanho = isset($qr['tamanho']) ? intval($qr['tamanho']) : 100;

        $stmt = $conn->prepare("UPDATE templates_certificados SET exibir_qrcode = ?, posicao_qrcode_x = ?, posicao_qrcode_y = ?, tamanho_qrcode = ? WHERE id = ?");
        $stmt->bind_param("iiiii", $exibir, $x, $y, $tamanho, $template_id);
        $stmt->execute();
        $stmt->close();
    }

    echo json_encode(['success' => true]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>