<?php
/**
 * Script para baixar e instalar fontes do Google Fonts para o FPDF
 */

// Aumentar tempo de execução
set_time_limit(600);

$fontDir = __DIR__ . '/../../vendor/setasign/fpdf/font/';
$makeFontPath = realpath(__DIR__ . '/../../vendor/setasign/fpdf/makefont/makefont.php');

if (!is_dir($fontDir)) {
    mkdir($fontDir, 0777, true);
}

$fonts = [
    'Roboto' => 'https://github.com/google/fonts/raw/main/apache/roboto/Roboto-Regular.ttf',
    'Lato' => 'https://github.com/google/fonts/raw/main/ofl/lato/Lato-Regular.ttf',
    'Montserrat' => 'https://github.com/google/fonts/raw/main/ofl/montserrat/static/Montserrat-Regular.ttf',
    'Oswald' => 'https://github.com/google/fonts/raw/main/ofl/oswald/static/Oswald-Regular.ttf',
    'Raleway' => 'https://github.com/google/fonts/raw/main/ofl/raleway/static/Raleway-Regular.ttf',
    'Merriweather' => 'https://github.com/google/fonts/raw/main/ofl/merriweather/Merriweather-Regular.ttf',
    'NotoSans' => 'https://github.com/google/fonts/raw/main/ofl/notosans/NotoSans%5Bwdth%2Cwght%5D.ttf', // This might fail if it's variable only, but let's try
    'Poppins' => 'https://github.com/google/fonts/raw/main/ofl/poppins/Poppins-Regular.ttf',
    'Ubuntu' => 'https://github.com/google/fonts/raw/main/ufl/ubuntu/Ubuntu-Regular.ttf',
    'PlayfairDisplay' => 'https://github.com/google/fonts/raw/main/ofl/playfairdisplay/static/PlayfairDisplay-Regular.ttf',
    'PTSans' => 'https://github.com/google/fonts/raw/main/ofl/ptsans/PTSans-Regular.ttf',
    'Nunito' => 'https://github.com/google/fonts/raw/main/ofl/nunito/static/Nunito-Regular.ttf',
    'Lora' => 'https://github.com/google/fonts/raw/main/ofl/lora/static/Lora-Regular.ttf',
    'Mukta' => 'https://github.com/google/fonts/raw/main/ofl/mukta/Mukta-Regular.ttf',
    'Anton' => 'https://github.com/google/fonts/raw/main/ofl/anton/Anton-Regular.ttf',
    'DancingScript' => 'https://github.com/google/fonts/raw/main/ofl/dancingscript/static/DancingScript-Regular.ttf',
    'Pacifico' => 'https://github.com/google/fonts/raw/main/ofl/pacifico/Pacifico-Regular.ttf',
    'Lobster' => 'https://github.com/google/fonts/raw/main/ofl/lobster/Lobster-Regular.ttf',
    'IndieFlower' => 'https://github.com/google/fonts/raw/main/ofl/indieflower/IndieFlower-Regular.ttf'
];

echo "<h1>Instalador de Fontes</h1>\n";
echo "<pre>\n";

foreach ($fonts as $name => $url) {
    echo "Processando <strong>$name</strong>...\n";

    $ttfFile = $fontDir . $name . '.ttf';

    // 1. Download
    if (!file_exists($ttfFile) || filesize($ttfFile) < 1000) {
        echo "  Baixando de $url... ";
        $content = @file_get_contents($url);
        if ($content === false || strlen($content) < 1000) {
            echo "ERRO: Falha ao baixar ou arquivo invalido.\n";
            continue;
        }
        file_put_contents($ttfFile, $content);
        echo "OK.\n";
    } else {
        echo "  Arquivo TTF já existe.\n";
    }

    // 2. MakeFont (via CLI subprocess)
    $phpFile = $fontDir . $name . '.php';
    if (!file_exists($phpFile)) {
        echo "  Gerando definição FPDF... ";

        // Command: php makefont.php font.ttf cp1252
        // We execute it inside the font directory so output goes there
        $cmd = "cd \"$fontDir\" && php \"$makeFontPath\" \"$name.ttf\" cp1252";

        $output = [];
        $returnVar = 0;
        exec($cmd, $output, $returnVar);

        if ($returnVar === 0 && file_exists($phpFile)) {
            echo "OK.\n";
        } else {
            echo "ERRO ao gerar fonte:\n";
            echo implode("\n", $output) . "\n";
        }
    } else {
        echo "  Definição já existe.\n";
    }
    echo "\n";
}

echo "</pre>\n";
echo "Concluído!\n";
