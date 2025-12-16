<?php
require_once '../app/config/config.php';

$conn = getDBConnection();

$sql = "ALTER TABLE templates_certificados 
        ADD COLUMN exibir_polo TINYINT(1) DEFAULT 0,
        ADD COLUMN posicao_polo_x INT DEFAULT 0,
        ADD COLUMN posicao_polo_y INT DEFAULT 0,
        ADD COLUMN tamanho_fonte_polo INT DEFAULT 12,
        ADD COLUMN cor_polo VARCHAR(7) DEFAULT '#000000',
        ADD COLUMN fonte_polo VARCHAR(50) DEFAULT 'Arial'";

if ($conn->query($sql) === TRUE) {
    echo "Colunas adicionadas com sucesso!";
} else {
    echo "Erro ao adicionar colunas: " . $conn->error;
}

$conn->close();
?>