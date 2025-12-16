<?php
// Debug script to generate PDF for ID 53 and save to disk for inspection
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Mock environment
$_SERVER['HTTP_HOST'] = 'localhost';
$_GET['id'] = 53;

require_once __DIR__ . '/bootstrap.php';

// Mock auth if needed (bootstrap might handle session)
if (session_status() !== PHP_SESSION_ACTIVE)
    session_start();
// We might need to fake login if the script checks it. 
// The original script checks: if (!isAuthenticated() || !hasRole(ROLE_PARCEIRO))
// Let's bypass this by defining the functions if they don't exist, or setting session vars.
$_SESSION['user_id'] = 1;
$_SESSION['user_role'] = 'parceiro';
$_SESSION['parceiro_id'] = 31; // Assuming from previous logs

// Copying the core logic from download-certificado.php but removing the output part
// and instead saving to a file.

ob_start();

try {
    $conn = getDBConnection();
    $id = 53;
    $parceiro_id = 31; // We need to be sure about this. 
    // Let's fetch the parceiro_id for cert 53 first to be safe.
    $stmt = $conn->prepare("SELECT parceiro_id FROM certificados WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($row = $res->fetch_assoc()) {
        $parceiro_id = $row['parceiro_id'];
    } else {
        die("Cert 53 not found");
    }

    // Now the main query
    $sql = "SELECT c.id, c.numero_certificado, c.arquivo_url, c.template_id, c.aluno_id, c.curso_id, c.criado_em AS data_geracao,
                   a.nome AS aluno_nome, cu.nome AS curso_nome, cu.carga_horaria AS curso_carga,
                   t.*,
                   ia.data_conclusao
            FROM certificados c
            JOIN alunos a ON a.id = c.aluno_id AND a.parceiro_id = c.parceiro_id
            JOIN cursos cu ON cu.id = c.curso_id AND cu.parceiro_id = c.parceiro_id
            JOIN templates_certificados t ON t.id = c.template_id AND t.parceiro_id = c.parceiro_id
            LEFT JOIN inscricoes_alunos ia ON ia.aluno_id = c.aluno_id AND ia.curso_id = c.curso_id AND ia.parceiro_id = c.parceiro_id
            WHERE c.id = ? AND c.parceiro_id = ? LIMIT 1";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param('ii', $id, $parceiro_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();

    if (!$row)
        die("No data for cert 53");

    $numero = $row['numero_certificado'] ?? (string) $id;
    $template_url = $row['arquivo_url'] ?? '';

    $source = null;
    if ($template_url) {
        $parsed = parse_url($template_url);
        if (isset($parsed['path'])) {
            $path = $parsed['path'];
            if (defined('APP_BASE_PATH') && strpos($path, APP_BASE_PATH) === 0) {
                $path = substr($path, strlen(APP_BASE_PATH));
            }
            $path = '/' . ltrim($path, '/');
            $source = realpath(__DIR__ . '/../../' . ltrim($path, '/'));
        }
    }

    if (!$source || !file_exists($source))
        die("Template file not found: $source");

    require_once __DIR__ . '/../../vendor/autoload.php';

    // Helper hex2rgb
    if (!function_exists('hex2rgb')) {
        function hex2rgb($hex)
        {
            $hex = str_replace('#', '', $hex);
            if (strlen($hex) == 3) {
                $r = hexdec(substr($hex, 0, 1) . substr($hex, 0, 1));
                $g = hexdec(substr($hex, 1, 1) . substr($hex, 1, 1));
                $b = hexdec(substr($hex, 2, 1) . substr($hex, 2, 1));
            } else {
                $r = hexdec(substr($hex, 0, 2));
                $g = hexdec(substr($hex, 2, 2));
                $b = hexdec(substr($hex, 4, 2));
            }
            return [$r, $g, $b];
        }
    }

    $template_w = intval($row['largura_mm'] ?? 2048);
    $template_h = intval($row['altura_mm'] ?? 1152);

    // Posições
    $pos_nome_x = intval($row['posicao_nome_x'] ?? 0);
    $pos_nome_y = intval($row['posicao_nome_y'] ?? 0);
    $pos_data_x = intval($row['posicao_data_x'] ?? 0);
    $pos_data_y = intval($row['posicao_data_y'] ?? 0);
    $pos_curso_x = intval($row['posicao_curso_x'] ?? 0);
    $pos_curso_y = intval($row['posicao_curso_y'] ?? 0);
    $pos_carga_x = intval($row['posicao_carga_horaria_x'] ?? 0);
    $pos_carga_y = intval($row['posicao_carga_horaria_y'] ?? 0);
    $pos_num_x = intval($row['posicao_numero_certificado_x'] ?? 0);
    $pos_num_y = intval($row['posicao_numero_certificado_y'] ?? 0);

    // Visibilidade
    $exibir_nome = isset($row['exibir_nome']) ? (bool) $row['exibir_nome'] : true;
    $exibir_curso = isset($row['exibir_curso']) ? (bool) $row['exibir_curso'] : true;
    $exibir_data = isset($row['exibir_data']) ? (bool) $row['exibir_data'] : true;
    $exibir_carga = isset($row['exibir_carga_horaria']) ? (bool) $row['exibir_carga_horaria'] : true;
    $exibir_num = isset($row['exibir_numero_certificado']) ? (bool) $row['exibir_numero_certificado'] : true;

    // Estilos
    $estilo_nome = ['fonte' => $row['fonte_nome'] ?? 'Arial', 'tamanho' => intval($row['tamanho_fonte_nome'] ?? 24), 'cor' => $row['cor_nome'] ?? '#000000'];
    $estilo_curso = ['fonte' => $row['fonte_curso'] ?? 'Arial', 'tamanho' => intval($row['tamanho_fonte_curso'] ?? 16), 'cor' => $row['cor_curso'] ?? '#000000'];
    $estilo_data = ['fonte' => $row['fonte_data'] ?? 'Arial', 'tamanho' => intval($row['tamanho_fonte_data'] ?? 14), 'cor' => $row['cor_data'] ?? '#000000'];
    $estilo_carga = ['fonte' => $row['fonte_carga_horaria'] ?? 'Arial', 'tamanho' => intval($row['tamanho_fonte_carga_horaria'] ?? 12), 'cor' => $row['cor_carga_horaria'] ?? '#000000'];
    $estilo_num = ['fonte' => $row['fonte_numero_certificado'] ?? 'Arial', 'tamanho' => intval($row['tamanho_fonte_numero_certificado'] ?? 12), 'cor' => $row['cor_numero_certificado'] ?? '#000000'];

    $aluno_nome = (string) ($row['aluno_nome'] ?? '');
    $curso_nome = (string) ($row['curso_nome'] ?? '');
    $data_base = $row['data_conclusao'] ?? $row['data_geracao'] ?? date('Y-m-d');
    $data_fmt = @date('d/m/Y', strtotime($data_base)) ?: $data_base;
    $curso_carga = intval($row['curso_carga'] ?? 0);

    $ext = strtolower(pathinfo($source, PATHINFO_EXTENSION));

    if (in_array($ext, ['jpg', 'jpeg', 'png'])) {
        $size = @getimagesize($source);
        $imgW = $size ? (int) $size[0] : $template_w;
        $imgH = $size ? (int) $size[1] : $template_h;
        $orientation = ($imgW > $imgH) ? 'L' : 'P';

        $pdf = new \FPDF($orientation, 'pt', [$imgW, $imgH]);
        $pdf->SetMargins(0, 0, 0);
        $pdf->SetAutoPageBreak(false);
        $pdf->AddPage($orientation, [$imgW, $imgH]);
        $pdf->Image($source, 0, 0, $imgW, $imgH);

        $w = $imgW;
        $h = $imgH;

        $renderText = function ($pdf, $text, $pos_x, $pos_y, $estilo, $default_y_ratio, $w, $h) {
            $fontFamily = 'Arial';
            if (stripos($estilo['fonte'], 'Times') !== false)
                $fontFamily = 'Times';
            if (stripos($estilo['fonte'], 'Courier') !== false)
                $fontFamily = 'Courier';

            $pdf->SetFont($fontFamily, '', $estilo['tamanho']);
            $rgb = hex2rgb($estilo['cor']);
            $pdf->SetTextColor($rgb[0], $rgb[1], $rgb[2]);

            $y = ($pos_y > 0) ? $pos_y : (int) round($h * $default_y_ratio);

            $textDecoded = mb_convert_encoding($text, 'ISO-8859-1', 'UTF-8');

            if ($pos_x <= 0) {
                $pdf->SetXY(0, $y);
                $pdf->Cell($w, $estilo['tamanho'] + 8, $textDecoded, 0, 1, 'C');
            } else {
                $pdf->SetXY($pos_x, $y);
                $pdf->Cell(0, $estilo['tamanho'] + 8, $textDecoded, 0, 1);
            }
        };

        if ($exibir_nome)
            $renderText($pdf, $aluno_nome, $pos_nome_x, $pos_nome_y, $estilo_nome, 0.40, $w, $h);
        if ($exibir_curso)
            $renderText($pdf, $curso_nome, $pos_curso_x, $pos_curso_y, $estilo_curso, 0.55, $w, $h);
        if ($exibir_data)
            $renderText($pdf, $data_fmt, $pos_data_x, $pos_data_y, $estilo_data, 0.72, $w, $h);
        if ($exibir_carga && $curso_carga > 0) {
            $txtCarga = 'Carga horaria: ' . intval($curso_carga) . ' horas';
            $renderText($pdf, $txtCarga, $pos_carga_x, $pos_carga_y, $estilo_carga, 0.80, $w, $h);
        }
        if ($exibir_num) {
            $txtNumero = 'Numero do Certificado: ' . $numero;
            $renderText($pdf, $txtNumero, $pos_num_x, $pos_num_y, $estilo_num, 0.88, $w, $h);
        }

        $content = $pdf->Output('S');
        file_put_contents(__DIR__ . '/debug_cert_53.pdf', $content);
        echo "PDF generated. Size: " . strlen($content);
    } else {
        echo "Not an image template. Logic for PDF template not fully replicated here.";
    }

} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
