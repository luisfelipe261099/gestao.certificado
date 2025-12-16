<?php
// CRITICAL: Suppress ALL PHP errors that could corrupt PDF
@ini_set('display_errors', '0');
@error_reporting(0);
@ini_set('log_errors', '1');
@ini_set('error_log', __DIR__ . '/../../logs/error.log');

ob_start();

require_once __DIR__ . '/bootstrap.php';

// Requer login como parceiro OU admin
if (!isAuthenticated()) {
    $_SESSION['error'] = 'Acesso negado.';
    header('Location: ' . APP_URL . '/login.php');
    exit;
}

// Verificar se é parceiro ou admin
$is_admin = hasRole(ROLE_ADMIN);
$is_parceiro = hasRole(ROLE_PARCEIRO);

if (!$is_admin && !$is_parceiro) {
    $_SESSION['error'] = 'Acesso negado.';
    header('Location: ' . APP_URL . '/login.php');
    exit;
}

$user = getCurrentUser();
$parceiro_id = $user['parceiro_id'] ?? $user['id'];

// Se for admin visualizando template do sistema, não precisa de parceiro_id
if ($is_admin) {
    $parceiro_id = 0; // Admin pode ver qualquer template
}

try {
    $conn = getDBConnection();
    $template_id = isset($_GET['id']) ? (int) $_GET['id'] : 0;

    if ($template_id <= 0) {
        throw new Exception('Template inválido');
    }

    // Buscar template completo
    // Admin pode ver qualquer template, parceiro só vê os seus ou do sistema
    if ($is_admin) {
        $stmt = $conn->prepare('SELECT * FROM templates_certificados WHERE id = ? LIMIT 1');
        $stmt->bind_param('i', $template_id);
    } else {
        $stmt = $conn->prepare('
            SELECT * FROM templates_certificados 
            WHERE id = ? AND (parceiro_id = ? OR template_sistema = 1)
            LIMIT 1
        ');
        $stmt->bind_param('ii', $template_id, $parceiro_id);
    }

    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        throw new Exception('Template não encontrado');
    }

    $t = $result->fetch_assoc();
    $stmt->close();

    // Buscar campos customizados do template (como CPF)
    $campos_customizados = [];
    $stmt = $conn->prepare("SELECT * FROM template_campos_customizados WHERE template_id = ? ORDER BY ordem ASC");
    $stmt->bind_param('i', $template_id);
    $stmt->execute();
    $result_campos = $stmt->get_result();
    while ($campo = $result_campos->fetch_assoc()) {
        $campos_customizados[] = $campo;
    }
    $stmt->close();

    // Dados fictícios para preview
    $aluno_nome = "Maria da Silva Santos";
    $curso_nome = "Melaama Control";
    $data_conclusao = date('Y-m-d');
    $curso_carga = 80;
    $numero_certificado = "PREVIEW-" . date('YmdHis');
    $aluno_cpf = "123.456.789-00"; // CPF fictício

    // Extrair URL do template
    $template_arquivo_url = $t['arquivo_url'] ?? '';
    if (empty($template_arquivo_url)) {
        throw new Exception('Template sem arquivo configurado');
    }

    // Converter URL para caminho filesystem
    $app_root = dirname(__DIR__, 2); // Sobe 2 níveis: actions -> app -> root

    // Remove a URL base (APP_URL) para pegar o caminho relativo
    $relative_path = str_replace(APP_URL, '', $template_arquivo_url);

    // Constrói o caminho absoluto do sistema de arquivos
    $source = $app_root . $relative_path;

    // Normaliza os separadores de diretório para o SO atual
    $source = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $source);

    if (!file_exists($source)) {
        throw new Exception('Arquivo do template não encontrado: ' . $source);
    }

    // Carregar FPDF
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

    $ext = strtolower(pathinfo($source, PATHINFO_EXTENSION));

    // Gerar PDF com a imagem de template
    if (in_array($ext, ['jpg', 'jpeg', 'png'])) {
        $size = @getimagesize($source);
        if (!$size) {
            throw new Exception('Não foi possível ler dimensões da imagem');
        }

        $imgW = (int) $size[0];
        $imgH = (int) $size[1];
        $orientation = ($imgW > $imgH) ? 'L' : 'P';

        $pdf = new \FPDF($orientation, 'pt', [$imgW, $imgH]);
        $pdf->SetMargins(0, 0, 0);
        $pdf->SetAutoPageBreak(false);
        $pdf->AddPage($orientation, [$imgW, $imgH]);
        $pdf->Image($source, 0, 0, $imgW, $imgH);

        $w = $imgW;
        $h = $imgH;

        // CRITICAL: Use TRUE PIXELS (no scaling)
        // The editor now saves coordinates relative to the natural image size
        $scale_x = 1;
        $scale_y = 1;

        error_log("SCALE DEBUG: Real=$imgW x $imgH, Saved=$template_width x $template_height, Scale=$scale_x x $scale_y");

        // Função helper para renderizar texto
        $renderText = function ($pdf, $text, $pos_x, $pos_y, $fonte, $tamanho, $cor, $default_y_ratio, $w, $h, $scale_x, $scale_y) {
            $fontFamily = 'Arial';
            if (stripos($fonte, 'Times') !== false)
                $fontFamily = 'Times';
            if (stripos($fonte, 'Courier') !== false)
                $fontFamily = 'Courier';
            if (stripos($fonte, 'Ceviche') !== false) {
                $pdf->AddFont('CevicheOne-Regular', '', 'CevicheOne-Regular.php');
                $fontFamily = 'CevicheOne-Regular';
            } else {
                // Tentar carregar fonte customizada se existir
                // O nome da fonte no dropdown (ex: Roboto-Regular) deve bater com o nome do arquivo .php
                $fontFile = $fonte . '.php';
                // Caminho relativo para a pasta de fontes do FPDF
                // visualizar-template.php está em app/actions/
                // vendor está em ../../vendor/
                $fontPath = __DIR__ . '/../../vendor/setasign/fpdf/font/' . $fontFile;

                if (file_exists($fontPath)) {
                    $pdf->AddFont($fonte, '', $fontFile);
                    $fontFamily = $fonte;
                }
            }

            // Se for fonte customizada, não usar negrito ('B') se não tiver variante
            $style = 'B';
            if ($fontFamily == 'CevicheOne-Regular') {
                $style = '';
            }

            $pdf->SetFont($fontFamily, $style, $tamanho);
            $rgb = hex2rgb($cor);
            $pdf->SetTextColor($rgb[0], $rgb[1], $rgb[2]);

            // Apply scaling to coordinates
            $y = ($pos_y > 0) ? ($pos_y * $scale_y) : (int) round($h * $default_y_ratio);
            $x_scaled = ($pos_x > 0) ? ($pos_x * $scale_x) : 0;

            error_log("RENDER TEXT: Original pos=($pos_x,$pos_y), Scaled pos=($x_scaled,$y)");

            $textDecoded = mb_convert_encoding($text, 'ISO-8859-1', 'UTF-8');

            if ($pos_x <= 0) {
                $pdf->SetXY(0, $y);
                $pdf->Cell($w, $tamanho + 8, $textDecoded, 0, 1, 'C');
            } else {
                $pdf->SetXY($x_scaled, $y);
                $pdf->Cell(0, $tamanho + 8, $textDecoded, 0, 1);
            }
        };

        // Nome do Aluno
        if ($t['exibir_nome'] ?? 1) {
            $renderText(
                $pdf,
                $aluno_nome,
                (int) ($t['posicao_nome_x'] ?? 0),
                (int) ($t['posicao_nome_y'] ?? 0),
                $t['fonte_nome'] ?? 'Arial',
                (int) ($t['tamanho_fonte_nome'] ?? 24),
                $t['cor_nome'] ?? '#000000',
                0.40,
                $w,
                $h,
                $scale_x,
                $scale_y
            );
        }

        // Nome do Curso
        if ($t['exibir_curso'] ?? 1) {
            $renderText(
                $pdf,
                $curso_nome,
                (int) ($t['posicao_curso_x'] ?? 0),
                (int) ($t['posicao_curso_y'] ?? 0),
                $t['fonte_curso'] ?? 'Arial',
                (int) ($t['tamanho_fonte_curso'] ?? 16),
                $t['cor_curso'] ?? '#000000',
                0.55,
                $w,
                $h,
                $scale_x,
                $scale_y
            );
        }

        // Data de Conclusão
        if ($t['exibir_data'] ?? 1) {
            $data_fmt = date('d/m/Y', strtotime($data_conclusao));
            $renderText(
                $pdf,
                $data_fmt,
                (int) ($t['posicao_data_x'] ?? 0),
                (int) ($t['posicao_data_y'] ?? 0),
                $t['fonte_data'] ?? 'Arial',
                (int) ($t['tamanho_fonte_data'] ?? 14),
                $t['cor_data'] ?? '#000000',
                0.72,
                $w,
                $h,
                $scale_x,
                $scale_y
            );
        }

        // Carga Horária (apenas o número de horas)
        if (($t['exibir_carga_horaria'] ?? 1) && $curso_carga > 0) {
            $txtCarga = $curso_carga; // Apenas o número
            $renderText(
                $pdf,
                $txtCarga,
                (int) ($t['posicao_carga_horaria_x'] ?? 0),
                (int) ($t['posicao_carga_horaria_y'] ?? 0),
                $t['fonte_carga_horaria'] ?? 'Arial',
                (int) ($t['tamanho_fonte_carga_horaria'] ?? 12),
                $t['cor_carga_horaria'] ?? '#000000',
                0.80,
                $w,
                $h,
                $scale_x,
                $scale_y
            );
        }

        // Número do Certificado
        if ($t['exibir_numero_certificado'] ?? 1) {
            $txtNumero = 'Numero do Certificado: ' . $numero_certificado;
            $renderText(
                $pdf,
                $txtNumero,
                (int) ($t['posicao_numero_certificado_x'] ?? 0),
                (int) ($t['posicao_numero_certificado_y'] ?? 0),
                $t['fonte_numero_certificado'] ?? 'Arial',
                (int) ($t['tamanho_fonte_numero_certificado'] ?? 12),
                $t['cor_numero_certificado'] ?? '#000000',
                0.88,
                $w,
                $h,
                $scale_x,
                $scale_y
            );
        }

        // Nome do Polo Parceiro
        if (($t['exibir_polo'] ?? 0) || ($t['id'] == 25)) {
            $nome_polo = "Polo Parceiro Exemplo";
            $renderText(
                $pdf,
                $nome_polo,
                (int) ($t['posicao_polo_x'] ?? 0),
                (int) ($t['posicao_polo_y'] ?? 0),
                $t['fonte_polo'] ?? 'Arial',
                (int) ($t['tamanho_fonte_polo'] ?? 12),
                $t['cor_polo'] ?? '#000000',
                0.95,
                $w,
                $h,
                $scale_x,
                $scale_y
            );
        }

        // QR Code (Preview)
        if ($t['exibir_qrcode'] ?? 0) {
            file_put_contents(__DIR__ . '/debug_preview.log', "QR Code ATIVADO para template {$template_id}\n", FILE_APPEND);

            $qrUrl = APP_URL . '/verificar-certificado.php?codigo=PREVIEW';
            $qrApiUrl = 'https://api.qrserver.com/v1/create-qr-code/?size=150x150&data=' . urlencode($qrUrl);

            // Download QR Code
            $tempQr = tempnam(sys_get_temp_dir(), 'qr_preview_');
            $qrContent = @file_get_contents($qrApiUrl);

            // Fallback cURL
            if (!$qrContent && function_exists('curl_init')) {
                file_put_contents(__DIR__ . '/debug_preview.log', "file_get_contents falhou, tentando cURL...\n", FILE_APPEND);
                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, $qrApiUrl);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
                $qrContent = curl_exec($ch);
                curl_close($ch);
            }

            if ($qrContent) {
                file_put_contents(__DIR__ . '/debug_preview.log', "Imagem baixada com sucesso (" . strlen($qrContent) . " bytes)\n", FILE_APPEND);
                file_put_contents($tempQr, $qrContent);

                $qrX = (int) ($t['posicao_qrcode_x'] ?? 0);
                $qrY = (int) ($t['posicao_qrcode_y'] ?? 0);
                $qrSize = (int) ($t['tamanho_qrcode'] ?? 100);

                // Apply scaling
                $qrX_scaled = ($qrX > 0) ? ($qrX * $scale_x) : ($w - 120);
                $qrY_scaled = ($qrY > 0) ? ($qrY * $scale_y) : ($h - 120);

                $pdf->Image($tempQr, $qrX_scaled, $qrY_scaled, $qrSize, $qrSize, 'PNG');

                // Render Text Details below QR Code (Preview)
                $pdf->SetFont('Arial', 'B', 8);
                $pdf->SetTextColor(0, 0, 0);

                $textY = $qrY_scaled + $qrSize + 5;

                // Title
                $pdf->SetXY($qrX_scaled - 20, $textY);
                $pdf->Cell($qrSize + 40, 10, utf8_decode('VALIDAÇÃO DE DIPLOMA'), 0, 1, 'C');

                // Code
                $pdf->SetFont('Arial', '', 7);
                $pdf->SetXY($qrX_scaled - 20, $textY + 8);
                $pdf->Cell($qrSize + 40, 10, utf8_decode('Código: ' . $numero_certificado), 0, 1, 'C');

                // URL
                $pdf->SetFont('Arial', '', 6);
                $pdf->SetXY($qrX_scaled - 40, $textY + 16);
                // Remove protocol for cleaner look
                $displayUrl = str_replace(['http://', 'https://'], '', APP_URL . '/verificar-certificado');
                $pdf->Cell($qrSize + 80, 10, $displayUrl, 0, 1, 'C');

                @unlink($tempQr);
            } else {
                file_put_contents(__DIR__ . '/debug_preview.log', "FALHA ao baixar imagem QR Code\n", FILE_APPEND);
            }
        } else {
            file_put_contents(__DIR__ . '/debug_preview.log', "QR Code DESATIVADO para template {$template_id}\n", FILE_APPEND);
        }

        // Renderizar CAMPOS CUSTOMIZADOS (CPF, etc)
        foreach ($campos_customizados as $campo) {
            $valor_campo = '';

            // Definir valor fictício baseado no label
            if (stripos($campo['label'], 'CPF') !== false) {
                $valor_campo = $aluno_cpf;
            } elseif (stripos($campo['label'], 'RG') !== false) {
                $valor_campo = "12.345.678-9";
            } else {
                $valor_campo = $campo['valor_padrao'] ?? "Exemplo " . $campo['label'];
            }

            if (!empty($valor_campo)) {
                $renderText(
                    $pdf,
                    $valor_campo,
                    (int) ($campo['posicao_x'] ?? 0),
                    (int) ($campo['posicao_y'] ?? 0),
                    'Arial',  // Fonte padrão para campos customizados
                    (int) ($campo['tamanho_fonte'] ?? 12),
                    $campo['cor_hex'] ?? '#000000',
                    0.50,  // Posição padrão (meio da página)
                    $w,
                    $h,
                    $scale_x,
                    $scale_y
                );
            }
        }

        // Gerar PDF e enviar
        $pdfContent = $pdf->Output('S');

        // Limpar todo buffer
        while (ob_get_level()) {
            ob_end_clean();
        }

        // Headers para visualização inline (não download)
        header('Content-Type: application/pdf');
        header('Content-Disposition: inline; filename="preview_' . preg_replace('/[^A-Za-z0-9_-]/', '', $t['nome']) . '.pdf"');
        header('Content-Length: ' . strlen($pdfContent));
        header('Cache-Control: no-cache, no-store, must-revalidate');
        header('Pragma: no-cache');
        header('Expires: 0');

        echo $pdfContent;
        exit;

    } else {
        throw new Exception('Tipo de arquivo não suportado para preview. Use JPG ou PNG.');
    }

} catch (Exception $e) {
    if (ob_get_level())
        ob_end_clean();
    http_response_code(500);
    die('Erro ao gerar preview: ' . $e->getMessage());
}
